<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າ Dashboard (ພາບລວມຍອດຂາຍ): ຄິດໄລ່ຕົວເລກ ແລະ ຂໍ້ມູນກຣາຟດ້ວຍ PHP ແລ້ວສະແດງບັດສະຖິຕິ ແລະ ກຣາຟ Chart.js.
 *   - partials/home/data_metrics.php : ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຕົວເລກຫຼັກ
 *   - partials/home/data_charts.php : ຂໍ້ມູນ: ກຣາຟ
 *   - partials/home/page_body.php : ສ່ວນສະແດງຜົນ: ຕົວກັ່ນຕອງ, ບັດສະຖິຕິ ແລະ ກ່ອງກຣາຟ
 *   - partials/home/js_charts_revenue.php : JS: ນັບຕົວເລກ ແລະ ກຣາຟລາຍຮັບ
 *   - partials/home/js_charts_other.php : JS: ກຣາຟລາຍປີ, % ກຳໄລ ແລະ ສິນຄ້າຂາຍດີ
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Multi-currency: dashboard figures stored in KIP, shown in the active currency. A
// currency picked here overrides the store's global display_currency setting for this
// view only (does not filter which bills are counted — just how the totals are shown).
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && isset(getCurrencyMeta()[$currency_filter])) {
    $__hRates = getExchangeRates($pdo);
    $__hRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__hRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__hRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__hRate, 'rates' => $__hRates],
            getCurrencyMeta()[$currency_filter]
        ));
    } else {
        $currency_filter = '';
    }
} else {
    $currency_filter = '';
}

$__hdc = getDisplayCurrency($pdo);
$__hsym = $__hdc['symbol']; $__hdec = (int)$__hdc['decimals'];
$__hcvt = function($v) use ($__hdc) { return convertFromKip($v, $__hdc['code'], $__hdc['rates']); };

if (empty($_SESSION['user_id']) || empty($_SESSION['checked'])) {
    echo "<script>window.top.location.href = '../auth/login.php?expired=1';</script>";
    exit();
}

// ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ຕົວເລກຫຼັກ
require __DIR__ . '/partials/home/data_metrics.php';
// ຂໍ້ມູນ: ກຣາຟ
require __DIR__ . '/partials/home/data_charts.php';
$base_path = '../';
require_once __DIR__ . '/../layouts/header.php';
?>

<link rel="stylesheet" href="../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../themes/reports.css'); ?>">

<?php echo themeCssLink('home.css'); /* CSS ຍ້າຍໄປ themes/home.css */ ?>
<script>
  // ຕັ້ງຊ່ວງວັນທີຕາມປຸ່ມທີ່ກົດ ແລ້ວສົ່ງຟອມ — ມື້ນີ້/ເດືອນນີ້ ບັງຄັບເປັນປີປັດຈຸບັນ, ທັງປີ ໃຊ້ປີທີ່ເລືອກຢູ່
  function homeSetPeriod(period, form) {
    var today = <?php echo json_encode(date('Y-m-d')); ?>;
    var curYear = <?php echo (int)$current_year_real; ?>;
    var from, to;
    if (period === 'today') {
      from = to = today;
      form.year.value = curYear;
    } else if (period === 'month') {
      from = today.slice(0, 8) + '01';
      to = today;
      form.year.value = curYear;
    } else {
      var yr = form.year.value;
      from = yr + '-01-01';
      to = yr + '-12-31';
    }
    form.from_date.value = from;
    form.to_date.value = to;
    form.submit();
  }
</script>
<div class="container-fluid p-3 p-md-4">
<?php require __DIR__ . '/partials/home/page_body.php'; /* ສ່ວນສະແດງຜົນ: ຕົວກັ່ນຕອງ, ບັດສະຖິຕິ ແລະ ກ່ອງກຣາຟ */ ?>
<!-- Chart.js Library -->
<script src="../plugins/chart.js/Chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<?php require __DIR__ . '/partials/home/js_charts_revenue.php'; /* JS: ນັບຕົວເລກ ແລະ ກຣາຟລາຍຮັບ */ ?>
<?php require __DIR__ . '/partials/home/js_charts_other.php'; /* JS: ກຣາຟລາຍປີ, % ກຳໄລ ແລະ ສິນຄ້າຂາຍດີ */ ?>
