<?php 
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
  <div class="sidebar" style="height: calc(100vh - 64px); overflow-y: auto; overflow-x: hidden; -webkit-overflow-scrolling: touch; touch-action: pan-y;">
  
    <!-- ===== User Panel (ຂໍ້ມູນຜູ້ໃຊ້ຢູ່ sidebar) ===== -->
    <?php
      // ດຶງຂໍ້ມູນ user ຈາກ session
      $sb_display_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
      if ($sb_display_name === '') $sb_display_name = $_SESSION['username'] ?? 'admin';
      $sb_profile_img = $_SESSION['profile_img'] ?? 'default.png';
      if (empty($sb_profile_img) || !file_exists(__DIR__ . '/../assets/img/users/' . $sb_profile_img)) {
        $sb_profile_img = 'default.png';
      }
      $sb_img_path = $bp . 'assets/img/users/' . $sb_profile_img;
    ?>
    <div class="user-panel mt-3 pb-3 mb-2 d-flex align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.15);">
      <div class="image" style="flex-shrink:0;">
        <img src="<?php echo htmlspecialchars($sb_img_path); ?>" class="img-circle elevation-2"
             alt="User Avatar"
             style="width: 42px; height: 42px; object-fit: cover; border: 2px solid rgba(255,255,255,0.55);">
      </div>
      <div class="info" style="padding-left: 10px; overflow: hidden;">
        <span style="display: block; color: #ffffff; font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
          <?php echo htmlspecialchars($sb_display_name); ?>
        </span>
        <span style="font-size: 0.72rem; color: #4ade80; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 2px;">
          <i class="fas fa-circle" style="font-size: 6px;"></i> <span data-i18n="layout.online_status"><?php echo htmlspecialchars(t('layout.online_status', 'ກຳລັງໃຊ້ງານ')); ?></span>
        </span>
      </div>
    </div>
    <!-- /User Panel -->

    <!-- Dynamic Menu Items Based on Permissions -->
    <nav class="mt-2 pb-4">
      <ul class="nav nav-pills nav-sidebar flex-column nav-flat" role="menu" data-accordion="true">
        
        <?php if (hasPermission('dashboard')): ?>
          <!-- Header: Main menu -->
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px;" data-i18n="layout.menu_header"><?php echo htmlspecialchars(t('layout.menu_header', 'ເມນູ')); ?></li>

          <!-- Menu: Dashboard -->
          <li class="nav-item">
            <a href="home.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-chart-line text-success"></i>
              <p data-i18n="layout.dashboard"><?php echo htmlspecialchars(t('layout.dashboard', 'ດາດສ໌ບອດ')); ?></p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermission('sale') || hasPermission('item_sales')): ?>
          <!-- Dropdown Treeview: ຂາຍສິນຄ້າ POS & ລາຍການຂາຍ -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cash-register text-warning"></i>
              <p>
                <span data-i18n="layout.pos_group"><?php echo htmlspecialchars(t('layout.pos_group', 'ຂາຍສິນຄ້າ POS')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if (hasPermission('sale')): ?>
              <!-- Sub-menu 1: ຂາຍສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/pos/pos.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-shopping-cart text-warning"></i>
                  <p data-i18n="layout.pos_sell"><?php echo htmlspecialchars(t('layout.pos_sell', 'ຂາຍສິນຄ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('item_sales') || hasPermission('sale')): ?>
              <!-- Sub-menu 2: ລາຍການຂາຍສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/item_sales.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-list-alt text-purple"></i>
                  <p data-i18n="layout.pos_item_sales"><?php echo htmlspecialchars(t('layout.pos_item_sales', 'ລາຍການຂາຍສິນຄ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (hasPermission('customers')): ?>
          <!-- Menu: Customer Management -->
          <li class="nav-item">
            <a href="<?php echo $bp; ?>pages/customers/customers.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-user-friends text-info"></i>
              <p data-i18n="layout.customers"><?php echo htmlspecialchars(t('layout.customers', 'ຈັດການລູກຄ້າ')); ?></p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermission('quotations')): ?>
          <!-- Menu: Quotations (ໃບສະເໜີລາຄາ) — standalone top-level item, own module -->
          <li class="nav-item">
            <a href="<?php echo $bp; ?>pages/quotations/quotations.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-file-invoice-dollar text-warning"></i>
              <p data-i18n="layout.quotations"><?php echo htmlspecialchars(t('layout.quotations', 'ໃບສະເໜີລາຄາ')); ?></p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermission('categories') || hasPermission('units') || hasPermission('products') || hasPermission('suppliers') || hasPermission('import_stock') || hasPermission('import_list') || hasPermission('stock_transfer') || hasPermission('transfer_history')): ?>
          <!-- Header: Inventory & Products -->
          <!-- <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px; padding-top: 15px;">ການຈັດການສິນຄ້າ</li> -->

          <?php if (hasPermission('categories') || hasPermission('units') || hasPermission('products') || hasPermission('suppliers')): ?>
          <!-- Dropdown Treeview: ຂໍ້ມູນສິນຄ້າ -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-boxes text-primary"></i>
              <p>
                <span data-i18n="layout.products_group"><?php echo htmlspecialchars(t('layout.products_group', 'ຂໍ້ມູນສິນຄ້າ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if (hasPermission('categories')): ?>
              <!-- Sub-menu: ປະເພດສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/categories/categories.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-th-list text-info"></i>
                  <p data-i18n="layout.categories"><?php echo htmlspecialchars(t('layout.categories', 'ໝວດໝູ່ສິນຄ້າ')); ?></p>
                </a>
              </li>

              <?php endif; ?>

              <?php if (hasPermission('units')): ?>
              <!-- Sub-menu: ຈັດການຫົວໜ່ວຍ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/units/units.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-ruler-combined text-info"></i>
                  <p data-i18n="layout.units"><?php echo htmlspecialchars(t('layout.units', 'ຈັດການຫົວໜ່ວຍ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('products')): ?>
              <!-- Sub-menu: ລາຍການສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/products/products.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-box text-primary"></i>
                  <p data-i18n="layout.products"><?php echo htmlspecialchars(t('layout.products', 'ລາຍການສິນຄ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('suppliers')): ?>
              <!-- Sub-menu: ຈັດການຜູ້ສະໜອງ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/suppliers/suppliers.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-truck text-success"></i>
                  <p data-i18n="layout.suppliers"><?php echo htmlspecialchars(t('layout.suppliers', 'ຈັດການຜູ້ສະໜອງ')); ?></p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if (hasPermission('import_stock') || hasPermission('import_list') || hasPermission('stock_transfer') || hasPermission('transfer_history')): ?>
          <!-- Dropdown Treeview: ນຳສິນຄ້າເຂົ້າສາງ -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-warehouse text-warning"></i>
              <p>
                <span data-i18n="layout.stock_intake_group"><?php echo htmlspecialchars(t('layout.stock_intake_group', 'ນຳສິນຄ້າເຂົ້າສາງ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if (hasPermission('import_stock')): ?>
              <!-- Sub-menu: ນຳເຂົ້າສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/import_stock/import_stock.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-truck-loading text-warning"></i>
                  <p data-i18n="layout.import_stock"><?php echo htmlspecialchars(t('layout.import_stock', 'ນຳເຂົ້າສິນຄ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('import_list') || hasPermission('import_stock')): ?>
              <!-- Sub-menu: ລາຍການສິນຄ້າຮັບເຂົ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/import_stock/import_list.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-list-alt text-success"></i>
                  <p data-i18n="layout.import_list"><?php echo htmlspecialchars(t('layout.import_list', 'ລາຍການສິນຄ້າຮັບເຂົ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('stock_transfer')): ?>
              <!-- Sub-menu: ໂອນສິນຄ້າລະຫວ່າງສາຂາ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/import_stock/stock_transfer.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-exchange-alt text-info"></i>
                  <p data-i18n="layout.stock_transfer"><?php echo htmlspecialchars(t('layout.stock_transfer', 'ໂອນສິນຄ້າລະຫວ່າງສາຂາ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('transfer_history')): ?>
              <!-- Sub-menu: ປະຫວັດການໂອນສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/import_stock/transfer_history.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-history text-secondary"></i>
                  <p data-i18n="layout.transfer_history"><?php echo htmlspecialchars(t('layout.transfer_history', 'ປະຫວັດການໂອນສິນຄ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (hasPermission('accounting')): ?>
          <!-- Header: Reports & Accounting -->
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px; padding-top: 15px;" data-i18n="layout.reports_header"><?php echo htmlspecialchars(t('layout.reports_header', 'ລາຍງານ & ການເງິນ')); ?></li>

          <!-- Menu: Bank Management (ຈັດການທະນາຄານ) -->
          <li class="nav-item">
            <a href="<?php echo $bp; ?>pages/bank/bank.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-university text-info"></i>
              <p data-i18n="layout.bank"><?php echo htmlspecialchars(t('layout.bank', 'ຈັດການທະນາຄານ')); ?></p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (hasPermission('report') || hasPermission('daily_report') || hasPermission('all_sales') || hasPermission('best_seller') || hasPermission('profit_cost') || hasPermission('financial') || hasPermission('category_sales') || hasPermission('delete_bills')): ?>
          <!-- Dropdown Treeview: ລາຍງານ -->
          <li class="nav-item has-treeview">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-file-invoice-dollar text-orange"></i>
              <p>
                <span data-i18n="layout.reports_group"><?php echo htmlspecialchars(t('layout.reports_group', 'ລາຍງານ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if (hasPermission('daily_report')): ?>
              <!-- 1. ລາຍງານປະຈຳວັນ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/daily_report.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-calendar-day text-info"></i>
                  <p data-i18n="layout.daily_report"><?php echo htmlspecialchars(t('layout.daily_report', 'ລາຍງານປະຈຳວັນ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('all_sales')): ?>
              <!-- 2. ລາຍງານການຂາຍທັງໝົດ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/all_sales.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-file-invoice-dollar text-success"></i>
                  <p data-i18n="layout.all_sales"><?php echo htmlspecialchars(t('layout.all_sales', 'ລາຍງານການຂາຍທັງໝົດ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('best_seller')): ?>
              <!-- 3. ລາຍງານສິນຄ້າຂາຍດີ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/best_seller.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-fire text-danger"></i>
                  <p data-i18n="layout.best_seller"><?php echo htmlspecialchars(t('layout.best_seller', 'ລາຍງານສິນຄ້າຂາຍດີ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('profit_cost')): ?>
              <!-- 4. ລາຍງານກຳໄລ-ຕົ້ນທຶນ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/profit_cost.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-chart-line text-warning"></i>
                  <p data-i18n="layout.profit_cost"><?php echo htmlspecialchars(t('layout.profit_cost', 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('financial')): ?>
              <!-- 5. ລາຍງານການເງິນ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/financial.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-wallet text-info"></i>
                  <p data-i18n="layout.financial"><?php echo htmlspecialchars(t('layout.financial', 'ລາຍງານການເງິນ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('category_sales')): ?>
              <!-- 6. ລາຍງານຕາມ/ປະເພດສິນຄ້າ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/category_sales.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-layer-group text-primary"></i>
                  <p data-i18n="layout.category_sales"><?php echo htmlspecialchars(t('layout.category_sales', 'ລາຍງານຕາມປະເພດສິນຄ້າ')); ?></p>
                </a>
              </li>
              <?php endif; ?>

              <?php if (hasPermission('delete_bills')): ?>
              <!-- 7. ປະຫວັດການລົບບິນຂາຍ -->
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/reports/delete_bills.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-trash-alt text-danger"></i>
                  <p data-i18n="layout.delete_bills"><?php echo htmlspecialchars(t('layout.delete_bills', 'ປະຫວັດການລົບບິນຂາຍ')); ?></p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (hasPermission('users') || hasPermission('permissions') || hasPermission('branches') || hasPermission('stores') || hasPermission('print_barcode') || hasPermission('exchange_rate') || hasPermission('promotions') || hasPermission('price_adjustment') || hasPermission('printers') || hasPermission('database')): ?>
          <!-- Header: Administration & Settings -->
          <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px; padding-top: 15px;" data-i18n="layout.admin_header"><?php echo htmlspecialchars(t('layout.admin_header', 'ການຈັດການລະບົບ')); ?></li>

          <?php if (hasPermission('users')): ?>
            <!-- Menu: Users Management -->
            <li class="nav-item">
              <a href="<?php echo $bp; ?>pages/users_manage/users_manage.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-users text-info"></i>
                <p data-i18n="layout.users"><?php echo htmlspecialchars(t('layout.users', 'ຈັດການຜູ້ນຳໃຊ້')); ?></p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (hasPermission('permissions')): ?>
            <!-- Menu: Permission Management -->
            <li class="nav-item">
              <a href="<?php echo $bp; ?>pages/permissions/permissions.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-user-shield text-warning"></i>
                <p data-i18n="layout.permissions"><?php echo htmlspecialchars(t('layout.permissions', 'ກຳນົດສິດ')); ?></p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (hasPermission('branches')): ?>
            <!-- Menu: ຈັດການສາຂາ (ໂຟເດີແຍກຕ່າງຫາກ pages/branches/) -->
            <li class="nav-item">
              <a href="<?php echo $bp; ?>pages/branches/branches.php" target="frame" class="nav-link">
                <i class="nav-icon fas fa-network-wired text-success"></i>
                <p data-i18n="layout.branches"><?php echo htmlspecialchars(t('layout.branches', 'ຈັດການສາຂາ')); ?></p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (hasPermission('stores') || hasPermission('print_barcode') || hasPermission('exchange_rate') || hasPermission('promotions') || hasPermission('price_adjustment') || hasPermission('printers')): ?>
            <!-- Dropdown Treeview: ຕັ້ງຄ່າລະບົບ -->
            <li class="nav-item has-treeview">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-cogs text-secondary"></i>
                <p>
                  <span data-i18n="layout.settings_group"><?php echo htmlspecialchars(t('layout.settings_group', 'ຕັ້ງຄ່າລະບົບ')); ?></span>
                  <i class="right fas fa-angle-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <?php if (hasPermission('stores')): ?>
                <!-- 1. ຂໍ້ມູນຮ້ານ (pages/settings/stores/) -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/stores/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-store text-primary"></i>
                    <p data-i18n="layout.stores"><?php echo htmlspecialchars(t('layout.stores', 'ຂໍ້ມູນຮ້ານ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('print_barcode')): ?>
                <!-- 2. ພິມບາໂຄ້ດ -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/print_barcode/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-barcode text-info"></i>
                    <p data-i18n="layout.print_barcode"><?php echo htmlspecialchars(t('layout.print_barcode', 'ພິມບາໂຄ້ດ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('exchange_rate')): ?>
                <!-- 3. ອັດຕາແລກປ່ຽນເງິນ -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/exchange_rate/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-exchange-alt text-success"></i>
                    <p data-i18n="layout.exchange_rate"><?php echo htmlspecialchars(t('layout.exchange_rate', 'ອັດຕາເເລກປ່ຽນເງິນ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('promotions')): ?>
                <!-- 4. ໂປຣໂມຊັ່ນ -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/promotions/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-percent text-danger"></i>
                    <p data-i18n="layout.promotions"><?php echo htmlspecialchars(t('layout.promotions', 'ໂປຣໂມຊັ່ນ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('price_adjustment')): ?>
                <!-- 5. ປັບລາຄາສິນຄ້າ -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/price_adjustment/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-tags text-warning"></i>
                    <p data-i18n="layout.price_adjustment"><?php echo htmlspecialchars(t('layout.price_adjustment', 'ປັບລາຄາສິນຄ້າ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('printers')): ?>
                <!-- 6. ຕັ້ງຄ່າປິ່ນເຕີ -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/printers/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-print text-teal"></i>
                    <p data-i18n="layout.printers"><?php echo htmlspecialchars(t('layout.printers', 'ຕັ້ງຄ່າປິ່ນເຕີ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('loyalty')): ?>
                <!-- 7. ຄະແນນສະສົມລູກຄ້າ -->
                <li class="nav-item">
                  <a href="<?php echo $bp; ?>pages/settings/loyalty/index.php" target="frame" class="nav-link">
                    <i class="nav-icon fas fa-star text-warning"></i>
                    <p data-i18n="layout.loyalty"><?php echo htmlspecialchars(t('layout.loyalty', 'ຄະແນນສະສົມລູກຄ້າ')); ?></p>
                  </a>
                </li>
                <?php endif; ?>
              </ul>
            </li>

            <!-- Menu: ຖານຂໍ້ມູນ (Database Management & Backup) -->
            <?php if (hasPermission('database')): ?>
              <li class="nav-item">
                <a href="<?php echo $bp; ?>pages/database/database.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-database text-info"></i>
                  <p data-i18n="layout.database"><?php echo htmlspecialchars(t('layout.database', 'ຖານຂໍ້ມູນ')); ?></p>
                </a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>

      </ul>
    </nav>
  </div>

</aside>
