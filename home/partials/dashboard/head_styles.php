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

  <?php echo themeCssLink('dashboard-shell.css'); /* CSS ຍ້າຍໄປ themes/dashboard-shell.css */ ?>


