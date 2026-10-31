<!-- ============================================================
     print_barcode_controls.php - ແຜງຄວບຄຸມການຄົ້ນຫາ, Filter ແລະ ຕັ້ງຄ່າສະຕິກເກີ
     ============================================================ -->
<div class="barcode-page-card p-3 mb-4">
  <div class="row align-items-center gy-2">
    <!-- ຊ່ອງຄົ້ນຫາຊື່ສິນຄ້າ ຫຼື ບາໂຄ້ດ -->
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

    <!-- ຕົວເລືອກ Filter ປະເພດສິນຄ້າ -->
    <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
      <select id="pageCategoryFilter" class="form-control" style="border-radius: 8px; font-size: 0.9rem;" onchange="renderPageBarcodeBlocks()">
        <option value=""><?php echo htmlspecialchars(t('print_barcode.category_all_option', '-- ເລືອກປະເພດສິນຄ້າທັງໝົດ --')); ?></option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- ປຸ່ມເລືອກທັງໝົດ/ຍົກເລີກ ແລະ ປຸ່ມຕັ້ງຈຳນວນດວງດ່ວນ -->
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

  <!-- ຕົວເລືອກສະແດງຜົນ ແລະ ຂະໜາດສະຕິກເກີ / ຂະໜາດບາໂຄ້ດ -->
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
      <div class="custom-control custom-checkbox custom-control-inline mb-0">
        <input type="checkbox" class="custom-control-input" id="opt_show_code" checked onchange="renderPageBarcodeBlocks()">
        <label class="custom-control-label font-weight-bold text-dark" for="opt_show_code" style="font-size: 0.85rem; cursor: pointer;">
          <?php echo htmlspecialchars(t('print_barcode.opt_show_code', 'ເລກລະຫັດໃຕ້ບາໂຄ້ດ')); ?>
        </label>
      </div>
    </div>

    <!-- ຕັ້ງຄ່າຂະໜາດບາໂຄ້ດ ແລະ ຄວາມສູງດວງຈິງ -->
    <div class="d-flex flex-wrap align-items-center" style="gap: 16px;">
      <small class="font-weight-bold text-secondary"><i class="fas fa-ruler-combined mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.barcode_size_label', 'ຂະໜາດບາໂຄ້ດ:')); ?></small>
      <div class="d-flex align-items-center" style="gap: 4px;">
        <label for="barcode_width_mm" class="mb-0 font-weight-bold text-dark" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('print_barcode.barcode_width', 'ກວ້າງ')); ?></label>
        <input type="number" id="barcode_width_mm" class="form-control form-control-sm" style="width: 70px; border-radius: 6px;" value="36" min="10" max="100" step="0.5" oninput="renderPageBarcodeBlocks()" onchange="renderPageBarcodeBlocks()">
        <span class="text-muted" style="font-size: 0.78rem;">mm</span>
      </div>
      <div class="d-flex align-items-center" style="gap: 4px;">
        <label for="barcode_height_mm" class="mb-0 font-weight-bold text-dark" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('print_barcode.barcode_height', 'ສູງ')); ?></label>
        <input type="number" id="barcode_height_mm" class="form-control form-control-sm" style="width: 70px; border-radius: 6px;" value="10" min="3" max="40" step="0.5" oninput="renderPageBarcodeBlocks()" onchange="renderPageBarcodeBlocks()">
        <span class="text-muted" style="font-size: 0.78rem;">mm</span>
      </div>
      <div class="d-flex align-items-center" style="gap: 4px;">
        <label for="label_horizontal_offset_mm" class="mb-0 font-weight-bold text-dark" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('print_barcode.horizontal_offset', 'ຍັບຊ້າຍ/ຂວາ')); ?></label>
        <input type="number" id="label_horizontal_offset_mm" class="form-control form-control-sm" style="width: 70px; border-radius: 6px;" value="0" min="-15" max="15" step="0.5" oninput="renderPageBarcodeBlocks()" onchange="renderPageBarcodeBlocks()">
        <span class="text-muted" style="font-size: 0.78rem;">mm</span>
      </div>
      <div class="d-flex align-items-center" style="gap: 4px;">
        <label for="label_vertical_offset_mm" class="mb-0 font-weight-bold text-dark" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('print_barcode.vertical_offset', 'ຍັບຂຶ້ນ/ລົງ')); ?></label>
        <input type="number" id="label_vertical_offset_mm" class="form-control form-control-sm" style="width: 70px; border-radius: 6px;" value="0" min="-15" max="15" step="0.5" oninput="renderPageBarcodeBlocks()" onchange="renderPageBarcodeBlocks()">
        <span class="text-muted" style="font-size: 0.78rem;">mm</span>
      </div>
      <div class="d-flex align-items-center" style="gap: 4px;" title="<?php echo htmlspecialchars(t('print_barcode.label_pitch_hint', 'ວັດຈາກຂອບເທິງຂອງດວງໜຶ່ງ ຫາ ຂອບເທິງຂອງດວງຖັດໄປ (ສະຕິກເກີ + ຮ່ອງ) — ແກ້ອາການພິມຫຼາຍດວງແລ້ວເລື່ອນສະສົມ')); ?>">
        <label for="label_pitch_mm" class="mb-0 font-weight-bold text-dark" style="font-size: 0.82rem;"><?php echo htmlspecialchars(t('print_barcode.label_pitch', 'ໄລຍະຕໍ່ດວງ')); ?></label>
        <input type="number" id="label_pitch_mm" class="form-control form-control-sm" style="width: 80px; border-radius: 6px;" value="0" min="0" max="120" step="0.1" placeholder="<?php echo htmlspecialchars(t('print_barcode.auto', 'ອັດຕະໂນມັດ')); ?>">
        <span class="text-muted" style="font-size: 0.78rem;">mm</span>
        <i class="fas fa-question-circle text-info" style="font-size: 0.75rem; cursor: help;"></i>
      </div>
    </div>

    <div class="d-flex align-items-center" style="gap: 8px;">
      <small class="font-weight-bold text-secondary"><i class="fas fa-expand mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.sticker_size_label', 'ຂະໜາດສະຕິກເກີ:')); ?></small>
      <select id="print_size" class="form-control form-control-sm font-weight-bold text-dark" style="border-radius: 6px; width: 260px;" onchange="renderPageBarcodeBlocks()">
        <option value="40x30_vert" selected><?php echo htmlspecialchars(t('print_barcode.size_40x30_vert', '40mm x 30mm (ລວງຕັ້ງ - ດວງດ່ຽວ)')); ?></option>
        <option value="40x30_horiz"><?php echo htmlspecialchars(t('print_barcode.size_40x30_horiz', '40mm x 30mm (ລວງນອນ - ດວງດ່ຽວ)')); ?></option>
        <option value="105x25_3up"><?php echo htmlspecialchars(t('print_barcode.size_105x25_3up', '105mm x 25mm (3 ຕາລວງນອນ)')); ?></option>
        <option value="50x30"><?php echo htmlspecialchars(t('print_barcode.size_50x30', '50mm x 30mm (ມ້ວນກາງ)')); ?></option>
        <option value="30x20"><?php echo htmlspecialchars(t('print_barcode.size_30x20', '30mm x 20mm (ມ້ວນນ້ອຍ)')); ?></option>
        <option value="50x40"><?php echo htmlspecialchars(t('print_barcode.size_50x40', '50mm x 40mm (ມ້ວນໃຫຍ່)')); ?></option>
        <option value="a4"><?php echo htmlspecialchars(t('print_barcode.size_a4', 'ເຈ້ຍ A4 (ຕາຕະລາງ)')); ?></option>
      </select>
    </div>

  </div>
</div>

<!-- ບ່ອນສະແດງລາຍການສິນຄ້າ -->
<div class="row" id="pageBarcodeBlocksContainer"></div>

<!-- Hidden Iframe ສຳລັບສັ່ງພິມ -->
<iframe id="pageBarcodePrintIframe" style="display:none;"></iframe>