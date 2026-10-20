param(
  [Parameter(Mandatory = $false)][string]$BinFile = '',
  [Parameter(Mandatory = $true)][string]$CacheFile,
  [Parameter(Mandatory = $true)][string]$PrinterName,
  [switch]$WarmOnly
)
$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Runtime.WindowsRuntime
$asTaskGeneric = ([System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
  $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1'
})[0]

function Await($WinRtTask, $ResultType) {
  $asTask = $asTaskGeneric.MakeGenericMethod($ResultType)
  $netTask = $asTask.Invoke($null, @($WinRtTask))
  if (-not $netTask.Wait(10000)) { throw 'ERR_TIMEOUT' }
  return ,$netTask.Result
}

[Windows.Devices.Enumeration.DeviceInformation, Windows.Devices.Enumeration, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.BluetoothLEDevice, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.BluetoothCacheMode, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceService, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristic, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattCommunicationStatus, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Devices.Bluetooth.GenericAttributeProfile.GattWriteOption, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
[Windows.Storage.Streams.DataWriter, Windows.Storage.Streams, ContentType=WindowsRuntime] | Out-Null

$cache = @{}
if (Test-Path $CacheFile) {
  try {
    $raw = Get-Content -Path $CacheFile -Raw -Encoding UTF8 | ConvertFrom-Json
    foreach ($p in $raw.PSObject.Properties) { $cache[$p.Name] = $p.Value }
  } catch {}
}

$entry = $cache[$PrinterName]
if (-not $entry -or -not $entry.id) {
  $selector = 'System.Devices.Aep.ProtocolId:="{bb7bb05e-5972-42b5-94fc-76eaa7084d49}" AND System.ItemNameDisplay:="' + $PrinterName.Replace('"', '') + '"'
  $devs = Await ([Windows.Devices.Enumeration.DeviceInformation]::FindAllAsync($selector, $null, [Windows.Devices.Enumeration.DeviceInformationKind]::AssociationEndpoint)) ([Windows.Devices.Enumeration.DeviceInformationCollection])
  $found = $devs | Select-Object -First 1
  if (-not $found) { throw 'ERR_NOT_FOUND' }
  $entry = [PSCustomObject]@{ id = $found.Id; service = ''; char = '' }
}

$ble = $null
$svc = $null
try {
  $ble = Await ([Windows.Devices.Bluetooth.BluetoothLEDevice]::FromIdAsync($entry.id)) ([Windows.Devices.Bluetooth.BluetoothLEDevice])
  if (-not $ble) { throw 'ERR_CONNECT_FAILED' }

  $ch = $null
  $uncached = [Windows.Devices.Bluetooth.BluetoothCacheMode]::Uncached
  $cached = [Windows.Devices.Bluetooth.BluetoothCacheMode]::Cached
  if ($entry.service -and $entry.char) {
    for ($try = 0; $try -lt 4 -and -not $ch; $try++) {
      $mode = if ($try -eq 0) { $cached } else { $uncached }
      if ($try -gt 0) {
        if ($svc) { try { $svc.Dispose() } catch {}; $svc = $null }
        if ($ble) { try { $ble.Dispose() } catch {}; $ble = $null }
        Start-Sleep -Milliseconds 250
        $ble = Await ([Windows.Devices.Bluetooth.BluetoothLEDevice]::FromIdAsync($entry.id)) ([Windows.Devices.Bluetooth.BluetoothLEDevice])
      }
      $srvs = Await ($ble.GetGattServicesForUuidAsync([Guid]$entry.service, $mode)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceServicesResult])
      if ($srvs.Status -eq 'Success' -and $srvs.Services.Count -gt 0) {
        $svc = $srvs.Services | Select-Object -First 1
        $chs = Await ($svc.GetCharacteristicsForUuidAsync([Guid]$entry.char, $mode)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristicsResult])
        if ($chs.Status -eq 'Success' -and $chs.Characteristics.Count -gt 0) {
          $ch = $chs.Characteristics | Select-Object -First 1
        }
      }
    }
  }

  if (-not $ch) {
    $pref = @('000018f0-0000-1000-8000-00805f9b34fb', '49535343-fe7d-4ae5-8fa9-9fafd205e455', '0000ff00-0000-1000-8000-00805f9b34fb', 'e7810a71-73ae-499d-8c15-faa9aef0c3f2')
    $srvs = Await ($ble.GetGattServicesAsync($uncached)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattDeviceServicesResult])
    if ($srvs.Status -ne 'Success') { throw 'ERR_OFFLINE' }
    foreach ($u in $pref) {
      $s = $srvs.Services | Where-Object { $_.Uuid.ToString().ToLower() -eq $u } | Select-Object -First 1
      if ($s) {
        $chs = Await ($s.GetCharacteristicsAsync($uncached)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristicsResult])
        foreach ($c in $chs.Characteristics) {
          if ([int]$c.CharacteristicProperties -band 12) {
            $svc = $s; $ch = $c
            $entry = [PSCustomObject]@{ id = $entry.id; service = $s.Uuid.ToString(); char = $c.Uuid.ToString() }
            $cache[$PrinterName] = $entry
            try { $cache | ConvertTo-Json -Depth 4 | Set-Content -Path $CacheFile -Encoding UTF8 } catch {}
            break
          }
        }
      }
      if ($ch) { break }
    }
  }

  if (-not $ch) { throw 'ERR_NO_CHAR' }
  if ($WarmOnly -or -not $BinFile -or -not (Test-Path $BinFile)) {
    Write-Output 'OK'
    return
  }

  $wm = ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCharacteristic].GetMethods() | Where-Object {
    $_.Name -eq 'WriteValueAsync' -and $_.GetParameters().Count -eq 2
  })[0]
  $writeOpt = if ([int]$ch.CharacteristicProperties -band 4) {
    [Windows.Devices.Bluetooth.GenericAttributeProfile.GattWriteOption]::WriteWithoutResponse
  } else {
    [Windows.Devices.Bluetooth.GenericAttributeProfile.GattWriteOption]::WriteWithResponse
  }

  [Windows.Devices.Bluetooth.GenericAttributeProfile.GattSession, Windows.Devices.Bluetooth, ContentType=WindowsRuntime] | Out-Null
  $sess = $null
  try {
    $sess = Await ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattSession]::FromDeviceIdAsync($ble.BluetoothDeviceId)) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattSession])
    if ($sess) { $sess.MaintainConnection = $true }
  } catch {}

  $allBytes = [System.IO.File]::ReadAllBytes($BinFile)
  $sw = [System.Diagnostics.Stopwatch]::StartNew()
  $off = 0
  $sent = 0
  $burst = 3600.0
  $rate = 18000.0
  $chunkSize = 244

  while ($off -lt $allBytes.Length) {
    $len = [Math]::Min($chunkSize, $allBytes.Length - $off)
    $part = New-Object byte[] $len
    [Array]::Copy($allBytes, $off, $part, 0, $len)
    $dw = New-Object Windows.Storage.Streams.DataWriter
    $dw.WriteBytes($part)
    $buf = $dw.DetachBuffer()
    $task = $wm.Invoke($ch, @($buf, $writeOpt))
    $st = Await $task ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCommunicationStatus])
    if ($st -ne 'Success') {
      Start-Sleep -Milliseconds 30
      $dw2 = New-Object Windows.Storage.Streams.DataWriter
      $dw2.WriteBytes($part)
      $buf2 = $dw2.DetachBuffer()
      $st = Await ($wm.Invoke($ch, @($buf2, $writeOpt))) ([Windows.Devices.Bluetooth.GenericAttributeProfile.GattCommunicationStatus])
      if ($st -ne 'Success') { throw "ERR_WRITE_$st" }
    }
    $off += $len
    $sent += $len
    if ($sent -gt $burst) {
      $ahead = (($sent - $burst) * 1000.0 / $rate) - $sw.Elapsed.TotalMilliseconds
      if ($ahead -gt 3) { Start-Sleep -Milliseconds ([int]$ahead) }
    }
  }

  Start-Sleep -Milliseconds 40
  Write-Output 'OK'
} finally {
  if ($sess) { try { $sess.Dispose() } catch {} }
  if ($svc) { try { $svc.Dispose() } catch {} }
  if ($ble) { try { $ble.Dispose() } catch {} }
}
