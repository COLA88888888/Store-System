<?php
// Direct USB receipt printing. A printer of type 'usb' in tbl_printer stores its Windows printer
// name (as shown in Settings > Printers & scanners) in ip_address. The bill is rastered exactly
// like a Network LAN ticket (escposRasterFromPng in api/lan_printer.php) and the same ESC/POS
// bytes are handed to the Windows spooler as RAW data by tools/raw-print.ps1 — no browser print
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
    $script = realpath(__DIR__ . '/../tools/raw-print.ps1');
    $cmd = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' . $script . '"'
         . ' -Printer "' . $printerName . '" -File "' . $tmp . '" 2>&1';
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
