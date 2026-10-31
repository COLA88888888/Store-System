<?php
/*
 * ສ່ວນ: JS ໜ້ານັບສິນຄ້າ
 * - ຄິດໄລ່ ແລະ ສະແດງຜົນ ຄົບ / ບໍ່ພໍ / ເກີນ ແລະ ສ່ວນຕ່າງ ແບບ Real-time ຂະນະນັບ
 * - ສະແກນບາໂຄ້ດ (ບວກ 1 ອັດຕະໂນມັດ ຫຼື ໂຟກັສຊ່ອງ)
 * - ກັ່ນຕອງ (ທັງໝົດ, ຄົບ, ບໍ່ພໍ, ເກີນ, ຍັງບໍ່ນັບ), ແບ່ງໜ້າ, ບັນທຶກ (Ctrl+S)
 * - ປັບສະຕັອກໃຫ້ຕົງ (ຂັ້ນດຽວ, ບໍ່ມີຂັ້ນອະນຸມັດ)
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

var scPage = 1, scDirty = {}, scSaving = false;

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
    $d.html('<span class="badge sc-res sc-res-uncounted"><i class="far fa-clock mr-1"></i>' + scT('sc.s040') + '</span>');
    if ($v.length) $v.text('-').addClass('text-muted');
    return;
  }

  // ຖ້າກຳລັງຕີຈຳນວນຕົວຈິງ (ຍັງບໍ່ທັນກົດບັນທຶກ): ບໍ່ໃຫ້ໂຊສ່ວນຕ່າງເທື່ອ!
  if (isDirty) {
    $r.attr('data-state', 'dirty').addClass('sc-state-dirty');
    $d.html('<span class="badge badge-warning text-dark font-weight-bold"><i class="fas fa-pencil-alt mr-1"></i>' + scT('sc.s081') + '</span>');
    if ($v.length) $v.text('-').addClass('text-muted');
    return;
  }

  // ພະນັກງານ (ນັບແບບບໍ່ຮູ້ຍອດ): ບໍ່ບອກ ຄົບ/ບໍ່ພໍ/ເກີນ — ບອກແຕ່ "ນັບແລ້ວ" (ກັນປ້ອນເພີ່ມໃຫ້ "ຄົບ" ເພື່ອປິດບັງສິນຄ້າທີ່ຫາຍ)
  if (!SC_SHOW_DIFF) {
    $r.attr('data-state', 'counted');
    $d.html('<span class="badge sc-res sc-res-uncounted" style="color:#1e40af;border-color:#93c5fd;background:#eff6ff;"><i class="fas fa-check mr-1"></i>' + scT('sc.s039') + '</span>');
    return;
  }

  // ກົດບັນທຶກແລ້ວ: ຈິ່ງໂຊສ່ວນຕ່າງມາ!
  var diff = counted - sys;
  if (SC_DONE && diff !== 0) {
    $r.attr('data-state', diff < 0 ? 'short' : 'over').addClass('sc-state-match');
    $d.html('<span class="badge sc-res sc-res-match"><i class="fas fa-check mr-1"></i>' + scT('sc.s082') + '</span>');
    if ($v.length) $v.text((diff > 0 ? '+' : '') + scFmt(diff) + (unit ? ' ' + unit : '')).addClass(diff < 0 ? 'text-danger font-weight-bold' : 'text-warning font-weight-bold');
    return;
  }
  if (diff === 0) {
    $r.attr('data-state', 'match').addClass('sc-state-match');
    $d.html('<span class="badge sc-res sc-res-match"><i class="fas fa-check mr-1"></i>' + scT('sc.s083') + '</span>');
    if ($v.length) $v.text('0').addClass('text-success font-weight-bold');
  } else if (diff < 0) {
    $r.attr('data-state', 'short').addClass('sc-state-short');
    $d.html('<span class="badge sc-res sc-res-short"><i class="fas fa-arrow-down mr-1"></i>' + scT('sc.s084') + '</span>');
    if ($v.length) $v.text(scFmt(diff) + (unit ? ' ' + unit : '')).addClass('text-danger font-weight-bold');
  } else {
    $r.attr('data-state', 'over').addClass('sc-state-over');
    $d.html('<span class="badge sc-res sc-res-over"><i class="fas fa-arrow-up mr-1"></i>' + scT('sc.s085') + '</span>');
    if ($v.length) $v.text('+' + scFmt(diff) + (unit ? ' ' + unit : '')).addClass('text-warning font-weight-bold');
  }
}

// ຄິດໄລ່ສະຫຼຸບທັງໝົດ
function scStats() {
  var s = { total: 0, counted: 0, uncounted: 0, match: 0, shortN: 0, shortQ: 0, overN: 0, overQ: 0, net: 0, shortValue: 0 };
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
    else if (diff < 0) { s.shortN++; s.shortQ += Math.abs(diff); s.shortValue += Math.abs(diff) * (parseFloat($r.attr('data-cost')) || 0); }
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
  $('#scStatCountedSummary').text(scT('sc.s039') + ' ' + scFmt(s.counted));
  $('#scStatCountedOnly').text(scFmt(s.counted));
  $('#scStatMatch').text(scFmt(s.match));
  $('#scStatShort').text(scFmt(s.shortN));
  $('#scStatShortQty').text(s.shortQ ? scT('sc.s086') + ' -' + scFmt(s.shortQ) : '0 ' + scT('sc.s075'));
  $('#scStatOver').text(scFmt(s.overN));
  $('#scStatOverQty').text(s.overQ ? scT('sc.s087') + scFmt(s.overQ) : '0 ' + scT('sc.s075'));
  $('#scStatUncountedBig').text(scFmt(s.uncounted));
  $('#scStatMismatchTotal').text(scFmt(s.shortN + s.overN));
  $('#scStatValue').text((s.net > 0 ? '+' : '') + scFmt(s.net));
}

// ---------- ກັ່ນຕອງ + ແບ່ງໜ້າ ----------
function scMatches(el) {
  var $r = $(el);

  var q = ($('#scScanInput').val() || '').trim().toLowerCase();
  var okText = !q
            || ($r.attr('data-name') || '').indexOf(q) !== -1
            || ($r.attr('data-barcode') || '').indexOf(q) !== -1
            || ($r.attr('data-zone') || '').indexOf(q) !== -1
            || ($r.attr('data-shelf') || '').indexOf(q) !== -1
            || String($r.attr('data-pid')) === q;
  // ບັອກ ໂຊນ / ຊັ້ນ: ຄ່າຫວ່າງ = ທັງໝົດ, __unassigned__ = ບໍ່ລະບຸ
  var okPlace = function(selectId, attr) {
    var f = $(selectId).length ? ($(selectId).val() || '').trim().toLowerCase() : '';
    var v = ($r.attr(attr) || '').trim().toLowerCase();
    return !f || (f === '__unassigned__' && !v) || v === f;
  };
  return okText && okPlace('#scZoneFilter', 'data-raw-zone') && okPlace('#scShelfFilter', 'data-raw-shelf');
}

function scRender() {
  var rows = $('.sc-row').get(), matched = rows.filter(scMatches);
  var pages = Math.max(1, Math.ceil(matched.length / SC_PER_PAGE));
  scPage = Math.min(Math.max(1, scPage), pages);
  var start = (scPage - 1) * SC_PER_PAGE, end = start + SC_PER_PAGE;
  rows.forEach(function(el) { el.style.display = 'none'; });
  matched.slice(start, end).forEach(function(el) { el.style.display = ''; });

  $('#scNoMatchRow').toggle(matched.length === 0 && rows.length > 0);
  $('#scPagerInfo').text(matched.length ? scT('sc.s074') + ' ' + scFmt(start + 1) + ' - ' + scFmt(Math.min(end, matched.length)) + ' ' + scT('sc.s088') + ' ' + scFmt(matched.length) + ' ' + scT('sc.s075') : scT('sc.s089'));

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
    // ລາຍການທີ່ສະແກນຖືກເຊື່ອງໂດຍບັອກ ໂຊນ / ຊັ້ນ → ລ້າງບັອກ ໃຫ້ເຫັນລາຍການນັ້ນ
    $('#scZoneFilter, #scShelfFilter').val('');
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
  $('#scSaveStatus').html(n > 0 ? '<span class="text-warning"><i class="fas fa-exclamation-circle mr-1"></i>' + scT('sc.s090') + ' ' + n + ' ' + scT('sc.s091') + '</span>' : '');
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
  $('#scJustScannedLoc').text(loc || scT('sc.s092'));
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
  $('#scSaveStatus').html('<i class="fas fa-spinner fa-spin mr-1"></i> ' + scT('sc.s093'));

  $.post(SC_BACKEND, { action: 'save_counts', check_id: SC_CHECK_ID, items: JSON.stringify(items) }, null, 'json')
    .done(function(res) {
      if (res && res.success) {
        scDirty = {};
        (res.rows || []).forEach(scApplySavedRow);
        $('.sc-row').each(function() { scPaintRow($(this)); });
        scRefreshDirtyUi();
        scUpdateStats();
        scRender();
        $('#scSaveStatus').html('<span class="text-success"><i class="fas fa-check-circle mr-1"></i>' + scT('sc.s094') + ' ' + res.saved_at + '</span>');
        if (!silent) {
          Swal.fire({ icon: 'success', title: scT('sc.s095'), timer: 1200, showConfirmButton: false });
        }
      } else {
        scRefreshDirtyUi();
        Swal.fire({ icon: 'error', title: scT('sc.s096'), text: (res && res.message) || '' });
      }
    })
    .fail(function() {
      scRefreshDirtyUi();
      Swal.fire({ icon: 'error', title: scT('sc.s097'), text: scT('sc.s098') });
    })
    .always(function() { scSaving = false; $('#scBtnSave').prop('disabled', false); });
}

function scSummaryBox() {
  var s = scStats();
  if (!SC_SHOW_DIFF) {
    return (s.uncounted > 0 ? '<div class="alert alert-warning text-left py-2 mb-2 font-weight-bold" style="font-size:0.92rem;"><i class="fas fa-exclamation-triangle mr-1"></i>' + scT('sc.s099') + ' ' + scFmt(s.uncounted) + ' ' + scT('sc.s100') + '</div>' : '') +
      '<div class="text-left p-2 rounded border bg-light" style="font-size:0.95rem; line-height:1.9;">' +
        scT('sc.s101') + ' <b>' + scFmt(s.total) + '</b> ' + scT('sc.s075') + '<br>' +
        scT('sc.s102') + ' <b>' + scFmt(s.counted) + '</b> ' + scT('sc.s075') + '<br>' +
        scT('sc.s103') + ' <b>' + scFmt(s.uncounted) + '</b> ' + scT('sc.s075') +
      '</div>';
  }
  return (s.uncounted > 0 ? '<div class="alert alert-warning text-left py-2 mb-2 font-weight-bold" style="font-size:0.92rem;"><i class="fas fa-exclamation-triangle mr-1"></i>' + scT('sc.s099') + ' ' + scFmt(s.uncounted) + ' ' + scT('sc.s100') + '</div>' : '') +
    '<div class="text-left p-2 rounded border bg-light" style="font-size:0.95rem; line-height:1.9;">' +
      scT('sc.s101') + ' <b>' + scFmt(s.total) + '</b> ' + scT('sc.s075') + '<br>' +
      scT('sc.s102') + ' <b>' + scFmt(s.counted) + '</b> ' + scT('sc.s075') + '<br>' +
      scT('sc.s104') + ' <b class="text-success">' + scFmt(s.match) + ' ' + scT('sc.s075') + '</b><br>' +
      scT('sc.s105') + ' <b class="text-danger">' + scFmt(s.shortN) + ' ' + scT('sc.s106') + '-' + scFmt(s.shortQ) + ')</b><br>' +
      scT('sc.s107') + ' <b class="text-warning">' + scFmt(s.overN) + ' ' + scT('sc.s108') + scFmt(s.overQ) + ')</b><br>' +
      scT('sc.s109') + ' <b>' + (s.net > 0 ? '+' : '') + scFmt(s.net) + '</b>' +
    '</div>' +
    // ມູນຄ່າສິນຄ້າທີ່ຂາດ (ຈຳນວນຂາດ × ຕົ້ນທຶນ) ໃຫ້ເຫັນກ່ອນປັບສະຕັອກ
    (s.shortN > 0 ? '<div class="alert alert-danger text-left py-2 mt-2 mb-0 font-weight-bold" style="font-size:0.92rem;">' +
      '<i class="fas fa-exclamation-triangle mr-1"></i><?php echo tlj('ສິນຄ້າຂາດ'); ?> ' + scFmt(s.shortQ) + ' <?php echo tlj('ໜ່ວຍ'); ?>' +
      (s.shortValue > 0 ? ' · <?php echo tlj('ມູນຄ່າ (ຕົ້ນທຶນ)'); ?> ' + scFmt(Math.round(s.shortValue)) + ' <?php echo kipSymbol(); ?>' : '') +
      '<div class="small font-weight-normal mt-1"><?php echo tlj('ກວດລາຍການ'); ?> "<?php echo tlj('ບໍ່ພໍ'); ?>" <?php echo tlj('ກ່ອນປັບສະຕັອກ'); ?> — <?php echo tlj('ປັບແລ້ວແກ້ຄືນບໍ່ໄດ້'); ?></div></div>' : '');
}

function scPostAction(act, title, html, btnText, btnColor) {
  var s = scStats();
  if (s.counted === 0) {
    Swal.fire({ icon: 'warning', title: scT('sc.s110'), text: scT('sc.s111') });
    return;
  }
  Swal.fire({
    icon: 'warning',
    title: title,
    html: html,
    showCancelButton: true,
    reverseButtons: true,
    confirmButtonText: btnText,
    cancelButtonText: scT('sc.s112'),
    confirmButtonColor: btnColor || '#2563eb',
    cancelButtonColor: '#64748b'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    var items = $('.sc-row').map(function() { return scRowPayload($(this)); }).get();
    Swal.fire({ title: scT('sc.s113'), allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
    $.post(SC_BACKEND, { action: act, check_id: SC_CHECK_ID, items: JSON.stringify(items) }, null, 'json')
      .done(function(res) {
        if (res && res.success) {
          scDirty = {};
          Swal.fire({ icon: 'success', title: res.message, timer: 1700, showConfirmButton: false }).then(function() { window.location.reload(); });
        } else {
          Swal.fire({ icon: 'error', title: scT('sc.s114'), text: (res && res.message) || '' });
        }
      })
      .fail(function() { Swal.fire({ icon: 'error', title: scT('sc.s097'), text: scT('sc.s115') }); });
  });
}

function scApproveAdjustment() {
  scPostAction('approve_adjustment', scT('sc.s119'),
    '<div class="alert alert-warning text-left py-2 mb-2" style="font-size:0.9rem;"><i class="fas fa-check-double mr-1"></i>' + scT('sc.s120') + '</div>' + scSummaryBox(),
    '<i class="fas fa-check-double mr-1"></i> ' + scT('sc.s016'), '#16a34a');
}

function scStartNewRound() {
  Swal.fire({
    title: scT('sc.s128'),
    text: scT('sc.s129'),
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-play mr-1"></i> ' + scT('sc.s130'),
    cancelButtonText: scT('sc.s112'),
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
    title: scT('sc.s131'),
    text: scT('sc.s132') + ' ' + SC_CHECK_NO + ' ' + scT('sc.s133'),
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> ' + scT('sc.s134'),
    cancelButtonText: scT('sc.s112'),
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b'
  }).then(function(r) {
    if (!r.isConfirmed) return;
    $.post(SC_BACKEND, { action: 'delete_check', check_id: SC_CHECK_ID }, function(res) {
      if (res && res.success) {
        Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false })
          .then(function() { window.location.href = 'stock_check.php'; });
      } else {
        Swal.fire({ icon: 'error', title: scT('sc.s097'), text: (res && res.message) || scT('sc.s135') });
      }
    }, 'json').fail(function() {
      Swal.fire({ icon: 'error', title: scT('sc.s097'), text: scT('sc.s115') });
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
    var statusText = diff === null ? scT('sc.s040') : (diff === 0 ? scT('sc.s083') : (SC_DONE ? scT('sc.s082') : (diff < 0 ? scT('sc.s084') : scT('sc.s085'))));
    // ໂຊນ / ຊັ້ນວາງ ແຍກເປັນຖັນຂອງມັນເອງ (ປ້າຍ "ໂຊນ X · ຊັ້ນ Y" ໃຕ້ຊື່) — ຊື່ສິນຄ້າບໍ່ມີຂໍ້ຄວາມຕຳແໜ່ງປົນ
    var loc = $.trim($r.find('.sc-c-name .badge').text().replace(/\s+/g, ' ')).split(' · ');
    var locPart = function(s, word) {
      s = $.trim(s || '');
      if (s === '' || s === scT('sc.s046') || s === scT('sc.s223') || s.indexOf('ບໍ່ລະບຸ') !== -1) return '-';
      return $.trim(s.indexOf(word) === 0 ? s.slice(word.length) : s).replace(/^:\s*/, '') || '-';
    };
    var rowData = [
      ++i,
      cell($r, '.sc-c-barcode'),
      cell($r, '.sc-c-name > div:first-child'),
      locPart(loc[0], scT('sc.s045')),
      locPart(loc[1], scT('sc.s136')),
      cell($r, '.sc-c-unit')
    ];
    if (SC_SHOW_SYSTEM) {
      rowData.push(scFmt(sys));
    }
    if (!SC_SHOW_DIFF) {
      rowData.push(
        c === null ? '-' : scFmt(c),
        c === null ? scT('sc.s040') : scT('sc.s039'),
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
    {t: scT('sc.s137'), a: 'c'},
    {t: scT('sc.s058')},
    {t: scT('sc.s138')},
    {t: scT('sc.s045'), a: 'c'},
    {t: scT('sc.s139'), a: 'c'},
    {t: scT('sc.s060'), a: 'c'}
  ];
  if (SC_SHOW_SYSTEM) {
    headers.push({t: scT('sc.s062'), a: 'r'});
  }
  headers.push({t: scT('sc.s140'), a: 'r'});
  if (SC_SHOW_DIFF) {
    headers.push(
      {t: scT('sc.s064'), a: 'c'},
      {t: scT('sc.s066'), a: 'r'}
    );
  } else {
    headers.push({t: scT('sc.s064'), a: 'c'});
  }
  headers.push(
    {t: scT('sc.s067')},
    {t: scT('sc.s068')}
  );
  var footer = null;
  if (SC_SHOW_DIFF) {
    footer = SC_SHOW_SYSTEM
      ? ['', '', scT('sc.s141'), '', '', '', '', '', '', (s.net > 0 ? '+' : '') + scFmt(s.net), '', '']
      : ['', '', scT('sc.s141'), '', '', '', '', '', (s.net > 0 ? '+' : '') + scFmt(s.net), '', ''];
  }

  var meta = [
    scT('sc.s009') + ' ' + SC_CREATED,
    scT('sc.s047') + ' ' + scFmt(s.total) + ' ' + scT('sc.s075'),
    scT('sc.s039') + ' ' + scFmt(s.counted)
  ];
  if (SC_SHOW_DIFF) {
    meta.push(
      scT('sc.s083') + ' ' + scFmt(s.match),
      scT('sc.s084') + ' ' + scFmt(s.shortN) + ' (-' + scFmt(s.shortQ) + ')',
      scT('sc.s085') + ' ' + scFmt(s.overN) + ' (+' + scFmt(s.overQ) + ')'
    );
  }

  return {
    title: scT('sc.s142') + ' ' + SC_CHECK_NO,
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
  }

  // ບໍ່ auto-focus ຕອນເປີດໜ້າ ເພື່ອບໍ່ໃຫ້ແປ້ນພິມເດັ້ງຂຶ້ນໃນຈໍສຳພັດ/ມືຖື
  // setTimeout(function() {
  //   $('#scScanInput').focus().select();
  // }, 100);

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

  // ກັ່ນຕອງຕາມ ໂຊນ / ຊັ້ນ
  $('#scZoneFilter, #scShelfFilter').on('change', function() {
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


  window.addEventListener('beforeunload', function(e) {
    if (Object.keys(scDirty).length) { e.preventDefault(); e.returnValue = ''; }
  });
});
</script>
