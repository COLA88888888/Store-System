<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ກົດການໄດ້ຄະແນນ, ຕາຕະລາງລາງວັນ, ລູກຄ້າຄະແນນສູງສຸດ, ປະຫວັດ ແລະ popup ລາງວັນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/loyalty/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="row align-items-center mb-3">
    <div class="col-sm-6">
      <h5 class="m-0 font-weight-bold text-dark" style="font-size: 18px;">
        <i class="fas fa-star text-warning mr-2"></i><?php echo htmlspecialchars(t('loyalty.page_title', 'ຕັ້ງຄ່າຄະແນນສະສົມຂອງລູກຄ້າ')); ?>
      </h5>
      <small class="text-muted"><?php echo htmlspecialchars(t('loyalty.page_subtitle', 'ລູກຄ້າຊື້ເຄື່ອງໄດ້ຄະແນນ ແລະ ເອົາຄະແນນມາແລກເປັນສ່ວນຫຼຸດ ຫຼື ສິນຄ້າໄດ້ຢູ່ໜ້າຂາຍ')); ?></small>
    </div>
    <div class="col-sm-6 text-right d-flex align-items-center justify-content-end mt-3 mt-sm-0" style="gap: 10px;">
      <span class="badge <?php echo $lpEnabled ? 'badge-success' : 'badge-secondary'; ?> px-3 py-2" style="font-size: 0.82rem; border-radius: 8px;">
        <i class="fas <?php echo $lpEnabled ? 'fa-check-circle' : 'fa-pause-circle'; ?> mr-1"></i><?php echo htmlspecialchars($lpEnabled ? t('loyalty.status_enabled', 'ກຳລັງເປີດໃຊ້ງານ') : t('loyalty.status_disabled', 'ປິດໃຊ້ງານຢູ່')); ?>
      </span>
      <?php if ($canAddLoyalty): ?>
        <button type="button" class="btn btn-primary px-3 font-weight-bold shadow-sm" onclick="lpOpenRewardModal()" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('loyalty.add_reward_title', 'ເພີ່ມລາຍການແລກຄະແນນ')); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      $(function () {
        Swal.fire({
          title: <?php echo json_encode($message_type === 'success' ? t('loyalty.msg_saved_title', 'ບັນທຶກສຳເລັດ!') : t('loyalty.msg_error_title', 'ແຈ້ງເຕືອນ'), JSON_UNESCAPED_UNICODE); ?>,
          text: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
          icon: '<?php echo $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'error'); ?>',
          confirmButtonColor: '#2c5aa0',
          confirmButtonText: <?php echo json_encode(t('loyalty.ok_btn', 'ຕົກລົງ'), JSON_UNESCAPED_UNICODE); ?>
        });
      });
    </script>
  <?php endif; ?>

  <div class="row">
    <!-- Earning rule -->
    <div class="col-lg-4 mb-3">
      <div class="card border-0 shadow-sm mb-0 lp-card">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-coins mr-2 text-primary"></i><?php echo htmlspecialchars(t('loyalty.rule_title', 'ກົດການໄດ້ຄະແນນ')); ?></h6>
        </div>
        <div class="card-body px-4">
          <form method="POST" action="index.php">
            <input type="hidden" name="action" value="save_loyalty">

            <div class="custom-control custom-switch mb-3">
              <input type="checkbox" class="custom-control-input" id="loyalty_enabled" name="loyalty_enabled" <?php echo $lpEnabled ? 'checked' : ''; ?> <?php echo $canEditLoyalty ? '' : 'disabled'; ?>>
              <label class="custom-control-label font-weight-bold text-dark" for="loyalty_enabled"><?php echo htmlspecialchars(t('loyalty.enable_label', 'ເປີດໃຊ້ງານຄະແນນສະສົມ')); ?></label>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.spend_amount_label', 'ຍອດຊື້ທຸກໆ (ກີບ)')); ?></label>
              <div class="input-group">
                <input type="text" inputmode="numeric" class="form-control font-weight-bold text-right" id="loyalty_spend_amount" name="loyalty_spend_amount" value="<?php echo number_format($lpSpend); ?>" oninput="this.value=this.value.replace(/[^0-9]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,',');" style="height: 42px;" <?php echo $canEditLoyalty ? '' : 'readonly'; ?>>
                <div class="input-group-append"><span class="input-group-text font-weight-bold">₭</span></div>
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.points_earned_label', 'ຈະໄດ້ຮັບຄະແນນ')); ?></label>
              <div class="input-group">
                <input type="number" min="1" class="form-control font-weight-bold text-right" id="loyalty_points_earned" name="loyalty_points_earned" value="<?php echo $lpPoints; ?>" style="height: 42px;" <?php echo $canEditLoyalty ? '' : 'readonly'; ?>>
                <div class="input-group-append"><span class="input-group-text font-weight-bold"><?php echo htmlspecialchars(t('loyalty.points_unit', 'ຄະແນນ')); ?></span></div>
              </div>
            </div>


            <?php if ($canEditLoyalty): ?>
              <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm" style="border-radius: 8px; height: 42px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
                <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('loyalty.save_btn', 'ບັນທຶກ')); ?>
              </button>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>

    <!-- Rewards -->
    <div class="col-lg-8 mb-3">
      <div class="card border-0 shadow-sm mb-0 lp-card">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-gift mr-2 text-primary"></i><?php echo htmlspecialchars(t('loyalty.rewards_title', 'ລາຍການແລກຄະແນນ')); ?></h6>
          <span class="badge badge-light border font-weight-bold text-muted px-2 py-1 ml-auto"><?php echo count($loyaltyRewards); ?> <?php echo htmlspecialchars(t('loyalty.items_unit', 'ລາຍການ')); ?></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
              <tr style="white-space: nowrap;">
                <th class="text-center" style="width: 55px;"><?php echo htmlspecialchars(t('loyalty.col_no', 'ລຳດັບ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_reward', 'ແລກໄດ້')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_type', 'ປະເພດ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('loyalty.col_points', 'ໃຊ້ຄະແນນ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('loyalty.col_value', 'ມູນຄ່າ')); ?></th>
                <th class="text-center" style="width: 80px;"><?php echo htmlspecialchars(t('loyalty.col_status', 'ສະຖານະ')); ?></th>
                <?php if ($canEditLoyalty || $canDelLoyalty): ?><th class="text-center" style="width: 100px;"><?php echo htmlspecialchars(t('loyalty.col_manage', 'ຈັດການ')); ?></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($loyaltyRewards)): ?>
                <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-gift fa-2x mb-2 d-block text-secondary opacity-50"></i><?php echo htmlspecialchars(t('loyalty.rewards_empty_title', 'ຍັງບໍ່ມີລາຍການແລກຄະແນນ')); ?></td></tr>
              <?php else: ?>
                <?php foreach ($loyaltyRewards as $idx => $rw): ?>
                  <?php
                    $isProductRw = $rw['reward_type'] === 'product';
                    $rwMissing   = $isProductRw && $rw['product_name'] === null;
                    $rwValue     = $isProductRw ? (float)$rw['product_price'] * (int)$rw['product_qty'] : (float)$rw['discount_amount'];
                    $rwJson = [
                      'id' => (int)$rw['id'], 'name' => $rw['reward_name'], 'points' => (int)$rw['points_cost'],
                      'type' => $isProductRw ? 'product' : 'discount', 'amount' => (float)$rw['discount_amount'],
                      'min_spend' => (float)($rw['min_spend'] ?? 0),
                      'product_id' => $rw['product_id'] !== null ? (int)$rw['product_id'] : null, 'qty' => (int)$rw['product_qty'],
                    ];
                  ?>
                  <tr style="white-space: nowrap;<?php echo $rw['is_active'] ? '' : ' opacity: 0.6;'; ?>">
                    <td class="text-center font-weight-bold text-muted"><?php echo $idx + 1; ?></td>
                    <td class="font-weight-bold text-dark">
                      <?php echo htmlspecialchars($rw['reward_name']); ?>
                      <?php if ($rwMissing): ?>
                        <small class="d-block text-danger font-weight-normal"><i class="fas fa-exclamation-triangle mr-1"></i><?php echo htmlspecialchars(t('loyalty.reward_product_missing', 'ບໍ່ພົບສິນຄ້ານີ້ໃນສາຂານີ້ — ຈະບໍ່ສະແດງຢູ່ໜ້າຂາຍ')); ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($isProductRw): ?>
                        <span class="badge badge-light border text-success font-weight-bold px-2 py-1" style="font-size: 0.76rem;"><i class="fas fa-box mr-1"></i><?php echo htmlspecialchars(t('loyalty.type_product', 'ແລກເປັນສິນຄ້າ')); ?></span>
                      <?php else: ?>
                        <span class="badge badge-light border text-danger font-weight-bold px-2 py-1" style="font-size: 0.76rem;"><i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars(t('loyalty.type_discount', 'ແລກເປັນສ່ວນຫຼຸດ')); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-light border font-weight-bold px-2 py-1" style="font-size: 0.8rem; color: #b45309;"><i class="fas fa-star mr-1"></i><?php echo number_format((int)$rw['points_cost']); ?></span>
                    </td>
                    <td class="text-right font-weight-bold text-dark">
                      <?php echo $rwMissing ? '-' : number_format($rwValue) . ' ₭'; ?>
                      <?php if (!$isProductRw): ?>
                        <?php
                          // The purchase the POS requires: the configured minimum, or — when there
                          // is none / it's not above the discount — simply more than the discount.
                          $rwMin = (float)($rw['min_spend'] ?? 0);
                          $rwAmt = (float)$rw['discount_amount'];
                          $rwNeedText = ($rwMin > $rwAmt)
                            ? sprintf(t('loyalty.min_spend_short', 'ຊື້ຂັ້ນຕ່ຳ %s ₭'), number_format($rwMin))
                            : sprintf(t('loyalty.min_spend_over_short', 'ຊື້ເກີນ %s ₭'), number_format($rwAmt));
                        ?>
                        <small class="d-block text-muted font-weight-normal"><?php echo htmlspecialchars($rwNeedText); ?></small>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if ($canEditLoyalty): ?>
                        <form method="POST" action="index.php" class="d-inline-block">
                          <input type="hidden" name="action" value="toggle_reward">
                          <input type="hidden" name="reward_id" value="<?php echo (int)$rw['id']; ?>">
                          <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="rwToggle<?php echo (int)$rw['id']; ?>" <?php echo $rw['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <label class="custom-control-label" for="rwToggle<?php echo (int)$rw['id']; ?>"></label>
                          </div>
                        </form>
                      <?php else: ?>
                        <span class="badge <?php echo $rw['is_active'] ? 'badge-success' : 'badge-secondary'; ?> px-2 py-1"><?php echo htmlspecialchars($rw['is_active'] ? t('loyalty.status_on', 'ເປີດ') : t('loyalty.status_off', 'ປິດ')); ?></span>
                      <?php endif; ?>
                    </td>
                    <?php if ($canEditLoyalty || $canDelLoyalty): ?>
                      <td class="text-center">
                        <?php if ($canEditLoyalty): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm px-2" title="<?php echo htmlspecialchars(t('loyalty.edit_btn', 'ແກ້ໄຂ')); ?>" onclick='lpOpenRewardModal(<?php echo json_encode($rwJson, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                        <?php endif; ?>
                        <?php if ($canDelLoyalty): ?>
                        <form method="POST" action="index.php" class="d-inline" onsubmit="return confirmDeleteReward(this);">
                          <input type="hidden" name="action" value="delete_reward">
                          <input type="hidden" name="reward_id" value="<?php echo (int)$rw['id']; ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="<?php echo htmlspecialchars(t('loyalty.delete_btn', 'ລຶບ')); ?>"><i class="fas fa-trash-alt"></i></button>
                        </form>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Top customers -->
    <div class="col-lg-5 mb-3" id="lpTopCustomers">
      <div class="card border-0 shadow-sm mb-0 lp-card">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-friends mr-2 text-info"></i><?php echo htmlspecialchars(t('loyalty.top_customers_title', 'ລູກຄ້າທີ່ມີຄະແນນຫຼາຍທີ່ສຸດ')); ?></h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
              <tr style="white-space: nowrap;">
                <th class="text-center" style="width: 55px;"><?php echo htmlspecialchars(t('loyalty.col_no', 'ລຳດັບ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_code', 'ລະຫັດ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_customer', 'ລູກຄ້າ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_phone', 'ເບີໂທ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('loyalty.col_balance', 'ຄະແນນທີ່ມີ')); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($topCustomers)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted"><?php echo htmlspecialchars(t('loyalty.top_customers_empty', 'ຍັງບໍ່ມີລູກຄ້າທີ່ມີຄະແນນ')); ?></td></tr>
              <?php else: ?>
                <?php foreach ($topCustomers as $i => $tc): ?>
                  <tr style="white-space: nowrap;">
                    <td class="text-center font-weight-bold text-muted"><?php echo ($cPage - 1) * $lpPerPage + $i + 1; ?></td>
                    <td><span class="lp-code-badge"><?php echo htmlspecialchars($tc['customer_code'] ?: '-'); ?></span></td>
                    <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($tc['customer_name']); ?></td>
                    <td class="text-dark"><?php echo htmlspecialchars($tc['phone'] ?: '-'); ?></td>
                    <td class="text-right">
                      <span class="badge badge-light border font-weight-bold px-2 py-1" style="font-size: 0.8rem; color: #b45309;"><i class="fas fa-star mr-1"></i><?php echo number_format((int)$tc['points']); ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php echo lpPager($topCustomersTotal, $cPage, $lpPerPage, 'cpage', 'lpTopCustomers'); ?>
      </div>
    </div>

    <!-- Recent history -->
    <div class="col-lg-7 mb-3" id="lpHistory">
      <div class="card border-0 shadow-sm mb-0 lp-card">
        <div class="card-header bg-white py-3 px-4 border-bottom">
          <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-history mr-2 text-primary"></i><?php echo htmlspecialchars(t('loyalty.history_title', 'ປະຫວັດຄະແນນລ່າສຸດ')); ?></h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
              <tr style="white-space: nowrap;">
                <th><?php echo htmlspecialchars(t('loyalty.col_date', 'ວັນທີ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_customer', 'ລູກຄ້າ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_activity', 'ລາຍການ')); ?></th>
                <th><?php echo htmlspecialchars(t('loyalty.col_bill', 'ເລກບິນ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('loyalty.col_change', 'ປ່ຽນແປງ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('loyalty.col_after', 'ຄົງເຫຼືອ')); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentLog)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted"><?php echo htmlspecialchars(t('loyalty.history_empty', 'ຍັງບໍ່ມີການເຄື່ອນໄຫວຂອງຄະແນນ')); ?></td></tr>
              <?php else: ?>
                <?php foreach ($recentLog as $lg): ?>
                  <?php
                    $change = (int)$lg['change_points'];
                    $logBadge = [
                      'earn' => 'text-success', 'redeem' => 'text-warning',
                      'redeem_refund' => 'text-info', 'earn_reversal' => 'text-danger',
                    ][$lg['log_type']] ?? 'text-dark';
                  ?>
                  <tr style="white-space: nowrap;">
                    <td class="text-muted"><i class="far fa-clock mr-1 text-info"></i><?php echo date('d/m/Y H:i', strtotime($lg['created_at'])); ?></td>
                    <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($lg['customer_name']); ?></td>
                    <td>
                      <span class="badge badge-light border font-weight-bold px-2 py-1 <?php echo $logBadge; ?>" style="font-size: 0.76rem;"><?php echo htmlspecialchars($logTypeLabels[$lg['log_type']] ?? $lg['log_type']); ?></span>
                      <?php if (!empty($lg['note'])): ?><small class="d-block text-muted"><?php echo htmlspecialchars($lg['note']); ?></small><?php endif; ?>
                    </td>
                    <td class="text-muted"><?php echo htmlspecialchars($lg['sale_bill'] ?: '-'); ?></td>
                    <td class="text-right font-weight-bold <?php echo $change >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($change > 0 ? '+' : '') . number_format($change); ?></td>
                    <td class="text-right font-weight-bold text-dark"><?php echo number_format((int)$lg['balance_after']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php echo lpPager($recentLogTotal, $hPage, $lpPerPage, 'hpage', 'lpHistory'); ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canAddLoyalty || $canEditLoyalty): ?>
<!-- Modal: Add / edit reward (same look as the Promotions form) -->
<div class="modal fade" id="lpRewardModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form method="POST" action="index.php">
        <input type="hidden" name="action" id="lpRewardAction" value="add_reward">
        <input type="hidden" name="reward_id" id="lpRewardId" value="">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;"><i class="fas fa-gift mr-2"></i><span id="lpRewardModalTitle"><?php echo htmlspecialchars(t('loyalty.add_reward_title', 'ເພີ່ມລາຍການແລກຄະແນນ')); ?></span></h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.col_type', 'ປະເພດ')); ?></label>
              <select name="reward_type" id="lpType" class="form-control" style="height: 42px;" onchange="lpToggleType()">
                <option value="discount"><?php echo htmlspecialchars(t('loyalty.type_discount', 'ແລກເປັນສ່ວນຫຼຸດ')); ?></option>
                <option value="product"><?php echo htmlspecialchars(t('loyalty.type_product', 'ແລກເປັນສິນຄ້າ')); ?></option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.col_points', 'ໃຊ້ຄະແນນ')); ?> <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" min="1" name="points_cost" id="lpPoints" class="form-control text-right font-weight-bold" style="height: 42px;" placeholder="10" required oninput="lpUpdatePreview()">
                <div class="input-group-append"><span class="input-group-text"><?php echo htmlspecialchars(t('loyalty.points_unit', 'ຄະແນນ')); ?></span></div>
              </div>
            </div>
          </div>

          <div class="mb-3" id="lpDiscountField">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.discount_amount_label', 'ມູນຄ່າສ່ວນຫຼຸດ (ກີບ)')); ?> <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" inputmode="numeric" name="discount_amount" id="lpAmount" class="form-control text-right font-weight-bold" style="height: 42px;" placeholder="50,000" oninput="this.value=this.value.replace(/[^0-9]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,','); lpUpdatePreview();">
              <div class="input-group-append"><span class="input-group-text">₭</span></div>
            </div>
            <label class="font-weight-bold text-dark small mb-1 mt-3"><?php echo htmlspecialchars(t('loyalty.min_spend_label', 'ຍອດຊື້ຂັ້ນຕ່ຳ ຈຶ່ງແລກໄດ້ (ກີບ)')); ?></label>
            <div class="input-group">
              <input type="text" inputmode="numeric" name="min_spend" id="lpMinSpend" class="form-control text-right font-weight-bold" style="height: 42px;" placeholder="100,000" oninput="this.value=this.value.replace(/[^0-9]/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,',');">
              <div class="input-group-append"><span class="input-group-text">₭</span></div>
            </div>
            <small class="text-muted"><?php echo htmlspecialchars(t('loyalty.min_spend_hint', 'ບໍ່ໃສ່ກໍໄດ້ — ແຕ່ຍອດຊື້ຕ້ອງຫຼາຍກວ່າມູນຄ່າສ່ວນຫຼຸດສະເໝີ')); ?></small>
          </div>

          <div class="row" id="lpProductField" style="display: none;">
            <div class="col-8 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.product_label', 'ສິນຄ້າ')); ?> <span class="text-danger">*</span></label>
              <select name="product_id" id="lpProduct" class="form-control" style="width: 100%;">
                <option value=""><?php echo htmlspecialchars(t('loyalty.product_select', '-- ເລືອກສິນຄ້າ --')); ?></option>
                <?php foreach ($rewardProducts as $rp): ?>
                  <option value="<?php echo (int)$rp['product_id']; ?>" data-name="<?php echo htmlspecialchars($rp['product_name']); ?>" data-unit="<?php echo htmlspecialchars($rp['unit'] ?? ''); ?>" data-price="<?php echo (float)$rp['price']; ?>"><?php echo htmlspecialchars($rp['product_name'] . ($rp['unit'] ? ' (' . $rp['unit'] . ')' : '')); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4 mb-3">
              <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.product_qty_label', 'ຈຳນວນ')); ?></label>
              <input type="number" min="1" name="product_qty" id="lpQty" value="1" class="form-control text-right font-weight-bold" style="height: 42px;" oninput="lpUpdatePreview()">
            </div>
          </div>

          <div class="mb-2">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('loyalty.reward_name_label', 'ຊື່ລາຍການ (ບໍ່ໃສ່ກໍໄດ້)')); ?></label>
            <input type="text" name="reward_name" id="lpName" class="form-control" style="height: 42px;" maxlength="255" placeholder="<?php echo htmlspecialchars(t('loyalty.reward_name_auto', 'ບໍ່ໃສ່ ລະບົບຈະຕັ້ງຊື່ໃຫ້ອັດຕະໂນມັດ')); ?>">
          </div>

          <small class="text-info d-block" id="lpPreviewHint"></small>
        </div>
        <div class="modal-footer border-0 bg-light p-3">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal" data-i18n-key="loyalty.cancel_btn"><?php echo htmlspecialchars(t('loyalty.cancel_btn', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('loyalty.save_btn', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

