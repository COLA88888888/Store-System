<?php
/**
 * ການຈັດການຄຳສັ່ງຊື້ຂອງສະມາຊິກ — ແບບແອັບ (ແຖບໄອຄອນຊ້າຍຄືກັບຫ້ອງແຊັດ) ເຕັມຈໍ:
 *   components/chat_rail.php (ແຊັດ / ຄຳສັ່ງຊື້ / ຕັ້ງຄ່າ / ຊ່ວຍເຫຼືອ) | ຫົວຂໍ້ + ຄົ້ນຫາ + ແທັບ + ຕົວກອງສະຖານະ + ລາຍການ
 *   ?s = active (ກຳລັງດຳເນີນການ, ຄ່າເລີ່ມຕົ້ນ) | completed | cancelled | all;  ?st = ສະຖານະຍ່ອຍໃນແທັບນັ້ນ;  ?p = ໜ້າ (ໜ້າລະ 10, memberOrdersPaged)
 * ຄົ້ນຫາ (ເລກອໍເດີ / ຊື່ສິນຄ້າ) ແລະ ເມນູສະຖານະ = assets/js/order_manage.js. CSS: css/order_manage.css + css/chat_room.css (.cr-rail, popup)
 * ອໍເດີທີ່ຜູກກັບບັນຊີ + ອໍເດີເກົ່າທີ່ສັ່ງດ້ວຍເບີດຽວກັນ (memberOrders ໃນ models/member_orders.php). ກົດແຖວ → pages/my_order.php
 * ບໍ່ໄດ້ login → pages/member_login.php
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ການຈັດການຄຳສັ່ງຊື້');
$currentPage = 'orders';
$pageNoIndex = true;
$member = currentMember($pdo);
if (!$member) {
    redirect('pages/member_login.php?next=' . rawurlencode('pages/my_orders.php'));
}
$pageStyles = ['css/chat_room.css', 'css/order_manage.css'];
$pageScripts = ['js/chat_room.js', 'js/order_manage.js'];

$tabs = ['active' => t('ກຳລັງດຳເນີນການ'), 'completed' => t('ສຳເລັດ'), 'cancelled' => t('ຍົກເລີກ'), 'all' => t('ທັງໝົດ')];
// ສະຖານະຍ່ອຍທີ່ເລືອກໄດ້ໃນແຕ່ລະແທັບ (ຕົວກອງ "ສະຖານະ:")
$tabStatuses = [
    'active'    => ['pending', 'confirmed', 'packing', 'shipped', 'arrived'],
    'completed' => [],
    'cancelled' => [],
    'all'       => ['pending', 'confirmed', 'packing', 'shipped', 'arrived', 'completed', 'cancelled'],
];
$s = (string)($_GET['s'] ?? 'active');
if ($s === '') {
    $s = 'all';   // ລິ້ງເກົ່າ ?s= (ທັງໝົດ)
}
if (!isset($tabs[$s])) {
    $s = 'active';
}
$st = (string)($_GET['st'] ?? '');
if (!in_array($st, $tabStatuses[$s], true)) {
    $st = '';
}
$counts = memberOrderCounts($pdo, $member);
// ແບ່ງໜ້າ ?p= (ໜ້າລະ 10) — ລິ້ງແທັບ / ສະຖານະ ເລີ່ມໜ້າ 1 ໃໝ່; ລິ້ງໜ້າເກັບ s + st ໄວ້
// ແນບ / ປ່ຽນ ສະລິບ (ຟອມໃນ modal ຕິດຕາມ — components/order_track_modal.php) → ກັບມາໜ້ານີ້ ພ້ອມເປີດ modal ອໍເດີນັ້ນ (?track=)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'slip') {
    $slipNo = (string)($_POST['no'] ?? '');
    $slipOrder = $slipNo !== '' ? memberOrder($pdo, $member, $slipNo) : null;
    if ($slipOrder) {
        $err = checkCsrf()
            ? memberAttachSlip($pdo, $slipOrder, $_FILES['slip'] ?? null, (int)($_POST['bank_account_id'] ?? 0), getActiveBankAccounts($pdo, (int)$slipOrder['store_id']))
            : t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
        flash($err === null ? t('ແນບສະລິບແລ້ວ — ທີມງານຈະກວດ ແລະ ຢືນຢັນອໍເດີໃຫ້ໄວທີ່ສຸດ') : $err, $err === null ? 'success' : 'error');
    }
    redirect('pages/my_orders.php?s=all&track=' . rawurlencode($slipNo));
}
// ?track=ເລກອໍເດີ (ລິ້ງຈາກກະດິ່ງ / ອີເມວ / ໜ້າເກົ່າ pages/my_order.php) → ເປີດ modal ຕິດຕາມຂອງອໍເດີນັ້ນເລີຍ
$trackNo = (string)($_GET['track'] ?? '');
$trackOrder = $trackNo !== '' ? memberOrder($pdo, $member, $trackNo) : null;
$paged = memberOrdersPaged($pdo, $member, $st !== '' ? $st : ($s === 'all' ? '' : $s), (int)($_GET['p'] ?? 1), 10);
$orders = $paged['orders'];

$chat = chatCurrent($pdo);
$railUnread = 0;
if ($chat) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM w_web_chat_messages WHERE chat_id = ? AND sender <> 'visitor' AND id > ?");
    $q->execute([(int)$chat['id'], (int)$chat['visitor_read_id']]);
    $railUnread = (int)$q->fetchColumn();
}
$railActive = 'orders';
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="om">
    <div class="om-shell cr">
        <?php require dirname(__DIR__) . '/components/chat_rail.php'; ?>
        <div class="om-main">
            <div class="om-head">
                <button type="button" class="cr-rail-open cr-mobile-only" data-cr-rail-open aria-label="<?php echo te('ເປີດເມນູ'); ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6.5h16M4 12h16M4 17.5h16"/></svg></button>
                <h1><?php echo te('ການຈັດການຄຳສັ່ງຊື້'); ?></h1>
                <label class="om-search">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" data-om-search placeholder="<?php echo te('ຄົ້ນຫາ'); ?>" aria-label="<?php echo te('ຄົ້ນຫາເລກອໍເດີ ຫຼື ຊື່ສິນຄ້າ'); ?>">
                </label>
            </div>
            <nav class="om-tabs">
                <?php foreach ($tabs as $k => $lbl): ?>
                    <a class="<?php echo $s === $k ? 'is-active' : ''; ?>" href="<?php echo url('pages/my_orders.php' . ($k !== 'active' ? '?s=' . $k : '')); ?>"><?php echo e($lbl); ?> (<?php echo (int)$counts[$k]; ?>)</a>
                <?php endforeach; ?>
            </nav>
            <div class="om-body">
                <?php if ($tabStatuses[$s]): ?>
                    <?php // ຕົວກອງສະຖານະ = ເມນູ <details> (ເປີດ/ປິດໄດ້ໂດຍບໍ່ຕ້ອງມີ JS; JS ປິດເມື່ອກົດຂ້າງນອກ) — ແຕ່ລະລາຍການເປັນລິ້ງ ?s=&st=
                    $stOpts = ['' => t('ທັງໝົດ')];
                    foreach ($tabStatuses[$s] as $code) {
                        $stOpts[$code] = orderStatusMeta($code)['label'];
                    } ?>
                    <details class="om-filter" data-om-filter>
                        <summary><span><?php echo te('ສະຖານະ:'); ?> <b><?php echo e($stOpts[$st]); ?></b></span>
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
                        <div class="om-menu" role="menu">
                            <?php foreach ($stOpts as $code => $lbl):
                                $qs = array_filter(['s' => $s !== 'active' ? $s : '', 'st' => $code]); ?>
                                <a role="menuitem" class="<?php echo $st === $code ? 'is-active' : ''; ?>" href="<?php echo url('pages/my_orders.php' . ($qs ? '?' . http_build_query($qs) : '')); ?>">
                                    
                                    <span><?php echo e($lbl); ?></span>
                                    <?php if ($st === $code): ?><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>

                <?php $otThumbs = memberOrderThumbs($pdo, $orders); require dirname(__DIR__) . '/components/order_table.php'; /* ຕາຕະລາງ + ແຖວໂຫຼດ */ ?>
                <?php if ($paged['total'] > 0):
                    $pgUrl = fn(int $n) => url('pages/my_orders.php' . (($q = http_build_query(array_filter(['s' => $s !== 'active' ? $s : '', 'st' => $st, 'p' => $n > 1 ? $n : ''], fn($v) => $v !== '' && $v !== null))) ? '?' . $q : ''));
                    $pgCur = $paged['page']; $pgN = $paged['pages'];
                    $pgShow = array_values(array_unique(array_filter([1, $pgCur - 1, $pgCur, $pgCur + 1, $pgN], fn($n) => $n >= 1 && $n <= $pgN)));
                    sort($pgShow);
                    $pgArrow = fn(string $d) => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $d . '"/></svg>'; ?>
                    <div class="om-pager" data-om-pager>
                        <span class="om-pager-info"><?php echo te('ສະແດງ %s–%s ຈາກ %s ລາຍການ', ($pgCur - 1) * $paged['per'] + 1, min($pgCur * $paged['per'], $paged['total']), $paged['total']); ?></span>
                        <?php if ($pgN >= 1): /* ສະແດງສະເໝີ */ ?>
                            <nav class="om-pages" aria-label="<?php echo te('ແບ່ງໜ້າ'); ?>">
                                <?php if ($pgCur > 1): ?><a href="<?php echo e($pgUrl($pgCur - 1)); ?>" aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>"><?php echo $pgArrow('m15 6-6 6 6 6'); ?></a><?php else: ?><span class="is-off"><?php echo $pgArrow('m15 6-6 6 6 6'); ?></span><?php endif; ?>
                                <?php $pgPrev = 0; foreach ($pgShow as $n): ?>
                                    <?php if ($n - $pgPrev > 1): ?><span class="is-gap">…</span><?php endif; ?>
                                    <a class="<?php echo $n === $pgCur ? 'is-active' : ''; ?>" href="<?php echo e($pgUrl($n)); ?>"<?php echo $n === $pgCur ? ' aria-current="page"' : ''; ?>><?php echo $n; ?></a>
                                <?php $pgPrev = $n; endforeach; ?>
                                <?php if ($pgCur < $pgN): ?><a href="<?php echo e($pgUrl($pgCur + 1)); ?>" aria-label="<?php echo te('ຖັດໄປ'); ?>"><?php echo $pgArrow('m9 6 6 6-6 6'); ?></a><?php else: ?><span class="is-off"><?php echo $pgArrow('m9 6 6 6-6 6'); ?></span><?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="om-empty" data-om-empty<?php echo $orders ? ' hidden' : ''; ?>>
                    <svg viewBox="0 0 140 130" width="140" height="130" aria-hidden="true">
                        <rect x="66" y="76" width="9" height="50" rx="2" fill="#e2e8f0" stroke="#94a3b8" stroke-width="2"/>
                        <path d="M40 24h58a22 22 0 0 1 22 22v34H18V46a22 22 0 0 1 22-22z" fill="#e2e8f0" stroke="#94a3b8" stroke-width="2.5"/>
                        <path d="M40 24a22 22 0 0 1 22 22v34H18V46a22 22 0 0 1 22-22z" fill="#cbd5e1" stroke="#94a3b8" stroke-width="2.5"/>
                        <path d="M30 50h20" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"/>
                        <path d="M98 40v-22h16v10H98" fill="#fbbf24" stroke="#94a3b8" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    <?php if (!$counts['all']): ?>
                        <b><?php echo te('ທ່ານຍັງບໍ່ມີຄຳສັ່ງຊື້'); ?></b>
                        <p><?php echo te('ເມື່ອມີຄຳສັ່ງຊື້ແລ້ວ ຈະປາກົດທີ່ນີ້'); ?></p>
                        <a class="om-shop" href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ເລືອກຊື້ສິນຄ້າ'); ?></a>
                    <?php else: ?>
                        <b data-om-empty-title><?php echo te('ບໍ່ມີຄຳສັ່ງຊື້ໃນລາຍການນີ້'); ?></b>
                        <p><?php echo te('ລອງເລືອກແທັບ ຫຼື ສະຖານະອື່ນ'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if ($trackOrder && !in_array((int)$trackOrder['id'], array_map(fn($x) => (int)$x['id'], $orders), true)):
    // ອໍເດີທີ່ເປີດດ້ວຍ ?track= ບໍ່ຢູ່ໃນໜ້ານີ້ຂອງຕາຕະລາງ → ສ້າງ <template> ຂອງມັນແຍກ
    $o = $trackOrder; $oMeta = orderStatusMeta((string)$o['status']); $oCour = getLaoCouriers()[$o['courier_code']] ?? null; $oTotal = orderDisplayTotal($o);
    $otBankList = getActiveBankAccounts($pdo, (int)$o['store_id']); $otBanks = array_column($otBankList, null, 'id');
    $otItemImgs = memberItemImages($pdo, $o['items'] ?? []);
    require dirname(__DIR__) . '/components/order_track_modal.php';
endif; ?>
<!-- modal ຕິດຕາມອໍເດີ (ເນື້ອໃນຈາກ <template data-ot-detail> — components/order_track_modal.php, assets/js/order_manage.js) -->
<dialog class="otm" data-otm<?php echo $trackOrder ? ' data-otm-open="' . (int)$trackOrder['id'] . '"' : ''; ?> aria-label="<?php echo te('ຕິດຕາມອໍເດີ'); ?>"><div class="otm-inner" data-otm-body></div></dialog>
<?php require dirname(__DIR__) . '/components/chat_room_modals.php'; /* popup ຊ່ວຍເຫຼືອ + ຕັ້ງຄ່າ */ ?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
