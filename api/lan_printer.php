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
    // Small chunks with a short pause between them, not one big burst. A receipt image can be
    // tens of KB, and plenty of cheap ESC/POS network/WiFi printers have a receive buffer far
    // smaller than that with no real TCP backpressure — fwrite() happily reports success while
    // the printer itself silently drops whatever didn't fit. The result: the ticket cuts off
    // partway through, and the leftover raster bytes it never consumed as an image get read as
    // plain text on its next job, printing as garbled (often Chinese-codepage) characters. A
    // 1 KB chunk every ~15 ms keeps arrival well under what even a small on-board buffer drains
    // at typical print speed.
    $chunkSize  = 1024;
    $chunkDelay = 15000; // microseconds
    while ($sent < $len) {
        $written = @fwrite($fp, substr($bytes, $sent, $chunkSize));
        if ($written === false || $written === 0) {
            fclose($fp);
            return [false, 'write failed after ' . $sent . ' bytes'];
        }
        $sent += $written;
        if ($sent < $len) usleep($chunkDelay);
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

    // A flat "black if darker than mid-gray" threshold drops anything the logo used lighter
    // colors or soft/anti-aliased edges for — those pixels just vanish, so the printed logo
    // came out visibly incomplete instead of the full image. Floyd–Steinberg error-diffusion
    // dithering renders those tones as a dot pattern instead, so the whole logo (and any
    // photo/gradient content) prints recognizably; pure black text is unaffected since its
    // luminance is already 0 or 255 and dithers to the same crisp pixels as before.
    $lum = [];
    for ($y = 0; $y < $h; $y++) {
        $row = [];
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($src, $x, $y);
            $alpha = ($rgb >> 24) & 0x7F; // GD alpha: 0 = opaque, 127 = fully transparent
            $opacity = 1 - ($alpha / 127);
            $pxLum = (((($rgb >> 16) & 0xFF) * 299) + ((($rgb >> 8) & 0xFF) * 587) + (($rgb & 0xFF) * 114)) / 1000;
            // Blend transparent pixels toward white (the paper) instead of an abrupt cutoff,
            // so a logo's soft/anti-aliased edges dither smoothly rather than disappearing.
            $row[] = ($pxLum * $opacity) + (255 * (1 - $opacity));
        }
        $lum[] = $row;
    }
    imagedestroy($src);

    $bitmap = [];
    for ($y = 0; $y < $h; $y++) {
        $bitmap[] = array_fill(0, $w, false);
        for ($x = 0; $x < $w; $x++) {
            $old = $lum[$y][$x];
            $black = $old < 128;
            $bitmap[$y][$x] = $black;
            $err = $old - ($black ? 0 : 255);
            if ($x + 1 < $w) $lum[$y][$x + 1] += $err * 7 / 16;
            if ($y + 1 < $h) {
                if ($x > 0) $lum[$y + 1][$x - 1] += $err * 3 / 16;
                $lum[$y + 1][$x] += $err * 5 / 16;
                if ($x + 1 < $w) $lum[$y + 1][$x + 1] += $err * 1 / 16;
            }
        }
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
                if ($bitmap[$y][$x]) {
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

    // GS V already feeds the paper to the cutter's own blade position before cutting — an
    // extra explicit "feed 4 lines" stacked in front of that (the old behaviour) left a long
    // blank gap between the last printed line (the thank-you footer) and the cut. Cut right
    // where the printer's own feed-to-cut already lands.
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
        . $GS . 'V' . chr(66) . chr(0);
}
