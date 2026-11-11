<?php
/**
 * ແທັກ SEO ໃນ <head> ຂອງທຸກໜ້າ (require ຈາກ layouts/header.php): description, robots, canonical,
 * Open Graph / Twitter (ຮູບ + ຫົວຂໍ້ຕອນແຊຣ໌ລິ້ງ), ແລະ JSON-LD. ຕົວແປທີ່ໜ້າຕັ້ງໄດ້: ເບິ່ງ includes/seo.php
 */
$seoDescription = trim((string)($pageDescription ?? '')) ?: (string)($config['seo_description'] ?? $company['tagline']);
$seoDescription = mb_substr(preg_replace('/\s+/u', ' ', $seoDescription), 0, 300);
$seoCanonical = canonicalUrl($pageCanonical ?? null);
$seoImage = absUrl($pageImage ?? asset('img/logo.png'));
$seoLocale = ['lo' => 'lo_LA', 'th' => 'th_TH', 'en' => 'en_US', 'zh' => 'zh_CN', 'vi' => 'vi_VN'][currentLang()] ?? 'lo_LA';
$seoJsonLd = array_merge(siteJsonLd($company), $pageJsonLd ?? []);
?>
    <meta name="description" content="<?php echo e($seoDescription); ?>">
<?php if (!empty($config['seo_keywords'])): ?>
    <meta name="keywords" content="<?php echo e($config['seo_keywords']); ?>">
<?php endif; ?>
    <meta name="robots" content="<?php echo !empty($pageNoIndex) ? 'noindex, follow' : 'index, follow, max-image-preview:large'; ?>">
    <link rel="canonical" href="<?php echo e($seoCanonical); ?>">
    <meta name="theme-color" content="#facc15">
    <meta property="og:site_name" content="<?php echo e($company['name']); ?>">
    <meta property="og:type" content="<?php echo e($pageOgType ?? 'website'); ?>">
    <meta property="og:title" content="<?php echo e($fullTitle); ?>">
    <meta property="og:description" content="<?php echo e($seoDescription); ?>">
    <meta property="og:url" content="<?php echo e($seoCanonical); ?>">
    <meta property="og:image" content="<?php echo e($seoImage); ?>">
    <meta property="og:locale" content="<?php echo e($seoLocale); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($fullTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($seoDescription); ?>">
    <meta name="twitter:image" content="<?php echo e($seoImage); ?>">
<?php if (!empty($config['google_site_verification'])): ?>
    <meta name="google-site-verification" content="<?php echo e($config['google_site_verification']); ?>">
<?php endif; ?>
<?php foreach ($seoJsonLd as $ld): ?>
    <script type="application/ld+json"><?php echo json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
<?php endforeach; ?>
