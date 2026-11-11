<?php
/**
 * ເລືອກບັນຊີທະນາຄານທີ່ໂອນ (w_bank_accounts ຈາກຫຼັງບ້ານ — QR + ເລກບັນຊີ) + ແນບຮູບສະລິບ ພ້ອມເບິ່ງຕົວຢ່າງ.
 * ໃຊ້ຮ່ວມ: components/member_order_form.php, pages/checkout.php (ສະມາຊິກ), pages/my_order.php (ແນບສະລິບພາຍຫຼັງ).
 * ຕ້ອງຢູ່ໃນ <form data-order-form enctype="multipart/form-data"> (assets/js/main.js: ຕິກເລືອກທະນາຄານ, ຕົວຢ່າງຮູບສະລິບ).
 * ຕົວແປ: $banks (getActiveBankAccounts), $bankSel (id ທີ່ເລືອກ), $afErr (fn ຂໍ້ຜິດພາດ), $slipRequired (bool), $slipAmount (ຍອດທີ່ຕ້ອງໂອນ, 0 = ບໍ່ສະແດງ)
 */
$bankSel = (int)($bankSel ?? 0);
if (!$bankSel && !empty($banks)) {
    $bankSel = (int)$banks[0]['id'];
}
$activeBank = null;
foreach ($banks as $b) {
    if ((int)$b['id'] === $bankSel) {
        $activeBank = $b;
        break;
    }
}
if (!$activeBank && !empty($banks)) {
    $activeBank = $banks[0];
    $bankSel = (int)$activeBank['id'];
}
?>
<div class="mf-pay">
    <?php if (!empty($slipAmount)): ?>
        <div class="mf-pay-amount"><?php echo te('ຍອດທີ່ຕ້ອງໂອນ'); ?> <b data-order-total><?php echo e(money($slipAmount)); ?></b> <small><?php echo te('(ບໍ່ລວມຄ່າຝາກຂົນສົ່ງ — ຈ່າຍປາຍທາງ)'); ?></small></div>
    <?php endif; ?>

    <div class="mf-pay-split">
        <!-- ຟາກຊ້າຍ: ເລືອກທະນາຄານ + ເລກບັນຊີ + QR Code -->
        <div class="mf-pay-col-bank">
            <?php if ($banks): ?>
                <div class="mf-banks">
                    <?php foreach ($banks as $b):
                        $on = (int)$b['id'] === $bankSel;
                    ?>
                        <label class="po-bank-pill<?php echo $on ? ' is-selected' : ''; ?>"
                               data-bank-card
                               data-id="<?php echo (int)$b['id']; ?>"
                               data-name="<?php echo e($b['bank_name']); ?>"
                               data-acc="<?php echo e($b['account_number']); ?>"
                               data-acc-name="<?php echo e($b['account_name']); ?>"
                               data-logo="<?php echo e($b['logo_url']); ?>"
                               data-qr="<?php echo e($b['qr_url']); ?>">
                            <input type="radio" name="bank_account_id" value="<?php echo (int)$b['id']; ?>"<?php echo $on ? ' checked' : ''; ?>>
                            <?php if ($b['logo_url']): ?>
                                <img class="po-bank-pill-logo" src="<?php echo e($b['logo_url']); ?>" alt="" width="22" height="22">
                            <?php endif; ?>
                            <span class="po-bank-pill-name"><?php echo e($b['bank_name']); ?></span>
                            <span class="po-tick" aria-hidden="true">✓</span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php echo $afErr('bank_account_id'); ?>

                <?php if ($activeBank): ?>
                    <div class="mf-active-qr-box" id="bankQrBox" data-bank-qr-box<?php echo empty($activeBank['qr_url']) ? ' data-no-qr="1"' : ''; ?>>
                        <div class="mf-active-qr-head">
                            <div class="mf-active-acc-row">
                                <span class="mf-active-acc-lbl"><?php echo te('ເລກບັນຊີ:'); ?></span>
                                <strong id="activeBankAcc" class="mf-active-acc-num"><?php echo e($activeBank['account_number']); ?></strong>
                                <small id="activeBankAccName" class="mf-active-acc-name">(<?php echo e($activeBank['account_name']); ?>)</small>
                                <button type="button" class="btn btn-outline btn-sm mf-copy-btn" id="copyBankAccBtn" data-copy="<?php echo e($activeBank['account_number']); ?>" title="<?php echo te('ຄັດລອກເລກບັນຊີ'); ?>">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <span class="mf-copy-txt"><?php echo te('ຄັດລອກ'); ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="mf-active-qr-content">
                            <div class="mf-active-qr-img-wrap" id="activeBankQrWrap"<?php echo empty($activeBank['qr_url']) ? ' style="display:none;"' : ''; ?>>
                                <img id="activeBankQrImg" src="<?php echo e($activeBank['qr_url']); ?>" alt="QR Code" loading="lazy">
                            </div>
                            <div class="mf-active-qr-details">
                                <p class="mf-active-qr-guide" id="activeBankQrGuide"><?php echo !empty($activeBank['qr_url']) ? te('ສະແກນ QR ຜ່ານແອັບທະນາຄານ ຫຼື ດາວໂຫລດຮູບໄປສະແກນຈ່າຍ') : te('ບໍ່ມີ QR Code ສຳລັບບັນຊີນີ້ — ກະລຸນາໂອນເຂົ້າເລກບັນຊີຂ້າງເທິງນີ້'); ?></p>
                                <div class="mf-active-qr-btns" id="activeBankQrBtns"<?php echo empty($activeBank['qr_url']) ? ' style="display:none;"' : ''; ?>>
                                    <a id="activeBankQrDownload" class="btn btn-primary btn-sm mf-dl-qr-btn" href="<?php echo e($activeBank['qr_url']); ?>" download="<?php echo e('qr-' . preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($activeBank['bank_name'])) . '.jpg'); ?>" data-dl-qr>
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        <span><?php echo te('ດາວໂຫລດ QR Code'); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="mf-note"><?php echo te('ທີມງານຈະສົ່ງເລກບັນຊີໃຫ້ທາງ WhatsApp ຫຼັງຢືນຢັນອໍເດີ'); ?></p>
            <?php endif; ?>
        </div>

        <!-- ຟາກຂວາ: ແນບສະລິບການໂອນເງິນ + ສະແດງຕົວຢ່າງຮູບສະລິບທັນທີ ແບບກະທັດຮັດ ບໍ່ໃຫຍ່ -->
        <div class="mf-pay-col-slip">
            <span class="mf-lbl"><?php echo te('ແນບຮູບສະລິບການໂອນເງິນ'); ?><?php echo !empty($slipRequired) ? ' *' : ''; ?></span>
            <label class="mf-slip" id="slipUploadCard">
                <input type="file" name="slip" id="slipFileInput" accept="image/jpeg,image/png,image/webp"<?php echo !empty($slipRequired) ? ' required' : ''; ?>>
                <span class="mf-slip-ph" id="slipPlaceholder">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <b><?php echo te('ຄລິກເພື່ອແນບຮູບສະລິບ'); ?></b>
                    <small><?php echo te('JPG, PNG, WEBP (ບໍ່ເກີນ 8 MB)'); ?></small>
                    <span class="mf-slip-subhint"><?php echo empty($slipRequired) ? te('ຍັງບໍ່ໄດ້ໂອນ? ສາມາດແນບພາຍຫຼັງໄດ້') : te('ກະລຸນາແນບສະລິບເພື່ອຢືນຢັນ'); ?></span>
                </span>
                <span class="mf-slip-preview" id="slipPreviewBox" hidden>
                    <img id="slipPreviewImg" alt="<?php echo te('ຮູບສະລິບໂອນເງິນ'); ?>">
                    <span class="mf-slip-change-pill">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        <span><?php echo te('ແນບຮູບແລ້ວ (ຄລິກເພື່ອປ່ຽນ)'); ?></span>
                    </span>
                </span>
            </label>
            <?php echo $afErr('slip'); ?>
        </div>
    </div>
</div>
