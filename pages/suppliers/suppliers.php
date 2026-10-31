<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າຈັດການຜູ້ສະໜອງ: ລາຍຊື່, ເພີ່ມ/ແກ້ໄຂ/ລົບ ຜ່ານ AJAX (pages/suppliers/api).
 *   - partials/suppliers_js.php : JS ຂອງໜ້າ
 */
// Standalone Page: ຈັດການຜູ້ສະໜອງ (Suppliers) — CRUD, ໃຊ້ເລືອກຕອນເພີ່ມສິນຄ້າ
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('suppliers'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$isAdmin = (
    ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' ||
    strtolower($_SESSION['status'] ?? '') === 'admin' ||
    ($_SESSION['user_id'] ?? 0) == 1
);

$userStoreId = intval($_SESSION['store_id'] ?? 1);
$currentStoreId = intval($_SESSION['active_store_id'] ?? $userStoreId);
$canAllBranches = $isAdmin || !empty($_SESSION['can_all_branches']);
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

if (!$canAllBranches) {
    $selectedStoreId = $userStoreId;
} else {
    $selectedStoreId = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : $currentStoreId;
}
$activeStoreId = $selectedStoreId;

$sup_message = '';
$sup_message_type = '';

// ---------- POST handling ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $activeStoreId = !empty($_POST['store_id']) ? intval($_POST['store_id']) : $selectedStoreId;
    $act  = $_POST['action'];
    $name = trim($_POST['supplier_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    $status  = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';
    $sid     = intval($_POST['supplier_id'] ?? 0);

    try {
        if ($act === 'add_supplier' || $act === 'edit_supplier') {
            if ($name === '') {
                $sup_message = tl('ກະລຸນາປ້ອນຊື່ຜູ້ສະໜອງ!');
                $sup_message_type = 'error';
            } else {
                // ຊື່ຊ້ຳໃນສາຂາດຽວກັນ
                $dupSql = "SELECT supplier_id FROM st_suppliers WHERE supplier_name = ? AND store_id = ?"
                        . ($act === 'edit_supplier' ? " AND supplier_id != ?" : "") . " LIMIT 1";
                $dupParams = [$name, $activeStoreId];
                if ($act === 'edit_supplier') $dupParams[] = $sid;
                $dupStmt = $pdo->prepare($dupSql);
                $dupStmt->execute($dupParams);
                if ($dupStmt->fetchColumn()) {
                    $sup_message = (tl('ມີຜູ້ສະໜອງຊື່') . ' "') . htmlspecialchars($name) . ('" ' . tl('ຢູ່ແລ້ວ!'));
                    $sup_message_type = 'error';
                } elseif ($act === 'add_supplier') {
                    // branch_no = ລຳດັບໃນສາຂານີ້ → ລະຫັດທີ່ສະແດງ "<ລະຫັດສາຂາ>-<branch_no>"
                    $ins = $pdo->prepare("INSERT INTO st_suppliers (supplier_name, phone, contact_person, address, notes, status, store_id, branch_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$name, $phone, $contact, $address, $notes, $status, $activeStoreId, nextBranchNo($pdo, "st_suppliers", $activeStoreId)]);
                    $sup_message = tl('ເພີ່ມຜູ້ສະໜອງສຳເລັດ!');
                    $sup_message_type = 'success';
                    logActivity($pdo, "ເພີ່ມຜູ້ສະໜອງ", "ຊື່: $name");
                } else {
                    $upd = $pdo->prepare("UPDATE st_suppliers SET supplier_name=?, phone=?, contact_person=?, address=?, notes=?, status=? WHERE supplier_id=? AND store_id=?");
                    $upd->execute([$name, $phone, $contact, $address, $notes, $status, $sid, $activeStoreId]);
                    $sup_message = tl('ແກ້ໄຂຜູ້ສະໜອງສຳເລັດ!');
                    $sup_message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂຜູ້ສະໜອງ", "ID: $sid, ຊື່: $name");
                }
            }
        } elseif ($act === 'delete_supplier' && $sid > 0) {
            // ຖ້າມີສິນຄ້າຜູກກັບຜູ້ສະໜອງນີ້ຢູ່ → ບໍ່ລົບ, ພຽງແຕ່ຕັ້ງເປັນ inactive
            $inUse = 0;
            try {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM st_products WHERE supplier_id = ?");
                $chk->execute([$sid]);
                $inUse = (int)$chk->fetchColumn();
            } catch (Throwable $e) {}
            if ($inUse > 0) {
                $pdo->prepare("UPDATE st_suppliers SET status='inactive' WHERE supplier_id=? AND store_id=?")->execute([$sid, $activeStoreId]);
                $sup_message = (tl('ຜູ້ສະໜອງນີ້ມີສິນຄ້າຜູກຢູ່') . ' ') . $inUse . ((' ' . tl('ລາຍການ') . ' — ' . tl('ປ່ຽນເປັນ') . ' "' . tl('ພັກໃຊ້ງານ') . '" ') . tl('ແທນການລົບ.'));
                $sup_message_type = 'success';
            } else {
                $pdo->prepare("DELETE FROM st_suppliers WHERE supplier_id=? AND store_id=?")->execute([$sid, $activeStoreId]);
                $sup_message = tl('ລົບຜູ້ສະໜອງສຳເລັດ!');
                $sup_message_type = 'success';
                logActivity($pdo, "ລົບຜູ້ສະໜອງ", "ID: $sid");
            }
        }
    } catch (Throwable $e) {
        $sup_message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
        $sup_message_type = 'error';
    }
}

// ---------- Data ----------
// ຄົ້ນຫາເປັນແບບ live (client-side) — ດຶງທັງໝົດມາ ແລ້ວ filter ດ້ວຍ JS
$search = trim($_GET['search'] ?? '');
$listSql = "SELECT s.*, (SELECT COUNT(*) FROM st_products p WHERE p.supplier_id = s.supplier_id) AS product_count
            FROM st_suppliers s WHERE s.store_id = :store_id";
$listParams = [':store_id' => $activeStoreId];
$listSql .= " ORDER BY s.status ASC, s.supplier_name ASC";
$suppliers = [];
ensureBranchNos($pdo, "st_suppliers");
try {
    $stmt = $pdo->prepare($listSql);
    $stmt->execute($listParams);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    attachBranchCodes($pdo, $suppliers, "st_suppliers"); // ລະຫັດ "1-1" ແຍກຕາມສາຂາ
} catch (Throwable $e) {
    // ຕາຕະລາງ suppliers ຍັງບໍ່ຖືກສ້າງ (auto-migration ຈະສ້າງໃຫ້ໃນ request ຖັດໄປ)
    if ($sup_message === '') {
        $sup_message = (tl('ກຳລັງຕັ້ງຄ່າຕາຕະລາງຜູ້ສະໜອງ') . ' — ' . tl('ກະລຸນາໂຫຼດໜ້ານີ້ຄືນອີກຄັ້ງ.'));
        $sup_message_type = 'error';
    }
}

require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="content-wrapper bg-light">
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h5 class="m-0 font-weight-bold text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon'; font-size: 18px;">
            <i class="fas fa-truck text-success mr-2"></i> <?php echo htmlspecialchars(t('suppliers.page_title', 'ຈັດການຜູ້ສະໜອງ')); ?>
          </h5>
        </div>
        <div class="col-sm-6 text-right">
          <?php if (hasPermission('suppliers', 'add')): ?>
          <button type="button" class="btn btn-primary px-3 py-1 font-weight-bold shadow-sm" onclick="openSupplierModal()" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('suppliers.btn_add', 'ເພີ່ມຜູ້ສະໜອງ')); ?>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if ($sup_message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($sup_message_type === 'success'): ?>
        Swal.fire({ icon: 'success', title: '<?php echo htmlspecialchars(t('suppliers.msg_success_title', 'ສຳເລັດ'), ENT_QUOTES); ?>', text: '<?php echo addslashes($sup_message); ?>', showConfirmButton: false, timer: 1600 });
        <?php else: ?>
        Swal.fire({ icon: 'error', title: '<?php echo htmlspecialchars(t('suppliers.msg_error_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>', text: '<?php echo addslashes($sup_message); ?>', confirmButtonColor: '#2563eb', confirmButtonText: '<?php echo htmlspecialchars(t('suppliers.btn_ok', 'ຕົກລົງ'), ENT_QUOTES); ?>' });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <section class="content pb-4">
    <div class="container-fluid">
      <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap: 12px;">
          <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 7px 12px;"><span id="supplierCountNum"><?php echo count($suppliers); ?></span> <?php echo htmlspecialchars(t('suppliers.count_label', 'ລາຍການ')); ?></span>
            <?php if (($canAllBranches) && count($stores) > 1): ?>
            <div class="prod-filter-wrap">
              <select id="storeFilter" class="form-control font-weight-bold" onchange="window.location.href='suppliers.php?store_id=' + this.value">
                <?php foreach ($stores as $st): ?>
                  <option value="<?php echo $st['store_id']; ?>" <?php echo ($selectedStoreId == $st['store_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('products.store_main_suffix', '(ສາງຫຼັກ)')) : htmlspecialchars(t('products.store_branch_suffix', '(ສາຂາຍ່ອຍ)')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
          </div>
          <div class="d-flex align-items-center ml-auto flex-grow-1 justify-content-end" style="gap: 8px; max-width: 560px;">
            <div class="input-group" style="flex: 1 1 auto;">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-search text-muted"></i></span>
              </div>
              <input type="text" id="supplierSearch" class="form-control border-left-0" placeholder="<?php echo htmlspecialchars(t('suppliers.search_ph', 'ຄົ້ນຫາ ຊື່ / ເບີໂທ / ຜູ້ຕິດຕໍ່...')); ?>" value="<?php echo htmlspecialchars($search); ?>" onkeyup="filterSuppliers()" style="height: 44px; font-size: 0.95rem; border-radius: 0 8px 8px 0; border-left: none;">
            </div>
            <button type="button" id="supplierSearchClear" class="btn btn-light border d-flex align-items-center justify-content-center" style="border-radius: 8px; height: 44px; width: 44px;" title="<?php echo htmlspecialchars(t('suppliers.clear', 'ລ້າງຄ່າ')); ?>" onclick="document.getElementById('supplierSearch').value='';filterSuppliers();document.getElementById('supplierSearch').focus();"><i class="fas fa-redo"></i></button>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.9rem;">
              <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr>
                  <th class="text-center" style="width: 56px;"><?php echo htmlspecialchars(t('suppliers.col_no', 'ລຳດັບ')); ?></th>
                  <th class="text-center" style="width: 80px;"><?php echo htmlspecialchars(t('suppliers.col_code', 'ລະຫັດ')); ?></th>
                  <th><?php echo htmlspecialchars(t('suppliers.col_name', 'ຊື່ຜູ້ສະໜອງ')); ?></th>
                  <th><?php echo htmlspecialchars(t('suppliers.col_phone', 'ເບີໂທ')); ?></th>
                  <th><?php echo htmlspecialchars(t('suppliers.col_contact', 'ຜູ້ຕິດຕໍ່')); ?></th>
                  <th><?php echo htmlspecialchars(t('suppliers.col_address', 'ທີ່ຢູ່')); ?></th>
                  <th class="text-center" style="width: 90px;"><?php echo htmlspecialchars(t('suppliers.col_products', 'ສິນຄ້າ')); ?></th>
                  <th class="text-center" style="width: 110px;"><?php echo htmlspecialchars(t('suppliers.col_status', 'ສະຖານະ')); ?></th>
                  <th class="text-center" style="width: 120px;"><?php echo htmlspecialchars(t('suppliers.col_action', 'ຈັດການ')); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr id="supplierNoResult" style="display: none;">
                  <td colspan="9" class="text-center text-muted py-4">
                    <i class="fas fa-search fa-lg mb-2 d-block opacity-50"></i>
                    <?php echo htmlspecialchars(t('suppliers.no_result', 'ບໍ່ພົບຜູ້ສະໜອງທີ່ຄົ້ນຫາ')); ?>
                  </td>
                </tr>
                <?php if (empty($suppliers)): ?>
                  <tr><td colspan="9" class="text-center text-muted py-5">
                    <i class="fas fa-truck fa-2x mb-2 d-block opacity-50"></i>
                    <?php echo htmlspecialchars(t('suppliers.empty', 'ຍັງບໍ່ມີຜູ້ສະໜອງ')); ?>
                  </td></tr>
                <?php else: foreach ($suppliers as $i => $s): ?>
                  <?php $sRow = strtolower(($s['display_code'] ?? '') . ' ' . ($s['supplier_name'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . ($s['contact_person'] ?? '') . ' ' . ($s['address'] ?? '')); ?>
                  <tr class="supplier-row" data-s="<?php echo htmlspecialchars($sRow); ?>">
                    <td class="text-center text-muted row-no"><?php echo $i + 1; ?></td>
                    <td class="text-center font-weight-bold"><?php echo branchCodeHtml($s['display_code'] ?? ''); ?></td>
                    <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($s['supplier_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['phone'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($s['contact_person'] ?: '-'); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($s['address'] ?: '-'); ?></td>
                    <td class="text-center"><?php echo (int)$s['product_count']; ?></td>
                    <td class="text-center">
                      <?php if (($s['status'] ?? 'active') === 'active'): ?>
                        <span class="badge badge-success"><?php echo htmlspecialchars(t('suppliers.status_active', 'ໃຊ້ງານ')); ?></span>
                      <?php else: ?>
                        <span class="badge badge-secondary"><?php echo htmlspecialchars(t('suppliers.status_inactive', 'ພັກໃຊ້ງານ')); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if (hasPermission('suppliers', 'edit')): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0" title="<?php echo htmlspecialchars(t('suppliers.btn_edit', 'ແກ້ໄຂ')); ?>" onclick='openSupplierModal(<?php echo json_encode($s, JSON_UNESCAPED_UNICODE); ?>)'>
                          <i class="fas fa-edit"></i>
                        </button>
                      <?php endif; ?>
                      <?php if (hasPermission('suppliers', 'del')): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" title="<?php echo htmlspecialchars(t('suppliers.btn_delete', 'ລົບ')); ?>" onclick="deleteSupplier(<?php echo (int)$s['supplier_id']; ?>, '<?php echo htmlspecialchars(addslashes($s['supplier_name']), ENT_QUOTES); ?>')">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-3 px-md-4" style="gap: 10px; flex-wrap: wrap; flex-direction: row;">
          <div class="d-flex align-items-center" style="gap: 8px; font-size: 0.85rem;">
            <span class="text-muted font-weight-bold">
              <?php echo htmlspecialchars(t('suppliers.page_showing', 'ສະແດງ')); ?>
              <span id="sup_page_info_start">0</span>-<span id="sup_page_info_end">0</span>
              <?php echo htmlspecialchars(t('suppliers.page_items', 'ລາຍການ')); ?>
            </span>
            <select id="supPageSizeSelect" class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;" onchange="changeSupplierPageSize()">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="all"><?php echo htmlspecialchars(t('suppliers.page_all', 'ທັງໝົດ')); ?></option>
            </select>
          </div>
          <nav aria-label="Supplier Pagination" style="margin-left: auto;">
            <ul class="pagination pagination-circle mb-0 justify-content-end" id="supplierPagination"></ul>
          </nav>
        </div>
      </div>
    </div>
  </section>
</div>

<?php echo themeCssLink('suppliers.css'); /* CSS ຍ້າຍໄປ themes/suppliers.css */ ?>

<!-- Add / Edit Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <form method="POST" action="" id="supplierForm">
        <input type="hidden" name="action" id="supplier_action" value="add_supplier">
        <input type="hidden" name="supplier_id" id="supplier_id">
        <input type="hidden" name="store_id" value="<?php echo $selectedStoreId; ?>">
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886);">
          <h6 class="modal-title font-weight-bold" id="supplierModalTitle" style="font-size: 1.05rem;">
            <i class="fas fa-truck mr-2"></i> <?php echo htmlspecialchars(t('suppliers.modal_add_title', 'ເພີ່ມຜູ້ສະໜອງ')); ?>
          </h6>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('suppliers.f_name', 'ຊື່ຜູ້ສະໜອງ')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="supplier_name" id="f_supplier_name" class="form-control" required placeholder="<?php echo htmlspecialchars(t('suppliers.ph_name', 'ກະລຸນາປ້ອນຊື່ຜູ້ສະໜອງ')); ?>" style="border-radius: 6px; height: 40px;">
          </div>
          <div class="form-row">
            <div class="form-group col-6">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('suppliers.f_phone', 'ເບີໂທ')); ?></label>
              <input type="text" name="phone" id="f_phone" class="form-control" placeholder="<?php echo htmlspecialchars(t('suppliers.ph_phone', '020 ')); ?>" style="border-radius: 6px; height: 40px;">
            </div>
            <div class="form-group col-6">
              <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('suppliers.f_contact', 'ຜູ້ຕິດຕໍ່')); ?></label>
              <input type="text" name="contact_person" id="f_contact_person" class="form-control" placeholder="<?php echo htmlspecialchars(t('suppliers.ph_contact', 'ຊື່ຜູ້ປະສານງານ')); ?>" style="border-radius: 6px; height: 40px;">
            </div>
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('suppliers.f_address', 'ທີ່ຢູ່')); ?></label>
            <input type="text" name="address" id="f_address" class="form-control" placeholder="<?php echo htmlspecialchars(t('suppliers.ph_address', 'ບ້ານ / ເມືອງ / ແຂວງ')); ?>" style="border-radius: 6px; height: 40px;">
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('suppliers.f_notes', 'ໝາຍເຫດ')); ?></label>
            <textarea name="notes" id="f_notes" class="form-control" rows="2" placeholder="<?php echo htmlspecialchars(t('suppliers.ph_notes', 'ໝາຍເຫດ (ຖ້າມີ)')); ?>" style="border-radius: 6px;"></textarea>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('suppliers.f_status', 'ສະຖານະ')); ?></label>
            <select name="status" id="f_status" class="form-control" style="border-radius: 6px; height: 40px;">
              <option value="active"><?php echo htmlspecialchars(t('suppliers.status_active', 'ໃຊ້ງານ')); ?></option>
              <option value="inactive"><?php echo htmlspecialchars(t('suppliers.status_inactive', 'ພັກໃຊ້ງານ')); ?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('suppliers.cancel', 'ຍົກເລີກ')); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('suppliers.btn_save', 'ບັນທຶກ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/suppliers_js.php'; /* JS ຂອງໜ້າ */ ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
