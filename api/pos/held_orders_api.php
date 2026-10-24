<?php
/**
 * ຈັດການບິນທີ່ພັກໄວ້ (Held Orders API)
 * Path: api/pos/held_orders_api.php
 * ບັນທຶກບິນທີ່ພັກໄວ້ລົງໃນຖານຂໍ້ມູນຢ່າງຖາວອນ ບໍ່ໃຫ້ຫາຍຈັກມື້ ຈົນກວ່າຈະຊຳລະ
 */

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. ດຶງລາຍການບິນທີ່ພັກໄວ້ທັງໝົດຂອງສາຂາ (Status = 'held')
if ($action === 'list_held_orders') {
    header('Content-Type: application/json; charset=utf-8');
    $storeId = getPosStoreId();
    try {
        $stmt = $pdo->prepare("
            SELECT id, store_id, user_id, user_name, customer_id, customer_name, customer_phone,
                   hold_date, hold_time, items_count, total_qty, total_amount, discount, discount_mode,
                   cart_json, customer_json, status, created_at
            FROM st_held_orders 
            WHERE store_id = ? AND status = 'held' 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$storeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $heldOrders = [];
        foreach ($rows as $r) {
            $cart = json_decode($r['cart_json'] ?? '[]', true) ?: [];
            $customer = json_decode($r['customer_json'] ?? '{}', true) ?: [
                'customer_id'   => $r['customer_id'],
                'customer_name' => $r['customer_name'] ?: 'ລູກຄ້າທົ່ວໄປ',
                'phone'         => $r['customer_phone'] ?: ''
            ];

            $heldOrders[] = [
                'id'            => $r['id'],
                'date'          => $r['hold_date'],
                'time'          => $r['hold_time'],
                'cart'          => $cart,
                'customer'      => $customer,
                'discount'      => $r['discount'] ?? '0',
                'discount_mode' => $r['discount_mode'] ?? 'amount',
                'user_name'     => $r['user_name'] ?? '',
                'created_at'    => $r['created_at']
            ];
        }

        echo json_encode(['success' => true, 'held_orders' => $heldOrders], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// 2. ບັນທຶກ ຫຼື ອັບເດດບິນທີ່ພັກໄວ້ (Save / Upsert Held Order)
if ($action === 'save_held_order') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    $id = trim($data['id'] ?? '');
    if (!$id) {
        $id = 'HOLD-' . time() . '-' . rand(100, 999);
    }

    $cart = $data['cart'] ?? [];
    $customer = $data['customer'] ?? [];
    $discount = strval($data['discount'] ?? '0');
    $discountMode = strval($data['discount_mode'] ?? 'amount');
    $storeId = getPosStoreId();
    $userId = intval($_SESSION['user_id'] ?? 0);
    $userName = $_SESSION['fname'] ?? $_SESSION['username'] ?? 'Cashier';

    $holdDate = !empty($data['date']) ? trim($data['date']) : date('Y-m-d');
    $holdTime = !empty($data['time']) ? trim($data['time']) : date('H:i');

    $itemsCount = count($cart);
    $totalQty = 0;
    $totalAmount = 0;
    foreach ($cart as $ci) {
        $q = floatval($ci['quantity'] ?? 0);
        $p = floatval($ci['unit_price'] ?? 0);
        $totalQty += $q;
        $totalAmount += ($q * $p);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO st_held_orders 
                (id, store_id, user_id, user_name, customer_id, customer_name, customer_phone,
                 hold_date, hold_time, items_count, total_qty, total_amount, discount, discount_mode,
                 cart_json, customer_json, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'held', NOW())
            ON DUPLICATE KEY UPDATE
                customer_id = VALUES(customer_id),
                customer_name = VALUES(customer_name),
                customer_phone = VALUES(customer_phone),
                items_count = VALUES(items_count),
                total_qty = VALUES(total_qty),
                total_amount = VALUES(total_amount),
                discount = VALUES(discount),
                discount_mode = VALUES(discount_mode),
                cart_json = VALUES(cart_json),
                customer_json = VALUES(customer_json),
                status = 'held',
                updated_at = NOW()
        ");
        $stmt->execute([
            $id,
            $storeId,
            $userId,
            $userName,
            $customer['customer_id'] ?? null,
            $customer['customer_name'] ?? 'ລູກຄ້າທົ່ວໄປ',
            $customer['phone'] ?? '',
            $holdDate,
            $holdTime,
            $itemsCount,
            $totalQty,
            $totalAmount,
            $discount,
            $discountMode,
            json_encode($cart, JSON_UNESCAPED_UNICODE),
            json_encode($customer, JSON_UNESCAPED_UNICODE)
        ]);

        echo json_encode([
            'success' => true,
            'id'      => $id,
            'date'    => $holdDate,
            'time'    => $holdTime
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// 3. ລົບບິນທີ່ພັກໄວ້ອອກ (Delete Held Order)
if ($action === 'delete_held_order') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $id = trim($data['id'] ?? $_GET['id'] ?? '');
    $storeId = getPosStoreId();

    if ($id) {
        try {
            $stmt = $pdo->prepare("UPDATE st_held_orders SET status = 'deleted', updated_at = NOW() WHERE id = ? AND store_id = ?");
            $stmt->execute([$id, $storeId]);
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
    }
    exit();
}

// 4. ລ້າງບິນພັກທັງໝົດຂອງສາຂານີ້ (Clear All Held Orders)
if ($action === 'clear_all_held_orders') {
    header('Content-Type: application/json; charset=utf-8');
    $storeId = getPosStoreId();
    try {
        $stmt = $pdo->prepare("UPDATE st_held_orders SET status = 'deleted', updated_at = NOW() WHERE store_id = ? AND status = 'held'");
        $stmt->execute([$storeId]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// 5. ປ່ຽນສະຖານະເປັນຈ່າຍແລ້ວ (Mark Paid)
if ($action === 'mark_paid_held_order') {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $id = trim($data['id'] ?? $_GET['id'] ?? '');
    $storeId = getPosStoreId();

    if ($id) {
        try {
            $stmt = $pdo->prepare("UPDATE st_held_orders SET status = 'paid', updated_at = NOW() WHERE id = ? AND store_id = ?");
            $stmt->execute([$id, $storeId]);
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
    }
    exit();
}
