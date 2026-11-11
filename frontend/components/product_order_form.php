<?php
/**
 * ຟອມຂໍໃບສະເໜີລາຄາ / ໃຫ້ທີມງານຕິດຕໍ່ກັບ (ບໍ່ມີລາຄາໜ້າເວັບ → ບໍ່ບັງຄັບລູກຄ້າກອກທີ່ຢູ່ຍາວໆກ່ອນຮູ້ລາຄາ).
 * ດ້ານເທິງ: ສະແດງ 3 ຂັ້ນຕອນທີ່ຊັດເຈນ ໃຫ້ລູກຄ້າໝັ້ນໃຈວ່າ ຍັງບໍ່ຕ້ອງໂອນເງິນຈົນກວ່າຈະຮູ້ລາຄາ.
 * ຟອມກອກງ່າຍພຽງ 3 ຊ່ອງ: ຊື່, ເບີໂທ/WhatsApp, ຈຳນວນ (+ ປຸ່ມກົດເປີດລະບຸແຂວງ/ຂົນສົ່ງລ່ວງໜ້າ ຖ້າລູກຄ້າຕ້ອງການ).
 */
$fieldErr = function (string $name) use ($orderErrors) {
    return isset($orderErrors[$name]) ? '<small class="po-err">' . e($orderErrors[$name]) . '</small>' : '';
};
$isMember = currentMember($pdo) !== null;
$curQty = max(1, (int)($orderForm['qty'] ?? 1));
$provDistricts = getLaoProvincesAndDistricts();
$curProvDistricts = $provDistricts[$orderForm['province']] ?? [];
$hasShippingFilled = ($orderForm['province'] !== '' || $orderForm['village'] !== '' || $orderForm['courier_branch'] !== '');
?>
<div class="po-card" id="productOrderSection">
    <div class="po-head">
        <div>
            <span class="po-kicker"><?php echo te('ຂໍລາຄາພິເສດ & ປຶກສາຟຣີ (ຍັງບໍ່ຕ້ອງໂອນເງິນ)'); ?></span>
            <h2 class="po-title"><?php echo te('ຝາກເບີໃຫ້ທີມງານແຈ້ງລາຄາ ຫຼື ຈອງສິນຄ້າໄວ້ກ່ອນ'); ?></h2>
            <p class="po-sub"><?php echo te('ບໍ່ຕ້ອງກັງວົນເລື່ອງລາຄາ — ພຽງແຕ່ຝາກຊື່ ແລະ ເບີ WhatsApp ໄວ້ ທີມງານຈະສົ່ງລາຄາພິເສດພ້ອມຮູບສິນຄ້າຕົວຈິງໃຫ້ເບິ່ງກ່ອນຕັດສິນໃຈຊື້.'); ?></p>
        </div>
    </div>

    <!-- 3 ຂັ້ນຕອນງ່າຍໆ ໃຫ້ລູກຄ້າໝັ້ນໃຈກ່ອນສັ່ງຊື້ -->
    <div class="po-trust-steps">
        <div class="po-trust-step">
            <span class="po-trust-num">1</span>
            <div>
                <strong><?php echo te('ຝາກເບີ ຫຼື ທັກແຊັດຖາມລາຄາ'); ?></strong>
                <span><?php echo te('ເລືອກຈຳນວນທີ່ຕ້ອງການ ແລ້ວຝາກເບີ WhatsApp ໄວ້'); ?></span>
            </div>
        </div>
        <div class="po-trust-step">
            <span class="po-trust-num">2</span>
            <div>
                <strong><?php echo te('ທີມງານແຈ້ງລາຄາພິເສດໃຫ້ກ່ອນ'); ?></strong>
                <span><?php echo te('ຮັບໃບສະເໜີລາຄາ + ຄ່າຝາກຂົນສົ່ງ ພ້ອມສ່ວນຫຼຸດສະມາຊິກ 5%'); ?></span>
            </div>
        </div>
        <div class="po-trust-step">
            <span class="po-trust-num">3</span>
            <div>
                <strong><?php echo te('ພໍໃຈລາຄາແລ້ວຈຶ່ງໂອນ & ຈັດສົ່ງທັນທີ'); ?></strong>
                <span><?php echo te('ກວດສອບສິນຄ້າ ພ້ອມຈັດຝາກຂົນສົ່ງທົ່ວປະເທດລາວ'); ?></span>
            </div>
        </div>
    </div>

    <?php if (!empty($orderErrors['form'])): ?>
        <div class="alert alert-error mb-3"><?php echo e($orderErrors['form']); ?></div>
    <?php endif; ?>

    <form class="po-form" method="post" novalidate data-order-form>
        <?php echo csrfField(); ?>
        <input type="hidden" name="order_submit" value="1">
        <input type="hidden" name="quick_inquiry" value="1">

        <!-- 1. ຂໍ້ມູນສິນຄ້າ + ຊື່ ແລະ ເບີໂທຕິດຕໍ່ກັບ -->
        <div class="po-section" data-prov-map="<?php echo e(json_encode($provDistricts, JSON_UNESCAPED_UNICODE)); ?>">
            <div class="po-item-bar mb-3">
                <div class="po-item-main">
                    <?php if (!empty($orderItem['img'])): ?>
                        <span data-lightbox><a class="po-item-thumb-link" href="<?php echo e($orderItem['img']); ?>" data-lightbox-item><img class="po-item-thumb" src="<?php echo e($orderItem['img']); ?>" alt="<?php echo e($orderItem['product_name']); ?>"></a></span>
                    <?php endif; ?>
                    <div>
                        <strong class="po-item-name"><?php echo e($orderItem['product_name']); ?></strong>
                        <div class="po-member-note">
                            <?php if ($isMember): ?>
                                ✓ <?php echo te('ສິດທິສະມາຊິກ: ໄດ້ຮັບສ່ວນຫຼຸດພິເສດ 5% ຈາກລາຄາປົກກະຕິ (ຄິດລາຄາຂາຍຕົວຈິງຕອນແຈ້ງຍອດ)'); ?>
                            <?php else: ?>
                                <?php echo te('ລົງທະບຽນເພື່ອເບິ່ງລາຄາ ແລະ ສັ່ງຊື້ອອນລາຍ'); ?> — <a href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນຟຣີ'); ?></a> · <a href="<?php echo url('pages/member_login.php?next=' . rawurlencode('pages/product.php?id=' . (int)$orderItem['product_id'])); ?>"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="po-item-controls">
                    <div class="po-qty-wrap">
                        <span class="po-qty-label"><?php echo te('ຈຳນວນທີ່ສົນໃຈ'); ?></span>
                        <div class="po-qty-box">
                            <button type="button" class="po-qty-btn" data-qty-step="-1" aria-label="-">−</button>
                            <input type="number" class="po-qty-input" name="qty" id="poQtyInput" value="<?php echo $curQty; ?>" min="1" max="999" data-qty-input>
                            <button type="button" class="po-qty-btn" data-qty-step="1" aria-label="+">+</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="po-grid-2">
                <label class="po-field">
                    <span><?php echo te('ຊື່ຂອງທ່ານ / ຊື່ຮ້ານ *'); ?></span>
                    <input class="input" type="text" name="name" value="<?php echo e($orderForm['name']); ?>" placeholder="<?php echo te('ເຊັ່ນ: ທ້າວ ສົມຊາຍ / ຮ້ານມິນິມາດ...'); ?>" required maxlength="150">
                    <?php echo $fieldErr('name'); ?>
                </label>
                <label class="po-field">
                    <span><?php echo te('ເບີໂທຕິດຕໍ່ / WhatsApp (ເພື່ອຮັບແຈ້ງລາຄາ) *'); ?></span>
                    <input class="input" type="tel" name="tel" value="<?php echo e($orderForm['tel']); ?>" placeholder="020 xxxx xxxx" required maxlength="20">
                    <?php echo $fieldErr('tel'); ?>
                </label>
            </div>

            <label class="po-field mt-2">
                <span><?php echo te('ຂໍ້ຄວາມເພີ່ມເຕີມ / ສິ່ງທີ່ຕ້ອງການຖາມ (ບໍ່ບັງຄັບ)'); ?></span>
                <input class="input" type="text" name="note" value="<?php echo e($orderForm['note']); ?>" placeholder="<?php echo te('ເຊັ່ນ: ຂໍລາຄາສົ່ງ, ໃຊ້ກັບຄອມ ຫຼື ມືຖື, ຕ້ອງການໃຫ້ຈັດສົ່ງຕ່າງແຂວງ...'); ?>">
            </label>

            <!-- ທາງເລືອກເສີມ: ລະບຸທີ່ຢູ່ຈັດສົ່ງ ແລະ ຂົນສົ່ງໄວ້ລ່ວງໜ້າ (ບໍ່ບັງຄັບ) -->
            <details class="po-optional-details"<?php echo $hasShippingFilled ? ' open' : ''; ?>>
                <summary class="po-optional-summary">
                    <span>+ <?php echo te('ລະບຸທີ່ຢູ່ຈັດສົ່ງ ແລະ ບໍລິສັດຂົນສົ່ງໄວ້ລ່ວງໜ້າ (ບໍ່ບັງຄັບ — ແຈ້ງຕອນຕົກລົງລາຄາແລ້ວກໍໄດ້)'); ?></span>
                </summary>
                <div class="po-optional-body">
                    <div class="po-grid-3">
                        <label class="po-field">
                            <span><?php echo te('ແຂວງ / ນະຄອນຫຼວງ'); ?></span>
                            <select class="input" name="province" data-province-select>
                                <option value=""><?php echo te('-- ເລືອກແຂວງ --'); ?></option>
                                <?php foreach ($provDistricts as $prov => $_dists): ?>
                                    <option value="<?php echo e($prov); ?>"<?php echo $orderForm['province'] === $prov ? ' selected' : ''; ?>><?php echo e($prov); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="po-field">
                            <span><?php echo te('ເມືອງ'); ?></span>
                            <select class="input" name="district" data-district-select data-placeholder="<?php echo te('-- ເລືອກເມືອງ --'); ?>" data-empty-placeholder="<?php echo te('-- ເລືອກແຂວງກ່ອນ --'); ?>">
                                <?php if ($curProvDistricts): ?>
                                    <option value=""><?php echo te('-- ເລືອກເມືອງ --'); ?></option>
                                    <?php foreach ($curProvDistricts as $dist): ?>
                                        <option value="<?php echo e($dist); ?>"<?php echo $orderForm['district'] === $dist ? ' selected' : ''; ?>><?php echo e($dist); ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value=""><?php echo te('-- ເລືອກແຂວງກ່ອນ --'); ?></option>
                                <?php endif; ?>
                            </select>
                        </label>
                        <label class="po-field">
                            <span><?php echo te('ບ້ານ'); ?></span>
                            <input class="input" type="text" name="village" value="<?php echo e($orderForm['village']); ?>" placeholder="<?php echo te('ປ້ອນຊື່ບ້ານ...'); ?>" maxlength="150">
                        </label>
                    </div>

                    <div class="po-courier-wrap mt-2">
                        <div class="po-courier-col-left">
                            <span class="po-field-lbl"><?php echo te('ບໍລິສັດຂົນສົ່ງທີ່ສະດວກ'); ?></span>
                            <div class="po-couriers">
                                <?php foreach ($couriers as $cCode => $cInfo):
                                    $checked = ($orderForm['courier_code'] === $cCode);
                                ?>
                                    <label class="po-courier-card<?php echo $checked ? ' is-selected' : ''; ?>" title="<?php echo e($cInfo['name']); ?>">
                                        <input type="radio" name="courier_code" value="<?php echo e($cCode); ?>"<?php echo $checked ? ' checked' : ''; ?>>
                                        <span class="po-courier-avatar">
                                            <img class="po-courier-logo" src="<?php echo e($cInfo['logo']); ?>" alt="<?php echo e($cInfo['short']); ?>">
                                            <span class="po-tick" aria-hidden="true">✓</span>
                                        </span>
                                        <span class="po-courier-name"><?php echo e($cInfo['short']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="po-courier-col-right">
                            <label class="po-field">
                                <span><?php echo te('ສາຂາຂົນສົ່ງປາຍທາງ / ບ່ອນຮັບເຄື່ອງ'); ?></span>
                                <input class="input" type="text" name="courier_branch" value="<?php echo e($orderForm['courier_branch']); ?>" placeholder="<?php echo te('ເຊັ່ນ: ສາຂາ ດົງໂດກ, ສາຂາ ປາກເຊ...'); ?>">
                            </label>
                            <label class="po-field mt-2">
                                <span><?php echo te('ລາຍລະອຽດທີ່ຢູ່ເພີ່ມເຕີມ'); ?></span>
                                <input class="input" type="text" name="address" value="<?php echo e($orderForm['address']); ?>" placeholder="<?php echo te('ເຊັ່ນ: ຮ່ອມ, ຈຸດສັງເກດ...'); ?>">
                            </label>
                        </div>
                    </div>
                </div>
            </details>
        </div>

        <div class="po-submit-bar">
            <p class="po-submit-note">
                <?php echo te('ຍັງບໍ່ມີການເກັບເງິນໃນຂັ້ນຕອນນີ້ — ທີມງານຈະຕິດຕໍ່ແຈ້ງລາຄາພິເສດໃຫ້ທ່ານທາງ WhatsApp / ໂທລະສັບ ທັນທີ.'); ?>
            </p>
            <button type="submit" class="btn btn-primary po-submit-btn">
                <?php echo te('ຂໍໃບສະເໜີລາຄາ / ໃຫ້ທີມງານຕິດຕໍ່ກັບ'); ?> →
            </button>
        </div>
    </form>
</div>
