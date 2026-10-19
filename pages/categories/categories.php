<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

// Load Centralized Backend API Logic
require_once __DIR__ . '/../../api/categories_backend.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/categories.css?v=<?php echo filemtime(__DIR__ . '/../../themes/categories.css'); ?>">
<link rel="stylesheet" href="../../themes/users.css?v=<?php echo filemtime(__DIR__ . '/../../themes/users.css'); ?>">

<div class="content-wrapper bg-light">

  <!-- Content Header -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-12 col-sm-6 mb-2 mb-sm-0">
          <h5 class="m-0 font-weight-bold text-dark" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon'; font-size: 18px;">
            <i class="fas fa-th-list text-primary mr-2"></i> <?php echo htmlspecialchars(t('categories.page_title', 'ຈັດການປະເພດສິນຄ້າ')); ?>
          </h5>
        </div>
        <div class="col-12 col-sm-6 text-sm-right">
          <div class="d-flex flex-wrap justify-content-sm-end" style="gap: 8px;">
            <?php if (!empty($catPrinters) && hasPermission('categories', 'edit')): ?>
            <button type="button" class="btn btn-outline-secondary px-3 py-1 font-weight-bold m-0" data-toggle="modal" data-target="#bulkPrinterModal" style="border-radius: 6px;">
              <i class="fas fa-print mr-1"></i> <?php echo htmlspecialchars(t('categories.btn_bulk_printer', 'ຕັ້ງເຄື່ອງພິມທຸກໝວດ')); ?>
            </button>
            <?php endif; ?>
            <?php if (hasPermission('categories', 'add')): ?>
            <button type="button" class="btn btn-primary px-3 py-1 font-weight-bold shadow-sm m-0" data-toggle="modal" data-target="#addCategoryModal" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
              <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('categories.btn_add', 'ເພີ່ມປະເພດສິນຄ້າ')); ?>
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SweetAlert Notification -->
  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($message_type === 'success'): ?>
        Swal.fire({
          icon: 'success',
          title: '<?php echo htmlspecialchars(t('categories.msg_success_title', 'ສຳເລັດ'), ENT_QUOTES); ?>',
          text: '<?php echo $message; ?>',
          showConfirmButton: false,
          timer: 1500
        });
        <?php else: ?>
        Swal.fire({
          icon: 'error',
          title: '<?php echo htmlspecialchars(t('categories.msg_error_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>',
          text: '<?php echo $message; ?>',
          confirmButtonColor: '#2563eb',
          confirmButtonText: '<?php echo htmlspecialchars(t('categories.btn_ok', 'ຕົກລົງ'), ENT_QUOTES); ?>'
        });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <!-- Main content -->
  <section class="content pb-5">
    <div class="container-fluid">

      <!-- MAIN CATEGORY TABLE CARD -->
      <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; background: #ffffff;">

        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap py-3 border-0" style="gap: 12px;">
          <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 7px 12px;"><span id="catCountNum"><?php echo count($allCategories); ?></span> <?php echo htmlspecialchars(t('categories.count_label', 'ລາຍການ')); ?></span>
          <div class="d-flex align-items-center ml-auto flex-grow-1 justify-content-end" style="gap: 8px; max-width: 420px;">
            <div class="input-group" style="flex: 1 1 auto;">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-search text-muted"></i></span>
              </div>
              <input type="text" id="catSearch" class="form-control border-left-0" placeholder="<?php echo htmlspecialchars(t('categories.search_ph', 'ຄົ້ນຫາຊື່ໝວດໝູ່...')); ?>" onkeyup="filterCategories()" style="height: 44px; font-size: 0.95rem; border-radius: 0 8px 8px 0; border-left: none;">
            </div>
            <button type="button" id="catSearchClear" class="btn btn-light border d-flex align-items-center justify-content-center" style="border-radius: 8px; height: 44px; width: 44px;" title="<?php echo htmlspecialchars(t('categories.clear', 'ລ້າງຄ່າ')); ?>" onclick="document.getElementById('catSearch').value='';filterCategories();document.getElementById('catSearch').focus();"><i class="fas fa-redo"></i></button>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="catTable">
              <thead class="bg-light text-secondary" style="font-size: 0.88rem; text-transform: uppercase;">
                <tr>
                  <th class="py-3 text-center" style="width: 60px;"><?php echo htmlspecialchars(t('categories.col_no', 'ລຳດັບ')); ?></th>
                  <th class="py-3 text-center" style="width: 100px;"><?php echo htmlspecialchars(t('categories.col_code', 'ລະຫັດ')); ?></th>
                  <th class="py-3" style="white-space: nowrap !important;"><?php echo htmlspecialchars(t('categories.col_name', 'ຊື່ປະເພດສິນຄ້າ')); ?></th>
                  <th class="py-3"><?php echo htmlspecialchars(t('categories.col_desc', 'ລາຍລະອຽດ')); ?></th>
                  <th class="py-3" style="width: 180px;"><?php echo htmlspecialchars(t('categories.col_printer', 'ເຄື່ອງພິມ')); ?></th>
                  <th class="py-3" style="width: 180px;"><?php echo htmlspecialchars(t('categories.col_created_at', 'ວັນທີບັນທຶກ')); ?></th>
                  <th class="text-center py-3" style="width: 140px;"><?php echo htmlspecialchars(t('categories.col_action', 'ຈັດການ')); ?></th>
                </tr>
              </thead>
              <tbody style="font-size: 0.95rem;">
                <tr id="catNoResult" style="display: none;">
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-search fa-lg mb-2 d-block opacity-50"></i>
                    <?php echo htmlspecialchars(t('categories.no_result', 'ບໍ່ພົບໝວດໝູ່ທີ່ຄົ້ນຫາ')); ?>
                  </td>
                </tr>
                <?php if (!empty($allCategories)): ?>
                  <?php $idx = 1; foreach ($allCategories as $cat):
                    $catJson = htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8');
                    $createdAt = !empty($cat['created_at']) ? date('d/m/Y H:i', strtotime($cat['created_at'])) : '-';
                    $productCount = intval($cat['product_count'] ?? 0);
                    $printerName = trim((string)($cat['printer_name'] ?? ''));
                    $catSearchAttr = strtolower(($cat['display_code'] ?? '') . ' ' . ($cat['category_name'] ?? '') . ' ' . ($cat['description'] ?? '') . ' ' . $printerName);
                  ?>
                    <tr class="cat-row" data-s="<?php echo htmlspecialchars($catSearchAttr); ?>">
                      <td class="align-middle text-center text-muted font-weight-bold row-no"><?php echo $idx++; ?></td>

                      <td class="align-middle text-center font-weight-bold">
                        <span class="badge badge-light border px-2 py-1" style="font-size: 0.85rem; color: #2563eb; background-color: #eff6ff; border-color: #bfdbfe !important;"><?php echo branchCodeHtml($cat['display_code'] ?? $cat['category_id']); ?></span>
                      </td>

                      <td class="align-middle font-weight-bold text-dark cat-name-cell" style="white-space: nowrap !important;">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                      </td>

                      <td class="align-middle text-muted cat-desc-cell">
                        <?php echo htmlspecialchars($cat['description'] ?: '-'); ?>
                      </td>

                      <td class="align-middle">
                        <?php if ($printerName !== ''): ?>
                          <?php $printerOff = isset($cat['printer_status']) && (int)$cat['printer_status'] !== 1; ?>
                          <span class="badge border px-2 py-1" style="font-size: 0.85rem; <?php echo $printerOff ? 'color: #64748b; background-color: #f1f5f9; border-color: #cbd5e1 !important; text-decoration: line-through;' : 'color: #047857; background-color: #ecfdf5; border-color: #a7f3d0 !important;'; ?>">
                            <i class="fas fa-print mr-1"></i><?php echo htmlspecialchars($printerName); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('categories.no_printer', 'ບໍ່ໄດ້ຕັ້ງ')); ?></span>
                        <?php endif; ?>
                      </td>

                      <td class="align-middle text-secondary" style="font-size: 0.88rem;">
                        <?php echo $createdAt; ?>
                      </td>

                      <td class="text-center align-middle">
                        <?php
                          $canEditCat = hasPermission('categories', 'edit');
                          $canDeleteCat = hasPermission('categories', 'del');
                        ?>
                        <?php if ($canEditCat || $canDeleteCat): ?>
                          <div class="btn-group btn-group-sm">
                            <?php if ($canEditCat): ?>
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-outline-warning" title="<?php echo htmlspecialchars(t('categories.title_edit', 'ແກ້ໄຂ')); ?>" onclick='openEditModal(<?php echo $catJson; ?>)'>
                              <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canDeleteCat): ?>
                            <!-- Delete Button -->
                            <button type="button" class="btn btn-outline-danger" title="<?php echo htmlspecialchars(t('categories.title_delete', 'ລົບ')); ?>" onclick="confirmDeleteCat(<?php echo $cat['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['category_name'])); ?>', <?php echo $productCount; ?>)">
                              <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('categories.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                        <?php endif; ?>
                      </td>

                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="fas fa-folder-open fa-3x mb-3 text-secondary d-block"></i>
                      <?php echo htmlspecialchars(t('categories.empty_state', 'ບໍ່ພົບຂໍ້ມູນປະເພດສິນຄ້າໃນລະບົບ')); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Footer -->
        <div id="catPagFooter" class="card-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-3 px-md-4" style="border-radius: 0 0 12px 12px; gap: 10px; flex-wrap: wrap;">
          <div class="d-flex align-items-center text-muted" style="gap: 8px; font-size: 0.85rem;">
            <span class="text-muted font-weight-bold">
              <?php echo htmlspecialchars(t('categories.page_showing', 'ສະແດງ')); ?>
              <span id="cat_page_info_start">0</span>-<span id="cat_page_info_end">0</span>
              <?php echo htmlspecialchars(t('categories.page_items', 'ລາຍການ')); ?>
            </span>
            <select id="catPageSizeSelect" class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;" onchange="changeCatPageSize()">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="all"><?php echo htmlspecialchars(t('categories.page_all', 'ທັງໝົດ')); ?></option>
            </select>
          </div>
          <nav aria-label="Category Pagination" class="ml-auto">
            <ul class="pagination pagination-circle mb-0 justify-content-end" id="catPagination"></ul>
          </nav>
        </div>

        <style>
          /* themes/users.css forces .card-footer into a centered column on mobile (for a
             different page's pagination) — this ID-scoped override always wins over that
             class-only rule regardless of viewport width, keeping this footer's nav pinned
             to the right as requested. */
          #catPagFooter {
            flex-direction: row !important;
            text-align: left !important;
          }
          #catPagFooter > nav {
            margin-left: auto !important;
          }
        </style>

      </div>

    </div>
  </section>
</div>

<!-- INCLUDE MODAL COMPONENTS -->
<?php
require_once __DIR__ . '/components/form_add_category.php';
require_once __DIR__ . '/components/form_edit_category.php';
require_once __DIR__ . '/components/form_bulk_printer.php';
?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
  var I18N_CATEGORIES = <?php echo tjson([
      'categories.cannot_delete_title' => 'ບໍ່ສາມາດລົບໄດ້!',
      'categories.cannot_delete_msg' => 'ໝວດໝູ່ "{name}" ມີລາຍການສິນຄ້າຢູ່ {count} ລາຍການ.',
      'categories.cannot_delete_hint' => 'ກະລຸນາຍ້າຍ ຫຼື ລົບລາຍການສິນຄ້າໃນໝວດໝູ່ນີ້ອອກກ່ອນ ຈຶ່ງຈະສາມາດລົບໄດ້!',
      'categories.btn_ok' => 'ຕົກລົງ',
      'categories.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
      'categories.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບປະເພດສິນຄ້າ "{name}" ແທ້ຫຼືບໍ່?',
      'categories.btn_delete_confirm' => '<i class="fas fa-trash-alt mr-1"></i> ລົບເລີຍ',
      'categories.btn_cancel' => 'ຍົກເລີກ',
  ]); ?>;

  var NEXT_CAT_ID = <?php echo $next_cat_id; ?>;
  // ລະຫັດທີ່ສະແດງ ແຍກຕາມສາຂາ ("1-13") — ລະຫັດພາຍໃນ (NEXT_CAT_ID) ຍັງສົ່ງໄປແບບເຊື່ອງ
  var NEXT_CAT_CODE = <?php echo json_encode($next_cat_code ?? (string)$next_cat_id); ?>;

  // Auto-fill next ID when add modal opens
  $('#addCategoryModal').on('show.bs.modal', function() {
    $('#add_cat_id').val(NEXT_CAT_ID);
    $('#add_cat_code_label').val(NEXT_CAT_CODE);
  });

  function openEditModal(cat) {
    $('#edit_cat_id').val(cat.category_id);
    $('#edit_cat_id_input').val(cat.category_id);
    $('#edit_cat_code_label').val(cat.display_code || cat.category_id);
    $('#edit_cat_name').val(cat.category_name);
    $('#edit_cat_desc').val(cat.description);
    $('#edit_cat_printer').val(cat.printer_id != null ? String(cat.printer_id) : '');
    $('#editCategoryModal').modal('show');
  }

  function confirmDeleteCat(catId, catName, productCount) {
    if (productCount && productCount > 0) {
      Swal.fire({
        icon: 'warning',
        title: I18N_CATEGORIES['categories.cannot_delete_title'],
        html: '<div style="font-family: \'Noto Sans Lao\', \'Souliyo\', \'Boon\', sans-serif;">' + I18N_CATEGORIES['categories.cannot_delete_msg'].replace('{name}', '<b>"' + catName + '"</b>').replace('{count}', '<b>' + productCount + '</b>') + '<br><span style="font-size: 0.9rem; color: #64748b; display: inline-block; margin-top: 8px;">' + I18N_CATEGORIES['categories.cannot_delete_hint'] + '</span></div>',
        confirmButtonColor: '#2563eb',
        confirmButtonText: I18N_CATEGORIES['categories.btn_ok'],
        heightAuto: false
      });
      return;
    }

    Swal.fire({
      title: I18N_CATEGORIES['categories.confirm_delete_title'],
      text: I18N_CATEGORIES['categories.confirm_delete_text'].replace('{name}', catName),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_CATEGORIES['categories.btn_delete_confirm'],
      cancelButtonText: I18N_CATEGORIES['categories.btn_cancel'],
      heightAuto: false
    }).then(function(result) {
      if (result.isConfirmed) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var actInput = document.createElement('input');
        actInput.type = 'hidden';
        actInput.name = 'action';
        actInput.value = 'delete_category';
        form.appendChild(actInput);

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'category_id';
        idInput.value = catId;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  // ====== Pagination (client-side, all rows already rendered) ======
  var catCurrentPage = 1;
  var catPageSize = 10;
  var catFilteredRows = [];

  function initCatPagination() {
    filterCategories();
  }

  // Live search — filters which rows are eligible, then pagination decides what's shown
  function filterCategories() {
    var searchEl = document.getElementById('catSearch');
    var q = searchEl ? (searchEl.value || '').toLowerCase().trim() : '';
    var rows = Array.from(document.querySelectorAll('#catTable tbody tr.cat-row'));
    catFilteredRows = rows.filter(function(tr) {
      return q === '' || (tr.getAttribute('data-s') || '').indexOf(q) > -1;
    });

    var noRes = document.getElementById('catNoResult');
    if (noRes) noRes.style.display = (catFilteredRows.length === 0 && rows.length > 0) ? '' : 'none';

    catCurrentPage = 1;
    applyCatPagination();
  }

  function changeCatPageSize() {
    var val = document.getElementById('catPageSizeSelect').value;
    catPageSize = (val === 'all') ? (catFilteredRows.length || 99999) : parseInt(val, 10);
    catCurrentPage = 1;
    applyCatPagination();
  }

  function goToCatPage(page) {
    var totalPages = Math.ceil(catFilteredRows.length / catPageSize) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    catCurrentPage = page;
    applyCatPagination();
  }

  function applyCatPagination() {
    var allRows = document.querySelectorAll('#catTable tbody tr.cat-row');
    allRows.forEach(function(r) { r.style.display = 'none'; });

    var total = catFilteredRows.length;
    var totalPages = Math.ceil(total / catPageSize) || 1;
    if (catCurrentPage > totalPages) catCurrentPage = totalPages;

    var startIdx = (catCurrentPage - 1) * catPageSize;
    var endIdx = (catPageSize >= 99999) ? total : startIdx + catPageSize;

    var shown = 0;
    for (var i = startIdx; i < endIdx && i < total; i++) {
      if (catFilteredRows[i]) {
        catFilteredRows[i].style.display = '';
        shown++;
        var no = catFilteredRows[i].querySelector('.row-no');
        if (no) no.textContent = startIdx + shown;
      }
    }

    var startEl = document.getElementById('cat_page_info_start');
    var endEl = document.getElementById('cat_page_info_end');
    var totalEl = document.getElementById('cat_page_info_total');
    if (startEl) startEl.textContent = total === 0 ? 0 : startIdx + 1;
    if (endEl) endEl.textContent = Math.min(endIdx, total);
    if (totalEl) totalEl.textContent = total;

    var countNum = document.getElementById('catCountNum');
    if (countNum) countNum.textContent = total;

    renderCatPaginationControls(totalPages);
  }

  function renderCatPaginationControls(totalPages) {
    var ul = document.getElementById('catPagination');
    if (!ul) return;
    ul.innerHTML = '';
    if (totalPages < 1) totalPages = 1;

    var prevLi = document.createElement('li');
    prevLi.className = 'page-item ' + (catCurrentPage <= 1 ? 'disabled' : '');
    prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (catCurrentPage > 1 ? 'onclick="goToCatPage(' + (catCurrentPage - 1) + ')"' : '') + '><i class="fas fa-chevron-left"></i></a>';
    ul.appendChild(prevLi);

    var maxButtons = 5;
    var startPage = Math.max(1, catCurrentPage - 2);
    var endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);

    if (startPage > 1) {
      var firstLi = document.createElement('li');
      firstLi.className = 'page-item';
      firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToCatPage(1)">1</a>';
      ul.appendChild(firstLi);
      if (startPage > 2) {
        var dotLi = document.createElement('li');
        dotLi.className = 'page-item disabled';
        dotLi.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
        ul.appendChild(dotLi);
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      var pageLi = document.createElement('li');
      pageLi.className = 'page-item ' + (p === catCurrentPage ? 'active' : '');
      pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToCatPage(' + p + ')">' + p + '</a>';
      ul.appendChild(pageLi);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        var dotLi2 = document.createElement('li');
        dotLi2.className = 'page-item disabled';
        dotLi2.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
        ul.appendChild(dotLi2);
      }
      var lastLi = document.createElement('li');
      lastLi.className = 'page-item';
      lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToCatPage(' + totalPages + ')">' + totalPages + '</a>';
      ul.appendChild(lastLi);
    }

    var nextLi = document.createElement('li');
    nextLi.className = 'page-item ' + (catCurrentPage >= totalPages ? 'disabled' : '');
    nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (catCurrentPage < totalPages ? 'onclick="goToCatPage(' + (catCurrentPage + 1) + ')"' : '') + '><i class="fas fa-chevron-right"></i></a>';
    ul.appendChild(nextLi);
  }

  document.addEventListener('DOMContentLoaded', initCatPagination);
</script>
