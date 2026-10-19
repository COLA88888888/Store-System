<?php
/**
 * ສ່ວນ: AJAX: ພິມບິນຍ່ອຍ LAN
 * ຮັບຮູບ PNG ຂອງບິນຍ່ອຍຈາກໜ້າຂາຍ ແລ້ວສົ່ງໄປເຄື່ອງພິມເຄືອຂ່າຍຂອງໝວດນັ້ນ (ຫຼື ເຂົ້າຄິວ print
 * agent).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ api/pos_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// LAN kitchen ticket: the POS draws the ticket as a PNG (the browser shapes the Lao text,
// which printer fonts can't) and this relays it to the category's network printer.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'print_lan_ticket') {
    header('Content-Type: application/json');
    // Wrapped end-to-end: any Throwable anywhere in this block (including one the inner
    // try/catches below don't happen to cover) still comes back as JSON with file:line instead
    // of a bare 500 — this block runs on hosts we don't have shell/log access to, so the
    // response body has to be the diagnostic, not the server's own error log.
    try {
    require_once dirname(__DIR__) . '/lan_printer.php';
    // Don't hold the session lock while waiting on the printer socket.
    session_write_close();

    $printerId = intval($_POST['printer_id'] ?? 0);
    $image     = (string)($_POST['image'] ?? '');
    $pngPrefix = 'data:image/png;base64,';
    if ($printerId <= 0 || strpos($image, $pngPrefix) !== 0 || strlen($image) > 4000000) {
        echo json_encode(['success' => false, 'message' => t('pos.lan_print_bad_request', 'ຂໍ້ມູນການພິມບໍ່ຖືກຕ້ອງ')], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $prStmt = $pdo->prepare("SELECT * FROM st_tbl_printer WHERE id = ? AND status = 1 LIMIT 1");
    $prStmt->execute([$printerId]);
    $printer = $prStmt->fetch();
    $isUsb = $printer && ($printer['type'] ?? '') === 'usb';
    if (!$printer || (($printer['type'] ?? '') !== 'ip' && !$isUsb)) {
        echo json_encode(['success' => false, 'message' => t('pos.lan_print_not_lan', 'ເຄື່ອງພິມນີ້ປິດໃຊ້ງານ ຫຼື ບໍ່ໄດ້ຕັ້ງເປັນ Network LAN')], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $addr = $isUsb ? null : lanPrinterAddress($printer['ip_address'] ?? '');
    if (!$isUsb && $addr === null) {
        echo json_encode(['success' => false, 'message' => t('pos.lan_print_bad_ip', 'IP Address ຂອງເຄື່ອງພິມບໍ່ຖືກຕ້ອງ')], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // GD renders the PNG-to-ESC/POS raster below — on some shared hosting it's disabled, which
    // would otherwise fatal into a blank 500 (no JSON) instead of a message the cashier can see.
    if (!extension_loaded('gd')) {
        echo json_encode(['success' => false, 'message' => t('pos.lan_print_no_gd', 'ເຊີບເວີບໍ່ໄດ້ເປີດໃຊ້ GD extension — ພິມຜ່ານ LAN ບໍ່ໄດ້')], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $png = base64_decode(substr($image, strlen($pngPrefix)), true);
    try {
        $bytes = ($png !== false) ? escposRasterFromPng($png, lanPrinterDots($printer['paper_width'] ?? 80)) : null;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit();
    }
    if ($bytes === null) {
        echo json_encode(['success' => false, 'message' => t('pos.lan_print_bad_request', 'ຂໍ້ມູນການພິມບໍ່ຖືກຕ້ອງ')], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // USB printer on this PC: the very same bytes go to the Windows spooler as RAW data, so the
    // bill is identical to a LAN one (api/usb_printer.php). Always direct — never the agent queue.
    if ($isUsb) {
        require_once dirname(__DIR__) . '/usb_printer.php';
        $usbName = usbPrinterName($printer['ip_address'] ?? '');
        [$ok, $err] = $usbName === null ? [false, t('pos.usb_print_no_name', 'ບໍ່ໄດ້ໃສ່ຊື່ເຄື່ອງພິມ USB')] : usbPrinterSend($usbName, $bytes);
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? '' : ($printer['name'] . ' (USB: ' . ($usbName ?? '') . ') — ' . $err),
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Off-site server (Settings > Printers > print_mode = agent): this box can't open a socket
    // to the shop's 192.168.x.x printer itself, so the finished ESC/POS bytes are parked in
    // print_jobs for tools/print-agent.php — which DOES sit on that LAN — to collect and send.
    if (getSetting($pdo, 'print_mode', 'direct') === 'agent') {
        try {
            $pdo->prepare("INSERT INTO st_print_jobs (printer_id, store_id, payload) VALUES (?, ?, ?)")
                ->execute([$printerId, getPosStoreId(), base64_encode($bytes)]);
            echo json_encode(['success' => true, 'queued' => true], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

    [$ok, $err] = lanPrinterSend($addr[0], $addr[1], $bytes);
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? '' : ($printer['name'] . ' (' . $addr[0] . ':' . $addr[1] . ') — ' . $err),
    ], JSON_UNESCAPED_UNICODE);
    exit();
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine(),
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

