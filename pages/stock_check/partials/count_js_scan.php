<?php
/*
 * ສ່ວນ: JS ໜ້ານັບສິນຄ້າ — ສະແກນບາໂຄ້ດ ແລະ ຟອມປ້ອນຈຳນວນທີ່ນັບໄດ້ຮອບນີ້ (ບວກເພີ່ມໃສ່ຍອດລວມ)
 * scOpenCountModal(): ຟອມສະແດງຍອດລວມທີ່ນັບແລ້ວ + ຊ່ອງ "ນັບໄດ້ຮອບນີ້" + ຍອດລວມໃໝ່.
 * scHandleScan(): Enter ໃນຊ່ອງສະແກນ → ຫາແຖວຕາມບາໂຄ້ດ/ລະຫັດ ແລ້ວເປີດຟອມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/stock_check/partials/count_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ
 * (ພາຍໃນ <script> ດຽວກັນ — ໃຊ້ຟັງຊັນ ແລະ ຕົວແປທີ່ count_js.php ສ້າງໄວ້ກ່ອນໜ້າ).
 */
?>
// ເປີດຟອມປ້ອນຈຳນວນທີ່ນັບຕົວຈິງເມື່ອສະແກນພົບສິນຄ້າ
function scOpenCountModal($r) {
  var esc = function(s) { return $('<div>').text(s).html(); };
  var name = esc($.trim($r.find('.sc-c-name').text()));
  var barcode = esc($.trim($r.find('.sc-c-barcode').text()));
  var rawU = $.trim($r.find('.sc-c-unit').text());
  var unit = esc(rawU && rawU !== '-' ? rawU : 'ອັນ');
  // ນັບແບບບວກເພີ່ມ: ຟອມຖາມ "ນັບໄດ້ຮອບນີ້" ແລ້ວບວກໃສ່ຍອດລວມ (ສະແດງຍອດລວມເກົ່າ ແລະ ຍອດລວມໃໝ່)
  var base = scBase($r), curAdd = scAddVal($r), curQty = curAdd;
  var curNote = esc($r.find('.sc-note').val() || '').replace(/"/g, '&quot;');
  Swal.fire({
    title: '<i class="fas fa-boxes text-primary mr-2"></i>ປ້ອນຈຳນວນທີ່ນັບໄດ້ຮອບນີ້',
    html:
      '<div class="text-left p-2 mb-3 rounded border" style="background:#f8fafc;">' +
        '<div class="font-weight-bold text-dark" style="font-size:1.05rem;">' + name + '</div>' +
        '<div class="small text-muted mt-1"><i class="fas fa-barcode mr-1"></i>ບາໂຄ້ດ: <b class="text-primary">' + barcode + '</b> &nbsp;|&nbsp; ຫົວໜ່ວຍ: <b>' + unit + '</b></div>' +
      '</div>' +
      '<div class="form-group text-left mb-3">' +
        '<div class="d-flex justify-content-between small mb-2 px-1"><span>ຍອດລວມທີ່ນັບໄວ້ແລ້ວ:</span><b>' + (base === null ? 'ຍັງບໍ່ນັບ' : scFmt(base) + ' ' + unit) + '</b></div>' +
        '<label class="font-weight-bold text-dark mb-1">ນັບໄດ້ຮອບນີ້ (' + unit + ') — ບວກເພີ່ມ <span class="text-danger">*</span></label>' +
        '<input type="text" id="scModalQty" inputmode="numeric" pattern="[0-9]*" maxlength="7" autocomplete="off" class="form-control form-control-lg text-center font-weight-bold" style="font-size:1.5rem; height:52px; border:2px solid #2563eb; border-radius:10px;" value="' + (curQty !== null ? curQty : '') + '" placeholder="ປ້ອນຈຳນວນ...">' +
        '<div class="text-center mt-2" style="font-size:1rem;">ຍອດລວມໃໝ່: <b class="text-primary" id="scModalTotal">' + (base === null ? '-' : scFmt(base)) + '</b> ' + unit + '</div>' +
      '</div>' +
      '<div class="form-group text-left mb-0">' +
        '<label class="small font-weight-bold text-muted mb-1">ໝາຍເຫດ (ຖ້າມີ)</label>' +
        '<input type="text" id="scModalNote" maxlength="255" autocomplete="off" class="form-control" style="border-radius:8px;" value="' + curNote + '" placeholder="ໝາຍເຫດລາຍການນີ້...">' +
      '</div>',
    showCancelButton: true, confirmButtonText: '<i class="fas fa-check mr-1"></i> ຕົກລົງ', cancelButtonText: 'ຍົກເລີກ',
    confirmButtonColor: '#2563eb', cancelButtonColor: '#64748b', heightAuto: false,
    didOpen: function() {
      var qEl = document.getElementById('scModalQty'), nEl = document.getElementById('scModalNote');
      if (qEl) {
        setTimeout(function() { qEl.focus(); qEl.select(); }, 50);
        $(qEl).on('keydown', function(e) {
          if (e.ctrlKey || e.metaKey || e.altKey) return;
          if (e.key === 'Enter') { e.preventDefault(); Swal.clickConfirm(); return; }
          if (e.key && e.key.length === 1 && !/[0-9]/.test(e.key)) {
            e.preventDefault();
            if (/[^\x20-\x7E]/.test(e.key) && window.showSwitchKeyboardAlert) window.showSwitchKeyboardAlert(e.key, qEl, function() { qEl.focus(); qEl.select(); });
          }
        }).on('input', function() {
          var clean = this.value.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
          if (clean !== this.value) this.value = clean;
          scModalPreview();
        });
        var scModalPreview = function() {
          var n = scToInt(qEl.value);
          $('#scModalTotal').text(n === null ? (base === null ? '-' : scFmt(base)) : scFmt((base || 0) + n));
        };
        scModalPreview();
      }
      if (nEl) $(nEl).on('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); Swal.clickConfirm(); } });
    },
    preConfirm: function() {
      var raw = ($('#scModalQty').val() || '').trim();
      if (raw === '' || !/^\d+$/.test(raw)) { Swal.showValidationMessage('ກະລຸນາປ້ອນຈຳນວນທີ່ນັບໄດ້ຮອບນີ້ (ຕົວເລກ 0 ຂຶ້ນໄປ)'); return false; }
      return { qty: parseInt(raw, 10), note: ($('#scModalNote').val() || '').trim() };
    }
  }).then(function(res) {
    if (res.isConfirmed && res.value) {
      $r.find('.sc-counted').val(res.value.qty);
      $r.find('.sc-note').val(res.value.note);
      scRenderTotal($r); scStampCountedBy($r); scMarkDirty($r); scGoToRow($r); scFlash($r);
    }
    setTimeout(function() { $('#scScanInput').val('').focus(); }, 60);
  });
}

// ສະແກນສິນຄ້າທີ່ນັບແລ້ວ (ມີຍອດລວມທີ່ບັນທຶກ ຫຼື ກຳລັງປ້ອນຢູ່ໃນຮອບນີ້) → ແຈ້ງເຕືອນກ່ອນ ພ້ອມຍອດລວມ/ຜູ້ນັບ/ເວລາ.
// ເລືອກ "ນັບເພີ່ມ" ຈຶ່ງເປີດຟອມ (ນັບຫຼາຍບ່ອນ ລວມກັນ). ປຸ່ມເລີ່ມຕົ້ນ = ຍົກເລີກ ແລະ Enter ບໍ່ກົດ "ນັບເພີ່ມ" —
// ເຄື່ອງສະແກນສົ່ງ Enter ຕໍ່ທ້າຍທຸກຄັ້ງ, ສະແກນຊ້ຳໄວໆ ຈະບໍ່ບວກເພີ່ມໂດຍບໍ່ຕັ້ງໃຈ.
function scWarnAlreadyCounted($r, onContinue) {
  var esc = function(s) { return $('<div>').text(s).html(); };
  var saved = scSaved($r), pending = !!scDirty[$r.attr('data-id')];
  var rawU = $.trim($r.find('.sc-c-unit').text()), unit = esc(rawU && rawU !== '-' ? rawU : '');
  var by = $.trim($r.find('.sc-counted-by').text().replace(/\s+/g, ' '));
  Swal.fire({
    icon: 'warning',
    title: 'ສິນຄ້ານີ້ນັບແລ້ວ',
    html: '<div class="font-weight-bold text-dark mb-2">' + esc($.trim($r.find('.sc-c-name').text())) + '</div>' +
          '<div class="text-left p-2 rounded border" style="background:#f8fafc; font-size:0.92rem; line-height:1.8;">' +
            'ຍອດລວມທີ່ນັບແລ້ວ: <b>' + (saved === null ? '-' : scFmt(saved) + ' ' + unit) + '</b>' +
            (by && by !== '-' ? '<br>ນັບເມື່ອ / ຜູ້ນັບ: <b>' + esc(by) + '</b>' : '') +
            (pending ? '<br><span class="text-warning font-weight-bold"><i class="fas fa-circle mr-1" style="font-size:8px;"></i>ມີຈຳນວນຮອບນີ້ທີ່ຍັງບໍ່ບັນທຶກ</span>' : '') +
          '</div>' +
          '<div class="small text-muted mt-2">ຖ້ານັບເພີ່ມຈາກບ່ອນອື່ນ ກົດ <b>ນັບເພີ່ມ</b> — ຈຳນວນຈະບວກໃສ່ຍອດລວມ</div>',
    showCancelButton: true, reverseButtons: true, focusCancel: true, allowEnterKey: false,
    confirmButtonText: '<i class="fas fa-plus mr-1"></i> ນັບເພີ່ມ',
    cancelButtonText: '<i class="fas fa-times mr-1"></i> ຍົກເລີກ',
    confirmButtonColor: '#2563eb', cancelButtonColor: '#64748b'
  }).then(function(res) {
    if (res.isConfirmed) onContinue();
    else setTimeout(function() { $('#scScanInput').val('').focus(); }, 60);
  });
}

// ສະແກນ: Enter → ຫາແຖວທີ່ບາໂຄ້ດ/ລະຫັດກົງກັນ ແລ້ວເປີດຟອມປ້ອນຈຳນວນຕົວຈິງ
// (ສິນຄ້າທີ່ນັບແລ້ວ → ແຈ້ງເຕືອນ "ສິນຄ້ານີ້ນັບແລ້ວ" ກ່ອນ)
function scHandleScan() {
  var code = ($('#scScanInput').val() || '').trim();
  if (!code) return;
  var lc = code.toLowerCase();
  var $hit = $('.sc-row').filter(function() {
    return $(this).attr('data-barcode') === lc || String($(this).attr('data-pid')) === code;
  }).first();
  if (!$hit.length) {
    var matched = scRender();
    if (matched.length === 1) $hit = $(matched[0]);
  }
  if ($hit.length) {
    $('#scScanInput').val('');
    scGoToRow($hit); scFlash($hit);
    if (SC_EDITABLE) {
      scBeep();
      if (scSaved($hit) !== null || scDirty[$hit.attr('data-id')]) {
        scWarnAlreadyCounted($hit, function() { scOpenCountModal($hit); });
      } else {
        scOpenCountModal($hit);
      }
    } else if (scLive($hit) !== null) {
      // ອ່ານຢ່າງດຽວ (ໃບສົ່ງກວດ/ນັບແລ້ວ): ບອກໃຫ້ຮູ້ວ່ານັບແລ້ວ ພ້ອມຍອດລວມ
      Swal.fire({ icon: 'info', title: 'ສິນຄ້ານີ້ນັບແລ້ວ',
        html: $('<div>').text($.trim($hit.find('.sc-c-name').text())).html() + '<br>ຍອດລວມ: <b>' + scFmt(scLive($hit)) + '</b>',
        timer: 2500, showConfirmButton: false, didClose: function() { $('#scScanInput').focus(); } });
    }
    return;
  }
  if (/[^\x20-\x7E]/.test(code) && window.showSwitchKeyboardAlert) {
    window.showSwitchKeyboardAlert(code, document.getElementById('scScanInput'), function() { $('#scScanInput').val('').focus(); scRender(); });
    return;
  }
  Swal.fire({
    icon: 'warning', title: 'ບໍ່ພົບສິນຄ້າ', html: 'ລະຫັດ: ' + $('<div>').text(code).html(),
    showConfirmButton: true, confirmButtonText: '<i class="fas fa-check mr-1"></i> ຕົກລົງ', confirmButtonColor: '#f59e0b',
    allowOutsideClick: false, allowEscapeKey: false, allowEnterKey: false, focusConfirm: false,
    didClose: function() { $('#scScanInput').focus().select(); }
  });
}

