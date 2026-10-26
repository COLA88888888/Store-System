<?php
// Component: Form Add Stock Import Modal
?>
<!-- Modal: Import Stock -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <form id="importStockForm" action="" method="POST" novalidate>
        <input type="hidden" name="action" value="import_stock">

        <div class="modal-header text-white" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
          <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-truck-loading mr-2"></i> ຟອມບັນທຶກຮັບສິນຄ້າເຂົ້າ
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body p-4">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark mb-1">ເລກທີໃບບິນຮັບເຂົ້າ <span class="text-danger">*</span> <small class="text-muted">(ລັອກອັດໂນມັດ)</small></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                </div>
                <input type="text" id="import_invoice_number" name="invoice_number" class="form-control bg-light font-weight-bold" placeholder="000001" readonly style="border-radius: 0 8px 8px 0; height: 42px; cursor: not-allowed; color: #1e293b;">
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label class="font-weight-bold text-dark mb-1">ຊື່ຜູ້ສະໜອງ / ຮ້ານສົ່ງ</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light"><i class="fas fa-store text-info"></i></span>
                </div>
                <input type="text" name="supplier_name" class="form-control" placeholder="ປ້ອນຊື່ຜູ້ສະໜອງ (ຖ້າມີ)" style="border-radius: 0 8px 8px 0; height: 42px;">
              </div>
            </div>
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1">
              <i class="fas fa-barcode text-primary mr-1"></i> ສີດບາໂຄ້ດ / ຄົ້ນຫາສິນຄ້າ <span class="text-danger">*</span>
            </label>
            <div class="input-group mb-1">
              <div class="input-group-prepend">
                <span class="input-group-text bg-light"><i class="fas fa-barcode text-primary"></i></span>
              </div>
              <input type="text" id="import_barcode_input" class="form-control" placeholder="ສີດບາໂຄ້ດ ຫຼື ປ້ອນຊື່/ລະຫັດສິນຄ້າແລ້ວ Enter..." autocomplete="off" style="border-radius: 0 8px 8px 0; height: 42px;" oninput="onBarcodeInputChange()" onkeydown="onBarcodeKeyDown(event)">
            </div>
            
            <!-- Dynamic Auto-Search Match Banner -->
            <div id="matched_product_info" class="p-2.5 px-3 rounded border d-none align-items-center justify-content-between my-2" style="border-color: #93c5fd !important; background-color: #eff6ff !important;">
              <div>
                <span class="font-weight-bold text-dark d-block" id="matched_name" style="font-size: 0.98rem;">-</span>
                <small class="text-muted">
                  <i class="fas fa-barcode mr-1"></i> ບາໂຄ້ດ: <span id="matched_barcode" class="font-weight-bold text-primary mr-2">-</span>
                  <i class="fas fa-tag mr-1 text-warning"></i> ລາຄາຊື້: <span id="matched_bprice" class="font-weight-bold text-danger mr-2">0 ₭</span>
                  <i class="fas fa-boxes mr-1 text-success"></i> ສະຕັອກ: <span id="matched_stock" class="font-weight-bold text-success">0</span>
                </small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetProductSelection()" title="ເລືອກ/ສີດສິນຄ້າໃໝ່"><i class="fas fa-times"></i></button>
            </div>

            <!-- Hidden Select element for form submission -->
            <select name="product_id" id="import_product_id" class="form-control d-none" onchange="onProductSelect()" required>
              <option value="">-- ເລືອກສິນຄ້າ --</option>
              <?php foreach ($products as $p): ?>
                <?php 
                  $extraBarcodes = [];
                  if (!empty($product_units_map[$p['product_id']])) {
                      foreach ($product_units_map[$p['product_id']] as $u) {
                          if (!empty($u['barcode'])) $extraBarcodes[] = $u['barcode'];
                      }
                  }
                  $allBarcodes = array_filter(array_merge([$p['barcode'] ?? ''], $extraBarcodes));
                ?>
                <option value="<?php echo $p['product_id']; ?>" 
                        data-name="<?php echo htmlspecialchars($p['product_name']); ?>"
                        data-barcode="<?php echo htmlspecialchars($p['barcode'] ?: ''); ?>"
                        data-allbarcodes="<?php echo htmlspecialchars(strtolower(implode(',', $allBarcodes))); ?>"
                        data-baseunit="<?php echo htmlspecialchars($p['unit'] ?: 'ອັນ'); ?>"
                        data-bprice="<?php echo floatval($p['bprice']); ?>"
                        data-stock="<?php echo intval($p['qty']); ?>">
                  <?php echo htmlspecialchars($p['product_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Highlighted Product Name & Buy Price Card (Readonly/Locked) -->
          <div class="card mb-3 border-0 shadow-sm" style="background-color: #f8fafc; border-radius: 12px; border: 1.5px dashed #cbd5e1 !important;">
            <div class="card-body p-3">
              <div class="row align-items-center">
                <!-- Product Name Display (Blue Badge - Locked) -->
                <div class="col-md-6 mb-2 mb-md-0">
                  <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                    <i class="fas fa-box text-primary mr-1"></i> ຊື່ສິນຄ້າ <small class="text-muted">(ລັອກອັດໂນມັດ)</small>
                  </label>
                  <div class="p-2 px-3 rounded font-weight-bold" style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.95rem; min-height: 42px; display: flex; align-items: center;">
                    <span id="import_product_name_display">-- ກະລຸນາສີດບາໂຄ້ດ ຫຼື ປ້ອນຄົ້ນຫາ --</span>
                  </div>
                </div>

                <!-- Buy Price Display (Yellow Badge - Locked) -->
                <div class="col-md-6">
                  <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                    <i class="fas fa-tag text-warning mr-1"></i> ລາຄາຊື້ / ຫົວໜ່ວຍ <small class="text-muted">(ລັອກອັດໂນມັດ)</small>
                  </label>
                  <div class="p-2 px-3 rounded font-weight-bold" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.95rem; min-height: 42px; display: flex; align-items: center; justify-content: space-between;">
                    <span id="import_cost_display">0 ₭</span>
                    <i class="fas fa-lock text-warning" style="font-size: 0.85rem;" title="ລັອກອັດໂນມັດ"></i>
                  </div>
                  <input type="hidden" name="cost_price" id="import_cost" value="0">
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-dark mb-1">ຫົວໜ່ວຍທີ່ຮັບເຂົ້າ <span class="text-danger">*</span></label>
              <select name="unit_key" id="import_unit_key" class="form-control" style="height: 42px; border-radius: 8px;" onchange="updateCalculations()" required>
                <option value="base">ຫົວໜ່ວຍຍ່ອຍ</option>
              </select>
            </div>

            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-dark mb-1">ຈຳນວນຮັບເຂົ້າ <span class="text-danger">*</span> <small class="text-primary font-weight-bold">(ປ້ອນ/ປັບຈຳນວນ)</small></label>
              <input type="number" name="quantity" id="import_qty" class="form-control font-weight-bold border-primary" min="1" value="1" style="height: 42px; border-radius: 8px; font-size: 1.05rem;" oninput="updateCalculations()" required>
            </div>

            <div class="col-md-4 mb-3">
              <label class="font-weight-bold text-dark mb-1"><i class="far fa-calendar-alt text-warning mr-1"></i> ວັນໝົດອາຍຸ (Expiry Date)</label>
              <input type="date" name="expiry_date" class="form-control" style="height: 42px; border-radius: 8px;">
            </div>
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1">ໝາຍເຫດ</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="ໝາຍເຫດເພີ່ມເຕີມ..." style="border-radius: 8px;"></textarea>
          </div>

          <!-- Highlighted Real-time Calculation Summary Box (Locked/Readonly) -->
          <div class="p-3 rounded mb-0 shadow-sm" style="background-color: #ecfdf5; border: 1.5px solid #a7f3d0;">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="font-weight-bold text-secondary" style="font-size: 0.88rem;">
                <i class="fas fa-layer-group text-success mr-1"></i> ຈຳນວນຍ່ອຍລວມທີ່ຈະເພີ່ມເຂົ້າສະຕັອກ:
              </span>
              <span class="h6 mb-0 font-weight-bold text-dark"><span id="calc_total_base">0</span> <span id="calc_base_unit">ອັນ</span></span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: #a7f3d0 !important;">
              <span class="font-weight-bold text-dark" style="font-size: 0.98rem;">
                <i class="fas fa-calculator text-success mr-1"></i> ມູນຄ່າທຶນລວມໃບບິນ (ເງິນລວມ): <small class="text-muted">(ຄິດໄລ່ອັດໂນມັດ)</small>
              </span>
              <span class="h4 mb-0 font-weight-bold text-success" id="calc_total_cost_display">0 ₭</span>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal">ຍົກເລີກ</button>
          <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitImportStock()">
            <i class="fas fa-save mr-1"></i> ບັນທຶກຮັບເຂົ້າ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
