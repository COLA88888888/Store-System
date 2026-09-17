<?php
require_once __DIR__ . '/../config/db.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$quotation_id = intval($_GET['id']);
$user_receive_name = $_SESSION['username'] ?? $_SESSION['fname'] ?? 'Admin';
$sold_by_id = intval($_SESSION['user_id'] ?? 1);
$client_ip = getClientIP();

try {
    $pdo->beginTransaction();

    // 1. Fetch Quotation
    $stmtQ = $pdo->prepare("SELECT * FROM quotations WHERE quotation_id = ? FOR UPDATE");
    $stmtQ->execute([$quotation_id]);
    $quotation = $stmtQ->fetch(PDO::FETCH_ASSOC);

    if (!$quotation || $quotation['status'] !== 'pending') {
        throw new Exception("Quotation not found or already processed.");
    }

    $activeStoreId = $quotation['store_id'];

    // 2. Fetch Quotation Details
    $stmtD = $pdo->prepare("SELECT * FROM quotation_details WHERE quotation_id = ?");
    $stmtD->execute([$quotation_id]);
    $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    // Generate new Sale Invoice Number
    $todayDate = date('Y-m-d');
    $todayStr  = date('Ymd');
    $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM tbsale_save WHERE sale_date = :today_date");
    $stmtSeq->execute([':today_date' => $todayDate]);
    $todayCount = intval($stmtSeq->fetchColumn() ?? 0);
    $invoice_no = $todayStr . '-' . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

    $total_qty = 0;
    foreach ($details as $d) {
        $total_qty += floatval($d['qty']);
    }

    // Insert into tbsale_save
    $stmtSave = $pdo->prepare("
        INSERT INTO tbsale_save (sale_save_bill, sale_date, sale_time, user_receive, customer_id, customer_name, ip_address, sale_qty, sale_amount, sale_discount_bill, sale_barlance, sale_pay, sale_return, type_pay, sale_status, store_id)
        VALUES (:invoice_no, CURDATE(), CURTIME(), :user_id, :customer_id, :customer_name, :ip_address, :sale_qty, :sale_amount, :discount_bill, :net_total, :cash_received, 0, 'Quotation To Sale', 'SUCCESS', :store_id)
    ");
    $stmtSave->execute([
        ':invoice_no'      => $invoice_no,
        ':user_id'         => $user_receive_name,
        ':customer_id'     => $quotation['customer_id'],
        ':customer_name'   => $quotation['customer_name'],
        ':ip_address'      => $client_ip,
        ':sale_qty'        => $total_qty,
        ':sale_amount'     => $quotation['subtotal'],
        ':discount_bill'   => $quotation['discount_amount'],
        ':net_total'       => $quotation['total_amount'],
        ':cash_received'   => $quotation['total_amount'],
        ':store_id'        => $activeStoreId
    ]);

    // Insert into physical sales table
    $stmtSalesTable = $pdo->prepare("
        INSERT INTO sales (invoice_number, sold_by, customer_id, customer_name, subtotal, discount_amount, vat_amount, tax_type, vat_rate, total_amount, cash_received, change_amount, payment_type, status, store_id, created_at)
        VALUES (:invoice_no, :sold_by_id, :customer_id, :customer_name, :subtotal, :discount_bill, :vat_amount, 'none', 0, :net_total, :cash_received, 0, 'Quotation To Sale', 'SUCCESS', :store_id, NOW())
    ");
    $stmtSalesTable->execute([
        ':invoice_no'      => $invoice_no,
        ':sold_by_id'      => $sold_by_id,
        ':customer_id'     => $quotation['customer_id'],
        ':customer_name'   => $quotation['customer_name'],
        ':subtotal'        => $quotation['subtotal'],
        ':discount_bill'   => $quotation['discount_amount'],
        ':vat_amount'      => $quotation['vat_amount'],
        ':net_total'       => $quotation['total_amount'],
        ':cash_received'   => $quotation['total_amount'],
        ':store_id'        => $activeStoreId
    ]);

    $stmtCheckStock = $pdo->prepare("SELECT qty, cut_qty FROM products WHERE product_id = :pid AND store_id = :store_id FOR UPDATE");
    $stmtUpdateStock = $pdo->prepare("UPDATE products SET qty = qty - :deduct_qty WHERE product_id = :pid AND store_id = :store_id");
    $stmtSaleDetail = $pdo->prepare("
        INSERT INTO tbsale_save_detail (save_bill, save_date, save_time, save_proid, save_proname, save_qty, save_price, cost_price, save_money, save_net_money, user_receives, multiplier)
        VALUES (:save_bill, CURDATE(), CURTIME(), :save_proid, :save_proname, :save_qty, :save_price, 0, :save_money, :save_net_money, :user_receives, 1)
    ");

    foreach ($details as $d) {
        $pid = intval($d['product_id']);
        $qty = floatval($d['qty']);
        $price = floatval($d['price']);
        
        $stmtCheckStock->execute([':pid' => $pid, ':store_id' => $activeStoreId]);
        $pData = $stmtCheckStock->fetch();
        
        if ($pData && intval($pData['cut_qty']) === 1) {
            $stmtUpdateStock->execute([
                ':deduct_qty' => $qty,
                ':pid' => $pid,
                ':store_id' => $activeStoreId
            ]);
        }
        
        $product_name_with_ref = $d['product_name'] . ' (ຈາກໃບສະເໜີລາຄາ ' . $quotation['quotation_no'] . ')';

        $stmtSaleDetail->execute([
            ':save_bill'    => $invoice_no,
            ':save_proid'   => $pid,
            ':save_proname' => $product_name_with_ref,
            ':save_qty'     => $qty,
            ':save_price'   => $price,
            ':save_money'   => $d['line_total'],
            ':save_net_money'=> $d['line_total'],
            ':user_receives'=> $user_receive_name
        ]);
    }

    // Update Quotation Status
    $pdo->prepare("UPDATE quotations SET status = 'accepted' WHERE quotation_id = ?")->execute([$quotation_id]);

    $pdo->commit();
    echo "<script>alert('ຂາຍ ແລະ ຕັດສະຕັອກສຳເລັດແລ້ວ! ເລກບິນ: $invoice_no'); window.location.href = '../pages/quotations/list.php';</script>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href = '../pages/quotations/list.php';</script>";
}
