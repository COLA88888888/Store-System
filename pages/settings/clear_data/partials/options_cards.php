<?php
/**
 * ສ່ວນ: ປຸ່ມເລືອກດ່ວນ ແລະ ກຸ່ມສ່ວນຂໍ້ມູນທີ່ລຶບໄດ້
 * ມາຈາກ pages/settings/clear_data/index.php. ສ້າງຈາກ $clearSections (api/clear_data/sections.php) —
 * ເພີ່ມສ່ວນໃໝ່ຢູ່ນັ້ນ ແລ້ວເພີ່ມຊື່ໃນ lang/<locale>/clear_data.php, ໜ້ານີ້ສະແດງເອງ.
 */
$cdPresets = [
    'transactions'  => ['icon' => 'fa-receipt',     'class' => 'btn-outline-primary'],
    'keep_catalog'  => ['icon' => 'fa-broom',       'class' => 'btn-outline-warning'],
    'factory_reset' => ['icon' => 'fa-biohazard',   'class' => 'btn-outline-danger'],
    'none'          => ['icon' => 'fa-times-circle', 'class' => 'btn-outline-secondary'],
];
?>
<div class="card cd-card shadow-sm mb-3">
  <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
    <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">
      <i class="fas fa-magic text-primary mr-1"></i> <?php echo htmlspecialchars(t('clear_data.quick_select')); ?>
    </span>
    <div class="d-flex flex-wrap" style="gap: 8px;">
      <?php foreach ($cdPresets as $pKey => $p): ?>
        <button type="button" class="btn <?php echo $p['class']; ?> btn-sm font-weight-bold" style="border-radius: 6px; font-size: 0.8rem;" onclick="cdSetPreset('<?php echo $pKey; ?>')">
          <i class="fas <?php echo $p['icon']; ?> mr-1"></i> <?php echo htmlspecialchars(t('clear_data.preset_' . $pKey)); ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<form id="clearDataForm" onsubmit="return false;">
  <div class="row" style="row-gap: 16px;">
    <?php foreach ($clearSections as $gKey => $group): ?>
      <div class="col-xl-6 col-12">
        <div class="card cd-card shadow-sm h-100 mb-0" data-group="<?php echo $gKey; ?>">
          <div class="cd-group-head">
            <h6 class="cd-group-title" onclick="cdToggleGroup('<?php echo $gKey; ?>')">
              <i class="fas <?php echo $group['icon']; ?>" style="color: <?php echo $group['color']; ?>;"></i><?php echo htmlspecialchars(t('clear_data.group_' . $gKey)); ?>
            </h6>
            <button type="button" class="btn btn-light btn-sm cd-group-toggle" onclick="cdToggleGroup('<?php echo $gKey; ?>')">
              <?php echo htmlspecialchars(t('clear_data.select_group')); ?>
            </button>
          </div>
          <div class="cd-items">
            <?php foreach ($group['items'] as $iKey => $item): ?>
              <label class="cd-item" for="cd_<?php echo $iKey; ?>">
                <input type="checkbox" class="cd-chk" id="cd_<?php echo $iKey; ?>" name="scope[]" value="<?php echo $iKey; ?>" data-group="<?php echo $gKey; ?>" onchange="cdRefresh()">
                <span class="cd-item-icon" style="background: <?php echo $group['color']; ?>1a; color: <?php echo $group['color']; ?>;"><i class="fas <?php echo $item['icon']; ?>"></i></span>
                <span class="cd-item-body">
                  <span class="cd-item-top">
                    <span class="cd-item-label" id="cd_label_<?php echo $iKey; ?>"><?php echo htmlspecialchars(t('clear_data.item_' . $iKey)); ?></span>
                    <span class="cd-count" id="cd_count_<?php echo $iKey; ?>"><span class="cd-count-num">…</span> <span><?php echo htmlspecialchars(t('clear_data.unit_' . $item['unit'])); ?></span></span>
                  </span>
                  <span class="cd-item-desc d-block"><?php echo htmlspecialchars(t('clear_data.item_' . $iKey . '_desc')); ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ຂໍ້ມູນທີ່ບໍ່ຖືກລຶບເດັດຂາດ -->
  <div class="alert border-0 shadow-sm mt-4 mb-0 d-flex align-items-center" style="border-radius: 12px; background: #f0fdf4; border-left: 5px solid #22c55e !important; padding: 14px 18px;">
    <i class="fas fa-shield-alt text-success mr-3" style="font-size: 1.3rem;"></i>
    <div>
      <div class="font-weight-bold text-dark" style="font-size: 0.92rem;"><?php echo htmlspecialchars(t('clear_data.preserved_note_title')); ?></div>
      <div class="text-secondary small"><?php echo htmlspecialchars(t('clear_data.preserved_note_text')); ?></div>
    </div>
  </div>

  <div class="cd-sticky-bar d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
    <div class="small text-secondary">
      <?php echo htmlspecialchars(t('clear_data.selected_label')); ?> <span class="cd-selected-count" id="cdSelectedCount">0</span>
    </div>
    <div class="d-flex" style="gap: 10px;">
      <button type="button" class="btn btn-secondary font-weight-bold px-4" style="border-radius: 6px; height: 42px;" onclick="cdSetPreset('none')">
        <?php echo htmlspecialchars(t('clear_data.btn_cancel')); ?>
      </button>
      <button type="button" class="btn btn-danger font-weight-bold px-4 shadow-sm" id="btnSubmitClear" style="border-radius: 6px; height: 42px; background: #dc2626; border: none;" onclick="cdOpenConfirm()" disabled>
        <i class="fas fa-trash-alt mr-2"></i> <?php echo htmlspecialchars(t('clear_data.btn_submit')); ?>
      </button>
    </div>
  </div>
</form>
