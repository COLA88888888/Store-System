<?php
// Component: Stock Transfer History View Details Modal
?>
<div class="modal fade" id="transferDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-info text-white py-3 px-4">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.05rem;">
          <i class="fas fa-receipt mr-1.5"></i> <?php echo htmlspecialchars(t('transfer_history.modal_title', 'ລາຍລະອຽດໃບໂອນສິນຄ້າ')); ?> (<span id="modalTransferCode">-</span>)
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        <div class="row mb-3 bg-white p-3 rounded border shadow-xs" style="font-size: 0.85rem; border-color: #e2e8f0 !important;">
          <div class="col-md-6 mb-2">
            <span class="text-muted d-block"><?php echo htmlspecialchars(t('transfer_history.modal_from_store', 'ສາຂາຕົ້ນທາງ:')); ?></span>
            <strong class="text-dark" id="modalFromStore">-</strong>
          </div>
          <div class="col-md-6 mb-2">
            <span class="text-muted d-block"><?php echo htmlspecialchars(t('transfer_history.modal_to_store', 'ສາຂາປາຍທາງ:')); ?></span>
            <strong class="text-success" id="modalToStore">-</strong>
          </div>
          <div class="col-md-4">
            <span class="text-muted d-block"><?php echo htmlspecialchars(t('transfer_history.modal_date', 'ວັນທີໂອນ:')); ?></span>
            <strong class="text-dark" id="modalDate">-</strong>
          </div>
          <div class="col-md-4">
            <span class="text-muted d-block"><?php echo htmlspecialchars(t('transfer_history.modal_creator', 'ຜູ້ດຳເນີນການ:')); ?></span>
            <strong class="text-dark" id="modalCreator">-</strong>
          </div>
          <div class="col-md-4">
            <span class="text-muted d-block"><?php echo htmlspecialchars(t('transfer_history.modal_status', 'ສະຖານະ:')); ?></span>
            <strong id="modalStatus" class="text-success">-</strong>
          </div>
        </div>

        <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-boxes text-info mr-1"></i> <?php echo htmlspecialchars(t('transfer_history.modal_items_heading', 'ລາຍການສິນຄ້າທີ່ໂອນ:')); ?></h6>
        <div class="table-responsive bg-white rounded border">
          <table class="table table-hover table-striped mb-0" style="font-size: 0.82rem;">
            <thead class="bg-light">
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th><?php echo htmlspecialchars(t('transfer_history.modal_col_product', 'ຊື່ສິນຄ້າ')); ?></th>
                <th class="text-center" style="width: 140px;"><?php echo htmlspecialchars(t('transfer_history.modal_col_barcode', 'ບາໂຄ້ດ')); ?></th>
                <th class="text-center" style="width: 120px;"><?php echo htmlspecialchars(t('transfer_history.modal_col_qty', 'ຈຳນວນໂອນ')); ?></th>
                <th class="text-center" style="width: 100px;"><?php echo htmlspecialchars(t('transfer_history.modal_col_unit', 'ຫົວໜ່ວຍ')); ?></th>
              </tr>
            </thead>
            <tbody id="modalDetailsTableBody">
            </tbody>
          </table>
        </div>
        
        <div class="mt-3 p-3 bg-white rounded border d-none" id="modalNotesBox" style="font-size: 0.8rem; border-color: #e2e8f0 !important;">
          <strong class="text-dark"><?php echo htmlspecialchars(t('transfer_history.modal_notes_label', 'ໝາຍເຫດ (Notes):')); ?></strong>
          <span class="d-block text-secondary mt-1" id="modalNotesText">-</span>
        </div>
      </div>
      <div class="modal-footer bg-light p-3">
        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('transfer_history.modal_close_btn', 'ປິດ')); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Form to submit cancellations -->
<form id="cancelTransferForm" method="POST" action="" style="display: none;">
  <input type="hidden" name="action" value="cancel_transfer">
  <input type="hidden" name="transfer_id" id="cancel_transfer_id_input">
</form>
