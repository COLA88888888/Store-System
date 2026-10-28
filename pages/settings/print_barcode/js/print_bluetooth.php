<?php
/*
 * ສ່ວນ: ພິມບາໂຄ້ດຜ່ານເຄື່ອງພິມ Bluetooth
 * ເຄື່ອງພິມ Bluetooth ໃດທີ່ເຊື່ອມຕໍ່ຢູ່ (ຕັ້ງຄ່າ › ເຄື່ອງພິມ) → ສະຕິກເກີບາໂຄ້ດພິມອອກເຄື່ອງນັ້ນເລີຍ ບໍ່ຕ້ອງເປີດໜ້າຕ່າງພິມ.
 * - ແຕ້ມແຕ່ລະດວງເປັນຮູບ (canvas, 8 ຈຸດ/mm = 203dpi) ຕາມຂະໜາດ ແລະ ຕົວເລືອກດຽວກັບໜ້າຕ່າງພິມ
 * - ສົ່ງເປັນ TSPL (ເຄື່ອງພິມສະຕິກເກີ ເຊັ່ນ XP-480B / XP-365B — ຈັບຮ່ອງສະຕິກເກີເອງ)
 *   ຫຼື ESC/POS raster (ເຄື່ອງພິມບິນ) — ເລືອກໄດ້ທີ່ແຖບລຸ່ມ, ຈື່ໄວ້ຕາມຊື່ເຄື່ອງພິມ
 * - ຂະໜາດ A4 ຫຼື ເລືອກ "ໜ້າຕ່າງພິມ" → ພິມແບບເດີມ (iframe)
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ pages/settings/print_barcode/print_barcode_js.php ຕໍ່ຈາກ js/print_execute.php.
 */
?>
var BC_BT_LANG_KEY = 'StoreMini.bc_bt_lang';
var BC_DOTS_MM = 8;                       // 203dpi
var BC_PX = BC_DOTS_MM * 25.4 / 96;       // 1 CSS px → ຈຸດ
var BC_FONT = '"Noto Sans Lao", "Phetsarath OT", "Leelawadee UI", "Saysettha OT", sans-serif';

function bcBtPrinter() {
  var P = null;
  try { P = window.top.MiniposBtPrinter; } catch (e) {}
  return P || window.MiniposBtPrinter || null;
}
function bcBtLangs() { try { return JSON.parse(localStorage.getItem(BC_BT_LANG_KEY) || '{}') || {}; } catch (e) { return {}; } }

// ຕົວເລືອກ "ພິມໄປ": Bluetooth (TSPL / ESC/POS) ຫຼື ໜ້າຕ່າງພິມ
function bcBtPaintTarget() {
  var P = bcBtPrinter(), info = P && P.info ? P.info() : null;
  var $wrap = $('#bcBtTargetWrap'), $sel = $('#bcBtTarget');
  if (!info || !info.connected || !info.name) { $wrap.addClass('d-none').removeClass('d-flex'); $sel.val('browser'); return; }
  var name = info.name, saved = bcBtLangs()[name];
  var def = saved || (/XP-?\d{3}B|TSC|label|sticker/i.test(name) ? 'tspl' : 'escpos');
  var esc = function (t) { return $('<div>').text(t).html(); };
  $sel.html(
    '<option value="tspl">' + esc(name) + ' — ເຄື່ອງພິມສະຕິກເກີ (TSPL)</option>' +
    '<option value="escpos">' + esc(name) + ' — ເຄື່ອງພິມບິນ (ESC/POS)</option>' +
    '<option value="browser">ໜ້າຕ່າງພິມ (ເຄື່ອງພິມ Windows)</option>'
  ).val(def).data('printer', name);
  $wrap.removeClass('d-none').addClass('d-flex');
}
$(document).on('change', '#bcBtTarget', function () {
  var name = $(this).data('printer'), v = $(this).val(), m = bcBtLangs();
  if (!name) return;
  m[name] = v;
  try { localStorage.setItem(BC_BT_LANG_KEY, JSON.stringify(m)); } catch (e) {}
});

function bcCss(css) {
  var num = function (re, d) { var m = re.exec(css || ''); return m ? parseFloat(m[1]) : d; };
  var mg = /margin:\s*([\d.]+)(?:px)?\s+[\d.]+(?:px)?\s+([\d.]+)(?:px)?/.exec(css || '');
  return {
    fs: num(/font-size:\s*([\d.]+)px/, 10),
    lh: num(/line-height:\s*([\d.]+)/, 1.1),
    maxW: num(/max-width:\s*([\d.]+)mm/, 0),
    mt: mg ? parseFloat(mg[1]) : 0,
    mb: mg ? parseFloat(mg[2]) : 0
  };
}

// ຕັດຂໍ້ຄວາມໃຫ້ພໍດີຄວາມກວ້າງ (…) ຄືກັບ text-overflow: ellipsis
function bcFit(ctx, text, maxW) {
  if (ctx.measureText(text).width <= maxW) return text;
  var s = text;
  while (s.length > 1 && ctx.measureText(s + '…').width > maxW) s = s.slice(0, -1);
  return s + '…';
}

// ແຕ້ມ 1 ດວງ ລົງ ctx ທີ່ (x0, y0) ຂະໜາດ wD x hD ຈຸດ
function bcDrawLabel(ctx, x0, y0, wD, hD, item, cfg, o) {
  var p = item.product;
  var code = (p.barcode && String(p.barcode).trim() !== '') ? String(p.barcode).trim() : String(p.product_id);
  var name = (p.product_name || '') + (p.unit ? ' (' + p.unit + ')' : '');
  var price = Number(p.price || 0).toLocaleString('en-US') + ' <?php echo kipSymbol(); ?>';
  var shopC = bcCss(cfg.shopTitleCss), prodC = bcCss(cfg.prodTitleCss), priceC = bcCss(cfg.priceTitleCss);
  var textMaxW = Math.min(wD - 8, (prodC.maxW || (wD / BC_DOTS_MM - 3)) * BC_DOTS_MM);

  // ບາໂຄ້ດ: ຄວາມກວ້າງແທ່ງເປັນຈຳນວນເຕັມຈຸດ + ຂອບຂາວ 10 ແທ່ງ (ຄືໜ້າຕ່າງພິມ)
  // ຄວາມສູງແທ່ງ = 50/70 ຂອງຄ່າທີ່ຕັ້ງ (ຄືໜ້າຕ່າງພິມ), ບໍ່ມີຂອບຂາວເທິງ-ລຸ່ມ → ເລກບາໂຄ້ດຢູ່ໃກ້ແທ່ງ
  var QUIET = 10, maxBcD = Math.round(o.barcodeWidthMm * BC_DOTS_MM), bcH = Math.round(o.barcodeHeightMm * BC_DOTS_MM * 50 / 70);
  var probe = document.createElementNS('http://www.w3.org/2000/svg', 'svg'), units = 0, bc = null, mod = 1;
  try {
    JsBarcode(probe, code, { format: 'CODE128', width: 1, height: 10, displayValue: false, margin: QUIET });
    units = parseFloat((probe.getAttribute('viewBox') || '').split(' ')[2]) || parseFloat(probe.getAttribute('width')) || 0;
    mod = Math.max(1, Math.floor(maxBcD / (units || 1)));
    bc = document.createElement('canvas');
    JsBarcode(bc, code, { format: 'CODE128', width: mod, height: bcH, displayValue: false, margin: QUIET * mod, marginTop: 0, marginBottom: 0, background: '#ffffff', lineColor: '#000000' });
  } catch (e) { bc = null; }

  var rows = [];
  if (o.showShop) rows.push({ t: SHOP_NAME || '', c: shopC, w: 'bold' });
  if (o.showName) rows.push({ t: name, c: prodC, w: 'bold' });
  rows.push({ bc: true });
  if (o.showCode) rows.push({ t: code, c: { fs: 14, lh: 0.75, mt: 1.5, mb: 2.3 } /* ສູງໂດຍປະມານ ໃຊ້ຈັດເຄິ່ງກາງ: 0.4mm + ຕົວເລກ + 0.6mm */, w: '800', mono: true });
  if (o.showPrice) rows.push({ t: price, c: priceC, w: '800' });

  var hOf = function (r) { return r.bc ? (bc ? bc.height : bcH) : (r.c.fs * r.c.lh + r.c.mt + r.c.mb) * BC_PX; };
  var total = rows.reduce(function (s, r) { return s + hOf(r); }, 0);
  var y = y0 + Math.max(0, (hD - total) / 2) + (o.verticalOffsetMm || 0) * BC_DOTS_MM;
  var cx = x0 + wD / 2;

  ctx.fillStyle = '#000';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  rows.forEach(function (r) {
    if (r.bc) {
      if (bc) {
        var dw = Math.min(bc.width, wD);
        ctx.drawImage(bc, Math.round(cx - dw / 2), Math.round(y), dw, bc.height);
      }
      y += hOf(r);
      return;
    }
    var fsD = r.c.fs * BC_PX;
    var maxW = textMaxW;
    if (r.mono) {   // ເລກໃຕ້ບາໂຄ້ດ: ໂຕໃຫຍ່ (14px) ຫຍໍ້ລົງພໍດີກວ້າງບາໂຄ້ດ, ວາງຕິດແທ່ງ ຫ່າງ 0.4mm ວັດຈາກຂອບເທິງຕົວເລກແທ້
      maxW = Math.min(wD - 4, bc ? bc.width : maxBcD);
      var fs = 14;
      ctx.font = r.w + ' ' + (fs * BC_PX) + 'px monospace';
      while (ctx.measureText(r.t).width > maxW && fs > 6) { fs -= 0.5; ctx.font = r.w + ' ' + (fs * BC_PX) + 'px monospace'; }
      var mt = ctx.measureText(r.t);
      var asc = mt.actualBoundingBoxAscent || fs * BC_PX * 0.72, desc = mt.actualBoundingBoxDescent || 0;
      ctx.textBaseline = 'alphabetic';
      y += Math.round(0.4 * BC_DOTS_MM);
      ctx.fillText(r.t, cx, y + asc);
      ctx.textBaseline = 'middle';
      y += asc + desc + Math.round(0.6 * BC_DOTS_MM);
      return;
    } else {
      ctx.font = r.w + ' ' + fsD + 'px ' + BC_FONT;
    }
    y += r.c.mt * BC_PX;
    ctx.fillText(r.mono ? r.t : bcFit(ctx, r.t, maxW), cx, y + fsD * r.c.lh / 2);
    y += fsD * r.c.lh + r.c.mb * BC_PX;
  });
}

// ສ້າງໜ້າສະຕິກເກີ (canvas) ທັງໝົດ: single_roll = 1 ດວງ/ໜ້າ, 3up_roll = 3 ດວງ/ແຖວ
function bcBuildPages(items, cfg, o) {
  var m = /size:\s*([\d.]+)mm\s+([\d.]+)mm/.exec(cfg.pageCss || '');
  var wMm = m ? parseFloat(m[1]) : 40, hMm = m ? parseFloat(m[2]) : 30;
  var labels = [];
  items.forEach(function (it) { for (var i = 0; i < it.copies; i++) labels.push(it); });
  var per = cfg.mode === '3up_roll' ? 3 : 1, pages = [];
  for (var k = 0; k < labels.length; k += per) {
    var c = document.createElement('canvas');
    c.width = Math.round(wMm * BC_DOTS_MM); c.height = Math.round(hMm * BC_DOTS_MM);
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
    if (per === 3) {   // 105x25: ຂອບ 1.5mm, ດວງກວ້າງ 32mm ຫ່າງກັນ 3mm (ຄື grid ຂອງໜ້າຕ່າງພິມ)
      for (var j = 0; j < 3 && labels[k + j]; j++) {
        bcDrawLabel(ctx, Math.round((1.5 + j * 35) * BC_DOTS_MM), 0, 32 * BC_DOTS_MM, Math.round(24.5 * BC_DOTS_MM), labels[k + j], cfg, o);
      }
    } else {
      bcDrawLabel(ctx, 0, 0, c.width, c.height, labels[k], cfg, o);
    }
    pages.push(c);
  }
  return { pages: pages, wMm: wMm, hMm: hMm, count: labels.length };
}

function bcAscii(s) { var a = new Uint8Array(s.length); for (var i = 0; i < s.length; i++) a[i] = s.charCodeAt(i) & 0xff; return a; }
function bcConcat(parts) {
  var n = parts.reduce(function (s, p) { return s + p.length; }, 0), out = new Uint8Array(n), off = 0;
  parts.forEach(function (p) { out.set(p, off); off += p.length; });
  return out;
}
// canvas → 1 bit ຕໍ່ຈຸດ (1 = ດຳ)
function bcBits(canvas) {
  var w = canvas.width, h = canvas.height, px = canvas.getContext('2d').getImageData(0, 0, w, h).data;
  var bw = (w + 7) >> 3, bits = new Uint8Array(bw * h);
  for (var y = 0; y < h; y++) for (var x = 0; x < w; x++) {
    var i = (y * w + x) << 2;
    if (px[i + 3] >= 128 && (px[i] * 299 + px[i + 1] * 587 + px[i + 2] * 114) < 160000) bits[y * bw + (x >> 3)] |= (0x80 >> (x & 7));
  }
  return { bits: bits, bw: bw, h: h };
}

// TSPL: ຕັ້ງຂະໜາດ + ຮ່ອງ 1 ເທື່ອ, ແລ້ວ CLS → BITMAP → PRINT ຕໍ່ດວງ (BITMAP ຂອງ TSPL: 0 = ດຳ)
function bcTsplHeader(wMm, hMm, gapMm) {
  return bcAscii('SIZE ' + wMm + ' mm,' + hMm + ' mm\r\nGAP ' + gapMm + ' mm,0 mm\r\nDIRECTION 1,0\r\nREFERENCE 0,0\r\nOFFSET 0 mm\r\nSET TEAR ON\r\n');
}
function bcTsplPage(canvas) {
  var b = bcBits(canvas), inv = new Uint8Array(b.bits.length);
  for (var i = 0; i < inv.length; i++) inv[i] = (~b.bits[i]) & 0xff;
  return bcConcat([bcAscii('CLS\r\nBITMAP 0,0,' + b.bw + ',' + b.h + ',0,'), inv, bcAscii('\r\nPRINT 1,1\r\n')]);
}
// ESC/POS: ຮູບຕໍ່ເນື່ອງ (GS v 0) ບໍ່ຕັດເຈ້ຍ
function bcEscposPage(canvas) {
  var b = bcBits(canvas), parts = [];
  for (var s = 0; s < b.h; s += 24) {
    var rows = Math.min(24, b.h - s), band = new Uint8Array(8 + b.bw * rows);
    band.set([0x1d, 0x76, 0x30, 0x00, b.bw & 0xff, (b.bw >> 8) & 0xff, rows & 0xff, (rows >> 8) & 0xff], 0);
    band.set(b.bits.subarray(s * b.bw, (s + rows) * b.bw), 8);
    parts.push(band);
  }
  return bcConcat(parts);
}

// ເອີ້ນຈາກ executePageBarcodePrint: true = ພິມຜ່ານ Bluetooth ແລ້ວ (ບໍ່ຕ້ອງເປີດໜ້າຕ່າງພິມ)
function barcodeBluetoothPrint(items, cfg, o) {
  var P = bcBtPrinter(), lang = $('#bcBtTarget').val();
  if (!P || !P.info || !P.info().connected || !lang || lang === 'browser' || cfg.mode === 'grid_a4') return false;
  if (typeof P.write !== 'function') {
    Swal.fire({ icon: 'info', title: 'ກະລຸນາໂຫຼດໜ້າໃໝ່', text: 'ກົດ Ctrl+F5 ເພື່ອໂຫຼດລະບົບເຄື່ອງພິມ Bluetooth ລຸ້ນໃໝ່ ແລ້ວພິມອີກຄັ້ງ' });
    return true;
  }

  var built = bcBuildPages(items, cfg, o);
  var gapMm = o.labelPitchMm > built.hMm ? +(o.labelPitchMm - built.hMm).toFixed(2) : 2;
  // ແບ່ງສົ່ງເປັນຊຸດ (~300 KB) ບໍ່ໃຫ້ຄຳຂໍດຽວໃຫຍ່/ດົນເກີນ
  var batches = [], cur = [], curLen = 0;
  built.pages.forEach(function (c) {
    var part = lang === 'tspl' ? bcTsplPage(c) : bcEscposPage(c);
    if (curLen + part.length > 300000 && cur.length) { batches.push(cur); cur = []; curLen = 0; }
    cur.push(part); curLen += part.length;
  });
  if (cur.length) batches.push(cur);

  var name = P.info().name;
  Swal.fire({ title: 'ກຳລັງພິມ ' + built.count + ' ດວງ…', text: name, allowOutsideClick: false, showConfirmButton: false, didOpen: function () { Swal.showLoading(); } });
  var chain = Promise.resolve();
  batches.forEach(function (parts, i) {
    chain = chain.then(function () {
      var head = lang === 'tspl' ? bcTsplHeader(built.wMm, built.hMm, gapMm) : (i === 0 ? new Uint8Array([0x1b, 0x40]) : new Uint8Array(0));
      return P.write(bcConcat([head].concat(parts)));
    });
  });
  chain.then(function () {
    clearPageBarcodeSelection();   // ພິມສຳເລັດ → ເອົາຕິກອອກ (ບໍ່ສຳເລັດ → ຄ້າງໄວ້ ພິມໃໝ່ໄດ້)
    Swal.fire({ icon: 'success', title: 'ພິມແລ້ວ ' + built.count + ' ດວງ', text: name, timer: 1800, showConfirmButton: false });
  }, function (err) {
    Swal.fire({ icon: 'error', title: 'ພິມຜ່ານ Bluetooth ບໍ່ສຳເລັດ', text: ((err && err.message) || '') + ' — ລອງເລືອກ "ໜ້າຕ່າງພິມ" ແທນ' });
  });
  return true;
}

$(function () {
  bcBtPaintTarget();
  var P = bcBtPrinter();
  if (P && P.onStatus) {
    P.onStatus(bcBtPaintTarget);
    window.addEventListener('pagehide', function () { if (P.offStatus) P.offStatus(bcBtPaintTarget); });
  }
});
