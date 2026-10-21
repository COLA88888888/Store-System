<?php
/*
 * ສ່ວນ: JS ໜ້ານັບສິນຄ້າ — ສະແກນບາໂຄ້ດ
 * scHandleScan(): Enter ໃນຊ່ອງສະແກນ → ຫາແຖວຕາມບາໂຄ້ດ/ລະຫັດ
 * ຖ້າເປີດ "ສະແກນ = ບວກ 1": ບວກ +1 ອັດຕະໂນມັດ ພ້ອມສຽງ beep ແລະ ອັບເດດຜົນ ຄົບ/ບໍ່ພໍ/ເກີນ ທັນທີ
 * ຖ້າປິດ: ໂຟກັສຊ່ອງປ້ອນຕົວເລກຂອງແຖວນັ້ນ ເພື່ອໃຫ້ພິມຈຳນວນໄດ້ໄວ
 */
?>
function scHandleScan() {
  var code = ($('#scScanInput').val() || '').trim();
  if (!code) return;
  var lc = code.toLowerCase();
  var $hit = $('.sc-row').filter(function() {
    return $(this).attr('data-barcode') === lc || String($(this).attr('data-pid')) === code;
  }).first();

  if (!$hit.length) {
    var matched = scRender();
    if (matched && matched.length === 1) $hit = $(matched[0]);
  }

  if ($hit.length) {
    $('#scScanInput').val('');
    scGoToRow($hit);
    scFlash($hit);
    scBeep();
    if (SC_EDITABLE) {
      var $inp = $hit.find('.sc-counted');
      var cur = scLive($hit);
      var next = (cur === null ? 1 : cur + 1);
      $inp.val(next);
      scPaintRow($hit);
      scStampCountedBy($hit);
      scMarkDirty($hit);
      scUpdateStats();
      scTriggerAutoSave();
      setTimeout(function() { $('#scScanInput').focus(); }, 50);
    } else {
      var curVal = scLive($hit);
      Swal.fire({
        icon: 'info',
        title: 'ສິນຄ້ານີ້: ' + (curVal === null ? 'ຍັງບໍ່ນັບ' : 'ນັບແລ້ວ ' + scFmt(curVal)),
        text: $.trim($hit.find('.sc-c-name').text()),
        timer: 1500,
        showConfirmButton: false,
        didClose: function() { $('#scScanInput').focus(); }
      });
    }
    return;
  }

  if (/[^\x20-\x7E]/.test(code) && window.showSwitchKeyboardAlert) {
    window.showSwitchKeyboardAlert(code, document.getElementById('scScanInput'), function() { $('#scScanInput').val('').focus(); scRender(); });
    return;
  }
  if (window.showBarcodeNotFoundAlert) {
    window.showBarcodeNotFoundAlert(code, document.getElementById('scScanInput'));
    return;
  }
  Swal.fire({
    icon: 'warning',
    title: 'ບໍ່ພົບສິນຄ້າໃນໃບນີ້',
    html: 'ລະຫັດ: <b>' + $('<div>').text(code).html() + '</b>',
    timer: 2000,
    showConfirmButton: false,
    didClose: function() { $('#scScanInput').focus().select(); }
  });
}
