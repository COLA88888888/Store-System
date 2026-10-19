<?php
/*
 * ສ່ວນ: ສະແດງກະຕ່າ
 * updateCartUI: ແຕ້ມລາຍການໃນກະຕ່າ, ສ່ວນຫຼຸດ, ລາງວັນຄະແນນ, ຍອດລວມ ແລະ ເປີດ/ປິດປຸ່ມຊຳລະ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_cart_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// --- CART & ITEM MANAGEMENT ---
function updateCartUI() {
  var container = $('#cartItemsContainer');
  container.empty();

  // An emptied cart is no longer a recalled held bill (it stays in the held list, unpaid).
  if (cart.length === 0 && typeof getCurrentHeldId === 'function' && getCurrentHeldId()) setCurrentHeldId(null);

  if (cart.length === 0) {
    container.html(`
      <div class="cart-empty-state d-flex flex-column align-items-center justify-content-center h-100 py-5">
        <i class="fas fa-shopping-basket text-muted mb-2" style="font-size: 2.2rem; opacity: 0.3;"></i>
        <div class="text-muted font-weight-bold" style="font-size: 0.92rem;"><?php echo htmlspecialchars(t('pos.cart_empty_title', 'ບໍ່ມີລາຍການສິນຄ້າໃນກະຕ່າ'), ENT_QUOTES); ?></div>
        <small class="text-muted"><?php echo htmlspecialchars(t('pos.cart_empty_hint', 'ກະລຸນາເລືອກສິນຄ້າ ຫຼື ສະແກນບາໂຄ້ດ'), ENT_QUOTES); ?></small>
      </div>
    `);
    window.CART_SUBTOTAL_KIP = 0;
    window.CART_DISCOUNT_KIP = POSCUR.toKip($('#cartDiscount').val() || '0');
    // An empty cart can't meet any discount reward's minimum purchase — drops those redemptions.
    window.CART_POINTS_DISCOUNT_KIP = (typeof posPointsDiscountKip === 'function') ? posPointsDiscountKip(0, 0) : 0;
    window.CART_VAT_KIP = 0;
    window.CART_NET_KIP = 0;
    if (typeof updateLoyaltyUI === 'function') updateLoyaltyUI();
    $('#cartItemCountBadge').text(cartCountText(0, 0));
    $('#cartSubtotal').html(POSCUR.fmt(0));
    $('#cartVat').html(POSCUR.fmt(0));
    $('#cartTotal').html(POSCUR.fmt(0));
    $('#mobileCartCountBadge').text('0').hide();
    $('#floatingCartItemCount').text(cartCountText(0, 0));
    $('#floatingCartTotal').html(POSCUR.fmt(0));
    $('#mobileFloatingCartBar').hide();
    $('#btnCheckout, #btnCheckoutFloating').prop('disabled', true).addClass('disabled');
    $('#btnClearCart').prop('disabled', true).addClass('disabled');
    $('#btnHoldOrder, #btnHoldCart').prop('disabled', true).addClass('disabled');
    updateProductGridBadges();
    saveCurrentBillState();
    broadcastCustomerDisplay('shopping');
    return;
  }

  var subtotal = 0;
  var totalItemsCount = 0;

  cart.forEach(function(item, idx) {
    var itemTotal = item.quantity * item.unit_price;
    subtotal += itemTotal;
    totalItemsCount += item.quantity;

    var rawImg = item.image || item.img_url || '';
    var imgName = rawImg ? rawImg.split('/').pop().split('\\').pop() : 'image.jpg';
    if (!imgName || imgName === 'image.jpg') {
      var imgPath = '<?php echo $base_path; ?>assets/img/image.jpg';
    } else {
      var imgPath = '<?php echo $base_path; ?>assets/product_img/' + imgName;
    }

    var isGift = !!item.is_free_gift;
    var giftBadge = item.reward_uid
      ? `<span class="badge font-weight-bold ml-1" style="font-size:0.78rem; padding:2px 6px; border-radius:4px; background:#f59e0b; color:#fff;"><i class="fas fa-star mr-1"></i>${posT('pos.redeem_badge')}</span>`
      : (isGift ? `<span class="badge badge-success font-weight-bold ml-1" style="font-size:0.78rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-gift mr-1"></i>ແຖມຟຣີ</span>` : '');
    var priceDisplay = isGift
      ? `<span class="text-success font-weight-bold" style="font-size:0.80rem;">${POSCUR.fmt(0)}</span>`
      : `${POSCUR.fmt(item.unit_price)}`;

    // ໂຄງແຖວດຽວກັບ Pharmacy: ຈໍໃຫຍ່ = ແຖວດຽວ; ໂທລະສັບ = ຊື່+ຫົວໜ່ວຍ ແຖວເທິງ, [🗑/− ຈຳນວນ +] ແລະ ລາຄາ ແຖວລຸ່ມ
    // (CSS: .cart-item-* ໃນ themes/pos/04-cart-items.css).
    var isQtyOne = (item.quantity <= 1);
    var trashSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
    var minusSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
    var plusSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1e293b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
    // ໂທລະສັບ: ຈຳນວນ 1 ແລ້ວກົດຊ້າຍ = ລຶບອອກ (ໄອຄອນຖັງຂີ້ເຫຍື້ອ); ຈໍໃຫຍ່ ຍັງເປັນປຸ່ມ − ປົກກະຕິ
    var minusActionMobile = isQtyOne ? `removeFromCart('${item.cartKey}')` : `incCartQty('${item.cartKey}', -1)`;
    var unitText = item.unit_name ? `<span class="cart-item-unit-spec">${item.unit_name}</span>` : '';
    var totalNum = isGift ? POSCUR.num(0) : POSCUR.num(itemTotal);

    var rowHtml = `
      <div class="cart-item-row" data-cart-key="${item.cartKey}" style="background:${isGift ? '#f0fdf4' : '#ffffff'};${isGift ? ' border-color:#86efac;' : ''}">

        <!-- 1. ຮູບພາບ -->
        <div class="cart-item-img-box">
          <img src="${imgPath}" class="cart-item-img" onerror="this.src='<?php echo $base_path; ?>assets/img/image.jpg';">
        </div>

        <!-- 2. ລາຍລະອຽດ & ປຸ່ມຄວບຄຸມ -->
        <div class="cart-item-content">
          <div class="cart-item-name-wrap">
            <div class="cart-item-title font-weight-bold" title="${item.product_name}">${item.product_name} ${unitText} ${giftBadge}</div>
            <div class="cart-item-desktop-sub">
              <span class="cart-item-unit-badge">${item.unit_name}</span>
              <span class="cart-item-price-inline font-weight-bold">${priceDisplay}</span>
            </div>
          </div>

          <div class="cart-item-actions-wrap">
            <div class="cart-item-qty-pill">
              <button onclick="if (window.innerWidth < 768) { ${minusActionMobile}; } else { incCartQty('${item.cartKey}', -1); }" title="${isQtyOne ? 'ລຶບ' : 'ຫຼຸດ'}" class="btn-qty-minus">
                <span class="d-md-none">${isQtyOne ? trashSvg : minusSvg}</span>
                <i class="fas fa-minus d-none d-md-inline"></i>
              </button>
              <input type="number" min="1" value="${item.quantity}" onchange="changeCartQty('${item.cartKey}', this.value)" class="cart-qty-val" ${isGift ? 'readonly' : ''}>
              <button onclick="incCartQty('${item.cartKey}', 1)" title="ເພີ່ມ" class="btn-qty-plus" ${isGift ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
                <span class="d-md-none">${plusSvg}</span>
                <i class="fas fa-plus d-none d-md-inline"></i>
              </button>
            </div>

            <div class="cart-item-total-wrap">
              <span class="cart-item-currency-prefix">${POSCUR.symbol}</span><span class="cart-item-total-val">${totalNum}</span><span class="cart-item-currency-suffix"> <span class="cur-sym">${POSCUR.symbol}</span></span>
            </div>

            <!-- ປຸ່ມລົບ (ສະເພາະຈໍໃຫຍ່) -->
            <button class="cart-item-del-btn" onclick="removeFromCart('${item.cartKey}')" title="ລຶບ">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>

      </div>
    `;
    container.append(rowHtml);
  });

  <?php
    $storeTaxType = !empty($company['tax_type']) ? $company['tax_type'] : 'none';
    $storeVatPercent = ($storeTaxType === 'none') ? 0 : (isset($company['vat_percent']) ? floatval($company['vat_percent']) : 0);
  ?>
  window.STORE_TAX_TYPE = '<?php echo $storeTaxType; ?>';
  window.STORE_VAT_PERCENT = <?php echo $storeVatPercent; ?>;
  window.STORE_TAX_ID = '<?php echo htmlspecialchars($company['tax_id'] ?? ''); ?>';
  // Discount is typed in the display currency; convert it to KIP for all math below.
  var discountVal  = POSCUR.toKip($('#cartDiscount').val() || '0');
  // Discount rewards bought with loyalty points come off after the cashier's own discount.
  var pointsDiscountVal = (typeof posPointsDiscountKip === 'function') ? posPointsDiscountKip(subtotal, discountVal) : 0;
  var taxType      = window.STORE_TAX_TYPE;
  var vatPercent   = window.STORE_VAT_PERCENT;
  var amtAfterDisc = Math.max(0, subtotal - discountVal - pointsDiscountVal);
  var vatVal       = 0;
  var netTotal     = amtAfterDisc;

  if (vatPercent > 0 && taxType !== 'none') {
    if (taxType === 'exclusive') {
      // Rate% is the tax's share of the FINAL total, not of the net price — see api/pos_backend.php.
      vatVal = (vatPercent < 100) ? Math.round(amtAfterDisc * (vatPercent / (100 - vatPercent)) * 100) / 100 : 0;
      netTotal = amtAfterDisc + vatVal;
      $('#cartVatRow').attr('style', 'display: flex !important;');
      $('#cartVatLabel').text('ອມພ (' + vatPercent + '%):');
      $('#cartVat').html(posFmtTotal(vatVal));
    } else {
      vatVal = Math.round((amtAfterDisc - (amtAfterDisc / (1 + (vatPercent / 100)))) * 100) / 100;
      netTotal = amtAfterDisc;
      $('#cartVatRow').attr('style', 'display: flex !important;');
      $('#cartVatLabel').text('ລວມ ອມພ (' + vatPercent + '%):');
      $('#cartVat').html(posFmtTotal(vatVal));
    }
  } else {
    vatVal = 0;
    netTotal = amtAfterDisc;
    $('#cartVatRow').attr('style', 'display: none !important;');
  }

  var lineCount = cart.length;
  var countText = cartCountText(lineCount, totalItemsCount);

  // Source-of-truth totals in KIP for the checkout modal to read (no DOM scraping).
  window.CART_SUBTOTAL_KIP = subtotal;
  window.CART_DISCOUNT_KIP = discountVal;   // cashier's discount only — the server adds the points part
  window.CART_POINTS_DISCOUNT_KIP = pointsDiscountVal;
  window.CART_VAT_KIP = vatVal;
  window.CART_NET_KIP = netTotal;
  if (typeof updateLoyaltyUI === 'function') updateLoyaltyUI();

  $('#cartItemCountBadge').text(countText);
  $('#cartSubtotal').html(POSCUR.fmt(subtotal));
  $('#cartTotal').html(posFmtTotal(netTotal));

  if (totalItemsCount > 0) {
    $('#mobileCartCountBadge').text(lineCount).css('display', 'inline-block');
    $('#floatingCartItemCount').text(countText);
    $('#floatingCartTotal').html(posFmtTotal(netTotal));
    if (window.innerWidth < 992 && $('#mobileTabProductsBtn').hasClass('active')) {
      $('#mobileFloatingCartBar').fadeIn(150);
    }
  } else {
    $('#mobileCartCountBadge').text('0').hide();
    $('#mobileFloatingCartBar').hide();
  }

  $('#btnCheckout, #btnCheckoutFloating').prop('disabled', false).removeClass('disabled');
  $('#btnClearCart').prop('disabled', false).removeClass('disabled');
  $('#btnHoldOrder, #btnHoldCart').prop('disabled', false).removeClass('disabled');

  updateProductGridBadges();
  saveCurrentBillState();
  broadcastCustomerDisplay('shopping');
}

