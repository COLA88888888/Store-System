<?php
/**
 * ສ່ວນ: AJAX: ໜີ້ເກົ່າຂອງລູກຄ້າ
 * ຟອມຂາຍຕິດໜີ້ໃຊ້ສະແດງ "ໜີ້ເກົ່າທີ່ຍັງຄ້າງ" ແລະ "ໜີ້ລວມຫຼັງບິນນີ້": ຍອດຄ້າງ, ຈຳນວນບິນ ແລະ
 * ຈຳນວນບິນທີ່ກາຍກຳນົດຊຳລະແລ້ວ — ສະເພາະສາຂາຂອງໜ້າຂາຍ (getPosStoreId()).
 * ໄຟລ໌ນີ້ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຈາກ api/pos_backend.php.
 */

if (isset($_GET['action']) && $_GET['action'] === 'customer_debt_info') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $cid = intval($_GET['customer_id'] ?? 0);
    // Same right as the ຕິດໜີ້ button itself
    if (!hasPermission('customer_debt', 'add')) {
        echo json_encode(['success' => false]);
        exit();
    }
    $out = ['success' => true, 'balance' => 0, 'bills' => 0, 'overdue' => 0];
    if ($cid > 0) {
        $st = $pdo->prepare("SELECT COALESCE(SUM(balance), 0) AS balance, COUNT(*) AS bills,
                                    COALESCE(SUM(due_date IS NOT NULL AND due_date < CURDATE()), 0) AS overdue
                             FROM st_customer_debts
                             WHERE customer_id = ? AND store_id = ? AND status IN ('unpaid','partial') AND balance > 0");
        $st->execute([$cid, getPosStoreId()]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $out['balance'] = round(floatval($row['balance'] ?? 0), 2);
        $out['bills']   = intval($row['bills'] ?? 0);
        $out['overdue'] = intval($row['overdue'] ?? 0);
    }
    echo json_encode($out);
    exit();
}
