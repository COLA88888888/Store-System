<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າລາຍການນຳເຂົ້າສິນຄ້າ: ແກ້ໄຂ/ລົບບິນນຳເຂົ້າ (POST), ດຶງລາຍການຕາມວັນທີ/ສາຂາ ແລ້ວສະແດງເປັນບິນ ຫຼື ລາຍລະອຽດ.
 *   - partials/import_list/actions.php : ການກະທຳ POST
 *   - partials/import_list/data.php : ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ລາຍການນຳເຂົ້າ
 *   - partials/import_list/table.php : ຕາຕະລາງລາຍການນຳເຂົ້າ
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Multi-currency: import costs are stored in KIP, shown in the active currency. A
// currency picked here overrides the store's global display_currency setting for this
// view only — it's a display convenience (see the cost in ฿/$/¥), not a filter on
// which import bills are counted (imports don't record a "tendered currency").
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && isset(getCurrencyMeta()[$currency_filter])) {
    $__ilRates = getExchangeRates($pdo);
    $__ilRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__ilRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__ilRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__ilRate, 'rates' => $__ilRates],
            getCurrencyMeta()[$currency_filter]
        ));
    } else {
        $currency_filter = '';
    }
} else {
    $currency_filter = '';
}

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('import_list') && !hasPermission('import_stock'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// ການກະທຳ POST
require __DIR__ . '/partials/import_list/actions.php';
// ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ລາຍການນຳເຂົ້າ
require __DIR__ . '/partials/import_list/data.php';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/import_stock.css?v=<?php echo filemtime(__DIR__ . '/../../themes/import_stock.css'); ?>">

<div class="content-wrapper bg-light">
  
  <!-- Page Header -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h4 class="m-0 font-weight-bold text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-list-alt text-success mr-2"></i> <?php echo htmlspecialchars(t('import_list.page_title', 'ລາຍການສິນຄ້າຮັບເຂົ້າ')); ?>
          </h4>
        </div>
        <div class="col-sm-6 text-right">
          <a href="import_stock.php" class="btn btn-primary font-weight-bold shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('import_list.import_stock_btn', 'ນຳເຂົ້າສິນຄ້າ')); ?>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="content pb-5">
    <div class="container-fluid">

      <!-- Alert Notification & Success Popup Modal -->
      <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-radius: 10px;">
          <i class="fas <?php echo ($message_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
          <?php echo htmlspecialchars($message); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <?php if ($message_type === 'success'): ?>
          <script>
            $(document).ready(function() {
              var swalTitle = '<?php echo (strpos($message, "ລົບ") !== false || strpos($message, "ຍົກເລີກ") !== false) ? "ລົບສຳເລັດ!" : htmlspecialchars(t("import_list.swal_success_title", "ດຳເນີນການສຳເລັດ!"), ENT_QUOTES); ?>';
              Swal.fire({
                title: swalTitle,
                text: '<?php echo addslashes($message); ?>',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: '<?php echo htmlspecialchars(t('import_list.ok_button', 'ຕົກລົງ'), ENT_QUOTES); ?>',
                timer: 3000,
                timerProgressBar: true
              });
            });
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <!-- DATE RANGE & VIEW TYPE FILTER CARD -->
      <?php require_once __DIR__ . '/components/filter_bar.php'; ?>

      <!-- MAIN TABLE CARD -->
      <div class="card shadow-sm border-0" style="border-radius: 16px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h5 class="card-title font-weight-bold text-dark mb-0" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
                <i class="fas fa-table text-primary mr-2"></i> <?php echo htmlspecialchars(t('import_list.table_card_title', 'ຕາຕະລາງລາຍການສິນຄ້າຮັບເຂົ້າ')); ?>
              </h5>
            </div>
            <div class="col-md-6 text-right d-flex align-items-center justify-content-end">
              <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 0.9rem; height: 34px; line-height: 18px;">
                <?php echo htmlspecialchars(t('import_list.total_prefix', 'ລວມ')); ?> <?php echo number_format($total_records); ?> <?php echo ($view_type === 'bill') ? htmlspecialchars(t('import_list.unit_bills', 'ໃບບິນ')) : htmlspecialchars(t('import_list.unit_items', 'ລາຍການ')); ?>
              </span>
            </div>
          </div>
        </div>

        <div class="card-body p-4">
          <!-- Search & Page Size Bar -->
          <div class="row align-items-center mb-3">
            <div class="col-md-4 mb-2 mb-md-0">
              <div class="d-flex align-items-center">
                <!-- <span class="mr-2 font-weight-bold text-muted" style="font-size: 0.88rem;">ສະແດງ:</span> -->
                <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 100px; height: 38px; border-radius: 6px;" onchange="changePageSize(this.value)">
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
            </div>

            <div class="col-md-8 text-md-right">
              <div class="input-group input-group-sm ml-md-auto" style="max-width: 340px;">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-white border-right-0" style="border-radius: 6px 0 0 6px;"><i class="fas fa-search text-muted"></i></span>
                </div>
                <input type="text" id="import_search" class="form-control border-left-0" placeholder="<?php echo htmlspecialchars(t('import_list.search_placeholder', 'ຄົ້ນຫາ ໃບບິນ, ຊື່ສິນຄ້າ, ຜູ້ສະໜອງ...')); ?>" onkeyup="filterImportHistory()" style="border-radius: 0 6px 6px 0; height: 36px;">
              </div>
            </div>
          </div>

<?php require __DIR__ . '/partials/import_list/table.php'; /* ຕາຕະລາງລາຍການນຳເຂົ້າ */ ?>
          <!-- Pagination Bar -->
          <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 px-2">
            <div class="d-flex justify-content-end ml-md-auto">
              <ul class="pagination pagination-circle mb-0" id="importPagination">
              </ul>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>
</div>

<!-- MODALS & COMPONENTS -->
<?php require_once __DIR__ . '/components/modal_edit_import.php'; ?>
<?php require_once __DIR__ . '/components/modal_bill_details.php'; ?>
<?php require_once __DIR__ . '/components/modal_edit_master_bill.php'; ?>
<?php require_once __DIR__ . '/components/modal_select_product.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<!-- JAVASCRIPT LOGIC -->
<?php require_once __DIR__ . '/components/import_list_js.php'; ?>
