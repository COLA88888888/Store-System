<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
$import = $impStmt->fetch(PDO::FETCH_ASSOC);

if (!$import) {
    exit(tl('ບໍ່ພົບຂໍ້ມູນໃບບິນນີ້ໃນລະບົບ!'));
}

// Fetch store info
$storeId = (int)($import['store_id'] ?? 1);
$storeStmt = $pdo->prepare("SELECT * FROM st_tbstore WHERE store_id = ?");
$storeStmt->execute([$storeId]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch company info
$compStmt = $pdo->query("SELECT * FROM st_tbcompanyinfo LIMIT 1");
$company = $compStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$storeName = !empty($store['store_name']) ? $store['store_name'] : (!empty($company['com_name_la']) ? $company['com_name_la'] : 'ຮ້ານ ຂາຍດີ ຈະເລີນຊັບ');
$storeAddress = !empty($store['address']) ? $store['address'] : ($company['com_address'] ?? '');
$storeTel = !empty($store['tel']) ? $store['tel'] : ($company['com_tel'] ?? '');
$storeTaxId = !empty($company['tax_id']) ? $company['tax_id'] : '';

// Logo
$logoUrl = '';
if (!empty($company['img_url']) && file_exists(__DIR__ . '/../../assets/img/logo/' . $company['img_url'])) {
    $logoUrl = '../../assets/img/logo/' . $company['img_url'];
} else {
    $logoFiles = glob(__DIR__ . '/../../assets/img/logo/*.*');
    if (!empty($logoFiles) && file_exists($logoFiles[0])) {
        $logoUrl = '../../assets/img/logo/' . basename($logoFiles[0]);
    }
}

// Fetch import details (join products on product_id)
$detStmt = $pdo->prepare("
    SELECT id.*, p.product_name, p.barcode, p.unit AS base_unit 
    FROM st_import_details id 
    JOIN st_imports i ON id.import_id = i.import_id
    LEFT JOIN st_products p ON id.product_id = p.product_id
    WHERE id.import_id = ? 
    ORDER BY id.import_detail_id ASC
");
$detStmt->execute([$import_id]);
$details = $detStmt->fetchAll(PDO::FETCH_ASSOC);

$creatorName = trim(($import['fname'] ?? '') . ' ' . ($import['lname'] ?? ''));
if (empty($creatorName)) $creatorName = $import['username'] ?? 'Admin';
$importDate = !empty($import['import_date']) ? date('d/m/Y H:i', strtotime($import['import_date'])) : '-';

// Calculate summary
$totalItems = count($details);
$totalQty = 0;
foreach ($details as $d) {
    $totalQty += floatval($d['quantity'] ?? 0);
}
$grandTotal = floatval($import['total_cost'] ?? 0);

// Detect PO reference from notes
$poRef = '-';
if (!empty($import['notes']) && preg_match('/PO[0-9\-]+/i', $import['notes'], $m)) {
    $poRef = $m[0];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo tlh('ໃບຮັບສິນຄ້າເຂົ້າສາງ (A4)'); ?> - <?php echo htmlspecialchars($import['invoice_number']); ?></title>
  <link rel="stylesheet" href="../../assets/css/local-font.css">
  <link rel="stylesheet" href="../../fontawesome-free-5.15.3-web/css/all.min.css">
  <?php echo themeCssLink('print_import.css'); ?>
</head>
<body>

  <!-- Screen Actions Bar (hidden when printing) -->
  <div class="print-actions-bar">
    <div class="doc-label">
      <i class="fas fa-file-invoice text-primary"></i>
      <span><?php echo tlh('ໃບຮັບສິນຄ້າເຂົ້າສາງ (A4) · ເລກທີ:'); ?> #<?php echo htmlspecialchars($import['invoice_number']); ?></span>
    </div>
    <div class="btn-group">
      <button type="button" class="btn-print-action primary" onclick="window.print()">
        <i class="fas fa-print"></i> <?php echo tlh('ພິມເອກະສານ A4 (Print)'); ?>
      </button>
      <button type="button" class="btn-print-action secondary" onclick="window.close()">
        <i class="fas fa-times"></i> <?php echo tlh('ປິດ (Close)'); ?>
      </button>
    </div>
  </div>

  <!-- A4 Page Container -->
  <div class="a4-sheet">

    <!-- Header Section -->
    <div class="grn-header">
      <div class="grn-store-info">
        <div class="grn-logo-wrap">
          <?php if (!empty($logoUrl)): ?>
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="grn-logo">
          <?php endif; ?>
          <div>
            <div class="grn-store-name"><?php echo htmlspecialchars($storeName); ?></div>
            <?php if (!empty($storeAddress)): ?>
              <div class="grn-store-meta"><i class="fas fa-map-marker-alt" style="width: 14px;"></i> <?php echo htmlspecialchars($storeAddress); ?></div>
            <?php endif; ?>
            <?php if (!empty($storeTel)): ?>
              <div class="grn-store-meta"><i class="fas fa-phone-alt" style="width: 14px;"></i> <?php echo tlh('ໂທ:'); ?> <?php echo htmlspecialchars($storeTel); ?></div>
            <?php endif; ?>
            <?php if (!empty($storeTaxId)): ?>
              <div class="grn-store-meta"><i class="fas fa-id-card" style="width: 14px;"></i> <?php echo tlh('ເລກປະຈຳຕົວຜູ້ເສຍອາກອນ:'); ?> <?php echo htmlspecialchars($storeTaxId); ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="grn-doc-identity">
        <h1 class="grn-doc-title"><?php echo tlh('ໃບຮັບສິນຄ້າເຂົ້າສາງ'); ?></h1>
        <div class="grn-doc-sub">GOODS RECEIVED NOTE (GRN)</div>
        <div class="grn-badge-box">
          <div class="badge-row">
            <span class="lbl"><?php echo tlh('ເລກທີໃບບິນ:'); ?></span>
            <span class="val highlight">#<?php echo htmlspecialchars($import['invoice_number']); ?></span>
          </div>
          <div class="badge-row">
            <span class="lbl"><?php echo tlh('ວັນທີ-ເວລາ:'); ?></span>
            <span class="val"><?php echo $importDate; ?></span>
          </div>
          <div class="badge-row">
            <span class="lbl"><?php echo tlh('ສາຂາທີ່ຮັບ:'); ?></span>
            <span class="val"><?php echo htmlspecialchars($storeName); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Metadata Grid (2 Cards) -->
    <div class="grn-meta-grid">
      <!-- Supplier Card -->
      <div class="grn-meta-card">
        <div class="grn-card-title"><i class="fas fa-truck text-primary"></i> <?php echo tlh('ຂໍ້ມູນຜູ້ສະໜອງສິນຄ້າ (Supplier Details)'); ?></div>
        <div class="grn-card-row">
          <span class="k"><?php echo tlh('ຊື່ຜູ້ສະໜອງ:'); ?></span>
          <span class="v font-weight-bold" style="color: #1e3a8a;"><?php echo !empty($import['supplier_name']) ? htmlspecialchars($import['supplier_name']) : '-'; ?></span>
        </div>
        <div class="grn-card-row">
          <span class="k"><?php echo tlh('ເລກທີໃບສັ່ງຊື້ (PO):'); ?></span>
          <span class="v"><?php echo htmlspecialchars($poRef); ?></span>
        </div>
        <div class="grn-card-row">
          <span class="k"><?php echo tlh('ສະຖານະ:'); ?></span>
          <span class="v" style="color: #15803d; font-weight: 700;"><?php echo tlh('ຮັບເຂົ້າສະຕັອກສຳເລັດ (Completed)'); ?></span>
        </div>
      </div>

      <!-- Receipt & Warehouse Info -->
      <div class="grn-meta-card">
        <div class="grn-card-title"><i class="fas fa-warehouse text-primary"></i> <?php echo tlh('ຂໍ້ມູນການຮັບເຂົ້າ ແລະ ສາງ (Warehouse Info)'); ?></div>
        <div class="grn-card-row">
          <span class="k"><?php echo tlh('ຜູ້ບັນທຶກ/ຜູ້ຮັບ:'); ?></span>
          <span class="v"><?php echo htmlspecialchars($creatorName); ?></span>
        </div>
        <div class="grn-card-row">
          <span class="k"><?php echo tlh('ວັນທີຮັບເຄື່ອງ:'); ?></span>
          <span class="v"><?php echo !empty($import['import_date']) ? date('d/m/Y', strtotime($import['import_date'])) : '-'; ?></span>
        </div>
        <div class="grn-card-row">
          <span class="k"><?php echo tlh('ສາງ/ສາຂາປາຍທາງ:'); ?></span>
          <span class="v"><?php echo htmlspecialchars($storeName); ?></span>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="grn-table-wrap">
      <table class="grn-table">
        <thead>
          <tr>
            <th class="c" style="width: 38px;">#</th>
            <th class="c" style="width: 125px;"><?php echo tlh('ລະຫັດ / ບາໂຄ້ດ'); ?></th>
            <th><?php echo tlh('ລາຍການສິນຄ້າ'); ?></th>
            <th class="c" style="width: 90px;"><?php echo tlh('ໝົດອາຍຸ (EXP)'); ?></th>
            <th class="c" style="width: 90px;"><?php echo tlh('ຈຳນວນ'); ?></th>
            <th class="r" style="width: 105px;"><?php echo tlh('ຕົ້ນທຶນຕໍ່ໜ່ວຍ'); ?></th>
            <th class="r" style="width: 115px;"><?php echo tlh('ມູນຄ່າລວມ'); ?> (<?php echo kipSymbol(); ?>)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($details)): ?>
            <tr>
              <td colspan="7" class="c" style="padding: 30px; color: #64748b;"><?php echo tlh('ບໍ່ມີລາຍການສິນຄ້າ'); ?></td>
            </tr>
          <?php else: ?>
            <?php foreach ($details as $idx => $d): ?>
              <?php
                $pName = !empty($d['product_name']) ? $d['product_name'] : (tl('ສິນຄ້າ') . ' #') . $d['product_id'];
                $barcode = !empty($d['barcode']) ? $d['barcode'] : '-';
                $unit = !empty($d['unit_name']) ? $d['unit_name'] : ($d['base_unit'] ?: 'ອັນ');
                $qty = floatval($d['quantity'] ?? 0);
                $cost = floatval($d['cost_price'] ?? 0);
                $subtot = floatval($d['total_cost'] ?? 0);
                $exp = !empty($d['expiry_date']) ? date('d/m/Y', strtotime($d['expiry_date'])) : '-';
              ?>
              <tr>
                <td class="c"><?php echo $idx + 1; ?></td>
                <td class="c font-mono"><?php echo htmlspecialchars($barcode); ?></td>
                <td>
                  <div class="prod-name"><?php echo htmlspecialchars($pName); ?></div>
                  <?php if (!empty($d['batch_no'])): ?>
                    <div class="prod-meta"><?php echo tlh('ເລກຊຸດ (Batch):'); ?> <?php echo htmlspecialchars($d['batch_no']); ?></div>
                  <?php endif; ?>
                </td>
                <td class="c">
                  <?php if ($exp !== '-'): ?>
                    <span class="badge-exp"><?php echo $exp; ?></span>
                  <?php else: ?>
                    <span style="color: #94a3b8;">-</span>
                  <?php endif; ?>
                </td>
                <td class="c font-weight-bold">
                  <?php echo number_format($qty); ?>
                  <span class="badge-unit"><?php echo htmlspecialchars($unit); ?></span>
                </td>
                <td class="r"><?php echo number_format($cost); ?></td>
                <td class="r font-weight-bold" style="color: #0f172a;"><?php echo number_format($subtot); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Summary & Totals -->
    <div class="grn-summary-row">
      <div class="grn-summary-notes">
        <div><strong><?php echo tlh('ໝາຍເຫດ (Remarks):'); ?></strong></div>
        <div style="margin-top: 3px;">
          <?php echo !empty($import['notes']) ? nl2br(htmlspecialchars($import['notes'])) : tl('ບໍ່ມີໝາຍເຫດເພີ່ມເຕີມ'); ?>
        </div>
      </div>

      <table class="grn-totals-table">
        <tr>
          <td class="lbl"><?php echo tlh('ລວມຈຳນວນລາຍການ:'); ?></td>
          <td class="val"><?php echo number_format($totalItems); ?> <?php echo tlh('ລາຍການ'); ?></td>
        </tr>
        <tr>
          <td class="lbl"><?php echo tlh('ລວມຈຳນວນສິນຄ້າ:'); ?></td>
          <td class="val"><?php echo number_format($totalQty); ?> <?php echo tlh('ຊິ້ນ/ໜ່ວຍ'); ?></td>
        </tr>
        <tr class="grand-total">
          <td class="lbl"><?php echo tlh('ມູນຄ່າລວມທັງໝົດ:'); ?></td>
          <td class="val"><?php echo number_format($grandTotal); ?> <?php echo kipSymbol(); ?></td>
        </tr>
      </table>
    </div>

    <!-- Signatures (4-Tier Standard) -->
    <div class="grn-signatures">
      <div class="grn-sig-box">
        <div class="grn-sig-role"><?php echo tlh('ຜູ້ສົ່ງສິນຄ້າ (Delivered By)'); ?></div>
        <div class="grn-sig-line"></div>
        <div class="grn-sig-name"><?php echo !empty($import['supplier_name']) ? htmlspecialchars($import['supplier_name']) : '(............................................)'; ?></div>
        <div class="grn-sig-date"><?php echo tlh('ວັນທີ: ....../....../............'); ?></div>
      </div>

      <div class="grn-sig-box">
        <div class="grn-sig-role"><?php echo tlh('ຜູ້ກວດກາສິນຄ້າ (Inspected By)'); ?></div>
        <div class="grn-sig-line"></div>
        <div class="grn-sig-name">(............................................)</div>
        <div class="grn-sig-date"><?php echo tlh('ວັນທີ: ....../....../............'); ?></div>
      </div>

      <div class="grn-sig-box">
        <div class="grn-sig-role"><?php echo tlh('ຜູ້ຮັບສິນຄ້າເຂົ້າສາງ (Received By)'); ?></div>
        <div class="grn-sig-line"></div>
        <div class="grn-sig-name"><?php echo htmlspecialchars($creatorName); ?></div>
        <div class="grn-sig-date"><?php echo tlh('ວັນທີ:'); ?> <?php echo !empty($import['import_date']) ? date('d/m/Y', strtotime($import['import_date'])) : '....../....../..........'; ?></div>
      </div>

      <div class="grn-sig-box">
        <div class="grn-sig-role"><?php echo tlh('ຜູ້ອະນຸມັດ (Authorized By)'); ?></div>
        <div class="grn-sig-line"></div>
        <div class="grn-sig-name">(............................................)</div>
        <div class="grn-sig-date"><?php echo tlh('ວັນທີ: ....../....../............'); ?></div>
      </div>
    </div>

    <!-- Document Footer -->
    <div class="grn-doc-footer">
      <div><?php echo tlh('ລະບົບຈັດການສາງສິນຄ້າ MiniPOS · ເອກະສານຮັບເຂົ້າສະຕັອກຖືກຕ້ອງຕາມມາດຕະຖານ'); ?></div>
      <div><?php echo tlh('ພິມເມື່ອ:'); ?> <?php echo date('d/m/Y H:i'); ?></div>
    </div>

  </div>

  <script>
    window.onload = function() {
      // Auto open print dialog smoothly
      setTimeout(function() {
        window.print();
      }, 400);
    };
  </script>
</body>
</html>
