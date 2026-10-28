<?php
/**
 * ໜ້າລາຍງານປິດກະ (Shift Close & Cash Reconciliation Report)
 * ຕິດຕາມປະຫວັດການເປີດ-ປິດກະ, ກວດສອບເງິນລິ້ນຊັກ, ເງິນສົດນັບໄດ້ຕົວຈິງ ແລະ ສ່ວນຕ່າງເງິນສົດ
 */
session_start();
$base_path = '../../';
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id']) || !hasPermission('shift_close_report')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// ສາຂານີ້ບໍ່ໄດ້ຕັ້ງກະ ແລະ ບໍ່ເຄີຍເປີດກະ → ສະແດງແຕ່ຂໍ້ຄວາມວ່າງ
$shHasShifts = branchHasShifts($pdo, intval($_SESSION['store_id'] ?? 0));

$userStatus = $_SESSION['status'] ?? '';
$isAdmin = (intval($_SESSION['user_id'] ?? 0) === 1 || $userStatus === 'ຜູ້ບໍລິຫານ' || strtolower($userStatus) === 'admin' || strtolower($userStatus) === 'super admin');
$isCashier = ($userStatus === 'ພະນັກງານ' && !$isAdmin);

// ບັອກເລືອກສາຂາ: ສະເພາະຜູ້ທີ່ເບິ່ງທຸກສາຂາໄດ້ ແລະ ມີ 2 ສາຂາຂຶ້ນໄປ
$shBranches = [];
try {
    $shBranches = $pdo->query("SELECT store_id, store_name, is_main FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$shBranchPick = canViewAllBranches($pdo) && count($shBranches) >= 2;
$shDefaultBranch = (int)getActiveStoreId($pdo);

$sr = function ($key) { return htmlspecialchars(t($key)); };

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="<?php echo $base_path; ?>themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<?php echo themeCssLink('report-table.css'); ?>

<style>
.report-filter-box,
.report-table-card,
.kpi-card-item,
#activeShiftBanner,
#noActiveShiftNotice,
.card {
    border-radius: 8px !important;
}
.btn,
.form-control {
    border-radius: 4px !important;
}
.nav-pills .nav-link {
    font-size: 0.9rem;
    padding: 8px 18px;
    transition: all 0.2s ease;
}
</style>

<script>
window.IS_CASHIER_USER = <?php echo $isCashier ? 'true' : 'false'; ?>;
window.IS_ADMIN_USER = <?php echo $isAdmin ? 'true' : 'false'; ?>;
window.SHIFT_REPORT_MODE = 'close';
</script>

<div class="container-fluid p-3 p-md-4">

  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-cash-register mr-2" style="color: #2c5aa0;"></i> <?php echo htmlspecialchars(t('layout.shift_close_report', 'ລາຍງານປິດກະ')); ?>
      </h5>
      <small class="text-muted" style="font-size: 0.82rem;">ຕິດຕາມປະຫວັດການເປີດ-ປິດກະ, ກວດສອບເງິນລິ້ນຊັກ, ເງິນສົດນັບໄດ້ຕົວຈິງ ແລະ ສ່ວນຕ່າງເງິນສົດ</small>
    </div>
    <?php if ($shHasShifts): ?>
    <div class="page-actions">
<?php require __DIR__ . '/../../layouts/partials/report/export_buttons.php'; ?>
    </div>
    <?php endif; ?>
  </div>

<?php if (!$shHasShifts): ?>
  <div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="card-body text-center py-5 text-muted">
      <i class="fas fa-user-clock fa-3x mb-3 text-secondary d-block" style="opacity: 0.5;"></i>
      <h6 class="font-weight-bold text-dark mb-1"><?php echo $sr('shifts.pg_empty_title'); ?></h6>
      <p class="small mb-0"><?php echo $sr('shifts.pg_empty_text'); ?></p>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../layouts/footer.php'; return; ?>
<?php endif; ?>

  <!-- Active Shift Alert Banner (Live Status) -->
  <div id="activeShiftBanner" class="card shadow-sm border-0 mb-3" style="display: none; border-radius: 8px; background: linear-gradient(135deg, #065f46 0%, #047857 100%); color: #ffffff;">
    <div class="card-body p-3 p-md-3.5">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div class="mb-3 mb-md-0">
          <div class="d-flex align-items-center mb-1">
            <span class="badge badge-light text-success font-weight-bold px-2 py-1 mr-2" style="font-size: 0.80rem; border-radius: 6px;">
              <i class="fas fa-dot-circle fa-beat text-success mr-1"></i> <?php echo $sr('shifts.pg_active_badge'); ?>
            </span>
            <h5 class="m-0 font-weight-bold text-white" id="activeShiftName">-</h5>
          </div>
          <div style="font-size: 0.86rem; opacity: 0.95;">
            <span><i class="fas fa-clock mr-1"></i> <?php echo $sr('shifts.pg_opened_at'); ?> <strong id="activeShiftOpenTime">-</strong></span>
            <span class="mx-2">•</span>
            <span><i class="fas fa-user mr-1"></i> <?php echo $sr('shifts.pg_opener'); ?> <strong id="activeShiftOpenedBy">-</strong></span>
            <span class="mx-2">•</span>
            <span><i class="fas fa-coins mr-1"></i> <?php echo $sr('shifts.pg_drawer_open'); ?> <strong id="activeShiftInitialCash">0 <?php echo kipSymbol(); ?></strong></span>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
          <button type="button" class="btn btn-light font-weight-bold px-3 shadow-xs d-inline-flex align-items-center" id="btnBannerViewSummary" style="border-radius: 4px !important; height: 42px;">
            <i class="fas fa-chart-pie mr-2 text-primary"></i><?php echo $sr('shifts.pg_btn_summary'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- No Active Shift Notice -->
  <div id="noActiveShiftNotice" class="card border shadow-xs mb-3" style="border-radius: 8px; background: #ffffff; border: 1.5px solid #e2e8f0 !important; display: none;">
    <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
      <div class="d-flex align-items-center">
        <div class="rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 44px; height: 44px; background: #eff6ff; border: 1.5px solid #bfdbfe;">
          <i class="fas fa-info-circle text-primary" style="font-size: 1.25rem;"></i>
        </div>
        <div>
          <strong class="text-dark d-block" style="font-size: 0.95rem;"><?php echo $sr('shifts.pg_no_active'); ?></strong>
          <small class="text-muted"><?php echo $sr('shifts.pg_next_shift'); ?> <span id="noticeNextShiftText" class="font-weight-bold text-dark">-</span></small>
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Summary Cards Grid (Shift Close Focus) -->
  <div class="kpi-cards-grid mb-3">
    <!-- 1. ຈຳນວນກະທັງໝົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo $sr('shifts.pg_kpi_shifts'); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" id="kpiTotalShifts">0</div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-calendar-check"></i></div>
    </div>

    <!-- 2. ເງິນລິ້ນຊັກເປີດລວມ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #475569 0%, #334155 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title">ເງິນລິ້ນຊັກເປີດລວມ</div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" id="kpiCloseTotalDrawer">0 <?php echo kipSymbol(); ?></div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-coins"></i></div>
    </div>

    <!-- 3. ເງິນສົດນັບໄດ້ຕົວຈິງລວມ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title">ເງິນສົດນັບໄດ້ຕົວຈິງ (ຂາຍ)</div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" id="kpiCloseTotalCounted">0 <?php echo kipSymbol(); ?></div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-money-bill-wave"></i></div>
    </div>

    <!-- 4. ສ່ວນຕ່າງເງິນສົດລວມ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);<?php echo $isCashier ? ' display: none !important;' : ''; ?>">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo $sr('shifts.pg_kpi_diff'); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" id="kpiTotalDiff">0 <?php echo kipSymbol(); ?></div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-balance-scale"></i></div>
    </div>
  </div>

  <!-- Filters Control Box -->
  <div class="report-filter-box mb-3" style="padding: 12px 16px;">
    <form onsubmit="event.preventDefault(); loadSessionsHistory();" style="display: flex; align-items: flex-end; flex-wrap: wrap; gap: 8px; width: 100%;">
      <!-- Search -->
      <div style="flex: 1.6 1 0; min-width: 190px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-search text-primary mr-1"></i> <?php echo $sr('shifts.pg_f_search'); ?>
        </label>
        <input type="text" class="form-control form-control-sm font-weight-bold" id="filterSessionSearch" autocomplete="off" placeholder="<?php echo $sr('shifts.pg_f_search_ph'); ?>" oninput="applySessionsSearch()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; width: 100%; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- From Date -->
      <div style="flex: 1 1 0; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo $sr('shifts.pg_f_from'); ?>
        </label>
        <input type="date" class="form-control form-control-sm font-weight-bold" id="filterDateFrom" value="<?php echo date('Y-m-01'); ?>" onchange="loadSessionsHistory()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; width: 100%; border: 1.5px solid #cbd5e1;">
      </div>

      <!-- To Date -->
      <div style="flex: 1 1 0; min-width: 140px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo $sr('shifts.pg_f_to'); ?>
        </label>
        <input type="date" class="form-control form-control-sm font-weight-bold" id="filterDateTo" value="<?php echo date('Y-m-d'); ?>" onchange="loadSessionsHistory()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; width: 100%; border: 1.5px solid #cbd5e1;">
      </div>

      <?php if ($shBranchPick): ?>
      <!-- Branch Select -->
      <div style="flex: 1.2 1 0; min-width: 160px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo $sr('shifts.pg_f_branch'); ?>
        </label>
        <select class="form-control form-control-sm font-weight-bold" id="filterStoreSelect" data-default="<?php echo $shDefaultBranch; ?>" onchange="onSessionsBranchChange()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; border: 1.5px solid #059669; color: #047857; background: #ecfdf5; width: 100%;">
          <?php foreach ($shBranches as $b): ?>
            <option value="<?php echo (int)$b['store_id']; ?>" <?php echo (int)$b['store_id'] === $shDefaultBranch ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['store_name']); ?></option>
          <?php endforeach; ?>
          <option value="0"><?php echo $sr('shifts.pg_f_all_branches'); ?></option>
        </select>
      </div>
      <?php endif; ?>

      <!-- Shift Select -->
      <div style="flex: 1.2 1 0; min-width: 160px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-business-time text-primary mr-1"></i> <?php echo $sr('shifts.pg_f_shift'); ?>
        </label>
        <select class="form-control form-control-sm font-weight-bold" id="filterShiftSelect" onchange="loadSessionsHistory()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value="0"><?php echo $sr('shifts.pg_f_all_shifts'); ?></option>
        </select>
      </div>

      <!-- Page Size Select -->
      <div style="flex: 0 0 96px; width: 96px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-list-ol text-primary mr-1"></i> <?php echo $sr('shifts.pg_f_show'); ?>
        </label>
        <select class="form-control form-control-sm font-weight-bold" id="filterPageSizeSelect" onchange="changeSessionsPageSize()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="all"><?php echo $sr('shifts.pg_f_all'); ?></option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex align-items-center flex-wrap" style="flex: 0 1 auto; gap: 6px;">
        <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" onclick="loadSessionsHistory()" style="border-radius: 4px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo $sr('shifts.pg_btn_search'); ?>
        </button>
        <button type="button" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" onclick="resetShiftFilters()" style="border-radius: 4px; height: 38px;" title="<?php echo $sr('shifts.pg_btn_reset'); ?>">
          <i class="fas fa-redo"></i>
        </button>
      </div>
    </form>
  </div>

  <!-- Shift Close History Table Card -->
  <div class="report-table-card card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden; max-width: 100%;">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.85rem; min-width: 980px;">
        <thead>
          <tr>
            <th class="text-center" style="width: 45px;">#</th>
            <th><?php echo $sr('shifts.pg_th_branch'); ?></th>
            <th><?php echo $sr('shifts.pg_th_name'); ?></th>
            <th><?php echo $sr('shifts.pg_th_time'); ?></th>
            <th><?php echo $sr('shifts.pg_th_people'); ?></th>
            <th class="text-right"><?php echo $sr('shifts.pg_th_drawer'); ?></th>
            <th class="text-right"><?php echo $sr('shifts.pg_th_cash'); ?></th>
            <th class="text-right"><?php echo $sr('shifts.pg_th_counted'); ?></th>
            <th class="text-center"><?php echo $sr('shifts.pg_th_diff'); ?></th>
            <th class="text-center"><?php echo $sr('shifts.pg_th_status'); ?></th>
            <th class="text-center" style="width: 90px;">ໃບສະຫຼຸບປິດກະ</th>
          </tr>
        </thead>
        <tbody id="sessionsTableBody">
          <tr>
            <td colspan="11" class="p-0">
              <div class="d-flex flex-column align-items-center justify-content-center py-5">
                <div class="system-loader-wrapper"><div class="system-loader-pulse-ring"></div>
                  <img src="<?php echo $base_path; ?>assets/img/logosystem/Wlaodev.png?v=<?php echo @filemtime(__DIR__ . '/../../assets/img/logosystem/Wlaodev.png') ?: 1; ?>" alt="WLaodev" class="system-loader-logo"></div>
                <span class="preloader-text"><span><?php echo $sr('shifts.rpt_loading'); ?></span><span class="loader-dot d1">.</span><span class="loader-dot d2">.</span><span class="loader-dot d3">.</span></span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Table Pagination Footer -->
    <div class="card-footer bg-white border-top d-flex align-items-center justify-content-between py-2.5 px-3 flex-wrap" style="gap: 10px; border-radius: 0 0 8px 8px;">
      <div class="text-muted font-weight-bold" style="font-size: 0.85rem;">
        <?php echo $sr('shifts.pg_showing'); ?> <span id="sessionsPageInfoStart" class="text-dark">0</span> - <span id="sessionsPageInfoEnd" class="text-dark">0</span> <?php echo $sr('shifts.pg_of_total'); ?> <span id="sessionsPageInfoTotal" class="text-primary font-weight-bold">0</span> <?php echo $sr('shifts.pg_items'); ?>
      </div>
      <nav aria-label="Sessions Pagination" style="margin-left: auto;">
        <ul class="pagination report-pagination mb-0 justify-content-end" id="sessionsPagination"></ul>
      </nav>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../settings/shifts/partials/modals.php'; ?>
<?php require_once __DIR__ . '/../settings/shifts/partials/js.php'; ?>
<?php if (!empty($_GET['open_session'])): ?>
<script>
// ມາຈາກແຈ້ງເຕືອນປິດກະ (home/partials/dashboard/js_shift_close_notif.php) → ເປີດໃບສະຫຼຸບ + ລາຍການຂາຍຂອງກະນັ້ນທັນທີ
$(function () { openShiftReportModal(<?php echo (int)$_GET['open_session']; ?>); });
</script>
<?php endif; ?>
<?php
$shStoreStmt = $pdo->prepare("SELECT store_name FROM st_tbstore WHERE store_id = ?");
$shStoreStmt->execute([getActiveStoreId($pdo)]);
$shStoreName = (string)($shStoreStmt->fetchColumn() ?: '');
$shPrintedBy = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? '');
?>
<script src="<?php echo $base_path; ?>assets/js/report-export.js?v=<?php echo @filemtime(__DIR__ . '/../../assets/js/report-export.js'); ?>"></script>
<script>
RptExport.config({ storeName: <?php echo json_encode($shStoreName, JSON_UNESCAPED_UNICODE); ?>, printedBy: <?php echo json_encode($shPrintedBy, JSON_UNESCAPED_UNICODE); ?> });
window.rptExport = function (action) {
  var list = window.allSessionsData || [];
  var num = function (v) { return formatNumber(parseFloat(v) || 0); };
  var sum = { init: 0, cash: 0, counted: 0, diff: 0 };
  var rows = list.map(function (s, i) {
    var d = s.difference_cash !== null ? parseFloat(s.difference_cash) || 0 : null;
    sum.init += parseFloat(s.initial_cash) || 0;
    sum.cash += parseFloat(s.system_sales_cash) || 0;
    if (s.counted_sales_cash !== null) sum.counted += parseFloat(s.counted_sales_cash) || 0;
    if (d !== null) sum.diff += d;
    return [i + 1, s.store_name || '-', s.shift_name || '-', s.open_time || '-', s.close_time || 'ກຳລັງຂາຍ',
            s.opened_by_name || '-', s.closed_by_name || '-',
            num(s.initial_cash), num(s.system_sales_cash),
            s.counted_sales_cash !== null ? num(s.counted_sales_cash) : '-',
            d === null ? '-' : (d > 0 ? '+' : '') + num(d),
            s.status === 'open' ? 'ກຳລັງເປີດ' : 'ປິດແລ້ວ'];
  });
  var shiftSel = $('#filterShiftSelect option:selected');
  var q = ($('#filterSessionSearch').val() || '').trim();
  var fmtD = function (v) { var p = String(v || '').split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : v; };
  var storeSel = $('#filterStoreSelect option:selected');
  RptExport.run(action, {
    title: 'ລາຍງານປິດກະເຮັດວຽກ',
    fileBase: 'shift_close_report',
    store: storeSel.length ? (storeSel.val() === '0' ? 'ທຸກສາຂາ' : storeSel.text()) : undefined,
    meta: ['ວັນທີ ' + fmtD($('#filterDateFrom').val()) + ' - ' + fmtD($('#filterDateTo').val()),
           shiftSel.val() !== '0' ? 'ກະ: ' + shiftSel.text() : 'ທຸກກະ',
           q ? 'ຄົ້ນຫາ: ' + q : '',
           'ທັງໝົດ ' + rows.length + ' ກະ', 'ສະກຸນເງິນ: LAK'].filter(Boolean),
    headers: [{t: 'ລຳດັບ', a: 'c'}, {t: 'ສາຂາ'}, {t: 'ຊື່ກະ'}, {t: 'ເວລາເປີດ'}, {t: 'ເວລາປິດ'}, {t: 'ຜູ້ເປີດ'}, {t: 'ຜູ້ປິດ'},
              {t: 'ເງິນລິ້ນຊັກຕອນເປີດ', a: 'r'}, {t: 'ຂາຍເງິນສົດ (ລະບົບ)', a: 'r'},
              {t: 'ເງິນສົດນັບໄດ້ຕົວຈິງ', a: 'r'}, {t: 'ສ່ວນຕ່າງ', a: 'r'}, {t: 'ສະຖານະ', a: 'c'}],
    rows: rows,
    footer: rows.length ? ['', 'ລວມ', '', '', '', '', '', num(sum.init), num(sum.cash),
                           num(sum.counted), (sum.diff > 0 ? '+' : '') + num(sum.diff), ''] : null
  });
};
</script>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
