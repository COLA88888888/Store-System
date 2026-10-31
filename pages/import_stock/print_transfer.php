<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Check authorization
if (empty($_SESSION['user_id'])) {
    exit("Access Denied");
}

$transfer_id = intval($_GET['transfer_id'] ?? 0);
if ($transfer_id <= 0) {
    exit("Invalid Transfer ID");
}

// Fetch transfer header
$trfStmt = $pdo->prepare("
    SELECT t.*, f.store_name as from_store_name, to_s.store_name as to_store_name, u.username, u.fname, u.lname 
    FROM st_stock_transfers t 
    LEFT JOIN st_tbstore f ON t.from_store_id = f.store_id
    LEFT JOIN st_tbstore to_s ON t.to_store_id = to_s.store_id
    LEFT JOIN st_tbuser u ON t.created_by = u.Id 
    WHERE t.transfer_id = ?
");
$trfStmt->execute([$transfer_id]);
$transfer = $trfStmt->fetch();

if (!$transfer) {
    exit(tl('ບໍ່ພົບຂໍ້ມູນໃບໂອນນີ້ໃນລະບົບ!'));
}

// Fetch transfer details
$detStmt = $pdo->prepare("
    SELECT d.*, p.barcode as prod_barcode
    FROM st_stock_transfer_details d 
    LEFT JOIN st_products p ON d.product_id = p.product_id AND p.store_id = ?
    WHERE d.transfer_id = ? 
    ORDER BY d.id ASC
");
$detStmt->execute([$transfer['from_store_id'], $transfer_id]);
$details = $detStmt->fetchAll();

$creatorName = trim(($transfer['fname'] ?? '') . ' ' . ($transfer['lname'] ?? ''));
if (empty($creatorName)) $creatorName = $transfer['username'] ?? 'Admin';
$transferDate = !empty($transfer['transfer_date']) ? date('d/m/Y H:i', strtotime($transfer['transfer_date'])) : '-';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo tlh('ໃບໂອນສິນຄ້າ'); ?> - <?php echo htmlspecialchars($transfer['transfer_code']); ?></title>
  <link rel="stylesheet" href="../../assets/css/local-font.css">
  <?php echo themeCssLink('print_transfer.css'); /* CSS ຍ້າຍໄປ themes/print_transfer.css */ ?>
</head>
<body>

  <div class="slip-container">
    <h3 class="title"><?php echo tlh('ໃບໂອນສິນຄ້າ'); ?></h3>
    <div style="text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 10px;">
      (Stock Transfer Receipt)
    </div>
    
    <?php if (!empty($company['tax_id'])): ?>
    <div class="info-line">
      <span><?php echo tlh('ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ:'); ?></span>
      <span style="font-weight: 700; font-size: 11px;"><?php echo htmlspecialchars($company['tax_id']); ?></span>
    </div>
    <?php endif; ?>
    <div class="info-line">
      <span><?php echo tlh('ເລກທີໃບໂອນ:'); ?></span>
      <strong><?php echo htmlspecialchars($transfer['transfer_code']); ?></strong>
    </div>
    <div class="info-line">
      <span><?php echo tlh('ວັນທີໂອນ:'); ?></span>
      <span><?php echo $transferDate; ?></span>
    </div>
    <div class="info-line">
      <span><?php echo tlh('ຜູ້ໂອນ:'); ?></span>
      <span><?php echo htmlspecialchars($creatorName); ?></span>
    </div>
    <div class="info-line">
      <span><?php echo tlh('ສະຖານະ:'); ?></span>
      <span style="font-weight: bold; text-transform: uppercase; color: <?php echo ($transfer['status'] === 'completed') ? 'green' : 'red'; ?>;">
        <?php echo ($transfer['status'] === 'completed') ? tl('ສຳເລັດ (Success)') : tl('ຍົກເລີກ (Cancelled)'); ?>
      </span>
    </div>

    <div class="divider"></div>

    <div class="info-line">
      <span><?php echo tlh('ສາຂາຕົ້ນທາງ:'); ?></span>
      <strong><?php echo htmlspecialchars($transfer['from_store_name']); ?></strong>
    </div>
    <div class="info-line">
      <span><?php echo tlh('ສາຂາປາຍທາງ:'); ?></span>
      <strong><?php echo htmlspecialchars($transfer['to_store_name']); ?></strong>
    </div>

    <div class="divider"></div>

    <table class="table-slip">
      <thead>
        <tr>
          <th><?php echo tlh('ລາຍການສິນຄ້າ'); ?></th>
          <th class="qty-col" style="width: 50px;"><?php echo tlh('ຈຳນວນ'); ?></th>
          <th class="price-col" style="width: 80px;"><?php echo tlh('ຫົວໜ່ວຍ'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $totalItems = 0;
        $totalQty = 0;
        foreach ($details as $row): 
          $totalItems++;
          $totalQty += $row['qty'];
        ?>
          <tr>
            <td>
              <div><?php echo htmlspecialchars($row['product_name']); ?></div>
              <small style="color: #475569; font-size: 9px;"><?php echo tlh('ບາໂຄ້ດ:'); ?> <?php echo htmlspecialchars($row['barcode'] ?: ($row['prod_barcode'] ?: '-')); ?></small>
            </td>
            <td class="qty-col"><?php echo number_format($row['qty']); ?></td>
            <td class="price-col"><?php echo htmlspecialchars($row['unit'] ?: 'ອັນ'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="divider"></div>

    <div class="info-line">
      <span><?php echo tlh('ລວມທັງໝົດ:'); ?></span>
      <strong><?php echo $totalItems; ?> <?php echo tlh('ລາຍການ'); ?></strong>
    </div>
    <div class="info-line">
      <span><?php echo tlh('ຈຳນວນລວມ:'); ?></span>
      <strong><?php echo number_format($totalQty); ?> <?php echo tlh('ອັນ'); ?></strong>
    </div>

    <?php if (!empty($transfer['notes'])): ?>
      <div class="notes-box">
        <strong><?php echo tlh('ໝາຍເຫດ:'); ?></strong> <?php echo htmlspecialchars($transfer['notes']); ?>
      </div>
    <?php endif; ?>

    <div class="divider" style="margin-top: 25px;"></div>
    
    <div style="text-align: center; font-size: 9px; margin-top: 10px; color: #475569;">
      <?php echo tlh('ລະບົບ Mini POS - ຈັດການສາຂາ & ສະຕັອກ'); ?>
    </div>

    <!-- Print Button (Hidden during print) -->
    <div class="no-print" style="margin-top: 20px; text-align: center;">
      <button onclick="window.print();" style="background: #2563eb; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <?php echo tlh('ພິມໃບບິນ'); ?>
      </button>
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
