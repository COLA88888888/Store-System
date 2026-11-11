<!-- Component: Customer JavaScript Logic -->
<script>
  var I18N_CUSTOMERS = <?php echo tjson([
      'customers.page_prev' => 'ໜ້າກ່ອນໜ້າ',
      'customers.page_next' => 'ໜ້າຖັດໄປ',
      'customers.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
      'customers.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບຂໍ້ມູນລູກຄ້າ "%s" ແທ້ຫຼືບໍ່?',
      'customers.delete_confirm_btn' => '<i class="fas fa-trash-alt mr-1"></i> ລົບເລີຍ',
      'customers.cancel' => 'ຍົກເລີກ',
      'customers.delete_success' => 'ລົບຂໍ້ມູນລູກຄ້າສຳເລັດ!',
      'customers.error_title' => 'ຜິດພາດ',
      'customers.error_generic' => 'ຜິດພາດ!',
  ]); ?>;

  var NEXT_CUST_CODE = '<?php echo $next_cust_code; ?>';
  // ລະຫັດຕໍ່ໄປ ແຍກຕາມສາຂາ { store_id: "CUST-2-0001" } — ປ່ຽນຕາມສາຂາທີ່ເລືອກໃນຟອມ
  var NEXT_CUST_CODES = <?php echo json_encode($next_cust_codes ?? new stdClass()); ?>;
  var currentPage = 1;
  var pageSize = 10;
  var filteredRows = [];

  function refreshNextCustomerCode() {
    var sid = $('#add_store_id').val();
    $('#add_customer_code').val((sid && NEXT_CUST_CODES[sid]) ? NEXT_CUST_CODES[sid] : NEXT_CUST_CODE);
  }
  $('#addCustomerModal').on('show.bs.modal', refreshNextCustomerCode);
  $(document).on('change', '#add_store_id', refreshNextCustomerCode);

  function openEditCustomerModal(cust) {
    $('#edit_customer_id').val(cust.customer_id);
    $('#edit_customer_code').val(cust.customer_code);
    $('#edit_customer_name').val(cust.customer_name);
    $('#edit_phone').val(cust.phone);
    $('#edit_member_card').val(cust.member_card || '');
    $('#edit_address').val(cust.address || '');
    $('#edit_notes').val(cust.notes || '');
    if (document.getElementById('edit_store_id') && cust.store_id) {
      $('#edit_store_id').val(cust.store_id);
    }
    $('#editCustomerModal').modal('show');
  }

  function refreshCustomerTable() {
    var url = 'customers.php?fetch_table=1';
    var storeVal = $('select[name="store_id"]').val();
    if (storeVal) url += '&store_id=' + storeVal;
    
    $.get(url, function(html) {
      var $newContent = $(html);
      var $tableContainer = $('.card-body.p-0');
      if ($tableContainer.length) {
        $tableContainer.replaceWith($newContent);
        initPagination();
      }
    });
  }

  // ====== Pagination & Search Filter Logic ======
  function initPagination() {
    var rows = Array.from(document.querySelectorAll('.cust-row'));
    filteredRows = rows;
    currentPage = 1;
    applyPagination();
  }

  function changePageSize() {
    var val = document.getElementById('pageSizeSelect').value;
    if (val === 'all') {
      pageSize = filteredRows.length > 0 ? filteredRows.length : 99999;
    } else {
      pageSize = parseInt(val, 10);
    }
    currentPage = 1;
    applyPagination();
  }

  function goToPage(page) {
    var totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentPage = page;
    applyPagination();
  }

  function applyPagination() {
    var allRows = document.querySelectorAll('.cust-row');
    allRows.forEach(function(r) { r.style.display = 'none'; });

    var total = filteredRows.length;
    var noDataRow = document.getElementById('noCustomerDataRow');
    if (noDataRow) {
      noDataRow.style.display = (total === 0) ? '' : 'none';
    }

    var totalPages = Math.ceil(total / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;

    var startIdx = (currentPage - 1) * pageSize;
    var endIdx = (pageSize >= 99999) ? total : startIdx + pageSize;

    for (var i = startIdx; i < endIdx && i < total; i++) {
      if (filteredRows[i]) {
        filteredRows[i].style.display = '';
      }
    }

    var startDisplay = total === 0 ? 0 : startIdx + 1;
    var endDisplay   = Math.min(endIdx, total);
    
    var startEl = document.getElementById('page_info_start');
    var endEl   = document.getElementById('page_info_end');
    var totalEl = document.getElementById('page_info_total');
    if (startEl) startEl.textContent = startDisplay;
    if (endEl)   endEl.textContent = endDisplay;
    if (totalEl) totalEl.textContent = total;

    renderPaginationControls(totalPages);
  }

  function renderPaginationControls(totalPages) {
    var paginationUl = document.getElementById('customerPagination');
    if (!paginationUl) return;
    paginationUl.innerHTML = '';

    if (totalPages < 1) totalPages = 1;

    // 1. Previous button
    var prevLi = document.createElement('li');
    prevLi.className = 'page-item ' + (currentPage <= 1 ? 'disabled' : '');
    prevLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (currentPage > 1 ? 'onclick="goToPage(' + (currentPage - 1) + ')"' : '') + ' title="' + I18N_CUSTOMERS['customers.page_prev'] + '"><i class="fas fa-chevron-left"></i></a>';
    paginationUl.appendChild(prevLi);

    // 2. Page numbers
    var maxButtons = 5;
    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
      startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
      var firstLi = document.createElement('li');
      firstLi.className = 'page-item';
      firstLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a>';
      paginationUl.appendChild(firstLi);

      if (startPage > 2) {
        var dotLi = document.createElement('li');
        dotLi.className = 'page-item disabled';
        dotLi.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
        paginationUl.appendChild(dotLi);
      }
    }

    for (var p = startPage; p <= endPage; p++) {
      var pageLi = document.createElement('li');
      pageLi.className = 'page-item ' + (p === currentPage ? 'active' : '');
      pageLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPage(' + p + ')">' + p + '</a>';
      paginationUl.appendChild(pageLi);
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        var dotLi2 = document.createElement('li');
        dotLi2.className = 'page-item disabled';
        dotLi2.innerHTML = '<span class="page-link" style="border:none; background:transparent;">...</span>';
        paginationUl.appendChild(dotLi2);
      }

      var lastLi = document.createElement('li');
      lastLi.className = 'page-item';
      lastLi.innerHTML = '<a class="page-link" href="javascript:void(0)" onclick="goToPage(' + totalPages + ')">' + totalPages + '</a>';
      paginationUl.appendChild(lastLi);
    }

    // 3. Next button
    var nextLi = document.createElement('li');
    nextLi.className = 'page-item ' + (currentPage >= totalPages ? 'disabled' : '');
    nextLi.innerHTML = '<a class="page-link" href="javascript:void(0)" ' + (currentPage < totalPages ? 'onclick="goToPage(' + (currentPage + 1) + ')"' : '') + ' title="' + I18N_CUSTOMERS['customers.page_next'] + '"><i class="fas fa-chevron-right"></i></a>';
    paginationUl.appendChild(nextLi);
  }

  function filterCustomerTable() {
    var search = document.getElementById('customerSearchInput').value.toLowerCase().trim();
    var fromDate = document.getElementById('fromDateInput') ? document.getElementById('fromDateInput').value : '';
    var toDate = document.getElementById('toDateInput') ? document.getElementById('toDateInput').value : '';
    var rows = document.querySelectorAll('.cust-row');
    filteredRows = [];

    rows.forEach(function(row) {
      var searchAttr = (row.getAttribute('data-search') || '').toLowerCase();
      var textContent = row.textContent.toLowerCase();
      var rowDate = row.getAttribute('data-date') || '';

      var matchesSearch = (search === '' || searchAttr.includes(search) || textContent.includes(search));
      var matchesDate = true;

      if (fromDate && rowDate) {
        if (rowDate < fromDate) matchesDate = false;
      }
      if (toDate && rowDate) {
        if (rowDate > toDate) matchesDate = false;
      }

      if (matchesSearch && matchesDate) {
        filteredRows.push(row);
      }
    });

    currentPage = 1;
    applyPagination();
  }

  $(document).ready(function() {
    initPagination();

    $('#customerSearchInput, #fromDateInput, #toDateInput').on('input keyup change search clear paste', function() {
      filterCustomerTable();
    });
  });

  function confirmDeleteCustomer(id, name) {
    Swal.fire({
      title: I18N_CUSTOMERS['customers.confirm_delete_title'],
      text: I18N_CUSTOMERS['customers.confirm_delete_text'].replace('%s', name),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: I18N_CUSTOMERS['customers.delete_confirm_btn'],
      cancelButtonText: I18N_CUSTOMERS['customers.cancel'],
      heightAuto: false
    }).then(function(result) {
      if (result.isConfirmed) {
        $.post('customers.php', { action: 'delete_customer', customer_id: id, is_ajax: 1 }, function(res) {
          if (res.success) {
            $('tr.cust-row').filter(function() {
              return $(this).find('button[onclick*="' + id + '"]').length > 0;
            }).fadeOut(300, function() { $(this).remove(); initPagination(); });
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: I18N_CUSTOMERS['customers.delete_success'],
              showConfirmButton: false,
              timer: 1500
            });
          } else {
            Swal.fire({ icon: 'error', title: I18N_CUSTOMERS['customers.error_title'], text: res.message || I18N_CUSTOMERS['customers.error_generic'] });
          }
        }, 'json');
      }
    });
  }
</script>
