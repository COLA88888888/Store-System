<?php
/*
 * ສ່ວນ: Popup ລາຍລະອຽດການໂອນສິນຄ້າ
 * ໂຄງ HTML ຂອງ popup ທີ່ສະແດງລາຍການສິນຄ້າທີ່ສາຂາໃຫຍ່ໂອນມາ (ຂໍ້ມູນເຕີມໂດຍ JS ໃນ
 * js_notifications.php).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <!-- STOCK TRANSFER DETAILS MODAL POPUP -->
  <div class="modal fade" id="notifTransferDetailModal" tabindex="-1" role="dialog" aria-hidden="true" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden;">
        
        <!-- Modal Header -->
        <div class="modal-header bg-primary text-white py-3 px-4" style="border-radius: 16px 16px 0 0;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1.15rem;">
            <i class="fas fa-truck-loading mr-2"></i> ລາຍລະອຽດການໂອນສິນຄ້າຈາກສາຂາໃຫຍ່
          </h5>
          <button type="button" class="close text-white opacity-90" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-4" style="background-color: #f8fafc;">
          <!-- Header Info Card -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3">
              <div class="row" style="font-size: 0.92rem;">
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="fas fa-file-invoice text-primary mr-1"></i> ລະຫັດໃບບິນໂອນ:</span>
                  <strong id="trf_modal_code" class="text-primary font-weight-bold ml-1"></strong>
                </div>
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="far fa-clock text-info mr-1"></i> ວັນທີ-ເວລາໂອນ:</span>
                  <strong id="trf_modal_date" class="text-dark ml-1"></strong>
                </div>
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="fas fa-store text-success mr-1"></i> ຈາກສາຂາ:</span>
                  <span id="trf_modal_from" class="font-weight-bold text-dark ml-1"></span>
                </div>
                <div class="col-md-6 mb-2">
                  <span class="text-muted"><i class="fas fa-arrow-right text-muted mr-1"></i> ຫາສາຂາ:</span>
                  <span id="trf_modal_to" class="font-weight-bold text-dark ml-1"></span>
                </div>
                <div class="col-md-12 mt-1">
                  <span class="text-muted"><i class="fas fa-user-edit text-secondary mr-1"></i> ຜູ້ດຳເນີນການ:</span>
                  <span id="trf_modal_creator" class="font-weight-bold text-dark ml-1"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Products Table Card -->
          <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-2.5 px-3 border-0 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.95rem;">
                <i class="fas fa-boxes text-primary mr-1"></i> ລາຍການສິນຄ້າທີ່ໂອນມາ (<span id="trf_modal_item_count">0</span>)
              </h6>
              <span class="text-success font-weight-bold" style="font-size: 0.92rem;">
                <i class="fas fa-coins mr-1"></i> <span id="trf_modal_total_value">0</span> ₭
              </span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive" style="max-height: 260px; overflow-y: auto; overflow-x: auto;">
                <table class="table table-hover table-striped align-middle mb-0 text-nowrap" style="white-space: nowrap;">
                  <thead class="bg-light text-secondary" style="font-size: 0.85rem; white-space: nowrap;">
                    <tr style="white-space: nowrap;">
                      <th class="text-center py-2.5 text-nowrap" style="width: 50px;">ລຳດັບ</th>
                      <th class="py-2.5 text-nowrap">ຊື່ສິນຄ້າ</th>
                      <th class="text-center py-2.5 text-nowrap">ບາໂຄດ</th>
                      <th class="text-center py-2.5 text-nowrap" style="width: 130px;">ຈຳນວນໂອນ</th>
                      <th class="text-right py-2.5 text-nowrap" style="width: 130px;">ມູນຄ່າ</th>
                    </tr>
                  </thead>
                  <tbody id="trf_modal_table_body" style="font-size: 0.9rem;">
                    <!-- Filled via JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer bg-light py-2.5 px-4" style="border-radius: 0 0 16px 16px;">
          <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal" style="border-radius: 8px;">ປິດ</button>
        </div>

      </div>
    </div>
  </div>

