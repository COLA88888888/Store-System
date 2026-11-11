<?php
/**
 * ກ່ອງ "ລະບົບຕາມປະເພດທຸລະກິດ" ຖັດຈາກແບນເນີໃນໜ້າຫຼັກ — ຂໍ້ມູນຈາກ $siteSolutions (includes/content.php).
 * ແຖບໄອຄອນດ້ານເທິງ (ເລື່ອນທາງຂ້າງໄດ້) ສະຫຼັບກຸ່ມກ່ອງ ([data-tabs] ໃນ assets/js/main.js).
 * ກົດກ່ອງ → ໜ້າຕິດຕໍ່ ພ້ອມຫົວຂໍ້ທີ່ຕື່ມໃຫ້ແລ້ວ. ຮູບຂອງກ່ອງ: solutionImage() ໃນ includes/helpers.php.
 */
if (empty($siteSolutions)) {
    return;
}
// ແຖບ "ອຸປະກອນ POS" (hardware) ຂຶ້ນກ່ອນ ແລະ ເປີດຢູ່ຕອນໂຫຼດໜ້າ — ແຖບອື່ນຕາມລຳດັບເດີມ
$solTabs = isset($siteSolutions['hardware']) ? ['hardware' => $siteSolutions['hardware']] + $siteSolutions : $siteSolutions;
$firstTab = array_key_first($solTabs);
?>
<section class="section solutions-section" id="solutions">
    <div class="container">
        <div class="solutions-card" data-tabs>
            <div class="solutions-tabs-wrap">
                <div class="solutions-tabs-inner">
                <div class="solutions-tabs" role="tablist" data-tabs-strip>
                    <?php foreach ($solTabs as $key => $tab): ?>
                        <button class="solutions-tab<?php echo $key === $firstTab ? ' active' : ''; ?>" type="button" role="tab"
                                id="sol-tab-<?php echo e($key); ?>" aria-controls="sol-panel-<?php echo e($key); ?>"
                                aria-selected="<?php echo $key === $firstTab ? 'true' : 'false'; ?>" data-tab="<?php echo e($key); ?>">
                            <span class="solutions-tab-icon" aria-hidden="true"><?php echo icon($key === 'hardware' ? 'monitor' : 'receipt', 38, 1.7); /* ໄອຄອນເສັ້ນ ແທນ emoji */ ?></span>
                            <span class="solutions-tab-label"><?php echo e($tab['label']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button class="solutions-scroll" type="button" aria-label="<?php echo te('ຖັດໄປ'); ?>" data-tabs-next hidden>›</button>
                </div>
                <?php /* ປຸ່ມ "ເບິ່ງທັງໝົດ" ຢູ່ແຖວດຽວກັບແຖບ (ຂວາມື) — ສະແດງສະເພາະປຸ່ມຂອງແຖບທີ່ເປີດຢູ່ (CSS :has ໃນ pages.css) */
                $solMore = ['hardware' => ['pages/equipment_list.php', t('ເບິ່ງອຸປະກອນທັງໝົດ')], 'pos' => ['pages/pos_systems.php', t('ເບິ່ງລະບົບທັງໝົດ')]]; ?>
                <div class="solutions-more">
                    <?php foreach ($solMore as $mKey => [$mHref, $mText]): if (!isset($solTabs[$mKey])) continue; ?>
                        <a class="solutions-more-btn" data-more-for="<?php echo e($mKey); ?>" href="<?php echo url($mHref); ?>"><?php echo e($mText); ?> <span aria-hidden="true">→</span></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($solTabs as $key => $tab): ?>
                <?php
                $isProduct = ($tab['style'] ?? '') === 'product';
                // ແຖບອຸປະກອນ: ສະແດງແຕ່ 5 ກ່ອງ + ປຸ່ມ "ເບິ່ງທັງໝົດ" → pages/equipment_list.php
                $tabItems = $isProduct ? array_slice($tab['items'], 0, 5) : $tab['items'];
                ?>
                <div class="solutions-grid<?php echo $isProduct ? ' is-product' : ''; ?>" role="tabpanel" id="sol-panel-<?php echo e($key); ?>" aria-labelledby="sol-tab-<?php echo e($key); ?>"
                     data-panel="<?php echo e($key); ?>" style="--cols: <?php echo count($tabItems) % 5 === 0 ? 5 : 4; ?>"<?php echo $key === $firstTab ? '' : ' hidden'; ?>>
                    <?php foreach ($tabItems as $item):
                        $img = solutionImage($item['slug']);
                        $solutionUrl = url('pages/solution.php?slug=' . rawurlencode($item['slug']));
                    ?>
                        <a class="solution-tile skel is-loading" href="<?php echo e($solutionUrl); ?>">
                            <span class="solution-media">
                                <?php if ($img): ?>
                                    <img src="<?php echo e($img); ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="solution-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="solution-title"><?php echo e($isProduct ? $item['title'] : $tab['label'] . ' ' . $item['title']); ?></span>
                            <?php if ($isProduct): ?><span class="solution-sub"><?php echo te('ສອບຖາມລາຄາ'); ?> →</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal ສະແດງລະບົບໃນຈໍຄອມ (Computer Monitor Mockup Modal) -->
    <div class="pos-demo-modal" id="posDemoModal" hidden aria-hidden="true" role="dialog" aria-modal="true">
        <div class="pos-demo-backdrop" data-demo-close></div>
        <div class="pos-demo-container">
            <div class="pos-demo-header">
                <div class="pos-demo-title-wrap">
                    <span class="pos-demo-tag"><?php echo te('ຕົວຢ່າງລະບົບຈິງ'); ?></span>
                    <h3 class="pos-demo-title" id="posDemoTitle"><?php echo te('ລະບົບ POS ຮ້ານເຄື່ອງດື່ມ ແລະ ຄາເຟ'); ?></h3>
                </div>
                <button type="button" class="pos-demo-close" data-demo-close aria-label="<?php echo te('ປິດ'); ?>">✕</button>
            </div>

            <div class="pos-demo-body">
                <!-- ຈໍຄອມພິວເຕີ (Computer Monitor Mockup) -->
                <div class="monitor-mockup">
                    <div class="monitor-screen">
                        <div class="monitor-camera" aria-hidden="true"></div>
                        <div class="monitor-display">
                            <img id="posDemoImage" src="<?php echo asset('img/demo/pos_drinks.png'); ?>" alt="<?php echo te('ລະບົບ POS'); ?>" draggable="false">
                        </div>
                    </div>
                    <div class="monitor-stand" aria-hidden="true"></div>
                    <div class="monitor-base" aria-hidden="true"></div>
                </div>
            </div>

            <div class="pos-demo-footer">
                <p class="pos-demo-hint" id="posDemoHint"><?php echo te('ຈໍຂາຍໜ້າຮ້ານສຳຜັດງ່າຍ, ຈັດການອໍເດີ, ຕັດສະຕັອກ ແລະ ພິມບິນຄົບວົງຈອນ'); ?></p>
                <div class="pos-demo-actions">
                    <button type="button" class="btn btn-outline" data-demo-close><?php echo te('ປິດ'); ?></button>
                    <a class="btn btn-primary" id="posDemoContactBtn" href="<?php echo url('pages/contact.php?subject=' . rawurlencode(t('ສົນໃຈລະບົບ ຮ້ານເຄື່ອງດື່ມ ແລະ ຄາເຟ'))); ?>">
                        <?php echo te('ປຶກສາ / ສັ່ງຊື້ລະບົບນີ້'); ?> →
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
