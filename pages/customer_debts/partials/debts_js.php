<?php
/*
 * ສ່ວນ: JS ຂອງໜ້າ
 * ເປີດລາຍລະອຽດ + pagination, ຮັບຊຳລະ, ຍົກເລີກໜີ້, ເພີ່ມໜີ້ຍົກມາ ແລະ ຄົ້ນຫາລູກຄ້າແບບສົດ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/customer_debts/customer_debts.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
var CDB_URL = '../../api/customer_debts_backend.php';
var CDB_CAN_PAY = <?php echo $canPayDebt ? 'true' : 'false'; ?>;
var CDB_CAN_CANCEL = <?php echo $canCancelDebt ? 'true' : 'false'; ?>;
var CDB_I18N = <?php echo json_encode([
  'unpaid'      => t('customer_debt.status_unpaid', 'ຍັງບໍ່ຊຳລະ'),
  'partial'     => t('customer_debt.status_partial', 'ຊຳລະບາງສ່ວນ'),
  'paid'        => t('customer_debt.status_paid', 'ຊຳລະຄົບ'),
  'cancelled'   => t('customer_debt.status_cancelled', 'ຍົກເລີກ'),
  'overdue'     => t('customer_debt.tab_overdue', 'ກາຍກຳນົດ'),
  'opening'     => t('customer_debt.opening_balance', 'ໜີ້ຍົກມາ'),
  'pay'         => t('customer_debt.btn_pay', 'ຮັບຊຳລະ'),
  'cancel'      => t('customer_debt.btn_cancel_debt', 'ຍົກເລີກໜີ້'),
  'no_payments' => t('customer_debt.no_payments', 'ຍັງບໍ່ມີການຊຳລະ'),
  'confirm_cancel' => t('customer_debt.confirm_cancel', 'ຢືນຢັນຍົກເລີກໜີ້ນີ້?'),
  'yes'         => t('customer_debt.yes', 'ຕົກລົງ'),
  'no'          => t('customer_debt.btn_cancel', 'ຍົກເລີກ'),
  'error'       => t('customer_debt.error_title', 'ຜິດພາດ'),
  'no_customer' => t('customer_debt.no_customer_found', 'ບໍ່ພົບລູກຄ້າ'),
  'no_debts'    => t('customer_debt.empty', 'ບໍ່ມີລາຍການໜີ້'),
  'pager'       => t('debt_report.pager_showing', 'ສະແດງ %s–%s ຈາກ %s ລາຍການ'),
  'owes'        => t('customer_debt.owes', 'ຄ້າງ'),
  'receive'     => t('customer_debt.btn_receive', 'ຮັບເງິນ'),
  'half'        => t('customer_debt.quick_half', 'ເຄິ່ງໜຶ່ງ'),
  'after_left'  => t('customer_debt.after_left', 'ຫຼັງຮັບເງິນນີ້ ຍັງຄ້າງ: %s'),
  'after_clear' => t('customer_debt.after_clear', 'ຮັບເງິນນີ້ແລ້ວ ໜີ້ໝົດ ✓'),
  'after_over'  => t('customer_debt.after_over', 'ເກີນຍອດຄ້າງ %s — ຮັບໄດ້ສູງສຸດ %s'),
  'after_empty' => t('customer_debt.after_empty', 'ປ້ອນຈຳນວນເງິນທີ່ລູກຄ້າຈ່າຍ'),
], JSON_UNESCAPED_UNICODE); ?>;
var __ddCustomerId = 0;

function cdbNum(n) { return Math.round(parseFloat(n) || 0).toLocaleString('en-US'); }
function cdbMoney(n) { return cdbNum(n) + ' ₭'; }
function cdbEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
function cdbDate(s) { if (!s) return '-'; var d = String(s).substr(0, 10).split('-'); return d[2] + '/' + d[1] + '/' + d[0] + (String(s).length > 10 ? ' ' + String(s).substr(11, 5) : ''); }
function cdbFormatMoney(el) { var v = el.value.replace(/[^0-9]/g, ''); el.value = v ? parseInt(v, 10).toLocaleString('en-US') : ''; }
function cdbToast(res) {
  Swal.fire({ icon: res.success ? 'success' : 'error', title: res.success ? res.message : CDB_I18N.error, text: res.success ? '' : res.message });
}

function openDebtDetail(customerId) {
  __ddCustomerId = customerId;
  $.post(CDB_URL, { action: 'debt_detail', customer_id: customerId }, function (res) {
    if (!res.success) return cdbToast(res);
    var today = new Date().toISOString().substr(0, 10);
    var name = res.debts.length ? res.debts[0].customer_name : '';
    $('#ddCustomerName').text(name);
    window.__ddDebts = {};
    res.debts.forEach(function (d) { window.__ddDebts[d.id] = d; });

    // Totals strip (cancelled bills don't count)
    var sumTotal = 0, sumPaid = 0, sumBal = 0;
    res.debts.forEach(function (d) {
      if (d.status === 'cancelled') return;
      sumTotal += parseFloat(d.amount) || 0;
      sumPaid  += parseFloat(d.paid_amount) || 0;
      if (d.status === 'unpaid' || d.status === 'partial') sumBal += parseFloat(d.balance) || 0;
    });
    $('#ddSumTotal').text(cdbMoney(sumTotal));
    $('#ddSumPaid').text(cdbMoney(sumPaid));
    $('#ddSumBalance').text(cdbMoney(sumBal));
    window.__ddPayAll = { customerId: customerId, name: name, balance: sumBal };
    $('#ddPayAllBtn').toggle(CDB_CAN_PAY && sumBal > 0);
    var rows = res.debts.map(function (d) {
      var open = d.status === 'unpaid' || d.status === 'partial';
      var pillCls = { unpaid: 'danger', partial: 'warning', paid: 'success', cancelled: 'muted' }[d.status] || 'muted';
      var overdue = open && d.due_date && d.due_date < today;
      var actions = '';
      if (open && CDB_CAN_PAY) {
        actions += '<button type="button" class="cdb-act cdb-act-icon cdb-act-box cdb-act-pay" title="' + CDB_I18N.receive + '" onclick="openPayDebtBill(' + d.id + ')"><i class="fas fa-money-bill-wave"></i></button>';
      }
      if (CDB_CAN_CANCEL && !d.invoice_number && d.status === 'unpaid') {
        actions += '<button type="button" class="cdb-act cdb-act-icon cdb-act-box cdb-act-cancel" title="' + CDB_I18N.cancel + '" onclick="cancelDebt(' + d.id + ')"><i class="fas fa-ban"></i></button>';
      }
      if (actions) actions = '<div class="cdb-actions">' + actions + '</div>';
      return '<tr' + (d.status === 'cancelled' ? ' style="opacity:.55"' : '') + '>' +
        '<td style="white-space:nowrap">' + cdbDate(d.created_at) + '</td>' +
        '<td>' + (d.invoice_number ? cdbEsc(d.invoice_number) : '<span class="cdb-pill muted">' + CDB_I18N.opening + '</span>') +
          (d.note ? '<div class="cdb-meta">' + cdbEsc(d.note) + '</div>' : '') + '</td>' +
        '<td class="cdb-money">' + cdbMoney(d.bill_amount) + '</td>' +
        '<td class="cdb-money">' + cdbMoney(d.deposit_amount) + '</td>' +
        '<td class="cdb-money">' + cdbMoney(d.amount) + '</td>' +
        '<td class="cdb-money text-success">' + cdbMoney(d.paid_amount) + '</td>' +
        '<td class="cdb-money font-weight-bold ' + (open ? 'text-danger' : '') + '">' + cdbMoney(d.balance) + '</td>' +
        '<td class="text-center" style="white-space:nowrap">' + (d.due_date ? cdbDate(d.due_date) : '-') +
          (overdue ? '<div><span class="cdb-pill warning">' + CDB_I18N.overdue + '</span></div>' : '') + '</td>' +
        '<td class="text-center"><span class="cdb-pill ' + pillCls + '">' + (CDB_I18N[d.status] || d.status) + '</span></td>' +
        '<td class="text-right" style="white-space:nowrap">' + actions + '</td></tr>';
    });
    cdbPagedTable('ddBills', rows, 10, CDB_I18N.no_debts);
    var pays = res.payments.map(function (p) {
      return '<tr><td style="white-space:nowrap">' + cdbDate(p.paid_at) + '</td>' +
        '<td>' + (p.invoice_number ? cdbEsc(p.invoice_number) : '<span class="cdb-pill muted">' + CDB_I18N.opening + '</span>') + '</td>' +
        '<td class="cdb-money font-weight-bold text-success">' + cdbMoney(p.amount) + '</td>' +
        '<td>' + cdbPayMethodHtml(p) + '</td>' +
        '<td>' + cdbEsc(p.note || '') + '</td><td>' + cdbEsc(p.received_by || '') + '</td></tr>';
    });
    cdbPagedTable('ddPayments', pays, 6, CDB_I18N.no_payments);
    $('#debtDetailModal').modal('show');
  }, 'json');
}

// Client-side pages for the detail popup tables: 5 rows a page, and the page buttons are
// always shown (even with a single page) under "ສະແດງ a–b ຈາກ n".
var CDB_DD_PER_PAGE = 5;
var __cdbPaged = {};
function cdbPagedTable(tbodyId, rowsHtml, colspan, emptyText) {
  __cdbPaged[tbodyId] = { rows: rowsHtml, colspan: colspan, empty: emptyText };
  cdbRenderPage(tbodyId, 1);
}
function cdbRenderPage(tbodyId, page) {
  var st = __cdbPaged[tbodyId];
  if (!st) return;
  var total = st.rows.length;
  var pages = Math.max(1, Math.ceil(total / CDB_DD_PER_PAGE));
  page = Math.min(Math.max(1, page), pages);
  var from = (page - 1) * CDB_DD_PER_PAGE;
  $('#' + tbodyId).html(total
    ? st.rows.slice(from, from + CDB_DD_PER_PAGE).join('')
    : '<tr><td colspan="' + st.colspan + '" class="text-center text-muted py-3">' + st.empty + '</td></tr>');

  var li = function (p, label, cls) {
    return '<li class="page-item ' + (cls || '') + '"><a class="page-link" href="javascript:void(0)" data-tbody="' + tbodyId + '" data-page="' + p + '">' + label + '</a></li>';
  };
  var html = li(page - 1, '<i class="fas fa-chevron-left" style="font-size:0.7rem"></i>', page <= 1 ? 'disabled' : '');
  var start = Math.max(1, page - 2), end = Math.min(pages, page + 2);
  if (start > 1) html += li(1, '1') + (start > 2 ? '<li class="page-item disabled"><span class="page-link" style="border:none">…</span></li>' : '');
  for (var p = start; p <= end; p++) html += li(p, p, p === page ? 'active' : '');
  if (end < pages) html += (end < pages - 1 ? '<li class="page-item disabled"><span class="page-link" style="border:none">…</span></li>' : '') + li(pages, pages);
  html += li(page + 1, '<i class="fas fa-chevron-right" style="font-size:0.7rem"></i>', page >= pages ? 'disabled' : '');

  var showing = CDB_I18N.pager.replace('%s', total ? from + 1 : 0).replace('%s', Math.min(from + CDB_DD_PER_PAGE, total)).replace('%s', total);
  $('#' + tbodyId + 'Pager').html('<small>' + showing + '</small><ul class="pagination">' + html + '</ul>');
}
$(document).on('click', '.cdb-modal-pager .page-link[data-page]', function () {
  if ($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;
  cdbRenderPage($(this).data('tbody'), parseInt($(this).data('page'), 10));
});

// Payment history "ຊ່ອງທາງ": 💵 ເງິນສົດ, or the receiving bank's logo + name
function cdbPayMethodHtml(p) {
  if (!p.bank_name && p.payment_method === 'ເງິນສົດ') {
    return '<span class="cdb-paybank text-success"><i class="fas fa-money-bill-wave"></i>' + cdbEsc(p.payment_method) + '</span>';
  }
  var logo = p.bank_logo_url
    ? '<img src="' + cdbEsc(p.bank_logo_url) + '" alt="" onerror="this.style.display=\'none\'">'
    : '<i class="fas fa-university text-primary"></i>';
  var label = p.bank_name ? cdbEsc(p.bank_name) : cdbEsc(p.payment_method);
  return '<span class="cdb-paybank">' + logo + '<b>' + label + '</b></span>';
}

function openPayDebt(customerId, debtId, name, invoice, outstanding) {
  window.__payOutstanding = outstanding;
  $('#payCustomerId').val(customerId);
  $('#payDebtId').val(debtId || 0);
  $('#payCustomerName').text(name || '');
  $('#payInvoiceLbl').text(invoice ? '— ' + invoice : '');
  $('#payOutstanding').text(cdbMoney(outstanding));
  $('#payFifoHint').toggle(!debtId);

  // Quick amounts: half the debt, then round notes smaller than the debt
  var quick = [];
  var half = Math.round(outstanding / 2);
  if (half > 0 && half < outstanding) quick.push([half, CDB_I18N.half + ' (' + cdbNum(half) + ')']);
  [500000, 200000, 100000, 50000, 20000].forEach(function (v) {
    if (v < outstanding && quick.length < 5) quick.push([v, cdbNum(v)]);
  });
  $('#payQuick').html(quick.map(function (q) {
    return '<button type="button" onclick="setPayAmount(' + q[0] + ')">' + q[1] + '</button>';
  }).join(''));

  setPayAmount(outstanding);
  pickPay('cash'); $('#payNote').val('');
  $('#payDebtModal').modal('show');
  setTimeout(function () { $('#payAmount').focus().select(); }, 400);
}

function setPayAmount(v) {
  $('#payAmount').val(cdbNum(v));
  updatePayAfter();
}

// Live line under the amount: what's still owed after this payment. Blocks submit when the
// amount is empty or more than the debt (the server would refuse it anyway).
function updatePayAfter() {
  var amt = parseFloat(($('#payAmount').val() || '').replace(/,/g, '')) || 0;
  var owed = parseFloat(window.__payOutstanding) || 0;
  var left = Math.round((owed - amt) * 100) / 100;
  var $a = $('#payAfter');
  var ok = false;
  if (amt <= 0) {
    $a.text(CDB_I18N.after_empty).css({ background: '#f1f5f9', color: '#64748b' });
  } else if (left < 0) {
    $a.text(CDB_I18N.after_over.replace('%s', cdbMoney(-left)).replace('%s', cdbMoney(owed))).css({ background: '#fef2f2', color: '#b91c1c' });
  } else if (left === 0) {
    $a.text(CDB_I18N.after_clear).css({ background: '#f0fdf4', color: '#15803d' });
    ok = true;
  } else {
    $a.text(CDB_I18N.after_left.replace('%s', cdbMoney(left))).css({ background: '#fffbeb', color: '#b45309' });
    ok = true;
  }
  $('#btnSubmitPay').prop('disabled', !ok);
}

// "ຮັບເງິນ" in the detail popup header: pays across all the customer's bills, oldest first
function openPayDebtAll() {
  var p = window.__ddPayAll;
  if (!p || !(p.balance > 0)) return;
  $('#debtDetailModal').modal('hide');
  openPayDebt(p.customerId, 0, p.name, '', p.balance);
}

function openPayDebtBill(debtId) {
  var d = (window.__ddDebts || {})[debtId];
  if (!d) return;
  $('#debtDetailModal').modal('hide');
  openPayDebt(d.customer_id, d.id, d.customer_name, d.invoice_number || '', parseFloat(d.balance));
}

// One click: 'cash', 'bank' + the bank account it went into, or plain 'transfer' (no banks set up).
function pickPay(kind, bankId) {
  $('#payMethod').val(kind === 'cash' ? 'cash' : 'transfer');
  $('#payBank').val(kind === 'bank' ? bankId : '');
  var $btns = $('.cdb-paybtn').removeClass('active');
  if (kind === 'bank') $btns.filter('[data-bank="' + bankId + '"]').addClass('active');
  else $btns.filter('[data-pay="' + kind + '"]').addClass('active');
}

function submitPayDebt() {
  var btn = $('#btnSubmitPay');
  if (btn.prop('disabled')) return;
  btn.prop('disabled', true);
  $.post(CDB_URL, {
    action: 'pay_debt',
    customer_id: $('#payCustomerId').val(),
    debt_id: $('#payDebtId').val(),
    amount: $('#payAmount').val().replace(/,/g, ''),
    payment_method: $('#payMethod').val(),
    bank_account_id: $('#payMethod').val() === 'transfer' ? $('#payBank').val() : '',
    note: $('#payNote').val()
  }, function (res) {
    cdbToast(res);
    if (res.success) { $('#payDebtModal').modal('hide'); setTimeout(function () { location.reload(); }, 1200); }
  }, 'json').always(function () { btn.prop('disabled', false); });
}

function cancelDebt(debtId) {
  Swal.fire({ icon: 'warning', title: CDB_I18N.confirm_cancel, showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: CDB_I18N.yes, cancelButtonText: CDB_I18N.no })
    .then(function (r) {
      if (!r.isConfirmed) return;
      $.post(CDB_URL, { action: 'cancel_debt', debt_id: debtId }, function (res) {
        cdbToast(res);
        if (res.success) setTimeout(function () { location.reload(); }, 1200);
      }, 'json');
    });
}

function openAddDebt() {
  clearPickedCustomer(true);
  $('#addAmount').val(''); $('#addDueDate').val(''); $('#addNote').val('');
  $('#addDebtModal').modal('show');
}

// ---- Customer picker: type a name / phone / code, pick with click or ↑ ↓ Enter ----
var __custSearchTimer = null, __custSearchSeq = 0, __custResults = [];

function cdbHighlight(text, q) {
  var safe = cdbEsc(text || '');
  if (!q) return safe;
  var i = String(text || '').toLowerCase().indexOf(q.toLowerCase());
  if (i < 0) return safe;
  return cdbEsc(text.substr(0, i)) + '<mark>' + cdbEsc(text.substr(i, q.length)) + '</mark>' + cdbEsc(text.substr(i + q.length));
}

function searchCustomers() {
  var q = $.trim($('#addCustomerSearch').val());
  var seq = ++__custSearchSeq;
  $.post(CDB_URL, { action: 'search_customers', q: q }, function (res) {
    if (seq !== __custSearchSeq || !res.success) return; // a newer keystroke already searched
    __custResults = res.customers;
    if (!__custResults.length) {
      $('#addCustomerResults').html('<div class="cdb-result-empty">' + CDB_I18N.no_customer + '</div>');
      return;
    }
    $('#addCustomerResults').html(__custResults.map(function (c, i) {
      var meta = [c.phone ? '<i class="fas fa-phone-alt"></i> ' + cdbHighlight(c.phone, q) : '', c.customer_code ? cdbHighlight(c.customer_code, q) : '']
        .filter(Boolean).join(' · ');
      var bal = parseFloat(c.balance) > 0 ? '<span class="cdb-pill danger">' + CDB_I18N.owes + ' ' + cdbMoney(c.balance) + '</span>' : '';
      return '<div class="cdb-result' + (i === 0 ? ' active' : '') + '" data-i="' + i + '">' +
        '<div class="min-w-0"><div class="font-weight-bold text-truncate">' + cdbHighlight(c.customer_name, q) + '</div>' +
        '<div class="cdb-meta text-truncate">' + meta + '</div></div>' + bal + '</div>';
    }).join(''));
  }, 'json');
}

function pickCustomer(i) {
  var c = __custResults[i];
  if (!c) return;
  $('#addCustomer').val(c.customer_id);
  $('#addCustomerChosenName').text(c.customer_name);
  $('#addCustomerChosenMeta').text([c.phone, c.customer_code].filter(Boolean).join(' · ') +
    (parseFloat(c.balance) > 0 ? '  —  ' + CDB_I18N.owes + ' ' + cdbMoney(c.balance) : ''));
  $('#addCustomerSearchWrap').hide();
  $('#addCustomerChosen').css('display', 'flex');
  setTimeout(function () { $('#addAmount').focus(); }, 50);
}

function clearPickedCustomer(silent) {
  $('#addCustomer').val('');
  $('#addCustomerChosen').hide();
  $('#addCustomerSearchWrap').show();
  $('#addCustomerSearch').val('');
  $('#addCustomerResults').empty();
  __custResults = [];
  if (!silent) { searchCustomers(); $('#addCustomerSearch').focus(); }
}

function submitAddDebt() {
  $.post(CDB_URL, {
    action: 'add_debt',
    customer_id: $('#addCustomer').val(),
    amount: $('#addAmount').val().replace(/,/g, ''),
    due_date: $('#addDueDate').val(),
    note: $('#addNote').val()
  }, function (res) {
    cdbToast(res);
    if (res.success) { $('#addDebtModal').modal('hide'); setTimeout(function () { location.reload(); }, 1200); }
  }, 'json');
}

$(function () {
  // Enter in the amount box = confirm
  $('#payAmount').on('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); if (!$('#btnSubmitPay').prop('disabled')) submitPayDebt(); }
  });

  // Instant filter of the customer list while typing; Enter still submits the form, which
  // searches the server (bill numbers included).
  $('#cdbSearch').on('input', function () {
    var q = $.trim($(this).val()).toLowerCase();
    var shown = 0;
    $('.cdb-row').each(function () {
      var hit = !q || String($(this).data('search') || '').indexOf(q) !== -1;
      $(this).toggle(hit);
      if (hit) shown++;
    });
    $('#cdbNoMatch').toggle($('.cdb-row').length > 0 && shown === 0);
  });

  $('#addDebtModal').on('shown.bs.modal', function () {
    if (!$('#addCustomer').val()) { searchCustomers(); $('#addCustomerSearch').focus(); }
  });
  $('#addCustomerSearch').on('input', function () {
    clearTimeout(__custSearchTimer);
    __custSearchTimer = setTimeout(searchCustomers, 200);
  }).on('keydown', function (e) {
    var items = $('#addCustomerResults .cdb-result');
    if (!items.length) return;
    var cur = items.index(items.filter('.active'));
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      var next = Math.max(0, Math.min(items.length - 1, cur + (e.key === 'ArrowDown' ? 1 : -1)));
      items.removeClass('active').eq(next).addClass('active')[0].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      e.preventDefault();
      pickCustomer(cur < 0 ? 0 : cur);
    }
  });
  $('#addCustomerResults').on('click', '.cdb-result', function () { pickCustomer($(this).data('i')); });
});
</script>
