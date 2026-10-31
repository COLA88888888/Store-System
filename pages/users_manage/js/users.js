/*
 * JS ໜ້າຈັດການຜູ້ໃຊ້ (1/2): ສະຖານະອອນລາຍສົດ, ລະຫັດຜູ້ໃຊ້ຖັດໄປ, ຮູບໂປຣໄຟລ໌, ສະແດງລະຫັດຜ່ານ ແລະ ກວດຟອມ.
 * ສ່ວນທີ 2 (popup ເບິ່ງ/ແກ້ໄຂ, ບັນທຶກ, ລົບ) ຢູ່ໃນ users_modals.js — ໂຫຼດຕໍ່ຈາກໄຟລ໌ນີ້.
 */
// ຂໍ້ຄວາມຕາມພາສາ (I18N_USERS ໃນ users_manage.php) — %s ຖືກແທນດ້ວຍຄ່າທີ່ສົ່ງມາຕາມລຳດັບ
function uT(key) {
  var dict = typeof I18N_USERS !== 'undefined' ? I18N_USERS : {};
  var v = dict[key] || key;
  var args = Array.prototype.slice.call(arguments, 1);
  return args.length ? v.replace(/%s/g, function () { return args.length ? args.shift() : ''; }) : v;
}

// Real-Time Polling for User Online/Offline Status (no page refresh required)
function pollLiveUserStatuses() {
  var apiUrl = typeof API_URL !== 'undefined' ? API_URL : '../../api/users_api.php';
  $.ajax({
    url: apiUrl + '?action=get_live_user_statuses',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && Array.isArray(res.users)) {
        var i18n = typeof I18N_USERS !== 'undefined' ? I18N_USERS : {};
        var onlineText = i18n['users_manage.status_online'] || '' + (window.__tl||String)("ໃຊ້ງານຢູ່") + '';
        var offlineText = i18n['users_manage.status_offline'] || '' + (window.__tl||String)("ບໍ່ໄດ້ໃຊ້ງານ") + '';
        var disabledText = i18n['users_manage.status_disabled'] || '' + (window.__tl||String)("ປິດໃຊ້ງານ (ລາອອກ)") + '';
        res.users.forEach(function(u) {
          var statusTd = $('td.user-online-status-td[data-user-id="' + u.id + '"]');
          if (statusTd.length > 0) {
            var html = '';
            if (u.account_active === 0) {
              html = '<span class="badge badge-dark px-2.5 py-1.5 shadow-sm" style="border-radius: 6px; font-size: 0.82rem;"><i class="fas fa-user-slash mr-1"></i> ' + disabledText + '</span>';
            } else if (u.is_online) {
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
    ['add_password', 'add_confirm_password'].forEach(function(pid) {
      var pEl = document.getElementById(pid);
      if (pEl) {
        pEl.type = 'password';
        pEl.value = '';
        var ic = pEl.parentElement ? pEl.parentElement.querySelector('button i') : null;
        if (ic) { ic.className = 'fas fa-eye-slash'; }
      }
    });
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

// Auto-generate ລະຫັດຜູ້ໃຊ້ ແຍກຕາມສາຂາ "<ລະຫັດສາຂາ>-<ລຳດັບ>" (1-1, 1-2 …) ຕາມສາຂາທີ່ເລືອກ
function fetchNextUserCode() {
  var apiUrl = typeof API_URL !== 'undefined' ? API_URL : '../../api/users_api.php';
  var codeEl = document.getElementById('add_user_code');
  if (!codeEl) return;

  codeEl.placeholder = uT('users_js.code_generating');
  codeEl.style.color = '#94a3b8';

  var storeSel = codeEl.form ? codeEl.form.querySelector('select[name="store_id"]') : null;
  var storeParam = storeSel && storeSel.value ? '&store_id=' + encodeURIComponent(storeSel.value) : '';

  $.ajax({
    url: apiUrl + '?action=get_next_user_code' + storeParam,
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success' && res.code) {
        codeEl.value = res.code;
        codeEl.style.color = '#2563eb';
      } else {
        codeEl.value = '';
        codeEl.style.color = '#2563eb';
      }
    },
    error: function() {
      codeEl.value = '';
      codeEl.style.color = '#2563eb';
    }
  });
}


// ປ່ຽນສາຂາໃນຟອມເພີ່ມ → ລະຫັດອໍໂຕ້ປ່ຽນຕາມ (ສະເພາະລະຫັດຮູບແບບ "1-3" — ລະຫັດທີ່ພິມເອງບໍ່ແຕະ)
$(document).on('change', 'select[name="store_id"]', function() {
  var codeEl = document.getElementById('add_user_code');
  if (!codeEl || codeEl.form !== this.form) return;
  if (codeEl.value === '' || /^[0-9A-Za-z]+-[0-9]+$/.test(codeEl.value)) fetchNextUserCode();
});

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

// Toggle Password Visibility (ເລີ່ມຕົ້ນເຊື່ອງລະຫັດ = ປິດຕາ fa-eye-slash, ກົດເບິ່ງ = ເປີດຕາ fa-eye)
function togglePassVisibility(inputId, btn) {
  var input = document.getElementById(inputId);
  if (!input) return;
  var icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  } else {
    input.type = 'password';
    if (icon) {
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
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

  if (!user_code) missingFields.push(uT('users_js.f_code'));
  if (!fname) missingFields.push(uT('users_js.f_name'));
  
  if (formId === 'addUserForm') {
    if (!password) missingFields.push(uT('users_js.f_password'));
  }

  if (!dob) missingFields.push(uT('users_js.f_dob'));
  if (!tel) missingFields.push(uT('users_js.f_tel'));

  if (missingFields.length > 0) {
    var fieldListStr = missingFields.join(', ');
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: uT('users_js.missing_title'),
        html: '<div style="font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', sans-serif;">' + uT('users_js.missing_text') + ' <strong class="text-danger">' + fieldListStr + '</strong><br><span class="text-muted mt-2 d-inline-block" style="font-size:0.9rem;">' + uT('users_js.missing_hint', '<span class="text-danger">*</span>') + '</span></div>',
        confirmButtonText: uT('users_js.ok'),
        confirmButtonColor: '#0284c7'
      });
    } else {
      alert(uT('users_js.missing_title') + ' ' + fieldListStr);
    }
    return false;
  }

  if (password || confirm_password) {
    if (password !== confirm_password) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: uT('users_js.pw_mismatch_title'),
          html: '<div style="font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', sans-serif;">' + uT('users_js.pw_mismatch_text') + '</div>',
          confirmButtonText: uT('users_js.ok'),
          confirmButtonColor: '#0284c7'
        });
      } else {
        alert(uT('users_js.pw_mismatch_text'));
      }
      return false;
    }
  }

  return true;
}

