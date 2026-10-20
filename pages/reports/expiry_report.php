<?php
// Standalone Page: ລາຍງານສິນຄ້າໝົດອາຍຸ (Product expiry report)
// Read-only report of every product that has an expiry date (products.expiry_date): expired,
// expiring soon (within EXPIRY_ALERT_DAYS) and the rest, with the time left shown as
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
if (!in_array($status, ['all', 'expired', 'near', 'normal'], true)) $status = 'all';
$within = (int)($_GET['within'] ?? 0);                 // 0 = any; else expires within N days (incl. expired)
if (!in_array($within, [0, 7, 15, 30, 60, 90], true)) $within = 0;
$search = trim($_GET['q'] ?? '');
$perPageRaw = $_GET['per_page'] ?? '25';
$perPage = $perPageRaw === 'all' ? 0 : max(5, min(500, (int)$perPageRaw));
$page = max(1, (int)($_GET['page'] ?? 1));

// Base filter (branch + search + "within N days"); the status tab is applied on top.
$baseWhere = "p.expiry_date IS NOT NULL";
$baseParams = [];
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

$counts = ['all' => 0, 'expired' => 0, 'near' => 0, 'normal' => 0];
try {
    $c = $pdo->prepare("
        SELECT COUNT(*) AS all_cnt,
               COALESCE(SUM(p.expiry_date < CURDATE()), 0) AS expired_cnt,
               COALESCE(SUM(p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)), 0) AS near_cnt,
               COALESCE(SUM(p.expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY)), 0) AS normal_cnt
        FROM st_products p WHERE $baseWhere");
    $c->execute(array_merge([$alertDays, $alertDays], $baseParams));
    $row = $c->fetch(PDO::FETCH_ASSOC);
    $counts = ['all' => (int)$row['all_cnt'], 'expired' => (int)$row['expired_cnt'], 'near' => (int)$row['near_cnt'], 'normal' => (int)$row['normal_cnt']];
} catch (Exception $e) {}

$where = $baseWhere;
$params = $baseParams;
if ($status === 'expired') {
    $where .= " AND p.expiry_date < CURDATE()";
} elseif ($status === 'near') {
    $where .= " AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $alertDays;
} elseif ($status === 'normal') {
    $where .= " AND p.expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $alertDays;
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
    return 'expiry_report.php?' . htmlspecialchars(http_build_query(array_merge($_GET, $over)));
}

$tabs = [
    'all'     => ['label' => t('expiry.tab_all', 'ທັງໝົດ'),        'icon' => 'fa-list',                 'color' => 'secondary'],
    'expired' => ['label' => t('expiry.tab_expired', 'ໝົດອາຍຸແລ້ວ'), 'icon' => 'fa-times-circle',         'color' => 'danger'],
    'near'    => ['label' => t('expiry.tab_near', 'ໃກ້ໝົດອາຍຸ'),    'icon' => 'fa-exclamation-triangle', 'color' => 'warning'],
    'normal'  => ['label' => t('expiry.tab_normal', 'ປົກກະຕິ'),     'icon' => 'fa-check-circle',         'color' => 'success'],
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<?php echo themeCssLink('expiry_report.css'); /* CSS ຍ້າຍໄປ themes/expiry_report.css */ ?>

<?php
$statColors = ['all' => '#2c5aa0', 'expired' => '#dc2626', 'near' => '#d97706', 'normal' => '#16a34a'];
$statDarks  = ['all' => '#1e3a70', 'expired' => '#991b1b', 'near' => '#b45309', 'normal' => '#15803d'];
$statIcons  = ['all' => 'fa-clipboard-list', 'expired' => 'fa-times-circle', 'near' => 'fa-hourglass-half', 'normal' => 'fa-check-circle'];
$statLabels = [
    'all'     => t('expiry.card_tracked', 'ສິນຄ້າທີ່ຕິດຕາມວັນໝົດອາຍຸ'),
    'expired' => t('expiry.tab_expired', 'ໝົດອາຍຸແລ້ວ'),
    'near'    => sprintf(t('expiry.card_near', 'ໃກ້ໝົດອາຍຸ (ບໍ່ເກີນ %s ວັນ)'), $alertDays),
    'normal'  => t('expiry.tab_normal', 'ປົກກະຕິ'),
];
?>

<div class="container-fluid p-3 p-md-4 exr">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-start mb-3 no-print" style="gap: 12px;">
    <div>
      <h5 class="exr-title"><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?></h5>
      <div class="exr-sub"><?php echo htmlspecialchars(sprintf(t('expiry.report_subtitle', 'ສິນຄ້າທີ່ໝົດອາຍຸແລ້ວ ແລະ ໃກ້ໝົດອາຍຸ (ແຈ້ງເຕືອນກ່ອນ %s ວັນ)'), $alertDays)); ?></div>
    </div>
    <button type="button" class="btn btn-light border btn-sm px-3" onclick="window.print()" style="border-radius: 8px; height: 36px; white-space: nowrap;">
      <i class="fas fa-print mr-1 text-muted"></i> <?php echo htmlspecialchars(t('expiry.print_btn', 'ພິມລາຍງານ')); ?>
    </button>
  </div>

  <div class="print-only mb-3">
    <h4 class="font-weight-bold mb-1"><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?> — <?php echo htmlspecialchars($tabs[$status]['label']); ?></h4>
    <div><?php echo htmlspecialchars($storeLabel); ?> · <?php echo htmlspecialchars(t('expiry.printed_at', 'ວັນທີພິມ')); ?>: <?php echo date('d/m/Y H:i'); ?></div>
  </div>

  <!-- Summary / status filter -->
  <div class="exr-stats mb-3 no-print">
    <?php foreach (['all', 'expired', 'near', 'normal'] as $key): ?>
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

  <div class="exr-panel">
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
      <div style="flex: 1 1 150px;">
        <label><?php echo htmlspecialchars(t('expiry.within_label', 'ຈະໝົດອາຍຸພາຍໃນ:')); ?></label>
        <select name="within" class="form-control" onchange="this.form.submit()">
          <option value="0"><?php echo htmlspecialchars(t('expiry.within_any', '-- ທັງໝົດ --')); ?></option>
          <?php foreach ([7, 15, 30, 60, 90] as $wd): ?>
            <option value="<?php echo $wd; ?>" <?php echo $within === $wd ? 'selected' : ''; ?>><?php echo htmlspecialchars(sprintf(t('expiry.dur_day', '%s ວັນ'), $wd)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
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
      <table class="table exr-table">
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
            <tr><td colspan="<?php echo $showBranchCol ? 8 : 7; ?>" class="text-center py-5 text-muted">
              <i class="far fa-calendar-check fa-2x mb-2 d-block" style="color: #cbd5e1;"></i>
              <?php echo htmlspecialchars($counts['all'] === 0 && $search === '' && $within === 0 ? t('expiry.empty_none', 'ຍັງບໍ່ມີສິນຄ້າທີ່ກຳນົດວັນໝົດອາຍຸ') : t('expiry.empty_filter', 'ບໍ່ພົບລາຍການ')); ?>
            </td></tr>
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

    <?php if ($total > 0): ?>
      <div class="exr-foot no-print">
        <small class="text-muted"><?php
          $from = $perPage > 0 ? ($page - 1) * $perPage + 1 : 1;
          $to = $perPage > 0 ? min($total, $page * $perPage) : $total;
          echo htmlspecialchars(sprintf(t('expiry.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'), number_format($from), number_format($to), number_format($total)));
        ?></small>
        <?php if ($pages > 1): ?>
          <ul class="pagination pagination-sm mb-0 ml-auto">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo expRepUrl(['page' => $page - 1]); ?>"><i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i></a></li>
            <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
              <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>"><a class="page-link" href="<?php echo expRepUrl(['page' => $p]); ?>"><?php echo $p; ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo expRepUrl(['page' => $page + 1]); ?>"><i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i></a></li>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
