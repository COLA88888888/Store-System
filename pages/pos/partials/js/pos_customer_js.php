<script>
// --- CUSTOMER SELECTION & QUICK ADD CUSTOMER ---
function updateSelectedCustomerUI() {
  if (selectedCustomer && selectedCustomer.customer_id) {
    $('#selectedCustomerDisplay').text(selectedCustomer.customer_name);
  } else {
    $('#selectedCustomerDisplay').text(posCustomerDisplayName(''));
  }
  if (typeof updateLoyaltyUI === 'function') updateLoyaltyUI();
}

function selectCustomer(cus) {
  // Points redeemed on this bill belong to the customer who owned them — switching to
  // someone else drops those redemptions (and their reward lines) from the bill.
  var newId = cus ? cus.customer_id : null;
  var changed = String(newId || '') !== String((selectedCustomer && selectedCustomer.customer_id) || '');
  if (changed && typeof posDropAllRedemptions === 'function' && posRedemptions().length) {
    posDropAllRedemptions();
    updateCartUI();
  }
  var keepRedemptions = (!changed && selectedCustomer && selectedCustomer.redemptions) ? selectedCustomer.redemptions : [];
  if (cus) {
    selectedCustomer = {
      customer_id: cus.customer_id,
      customer_name: cus.customer_name,
      phone: cus.phone || '',
      address: cus.address || '',
      notes: cus.notes || '',
      redemptions: keepRedemptions
    };
  } else {
    selectedCustomer = { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '', address: '', notes: '', redemptions: [] };
  }
  updateSelectedCustomerUI();
  saveCurrentBillState();
  $('#selectCustomerModal').modal('hide');

  // Came here from the ຕິດໜີ້ button with no customer — continue to the credit payment form.
  if (window.__posCreditPending) {
    window.__posCreditPending = false;
    if (cus && typeof openCreditCheckout === 'function') setTimeout(openCreditCheckout, 450);
  }
}

function openCustomerSelectModal() {
  $('#customerSearchInput').val('');
  filterCustomersList();
  $('#selectCustomerModal').modal('show');
}

Object.assign(window.POS_RCPT_I18N, <?php echo tjson([
  'pos.pager_showing' => 'ສະແດງ %s–%s ຈາກ %s ລາຍການ',
]); ?>);

// The customer picker shows one page at a time so a shop with hundreds of members still
// opens it instantly. Typing a search always goes back to page 1.
var CUSTOMER_PAGE_SIZE = 10;
var customerListPage = 1;

function filterCustomersList(page) {
  customerListPage = page || 1;
  var q = ($('#customerSearchInput').val() || '').toLowerCase().trim();
  var tbody = $('#customerTableBody');
  tbody.empty();

  var filtered = allCustomers.filter(function(c) {
    if (!c || c.customer_code === 'CUST-001' || (c.customer_name && c.customer_name.indexOf('ລູກຄ້າທົ່ວໄປ') !== -1)) {
      return false;
    }
    var name  = (c.customer_name || '').toLowerCase();
    var phone = (c.phone || '').toLowerCase();
    var code  = (c.customer_code || '').toLowerCase();
    var card  = (c.member_card || '').toLowerCase();
    return name.indexOf(q) !== -1 || phone.indexOf(q) !== -1 || code.indexOf(q) !== -1 || card.indexOf(q) !== -1;
  });
  // Most loyalty points first. Array.sort is stable, so customers with equal points keep
  // allCustomers' own order (newest first).
  filtered.sort(function(a, b) { return (parseInt(b.points, 10) || 0) - (parseInt(a.points, 10) || 0); });

  if (filtered.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="7" class="text-center py-5 text-muted">
          <i class="fas fa-user-slash fa-2x mb-2 d-block text-secondary"></i>
          <span class="font-weight-bold d-block" style="font-size: 1.05rem; color: #64748b;">ບໍ່ມີຂໍ້ມູນລູກຄ້າ / ສະມາຊິກໃນລະບົບ</span>
        </td>
      </tr>
    `);
    renderCustomerPager(0);
    return;
  }

  var totalPages = Math.max(1, Math.ceil(filtered.length / CUSTOMER_PAGE_SIZE));
  if (customerListPage > totalPages) customerListPage = totalPages;
  var offset = (customerListPage - 1) * CUSTOMER_PAGE_SIZE;

  filtered.slice(offset, offset + CUSTOMER_PAGE_SIZE).forEach(function(c, i) {
    var idx = offset + i;
    var jsonStr = JSON.stringify(c).replace(/'/g, "&apos;");
    var createdAt = c.created_at ? new Date(c.created_at).toLocaleDateString('lo-LA') : '-';
    var memberCardBadge = c.member_card 
      ? `<span class="badge badge-pill px-2.5 py-1.5" style="font-size: 0.85rem; background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;"><i class="fas fa-id-card mr-1 text-primary"></i>${c.member_card}</span>`
      : `<span class="text-muted">-</span>`;

    tbody.append(`
      <tr style="white-space: nowrap;">
        <td class="text-center text-muted font-weight-bold align-middle" style="white-space: nowrap;">${idx + 1}</td>
        <td class="text-center align-middle font-weight-bold" style="white-space: nowrap;"><span class="cust-code-badge" style="white-space: nowrap;">${c.customer_code || '-'}</span></td>
        <td class="font-weight-bold text-dark align-middle" style="white-space: nowrap;">${c.customer_name}${(parseInt(c.points) > 0) ? ` <span class="badge badge-pill" style="background:#fef3c7;color:#b45309;border:1px solid #fde68a;font-size:0.78rem;"><i class="fas fa-star mr-1"></i>${parseInt(c.points).toLocaleString()}</span>` : ''}</td>
        <td class="align-middle text-center text-dark" style="white-space: nowrap;">${c.phone ? `<a href="tel:${c.phone}" class="text-dark font-weight-bold">${c.phone}</a>` : '<span class="text-muted">-</span>'}</td>
        <td class="text-center align-middle font-weight-bold" style="white-space: nowrap;">${memberCardBadge}</td>
        <td class="text-center align-middle text-secondary" style="font-size: 0.88rem; white-space: nowrap;"><i class="far fa-clock text-info mr-1"></i>${createdAt}</td>
        <td class="text-center align-middle" style="white-space: nowrap;">
          <button type="button" class="btn btn-sm btn-success rounded-circle shadow-sm" title="ເລືອກລູກຄ້ານີ້" onclick='selectCustomer(${jsonStr})' style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #10b981 !important; color: #ffffff !important; border: none !important; cursor: pointer;">
            <i class="fas fa-check" style="font-size: 0.9rem; color: #ffffff;"></i>
          </button>
        </td>
      </tr>
    `);
  });
  renderCustomerPager(filtered.length);
  $('#selectCustomerModal .table-responsive').scrollTop(0);
}

// "ສະແດງ 11–20 ຈາກ 57 ລາຍການ" + page buttons: first, current ±2, last, with … gaps.
function renderCustomerPager(total) {
  var pager = $('#customerPager');
  var totalPages = Math.ceil(total / CUSTOMER_PAGE_SIZE);
  if (totalPages <= 1) {
    pager.html(total > 0 ? '<small class="text-muted">' + posT('pos.pager_showing', 1, total, total) + '</small>' : '');
    return;
  }
  var cur = customerListPage;
  var from = (cur - 1) * CUSTOMER_PAGE_SIZE + 1;
  var to = Math.min(total, cur * CUSTOMER_PAGE_SIZE);

  function item(p, label, disabled, active) {
    return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
      '<a class="page-link" href="javascript:void(0)"' + (disabled || active ? '' : ' onclick="filterCustomersList(' + p + ')"') + '>' + label + '</a></li>';
  }
  var html = item(cur - 1, '<i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>', cur === 1, false);
  var start = Math.max(1, cur - 2), end = Math.min(totalPages, cur + 2);
  if (start > 1) {
    html += item(1, '1', false, false);
    if (start > 2) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
  }
  for (var p = start; p <= end; p++) html += item(p, String(p), false, p === cur);
  if (end < totalPages) {
    if (end < totalPages - 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
    html += item(totalPages, String(totalPages), false, false);
  }
  html += item(cur + 1, '<i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>', cur === totalPages, false);

  pager.html(
    '<small class="text-muted">' + posT('pos.pager_showing', from.toLocaleString(), to.toLocaleString(), total.toLocaleString()) + '</small>' +
    '<ul class="pagination pagination-sm mb-0">' + html + '</ul>'
  );
}

var matchedExistingCustomer = null;

function checkExistingCustomer() {
  var phoneVal = ($('#newCusPhone').val() || '').trim();
  var nameVal  = ($('#newCusName').val() || '').trim().toLowerCase();
  var cardVal  = ($('#newCusMemberCard').val() || '').trim().toLowerCase();

  matchedExistingCustomer = null;

  if (!phoneVal && !nameVal && !cardVal) {
    $('#existingCustomerAlert').addClass('d-none');
    return;
  }

  var cleanedPhone = phoneVal.replace(/\D/g, '');

  var found = allCustomers.find(function(c) {
    // 1. Check Phone
    if (cleanedPhone && cleanedPhone.length >= 4 && c.phone) {
      var p = c.phone.replace(/\D/g, '');
      if (p.length >= 4 && (p === cleanedPhone || p.endsWith(cleanedPhone) || cleanedPhone.endsWith(p))) {
        return true;
      }
    }
    // 2. Check Name
    if (nameVal && nameVal.length >= 3 && c.customer_name) {
      var cName = c.customer_name.toLowerCase().trim();
      if (cName === nameVal) {
        return true;
      }
    }
    // 3. Check Member Card
    if (cardVal && cardVal.length >= 3 && c.member_card) {
      var cCard = c.member_card.toLowerCase().trim();
      if (cCard === cardVal) {
        return true;
      }
    }
    return false;
  });

  if (found) {
    matchedExistingCustomer = found;
    var infoStr = found.customer_name + ' (' + (found.phone || '-') + ')';
    $('#existingCusInfoText').text(infoStr);
    $('#existingCustomerAlert').removeClass('d-none');
  } else {
    $('#existingCustomerAlert').addClass('d-none');
  }
}

$(document).on('click', '#btnSelectExistingCus', function() {
  if (matchedExistingCustomer) {
    selectCustomer(matchedExistingCustomer);
    $('#addCustomerModal').modal('hide');
    Swal.fire({
      icon: 'success',
      title: 'ເລືອກລູກຄ້າເກົ່າສຳເລັດ!',
      text: 'ເລືອກ: ' + matchedExistingCustomer.customer_name + ' ແລ້ວ',
      timer: 1500,
      showConfirmButton: false
    });
  }
});

function openAddCustomerModal() {
  matchedExistingCustomer = null;
  $('#existingCustomerAlert').addClass('d-none');
  
  var maxNum = 0;
  allCustomers.forEach(function(c) {
    if (c.customer_id) {
      var cid = parseInt(c.customer_id, 10);
      if (cid > maxNum) maxNum = cid;
    }
  });
  var nextCode = 'CUST-' + String(maxNum + 1).padStart(3, '0');

  $('#newCusCode').val(nextCode);
  $('#newCusName').val('');
  $('#newCusPhone').val('');
  $('#newCusMemberCard').val('');
  $('#newCusNotes').val('');

  // Close selectCustomerModal first to prevent overlapping modals
  $('#selectCustomerModal').modal('hide');
  setTimeout(function() {
    $('#addCustomerModal').modal('show');
    setTimeout(function() { $('#newCusPhone').focus(); }, 300);
  }, 150);
}

function submitQuickAddCustomer() {
  var phone = $('#newCusPhone').val().trim();
  var name  = $('#newCusName').val().trim();

  if (!name) {
    Swal.fire({ icon: 'warning', title: 'ແຈ້ງເຕືອນ', text: 'ກະລຸນາປ້ອນຊື່ລູກຄ້າ!' });
    return;
  }

  $.ajax({
    url: '../../api/pos_backend.php',
    type: 'POST',
    data: {
      action: 'add_customer_ajax',
      customer_code: $('#newCusCode').val().trim(),
      customer_name: name,
      phone: phone,
      address: $('#newCusAddress').val().trim(),
      member_card: $('#newCusMemberCard').val().trim(),
      notes: $('#newCusNotes').val().trim()
    },
    dataType: 'json',
    success: function(res) {
      if (res.success) {
        allCustomers.push(res.customer);
        selectCustomer(res.customer);
        $('#addCustomerModal').modal('hide');
        Swal.fire({ icon: 'success', title: 'ເພີ່ມລູກຄ້າໃໝ່ສຳເລັດ!', timer: 1200, showConfirmButton: false });
      } else {
        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: res.message });
      }
    },
    error: function() {
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ບໍ່ສາມາດບັນທຶກຂໍ້ມູນລູກຄ້າໄດ້!' });
    }
  });
}

// Closing the customer picker without choosing anyone cancels a pending ຕິດໜີ້ request
// (selectCustomer() already consumed it when a customer was chosen).
$(function () {
  $('#selectCustomerModal').on('hidden.bs.modal', function () {
    if (!$('#addCustomerModal').hasClass('show')) window.__posCreditPending = false;
  });
});
</script>
