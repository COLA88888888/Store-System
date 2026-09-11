<?php
// Component: Modal Select Product Popup (Modern POS Catalog with Page Size & Pagination)
?>
<!-- Modal: Select Product Popup -->
<div class="modal fade" id="productSelectModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden; max-height: 92vh; display: flex; flex-direction: column;">

      <!-- Header with Gradient Background (always pinned, never scrolls) -->
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; flex-shrink: 0;">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.1rem;">
          <?php echo htmlspecialchars(t('stock_transfer.psm_title', 'ເລືອກສິນຄ້າຈາກຄັງ')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4 d-flex flex-column" style="background-color: #f8fafc; overflow: hidden; -webkit-overflow-scrolling: touch; flex: 1 1 auto; min-height: 0;">
        
        <!-- Filter Bar: Page Size + Category Select + Search Input -->
        <div class="row align-items-center mb-3 flex-shrink-0">
          
          <!-- 1. Page Size Selector (ບັອກໂຊລາຍການ ຢູ່ໜ້າບັອກເລືອກປະເພດສິນຄ້າ) -->
          <div class="col-md-2 col-sm-3 mb-2 mb-md-0">
            <!-- <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.88rem;">
              <i class="fas fa-list-ol text-primary mr-1"></i> ສະແດງ
            </label> -->
            <select id="modal_page_size_select" class="form-control font-weight-bold shadow-sm" onchange="changeModalPageSize(this.value)" style="height: 42px; border-radius: 8px; border-color: #cbd5e1;">
              <option value="10" selected>10</option>
              <option value="20">20</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>

          <!-- 2. Category Filter Dropdown (ບັອກເລືອກປະເພດສິນຄ້າ) -->
          <div class="col-md-4 col-sm-4 mb-2 mb-md-0">
            <!-- <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.88rem;">
              <i class="fas fa-filter text-primary mr-1"></i> ປະເພດສິນຄ້າ
            </label> -->
            <select id="modal_category_filter" class="form-control font-weight-bold shadow-sm" onchange="filterModalProducts()" style="height: 42px; border-radius: 8px; border-color: #cbd5e1;">
              <option value=""><?php echo htmlspecialchars(t('stock_transfer.psm_all_categories', '-- ທັງໝົດປະເພດສິນຄ້າ --')); ?></option>
              <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <!-- 3. Search Input Box (ບັອກຄົ້ນຫາ) -->
          <div class="col-md-6 col-sm-5">
            <!-- <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.88rem;">
              <i class="fas fa-search text-primary mr-1"></i> ຄົ້ນຫາສິນຄ້າ
            </label> -->
            <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
              </div>
              <input type="text" id="modal_product_search" class="form-control border-left-0 pl-0" placeholder="<?php echo htmlspecialchars(t('stock_transfer.psm_search_placeholder', 'ຄົ້ນຫາ ຊື່ສິນຄ້າ, ບາໂຄ້ດ, ລະຫັດ...')); ?>" autocomplete="off" onkeyup="filterModalProducts()" style="height: 42px; font-size: 0.95rem;">
            </div>
          </div>
        </div>

        <!-- Batch Action Bar: Displayed when items are checked -->
        <div id="modal_batch_actions_bar" class="alert alert-info py-2 px-3 mb-3 d-none align-items-center justify-content-between border-0 shadow-sm" style="border-radius: 10px; background-color: #eff6ff; color: #1e40af; flex-shrink: 0;">
          <div class="d-flex align-items-center">
            <i class="fas fa-check-circle mr-2 text-primary fa-lg"></i>
            <span class="font-weight-bold" style="font-size: 0.92rem;">
              <?php echo htmlspecialchars(t('stock_transfer.psm_selected_prefix', 'ເລືອກແລ້ວ')); ?> <span id="modal_selected_count" class="badge badge-primary font-weight-bold mx-1" style="font-size: 0.88rem; padding: 3px 8px;">0</span> <?php echo htmlspecialchars(t('stock_transfer.psm_selected_suffix', 'ລາຍການ')); ?>
            </span>
          </div>
          <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3 py-1.5 shadow-sm" onclick="addSelectedModalProductsToCart()" style="border-radius: 8px; font-size: 0.85rem;">
            <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('stock_transfer.psm_add_items_btn', 'ເພີ່ມລາຍການ')); ?>
          </button>
        </div>

        <!-- Products List Table inside Modal -->
        <div class="table-responsive bg-white rounded border shadow-sm mb-3" style="flex: 1 1 auto; min-height: 220px; overflow-y: auto; overscroll-behavior-y: contain; -webkit-overflow-scrolling: touch; border-radius: 12px !important;">
          <table class="table table-hover mb-0 align-middle text-nowrap">
            <thead class="text-dark font-weight-bold" style="position: sticky; top: 0; z-index: 10; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
              <tr>
                <th class="text-center" style="width: 70px;"><?php echo htmlspecialchars(t('stock_transfer.psm_col_code', 'ລະຫັດ')); ?></th>
                <th><?php echo htmlspecialchars(t('stock_transfer.psm_col_name', 'ຊື່ສິນຄ້າ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('stock_transfer.psm_col_barcode', 'ບາໂຄ້ດ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('stock_transfer.psm_col_stock', 'ສະຕັອກ')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('stock_transfer.psm_col_buy_price', 'ລາຄາຊື້')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('stock_transfer.psm_col_sell_price', 'ລາຄາຂາຍ')); ?></th>
                <th class="text-center" style="width: 120px;">
                  <div class="custom-control custom-checkbox d-inline-flex align-items-center justify-content-center" title="<?php echo htmlspecialchars(t('stock_transfer.psm_select_all_title', 'ເລືອກທັງໝົດ')); ?>">
                    <input type="checkbox" class="custom-control-input" id="selectAllModalProducts" onchange="toggleSelectAllModalProducts(this.checked)">
                    <label class="custom-control-label font-weight-bold text-dark" for="selectAllModalProducts" style="cursor: pointer; font-size: 0.85rem; user-select: none;">
                      <?php echo htmlspecialchars(t('stock_transfer.psm_select', 'ເລືອກ')); ?>
                    </label>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody id="modal_product_list">
              <?php if (empty($products)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-5">
                    <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
                    <span class="font-weight-bold"><?php echo htmlspecialchars(t('stock_transfer.psm_empty', 'ບໍ່ມີຂໍ້ມູນສິນຄ້າໃນລະບົບ')); ?></span>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($products as $p): ?>
                  <?php 
                    $extraBarcodes = [];
                    if (!empty($product_units_map[$p['product_id']])) {
                        foreach ($product_units_map[$p['product_id']] as $u) {
                            if (!empty($u['barcode'])) $extraBarcodes[] = $u['barcode'];
                        }
                    }
                    $allBarcodesStr = strtolower(implode(',', array_filter(array_merge([$p['barcode'] ?? ''], $extraBarcodes))));
                    $searchData = strtolower($p['product_name'] . ' ' . $p['barcode'] . ' ' . $allBarcodesStr . ' ' . $p['product_id']);
                    $catId = $p['category_id'] ?? '';
                    $stockQty = intval($p['qty']);
                    $unitName = $p['unit'] ?: t('stock_transfer.default_unit', 'ອັນ');
                  ?>
                  <tr class="modal-product-row" data-search="<?php echo htmlspecialchars($searchData); ?>" data-category="<?php echo htmlspecialchars($catId); ?>" onclick="toggleRowCheckbox(this, event)" style="cursor: pointer;">
                    
                    <!-- 1. ລະຫັດ (Product ID) -->
                    <td class="text-center align-middle font-weight-bold text-secondary">
                      <?php echo str_pad($p['product_id'], 4, '0', STR_PAD_LEFT); ?>
                    </td>
                    
                    <!-- 2. ຊື່ສິນຄ້າ (ຫົວໜ່ວຍຢູ່ນຳ) -->
                    <td class="align-middle">
                      <span class="font-weight-bold text-dark d-block" style="font-size: 0.98rem;">
                        <?php echo htmlspecialchars($p['product_name']); ?>
                        <small class="text-primary font-weight-bold"> (<?php echo htmlspecialchars($unitName); ?>)</small>
                      </span>
                      <?php if (!empty($p['category_name'])): ?>
                        <small class="text-muted"><i class="fas fa-folder mr-1"></i> <?php echo htmlspecialchars($p['category_name']); ?></small>
                      <?php endif; ?>
                    </td>

                    <!-- 3. ບາໂຄ້ດ -->
                    <td class="text-center align-middle font-weight-bold">
                      <?php if (!empty($p['barcode'])): ?>
                        <span class="badge badge-light text-primary border px-2 py-1" style="border-color: #93c5fd !important; background-color: #eff6ff;">
                          <i class="fas fa-barcode mr-1"></i> <?php echo htmlspecialchars($p['barcode']); ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- 4. ສະຕັອກ -->
                    <td class="text-center align-middle font-weight-bold">
                      <?php if ($stockQty > 0): ?>
                        <span class="badge badge-success px-2 py-1" style="font-size: 0.85rem; border-radius: 6px;">
                          <?php echo number_format($stockQty); ?> <?php echo htmlspecialchars($unitName); ?>
                        </span>
                      <?php else: ?>
                        <span class="badge badge-secondary px-2 py-1" style="font-size: 0.85rem; border-radius: 6px;">
                          0 <?php echo htmlspecialchars($unitName); ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- 5. ລາຄາຊື້ (₭) -->
                    <td class="text-right align-middle font-weight-bold text-danger" style="font-size: 0.98rem;">
                      <?php echo displayMoney($pdo, $p['bprice']); ?>
                    </td>

                    <!-- 6. ລາຄາຂາຍ (₭) -->
                    <td class="text-right align-middle font-weight-bold text-success" style="font-size: 0.98rem;">
                      <?php echo displayMoney($pdo, $p['price']); ?>
                    </td>

                    <!-- 7. ປຸ່ມເລືອກ + ບັອກຕິກ (Checkbox & Green Check Button) -->
                    <td class="text-center align-middle">
                      <div class="d-flex align-items-center justify-content-center" style="gap: 8px;">
                        <div class="custom-control custom-checkbox" style="padding-left: 1.5rem;">
                          <input type="checkbox" class="custom-control-input modal-product-checkbox" id="chk_modal_prod_<?php echo $p['product_id']; ?>" data-product-id="<?php echo $p['product_id']; ?>" onclick="event.stopPropagation(); checkSingleModalProduct()">
                          <label class="custom-control-label" for="chk_modal_prod_<?php echo $p['product_id']; ?>" style="cursor: pointer;"></label>
                        </div>
                        <button type="button" class="btn btn-modal-select-green" style="background-color: #10b981 !important; color: #ffffff !important; border: none !important; width: 34px !important; height: 34px !important; border-radius: 6px !important; transform: none !important;" onclick="event.stopPropagation(); selectProductFromModal(<?php echo $p['product_id']; ?>)" title="<?php echo htmlspecialchars(t('stock_transfer.psm_select_one_title', 'ເລືອກສິນຄ້ານີ້ດຽວ')); ?>">
                          <i class="fas fa-check font-weight-bold" style="color: #ffffff !important; font-size: 0.9rem !important;"></i>
                        </button>
                      </div>
                    </td>

                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>

      <!-- Modal Footer: Page Info & Circular Pagination (always pinned, never scrolls) -->
      <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between py-3 px-4 border-top bg-white" style="border-color: #cbd5e1 !important; flex-shrink: 0;">
        <div class="d-flex justify-content-end ml-sm-auto">
          <ul class="pagination pagination-circle mb-0 justify-content-end" id="modalProductPagination">
          </ul>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  window.modalSelectAllCatalog = false;

  function toggleSelectAllModalProducts(isChecked) {
    window.modalSelectAllCatalog = isChecked;
    $('.modal-product-row:visible .modal-product-checkbox').prop('checked', isChecked);
    updateModalSelectedCount();
  }

  function checkSingleModalProduct() {
    var visibleBoxes = $('.modal-product-row:visible .modal-product-checkbox');
    var checkedBoxes = $('.modal-product-row:visible .modal-product-checkbox:checked');
    if (checkedBoxes.length < visibleBoxes.length) {
      window.modalSelectAllCatalog = false;
    }
    $('#selectAllModalProducts').prop('checked', visibleBoxes.length > 0 && visibleBoxes.length === checkedBoxes.length);
    updateModalSelectedCount();
  }

  function toggleRowCheckbox(rowEl, evt) {
    if ($(evt.target).closest('.custom-checkbox, button').length) return;
    var chk = $(rowEl).find('.modal-product-checkbox');
    if (chk.length > 0) {
      chk.prop('checked', !chk.prop('checked'));
      checkSingleModalProduct();
    }
  }

  function updateModalSelectedCount() {
    var count = 0;
    if (window.modalSelectAllCatalog && typeof PRODUCTS_LIST !== 'undefined') {
      count = PRODUCTS_LIST.length;
    } else {
      count = $('.modal-product-checkbox:checked').length;
    }

    $('#modal_selected_count').text(count);
    if (count > 0) {
      $('#modal_batch_actions_bar').removeClass('d-none').addClass('d-flex');
    } else {
      $('#modal_batch_actions_bar').addClass('d-none').removeClass('d-flex');
    }
  }
</script>
