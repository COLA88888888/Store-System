<?php
/**
 * --------------------------------------------------------------------------
 * Partials: Header & Warning Notice
 * Path: pages/settings/clear_data/partials/header.php
 * --------------------------------------------------------------------------
 */
?>
<!-- Page Header Title -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 12px;">
  <div>
    <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
      <i class="fas fa-trash-alt mr-2 text-danger"></i> <?php echo htmlspecialchars(t('clear_data.title', 'ລຶບ/ລ້າງຂໍ້ມູນລະບົບ')); ?>
    </h5>
    <small class="text-muted" style="font-size: 0.85rem;">
      <?php echo htmlspecialchars(t('clear_data.subtitle', 'ເລືອກລຶບຂໍ້ມູນທຸລະກຳການຂາຍ, ປະຫວັດລາຍງານ, ການເຄື່ອນໄຫວສະຕັອກ ຫຼື ລ້າງຂໍ້ມູນລະບົບທັງໝົດ')); ?>
    </small>
  </div>
  <div class="page-actions">
    <a href="<?php echo $base_path; ?>pages/database/database.php" class="btn btn-outline-primary btn-sm font-weight-bold px-3 py-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; font-size: 0.82rem;">
      <i class="fas fa-database mr-1.5 text-info"></i> <?php echo htmlspecialchars(t('clear_data.btn_backup_now', 'ສຳຮອງຖານຂໍ້ມູນດຽວນີ້')); ?>
    </a>
  </div>
</div>

<!-- Important Warning Alert Banner -->
<div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center" style="border-radius: 12px; background: #fffbeb; border-left: 5px solid #f59e0b !important; padding: 14px 18px;">
  <div class="mr-3">
    <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); display: flex; align-items: center; justify-content: center;">
      <i class="fas fa-exclamation-triangle text-warning" style="font-size: 1.25rem;"></i>
    </div>
  </div>
  <div class="flex-grow-1">
    <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">
      <?php echo htmlspecialchars(t('clear_data.backup_warning_title', 'ຄຳເຕືອນສຳຄັນ:')); ?>
    </div>
    <div class="text-secondary small mt-0.5">
      <?php echo htmlspecialchars(t('clear_data.backup_warning_text', 'ການລຶບຂໍ້ມູນເປັນການກະທຳຖາວອນ ແລະ ບໍ່ສາມາດກູ້ຄືນໄດ້! ແນະນຳໃຫ້ສຳຮອງຖານຂໍ້ມູນ (Backup) ໄວ້ກ່ອນທຸກຄັ້ງ.')); ?>
    </div>
  </div>
</div>
