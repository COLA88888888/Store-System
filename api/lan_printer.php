<?php
// Raw TCP ("JetDirect", port 9100) printing to ESC/POS network printers on the shop LAN.
// Works with no internet: the MiniPos server talks straight to the printer's IP.
//
// Printer fonts can't render Lao, so tickets arrive as a PNG drawn by the browser (which does
// the Lao text shaping) and go out as a GS v 0 raster image.

function lanPrinterAddress($raw) {
    $raw = trim((string)$raw);
    $port = 9100;
    if (preg_match('/^([0-9a-zA-Z.-]+):([0-9]{1,5})$/', $raw, $m)) {
        $raw = $m[1];
        $port = (int)$m[2];
    }
    if ($raw === '' || !preg_match('/^[0-9a-zA-Z.-]+$/', $raw) || $port < 1 || $port > 65535) {
        return null;
    }
    return [$raw, $port];
}

// Printable width in dots at 203 dpi: 80 mm paper = 576, 58 mm paper = 384.
function lanPrinterDots($paperWidth) {
    return intval($paperWidth) === 58 ? 384 : 576;
}

function lanPrinterSend($host, $port, $bytes, $timeout = 4) {
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, (int)$port, $errno, $errstr, $timeout);
    if (!$fp) {
        return [false, $errstr !== '' ? $errstr : ('connection failed (' . $errno . ')')];
    }
    stream_set_timeout($fp, $timeout);
    $len = strlen($bytes);
    $sent = 0;
    while ($sent < $len) {
        $written = @fwrite($fp, substr($bytes, $sent, 8192));
        if ($written === false || $written === 0) {
            fclose($fp);
            return [false, 'write failed after ' . $sent . ' bytes'];
        }
        $sent += $written;
    }
    fclose($fp);
    return [true, ''];
}

function escposRasterFromPng($png, $dots) {
    $src = @imagecreatefromstring($png);
    if (!$src) {
        return null;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    if ($w < 1 || $h < 1 || $h > 20000) {
        imagedestroy($src);
        return null;
    }
    if ($w !== $dots) {
        $nh = max(1, (int)round($h * $dots / $w));
        $dst = imagecreatetruecolor($dots, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dots, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
        $w = $dots;
        $h = $nh;
    }

    $ESC = chr(27);
    $GS = chr(29);
    $rowBytes = intdiv($w + 7, 8);
    $out = $ESC . '@';

    // Send the image in bands so each GS v 0 block fits in small printer buffers.
    $band = 200;
    for ($y0 = 0; $y0 < $h; $y0 += $band) {
        $rows = min($band, $h - $y0);
        $data = '';
        for ($y = $y0; $y < $y0 + $rows; $y++) {
            $byte = 0;
            $bit = 0;
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;
                $lum = (((($rgb >> 16) & 0xFF) * 299) + ((($rgb >> 8) & 0xFF) * 587) + (($rgb & 0xFF) * 114)) / 1000;
                if ($alpha < 64 && $lum < 128) {
                    $byte |= (0x80 >> $bit);
                }
                $bit++;
                if ($bit === 8) {
                    $data .= chr($byte);
                    $byte = 0;
                    $bit = 0;
                }
            }
            if ($bit > 0) {
                $data .= chr($byte);
            }
        }
        $out .= $GS . 'v0' . chr(0)
            . chr($rowBytes & 0xFF) . chr(($rowBytes >> 8) & 0xFF)
            . chr($rows & 0xFF) . chr(($rows >> 8) & 0xFF)
            . $data;
    }
    imagedestroy($src);

    $out .= $ESC . 'd' . chr(4);          // feed 4 lines
    $out .= $GS . 'V' . chr(66) . chr(0); // feed to cutter and cut
    return $out;
}

// Plain-text test page (ASCII only — printer fonts can't draw Lao, so non-ASCII becomes "?").
function escposTestPage($printerName, $host, $port) {
    $ESC = chr(27);
    $GS = chr(29);
    $LF = chr(10);
    $name = preg_replace('/[^ -~]/', '?', (string)$printerName);
    return $ESC . '@'
        . $ESC . 'a' . chr(1)
        . $GS . '!' . chr(17) . 'MiniPos' . $LF
        . $GS . '!' . chr(0) . 'LAN printer test' . $LF . $LF
        . $ESC . 'a' . chr(0)
        . 'Printer: ' . $name . $LF
        . 'Address: ' . $host . ':' . $port . $LF
        . 'Time:    ' . date('Y-m-d H:i:s') . $LF . $LF
        . 'If you can read this, LAN printing works.' . $LF
        . $ESC . 'd' . chr(4)
        . $GS . 'V' . chr(66) . chr(0);
}
