<?php
/**
 * ເມນູສະມາຊິກທີ່ແຖບເທິງ (layouts/navbar.php) — ປຸ່ມຮູບໂປຣໄຟລ໌ (ບໍ່ມີ ☰) → ກ່ອງເມນູ:
 *   ຮູບ + ຊື່ + ອີເມວ (ຫຼື ເລກສະມາຊິກ) | ບັນຊີຂອງຂ້ອຍ, ຄຳສັ່ງຊື້ຂອງຂ້ອຍ, ກະຕ່າສິນຄ້າ, ແຊັດກັບທີມງານ | ອອກຈາກລະບົບ (POST pages/account.php)
 * ຕົວແປ: $navMember (currentMember). ເປີດ/ປິດ: [data-nm] ໃນ assets/js/main.js. CSS: .nm-* ໃນ assets/css/member.css
 */
$mmAva = fn(string $cls) => '<span class="' . $cls . '"><img src="' . e(memberAvatarUrl($navMember)) . '" alt="" referrerpolicy="no-referrer"></span>';   // ຮູບ Google ຫຼື ຮູບເລີ່ມຕົ້ນ (memberAvatarUrl)
$mmIco = fn(string $d) => '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$mmItems = [
    ['pages/account.php',   t('ບັນຊີຂອງຂ້ອຍ'),       '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
    ['pages/my_orders.php', t('ຄຳສັ່ງຊື້ຂອງຂ້ອຍ'),    '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7.5h6M9 12h6M9 16.5h4"/>'],
    ['pages/cart.php',      t('ກະຕ່າສິນຄ້າ'),        '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2L21 7H6"/>'],
    ['pages/contact.php',   t('ແຊັດກັບທີມງານ'),      '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 11h7M8.5 14.5h4"/>'],
];
?>
<div class="nm" data-nm>
    <button type="button" class="nm-btn" aria-haspopup="true" aria-expanded="false" title="<?php echo e(memberCode($navMember['member_no'])); ?>" aria-label="<?php echo te('ບັນຊີຂອງຂ້ອຍ'); ?>" data-nm-toggle>
        <?php echo $mmAva('nm-ava'); ?>
    </button>
    <div class="nm-panel" role="menu">
        <div class="nm-head">
            <?php echo $mmAva('nm-ava is-lg'); ?>
            <span class="nm-who">
                <b><?php echo e($navMember['name']); ?></b>
                <small><?php echo e($navMember['email'] ?: memberCode($navMember['member_no'])); ?></small>
            </span>
        </div>
        <?php foreach ($mmItems as [$href, $label, $icon]): ?>
            <a class="nm-item" role="menuitem" href="<?php echo url($href); ?>"><?php echo $mmIco($icon); ?><span><?php echo e($label); ?></span></a>
        <?php endforeach; ?>
        <form class="nm-out" method="post" action="<?php echo url('pages/account.php'); ?>" data-logout-confirm="<?php echo te('ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ບໍ?'); ?>" data-logout-text="<?php echo te('ທ່ານຈະຕ້ອງເຂົ້າສູ່ລະບົບອີກຄັ້ງ ເພື່ອເບິ່ງລາຄາ ແລະ ສັ່ງຊື້'); ?>" data-logout-yes="<?php echo te('ອອກຈາກລະບົບ'); ?>" data-logout-no="<?php echo te('ຍົກເລີກ'); ?>">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="nm-item" role="menuitem"><?php echo $mmIco('<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="M14 8l4 4-4 4M18 12H9"/>'); ?><span><?php echo te('ອອກຈາກລະບົບ'); ?></span></button>
        </form>
    </div>
</div>
