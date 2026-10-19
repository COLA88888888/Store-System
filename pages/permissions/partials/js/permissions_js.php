<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍ Partials JavaScript: ຟັງຊັນຄວບຄຸມ UI ແລະ AJAX Requests
 * Path: pages/permissions/partials/js/permissions_js.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່:
 * - selectUser(userId): ສະຫຼັບສະແດງຂໍ້ມູນຕາຕະລາງສິດຂອງຜູ້ນຳໃຊ້ທີ່ເລືອກຢູ່ທາງຊ້າຍ
 * - filterUserList(): ຟັງຊັນ Live Search ຄົ້ນຫາຊື່ຜູ້ໃຊ້ ຫຼື ບົດບາດ ໃນລາຍຊື່ທາງຊ້າຍ
 * - toggleUserPerm(userId, perm, checkbox): ສົ່ງ AJAX ໄປອັບເດດສິດເອກະລາດ (ເປີດ/ປິດ ສະວິດ)
 * - applyRolePreset(userId, presetKey): ສົ່ງ AJAX ນຳໃຊ້ຮູບແບບສິດດ່ວນ (Cashier, Accountant, etc.)
 */
?>

<!-- JavaScript Logic ສຳລັບ Master-Detail & AJAX Interactions -->
<script>
var I18N_PERMISSIONS = <?php echo tjson([
    'permissions.badge_account_suffix' => 'ບັນຊີ',
    'permissions.js_alert_title' => 'ແຈ້ງເຕືອນ',
    'permissions.js_error_title' => 'ຜິດພາດ',
    'permissions.js_update_perm_fail' => 'ບໍ່ສາມາດອັບເດດສິດໄດ້!',
    'permissions.js_connection_error' => 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ກັບເຊີເວີ!',
    'permissions.js_confirm_preset_title' => 'ຢືນຢັນການກຳນົດສິດດ່ວນ?',
    'permissions.js_confirm_preset_text' => 'ລະບົບຈະອັບເດດສິດທຸກໂມດູນຂອງຜູ້ນຳໃຊ້ນີ້ຕາມຮູບແບບທີ່ເລືອກ!',
    'permissions.js_confirm_apply' => 'ຢືນຢັນນຳໃຊ້',
    'permissions.js_cancel' => 'ຍົກເລີກ',
    'permissions.js_preset_fail' => 'ບໍ່ສາມາດກຳນົດສິດດ່ວນໄດ້!',
]); ?>;

/**
 * 1. ຟັງຊັນເລືອກຜູ້ນຳໃຊ້ (Master-Detail Selection)
 * ເມື່ອຜູ້ໃຊ້ຄລິກເລືອກບັນຊີຜູ້ໃຊ້ຢູ່ທາງຊ້າຍ:
 * - ໄຮໄລ້ (Active) Card ຜູ້ໃຊ້ນັ້ນ
 * - ສະແດງ Panel ຕາຕະລາງສິດ (Matrix Table) ຂອງຜູ້ໃຊ້ນັ້ນຢູ່ທາງຂວາ
 */
function selectUser(userId) {
  // ລົບ class active ຈາກທຸກບັນຊີ
  document.querySelectorAll('.user-item-btn').forEach(function(btn) {
    btn.classList.remove('active-user-item');
  });
  
  // ເພີ່ມ class active ໃຫ້ບັນຊີທີ່ຖືກເລືອກ
  var activeBtn = document.getElementById('user-item-' + userId);
  if (activeBtn) {
    activeBtn.classList.add('active-user-item');
  }

  // ຊ່ອນ Panel ຕາຕະລາງສິດຂອງທຸກຄົນ
  document.querySelectorAll('.user-detail-panel').forEach(function(panel) {
    panel.style.display = 'none';
  });
  
  // ສະແດງ Panel ຕາຕະລາງສິດຂອງຄົນທີ່ເລືອກ
  var targetPanel = document.getElementById('user-detail-' + userId);
  if (targetPanel) {
    targetPanel.style.display = 'flex';
  }
}

/**
 * 2. ຟັງຊັນ Live Search (ຄົ້ນຫາຊື່ຜູ້ໃຊ້ ຫຼື ບົດບາດ)
 * ທຳງານເມື່ອມີການພິມຂໍ້ຄວາມໃນຊ່ອງ Input ຄົ້ນຫາ
 */
function filterUserList() {
  var input = document.getElementById('userFilterInput');
  var filter = input.value.toLowerCase().trim();
  var items = document.querySelectorAll('.user-item-btn');
  var visibleCount = 0;

  items.forEach(function(item) {
    var username = item.getAttribute('data-username') || '';
    var status = item.getAttribute('data-status') || '';
    if (username.indexOf(filter) > -1 || status.indexOf(filter) > -1) {
      item.style.display = 'flex';
      visibleCount++;
    } else {
      item.style.display = 'none';
    }
  });

  // ອັບເດດ Badge ຈຳນວນບັນຊີທີ່ພົບ
  var badge = document.getElementById('totalUsersBadge');
  if (badge) {
    badge.innerText = visibleCount + ' ' + I18N_PERMISSIONS['permissions.badge_account_suffix'];
  }
}

/**
 * 3. Helper SweetAlert Toast Notification
 */
var Toast = null;
function getToast() {
  if (!Toast && typeof Swal !== 'undefined') {
    Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
      didOpen: function(toast) {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });
  }
  return Toast;
}

/**
 * 4. ຟັງຊັນປ່ຽນສິດເອກະລາດ (Single Permission Toggle via AJAX)
 * ເມື່ອມີການກົດ ເປີດ/ປິດ ສະວິດໃນຕາຕະລາງ:
 * - ສົ່ງ AJAX POST ajax_action: 'toggle_perm' ໄປຫາ backend
 * - ອັບເດດ Badge ຈຳນວນສິດ (Perm Count Counter)
 */
function toggleUserPerm(userId, perm, checkbox) {
  var val = checkbox.checked ? 1 : 0;
  var switchStates = {};
  document.querySelectorAll('[data-user-id="' + userId + '"]').forEach(function(cb) {
    switchStates[cb.id] = cb.checked ? 1 : 0;
  });

  $.ajax({
    url: window.location.href,
    type: 'POST',
    data: {
      ajax_action: 'toggle_perm',
      user_id: userId,
      perm: perm,
      val: val,
      switch_key: checkbox.id,
      switch_states: JSON.stringify(switchStates)
    },
    dataType: 'json',
    success: function(res) {
      if (res.success) {
        var toast = getToast();
        if (toast) {
          toast.fire({
            icon: 'success',
            title: res.message
          });
        }
        // ອັບເດດປ້າຍນັບສິດ (Badge Counter)
        var countVal = document.getElementById('count-val-' + userId);
        if (countVal && res.perm_count !== undefined) {
          countVal.innerText = res.perm_count;
        }
      } else {
        // ຄືນຄ່າສະວິດ ຖ້າອັບເດດບໍ່ສຳເລັດ
        checkbox.checked = !checkbox.checked;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: I18N_PERMISSIONS['permissions.js_alert_title'],
            text: res.message || I18N_PERMISSIONS['permissions.js_update_perm_fail']
          });
        }
      }
    },
    error: function() {
      checkbox.checked = !checkbox.checked;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: I18N_PERMISSIONS['permissions.js_error_title'],
          text: I18N_PERMISSIONS['permissions.js_connection_error']
        });
      }
    }
  });
}

// Restore each matrix switch from its own saved state.  Several columns can
// use the same permission internally, so their visual state must be restored
// by switch ID rather than by the shared permission name.
$(function() {
  $.ajax({
    url: window.location.href,
    type: 'POST',
    dataType: 'json',
    data: { ajax_action: 'get_switch_states' },
    success: function(res) {
      if (!res.success || !res.states) return;

      Object.keys(res.states).forEach(function(switchKey) {
        var checkbox = document.getElementById(switchKey);
        if (checkbox) {
          checkbox.checked = Number(res.states[switchKey]) === 1;
        }
      });
    }
  });
});

/**
 * 5. ຟັງຊັນນຳໃຊ້ຮູບແບບສິດດ່ວນ (Apply Role Preset Template via AJAX)
 */
function applyRolePreset(userId, presetKey) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: I18N_PERMISSIONS['permissions.js_confirm_preset_title'],
      text: I18N_PERMISSIONS['permissions.js_confirm_preset_text'],
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3b82f6',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_PERMISSIONS['permissions.js_confirm_apply'],
      cancelButtonText: I18N_PERMISSIONS['permissions.js_cancel']
    }).then((result) => {
      if (result.isConfirmed) {
        executePreset(userId, presetKey);
      }
    });
  } else {
    if (confirm(I18N_PERMISSIONS['permissions.js_confirm_preset_title'])) {
      executePreset(userId, presetKey);
    }
  }
}

/**
 * 6. Execute Preset AJAX Request
 */
function executePreset(userId, presetKey) {
  // ສົ່ງລາຍການສະວິດທັງໝົດ ({switch_id: perm}) ເພື່ອໃຫ້ backend ບັນທຶກສະຖານະທຸກສະວິດ
  var switches = {};
  document.querySelectorAll('[data-user-id="' + userId + '"][data-perm]').forEach(function(cb) {
    switches[cb.id] = cb.getAttribute('data-perm');
  });

  $.ajax({
    url: window.location.href,
    type: 'POST',
    data: {
      ajax_action: 'apply_preset',
      user_id: userId,
      preset: presetKey,
      switches: JSON.stringify(switches)
    },
    dataType: 'json',
    success: function(res) {
      if (res.success) {
        var toast = getToast();
        if (toast) {
          toast.fire({
            icon: 'success',
            title: res.message
          });
        }
        var countVal = document.getElementById('count-val-' + userId);
        if (countVal && res.perm_count !== undefined) {
          countVal.innerText = res.perm_count;
        }

        // ອັບເດດສະຖານະສະວິດທຸກອັນໃນຕາຕະລາງຕາມຄ່າ Preset ໃໝ່
        if (res.switch_values) {
          Object.keys(res.switch_values).forEach(function(switchKey) {
            var cb = document.getElementById(switchKey);
            if (cb) cb.checked = Number(res.switch_values[switchKey]) === 1;
          });
        } else if (res.permissions) {
          var p = res.permissions;
          for (var key in p) {
            var val = p[key];
            document.querySelectorAll('[data-user-id="' + userId + '"][data-perm="' + key + '"]').forEach(function(cb) {
              cb.checked = (val == 1);
            });
          }
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: I18N_PERMISSIONS['permissions.js_alert_title'],
            text: res.message || I18N_PERMISSIONS['permissions.js_preset_fail']
          });
        }
      }
    },
    error: function() {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: I18N_PERMISSIONS['permissions.js_error_title'],
          text: I18N_PERMISSIONS['permissions.js_connection_error']
        });
      }
    }
  });
}
</script>
