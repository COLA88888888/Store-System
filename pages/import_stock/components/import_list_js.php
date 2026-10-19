<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * JS ຂອງໜ້າລາຍການນຳເຂົ້າ: ຕາຕະລາງປະຫວັດ, ສົ່ງອອກ Excel, popup ບິນ/ແກ້ໄຂ/ລົບ ແລະ popup ເລືອກສິນຄ້າ.
 *   - import_list/history_table.php : ຕາຕະລາງປະຫວັດ ແລະ Excel
 *   - import_list/modal_products.php : Popup ເລືອກສິນຄ້າ
 */
// Component: JavaScript Logic for Import List (import_list.php)
?>
<script>
<?php $__idc = getDisplayCurrency($pdo); ?>
window.IMP_CUR = { code: <?php echo json_encode($__idc['code']); ?>, symbol: <?php echo json_encode($__idc['symbol'], JSON_UNESCAPED_UNICODE); ?>, decimals: <?php echo (int)$__idc['decimals']; ?>, rate: <?php echo json_encode((float)$__idc['rate']); ?>, isKip: <?php echo ($__idc['code']==='KIP')?'true':'false'; ?> };
function impFmtFromKip(n){ n=parseFloat(n)||0; var v=IMP_CUR.isKip?Math.round(n):(n/IMP_CUR.rate); return v.toLocaleString('en-US',{minimumFractionDigits:IMP_CUR.decimals,maximumFractionDigits:IMP_CUR.decimals}) + ' <span class="cur-sym">' + IMP_CUR.symbol + '</span>'; }
  var I18N_IMPORT_LIST = <?php echo tjson([
      'import_list.default_unit' => 'ອັນ',
      'import_list.js_no_items_in_bill' => 'ບໍ່ມີລາຍການສິນຄ້າໃນໃບບິນນີ້',
      'import_list.js_cannot_edit_moved' => 'ບໍ່ສາມາດແກ້ໄຂໄດ້ (ສິນຄ້າເຄື່ອນໄຫວແລ້ວ)',
      'import_list.js_edit' => 'ແກ້ໄຂ',
      'import_list.js_delete' => 'ລົບ',
      'import_list.js_cancel_import_title' => 'ຍົກເລີກການຮັບສິນຄ້າເຂົ້າ?',
      'import_list.js_cancel_import_text' => 'ທ່ານຕ້ອງການຍົກເລີກໃບບິນທີ [%s] ສິນຄ້າ (%s) ແທ້ບໍ? ສະຕັອກຈະຖືກປັບຫຼຸດລົງຄືນ!',
      'import_list.js_btn_delete_bill' => 'ລົບບິນນີ້',
      'import_list.js_delete_bill_title' => 'ລົບໃບບິນຮັບເຂົ້າສິນຄ້າ?',
      'import_list.js_delete_bill_text' => 'ທ່ານຕ້ອງການລົບໃບບິນທີ [%s] ທັງໝົດແທ້ບໍ? ສິນຄ້າທັງໝົດໃນໃບບິນນີ້ຈະຖືກປັບຫຼຸດສະຕັອກຄືນ!',
      'import_list.modal_btn_cancel' => 'ຍົກເລີກ',
      'import_list.js_export_name' => 'ລາຍການສິນຄ້າຮັບເຂົ້າ',
  ]); ?>;

  var PRODUCTS_LIST = <?php echo json_encode($products); ?>;
  var PRODUCTS_UNITS = <?php echo json_encode($product_units_map); ?>;
  var BILL_ITEMS_MAP = <?php echo json_encode($billItemsMap); ?>;
  
  var currentPage = 1;
  var pageSize = 10;
  var filteredRows = [];

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  $(document).ready(function() {
    filterImportHistory();

    $(document).on('click', '.btn-view-bill', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var invoice = $(this).data('invoice');
      var supplier = $(this).data('supplier');
      var date = $(this).data('date');
      var cost = parseFloat($(this).data('cost') || 0);
      openBillDetailsModal(id, invoice, supplier, date, cost);
    });

    $(document).on('click', '.btn-edit-master-bill', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var invoice = $(this).data('invoice');
      var supplier = $(this).data('supplier');
      var date = $(this).data('date');
      var notes = $(this).data('notes');

      $('#edit_master_import_id').val(id);
      $('#edit_master_invoice_label').text(invoice);
      $('#edit_master_supplier').val(supplier || '');
      $('#edit_master_date').val(date || '');
      $('#edit_master_notes').val(notes || '');

      $('#editMasterBillModal').modal('show');
    });

    $(document).on('click', '.btn-delete-master-bill', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var invoice = $(this).data('invoice');
      confirmDeleteMasterBill(id, invoice);
    });

    $(document).on('click', '.btn-edit-import, .btn-edit-import-detail', function(e) {
      e.preventDefault();
      var rawJson = $(this).attr('data-json') || $(this).data('json');
      var rowObj = null;
      if (typeof rawJson === 'object') {
        rowObj = rawJson;
      } else {
        try { rowObj = JSON.parse(rawJson); } catch(err) { console.error(err); }
      }
      if (rowObj) {
        if (!rowObj.invoice_number) {
            rowObj.invoice_number = $(this).data('invoice'); // Fallback if missing
        }
        openEditImportModal(rowObj);
      }
    });

    $(document).on('click', '.btn-delete-import, .btn-delete-import-detail', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var invoice = $(this).data('invoice');
      var name = $(this).data('name');
      confirmDeleteImport(id, invoice, name);
    });
  });

<?php require __DIR__ . '/import_list/history_table.php'; /* ຕາຕະລາງປະຫວັດ ແລະ Excel */ ?>
  function openBillDetailsModal(importId, invoiceNo, supplier, importDate, totalCost) {
    currentModalImportId = importId;
    $('#modal_invoice_no').text(invoiceNo);
    $('#modal_supplier').text(supplier || '-');
    $('#modal_import_date').text(importDate);
    $('#modal_total_cost').html(impFmtFromKip(totalCost));

    var items = BILL_ITEMS_MAP[importId] || [];
    var tbody = $('#bill_items_modal_tbody');
    tbody.empty();

    if (items.length === 0) {
      tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">' + I18N_IMPORT_LIST['import_list.js_no_items_in_bill'] + '</td></tr>');
    } else {
      items.forEach(function(item, idx) {
        var actionCol = '';
        if (parseInt(item.has_movement) === 1) {
          actionCol = '<i class="fas fa-lock text-muted" title="' + I18N_IMPORT_LIST['import_list.js_cannot_edit_moved'] + '"></i>';
        } else {
          var safeJson = JSON.stringify(item).replace(/'/g, "&apos;");
          actionCol = `
            <button class="btn btn-sm btn-outline-warning rounded-circle px-2 py-1 mr-1 btn-edit-import-detail" data-invoice="${invoiceNo}" data-json='${safeJson}' title="${I18N_IMPORT_LIST['import_list.js_edit']}"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-outline-danger rounded-circle px-2 py-1 btn-delete-import-detail" data-id="${item.import_detail_id}" data-invoice="${invoiceNo}" data-name="${escapeHtml(item.product_name)}" title="${I18N_IMPORT_LIST['import_list.js_delete']}"><i class="fas fa-trash"></i></button>
          `;
        }

        tbody.append(`
          <tr>
            <td class="text-center font-weight-bold text-muted">${idx + 1}</td>
            <td class="align-middle">
              <span class="font-weight-bold text-dark d-block">${escapeHtml(item.product_name)}</span>
              ${item.barcode ? '<small class="text-muted"><i class="fas fa-barcode mr-1"></i> ' + escapeHtml(item.barcode) + '</small>' : ''}
            </td>
            <td class="text-center align-middle font-weight-bold text-dark">${item.quantity.toLocaleString()} ${escapeHtml(item.unit_name)}</td>
            <td class="text-right align-middle font-weight-bold text-dark">${impFmtFromKip(item.cost_price)}</td>
            <td class="text-right align-middle font-weight-bold text-primary">${impFmtFromKip(item.total_cost)}</td>
            <td class="text-center align-middle">${actionCol}</td>
          </tr>
        `);
      });
    }

    $('#billDetailsModal').modal('show');
  }

  function printCurrentModalBill() {
    if (currentModalImportId > 0) {
      window.open('print_import.php?import_id=' + currentModalImportId, '_blank');
    }
  }

  function openEditImportModal(row) {
    $('#billDetailsModal').modal('hide'); // Fix z-index stacking by hiding the background modal

    $('#edit_import_detail_id').val(row.import_detail_id);
    $('#edit_invoice_label').text(row.invoice_number);
    $('#edit_product_name_label').text(row.product_name);
    $('#edit_supplier_name').val(row.supplier_name || '');
    $('#edit_quantity').val(row.quantity);
    $('#edit_cost_price').val(row.cost_price);
    $('#edit_expiry_date').val(row.expiry_date || '');
    $('#edit_notes').val(row.notes || '');

    var unitSelect = $('#edit_unit_key');
    unitSelect.empty();

    var baseUnit = row.base_unit || I18N_IMPORT_LIST['import_list.default_unit'];
    unitSelect.append(`<option value="base">${escapeHtml(baseUnit)} (1)</option>`);

    var productId = row.product_id;
    if (PRODUCTS_UNITS[productId]) {
      PRODUCTS_UNITS[productId].forEach(function(u) {
        var isSel = (row.unit_name === u.unit_name && parseInt(row.multiplier) === parseInt(u.multiplier)) ? 'selected' : '';
        unitSelect.append(`<option value="${u.id}" ${isSel}>${escapeHtml(u.unit_name)} (${u.multiplier})</option>`);
      });
    }

    $('#editImportModal').modal('show');
  }

  function confirmDeleteImport(importDetailId, invoiceNumber, productName) {
    Swal.fire({
      title: I18N_IMPORT_LIST['import_list.js_cancel_import_title'],
      text: I18N_IMPORT_LIST['import_list.js_cancel_import_text'].replace('%s', invoiceNumber).replace('%s', productName),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_IMPORT_LIST['import_list.js_btn_delete_bill'],
      cancelButtonText: I18N_IMPORT_LIST['import_list.modal_btn_cancel']
    }).then((result) => {
      if (result.isConfirmed) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_import_detail';
        form.appendChild(actionInput);

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'import_detail_id';
        idInput.value = importDetailId;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  function confirmDeleteMasterBill(importId, invoiceNumber) {
    Swal.fire({
      title: I18N_IMPORT_LIST['import_list.js_delete_bill_title'],
      text: I18N_IMPORT_LIST['import_list.js_delete_bill_text'].replace('%s', invoiceNumber),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_IMPORT_LIST['import_list.js_btn_delete_bill'],
      cancelButtonText: I18N_IMPORT_LIST['import_list.modal_btn_cancel']
    }).then((result) => {
      if (result.isConfirmed) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_master_import';
        form.appendChild(actionInput);

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'import_id';
        idInput.value = importId;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
      }
    });
  }

<?php require __DIR__ . '/import_list/modal_products.php'; /* Popup ເລືອກສິນຄ້າ */ ?>
</script>
