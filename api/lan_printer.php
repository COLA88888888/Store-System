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

// Write $bytes to an open printer socket at LAN_PRINT_BYTES_PER_SEC after an initial
// LAN_PRINT_HEAD_START burst. Shared by lanPrinterSend(); tools/print-agent.php has a copy.
// 72 KB/s was below an 80 mm printer's raster speed, so it ran dry and printed the bill in
// stop-start jerks; 190 KB/s overran some printers' receive buffer and the bill came out cut
// short. 120 KB/s + an 8 KB head start sits between the two: fast enough to print in one
// continuous run. If a printer starts cutting bills short, lower this again (e.g. 100000).
if (!defined('LAN_PRINT_BYTES_PER_SEC')) define('LAN_PRINT_BYTES_PER_SEC', 120000);
if (!defined('LAN_PRINT_HEAD_START'))    define('LAN_PRINT_HEAD_START', 8192);
function lanPrinterWritePaced($fp, $bytes) {
    $len = strlen($bytes);
    $sent = 0;
    $chunk = 2048;
    $start = microtime(true);
    while ($sent < $len) {
        $allowed = LAN_PRINT_HEAD_START + (int)((microtime(true) - $start) * LAN_PRINT_BYTES_PER_SEC);
        if ($sent >= $allowed) {
            usleep(2000);
            continue;
        }
        $n = min($chunk, $len - $sent, $allowed - $sent);
        $written = @fwrite($fp, substr($bytes, $sent, $n));
        if ($written === false || $written === 0) {
            return [false, 'write failed after ' . $sent . ' bytes'];
        }
        $sent += $written;
    }
    return [true, ''];
}

function lanPrinterSend($host, $port, $bytes, $timeout = 4) {
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, (int)$port, $errno, $errstr, $timeout);
    if (!$fp) {
        // ຖ້າໃສ່ IP ຂອງຄອມເຄື່ອງນີ້ (127.0.0.1, localhost, ຫຼື 192.168.1.11) ທີ່ບໍ່ໄດ້ສຽບສາຍແລນ ແຕ່ຕໍ່ຜ່ານ USB
        $localIps = ['127.0.0.1', 'localhost', '::1'];
        $hostLower = strtolower(trim($host));
        $myIp = @getHostByName(@getHostName());
        if ($myIp) $localIps[] = strtolower($myIp);
        $localIps[] = '192.168.1.11';

        if (in_array($hostLower, $localIps, true)) {
            require_once __DIR__ . '/usb_printer.php';
            global $pdo;
            $usbName = 'XP-80C';
            if (isset($pdo)) {
                try {
                    $st = $pdo->query("SELECT ip_address FROM st_tbl_printer WHERE type = 'usb' AND status = 1 ORDER BY id DESC LIMIT 1");
                    $r = $st ? $st->fetch(PDO::FETCH_ASSOC) : null;
                    if ($r && !empty($r['ip_address'])) $usbName = trim($r['ip_address']);
                } catch (Throwable $e) {}
            }
            return usbPrinterSend($usbName, $bytes);
        }
        return [false, $errstr !== '' ? $errstr : ('connection failed (' . $errno . ')')];
    }
    stream_set_timeout($fp, $timeout);
    // Pace the bytes to the printer's own speed. Too slow and the printer runs dry mid-ticket
    // and prints in stop-start jerks (the old 1 KB / 15 ms ≈ 68 KB/s was well under the
    // ~140–170 KB/s an 80 mm printer eats at full speed). One big burst is no good either: plenty
    // of cheap ESC/POS network/WiFi printers have a small receive buffer and no real TCP
    // backpressure — fwrite() reports success while the printer silently drops the overflow,
    // cutting the ticket short and printing the leftover raster as garbled text on the next job.
    // So: a small head start to get the paper moving at once, then a steady clock-based rate that
    // the printer can absorb (usleep granularity differs by OS, so time is measured, not
    // assumed from the sleep length).
    [$ok, $err] = lanPrinterWritePaced($fp, $bytes);
    if (!$ok) {
        fclose($fp);
        return [false, $err];
    }
    fclose($fp);
    return [true, ''];
}

// ບິນ LAN: ຍັບຂວາ 7 dots (~0.9 mm) ໃຫ້ຂອບເຈ້ຍສອງຂ້າງເທົ່າກັນ. ທົດລອງກັບເຄື່ອງແລ້ວ:
//   6 = ຊິດຊ້າຍ ~0.35 mm (ວັດຈາກຮູບ: ຊ້າຍ ~5.4 mm, ຂວາ ~6.1 mm); 8, 9 = ຊິດຂວາເລັກນ້ອຍ.
//   ບວກ = ຂວາ, ລົບ = ຊ້າຍ (8 dots = 1 mm; 1 dot = 0.125 mm ເປັນໜ່ວຍນ້ອຍສຸດ).
//   USB ມີຄ່າຂອງມັນເອງ: USB_PRINT_SHIFT_MM ໃນ assets/js/lan-receipt.js.
if (!defined('LAN_PRINT_SHIFT_DOTS')) define('LAN_PRINT_SHIFT_DOTS', 7);
// ບິນ USB (ສົ່ງ ESC/POS ກົງເຂົ້າ Windows spooler): ບິນ browser ເປັນຫຼັກ — ບິນ browser ພິມອອກເຄື່ອງ USB ດຽວກັນ
// ຖືກຍັບຊ້າຍ USB_PRINT_SHIFT_MM = -0.15 mm (assets/js/lan-receipt.js) ≈ -1 ຈຸດ. ເມື່ອກ່ອນ USB ໃຊ້ຄ່າຂອງ LAN (+7)
// ຈຶ່ງອອກມາເຫຼື່ອມຂວາ ~1 mm (≈ 1 ຂີດ) ທຽບກັບບິນ browser. ທົດລອງພິມແທ້ (31/10/2026): -1 ຍັງເຫຼື່ອມຂວາອີກ 1 ຂີດ
// → ຍັບຊ້າຍເພີ່ມ 1 ຂີດ (≈ 10 ຈຸດ) = -11. ຫົວພິມ USB ຈຶ່ງເລີ່ມຫ່າງຈາກຂອບຊ້າຍຫຼາຍກວ່າໜ້າຕ່າງພິມຂອງ browser.
// ປ່ຽນຄ່ານີ້ ຖ້າເຄື່ອງ USB ເຄື່ອງອື່ນຫົວພິມເຫຼື່ອມ (ບວກ = ຂວາ, ລົບ = ຊ້າຍ; 8 ຈຸດ = 1 mm).
if (!defined('USB_PRINT_SHIFT_DOTS')) define('USB_PRINT_SHIFT_DOTS', -11);
function escposRasterFromPng($png, $dots, $shiftDots = LAN_PRINT_SHIFT_DOTS) {
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

    // LAN-only horizontal nudge (LAN_PRINT_SHIFT_DOTS, now 7 ≈ 0.9 mm right) on top of the centring
    // done for both paths by PRINT_SHIFT_DOTS in assets/js/lan-receipt.js — this printer's head
    // sits off-centre over the network, so the bill still had more blank paper on the right. Was: the bill image is tuned for USB
    // (browser dialog); the LAN printer's head sits differently, so the same image came out too
    // far left. Shift it right here, which the USB path never goes through. 8 dots = 1 mm;
    // positive = right, negative = left.
    $shift = (int)$shiftDots;
    // ບໍ່ຍັບເກີນຂອບເຈ້ຍຫວ່າງ — ໜັງສືຢູ່ຂອບຈະບໍ່ຖືກຕັດຫາຍ
    if ($shift !== 0) {
        $minX = $w; $maxX = -1;
        foreach ($bitmap as $row) {
            $first = array_search(true, $row, true);
            if ($first === false) continue;
            if ($first < $minX) $minX = $first;
            $last = $w - 1 - array_search(true, array_reverse($row), true);
            if ($last > $maxX) $maxX = $last;
        }
        if ($maxX >= 0) {
            $shift = $shift > 0 ? min($shift, $w - 1 - $maxX) : -min(-$shift, $minX);
        }
    }
    if ($shift !== 0) {
        $blank = array_fill(0, abs($shift), false);
        foreach ($bitmap as $y => $row) {
            $bitmap[$y] = $shift > 0
                ? array_merge($blank, array_slice($row, 0, $w - $shift))
                : array_merge(array_slice($row, -$shift), $blank);
        }
    }

    // Trim blank paper after the last printed line (the thank-you footer): the rendered image
    // carries bottom padding / empty rows, and every blank row is fed out before the cut. Keep
    // a small margin (~2 mm) under the last dot so the cut doesn't graze the text.
    $lastInk = -1;
    for ($y = $h - 1; $y >= 0; $y--) {
        if (in_array(true, $bitmap[$y], true)) { $lastInk = $y; break; }
    }
    if ($lastInk >= 0) {
        $h = min($h, $lastInk + 1 + 16);
        $bitmap = array_slice($bitmap, 0, $h);
    }

    $packed = [];
    for ($y = 0; $y < $h; $y++) {
        $data = '';
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
        $packed[] = $data;
    }
    return escposFromRows($packed, intdiv($w + 7, 8));
}

// ESC/POS bytes from packed raster rows (1 bit per dot, MSB = leftmost, 1 = black).
function escposFromRows(array $packed, $rowBytes) {
    $ESC = chr(27);
    $GS = chr(29);
    $h = count($packed);
    $out = $ESC . '@';

    // Send the image in small bands (48 rows ≈ 3.5 KB at 80 mm): a printer prints a GS v 0 block
    // once it has received it whole, so small bands keep the paper moving continuously instead of
    // pausing while each big block arrives — and each still fits a small receive buffer.
    $band = 48;
    for ($y0 = 0; $y0 < $h; $y0 += $band) {
        $rows = min($band, $h - $y0);
        $out .= $GS . 'v0' . chr(0)
            . chr($rowBytes & 0xFF) . chr(($rowBytes >> 8) & 0xFF)
            . chr($rows & 0xFF) . chr(($rows >> 8) & 0xFF)
            . implode('', array_slice($packed, $y0, $rows));
    }

    // GS V already feeds the paper to the cutter's own blade position before cutting — an
    // extra explicit "feed 4 lines" stacked in front of that (the old behaviour) left a long
    // blank gap between the last printed line (the thank-you footer) and the cut. Cut right
    // where the printer's own feed-to-cut already lands.
    $out .= $GS . 'V' . chr(66) . chr(0); // feed to cutter and cut
    return $out;
}

// ທາງດ່ວນສຳລັບໃບບິນທີ່ເປັນດຳ-ຂາວລ້ວນແລ້ວ (assets/js/lan-receipt.js ຕັດເປັນດຳ-ຂາວໃນ inkCanvas ກ່ອນສົ່ງ):
// GD ອັດຮູບເປັນ 1 bit ໃຫ້ດ້ວຍ imagewbmp() ໃນ C ແທນການວົນທີລະ pixel ໃນ PHP (~1.5 s → ~0.05 s).
// ຜົນອອກຄືກັບ escposRasterFromPng() ທຸກ byte — dithering ຂອງຮູບດຳ-ຂາວລ້ວນ ກໍຄື threshold ທຳມະດາ.
// ຄືນ null ຖ້າຮູບບໍ່ກວ້າງເທົ່າຫົວພິມ (ຕ້ອງຫຍໍ້ = ມີສີເທົາ) → ຜູ້ເອີ້ນໃຊ້ escposRasterFromPng() ແທນ.
function escposRasterFromBwPng($png, $dots, $shiftDots = LAN_PRINT_SHIFT_DOTS) {
    if (!function_exists('imagewbmp')) {
        return null;
    }
    $src = @imagecreatefromstring($png);
    if (!$src) {
        return null;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    if ($w !== $dots || $w % 8 !== 0 || $h < 1 || $h > 20000) {
        imagedestroy($src);
        return null;
    }
    if (!imageistruecolor($src)) {
        imagepalettetotruecolor($src);
    }
    $rowBytes = intdiv($w + 7, 8);
    $packed = gdPackedRows($src, $w, $h);
    if ($packed === null) {
        imagedestroy($src);
        return null;
    }

    // ຂອບໝຶກຊ້າຍ-ຂວາ ແລະ ແຖວສຸດທ້າຍທີ່ມີໝຶກ
    $blank = str_repeat("\0", $rowBytes);
    $minX = $w; $maxX = -1; $lastInk = -1;
    foreach ($packed as $y => $row) {
        if ($row === $blank) continue;
        $lastInk = $y;
        $i = strspn($row, "\0");
        $x = $i * 8 + 7 - (int)floor(log(ord($row[$i]), 2));
        if ($x < $minX) $minX = $x;
        $j = strlen(rtrim($row, "\0")) - 1;
        $b = ord($row[$j]);
        $x = $j * 8 + 7 - (int)log($b & -$b, 2);
        if ($x > $maxX) $maxX = $x;
    }

    // ຍັບ $shiftDots (LAN: LAN_PRINT_SHIFT_DOTS, USB: USB_PRINT_SHIFT_DOTS) ຄືກັບ escposRasterFromPng() (ບໍ່ຍັບເກີນຂອບເຈ້ຍຫວ່າງ);
    // ໃຫ້ GD ຍັບຮູບ (imagecopy) ແລ້ວອັດໃໝ່ — ໄວກວ່າຍັບທີລະ bit ໃນ PHP
    $shift = (int)$shiftDots;
    if ($shift !== 0 && $maxX >= 0) {
        $shift = $shift > 0 ? min($shift, $w - 1 - $maxX) : -min(-$shift, $minX);
        if ($shift !== 0) {
            $dst = imagecreatetruecolor($w, $h);
            imagefilledrectangle($dst, 0, 0, $w - 1, $h - 1, imagecolorallocate($dst, 255, 255, 255));
            imagecopy($dst, $src, max(0, $shift), 0, max(0, -$shift), 0, $w - abs($shift), $h);
            $packed = gdPackedRows($dst, $w, $h);
            imagedestroy($dst);
            if ($packed === null) {
                imagedestroy($src);
                return null;
            }
        }
    }
    imagedestroy($src);

    // ຕັດເຈ້ຍເປົ່າທາງລຸ່ມ ເຫຼືອ ~2 mm ໃຕ້ແຖວສຸດທ້າຍ
    if ($lastInk >= 0) {
        $packed = array_slice($packed, 0, min($h, $lastInk + 1 + 16));
    }
    return escposFromRows($packed, $rowBytes);
}

// ແຖວ raster ທີ່ອັດແລ້ວ (1 = ດຳ, bit ຊ້າຍສຸດກ່ອນ) ຈາກຮູບ GD ຜ່ານ imagewbmp():
// WBMP ໃຫ້ pixel ທີ່ເປັນສີດຳ (0x000000) ພໍດີ = 0, ນອກນັ້ນ = 1 → ກັບດ້ວຍ ~ ໃຫ້ເປັນແບບ ESC/POS.
function gdPackedRows($im, $w, $h) {
    ob_start();
    imagewbmp($im, null, 0);
    $wbmp = ob_get_clean();
    $p = 2; // type 0 + fix header
    $dims = [];
    for ($k = 0; $k < 2; $k++) {
        $v = 0;
        do {
            $b = ord($wbmp[$p++]);
            $v = ($v << 7) | ($b & 0x7F);
        } while ($b & 0x80);
        $dims[] = $v;
    }
    $rowBytes = intdiv($w + 7, 8);
    if ($dims[0] !== $w || $dims[1] !== $h || strlen($wbmp) - $p < $rowBytes * $h) {
        return null;
    }
    return str_split(~substr($wbmp, $p, $rowBytes * $h), $rowBytes);
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
