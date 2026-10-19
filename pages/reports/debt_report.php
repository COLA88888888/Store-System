<?php
// Standalone Page: ລາຍງານໜີ້ລູກຄ້າ (Customer debt report)
// Read-only. Three views over customer_debts / customer_debt_payments:
//   outstanding — every bill still owed today, with its age (0-30 / 31-60 / 61-90 / 90+ days)
//   payments    — repayments received in the date range
//   new         — debts created in the date range that are still owed (a fully paid debt drops out)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('report') && !hasPermission('customer_debt')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT store_id, store_name, is_main FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$multiBranch = canViewAllBranches($pdo) && count($branchesList) > 1;
$filterStore = $multiBranch
    ? (isset($_GET['store_id']) && $_GET['store_id'] !== '' ? (int)$_GET['store_id'] : intval($_SESSION['store_id'] ?? 1))
    : getActiveStoreId($pdo);

$view = $_GET['view'] ?? 'outstanding';
if (!in_array($view, ['outstanding', 'payments', 'new'], true)) $view = 'outstanding';
$isDate = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };
$dateFrom = $isDate($_GET['from'] ?? null) ? $_GET['from'] : date('Y-m-01');
$dateTo   = $isDate($_GET['to'] ?? null) ? $_GET['to'] : date('Y-m-d');
if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
$search = trim($_GET['q'] ?? '');

$storeSql = $filterStore > 0 ? ' AND d.store_id = ' . (int)$filterStore : '';
$paySql   = $filterStore > 0 ? ' AND p.store_id = ' . (int)$filterStore : '';
$searchSql = '';
$searchParams = [];
if ($search !== '') {
    $searchSql = ' AND (d.customer_name LIKE ? OR d.invoice_number LIKE ? OR c.phone LIKE ?)';
    $like = '%' . $search . '%';
    $searchParams = [$like, $like, $like];
}

// Summary tiles
$sum = ['new' => 0, 'collected' => 0, 'outstanding' => 0, 'overdue' => 0];
try {
    $s = $pdo->prepare("SELECT COALESCE(SUM(d.balance), 0) FROM st_customer_debts d WHERE d.status IN ('unpaid','partial') AND DATE(d.created_at) BETWEEN ? AND ? $storeSql");
    $s->execute([$dateFrom, $dateTo]);
    $sum['new'] = (float)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM st_customer_debt_payments p WHERE DATE(p.paid_at) BETWEEN ? AND ? $paySql");
    $s->execute([$dateFrom, $dateTo]);
    $sum['collected'] = (float)$s->fetchColumn();
    $row = $pdo->query("SELECT COALESCE(SUM(d.balance), 0) AS outstanding,
                               COALESCE(SUM(CASE WHEN d.due_date IS NOT NULL AND d.due_date < CURDATE() THEN d.balance ELSE 0 END), 0) AS overdue
                        FROM st_customer_debts d WHERE d.status IN ('unpaid','partial') $storeSql")->fetch(PDO::FETCH_ASSOC);
    $sum['outstanding'] = (float)$row['outstanding'];
    $sum['overdue'] = (float)$row['overdue'];
} catch (Exception $e) {}

$rows = [];
$payChannels = [];
$aging =['0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
try {
    if ($view === 'outstanding') {
        $st = $pdo->prepare("SELECT d.*, c.phone, s.store_name, DATEDIFF(CURDATE(), DATE(d.created_at)) AS age_days
                             FROM st_customer_debts d
                             LEFT JOIN st_customers c ON c.customer_id = d.customer_id
                             LEFT JOIN st_tbstore s ON s.store_id = d.store_id
                             WHERE d.status IN ('unpaid','partial') $storeSql $searchSql
                             ORDER BY d.customer_name ASC, d.created_at ASC");
        $st->execute($searchParams);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $age = (int)$r['age_days'];
            $bucket = $age <= 30 ? '0_30' : ($age <= 60 ? '31_60' : ($age <= 90 ? '61_90' : '90_plus'));
            $aging[$bucket] += (float)$r['balance'];
        }
    } elseif ($view === 'payments') {
        $st = $pdo->prepare("SELECT p.*, d.customer_name, d.invoice_number, c.phone, s.store_name,
                                    b.bank_logo, b.bank_code, b.account_number AS bank_account_no
                             FROM st_customer_debt_payments p
                             LEFT JOIN st_customer_debts d ON d.id = p.debt_id
                             LEFT JOIN st_customers c ON c.customer_id = p.customer_id
                             LEFT JOIN st_tbstore s ON s.store_id = p.store_id
                             LEFT JOIN st_bank_accounts b ON b.id = p.bank_account_id
                             WHERE DATE(p.paid_at) BETWEEN ? AND ? $paySql $searchSql
                             ORDER BY p.paid_at DESC, p.id DESC");
        $st->execute(array_merge([$dateFrom, $dateTo], $searchParams));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        // Money received per channel: ເງິນສົດ, then each receiving bank (logo + name)
        foreach ($rows as &$r) {
            $r['bank_logo_url'] = !empty($r['bank_account_id']) ? resolveBankLogo($r['bank_logo'] ?? '', $r['bank_code'] ?? '') : '';
            $isCash = empty($r['bank_name']) && $r['payment_method'] === 'ເງິນສົດ';
            $key = $isCash ? 'cash' : ('bank:' . ($r['bank_account_id'] ?: $r['bank_name'] ?: 'other'));
            if (!isset($payChannels[$key])) {
                $payChannels[$key] = [
                    'label' => $isCash ? t('customer_debt.method_cash', 'ເງິນສົດ') : ($r['bank_name'] ?: $r['payment_method']),
                    'logo'  => $r['bank_logo_url'], 'cash' => $isCash, 'total' => 0, 'count' => 0,
                ];
            }
            $payChannels[$key]['total'] += (float)$r['amount'];
            $payChannels[$key]['count']++;
        }
        unset($r);
    } else {
        $st = $pdo->prepare("SELECT d.*, c.phone, s.store_name
                             FROM st_customer_debts d
                             LEFT JOIN st_customers c ON c.customer_id = d.customer_id
                             LEFT JOIN st_tbstore s ON s.store_id = d.store_id
                             WHERE d.status IN ('unpaid','partial') AND DATE(d.created_at) BETWEEN ? AND ? $storeSql $searchSql
                             ORDER BY d.created_at DESC, d.id DESC");
        $st->execute(array_merge([$dateFrom, $dateTo], $searchParams));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

// Footer totals cover every filtered row (not just the visible page)
$tot = $tA = $tP = $tB = 0;
foreach ($rows as $r) {
    if ($view === 'payments') {
        $tot += (float)$r['amount'];
    } else {
        $tA += (float)$r['amount'];
        $tP += (float)$r['paid_amount'];
        $tB += $r['status'] === 'cancelled' ? 0 : (float)$r['balance'];
    }
}

// Pagination (same controls as the other reports: ໂຊລາຍການ + circular page links)
$perPageRaw = (string)($_GET['per_page'] ?? '10');
if (!in_array($perPageRaw, ['5', '10', '25', '50', '100', 'all'], true)) $perPageRaw = '10';
$perPage = $perPageRaw === 'all' ? max(1, count($rows)) : (int)$perPageRaw;
$totalRecords = count($rows);
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);
$offset = ($page - 1) * $perPage;
$pageRows = array_slice($rows, $offset, $perPage);

$showBranchCol = $multiBranch && $filterStore === 0;
$storeLabel = t('debt_report.all_branches', '-- ທຸກສາຂາ --');
foreach ($branchesList as $b) if ((int)$b['store_id'] === $filterStore) $storeLabel = $b['store_name'];

$views = [
    'outstanding' => t('debt_report.view_outstanding', 'ໜີ້ຄ້າງຊຳລະ'),
    'payments'    => t('debt_report.view_payments', 'ປະຫວັດຮັບຊຳລະ'),
    'new'         => t('debt_report.view_new', 'ໜີ້ໃໝ່ໃນຊ່ວງວັນທີ'),
];
$viewIcons = [
    'outstanding' => ['fa-hourglass-half', '#d97706'],
    'payments'    => ['fa-money-bill-wave', '#16a34a'],
    'new'         => ['fa-file-invoice-dollar', '#0891b2'],
];
$statusLabels = [
    'unpaid'    => ['danger', t('customer_debt.status_unpaid', 'ຍັງບໍ່ຊຳລະ')],
    'partial'   => ['warning', t('customer_debt.status_partial', 'ຊຳລະບາງສ່ວນ')],
    'paid'      => ['success', t('customer_debt.status_paid', 'ຊຳລະຄົບ')],
    'cancelled' => ['muted', t('customer_debt.status_cancelled', 'ຍົກເລີກ')],
];

function debtRepUrl($over) {
    return 'debt_report.php?' . htmlspecialchars(http_build_query(array_merge($_GET, $over)));
}

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<style>
  .dbr { font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; color: #1e293b; }
  .dbr-title { font-size: 1.15rem; font-weight: 700; margin: 0; }
  .dbr-sub { font-size: 0.82rem; color: #64748b; }
  .dbr-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
  @media (max-width: 767.98px) { .dbr-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  .dbr-stat { position: relative; overflow: hidden; background: linear-gradient(135deg, var(--c), var(--d)); border-radius: 12px; padding: 14px 16px; color: #fff; box-shadow: 0 6px 16px rgba(15, 23, 42, .12); }
  .dbr-stat::after { content: ''; position: absolute; right: -24px; bottom: -34px; width: 110px; height: 110px; border-radius: 50%; background: rgba(255, 255, 255, .1); }
  .dbr-stat .lbl { font-size: 0.82rem; font-weight: 700; color: rgba(255, 255, 255, .95); }
  .dbr-stat .num { font-size: 1.4rem; font-weight: 800; line-height: 1.25; margin-top: 4px; word-break: break-all; }
  .dbr-stat .hint { font-size: 0.74rem; color: rgba(255, 255, 255, .85); margin-top: 4px; }
  @media (max-width: 767.98px) { .dbr-stat .num { font-size: 1.05rem; } }
  .dbr-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
  .dbr-filters { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
  .dbr-filters label { font-size: 0.76rem; color: #64748b; margin-bottom: 3px; display: block; }
  .dbr-filters .form-control, .dbr-filters .btn { height: 36px; border-radius: 8px; font-size: 0.86rem; }
  /* Tabs: Bootstrap nav-tabs — active tab white with a border, others plain blue text, coloured icon each */
  .dbr-navtabs { padding: 12px 14px 0; border-bottom: 1px solid #dee2e6; flex-wrap: wrap; }
  .dbr-navtabs .nav-link { font-weight: 700; font-size: 0.92rem; color: #1d6fd8; padding: 0.6rem 1rem; border: 1px solid transparent; border-radius: 8px 8px 0 0; }
  .dbr-navtabs .nav-link:hover { border-color: #e9ecef #e9ecef #dee2e6; color: #1558b0; }
  .dbr-navtabs .nav-link.active { color: #1e293b; background: #fff; border-color: #dee2e6 #dee2e6 #fff; }
  .dbr-aging { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; }
  @media (max-width: 767.98px) { .dbr-aging { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  .dbr-aging div { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; }
  .dbr-aging small { color: #64748b; display: block; font-size: 0.75rem; }
  .dbr-aging b { font-size: 0.98rem; }
  .dbr-channels { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; }
  .dbr-channel { display: flex; align-items: center; gap: 10px; min-width: 170px; padding: 8px 12px; border-radius: 8px;
    border: 1px solid #ddd6fe; background: #faf5ff; }
  .dbr-channel.is-cash { border-color: #bbf7d0; background: #f0fdf4; }
  .dbr-channel img { width: 30px; height: 30px; object-fit: contain; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; }
  .dbr-channel-ico { width: 30px; text-align: center; font-size: 1.15rem; color: #6d28d9; }
  .dbr-channel.is-cash .dbr-channel-ico { color: #16a34a; }
  .dbr-channel small { display: block; color: #64748b; font-size: 0.75rem; }
  .dbr-channel b { font-size: 0.98rem; }
  .dbr-paybank { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
  .dbr-paybank img { width: 20px; height: 20px; object-fit: contain; border-radius: 4px; border: 1px solid #e2e8f0; background: #fff; }
  /* Same look as the other report tables (financial / all sales): white header, 2px rule, bold dark text */
  .dbr-table { margin: 0; font-size: 0.88rem; }
  .dbr-table thead th { background: #ffffff; color: #1e293b; font-size: 0.86rem; font-weight: 700; border-top: 0; border-bottom: 2px solid #e2e8f0; padding: 0.85rem 0.75rem; white-space: nowrap; }
  .dbr-table td { padding: 0.75rem; vertical-align: middle; border-top: 1px solid #f1f5f9; }
  .dbr-table tbody tr:hover { background: #f8fafc; }
  .dbr-table tfoot td { font-weight: 700; background: #f8fafc; color: #0f172a; border-top: 2px solid #cbd5e1; padding: 0.85rem 0.75rem; }
  .dbr-pager { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding: 12px 14px; border-top: 1px solid #e2e8f0; background: #fff; border-radius: 0 0 10px 10px; }
  .dbr-money { text-align: right; white-space: nowrap; }
  .dbr-meta { font-size: 0.76rem; color: #94a3b8; }
  .dbr-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.76rem; font-weight: 600; white-space: nowrap; }
  .dbr-pill.danger { background: #fef2f2; color: #b91c1c; }
  .dbr-pill.warning { background: #fffbeb; color: #b45309; }
  .dbr-pill.success { background: #f0fdf4; color: #15803d; }
  .dbr-pill.muted { background: #f1f5f9; color: #64748b; }
  .print-only { display: none; }
  @media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .container-fluid { padding: 0 !important; }
    .dbr-panel { border: none !important; }
    .dbr-table { font-size: 11px !important; }
  }
</style>

<div class="container-fluid p-3 p-md-4 dbr">
  <div class="d-flex justify-content-between align-items-start mb-3 no-print" style="gap: 12px;">
    <div>
      <h5 class="dbr-title"><i class="fas fa-hand-holding-usd text-danger mr-1"></i> <?php echo htmlspecialchars(t('debt_report.title', 'ລາຍງານໜີ້ລູກຄ້າ')); ?></h5>
      <div class="dbr-sub"><?php echo htmlspecialchars(t('debt_report.subtitle', 'ໜີ້ຄ້າງ, ການຮັບຊຳລະ ແລະ ໜີ້ໃໝ່ຂອງລູກຄ້າ')); ?></div>
    </div>
    <button type="button" class="btn btn-light border btn-sm px-3" onclick="window.print()" style="border-radius: 8px; height: 36px; white-space: nowrap;">
      <i class="fas fa-print mr-1 text-muted"></i> <?php echo htmlspecialchars(t('debt_report.print_btn', 'ພິມລາຍງານ')); ?>
    </button>
  </div>

  <div class="print-only mb-3">
    <h4 class="font-weight-bold mb-1"><?php echo htmlspecialchars(t('debt_report.title', 'ລາຍງານໜີ້ລູກຄ້າ')); ?> — <?php echo htmlspecialchars($views[$view]); ?></h4>
    <div><?php echo htmlspecialchars($storeLabel); ?>
      <?php if ($view !== 'outstanding'): ?> · <?php echo date('d/m/Y', strtotime($dateFrom)); ?> – <?php echo date('d/m/Y', strtotime($dateTo)); ?><?php endif; ?>
      · <?php echo htmlspecialchars(t('debt_report.printed_at', 'ວັນທີພິມ')); ?>: <?php echo date('d/m/Y H:i'); ?></div>
  </div>

  <div class="dbr-stats mb-3">
    <?php
      $range = date('d/m/Y', strtotime($dateFrom)) . ' – ' . date('d/m/Y', strtotime($dateTo));
      $tiles = [
        [t('debt_report.card_new', 'ໜີ້ໃໝ່ (ຊ່ວງວັນທີ)'), $sum['new'], $range, '#2c5aa0', '#1e3a70'],
        [t('debt_report.card_collected', 'ຮັບຊຳລະ (ຊ່ວງວັນທີ)'), $sum['collected'], $range, '#16a34a', '#15803d'],
        [t('debt_report.card_outstanding', 'ໜີ້ຄ້າງທັງໝົດ (ປັດຈຸບັນ)'), $sum['outstanding'], t('debt_report.as_of_today', 'ຮອດມື້ນີ້'), '#dc2626', '#991b1b'],
        [t('debt_report.card_overdue', 'ໜີ້ກາຍກຳນົດ'), $sum['overdue'], t('debt_report.as_of_today', 'ຮອດມື້ນີ້'), '#d97706', '#b45309'],
      ];
      foreach ($tiles as $tile):
    ?>
      <div class="dbr-stat" style="--c: <?php echo $tile[3]; ?>; --d: <?php echo $tile[4]; ?>;">
        <div class="lbl"><?php echo htmlspecialchars($tile[0]); ?></div>
        <div class="num"><?php echo formatCurrency($tile[1]); ?></div>
        <div class="hint"><?php echo htmlspecialchars($tile[2]); ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="dbr-panel">
    <ul class="nav nav-tabs dbr-navtabs no-print">
      <?php foreach ($views as $key => $label): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo $view === $key ? 'active' : ''; ?>" href="<?php echo debtRepUrl(['view' => $key, 'page' => 1]); ?>">
            <i class="fas <?php echo $viewIcons[$key][0]; ?> mr-1" style="color: <?php echo $viewIcons[$key][1]; ?>;"></i><?php echo htmlspecialchars($label); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <form method="GET" action="debt_report.php" class="dbr-filters no-print">
      <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
      <div style="flex: 2 1 200px;">
        <label><?php echo htmlspecialchars(t('debt_report.search_label', 'ຄົ້ນຫາ:')); ?></label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="<?php echo htmlspecialchars(t('customer_debt.search_placeholder', 'ຊື່ລູກຄ້າ, ເບີໂທ, ເລກບິນ...')); ?>">
      </div>
      <?php if ($view !== 'outstanding'): ?>
      <div style="flex: 1 1 140px;">
        <label><?php echo htmlspecialchars(t('debt_report.from', 'ແຕ່ວັນທີ:')); ?></label>
        <input type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-control">
      </div>
      <div style="flex: 1 1 140px;">
        <label><?php echo htmlspecialchars(t('debt_report.to', 'ຫາວັນທີ:')); ?></label>
        <input type="date" name="to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-control">
      </div>
      <?php else: ?>
        <input type="hidden" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>">
        <input type="hidden" name="to" value="<?php echo htmlspecialchars($dateTo); ?>">
      <?php endif; ?>
      <?php if ($multiBranch): ?>
      <div style="flex: 1 1 150px;">
        <label><?php echo htmlspecialchars(t('debt_report.branch_label', 'ສາຂາ:')); ?></label>
        <select name="store_id" class="form-control" onchange="this.form.submit()">
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo (int)$b['store_id']; ?>" <?php echo $filterStore === (int)$b['store_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['store_name']); ?></option>
          <?php endforeach; ?>
          <option value="0" <?php echo $filterStore === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('debt_report.all_branches', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php endif; ?>
      <div style="flex: 0 1 110px;">
        <label><?php echo htmlspecialchars(t('reports.per_page_label', 'ໂຊລາຍການ:')); ?></label>
        <select name="per_page" class="form-control" onchange="this.form.submit()">
          <?php foreach (['5', '10', '25', '50', '100', 'all'] as $pp): ?>
            <option value="<?php echo $pp; ?>" <?php echo $perPageRaw === $pp ? 'selected' : ''; ?>><?php echo $pp === 'all' ? htmlspecialchars(t('reports.all_opt', 'ທັງໝົດ')) : $pp; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex" style="gap: 6px;">
        <button type="submit" class="btn btn-primary px-3" style="background: #244886; border-color: #244886;"><i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('debt_report.search_btn', 'ຄົ້ນຫາ')); ?></button>
        <a href="debt_report.php?view=<?php echo htmlspecialchars($view); ?>" class="btn btn-light border px-3 d-flex align-items-center"><i class="fas fa-redo text-muted"></i></a>
      </div>
    </form>

    <?php if ($view === 'payments' && !empty($payChannels)): ?>
      <!-- Collected in the date range, per channel (cash / each receiving bank) -->
      <div class="dbr-channels">
        <?php foreach ($payChannels as $ch): ?>
          <div class="dbr-channel <?php echo $ch['cash'] ? 'is-cash' : ''; ?>">
            <?php if ($ch['cash']): ?>
              <i class="fas fa-money-bill-wave dbr-channel-ico"></i>
            <?php elseif ($ch['logo']): ?>
              <img src="<?php echo htmlspecialchars($ch['logo']); ?>" alt="" onerror="this.style.display='none'">
            <?php else: ?>
              <i class="fas fa-university dbr-channel-ico"></i>
            <?php endif; ?>
            <div>
              <small><?php echo htmlspecialchars($ch['label']); ?> · <?php echo htmlspecialchars(sprintf(t('debt_report.times', '%s ຄັ້ງ'), number_format($ch['count']))); ?></small>
              <b><?php echo formatCurrency($ch['total']); ?></b>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($view === 'outstanding'): ?>
      <div class="dbr-aging">
        <div><small><?php echo htmlspecialchars(t('debt_report.age_0_30', 'ອາຍຸໜີ້ 0–30 ວັນ')); ?></small><b><?php echo formatCurrency($aging['0_30']); ?></b></div>
        <div><small><?php echo htmlspecialchars(t('debt_report.age_31_60', 'ອາຍຸໜີ້ 31–60 ວັນ')); ?></small><b><?php echo formatCurrency($aging['31_60']); ?></b></div>
        <div><small><?php echo htmlspecialchars(t('debt_report.age_61_90', 'ອາຍຸໜີ້ 61–90 ວັນ')); ?></small><b class="text-warning"><?php echo formatCurrency($aging['61_90']); ?></b></div>
        <div><small><?php echo htmlspecialchars(t('debt_report.age_90_plus', 'ອາຍຸໜີ້ ເກີນ 90 ວັນ')); ?></small><b class="text-danger"><?php echo formatCurrency($aging['90_plus']); ?></b></div>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 text-nowrap dbr-table">
        <?php if ($view === 'payments'): ?>
          <thead><tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_date', 'ວັນທີ')); ?></th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_customer', 'ລູກຄ້າ')); ?></th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_invoice', 'ເລກບິນ')); ?></th>
            <?php if ($showBranchCol): ?><th><?php echo htmlspecialchars(t('debt_report.col_branch', 'ສາຂາ')); ?></th><?php endif; ?>
            <th><?php echo htmlspecialchars(t('customer_debt.col_method', 'ຊ່ອງທາງ')); ?></th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_received_by', 'ຜູ້ຮັບ')); ?></th>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_amount', 'ຈຳນວນເງິນ')); ?></th>
          </tr></thead>
          <tbody>
            <?php foreach ($pageRows as $i => $r): ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?php echo $offset + $i + 1; ?></td>
                <td style="white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($r['paid_at'])); ?></td>
                <td><b><?php echo htmlspecialchars($r['customer_name'] ?? '-'); ?></b><?php if (!empty($r['phone'])): ?><div class="dbr-meta"><?php echo htmlspecialchars($r['phone']); ?></div><?php endif; ?></td>
                <td><?php echo $r['invoice_number'] ? htmlspecialchars($r['invoice_number']) : '<span class="dbr-pill muted">' . htmlspecialchars(t('customer_debt.opening_balance', 'ໜີ້ຍົກມາ')) . '</span>'; ?></td>
                <?php if ($showBranchCol): ?><td class="text-muted"><?php echo htmlspecialchars($r['store_name'] ?? '-'); ?></td><?php endif; ?>
                <td>
                  <?php if (empty($r['bank_name']) && $r['payment_method'] === 'ເງິນສົດ'): ?>
                    <span class="dbr-paybank text-success"><i class="fas fa-money-bill-wave"></i><?php echo htmlspecialchars(t('customer_debt.method_cash', 'ເງິນສົດ')); ?></span>
                  <?php else: ?>
                    <span class="dbr-paybank">
                      <?php if ($r['bank_logo_url']): ?><img src="<?php echo htmlspecialchars($r['bank_logo_url']); ?>" alt="" onerror="this.style.display='none'"><?php else: ?><i class="fas fa-university text-primary"></i><?php endif; ?>
                      <b><?php echo htmlspecialchars($r['bank_name'] ?: $r['payment_method']); ?></b>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-muted"><?php echo htmlspecialchars($r['received_by'] ?? ''); ?></td>
                <td class="dbr-money font-weight-bold text-success"><?php echo formatCurrency($r['amount']); ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="<?php echo $showBranchCol ? 8 : 7; ?>" class="text-center py-5 text-muted"><?php echo htmlspecialchars(t('debt_report.empty', 'ບໍ່ພົບລາຍການ')); ?></td></tr><?php endif; ?>
          </tbody>
          <?php if (!empty($rows)): ?>
            <tfoot><tr><td colspan="<?php echo $showBranchCol ? 7 : 6; ?>" class="text-right"><?php echo htmlspecialchars(t('debt_report.total', 'ລວມ')); ?></td><td class="dbr-money text-success"><?php echo formatCurrency($tot); ?></td></tr></tfoot>
          <?php endif; ?>

        <?php else: ?>
          <thead><tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_date', 'ວັນທີ')); ?></th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_customer', 'ລູກຄ້າ')); ?></th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_invoice', 'ເລກບິນ')); ?></th>
            <?php if ($showBranchCol): ?><th><?php echo htmlspecialchars(t('debt_report.col_branch', 'ສາຂາ')); ?></th><?php endif; ?>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_total', 'ຍອດໜີ້')); ?></th>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_paid', 'ຊຳລະແລ້ວ')); ?></th>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_balance', 'ຄົງເຫຼືອ')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars($view === 'outstanding' ? t('debt_report.col_age', 'ອາຍຸໜີ້') : t('customer_debt.col_status', 'ສະຖານະ')); ?></th>
          </tr></thead>
          <tbody>
            <?php foreach ($pageRows as $i => $r):
              $overdue = !empty($r['due_date']) && $r['due_date'] < date('Y-m-d') && in_array($r['status'], ['unpaid', 'partial'], true);
            ?>
              <tr<?php echo $r['status'] === 'cancelled' ? ' style="opacity:.55"' : ''; ?>>
                <td class="text-center font-weight-bold text-muted"><?php echo $offset + $i + 1; ?></td>
                <td style="white-space: nowrap;"><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                <td><b><?php echo htmlspecialchars($r['customer_name'] ?? '-'); ?></b><?php if (!empty($r['phone'])): ?><div class="dbr-meta"><?php echo htmlspecialchars($r['phone']); ?></div><?php endif; ?></td>
                <td><?php echo $r['invoice_number'] ? htmlspecialchars($r['invoice_number']) : '<span class="dbr-pill muted">' . htmlspecialchars(t('customer_debt.opening_balance', 'ໜີ້ຍົກມາ')) . '</span>'; ?>
                  <?php if (!empty($r['note'])): ?><div class="dbr-meta"><?php echo htmlspecialchars($r['note']); ?></div><?php endif; ?></td>
                <?php if ($showBranchCol): ?><td class="text-muted"><?php echo htmlspecialchars($r['store_name'] ?? '-'); ?></td><?php endif; ?>
                <td class="dbr-money"><?php echo formatCurrency($r['amount']); ?></td>
                <td class="dbr-money text-success"><?php echo formatCurrency($r['paid_amount']); ?></td>
                <td class="dbr-money font-weight-bold <?php echo in_array($r['status'], ['unpaid', 'partial'], true) ? 'text-danger' : ''; ?>"><?php echo formatCurrency($r['status'] === 'cancelled' ? 0 : $r['balance']); ?></td>
                <td class="text-center" style="white-space: nowrap;">
                  <?php if ($view === 'outstanding'): ?>
                    <?php $age = (int)$r['age_days']; ?>
                    <span class="dbr-pill <?php echo $age > 90 ? 'danger' : ($age > 60 ? 'warning' : 'muted'); ?>"><?php echo htmlspecialchars(sprintf(t('debt_report.days', '%s ວັນ'), $age)); ?></span>
                  <?php else: ?>
                    <span class="dbr-pill <?php echo $statusLabels[$r['status']][0] ?? 'muted'; ?>"><?php echo htmlspecialchars($statusLabels[$r['status']][1] ?? $r['status']); ?></span>
                  <?php endif; ?>
                  <?php if ($overdue): ?><div><span class="dbr-pill warning"><?php echo htmlspecialchars(t('customer_debt.tab_overdue', 'ກາຍກຳນົດ')); ?></span></div><?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?><tr><td colspan="<?php echo $showBranchCol ? 9 : 8; ?>" class="text-center py-5 text-muted"><?php echo htmlspecialchars(t('debt_report.empty', 'ບໍ່ພົບລາຍການ')); ?></td></tr><?php endif; ?>
          </tbody>
          <?php if (!empty($rows)): ?>
            <tfoot><tr>
              <td colspan="<?php echo $showBranchCol ? 5 : 4; ?>" class="text-right"><?php echo htmlspecialchars(t('debt_report.total', 'ລວມ')); ?></td>
              <td class="dbr-money"><?php echo formatCurrency($tA); ?></td>
              <td class="dbr-money text-success"><?php echo formatCurrency($tP); ?></td>
              <td class="dbr-money text-danger"><?php echo formatCurrency($tB); ?></td>
              <td></td>
            </tr></tfoot>
          <?php endif; ?>
        <?php endif; ?>
      </table>
    </div>

    <!-- Footer (always shown, even with one page / no rows): record range + circular blue pagination (same as the other reports) -->
    <div class="dbr-pager no-print">
      <small class="text-muted font-weight-bold">
        <?php echo htmlspecialchars(sprintf(t('debt_report.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'),
            number_format($totalRecords ? $offset + 1 : 0), number_format(min($offset + $perPage, $totalRecords)), number_format($totalRecords))); ?>
      </small>
      <nav aria-label="Page navigation">
        <ul class="pagination report-pagination mb-0">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo debtRepUrl(['page' => max(1, $page - 1)]); ?>"><i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i></a>
          </li>
          <?php
            $startP = max(1, $page - 2);
            $endP   = min($totalPages, $page + 2);
            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="' . debtRepUrl(['page' => 1]) . '">1</a></li>';
                if ($startP > 2) echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
            }
            for ($p = $startP; $p <= $endP; $p++) {
                echo '<li class="page-item ' . ($p === $page ? 'active' : '') . '"><a class="page-link" href="' . debtRepUrl(['page' => $p]) . '">' . $p . '</a></li>';
            }
            if ($endP < $totalPages) {
                if ($endP < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                echo '<li class="page-item"><a class="page-link" href="' . debtRepUrl(['page' => $totalPages]) . '">' . $totalPages . '</a></li>';
            }
          ?>
          <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo debtRepUrl(['page' => min($totalPages, $page + 1)]); ?>"><i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i></a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
