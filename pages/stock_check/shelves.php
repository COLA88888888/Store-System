<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = '../../';

require_once __DIR__ . '/../../config/db.php';

// ຂໍ້ຄວາມໃນໜ້າ ຕາມພາສາທີ່ເລືອກ (lang/*/zones_shelves.php) — PHP ໃຊ້ $zs(), JS ໃຊ້ zsT()
$zs = function ($key) { return htmlspecialchars(t($key)); };

if (empty($_SESSION['user_id']) || (!hasPermission('stock_check') && !hasPermission('stock_count') && !hasPermission('products'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$activeStoreId = getActiveStoreId($pdo);
$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    if ($act === 'add_shelf') {
        $name = trim($_POST['shelf_name'] ?? '');
        $zone_id = intval($_POST['zone_id'] ?? 0) ?: null;
        $desc = trim($_POST['description'] ?? '');
        if ($name === '') {
            $message = t('zs.s_msg_required');
            $message_type = 'danger';
        } else {
            $chk = $pdo->prepare("SELECT 1 FROM st_shelves WHERE shelf_name = ? AND zone_id <=> ? AND store_id = ? LIMIT 1");
            $chk->execute([$name, $zone_id, $activeStoreId]);
            if ($chk->fetchColumn()) {
                $message = sprintf(t('zs.s_msg_exists_zone'), $name);
                $message_type = 'warning';
            } else {
                $stmt = $pdo->prepare("INSERT INTO st_shelves (shelf_name, zone_id, description, store_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $zone_id, $desc ?: null, $activeStoreId]);
                $message = t('zs.s_msg_added');
                $message_type = 'success';
                logActivity($pdo, 'ເພີ່ມຊັ້ນວາງ', "ຊື່: $name");
            }
        }
    } elseif ($act === 'edit_shelf') {
        $id = intval($_POST['shelf_id'] ?? 0);
        $name = trim($_POST['shelf_name'] ?? '');
        $zone_id = intval($_POST['zone_id'] ?? 0) ?: null;
        $desc = trim($_POST['description'] ?? '');
        if ($id <= 0 || $name === '') {
            $message = t('zs.invalid');
            $message_type = 'danger';
        } else {
            $oldNameStmt = $pdo->prepare("SELECT shelf_name FROM st_shelves WHERE shelf_id = ? AND store_id = ?");
            $oldNameStmt->execute([$id, $activeStoreId]);
            $oldName = $oldNameStmt->fetchColumn();

            $chk = $pdo->prepare("SELECT 1 FROM st_shelves WHERE shelf_name = ? AND zone_id <=> ? AND shelf_id != ? AND store_id = ? LIMIT 1");
            $chk->execute([$name, $zone_id, $id, $activeStoreId]);
            if ($chk->fetchColumn()) {
                $message = sprintf(t('zs.s_msg_exists'), $name);
                $message_type = 'warning';
            } else {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE st_shelves SET shelf_name = ?, zone_id = ?, description = ? WHERE shelf_id = ? AND store_id = ?");
                $stmt->execute([$name, $zone_id, $desc ?: null, $id, $activeStoreId]);
                if ($oldName && $oldName !== $name) {
                    $pdo->prepare("UPDATE st_products SET shelf = ? WHERE shelf = ? AND store_id = ?")->execute([$name, $oldName, $activeStoreId]);
                    $pdo->prepare("UPDATE st_stock_check_items SET shelf = ? WHERE shelf = ?")->execute([$name, $oldName]);
                }
                $pdo->commit();
                $message = t('zs.s_msg_updated');
                $message_type = 'success';
                logActivity($pdo, 'ແກ້ໄຂຊັ້ນວາງ', "ID: $id, ຊື່: $name");
            }
        }
    } elseif ($act === 'delete_shelf') {
        $id = intval($_POST['shelf_id'] ?? 0);
        $shStmt = $pdo->prepare("SELECT shelf_name FROM st_shelves WHERE shelf_id = ? AND store_id = ?");
        $shStmt->execute([$id, $activeStoreId]);
        $shName = $shStmt->fetchColumn();
        if ($id > 0 && $shName) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE st_products SET shelf = NULL WHERE shelf = ? AND store_id = ?")->execute([$shName, $activeStoreId]);
            $pdo->prepare("DELETE FROM st_shelves WHERE shelf_id = ? AND store_id = ?")->execute([$id, $activeStoreId]);
            $pdo->commit();
            $message = t('zs.s_msg_deleted');
            $message_type = 'success';
            logActivity($pdo, 'ລົບຊັ້ນວາງ', "ID: $id, ຊື່: $shName");
        }
    }
}

// Fetch all zones for dropdowns & filter
$zStmt = $pdo->prepare("SELECT zone_id, zone_name FROM st_zones WHERE store_id = ? ORDER BY zone_name ASC");
$zStmt->execute([$activeStoreId]);
$zones = $zStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected zone filter
$filterZoneId = isset($_GET['zone_id']) ? intval($_GET['zone_id']) : 0;

// Fetch shelves
$sql = "
    SELECT s.*, z.zone_name,
           (SELECT COUNT(*) FROM st_products WHERE shelf = s.shelf_name COLLATE utf8mb4_general_ci AND store_id = s.store_id) AS product_count
    FROM st_shelves s
    LEFT JOIN st_zones z ON z.zone_id = s.zone_id
    WHERE s.store_id = ?
";
$params = [$activeStoreId];
if ($filterZoneId > 0) {
    $sql .= " AND s.zone_id = ?";
    $params[] = $filterZoneId;
}
$sql .= " ORDER BY IFNULL(z.zone_name, '') ASC, s.shelf_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../layouts/header.php';
?>
<link rel="stylesheet" href="../../themes/categories.css?v=<?php echo filemtime(__DIR__ . '/../../themes/categories.css'); ?>">
<link rel="stylesheet" href="../../themes/stock_check.css?v=<?php echo filemtime(__DIR__ . '/../../themes/stock_check.css'); ?>">

<div class="content-wrapper bg-light">
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
        <div>
          <h5 class="m-0 font-weight-bold text-dark">
            <i class="fas fa-layer-group text-info mr-2"></i> <?php echo $zs('zs.s_title'); ?>
          </h5>
          <small class="text-muted"><?php echo $zs('zs.s_subtitle'); ?></small>
        </div>
        <button type="button" class="btn btn-primary ml-auto font-weight-bold shadow-sm" data-toggle="modal" data-target="#addShelfModal" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> <?php echo $zs('zs.s_btn_add'); ?>
        </button>
      </div>
    </div>
  </section>

  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: '<?php echo $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'error'); ?>',
          title: <?php echo json_encode($message_type === 'success' ? t('zs.success') : t('zs.notice'), JSON_UNESCAPED_UNICODE); ?>,
          text: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
          timer: <?php echo $message_type === 'success' ? '1500' : '2500'; ?>,
          showConfirmButton: <?php echo $message_type === 'success' ? 'false' : 'true'; ?>
        });
      });
    </script>
  <?php endif; ?>

  <section class="content pb-5">
    <div class="container-fluid">
      <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; background: #ffffff;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap mp-search-head" style="gap: 10px;">
          <h6 class="card-title m-0 font-weight-bold text-dark mr-2">
            <i class="fas fa-list mr-1 text-info"></i> <?php echo $zs('zs.s_list_title'); ?> (<?php echo count($shelves); ?>)
          </h6>
          <div class="d-flex align-items-center flex-wrap ml-auto mp-search-box" style="gap: 10px;">
            <!-- Dropdown Filter by Zone (ຢູ່ຕິດກັບບັອກຄົ້ນຫາ) -->
            <div style="width: 260px; max-width: 100%;">
              <select class="form-control font-weight-bold" onchange="window.location.href='shelves.php' + (this.value ? '?zone_id=' + this.value : '')" style="border-radius: 8px; height: 42px; font-size: 0.95rem;">
                <option value=""><?php echo $zs('zs.s_all_zones'); ?></option>
                <?php foreach ($zones as $zn): ?>
                  <option value="<?php echo (int)$zn['zone_id']; ?>" <?php echo $filterZoneId === (int)$zn['zone_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($zn['zone_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="width: 360px; max-width: 100%;">
              <input type="text" id="shelfSearchInput" class="form-control" placeholder="<?php echo $zs('zs.s_search_ph'); ?>" onkeyup="filterShelfTable()" style="border-radius: 8px; height: 42px; font-size: 0.95rem;">
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="shelfTable">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem;">
                <tr>
                  <th class="py-3 text-center" style="width: 60px;">#</th>
                  <th class="py-3"><?php echo $zs('zs.s_col_name'); ?></th>
                  <th class="py-3" style="width: 180px;"><?php echo $zs('zs.s_col_zone'); ?></th>
                  <th class="py-3 text-center" style="width: 140px;"><?php echo $zs('zs.col_products'); ?></th>
                  <th class="py-3"><?php echo $zs('zs.col_desc'); ?></th>
                  <th class="py-3 text-center" style="width: 130px;"><?php echo $zs('zs.col_created'); ?></th>
                  <th class="py-3 text-center" style="width: 110px;"><?php echo $zs('zs.col_manage'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($shelves)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="fas fa-layer-group fa-3x text-secondary mb-2 d-block"></i>
                      <?php echo $zs($filterZoneId ? 'zs.s_empty_in_zone' : 'zs.s_empty'); ?>
                    </td>
                  </tr>
                <?php else: foreach ($shelves as $idx => $sh): ?>
                  <tr class="shelf-row">
                    <td class="text-center text-muted row-no"><?php echo $idx + 1; ?></td>
                    <td class="font-weight-bold text-dark shelf-name-cell">
                      <i class="fas fa-layer-group text-info mr-1"></i> <?php echo htmlspecialchars($sh['shelf_name']); ?>
                    </td>
                    <td>
                      <?php if (!empty($sh['zone_name'])): ?>
                        <span class="badge badge-light border text-primary font-weight-bold px-2 py-1">
                          <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($sh['zone_name']); ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted small"><?php echo $zs('zs.s_no_zone'); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-light border text-secondary px-2 py-1 font-weight-bold">
                        <i class="fas fa-boxes mr-1"></i> <?php echo number_format((int)$sh['product_count']); ?> <?php echo $zs('zs.items_unit'); ?>
                      </span>
                    </td>
                    <td class="text-muted small"><?php echo htmlspecialchars($sh['description'] ?: '-'); ?></td>
                    <td class="text-center text-muted small"><?php echo date('d/m/Y', strtotime($sh['created_at'])); ?></td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick='openEditShelfModal(<?php echo json_encode($sh, JSON_UNESCAPED_UNICODE); ?>)' title="<?php echo $zs('zs.edit'); ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteShelf(<?php echo (int)$sh['shelf_id']; ?>, '<?php echo htmlspecialchars(addslashes($sh['shelf_name'])); ?>', <?php echo (int)$sh['product_count']; ?>)" title="<?php echo $zs('zs.delete'); ?>">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Footer (ແບບດຽວກັບໜ້າປະເພດສິນຄ້າ — assets/js/table-pager.js) -->
        <div id="shelfPagFooter" class="card-footer table-pager-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-3 px-md-4" style="border-radius: 0 0 12px 12px; gap: 10px; flex-wrap: wrap;">
          <div class="d-flex align-items-center text-muted" style="gap: 8px; font-size: 0.85rem;">
            <span class="text-muted font-weight-bold"><?php echo $zs('zs.showing'); ?> <span data-pager-start>0</span>-<span data-pager-end>0</span> <?php echo $zs('zs.items_unit'); ?></span>
            <select data-pager-size class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="all"><?php echo $zs('zs.all'); ?></option>
            </select>
          </div>
          <nav aria-label="Shelf Pagination" class="ml-auto">
            <ul class="pagination pagination-circle mb-0 justify-content-end" data-pager-ul></ul>
          </nav>
        </div>
        <?php echo themeCssLink('table-pager.css'); ?>
      </div>
    </div>
  </section>
</div>

<!-- Modal: ເພີ່ມຊັ້ນວາງ -->
<div class="modal fade" id="addShelfModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="add_shelf">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h6 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> <?php echo $zs('zs.s_modal_add'); ?></h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark"><?php echo $zs('zs.s_field_zone'); ?> </label>
            <select name="zone_id" class="form-control" style="height: 40px; border-radius: 6px;">
              <option value=""><?php echo $zs('zs.zone_none_opt'); ?></option>
              <?php foreach ($zones as $zn): ?>
                <option value="<?php echo (int)$zn['zone_id']; ?>" <?php echo $filterZoneId === (int)$zn['zone_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($zn['zone_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark"><?php echo $zs('zs.s_field_name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="shelf_name" class="form-control" placeholder="<?php echo $zs('zs.s_name_ph'); ?>" required style="height: 40px; border-radius: 6px;">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark"><?php echo $zs('zs.field_desc'); ?> <small class="text-muted font-weight-normal"><?php echo $zs('zs.optional'); ?></small></label>
            <textarea name="description" class="form-control" rows="3" placeholder="<?php echo $zs('zs.optional_ph'); ?>" style="border-radius: 6px;"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-3">
          <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal"><?php echo $zs('zs.cancel'); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo $zs('zs.save'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: ແກ້ໄຂຊັ້ນວາງ -->
<div class="modal fade" id="editShelfModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_shelf">
        <input type="hidden" name="shelf_id" id="edit_shelf_id">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h6 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> <?php echo $zs('zs.s_modal_edit'); ?></h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark"><?php echo $zs('zs.s_field_zone'); ?> </label>
            <select name="zone_id" id="edit_shelf_zone_id" class="form-control" style="height: 40px; border-radius: 6px;">
              <option value=""><?php echo $zs('zs.zone_none_opt'); ?></option>
              <?php foreach ($zones as $zn): ?>
                <option value="<?php echo (int)$zn['zone_id']; ?>">
                  <?php echo htmlspecialchars($zn['zone_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark"><?php echo $zs('zs.s_field_name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="shelf_name" id="edit_shelf_name" class="form-control" required style="height: 40px; border-radius: 6px;">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark"><?php echo $zs('zs.field_desc'); ?> <small class="text-muted font-weight-normal"><?php echo $zs('zs.optional'); ?></small></label>
            <textarea name="description" id="edit_shelf_desc" class="form-control" rows="3" placeholder="<?php echo $zs('zs.optional_ph'); ?>" style="border-radius: 6px;"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-3">
          <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal"><?php echo $zs('zs.cancel'); ?></button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo $zs('zs.update'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteShelfForm" action="" method="POST" style="display: none;">
  <input type="hidden" name="action" value="delete_shelf">
  <input type="hidden" name="shelf_id" id="delete_shelf_id">
</form>

<script src="<?php echo $base_path; ?>assets/js/table-pager.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/table-pager.js'); ?>"></script>
<script>
// ຂໍ້ຄວາມ popup (lang/*/zones_shelves.php) — window.I18N_* ຈຶ່ງຖືກປ່ຽນຄ່າເອງເມື່ອກົດທຸງພາສາ
var I18N_ZS = <?php echo tjson(require __DIR__ . '/../../lang/lo/zones_shelves.php'); ?>;
function zsT(key) {
  var v = I18N_ZS[key] || key;
  var args = Array.prototype.slice.call(arguments, 1);
  return args.length ? v.replace(/%s/g, function () { return args.length ? args.shift() : ''; }) : v;
}

// ຄົ້ນຫາ + ແບ່ງໜ້າ: ຄຳຄົ້ນຫາເລືອກແຖວທີ່ກົງ ແລ້ວ pager ສະແດງເທື່ອລະໜ້າ
var shelfPager = TablePager.create({
  rows: '#shelfTable tbody tr.shelf-row',
  footer: '#shelfPagFooter',
  filter: function(tr) {
    var q = ($('#shelfSearchInput').val() || '').toLowerCase().trim();
    return !q || $(tr).find('.shelf-name-cell').text().toLowerCase().indexOf(q) !== -1;
  }
});

function filterShelfTable() {
  shelfPager.refresh();
}

function openEditShelfModal(sh) {
  $('#edit_shelf_id').val(sh.shelf_id);
  $('#edit_shelf_name').val(sh.shelf_name);
  $('#edit_shelf_zone_id').val(sh.zone_id || '');
  $('#edit_shelf_desc').val(sh.description || '');
  $('#editShelfModal').modal('show');
}

function confirmDeleteShelf(id, name, prodCount) {
  var warn = prodCount > 0 ? ('<br><span class="text-danger small">' + zsT('zs.s_delete_warn', prodCount) + '</span>') : '';
  Swal.fire({
    title: zsT('zs.confirm_delete'),
    html: zsT('zs.s_delete_text', '<b>"' + $('<div>').text(name).html() + '"</b>') + warn,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: zsT('zs.yes_delete'),
    cancelButtonText: zsT('zs.cancel')
  }).then(function(r) {
    if (r.isConfirmed) {
      $('#delete_shelf_id').val(id);
      $('#deleteShelfForm').submit();
    }
  });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
