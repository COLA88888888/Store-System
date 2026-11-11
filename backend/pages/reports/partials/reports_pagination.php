<!-- Table Card Footer: Circular Blue Pagination Controls (ຊິດຂວາທາງລຸ່ມ) -->
<div class="card-footer bg-white border-top py-3 px-3.5 d-flex align-items-center justify-content-end no-print" style="border-radius: 0 0 14px 14px;">
  <nav aria-label="Page navigation" class="ml-auto">
    <ul class="pagination report-pagination mb-0 justify-content-end">
      <?php
        $totalPagesDisplay = max(1, (int)$total_pages);
        $currPage = max(1, (int)$page);
        if ($per_page === 'all') {
            $totalPagesDisplay = 1;
            $currPage = 1;
        }
      ?>

      <!-- Previous Page -->
      <li class="page-item <?php echo $currPage <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo getReportPageUrl(max(1, $currPage - 1)); ?>" aria-label="Previous">
          <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
        </a>
      </li>

      <?php
        $range = 2;
        $startP = max(1, $currPage - $range);
        $endP   = min($totalPagesDisplay, $currPage + $range);

        if ($startP > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . getReportPageUrl(1) . '">1</a></li>';
            if ($startP > 2) {
                echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
            }
        }

        for ($p = $startP; $p <= $endP; $p++) {
            $activeClass = ($p == $currPage) ? 'active' : '';
            echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . getReportPageUrl($p) . '">' . $p . '</a></li>';
        }

        if ($endP < $totalPagesDisplay) {
            if ($endP < $totalPagesDisplay - 1) {
                echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="' . getReportPageUrl($totalPagesDisplay) . '">' . $totalPagesDisplay . '</a></li>';
        }
      ?>

      <!-- Next Page -->
      <li class="page-item <?php echo $currPage >= $totalPagesDisplay ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?php echo getReportPageUrl(min($totalPagesDisplay, $currPage + 1)); ?>" aria-label="Next">
          <i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i>
        </a>
      </li>
    </ul>
  </nav>
</div>
