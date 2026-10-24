<?php
/*
 * ສ່ວນ: ບິນທີ່ພັກໄວ້ (ພັກບິນ)
 * ພັກບິນ, ເອີ້ນຄືນ (ບິນຍັງຢູ່ໃນລາຍການຈົນກວ່າຈະຈ່າຍ), ລົບບິນພັກທີ່ຈ່າຍແລ້ວ, ຕົວເລກໃນປຸ່ມ, popup
 * ລາຍການ ແລະ ລຶບ/ລ້າງທັງໝົດ.
 * ບັນທຶກລົງທັງຖານຂໍ້ມູນ (MySQL: st_held_orders) ແລະ localStorage ເພື່ອໃຫ້ຄ້າງໄວ້ຕະຫຼອດບໍ່ໃຫ້ຫາຍຈັກມື້ ຈົນກວ່າຈະມາກົດຊຳລະ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_bills_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// --- HELD ORDERS (ພັກບິນ) MANAGEMENT ---
// A held bill stays in the held list until it is PAID. Recalling it only copies it into the
// cart and links the open bill to it (heldId); the link is what checkout uses to drop it once
// the sale succeeds, and what "ພັກບິນ" uses to update it instead of creating a duplicate.
var posLooseHeldId = null; // link kept here while no active bill is open

function getCurrentHeldId() {
  var bill = activeBills.find(function(b) { return b.id === currentBillId; });
  return bill ? (bill.heldId || null) : posLooseHeldId;
}

function setCurrentHeldId(heldId) {
  var bill = activeBills.find(function(b) { return b.id === currentBillId; });
  if (bill) {
    bill.heldId = heldId || null;
    localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));
  } else {
    posLooseHeldId = heldId || null;
  }
}

// ດຶງຂໍ້ມູນບິນທີ່ພັກໄວ້ຈາກຖານຂໍ້ມູນ (Database Sync) ເພື່ອໃຫ້ຄ້າງໄວ້ທຸກມື້ ບໍ່ໃຫ້ຫາຍ
function loadHeldOrdersFromServer(callback) {
  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php') + '?action=list_held_orders',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res && res.success && Array.isArray(res.held_orders)) {
        heldOrders = res.held_orders;
        try {
          localStorage.setItem('StoreMini.held_orders', JSON.stringify(heldOrders));
        } catch(e) {}
        updateHeldOrdersBadge();
        if (typeof renderHeldOrders === 'function' && $('#heldOrdersModal').is(':visible')) {
          renderHeldOrders();
        }
      }
      if (typeof callback === 'function') callback();
    },
    error: function() {
      // ກໍລະນີອອຟລາຍ/ເຄືອຂ່າຍຫຼຸດ: ດຶງຈາກ localStorage ສຳຮອງ
      try {
        heldOrders = JSON.parse(localStorage.getItem('StoreMini.held_orders') || '[]');
      } catch(e) { heldOrders = []; }
      updateHeldOrdersBadge();
      if (typeof callback === 'function') callback();
    }
  });
}

// Called from checkout success: the recalled held bill has now been paid.
function removePaidHeldOrder() {
  var heldId = getCurrentHeldId();
  if (!heldId) return;

  // ລົບອອກຈາກ Local Memory & Storage
  heldOrders = heldOrders.filter(function(o) { return o.id !== heldId; });
  try {
    localStorage.setItem('StoreMini.held_orders', JSON.stringify(heldOrders));
  } catch(e) {}
  setCurrentHeldId(null);
  updateHeldOrdersBadge();

  // ອັບເດດສະຖານະໃນຖານຂໍ້ມູນເປັນ paid
  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php') + '?action=mark_paid_held_order',
    type: 'POST',
    data: { id: heldId },
    dataType: 'json'
  });
}

function holdCurrentCart() {
  holdCurrentOrder();
}

function holdCurrentOrder() {
  // ກະຕ່າຫວ່າງ: ບໍ່ເຮັດຫຍັງ ບໍ່ຕ້ອງມີ popup
  if (cart.length === 0) return;

  var now = new Date();
  var y = now.getFullYear();
  var m = String(now.getMonth() + 1).padStart(2, '0');
  var d = String(now.getDate()).padStart(2, '0');
  var dateStr = y + '-' + m + '-' + d;
  var timeStr = now.toLocaleTimeString('lo-LA', { hour: '2-digit', minute: '2-digit' });

  var snapshot = {
    cart: JSON.parse(JSON.stringify(cart)),
    customer: JSON.parse(JSON.stringify(selectedCustomer)),
    discount: $('#cartDiscount').val(),
    discount_mode: getCartDiscountMode()
  };

  var targetOrder = null;
  // Re-holding a recalled bill updates it in place (keeps its number and original date/time).
  var linkedId = getCurrentHeldId();
  var existing = linkedId ? heldOrders.find(function(o) { return o.id === linkedId; }) : null;
  if (existing) {
    Object.assign(existing, snapshot);
    targetOrder = existing;
  } else {
    targetOrder = Object.assign({
      id: 'HOLD-' + Date.now() + '-' + Math.floor(Math.random() * 900 + 100),
      date: dateStr,
      time: timeStr
    }, snapshot);
    heldOrders.push(targetOrder);
  }

  // ບັນທຶກໃນ LocalStorage ທັນທີ
  try {
    localStorage.setItem('StoreMini.held_orders', JSON.stringify(heldOrders));
  } catch(e) {}
  setCurrentHeldId(null);

  // ບັນທຶກລົງຖານຂໍ້ມູນ MySQL ຢ່າງຖາວອນ (Permanent Database Storage)
  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php') + '?action=save_held_order',
    type: 'POST',
    data: JSON.stringify(targetOrder),
    contentType: 'application/json',
    dataType: 'json'
  });

  cart = [];
  selectedCustomer = { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '' };
  setCartDiscountMode('amount');
  $('#cartDiscount').val('0');

  updateSelectedCustomerUI();
  updateCartUI();
  saveCurrentBillState();
  updateHeldOrdersBadge();

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: 'ພັກບິນສຳເລັດ!',
    timer: 1200,
    showConfirmButton: false
  });
}

function updateHeldOrdersBadge() {
  var count = heldOrders.length;
  if (count > 0) {
    $('#heldCountBadge').text(count).show();
    $('#btnHeldOrders').prop('disabled', false).removeClass('disabled').css('opacity', '1');
  } else {
    $('#heldCountBadge').hide().text('0');
    $('#btnHeldOrders').prop('disabled', true).addClass('disabled').css('opacity', '0.6');
  }
}

function openHeldOrdersModal() {
  if ($('#btnHeldOrders').is(':disabled') && heldOrders.length === 0) return;
  $('#heldOrdersSearch').val('');
  // ດຶງຂໍ້ມູນຫຼ້າສຸດຈາກ Server ກ່ອນເປີດ modal
  loadHeldOrdersFromServer(function() {
    renderHeldOrders();
  });
  $('#heldOrdersModal').modal('show');
}

function renderHeldOrders() {
  var container = $('#heldOrdersContainer');
  container.empty();

  if (heldOrders.length === 0) {
    container.html(`<div class="text-center py-4 text-muted"><?php echo htmlspecialchars(t('pos.held_no_orders', 'ບໍ່ມີບິນທີ່ພັກໄວ້'), ENT_QUOTES); ?></div>`);
    return;
  }

  var heldSearchQ = ($('#heldOrdersSearch').val() || '').toLowerCase().trim();
  var heldShownCount = 0;
  var openHeldId = getCurrentHeldId();

  heldOrders.forEach(function(order, idx) {
    // Match against bill number, date, time, customer name/phone and every product name
    var haystack = ['#' + (idx + 1), order.date || '', order.time || '',
      (order.customer && order.customer.customer_name) || '',
      (order.customer && order.customer.phone) || ''];
    (order.cart || []).forEach(function(ci) { haystack.push(ci.product_name || ''); });
    if (heldSearchQ && haystack.join(' ').toLowerCase().indexOf(heldSearchQ) === -1) return;
    heldShownCount++;

    var lineCount = (order.cart || []).length;
    var totalQty  = 0;
    var totalAmount = 0;
    var itemsListHtml = '<div class="my-2 p-2 bg-white border rounded" style="font-size: 0.84rem;">';

    (order.cart || []).forEach(function(i) {
      totalQty += i.quantity;
      var itemTotal = i.quantity * i.unit_price;
      totalAmount += itemTotal;
      var unitStr = i.unit_name ? (' ' + i.unit_name) : '';
      itemsListHtml += `
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="border-color: #f1f5f9 !important;">
          <span class="text-dark font-weight-bold text-truncate" style="max-width: 220px;">• ${i.product_name}</span>
          <span class="text-nowrap">
            <span class="badge badge-light border text-primary font-weight-bold mr-1" style="font-size: 0.86rem;">x${i.quantity}${unitStr}</span>
            <strong class="text-dark">${window.POSCUR ? POSCUR.fmt(itemTotal) : itemTotal.toLocaleString() + ' ₭'}</strong>
          </span>
        </div>
      `;
    });
    itemsListHtml += '</div>';

    var discountVal = cartDiscountKip(totalAmount, order.discount || '0', order.discount_mode || 'amount');
    var netTotal = Math.max(0, totalAmount - discountVal);

    var dateBadgeHtml = '';
    if (order.date) {
      var dParts = order.date.split('-');
      var dFormatted = (dParts.length === 3) ? (dParts[2] + '/' + dParts[1] + '/' + dParts[0]) : order.date;
      dateBadgeHtml = `<span class="badge badge-light border text-dark font-weight-bold mr-1" title="ວັນທີພັກບິນ"><i class="fas fa-calendar-alt text-primary mr-1"></i>${dFormatted}</span>`;
    }

    var isOpenNow = order.id === openHeldId;
    var cardHtml = `
      <div class="held-item-card ${isOpenNow ? 'border-primary bg-light' : ''}">
        <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap" style="gap: 6px;">
          <div class="d-flex align-items-center flex-wrap" style="gap: 4px;">
            <span class="badge badge-warning font-weight-bold"><?php echo htmlspecialchars(t('pos.held_bill_label', 'ບິນພັກ'), ENT_QUOTES); ?> #${idx + 1}</span>
            ${isOpenNow ? '<span class="badge badge-primary font-weight-bold"><?php echo htmlspecialchars(t('pos.held_open_now', 'ກຳລັງເປີດຢູ່'), ENT_QUOTES); ?></span>' : ''}
            ${dateBadgeHtml}
            <span class="small text-muted"><i class="fas fa-clock mr-1"></i>${order.time}</span>
          </div>
          <h5 class="font-weight-bold text-success mb-0">${window.POSCUR ? POSCUR.fmt(netTotal) : netTotal.toLocaleString() + ' ₭'}</h5>
        </div>
        <div class="small text-muted mb-1">
          <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars(t('pos.receipt_customer', 'ລູກຄ້າ:'), ENT_QUOTES); ?> <strong>${order.customer.customer_name}</strong> |
          <i class="fas fa-shopping-basket mr-1 ml-1"></i> <strong>${lineCount}</strong> <?php echo htmlspecialchars(t('pos.cart_count_items', 'ລາຍການ'), ENT_QUOTES); ?> (${totalQty} <?php echo htmlspecialchars(t('pos.cart_count_qty', 'ຈຳນວນ'), ENT_QUOTES); ?>)
        </div>
        ${itemsListHtml}
        <div class="d-flex justify-content-end mt-2" style="gap: 6px;">
          <button class="btn btn-sm btn-success font-weight-bold" onclick="restoreHeldOrder('${order.id}')">
            <i class="fas fa-undo mr-1"></i> <?php echo htmlspecialchars(t('pos.recall_bill', 'ເອີ້ນຄືນບິນ'), ENT_QUOTES); ?>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteHeldOrder('${order.id}')" title="<?php echo htmlspecialchars(t('pos.delete', 'ລຶບ'), ENT_QUOTES); ?>">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
      </div>
    `;
    container.append(cardHtml);
  });

  if (heldShownCount === 0) {
    container.html(`<div class="text-center py-4 text-muted"><?php echo htmlspecialchars(t('pos.held_no_match', 'ບໍ່ພົບບິນທີ່ພັກໄວ້ຕົງກັບການຄົ້ນຫາ'), ENT_QUOTES); ?></div>`);
  }
}

function restoreHeldOrder(orderId) {
  var idx = heldOrders.findIndex(function(o) { return o.id === orderId; });
  if (idx === -1) return;

  var order = heldOrders[idx];
  cart = JSON.parse(JSON.stringify(order.cart));
  selectedCustomer = JSON.parse(JSON.stringify(order.customer));
  setCartDiscountMode(order.discount_mode);
  $('#cartDiscount').val(order.discount || '0');

  // Stays in heldOrders until paid — just link the open bill to it.
  setCurrentHeldId(order.id);

  updateSelectedCustomerUI();
  updateCartUI();
  saveCurrentBillState();
  updateHeldOrdersBadge();

  $('#heldOrdersModal').modal('hide');
}

function deleteHeldOrder(orderId) {
  if (getCurrentHeldId() === orderId) setCurrentHeldId(null);
  heldOrders = heldOrders.filter(function(o) { return o.id !== orderId; });
  try {
    localStorage.setItem('StoreMini.held_orders', JSON.stringify(heldOrders));
  } catch(e) {}
  renderHeldOrders();
  updateHeldOrdersBadge();

  // ລົບອອກຈາກຖານຂໍ້ມູນ MySQL
  $.ajax({
    url: (window.POS_BACKEND_URL || '../../api/pos_backend.php') + '?action=delete_held_order',
    type: 'POST',
    data: { id: orderId },
    dataType: 'json'
  });
}

// ລ້າງບິນພັກທັງໝົດ — ເປັນການລົບຫຼາຍລາຍການພ້ອມກັນ ຈຶ່ງມີ popup ຢືນຢັນກ່ອນ
function clearAllHeldOrders() {
  if (!heldOrders || heldOrders.length === 0) return;
  Swal.fire({
    title: 'ລ້າງບິນພັກທັງໝົດ?',
    text: 'ບິນທີ່ພັກໄວ້ທັງໝົດ (' + heldOrders.length + ' ບິນ) ຈະຖືກລຶບອອກ ແລະ ກູ້ຄືນບໍ່ໄດ້!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ລ້າງທັງໝົດ',
    cancelButtonText: 'ຍົກເລີກ' 
  }).then(function(result) {
    if (!result.isConfirmed) return;
    heldOrders = [];
    try {
      localStorage.setItem('StoreMini.held_orders', JSON.stringify(heldOrders));
    } catch(e) {}
    setCurrentHeldId(null);
    renderHeldOrders();
    updateHeldOrdersBadge();
    $('#heldOrdersModal').modal('hide');

    // ລ້າງອອກຈາກຖານຂໍ້ມູນ MySQL
    $.ajax({
      url: (window.POS_BACKEND_URL || '../../api/pos_backend.php') + '?action=clear_all_held_orders',
      type: 'POST',
      dataType: 'json'
    });

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'ລ້າງບິນພັກທັງໝົດແລ້ວ',
      timer: 1200,
      showConfirmButton: false
    });
  });
}
