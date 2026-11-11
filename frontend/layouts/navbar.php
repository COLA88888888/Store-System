<?php
/**
 * ເມນູດ້ານເທິງ — ຖືກ require ຈາກ layouts/header.php. ເພີ່ມໜ້າໃໝ່ໃນ $navItems.
 * ລຽງຈາກຊ້າຍ: ໂລໂກ້ | ເມນູ | ທຸງພາສາ | ປຸ່ມເຂົ້າສູ່ລະບົບ (→ backend/)
 * ທຸງພາສາ: ກົດແລ້ວລາຍການພາສາເລື່ອນລົງ ([data-lang-menu] ໃນ assets/js/main.js); ແຕ່ລະພາສາເປັນລິ້ງ ?lang=xx (includes/i18n.php).
 * ຈໍນ້ອຍ: ເມນູຫຍໍ້ເປັນປຸ່ມ ☰ (assets/js/main.js).
 */
$navItems = [
    'home'        => [t('ໜ້າຫຼັກ'), ''],
    'about'       => [t('ກ່ຽວກັບພວກເຮົາ'), 'pages/about.php'],
    'services'    => [t('ບໍລິການ'), 'pages/services.php'],
    'portfolio'   => [t('ຜົນງານ'), 'pages/portfolio.php'],
    'solutions'   => [t('ລະບົບ POS'), 'pages/pos_systems.php'],   // ລະບົບ POS ຕາມປະເພດທຸລະກິດ (pages/pos_systems.php → pages/solution.php)
    'equipment'   => [t('ອຸປະກອນ POS'), 'pages/equipment_list.php'],
    'contact'     => [t('ຕິດຕໍ່'), 'pages/contact.php'],
];
if (!hasWorks()) {
    unset($navItems['portfolio']);   // ຍັງບໍ່ມີຜົນງານແທ້ → ບໍ່ສະແດງເມນູ (includes/content.php $siteWorks)
}
$lang = currentLang();
?>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo url(); ?>">
            <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="66" height="44">
            <span><?php echo e($company['name']); ?></span>
        </a>

        <nav class="site-nav" data-nav>
            <?php foreach ($navItems as $key => [$label, $href]): ?>
                <a class="nav-link<?php echo navActive($key); ?>" href="<?php echo url($href); ?>"><?php echo e($label); ?></a>
            <?php endforeach; ?>
            <?php /* ມືຖືຈໍນ້ອຍ: ປຸ່ມ "ເຂົ້າສູ່ລະບົບ" ຖືກເຊື່ອງຈາກແຖບເທິງ (ບໍ່ດັ່ງນັ້ນລົ້ນຈໍ) → ມາຢູ່ໃນເມນູ ☰ ແທນ */ ?>
            <?php if (currentMember($pdo)): ?>
                <a class="nav-link nav-mobile-only" href="<?php echo url('pages/account.php'); ?>"><?php echo te('ບັນຊີຂອງຂ້ອຍ'); ?></a>
            <?php else: ?>
                <a class="nav-link nav-mobile-only" href="<?php echo url('pages/member_login.php'); ?>" data-member-open="login"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></a>
            <?php endif; ?>
        </nav>

        <div class="nav-actions">
            <div class="lang-switch" data-lang-menu>
                <button class="lang-current" type="button" aria-haspopup="true" aria-expanded="false" title="<?php echo te('ປ່ຽນພາສາ'); ?>" data-lang-toggle>
                    <img src="<?php echo asset('img/flags/' . SITE_LANGS[$lang]['flag']); ?>" alt="<?php echo e(SITE_LANGS[$lang]['label']); ?>" width="32" height="32">
                </button>
                <div class="lang-menu" role="menu">
                    <?php foreach (SITE_LANGS as $code => $meta): ?>
                        <a class="lang-item<?php echo $code === $lang ? ' active' : ''; ?>" role="menuitem" href="<?php echo e(langUrl($code)); ?>" lang="<?php echo e($code); ?>">
                            <img src="<?php echo asset('img/flags/' . $meta['flag']); ?>" alt="" width="30" height="30">
                            <span><?php echo e($meta['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php $navCartMember = currentMember($pdo); $navCartCount = $navCartMember ? array_sum(cartItems()) : 0; ?>
            <?php if ($navCartMember): /* ກະຕ່າ: ສະມາຊິກ → pages/cart.php + ຈຳນວນ ([data-cart-count] ອັບເດດໂດຍ assets/js/cart.js) */ ?>
                <a class="btn-cart" href="<?php echo url('pages/cart.php'); ?>" title="<?php echo te('ກະຕ່າສິນຄ້າ'); ?>" aria-label="<?php echo te('ກະຕ່າສິນຄ້າ'); ?>">
                    <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2L21 7H6"/></svg>
                    <span class="btn-cart-count" data-cart-count<?php echo $navCartCount ? '' : ' hidden'; ?>><?php echo (int)$navCartCount; ?></span>
                </a>
                <?php require dirname(__DIR__) . '/components/member_bell.php'; /* ກະດິ່ງແຈ້ງເຕືອນສະມາຊິກ */ ?>
            <?php else: /* ລູກຄ້າທົ່ວໄປ: ກົດກະຕ່າ → ແຈ້ງເຕືອນໃຫ້ລົງທະບຽນ (popup + ຂໍ້ຄວາມ data-member-note — assets/js/member.js) */ ?>
                <a class="btn-cart" href="#register" data-member-open="register" data-member-note="<?php echo te('ກະລຸນາລົງທະບຽນ ຫຼື ເຂົ້າສູ່ລະບົບ ກ່ອນໃຊ້ກະຕ່າສິນຄ້າ — ສະມາຊິກເຫັນລາຄາ ແລະ ສັ່ງຊື້ໄດ້ເລີຍ'); ?>" title="<?php echo te('ກະຕ່າສິນຄ້າ'); ?>" aria-label="<?php echo te('ກະຕ່າສິນຄ້າ'); ?>">
                    <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2L21 7H6"/></svg>
                </a>
            <?php endif; ?>
            <?php if ($navMember = $navCartMember): /* ສະມາຊິກ login ແລ້ວ → ປຸ່ມ ☰ + ຮູບໂປຣໄຟລ໌ ເປີດເມນູສະມາຊິກ */ ?>
                <?php require dirname(__DIR__) . '/components/member_menu.php'; ?>
            <?php else: ?>
                <a class="btn btn-member" href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນ'); ?></a>
            <?php endif; ?>
            <?php if (!$navCartMember): /* ເຂົ້າສູ່ລະບົບສະມາຊິກ (popup) — ບໍ່ເດັ້ງໄປໜ້າອື່ນ ແລະ ບໍ່ເຫັນຟອມຫຼັງບ້ານ */ ?>
                <a class="btn btn-login" href="<?php echo url('pages/member_login.php'); ?>" data-member-open="login"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></a>
            <?php endif; ?>
            <button class="nav-toggle" type="button" aria-label="<?php echo te('ເປີດເມນູ'); ?>" aria-expanded="false" data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
