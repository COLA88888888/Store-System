<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍຫຼັກ: ໜ້າຕັ້ງຄ່າລຶບ / ລ້າງຂໍ້ມູນລະບົບ (Clear & Reset System Data Page)
 * Path: pages/settings/clear_data/index.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່: ເລືອກລຶບຂໍ້ມູນໃນລະບົບເປັນແຕ່ລະສ່ວນ (ລາຍການສ່ວນ: api/clear_data/sections.php),
 * ຢືນຢັນດ້ວຍລະຫັດຜ່ານກ່ອນລຶບຖາວອນ (api/clear_data_backend.php).
 *
 * ລຳດັບສ່ວນປະກອບ (partials/):
 *   1. header.php         ຫົວຂໍ້ ແລະ ຄຳເຕືອນໃຫ້ສຳຮອງຂໍ້ມູນ
 *   2. options_cards.php  ປຸ່ມເລືອກດ່ວນ + ກຸ່ມສ່ວນຂໍ້ມູນ (ໃຊ້ $clearSections)
 *   3. modal_confirm.php  ໜ້າຕ່າງຢືນຢັນລະຫັດຜ່ານ
 *   4. js.php             ນັບຈຳນວນ, ເລືອກ, ສົ່ງລຶບ
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

$userId = intval($_SESSION['user_id'] ?? 0);
if ($userId <= 0 || !hasPermission('clear_data')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$clearSections = require __DIR__ . '/../../../api/clear_data/sections.php';

// ເລືອກສາຂາທີ່ຈະລຶບ: ຜູ້ທີ່ເຫັນທຸກສາຂາ ເລືອກໄດ້ (ລວມສາຂາທີ່ປິດໃຊ້ງານ ເພາະອາດຍັງມີຂໍ້ມູນຄ້າງ);
// ຜູ້ໃຊ້ສາຂາຍ່ອຍ ຖືກລັອກໄວ້ທີ່ສາຂາຂອງຕົນ (backend ກວດຊ້ຳ)
$cdCanAllBranches = canViewAllBranches($pdo);
$cdStores = $pdo->query('SELECT store_id, store_name, status, is_main FROM st_tbstore ORDER BY store_id ASC')->fetchAll(PDO::FETCH_ASSOC);
$cdOwnStoreId = (int)getActiveStoreId($pdo);
$cdOwnStoreName = '';
foreach ($cdStores as $st) {
    if ((int)$st['store_id'] === $cdOwnStoreId) $cdOwnStoreName = $st['store_name'];
}

require_once __DIR__ . '/../../../layouts/header.php';
?>

<?php echo themeCssLink('reports.css'); ?>
<?php echo themeCssLink('settings-clear-data.css'); ?>

<div class="container-fluid p-3 p-md-4">
  <?php require __DIR__ . '/partials/header.php'; ?>
  <?php require __DIR__ . '/partials/options_cards.php'; ?>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<?php require __DIR__ . '/partials/js.php'; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
