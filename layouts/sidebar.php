<?php
  // ໄຟລ໌ຍ່ອຍ (require ຕາມລຳດັບ): partials/sidebar/inventory_menu.php — ເມນູຂໍ້ມູນສິນຄ້າ, ເຊັກສະຕັອກ, ນຳສິນຄ້າເຂົ້າສາງ
  // ທຸກເມນູຖືກວາດສະເໝີ ພ້ອມ permMenuAttr('ໂມດູນ') (data-perm + hidden ເມື່ອບໍ່ມີສິດ) — ຢ່າຫໍ່ດ້ວຍ if (hasPermission())
  // ເພາະ home/partials/dashboard/js_live_permissions.php ສະແດງ/ເຊື່ອງເອງທັນທີທີ່ສິດປ່ຽນ. ໂມດູນໃໝ່: ເພີ່ມໃນ api/my_permissions.php ນຳ
  $bp = $base_path ?? '';
  $headerLogoImg = $bp . 'assets/img/logosystem/Wlaodev.jpg';
  $headerBrandText = 'Wlaodev POS';
?>
<!-- UNIFIED SIDEBAR (ແຖບເມນູໄດນາມິກກວດສອບຕາມສິດ) -->
<aside class="main-sidebar elevation-0 sidebar-dark-primary" style="background-color: #244886; position: fixed; top: 0; bottom: 0; left: 0; z-index: 1038; border-right: 1px solid rgba(255, 255, 255, 0.18) !important; box-shadow: none !important;">
  <!-- Logo & Brand -->
  <?php if (hasPermission('dashboard')): ?>
    <a href="<?php echo $bp; ?>home/home.php" target="frame" class="brand-link" style="padding: 0 16px; display: flex; align-items: center; justify-content: flex-start; gap: 12px; text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important; border-right: 1px solid rgba(255, 255, 255, 0.18) !important; background-color: #244886; height: 64px;">
  <?php else: ?>
    <div class="brand-link" style="padding: 0 16px; display: flex; align-items: center; justify-content: flex-start; gap: 12px; text-decoration: none; border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important; border-right: 1px solid rgba(255, 255, 255, 0.18) !important; background-color: #244886; height: 64px; cursor: default;">
  <?php endif; ?>
    <div style="background: #ffffff; border-radius: 50%; padding: 3px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.18); width: 44px; height: 44px; min-width: 44px;">
      <img src="<?php echo htmlspecialchars($headerLogoImg); ?>" alt="POS Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;">
    </div>
    <span class="brand-text font-weight-bold" style="font-size: 1.05rem; color: #ffffff; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;"><?php echo htmlspecialchars($headerBrandText); ?></span>
  <?php if (hasPermission('dashboard')): ?>
    </a>
  <?php else: ?>
    </div>
  <?php endif; ?>

  <!-- Menu Sidebar -->
  <div class="sidebar" style="height: calc(100vh - 64px - 38px); overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; touch-action: pan-y;">
  
    <!-- ຂໍ້ມູນຜູ້ໃຊ້ (ຮູບ/ຊື່/ສະຖານະ) ຍ້າຍໄປແຖບເທິງ: home/partials/dashboard/navbar.php (.nav-user-chip) -->

    <!-- Dynamic Menu Items Based on Permissions -->
    <nav class="mt-2 pb-4">
      <ul class="nav nav-pills nav-sidebar flex-column nav-flat" role="menu" data-accordion="true">
        
          <!-- Header: Main menu -->
          <li class="nav-header text-uppercase"<?php echo permMenuAttr('dashboard'); ?> style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px;" data-i18n="layout.menu_header"><?php echo htmlspecialchars(t('layout.menu_header', 'ເມນູ')); ?></li>

          <!-- Menu: Dashboard -->
          <li class="nav-item"<?php echo permMenuAttr('dashboard'); ?>>
            <a href="home.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-chart-line text-success"></i>
              <p data-i18n="layout.dashboard"><?php echo htmlspecialchars(t('layout.dashboard', 'ດາດສ໌ບອດ')); ?></p>
            </a>
          </li>

          <!-- Dropdown Treeview: ຂາຍສິນຄ້າ POS & ລາຍການຂາຍ -->
          <li class="nav-item has-treeview"<?php echo permMenuAttr('sale', 'item_sales'); ?>>
            <a class="nav-link" role="button" style="cursor: pointer;">
              <i class="nav-icon fas fa-cash-register text-warning"></i>
              <p>
                <span data-i18n="layout.pos_group"><?php echo htmlspecialchars(t('layout.pos_group', 'ຂາຍສິນຄ້າ POS')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <!-- Sub-menu 1: ຂາຍສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('sale'); ?>>
                <a href="<?php echo $bp; ?>pages/pos/pos.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-shopping-cart text-warning"></i>
                  <p data-i18n="layout.pos_sell"><?php echo htmlspecialchars(t('layout.pos_sell', 'ຂາຍສິນຄ້າ')); ?></p>
                </a>
              </li>

              <!-- Sub-menu 2: ລາຍການຂາຍສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('item_sales'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/item_sales.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-list-alt text-purple"></i>
                  <p data-i18n="layout.pos_item_sales"><?php echo htmlspecialchars(t('layout.pos_item_sales', 'ລາຍການຂາຍສິນຄ້າ')); ?></p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Menu: Customer Management -->
          <li class="nav-item"<?php echo permMenuAttr('customers'); ?>>
            <a href="<?php echo $bp; ?>pages/customers/customers.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-user-friends text-info"></i>
              <p data-i18n="layout.customers"><?php echo htmlspecialchars(t('layout.customers', 'ຈັດການລູກຄ້າ')); ?></p>
            </a>
          </li>

        <?php require __DIR__ . '/partials/sidebar/inventory_menu.php'; /* ຂໍ້ມູນສິນຄ້າ, ເຊັກສະຕັອກ, ນຳສິນຄ້າເຂົ້າສາງ */ ?>

          <!-- Header: Reports & Accounting -->
          <li class="nav-header text-uppercase"<?php echo permMenuAttr('accounting'); ?> style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px; padding-top: 15px;" data-i18n="layout.reports_header"><?php echo htmlspecialchars(t('layout.reports_header', 'ລາຍງານ & ການເງິນ')); ?></li>

          <!-- Menu: Bank Management (ຈັດການທະນາຄານ) -->
          <li class="nav-item"<?php echo permMenuAttr('accounting'); ?>>
            <a href="<?php echo $bp; ?>pages/bank/bank.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-university text-info"></i>
              <p data-i18n="layout.bank"><?php echo htmlspecialchars(t('layout.bank', 'ຈັດການທະນາຄານ')); ?></p>
            </a>
          </li>

          <!-- Dropdown Treeview: ລາຍງານ -->
          <li class="nav-item has-treeview"<?php echo permMenuAttr('report', 'daily_report', 'all_sales', 'best_seller', 'profit_cost', 'financial', 'category_sales', 'delete_bills', 'expiry', 'shifts', 'shift_report', 'shift_close_report', 'shift_revenue_report'); ?>>
            <a class="nav-link" role="button" style="cursor: pointer;">
              <i class="nav-icon fas fa-file-invoice-dollar text-orange"></i>
              <p>
                <span data-i18n="layout.reports_group"><?php echo htmlspecialchars(t('layout.reports_group', 'ລາຍງານ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <!-- 1. ລາຍງານປະຈຳວັນ -->
              <li class="nav-item"<?php echo permMenuAttr('daily_report'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/daily_report.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-calendar-day text-info"></i>
                  <p data-i18n="layout.daily_report"><?php echo htmlspecialchars(t('layout.daily_report', 'ລາຍງານປະຈຳວັນ')); ?></p>
                </a>
              </li>

              <!-- 2. ລາຍງານການຂາຍທັງໝົດ -->
              <li class="nav-item"<?php echo permMenuAttr('all_sales'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/all_sales.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-file-invoice-dollar text-success"></i>
                  <p data-i18n="layout.all_sales"><?php echo htmlspecialchars(t('layout.all_sales', 'ລາຍງານການຂາຍທັງໝົດ')); ?></p>
                </a>
              </li>

              <!-- 3. ລາຍງານສິນຄ້າຂາຍດີ -->
              <li class="nav-item"<?php echo permMenuAttr('best_seller'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/best_seller.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-fire text-danger"></i>
                  <p data-i18n="layout.best_seller"><?php echo htmlspecialchars(t('layout.best_seller', 'ລາຍງານສິນຄ້າຂາຍດີ')); ?></p>
                </a>
              </li>

              <!-- 4. ລາຍງານກຳໄລ-ຕົ້ນທຶນ -->
              <li class="nav-item"<?php echo permMenuAttr('profit_cost'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/profit_cost.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-chart-line text-warning"></i>
                  <p data-i18n="layout.profit_cost"><?php echo htmlspecialchars(t('layout.profit_cost', 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ')); ?></p>
                </a>
              </li>

              <!-- 5. ລາຍງານການເງິນ -->
              <li class="nav-item"<?php echo permMenuAttr('financial'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/financial.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-wallet text-info"></i>
                  <p data-i18n="layout.financial"><?php echo htmlspecialchars(t('layout.financial', 'ລາຍງານການເງິນ')); ?></p>
                </a>
              </li>

              <!-- 6. ລາຍງານຕາມ/ປະເພດສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('category_sales'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/category_sales.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-layer-group text-primary"></i>
                  <p data-i18n="layout.category_sales"><?php echo htmlspecialchars(t('layout.category_sales', 'ລາຍງານຕາມປະເພດສິນຄ້າ')); ?></p>
                </a>
              </li>

              <!-- 8. ລາຍງານສິນຄ້າໝົດອາຍຸ -->
              <li class="nav-item"<?php echo permMenuAttr('report', 'expiry'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/expiry_report.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-hourglass-half text-warning"></i>
                  <p data-i18n="layout.expiry_report"><?php echo htmlspecialchars(t('layout.expiry_report', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?></p>
                </a>
              </li>

              <!-- 10. ລາຍງານປິດກະ -->
              <li class="nav-item"<?php echo permMenuAttr('shift_close_report'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/shift_close_report.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-cash-register text-info"></i>
                  <p data-i18n="layout.shift_close_report"><?php echo htmlspecialchars(t('layout.shift_close_report', 'ລາຍງານປິດກະ')); ?></p>
                </a>
              </li>

              <!-- 11. ລາຍງານລາຍຮັບຕາມກະ -->
              <li class="nav-item"<?php echo permMenuAttr('shift_revenue_report'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/shift_revenue_report.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-chart-line text-success"></i>
                  <p data-i18n="layout.shift_revenue_report"><?php echo htmlspecialchars(t('layout.shift_revenue_report', 'ລາຍງານລາຍຮັບຕາມກະ')); ?></p>
                </a>
              </li>

              <!-- 7. ປະຫວັດການລົບບິນຂາຍ -->
              <li class="nav-item"<?php echo permMenuAttr('delete_bills'); ?>>
                <a href="<?php echo $bp; ?>pages/reports/delete_bills.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-trash-alt text-danger"></i>
                  <p data-i18n="layout.delete_bills"><?php echo htmlspecialchars(t('layout.delete_bills', 'ປະຫວັດການລົບບິນຂາຍ')); ?></p>
                </a>
              </li>
              
            </ul>
          </li>

          <!-- Header: Administration & Settings -->
          <li class="nav-header text-uppercase"<?php echo permMenuAttr('users', 'permissions', 'branches', 'stores', 'print_barcode', 'exchange_rate', 'promotions', 'price_adjustment', 'printers', 'loyalty', 'expiry', 'shifts', 'database'); ?> style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px; padding-top: 15px;" data-i18n="layout.admin_header"><?php echo htmlspecialchars(t('layout.admin_header', 'ການຈັດການລະບົບ')); ?></li>

            <!-- Menu: Users Management -->
            <li class="nav-item"<?php echo permMenuAttr('users'); ?>>
              <a href="<?php echo $bp; ?>pages/users_manage/users_manage.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-users text-info"></i>
                <p data-i18n="layout.users"><?php echo htmlspecialchars(t('layout.users', 'ຈັດການຜູ້ນຳໃຊ້')); ?></p>
              </a>
            </li>

            <!-- Menu: Permission Management -->
            <li class="nav-item"<?php echo permMenuAttr('permissions'); ?>>
              <a href="<?php echo $bp; ?>pages/permissions/permissions.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-user-shield text-warning"></i>
                <p data-i18n="layout.permissions"><?php echo htmlspecialchars(t('layout.permissions', 'ກຳນົດສິດ')); ?></p>
              </a>
            </li>

            <!-- Menu: ຈັດການສາຂາ (ໂຟເດີແຍກຕ່າງຫາກ pages/branches/) -->
            <li class="nav-item"<?php echo permMenuAttr('branches'); ?>>
              <a href="<?php echo $bp; ?>pages/branches/branches.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-network-wired text-success"></i>
                <p data-i18n="layout.branches"><?php echo htmlspecialchars(t('layout.branches', 'ຈັດການສາຂາ')); ?></p>
              </a>
            </li>

            <!-- Dropdown Treeview: ຕັ້ງຄ່າລະບົບ -->
            <li class="nav-item has-treeview"<?php echo permMenuAttr('stores', 'print_barcode', 'exchange_rate', 'promotions', 'price_adjustment', 'printers', 'loyalty', 'expiry', 'shifts'); ?>>
              <a class="nav-link" role="button" style="cursor: pointer;">
                <i class="nav-icon fas fa-cogs text-secondary"></i>
                <p>
                  <span data-i18n="layout.settings_group"><?php echo htmlspecialchars(t('layout.settings_group', 'ຕັ້ງຄ່າລະບົບ')); ?></span>
                  <i class="right fas fa-angle-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <!-- 1. ຂໍ້ມູນຮ້ານ (pages/settings/stores/) -->
                <li class="nav-item"<?php echo permMenuAttr('stores'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/stores/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-store text-primary"></i>
                    <p data-i18n="layout.stores"><?php echo htmlspecialchars(t('layout.stores', 'ຂໍ້ມູນຮ້ານ')); ?></p>
                  </a>
                </li>

                <!-- 2. ພິມບາໂຄ້ດ -->
                <li class="nav-item"<?php echo permMenuAttr('print_barcode'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/print_barcode/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-barcode text-info"></i>
                    <p data-i18n="layout.print_barcode"><?php echo htmlspecialchars(t('layout.print_barcode', 'ພິມບາໂຄ້ດ')); ?></p>
                  </a>
                </li>

                <!-- 3. ອັດຕາແລກປ່ຽນເງິນ -->
                <li class="nav-item"<?php echo permMenuAttr('exchange_rate'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/exchange_rate/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-exchange-alt text-success"></i>
                    <p data-i18n="layout.exchange_rate"><?php echo htmlspecialchars(t('layout.exchange_rate', 'ອັດຕາເເລກປ່ຽນເງິນ')); ?></p>
                  </a>
                </li>

                <!-- 4. ໂປຣໂມຊັ່ນ -->
                <li class="nav-item"<?php echo permMenuAttr('promotions'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/promotions/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-percent text-danger"></i>
                    <p data-i18n="layout.promotions"><?php echo htmlspecialchars(t('layout.promotions', 'ໂປຣໂມຊັ່ນ')); ?></p>
                  </a>
                </li>

                <!-- 5. ປັບລາຄາສິນຄ້າ -->
                <li class="nav-item"<?php echo permMenuAttr('price_adjustment'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/price_adjustment/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-tags text-warning"></i>
                    <p data-i18n="layout.price_adjustment"><?php echo htmlspecialchars(t('layout.price_adjustment', 'ປັບລາຄາສິນຄ້າ')); ?></p>
                  </a>
                </li>

                <!-- 6. ຕັ້ງຄ່າປິ່ນເຕີ -->
                <li class="nav-item"<?php echo permMenuAttr('printers'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/printers/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-print text-teal"></i>
                    <p data-i18n="layout.printers"><?php echo htmlspecialchars(t('layout.printers', 'ຕັ້ງຄ່າປິ່ນເຕີ')); ?></p>
                  </a>
                </li>

                <!-- 7. ຄະແນນສະສົມລູກຄ້າ -->
                <li class="nav-item"<?php echo permMenuAttr('loyalty'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/loyalty/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-star text-warning"></i>
                    <p data-i18n="layout.loyalty"><?php echo htmlspecialchars(t('layout.loyalty', 'ຄະແນນສະສົມລູກຄ້າ')); ?></p>
                  </a>
                </li>

                <!-- 8. ຈັດການສິນຄ້າໝົດອາຍຸ (was nested inside the loyalty check, so it only showed to users who also had loyalty) -->
                <li class="nav-item"<?php echo permMenuAttr('expiry'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/expiry/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-hourglass-half text-danger"></i>
                    <p data-i18n="layout.expiry"><?php echo htmlspecialchars(t("layout.expiry", "ຈັດການສິນຄ້າໝົດອາຍຸ")); ?></p>
                  </a>
                </li>

                <!-- 9. ຈັດການກະເຮັດວຽກ -->
                <li class="nav-item"<?php echo permMenuAttr('shifts'); ?>>
                  <a href="<?php echo $bp; ?>pages/settings/shifts/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-user-clock text-info"></i>
                    <p data-i18n="layout.shifts"><?php echo htmlspecialchars(t('layout.shifts', 'ຈັດການກະເຮັດວຽກ')); ?></p>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Menu: ຖານຂໍ້ມູນ (Database Management & Backup) -->
              <li class="nav-item"<?php echo permMenuAttr('database'); ?>>
                <a href="<?php echo $bp; ?>pages/database/database.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-database text-info"></i>
                  <p data-i18n="layout.database"><?php echo htmlspecialchars(t('layout.database', 'ຖານຂໍ້ມູນ')); ?></p>
                </a>
              </li>

      </ul>
    </nav>
  </div>

  <!-- Pinned Sidebar Footer (ເສັ້ນຂີດຂັ້ນ & Wlaodev Pos System v4.10.26 ຕິດຢູ່ດ້ານລຸ່ມສຸດ ຕາມສະໄຕຣ Fortinet UI) -->
  <div class="sidebar-custom-footer" style="position: absolute; bottom: 0; left: 0; right: 0; height: 38px; border-top: 1px solid rgba(255, 255, 255, 0.18); display: flex; align-items: center; justify-content: space-between; background-color: inherit; z-index: 1040; padding: 0 14px; overflow: hidden; box-sizing: border-box; user-select: none;">
    <span class="sidebar-brand-name" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 0.75rem; font-weight: 600; color: rgba(255, 255, 255, 0.9); letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
      Wlaodev Pos System
    </span>
    <span class="sidebar-version-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight: 400; color: rgba(255, 255, 255, 0.85); font-size: 0.78rem; letter-spacing: 0.4px; white-space: nowrap; display: inline-block;">
      v4.10.26
    </span>
  </div>
</aside>
