<?php
session_start();
require_once __DIR__ . '/../../config/db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$base_path = '../../';
$activeStoreId = getActiveStoreId($pdo);

$message = '';
$message_type = '';
$insertedId = 0;

// POST Action to save quotation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_quotation') {
    $cart_json = $_POST['cart_json'] ?? '[]';
    $items = json_decode($cart_json, true);
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? 'ລູກຄ້າທົ່ວໄປ');
    $discount_amount = floatval(str_replace(',', '', $_POST['discount_amount'] ?? '0'));
    
    // Auto Generate Quotation Number (Continuous sequence)
    $todayStr  = date('Ymd');
    $stmtSeq = $pdo->query("SELECT MAX(quotation_id) FROM quotations");
    $maxId = intval($stmtSeq->fetchColumn() ?? 0);
    $quotation_number = 'QT' . $todayStr . '-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);

    if (!empty($items)) {
        try {
            $pdo->beginTransaction();
            
            $subtotal = 0;
            foreach ($items as $it) {
                $q = floatval($it['quantity'] ?? 0);
                $p = floatval($it['price'] ?? 0);
                $subtotal += ($q * $p);
            }
            $total_amount = $subtotal - $discount_amount;
            
            $user_id = intval($_SESSION['user_id'] ?? 1);

            // Insert Quotation
            $stmt = $pdo->prepare("INSERT INTO quotations (quotation_no, customer_id, customer_name, subtotal, discount_amount, vat_amount, total_amount, status, store_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, 0, ?, 'pending', ?, ?, NOW())");
            $stmt->execute([
                $quotation_number,
                $customer_id,
                $customer_name,
                $subtotal,
                $discount_amount,
                $total_amount,
                $activeStoreId,
                $user_id
            ]);
            $quotation_id = $pdo->lastInsertId();
            $insertedId = $quotation_id;
            
            // Insert Details
            $stmtD = $pdo->prepare("INSERT INTO quotation_details (quotation_id, product_id, product_name, qty, price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($items as $it) {
                $pid = intval($it['product_id']);
                $pname = $it['product_name'] ?? '';
                $pqty = floatval($it['quantity'] ?? 0);
                $pprice = floatval($it['price'] ?? 0);
                $ptotal = floatval($it['total'] ?? ($pqty * $pprice));
                $stmtD->execute([$quotation_id, $pid, $pname, $pqty, $pprice, $ptotal]);
            }

            $pdo->commit();
            $message = "ບັນທຶກໃບສະເໜີລາຄາສຳເລັດແລ້ວ! ເລກທີ: " . $quotation_number;
            $message_type = 'success';

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = "ກະລຸນາເພີ່ມສິນຄ້າກ່ອນບັນທຶກ!";
        $message_type = 'danger';
    }
}

// Fetch Customers
$custStmt = $pdo->query("SELECT customer_id, customer_name, phone FROM customers ORDER BY customer_name ASC");
$customers = $custStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Categories
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$catStmt->execute([$activeStoreId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Products for search
$prodStmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.barcode, p.price, p.qty, p.category_id, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
");
$prodStmt->execute([$activeStoreId]);
$products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

$stmtSeq = $pdo->query("SELECT MAX(quotation_id) FROM quotations");
$maxIdUI = intval($stmtSeq->fetchColumn() ?? 0);
$next_q_num = 'QT' . date('Ymd') . '-' . str_pad($maxIdUI + 1, 4, '0', STR_PAD_LEFT);

require_once __DIR__ . '/../../layouts/header.php';
?>

<div class="content-wrapper bg-light">
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h4 class="m-0 font-weight-bold text-dark"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i> ສ້າງໃບສະເໜີລາຄາ</h4>
        </div>
      </div>
    </div>
  </section>

  <section class="content pb-5">
    <div class="container-fluid">

      <?php if (!empty($message)): ?>
        <script>
          $(document).ready(function() {
            Swal.fire({
              title: '<?php echo $message_type === "success" ? "ສຳເລັດ!" : "ຜິດພາດ!"; ?>',
              text: '<?php echo addslashes($message); ?>',
              icon: '<?php echo $message_type; ?>',
              confirmButtonColor: '#3085d6',
              confirmButtonText: 'ຕົກລົງ'
            }).then(() => {
                <?php if ($message_type === 'success' && $insertedId > 0): ?>
                    window.open('print.php?id=<?php echo $insertedId; ?>', '_blank', 'width=800,height=600');
                    window.location.href = 'quotation.php'; // reload to clear form
                <?php endif; ?>
            });
          });
        </script>
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
        <div class="card-body p-4">
          <form id="quotationForm" action="" method="POST">
            <input type="hidden" name="action" value="save_quotation">
            <input type="hidden" id="cart_json_input" name="cart_json" value="[]">

            <!-- Top Row -->
            <div class="row bg-light p-3 rounded mb-3 border">
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold text-dark mb-1">ເລກທີໃບສະເໜີລາຄາ</label>
                <input type="text" class="form-control font-weight-bold bg-white text-primary border-primary" value="<?php echo $next_q_num; ?>" readonly style="border-radius: 8px;">
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold text-dark mb-1">ລູກຄ້າ</label>
                <select name="customer_id" id="customer_id" class="form-control select2" style="width:100%; border-radius: 8px;" onchange="updateCustomerName()">
                    <option value="0">ລູກຄ້າທົ່ວໄປ</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['customer_id']; ?>" data-name="<?php echo htmlspecialchars($c['customer_name']); ?>">
                            <?php echo htmlspecialchars($c['customer_name']); ?> <?php echo !empty($c['phone']) ? '('.$c['phone'].')' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="customer_name" id="customer_name" value="ລູກຄ້າທົ່ວໄປ">
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold text-dark mb-1">ຜູ້ອອກບິນ</label>
                <input type="text" class="form-control bg-white font-weight-bold" value="<?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>" readonly style="border-radius: 8px;">
              </div>
            </div>

            <!-- Product Entry Row -->
            <div class="row mb-3">
              <div class="col-12">
                <button type="button" class="btn btn-primary btn-lg font-weight-bold shadow-sm" data-toggle="modal" data-target="#productSelectModal" style="border-radius: 8px;">
                    <i class="fas fa-list-ul mr-2"></i> ເລືອກສິນຄ້າ (ຕິກເອົາຕາມໝວດໝູ່)
                </button>
              </div>
            </div>

            <!-- Cart Table -->
            <div class="border rounded mb-3 shadow-sm bg-white">
              <div class="table-responsive">
              <table class="table table-hover mb-0 align-middle text-nowrap" style="min-width: 720px;">
                <thead class="bg-light">
                  <tr>
                    <th class="text-center" style="width: 50px;">ລ/ດ</th>
                    <th>ລາຍການສິນຄ້າ</th>
                    <th class="text-center" style="width: 120px;">ຈຳນວນ</th>
                    <th class="text-right" style="width: 150px;">ລາຄາ/ໜ່ວຍ</th>
                    <th class="text-right" style="width: 150px;">ລວມເງິນ</th>
                    <th class="text-center" style="width: 80px;">ລຶບ</th>
                  </tr>
                </thead>
                <tbody id="cart_tbody">
                  <tr id="empty_cart_row">
                    <td colspan="6" class="text-center text-muted py-4">ຍັງບໍ່ມີລາຍການສິນຄ້າ</td>
                  </tr>
                </tbody>
              </table>
              </div>
            </div>

            <!-- Summary & Submit -->
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>ລວມເງິນ:</span>
                                <span id="lbl_subtotal" class="font-weight-bold">0 ₭</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span>ສ່ວນຫຼຸດ:</span>
                                <input type="text" name="discount_amount" id="input_discount" class="form-control form-control-sm text-right w-50" value="0" oninput="formatNumberInput(this); updateCartRender();">
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="font-weight-bold" style="font-size: 1.1rem;">ຍອດລວມທັງໝົດ:</span>
                                <span id="lbl_net_total" class="font-weight-bold text-primary" style="font-size: 1.2rem;">0 ₭</span>
                            </div>
                            <button type="button" class="btn btn-primary btn-block btn-lg mt-3 font-weight-bold" onclick="submitQuotation()">
                                <i class="fas fa-save"></i> ບັນທຶກໃບສະເໜີລາຄາ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Modal: Select Product Popup -->
<div class="modal fade" id="productSelectModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden; max-height: 92vh; display: flex; flex-direction: column;">
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important; flex-shrink: 0;">
        <h5 class="modal-title font-weight-bold" style="font-size: 1.1rem;">ເລືອກສິນຄ້າ</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8fafc; overflow-y: auto; flex: 1 1 auto;">
        
        <div class="row align-items-center mb-3">
          <div class="col-md-2 col-sm-3 mb-2 mb-md-0">
            <select id="modal_page_size" class="form-control font-weight-bold shadow-sm" onchange="changeModalPageSize(this.value)" style="height: 42px; border-radius: 8px;">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div class="col-md-4 col-sm-4 mb-2 mb-md-0">
            <select id="modal_category_filter" class="form-control font-weight-bold shadow-sm" onchange="filterSimpleModalProducts()" style="height: 42px; border-radius: 8px;">
              <option value="">-- ທຸກໝວດໝູ່ --</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 col-sm-5">
            <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
              </div>
              <input type="text" id="modal_product_search" class="form-control border-left-0 pl-0" placeholder="ຄົ້ນຫາຊື່ສິນຄ້າ, ບາໂຄດ..." autocomplete="off" onkeyup="filterSimpleModalProducts()" style="height: 42px; font-size: 0.95rem;">
            </div>
          </div>
        </div>

        <div id="modal_batch_actions_bar" class="alert alert-info py-2 px-3 mb-3 d-none align-items-center justify-content-between border-0 shadow-sm" style="border-radius: 10px; background-color: #eff6ff; color: #1e40af;">
          <div class="d-flex align-items-center">
            <i class="fas fa-check-circle mr-2 text-primary fa-lg"></i>
            <span class="font-weight-bold" style="font-size: 0.92rem;">
              ເລືອກແລ້ວ <span id="modal_selected_count" class="badge badge-primary font-weight-bold mx-1" style="font-size: 0.88rem; padding: 3px 8px;">0</span> ລາຍການ
            </span>
          </div>
          <button type="button" class="btn btn-primary btn-sm font-weight-bold px-3 shadow-sm" onclick="addSelectedModalProductsToCart()" style="border-radius: 8px; font-size: 0.85rem; height: 34px;">
            <i class="fas fa-plus-circle mr-1"></i> ເພີ່ມລາຍການ
          </button>
        </div>

        <div class="table-responsive bg-white rounded border shadow-sm mb-3" style="overflow-x: auto;">
          <table class="table table-hover mb-0 align-middle text-nowrap">
            <thead class="text-dark font-weight-bold" style="position: sticky; top: -25px; z-index: 10; background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
              <tr>
                <th class="text-center" style="width: 70px;">ລະຫັດ</th>
                <th>ລາຍການສິນຄ້າ</th>
                <th class="text-center">ບາໂຄດ</th>
                <th class="text-center">ສະຕັອກ</th>
                <th class="text-right">ລາຄາຂາຍ</th>
                <th class="text-center" style="width: 100px;">
                  <div class="custom-control custom-checkbox d-inline-flex">
                    <input type="checkbox" class="custom-control-input" id="selectAllModalProducts" onchange="toggleSelectAllModalProducts(this.checked)">
                    <label class="custom-control-label font-weight-bold text-dark" for="selectAllModalProducts" style="cursor: pointer; font-size: 0.85rem; user-select: none;">ເລືອກ</label>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody id="modal_product_list">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex align-items-center justify-content-between mt-2">
            <div class="text-muted font-weight-bold" style="font-size: 0.88rem;">
              ສະແດງ <span id="modal_page_start" class="text-dark">1</span> - <span id="modal_page_end" class="text-dark">10</span> ຈາກທັງໝົດ <span id="modal_page_total" class="text-primary">0</span> ລາຍການ
            </div>
            <ul class="pagination pagination-sm mb-0" id="modal_pagination">
            </ul>
        </div>
        
      </div>
      <div class="modal-footer bg-white border-top py-3 px-4" style="flex-shrink: 0; display: flex; justify-content: space-between;">
        <button type="button" class="btn btn-outline-secondary font-weight-bold px-4" data-dismiss="modal" style="border-radius: 8px;">ປິດ</button>
        <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" onclick="addSelectedModalProductsToCart()" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1"></i> ເພີ່ມລາຍການທີ່ເລືອກ
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Select2 -->
<link href="../../plugins/select2/css/select2.min.css" rel="stylesheet" />
<link href="../../plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="../../plugins/select2/js/select2.full.min.js"></script>

<script>
var PRODUCTS_LIST = <?php echo json_encode($products); ?>;
var cartItems = [];
var activeProduct = null;

// Modal variables
var modalFilteredRows = [];
var modalSelectedIds = new Set();
var modalPageSize = 10;
var modalCurrentPage = 1;

$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4' });
    $('#product_search').focus();
    
    // Init modal list
    modalFilteredRows = PRODUCTS_LIST;
    renderModalProductTable();
});

function updateCustomerName() {
    var name = $('#customer_id option:selected').data('name');
    $('#customer_name').val(name || 'ລູກຄ້າທົ່ວໄປ');
}

function filterSimpleModalProducts() {
    var q = $('#modal_product_search').val().trim().toLowerCase();
    var catId = $('#modal_category_filter').val();
    
    modalFilteredRows = PRODUCTS_LIST.filter(function(p) {
        var matchSearch = true;
        var matchCat = true;
        
        if (q) {
            var searchStr = (p.product_name + ' ' + (p.barcode||'') + ' ' + p.product_id).toLowerCase();
            matchSearch = searchStr.includes(q);
        }
        
        if (catId !== "") {
            matchCat = (p.category_id == catId);
        }
        
        return matchSearch && matchCat;
    });
    modalCurrentPage = 1;
    renderModalProductTable();
}

function changeModalPageSize(size) {
    modalPageSize = parseInt(size, 10) || 10;
    modalCurrentPage = 1;
    renderModalProductTable();
}

function renderModalProductTable() {
    var tbody = $('#modal_product_list');
    tbody.empty();
    
    var totalRows = modalFilteredRows.length;
    var totalPages = Math.ceil(totalRows / modalPageSize) || 1;
    if (modalCurrentPage > totalPages) modalCurrentPage = totalPages;
    if (modalCurrentPage < 1) modalCurrentPage = 1;
    
    var startIdx = (modalCurrentPage - 1) * modalPageSize;
    var endIdx = Math.min(startIdx + modalPageSize, totalRows);
    
    $('#modal_page_start').text(totalRows > 0 ? startIdx + 1 : 0);
    $('#modal_page_end').text(endIdx);
    $('#modal_page_total').text(totalRows);
    
    if (totalRows === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted py-5">ຍັງບໍ່ມີລາຍການສິນຄ້າ</td></tr>');
    } else {
        for (var i = startIdx; i < endIdx; i++) {
            var p = modalFilteredRows[i];
            var isChecked = modalSelectedIds.has(p.product_id);
            var tr = '<tr style="cursor: pointer;" onclick="toggleRowCheckbox(' + p.product_id + ', event)">';
            tr += '<td class="text-center font-weight-bold text-secondary align-middle">' + p.product_id.toString().padStart(4, '0') + '</td>';
            tr += '<td class="align-middle"><span class="font-weight-bold text-dark d-block">' + p.product_name + '</span>';
            if (p.category_name) {
                tr += '<small class="text-muted"><i class="fas fa-folder mr-1"></i> ' + p.category_name + '</small>';
            }
            tr += '</td>';
            tr += '<td class="text-center font-weight-bold align-middle"><span class="badge badge-light text-primary border px-2 py-1"><i class="fas fa-barcode mr-1"></i> ' + (p.barcode || '-') + '</span></td>';
            tr += '<td class="text-center font-weight-bold text-success align-middle">' + p.qty + '</td>';
            tr += '<td class="text-right font-weight-bold text-danger align-middle">' + parseFloat(p.price).toLocaleString() + ' ₭</td>';
            tr += '<td class="text-center align-middle"><div class="custom-control custom-checkbox d-inline-flex" style="pointer-events: none;"><input type="checkbox" class="custom-control-input" id="chk_modal_' + p.product_id + '" ' + (isChecked ? 'checked' : '') + '><label class="custom-control-label" for="chk_modal_' + p.product_id + '"></label></div></td>';
            tr += '</tr>';
            tbody.append(tr);
        }
    }
    
    // Render pagination
    var ul = $('#modal_pagination');
    ul.empty();
    
    var prevDisabled = (modalCurrentPage === 1) ? 'disabled' : '';
    ul.append('<li class="page-item ' + prevDisabled + '"><a class="page-link" href="javascript:void(0)" onclick="goToModalPage(' + (modalCurrentPage - 1) + ')"><i class="fas fa-chevron-left"></i></a></li>');
    
    var startPage = Math.max(1, modalCurrentPage - 2);
    var endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    for (var i = startPage; i <= endPage; i++) {
        var active = (i === modalCurrentPage) ? 'active' : '';
        ul.append('<li class="page-item ' + active + '"><a class="page-link" href="javascript:void(0)" onclick="goToModalPage(' + i + ')">' + i + '</a></li>');
    }
    
    var nextDisabled = (modalCurrentPage === totalPages) ? 'disabled' : '';
    ul.append('<li class="page-item ' + nextDisabled + '"><a class="page-link" href="javascript:void(0)" onclick="goToModalPage(' + (modalCurrentPage + 1) + ')"><i class="fas fa-chevron-right"></i></a></li>');
    
    updateBatchActionsBar();
}

function goToModalPage(page) {
    var totalRows = modalFilteredRows.length;
    var totalPages = Math.ceil(totalRows / modalPageSize) || 1;
    if (page >= 1 && page <= totalPages) {
        modalCurrentPage = page;
        renderModalProductTable();
    }
}

function toggleRowCheckbox(productId, event) {
    if (event.target.tagName.toLowerCase() === 'input') return;
    
    if (modalSelectedIds.has(productId)) {
        modalSelectedIds.delete(productId);
        $('#chk_modal_' + productId).prop('checked', false);
    } else {
        modalSelectedIds.add(productId);
        $('#chk_modal_' + productId).prop('checked', true);
    }
    updateBatchActionsBar();
}

function toggleSelectAllModalProducts(checked) {
    for (var i = 0; i < modalFilteredRows.length; i++) {
        var pid = modalFilteredRows[i].product_id;
        if (checked) {
            modalSelectedIds.add(pid);
            $('#chk_modal_' + pid).prop('checked', true);
        } else {
            modalSelectedIds.delete(pid);
            $('#chk_modal_' + pid).prop('checked', false);
        }
    }
    updateBatchActionsBar();
}

function updateBatchActionsBar() {
    var count = modalSelectedIds.size;
    $('#modal_selected_count').text(count);
    if (count > 0) {
        $('#modal_batch_actions_bar').removeClass('d-none').addClass('d-flex');
    } else {
        $('#modal_batch_actions_bar').addClass('d-none').removeClass('d-flex');
        $('#selectAllModalProducts').prop('checked', false);
    }
}

function addSelectedModalProductsToCart() {
    if (modalSelectedIds.size === 0) return;
    
    modalSelectedIds.forEach(function(pid) {
        var p = PRODUCTS_LIST.find(item => item.product_id == pid);
        if (p) {
            activeProduct = p;
            $('#entry_qty').val(1);
            addCurrentItem(); // this adds to cartItems and resets activeProduct
        }
    });
    
    $('#productSelectModal').modal('hide');
    modalSelectedIds.clear();
    $('#selectAllModalProducts').prop('checked', false);
    updateBatchActionsBar();
    renderModalProductTable(); // refresh checkboxes
    
    Swal.fire({
        icon: 'success',
        title: 'ເພີ່ມລາຍການສຳເລັດ!',
        showConfirmButton: false,
        timer: 1500
    });
}

// Scanner functions removed as requested

function removeCartItem(idx) {
    cartItems.splice(idx, 1);
    updateCartRender();
}

function updateCartItemQty(idx, qty) {
    cartItems[idx].quantity = parseFloat(qty) || 1;
    updateCartRender();
}

function formatNumberInput(el) {
    var val = el.value.replace(/[^0-9]/g, '');
    if (val) {
        el.value = parseInt(val).toLocaleString('en-US');
    } else {
        el.value = '0';
    }
}

function updateCartRender() {
    var tbody = $('#cart_tbody');
    tbody.empty();
    
    if (cartItems.length === 0) {
        tbody.append('<tr id="empty_cart_row"><td colspan="6" class="text-center text-muted py-4">ຍັງບໍ່ມີລາຍການສິນຄ້າ</td></tr>');
        $('#lbl_subtotal').text('0 ₭');
        $('#lbl_net_total').text('0 ₭');
        $('#cart_json_input').val('[]');
        return;
    }
    
    var subtotal = 0;
    cartItems.forEach(function(item, idx) {
        var tot = item.quantity * item.price;
        subtotal += tot;
        
        var tr = `<tr>
            <td class="text-center align-middle">${idx + 1}</td>
            <td class="align-middle font-weight-bold">${item.product_name}</td>
            <td class="align-middle">
                <input type="number" class="form-control form-control-sm text-center" min="1" value="${item.quantity}" onchange="updateCartItemQty(${idx}, this.value)">
            </td>
            <td class="text-right align-middle text-dark font-weight-bold">${item.price.toLocaleString('en-US')} ₭</td>
            <td class="text-right align-middle font-weight-bold text-primary">${tot.toLocaleString('en-US')} ₭</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCartItem(${idx})"><i class="fas fa-trash-alt"></i></button>
            </td>
        </tr>`;
        tbody.append(tr);
    });
    
    var discount = parseFloat($('#input_discount').val().replace(/,/g, '')) || 0;
    var net_total = subtotal - discount;
    
    $('#lbl_subtotal').text(subtotal.toLocaleString('en-US') + ' ₭');
    $('#lbl_net_total').text(net_total.toLocaleString('en-US') + ' ₭');
    
    $('#cart_json_input').val(JSON.stringify(cartItems));
}

function submitQuotation() {
    if (cartItems.length === 0) {
        Swal.fire('ແຈ້ງເຕືອນ', 'ກະລຸນາເພີ່ມລາຍການສິນຄ້າ', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'ຍືນຍັນການບັນທຶກ',
        text: 'ຕ້ອງການບັນທຶກໃບສະເໜີລາຄານີ້ບໍ່?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ບັນທຶກ',
        cancelButtonText: 'ຍົກເລີກ'
    }).then(function(res) {
        if (res.isConfirmed) {
            $('#quotationForm').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
