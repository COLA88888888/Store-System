<?php
/**
 * ລາຍລະອຽດຜົນງານ 1 ອັນ (GET w = slug) — ຊື່, ວັນທີ, ລາຍລະອຽດ ແລະ ຮູບທັງໝົດ (ກົດຮູບ = lightbox ຊູມຊ້າໆ: [data-lightbox] ໃນ assets/js/main.js).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!hasWorks()) {
    redirect('pages/services.php');   // ຍັງບໍ່ມີຜົນງານແທ້ → ໄປໜ້າບໍລິການ
}
$work = getWork((string)($_GET['w'] ?? ''));
if (!$work) {
    http_response_code(404);
}
$pageTitle = $work ? $work['title'] : t('ບໍ່ພົບຜົນງານ');
$currentPage = 'portfolio';
if ($work) {
    $pageDescription = $work['text'];
    $pageImage = $work['photos'][0] ?? null;
    $pageCanonical = 'pages/work.php?w=' . rawurlencode($work['slug']);
    $pageOgType = 'article';
    $pageJsonLd = [breadcrumbJsonLd([[t('ໜ້າຫຼັກ'), ''], [t('ຜົນງານ'), 'pages/portfolio.php'], [$work['title'], $pageCanonical]])];
}
$pageStyles = array_merge($pageStyles ?? [], ['css/product_gallery.css']);
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section sol-page wk-detail-page">
    <div class="container sol-container">
        <?php if (!$work): ?>
            <p class="empty"><?php echo te('ບໍ່ພົບຜົນງານ'); ?> — <a href="<?php echo url('pages/portfolio.php'); ?>"><?php echo te('ເບິ່ງຜົນງານທັງໝົດ'); ?></a></p>
        <?php else:
            $photos = $work['photos'] ?? [];
            $firstPhoto = $photos[0] ?? '';
            $photoCount = count($photos);
        ?>
            <nav class="breadcrumb solution-breadcrumb mb-3" aria-label="Breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo te('ໜ້າຫຼັກ'); ?></a> <span class="sep">/</span>
                <a href="<?php echo url('pages/portfolio.php'); ?>"><?php echo te('ຜົນງານ'); ?></a> <span class="sep">/</span>
                <span class="current"><?php echo e($work['title']); ?></span>
            </nav>

            <!-- 2 ຖັນ: ຊ້າຍ ແກເລີຣີ Fastwork (Image 2), ຂວາ ຂໍ້ມູນຮູບແບບທີ 1 (Image 1) -->
            <div class="wk-work-card">
                <!-- ຄໍລຳຊ້າຍ: ແກເລີຣີ Fastwork (ຮູບໃຫຍ່ + ປ້າຍລຳດັບ 1/6 + ຮູບນ້ອຍລຸ່ມ) -->
                <div class="sol-gallery" id="solutionGallery" data-gallery>
                    <div class="sol-stage">
                        <span class="sol-counter" id="galleryCounter">1/<?php echo $photoCount; ?></span>
                        <button class="sol-zoom" type="button" id="btnGalleryZoom" aria-label="<?php echo te('ເບິ່ງຮູບເຕັມຈໍ'); ?>">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                        </button>
                        <div class="sol-viewport" id="galleryViewport" style="--bg: url('<?php echo e($firstPhoto); ?>')">
                            <img id="galleryMainImg" src="<?php echo e($firstPhoto); ?>" alt="<?php echo e($work['title']); ?>">
                        </div>
                        <?php if ($photoCount > 1): ?>
                            <button class="sol-nav prev" type="button" aria-label="<?php echo te('ຮູບກ່ອນໜ້າ'); ?>" data-gallery-prev>‹</button>
                            <button class="sol-nav next" type="button" aria-label="<?php echo te('ຮູບຖັດໄປ'); ?>" data-gallery-next>›</button>
                        <?php endif; ?>
                    </div>

                    <?php if ($photoCount > 1): ?>
                        <div class="sol-thumbs-wrap">
                            <div class="sol-thumbs" id="galleryThumbs" data-thumbs-strip>
                                <?php foreach ($photos as $idx => $photo): ?>
                                    <button class="thumb-btn<?php echo $idx === 0 ? ' active' : ''; ?>" type="button"
                                            data-idx="<?php echo $idx; ?>"
                                            data-img="<?php echo e($photo); ?>"
                                            data-caption="<?php echo e($work['title'] . ' ' . ($idx + 1)); ?>"
                                            aria-label="<?php echo te('ຮູບທີ %d', $idx + 1); ?>">
                                        <img src="<?php echo e($photo); ?>" alt="<?php echo e($work['title'] . ' ' . ($idx + 1)); ?>" loading="lazy">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($photoCount > 5): /* ‹ ເຊື່ອງຢູ່ຕົ້ນແຖບ, ກົດ › ເລື່ອນ 1 ຮູບແລ້ວ ‹ ຈຶ່ງຂຶ້ນມາ (main.js syncThumbArrows) */ ?>
                                <button class="sol-thumbs-prev" type="button" data-thumbs-prev hidden aria-label="<?php echo te('ຮູບກ່ອນໜ້າ'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg></button>
                                <button class="sol-thumbs-next" type="button" data-thumbs-next aria-label="<?php echo te('ຮູບຖັດໄປ'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ຄໍລຳຂວາ: ຂໍ້ມູນຮູບແບບທີ 1 (Work Detail + ມາດຕະຖານ + ປຸ່ມຕິດຕໍ່) -->
                <div class="wk-side-info-col">
                    <!-- 1. ກາດຂໍ້ມູນຜົນງານ -->
                    <div class="wk-detail-main">
                        <div class="wk-detail-head">
                            <?php if (!empty($work['logo'])): ?>
                                <span class="wk-detail-logo"><img src="<?php echo e($work['logo']); ?>" alt="<?php echo e($work['shop_name'] ?: $work['title']); ?>"></span>
                            <?php endif; ?>
                            <div class="wk-detail-head-body">
                                <?php if (!empty($work['shop_name']) || !empty($work['category'])): ?>
                                    <div class="wk-detail-badges">
                                        <?php if (!empty($work['shop_name'])): ?>
                                            <span class="wk-detail-shop"><?php echo e($work['shop_name']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($work['category'])): ?>
                                            <span class="wk-cat-pill"><?php echo e($work['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <h1 class="wk-detail-title"><?php echo e($work['title']); ?></h1>
                                <p class="wk-date">
                                    <span class="wk-meta-item">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        <?php echo e(workDate($work['date'])); ?>
                                    </span>
                                    <?php if ($photoCount > 0): ?>
                                        <span class="wk-meta-dot">·</span>
                                        <span class="wk-meta-item">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <?php echo e(t('%d ຮູບ', $photoCount)); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if (!empty($work['text'])): ?>
                            <p class="wk-detail-text"><?php echo e($work['text']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- 2. ກາດມາດຕະຖານການຕິດຕັ້ງ WlaoDev -->
                    <aside class="wk-detail-side">
                        <div class="wk-side-head">
                            <span class="wk-side-dot"></span>
                            <span class="wk-side-label"><?php echo te('ມາດຕະຖານການຕິດຕັ້ງ WlaoDev'); ?></span>
                        </div>
                        <div class="wk-side-perks">
                            <div class="wk-perk-item">
                                <span class="wk-perk-icon">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </span>
                                <div>
                                    <strong><?php echo te('ຕິດຕັ້ງຟຣີເຖິງສະຖານທີ່ / ອອນລາຍ'); ?></strong>
                                    <span><?php echo te('ໃກ້ລົງຕິດຕັ້ງໃຫ້ເຖິງຮ້ານ · ຖ້າຢູ່ໄກ ພວກເຮົາຕິດຕັ້ງ ແລະ ສອນອອນລາຍໃຫ້'); ?></span>
                                </div>
                            </div>
                            <div class="wk-perk-item">
                                <span class="wk-perk-icon">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </span>
                                <div>
                                    <strong><?php echo te('ສອນໃຊ້ງານໃຫ້ຟຣີ'); ?></strong>
                                    <span><?php echo te('ແນະນຳທຸກຂັ້ນຕອນຈົນຄ່ອງແຄ້ວ ທັງໜ້າຮ້ານ ແລະ ອອນລາຍ'); ?></span>
                                </div>
                            </div>
                            <div class="wk-perk-item">
                                <span class="wk-perk-icon">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                </span>
                                <div>
                                    <strong><?php echo te('ດູແລຫຼັງການຂາຍ 24 ຊົ່ວໂມງ'); ?></strong>
                                    <span><?php echo te('ທີມງານຊ່ວຍແກ້ໄຂບັນຫາທັນທີ'); ?></span>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- 3. ປຸ່ມຈັດການ / ສອບຖາມ Fastwork Style -->
                    <div class="wk-side-actions">
                        <a class="wk-btn-action-wa" href="https://wa.me/8562055949007?text=<?php echo rawurlencode(t('ສົນໃຈລະບົບ: ') . $work['title']); ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.5 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1.1.1-1.8-.1a16.4 16.4 0 0 1-6.5-5.7c-.9-1.2-1.5-2.6-1.5-3.9 0-1.3.7-2 1-2.3.3-.3.6-.3.8-.3h.6c.2 0 .5 0 .7.5.2.6.8 2 .9 2.1.1.2.1.4 0 .6-.1.2-.2.4-.4.6l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.4 1.8 2.2 1.2 1.1 2.2 1.4 2.5 1.6.3.1.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.4-.2.7-.1.3.1 1.8.9 2.1 1 .3.2.5.2.6.4.1.1.1.7-.1 1.3z"/></svg>
                            <span><?php echo te('ສອບຖາມຜ່ານ WhatsApp'); ?></span>
                        </a>
                        <a class="wk-btn-action-chat" href="<?php echo url('pages/contact.php?subject=' . rawurlencode(t('ສົນໃຈລະບົບ: ') . $work['title'])); ?>">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <span><?php echo te('ແຊັດສອບຖາມທັນທີ'); ?></span>
                        </a>
                        <a class="wk-btn-action-back" href="<?php echo url('pages/portfolio.php'); ?>">
                            ← <?php echo te('ກັບໄປໜ້າຜົນງານທັງໝົດ'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ກາດ CTA ດ້ານລຸ່ມ -->
            <div class="wk-cta-card mt-4">
                <div class="wk-cta-glow" aria-hidden="true"></div>
                <div class="wk-cta-info">
                    <span class="wk-cta-badge">
                        <span class="wk-cta-pulse"></span>
                        <?php echo te('ພ້ອມບໍລິການທົ່ວປະເທດ'); ?>
                    </span>
                    <h2 class="wk-cta-title"><?php echo te('ສົນໃຈຕິດຕັ້ງລະບົບແບບຮ້ານນີ້ບໍ?'); ?></h2>
                    <p class="wk-cta-desc"><?php echo te('ປຶກສາຟຣີ ພ້ອມຕິດຕັ້ງຟຣີ ແລະ ສອນໃຊ້ງານໃຫ້ຟຣີ ດູແລຫຼັງການຂາຍ 24 ຊົ່ວໂມງ'); ?></p>
                </div>
                <div class="wk-cta-actions">
                    <a class="wk-btn-wa" href="https://wa.me/8562055949007?text=<?php echo rawurlencode(t('ສົນໃຈລະບົບ: ') . $work['title']); ?>" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.5 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1.1.1-1.8-.1a16.4 16.4 0 0 1-6.5-5.7c-.9-1.2-1.5-2.6-1.5-3.9 0-1.3.7-2 1-2.3.3-.3.6-.3.8-.3h.6c.2 0 .5 0 .7.5.2.6.8 2 .9 2.1.1.2.1.4 0 .6-.1.2-.2.4-.4.6l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.4 1.8 2.2 1.2 1.1 2.2 1.4 2.5 1.6.3.1.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.4-.2.7-.1.3.1 1.8.9 2.1 1 .3.2.5.2.6.4.1.1.1.7-.1 1.3z"/></svg>
                        <span><?php echo te('ສອບຖາມຜ່ານ WhatsApp'); ?></span>
                    </a>
                    <a class="wk-btn-chat" href="<?php echo url('pages/contact.php?subject=' . rawurlencode(t('ສົນໃຈລະບົບ: ') . $work['title'])); ?>">
                        <span><?php echo te('ແຊັດສອບຖາມທັນທີ'); ?></span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/components/gallery_viewer.php'; ?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
