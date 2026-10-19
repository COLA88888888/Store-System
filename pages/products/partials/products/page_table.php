<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ: ຫົວໜ້າ ແລະ ຕາຕະລາງສິນຄ້າ
 * ຫົວໜ້າ, ແຈ້ງເຕືອນຜົນການບັນທຶກ, ຕົວກັ່ນຕອງ (ສາຂາ/ປະເພດ/ຄົ້ນຫາ), ຕາຕະລາງສິນຄ້າ ແລະ ປຸ່ມ
 * pagination.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/products.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- Page Header -->
  <div class="row mb-3 align-items-center">
    <div class="col-sm-6">
      <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
        <i class="fas fa-box text-primary mr-2"></i> <?php echo htmlspecialchars(t('products.page_title', 'ລາຍງານສິນຄ້າທັງໝົດ')); ?>
      </h5>
    </div>
    <div class="col-sm-6 text-right">
      <?php if (hasPermission('products', 'add')): ?>
        <button type="button" class="btn btn-primary px-3" data-toggle="modal" data-target="#addProductModal" style="border-radius: 6px; font-weight: 600; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('products.btn_add_new', 'ເພີ່ມສິນຄ້າໃໝ່')); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- SweetAlert Notification -->
  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($message_type === 'success'): ?>
        Swal.fire({ icon: 'success', title: '<?php echo htmlspecialchars(t('products.alert_success_title', 'ສຳເລັດ'), ENT_QUOTES); ?>', text: '<?php echo addslashes($message); ?>', showConfirmButton: false, timer: 1500 });
        <?php elseif ($message_type === 'warning'): ?>
        Swal.fire({ icon: 'warning', title: '<?php echo htmlspecialchars(t('products.alert_notice_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>', text: '<?php echo addslashes($message); ?>', confirmButtonColor: '#2563eb', confirmButtonText: '<?php echo htmlspecialchars(t('products.btn_ok', 'ຕົກລົງ'), ENT_QUOTES); ?>' });
        <?php else: ?>
        Swal.fire({ icon: 'error', title: '<?php echo htmlspecialchars(t('products.alert_notice_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>', text: '<?php echo addslashes($message); ?>', confirmButtonColor: '#2563eb', confirmButtonText: '<?php echo htmlspecialchars(t('products.btn_ok', 'ຕົກລົງ'), ENT_QUOTES); ?>' });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <!-- Main Card -->
  <div class="category-card">

    <!-- Card Header: Title + Per Page Selector (left) + Category Filter & Search (right) -->
    <div class="category-card-header d-flex justify-content-between align-items-center" style="gap: 12px; flex-wrap: wrap;">

      <!-- Left: Title & Per Page selector (ບັອກໂຊລາຍການສິນຄ້າ) -->
      <div class="d-flex align-items-center" style="gap: 14px; flex-wrap: wrap;">
        <div class="d-flex align-items-center" style="gap: 6px;">
          <select id="perPageHeaderSelect" class="form-control form-control-sm bg-light font-weight-bold text-primary" style="width: 88px; height: 34px; border-radius: 8px; font-size: 0.88rem;" onchange="changePerPage(this.value)">
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="30">30</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all"><?php echo htmlspecialchars(t('products.per_page_all', 'ທັງໝົດ')); ?></option>
          </select>
        </div>
      </div>

      <!-- Right Controls: Store Filter + Category Filter + Search (ຂວາ) -->
      <div class="d-flex align-items-center" style="gap: 10px; flex-wrap: wrap;">
        <!-- Dropdown: ເລືອກສາຂາ — main branch / admin only; a sub-branch user is locked to
             their own branch. Only worth showing once there's more than one branch. -->
        <?php if (($canAllBranches) && count($stores) > 1): ?>
        <div class="prod-filter-wrap" style="width: 200px;">
          <select id="storeFilter" class="form-control font-weight-bold" onchange="switchStoreFilter(this.value)">
            <?php foreach ($stores as $st): ?>
              <option value="<?php echo $st['store_id']; ?>" <?php echo ($selectedStoreId == $st['store_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('products.store_main_suffix', '(ສາງຫຼັກ)')) : htmlspecialchars(t('products.store_branch_suffix', '(ສາຂາຍ່ອຍ)')); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Dropdown: ເລືອກປະເພດ (ທາງໜ້າບັອກຄົ້ນຫາ) -->
        <div class="prod-filter-wrap" style="width: 200px;">
          <select id="categoryFilter" class="form-control" onchange="filterProducts()">
            <option value=""><?php echo htmlspecialchars(t('products.category_filter_placeholder', '--ເລືອກປະເພດ--')); ?></option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Search: ບັອກຄົ້ນຫາ -->
        <div class="search-box-wrap" style="width: 250px;">
          <i class="fas fa-search"></i>
          <input type="text" id="productSearch" class="form-control" placeholder="<?php echo htmlspecialchars(t('products.search_placeholder', 'ຄົ້ນຫາ ຊື່, ບາໂຄ້ດ...')); ?>" onkeyup="filterProducts()">
        </div>
      </div>

    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table-custom" id="productTable">
        <thead>
          <tr>
            <th class="text-center" style="width: 65px;"><?php echo htmlspecialchars(t('products.col_no', 'ລຳດັບ')); ?></th>
            <th class="text-center" style="width: 85px;"><?php echo htmlspecialchars(t('products.col_image', 'ຮູບພາບ')); ?></th>
            <th class="text-center" style="width: 105px;"><?php echo htmlspecialchars(t('products.col_code', 'ລະຫັດ')); ?></th>
            <th style="width: 175px;"><?php echo htmlspecialchars(t('products.col_barcode', 'ບາໂຄ້ດ')); ?></th>
            <th style="min-width: 200px;"><?php echo htmlspecialchars(t('products.col_name', 'ຊື່ສິນຄ້າ')); ?></th>
            <th class="text-right" style="width: 125px;"><?php echo htmlspecialchars(t('products.col_bprice', 'ລາຄາຊື້')); ?></th>
            <th class="text-right" style="width: 125px;"><?php echo htmlspecialchars(t('products.col_price', 'ລາຄາຂາຍ')); ?></th>
            <?php if ($isAdmin): ?>
              <th class="text-center" style="width: 95px;"><?php echo htmlspecialchars(t('products.col_qty', 'ຈຳນວນ')); ?></th>
            <?php endif; ?>
            <th class="text-center" style="width: 85px;"><?php echo htmlspecialchars(t('products.col_manage', 'ຈັດການ')); ?></th>
          </tr>
        </thead>
        <tbody>
          <!-- ບັອກສະແດງເມື່ອບໍ່ມີຂໍ້ມູນສິນຄ້າ ຫຼື ຄົ້ນຫາ/ເລືອກປະເພດແລ້ວບໍ່ພົບ -->
          <tr id="noProductDataRow" style="<?php echo empty($products) ? '' : 'display: none;'; ?>">
            <td colspan="<?php echo $isAdmin ? 9 : 8; ?>" class="text-center py-5 text-muted">
              <i class="fas fa-box-open fa-2x mb-2 d-block text-secondary"></i>
              <span class="font-weight-bold d-block" style="font-size: 1.05rem; color: #64748b;"><?php echo htmlspecialchars(t('products.empty_state', 'ບໍ່ມີຂໍ້ມູນສິນຄ້າ')); ?></span>
            </td>
          </tr>
          <?php if (!empty($products)): ?>
            <?php $rowNo = 1; foreach ($products as $p): ?>
              <?php
                $extraBarcodes = [];
                if (!empty($productUnitsMap[$p['product_id']])) {
                    foreach ($productUnitsMap[$p['product_id']] as $u) {
                        if (!empty($u['barcode'])) {
                            $extraBarcodes[] = $u['barcode'];
                        }
                    }
                }
                $allBarcodes = array_filter(array_merge([$p['barcode'] ?? ''], $extraBarcodes));
              ?>
              <?php $rowQty = (float)($p['qty'] ?? 0); ?>
              <tr class="product-row" data-category="<?php echo $p['category_id']; ?>" data-barcodes="<?php echo htmlspecialchars(strtolower(implode(',', $allBarcodes))); ?>" data-bval="<?php echo convertFromKip((float)($p['bprice'] ?? 0) * $rowQty, $__pdc['code'], $__pdc['rates']); ?>" data-pval="<?php echo convertFromKip((float)($p['price'] ?? 0) * $rowQty, $__pdc['code'], $__pdc['rates']); ?>">

                <!-- ລຳດັບ -->
                <td class="text-center">
                  <span class="text-muted font-weight-bold" style="font-size: 0.85rem;"><?php echo $rowNo++; ?></span>
                </td>

                <!-- ຮູບພາບ (ຂະໜາດ 50x50) -->
                <td class="text-center">
                  <?php
                    $imgPath = getProductImagePath($p['img_url'] ?? '');
                  ?>
                  <?php if ($imgPath): ?>
                    <img src="<?php echo htmlspecialchars($imgPath); ?>"
                         style="width:50px; height:50px; object-fit:cover; background:#fff; padding:0; border-radius:10px; border:1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.04);"
                         alt="<?php echo htmlspecialchars($p['product_name']); ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="width:50px; height:50px; border-radius:10px; background:#f1f5f9; border:1px solid #e2e8f0; display:none; align-items:center; justify-content:center; margin: 0 auto;">
                      <i class="fas fa-image" style="color:#cbd5e1; font-size:1.2rem;"></i>
                    </div>
                  <?php else: ?>
                    <div style="width:50px; height:50px; border-radius:10px; background:#f1f5f9; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; margin: 0 auto;">
                      <i class="fas fa-image" style="color:#cbd5e1; font-size:1.2rem;"></i>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- ລະຫັດສິນຄ້າ (ແຍກຕ່າງຫາກ: ສາຂາ=ແດງ, ປະເພດ=ຟ້າ, ສິນຄ້າ=ດຳ) -->
                <td class="text-center product-code-cell">
                  <?php echo formatProductIdColored($p['product_id'], $p['category_id'] ?? null, $p['store_code'] ?? null); ?>
                </td>

                <!-- ບາໂຄ້ດ (ແຍກຕ່າງຫາກ - ພ້ອມໄອຄອນປິ່ນເຕີໃກ້ບາໂຄ້ດ) -->
                <td class="product-barcode-cell" style="white-space: nowrap;">
                  <div class="d-flex align-items-center" style="gap: 8px;">
                    <?php if (!empty($p['barcode'])): ?>
                      <span style="font-family: 'Courier New', monospace; font-size: 0.88rem; font-weight: 600; color: #334155; letter-spacing: 0.5px;">
                        <i class="fas fa-barcode mr-1 text-muted" style="font-size: 0.82rem;"></i><?php echo htmlspecialchars($p['barcode']); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- ຊື່ສິນຄ້າ -->
                <td class="product-name-cell">
                  <span class="font-weight-bold" style="color: #1e293b;"><?php echo htmlspecialchars($p['product_name']); ?></span>
                  <?php if (!empty($p['unit'])): ?>
                    <span class="badge badge-light border ml-1 px-2 py-0.5" style="font-size:0.78rem; color:#64748b; font-weight: 500;">
                      <i class="fas fa-box-open mr-1" style="font-size:0.72rem; color:#94a3b8;"></i><?php echo htmlspecialchars($p['unit']); ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($p['expiry_date'])): ?>
                    <?php
                      $expDaysLeft = (int)floor((strtotime($p['expiry_date']) - strtotime(date('Y-m-d'))) / 86400);
                      $expClass = $expDaysLeft < 0 ? 'badge-danger' : ($expDaysLeft <= $expiryAlertDays ? 'badge-warning text-dark' : 'badge-light border text-muted');
                    ?>
                    <span class="badge <?php echo $expClass; ?> ml-1 px-2 py-0.5" style="font-size:0.76rem; font-weight: 600;" title="<?php echo htmlspecialchars(t('products.field_expiry_date', 'ວັນໝົດອາຍຸ')); ?>">
                      <i class="far fa-calendar-alt mr-1"></i>EXP <?php echo date('d/m/Y', strtotime($p['expiry_date'])); ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- ລາຄາຊື້ — ເຊື່ອງໄວ້ສຳລັບສິນຄ້າ/ບໍລິການທີ່ "ບໍ່ຕັດສະຕັອກ" (cut_qty = 0) ຖ້າຍັງບໍ່ໄດ້ຕັ້ງຕົ້ນທຶນ
                     (ບໍ່ຢາກໃຫ້ສະແດງ "0" ຄືກັບວ່າຕົ້ນທຶນແມ່ນ 0 ແທ້ໆ) — ແຕ່ຖ້າມີການຕັ້ງຕົ້ນທຶນໄວ້ ກໍ່ໃຫ້ສະແດງປົກກະຕິ -->
                <td class="text-right" style="color: #64748b; font-size: 0.9rem; white-space: nowrap;">
                  <?php if (intval($p['cut_qty'] ?? 1) === 0 && floatval($p['bprice'] ?? 0) <= 0): ?>
                    <span class="text-muted">-</span>
                  <?php else: ?>
                    <?php echo displayMoney($pdo, $p['bprice']); ?>
                  <?php endif; ?>
                </td>

                <!-- ລາຄາຂາຍ -->
                <td class="text-right font-weight-bold" style="color: #16a34a; white-space: nowrap;">
                  <?php echo displayMoney($pdo, $p['price']); ?>
                </td>

                <?php if ($isAdmin): ?>
                  <!-- ຈຳນວນ (ສະແດງສະເພາະແອັດມິນ) — ເຊື່ອງໄວ້ສຳລັບສິນຄ້າທີ່ຕັ້ງ "ບໍ່ຕັດສະຕັອກ"
                       (cut_qty = 0) ເພາະຈຳນວນບໍ່ມີຄວາມໝາຍສຳລັບສິນຄ້າ/ບໍລິການແບບນີ້ -->
                  <td class="text-center" style="white-space: nowrap;">
                    <?php if (intval($p['cut_qty'] ?? 1) === 0): ?>
                      <span class="text-muted">-</span>
                    <?php else: ?>
                      <?php
                        $qty = floatval($p['qty'] ?? 0);
                        $qtyClass = $qty <= 0 ? 'badge-danger' : ($qty <= 10 ? 'badge-warning text-dark' : 'badge-success');
                        $qtyLabel = $qty <= 0 ? t('products.qty_out_of_stock', 'ໝົດແລ້ວ (0)') : (number_format($qty) . ' ' . (!empty($p['unit']) ? htmlspecialchars($p['unit']) : ''));
                      ?>
                      <span class="badge <?php echo $qtyClass; ?> px-2 py-1" style="border-radius: 6px; font-size: 0.82rem; min-width: 36px;">
                        <?php echo $qtyLabel; ?>
                      </span>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>

                <!-- ຈັດການ (ໄອຄອນແກ້ໄຂ, ລົບ ສະເພາະ) -->
                <td class="text-center" style="white-space: nowrap;">
                  <?php if (hasPermission('products', 'edit') || hasPermission('products', 'del')): ?>
                    <?php if (hasPermission('products', 'edit')): ?>
                      <button type="button" class="icon-btn icon-btn-edit mr-1" title="<?php echo htmlspecialchars(t('products.tooltip_edit', 'ແກ້ໄຂ')); ?>"
                        onclick='editProduct(<?php echo json_encode($p); ?>)'>
                        <i class="fas fa-edit"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (hasPermission('products', 'del')): ?>
                      <button type="button" class="icon-btn icon-btn-delete" title="<?php echo htmlspecialchars(t('products.tooltip_delete', 'ລົບ')); ?>"
                        onclick="confirmDeleteProduct(<?php echo $p['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($p['product_name'])); ?>')">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('products.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                  <?php endif; ?>
                </td>

              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($products)): ?>
          <?php
            // Totals = unit price x stock qty (total inventory value at cost / at retail)
            $sumBprice = 0; $sumPrice = 0;
            foreach ($products as $pp) {
              $q = (float)($pp['qty'] ?? 0);
              $sumBprice += (float)($pp['bprice'] ?? 0) * $q;
              $sumPrice  += (float)($pp['price'] ?? 0) * $q;
            }
          ?>
          <tfoot>
            <tr style="background:#f8fafc; font-weight:800; border-top:2px solid #cbd5e1;">
              <td colspan="5" class="text-right" style="color:#0f172a; white-space: nowrap;"><?php echo htmlspecialchars(t('products.grand_total', 'ລວມທັງໝົດ:')); ?></td>
              <td class="text-right" id="totalBprice" style="color:#64748b; white-space: nowrap;"><?php echo displayMoney($pdo, $sumBprice); ?></td>
              <td class="text-right" id="totalPrice" style="color:#16a34a; white-space: nowrap;"><?php echo displayMoney($pdo, $sumPrice); ?></td>
              <?php if ($isAdmin): ?><td></td><?php endif; ?>
              <td></td>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <!-- Card Footer: Blue Circular Pagination Buttons (ຊິດຂວາທາງລຸ່ມ) -->
    <div class="card-footer bg-white border-top d-flex align-items-center justify-content-end py-3 px-3 px-md-4" style="border-radius: 0 0 14px 14px;">
      <nav aria-label="Product Pagination">
        <ul class="pagination pagination-circle mb-0 justify-content-end" id="productPagination">
          <!-- Dynamic circular page buttons -->
        </ul>
      </nav>
    </div>

  </div>
</div>

