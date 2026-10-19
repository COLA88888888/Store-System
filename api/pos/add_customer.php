<?php
/**
 * ສ່ວນ: AJAX: ເພີ່ມລູກຄ້າດ່ວນ
 * ເພີ່ມລູກຄ້າໃໝ່ຈາກໜ້າຂາຍ (ກວດເບີໂທຊ້ຳ, ສ້າງລະຫັດລູກຄ້າ) ແລ້ວສົ່ງຂໍ້ມູນລູກຄ້າກັບເປັນ JSON.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ api/pos_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// 2. AJAX handler for Quick Add Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer_ajax') {
    header('Content-Type: application/json');
    $c_name        = trim($_POST['customer_name'] ?? '');
    $c_phone       = trim($_POST['phone'] ?? '');
    $c_address     = trim($_POST['address'] ?? '');
    $c_code        = trim($_POST['customer_code'] ?? '');
    $c_member_card = trim($_POST['member_card'] ?? '');
    $c_notes       = trim($_POST['notes'] ?? '');

    if (empty($c_name)) {
        echo json_encode(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ລູກຄ້າ!']);
        exit();
    }

    $activePosStoreId = getPosStoreId();
    if (empty($c_code)) {
        $stmtSeq = $pdo->prepare("SELECT IFNULL(MAX(customer_id), 0) + 1 FROM st_customers WHERE store_id = ?");
        $stmtSeq->execute([$activePosStoreId]);
        $maxCusId = (int)$stmtSeq->fetchColumn();
        $c_code = 'CUST-' . str_pad($activePosStoreId, 2, '0', STR_PAD_LEFT) . '-' . str_pad($maxCusId, 3, '0', STR_PAD_LEFT);
    }

    try {
        $stmtIns = $pdo->prepare("INSERT INTO st_customers (customer_code, customer_name, phone, address, member_card, notes, store_id, created_at) VALUES (:code, :name, :phone, :address, :card, :notes, :store_id, NOW())");
        $stmtIns->execute([
            ':code'     => $c_code,
            ':name'     => $c_name,
            ':phone'    => $c_phone,
            ':address'  => $c_address,
            ':card'     => $c_member_card,
            ':notes'    => $c_notes,
            ':store_id' => $activePosStoreId
        ]);
        $new_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'customer' => [
                'customer_id'   => $new_id,
                'customer_code' => $c_code,
                'customer_name' => $c_name,
                'phone'         => $c_phone,
                'address'       => $c_address,
                'member_card'   => $c_member_card,
                'notes'         => $c_notes
            ]
        ]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'ຜິດພາດ: ' . $e->getMessage()]);
        exit();
    }
}

