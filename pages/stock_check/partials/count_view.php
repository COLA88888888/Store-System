<?php
/*
 * ສ່ວນ: ໜ້ານັບສິນຄ້າຂອງໃບເຊັກສະຕັອກ
 * ຫົວໃບ (ເລກທີ/ສະຖານະ/ຜູ້ກວດ), ບັດສະຫຼຸບ (ທັງໝົດ/ນັບແລ້ວ/ຄົບ/ບໍ່ຄົບ/ເກີນ/ສ່ວນຕ່າງລວມ), ຊ່ອງສະແກນ-ຄົ້ນຫາ,
 * ປຸ່ມກັ່ນຕອງ ແລະ ຕາຕະລາງ: ຈຳນວນໃນລະບົບ ທຽບ ຈຳນວນນັບໄດ້ → ສ່ວນຕ່າງ (ຈຳນວນສະຕັອກ, ບໍ່ແມ່ນເງິນ).
 * ໃບທີ່ຢືນຢັນແລ້ວ ສະແດງແບບອ່ານຢ່າງດຽວ. ຕົວເລກສະຫຼຸບ ແລະ ສ່ວນຕ່າງ ຄິດໂດຍ count_js.php.
 * ຖືກ require ຈາກ pages/stock_check/stock_check.php (ໃຊ້ $scCheck, $scItems ຈາກ backend).
 */
$scStatus     = $scCheck['status'];
$scIsDraft    = ($scStatus === 'draft');
$scIsPending  = ($scStatus === 'pending');
$scIsApproved = ($scStatus === 'approved');
$scIsDone     = ($scStatus === 'completed');
$scEditable   = ($scIsDraft || $scIsPending || $scIsApproved) && $scCanCount;   // ປ້ອນ/ບັນທຶກຈຳນວນ
// ຈຳນວນໃນລະບົບ: ສະແດງໃຫ້ຫົວໜ້າເຫັນເວລາກວດສອບ (pending) ແລະ ສະແດງເມື່ອອະນຸມັດແລ້ວ (approved) ຫຼື ປິດໃບແລ້ວ (completed)
$scShowSystem = $scIsDone || $scIsApproved || ($scIsPending && $scSeeResults);
// ຜົນການນັບ (ຄົບ/ບໍ່ຄົບ/ເກີນ): ສະແດງໃຫ້ຜູ້ຈັດການ/ຫົວໜ້າ ຫຼື ເມື່ອຫົວໜ້າອະນຸມັດແລ້ວ/ປິດໃບແລ້ວ
$scShowResults = $scIsDone || $scIsApproved || $scSeeResults;
$scColCount = 7 + ($scShowSystem ? 1 : 0) + ($scShowResults ? 2 : 0);
// ໃບທີ່ຢືນຢັນປິດແລ້ວ: ສະແດງແຕ່ລາຍການທີ່ນັບ (ລາຍການທີ່ບໍ່ໄດ້ນັບ ບໍ່ຖືກປັບ — ບໍ່ຕ້ອງສະແດງ).
$scItemTotal = count($scItems);
if ($scIsDone) {
    $scItems = array_values(array_filter($scItems, function ($it) { return $it['counted_qty'] !== null; }));
}
?>
  <section class="content-header py-3 sc-no-print-pad">
    <div class="container-fluid">
      <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap: 10px;">
        <div>
          <h5 class="m-0 font-weight-bold text-dark sc-title">
            <i class="fas fa-clipboard-check text-success mr-2"></i> ເຊັກສະຕັອກ <?php echo htmlspecialchars($scCheck['check_no']); ?>
            <?php if ($scIsDraft): ?>
              <span class="badge sc-badge-draft align-middle ml-1">ກຳລັງນັບ</span>
            <?php elseif ($scIsPending): ?>
              <span class="badge badge-warning align-middle ml-1 px-2 py-1"><i class="fas fa-clock mr-1"></i>ລໍຖ້າຫົວໜ້າອະນຸມັດ</span>
            <?php elseif ($scIsApproved): ?>
              <span class="badge badge-info align-middle ml-1 px-2 py-1"><i class="fas fa-user-check mr-1"></i>ຫົວໜ້າອະນຸມັດແລ້ວ (ພ້ອມປັບສະຕັອກ)</span>
            <?php else: ?>
              <span class="badge sc-badge-done align-middle ml-1">ນັບແລ້ວ</span>
            <?php endif; ?>
          </h5>
          <div class="small text-muted mt-1">
            ເລີ່ມ: <?php echo date('d/m/Y H:i', strtotime($scCheck['created_at'])); ?>
            · ໂດຍ: <?php echo htmlspecialchars($scCheck['created_by_name'] ?? '-'); ?>
            <?php if (!empty($scCheck['approved_at'])): ?>
              · ອະນຸຍາດໂດຍ: <b><?php echo htmlspecialchars($scCheck['approved_by_name'] ?? '-'); ?></b> (<?php echo date('d/m/Y H:i', strtotime($scCheck['approved_at'])); ?>)
            <?php endif; ?>
            <?php if ($scIsDone): ?>
              · ປັບສະຕັອກ: <?php echo !empty($scCheck['completed_at']) ? date('d/m/Y H:i', strtotime($scCheck['completed_at'])) : '-'; ?>
              (<?php echo htmlspecialchars($scCheck['completed_by_name'] ?? '-'); ?>)
            <?php endif; ?>
          </div>
        </div>
        <div class="sc-no-print d-flex flex-wrap align-items-center" style="gap: 6px;">
          <?php if ($scEditable): ?>
          <button type="button" class="btn btn-primary sc-add-btn font-weight-bold shadow-sm" id="scBtnSave" onclick="scSave()">
            <i class="fas fa-save mr-1"></i> ບັນທຶກ <span class="badge badge-light ml-1" id="scUnsavedBadge" style="display: none;">0</span>
          </button>
          <?php endif; ?>

          <?php if ($scIsDraft && $scCanCount): ?>
          <span class="sc-btn-divider"></span>
          <button type="button" class="btn btn-warning font-weight-bold shadow-sm" onclick="scSubmitApproval()">
            <i class="fas fa-paper-plane mr-1"></i> ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ
          </button>
          <?php endif; ?>

          <?php if (($scIsDraft || $scIsPending) && $scCanApprove): ?>
          <span class="sc-btn-divider"></span>
          <button type="button" class="btn btn-info font-weight-bold shadow-sm" onclick="scApproveAdjustment()">
            <i class="fas fa-user-check mr-1"></i> ອະນຸຍາດໃຫ້ປັບສະຕັອກ
          </button>
          <?php if ($scIsPending): ?>
          <button type="button" class="btn btn-outline-danger font-weight-bold" onclick="scRejectAdjustment()">
            <i class="fas fa-undo mr-1"></i> ສົ່ງກັບໃຫ້ນັບໃໝ່
          </button>
          <?php endif; ?>
          <?php endif; ?>

          <?php if ($scIsApproved && ($scCanCount || $scCanConfirm)): ?>
          <span class="sc-btn-divider"></span>
          <button type="button" class="btn btn-success font-weight-bold shadow-sm" onclick="scComplete()">
            <i class="fas fa-check-double mr-1"></i> ປັບສະຕັອກຕົວຈິງ ກັບ ລະບົບໃຫ້ຕົງກັນ
          </button>
          <?php if ($scCanApprove): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" onclick="scRejectAdjustment()" title="ຍົກເລີກການອະນຸມັດ ເພື່ອກັບໄປນັບໃໝ່">
            <i class="fas fa-undo"></i>
          </button>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="content pb-5">
    <div class="container-fluid">

<?php require __DIR__ . '/tabs.php'; /* ເມນູລຽນກັນ: ໃບເຊັກສະຕັອກ | ນັບສະຕັອກ | ປະຫວັດການນັບ */ ?>

      <?php if ($scIsPending): ?>
      <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px;">
        <div><i class="fas fa-user-clock mr-2"></i><b>ລໍຖ້າຫົວໜ້າກວດສອບ:</b> ໃບນີ້ສົ່ງໃຫ້ຫົວໜ້າກວດເບິ່ງລາຍການທີ່ບໍ່ຄົບ/ເກີນແລ້ວ — ຕ້ອງໄດ້ຮັບອະນຸຍາດຈາກຫົວໜ້າກ່ອນ ຈຶ່ງສາມາດກົດປັບສະຕັອກໄດ້.</div>
      </div>
      <?php elseif ($scIsApproved): ?>
      <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px;">
        <div><i class="fas fa-check-circle mr-2"></i><b>ຫົວໜ້າອະນຸຍາດແລ້ວ (ໂດຍ <?php echo htmlspecialchars($scCheck['approved_by_name'] ?? 'ຫົວໜ້າ'); ?>):</b> ກວດເບິ່ງລາຍການບໍ່ຄົບ/ເກີນ ແລ້ວກົດປຸ່ມ <b>"ປັບສະຕັອກຕົວຈິງ ກັບ ລະບົບໃຫ້ຕົງກັນ"</b> ດ້ານເທິງໄດ້ເລີຍ.</div>
      </div>
      <?php endif; ?>

      <!-- ບັດສະຫຼຸບ (ແບບດຽວກັບ KPI ໃນໜ້າລາຍງານ) — ຕົວເລກຕື່ມໂດຍ count_js.php -->
      <div class="sc-kpi-grid mb-3">
        <div class="sc-kpi" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
          <div><div class="sc-kpi-title">ສິນຄ້າທີ່ຕ້ອງນັບ</div><div class="sc-kpi-val" id="scStatTotal">0</div><div class="sc-kpi-sub">ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-boxes"></i></div>
        </div>
        <div class="sc-kpi" style="background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);">
          <div><div class="sc-kpi-title">ນັບແລ້ວ</div><div class="sc-kpi-val" id="scStatCounted">0</div><div class="sc-kpi-sub" id="scStatUncounted">ຍັງບໍ່ນັບ 0</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-clipboard-check"></i></div>
        </div>
        <?php if (!$scShowResults): ?>
        <!-- ພະນັກງານນັບ: ບໍ່ສະແດງຜົນ ຄົບ/ບໍ່ຄົບ/ເກີນ ແລະ ສ່ວນຕ່າງ ລະຫວ່າງນັບ -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #64748b 0%, #334155 100%);">
          <div><div class="sc-kpi-title">ຍັງບໍ່ນັບ</div><div class="sc-kpi-val" id="scStatUncountedBig">0</div><div class="sc-kpi-sub">ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="far fa-square"></i></div>
        </div>
        <?php else: ?>
        <div class="sc-kpi" style="background: linear-gradient(135deg, #64748b 0%, #334155 100%);">
          <div><div class="sc-kpi-title">ຄົບ (ຕົງກັນ)</div><div class="sc-kpi-val" id="scStatMatch">0</div><div class="sc-kpi-sub">ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-equals"></i></div>
        </div>
        <div class="sc-kpi" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
          <div><div class="sc-kpi-title">ບໍ່ຄົບ (ຈຳນວນ)</div><div class="sc-kpi-val" id="scStatShort">0</div><div class="sc-kpi-sub" id="scStatShortQty">0 ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-arrow-down"></i></div>
        </div>
        <div class="sc-kpi" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);">
          <div><div class="sc-kpi-title">ເກີນ (ຈຳນວນ)</div><div class="sc-kpi-val" id="scStatOver">0</div><div class="sc-kpi-sub" id="scStatOverQty">0 ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-arrow-up"></i></div>
        </div>
        <div class="sc-kpi" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);">
          <div><div class="sc-kpi-title">ສ່ວນຕ່າງລວມ</div><div class="sc-kpi-val" id="scStatValue">0</div><div class="sc-kpi-sub">ຈຳນວນ (ເກີນ − ບໍ່ຄົບ)</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-balance-scale"></i></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="card sc-card">
        <div class="card-header bg-white py-3 border-0 sc-no-print">
          <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
            <div class="input-group sc-scan-group">
              <div class="input-group-prepend"><span class="input-group-text bg-primary"><i class="fas fa-barcode"></i></span></div>
              <input type="text" id="scScanInput" class="form-control" autocomplete="off"
                     placeholder="<?php echo $scEditable ? 'ສະແກນບາໂຄ້ດ ເພື່ອປ້ອນຈຳນວນຕົວຈິງ ຫຼື ພິມຊື່ສິນຄ້າເພື່ອຄົ້ນຫາ' : 'ພິມຊື່ ຫຼື ບາໂຄ້ດເພື່ອຄົ້ນຫາ'; ?>">
            </div>
            <!-- ປຸ່ມກັ່ນຕອງ: ສີແຕ່ລະປຸ່ມກົງກັບບັດສະຫຼຸບ (CSS: .sc-filter .btn[data-filter]) -->
            <div class="sc-filter" role="group">
              <!-- ໃບກຳລັງນັບ: ເລີ່ມຕົ້ນສະແດງແຕ່ "ຍັງບໍ່ນັບ" — ຖ້າສົ່ງກວດ/ອະນຸມັດແລ້ວ ເລີ່ມຕົ້ນສະແດງ "ທັງໝົດ" ເພື່ອກວດລາຍການບໍ່ຄົບ/ເກີນ -->
              <button type="button" class="btn <?php echo $scIsDraft ? '' : 'active'; ?>" data-filter="all"><i class="fas fa-list mr-1"></i>ທັງໝົດ</button>
              <?php if (!$scIsDone): ?>
              <button type="button" class="btn <?php echo $scIsDraft ? 'active' : ''; ?>" data-filter="uncounted"><i class="far fa-square mr-1"></i>ຍັງບໍ່ນັບ</button>
              <button type="button" class="btn" data-filter="counted"><i class="fas fa-clipboard-check mr-1"></i>ນັບແລ້ວ</button>
              <?php endif; ?>
              <?php if ($scShowResults): ?>
              <button type="button" class="btn" data-filter="match"><i class="fas fa-equals mr-1"></i>ຄົບ</button>
              <button type="button" class="btn" data-filter="short"><i class="fas fa-arrow-down mr-1"></i>ບໍ່ຄົບ</button>
              <button type="button" class="btn" data-filter="over"><i class="fas fa-arrow-up mr-1"></i>ເກີນ</button>
              <?php endif; ?>
            </div>
            <span class="small text-muted ml-auto" id="scSaveStatus"></span>
<?php require __DIR__ . '/export_buttons.php'; ?>
          </div>
          <?php if (!empty($scCheck['note']) || $scEditable): ?>
          <div class="mt-2">
            <?php if ($scEditable): ?>
              <input type="text" id="scNote" class="form-control sc-note-input" maxlength="255" placeholder="ໝາຍເຫດຂອງໃບນີ້ (ບໍ່ບັງຄັບ)" value="<?php echo htmlspecialchars($scCheck['note'] ?? ''); ?>">
            <?php else: ?>
              <div class="small"><b>ໝາຍເຫດ:</b> <?php echo htmlspecialchars($scCheck['note']); ?></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive sc-table-wrap">
            <table class="table table-hover mb-0 sc-table sc-count-table">
              <thead class="bg-light text-secondary">
                <tr>
                  <th class="text-center" style="width: 50px;">#</th>
                  <th style="width: 130px;">ບາໂຄ້ດ</th>
                  <th>ຊື່ສິນຄ້າ</th>
                  <th style="width: 80px;">ຫົວໜ່ວຍ</th>
                  <?php if ($scShowSystem): ?><th class="text-right" style="width: 95px;">ໃນລະບົບ</th><?php endif; ?>
                  <th class="text-center" style="width: 130px;"><?php echo $scEditable ? 'ນັບເພີ່ມ / ຍອດລວມ' : 'ຈຳນວນຕົວຈິງ'; ?></th>
                  <?php if ($scShowResults): ?>
                  <th class="text-center" style="width: 110px;">ຜົນການນັບ</th>
                  <th class="text-right" style="width: 120px;">ສ່ວນຕ່າງ</th>
                  <?php endif; ?>
                  <th style="width: 150px;">ນັບເມື່ອ / ຜູ້ນັບ</th>
                  <th style="width: 170px;">ໝາຍເຫດ</th>
                </tr>
              </thead>
              <tbody id="scItemsBody">
                <?php if (empty($scItems)): ?>
                  <tr><td colspan="<?php echo $scColCount; ?>" class="text-center py-5 text-muted"><?php echo $scIsDraft ? 'ບໍ່ມີສິນຄ້າທີ່ຕ້ອງນັບໃນສາຂານີ້' : 'ໃບນີ້ບໍ່ມີລາຍການທີ່ນັບ'; ?></td></tr>
                <?php endif; ?>
                <?php foreach ($scItems as $i => $it):
                  $counted = $it['counted_qty'];
                ?>
                  <tr class="sc-row"
                      data-id="<?php echo (int)$it['id']; ?>"
                      data-pid="<?php echo htmlspecialchars((string)$it['product_id']); ?>"
                      data-barcode="<?php echo htmlspecialchars(strtolower((string)$it['barcode'])); ?>"
                      data-name="<?php echo htmlspecialchars(mb_strtolower((string)$it['product_name'])); ?>"
                      <?php /* ພະນັກງານນັບ: ບໍ່ສົ່ງຈຳນວນໃນລະບົບ ມາໃນ HTML ເລີຍ (ເບິ່ງ source ກໍບໍ່ເຫັນ) */ ?>
                      data-system="<?php echo $scShowResults ? (int)$it['system_qty'] : 0; ?>"
                      data-saved="<?php echo $counted === null ? '' : (int)$counted; ?>">
                    <td class="text-center text-muted"><?php echo $i + 1; ?></td>
                    <td class="small text-secondary sc-c-barcode"><?php echo htmlspecialchars($it['barcode'] ?: '-'); ?></td>
                    <td class="font-weight-bold text-dark sc-c-name"><?php echo htmlspecialchars($it['product_name']); ?></td>
                    <td class="text-muted sc-c-unit"><?php echo htmlspecialchars($it['unit'] ?: '-'); ?></td>
                    <?php if ($scShowSystem): ?><td class="text-right font-weight-bold"><?php echo number_format((int)$it['system_qty']); ?></td><?php endif; ?>
                    <td class="text-center">
                      <?php if ($scEditable): ?>
                        <!-- ນັບແບບບວກເພີ່ມ: ຊ່ອງ = ຈຳນວນທີ່ນັບໄດ້ຮອບນີ້, ບັນທຶກແລ້ວບວກໃສ່ຍອດລວມ (ນັບຫຼາຍມື້/ຫຼາຍບ່ອນ ລວມກັນ).
                             ✏️ = ແກ້ຍອດລວມໂດຍກົງ. type="text" + inputmode="numeric": ບໍ່ມີປຸ່ມລູກສອນ; JS ກັ້ນໃຫ້ປ້ອນໄດ້ແຕ່ 0-9 -->
                        <div class="sc-count-cell">
                          <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="7" autocomplete="off" class="form-control form-control-sm text-center sc-counted"
                                 value="" placeholder="+0" title="ຈຳນວນທີ່ນັບໄດ້ຮອບນີ້ (ບວກເພີ່ມໃສ່ຍອດລວມ)">
                          <div class="sc-total-line">
                            <span class="sc-total"></span>
                            <button type="button" class="btn btn-link p-0 sc-edit-total" title="ແກ້ຍອດລວມ"><i class="fas fa-pen"></i></button>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="font-weight-bold sc-counted-text" data-value="<?php echo $counted === null ? '' : (int)$counted; ?>"><?php echo $counted === null ? '-' : number_format((int)$counted); ?></span>
                      <?php endif; ?>
                    </td>
                    <?php if ($scShowResults): ?>
                    <td class="text-center sc-diff">-</td>
                    <td class="text-right sc-diff-value">-</td>
                    <?php endif; ?>
                    <td class="small sc-counted-by">
                      <?php if ($counted !== null && !empty($it['counted_at'])): ?>
                        <i class="fas fa-check-circle text-success mr-1"></i><?php echo date('d/m H:i', strtotime($it['counted_at'])); ?>
                        <div class="text-muted"><?php echo htmlspecialchars($it['counted_by_name'] ?? '-'); ?></div>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($scEditable): ?>
                        <input type="text" maxlength="255" class="form-control form-control-sm sc-note" value="<?php echo htmlspecialchars($it['note'] ?? ''); ?>">
                      <?php else: ?>
                        <span class="small text-muted"><?php echo htmlspecialchars($it['note'] ?? ''); ?></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="scNoMatchRow" style="display: none;"><td colspan="<?php echo $scColCount; ?>" class="text-center py-4 text-muted">ບໍ່ພົບລາຍການທີ່ກົງກັບການຄົ້ນຫາ</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- ແບ່ງໜ້າຕາຕະລາງນັບ (ຝັ່ງ browser — ທຸກແຖວຢູ່ໃນໜ້າ ເພື່ອສະແກນ/ບັນທຶກໄດ້ຄົບ) — count_js.php -->
        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center sc-no-print" style="row-gap: 10px;">
          <small class="text-muted font-weight-bold d-flex align-items-center flex-wrap" style="gap: 8px;">
            <span class="d-inline-flex align-items-center">ສະແດງ
              <select class="custom-select custom-select-sm sc-per-page mx-1" id="scPerPage">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">ທັງໝົດ</option>
              </select>ລາຍການ
            </span>
            <span id="scPagerInfo"></span>
          </small>
          <ul class="pagination sc-pagination mb-0 ml-md-auto" id="scPager"></ul>
        </div>
<?php $scExpPlace = 'bottom'; require __DIR__ . '/export_buttons.php'; /* ໂທລະສັບ: ປຸ່ມລຸ່ມສຸດ */ ?>
      </div>

      <?php if ($scEditable): ?>
      <div class="small text-muted mt-2 sc-no-print">
        <i class="fas fa-info-circle mr-1"></i>
        <b>ນັບຫຼາຍມື້ / ຫຼາຍບ່ອນ:</b> ປ້ອນແຕ່ຈຳນວນທີ່ນັບໄດ້ <b>ຮອບນີ້</b> — ກົດບັນທຶກແລ້ວລະບົບບວກເພີ່ມໃສ່ຍອດລວມໃຫ້ເອງ (ນັບຜິດ ກົດ <i class="fas fa-pen"></i> ແກ້ຍອດລວມ).<br>
        <b>ຂັ້ນຕອນການເຊັກສະຕັອກ:</b> 1) ພະນັກງານສະແກນ/ປ້ອນຈຳນວນຕົວຈິງ ແລ້ວກົດ <b>ບັນທຶກ</b> ຫຼື <b>ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ</b> →
        2) ຫົວໜ້າກວດເບິ່ງລາຍການທີ່ບໍ່ຄົບ/ເກີນ ແລ້ວກົດ <b>ອະນຸຍາດໃຫ້ປັບສະຕັອກ</b> →
        3) ກົດປຸ່ມ <b>ປັບສະຕັອກຕົວຈິງ ກັບ ລະບົບໃຫ້ຕົງກັນ</b> ເພື່ອປັບສະຕັອກໃນລະບົບໃຫ້ກົງກັບຕົວຈິງ.
      </div>
      <?php endif; ?>

    </div>
  </section>
