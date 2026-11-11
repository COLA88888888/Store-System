<?php
/*
 * ສ່ວນ: ຟອມຂໍ້ມູນຮ້ານ
 * ຟອມ: ໂລໂກ້, ຊື່ ແລະ ເບີໂທ, ທີ່ຢູ່, ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ, VAT, ຂໍ້ຄວາມທ້າຍບິນ ແລະ ໝາຍເລກຄິວ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/stores/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><div class="container-fluid p-4">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
        <i class="fas fa-store text-primary mr-2"></i> <?php echo htmlspecialchars(t('stores.page_title', 'ຂໍ້ມູນຮ້ານຄ້າ')); ?>
      </h5>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: '<?php echo $message_type === "success" ? "success" : ($message_type === "warning" ? "warning" : "error"); ?>',
          title: '<?php echo $message_type === "success" ? tl('ສຳເລັດ') : tl('ແຈ້ງເຕືອນ'); ?>',
          text: <?php echo json_encode($message); ?>,
          confirmButtonColor: '#2563eb'
        });
      });
    </script>
  <?php endif; ?>

  <div class="row">
    <!-- Store Information Edit Form -->
    <div class="col-lg-12 mb-4">
      <div class="store-card p-4 p-md-5">
        <h6 class="font-weight-bold text-dark mb-4 pb-2 border-bottom d-flex align-items-center">
          <i class="fas fa-edit text-primary mr-2"></i> <?php echo htmlspecialchars(t('stores.section_edit_info', 'ແກ້ໄຂຂໍ້ມູນຮ້ານ / ບໍລິສັດ')); ?>
        </h6>

        <form id="storeInfoForm" action="" method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="action" value="save_store_info">

          <!-- Logo Upload Section -->
          <div class="row align-items-center mb-4 pb-3 border-bottom">
            <!-- Store Logo -->
            <div class="col-md-12">
              <div class="d-flex align-items-center">
                <div class="position-relative mr-3" style="width: 100px; height: 100px; flex-shrink: 0;">
                  <img id="logo_preview" src="<?php echo htmlspecialchars($logoPath); ?>" 
                       style="width: 100px; height: 100px; object-fit: contain; border-radius: 50%; border: 2px solid #93c5fd; background: #f8fafc; padding: 4px; cursor: pointer;"
                       onclick="document.getElementById('logo_input').click();"
                       onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'><circle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%23e2e8f0\'/><text x=\'50\' y=\'58\' text-anchor=\'middle\' font-size=\'38\' fill=\'%2394a3b8\'>&#128722;</text></svg>'">
                  <?php /* btn-xs + rounded-circle: ບໍ່ໃຫ້ກົດ 38px / ມຸມ 6px ທົ່ວລະບົບ (assets/css/global-custom/03-controls.css) ແຕະ */ ?>
                  <button type="button" class="btn btn-xs btn-primary rounded-circle position-absolute shadow-sm d-flex align-items-center justify-content-center p-0"
                          style="right: 0; bottom: 0; width: 26px; height: 26px; border: 2px solid #fff;"
                          onclick="document.getElementById('logo_input').click();"
                          title="<?php echo htmlspecialchars(t('stores.logo_change_title', 'ປ່ຽນຮູບໂລໂກ້')); ?>">
                    <i class="fas fa-camera" style="font-size: 0.7rem;"></i>
                  </button>
                  <!-- ປຸ່ມ ✕ ສີແດງ: ລຶບໂລໂກ້ (ມີຜົນເມື່ອກົດບັນທຶກ) — ສະແດງສະເພາະເມື່ອມີໂລໂກ້ -->
                  <button type="button" id="logo_remove_btn" class="btn btn-xs btn-danger rounded-circle position-absolute shadow-sm d-flex align-items-center justify-content-center p-0"
                          style="right: 0; top: 0; width: 24px; height: 24px; border: 2px solid #fff;<?php echo $hasCustomLogo ? '' : ' display: none !important;'; ?>"
                          onclick="removeStoreLogo()"
                          title="<?php echo htmlspecialchars(t('stores.logo_remove_title', 'ລຶບຮູບໂລໂກ້')); ?>">
                    <i class="fas fa-times" style="font-size: 0.72rem;"></i>
                  </button>
                  <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                </div>
                <div>
                  <h6 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('stores.logo_heading', 'ໂລໂກ້ຮ້ານຄ້າ')); ?></h6>
                  <small class="text-muted d-block mb-2"><?php echo htmlspecialchars(t('stores.logo_help', 'ຮອງຮັບຮູບ JPG, PNG, WEBP (500x500px)')); ?></small>
                  <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 6px;" onclick="document.getElementById('logo_input').click();">
                    <i class="fas fa-upload mr-1"></i> <?php echo htmlspecialchars(t('stores.logo_choose_btn', 'ເລືອກຮູບໂລໂກ້')); ?>
                  </button>
                  <input type="file" name="logo_img" id="logo_input" accept="image/*" style="display: none;" onchange="previewStoreLogo(this)">
                </div>
              </div>
            </div>
          </div>

          <?php
            // Every branch (not just main) now edits its own name/address/phone/tax/etc.
            $sharedReadonly = '';
            $sharedDisabled = '';
            $sharedFieldClass = '';
          ?>

          <?php if (!$isMainBranchView): ?>
            <div class="alert alert-info border-0 mb-4" style="border-radius: 10px; font-size: 0.88rem;">
              <i class="fas fa-info-circle mr-1"></i> <?php echo htmlspecialchars(t('stores.branch_own_info_note', 'ຂໍ້ມູນນີ້ແມ່ນຂອງສາຂານີ້ໂດຍສະເພາະ, ບໍ່ປົນກັບສາຂາອື່ນ.')); ?>
            </div>
          <?php endif; ?>

          <!-- Store Name & Phone -->
          <div class="row">
            <div class="col-md-7 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_name', 'ຊື່ຮ້ານຄ້າ / ບໍລິສັດ:')); ?> <span class="text-danger">*</span></label>
              <input type="text" name="com_name_la" id="input_store_name" class="form-control font-weight-bold text-primary <?php echo $sharedFieldClass; ?>"
                     value="<?php echo htmlspecialchars($company['com_name_la'] ?? ''); ?>"
                     placeholder="<?php echo htmlspecialchars(t('stores.field_name_ph', 'ປ້ອນຊື່ຮ້ານຄ້າ...')); ?>" <?php echo $sharedReadonly; ?> required>
            </div>
            <div class="col-md-5 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_tel', 'ເບີໂທລະສັບຕິດຕໍ່:')); ?> <span class="text-danger">*</span></label>
              <input type="text" name="com_tel" id="input_store_tel" class="form-control <?php echo $sharedFieldClass; ?>"
                     value="<?php echo htmlspecialchars($company['com_tel'] ?? ''); ?>"
                     placeholder="020 xxxxxxxx" <?php echo $sharedReadonly; ?> required>
            </div>
          </div>

          <!-- Store Address -->
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_address', 'ທີ່ຢູ່ຮ້ານຄ້າ (ສະແດງໃນໃບບິນ):')); ?></label>
            <textarea name="com_address" id="input_store_address" rows="2" class="form-control <?php echo $sharedFieldClass; ?>"
                      placeholder="<?php echo htmlspecialchars(t('stores.field_address_ph', 'ບ້ານ, ເມືອງ, ແຂວງ...')); ?>" <?php echo $sharedReadonly; ?>><?php echo htmlspecialchars($company['com_address'] ?? ''); ?></textarea>
          </div>

          <!-- Taxpayer ID / Tax Number -->
          <div class="form-group mb-3" id="tax_id_wrap">
            <label class="font-weight-bold text-dark small mb-1">
              <i class="fas fa-id-card text-primary mr-1"></i> <?php echo htmlspecialchars(t('stores.field_tax_id', 'ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ:')); ?>
            </label>
            <input type="text" name="tax_id" class="form-control font-weight-bold <?php echo $sharedFieldClass; ?>"
                   value="<?php echo htmlspecialchars($company['tax_id'] ?? ''); ?>"
                   placeholder="<?php echo htmlspecialchars(t('stores.field_tax_id_ph', 'ປ້ອນເລກປະຈຳຕົວຜູ້ເສຍອາກອນ (ຖ້າບໍ່ປ້ອນ ຈະບໍ່ສະແດງໃນໃບບິນ)...')); ?>" <?php echo $sharedReadonly; ?>>
            <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> <?php echo htmlspecialchars(t('stores.tax_id_note', 'ໝາຍເຫດ: ຖ້າປ້ອນເລກປະຈຳຕົວຜູ້ເສຍອາກອນ ລະບົບຈະສະແດງໃນໃບບິນອັດໂນມັດ, ຖ້າປະຫວ່າງໄວ້ ຈະບໍ່ສະແດງໃນໃບບິນ.')); ?></small>
          </div>

          <!-- Tax / VAT Configuration Section -->
          <div class="form-group mb-4 p-3 bg-light rounded border">
            <label class="font-weight-bold text-dark small mb-2 d-block">
              <i class="fas fa-percent text-danger mr-1"></i> <?php echo htmlspecialchars(t('stores.vat_section_label', 'ການຕັ້ງຄ່າອາກອນມູນຄ່າເພີ່ມ (ອມພ):')); ?>
            </label>
            <div class="row align-items-center">
              <div class="col-md-7 mb-2 mb-md-0">
                <div class="custom-control custom-radio custom-control-inline">
                  <input type="radio" id="tax_inc" name="tax_type" value="inclusive" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'inclusive') ? 'checked' : ''; ?> <?php echo $sharedDisabled; ?>>
                  <label class="custom-control-label font-weight-bold text-dark small" for="tax_inc">
                    <?php echo htmlspecialchars(t('stores.tax_inclusive', 'ອາກອນພາຍໃນ')); ?> <span class="text-muted"><?php echo htmlspecialchars(t('stores.tax_inclusive_hint', '(Inclusive - ລາຄາລວມ ອມພ ແລ້ວ)')); ?></span>
                  </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1">
                  <input type="radio" id="tax_exc" name="tax_type" value="exclusive" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'exclusive') ? 'checked' : ''; ?> <?php echo $sharedDisabled; ?>>
                  <label class="custom-control-label font-weight-bold text-dark small" for="tax_exc">
                    <?php echo htmlspecialchars(t('stores.tax_exclusive', 'ອາກອນພາຍນອກ')); ?> <span class="text-muted"><?php echo htmlspecialchars(t('stores.tax_exclusive_hint', '(Exclusive - ບວກເພີ່ມ ອມພ %)')); ?></span>
                  </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1">
                  <input type="radio" id="tax_none" name="tax_type" value="none" class="custom-control-input" <?php echo (($company['tax_type'] ?? '') === 'none') ? 'checked' : ''; ?> <?php echo $sharedDisabled; ?>>
                  <label class="custom-control-label font-weight-bold text-muted small" for="tax_none">
                    <?php echo htmlspecialchars(t('stores.tax_none', 'ບໍ່ມີອາກອນມູນຄ່າເພີ່ມ (0%)')); ?>
                  </label>
                </div>
              </div>
              <div class="col-md-5" id="vat_percent_wrap">
                <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.vat_rate', 'ອັດຕາ ອມພ (%):')); ?></label>
                <div class="input-group">
                  <input type="number" step="any" min="0" max="100" name="vat_percent" class="form-control font-weight-bold text-primary <?php echo $sharedFieldClass; ?>"
                         value="<?php echo htmlspecialchars($company['vat_percent'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(t('stores.vat_rate_ph', 'ກະລຸນາປ້ອນອັດຕາ ອມພ %')); ?>" <?php echo $sharedReadonly; ?>>
                  <div class="input-group-append">
                    <span class="input-group-text font-weight-bold">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>



          <!-- Receipt Footer Message -->
          <div class="form-group mb-4">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('stores.field_footer', 'ຂໍ້ຄວາມທ້າຍໃບບິນ:')); ?></label>
            <input type="text" name="receipt_footer" id="input_store_footer" class="form-control <?php echo $sharedFieldClass; ?>"
                   value="<?php echo htmlspecialchars($company['barcode'] ?? ''); ?>"
                   placeholder="<?php echo htmlspecialchars(t('stores.field_footer_ph', 'ເຊັ່ນ: ຂອບໃຈທີ່ມາອຸດໜູນ, ສິນຄ້າຊື້ແລ້ວບໍ່ຮັບປ່ຽນຄືນ...')); ?>" <?php echo $sharedReadonly; ?>>
            <?php /* ບັອກດຽວ: ພິມໃສ່ພາສາໃດ ໃບບິນກໍສະແດງຕາມນັ້ນ ທຸກພາສາ (layouts/receipt_template.php) */ ?>
          </div>

          <!-- Queue number on receipt (coffee-shop style) -->
          <?php if ($isMainBranchView): ?>
          <div class="form-group mb-4">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="queue_enabled" name="queue_enabled" value="1"
                     <?php echo (getSetting($pdo, 'queue_enabled', '0') === '1') ? 'checked' : ''; ?>>
              <label class="custom-control-label font-weight-bold text-dark small" for="queue_enabled">
                <?php echo htmlspecialchars(t('stores.field_queue_enabled', 'ສະແດງເລກຄິວເທິງໃບບິນ (ຮ້ານກາເຟ / ຮ້ານທີ່ມີຄິວ)')); ?>
              </label>
            </div>
            <small class="text-muted"><?php echo htmlspecialchars(t('stores.field_queue_hint', 'ເລກຄິວເລີ່ມ 1 ໃໝ່ທຸກມື້ ພິມໂຕໃຫຍ່ຢູ່ເທິງໂລໂກ້. ຮ້ານທົ່ວໄປປິດໄວ້.')); ?></small>
          </div>
          <?php endif; ?>

          <!-- Submit Button (Aligned to the Right) -->
          <div class="pt-3 border-top text-right d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
              <i class="fas fa-save mr-2"></i> <?php echo htmlspecialchars(t('stores.btn_save_info', 'ບັນທຶກຂໍ້ມູນຮ້ານຄ້າ')); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

