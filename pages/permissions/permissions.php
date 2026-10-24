<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍຫຼັກ: ຈັດການສິດການໃຊ້ງານ (Main Permissions Controller)
 * Path: pages/permissions/permissions.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່: ເປັນ Controller ຫຼັກໃນການໂຫຼດ partials ແຕ່ລະສ່ວນມາປະກອບເຂົ້າກັນ
 * 1. ໂຫຼດ backend (permissions_backend.php) ເພື່ອເຊັກສິດ & AJAX
 * 2. ໂຫຼດ layout header (header.php)
 * 3. ໂຫຼດ theme stylesheet (permissions.css)
 * 4. ໂຫຼດ UI Partials: Header, Sidebar Users List, Matrix Table
 * 5. ໂຫຼດ JavaScript logic (permissions_js.php)
 */

// 1. ໂຫຼດສ່ວນປະມວນຜົນ Backend, Session Check ແລະ AJAX Handlers
require_once __DIR__ . '/../../api/permissions_backend.php';

// 2. ໂຫຼດ Layout Header ຂອງລະບົບ (Navbar & AdminLTE Shell)
require_once __DIR__ . '/../../layouts/header.php';
?>

<!-- 3. ໂຫຼດ Theme CSS ສະເພາະຂອງໜ້າກຳນົດສິດ -->
<link rel="stylesheet" href="../../themes/permissions.css?v=<?php echo filemtime(__DIR__ . '/../../themes/permissions.css'); ?>">

<!-- 4. Container ຫຼັກຂອງໜ້າກຳນົດສິດ (Full-Height Layout ລົງສຸດຈໍ) -->
<div class="container-fluid permissions-page-container">
  
  <!-- ສ່ວນຫົວຂໍ້ໜ້າ (Header Title & Quick Action Link) -->
  <?php require_once __DIR__ . '/partials/permissions_header.php'; ?>

  <!-- ສ່ວນ Master-Detail Grid (ແບ່ງເປັນ 2 ຄໍລຳ: ຊ້າຍລາຍຊື່ຜູ້ໃຊ້, ຂວາຕາຕະລາງສິດ) -->
  <div class="row permissions-main-row">
    
    <!-- ຄໍລຳຊ້າຍ: ລາຍຊື່ຜູ້ນຳໃຊ້ ແລະ ຊ່ອງຄົ້ນຫາ -->
    <?php require_once __DIR__ . '/partials/user_list_sidebar.php'; ?>

    <!-- ຄໍລຳຂວາ: ຕາຕະລາງສະວິດ ເປີດ/ປິດ ສິດທຸກໂມດູນ -->
    <?php require_once __DIR__ . '/partials/permissions_matrix_table.php'; ?>

  </div>

</div>

<!-- 5. ໂຫຼດ JavaScript Functions ສຳລັບການໂຕ້ຕອບ, ເລືອກຜູ້ໃຊ້ ແລະ AJAX Toggle -->
<?php require_once __DIR__ . '/partials/js/permissions_js.php'; ?>

<!-- 6. ໂຫຼດ Layout Footer ຂອງລະບົບ -->
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
