<?php
/*
 * ສ່ວນ: JS ຂອງໜ້າ
 * popup ເລືອກສິນຄ້າ (ສະແກນ/ຄົ້ນຫາ), ບັນທຶກວັນໝົດອາຍຸ ແລະ ຢືນຢັນລຶບ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/expiry/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
var EX_PRODUCT_EMPTY = <?php echo json_encode(t('expiry.product_select_placeholder', 'ສະແກນບາໂຄ້ດ ຫຼື ຄົ້ນຫາຊື່ສິນຄ້າ...'), JSON_UNESCAPED_UNICODE); ?>;

// Put the chosen product (from a scan or a row's edit button) into the Select2 box
function exSelectProduct(id, text, expiry) {
  var $sel = $('#exProduct');
  $sel.empty();
  if (id) $sel.append(new Option(text, String(id), true, true));
  $sel.val(id ? String(id) : null).trigger('change.select2');
  if (expiry) miniposSetDate(document.getElementById('exDate'), expiry);
}

function exOpenModal(row) {
  row = row || { product_id: '', expiry_date: '' };
  $('#exScan').val('');
  $('#exScanMsg').text('').removeClass('text-danger text-success');
  if (row.product_id) {
    exSelectProduct(row.product_id, row.text || String(row.product_id), row.expiry_date || '');
  } else {
    exSelectProduct('', '', '');
  }
  miniposSetDate(document.getElementById('exDate'), row.expiry_date || '');
  $('#exModal').modal('show');
}

function exConfirmClear(form) {
  Swal.fire({
    title: <?php echo json_encode(t('expiry.clear_confirm_title', 'ຍົກເລີກການຕິດຕາມວັນໝົດອາຍຸ?'), JSON_UNESCAPED_UNICODE); ?>,
    text: <?php echo json_encode(t('expiry.clear_confirm_text', 'ສິນຄ້ານີ້ຈະບໍ່ແຈ້ງເຕືອນອີກ ຈົນກວ່າຈະກຳນົດວັນໝົດອາຍຸໃໝ່'), JSON_UNESCAPED_UNICODE); ?>,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText: <?php echo json_encode(t('expiry.clear_btn', 'ຍົກເລີກການຕິດຕາມ'), JSON_UNESCAPED_UNICODE); ?>,
    cancelButtonText: <?php echo json_encode(t('expiry.cancel_btn', 'ຍົກເລີກ'), JSON_UNESCAPED_UNICODE); ?>
  }).then(function (r) { if (r.isConfirmed) form.submit(); });
  return false;
}

$(function () {
  var EX_I18N = <?php echo json_encode([
    'not_found'  => t('expiry.scan_not_found', 'ບໍ່ພົບສິນຄ້າບາໂຄ້ດນີ້: %s'),
    'found'      => t('expiry.scan_found', 'ເລືອກແລ້ວ: %s'),
    'need'       => t('expiry.scan_required', 'ກະລຸນາສະແກນສິນຄ້າກ່ອນ'),
  ], JSON_UNESCAPED_UNICODE); ?>;

  // After a product is chosen, jump straight to the date and open the calendar
  function exFocusDate() {
    var el = document.getElementById('exDate');
    var fp = el && el._flatpickr;
    setTimeout(function () { if (fp) { fp.altInput.focus(); fp.open(); } else if (el) el.focus(); }, 50);
  }

  // Look a scanned/typed code up and select that product. `quiet` = auto-lookup while typing:
  // a miss just waits for more input instead of showing "not found".
  var exLookupSeq = 0;
  function exLookupCode(code, quiet) {
    code = $.trim(code || '');
    if (!code) return;
    var seq = ++exLookupSeq;
    var $msg = $('#exScanMsg');
    $.getJSON('index.php', { ex_ajax: 'barcode', code: code }, function (d) {
      if (seq !== exLookupSeq) return;   // a newer scan superseded this one
      if (d && d.found) {
        exSelectProduct(d.id, d.text, d.expiry);
        $msg.text(EX_I18N.found.replace('%s', d.text)).removeClass('text-danger').addClass('text-success');
        $('#exScan').val('');   // scan: pick the product only — don't pop the calendar open
      } else if (!quiet) {
        if (window.hasNonEnglishChars && window.hasNonEnglishChars(code) && window.showSwitchKeyboardAlert) {
          window.showSwitchKeyboardAlert(code, document.getElementById('exScan'));
          return;
        }
        $msg.text(EX_I18N.not_found.replace('%s', code)).removeClass('text-success').addClass('text-danger');
        $('#exScan').select();
      }
    });
  }

  // Enter (most scanners send it) -> look up right away
  var exScanTimer = null;
  $('#exScan').on('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    clearTimeout(exScanTimer);
    exLookupCode(this.value, false);
  });
  // Scanners that don't send Enter: once input stops for a moment, look the code up by itself
  $('#exScan').on('input', function () {
    clearTimeout(exScanTimer);
    var v = $.trim(this.value);
    if (v.length < 4) return;
    exScanTimer = setTimeout(function () { exLookupCode(v, true); }, 300);
  });
  // Product search by name / code — the way to pick a product that has no barcode
  $('#exProduct').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#exModal'),
    placeholder: EX_PRODUCT_EMPTY,
    allowClear: true,
    minimumInputLength: 0,
    ajax: {
      url: 'index.php',
      dataType: 'json',
      delay: 250,
      data: function (p) { return { ex_ajax: 'search', q: p.term || '', page: p.page || 1 }; },
      // Barcode scanned straight into the search box -> pick the exact match immediately
      processResults: function (data, params) {
        var term = $.trim((params && params.term) || '');
        var hit = null;
        if (term) {
          (data.results || []).forEach(function (r) { if (!hit && r.barcode && r.barcode === term) hit = r; });
        }
        if (hit) {
          setTimeout(function () {
            exSelectProduct(hit.id, hit.text, hit.expiry);
            $('#exProduct').select2('close');
            $('#exScanMsg').text(EX_I18N.found.replace('%s', hit.text)).removeClass('text-danger').addClass('text-success');
          }, 0);
        }
        return data;
      }
    }
  }).on('select2:select', function (e) {
    var d = e.params.data || {};
    if (d.expiry) miniposSetDate(document.getElementById('exDate'), d.expiry);
    $('#exScanMsg').text('').removeClass('text-danger text-success');
    exFocusDate();
  });

  // No product scanned yet -> don't submit, send the cashier back to the scan box
  $('#exModal form').on('submit', function (e) {
    if (!$('#exProduct').val()) {
      e.preventDefault();
      $('#exScanMsg').text(EX_I18N.need).removeClass('text-success').addClass('text-danger');
      $('#exScan').trigger('focus');
    }
  });

  // Ready to scan as soon as the modal opens (new entry only; editing keeps the chosen product)
  $('#exModal').on('shown.bs.modal', function () {
    if (!$('#exProduct').val()) $('#exScan').trigger('focus');
  });


  // ຄົ້ນຫາອັດຕະໂນມັດຈະໂຫຼດໜ້າໃໝ່ — ໃຫ້ cursor ກັບມາຢູ່ທ້າຍຊ່ອງຄົ້ນຫາ ເພື່ອພິມຕໍ່ໄດ້
  var sq = document.getElementById('searchQ');
  if (sq && sq.value) { sq.focus(); sq.setSelectionRange(sq.value.length, sq.value.length); }

  var searchTimeout;
  $('#searchQ').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
      $('#expirySearchForm').submit();
    }, 500);
  });
});
</script>
