<script>
// --- CUSTOMER SELECTION & QUICK ADD CUSTOMER ---
function updateSelectedCustomerUI() {
  if (selectedCustomer && selectedCustomer.customer_id) {
    $('#selectedCustomerDisplay').text(selectedCustomer.customer_name);
  } else {
    $('#selectedCustomerDisplay').text(posCustomerDisplayName(''));
  }
}

function selectCustomer(cus) {
  if (cus) {
    selectedCustomer = {
      customer_id: cus.customer_id,
      customer_name: cus.customer_name,
      phone: cus.phone || '',
      address: cus.address || '',
      notes: cus.notes || ''
    };
  } else {
    selectedCustomer = { customer_id: null, customer_name: 'ລູກຄ້າທົ່ວໄປ', phone: '', address: '', notes: '' };
  }
  updateSelectedCustomerUI();
  saveCurrentBillState();
  $('#selectCustomerModal').modal('hide');
}

function openCustomerSelectModal() {
  $('#customerSearchInput').val('');
  filterCustomersList();
  $('#selectCustomerModal').modal('show');
}

function filterCustomersList() {
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

  if (filtered.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="7" class="text-center py-5 text-muted">
          <i class="fas fa-user-slash fa-2x mb-2 d-block text-secondary"></i>
          <span class="font-weight-bold d-block" style="font-size: 1.05rem; color: #64748b;">ບໍ່ມີຂໍ້ມູນລູກຄ້າ / ສະມາຊິກໃນລະບົບ</span>
        </td>
      </tr>
    `);
    return;
  }

  filtered.forEach(function(c, idx) {
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
</script>
