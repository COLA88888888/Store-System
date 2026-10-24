<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຈັດການສິນຄ້າໝົດອາຍຸ: ກຳນົດວັນໝົດອາຍຸໃຫ້ສິນຄ້າ (ສະແກນບາໂຄ້ດ) ແລະ ລາຍການສິນຄ້າທີ່ຕິດຕາມ.
 *   - partials/ajax.php : AJAX ຂອງ popup ກຳນົດວັນໝົດອາຍຸ
 *   - partials/expiry_js.php : JS ຂອງໜ້າ
 */
// ============================================================
// pages/settings/expiry/index.php
// ຈັດການສິນຄ້າໝົດອາຍຸ (Product expiry)
// A product is tracked only when it has an expiry date (products.expiry_date). Tracked
// products raise a bell notification once getExpiryAlertDays() days (fixed at 5) or fewer
// are left. This page only sets / edits / clears dates; the status report (tabs, print) is
// pages/reports/expiry_report.php.
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

// Own switch in Permissions (ສິນຄ້າໝົດອາຍຸ). A user who only had "setup" before still passes —
// the session's 'expiry' flag falls back to setup — until the switch is turned off for them.
if (empty($_SESSION['user_id']) || !hasPermission('expiry')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';
// Each has its own switch in Permissions (admins pass inside hasPermission()):
//   add  = give a product an expiry date it doesn't have yet
//   edit = change an existing expiry date
//   del  = stop tracking (clear the date)
$canAddExpiry  = hasPermission('expiry', 'add');
$canEditExpiry = hasPermission('expiry', 'edit');
$canDelExpiry  = hasPermission('expiry', 'del');
$canSetExpiry  = $canAddExpiry || $canEditExpiry;   // may open the set-date modal at all
$activeStoreId = getActiveStoreId($pdo);

// AJAX ຂອງ popup ກຳນົດວັນໝົດອາຍຸ
require __DIR__ . '/partials/ajax.php';
// ===== Tracked products (management list; the status report lives in pages/reports/expiry_report.php) =====
$searchQ = trim($_GET['q'] ?? '');

$where = "p.store_id = ? AND p.expiry_date IS NOT NULL";
$params = [$activeStoreId];
if ($searchQ !== '') {
    $where .= " AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.product_id LIKE ?)";
    $like = '%' . $searchQ . '%';
    array_push($params, $like, $like, $like);
}

$exPerPage = 15;
$exPage = max(1, (int)($_GET['page'] ?? 1));
$exTotal = 0;
$expiryRows = [];
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM st_products p WHERE $where");
    $s->execute($params);
    $exTotal = (int)$s->fetchColumn();
    $exPage = min($exPage, max(1, (int)ceil($exTotal / $exPerPage)));

    $s = $pdo->prepare("
        SELECT p.product_id, p.product_name, p.barcode, p.unit, p.expiry_date,
               c.category_name, DATEDIFF(p.expiry_date, CURDATE()) AS days_left
        FROM st_products p
        LEFT JOIN st_categories c ON c.category_id = p.category_id
        WHERE $where
        ORDER BY p.expiry_date ASC, p.product_name ASC
        LIMIT $exPerPage OFFSET " . (($exPage - 1) * $exPerPage));
    $s->execute($params);
    $expiryRows = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}


// Keep the current filter when a form posts back to this page
$selfUrl = 'index.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : '');

function exPager($total, $page, $perPage) {
    $pages = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
    $from = $total === 0 ? 0 : ($perPage > 0 ? ($page - 1) * $perPage + 1 : 1);
    $to   = $perPage > 0 ? min($total, $page * $perPage) : $total;
    $html = '<div class="card-footer bg-white border-top py-3 px-3.5 d-flex flex-column flex-md-row justify-content-between align-items-center no-print" style="row-gap: 12px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">'
          . '<div class="d-flex align-items-center">'
          . '<small class="text-muted font-weight-bold" style="font-size: 0.85rem;">'
          . ($total > 0
              ? htmlspecialchars(sprintf(t('reports.showing_items', 'ສະແດງ %s ຫາ %s ຈາກທັງໝົດ %s ລາຍການ'), number_format($from), number_format($to), number_format($total)))
              : htmlspecialchars(t('reports.showing_empty', 'ສະແດງ 0 ຈາກ 0 ລາຍການ')))
          . '</small>'
          . '</div>';
    $url = function ($p) { return 'index.php?' . htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $p]))); };
    $html .= '<nav aria-label="Page navigation" class="ml-auto">'
          . '<ul class="pagination report-pagination mb-0">'
          . '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">'
          . '<a class="page-link" href="' . ($page <= 1 ? '#' : $url(max(1, $page - 1))) . '" aria-label="Previous"><i class="fas fa-chevron-left" style="font-size:0.76rem;"></i></a>'
          . '</li>';
    $start = max(1, $page - 2); $end = min($pages, $page + 2);
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url(1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link" style="border:none; background:transparent;">...</span></li>';
    }
    for ($p = $start; $p <= $end; $p++) {
        $html .= '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="' . ($p === $page ? '#' : $url($p)) . '">' . $p . '</a></li>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link" style="border:none; background:transparent;">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $url($pages) . '">' . $pages . '</a></li>';
    }
    $html .= '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '">'
          . '<a class="page-link" href="' . ($page >= $pages ? '#' : $url(min($pages, $page + 1))) . '" aria-label="Next"><i class="fas fa-chevron-right" style="font-size:0.76rem;"></i></a>'
          . '</li>'
          . '</ul>'
          . '</nav>'
          . '</div>';
    return $html;
}

$canViewReport = hasPermission('report') || hasPermission('expiry');

require_once __DIR__ . '/../../../layouts/header.php';
?>

<?php echo themeCssLink('settings-expiry.css'); /* CSS ຍ້າຍໄປ themes/settings-expiry.css */ ?>

<div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="row align-items-center mb-3 no-print">
    <div class="col-sm-6">
      <h5 class="m-0 font-weight-bold text-dark" style="font-size: 18px;">
        <i class="fas fa-hourglass-half text-danger mr-2"></i><?php echo htmlspecialchars(t('expiry.page_title', 'ຈັດການສິນຄ້າໝົດອາຍຸ')); ?>
      </h5>
      <small class="text-muted"><?php echo htmlspecialchars(t('expiry.page_subtitle', 'ສິນຄ້າທີ່ກຳນົດວັນໝົດອາຍຸໄວ້ ຈຶ່ງຈະຖືກຕິດຕາມ ແລະ ແຈ້ງເຕືອນ')); ?></small>
    </div>
    <div class="col-sm-6 text-right d-flex align-items-center justify-content-end flex-wrap mt-3 mt-sm-0" style="gap: 10px;">
      <?php if ($canViewReport): ?>
        <a href="../../reports/expiry_report.php" class="btn btn-light border px-3 font-weight-bold" style="border-radius: 8px; height: 38px; line-height: 24px;">
          <i class="fas fa-clipboard-list mr-1"></i> <?php echo htmlspecialchars(t('layout.expiry_report', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?>
        </a>
      <?php endif; ?>
      <?php if ($canAddExpiry): ?>
        <button type="button" class="btn btn-primary px-3 font-weight-bold shadow-sm" onclick="exOpenModal()" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('expiry.add_btn', 'ກຳນົດວັນໝົດອາຍຸ')); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($message)): ?>
    <script>
      $(function () {
        Swal.fire({
          title: <?php echo json_encode($message_type === 'success' ? t('expiry.msg_saved_title', 'ບັນທຶກສຳເລັດ!') : t('expiry.msg_error_title', 'ແຈ້ງເຕືອນ'), JSON_UNESCAPED_UNICODE); ?>,
          text: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
          icon: '<?php echo $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'error'); ?>',
          confirmButtonColor: '#2c5aa0',
          confirmButtonText: <?php echo json_encode(t('expiry.ok_btn', 'ຕົກລົງ'), JSON_UNESCAPED_UNICODE); ?>
        });
      });
    </script>
  <?php endif; ?>

  <div class="row">
    <div class="col-12 mb-3">
      <div class="card border-0 shadow-sm mb-0 ex-card">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom">
          <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
            <h6 class="m-0 font-weight-bold text-dark mr-2"><i class="fas fa-list mr-2 text-primary"></i><?php echo htmlspecialchars(t('expiry.card_tracked', 'ສິນຄ້າທີ່ຕິດຕາມວັນໝົດອາຍຸ')); ?> <span class="badge badge-light border ml-1"><?php echo number_format($exTotal); ?></span></h6>
            <form method="GET" action="index.php" class="ml-auto" style="min-width: 280px;" id="expirySearchForm">
              <input type="text" name="q" id="searchQ" class="form-control" value="<?php echo htmlspecialchars($searchQ); ?>" placeholder="<?php echo htmlspecialchars(t('expiry.search_placeholder', 'ຄົ້ນຫາ ຊື່, ບາໂຄ້ດ...')); ?>" style="height: 40px; border-radius: 6px;">
            </form>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
              <tr style="white-space: nowrap;">
                <th class="text-center" style="width: 55px;"><?php echo htmlspecialchars(t('expiry.col_no', 'ລຳດັບ')); ?></th>
                <th><?php echo htmlspecialchars(t('expiry.col_code', 'ລະຫັດ')); ?></th>
                <th><?php echo htmlspecialchars(t('expiry.col_product', 'ສິນຄ້າ')); ?></th>
                <th><?php echo htmlspecialchars(t('expiry.col_category', 'ປະເພດ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_days_left', 'ຍັງເຫຼືອ')); ?></th>
                <?php if ($canEditExpiry || $canDelExpiry): ?><th class="text-center" style="width: 100px;"><?php echo htmlspecialchars(t('expiry.col_manage', 'ຈັດການ')); ?></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($expiryRows)): ?>
                <tr><td colspan="7" class="text-center py-5 text-muted">
                  <i class="fas fa-calendar-check fa-2x mb-2 d-block text-secondary"></i>
                  <?php echo htmlspecialchars($searchQ === '' ? t('expiry.empty_none', 'ຍັງບໍ່ມີສິນຄ້າທີ່ກຳນົດວັນໝົດອາຍຸ') : t('expiry.empty_filter', 'ບໍ່ພົບລາຍການ')); ?>
                </td></tr>
              <?php else: ?>
                <?php foreach ($expiryRows as $idx => $r): ?>
                  <?php
                    $days = (int)$r['days_left'];
                    if ($days < 0) {
                        $daysText = '<span class="text-danger font-weight-bold">' . formatExpiryRemainingHtml($r['expiry_date']) . '</span>';
                    } elseif ($days === 0) {
                        $daysText = '<span class="text-danger font-weight-bold">' . htmlspecialchars(t('expiry.days_today', 'ມື້ນີ້')) . '</span>';
                    } elseif ($days <= $alertDays) {
                        $daysText = '<span class="font-weight-bold" style="color: #b45309;">' . formatExpiryRemainingHtml($r['expiry_date']) . '</span>';
                    } else {
                        $daysText = '<span class="text-muted font-weight-bold">' . formatExpiryRemainingHtml($r['expiry_date']) . '</span>';
                    }
                    $rowJson = ['product_id' => (int)$r['product_id'], 'expiry_date' => $r['expiry_date'], 'text' => $exText($r)];
                  ?>
                  <tr style="white-space: nowrap;">
                    <td class="text-center font-weight-bold text-muted"><?php echo ($exPage - 1) * $exPerPage + $idx + 1; ?></td>
                    <td><span class="ex-code-badge"><?php echo (int)$r['product_id']; ?></span></td>
                    <td class="font-weight-bold text-dark">
                      <?php echo htmlspecialchars($r['product_name']); ?>
                      <?php if (!empty($r['barcode'])): ?><small class="d-block text-muted font-weight-normal"><i class="fas fa-barcode mr-1"></i><?php echo htmlspecialchars($r['barcode']); ?></small><?php endif; ?>
                    </td>
                    <td class="text-muted"><?php echo htmlspecialchars($r['category_name'] ?? '-'); ?></td>
                    <td class="text-center font-weight-bold text-dark"><i class="far fa-calendar-alt mr-1 text-muted"></i><?php echo date('d/m/Y', strtotime($r['expiry_date'])); ?></td>
                    <td class="text-center"><?php echo $daysText; ?></td>
                    <?php if ($canEditExpiry || $canDelExpiry): ?>
                      <td class="text-center">
                        <?php if ($canEditExpiry): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm px-2" title="<?php echo htmlspecialchars(t('expiry.edit_btn', 'ແກ້ໄຂວັນໝົດອາຍຸ')); ?>" onclick='exOpenModal(<?php echo json_encode($rowJson, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                        <?php endif; ?>
                        <?php if ($canDelExpiry): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($selfUrl); ?>" class="d-inline" onsubmit="return exConfirmClear(this);">
                          <input type="hidden" name="action" value="clear_expiry">
                          <input type="hidden" name="product_id" value="<?php echo (int)$r['product_id']; ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="<?php echo htmlspecialchars(t('expiry.clear_btn', 'ຍົກເລີກການຕິດຕາມ')); ?>"><i class="fas fa-calendar-times"></i></button>
                        </form>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php echo exPager($exTotal, $exPage, $exPerPage); ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canSetExpiry): ?>
<!-- Modal: set / change a product's expiry date -->
<div class="modal fade" id="exModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form method="POST" action="<?php echo htmlspecialchars($selfUrl); ?>">
        <input type="hidden" name="action" value="set_expiry">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;"><i class="fas fa-calendar-alt mr-2"></i><?php echo htmlspecialchars(t('expiry.add_btn', 'ກຳນົດວັນໝົດອາຍຸ')); ?></h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <!-- Barcode scan: scan (or type) and press Enter - picks the product straight away -->
          <div class="mb-3">
            <label class="font-weight-bold text-dark small mb-1"><i class="fas fa-barcode mr-1 text-primary"></i><?php echo htmlspecialchars(t('expiry.scan_label', 'ສະແກນບາໂຄ້ດ')); ?></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px;"><i class="fas fa-barcode"></i></span></div>
              <input type="text" id="exScan" class="form-control" autocomplete="off" inputmode="search" placeholder="<?php echo htmlspecialchars(t('expiry.scan_placeholder', 'ສະແກນ ຫຼື ພິມບາໂຄ້ດ ແລ້ວກົດ Enter')); ?>" style="height: 42px; border-radius: 0 8px 8px 0;">
            </div>
            <small id="exScanMsg" class="d-block mt-1" style="min-height: 18px;"></small>
          </div>
          <div class="mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('expiry.col_product', 'ສິນຄ້າ')); ?> <span class="text-danger">*</span></label>
            <!-- Filled by the scan above, or search by name here (for products without a barcode) -->
            <select name="product_id" id="exProduct" class="form-control" style="width: 100%;"></select>
          </div>
          <div class="mb-2">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('expiry.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?> <span class="text-danger">*</span></label>
            <input type="date" name="expiry_date" id="exDate" class="form-control font-weight-bold" style="height: 42px;" required>
          </div>
          <small class="text-muted"><?php echo htmlspecialchars(sprintf(t('expiry.modal_hint', 'ລະບົບຈະແຈ້ງເຕືອນເມື່ອເຫຼືອ %s ມື້ ຫຼື ໜ້ອຍກວ່າ'), $alertDays)); ?></small>
        </div>
        <div class="modal-footer border-0 bg-light p-3">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal"><?php echo htmlspecialchars(t('expiry.cancel_btn', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('expiry.save_btn', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/partials/expiry_js.php'; /* JS ຂອງໜ້າ */ ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>






