<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = '../../';

require_once __DIR__ . '/../../config/db.php';

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
            $message = 'ກະລຸນາປ້ອນຊື່ຊັ້ນວາງ!';
            $message_type = 'danger';
        } else {
            $chk = $pdo->prepare("SELECT 1 FROM st_shelves WHERE shelf_name = ? AND zone_id <=> ? AND store_id = ? LIMIT 1");
            $chk->execute([$name, $zone_id, $activeStoreId]);
            if ($chk->fetchColumn()) {
                $message = 'ຊື່ຊັ້ນວາງ "' . htmlspecialchars($name) . '" ນີ້ມີແລ້ວໃນໂຊນນີ້!';
                $message_type = 'warning';
            } else {
                $stmt = $pdo->prepare("INSERT INTO st_shelves (shelf_name, zone_id, description, store_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $zone_id, $desc ?: null, $activeStoreId]);
                $message = 'ເພີ່ມຊັ້ນວາງສຳເລັດ!';
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
            $message = 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ!';
            $message_type = 'danger';
        } else {
            $oldNameStmt = $pdo->prepare("SELECT shelf_name FROM st_shelves WHERE shelf_id = ? AND store_id = ?");
            $oldNameStmt->execute([$id, $activeStoreId]);
            $oldName = $oldNameStmt->fetchColumn();

            $chk = $pdo->prepare("SELECT 1 FROM st_shelves WHERE shelf_name = ? AND zone_id <=> ? AND shelf_id != ? AND store_id = ? LIMIT 1");
            $chk->execute([$name, $zone_id, $id, $activeStoreId]);
            if ($chk->fetchColumn()) {
                $message = 'ຊື່ຊັ້ນວາງ "' . htmlspecialchars($name) . '" ນີ້ມີແລ້ວ!';
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
                $message = 'ແກ້ໄຂຊັ້ນວາງສຳເລັດ!';
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
            $message = 'ລົບຊັ້ນວາງສຳເລັດ!';
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
            <i class="fas fa-layer-group text-info mr-2"></i> ຈັດການຊັ້ນວາງສິນຄ້າ
          </h5>
          <small class="text-muted">ກຳນົດຊັ້ນວາງ / ລັອກສິນຄ້າຕາມແຕ່ລະໂຊນ</small>
        </div>
        <button type="button" class="btn btn-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#addShelfModal" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> ເພີ່ມຊັ້ນວາງໃໝ່
        </button>
      </div>
    </div>
  </section>

  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: '<?php echo $message_type === 'success' ? 'success' : ($message_type === 'warning' ? 'warning' : 'error'); ?>',
          title: '<?php echo $message_type === 'success' ? 'ສຳເລັດ' : 'ແຈ້ງເຕືອນ'; ?>',
          text: '<?php echo $message; ?>',
          timer: <?php echo $message_type === 'success' ? '1500' : '2500'; ?>,
          showConfirmButton: <?php echo $message_type === 'success' ? 'false' : 'true'; ?>
        });
      });
    </script>
  <?php endif; ?>

  <section class="content pb-5">
    <div class="container-fluid">
      <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; background: #ffffff;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
          <h6 class="card-title m-0 font-weight-bold text-dark mr-2">
            <i class="fas fa-list mr-1 text-info"></i> ລາຍການຊັ້ນວາງທັງໝົດ (<?php echo count($shelves); ?>)
          </h6>
          <div class="d-flex align-items-center flex-wrap ml-auto" style="gap: 10px;">
            <!-- Dropdown Filter by Zone (ຢູ່ຕິດກັບບັອກຄົ້ນຫາ) -->
            <div style="width: 260px; max-width: 100%;">
              <select class="form-control font-weight-bold" onchange="window.location.href='shelves.php' + (this.value ? '?zone_id=' + this.value : '')" style="border-radius: 8px; height: 42px; font-size: 0.95rem;">
                <option value="">-- ທຸກໂຊນ --</option>
                <?php foreach ($zones as $zn): ?>
                  <option value="<?php echo (int)$zn['zone_id']; ?>" <?php echo $filterZoneId === (int)$zn['zone_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($zn['zone_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="width: 360px; max-width: 100%;">
              <input type="text" id="shelfSearchInput" class="form-control" placeholder="ຄົ້ນຫາຊື່ຊັ້ນວາງ..." onkeyup="filterShelfTable()" style="border-radius: 8px; height: 42px; font-size: 0.95rem;">
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="shelfTable">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem;">
                <tr>
                  <th class="py-3 text-center" style="width: 60px;">#</th>
                  <th class="py-3">ຊື່ຊັ້ນວາງ</th>
                  <th class="py-3" style="width: 180px;">ໂຊນທີ່ສັງກັດ</th>
                  <th class="py-3 text-center" style="width: 140px;">ຈຳນວນສິນຄ້າ</th>
                  <th class="py-3">ຄຳອະທິບາຍ</th>
                  <th class="py-3 text-center" style="width: 130px;">ວັນທີສ້າງ</th>
                  <th class="py-3 text-center" style="width: 110px;">ຈັດການ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($shelves)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="fas fa-layer-group fa-3x text-secondary mb-2 d-block"></i>
                      ຍັງບໍ່ມີຂໍ້ມູນຊັ້ນວາງ<?php echo $filterZoneId ? ' ໃນໂຊນນີ້' : ''; ?>
                    </td>
                  </tr>
                <?php else: foreach ($shelves as $idx => $sh): ?>
                  <tr class="shelf-row">
                    <td class="text-center text-muted"><?php echo $idx + 1; ?></td>
                    <td class="font-weight-bold text-dark shelf-name-cell">
                      <i class="fas fa-layer-group text-info mr-1"></i> <?php echo htmlspecialchars($sh['shelf_name']); ?>
                    </td>
                    <td>
                      <?php if (!empty($sh['zone_name'])): ?>
                        <span class="badge badge-light border text-primary font-weight-bold px-2 py-1">
                          <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($sh['zone_name']); ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted small">- ບໍ່ລະບຸໂຊນ -</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-light border text-secondary px-2 py-1 font-weight-bold">
                        <i class="fas fa-boxes mr-1"></i> <?php echo number_format((int)$sh['product_count']); ?> ລາຍການ
                      </span>
                    </td>
                    <td class="text-muted small"><?php echo htmlspecialchars($sh['description'] ?: '-'); ?></td>
                    <td class="text-center text-muted small"><?php echo date('d/m/Y', strtotime($sh['created_at'])); ?></td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick='openEditShelfModal(<?php echo json_encode($sh, JSON_UNESCAPED_UNICODE); ?>)' title="ແກ້ໄຂ">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteShelf(<?php echo (int)$sh['shelf_id']; ?>, '<?php echo htmlspecialchars(addslashes($sh['shelf_name'])); ?>', <?php echo (int)$sh['product_count']; ?>)" title="ລົບ">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
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
          <h6 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> ເພີ່ມຊັ້ນວາງສິນຄ້າໃໝ່</h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">ໂຊນສັງກັດ: </label>
            <select name="zone_id" class="form-control" style="height: 40px; border-radius: 6px;">
              <option value="">-- ບໍ່ລະບຸ / ເລືອກໂຊນ --</option>
              <?php foreach ($zones as $zn): ?>
                <option value="<?php echo (int)$zn['zone_id']; ?>" <?php echo $filterZoneId === (int)$zn['zone_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($zn['zone_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">ຊື່ຊັ້ນວາງ / ລັອກ: <span class="text-danger">*</span></label>
            <input type="text" name="shelf_name" class="form-control" placeholder="ເຊັ່ນ: ຊັ້ນ 1, ລັອກ A-01..." required style="height: 40px; border-radius: 6px;">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark">ຄຳອະທິບາຍ: <small class="text-muted font-weight-normal">(ບໍ່ບັງຄັບ)</small></label>
            <textarea name="description" class="form-control" rows="3" placeholder="ບໍ່ບັງຄັບ..." style="border-radius: 6px;"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-3">
          <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal">ຍົກເລີກ</button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> ບັນທຶກ
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
          <h6 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> ແກ້ໄຂຊັ້ນວາງສິນຄ້າ</h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">ໂຊນສັງກັດ: </label>
            <select name="zone_id" id="edit_shelf_zone_id" class="form-control" style="height: 40px; border-radius: 6px;">
              <option value="">-- ບໍ່ລະບຸ / ເລືອກໂຊນ --</option>
              <?php foreach ($zones as $zn): ?>
                <option value="<?php echo (int)$zn['zone_id']; ?>">
                  <?php echo htmlspecialchars($zn['zone_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">ຊື່ຊັ້ນວາງ / ລັອກ: <span class="text-danger">*</span></label>
            <input type="text" name="shelf_name" id="edit_shelf_name" class="form-control" required style="height: 40px; border-radius: 6px;">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark">ຄຳອະທິບາຍ: <small class="text-muted font-weight-normal">(ບໍ່ບັງຄັບ)</small></label>
            <textarea name="description" id="edit_shelf_desc" class="form-control" rows="3" placeholder="ບໍ່ບັງຄັບ..." style="border-radius: 6px;"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 bg-light py-3">
          <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal">ຍົກເລີກ</button>
          <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> ອັບເດດ
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

<script>
function filterShelfTable() {
  var q = ($('#shelfSearchInput').val() || '').toLowerCase().trim();
  $('#shelfTable tbody tr.shelf-row').each(function() {
    var txt = $(this).find('.shelf-name-cell').text().toLowerCase();
    $(this).toggle(!q || txt.indexOf(q) !== -1);
  });
}

function openEditShelfModal(sh) {
  $('#edit_shelf_id').val(sh.shelf_id);
  $('#edit_shelf_name').val(sh.shelf_name);
  $('#edit_shelf_zone_id').val(sh.zone_id || '');
  $('#edit_shelf_desc').val(sh.description || '');
  $('#editShelfModal').modal('show');
}

function confirmDeleteShelf(id, name, prodCount) {
  var warn = prodCount > 0 ? ('<br><span class="text-danger small">ຊັ້ນວາງນີ້ມີສິນຄ້າຢູ່ ' + prodCount + ' ລາຍການ (ທີ່ຕັ້ງຊັ້ນວາງຂອງສິນຄ້າຈະຖືກປ່ຽນເປັນວ່າງ)</span>') : '';
  Swal.fire({
    title: 'ຢືນຢັນການລົບ?',
    html: 'ທ່ານຕ້ອງການລົບຊັ້ນວາງ <b>"' + name + '"</b> ແທ້ບໍ່?' + warn,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ແມ່ນ, ລົບເລີຍ',
    cancelButtonText: 'ຍົກເລີກ'
  }).then(function(r) {
    if (r.isConfirmed) {
      $('#delete_shelf_id').val(id);
      $('#deleteShelfForm').submit();
    }
  });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
