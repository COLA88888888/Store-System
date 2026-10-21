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

    if ($act === 'add_zone') {
        $name = trim($_POST['zone_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name === '') {
            $message = 'ກະລຸນາປ້ອນຊື່ໂຊນ!';
            $message_type = 'danger';
        } else {
            $chk = $pdo->prepare("SELECT 1 FROM st_zones WHERE zone_name = ? AND store_id = ? LIMIT 1");
            $chk->execute([$name, $activeStoreId]);
            if ($chk->fetchColumn()) {
                $message = 'ຊື່ໂຊນ "' . htmlspecialchars($name) . '" ນີ້ມີແລ້ວໃນສາຂານີ້!';
                $message_type = 'warning';
            } else {
                $stmt = $pdo->prepare("INSERT INTO st_zones (zone_name, description, store_id) VALUES (?, ?, ?)");
                $stmt->execute([$name, $desc ?: null, $activeStoreId]);
                $message = 'ເພີ່ມໂຊນສຳເລັດ!';
                $message_type = 'success';
                logActivity($pdo, 'ເພີ່ມໂຊນສິນຄ້າ', "ຊື່: $name");
            }
        }
    } elseif ($act === 'edit_zone') {
        $id = intval($_POST['zone_id'] ?? 0);
        $name = trim($_POST['zone_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($id <= 0 || $name === '') {
            $message = 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ!';
            $message_type = 'danger';
        } else {
            $oldNameStmt = $pdo->prepare("SELECT zone_name FROM st_zones WHERE zone_id = ? AND store_id = ?");
            $oldNameStmt->execute([$id, $activeStoreId]);
            $oldName = $oldNameStmt->fetchColumn();

            $chk = $pdo->prepare("SELECT 1 FROM st_zones WHERE zone_name = ? AND zone_id != ? AND store_id = ? LIMIT 1");
            $chk->execute([$name, $id, $activeStoreId]);
            if ($chk->fetchColumn()) {
                $message = 'ຊື່ໂຊນ "' . htmlspecialchars($name) . '" ນີ້ມີແລ້ວ!';
                $message_type = 'warning';
            } else {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE st_zones SET zone_name = ?, description = ? WHERE zone_id = ? AND store_id = ?");
                $stmt->execute([$name, $desc ?: null, $id, $activeStoreId]);
                if ($oldName && $oldName !== $name) {
                    $pdo->prepare("UPDATE st_products SET zone = ? WHERE zone = ? AND store_id = ?")->execute([$name, $oldName, $activeStoreId]);
                    $pdo->prepare("UPDATE st_stock_check_items SET zone = ? WHERE zone = ?")->execute([$name, $oldName]);
                }
                $pdo->commit();
                $message = 'ແກ້ໄຂໂຊນສຳເລັດ!';
                $message_type = 'success';
                logActivity($pdo, 'ແກ້ໄຂໂຊນສິນຄ້າ', "ID: $id, ຊື່: $name");
            }
        }
    } elseif ($act === 'delete_zone') {
        $id = intval($_POST['zone_id'] ?? 0);
        $znStmt = $pdo->prepare("SELECT zone_name FROM st_zones WHERE zone_id = ? AND store_id = ?");
        $znStmt->execute([$id, $activeStoreId]);
        $znName = $znStmt->fetchColumn();
        if ($id > 0 && $znName) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE st_shelves SET zone_id = NULL WHERE zone_id = ? AND store_id = ?")->execute([$id, $activeStoreId]);
            $pdo->prepare("UPDATE st_products SET zone = NULL WHERE zone = ? AND store_id = ?")->execute([$znName, $activeStoreId]);
            $pdo->prepare("DELETE FROM st_zones WHERE zone_id = ? AND store_id = ?")->execute([$id, $activeStoreId]);
            $pdo->commit();
            $message = 'ລົບໂຊນສຳເລັດ!';
            $message_type = 'success';
            logActivity($pdo, 'ລົບໂຊນສິນຄ້າ', "ID: $id, ຊື່: $znName");
        }
    }
}

// Fetch Zones
$zonesQuery = "
    SELECT z.*,
           (SELECT COUNT(*) FROM st_shelves WHERE zone_id = z.zone_id) AS shelf_count,
           (SELECT COUNT(*) FROM st_products WHERE zone = z.zone_name COLLATE utf8mb4_general_ci AND store_id = z.store_id) AS product_count
    FROM st_zones z
    WHERE z.store_id = ?
    ORDER BY z.zone_name ASC
";
$stmt = $pdo->prepare($zonesQuery);
$stmt->execute([$activeStoreId]);
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <i class="fas fa-map-marker-alt text-primary mr-2"></i> ຈັດການໂຊນສິນຄ້າ
          </h5>
          <small class="text-muted">ກຳນົດພື້ນທີ່ຈັດເກັບ / ໂຊນພາຍໃນຮ້ານ ແລະ ສາງສິນຄ້າ</small>
        </div>
        <button type="button" class="btn btn-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#addZoneModal" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> ເພີ່ມໂຊນໃໝ່
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
          <h6 class="card-title m-0 font-weight-bold text-dark">
            <i class="fas fa-list mr-1 text-primary"></i> ລາຍການໂຊນທັງໝົດ (<?php echo count($zones); ?>)
          </h6>
          <div class="ml-auto" style="width: 360px; max-width: 100%;">
            <input type="text" id="zoneSearchInput" class="form-control" placeholder="ຄົ້ນຫາຊື່ໂຊນ..." onkeyup="filterZoneTable()" style="border-radius: 8px; height: 42px; font-size: 0.95rem;">
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="zoneTable">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem;">
                <tr>
                  <th class="py-3 text-center" style="width: 60px;">#</th>
                  <th class="py-3">ຊື່ໂຊນ</th>
                  <th class="py-3 text-center" style="width: 140px;">ຈຳນວນຊັ້ນວາງ</th>
                  <th class="py-3 text-center" style="width: 140px;">ຈຳນວນສິນຄ້າ</th>
                  <th class="py-3">ຄຳອະທິບາຍ</th>
                  <th class="py-3 text-center" style="width: 130px;">ວັນທີສ້າງ</th>
                  <th class="py-3 text-center" style="width: 110px;">ຈັດການ</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($zones)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="fas fa-map-marker-alt fa-3x text-secondary mb-2 d-block"></i>
                      ຍັງບໍ່ມີຂໍ້ມູນໂຊນໃນສາຂານີ້
                    </td>
                  </tr>
                <?php else: foreach ($zones as $idx => $z): ?>
                  <tr class="zone-row">
                    <td class="text-center text-muted"><?php echo $idx + 1; ?></td>
                    <td class="font-weight-bold text-dark zone-name-cell">
                      <i class="fas fa-map-marker-alt text-primary mr-1"></i> <?php echo htmlspecialchars($z['zone_name']); ?>
                    </td>
                    <td class="text-center">
                      <a href="shelves.php?zone_id=<?php echo (int)$z['zone_id']; ?>" class="badge badge-light border text-primary px-2 py-1 font-weight-bold" title="ຄລິກເພື່ອເບິ່ງຊັ້ນວາງໃນໂຊນນີ້">
                        <i class="fas fa-layer-group mr-1"></i> <?php echo number_format((int)$z['shelf_count']); ?> ຊັ້ນ
                      </a>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-light border text-secondary px-2 py-1 font-weight-bold">
                        <i class="fas fa-boxes mr-1"></i> <?php echo number_format((int)$z['product_count']); ?> ລາຍການ
                      </span>
                    </td>
                    <td class="text-muted small"><?php echo htmlspecialchars($z['description'] ?: '-'); ?></td>
                    <td class="text-center text-muted small"><?php echo date('d/m/Y', strtotime($z['created_at'])); ?></td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick='openEditZoneModal(<?php echo json_encode($z, JSON_UNESCAPED_UNICODE); ?>)' title="ແກ້ໄຂ">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteZone(<?php echo (int)$z['zone_id']; ?>, '<?php echo htmlspecialchars(addslashes($z['zone_name'])); ?>', <?php echo (int)$z['product_count']; ?>)" title="ລົບ">
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

<!-- Modal: ເພີ່ມໂຊນ -->
<div class="modal fade" id="addZoneModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="add_zone">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h6 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> ເພີ່ມໂຊນສິນຄ້າໃໝ່</h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">ຊື່ໂຊນ: <span class="text-danger">*</span></label>
            <input type="text" name="zone_name" class="form-control" placeholder="ເຊັ່ນ: ໂຊນ A, ໜ້າຮ້ານ, ສາງ 1..." required style="height: 40px; border-radius: 6px;">
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

<!-- Modal: ແກ້ໄຂໂຊນ -->
<div class="modal fade" id="editZoneModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_zone">
        <input type="hidden" name="zone_id" id="edit_zone_id">
        <div class="modal-header bg-primary text-white border-0 py-3">
          <h6 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> ແກ້ໄຂໂຊນສິນຄ້າ</h6>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">ຊື່ໂຊນ: <span class="text-danger">*</span></label>
            <input type="text" name="zone_name" id="edit_zone_name" class="form-control" required style="height: 40px; border-radius: 6px;">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark">ຄຳອະທິບາຍ: <small class="text-muted font-weight-normal">(ບໍ່ບັງຄັບ)</small></label>
            <textarea name="description" id="edit_zone_desc" class="form-control" rows="3" placeholder="ບໍ່ບັງຄັບ..." style="border-radius: 6px;"></textarea>
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
<form id="deleteZoneForm" action="" method="POST" style="display: none;">
  <input type="hidden" name="action" value="delete_zone">
  <input type="hidden" name="zone_id" id="delete_zone_id">
</form>

<script>
function filterZoneTable() {
  var q = ($('#zoneSearchInput').val() || '').toLowerCase().trim();
  $('#zoneTable tbody tr.zone-row').each(function() {
    var txt = $(this).find('.zone-name-cell').text().toLowerCase();
    $(this).toggle(!q || txt.indexOf(q) !== -1);
  });
}

function openEditZoneModal(z) {
  $('#edit_zone_id').val(z.zone_id);
  $('#edit_zone_name').val(z.zone_name);
  $('#edit_zone_desc').val(z.description || '');
  $('#editZoneModal').modal('show');
}

function confirmDeleteZone(id, name, prodCount) {
  var warn = prodCount > 0 ? ('<br><span class="text-danger small">ໂຊນນີ້ມີສິນຄ້າຢູ່ ' + prodCount + ' ລາຍການ (ທີ່ຕັ້ງໂຊນຂອງສິນຄ້າຈະຖືກປ່ຽນເປັນວ່າງ)</span>') : '';
  Swal.fire({
    title: 'ຢືນຢັນການລົບ?',
    html: 'ທ່ານຕ້ອງການລົບໂຊນ <b>"' + name + '"</b> ແທ້ບໍ່?' + warn,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'ແມ່ນ, ລົບເລີຍ',
    cancelButtonText: 'ຍົກເລີກ'
  }).then(function(r) {
    if (r.isConfirmed) {
      $('#delete_zone_id').val(id);
      $('#deleteZoneForm').submit();
    }
  });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
