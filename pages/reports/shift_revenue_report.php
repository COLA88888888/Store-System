<?php
/**
 * ໜ້າລາຍງານລາຍຮັບຕາມກະ (Work Shift Sales & Revenue Report)
 * ສະຫຼຸບລາຍຮັບຍອດຂາຍ, ຊ່ອງທາງການຊຳລະເງິນ ແລະ ຈຳນວນບິນຂາຍຕາມແຕ່ລະກະ
 */
session_start();
$base_path = '../../';
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id']) || !hasPermission('shift_revenue_report')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$shHasShifts = branchHasShifts($pdo, intval($_SESSION['store_id'] ?? 0));

$userStatus = $_SESSION['status'] ?? '';
$isAdmin = (intval($_SESSION['user_id'] ?? 0) === 1 || $userStatus === 'ຜູ້ບໍລິຫານ' || strtolower($userStatus) === 'admin' || strtolower($userStatus) === 'super admin');
$isCashier = ($userStatus === 'ພະນັກງານ' && !$isAdmin);

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
/* ປຸ່ມລາຍລະອຽດ: ກົດທັບກົດເກນລວມ ".table td .btn:hover { background: transparent }" (02-components.css)
   ທີ່ເຮັດໃຫ້ພື້ນໂປ່ງໃສ ແຕ່ outline-success ປ່ຽນໄອຄອນເປັນສີຂາວ → ເມົາສ໌ຖືກແລ້ວເປັນສີຂາວໝົດ */
.table td .btn.shift-detail-btn:hover,
.table td .btn.shift-detail-btn:focus {
    background: #16a34a !important;
    border-color: #16a34a !important;
    color: #ffffff !important;
    opacity: 1 !important;
}
</style>

<script>
window.IS_CASHIER_USER = <?php echo $isCashier ? 'true' : 'false'; ?>;
window.IS_ADMIN_USER = <?php echo $isAdmin ? 'true' : 'false'; ?>;
window.SHIFT_REPORT_MODE = 'revenue';
</script>

<div class="container-fluid p-3 p-md-4">

  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-chart-line mr-2" style="color: #059669;"></i> <?php echo htmlspecialchars(t('layout.shift_revenue_report', 'ລາຍງານລາຍຮັບຕາມກະ')); ?>
      </h5>
      <small class="text-muted" style="font-size: 0.82rem;"><?php echo tlh('ສະຫຼຸບລາຍຮັບຍອດຂາຍ, ຊ່ອງທາງການຊຳລະເງິນ ແລະ ຈຳນວນບິນຂາຍຕາມແຕ່ລະກະ'); ?></small>
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

  <!-- KPI Summary Cards Grid (Revenue by Shift Focus) -->
  <div class="kpi-cards-grid mb-3">
    <!-- 1. ຍອດລາຍຮັບລວມທຸກກະ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo tlh('ຍອດລາຍຮັບລວມທຸກກະ'); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" id="kpiRevTotalSales">0 <?php echo kipSymbol(); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-coins"></i></div>
    </div>

    <!-- 2. ຍອດຂາຍເງິນສົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo tlh('ຍອດຂາຍເງິນສົດ'); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" id="kpiRevCashSales">0 <?php echo kipSymbol(); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-money-bill-wave"></i></div>
    </div>

    <!-- 3. ຍອດຂາຍໂອນ / QR -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo tlh('ຍອດຂາຍໂອນ'); ?> / QR</div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" id="kpiRevTransferSales">0 <?php echo kipSymbol(); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-qrcode"></i></div>
    </div>

    <!-- 4. ຈຳນວນບິນຂາຍທັງໝົດ -->
    <div class="kpi-card-item d-flex align-items-center justify-content-between text-white" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
      <div style="z-index: 2; min-width: 0;">
        <div class="kpi-card-title"><?php echo tlh('ຈຳນວນບິນຂາຍທັງໝົດ'); ?></div>
        <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <span class="counter-num" id="kpiRevTotalBills">0 <?php echo tlh('ບິນ'); ?></span>
        </div>
      </div>
      <div class="kpi-card-icon"><i class="fas fa-file-invoice-dollar"></i></div>
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
        <input type="text" class="form-control form-control-sm font-weight-bold" id="filterSessionSearch" autocomplete="off" placeholder="<?php echo tlh('ຄົ້ນຫາຊື່ກະ, ພະນັກງານ, ວັນທີ...'); ?>" oninput="applySessionsSearch()" style="border-radius: 4px; height: 38px; font-size: 0.85rem; width: 100%; border: 1.5px solid #cbd5e1;">
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
        <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" onclick="loadSessionsHistory()" style="border-radius: 4px; height: 38px; background: linear-gradient(135deg, #059669, #047857); border: none; white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1.5"></i> <?php echo $sr('shifts.pg_btn_search'); ?>
        </button>
        <button type="button" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2.5" onclick="resetShiftFilters()" style="border-radius: 4px; height: 38px;" title="<?php echo $sr('shifts.pg_btn_reset'); ?>">
          <i class="fas fa-redo"></i>
        </button>
      </div>
    </form>
  </div>

  <!-- Shift Revenue Table Card -->
  <div class="report-table-card card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden; max-width: 100%;">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.85rem; min-width: 980px;">
        <thead>
          <tr>
            <th class="text-center" style="width: 45px;">#</th>
            <th><?php echo $sr('shifts.pg_th_branch'); ?></th>
            <th><?php echo $sr('shifts.pg_th_name'); ?></th>
            <th><?php echo tlh('ວັນທີ - ເວລາຂາຍ'); ?></th>
            <th><?php echo tlh('ພະນັກງານຂາຍ'); ?></th>
            <th class="text-center" style="width: 80px;"><?php echo tlh('ຈຳນວນບິນ'); ?></th>
            <th class="text-right"><?php echo $sr('shifts.pg_th_cash'); ?></th>
            <th class="text-right"><?php echo $sr('shifts.pg_th_transfer'); ?></th>
            <th class="text-right"><?php echo tlh('ຂາຍຕິດໜີ້'); ?></th>
            <th class="text-right"><?php echo tlh('ຍອດລາຍຮັບລວມ'); ?></th>
            <th class="text-center" style="width: 90px;"><?php echo tlh('ລາຍລະອຽດ'); ?></th>
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
  var sum = { bills: 0, cash: 0, trf: 0, debt: 0, total: 0 };
  var rows = list.map(function (s, i) {
    sum.bills += parseInt(s.system_total_bills) || 0;
    sum.cash += parseFloat(s.system_sales_cash) || 0;
    sum.trf += parseFloat(s.system_sales_transfer) || 0;
    sum.debt += parseFloat(s.system_sales_debt) || 0;
    sum.total += parseFloat(s.system_total_sales) || 0;
    var trfText = num(s.system_sales_transfer);
    if (s.transfer_banks_text) trfText += ' (' + s.transfer_banks_text + ')';
    return [i + 1, s.store_name || '-', s.shift_name || '-', (s.open_time || '-') + ' - ' + (s.close_time || '<?php echo tlj('ກຳລັງຂາຍ'); ?>'),
            s.staff_names || s.opened_by_name || '-',
            num(s.system_total_bills),
            num(s.system_sales_cash), trfText, num(s.system_sales_debt),
            num(s.system_total_sales)];
  });
  var shiftSel = $('#filterShiftSelect option:selected');
  var q = ($('#filterSessionSearch').val() || '').trim();
  var fmtD = function (v) { var p = String(v || '').split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : v; };
  var storeSel = $('#filterStoreSelect option:selected');
  RptExport.run(action, {
    title: '<?php echo tlj('ລາຍງານລາຍຮັບຕາມກະ'); ?>',
    fileBase: 'shift_revenue_report',
    store: storeSel.length ? (storeSel.val() === '0' ? '<?php echo tlj('ທຸກສາຂາ'); ?>' : storeSel.text()) : undefined,
    meta: ['<?php echo tlj('ວັນທີ'); ?> ' + fmtD($('#filterDateFrom').val()) + ' - ' + fmtD($('#filterDateTo').val()),
           shiftSel.val() !== '0' ? '<?php echo tlj('ກະ:'); ?> ' + shiftSel.text() : '<?php echo tlj('ທຸກກະ'); ?>',
           q ? '<?php echo tlj('ຄົ້ນຫາ:'); ?> ' + q : '',
           '<?php echo tlj('ທັງໝົດ'); ?> ' + rows.length + ' <?php echo tlj('ກະ'); ?>', '<?php echo tlj('ສະກຸນເງິນ:'); ?> LAK'].filter(Boolean),
    headers: [{t: '<?php echo tlj('ລຳດັບ'); ?>', a: 'c'}, {t: '<?php echo tlj('ສາຂາ'); ?>'}, {t: '<?php echo tlj('ຊື່ກະ'); ?>'}, {t: '<?php echo tlj('ວັນທີ-ເວລາຂາຍ'); ?>'}, {t: '<?php echo tlj('ພະນັກງານຂາຍ'); ?>'},
              {t: '<?php echo tlj('ຈຳນວນບິນ'); ?>', a: 'c'}, {t: '<?php echo tlj('ຂາຍເງິນສົດ'); ?>', a: 'r'}, {t: '<?php echo tlj('ຂາຍໂອນ'); ?>/QR', a: 'r'}, {t: '<?php echo tlj('ຂາຍຕິດໜີ້'); ?>', a: 'r'},
              {t: '<?php echo tlj('ຍອດລາຍຮັບລວມ'); ?>', a: 'r'}],
    rows: rows,
    footer: rows.length ? ['', '<?php echo tlj('ລວມ'); ?>', '', '', '', num(sum.bills), num(sum.cash), num(sum.trf), num(sum.debt), num(sum.total)] : null
  });
};
</script>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
