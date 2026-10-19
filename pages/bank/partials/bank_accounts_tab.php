<?php $__bdc = getDisplayCurrency($pdo); $__bsym = $__bdc['symbol']; $__bdec=(int)$__bdc['decimals'];
if(!function_exists('__bnum')){function __bnum($dc,$k){return number_format(convertFromKip($k,$dc['code'],$dc['rates']),(int)$dc['decimals'],'.','');}}
?>
<?php
// pages/bank/partials/bank_accounts_tab.php
if (!defined('MINIPOS_APP')) {
    define('MINIPOS_APP', true);
}

// This branch's own bank accounts, plus any account shared across every branch (store_id = 0
// — same convention promotions uses for a "global" row).
ensureBranchNos($pdo, "st_bank_accounts");
$banks_stmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE store_id = ? OR store_id = 0 ORDER BY is_active DESC, id ASC");
$banks_stmt->execute([getActiveStoreId($pdo)]);
$bank_accounts_list = $banks_stmt ? $banks_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
attachBranchCodes($pdo, $bank_accounts_list, "st_bank_accounts"); // ລະຫັດ "1-1" ແຍກຕາມສາຂາ

// Helper get bank logo image
if (!function_exists('resolveBankLogo')) {
    function resolveBankLogo($logoFile, $bankCode = '') {
        if (empty($logoFile) || trim($logoFile) === '') {
            return '';
        }
        $filename = basename(trim($logoFile));
        $root = realpath(__DIR__ . '/../../../') ?: dirname(dirname(dirname(__DIR__)));
        $htdocsRoot = 'D:/xampp/htdocs/MiniPos';
        if (file_exists($root . '/assets/img/banks/' . $filename) || file_exists($htdocsRoot . '/assets/img/banks/' . $filename)) {
            return '../../assets/img/banks/' . $filename;
        }
        return '../../assets/img/banks/' . $filename;
    }
}

// Helper get bank QR image
if (!function_exists('resolveBankQr')) {
    function resolveBankQr($qrFile, $bankCode = '') {
        if (empty($qrFile) || trim($qrFile) === '') {
            return '';
        }
        $filename = basename(trim($qrFile));
        $root = realpath(__DIR__ . '/../../../') ?: dirname(dirname(dirname(__DIR__)));
        $htdocsRoot = 'D:/xampp/htdocs/MiniPos';
        if (file_exists($root . '/assets/img/qr/' . $filename) || file_exists($htdocsRoot . '/assets/img/qr/' . $filename)) {
            return '../../assets/img/qr/' . $filename;
        }
        return '';
    }
}

// Transfer received per bank for the date range and branch filter — the same helper the daily
// report uses (config/db.php), so both pages always show the same numbers.
$sales_by_bank_id = getBankTransferTotals($pdo, $start_date, $end_date, (!empty($filter_store_id) && $filter_store_id > 0) ? $filter_store_id : 0);
$total_transfer_sum = 0.00;
$total_transfer_count = 0;
// Total = the accounts actually listed on this page (not other branches' accounts)
foreach ($bank_accounts_list as $__bl) {
    $bs = $sales_by_bank_id[intval($__bl['id'])] ?? null;
    if (!$bs) continue;
    $total_transfer_sum += $bs['total_received'];
    $total_transfer_count += $bs['total_tx'];
}

// Helper brand colors for Lao Banks
function getBankBrandStyle($bankCode) {
    $code = strtoupper(trim($bankCode));
    
    if (strpos($code, 'BCEL') !== false || strpos($code, 'BCL') !== false || strpos($code, 'ONEPAY') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#fef08a', 'bg' => 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)', 'border' => '#dc2626', 'badge_bg' => '#991b1b', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'LDB') !== false || strpos($code, 'DEVELOPMENT') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#e0f2fe', 'bg' => 'linear-gradient(135deg, #38bdf8 0%, #0284c7 100%)', 'border' => '#0284c7', 'badge_bg' => '#0369a1', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'JDB') !== false || strpos($code, 'PHONGSAVANH') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#93c5fd', 'bg' => 'linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%)', 'border' => '#1d4ed8', 'badge_bg' => '#1e3a8a', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'STB') !== false || strpos($code, 'ST') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#ffedd5', 'bg' => 'linear-gradient(135deg, #ea580c 0%, #9a3412 100%)', 'border' => '#ea580c', 'badge_bg' => '#c2410c', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'APB') !== false || strpos($code, 'AGRICULTUR') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#fde047', 'bg' => 'linear-gradient(135deg, #15803d 0%, #14532d 100%)', 'border' => '#15803d', 'badge_bg' => '#16a34a', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'LVB') !== false || strpos($code, 'LAO-VIET') !== false || strpos($code, 'LAO VIET') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#fef08a', 'bg' => 'linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%)', 'border' => '#b91c1c', 'badge_bg' => '#dc2626', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'MARUHAN') !== false || strpos($code, 'MJB') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#a7f3d0', 'bg' => 'linear-gradient(135deg, #059669 0%, #064e3b 100%)', 'border' => '#059669', 'badge_bg' => '#047857', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'INDOCHINA') !== false || strpos($code, 'IB') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#facc15', 'bg' => 'linear-gradient(135deg, #18181b 0%, #09090b 100%)', 'border' => '#27272a', 'badge_bg' => '#27272a', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'KASIKORN') !== false || strpos($code, 'KBANK') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#fef08a', 'bg' => 'linear-gradient(135deg, #16a34a 0%, #14532d 100%)', 'border' => '#16a34a', 'badge_bg' => '#15803d', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'ICBC') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#fecdd3', 'bg' => 'linear-gradient(135deg, #be123c 0%, #881337 100%)', 'border' => '#be123c', 'badge_bg' => '#9f1239', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'BFL') !== false || strpos($code, 'BRED') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#c7d2fe', 'bg' => 'linear-gradient(135deg, #4338ca 0%, #1e1b4b 100%)', 'border' => '#4338ca', 'badge_bg' => '#3730a3', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'BIC') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#a5f3fc', 'bg' => 'linear-gradient(135deg, #0891b2 0%, #164e63 100%)', 'border' => '#0891b2', 'badge_bg' => '#0e7490', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }
    if (strpos($code, 'CANADIA') !== false) {
        return ['color' => '#ffffff', 'acc_color' => '#fecdd3', 'bg' => 'linear-gradient(135deg, #e11d48 0%, #881337 100%)', 'border' => '#e11d48', 'badge_bg' => '#be123c', 'text_sub' => 'rgba(255,255,255,0.85)'];
    }

    // Auto-generate dynamic corporate gradient palette for any new custom bank
    $hash = abs(crc32($code));
    $hue = $hash % 360;
    $bg = "linear-gradient(135deg, hsl({$hue}, 75%, 35%) 0%, hsl({$hue}, 80%, 20%) 100%)";
    $badge_bg = "hsl({$hue}, 80%, 22%)";
    $acc_color = "hsl({$hue}, 95%, 85%)";
    
    return [
        'color' => '#ffffff',
        'acc_color' => $acc_color,
        'bg' => $bg,
        'border' => "hsl({$hue}, 70%, 40%)",
        'badge_bg' => $badge_bg,
        'text_sub' => 'rgba(255,255,255,0.85)'
    ];
}
?>

<!-- Bank Summary Cards Grid -->
<div class="row mb-4">

  <?php foreach ($bank_accounts_list as $bank): ?>
    <?php 
      $bId = intval($bank['id']);
      $stats = $sales_by_bank_id[$bId] ?? ['total_received' => 0, 'total_tx' => 0];
      $receivedAmt = floatval($stats['total_received']);
      $txCount = intval($stats['total_tx']);
      $pct = ($total_transfer_sum > 0) ? round(($receivedAmt / $total_transfer_sum) * 100, 1) : 0;
      $logoSrc = resolveBankLogo($bank['bank_logo'], $bank['bank_code'] ?: $bank['bank_name']);
      $brand = getBankBrandStyle($bank['bank_code'] ?: $bank['bank_name']);
    ?>
    <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
      <div class="bank-card p-3 border-0 shadow-sm rounded-lg h-100 d-flex flex-column justify-content-between" style="background: <?php echo $brand['bg']; ?>; color: #ffffff; min-height: 150px; border-radius: 14px !important;">
        <div class="d-flex align-items-start justify-content-between mb-1">
          <div class="d-flex align-items-center" style="gap: 12px;">
            <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Bank Logo" style="width: 48px; height: 48px; object-fit: contain; border-radius: 10px; border: 1px solid rgba(255,255,255,0.3); background: #fff; padding: 2px; flex-shrink: 0;">
            <div style="min-width: 0;">
              <h6 class="font-weight-bold text-white mb-0 text-truncate" style="max-width: 200px; font-size: 1.1rem;" title="<?php echo htmlspecialchars($bank['bank_name']); ?>">
                <?php echo htmlspecialchars($bank['bank_name']); ?>
              </h6>
              <div class="font-weight-bold" style="font-size: 0.95rem; font-family: monospace; letter-spacing: 0.5px; color: <?php echo $brand['acc_color']; ?>;">
                <?php echo htmlspecialchars($bank['account_number']); ?>
              </div>
            </div>
          </div>

          <div class="dropdown">
            <button class="btn btn-sm btn-link text-white-50 p-0" type="button" data-toggle="dropdown">
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right shadow border-0" style="border-radius: 8px;">
              <a class="dropdown-item py-1 text-primary" href="#" onclick="editBankAccount(<?php echo htmlspecialchars(json_encode($bank)); ?>); return false;">
                <i class="fas fa-edit mr-1"></i> <?php echo htmlspecialchars(t('bank.edit_info', 'ແກ້ໄຂຂໍ້ມູນ')); ?>
              </a>
              <a class="dropdown-item py-1 text-danger" href="#" onclick="deleteBankAccount(<?php echo $bId; ?>, '<?php echo htmlspecialchars(addslashes($bank['bank_name'])); ?>'); return false;">
                <i class="fas fa-trash-alt mr-1"></i> <?php echo htmlspecialchars(t('bank.delete_account', 'ລົບບັນຊີ')); ?>
              </a>
            </div>
          </div>
        </div>

        <div class="pt-2 border-top d-flex align-items-center justify-content-between mt-2" style="border-color: rgba(255,255,255,0.2) !important;">
          <div>
            <small style="color: <?php echo $brand['text_sub']; ?>; font-size: 0.8rem;" class="font-weight-bold"><?php echo htmlspecialchars(t('bank.received_amount', 'ຍອດຮັບເງິນ:')); ?></small>
            <div class="font-weight-bold text-white" style="font-size: 1.3rem; line-height: 1.3;">
              <span class="counter-num" data-target="<?php echo __bnum($__bdc, $receivedAmt); ?>" data-suffix=" <?php echo htmlspecialchars($__bsym); ?>" data-decimals="<?php echo $__bdec; ?>"><?php echo displayMoney($pdo, $receivedAmt); ?></span>
            </div>
          </div>
          <div class="text-right">
            <span class="badge text-white px-2 py-1 shadow-xs" style="font-size: 0.8rem; background-color: <?php echo $brand['badge_bg']; ?>; border: 1px solid rgba(255,255,255,0.25);">
              <?php echo number_format($txCount); ?> <?php echo htmlspecialchars(t('bank.bill_suffix', 'ບິນ')); ?> (<?php echo $pct; ?>%)
            </span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Total Transfer Revenue Summary Card (Moved to the END) -->
  <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
    <div class="bank-card p-3 rounded-lg border-0 shadow-sm h-100 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%); color: #ffffff; min-height: 150px; border-radius: 14px !important;">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <small class="text-white-50 font-weight-bold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars(t('bank.total_transfer_received', 'ລວມຍອດຮັບເງິນໂອນທັງໝົດ')); ?></small>
          <h5 class="font-weight-bold text-white mb-0 mt-1" style="font-size: 1.6rem;">
            <span class="counter-num" data-target="<?php echo __bnum($__bdc, $total_transfer_sum); ?>" data-suffix=" <?php echo htmlspecialchars($__bsym); ?>" data-decimals="<?php echo $__bdec; ?>"><?php echo displayMoney($pdo, $total_transfer_sum); ?></span>
          </h5>
          <small class="text-white-50 font-weight-bold mt-2 d-block" style="font-size: 0.82rem;">
            <i class="fas fa-exchange-alt mr-1"></i> <?php echo htmlspecialchars(sprintf(t('bank.total_items', 'ລວມ %s ລາຍການ'), number_format($total_transfer_count))); ?>
          </small>
        </div>
        <div class="rounded-circle text-primary" style="width: 52px; height: 52px; font-size: 1.35rem; display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.95); box-shadow: 0 4px 12px rgba(0,0,0,0.15); flex-shrink: 0;">
          <i class="fas fa-qrcode"></i>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Bank Accounts Table & Transfers Breakdown -->
<div class="card border-0 shadow-sm rounded-lg overflow-hidden mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="font-weight-bold text-dark mb-0">
      <i class="fas fa-list-alt text-info mr-2"></i> <?php echo htmlspecialchars(t('bank.table_title', 'ຕາຕະລາງລາຍການບັນຊີທະນາຄານ ແລະ ຍອດຮັບເງິນໂອນ')); ?>
    </h6>
    <!-- <span class="badge badge-light border text-secondary font-weight-bold p-2">
      ລວມບັນຊີທັງໝົດ: <?php echo count($bank_accounts_list); ?> ບັນຊີ
    </span> -->
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
      <thead class="bg-light text-uppercase text-secondary font-weight-bold" style="font-size: 0.8rem;">
        <tr>
          <th class="py-3 px-4" style="width: 60px;"><?php echo htmlspecialchars(t('bank.col_code', 'ລະຫັດ')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('bank.col_bank', 'ທະນາຄານ')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('bank.col_account_number', 'ເລກບັນຊີ')); ?></th>
          <th class="py-3"><?php echo htmlspecialchars(t('bank.col_account_name', 'ຊື່ບັນຊີ')); ?></th>
          <th class="py-3 text-center"><?php echo htmlspecialchars(t('bank.col_qr', 'QR')); ?></th>
          <th class="py-3 text-right"><?php echo htmlspecialchars(t('bank.col_transfer_received', 'ຍອດຮັບເງິນໂອນ')); ?></th>
          <th class="py-3 text-center"><?php echo htmlspecialchars(t('bank.col_bill_count', 'ຈຳນວນບິນ')); ?></th>
          <th class="py-3 text-center"><?php echo htmlspecialchars(t('bank.col_status', 'ສະຖານະ')); ?></th>
          <th class="py-3 text-center" style="width: 110px;"><?php echo htmlspecialchars(t('bank.col_manage', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bank_accounts_list)): ?>
          <tr>
            <td colspan="9" class="text-center py-4 text-muted">
              <i class="fas fa-university fa-3x mb-2 text-secondary"></i>
              <div><?php echo htmlspecialchars(t('bank.empty_state', 'ຍັງບໍ່ທັນມີຂໍ້ມູນບັນຊີທະນາຄານ')); ?></div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($bank_accounts_list as $index => $bank): ?>
            <?php 
              $bId = intval($bank['id']);
              $stats = $sales_by_bank_id[$bId] ?? ['total_received' => 0, 'total_tx' => 0];
              $amt = floatval($stats['total_received']);
              $tx = intval($stats['total_tx']);
              $logoSrc = resolveBankLogo($bank['bank_logo'], $bank['bank_code'] ?: $bank['bank_name']);
              $qrSrc = resolveBankQr($bank['qr_code_img']);
              $brand = getBankBrandStyle($bank['bank_code'] ?: $bank['bank_name']);
            ?>
            <tr>
              <td class="py-3 px-4 text-secondary font-weight-bold" style="white-space: nowrap;"><?php echo branchCodeHtml($bank['display_code'] ?? ($index + 1)); ?></td>
              <td class="py-3 font-weight-bold">
                <div class="d-flex align-items-center" style="gap: 10px;">
                  <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Bank Logo" style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; padding: 2px;">
                  <div>
                    <div><?php echo htmlspecialchars($bank['bank_name']); ?></div>
                  </div>
                </div>
              </td>
              <td class="py-3 font-weight-bold">
                <span class="badge px-2.5 py-1.5 border shadow-2xs" style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: #0f172a !important; background: #f8fafc; border-color: #cbd5e1 !important; letter-spacing: 0.5px;">
                  <?php echo htmlspecialchars($bank['account_number']); ?>
                </span>
              </td>
              <td class="py-3 text-muted"><?php echo htmlspecialchars($bank['account_name'] ?: '-'); ?></td>
              <td class="py-3 text-center">
                <?php if ($qrSrc !== ''): ?>
                <img src="<?php echo htmlspecialchars($qrSrc); ?>" style="width: 38px; height: 38px; object-fit: contain; border-radius: 6px; border: 1px solid #cbd5e1; padding: 2px; background: #fff;" onerror="this.outerHTML='<span class=&quot;text-muted&quot;>-</span>';">
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="py-3 text-right font-weight-bold text-success" style="font-size: 0.95rem;">
                <?php echo displayMoney($pdo, $amt); ?>
              </td>
              <td class="py-3 text-center font-weight-bold text-primary">
                <?php echo number_format($tx); ?> <?php echo htmlspecialchars(t('bank.bill_suffix', 'ບິນ')); ?>
              </td>
              <td class="py-3 text-center">
                <?php if ($bank['is_active']): ?>
                  <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars(t('bank.status_active', 'ເປີດ')); ?></span>
                <?php else: ?>
                  <span class="badge badge-secondary px-2 py-1"><i class="fas fa-minus-circle mr-1"></i> <?php echo htmlspecialchars(t('bank.status_inactive', 'ປິດ')); ?></span>
                <?php endif; ?>
              </td>
              <td class="py-3 text-center">
                <?php if (hasPermission('accounting', 'edit') || hasPermission('bank', 'edit') || hasPermission('accounting', 'del') || hasPermission('bank', 'del')): ?>
                  <div class="btn-group btn-group-sm">
                    <?php if (hasPermission('accounting', 'edit') || hasPermission('bank', 'edit')): ?>
                      <button type="button" class="btn btn-sm btn-outline-primary" title="<?php echo htmlspecialchars(t('bank.action_edit', 'ແກ້ໄຂ')); ?>" onclick="editBankAccount(<?php echo htmlspecialchars(json_encode($bank)); ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (hasPermission('accounting', 'del') || hasPermission('bank', 'del')): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger" title="<?php echo htmlspecialchars(t('bank.action_delete', 'ລົບ')); ?>" onclick="deleteBankAccount(<?php echo $bId; ?>, '<?php echo htmlspecialchars(addslashes($bank['bank_name'])); ?>')">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('bank.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
var I18N_BANK = <?php echo tjson([
    'bank.modal_title_edit' => 'ແກ້ໄຂຂໍ້ມູນບັນຊີທະນາຄານ',
    'bank.modal_title_add' => 'ເພີ່ມບັນຊີທະນາຄານໃໝ່',
    'bank.validate_title' => 'ກະລຸນາປ້ອນຂໍ້ມູນ',
    'bank.validate_bank_name' => 'ກະລຸນາປ້ອນ ຊື່ທະນາຄານ!',
    'bank.validate_account_number' => 'ກະລຸນາປ້ອນ ເລກບັນຊີທະນາຄານ!',
    'bank.validate_account_name' => 'ກະລຸນາປ້ອນ ຊື່ເຈົ້າຂອງບັນຊີ!',
    'bank.btn_ok' => 'ຕົກລົງ',
    'bank.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
    'bank.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບບັນຊີ "%s" ແທ້ຫຼືບໍ່?',
    'bank.confirm_delete_button' => 'ລົບເລີຍ',
    'bank.cancel' => 'ຍົກເລີກ',
]); ?>;

function editBankAccount(bank) {
  $('#form_bank_action').val('edit_bank_account');
  $('#form_bank_id').val(bank.id);
  $('#form_bank_name').val(bank.bank_name);
  $('#form_account_number').val(bank.account_number);
  $('#form_account_name').val(bank.account_name);
  $('#form_is_active').val(bank.is_active || 1);
  $('#form_store_id').val(bank.store_id);
  setBankImage('bank_logo', bank.bank_logo ? '../../assets/img/banks/' + bank.bank_logo : '');
  setBankImage('qr_code_img', bank.qr_code_img ? '../../assets/img/qr/' + bank.qr_code_img : '');

  $('#bankModalTitle').html('<i class="fas fa-edit text-primary mr-1"></i> ' + I18N_BANK['bank.modal_title_edit']);
  $('#addBankAccountModal').modal('show');
}

// Logo / QR picker in the bank modal. field = 'bank_logo' | 'qr_code_img'.
// setBankImage shows a saved image (or clears the box) and resets the pending upload/removal.
function setBankImage(field, src) {
  $('#form_' + field).val('');
  $('#form_remove_' + field).val('0');
  $('#preview_' + field).attr('src', src || '');
  $('#pick_' + field).toggleClass('has-img', !!src);
}

function previewBankImage(input, field) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function (ev) {
    $('#preview_' + field).attr('src', ev.target.result);
    $('#pick_' + field).addClass('has-img');
    $('#form_remove_' + field).val('0');
  };
  reader.readAsDataURL(input.files[0]);
}

// Closing the modal any way (backdrop, Esc) returns it to a blank "add" form, so the next
// "add" doesn't show the previous account's images.
$(function () {
  $('#addBankAccountModal').on('hidden.bs.modal', resetBankForm);
});

// × on the corner: drop the picked file, and on save also delete the stored image
function removeBankImage(field) {
  $('#form_' + field).val('');
  $('#preview_' + field).attr('src', '');
  $('#pick_' + field).removeClass('has-img');
  $('#form_remove_' + field).val('1');
}

function validateBankForm(e) {
  var bName = $.trim($('#form_bank_name').val());
  var accNum = $.trim($('#form_account_number').val());
  var accName = $.trim($('#form_account_name').val());

  if (!bName) {
    if (e && e.preventDefault) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_BANK['bank.validate_title'],
      text: I18N_BANK['bank.validate_bank_name'],
      confirmButtonColor: '#3085d6',
      confirmButtonText: I18N_BANK['bank.btn_ok']
    }).then(function() {
      setTimeout(function() { $('#form_bank_name').focus(); }, 150);
    });
    return false;
  }

  if (!accNum) {
    if (e && e.preventDefault) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_BANK['bank.validate_title'],
      text: I18N_BANK['bank.validate_account_number'],
      confirmButtonColor: '#3085d6',
      confirmButtonText: I18N_BANK['bank.btn_ok']
    }).then(function() {
      setTimeout(function() { $('#form_account_number').focus(); }, 150);
    });
    return false;
  }

  if (!accName) {
    if (e && e.preventDefault) e.preventDefault();
    Swal.fire({
      icon: 'warning',
      title: I18N_BANK['bank.validate_title'],
      text: I18N_BANK['bank.validate_account_name'],
      confirmButtonColor: '#3085d6',
      confirmButtonText: I18N_BANK['bank.btn_ok']
    }).then(function() {
      setTimeout(function() { $('#form_account_name').focus(); }, 150);
    });
    return false;
  }

  return true;
}

function resetBankForm() {
  $('#form_bank_action').val('add_bank_account');
  $('#form_bank_id').val('0');
  $('#form_bank_name').val('');
  $('#form_account_number').val('');
  $('#form_account_name').val('');
  $('#form_is_active').val('1');
  $('#form_store_id').val(<?php echo (int)$userStoreId; ?>);
  setBankImage('bank_logo', '');
  setBankImage('qr_code_img', '');
  $('#bankModalTitle').html('<i class="fas fa-plus-circle text-primary mr-1"></i> ' + I18N_BANK['bank.modal_title_add']);
}

function deleteBankAccount(bankId, bankName) {
  Swal.fire({
    title: I18N_BANK['bank.confirm_delete_title'],
    text: I18N_BANK['bank.confirm_delete_text'].replace('%s', bankName),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: I18N_BANK['bank.confirm_delete_button'],
    cancelButtonText: I18N_BANK['bank.cancel']
  }).then((result) => {
    if (result.isConfirmed) {
      var form = $('<form action="" method="POST"></form>');
      form.append('<input type="hidden" name="action" value="delete_bank_account">');
      form.append(`<input type="hidden" name="bank_id" value="${bankId}">`);
      $('body').append(form);
      form.submit();
    }
  });
}

// CountUp Animated Numbers (ເງິນແລ່ນ)
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.counter-num').forEach(function (el) {
    var target = parseFloat(el.getAttribute('data-target') || '0');
    var suffix = el.getAttribute('data-suffix') || '';
    if (isNaN(target)) target = 0;
    var startTime = null;
    var duration = 1200;
    function step(ts) {
      if (!startTime) startTime = ts;
      var prog = Math.min((ts - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - prog, 3);
      var val = Math.floor(ease * target);
      el.textContent = val.toLocaleString('en-US',{maximumFractionDigits:<?php echo $__bdec; ?>}) + suffix;
      if (prog < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = target.toLocaleString('en-US',{maximumFractionDigits:<?php echo $__bdec; ?>}) + suffix;
      }
    }
    requestAnimationFrame(step);
  });
});
</script>
