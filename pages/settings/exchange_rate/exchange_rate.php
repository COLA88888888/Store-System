<?php
// ============================================================
// pages/settings/exchange_rate/index.php
// ຟາຍຫຼັກຄວບຄຸມການຕັ້ງຄ່າອັດຕາແລກປ່ຽນເງິນ (Exchange Rates Module)
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

// ກວດສອບສິດທິການເຂົ້າເຖິງ
if (empty($_SESSION['user_id']) || (!hasPermission('exchange_rate') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// ຈັດການ POST Action (ເພີ່ມ, ແກ້ໄຂ, ລຶບ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = $_SESSION['username'] ?? 'admin';

    if ($action === 'save_rate') {
        $thb_rate = floatval(str_replace(',', '', $_POST['ex_kip_bath'] ?? '0'));
        $usd_rate = floatval(str_replace(',', '', $_POST['ex_kip_us'] ?? '0'));
        $cny_rate = floatval(str_replace(',', '', $_POST['ex_kip_cn'] ?? '0'));

        if ($thb_rate > 0 && $usd_rate > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO tbexchange (ex_date, ex_time, ex_kip_bath, ex_kip_us, ex_kip_cn, ex_status, ex_userlogin, branch_id) VALUES (CURDATE(), CURTIME(), ?, ?, ?, 'Active', ?, 1)");
                $stmt->execute([$thb_rate, $usd_rate, $cny_rate, $username]);
                $message = t('exchange_rate.msg_save_success', 'ອັບເດດອັດຕາແລກປ່ຽນເງິນສຳເລັດແລ້ວ!');
                $message_type = 'success';
                logActivity($pdo, "ອັບເດດອັດຕາແລກປ່ຽນ", "THB: $thb_rate ₭, USD: $usd_rate ₭, CNY: $cny_rate ₭");
            } catch (Exception $e) {
                $message = t('exchange_rate.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = t('exchange_rate.msg_invalid_rate', 'ກະລຸນາປ້ອນອັດຕາແລກປ່ຽນທີ່ຖືກຕ້ອງ!');
            $message_type = 'warning';
        }
    } elseif ($action === 'update_rate') {
        $rate_id = intval($_POST['rate_id'] ?? 0);
        $thb_rate = floatval(str_replace(',', '', $_POST['ex_kip_bath'] ?? '0'));
        $usd_rate = floatval(str_replace(',', '', $_POST['ex_kip_us'] ?? '0'));
        $cny_rate = floatval(str_replace(',', '', $_POST['ex_kip_cn'] ?? '0'));

        if ($rate_id > 0 && $thb_rate > 0 && $usd_rate > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE tbexchange SET ex_kip_bath = ?, ex_kip_us = ?, ex_kip_cn = ?, ex_userlogin = ? WHERE Id = ?");
                $stmt->execute([$thb_rate, $usd_rate, $cny_rate, $username, $rate_id]);
                $message = t('exchange_rate.msg_update_success', 'ດຳເນີນການອັບເດດອັດຕາແລກປ່ຽນສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ແກ້ໄຂອັດຕາແລກປ່ຽນ", "ID: $rate_id, THB: $thb_rate ₭, USD: $usd_rate ₭, CNY: $cny_rate ₭");
            } catch (Exception $e) {
                $message = t('exchange_rate.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'delete_rate') {
        $rate_id = intval($_POST['rate_id'] ?? 0);
        if ($rate_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM tbexchange WHERE Id = ?");
                $stmt->execute([$rate_id]);
                $message = t('exchange_rate.msg_delete_success', 'ລຶບປະຫວັດອັດຕາແລກປ່ຽນສຳເລັດແລ້ວ!');
                $message_type = 'success';
                logActivity($pdo, "ລຶບອັດຕາແລກປ່ຽນ", "ID: $rate_id");
            } catch (Exception $e) {
                $message = t('exchange_rate.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// ດຶງປະຫວັດອັດຕາແລກປ່ຽນທັງໝົດ
$historyRates = $pdo->query("SELECT * FROM tbexchange ORDER BY Id DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../../layouts/header.php';
?>

<!-- Layout ຫຼັກ ສະແດງຜົນແຕ່ລະສ່ວນຍ່ອຍ (Modular Components) -->
<div class="container-fluid p-4">

  <!-- 1. ສ່ວນ Header ແລະ Alert -->
  <?php require_once __DIR__ . '/exchange_rate_header.php'; ?>

  <!-- 2. ສ່ວນ ຕາຕະລາງປະຫວັດອັດຕາແລກປ່ຽນ -->
  <?php require_once __DIR__ . '/exchange_rate_table.php'; ?>

</div>

<!-- 3. ສ່ວນ Modal ຟອມເພີ່ມ/ແກ້ໄຂ ແລະ Form ລຶບ -->
<?php require_once __DIR__ . '/exchange_rate_modals.php'; ?>

<!-- 4. ສ່ວນ JavaScript ເຄື່ອງມື -->
<?php require_once __DIR__ . '/exchange_rate_js.php'; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>