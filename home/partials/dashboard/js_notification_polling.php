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
          var totalServerCount = res.total_count || 0;

          // Own account's access-expiry warning (see MY_ACCESS_DAYS_LEFT above) is folded into
          // whichever bell this session actually sees — it isn't tracked server-side by
          // poll_notifications, so it's added here on every poll cycle instead.
          var hasAccessWarning = (typeof MY_ACCESS_DAYS_LEFT === 'number');
          if (hasAccessWarning) totalServerCount += 1;

          var readKeys = [];
          try {
            readKeys = loadReadNotifKeys(badgeId);
          } catch(e) { readKeys = []; }

          var unreadCount = 0;
          if (res.is_main) {
            // Count unread sub-branch stock import items (exactly 1 per imported item detail)
            (res.sub_branch_imports || []).forEach(function(imp) {
              if (!readKeys.includes('imp_' + imp.import_detail_id)) unreadCount++;
            });
            // Low stock & Out of stock alerts stay active continuously until stock is restocked
            (res.grouped_low_stock || []).forEach(function(grp) {
              unreadCount += (grp.items ? grp.items.length : 0);
            });
            unreadCount += (res.expiry_list || []).length; // Persistent expiry alert
          } else {
            unreadCount += (res.sub_expiry || []).length; // Persistent expiry alert
            (res.incoming_transfers || []).forEach(function(t) {
              if (!readKeys.includes('trf_' + t.transfer_id)) unreadCount++;
            });
            (res.sub_low_stock || []).forEach(function(s) {
              unreadCount++; // Persistent low stock alert
            });
            (res.sub_new_products || []).forEach(function(p) {
              if (!readKeys.includes('np_' + p.product_id)) unreadCount++;
            });
          }
          if (hasAccessWarning) unreadCount++; // persistent, like a low-stock alert

          if (lastUnreadCount[badgeId] !== -1 && unreadCount > lastUnreadCount[badgeId]) {
            playNotificationSound();
          }
          lastUnreadCount[badgeId] = unreadCount;

          var btnId = res.is_main ? '#btnMarkAllMain' : '#btnMarkAllSub';
          var titleCountId = res.is_main ? '#mainNotifTitleCount' : '#subNotifTitleCount';
          $(titleCountId).text(totalServerCount);

          if (unreadCount > 0) {
            if (badge.length > 0) {
              badge.text(unreadCount).show();
            } else {
              var parentAnchor = $('#' + (res.is_main ? 'mainNotifBellIcon' : 'subNotifBellIcon')).parent();
              parentAnchor.append('<span id="' + badgeId + '" class="badge badge-danger font-weight-bold position-absolute" style="top: -2px; right: -4px; font-size: 0.72rem; border-radius: 10px; padding: 2px 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.4); border: 1.5px solid #ffffff;">' + unreadCount + '</span>');
            }
            $(btnId).show();
          } else {
            if (badge.length > 0) badge.hide();
            $(btnId).hide();
          }

          // Dynamically update dropdown HTML in real time without page refresh
          if (res.is_main) {
            var bodyEl = $('#mainNotifDropdownBody');
            if (bodyEl.length > 0) {
              var html = buildAccessWarningNotifHtml();

              // 0. Near-expiry / expired products (all branches, so prefix the branch name)
              html += buildExpiryNotifHtml(res.expiry_list, true);

              // 1. Sub-Branch Stock Additions Section
              if (res.sub_branch_imports && res.sub_branch_imports.length > 0) {
                html += '<div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ecfdf5; font-size: 0.82rem; white-space: nowrap;">';
                html += '<span class="text-success text-truncate"><i class="fas fa-boxes mr-1"></i> ' + I18N_LAYOUT['layout.sub_branch_imports'] + '</span>';
                html += '<span class="badge badge-success font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">' + res.sub_branch_imports.length + ' ' + I18N_LAYOUT['layout.items_unit'] + '</span>';
                html += '</div>';

                res.sub_branch_imports.forEach(function(imp) {
                  var isRead = readKeys.includes('imp_' + imp.import_detail_id);
                  var costVal = parseFloat(imp.total_cost || 0);
                  var qtyVal = parseFloat(imp.quantity || 0);
                  var impJsonStr = JSON.stringify(imp).replace(/'/g, "&apos;").replace(/"/g, "&quot;");

                  html += '<div id="notif_item_imp_' + imp.import_detail_id + '" onclick="markItemAsRead(\'imp_' + imp.import_detail_id + '\', \'mainNotifBadge\'); viewSubBranchImportDetail(' + impJsonStr + ')" class="dropdown-item py-2 px-3 border-bottom" style="background-color: #ffffff; cursor: pointer; opacity: ' + (isRead ? '0.55' : '1') + ';">';
                  html += '<div class="d-flex align-items-center justify-content-between mb-1">';
                  html += '<strong class="text-dark text-truncate mr-2" style="font-size: 0.82rem;" title="' + (imp.store_name || '') + ' - ' + (imp.product_name || '') + '"><i class="fas fa-store text-info mr-1"></i> ' + (imp.store_name || I18N_LAYOUT['layout.branch_sub_default']) + ': ' + (imp.product_name || I18N_LAYOUT['layout.default_product']) + '</strong>';
                  html += '<span class="badge badge-success font-weight-bold px-2 py-0.5" style="font-size: 0.73rem;">+' + qtyVal.toLocaleString() + ' ' + (imp.unit_name || I18N_LAYOUT['layout.default_unit']) + '</span>';
                  html += '</div>';
                  html += '<div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.76rem;">';
                  html += '<span><i class="fas fa-user-tag text-info mr-1"></i> ' + (imp.creator_name || 'Admin') + '</span>';
                  html += '<span class="font-weight-bold text-success"><i class="fas fa-coins mr-1"></i> ' + costVal.toLocaleString() + ' ₭</span>';
                  html += '</div>';
                  html += '</div>';
                });
              }

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

                    html += '<div id="notif_item_main_' + alert.product_id + '" onclick="goToRestockProduct(' + JSON.stringify(alert.product_name || '') + ')" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between border-bottom flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;" title="' + I18N_LAYOUT['layout.click_to_restock'] + '">';
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
              var currentStoreId = <?php echo intval($_SESSION['store_id'] ?? 1); ?>;
              var html = buildAccessWarningNotifHtml();

              html += buildExpiryNotifHtml(res.sub_expiry, false);

              if (res.incoming_transfers && res.incoming_transfers.length > 0) {
                html += '<div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #eff6ff; font-size: 0.82rem; white-space: nowrap;">';
                html += '<span class="text-primary text-truncate"><i class="fas fa-exchange-alt mr-1"></i> ' + I18N_LAYOUT['layout.transfer_list'] + '</span>';
                html += '<span class="badge badge-primary font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">' + res.incoming_transfers.length + ' ' + I18N_LAYOUT['layout.bill_unit'] + '</span>';
                html += '</div>';

                res.incoming_transfers.forEach(function(trf) {
                  var isOutgoing = (parseInt(trf.from_store_id) === currentStoreId);
                  var targetStoreName = isOutgoing ? (trf.to_store_name || I18N_LAYOUT['layout.branch_sub_default']) : (trf.from_store_name || I18N_LAYOUT['layout.branch_main_default']);
                  var directionIcon = isOutgoing ? 'fa-paper-plane text-success' : 'fa-truck-loading text-primary';
                  var directionPrefix = isOutgoing ? (I18N_LAYOUT['layout.transfer_to'] + ' ') : (I18N_LAYOUT['layout.transfer_from'] + ' ');
                  var trfCode = trf.transfer_code || ('TRF-' + trf.transfer_id);
                  var itemSummary = trf.item_summary || I18N_LAYOUT['layout.item_transferred'];
                  var isRead = readKeys.includes('trf_' + trf.transfer_id);

                  var trfValue = Math.round(parseFloat(trf.total_value || 0));

                  html += '<div id="notif_item_trf_' + trf.transfer_id + '" onclick="markItemAsRead(\'trf_' + trf.transfer_id + '\', \'subNotifBadge\'); viewTransferDetailsModal(' + trf.transfer_id + ')" class="dropdown-item py-2 px-3 border-bottom d-flex flex-column" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden; opacity: ' + (isRead ? '0.55' : '1') + ';">';
                  html += '<div class="d-flex align-items-center justify-content-between flex-nowrap w-100">';
                  html += '<div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">';
                  html += '<i class="fas ' + directionIcon + ' mr-2" style="font-size: 0.82rem;"></i>';
                  html += '<span class="text-dark font-weight-bold" style="font-size: 0.84rem;">' + (trf.formatted_date || '') + '</span>';
                  html += '</div>';
                  html += '<div class="d-flex align-items-center text-nowrap flex-shrink-0">';
                  html += '<span class="badge badge-info font-weight-bold" style="font-size: 0.72rem;">' + (trf.total_items || 1) + ' ' + I18N_LAYOUT['layout.items_unit'] + '</span>';
                  html += '</div>';
                  html += '</div>';
                  html += '<div class="text-success font-weight-bold mt-1" style="font-size: 0.78rem;"><i class="fas fa-coins mr-1"></i> ' + trfValue.toLocaleString() + ' ₭</div>';
                  html += '</div>';
                });
              }

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

                  html += '<div id="notif_item_stk_' + stk.product_id + '" onclick="markItemAsRead(\'stk_' + stk.product_id + '\', \'subNotifBadge\')" class="dropdown-item py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden; opacity: ' + (isRead ? '0.55' : '1') + ';">';
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

              if (res.sub_new_products && res.sub_new_products.length > 0) {
                html += '<div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #f0fdf4; font-size: 0.82rem; white-space: nowrap;">';
                html += '<span class="text-success text-truncate"><i class="fas fa-sparkles mr-1"></i> ' + I18N_LAYOUT['layout.new_products'] + '</span>';
                html += '<span class="badge badge-success font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">' + res.sub_new_products.length + ' ' + I18N_LAYOUT['layout.items_unit'] + '</span>';
                html += '</div>';

                res.sub_new_products.forEach(function(np) {
                  var isRead = readKeys.includes('np_' + np.product_id);
                  html += '<div id="notif_item_np_' + np.product_id + '" onclick="markItemAsRead(\'np_' + np.product_id + '\', \'subNotifBadge\')" class="dropdown-item py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden; opacity: ' + (isRead ? '0.55' : '1') + ';">';
                  html += '<div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">';
                  html += '<i class="fas fa-sparkles text-success mr-1.5" style="font-size: 0.82rem;"></i>';
                  html += '<span class="text-dark font-weight-bold text-truncate" style="font-size: 0.82rem;" title="' + (np.product_name || '') + '">' + (np.product_name || '') + '</span>';
                  html += '</div>';
                  html += '<div class="text-nowrap flex-shrink-0">';
                  html += '<span class="badge badge-light border text-dark font-weight-bold" style="font-size: 0.76rem;">' + (parseInt(np.price || 0).toLocaleString()) + ' ₭</span>';
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
