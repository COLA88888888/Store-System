<style>
/* Modal Theme & Corner Standards: ມຸມ 4px ແລະ ສີຫົວຕາມເທັມເພລດ StoreMini */
#modalShiftConfig .modal-content,
#modalOpenShift .modal-content,
#modalCloseShift .modal-content,
#modalShiftReport .modal-content {
    border-radius: 4px !important;
    border: none !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
}

#modalShiftConfig .modal-header,
#modalOpenShift .modal-header,
#modalCloseShift .modal-header,
#modalShiftReport .modal-header {
    background: linear-gradient(135deg, #2c5aa0, #244886) !important;
    border-top-left-radius: 4px !important;
    border-top-right-radius: 4px !important;
    border-bottom: none !important;
    color: #ffffff !important;
    padding: 12px 20px !important;
}

#modalShiftConfig .modal-footer,
#modalOpenShift .modal-footer,
#modalCloseShift .modal-footer,
#modalShiftReport .modal-footer {
    border-bottom-left-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
    border-top: 1px solid #e2e8f0 !important;
    padding: 10px 18px !important;
}

/* ມຸມ 4px ທຸກ Input, Select, Button, Alert, Card ພາຍໃນ Modal */
.modal-content input,
.modal-content select,
.modal-content textarea,
.modal-content .btn,
.modal-content .alert,
.modal-content .card,
.modal-content .p-2\.5 {
    border-radius: 4px !important;
}

.modal-content .input-group > .form-control:not(:last-child) {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 4px !important;
    border-bottom-left-radius: 4px !important;
}

.modal-content .input-group > .input-group-append > .input-group-text {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
}

.modal-content .input-group > .input-group-prepend > .input-group-text {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 4px !important;
    border-bottom-left-radius: 4px !important;
}
</style>

<!-- Modal 1: ຕັ້ງຄ່າກະ (ເພີ່ມ / ແກ້ໄຂ) -->
<div class="modal fade" id="modalShiftConfig" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 720px;">
    <div class="modal-content" style="border-radius: 4px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
      <form id="formShiftConfig" onsubmit="saveShiftConfig(event)">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 4px; border-top-right-radius: 4px; padding: 14px 24px;">
          <h5 class="modal-title font-weight-bold" id="modalShiftConfigTitle" style="color: #ffffff; font-size: 1.15rem;">
            <i class="fas fa-calendar-plus mr-2"></i> ເພີ່ມກະເຮັດວຽກໃໝ່
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4 p-md-4">
          <input type="hidden" id="shiftConfigId" name="id" value="0">
          
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark" style="font-size: 0.95rem;">
              ຊື່ກະເຮັດວຽກ <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white" style="border-radius: 4px 0 0 4px; font-size: 1rem;"><i class="fas fa-tag text-muted"></i></span>
              </div>
              <input type="text" class="form-control font-weight-bold text-dark" id="shiftConfigName" name="shift_name" placeholder="ຕົວຢ່າງ: ກະເຊົ້າ, ກະບ່າຍ, ກະກາງຄືນ" required style="border-radius: 0 4px 4px 0; font-size: 1rem; height: 44px;">
            </div>
          </div>

          <div class="row">
            <!-- ເວລາເລີ່ມເປີດກະ (ໂມງ / ນາທີ ຕາມພາສາ) -->
            <div class="col-12 col-md-6 mb-3 mb-md-0">
              <div class="form-group mb-0 p-3 bg-light rounded border" style="border-radius: 4px; border-color: #e2e8f0 !important;">
                <label class="font-weight-bold text-dark mb-2 d-block" style="font-size: 0.92rem;">
                  <i class="fas fa-clock text-primary mr-1"></i> ເວລາເລີ່ມເປີດກະ <span class="text-danger">*</span>
                </label>
                <div class="d-flex align-items-center" style="gap: 8px;">
                  <div style="flex: 1.35;">
                    <label class="small text-muted mb-1 font-weight-bold d-block" style="font-size: 0.78rem;">ຊົ່ວໂມງ</label>
                    <select class="form-control font-weight-bold text-dark" id="shiftStartHour" onchange="syncShiftTimePicker()" style="border-radius: 4px; font-size: 0.92rem; height: 42px; background: #ffffff;">
                    </select>
                  </div>
                  <div style="flex: 1;">
                    <label class="small text-muted mb-1 font-weight-bold d-block" style="font-size: 0.78rem;">ນາທີ</label>
                    <select class="form-control font-weight-bold text-dark" id="shiftStartMinute" onchange="syncShiftTimePicker()" style="border-radius: 4px; font-size: 0.92rem; height: 42px; background: #ffffff;">
                    </select>
                  </div>
                </div>
                <input type="hidden" id="shiftConfigStartTime" name="start_time" value="07:00">
                <div class="mt-2.5 pt-2 border-top d-flex align-items-center justify-content-between" style="font-size: 0.88rem;">
                  <span class="text-muted">ສະແດງຜົນ:</span>
                  <span class="text-primary font-weight-bold" id="shiftStartTimeDisplay">
                    <i class="fas fa-check-circle mr-1 text-success"></i> 7 ໂມງ 00 ນາທີ
                  </span>
                </div>
              </div>
            </div>

            <!-- ເວລາສິ້ນສຸດກະ (ໂມງ / ນາທີ ຕາມພາສາ) -->
            <div class="col-12 col-md-6">
              <div class="form-group mb-0 p-3 bg-light rounded border" style="border-radius: 4px; border-color: #e2e8f0 !important;">
                <label class="font-weight-bold text-dark mb-2 d-block" style="font-size: 0.92rem;">
                  <i class="fas fa-history text-secondary mr-1"></i> ເວລາສິ້ນສຸດກະ <span class="text-danger">*</span>
                </label>
                <div class="d-flex align-items-center" style="gap: 8px;">
                  <div style="flex: 1.35;">
                    <label class="small text-muted mb-1 font-weight-bold d-block" style="font-size: 0.78rem;">ຊົ່ວໂມງ</label>
                    <select class="form-control font-weight-bold text-dark" id="shiftEndHour" onchange="syncShiftTimePicker()" style="border-radius: 4px; font-size: 0.92rem; height: 42px; background: #ffffff;">
                    </select>
                  </div>
                  <div style="flex: 1;">
                    <label class="small text-muted mb-1 font-weight-bold d-block" style="font-size: 0.78rem;">ນາທີ</label>
                    <select class="form-control font-weight-bold text-dark" id="shiftEndMinute" onchange="syncShiftTimePicker()" style="border-radius: 4px; font-size: 0.92rem; height: 42px; background: #ffffff;">
                    </select>
                  </div>
                </div>
                <input type="hidden" id="shiftConfigEndTime" name="end_time" value="15:00">
                <div class="mt-2.5 pt-2 border-top d-flex align-items-center justify-content-between" style="font-size: 0.88rem;">
                  <span class="text-muted">ສະແດງຜົນ:</span>
                  <span class="text-secondary font-weight-bold" id="shiftEndTimeDisplay">
                    <i class="fas fa-check-circle mr-1 text-success"></i> 15 ໂມງ 00 ນາທີ
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- ພະນັກງານປະຈຳກະ: ຕິກເລືອກວ່າກະນີ້ມີໃຜແດ່ (ຈຳນວນຄົນ = ຈຳນວນທີ່ຕິກ) — ສະແດງໃນລາຍງານປິດກະ -->
          <div class="form-group mb-0 mt-3">
            <label class="font-weight-bold text-dark mb-1 d-flex justify-content-between align-items-center" style="font-size: 0.92rem;">
              <span><i class="fas fa-users text-primary mr-1"></i> ພະນັກງານໃນກະນີ້ <span class="text-danger">*</span></span>
              <span class="badge badge-danger px-2 py-1" id="shiftStaffCount">0 ຄົນ</span>
            </label>
            <div id="shiftStaffList" class="border rounded p-2" style="max-height: 180px; overflow-y: auto; border-color: #e2e8f0 !important; background: #fff; transition: all 0.3s ease;">
              <div class="text-muted small py-2 text-center"><i class="fas fa-spinner fa-spin mr-1"></i> ກຳລັງໂຫຼດ...</div>
            </div>
            <small id="shiftStaffRequiredNotice" class="text-danger d-block mt-1 font-weight-bold" style="font-size: 0.82rem;">
              <i class="fas fa-exclamation-circle mr-1"></i> ກະລຸນາເລືອກພະນັກງານກະນີ້ກ່ອນ
            </small>
          </div>

          <?php /* ສະວິດ "ເປີດໃຊ້ງານກະນີ້" ຖືກເອົາອອກ: ກະໃໝ່ເປີດໃຊ້ງານເລີຍ, ແກ້ໄຂແລ້ວສະຖານະເດີມຄົງເກົ່າ (api/shifts_backend.php) */ ?>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; border-top: 1px solid #e2e8f0; padding: 12px 24px;">
          <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal" style="border-radius: 4px; height: 42px;">ຍົກເລີກ</button>
          <button type="submit" id="btnSaveShiftConfig" class="btn btn-primary px-4 font-weight-bold" style="border-radius: 4px; height: 42px; background: linear-gradient(135deg, #2c5aa0, #244886) !important; border: none; box-shadow: 0 4px 10px rgba(36, 72, 134, 0.2);">
            <i class="fas fa-save mr-1.5"></i> ບັນທຶກ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 2: ເປີດກະເຮັດວຽກ (Open Shift Modal) -->
<div class="modal fade" id="modalOpenShift" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 580px;">
    <div class="modal-content" style="border-radius: 4px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
      <form id="formOpenShift" onsubmit="submitOpenShift(event)">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 4px; border-top-right-radius: 4px; padding: 14px 24px;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1.15rem;">
            <i class="fas fa-door-open mr-2"></i> ເປີດກະເຮັດວຽກ
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center" style="border-radius: 4px; font-size: 0.88rem; background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1;">
            <i class="fas fa-info-circle mr-2 text-primary" style="font-size: 1.1rem;"></i>
            <span>ເວລາປະຈຸບັນຂອງລະບົບ: <strong id="openShiftCurrentTimeDisplay">--:--</strong></span>
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark" style="font-size: 0.92rem;">
              ເລືອກກະເຮັດວຽກ <span class="text-danger">*</span>
            </label>
            <select class="form-control" id="openShiftSelectId" name="shift_id" required style="border-radius: 4px; font-size: 0.95rem; height: 42px;" onchange="onShiftSelectionChange()">
              <option value="">-- ເລືອກກະ --</option>
            </select>
            <div id="openShiftTimeWarning" class="alert alert-danger py-2 px-3 mt-2 mb-0" style="display: none; border-radius: 4px; font-size: 0.85rem;">
              <i class="fas fa-exclamation-triangle mr-1"></i> <span id="openShiftTimeWarningText">ຍັງບໍ່ຮອດໂມງເປີດກະ</span>
            </div>
          </div>

          <div class="form-group mb-2">
            <label class="font-weight-bold text-dark" style="font-size: 0.92rem;">
              ເງິນສົດໃນລິ້ນຊັກເກັບເງິນ (ຕອນເປີດກະ) <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-lg">
              <input type="text" class="form-control text-right font-weight-bold text-primary" id="openShiftInitialCash" name="initial_cash" value="" placeholder="ປ້ອນຈຳນວນເງິນ (ບໍ່ມີໃຫ້ໃສ່ 0)" required style="border-radius: 4px 0 0 4px; font-size: 1.35rem;" oninput="formatNumberInput(this)">
              <div class="input-group-append">
                <span class="input-group-text font-weight-bold bg-light" style="border-radius: 0 4px 4px 0;"><?php echo kipSymbol(); ?></span>
              </div>
            </div>
            <small class="text-muted d-block mt-1">
              <i class="fas fa-coins mr-1 text-warning"></i> ປ້ອນເງິນທອນເລີ່ມຕົ້ນໃນລິ້ນຊັກ (ຖ້າບໍ່ມີເງິນທອນໃຫ້ໃສ່ <strong>0</strong>)
            </small>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
          <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" style="border-radius: 4px;">ຍົກເລີກ</button>
          <button type="submit" id="btnSubmitOpenShift" class="btn btn-primary px-4 font-weight-bold" style="border-radius: 4px; background: linear-gradient(135deg, #2c5aa0, #244886) !important; border: none;">
            <i class="fas fa-check-circle mr-1"></i> ຢືນຢັນເປີດກະ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 3: ປິດກະເຮັດວຽກ (Close Shift Modal) -->
<div class="modal fade" id="modalCloseShift" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content" style="border-radius: 4px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
      <form id="formCloseShift" onsubmit="submitCloseShift(event)">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 4px; border-top-right-radius: 4px;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
            <i class="fas fa-door-closed mr-2"></i> ປິດກະເຮັດວຽກ & ສະຫຼຸບຍອດຂາຍ
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="closeShiftSessionId" name="session_id" value="0">

          <!-- Shift Info Banner -->
          <div class="card bg-light border-0 mb-3" style="border-radius: 4px;">
            <div class="card-body p-3">
              <div class="row text-center text-md-left">
                <div class="col-md-4 mb-2 mb-md-0">
                  <small class="text-muted d-block">ຊື່ກະ:</small>
                  <strong class="text-dark" style="font-size: 1.05rem;" id="closeShiftNameDisplay">-</strong>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <small class="text-muted d-block">ເວລາເປີດກະ:</small>
                  <span class="text-dark font-weight-bold" id="closeShiftOpenTimeDisplay">-</span>
                </div>
                <div class="col-md-4">
                  <small class="text-muted d-block">ຜູ້ເປີດກະ:</small>
                  <span class="text-dark font-weight-bold" id="closeShiftOpenedByDisplay">-</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Sales Summary Overview Grid -->
          <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2">
              <div class="p-2.5 bg-white border text-center shadow-xs" style="border-radius: 4px;">
                <small class="text-muted d-block" style="font-size: 0.78rem;">ເງິນລິ້ນຊັກຕອນເປີດ</small>
                <span class="font-weight-bold text-secondary" style="font-size: 1.05rem;" id="closeShiftInitialCashDisplay">0 <?php echo kipSymbol(); ?></span>
              </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
              <div class="p-2.5 bg-white border text-center shadow-xs" style="border-radius: 4px;">
                <small class="text-muted d-block" style="font-size: 0.78rem;">ຂາຍເງິນສົດ (ລະບົບ)</small>
                <span class="font-weight-bold text-success" style="font-size: 1.05rem;" id="closeShiftSysCashDisplay">0 <?php echo kipSymbol(); ?></span>
              </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
              <div class="p-2.5 bg-white border text-center shadow-xs" style="border-radius: 4px;">
                <small class="text-muted d-block" style="font-size: 0.78rem;">ຂາຍໂອນ / QR (ລະບົບ)</small>
                <span class="font-weight-bold text-info" style="font-size: 1.05rem;" id="closeShiftSysTransferDisplay">0 <?php echo kipSymbol(); ?></span>
              </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
              <div class="p-2.5 bg-white border text-center shadow-xs" style="border-radius: 4px;">
                <small class="text-muted d-block" style="font-size: 0.78rem;">ຍອດຂາຍລວມ (<span id="closeShiftTotalBillsDisplay">0</span> ບິນ)</small>
                <span class="font-weight-bold text-primary" style="font-size: 1.05rem;" id="closeShiftSysTotalDisplay">0 <?php echo kipSymbol(); ?></span>
              </div>
            </div>
          </div>

          <hr class="my-3">

          <!-- Cash Drawer Reconciliation -->
          <div class="p-3 mb-3" style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 4px;">
            <label class="font-weight-bold text-dark d-block mb-1" style="font-size: 0.98rem;">
              <i class="fas fa-hand-holding-usd text-warning mr-1"></i> ປ້ອນຈຳນວນເງິນສົດທີ່ນັບໄດ້ຕົວຈິງ (ສະເພາະຂາຍ) <span class="text-danger">*</span>
            </label>
            <div class="small text-danger font-weight-bold mb-2">
              * ໝາຍເຫດ: ປ້ອນສະເພາະເງິນທີ່ໄດ້ຈາກການຂາຍເທົ່ານັ້ນ (ບໍ່ລວມກັບເງິນລິ້ນຊັກຕອນເປີດກະ)
            </div>

            <div class="input-group input-group-lg mb-2">
              <input type="text" class="form-control text-right font-weight-bold text-dark" id="closeShiftCountedCash" name="counted_sales_cash" value="0" placeholder="0" required style="border-radius: 4px 0 0 4px; font-size: 1.45rem; background: #ffffff;" oninput="formatNumberInput(this); calculateShiftDifference();">
              <div class="input-group-append">
                <span class="input-group-text font-weight-bold bg-white" style="border-radius: 0 4px 4px 0;"><?php echo kipSymbol(); ?></span>
              </div>
            </div>

            <!-- Dynamic Live Difference Indicator -->
            <div id="shiftDiffAlert" class="alert alert-secondary py-2 px-3 mb-0 d-flex justify-content-between align-items-center" style="border-radius: 4px;">
              <span><strong>ສ່ວນຕ່າງເງິນສົດ (ຕົວຈິງ - ລະບົບ):</strong></span>
              <span class="font-weight-bold" id="shiftDiffDisplay" style="font-size: 1.2rem;">0 <?php echo kipSymbol(); ?></span>
            </div>
          </div>

          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark" style="font-size: 0.88rem;">ໝາຍເຫດ / ລາຍລະອຽດເພີ່ມເຕີມ:</label>
            <textarea class="form-control" id="closeShiftNotes" name="notes" rows="2" placeholder="ລະບຸເຫດຜົນຫາກເງິນຂາດ ຫຼື ເກີນ (ຖ້າມີ)..." style="border-radius: 4px;"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
          <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" style="border-radius: 4px;">ຍົກເລີກ</button>
          <button type="submit" id="btnSubmitCloseShift" class="btn btn-primary px-4 font-weight-bold" style="border-radius: 4px; background: linear-gradient(135deg, #2c5aa0, #244886) !important; border: none;">
            <i class="fas fa-check mr-1"></i> ຢືນຢັນປິດກະ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal 4: ເບິ່ງລາຍງານກະ & Export (Shift Report Modal) -->
<div class="modal fade" id="modalShiftReport" tabindex="-1" role="dialog" aria-hidden="true" style="overflow-y: hidden;">
  <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document" style="max-height: calc(100vh - 40px); margin: 20px auto;">
    <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.25); max-height: calc(100vh - 40px); display: flex; flex-direction: column; overflow: hidden;">
      <div class="modal-header text-white flex-shrink-0" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 8px; border-top-right-radius: 8px; z-index: 10;">
        <h5 class="modal-title font-weight-bold" id="modalShiftReportTitle" style="font-size: 1.05rem;">
          <i class="fas fa-file-invoice-dollar text-warning mr-2"></i> <?php echo htmlspecialchars(t('shifts.rpt_modal_title', 'ລາຍງານສະຫຼຸບກະເຮັດວຽກ')); ?>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4 flex-grow-1" id="printableShiftReport" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
        <!-- Rendered by JavaScript: see renderShiftReportContent() in js.php -->
        <div id="shiftReportContent">
          <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
            <p class="text-muted">ກຳລັງໂຫຼດຂໍ້ມູນລາຍງານ...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light d-flex justify-content-between align-items-center flex-shrink-0" style="border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; z-index: 10;">
        <div>
          <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" style="border-radius: 4px;">
            <i class="fas fa-times mr-1"></i> <?php echo htmlspecialchars(t('shifts.rpt_btn_close', 'ປິດ')); ?>
          </button>
        </div>
        <div class="d-flex" style="gap: 8px;">
          <button type="button" class="btn btn-outline-dark font-weight-bold px-3 shadow-xs" onclick="printShiftReportA4()" style="border-radius: 4px;">
            <i class="fas fa-print mr-1 text-primary"></i> <?php echo htmlspecialchars(t('shifts.rpt_btn_print', 'ພິມສະຫຼຸບ')); ?>
          </button>
          <button type="button" class="btn btn-success font-weight-bold px-3 shadow-xs" onclick="exportShiftReportExcel()" style="border-radius: 4px; background: #15803d; border-color: #15803d;">
            <i class="fas fa-file-excel mr-1"></i> Excel
          </button>
          <button type="button" class="btn btn-danger font-weight-bold px-3 shadow-xs" onclick="exportShiftReportPDF()" style="border-radius: 4px; background: #dc2626; border-color: #dc2626;">
            <i class="fas fa-file-pdf mr-1"></i> PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
