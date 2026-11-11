<?php
/*
 * ສ່ວນ: ຂໍ້ມູນລາຍງານ "ລາຍຮັບ ເວັບໄຊ vs ໜ້າຮ້ານ" — ຖືກ require ຈາກ pages/reports/channel_report.php
 * ໜ້າຮ້ານ  = ບິນ POS (w_tbsale_save, ບໍ່ນັບ CANCEL) ຍອດສຸດທິ sale_barlance ຕາມ sale_date
 * ເວັບໄຊ   = ອໍເດີເວັບທີ່ "ສຳເລັດ" (w_web_orders.status = completed) ຍອດ total_amount ຕາມວັນທີສັ່ງ created_at
 *            (ກົດ "ສຳເລັດ" ໃນ pages/web_orders ຈະບັນທຶກລາຄາຕາມລະບົບລົງ total_amount)
 * ສ້າງ: $fromDate, $toDate, $storeId, $branches, $shop, $web, $daily, $topShop, $topWeb, $winner
 */
$fromDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from_date'] ?? '') ? $_GET['from_date'] : date('Y-m-01');
$toDate   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to_date'] ?? '') ? $_GET['to_date'] : date('Y-m-d');
if ($fromDate > $toDate) [$fromDate, $toDate] = [$toDate, $fromDate];

$canAll = canViewAllBranches($pdo);
$storeId = $canAll ? (int)($_GET['store_id'] ?? 0) : (int)getActiveStoreId($pdo);   // 0 = ທຸກສາຂາ
$branches = [];
if ($canAll) {
    try { $branches = $pdo->query("SELECT store_id, store_name FROM w_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
}

$shop = ['count' => 0, 'revenue' => 0.0];
$web = ['count' => 0, 'revenue' => 0.0, 'open' => 0, 'cancelled' => 0, 'noprice' => 0];
$daily = [];
$topShop = [];
$topWeb = [];
try {
    // --- ໜ້າຮ້ານ ---
    $sw = "(s.sale_status IS NULL OR s.sale_status <> 'CANCEL') AND s.sale_date BETWEEN ? AND ?";
    $sp = [$fromDate, $toDate];
    if ($storeId > 0) { $sw .= ' AND s.store_id = ?'; $sp[] = $storeId; }
    $st = $pdo->prepare("SELECT COUNT(*) n, COALESCE(SUM(s.sale_barlance), 0) r FROM w_tbsale_save s WHERE $sw");
    $st->execute($sp);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    $shop = ['count' => (int)$r['n'], 'revenue' => (float)$r['r']];

    $st = $pdo->prepare("SELECT s.sale_date d, COUNT(*) n, COALESCE(SUM(s.sale_barlance), 0) r FROM w_tbsale_save s WHERE $sw GROUP BY s.sale_date");
    $st->execute($sp);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $daily[$r['d']]['shop'] = (float)$r['r'];
        $daily[$r['d']]['shop_n'] = (int)$r['n'];
    }

    $st = $pdo->prepare("SELECT d.save_proname name, SUM(d.save_qty) qty, SUM(d.save_net_money) amount
        FROM w_tbsale_save_detail d JOIN w_tbsale_save s ON s.sale_save_bill = d.save_bill
        WHERE $sw GROUP BY d.save_proname ORDER BY amount DESC, qty DESC LIMIT 5");
    $st->execute($sp);
    $topShop = $st->fetchAll(PDO::FETCH_ASSOC);

    // --- ເວັບໄຊ ---
    $ww = "DATE(o.created_at) BETWEEN ? AND ?";
    $wp = [$fromDate, $toDate];
    if ($storeId > 0) { $ww .= ' AND o.store_id = ?'; $wp[] = $storeId; }
    $st = $pdo->prepare("SELECT
            SUM(o.status = 'completed') n,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount END), 0) r,
            SUM(o.status = 'completed' AND o.total_amount <= 0) noprice,
            SUM(o.status = 'cancelled') cancelled,
            SUM(o.status NOT IN ('completed', 'cancelled')) open_n
        FROM w_web_orders o WHERE $ww");
    $st->execute($wp);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    $web = ['count' => (int)$r['n'], 'revenue' => (float)$r['r'], 'open' => (int)$r['open_n'], 'cancelled' => (int)$r['cancelled'], 'noprice' => (int)$r['noprice']];

    $st = $pdo->prepare("SELECT DATE(o.created_at) d, COUNT(*) n, COALESCE(SUM(o.total_amount), 0) r
        FROM w_web_orders o WHERE $ww AND o.status = 'completed' GROUP BY DATE(o.created_at)");
    $st->execute($wp);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $daily[$r['d']]['web'] = (float)$r['r'];
        $daily[$r['d']]['web_n'] = (int)$r['n'];
    }

    $st = $pdo->prepare("SELECT i.product_name name, SUM(i.qty) qty, SUM(i.subtotal) amount
        FROM w_web_order_items i JOIN w_web_orders o ON o.id = i.order_id
        WHERE $ww AND o.status = 'completed' GROUP BY i.product_name ORDER BY amount DESC, qty DESC LIMIT 5");
    $st->execute($wp);
    $topWeb = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('channel_report: ' . $e->getMessage());
}
krsort($daily);

$totalRevenue = $shop['revenue'] + $web['revenue'];
$shopShare = $totalRevenue > 0 ? round($shop['revenue'] / $totalRevenue * 100, 1) : 0;
$webShare  = $totalRevenue > 0 ? round(100 - $shopShare, 1) : 0;
$winner = $totalRevenue <= 0 ? 'none' : ($shop['revenue'] > $web['revenue'] ? 'shop' : ($web['revenue'] > $shop['revenue'] ? 'web' : 'tie'));
$money = fn($n) => number_format((float)$n) . ' ₭';
