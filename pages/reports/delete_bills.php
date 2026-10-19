<?php
// Standalone Page: ປະຫວັດການລົບບິນຂາຍ (Deleted Bills History Report)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('delete_bills') && !hasPermission('report')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// Auto-ensure ip_address column exists in tbsale_save
try {
    $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN ip_address VARCHAR(45) NULL AFTER customer_name");
} catch (Exception $e) {}

$from_date     = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date       = trim($_GET['to_date'] ?? date('Y-m-d'));
$search        = trim($_GET['search'] ?? '');
$employee_name = trim($_GET['employee_name'] ?? '');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}
$page          = max(1, intval($_GET['page'] ?? 1));
$per_page_raw  = trim($_GET['per_page'] ?? '10');

// Fetch all active branches
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check store_id / paid_currency columns
$hasStoreIdCol  = false;
$hasCurrencyCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol  = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName   = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
    $hasCurrencyCol = in_array('paid_currency', $saleCols);
} catch (Exception $e) {}

if ($per_page_raw === 'all') {
    $per_page = 999999;
} else {
    $per_page = max(1, intval($per_page_raw));
}

// Query Deleted / Cancelled Bills
$where   = ["s.sale_status = 'CANCEL'"];
$params  = [];

if (!empty($from_date)) {
    $where[] = "s.sale_date >= :from_date";
    $params[':from_date'] = $from_date;
}
if (!empty($to_date)) {
    $where[] = "s.sale_date <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($filter_store_id > 0 && $hasStoreIdCol) {
    $where[] = "s.{$storeColName} = :filter_store_id";
    $params[':filter_store_id'] = $filter_store_id;
}
if (!empty($search)) {
    $where[] = "(s.sale_save_bill LIKE :search OR u.username LIKE :search OR u.fname LIKE :search OR u.lname LIKE :search OR s.user_receive LIKE :search)";
    $params[':search'] = '%' . $search . '%';
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

// Count Total Deleted Bills (whole filtered set, not just this page) — drives pagination
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) AS cnt
    FROM st_tbsale_save s
    LEFT JOIN st_tbuser u ON (s.user_receive = u.Id OR s.user_receive = u.username)
    WHERE {$whereClause}
");
$stmtCount->execute($params);
$countRow = $stmtCount->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0];
$total_records      = (int)($countRow['cnt'] ?? 0);

$total_pages = max(1, ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Fetch Deleted Bills with User info & IP
$sql = "
    SELECT 
        s.*,
        COALESCE(CONCAT(IFNULL(u.fname, ''), ' ', IFNULL(u.lname, '')), u.username, s.user_receive, 'Admin') AS employee_display_name
    FROM st_tbsale_save s
    LEFT JOIN st_tbuser u ON (s.user_receive = u.Id OR s.user_receive = u.username)
    WHERE {$whereClause}
    ORDER BY s.sale_date DESC, s.sale_time DESC, s.Id DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deleted_bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/partials/reports_modal.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-history text-danger mr-2"></i> <?php echo htmlspecialchars(t('reports.title_delete_bills', 'ປະຫວັດການລົບບິນຂາຍ')); ?>
      </h5>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div class="report-filter-box no-print mb-3.5" style="padding: 12px 16px;">
    <form method="GET" action="delete_bills.php" class="d-flex flex-column flex-md-row align-items-md-end flex-wrap" style="gap: 12px;">
      <!-- Per Page Dropdown -->
      <div style="flex: 1 1 110px; width: 100%;">
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
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- To Date (ຫາວັນທີ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Branch Filter Dropdown (ເລືອກສາຂາ) — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($branchesList) > 1): ?>
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1; width: 100%;" onchange="this.form.submit()">
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ((int)$filter_store_id === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

      <!-- Combined Search Box (ຄົ້ນຫາ ເລກບິນ ຫຼື ພະນັກງານ) -->
      <div style="flex: 1 1 250px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-search text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.search_label', 'ຄົ້ນຫາ:')); ?>
        </label>
        <input type="text" name="search" id="deleteBillSearchInput" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(t('reports.search_placeholder_bill_emp', 'ປ້ອນເລກບິນ ຫຼື ຊື່ພະນັກງານ...')); ?>" value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
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

      <!-- Buttons -->
      <div style="flex: 1 1 130px; width: 100%;">
        <div class="d-flex align-items-center" style="gap: 8px; width: 100%;">
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); flex: 1;">
            <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
          </button>
          <a href="delete_bills.php" class="btn btn-light btn-sm border font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px;" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Table Card -->
  <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
      <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.88rem;">
          <thead style="background-color: #ffffff; color: #1e293b; border-bottom: 2px solid #cbd5e1;">
            <tr style="background: #ffffff; color: #1e293b;">
              <th class="text-center py-3" style="width: 60px;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
              <th class="py-3"><?php echo htmlspecialchars(t('reports.col_bill_no', 'ເລກບິນ')); ?></th>
              <th class="py-3"><?php echo htmlspecialchars(t('reports.col_deleted_by', 'ຜູ້ລົບບິນ')); ?></th>
              <th class="text-center py-3"><?php echo htmlspecialchars(t('reports.col_date', 'ວັນທີ')); ?></th>
              <th class="text-center py-3"><?php echo htmlspecialchars(t('reports.col_time', 'ເວລາ')); ?></th>
              <th class="text-right py-3"><?php echo htmlspecialchars(t('reports.col_amount_total', 'ເງິນລວມ')); ?></th>
              <th class="text-center py-3">IP</th>
              <th class="text-center py-3" style="width: 80px;"><?php echo htmlspecialchars(t('reports.detail_opt', 'ລາຍລະອຽດ')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($deleted_bills)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted font-weight-bold">
                  <i class="fas fa-trash-alt fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                  <?php echo htmlspecialchars(t('reports.no_deleted_bills_data', 'ບໍ່ພົບຂໍ້ມູນປະຫວັດບິນທີ່ຖືກລຶບ')); ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($deleted_bills as $idx => $row): ?>
                <?php
                  $gross = floatval($row['sale_amount'] ?? 0);
                  $disc  = floatval($row['sale_discount_bill'] ?? 0);
                  $net   = floatval($row['sale_barlance'] ?? max(0, $gross - $disc));
                  $date_formatted = !empty($row['sale_date']) ? date('d/m/Y', strtotime($row['sale_date'])) : '-';
                  $time_formatted = !empty($row['sale_time']) ? substr($row['sale_time'], 0, 8) : '-';
                  $client_ip = !empty($row['ip_address']) ? htmlspecialchars($row['ip_address']) : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                  $bill_no   = htmlspecialchars($row['sale_save_bill']);
                ?>
                <tr>
                  <td class="text-center font-weight-bold text-muted"><?php echo $offset + $idx + 1; ?></td>
                  <td class="font-weight-bold text-danger" style="font-size: 0.92rem;">
                    <i class="fas fa-file-invoice text-danger mr-1"></i>
                    <?php echo $bill_no; ?>
                  </td>
                  <td class="font-weight-bold text-dark" style="font-size: 0.9rem;">
                    <i class="fas fa-user-circle text-primary mr-1"></i>
                    <?php echo htmlspecialchars($row['employee_display_name']); ?>
                  </td>
                  <td class="text-center font-weight-bold text-dark"><?php echo $date_formatted; ?></td>
                  <td class="text-center text-muted font-weight-bold"><?php echo $time_formatted; ?></td>
                  <td class="text-right font-weight-bold text-primary" style="font-size: 0.95rem;">
                    <?php echo displayMoney($pdo, $net); ?>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-light border text-dark px-2.5 py-1" style="font-family: monospace; font-size: 0.82rem; border-radius: 6px;">
                      <?php echo $client_ip; ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-info font-weight-bold px-2.5 py-1" onclick="viewBillDetails('<?php echo $bill_no; ?>')" title="<?php echo htmlspecialchars(t('reports.view_deleted_items_title', 'ເບິ່ງລາຍການສິນຄ້າທີ່ຖືກລຶບ')); ?>" style="border-radius: 6px; font-size: 0.82rem; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                      <i class="fas fa-eye mr-1"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Table Card Footer: Circular Blue Pagination -->
    <div class="card-footer bg-white border-top py-3 px-3.5 d-flex flex-column flex-md-row justify-content-between align-items-center no-print" style="row-gap: 12px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
      <nav aria-label="Page navigation" class="ml-auto">
        <ul class="pagination report-pagination mb-0">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="delete_bills.php?page=<?php echo max(1, $page - 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&search=<?php echo urlencode($search); ?>&employee_name=<?php echo urlencode($employee_name); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
            </a>
          </li>

          <?php
            $range = 2;
            $startP = max(1, $page - $range);
            $endP   = min($total_pages, $page + $range);

            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="delete_bills.php?page=1&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&employee_name=' . urlencode($employee_name) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">1</a></li>';
                if ($startP > 2) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
            }

            for ($p = $startP; $p <= $endP; $p++) {
                $activeClass = ($p == $page) ? 'active' : '';
                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="delete_bills.php?page=' . $p . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&employee_name=' . urlencode($employee_name) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $p . '</a></li>';
            }

            if ($endP < $total_pages) {
                if ($endP < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="delete_bills.php?page=' . $total_pages . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&employee_name=' . urlencode($employee_name) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $total_pages . '</a></li>';
            }
          ?>

          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="delete_bills.php?page=<?php echo min($total_pages, $page + 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&search=<?php echo urlencode($search); ?>&employee_name=<?php echo urlencode($employee_name); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
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
    const searchInput = document.getElementById('deleteBillSearchInput');
    const table = document.querySelector('.table');

    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr:not(#noDeleteSearchResultRow)');
            let visibleCount = 0;

            if (val === '') {
                rows.forEach(row => row.style.display = '');
                const oldNoRow = document.getElementById('noDeleteSearchResultRow');
                if (oldNoRow) oldNoRow.remove();
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

            let noRow = document.getElementById('noDeleteSearchResultRow');
            if (visibleCount === 0 && rows.length > 0) {
                if (!noRow) {
                    noRow = document.createElement('tr');
                    noRow.id = 'noDeleteSearchResultRow';
                    noRow.innerHTML = `<td colspan="8" class="text-center py-5 text-muted font-weight-bold">
                        <i class="fas fa-exclamation-circle fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                        <?php echo htmlspecialchars(t('reports.no_search_match', 'ບໍ່ພົບຂໍ້ມູນທີ່ຕົງກັບຄຳຄົ້ນຫາ'), ENT_QUOTES); ?> "${this.value}"
                    </td>`;
                    table.querySelector('tbody').appendChild(noRow);
                }
            } else {
                if (noRow) noRow.remove();
            }
        });
    }
});
</script>

<?php 
require_once __DIR__ . '/partials/js/reports_js.php';
require_once __DIR__ . '/../../layouts/footer.php'; 
?>
