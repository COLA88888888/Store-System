<?php
// Standalone Page: ລາຍງານການເງິນ (Financial Summary Report)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('financial') && !hasPermission('report') && ($_SESSION['status'] ?? '') !== 'ຜູ້ບໍລິຫານ') {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$from_date    = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date      = trim($_GET['to_date'] ?? date('Y-m-d'));
$search       = trim($_GET['search'] ?? '');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : 0;
$page         = max(1, intval($_GET['page'] ?? 1));
$per_page_raw = trim($_GET['per_page'] ?? '10');

// Fetch all active branches for selection
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check store_id / paid_currency columns
$hasStoreIdCol  = false;
$hasCurrencyCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol  = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName   = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
    $hasCurrencyCol = in_array('paid_currency', $saleCols);
} catch (Exception $e) {}

if ($per_page_raw === 'all') {
    $per_page = 999999;
} else {
    $per_page = max(1, intval($per_page_raw));
}

// SQL Filter Conditions — cancelled bills must not be counted in the money totals
// (they have their own report: ປະຫວັດການລົບບິນຂາຍ / delete_bills)
$where   = ["(s.sale_status IS NULL OR s.sale_status != 'CANCEL')"];
$params  = [];

if (!empty($from_date)) {
    $where[] = "s.sale_date >= :from_date";
    $params[':from_date'] = $from_date;
}
if (!empty($to_date)) {
    $where[] = "s.sale_date <= :to_date";
    $params[':to_date'] = $to_date;
}
if (!empty($search)) {
    $where[] = "(s.sale_save_bill LIKE :search OR s.user_receive LIKE :search OR s.type_pay LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($filter_store_id > 0 && $hasStoreIdCol) {
    $where[] = "s.{$storeColName} = :filter_store_id";
    $params[':filter_store_id'] = $filter_store_id;
}

// Currency Filter — which currency the customer actually tendered on the bill. Once
// applied, every money figure on the page switches to show in that currency instead
// of the store's default, via setDisplayCurrencyOverride().
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && $hasCurrencyCol && isset(getCurrencyMeta()[$currency_filter])) {
    $where[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
    $params[':currency_filter'] = $currency_filter;

    $__curRates = getExchangeRates($pdo);
    $__curRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__curRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__curRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__curRate, 'rates' => $__curRates],
            getCurrencyMeta()[$currency_filter]
        ));
    }
} else {
    $currency_filter = '';
}

$whereClause = implode(" AND ", $where);

// Check if cash_received / qr_received columns exist dynamically
$hasCashCol = !empty($pdo->query("SHOW COLUMNS FROM tbsale_save LIKE 'cash_received'")->fetch());
$hasQrCol   = !empty($pdo->query("SHOW COLUMNS FROM tbsale_save LIKE 'qr_received'")->fetch());

// Count Total Financial Records
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tbsale_save s WHERE {$whereClause}");
$stmtCount->execute($params);
$total_records = (int)$stmtCount->fetchColumn();

$total_pages = max(1, ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Fetch Paginated Financial Records
$sql = "
    SELECT s.* 
    FROM tbsale_save s
    WHERE {$whereClause}
    ORDER BY s.sale_date DESC, s.sale_time DESC, s.Id DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$finances = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="row-gap: 10px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-wallet text-info mr-2"></i> <?php echo htmlspecialchars(t('reports.title_financial', 'ລາຍງານການເງິນ')); ?>
      </h5>
    </div>

    <!-- Export & Print Buttons (Excel, PDF, Print) -->
    <div class="d-flex align-items-center justify-content-end ml-auto no-print" style="gap: 5px;">
      <button type="button" onclick="exportReportExcel('all')" class="btn btn-xs btn-success font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
        <i class="fas fa-file-excel mr-1" style="font-size: 0.8rem;"></i> Excel
      </button>
      <button type="button" onclick="exportReportPDF('all')" class="btn btn-xs btn-danger font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #dc2626, #b91c1c); border: none;">
        <i class="fas fa-file-pdf mr-1" style="font-size: 0.8rem;"></i> PDF
      </button>
      <button type="button" onclick="printReportTable('all')" class="btn btn-xs btn-primary font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
        <i class="fas fa-print mr-1" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars(t('reports.print_btn', 'ພິມ')); ?>
      </button>
    </div>
  </div>

  <!-- Search & Filter Bar -->
  <div class="report-filter-box no-print mb-3.5" style="padding: 12px 16px;">
    <form method="GET" action="financial.php" class="d-flex flex-column flex-md-row align-items-md-end flex-wrap" style="gap: 12px;">
      <!-- Per Page Dropdown (ໂຊລາຍການ - ໜ້າສຸດ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-layer-group text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.per_page_label', 'ໂຊລາຍການ:')); ?>
        </label>
        <select name="per_page" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value="5" <?php echo $per_page_raw === '5' ? 'selected' : ''; ?>>5</option>
          <option value="10" <?php echo $per_page_raw === '10' ? 'selected' : ''; ?>>10</option>
          <option value="25" <?php echo $per_page_raw === '25' ? 'selected' : ''; ?>>25</option>
          <option value="50" <?php echo $per_page_raw === '50' ? 'selected' : ''; ?>>50</option>
          <option value="100" <?php echo $per_page_raw === '100' ? 'selected' : ''; ?>>100</option>
          <option value="all" <?php echo $per_page_raw === 'all' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_opt', 'ທັງໝົດ')); ?></option>
        </select>
      </div>

      <!-- From Date (ຕັ້ງແຕ່ວັນທີ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- To Date (ຫາວັນທີ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Branch Filter (ເລືອກສາຂາ) — only worth showing once there's more than one branch -->
      <?php if (count($branchesList) > 1): ?>
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; width: 100%;" onchange="this.form.submit()">
          <option value="0"><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

      <!-- Search Input (ຄົ້ນຫາ ເລກບິນ / ຊື່) -->
      <div style="flex: 1 1 180px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-search text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.search_bill_seller_label', 'ຄົ້ນຫາ (ເລກບິນ / ຊື່):')); ?>
        </label>
        <input type="text" name="search" id="financialSearchInput" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(t('reports.search_placeholder_bill_emp', 'ປ້ອນເລກບິນ ຫຼື ຊື່ພະນັກງານ...')); ?>" value="<?php echo htmlspecialchars($search); ?>" oninput="debounceSubmit(this.form)" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Currency Filter (ສະກຸນເງິນ) -->
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.all_currencies_opt', '-- ທຸກສະກຸນເງິນ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($__ci['name'] . ' (' . $__ci['symbol'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action Buttons (ຄົ້ນຫາ & ຣີໂຫລດ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block d-md-none" style="font-size: 0.82rem; visibility: hidden;">&nbsp;</label>
        <div class="d-flex align-items-center" style="gap: 8px; width: 100%;">
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); flex: 1;">
            <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
          </button>
          <a href="financial.php" class="btn btn-light btn-sm border font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px;" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Financial Table Card (11 Columns) -->
  <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
      <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.88rem;">
          <thead style="background-color: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;">
            <tr style="background: #ffffff; color: #1e293b;">
              <th class="text-center py-3" style="width: 50px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
              <th class="py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_bill_no', 'ເລກບິນ')); ?></th>
              <th class="py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_date_alt', 'ວັນທີ່')); ?></th>
              <th class="text-center py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_cash', 'ເງິນສົດ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.transfer_opt', 'ເງິນໂອນ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_change', 'ເງິນທອນ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_received', 'ຮັບຈິງ')); ?></th>
              <th class="text-center py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_status', 'ສະຖານະ')); ?></th>
              <th class="text-center py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_time', 'ເວລາ')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($finances)): ?>
              <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                  <i class="fas fa-wallet fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                  <?php echo htmlspecialchars(t('reports.no_financial_data', 'ບໍ່ພົບຂໍ້ມູນລາຍງານການເງິນ')); ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($finances as $idx => $row): ?>
                <?php
                  $net     = floatval($row['sale_barlance'] ?? 0);
                  $pay     = floatval($row['sale_pay'] ?? 0);
                  $ret     = floatval($row['sale_return'] ?? 0);
                  $typePay = $row['type_pay'] ?? '';

                  if ($hasCashCol && $hasQrCol && (floatval($row['cash_received'] ?? 0) > 0 || floatval($row['qr_received'] ?? 0) > 0)) {
                      $cash = floatval($row['cash_received']);
                      $qr   = floatval($row['qr_received']);
                  } else {
                      if (mb_strpos($typePay, 'ໂອນ') !== false || mb_strpos($typePay, 'QR') !== false) {
                          if (mb_strpos($typePay, 'ເງິນສົດ') !== false) {
                              $cash = max(0, $pay - $ret);
                              $qr   = max(0, $net - $cash);
                          } else {
                              $cash = 0;
                              $qr   = $net;
                          }
                      } else {
                          $cash = $net;
                          $qr   = 0;
                      }
                  }

                  $change  = max(0, $ret);
                  $received = max(0, $pay);
                  $status  = $row['sale_status'] ?? 'SUCCESS';
                  $time    = substr($row['sale_time'] ?? '00:00', 0, 5);
                  $dateFormatted = date('d/m/Y', strtotime($row['sale_date']));
                ?>
                <tr>
                  <td class="text-center font-weight-bold text-muted"><?php echo $offset + $idx + 1; ?></td>
                  <td class="font-weight-bold text-primary"><?php echo htmlspecialchars($row['sale_save_bill']); ?></td>
                  <td class="text-left" style="font-size: 0.84rem; color: #475569;"><?php echo $dateFormatted; ?></td>
                  <td class="text-center">
                    <?php $billDiscount = floatval($row['sale_discount_bill'] ?? 0); ?>
                    <?php if ($billDiscount > 0): ?>
                      <span class="badge badge-danger font-weight-bold px-2 py-1" style="font-size: 0.76rem; border-radius: 6px;">
                        -<?php echo displayMoney($pdo, $billDiscount); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-right font-weight-bold text-dark"><?php echo displayMoney($pdo, $cash); ?></td>
                  <td class="text-right font-weight-bold text-primary"><?php echo displayMoney($pdo, $qr); ?></td>
                  <td class="text-right font-weight-bold text-info"><?php echo displayMoney($pdo, $change); ?></td>
                  <td class="text-right font-weight-bold text-success" style="font-size: 0.94rem;"><?php echo displayMoney($pdo, $received); ?></td>
                  <td class="text-center font-weight-bold">
                    <?php if ($status === 'CANCEL'): ?>
                      <span class="badge badge-danger px-2 py-1" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('reports.status_cancel', 'ຍົກເລີກ')); ?></span>
                    <?php else: ?>
                      <span class="badge badge-success px-2 py-1" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('reports.status_paid', 'ຈ່າຍແລ້ວ')); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center font-weight-bold text-dark" style="font-size: 0.84rem;"><?php echo $time; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($finances)): ?>
            <tfoot style="background: #f8fafc; border-top: 2px solid #cbd5e1;">
              <tr class="font-weight-bold" style="font-size: 0.9rem; color: #0f172a;">
                <td colspan="4" class="text-center py-3 text-uppercase font-weight-bold" style="background: #f8fafc; color: #0f172a;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
                <td class="text-right py-3 text-dark font-weight-bold" style="background: #f8fafc; color: #0f172a;" id="tot_cash">0 ₭</td>
                <td class="text-right py-3 text-primary font-weight-bold" style="background: #f8fafc;" id="tot_qr">0 ₭</td>
                <td class="text-right py-3 text-info font-weight-bold" style="background: #f8fafc;" id="tot_change">0 ₭</td>
                <td class="text-right py-3 text-success font-weight-bold" style="background: #f8fafc; font-size: 0.96rem;" id="tot_rec">0 ₭</td>
                <td colspan="2" style="background: #f8fafc;"></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Table Card Footer: Circular Blue Pagination Aligned Right -->
    <div class="card-footer bg-white border-top py-3 px-3.5 d-flex flex-column flex-md-row justify-content-between align-items-center no-print" style="row-gap: 12px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">

      <nav aria-label="Page navigation" class="ml-auto">
        <ul class="pagination report-pagination mb-0">
          <!-- Previous Page -->
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="financial.php?page=<?php echo max(1, $page - 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
            </a>
          </li>

          <?php
            $range = 2;
            $startP = max(1, $page - $range);
            $endP   = min($total_pages, $page + $range);

            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="financial.php?page=1&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">1</a></li>';
                if ($startP > 2) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
            }

            for ($p = $startP; $p <= $endP; $p++) {
                $activeClass = ($p == $page) ? 'active' : '';
                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="financial.php?page=' . $p . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $p . '</a></li>';
            }

            if ($endP < $total_pages) {
                if ($endP < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="financial.php?page=' . $total_pages . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $total_pages . '</a></li>';
            }
          ?>

          <!-- Next Page -->
          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="financial.php?page=<?php echo min($total_pages, $page + 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('financialSearchInput');
    const table = document.querySelector('.table');
    
    function calculateTotals() {
        let sumCash = 0, sumQr = 0, sumChange = 0, sumRec = 0;
        const rows = table.querySelectorAll('tbody tr:not(#noSearchResultRow)');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 10) {
                    sumCash += parseFloat(cells[4].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                    sumQr += parseFloat(cells[5].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                    sumChange += parseFloat(cells[6].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                    sumRec += parseFloat(cells[7].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                }
            }
        });
        const elCash = document.getElementById('tot_cash');
        const elQr = document.getElementById('tot_qr');
        const elChange = document.getElementById('tot_change');
        const elRec = document.getElementById('tot_rec');

        // repMoney() returns markup (<span class="cur-sym">…</span>), so assign as HTML
        if (elCash) elCash.innerHTML = repMoney(sumCash);
        if (elQr) elQr.innerHTML = repMoney(sumQr);
        if (elChange) elChange.innerHTML = repMoney(sumChange);
        if (elRec) elRec.innerHTML = repMoney(sumRec);
    }

    if (searchInput && table) {
        calculateTotals();
        searchInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr:not(#noSearchResultRow)');
            let visibleCount = 0;

            if (val === '') {
                rows.forEach(row => row.style.display = '');
                const oldNoRow = document.getElementById('noSearchResultRow');
                if (oldNoRow) oldNoRow.remove();
                calculateTotals();
                return;
            }

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(val)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            let noRow = document.getElementById('noSearchResultRow');
            if (visibleCount === 0 && rows.length > 0) {
                if (!noRow) {
                    noRow = document.createElement('tr');
                    noRow.id = 'noSearchResultRow';
                    const colCount = table.querySelectorAll('thead th').length || 9;
                    noRow.innerHTML = `<td colspan="${colCount}" class="text-center py-5 text-muted font-weight-bold" style="font-size: 0.95rem;">
                        <i class="fas fa-exclamation-circle fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                        <?php echo htmlspecialchars(t('reports.no_search_match', 'ບໍ່ພົບຂໍ້ມູນທີ່ຕົງກັບຄຳຄົ້ນຫາ'), ENT_QUOTES); ?>
                    </td>`;
                    table.querySelector('tbody').appendChild(noRow);
                }
            } else {
                if (noRow) noRow.remove();
            }
            calculateTotals();
        });
    } else if (table) {
        calculateTotals();
    }
});

function viewBillDetails(billId) {
    if (typeof window.showBillDetailModal === 'function') {
        window.showBillDetailModal(billId);
    } else {
        alert('ເລກບິນ: ' + billId);
    }
}
</script>

<!-- html2pdf & Shared Report JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
