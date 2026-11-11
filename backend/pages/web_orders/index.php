<?php
/**
 * ຄຳສັ່ງຊື້ຈາກເວັບໄຊ — ພະນັກງານກວດເບິ່ງສິນຄ້າທີ່ລູກຄ້າຂໍໃບສະເໜີລາຄາ ແລ້ວແຈ້ງລາຄາທາງ WhatsApp.
 * ສະຖານະ 6 ຂັ້ນ + ຍົກເລີກ (ລູກຄ້າສະມາຊິກເຫັນໃນ frontend/pages/my_order.php):
 *   ໃໝ່ (pending) → ຢືນຢັນ/ຮັບເງິນ (confirmed) → ແພັກ (packing) → ຈັດສົ່ງ (shipped) → ຮອດສາຂາ (arrived) → ສຳເລັດ (completed)
 *   ກົດ "ແຈ້ງລາຄາທາງ WhatsApp" ຢູ່ອໍເດີໃໝ່ = ກາຍເປັນ confirmed ເອງ; ເລືອກສະຖານະອື່ນໃນ dropdown ຂອງແຕ່ລະແຖວ.
 *   ເລກໃບຝາກເຄື່ອງ (tracking_no): action=tracking. ສະມາຊິກແນບສະລິບ → ຮູບສະແດງໃນແຖວ (slip_img).
 * ທຸກການປ່ຽນບັນທຶກລົງ w_web_order_status_log (ລູກຄ້າເຫັນໃນໜ້າບັນຊີ frontend).
 * ປຸ່ມທັງໝົດສົ່ງ POST ດ້ວຍ fetch (ajax=1 → JSON) ແລ້ວໂຫຼດແຖວໃໝ່ ບໍ່ຕ້ອງໂຫຼດໜ້າ.
 * ຄົ້ນຫາສົດ + ແບ່ງໜ້າ 10 ລາຍການ: ?ajax=1 → JSON {rows, pager, total, page}. ສິດ: ໂມດູນ sale ຫຼື customers.
 *
 * ແຜນຜັງ (ລຳດັບ require):
 *   - partials/rows.php : ສ້າງ HTML ແຖວຕາຕະລາງ ($woRows) + ແຖບແບ່ງໜ້າ ($woPager)
 *   - partials/stats.php: ແຖວບັດສະຖິຕິ (ອໍເດີ, ຍອດຂາຍອອນລາຍ, ສະມາຊິກໃໝ່) ເທິງຕາຕະລາງ
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../config/db.php';
if (empty($_SESSION['user_id']) || (!hasPermission('sale') && !hasPermission('customers'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// ສະຖານະທີ່ສະແດງ [ປ້າຍ, badge] — 6 ຂັ້ນ + ຍົກເລີກ (ຄືກັບ ORDER_STEPS ໃນ frontend/models/orders.php)
$statusLabels = [
    'pending'   => ['ໃໝ່ (ລໍຖ້າຢືນຢັນ)', 'badge-warning'],
    'confirmed' => ['ຢືນຢັນ / ຮັບເງິນແລ້ວ', 'badge-info'],
    'packing'   => ['ກຳລັງແພັກສິນຄ້າ', 'badge-info'],
    'shipped'   => ['ກຳລັງຈັດສົ່ງ', 'badge-primary'],
    'arrived'   => ['ເຄື່ອງຮອດສາຂາແລ້ວ', 'badge-primary'],
    'completed' => ['ສຳເລັດ', 'badge-success'],
    'cancelled' => ['ຍົກເລີກ', 'badge-secondary'],
];
$legacyAsConfirmed = [];   // ເມື່ອກ່ອນ packing/shipped/arrived ສະແດງເປັນ confirmed — ດຽວນີ້ສະແດງແທ້ (v38)   // ຂໍ້ມູນເກົ່າ → ນັບເປັນ "ແຈ້ງລາຄາແລ້ວ"

// ປ່ຽນສະຖານະ: action=notified (ກົດແຈ້ງ WhatsApp — ປ່ຽນສະເພາະອໍເດີໃໝ່) | set_status (ທຸກສະຖານະ) | tracking (ເລກໃບຝາກເຄື່ອງ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $act = (string)($_POST['action'] ?? '');
    $staff = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? '');
    $cur = $pdo->prepare("SELECT status FROM w_web_orders WHERE id = ?");
    $cur->execute([$oid]);
    $row = $oid > 0 ? $cur->fetch(PDO::FETCH_ASSOC) : null;
    $new = null;
    $note = null;
    if ($row && $act === 'notified' && $row['status'] === 'pending') {
        $new = 'confirmed';
        $note = 'ແຈ້ງລາຄາທາງ WhatsApp';
    } elseif ($row && $act === 'set_status' && isset($statusLabels[$_POST['status'] ?? ''])) {
        $new = (string)$_POST['status'];
    } elseif ($row && $act === 'receipt') {
        // ຮູບບິນຮັບເຄື່ອງຂອງບໍລິສັດຂົນສົ່ງ (ແທນເລກໃບຝາກເຄື່ອງ): JPG/PNG/WEBP ≤ 8MB → backend/assets/web_receipts/receipt_*.ext (ແຍກຈາກສະລິບ)
        $rf = $_FILES['receipt'] ?? null;
        $rErr = null;
        $rMime = ($rf && ($rf['error'] ?? 1) === UPLOAD_ERR_OK) ? ((new finfo(FILEINFO_MIME_TYPE))->file($rf['tmp_name']) ?: '') : '';
        $rExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$rMime] ?? null;
        if (!$rExt) {
            $rErr = 'ຮອງຮັບສະເພາະຮູບ JPG, PNG ຫຼື WEBP';
        } elseif ($rf['size'] > 8 * 1024 * 1024) {
            $rErr = 'ຮູບໃຫຍ່ເກີນ 8 MB';
        } else {
            $rDir = dirname(__DIR__, 2) . '/assets/web_receipts';
            if (!is_dir($rDir)) @mkdir($rDir, 0775, true);
            if (!is_file($rDir . '/.htaccess')) @file_put_contents($rDir . '/.htaccess', "Require all denied\n# web_receipts: ເບິ່ງຜ່ານ backend/pages/web_orders/slip.php?type=receipt ຫຼື frontend/api/slip.php?type=receipt\n");
            $rName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $rExt;
            if (!@move_uploaded_file($rf['tmp_name'], $rDir . '/' . $rName)) {
                $rErr = 'ບັນທຶກຮູບບໍ່ສຳເລັດ';
            } else {
                $old = $pdo->prepare("SELECT receipt_img FROM w_web_orders WHERE id = ?");
                $old->execute([$oid]);
                $oldRel = (string)$old->fetchColumn();
                if ($oldRel !== '' && preg_match('#^backend/assets/web_(receipts|slips)/receipt_#', $oldRel)) @unlink(dirname(__DIR__, 3) . '/' . $oldRel);   // ລຶບບິນເກົ່າ
                $pdo->prepare("UPDATE w_web_orders SET receipt_img = ?, receipt_at = NOW() WHERE id = ?")->execute(['backend/assets/web_receipts/' . $rName, $oid]);
                $pdo->prepare("INSERT INTO w_web_order_status_log (order_id, status, note, staff_name) VALUES (?, ?, ?, ?)")
                    ->execute([$oid, $row['status'], 'ແນບບິນຮັບເຄື່ອງ', $staff ?: null]);
                webMailOrderStatus($pdo, $oid, $row['status'], 'receipt');   // ກະດິ່ງ + ອີເມວ "ສົ່ງບິນຮັບເຄື່ອງແລ້ວ"
            }
        }
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $rErr === null, 'message' => $rErr]);
            exit();
        }
    } elseif ($row && $act === 'tracking') {
        $trk = mb_substr(trim((string)($_POST['tracking_no'] ?? '')), 0, 100);
        $pdo->prepare("UPDATE w_web_orders SET tracking_no = ? WHERE id = ?")->execute([$trk !== '' ? $trk : null, $oid]);
        $waUrl = '';
        if ($trk !== '') {
            $pdo->prepare("INSERT INTO w_web_order_status_log (order_id, status, note, staff_name) VALUES (?, ?, ?, ?)")
                ->execute([$oid, $row['status'], 'ເລກໃບຝາກເຄື່ອງ: ' . $trk, $staff ?: null]);
            webMailOrderStatus($pdo, $oid, $row['status'], $trk);   // ອີເມວແຈ້ງເລກໃບຝາກເຄື່ອງ

            // ສ້າງ WhatsApp URL ບິນຂົນສົ່ງ ຄົບຊຸດ ໃຫ້ພະນັກງານກົດສົ່ງໄດ້ທັນທີ
            $ordQ = $pdo->prepare("SELECT o.*, m.member_no FROM w_web_orders o LEFT JOIN w_web_members m ON m.id = o.member_id WHERE o.id = ?");
            $ordQ->execute([$oid]);
            $oInfo = $ordQ->fetch(PDO::FETCH_ASSOC);
            if ($oInfo) {
                $itQ = $pdo->prepare("SELECT * FROM w_web_order_items WHERE order_id = ? ORDER BY id ASC");
                $itQ->execute([$oid]);
                $oItems = $itQ->fetchAll(PDO::FETCH_ASSOC);
                $lines = [];
                $sum = 0.0;
                foreach ($oItems as $it) {
                    $qty = (int)$it['qty'];
                    $u = (float)$it['price'];
                    $sub = $u * $qty;
                    $sum += $sub;
                    $lines[] = '- ' . $it['product_name'] . ' x' . $qty . ($u > 0 ? ' = ' . number_format($sub) . ' ₭' : '');
                }
                if ((float)$oInfo['total_amount'] > 0) $sum = (float)$oInfo['total_amount'];
                $rawTel = preg_replace('/\D+/', '', (string)$oInfo['customer_tel']);
                if (strpos($rawTel, '0') === 0) $rawTel = '856' . substr($rawTel, 1);
                elseif (strpos($rawTel, '20') === 0 || strpos($rawTel, '30') === 0) $rawTel = '856' . $rawTel;

                $courier = !empty($oInfo['courier_name']) ? $oInfo['courier_name'] . (!empty($oInfo['courier_branch']) ? ' (ສາຂາ: ' . $oInfo['courier_branch'] . ')' : '') : 'ຂົນສົ່ງ';
                $addr = !empty($oInfo['customer_address']) ? "\n📍 ທີ່ຢູ່ຈັດສົ່ງ: " . $oInfo['customer_address'] : '';
                $msg = "📦 *ບິນຂົນສົ່ງສິນຄ້າ / ແຈ້ງເລກໃບຝາກເຄື່ອງ*\n"
                    . "🏢 *ຮ້ານ WlaoDev POS*\n"
                    . "--------------------------------\n"
                    . "📋 ເລກທີຄຳສັ່ງຊື້: #" . $oInfo['order_no'] . "\n"
                    . "👤 ລູກຄ້າ: " . $oInfo['customer_name'] . "\n"
                    . "📞 ເບີໂທ: " . $oInfo['customer_tel'] . $addr . "\n"
                    . "🚚 ຂົນສົ່ງ: " . $courier . "\n"
                    . "🔢 *ເລກໃບຝາກເຄື່ອງ (Tracking):* " . $trk . "\n"
                    . "--------------------------------\n"
                    . "📦 *ລາຍການສິນຄ້າ:*\n" . implode("\n", $lines) . "\n"
                    . ($sum > 0 ? "💰 ລວມທັງໝົດ: " . number_format($sum) . " ₭\n" : "")
                    . "--------------------------------\n"
                    . "✅ ສິນຄ້າໄດ້ຈັດສົ່ງອອກແລ້ວ! ທ່ານສາມາດນຳເລກໃບຝາກນີ້ໄປກວດສອບການຈັດສົ່ງ ຫຼື ລໍຖ້າຮັບສາຍຈາກຂົນສົ່ງ.\n"
                    . "🙏 ຂອບໃຈທີ່ໃຊ້ບໍລິການ WlaoDev!";
                $waUrl = 'https://wa.me/' . $rawTel . '?text=' . rawurlencode($msg);
            }
        }
    }
    if ($new !== null && $new !== $row['status']) {
        if ($new === 'completed') {
            // ສຳເລັດ = ລາຍຮັບຈິງ: ບັນທຶກລາຄາຕາມລະບົບ (w_products.price, ສາຂາຂອງອໍເດີກ່ອນ) ລົງລາຍການທີ່ຍັງເປັນ 0
            // ແລະ ຍອດລວມ total_amount → ລາຍງານ "ເວັບໄຊ vs ໜ້າຮ້ານ" (pages/reports/channel_report.php) ໃຊ້ຕົວເລກນີ້
            $its = $pdo->prepare("SELECT i.id, i.qty, i.price,
                    COALESCE((SELECT p.price FROM w_products p WHERE p.product_id = i.product_id
                              ORDER BY (p.store_id = o.store_id) DESC, p.price DESC LIMIT 1), 0) AS sys_price
                FROM w_web_order_items i JOIN w_web_orders o ON o.id = i.order_id WHERE i.order_id = ?");
            $its->execute([$oid]);
            $sum = 0.0;
            $upd = $pdo->prepare("UPDATE w_web_order_items SET price = ?, subtotal = ? WHERE id = ?");
            foreach ($its->fetchAll(PDO::FETCH_ASSOC) as $it) {
                $unit = (float)$it['price'] > 0 ? (float)$it['price'] : (float)$it['sys_price'];
                $sub = $unit * (int)$it['qty'];
                if ((float)$it['price'] <= 0 && $unit > 0) $upd->execute([$unit, $sub, $it['id']]);
                $sum += $sub;
            }
            if ($sum > 0) $pdo->prepare("UPDATE w_web_orders SET total_amount = ? WHERE id = ?")->execute([$sum, $oid]);
        }
        $pdo->prepare("UPDATE w_web_orders SET status = ? WHERE id = ?")->execute([$new, $oid]);
        webMailOrderStatus($pdo, $oid, $new);   // ອີເມວແຈ້ງສະມາຊິກ (config/helpers/web_mail.php)
        $pdo->prepare("INSERT INTO w_web_order_status_log (order_id, status, note, staff_name) VALUES (?, ?, ?, ?)")
            ->execute([$oid, $new, $note, $staff ?: null]);
    }
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'     => (bool)$row,
            'status'      => $new ?? ($row['status'] ?? null),
            'tracking_no' => $trk ?? null,
            'wa_url'      => $waUrl ?? null
        ]);
        exit();
    }
    $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'q' => $_GET['q'] ?? '', 'page' => $_GET['page'] ?? '']));
    header('Location: index.php' . ($qs ? '?' . $qs : ''));
    exit();
}

$statusFilter = trim((string)($_GET['status'] ?? ''));
if (!isset($statusLabels[$statusFilter])) $statusFilter = '';
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($statusFilter !== '') {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($q !== '') {
    // ເລກບິນ / ຊື່ / ເບີໂທ / ຊື່ສິນຄ້າ
    $where[] = '(o.order_no LIKE ? OR o.customer_name LIKE ? OR o.customer_tel LIKE ?
                 OR EXISTS (SELECT 1 FROM w_web_order_items i WHERE i.order_id = o.id AND i.product_name LIKE ?))';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
$whereSql = implode(' AND ', $where);

$orders = [];
$itemsByOrder = [];
$logByOrder = [];
$sysPrice = [];   // [product_id][store_id] => ລາຄາຂາຍໃນລະບົບ
$total = 0;
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM w_web_orders o WHERE $whereSql");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);

    // member_no: ອໍເດີຂອງສະມາຊິກເວັບ (w_web_orders.member_id, v38)
    $st = $pdo->prepare("SELECT o.*, m.member_no FROM w_web_orders o LEFT JOIN w_web_members m ON m.id = o.member_id WHERE $whereSql ORDER BY o.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
    $st->execute($params);
    $orders = $st->fetchAll(PDO::FETCH_ASSOC);

    if ($orders) {
        $ids = array_column($orders, 'id');
        $inMarks = implode(',', array_fill(0, count($ids), '?'));
        $itSt = $pdo->prepare("SELECT * FROM w_web_order_items WHERE order_id IN ($inMarks) ORDER BY id ASC");
        $itSt->execute($ids);
        foreach ($itSt->fetchAll(PDO::FETCH_ASSOC) as $it) {
            $itemsByOrder[(int)$it['order_id']][] = $it;
        }
        // ລາຄາຂາຍປັດຈຸບັນຈາກລະບົບ (w_products.price) — ອໍເດີເວັບບັນທຶກລາຄາ 0 (ໂໝດໃບສະເໜີລາຄາ),
        // ຈຶ່ງໃຊ້ລາຄານີ້ແຈ້ງລູກຄ້າ. ສິນຄ້າດຽວກັນມີຫຼາຍສາຂາ → ເອົາສາຂາຂອງອໍເດີກ່ອນ.
        $pids = [];
        foreach ($itemsByOrder as $its) foreach ($its as $it) if ((int)$it['product_id'] > 0) $pids[(int)$it['product_id']] = true;
        if ($pids) {
            $pm = implode(',', array_fill(0, count($pids), '?'));
            $pSt = $pdo->prepare("SELECT product_id, store_id, price FROM w_products WHERE product_id IN ($pm)");
            $pSt->execute(array_keys($pids));
            foreach ($pSt->fetchAll(PDO::FETCH_ASSOC) as $pr) {
                $sysPrice[(int)$pr['product_id']][(int)$pr['store_id']] = (float)$pr['price'];
            }
        }
        $lgSt = $pdo->prepare("SELECT * FROM w_web_order_status_log WHERE order_id IN ($inMarks) ORDER BY id DESC");
        $lgSt->execute($ids);
        foreach ($lgSt->fetchAll(PDO::FETCH_ASSOC) as $lg) {
            $logByOrder[(int)$lg['order_id']][] = $lg;
        }
    }
} catch (Throwable $e) {
    error_log('web_orders: ' . $e->getMessage());
}
$pages = $pages ?? 1;

require __DIR__ . '/partials/rows.php';   // → $woRows, $woPager

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['rows' => $woRows, 'pager' => $woPager, 'total' => $total, 'page' => $page], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../../layouts/header.php';
?>
<?php echo themeCssLink('web_orders.css'); ?>
<div class="content-wrapper bg-light">
  <section class="content pt-3">
    <div class="container-fluid">
      <div class="wo-head">
        <h1 class="wo-title">ຄຳສັ່ງຊື້ຈາກເວັບໄຊ <small class="text-muted">(<span id="woTotal"><?php echo number_format($total); ?></span> ລາຍການ)</small></h1>
        <form method="get" class="wo-toolbar" id="woFilter" autocomplete="off">
          <div class="wo-search">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="ຄົ້ນຫາ ເລກບິນ / ຊື່ / ເບີໂທ / ສິນຄ້າ">
          </div>
          <select name="status" class="form-control">
            <option value="">ທຸກສະຖານະ</option>
            <?php foreach ($statusLabels as $k => [$lbl]): ?>
              <option value="<?php echo $k; ?>"<?php echo $statusFilter === $k ? ' selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="page" value="<?php echo $page; ?>">
        </form>
      </div>
<?php require __DIR__ . '/partials/stats.php'; ?>

      <div class="card wo-card">
        <div class="table-responsive wo-table-responsive">
          <table class="table table-hover mb-0 wo-table">
            <thead>
              <tr>
                <th style="width: 140px; min-width: 140px;">ເລກຄຳສັ່ງຊື້</th>
                <th style="width: 200px; min-width: 200px;">ລູກຄ້າ</th>
                <th style="width: 80px; min-width: 80px; text-align: center;">ສະລິບ</th>
                <th style="min-width: 270px;">ສິນຄ້າ & ຍອດລວມ</th>
                <th style="width: 150px; min-width: 150px;">ສະຖານະ</th>
                <th style="width: 260px; min-width: 260px;">ເລກໃບຝາກເຄື່ອງ / ບິນ</th>
                <th style="width: 150px; min-width: 150px; text-align: center;">ຈັດການ</th>
              </tr>
            </thead>
            <tbody id="woRows"><?php echo $woRows; ?></tbody>
          </table>
        </div>
        <div id="woPager"><?php echo $woPager; ?></div>
      </div>
    </div>
  </section>
</div>
<script>
// ຄົ້ນຫາສົດ: ພິມ (ລໍຖ້າ 300ms) / ປ່ຽນສະຖານະ / ກົດເລກໜ້າ → ດຶງແຖວໃໝ່ຈາກ ?ajax=1 ໂດຍບໍ່ໂຫຼດໜ້າ; URL ອັບເດດນຳ
(function () {
  var form = document.getElementById('woFilter');
  var qInput = form.querySelector('[name=q]');
  var pageInput = form.querySelector('[name=page]');
  var timer = null, reqNo = 0;
  function load() {
    var params = new URLSearchParams(new FormData(form));
    var my = ++reqNo;
    document.getElementById('woRows').style.opacity = '.5';
    fetch('index.php?ajax=1&' + params.toString(), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (my !== reqNo) return;   // ມີຄຳຂໍໃໝ່ກວ່າແລ້ວ
        document.getElementById('woRows').innerHTML = d.rows;
        document.getElementById('woPager').innerHTML = d.pager;
        document.getElementById('woTotal').textContent = Number(d.total).toLocaleString();
        pageInput.value = d.page;
        history.replaceState(null, '', 'index.php?' + new URLSearchParams(new FormData(form)).toString());
      })
      .catch(function () {})
      .then(function () { if (my === reqNo) document.getElementById('woRows').style.opacity = ''; });
  }
  qInput.addEventListener('input', function () {
    clearTimeout(timer);
    pageInput.value = 1;
    timer = setTimeout(load, 300);
  });
  form.querySelector('[name=status]').addEventListener('change', function () { pageInput.value = 1; load(); });
  form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); pageInput.value = 1; load(); });
  // ປ່ຽນສະຖານະແບບບໍ່ໂຫຼດໜ້າ ແລ້ວວາດແຖວໃໝ່
  function postStatus(data) {
    var fd = new FormData();
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    fd.append('ajax', '1');
    return fetch('index.php', { method: 'POST', body: fd, credentials: 'same-origin', keepalive: true })
      .then(function (r) { return r.json(); }).then(function (d) {
        load();
        return d;
      }).catch(function () {});
  }
  // ເລືອກສະຖານະໃນ dropdown → ບັນທຶກທັນທີ; ກົດ "ບັນທຶກ" ຂ້າງເລກໃບຝາກເຄື່ອງ
  document.getElementById('woRows').addEventListener('change', function (e) {
    var rfile = e.target.closest('[data-wo-receipt-file]');
    if (rfile && rfile.files[0]) {
      var fd = new FormData();
      fd.append('action', 'receipt'); fd.append('order_id', rfile.getAttribute('data-wo-receipt-file')); fd.append('receipt', rfile.files[0]); fd.append('ajax', '1');
      var box = rfile.closest('.wo-receipt') || rfile.closest('.wo-track-tools'); if (box) box.classList.add('is-busy');
      fetch('index.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.success) { Swal.fire({ icon: 'error', text: d.message || 'ອັບໂຫຼດບໍ່ສຳເລັດ' }); if (box) box.classList.remove('is-busy'); return; }
        Swal.fire({ icon: 'success', title: 'ແນບບິນຮັບເຄື່ອງແລ້ວ', text: 'ລູກຄ້າເຫັນບິນໃນໜ້າຕິດຕາມອໍເດີ', showConfirmButton: false, timer: 1500 });
        load();
      }).catch(function () { if (box) box.classList.remove('is-busy'); });
      return;
    }
    var sel = e.target.closest('[data-wo-select]');
    if (!sel) return;
    sel.disabled = true;
    postStatus({ action: 'set_status', status: sel.value, order_id: sel.getAttribute('data-wo-select') }).then(function () {
      Swal.fire({ icon: 'success', title: 'ອັບເດດສະຖານະແລ້ວ', showConfirmButton: false, timer: 1200 });
    });
  });
  document.getElementById('woRows').addEventListener('click', function (e) {
    var slip = e.target.closest('[data-wo-slip]');   // ກົດສະລິບ → ຂະຫຍາຍຮູບໃນໜ້າ (ບໍ່ເປີດແທັບໃໝ່)
    if (slip && typeof window.viewImagePreview === 'function') { e.preventDefault(); window.viewImagePreview(slip.getAttribute('href'), 'ສະລິບໂອນເງິນ'); return; }
    var rc = e.target.closest('[data-wo-receipt]');   // ອັບໂຫຼດບິນຮັບເຄື່ອງ → ເປີດເລືອກຮູບ
    if (rc) { rc.parentNode.querySelector('input[type=file]').click(); return; }
    var rv = e.target.closest('[data-wo-receipt-view]');   // ເບິ່ງບິນຮັບເຄື່ອງ (lightbox)
    if (rv && typeof window.viewImagePreview === 'function') { e.preventDefault(); window.viewImagePreview(rv.getAttribute('href'), 'ບິນຮັບເຄື່ອງ'); return; }
    var tbtn = e.target.closest('[data-wo-track-toggle]'); // ເປີດ/ປິດ ຊ່ອງແກ້ໄຂເລກໃບຝາກເຄື່ອງ
    if (tbtn) {
      var tid = tbtn.getAttribute('data-wo-track-toggle');
      var tbox = document.getElementById('woTrackEdit_' + tid);
      if (tbox) {
        var isHidden = tbox.style.display === 'none';
        tbox.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
          var tinp = tbox.querySelector('input');
          if (tinp) { tinp.focus(); tinp.select(); }
        }
      }
      return;
    }
    var trk = e.target.closest('[data-wo-track]');
    if (trk) {
      var box = trk.closest('.wo-track-box') || trk.closest('td');
      var inp = box ? box.querySelector('input') : null;
      if (!inp) return;
      var val = inp.value.trim();
      trk.disabled = true;
      postStatus({ action: 'tracking', tracking_no: val, order_id: trk.getAttribute('data-wo-track') }).then(function (d) {
        trk.disabled = false;
        if (val !== '') {
          // ຖາມພະນັກງານວ່າຕ້ອງການສົ່ງບິນຂົນສົ່ງໃຫ້ລູກຄ້າທາງ WhatsApp ເລີຍບໍ່
          Swal.fire({
            icon: 'success',
            title: 'ບັນທຶກເລກໃບຝາກເຄື່ອງແລ້ວ!',
            html: '<div style="background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0; margin:10px 0; text-align:left;">' +
                  '<div style="font-size:12px; color:#64748b;">ເລກໃບຝາກເຄື່ອງ:</div>' +
                  '<div style="font-size:18px; font-weight:800; color:#2563eb; letter-spacing:1px; font-family:monospace;">' + val + '</div>' +
                  '</div>' +
                  '<p style="margin-top:12px; font-size:14px; color:#334155;">ຕ້ອງການ <b>ສົ່ງບິນຂົນສົ່ງ</b> ໃຫ້ລູກຄ້າທາງ WhatsApp ເລີຍບໍ່?</p>',
            showCancelButton: true,
            confirmButtonColor: '#25D366',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fab fa-whatsapp"></i> ສົ່ງບິນຂົນສົ່ງ (WhatsApp)',
            cancelButtonText: 'ປິດ'
          }).then(function (res) {
            if (res.isConfirmed && d && d.wa_url) {
              window.open(d.wa_url, '_blank', 'noopener');
            }
          });
        } else {
          Swal.fire({ icon: 'success', title: 'ບັນທຶກແລ້ວ', showConfirmButton: false, timer: 1200 });
        }
      });
      return;
    }
    // ກົດແຈ້ງ WhatsApp: ປ່ອຍໃຫ້ລິ້ງເປີດ WhatsApp ຕາມປົກກະຕິ + ບັນທຶກ "ແຈ້ງລາຄາແລ້ວ" ເອງ (ສະເພາະອໍເດີໃໝ່)
    var wa = e.target.closest('[data-wo-notify]');
    if (wa) { postStatus({ action: 'notified', order_id: wa.getAttribute('data-wo-notify') }); return; }
    var b = e.target.closest('[data-wo-status]');
    if (!b) return;
    var status = b.getAttribute('data-wo-status');
    wlaoConfirm(b.getAttribute('data-confirm'), status === 'cancelled').then(function (ok) {
      if (!ok) return;
      b.disabled = true;
      postStatus({ action: 'set_status', status: status, order_id: b.getAttribute('data-wo-id') }).then(function () {
        Swal.fire({ icon: 'success', title: status === 'completed' ? 'ອໍເດີສຳເລັດແລ້ວ' : (status === 'cancelled' ? 'ຍົກເລີກອໍເດີແລ້ວ' : 'ເປີດອໍເດີຄືນແລ້ວ'), showConfirmButton: false, timer: 1400 });
      });
    });
  });
  document.getElementById('woPager').addEventListener('click', function (e) {
    var a = e.target.closest('[data-page]');
    if (!a) return;
    e.preventDefault();
    if (a.parentNode.classList.contains('disabled') || a.parentNode.classList.contains('active')) return;
    pageInput.value = a.getAttribute('data-page');
    load();
  });
})();
</script>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
