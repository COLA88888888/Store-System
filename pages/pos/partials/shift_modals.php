<style>
/* POS Shift Modals Theme & 4px Corner Standards */
#posShiftOpenModal .modal-content,
#posShiftEarlyLockModal .modal-content,
#posShiftCloseModal .modal-content,
#posShiftReportModal .modal-content {
    border-radius: 4px !important;
    border: none !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.25) !important;
}

#posShiftOpenModal .modal-header,
#posShiftCloseModal .modal-header,
#posShiftReportModal .modal-header {
    background: linear-gradient(135deg, #2c5aa0, #244886) !important;
    border-top-left-radius: 4px !important;
    border-top-right-radius: 4px !important;
    border-bottom: none !important;
    color: #ffffff !important;
    padding: 12px 20px !important;
}

#posShiftOpenModal .modal-footer,
#posShiftCloseModal .modal-footer,
#posShiftReportModal .modal-footer {
    border-bottom-left-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
    border-top: 1px solid #e2e8f0 !important;
    padding: 10px 18px !important;
}

#posShiftOpenModal input, #posShiftOpenModal select, #posShiftOpenModal textarea, #posShiftOpenModal .btn, #posShiftOpenModal .alert,
#posShiftCloseModal input, #posShiftCloseModal select, #posShiftCloseModal textarea, #posShiftCloseModal .btn, #posShiftCloseModal .alert, #posShiftCloseModal .card,
#posShiftReportModal .btn,
#posShiftEarlyLockModal .btn, #posShiftEarlyLockModal .alert {
    border-radius: 4px !important;
}

#posShiftOpenModal .input-group > .form-control:not(:last-child),
#posShiftCloseModal .input-group > .form-control:not(:last-child) {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 4px !important;
    border-bottom-left-radius: 4px !important;
}

#posShiftOpenModal .input-group > .input-group-append > .input-group-text,
#posShiftCloseModal .input-group > .input-group-append > .input-group-text {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
}
</style>

<!-- ====================================================================
     POS Work Shift Modals (ເປີດກະ, ປິດກະ, ເຕືອນຍັງບໍ່ຮອດໂມງ, ສະຫຼຸບຍອດຂາຍ)
     ==================================================================== -->

<!-- Modal A: ເປີດກະເຮັດວຽກຕອນເລີ່ມຕົ້ນ (POS Open Shift Modal) -->
<div class="modal fade" id="posShiftOpenModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 4px; border: none; box-shadow: 0 20px 45px rgba(0,0,0,0.3);">
      <form id="posFormOpenShift" onsubmit="submitPosOpenShift(event)">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 4px; border-top-right-radius: 4px;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
            <i class="fas fa-user-clock mr-2"></i> ເປີດກະ — ປ້ອນເງິນສົດໃນລິ້ນຊັກ
          </h5>
        </div>
        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center" style="border-radius: 4px; font-size: 0.88rem; background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1;">
            <i class="fas fa-clock mr-2 text-primary" style="font-size: 1.15rem;"></i>
            <span>ເວລາປະຈຸບັນຂອງລະບົບ: <strong id="posCurrentClockDisplay">--:--</strong></span>
          </div>

          <!-- ມີກະທີ່ເປີດໄດ້ກະດຽວ → ເລືອກໃຫ້ເອງ ສະແດງຊື່ກະແທນ dropdown (populatePosOpenShiftModal) -->
          <div id="posShiftAutoInfo" class="mb-3 p-2 px-3" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.95rem;">
            <i class="fas fa-business-time text-primary mr-1"></i> ກະ: <strong id="posShiftAutoName">-</strong>
          </div>

          <div class="form-group mb-3" id="posShiftSelectGroup">
            <label class="font-weight-bold text-dark" style="font-size: 0.92rem;">
              ເລືອກກະເຮັດວຽກ <span class="text-danger">*</span>
            </label>
            <select class="form-control" id="posSelectShiftId" name="shift_id" required style="border-radius: 4px; font-size: 0.95rem; height: 42px;" onchange="onPosShiftSelectionChange()">
              <option value="">-- ເລືອກກະເຮັດວຽກ --</option>
            </select>
            <div id="posShiftTimeWarningAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0" style="display: none; border-radius: 4px; font-size: 0.85rem;">
              <i class="fas fa-exclamation-triangle mr-1"></i> <span id="posShiftTimeWarningText">ຍັງບໍ່ຮອດໂມງເປີດກະ</span>
            </div>
          </div>

          <div class="form-group mb-2">
            <label class="font-weight-bold text-dark" style="font-size: 0.92rem;">
              ເງິນສົດໃນລິ້ນຊັກເກັບເງິນ (ຕອນເປີດກະ) <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-lg">
              <input type="text" inputmode="numeric" class="form-control text-right font-weight-bold text-primary" id="posInitialCashInput" name="initial_cash" value="" placeholder="ປ້ອນຈຳນວນເງິນ (ບໍ່ມີໃຫ້ໃສ່ 0)" required style="border-radius: 6px 0 0 6px; font-size: 1.55rem; height: 54px;" oninput="formatNumberInput(this)">
              <div class="input-group-append">
                <span class="input-group-text font-weight-bold bg-light" style="border-radius: 0 6px 6px 0; font-size: 1.35rem; padding: 0 16px;"><?php echo kipSymbol(); ?></span>
              </div>
            </div>
            <small class="text-muted d-block mt-1">
              <i class="fas fa-info-circle mr-1 text-info"></i> ກ່ອນເລີ່ມຂາຍຕ້ອງປ້ອນເງິນສົດໃນລິ້ນຊັກກ່ອນ (ຖ້າບໍ່ມີເງິນທອນໃຫ້ໃສ່ <strong>0</strong>)
            </small>
          </div>
        </div>
        <div class="modal-footer bg-light p-3" style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
          <button type="submit" id="btnPosSubmitOpenShift" class="btn btn-primary btn-block font-weight-bold" style="border-radius: 8px; font-size: 1.25rem; min-height: 54px; padding: 12px 20px; background: linear-gradient(135deg, #2c5aa0, #244886) !important; border: none; box-shadow: 0 4px 14px rgba(36, 72, 134, 0.35);">
            <i class="fas fa-check-circle mr-2 fa-lg"></i> ຕົກລົງ — ເປີດກະ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal B: ບລັອກຖ້າຍັງບໍ່ຮອດໂມງເປີດກະ (Early Shift Lock Modal) -->
<div class="modal fade" id="posShiftEarlyLockModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content text-center p-4" style="border-radius: 4px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.35);">
      <div class="mb-3">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: #fee2e2; color: #dc2626;">
          <i class="fas fa-hourglass-start fa-2x"></i>
        </div>
      </div>
      <h4 class="font-weight-bold text-danger mb-2">ຍັງບໍ່ຮອດໂມງເປີດກະ!</h4>
      <p class="text-dark mb-1" style="font-size: 1.05rem;" id="posEarlyShiftMessage">
        ກະຖັດໄປຈະເລີ່ມເວລາ --:--
      </p>
      <small class="text-muted d-block mb-4" style="font-size: 0.90rem;">
        ລະບົບອະນຸຍາດໃຫ້ເປີດກະໄດ້ສະເພາະເມື່ອຮອດກຳນົດເວລາທີ່ຕັ້ງໄວ້ເທົ່ານັ້ນ. ເວລາປັດຈຸບັນ: <strong id="posEarlyCurrentClock">--:--</strong>
      </small>
      <div class="d-flex" style="gap: 10px;">
        <button type="button" class="btn btn-outline-secondary flex-fill font-weight-bold py-2" onclick="checkShiftStatusOnPosLoad()" style="border-radius: 4px;">
          <i class="fas fa-sync-alt mr-1"></i> ກວດສອບເວລາຄືນ
        </button>
        <button type="button" class="btn btn-danger flex-fill font-weight-bold py-2" onclick="window.top.location.href='../../auth/logout.php'" style="border-radius: 4px;">
          <i class="fas fa-sign-out-alt mr-1"></i> ອອກຈາກລະບົບ
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal C: ປິດກະເຮັດວຽກ (POS Close Shift Modal) -->
<div class="modal fade" id="posShiftCloseModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content" style="border-radius: 4px; border: none; box-shadow: 0 20px 45px rgba(0,0,0,0.3);">
      <form id="posFormCloseShift" onsubmit="submitPosCloseShift(event)">
        <div class="modal-header text-white" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 4px; border-top-right-radius: 4px;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
            <i class="fas fa-door-closed mr-2"></i> ປິດກະເຮັດວຽກ & ສະຫຼຸບຍອດຂາຍ
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="posCloseSessionId" name="session_id" value="0">

          <!-- Shift Info Summary -->
          <div class="card bg-light border-0 mb-3" style="border-radius: 4px;">
            <div class="card-body p-3">
              <div class="row text-center text-md-left">
                <div class="col-md-4 mb-2 mb-md-0">
                  <small class="text-muted d-block">ຊື່ກະ:</small>
                  <strong class="text-dark" style="font-size: 1.1rem;" id="posCloseShiftNameDisplay">-</strong>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                  <small class="text-muted d-block">ເວລາເປີດກະ:</small>
                  <span class="text-dark font-weight-bold" id="posCloseShiftOpenTimeDisplay">-</span>
                </div>
                <div class="col-md-4">
                  <small class="text-muted d-block">ຜູ້ເປີດກະ:</small>
                  <span class="text-dark font-weight-bold" id="posCloseShiftOpenedByDisplay">-</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ປິດກະ: ສະແດງແຕ່ເງິນລິ້ນຊັກຕອນເປີດ — ບໍ່ສະແດງຍອດຂາຍລະບົບ ແລະ ສ່ວນຕ່າງ (ນັບເງິນຕົວຈິງໂດຍບໍ່ຮູ້ຍອດ).
               ສ່ວນຕ່າງ ຜູ້ຈັດການ/ຜູ້ບໍລິຫານ ເຫັນໃນລາຍງານຫຼັງປິດກະ -->
          <div class="mb-3" id="posCloseSysGrid">
            <div class="p-2.5 bg-white border text-center shadow-xs" style="border-radius: 4px;">
              <small class="text-muted d-block" style="font-size: 0.82rem;">ເງິນລິ້ນຊັກຕອນເປີດກະ</small>
              <span class="font-weight-bold text-secondary" style="font-size: 1.15rem;" id="posCloseInitialCashDisplay">0 <?php echo kipSymbol(); ?></span>
            </div>
          </div>

          <hr class="my-3">

          <!-- Counted Cash Input -->
          <div class="p-3 mb-3" style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 4px;">
            <label class="font-weight-bold text-dark d-block mb-1" style="font-size: 0.98rem;">
              <i class="fas fa-hand-holding-usd text-warning mr-1"></i> ປ້ອນຈຳນວນເງິນສົດທີ່ນັບໄດ້ຕົວຈິງ (ສະເພາະຂາຍ) <span class="text-danger">*</span>
            </label>
            <div class="small text-danger font-weight-bold mb-2">
              * ໝາຍເຫດ: ປ້ອນສະເພາະເງິນທີ່ໄດ້ຈາກການຂາຍເທົ່ານັ້ນ (ບໍ່ລວມກັບເງິນລິ້ນຊັກຕອນເປີດກະ)
            </div>

            <div class="input-group input-group-lg">
              <input type="text" inputmode="numeric" class="form-control text-right font-weight-bold text-dark" id="posCloseCountedCashInput" name="counted_sales_cash" value="" placeholder="ປ້ອນເງິນທີ່ນັບໄດ້ (ບໍ່ມີໃຫ້ໃສ່ 0)" required style="border-radius: 4px 0 0 4px; font-size: 1.45rem; background: #ffffff;" oninput="formatNumberInput(this);">
              <div class="input-group-append">
                <span class="input-group-text font-weight-bold bg-white" style="border-radius: 0 4px 4px 0;"><?php echo kipSymbol(); ?></span>
              </div>
            </div>

          </div>

          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark" style="font-size: 0.88rem;">ໝາຍເຫດ / ລາຍລະອຽດເພີ່ມເຕີມ:</label>
            <textarea class="form-control" id="posCloseShiftNotes" name="notes" rows="2" placeholder="ລະບຸເຫດຜົນຫາກເງິນຂາດ ຫຼື ເກີນ (ຖ້າມີ)..." style="border-radius: 4px;"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light" style="border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
          <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" style="border-radius: 4px;">ຍົກເລີກ</button>
          <button type="submit" id="btnPosSubmitCloseShift" class="btn btn-primary px-4 font-weight-bold" style="border-radius: 4px; background: linear-gradient(135deg, #2c5aa0, #244886) !important; border: none;">
            <i class="fas fa-check mr-1"></i> <span id="posCloseSubmitLabel">ຢືນຢັນປິດກະ</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal D: ລາຍງານສະຫຼຸບກະ & ອອກຈາກລະບົບ (POS Shift Summary & Export Modal) -->
<div class="modal fade" id="posShiftReportModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" style="overflow-y: hidden;">
  <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document" style="max-height: calc(100vh - 40px); margin: 20px auto;">
    <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 20px 45px rgba(0,0,0,0.3); max-height: calc(100vh - 40px); display: flex; flex-direction: column; overflow: hidden;">
      <div class="modal-header text-white flex-shrink-0" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; border-top-left-radius: 8px; border-top-right-radius: 8px; z-index: 10;">
        <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
          <i class="fas fa-file-invoice-dollar text-warning mr-2"></i> ລາຍງານສະຫຼຸບປິດກະເຮັດວຽກ
        </h5>
      </div>
      <div class="modal-body p-4 flex-grow-1" id="posPrintableReportArea" style="overflow-y: auto; -webkit-overflow-scrolling: touch;">
        <div id="posShiftReportModalContent">
          <!-- Populated by JavaScript -->
        </div>
      </div>
      <div class="modal-footer bg-light d-flex justify-content-between align-items-center flex-shrink-0" style="border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; z-index: 10;">
        <div>
          <button type="button" class="btn btn-danger font-weight-bold px-3 py-2 shadow-xs" onclick="logoutCashierAfterShift()" style="border-radius: 4px;">
            <i class="fas fa-sign-out-alt mr-1"></i> ອອກຈາກລະບົບ
          </button>
        </div>
        <div class="d-flex" style="gap: 8px;">
          <button type="button" class="btn btn-outline-dark font-weight-bold px-3 shadow-xs" onclick="printPosShiftThermalSlip()" style="border-radius: 4px;">
            <i class="fas fa-print mr-1 text-primary"></i> ພິມສະຫຼຸບ
          </button>
          <button type="button" class="btn btn-success font-weight-bold px-3 shadow-xs" onclick="exportPosShiftExcel()" style="border-radius: 4px; background: #15803d; border-color: #15803d;">
            <i class="fas fa-file-excel mr-1"></i> Excel
          </button>
          <button type="button" class="btn btn-danger font-weight-bold px-3 shadow-xs" onclick="exportPosShiftPDF()" style="border-radius: 4px; background: #dc2626; border-color: #dc2626;">
            <i class="fas fa-file-pdf mr-1"></i> PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
