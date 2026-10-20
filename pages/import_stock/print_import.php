<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('import_list') && !hasPermission('import_stock'))) {
    exit("Access Denied");
}

$import_id = intval($_GET['import_id'] ?? 0);
if ($import_id <= 0) {
    exit("Invalid Import ID");
}

// Fetch master import
$impStmt = $pdo->prepare("
    SELECT i.*, u.username, u.fname, u.lname 
    FROM st_imports i 
    LEFT JOIN st_tbuser u ON i.created_by = u.Id 
    WHERE i.import_id = ?
");
$impStmt->execute([$import_id]);
$import = $impStmt->fetch();

if (!$import) {
    exit("ບໍ່ພົບຂໍ້ມູນໃບບິນນີ້ໃນລະບົບ!");
}

// Fetch import details
$detStmt = $pdo->prepare("
    SELECT id.*, p.product_name, p.barcode, p.unit AS base_unit 
    FROM st_import_details id 
    JOIN st_imports i ON id.import_id = i.import_id
    LEFT JOIN st_products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
    WHERE id.import_id = ? 
    GROUP BY id.import_detail_id
    ORDER BY id.import_detail_id ASC
");
$detStmt->execute([$import_id]);
$details = $detStmt->fetchAll();

$creatorName = trim(($import['fname'] ?? '') . ' ' . ($import['lname'] ?? ''));
if (empty($creatorName)) $creatorName = $import['username'] ?? 'Admin';
$importDate = !empty($import['import_date']) ? date('d/m/Y H:i', strtotime($import['import_date'])) : '-';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ໃບບິນຮັບສິນຄ້າເຂົ້າ - <?php echo htmlspecialchars($import['invoice_number']); ?></title>
  <link rel="stylesheet" href="../../assets/css/local-font.css">
  <?php echo themeCssLink('print_import.css'); /* CSS ຍ້າຍໄປ themes/print_import.css */ ?>
</head>
<body>

  <div class="slip-container">
    
    <!-- Title -->
    <h3 class="title">ໃບບິນຮັບເຂົ້າສະຕັອກ</h3>
    
    <!-- Bill Info -->
    <?php if (!empty($company['tax_id'])): ?>
    <div class="info-line">
      <span>ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ:</span>
      <span style="font-weight: 700; font-size: 11px;"><?php echo htmlspecialchars($company['tax_id']); ?></span>
    </div>
    <?php endif; ?>
    <div class="info-line">
      <span>Bill:</span>
      <span style="font-weight: 700; font-size: 13px;"><?php echo htmlspecialchars($import['invoice_number']); ?></span>
    </div>
    <div class="info-line">
      <span>ວັນທີ:</span>
      <span><?php echo $importDate; ?></span>
    </div>
    <div class="info-line">
      <span>ຜູ້ສະໜອງ:</span>
      <span><?php echo !empty($import['supplier_name']) ? htmlspecialchars($import['supplier_name']) : '-'; ?></span>
    </div>
    <div class="info-line">
      <span>ຜູ້ຮັບ:</span>
      <span><?php echo htmlspecialchars($creatorName); ?></span>
    </div>

    <div class="divider"></div>

    <!-- Items Table -->
    <table class="table-slip">
      <thead>
        <tr>
          <th class="col-code">ລະຫັດ</th>
          <th class="col-name">ຊື່ສິນຄ້າ</th>
          <th class="col-qty">ຈຳນວນ</th>
          <th class="col-total">ເງິນລວມ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($details as $d): ?>
          <tr>
            <td class="col-code" style="font-family: monospace; font-size: 10px;"><?php echo htmlspecialchars($d['barcode'] ?: '-'); ?></td>
            <td class="col-name">
              <?php echo htmlspecialchars($d['product_name']); ?>
              <?php if (!empty($d['expiry_date'])): ?>
                <br><span style="font-size: 9px; color: #555;">(EXP: <?php echo date('d/m/y', strtotime($d['expiry_date'])); ?>)</span>
              <?php endif; ?>
            </td>
            <td class="col-qty">
              <?php echo number_format($d['quantity']); ?><br>
              <span style="font-size: 9px; color: #555;"><?php echo htmlspecialchars($d['unit_name']); ?></span>
            </td>
            <td class="col-total font-weight-bold">
              <?php echo number_format($d['total_cost']); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="divider"></div>

    <!-- Total -->
    <div class="total-box">
      <span>ລວມທັງໝົດ:</span>
      <span><?php echo number_format($import['total_cost']); ?> ₭</span>
    </div>

    <div class="divider"></div>
    
    <?php if (!empty($import['notes'])): ?>
    <div style="font-size: 10px; margin-bottom: 5px;">
      <strong>ໝາຍເຫດ:</strong> <?php echo nl2br(htmlspecialchars($import['notes'])); ?>
    </div>
    <?php endif; ?>

    <!-- Simple Signatures for Slip -->
    <div class="signature-area">
      <div>
        <div class="sig-line"></div>
        <span>ຜູ້ສົ່ງສິນຄ້າ</span>
      </div>
      <div>
        <div class="sig-line"></div>
        <span>ຜູ້ຮັບສິນຄ້າ</span>
      </div>
    </div>

    <div class="footer-msg">
      MiniPOS System<br>
      *** ຮັບເຄື່ອງເຂົ້າສຳເລັດ ***
    </div>

  </div>

  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
      }, 500);
    };

    window.onafterprint = function() {
      setTimeout(function() {
        window.close();
      }, 500);
    };
  </script>
</body>
</html>
