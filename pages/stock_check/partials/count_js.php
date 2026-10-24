<?php
/*
 * ສ່ວນ: JS ໜ້ານັບສິນຄ້າ
 * - ຄິດໄລ່ ແລະ ສະແດງຜົນ ຄົບ / ບໍ່ພໍ / ເກີນ ແລະ ສ່ວນຕ່າງ ແບບ Real-time ຂະນະນັບ
 * - ສະແກນບາໂຄ້ດ (ບວກ 1 ອັດຕະໂນມັດ ຫຼື ໂຟກັສຊ່ອງ)
 * - ກັ່ນຕອງ (ທັງໝົດ, ຄົບ, ບໍ່ພໍ, ເກີນ, ຍັງບໍ່ນັບ), ແບ່ງໜ້າ, ບັນທຶກ (Ctrl+S)
 * - ສົ່ງຂໍອະນຸມັດ ແລະ ປັບສະຕັອກ
 */
?>
<script>
var SC_CHECK_ID     = <?php echo (int)$scCheck['id']; ?>;
var SC_STATUS       = <?php echo json_encode($scCheck['status']); ?>;
var SC_EDITABLE     = <?php echo $scEditable ? 'true' : 'false'; ?>;
var SC_DONE         = <?php echo $scCheck['status'] === 'completed' ? 'true' : 'false'; ?>;
var SC_SHOW_RESULTS = <?php echo !empty($scShowResults) ? 'true' : 'false'; ?>;
var SC_SHOW_SYSTEM  = <?php echo !empty($scShowSystem) ? 'true' : 'false'; ?>;
var SC_SHOW_DIFF    = <?php echo !empty($scShowDiff) ? 'true' : 'false'; ?>;
var SC_TOTAL_ITEMS  = <?php echo (int)$scItemTotal; ?>;
var SC_CHECK_NO     = <?php echo json_encode($scCheck['check_no']); ?>;
var SC_CREATED      = <?php echo json_encode(date('d/m/Y H:i', strtotime($scCheck['created_at']))); ?>;
var SC_BACKEND      = '../../api/stock_check_backend.php';
var SC_ME           = <?php echo json_encode(trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;

var SC_PER_PAGE = 50;
try {
  var scSavedPer = localStorage.getItem('StoreMini.stock_check_per_page');
  if (scSavedPer) SC_PER_PAGE = scSavedPer === 'all' ? Infinity : (parseInt(scSavedPer, 10) || 50);
} catch (e) {}

var scFilter = 'all', scPage = 1, scDirty = {}, scSaving = false;

function scFmt(n) { return Number(n || 0).toLocaleString('en-US'); }

function scToInt(v) {
  if (v === undefined || v === null || String(v).trim() === '') return null;
  var n = parseInt(v, 10);
  return isNaN(n) ? null : Math.max(0, n);
}

// ຄ່າຕົວຈິງປັດຈຸບັນຂອງແຖວ (ກຳລັງພິມ ຫຼື ທີ່ບັນທຶກແລ້ວ)
function scLive($r) {
  if (!SC_EDITABLE) return scToInt($r.find('.sc-counted-text').attr('data-value'));
  var v = $r.find('.sc-counted').val();
  return scToInt(v);
}

function scSaved($r) { return scToInt($r.attr('data-saved')); }

// ທາສີ ແລະ ອັບເດດສະຖານະແຖວ: ຄົບ (ຂຽວ) / ບໍ່ພໍ (ແດງ) / ເກີນ (ສົ້ມ) / ຍັງບໍ່ນັບ (ເທົາ)
function scPaintRow($r) {
  var counted = scLive($r);
  var isDirty = !!scDirty[$r.attr('data-id')];
  var sys     = parseInt($r.attr('data-system'), 10) || 0;
  var $d      = $r.find('.sc-diff');
  var $v      = $r.find('.sc-diff-value');
  var unit    = $.trim($r.find('.sc-c-unit').text());
  if (unit === '-') unit = '';

  $r.removeClass('sc-state-match sc-state-short sc-state-over sc-state-uncounted sc-state-dirty');
  $v.removeClass('text-danger text-success text-muted text-warning font-weight-bold');

  if (counted === null) {
    $r.attr('data-state', 'uncounted').addClass('sc-state-uncounted');
    $d.html('<span class="badge sc-res sc-res-uncounted"><i class="far fa-clock mr-1"></i>ຍັງບໍ່ນັບ</span>');
    if ($v.length) $v.text('-').addClass('text-muted');
    return;
  }

  // ຖ້າກຳລັງຕີຈຳນວນຕົວຈິງ (ຍັງບໍ່ທັນກົດບັນທຶກ): ບໍ່ໃຫ້ໂຊສ່ວນຕ່າງເທື່ອ!
  if (isDirty) {
    $r.attr('data-state', 'dirty').addClass('sc-state-dirty');
    $d.html('<span class="badge badge-warning text-dark font-weight-bold"><i class="fas fa-pencil-alt mr-1"></i>ກຳລັງປ້ອນ</span>');
    if ($v.length) $v.text('-').addClass('text-muted');
    return;
  }

  // ກົດບັນທຶກແລ້ວ: ຈິ່ງໂຊສ່ວນຕ່າງມາ!
  var diff = counted - sys;
  if (SC_DONE && diff !== 0) {
    $r.attr('data-state', diff < 0 ? 'short' : 'over').addClass('sc-state-match');
    $d.html('<span class="badge sc-res sc-res-match"><i class="fas fa-check mr-1"></i>ຄົບ (ປັບແລ້ວ)</span>');
    if ($v.length) $v.text((diff > 0 ? '+' : '') + scFmt(diff) + (unit ? ' ' + unit : '')).addClass(diff < 0 ? 'text-danger font-weight-bold' : 'text-warning font-weight-bold');
    return;
  }
  if (diff === 0) {
    $r.attr('data-state', 'match').addClass('sc-state-match');
    $d.html('<span class="badge sc-res sc-res-match"><i class="fas fa-check mr-1"></i>ຄົບ</span>');
    if ($v.length) $v.text('0').addClass('text-success font-weight-bold');
  } else if (diff < 0) {
    $r.attr('data-state', 'short').addClass('sc-state-short');
    $d.html('<span class="badge sc-res sc-res-short"><i class="fas fa-arrow-down mr-1"></i>ບໍ່ພໍ</span>');
    if ($v.length) $v.text(scFmt(diff) + (unit ? ' ' + unit : '')).addClass('text-danger font-weight-bold');
  } else {
    $r.attr('data-state', 'over').addClass('sc-state-over');
    $d.html('<span class="badge sc-res sc-res-over"><i class="fas fa-arrow-up mr-1"></i>ເກີນ</span>');
    if ($v.length) $v.text('+' + scFmt(diff) + (unit ? ' ' + unit : '')).addClass('text-warning font-weight-bold');
  }
}

// ຄິດໄລ່ສະຫຼຸບທັງໝົດ
function scStats() {
  var s = { total: 0, counted: 0, uncounted: 0, match: 0, shortN: 0, shortQ: 0, overN: 0, overQ: 0, net: 0 };
  $('.sc-row').each(function() {
    var $r = $(this);
    var c = scLive($r);
    s.total++;
    if (c === null) {
      s.uncounted++;
      return;
    }
    s.counted++;
    var sys = parseInt($r.attr('data-system'), 10) || 0;
    var diff = c - sys;
    s.net += diff;
    if (diff === 0) s.match++;
    else if (diff < 0) { s.shortN++; s.shortQ += Math.abs(diff); }
    else { s.overN++; s.overQ += diff; }
  });
  s.total = Math.max(s.total, SC_TOTAL_ITEMS);
  s.uncounted = s.total - s.counted;
  return s;
}

// ອັບເດດບັດສະຫຼຸບ
function scUpdateStats() {
  var s = scStats();
  $('#scStatTotal').text(scFmt(s.total));
  $('#scStatCountedSummary').text('ນັບແລ້ວ ' + scFmt(s.counted));
  $('#scStatCountedOnly').text(scFmt(s.counted));
  $('#scStatMatch').text(scFmt(s.match));
  $('#scStatShort').text(scFmt(s.shortN));
  $('#scStatShortQty').text(s.shortQ ? 'ຂາດ -' + scFmt(s.shortQ) : '0 ລາຍການ');
  $('#scStatOver').text(scFmt(s.overN));
  $('#scStatOverQty').text(s.overQ ? 'ເກີນ +' + scFmt(s.overQ) : '0 ລາຍການ');
  $('#scStatUncountedBig').text(scFmt(s.uncounted));
  $('#scStatMismatchTotal').text(scFmt(s.shortN + s.overN));
  $('#scStatValue').text((s.net > 0 ? '+' : '') + scFmt(s.net));
  $('#scFilterTotalBadge').text(scFmt(s.total));
  $('#scFilterCountedBadge').text(scFmt(s.counted));
  $('#scFilterMismatchBadge').text(scFmt(s.shortN + s.overN));
  $('#scFilterUncountedBadge').text(scFmt(s.uncounted));
}

// ---------- ກັ່ນຕອງ + ແບ່ງໜ້າ ----------
function scMatches(el) {
  var $r = $(el), st = $r.attr('data-state');
  var isDirty = !!scDirty[$r.attr('data-id')];

  var q = ($('#scScanInput').val() || '').trim().toLowerCase();
  var okText = !q
            || ($r.attr('data-name') || '').indexOf(q) !== -1
            || ($r.attr('data-barcode') || '').indexOf(q) !== -1
            || ($r.attr('data-zone') || '').indexOf(q) !== -1
            || ($r.attr('data-shelf') || '').indexOf(q) !== -1
            || String($r.attr('data-pid')) === q;
  var okState = (scFilter === 'all')
             || (scFilter === 'uncounted' && (st === 'uncounted' || isDirty))
             || (scFilter === 'counted' && st !== 'uncounted' && !isDirty)
             || (scFilter === 'mismatch' && (st === 'short' || st === 'over'))
             || (scFilter === st);
  var zFilter = $('#scZoneFilter').length ? ($('#scZoneFilter').val() || '').trim().toLowerCase() : '';
  var rRawZone = ($r.attr('data-raw-zone') || '').trim().toLowerCase();
  var okZone = !zFilter
            || (zFilter === '__unassigned__' && !rRawZone)
            || rRawZone === zFilter;
  return okText && okState && okZone;
}

function scRender() {
  var rows = $('.sc-row').get(), matched = rows.filter(scMatches);
  var pages = Math.max(1, Math.ceil(matched.length / SC_PER_PAGE));
  scPage = Math.min(Math.max(1, scPage), pages);
  var start = (scPage - 1) * SC_PER_PAGE, end = start + SC_PER_PAGE;
  rows.forEach(function(el) { el.style.display = 'none'; });
  matched.slice(start, end).forEach(function(el) { el.style.display = ''; });

  $('#scNoMatchRow').toggle(matched.length === 0 && rows.length > 0);
  $('#scPagerInfo').text(matched.length ? 'ສະແດງ ' + scFmt(start + 1) + ' - ' + scFmt(Math.min(end, matched.length)) + ' ຈາກທັງໝົດ ' + scFmt(matched.length) + ' ລາຍການ' : 'ທັງໝົດ 0 ລາຍການ');

  var html = '';
  var li = function(p, label, cls) { return '<li class="page-item ' + (cls || '') + '"><a class="page-link" href="#" data-page="' + p + '">' + label + '</a></li>'; };
  html += li(scPage - 1, '<i class="fas fa-chevron-left"></i>', scPage <= 1 ? 'disabled' : '');
  var a = Math.max(1, scPage - 2), b = Math.min(pages, scPage + 2);
  if (a > 1) { html += li(1, '1'); if (a > 2) html += '<li class="page-item disabled"><span class="page-link border-0">…</span></li>'; }
  for (var p = a; p <= b; p++) html += li(p, p, p === scPage ? 'active' : '');
  if (b < pages) { if (b < pages - 1) html += '<li class="page-item disabled"><span class="page-link border-0">…</span></li>'; html += li(pages, pages); }
  html += li(scPage + 1, '<i class="fas fa-chevron-right"></i>', scPage >= pages ? 'disabled' : '');
  $('#scPager').html(html);
  return matched;
}

function scGoToRow($r) {
  var matched = $('.sc-row').get().filter(scMatches);
  var idx = matched.indexOf($r.get(0));
  if (idx === -1) {
    scFilter = 'all';
    $('.sc-filter .btn').removeClass('active').filter('[data-filter="all"]').addClass('active');
    matched = $('.sc-row').get().filter(scMatches);
    idx = matched.indexOf($r.get(0));
  }
  scPage = Math.floor(Math.max(0, idx) / SC_PER_PAGE) + 1;
  scRender();
}

function scFlash($r) {
  $r.addClass('sc-flash');
  setTimeout(function() { $r.removeClass('sc-flash'); }, 700);
  var el = $r.get(0);
  if (el && el.scrollIntoView) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
}

function scBeep() {
  try {
    var ctx = new (window.AudioContext || window.webkitAudioContext)();
    var osc = ctx.createOscillator(), gain = ctx.createGain();
    osc.frequency.value = 1200; gain.gain.value = 0.15;
    osc.connect(gain); gain.connect(ctx.destination);
    osc.start(); osc.stop(ctx.currentTime + 0.08);
  } catch (e) {}
}

function scRefreshDirtyUi() {
  var n = Object.keys(scDirty).length;
  $('#scUnsavedBadge').text(n).toggle(n > 0);
  $('#scSaveStatus').html(n > 0 ? '<span class="text-warning"><i class="fas fa-exclamation-circle mr-1"></i>ມີ ' + n + ' ລາຍການຍັງບໍ່ບັນທຶກ</span>' : '');
}

function scMarkDirty($r) {
  scDirty[$r.attr('data-id')] = true;
  scRefreshDirtyUi();
}

function scStampCountedBy($r) {
  var $c = $r.find('.sc-counted-by');
  if (scLive($r) === null) { $c.html('<span class="text-muted">-</span>'); return; }
  var d = new Date(), p = function(n) { return (n < 10 ? '0' : '') + n; };
  $c.html('<i class="fas fa-check-circle text-success mr-1"></i>' + p(d.getDate()) + '/' + p(d.getMonth() + 1) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) +
          '<div class="text-muted">' + $('<div>').text(SC_ME || '-').html() + '</div>');
}

function scRowPayload($r) {
  var live = scLive($r);
  var val = (live === null) ? '' : live;
  return {
    id: parseInt($r.attr('data-id'), 10),
    counted_qty: val,
    set: val,
    note: $r.find('.sc-note').val() || ''
  };
}

function scApplySavedRow(r) {
  var $r = $('.sc-row[data-id="' + r.id + '"]');
  if (!$r.length) return;
  var v = (r.counted_qty === null) ? '' : r.counted_qty;
  $r.attr('data-saved', v);
  if (r.system_qty !== undefined) {
    $r.attr('data-system', r.system_qty);
    $r.find('.sc-system-qty').text(scFmt(r.system_qty));
  }
  $r.find('.sc-counted').val(v);
  $r.find('.sc-counted-by').html(r.counted_qty === null || !r.counted_at ? '<span class="text-muted">-</span>'
    : '<i class="fas fa-check-circle text-success mr-1"></i>' + r.counted_at + '<div class="text-muted">' + $('<div>').text(r.counted_by_name || '-').html() + '</div>');
  delete scDirty[r.id];
  scPaintRow($r);
}

// ສະແດງບັດລາຍການທີ່ສະແກນລ່າສຸດ
function scShowJustScannedBox($hit, qty) {
  if (!$hit || !$hit.length) return;
  var name = $.trim($hit.find('.sc-c-name > div:first-child').text() || $hit.find('.sc-c-name').text());
  var barcode = $.trim($hit.find('.sc-c-barcode').text());
  var unit = $.trim($hit.find('.sc-c-unit').text());
  if (unit === '-') unit = '';
  var loc = $.trim($hit.find('.sc-c-name .badge').text().replace(/\s+/g, ' '));
  var id = $hit.attr('data-id');
  var sysQty = parseInt($hit.attr('data-system'), 10);

  $('#scJustScannedId').val(id);
  $('#scJustScannedName').text(name);
  $('#scJustScannedBarcode').text(barcode || '-');
  $('#scJustScannedUnit').text(unit);
  $('#scJustScannedLoc').text(loc || 'ບໍ່ລະບຸ');
  $('#scJustScannedQty').val(qty === null ? 0 : qty);

  if (SC_SHOW_SYSTEM && !isNaN(sysQty)) {
    $('#scJustScannedSysBox').show();
    $('#scJustScannedSysQty').text(scFmt(sysQty));
  } else {
    $('#scJustScannedSysBox').hide();
  }

  $('#scJustScannedBox').slideDown(150);
}

function scStepScannedQty(delta) {
  var id = $('#scJustScannedId').val();
  if (!id) return;
  var $r = $('.sc-row[data-id="' + id + '"]');
  if (!$r.length) return;
  var cur = scLive($r);
  var next = Math.max(0, (cur === null ? 0 : cur) + delta);
  $r.find('.sc-counted').val(next);
  $('#scJustScannedQty').val(next);
  scStampCountedBy($r);
  scMarkDirty($r);
  scPaintRow($r);
  scUpdateStats();
}

function scSetScannedQty(val) {
  var id = $('#scJustScannedId').val();
  if (!id) return;
  var $r = $('.sc-row[data-id="' + id + '"]');
  if (!$r.length) return;
  var clean = String(val).replace(/[^0-9]/g, '');
  var next = clean === '' ? null : parseInt(clean, 10);
  $r.find('.sc-counted').val(next === null ? '' : next);
  scStampCountedBy($r);
  scMarkDirty($r);
  scPaintRow($r);
  scUpdateStats();
}

// ບັນທຶກອັດຕະໂນມັດ (Auto-save)
var scAutoSaveTimer = null;
function scTriggerAutoSave() {
  clearTimeout(scAutoSaveTimer);
  scAutoSaveTimer = setTimeout(function() {
    if (Object.keys(scDirty).length && !scSaving && SC_EDITABLE) {
      scSave(true);
    }
  }, 600);
}

// ບັນທຶກລາຍການ
function scSave(silent) {
  if (!SC_EDITABLE || scSaving) return;
  var ids = Object.keys(scDirty);
  var $rows = ids.length ? $(ids.map(function(id) { return '.sc-row[data-id="' + id + '"]'; }).join(',')) : $('.sc-row');
  var items = $rows.map(function() { return scRowPayload($(this)); }).get();

  scSaving = true;
  $('#scBtnSave').prop('disabled', true);
  $('#scSaveStatus').html('<i class="fas fa-spinner fa-spin mr-1"></i> ກຳລັງບັນທຶກ...');

  $.post(SC_BACKEND, { action: 'save_counts', check_id: SC_CHECK_ID, items: JSON.stringify(items), note: $('#scNote').val() || '' }, null, 'json')
    .done(function(res) {
      if (res && res.success) {
        scDirty = {};
        (res.rows || []).forEach(scApplySavedRow);
        $('.sc-row').each(function() { scPaintRow($(this)); });
        scRefreshDirtyUi();
        scUpdateStats();
        scRender();
        $('#scSaveStatus').html('<span class="text-success"><i class="fas fa-check-circle mr-1"></i>ບັນທຶກແລ້ວ ' + res.saved_at + '</span>');
        if (!silent) {
          Swal.fire({ icon: 'success', title: 'ບັນທຶກສຳເລັດ', timer: 1200, showConfirmButton: false });
        }
      } else {
        scRefreshDirtyUi();
        Swal.fire({ icon: 'error', title: 'ບັນທຶກບໍ່ສຳເລັດ', text: (res && res.message) || '' });
      }
    })
    .fail(function() {
      scRefreshDirtyUi();
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ເຊື່ອມຕໍ່ເຊີບເວີບໍ່ໄດ້ — ຍັງບໍ່ໄດ້ບັນທຶກ' });
    })
    .always(function() { scSaving = false; $('#scBtnSave').prop('disabled', false); });
}

function scSummaryBox() {
  var s = scStats();
  if (!SC_SHOW_DIFF) {
    return (s.uncounted > 0 ? '<div class="alert alert-warning text-left py-2 mb-2 font-weight-bold" style="font-size:0.92rem;"><i class="fas fa-exclamation-triangle mr-1"></i>ຍັງມີ ' + scFmt(s.uncounted) + ' ລາຍການທີ່ຍັງບໍ່ໄດ້ນັບ</div>' : '') +
      '<div class="text-left p-2 rounded border bg-light" style="font-size:0.95rem; line-height:1.9;">' +
        'ສິນຄ້າທັງໝົດ: <b>' + scFmt(s.total) + '</b> ລາຍການ<br>' +
        'ນັບແລ້ວ: <b>' + scFmt(s.counted) + '</b> ລາຍການ<br>' +
        'ຍັງບໍ່ນັບ: <b>' + scFmt(s.uncounted) + '</b> ລາຍການ' +
      '</div>';
  }
  return (s.uncounted > 0 ? '<div class="alert alert-warning text-left py-2 mb-2 font-weight-bold" style="font-size:0.92rem;"><i class="fas fa-exclamation-triangle mr-1"></i>ຍັງມີ ' + scFmt(s.uncounted) + ' ລາຍການທີ່ຍັງບໍ່ໄດ້ນັບ</div>' : '') +
    '<div class="text-left p-2 rounded border bg-light" style="font-size:0.95rem; line-height:1.9;">' +
      'ສິນຄ້າທັງໝົດ: <b>' + scFmt(s.total) + '</b> ລາຍການ<br>' +
      'ນັບແລ້ວ: <b>' + scFmt(s.counted) + '</b> ລາຍການ<br>' +
      'ຄົບ (ຕົງກັນ): <b class="text-success">' + scFmt(s.match) + ' ລາຍການ</b><br>' +
      'ບໍ່ພໍ (ຂາດ): <b class="text-danger">' + scFmt(s.shortN) + ' ລາຍການ (-' + scFmt(s.shortQ) + ')</b><br>' +
      'ເກີນ: <b class="text-warning">' + scFmt(s.overN) + ' ລາຍການ (+' + scFmt(s.overQ) + ')</b><br>' +
      'ສ່ວນຕ່າງລວມ: <b>' + (s.net > 0 ? '+' : '') + scFmt(s.net) + '</b>' +
    '</div>';
}

function scPostAction(act, title, html, btnText, btnColor) {
  var s = scStats();
  if (act !== 'reject_adjustment' && s.counted === 0) {
    Swal.fire({ icon: 'warning', title: 'ຍັງບໍ່ໄດ້ນັບສິນຄ້າເລີຍ', text: 'ກະລຸນາປ້ອນຈຳນວນຕົວຈິງຢ່າງໜ້ອຍ 1 ລາຍການ' });
    return;
  }
  Swal.fire({
    icon: act === 'reject_adjustment' ? 'question' : 'warning',
    title: title,
    html: html,
    showCancelButton: true,
    reverseButtons: true,
    confirmButtonText: btnText,
    cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: btnColor || '#2563eb',
    cancelButtonColor: '#64748b'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    var items = $('.sc-row').map(function() { return scRowPayload($(this)); }).get();
    Swal.fire({ title: 'ກຳລັງດຳເນີນການ...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
    $.post(SC_BACKEND, { action: act, check_id: SC_CHECK_ID, items: JSON.stringify(items), note: $('#scNote').val() || '' }, null, 'json')
      .done(function(res) {
        if (res && res.success) {
          scDirty = {};
          Swal.fire({ icon: 'success', title: res.message, timer: 1700, showConfirmButton: false }).then(function() { window.location.reload(); });
        } else {
          Swal.fire({ icon: 'error', title: 'ບໍ່ສຳເລັດ', text: (res && res.message) || '' });
        }
      })
      .fail(function() { Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ເຊື່ອມຕໍ່ເຊີບເວີບໍ່ໄດ້' }); });
  });
}

function scSubmitApproval() {
  scPostAction('submit_for_approval', 'ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ?',
    '<div class="text-left small text-muted mb-2">ສົ່ງຜົນການນັບໃຫ້ຫົວໜ້າກວດເບິ່ງລາຍການທີ່ບໍ່ພໍ/ເກີນ ເພື່ອກວດສອບ ແລະ ປັບສະຕັອກ</div>' + scSummaryBox(),
    '<i class="fas fa-paper-plane mr-1"></i> ສົ່ງກວດສອບ', '#d97706');
}

function scApproveAdjustment() {
  scPostAction('approve_adjustment', 'ຢືນຢັນການປັບສະຕັອກໃຫ້ຕົງ?',
    '<div class="alert alert-warning text-left py-2 mb-2" style="font-size:0.9rem;"><i class="fas fa-check-double mr-1"></i>ລະບົບຈະບັນທຶກຈຳນວນຕົວຈິງ ແລະ ປັບສະຕັອກໃນລະບົບໃຫ້ຕົງກັບທີ່ນັບໄດ້ທັນທີ</div>' + scSummaryBox(),
    '<i class="fas fa-check-double mr-1"></i> ປັບສະຕັອກໃຫ້ຕົງ', '#16a34a');
}

function scRejectAdjustment() {
  scPostAction('reject_adjustment', 'ສົ່ງກັບໃຫ້ນັບໃໝ່?', 'ໃບເຊັກສະຕັອກຈະກັບໄປສະຖານະ "ກຳລັງນັບ" ເພື່ອໃຫ້ພະນັກງານກວດນັບໃໝ່', '<i class="fas fa-undo mr-1"></i> ສົ່ງກັບ', '#ef4444');
}

function scComplete() {
  scPostAction('complete_check', 'ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ?',
    '<div class="alert alert-warning text-left py-2 mb-2" style="font-size:0.9rem;"><i class="fas fa-lock mr-1"></i>ລະບົບຈະປັບຈຳນວນສະຕັອກໃນລະບົບໃຫ້ຕົງກັບຈຳນວນຕົວຈິງທີ່ນັບໄດ້ ແລະ ປິດໃບເຊັກສະຕັອກນີ້ທັນທີ</div>' + scSummaryBox(),
    '<i class="fas fa-check-double mr-1"></i> ຢືນຢັນປັບສະຕັອກ', '#16a34a');
}

function scStartNewRound() {
  Swal.fire({
    title: 'ເລີ່ມຮອບເຊັກສະຕັອກໃໝ່?',
    text: 'ລະບົບຈະສ້າງຮອບເຊັກສະຕັອກໃໝ່ເພື່ອໃຫ້ນັບສິນຄ້າຕໍ່ໄດ້ທັນທີ',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-play mr-1"></i> ເລີ່ມເລີຍ',
    cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#64748b'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    var $form = $('<form method="post" action="stock_check.php"><input type="hidden" name="action" value="create_check"><input type="hidden" name="force_new" value="1"></form>');
    $('body').append($form);
    $form.submit();
  });
}

function scDeleteCurrentCheck() {
  Swal.fire({
    icon: 'warning',
    title: 'ລຶບໃບເຊັກສະຕັອກນີ້?',
    text: 'ລຶບໃບ ' + SC_CHECK_NO + ' ແລະ ຂໍ້ມູນທີ່ນັບໄວ້ (ສະຕັອກບໍ່ຖືກປ່ຽນ)',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ລຶບເລີຍ',
    cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    $.post(SC_BACKEND, { action: 'delete_check', check_id: SC_CHECK_ID }, function(res) {
      if (res && res.success) {
        Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false })
          .then(function() { window.location.href = 'stock_check.php'; });
      } else {
        Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: (res && res.message) || 'ລຶບບໍ່ສຳເລັດ' });
      }
    }, 'json').fail(function() {
      Swal.fire({ icon: 'error', title: 'ຜິດພາດ', text: 'ເຊື່ອມຕໍ່ເຊີບເວີບໍ່ໄດ້' });
    });
  });
}

<?php require __DIR__ . '/count_js_scan.php'; ?>

// ---------- ລາຍງານ (Excel / PDF / ພິມ) ----------
function scGetReport() {
  var cell = function($r, sel) { return $.trim($r.find(sel).text().replace(/\s+/g, ' ')); };
  var rows = [], i = 0;
  var s = scStats();
  $('.sc-row').each(function() {
    var $r = $(this);
    var c = scLive($r);
    var sys = parseInt($r.attr('data-system'), 10) || 0;
    var diff = c === null ? null : c - sys;
    // "ນັບເມື່ອ · ຜູ້ນັບ" (ເວລາ ແລະ ຊື່ຢູ່ຄົນລະແຖວໃນຕາຕະລາງ — ບໍ່ໃຫ້ຕິດກັນ)
    var $cb = $r.find('.sc-counted-by');
    var cbName = $.trim($cb.children('div').text());
    var cbTime = $.trim($cb.clone().children('div').remove().end().text().replace(/\s+/g, ' '));
    var by = cbName ? cbTime + ' · ' + cbName : (cbTime || '-');
    var statusText = diff === null ? 'ຍັງບໍ່ນັບ' : (diff === 0 ? 'ຄົບ' : (SC_DONE ? 'ຄົບ (ປັບແລ້ວ)' : (diff < 0 ? 'ບໍ່ພໍ' : 'ເກີນ')));
    // ໂຊນ / ຊັ້ນວາງ ແຍກເປັນຖັນຂອງມັນເອງ (ປ້າຍ "ໂຊນ X · ຊັ້ນ Y" ໃຕ້ຊື່) — ຊື່ສິນຄ້າບໍ່ມີຂໍ້ຄວາມຕຳແໜ່ງປົນ
    var loc = $.trim($r.find('.sc-c-name .badge').text().replace(/\s+/g, ' ')).split(' · ');
    var locPart = function(s, word) {
      s = $.trim(s || '');
      if (s === '' || s.indexOf('ບໍ່ລະບຸ') !== -1) return '-';
      return $.trim(s.indexOf(word) === 0 ? s.slice(word.length) : s).replace(/^:\s*/, '') || '-';
    };
    var rowData = [
      ++i,
      cell($r, '.sc-c-barcode'),
      cell($r, '.sc-c-name > div:first-child'),
      locPart(loc[0], 'ໂຊນ'),
      locPart(loc[1], 'ຊັ້ນ'),
      cell($r, '.sc-c-unit')
    ];
    if (SC_SHOW_SYSTEM) {
      rowData.push(scFmt(sys));
    }
    if (!SC_SHOW_DIFF) {
      rowData.push(
        c === null ? '-' : scFmt(c),
        c === null ? 'ຍັງບໍ່ນັບ' : 'ນັບແລ້ວ',
        by === '-' ? '' : by,
        $r.find('.sc-note').val() || cell($r, 'td:last')
      );
    } else {
      rowData.push(
        c === null ? '-' : scFmt(c),
        statusText,
        diff === null ? '-' : (diff > 0 ? '+' : '') + scFmt(diff),
        by === '-' ? '' : by,
        $r.find('.sc-note').val() || cell($r, 'td:last')
      );
    }
    rows.push(rowData);
  });
  var headers = [
    {t: 'ລຳດັບ', a: 'c'},
    {t: 'ບາໂຄ້ດ'},
    {t: 'ສິນຄ້າ'},
    {t: 'ໂຊນ', a: 'c'},
    {t: 'ຊັ້ນວາງ', a: 'c'},
    {t: 'ຫົວໜ່ວຍ', a: 'c'}
  ];
  if (SC_SHOW_SYSTEM) {
    headers.push({t: 'ໃນລະບົບ', a: 'r'});
  }
  headers.push({t: 'ຕົວຈິງ', a: 'r'});
  if (SC_SHOW_DIFF) {
    headers.push(
      {t: 'ສະຖານະ', a: 'c'},
      {t: 'ສ່ວນຕ່າງ', a: 'r'}
    );
  } else {
    headers.push({t: 'ສະຖານະ', a: 'c'});
  }
  headers.push(
    {t: 'ນັບເມື່ອ / ຜູ້ນັບ'},
    {t: 'ໝາຍເຫດ'}
  );
  var footer = null;
  if (SC_SHOW_DIFF) {
    footer = SC_SHOW_SYSTEM
      ? ['', '', 'ລວມ', '', '', '', '', '', '', (s.net > 0 ? '+' : '') + scFmt(s.net), '', '']
      : ['', '', 'ລວມ', '', '', '', '', '', (s.net > 0 ? '+' : '') + scFmt(s.net), '', ''];
  }

  var meta = [
    'ເລີ່ມ: ' + SC_CREATED,
    'ທັງໝົດ ' + scFmt(s.total) + ' ລາຍການ',
    'ນັບແລ້ວ ' + scFmt(s.counted)
  ];
  if (SC_SHOW_DIFF) {
    meta.push(
      'ຄົບ ' + scFmt(s.match),
      'ບໍ່ພໍ ' + scFmt(s.shortN) + ' (-' + scFmt(s.shortQ) + ')',
      'ເກີນ ' + scFmt(s.overN) + ' (+' + scFmt(s.overQ) + ')'
    );
  }

  return {
    title: 'ໃບເຊັກສະຕັອກ ' + SC_CHECK_NO,
    fileBase: 'stock_check_' + SC_CHECK_NO,
    meta: meta,
    headers: headers,
    rows: rows,
    footer: footer
  };
}

$(function() {
  $('.sc-row').each(function() { scPaintRow($(this)); });
  scUpdateStats();
  scRender();

  var initialQ = ($('#scScanInput').val() || '').trim();
  if (initialQ) {
    var $hit = $('.sc-row:visible').first();
    if ($hit.length) {
      scGoToRow($hit);
      scFlash($hit);
    }
  } else {
    scFilter = 'all';
    scRender();
  }

  // ຈັບແຕ່ບັອກສະແກນຢ່າງດຽວ (ບໍ່ຈັບບັອກຈຳນວນ)
  setTimeout(function() {
    $('#scScanInput').focus().select();
  }, 100);

  // ຄົ້ນຫາ & ສະແກນ
  $('#scScanInput').on('input', function() {
    scPage = 1;
    scRender();
  }).on('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      scHandleScan();
    }
  });

  // ກັ່ນຕອງຕາມໂຊນ
  $('#scZoneFilter').on('change', function() {
    scPage = 1;
    scRender();
  });

  // ປຸ່ມກັ່ນຕອງ
  $('.sc-filter .btn').on('click', function() {
    $('.sc-filter .btn').removeClass('active');
    $(this).addClass('active');
    scFilter = $(this).attr('data-filter');
    scPage = 1;
    scRender();
  });

  // ຈຳນວນແຖວຕໍ່ໜ້າ
  $('#scPerPage').val(SC_PER_PAGE === Infinity ? 'all' : String(SC_PER_PAGE)).on('change', function() {
    var v = $(this).val();
    SC_PER_PAGE = v === 'all' ? Infinity : (parseInt(v, 10) || 20);
    try { localStorage.setItem('StoreMini.stock_check_per_page', v); } catch (e) {}
    scPage = 1;
    scRender();
  });

  // ແບ່ງໜ້າ
  $('#scPager').on('click', 'a[data-page]', function(e) {
    e.preventDefault();
    if ($(this).parent().hasClass('disabled')) return;
    scPage = parseInt($(this).attr('data-page'), 10) || 1;
    scRender();
    var t = $('.sc-card').get(0);
    if (t && t.scrollIntoView) t.scrollIntoView({ block: 'start', behavior: 'smooth' });
  });

  if (!SC_EDITABLE) return;

  // ປ້ອນຕົວເລກຕົວຈິງໃນຕາຕະລາງ
  $('#scItemsBody').on('keydown', '.sc-counted', function(e) {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key === 'Enter') {
      e.preventDefault();
      $('#scScanInput').focus().select();
      return;
    }
    if (e.key && e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
  });

  // ອັບເດດເມື່ອປ້ອນຕົວເລກຕົວຈິງ: ໝາຍເປັນກຳລັງປ້ອນ (ຍັງບໍ່ໂຊສ່ວນຕ່າງ ຈົນກວ່າຈະກົດບັນທຶກ)
  $('#scItemsBody').on('input', '.sc-counted', function() {
    var clean = this.value.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
    if (clean !== this.value) this.value = clean;
    var $r = $(this).closest('.sc-row');
    scMarkDirty($r);
    scPaintRow($r);
    scStampCountedBy($r);
    scUpdateStats();
  });

  $('#scItemsBody').on('input', '.sc-note', function() {
    var $r = $(this).closest('.sc-row');
    scMarkDirty($r);
  });

  // ຄີລັດ Ctrl+S = ບັນທຶກ
  $(document).on('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
      e.preventDefault();
      scSave();
    }
  });

  // ຮັບການສະແກນບາໂຄ້ດອັດຕະໂນມັດທົ່ວໜ້າ (Barcode Scanner Global Listener)
  var scBarcodeBuffer = '', scBarcodeTimer = null;
  $(document).on('keypress', function(e) {
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'textarea' || (tag === 'input' && e.target.id !== 'scScanInput')) {
      return;
    }
    if (e.which === 13) {
      if (scBarcodeBuffer.length >= 3) {
        e.preventDefault();
        $('#scScanInput').val(scBarcodeBuffer);
        scBarcodeBuffer = '';
        clearTimeout(scBarcodeTimer);
        scHandleScan();
        return;
      }
    }
    var char = String.fromCharCode(e.which);
    if (char && char.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
      scBarcodeBuffer += char;
      clearTimeout(scBarcodeTimer);
      scBarcodeTimer = setTimeout(function() { scBarcodeBuffer = ''; }, 250);
    }
  });

  $('#scNote').on('input', function() { scRefreshDirtyUi(); });

  window.addEventListener('beforeunload', function(e) {
    if (Object.keys(scDirty).length) { e.preventDefault(); e.returnValue = ''; }
  });
});
</script>
