<?php
/**
 * API: ສິດປັດຈຸບັນຂອງຜູ້ໃຊ້ທີ່ Login ຢູ່ — ໃຫ້ home/partials/dashboard/js_live_permissions.php ຖາມທຸກໆສອງສາມວິນາທີ
 * ເພື່ອສະແດງ/ເຊື່ອງເມນູ ແລະ ໂຫຼດໜ້າທີ່ເປີດຢູ່ໃໝ່ທັນທີ ເມື່ອຜູ້ບໍລິຫານເປີດ/ປິດສິດ (ໜ້າກຳນົດສິດ).
 * ສົ່ງຄືນ { perms: { ໂມດູນ: "VAED" }, sig } — V/A/E/D = ເບິ່ງ/ເພີ່ມ/ແກ້ໄຂ/ລຶບ (1 = ມີສິດ, 0 = ບໍ່ມີ).
 * ເປັນຄຳຂໍ passive: ບໍ່ຕໍ່ເວລາ idle 15 ນາທີ (config/bootstrap/session_auth.php).
 * ສິດຄິດໄລ່ດ້ວຍ hasPermission() ຕົວດຽວກັບທຸກໜ້າ — session ອ່ານສິດຈາກຖານຂໍ້ມູນໃໝ່ທຸກຄຳຂໍຢູ່ແລ້ວ.
 */
define('SESSION_PASSIVE_REQUEST', true);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();
require_once __DIR__ . '/../config/db.php';
ob_end_clean();   // session_auth ອາດພິມ <script> ອອກຈາກລະບົບ — ບໍ່ໃຫ້ປົນ JSON

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['user_id']) || empty($_SESSION['checked'])) {
    echo json_encode(['success' => false, 'unauthenticated' => true]);
    exit();
}

// ທຸກໂມດູນທີ່ເມນູ (layouts/sidebar.php) ແລະ ປຸ່ມໃນໜ້າຕ່າງໆ ກວດດ້ວຍ hasPermission()
$modules = [
    'dashboard', 'sale', 'item_sales', 'customers',
    'categories', 'units', 'products', 'suppliers', 'stock_check', 'stock_count',
    'import_stock', 'import_list', 'stock_transfer', 'transfer_history',
    'accounting', 'bank', 'report', 'daily_report', 'all_sales', 'best_seller', 'profit_cost', 'financial',
    'category_sales', 'expiry', 'shift_report', 'shift_close_report', 'shift_revenue_report', 'expiry_report', 'delete_bills',
    'users', 'permissions', 'branches', 'stores', 'print_barcode', 'exchange_rate', 'promotions',
    'price_adjustment', 'printers', 'loyalty', 'shifts', 'tts', 'database', 'setup', 'clear_data',
];

$perms = [];
foreach ($modules as $m) {
    $perms[$m] = (hasPermission($m, 'view') ? '1' : '0') . (hasPermission($m, 'add') ? '1' : '0')
               . (hasPermission($m, 'edit') ? '1' : '0') . (hasPermission($m, 'del') ? '1' : '0');
}

echo json_encode(['success' => true, 'perms' => $perms, 'sig' => md5(json_encode($perms))], JSON_UNESCAPED_UNICODE);
