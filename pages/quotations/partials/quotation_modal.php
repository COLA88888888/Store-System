<!-- Add / Edit Quotation Modal -->
<div class="modal fade" id="quotationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form id="quotationForm">
        <input type="hidden" name="action" id="quote_action" value="add_quotation">
        <input type="hidden" name="quotation_id" id="quote_id">
        <input type="hidden" name="customer_id" id="quote_customer_id">
        <input type="hidden" name="customer_name" id="quote_customer_name">
        <input type="hidden" name="items" id="quote_items_json">

        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886);">
          <h6 class="modal-title font-weight-bold" id="quotationModalTitle" style="font-size: 1.05rem;">
            <i class="fas fa-file-invoice-dollar mr-2"></i> <?php echo htmlspecialchars(t('quotations.modal_add_title', 'ສ້າງໃບສະເໜີລາຄາໃໝ່')); ?>
          </h6>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>

        <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">

          <!-- Customer picker -->
          <div class="form-row align-items-end">
            <div class="form-group col-md-4">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.f_customer_name', 'ຊື່ລູກຄ້າ')); ?> <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" id="quote_customer_display" class="form-control" readonly placeholder="<?php echo htmlspecialchars(t('quotations.ph_customer_name', 'ຍັງບໍ່ໄດ້ເລືອກລູກຄ້າ')); ?>" style="border-radius: 6px 0 0 6px; height: 40px; background: #f8fafc;">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-primary" onclick="openCustomerPicker()" style="border-radius: 0 6px 6px 0;"><i class="fas fa-user-friends mr-1"></i> <?php echo htmlspecialchars(t('quotations.btn_select_customer', 'ເລືອກລູກຄ້າ')); ?></button>
                </div>
              </div>
            </div>
            <div class="form-group col-md-3">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.f_customer_tel', 'ເບີໂທ')); ?></label>
              <input type="text" id="quote_customer_tel" class="form-control" placeholder="020 ..." style="border-radius: 6px; height: 40px;">
            </div>
            <div class="form-group col-md-5">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.f_customer_address', 'ທີ່ຢູ່')); ?></label>
              <input type="text" id="quote_customer_address" class="form-control" style="border-radius: 6px; height: 40px;">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.f_quote_date', 'ວັນທີສະເໜີ')); ?></label>
              <input type="date" id="quote_date" class="form-control" style="border-radius: 6px; height: 40px;">
            </div>
              <div class="form-group col-md-3" style="display: none;">
                <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.f_valid_until', 'ໃຊ້ໄດ້ຮອດວັນທີ')); ?></label>
                <input type="date" id="quote_valid_until" class="form-control" style="border-radius: 6px; height: 40px;">
              </div>
              <div class="form-group col-md-9">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.f_notes', 'ໝາຍເຫດ')); ?></label>
              <input type="text" id="quote_notes" class="form-control" placeholder="<?php echo htmlspecialchars(t('quotations.ph_notes', 'ໝາຍເຫດ (ຖ້າມີ)')); ?>" style="border-radius: 6px; height: 40px;">
            </div>
          </div>

          <hr>

          <!-- Line items -->
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="font-weight-bold text-dark mb-0"><?php echo htmlspecialchars(t('quotations.items_title', 'ລາຍການສິນຄ້າ')); ?></label>
            <button type="button" class="btn btn-sm btn-primary" onclick="openProductPicker()"><i class="fas fa-plus mr-1"></i> <?php echo htmlspecialchars(t('quotations.btn_select_products', 'ເລືອກສິນຄ້າ')); ?></button>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-2" id="quoteItemsTable">
              <thead style="background: #f8fafc; font-size: 0.82rem;">
                <tr>
                  <th style="min-width: 220px;"><?php echo htmlspecialchars(t('quotations.col_item_product', 'ສິນຄ້າ')); ?></th>
                  <th style="width: 100px;"><?php echo htmlspecialchars(t('quotations.col_item_unit', 'ຫົວໜ່ວຍ')); ?></th>
                  <th style="width: 90px;"><?php echo htmlspecialchars(t('quotations.col_item_qty', 'ຈຳນວນ')); ?></th>
                  <th style="width: 130px;"><?php echo htmlspecialchars(t('quotations.col_item_price', 'ລາຄາ')); ?></th>
                  <th style="width: 120px;"><?php echo htmlspecialchars(t('quotations.col_item_discount', 'ສ່ວນຫຼຸດ')); ?></th>
                  <th style="width: 140px;" class="text-right"><?php echo htmlspecialchars(t('quotations.col_item_total', 'ລວມ')); ?></th>
                  <th style="width: 40px;"></th>
                </tr>
              </thead>
              <tbody id="quoteItemsBody">
                <tr id="quoteItemsEmptyRow">
                  <td colspan="7" class="text-center text-muted py-3"><?php echo htmlspecialchars(t('quotations.items_empty', 'ຍັງບໍ່ໄດ້ເລືອກສິນຄ້າ — ກົດ "ເລືອກສິນຄ້າ" ຂ້າງເທິງ')); ?></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Totals -->
          <div class="row justify-content-end">
            <div class="col-md-5">
              <div class="d-flex justify-content-between mb-1">
                <span class="font-weight-bold text-muted"><?php echo htmlspecialchars(t('quotations.f_subtotal', 'ລວມຍ່ອຍ')); ?></span>
                <span class="font-weight-bold" id="quote_subtotal_display">0 ₭</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="font-weight-bold text-muted"><?php echo htmlspecialchars(t('quotations.f_discount_amount', 'ສ່ວນຫຼຸດລວມ')); ?></span>
                <input type="text" id="quote_discount_amount" class="form-control form-control-sm text-right" style="width: 140px; border-radius: 6px;" value="0" oninput="quoteFormatInput(this); quoteRecalcTotals()">
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="font-weight-bold text-muted"><?php echo htmlspecialchars(t('quotations.f_vat_amount', 'ພາສີ (VAT)')); ?></span>
                <input type="text" id="quote_vat_amount" class="form-control form-control-sm text-right" style="width: 140px; border-radius: 6px;" value="0" oninput="quoteFormatInput(this); quoteRecalcTotals()">
              </div>
              <div class="d-flex justify-content-between pt-2" style="border-top: 2px solid #e2e8f0;">
                <span class="font-weight-bold text-dark" style="font-size: 1.05rem;"><?php echo htmlspecialchars(t('quotations.f_total', 'ຍອດລວມທັງໝົດ')); ?></span>
                <span class="font-weight-bold text-primary" id="quote_total_display" style="font-size: 1.15rem;">0 ₭</span>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('quotations.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('quotations.btn_save', 'ບັນທຶກ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Customer Picker Modal — choose an existing customer from the system -->
<div class="modal fade" id="customerPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header py-3 px-4" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <h6 class="modal-title font-weight-bold text-dark" style="font-size: 1.02rem;"><i class="fas fa-user-friends mr-2 text-primary"></i> <?php echo htmlspecialchars(t('quotations.picker_customer_title', 'ເລືອກລູກຄ້າ')); ?></h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-3">
        <input type="text" id="customerPickerSearch" class="form-control mb-3" placeholder="<?php echo htmlspecialchars(t('quotations.picker_customer_search_ph', 'ຄົ້ນຫາຊື່ ຫຼື ເບີໂທ...')); ?>" oninput="renderCustomerPickerList()" style="border-radius: 8px; height: 42px;">
        <div id="customerPickerList" style="max-height: 360px; overflow-y: auto;"></div>
        <?php if (empty($customersList)): ?>
          <p class="text-muted text-center py-3"><?php echo htmlspecialchars(t('quotations.picker_customer_empty', 'ຍັງບໍ່ມີລູກຄ້າໃນລະບົບ — ພິມຊື່ດ້ານລຸ່ມເພື່ອສ້າງລູກຄ້າໃໝ່')); ?></p>
        <?php endif; ?>
        <hr>
        <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('quotations.picker_customer_new_label', 'ຫຼື ພິມຊື່ລູກຄ້າໃໝ່ (ຍັງບໍ່ມີໃນລະບົບ)')); ?></label>
        <div class="input-group">
          <input type="text" id="customerPickerNewName" class="form-control" placeholder="<?php echo htmlspecialchars(t('quotations.ph_customer_name', 'ຊື່ລູກຄ້າ')); ?>" style="border-radius: 8px 0 0 8px; height: 42px;">
          <div class="input-group-append">
            <button type="button" class="btn btn-success" onclick="quoteSelectNewCustomer()" style="border-radius: 0 8px 8px 0;"><i class="fas fa-check mr-1"></i> <?php echo htmlspecialchars(t('quotations.btn_use_this', 'ໃຊ້ຊື່ນີ້')); ?></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Product Picker Modal — browse by category, tick to add (no typing) -->
<div class="modal fade" id="productPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header py-3 px-4" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <h6 class="modal-title font-weight-bold text-dark" style="font-size: 1.02rem;"><i class="fas fa-boxes mr-2 text-primary"></i> <?php echo htmlspecialchars(t('quotations.picker_product_title', 'ເລືອກສິນຄ້າ')); ?></h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-3">
        <div class="form-row mb-2">
          <div class="col-md-5">
            <select id="productPickerCategory" class="form-control" onchange="quoteResetPickerPageAndRender()" style="border-radius: 8px; height: 42px;">
              <option value=""><?php echo htmlspecialchars(t('quotations.picker_all_categories', '-- ທຸກໝວດໝູ່ --')); ?></option>
              <?php foreach ($categoriesLookup as $cat): ?>
                <option value="<?php echo (int)$cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-7">
            <input type="text" id="productPickerSearch" class="form-control" placeholder="<?php echo htmlspecialchars(t('quotations.picker_product_search_ph', 'ຄົ້ນຫາຊື່ສິນຄ້າ...')); ?>" oninput="quoteResetPickerPageAndRender()" style="border-radius: 8px; height: 42px;">
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="pickerSelectAll" onchange="quoteToggleSelectAllPicker(this.checked)">
                <label class="custom-control-label font-weight-bold" for="pickerSelectAll" style="cursor: pointer;">ເລືອກທັງໝົດ</label>
            </div>
            <div class="text-muted small"><span id="productPickerCheckedCount" class="font-weight-bold text-primary">0</span> <?php echo htmlspecialchars(t('quotations.picker_product_selected', 'ລາຍການທີ່ເລືອກ')); ?></div>
        </div>
        <div id="productPickerList" style="height: 320px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;"></div>
        
        <div class="d-flex align-items-center justify-content-between mt-2 px-1">
            <div class="text-muted small" id="pickerPageInfo">ໜ້າ 1 / 1</div>
            <ul class="pagination pagination-sm mb-0" id="pickerPagination"></ul>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 pb-3 px-4">
        <button type="button" class="btn btn-light font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('quotations.cancel', 'ຍົກເລີກ')); ?></button>
        <button type="button" class="btn btn-primary font-weight-bold px-4" onclick="quoteAddPickedProducts()" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('quotations.btn_add_selected', 'ເພີ່ມທີ່ເລືອກ')); ?>
        </button>
      </div>
    </div>
  </div>
</div>
