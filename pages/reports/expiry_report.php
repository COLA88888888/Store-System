<?php
// Standalone Page: ລາຍງານສິນຄ້າໝົດອາຍຸ (Product expiry report)
// Read-only report of products that need attention: already expired, or expiring within
// EXPIRY_ALERT_DAYS. Products further out than that are not listed. Time left is shown as
// years / months / days ("1 ເດືອນ 1 ວັນ"). Dates are set on Settings > ຈັດການສິນຄ້າໝົດອາຍຸ.
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('report') && !hasPermission('expiry')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$alertDays = getExpiryAlertDays($pdo);

// Branch: users who can see every branch may pick one (0 = all); everyone else is locked to theirs.
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT store_id, store_name, is_main FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$multiBranch = canViewAllBranches($pdo) && count($branchesList) > 1;
$filterStore = $multiBranch
    ? (isset($_GET['store_id']) && $_GET['store_id'] !== '' ? (int)$_GET['store_id'] : intval($_SESSION['store_id'] ?? 1))
    : getActiveStoreId($pdo);

$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'expired', 'near'], true)) $status = 'all';
// "ຈະໝົດອາຍຸພາຍໃນ N ວັນ" — only spans shorter than the alert window still narrow the list
$withinOptions = array_values(array_filter([1, 3, 7, 15, 30, 60, 90], function ($d) use ($alertDays) { return $d < $alertDays; }));
$within = (int)($_GET['within'] ?? 0);                 // 0 = any; else expires within N days (incl. expired)
if (!in_array($within, $withinOptions, true)) $within = 0;
$search = trim($_GET['q'] ?? '');
$perPageRaw = $_GET['per_page'] ?? '25';
$perPage = $perPageRaw === 'all' ? 0 : max(5, min(500, (int)$perPageRaw));
$page = max(1, (int)($_GET['page'] ?? 1));

// Base filter: only expired + expiring within the alert window, then branch + search +
// "within N days"; the status card is applied on top.
$baseWhere = "p.expiry_date IS NOT NULL AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
$baseParams = [$alertDays];
if ($filterStore > 0) {
    $baseWhere .= " AND p.store_id = ?";
    $baseParams[] = $filterStore;
}
if ($search !== '') {
    $baseWhere .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR CAST(p.product_id AS CHAR) LIKE ?)";
    $like = '%' . $search . '%';
    array_push($baseParams, $like, $like, $like);
}
if ($within > 0) {
    $baseWhere .= " AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $baseParams[] = $within;
}

$counts = ['all' => 0, 'expired' => 0, 'near' => 0];
try {
    $c = $pdo->prepare("
        SELECT COUNT(*) AS all_cnt,
               COALESCE(SUM(p.expiry_date < CURDATE()), 0) AS expired_cnt,
               COALESCE(SUM(p.expiry_date >= CURDATE()), 0) AS near_cnt
        FROM st_products p WHERE $baseWhere");
    $c->execute($baseParams);
    $row = $c->fetch(PDO::FETCH_ASSOC);
    $counts = ['all' => (int)$row['all_cnt'], 'expired' => (int)$row['expired_cnt'], 'near' => (int)$row['near_cnt']];
} catch (Exception $e) {}

$where = $baseWhere;
$params = $baseParams;
if ($status === 'expired') {
    $where .= " AND p.expiry_date < CURDATE()";
} elseif ($status === 'near') {
    $where .= " AND p.expiry_date >= CURDATE()";   // upper bound is already in $baseWhere
}

$total = $counts[$status];
$pages = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
$page = min($page, $pages);
$rows = [];
try {
    $sql = "SELECT p.product_id, p.product_name, p.barcode, p.unit, p.qty, p.cut_qty, p.expiry_date,
                   c.category_name, s.store_name, DATEDIFF(p.expiry_date, CURDATE()) AS days_left
            FROM st_products p
            LEFT JOIN st_categories c ON c.category_id = p.category_id
            LEFT JOIN st_tbstore s ON s.store_id = p.store_id
            WHERE $where
            ORDER BY p.expiry_date ASC, p.product_name ASC";
    if ($perPage > 0) $sql .= " LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$showBranchCol = $multiBranch && $filterStore === 0;
$storeLabel = t('expiry.all_branches', '-- ທຸກສາຂາ --');
foreach ($branchesList as $b) if ((int)$b['store_id'] === $filterStore) $storeLabel = $b['store_name'];

function expRepUrl($over) {
    $q = $_GET;
    unset($q['print'], $q['export']);   // ລິ້ງອື່ນໆ ບໍ່ສັ່ງພິມ / ອອກໄຟລ໌ຊ້ຳ
    return 'expiry_report.php?' . htmlspecialchars(http_build_query(array_merge($q, $over)));
}

$tabs = [
    'all'     => ['label' => t('expiry.tab_all', 'ທັງໝົດ'),        'icon' => 'fa-list',                 'color' => 'secondary'],
    'expired' => ['label' => t('expiry.tab_expired', 'ໝົດອາຍຸແລ້ວ'), 'icon' => 'fa-times-circle',         'color' => 'danger'],
    'near'    => ['label' => t('expiry.tab_near', 'ໃກ້ໝົດອາຍຸ'),    'icon' => 'fa-exclamation-triangle', 'color' => 'warning'],
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<?php echo themeCssLink('expiry_report.css'); /* CSS ຍ້າຍໄປ themes/expiry_report.css */ ?>
<?php echo themeCssLink('report-table.css'); /* ຕາຕະລາງ + ປຸ່ມ Excel/PDF/ພິມ ແບບດຽວກັນທົ່ວລະບົບ */ ?>

<?php
$statColors = ['all' => '#2c5aa0', 'expired' => '#dc2626', 'near' => '#d97706'];
$statDarks  = ['all' => '#1e3a70', 'expired' => '#991b1b', 'near' => '#b45309'];
$statIcons  = ['all' => 'fa-clipboard-list', 'expired' => 'fa-times-circle', 'near' => 'fa-hourglass-half'];
$statLabels = [
    'all'     => t('expiry.card_attention', 'ສິນຄ້າໃກ້ໝົດອາຍຸ ແລະ ໝົດອາຍຸ'),
    'expired' => t('expiry.tab_expired', 'ໝົດອາຍຸແລ້ວ'),
    'near'    => sprintf(t('expiry.card_near', 'ໃກ້ໝົດອາຍຸ (ບໍ່ເກີນ %s ວັນ)'), $alertDays),
];
?>

<div class="container-fluid p-3 p-md-4 exr">
  <!-- Header -->
  <div class="exr-header mb-3 no-print">
    <div>
      <h5 class="exr-title"><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?></h5>
      <div class="exr-sub"><?php echo htmlspecialchars(sprintf(t('expiry.report_subtitle', 'ສິນຄ້າທີ່ໝົດອາຍຸແລ້ວ ແລະ ໃກ້ໝົດອາຍຸ (ແຈ້ງເຕືອນກ່ອນ %s ວັນ)'), $alertDays)); ?></div>
    </div>
    <div class="exr-actions">
      <?php require __DIR__ . '/../../layouts/partials/report/export_buttons.php'; /* Excel / PDF / ພິມ */ ?>
    </div>
  </div>

  <div class="print-only mb-3">
    <h4 class="font-weight-bold mb-1"><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?> — <?php echo htmlspecialchars($tabs[$status]['label']); ?></h4>
    <div><?php echo htmlspecialchars($storeLabel); ?> · <?php echo htmlspecialchars(t('expiry.printed_at', 'ວັນທີພິມ')); ?>: <?php echo date('d/m/Y H:i'); ?></div>
  </div>

  <!-- Summary / status filter -->
  <div class="exr-stats mb-3 no-print">
    <?php foreach (['all', 'expired', 'near'] as $key): ?>
      <?php $pct = $counts['all'] > 0 ? round($counts[$key] / $counts['all'] * 100) : 0; ?>
      <a href="<?php echo expRepUrl(['status' => $key, 'page' => 1]); ?>" class="exr-stat <?php echo $status === $key ? 'active' : ''; ?>" style="--c: <?php echo $statColors[$key]; ?>; --d: <?php echo $statDarks[$key]; ?>;">
        <div class="top">
          <div>
            <div class="lbl"><?php echo htmlspecialchars($statLabels[$key]); ?></div>
            <div class="num"><?php echo number_format($counts[$key]); ?><small><?php echo htmlspecialchars(t('expiry.unit_items', 'ລາຍການ')); ?></small></div>
          </div>
          <div class="ico"><i class="fas <?php echo $statIcons[$key]; ?>"></i></div>
        </div>
        <div class="pct"><?php echo $key === 'all'
            ? htmlspecialchars(sprintf(t('expiry.stat_all_hint', 'ແຈ້ງເຕືອນກ່ອນ %s ວັນ'), $alertDays))
            : htmlspecialchars(sprintf(t('expiry.stat_pct', '%s%% ຂອງທັງໝົດ'), $pct)); ?></div>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="exr-panel rpt-card">
    <!-- Filters -->
    <form method="GET" action="expiry_report.php" class="exr-filters no-print">
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
      <div style="flex: 2 1 220px;">
        <label><?php echo htmlspecialchars(t('expiry.search_label', 'ຄົ້ນຫາ:')); ?></label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="<?php echo htmlspecialchars(t('expiry.search_placeholder', 'ຄົ້ນຫາ ຊື່, ບາໂຄ້ດ...')); ?>">
      </div>
      <?php if ($multiBranch): ?>
      <div style="flex: 1 1 150px;">
        <label><?php echo htmlspecialchars(t('expiry.branch_label', 'ສາຂາ:')); ?></label>
        <select name="store_id" class="form-control" onchange="this.form.submit()">
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo (int)$b['store_id']; ?>" <?php echo $filterStore === (int)$b['store_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['store_name']); ?></option>
          <?php endforeach; ?>
          <option value="0" <?php echo $filterStore === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('expiry.all_branches', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php endif; ?>
      <?php if (!empty($withinOptions)): ?>
      <div style="flex: 1 1 150px;">
        <label><?php echo htmlspecialchars(t('expiry.within_label', 'ຈະໝົດອາຍຸພາຍໃນ:')); ?></label>
        <select name="within" class="form-control" onchange="this.form.submit()">
          <option value="0"><?php echo htmlspecialchars(t('expiry.within_any', '-- ທັງໝົດ --')); ?></option>
          <?php foreach ($withinOptions as $wd): ?>
            <option value="<?php echo $wd; ?>" <?php echo $within === $wd ? 'selected' : ''; ?>><?php echo htmlspecialchars(sprintf(t('expiry.dur_day', '%s ວັນ'), $wd)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div style="flex: 0 1 100px;">
        <label><?php echo htmlspecialchars(t('expiry.per_page_label', 'ສະແດງ:')); ?></label>
        <select name="per_page" class="form-control" onchange="this.form.submit()">
          <?php foreach (['10', '25', '50', '100', 'all'] as $pp): ?>
            <option value="<?php echo $pp; ?>" <?php echo $perPageRaw === $pp ? 'selected' : ''; ?>><?php echo $pp === 'all' ? htmlspecialchars(t('expiry.tab_all', 'ທັງໝົດ')) : $pp; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex" style="gap: 6px;">
        <button type="submit" class="btn btn-primary px-3" style="background: #244886; border-color: #244886;"><i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('expiry.search_btn', 'ຄົ້ນຫາ')); ?></button>
        <a href="expiry_report.php" class="btn btn-light border px-3 d-flex align-items-center" title="Reset"><i class="fas fa-redo text-muted"></i></a>
      </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-hover table-striped rpt-table exr-table mb-0">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th><?php echo htmlspecialchars(t('expiry.col_product', 'ສິນຄ້າ')); ?></th>
            <th><?php echo htmlspecialchars(t('expiry.col_category', 'ປະເພດ')); ?></th>
            <?php if ($showBranchCol): ?><th><?php echo htmlspecialchars(t('expiry.col_branch', 'ສາຂາ')); ?></th><?php endif; ?>
            <th class="text-right"><?php echo htmlspecialchars(t('expiry.col_stock', 'ສະຕັອກ')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_remaining', 'ເຫຼືອເວລາ')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_status', 'ສະຖານະ')); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr class="empty-row" style="background: #ffffff !important;">
              <td colspan="<?php echo $showBranchCol ? 8 : 7; ?>" class="text-center py-5 text-muted" style="background: #ffffff !important;">
                <i class="far fa-calendar-check fa-3x mb-3 d-block" style="color: #cbd5e1;"></i>
                <div class="text-muted font-weight-bold" style="font-size: 1rem;"><?php echo htmlspecialchars($counts['all'] === 0 && $search === '' && $within === 0 ? t('expiry.empty_none_attention', 'ບໍ່ມີສິນຄ້າໃກ້ໝົດອາຍຸ ຫຼື ໝົດອາຍຸ') : t('expiry.empty_filter', 'ບໍ່ພົບລາຍການ')); ?></div>
                <?php if (!($counts['all'] === 0 && $search === '' && $within === 0)): ?>
                  <div class="text-muted small mt-1"><?php echo htmlspecialchars(t('reports.change_filter_hint', 'ກະລຸນາປ່ຽນເງື່ອນໄຂການຄົ້ນຫາ ຫຼື ເລືອກຊ່ວງວັນທີໃໝ່')); ?></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $i => $r): ?>
              <?php
                $days = (int)$r['days_left'];
                if ($days < 0) {
                    $pill = ['danger', t('expiry.status_expired', 'ໝົດອາຍຸແລ້ວ')];
                    $color = '#dc2626';
                } elseif ($days === 0) {
                    $pill = ['danger', t('expiry.status_today', 'ໝົດອາຍຸມື້ນີ້')];
                    $color = '#dc2626';
                } elseif ($days <= $alertDays) {
                    $pill = ['warning', t('expiry.status_near', 'ໃກ້ໝົດອາຍຸ')];
                    $color = '#b45309';
                } else {
                    $pill = ['success', t('expiry.status_normal', 'ປົກກະຕິ')];
                    $color = '#15803d';
                }
              ?>
              <tr>
                <td class="text-center text-muted"><?php echo ($perPage > 0 ? ($page - 1) * $perPage : 0) + $i + 1; ?></td>
                <td>
                  <div class="exr-name"><?php echo htmlspecialchars($r['product_name']); ?><?php if (!empty($r['unit'])): ?> <span class="text-muted font-weight-normal">(<?php echo htmlspecialchars($r['unit']); ?>)</span><?php endif; ?></div>
                  <div class="exr-meta">
                    <?php echo htmlspecialchars(t('expiry.col_code', 'ລະຫັດ')); ?> <?php echo (int)$r['product_id']; ?>
                    <?php if (!empty($r['barcode'])): ?> · <?php echo htmlspecialchars($r['barcode']); ?><?php endif; ?>
                  </div>
                </td>
                <td class="text-muted"><?php echo htmlspecialchars($r['category_name'] ?? '-'); ?></td>
                <?php if ($showBranchCol): ?><td class="text-muted"><?php echo htmlspecialchars($r['store_name'] ?? '-'); ?></td><?php endif; ?>
                <td class="text-right" style="white-space: nowrap;">
                  <?php if ((int)$r['cut_qty'] === 0): ?>
                    <span class="text-muted">-</span>
                  <?php elseif ((float)$r['qty'] <= 0): ?>
                    <span class="exr-pill muted"><?php echo htmlspecialchars(t('expiry.out_of_stock', 'ໝົດສະຕັອກ')); ?></span>
                  <?php else: ?>
                    <span class="font-weight-bold"><?php echo number_format((float)$r['qty']); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center" style="white-space: nowrap;"><?php echo date('d/m/Y', strtotime($r['expiry_date'])); ?></td>
                <td class="text-center exr-remaining" style="color: <?php echo $color; ?>;"><?php echo formatExpiryRemainingHtml($r['expiry_date']); ?></td>
                <td class="text-center"><span class="exr-pill <?php echo $pill[0]; ?>"><?php echo htmlspecialchars($pill[1]); ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Table Card Footer: Circular Blue Pagination Controls (ຊິດຂວາທາງລຸ່ມ ແບບດຽວກັບໜ້າລາຍງານອື່ນໆ) -->
    <div class="card-footer bg-white border-top py-3 px-3.5 d-flex flex-column flex-md-row justify-content-between align-items-center no-print" style="row-gap: 12px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
      <div class="d-flex align-items-center">
        <small class="text-muted font-weight-bold" style="font-size: 0.85rem;">
          <?php
            $from = $total === 0 ? 0 : ($perPage > 0 ? ($page - 1) * $perPage + 1 : 1);
            $to = $perPage > 0 ? min($total, $page * $perPage) : $total;
            if ($total > 0) {
                echo htmlspecialchars(sprintf(t('reports.showing_items', 'ສະແດງ %s ຫາ %s ຈາກທັງໝົດ %s ລາຍການ'), number_format($from), number_format($to), number_format($total)));
            } else {
                echo htmlspecialchars(t('reports.showing_empty', 'ສະແດງ 0 ຈາກ 0 ລາຍການ'));
            }
          ?>
        </small>
      </div>

      <!-- Pagination: Circular Blue Pagination Controls (ແບບດຽວກັບໜ້າລາຍງານອື່ນໆ) -->
      <nav aria-label="Page navigation" class="ml-auto">
        <ul class="pagination report-pagination mb-0">
          <!-- Previous Page -->
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo expRepUrl(['page' => max(1, $page - 1)]); ?>" aria-label="Previous">
              <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
            </a>
          </li>

          <?php
            $range = 2;
            $startP = max(1, $page - $range);
            $endP   = min($pages, $page + $range);

            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="' . expRepUrl(['page' => 1]) . '">1</a></li>';
                if ($startP > 2) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none; background:transparent;">...</span></li>';
                }
            }

            for ($p = $startP; $p <= $endP; $p++) {
                $activeClass = ($p == $page) ? 'active' : '';
                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . expRepUrl(['page' => $p]) . '">' . $p . '</a></li>';
            }

            if ($endP < $pages) {
                if ($endP < $pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none; background:transparent;">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="' . expRepUrl(['page' => $pages]) . '">' . $pages . '</a></li>';
            }
          ?>

          <!-- Next Page -->
          <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo expRepUrl(['page' => min($pages, $page + 1)]); ?>" aria-label="Next">
              <i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<?php
// ພິມ / Excel / PDF — ເອົາຕາຕະລາງ .exr-table (ທຸກລາຍການ ຫຼັງໂຫຼດ per_page=all) ອອກເປັນລາຍງານ Phetsarath OT
$rptMeta = [$tabs[$status]['label'], sprintf(t('expiry.stat_all_hint', 'ແຈ້ງເຕືອນກ່ອນ %s ວັນ'), $alertDays)];
if ($within > 0)    $rptMeta[] = t('expiry.within_label', 'ຈະໝົດອາຍຸພາຍໃນ:') . ' ' . sprintf(t('expiry.dur_day', '%s ວັນ'), $within);
if ($search !== '') $rptMeta[] = t('expiry.search_label', 'ຄົ້ນຫາ:') . ' ' . $search;
$rptExport = [
    'table'    => '.exr-table',
    'title'    => t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ'),
    'fileBase' => 'expiry_report_' . $status,
    'store'    => $storeLabel,
    'meta'     => $rptMeta,
];
require __DIR__ . '/../../layouts/partials/report/export_js.php';
?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
