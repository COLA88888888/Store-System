<!-- Loyalty: exchange the selected customer's points for a reward (list built by pos_loyalty_js.php) -->
<div class="modal fade" id="redeemPointsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
      <div class="modal-header text-white py-3" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
        <h5 class="modal-title font-weight-bold" style="font-size: 1.1rem;">
          <i class="fas fa-gift mr-2"></i><span data-i18n-key="pos.redeem_modal_title"><?php echo htmlspecialchars(t('pos.redeem_modal_title', 'ແລກຄະແນນ')); ?></span>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body p-3" style="background: #f8fafc;">
        <div class="d-flex justify-content-between align-items-center mb-3 p-2 px-3 bg-white" style="border-radius: 10px; border: 1.5px solid #fde68a;">
          <div>
            <div class="font-weight-bold text-dark" id="redeemCustomerName">-</div>
            <small class="text-muted" data-i18n-key="pos.redeem_available"><?php echo htmlspecialchars(t('pos.redeem_available', 'ຄະແນນທີ່ໃຊ້ໄດ້')); ?></small>
          </div>
          <div class="font-weight-bold" style="font-size: 1.5rem; color: #b45309;">
            <i class="fas fa-star mr-1" style="font-size: 1.1rem;"></i><span id="redeemAvailablePoints">0</span>
          </div>
        </div>
        <div id="redeemRewardList"></div>
      </div>
      <div class="modal-footer bg-white py-2">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 8px;" data-i18n-key="pos.redeem_close"><?php echo htmlspecialchars(t('pos.redeem_close', 'ປິດ')); ?></button>
      </div>
    </div>
  </div>
</div>
