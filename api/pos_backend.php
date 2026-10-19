<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * Backend ຂອງໜ້າຂາຍ: ຈັດການ AJAX (ສະຕັອກສົດ, ພິມ LAN, ຂາຍ/checkout, ເພີ່ມລູກຄ້າ) ແລະ ກຽມຂໍ້ມູນໃຫ້ pages/pos/pos.php. ທຸກການກະທຳ AJAX ຈະ exit() ກ່ອນສ່ວນດຶງຂໍ້ມູນໜ້າ.
 *   - pos/customer_debt_info.php : AJAX: ໜີ້ເກົ່າຂອງລູກຄ້າ (ຟອມຂາຍຕິດໜີ້)
 *   - pos/print_lan_ticket.php : AJAX: ພິມບິນຍ່ອຍ LAN
 *   - pos/checkout_totals.php : Checkout (1): ຄິດຍອດເງິນ
 *   - pos/checkout_save.php : Checkout (2): ບັນທຶກບິນ
 *   - pos/add_customer.php : AJAX: ເພີ່ມລູກຄ້າດ່ວນ
 *   - pos/promotions.php : ໂປຣໂມຊັ່ນ
 *   - pos/page_data.php : ຂໍ້ມູນສຳລັບໜ້າຂາຍ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';

// Check authorization
if (empty($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ເຊດຊັນໝົດອາຍຸ! ກະລຸນາເຂົ້າສູ່ລະບົບໃໝ່']);
        exit();
    }
    echo "<script>window.top.location.href = '../index.php';</script>";
    exit();
}

$vat_rate = floatval(getSetting($pdo, 'vat_rate', '0'));

// Fetch Company/Store info for the branch making this sale — shared name/address/phone from
// the main branch, logo specific to this branch.
$company = getCompanyInfoForBranch($pdo, getPosStoreId());

// Helper function to get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Live Stock Polling Handler
if (isset($_GET['action']) && $_GET['action'] === 'get_live_stocks') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    $liveStoreId = getPosStoreId();
    $stmt = $pdo->prepare("SELECT product_id, qty FROM st_products WHERE store_id = ?");
    $stmt->execute([$liveStoreId]);
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'stocks' => $stocks]);
    exit();
}

// AJAX: ໜີ້ເກົ່າຂອງລູກຄ້າ (ຟອມຂາຍຕິດໜີ້)
require __DIR__ . '/pos/customer_debt_info.php';
// AJAX: ພິມບິນຍ່ອຍ LAN
require __DIR__ . '/pos/print_lan_ticket.php';
// 1. AJAX handler for Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    ob_start();
    header('Content-Type: application/json');
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    $cash_received = floatval($_POST['cash_received'] ?? 0.00);
    $qr_received   = floatval($_POST['qr_received']   ?? 0.00);
    $payment_type  = $_POST['payment_type'] ?? 'ເງິນສົດ';
    $pay_mode      = $_POST['pay_mode']     ?? 'single';
    $discount_bill = floatval($_POST['discount_amount'] ?? 0.00);
    $customer_id   = !empty($_POST['customer_id']) ? strval($_POST['customer_id']) : '';
    $customer_name = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : 'ລູກຄ້າທົ່ວໄປ';
    $client_ip     = getClientIP();
    // Loyalty rewards the customer is exchanging points for on this bill — a list of
    // loyalty_rewards ids (an id repeats when the same reward is redeemed twice). Costs and
    // discount values are always re-read from the DB below, never taken from the client.
    $redeem_ids = json_decode($_POST['redemptions'] ?? '[]', true);
    $redeem_ids = is_array($redeem_ids) ? array_values(array_filter(array_map('intval', $redeem_ids))) : [];

    // Tender currency for this bill (chosen at checkout). Base data stays in KIP; these
    // just record what the customer handed over so the daily report can split by currency.
    $allowed_currencies = ['KIP', 'THB', 'USD', 'CNY'];
    $paid_currency = strtoupper(trim((string)($_POST['paid_currency'] ?? 'KIP')));
    if (!in_array($paid_currency, $allowed_currencies, true)) $paid_currency = 'KIP';
    $paid_rate = ($paid_currency === 'KIP') ? 1.0 : (float)($_POST['paid_rate'] ?? 0);
    if ($paid_rate <= 0) { $paid_currency = 'KIP'; $paid_rate = 1.0; }
    
    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'ກະຕ່າສິນຄ້າຫວ່າງເປົ່າ!']);
        exit();
    }

    // Idempotency guard: the exact same checkout (same cart + amounts + customer, same login
    // session) arriving again within a few seconds is a duplicate submission — a double-tap on
    // the submit button, a slow-network retry, or a stale browser tab that fired before a fix
    // landed — never a genuine second sale (a real repeat purchase is never byte-identical down
    // to the tendered amounts). Replay the first request's own response instead of creating a
    // second bill and decrementing stock twice. This is server-side and session-scoped, so it
    // still catches a duplicate even if the client-side re-entrancy guard in pos_checkout_js.php
    // was bypassed or hasn't loaded yet.
    $idemFp = md5(($_POST['cart'] ?? '') . '|' . $cash_received . '|' . $qr_received . '|' .
        $payment_type . '|' . $discount_bill . '|' . $customer_id . '|' . implode(',', $redeem_ids) . '|' . ($_SESSION['user_id'] ?? ''));
    if (
        isset($_SESSION['pos_last_checkout_fp'], $_SESSION['pos_last_checkout_time'], $_SESSION['pos_last_checkout_response']) &&
        $_SESSION['pos_last_checkout_fp'] === $idemFp &&
        (time() - $_SESSION['pos_last_checkout_time']) < 20
    ) {
        echo json_encode($_SESSION['pos_last_checkout_response']);
        exit();
    }

    try {
        // Checkout (1): ຄິດຍອດເງິນ
        require __DIR__ . '/pos/checkout_totals.php';
        // Checkout (2): ບັນທຶກບິນ
        require __DIR__ . '/pos/checkout_save.php';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
        exit();
    }
}

// AJAX: ເພີ່ມລູກຄ້າດ່ວນ
require __DIR__ . '/pos/add_customer.php';
// ໂປຣໂມຊັ່ນ
require __DIR__ . '/pos/promotions.php';
// ຂໍ້ມູນສຳລັບໜ້າຂາຍ
require __DIR__ . '/pos/page_data.php';
