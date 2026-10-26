<?php
/*
 * ສ່ວນ: ເມນູສິນຄ້າ ແລະ ສາງ ໃນ sidebar
 * ຂໍ້ມູນສິນຄ້າ (ໝວດໝູ່/ຫົວໜ່ວຍ/ລາຍການສິນຄ້າ/ຜູ້ສະໜອງ) → ເຊັກສະຕັອກ → ນຳສິນຄ້າເຂົ້າສາງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ layouts/sidebar.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປ $bp ຮ່ວມກັນ).
 */
?>
          <!-- Header: Inventory & Products -->
          <!-- <li class="nav-header text-uppercase" style="color: rgba(255,255,255,0.85); font-size: 0.75rem; letter-spacing: 1px; padding-top: 15px;">ການຈັດການສິນຄ້າ</li> -->

          <!-- Dropdown Treeview: ຂໍ້ມູນສິນຄ້າ -->
          <li class="nav-item has-treeview"<?php echo permMenuAttr('categories', 'units', 'products', 'suppliers'); ?>>
            <a class="nav-link" role="button" style="cursor: pointer;">
              <i class="nav-icon fas fa-boxes text-primary"></i>
              <p>
                <span data-i18n="layout.products_group"><?php echo htmlspecialchars(t('layout.products_group', 'ຂໍ້ມູນສິນຄ້າ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <!-- Sub-menu: ປະເພດສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('categories'); ?>>
                <a href="<?php echo $bp; ?>pages/categories/categories.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-th-list text-info"></i>
                  <p data-i18n="layout.categories"><?php echo htmlspecialchars(t('layout.categories', 'ໝວດໝູ່ສິນຄ້າ')); ?></p>
                </a>
              </li>


              <!-- Sub-menu: ຈັດການຫົວໜ່ວຍ -->
              <li class="nav-item"<?php echo permMenuAttr('units'); ?>>
                <a href="<?php echo $bp; ?>pages/units/units.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-ruler-combined text-info"></i>
                  <p data-i18n="layout.units"><?php echo htmlspecialchars(t('layout.units', 'ຈັດການຫົວໜ່ວຍ')); ?></p>
                </a>
              </li>

              <!-- Sub-menu: ລາຍການສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('products'); ?>>
                <a href="<?php echo $bp; ?>pages/products/products.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-box text-primary"></i>
                  <p data-i18n="layout.products"><?php echo htmlspecialchars(t('layout.products', 'ລາຍການສິນຄ້າ')); ?></p>
                </a>
              </li>

              <!-- Sub-menu: ຈັດການຜູ້ສະໜອງ -->
              <li class="nav-item"<?php echo permMenuAttr('suppliers'); ?>>
                <a href="<?php echo $bp; ?>pages/suppliers/suppliers.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-truck text-success"></i>
                  <p data-i18n="layout.suppliers"><?php echo htmlspecialchars(t('layout.suppliers', 'ຈັດການຜູ້ສະໜອງ')); ?></p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Dropdown Treeview: ເຊັກສະຕັອກ & ທີ່ຕັ້ງ (ໂຊນ, ຊັ້ນວາງ) -->
          <li class="nav-item has-treeview"<?php echo permMenuAttr('stock_check', 'stock_count'); ?>>
            <a class="nav-link" role="button" style="cursor: pointer;">
              <i class="nav-icon fas fa-clipboard-check text-success"></i>
              <p>
                <span data-i18n="layout.stock_check_group"><?php echo htmlspecialchars(t('layout.stock_check_group', 'ກວດສອບສິນຄ້າ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <!-- Sub-menu 1: ເຊັກສະຕັອກ -->
              <li class="nav-item"<?php echo permMenuAttr('stock_check', 'stock_count'); ?>>
                <a href="<?php echo $bp; ?>pages/stock_check/stock_check.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-clipboard-list text-success"></i>
                  <p data-i18n="layout.stock_check_list"><?php echo htmlspecialchars(t('layout.stock_check_list', 'ເຊັກສະຕັອກ')); ?></p>
                </a>
              </li>
              <!-- Sub-menu 2: ໂຊນສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('stock_check', 'stock_count'); ?>>
                <a href="<?php echo $bp; ?>pages/stock_check/zones.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-map-marker-alt text-primary"></i>
                  <p data-i18n="layout.zones"><?php echo htmlspecialchars(t('layout.zones', 'ໂຊນສິນຄ້າ')); ?></p>
                </a>
              </li>
              <!-- Sub-menu 3: ຊັ້ນວາງສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('stock_check', 'stock_count'); ?>>
                <a href="<?php echo $bp; ?>pages/stock_check/shelves.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-layer-group text-info"></i>
                  <p data-i18n="layout.shelves"><?php echo htmlspecialchars(t('layout.shelves', 'ຊັ້ນວາງສິນຄ້າ')); ?></p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Dropdown Treeview: ນຳສິນຄ້າເຂົ້າສາງ -->
          <li class="nav-item has-treeview"<?php echo permMenuAttr('import_stock', 'import_list', 'stock_transfer', 'transfer_history'); ?>>
            <a class="nav-link" role="button" style="cursor: pointer;">
              <i class="nav-icon fas fa-warehouse text-warning"></i>
              <p>
                <span data-i18n="layout.stock_intake_group"><?php echo htmlspecialchars(t('layout.stock_intake_group', 'ນຳສິນຄ້າເຂົ້າສາງ')); ?></span>
                <i class="right fas fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <!-- Sub-menu: ນຳເຂົ້າສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('import_stock'); ?>>
                <a href="<?php echo $bp; ?>pages/import_stock/import_stock.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-truck-loading text-warning"></i>
                  <p data-i18n="layout.import_stock"><?php echo htmlspecialchars(t('layout.import_stock', 'ນຳເຂົ້າສິນຄ້າ')); ?></p>
                </a>
              </li>

              <!-- Sub-menu: ລາຍການສິນຄ້າຮັບເຂົ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('import_list', 'import_stock'); ?>>
                <a href="<?php echo $bp; ?>pages/import_stock/import_list.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-list-alt text-success"></i>
                  <p data-i18n="layout.import_list"><?php echo htmlspecialchars(t('layout.import_list', 'ລາຍການສິນຄ້າຮັບເຂົ້າ')); ?></p>
                </a>
              </li>

              <!-- Sub-menu: ໂອນສິນຄ້າລະຫວ່າງສາຂາ -->
              <li class="nav-item"<?php echo permMenuAttr('stock_transfer'); ?>>
                <a href="<?php echo $bp; ?>pages/import_stock/stock_transfer.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-exchange-alt text-info"></i>
                  <p data-i18n="layout.stock_transfer"><?php echo htmlspecialchars(t('layout.stock_transfer', 'ໂອນສິນຄ້າລະຫວ່າງສາຂາ')); ?></p>
                </a>
              </li>

              <!-- Sub-menu: ປະຫວັດການໂອນສິນຄ້າ -->
              <li class="nav-item"<?php echo permMenuAttr('transfer_history'); ?>>
                <a href="<?php echo $bp; ?>pages/import_stock/transfer_history.php" target="frame" class="nav-link">
                  <i class="nav-icon fas fa-history text-secondary"></i>
                  <p data-i18n="layout.transfer_history"><?php echo htmlspecialchars(t('layout.transfer_history', 'ປະຫວັດການໂອນສິນຄ້າ')); ?></p>
                </a>
              </li>
            </ul>
          </li>
