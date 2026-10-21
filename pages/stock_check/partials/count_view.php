<?php
/*
 * ສ່ວນ: ໜ້ານັບສິນຄ້າຂອງໃບເຊັກສະຕັອກ
 * ຫົວໃບ, ບັດສະຫຼຸບ (ທັງໝົດ/ຄົບ/ບໍ່ພໍ/ເກີນ/ຍັງບໍ່ນັບ/ສ່ວນຕ່າງ), ຊ່ອງສະແກນ-ຄົ້ນຫາ,
 * ປຸ່ມກັ່ນຕອງ ແລະ ຕາຕະລາງ: ໃນລະບົບ ທຽບ ຕົວຈິງ → ຄົບ / ບໍ່ພໍ / ເກີນ (ສ່ວນຕ່າງ).
 * ປັບປຸງໃຫ້ງ່າຍດາຍ, ບໍ່ຊັບຊ້ອນ, ເຫັນຜົນທັນທີຂະນະນັບ.
 */
$scStatus     = $scCheck['status'];
$scIsDraft    = ($scStatus === 'draft');
$scIsPending  = ($scStatus === 'pending');
$scIsApproved = ($scStatus === 'approved');
$scIsDone     = ($scStatus === 'completed');

// ສິດແກ້ໄຂຕົວເລກຕົວຈິງ:
// - ໃນ draft: ພະນັກງານນັບ / ຫົວໜ້າ ປ້ອນໄດ້
// - ໃນ pending / approved: ຫົວໜ້າ / ຜູ້ມີສິດອະນຸມັດ ສາມາດແກ້ໄຂປັບປ້ອນຕົວເລກຕົວຈິງໄດ້
$scEditable   = ($scIsDraft && $scCanCount) || (($scIsPending || $scIsApproved) && $scCanApprove);

// ສະແດງຍອດໃນລະບົບ ແລະ ສ່ວນຕ່າງ:
// - ສະເພາະຫົວໜ້າ / ຜູ້ບໍລິຫານ ເທົ່ານັ້ນ (ພະນັກງານບໍ່ໃຫ້ໂຊສະຕັອກໃນລະບົບ)
$scShowSystem = $scCanApprove;
$scShowDiff   = $scCanApprove;
$scShowResults = $scCanApprove;
$scColCount   = 7 + ($scShowSystem ? 1 : 0) + ($scShowDiff ? 1 : 0);
$scItemTotal  = count($scItems);
if ($scIsDone) {
    $scItems = array_values(array_filter($scItems, function ($it) { return $it['counted_qty'] !== null; }));
}
$scZones = array_values(array_unique(array_filter(array_map(function ($it) {
    return trim($it['zone'] ?? '');
}, $scItems))));
sort($scZones);
$hasUnassignedZone = false;
foreach ($scItems as $it) {
    if (trim((string)($it['zone'] ?? '')) === '') {
        $hasUnassignedZone = true;
        break;
    }
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
              <span class="badge badge-warning align-middle ml-1 px-2 py-1"><i class="fas fa-clock mr-1"></i>ລໍຖ້າຫົວໜ້າກວດສອບ</span>
            <?php elseif ($scIsApproved): ?>
              <span class="badge badge-info align-middle ml-1 px-2 py-1"><i class="fas fa-user-check mr-1"></i>ຫົວໜ້າກວດສອບແລ້ວ</span>
            <?php else: ?>
              <span class="badge sc-badge-done align-middle ml-1"><i class="fas fa-check mr-1"></i>ປັບສະຕັອກແລ້ວ</span>
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

          <?php if ($scIsPending && $scCanApprove): ?>
          <span class="sc-btn-divider"></span>
          <button type="button" class="btn btn-outline-danger font-weight-bold" onclick="scRejectAdjustment()">
            <i class="fas fa-undo mr-1"></i> ສົ່ງກັບໃຫ້ນັບໃໝ່
          </button>
          <button type="button" class="btn btn-success font-weight-bold shadow-sm" onclick="scComplete()">
            <i class="fas fa-check-double mr-1"></i> ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ
          </button>
          <?php endif; ?>

          <?php if ($scIsApproved && ($scCanCount || $scCanConfirm)): ?>
          <span class="sc-btn-divider"></span>
          <button type="button" class="btn btn-success font-weight-bold shadow-sm" onclick="scComplete()">
            <i class="fas fa-check-double mr-1"></i> ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="content pb-5">
    <div class="container-fluid">

<?php require __DIR__ . '/tabs.php'; /* ເມນູລຽນກັນ: ໃບເຊັກສະຕັອກ | ນັບສະຕັອກ | ປະຫວັດການນັບ */ ?>

      <?php if ($scIsDraft): ?>
        <div class="alert <?php echo $scCanApprove ? 'alert-secondary' : 'alert-primary'; ?> d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px;">
          <div><i class="fas <?php echo $scCanApprove ? 'fa-clipboard-list' : 'fa-info-circle'; ?> mr-2"></i><b>ຂັ້ນຕອນນັບສິນຄ້າ:</b> ປ້ອນຈຳນວນຕົວຈິງທີ່ນັບໄດ້ໃນຮ້ານ ແລ້ວກົດ <b>"ບັນທຶກ"</b>. ເມື່ອນັບແລ້ວ ໃຫ້ກົດ <b>"ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ"</b> ເພື່ອກວດເບິ່ງລາຍການທີ່ບໍ່ຕົງ/ຄົບ/ເກີນ ກ່ອນປັບສະຕັອກ.</div>
        </div>
      <?php elseif ($scIsPending): ?>
        <?php if ($scCanApprove): ?>
          <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px; border-left: 5px solid #d97706;">
            <div><i class="fas fa-user-check fa-lg mr-2 text-warning"></i><b>ຫົວໜ້າກວດສອບ:</b> ກະລຸນາກວດເບິ່ງລາຍການທີ່ບໍ່ຕົງ (ບໍ່ພໍ / ເກີນ / ຄົບ). ຫົວໜ້າສາມາດແກ້ໄຂປັບປ້ອນຕົວເລກຕົວຈິງໄດ້, ສົ່ງກັບໃຫ້ນັບໃໝ່, ຫຼື ກົດປຸ່ມ <b>"ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ"</b> ດ້ານເທິງ.</div>
          </div>
        <?php else: ?>
          <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px;">
            <div><i class="fas fa-clock fa-lg mr-2 text-warning"></i><b>ລໍຖ້າຫົວໜ້າກວດສອບ:</b> ໃບເຊັກສະຕັອກນີ້ສົ່ງໃຫ້ຫົວໜ້າກວດສອບແລ້ວ — ກະລຸນາລໍຖ້າຫົວໜ້າກວດສອບລາຍການທີ່ບໍ່ຕົງ/ຄົບ/ເກີນ ແລະ ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ.</div>
          </div>
        <?php endif; ?>
      <?php elseif ($scIsApproved): ?>
        <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px; border-left: 5px solid #0284c7;">
          <div><i class="fas fa-check-circle fa-lg mr-2 text-info"></i><b>ຫົວໜ້າກວດສອບ ແລະ ອະນຸມັດແລ້ວ:</b> ກວດເບິ່ງລາຍການບໍ່ພໍ/ເກີນ ແລ້ວກົດປຸ່ມ <b>"ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ"</b> ດ້ານເທິງໄດ້ເລີຍ.</div>
        </div>
      <?php elseif ($scIsDone): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap mb-3 sc-no-print" style="border-radius:10px; border-left: 5px solid #16a34a;">
          <div><i class="fas fa-check-double fa-lg mr-2 text-success"></i><b>ປັບສະຕັອກຮຽບຮ້ອຍແລ້ວ:</b> ລະບົບໄດ້ອັບເດດຍອດສິນຄ້າໃນລະບົບໃຫ້ຕົງກັບ <b>"ຈຳນວນຕົວຈິງ"</b> ຮຽບຮ້ອຍແລ້ວ. ຕາຕະລາງດ້ານລຸ່ມສະແດງ <b>ປະຫວັດການປັບຍອດ</b> (ຍອດກ່ອນປັບ ທຽບກັບ ຕົວຈິງ ແລະ ສ່ວນຕ່າງທີ່ປັບເຂົ້າ/ອອກ).</div>
        </div>
      <?php endif; ?>

      <!-- ບັດສະຫຼຸບ -->
      <div class="sc-kpi-grid mb-3">
        <!-- 1. ທັງໝົດ -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
          <div><div class="sc-kpi-title">ສິນຄ້າທັງໝົດ</div><div class="sc-kpi-val" id="scStatTotal">0</div><div class="sc-kpi-sub" id="scStatCountedSummary">ນັບແລ້ວ 0</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-boxes"></i></div>
        </div>
        <?php if (!$scShowDiff): ?>
        <!-- ນັບແລ້ວ (ສຳລັບພະນັກງານ ບໍ່ໃຫ້ໂຊ ຄົບ/ບໍ່ພໍ/ເກີນ) -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
          <div><div class="sc-kpi-title">ນັບແລ້ວ</div><div class="sc-kpi-val" id="scStatCountedOnly">0</div><div class="sc-kpi-sub">ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <?php endif; ?>
        <?php if ($scShowDiff): ?>
        <!-- 2. ຄົບ (ຕົງກັນ) -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
          <div><div class="sc-kpi-title">ຄົບ (ຕົງກັນ)</div><div class="sc-kpi-val" id="scStatMatch">0</div><div class="sc-kpi-sub">ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <!-- 3. ບໍ່ພໍ (ຂາດ) -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
          <div><div class="sc-kpi-title">ບໍ່ພໍ (ຂາດ)</div><div class="sc-kpi-val" id="scStatShort">0</div><div class="sc-kpi-sub" id="scStatShortQty">0 ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-minus-circle"></i></div>
        </div>
        <!-- 4. ເກີນ -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
          <div><div class="sc-kpi-title">ເກີນ</div><div class="sc-kpi-val" id="scStatOver">0</div><div class="sc-kpi-sub" id="scStatOverQty">0 ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-plus-circle"></i></div>
        </div>
        <?php endif; ?>
        <!-- 5. ຍັງບໍ່ນັບ -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
          <div><div class="sc-kpi-title">ຍັງບໍ່ນັບ</div><div class="sc-kpi-val" id="scStatUncountedBig">0</div><div class="sc-kpi-sub">ລາຍການ</div></div>
          <div class="sc-kpi-icon"><i class="far fa-clock"></i></div>
        </div>
        <?php if ($scShowDiff): ?>
        <!-- 6. ສ່ວນຕ່າງລວມ -->
        <div class="sc-kpi" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
          <div><div class="sc-kpi-title">ສ່ວນຕ່າງລວມ</div><div class="sc-kpi-val" id="scStatValue">0</div><div class="sc-kpi-sub">ເກີນ − ບໍ່ພໍ</div></div>
          <div class="sc-kpi-icon"><i class="fas fa-balance-scale"></i></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="card sc-card">
        <div class="card-header bg-white py-3 border-0 sc-no-print">
          <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
            <div class="d-flex flex-wrap align-items-center" style="gap: 10px; flex: 1 1 auto;">
              <!-- ຊ່ອງສະແກນ / ຄົ້ນຫາ -->
              <div class="input-group sc-scan-group">
                <div class="input-group-prepend"><span class="input-group-text bg-primary"><i class="fas fa-barcode"></i></span></div>
                <input type="text" id="scScanInput" class="form-control" autocomplete="off"
                       value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                       placeholder="<?php echo $scEditable ? 'ສະແກນບາໂຄ້ດ ຫຼື ພິມຊື່, ໂຊນ...' : 'ພິມຊື່, ບາໂຄ້ດ, ໂຊນ...'; ?>">
              </div>

              <?php if (!empty($scZones) || $hasUnassignedZone): ?>
              <div class="sc-zone-wrap" style="width: 150px;">
                <select id="scZoneFilter" class="form-control" style="border-radius: 8px; font-weight: 600; font-size: 0.88rem; height: 38px;">
                  <option value="">-- ທຸກໂຊນ --</option>
                  <?php foreach ($scZones as $zn): ?>
                    <option value="<?php echo htmlspecialchars(mb_strtolower($zn)); ?>"><?php echo htmlspecialchars('ໂຊນ ' . $zn); ?></option>
                  <?php endforeach; ?>
                  <?php if ($hasUnassignedZone): ?>
                    <option value="__unassigned__">ໂຊນ: ບໍ່ລະບຸ</option>
                  <?php endif; ?>
                </select>
              </div>
              <?php endif; ?>

              <!-- ປຸ່ມກັ່ນຕອງ -->
              <div class="sc-filter" role="group">
                <button type="button" class="btn" data-filter="counted" id="scBtnFilterCounted"><i class="fas fa-check-double mr-1"></i>ນັບແລ້ວ <span class="badge badge-light ml-1" id="scFilterCountedBadge">0</span></button>
                <button type="button" class="btn active" data-filter="all"><i class="fas fa-list mr-1"></i>ທັງໝົດ</button>
                <?php if ($scShowDiff): ?>
                <button type="button" class="btn" data-filter="mismatch" id="scBtnFilterMismatch"><i class="fas fa-exclamation-triangle mr-1"></i>ບໍ່ຕົງ <span class="badge badge-danger ml-1" id="scFilterMismatchBadge">0</span></button>
                <button type="button" class="btn" data-filter="match"><i class="fas fa-check mr-1"></i>ຄົບ</button>
                <button type="button" class="btn" data-filter="short"><i class="fas fa-arrow-down mr-1"></i>ບໍ່ພໍ</button>
                <button type="button" class="btn" data-filter="over"><i class="fas fa-arrow-up mr-1"></i>ເກີນ</button>
                <?php endif; ?>
                <button type="button" class="btn" data-filter="uncounted"><i class="far fa-clock mr-1"></i>ຍັງບໍ່ນັບ</button>
              </div>
            </div>

            <div class="d-flex align-items-center ml-auto" style="gap: 8px;">
              <span class="small font-weight-bold" id="scSaveStatus"></span>
<?php require __DIR__ . '/export_buttons.php'; ?>
            </div>
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
                  <th class="text-center" style="width: 45px;">#</th>
                  <th style="width: 120px;">ບາໂຄ້ດ</th>
                  <th>ຊື່ສິນຄ້າ</th>
                  <th class="text-center" style="width: 70px;">ຫົວໜ່ວຍ</th>
                  <?php if ($scShowSystem): ?>
                  <th class="text-right" style="width: 110px;"><?php echo $scIsDone ? 'ໃນລະບົບ (ກ່ອນປັບ)' : 'ໃນລະບົບ'; ?></th>
                  <?php endif; ?>
                  <th class="text-center" style="width: 135px;"><?php echo $scIsDone ? 'ຕົວຈິງ (ຍອດໃໝ່)' : 'ຈຳນວນຕົວຈິງ'; ?></th>
                  <th class="text-center" style="width: 105px;">ສະຖານະ</th>
                  <?php if ($scShowDiff): ?>
                  <th class="text-right" style="width: 95px;"><?php echo $scIsDone ? 'ປັບຍອດ' : 'ສ່ວນຕ່າງ'; ?></th>
                  <?php endif; ?>
                  <th style="width: 135px;">ນັບເມື່ອ / ຜູ້ນັບ</th>
                  <th style="width: 140px;">ໝາຍເຫດ</th>
                </tr>
              </thead>
              <tbody id="scItemsBody">
                <?php if (empty($scItems)): ?>
                  <tr><td colspan="<?php echo $scColCount; ?>" class="text-center py-5 text-muted"><?php echo $scIsDraft ? 'ບໍ່ມີສິນຄ້າທີ່ຕ້ອງນັບໃນສາຂານີ້' : 'ໃບນີ້ບໍ່ມີລາຍການທີ່ນັບ'; ?></td></tr>
                <?php endif; ?>
                <?php foreach ($scItems as $i => $it):
                  $counted = $it['counted_qty'];
                  $sysQty  = (int)$it['system_qty'];
                  $itZone  = trim((string)($it['zone'] ?? ''));
                  $itShelf = trim((string)($it['shelf'] ?? ''));
                  $hasLoc  = ($itZone !== '' || $itShelf !== '');
                ?>
                  <tr class="sc-row"
                      data-id="<?php echo (int)$it['id']; ?>"
                      data-pid="<?php echo htmlspecialchars((string)$it['product_id']); ?>"
                      data-barcode="<?php echo htmlspecialchars(strtolower((string)$it['barcode'])); ?>"
                      data-name="<?php echo htmlspecialchars(mb_strtolower((string)$it['product_name'])); ?>"
                      data-zone="<?php echo htmlspecialchars(mb_strtolower($itZone !== '' ? $itZone : 'ບໍ່ລະບຸ')); ?>"
                      data-shelf="<?php echo htmlspecialchars(mb_strtolower($itShelf !== '' ? $itShelf : 'ບໍ່ລະບຸ')); ?>"
                      data-raw-zone="<?php echo htmlspecialchars(mb_strtolower($itZone)); ?>"
                      data-system="<?php echo $scShowSystem ? $sysQty : ''; ?>"
                      data-saved="<?php echo $counted === null ? '' : (int)$counted; ?>">
                    <td class="text-center text-muted"><?php echo $i + 1; ?></td>
                    <td class="small text-secondary sc-c-barcode"><?php echo htmlspecialchars($it['barcode'] ?: '-'); ?></td>
                    <td class="font-weight-bold text-dark sc-c-name">
                      <div><?php echo htmlspecialchars($it['product_name']); ?></div>
                      <div class="mt-1">
                        <span class="badge badge-light border <?php echo $hasLoc ? 'text-secondary' : 'text-muted'; ?> font-weight-normal px-2 py-0.5" style="font-size: 0.75rem;">
                          <i class="fas fa-map-marker-alt <?php echo $hasLoc ? 'text-primary' : 'text-secondary'; ?> mr-1"></i><?php
                            $zd = $itZone !== '' ? ((mb_stripos($itZone, 'ໂຊນ') === 0) ? $itZone : ('ໂຊນ ' . $itZone)) : 'ໂຊນ: ບໍ່ລະບຸ';
                            $sd = $itShelf !== '' ? ((mb_stripos($itShelf, 'ຊັ້ນ') === 0) ? $itShelf : ('ຊັ້ນ ' . $itShelf)) : 'ຊັ້ນ: ບໍ່ລະບຸ';
                            echo htmlspecialchars($zd . ' · ' . $sd);
                          ?>
                        </span>
                      </div>
                    </td>
                    <td class="text-center text-muted sc-c-unit"><?php echo htmlspecialchars($it['unit'] ?: '-'); ?></td>
                    <?php if ($scShowSystem): ?>
                    <td class="text-right font-weight-bold text-secondary sc-system-qty">
                      <?php echo number_format($sysQty); ?>
                    </td>
                    <?php endif; ?>
                    <td class="text-center">
                      <?php if ($scEditable): ?>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="7" autocomplete="off"
                               class="form-control form-control-sm text-center font-weight-bold sc-counted"
                               value="<?php echo $counted !== null ? (int)$counted : ''; ?>"
                               placeholder="0"
                               title="ປ້ອນຈຳນວນຕົວຈິງທີ່ນັບໄດ້">
                      <?php else: ?>
                        <span class="font-weight-bold sc-counted-text" data-value="<?php echo $counted === null ? '' : (int)$counted; ?>"><?php echo $counted === null ? '-' : number_format((int)$counted); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center sc-diff">-</td>
                    <?php if ($scShowDiff): ?>
                    <td class="text-right sc-diff-value font-weight-bold">-</td>
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
                        <input type="text" maxlength="255" class="form-control form-control-sm sc-note" placeholder="ໝາຍເຫດ..." value="<?php echo htmlspecialchars($it['note'] ?? ''); ?>">
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
<?php $scExpPlace = 'bottom'; require __DIR__ . '/export_buttons.php'; ?>
      </div>

      <?php if ($scEditable): ?>
      <div class="small text-muted mt-2 sc-no-print">
        <i class="fas fa-lightbulb text-warning mr-1"></i>
        <b>ຄຳແນະນຳ:</b>
        <?php if ($scIsDraft): ?>
          ປ້ອນຈຳນວນຕົວຈິງທີ່ນັບໄດ້ໃນຮ້ານ ແລ້ວກົດ <b>ບັນທຶກ</b> (Ctrl+S). ເມື່ອນັບຄົບແລ້ວ ໃຫ້ກົດ <b>ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ</b> ເພື່ອກວດກາຄວາມຖືກຕ້ອງກ່ອນປັບສະຕັອກ.
        <?php elseif ($scIsPending && $scCanApprove): ?>
          ຫົວໜ້າສາມາດກວດສອບລາຍການທີ່ບໍ່ຕົງ/ຄົບ/ເກີນ. ທ່ານສາມາດແກ້ໄຂປັບປ້ອນຕົວເລກຕົວຈິງໄດ້ໂດຍກົງ, ສົ່ງກັບໃຫ້ນັບໃໝ່, ຫຼື ກົດ <b>ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ</b> ເພື່ອອັບເດດສະຕັອກໃນລະບົບ.
        <?php else: ?>
          ປ້ອນຈຳນວນຕົວຈິງໃນຊ່ອງ ຫຼື ໃຊ້ເຄື່ອງສະແກນບາໂຄ້ດ ແລ້ວກົດ <b>ບັນທຶກ</b> (Ctrl+S).
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </section>
