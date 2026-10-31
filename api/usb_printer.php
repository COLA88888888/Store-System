<?php
// Direct USB receipt printing. A printer of type 'usb' in tbl_printer stores its Windows printer
// name (as shown in Settings > Printers & scanners) in ip_address. The bill is rastered exactly
// like a Network LAN ticket (escposRasterFromPng in api/lan_printer.php) and the same ESC/POS
// bytes are handed to the Windows spooler as RAW data by tools/raw-print.cs (compiled once to an
// .exe, see usbRawPrintExe(); tools/raw-print.ps1 is the slower fallback) — no browser print
// dialog, no driver scaling or margins — so a USB bill is identical to a LAN bill.
//
// Only works when MiniPos runs on the PC the USB printer is plugged into (Laragon/XAMPP in the
// shop). Returns [ok, error] like lanPrinterSend().

function usbPrinterName($raw) {
    $name = trim((string)$raw);
    // A printer name can hold spaces and brackets ("XP-80C (copy 3)") but never quotes
    if ($name === '' || strlen($name) > 200 || preg_match('/["\x00-\x1f]/', $name)) return null;
    return $name;
}

// tools/raw-print.cs ຄອມໄພລ໌ເປັນ .exe ຄັ້ງດຽວ (csc.exe ຂອງ .NET Framework 4 ທີ່ມີໃນ Windows) ເກັບໄວ້ໃນ temp.
// ເປີດ .exe ໃຊ້ ~0.05 s ແທນ PowerShell ~2 s ຕໍ່ບິນ. ຄອມໄພລ໌ບໍ່ໄດ້ → null → ໃຊ້ raw-print.ps1 ຄືເກົ່າ.
// ແກ້ raw-print.cs ແລ້ວ .exe ຈະຖືກສ້າງໃໝ່ເອງ (ຊື່ໄຟລ໌ມີ filemtime ຂອງ .cs).
function usbRawPrintExe() {
    $cs = realpath(__DIR__ . '/../tools/raw-print.cs');
    if ($cs === false) return null;
    $exe = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'MiniPosRawPrint_' . filemtime($cs) . '.exe';
    if (is_file($exe)) return $exe;
    $win = getenv('WINDIR') ?: 'C:\\Windows';
    foreach (['Framework64', 'Framework'] as $fw) {
        $csc = $win . '\\Microsoft.NET\\' . $fw . '\\v4.0.30319\\csc.exe';
        if (!is_file($csc)) continue;
        // ຄອມໄພລ໌ໃສ່ຊື່ຊົ່ວຄາວກ່ອນ ແລ້ວຄ່ອຍປ່ຽນຊື່ — ສອງບິນພ້ອມກັນຈະບໍ່ໄດ້ .exe ທີ່ຂຽນບໍ່ແລ້ວ
        $part = $exe . '.' . getmypid() . '.part';
        $out = [];
        $code = 1;
        exec('"' . $csc . '" /nologo /optimize /target:exe /out:"' . $part . '" "' . $cs . '" 2>&1', $out, $code);
        if ($code === 0 && is_file($part) && @rename($part, $exe)) return $exe;
        @unlink($part);
        if (is_file($exe)) return $exe;
    }
    return null;
}

function usbPrinterSend($printerName, $bytes) {
    if (stripos(PHP_OS, 'WIN') !== 0) {
        return [false, 'USB printing needs MiniPos running on Windows'];
    }
    if (!function_exists('exec')) {
        return [false, 'PHP exec() is disabled'];
    }
    $tmp = tempnam(sys_get_temp_dir(), 'mpr');
    if ($tmp === false || file_put_contents($tmp, $bytes) === false) {
        return [false, 'cannot write temp file'];
    }
    $exe = usbRawPrintExe();
    if ($exe !== null) {
        $cmd = '"' . $exe . '" "' . $printerName . '" "' . $tmp . '" 2>&1';
    } else {
        $script = realpath(__DIR__ . '/../tools/raw-print.ps1');
        $cmd = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' . $script . '"'
             . ' -Printer "' . $printerName . '" -File "' . $tmp . '" 2>&1';
    }
    $out = [];
    $code = 1;
    exec($cmd, $out, $code);
    @unlink($tmp);
    if ($code !== 0) {
        $msg = trim(implode(' ', $out));
        return [false, $msg !== '' ? $msg : ('exit code ' . $code)];
    }
    return [true, ''];
}
