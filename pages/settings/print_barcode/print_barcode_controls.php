<!-- ============================================================
     print_barcode_controls.php - ແຜງຄວບຄຸມການຄົ້ນຫາ, Filter ແລະ ຕັ້ງຄ່າສະຕິກເກີ
     ============================================================ -->
<div class="barcode-page-card p-3 mb-4">
  <div class="row align-items-center gy-2">
    <!-- 1. ຊ່ອງຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ບາໂຄ້ດ -->
    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
      <div class="input-group">
        <div class="input-group-prepend">
          <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px;">
            <i class="fas fa-search text-muted"></i>
          </span>
        </div>
        <input type="text" id="pageBarcodeSearch" class="form-control border-left-0"
               placeholder="<?php echo htmlspecialchars(t('print_barcode.search_placeholder', 'ຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ບາໂຄ້ດ...')); ?>"
               style="border-radius: 0 8px 8px 0; font-size: 0.9rem;"
               onkeyup="renderPageBarcodeBlocks()">
      </div>
    </div>

    <!-- 2. ຕົວເລືອກ Filter ປະເພດສິນຄ້າ -->
    <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
      <select id="pageCategoryFilter" class="form-control" style="border-radius: 8px; font-size: 0.9rem;" onchange="renderPageBarcodeBlocks()">
        <option value=""><?php echo htmlspecialchars(t('print_barcode.category_all_option', '-- ເລືອກປະເພດສິນຄ້າທັງໝົດ --')); ?></option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- 3. ປຸ່ມເລືອກທັງໝົດ/ຍົກເລີກ ແລະ ປຸ່ມຕັ້ງຈຳນວນດວງດ່ວນ (Quick Setters) -->
    <div class="col-md-5 col-12 d-flex flex-wrap align-items-center justify-content-md-end" style="gap: 6px;">
      <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" style="border-radius: 6px;" onclick="selectAllPageProducts(true)">
        <i class="fas fa-check-square mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.select_all', 'ເລືອກທັງໝົດ')); ?>
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold" style="border-radius: 6px;" onclick="selectAllPageProducts(false)">
        <i class="far fa-square mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.deselect_all', 'ຍົກເລີກທັງໝົດ')); ?>
      </button>

      <div class="btn-group btn-group-sm">
        <span class="btn btn-sm btn-light border disabled font-weight-bold text-dark px-2" style="font-size: 0.78rem;"><?php echo htmlspecialchars(t('print_barcode.set_copies_label', 'ຕັ້ງດວງ:')); ?></span>
        <button type="button" class="btn btn-sm btn-light border font-weight-bold" onclick="setAllPageCopies(1)">1</button>
        <button type="button" class="btn btn-sm btn-light border font-weight-bold" onclick="setAllPageCopies(2)">2</button>
        <button type="button" class="btn btn-sm btn-light border font-weight-bold" onclick="setAllPageCopies(5)">5</button>
        <button type="button" class="btn btn-sm btn-light border font-weight-bold" onclick="setAllPageCopies(10)">10</button>
      </div>
    </div>
  </div>

  <!-- 4. ຕົວເລືອກສະແດງຜົນ (ຊື່ຮ້ານ, ຊື່ສິນຄ້າ, ລາຄາ) ແລະ ເລືອກຂະໜາດສະຕິກເກີ (40x30, 50x30, A4) -->
  <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 pt-2 border-top" style="gap: 12px;">
    
    <div class="d-flex flex-wrap align-items-center" style="gap: 16px;">
      <small class="font-weight-bold text-secondary"><i class="fas fa-sliders-h mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.display_options_label', 'ຕົວເລືອກສະແດງ:')); ?></small>
      <div class="custom-control custom-checkbox custom-control-inline mb-0">
        <input type="checkbox" class="custom-control-input" id="opt_show_shop" onchange="renderPageBarcodeBlocks()">
        <label class="custom-control-label font-weight-bold text-dark" for="opt_show_shop" style="font-size: 0.85rem; cursor: pointer;">
          <?php echo htmlspecialchars(t('print_barcode.opt_shop_name', 'ຊື່ຮ້ານ')); ?> (<?php echo htmlspecialchars($company_name); ?>)
        </label>
      </div>
      <div class="custom-control custom-checkbox custom-control-inline mb-0">
        <input type="checkbox" class="custom-control-input" id="opt_show_name" checked onchange="renderPageBarcodeBlocks()">
        <label class="custom-control-label font-weight-bold text-dark" for="opt_show_name" style="font-size: 0.85rem; cursor: pointer;">
          <?php echo htmlspecialchars(t('print_barcode.opt_product_name', 'ຊື່ສິນຄ້າ')); ?>
        </label>
      </div>
      <div class="custom-control custom-checkbox custom-control-inline mb-0">
        <input type="checkbox" class="custom-control-input" id="opt_show_price" checked onchange="renderPageBarcodeBlocks()">
        <label class="custom-control-label font-weight-bold text-dark" for="opt_show_price" style="font-size: 0.85rem; cursor: pointer;">
          <?php echo htmlspecialchars(t('print_barcode.opt_sale_price', 'ລາຄາຂາຍ')); ?>
        </label>
      </div>
    </div>

    <div class="d-flex align-items-center" style="gap: 8px;">
      <small class="font-weight-bold text-secondary"><i class="fas fa-expand mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.sticker_size_label', 'ຂະໜາດສະຕິກເກີ:')); ?></small>
      <select id="print_size" class="form-control form-control-sm font-weight-bold text-dark" style="border-radius: 6px; width: 210px;" onchange="renderPageBarcodeBlocks()">
        <option value="3up_105x25" selected><?php echo htmlspecialchars(t('print_barcode.size_3up', '105mm x 25mm (3 ຕາລວງນອນ)')); ?></option>
        <option value="40x30"><?php echo htmlspecialchars(t('print_barcode.size_40x30', '40mm x 30mm (ມ້ວນມາດຕະຖານ)')); ?></option>
        <option value="50x30"><?php echo htmlspecialchars(t('print_barcode.size_50x30', '50mm x 30mm (ມ້ວນກາງ)')); ?></option>
        <option value="a4"><?php echo htmlspecialchars(t('print_barcode.size_a4', 'ເຈ້ຍ A4 (ຕາຕະລາງ)')); ?></option>
      </select>
    </div>

    <div class="d-flex align-items-center" style="gap: 8px;">
      <small class="font-weight-bold text-secondary"><i class="fas fa-arrows-alt-v mr-1"></i> ຄວາມສູງບາໂຄ້ດ:</small>
      <select id="barcode_height" class="form-control form-control-sm font-weight-bold text-dark" style="border-radius: 6px; width: 170px;" onchange="renderPageBarcodeBlocks()">
        <option value="auto" selected>ຄວາມສູງ: ພໍດີ (Auto)</option>
        <option value="tall">ຄວາມສູງ: ສູງ (Tall)</option>
        <option value="extra_tall">ຄວາມສູງ: ສູງຫຼາຍ (Extra)</option>
      </select>
    </div>

    <div class="d-flex align-items-center" id="rotateLabelWrap" style="gap: 6px; display: none;">
      <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="opt_rotate_180">
        <label class="custom-control-label font-weight-bold text-secondary" for="opt_rotate_180" style="font-size: 0.86rem;">
          <?php echo htmlspecialchars(t('print_barcode.opt_rotate_180', 'ໝູນປ້າຍ 180° (ຖ້າພິມອອກມາກັບຫົວ)')); ?>
        </label>
      </div>
    </div>

  </div>
</div>