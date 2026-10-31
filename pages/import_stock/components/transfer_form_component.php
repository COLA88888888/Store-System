<?php
// Component: Stock Transfer Form Layout
?>
<form id="directTransferForm" action="" method="POST">
  <input type="hidden" name="action" value="execute_transfer">
  <input type="hidden" id="cart_json_input" name="items_json" value="[]">

  <!-- Top Header Details Row: Code, Date, From Store, To Store -->
  <div class="row bg-light p-3 rounded mb-3 border" style="border-color: #cbd5e1 !important;">
    <!-- 1. Transfer Code -->
    <div class="col-md-3 mb-2 mb-md-0">
      <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
        <?php echo htmlspecialchars(t('stock_transfer.transfer_code_label', 'ເລກທີໃບໂອນສິນຄ້າ')); ?>
      </label>
      <input type="text" id="transfer_code_preview" class="form-control font-weight-bold bg-white text-primary border-primary" value="<?php echo $next_transfer_code; ?>" readonly style="height: 42px; border-radius: 8px; cursor: not-allowed; font-size: 1.05rem;">
    </div>

    <!-- 2. Transfer Date -->
    <div class="col-md-3 mb-2 mb-md-0">
      <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
        <?php echo htmlspecialchars(t('stock_transfer.transfer_date_label', 'ວັນທີໂອນ')); ?> <span class="text-danger">*</span>
      </label>
      <input type="date" name="transfer_date" id="transfer_date" class="form-control font-weight-bold" value="<?php echo date('Y-m-d'); ?>" required style="height: 42px; border-radius: 8px; cursor: pointer;">
    </div>

    <!-- 3. From Store -->
    <div class="col-md-3 mb-2 mb-md-0">
      <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
        <?php echo htmlspecialchars(t('stock_transfer.from_store_label', 'ສາຂາຕົ້ນທາງ')); ?> <span class="text-danger">*</span>
      </label>
      <select name="from_store_id" id="from_store_id" class="form-control font-weight-bold" style="height: 42px; border-radius: 8px;">
        <?php foreach ($stores as $st): ?>
          <?php if (!empty($canAllBranches) || $st['store_id'] == $sourceStoreId): ?>
          <option value="<?php echo $st['store_id']; ?>" <?php echo ($sourceStoreId == $st['store_id']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('stock_transfer.main_warehouse_suffix', '(ສາງຫຼັກ)')) : ''; ?>
          </option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- 4. To Store -->
    <div class="col-md-3">
      <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
        <?php echo htmlspecialchars(t('stock_transfer.to_store_label', 'ສາຂາປາຍທາງ (To)')); ?> <span class="text-danger">*</span>
      </label>
      <select name="to_store_id" id="to_store_id" class="form-control font-weight-bold border-success" style="height: 42px; border-radius: 8px; background: #ecfdf5; color: #047857;" required>
        <option value=""><?php echo htmlspecialchars(t('stock_transfer.select_target_store', '-- ເລືອກສາຂາປາຍທາງ --')); ?></option>
        <?php foreach ($stores as $st): ?>
          <?php if ($st['store_id'] != $sourceStoreId): ?>
            <option value="<?php echo $st['store_id']; ?>" <?php echo ($preSelectedTarget == $st['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('stock_transfer.main_warehouse_suffix', '(ສາງຫຼັກ)')) : ''; ?>
            </option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- Barcode Scanning & Item Entry Row -->
  <div class="row align-items-end mb-3">
    <!-- Barcode / Search Box -->
    <div class="col-md-5 mb-3 mb-md-0">
      <label class="font-weight-bold text-dark mb-1">
        <?php echo htmlspecialchars(t('stock_transfer.scan_barcode_label', 'ສະແກນບາໂຄ້ດ / ລະຫັດສິນຄ້າຕົ້ນທາງ')); ?> <span class="text-danger">*</span>
      </label>
      <div class="input-group">
        <input type="text" id="direct_barcode_input" class="form-control" placeholder="<?php echo htmlspecialchars(t('stock_transfer.scan_barcode_placeholder', 'ສະແກນບາໂຄ້ດ ຫຼື ປ້ອນລະຫັດ...')); ?>" value="<?php echo htmlspecialchars($preSearch); ?>" autocomplete="off" style="height: 42px; border-radius: 8px 0 0 8px; border: 1.5px solid #007bff;" oninput="onDirectBarcodeChange()" onkeydown="onDirectBarcodeKeyDown(event)">
        <div class="input-group-append">
          <button type="button" class="btn btn-outline-primary font-weight-bold" data-toggle="modal" data-target="#productSelectModal" title="<?php echo htmlspecialchars(t('stock_transfer.open_select_product_modal', 'ເປີດປັອບອັບເລືອກສິນຄ້າ')); ?>" style="height: 42px; border: 1.5px solid #007bff !important; border-radius: 0 8px 8px 0 !important; background-color: #eff6ff; color: #0056b3;">
            <i class="fas fa-search-plus mr-1"></i> <?php echo htmlspecialchars(t('stock_transfer.select_product_button', 'ເລືອກສິນຄ້າ')); ?>
          </button>
        </div>
      </div>
    </div>

    <!-- Quantity Input -->
    <div class="col-6 col-md-3 mb-3 mb-md-0">
      <label class="font-weight-bold text-dark mb-1">
        <?php echo htmlspecialchars(t('stock_transfer.transfer_qty_label', 'ຈຳນວນໂອນ')); ?> <span class="text-danger">*</span>
      </label>
      <input type="number" id="direct_qty" class="form-control font-weight-bold border-primary text-center" min="1" value="1" style="height: 42px; border-radius: 8px; font-size: 1.1rem;" onkeydown="onQtyKeyDown(event)">
    </div>

    <!-- Unit Select -->
    <div class="col-6 col-md-2 mb-3 mb-md-0">
      <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('stock_transfer.unit_label', 'ຫົວໜ່ວຍ')); ?></label>
      <select id="direct_unit_key" class="form-control" style="height: 42px; border-radius: 8px;">
        <option value="base"><?php echo htmlspecialchars(t('stock_transfer.unit_label', 'ຫົວໜ່ວຍ')); ?></option>
      </select>
    </div>

    <!-- Add Item Button -->
    <div class="col-md-2">
      <button type="button" class="btn btn-primary font-weight-bold btn-block shadow-sm" style="height: 42px; border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886);" onclick="addCurrentItemToCart()">
        <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('stock_transfer.add_item_button', 'ເພີ່ມລາຍການ')); ?>
      </button>
    </div>
  </div>

  <!-- Active Product Selected Banner -->
  <div id="direct_matched_banner" class="p-2.5 px-3 rounded border d-none align-items-center justify-content-between mb-3" style="border-color: #93c5fd !important; background-color: #eff6ff !important;">
    <div>
      <span class="font-weight-bold text-dark d-block" id="direct_matched_name" style="font-size: 1rem;">-</span>
      <small class="text-muted">
         <?php echo htmlspecialchars(t('stock_transfer.barcode_label', 'ບາໂຄ້ດ:')); ?> <span id="direct_matched_barcode" class="font-weight-bold text-primary mr-2">-</span>
         <?php echo htmlspecialchars(t('stock_transfer.source_stock_label', 'ສະຕັອກຕົ້ນທາງ:')); ?> <span id="direct_matched_stock" class="font-weight-bold text-success">0</span>
      </small>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDirectSelection()"><i class="fas fa-times"></i></button>
  </div>

  <!-- LIVE TRANSFER ITEMS TABLE -->
  <div class="border rounded mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden; background-color: #ffffff;">
    <!-- Only the table scrolls sideways on phones; header, pagination and submit bar stay put -->
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="font-weight-bold bg-light">
        <tr>
          <th class="text-center" style="width: 50px;"><?php echo htmlspecialchars(t('stock_transfer.col_index', 'ລຳດັບ')); ?></th>
          <th><?php echo htmlspecialchars(t('stock_transfer.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
          <th class="text-center" style="width: 140px;"><?php echo htmlspecialchars(t('stock_transfer.col_source_stock', 'ສະຕັອກຕົ້ນທາງ')); ?></th>
          <th class="text-center" style="width: 180px;"><?php echo htmlspecialchars(t('stock_transfer.col_transfer_qty', 'ຈຳນວນໂອນ')); ?></th>
          <th class="text-center" style="width: 100px;"><?php echo htmlspecialchars(t('stock_transfer.col_actions', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody id="cart_table_body">
        <tr id="empty_cart_row">
          <td colspan="5" class="text-center text-muted py-5">
            <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
            <span class="font-weight-bold"><?php echo htmlspecialchars(t('stock_transfer.empty_cart_title', 'ຍັງບໍ່ມີລາຍການສິນຄ້າໃນໃບໂອນ')); ?></span><br>
            <small><?php echo htmlspecialchars(t('stock_transfer.empty_cart_hint', 'ກະລຸນາສະແກນບາໂຄ້ດ ຫຼື ກົດປຸ່ມ "ເລືອກສິນຄ້າ" ເພື່ອເພີ່ມສິນຄ້າທີ່ຈະໂອນ')); ?></small>
          </td>
        </tr>
      </tbody>
    </table>
    </div>

    <!-- Table Pagination Bar: Displays when > 10 items -->
    <div id="cart_pagination_row" class="px-3 py-2 bg-light border-top d-none align-items-center justify-content-between" style="border-color: #cbd5e1 !important;">
      <div class="text-muted font-weight-bold" style="font-size: 0.88rem;">
        <span id="cart_page_start" class="text-dark">1</span> - <span id="cart_page_end" class="text-dark">10</span> <span id="cart_page_total" class="text-primary">0</span> <?php echo htmlspecialchars(t('stock_transfer.items_unit', 'ລາຍການ')); ?>
      </div>
      <div>
        <ul class="pagination pagination-circle mb-0 justify-content-end" id="cartTablePagination">
        </ul>
      </div>
    </div>

    <!-- Notes & Submit Bar -->
    <div class="card-footer bg-light border-top p-3 d-flex flex-wrap align-items-center justify-content-between" style="border-color: #e2e8f0 !important; gap: 10px;">
      <div style="flex: 1 1 300px;">
        <input type="text" name="notes" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(t('stock_transfer.notes_placeholder', 'ໝາຍເຫດການໂອນສິນຄ້າ (ຖ້າມີ)...')); ?>" style="border-radius: 6px; height: 38px;">
      </div>
      <div class="ml-auto">
        <button type="button" class="btn btn-success font-weight-bold px-4 shadow-sm" style="height: 38px; border-radius: 6px; background: linear-gradient(135deg, #10b981, #059669); border: none;" onclick="submitDirectTransferBill()">
          <i class="fas fa-paper-plane mr-1.5" style="font-size: 1rem;"></i> <?php echo htmlspecialchars(t('stock_transfer.confirm_transfer_button', 'ຢືນຢັນການໂອນສິນຄ້າ')); ?>
        </button>
      </div>
    </div>
  </div>

</form>
