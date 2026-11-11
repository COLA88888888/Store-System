<?php
/**
 * ສະມາຊິກເວັບໄຊ — ລາຍຊື່ຄົນທີ່ສະໝັກຜ່ານເວັບ (ຕາຕະລາງ w_web_members).
 * ອອກແບບຕາມມາດຕະຖານໂມດູນລູກຄ້າ (customers.php): Filter Bar (ຈຳນວນ, ວັນທີ, ຄົ້ນຫາສົດ) + Pagination ວົງມົນສີຟ້າ.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id']) || !hasPermission('customers')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// ຈັດການ POST: ຕັ້ງລະຫັດໃໝ່ (reset_pw)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'reset_pw') {
    header('Content-Type: application/json; charset=utf-8');
    $mid = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("SELECT id, name, tel, member_no FROM w_web_members WHERE id = ?");
    $st->execute([$mid]);
    $mm = $st->fetch(PDO::FETCH_ASSOC);
    if (!$mm) {
        echo json_encode(['success' => false]);
        exit();
    }
    $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
    $newPw = '';
    for ($i = 0; $i < 8; $i++) {
        $newPw .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $pdo->prepare("UPDATE w_web_members SET password_hash = ? WHERE id = ?")->execute([password_hash($newPw, PASSWORD_DEFAULT), $mid]);
    try {
        $pdo->prepare("DELETE FROM w_web_member_tokens WHERE member_id = ?")->execute([$mid]);
    } catch (Throwable $e) {
    }
    echo json_encode(['success' => true, 'password' => $newPw, 'name' => $mm['name'], 'tel' => (string)$mm['tel'], 'code' => memberCode($mm['member_no'])], JSON_UNESCAPED_UNICODE);
    exit();
}

// ຈັດການ POST: ປິດ/ເປີດບັນຊີ (toggle)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $pdo->prepare("UPDATE w_web_members SET status = IF(status = 'active', 'disabled', 'active') WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
    $qs = http_build_query(array_filter([
        'q'         => $_GET['q'] ?? '',
        'page'      => $_GET['page'] ?? '',
        'per_page'  => $_GET['per_page'] ?? '',
        'from_date' => $_GET['from_date'] ?? '',
        'to_date'   => $_GET['to_date'] ?? '',
    ]));
    header('Location: index.php' . ($qs ? '?' . $qs : ''));
    exit();
}

// ຟັງຊັນສ້າງ Pagination ວົງມົນສີຟ້າ ແບບດຽວກັບ customer_pagination
function wmRenderCircularPagination(int $page, int $pages, int $total, int $perPage): array
{
    $startRow = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $endRow   = min($total, $page * $perPage);
    $info = "ສະແດງ " . number_format($startRow) . " - " . number_format($endRow) . " ຈາກທັງໝົດ " . number_format($total) . " ຄົນ";

    $html = '<ul class="pagination pagination-circle mb-0 justify-content-end">';
    
    // ປຸ່ມກ່ອນໜ້າ (Prev)
    $prevDisabled = $page <= 1;
    $html .= '<li class="page-item ' . ($prevDisabled ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="javascript:void(0)" ' . ($prevDisabled ? '' : 'data-page="' . ($page - 1) . '"') . ' title="ກ່ອນໜ້າ"><i class="fas fa-chevron-left"></i></a>';
    $html .= '</li>';

    // ປຸ່ມເລກໜ້າ (ສະແດງສູງສຸດ 5 ປຸ່ມ)
    $maxButtons = 5;
    $startPage = max(1, $page - 2);
    $endPage = min($pages, $startPage + $maxButtons - 1);
    if ($endPage - $startPage < $maxButtons - 1) {
        $startPage = max(1, $endPage - $maxButtons + 1);
    }

    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($p = $startPage; $p <= $endPage; $p++) {
        $active = ($p === $page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link" href="javascript:void(0)" data-page="' . $p . '">' . $p . '</a>';
        $html .= '</li>';
    }

    if ($endPage < $pages) {
        if ($endPage < $pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page="' . $pages . '">' . $pages . '</a></li>';
    }

    // ປຸ່ມຖັດໄປ (Next)
    $nextDisabled = $page >= $pages;
    $html .= '<li class="page-item ' . ($nextDisabled ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="javascript:void(0)" ' . ($nextDisabled ? '' : 'data-page="' . ($page + 1) . '"') . ' title="ຖັດໄປ"><i class="fas fa-chevron-right"></i></a>';
    $html .= '</li>';

    $html .= '</ul>';

    return ['info' => $info, 'pager' => $html];
}

// ຕົວກອງຂໍ້ມູນ
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$fromDate = isset($_GET['from_date']) ? trim((string)$_GET['from_date']) : date('Y-m-01');
$toDate   = isset($_GET['to_date']) ? trim((string)$_GET['to_date']) : date('Y-m-d');
$perPage  = $_GET['per_page'] ?? '10';

if ($perPage === 'all') {
    $limit = 10000;
} else {
    $limit = in_array((int)$perPage, [10, 25, 50, 100], true) ? (int)$perPage : 10;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$members = [];
$total = 0;
$activeCount = 0;

try {
    $where = '1=1';
    $params = [];

    if ($q !== '') {
        $where .= ' AND (name LIKE ? OR tel LIKE ? OR email LIKE ? OR shop_name LIKE ? OR member_no = ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = preg_match('/^(?:wl-?)?0*(\d+)$/i', $q, $mNo) ? (int)$mNo[1] : -1;
    }

    if ($fromDate !== '') {
        $where .= ' AND created_at >= ?';
        $params[] = $fromDate . ' 00:00:00';
    }
    if ($toDate !== '') {
        $where .= ' AND created_at <= ?';
        $params[] = $toDate . ' 23:59:59';
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM w_web_members WHERE $where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $pages = max(1, (int)ceil($total / $limit));
    $page = min($page, $pages);
    $offset = ($page - 1) * $limit;

    $st = $pdo->prepare("SELECT * FROM w_web_members WHERE $where ORDER BY member_no ASC LIMIT $limit OFFSET $offset");
    $st->execute($params);
    $members = $st->fetchAll(PDO::FETCH_ASSOC);

    $activeCount = (int)$pdo->query("SELECT COUNT(*) FROM w_web_members WHERE status = 'active'")->fetchColumn();
} catch (Throwable $e) {
    error_log('web_members: ' . $e->getMessage());
    $pages = 1;
    $offset = 0;
}

// ສ້າງ HTML ແຖວຕາຕະລາງ
ob_start();
if (!$members): ?>
  <tr id="noCustomerDataRow">
    <td colspan="9" class="text-center py-5 text-muted">
      <i class="fas fa-user-slash fa-2x mb-2 text-secondary d-block"></i>
      <span class="font-weight-bold d-block" style="font-size: 1.05rem; color: #64748b;">ບໍ່ມີຂໍ້ມູນສະມາຊິກໃນລະບົບ</span>
    </td>
  </tr>
<?php else:
  $idx = $offset + 1;
  foreach ($members as $m):
    $isActive = $m['status'] === 'active'; ?>
    <tr class="cust-row <?php echo $isActive ? '' : 'text-muted'; ?>">
      <td class="align-middle text-center text-muted font-weight-bold" style="width: 50px;"><?php echo $idx++; ?></td>
      
      <td class="align-middle text-center font-weight-bold" style="width: 105px;">
        <span class="cust-code-badge"><?php echo memberCode($m['member_no']); ?></span>
      </td>

      <td class="align-middle font-weight-bold text-dark">
        <span><?php echo htmlspecialchars($m['name']); ?></span>
        <?php if (!empty($m['google_id'])): ?><span class="badge badge-light border ml-1">Google</span><?php endif; ?>
        <?php if (!empty($m['shop_name'])): ?><div class="small text-muted font-weight-normal"><?php echo htmlspecialchars($m['shop_name']); ?></div><?php endif; ?>
      </td>

      <td class="align-middle">
        <?php if (!empty($m['tel'])): ?>
          <a href="tel:<?php echo htmlspecialchars($m['tel']); ?>" class="text-dark font-weight-bold"><?php echo htmlspecialchars($m['tel']); ?></a>
        <?php else: ?>
          <span class="text-warning small font-weight-bold">ຍັງບໍ່ມີເບີ</span>
        <?php endif; ?>
        <?php if (!empty($m['whatsapp']) && $m['whatsapp'] !== $m['tel']): ?><div class="small text-muted">WA: <?php echo htmlspecialchars($m['whatsapp']); ?></div><?php endif; ?>
      </td>

      <td class="align-middle text-dark">
        <?php echo !empty($m['email']) ? htmlspecialchars((string)$m['email']) : '<span class="text-muted">-</span>'; ?>
      </td>

      <td class="align-middle text-center font-weight-bold" style="font-size: 0.85rem; color: #475569; width: 140px;">
        <?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?>
      </td>

      <td class="align-middle text-center font-weight-bold" style="font-size: 0.85rem; color: #475569; width: 140px;">
        <?php echo $m['last_login_at'] ? date('d/m/Y H:i', strtotime($m['last_login_at'])) : '<span class="text-muted">-</span>'; ?>
      </td>

      <td class="align-middle text-center" style="width: 90px;">
        <span class="badge <?php echo $isActive ? 'badge-success' : 'badge-secondary'; ?> wo-badge"><?php echo $isActive ? 'ໃຊ້ງານ' : 'ປິດ'; ?></span>
      </td>

      <td class="align-middle text-center wm-actions" style="width: 100px;">
        <form method="post" data-swal-confirm="<?php echo $isActive ? 'ປິດບັນຊີ ' . htmlspecialchars($m['name']) . '? (ເຂົ້າລະບົບບໍ່ໄດ້)' : 'ເປີດບັນຊີ ' . htmlspecialchars($m['name']) . ' ຄືນ?'; ?>"<?php echo $isActive ? ' data-swal-danger' : ''; ?>>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
          <button class="wm-ico rounded-circle <?php echo $isActive ? 'is-off' : 'is-on'; ?>" title="<?php echo $isActive ? 'ປິດບັນຊີ' : 'ເປີດບັນຊີຄືນ'; ?>" aria-label="<?php echo $isActive ? 'ປິດບັນຊີ' : 'ເປີດບັນຊີຄືນ'; ?>"><i class="fas <?php echo $isActive ? 'fa-lock' : 'fa-lock-open'; ?>"></i></button>
        </form>
        <button type="button" class="wm-ico rounded-circle is-key" title="ຕັ້ງລະຫັດຜ່ານໃໝ່" aria-label="ຕັ້ງລະຫັດຜ່ານໃໝ່" data-wm-reset="<?php echo (int)$m['id']; ?>" data-name="<?php echo htmlspecialchars($m['name']); ?>"><i class="fas fa-key"></i></button>
      </td>
    </tr>
<?php endforeach;
endif;
$rowsHtml = ob_get_clean();

$pagerData = wmRenderCircularPagination($page, $pages, $total, $limit);

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'rows'  => $rowsHtml,
        'pager' => $pagerData['pager'],
        'info'  => $pagerData['info'],
        'total' => $total,
        'page'  => $page
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../layouts/header.php';
?>
<?php echo themeCssLink('customers.css'); ?>
<?php echo themeCssLink('web_orders.css'); ?>

<div class="content-wrapper bg-light">
  <section class="content pt-3 pb-5">
    <div class="container-fluid">
      
      <!-- ຫົວຂໍ້ໜ້າ -->
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 font-weight-bold text-dark mb-0">
          <i class="fas fa-users text-primary mr-2"></i>ສະມາຊິກເວັບໄຊ <small class="text-muted" style="font-size: 0.95rem;">(ໃຊ້ງານ <?php echo number_format($activeCount); ?> ຄົນ)</small>
        </h1>
      </div>

      <!-- MAIN MEMBER CARD -->
      <div class="card customer-card shadow-sm border-0" style="border-radius: 14px;">
        
        <!-- Filter Bar Component (Page Size, Date Range, Member Search) -->
        <div class="customer-card-header p-3 bg-white border-bottom" style="border-radius: 14px 14px 0 0;">
          <form id="wmFilter" class="row align-items-center" style="row-gap: 12px;" autocomplete="off" onsubmit="return false;">
            
            <!-- Left: Page Size Selector -->
            <div class="col-auto">
              <div class="d-flex align-items-center" style="gap: 6px;">
                <select id="pageSizeSelect" name="per_page" class="form-control" style="width: 80px; height: 38px; font-size: 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                  <option value="10" <?php echo $perPage === '10' ? 'selected' : ''; ?>>10</option>
                  <option value="25" <?php echo $perPage === '25' ? 'selected' : ''; ?>>25</option>
                  <option value="50" <?php echo $perPage === '50' ? 'selected' : ''; ?>>50</option>
                  <option value="100" <?php echo $perPage === '100' ? 'selected' : ''; ?>>100</option>
                  <option value="all" <?php echo $perPage === 'all' ? 'selected' : ''; ?>>ທັງໝົດ</option>
                </select>
              </div>
            </div>

            <!-- Middle: Date Range Search -->
            <div class="col-lg-5 col-md-6 col-12">
              <div class="d-flex align-items-center flex-wrap flex-sm-nowrap" style="gap: 8px;">
                <div class="input-group" style="height: 38px;">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light text-muted px-2.5" style="border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none;"><i class="fas fa-calendar-alt text-primary"></i></span>
                  </div>
                  <input type="date" id="fromDateInput" name="from_date" class="form-control font-weight-bold" title="ຕັ້ງແຕ່ວັນທີ" value="<?php echo htmlspecialchars($fromDate); ?>" style="height: 38px; font-size: 0.88rem; border-radius: 0 8px 8px 0; border: 1px solid #cbd5e1;">
                </div>
                <span class="font-weight-bold text-secondary px-1" style="font-size: 0.88rem; white-space: nowrap;">ຫາ</span>
                <div class="input-group" style="height: 38px;">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-light text-muted px-2.5" style="border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none;"><i class="fas fa-calendar-check text-success"></i></span>
                  </div>
                  <input type="date" id="toDateInput" name="to_date" class="form-control font-weight-bold" title="ຫາວັນທີ" value="<?php echo htmlspecialchars($toDate); ?>" style="height: 38px; font-size: 0.88rem; border-radius: 0 8px 8px 0; border: 1px solid #cbd5e1;">
                </div>
                <button type="button" id="btnDateSearch" class="btn btn-primary font-weight-bold px-3 shadow-sm" title="ຄົ້ນຫາ" style="border-radius: 8px; height: 38px; font-size: 0.88rem; white-space: nowrap; display: flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
                  <i class="fas fa-search"></i> ຄົ້ນຫາ
                </button>
              </div>
            </div>

            <!-- Right: Member Search Box -->
            <div class="col-lg-5 col-md-5 col-12 ml-auto">
              <div class="input-group" style="height: 38px;">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-light text-primary px-3" style="border-radius: 8px 0 0 8px; border: 1px solid #cbd5e1; border-right: none;"><i class="fas fa-search" style="font-size: 0.95rem;"></i></span>
                </div>
                <input type="search" id="memberSearchInput" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="ຄົ້ນຫາ ລາຍຊື່ສະມາຊິກ, ລະຫັດ, ເບີໂທ, ອີເມວ..." style="height: 38px; font-size: 0.9rem; border-radius: 0 8px 8px 0; border: 1px solid #cbd5e1;">
              </div>
            </div>

            <input type="hidden" name="page" id="pageInput" value="<?php echo $page; ?>">
          </form>
        </div>

        <!-- Table Component -->
        <div class="card-body p-0">
          <div class="table-responsive wm-table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap wo-table wm-table" id="wmTable">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
                <tr>
                  <th class="py-3 text-center" style="width: 50px;">ລຳດັບ</th>
                  <th class="py-3 text-center" style="width: 105px;">ລະຫັດ</th>
                  <th class="py-3">ຊື່</th>
                  <th class="py-3">ເບີໂທ</th>
                  <th class="py-3">ອີເມວ</th>
                  <th class="py-3 text-center" style="width: 140px;">ເວລາທີ່ສະໝັກ</th>
                  <th class="py-3 text-center" style="width: 140px;">ເຂົ້າລະບົບລ່າສຸດ</th>
                  <th class="py-3 text-center" style="width: 90px;">ສະຖານະ</th>
                  <th class="py-3 text-center" style="width: 100px;">ຈັດການ</th>
                </tr>
              </thead>
              <tbody id="wmRows" style="font-size: 0.92rem;">
                <?php echo $rowsHtml; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Circular Blue Pagination Footer -->
        <div class="card-footer bg-white border-top d-flex flex-column flex-md-row align-items-center justify-content-between py-3 px-3 px-md-4" style="border-radius: 0 0 14px 14px;">
          <div class="text-muted small mb-2 mb-md-0" id="wmPagerInfo">
            <?php echo $pagerData['info']; ?>
          </div>
          <div class="d-flex justify-content-end ml-md-auto">
            <nav aria-label="Member Pagination" id="wmPaginationNav">
              <?php echo $pagerData['pager']; ?>
            </nav>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<script>
(function () {
  var form = document.getElementById('wmFilter');
  var pageInput = document.getElementById('pageInput');
  var rows = document.getElementById('wmRows');
  var pagerInfo = document.getElementById('wmPagerInfo');
  var paginationNav = document.getElementById('wmPaginationNav');
  var searchInput = document.getElementById('memberSearchInput');
  var pageSizeSelect = document.getElementById('pageSizeSelect');
  var btnDateSearch = document.getElementById('btnDateSearch');
  var fromDateInput = document.getElementById('fromDateInput');
  var toDateInput = document.getElementById('toDateInput');
  var timer = null, reqNo = 0;

  function load() {
    var qs = new URLSearchParams(new FormData(form)).toString();
    var my = ++reqNo;
    rows.style.opacity = '.5';
    fetch('index.php?ajax=1&' + qs, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (my !== reqNo) return;
        rows.innerHTML = d.rows;
        pagerInfo.innerHTML = d.info;
        paginationNav.innerHTML = d.pager;
        pageInput.value = d.page;
        var url = 'index.php?' + new URLSearchParams(new FormData(form)).toString();
        history.replaceState(null, '', url);
        rows.querySelectorAll('form').forEach(function (f) { f.action = url; });
      })
      .catch(function () {})
      .then(function () { if (my === reqNo) rows.style.opacity = ''; });
  }

  // ຄົ້ນຫາແບບ Real-time (debounce 300ms)
  searchInput.addEventListener('input', function () {
    clearTimeout(timer);
    pageInput.value = 1;
    timer = setTimeout(load, 300);
  });
  searchInput.addEventListener('search', function () {
    clearTimeout(timer);
    pageInput.value = 1;
    load();
  });

  // ປ່ຽນຈຳນວນແຖວຕໍ່ໜ້າ
  pageSizeSelect.addEventListener('change', function () {
    pageInput.value = 1;
    load();
  });

  // ກົດປຸ່ມຄົ້ນຫາວັນທີ ຫຼື ເລືອກວັນທີຈາກປະຕິທິນ
  var dateTimer = null;
  function onDateChange() {
    clearTimeout(dateTimer);
    dateTimer = setTimeout(function () {
      pageInput.value = 1;
      load();
    }, 120);
  }
  btnDateSearch.addEventListener('click', function () {
    clearTimeout(dateTimer);
    pageInput.value = 1;
    load();
  });
  fromDateInput.addEventListener('change', onDateChange);
  fromDateInput.addEventListener('input', onDateChange);
  toDateInput.addEventListener('change', onDateChange);
  toDateInput.addEventListener('input', onDateChange);
  if (window.jQuery) {
    $('#fromDateInput, #toDateInput').on('change input', onDateChange);
  }

  // ກົດປຸ່ມເລກໜ້າ (Circular pagination)
  paginationNav.addEventListener('click', function (e) {
    var a = e.target.closest('[data-page]');
    if (!a) return;
    e.preventDefault();
    if (a.parentNode.classList.contains('disabled') || a.parentNode.classList.contains('active')) return;
    pageInput.value = a.getAttribute('data-page');
    load();
  });

  // ຕັ້ງລະຫັດຜ່ານໃໝ່ (reset_pw) + ສົ່ງ WhatsApp
  rows.addEventListener('click', function (e) {
    var b = e.target.closest('[data-wm-reset]');
    if (!b) return;
    wlaoConfirm('ຕັ້ງລະຫັດຜ່ານໃໝ່ໃຫ້ ' + b.getAttribute('data-name') + '? (ລະຫັດເກົ່າໃຊ້ບໍ່ໄດ້ອີກ)', false).then(function (ok) {
      if (!ok) return;
      var fd = new FormData();
      fd.append('action', 'reset_pw');
      fd.append('id', b.getAttribute('data-wm-reset'));
      fetch('index.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.success) return;
        var tel = (d.tel || '').replace(/\D+/g, '').replace(/^0/, '856');
        var msg = 'ສະບາຍດີ ' + d.name + ' (' + d.code + ')\nລະຫັດຜ່ານໃໝ່ເຂົ້າສູ່ລະບົບ WlaoDev: ' + d.password + '\nເຂົ້າສູ່ລະບົບດ້ວຍເບີໂທ ຫຼື ອີເມວ ແລ້ວປ່ຽນລະຫັດໄດ້ໃນໜ້າບັນຊີ';
        Swal.fire({
          icon: 'success', title: 'ລະຫັດຜ່ານໃໝ່',
          html: '<div style="font-size:1.6rem;font-weight:800;letter-spacing:.12em;margin:.4rem 0">' + d.password + '</div><div class="small text-muted">ສົ່ງລະຫັດນີ້ໃຫ້ລູກຄ້າ — ລູກຄ້າປ່ຽນເອງໄດ້ໃນໜ້າບັນຊີ</div>',
          showConfirmButton: !!tel, confirmButtonText: '<i class="fab fa-whatsapp"></i> ສົ່ງທາງ WhatsApp', confirmButtonColor: '#16a34a',
          showCancelButton: true, cancelButtonText: 'ປິດ', timer: undefined
        }).then(function (r) { if (r.isConfirmed && tel) window.open('https://wa.me/' + tel + '?text=' + encodeURIComponent(msg), '_blank'); });
      });
    });
  });
})();
</script>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
