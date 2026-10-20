<?php
/*
 * ສ່ວນ: JS: ແຈ້ງເຕືອນເຊັກສະຕັອກ
 * ຂໍ້ມູນມາຈາກ poll_notifications (res.stock_checks — config/helpers/stock_check_alerts.php):
 *   review   → ຫົວໜ້າ: "ພະນັກງານສົ່ງໃບ SC-… ມາກວດສອບ"
 *   approved → ພະນັກງານ: "ຫົວໜ້າອະນຸມັດແລ້ວ — ປັບສະຕັອກໄດ້"
 * - buildStockCheckNotifHtml(): ສ່ວນໃນກະດິ່ງແຈ້ງເຕືອນ (ກົດ → ເປີດໃບນັ້ນໃນ iframe ແລະ ໝາຍວ່າອ່ານແລ້ວ)
 * - countUnreadStockChecks(): ນັບທີ່ຍັງບໍ່ອ່ານ ລວມເຂົ້າຕົວເລກເທິງກະດິ່ງ (ສຽງດັງຕາມລະບົບເດີມ)
 * - toastNewStockChecks(): ປ໊ອບອັບມຸມຂວາເທິງ ຄັ້ງດຽວຕໍ່ແຈ້ງເຕືອນ (ຈື່ໄວ້ໃນ localStorage)
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ home/dashboard.php ຕໍ່ຈາກ js_notifications.php (ຢູ່ໃນ <script> ດຽວກັນ).
 */
?>
    var SC_NOTIF_TEXT = {
      review:   { head: 'ສົ່ງມາກວດສອບ (ເຊັກສະຕັອກ)', icon: 'fa-user-clock', color: '#d97706', bg: '#fffbeb',
                  line: function(n) { return 'ພະນັກງານສົ່ງໃບ ' + n.check_no + ' ມາກວດສອບ'; },
                  sub:  function(n) { return 'ສົ່ງໂດຍ ' + (n.who || '-') + ' · ນັບ ' + n.counted + ' ລາຍການ'; } },
      approved: { head: 'ອະນຸມັດແລ້ວ — ປັບສະຕັອກໄດ້', icon: 'fa-user-check', color: '#0284c7', bg: '#f0f9ff',
                  line: function(n) { return 'ຫົວໜ້າອະນຸມັດໃບ ' + n.check_no + ' ແລ້ວ — ປັບສະຕັອກໄດ້'; },
                  sub:  function(n) { return 'ອະນຸມັດໂດຍ ' + (n.who || '-'); } }
    };

    function scNotifEsc(s) { return $('<div>').text(s === null || s === undefined ? '' : String(s)).html(); }

    function countUnreadStockChecks(list, readKeys) {
      var n = 0;
      (list || []).forEach(function(it) { if (readKeys.indexOf(it.key) === -1) n++; });
      return n;
    }

    function buildStockCheckNotifHtml(list, badgeId, readKeys, showStore) {
      if (!list || !list.length) return '';
      var html = '';
      ['review', 'approved'].forEach(function(type) {
        var items = list.filter(function(it) { return it.type === type; });
        if (!items.length) return;
        var T = SC_NOTIF_TEXT[type];
        html += '<div class="px-3 py-1.5 font-weight-bold border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: ' + T.bg + '; font-size: 0.82rem; white-space: nowrap;">';
        html += '<span class="text-truncate" style="color: ' + T.color + ';"><i class="fas ' + T.icon + ' mr-1"></i> ' + T.head + '</span>';
        html += '<span class="badge font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px; background: ' + T.color + '; color: #fff;">' + items.length + '</span>';
        html += '</div>';
        items.forEach(function(it) {
          var isRead = readKeys.indexOf(it.key) !== -1;
          html += '<div id="notif_item_' + it.key + '" onclick="openStockCheckFromNotif(' + parseInt(it.id, 10) + ', \'' + it.key + '\', \'' + badgeId + '\')" class="dropdown-item py-2 px-3 border-bottom" style="background-color: #ffffff; cursor: pointer; white-space: normal; opacity: ' + (isRead ? '0.55' : '1') + ';">';
          html += '<div class="d-flex align-items-center justify-content-between">';
          html += '<strong class="text-dark mr-2" style="font-size: 0.82rem;"><i class="fas fa-clipboard-check mr-1" style="color: ' + T.color + ';"></i>' + scNotifEsc(T.line(it)) + '</strong>';
          html += '<span class="text-muted flex-shrink-0" style="font-size: 0.72rem;">' + scNotifEsc(it.at_text) + '</span>';
          html += '</div>';
          html += '<div class="text-muted" style="font-size: 0.76rem;">' + scNotifEsc(T.sub(it)) + (showStore && it.store_name ? ' · <i class="fas fa-store mr-1"></i>' + scNotifEsc(it.store_name) : '') + '</div>';
          html += '</div>';
        });
      });
      return html;
    }

    // ກົດແຈ້ງເຕືອນ → ໝາຍວ່າອ່ານແລ້ວ ແລະ ເປີດໃບເຊັກສະຕັອກນັ້ນໃນ iframe
    function openStockCheckFromNotif(id, key, badgeId) {
      if (key && badgeId && typeof markItemAsRead === 'function') markItemAsRead(key, badgeId);
      var url = '../pages/stock_check/stock_check.php?id=' + parseInt(id, 10);
      var iframe = document.getElementById('mainContentFrame') || document.querySelector('iframe[name="frame"]');
      if (iframe) {
        iframe.src = url;
        try { sessionStorage.setItem('StoreMini.currentIframeSrc', url); } catch (e) {}
      } else {
        window.location.href = url;
      }
      try { $('.dropdown-menu.show').removeClass('show'); } catch (e) {}
    }
    window.openStockCheckFromNotif = openStockCheckFromNotif;

    // ປ໊ອບອັບ (toast) ຄັ້ງດຽວຕໍ່ແຈ້ງເຕືອນໃໝ່ — ຈື່ key ທີ່ສະແດງແລ້ວໄວ້ 3 ວັນ
    function toastNewStockChecks(list, badgeId) {
      if (!list || !list.length || typeof Swal === 'undefined') return;
      var shown = {};
      try { shown = JSON.parse(localStorage.getItem('StoreMini.sc_notif_toasted') || '{}'); } catch (e) { shown = {}; }
      var now = Date.now(), fresh = null;
      Object.keys(shown).forEach(function(k) { if (now - shown[k] > 3 * 86400000) delete shown[k]; });
      list.forEach(function(it) { if (!fresh && !shown[it.key]) fresh = it; });
      if (!fresh) return;
      list.forEach(function(it) { shown[it.key] = shown[it.key] || now; });   // ສະແດງອັນລ່າສຸດອັນດຽວ, ອັນອື່ນຢູ່ໃນກະດິ່ງ
      try { localStorage.setItem('StoreMini.sc_notif_toasted', JSON.stringify(shown)); } catch (e) {}
      var T = SC_NOTIF_TEXT[fresh.type] || SC_NOTIF_TEXT.review;
      Swal.fire({
        toast: true, position: 'top-end', icon: fresh.type === 'review' ? 'warning' : 'info',
        title: scNotifEsc(T.line(fresh)), html: '<div style="font-size: 0.85rem;">' + scNotifEsc(T.sub(fresh)) + '<br><a href="#" class="font-weight-bold" style="color: ' + T.color + ';">ກົດເພື່ອເປີດໃບ</a></div>',
        showConfirmButton: false, showCloseButton: true, timer: 10000, timerProgressBar: true,
        didOpen: function(el) {
          el.style.cursor = 'pointer';
          el.addEventListener('click', function(e) {
            if (e.target.closest('.swal2-close')) return;
            Swal.close();
            openStockCheckFromNotif(fresh.id, fresh.key, badgeId);
          });
        }
      });
    }
