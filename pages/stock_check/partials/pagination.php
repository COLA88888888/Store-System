<?php
/*
 * ສ່ວນ: ແບ່ງໜ້າ (ວົງມົນ ແບບດຽວກັບໜ້າລາຍງານ .report-pagination)
 * ລິ້ງເກັບຕົວກອງເດີມໄວ້ທັງໝົດ (view/from/to/q), ປ່ຽນແຕ່ page.
 * ຖືກ require ຈາກ list_view.php ແລະ history_view.php (ໃຊ້ $scPage, $scTotalPages, $scTotalRows, $scPageOffset, $scPerPage).
 */
$scPgLink = function ($p) {
    $q = $_GET;
    unset($q['id']);
    $q['page'] = $p;
    return 'stock_check.php?' . http_build_query($q);
};
?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center sc-no-print" style="row-gap: 10px;">
          <small class="text-muted font-weight-bold d-flex align-items-center flex-wrap" style="gap: 8px;">
            <!-- ບັອກເລືອກຈຳນວນແຖວຕໍ່ໜ້າ -->
            <span class="d-inline-flex align-items-center"><?php echo $sc('sc.s074'); ?>
              <select class="custom-select custom-select-sm sc-per-page mx-1" onchange="window.location.href = this.value;">
                <?php foreach (array_merge($scPerPageOptions, ['all']) as $opt):
                  $q = $_GET; unset($q['id'], $q['page']); $q['per_page'] = $opt; ?>
                  <option value="stock_check.php?<?php echo htmlspecialchars(http_build_query($q)); ?>" <?php echo (string)$opt === (string)$scPerPageParam ? 'selected' : ''; ?>><?php echo $opt === 'all' ? t('sc.s047') : $opt; ?></option>
                <?php endforeach; ?>
              </select><?php echo $sc('sc.s075'); ?>
            </span>
            <?php if ($scTotalRows > 0): ?>
              <?php echo $sc('sc.s074'); ?> <?php echo number_format($scPageOffset + 1); ?> - <?php echo number_format(min($scPageOffset + $scPerPage, $scTotalRows)); ?>
              <?php echo $sc('sc.s088'); ?> <?php echo number_format($scTotalRows); ?> <?php echo $sc('sc.s075'); ?>
            <?php else: ?>
              <?php echo $sc('sc.s089'); ?>
            <?php endif; ?>
          </small>
          <!-- ສະແດງສະເໝີ (ເຖິງວ່າຈະມີໜ້າດຽວ) -->
          <ul class="pagination sc-pagination mb-0 ml-md-auto">
            <li class="page-item <?php echo $scPage <= 1 ? 'disabled' : ''; ?>">
              <a class="page-link" href="<?php echo htmlspecialchars($scPgLink(max(1, $scPage - 1))); ?>"><i class="fas fa-chevron-left"></i></a>
            </li>
            <?php
              $pgStart = max(1, $scPage - 2);
              $pgEnd = min($scTotalPages, $scPage + 2);
              if ($pgStart > 1) {
                  echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($scPgLink(1)) . '">1</a></li>';
                  if ($pgStart > 2) echo '<li class="page-item disabled"><span class="page-link border-0">…</span></li>';
              }
              for ($p = $pgStart; $p <= $pgEnd; $p++) {
                  echo '<li class="page-item ' . ($p === $scPage ? 'active' : '') . '"><a class="page-link" href="' . htmlspecialchars($scPgLink($p)) . '">' . $p . '</a></li>';
              }
              if ($pgEnd < $scTotalPages) {
                  if ($pgEnd < $scTotalPages - 1) echo '<li class="page-item disabled"><span class="page-link border-0">…</span></li>';
                  echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($scPgLink($scTotalPages)) . '">' . $scTotalPages . '</a></li>';
              }
            ?>
            <li class="page-item <?php echo $scPage >= $scTotalPages ? 'disabled' : ''; ?>">
              <a class="page-link" href="<?php echo htmlspecialchars($scPgLink(min($scTotalPages, $scPage + 1))); ?>"><i class="fas fa-chevron-right"></i></a>
            </li>
          </ul>
        </div>
