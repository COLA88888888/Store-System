<?php
/**
 * ອຸປະກອນທັງໝົດ (ປຸ່ມ "ເບິ່ງທັງໝົດ" ໃນ pages/equipment.php) — ຄືໜ້າລາຍການຂອງ fastwork:
 *   ປຸ່ມໝວດ (GET cat) → ແຖບກັ່ນຕອງ: ຮຽງຕາມ (sort) + ສະວິດ "ມີພ້ອມສົ່ງ" (stock=1) → "ພົບ N ລາຍການ / ໜ້າ X ຈາກ Y"
 *   → ຕາຕະລາງບັດ 5 ຖັນ × 5 ແຖວ ຕໍ່ໜ້າ: 24 ບັດ (components/market_card.php) + ບັດໂຄສະນາສີເຫຼືອງ (ອັນທີ 5) → ແບ່ງໜ້າວົງມົນ (page).
 *   ປ່ຽນໜ້າ/ໝວດ = ໂຫຼດບໍ່ refresh ພ້ອມບັດ skeleton ([data-ajax-list] ໃນ assets/js/main.js); ບັດສະແດງ skeleton ຈົນຮູບໂຫຼດ.
 * ສິນຄ້າ = w_products. ບໍ່ມີສິນຄ້າເລີຍ → ອຸປະກອນສຳຮອງຈາກ hardwareFallback() (models/equipment.php), ປຸ່ມໝວດ = ແຕ່ລະອຸປະກອນ (GET hw).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ອຸປະກອນ POS');
$pageDescription = t('ເຄື່ອງ POS, ປິ້ນເຕີ, ເຄື່ອງສະແກນ, ລິ້ນຊັກເກັບເງິນ ແລະ ເຈ້ຍພິມບິນ — ຂາຍພ້ອມລະບົບ ຕິດຕັ້ງຟຣີ ແລະ ສອນໃຊ້ງານໃຫ້ຟຣີ');
$currentPage = 'equipment';
$logo = asset('img/logo.png');

$filter = [
    'q'        => trim((string)($_GET['q'] ?? '')),
    'category' => (int)($_GET['cat'] ?? 0),
    'sort'     => (string)($_GET['sort'] ?? 'new'),
    'in_stock' => !empty($_GET['stock']),
    'page'     => (int)($_GET['page'] ?? 1),
    'per_page' => 23, // 4 ຖັນ × 6 ແຖວ = 24 ບັດ (23 ສິນຄ້າ + 1 ບັດໂຄສະນາ) — ຕື່ມເຕັມ 6 ແຖວພໍດີ ບໍ່ໃຫ້ເຫຼືອກາດດ່ຽວ 1 ອັນຢູ່ແຖວລຸ່ມ (ຍ້າຍໄປໜ້າໃໝ່)
];
$sortOptions = ['new' => t('ໃໝ່ລ່າສຸດ'), 'name' => t('ຊື່ ກ → ຮ')];
// ຮັບສະເພາະ sort ທີ່ມີໃນເມນູ — ກັນ ?sort=price_asc ຈັດລຳດັບຕາມລາຄາ (ເວັບບໍ່ເປີດເຜີຍລາຄາ)
if (!isset($sortOptions[$filter['sort']])) $filter['sort'] = 'new';

$categories = $pdo ? getCategories($pdo, $shopStoreId) : [];
$useFallback = !$categories;
$chips = [];   // [label, query, active]
$cards = [];

if (!$useFallback) {
    $queryBase = array_filter(['sort' => $filter['sort'] !== 'new' ? $filter['sort'] : null, 'stock' => $filter['in_stock'] ? 1 : null]);
    $chips[] = [t('ທັງໝົດ'), $queryBase, $filter['category'] === 0];
    foreach ($categories as $c) {
        $chips[] = [$c['category_name'], array_merge($queryBase, ['cat' => (int)$c['category_id']]), $filter['category'] === (int)$c['category_id']];
    }
    $result = getProducts($pdo, $shopStoreId, $filter);
    $cards = array_map('productCard', $result['items']);
    $queryBase += array_filter(['cat' => $filter['category'] ?: null]);
} else {
    $hw = hardwareFallback();
    $pick = (string)($_GET['hw'] ?? '');
    $pick = isset($hw[$pick]) ? $pick : '';
    $chips[] = [t('ທັງໝົດ'), [], $pick === ''];
    foreach ($hw as $slug => $h) {
        $chips[] = [$h['label'], ['hw' => $slug], $pick === $slug];
        if ($pick === '' || $pick === $slug) {
            $cards[] = $h['card'];
        }
    }
    $pages = max(1, (int)ceil(count($cards) / $filter['per_page']));
    $page = min(max(1, $filter['page']), $pages);
    $result = ['total' => count($cards), 'pages' => $pages, 'page' => $page];
    $cards = array_slice($cards, ($page - 1) * $filter['per_page'], $filter['per_page']);
    $queryBase = array_filter(['hw' => $pick ?: null]);
}
$showPromo = true; // ທຸກໜ້າ: ອັນທີ 5 = ບັດໂຄສະນາ

require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="section eq-section eq-list" data-ajax-list>
    <?php /* ແຖບພື້ນຫຼັງເຕັມຈໍແບບຂັ້ນ ພ້ອມໜັງສື 3D Wlaodev.com ຢູ່ເຄິ່ງກາງ ແລະ ໂລໂກ້ WD ປະດັບ */ ?>
    <div class="eq-cover-band" aria-hidden="true">
        <span class="eq-cover-title-3d">Wlaodev.com</span>
        <img class="eq-cover-logo" src="<?php echo e(asset('img/wd_mark.png')); ?>" alt="" width="800" height="369">
    </div>

    <div class="container eq-container">
        <?php $listBanner = getBannerBySlug('ban'); /* ແບນເນີສີຟ້າ "ອຸປະກອນ POS ຄົບຊຸດ" = backend/assets/img/baner/ban.png (models/banners.php) */ ?>
        <?php if ($listBanner && $listBanner['slug'] === 'ban'): ?>
            <div class="eq-list-banner" style="--bg: url('<?php echo e($listBanner['url']); ?>')"><?php /* --bg = ພື້ນມົວສອງຂ້າງ (pages.css) */ ?>
                <span class="eq-lb-inner">
                    <img src="<?php echo e($listBanner['url']); ?>" width="<?php echo (int)$listBanner['width']; ?>" height="<?php echo (int)$listBanner['height']; ?>"
                         alt="<?php echo te('ອຸປະກອນ POS ຄົບຊຸດ ຄຸນນະພາບດີ — ເຄື່ອງ POS, ເຄື່ອງພິມບິນ, ເຄື່ອງສະແກນບາໂຄ້ດ'); ?>" fetchpriority="high" draggable="false">
                </span>
            </div>
        <?php endif; ?>
        <h1 class="eq-list-title"><?php echo e($pageTitle); ?></h1>

        <!-- ປຸ່ມໝວດແຖວດຽວ ເລື່ອນທາງຂ້າງ + ລູກສອນວົງມົນ ‹ › ([data-strip] ໃນ assets/js/main.js) -->
        <div class="eq-chips-wrap" data-strip>
            <nav class="eq-chips" aria-label="<?php echo te('ໝວດສິນຄ້າ'); ?>" data-strip-track>
                <?php foreach ($chips as [$label, $q, $active]): ?>
                    <a class="eq-chip<?php echo $active ? ' active' : ''; ?>" href="<?php echo $q ? '?' . e(http_build_query($q)) : '?'; ?>#eq-results" data-ajax><?php echo e($label); ?></a>
                <?php endforeach; ?>
            </nav>
            <button type="button" class="eq-chips-btn prev" data-strip-prev aria-label="<?php echo te('ກ່ອນໜ້າ'); ?>" hidden>
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button type="button" class="eq-chips-btn next" data-strip-next aria-label="<?php echo te('ຖັດໄປ'); ?>" hidden>
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>

        <?php if (!$useFallback): ?>
            <form class="eq-toolbar" method="get">
                <?php if ($filter['category']): ?><input type="hidden" name="cat" value="<?php echo (int)$filter['category']; ?>"><?php endif; ?>
                <label class="eq-sort">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M17 20V4M17 4l-3 3M17 4l3 3"/></svg>
                    <select name="sort" data-auto-submit aria-label="<?php echo te('ຮຽງຕາມ'); ?>">
                        <?php foreach ($sortOptions as $k => $label): ?>
                            <option value="<?php echo e($k); ?>"<?php echo $filter['sort'] === $k ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="eq-switch">
                    <span><?php echo te('ມີພ້ອມສົ່ງ'); ?></span>
                    <input type="checkbox" name="stock" value="1"<?php echo $filter['in_stock'] ? ' checked' : ''; ?> data-auto-submit>
                    <span class="eq-switch-ui" aria-hidden="true"></span>
                </label>
            </form>
        <?php endif; ?>

        <div class="eq-results" id="eq-results" data-ajax-results>
        <div class="eq-count">
            <span><?php echo te('ພົບ %d ລາຍການ', (int)$result['total']); ?></span>
            <span><?php echo te('ໜ້າ %d ຈາກ %d', (int)$result['page'], (int)$result['pages']); ?></span>
        </div>

        <?php if ($cards || $showPromo): ?>
            <div class="eq-grid">
                <?php 
                $promoRendered = false;
                foreach ($cards as $i => $card): 
                    if ($showPromo && $i === 3): 
                        require dirname(__DIR__) . '/components/equipment_promo.php'; 
                        $promoRendered = true;
                    endif; 
                    require dirname(__DIR__) . '/components/market_card.php'; 
                endforeach; 
                if ($showPromo && !$promoRendered): 
                    require dirname(__DIR__) . '/components/equipment_promo.php'; 
                endif; 
                ?>
            </div>
        <?php endif; ?>
        <?php if (!$cards): ?>
            <p class="empty"><?php echo te('ບໍ່ພົບສິນຄ້າ'); ?></p>
        <?php endif; ?>

        <?php require dirname(__DIR__) . '/components/pagination.php'; ?>
        </div>
    </div>
    <template id="mkSkeleton"><div class="mk-card mk-card-skel is-loading"><?php require dirname(__DIR__) . '/components/market_skeleton.php'; ?></div></template>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
