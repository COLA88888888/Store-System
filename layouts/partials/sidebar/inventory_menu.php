<?php
/*
 * ສ່ວນ: ເມນູສິນຄ້າ ແລະ ສາງ ໃນ sidebar
 * ຂໍ້ມູນສິນຄ້າ (ໝວດໝູ່/ຫົວໜ່ວຍ/ລາຍການສິນຄ້າ/ຜູ້ສະໜອງ) → ເຊັກສະຕັອກ → ນຳສິນຄ້າເຂົ້າສາງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ layouts/sidebar.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປ $bp ຮ່ວມກັນ).
 */
?>
        <?php if (hasPermission('categories') || hasPermission('units') || hasPermission('products') || hasPermission('suppliers') || hasPermission('stock_check') || hasPermission('stock_count') || hasPermission('import_stock') || hasPermission('import_list') || hasPermission('stock_transfer') || hasPermission('transfer_history')): ?>
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

          <?php if (hasPermission('stock_check') || hasPermission('stock_count')): ?>
          <!-- Menu: ເຊັກສະຕັອກ (ນັບສິນຄ້າຈິງທຽບກັບລະບົບ) — ຜູ້ຈັດການ ຫຼື ພະນັກງານນັບ -->
          <li class="nav-item">
            <a href="<?php echo $bp; ?>pages/stock_check/stock_check.php" target="frame" class="nav-link">
              <i class="nav-icon fas fa-clipboard-check text-success"></i>
              <p data-i18n="layout.stock_check"><?php echo htmlspecialchars(t('layout.stock_check', 'ເຊັກສະຕັອກ')); ?></p>
            </a>
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
