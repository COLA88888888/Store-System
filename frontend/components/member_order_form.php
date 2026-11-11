<?php
/**
 * ຟອມສັ່ງຊື້ຂອງສະມາຊິກ (ໜ້າສິນຄ້າ pages/product.php) — ແບບ Modal Popup:
 *   ລາຄາ + ຈຳນວນ + ຍອດລວມສົດ → ທີ່ຢູ່/ຂົນສົ່ງ/ສາຂາ (ຕື່ມຈາກບັນຊີ) → ທະນາຄານ + ສະລິບ → ຢືນຢັນ → pages/my_order.php
 * ເບີໂທ = ເບີທີ່ລົງທະບຽນ (ປ່ຽນໃນໜ້າບັນຊີ). ບັນຊີ Google ທີ່ຍັງບໍ່ມີເບີ → ໃຫ້ໄປເພີ່ມເບີກ່ອນ.
 * ຕົວແປຈາກໜ້າ: $member, $orderItem (product_id, product_name, price, old_price, img, max_qty, unit), $orderForm, $orderErrors, $banks
 * POST ກັບມາໜ້າເດີມ (member_order=1) — ກົດການຢູ່ pages/product.php → memberValidateOrder() + createDirectProductOrder()
 */
$fieldErr = function (string $name) use ($orderErrors) {
    return isset($orderErrors[$name]) ? '<small class="field-error">' . e($orderErrors[$name]) . '</small>' : '';
};
$unit = (float)$orderItem['price'];
$oldUnit = (float)($orderItem['old_price'] ?? 0);
$curQty = max(1, (int)($orderForm['qty'] ?? 1));
$af = $orderForm;
$afErr = $fieldErr;
$afRequireCourier = true;
$bankSel = (int)($orderForm['bank_account_id'] ?? 0);
$slipRequired = false;
$slipAmount = $unit * $curQty;
?>
<div class="po-modal" id="productOrderModal" data-order-modal<?php echo empty($orderErrors) ? ' hidden' : ''; ?> data-auto-open="<?php echo !empty($orderErrors) ? '1' : '0'; ?>">
    <div class="po-modal-backdrop" data-order-modal-close></div>
    <div class="po-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="productOrderTitle">
        <!-- ສ່ວນຫົວ Modal ຢູ່ຄົງທີ່ດ້ານເທິງ ບໍ່ບັງໜັງສື -->
        <div class="po-modal-header">
            <div class="po-head-info">
                <span class="po-kicker">✓ <?php echo te('ສະມາຊິກເລກທີ %s — ລາຄາສະມາຊິກ', memberCode($member['member_no'])); ?></span>
                <h2 class="po-title" id="productOrderTitle"><?php echo te('ສັ່ງຊື້ສິນຄ້າ'); ?></h2>
                <p class="po-sub"><?php echo te('ເລືອກຈຳນວນ, ກວດທີ່ຢູ່ຈັດສົ່ງ ແລະ ແນບສະລິບການໂອນ — ຕິດຕາມສະຖານະເຄື່ອງໄດ້ໃນໜ້າ "ອໍເດີຂອງຂ້ອຍ"'); ?></p>
            </div>
            <button type="button" class="po-modal-close" data-order-modal-close aria-label="<?php echo te('ປິດ'); ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="po-modal-body mo-card" id="productOrderSection">
            <?php if (memberNeedsPhone($member)): ?>
                <div class="alert alert-error"><?php echo te('ກະລຸນາເພີ່ມເບີໂທຈິງໃນບັນຊີກ່ອນສັ່ງຊື້ (ທີມງານຕ້ອງໂທຢືນຢັນອໍເດີ)'); ?>
                    <a class="btn btn-primary btn-sm" href="<?php echo url('pages/account.php?edit=1'); ?>"><?php echo te('ເພີ່ມເບີໂທ'); ?></a></div>
            <?php elseif ($unit <= 0): ?>
                <div class="alert alert-info"><?php echo te('ສິນຄ້ານີ້ຍັງບໍ່ໄດ້ຕັ້ງລາຄາ — ກະລຸນາແຊັດ ຫຼື ໂທສອບຖາມ'); ?></div>
            <?php else: ?>
                <?php if (!empty($orderErrors['form'])): ?><div class="alert alert-error"><?php echo e($orderErrors['form']); ?></div><?php endif; ?>
                <?php if ($orderErrors && empty($orderErrors['form'])): ?><div class="alert alert-error"><?php echo te('ກະລຸນາກວດຂໍ້ມູນທີ່ໝາຍສີແດງ'); ?></div><?php endif; ?>

                <form class="po-form" method="post" enctype="multipart/form-data" novalidate data-order-form
                      data-unit-price="<?php echo e((string)$unit); ?>" data-old-unit-price="<?php echo e((string)$oldUnit); ?>">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="member_order" value="1">

                    <div class="po-item-bar mb-3">
                        <div class="po-item-main">
                            <?php if (!empty($orderItem['img'])): ?>
                                <a class="po-item-thumb-wrap" href="<?php echo e($orderItem['img']); ?>" target="_blank" data-po-img-view title="<?php echo te('ກົດເພື່ອເບິ່ງຮູບໃຫຍ່'); ?>">
                                    <img class="po-item-thumb" src="<?php echo e($orderItem['img']); ?>" alt="<?php echo e($orderItem['product_name']); ?>">
                                    <span class="po-thumb-zoom" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                    </span>
                                </a>
                            <?php endif; ?>
                            <div>
                                <strong class="po-item-name"><?php echo e($orderItem['product_name']); ?></strong>
                                <div class="mo-unit"><b><?php echo e(money($unit)); ?></b><?php if ($oldUnit > $unit): ?> <s><?php echo e(money($oldUnit)); ?></s> <span class="mo-off">-<?php echo (int)round(100 - $unit / $oldUnit * 100); ?>%</span><?php endif; ?> / <?php echo e($orderItem['unit'] ?: t('ອັນ')); ?></div>
                            </div>
                        </div>
                        <div class="po-item-controls">
                            <div class="po-qty-wrap">
                                <span class="po-qty-label"><?php echo te('ຈຳນວນ'); ?></span>
                                <div class="po-qty-box">
                                    <button type="button" class="po-qty-btn" data-qty-step="-1" aria-label="-">−</button>
                                    <input type="number" class="po-qty-input" id="moQty" name="qty" value="<?php echo $curQty; ?>" min="1" max="<?php echo (int)$orderItem['max_qty']; ?>" data-qty-input>
                                    <button type="button" class="po-qty-btn" data-qty-step="1" aria-label="+">+</button>
                                </div>
                            </div>
                            <div class="po-total-wrap">
                                <span class="po-total-label"><?php echo te('ລວມ'); ?></span>
                                <?php if ($oldUnit > $unit): ?><span class="po-total-old-line"><s data-order-old-total><?php echo e(money($oldUnit * $curQty)); ?></s></span><?php endif; ?>
                                <b class="po-total-val" data-order-total><?php echo e(money($unit * $curQty)); ?></b>
                            </div>
                        </div>
                    </div>
                    <?php echo $fieldErr('qty'); ?>

                    <h3 class="mf-h"><span>1</span><?php echo te('ຜູ້ຮັບເຄື່ອງ'); ?></h3>
                    <div class="mf-grid-2">
                        <label class="mf-field">
                            <span><?php echo te('ຊື່ຜູ້ຮັບ *'); ?></span>
                            <input class="input" name="name" value="<?php echo e($orderForm['name']); ?>" required maxlength="150">
                            <?php echo $fieldErr('name'); ?>
                        </label>
                        <label class="mf-field">
                            <span><?php echo te('ເບີໂທ (ເບີທີ່ລົງທະບຽນ)'); ?></span>
                            <input class="input" value="<?php echo e(laoPhoneFormat($member['tel'])); ?>" readonly>
                            <small class="mf-hint"><a href="<?php echo url('pages/account.php?edit=1'); ?>"><?php echo te('ປ່ຽນເບີໂທໃນບັນຊີ'); ?></a></small>
                        </label>
                    </div>

                    <h3 class="mf-h"><span>2</span><?php echo te('ທີ່ຢູ່ຈັດສົ່ງ ແລະ ສາຂາຂົນສົ່ງ'); ?></h3>
                    <?php require __DIR__ . '/member_address_fields.php'; ?>

                    <h3 class="mf-h"><span>3</span><?php echo te('ຊຳລະເງິນ (ໂອນ) ແລະ ແນບສະລິບ'); ?></h3>
                    <?php require __DIR__ . '/bank_slip_fields.php'; ?>

                    <label class="mf-field">
                        <span><?php echo te('ໝາຍເຫດເຖິງທີມງານ'); ?></span>
                        <textarea class="input" name="note" rows="2" maxlength="500" placeholder="<?php echo te('ເຊັ່ນ: ຕ້ອງການໃບບິນ, ໂທກ່ອນສົ່ງ...'); ?>"><?php echo e($orderForm['note']); ?></textarea>
                    </label>

                    <div class="po-submit-bar">
                        <p class="po-submit-note"><?php echo te('ທີມງານກວດສະລິບ ແລະ ຢືນຢັນອໍເດີ ແລ້ວແຈ້ງເລກໃບຝາກເຄື່ອງໃນໜ້າ "ອໍເດີຂອງຂ້ອຍ"'); ?></p>
                        <div class="mo-submit-btns">
                            <?php if (!empty($orderItem['product_id'])): /* ໃສ່ກະຕ່າ (assets/js/cart.js) — ສັ່ງຫຼາຍລາຍການພ້ອມກັນໃນ pages/checkout.php */ ?>
                                <button type="button" class="btn btn-outline" data-add-to-cart="<?php echo (int)$orderItem['product_id']; ?>" data-qty-from="#moQty"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2L21 7H6"/></svg> <span><?php echo te('ໃສ່ກະຕ່າ'); ?></span></button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary po-submit-btn"><?php echo te('ຢືນຢັນການສັ່ງຊື້'); ?> →</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <!-- Lightbox ເບິ່ງຮູບສິນຄ້າໃຫຍ່ -->
        <?php if (!empty($orderItem['img'])): ?>
        <div class="po-img-modal" id="poImgModal" hidden aria-hidden="true">
            <div class="po-img-backdrop" data-po-img-close></div>
            <div class="po-img-box">
                <button type="button" class="po-img-close" data-po-img-close aria-label="<?php echo te('ປິດ'); ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
                <img src="<?php echo e($orderItem['img']); ?>" alt="<?php echo e($orderItem['product_name']); ?>">
                <span class="po-img-caption"><?php echo e($orderItem['product_name']); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
