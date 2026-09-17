// Real-Time Polling for User Online/Offline Status (no page refresh required)
function pollLiveUserStatuses() {
  var apiUrl = typeof API_URL !== 'undefined' ? API_URL : 'api_user.php';
  $.ajax({
    url: apiUrl + '?action=get_live_user_statuses',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && Array.isArray(res.users)) {
        var i18n = typeof I18N_USERS !== 'undefined' ? I18N_USERS : {};
        var onlineText = i18n['users_manage.status_online'] || 'ໃຊ້ງານຢູ່';
        var offlineText = i18n['users_manage.status_offline'] || 'ບໍ່ໄດ້ໃຊ້ງານ';
        res.users.forEach(function(u) {
          var statusTd = $('td.user-online-status-td[data-user-id="' + u.id + '"]');
          if (statusTd.length > 0) {
            var html = '';
            if (u.is_online) {
              html = '<span class="badge badge-success px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + onlineText + '</span>';
            } else {
              html = '<span class="badge badge-danger px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + offlineText + '</span>';
            }
            if (statusTd.html().trim() !== html) {
              statusTd.html(html);
            }
          }
        });
      }
    }
  });
}

$(document).ready(function() {
  // Start Real-Time Live Polling (Every 3 seconds)
  pollLiveUserStatuses();
  setInterval(pollLiveUserStatuses, 3000);

  $('#addUserModal').on('show.bs.modal', function() {
    removeAvatar('add_profile_img', 'add_avatar_preview', 'btn_remove_add_avatar');
    // ລະຫັດຜູ້ນຳໃຊ້: ປ້ອນດ້ວຍຕົນເອງ
    var codeEl = document.getElementById('add_user_code');
    if (codeEl) { codeEl.value = ''; }
  });

  // Live Real-Time Search Filter
  $('#userSearchInput').on('keyup input', function() {
    var val = $(this).val().toLowerCase().trim();
    $('table tbody tr').each(function() {
      var text = $(this).text().toLowerCase();
      if (text.indexOf(val) !== -1) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });
});

// Auto-generate ລະຫັດຜູ້ໃຊ້ (USR-XXX)
function fetchNextUserCode() {
  var apiUrl = typeof API_URL !== 'undefined' ? API_URL : 'api_user.php';
  var codeEl = document.getElementById('add_user_code');
  if (!codeEl) return;

  codeEl.placeholder = 'ກຳລັງສ້າງ...';
  codeEl.style.color = '#94a3b8';

  $.ajax({
    url: apiUrl + '?action=get_next_user_code',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && res.code) {
        codeEl.value = res.code;
        codeEl.style.color = '#2563eb';
      } else {
        codeEl.value = 'USR-001';
        codeEl.style.color = '#2563eb';
      }
    },
    error: function() {
      codeEl.value = 'USR-001';
      codeEl.style.color = '#2563eb';
    }
  });
}


// Avatar Live Preview & Remove
function previewAvatar(input, previewId, removeBtnId) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById(previewId).src = e.target.result;
      if (removeBtnId) {
        var removeBtn = document.getElementById(removeBtnId);
        if (removeBtn) removeBtn.style.display = 'flex';
      }
    }
    reader.readAsDataURL(input.files[0]);
    if (previewId === 'edit_avatar_preview') {
      var flag = document.getElementById('remove_profile_img_flag');
      if (flag) flag.value = "0";
    }
  }
}

function removeAvatar(inputId, previewId, removeBtnId) {
  var input = document.getElementById(inputId);
  if (input) input.value = '';
  
  var preview = document.getElementById(previewId);
  if (preview) {
    var basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '../../';
    preview.src = basePath + 'assets/img/users/default.png';
  }
  
  var removeBtn = document.getElementById(removeBtnId);
  if (removeBtn) {
    removeBtn.style.display = 'none';
  }
  
  if (previewId === 'edit_avatar_preview') {
    var flag = document.getElementById('remove_profile_img_flag');
    if (flag) flag.value = "1";
  }
}

// Toggle Password Visibility
function togglePassVisibility(inputId, btn) {
  var input = document.getElementById(inputId);
  if (!input) return;
  var icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    }
  } else {
    input.type = 'password';
    if (icon) {
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }
}

// SweetAlert2 Form Validation
function validateUserForm(formId) {
  var form = document.getElementById(formId);
  if (!form) return true;

  var user_code = (form.querySelector('[name="user_code"]')?.value || '').trim();
  var fname = (form.querySelector('[name="fname"]')?.value || '').trim();
  var password = (form.querySelector('[name="password"]')?.value || '').trim();
  var confirm_password = (form.querySelector('[name="confirm_password"]')?.value || '').trim();
  var dob = (form.querySelector('[name="dob"]')?.value || '').trim();
  var address = (form.querySelector('[name="address"]')?.value || '').trim();
  var tel = (form.querySelector('[name="tel"]')?.value || '').trim();


  var missingFields = [];

  if (!user_code) missingFields.push('ລະຫັດຜູ້ນຳໃຊ້');
  if (!fname) missingFields.push('ຊື່');
  
  if (formId === 'addUserForm') {
    if (!password) missingFields.push('ລະຫັດຜ່ານ');
  }

  if (!dob) missingFields.push('ວັນເດືອນປີເກີດ');
  if (!tel) missingFields.push('ເບີໂທ');

  if (missingFields.length > 0) {
    var fieldListStr = missingFields.join(', ');
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: 'ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ!',
        html: '<div style="font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', sans-serif;">ທ່ານຍັງບໍ່ທັນໄດ້ປ້ອນຂໍ້ມູນ: <strong class="text-danger">' + fieldListStr + '</strong><br><span class="text-muted mt-2 d-inline-block" style="font-size:0.9rem;">ກະລຸນາກວດສອບ ແລະ ປ້ອນຂໍ້ມູນທີ່ມີເຄື່ອງໝາຍ (<span class="text-danger">*</span>) ໃຫ້ຄົບຖ້ວນ</span></div>',
        confirmButtonText: 'ຕົກລົງ',
        confirmButtonColor: '#0284c7'
      });
    } else {
      alert('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ: ' + fieldListStr);
    }
    return false;
  }

  if (password || confirm_password) {
    if (password !== confirm_password) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'ລະຫັດຜ່ານບໍ່ກົງກັນ!',
          html: '<div style="font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', sans-serif;">ກະລຸນາກວດສອບ <strong>ລະຫັດຜ່ານ</strong> ແລະ <strong>ຢືນຢັນລະຫັດຜ່ານ</strong> ໃຫ້ກົງກັນ!</div>',
          confirmButtonText: 'ຕົກລົງ',
          confirmButtonColor: '#0284c7'
        });
      } else {
        alert('ລະຫັດຜ່ານ ແລະ ຢືນຢັນລະຫັດຜ່ານ ບໍ່ກົງກັນ!');
      }
      return false;
    }
  }

  return true;
}

// Open View Modal
function openViewModal(user) {
  var fullName = user.fname || user.username || '';
  var basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '../../';

  document.getElementById('view_fullname').innerText = fullName;
  document.getElementById('view_id').innerText = user.Id;
  document.getElementById('view_user_code').innerText = user.user_code ? user.user_code : '-';
  document.getElementById('view_gender').innerText = user.gender || '-';
  // Format ວັນທີ: 2005-09-16 → 16/09/2005
  function formatDob(raw) {
    if (!raw || raw === '-') return '-';
    // YYYY-MM-DD
    var m = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return m[3] + '/' + m[2] + '/' + m[1];
    return raw;
  }
  document.getElementById('view_dob').innerText = formatDob(user.dob);
  document.getElementById('view_tel').innerText = user.tel || '-';
  document.getElementById('view_address').innerText = user.address || '-';
  document.getElementById('view_notes').innerText = user.notes || '-';

  var imgName = user.profile_img || 'default.png';
  document.getElementById('view_avatar').src = basePath + 'assets/img/users/' + imgName;

  var st = (user.status || user.userstatus || 'ພະນັກງານ').trim();
  var isOnline = !!user.is_online;
  var badgeHtml = '';
  var i18n = typeof I18N_USERS !== 'undefined' ? I18N_USERS : {};

  // Online/Offline status badge
  if (isOnline) {
    badgeHtml += '<span class="badge badge-success px-3 py-1 mr-1 shadow-sm" style="border-radius: 6px;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + (i18n['users_manage.status_online'] || 'ໃຊ້ງານຢູ່') + '</span> ';
  } else {
    badgeHtml += '<span class="badge badge-danger px-3 py-1 mr-1 shadow-sm" style="border-radius: 6px;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + (i18n['users_manage.status_offline'] || 'ບໍ່ໄດ້ໃຊ້ງານ') + '</span> ';
  }

  // Role badge (ຜູ້ບໍລິຫານ / ພະນັກງານ)
  if (st === 'ຜູ້ບໍລິຫານ' || st.toLowerCase() === 'admin') {
    badgeHtml += '<span class="badge badge-primary px-3 py-1" style="border-radius: 6px;"><i class="fas fa-user-shield mr-1"></i> ' + (i18n['users_manage.role_admin'] || 'ຜູ້ບໍລິຫານ') + '</span>';
  } else {
    badgeHtml += '<span class="badge badge-secondary px-3 py-1" style="border-radius: 6px;"><i class="fas fa-user mr-1"></i> ' + (i18n['users_manage.role_employee'] || 'ພະນັກງານ') + '</span>';
  }
  
  var statusBadgeEl = document.getElementById('view_status_badge');
  if (statusBadgeEl) {
    statusBadgeEl.innerHTML = badgeHtml;
  }

  // ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດ (Id 1) ເທົ່ານັ້ນທີ່ແກ້ໄຂບັນຊີຜູ້ບໍລິຫານຄົນອື່ນໄດ້ — ແຕ່ຜູ້ບໍລິຫານ
  // ຄົນອື່ນຍັງແກ້ໄຂຂໍ້ມູນຂອງຕົນເອງໄດ້ (self-service). ຄືກົດເກນດຽວກັນກັບຕາຕະລາງ
  // (users_manage.php: $canEditRow) ແລະ ຝັ່ງ server (api_user.php).
  var isOwnerUserOnly = typeof IS_OWNER_USER !== 'undefined' ? IS_OWNER_USER : false;
  var canEditUsersPerm = typeof CAN_EDIT_USERS !== 'undefined' ? CAN_EDIT_USERS : false;
  var currentSessionUserId = typeof CURRENT_SESSION_USER_ID !== 'undefined' ? CURRENT_SESSION_USER_ID : 0;
  var isEditingSelf = (parseInt(user.Id, 10) === currentSessionUserId);
  var rowIsAdmin = (parseInt(user.Id, 10) === 1 || st === 'ຜູ້ບໍລິຫານ' || st.toLowerCase() === 'admin' || st.toLowerCase() === 'super admin');
  var canEditThisUser = canEditUsersPerm && (isOwnerUserOnly || !rowIsAdmin || isEditingSelf);

  var btnEditFromView = document.getElementById('btn_edit_from_view');
  if (btnEditFromView) {
    if (canEditThisUser) {
      btnEditFromView.style.display = '';
      btnEditFromView.onclick = function() {
        var savedUser = user;
        // ລໍຖ້າ viewUserModal ປິດສຳເລັດກ່ອນ ຈຶ່ງເປີດ editUserModal
        $('#viewUserModal').one('hidden.bs.modal', function() {
          openEditModal(savedUser);
        });
        $('#viewUserModal').modal('hide');
      };
    } else {
      btnEditFromView.style.display = 'none';
      btnEditFromView.onclick = null;
    }
  }

  $('#viewUserModal').modal('show');
}

// Open Edit Modal
function openEditModal(user) {
  var basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '../../';
  document.getElementById('edit_user_id').value = user.Id;
  document.getElementById('edit_user_code').value = user.user_code || '';
  document.getElementById('edit_fname').value = user.fname || user.username || '';
  document.getElementById('edit_tel').value = user.tel || '';
  
  if (user.dob && user.dob.indexOf('/') !== -1) {
    var parts = user.dob.split('/');
    if (parts.length === 3) {
      document.getElementById('edit_dob').value = parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
    } else {
      document.getElementById('edit_dob').value = user.dob;
    }
  } else {
    document.getElementById('edit_dob').value = user.dob || '';
  }

  document.getElementById('edit_address').value = user.address || '';
  document.getElementById('edit_notes').value = user.notes || '';
  document.getElementById('edit_password').value = '';
  document.getElementById('edit_confirm_password').value = '';

  var editStoreEl = document.getElementById('edit_store_id');
  if (editStoreEl) {
    editStoreEl.value = user.store_id || user.branch_id || '1';
  }

  var genderVal = (user.gender || 'ຊາຍ').trim();
  if (genderVal === 'ຍິງ') {
    document.getElementById('edit_gender_female').checked = true;
  } else {
    document.getElementById('edit_gender_male').checked = true;
  }

  var imgName = user.profile_img || 'default.png';
  document.getElementById('edit_avatar_preview').src = basePath + 'assets/img/users/' + imgName;
  var removeEditBtn = document.getElementById('btn_remove_edit_avatar');
  var removeFlag = document.getElementById('remove_profile_img_flag');
  if (removeFlag) removeFlag.value = "0";
  if (imgName && imgName !== 'default.png') {
    if (removeEditBtn) removeEditBtn.style.display = 'flex';
  } else {
    if (removeEditBtn) removeEditBtn.style.display = 'none';
  }

  var rawStatus = (user.status || user.userstatus || '').trim();
  var st = rawStatus.toLowerCase();
  var selectEl = document.getElementById('edit_status') || document.getElementById('edit_userstatus');

  if (st === 'admin' || st === 'administrator' || rawStatus === 'ຜູ້ບໍລິຫານ') {
    selectEl.value = 'ຜູ້ບໍລິຫານ';
  } else {
    selectEl.value = 'ພະນັກງານ';
  }

  if (!selectEl.value) {
    selectEl.selectedIndex = 0;
  }

  // ຈຳນວນມື້ອະນຸຍາດໃຫ້ໃຊ້ງານ (ສະເພາະຜູ້ບໍລິຫານຄົນທຳອິດເຫັນ/ແກ້ໄຂ) — ID 1 ບໍ່ມີວັນໝົດອາຍຸ
  var accessDaysGroup = document.getElementById('edit_access_days_group');
  var accessDaysInput = document.getElementById('edit_access_days');
  if (accessDaysGroup && accessDaysInput) {
    if (parseInt(user.Id, 10) === 1) {
      accessDaysGroup.style.display = 'none';
      accessDaysInput.value = '';
    } else {
      accessDaysGroup.style.display = '';
      if (user.access_expire_date) {
        var msLeft = new Date(user.access_expire_date + 'T00:00:00') - new Date(new Date().toDateString());
        var daysLeft = Math.ceil(msLeft / 86400000);
        accessDaysInput.value = daysLeft > 0 ? daysLeft : 0;
      } else {
        accessDaysInput.value = '';
      }
    }
  }

  $('#editUserModal').modal('show');
}

// Submit Form via AJAX API
function submitUserForm(e, formId, actionName) {
  if (e) e.preventDefault();

  if (!validateUserForm(formId)) {
    return false;
  }

  var form = document.getElementById(formId);
  var formData = new FormData(form);
  formData.set('action', actionName);

  if (typeof Swal !== 'undefined') {
    Swal.fire({
      html: '<div class="lao-dots-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div><div class="preloader-text" style="margin-top:10px;">ກຳລັງໂຫຼດຂໍ້ມູນ...</div>',
      showConfirmButton: false,
      allowOutsideClick: false,
      background: '#ffffff'
    });
  }

  var targetUrl = typeof API_URL !== 'undefined' ? API_URL : 'api_user.php';

  fetch(targetUrl, {
    method: 'POST',
    body: formData
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    if (data.status === 'success') {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'ສຳເລັດ!',
          text: data.message,
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true
        }).then(function() {
          location.reload();
        });
      } else {
        location.reload();
      }
    } else {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'ຜິດພາດ!',
          text: data.message || 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກ',
          confirmButtonText: 'ຕົກລົງ',
          confirmButtonColor: '#ef4444'
        });
      } else {
        alert(data.message || 'ເກີດຂໍ້ຜິດພາດ');
      }
    }
  })
  .catch(function(err) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'ຜິດພາດ!',
        text: 'ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບ API ໄດ້: ' + err.message,
        confirmButtonText: 'ຕົກລົງ'
      });
    } else {
      alert('ບໍ່ສາມາດເຊື່ອມຕໍ່ກັບ API ໄດ້: ' + err.message);
    }
  });

  return false;
}

// Confirm & Perform Delete
function confirmDeleteUser(id, username) {
  var targetUrl = typeof API_URL !== 'undefined' ? API_URL : 'api_user.php';
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: 'ຢືນຢັນການລົບຜູ້ໃຊ້',
      text: 'ທ່ານຕ້ອງການລົບຜູ້ໃຊ້ "' + username + '" ແທ້ຫຼືບໍ່?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-trash mr-1"></i> ລົບເລີຍ',
      cancelButtonText: 'ຍົກເລີກ'
    }).then(function(res) {
      if (res.isConfirmed) {
        Swal.fire({
          html: '<div class="lao-dots-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div><div class="preloader-text" style="margin-top:10px;">ກຳລັງໂຫຼດຂໍ້ມູນ...</div>',
          showConfirmButton: false,
          allowOutsideClick: false,
          background: '#ffffff'
        });

        var formData = new FormData();
        formData.append('action', 'delete_user');
        formData.append('user_id', id);

        fetch(targetUrl, {
          method: 'POST',
          body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'ສຳເລັດ!',
              text: data.message,
              confirmButtonText: 'ຕົກລົງ',
              confirmButtonColor: '#0284c7'
            }).then(function() {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'ຜິດພາດ!',
              text: data.message,
              confirmButtonText: 'ຕົກລົງ'
            });
          }
        })
        .catch(function(err) {
          Swal.fire({
            icon: 'error',
            title: 'ຜິດພາດ!',
            text: 'ເກີດຂໍ້ຜິດພາດໃນການລົບ: ' + err.message,
            confirmButtonText: 'ຕົກລົງ'
          });
        });
      }
    });
  } else {
    if (confirm('ທ່ານຕ້ອງການລົບຜູ້ໃຊ້ "' + username + '" ແທ້ຫຼືບໍ່?')) {
      var formData = new FormData();
      formData.append('action', 'delete_user');
      formData.append('user_id', id);

      fetch(targetUrl, { method: 'POST', body: formData })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        alert(data.message);
        if (data.status === 'success') location.reload();
      });
    }
  }
}
