<?php
/*
 * ສ່ວນ: JS: ດຶງແຈ້ງເຕືອນແບບ realtime ແລະ ໂມງ
 * pollLiveNotifications() ຖາມເຊີເວີເປັນໄລຍະເພື່ອອັບເດດແຈ້ງເຕືອນ, ສະຫຼັບສາຂາ, ເປີດຟອມໂອນສິນຄ້າ,
 * ແລະ ໂມງເວລາໃນແຖບເທິງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>    function pollLiveNotifications() {
      $.ajax({
        url: '../api/stock_transfer_backend.php?action=poll_notifications',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
          if (!res || !res.success) return;
          window.lastNotifResponse = res;
          
          var badgeId = res.is_main ? 'mainNotifBadge' : 'subNotifBadge';
          var badge = $('#' + badgeId);
          // ກະດິ່ງແຈ້ງເຕືອນ: ສະເພາະສິນຄ້າໃກ້ໝົດ/ໝົດ ແລະ ສິນຄ້າໃກ້ໝົດອາຍຸ/ໝົດອາຍຸ ເທົ່ານັ້ນ.
          // ທັງສອງແບບຄ້າງໄວ້ຈົນກວ່າຈະເຕີມສະຕັອກ / ຈັດການສິນຄ້າໝົດອາຍຸ (ກົດອ່ານແລ້ວບໍ່ຫາຍ).
          var unreadCount = 0;
          if (res.is_main) {
            (res.grouped_low_stock || []).forEach(function(grp) {
              unreadCount += (grp.items ? grp.items.length : 0);
            });
            unreadCount += (res.expiry_list || []).length;
          } else {
            unreadCount += (res.sub_low_stock || []).length;
            unreadCount += (res.sub_expiry || []).length;
          }
          // ເຊັກສະຕັອກ / ປິດກະ: ບໍ່ຢູ່ໃນກະດິ່ງແລ້ວ — ຍັງປ໊ອບອັບມຸມຂວາເທິງຄືເກົ່າ
          toastNewStockChecks(res.stock_checks, badgeId);
          toastNewShiftCloses(res.shift_closes, badgeId);
          var totalServerCount = unreadCount;
          var readKeys = [];
          try { readKeys = loadReadNotifKeys(badgeId); } catch (e) { readKeys = []; }

          if (lastUnreadCount[badgeId] !== -1 && unreadCount > lastUnreadCount[badgeId]) {
            playNotificationSound();
          }
          lastUnreadCount[badgeId] = unreadCount;

          var titleCountId = res.is_main ? '#mainNotifTitleCount' : '#subNotifTitleCount';
          $(titleCountId).text(totalServerCount);

          if (unreadCount > 0) {
            if (badge.length > 0) {
              badge.text(unreadCount).show();
            } else {
              var parentAnchor = $('#' + (res.is_main ? 'mainNotifBellIcon' : 'subNotifBellIcon')).parent();
              parentAnchor.append('<span id="' + badgeId + '" class="badge badge-danger font-weight-bold position-absolute" style="top: -2px; right: -4px; font-size: 0.72rem; border-radius: 10px; padding: 2px 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.4); border: 1.5px solid #ffffff;">' + unreadCount + '</span>');
            }
          } else {
            if (badge.length > 0) badge.hide();
          }

          // Dynamically update dropdown HTML in real time without page refresh
          if (res.is_main) {
            var bodyEl = $('#mainNotifDropdownBody');
            if (bodyEl.length > 0) {
              var html = '';

              // 1. Near-expiry / expired products (all branches, so prefix the branch name)
              html += buildExpiryNotifHtml(res.expiry_list, true);

              // 2. Low Stock & Out of Stock Section
              if (res.grouped_low_stock && res.grouped_low_stock.length > 0) {
                res.grouped_low_stock.forEach(function(grp) {
                  html += '<div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #f1f5f9; font-size: 0.82rem; white-space: nowrap;">';
                  html += '<span class="text-truncate"><i class="fas fa-store text-primary mr-1"></i> ' + (grp.store_name || '') + (grp.is_main ? ' <small class="text-muted">' + I18N_LAYOUT['layout.branch_main_suffix'] + '</small>' : '') + '</span>';
                  html += '<span class="badge badge-warning text-dark font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">' + (grp.items ? grp.items.length : 0) + ' ' + I18N_LAYOUT['layout.items_unit'] + '</span>';
                  html += '</div>';

                  (grp.items || []).forEach(function(alert) {
                    var isOutOfStock = (parseInt(alert.qty || 0) <= 0);
                    var badgeClass = isOutOfStock ? 'badge-danger' : 'badge-warning text-dark';
                    var qtyLabel = isOutOfStock ? I18N_LAYOUT['layout.out_of_stock'] : (I18N_LAYOUT['layout.remaining'] + ' ' + alert.qty + ' ' + (alert.unit || I18N_LAYOUT['layout.default_unit']));

                    html += '<div id="notif_item_main_' + alert.product_id + '" onclick="markItemAsRead(\'main_' + alert.product_id + '\', \'mainNotifBadge\'); goToStockList(' + (parseInt(alert.store_id, 10) || 0) + ', ' + (isOutOfStock ? 'true' : 'false') + ', \'' + String(alert.product_id).replace(/[^0-9A-Za-z_-]/g, '') + '\')" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between border-bottom flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;">';
                    html += '<div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">';
                    html += '<i class="fas fa-exclamation-circle ' + (isOutOfStock ? 'text-danger' : 'text-warning') + ' mr-1.5" style="font-size: 0.82rem;"></i>';
                    html += '<strong class="text-dark text-truncate" style="font-size: 0.82rem;" title="' + (alert.product_name || '') + '">' + (alert.product_name || '') + '</strong>';
                    html += '</div>';
                    html += '<div class="text-nowrap flex-shrink-0 ml-2">';
                    html += '<span class="badge ' + badgeClass + ' font-weight-bold px-2 py-1" style="font-size: 0.74rem; border-radius: 6px;">' + qtyLabel + '</span>';
                    html += '</div>';
                    html += '</div>';
                  });
                });
              }

              if (!html) {
                html = '<div class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i><span style="font-size: 0.85rem;">' + I18N_LAYOUT['layout.no_new_notif'] + '</span></div>';
              }
              bodyEl.html(html);
            }
          } else {
            var bodyEl = $('#subNotifDropdownBody');
            if (bodyEl.length > 0) {
              var html = buildExpiryNotifHtml(res.sub_expiry, false);

              if (res.sub_low_stock && res.sub_low_stock.length > 0) {
                html += '<div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #fff7ed; font-size: 0.82rem; white-space: nowrap;">';
                html += '<span class="text-danger text-truncate"><i class="fas fa-exclamation-triangle mr-1"></i> ' + I18N_LAYOUT['layout.low_stock_own'] + '</span>';
                html += '<span class="badge badge-danger font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">' + res.sub_low_stock.length + ' ' + I18N_LAYOUT['layout.items_unit'] + '</span>';
                html += '</div>';

                res.sub_low_stock.forEach(function(stk) {
                  var isRead = readKeys.includes('stk_' + stk.product_id);
                  var isOutOfStock = (parseInt(stk.qty || 0) <= 0);
                  var badgeClass = isOutOfStock ? 'badge-danger' : 'badge-warning text-dark';
                  var qtyLabel = isOutOfStock ? I18N_LAYOUT['layout.out_of_stock'] : (I18N_LAYOUT['layout.remaining'] + ' ' + stk.qty + ' ' + (stk.unit || I18N_LAYOUT['layout.default_unit']));
                  var iconClass = isOutOfStock ? 'text-danger' : 'text-warning';

                  html += '<div id="notif_item_stk_' + stk.product_id + '" onclick="markItemAsRead(\'stk_' + stk.product_id + '\', \'subNotifBadge\'); goToStockList(' + (parseInt(stk.store_id, 10) || 0) + ', ' + (isOutOfStock ? 'true' : 'false') + ', \'' + String(stk.product_id).replace(/[^0-9A-Za-z_-]/g, '') + '\')" class="dropdown-item py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden; opacity: ' + (isRead ? '0.55' : '1') + ';">';
                  html += '<div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">';
                  html += '<i class="fas fa-exclamation-circle ' + iconClass + ' mr-1.5" style="font-size: 0.82rem;"></i>';
                  html += '<span class="text-dark font-weight-bold text-truncate" style="font-size: 0.82rem;" title="' + (stk.product_name || '') + '">' + (stk.product_name || '') + '</span>';
                  html += '</div>';
                  html += '<div class="text-nowrap flex-shrink-0">';
                  html += '<span class="badge ' + badgeClass + ' font-weight-bold px-2 py-1" style="font-size: 0.74rem;">' + qtyLabel + '</span>';
                  html += '</div>';
                  html += '</div>';
                });
              }

              if (!html) {
                html = '<div class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i><span style="font-size: 0.85rem;">' + I18N_LAYOUT['layout.no_sub_notif'] + '</span></div>';
              }

              bodyEl.html(html);
            }
          }
        }
      });
    }

    window.pollLiveNotifications = pollLiveNotifications;
    setInterval(pollLiveNotifications, 1500);

    function switchDashboardBranch(storeId) {
      $.ajax({
        url: '../api/branches_backend.php',
        type: 'POST',
        data: { action: 'switch_branch', store_id: storeId },
        success: function(res) {
          window.location.reload();
        }
      });
    }

    function openStockTransferModal(targetStoreId, productName, productId) {
      var iframe = document.getElementById('mainContentFrame');
      var transferUrl = '../pages/import_stock/stock_transfer.php?target_store=' + targetStoreId + '&search=' + encodeURIComponent(productName || '') + (productId ? '&product_id=' + productId : '') + '&auto_add=1';
      if (iframe) {
        iframe.src = transferUrl;
        sessionStorage.setItem('StoreMini.currentIframeSrc', transferUrl);
      } else {
        window.location.href = transferUrl;
      }
    }
  </script>

  <script>
    // Real-Time Clock Function
    function updateLiveClock() {
      var now = new Date();
      var d = String(now.getDate()).padStart(2, '0');
      var m = String(now.getMonth() + 1).padStart(2, '0');
      var y = now.getFullYear();
      var hh = String(now.getHours()).padStart(2, '0');
      var mm = String(now.getMinutes()).padStart(2, '0');
      var ss = String(now.getSeconds()).padStart(2, '0');
      var el = document.getElementById('live_datetime_clock');
      if (el) {
        el.innerText = d + '/' + m + '/' + y + ' ' + hh + ':' + mm + ':' + ss;
      }
    }
    setInterval(updateLiveClock, 1000);
  </script>
