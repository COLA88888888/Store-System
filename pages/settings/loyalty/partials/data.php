<?php
/**
 * ສ່ວນ: ຂໍ້ມູນຂອງໜ້າ
 * ການຕັ້ງຄ່າ, ລາງວັນ, ລູກຄ້າຄະແນນສູງສຸດ, ປະຫວັດຄະແນນ ແລະ ຟັງຊັນ lpPager (pagination).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/loyalty/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ===== Data for the page =====
// ກົດການໄດ້ຄະແນນຂອງສາຂາທີ່ເລືອກ (loyaltySetting: ສາຂາທີ່ຍັງບໍ່ເຄີຍບັນທຶກ ໃຊ້ຄ່າລວມເດີມ)
$lpEnabled = loyaltySetting($pdo, 'loyalty_enabled', $activeStoreId, '0') === '1';
$lpSpend   = (int)loyaltySetting($pdo, 'loyalty_spend_amount', $activeStoreId, '100000');
$lpPoints  = (int)loyaltySetting($pdo, 'loyalty_points_earned', $activeStoreId, '1');
if ($lpSpend < 1)  $lpSpend  = 100000;
if ($lpPoints < 1) $lpPoints = 1;

$loyaltyRewards = [];
try {
    $rStmt = $pdo->prepare("
        SELECT r.*, p.product_name, p.unit, p.price AS product_price
        FROM st_loyalty_rewards r
        LEFT JOIN st_products p ON (r.reward_type = 'product' AND p.product_id = r.product_id AND p.store_id = ?)
        WHERE r.store_id = ?
        ORDER BY r.points_cost ASC, r.id ASC
    ");
    $rStmt->execute([$activeStoreId, $activeStoreId]);
    $loyaltyRewards = $rStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$rewardProducts = [];
try {
    $rpStmt = $pdo->prepare("SELECT product_id, product_name, unit, price FROM st_products WHERE store_id = ? ORDER BY product_name ASC");
    $rpStmt->execute([$activeStoreId]);
    $rewardProducts = $rpStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ຄະແນນແຍກແຕ່ລະສາຂາ ບໍ່ລວມກັນ: ລູກຄ້າ ແລະ ປະຫວັດ ສະແດງສະເພາະສາຂາທີ່ເລືອກ (ສາຂາຫຼັກປ່ຽນສາຂາດ້ວຍຕົວເລືອກສາຂາ)
$custScopeSql = 'c.store_id = :sid';
$custScopeParams = [':sid' => $activeStoreId];

// Both lists page independently: ?cpage= for customers, ?hpage= for the history.
$lpPerPage = 10;
$cPage = max(1, (int)($_GET['cpage'] ?? 1));
$hPage = max(1, (int)($_GET['hpage'] ?? 1));
$topCustomers = []; $recentLog = [];
$topCustomersTotal = 0; $recentLogTotal = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM st_customers c WHERE c.points > 0 AND $custScopeSql");
    $s->execute($custScopeParams);
    $topCustomersTotal = (int)$s->fetchColumn();
    $cPage = min($cPage, max(1, (int)ceil($topCustomersTotal / $lpPerPage)));

    $s = $pdo->prepare("SELECT c.customer_id, c.customer_code, c.customer_name, c.phone, c.points FROM st_customers c WHERE c.points > 0 AND $custScopeSql ORDER BY c.points DESC, c.customer_name ASC LIMIT $lpPerPage OFFSET " . (($cPage - 1) * $lpPerPage));
    $s->execute($custScopeParams);
    $topCustomers = $s->fetchAll(PDO::FETCH_ASSOC);

    $s = $pdo->prepare("SELECT COUNT(*) FROM st_loyalty_points_log l JOIN st_customers c ON c.customer_id = l.customer_id WHERE $custScopeSql AND l.store_id = :sid");
    $s->execute($custScopeParams);
    $recentLogTotal = (int)$s->fetchColumn();
    $hPage = min($hPage, max(1, (int)ceil($recentLogTotal / $lpPerPage)));

    $s = $pdo->prepare("
        SELECT l.*, c.customer_name FROM st_loyalty_points_log l
        JOIN st_customers c ON c.customer_id = l.customer_id
        WHERE $custScopeSql AND l.store_id = :sid
        ORDER BY l.id DESC LIMIT $lpPerPage OFFSET " . (($hPage - 1) * $lpPerPage));
    $s->execute($custScopeParams);
    $recentLog = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Card footer: "ສະແດງ 11–20 ຈາກ 57 ລາຍການ" + page links (first, current ±2, last). Links keep
// the other list's page and jump back to this card via $anchor.
function lpPager($total, $page, $perPage, $param, $anchor) {
    $pages = (int)ceil($total / $perPage);
    if ($total <= 0) return '';
    $from = ($page - 1) * $perPage + 1;
    $to   = min($total, $page * $perPage);
    $html = '<div class="card-footer bg-white mt-auto d-flex justify-content-between align-items-center flex-wrap py-2 px-3" style="gap: 8px;">'
          . '<small class="text-muted">' . htmlspecialchars(sprintf(t('loyalty.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'), number_format($from), number_format($to), number_format($total))) . '</small>';
    if ($pages > 1) {
        $url = function ($p) use ($param, $anchor) {
            return 'index.php?' . htmlspecialchars(http_build_query(array_merge($_GET, [$param => $p]))) . '#' . $anchor;
        };
        $item = function ($p, $label, $disabled = false, $active = false) use ($url) {
            return '<li class="page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '') . '">'
                 . '<a class="page-link" href="' . ($disabled || $active ? '#' : $url($p)) . '">' . $label . '</a></li>';
        };
        $html .= '<ul class="pagination pagination-sm mb-0 ml-auto">' . $item($page - 1, '<i class="fas fa-chevron-left" style="font-size:0.72rem;"></i>', $page <= 1);
        $start = max(1, $page - 2); $end = min($pages, $page + 2);
        if ($start > 1) { $html .= $item(1, '1'); if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
        for ($p = $start; $p <= $end; $p++) $html .= $item($p, (string)$p, false, $p === $page);
        if ($end < $pages) { if ($end < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; $html .= $item($pages, (string)$pages); }
        $html .= $item($page + 1, '<i class="fas fa-chevron-right" style="font-size:0.72rem;"></i>', $page >= $pages) . '</ul>';
    }
    return $html . '</div>';
}

$logTypeLabels = [
    'earn'          => t('loyalty.log_earn', 'ໄດ້ຮັບ'),
    'redeem'        => t('loyalty.log_redeem', 'ແລກ'),
    'redeem_refund' => t('loyalty.log_redeem_refund', 'ຄືນຄະແນນ (ລຶບບິນ)'),
    'earn_reversal' => t('loyalty.log_earn_reversal', 'ຫັກຄືນ (ລຶບບິນ)'),
];

