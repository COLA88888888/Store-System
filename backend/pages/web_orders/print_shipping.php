<?php
/**
 * ໃບສົ່ງເຄື່ອງ / ບິນຂົນສົ່ງດ່ວນ (Express Logistics Parcel Waybill)
 * ອອກແບບໃໝ່ ມາດຕະຖານຂົນສົ່ງສາກົນ & ຂົນສົ່ງລາວ (Anousith, HAL, Mixay, J&T)
 * ຮອງຮັບທັງເຄື່ອງພິມສະຕິກເກີ 4x6 ນິ້ວ (Thermal 100x150mm) ແລະ ເຈ້ຍ A5 / A4.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('sale') && !hasPermission('customers'))) {
    exit("Access Denied");
}

$oid = (int)($_GET['id'] ?? 0);
if ($oid <= 0) {
    exit("Invalid Order ID");
}

// ດຶງຂໍ້ມູນອໍເດີ
$st = $pdo->prepare("SELECT o.*, m.member_no FROM w_web_orders o LEFT JOIN w_web_members m ON m.id = o.member_id WHERE o.id = ?");
$st->execute([$oid]);
$order = $st->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit("ບໍ່ພົບຂໍ້ມູນຄຳສັ່ງຊື້ #" . htmlspecialchars((string)$oid));
}

// ດຶງລາຍການສິນຄ້າ
$itSt = $pdo->prepare("SELECT * FROM w_web_order_items WHERE order_id = ? ORDER BY id ASC");
$itSt->execute([$oid]);
$items = $itSt->fetchAll(PDO::FETCH_ASSOC);

// ດຶງລາຄາລະບົບ ຖ້າລາຄາໃນອໍເດີເປັນ 0
$orderStore = (int)($order['store_id'] ?? 1);
$pids = [];
foreach ($items as $it) {
    if ((int)$it['product_id'] > 0 && (float)$it['price'] <= 0) {
        $pids[(int)$it['product_id']] = true;
    }
}
$sysPrice = [];
if ($pids) {
    $pm = implode(',', array_fill(0, count($pids), '?'));
    $pSt = $pdo->prepare("SELECT product_id, store_id, price FROM w_products WHERE product_id IN ($pm)");
    $pSt->execute(array_keys($pids));
    foreach ($pSt->fetchAll(PDO::FETCH_ASSOC) as $pr) {
        $sysPrice[(int)$pr['product_id']][(int)$pr['store_id']] = (float)$pr['price'];
    }
}

// ຂໍ້ມູນຮ້ານ
$compStmt = $pdo->query("SELECT * FROM w_tbcompanyinfo LIMIT 1");
$company = $compStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$storeStmt = $pdo->prepare("SELECT * FROM w_tbstore WHERE store_id = ?");
$storeStmt->execute([$orderStore]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$shopName = !empty($store['store_name']) ? $store['store_name'] : (!empty($company['com_name_la']) ? $company['com_name_la'] : 'WlaoDev Minimart POS');
$shopTel = !empty($store['tel']) ? $store['tel'] : (!empty($company['com_tel']) ? $company['com_tel'] : '020 9999xxxx');
$shopAddr = !empty($store['address']) ? $store['address'] : (!empty($company['com_address']) ? $company['com_address'] : 'ນະຄອນຫຼວງວຽງຈັນ');

// ຄຳນວນຍອດລວມ ແລະ ລາຍການ
$sum = 0.0;
$itemRows = [];
foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $unit = (float)$it['price'];
    if ($unit <= 0 && isset($sysPrice[$pid])) {
        $unit = $sysPrice[$pid][$orderStore] ?? max($sysPrice[$pid]);
    }
    $qty = (int)$it['qty'];
    $sub = $unit * $qty;
    $sum += $sub;
    $itemRows[] = [
        'name' => $it['product_name'],
        'qty'  => $qty,
        'unit' => $unit,
        'sub'  => $sub,
    ];
}
if ((float)$order['total_amount'] > 0) {
    $sum = (float)$order['total_amount'];
}

// ລະບຸສີ ແລະ ໂລໂກ້ຂົນສົ່ງ
$cCode = strtolower(trim((string)($order['courier_code'] ?? '')));
$cName = (string)($order['courier_name'] ?? '');
if ($cCode === '' && $cName !== '') {
    if (stripos($cName, 'anousith') !== false || mb_strpos($cName, 'ອະນຸສິດ') !== false) $cCode = 'anousith';
    elseif (stripos($cName, 'hal') !== false || mb_strpos($cName, 'ຮຸ່ງອາລຸນ') !== false) $cCode = 'hal';
    elseif (stripos($cName, 'mixay') !== false || mb_strpos($cName, 'ມີໄຊ') !== false) $cCode = 'mixay';
    elseif (stripos($cName, 'j&t') !== false || stripos($cName, 'jnt') !== false) $cCode = 'jnt';
    elseif (stripos($cName, 'zto') !== false) $cCode = 'zto';
}

$courierBrandName = $cName ?: 'ຂົນສົ່ງດ່ວນ (EXPRESS)';
$courierColor = '#1e3a8a';
$courierAccent = '#2563eb';

if ($cCode === 'anousith') {
    $courierBrandName = 'ANOUSITH EXPRESS · ອະນຸສິດ';
    $courierColor = '#E11D2E';
    $courierAccent = '#1E3A8A';
} elseif ($cCode === 'hal') {
    $courierBrandName = 'HAL LOGISTICS · ຮຸ່ງອາລຸນ';
    $courierColor = '#FF6B00';
    $courierAccent = '#003399';
} elseif ($cCode === 'mixay') {
    $courierBrandName = 'MIXAY EXPRESS · ມີໄຊ';
    $courierColor = '#059669';
    $courierAccent = '#D97706';
} elseif ($cCode === 'jnt') {
    $courierBrandName = 'J&T EXPRESS';
    $courierColor = '#DC2626';
    $courierAccent = '#111827';
}

$courierLogo = '';
if ($cCode !== '') {
    $cFile = dirname(__DIR__, 2) . '/assets/img/couriers/' . $cCode . '.jpg';
    if (is_file($cFile)) {
        $courierLogo = '../../assets/img/couriers/' . $cCode . '.jpg';
    } else {
        $cFileSvg = dirname(__DIR__, 2) . '/assets/img/couriers/' . $cCode . '.svg';
        if (is_file($cFileSvg)) $courierLogo = '../../assets/img/couriers/' . $cCode . '.svg';
    }
}

// ຂໍ້ຄວາມ WhatsApp
$rawTel = preg_replace('/\D+/', '', (string)$order['customer_tel']);
if (strpos($rawTel, '0') === 0) $rawTel = '856' . substr($rawTel, 1);
elseif (strpos($rawTel, '20') === 0 || strpos($rawTel, '30') === 0) $rawTel = '856' . $rawTel;

$courierText = !empty($order['courier_name']) ? $order['courier_name'] . (!empty($order['courier_branch']) ? " (ສາຂາ: {$order['courier_branch']})" : '') : 'ຂົນສົ່ງ';
$addrText = !empty($order['customer_address']) ? "\n📍 ທີ່ຢູ່ຈັດສົ່ງ: " . $order['customer_address'] : '';
$trackingText = !empty($order['tracking_no']) ? $order['tracking_no'] : ($order['order_no']);

$itemLines = implode("\n", array_map(fn($l) => '- ' . $l['name'] . ' x' . $l['qty'] . ($l['unit'] > 0 ? ' = ' . number_format($l['sub']) . ' ₭' : ''), $itemRows));

$waMsg = "📦 *ບິນຂົນສົ່ງສິນຄ້າ / ແຈ້ງເລກໃບຝາກເຄື່ອງ*\n"
    . "🏢 *ຮ້ານ " . $shopName . "*\n"
    . "--------------------------------\n"
    . "📋 ເລກທີຄຳສັ່ງຊື້: #" . $order['order_no'] . "\n"
    . "👤 ລູກຄ້າ: " . $order['customer_name'] . "\n"
    . "📞 ເບີໂທ: " . $order['customer_tel'] . $addrText . "\n"
    . "🚚 ຂົນສົ່ງ: " . $courierText . "\n"
    . "🔢 *ເລກໃບຝາກເຄື່ອງ (Tracking):* " . $trackingText . "\n"
    . "--------------------------------\n"
    . "📦 *ລາຍການສິນຄ້າ:*\n" . $itemLines . "\n"
    . ($sum > 0 ? "💰 ລວມທັງໝົດ: " . number_format($sum) . " ₭\n" : "")
    . "--------------------------------\n"
    . "✅ ສິນຄ້າໄດ້ຈັດສົ່ງອອກແລ້ວ! ທ່ານສາມາດນຳເລກໃບຝາກນີ້ໄປກວດສອບການຈັດສົ່ງ ຫຼື ລໍຖ້າຮັບສາຍຈາກຂົນສົ່ງ.\n"
    . "🙏 ຂອບໃຈທີ່ໃຊ້ບໍລິການ " . $shopName . "!";

$waUrl = 'https://wa.me/' . $rawTel . '?text=' . rawurlencode($waMsg);
$barcodeVal = !empty($order['tracking_no']) ? $order['tracking_no'] : $order['order_no'];
?>
<!DOCTYPE html>
<html lang="lo">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ໃບສົ່ງເຄື່ອງ #<?php echo htmlspecialchars((string)$order['order_no']); ?> - <?php echo htmlspecialchars($courierBrandName); ?></title>
  <link rel="stylesheet" href="../../assets/css/local-font.css">
  <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
  <script src="../../assets/js/JsBarcode.all.min.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Noto Sans Lao', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #e2e8f0;
      color: #0f172a;
      font-size: 13px;
      line-height: 1.45;
      padding: 24px 16px;
      min-height: 100vh;
    }

    /* Floating Toolbar (ຈໍຄອມພິວເຕີ) */
    .top-actions {
      max-width: 680px;
      margin: 0 auto 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
      padding: 12px 18px;
      border-radius: 12px;
      border: 1px solid #cbd5e1;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      border-radius: 8px;
      font-size: 13.5px;
      font-weight: 700;
      text-decoration: none;
      border: 0;
      cursor: pointer;
      transition: all .15s ease;
      white-space: nowrap;
    }
    .btn-print { background: #2563eb; color: #ffffff; box-shadow: 0 2px 6px rgba(37,99,235,0.3); }
    .btn-print:hover { background: #1d4ed8; }
    .btn-wa { background: #25D366; color: #ffffff; box-shadow: 0 2px 6px rgba(37,211,102,0.3); }
    .btn-wa:hover { background: #1eb956; }
    .btn-back { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; }
    .btn-back:hover { background: #f1f5f9; color: #0f172a; }

    /* ໃບສົ່ງເຄື່ອງ (Waybill Container) */
    .waybill-sheet {
      max-width: 680px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 14px;
      border: 2px solid #0f172a;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
      overflow: hidden;
    }

    /* ແຖບສີດ້ານເທິງ */
    .wb-color-bar {
      height: 8px;
      background: <?php echo $courierColor; ?>;
    }

    /* ຫົວບິນຂົນສົ່ງ */
    .wb-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px 22px;
      border-bottom: 2px solid #0f172a;
      background: #f8fafc;
    }
    .wb-courier-brand {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .wb-courier-logo {
      height: 48px;
      width: auto;
      max-width: 90px;
      object-fit: contain;
      border-radius: 6px;
      border: 1px solid #cbd5e1;
      background: #fff;
      padding: 3px;
    }
    .wb-brand-text h1 {
      font-size: 17px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: .3px;
      line-height: 1.25;
    }
    .wb-brand-text p {
      font-size: 11px;
      color: #64748b;
      font-weight: 700;
      letter-spacing: .5px;
      text-transform: uppercase;
    }
    .wb-route-badge {
      text-align: right;
    }
    .wb-route-box {
      display: inline-block;
      background: #0f172a;
      color: #ffffff;
      padding: 6px 14px;
      border-radius: 8px;
      text-align: center;
    }
    .wb-route-lbl { font-size: 10px; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
    .wb-route-val { font-size: 15px; font-weight: 900; color: #facc15; }

    /* ສ່ວນບາໂຄດ & ເລກໃບຝາກເຄື່ອງ */
    .wb-barcode-sec {
      padding: 14px 22px;
      text-align: center;
      background: #ffffff;
      border-bottom: 2px solid #0f172a;
    }
    #barcodeSvg {
      max-width: 100%;
      height: 52px;
      display: block;
      margin: 0 auto;
    }
    .wb-track-num {
      font-family: 'Courier New', Courier, monospace;
      font-size: 24px;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: 3px;
      margin-top: 4px;
    }
    .wb-meta-info {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      color: #475569;
      margin-top: 6px;
      padding-top: 6px;
      border-top: 1px dashed #cbd5e1;
    }
    .wb-meta-info b { color: #0f172a; }

    /* ກ່ອງຜູ້ຮັບ (TO) & ຜູ້ສົ່ງ (FROM) */
    .wb-parties {
      display: grid;
      grid-template-columns: 1.25fr 1fr;
      border-bottom: 2px solid #0f172a;
    }
    .wb-to-box {
      padding: 16px 20px;
      border-right: 2px solid #0f172a;
      background: #ffffff;
    }
    .wb-from-box {
      padding: 16px 18px;
      background: #f8fafc;
    }
    .wb-tag {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .wb-tag.is-to { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
    .wb-tag.is-from { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

    /* ຜູ້ຮັບ: ຕົວໜັງສືໃຫຍ່ ແລະ ຊັດເຈນທີ່ສຸດ */
    .wb-to-name {
      font-size: 18px;
      font-weight: 900;
      color: #0f172a;
      margin-bottom: 4px;
      line-height: 1.3;
    }
    .wb-to-phone {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 20px;
      font-weight: 900;
      color: #1e3a8a;
      background: #eff6ff;
      padding: 4px 10px;
      border-radius: 6px;
      border: 1px solid #bfdbfe;
      margin-bottom: 8px;
      font-family: 'Courier New', Courier, monospace;
      letter-spacing: 1px;
    }
    .wb-to-addr {
      font-size: 13.5px;
      color: #1e293b;
      line-height: 1.5;
      font-weight: 500;
    }
    .wb-branch-highlight {
      margin-top: 8px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #fef3c7;
      border: 1px solid #fde68a;
      color: #92400e;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 700;
    }

    /* ຜູ້ສົ່ງ */
    .wb-from-name { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 3px; }
    .wb-from-phone { font-size: 13px; font-weight: 700; color: #2563eb; margin-bottom: 4px; }
    .wb-from-addr { font-size: 12px; color: #475569; line-height: 1.45; }

    /* ສະແຕັມການຊຳລະເງິນ / COD */
    .wb-pay-sec {
      padding: 14px 22px;
      border-bottom: 2px solid #0f172a;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .wb-pay-badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 16px;
      border-radius: 8px;
    }
    .wb-pay-badge.is-paid {
      background: #dcfce7;
      border: 2px solid #86efac;
      color: #166534;
    }
    .wb-pay-badge.is-cod {
      background: #fee2e2;
      border: 2px solid #fca5a5;
      color: #991b1b;
    }
    .wb-pay-title { font-size: 16px; font-weight: 900; line-height: 1.2; }
    .wb-pay-sub { font-size: 11px; opacity: .85; }
    .wb-amount-block {
      text-align: right;
    }
    .wb-amount-lbl { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; }
    .wb-amount-val { font-size: 22px; font-weight: 900; color: #0f172a; }

    /* ລາຍການສິນຄ້າພາຍໃນກ່ອງ */
    .wb-items-sec {
      padding: 12px 22px;
      border-bottom: 2px solid #0f172a;
      background: #f8fafc;
    }
    .wb-items-sec-title {
      font-size: 11px;
      font-weight: 800;
      color: #475569;
      text-transform: uppercase;
      margin-bottom: 6px;
      display: flex;
      justify-content: space-between;
    }
    .wb-items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12.5px;
    }
    .wb-items-table th {
      background: #e2e8f0;
      color: #334155;
      padding: 6px 8px;
      font-weight: 700;
      border: 1px solid #cbd5e1;
      text-align: left;
    }
    .wb-items-table td {
      padding: 6px 8px;
      border: 1px solid #e2e8f0;
      background: #ffffff;
    }
    .wb-items-table .text-center { text-align: center; }
    .wb-items-table .text-right { text-align: right; }

    /* ຄຳເຕືອນ & ເຊັນຮັບ */
    .wb-foot {
      padding: 14px 22px;
      background: #ffffff;
    }
    .wb-advisory {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      background: #eff6ff;
      border: 1px dashed #93c5fd;
      border-radius: 6px;
      font-size: 11px;
      color: #1e40af;
      margin-bottom: 14px;
    }
    .wb-signs {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      text-align: center;
    }
    .wb-sign-box {
      border: 1px dashed #cbd5e1;
      border-radius: 6px;
      padding: 8px 6px;
      background: #fafafa;
    }
    .wb-sign-line {
      height: 38px;
      border-bottom: 1px solid #94a3b8;
      margin-bottom: 4px;
    }
    .wb-sign-title { font-size: 11px; font-weight: 800; color: #334155; }
    .wb-sign-date { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }

    /* ຮອງຮັບການພິມອອກເຈ້ຍ (@media print) */
    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
      }
      .top-actions { display: none !important; }
      .waybill-sheet {
        border: 2px solid #000000 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        max-width: 100% !important;
      }
      .wb-head { background: #f0f0f0 !important; border-bottom: 2px solid #000 !important; }
      .wb-barcode-sec, .wb-parties, .wb-pay-sec, .wb-items-sec { border-bottom: 2px solid #000 !important; }
      .wb-to-box { border-right: 2px solid #000 !important; }
      .wb-to-phone { background: #eee !important; color: #000 !important; border: 1px solid #000 !important; }
      .wb-route-box { background: #000 !important; color: #fff !important; }
      .wb-route-val { color: #fff !important; }
      .wb-pay-badge.is-paid { background: #eee !important; border: 2px solid #000 !important; color: #000 !important; }
      .wb-pay-badge.is-cod { background: #eee !important; border: 2px solid #000 !important; color: #000 !important; }
      .wb-items-table th { background: #eee !important; color: #000 !important; border: 1px solid #000 !important; }
      .wb-items-table td { border: 1px solid #000 !important; }
    }
  </style>
</head>
<body>

  <!-- ແຖບປຸ່ມດຳເນີນການ (ບໍ່ສະແດງຕອນພິມ) -->
  <div class="top-actions">
    <div>
      <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> ກັບຄືນ</a>
    </div>
    <div style="display: flex; gap: 8px;">
      <a href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener" class="btn btn-wa">
        <i class="fab fa-whatsapp"></i> ສົ່ງ WhatsApp ຫາລູກຄ້າ
      </a>
      <button onclick="window.print()" class="btn btn-print">
        <i class="fas fa-print"></i> ພິມໃບສົ່ງເຄື່ອງ
      </button>
    </div>
  </div>

  <!-- ໃບສົ່ງເຄື່ອງ / ບິນຂົນສົ່ງ (Waybill Sheet) -->
  <div class="waybill-sheet">
    
    <div class="wb-color-bar"></div>

    <!-- ຫົວບິນ: ໂລໂກ້ຂົນສົ່ງ + ຊື່ບໍລິສັດ + ສາຂາປາຍທາງ -->
    <div class="wb-head">
      <div class="wb-courier-brand">
        <?php if ($courierLogo): ?>
          <img src="<?php echo htmlspecialchars($courierLogo); ?>" alt="" class="wb-courier-logo">
        <?php else: ?>
          <div style="width:48px; height:48px; border-radius:6px; background:#0f172a; color:#fff; display:grid; place-items:center; font-size:22px;"><i class="fas fa-shipping-fast"></i></div>
        <?php endif; ?>
        <div class="wb-brand-text">
          <h1><?php echo htmlspecialchars($courierBrandName); ?></h1>
          <p>EXPRESS LOGISTICS · ໃບສົ່ງເຄື່ອງດ່ວນ</p>
        </div>
      </div>
      <div class="wb-route-badge">
        <div class="wb-route-box">
          <div class="wb-route-lbl">ສາຂາປາຍທາງ</div>
          <div class="wb-route-val"><?php echo htmlspecialchars(!empty($order['courier_branch']) ? $order['courier_branch'] : 'ນະຄອນຫຼວງ'); ?></div>
        </div>
      </div>
    </div>

    <!-- ບາໂຄດ & ເລກໃບຝາກເຄື່ອງ (Code 128) -->
    <div class="wb-barcode-sec">
      <svg id="barcodeSvg"></svg>
      <div class="wb-track-num"><?php echo htmlspecialchars($barcodeVal); ?></div>
      <div class="wb-meta-info">
        <div>ອໍເດີ: <b>#<?php echo htmlspecialchars((string)$order['order_no']); ?></b></div>
        <div>ວັນທີສັ່ງຊື້: <b><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></b></div>
        <div>ພິມວັນທີ: <b><?php echo date('d/m/Y H:i'); ?></b></div>
      </div>
    </div>

    <!-- ຂໍ້ມູນຜູ້ຮັບ (ໃຫຍ່ ແລະ ເດັ່ນ) & ຂໍ້ມູນຜູ້ສົ່ງ -->
    <div class="wb-parties">
      <!-- ຜູ້ຮັບ (TO) -->
      <div class="wb-to-box">
        <span class="wb-tag is-to"><i class="fas fa-user-check"></i> ຜູ້ຮັບເຄື່ອງ (RECIPIENT) - ປາຍທາງ</span>
        <div class="wb-to-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
        <div>
          <span class="wb-to-phone"><i class="fas fa-phone-alt fa-xs"></i> <?php echo htmlspecialchars($order['customer_tel']); ?></span>
        </div>
        <div class="wb-to-addr">
          <i class="fas fa-map-marker-alt text-danger"></i> 
          <?php echo nl2br(htmlspecialchars((string)$order['customer_address'])); ?>
        </div>
        <?php if (!empty($order['courier_branch'])): ?>
          <div class="wb-branch-highlight">
            <i class="fas fa-building"></i> ຮັບທີ່ສາຂາ: <b><?php echo htmlspecialchars($order['courier_branch']); ?></b>
          </div>
        <?php endif; ?>
      </div>

      <!-- ຜູ້ສົ່ງ (FROM) -->
      <div class="wb-from-box">
        <span class="wb-tag is-from"><i class="fas fa-store"></i> ຜູ້ສົ່ງ (SENDER)</span>
        <div class="wb-from-name"><?php echo htmlspecialchars($shopName); ?></div>
        <div class="wb-from-phone"><i class="fas fa-phone-alt fa-xs"></i> <?php echo htmlspecialchars($shopTel); ?></div>
        <div class="wb-from-addr"><?php echo htmlspecialchars($shopAddr); ?></div>
      </div>
    </div>

    <!-- ການຊຳລະເງິນ & ຍອດລວມ (COD / ຊຳລະແລ້ວ) -->
    <div class="wb-pay-sec">
      <div class="wb-pay-badge is-paid">
        <i class="fas fa-check-circle fa-2x"></i>
        <div>
          <div class="wb-pay-title">ຊຳລະແລ້ວ / PAID</div>
          <div class="wb-pay-sub">ຫ້າມເກັບເງິນປາຍທາງ (ສິນຄ້າຊຳລະຮຽບຮ້ອຍແລ້ວ)</div>
        </div>
      </div>
      <div class="wb-amount-block">
        <div class="wb-amount-lbl">ມູນຄ່າສິນຄ້າລວມ</div>
        <div class="wb-amount-val"><?php echo number_format($sum); ?> ₭</div>
      </div>
    </div>

    <!-- ລາຍການສິນຄ້າພາຍໃນກ່ອງ -->
    <div class="wb-items-sec">
      <div class="wb-items-sec-title">
        <span><i class="fas fa-boxes"></i> ລາຍການສິນຄ້າພາຍໃນກ່ອງພັດສະດຸ (PACKAGE CONTENTS)</span>
        <span>ຈຳນວນລາຍການ: <?php echo count($itemRows); ?></span>
      </div>
      <table class="wb-items-table">
        <thead>
          <tr>
            <th style="width: 32px;" class="text-center">#</th>
            <th>ຊື່ສິນຄ້າ</th>
            <th style="width: 55px;" class="text-center">ຈຳນວນ</th>
            <th style="width: 90px;" class="text-right">ລາຄາ/ໜ່ວຍ</th>
            <th style="width: 100px;" class="text-right">ລວມ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($itemRows as $idx => $r): ?>
            <tr>
              <td class="text-center"><?php echo $idx + 1; ?></td>
              <td><b><?php echo htmlspecialchars($r['name']); ?></b></td>
              <td class="text-center font-weight-bold">× <?php echo $r['qty']; ?></td>
              <td class="text-right"><?php echo $r['unit'] > 0 ? number_format($r['unit']) . ' ₭' : '-'; ?></td>
              <td class="text-right"><?php echo $r['sub'] > 0 ? number_format($r['sub']) . ' ₭' : '-'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!empty($order['note'])): ?>
        <div style="margin-top: 6px; font-size: 11px; color: #dc2626;">
          <b>ໝາຍເຫດຈາກລູກຄ້າ:</b> <?php echo htmlspecialchars($order['note']); ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ຄຳເຕືອນ & ລາຍເຊັນມອບ-ຮັບ -->
    <div class="wb-foot">
      <div class="wb-advisory">
        <i class="fas fa-video fa-lg"></i>
        <span><b>ຄຳແນະນຳສຳຄັນ:</b> ກະລຸນາຖ່າຍຄລິບວິດີໂອຂະນະແກະກ່ອງພັດສະດຸທຸກຄັ້ງ ເພື່ອເປັນຫຼັກຖານຫາກສິນຄ້າມີບັນຫາ ຫຼື ບໍ່ຄົບ.</span>
      </div>
      <div class="wb-signs">
        <div class="wb-sign-box">
          <div class="wb-sign-line"></div>
          <div class="wb-sign-title">ຜູ້ສົ່ງເຄື່ອງ (SENDER)</div>
          <div class="wb-sign-date"><?php echo date('d/m/Y'); ?></div>
        </div>
        <div class="wb-sign-box">
          <div class="wb-sign-line"></div>
          <div class="wb-sign-title">ພະນັກງານຂົນສົ່ງ (COURIER)</div>
          <div class="wb-sign-date">ວັນທີ ....../....../......</div>
        </div>
        <div class="wb-sign-box">
          <div class="wb-sign-line"></div>
          <div class="wb-sign-title">ຜູ້ຮັບເຄື່ອງ (RECIPIENT)</div>
          <div class="wb-sign-date">ວັນທີ ....../....../......</div>
        </div>
      </div>
    </div>

  </div>

  <script>
    // ສ້າງ SVG Barcode ມາດຕະຖານ Code 128
    try {
      JsBarcode("#barcodeSvg", "<?php echo addslashes($barcodeVal); ?>", {
        format: "CODE128",
        lineColor: "#0f172a",
        width: 2.2,
        height: 48,
        displayValue: false,
        margin: 0
      });
    } catch (e) {
      console.warn("Barcode rendering fallback:", e);
    }
  </script>

</body>
</html>
