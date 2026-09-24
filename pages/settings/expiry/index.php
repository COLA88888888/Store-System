<?php
// ============================================================
// pages/settings/expiry/index.php
// ຈັດການສິນຄ້າໝົດອາຍຸ (Product expiry)
// A product is tracked only when it has an expiry date (products.expiry_date). Tracked
// products raise a bell notification once getExpiryAlertDays() days (fixed at 5) or fewer
// are left, and this page doubles as the expiry report (filter, print).
// ============================================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('expiry') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';
$canEditExpiry = hasPermission('expiry', 'edit') || hasPermission('setup') || ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ';
$activeStoreId = getActiveStoreId($pdo);

// ===== AJAX for the "set expiry date" modal =====
// The product is chosen by scanning its barcode (or typing the barcode / product code) — the
// modal never loads the product list, so it stays fast with thousands of products.
// ex_ajax=barcode: exact barcode (or product code) -> one product of this branch.
$exAjax = $_GET['ex_ajax'] ?? '';
if ($exAjax === 'barcode') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$canEditExpiry) {
        echo json_encode(['results' => [], 'found' => false]);
        exit();
    }
    $exText = function ($r) {
        return $r['product_name'] . (!empty($r['unit']) ? ' (' . $r['unit'] . ')' : '') . (!empty($r['barcode']) ? ' · ' . $r['barcode'] : '');
    };
    try {
        $code = trim((string)($_GET['code'] ?? ''));
        $row = false;
        if ($code !== '') {
            $st = $pdo->prepare("SELECT product_id, product_name, unit, barcode, expiry_date FROM products
                WHERE store_id = ? AND (barcode = ? OR CAST(product_id AS CHAR) = ?) ORDER BY (barcode = ?) DESC LIMIT 1");
            $st->execute([$activeStoreId, $code, $code, $code]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        }
        echo json_encode($row
            ? ['found' => true, 'id' => (int)$row['product_id'], 'text' => $exText($row), 'expiry' => $row['expiry_date'] ?? '']
            : ['found' => false], JSON_UNESCAPED_UNICODE);
        exit();
    } catch (Throwable $e) {
        echo json_encode(['results' => [], 'found' => false]);
    }
    exit();
}

$postAction = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['action'] ?? '') : '';
if ($postAction !== '' && !$canEditExpiry) {
    $message = t('expiry.err_no_permission', 'ທ່ານບໍ່ມີສິດແກ້ໄຂການຕັ້ງຄ່ານີ້!');
    $message_type = 'warning';
    $postAction = '';
}

try {
    if ($postAction === 'set_expiry' || $postAction === 'clear_expiry') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $expDate   = $postAction === 'set_expiry' ? normalizeExpiryDate($_POST['expiry_date'] ?? '') : null;
        $pStmt = $pdo->prepare("SELECT product_name FROM products WHERE product_id = ? AND store_id = ? LIMIT 1");
        $pStmt->execute([$productId, $activeStoreId]);
        $pName = $pStmt->fetchColumn();

        if ($pName === false) {
            $message = t('expiry.err_product', 'ກະລຸນາເລືອກສິນຄ້າ');
            $message_type = 'warning';
        } elseif ($postAction === 'set_expiry' && $expDate === null) {
            $message = t('expiry.err_date', 'ກະລຸນາເລືອກວັນໝົດອາຍຸ');
            $message_type = 'warning';
        } else {
            $pdo->prepare("UPDATE products SET expiry_date = ? WHERE product_id = ? AND store_id = ?")
                ->execute([$expDate, $productId, $activeStoreId]);
            if ($expDate !== null) {
                $message = t('expiry.msg_set', 'ບັນທຶກວັນໝົດອາຍຸສຳເລັດ!');
                logActivity($pdo, 'ກຳນົດວັນໝົດອາຍຸສິນຄ້າ', "ສິນຄ້າ: $pName, ວັນໝົດອາຍຸ: " . date('d/m/Y', strtotime($expDate)));
            } else {
                $message = t('expiry.msg_cleared', 'ຍົກເລີກການຕິດຕາມວັນໝົດອາຍຸແລ້ວ');
                logActivity($pdo, 'ຍົກເລີກວັນໝົດອາຍຸສິນຄ້າ', "ສິນຄ້າ: $pName");
            }
            $message_type = 'success';
        }
    }
} catch (Exception $e) {
    $message = t('expiry.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
    $message_type = 'danger';
}

$alertDays = getExpiryAlertDays($pdo);

// ===== Report =====
$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['all', 'expired', 'near', 'normal'], true)) $statusFilter = 'all';
$searchQ = trim($_GET['q'] ?? '');

$counts = ['all' => 0, 'expired' => 0, 'near' => 0, 'normal' => 0];
try {
    $c = $pdo->prepare("
        SELECT COUNT(*) AS all_cnt,
               COALESCE(SUM(DATEDIFF(expiry_date, CURDATE()) < 0), 0) AS expired_cnt,
               COALESCE(SUM(DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND ?), 0) AS near_cnt,
               COALESCE(SUM(DATEDIFF(expiry_date, CURDATE()) > ?), 0) AS normal_cnt
        FROM products WHERE store_id = ? AND expiry_date IS NOT NULL");
    $c->execute([$alertDays, $alertDays, $activeStoreId]);
    $row = $c->fetch(PDO::FETCH_ASSOC);
    $counts = ['all' => (int)$row['all_cnt'], 'expired' => (int)$row['expired_cnt'], 'near' => (int)$row['near_cnt'], 'normal' => (int)$row['normal_cnt']];
} catch (Exception $e) {}

$where = "p.store_id = ? AND p.expiry_date IS NOT NULL";
$params = [$activeStoreId];
if ($statusFilter === 'expired') {
    $where .= " AND p.expiry_date < CURDATE()";
} elseif ($statusFilter === 'near') {
    $where .= " AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $alertDays;
} elseif ($statusFilter === 'normal') {
    $where .= " AND p.expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $alertDays;
}
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
    $s = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where");
    $s->execute($params);
    $exTotal = (int)$s->fetchColumn();
    $exPage = min($exPage, max(1, (int)ceil($exTotal / $exPerPage)));

    $s = $pdo->prepare("
        SELECT p.product_id, p.product_name, p.barcode, p.unit, p.qty, p.cut_qty, p.expiry_date,
               c.category_name, DATEDIFF(p.expiry_date, CURDATE()) AS days_left
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        WHERE $where
        ORDER BY p.expiry_date ASC, p.product_name ASC
        LIMIT $exPerPage OFFSET " . (($exPage - 1) * $exPerPage));
    $s->execute($params);
    $expiryRows = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}


$storeName = '';
try {
    $s = $pdo->prepare("SELECT store_name FROM tbstore WHERE store_id = ? LIMIT 1");
    $s->execute([$activeStoreId]);
    $storeName = (string)$s->fetchColumn();
} catch (Exception $e) {}

// Keep the current filter when a form posts back to this page
$selfUrl = 'index.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : '');

function exPager($total, $page, $perPage) {
    if ($total <= 0) return '';
    $pages = (int)ceil($total / $perPage);
    $from = ($page - 1) * $perPage + 1;
    $to   = min($total, $page * $perPage);
    $html = '<div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap py-2 px-3 no-print" style="gap: 8px;">'
          . '<small class="text-muted">' . htmlspecialchars(sprintf(t('expiry.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'), number_format($from), number_format($to), number_format($total))) . '</small>';
    if ($pages > 1) {
        $url = function ($p) { return 'index.php?' . htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $p]))); };
        $item = function ($p, $label, $disabled = false, $active = false) use ($url) {
            return '<li class="page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '') . '">'
                 . '<a class="page-link" href="' . ($disabled || $active ? '#' : $url($p)) . '">' . $label . '</a></li>';
        };
        $html .= '<ul class="pagination pagination-sm mb-0 ml-auto">' . $item($page - 1, '<i class="fas fa-chevron-left" style="font-size:0.72rem;"></i>', $page <= 1);
        $start = max(1, $page - 2); $end = min($pages, $page + 2);
        if ($start > 1) { $html .= $item(1, '1'); if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
        for ($p = $start; $p <= $end; $p++) $html .= $item($p, (string)$p, false, $p === $page);
        if ($end < $pages) { if ($end < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; $html .= $item($pages, (string)$pages); }
        $html .= $item($page + 1, '<i class="fas fa-chevron-right" style="font-size:0.72rem;"></i>', $page >= $pages) . '</ul>';
    }
    return $html . '</div>';
}

$statusTabs = [
    'all'     => ['label' => t('expiry.tab_all', 'ທັງໝົດ'),         'icon' => 'fa-list',                 'color' => 'secondary'],
    'expired' => ['label' => t('expiry.tab_expired', 'ໝົດອາຍຸແລ້ວ'), 'icon' => 'fa-times-circle',         'color' => 'danger'],
    'near'    => ['label' => t('expiry.tab_near', 'ໃກ້ໝົດອາຍຸ'),     'icon' => 'fa-exclamation-triangle', 'color' => 'warning'],
    'normal'  => ['label' => t('expiry.tab_normal', 'ປົກກະຕິ'),      'icon' => 'fa-check-circle',         'color' => 'success'],
];

require_once __DIR__ . '/../../../layouts/header.php';
?>

<style>
  .ex-card { border-radius: 12px; border: 1.5px solid #e2e8f0 !important; background: #ffffff; }
  .ex-code-badge { display: inline-block; padding: 2px 10px; border-radius: 6px; background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 0.8rem; }
  .ex-tabs .nav-link { border-radius: 8px; font-weight: 700; font-size: 0.84rem; color: #475569; padding: 6px 12px; }
  .ex-tabs .nav-link.active { background: #244886; color: #fff; }
  .ex-tabs .nav-link .badge { font-size: 0.72rem; }
  .print-only { display: none; }
  @media print {
    .no-print, .modal { display: none !important; }
    .print-only { display: block !important; }
    .ex-card { border: none !important; box-shadow: none !important; }
    .container-fluid { padding: 0 !important; }
    .col-lg-9 { flex: 0 0 100%; max-width: 100%; }
    table { font-size: 11px !important; }
  }
</style>

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
      <button type="button" class="btn btn-light border px-3 font-weight-bold" onclick="window.print()" style="border-radius: 8px; height: 38px;">
        <i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('expiry.print_btn', 'ພິມລາຍງານ')); ?>
      </button>
      <?php if ($canEditExpiry): ?>
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
    <!-- Alert rule -->
    <!-- Report -->
    <div class="col-12 mb-3">
      <div class="print-only mb-3">
        <h4 class="font-weight-bold mb-1"><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?> — <?php echo htmlspecialchars($statusTabs[$statusFilter]['label']); ?></h4>
        <div><?php echo htmlspecialchars($storeName); ?> · <?php echo htmlspecialchars(t('expiry.printed_at', 'ວັນທີພິມ')); ?>: <?php echo date('d/m/Y H:i'); ?></div>
      </div>
      <div class="card border-0 shadow-sm mb-0 ex-card">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom no-print">
          <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
            <h6 class="m-0 font-weight-bold text-dark mr-2"><i class="fas fa-clipboard-list mr-2 text-primary"></i><?php echo htmlspecialchars(t('expiry.report_title', 'ລາຍງານສິນຄ້າໝົດອາຍຸ')); ?></h6>
            <ul class="nav nav-pills ex-tabs" style="gap: 4px;">
              <?php foreach ($statusTabs as $key => $tab): ?>
                <li class="nav-item">
                  <a class="nav-link <?php echo $statusFilter === $key ? 'active' : ''; ?>" href="index.php?<?php echo htmlspecialchars(http_build_query(array_filter(['status' => $key, 'q' => $searchQ], 'strlen'))); ?>">
                    <i class="fas <?php echo $tab['icon']; ?> mr-1 <?php echo $statusFilter === $key ? '' : 'text-' . $tab['color']; ?>"></i><?php echo htmlspecialchars($tab['label']); ?>
                    <span class="badge <?php echo $statusFilter === $key ? 'badge-light' : 'badge-' . $tab['color']; ?> ml-1"><?php echo number_format($counts[$key]); ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
            <form method="GET" action="index.php" class="ml-auto" style="min-width: 320px;" id="expirySearchForm">
<input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
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
                <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_stock', 'ສະຕັອກ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_days_left', 'ຍັງເຫຼືອ')); ?></th>
                <th class="text-center"><?php echo htmlspecialchars(t('expiry.col_status', 'ສະຖານະ')); ?></th>
                <?php if ($canEditExpiry): ?><th class="text-center no-print" style="width: 100px;"><?php echo htmlspecialchars(t('expiry.col_manage', 'ຈັດການ')); ?></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($expiryRows)): ?>
                <tr><td colspan="9" class="text-center py-5 text-muted">
                  <i class="fas fa-calendar-check fa-2x mb-2 d-block text-secondary"></i>
                  <?php echo htmlspecialchars($counts['all'] === 0 ? t('expiry.empty_none', 'ຍັງບໍ່ມີສິນຄ້າທີ່ກຳນົດວັນໝົດອາຍຸ') : t('expiry.empty_filter', 'ບໍ່ພົບລາຍການ')); ?>
                </td></tr>
              <?php else: ?>
                <?php foreach ($expiryRows as $idx => $r): ?>
                  <?php
                    $days = (int)$r['days_left'];
                    if ($days < 0) {
                        $stBadge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>' . htmlspecialchars(t('expiry.status_expired', 'ໝົດອາຍຸແລ້ວ')) . '</span>';
                        $daysText = '<span class="text-danger font-weight-bold">' . formatExpiryRemainingHtml($r['expiry_date']) . '</span>';
                    } elseif ($days === 0) {
                        $stBadge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i>' . htmlspecialchars(t('expiry.status_today', 'ໝົດອາຍຸມື້ນີ້')) . '</span>';
                        $daysText = '<span class="text-danger font-weight-bold">' . htmlspecialchars(t('expiry.days_today', 'ມື້ນີ້')) . '</span>';
                    } elseif ($days <= $alertDays) {
                        $stBadge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>' . htmlspecialchars(t('expiry.status_near', 'ໃກ້ໝົດອາຍຸ')) . '</span>';
                        $daysText = '<span class="font-weight-bold" style="color: #b45309;">' . formatExpiryRemainingHtml($r['expiry_date']) . '</span>';
                    } else {
                        $stBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>' . htmlspecialchars(t('expiry.status_normal', 'ປົກກະຕິ')) . '</span>';
                        $daysText = '<span class="text-muted font-weight-bold">' . formatExpiryRemainingHtml($r['expiry_date']) . '</span>';
                    }
                    $rowJson = ['product_id' => (int)$r['product_id'], 'expiry_date' => $r['expiry_date'],
                                'text' => $r['product_name'] . (!empty($r['unit']) ? ' (' . $r['unit'] . ')' : '') . (!empty($r['barcode']) ? ' · ' . $r['barcode'] : '')];
                  ?>
                  <tr style="white-space: nowrap;">
                    <td class="text-center font-weight-bold text-muted"><?php echo ($exPage - 1) * $exPerPage + $idx + 1; ?></td>
                    <td><span class="ex-code-badge"><?php echo (int)$r['product_id']; ?></span></td>
                    <td class="font-weight-bold text-dark">
                      <?php echo htmlspecialchars($r['product_name']); ?>
                      <?php if (!empty($r['barcode'])): ?><small class="d-block text-muted font-weight-normal"><i class="fas fa-barcode mr-1"></i><?php echo htmlspecialchars($r['barcode']); ?></small><?php endif; ?>
                    </td>
                    <td class="text-muted"><?php echo htmlspecialchars($r['category_name'] ?? '-'); ?></td>
                    <td class="text-center">
                      <?php if ((int)$r['cut_qty'] === 0): ?>
                        <span class="text-muted">-</span>
                      <?php elseif ((float)$r['qty'] <= 0): ?>
                        <span class="badge badge-light border text-muted px-2 py-1"><?php echo htmlspecialchars(t('expiry.out_of_stock', 'ໝົດສະຕັອກ')); ?></span>
                      <?php else: ?>
                        <span class="font-weight-bold text-dark"><?php echo number_format((float)$r['qty']); ?></span> <small class="text-muted"><?php echo htmlspecialchars($r['unit'] ?? ''); ?></small>
                      <?php endif; ?>
                    </td>
                    <td class="text-center font-weight-bold text-dark"><i class="far fa-calendar-alt mr-1 text-muted"></i><?php echo date('d/m/Y', strtotime($r['expiry_date'])); ?></td>
                    <td class="text-center"><?php echo $daysText; ?></td>
                    <td class="text-center"><?php echo $stBadge; ?></td>
                    <?php if ($canEditExpiry): ?>
                      <td class="text-center no-print">
                        <button type="button" class="btn btn-outline-primary btn-sm px-2" title="<?php echo htmlspecialchars(t('expiry.edit_btn', 'ແກ້ໄຂວັນໝົດອາຍຸ')); ?>" onclick='exOpenModal(<?php echo json_encode($rowJson, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                        <form method="POST" action="<?php echo htmlspecialchars($selfUrl); ?>" class="d-inline" onsubmit="return exConfirmClear(this);">
                          <input type="hidden" name="action" value="clear_expiry">
                          <input type="hidden" name="product_id" value="<?php echo (int)$r['product_id']; ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="<?php echo htmlspecialchars(t('expiry.clear_btn', 'ຍົກເລີກການຕິດຕາມ')); ?>"><i class="fas fa-calendar-times"></i></button>
                        </form>
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

<?php if ($canEditExpiry): ?>
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
            <!-- Filled by the scan above; not typed into -->
            <input type="hidden" name="product_id" id="exProduct" value="">
            <div id="exProductName" class="form-control d-flex align-items-center text-muted" style="height: 42px; background: #f8fafc; cursor: default; user-select: text;"><?php echo htmlspecialchars(t('expiry.product_scan_first', 'ສະແກນບາໂຄ້ດເພື່ອເລືອກສິນຄ້າ')); ?></div>
          </div>
          <div class="mb-2">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('expiry.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?> <span class="text-danger">*</span></label>
            <input type="date" name="expiry_date" id="exDate" class="form-control font-weight-bold" style="height: 42px;" required>
          </div>
          <small class="text-muted"><?php echo htmlspecialchars(sprintf(t('expiry.modal_hint', 'ລະບົບຈະແຈ້ງເຕືອນເມື່ອເຫຼືອ %s ມື້ ຫຼື ໜ້ອຍກວ່າ'), $alertDays)); ?></small>
        </div>
        <div class="modal-footer border-0 bg-light p-3">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal"><?php echo htmlspecialchars(t('expiry.cancel_btn', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('expiry.save_btn', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
var EX_PRODUCT_EMPTY = <?php echo json_encode(t('expiry.product_scan_first', 'ສະແກນບາໂຄ້ດເພື່ອເລືອກສິນຄ້າ'), JSON_UNESCAPED_UNICODE); ?>;

// Show the scanned product (read-only) and remember its id for the form
function exSelectProduct(id, text, expiry) {
  $('#exProduct').val(id ? String(id) : '');
  $('#exProductName').text(id ? text : EX_PRODUCT_EMPTY)
    .toggleClass('text-muted', !id).toggleClass('text-dark font-weight-bold', !!id);
  if (expiry) miniposSetDate(document.getElementById('exDate'), expiry);
}

function exOpenModal(row) {
  row = row || { product_id: '', expiry_date: '' };
  $('#exScan').val('');
  $('#exScanMsg').text('').removeClass('text-danger text-success');
  if (row.product_id) {
    exSelectProduct(row.product_id, row.text || String(row.product_id), row.expiry_date || '');
  } else {
    exSelectProduct('', '', '');
  }
  miniposSetDate(document.getElementById('exDate'), row.expiry_date || '');
  $('#exModal').modal('show');
}

function exConfirmClear(form) {
  Swal.fire({
    title: <?php echo json_encode(t('expiry.clear_confirm_title', 'ຍົກເລີກການຕິດຕາມວັນໝົດອາຍຸ?'), JSON_UNESCAPED_UNICODE); ?>,
    text: <?php echo json_encode(t('expiry.clear_confirm_text', 'ສິນຄ້ານີ້ຈະບໍ່ແຈ້ງເຕືອນອີກ ຈົນກວ່າຈະກຳນົດວັນໝົດອາຍຸໃໝ່'), JSON_UNESCAPED_UNICODE); ?>,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: <?php echo json_encode(t('expiry.clear_btn', 'ຍົກເລີກການຕິດຕາມ'), JSON_UNESCAPED_UNICODE); ?>,
    cancelButtonText: <?php echo json_encode(t('expiry.cancel_btn', 'ຍົກເລີກ'), JSON_UNESCAPED_UNICODE); ?>
  }).then(function (r) { if (r.isConfirmed) form.submit(); });
  return false;
}

$(function () {
  var EX_I18N = <?php echo json_encode([
    'not_found'  => t('expiry.scan_not_found', 'ບໍ່ພົບສິນຄ້າບາໂຄ້ດນີ້: %s'),
    'found'      => t('expiry.scan_found', 'ເລືອກແລ້ວ: %s'),
    'need'       => t('expiry.scan_required', 'ກະລຸນາສະແກນສິນຄ້າກ່ອນ'),
  ], JSON_UNESCAPED_UNICODE); ?>;

  // After a product is chosen, jump straight to the date and open the calendar
  function exFocusDate() {
    var el = document.getElementById('exDate');
    var fp = el && el._flatpickr;
    setTimeout(function () { if (fp) { fp.altInput.focus(); fp.open(); } else if (el) el.focus(); }, 50);
  }

  // Barcode scanner / typed code + Enter -> select that product
  $('#exScan').on('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var code = $.trim(this.value);
    if (!code) return;
    var $msg = $('#exScanMsg');
    $.getJSON('index.php', { ex_ajax: 'barcode', code: code }, function (d) {
      if (d && d.found) {
        exSelectProduct(d.id, d.text, d.expiry);
        $msg.text(EX_I18N.found.replace('%s', d.text)).removeClass('text-danger').addClass('text-success');
        $('#exScan').val('');
        exFocusDate();
      } else {
        $msg.text(EX_I18N.not_found.replace('%s', code)).removeClass('text-success').addClass('text-danger');
        $('#exScan').select();
      }
    });
  });
  // No product scanned yet -> don't submit, send the cashier back to the scan box
  $('#exModal form').on('submit', function (e) {
    if (!$('#exProduct').val()) {
      e.preventDefault();
      $('#exScanMsg').text(EX_I18N.need).removeClass('text-success').addClass('text-danger');
      $('#exScan').trigger('focus');
    }
  });

  // Ready to scan as soon as the modal opens (new entry only; editing keeps the chosen product)
  $('#exModal').on('shown.bs.modal', function () {
    if (!$('#exProduct').val()) $('#exScan').trigger('focus');
  });


  // ຄົ້ນຫາອັດຕະໂນມັດຈະໂຫຼດໜ້າໃໝ່ — ໃຫ້ cursor ກັບມາຢູ່ທ້າຍຊ່ອງຄົ້ນຫາ ເພື່ອພິມຕໍ່ໄດ້
  var sq = document.getElementById('searchQ');
  if (sq && sq.value) { sq.focus(); sq.setSelectionRange(sq.value.length, sq.value.length); }

  var searchTimeout;
  $('#searchQ').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
      $('#expirySearchForm').submit();
    }, 500);
  });
});
</script>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>






