<?php
/*
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງບິນທີ່ເປີດຢູ່ (ຫຼາຍບິນພ້ອມກັນ) ແລະ ບິນທີ່ພັກໄວ້ໃນໜ້າຂາຍ (ເກັບໄວ້ໃນ localStorage ຂອງ browser).
 *   - bills/held_orders.php : ບິນທີ່ພັກໄວ້ (ພັກບິນ)
 */
?>
<script>
// ຂໍ້ຄວາມບິນເປີດຢູ່ + ບິນພັກ: ອ່ານຜ່ານ posT() ຕອນວາດ (ບໍ່ຝັງ t() ໃສ່ JS) — POS_RCPT_I18N ຖືກປ່ຽນຄ່າເອງເມື່ອກົດທຸງພາສາ
// (home/partials/dashboard/js_language.php) ແລ້ວ pos:langchange ຂ້າງລຸ່ມວາດໃໝ່ ຈຶ່ງບໍ່ຕ້ອງໂຫຼດໜ້າ.
Object.assign(window.POS_RCPT_I18N, <?php echo tjson([
  'pos.customer_default' => 'ລູກຄ້າທົ່ວໄປ', 'pos.receipt_customer' => 'ລູກຄ້າ:', 'pos.cart_count_items' => 'ລາຍການ',
  'pos.cart_count_qty' => 'ຈຳນວນ', 'pos.held_no_orders' => 'ບໍ່ມີບິນທີ່ພັກໄວ້', 'pos.held_bill_label' => 'ບິນພັກ',
  'pos.held_open_now' => 'ກຳລັງເປີດຢູ່', 'pos.recall_bill' => 'ເອີ້ນຄືນບິນ', 'pos.delete' => 'ລຶບ', 'pos.cancel' => 'ຍົກເລີກ',
  'pos.held_no_match' => 'ບໍ່ພົບບິນທີ່ພັກໄວ້ຕົງກັບການຄົ້ນຫາ', 'pos.held_saved_toast' => 'ພັກບິນສຳເລັດ!',
  'pos.held_date_title' => 'ວັນທີພັກບິນ', 'pos.held_clear_all_title' => 'ລ້າງບິນພັກທັງໝົດ?',
  'pos.held_clear_all_text' => 'ບິນທີ່ພັກໄວ້ທັງໝົດ (%s ບິນ) ຈະຖືກລຶບອອກ ແລະ ກູ້ຄືນບໍ່ໄດ້!', 'pos.held_clear_all_btn' => 'ລ້າງທັງໝົດ',
  'pos.held_cleared_toast' => 'ລ້າງບິນພັກທັງໝົດແລ້ວ', 'pos.bill_no_n' => 'ບິນທີ %s', 'pos.bill_open_first_toast' => 'ເປີດບິນທີ 1 ສຳເລັດ!',
  'pos.bill_open_new_toast' => 'ເປີດບິນໃໝ່ສຳເລັດ!', 'pos.bill_not_opened' => 'ຍັງບໍ່ທັນເປີດ', 'pos.bills_none_open' => 'ບໍ່ມີບິນທີ່ເປີດຢູ່',
  'pos.bill_open_this' => 'ເປີດບິນນີ້', 'pos.bill_close_this' => 'ປິດບິນນີ້',
]); ?>);

// ຊື່ບິນ "ບິນທີ N" ເກັບໄວ້ໃນ localStorage ເປັນພາສາທີ່ສ້າງ — ສະແດງຕາມລຳດັບ + ພາສາປັດຈຸບັນແທນ
function activeBillLabel(bill) {
  var idx = activeBills.indexOf(bill);
  return idx === -1 ? (bill.name || '') : posT('pos.bill_no_n', idx + 1);
}

window.addEventListener('pos:langchange', function () {
  updateActiveBillsUI();
  if ($('#activeBillsModal').hasClass('show')) renderActiveBills();
  if ($('#heldOrdersModal').hasClass('show') && typeof renderHeldOrders === 'function') renderHeldOrders();
});

// --- ACTIVE BILLS & HELD ORDERS MANAGEMENT ---
function initActiveBills() {
  var storedOpened = localStorage.getItem('StoreMini.bill_opened');
  try {
    activeBills = JSON.parse(localStorage.getItem('StoreMini.active_bills') || '[]');
  } catch(e) { activeBills = []; }

  if (storedOpened === '1' && activeBills && activeBills.length > 0) {
    isBillOpened = true;
    currentBillId = activeBills[0].id;
    loadBillState(currentBillId);
  } else {
    isBillOpened = false;
    activeBills = [];
    currentBillId = '';
    cart = [];
    localStorage.removeItem('StoreMini.bill_opened');
    localStorage.removeItem('StoreMini.active_bills');
    // ບໍ່ມີບິນເປີດຢູ່: ຕ້ອງໃຫ້ ຊຳລະ / ລ້າງ / ພັກບິນ ຂຶ້ນສະຖານະ disabled ຕັ້ງແຕ່ໂຫຼດໜ້າ
    updateCartUI();
  }

  updateActiveBillsUI();
}

function saveCurrentBillState() {
  var idx = activeBills.findIndex(function(b) { return b.id === currentBillId; });
  if (idx !== -1) {
    activeBills[idx].cart = JSON.parse(JSON.stringify(cart));
    activeBills[idx].customer = JSON.parse(JSON.stringify(selectedCustomer));
    activeBills[idx].discount = $('#cartDiscount').val();
    activeBills[idx].discount_mode = getCartDiscountMode();
    localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));
  }
}

function loadBillState(billId) {
  var bill = activeBills.find(function(b) { return b.id === billId; });
  if (!bill) return;

  currentBillId = bill.id;
  cart = JSON.parse(JSON.stringify(bill.cart || []));
  selectedCustomer = JSON.parse(JSON.stringify(bill.customer || { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '' }));
  setCartDiscountMode(bill.discount_mode);
  $('#cartDiscount').val(bill.discount || '0');

  updateSelectedCustomerUI();
  updateCartUI();
  updateActiveBillsUI();
}

function resequenceActiveBills() {
  activeBills.forEach(function(b, idx) {
    b.name = '<?php echo tlj('ບິນທີ'); ?> ' + (idx + 1);
  });
  localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));
}

window.createNewBillModal = function() {
  saveCurrentBillState();

  if (!isBillOpened || activeBills.length === 0) {
    var newId = 'BILL-' + Date.now();
    // A customer the cashier picked before any bill was open (e.g. before scanning the first
    // item) carries into this first bill instead of being reset to the walk-in customer.
    var carriedCustomer = (selectedCustomer && selectedCustomer.customer_id)
      ? JSON.parse(JSON.stringify(selectedCustomer))
      : { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '' };
    var firstBill = {
      id: newId,
      name: '<?php echo tlj('ບິນທີ'); ?> 1',
      time: new Date().toLocaleTimeString('lo-LA', { hour: '2-digit', minute: '2-digit' }),
      customer: carriedCustomer,
      cart: [],
      discount: '0'
    };
    activeBills = [firstBill];
    isBillOpened = true;
    localStorage.setItem('StoreMini.bill_opened', '1');
    localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));
    loadBillState(newId);

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: posT('pos.bill_open_first_toast'),
      timer: 1500,
      timerProgressBar: true,
      showConfirmButton: false
    });
    return;
  }

  // ຖ້າ ບິນປັດຈຸບັນ ຍັງຫວ່າງເປົ່າ (ບໍ່ມີລາຍການ ແລະ ລູກຄ້າທົ່ວໄປ) — ໃຊ້ ບິນນັ້ນເລີຍ ບໍ່ຕ້ອງສ້າງໃໝ່
  var currentBill = activeBills.find(function(b) { return b.id === currentBillId; });
  var isCurrentEmpty = currentBill
    && (!currentBill.cart || currentBill.cart.length === 0)
    && (!currentBill.customer || !currentBill.customer.customer_id);

  if (isCurrentEmpty) {
    isBillOpened = true;
    localStorage.setItem('StoreMini.bill_opened', '1');
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: posT('pos.bill_open_new_toast'),
      timer: 1500,
      timerProgressBar: true,
      showConfirmButton: false
    });
    updateActiveBillsUI();
    return;
  }

  resequenceActiveBills();
  var billNumber = activeBills.length + 1;
  var newId = 'BILL-' + Date.now();

  var newBill = {
    id: newId,
    name: '<?php echo tlj('ບິນທີ'); ?> ' + billNumber,
    time: new Date().toLocaleTimeString('lo-LA', { hour: '2-digit', minute: '2-digit' }),
    customer: { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '' },
    cart: [],
    discount: '0'
  };

  activeBills.push(newBill);
  resequenceActiveBills();

  isBillOpened = true;
  localStorage.setItem('StoreMini.bill_opened', '1');

  loadBillState(newId);

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: posT('pos.bill_open_new_toast'),
    timer: 1500,
    timerProgressBar: true,
    showConfirmButton: false
  });
}

function updateActiveBillsUI() {
  // ຖ້າຍັງບໍ່ໄດ້ເປີດບິນ ຫຼື activeBills ຫວ່າງເປົ່າ — ສະແດງ 0 ແລະ ຍັງບໍ່ທັນເປີດ
  if (!isBillOpened || !activeBills || activeBills.length === 0) {
    $('#activeBillsCountBadge').text('0').show();
    $('#btnActiveBills').prop('disabled', true).addClass('disabled').css('opacity', '0.6');
    $('#currentBillBadge')
      .text(posT('pos.bill_not_opened'))
      .removeClass('badge-primary')
      .addClass('badge-secondary');
    return;
  }

  var openedCount = activeBills.length;

  if (openedCount > 0) {
    $('#activeBillsCountBadge').text(openedCount).show();
    $('#btnActiveBills').prop('disabled', false).removeClass('disabled').css('opacity', '1');
  } else {
    $('#activeBillsCountBadge').text('0').show();
    $('#btnActiveBills').prop('disabled', true).addClass('disabled').css('opacity', '0.6');
  }

  var currentBill = activeBills.find(function(b) { return b.id === currentBillId; });
  if (currentBill && isBillOpened) {
    $('#currentBillBadge')
      .text(activeBillLabel(currentBill))
      .removeClass('badge-secondary')
      .addClass('badge-primary');
  } else {
    $('#currentBillBadge')
      .text(posT('pos.bill_not_opened'))
      .removeClass('badge-primary')
      .addClass('badge-secondary');
  }
}

function openActiveBillsModal() {
  if ($('#btnActiveBills').is(':disabled')) return;
  saveCurrentBillState();
  $('#activeBillsSearchInput').val('');
  $('#activeBillsNoMatch').hide();
  renderActiveBills();
  $('#activeBillsModal').modal('show');
}

// Filter the rendered bill cards by name / customer / product text.
function filterActiveBillsList() {
  var q = ($('#activeBillsSearchInput').val() || '').trim().toLowerCase();
  var anyVisible = false;
  $('#activeBillsContainer .held-item-card').each(function() {
    var hit = !q || ($(this).attr('data-bill-search') || '').indexOf(q) !== -1;
    $(this).toggle(hit);
    if (hit) anyVisible = true;
  });
  $('#activeBillsNoMatch').toggle(!anyVisible && q.length > 0);
}

function renderActiveBills() {
  var container = $('#activeBillsContainer');
  container.empty();
  $('#activeBillsNoMatch').hide();
  // Search box only earns its place once there's more than one open bill.
  $('#activeBillsSearchWrap').toggle(activeBills.length > 1);

  if (activeBills.length === 0) {
    container.html(`<div class="text-center py-4 text-muted">${posT('pos.bills_none_open')}</div>`);
    return;
  }

  activeBills.forEach(function(bill) {
    var isCurrent = (bill.id === currentBillId);
    var lineCount = (bill.cart || []).length;
    var totalQty  = 0;
    var totalAmount = 0;
    var itemsListHtml = '<div class="my-2 p-2 bg-white border rounded" style="font-size: 0.84rem;">';

    (bill.cart || []).forEach(function(i) {
      totalQty += i.quantity;
      var itemTotal = i.quantity * i.unit_price;
      totalAmount += itemTotal;
      var unitStr = i.unit_name ? (' ' + i.unit_name) : '';
      itemsListHtml += `
        <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="border-color: #f1f5f9 !important;">
          <span class="text-dark font-weight-bold text-truncate" style="max-width: 180px;">• ${i.product_name}</span>
          <span class="text-nowrap">
            <span class="badge badge-light border text-primary font-weight-bold mr-1" style="font-size: 0.86rem;">x${i.quantity}${unitStr}</span>
            <strong class="text-dark">${window.POSCUR ? POSCUR.fmt(itemTotal) : itemTotal.toLocaleString() + ' <?php echo kipSymbol(); ?>'}</strong>
          </span>
        </div>
      `;
    });
    itemsListHtml += '</div>';

    var discountVal = cartDiscountKip(totalAmount, bill.discount || '0', bill.discount_mode || 'amount');
    var netTotal = Math.max(0, totalAmount - discountVal);
    var cusName = posCustomerDisplayName(bill.customer && bill.customer.customer_name);

    var cardHtml = `
      <div class="held-item-card ${isCurrent ? 'border-primary bg-light' : ''}">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <div>
            <span class="badge ${isCurrent ? 'badge-primary' : 'badge-secondary'} font-weight-bold mr-1">${activeBillLabel(bill)}</span>
            <span class="small text-muted"><i class="fas fa-clock mr-1"></i>${bill.time}</span>
          </div>
          <h5 class="font-weight-bold text-success mb-0">${window.POSCUR ? POSCUR.fmt(netTotal) : netTotal.toLocaleString() + ' <?php echo kipSymbol(); ?>'}</h5>
        </div>
        <div class="small text-muted mb-1">
          <i class="fas fa-user mr-1"></i> ${posT('pos.receipt_customer')} <strong>${cusName}</strong> |
          <i class="fas fa-shopping-basket mr-1 ml-1"></i> <strong>${lineCount}</strong> ${posT('pos.cart_count_items')} (${totalQty} ${posT('pos.cart_count_qty')})
        </div>
        ${itemsListHtml}
        <div class="d-flex justify-content-end mt-2" style="gap: 6px;">
          ${!isCurrent ? `
            <button class="btn btn-sm btn-primary font-weight-bold" onclick="selectActiveBill('${bill.id}')">
              <i class="fas fa-sign-in-alt mr-1"></i> ${posT('pos.bill_open_this')}
            </button>
          ` : '<span class="badge badge-success align-self-center px-2 py-1"><i class="fas fa-check-circle mr-1"></i>' + posT('pos.held_open_now') + '</span>'}
          ${activeBills.length > 1 ? `
            <button class="btn btn-sm btn-outline-danger" onclick="closeActiveBill('${bill.id}')" title="${posT('pos.bill_close_this')}">
              <i class="fas fa-times"></i>
            </button>
          ` : ''}
        </div>
      </div>
    `;
    container.append(cardHtml);
    // Haystack for the modal's search box: bill name + customer + every product name.
    var searchStr = (activeBillLabel(bill) + ' ' + cusName + ' ' +
      (bill.cart || []).map(function(i) { return i.product_name || ''; }).join(' ')).toLowerCase();
    container.children().last().attr('data-bill-search', searchStr);
  });

  filterActiveBillsList();
}

function selectActiveBill(billId) {
  saveCurrentBillState();
  loadBillState(billId);
  $('#activeBillsModal').modal('hide');
}

function closeActiveBill(billId) {
  if (activeBills.length <= 1) return;
  activeBills = activeBills.filter(function(b) { return b.id !== billId; });
  localStorage.setItem('StoreMini.active_bills', JSON.stringify(activeBills));

  if (currentBillId === billId) {
    currentBillId = activeBills[0].id;
    loadBillState(currentBillId);
  }
  renderActiveBills();
  updateActiveBillsUI();
}

<?php require __DIR__ . '/bills/held_orders.php'; /* ບິນທີ່ພັກໄວ້ (ພັກບິນ) */ ?>
</script>
