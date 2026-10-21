<?php
/*
 * ສ່ວນ: ແຖບເມນູຂອງໂມດູນເຊັກສະຕັອກ (ໃຊ້ຮ່ວມກັນທຸກໜ້າ)
 *   ໃບເຊັກສະຕັອກ | ນັບສະຕັອກ | ປະຫວັດການນັບ
 * "ນັບສະຕັອກ" = ໜ້ານັບຂອງໃບທີ່ເປີດຢູ່ (?id=N) — ຖ້າບໍ່ໄດ້ເປີດໃບໃດ ລິ້ງໄປໃບທີ່ກຳລັງນັບຄ້າງ;
 * ຖ້າບໍ່ມີໃບຄ້າງເລີຍ ແຖບນີ້ເປັນສີຈາງ (ກົດບໍ່ໄດ້).
 * ຖືກ require ຈາກ list_view.php ແລະ count_view.php (ໃຊ້ $scCheck, $scOpenDraft, $scView, $scDateFrom, $scDateTo).
 */
$scTabActive = $scCheck ? 'count' : ($scTabActive ?? $scView ?? 'checks');
$scTabRange  = 'from=' . urlencode($scDateFrom ?? date('Y-m-01')) . '&to=' . urlencode($scDateTo ?? date('Y-m-d'));
$scCountId   = $scCheck ? (int)$scCheck['id'] : ($scOpenDraft ? (int)$scOpenDraft['id'] : 0);
$scCountNo   = $scCheck ? $scCheck['check_no'] : ($scOpenDraft['check_no'] ?? '');
?>
      <ul class="nav sc-tabs mb-3 sc-no-print">
        <li class="nav-item">
          <a class="nav-link <?php echo $scTabActive === 'checks' ? 'active text-primary' : ''; ?>" href="stock_check.php?<?php echo $scTabRange; ?>">
            <i class="fas fa-clipboard-list mr-1"></i> ໃບເຊັກສະຕັອກ
          </a>
        </li>
        <li class="nav-item">
          <?php if ($scCountId): ?>
          <a class="nav-link <?php echo $scTabActive === 'count' ? 'active text-primary' : ''; ?>" href="stock_check.php?id=<?php echo $scCountId; ?>">
            <i class="fas fa-barcode mr-1"></i> ນັບສະຕັອກ
            <span class="sc-tab-no"><?php echo htmlspecialchars($scCountNo); ?></span>
          </a>
          <?php else: ?>
          <span class="nav-link disabled" title="ຍັງບໍ່ມີໃບທີ່ກຳລັງນັບ">
            <i class="fas fa-barcode mr-1"></i> ນັບສະຕັອກ
          </span>
          <?php endif; ?>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $scTabActive === 'history' ? 'active text-primary' : ''; ?>" href="stock_check.php?view=history&<?php echo $scTabRange; ?>">
            <i class="fas fa-history mr-1"></i> ປະຫວັດການນັບ
          </a>
        </li>
      </ul>
