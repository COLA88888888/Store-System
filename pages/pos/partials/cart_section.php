<!-- Right Side: POS Cart Panel -->
<div class="pos-cart shadow-sm">
  <!-- Active Bills Navigation Bar -->
  <div class="pos-active-bills-bar bg-light border-bottom p-2 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center" style="gap: 6px;">
      <?php if (hasPermission('customer_debt', 'add')): ?>
      <!-- ຂາຍຕິດໜີ້: opens the payment form straight in credit mode -->
      <button type="button" class="btn btn-sm btn-danger font-weight-bold px-3 py-1.5 shadow-sm" id="btnCreditSale" onclick="openCreditCheckout()" title="<?php echo htmlspecialchars(t('pos.credit_sale_btn_title', 'ຂາຍຕິດໜີ້ (ລູກຄ້າຈ່າຍພາຍຫຼັງ)')); ?>" style="border-radius: 8px; white-space: nowrap; font-size: 0.85rem;">
        <i class="fas fa-hand-holding-usd mr-1"></i> <?php echo htmlspecialchars(t('pos.pay_mode_credit', 'ຕິດໜີ້')); ?>
      </button>
      <?php endif; ?>
      <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold px-3 py-1.5 shadow-sm d-none d-md-inline-block" onclick="openCustomerDisplayWindow()" title="<?php echo htmlspecialchars(t('pos.customer_display_title', 'ເປີດໜ້າຈໍສະແດງຜົນລູກຄ້າ (Customer Display)')); ?>" style="border-radius: 8px; white-space: nowrap; font-size: 0.85rem; background: #ffffff;">
        <i class="fas fa-desktop mr-1"></i> <?php echo htmlspecialchars(t('pos.customer_display_btn', 'ຈໍລູກຄ້າ')); ?>
      </button>
    </div>
    <div class="d-flex align-items-center" style="gap: 6px;">
      <button type="button" class="btn btn-sm btn-success font-weight-bold px-3 py-1.5 shadow-sm" onclick="createNewBillModal()" title="<?php echo htmlspecialchars(t('pos.new_bill', 'ເປີດບິນໃໝ່')); ?>" style="border-radius: 8px; white-space: nowrap; font-size: 0.85rem;">
        <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('pos.new_bill', 'ເປີດບິນໃໝ່')); ?>
      </button>
      <button type="button" class="btn btn-sm btn-outline-warning text-dark font-weight-bold px-3 py-1.5 shadow-sm" id="btnActiveBills" onclick="openActiveBillsModal()" title="<?php echo htmlspecialchars(t('pos.active_bills_title', 'ບິນທີ່ເປີດຢູ່')); ?>" disabled style="border-radius: 8px; white-space: nowrap; font-size: 0.85rem; background: #ffffff;">
        <i class="fas fa-list-alt mr-1 text-primary"></i> <?php echo htmlspecialchars(t('pos.active_bills_title', 'ບິນທີ່ເປີດຢູ່')); ?> <span class="badge badge-dark font-weight-bold ml-1" id="activeBillsCountBadge" style="display: none;">0</span>
      </button>
    </div>
  </div>

  <!-- Customer Bar -->
  <div class="pos-customer-bar bg-white border-bottom p-2 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center pr-2" style="overflow: hidden; flex: 1;">
      <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-2" style="width: 38px; height: 38px; flex-shrink: 0; border: 1.5px solid #c7d2e8;">
        <i class="fas fa-user text-primary" style="font-size: 1.1rem;"></i>
      </div>
      <div style="overflow: hidden; min-width: 0;">
        <small class="text-muted font-weight-bold d-block" style="font-size: 0.82rem; line-height: 1.1; margin-bottom: 2px;"><?php echo htmlspecialchars(t('pos.customer_label', 'ລູກຄ້າ / ສະມາຊິກ:')); ?></small>
        <span class="font-weight-bold text-dark text-truncate d-block" style="font-size: 0.92rem; line-height: 1.2;" id="selectedCustomerDisplay"><?php echo htmlspecialchars(t('pos.customer_default', 'ລູກຄ້າທົ່ວໄປ')); ?></span>
      </div>
    </div>
    <div class="d-flex align-items-center" style="gap: 6px; flex-shrink: 0;">
      <!-- Loyalty: points balance + redeem button, shown only for a customer with points (pos_loyalty_js.php) -->
      <span id="selectedCustomerPoints" class="badge badge-pill font-weight-bold" style="display: none; background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:0.8rem; padding: 4px 8px;"><i class="fas fa-star mr-1"></i><span id="selectedCustomerPointsVal">0</span></span>
      <button class="btn btn-sm font-weight-bold px-2 py-1" type="button" id="btnRedeemPoints" onclick="openRedeemPointsModal()" style="display: none; border-radius: 8px; font-size: 0.82rem; background: #f59e0b; border-color: #d97706; color: #ffffff;" title="<?php echo htmlspecialchars(t('pos.redeem_btn', 'ແລກຄະແນນ')); ?>">
        <i class="fas fa-gift mr-1"></i><span class="d-none d-xl-inline"><?php echo htmlspecialchars(t('pos.redeem_btn', 'ແລກຄະແນນ')); ?></span>
      </button>
      <button class="btn btn-sm btn-outline-primary font-weight-bold px-2.5 py-1" type="button" onclick="openCustomerSelectModal()" style="border-radius: 8px; font-size: 0.82rem;">
        <i class="fas fa-search mr-1"></i> <?php echo htmlspecialchars(t('pos.select', 'ເລືອກ')); ?>
      </button>
      <button class="btn btn-sm btn-outline-success font-weight-bold px-2 py-1" type="button" onclick="openAddCustomerModal()" style="border-radius: 8px; font-size: 0.82rem;" title="<?php echo htmlspecialchars(t('pos.add_new_customer', 'ເພີ່ມລູກຄ້າໃໝ່')); ?>">
        <i class="fas fa-user-plus"></i>
      </button>
    </div>
  </div>

  <div class="cart-items" id="cartItemsContainer">
    <!-- Same empty state updateCartUI() (pos_cart_js.php) draws, so the panel doesn't switch
         looks the first time the cart is re-rendered (e.g. when a customer is picked). -->
    <div class="cart-empty-state d-flex flex-column align-items-center justify-content-center h-100 py-5">
      <i class="fas fa-shopping-basket text-muted mb-2" style="font-size: 2.2rem; opacity: 0.3;"></i>
      <div class="text-muted font-weight-bold" style="font-size: 0.92rem;"><?php echo htmlspecialchars(t('pos.cart_empty_title', 'ບໍ່ມີລາຍການສິນຄ້າໃນກະຕ່າ')); ?></div>
      <small class="text-muted"><?php echo htmlspecialchars(t('pos.cart_empty_hint', 'ກະລຸນາເລືອກສິນຄ້າ ຫຼື ສະແກນບາໂຄ້ດ')); ?></small>
    </div>
  </div>

  <!-- Calculations Summary & Actions -->
  <div class="cart-footer">
    <div class="d-flex justify-content-between mb-0.5 small text-muted">
      <span><?php echo htmlspecialchars(t('pos.subtotal_label', 'ລວມມູນຄ່າ:')); ?></span>
      <span class="font-weight-bold text-dark" id="cartSubtotal">0 ₭</span>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="small text-muted"><?php echo htmlspecialchars(t('pos.bill_discount_label', 'ສ່ວນຫຼຸດທ້າຍບິນ:')); ?></span>
      <div class="input-group input-group-sm" style="width: 200px; height: 26px; position: relative;">
        <input type="text" id="cartDiscount" data-mode="amount" class="form-control text-right text-danger font-weight-bold" value="0" style="height: 26px; padding: 2px 6px; font-size: 0.85rem;" oninput="formatCartDiscountInput(this); updateCartUI();">
        <!-- ໂໝດ %: ຈຳນວນເງິນທີ່ຫຼຸດແທ້ ສະແດງຢູ່ຊ້າຍໃນບັອກດຽວກັນ ເຊັ່ນ "= -390 ₭" (ຕື່ມໂດຍ cart_ui.php) -->
        <span id="cartDiscountPctHint" class="text-danger" style="display: none; position: absolute; left: 7px; top: 50%; transform: translateY(-50%); z-index: 4; pointer-events: none; font-size: 0.75rem; font-weight: 600; opacity: 0.75; white-space: nowrap;"></span>
        <!-- ເລືອກແບບສ່ວນຫຼຸດ: ກີບ (ຈຳນວນເງິນ) ຫຼື % ຂອງຍອດລວມ (pos_js.php: setCartDiscountModeAndReset) -->
        <div class="input-group-append">
          <button type="button" class="btn btn-primary font-weight-bold cart-disc-mode-btn" data-mode="amount" id="cartDiscountModeBtn" onclick="setCartDiscountModeAndReset('amount')" title="ສ່ວນຫຼຸດເປັນຈຳນວນເງິນ" style="padding: 2px 7px; font-size: 0.8rem; min-width: 28px;"><?php echo htmlspecialchars($posDisplayCurrency['symbol'] ?? '₭'); ?></button>
          <button type="button" class="btn btn-outline-secondary font-weight-bold cart-disc-mode-btn" data-mode="percent" onclick="setCartDiscountModeAndReset('percent')" title="ສ່ວນຫຼຸດເປັນເປີເຊັນ" style="padding: 2px 7px; font-size: 0.8rem; min-width: 28px;">%</button>
        </div>
      </div>
    </div>
    
    <!-- Loyalty points redeemed on this bill (discount rewards) — filled by pos_loyalty_js.php -->
    <div id="cartPointsRow" class="mb-1" style="display: none;">
      <div class="d-flex justify-content-between align-items-center small">
        <span class="font-weight-bold" style="color: #b45309;"><i class="fas fa-gift mr-1"></i><?php echo htmlspecialchars(t('pos.redeem_row_label', 'ແລກຄະແນນ')); ?> (<span id="cartPointsUsed">0</span> <?php echo htmlspecialchars(t('pos.points_unit', 'ຄະແນນ')); ?>)</span>
        <span class="font-weight-bold text-danger" id="cartPointsDiscount">0 ₭</span>
      </div>
      <div id="cartRedemptionList" style="font-size: 0.78rem;"></div>
    </div>

    <?php
      $cTaxType = !empty($company['tax_type']) ? $company['tax_type'] : 'none';
      $cVatPercent = ($cTaxType === 'none') ? 0 : (isset($company['vat_percent']) ? floatval($company['vat_percent']) : 0);
      $showCartVat = ($cVatPercent > 0 && $cTaxType !== 'none');
      $cLabelText = ($cTaxType === 'exclusive') ? (t('pos.vat_exclusive_label', 'ອມພ') . " ({$cVatPercent}%):") : (t('pos.vat_inclusive_label', 'ລວມ ອມພ') . " ({$cVatPercent}%):");
    ?>
    <div class="d-flex justify-content-between align-items-center mb-0.5 small text-muted" id="cartVatRow" style="<?php echo $showCartVat ? 'display: flex !important;' : 'display: none !important;'; ?>">
      <span id="cartVatLabel"><?php echo $cLabelText; ?></span>
      <span class="font-weight-bold text-dark" id="cartVat">0 ₭</span>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-1 pt-1 border-top">
      <span class="font-weight-bold" style="font-size: 1.0rem; color: #1e293b;"><?php echo htmlspecialchars(t('pos.net_total_label', 'ຍອດຊຳລະສຸດທິ:')); ?></span>
      <span class="font-weight-bold" style="font-size: 1.35rem; color: #16a34a;" id="cartTotal">0 ₭</span>
    </div>

    <!-- Action Buttons Bar (Single-Row Compact 4-Button Bar) -->
    <div class="pt-1 mt-1 border-top">
      <div class="d-flex align-items-center" style="gap: 5px; width: 100%;">
        <button class="btn btn-info font-weight-bold text-white d-flex align-items-center justify-content-center shadow-sm" onclick="holdCurrentOrder()" id="btnHoldOrder" style="flex: 1; min-width: 0; border-radius: 8px; font-size: 0.86rem; min-height: 38px; padding: 4px 3px; white-space: nowrap;">
          <i class="fas fa-pause-circle mr-1" style="font-size: 0.88rem !important;"></i> <span><?php echo htmlspecialchars(t('pos.hold_bill', 'ພັກບິນ')); ?></span>
        </button>
        <button class="btn btn-primary font-weight-bold text-white d-flex align-items-center justify-content-center shadow-sm" onclick="openHeldOrdersModal()" id="btnHeldOrders" style="flex: 1; min-width: 0; border-radius: 8px; font-size: 0.86rem; min-height: 38px; padding: 4px 3px; white-space: nowrap;">
          <i class="fas fa-history mr-1" style="font-size: 0.88rem !important;"></i> <span><?php echo htmlspecialchars(t('pos.held_bills', 'ບິນທີ່ພັກ')); ?></span> <span class="badge badge-light badge-hold-count text-dark font-weight-bold ml-1" id="heldCountBadge" style="font-size: 0.76rem; padding: 1px 3px;">0</span>
        </button>
        <button class="btn btn-danger font-weight-bold d-flex align-items-center justify-content-center shadow-sm" onclick="clearCart()" id="btnClearCart" style="flex: 1; min-width: 0; border-radius: 8px; font-size: 0.86rem; min-height: 38px; padding: 4px 3px; white-space: nowrap;">
          <i class="fas fa-trash-alt mr-1" style="font-size: 0.88rem !important;"></i> <span><?php echo htmlspecialchars(t('pos.clear', 'ລ້າງ')); ?></span>
        </button>
        <button class="btn btn-warning font-weight-bold text-dark shadow-sm d-flex align-items-center justify-content-center" onclick="openCheckoutModal()" id="btnCheckout" style="flex: 1.3; min-width: 0; border-radius: 8px; font-size: 0.90rem; min-height: 38px; padding: 4px 5px; white-space: nowrap; background: #eab308; border-color: #ca8a04;">
          <i class="fas fa-money-bill-wave mr-1" style="font-size: 0.94rem !important;"></i> <span><?php echo htmlspecialchars(t('pos.pay', 'ຊຳລະເງິນ')); ?></span>
        </button>
      </div>
    </div>








  </div>
</div>
