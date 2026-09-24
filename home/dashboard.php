<?php
// ============================================================
// dashboard.php - UNIFIED MASTER LAYOUT (ລະບົບດາດສ໌ບອດຫຼັກ)
// ============================================================
session_start();

// 1. ກວດສອບຄວາມປອດໄພ ແລະ ການເຂົ້າສູ່ລະບົບ
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?expired=1');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lang/translator.php';

$base_path = '../';
$site_name = 'Wlaodev POS';
$site_logo = '../assets/img/logosystem/Wlaodev.jpg';

// Own-account access expiry (owner-only field, set from Users Management). Two flavors:
// - $myAccessDaysLeft: unbounded, used to make the "ແພັກເກັດ" badge show THIS user's own
//   remaining days instead of the shop-wide license days, whenever they have a personal limit.
// - $myAccessDaysLeftForWarning: only set when <=10 days away, drives the notification-bell
//   warning item so the user can ask the owner to extend it before auth/access_expired.php locks them out.
$myAccessDaysLeft = null;
$myAccessDaysLeftForWarning = null;

if (!empty($_SESSION['user_id'])) {
    try {
        $uFetch = $pdo->prepare("SELECT profile_img, fname, lname, username, status, access_expire_date FROM tbuser WHERE Id = ? LIMIT 1");
        $uFetch->execute([$_SESSION['user_id']]);
        if ($userRow = $uFetch->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($userRow['profile_img'])) $_SESSION['profile_img'] = $userRow['profile_img'];
            if (isset($userRow['fname'])) $_SESSION['fname'] = $userRow['fname'];
            if (isset($userRow['lname'])) $_SESSION['lname'] = $userRow['lname'];
            if (isset($userRow['status'])) $_SESSION['status'] = $userRow['status'];

            if (intval($_SESSION['user_id']) !== 1 && !empty($userRow['access_expire_date'])) {
                $daysLeft = (int)ceil((strtotime($userRow['access_expire_date']) - strtotime(date('Y-m-d'))) / 86400);
                if ($daysLeft > 0) {
                    $myAccessDaysLeft = $daysLeft;
                    if ($daysLeft <= 10) {
                        $myAccessDaysLeftForWarning = $daysLeft;
                    }
                }
            }
        }
    } catch (Exception $e) {}
}

$display_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if ($display_name === '') {
    $display_name = $_SESSION['username'] ?? 'admin';
}

$profile_img = $_SESSION['profile_img'] ?? 'default.png';
if (empty($profile_img) || !file_exists(__DIR__ . '/../assets/img/users/' . $profile_img)) {
    $profile_img = 'default.png';
}
$profile_img_path = '../assets/img/users/' . $profile_img;

// ດຶງໜ້າເລີ່ມຕົ້ນຕາມສິດ (Default Page Route based on permissions)
$default_iframe_src = 'home.php';
if (!hasPermission('dashboard')) {
    if (hasPermission('sale')) {
        $default_iframe_src = '../pages/pos/pos.php';
    } elseif (hasPermission('customers')) {
        $default_iframe_src = '../pages/customers/customers.php';
    } elseif (hasPermission('categories')) {
        $default_iframe_src = '../pages/categories/categories.php';
    } elseif (hasPermission('products')) {
        $default_iframe_src = '../pages/products/products.php';
    } elseif (hasPermission('import_stock')) {
        $default_iframe_src = '../pages/import_stock/import_stock.php';
    } elseif (hasPermission('import_list')) {
        $default_iframe_src = '../pages/import_stock/import_list.php';
    } elseif (hasPermission('stock_transfer')) {
        $default_iframe_src = '../pages/import_stock/stock_transfer.php';
    } elseif (hasPermission('transfer_history')) {
        $default_iframe_src = '../pages/import_stock/transfer_history.php';
    } elseif (hasPermission('accounting')) {
        $default_iframe_src = '../pages/bank/bank.php';
    } elseif (hasPermission('report') || hasPermission('daily_report') || hasPermission('all_sales') || hasPermission('best_seller') || hasPermission('profit_cost') || hasPermission('financial') || hasPermission('category_sales') || hasPermission('delete_bills')) {
        $default_iframe_src = '../pages/reports/daily_report.php';
    } elseif (hasPermission('users')) {
        $default_iframe_src = '../pages/users_manage/users_manage.php';
    } elseif (hasPermission('permissions')) {
        $default_iframe_src = '../pages/permissions/permissions.php';
    } elseif (hasPermission('setup') || hasPermission('branches')) {
        $default_iframe_src = '../pages/branches/branches.php';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="google" content="notranslate">
  <title><?php echo htmlspecialchars($site_name); ?></title>
  <link rel="shortcut icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/jpeg">
  <link rel="icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/jpeg">
  
  <script>
    (function() {
      // ກວດສອບວ່າ Tab ນີ້ໄດ້ຜ່ານການ Login ໂດຍກົງຫຼືບໍ່ (ຖ້າກັອບປີ້ Link ມາວາງຢູ່ Tab ໃໝ່ -> ເດັ້ງໄປ Login ທັນທີ)
      try {
        var activeTabToken = sessionStorage.getItem('pos_tab_active');
        if (!activeTabToken || activeTabToken.indexOf('TOKEN_') !== 0) {
          window.location.href = '../auth/logout.php?expired=1';
          return;
        }
        var savedSrc = sessionStorage.getItem('currentIframeSrc') || '';
        var isPos = savedSrc.indexOf('pos.php') !== -1 || savedSrc.indexOf('pos/') !== -1;
        if (isPos) {
          document.documentElement.classList.add('sidebar-collapse');
        }
      } catch(e) {
        window.location.href = '../auth/logout.php?expired=1';
      }
    })();
  </script>

  <!-- Local Fonts & Styles -->
  <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/pages/menu-sidebar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/global-custom.css?v=<?php echo time(); ?>">

  <style>
    /* Critical anti-flash scrollbar rules (Prevent native large scrollbar on refresh) */
    html, body, .wrapper, .main-sidebar, .sidebar, .os-viewport, .os-content {
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    html::-webkit-scrollbar, body::-webkit-scrollbar, .main-sidebar::-webkit-scrollbar, .sidebar::-webkit-scrollbar, .os-viewport::-webkit-scrollbar, .os-content::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
    }

    /* Desktop View: Keep original default sizes */
    #topNavbarPushMenuBtn i {
      font-size: 1.2rem;
    }
    #mainNotifBellIcon,
    #subNotifBellIcon {
      font-size: 1.25rem;
    }
    .logout-nav-btn {
      font-size: 0.95rem;
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 8px;
    }
    .logout-nav-btn i {
      font-size: 1.1rem;
      margin-right: 4px;
    }
    .logout-nav-btn:hover {
      background: rgba(255, 255, 255, 0.3) !important;
    }

    /* Slim & Modern Custom Scrollbars */
    ::-webkit-scrollbar {
      width: 5px !important;
      height: 5px !important;
    }
    ::-webkit-scrollbar-track {
      background: #f1f5f9 !important;
      border-radius: 8px !important;
    }
    ::-webkit-scrollbar-thumb {
      background: #cbd5e1 !important;
      border-radius: 8px !important;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8 !important;
    }
    * {
      scrollbar-width: thin !important;
      scrollbar-color: #cbd5e1 #f1f5f9 !important;
    }

    #mainNotifDropdownBody::-webkit-scrollbar,
    #subNotifDropdownBody::-webkit-scrollbar,
    .notif-dropdown-box::-webkit-scrollbar,
    .dropdown-menu::-webkit-scrollbar {
      width: 4px !important;
    }
    #mainNotifDropdownBody::-webkit-scrollbar-thumb,
    #subNotifDropdownBody::-webkit-scrollbar-thumb,
    .notif-dropdown-box::-webkit-scrollbar-thumb,
    .dropdown-menu::-webkit-scrollbar-thumb {
      background: #94a3b8 !important;
      border-radius: 10px !important;
    }

    /* Mobile View (screen width <= 768px): Only enlarge icons for mobile phone screens */
    @media (max-width: 768px) {
      #topNavbarPushMenuBtn i,
      #mainNotifBellIcon,
      #subNotifBellIcon,
      .logout-nav-btn i {
        font-size: 1.7rem !important;
      }
      
      .nav-link {
        padding-left: 8px !important;
        padding-right: 8px !important;
      }
      
      #mainNotifBadge, #subNotifBadge {
        top: -2px !important;
        right: -3px !important;
        font-size: 0.72rem !important;
        padding: 3px 6px !important;
      }
    }

    .notif-dropdown-box {
      border-radius: 12px !important;
      min-width: 270px !important;
      width: 280px !important;
      max-width: 90vw !important;
      padding: 0 !important;
      overflow: hidden !important;
    }

    /* Theme colour picker swatches */
    .pos-theme-swatch {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      border: 2px solid #fff;
      box-shadow: 0 0 0 1px rgba(0,0,0,0.18);
      padding: 0;
      cursor: pointer;
      transition: transform 0.12s ease;
    }
    .pos-theme-swatch:hover { transform: scale(1.15); }
    .pos-theme-swatch.active {
      box-shadow: 0 0 0 2px #fff, 0 0 0 4px #111827 !important;
    }
  </style>


  <!-- Scripts -->
  <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
</head>
<body class="hold-transition sidebar-mini sidebar-no-expand layout-fixed">
<script>
  if (document.documentElement.classList.contains('sidebar-collapse')) {
    document.body.classList.add('sidebar-collapse');
  }
</script>

<div class="wrapper">

  <!-- ແຖບເມນູດ້ານເທິງ (Top Navigation Bar) -->
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
        $allStores = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
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
        $cLic = $pdo->query("SELECT license_start_date, license_expire_date FROM tbcompanyinfo WHERE branch_id = (SELECT store_id FROM tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
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

    <!-- Right side: Subscription Days Remaining + Notifications Bell + Logout -->
    <ul class="navbar-nav ml-auto align-items-center" style="gap: 12px; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      
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
                    <div id="notif_item_main_<?php echo $alert['product_id']; ?>" onclick="markItemAsRead('main_<?php echo $alert['product_id']; ?>', 'mainNotifBadge')" class="dropdown-item py-2 px-3 d-flex align-items-center justify-content-between border-bottom flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;">
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
                  <div id="notif_item_stk_<?php echo $stk['product_id']; ?>" onclick="markItemAsRead('stk_<?php echo $stk['product_id']; ?>', 'subNotifBadge')" class="dropdown-item py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-nowrap" style="background-color: #ffffff; cursor: pointer; white-space: nowrap; overflow: hidden;">
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

  <script>
    // I18N_LAYOUT: translated strings used by JS-rendered navbar/notification markup
    // (kept in sync with lang/<locale>/layout.php so live-polled notifications stay translated too)
    var I18N_LAYOUT = <?php echo tjson([
        'layout.package_label' => 'ແພັກເກັດ',
        'layout.days_unit' => 'ວັນ',
        'layout.package_unlimited' => 'ບໍ່ມີກຳນົດ',
        'layout.access_expiry_warning_title' => 'ອາຍຸການນຳໃຊ້ໃກ້ໝົດ',
        'layout.access_expiry_warning_text' => 'ບັນຊີຂອງທ່ານຈະໝົດອາຍຸໃນອີກ %d ມື້. ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານເພື່ອຕໍ່ອາຍຸການນຳໃຊ້.',
        'layout.access_expiry_warning_text_today' => 'ບັນຊີຂອງທ່ານຈະໝົດອາຍຸໃນມື້ນີ້! ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານເພື່ອຕໍ່ອາຍຸການນຳໃຊ້.',
        'layout.branch_main_suffix' => '(ສາງຫຼັກ)',
        'layout.items_unit' => 'ລາຍການ',
        'layout.out_of_stock' => 'ໝົດແລ້ວ (0)',
        'layout.remaining' => 'ເຫຼືອ',
        'layout.default_unit' => 'ອັນ',
        'layout.no_low_stock' => 'ບໍ່ມີສິນຄ້າໃກ້ໝົດ ຫຼື ໝົດແລ້ວ',
        'layout.no_new_notif' => 'ບໍ່ມີການແຈ້ງເຕືອນໃໝ່',
        'layout.no_sub_notif' => 'ບໍ່ມີການແຈ້ງເຕືອນໃໝ່ໃນສາຂາ',
        'layout.transfer_list' => 'ລາຍການໂອນສິນຄ້າ',
        'layout.bill_unit' => 'ໃບບິນ',
        'layout.transfer_to' => 'ໂອນໄປຫາ',
        'layout.transfer_from' => 'ໂອນມາຈາກ',
        'layout.branch_sub_default' => 'ສາຂາຍ່ອຍ',
        'layout.branch_main_default' => 'ສາຂາໃຫຍ່',
        'layout.item_transferred' => 'ສິນຄ້າໂອນ',
        'layout.low_stock_own' => 'ສິນຄ້າໃກ້ໝົດ / ໝົດແລ້ວ',
        'layout.new_products' => 'ສິນຄ້າໃໝ່ເພີ່ມເຂົ້າສາຂາ',
        'layout.sub_branch_imports' => 'ສາຂາຍ່ອຍເພີ່ມສິນຄ້າເຂົ້າ',
        'layout.click_to_restock' => 'ກົດເພື່ອໄປໜ້ານຳເຂົ້າສິນຄ້າ',
        'layout.default_product' => 'ສິນຄ້າ',
        'layout.logout' => 'ອອກຈາກລະບົບ',
        'layout.logout_confirm_title' => 'ຢືນຢັນການອອກຈາກລະບົບ',
        'layout.logout_confirm_text' => 'ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ຫຼືບໍ່?',
        'layout.cancel' => 'ຍົກເລີກ',
    ]); ?>;

    // Own account's remaining access days (owner-only field, Users Management) — null when
    // unlimited, not applicable (owner account), or more than 10 days away. Folded into
    // whichever notification bell (main/sub) this session sees — see pollLiveNotifications().
    var MY_ACCESS_DAYS_LEFT = <?php echo ($myAccessDaysLeftForWarning === null) ? 'null' : intval($myAccessDaysLeftForWarning); ?>;

    // I18N_ALL_LAYOUT: sidebar/navbar strings for EVERY supported language (not just the
    // current one), embedded once so a flag click can repaint the chrome instantly, client-side,
    // with zero network round trip / page reload.
    var I18N_ALL_LAYOUT = <?php
        $allLayoutDict = [];
        foreach (POS_SUPPORTED_LANGS as $lc) {
            $allLayoutDict[$lc] = loadLangDomain($lc, 'layout');
        }
        echo json_encode($allLayoutDict, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>;

    // I18N_ALL_MERGED: every lang/*/*.php domain merged per language (same dict t() uses
    // server-side), embedded once so the CURRENT iframe page's own already-rendered content can
    // also be translated instantly client-side — no reload. Pages don't carry data-i18n
    // attributes on their body content (only the shell does), so translateIframeContent() below
    // works by reverse-matching each visible text node's current string back to its translation
    // key in the outgoing language's dict, then swapping in the incoming language's value for
    // that same key. Text that doesn't match any known dict value (product names, numbers,
    // prices, anything sprintf-formatted with a %d already substituted in) is simply left alone.
    var I18N_ALL_MERGED = <?php
        $allMergedDict = [];
        foreach (POS_SUPPORTED_LANGS as $lc) {
            $allMergedDict[$lc] = loadLangDictionary($lc);
        }
        echo json_encode($allMergedDict, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>;
    var POS_LANG_META_JS = <?php echo json_encode(POS_LANG_META, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    // Tracks the language the iframe's current page content was actually rendered in
    // server-side, so a flag click knows which dict to reverse-match FROM.
    var CURRENT_ACTIVE_LANG = '<?php echo getCurrentLang(); ?>';

    // Language flag switcher: repaints the sidebar/navbar chrome AND the current iframe page's
    // content instantly, client-side — no reload, no waiting on the network. The choice is still
    // persisted server-side in the background so the next real navigation renders pre-translated.
    document.addEventListener('DOMContentLoaded', function() {
      function applyLangInstantly(lang) {
        var dict = I18N_ALL_LAYOUT[lang];
        if (!dict) return;

        // Keep I18N_LAYOUT (used by JS-rendered notification dropdown markup) in sync too.
        I18N_LAYOUT = dict;

        document.querySelectorAll('[data-i18n]').forEach(function(el) {
          var key = el.getAttribute('data-i18n');
          if (dict[key]) el.textContent = dict[key];
        });
        document.querySelectorAll('[data-i18n-title]').forEach(function(el) {
          var key = el.getAttribute('data-i18n-title');
          if (dict[key]) el.setAttribute('title', dict[key]);
        });
        document.documentElement.setAttribute('lang', lang);

        var meta = POS_LANG_META_JS[lang];
        if (meta) {
          var triggerImg = document.getElementById('currentLangFlagImg');
          if (triggerImg) {
            triggerImg.src = '../assets/img/flag_img/' + meta.img;
            triggerImg.alt = lang;
          }
        }

        document.querySelectorAll('.lang-flag-btn').forEach(function(el) {
          var isActive = el.getAttribute('data-lang') === lang;
          el.classList.toggle('active', isActive);
          el.style.background = isActive ? '#eaf2ff' : 'transparent';
        });
      }

      var reverseMapCache = {};
      function getReverseMap(fromLang) {
        if (reverseMapCache[fromLang]) return reverseMapCache[fromLang];
        var dict = I18N_ALL_MERGED[fromLang] || {};
        var rev = {};
        for (var key in dict) {
          if (!Object.prototype.hasOwnProperty.call(dict, key)) continue;
          var val = (dict[key] || '').trim();
          if (val && !(val in rev)) rev[val] = key; // first key wins on a shared-text collision
        }
        reverseMapCache[fromLang] = rev;
        return rev;
      }

      // Dictionary values with %s / %d placeholders, compiled to anchored regexes (longest
      // literal text first, so "ກາຍມາ %s ມື້" wins over the bare "%s ມື້"). Values whose fixed
      // text is under 2 characters are skipped — they'd match almost anything.
      var sprintfPatternCache = {};
      function getSprintfPatterns(fromLang) {
        if (sprintfPatternCache[fromLang]) return sprintfPatternCache[fromLang];
        var dict = I18N_ALL_MERGED[fromLang] || {};
        var list = [], seen = {};
        for (var key in dict) {
          if (!Object.prototype.hasOwnProperty.call(dict, key)) continue;
          var val = (dict[key] || '').trim();
          if (!/%[sd]/.test(val) || seen[val]) continue;
          var literal = val.replace(/%[sd]/g, '').replace(/\s+/g, '');
          // One CJK character is a whole word ("%s 天" = "%s days"); anything else needs 2+.
          if (literal.length < 2 && !/[㐀-鿿]/.test(literal)) continue;
          seen[val] = 1;
          var src = val.split(/%[sd]/).map(function (part) {
            return part.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          }).join('(.+?)');
          list.push({ key: key, re: new RegExp('^' + src + '$'), weight: literal.length });
        }
        list.sort(function (a, b) { return b.weight - a.weight; });
        sprintfPatternCache[fromLang] = list;
        return list;
      }

      function translateIframeContent(fromLang, toLang) {
        var frameEl = document.querySelector('iframe[name="frame"]');
        if (!frameEl) return;
        var frameDoc;
        try {
          frameDoc = frameEl.contentDocument || frameEl.contentWindow.document;
        } catch (e) {
          return; // cross-origin guard, shouldn't happen — the iframe is always same-origin
        }
        if (!frameDoc || !frameDoc.body) return;

        var rev = getReverseMap(fromLang);
        var toDict = I18N_ALL_MERGED[toLang] || {};

        var walker = frameDoc.createTreeWalker(frameDoc.body, NodeFilter.SHOW_TEXT, {
          acceptNode: function(node) {
            var tag = node.parentNode && node.parentNode.tagName;
            if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT') return NodeFilter.FILTER_REJECT;
            return NodeFilter.FILTER_ACCEPT;
          }
        });
        // "ກີບ (₭)" / "BCEL One (ສາຂາຫຼັກ)": a known word followed by a bracketed part that
        // isn't in any dictionary (currency symbol, count, code) — translate the word, keep the rest.
        var BRACKET_TAIL_RE = /^(.+?)(\s*\([^()]*\))$/;
        var patterns = getSprintfPatterns(fromLang);
        function translateText(text) {
          var key = rev[text];
          if (key && toDict[key]) return toDict[key];
          var m = text.match(BRACKET_TAIL_RE);
          if (m) {
            var headKey = rev[m[1].trim()];
            if (headKey && toDict[headKey]) return toDict[headKey] + m[2];
          }
          // "3 ມື້" / "ສະແດງ 1–10 ຈາກ 25 ລາຍການ": a sprintf string with its values already filled
          // in — match it against the %s/%d patterns and refill the target language's pattern.
          for (var i = 0; i < patterns.length; i++) {
            var pm = text.match(patterns[i].re);
            if (!pm) continue;
            var target = toDict[patterns[i].key];
            if (!target) continue;
            var args = pm.slice(1);
            return target.replace(/%[sd]/g, function () { return args.length ? args.shift() : ''; });
          }
          return null;
        }

        var node;
        while ((node = walker.nextNode())) {
          var trimmed = node.nodeValue.trim();
          if (!trimmed) continue;
          var translated = translateText(trimmed);
          if (translated !== null) {
            var lead = node.nodeValue.match(/^\s*/)[0];
            var trail = node.nodeValue.match(/\s*$/)[0];
            node.nodeValue = lead + translated + trail;
          }
        }

        ['placeholder', 'title'].forEach(function(attr) {
          frameDoc.querySelectorAll('[' + attr + ']').forEach(function(el) {
            var val = (el.getAttribute(attr) || '').trim();
            var key = rev[val];
            if (key && toDict[key]) el.setAttribute(attr, toDict[key]);
          });
        });

        frameDoc.querySelectorAll('input[type="submit"], input[type="button"], input[type="reset"]').forEach(function(el) {
          var val = (el.value || '').trim();
          var key = rev[val];
          if (key && toDict[key]) el.value = toDict[key];
        });
      }

      // Re-point the iframe page's own JS i18n dictionaries (I18N_*, PROMO_I18N, …) at the
      // incoming language, so a Swal/alert fired AFTER a flag switch — with no reload — speaks
      // the new language too, not just the already-painted DOM. Every such object is a flat
      // map keyed by the same "domain.key" strings t()/tjson() use, so we can find them by
      // shape on the (same-origin) iframe window and swap each value from I18N_ALL_MERGED.
      function retranslateIframeI18nObjects(toLang) {
        var frameEl = document.querySelector('iframe[name="frame"]');
        if (!frameEl) return;
        var win;
        try { win = frameEl.contentWindow; } catch (e) { return; }
        if (!win) return;
        var toDict = I18N_ALL_MERGED[toLang];
        if (!toDict) return;
        var props;
        try { props = Object.keys(win); } catch (e) { return; }
        // "domain.key" shape — the exact form t()/tjson() keys take.
        var KEY_RE = /^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/;
        var hasOwn = Object.prototype.hasOwnProperty;
        props.forEach(function(prop) {
          var obj;
          try { obj = win[prop]; } catch (e) { return; }
          // Plain dictionary only: object, not null, not an Array/DOM/function. Don't compare
          // prototypes across realms (the iframe has its own Object.prototype) — the key
          // heuristic below is strict enough on its own.
          if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return;
          if (typeof obj.nodeType === 'number') return; // DOM node
          var oKeys;
          try { oKeys = Object.keys(obj); } catch (e) { return; }
          if (!oKeys.length || oKeys.length > 2000) return;
          var isI18n = oKeys.some(function(k) {
            return KEY_RE.test(k) && hasOwn.call(toDict, k);
          });
          if (!isI18n) return;
          oKeys.forEach(function(k) {
            if (hasOwn.call(toDict, k)) {
              try { obj[k] = toDict[k]; } catch (e) {}
            }
          });
        });
      }

      document.querySelectorAll('.lang-flag-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var lang = btn.getAttribute('data-lang');
          if (!lang || lang === CURRENT_ACTIVE_LANG) return;
          var fromLang = CURRENT_ACTIVE_LANG;

          // 1. Instant, client-side repaint of the persistent chrome.
          applyLangInstantly(lang);

          // 2. Instant, client-side translation of the current iframe page's own content —
          //    no reload, matching the shell's zero-network-wait feel.
          translateIframeContent(fromLang, lang);

          // 2b. Re-point that page's JS i18n dictionaries so its popups switch too.
          retranslateIframeI18nObjects(lang);

          // 2c. Notify the iframe page so it can redo anything the DOM walker can't reach —
          //     e.g. redraw <canvas> charts whose labels are baked into the bitmap, or re-render
          //     JS-animated counters whose suffix text got baked into a compound text node
          //     ("16 ບິນ") that never exact-matches a dictionary value on its own.
          try {
            var _frameForEvt = document.querySelector('iframe[name="frame"]');
            if (_frameForEvt && _frameForEvt.contentWindow) {
              _frameForEvt.contentWindow.dispatchEvent(new CustomEvent('pos:langchange', { detail: { lang: lang, fromLang: fromLang } }));
            }
          } catch (e) {}

          CURRENT_ACTIVE_LANG = lang;

          // 3. Persist the choice server-side in the background, purely so the NEXT real
          //    navigation is already rendered in this language from the start.
          fetch('../lang/set_lang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'lang=' + encodeURIComponent(lang)
          }).catch(function() {});
        });
      });
    });

    // Web Audio API Notification Chime Generator with Mobile Autoplay Policy Unlocker
    var sharedAudioCtx = null;
    var isAudioUnlocked = false;

    function unlockAudioContext() {
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
      try { raw = JSON.parse(localStorage.getItem('read_notif_keys_' + badgeId) || '[]'); } catch(e) { raw = []; }
      try { tsMap = JSON.parse(localStorage.getItem('read_notif_ts_' + badgeId) || '{}'); } catch(e) { tsMap = {}; }
      var now = Date.now(), kept = [], keptTs = {};
      raw.forEach(function(k) {
        if (NOTIF_STICKY_RE.test(k)) return;                 // sticky: never treated as read
        var t = tsMap[k];
        if (t && (now - t) > NOTIF_READ_TTL) return;         // expired after 1 day
        kept.push(k);
        keptTs[k] = t || now;
      });
      try {
        localStorage.setItem('read_notif_keys_' + badgeId, JSON.stringify(kept));
        localStorage.setItem('read_notif_ts_' + badgeId, JSON.stringify(keptTs));
      } catch(e) {}
      return kept;
    }

    function persistReadNotifKey(badgeId, key) {
      if (!key || NOTIF_STICKY_RE.test(key)) return false;   // can't dismiss sticky alerts
      var keys = loadReadNotifKeys(badgeId);
      if (keys.indexOf(key) !== -1) return false;
      keys.push(key);
      var tsMap = {};
      try { tsMap = JSON.parse(localStorage.getItem('read_notif_ts_' + badgeId) || '{}'); } catch(e) {}
      tsMap[key] = Date.now();
      try {
        localStorage.setItem('read_notif_keys_' + badgeId, JSON.stringify(keys));
        localStorage.setItem('read_notif_ts_' + badgeId, JSON.stringify(tsMap));
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
                tbodyHtml += '<td class="text-right align-middle font-weight-bold text-success text-nowrap" style="white-space: nowrap;">' + Math.round(lineValue).toLocaleString() + ' ₭</td>';
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
                  <td class="text-right">${costVal.toLocaleString()} ₭</td>
                  <td class="text-right font-weight-bold text-success">${totalVal.toLocaleString()} ₭</td>
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
      $('.dropdown-menu.show').removeClass('show');
      $('.nav-item.dropdown.show').removeClass('show');
    }
    window.goToRestockProduct = goToRestockProduct;

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

    function pollLiveNotifications() {
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
          } else {
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
        sessionStorage.setItem('currentIframeSrc', transferUrl);
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
  <!-- /.navbar -->

  <!-- ແຖບເມນູທາງຊ້າຍ (Unified Dynamic Sidebar) -->
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

  <script>
    // Theme colour: recolour sidebar + navbar chrome from a per-browser saved choice.
    (function() {
      var STORE_KEY = 'pos_theme_color';
      var DEFAULT_COLOR = '#244886';

      // Lighten (percent > 0) or darken (percent < 0) a #rrggbb colour.
      function shade(hex, percent) {
        hex = String(hex).replace('#', '');
        if (hex.length !== 6) return '#' + hex;
        var r = parseInt(hex.substr(0, 2), 16),
            g = parseInt(hex.substr(2, 2), 16),
            b = parseInt(hex.substr(4, 2), 16);
        var t = percent < 0 ? 0 : 255, p = Math.abs(percent);
        r = Math.round((t - r) * p) + r;
        g = Math.round((t - g) * p) + g;
        b = Math.round((t - b) * p) + b;
        return '#' + [r, g, b].map(function(x) { return ('0' + x.toString(16)).slice(-2); }).join('');
      }

      // Recolour the active / clicked sidebar menu highlight to match the theme.
      function paintActiveHighlight(color) {
        var css =
          '.nav-sidebar > .nav-item:not(.has-treeview) > .nav-link.active,' +
          '.nav-sidebar .nav-treeview .nav-link.active{' +
          'background:linear-gradient(135deg,' + shade(color, -0.28) + ',' + shade(color, 0.38) + ') !important;}';
        var tag = document.getElementById('posThemeActiveStyle');
        if (!tag) {
          tag = document.createElement('style');
          tag.id = 'posThemeActiveStyle';
          document.head.appendChild(tag);
        }
        tag.textContent = css;
      }

      // Override rules for buttons / primary accents — injected into BOTH the shell
      // and the iframe page so the whole system follows the chosen colour.
      // includeInline: also retarget the hard-coded blue-gradient inline styles
      // (safe inside the iframe; skipped in the shell where it would hit chrome).
      // NOTE: every selector is prefixed with `body ` so it out-specifies the
      // per-page theme stylesheets (themes/pos.css, themes/reports.css, …) that
      // are <link>ed inside <body> and would otherwise win on source order.
      function buttonCSS(color, includeInline) {
        var d = shade(color, -0.18), l = shade(color, 0.20), gd = shade(color, -0.28);
        var rules = [
          'body .btn-primary,body .btn-primary.disabled,body .btn-primary:disabled{background:' + color + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .btn-primary:hover,body .btn-primary:focus,body .btn-primary:not(:disabled):not(.disabled):active,body .btn-primary:not(:disabled):not(.disabled).active{background:' + d + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .btn-outline-primary{color:' + color + '!important;border-color:' + color + '!important;background:transparent!important}',
          'body .btn-outline-primary:hover,body .btn-outline-primary:not(:disabled):not(.disabled):active,body .btn-outline-primary:not(:disabled):not(.disabled).active{background:' + color + '!important;border-color:' + color + '!important;color:#fff!important}',
          'body .btn-info{background:' + color + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .btn-info:hover,body .btn-info:focus,body .btn-info:not(:disabled):not(.disabled):active{background:' + d + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .badge-primary{background:' + color + '!important;background-image:none!important}',
          // themes/pos.css sets `.bg-primary{background:linear-gradient(...)!important}` — a
          // background-color-only override leaves that gradient IMAGE on top, so clear it too.
          'body .bg-primary{background:' + color + '!important;background-image:none!important}',
          'body .text-primary{color:' + color + '!important}',
          'body .btn-link{color:' + color + '!important}',
          // Pagination — plain .pagination (Bootstrap / global-custom.css / report-pagination).
          'body .pagination .page-item .page-link{color:' + color + '!important}',
          'body .pagination .page-item.active .page-link{background:' + color + '!important;background-image:none!important;border-color:' + color + '!important;color:#fff!important;box-shadow:0 4px 10px rgba(0,0,0,0.18)!important}',
          'body .pagination .page-item:not(.active):not(.disabled) .page-link:hover{background-color:' + l + '!important;border-color:' + color + '!important;color:' + d + '!important}',
          'body .pagination .page-item.disabled .page-link{color:#6c757d!important}',
          // .pagination-circle inside modals: themes/import_stock.css uses ".modal .pagination-circle ..." (5 classes) — need matching depth to win.
          'body .pagination-circle .page-item.active .page-link,body .modal .pagination-circle .page-item.active .page-link{background:' + color + '!important;background-image:none!important;border-color:' + color + '!important;color:#fff!important}',
          'body .pagination-circle .page-item .page-link:hover,body .modal .pagination-circle .page-item:not(.active):not(.disabled) .page-link:hover{background-color:' + l + '!important;border-color:' + color + '!important;color:' + d + '!important}',
          'body .custom-control-input:checked~.custom-control-label::before{background-color:' + color + '!important;border-color:' + color + '!important}',
          'body .nav-pills .nav-link.active{background-color:' + color + '!important}',
          'body .swal2-styled.swal2-confirm{background-color:' + color + '!important}'
        ];
        if (includeInline) {
          // NOTE: inline hard-coded #2c5aa0/#244886 gradients are handled by paintIframeInline()
          // (JS) not CSS — an [style*="#hex"] rule stops matching once we've rewritten the style,
          // which is exactly why a 2nd colour pick used to "stick".
          // POS page: scan box + search badge use their own hard-coded gradients in themes/pos.css.
          rules.push('body .pos-search-wrapper{background:linear-gradient(135deg,' + gd + ' 0%,' + color + ' 50%,' + l + ' 100%)!important}');
          rules.push('body .pos-search-badge{background:linear-gradient(135deg,' + l + ',' + color + ')!important}');
        }
        return rules.join('');
      }

      function injectStyle(doc, id, css) {
        if (!doc || !doc.head) return;
        var tag = doc.getElementById(id);
        if (!tag) {
          tag = doc.createElement('style');
          tag.id = id;
          doc.head.appendChild(tag);
        }
        tag.textContent = css;
      }

      function removeStyle(doc, id) {
        if (!doc) return;
        var tag = doc.getElementById(id);
        if (tag) tag.remove();
      }

      function iframeDoc() {
        try {
          var f = document.querySelector('iframe[name="frame"]');
          return f ? (f.contentDocument || f.contentWindow.document) : null;
        } catch (e) { return null; }
      }

      // Some page elements (modal headers, "add" buttons, section bars) hard-code
      // `style="background: linear-gradient(135deg,#2c5aa0,#244886) !important"` inline.
      // Inline !important can only be beaten by JS setting the property directly.
      // We tag each one with .pos-inline-themed on first touch so a 2nd/3rd colour pick
      // still finds it — after the first rewrite its style no longer contains the old hex.
      function paintIframeInline(color) {
        var idoc = iframeDoc();
        if (!idoc || !idoc.body) return;
        var l = shade(color, 0.20), d = shade(color, -0.18);
        var grad = 'linear-gradient(135deg, ' + l + ', ' + color + ')';
        var BRAND = /#2c5aa0|#244886/i;
        idoc.querySelectorAll('[style*="#2c5aa0"],[style*="#244886"],.pos-inline-themed').forEach(function(el) {
          // Form fields keep a neutral fill — only their border may follow the theme.
          var isField = (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT');
          if (!el.classList.contains('pos-inline-themed')) {
            var s = el.getAttribute('style') || '';
            // Only recolour a property whose VALUE actually carries the brand hex — a
            // white `background:#fff` sitting next to a `border:...#244886` must stay white.
            var hasBg = false, hasBorder = false;
            s.split(';').forEach(function(decl) {
              var i = decl.indexOf(':');
              if (i < 0 || !BRAND.test(decl.slice(i + 1))) return;
              var prop = decl.slice(0, i).trim().toLowerCase();
              if (prop === 'background' || prop === 'background-color' || prop === 'background-image') hasBg = true;
              else if (prop === 'border' || prop === 'border-color') hasBorder = true;
            });
            if (isField) hasBg = false;
            if (!hasBg && !hasBorder) return;
            el.classList.add('pos-inline-themed');
            el.dataset.posBg = hasBg ? '1' : '';
            el.dataset.posBorder = hasBorder ? '1' : '';
          }
          if (el.dataset.posBg) el.style.setProperty('background', grad, 'important');
          if (el.dataset.posBorder) el.style.setProperty('border-color', d, 'important');
        });
      }

      function paint(color) {
        var nav = document.querySelector('.main-header.navbar');
        if (nav) nav.style.setProperty('background-color', color, 'important');
        var side = document.querySelector('.main-sidebar');
        if (side) side.style.setProperty('background-color', color, 'important');
        document.querySelectorAll('.main-sidebar .brand-link').forEach(function(el) {
          el.style.setProperty('background-color', color, 'important');
        });
        // The .wrapper::before "shield" (fixed block behind the logo that hides the white
        // gap while the sidebar slides) is a pseudo-element — recolour it via an injected
        // rule so it stays invisible against the theme instead of flashing the old blue
        // during the collapse/expand animation.
        injectStyle(document, 'posThemeShieldStyle', '.wrapper::before{background:' + color + ' !important;background-color:' + color + ' !important}');
        // Flat-fill the navbar notification dropdown headers so they track the theme too.
        document.querySelectorAll('.main-header .dropdown-header').forEach(function(el) {
          el.style.setProperty('background-color', color, 'important');
        });
        paintActiveHighlight(color);
        injectStyle(document, 'posThemeBtnStyle', buttonCSS(color, false));
        injectStyle(iframeDoc(), 'posThemeBtnStyle', buttonCSS(color, true));
        paintIframeInline(color);
        document.querySelectorAll('.pos-theme-swatch').forEach(function(el) {
          el.classList.toggle('active', (el.getAttribute('data-color') || '').toLowerCase() === color.toLowerCase());
        });
      }

      var currentColor = DEFAULT_COLOR;

      function applyThemeColor(color, save) {
        currentColor = color;
        paint(color);
        if (save) { try { localStorage.setItem(STORE_KEY, color); } catch (e) {} }
      }

      // Re-inject the button theme into every iframe page as it loads.
      function reinjectIframe() {
        if (currentColor && currentColor !== DEFAULT_COLOR) {
          injectStyle(iframeDoc(), 'posThemeBtnStyle', buttonCSS(currentColor, true));
          paintIframeInline(currentColor);
        }
      }
      if (window.jQuery) {
        jQuery(function($) {
          $('iframe[name="frame"]').on('load', reinjectIframe);
          reinjectIframe();               // in case the frame already finished loading
          setTimeout(reinjectIframe, 500); // and once more after first paint settles
        });
      }

      // Apply saved choice immediately (elements already exist at this point).
      var saved = null;
      try { saved = localStorage.getItem(STORE_KEY); } catch (e) {}
      if (saved) applyThemeColor(saved, false);

      document.addEventListener('DOMContentLoaded', function() {
        if (saved) applyThemeColor(saved, false);
        document.querySelectorAll('.pos-theme-swatch').forEach(function(el) {
          el.addEventListener('click', function() {
            applyThemeColor(this.getAttribute('data-color'), true);
          });
        });
        var resetBtn = document.getElementById('posThemeReset');
        if (resetBtn) {
          resetBtn.addEventListener('click', function() {
            try { localStorage.removeItem(STORE_KEY); } catch (e) {}
            currentColor = DEFAULT_COLOR;
            applyThemeColor(DEFAULT_COLOR, false);
            // Drop overrides so the stylesheets' original blues return.
            removeStyle(document, 'posThemeActiveStyle');
            removeStyle(document, 'posThemeBtnStyle');
            removeStyle(document, 'posThemeShieldStyle');
            removeStyle(iframeDoc(), 'posThemeBtnStyle');
          });
        }
      });
    })();
  </script>

  <script>
    (function() {
      var defaultSrc = '<?php echo $default_iframe_src; ?>';
      var hasDashboardPerm = <?php echo hasPermission('dashboard') ? 'true' : 'false'; ?>;
      var savedSrc = sessionStorage.getItem('currentIframeSrc');
      
      // If user does not have dashboard permission and savedSrc is home.php, clear it
      if (!hasDashboardPerm && (!savedSrc || savedSrc === 'home.php')) {
        savedSrc = defaultSrc;
        sessionStorage.setItem('currentIframeSrc', defaultSrc);
      }
      
      var targetSrc = savedSrc || defaultSrc;
      if (targetSrc) {
        var links = document.querySelectorAll('.nav-sidebar a.nav-link');
        for (var i = 0; i < links.length; i++) {
          links[i].classList.remove('active');
        }
        for (var i = 0; i < links.length; i++) {
          var href = links[i].getAttribute('href');
          if (href && href !== '#' && (href === targetSrc || targetSrc.endsWith(href))) {
            links[i].classList.add('active');
            var treeview = links[i].closest('.nav-treeview');
            if (treeview) {
              var parentLiGroup = treeview.closest('.nav-item');
              if (parentLiGroup) {
                parentLiGroup.classList.add('menu-open');
              }
            }
            break;
          }
        }
      }
    })();
  </script>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" style="background-color: #f4f6f9; position: relative;">
    <!-- Page Loading Spinner Overlay (12-Dot Pulse Circle Spinner from Screenshot) -->
    <style>
      .loader-dots-spinner {
        color: #0263ff;
        display: inline-block;
        position: relative;
        width: 56px;
        height: 56px;
      }
      .loader-dots-spinner div {
        transform-origin: 28px 28px;
        animation: loader-dots-spinner-anim 1.2s linear infinite;
      }
      .loader-dots-spinner div:after {
        content: " ";
        display: block;
        position: absolute;
        top: 4px;
        left: 25px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #0263ff;
      }
      .loader-dots-spinner div:nth-child(1) { transform: rotate(0deg); animation-delay: -1.1s; }
      .loader-dots-spinner div:nth-child(2) { transform: rotate(30deg); animation-delay: -1s; }
      .loader-dots-spinner div:nth-child(3) { transform: rotate(60deg); animation-delay: -0.9s; }
      .loader-dots-spinner div:nth-child(4) { transform: rotate(90deg); animation-delay: -0.8s; }
      .loader-dots-spinner div:nth-child(5) { transform: rotate(120deg); animation-delay: -0.7s; }
      .loader-dots-spinner div:nth-child(6) { transform: rotate(150deg); animation-delay: -0.6s; }
      .loader-dots-spinner div:nth-child(7) { transform: rotate(180deg); animation-delay: -0.5s; }
      .loader-dots-spinner div:nth-child(8) { transform: rotate(210deg); animation-delay: -0.4s; }
      .loader-dots-spinner div:nth-child(9) { transform: rotate(240deg); animation-delay: -0.3s; }
      .loader-dots-spinner div:nth-child(10) { transform: rotate(270deg); animation-delay: -0.2s; }
      .loader-dots-spinner div:nth-child(11) { transform: rotate(300deg); animation-delay: -0.1s; }
      .loader-dots-spinner div:nth-child(12) { transform: rotate(330deg); animation-delay: 0s; }
      @keyframes loader-dots-spinner-anim {
        0% { opacity: 1; }
        100% { opacity: 0.15; }
      }
    </style>
    <div id="iframeLoaderOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.94); z-index: 999; display: flex; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
      <div class="loader-dots-spinner">
        <div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div>
      </div>
      <div style="margin-top: 18px; font-weight: 700; color: #1e293b; font-size: 1.05rem; letter-spacing: 0.5px;" data-i18n="layout.loading_data">
        <?php echo htmlspecialchars(t('layout.loading_data', 'ກຳລັງໂຫຼດຂໍ້ມູນ...')); ?>
      </div>
    </div>

    <iframe width="100%" height="100%" frameborder="0" name="frame" src="<?php echo $default_iframe_src; ?>" style="background-color: #f4f6f9;"></iframe>
    <style>
      .content-wrapper {
        height: calc(var(--app-vh, 1vh) * 100 - 64px - 42px) !important;
      }
      @media (max-width: 991.98px) {
        .content-wrapper {
          height: calc(var(--app-vh, 1vh) * 100 - 64px - 42px) !important;
        }
      }
    </style>
    <script>
      // 100vh on tablet/mobile browsers can include area hidden behind the address bar/toolbar,
      // which made the content-wrapper (and anything pinned to its bottom, like the POS action
      // buttons) taller than what's actually visible with no way to scroll to it. Measure the
      // real visible height with window.innerHeight instead and drive the CSS off that.
      (function () {
        function setAppVh() {
          document.documentElement.style.setProperty('--app-vh', (window.innerHeight * 0.01) + 'px');
        }
        setAppVh();
        window.addEventListener('resize', setAppVh);
        window.addEventListener('orientationchange', setAppVh);
      })();
      function hideFastLoader() {
        var loader = document.getElementById('iframeLoaderOverlay');
        if (loader) {
          loader.style.opacity = '0';
          loader.style.transition = 'opacity 0.25s ease';
          setTimeout(function() { loader.style.display = 'none'; }, 250);
        }
      }
      function showFastLoader() {
        var loader = document.getElementById('iframeLoaderOverlay');
        if (loader) {
          loader.style.display = 'flex';
          loader.style.opacity = '1';
        }
      }
      (function() {
        var defaultSrc = '<?php echo $default_iframe_src; ?>';
        var hasDashboardPerm = <?php echo hasPermission('dashboard') ? 'true' : 'false'; ?>;
        var savedSrc = sessionStorage.getItem('currentIframeSrc');
        if (!hasDashboardPerm && (!savedSrc || savedSrc === 'home.php' || savedSrc.endsWith('/home.php'))) {
          savedSrc = defaultSrc;
          sessionStorage.setItem('currentIframeSrc', defaultSrc);
        }
        if (savedSrc) {
          document.getElementsByName('frame')[0].src = savedSrc;
        }
        // Safety timeout: automatically hide loading spinner after 600ms
        setTimeout(hideFastLoader, 600);
      })();
    </script>
  </div>

  <!-- Main Footer -->
  <footer class="main-footer" style="background: #ffffff; border-top: 1px solid #cbd5e1; color: #475569; padding: 0 20px; font-size: 0.88rem; height: 42px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
    <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
      <span>Wlaodev Pos System</span>
    </div>
    <div style="font-weight: 700; color: #64748b;">
      <span>Version 3.8.26</span>
    </div>
  </footer>

</div>
<!-- ./wrapper -->

<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.js"></script>

<script>
  $(function() {
    var posUserToggledManual = false;

    // Handle manual Hamburger PushMenu toggle click
    $(document).on('click', '#topNavbarPushMenuBtn, [data-widget="pushmenu"]', function(e) {
      e.preventDefault();
      e.stopPropagation();

      posUserToggledManual = true;

      var isMobile = window.innerWidth < 768;
      var isCollapsed = $('body').hasClass('sidebar-collapse') || $('html').hasClass('sidebar-collapse');
      
      if (isMobile) {
        if ($('body').hasClass('sidebar-open')) {
          $('html, body').removeClass('sidebar-open sidebar-is-opening');
        } else {
          $('html, body').addClass('sidebar-open').removeClass('sidebar-collapse sidebar-closed');
        }
      } else {
        if (isCollapsed) {
          $('html, body').removeClass('sidebar-collapse sidebar-closed');
        } else {
          $('html, body').addClass('sidebar-collapse');
        }
      }
    });

    // Disable hover expansion on sidebar completely
    $('.main-sidebar, .brand-link').off('mouseenter mouseleave mouseover mouseout');
    $(document).off('mouseenter mouseleave mouseover mouseout', '.main-sidebar, .brand-link');

    // Restore sidebar scroll position if available
    var sidebarEl = document.querySelector('.sidebar');
    if (sidebarEl && window.jQuery && jQuery.fn.overlayScrollbars) {
      var os = $(sidebarEl).overlayScrollbars();
      if (os && window.innerWidth <= 991) {
        // overlayScrollbars caches its scroll height from when the sidebar was still a
        // hidden/collapsed off-canvas drawer, so on mobile the bottom of the menu can end
        // up unreachable and scrolling feels like it jumps — fall back to native scrolling.
        os.destroy();
        sidebarEl.style.overflowY = 'auto';
      } else {
        var savedScroll = sessionStorage.getItem('sidebarScrollTop');
        if (os && savedScroll) {
          os.scroll({ y: parseInt(savedScroll, 10) }, 0);
        }
      }
    }

    var loaderSafetyTimer = null;

    function showFastLoader() {
      clearTimeout(loaderSafetyTimer);
      $('#iframeLoaderOverlay').stop(true, true).show();
      loaderSafetyTimer = setTimeout(function() {
        $('#iframeLoaderOverlay').stop(true, true).fadeOut(120);
      }, 1200);
    }

    function hideFastLoader() {
      clearTimeout(loaderSafetyTimer);
      $('#iframeLoaderOverlay').stop(true, true).fadeOut(120);
    }

    // ===== Exclusive Active Menu Highlight =====
    var $sidebar = $('.nav-sidebar');
    
    // Highlight initial active menu item on DOM ready
    var currentSrc = sessionStorage.getItem('currentIframeSrc') || $('iframe[name="frame"]').attr('src');
    if (currentSrc) {
      var normSrc = currentSrc.replace(/\\/g, '/');
      $('.nav-sidebar .nav-link[target="frame"]').each(function() {
        var href = $(this).attr('href');
        if (!href || href === '#') return;
        var normHref = href.replace(/\\/g, '/');
        if (normHref === normSrc || normSrc.endsWith(normHref) || normHref.endsWith(normSrc)) {
          $('.nav-sidebar .nav-link').removeClass('active');
          $(this).addClass('active');
          var $tree = $(this).closest('.has-treeview');
          if ($tree.length) {
            $tree.addClass('menu-open');
            $tree.children('.nav-treeview').show();
          }
          return false;
        }
      });
    }

    $sidebar.on('click', '.nav-link', function() {
      var href = $(this).attr('href');
      
      if (!href || href === '#' || $(this).closest('.nav-item').hasClass('has-treeview')) {
        return;
      }

      // Reset manual toggle flag on new menu navigation
      posUserToggledManual = false;

      // Show high-speed 12-dot pulse loader overlay on menu click
      showFastLoader();

      // REALTIME: Highlight clicked menu item immediately with 0ms delay!
      $('.nav-sidebar .nav-link').removeClass('active');
      $(this).addClass('active');

      // Keep parent treeview dropdown open & expanded if inside a dropdown
      var $parentTree = $(this).closest('.has-treeview');
      if ($parentTree.length) {
        $parentTree.addClass('menu-open');
        $parentTree.children('.nav-treeview').show();
      }

      sessionStorage.setItem('currentIframeSrc', href);

      if (href && (href.indexOf('pos.php') !== -1 || href.indexOf('/pos/') !== -1)) {
        $('html, body').addClass('sidebar-collapse').removeClass('sidebar-closed sidebar-open');
      } else {
        $('html, body').removeClass('sidebar-collapse sidebar-closed sidebar-open');
      }

      if (sidebarEl && window.jQuery && jQuery.fn.overlayScrollbars) {
        var osInstance = $(sidebarEl).overlayScrollbars();
        if (osInstance && typeof osInstance.scroll === 'function') {
          var scrollObj = osInstance.scroll();
          var scrollTop = 0;
          if (scrollObj) {
            if (scrollObj.position && typeof scrollObj.position.y !== 'undefined') {
              scrollTop = scrollObj.position.y;
            } else if (typeof scrollObj.y !== 'undefined') {
              scrollTop = scrollObj.y;
            }
          }
          sessionStorage.setItem('sidebarScrollTop', scrollTop);
        }
      }
    });

    $('iframe[name="frame"]').on('load', function() {
      // Hide high-speed 12-dot pulse loader overlay smoothly on page load
      hideFastLoader();

      // Bind click/touchstart event inside iframe to close sidebar
      try {
        var iframeWin = this.contentWindow;
        var iframeDoc = this.contentDocument || iframeWin.document;
        if (iframeDoc && window.jQuery) {
          $(iframeDoc).off('click.audioUnlock touchstart.audioUnlock').on('click.audioUnlock touchstart.audioUnlock', function() {
            if (window.unlockAudioContext) window.unlockAudioContext();
          });
          $(iframeDoc).off('click.notifClose touchstart.notifClose').on('click.notifClose touchstart.notifClose', function() {
            $('.dropdown-menu.show').removeClass('show');
            $('.nav-item.dropdown.show').removeClass('show');
          });
          $(iframeDoc).off('click.sidebarClose touchstart.sidebarClose').on('click.sidebarClose touchstart.sidebarClose', function() {
            if (window.innerWidth <= 992) {
              var isSidebarOpen = $('body').hasClass('sidebar-open') || $('html').hasClass('sidebar-open');
              if (isSidebarOpen) {
                $('#topNavbarPushMenuBtn').click();
              }
            }
          });

          // Count activity inside the iframe as activity — otherwise a user filling a long
          // form gets force-logged-out after 15 min even while working. Throttled to 1×/20s.
          var _idlePing = 0;
          $(iframeDoc).off('mousemove.idle keydown.idle click.idle scroll.idle touchstart.idle wheel.idle')
            .on('mousemove.idle keydown.idle click.idle scroll.idle touchstart.idle wheel.idle', function() {
              var now = Date.now();
              if (now - _idlePing > 20000) {
                _idlePing = now;
                if (window.resetIdleTimer) window.resetIdleTimer();
              }
            });
        }
      } catch(e) {
        // Suppress cross-origin warnings if any
      }

      try {
        var path = this.contentWindow.location.pathname;
        var search = this.contentWindow.location.search || '';
        var fullTarget = path.substring(path.lastIndexOf('/') + 1) + search;
        var page = path.substring(path.lastIndexOf('/') + 1);

        var isPosPage = (path.indexOf('pos.php') !== -1 || path.indexOf('/pos/') !== -1);

        if (isPosPage) {
          // Auto-collapse on POS page initial load unless user manually toggled hamburger
          if (!posUserToggledManual) {
            $('html, body').addClass('sidebar-collapse').removeClass('sidebar-closed sidebar-open');
          }
        } else {
          // Auto-expand sidebar for ALL other pages
          posUserToggledManual = false;
          $('html, body').removeClass('sidebar-collapse sidebar-closed sidebar-open');
        }

        if (page && page !== 'blank') {
          var normPath = path.replace(/\\/g, '/');
          // Only try to re-match if the current active item doesn't already match the actual loaded path
          var $currentActive = $('.nav-sidebar .nav-link.active[target="frame"]');
          var currentActiveHref = $currentActive.length ? $currentActive.attr('href').replace(/\\/g, '/') : '';

          // Check if the currently highlighted menu already matches the loaded page
          var alreadyMatches = currentActiveHref && (
            normPath.endsWith(currentActiveHref) ||
            currentActiveHref.endsWith(normPath) ||
            normPath.indexOf(currentActiveHref.replace('/index.php', '')) !== -1
          );

          if (!alreadyMatches) {
            var matched = false;

            // Exact path match first
            $('.nav-sidebar .nav-link[target="frame"]').each(function() {
              var href = $(this).attr('href');
              if (!href || href === '#') return;
              var normHref = href.replace(/\\/g, '/');

              // Must match specifically — use full path ending, not partial indexOf
              if (normPath.endsWith(normHref) || normHref === normPath) {
                $('.nav-sidebar .nav-link').removeClass('active');
                $(this).addClass('active');

                var $tree = $(this).closest('.has-treeview');
                if ($tree.length) {
                  $tree.addClass('menu-open');
                  $tree.children('.nav-treeview').show();
                }

                sessionStorage.setItem('currentIframeSrc', href);
                matched = true;
                return false;
              }
            });

            if (!matched) {
              // Fallback: match by filename only (no cross-folder matching)
              $('.nav-sidebar .nav-link[target="frame"]').each(function() {
                var href = $(this).attr('href');
                if (!href || href === '#') return;
                var hrefNorm = href.replace(/\\/g, '/');
                var hrefPage = hrefNorm.substring(hrefNorm.lastIndexOf('/') + 1);
                if (hrefPage && hrefPage !== 'index.php' && hrefPage === page) {
                  $('.nav-sidebar .nav-link').removeClass('active');
                  $(this).addClass('active');

                  var $tree = $(this).closest('.has-treeview');
                  if ($tree.length) {
                    $tree.addClass('menu-open');
                    $tree.children('.nav-treeview').show();
                  }

                  sessionStorage.setItem('currentIframeSrc', href);
                  return false;
                }
              });
            }
          }
        }
      } catch(e) {}
    });

    // ===== Universal Accordion Dropdown Toggle (Only 1 dropdown open at a time) =====
    $(document).on('click', '.nav-sidebar .has-treeview > .nav-link', function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      var $parent = $(this).closest('.has-treeview');
      var $tree = $parent.children('.nav-treeview');
      var isOpen = $parent.hasClass('menu-open');

      // Accordion: close ONLY the other menus that are actually open. Iterating every
      // .has-treeview and .stop(true,false)-ing closed ones left in-flight slides frozen
      // mid-height, which nudged the whole sidebar (and every chevron) sideways.
      $('.nav-sidebar .has-treeview.menu-open, .nav-sidebar .has-treeview.menu-is-opening').not($parent).each(function() {
        var $otherParent = $(this);
        // Drop the classes now so the chevron rotates back in step with the slide,
        // not 300ms later in a callback.
        $otherParent.removeClass('menu-open menu-is-opening');
        $otherParent.children('.nav-treeview').stop(true, true).slideUp(300);
      });

      // Toggle current menu — same 300ms both ways, and finish (not freeze) any running anim
      if (isOpen) {
        $parent.removeClass('menu-open menu-is-opening');
        $tree.stop(true, true).slideUp(300);
      } else {
        $parent.addClass('menu-is-opening');
        $tree.stop(true, true).slideDown(300, function() {
          $parent.addClass('menu-open').removeClass('menu-is-opening');
        });
      }
    });

    // PushMenu event sync
    $(document).on('collapsed.lte.pushmenu', function() {
      $('html, body').addClass('sidebar-collapse');
    });
    $(document).on('shown.lte.pushmenu', function() {
      $('html, body').removeClass('sidebar-collapse');
    });

    // ===== Auto-close sidebar on mobile when menu item clicked =====
    // ເຊື່ອງ sidebar ອັດຕະໂນມັດ ເມື່ອກົດ menu ໃນໜ້າຈໍ mobile (≤992px)
    $(document).on('click', '.nav-sidebar .nav-link', function() {
      var isMobile = window.innerWidth <= 992;
      var href = $(this).attr('href');
      var isLeafItem = href && href !== '#' && !$(this).closest('.nav-item').hasClass('has-treeview');

      if (isMobile && isLeafItem) {
        $('body').removeClass('sidebar-open sidebar-is-opening');
        if (window.jQuery && $.fn.PushMenu) {
          try { $('[data-widget="pushmenu"]').PushMenu('collapse'); } catch(err) {}
          try { $('[data-widget="pushmenu"]').PushMenu('close'); } catch(err) {}
        }
      }
    });

    // ===== Close Sidebar on Mobile when clicking/tapping on blank area outside sidebar =====
    // ກົດ ຫຼື ແຕະບ່ອນຫວ່າງເປົ່າ (Outside Sidebar) ເພື່ອເຊື່ອງ sidebar ໃນໜ້າຈໍ mobile (≤992px)
    $(document).on('click touchstart', function(e) {
      if (window.innerWidth <= 992) {
        var $target = $(e.target);
        var isSidebarOpen = $('body').hasClass('sidebar-open') || $('html').hasClass('sidebar-open');
        
        if (isSidebarOpen) {
          var isInsideSidebar = $target.closest('.main-sidebar').length > 0;
          var isPushMenuBtn = $target.closest('#topNavbarPushMenuBtn').length > 0 || $target.closest('[data-widget="pushmenu"]').length > 0;
          
          if (!isInsideSidebar && !isPushMenuBtn) {
            $('#topNavbarPushMenuBtn').click();
          }
        }
      }
    });

  });

  function confirmLogout() {
    Swal.fire({
      title: '<span style="font-size:1.15rem; font-weight:700; color:#1e293b;">' + I18N_LAYOUT['layout.logout_confirm_title'] + '</span>',
      html: '<div style="font-size:0.90rem; color:#475569; font-weight:600; margin-top:4px;">' + I18N_LAYOUT['layout.logout_confirm_text'] + '</div>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-power-off mr-1"></i> ' + I18N_LAYOUT['layout.logout'],
      cancelButtonText: I18N_LAYOUT['layout.cancel'],
      heightAuto: false
    }).then(function(result) {
      if (result.isConfirmed) {
        sessionStorage.clear();
        window.location.href = '../auth/logout.php';
      }
    });
  }

  // ===== Client-Side Auto Idle Inactivity Timeout (15 ນາທີ = 900,000ms) =====
  (function() {
    var idleTime = 0;
    var maxIdleTime = 15 * 60 * 1000; // 15 ນາທີ
    var lastPingTime = Date.now();

    function resetIdleTimer() {
      idleTime = 0;
      // ຖ້າຜູ້ໃຊ້ນຳໃຊ້ຢູ່ — ສົ່ງ Heartbeat ໄປອັບເດດ Session ຢູ່ Server ເປັນໄລຍະ (ທຸກໆ 2 ນາທີ)
      if (Date.now() - lastPingTime > 120000) {
        lastPingTime = Date.now();
        fetch('../config/db.php', { method: 'HEAD' }).catch(function(){});
      }
    }

    // Reset idle timer ຢ່າງຕໍ່ເນື່ອງເມື່ອມີການເຄື່ອນໄຫວ ຫຼື ນຳໃຊ້ລະບົບ
    window.onload = resetIdleTimer;
    window.onmousemove = resetIdleTimer;
    window.onmousedown = resetIdleTimer;
    window.ontouchstart = resetIdleTimer;
    window.onclick = resetIdleTimer;
    window.onkeydown = resetIdleTimer;
    window.onscroll = resetIdleTimer;
    window.addEventListener('scroll', resetIdleTimer, true);
    window.addEventListener('message', resetIdleTimer);

    // Activity INSIDE the iframe (typing in a form, clicking, scrolling a page) does not
    // bubble to this parent window — without this the user gets logged out mid-task after
    // 15 min even while actively working. The iframe-load handler calls this on iframe events.
    window.resetIdleTimer = resetIdleTimer;

    setInterval(function() {
      idleTime += 5000; // ກວດສອບທຸກໆ 5 ວິນາທີ
      if (idleTime >= maxIdleTime) {
        sessionStorage.clear();
        window.location.href = '../auth/logout.php?expired=1';
      }
    }, 5000);
  })();
</script>
  <!-- STOCK TRANSFER DETAILS MODAL POPUP -->
  <div class="modal fade" id="notifTransferDetailModal" tabindex="-1" role="dialog" aria-hidden="true" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden;">
        
        <!-- Modal Header -->
        <div class="modal-header bg-primary text-white py-3 px-4" style="border-radius: 16px 16px 0 0;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1.15rem;">
            <i class="fas fa-truck-loading mr-2"></i> ລາຍລະອຽດການໂອນສິນຄ້າຈາກສາຂາໃຫຍ່
          </h5>
          <button type="button" class="close text-white opacity-90" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-4" style="background-color: #f8fafc;">
          <!-- Header Info Card -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3">
              <div class="row" style="font-size: 0.92rem;">
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="fas fa-file-invoice text-primary mr-1"></i> ລະຫັດໃບບິນໂອນ:</span>
                  <strong id="trf_modal_code" class="text-primary font-weight-bold ml-1"></strong>
                </div>
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="far fa-clock text-info mr-1"></i> ວັນທີ-ເວລາໂອນ:</span>
                  <strong id="trf_modal_date" class="text-dark ml-1"></strong>
                </div>
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="fas fa-store text-success mr-1"></i> ຈາກສາຂາ:</span>
                  <span id="trf_modal_from" class="font-weight-bold text-dark ml-1"></span>
                </div>
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="fas fa-arrow-right text-muted mr-1"></i> ຫາສາຂາ:</span>
                  <span id="trf_modal_to" class="font-weight-bold text-dark ml-1"></span>
                </div>
                <div class="col-md-12 mt-1">
                  <span class="text-muted"><i class="fas fa-user-edit text-secondary mr-1"></i> ຜູ້ດຳເນີນການ:</span>
                  <span id="trf_modal_creator" class="font-weight-bold text-dark ml-1"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Products Table Card -->
          <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-2.5 px-3 border-0 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.95rem;">
                <i class="fas fa-boxes text-primary mr-1"></i> ລາຍການສິນຄ້າທີ່ໂອນມາ (<span id="trf_modal_item_count">0</span>)
              </h6>
              <span class="text-success font-weight-bold" style="font-size: 0.92rem;">
                <i class="fas fa-coins mr-1"></i> <span id="trf_modal_total_value">0</span> ₭
              </span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive" style="max-height: 260px; overflow-y: auto; overflow-x: auto;">
                <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="white-space: nowrap;">
                  <thead class="bg-light text-secondary" style="font-size: 0.85rem; white-space: nowrap;">
                    <tr style="white-space: nowrap;">
                      <th class="text-center py-2.5 text-nowrap" style="width: 50px;">ລຳດັບ</th>
                      <th class="py-2.5 text-nowrap">ຊື່ສິນຄ້າ</th>
                      <th class="text-center py-2.5 text-nowrap">ບາໂຄດ</th>
                      <th class="text-center py-2.5 text-nowrap" style="width: 130px;">ຈຳນວນໂອນ</th>
                      <th class="text-right py-2.5 text-nowrap" style="width: 130px;">ມູນຄ່າ</th>
                    </tr>
                  </thead>
                  <tbody id="trf_modal_table_body" style="font-size: 0.9rem;">
                    <!-- Filled via JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer bg-light py-2.5 px-4" style="border-radius: 0 0 16px 16px;">
          <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal" style="border-radius: 8px;">ປິດ</button>
        </div>

      </div>
    </div>
  </div>

<script>
  // Keep the kip symbol (U+20AD) glued to its amount in the app shell too (low-stock alerts,
  // transfer modals, JS-rendered notification rows). Mirrors the pass in layouts/footer.php;
  // the shell doesn't include that file.
  (function() {
      var KIP = '₭';
      var NBSP = String.fromCharCode(160);
      var KIP_RE = /(\d)\s+₭/g;
      function fixKipInNode(node) {
          var v = node.nodeValue;
          if (!v || v.indexOf(KIP) === -1) return;
          var next = v.replace(KIP_RE, '$1' + NBSP + KIP);
          if (next !== v) node.nodeValue = next;
      }
      function walkAndFix(root) {
          if (!root) return;
          if (root.nodeType === 3) { fixKipInNode(root); return; }
          if (root.nodeType !== 1) return;
          var tag = root.tagName;
          if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'TEXTAREA') return;
          var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
          var n;
          while ((n = walker.nextNode())) fixKipInNode(n);
      }
      function boot() {
          walkAndFix(document.body);
          if (!('MutationObserver' in window) || !document.body) return;
          var queued = false, pending = [];
          var mo = new MutationObserver(function(muts) {
              for (var i = 0; i < muts.length; i++) {
                  var m = muts[i];
                  if (m.type === 'characterData') { pending.push(m.target); }
                  else { for (var j = 0; j < m.addedNodes.length; j++) pending.push(m.addedNodes[j]); }
              }
              if (queued) return;
              queued = true;
              requestAnimationFrame(function() {
                  queued = false;
                  var batch = pending; pending = [];
                  for (var k = 0; k < batch.length; k++) walkAndFix(batch[k]);
              });
          });
          mo.observe(document.body, { childList: true, subtree: true, characterData: true });
      }
      if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', boot);
      } else {
          boot();
      }
  })();
</script>

</body>
</html>
