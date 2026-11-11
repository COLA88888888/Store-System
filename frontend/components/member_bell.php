<?php
/**
 * ກະດິ່ງແຈ້ງເຕືອນ ໃນແຖບເມນູ — ສະເພາະສະມາຊິກ (require ຈາກ layouts/navbar.php ຖັດຈາກກະຕ່າ).
 * ກົດ → ດຶງລາຍການຈາກ api/notifications.php ສະແດງໃນກ່ອງເລື່ອນລົງ. JS: assets/js/member_bell.js, CSS: .nb-* ໃນ assets/css/member.css
 * ຕົວແປ: $navCartMember (ສະມາຊິກປັດຈຸບັນ)
 */
$nbUnread = memberNotifUnread($pdo, (int)$navCartMember['id']);
?>
<div class="nb" data-bell data-endpoint="<?php echo e(url('api/notifications.php')); ?>"
     data-empty="<?php echo te('ຍັງບໍ່ມີແຈ້ງເຕືອນ'); ?>" data-loading="<?php echo te('ກຳລັງໂຫຼດ…'); ?>">
    <button class="nb-btn" type="button" title="<?php echo te('ແຈ້ງເຕືອນ'); ?>" aria-label="<?php echo te('ແຈ້ງເຕືອນ'); ?>" aria-haspopup="true" aria-expanded="false" data-bell-toggle>
        <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <span class="nb-count" data-bell-count<?php echo $nbUnread ? '' : ' hidden'; ?>><?php echo $nbUnread > 99 ? '99+' : $nbUnread; ?></span>
    </button>
    <div class="nb-panel" role="dialog" aria-label="<?php echo te('ແຈ້ງເຕືອນ'); ?>" hidden data-bell-panel>
        <div class="nb-head">
            <strong><?php echo te('ແຈ້ງເຕືອນ'); ?></strong>
            <button type="button" class="nb-readall" data-bell-readall><?php echo te('ອ່ານແລ້ວທັງໝົດ'); ?></button>
        </div>
        <div class="nb-list" data-bell-list></div>
    </div>
</div>
