/*
 * bluetooth-printer-escpos.js — ແປງຮູບໃບບິນເປັນຄຳສັ່ງ ESC/POS (raster "GS v 0")
 * ແຍກອອກຈາກ assets/js/bluetooth-printer.js (ສ່ວນ "ESC/POS") — ຕ້ອງໂຫຼດໄຟລ໌ນີ້ກ່ອນ bluetooth-printer.js.
 *
 * window.MiniposEscPos:
 *   concat(parts)               → Uint8Array   ຕໍ່ຫຼາຍທ່ອນເປັນອັນດຽວ
 *   loadCanvas(canvasOrDataUri) → Promise<canvas>
 *   rasterBytes(canvas, maxDots) → Uint8Array  ຮູບ → ESC/POS raster (GS v 0 ຕໍ່ເນື່ອງຄືເຄື່ອງພິມທົ່ວໄປ)
 *   testCanvas(dots, name)       → canvas      ຮູບໃບທົດສອບ (ທຸກພາສາ)
 *   BLE_SERVICES, findWritable(server)          UUID service ເຄື່ອງພິມ BLE ແລະ ຫາຊ່ອງສົ່ງຂໍ້ມູນ
 */
(function (window) {
  'use strict';

  var RASTER_BAND = 24;       // 24 ແຖວຈຸດ (3 mm) ຕໍ່ຄຳສັ່ງ GS v 0 (ພໍດີກັບ Buffer ເຄື່ອງພິມ ບໍ່ໃຫ້ລົ້ນເປັນພາສາຕ່າງດ້າວ)

  function concat(parts) {
    var len = parts.reduce(function (n, p) { return n + p.length; }, 0), out = new Uint8Array(len), o = 0;
    parts.forEach(function (p) { out.set(p, o); o += p.length; });
    return out;
  }

  function loadCanvas(src) {
    if (src && src.getContext) return Promise.resolve(src);
    return new Promise(function (resolve, reject) {
      var img = new Image();
      img.onload = function () {
        var c = document.createElement('canvas');
        c.width = img.naturalWidth; c.height = img.naturalHeight;
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0);
        resolve(c);
      };
      img.onerror = function () { reject(new Error('' + (window.__tl||String)("ໂຫຼດຮູບໃບບິນບໍ່ໄດ້") + '')); };
      img.src = src;
    });
  }

  // ຮູບ → TSPL (ເຄື່ອງພິມສະຕິກເກີ ເຊັ່ນ XP-480B ທີ່ບໍ່ຮູ້ຈັກ ESC/POS): 1 ໜ້າ ສູງເທົ່າຮູບ, BITMAP ຂອງ TSPL 0 = ດຳ
  function tsplBytes(canvas, maxDots, gapMm) {
    var r = toBits(canvas, maxDots), inv = new Uint8Array(r.bits.length);
    for (var i = 0; i < inv.length; i++) inv[i] = (~r.bits[i]) & 0xff;
    var mm = function (d) { return Math.round(d / 8 * 10) / 10; };   // 203dpi = 8 ຈຸດ/mm
    var ascii = function (s) { var a = new Uint8Array(s.length); for (var k = 0; k < s.length; k++) a[k] = s.charCodeAt(k) & 0xff; return a; };
    return concat([
      ascii('SIZE ' + mm(r.bw * 8) + ' mm,' + mm(r.h) + ' mm\r\nGAP ' + (gapMm == null ? 2 : gapMm) + ' mm,0 mm\r\nDIRECTION 1,0\r\nCLS\r\nBITMAP 0,0,' + r.bw + ',' + r.h + ',0,'),
      inv,
      ascii('\r\nPRINT 1,1\r\n')
    ]);
  }

  // ຮູບ → 1 bit ຕໍ່ຈຸດ (1 = ດຳ), ປັບຄວາມກວ້າງເປັນ maxDots
  function toBits(canvas, maxDots) {
    var w = canvas.width, h = canvas.height;
    if (maxDots && w !== maxDots) {   // ປັບຄວາມກວ້າງໃຫ້ກົງກັບຈຸດຫົວພິມ (384 ຫຼື 576 dots)
      var s = document.createElement('canvas');
      s.width = maxDots; s.height = Math.max(1, Math.round(h * maxDots / w));
      var sctx = s.getContext('2d');
      sctx.fillStyle = '#fff'; sctx.fillRect(0, 0, s.width, s.height);
      sctx.drawImage(canvas, 0, 0, s.width, s.height);
      canvas = s; w = s.width; h = s.height;
    }
    var px = canvas.getContext('2d').getImageData(0, 0, w, h).data;
    var bw = (w + 7) >> 3, bits = new Uint8Array(bw * h);
    for (var y = 0; y < h; y++) {
      var rowOff = y * bw, pxRow = y * w * 4;
      for (var x = 0; x < w; x++) {
        var i = pxRow + (x << 2);
        if (px[i + 3] >= 128 && (px[i] * 299 + px[i + 1] * 587 + px[i + 2] * 114) < 160000) {
          bits[rowOff + (x >> 3)] |= (0x80 >> (x & 7));
        }
      }
    }
    return { bits: bits, bw: bw, h: h };
  }

  // ຮູບ → ESC/POS raster (1 bit ຕໍ່ຈຸດ, ດຳ = ພິມ) ແບບ GS v 0 ຄືກັບເຄື່ອງພິມທົ່ວໄປ/LAN
  function rasterBytes(canvas, maxDots) {
    var r = toBits(canvas, maxDots), bits = r.bits, bw = r.bw, h = r.h;

    // ສົ່ງເປັນຊຸດ GS v 0 ຄວາມກວ້າງຄົງທີ່ (48 bytes = 384 dots) ຄົບທຸກແຖວ ເພື່ອບໍ່ໃຫ້ QR ແລະ ຂໍ້ຄວາມທ້າຍບິນຜິດພ້ຽນ
    var parts = [];
    for (var sRow = 0; sRow < h; sRow += RASTER_BAND) {
      var rows = Math.min(RASTER_BAND, h - sRow);
      var band = new Uint8Array(8 + bw * rows);
      band[0] = 0x1d; band[1] = 0x76; band[2] = 0x30; band[3] = 0x00;
      band[4] = bw & 0xff; band[5] = (bw >> 8) & 0xff;
      band[6] = rows & 0xff; band[7] = (rows >> 8) & 0xff;
      band.set(bits.subarray(sRow * bw, (sRow + rows) * bw), 8);
      parts.push(band);
    }
    return concat(parts);
  }

  // ຮູບໃບທົດສອບ: ຂໍ້ຄວາມພາສາລາວ + ຕົວຢ່າງທຸກພາສາທີ່ລະບົບມີ (ລາວ/ໄທ/ຈີນ/ອັງກິດ)
  function testCanvas(dots, printerName) {
    var c = document.createElement('canvas');
    c.width = dots; c.height = 250;
    var ctx = c.getContext('2d');
    var font = '"Noto Sans Lao", "Phetsarath OT", "Leelawadee UI", "Microsoft YaHei", sans-serif';
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
    ctx.fillStyle = '#000'; ctx.textAlign = 'center';
    ctx.font = 'bold 30px ' + font;
    ctx.fillText('' + (window.__tl||String)("ທົດສອບເຄື່ອງພິມ") + '', dots / 2, 50);
    ctx.font = '22px ' + font;
    ctx.fillText('' + (window.__tl||String)("ພິມໄດ້ປົກກະຕິ") + '', dots / 2, 92);
    ctx.fillText('' + (window.__tl||String)("ລາວ") + ' · ไทย · 中文 · English', dots / 2, 127);
    ctx.fillText(printerName || '', dots / 2, 162);
    var d = new Date(), p2 = function (n) { return (n < 10 ? '0' : '') + n; };
    ctx.fillText(p2(d.getDate()) + '/' + p2(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + p2(d.getHours()) + ':' + p2(d.getMinutes()), dots / 2, 197);
    ctx.fillRect(10, 230, dots - 20, 3);
    return c;
  }

  // UUID ຂອງ service ທີ່ເຄື່ອງພິມ BLE ນິຍົມໃຊ້ (ຕ້ອງລະບຸລ່ວງໜ້າ ບໍ່ດັ່ງນັ້ນ browser ບໍ່ໃຫ້ເຂົ້າເຖິງ)
  var BLE_SERVICES = [
    '000018f0-0000-1000-8000-00805f9b34fb',   // ເຄື່ອງພິມຈີນທົ່ວໄປ (Xprinter, Goojprt, MTP…)
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',   // ຫຼາຍຍີ່ຫໍ້ (Peripage, Phomemo…)
    '49535343-fe7d-4ae5-8fa9-9fafd205e455',   // ISSC transparent UART
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '0000fee7-0000-1000-8000-00805f9b34fb',
    '0000ae30-0000-1000-8000-00805f9b34fb',
    '0000ffe0-0000-1000-8000-00805f9b34fb',
    '0000ff10-0000-1000-8000-00805f9b34fb'
  ];

  // ຫາຊ່ອງສົ່ງຂໍ້ມູນ (characteristic ທີ່ຂຽນໄດ້) ຂອງເຄື່ອງພິມ BLE
  function findWritable(server) {
    return server.getPrimaryServices().then(function (services) {
      var i = 0;
      function nextService() {
        if (i >= services.length) throw new Error('' + (window.__tl||String)("ບໍ່ພົບຊ່ອງສົ່ງຂໍ້ມູນຂອງເຄື່ອງພິມນີ້ (ເຄື່ອງນີ້ອາດບໍ່ແມ່ນ") + ' BLE — ' + (window.__tl||String)("ລອງແບບ COM port)") + '');
        var svc = services[i++];
        return svc.getCharacteristics().then(function (chars) {
          for (var k = 0; k < chars.length; k++) {
            var p = chars[k].properties;
            if (p.write || p.writeWithoutResponse) return chars[k];
          }
          return nextService();
        }, nextService);
      }
      return nextService();
    });
  }

  window.MiniposEscPos = { concat: concat, loadCanvas: loadCanvas, rasterBytes: rasterBytes, tsplBytes: tsplBytes, testCanvas: testCanvas,
    BLE_SERVICES: BLE_SERVICES, findWritable: findWritable };
  try { if (window.top && window.top !== window) window.top.MiniposEscPos = window.MiniposEscPos; } catch (e) {}
})(window);
