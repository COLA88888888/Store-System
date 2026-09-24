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
    $branchesList = $pdo->query("SELECT store_id, store_name, is_main FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$multiBranch = canViewAllBranches($pdo) && count($branchesList) > 1;
$filterStore = $multiBranch
    ? (isset($_GET['store_id']) && $_GET['store_id'] !== '' ? (int)$_GET['store_id'] : getActiveStoreId($pdo))
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
        FROM products p WHERE $baseWhere");
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
            FROM products p
            LEFT JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN tbstore s ON s.store_id = p.store_id
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
<style>
  .exr-card { border-radius: 12px; color: #fff; padding: 14px 16px; height: 100%; }
  .exr-card .num { font-size: 1.6rem; font-weight: 800; line-height: 1.2; }
  .exr-card .lbl { font-size: 0.82rem; font-weight: 700; opacity: .95; }
  .exr-card i.bg-icon { font-size: 2rem; opacity: .35; }
  .exr-tabs .nav-link { border-radius: 8px; font-weight: 700; font-size: 0.84rem; color: #475569; padding: 6px 12px; }
  .exr-tabs .nav-link.active { background: #244886; color: #fff; }
  .exr-remaining { font-weight: 700; white-space: nowrap; }
  .print-only { display: none; }
  @media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .container-fluid { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    table { font-size: 11px !important; }
  }
</style>

<div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-hourglass-half text-danger mr-2"></i> <?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?>
      </h5>
      <small class="text-muted"><?php echo htmlspecialchars(sprintf(t('expiry.report_subtitle', 'ສິນຄ້າທີ່ໝົດອາຍຸແລ້ວ ແລະ ໃກ້ໝົດອາຍຸ (ແຈ້ງເຕືອນກ່ອນ %s ວັນ)'), $alertDays)); ?></small>
    </div>
    <button type="button" class="btn btn-light border px-3 font-weight-bold" onclick="window.print()" style="border-radius: 8px; height: 38px;">
      <i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('expiry.print_btn', 'ພິມລາຍງານ')); ?>
    </button>
  </div>

  <div class="print-only mb-3">
    <h4 class="font-weight-bold mb-1"><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?> — <?php echo htmlspecialchars($tabs[$status]['label']); ?></h4>
    <div><?php echo htmlspecialchars($storeLabel); ?> · <?php echo htmlspecialchars(t('expiry.printed_at', 'ວັນທີພິມ')); ?>: <?php echo date('d/m/Y H:i'); ?></div>
  </div>

  <!-- Summary cards -->
  <div class="row mb-3 no-print">
    <div class="col-md-4 col-12 mb-2">
      <a href="<?php echo expRepUrl(['status' => 'expired', 'page' => 1]); ?>" class="d-block text-decoration-none">
        <div class="exr-card d-flex justify-content-between align-items-center shadow-sm" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
          <div><div class="lbl"><?php echo htmlspecialchars(t('expiry.tab_expired', 'ໝົດອາຍຸແລ້ວ')); ?></div><div class="num"><?php echo number_format($counts['expired']); ?></div></div>
          <i class="fas fa-times-circle bg-icon"></i>
        </div>
      </a>
    </div>
    <div class="col-md-4 col-12 mb-2">
      <a href="<?php echo expRepUrl(['status' => 'near', 'page' => 1]); ?>" class="d-block text-decoration-none">
        <div class="exr-card d-flex justify-content-between align-items-center shadow-sm" style="background: linear-gradient(135deg, #f59e0b, #b45309);">
          <div><div class="lbl"><?php echo htmlspecialchars(sprintf(t('expiry.card_near', 'ໃກ້ໝົດອາຍຸ (ບໍ່ເກີນ %s ວັນ)'), $alertDays)); ?></div><div class="num"><?php echo number_format($counts['near']); ?></div></div>
          <i class="fas fa-exclamation-triangle bg-icon"></i>
        </div>
      </a>
    </div>
    <div class="col-md-4 col-12 mb-2">
      <a href="<?php echo expRepUrl(['status' => 'all', 'page' => 1]); ?>" class="d-block text-decoration-none">
        <div class="exr-card d-flex justify-content-between align-items-center shadow-sm" style="background: linear-gradient(135deg, #2c5aa0, #244886);">
          <div><div class="lbl"><?php echo htmlspecialchars(t('expiry.card_tracked', 'ສິນຄ້າທີ່ຕິດຕາມວັນໝົດອາຍຸ')); ?></div><div class="num"><?php echo number_format($counts['all']); ?></div></div>
          <i class="fas fa-clipboard-list bg-icon"></i>
        </div>
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="report-filter-box no-print mb-3" style="padding: 12px 16px;">
    <form method="GET" action="expiry_report.php" class="d-flex flex-column flex-md-row align-items-md-end flex-wrap" style="gap: 12px;">
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
      <?php if ($multiBranch): ?>
      <div style="flex: 1 1 160px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem;"><i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('expiry.branch_label', 'ສາຂາ:')); ?></label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px;">
          <option value="0" <?php echo $filterStore === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('expiry.all_branches', '-- ທຸກສາຂາ --')); ?></option>
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo (int)$b['store_id']; ?>" <?php echo $filterStore === (int)$b['store_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['store_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div style="flex: 1 1 170px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem;"><i class="fas fa-hourglass-half text-warning mr-1"></i> <?php echo htmlspecialchars(t('expiry.within_label', 'ຈະໝົດອາຍຸພາຍໃນ:')); ?></label>
        <select name="within" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px;">
          <option value="0"><?php echo htmlspecialchars(t('expiry.within_any', '-- ທັງໝົດ --')); ?></option>
          <?php foreach ([7, 15, 30, 60, 90] as $wd): ?>
            <option value="<?php echo $wd; ?>" <?php echo $within === $wd ? 'selected' : ''; ?>><?php echo htmlspecialchars(sprintf(t('expiry.dur_day', '%s ວັນ'), $wd)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex: 2 1 240px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem;"><i class="fas fa-search text-primary mr-1"></i> <?php echo htmlspecialchars(t('expiry.search_label', 'ຄົ້ນຫາ:')); ?></label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(t('expiry.search_placeholder', 'ຄົ້ນຫາ ຊື່, ບາໂຄ້ດ...')); ?>" style="border-radius: 8px; height: 38px;">
      </div>
      <div style="flex: 0 1 110px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem;"><i class="fas fa-layer-group text-primary mr-1"></i> <?php echo htmlspecialchars(t('expiry.per_page_label', 'ສະແດງ:')); ?></label>
        <select name="per_page" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px;">
          <?php foreach (['10', '25', '50', '100', 'all'] as $pp): ?>
            <option value="<?php echo $pp; ?>" <?php echo $perPageRaw === $pp ? 'selected' : ''; ?>><?php echo $pp === 'all' ? htmlspecialchars(t('expiry.tab_all', 'ທັງໝົດ')) : $pp; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex align-items-center" style="gap: 8px;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('expiry.search_btn', 'ຄົ້ນຫາ')); ?></button>
        <a href="expiry_report.php" class="btn btn-light btn-sm border px-3" style="border-radius: 8px; height: 38px; line-height: 26px;"><i class="fas fa-redo"></i></a>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white py-2 px-3 no-print">
      <ul class="nav nav-pills exr-tabs" style="gap: 4px;">
        <?php foreach ($tabs as $key => $tab): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $status === $key ? 'active' : ''; ?>" href="<?php echo expRepUrl(['status' => $key, 'page' => 1]); ?>">
              <i class="fas <?php echo $tab['icon']; ?> mr-1 <?php echo $status === $key ? '' : 'text-' . $tab['color']; ?>"></i><?php echo htmlspecialchars($tab['label']); ?>
              <span class="badge <?php echo $status === $key ? 'badge-light' : 'badge-' . $tab['color']; ?> ml-1"><?php echo number_format($counts[$key]); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
        <thead style="background: #f8fafc; color: #1e293b; border-bottom: 2px solid #cbd5e1;">
          <tr style="white-space: nowrap;">
            <th class="text-center py-3" style="width: 55px;"><?php echo htmlspecialchars(t('expiry.col_no', 'ລຳດັບ')); ?></th>
            <th class="py-3"><?php echo htmlspecialchars(t('expiry.col_code', 'ລະຫັດ')); ?></th>
            <th class="py-3"><?php echo htmlspecialchars(t('expiry.col_product', 'ສິນຄ້າ')); ?></th>
            <th class="py-3"><?php echo htmlspecialchars(t('expiry.col_category', 'ປະເພດ')); ?></th>
            <?php if ($showBranchCol): ?><th class="py-3"><?php echo htmlspecialchars(t('expiry.col_branch', 'ສາຂາ')); ?></th><?php endif; ?>
            <th class="text-center py-3"><?php echo htmlspecialchars(t('expiry.col_stock', 'ສະຕັອກ')); ?></th>
            <th class="text-center py-3"><?php echo htmlspecialchars(t('expiry.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?></th>
            <th class="text-center py-3"><?php echo htmlspecialchars(t('expiry.col_remaining', 'ເຫຼືອເວລາ')); ?></th>
            <th class="text-center py-3"><?php echo htmlspecialchars(t('expiry.col_status', 'ສະຖານະ')); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="<?php echo $showBranchCol ? 9 : 8; ?>" class="text-center py-5 text-muted">
              <i class="fas fa-calendar-check fa-2x mb-2 d-block text-secondary"></i>
              <?php echo htmlspecialchars($counts['all'] === 0 && $search === '' && $within === 0 ? t('expiry.empty_none', 'ຍັງບໍ່ມີສິນຄ້າທີ່ກຳນົດວັນໝົດອາຍຸ') : t('expiry.empty_filter', 'ບໍ່ພົບລາຍການ')); ?>
            </td></tr>
          <?php else: ?>
            <?php foreach ($rows as $i => $r): ?>
              <?php
                $days = (int)$r['days_left'];
                if ($days < 0) {
                    $badge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>' . htmlspecialchars(t('expiry.status_expired', 'ໝົດອາຍຸແລ້ວ')) . '</span>';
                    $color = '#dc2626';
                } elseif ($days === 0) {
                    $badge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i>' . htmlspecialchars(t('expiry.status_today', 'ໝົດອາຍຸມື້ນີ້')) . '</span>';
                    $color = '#dc2626';
                } elseif ($days <= $alertDays) {
                    $badge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>' . htmlspecialchars(t('expiry.status_near', 'ໃກ້ໝົດອາຍຸ')) . '</span>';
                    $color = '#b45309';
                } else {
                    $badge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>' . htmlspecialchars(t('expiry.status_normal', 'ປົກກະຕິ')) . '</span>';
                    $color = '#15803d';
                }
              ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?php echo ($perPage > 0 ? ($page - 1) * $perPage : 0) + $i + 1; ?></td>
                <td style="white-space: nowrap;"><span class="badge badge-light border px-2 py-1" style="font-size: 0.8rem;"><?php echo (int)$r['product_id']; ?></span></td>
                <td class="font-weight-bold text-dark">
                  <?php echo htmlspecialchars($r['product_name']); ?><?php if (!empty($r['unit'])): ?> <small class="text-muted">(<?php echo htmlspecialchars($r['unit']); ?>)</small><?php endif; ?>
                  <?php if (!empty($r['barcode'])): ?><small class="d-block text-muted font-weight-normal"><i class="fas fa-barcode mr-1"></i><?php echo htmlspecialchars($r['barcode']); ?></small><?php endif; ?>
                </td>
                <td class="text-muted"><?php echo htmlspecialchars($r['category_name'] ?? '-'); ?></td>
                <?php if ($showBranchCol): ?><td class="text-muted"><?php echo htmlspecialchars($r['store_name'] ?? '-'); ?></td><?php endif; ?>
                <td class="text-center" style="white-space: nowrap;">
                  <?php if ((int)$r['cut_qty'] === 0): ?>
                    <span class="text-muted">-</span>
                  <?php elseif ((float)$r['qty'] <= 0): ?>
                    <span class="badge badge-light border text-muted px-2 py-1"><?php echo htmlspecialchars(t('expiry.out_of_stock', 'ໝົດສະຕັອກ')); ?></span>
                  <?php else: ?>
                    <span class="font-weight-bold text-dark"><?php echo number_format((float)$r['qty']); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center font-weight-bold text-dark" style="white-space: nowrap;"><i class="far fa-calendar-alt mr-1 text-muted"></i><?php echo date('d/m/Y', strtotime($r['expiry_date'])); ?></td>
                <td class="text-center exr-remaining" style="color: <?php echo $color; ?>;"><?php echo formatExpiryRemainingHtml($r['expiry_date']); ?></td>
                <td class="text-center" style="white-space: nowrap;"><?php echo $badge; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($total > 0): ?>
      <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap py-2 px-3 no-print" style="gap: 8px;">
        <small class="text-muted"><?php
          $from = $perPage > 0 ? ($page - 1) * $perPage + 1 : 1;
          $to = $perPage > 0 ? min($total, $page * $perPage) : $total;
          echo htmlspecialchars(sprintf(t('expiry.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'), number_format($from), number_format($to), number_format($total)));
        ?></small>
        <?php if ($pages > 1): ?>
          <ul class="pagination pagination-sm mb-0 ml-auto">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo expRepUrl(['page' => $page - 1]); ?>"><i class="fas fa-chevron-left" style="font-size: 0.72rem;"></i></a></li>
            <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
              <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>"><a class="page-link" href="<?php echo expRepUrl(['page' => $p]); ?>"><?php echo $p; ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo expRepUrl(['page' => $page + 1]); ?>"><i class="fas fa-chevron-right" style="font-size: 0.72rem;"></i></a></li>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
