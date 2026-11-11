<?php
/**
 * ຊ່ອງທີ່ຢູ່ຈັດສົ່ງ + ບໍລິສັດຂົນສົ່ງ/ສາຂາປາຍທາງ — ໃຊ້ຮ່ວມ: pages/account.php (ແກ້ໄຂຂໍ້ມູນ),
 * components/member_order_form.php ແລະ pages/checkout.php (ສະມາຊິກ).
 * ຕ້ອງຢູ່ໃນ <form data-order-form> (assets/js/main.js: ເລືອກແຂວງ → ລາຍຊື່ເມືອງ, ຕິກເລືອກຂົນສົ່ງ).
 * ຕົວແປທີ່ຕ້ອງມີ: $af (ຄ່າ: province, district, village, address, courier_code, courier_branch),
 *                $afErr (fn(string $field): string — HTML ຂໍ້ຜິດພາດ), $afRequireCourier (bool — ບັງຄັບສາຂາຂົນສົ່ງ)
 */
$afProv = getLaoProvincesAndDistricts();
$afDists = $afProv[$af['province'] ?? ''] ?? [];
$afReq = !empty($afRequireCourier) ? ' *' : '';
?>
<div class="mf-address" data-prov-map="<?php echo e(json_encode($afProv, JSON_UNESCAPED_UNICODE)); ?>">
    <div class="mf-grid-3">
        <label class="mf-field">
            <span><?php echo te('ແຂວງ / ນະຄອນຫຼວງ *'); ?></span>
            <select class="input" name="province" data-province-select required>
                <option value=""><?php echo te('-- ເລືອກແຂວງ --'); ?></option>
                <?php foreach ($afProv as $prov => $_d): ?>
                    <option value="<?php echo e($prov); ?>"<?php echo ($af['province'] ?? '') === $prov ? ' selected' : ''; ?>><?php echo e($prov); ?></option>
                <?php endforeach; ?>
            </select>
            <?php echo $afErr('province'); ?>
        </label>
        <label class="mf-field">
            <span><?php echo te('ເມືອງ *'); ?></span>
            <select class="input" name="district" data-district-select required data-placeholder="<?php echo te('-- ເລືອກເມືອງ --'); ?>" data-empty-placeholder="<?php echo te('-- ເລືອກແຂວງກ່ອນ --'); ?>">
                <option value=""><?php echo $afDists ? te('-- ເລືອກເມືອງ --') : te('-- ເລືອກແຂວງກ່ອນ --'); ?></option>
                <?php foreach ($afDists as $dist): ?>
                    <option value="<?php echo e($dist); ?>"<?php echo ($af['district'] ?? '') === $dist ? ' selected' : ''; ?>><?php echo e($dist); ?></option>
                <?php endforeach; ?>
            </select>
            <?php echo $afErr('district'); ?>
        </label>
        <label class="mf-field">
            <span><?php echo te('ບ້ານ *'); ?></span>
            <input class="input" type="text" name="village" value="<?php echo e($af['village'] ?? ''); ?>" maxlength="150" required placeholder="<?php echo te('ປ້ອນຊື່ບ້ານ...'); ?>">
            <?php echo $afErr('village'); ?>
        </label>
    </div>
    <label class="mf-field">
        <span><?php echo te('ລາຍລະອຽດທີ່ຢູ່ (ຮ່ອມ, ເຮືອນເລກທີ, ຈຸດສັງເກດ)'); ?></span>
        <textarea class="input" name="address" rows="2" maxlength="255" placeholder="<?php echo te('ເຊັ່ນ: ຮ່ອມ 5, ໃກ້ຕະຫຼາດ...'); ?>"><?php echo e($af['address'] ?? ''); ?></textarea>
    </label>

    <div class="mf-courier-row">
        <div class="mf-courier-col-pick">
            <span class="mf-lbl"><?php echo te('ບໍລິສັດຂົນສົ່ງ'); ?><?php echo $afReq; ?></span>
            <div class="po-couriers">
                <?php foreach (getLaoCouriers() as $cCode => $cInfo):
                    $checked = ($af['courier_code'] ?? '') === $cCode;
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
            <?php echo $afErr('courier_code'); ?>
        </div>
        <div class="mf-courier-col-branch">
            <label class="mf-field">
                <span><?php echo te('ສາຂາຂົນສົ່ງປາຍທາງ / ບ່ອນຮັບເຄື່ອງ'); ?><?php echo $afReq; ?></span>
                <textarea class="input" name="courier_branch" rows="2" maxlength="300" placeholder="<?php echo te('ເຊັ່ນ: ອະນຸສິດ ສາຂາ ດົງໂດກ, HAL ສາຂາ ປາກເຊ...'); ?>"<?php echo $afReq ? ' required' : ''; ?>><?php echo e($af['courier_branch'] ?? ''); ?></textarea>
                <small class="mf-hint"><?php echo te('ລະບຸຊື່ສາຂາຂົນສົ່ງທີ່ທ່ານສະດວກໄປຮັບ'); ?></small>
                <?php echo $afErr('courier_branch'); ?>
            </label>
        </div>
    </div>
</div>
