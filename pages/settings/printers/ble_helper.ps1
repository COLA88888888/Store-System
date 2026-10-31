# ble_helper.ps1 — ໂຕຊ່ວຍພິມ Bluetooth ທີ່ເປີດຄ້າງໄວ້ (ble_bridge.php ເປີດໃຫ້ເອງ ເທື່ອທຳອິດທີ່ຕ້ອງໃຊ້).
#
# ble_send.ps1 ເດີມ: ທຸກບິນເປີດ PowerShell ໃໝ່ + ເຊື່ອມ BLE ໃໝ່ + ຊອກ service ໃໝ່ = ຊ້າ 2.5–6 ວິ ກ່ອນເລີ່ມສົ່ງ.
# ໂຕນີ້ເປີດຄັ້ງດຽວ ຖືການເຊື່ອມຕໍ່ໄວ້ (GattSession.MaintainConnection) → ບິນສົ່ງອອກທັນທີ.
# ເຄື່ອງພິມປິດຢູ່ → ລອງເຊື່ອມໃໝ່ເອງທຸກ $RetryMs; ເປີດເຄື່ອງພິມເມື່ອໃດ ເຊື່ອມຕໍ່ເອງເລີຍ (ບໍ່ຕ້ອງເພີ່ມເຄື່ອງໃໝ່).
#
# ຟັງຢູ່ 127.0.0.1:$Port ເທົ່ານັ້ນ. ຄຳສັ່ງ (1 ແຖວ, ຊື່ເຄື່ອງພິມເປັນ base64 UTF-8):
#   CHECK <name64>\n           → "ON" / "OFF"   (ເຊື່ອມຢູ່ບໍ; ຖ້າບໍ່ ແລະ ບໍ່ໄດ້ລອງມາ 3 ວິ ກໍລອງເຊື່ອມເລີຍ)
#   PRINT <name64> <len>\n<bytes> → "OK" / "ERR_..."
#   QUIT\n                     → ປິດໂຕຊ່ວຍ
# ບໍ່ມີຄຳສັ່ງມາ $IdleExitMin ນາທີ (ປິດໂປຣແກຣມ POS ແລ້ວ) → ປິດເອງ.
param(
  [int]$Port = 9133,
  [Parameter(Mandatory = $true)][string]$CacheFile,
  [int]$RetryMs = 4000,
  [int]$IdleExitMin = 30
)
$ErrorActionPreference = 'Stop'

$listener = New-Object System.Net.Sockets.TcpListener ([System.Net.IPAddress]::Loopback, $Port)
try { $listener.Start() } catch { exit 0 }   # ມີໂຕຊ່ວຍເປີດຢູ່ແລ້ວ

Add-Type -AssemblyName System.Runtime.WindowsRuntime
$asTaskGeneric = ([System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
  $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1'
})[0]
function Await($WinRtTask, $ResultType, [int]$TimeoutMs = 8000) {
  $netTask = $asTaskGeneric.MakeGenericMethod($ResultType).Invoke($null, @($WinRtTask))
  if (-not $netTask.Wait($TimeoutMs)) { throw 'ERR_TIMEOUT' }
  return ,$netTask.Result
}
[Windows.Devices.Enumeration.DeviceInformation, Windows.Devices.Enumeration, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.BluetoothLEDevice, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.BluetoothCacheMode, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceService, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristic, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattCommunicationStatus, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattWriteOption, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattSession, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Storage.Streams.DataWriter, Windows.Storage.Streams, ContentType=WindowsRuntime] | Out-Null

$wm = ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristic].GetMethods() | Where-Object {
  $_.Name -eq 'WriteValueAsync' -and $_.GetParameters().Count -eq 2
})[0]
$PREF = @('000018f0-0000-1000-8000-00805f9b34fb', '49535343-fe7d-4ae5-8fa9-9fafd205e455', '0000ff00-0000-1000-8000-00805f9b34fb', 'e7810a71-73ae-499d-8c15-faa9aef0c3f2')

# ສະຖານະການເຊື່ອມຕໍ່ປັດຈຸບັນ
$S = @{ name = ''; ble = $null; svc = $null; ch = $null; sess = $null; opt = $null; lastTry = [DateTime]::MinValue }

function Read-Cache {
  $c = @{}
  if (Test-Path $CacheFile) {
    try {
      $raw = Get-Content -Path $CacheFile -Raw -Encoding UTF8 | ConvertFrom-Json
      foreach ($p in $raw.PSObject.Properties) { $c[$p.Name] = $p.Value }
    } catch {}
  }
  return $c
}

function Drop-Conn {
  foreach ($k in @('sess', 'svc', 'ble')) { if ($S[$k]) { try { $S[$k].Dispose() } catch {} } }
  $S.ble = $null; $S.svc = $null; $S.ch = $null; $S.sess = $null; $S.opt = $null
}

function Is-On {
  if (-not $S.ch -or -not $S.ble) { return $false }
  try { return ($S.ble.ConnectionStatus.ToString() -eq 'Connected') } catch { return $false }
}

# ເຊື່ອມຕໍ່ເຄື່ອງພິມຊື່ $name (ໃຊ້ id/service/char ຈາກ ble_cache.json ຄື ble_send.ps1). ຄືນ $true ຖ້າສຳເລັດ.
function Connect-Printer([string]$name) {
  $S.lastTry = [DateTime]::Now
  Drop-Conn
  $S.name = $name
  try {
    $cache = Read-Cache
    $entry = $cache[$name]
    if (-not $entry -or -not $entry.id) {
      $selector = 'System.Devices.Aep.ProtocolId:="{bb7bb05e-5972-42b5-94fc-76eaa7084d49}" AND System.ItemNameDisplay:="' + $name.Replace('"', '') + '"'
      $devs = Await ([Windows.Devices.Enumeration.DeviceInformation]::FindAllAsync($selector, $null, [Windows.Devices.Enumeration.DeviceInformationKind]::AssociationEndpoint)) ([Windows.Devices.Enumeration.DeviceInformationCollection]) 40000
      $found = $devs | Select-Object -First 1
      if (-not $found) { return $false }
      $entry = [PSCustomObject]@{ id = $found.Id; service = ''; char = '' }
    }
    # ລໍແຕ່ລະຂັ້ນສັ້ນໆ ($T): ເຄື່ອງພິມປິດຢູ່ → ຮູ້ພາຍໃນ ~4 ວິ (ເມື່ອກ່ອນ 8 ວິ ຫຼາຍຂັ້ນ ລວມ 25–31 ວິ ຕໍ່ການກວດ)
    $T = 4000
    $S.ble = Await ([Windows.Devices.Bluetooth.BluetoothLEDevice]::FromIdAsync($entry.id)) ([Windows.Devices.Bluetooth.BluetoothLEDevice]) $T
    if (-not $S.ble) { return $false }
    $unc = [Windows.Devices.Bluetooth.BluetoothCacheMode]::Uncached
    $ch = $null
    if ($entry.service -and $entry.char) {
      $srvs = Await ($S.ble.GetGattServicesForUuidAsync([Guid]$entry.service, $unc)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceServicesResult]) $T
      # ເຄື່ອງພິມບໍ່ຕອບ (ປິດ / ຢູ່ໄກ) → ຢຸດເລີຍ, ບໍ່ຄົ້ນຫາ service ທັງໝົດຊ້ຳ
      if ($srvs.Status -ne 'Success') { Drop-Conn; return $false }
      if ($srvs.Services.Count -gt 0) {
        $S.svc = $srvs.Services | Select-Object -First 1
        $chs = Await ($S.svc.GetCharacteristicsForUuidAsync([Guid]$entry.char, $unc)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristicsResult]) $T
        if ($chs.Status -eq 'Success' -and $chs.Characteristics.Count -gt 0) { $ch = $chs.Characteristics | Select-Object -First 1 }
      }
    }
    if (-not $ch) {
      $srvs = Await ($S.ble.GetGattServicesAsync($unc)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceServicesResult]) $T
      if ($srvs.Status -ne 'Success') { Drop-Conn; return $false }
      foreach ($u in $PREF) {
        $s = $srvs.Services | Where-Object { $_.Uuid.ToString().ToLower() -eq $u } | Select-Object -First 1
        if (-not $s) { continue }
        $chs = Await ($s.GetCharacteristicsAsync($unc)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristicsResult]) $T
        foreach ($c in $chs.Characteristics) {
          if ([int]$c.CharacteristicProperties -band 12) {
            $S.svc = $s; $ch = $c
            $cache[$name] = [PSCustomObject]@{ id = $entry.id; service = $s.Uuid.ToString(); char = $c.Uuid.ToString() }
            try { $cache | ConvertTo-Json -Depth 4 | Set-Content -Path $CacheFile -Encoding UTF8 } catch {}
            break
          }
        }
        if ($ch) { break }
      }
    }
    if (-not $ch) { Drop-Conn; return $false }
    $S.ch = $ch
    $S.opt = if ([int]$ch.CharacteristicProperties -band 4) {
      [Windows.Devices.Bluetooth.GenericAttributeProfile.GattWriteOption]::WriteWithoutResponse
    } else {
      [Windows.Devices.Bluetooth.GenericAttributeProfile.GattWriteOption]::WriteWithResponse
    }
    try {
      $S.sess = Await ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattSession]::FromDeviceIdAsync($S.ble.BluetoothDeviceId)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattSession]) $T
      if ($S.sess) { $S.sess.MaintainConnection = $true }   # ຖືການເຊື່ອມຕໍ່ໄວ້ລະຫວ່າງບິນ
    } catch {}
    return $true
  } catch {
    Drop-Conn
    return $false
  }
}

# ກວດວ່າເຄື່ອງພິມຍັງຢູ່ແທ້ (ຖາມເຄື່ອງພິມໂດຍກົງ): ConnectionStatus ຂອງ Windows ຊ້າ 10-30 ວິ ກວ່າຈະຮູ້ວ່າປິດເຄື່ອງ,
# ສ່ວນການຖາມ service ແບບ Uncached ຕ້ອງໄປຮອດເຄື່ອງພິມ — ບໍ່ຕອບພາຍໃນ $ms → ຖືວ່າປິດແລ້ວ
function Probe-Alive([int]$ms) {
  if (-not $S.ble -or -not $S.svc) { return $false }
  try {
    $r = Await ($S.ble.GetGattServicesForUuidAsync($S.svc.Uuid, [Windows.Devices.Bluetooth.BluetoothCacheMode]::Uncached)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceServicesResult]) $ms
    return ($r.Status -eq 'Success' -and $r.Services.Count -gt 0)
  } catch { return $false }
}

function Ensure-Printer([string]$name) {
  if ($S.name -eq $name -and (Is-On)) { return $true }
  return (Connect-Printer $name)
}

function Write-Chunk([byte[]]$part) {
  $dw = New-Object Windows.Storage.Streams.DataWriter
  $dw.WriteBytes($part)
  return (Await ($wm.Invoke($S.ch, @($dw.DetachBuffer(), $S.opt))) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCommunicationStatus]) 5000)
}

# ສົ່ງບິນ: ອັດຕາດຽວກັບ ble_send.ps1 (Printer001: 42000 B/s Buffer ລົ້ນ → ບິນເປັນຕົວອັກສອນແປກ; 28000 ພິມຖືກ)
function Send-Bytes([byte[]]$all) {
  $sw = [System.Diagnostics.Stopwatch]::StartNew()
  $off = 0; $burst = 3600.0; $rate = 28000.0; $chunk = 244
  while ($off -lt $all.Length) {
    $len = [Math]::Min($chunk, $all.Length - $off)
    $part = New-Object byte[] $len
    [Array]::Copy($all, $off, $part, 0, $len)
    $st = Write-Chunk $part
    if ($st -ne 'Success') {
      Start-Sleep -Milliseconds 30
      $st = Write-Chunk $part
      if ($st -ne 'Success') { throw "ERR_WRITE_$st" }
    }
    $off += $len
    if ($off -gt $burst) {
      $ahead = (($off - $burst) * 1000.0 / $rate) - $sw.Elapsed.TotalMilliseconds
      if ($ahead -gt 3) { Start-Sleep -Milliseconds ([int]$ahead) }
    }
  }
}

function Read-Line($stream) {
  $ms = New-Object System.IO.MemoryStream
  while ($true) {
    $b = $stream.ReadByte()
    if ($b -lt 0 -or $b -eq 10) { break }
    $ms.WriteByte([byte]$b)
    if ($ms.Length -gt 4096) { break }
  }
  return [System.Text.Encoding]::ASCII.GetString($ms.ToArray()).Trim()
}

function Decode-Name([string]$b64) {
  try { return [System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($b64)) } catch { return '' }
}

function Handle-Client($client) {
  $client.ReceiveTimeout = 10000
  $client.SendTimeout = 10000
  $stream = $client.GetStream()
  $reply = 'ERR_BAD_REQUEST'
  try {
    $parts = (Read-Line $stream) -split ' '
    switch ($parts[0]) {
      'CHECK' {
        # ຕອບທັນທີຕາມສະຖານະປັດຈຸບັນ (ເຄື່ອງພິມປິດ/ຫຼຸດ → OFF ທັນທີ); ການເຊື່ອມໃໝ່ເຮັດຢູ່ເບື້ອງຫຼັງ (loop ລຸ່ມ).
        # ລອງເຊື່ອມໃນຄຳສັ່ງນີ້ສະເພາະເທື່ອທຳອິດຂອງເຄື່ອງພິມນີ້ (ເປີດໂປຣແກຣມ / ປ່ຽນເຄື່ອງພິມ)
        $name = Decode-Name $parts[1]
        if ($S.name -eq $name) {
          # ປິດເຄື່ອງພິມ → ຕັດທັນທີ (ລອງ 2 ເທື່ອ ກັນສັນຍານສະດຸດຊົ່ວຄາວ); loop ລຸ່ມຈະເຊື່ອມໃໝ່ເອງເມື່ອເປີດເຄື່ອງ
          if ((Is-On) -and ((Probe-Alive 1500) -or (Probe-Alive 1000))) { $reply = 'ON' }
          else {
            if ($S.ch) { Drop-Conn; $S.lastTry = [DateTime]::Now }
            $reply = 'OFF'
          }
        }
        else { $reply = if (Connect-Printer $name) { 'ON' } else { 'OFF' } }
      }
      'PRINT' {
        $name = Decode-Name $parts[1]
        $len = [int]$parts[2]
        $buf = New-Object byte[] $len
        $got = 0
        while ($got -lt $len) {
          $n = $stream.Read($buf, $got, $len - $got)
          if ($n -le 0) { throw 'ERR_SHORT_READ' }
          $got += $n
        }
        if (-not (Ensure-Printer $name)) { $reply = 'ERR_OFFLINE' }
        else {
          try { Send-Bytes $buf; $reply = 'OK' }
          catch {
            # ເຊື່ອມຕໍ່ຂາດລະຫວ່າງບິນ (ປິດ/ເປີດເຄື່ອງພິມ) → ເຊື່ອມໃໝ່ ແລ້ວສົ່ງຄືນອີກເທື່ອດຽວ
            if (Connect-Printer $name) {
              try { Send-Bytes $buf; $reply = 'OK' } catch { $reply = "$($_.Exception.Message)" }
            } else { $reply = 'ERR_OFFLINE' }
          }
        }
      }
      'QUIT' { $reply = 'BYE' }
    }
  } catch {
    $reply = "ERR_$($_.Exception.Message)"
  }
  try {
    $out = [System.Text.Encoding]::ASCII.GetBytes($reply + "`n")
    $stream.Write($out, 0, $out.Length)
    $stream.Flush()
  } catch {}
  try { $client.Close() } catch {}
  return $reply
}

$lastRequest = [DateTime]::Now
try {
  while ($true) {
    if ($listener.Pending()) {
      $lastRequest = [DateTime]::Now
      if ((Handle-Client ($listener.AcceptTcpClient())) -eq 'BYE') { break }
      continue
    }
    if (([DateTime]::Now - $lastRequest).TotalMinutes -ge $IdleExitMin) { break }
    # ເຄື່ອງພິມຫຼຸດ/ປິດ → ລອງເຊື່ອມໃໝ່ເອງເປັນໄລຍະ: ເປີດເຄື່ອງພິມເມື່ອໃດ ກໍເຊື່ອມຕໍ່ເອງເລີຍ
    if ($S.name -and -not (Is-On) -and ([DateTime]::Now - $S.lastTry).TotalMilliseconds -ge $RetryMs) {
      Connect-Printer $S.name | Out-Null
      continue
    }
    Start-Sleep -Milliseconds 40
  }
} finally {
  Drop-Conn
  try { $listener.Stop() } catch {}
}
