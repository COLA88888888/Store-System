<?php
/*
 * ສ່ວນ: CSS ຂອງໂຄງຫຼັກ
 * ໂຫຼດຟອນ ແລະ ໄຟລ໌ CSS ຂອງ shell, ພ້ອມ style ໃນໜ້າ: scrollbar, ຂະໜາດໄອຄອນເທິງມືຖື,
 * ກ່ອງເລືອກສີທີມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Local Fonts & Styles -->
  <link rel="stylesheet" href="../assets/css/local-font.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../sweetalert/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="../assets/css/pages/menu-sidebar/01-base-navbar.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/pages/menu-sidebar/02-collapsed-mobile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/pages/menu-sidebar/03-alignment.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/global-custom/01-base.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../assets/css/global-custom/02-components.css?v=<?php echo time(); ?>">

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


