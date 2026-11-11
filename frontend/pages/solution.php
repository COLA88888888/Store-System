<?php
/**
 * ໜ້າລາຍລະອຽດລະບົບ POS ແລະ ອຸປະກອນ POS (Solution / Hardware Detail Page)
 *   - ຖ້າເປັນສິນຄ້າ (hardware): ສະແດງຂໍ້ມູນການຊື້, ລາຄາ, ລາຍລະອຽດສິນຄ້າ + ຟອມທີ່ຢູ່ຈັດສົ່ງ, ບໍລິສັດຂົນສົ່ງໃນລາວ + ສາຂາ,
 *     ທະນາຄານຈາກລະບົບຫຼັງບ້ານ ແລະ ອັບໂຫຼດຮູບສະລິບໂອນເງິນ (components/product_order_form.php)
 *   - ຖ້າເປັນລະບົບ POS (pos): ສະແດງຂໍ້ມູນລະບົບ, ຄຸນສົມບັດເດັ່ນ ແລະ ຊ່ອງທາງຕິດຕໍ່ສອບຖາມ
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

$slug = trim((string)($_GET['slug'] ?? 'drinks'));
$solution = getSolutionDetail($slug);

if (!$solution) {
    http_response_code(404);
}

$isProduct = $solution && $solution['category_key'] === 'hardware';
// SEO: ລະບົບ POS → "ລະບົບ POS ຮ້ານອາຫານ" (ຄຳທີ່ຄົນຄົ້ນ); ອຸປະກອນ → ຊື່ສິນຄ້າ
$pageTitle = $solution
    ? ($solution['category_key'] === 'hardware' ? $solution['title'] : $solution['category_label'] . ' ' . $solution['title'])
    : t('ບໍ່ພົບລາຍການທີ່ເລືອກ');
$currentPage = $isProduct ? 'equipment' : 'solutions';

$couriers = $isProduct ? getLaoCouriers() : [];
$provinces = $isProduct ? getLaoProvinces() : [];
$orderErrors = [];
$orderForm = [
    'qty'             => max(1, (int)($_POST['qty'] ?? 1)),
    'name'            => '',
    'tel'             => '',
    'email'           => '',
    'province'        => '',
    'district'        => '',
    'village'         => '',
    'address'         => '',
    'courier_code'    => 'anousith',
    'courier_branch'  => '',
    'bank_account_id' => 0,
    'note'            => '',
];
if ($isProduct && ($member = currentMember($pdo))) {
    $orderForm['name']  = $member['name'];
    $orderForm['tel']   = $member['tel'];
    $orderForm['email'] = (string)$member['email'];
}

if ($isProduct && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['order_submit'])) {
    [$validated, $orderErrors] = validateOrderCustomer($_POST, []);
    $orderForm = array_merge($orderForm, $validated, ['qty' => max(1, min(999, (int)($_POST['qty'] ?? 1)))]);

    if (!checkCsrf()) {
        $orderErrors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif (!$pdo) {
        $orderErrors['form'] = t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ ກະລຸນາລອງໃໝ່ພາຍຫຼັງ');
    }

    if (!$orderErrors) {
        $itemData = [
            'product_id'   => 0,
            'item_slug'    => $solution['slug'],
            'product_name' => $solution['title'],
            'price'        => 0,
        ];
        $res = createDirectProductOrder($pdo, $shopStoreId, $itemData, (int)$orderForm['qty'], $validated);
        if ($res['success']) {
            $_SESSION['last_order_no'] = $res['order_no'];
            redirect('pages/order_success.php?no=' . urlencode($res['order_no']));
        }
        $orderErrors['form'] = $res['message'];
    }
}

if ($solution) {
    // ຂ້າມຮູບທີ່ໄຟລ໌ບໍ່ມີແລ້ວ (ເຊັ່ນ ຮູບແບນເນີເກົ່າທີ່ຖືກລຶບ) — ບໍ່ໃຫ້ແກເລີຣີ / ໜ້າເບິ່ງຮູບເຕັມຈໍ ຂຶ້ນຮູບແຕກ
    $solution['images'] = array_values(array_filter($solution['images'] ?? [], function (array $im): bool {
        $rel = ltrim(substr((string)strtok((string)($im['url'] ?? ''), '?'), strlen(siteRoot())), '/');
        if ($rel === '' || preg_match('#^https?:#', (string)$im['url'])) {
            return $rel !== '';
        }
        $root = dirname(__DIR__, 2);
        return is_file(str_starts_with($rel, 'backend/') ? "$root/$rel" : "$root/frontend/$rel");
    }));
    // ສັ້ນ ~160 ຕົວ (Google ຕັດສ່ວນເກີນ): ປະໂຫຍກດຽວ (tagline) + ຊວນຕິດຕໍ່
    $pageDescription = mb_strimwidth($solution['tagline'] . ' — ' . t('ຕິດຕັ້ງຟຣີ ສອນໃຊ້ງານໃຫ້ຟຣີ, ດູແລ 24 ຊົ່ວໂມງ'), 0, 170, '…');
    $pageImage = $solution['images'][0]['url'] ?? null;
    $pageCanonical = 'pages/solution.php?slug=' . rawurlencode($solution['slug']);
    $pageOgType = 'product';
    $pageJsonLd = [
        productJsonLd($solution['title'], $pageDescription, $pageImage, $pageCanonical, 0), // 0 = ບໍ່ເປີດເຜີຍລາຄາໃນ JSON-LD
        breadcrumbJsonLd([[t('ໜ້າຫຼັກ'), ''], [$solution['category_label'], $isProduct ? 'pages/equipment.php' : ''], [$solution['title'], $pageCanonical]]),
    ];
}

$pageStyles = array_merge($pageStyles ?? [], ['css/product_gallery.css']);   // ແກເລີຣີແບບ Fastwork
require dirname(__DIR__) . '/layouts/header.php';
?>

<section class="section sol-page">
    <div class="container sol-container">
        <?php if (!$solution): ?>
            <div class="empty">
                <p><?php echo te('ບໍ່ພົບຂໍ້ມູນທີ່ທ່ານຕ້ອງການ.'); ?></p>
                <div class="center mt-2">
                    <a class="btn btn-primary" href="<?php echo url(); ?>"><?php echo te('ກັບໄປໜ້າຫຼັກ'); ?></a>
                </div>
            </div>
        <?php else:
            $images = $solution['images'];
            $firstImg = $images[0] ?? null;
            $defaultSubject = $isProduct
                ? t('ສົນໃຈ %s', $solution['title'])
                : t('ສົນໃຈລະບົບ %s', $solution['title']);
            $chatHref = url('pages/contact.php?subject=' . rawurlencode($defaultSubject));
            $waHref = 'https://wa.me/8562055949007?text=' . rawurlencode($defaultSubject);
            $fbHref = fbChatUrl($defaultSubject);
            $catKey = $solution['category_key'];
            $relatedItems = array_values(array_filter(
                $siteSolutions[$catKey]['items'] ?? [],
                fn($it) => $it['slug'] !== $solution['slug']
            ));
            $relatedItems = array_slice($relatedItems, 0, 4);
            $priceVal = (float)($solution['price_starting'] ?? 0);
        ?>
            <nav class="sol-bc" aria-label="Breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo te('ໜ້າຫຼັກ'); ?></a>
                <span>/</span>
                <?php if ($isProduct): ?>
                    <a href="<?php echo url('pages/equipment.php'); ?>"><?php echo e($solution['category_label']); ?></a>
                <?php else: ?>
                    <span><?php echo e($solution['category_label']); ?></span>
                <?php endif; ?>
                <span>/</span>
                <strong><?php echo e($solution['title']); ?></strong>
            </nav>

            <!-- ກາດຫຼັກ 2 ຖັນ (ຊ້າຍ: ຮູບພາບ, ຂວາ: ຂໍ້ມູນການຊື້ & ລາຍລະອຽດ) -->
            <div class="sol-main-card">
                <div class="sol-gallery<?php echo $isProduct ? ' is-product' : ''; ?>" id="solutionGallery" data-gallery>
                    <div class="sol-stage">
                        <span class="sol-counter" id="galleryCounter">1/<?php echo count($images); ?></span>
                        <button class="sol-zoom" type="button" id="btnGalleryZoom" aria-label="<?php echo te('ເບິ່ງຮູບເຕັມຈໍ'); ?>">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                        </button>
                        <div class="sol-viewport" id="galleryViewport" style="--bg: url('<?php echo e($firstImg['url'] ?? ''); ?>')"><?php /* --bg = ພື້ນມົວຫຼັງຮູບ; main.js ປ່ຽນຕາມຮູບທີ່ເລືອກ */ ?>
                            <img id="galleryMainImg" src="<?php echo e($firstImg['url'] ?? ''); ?>" alt="<?php echo e($solution['title']); ?>">
                        </div>
                        <?php if (count($images) > 1): ?>
                            <button class="sol-nav prev" type="button" aria-label="<?php echo te('ຮູບກ່ອນໜ້າ'); ?>" data-gallery-prev>‹</button>
                            <button class="sol-nav next" type="button" aria-label="<?php echo te('ຮູບຖັດໄປ'); ?>" data-gallery-next>›</button>
                        <?php endif; ?>
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div class="sol-thumbs-wrap">
                        <div class="sol-thumbs" id="galleryThumbs" data-thumbs-strip>
                            <?php foreach ($images as $idx => $img): ?>
                                <button class="thumb-btn<?php echo $idx === 0 ? ' active' : ''; ?>" type="button"
                                        data-idx="<?php echo $idx; ?>"
                                        data-img="<?php echo e($img['url']); ?>"
                                        data-caption="<?php echo e($img['caption'] ?? ''); ?>"
                                        aria-label="<?php echo te('ຮູບທີ %d', $idx + 1); ?>">
                                    <img src="<?php echo e($img['thumb'] ?? $img['url']); ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($images) > 5): /* ຮູບເກີນ 5 → ປຸ່ມເລື່ອນແຖບຮູບນ້ອຍ (main.js [data-thumbs-next]) */ ?>
                            <button class="sol-thumbs-next" type="button" data-thumbs-next aria-label="<?php echo te('ຮູບຖັດໄປ'); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></button>
                        <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="sol-info">
                    <span class="sol-cat-tag"><?php echo e($solution['category_label']); ?></span><?php /* ບໍ່ໃສ່ emoji */ ?>
                    <h1 class="sol-title"><?php echo e($solution['title']); ?></h1>
                    <?php /* ລະບົບ POS: ປະໂຫຍກດຽວ (tagline) ແທນຫຍໍ້ໜ້າຍາວ — ລາຍລະອຽດຢູ່ໃນສ່ວນ "ບັນຫາ → ແກ້ໄດ້" ລຸ່ມນີ້ */ ?>
                    <p class="sol-desc<?php echo $isProduct ? '' : ' is-lead'; ?>"><?php echo e($isProduct ? $solution['overview'] : $solution['tagline']); ?></p>

                    <?php if ($isProduct): ?>
                        <div class="sol-perks">
                            <span>✓ <?php echo te('ມີສິນຄ້າພ້ອມຈັດສົ່ງ'); ?></span>
                            <span>✓ <?php echo te('ຮັບປະກັນອຸປະກອນ 1 ປີ'); ?></span>
                            <span>✓ <?php echo te('ຈັດສົ່ງທົ່ວປະເທດລາວ'); ?></span>
                            <span>✓ <?php echo te('ກວດສອບສະລິບ & ສົ່ງໄວ'); ?></span>
                        </div>

                        <?php $isMember = currentMember($pdo) !== null; ?>
                        <div class="sol-contact-panel is-buy-panel">
                            <div class="sol-contact-head">
                                <div>
                                    <span class="sol-contact-kicker"><?php echo te('ລາຄາສິນຄ້າ'); ?></span>
                                    <span class="sol-member-pill"><?php echo $isMember ? te('ສິດສະມາຊິກ: ຮັບສ່ວນຫຼຸດພິເສດ 5% ຈາກລາຄາປົກກະຕິ') : te('ສະມາຊິກຮັບສ່ວນຫຼຸດພິເສດ 5%'); ?></span>
                                </div>
                                <?php if ($priceVal > 0 && !canSeePrices()): /* ອອກຈາກລະບົບ: ບໍ່ສະແດງລາຄາ */ ?>
                                    <strong class="sol-contact-price mo-price">
                                        <span class="sol-price-locked">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <?php echo te('ສະເພາະສະມາຊິກ'); ?>
                                        </span>
                                    </strong>
                                <?php elseif ($priceVal > 0): ?>
                                    <strong class="sol-contact-price mo-price">
                                        <?php echo money($priceVal); ?>
                                        <?php if (!empty($solution['old_price']) && (float)$solution['old_price'] > $priceVal): ?>
                                            <s><?php echo money((float)$solution['old_price']); ?></s>
                                        <?php endif; ?>
                                    </strong>
                                <?php else: ?>
                                    <strong class="sol-contact-price"><?php echo te('ສອບຖາມລາຄາພິເສດ (ປຶກສາຟຣີ)'); ?></strong>
                                <?php endif; ?>
                            </div>
                            <?php /* ບໍ່ມີປຸ່ມກະຕ່າ (ລູກຄ້າຕິດຕໍ່ສອບຖາມລາຄາໂດຍກົງ) — ຟອມສັ່ງຊື້ ແລະ ທີ່ຢູ່ຈັດສົ່ງ ຍັງຢູ່ລຸ່ມ */ ?>
                            <div class="sol-contact-btns">
                                <a class="sol-btn is-chat" href="<?php echo e($chatHref); ?>">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                    <span><?php echo te('ແຊັດສອບຖາມ'); ?></span>
                                </a>
                                <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.7-.1 1.4z"/></svg>
                                    <span>WhatsApp</span>
                                </a>
                                <a class="sol-btn is-tel" href="tel:+8562029777302">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                                    <span>020 29 777 302</span>
                                </a>
                                <a class="sol-btn is-fb" href="<?php echo e($fbHref); ?>" target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M15.12 5.32H17V2.14A26.11 26.11 0 0 0 14.26 2c-2.72 0-4.58 1.66-4.58 4.7v2.62H6.61v3.56h3.07V22h3.68v-9.12h3.06l.46-3.56h-3.52V6.65c0-1.05.29-1.73 1.76-1.73z"/></svg>
                                    <span>Facebook Page</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="sol-perks is-icons">
                            <span><i aria-hidden="true">✓</i><?php echo te('ຕິດຕັ້ງຟຣີ'); ?></span>
                            <span><i aria-hidden="true">✓</i><?php echo te('ສອນໃຊ້ງານໃຫ້ຟຣີ'); ?></span>
                            <span><i aria-hidden="true">✓</i><?php echo te('ດູແລ 24 ຊົ່ວໂມງ'); ?></span>
                            <span><i aria-hidden="true">✓</i><?php echo te('ປັບແຕ່ງຕາມຮ້ານ'); ?></span>
                        </div>

                        <div class="sol-contact-panel is-glow">
                            <div class="sol-contact-head">
                                <div>
                                    <span class="sol-contact-kicker"><?php echo te('ລາຄາສິນຄ້າ ແລະ ລະບົບ'); ?></span>
                                    <span class="sol-live"><i class="srv-live-dot" aria-hidden="true"></i><?php echo te('ປຶກສາຟຣີ · ຕອບໄວ'); ?></span>
                                </div>
                                <?php if ($priceVal > 0 && !canSeePrices()): /* ອອກຈາກລະບົບ: ບໍ່ສະແດງລາຄາ */ ?>
                                    <strong class="sol-contact-price mo-price">
                                        <span class="sol-price-locked">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <?php echo te('ສະເພາະສະມາຊິກ'); ?>
                                        </span>
                                    </strong>
                                <?php elseif ($priceVal > 0): ?>
                                    <strong class="sol-contact-price mo-price">
                                        <?php echo money($priceVal); ?>
                                        <?php if (!empty($solution['old_price']) && (float)$solution['old_price'] > $priceVal): ?>
                                            <s><?php echo money((float)$solution['old_price']); ?></s>
                                        <?php endif; ?>
                                    </strong>
                                <?php else: ?>
                                    <strong class="sol-contact-price"><?php echo te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></strong>
                                <?php endif; ?>
                            </div>
                            <div class="sol-contact-btns">
                                <a class="sol-btn is-chat" href="<?php echo e($chatHref); ?>">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                    <span><?php echo te('ແຊັດສອບຖາມ'); ?></span>
                                </a>
                                <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.7-.1 1.4z"/></svg>
                                    <span>WhatsApp</span>
                                </a>
                                <a class="sol-btn is-tel" href="tel:+8562029777302">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                                    <span>020 29 777 302</span>
                                </a>
                                <a class="sol-btn is-fb" href="<?php echo e($fbHref); ?>" target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M15.12 5.32H17V2.14A26.11 26.11 0 0 0 14.26 2c-2.72 0-4.58 1.66-4.58 4.7v2.62H6.61v3.56h3.07V22h3.68v-9.12h3.06l.46-3.56h-3.52V6.65c0-1.05.29-1.73 1.76-1.73z"/></svg>
                                    <span>Facebook Page</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isProduct):
                $orderItem = [
                    'product_id'   => 0,
                    'item_slug'    => $solution['slug'],
                    'product_name' => $solution['title'],
                    'price'        => 0,
                    'img'          => $firstImg['url'] ?? '',
                ];
                // ລູກຄ້າທົ່ວໄປ: ກາດລົງທະບຽນ (ບໍ່ມີຟອມຂໍໃບສະເໜີລາຄາ); ສະມາຊິກ: ຟອມສອບຖາມ (ອຸປະກອນນີ້ບໍ່ມີລາຄາໃນລະບົບ)
                require dirname(__DIR__) . '/components/' . (currentMember($pdo) ? 'product_order_form.php' : 'member_cta.php');
            endif; ?>

            <?php if (!$isProduct): ?>
                <!-- ລະບົບ POS: ບັນຫາ → ແກ້ໄດ້, ມີຫຍັງໃນລະບົບ, 4 ຂັ້ນຕອນ, FAQ, ແຖບປຶກສາ -->
                <?php require dirname(__DIR__) . '/components/solution_pos_sections.php'; ?>
            <?php else: ?>
            <!-- ລາຍລະອຽດສິນຄ້າ / ຄຸນສົມບັດເດັ່ນ (ອຸປະກອນ) -->
            <?php
            // ອຸປະກອນ: ແບບດຽວກັບໜ້າລະບົບ POS (CSS .solx-*) — ຄຸນສົມບັດ = ກາດ ✓, ຈຸດເດັ່ນ = ກາດໄອຄອນເສັ້ນ (ບໍ່ໃຊ້ emoji)
            $hwIcons = [
                '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',                                                         // ໄວ ທົນທານ
                '<path d="M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0zM12 18v4"/>',                                      // ເຊື່ອມຕໍ່ (ປລັກ)
                '<path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/><path d="m9 12 2 2 4-4"/>',                  // ຮັບປະກັນ
                '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
            ];
            $hwSvg = fn(string $p) => '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
            ?>
            <section class="solx-block">
                <div class="solx-head" data-reveal>
                    <span class="solx-kicker"><?php echo te('ສະເປັກ'); ?></span>
                    <h2><?php echo te('ລາຍລະອຽດ ແລະ ຄຸນສົມບັດສິນຄ້າ'); ?></h2>
                </div>
                <div class="solx-feat-grid">
                    <?php foreach ($solution['features'] as $i => $feat): ?>
                        <div class="solx-feat" data-reveal style="--reveal-delay: <?php echo ($i % 3) * 90; ?>ms">
                            <span class="solx-feat-ok" aria-hidden="true"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <span><?php echo e($feat); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="solx-block">
                <div class="solx-head" data-reveal>
                    <span class="solx-kicker"><?php echo te('ອຸ່ນໃຈທຸກການຊື້'); ?></span>
                    <h2><?php echo te('ການຮັບປະກັນ ແລະ ບໍລິການຫຼັງການຂາຍ'); ?></h2>
                </div>
                <div class="hwx-grid">
                    <?php foreach (array_slice($solution['importance'], 0, 4) as $i => $imp): ?>
                        <div class="hwx-card" data-reveal style="--reveal-delay: <?php echo $i * 110; ?>ms">
                            <span class="hwx-ico"><?php echo $hwSvg($hwIcons[$i] ?? $hwIcons[0]); ?></span>
                            <strong><?php echo e($imp['title']); ?></strong>
                            <p><?php echo e($imp['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="solx-cta" data-reveal>
                <div class="solx-cta-body">
                    <div class="solx-cta-brand">
                        <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="22" height="15">
                        <span>WlaoDev for Business</span>
                    </div>
                    <strong><?php echo te('ຢາກຮູ້ລາຄາ ຫຼື ບໍ່ແນ່ໃຈວ່າລຸ້ນໃດເໝາະກັບຮ້ານ?'); ?></strong>
                    <span><?php echo te('ປຶກສາຟຣີ ບໍ່ມີຄ່າໃຊ້ຈ່າຍ — ທີມງານຕອບໄວ 24 ຊົ່ວໂມງ'); ?></span>
                </div>
                <div class="solx-cta-btns">
                    <?php if (!empty($chatHref)): ?>
                        <a class="sol-btn is-chat" href="<?php echo e($chatHref); ?>"><?php echo te('ແຊັດສອບຖາມ'); ?></a>
                    <?php endif; ?>
                    <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">WhatsApp</a>
                    <a class="sol-btn sol-btn-primary" href="<?php echo url('pages/contact.php'); ?>">
                        <span><?php echo te('ປຶກສາຟຣີ'); ?></span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($relatedItems): ?>
                <div class="sol-related">
                    <div class="sol-related-head">
                        <h2><?php echo te('ລາຍການອື່ນໃນໝວດ %s', $solution['category_label']); ?></h2>
                        <?php if ($isProduct): ?>
                            <a href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ເບິ່ງທັງໝົດ →'); ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="sol-related-grid<?php echo $isProduct ? ' is-product' : ''; ?>">
                        <?php foreach ($relatedItems as $rel):
                            $relImg = solutionImage($rel['slug']);
                        ?>
                            <a class="sol-rel-card" href="<?php echo url('pages/solution.php?slug=' . rawurlencode($rel['slug'])); ?>">
                                <span class="sol-rel-thumb">
                                    <?php if ($relImg): ?>
                                        <img src="<?php echo e($relImg); ?>" alt="<?php echo e($rel['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <span><?php echo $rel['icon']; ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="sol-rel-body">
                                    <strong><?php echo e($rel['title']); ?></strong>
                                    <small class="sol-rel-contact"><?php echo te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/components/gallery_viewer.php'; /* ເບິ່ງຮູບເຕັມຈໍ: ລຳດັບ, ຊູມ, ສະໄລ, ເຕັມຈໍ, ແຖບຮູບນ້ອຍ */ ?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
