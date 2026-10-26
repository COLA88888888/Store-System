<?php
/*
 * ສ່ວນ: ແຖບເມນູດ້ານເທິງ (Navbar)
 * ປຸ່ມເປີດ/ປິດເມນູ, ໂມງ, ກະດິ່ງແຈ້ງເຕືອນສາຂາໃຫຍ່ ແລະ ສາຂາຍ່ອຍ, ຈຳນວນວັນໃຊ້ງານທີ່ເຫຼືອ,
 * ເລືອກສີທີມ, ເລືອກພາສາ ແລະ ປຸ່ມອອກຈາກລະບົບ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- ແຖບເມນູດ້ານເທິງ (Top Navigation Bar) -->
  <nav class="main-header navbar navbar-expand navbar-dark justify-content-between" style="background-color: #244886 !important; border: none !important; border-bottom: none !important; box-shadow: none !important; height: 64px; position: relative !important; z-index: 1030 !important;">
    <!-- Left side: Menu toggle & Branch Switcher -->
    <ul class="navbar-nav align-items-center">
      <li class="nav-item">
        <a class="nav-link" id="topNavbarPushMenuBtn" href="#" role="button" style="color: #ffffff; font-size: 1.2rem; cursor: pointer;" title="<?php echo htmlspecialchars(t('layout.menu', 'ເມນູ')); ?>">
          <i class="fas fa-bars"></i>
        </a>
      </li>
      <?php
        $userStoreId = intval($_SESSION['store_id'] ?? 1);
        $userIsAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
        $userIsMain = isMainBranch($pdo, $userStoreId);
        $userCanAllBranches = canViewAllBranches($pdo);
        $allStores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $activeStoreId = getActiveStoreId($pdo);
        $currentStoreName = '';
        foreach ($allStores as $s) {
            if ($s['store_id'] == $activeStoreId) {
                $currentStoreName = $s['store_name'];
                break;
            }
        }
      ?>
      <!-- Branch Switcher Removed -->
    </ul>

    <?php
      // ດຶງຂໍ້ມູນແພັກເກັດການນຳໃຊ້ (Subscription License Info)
      $license_start = '2026-01-01';
      $license_expire = '2026-12-31';
      try {
        $cLic = $pdo->query("SELECT license_start_date, license_expire_date FROM st_tbcompanyinfo WHERE branch_id = (SELECT store_id FROM st_tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($cLic) {
          if (!empty($cLic['license_start_date'])) $license_start = $cLic['license_start_date'];
          if (!empty($cLic['license_expire_date'])) $license_expire = $cLic['license_expire_date'];
        }
      } catch (Exception $ex) {}

      $todayObj = new DateTime(date('Y-m-d'));
      $expireObj = new DateTime($license_expire);
      $daysRemaining = 0;
      if ($expireObj >= $todayObj) {
        $diff = $todayObj->diff($expireObj);
        $daysRemaining = (int)$diff->format('%a');
      }

      $formattedStart = date('d/m/Y', strtotime($license_start));
      $formattedExpire = date('d/m/Y', strtotime($license_expire));

      // The "ແພັກເກັດ" badge is about THIS user's own personal access limit only (not the
      // shop-wide license, which caused confusion before) — shows their remaining days when
      // the owner has set one, or "ບໍ່ມີກຳນົດ" (unlimited) when no personal limit applies.
      $displayDaysRemaining = $myAccessDaysLeft;

      // Low Stock & Out of Stock Alerts for Main Branch & All Branches (Grouped by store)
      $lowStockList = getLowStockAlerts($pdo);
      $lowStockCount = count($lowStockList);

      $groupedLowStock = [];
      foreach ($lowStockList as $alert) {
          $stId = $alert['store_id'];
          if (!isset($groupedLowStock[$stId])) {
              $groupedLowStock[$stId] = [
                  'store_id'   => $alert['store_id'],
                  'store_name' => $alert['store_name'],
                  'is_main'    => $alert['is_main'] ?? 0,
                  'items'      => []
              ];
          }
          $groupedLowStock[$stId]['items'][] = $alert;
      }
    ?>

    <!-- ບັອກສະແກນ + ປຸ່ມຈໍລູກຄ້າ "ຕົວແທນ" ຂອງໜ້າຂາຍ: ສະແດງໃນ Navbar ຂະນະໜ້າຂາຍກຳລັງໂຫຼດ (ຣີເຟຣດ / ເປີດໜ້າຂາຍ)
         ບໍ່ໃຫ້ບັອກສະແກນຫາຍໄປ — ໜ້າຂາຍໂຫຼດແລ້ວ ບັອກແທ້ (ໃນ iframe) ຂຶ້ນແທນທີ່ ແລະ ຕົວນີ້ເຊື່ອງ.
         ຄວບຄຸມໂດຍ js_pos_navbar.php (apply), CSS: themes/dashboard-pos-navbar.css -->
    <div id="posNavGhost" class="pos-nav-ghost" aria-hidden="true" style="display: none;">
      <div class="pos-nav-ghost-search">
        <i class="fas fa-search"></i>
        <span><?php echo htmlspecialchars(t('pos.search_placeholder', 'ສະແກນບາໂຄ້ດ ຫຼື ພິມຊື່ສິນຄ້າ...')); ?></span>
      </div>
      <div class="pos-nav-ghost-btn">
        <i class="fas fa-desktop"></i> <?php echo htmlspecialchars(t('pos.customer_display_btn', 'ຈໍລູກຄ້າ')); ?>
      </div>
    </div>
    <script>
      // ສະແດງທັນທີຕັ້ງແຕ່ໜ້າເລີ່ມແຕ້ມ ຖ້າໜ້າທີ່ກຳລັງເປີດແມ່ນໜ້າຂາຍ (ກ່ອນ js_pos_navbar.php ໂຫຼດ)
      (function () {
        try {
          var g = document.getElementById('posNavGhost');
          var src = sessionStorage.getItem('StoreMini.currentIframeSrc') || '';
          var l = localStorage.getItem('StoreMini.pos_nav_left'), r = localStorage.getItem('StoreMini.pos_nav_right_dist');
          if (l) g.style.left = l + 'px';
          if (r) g.style.right = r + 'px';
          if (window.innerWidth >= 992 && /pos\/pos\.php/.test(src)) g.classList.add('show');
        } catch (e) {}
      })();
    </script>

    <!-- Right side: Subscription Days Remaining + Notifications Bell + Logout -->
    <ul class="navbar-nav ml-auto align-items-center" style="gap: 12px; font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      
      <!-- Notifications Dropdown (Low Stock & Out of Stock Warning Grouped by Branch - Only for Main Branch / Admin) -->
      <?php if ($userCanAllBranches): ?>
        <li class="nav-item dropdown">
          <a class="nav-link text-white position-relative px-2 d-flex align-items-center" data-toggle="dropdown" href="#" onclick="markNotifAsRead('mainNotifBadge')" data-i18n-title="layout.notif_main_title" title="<?php echo htmlspecialchars(t('layout.notif_main_title', 'ແຈ້ງເຕືອນສິນຄ້າໃກ້ສິນສຸດ/ສິນຄ້າໝົດ')); ?>" style="font-size: 1.25rem; cursor: pointer;">
            <i id="mainNotifBellIcon" class="fas fa-bell text-white"></i>
            <span id="mainNotifBadge" class="badge badge-danger font-weight-bold position-absolute" style="top: 2px; right: -2px; font-size: 0.68rem; border-radius: 10px; padding: 2px 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.3); <?php echo ($lowStockCount > 0) ? '' : 'display: none;'; ?>">
              <?php echo $lowStockCount; ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notif-dropdown-box shadow-lg border-0">
            <div class="dropdown-header font-weight-bold d-flex text-white justify-content-between align-items-center py-2.5 px-3" style="background-color: #244886 !important;">
              <span class="text-truncate mr-2"><i class="fas fa-exclamation-triangle text-warning mr-1.5"></i> <span data-i18n="layout.notif_main_header"><?php echo htmlspecialchars(t('layout.notif_main_header', 'ແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດ/ໝົດ')); ?></span> (<span id="mainNotifTitleCount"><?php echo $lowStockCount; ?></span>)</span>
              <button type="button" id="btnMarkAllMain" class="btn btn-xs btn-outline-light font-weight-bold flex-shrink-0" onclick="markAllNotifsAsRead('mainNotifBadge')" data-i18n="layout.mark_all_read" style="border-radius: 6px; font-size: 0.73rem; <?php echo ($lowStockCount > 0) ? '' : 'display: none;'; ?>"><?php echo htmlspecialchars(t('layout.mark_all_read', 'ອ່ານທັງໝົດ')); ?></button>
            </div>
            <div class="dropdown-divider m-0"></div>
            
            <div id="mainNotifDropdownBody" style="max-height: 380px; overflow-y: auto;">
              <?php if ($lowStockCount > 0 && !empty($groupedLowStock)): ?>
                <?php foreach ($groupedLowStock as $stId => $branchGroup): ?>
                  <!-- ຫົວຂໍ້ແຍກຕາມສາຂາ (Branch Section Header) -->
                  <div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #f1f5f9; font-size: 0.82rem; white-space: nowrap;">
                    <span class="text-truncate">
                      <i class="fas fa-store text-primary mr-1"></i> <?php echo htmlspecialchars($branchGroup['store_name']); ?>
                      <?php if (!empty($branchGroup['is_main'])): ?>
                        <small class="text-muted"><?php echo htmlspecialchars(t('layout.branch_main_suffix', '(ສາງຫຼັກ)')); ?></small>
                      <?php endif; ?>
                    </span>
                    <span class="badge badge-warning text-dark font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;">
                      <?php echo count($branchGroup['items']); ?> <?php echo htmlspecialchars(t('layout.items_unit', 'ລາຍການ')); ?>
                    </span>
                  </div>

                  <!-- ລາຍການສິນຄ້າໃນສາຂານັ້ນ (Single Line Flex Row) -->
                  <?php foreach ($branchGroup['items'] as $alert): ?>
                    <?php 
                      $isOutOfStock = intval($alert['qty']) <= 0;
                      $badgeClass = $isOutOfStock ? 'badge-danger' : 'badge-warning text-dark';
                      $qtyLabel = $isOutOfStock ? t('layout.out_of_stock', 'ໝົດແລ້ວ (0)') : t('layout.remaining', 'ເຫຼືອ') . ' ' . $alert['qty'] . ' ' . htmlspecialchars($alert['unit'] ?: t('layout.default_unit', 'ອັນ'));
                    ?>
                    <div id="notif_item_main_<?php echo $alert['product_id']; ?>" onclick="markItemAsRead('main_<?php echo $alert['product_id']; ?>', 'mainNotifBadge'); goToStockList(<?php echo (int)($alert['store_id'] ?? 0); ?>, <?php echo $isOutOfStock ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars(addslashes((string)$alert['product_id'])); ?>')" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between border-bottom flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;">
                      <div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">
                        <i class="fas fa-exclamation-circle <?php echo $isOutOfStock ? 'text-danger' : 'text-warning'; ?> mr-1.5" style="font-size: 0.82rem;"></i>
                        <strong class="text-dark text-truncate" style="font-size: 0.82rem;" title="<?php echo htmlspecialchars($alert['product_name']); ?>"><?php echo htmlspecialchars($alert['product_name']); ?></strong>
                      </div>
                      <div class="text-nowrap flex-shrink-0 ml-2">
                        <span class="badge <?php echo $badgeClass; ?> font-weight-bold px-2 py-1" style="font-size: 0.74rem; border-radius: 6px;"><?php echo $qtyLabel; ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center py-4 text-muted">
                  <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                  <span style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('layout.no_low_stock', 'ບໍ່ມີສິນຄ້າໃກ້ໝົດ ຫຼື ໝົດແລ້ວ')); ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </li>
      <?php endif; ?>

      <!-- Sub-Branch Notifications Dropdown (Incoming Transfers + Own Low Stock + New Products) -->
      <?php if (!$userCanAllBranches): ?>
        <?php
          $subStoreId = intval($_SESSION['store_id'] ?? 1);
          $incomingTransfers = getIncomingTransfersForStore($pdo, $subStoreId);
          $subLowStock = getLowStockAlerts($pdo, $subStoreId);
          $subNewProducts = getNewProductsForStore($pdo, $subStoreId);
          
          $totalSubNotifications = count($incomingTransfers) + count($subLowStock) + count($subNewProducts);
        ?>
        <li class="nav-item dropdown">
          <a class="nav-link text-white position-relative px-2 d-flex align-items-center" data-toggle="dropdown" href="#" data-i18n-title="layout.notif_sub_title" title="<?php echo htmlspecialchars(t('layout.notif_sub_title', 'ແຈ້ງເຕືອນສາຂາຍ່ອຍ')); ?>" style="font-size: 1.25rem; cursor: pointer;">
            <i id="subNotifBellIcon" class="fas fa-bell text-white"></i>
            <span id="subNotifBadge" class="badge badge-danger font-weight-bold position-absolute" style="top: 2px; right: -2px; font-size: 0.68rem; border-radius: 10px; padding: 2px 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.3); <?php echo ($totalSubNotifications > 0) ? '' : 'display: none;'; ?>">
              <?php echo $totalSubNotifications; ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notif-dropdown-box shadow-lg border-0">
            <div class="dropdown-header font-weight-bold d-flex text-white justify-content-between align-items-center py-2.5 px-3" style="background-color: #244886 !important;">
              <span class="text-truncate mr-2"><i class="fas fa-bell text-white mr-1.5"></i> <span data-i18n="layout.notif_sub_header"><?php echo htmlspecialchars(t('layout.notif_sub_header', 'ແຈ້ງເຕືອນສາຂາ')); ?></span> (<span id="subNotifTitleCount"><?php echo $totalSubNotifications; ?></span>)</span>
              <button type="button" id="btnMarkAllSub" class="btn btn-xs btn-outline-light font-weight-bold flex-shrink-0" onclick="markAllNotifsAsRead('subNotifBadge')" data-i18n="layout.mark_all_read" style="border-radius: 6px; font-size: 0.73rem; <?php echo ($totalSubNotifications > 0) ? '' : 'display: none;'; ?>"><?php echo htmlspecialchars(t('layout.mark_all_read', 'ອ່ານທັງໝົດ')); ?></button>
            </div>
            <div class="dropdown-divider m-0"></div>
            
            <div id="subNotifDropdownBody" style="max-height: 380px; overflow-y: auto;">
              <!-- 1. ລາຍການໂອນສິນຄ້າ (Incoming & Outgoing Stock Transfers) -->
              <?php if (!empty($incomingTransfers)): ?>
                <div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #eff6ff; font-size: 0.82rem; white-space: nowrap;">
                  <span class="text-primary text-truncate"><i class="fas fa-exchange-alt mr-1"></i> <?php echo htmlspecialchars(t('layout.transfer_list', 'ລາຍການໂອນສິນຄ້າ')); ?></span>
                  <span class="badge badge-primary font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;"><?php echo count($incomingTransfers); ?> <?php echo htmlspecialchars(t('layout.bill_unit', 'ໃບບິນ')); ?></span>
                </div>
                <?php foreach ($incomingTransfers as $trf): ?>
                  <?php
                    $isOutgoing = (intval($trf['from_store_id']) === intval($subStoreId));
                    $targetStoreName = $isOutgoing ? ($trf['to_store_name'] ?: t('layout.branch_sub_default', 'ສາຂາຍ່ອຍ')) : ($trf['from_store_name'] ?: t('layout.branch_main_default', 'ສາຂາໃຫຍ່'));
                    $directionIcon = $isOutgoing ? 'fa-paper-plane text-success' : 'fa-truck-loading text-primary';
                    $directionPrefix = $isOutgoing ? t('layout.transfer_to', 'ໂອນໄປຫາ') . ' ' : t('layout.transfer_from', 'ໂອນມາຈາກ') . ' ';
                  ?>
                  <div id="notif_item_trf_<?php echo $trf['transfer_id']; ?>" onclick="markItemAsRead('trf_<?php echo $trf['transfer_id']; ?>', 'subNotifBadge'); viewTransferDetailsModal(<?php echo intval($trf['transfer_id']); ?>)" class="dropdown-item py-2 px-3 border-bottom d-flex flex-column" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden; transition: background 0.15s;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='#ffffff'">
                    <div class="d-flex align-items-center justify-content-between flex-nowrap w-100">
                      <div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">
                        <i class="fas <?php echo $directionIcon; ?> mr-2" style="font-size: 0.82rem;"></i>
                        <span class="text-dark font-weight-bold" style="font-size: 0.84rem;"><?php echo date('d/m/Y H:i', strtotime($trf['transfer_date'])); ?></span>
                      </div>
                      <div class="d-flex align-items-center text-nowrap flex-shrink-0">
                        <span class="badge badge-info font-weight-bold" style="font-size: 0.72rem;"><?php echo intval($trf['total_items'] ?? 1); ?> ລາຍການ</span>
                      </div>
                    </div>
                    <div class="text-success font-weight-bold mt-1" style="font-size: 0.78rem;">
                      <i class="fas fa-coins mr-1"></i> <?php echo htmlspecialchars(formatCurrency($trf['total_value'] ?? 0)); ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- 2. ສິນຄ້າໃນສາຂາໂຕເອງໃກ້ໝົດ / ໝົດແລ້ວ (Low Stock & Out of Stock in Sub-Branch) -->
              <?php if (!empty($subLowStock)): ?>
                <div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #fff7ed; font-size: 0.82rem; white-space: nowrap;">
                  <span class="text-danger text-truncate"><i class="fas fa-exclamation-triangle mr-1"></i> ສິນຄ້າໃກ້ໝົດ / ໝົດແລ້ວ</span>
                  <span class="badge badge-danger font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;"><?php echo count($subLowStock); ?> ລາຍການ</span>
                </div>
                <?php foreach ($subLowStock as $stk): ?>
                  <?php 
                    $isZero = (intval($stk['qty']) <= 0);
                    $bClass = $isZero ? 'badge-danger' : 'badge-warning text-dark';
                    $lbl = $isZero ? 'ໝົດແລ້ວ (0)' : 'ເຫຼືອ ' . $stk['qty'] . ' ' . htmlspecialchars($stk['unit'] ?: 'ອັນ');
                    $icoClass = $isZero ? 'text-danger' : 'text-warning';
                  ?>
                  <div id="notif_item_stk_<?php echo $stk['product_id']; ?>" onclick="markItemAsRead('stk_<?php echo $stk['product_id']; ?>', 'subNotifBadge'); goToStockList(<?php echo (int)($stk['store_id'] ?? 0); ?>, <?php echo $isZero ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars(addslashes((string)$stk['product_id'])); ?>')" class="dropdown-item py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;">
                    <div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">
                      <i class="fas fa-exclamation-circle <?php echo $icoClass; ?> mr-1.5" style="font-size: 0.82rem;"></i>
                      <span class="text-dark font-weight-bold text-truncate" style="font-size: 0.82rem;" title="<?php echo htmlspecialchars($stk['product_name']); ?>"><?php echo htmlspecialchars($stk['product_name']); ?></span>
                    </div>
                    <div class="text-nowrap flex-shrink-0">
                      <span class="badge <?php echo $bClass; ?> font-weight-bold px-2 py-1" style="font-size: 0.74rem;"><?php echo $lbl; ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <!-- 3. ສິນຄ້າໃໝ່ (New Products Arrived/Added) -->
              <?php if (!empty($subNewProducts)): ?>
                <div class="px-3 py-1.5 font-weight-bold text-dark border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #f0fdf4; font-size: 0.82rem; white-space: nowrap;">
                  <span class="text-success text-truncate"><i class="fas fa-sparkles mr-1"></i> ສິນຄ້າໃໝ່ເພີ່ມເຂົ້າສາຂາ</span>
                  <span class="badge badge-success font-weight-bold flex-shrink-0 ml-2" style="font-size: 0.7rem; border-radius: 6px;"><?php echo count($subNewProducts); ?> ລາຍການ</span>
                </div>
                <?php foreach ($subNewProducts as $np): ?>
                  <div id="notif_item_np_<?php echo $np['product_id']; ?>" onclick="markItemAsRead('np_<?php echo $np['product_id']; ?>', 'subNotifBadge')" class="dropdown-item py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;">
                    <div class="d-flex align-items-center text-nowrap mr-2" style="overflow: hidden; text-overflow: ellipsis; min-width: 0;">
                      <i class="fas fa-sparkles text-success mr-1.5" style="font-size: 0.82rem;"></i>
                      <span class="text-dark font-weight-bold text-truncate" style="font-size: 0.82rem;" title="<?php echo htmlspecialchars($np['product_name']); ?>"><?php echo htmlspecialchars($np['product_name']); ?></span>
                      <small class="text-muted ml-1.5"><?php echo date('d/m/Y', strtotime($np['created_at'] ?? 'now')); ?></small>
                    </div>
                    <div class="text-nowrap flex-shrink-0">
                      <span class="badge badge-light border text-dark font-weight-bold" style="font-size: 0.76rem;"><?php echo number_format($np['price'], 0); ?> ₭</span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if ($totalSubNotifications === 0): ?>
                <div class="text-center py-4 text-muted">
                  <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                  <span style="font-size: 0.85rem;">ບໍ່ມີການແຈ້ງເຕືອນໃໝ່ໃນສາຂາ</span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </li>
      <?php endif; ?>

      <!-- Subscription info — shown to everyone (not just main-branch/admin), since a
           sub-branch employee with a personal access limit needs to see their own countdown too. -->
      <li class="nav-item d-none d-md-flex align-items-center text-white mr-1" style="font-size: 0.85rem; font-weight: 600;">
        <span class="d-inline-flex align-items-center">
          <span data-i18n="layout.package_label"><?php echo htmlspecialchars(t('layout.package_label', 'ແພັກເກັດ')); ?></span>:<?php if ($displayDaysRemaining !== null): ?><span class="mx-1 text-white" style="font-size: 0.95rem; font-weight: 800"><?php echo $displayDaysRemaining; ?></span> <span data-i18n="layout.days_unit"><?php echo htmlspecialchars(t('layout.days_unit', 'ວັນ')); ?></span><?php else: ?><span class="mx-1 text-white" style="font-size: 0.95rem; font-weight: 800" data-i18n="layout.package_unlimited"><?php echo htmlspecialchars(t('layout.package_unlimited', 'ບໍ່ມີກຳນົດ')); ?></span><?php endif; ?>
        </span>
      </li>

      <!-- Theme Colour Picker (recolours sidebar + navbar, saved per-browser) -->
      <li class="nav-item dropdown d-flex align-items-center mr-2">
        <a class="nav-link p-0 d-flex align-items-center justify-content-center text-white"
           href="#"
           id="themeColorDropdown"
           role="button"
           data-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false"
           style="font-size: 1.15rem; cursor: pointer; width: 32px; height: 32px;"
           data-i18n-title="layout.theme_color_title"
           title="<?php echo htmlspecialchars(t('layout.theme_color_title', 'ປ່ຽນສີພື້ນຫຼັງ')); ?>">
          <i class="fas fa-palette"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right shadow-lg p-2"
             aria-labelledby="themeColorDropdown"
             style="min-width: 188px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); background: #ffffff;">
          <div class="px-1 pb-2 font-weight-bold text-muted" style="font-size: 0.78rem;" data-i18n="layout.theme_color_title"><?php echo htmlspecialchars(t('layout.theme_color_title', 'ປ່ຽນສີພື້ນຫຼັງ')); ?></div>
          <div class="d-flex flex-wrap" style="gap: 8px;">
            <?php
              $posThemeColors = ['#244886','#1d4ed8','#0369a1','#0f766e','#166534','#4d7c0f','#b45309','#c2410c','#be123c','#9f1239','#6d28d9','#7c3aed','#374151','#111827','#0b7285'];
              foreach ($posThemeColors as $tc):
            ?>
              <button type="button" class="pos-theme-swatch" data-color="<?php echo $tc; ?>" title="<?php echo $tc; ?>" style="background: <?php echo $tc; ?>;"></button>
            <?php endforeach; ?>
          </div>
          <button type="button" id="posThemeReset" class="btn btn-sm btn-light border w-100 mt-2 font-weight-bold" style="font-size: 0.78rem; border-radius: 8px;" data-i18n="layout.theme_color_reset"><?php echo htmlspecialchars(t('layout.theme_color_reset', 'ຄ່າເລີ່ມຕົ້ນ')); ?></button>
        </div>
      </li>

      <!-- Language Switcher Dropdown (Flags Only: Laos / Thailand / China / US) -->
      <?php $currentLangCode = getCurrentLang(); ?>
      <li class="nav-item dropdown d-flex align-items-center mr-2">
        <a class="nav-link p-0 d-flex align-items-center justify-content-center" 
           href="#" 
           id="langDropdown" 
           role="button" 
           data-toggle="dropdown" 
           aria-haspopup="true" 
           aria-expanded="false" 
           style="background: transparent; border: none; padding: 0 !important;"
           data-i18n-title="layout.lang_switch_title"
           title="<?php echo htmlspecialchars(t('layout.lang_switch_title', 'ປ່ຽນພາສາ')); ?>">
          <img id="currentLangFlagImg" src="../assets/img/flag_img/<?php echo POS_LANG_META[$currentLangCode]['img']; ?>"
               alt="<?php echo $currentLangCode; ?>"
               style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; box-shadow: 0 1.5px 4px rgba(0,0,0,0.35); border: 2px solid rgba(255,255,255,0.8);">
        </a>
        <div class="dropdown-menu dropdown-menu-right shadow-lg py-1.5 px-1" 
             aria-labelledby="langDropdown" 
             style="min-width: 60px; width: 60px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.08); background: #ffffff; text-align: center;">
          <?php foreach (POS_LANG_META as $langCode => $meta): ?>
            <a class="dropdown-item p-1.5 d-flex align-items-center justify-content-center my-1 lang-flag-btn<?php echo $currentLangCode === $langCode ? ' active' : ''; ?>" 
               href="javascript:void(0);" 
               data-lang="<?php echo htmlspecialchars($langCode); ?>" 
               style="border-radius: 6px; background: <?php echo $currentLangCode === $langCode ? '#eaf2ff' : 'transparent'; ?>; transition: background 0.15s;" 
               title="<?php echo htmlspecialchars($meta['label']); ?>">
              <img src="../assets/img/flag_img/<?php echo $meta['img']; ?>"
                   alt="<?php echo $langCode; ?>"
                   style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; box-shadow: 0 1.5px 3px rgba(0,0,0,0.25);">
            </a>
          <?php endforeach; ?>
        </div>
      </li>

      <!-- Logout button -->
      <li class="nav-item">
        <a class="nav-link logout-nav-btn font-weight-bold" href="javascript:void(0);" onclick="confirmLogout()" data-i18n-title="layout.logout" title="<?php echo htmlspecialchars(t('layout.logout', 'ອອກຈາກລະບົບ')); ?>">
          <i class="fas fa-power-off"></i>
          <span class="d-none d-md-inline" data-i18n="layout.logout"><?php echo htmlspecialchars(t('layout.logout', 'ອອກຈາກລະບົບ')); ?></span>
        </a>
      </li>
    </ul>
  </nav>

