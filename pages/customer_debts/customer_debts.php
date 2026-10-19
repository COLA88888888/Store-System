<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າລູກຄ້າຕິດໜີ້: ບັດສະຫຼຸບ, ລາຍຊື່ລູກຄ້າທີ່ຕິດໜີ້, popup ລາຍລະອຽດ/ຮັບຊຳລະ/ເພີ່ມໜີ້ຍົກມາ.
 *   - partials/styles.php : CSS ຂອງໜ້າ
 *   - partials/modals.php : Popup ທັງໝົດ
 *   - partials/debts_js.php : JS ຂອງໜ້າ
 */
// Page: ລູກຄ້າຕິດໜີ້ (Customer debts)
// One row per customer who owes money — bills sold on credit at the POS plus opening balances
// entered here. Repayments are taken from this page (per bill, or oldest-first across all bills).
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../api/customer_debts_backend.php';

$canAddDebt    = hasPermission('customer_debt', 'add');
$canPayDebt    = hasPermission('customer_debt', 'edit');
$canCancelDebt = hasPermission('customer_debt', 'delete');

$statusTabs = [
    'open'    => t('customer_debt.tab_open', 'ຍັງຄ້າງຊຳລະ'),
    'overdue' => t('customer_debt.tab_overdue', 'ກາຍກຳນົດ'),
    'paid'    => t('customer_debt.tab_paid', 'ຊຳລະຄົບແລ້ວ'),
    'all'     => t('customer_debt.tab_all', 'ທັງໝົດ'),
];

require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- Same look as the sales list (pages/reports/item_sales.php): filter box, KPI cards, report table -->
<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<?php require __DIR__ . '/partials/styles.php'; /* CSS ຂອງໜ້າ */ ?>

<div class="container-fluid p-3 p-md-4 cdb">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
    <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      <i class="fas fa-hand-holding-usd mr-2" style="color: #dc2626;"></i> <?php echo htmlspecialchars(t('customer_debt.title', 'ລູກຄ້າຕິດໜີ້')); ?>
    </h5>
    <?php if ($canAddDebt): ?>
      <button type="button" class="btn btn-danger btn-sm font-weight-bold px-3" style="border-radius: 8px; height: 38px;" onclick="openAddDebt()">
        <i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.btn_add', 'ເພີ່ມໜີ້ຍົກມາ')); ?>
      </button>
    <?php endif; ?>
  </div>

  <!-- Search & filter (one row, like reports_filter.php) -->
  <div class="report-filter-box mb-3" style="padding: 12px 16px;">
    <form method="GET" style="display: flex; align-items: flex-end; flex-wrap: wrap; gap: 8px; width: 100%;">
      <div style="flex: 3 1 0; min-width: 200px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-search text-primary mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.search_label_full', 'ຄົ້ນຫາ (ຊື່ / ເບີໂທ / ເລກບິນ):')); ?>
        </label>
        <!-- Filters the list as you type (name / phone / code); Enter searches the server (bill numbers too) -->
        <input type="text" name="q" id="cdbSearch" value="<?php echo htmlspecialchars($debtSearch); ?>" class="form-control form-control-sm" autocomplete="off" placeholder="<?php echo htmlspecialchars(t('customer_debt.search_placeholder', 'ຊື່ລູກຄ້າ, ເບີໂທ, ເລກບິນ...')); ?>" style="border-radius: 8px; height: 38px; font-size: 0.85rem; width: 100%;">
      </div>
      <div style="flex: 1 1 0; min-width: 160px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-list text-primary mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.status_label', 'ສະຖານະ:')); ?>
        </label>
        <select name="status" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #2563eb; color: #1e40af; background: #eff6ff; width: 100%;">
          <?php foreach ($statusTabs as $key => $label): ?>
            <option value="<?php echo $key; ?>" <?php echo $debtStatus === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex: 1 1 0; min-width: 100px;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-layer-group text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.per_page_label', 'ໂຊລາຍການ:')); ?>
        </label>
        <select name="per_page" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <?php foreach (['10', '25', '50', '100', 'all'] as $pp): ?>
            <option value="<?php echo $pp; ?>" <?php echo $debtPerPageRaw === $pp ? 'selected' : ''; ?>><?php echo $pp === 'all' ? htmlspecialchars(t('reports.all_opt', 'ທັງໝົດ')) : $pp; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="d-flex align-items-center flex-wrap" style="flex: 0 1 auto; gap: 6px;">
        <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); white-space: nowrap; font-size: 0.85rem;">
          <i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('customer_debt.search_btn', 'ຄົ້ນຫາ')); ?>
        </button>
        <a href="customer_debts.php" class="btn btn-light btn-sm border font-weight-bold d-inline-flex align-items-center justify-content-center px-2" style="border-radius: 8px; height: 38px;" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>">
          <i class="fas fa-redo"></i>
        </a>
      </div>
    </form>
  </div>

  <!-- KPI cards (reports_kpi.php style); the first three open the matching list -->
  <div class="kpi-cards-grid" style="margin-bottom: 20px;">
    <?php
      // [label, value, icon, gradient from, gradient to, status it opens (null = none)]
      $tiles = [
        [t('customer_debt.card_outstanding', 'ໜີ້ຄ້າງທັງໝົດ'), formatCurrency($debtSummary['outstanding']), 'fa-file-invoice-dollar', '#f43f5e', '#be123c', 'open'],
        [t('customer_debt.card_customers', 'ລູກຄ້າທີ່ຕິດໜີ້'), number_format((int)$debtSummary['customers']) . ' ' . t('customer_debt.unit_people', 'ຄົນ'), 'fa-users', '#2563eb', '#1d4ed8', 'open'],
        [t('customer_debt.card_overdue', 'ໜີ້ກາຍກຳນົດ'), formatCurrency($debtSummary['overdue']), 'fa-exclamation-triangle', '#f59e0b', '#b45309', 'overdue'],
        [t('customer_debt.card_collected_today', 'ຮັບຊຳລະມື້ນີ້'), formatCurrency($debtSummary['collected_today']), 'fa-coins', '#10b981', '#047857', null],
      ];
      foreach ($tiles as $tile):
        $tileTag = $tile[5] ? 'a' : 'div';
    ?>
      <<?php echo $tileTag; ?> class="kpi-card-item d-flex align-items-center justify-content-between text-white<?php echo $tile[5] ? ' cdb-kpi-link' : ''; ?>" style="background: linear-gradient(135deg, <?php echo $tile[3]; ?> 0%, <?php echo $tile[4]; ?> 100%);"<?php if ($tile[5]): ?> href="?<?php echo htmlspecialchars(http_build_query(['status' => $tile[5]])); ?>"<?php endif; ?>>
        <div style="z-index: 2; min-width: 0;">
          <div class="kpi-card-title"><?php echo htmlspecialchars($tile[0]); ?></div>
          <div class="font-weight-bold mt-1 text-white kpi-card-val" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;"><?php echo htmlspecialchars($tile[1]); ?></div>
        </div>
        <div class="kpi-card-icon"><i class="fas <?php echo $tile[2]; ?>"></i></div>
      </<?php echo $tileTag; ?>>
    <?php endforeach; ?>
  </div>

  <div class="report-table-card card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 100%;">
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.85rem; min-width: 720px;">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th><?php echo htmlspecialchars(t('customer_debt.col_customer', 'ລູກຄ້າ')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars(t('customer_debt.col_bills', 'ບິນຄ້າງ')); ?></th>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_total', 'ຍອດໜີ້')); ?></th>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_paid', 'ຊຳລະແລ້ວ')); ?></th>
            <th class="text-right"><?php echo htmlspecialchars(t('customer_debt.col_balance', 'ຄົງເຫຼືອ')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars(t('customer_debt.col_oldest', 'ຕິດໜີ້ແຕ່')); ?></th>
            <th class="text-center"><?php echo htmlspecialchars(t('customer_debt.col_actions', 'ຈັດການ')); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($debtCustomers)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">
              <i class="fas fa-hand-holding-usd fa-2x mb-2 d-block" style="color: #cbd5e1;"></i>
              <?php echo htmlspecialchars(t('customer_debt.empty', 'ບໍ່ມີລາຍການໜີ້')); ?>
            </td></tr>
          <?php else: foreach ($debtCustomers as $i => $c): ?>
            <?php
              $bal = (float)$c['balance'];
              $tot = (float)$c['total_amount'];
              $paidPct = $tot > 0 ? max(0, min(100, round((float)$c['paid_amount'] / $tot * 100))) : 0;
              $oldestDays = $c['oldest_open'] ? (int)floor((strtotime(date('Y-m-d')) - strtotime(substr($c['oldest_open'], 0, 10))) / 86400) : null;
              $searchText = mb_strtolower(trim(($c['customer_name'] ?? '') . ' ' . ($c['phone'] ?? '') . ' ' . ($c['customer_code'] ?? '')));
            ?>
            <!-- Click anywhere on the row → the customer's bills -->
            <tr class="cdb-row" data-search="<?php echo htmlspecialchars($searchText); ?>" onclick="if (!$(event.target).closest('button').length) openDebtDetail(<?php echo (int)$c['customer_id']; ?>)">
              <td class="text-center text-muted"><?php echo $debtOffset + $i + 1; ?></td>
              <td>
                <div class="cdb-name"><?php echo htmlspecialchars($c['customer_name'] ?? '-'); ?></div>
                <div class="cdb-meta">
                  <?php echo htmlspecialchars($c['customer_code'] ?? ''); ?>
                  <?php if (!empty($c['phone'])): ?> · <i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($c['phone']); ?><?php endif; ?>
                </div>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <b><?php echo (int)$c['open_bills']; ?></b> <?php echo htmlspecialchars(t('customer_debt.unit_bills', 'ບິນ')); ?>
                <div class="cdb-meta"><?php echo htmlspecialchars(sprintf(t('customer_debt.bills_of_total', 'ຈາກທັງໝົດ %s ບິນ'), (int)$c['bills'])); ?></div>
              </td>
              <td class="cdb-money"><?php echo formatCurrency($c['total_amount']); ?></td>
              <td class="cdb-money text-success"><?php echo formatCurrency($c['paid_amount']); ?></td>
              <td class="cdb-money">
                <?php if ($bal > 0): ?>
                  <span class="font-weight-bold text-danger" style="font-size: 1rem;"><?php echo formatCurrency($bal); ?></span>
                  <!-- How much of the debt has been paid back -->
                  <div class="cdb-progress" title="<?php echo htmlspecialchars(sprintf(t('customer_debt.paid_pct', 'ຊຳລະແລ້ວ %s%%'), $paidPct)); ?>"><span style="width: <?php echo $paidPct; ?>%;"></span></div>
                  <div class="cdb-meta"><?php echo htmlspecialchars(sprintf(t('customer_debt.paid_pct', 'ຊຳລະແລ້ວ %s%%'), $paidPct)); ?></div>
                  <?php if ((float)$c['overdue'] > 0): ?><div><span class="cdb-pill warning"><i class="fas fa-exclamation-triangle mr-1"></i><?php echo htmlspecialchars(t('customer_debt.tab_overdue', 'ກາຍກຳນົດ')); ?> <?php echo formatCurrency($c['overdue']); ?></span></div><?php endif; ?>
                <?php else: ?>
                  <span class="cdb-pill success"><i class="fas fa-check mr-1"></i><?php echo htmlspecialchars(t('customer_debt.status_paid', 'ຊຳລະຄົບ')); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <?php if ($c['oldest_open']): ?>
                  <?php echo date('d/m/Y', strtotime($c['oldest_open'])); ?>
                  <div class="cdb-meta"><?php echo $oldestDays <= 0 ? htmlspecialchars(t('customer_debt.today', 'ມື້ນີ້')) : htmlspecialchars(sprintf(t('customer_debt.days_ago', '%s ມື້ກ່ອນ'), $oldestDays)); ?></div>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td class="text-center" style="white-space: nowrap;">
                <div class="cdb-actions">
                  <?php if ($canPayDebt && $bal > 0): ?>
                    <button type="button" class="cdb-act cdb-act-icon cdb-act-box cdb-act-pay" onclick="openPayDebt(<?php echo (int)$c['customer_id']; ?>, 0, <?php echo htmlspecialchars(json_encode($c['customer_name'] ?? ''), ENT_QUOTES); ?>, '', <?php echo $bal; ?>)" title="<?php echo htmlspecialchars(t('customer_debt.btn_receive', 'ຮັບເງິນ')); ?>">
                      <i class="fas fa-money-bill-wave"></i>
                    </button>
                  <?php endif; ?>
                  <button type="button" class="cdb-act cdb-act-icon cdb-act-box cdb-act-detail" onclick="openDebtDetail(<?php echo (int)$c['customer_id']; ?>)" title="<?php echo htmlspecialchars(t('customer_debt.btn_view_bills', 'ເບິ່ງບິນ')); ?>">
                    <i class="fas fa-list-ul"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          <tr id="cdbNoMatch" style="display: none;"><td colspan="8" class="text-center py-4 text-muted"><?php echo htmlspecialchars(t('customer_debt.no_match', 'ບໍ່ພົບລູກຄ້າທີ່ຄົ້ນຫາ — ກົດ Enter ເພື່ອຄົ້ນຫາຕາມເລກບິນ')); ?></td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination (always shown, same circular style as reports_pagination.php) -->
    <?php
      $debtPageUrl = function ($p) use ($debtStatus, $debtSearch, $debtPerPageRaw) {
          return '?' . htmlspecialchars(http_build_query(['status' => $debtStatus, 'q' => $debtSearch, 'per_page' => $debtPerPageRaw, 'page' => $p]));
      };
      $pgStart = max(1, $debtPage - 2);
      $pgEnd   = min($debtTotalPages, $debtPage + 2);
    ?>
    <div class="card-footer bg-white border-top py-3 px-3 d-flex align-items-center justify-content-end flex-wrap" style="gap: 12px; border-radius: 0 0 14px 14px;">
      <small class="text-muted font-weight-bold ml-auto">
        <?php echo htmlspecialchars(sprintf(t('debt_report.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'),
            $debtTotalRows ? $debtOffset + 1 : 0, $debtOffset + count($debtCustomers), $debtTotalRows)); ?>
      </small>
      <nav aria-label="Page navigation">
        <ul class="pagination report-pagination mb-0 justify-content-end">
          <li class="page-item <?php echo $debtPage <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $debtPageUrl(max(1, $debtPage - 1)); ?>" aria-label="Previous"><i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i></a>
          </li>
          <?php if ($pgStart > 1): ?>
            <li class="page-item"><a class="page-link" href="<?php echo $debtPageUrl(1); ?>">1</a></li>
            <?php if ($pgStart > 2): ?><li class="page-item disabled"><span class="page-link" style="border: none;">...</span></li><?php endif; ?>
          <?php endif; ?>
          <?php for ($p = $pgStart; $p <= $pgEnd; $p++): ?>
            <li class="page-item <?php echo $p === $debtPage ? 'active' : ''; ?>"><a class="page-link" href="<?php echo $debtPageUrl($p); ?>"><?php echo $p; ?></a></li>
          <?php endfor; ?>
          <?php if ($pgEnd < $debtTotalPages): ?>
            <?php if ($pgEnd < $debtTotalPages - 1): ?><li class="page-item disabled"><span class="page-link" style="border: none;">...</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?php echo $debtPageUrl($debtTotalPages); ?>"><?php echo $debtTotalPages; ?></a></li>
          <?php endif; ?>
          <li class="page-item <?php echo $debtPage >= $debtTotalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $debtPageUrl(min($debtTotalPages, $debtPage + 1)); ?>" aria-label="Next"><i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i></a>
          </li>
        </ul>
      </nav>
    </div>
  </div>

  <!-- Detail modal -->
<?php require __DIR__ . '/partials/modals.php'; /* Popup ທັງໝົດ */ ?>
<?php require __DIR__ . '/partials/debts_js.php'; /* JS ຂອງໜ້າ */ ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
