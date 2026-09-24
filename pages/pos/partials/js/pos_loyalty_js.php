<script>
// --- LOYALTY: EXCHANGE CUSTOMER POINTS FOR A REWARD ---
// The bill's redemptions live on selectedCustomer.redemptions ([{ uid, reward_id }]), so they
// travel with the bill through switching / holding / restoring exactly like the customer does,
// and are dropped when the customer changes. A product reward is also a 0-price cart line
// tagged with reward_id + reward_uid. Nothing is deducted here: the server re-prices every
// reward and takes the points inside the checkout transaction (api/pos_backend.php).

Object.assign(window.POS_RCPT_I18N, <?php echo tjson([
  'pos.redeem_need_customer'   => 'ກະລຸນາເລືອກລູກຄ້າກ່ອນ',
  'pos.redeem_no_rewards'      => 'ຍັງບໍ່ມີລາຍການແລກຄະແນນ — ຕັ້ງຄ່າໄດ້ທີ່ ຕັ້ງຄ່າ > ຄະແນນສະສົມ',
  'pos.redeem_not_enough'      => 'ຄະແນນບໍ່ພໍ',
  'pos.redeem_action'          => 'ແລກ',
  'pos.redeem_points_suffix'   => '%s ຄະແນນ',
  'pos.redeem_discount_desc'   => 'ສ່ວນຫຼຸດ %s',
  'pos.redeem_product_desc'    => '%s × %s',
  'pos.redeem_out_of_stock'    => 'ສິນຄ້າ "%s" ບໍ່ພໍໃນສາງ — ແລກບໍ່ໄດ້',
  'pos.redeem_added'           => 'ແລກຄະແນນສຳເລັດ: %s',
  'pos.redeem_badge'           => 'ແລກຄະແນນ',
  'pos.redeem_remove'          => 'ຍົກເລີກ',
  'pos.redeem_after'           => 'ຫຼັງແລກ ເຫຼືອ %s ຄະແນນ',
  'pos.redeem_min_spend'       => 'ຊື້ຂັ້ນຕ່ຳ %s',
  'pos.redeem_buy_more'        => 'ຍອດຊື້ບໍ່ພໍ',
  'pos.redeem_dropped'         => 'ຍົກເລີກການແລກ "%s" ແລ້ວ — ຍອດຊື້ຕ້ອງຢ່າງໜ້ອຍ %s (ຄະແນນບໍ່ຖືກຕັດ)',
]); ?>);

function posLoyaltyEnabled() {
  return !!(window.POS_LOYALTY && POS_LOYALTY.enabled);
}

function posRewardById(id) {
  var list = (window.POS_LOYALTY && POS_LOYALTY.rewards) || [];
  for (var i = 0; i < list.length; i++) if (list[i].id === parseInt(id, 10)) return list[i];
  return null;
}

// Points balance comes from allCustomers (refreshed after every sale), not from the copy on
// selectedCustomer, which may be an older snapshot from a held or restored bill.
function posCustomerPointsBalance() {
  if (!selectedCustomer || !selectedCustomer.customer_id) return 0;
  var c = (allCustomers || []).find(function (x) { return String(x.customer_id) === String(selectedCustomer.customer_id); });
  return c ? (parseInt(c.points, 10) || 0) : 0;
}

function posRedemptions() {
  return (selectedCustomer && Array.isArray(selectedCustomer.redemptions)) ? selectedCustomer.redemptions : [];
}

function posPointsUsed() {
  return posRedemptions().reduce(function (sum, r) {
    var rw = posRewardById(r.reward_id);
    return sum + (rw ? rw.points : 0);
  }, 0);
}

function posPointsAvailable() {
  return posCustomerPointsBalance() - posPointsUsed();
}

// Value of the items in the cart (reward lines are 0-price, so they don't count).
function posCartSubtotalKip() {
  return (cart || []).reduce(function (sum, i) { return sum + (parseFloat(i.quantity) || 0) * (parseFloat(i.unit_price) || 0); }, 0);
}

// Purchase a discount reward needs: its own minimum, and always more than the discount itself.
// Returns the smallest qualifying amount (for messages).
function posRewardMinSpend(rw) {
  return Math.max(parseFloat(rw.min_spend) || 0, (parseFloat(rw.discount_amount) || 0) + 1);
}

// Can this reward be added to the current bill on top of the discounts already redeemed?
// Product rewards have no purchase condition. Mirrors api/pos_backend.php.
function posRewardQualifies(rw, subtotal, discountAlready) {
  if (!rw || rw.type !== 'discount') return true;
  return subtotal >= (parseFloat(rw.min_spend) || 0) && subtotal > discountAlready + rw.discount_amount;
}

// KIP taken off the bill by discount rewards. Also drops any discount redemption the cart no
// longer qualifies for (e.g. items were removed after redeeming) — its points were never
// taken, since points only come off at checkout.
function posPointsDiscountKip(subtotal, manualDiscount) {
  var total = 0;
  var dropped = [];
  if (selectedCustomer && Array.isArray(selectedCustomer.redemptions)) {
    selectedCustomer.redemptions = selectedCustomer.redemptions.filter(function (r) {
      var rw = posRewardById(r.reward_id);
      if (!rw || rw.type !== 'discount') return true;
      if (!posRewardQualifies(rw, subtotal, total)) { dropped.push(rw); return false; }
      total += rw.discount_amount;
      return true;
    });
  }
  if (dropped.length && window.Swal) {
    Swal.fire({
      toast: true, position: 'top-end', icon: 'warning', timer: 3500, showConfirmButton: false,
      title: posT('pos.redeem_dropped', dropped.map(function (rw) { return rw.name; }).join(', '), POSCUR.fmt(posRewardMinSpend(dropped[0])))
    });
  }
  return Math.min(total, Math.max(0, subtotal - manualDiscount));
}

// Drop every redemption on the current bill and its reward lines from the cart.
function posDropAllRedemptions() {
  cart = cart.filter(function (i) { return !i.reward_uid; });
  if (selectedCustomer) selectedCustomer.redemptions = [];
}

// Badge + button in the customer bar, and the "ແລກຄະແນນ" row in the totals. Called from
// updateCartUI() and updateSelectedCustomerUI().
function updateLoyaltyUI() {
  // Shown for every registered customer while loyalty is on — even at 0 points or with no
  // rewards set up yet, so the cashier always sees where points live; the redeem window itself
  // explains "no rewards yet" / "not enough points" instead of the button silently vanishing.
  var hasCustomer = !!(selectedCustomer && selectedCustomer.customer_id);
  var available = posPointsAvailable();
  var show = posLoyaltyEnabled() && hasCustomer;

  $('#selectedCustomerPointsVal').text(available.toLocaleString());
  $('#selectedCustomerPoints').toggle(show);
  $('#btnRedeemPoints').toggle(show);

  var reds = posRedemptions();
  if (!reds.length) {
    $('#cartPointsRow').hide();
    $('#cartRedemptionList').empty();
    return;
  }
  $('#cartPointsUsed').text(posPointsUsed().toLocaleString());
  $('#cartPointsDiscount').html('-' + POSCUR.fmt(window.CART_POINTS_DISCOUNT_KIP || 0));
  var html = '';
  reds.forEach(function (r) {
    var rw = posRewardById(r.reward_id);
    if (!rw) return;
    html += '<div class="d-flex justify-content-between align-items-center text-muted">' +
      '<span class="text-truncate">• ' + $('<div>').text(rw.name).html() + ' (−' + rw.points.toLocaleString() + ')</span>' +
      '<a href="javascript:void(0)" class="text-danger ml-2 flex-shrink-0" onclick="removeRedemption(\'' + r.uid + '\')">' + posT('pos.redeem_remove') + '</a>' +
      '</div>';
  });
  $('#cartRedemptionList').html(html);
  $('#cartPointsRow').show();
}

function openRedeemPointsModal() {
  if (!selectedCustomer || !selectedCustomer.customer_id) {
    Swal.fire({ icon: 'warning', title: posT('pos.redeem_need_customer'), confirmButtonColor: '#f59e0b' });
    return;
  }
  renderRedeemRewardList();
  $('#redeemPointsModal').modal('show');
}

// Reward thumbnail: the product's own photo for a product reward (falls back to the tile if
// the image is missing), a coloured icon tile otherwise — same look as Settings > Loyalty.
function posRewardIconHtml(rw) {
  var isProduct = rw.type === 'product';
  var tile = '<span class="d-inline-flex align-items-center justify-content-center mr-3 flex-shrink-0" style="width:46px;height:46px;border-radius:14px;' +
    (isProduct
      ? 'background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#15803d;"><i class="fas fa-gift" style="font-size:1.25rem;"></i>'
      : 'background:linear-gradient(135deg,#fee2e2,#fecaca);color:#dc2626;"><i class="fas fa-percent" style="font-size:1.15rem;"></i>') +
    '</span>';
  var img = rw.image ? String(rw.image).split('/').pop().split('\\').pop() : '';
  if (!isProduct || !img || img === 'image.jpg') return tile;
  return '<span class="mr-3 flex-shrink-0 position-relative" style="width:46px;height:46px;">' +
    '<img src="<?php echo $base_path; ?>assets/product_img/' + encodeURIComponent(img) + '" style="width:46px;height:46px;object-fit:cover;border-radius:14px;border:1.5px solid #bbf7d0;background:#f0fdf4;" ' +
      'onerror="this.parentNode.outerHTML=posRewardIconHtml({type:\'product\'})">' +
    '<span class="position-absolute d-inline-flex align-items-center justify-content-center" style="right:-5px;bottom:-5px;width:20px;height:20px;border-radius:50%;background:#16a34a;color:#fff;border:2px solid #fff;font-size:0.6rem;"><i class="fas fa-gift"></i></span>' +
  '</span>';
}

function renderRedeemRewardList() {
  $('#redeemCustomerName').text(selectedCustomer.customer_name || '');
  var available = posPointsAvailable();
  $('#redeemAvailablePoints').text(available.toLocaleString());

  var rewards = (window.POS_LOYALTY && POS_LOYALTY.rewards) || [];
  if (!rewards.length) {
    $('#redeemRewardList').html('<div class="text-center text-muted py-4">' + posT('pos.redeem_no_rewards') + '</div>');
    return;
  }
  var html = '';
  var subtotal = posCartSubtotalKip();
  var discountAlready = posRedemptions().reduce(function (sum, r) {
    var x = posRewardById(r.reward_id);
    return sum + ((x && x.type === 'discount') ? x.discount_amount : 0);
  }, 0);
  rewards.forEach(function (rw) {
    var hasPoints = available >= rw.points;
    var qualifies = posRewardQualifies(rw, subtotal, discountAlready);
    var enough = hasPoints && qualifies;
    var desc = rw.type === 'product'
      ? posT('pos.redeem_product_desc', rw.product_name + (rw.unit ? ' (' + rw.unit + ')' : ''), rw.product_qty)
      : posT('pos.redeem_discount_desc', POSCUR.fmt(rw.discount_amount)) + ' · ' + posT('pos.redeem_min_spend', POSCUR.fmt(posRewardMinSpend(rw)));
    html += '<div class="d-flex align-items-center bg-white mb-2 p-2 px-3" style="border-radius: 12px; border: 1.5px solid ' + (enough ? '#e2e8f0' : '#f1f5f9') + '; opacity: ' + (enough ? '1' : '0.55') + ';">' +
      posRewardIconHtml(rw) +
      '<div style="flex: 1; min-width: 0;">' +
        '<div class="font-weight-bold text-dark text-truncate">' + $('<div>').text(rw.name).html() + '</div>' +
        '<small class="text-muted">' + desc + '</small>' +
      '</div>' +
      '<span class="badge badge-pill mx-2 px-2 py-1" style="background:#fef3c7;color:#b45309;border:1px solid #fde68a;font-size:0.82rem;"><i class="fas fa-star mr-1"></i>' + posT('pos.redeem_points_suffix', rw.points.toLocaleString()) + '</span>' +
      (enough
        ? '<button type="button" class="btn btn-sm font-weight-bold px-3" style="background:#f59e0b;color:#fff;border-radius:8px;" onclick="redeemReward(' + rw.id + ')">' + posT('pos.redeem_action') + '</button>'
        : '<button type="button" class="btn btn-sm btn-light font-weight-bold px-2" style="border-radius:8px;" disabled>' + posT(hasPoints ? 'pos.redeem_buy_more' : 'pos.redeem_not_enough') + '</button>') +
      '</div>';
  });
  $('#redeemRewardList').html(html);
}

function redeemReward(rewardId) {
  var rw = posRewardById(rewardId);
  if (!rw || !selectedCustomer || !selectedCustomer.customer_id) return;
  if (posPointsAvailable() < rw.points) return;
  var discountAlready = posRedemptions().reduce(function (sum, r) {
    var x = posRewardById(r.reward_id);
    return sum + ((x && x.type === 'discount') ? x.discount_amount : 0);
  }, 0);
  if (!posRewardQualifies(rw, posCartSubtotalKip(), discountAlready)) {
    Swal.fire({ icon: 'warning', title: posT('pos.redeem_min_spend', POSCUR.fmt(posRewardMinSpend(rw))), confirmButtonColor: '#f59e0b' });
    return;
  }

  if (!isBillOpened && typeof checkBillOpenedOrAlert === 'function') checkBillOpenedOrAlert();

  var uid = 'R' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
  if (rw.type === 'product') {
    if (getProductRemainingStock(rw.product_id) < rw.product_qty) {
      Swal.fire({ icon: 'error', title: posT('pos.redeem_out_of_stock', rw.product_name), confirmButtonColor: '#ef4444' });
      return;
    }
    window._cartTouchSeq = (window._cartTouchSeq || 0) + 1;
    cart.push({
      cartKey: 'REWARD_' + uid,
      product_id: rw.product_id,
      product_name: rw.product_name,
      category_id: rw.category_id,
      unit_name: rw.unit || posT('pos.unit_generic'),
      unit_price: 0,
      original_price: rw.price,
      cost_price: rw.bprice,
      multiplier: 1,
      quantity: rw.product_qty,
      image: rw.image || '',
      is_free_gift: true,
      reward_id: rw.id,
      reward_uid: uid,
      _touch: window._cartTouchSeq
    });
  }
  if (!Array.isArray(selectedCustomer.redemptions)) selectedCustomer.redemptions = [];
  selectedCustomer.redemptions.push({ uid: uid, reward_id: rw.id });

  updateCartUI();
  renderRedeemRewardList();
  Swal.fire({ icon: 'success', title: posT('pos.redeem_added', rw.name), text: posT('pos.redeem_after', posPointsAvailable().toLocaleString()), timer: 1500, showConfirmButton: false });
}

function removeRedemption(uid) {
  if (!selectedCustomer || !Array.isArray(selectedCustomer.redemptions)) return;
  selectedCustomer.redemptions = selectedCustomer.redemptions.filter(function (r) { return r.uid !== uid; });
  cart = cart.filter(function (i) { return i.reward_uid !== uid; });
  updateCartUI();
}

// After a successful sale, keep the local points balance in step with the server's.
function posApplyCustomerPointsFromCheckout(res) {
  if (!res || !res.customer_id) return;
  var c = (allCustomers || []).find(function (x) { return String(x.customer_id) === String(res.customer_id); });
  if (c && res.customer_points !== undefined) c.points = parseInt(res.customer_points, 10) || 0;
}
</script>
