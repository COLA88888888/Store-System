<?php
/**
 * --------------------------------------------------------------------------
 * ຟາຍ Partials: ລາຍຊື່ຜູ້ນຳໃຊ້ ແລະ ຊ່ອງຄົ້ນຫາ (Users List Sidebar)
 * Path: pages/permissions/partials/user_list_sidebar.php
 * --------------------------------------------------------------------------
 * ໜ້າທີ່:
 * - ສະແດງຊ່ອງຄົ້ນຫາຜູ້ນຳໃຊ້ (Live Search Input)
 * - ສະແດງລາຍຊື່ຜູ້ໃຊ້ທັງໝົດເປັນ Card ໃຫ້ເລືອກ (User Items List)
 * - ສະແດງປ້າຍຈຳນວນສິດທີ່ເປີດຢູ່ (Perm Count Badge Counter, ເຊັ່ນ 10/10 ສິດ, 1/10 ສິດ)
 */
?>

<!-- LEFT COLUMN: Users List Card (ຄໍລຳທາງຊ້າຍ) -->
<div class="col-lg-4 col-xl-4 permissions-col">
  <div class="user-list-card">
    
    <!-- 1. Header ຂອງ Card ລາຍຊື່ຜູ້ໃຊ້ -->
    <div class="user-list-header">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="font-weight-bold mb-0 text-dark">
          <i class="fas fa-users text-primary mr-1"></i> <?php echo htmlspecialchars(t('permissions.users_list_title', 'ລາຍຊື່ຜູ້ນຳໃຊ້')); ?>
        </h6>
        <!-- Badge ສະແດງຈຳນວນບັນຊີຜູ້ນຳໃຊ້ທັງໝົດ -->
        <span class="badge badge-primary px-2 py-1" style="border-radius: 8px;" id="totalUsersBadge">
          <?php echo $totalUsers; ?> <?php echo htmlspecialchars(t('permissions.badge_account_suffix', 'ບັນຊີ')); ?>
        </span>
      </div>

      <!-- 2. Live Search Input (ຊ່ອງຄົ້ນຫາຊື່ຜູ້ໃຊ້ ຫຼື ບົດບາດ) -->
      <div class="user-search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="userFilterInput" class="form-control" placeholder="<?php echo htmlspecialchars(t('permissions.search_placeholder', 'ຄົ້ນຫາຊື່ຜູ້ໃຊ້ ຫຼື ບົດບາດ...')); ?>" onkeyup="filterUserList()">
      </div>
    </div>

    <!-- 3. Scrollable Users List (ລາຍຊື່ຜູ້ນຳໃຊ້ແບບສະກໍບາ) -->
    <div class="user-items-scroll" id="userItemsList">
      <?php if (empty($users)): ?>
        <div class="empty-users-state">
          <i class="fas fa-user-slash fa-3x mb-3 text-muted"></i>
          <p class="mb-0"><?php echo htmlspecialchars(t('permissions.no_users_found', 'ບໍ່ພົບຂໍ້ມູນຜູ້ນຳໃຊ້ໃນລະບົບ')); ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($users as $index => $u): ?>
          <?php 
            $uStatus = $u['status'] ?? $u['userstatus'] ?? '';
            $isAdmin = (strtolower($uStatus) === 'admin' || $uStatus === 'ຜູ້ບໍລິຫານ' || $u['Id'] == 1);
            $displayName = htmlspecialchars($u['username'] ?? 'User');
            $isActiveFirst = ($index === 0); // ເລືອກເຈົ້າຂອງລະບົບ (ຄົນທຳອິດ) ເປັນຄ່າເລີ່ມຕົ້ນ
            
            // ຄຳນວນຈຳນວນສິດທີ່ເປີດຢູ່ທັງໝົດ
            $permCount = 0;
            $allPermKeys = ['dashboard', 'sale', 'stock', 'report', 'accounting', 'setup', 'users', 'permissions', 'edit', 'customers'];
            foreach ($allPermKeys as $pk) {
                if (!empty($u[$pk]) || $isAdmin) {
                    $permCount++;
                }
            }
          ?>
          <!-- ປຸ່ມເລືອກຜູ້ນຳໃຊ້ແຕ່ລະບັນຊີ -->
          <div class="user-item-btn <?php echo $isActiveFirst ? 'active-user-item' : ''; ?>" 
               id="user-item-<?php echo $u['Id']; ?>"
               data-user-id="<?php echo $u['Id']; ?>"
               data-username="<?php echo strtolower($displayName); ?>"
               data-status="<?php echo strtolower($uStatus); ?>"
               onclick="selectUser(<?php echo $u['Id']; ?>)">
            
            <div class="d-flex align-items-center overflow-hidden">
              <!-- User Avatar (ຮູບ/ຕົວອັກສອນຍໍ້ ຂອງຜູ້ໃຊ້) -->
              <div class="user-avatar-circle mr-3 <?php echo $isAdmin ? 'user-avatar-admin' : ''; ?>">
                <?php if ($u['Id'] == 1): ?>
                  <i class="fas fa-crown text-white" style="font-size: 1.1rem;"></i>
                <?php else: ?>
                  <?php echo mb_substr($displayName, 0, 1, 'UTF-8'); ?>
                <?php endif; ?>
              </div>

              <!-- User Details (ຊື່ຜູ້ໃຊ້ ແລະ ບົດບາດ) -->
              <div class="text-truncate">
                <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.95rem;">
                  <?php echo $displayName; ?>
                </div>
                <div class="d-flex align-items-center gap-1 mt-1">
                  <?php if ($u['Id'] == 1): ?>
                    <span class="badge badge-danger" style="font-size: 0.7rem; border-radius: 4px;"><?php echo htmlspecialchars(t('permissions.badge_super_admin', 'Admin')); ?></span>
                  <?php elseif ($isAdmin): ?>
                    <span class="badge badge-primary" style="font-size: 0.7rem; border-radius: 4px;"><?php echo htmlspecialchars(t('permissions.role_admin_badge', 'ຜູ້ບໍລິຫານ')); ?></span>
                  <?php else: ?>
                    <span class="badge badge-secondary" style="font-size: 0.7rem; border-radius: 4px;"><?php echo htmlspecialchars($uStatus ?: t('permissions.role_employee_default', 'ພະນັກງານ')); ?></span>
                  <?php endif; ?>
                  <!-- <span class="text-muted ml-1" style="font-size: 0.75rem;">ID: #<?php echo $u['Id']; ?></span> -->
                </div>
              </div>
            </div>

            <!-- Active Permissions Badge Counter (ປ້າຍນັບສິດ ເຊັ່ນ: 10/10 ສິດ, 1/10 ສິດ) -->
            <!-- <div class="ml-2">
              <span class="perm-count-badge" id="badge-perm-count-<?php echo $u['Id']; ?>">
                <span id="count-val-<?php echo $u['Id']; ?>"><?php echo $permCount; ?></span>/10 ສິດ
              </span>
            </div> -->

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>
