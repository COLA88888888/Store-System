<?php
$bp = isset($base_path) ? rtrim($base_path, '/') . '/' : '../../../';
?>
<!-- ============================================================
     exchange_rate_modals.php - ຟອມ Modal ເພີ່ມ, ແກ້ໄຂ ແລະ ລຶບອັດຕາແລກປ່ຽນ
     ============================================================ -->

<!-- 1. Modal: ເພີ່ມອັດຕາແລກປ່ຽນເງິນໃໝ່ -->
<div class="modal fade" id="rateModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="save_rate">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
            <i class="fas fa-plus-circle mr-2"></i> <?php echo htmlspecialchars(t('exchange_rate.modal_add_title', 'ອັບເດດອັດຕາແລກປ່ຽນເງິນໃໝ່')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">
              <img src="<?php echo $bp; ?>assets/img/flag_img/Flag_of_Thailand.webp" alt="THB" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
              <span class="flag-icon flag-icon-th mr-1" style="display:none;"></span>
              <?php echo htmlspecialchars(t('exchange_rate.label_thb', '1 ບາດ (THB ➔ LAK):')); ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="text" name="ex_kip_bath" class="form-control text-right font-weight-bold text-success" placeholder="0" value="650" oninput="formatPriceInput(this)" required style="height: 42px; font-size: 1.05rem;">
              <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo kipSymbol(); ?></span></div>
            </div>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">
              <img src="<?php echo $bp; ?>assets/img/flag_img/flag-Stars.webp" alt="USD" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
              <span class="flag-icon flag-icon-us mr-1" style="display:none;"></span>
              <?php echo htmlspecialchars(t('exchange_rate.label_usd', '1 ໂດລາ (USD ➔ LAK):')); ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="text" name="ex_kip_us" class="form-control text-right font-weight-bold text-primary" placeholder="0" value="22,000" oninput="formatPriceInput(this)" required style="height: 42px; font-size: 1.05rem;">
              <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo kipSymbol(); ?></span></div>
            </div>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">
              <img src="<?php echo $bp; ?>assets/img/flag_img/Flag-chaina.webp" alt="CNY" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
              <span class="flag-icon flag-icon-cn mr-1" style="display:none;"></span>
              <?php echo htmlspecialchars(t('exchange_rate.label_cny', '1 ຢວນ (CNY ➔ LAK):')); ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="text" name="ex_kip_cn" class="form-control text-right font-weight-bold text-warning" placeholder="0" value="3,100" oninput="formatPriceInput(this)" required style="height: 42px; font-size: 1.05rem; color: #b45309 !important;">
              <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo kipSymbol(); ?></span></div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-0">
          <button type="button" class="btn btn-light font-weight-bold px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('exchange_rate.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="border: none; background: linear-gradient(135deg, #2c5aa0, #244886);"><?php echo htmlspecialchars(t('exchange_rate.btn_save', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Modal: ແກ້ໄຂອັດຕາແລກປ່ຽນເງິນ -->
<div class="modal fade" id="editRateModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="update_rate">
        <input type="hidden" name="rate_id" id="edit_rate_id" value="">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
            <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('exchange_rate.modal_edit_title', 'ແກ້ໄຂອັດຕາແລກປ່ຽນເງິນ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">
              <img src="<?php echo $bp; ?>assets/img/flag_img/Flag_of_Thailand.webp" alt="THB" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
              <span class="flag-icon flag-icon-th mr-1" style="display:none;"></span>
              <?php echo htmlspecialchars(t('exchange_rate.label_thb', '1 ບາດ (THB ➔ LAK):')); ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="text" name="ex_kip_bath" id="edit_ex_kip_bath" class="form-control text-right font-weight-bold text-success" placeholder="0" oninput="formatPriceInput(this)" required style="height: 42px; font-size: 1.05rem;">
              <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo kipSymbol(); ?></span></div>
            </div>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">
              <img src="<?php echo $bp; ?>assets/img/flag_img/flag-Stars.webp" alt="USD" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
              <span class="flag-icon flag-icon-us mr-1" style="display:none;"></span>
              <?php echo htmlspecialchars(t('exchange_rate.label_usd', '1 ໂດລາ (USD ➔ LAK):')); ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="text" name="ex_kip_us" id="edit_ex_kip_us" class="form-control text-right font-weight-bold text-primary" placeholder="0" oninput="formatPriceInput(this)" required style="height: 42px; font-size: 1.05rem;">
              <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo kipSymbol(); ?></span></div>
            </div>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1">
              <img src="<?php echo $bp; ?>assets/img/flag_img/Flag-chaina.webp" alt="CNY" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
              <span class="flag-icon flag-icon-cn mr-1" style="display:none;"></span>
              <?php echo htmlspecialchars(t('exchange_rate.label_cny', '1 ຢວນ (CNY ➔ LAK):')); ?> <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="text" name="ex_kip_cn" id="edit_ex_kip_cn" class="form-control text-right font-weight-bold text-warning" placeholder="0" oninput="formatPriceInput(this)" required style="height: 42px; font-size: 1.05rem; color: #b45309 !important;">
              <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo kipSymbol(); ?></span></div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-0">
          <button type="button" class="btn btn-light font-weight-bold px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('exchange_rate.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><?php echo htmlspecialchars(t('exchange_rate.btn_update', 'ອັບເດດ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. ຟອມ Hidden ສຳລັບສົ່ງຄ່າລຶບອັດຕາແລກປ່ຽນ -->
<form id="deleteRateForm" action="" method="POST" style="display: none;">
  <input type="hidden" name="action" value="delete_rate">
  <input type="hidden" name="rate_id" id="delete_rate_id" value="">
</form>