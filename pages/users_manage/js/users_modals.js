/*
 * JS ໜ້າຈັດການຜູ້ໃຊ້ (2/2): popup ເບິ່ງ/ແກ້ໄຂ, ສົ່ງຟອມບັນທຶກ ແລະ ຢືນຢັນລົບຜູ້ໃຊ້.
 * ໃຊ້ຟັງຊັນຈາກ users.js (ໂຫຼດກ່ອນ).
 */
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
  var viewAv = document.getElementById('view_avatar');
  if (viewAv) {
    viewAv.src = basePath + 'assets/img/users/' + imgName;
    viewAv.setAttribute('data-preview-title', (user.fname || user.username || '') + (user.user_code ? ' (' + user.user_code + ')' : ''));
  }

  var st = (user.status || user.userstatus || 'ພະນັກງານ').trim();
  var isOnline = !!user.is_online;
  var badgeHtml = '';
  var i18n = typeof I18N_USERS !== 'undefined' ? I18N_USERS : {};

  // Online/Offline status badge
  if (isOnline) {
    badgeHtml += '<span class="badge badge-success px-3 py-1 mr-1 shadow-sm" style="border-radius: 6px;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + (i18n['users_manage.status_online'] || '' + (window.__tl||String)("ໃຊ້ງານຢູ່") + '') + '</span> ';
  } else {
    badgeHtml += '<span class="badge badge-danger px-3 py-1 mr-1 shadow-sm" style="border-radius: 6px;"><i class="fas fa-circle mr-1" style="font-size: 0.55rem; vertical-align: middle;"></i> ' + (i18n['users_manage.status_offline'] || '' + (window.__tl||String)("ບໍ່ໄດ້ໃຊ້ງານ") + '') + '</span> ';
  }

  // Role badge (ຜູ້ບໍລິຫານ / ຜູ້ຈັດການ / ພະນັກງານ)
  var cleanSt = st.toLowerCase();
  var isAdm = (parseInt(user.Id, 10) === 1 || ['ຜູ້ບໍລິຫານ', 'ผู้บริหาร', 'admin', 'administrator', 'super admin', 'superadmin'].indexOf(cleanSt) !== -1);
  var isMgr = ['ຜູ້ຈັດການ', 'ผู้จัดการ', 'manager'].indexOf(cleanSt) !== -1;
  if (isAdm) {
    badgeHtml += '<span class="badge badge-primary px-3 py-1" style="border-radius: 6px;"><i class="fas fa-user-shield mr-1"></i> ' + (i18n['users_manage.role_admin'] || 'ຜູ້ບໍລິຫານ') + '</span>';
  } else if (isMgr) {
    badgeHtml += '<span class="badge badge-info px-3 py-1" style="border-radius: 6px;"><i class="fas fa-user-tie mr-1"></i> ' + (i18n['users_manage.role_manager_short'] || 'ຜູ້ຈັດການ') + '</span>';
  } else {
    badgeHtml += '<span class="badge badge-secondary px-3 py-1" style="border-radius: 6px;"><i class="fas fa-user mr-1"></i> ' + (i18n['users_manage.role_employee'] || 'ພະນັກງານ') + '</span>';
  }
  
  var statusBadgeEl = document.getElementById('view_status_badge');
  if (statusBadgeEl) {
    statusBadgeEl.innerHTML = badgeHtml;
  }

  // ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດ (Id 1) ເທົ່ານັ້ນທີ່ແກ້ໄຂບັນຊີຜູ້ບໍລິຫານຄົນອື່ນໄດ້ — ແຕ່ຜູ້ບໍລິຫານ
  // ຄົນອື່ນຍັງແກ້ໄຂຂໍ້ມູນຂອງຕົນເອງໄດ້ (self-service). ຄືກົດເກນດຽວກັນກັບຕາຕະລາງ
  // (users_manage.php: $canEditRow) ແລະ ຝັ່ງ server (api/users_api.php).
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
  ['edit_password', 'edit_confirm_password'].forEach(function(pid) {
    var pEl = document.getElementById(pid);
    if (pEl) {
      pEl.type = 'password';
      pEl.value = '';
      var ic = pEl.parentElement ? pEl.parentElement.querySelector('button i') : null;
      if (ic) { ic.className = 'fas fa-eye-slash'; }
    }
  });

  var editStoreEl = document.getElementById('edit_store_id');
  if (editStoreEl) {
    var curStore = String(user.store_id || user.branch_id || '');
    // ລາຍການສາຂາມີແຕ່ສາຂາທີ່ເປີດໃຊ້ງານ: ຖ້າສາຂາປັດຈຸບັນຂອງຜູ້ໃຊ້ຖືກປິດ ມັນບໍ່ມີໃນລາຍການ → ເພີ່ມໃຫ້ຊົ່ວຄາວ
    // ບໍ່ດັ່ງນັ້ນການແກ້ຂໍ້ມູນອື່ນ (ເຊັ່ນ ຕໍ່ອາຍຸ) ຈະຍ້າຍຜູ້ໃຊ້ໄປສາຂາອື່ນໂດຍບໍ່ຮູ້ຕົວ
    $(editStoreEl).find('option.edit-store-inactive').remove();
    if (curStore && !$(editStoreEl).find('option[value="' + curStore + '"]').length) {
      $('<option class="edit-store-inactive"></option>').val(curStore)
        .text((user.store_name || uT('users_js.store_fallback', curStore)) + ' ' + uT('users_js.store_inactive')).appendTo(editStoreEl);
    }
    editStoreEl.value = curStore || editStoreEl.value;
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
  } else if (rawStatus === 'ຜູ້ຈັດການ') {
    // ຕ້ອງເລືອກຄືນໃຫ້ຖືກ — ບໍ່ດັ່ງນັ້ນການແກ້ຂໍ້ມູນອື່ນຈະປົດຜູ້ຈັດການເປັນພະນັກງານ (ແລະ ເອົາສິດຄືນ)
    selectEl.value = 'ຜູ້ຈັດການ';
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
      html: '<div class="lao-dots-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div><div class="preloader-text" style="margin-top:10px;">' + uT('users_js.loading') + '</div>',
      showConfirmButton: false,
      allowOutsideClick: false,
      background: '#ffffff'
    });
  }

  var targetUrl = typeof API_URL !== 'undefined' ? API_URL : '../../api/users_api.php';

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
          title: uT('users_js.success'),
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
          title: uT('users_js.error'),
          text: data.message || uT('users_js.save_failed'),
          confirmButtonText: uT('users_js.ok'),
          confirmButtonColor: '#ef4444'
        });
      } else {
        alert(data.message || uT('users_js.generic_error'));
      }
    }
  })
  .catch(function(err) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: uT('users_js.error'),
        text: uT('users_js.api_failed') + ' ' + err.message,
        confirmButtonText: uT('users_js.ok')
      });
    } else {
      alert(uT('users_js.api_failed') + ' ' + err.message);
    }
  });

  return false;
}

// Confirm & Perform Delete
function confirmDeleteUser(id, username) {
  var targetUrl = typeof API_URL !== 'undefined' ? API_URL : '../../api/users_api.php';
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: uT('users_js.delete_title'),
      text: uT('users_js.delete_text', username),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-trash mr-1"></i> ' + uT('users_js.delete_btn'),
      cancelButtonText: uT('users_js.cancel')
    }).then(function(res) {
      if (res.isConfirmed) {
        Swal.fire({
          html: '<div class="lao-dots-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div><div class="preloader-text" style="margin-top:10px;">' + uT('users_js.loading') + '</div>',
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
              title: uT('users_js.success'),
              text: data.message,
              confirmButtonText: uT('users_js.ok'),
              confirmButtonColor: '#0284c7'
            }).then(function() {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: uT('users_js.error'),
              text: data.message,
              confirmButtonText: uT('users_js.ok')
            });
          }
        })
        .catch(function(err) {
          Swal.fire({
            icon: 'error',
            title: uT('users_js.error'),
            text: uT('users_js.delete_failed') + ' ' + err.message,
            confirmButtonText: uT('users_js.ok')
          });
        });
      }
    });
  } else {
    if (confirm(uT('users_js.delete_text', username))) {
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

// ເປີດ / ປິດໃຊ້ງານບັນຊີ (ພະນັກງານລາອອກ) — ປິດແລ້ວເຂົ້າລະບົບບໍ່ໄດ້ ແຕ່ປະຫວັດຍັງຢູ່
function toggleUserActive(id, username, active) {
  var targetUrl = typeof API_URL !== 'undefined' ? API_URL : '../../api/users_api.php';
  var title = active ? uT('users_js.enable_title') : uT('users_js.disable_title');
  var text = active
    ? uT('users_js.enable_text', username)
    : uT('users_js.disable_text', username);

  Swal.fire({
    title: title,
    text: text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: active ? '#16a34a' : '#475569',
    cancelButtonColor: '#64748b',
    confirmButtonText: active ? '<i class="fas fa-user-check mr-1"></i> ' + uT('users_js.enable_btn') : '<i class="fas fa-user-slash mr-1"></i> ' + uT('users_js.disable_btn'),
    cancelButtonText: uT('users_js.cancel')
  }).then(function(res) {
    if (!res.isConfirmed) return;

    var formData = new FormData();
    formData.append('action', 'toggle_user_active');
    formData.append('user_id', id);
    formData.append('active', active);

    fetch(targetUrl, { method: 'POST', body: formData })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data.status === 'success') {
        Swal.fire({ icon: 'success', title: uT('users_js.success'), text: data.message, confirmButtonText: uT('users_js.ok'), confirmButtonColor: '#0284c7' })
          .then(function() { location.reload(); });
      } else {
        Swal.fire({ icon: 'error', title: uT('users_js.error'), text: data.message, confirmButtonText: uT('users_js.ok') });
      }
    })
    .catch(function(err) {
      Swal.fire({ icon: 'error', title: uT('users_js.error'), text: err.message, confirmButtonText: uT('users_js.ok') });
    });
  });
}
