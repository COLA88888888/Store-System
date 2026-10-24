<?php
/*
 * ສ່ວນ: ແຖບເມນູຂອງໂມດູນເຊັກສະຕັອກ
 *   ນັບສະຕັອກ (ນັບປັດຈຸບັນ) | ປະຫວັດໃບເຊັກສະຕັອກ | ປະຫວັດລາຍສິນຄ້າ
 */
$scIsChecksView  = (($_GET['view'] ?? '') === 'checks');
$scIsHistoryView = (($_GET['view'] ?? '') === 'history');
$scTabActive     = ($scCheck && !$scIsChecksView && !$scIsHistoryView) ? 'count' : ($scIsHistoryView ? 'history' : 'checks');
$scTabRange      = 'from=' . urlencode($scDateFrom ?? date('Y-m-01')) . '&to=' . urlencode($scDateTo ?? date('Y-m-d'));
$scCountNo       = $scCheck ? $scCheck['check_no'] : ($scOpenDraft['check_no'] ?? '');
?>
      <ul class="nav sc-tabs mb-3 sc-no-print">
        <li class="nav-item">
          <a class="nav-link <?php echo $scTabActive === 'count' ? 'active text-primary font-weight-bold' : ''; ?>" href="stock_check.php">
            <i class="fas fa-barcode mr-1"></i> <?php echo $sc('sc.s201'); ?>
            <?php if (!empty($scCountNo)): ?>
              <span class="sc-tab-no"><?php echo htmlspecialchars($scCountNo); ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $scTabActive === 'checks' ? 'active text-primary font-weight-bold' : ''; ?>" href="stock_check.php?view=checks&<?php echo $scTabRange; ?>">
            <i class="fas fa-clipboard-list mr-1"></i> <?php echo $sc('sc.s149'); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $scTabActive === 'history' ? 'active text-primary font-weight-bold' : ''; ?>" href="stock_check.php?view=history&<?php echo $scTabRange; ?>">
            <i class="fas fa-history mr-1"></i> <?php echo $sc('sc.s202'); ?>
          </a>
        </li>
      </ul>
