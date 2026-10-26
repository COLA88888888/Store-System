<?php
/*
 * ສ່ວນ: ບັອກເລືອກກະ ໃນຟອມກັ່ນຕອງຂອງໜ້າລາຍງານ — ໃຊ້ຮ່ວມກັນທຸກໜ້າລາຍງານການຂາຍ.
 * ໃຊ້ $shiftFilter ຈາກ reportShiftFilter() (config/helpers/report_shift_filter.php).
 * ບໍ່ມີກະ (ສາຂາບໍ່ໃຊ້ກະ / ເລືອກທຸກສາຂາ) → ບໍ່ສະແດງຫຍັງເລີຍ.
 * $shiftSelBoxStyle (ບໍ່ບັງຄັບ): style ຂອງກ່ອງ ໃຫ້ກົງກັບຊ່ອງອື່ນໃນຟອມຂອງໜ້ານັ້ນ.
 */
if (!empty($shiftFilter['options'])):
?>
      <div style="<?php echo htmlspecialchars($shiftSelBoxStyle ?? 'flex: 1 1 0; min-width: 140px;'); ?>">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-user-clock text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.shift_label', 'ກະ:')); ?>
        </label>
        <select name="shift_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #0891b2; color: #0e7490; background: #ecfeff; width: 100%;">
          <option value="0"><?php echo htmlspecialchars(t('reports.all_shifts_opt', '-- ທຸກກະ --')); ?></option>
          <?php foreach ($shiftFilter['options'] as $__sh): ?>
            <option value="<?php echo (int)$__sh['id']; ?>" <?php echo $shiftFilter['id'] === $__sh['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($__sh['shift_name'] . ($__sh['start'] !== '' ? ' (' . $__sh['start'] . ' - ' . $__sh['end'] . ')' : '')); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
<?php endif; ?>
