<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

// Load Centralized Backend API Logic
require_once __DIR__ . '/../../api/units_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/categories.css?v=<?php echo filemtime(__DIR__ . '/../../themes/categories.css'); ?>">
<link rel="stylesheet" href="../../themes/users.css?v=<?php echo filemtime(__DIR__ . '/../../themes/users.css'); ?>">

<div class="content-wrapper bg-light">

  <!-- Content Header -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h5 class="m-0 font-weight-bold text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon'; font-size: 18px;">
            <i class="fas fa-ruler-combined text-info mr-2"></i> <?php echo htmlspecialchars(t('units.page_title', 'ຈັດການຫົວໜ່ວຍສິນຄ້າ')); ?>
          </h5>
        </div>
        <div class="col-sm-6 text-right">
          <?php if (hasPermission('units', 'add')): ?>
          <button type="button" class="btn btn-primary px-3 py-1 font-weight-bold shadow-sm" data-toggle="modal" data-target="#addUnitModal" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('units.btn_add', 'ເພີ່ມຫົວໜ່ວຍ')); ?>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- SweetAlert Notification -->
  <?php if ($unit_message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($unit_message_type === 'success'): ?>
        Swal.fire({
          icon: 'success',
          title: '<?php echo htmlspecialchars(t('units.msg_success_title', 'ສຳເລັດ'), ENT_QUOTES); ?>',
          text: '<?php echo $unit_message; ?>',
          showConfirmButton: false,
          timer: 1500
        });
        <?php else: ?>
        Swal.fire({
          icon: 'error',
          title: '<?php echo htmlspecialchars(t('units.msg_error_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>',
          text: '<?php echo $unit_message; ?>',
          confirmButtonColor: '#2563eb',
          confirmButtonText: '<?php echo htmlspecialchars(t('units.btn_ok', 'ຕົກລົງ'), ENT_QUOTES); ?>'
        });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <!-- Main content -->
  <section class="content pb-5">
    <div class="container-fluid">

      <!-- MAIN UNIT TABLE CARD -->
      <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; background: #ffffff;">

        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap py-3 border-0" style="gap: 12px;">
          <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <h6 class="card-title m-0 font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon'; color: #0f172a; font-size: 1rem;">
              <?php echo htmlspecialchars(t('units.table_card_title', 'ລາຍງານຫົວໜ່ວຍທັງໝົດ')); ?>
            </h6>
            <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 7px 12px;"><span id="unitCountNum"><?php echo count($allUnits); ?></span> <?php echo htmlspecialchars(t('units.count_label', 'ລາຍການ')); ?></span>
            <?php if (($canAllBranches) && count($stores) > 1): ?>
            <div class="prod-filter-wrap">
              <select id="storeFilter" class="form-control font-weight-bold" onchange="window.location.href='units.php?store_id=' + this.value">
                <?php foreach ($stores as $st): ?>
                  <option value="<?php echo $st['store_id']; ?>" <?php echo ($selectedStoreId == $st['store_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('products.store_main_suffix', '(ສາງຫຼັກ)')) : htmlspecialchars(t('products.store_branch_suffix', '(ສາຂາຍ່ອຍ)')); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="unitTable">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
                <tr>
                  <th class="py-3 text-center" style="width: 60px;"><?php echo htmlspecialchars(t('units.col_no', 'ລຳດັບ')); ?></th>
                  <th class="py-3 text-center" style="width: 100px;"><?php echo htmlspecialchars(t('units.col_code', 'ລະຫັດ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('units.col_name', 'ຊື່ຫົວໜ່ວຍ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('units.col_desc', 'ລາຍລະອຽດ')); ?></th>
                  <th class="py-3" style="width: 180px;"><?php echo htmlspecialchars(t('units.col_created_at', 'ວັນທີບັນທຶກ')); ?></th>
                  <th class="text-center py-3" style="width: 140px;"><?php echo htmlspecialchars(t('units.col_action', 'ຈັດການ')); ?></th>
                </tr>
              </thead>
              <tbody style="font-size: 0.95rem;">
                <?php if (!empty($allUnits)): ?>
                  <?php $uIdx = 1; foreach ($allUnits as $unit):
                    $unitJson = htmlspecialchars(json_encode($unit), ENT_QUOTES, 'UTF-8');
                    $unitCreatedAt = !empty($unit['created_at']) ? date('d/m/Y H:i', strtotime($unit['created_at'])) : '-';
                    $unitProductCount = intval($unit['product_count'] ?? 0);
                  ?>
                    <tr class="unit-row">
                      <td class="align-middle text-center text-muted font-weight-bold row-no"><?php echo $uIdx++; ?></td>

                      <td class="align-middle text-center font-weight-bold">
                        <span class="badge badge-light border px-2 py-1" style="font-size: 0.85rem; color: #2563eb; background-color: #eff6ff; border-color: #bfdbfe !important;"><?php echo branchCodeHtml($unit['display_code'] ?? $unit['unit_id']); ?></span>
                      </td>

                      <td class="align-middle font-weight-bold text-dark">
                        <?php echo htmlspecialchars($unit['unit_name']); ?>
                      </td>

                      <td class="align-middle text-muted">
                        <?php echo htmlspecialchars($unit['description'] ?: '-'); ?>
                      </td>

                      <td class="align-middle text-secondary" style="font-size: 0.88rem;">
                        <?php echo $unitCreatedAt; ?>
                      </td>

                      <td class="text-center align-middle">
                        <?php
                          $canEditUnit = hasPermission('units', 'edit');
                          $canDeleteUnit = hasPermission('units', 'del');
                        ?>
                        <?php if ($canEditUnit || $canDeleteUnit): ?>
                          <div class="btn-group btn-group-sm">
                            <?php if ($canEditUnit): ?>
                            <button type="button" class="btn btn-outline-warning" title="<?php echo htmlspecialchars(t('units.title_edit', 'ແກ້ໄຂ')); ?>" onclick='openEditUnitModal(<?php echo $unitJson; ?>)'>
                              <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canDeleteUnit): ?>
                            <button type="button" class="btn btn-outline-danger" title="<?php echo htmlspecialchars(t('units.title_delete', 'ລົບ')); ?>" onclick="confirmDeleteUnit(<?php echo $unit['unit_id']; ?>, '<?php echo htmlspecialchars(addslashes($unit['unit_name'])); ?>', <?php echo $unitProductCount; ?>)">
                              <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('units.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                        <?php endif; ?>
                      </td>

                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                      <i class="fas fa-ruler-combined fa-3x mb-3 text-secondary d-block"></i>
                      <?php echo htmlspecialchars(t('units.empty_state', 'ບໍ່ພົບຂໍ້ມູນຫົວໜ່ວຍໃນລະບົບ')); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Footer (ແບບດຽວກັບໜ້າປະເພດສິນຄ້າ) -->
        <div id="unitPagFooter" class="card-footer table-pager-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-3 px-md-4" style="border-radius: 0 0 12px 12px; gap: 10px; flex-wrap: wrap;">
          <div class="d-flex align-items-center text-muted" style="gap: 8px; font-size: 0.85rem;">
            <span class="text-muted font-weight-bold">
              <?php echo htmlspecialchars(t('units.page_showing', 'ສະແດງ')); ?>
              <span data-pager-start>0</span>-<span data-pager-end>0</span>
              <?php echo htmlspecialchars(t('units.page_items', 'ລາຍການ')); ?>
            </span>
            <select data-pager-size class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="all"><?php echo htmlspecialchars(t('units.page_all', 'ທັງໝົດ')); ?></option>
            </select>
          </div>
          <nav aria-label="Unit Pagination" class="ml-auto">
            <ul class="pagination pagination-circle mb-0 justify-content-end" data-pager-ul></ul>
          </nav>
        </div>

        <?php echo themeCssLink('table-pager.css'); ?>

      </div>

    </div>
  </section>
</div>

<!-- INCLUDE MODAL COMPONENTS -->
<?php
require_once __DIR__ . '/components/form_add_unit.php';
require_once __DIR__ . '/components/form_edit_unit.php';
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
  var I18N_UNITS = <?php echo tjson([
      'units.cannot_delete_title' => 'ບໍ່ສາມາດລົບໄດ້!',
      'units.cannot_delete_msg' => 'ຫົວໜ່ວຍ "{name}" ມີສິນຄ້າໃຊ້ຢູ່ {count} ລາຍການ.',
      'units.cannot_delete_hint' => 'ກະລຸນາປ່ຽນຫົວໜ່ວຍລາຍການສິນຄ້ານີ້ອອກກ່ອນ ຈຶ່ງຈະສາມາດລົບໄດ້!',
      'units.btn_ok' => 'ຕົກລົງ',
      'units.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
      'units.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບຫົວໜ່ວຍ "{name}" ແທ້ຫຼືບໍ່?',
      'units.btn_delete_confirm' => '<i class="fas fa-trash-alt mr-1"></i> ລົບເລີຍ',
      'units.btn_cancel' => 'ຍົກເລີກ',
  ]); ?>;

  var NEXT_UNIT_ID = <?php echo $next_unit_id; ?>;
  // ລະຫັດທີ່ສະແດງ ແຍກຕາມສາຂາ ("1-13") — ລະຫັດພາຍໃນ (NEXT_UNIT_ID) ຍັງສົ່ງໄປແບບເຊື່ອງ
  var NEXT_UNIT_CODE = <?php echo json_encode($next_unit_code ?? (string)$next_unit_id); ?>;

  // Auto-fill next ID when add modal opens
  $('#addUnitModal').on('show.bs.modal', function() {
    $('#add_unit_id').val(NEXT_UNIT_ID);
    $('#add_unit_code_label').val(NEXT_UNIT_CODE);
  });

  function openEditUnitModal(unit) {
    $('#edit_unit_id').val(unit.unit_id);
    $('#edit_unit_id_input').val(unit.unit_id);
    $('#edit_unit_code_label').val(unit.display_code || unit.unit_id);
    $('#edit_unit_name').val(unit.unit_name);
    $('#edit_unit_desc').val(unit.description);
    $('#editUnitModal').modal('show');
  }

  function confirmDeleteUnit(unitId, unitName, productCount) {
    if (productCount && productCount > 0) {
      Swal.fire({
        icon: 'warning',
        title: I18N_UNITS['units.cannot_delete_title'],
        html: '<div style="font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', sans-serif;">' + I18N_UNITS['units.cannot_delete_msg'].replace('{name}', '<b>"' + unitName + '"</b>').replace('{count}', '<b>' + productCount + '</b>') + '<br><span style="font-size: 0.9rem; color: #64748b; display: inline-block; margin-top: 8px;">' + I18N_UNITS['units.cannot_delete_hint'] + '</span></div>',
        confirmButtonColor: '#2563eb',
        confirmButtonText: I18N_UNITS['units.btn_ok'],
        heightAuto: false
      });
      return;
    }

    Swal.fire({
      title: I18N_UNITS['units.confirm_delete_title'],
      text: I18N_UNITS['units.confirm_delete_text'].replace('{name}', unitName),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_UNITS['units.btn_delete_confirm'],
      cancelButtonText: I18N_UNITS['units.btn_cancel'],
      heightAuto: false
    }).then(function(result) {
      if (result.isConfirmed) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var actInput = document.createElement('input');
        actInput.type = 'hidden';
        actInput.name = 'action';
        actInput.value = 'delete_unit';
        form.appendChild(actInput);

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'unit_id';
        idInput.value = unitId;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  // ແບ່ງໜ້າ (assets/js/table-pager.js — ແບບດຽວກັບໜ້າປະເພດສິນຄ້າ)
  document.addEventListener('DOMContentLoaded', function() {
    TablePager.create({ rows: '#unitTable tbody tr.unit-row', footer: '#unitPagFooter' });
  });
</script>
<script src="<?php echo $base_path; ?>assets/js/table-pager.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/table-pager.js'); ?>"></script>
