<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍ Partials: ຕາຕະລາງປຸ່ມສະວິດກຳນົດສິດ (Permissions Matrix Table)
 * Path: pages/permissions/partials/permissions_matrix_table.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່:
 * - ສະແດງຂໍ້ມູນລາຍລະອຽດຜູ້ນຳໃຊ້ທີ່ເລືອກ (Detail Header) ພ້ອມ ຊື່ສາຂາ ແລະ ປຸ່ມ "ກຳນົດສິດດ່ວນ"
 * - ສະແດງຕາຕະລາງ 10+ ໂມດູນລະບົບ ພ້ອມ Sticky Header ແລະ ສະວິດ ເປີດ/ປິດ (View, Add, Edit, Delete)
 * - ໂຄງສ້າງແຍກເປັນແຕ່ລະສ່ວນ (Modular Components) ເພື່ອຄວາມເປັນລະບຽບ ແລະ ງ່າຍຕໍ່ການຈັດການ
 */
?>

<!-- RIGHT COLUMN (DETAIL): Menu Permissions Matrix Table -->
<div class="col-lg-8 col-xl-8 permissions-col">
  <div class="perm-detail-card">
    
    <?php if (!empty($users)): ?>
      <?php foreach ($users as $index => $u): ?>
        <?php
          $isFirstUser = ($index === 0);
          $uStatus = $u['status'] ?? $u['userstatus'] ?? '';
          // Cosmetic role check — also used below to decide switch editability.
          $isRoleAdmin = (strtolower($uStatus) === 'admin' || strtolower($uStatus) === 'super admin' || $uStatus === 'ຜູ້ບໍລິຫານ');
          // Id 1 is the untouchable super-admin: switches always disabled, for everyone. Any
          // OTHER admin-labeled account can only be edited by the owner (Id 1) — a non-owner
          // viewer gets disabled switches here too, even though they can still see this user.
          $isAdmin = (($u['Id'] ?? 0) == 1) || (!isOwnerUser() && $isRoleAdmin);
          $displayName = htmlspecialchars($u['username'] ?? 'User');
        ?>
        <!-- Panel ຕາຕະລາງສິດຂອງແຕ່ລະຜູ້ນຳໃຊ້ (ເລີ່ມຕົ້ນສະແດງຄົນທຳອິດ/ເຈົ້າຂອງລະບົບ, ຄົນອື່ນເຊື່ອງໄວ້) -->
        <div class="user-detail-panel w-100 flex-column" 
             id="user-detail-<?php echo $u['Id']; ?>" 
             style="<?php echo $isFirstUser ? 'display: flex;' : 'display: none;'; ?>">
          
          <!-- 1. Detail Header (ຂໍ້ມູນຜູ້ນຳໃຊ້ + ສາຂາ + ປຸ່ມກຳນົດສິດດ່ວນ) -->
          <?php require __DIR__ . '/matrix_header_component.php'; ?>

          <!-- 2. Detail Body: ຕາຕະລາງສິດ Matrix Table (4 Columns: ເບິ່ງ, ເພີ່ມ, ແກ້ໄຂ, ລົບ) -->
          <div class="perm-detail-body">
            <div class="perm-matrix-table-wrapper">
              <table class="table table-hover align-middle mb-0 perm-matrix-table" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
                <thead class="bg-light">
                  <tr>
                    <th style="min-width: 240px;"><?php echo htmlspecialchars(t('permissions.col_menu', 'ເມນູລະບົບ (Sidebar Menu)')); ?></th>
                    <th class="text-center" style="width: 90px;"><?php echo htmlspecialchars(t('permissions.action_view', 'ເບິ່ງ')); ?></th>
                    <th class="text-center" style="width: 90px;"><?php echo htmlspecialchars(t('permissions.action_add', 'ເພີ່ມ')); ?></th>
                    <th class="text-center" style="width: 90px;"><?php echo htmlspecialchars(t('permissions.action_edit', 'ແກ້ໄຂ')); ?></th>
                    <th class="text-center" style="width: 90px;"><?php echo htmlspecialchars(t('permissions.action_delete', 'ລົບ')); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                    // Load modular section components for clean and tidy code
                    require __DIR__ . '/sections/section_main_menu.php';
                    require __DIR__ . '/sections/section_pos_sales.php';
                    require __DIR__ . '/sections/section_customers.php';
                    require __DIR__ . '/sections/section_inventory.php';
                    require __DIR__ . '/sections/section_accounting.php';
                    require __DIR__ . '/sections/section_reports.php';
                    require __DIR__ . '/sections/section_setup.php';
                  ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>
