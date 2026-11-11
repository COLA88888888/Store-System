<?php
/**
 * ສ່ວນຫົວຂອງທຸກໜ້າ: <head>, ໄຟລ໌ CSS (ລຳດັບ = cascade), ແລະ ເມນູ (layouts/navbar.php).
 * ໜ້າທີ່ include ຕ້ອງຕັ້ງ $pageTitle ແລະ $currentPage ກ່ອນ. ປິດດ້ວຍ layouts/footer.php.
 * SEO (description, canonical, Open Graph, JSON-LD): layouts/seo_head.php — ຕົວແປທີ່ຕັ້ງໄດ້ ເບິ່ງ includes/seo.php
 */
$pageTitle = $pageTitle ?? '';
$brandTitle = 'wlaodev.com';
$fullTitle = $pageTitle !== '' ? $pageTitle . ' | ' . $brandTitle : $brandTitle . ' — ' . $company['tagline'];
?>
<!DOCTYPE html>
<html lang="<?php echo e(currentLang()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no<?php echo ($currentPage ?? '') === 'contact' ? '' : ', viewport-fit=cover'; /* ໜ້າແຊັດ: ບໍ່ cover — iPhone ຈະວາງໜ້າໄວ້ເທິງແຖບເຄື່ອງມື ບ່ອນພິມຂໍ້ຄວາມບໍ່ຖືກບັງ */ ?>"><?php /* ໂທລະສັບ: ບໍ່ໃຫ້ຊູມ (ຜູ້ໃຊ້ຕ້ອງການໃຫ້ໜ້າຢູ່ນິ່ງ) — ເບິ່ງ css/mobile.css */ ?>
    <title><?php echo e($fullTitle); ?></title>
<?php require __DIR__ . '/seo_head.php'; ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('img/favicon.svg'); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo asset('img/favicon-32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo asset('img/favicon-16.png'); ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?php echo asset('img/favicon-48.png'); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo asset('img/favicon-192.png'); ?>">
    <link rel="icon" type="image/png" sizes="256x256" href="<?php echo asset('img/favicon.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo asset('img/favicon-192.png'); ?>">
    <link rel="shortcut icon" href="<?php echo asset('img/favicon-32.png'); ?>">
    <link rel="preload" href="<?php echo url('assets/fonts/Nuanta-Regular.ttf'); ?>" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="<?php echo asset('css/fonts.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/base.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/member.css'); ?>">
    <?php foreach ($pageStyles ?? [] as $pageCss): /* CSS ສະເພາະໜ້າ (ເຊັ່ນ pages/contact.php → css/chat_room.css) */ ?>
    <link rel="stylesheet" href="<?php echo asset($pageCss); ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?php echo asset('css/mobile.css'); ?>"><?php /* ປັບສະເພາະໂທລະສັບ — ໂຫຼດຫຼັງສຸດ */ ?>
    <script>
        window.APP = {
            base: <?php echo json_encode(frontBase()); ?>,
            csrf: <?php echo json_encode(csrfToken()); ?>,
            isMember: <?php echo currentMember() !== null ? 'true' : 'false'; ?>,
            i18n: <?php echo json_encode(['error' => t('ເກີດຂໍ້ຜິດພາດ (%s)'), 'offline' => t('ເຊື່ອມຕໍ່ບໍ່ໄດ້ ກະລຸນາກວດອິນເຕີເນັດ')], JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
    <!-- ກົດເມນູແລ້ວບໍ່ກະພິບ: browser ໂຫຼດໜ້າໄວ້ລ່ວງໜ້າ ເມື່ອເອົາເມົາໄປຖືກລິ້ງ (prerender; Chrome/Edge) — ການປ່ຽນໜ້າແບບຈາງ: @view-transition ໃນ assets/css/base.css.
         ບໍ່ໂຫຼດລ່ວງໜ້າ: backend/, api/, ປ່ຽນພາສາ (?lang= ຕັ້ງ cookie), ກະຕ່າ ແລະ ການສັ່ງຊື້ -->
    <script type="speculationrules"><?php echo json_encode(['prerender' => [[
        'where' => ['and' => [
            ['href_matches' => frontBase() . '*'],
            ['not' => ['href_matches' => frontBase() . 'backend/*']],
            ['not' => ['href_matches' => frontBase() . 'api/*']],
            ['not' => ['href_matches' => '*?*lang=*']],
            ['not' => ['href_matches' => frontBase() . 'pages/(cart|checkout|order_success).php*']],
        ]],
        'eagerness' => 'moderate',
    ]]], JSON_UNESCAPED_SLASHES); ?></script>
</head>
<body class="page-<?php echo e($currentPage ?? 'default'); ?>">
<?php require __DIR__ . '/navbar.php'; ?>
<main class="site-main">
<?php if ($flashMsg = flash()): ?>
    <div class="container"><div class="alert alert-<?php echo e($flashMsg['type']); ?>"><?php echo e($flashMsg['message']); ?></div></div>
<?php endif; ?>
