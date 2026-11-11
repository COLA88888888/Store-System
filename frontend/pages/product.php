<?php
/**
 * ໜ້າລາຍລະອຽດສິນຄ້າ (GET: id = product_id).
 *   - ສະມາຊິກ: ເຫັນລາຄາ + ຟອມສັ່ງຊື້ຈິງ (components/member_order_form.php): ທີ່ຢູ່, ຂົນສົ່ງ + ສາຂາ, ທະນາຄານ, ສະລິບ → pages/my_order.php
 *   - ລູກຄ້າທົ່ວໄປ: ບໍ່ເຫັນລາຄາ ແລະ ບໍ່ມີຟອມຂໍໃບສະເໜີລາຄາ — ກາດລົງທະບຽນ / ເຂົ້າສູ່ລະບົບ (components/member_cta.php)
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$product = $pdo ? getProduct($pdo, $shopStoreId, (int)($_GET['id'] ?? 0)) : null;
if (!$product) {
    http_response_code(404);
}
$pageTitle = $product ? $product['product_name'] : t('ບໍ່ພົບສິນຄ້າ');
$currentPage = 'equipment';

$couriers = $product ? getLaoCouriers() : [];
$provinces = $product ? getLaoProvinces() : [];
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

// ສະມາຊິກ: ສັ່ງຊື້ຈິງ (ລາຄາ + ທີ່ຢູ່ + ສາຂາຂົນສົ່ງ + ສະລິບ) — components/member_order_form.php, models/member_orders.php
$member = $product ? currentMember($pdo) : null;
$banks = $member ? getActiveBankAccounts($pdo, $shopStoreId) : [];
if ($member) {
    $orderForm = array_merge($orderForm, memberOrderDefaults($member));
}
if ($member && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['member_order'])) {
    [$validated, $orderErrors] = memberValidateOrder($member, $_POST, $_FILES['slip'] ?? null, $banks);
    $orderForm = array_merge($orderForm, $_POST, ['qty' => max(1, min(999, (int)($_POST['qty'] ?? 1)))]);
    if (memberNeedsPhone($member)) {
        $orderErrors['form'] = t('ກະລຸນາເພີ່ມເບີໂທຈິງໃນບັນຊີກ່ອນສັ່ງຊື້');
    } elseif (!checkCsrf()) {
        $orderErrors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    }
    if (!$orderErrors) {
        $res = createDirectProductOrder($pdo, $shopStoreId, [
            'product_id'   => (int)$product['product_id'],
            'item_slug'    => null,
            'product_name' => $product['product_name'],
            'price'        => (float)$product['price'],
        ], (int)$orderForm['qty'], $validated);
        if ($res['success']) {
            memberRememberAddress($pdo, $member, $validated);
            memberMailOrder($pdo, (int)$member['id'], $res['order_no']);   // ອີເມວ "ໄດ້ຮັບອໍເດີແລ້ວ"
            flash($validated['slip_img'] ? t('ສັ່ງຊື້ສຳເລັດ! ທີມງານກຳລັງກວດສະລິບ') : t('ສັ່ງຊື້ສຳເລັດ! ກະລຸນາໂອນເງິນ ແລະ ແນບສະລິບ'));
            redirect('pages/my_orders.php?s=all&track=' . urlencode($res['order_no']));
        }
        $orderErrors['form'] = $res['message'];
    }
}

if ($product && !$member && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['order_submit'])) {
    [$validated, $orderErrors] = validateOrderCustomer($_POST, []);
    $orderForm = array_merge($orderForm, $validated, ['qty' => max(1, min(999, (int)($_POST['qty'] ?? 1)))]);

    if (!checkCsrf()) {
        $orderErrors['form'] = t('ໝົດເວລາຂອງຟອມ ກະລຸນາລອງສົ່ງອີກຄັ້ງ');
    } elseif (!$pdo) {
        $orderErrors['form'] = t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ ກະລຸນາລອງໃໝ່ພາຍຫຼັງ');
    }

    if (!$orderErrors) {
        $itemData = [
            'product_id'   => (int)$product['product_id'],
            'item_slug'    => null,
            'product_name' => $product['product_name'],
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

if ($product) {
    $pageCanonical = 'pages/product.php?id=' . (int)$product['product_id'];
    $pageImage = productImage($product['img_url']);
    // ບໍ່ໃສ່ລາຄາໃນ meta / JSON-LD (Google ແລະ ຄົນເປີດ source ຈະເຫັນ) — ຮ້ານບໍ່ເປີດເຜີຍລາຄາ ໃຫ້ລູກຄ້າຕິດຕໍ່ສອບຖາມ
    $pageDescription = t('%s — ຕິດຕໍ່ສອບຖາມລາຄາ ແລະ ສັ່ງຊື້ໄດ້ທີ່ %s', $product['product_name'], $company['name']);
    $pageOgType = 'product';
    $pageJsonLd = [
        productJsonLd($product['product_name'], $pageDescription, $pageImage, $pageCanonical, 0, productInStock($product)),
        breadcrumbJsonLd([[t('ໜ້າຫຼັກ'), ''], [t('ອຸປະກອນ POS'), 'pages/equipment.php'], [$product['product_name'], $pageCanonical]]),
    ];
}
$related = $product ? getRelatedProducts($pdo, $shopStoreId, $product) : [];
$pageStyles = array_merge($pageStyles ?? [], ['css/product_gallery.css']);   // ແກເລີຣີແບບ Fastwork
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section sol-page">
    <div class="container sol-container">
        <?php if (!$product): ?>
            <p class="empty"><?php echo te('ບໍ່ພົບສິນຄ້ານີ້ ຫຼື ສິນຄ້າຖືກຢຸດຂາຍແລ້ວ.'); ?></p>
            <div class="center"><a class="btn btn-primary" href="<?php echo url('pages/equipment.php'); ?>"><?php echo te('ກັບໄປໜ້າສິນຄ້າ'); ?></a></div>
        <?php else:
            $img = productImage($product['img_url']);
            $inStock = productInStock($product);
            $priceVal = (float)$product['price'];
            $defaultSubject = t('ສົນໃຈ %s', $product['product_name']);
            $chatHref = url('pages/contact.php?subject=' . rawurlencode($defaultSubject));
            $waHref = 'https://wa.me/8562055949007?text=' . rawurlencode($defaultSubject);
        ?>
            <nav class="sol-bc" aria-label="Breadcrumb">
                <a href="<?php echo url(); ?>"><?php echo te('ໜ້າຫຼັກ'); ?></a>
                <span>/</span>
                <a href="<?php echo url('pages/equipment.php'); ?>"><?php echo te('ອຸປະກອນ POS'); ?></a>
                <?php if ($product['category_name']): ?>
                    <span>/</span>
                    <a href="<?php echo url('pages/equipment_list.php?cat=' . (int)$product['category_id'] . '#eq-results'); ?>"><?php echo e($product['category_name']); ?></a>
                <?php endif; ?>
                <span>/</span>
                <strong><?php echo e($product['product_name']); ?></strong>
            </nav>

            <div class="sol-main-card">
                <?php $images = getProductImages($pdo, $product); /* ຮູບຫຼັກ + ຮູບຫຼາຍມຸມ (models/products.php) — ແກເລີຣີດຽວກັບ pages/solution.php (assets/js/main.js #solutionGallery) */ ?>
                <div class="sol-gallery is-product" id="solutionGallery" data-gallery>
                    <div class="sol-stage">
                        <?php if ($images): ?>
                            <?php if (count($images) > 1): ?><span class="sol-counter" id="galleryCounter">1/<?php echo count($images); ?></span><?php endif; ?>
                            <button class="sol-zoom" type="button" id="btnGalleryZoom" aria-label="<?php echo te('ເບິ່ງຮູບເຕັມຈໍ'); ?>">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                            </button>
                            <div class="sol-viewport" id="galleryViewport" style="--bg: url('<?php echo e($images[0]['url']); ?>')">
                                <img id="galleryMainImg" src="<?php echo e($images[0]['url']); ?>" alt="<?php echo e($product['product_name']); ?>">
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="sol-nav prev" type="button" aria-label="<?php echo te('ຮູບກ່ອນໜ້າ'); ?>" data-gallery-prev>‹</button>
                                <button class="sol-nav next" type="button" aria-label="<?php echo te('ຮູບຖັດໄປ'); ?>" data-gallery-next>›</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="sol-viewport"><span class="product-noimg">📦</span></div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="sol-thumbs-wrap">
                        <div class="sol-thumbs" id="galleryThumbs" data-thumbs-strip>
                            <?php foreach ($images as $idx => $gi): ?>
                                <button class="thumb-btn<?php echo $idx === 0 ? ' active' : ''; ?>" type="button" data-idx="<?php echo $idx; ?>" data-img="<?php echo e($gi['url']); ?>" data-caption="" aria-label="<?php echo te('ຮູບທີ %d', $idx + 1); ?>">
                                    <img src="<?php echo e($gi['thumb']); ?>" alt="" loading="lazy">
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
                    <h1 class="sol-title"><?php echo e($product['product_name']); ?></h1>
                    <?php if ($product['barcode']): ?>
                        <p class="sol-desc"><?php echo te('ລະຫັດສິນຄ້າ: %s', $product['barcode']); ?></p>
                    <?php endif; ?>

                    <div class="sol-perks">
                        <span>✓ <?php echo !$inStock ? te('ສິນຄ້າໝົດ') : ((int)$product['cut_qty'] === 0 ? te('ພ້ອມຈັດສົ່ງ') : te('ມີສິນຄ້າ %d %s', (int)$product['qty'], $product['unit'] ?: t('ອັນ'))); ?></span>
                        <span>✓ <?php echo te('ຈັດສົ່ງທົ່ວປະເທດລາວ'); ?></span>
                        <span>✓ <?php echo te('ກວດສອບສະລິບ & ສົ່ງໄວ'); ?></span>
                    </div>

                    <?php $isMember = canSeePrices(); $pInfo = memberPriceInfo($priceVal, (float)($product['old_price'] ?? 0)); ?>
                    <div class="sol-contact-panel is-buy-panel">
                        <div class="sol-contact-head">
                            <div>
                                <span class="sol-contact-kicker"><?php echo te('ລາຄາສິນຄ້າ'); ?></span>
                                <span class="sol-member-pill"><?php echo $isMember ? te('ສິດສະມາຊິກ: ຮັບສ່ວນຫຼຸດພິເສດ 5% ຈາກລາຄາປົກກະຕິ') : te('ສະເພາະສະມາຊິກ — ກະລຸນາເຂົ້າສູ່ລະບົບເພື່ອເບິ່ງລາຄາ'); ?></span>
                            </div>
                            <?php if ($priceVal > 0): ?>
                                <strong class="sol-contact-price mo-price">
                                    <?php if ($isMember): ?>
                                        <?php echo e(money($pInfo['sell'])); ?>
                                        <?php if ($pInfo['old'] > $pInfo['sell']): ?><s><?php echo e(money($pInfo['old'])); ?></s><?php endif; ?>
                                    <?php else: ?>
                                        <span class="sol-price-locked">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -2px; margin-right: 4px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <?php echo te('ສະເພາະສະມາຊິກ'); ?>
                                        </span>
                                    <?php endif; ?>
                                </strong>
                            <?php else: ?>
                                <strong class="sol-contact-price"><?php echo te('ສອບຖາມລາຄາພິເສດ (ປຶກສາຟຣີ)'); ?></strong>
                            <?php endif; ?>
                        </div>
                        <div class="sol-buy-actions">
                            <?php if ($priceVal > 0): ?>
                                <?php if ($isMember): ?>
                                    <button type="button" class="sol-btn sol-btn-buy mo-buy-btn" data-order-modal-open>
                                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                                        <span><?php echo te('ສັ່ງຊື້ເລີຍ'); ?></span>
                                    </button>
                                <?php else: ?>
                                    <a class="sol-btn sol-btn-buy mo-buy-btn" href="#login" data-member-open="login" data-member-note="<?php echo te('ກະລຸນາເຂົ້າສູ່ລະບົບເພື່ອເບິ່ງລາຄາ ແລະ ສັ່ງຊື້'); ?>">
                                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <span><?php echo te('ເຂົ້າສູ່ລະບົບເພື່ອເບິ່ງລາຄາ'); ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a class="sol-btn is-chat" href="<?php echo e($chatHref); ?>">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                <span><?php echo te('ແຊັດສອບຖາມ'); ?></span>
                            </a>
                            <a class="sol-btn is-wa" href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.7-.1 1.4z"/></svg>
                                <span>WhatsApp</span>
                            </a>
                            <a class="sol-btn is-tel" href="<?php echo e($company['tel_href'] ?? 'tel:+8562029777302'); ?>">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span><?php echo e($company['tel'] ?? '020 29 777 302'); ?></span>
                            </a>
                            <a class="sol-btn is-fb" href="<?php echo e(fbChatUrl($defaultSubject)); ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M15.12 5.32H17V2.14A26.11 26.11 0 0 0 14.26 2c-2.72 0-4.58 1.66-4.58 4.7v2.62H6.61v3.56h3.07V22h3.68v-9.12h3.06l.46-3.56h-3.52V6.65c0-1.05.29-1.73 1.76-1.73z"/></svg>
                                <span>Facebook</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $orderItem = [
                'product_id'   => (int)$product['product_id'],
                'item_slug'    => null,
                'product_name' => $product['product_name'],
                'price'        => $member ? $pInfo['sell'] : 0,
                'old_price'    => $member ? $pInfo['old'] : 0,
                'max_qty'      => max(1, min(999, productMaxQty($product))),
                'unit'         => (string)$product['unit'],
                'img'          => $img,
            ];
            // ສະມາຊິກ = ຟອມສັ່ງຊື້ຈິງ; ລູກຄ້າທົ່ວໄປ = ກາດລົງທະບຽນ/ເຂົ້າສູ່ລະບົບ (ບໍ່ມີຟອມຂໍໃບສະເໜີລາຄາ); ສະມາຊິກ + ສິນຄ້າໝົດ = ກາດແຊັດ
            $ctaOutOfStock = $member && !$inStock;
            if ($member && $inStock): ?>
                <div class="po-trigger-card">
                    <div class="po-trigger-main">
                        <span class="po-trigger-badge">✓ <?php echo te('ລາຄາສະມາຊິກພິເສດ'); ?></span>
                        <h3 class="po-trigger-title"><?php echo te('ພ້ອມສັ່ງຊື້ %s ແລ້ວບໍ່?', $product['product_name']); ?></h3>
                        <p class="po-trigger-desc"><?php echo te('ກົດສັ່ງຊື້ເພື່ອເລືອກຈຳນວນ, ທີ່ຢູ່ຈັດສົ່ງ ແລະ ແນບສະລິບໂອນເງິນໃນລະບົບສະມາຊິກ'); ?></p>
                    </div>
                    <div class="po-trigger-side">
                        <button type="button" class="btn btn-primary btn-lg po-trigger-btn" data-order-modal-open>
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            <span><?php echo te('ກົດສັ່ງຊື້ສິນຄ້າ'); ?></span>
                        </button>
                    </div>
                </div>
            <?php endif;
            require dirname(__DIR__) . '/components/' . ($member && $inStock ? 'member_order_form.php' : 'member_cta.php');
            ?>

            <?php if ($related): ?>
                <div class="sol-related">
                    <div class="sol-related-head">
                        <h2><?php echo te('ສິນຄ້າໃນໝວດດຽວກັນ'); ?></h2>
                        <a href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ເບິ່ງທັງໝົດ →'); ?></a>
                    </div>
                    <div class="sol-related-grid is-product">
                        <?php foreach ($related as $rel):
                            $relImg = productImage($rel['img_url'] ?? null);
                        ?>
                            <a class="sol-rel-card" href="<?php echo url('pages/product.php?id=' . (int)$rel['product_id']); ?>">
                                <span class="sol-rel-thumb">
                                    <?php if ($relImg): ?>
                                        <img src="<?php echo e($relImg); ?>" alt="<?php echo e($rel['product_name']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <span>📦</span>
                                    <?php endif; ?>
                                </span>
                                <span class="sol-rel-body">
                                    <strong><?php echo e($rel['product_name']); ?></strong>
                                    <?php if ($isMember && (float)$rel['price'] > 0): ?>
                                        <small class="sol-rel-price"><?php echo e(money((float)$rel['price'])); ?></small>
                                    <?php elseif (!$isMember): ?>
                                        <small class="sol-rel-lock"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align: -1px; margin-right: 2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><?php echo te('ສະເພາະສະມາຊິກ'); ?></small>
                                    <?php else: ?>
                                        <small class="sol-rel-contact"><?php echo te('ຕິດຕໍ່ສອບຖາມລາຄາ'); ?></small>
                                    <?php endif; ?>
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
