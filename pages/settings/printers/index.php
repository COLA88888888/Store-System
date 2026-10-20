<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຕັ້ງຄ່າເຄື່ອງພິມຂອງສາຂາ: ເພີ່ມ/ແກ້ໄຂ/ລົບ ເຄື່ອງພິມ IP/USB/browser, ເຄື່ອງພິມບິນຫຼັກ ແລະ ໂໝດ print agent.
 *   - partials/actions.php : ການກະທຳ POST ແລະ token ຂອງ print agent
 */
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('printers') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Printers are physical devices sitting in one specific shop — unlike bank accounts /
// promotions, they must never be mixed across branches in this list, not even for the main
// branch / admin. Everyone, including canViewAllBranches() users, only ever sees and manages
// the active branch's own printers (switch branch via the store switcher to manage another).
$myPrinterStoreId = getActiveStoreId($pdo);

// Printers installed in Windows on this PC — suggestions for a 'usb' printer's name field.
function minipos_windows_printers() {
    if (stripos(PHP_OS, 'WIN') !== 0 || !function_exists('exec')) return [];
    $out = [];
    @exec('powershell -NoProfile -NonInteractive -Command "Get-Printer | Select-Object -ExpandProperty Name" 2>NUL', $out);
    return array_values(array_filter(array_map('trim', $out), 'strlen'));
}

// The PowerShell call takes ~1s, which used to hold up every page load (and trip the "slow
// network" loader). The page now fetches the list with this AJAX call after it has shown,
// and the result is kept in the session for 10 minutes.
if (($_GET['ajax'] ?? '') === 'win_printers') {
    $cache = $_SESSION['minipos_win_printers'] ?? null;
    if (!is_array($cache) || ($cache['at'] ?? 0) < time() - 600) {
        $cache = ['at' => time(), 'list' => minipos_windows_printers()];
        $_SESSION['minipos_win_printers'] = $cache;
    }
    session_write_close();
    header('Content-Type: application/json');
    echo json_encode($cache['list'], JSON_UNESCAPED_UNICODE);
    exit();
}

// ການກະທຳ POST ແລະ token ຂອງ print agent
require __DIR__ . '/partials/actions.php';
?>

<div class="container-fluid p-4">
  <div class="row mb-3 align-items-center">
    <div class="col-sm-6">
      <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
        <i class="fas fa-print text-primary mr-2"></i> <?php echo htmlspecialchars(t('printers.page_title', 'ຕັ້ງຄ່າເຄື່ອງພິມ')); ?>
      </h5>
    </div>
    <div class="col-sm-6 text-right">
      <?php if (hasPermission('printers', 'add')): ?>
        <button type="button" class="btn btn-primary px-3.5 font-weight-bold text-white shadow-sm" data-toggle="modal" data-target="#addPrinterModal" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1.5"></i> <?php echo htmlspecialchars(t('printers.add_new', 'ເພີ່ມເຄື່ອງພິມໃໝ່')); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
          title: <?php echo json_encode($message); ?>,
          showConfirmButton: false,
          timer: 1300,
          timerProgressBar: true
        });
      });
    </script>
  <?php endif; ?>

  <div class="alert alert-light border d-flex align-items-start" style="border-radius: 10px; font-size: 0.85rem;">
    <i class="fas fa-info-circle text-primary mr-2 mt-1"></i>
    <div><?php echo htmlspecialchars(t('printers.receipt_hint', 'ໝາຍເຫດ: ຖ້າຕັ້ງເຄື່ອງພິມໃບບິນຫຼັກໄວ້ (ໄອຄອນໃບບິນສີຂຽວ), ໃບບິນລູກຄ້າຈະພິມຜ່ານ LAN ໂດຍກົງ ບໍ່ຂຶ້ນປັອບອັບພິມຂອງບຣາວເຊີ. ຖ້າບໍ່ຕັ້ງ, ໃບບິນຈະພິມຜ່ານບຣາວເຊີຄືເກົ່າ.')); ?></div>
  </div>

  <?php if (hasPermission('printers', 'edit')): ?>
  <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
    <div class="card-header bg-white py-3 px-4 border-bottom">
      <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-server mr-2 text-primary"></i> <?php echo htmlspecialchars(t('printers.mode_title', 'ຮູບແບບການພິມ')); ?></h6>
    </div>
    <div class="card-body px-4 py-3">
      <div class="row">
      <div class="col-lg-6 mb-2">
      <form action="" method="POST" class="form-inline mb-2">
        <input type="hidden" name="action" value="set_print_mode">
        <select name="print_mode" class="form-control mr-2" style="height: 42px; min-width: 280px;">
          <option value="direct" <?php echo $printMode === 'direct' ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars(t('printers.mode_direct', 'ພິມໂດຍກົງ — ລະບົບແລ່ນຢູ່ຄອມໃນຮ້ານ')); ?>
          </option>
          <option value="agent" <?php echo $printMode === 'agent' ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars(t('printers.mode_agent', 'ຜ່ານຕົວແທນພິມ — ລະບົບຢູ່ເຊີບເວີນອກ')); ?>
          </option>
        </select>
        <button type="submit" class="btn btn-primary font-weight-bold px-3" style="height: 42px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><?php echo htmlspecialchars(t('printers.btn_save', 'ບັນທຶກ')); ?></button>
      </form>
      <small class="text-muted d-block"><?php echo htmlspecialchars(t('printers.mode_hint', 'ເຊີບເວີນອກຮ້ານຕໍ່ຫາປິ່ນເຕີ 192.168.x.x ບໍ່ໄດ້ — ກໍລະນີນັ້ນຕ້ອງໃຊ້ຕົວແທນພິມ')); ?></small>
      </div>
      <div class="col-lg-6 mb-2">

      <?php // Shown in both modes on purpose: the token has to be copied into the shop PC
            // BEFORE the agent can work, and hiding it until agent mode is picked sends people
            // hunting for something that isn't on screen yet. ?>
        <div class="mb-2">
          <span class="font-weight-bold text-dark small"><?php echo htmlspecialchars(t('printers.agent_token', 'ລະຫັດຕົວແທນພິມ (ໃສ່ໃນ tools/print-agent.bat):')); ?></span>
          <code class="d-block mt-1 p-2" style="background:#f1f5f9; border-radius:6px; word-break:break-all; font-size:0.85rem;"><?php echo htmlspecialchars($printAgentToken); ?></code>
        </div>
        <form action="" method="POST" style="display:inline;" onsubmit="return prnConfirmSubmit(this, '<?php echo htmlspecialchars(t('printers.agent_token_confirm', 'ສ້າງລະຫັດໃໝ່? ຕົວແທນພິມເກົ່າຈະໃຊ້ບໍ່ໄດ້ຈົນກວ່າຈະອັບເດດລະຫັດ.'), ENT_QUOTES); ?>', false);">
          <input type="hidden" name="action" value="regen_agent_token">
          <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync-alt mr-1"></i><?php echo htmlspecialchars(t('printers.agent_token_new', 'ສ້າງລະຫັດໃໝ່')); ?></button>
        </form>
        <span class="badge <?php echo $pendingPrintJobs > 0 ? 'badge-warning' : 'badge-light border text-muted'; ?> ml-2 px-2 py-1">
          <?php echo htmlspecialchars(t('printers.agent_pending', 'ງານພິມຄ້າງ')); ?>: <?php echo $pendingPrintJobs; ?>
        </span>
      </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

<?php require __DIR__ . '/partials/bluetooth_card.php'; /* ເຄື່ອງພິມ Bluetooth: ເຊື່ອມຕໍ່ / ສະຖານະ / ທົດສອບ */ ?>

  <div class="card border-0 shadow-sm" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-list mr-2 text-primary"></i> <?php echo htmlspecialchars(t('printers.list_title', 'ລາຍການເຄື່ອງພິມໃນລະບົບ')); ?></h6>
      <span class="badge badge-light border font-weight-bold text-muted px-2.5 py-1"><?php echo count($printers); ?> <?php echo htmlspecialchars(t('printers.count_suffix', 'ເຄື່ອງ')); ?></span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
        <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
          <tr style="white-space: nowrap;">
            <th class="text-center" style="width: 70px; white-space: nowrap;"><?php echo htmlspecialchars(t('printers.col_no', 'ລ/ດ')); ?></th>
            <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('printers.col_name', 'ຊື່ເຄື່ອງພິມ')); ?></th>
            <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('printers.col_type', 'ປະເພດການເຊື່ອມຕໍ່')); ?></th>
            <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('printers.col_ip', 'IP Address')); ?></th>
            <th class="text-center" style="white-space: nowrap;"><?php echo htmlspecialchars(t('printers.col_status', 'ສະຖານະ')); ?></th>
            <th class="text-center" style="width: 140px; white-space: nowrap;"><?php echo htmlspecialchars(t('printers.col_actions', 'ຈັດການ')); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($printers)): ?>
            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-print fa-2x mb-2 d-block text-secondary opacity-50"></i><?php echo htmlspecialchars(t('printers.empty_list', 'ບໍ່ມີລາຍການເຄື່ອງພິມ')); ?></td></tr>
          <?php else: ?>
            <?php $i = 1; foreach ($printers as $pr): ?>
              <tr style="white-space: nowrap;">
                <td class="text-center font-weight-bold text-muted" style="white-space: nowrap;"><?php echo $i++; ?></td>
                <td class="font-weight-bold text-dark text-nowrap" style="white-space: nowrap;">
                  <?php echo htmlspecialchars($pr['name']); ?>
                  <?php if (!empty($pr['is_receipt_printer'])): ?>
                    <span class="badge badge-success px-2 py-1 ml-1" style="font-size: 0.72rem;"><i class="fas fa-receipt mr-1"></i><?php echo htmlspecialchars(t('printers.receipt_badge', 'ໃບບິນຫຼັກ')); ?></span>
                  <?php endif; ?>
                </td>
                <td style="white-space: nowrap;">
                  <span class="badge <?php echo $pr['type'] === 'ip' ? 'badge-primary' : ($pr['type'] === 'usb' ? 'badge-info' : 'badge-secondary'); ?> px-2.5 py-1" style="font-size: 0.78rem; white-space: nowrap;">
                    <?php echo $pr['type'] === 'usb' ? '<i class="fab fa-usb mr-1"></i> USB' : ($pr['type'] === 'ip' ? '<i class="fas fa-network-wired mr-1"></i> Network IP' : '<i class="fas fa-desktop mr-1"></i> Browser'); ?>
                  </span>
                  <small class="text-muted font-weight-bold ml-1"><?php echo intval($pr['paper_width'] ?? 80) === 58 ? '58 mm' : '80 mm'; ?></small>
                </td>
                <td class="font-weight-bold" style="font-family: monospace; color: #2563eb; font-size: 0.9rem; white-space: nowrap;">
                  <?php echo !empty($pr['ip_address']) ? htmlspecialchars($pr['ip_address']) : '<span class="text-muted font-weight-normal">-</span>'; ?>
                </td>
                <td class="text-center" style="white-space: nowrap;">
                  <?php if (hasPermission('printers', 'edit')): ?>
                    <form action="" method="POST" style="display:inline-block; vertical-align: middle;">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?php echo $pr['id']; ?>">
                      <input type="hidden" name="status" value="<?php echo $pr['status'] ? 0 : 1; ?>">
                      <button type="submit" class="btn btn-link p-0 border-0 shadow-none align-middle" style="outline: none; text-decoration: none; cursor: pointer; transform: none !important;" title="<?php echo htmlspecialchars($pr['status'] ? t('printers.disable', 'ປິດໃຊ້ງານ') : t('printers.enable', 'ເປີດໃຊ້ງານ')); ?>">
                        <div style="width: 48px; height: 20px; background: <?php echo $pr['status'] ? '#10b981' : '#cbd5e1'; ?>; border-radius: 20px; position: relative; transition: background-color 0.2s ease-in-out; display: inline-block; vertical-align: middle;">
                          <div style="width: 14px; height: 14px; background: #ffffff; border-radius: 50%; position: absolute; top: 3px; <?php echo $pr['status'] ? 'right: 3px;' : 'left: 3px;'; ?> transition: all 0.2s ease-in-out; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"></div>
                        </div>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="badge <?php echo $pr['status'] ? 'badge-success' : 'badge-secondary'; ?> px-2 py-1">
                      <?php echo htmlspecialchars($pr['status'] ? t('printers.enable', 'ເປີດໃຊ້ງານ') : t('printers.disable', 'ປິດໃຊ້ງານ')); ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-center" style="white-space: nowrap;">
                  <?php if (hasPermission('printers', 'edit') || hasPermission('printers', 'del')): ?>
                    <div class="btn-group btn-group-sm" role="group">
                      <?php if ($pr['type'] === 'ip' && !empty($pr['ip_address'])): ?>
                        <button type="button" class="btn btn-outline-success btn-sm px-2" title="<?php echo htmlspecialchars(t('printers.btn_test', 'ທົດສອບພິມ')); ?>" onclick="testPrinter(<?php echo (int)$pr['id']; ?>, this)">
                          <i class="fas fa-vial"></i>
                        </button>
                      <?php endif; ?>
                      <?php if ($pr['type'] === 'ip' && !empty($pr['ip_address']) && hasPermission('printers', 'edit')): ?>
                        <form action="" method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="<?php echo !empty($pr['is_receipt_printer']) ? 'unset_receipt_printer' : 'set_receipt_printer'; ?>">
                          <input type="hidden" name="id" value="<?php echo $pr['id']; ?>">
                          <button type="submit" class="btn <?php echo !empty($pr['is_receipt_printer']) ? 'btn-success' : 'btn-outline-secondary'; ?> btn-sm px-2"
                                  title="<?php echo htmlspecialchars(!empty($pr['is_receipt_printer']) ? t('printers.unset_receipt', 'ຍົກເລີກເປັນເຄື່ອງພິມໃບບິນຫຼັກ') : t('printers.set_receipt', 'ຕັ້ງເປັນເຄື່ອງພິມໃບບິນຫຼັກ')); ?>">
                            <i class="fas fa-receipt"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                      <?php if (hasPermission('printers', 'edit')): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm px-2" title="<?php echo htmlspecialchars(t('printers.edit', 'ແກ້ໄຂ')); ?>"
                                onclick="editPrinter(<?php echo $pr['id']; ?>, '<?php echo htmlspecialchars(addslashes($pr['name'])); ?>', '<?php echo htmlspecialchars(addslashes($pr['type'])); ?>', '<?php echo htmlspecialchars(addslashes($pr['ip_address'] ?? '')); ?>', <?php echo intval($pr['paper_width'] ?? 80) === 58 ? 58 : 80; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                      <?php endif; ?>
                      <?php if (hasPermission('printers', 'del')): ?>
                        <form action="" method="POST" onsubmit="return prnConfirmSubmit(this, '<?php echo htmlspecialchars(t('printers.delete_confirm', 'ຕ້ອງການລົບເຄື່ອງພິມນີ້ແທ້ຫຼືບໍ່?'), ENT_QUOTES); ?>', true);" style="display:inline;">
                          <input type="hidden" name="action" value="delete_printer">
                          <input type="hidden" name="id" value="<?php echo $pr['id']; ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm px-2" title="<?php echo htmlspecialchars(t('printers.delete', 'ລຶບ')); ?>"><i class="fas fa-trash-alt"></i></button>
                        </form>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('printers.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Add Printer -->
<div class="modal fade" id="addPrinterModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="add_printer">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
            <i class="fas fa-plus-circle mr-2"></i> <?php echo htmlspecialchars(t('printers.modal_add_title', 'ເພີ່ມເຄື່ອງພິມໃໝ່')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_name', 'ຊື່ເຄື່ອງພິມ:')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="<?php echo htmlspecialchars(t('printers.field_name_placeholder', 'ເຄື່ອງພິມໃບບິນໜ້າຮ້ານ, ເຄື່ອງພິມເຮືອນຄົວ...')); ?>" required style="height: 42px;">
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_type', 'ປະເພດການເຊື່ອມຕໍ່:')); ?> <span class="text-danger">*</span></label>
            <select name="type" class="form-control" style="height: 42px;">
              <option value="browser">Browser / Windows Driver / USB</option>
              <option value="ip">Network LAN / WiFi (IP Address)</option>
              <option value="usb">USB — <?php echo htmlspecialchars(t('printers.type_usb_direct', 'ພິມກົງ ຄືກັບ LAN (ໃສ່ຊື່ເຄື່ອງພິມ Windows)')); ?></option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_ip', 'IP Address ເຄື່ອງພິມ (ຖ້າເປັນ Network IP):')); ?></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-network-wired text-muted"></i></span></div>
              <input type="text" name="ip_address" list="winPrinterList" autocomplete="off" class="form-control font-weight-bold text-primary" placeholder="<?php echo htmlspecialchars(t('printers.field_ip_placeholder', '192.168...')); ?>" style="height: 42px;">
            </div>
            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars(t('printers.lan_hint', 'ເຄື່ອງພິມ Network LAN ຈະໄດ້ຮັບໃບຍ່ອຍໂດຍກົງ ບໍ່ຕ້ອງກົດພິມ. ໃສ່ IP ຂອງເຄື່ອງພິມ (ພອດ 9100 ເປັນຄ່າເລີ່ມຕົ້ນ ຫຼື IP:ພອດ)')); ?></small>
            <small class="text-info d-block mt-1"><i class="fab fa-usb mr-1"></i><?php echo htmlspecialchars(t('printers.usb_hint', 'USB: ໃສ່ຊື່ເຄື່ອງພິມໃນ Windows (ເລືອກຈາກລາຍການໄດ້). ໃບບິນຈະອອກຄືກັບ LAN ທຸກຢ່າງ, ບໍ່ມີໜ້າຕ່າງພິມ.')); ?></small>
          </div>
          <div class="form-group mb-0 mt-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_paper_width', 'ຂະໜາດເຈ້ຍ:')); ?></label>
            <select name="paper_width" class="form-control" style="height: 42px;">
              <option value="80">80 mm</option>
              <option value="58">58 mm</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-0">
          <button type="button" class="btn btn-light font-weight-bold px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('printers.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary text-white font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><?php echo htmlspecialchars(t('printers.btn_save', 'ບັນທຶກ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Edit Printer -->
<div class="modal fade" id="editPrinterModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_printer">
        <input type="hidden" name="id" id="edit_printer_id" value="">
        <div class="modal-header text-white py-3 px-4" style="background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-size: 1rem;">
            <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('printers.modal_edit_title', 'ແກ້ໄຂຂໍ້ມູນເຄື່ອງພິມ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_name', 'ຊື່ເຄື່ອງພິມ:')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="name" id="edit_printer_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('printers.field_name_placeholder_short', 'ເຊັ່ນ: ເຄື່ອງພິມໃບບິນໜ້າຮ້ານ...')); ?>" required style="height: 42px;">
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_type', 'ປະເພດການເຊື່ອມຕໍ່:')); ?> <span class="text-danger">*</span></label>
            <select name="type" id="edit_printer_type" class="form-control" style="height: 42px;">
              <option value="browser">Browser / Windows Driver / USB</option>
              <option value="ip">Network LAN / WiFi (IP Address)</option>
              <option value="usb">USB — <?php echo htmlspecialchars(t('printers.type_usb_direct', 'ພິມກົງ ຄືກັບ LAN (ໃສ່ຊື່ເຄື່ອງພິມ Windows)')); ?></option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_ip_edit', 'IP Address ເຄື່ອງພິມ:')); ?></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-network-wired text-muted"></i></span></div>
              <input type="text" name="ip_address" id="edit_printer_ip" list="winPrinterList" autocomplete="off" class="form-control font-weight-bold text-primary" placeholder="192.168.1.200" style="height: 42px;">
            </div>
            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars(t('printers.lan_hint', 'ເຄື່ອງພິມ Network LAN ຈະໄດ້ຮັບໃບຍ່ອຍໂດຍກົງ ບໍ່ຕ້ອງກົດພິມ. ໃສ່ IP ຂອງເຄື່ອງພິມ (ພອດ 9100 ເປັນຄ່າເລີ່ມຕົ້ນ ຫຼື IP:ພອດ)')); ?></small>
            <small class="text-info d-block mt-1"><i class="fab fa-usb mr-1"></i><?php echo htmlspecialchars(t('printers.usb_hint', 'USB: ໃສ່ຊື່ເຄື່ອງພິມໃນ Windows (ເລືອກຈາກລາຍການໄດ້). ໃບບິນຈະອອກຄືກັບ LAN ທຸກຢ່າງ, ບໍ່ມີໜ້າຕ່າງພິມ.')); ?></small>
          </div>
          <div class="form-group mb-0 mt-3">
            <label class="font-weight-bold text-dark small mb-1"><?php echo htmlspecialchars(t('printers.field_paper_width', 'ຂະໜາດເຈ້ຍ:')); ?></label>
            <select name="paper_width" id="edit_printer_paper" class="form-control" style="height: 42px;">
              <option value="80">80 mm</option>
              <option value="58">58 mm</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pb-4 px-4 pt-0">
          <button type="button" class="btn btn-light font-weight-bold px-3" data-dismiss="modal"><?php echo htmlspecialchars(t('printers.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;"><?php echo htmlspecialchars(t('printers.btn_update', 'ອັບເດດ')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Windows printer names for the USB name field — filled after the page has shown (see ?ajax=win_printers above) -->
<datalist id="winPrinterList"></datalist>
<script>
  window.addEventListener('load', function () {
    fetch('?ajax=win_printers', { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (names) {
        var dl = document.getElementById('winPrinterList');
        if (!dl || !Array.isArray(names)) return;
        names.forEach(function (n) {
          var o = document.createElement('option');
          o.value = n;
          dl.appendChild(o);
        });
      })
      .catch(function () {});
  });
</script>

<script>
function editPrinter(id, name, type, ip, paper) {
  document.getElementById('edit_printer_id').value = id;
  document.getElementById('edit_printer_name').value = name;
  document.getElementById('edit_printer_type').value = type;
  document.getElementById('edit_printer_ip').value = ip;
  document.getElementById('edit_printer_paper').value = String(paper || 80);
  $('#editPrinterModal').modal('show');
}

// SweetAlert confirm for a form submit (replaces the browser's native confirm() box).
// Returns false to stop the submit; on "confirm" the form is submitted for real.
// form.submit() skips onsubmit, so this doesn't loop.
function prnConfirmSubmit(form, text, danger) {
  Swal.fire({
    icon: danger ? 'warning' : 'question',
    title: <?php echo json_encode(t('printers.confirm_title', 'ຢືນຢັນ'), JSON_UNESCAPED_UNICODE); ?>,
    text: text,
    showCancelButton: true,
    confirmButtonColor: danger ? '#ef4444' : '#2563eb',
    cancelButtonColor: '#64748b',
    confirmButtonText: danger
      ? <?php echo json_encode(t('printers.confirm_delete_btn', 'ລົບເລີຍ'), JSON_UNESCAPED_UNICODE); ?>
      : <?php echo json_encode(t('printers.confirm_ok_btn', 'ຕົກລົງ'), JSON_UNESCAPED_UNICODE); ?>,
    cancelButtonText: <?php echo json_encode(t('printers.cancel', 'ຍົກເລີກ'), JSON_UNESCAPED_UNICODE); ?>,
    reverseButtons: true
  }).then(function (r) { if (r.isConfirmed) form.submit(); });
  return false;
}

function testPrinter(id, btn) {
  if (btn) btn.disabled = true;
  $.post('', { action: 'test_print', id: id }, null, 'json')
    .done(function (res) {
      Swal.fire({ icon: res && res.success ? 'success' : 'error', title: (res && res.message) || '' });
    })
    .fail(function () {
      Swal.fire({ icon: 'error', title: 'Error' });
    })
    .always(function () {
      if (btn) btn.disabled = false;
    });
}
</script>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
