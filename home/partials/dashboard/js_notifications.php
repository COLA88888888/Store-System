<?php
/*
 * ສ່ວນ: JS: ແຈ້ງເຕືອນ
 * ສຽງແຈ້ງເຕືອນ, ຈື່ວ່າແຈ້ງເຕືອນໃດອ່ານແລ້ວ, ຕົວເລກໃນກະດິ່ງ, ເປີດລາຍລະອຽດການໂອນສິນຄ້າ/ການນຳເຂົ້າ,
 * ໄປໜ້າສິນຄ້າໝົດ/ໝົດອາຍຸ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>    function unlockAudioContext() {
      try {
        var AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        if (!sharedAudioCtx) {
          sharedAudioCtx = new AudioCtx();
        }
        if (sharedAudioCtx.state === 'suspended') {
          sharedAudioCtx.resume();
        }
        var buffer = sharedAudioCtx.createBuffer(1, 1, 22050);
        var source = sharedAudioCtx.createBufferSource();
        source.buffer = buffer;
        source.connect(sharedAudioCtx.destination);
        source.start(0);
        isAudioUnlocked = true;
      } catch(e) {}
    }

    ['click', 'touchstart', 'touchend', 'pointerdown', 'keydown'].forEach(function(evtName) {
      document.addEventListener(evtName, unlockAudioContext, { capture: true, passive: true });
      window.addEventListener(evtName, unlockAudioContext, { capture: true, passive: true });
    });

    function playNotificationSound() {
      try {
        unlockAudioContext();
        var AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        
        var ctx = sharedAudioCtx || new AudioCtx();
        if (ctx.state === 'suspended') {
          ctx.resume();
        }
        
        var now = ctx.currentTime;
        var chimeNotes = [
          { freq: 880.00, delay: 0.00, duration: 0.30, gain: 0.9 },
          { freq: 1108.73, delay: 0.12, duration: 0.30, gain: 0.9 },
          { freq: 1318.51, delay: 0.24, duration: 0.55, gain: 1.0 }
        ];

        chimeNotes.forEach(function(note) {
          var startTime = now + note.delay;
          var osc = ctx.createOscillator();
          var gainNode = ctx.createGain();

          osc.type = 'triangle';
          osc.frequency.setValueAtTime(note.freq, startTime);

          gainNode.gain.setValueAtTime(note.gain, startTime);
          gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + note.duration);

          osc.connect(gainNode);
          gainNode.connect(ctx.destination);

          osc.start(startTime);
          osc.stop(startTime + note.duration);
        });
      } catch(e) {}
    }
    window.playNotificationSound = playNotificationSound;
    window.unlockAudioContext = unlockAudioContext;

    // Low-stock / out-of-stock alerts (keys main_*, stk_*) are STICKY per branch: they cannot be
    // dismissed and only clear once the product is restocked. Every other notification's
    // "read" state expires after 1 day, so those alerts disappear on their own.
    var NOTIF_STICKY_RE = /^(main_|stk_)/;
    var NOTIF_READ_TTL = 86400000; // 1 day

    function loadReadNotifKeys(badgeId) {
      var raw = [], tsMap = {};
      try { raw = JSON.parse(localStorage.getItem('StoreMini.read_notif_keys_' + badgeId) || '[]'); } catch(e) { raw = []; }
      try { tsMap = JSON.parse(localStorage.getItem('StoreMini.read_notif_ts_' + badgeId) || '{}'); } catch(e) { tsMap = {}; }
      var now = Date.now(), kept = [], keptTs = {};
      raw.forEach(function(k) {
        if (NOTIF_STICKY_RE.test(k)) return;                 // sticky: never treated as read
        var t = tsMap[k];
        if (t && (now - t) > NOTIF_READ_TTL) return;         // expired after 1 day
        kept.push(k);
        keptTs[k] = t || now;
      });
      try {
        localStorage.setItem('StoreMini.read_notif_keys_' + badgeId, JSON.stringify(kept));
        localStorage.setItem('StoreMini.read_notif_ts_' + badgeId, JSON.stringify(keptTs));
      } catch(e) {}
      return kept;
    }

    function persistReadNotifKey(badgeId, key) {
      if (!key || NOTIF_STICKY_RE.test(key)) return false;   // can't dismiss sticky alerts
      var keys = loadReadNotifKeys(badgeId);
      if (keys.indexOf(key) !== -1) return false;
      keys.push(key);
      var tsMap = {};
      try { tsMap = JSON.parse(localStorage.getItem('StoreMini.read_notif_ts_' + badgeId) || '{}'); } catch(e) {}
      tsMap[key] = Date.now();
      try {
        localStorage.setItem('StoreMini.read_notif_keys_' + badgeId, JSON.stringify(keys));
        localStorage.setItem('StoreMini.read_notif_ts_' + badgeId, JSON.stringify(tsMap));
      } catch(e) {}
      return true;
    }

    // Bell click handler referenced inline in the markup — keep it harmless.
    function markNotifAsRead(badgeId) { if (badgeId) loadReadNotifKeys(badgeId); }
    window.markNotifAsRead = markNotifAsRead;

    function updateBadgeUI(badgeId) {
      var readKeys = [];
      try {
        readKeys = loadReadNotifKeys(badgeId);
      } catch(e) { readKeys = []; }

      var dropdownBodyId = badgeId === 'mainNotifBadge' ? 'mainNotifDropdownBody' : 'subNotifDropdownBody';
      var items = $('#' + dropdownBodyId + ' .dropdown-item');
      
      var unreadCount = 0;
      items.each(function() {
        var itemId = $(this).attr('id');
        if (itemId) {
          var key = itemId.replace('notif_item_', '');
          if (!readKeys.includes(key)) {
            unreadCount++;
          }
        }
      });

      var btnId = badgeId === 'mainNotifBadge' ? '#btnMarkAllMain' : '#btnMarkAllSub';
      var badge = $('#' + badgeId);
      if (unreadCount > 0) {
        if (badge.length > 0) {
          badge.text(unreadCount).show();
        } else {
          var iconId = badgeId === 'mainNotifBadge' ? 'mainNotifBellIcon' : 'subNotifBellIcon';
          var parentAnchor = $('#' + iconId).parent();
          parentAnchor.append('<span id="' + badgeId + '" class="badge badge-danger font-weight-bold position-absolute" style="top: -2px; right: -4px; font-size: 0.72rem; border-radius: 10px; padding: 2px 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.4); border: 1.5px solid #ffffff;">' + unreadCount + '</span>');
        }
        $(btnId).show();
      } else {
        if (badge.length > 0) badge.hide();
        $(btnId).hide();
      }
    }

    function markItemAsRead(itemKey, badgeId) {
      if (!itemKey || !badgeId) return;
      // Sticky low-stock alerts (main_*, stk_*) can't be dismissed — persistReadNotifKey returns false.
      if (persistReadNotifKey(badgeId, itemKey)) {
        var el = document.getElementById('notif_item_' + itemKey);
        if (el) el.style.opacity = '0.55';
        updateBadgeUI(badgeId);
      }
    }

    function markAllNotifsAsRead(badgeId) {
      var badge = $('#' + badgeId);
      if (badge.length > 0) badge.hide();

      var btnId = badgeId === 'mainNotifBadge' ? '#btnMarkAllMain' : '#btnMarkAllSub';
      $(btnId).hide();

      // Mark every currently visible notification as read — EXCEPT sticky low-stock alerts
      // (persistReadNotifKey ignores main_*/stk_*), which keep showing until restocked.
      var dropdownBodyId = badgeId === 'mainNotifBadge' ? 'mainNotifDropdownBody' : 'subNotifDropdownBody';
      $('#' + dropdownBodyId + ' .dropdown-item').each(function() {
        var itemId = $(this).attr('id');
        if (!itemId) return;
        var key = itemId.replace('notif_item_', '');
        if (NOTIF_STICKY_RE.test(key)) return;   // leave sticky low-stock rows untouched
        persistReadNotifKey(badgeId, key);
        $(this).css('opacity', '0.55');
      });

      if (window.lastNotifResponse) {
        var res = window.lastNotifResponse;
        if (res.is_main) {
          (res.sub_branch_imports || []).forEach(function(imp) { persistReadNotifKey(badgeId, 'imp_' + imp.import_detail_id); });
        } else {
          (res.incoming_transfers || []).forEach(function(t) { persistReadNotifKey(badgeId, 'trf_' + t.transfer_id); });
          (res.sub_new_products || []).forEach(function(p) { persistReadNotifKey(badgeId, 'np_' + p.product_id); });
        }
      }

      // A remaining sticky low-stock alert should keep the badge visible.
      updateBadgeUI(badgeId);
    }

    $(document).ready(function() {
      pollLiveNotifications();
    });

    function viewTransferDetailsModal(transferId) {
      if (!transferId) return;
      var activeBadgeId = ($('#mainNotifBadge').length > 0 && $('#mainNotifBadge').is(':visible')) ? 'mainNotifBadge' : 'subNotifBadge';
      markItemAsRead('trf_' + transferId, activeBadgeId);

      // Automatically hide notification dropdown menu when clicking an item
      $('.dropdown-menu, .nav-item.dropdown, .dropdown').removeClass('show');

      // Reset modal contents and show loading spinner inside modal table
      $('#trf_modal_code').text('TRF-' + transferId);
      $('#trf_modal_date').text('ກຳລັງໂຫຼດ...');
      $('#trf_modal_from').text('-');
      $('#trf_modal_to').text('-');
      $('#trf_modal_creator').text('-');
      $('#trf_modal_item_count').text('0');
      $('#trf_modal_total_value').text('0');
      $('#trf_modal_table_body').html('<tr><td colspan="5" class="text-center py-4 text-primary font-weight-bold"><i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>ກຳລັງດຶງຂໍ້ມູນລາຍລະອຽດ...</td></tr>');

      // Move modal to body container to bypass any parent z-index / overflow restrictions
      var modalEl = $('#notifTransferDetailModal');
      if (modalEl.length > 0) {
        modalEl.appendTo('body').modal('show');
      }

      $.ajax({
        url: '../api/stock_transfer_backend.php?action=get_transfer_details&transfer_id=' + transferId,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
          if (res && res.success && res.transfer) {
            var trf = res.transfer;
            var details = res.details || [];

            $('#trf_modal_code').text(trf.transfer_code || ('TRF-' + trf.transfer_id));
            $('#trf_modal_date').text(trf.transfer_date || '-');
            $('#trf_modal_from').text(trf.from_store_name || 'ສາຂາໃຫຍ່');
            $('#trf_modal_to').text(trf.to_store_name || '-');
            $('#trf_modal_creator').text(trf.creator_name || 'Admin');
            $('#trf_modal_item_count').text(details.length);

            var tbodyHtml = '';
            var modalTotalValue = 0;
            if (details.length > 0) {
              details.forEach(function(item, idx) {
                var pName = item.product_name || '-';
                var barcode = item.prod_barcode || item.barcode || '-';
                var qty = item.qty || 1;
                var unit = item.unit || 'ອັນ';
                var lineValue = parseFloat(qty) * parseFloat(item.bprice || 0);
                modalTotalValue += lineValue;

                tbodyHtml += '<tr style="white-space: nowrap;">';
                tbodyHtml += '<td class="text-center align-middle font-weight-bold text-muted text-nowrap">' + (idx + 1) + '</td>';
                tbodyHtml += '<td class="align-middle font-weight-bold text-dark text-nowrap" style="white-space: nowrap;">' + pName + '</td>';
                tbodyHtml += '<td class="text-center align-middle text-muted text-nowrap" style="white-space: nowrap;">' + barcode + '</td>';
                tbodyHtml += '<td class="text-center align-middle text-nowrap" style="white-space: nowrap;"><span class="badge badge-primary px-3 py-1.5 font-weight-bold" style="font-size:0.82rem; border-radius: 6px;">' + qty + ' ' + unit + '</span></td>';
                tbodyHtml += '<td class="text-right align-middle font-weight-bold text-success text-nowrap" style="white-space: nowrap;">' + Math.round(lineValue).toLocaleString() + ' <?php echo kipSymbol(); ?></td>';
                tbodyHtml += '</tr>';
              });
            } else {
              tbodyHtml = '<tr><td colspan="5" class="text-center py-4 text-muted">ບໍ່ພົບລາຍການສິນຄ້າ</td></tr>';
            }

            $('#trf_modal_table_body').html(tbodyHtml);
            $('#trf_modal_total_value').text(Math.round(modalTotalValue).toLocaleString());
          } else {
            $('#trf_modal_table_body').html('<tr><td colspan="5" class="text-center py-4 text-danger font-weight-bold"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>' + (res ? res.message : 'ບໍ່ສາມາດໂຫຼດຂໍ້ມູນໄດ້') + '</td></tr>');
          }
        },
        error: function() {
          $('#trf_modal_table_body').html('<tr><td colspan="5" class="text-center py-4 text-danger font-weight-bold"><i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ລະບົບ</td></tr>');
        }
      });
    }

    window.markItemAsRead = markItemAsRead;
    window.viewTransferDetailsModal = viewTransferDetailsModal;
    window.markAllNotifsAsRead = markAllNotifsAsRead;

    function viewSubBranchImportDetail(itemData) {
      if (!itemData) return;
      var qtyVal = parseFloat(itemData.quantity || 0);
      var costVal = parseFloat(itemData.cost_price || 0);
      var totalVal = parseFloat(itemData.total_cost || 0);

      var htmlContent = `
        <div class="text-left" style="font-size: 0.9rem; line-height: 1.6; color: #334155;">
          <div class="p-2.5 mb-3 rounded" style="background-color: #f8fafc; border: 1px solid #cbd5e1;">
            <div class="mb-1.5"><strong class="text-dark"><i class="fas fa-store text-primary mr-1.5"></i> ສາຂາຍ່ອຍ:</strong> <span class="text-dark font-weight-bold">${itemData.store_name || '-'}</span></div>
            <div class="mb-1.5"><strong class="text-dark"><i class="fas fa-user-tag text-info mr-1.5"></i> ຜູ້ດຳເນີນການນຳເຂົ້າ:</strong> <span class="badge badge-info px-2 py-1" style="font-size: 0.82rem;">${itemData.creator_name || 'Admin'}</span></div>
            <div><strong class="text-dark"><i class="fas fa-file-invoice text-secondary mr-1.5"></i> ເລກທີໃບບິນ:</strong> <span class="font-weight-bold text-dark">${itemData.invoice_number || '-'}</span></div>
          </div>
          
          <div class="table-responsive border rounded mb-3">
            <table class="table table-bordered table-sm mb-0">
              <thead class="bg-light">
                <tr>
                  <th>ຊື່ສິນຄ້າ</th>
                  <th class="text-center">ຈຳນວນ</th>
                  <th class="text-right">ຕົ້ນທຶນ/ໜ່ວຍ</th>
                  <th class="text-right">ມູນຄ່າລວມ</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>${itemData.product_name || '-'}</strong></td>
                  <td class="text-center"><span class="badge badge-success px-2 py-1">+${qtyVal.toLocaleString()} ${itemData.unit_name || 'ອັນ'}</span></td>
                  <td class="text-right">${costVal.toLocaleString()} <?php echo kipSymbol(); ?></td>
                  <td class="text-right font-weight-bold text-success">${totalVal.toLocaleString()} <?php echo kipSymbol(); ?></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between text-muted pt-1" style="font-size: 0.8rem;">
            <span><i class="fas fa-truck mr-1"></i> ຜູ້ສະໜອງ: <strong>${itemData.supplier_name || 'ທົ່ວໄປ'}</strong></span>
            <span><i class="fas fa-clock mr-1"></i> ວັນທີ/ເວລາ: <strong>${itemData.import_date || '-'}</strong></span>
          </div>
        </div>
      `;

      Swal.fire({
        title: '<i class="fas fa-boxes text-success mr-2"></i>ລາຍລະອຽດການນຳເຂົ້າສິນຄ້າ',
        html: htmlContent,
        confirmButtonText: 'ອ່ານແລ້ວ / ປິດ',
        confirmButtonColor: '#0284c7',
        width: '520px'
      });
    }
    window.viewSubBranchImportDetail = viewSubBranchImportDetail;

    function goToRestockProduct(productName) {
      var url = '<?php echo $base_path; ?>pages/import_stock/import_stock.php';
      if (productName) {
        url += '?search=' + encodeURIComponent(productName);
      }
      $('#mainContentFrame').attr('src', url);
      try { sessionStorage.setItem('StoreMini.currentIframeSrc', url); } catch (e) {}
      $('.dropdown-menu.show').removeClass('show');
      $('.nav-item.dropdown.show').removeClass('show');
    }
    window.goToRestockProduct = goToRestockProduct;

    // ກົດລາຍການໃນກະດິ່ງສິນຄ້າໃກ້ໝົດ/ໝົດ → ເປີດໜ້າລາຍການສິນຄ້າຂອງສາຂານັ້ນ ສະແດງສະເພາະສິນຄ້າທີ່ກົດ
    // (?product=ID). ບໍ່ມີ ID → ສິນຄ້າໃກ້ໝົດທັງໝົດ (stock=low) ຫຼື ໝົດແລ້ວ (stock=out)
    function goToStockList(storeId, isOut, productId) {
      var url = '<?php echo $base_path; ?>pages/products/products.php?stock=' + (isOut ? 'out' : 'low');
      if (parseInt(storeId, 10) > 0) url += '&store_id=' + parseInt(storeId, 10);
      if (productId) url += '&product=' + encodeURIComponent(productId);
      $('#mainContentFrame').attr('src', url);
      try { sessionStorage.setItem('StoreMini.currentIframeSrc', url); } catch (e) {}
      $('.dropdown-menu.show').removeClass('show');
      $('.nav-item.dropdown.show').removeClass('show');
    }
    window.goToStockList = goToStockList;

    function goToExpiryProduct(productName, expired) {
      var url = '<?php echo $base_path; ?>pages/reports/expiry_report.php?status=' + (expired ? 'expired' : 'near');
      if (productName) url += '&q=' + encodeURIComponent(productName);
      $('#mainContentFrame').attr('src', url);
      try { sessionStorage.setItem('StoreMini.currentIframeSrc', url); } catch (e) {}
      $('.dropdown-menu.show').removeClass('show');
      $('.nav-item.dropdown.show').removeClass('show');
    }
    window.goToExpiryProduct = goToExpiryProduct;

    // ສ່ວນ "ສິນຄ້າໃກ້ໝົດອາຍຸ" ໃນກະດິ່ງ (ຂໍ້ມູນຈາກ getExpiryAlerts: ເຫຼືອ <= 5 ມື້ ຫຼື ໝົດອາຍຸແລ້ວ).
    // ຄືກັບສິນຄ້າໃກ້ໝົດສະຕັອກ: ແຈ້ງຄ້າງໄວ້ຈົນກວ່າຈະຍົກເລີກການຕິດຕາມ ຫຼື ແກ້ວັນໝົດອາຍຸ.
    function buildExpiryNotifHtml(list, showStore) {
      if (!list || list.length === 0) return '';
      var esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      };
      var html = '<div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #fef2f2; font-size: 0.82rem; white-space: nowrap;">';
      html += '<span class="text-danger text-truncate"><i class="fas fa-hourglass-half mr-1"></i> ' + I18N_LAYOUT['layout.expiry_near_header'] + '</span>';
      html += '<span class="badge badge-danger font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">' + list.length + ' ' + I18N_LAYOUT['layout.items_unit'] + '</span>';
      html += '</div>';
      list.forEach(function (it) {
        var days = parseInt(it.days_left, 10);
        var expired = days < 0;
        var label, cls;
        if (expired)        { label = I18N_LAYOUT['layout.expiry_expired']; cls = 'badge-danger'; }
        else if (days === 0) { label = I18N_LAYOUT['layout.expiry_today']; cls = 'badge-danger'; }
        else                { label = I18N_LAYOUT['layout.expiry_days_left'].replace('%d', days); cls = 'badge-warning text-dark'; }
        var name = (showStore && it.store_name ? it.store_name + ': ' : '') + (it.product_name || '');
        html += '<div id="notif_item_exp_' + it.store_id + '_' + it.product_id + '" onclick="goToExpiryProduct(' + esc(JSON.stringify(it.product_name || '')) + ', ' + (expired ? 'true' : 'false') + ')" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between border-bottom flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;" title="' + esc(I18N_LAYOUT['layout.click_to_expiry']) + '">';
        html += '<div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">';
        html += '<i class="fas fa-hourglass-end ' + (expired || days === 0 ? 'text-danger' : 'text-warning') + ' mr-1.5" style="font-size: 0.82rem;"></i>';
        html += '<strong class="text-dark text-truncate" style="font-size: 0.82rem;" title="' + esc(name) + '">' + esc(name) + '</strong>';
        html += '</div>';
        html += '<div class="text-nowrap flex-shrink-0 ml-2"><span class="badge ' + cls + ' font-weight-bold px-2 py-1" style="font-size: 0.74rem; border-radius: 6px;">' + esc(label) + '</span></div>';
        html += '</div>';
      });
      return html;
    }

    // Automatically close notification dropdown when clicking outside on navbar/main document
    $(document).on('click.notifClose touchstart.notifClose', function (e) {
      if (!$(e.target).closest('.nav-item.dropdown').length) {
        $('.dropdown-menu.show').removeClass('show');
        $('.nav-item.dropdown.show').removeClass('show');
      }
    });

    var lastUnreadCount = { mainNotifBadge: -1, subNotifBadge: -1 };

    // Real-Time Notification Polling (Every 1.5 seconds)
    // Own account's access-expiry warning row, prepended into whichever notification bell
    // (main/sub) this session sees. Not clickable/dismissible like the other items — it stays
    // until the owner extends the account or it expires.
    function buildAccessWarningNotifHtml() {
      if (typeof MY_ACCESS_DAYS_LEFT !== 'number') return '';
      var text = MY_ACCESS_DAYS_LEFT > 0
        ? I18N_LAYOUT['layout.access_expiry_warning_text'].replace('%d', MY_ACCESS_DAYS_LEFT)
        : I18N_LAYOUT['layout.access_expiry_warning_text_today'];
      var html = '<div class="dropdown-item py-2 px-3 border-bottom" style="background-color: #fffbeb; white-space: normal; cursor: default;">';
      html += '<div class="d-flex align-items-center text-dark font-weight-bold mb-1" style="font-size: 0.82rem;">';
      html += '<i class="fas fa-user-clock text-warning mr-2"></i> ' + I18N_LAYOUT['layout.access_expiry_warning_title'];
      html += '</div>';
      html += '<div class="text-muted" style="font-size: 0.78rem;">' + text + '</div>';
      html += '</div>';
      return html;
    }

