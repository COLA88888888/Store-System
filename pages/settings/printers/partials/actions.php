<?php
/**
 * ສ່ວນ: ການກະທຳ POST ແລະ token ຂອງ print agent
 * ເພີ່ມ/ແກ້ໄຂ/ລົບ ເຄື່ອງພິມ, ກຳນົດເຄື່ອງພິມບິນຫຼັກ, ທົດສອບພິມ, ໂໝດການພິມ ແລະ ສ້າງ token ໃຫ້ print
 * agent.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/printers/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_printer') {
        $name = trim($_POST['name'] ?? '');
        $ip_address = trim($_POST['ip_address'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['ip', 'browser', 'usb'], true) ? $_POST['type'] : 'browser';
        $paper_width = in_array(intval($_POST['paper_width'] ?? 80), [58, 80, 148, 210], true) ? intval($_POST['paper_width']) : 80;   // 148 = A5, 210 = A4

        if ($name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO st_tbl_printer (name, ip_address, type, status, branch_id, paper_width) VALUES (?, ?, ?, 1, ?, ?)");
                $stmt->execute([$name, $ip_address, $type, $myPrinterStoreId, $paper_width]);
                $message = t('printers.msg_add_success', 'ເພີ່ມເຄື່ອງພິມໃໝ່ສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ເພີ່ມເຄື່ອງພິມ", "$name ($type)");
            } catch (Exception $e) {
                $message = sprintf(t('printers.msg_error', 'ຜິດພາດ: %s'), $e->getMessage());
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'edit_printer') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $ip_address = trim($_POST['ip_address'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['ip', 'browser', 'usb'], true) ? $_POST['type'] : 'browser';
        $paper_width = in_array(intval($_POST['paper_width'] ?? 80), [58, 80, 148, 210], true) ? intval($_POST['paper_width']) : 80;   // 148 = A5, 210 = A4

        if ($id > 0 && $name !== '') {
            try {
                $sql = "UPDATE st_tbl_printer SET name = ?, ip_address = ?, type = ?, paper_width = ? WHERE id = ? AND branch_id = ?";
                $params = [$name, $ip_address, $type, $paper_width, $id, $myPrinterStoreId];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = t('printers.msg_edit_success', 'ອັບເດດຂໍ້ມູນເຄື່ອງພິມສຳເລັດແລ້ວ!');
                $message_type = 'success';
                logActivity($pdo, "ແກ້ໄຂເຄື່ອງພິມ", "$name (ID: $id)");
            } catch (Exception $e) {
                $message = sprintf(t('printers.msg_error', 'ຜິດພາດ: %s'), $e->getMessage());
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        if ($id > 0) {
            $sql = "UPDATE st_tbl_printer SET status = ? WHERE id = ? AND branch_id = ?";
            $params = [$status, $id, $myPrinterStoreId];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = t('printers.msg_toggle_success', 'ອັບເດດສະຖານະເຄື່ອງພິມສຳເລັດ!');
            $message_type = 'success';
        }
    } elseif ($action === 'set_receipt_printer') {
        // Only one printer at a time gets the customer receipt (mirrors tbstore.is_main:
        // setting a new one unsets the old one) — scoped to THIS branch only, so setting a
        // receipt printer here never touches another branch's own receipt-printer choice.
        // Restricted to Network LAN printers — a 'browser' printer needs no designation,
        // it's already what happens by default.
        $id = intval($_POST['id'] ?? 0);
        try {
            $target = $pdo->prepare("SELECT type, status, branch_id FROM st_tbl_printer WHERE id = ?");
            $target->execute([$id]);
            $row = $target->fetch();
            $ownsRow = $row && (int)$row['branch_id'] === $myPrinterStoreId;
            if (!$row || !$ownsRow || !in_array($row['type'], ['ip', 'usb'], true) || !$row['status']) {
                $message = t('printers.receipt_needs_lan', 'ເລືອກໄດ້ສະເພາະເຄື່ອງພິມ Network LAN ທີ່ເປີດໃຊ້ງານຢູ່ເທົ່ານັ້ນ!');
                $message_type = 'warning';
            } else {
                $rowBranchId = (int)$row['branch_id'];
                $pdo->prepare("UPDATE st_tbl_printer SET is_receipt_printer = 0 WHERE branch_id = ?")->execute([$rowBranchId]);
                $pdo->prepare("UPDATE st_tbl_printer SET is_receipt_printer = 1 WHERE id = ?")->execute([$id]);
                $message = t('printers.receipt_set_success', 'ຕັ້ງເປັນເຄື່ອງພິມໃບບິນຫຼັກສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ຕັ້ງເຄື່ອງພິມໃບບິນຫຼັກ", $row['type'] . ' #' . $id);
            }
        } catch (Exception $e) {
            $message = sprintf(t('printers.msg_error', 'ຜິດພາດ: %s'), $e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'unset_receipt_printer') {
        $sql = "UPDATE st_tbl_printer SET is_receipt_printer = 0 WHERE branch_id = ?";
        $pdo->prepare($sql)->execute([$myPrinterStoreId]);
        $message = t('printers.receipt_unset_success', 'ຍົກເລີກເຄື່ອງພິມໃບບິນຫຼັກແລ້ວ — ໃບບິນຈະພິມຜ່ານບຣາວເຊີຄືເກົ່າ');
        $message_type = 'success';
    } elseif ($action === 'test_print') {
        // AJAX: send a plain ASCII test page straight to a Network LAN printer (raw TCP).
        header('Content-Type: application/json');
        require_once dirname(__DIR__) . '/../../../api/lan_printer.php';
        session_write_close();
        $id = intval($_POST['id'] ?? 0);
        $testSql = "SELECT * FROM st_tbl_printer WHERE id = ? AND branch_id = ?";
        $testParams = [$id, $myPrinterStoreId];
        $testStmt = $pdo->prepare($testSql . " LIMIT 1");
        $testStmt->execute($testParams);
        $testPrinter = $testStmt->fetch();
        if ($testPrinter && ($testPrinter['type'] ?? '') === 'usb') {
            require_once dirname(__DIR__) . '/../../../api/usb_printer.php';
            $usbName = usbPrinterName($testPrinter['ip_address'] ?? '');
            [$ok, $err] = $usbName === null ? [false, 'no printer name'] : usbPrinterSend($usbName, escposTestPage($testPrinter['name'] ?? '', 'USB', $usbName));
            echo json_encode([
                'success' => $ok,
                'message' => $ok
                    ? sprintf(t('printers.test_ok', 'ສົ່ງໜ້າທົດສອບໄປທີ່ %s ແລ້ວ'), 'USB: ' . $usbName)
                    : sprintf(t('printers.test_fail', 'ເຊື່ອມຕໍ່ເຄື່ອງພິມທີ່ %s ບໍ່ໄດ້: %s'), 'USB: ' . $usbName, $err),
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $addr = $testPrinter ? lanPrinterAddress($testPrinter['ip_address'] ?? '') : null;
        if (!$testPrinter || ($testPrinter['type'] ?? '') !== 'ip' || $addr === null) {
            echo json_encode(['success' => false, 'message' => t('printers.test_not_lan', 'ທົດສອບໄດ້ສະເພາະເຄື່ອງພິມ Network LAN ທີ່ມີ IP Address')], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $target = $addr[0] . ':' . $addr[1];
        [$ok, $err] = lanPrinterSend($addr[0], $addr[1], escposTestPage($testPrinter['name'] ?? '', $addr[0], $addr[1]));
        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? sprintf(t('printers.test_ok', 'ສົ່ງໜ້າທົດສອບໄປທີ່ %s ແລ້ວ'), $target)
                : sprintf(t('printers.test_fail', 'ເຊື່ອມຕໍ່ເຄື່ອງພິມທີ່ %s ບໍ່ໄດ້: %s'), $target, $err),
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } elseif ($action === 'delete_printer') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = "DELETE FROM st_tbl_printer WHERE id = ? AND branch_id = ?";
            $params = [$id, $myPrinterStoreId];
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = t('printers.msg_delete_success', 'ລົບເຄື່ອງພິມສຳເລັດ!');
            $message_type = 'success';
        }
    } elseif ($action === 'set_print_mode') {
        // direct = this server sits on the shop LAN and can reach printer IPs itself.
        // agent  = the server is hosted off-site, so LAN print jobs queue in print_jobs for
        //          the shop's own print agent (tools/print-agent.php, running on a shop PC
        //          that IS on that LAN) to collect and print.
        $mode = ($_POST['print_mode'] ?? 'direct') === 'agent' ? 'agent' : 'direct';
        try {
            $pdo->prepare("INSERT INTO st_system_settings (setting_key, setting_value) VALUES ('print_mode', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([$mode]);
            $message = t('printers.msg_mode_saved', 'ບັນທຶກຮູບແບບການພິມແລ້ວ!');
            $message_type = 'success';
        } catch (Throwable $e) {
            $message = sprintf(t('printers.msg_error', 'ຜິດພາດ: %s'), $e->getMessage());
            $message_type = 'danger';
        }
    } elseif ($action === 'regen_agent_token') {
        try {
            $pdo->prepare("INSERT INTO st_system_settings (setting_key, setting_value) VALUES ('print_agent_token', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
                ->execute([bin2hex(random_bytes(24))]);
            $message = t('printers.msg_token_new', 'ສ້າງລະຫັດຕົວແທນພິມໃໝ່ແລ້ວ! ຢ່າລືມອັບເດດໃສ່ຄອມຮ້ານ.');
            $message_type = 'success';
        } catch (Throwable $e) {
            $message = sprintf(t('printers.msg_error', 'ຜິດພາດ: %s'), $e->getMessage());
            $message_type = 'danger';
        }
    }
}

$printersStmt = $pdo->prepare("SELECT * FROM st_tbl_printer WHERE branch_id = ? ORDER BY id ASC");
$printersStmt->execute([$myPrinterStoreId]);
$printers = $printersStmt->fetchAll();
$printMode = getSetting($pdo, 'print_mode', 'direct') === 'agent' ? 'agent' : 'direct';
$printAgentToken = trim((string)getSetting($pdo, 'print_agent_token', ''));
// The agent can't authenticate without one, so mint it on first view rather than making
// the shop hunt for a button.
if ($printAgentToken === '') {
    try {
        $printAgentToken = bin2hex(random_bytes(24));
        $pdo->prepare("INSERT INTO st_system_settings (setting_key, setting_value) VALUES ('print_agent_token', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$printAgentToken]);
    } catch (Throwable $e) { $printAgentToken = ''; }
}
$pendingPrintJobs = 0;
try {
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM st_print_jobs WHERE status = 'pending' AND store_id = ?");
    $pendingStmt->execute([$myPrinterStoreId]);
    $pendingPrintJobs = (int)$pendingStmt->fetchColumn();
} catch (Throwable $e) {}

require_once dirname(__DIR__) . '/../../../layouts/header.php';
