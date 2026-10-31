<?php
/*
 * ສ່ວນ: JS: ແຈ້ງເຕືອນປິດກະ (ຜູ້ບໍລິຫານ / ຜູ້ຈັດການ)
 * ຂໍ້ມູນມາຈາກ poll_notifications (res.shift_closes — config/helpers/shift_alerts.php):
 *   "ພະນັກງານ X ປິດກະ Y" (ກົດອອກຈາກລະບົບ ຫຼື ປິດເອງ) ພ້ອມຍອດຂາຍ ແລະ ຈຳນວນບິນ
 * - buildShiftCloseNotifHtml(): ສ່ວນໃນກະດິ່ງແຈ້ງເຕືອນ (ສະເພາະທີ່ຍັງບໍ່ອ່ານ — ກົດເບິ່ງແລ້ວຫາຍໄປ)
 * - countUnreadShiftCloses(): ນັບທີ່ຍັງບໍ່ອ່ານ ລວມເຂົ້າຕົວເລກເທິງກະດິ່ງ
 * - toastNewShiftCloses(): ປ໊ອບອັບມຸມຂວາເທິງ ຄັ້ງດຽວຕໍ່ກະ (ຈື່ໄວ້ໃນ localStorage)
 * - ກົດ → ລາຍງານປິດກະ ເປີດໃບສະຫຼຸບ + ລາຍການຂາຍຂອງພະນັກງານໃນກະນັ້ນ (?open_session=N)
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ home/dashboard.php ຕໍ່ຈາກ js_stock_check_notif.php (ໃຊ້ scNotifEsc ຂອງໄຟລ໌ນັ້ນ).
 */
?>
    var SHC_COLOR = '#7c3aed', SHC_BG = '#f5f3ff';

    function shcLine(n) {
      return (n.who || '-') + (n.by_logout ? ' <?php echo tlj('ອອກຈາກລະບົບ'); ?> — <?php echo tlj('ປິດກະ'); ?> ' : ' <?php echo tlj('ປິດກະ'); ?> ') + (n.shift_name || '');
    }
    function shcSub(n) {
      return '<?php echo tlj('ຍອດຂາຍ'); ?> ' + Number(n.total || 0).toLocaleString('en-US') + ' <?php echo kipSymbol(); ?> · ' + (n.bills || 0) + ' <?php echo tlj('ບິນ'); ?>';
    }

    function countUnreadShiftCloses(list, readKeys) {
      var n = 0;
      (list || []).forEach(function(it) { if (readKeys.indexOf(it.key) === -1) n++; });
      return n;
    }

    // ສະແດງສະເພາະທີ່ຍັງບໍ່ອ່ານ — ກົດເບິ່ງແລ້ວ (ຫຼື ອ່ານທັງໝົດ) ແຈ້ງເຕືອນນັ້ນຫາຍໄປຈາກກະດິ່ງ
    function buildShiftCloseNotifHtml(list, badgeId, readKeys, showStore) {
      list = (list || []).filter(function(it) { return readKeys.indexOf(it.key) === -1; });
      if (!list.length) return '';
      var html = '<div id="shcNotifHead" class="px-3 py-1.5 font-weight-bold border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: ' + SHC_BG + '; font-size: 0.82rem; white-space: nowrap;">';
      html += '<span class="text-truncate" style="color: ' + SHC_COLOR + ';"><i class="fas fa-cash-register mr-1"></i> <?php echo tlj('ປິດກະແລ້ວ'); ?></span>';
      html += '<span id="shcNotifCount" class="badge font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px; background: ' + SHC_COLOR + '; color: #fff;">' + list.length + '</span>';
      html += '</div>';
      list.forEach(function(it) {
        html += '<div id="notif_item_' + it.key + '" onclick="openShiftCloseFromNotif(' + parseInt(it.id, 10) + ', \'' + it.key + '\', \'' + badgeId + '\')" class="dropdown-item py-2 px-3 border-bottom shc-notif-item" style="background-color: #ffffff; cursor: pointer; white-space: normal;">';
        html += '<div class="d-flex align-items-center justify-content-between">';
        html += '<strong class="text-dark mr-2" style="font-size: 0.82rem;"><i class="fas ' + (it.by_logout ? 'fa-sign-out-alt' : 'fa-lock') + ' mr-1" style="color: ' + SHC_COLOR + ';"></i>' + scNotifEsc(shcLine(it)) + '</strong>';
        html += '<span class="text-muted flex-shrink-0" style="font-size: 0.72rem;">' + scNotifEsc(it.at_text) + '</span>';
        html += '</div>';
        html += '<div class="text-muted" style="font-size: 0.76rem;">' + scNotifEsc(shcSub(it)) + (showStore && it.store_name ? ' · <i class="fas fa-store mr-1"></i>' + scNotifEsc(it.store_name) : '') + '</div>';
        html += '</div>';
      });
      return html;
    }

    // ກົດແຈ້ງເຕືອນ → ໝາຍວ່າອ່ານແລ້ວ ແລະ ເປີດລາຍງານປິດກະ ພ້ອມໃບສະຫຼຸບ/ລາຍການຂາຍຂອງກະນັ້ນ
    function openShiftCloseFromNotif(id, key, badgeId) {
      if (key && badgeId && typeof markItemAsRead === 'function') markItemAsRead(key, badgeId);
      // ເອົາອອກຈາກກະດິ່ງທັນທີ (ບໍ່ຕ້ອງລໍ poll ຮອບຕໍ່ໄປ); ໝວດຫວ່າງແລ້ວ → ເອົາຫົວໝວດອອກນຳ
      if (key) $('#notif_item_' + key).remove();
      var left = $('.shc-notif-item').length;
      if (left) $('#shcNotifCount').text(left); else $('#shcNotifHead').remove();
      var url = '../pages/reports/shift_close_report.php?open_session=' + parseInt(id, 10);
      var iframe = document.getElementById('mainContentFrame') || document.querySelector('iframe[name="frame"]');
      if (iframe) {
        iframe.src = url;
        try { sessionStorage.setItem('StoreMini.currentIframeSrc', url); } catch (e) {}
      } else {
        window.location.href = url;
      }
      try { $('.dropdown-menu.show').removeClass('show'); } catch (e) {}
    }
    window.openShiftCloseFromNotif = openShiftCloseFromNotif;

    // ປ໊ອບອັບ (toast) ຄັ້ງດຽວຕໍ່ກະທີ່ປິດ — ຈື່ key ທີ່ສະແດງແລ້ວໄວ້ 3 ວັນ
    function toastNewShiftCloses(list, badgeId) {
      if (!list || !list.length || typeof Swal === 'undefined') return;
      var shown = {};
      try { shown = JSON.parse(localStorage.getItem('StoreMini.shc_notif_toasted') || '{}'); } catch (e) { shown = {}; }
      var now = Date.now(), fresh = null;
      Object.keys(shown).forEach(function(k) { if (now - shown[k] > 3 * 86400000) delete shown[k]; });
      list.forEach(function(it) { if (!fresh && !shown[it.key]) fresh = it; });
      if (!fresh) return;
      list.forEach(function(it) { shown[it.key] = shown[it.key] || now; });   // ສະແດງອັນລ່າສຸດອັນດຽວ, ອັນອື່ນຢູ່ໃນກະດິ່ງ
      try { localStorage.setItem('StoreMini.shc_notif_toasted', JSON.stringify(shown)); } catch (e) {}
      Swal.fire({
        toast: true, position: 'top-end', icon: 'info',
        title: scNotifEsc(shcLine(fresh)),
        html: '<div style="font-size: 0.85rem;">' + scNotifEsc(shcSub(fresh)) + '<br><a href="#" class="font-weight-bold" style="color: ' + SHC_COLOR + ';"><?php echo tlj('ກົດເພື່ອເບິ່ງລາຍການຂາຍຂອງກະ'); ?></a></div>',
        showConfirmButton: false, showCloseButton: true, timer: 10000, timerProgressBar: true,
        didOpen: function(el) {
          el.style.cursor = 'pointer';
          el.addEventListener('click', function(e) {
            if (e.target.closest('.swal2-close')) return;
            Swal.close();
            openShiftCloseFromNotif(fresh.id, fresh.key, badgeId);
          });
        }
      });
    }
