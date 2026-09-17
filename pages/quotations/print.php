<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if (!isset($_GET['id'])) {
    die("No ID provided");
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT q.*, u.username AS user_name FROM quotations q LEFT JOIN tbuser u ON q.created_by = u.Id WHERE q.quotation_id = ?");
$stmt->execute([$id]);
$q = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$q) {
    die("Quotation not found");
}

$created_by_name = !empty($q['user_name']) ? $q['user_name'] : $q['created_by'];

$stmtD = $pdo->prepare("SELECT d.*, p.unit FROM quotation_details d LEFT JOIN products p ON d.product_id = p.product_id WHERE d.quotation_id = ?");
$stmtD->execute([$id]);
$details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

// Fetch Company/Store info
$activeStoreId = $q['store_id'];
$storeStmt = $pdo->prepare("SELECT * FROM tbstore WHERE store_id = ?");
$storeStmt->execute([$activeStoreId]);
$company = $storeStmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    $company = [
        'com_name_la' => 'Company Name',
        'com_address' => 'Vientiane',
        'com_tel'     => '020 0000000'
    ];
}

$logoName = !empty($company['img_url']) ? basename($company['img_url']) : 'logo.png';
$base_path = '../../';
$logoPath = $base_path . 'assets/img/logo/' . $logoName;

function getLaoNumber($number) {
    $number = strval(floor((float)$number));
    if ($number === '0') return 'ສູນ';
    $lao_digits = ['ສູນ', 'ໜຶ່ງ', 'ສອງ', 'ສາມ', 'ສີ່', 'ຫ້າ', 'ຫົກ', 'ເຈັດ', 'ແປດ', 'ເກົ້າ'];
    $lao_places = ['', 'ສິບ', 'ຮ້ອຍ', 'ພັນ', 'ໝື່ນ', 'ແສນ'];
    
    $parts = [];
    while (strlen($number) > 0) {
        $parts[] = substr($number, -6);
        $number = substr($number, 0, -6);
    }
    
    $result = [];
    foreach ($parts as $p => $part) {
        if ((int)$part == 0) continue;
        $strlen = strlen($part);
        $text = '';
        for ($i=0; $i<$strlen; $i++) {
            $tmp = (int)substr($part, $i, 1);
            if ($tmp != 0) {
                $pos = $strlen - $i - 1;
                if ($pos == 1 && $tmp == 1) {
                    $text .= 'ສິບ';
                } elseif ($pos == 1 && $tmp == 2) {
                    $text .= 'ຊາວ';
                } elseif ($pos == 0 && $tmp == 1 && $strlen > 1 && substr($part, $i-1, 1) != '0') {
                    $text .= 'ເອັດ';
                } else {
                    $text .= $lao_digits[$tmp] . $lao_places[$pos];
                }
            }
        }
        if ($p > 0) {
            $text .= str_repeat('ລ້ານ', $p);
        }
        array_unshift($result, $text);
    }
    return implode('', $result) . 'ກີບ';
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="utf-8">
    <title>ໃບສະເໜີລາຄາ <?php echo htmlspecialchars($q['quotation_no']); ?></title>
    <style>
      @page { size: A4; margin: 0; }
      * { box-sizing: border-box; font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', Arial, sans-serif !important; color: #000 !important; -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
      html, body { width: 100%; margin: 0; padding: 0; background: #fff; font-size: 15px; font-weight: 400; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .container { max-width: 210mm; margin: 0 auto; padding: 1.5cm 2cm 2.5cm 3cm; }
      
      .top-header { text-align: center; margin-bottom: 15px; line-height: 1.6; }
      .top-header .rep { font-weight: normal; }
      
      .doc-title { text-align: center; margin-bottom: 15px; }
      .doc-title h2 { margin: 0; font-size: 22px; font-weight: bold; }
      .doc-title p { margin: 0; font-size: 18px; font-weight: bold; }
      
      .info-section { display: flex; justify-content: space-between; margin-bottom: 15px; }
      .info-left { width: 60%; }
      .info-left img { max-width: 250px; max-height: 90px; object-fit: contain; margin-bottom: 10px; }
      .info-left p { margin: 2px 0; }
      
      .info-right { width: 35%; text-align: right; margin-top: 0; }
      .info-right p { margin: 2px 0; }
      
      table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #000; }
      th { border: 1px solid #000; padding: 8px 5px; text-align: center; background-color: #f4cbad !important; font-weight: normal; font-size: 14px; line-height: 1.2; }
      td { border: 1px solid #000; padding: 8px 5px; }
      .td-center { text-align: center; }
      .td-right { text-align: right; }
      
      /* Only vertical borders inside body */
      tbody td { border-bottom: none; border-top: none; }
      tbody tr:last-child td { border-bottom: 1px solid #000; }
      
      tfoot td { background-color: #f4cbad !important; font-weight: bold; padding: 10px 5px; border: 1px solid #000; }
      
      .signature-section { display: flex; justify-content: space-between; margin-top: 60px; padding: 0 40px; }
      .signature-box { text-align: center; width: 200px; font-weight: bold; }
      
      @media print {
        .container { padding: 1.5cm 2cm 2.5cm 3cm; margin: 0; width: 100%; max-width: 100%; }
        .no-print { display: none !important; }
      }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; font-weight: bold; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">
            ພິມໃບສະເໜີລາຄາ (Print)
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; font-weight: bold; cursor: pointer; background: #dc3545; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            ປິດໜ້າຈໍ (Close)
        </button>
    </div>

    <div class="container">
        <!-- Top Header -->
        <div class="top-header">
            <div class="rep">ສາທາລະນະລັດ ປະຊາທິປະໄຕ ປະຊາຊົນລາວ</div>
            <div class="motto">ສັນຕິພາບ ເອກະລາດ ປະຊາທິປະໄຕ ເອກະພາບ ວັດທະນາຖາວອນ</div>
        </div>
        
        <!-- Document Title -->
        <div class="doc-title">
            <h2><?php echo ($q['status'] ?? '') === 'converted' ? 'ໃບບິນຂາຍ' : 'ໃບສະເໜີລາຄາ'; ?></h2>
            <p><?php echo ($q['status'] ?? '') === 'converted' ? 'Invoice' : 'Quotation'; ?></p>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-left">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" onerror="this.onerror=null; this.src='../../assets/img/logo/logo.png'">
                <p><b><?php echo htmlspecialchars($company['com_name_la'] ?? $company['store_name']); ?></b></p>
                <p><?php echo htmlspecialchars($company['com_address'] ?? ''); ?></p>
                <p>ໂທ: <?php echo htmlspecialchars($company['com_tel'] ?? ''); ?></p>
            </div>
            <div class="info-right">
                <p>ເລກທີ: <?php echo htmlspecialchars($q['quotation_no']); ?></p>
                <p>ວັນທີ: <?php echo date('d/m/Y', strtotime($q['created_at'])); ?></p>
                <p>ລູກຄ້າ: <b><?php echo htmlspecialchars($q['customer_name']); ?></b></p>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ລ/ດ<br>No:</th>
                    <th>ເນື້ອໃນລາຍການ<br>Descriptions</th>
                    <th style="width: 70px;">ໜ່ວຍ<br>Unit</th>
                    <th style="width: 70px;">ຈ/ນ<br>Qty</th>
                    <th style="width: 120px;">ລາຄາ<br>Price (LAK)</th>
                    <th style="width: 130px;">ມູນຄ່າ<br>Amount (LAK)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                foreach ($details as $d): 
                ?>
                <tr>
                    <td class="td-center"><?php echo $i++; ?>.</td>
                    <td><?php echo htmlspecialchars($d['product_name']); ?></td>
                    <td class="td-center"><?php echo htmlspecialchars($d['unit'] ?? 'ອັນ'); ?></td>
                    <td class="td-center"><?php echo (float)$d['qty']; ?></td>
                    <td class="td-right"><?php echo number_format($d['price']); ?></td>
                    <td class="td-right"><?php echo number_format($d['line_total']); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php 
                // Add some empty rows to make it look like the picture if items are few
                $empty_rows = max(0, 5 - count($details));
                for ($e = 0; $e < $empty_rows; $e++):
                ?>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <?php if ($q['discount_amount'] > 0 || $q['vat_amount'] > 0): ?>
                <tr>
                    <td colspan="4" class="td-center" style="background-color: white !important; border: none; border-left: 1px solid #000; border-right: 1px solid #000;">&nbsp;</td>
                    <td class="td-right">ມູນຄ່າ / Subtotal</td>
                    <td class="td-right" style="background-color: white !important;"><?php echo number_format($q['subtotal']); ?></td>
                </tr>
                <?php if ($q['discount_amount'] > 0): ?>
                <tr>
                    <td colspan="4" class="td-center" style="background-color: white !important; border: none; border-left: 1px solid #000; border-right: 1px solid #000;">&nbsp;</td>
                    <td class="td-right">ສ່ວນຫຼຸດ / Discount</td>
                    <td class="td-right" style="background-color: white !important;"><?php echo number_format($q['discount_amount']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($q['vat_amount'] > 0): ?>
                <tr>
                    <td colspan="4" class="td-center" style="background-color: white !important; border: none; border-left: 1px solid #000; border-right: 1px solid #000;">&nbsp;</td>
                    <td class="td-right">ອາກອນ / VAT</td>
                    <td class="td-right" style="background-color: white !important;"><?php echo number_format($q['vat_amount']); ?></td>
                </tr>
                <?php endif; ?>
                <?php endif; ?>
                <tr>
                    <td colspan="4" class="td-center">(<?php echo getLaoNumber($q['total_amount']); ?>)</td>
                    <td class="td-right">ລວມ / Total</td>
                    <td class="td-right" style="background-color: white !important;"><?php echo number_format($q['total_amount']); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div>ຜູ້ຈ່າຍ</div>
            </div>
            <div class="signature-box">
                <div>ຜູ້ຮັບເງິນ</div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto print when opened in popup
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
