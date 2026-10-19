<?php
/*
 * ສ່ວນ: ແທັບ: ເພີ່ມຫຼາຍລາຍການ
 * ຟອມເພີ່ມຫຼາຍລາຍການ: ນຳເຂົ້າ Excel, ຜູ້ສະໜອງບັອກດຽວສຳລັບທັງຊຸດ, ບໍ່ຕັດສະຕັອກ, ແລະ ກ່ອງແຖວສິນຄ້າ
 * (ສ້າງໂດຍ JS).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/components/form_add_product.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>      <!-- ============================ PANE 2: BULK ADD ============================ -->
      <style>
        /* ຮູບພາບລາຍແຖວ: ກ່ອງສີ່ຫຼ່ຽມນ້ອຍ ກົດເພື່ອເລືອກຮູບ */
        .bulk-img-box { position: relative; width: 52px; height: 52px; border: 2px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;
          display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: visible; transition: border-color .15s; }
        .bulk-img-box:hover { border-color: #2563eb; }
        .bulk-img-box.has-img { border-style: solid; border-color: #16a34a; background: #fff; }
        .bulk-img-ico { color: #94a3b8; font-size: 1.1rem; }
        .bulk-img-preview { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; }
        .bulk-img-clear { position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; padding: 0; border: none; border-radius: 50%;
          background: #dc2626; color: #fff; font-size: 14px; line-height: 20px; text-align: center; cursor: pointer; z-index: 2; }
      </style>
      <div class="tab-pane fade" id="pane-add-bulk" role="tabpanel">
      <form id="bulkAddProductForm" novalidate action="" method="POST" onsubmit="return submitBulkAddProduct(event)">
        <input type="hidden" name="action" value="add_products_bulk">

        <div class="modal-body p-4" style="max-height: 78vh; overflow-y: auto;">
          <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 8px;">
            <small class="text-muted font-weight-bold" style="font-size: 0.8rem;">
              <i class="fas fa-info-circle mr-1"></i>
              <?php echo htmlspecialchars(t('products.bulk_hint', 'ປ້ອນໄດ້ຫຼາຍລາຍການພ້ອມກັນ — ບັອກທີ່ວ່າງຈະຖືກຂ້າມ. ຈຳນວນສະຕັອກເລີ່ມຕົ້ນ = 0')); ?>
            </small>
            <span class="badge badge-primary" style="font-size: 0.8rem;" id="bulkRowCounter">0</span>
          </div>

          <!-- ນຳເຂົ້າຈາກ Excel: ອ່ານໄຟລ໌ຢູ່ໃນ browser ແລ້ວຕື່ມແຖວລຸ່ມນີ້ໃຫ້ — ກວດ/ແກ້ໄຂໄດ້ກ່ອນກົດບັນທຶກ -->
          <div class="d-flex align-items-center flex-wrap mb-3 p-2" style="gap: 8px; background: #f0fdf4; border: 1px dashed #86efac; border-radius: 8px;">
            <button type="button" class="btn btn-sm btn-success font-weight-bold" style="border-radius: 6px;" onclick="$('#bulk_excel_file').val('').click()">
              <i class="fas fa-file-excel mr-1"></i> ນຳເຂົ້າຈາກ Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" style="border-radius: 6px;" onclick="bulkDownloadExcelTemplate()">
              <i class="fas fa-download mr-1"></i> ດາວໂຫຼດແບບຟອມ
            </button>
            <input type="file" id="bulk_excel_file" accept=".xlsx,.xls,.csv" class="d-none" onchange="bulkImportExcel(this)">
            <small class="text-muted" style="font-size: 0.78rem;">ຖັນ: ປະເພດ, ຊື່ສິນຄ້າ, ບາໂຄ້ດ, ຫົວໜ່ວຍ, ລາຄາຊື້, ລາຄາຂາຍ, ຜູ້ສະໜອງ</small>
          </div>

          <!-- ຕັ້ງຄ່າທັງຊຸດ: ຜູ້ສະໜອງ (ບັອກດຽວ) + ບໍ່ຕັດສະຕັອກ -->
          <div class="row mb-3" style="row-gap: 10px;">
            <div class="col-md-6">
              <label class="font-weight-bold text-dark mb-1"><i class="fas fa-truck text-primary mr-1"></i><?php echo htmlspecialchars(t('products.bulk_supplier_all', 'ຜູ້ສະໜອງ (ໃຊ້ກັບທຸກລາຍການ)')); ?></label>
              <select name="bulk_supplier_id" id="bulk_supplier_id" class="form-control" style="height: 40px; border-radius: 6px;">
                <option value=""><?php echo htmlspecialchars(t('products.select_supplier_placeholder', '-- ບໍ່ລະບຸ / ເລືອກຜູ້ສະໜອງ --')); ?></option>
                <?php foreach (($suppliers ?? []) as $sp): ?>
                  <option value="<?php echo (int)$sp['supplier_id']; ?>"><?php echo htmlspecialchars($sp['supplier_name']); ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted"><?php echo htmlspecialchars(t('products.bulk_supplier_hint', 'ສິນຄ້າຫຼາຍລາຍການມາຈາກຜູ້ສະໜອງດຽວ — ເລືອກບ່ອນນີ້ບ່ອນດຽວ ທຸກລາຍການຈະເປັນຂອງຜູ້ສະໜອງນີ້')); ?></small>
            </div>
            <div class="col-md-6">
              <div class="custom-control custom-switch mt-md-4 pt-md-1">
                <input type="checkbox" class="custom-control-input" id="bulk_no_cut_qty" name="bulk_no_cut_qty" value="1">
                <label class="custom-control-label font-weight-bold text-dark" for="bulk_no_cut_qty">
                  <?php echo htmlspecialchars(t('products.bulk_no_cut_qty', 'ບໍ່ຕັດສະຕັອກ ສຳລັບທຸກລາຍການໃນຊຸດນີ້')); ?>
                </label>
                <br><small class="text-muted"><?php echo htmlspecialchars(t('products.bulk_no_cut_qty_hint', 'ໃຊ້ສຳລັບບໍລິການ / ສິນຄ້າສັ່ງເຮັດ — ຂາຍໄດ້ໂດຍບໍ່ຕັດຈຳນວນຄັງ. ຕັ້ງທີ່ນີ້ຄັ້ງດຽວ ແທນທີ່ຈະໄປແກ້ໄຂເອົາທີລະລາຍການພາຍຫຼັງ')); ?></small>
              </div>
            </div>
          </div>

          <div id="bulk_rows_container"></div>

          <div class="btn-group btn-group-sm mt-2" role="group">
            <button type="button" class="btn btn-outline-primary font-weight-bold" style="border-radius: 6px 0 0 6px;" onclick="bulkAddRows(1)">
              <i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('products.bulk_add_row', 'ເພີ່ມແຖວ')); ?>
            </button>
            <button type="button" class="btn btn-outline-primary font-weight-bold px-3" onclick="bulkAddRows(5)">+5</button>
            <button type="button" class="btn btn-outline-primary font-weight-bold px-3" style="border-radius: 0 6px 6px 0;" onclick="bulkAddRows(10)">+10</button>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px; cursor: pointer;" data-dismiss="modal"><?php echo htmlspecialchars(t('products.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; cursor: pointer; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('products.bulk_btn_save', 'ບັນທຶກທັງໝົດ')); ?>
          </button>
        </div>
      </form>
      </div><!-- /pane-add-bulk -->

      </div><!-- /tab-content -->
    </div>
  </div>
</div>
