<?php
/**
 * ແຖບໄອຄອນແຄບຊ້າຍ (ແບບແອັບແຊັດ) — ໃຊ້ໃນ pages/contact.php (ແຊັດ) ແລະ pages/my_orders.php (ການຈັດການຄຳສັ່ງຊື້).
 * ຕົວແປ: $railActive = 'chat' | 'orders', $railUnread = ຈຳນວນຂໍ້ຄວາມທີ່ຍັງບໍ່ອ່ານ (ຈຸດແດງ, ບໍ່ບັງຄັບ).
 * ຕັ້ງຄ່າ / ຊ່ວຍເຫຼືອ ເປີດ popup ໃນ components/chat_room_modals.php ([data-cr-open], assets/js/chat_room.js).
 * ມືຖື (≤ 992px): ແຖບນີ້ກາຍເປັນລິ້ນຊັກເລື່ອນອອກຈາກຊ້າຍ (ໂລໂກ້ + ໄອຄອນ + ຊື່) — ເປີດດ້ວຍປຸ່ມ ☰ [data-cr-rail-open],
 *   ປິດ: ກົດພື້ນມືດ [data-cr-rail-close] / Esc / ເລືອກເມນູ (assets/js/chat_room.js)
 * ຄົນທົ່ວໄປກົດ "ຄຳສັ່ງຊື້" → popup ລົງທະບຽນ (assets/js/member.js). CSS: .cr-rail ໃນ assets/css/chat_room.css
 */
$railActive = $railActive ?? 'chat';
$railUnread = (int)($railUnread ?? 0);
$railMember = (bool)currentMember($pdo);
$railCls = function (string $k) use ($railActive): string {
    return 'cr-rail-btn' . ($railActive === $k ? ' is-active' : '');
};
?>
<div class="cr-rail-back" data-cr-rail-close aria-hidden="true"></div>
<nav class="cr-rail" aria-label="<?php echo te('ເມນູແຊັດ'); ?>">
    <a class="cr-rail-logo" href="<?php echo url(''); ?>"><img src="<?php echo asset('img/logo.png'); ?>" alt="" width="48" height="32"><span><?php echo e($company['name'] ?? 'Wlaodev'); ?></span></a>
    <a class="<?php echo $railCls('chat'); ?>" href="<?php echo url('pages/contact.php'); ?>"<?php echo $railActive === 'chat' ? ' aria-current="page"' : ''; ?>><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M12 3C6.5 3 2 6.9 2 11.6c0 2.6 1.4 5 3.7 6.5L5 21.5l3.9-2.1c1 .3 2 .4 3.1.4 5.5 0 10-3.9 10-8.6S17.5 3 12 3zM8 10.5h8a.9.9 0 0 1 0 1.8H8a.9.9 0 0 1 0-1.8zm0-3.2h6a.9.9 0 0 1 0 1.8H8a.9.9 0 0 1 0-1.8z"/></svg><span><?php echo te('ແຊັດ'); ?></span><?php if ($railUnread): ?><i class="cr-rail-dot"><?php echo $railUnread; ?></i><?php endif; ?></a>
    <?php if ($railMember): ?>
        <a class="<?php echo $railCls('orders'); ?>" href="<?php echo url('pages/my_orders.php'); ?>"<?php echo $railActive === 'orders' ? ' aria-current="page"' : ''; ?>><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7.5h6M9 12h6M9 16.5h4"/></svg><span><?php echo te('ຄຳສັ່ງຊື້'); ?></span></a>
    <?php else: ?>
        <a class="cr-rail-btn" href="#register" data-member-open="register" data-member-note="<?php echo te('ລົງທະບຽນເປັນສະມາຊິກ ເພື່ອສັ່ງຊື້ ແລະ ຕິດຕາມຄຳສັ່ງຊື້'); ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7.5h6M9 12h6M9 16.5h4"/></svg><span><?php echo te('ຄຳສັ່ງຊື້'); ?></span></a>
    <?php endif; ?>
    <div class="cr-rail-bottom">
        <button type="button" class="cr-rail-btn" data-cr-open="settings" title="<?php echo te('ຕັ້ງຄ່າການໃຊ້ງານ'); ?>"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg><span><?php echo te('ຕັ້ງຄ່າ'); ?></span></button>
        <button type="button" class="cr-rail-btn" data-cr-open="help" title="<?php echo te('ວິທີສັ່ງຊື້'); ?>"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M9.3 9.2a2.8 2.8 0 0 1 5.4 1c0 1.9-2.7 2.5-2.7 4"/><circle cx="12" cy="17.3" r=".6" fill="currentColor"/></svg><span><?php echo te('ຊ່ວຍເຫຼືອ'); ?></span></button>
    </div>
</nav>
