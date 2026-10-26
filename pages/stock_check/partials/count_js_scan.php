<?php
/*
 * ສ່ວນ: JS ໜ້ານັບສິນຄ້າ — ສະແກນບາໂຄ້ດ
 * scHandleScan(): Enter ໃນຊ່ອງສະແກນ → ຫາແຖວຕາມບາໂຄ້ດ/ລະຫັດ
 * - ນັບແລ້ວບໍ່ໃຫ້ໂຊຢູ່ນິ: ລາຍການທີ່ນັບແລ້ວຈະໄປຢູ່ "ປະຫວັດການນັບ"
 * - ຖ້າສະແກນສິນຄ້າທີ່ນັບຄົບແລ້ວ: ແຈ້ງເຕືອນ "ສິນຄ້ານີ້ນັບຄົບແລ້ວ! (ຢູ່ໃນປະຫວັດການນັບ)" ໂດຍບໍ່ເອົາມາໂຊໃນຕາຕະລາງນີ້
 * - ຖ້າມີການຂາຍ/ຕັດສະຕັອກ: ສະແກນກໍຂຶ້ນມາອີກ ບໍ່ໃຫ້ເຕືອນວ່າຄົບ ເພື່ອໃຫ້ນັບໃໝ່
 * - ພະນັກງານປ້ອນຈຳນວນຕົວຈິງ ແລ້ວກົດບັນທຶກຈິ່ງສະແດງສ່ວນຕ່າງ
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

    var cur = scLive($hit);
    var sys = parseInt($hit.attr('data-system'), 10) || 0;
    var saved = scSaved($hit);
    // "ນັບຄົບແລ້ວ!" ບອກວ່າເທົ່າກັບລະບົບ — ສະເພາະຜູ້ທີ່ເຫັນລະບົບ (ພະນັກງານນັບແບບບໍ່ຮູ້ຍອດ)
    var isAlreadyComplete = SC_SHOW_SYSTEM && (cur !== null && cur === sys && sys > 0 && !scDirty[$hit.attr('data-id')]);

    // 1. ກວດສອບສະຕັອກລ່າສຸດ ແລະ ສະຖານະການຂາຍກັບເຊີບເວີ
    $.post(SC_BACKEND, { action: 'check_item_stock', check_id: SC_CHECK_ID, barcode: code }, function(res) {
      // ພະນັກງານ (ນັບແບບບໍ່ເຫັນລະບົບ): ເຊີເວີບໍ່ສົ່ງສະຕັອກລ່າສຸດ — ບໍ່ອັບເດດ/ບໍ່ແຈ້ງຫຍັງ
      if (res && res.success && res.item && SC_SHOW_SYSTEM && res.live_qty !== undefined) {
        var it = res.item;
        var liveQty = parseInt(res.live_qty, 10);
        $hit.attr('data-system', liveQty);
        $hit.find('.sc-system-qty').text(scFmt(liveQty));

        var curVal = scLive($hit);
        var isCut = !!res.stock_cut;

        if (isCut) {
          // ຖ້າມີການຂາຍ ຕັດສະຕັອກ: ສະແກນກໍຂຶ້ນມາອີກ ບໍ່ໃຫ້ເຕືອນວ່າຄົບ!
          $hit.attr('data-saved', ''); // ຣີເຊັດສະຖານະບັນທຶກ ເພື່ອໃຫ້ນັບໃໝ່
          scMarkDirty($hit);
          $hit.detach().prependTo($('#scItemsBody'));
          scPage = 1;
          scRender();
          scGoToRow($hit);
          scFlash($hit);
          scBeep();
          scShowJustScannedBox($hit, curVal);

          Swal.fire({
            icon: 'info',
            title: scT('sc.s143'),
            html: '<b>' + $('<div>').text(it.product_name).html() + '</b><br>' + scT('sc.s144') + ' <b>' + scFmt(liveQty) + ' ' + (it.unit || '') + '</b><br><small class="text-muted">' + scT('sc.s145') + '</small>',
            timer: 2600,
            showConfirmButton: false
          });
        }
      }
    }, 'json');

    // 2. ຖ້ານັບຄົບແລ້ວ ແລະ ບໍ່ໄດ້ມີການຕັດສະຕັອກ: ແຈ້ງເຕືອນວ່າ "ສິນຄ້ານີ້ນັບຄົບແລ້ວ!"
    if (isAlreadyComplete) {
      scBeep();
      scShowJustScannedBox($hit, cur);
      Swal.fire({
        icon: 'success',
        title: scT('sc.s146'),
        html: '<b>' + $('<div>').text($.trim($hit.find('.sc-c-name > div:first-child').text() || $hit.find('.sc-c-name').text())).html() + '</b><br>' + scT('sc.s102') + ' <b>' + scFmt(cur) + '</b>',
        timer: 1800,
        showConfirmButton: false
      });
      setTimeout(function() { $('#scScanInput').focus().select(); }, 60);
      return;
    }

    // 3. ຖ້າເປັນລາຍການທີ່ຍັງບໍ່ທັນນັບ: ເອົາຂຶ້ນມາເທິງສຸດ, ບວກ 1 ແລະ ໝາຍວ່າກຳລັງປ້ອນ
    $hit.detach().prependTo($('#scItemsBody'));
    if (SC_EDITABLE) {
      var next = (cur === null ? 1 : cur + 1);
      $hit.find('.sc-counted').val(next);
      scStampCountedBy($hit);
      scMarkDirty($hit);
      scPaintRow($hit);
      scUpdateStats();
      scShowJustScannedBox($hit, next);
    } else {
      scShowJustScannedBox($hit, cur);
    }

    scPage = 1;
    scRender();
    scGoToRow($hit);
    scFlash($hit);
    scBeep();

    setTimeout(function() { $('#scScanInput').focus().select(); }, 60);
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
    title: scT('sc.s147'),
    html: scT('sc.s148') + ' <b>' + $('<div>').text(code).html() + '</b>',
    timer: 2000,
    showConfirmButton: false,
    didClose: function() { $('#scScanInput').focus().select(); }
  });
}
