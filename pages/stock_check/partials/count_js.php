<?php
/*
 * ສ່ວນ: JS ໜ້ານັບສິນຄ້າ
 * - ຜົນການນັບ (ຄົບ/ບໍ່ຄົບ/ເກີນ), ສ່ວນຕ່າງ (ຈຳນວນສະຕັອກ) ແລະ ບັດສະຫຼຸບ ຄິດຈາກຄ່າທີ່ "ບັນທຶກແລ້ວ" (data-saved) ເທົ່ານັ້ນ —
 *   ພິມ/ສະແກນ ບໍ່ສະແດງສ່ວນຕ່າງທັນທີ, ຕ້ອງກົດບັນທຶກກ່ອນ. ບໍ່ມີບັນທຶກອັດຕະໂນມັດ (ຈະເຮັດໃຫ້ຜົນໂຜ່ອອກມາເອງ);
 *   ອອກຈາກໜ້າຕອນຍັງບໍ່ບັນທຶກ browser ຈະຖາມກ່ອນ.
 * - ສະແກນບາໂຄ້ດ (+1 ໃສ່ແຖວນັ້ນ ແລະ ໄປໜ້າທີ່ມີແຖວນັ້ນ) ຫຼື ພິມຄົ້ນຫາ, ປຸ່ມກັ່ນຕອງ, ແບ່ງໜ້າຝັ່ງ browser.
 * - scGetReport(): ຂໍ້ມູນໃຫ້ export_js.php (ໃບກຳລັງນັບ = ໃບນັບສິນຄ້າ ບໍ່ມີຈຳນວນໃນລະບົບ; ໃບຢືນຢັນແລ້ວ = ລາຍງານເຕັມ).
 * ໄຟລ໌ຍ່ອຍ: count_js_scan.php (ສະແກນ + ຟອມປ້ອນຈຳນວນຮອບນີ້) — require ຢູ່ຈຸດເດີມໃນ <script>.
 * ຖືກ require ຈາກ pages/stock_check/stock_check.php ຫຼັງ footer (ໃຊ້ $scCheck, $scEditable).
 */
?>
<script>
var SC_CHECK_ID = <?php echo (int)$scCheck['id']; ?>;
var SC_STATUS   = <?php echo json_encode($scCheck['status']); ?>;
var SC_EDITABLE = <?php echo $scEditable ? 'true' : 'false'; ?>;
var SC_DONE     = <?php echo $scCheck['status'] === 'completed' ? 'true' : 'false'; ?>;
// false = ພະນັກງານນັບ ໃນໃບທີ່ຍັງນັບຢູ່: ບໍ່ສະແດງຜົນ/ສີ ຄົບ-ບໍ່ຄົບ-ເກີນ (ຈຳນວນໃນລະບົບບໍ່ຖືກສົ່ງມາເລີຍ)
var SC_SHOW_RESULTS = <?php echo $scShowResults ? 'true' : 'false'; ?>;
// ຈຳນວນສິນຄ້າທັງໝົດໃນໃບ — ໃບທີ່ຢືນຢັນແລ້ວ ສະແດງແຕ່ແຖວທີ່ນັບ ຈຶ່ງນັບຈາກແຖວໃນໜ້າບໍ່ໄດ້
var SC_TOTAL_ITEMS = <?php echo (int)$scItemTotal; ?>;
var SC_CHECK_NO = <?php echo json_encode($scCheck['check_no']); ?>;
var SC_CREATED  = <?php echo json_encode(date('d/m/Y H:i', strtotime($scCheck['created_at']))); ?>;
var SC_BACKEND  = '../../api/stock_check_backend.php';
var SC_ME = <?php echo json_encode(trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;
// ແຖວຕໍ່ໜ້າ — ປ່ຽນໄດ້ຈາກບັອກ "ສະແດງ N ລາຍການ" (ຈື່ໄວ້ໃນ browser)
var SC_PER_PAGE = 20;
try {
  var scSavedPer = localStorage.getItem('StoreMini.stock_check_per_page');
  if (scSavedPer) SC_PER_PAGE = scSavedPer === 'all' ? Infinity : (parseInt(scSavedPer, 10) || 20);
} catch (e) {}
// ໃບກຳລັງນັບ: ເລີ່ມຕົ້ນສະແດງແຕ່ລາຍການທີ່ຍັງບໍ່ນັບ; ຖ້າສົ່ງກວດ/ອະນຸມັດ/ປິດໃບແລ້ວ ສະແດງທັງໝົດ
var scFilter = (SC_STATUS === 'draft') ? 'uncounted' : 'all', scPage = 1, scDirty = {}, scSaving = false;

function scFmt(n) { return Number(n || 0).toLocaleString('en-US'); }
function scToInt(v) {
  if (v === undefined || v === null || String(v).trim() === '') return null;
  var n = parseInt(v, 10);
  return isNaN(n) ? null : Math.max(0, n);
}
// ນັບແບບບວກເພີ່ມ: ຊ່ອງໃນແຖວ = ຈຳນວນທີ່ນັບໄດ້ຮອບນີ້ (scAddVal); ✏️ ຕັ້ງຍອດລວມໃໝ່ (data-set: "null" = ລ້າງ).
// ຍອດລວມທີ່ຈະເປັນ = (ຍອດທີ່ຕັ້ງໃໝ່ ຫຼື ຍອດທີ່ບັນທຶກແລ້ວ) + ຮອບນີ້
function scSaved($r)  { return scToInt($r.attr('data-saved')); }
function scAddVal($r) { return scToInt($r.find('.sc-counted').val()); }
function scSetVal($r) {
  var v = $r.attr('data-set');
  if (v === undefined || v === '') return undefined;   // ບໍ່ໄດ້ແກ້ຍອດລວມ
  return v === 'null' ? null : scToInt(v);
}
function scBase($r) { var s = scSetVal($r); return s !== undefined ? s : scSaved($r); }
// ຍອດລວມທີ່ກຳລັງຈະບັນທຶກ (ໃຊ້ທຸກບ່ອນທີ່ຕ້ອງການ "ຄ່າປັດຈຸບັນ": ສະຫຼຸບ, ກ່ອງຢືນຢັນ, ລາຍງານ)
function scLive($r) {
  if (!SC_EDITABLE) return scToInt($r.find('.sc-counted-text').attr('data-value'));
  var base = scBase($r), add = scAddVal($r);
  if (add === null) return base;
  return (base || 0) + add;
}
// ສະແດງຍອດລວມຂ້າງຊ່ອງ: "ລວມ 100" ຫຼື ຖ້າມີການປ່ຽນທີ່ຍັງບໍ່ບັນທຶກ "100 → 150"
function scRenderTotal($r) {
  var $t = $r.find('.sc-total');
  if (!$t.length) return;
  var saved = scSaved($r), next = scLive($r), changed = scSetVal($r) !== undefined || (scAddVal($r) !== null);
  var fmt = function(v) { return v === null ? '-' : scFmt(v); };
  if (changed && next !== saved) {
    $t.html('ລວມ ' + fmt(saved) + ' <i class="fas fa-arrow-right mx-1"></i><b class="text-primary">' + fmt(next) + '</b>');
  } else {
    $t.html(saved === null ? '<span class="text-muted">ຍັງບໍ່ນັບ</span>' : 'ລວມ <b>' + scFmt(saved) + '</b>');
  }
}

// ແຕ້ມຜົນການນັບຈາກຄ່າທີ່ບັນທຶກແລ້ວ
function scPaintRow($r) {
  var counted = scSaved($r);
  if (!SC_SHOW_RESULTS) {   // ພະນັກງານນັບ: ຮູ້ແຕ່ວ່ານັບແລ້ວ/ຍັງບໍ່ນັບ
    $r.attr('data-state', counted === null ? 'uncounted' : 'counted');
    return;
  }
  var $d = $r.find('.sc-diff'), $v = $r.find('.sc-diff-value');
  $r.removeClass('sc-state-match sc-state-short sc-state-over');
  $v.removeClass('text-danger text-success text-muted');
  if (counted === null) {
    $r.attr('data-state', 'uncounted');
    $d.html('<span class="text-muted">ຍັງບໍ່ນັບ</span>');
    $v.text('-').addClass('text-muted');
    return;
  }
  var diff = counted - (parseInt($r.attr('data-system'), 10) || 0);
  var state = diff === 0 ? 'match' : (diff < 0 ? 'short' : 'over');
  $r.attr('data-state', state).addClass('sc-state-' + state);
  $d.html(diff === 0 ? '<span class="badge sc-res sc-res-match"><i class="fas fa-check mr-1"></i>ຄົບ</span>'
    : (diff < 0 ? '<span class="badge sc-res sc-res-short"><i class="fas fa-arrow-down mr-1"></i>ບໍ່ຄົບ</span>'
                : '<span class="badge sc-res sc-res-over"><i class="fas fa-arrow-up mr-1"></i>ເກີນ</span>'));
  // ສ່ວນຕ່າງ = ຈຳນວນສະຕັອກ (ບໍ່ແມ່ນເງິນ)
  var unit = $.trim($r.find('.sc-c-unit').text());
  $v.text((diff > 0 ? '+' : '') + scFmt(diff) + (unit && unit !== '-' ? ' ' + unit : ''))
    .addClass(diff === 0 ? 'text-muted' : (diff < 0 ? 'text-danger' : 'text-success'));
}

// ສະຫຼຸບ: live = ໃຊ້ຄ່າທີ່ກຳລັງພິມ (ສຳລັບກ່ອງຢືນຢັນ), ບໍ່ດັ່ງນັ້ນ ໃຊ້ຄ່າທີ່ບັນທຶກແລ້ວ
function scStats(live) {
  var s = { total: 0, counted: 0, match: 0, shortN: 0, shortQ: 0, overN: 0, overQ: 0, net: 0 };
  $('.sc-row').each(function() {
    var $r = $(this), c = live ? scLive($r) : scSaved($r);
    s.total++;
    if (c === null) return;
    s.counted++;
    var diff = c - (parseInt($r.attr('data-system'), 10) || 0);
    s.net += diff;   // ສ່ວນຕ່າງລວມ = ເກີນ − ບໍ່ຄົບ (ຈຳນວນສະຕັອກ)
    if (diff === 0) s.match++;
    else if (diff < 0) { s.shortN++; s.shortQ -= diff; }
    else { s.overN++; s.overQ += diff; }
  });
  s.total = Math.max(s.total, SC_TOTAL_ITEMS);
  return s;
}

function scUpdateStats() {
  var s = scStats(false);
  $('#scStatTotal').text(scFmt(s.total));
  $('#scStatCounted').text(scFmt(s.counted));
  $('#scStatUncounted').text('ຍັງບໍ່ນັບ ' + scFmt(s.total - s.counted));
  $('#scStatUncountedBig').text(scFmt(s.total - s.counted));
  $('#scStatMatch').text(scFmt(s.match));
  // ບໍ່ຄົບ / ເກີນ: ຕົວເລກໃຫຍ່ = ຈຳນວນສະຕັອກ, ຕົວນ້ອຍ = ຈັກລາຍການ
  $('#scStatShort').text(s.shortQ ? '-' + scFmt(s.shortQ) : '0');
  $('#scStatShortQty').text(scFmt(s.shortN) + ' ລາຍການ');
  $('#scStatOver').text(s.overQ ? '+' + scFmt(s.overQ) : '0');
  $('#scStatOverQty').text(scFmt(s.overN) + ' ລາຍການ');
  $('#scStatValue').text((s.net > 0 ? '+' : '') + scFmt(s.net));
}

// ---------- ກັ່ນຕອງ + ແບ່ງໜ້າ ----------
function scMatches(el) {
  var $r = $(el), st = $r.attr('data-state');
  var q = ($('#scScanInput').val() || '').trim().toLowerCase();
  var okText = !q || ($r.attr('data-name') || '').indexOf(q) !== -1 || ($r.attr('data-barcode') || '').indexOf(q) !== -1 || String($r.attr('data-pid')) === q;
  // "ຍັງບໍ່ນັບ" ລວມແຖວທີ່ກຳລັງປ້ອນ/ສະແກນແຕ່ຍັງບໍ່ບັນທຶກ — ບໍ່ໃຫ້ຫາຍໄປລະຫວ່າງພິມ, ຫາຍໄປຫຼັງກົດບັນທຶກ
  var okState = scFilter === 'all'
    || (scFilter === 'uncounted' ? (st === 'uncounted' || !!scDirty[$r.attr('data-id')])
      : (scFilter === 'counted' ? st !== 'uncounted' : scFilter === st));
  return okText && okState;
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
  {  // ສະແດງສະເໝີ (ເຖິງວ່າຈະມີໜ້າດຽວ)
    var li = function(p, label, cls) { return '<li class="page-item ' + (cls || '') + '"><a class="page-link" href="#" data-page="' + p + '">' + label + '</a></li>'; };
    html += li(scPage - 1, '<i class="fas fa-chevron-left"></i>', scPage <= 1 ? 'disabled' : '');
    var a = Math.max(1, scPage - 2), b = Math.min(pages, scPage + 2);
    if (a > 1) { html += li(1, '1'); if (a > 2) html += '<li class="page-item disabled"><span class="page-link border-0">…</span></li>'; }
    for (var p = a; p <= b; p++) html += li(p, p, p === scPage ? 'active' : '');
    if (b < pages) { if (b < pages - 1) html += '<li class="page-item disabled"><span class="page-link border-0">…</span></li>'; html += li(pages, pages); }
    html += li(scPage + 1, '<i class="fas fa-chevron-right"></i>', scPage >= pages ? 'disabled' : '');
  }
  $('#scPager').html(html);
  return matched;
}

// ໄປໜ້າທີ່ມີແຖວນີ້ (ຖ້າຖືກກັ່ນຕອງອອກ ໃຫ້ກັບໄປ "ທັງໝົດ")
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

// ---------- ການປ່ຽນແປງທີ່ຍັງບໍ່ບັນທຶກ ----------
function scRefreshDirtyUi() {
  var n = Object.keys(scDirty).length;
  $('#scUnsavedBadge').text(n).toggle(n > 0);
  $('#scSaveStatus').html(n > 0 ? '<i class="fas fa-circle text-warning mr-1" style="font-size:8px;"></i> ມີ ' + n + ' ລາຍການຍັງບໍ່ບັນທຶກ — ກະລຸນາກົດບັນທຶກ' : '');
}

function scMarkDirty($r) {
  scDirty[$r.attr('data-id')] = true;
  scRefreshDirtyUi();
}

// ຖັນ "ນັບເມື່ອ / ຜູ້ນັບ": ອັບເດດທັນທີເມື່ອປ້ອນຈຳນວນ (ລຶບອອກ → "-")
function scStampCountedBy($r) {
  var $c = $r.find('.sc-counted-by');
  if (scLive($r) === null) { $c.html('<span class="text-muted">-</span>'); return; }
  var d = new Date(), p = function(n) { return (n < 10 ? '0' : '') + n; };
  $c.html('<i class="fas fa-check-circle text-success mr-1"></i>' + p(d.getDate()) + '/' + p(d.getMonth() + 1) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) +
          '<div class="text-muted">' + $('<div>').text(SC_ME || '-').html() + '</div>');
}

// ສົ່ງເປັນສ່ວນຕ່າງ (add) / ຍອດທີ່ຕັ້ງໃໝ່ (set) — ເຊີບເວີບວກໃນຖານຂໍ້ມູນ ສະນັ້ນ ຫຼາຍຄົນນັບພ້ອມກັນບໍ່ທັບກັນ
function scRowPayload($r) {
  var p = { id: parseInt($r.attr('data-id'), 10), note: $r.find('.sc-note').val() || '' };
  if (!SC_EDITABLE) return p;
  var setv = scSetVal($r), add = scAddVal($r);
  if (setv !== undefined) p.set = setv === null ? '' : setv;
  if (add !== null) {
    if (add > 0) p.add = add;
    else if (scBase($r) === null) p.set = 0;   // ນັບແລ້ວບໍ່ມີສິນຄ້າເລີຍ → ບັນທຶກເປັນ 0
  }
  return p;
}

// ຫຼັງບັນທຶກ: ເອົາຍອດລວມລ່າສຸດຈາກເຊີບເວີ (ລວມທີ່ຄົນອື່ນນັບເພີ່ມ), ລ້າງຊ່ອງຮອບນີ້
function scApplySavedRow(r) {
  var $r = $('.sc-row[data-id="' + r.id + '"]');
  if (!$r.length) return;
  $r.attr('data-saved', r.counted_qty === null ? '' : r.counted_qty).removeAttr('data-set');
  $r.find('.sc-counted').val('');
  $r.find('.sc-counted-by').html(r.counted_qty === null || !r.counted_at ? '<span class="text-muted">-</span>'
    : '<i class="fas fa-check-circle text-success mr-1"></i>' + r.counted_at + '<div class="text-muted">' + $('<div>').text(r.counted_by_name || '-').html() + '</div>');
  scRenderTotal($r);
  scPaintRow($r);
}

// ບັນທຶກ → ແລ້ວຈຶ່ງສະແດງຜົນການນັບຂອງແຖວທີ່ບັນທຶກ
function scSave() {
  if (!SC_EDITABLE || scSaving) return;
  var ids = Object.keys(scDirty);
  var $rows = $(ids.map(function(id) { return '.sc-row[data-id="' + id + '"]'; }).join(',') || []);
  var items = $rows.map(function() { return scRowPayload($(this)); }).get();
  scSaving = true;
  $('#scBtnSave').prop('disabled', true);
  $('#scSaveStatus').html('<i class="fas fa-spinner fa-spin mr-1"></i> ກຳລັງບັນທຶກ...');
  $.post(SC_BACKEND, { action: 'save_counts', check_id: SC_CHECK_ID, items: JSON.stringify(items), note: $('#scNote').val() || '' }, null, 'json')
    .done(function(res) {
      if (res && res.success) {
        (res.rows || []).forEach(scApplySavedRow);
        scDirty = {};
        scRefreshDirtyUi();
        scUpdateStats();
        scRender();
        $('#scSaveStatus').html('<i class="fas fa-check-circle text-success mr-1"></i> ບັນທຶກແລ້ວ ' + res.saved_at);
        Swal.fire({ icon: 'success', title: 'ບັນທຶກສຳເລັດ',
          text: items.length ? 'ບັນທຶກ ' + items.length + ' ລາຍການ — ຍ້າຍໄປແຖບ "ປະຫວັດການນັບ" ແລ້ວ' : '',
          timer: 1500, showConfirmButton: false });
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

function scPostAction(act, title, html, btnText, btnColor) {
  var s = scStats(true);
  if (act !== 'reject_adjustment' && s.counted === 0) {
    Swal.fire({ icon: 'warning', title: 'ຍັງບໍ່ໄດ້ນັບສິນຄ້າເລີຍ', text: 'ກະລຸນາປ້ອນຈຳນວນຕົວຈິງຢ່າງໜ້ອຍ 1 ລາຍການ' });
    return;
  }
  Swal.fire({
    icon: act === 'reject_adjustment' ? 'question' : 'warning', title: title, html: html,
    showCancelButton: true, reverseButtons: true, confirmButtonText: btnText, cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: btnColor || '#2563eb', cancelButtonColor: '#64748b'
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

function scSummaryBox() {
  var s = scStats(true), uncounted = s.total - s.counted;
  return (uncounted > 0 ? '<div class="text-left text-danger font-weight-bold mb-2" style="font-size:0.92rem;"><i class="fas fa-exclamation-triangle mr-1"></i>ຍັງມີ ' + scFmt(uncounted) + ' ລາຍການທີ່ບໍ່ໄດ້ນັບ</div>' : '') +
    (SC_SHOW_RESULTS
      ? '<div class="text-left" style="font-size:0.95rem; line-height:1.85;">ນັບແລ້ວ: <b>' + scFmt(s.counted) + '</b> / ' + scFmt(s.total) + ' ລາຍການ<br>ຄົບ: <b>' + scFmt(s.match) + ' ລາຍການ</b><br>ບໍ່ຄົບ: <b class="text-danger">' + scFmt(s.shortN) + ' ລາຍການ (-' + scFmt(s.shortQ) + ')</b><br>ເກີນ: <b class="text-success">' + scFmt(s.overN) + ' ລາຍການ (+' + scFmt(s.overQ) + ')</b><br>ສ່ວນຕ່າງລວມ: <b>' + (s.net > 0 ? '+' : '') + scFmt(s.net) + '</b></div>'
      : '<div class="text-left" style="font-size:0.95rem;">ນັບແລ້ວ: <b>' + scFmt(s.counted) + '</b> / ' + scFmt(s.total) + ' ລາຍການ</div>');
}

function scSubmitApproval() {
  scPostAction('submit_for_approval', 'ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ?',
    '<div class="text-left small text-muted mb-2">ສົ່ງຜົນການນັບໃຫ້ຫົວໜ້າກວດເບິ່ງລາຍການທີ່ບໍ່ຄົບ/ເກີນ ເພື່ອຂໍອະນຸຍາດປັບສະຕັອກ</div>' + scSummaryBox(),
    '<i class="fas fa-paper-plane mr-1"></i> ສົ່ງຂໍອະນຸມັດ', '#d97706');
}

function scApproveAdjustment() {
  scPostAction('approve_adjustment', 'ອະນຸຍາດໃຫ້ປັບສະຕັອກໃຫ້ຕົງກັນ?',
    '<div class="alert alert-info text-left py-2 mb-2" style="font-size:0.9rem;"><i class="fas fa-user-check mr-1"></i>ເມື່ອອະນຸຍາດແລ້ວ ພະນັກງານຈະເຫັນລາຍການບໍ່ຄົບ/ເກີນ ແລະ ສາມາດກົດປຸ່ມ <b>ປັບສະຕັອກຕົວຈິງ ກັບ ລະບົບໃຫ້ຕົງກັນ</b> ໄດ້</div>' + scSummaryBox(),
    '<i class="fas fa-check mr-1"></i> ອະນຸຍາດໃຫ້ປັບສະຕັອກ', '#0284c7');
}

function scRejectAdjustment() {
  scPostAction('reject_adjustment', 'ສົ່ງກັບໃຫ້ນັບໃໝ່?', 'ໃບເຊັກສະຕັອກຈະກັບໄປສະຖານະ "ກຳລັງນັບ" ເພື່ອໃຫ້ພະນັກງານກວດນັບໃໝ່', '<i class="fas fa-undo mr-1"></i> ສົ່ງກັບ', '#ef4444');
}

function scComplete() {
  scPostAction('complete_check', 'ປັບສະຕັອກຕົວຈິງ ກັບ ລະບົບໃຫ້ຕົງກັນ?',
    '<div class="alert alert-warning text-left py-2 mb-2" style="font-size:0.9rem;"><i class="fas fa-lock mr-1"></i>ປັບສະຕັອກແລ້ວ <b>ໃບນີ້ຈະປິດທັນທີ ແລະ ແກ້ໄຂບໍ່ໄດ້ອີກ</b></div>' + scSummaryBox(),
    '<i class="fas fa-check-double mr-1"></i> ຢືນຢັນປັບສະຕັອກ', '#16a34a');
}

<?php require __DIR__ . '/count_js_scan.php'; /* ສະແກນ + ຟອມປ້ອນຈຳນວນຮອບນີ້ */ ?>
// ---------- ລາຍງານ (Excel / PDF / ພິມ) ----------
function scGetReport() {
  var cell = function($r, sel) { return $.trim($r.find(sel).text().replace(/\s+/g, ' ')); };
  var rows = [], i = 0;
  if (!SC_DONE) {
    // ໃບກຳລັງນັບ: ໃບນັບສິນຄ້າ (ບໍ່ມີຈຳນວນໃນລະບົບ) — ຊ່ອງຕົວຈິງວ່າງໄວ້ໃຫ້ຂຽນຖ້າຍັງບໍ່ນັບ
    $('.sc-row').each(function() {
      var $r = $(this), c = scLive($r), by = cell($r, '.sc-counted-by');
      rows.push([++i, cell($r, '.sc-c-barcode'), cell($r, '.sc-c-name'), cell($r, '.sc-c-unit'),
                 c === null ? '' : scFmt(c), by === '-' ? '' : by, $r.find('.sc-note').val() || '']);
    });
    return {
      title: 'ໃບນັບສະຕັອກ ' + SC_CHECK_NO, fileBase: 'stock_count_sheet_' + SC_CHECK_NO,
      meta: ['ເລີ່ມນັບ ' + SC_CREATED, 'ທັງໝົດ ' + rows.length + ' ລາຍການ'],
      headers: [{t: 'ລຳດັບ', a: 'c'}, {t: 'ບາໂຄ້ດ'}, {t: 'ສິນຄ້າ'}, {t: 'ຫົວໜ່ວຍ', a: 'c'}, {t: 'ຈຳນວນຕົວຈິງ', a: 'c'}, {t: 'ນັບເມື່ອ / ຜູ້ນັບ'}, {t: 'ໝາຍເຫດ'}],
      rows: rows, footer: null
    };
  }
  var s = scStats(false);
  $('.sc-row').each(function() {
    var $r = $(this), c = scSaved($r), sys = parseInt($r.attr('data-system'), 10) || 0;
    var diff = c === null ? null : c - sys, by = cell($r, '.sc-counted-by');
    rows.push([++i, cell($r, '.sc-c-barcode'), cell($r, '.sc-c-name'), cell($r, '.sc-c-unit'), scFmt(sys),
               c === null ? '-' : scFmt(c),
               diff === null ? 'ບໍ່ໄດ້ນັບ' : (diff === 0 ? 'ຄົບ' : (diff < 0 ? 'ບໍ່ຄົບ' : 'ເກີນ')),
               diff === null ? '-' : (diff > 0 ? '+' : '') + scFmt(diff),
               by === '-' ? '' : by, cell($r, 'td:last')]);
  });
  return {
    title: 'ລາຍງານເຊັກສະຕັອກ ' + SC_CHECK_NO, fileBase: 'stock_check_' + SC_CHECK_NO,
    meta: ['ເລີ່ມນັບ ' + SC_CREATED, 'ນັບແລ້ວ ' + scFmt(s.counted) + ' / ' + scFmt(s.total), 'ຄົບ ' + scFmt(s.match),
           'ບໍ່ຄົບ ' + scFmt(s.shortN) + ' (-' + scFmt(s.shortQ) + ')', 'ເກີນ ' + scFmt(s.overN) + ' (+' + scFmt(s.overQ) + ')'],
    headers: [{t: 'ລຳດັບ', a: 'c'}, {t: 'ບາໂຄ້ດ'}, {t: 'ສິນຄ້າ'}, {t: 'ຫົວໜ່ວຍ', a: 'c'}, {t: 'ໃນລະບົບ', a: 'r'}, {t: 'ຕົວຈິງ', a: 'r'},
              {t: 'ຜົນການນັບ', a: 'c'}, {t: 'ສ່ວນຕ່າງ', a: 'r'}, {t: 'ນັບເມື່ອ / ຜູ້ນັບ'}, {t: 'ໝາຍເຫດ'}],
    rows: rows,
    footer: ['', '', 'ລວມ', '', '', '', '', (s.net > 0 ? '+' : '') + scFmt(s.net), '', '']
  };
}

$(function() {
  $('.sc-row').each(function() { scPaintRow($(this)); scRenderTotal($(this)); });
  scUpdateStats();
  scRender();

  $('#scScanInput').on('input', function() { scPage = 1; scRender(); }).on('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); scHandleScan(); }
  }).focus();

  $('.sc-filter .btn').on('click', function() {
    $('.sc-filter .btn').removeClass('active');
    $(this).addClass('active');
    scFilter = $(this).attr('data-filter');
    scPage = 1;
    scRender();
  });

  $('#scPerPage').val(SC_PER_PAGE === Infinity ? 'all' : String(SC_PER_PAGE)).on('change', function() {
    var v = $(this).val();
    SC_PER_PAGE = v === 'all' ? Infinity : (parseInt(v, 10) || 20);
    try { localStorage.setItem('StoreMini.stock_check_per_page', v); } catch (e) {}
    scPage = 1;
    scRender();
  });

  $('#scPager').on('click', 'a[data-page]', function(e) {
    e.preventDefault();
    if ($(this).parent().hasClass('disabled')) return;
    scPage = parseInt($(this).attr('data-page'), 10) || 1;
    scRender();
    var t = $('.sc-card').get(0);
    if (t && t.scrollIntoView) t.scrollIntoView({ block: 'start', behavior: 'smooth' });
  });

  if (!SC_EDITABLE) return;

  // ຊ່ອງຈຳນວນ: ປ້ອນໄດ້ແຕ່ຕົວເລກ 0-9 — ກັ້ນຕອນກົດປຸ່ມ ແລະ ລຶບອອກຕອນວາງ (paste) / IME
  $('#scItemsBody').on('keydown', '.sc-counted', function(e) {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key && e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
  });
  $('#scItemsBody').on('input', '.sc-counted', function() {
    var clean = this.value.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
    if (clean !== this.value) this.value = clean;
  });

  $('#scItemsBody').on('input', '.sc-counted, .sc-note', function() {
    var $r = $(this).closest('.sc-row');
    if ($(this).hasClass('sc-counted')) { scRenderTotal($r); scStampCountedBy($r); }
    scMarkDirty($r);
  });

  // ✏️ ແກ້ຍອດລວມໂດຍກົງ (ນັບຜິດ) — ວ່າງ = ກັບເປັນ "ຍັງບໍ່ນັບ"; ຈຳນວນຮອບນີ້ທີ່ຍັງບໍ່ບັນທຶກຖືກລ້າງ
  $('#scItemsBody').on('click', '.sc-edit-total', function() {
    var $r = $(this).closest('.sc-row'), base = scBase($r);
    var name = $.trim($r.find('.sc-c-name').text());
    Swal.fire({
      title: 'ແກ້ຍອດລວມ',
      html: '<div class="small text-muted mb-2">' + $('<div>').text(name).html() + '</div>' +
            '<div class="small mb-1">ຍອດລວມປັດຈຸບັນ: <b>' + (base === null ? 'ຍັງບໍ່ນັບ' : scFmt(base)) + '</b></div>',
      input: 'text',
      inputValue: base === null ? '' : String(base),
      inputAttributes: { inputmode: 'numeric', maxlength: 7, autocomplete: 'off' },
      inputPlaceholder: 'ວ່າງ = ຍັງບໍ່ນັບ',
      showCancelButton: true, reverseButtons: true,
      confirmButtonText: '<i class="fas fa-check mr-1"></i> ຕົກລົງ', cancelButtonText: 'ຍົກເລີກ',
      confirmButtonColor: '#2563eb', cancelButtonColor: '#64748b',
      inputValidator: function(v) { if (String(v).trim() !== '' && !/^\d+$/.test(String(v).trim())) return 'ປ້ອນໄດ້ແຕ່ຕົວເລກ 0 ຂຶ້ນໄປ'; }
    }).then(function(res) {
      if (!res.isConfirmed) return;
      var v = String(res.value).trim();
      $r.attr('data-set', v === '' ? 'null' : String(parseInt(v, 10)));
      $r.find('.sc-counted').val('');
      scRenderTotal($r); scStampCountedBy($r); scMarkDirty($r); scFlash($r);
    });
  });
  // Enter ໃນຊ່ອງນັບ → ໄປແຖວຖັດໄປ (ຂ້າມໄປໜ້າຕໍ່ໄປໄດ້)
  $('#scItemsBody').on('keydown', '.sc-counted', function(e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    var matched = $('.sc-row').get().filter(scMatches);
    var idx = matched.indexOf($(this).closest('.sc-row').get(0));
    var next = matched[idx + 1];
    if (!next) { $('#scScanInput').focus(); return; }
    if (next.style.display === 'none') scGoToRow($(next));
    $(next).find('.sc-counted').focus().select();
  });
  // Ctrl+S = ບັນທຶກ
  $(document).on('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); scSave(); }
  });
  $('#scNote').on('input', function() { scRefreshDirtyUi(); });

  window.addEventListener('beforeunload', function(e) {
    if (Object.keys(scDirty).length) { e.preventDefault(); e.returnValue = ''; }
  });
});
</script>
