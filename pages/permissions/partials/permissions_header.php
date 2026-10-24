<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍ Partials Header: ສ່ວນຫົວຂໍ້ໜ້າກຳນົດສິດ (Permissions Page Header)
 * Path: pages/permissions/partials/permissions_header.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່: ສະແດງ Title Header ຂອງໜ້າກຳນົດສິດ ພ້ອມ ປຸ່ມທາງລັດໄປຫາ "ຈັດການຜູ້ນຳໃຊ້"
 */
?>

<!-- Page Header UI: Title ຢູ່ຊ້າຍ, ປຸ່ມຈັດການຜູ້ນຳໃຊ້ ຢູ່ຂວາມືສະເໝີ -->
<div class="d-flex justify-content-between align-items-center w-100 permissions-page-header">
  
  <!-- Title ຫົວຂໍ້ໜ້າ (ທາງຊ້າຍ) -->
  <div class="d-flex align-items-center">
    <h5 class="font-weight-bold mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; color: #0f172a; font-size: 1.1rem;">
      <i class="fas fa-user-shield text-warning mr-2"></i> <?php echo htmlspecialchars(t('permissions.header_title', 'ກຳນົດສິດການໃຊ້ງານ')); ?>
    </h5>
  </div>

  <!-- Quick Link ປຸ່ມທາງລັດໄປຫາໜ້າຈັດການຜູ້ນຳໃຊ້ (ຍ້າຍມາຂວາມື) -->
  <div class="ml-auto text-right">
    <a href="<?php echo $base_path; ?>pages/users_manage/users_manage.php" class="btn btn-outline-primary shadow-sm font-weight-bold" style="border-radius: 10px; padding: 6px 14px; font-size: 0.85rem; white-space: nowrap;">
      <i class="fas fa-users-cog mr-1"></i> <?php echo htmlspecialchars(t('permissions.btn_manage_users', 'ຈັດການຜູ້ນຳໃຊ້')); ?>
    </a>
  </div>

</div>
