/**
 * MiniPos LAN & Bluetooth receipt rendering — ແປງໃບບິນ HTML ເປັນຮູບດຳ-ຂາວ ຫຼື Canvas
 * ເພື່ອໃຫ້ພາສາລາວ, ໂລໂກ້ ແລະ QR ອອກຄືກັບເຄື່ອງພິມທົ່ວໄປທຸກຢ່າງ.
 */
(function (window, document) {
  var FONT_FILES = [['Nuanta-Regular.ttf', 'Nuanta-Bold.ttf'], ['NotoSansLao-Regular.ttf', 'NotoSansLao-Bold.ttf']];
  var fontCache = null;

  function fontFaces(basePath) {
    if (fontCache) return Promise.resolve(fontCache);
    function toBase64(url) {
      return fetch(url).then(function (r) {
        if (!r.ok) throw new Error(url + ' → HTTP ' + r.status);
        return r.arrayBuffer();
      }).then(function (buf) {
        var bin = '', bytes = new Uint8Array(buf);
        for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin);
      });
    }
    function face(b64, weight) {
      return '@font-face{font-family:"Receipt Lao";src:url(data:font/ttf;base64,' + b64 +
        ') format("truetype");font-weight:' + weight + ';font-style:normal;}';
    }
    function loadPair(i) {
      if (i >= FONT_FILES.length) return Promise.reject(new Error('no receipt Lao font found in assets/fonts/'));
      var names = FONT_FILES[i];
      return toBase64(basePath + 'assets/fonts/' + names[0]).then(function (regular) {
        return toBase64(basePath + 'assets/fonts/' + names[1])
          .catch(function () { return ''; })
          .then(function (bold) { return [regular, bold]; });
      }).catch(function () { return loadPair(i + 1); });
    }
    return loadPair(0).then(function (b64) {
      var css = face(b64[0], 400);
      if (b64[1] && b64[1] !== b64[0]) css += face(b64[1], 700);
      return latinFontFaces(basePath).then(function (latin) {
        fontCache = latin + css;
        return fontCache;
      });
    });
  }

  var LATIN_FONT_FILES = [['NotoSans-Latin-400.woff2', 400], ['NotoSans-Latin-700.woff2', 700]];
  var latinCache = null;
  var LATIN_LETTERS = 'U+0041-005A,U+0061-007A,U+00C0-024F';
  function latinFontFaces(basePath) {
    if (latinCache !== null) return Promise.resolve(latinCache);
    return Promise.all(LATIN_FONT_FILES.map(function (f) {
      return fetch(basePath + 'assets/fonts/' + f[0]).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.arrayBuffer();
      }).then(function (buf) {
        var bin = '', bytes = new Uint8Array(buf);
        for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        var src = 'src:url(data:font/woff2;base64,' + btoa(bin) + ') format("woff2");font-weight:' + f[1] + ';font-style:normal;';
        return '@font-face{font-family:"Receipt Latin";' + src + '}' +
          '@font-face{font-family:"Receipt Latin";' + src + 'size-adjust:90%;unicode-range:' + LATIN_LETTERS + ';}';
      }).catch(function () { return ''; });
    })).then(function (faces) {
      latinCache = faces.join('');
      return latinCache;
    });
  }

  function paperLum(p, i) {
    var lum = (p[i] * 299 + p[i + 1] * 587 + p[i + 2] * 114) / 1000;
    return lum * (p[i + 3] / 255) + 255 * (1 - p[i + 3] / 255);
  }

  function otsuThreshold(hist, n) {
    var sum = 0, sumB = 0, wB = 0, best = -1, thr = 128;
    for (var t = 0; t < 256; t++) sum += t * hist[t];
    for (t = 0; t < 256; t++) {
      wB += hist[t];
      if (!wB) continue;
      var wF = n - wB;
      if (!wF) break;
      sumB += t * hist[t];
      var diff = sumB / wB - (sum - sumB) / wF, v = wB * wF * diff * diff;
      if (v > best) { best = v; thr = t; }
    }
    return thr;
  }

  var LOGO_BW_THRESHOLD = 215;
  function toBlackWhite(ctx, w, h) {
    var d = ctx.getImageData(0, 0, w, h), p = d.data, n = w * h, dark = 0, i;
    var lum = new Float32Array(n), hist = new Array(256).fill(0);
    for (i = 0; i < n; i++) {
      lum[i] = paperLum(p, i * 4);
      hist[Math.min(255, Math.round(lum[i]))]++;
      if (lum[i] < 128) dark++;
    }
    var invert = dark > n * 0.5;
    var thr = invert ? otsuThreshold(hist, n) : LOGO_BW_THRESHOLD;
    for (i = 0; i < n; i++) {
      var black = invert ? lum[i] > thr : lum[i] < thr;
      var v = black ? 0 : 255;
      p[i * 4] = p[i * 4 + 1] = p[i * 4 + 2] = v;
      p[i * 4 + 3] = 255;
    }
    ctx.putImageData(d, 0, 0);
  }

  var QR_MIN_PX = 600;
  function qrCanvas(img) {
    var w = img.naturalWidth || 1, h = img.naturalHeight || 1;
    var k = Math.max(1, Math.ceil(QR_MIN_PX / w));
    var c = document.createElement('canvas');
    c.width = w * k;
    c.height = h * k;
    var ctx = c.getContext('2d');
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(img, 0, 0, c.width, c.height);
    var d = ctx.getImageData(0, 0, c.width, c.height), p = d.data;
    for (var i = 0; i < p.length; i += 4) {
      var v = paperLum(p, i) < 140 ? 0 : 255;
      p[i] = p[i + 1] = p[i + 2] = v;
      p[i + 3] = 255;
    }
    ctx.putImageData(d, 0, 0);
    return c;
  }

  function cropToInk(c) {
    var w = c.width, h = c.height, p = c.getContext('2d').getImageData(0, 0, w, h).data;
    var minX = w, minY = h, maxX = -1, maxY = -1;
    for (var y = 0; y < h; y++) {
      for (var x = 0; x < w; x++) {
        if (p[(y * w + x) * 4] < 128) {
          if (x < minX) minX = x;
          if (x > maxX) maxX = x;
          if (y < minY) minY = y;
          if (y > maxY) maxY = y;
        }
      }
    }
    if (maxX < 0) return c;
    var out = document.createElement('canvas');
    out.width = maxX - minX + 1;
    out.height = maxY - minY + 1;
    out.getContext('2d').drawImage(c, minX, minY, out.width, out.height, 0, 0, out.width, out.height);
    return out;
  }

  function imageMode(img) {
    if (img.classList.contains('receipt-logo')) return 'logo';
    if (img.classList.contains('receipt-qr-img')) return 'qr';
    return '';
  }

  var imageUriCache = {};
  function imageToDataUri(src, mode) {
    var cacheKey = (mode || '') + '|' + (src || '');
    if (src && imageUriCache[cacheKey]) return Promise.resolve(imageUriCache[cacheKey]);
    return imageToDataUriRaw(src, mode).then(function (uri) {
      if (uri && src && src.indexOf('data:') !== 0) imageUriCache[cacheKey] = uri;
      return uri;
    });
  }
  function imageToDataUriRaw(src, mode) {
    return new Promise(function (resolve) {
      if (!src || (src.indexOf('data:') === 0 && !mode)) { resolve(src || ''); return; }
      var img = new Image();
      img.onload = function () {
        try {
          var c;
          if (mode === 'qr') {
            c = qrCanvas(img);
          } else {
            c = document.createElement('canvas');
            c.width = img.naturalWidth || 1;
            c.height = img.naturalHeight || 1;
            var ctx = c.getContext('2d');
            ctx.drawImage(img, 0, 0);
            if (mode) {
              toBlackWhite(ctx, c.width, c.height);
              c = cropToInk(c);
            }
          }
          resolve(c.toDataURL('image/png'));
        } catch (e) { resolve(''); }
      };
      img.onerror = function () { resolve(''); };
      img.src = src;
    });
  }

  function preDecode(uri) {
    return new Promise(function (resolve) {
      var im = new Image();
      im.onload = im.onerror = function () { resolve(); };
      im.src = uri;
      if (im.decode) im.decode().then(resolve, resolve);
    });
  }

  function convertImages(root) {
    return Promise.all(Array.prototype.map.call(root.querySelectorAll('img'), function (img) {
      if (img.style.display === 'none') return Promise.resolve();
      return imageToDataUri(img.getAttribute('src'), imageMode(img)).then(function (uri) {
        if (uri) img.setAttribute('src', uri); else img.removeAttribute('src');
        return uri ? preDecode(uri) : null;
      });
    }));
  }

  function printHtml(source) {
    var clone = source.cloneNode(true);
    return convertImages(clone).then(function () { return clone.innerHTML; });
  }

  function svgParseError(svgText) {
    try {
      var doc = new DOMParser().parseFromString(svgText, 'image/svg+xml');
      var err = doc.getElementsByTagName('parsererror')[0];
      return err ? err.textContent.replace(/\s+/g, ' ').trim().slice(0, 300) : '';
    } catch (e) { return ''; }
  }

  // ==========================================
  // RECEIPT_CSS: ໂຄງຮ່າງບິນດຽວ (ແບບບິນພິມຜ່ານ browser) ໃຊ້ຮ່ວມກັນທຸກຊ່ອງທາງ —
  // browser, LAN, USB ແລະ Bluetooth ຈຶ່ງພິມອອກມາເປັນບິນໜ້າຕາດຽວກັນ.
  // ຫ້າມເພີ່ມ CSS ສະເພາະຊ່ອງທາງໃດໜຶ່ງ ບໍ່ດັ່ງນັ້ນບິນຈະອອກມາບໍ່ຄືກັນອີກ.
  // ==========================================
  var RECEIPT_CSS =
    ' *{box-sizing:border-box;font-family:"Receipt Lao","Receipt Latin",Arial,sans-serif !important;color:#000 !important;}' +
    ' body,.rcpt-root{margin:0;font-size:17px;font-weight:400;line-height:1.5;background:#fff;}' +
    ' h1,h2,h3,h4,h5,h6{margin:0 !important;} h6{font-size:20px !important;white-space:nowrap;line-height:1.3 !important;}' +
    ' .text-center{text-align:center !important;} .text-right{text-align:right !important;}' +
    ' .font-weight-bold{font-weight:400 !important;color:#000 !important;}' +
    ' .small{font-size:14px !important;color:#000 !important;font-weight:400 !important;}' +
    ' .d-flex{display:flex;} .justify-content-between{justify-content:space-between;}' +
    ' [style*="display: none"],[style*="display:none"],.d-none{display:none !important;}' +
    ' .mb-0{margin-bottom:0 !important;} .mb-1{margin-bottom:4px !important;} .mb-2{margin-bottom:8px !important;}' +
    ' .mt-2{margin-top:8px !important;}' +
    ' .text-muted{color:#000 !important;font-weight:400 !important;}' +
    ' .receipt-header-address,.receipt-header-tel{font-size:15px !important;font-weight:400 !important;color:#000 !important;line-height:1.4 !important;}' +
    ' .receipt-footer-msg{font-size:15px !important;font-weight:400 !important;color:#000 !important;margin-top:10px !important;padding-top:8px !important;text-align:center !important;}' +
    ' img.receipt-logo{max-width:90px !important;max-height:90px !important;height:auto !important;display:block !important;margin:0 auto 12px auto !important;object-fit:contain !important;}' +
    ' img.receipt-qr-img{width:50% !important;max-width:50% !important;max-height:none !important;height:auto !important;display:block;margin:6px auto 2px auto !important;object-fit:contain !important;}' +
    ' table{width:100%;border-collapse:collapse;margin:4px 0;font-size:15px !important;color:#000 !important;}' +
    ' td,th{padding:4px 0;vertical-align:top;color:#000 !important;font-weight:400 !important;}' +
    ' th{font-weight:400 !important;}' +
    ' .rc-strong,.rc-strong *{font-weight:400 !important;-webkit-text-stroke:0.25px #000 !important;}' +
    ' .rc-sum,.rc-sum *{font-size:15px !important;font-weight:400 !important;-webkit-text-stroke:0.25px #000 !important;}' +
    ' .rc-net,.rc-net *{font-size:17px !important;font-weight:400 !important;-webkit-text-stroke:0.25px #000 !important;}' +

    // ເສັ້ນຂີດປະ ແລະ ເນື້ອໃນ: ບໍ່ຍັບຊ້າຍ, ຫົດຂວາ 2px ໃຫ້ເສັ້ນຂີດປະ ແລະ ໜັງສືຝັ່ງຂວາບໍ່ຂາດ
    ' div[style*="dashed"],.rc-divider{border-top:1px dashed #000 !important;margin:2px 0 !important;margin-left:0px !important;width:calc(100% - 2px) !important;}' +
    ' tr.rc-strong{border-top:none !important;border-bottom:none !important;}' +
    ' thead tr,tr:first-child th{border-bottom:1px dashed #000 !important;padding-bottom:4px !important;}' +
    ' th [style*="dashed"],th .rc-divider,.receipt-qr-box [style*="dashed"],.receipt-qr-box .rc-divider{display:none !important;}' +
    ' .receipt-meta,table,#rc_payment_rows,#rc_rep_payment_rows,.rc-sum-wrap,.rc-change-wrap,#rc_multicur,#rc_rep_multicur,.receipt-qr-box,.receipt-footer-msg,.rc-sum,.rc-net{margin-left:0px !important;width:calc(100% - 2px) !important;}' +
    // ຫົວຂໍ້ ແລະ ໂລໂກ້ ຢູ່ເຄິ່ງກາງ
    ' .receipt-header-block{margin-left:0px !important;width:100% !important;text-align:center !important;}' +
    // ເມື່ອກ່ອນແຕ່ລະໜ້າສົ່ງ CSS ເພີ່ມຂອງຕົນເອງ (POS_RCPT_EXTRA_CSS / REPORT_RCPT_EXTRA_CSS) — ລວມເຂົ້າບ່ອນດຽວ
    ' #rc_total,#rc_rep_total{font-size:17px !important;}' +
    ' #rc_multicur,#rc_rep_multicur{font-size:14.5px !important;}' +
    ' #rc_bank_name_lbl,#rc_rep_bank_name_lbl{font-size:15px !important;}';

  // ສະເພາະບິນ Bluetooth: ເຄື່ອງ Bluetooth ພິມທັງບິນເຫຼື່ອມໄປຂວາ 1 ຂີດ (ເສັ້ນຂີດປະເກີນຂວາ 1 ຂີດ) →
  // ຍ້າຍທັງບິນ (ເສັ້ນຂີດປະ + ໜັງສື) ໄປຊ້າຍ 1 ຂີດ: ປາຍຂວາສັ້ນລົງ 1 ຂີດ, ທາງຊ້າຍເພີ່ມ 1 ຂີດ, ໜັງສືໄປຕາມເສັ້ນ.
  // 1 ຂີດ + ຊ່ອງ ≈ 10 ຈຸດ ທີ່ຫົວພິມ 576 ຈຸດ (ຫົວພິມ 384 ຈຸດ ຫຍໍ້ຕາມສ່ວນ). browser/LAN/USB ບໍ່ກະທົບ.
  var BT_SHIFT_DOTS_576 = -10;

  var INK_THRESHOLD = 155;
  var PAPER_LUM = 250;
  // ຂອບຊ້າຍຂອງບິນ (padding-left ຂອງໂຄງຮ່າງ): 12px. 5px ≈ 1 ຂີດ: ເພີ່ມ 5px → ທຸກເສັ້ນຂີດປະສັ້ນລົງທາງຊ້າຍ 1 ຂີດ
  // ແລະ ໜັງສືຝັ່ງຊ້າຍຍັບເຂົ້າໄປນຳ (ທຸກຊ່ອງທາງ: browser ເປັນຫຼັກ, LAN/USB/Bluetooth ຄືກັນ).
  // ທົດລອງມາແລ້ວ: 7px ແລະ 2px ໜັງສືຊ້າຍມືອອກໄປໃກ້ຂອບເຈ້ຍເກີນ — ແຕ່ຫຼັງຍັບບິນ browser ໄປຂວາ 1 mm
  // (BROWSER_DIALOG_SHIFT_DOTS) ຂອບຊ້າຍກວ້າງຂຶ້ນ → ຜູ້ໃຊ້ຂໍໃຫ້ເສັ້ນຂີດປະຍາວອອກທາງຊ້າຍ 1 ຂີດ ແລະ ໜັງສືໄປນຳ (31/10/2026): 7px.
  var RECEIPT_PAD_LEFT = 7;
  // ບິນຖືກຈັດເຄິ່ງກາງເຈ້ຍ (centerShift) → ເນື້ອໃນກວ້າງ/ແຄບລົງ 1 ຂີດ ຈະເລື່ອນເຄິ່ງຂີດ; ຊົດເຊີຍ 5 ຈຸດ (ທີ່ 576 ຈຸດ)
  // ຕໍ່ 1 ຂີດ ທີ່ປ່ຽນຈາກ 12px ເພື່ອໃຫ້ປາຍຂວາຢູ່ບ່ອນເດີມ (12px → 0, 7px → -5).
  // +8 ຈຸດ (1 mm ໄປຂວາ) ທີ່ເຄີຍໃຊ້ສະເພາະບິນ browser (BROWSER_DIALOG_SHIFT_DOTS) ລວມເຂົ້າມານີ້ → ຮູບບິນດຽວກັນທຸກ pixel
  // ທຸກຊ່ອງທາງ (browser, USB, LAN, Bluetooth, ເຄື່ອງມີເຄື່ອງພິມໃນໂຕ): -5 + 8 = 3.
  var PRINT_SHIFT_DOTS = 3;
  function paperDots(widthPx) { return widthPx <= 240 ? 384 : 576; }
  function svgHeight(h) { return Math.ceil(h * 1.65) + 140; }
  function inkCanvas(svgImg, widthPx, h, targetDots) {
    var dots = targetDots || paperDots(widthPx), scale = dots / widthPx;
    var thr = INK_THRESHOLD;
    var canvas = document.createElement('canvas');
    canvas.width = dots;
    canvas.height = Math.ceil(h * scale);
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.scale(scale, scale);
    ctx.drawImage(svgImg, 0, 0, widthPx, h);
    var d = ctx.getImageData(0, 0, canvas.width, canvas.height), p = d.data, w = canvas.width;
    var lastInkRow = 0, ink = 0;
    for (var i = 0; i < p.length; i += 4) {
      var lum = paperLum(p, i);
      if (thr > 0) {
        var v = lum < thr ? 0 : 255;
        p[i] = p[i + 1] = p[i + 2] = v;
        p[i + 3] = 255;
        if (v === 0) ink++;
      } else if (lum < 128) {
        ink++;
      }
      if (lum < PAPER_LUM) lastInkRow = (i / 4 / w) | 0;
    }
    var keep = Math.min(canvas.height, lastInkRow + Math.round(18 * scale));
    var out = document.createElement('canvas');
    out.width = w;
    out.height = Math.max(1, keep);
    var octx = out.getContext('2d');
    octx.fillStyle = '#ffffff';
    octx.fillRect(0, 0, out.width, out.height);
    var btShift = targetDots ? Math.round(BT_SHIFT_DOTS_576 * w / 576) : 0;
    octx.putImageData(d, centerShift(p, w, keep, btShift), 0, 0, 0, w, keep);
    out.__inkCount = ink;
    return out;
  }

  function centerShift(p, w, rows, extraShift) {
    var minX = w, maxX = -1;
    for (var y = 0; y < rows; y++) {
      for (var x = 0; x < w; x++) {
        if (paperLum(p, (y * w + x) * 4) < PAPER_LUM) {
          if (x < minX) minX = x;
          if (x > maxX) maxX = x;
        }
      }
    }
    if (maxX < 0) return 0;
    var inkW = maxX - minX + 1;
    var left = Math.round((w - inkW) / 2) + Math.round(PRINT_SHIFT_DOTS * w / 576) + (extraShift || 0);
    left = Math.max(0, Math.min(w - inkW, left));
    return left - minX;
  }

  function decodeSvg(svg, svgUri) {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      function fail(e) { reject(new Error(svgParseError(svg) || (e && e.message) || 'svg image failed to load')); }
      img.onerror = fail;
      img.src = svgUri;
      if (img.decode) img.decode().then(function () { resolve(img); }, fail);
      else img.onload = function () { resolve(img); };
    });
  }

  function inkCount(canvas) {
    if (typeof canvas.__inkCount === 'number') return canvas.__inkCount;
    var p = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data, n = 0;
    for (var i = 0; i < p.length; i += 4) if (p[i] < 128) n++;
    return n;
  }

  var RENDER_MAX_PASSES = 3, RENDER_IMG_MAX_PASSES = 6, RENDER_PASS_WAIT = 15, fontsWarmed = false;
  function renderStable(svg, svgUri, widthPx, h, targetDots) {
    var last = -1, pass = 0, best = null, bestN = -1;
    var hasImages = svg.indexOf('<img') !== -1;
    var maxPasses = hasImages ? RENDER_IMG_MAX_PASSES : RENDER_MAX_PASSES;
    function once() {
      return decodeSvg(svg, svgUri).then(function (img) {
        var c = inkCanvas(img, widthPx, h, targetDots), n = inkCount(c);
        pass++;
        if (n > bestN) { best = c; bestN = n; }
        var stable = n > 0 && n === last;
        if (n > 0 && (stable || (fontsWarmed && !hasImages))) {
          fontsWarmed = true;
          return best;
        }
        if (pass >= maxPasses) return best;
        last = n;
        return new Promise(function (r) { setTimeout(r, RENDER_PASS_WAIT); }).then(once);
      });
    }
    return once();
  }
  function loadSvgImage(svg, svgUri) { return decodeSvg(svg, svgUri); }

  function toCanvas(source, widthPx, basePath, extraCss, targetDots) {
    return renderReceipt(source, widthPx, basePath, extraCss, targetDots);
  }

  function toImage(source, widthPx, basePath, extraCss) {
    return renderReceipt(source, widthPx, basePath, extraCss).then(function (canvas) {
      return canvas.toDataURL('image/png');
    });
  }

  function warmUp(source, widthPx, basePath, extraCss) {
    if (!source) return Promise.resolve();
    return renderReceipt(source, widthPx, basePath, extraCss).then(function () {}, function () {});
  }

  function renderReceipt(source, widthPx, basePath, extraCss, targetDots) {
    if (!source) return Promise.reject(new Error('no receipt element'));
    var renderCss = 'width:' + widthPx + 'px;background:#fff;margin:0;padding:4px 8px 8px ' + RECEIPT_PAD_LEFT + 'px;box-sizing:border-box;';
    var clone = source.cloneNode(true);
    clone.removeAttribute('id');
    clone.style.cssText = 'position:fixed;left:-99999px;top:0;display:block;' + renderCss;
    document.body.appendChild(clone);

    return convertImages(clone).then(function () { return fontFaces(basePath); }).then(function (fontCss) {
      var h = svgHeight(Math.max(clone.scrollHeight, 80));
      clone.style.cssText = renderCss;
      var html;
      try {
        html = new XMLSerializer().serializeToString(clone);
      } catch (e) {
        html = clone.outerHTML.replace(
          /<(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)((?:[^>"']|"[^"]*"|'[^']*')*?)\/?>/gi,
          function (m, tag, attrs) { return '<' + tag + attrs + ' />'; }
        );
      }
      html = html.replace(/&nbsp;/g, '&#160;')
                 .replace(/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)/g, '&amp;');
      document.body.removeChild(clone);

      var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + widthPx + '" height="' + h + '">' +
        '<foreignObject width="100%" height="100%">' +
        '<div xmlns="http://www.w3.org/1999/xhtml" class="rcpt-root">' +
        '<style>' + fontCss + RECEIPT_CSS + (extraCss || '') + '</style>' +
        html +
        '</div></foreignObject></svg>';
      var svgUri = 'data:image/svg+xml;charset=utf-8;base64,' + btoa(unescape(encodeURIComponent(svg)));

      return renderStable(svg, svgUri, widthPx, h, targetDots);
    }).catch(function (err) {
      if (clone.parentNode) document.body.removeChild(clone);
      throw err;
    });
  }

  function paperPx(paperWidth) {
    return (parseInt(paperWidth, 10) === 58) ? 219 : 302;
  }

  // ບິນໜ້າຕ່າງພິມ browser ໃນຄອມ: ວາງຮູບບິນ (ຮູບດຽວກັບທຸກຊ່ອງທາງ) ເລື່ອນຊ້າຍຈາກກາງເຈ້ຍ.
  // ທົດລອງພິມແທ້ 31/10/2026: -0.15 mm ອອກມາເກີນຂວາ 1 mm → ຍັບຊ້າຍອີກ 1 mm ເຕັມ (8 ຈຸດ × 0.1251 mm = 1.001 mm)
  // ໃຫ້ສ່ວນເສດ (0.15 mm) ຄືເກົ່າ — ຍັບເປັນເສດ mm ອື່ນ (ເຊັ່ນ 1.1 mm) ເຮັດໃຫ້ໜັງສືແຕກເປັນເມັດຊາຍ.
  var USB_PRINT_SHIFT_MM = -1.151;
  function imageHtml(dataUri, paperWidth) {
    if (isBuiltInPrinterDevice()) {
      return '<img class="receipt-print-img" src="' + dataUri + '" alt="" style="display:block;width:100%;max-width:100%;height:auto;margin:0 auto;">';
    }
    var mm = (parseInt(paperWidth, 10) === 58) ? 48 : 72;
    return '<img class="receipt-print-img" src="' + dataUri + '" alt="" style="display:block;width:' + mm +
      'mm;max-width:none;height:auto;margin:0 auto;position:relative;left:' + USB_PRINT_SHIFT_MM + 'mm;">';
  }

  function isAndroid() { return /Android/i.test(navigator.userAgent || ''); }
  function isBuiltInPrinterDevice() {
    try {
      var forced = window.localStorage && localStorage.getItem('StoreMini.receipt_fit_paper');
      if (forced === '1') return true;
      if (forced === '0') return false;
    } catch (e) {}
    if (isAndroid()) return true;
    var touch = (navigator.maxTouchPoints || 0) > 0, coarse = false;
    try { coarse = !!(window.matchMedia && window.matchMedia('(any-pointer: coarse)').matches); } catch (e) {}
    return touch && coarse;
  }
  // ບິນທຸກເຄື່ອງ (ລວມເຄື່ອງທີ່ມີເຄື່ອງພິມໃນໂຕ) ແຕ້ມດ້ວຍຄວາມກວ້າງດຽວກັບບິນ browser ໃນຄອມ —
  // ເມື່ອກ່ອນເຄື່ອງ Android ໃຊ້ 219px (58mm) ເຮັດໃຫ້ໜັງສືຕັດແຖວຕ່າງຈາກ browser. ເຄື່ອງພິມ 58mm ຈະຫຍໍ້ຮູບລົງເອງ (width:100%).
  function dialogPaperPx(paperWidth) {
    return paperPx(paperWidth);
  }
  var FIT_PRINT_SHIFT_MM = -1.5;

  function isPagePaper(paperWidth) {
    var w = parseInt(paperWidth, 10);
    return w === 148 || w === 210;
  }
  function devicePagePaper() {
    try {
      var s = JSON.parse(localStorage.getItem('StoreMini.bt_printer_settings') || '{}') || {};
      return isPagePaper(s.paper) ? parseInt(s.paper, 10) : null;
    } catch (e) { return null; }
  }
  function receiptPaper(serverPaper) {
    return devicePagePaper() || serverPaper;
  }
  function pagePaperCss(paperWidth) {
    var a4 = parseInt(paperWidth, 10) === 210;
    var base = a4 ? 16 : 14;
    return '@page { size: ' + (a4 ? 'A4' : 'A5') + ' portrait; margin: ' + (a4 ? 14 : 10) + 'mm; } ' +
      'html, body { width: auto !important; max-width: none !important; margin: 0 !important; padding: 0 !important; font-size: ' + base + 'px !important; } ' +
      'body:has(.receipt-print-img) { padding: 0 !important; } ' +
      'h6 { font-size: ' + (base + 8) + 'px !important; white-space: normal !important; } ' +
      '.small, .receipt-header-address, .receipt-header-tel, .receipt-footer-msg { font-size: ' + (base - 1) + 'px !important; } ' +
      'table { font-size: ' + base + 'px !important; margin: 8px 0 !important; } ' +
      'td, th { padding: 6px 4px !important; } ' +
      'thead th, tr:first-child th { border-bottom: 1px solid #000 !important; } ' +
      'img.receipt-logo { max-width: ' + (a4 ? 32 : 26) + 'mm !important; max-height: ' + (a4 ? 32 : 26) + 'mm !important; } ' +
      'img.receipt-qr-img { width: ' + (a4 ? 40 : 32) + 'mm !important; max-width: ' + (a4 ? 40 : 32) + 'mm !important; } ' +
      '#rc_total, #rc_rep_total { font-size: ' + (base + 6) + 'px !important; }';
  }

  function dialogPageCss(paperWidth) {
    if (isPagePaper(paperWidth)) return pagePaperCss(paperWidth);
    if (!isBuiltInPrinterDevice()) return '';
    return '@page { size: auto; margin: 0mm; } ' +
      'html, body { width: 100% !important; max-width: 100% !important; margin: 0 !important; } ' +
      'body:has(.receipt-print-img) { padding: 0 1.5mm !important; } ' +
      'body:not(:has(.receipt-print-img)) { padding: 2mm !important; } ' +
      'img.receipt-print-img { width: 100% !important; max-width: 100% !important; position: relative !important; left: ' + FIT_PRINT_SHIFT_MM + 'mm !important; margin: 0 auto !important; } ' +
      'table { table-layout: fixed; } td, th { word-break: break-word; }';
  }

  // ບິນທີ່ເປັນຮູບແລ້ວ (imageHtml) → ໜ້າພິມແບບເບົາ: ມີແຕ່ຂະໜາດເຈ້ຍ + ຮູບ. ເມື່ອກ່ອນໜ້າພິມໂຫຼດ local-font.css (1.2 MB)
  // ແລະ @font-face ອີກ 4 ໄຟລ໌ ທີ່ຮູບບໍ່ໄດ້ໃຊ້ — Chrome ຕ້ອງໂຫຼດ/ຖອດ font ທັງໝົດກ່ອນສ້າງ preview — ບວກລໍຖ້າຕາຍຕົວ 200–600 ms.
  // ຕຳແໜ່ງບິນຄືເກົ່າທຸກຢ່າງ: ໜ້າເກົ່າກໍ margin/padding 0 ແລະ ກວ້າງເຕັມໜ້າ ຕອນມີ .receipt-print-img ຢູ່ແລ້ວ.
  // CSS ຂອງໜ້າພິມບິນແບບ HTML (ກໍລະນີແປງເປັນຮູບບໍ່ໄດ້ ຫຼື ເຈ້ຍ A4/A5): ໃຊ້ RECEIPT_CSS ຕົວດຽວກັບຮູບບິນ
  // ໂຄງຮ່າງ 302px ຂອບດຽວກັບຮູບ (renderReceipt) ແລ້ວຫຍໍ້ໃຫ້ກວ້າງ 72 mm ຄືຮູບບິນ. font ໂຫຼດຈາກໄຟລ໌ໃນ assets/fonts/.
  function htmlReceiptCss(basePath, paperWidth) {
    var px = paperPx(paperWidth), mm = px === 219 ? 48 : 72;
    var faces = FONT_FILES[0].map(function (file, i) {
      return '@font-face{font-family:"Receipt Lao";src:url("' + basePath + 'assets/fonts/' + file + '") format("truetype");font-weight:' + (i ? 700 : 400) + ';font-style:normal;}';
    }).join('') + LATIN_FONT_FILES.map(function (lf) {
      var src = 'src:url("' + basePath + 'assets/fonts/' + lf[0] + '") format("woff2");font-weight:' + lf[1] + ';font-style:normal;';
      return '@font-face{font-family:"Receipt Latin";' + src + '}@font-face{font-family:"Receipt Latin";' + src + 'size-adjust:90%;unicode-range:' + LATIN_LETTERS + ';}';
    }).join('');
    return '@page { size: 80mm auto; margin: 0mm; } ' + faces +
      ' html { margin: 0; padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
      (isPagePaper(paperWidth) ? '' : ' body { width: ' + px + 'px; margin: 0 auto; padding: 4px 8px 8px ' + RECEIPT_PAD_LEFT + 'px; box-sizing: border-box; zoom: ' + (mm * 96 / 25.4 / px).toFixed(4) + '; }') +
      RECEIPT_CSS + ' ' + dialogPageCss(paperWidth);
  }

  function printImageReceipt(iframe, html, title, paperWidth) {
    var win = iframe.contentWindow, doc = win.document, fired = false;
    var esc = String(title || '').replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; });
    doc.open();
    doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + esc + '</title><style>' +
      '@page { size: 80mm auto; margin: 0mm; } ' +
      'html, body { margin: 0; padding: 0; width: 100%; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; } ' +
      dialogPageCss(paperWidth) + '</style></head><body>' + html + '</body></html>');
    doc.close();
    function fire() {
      if (fired) return;
      fired = true;
      try { win.focus(); win.print(); } catch (e) { window.print(); }
    }
    var img = doc.querySelector('img.receipt-print-img');
    // ຮູບເປັນ data URI ທີ່ຖອດແລ້ວ (preDecode) → ພ້ອມເກືອບທັນທີ; ລໍ 1 frame ໃຫ້ຈັດໜ້າກ່ອນ
    if (!img || (img.complete && img.naturalWidth)) setTimeout(fire, 30);
    else { img.onload = img.onerror = function () { setTimeout(fire, 30); }; setTimeout(fire, 1500); }
  }

  function print(source, printer, opts) {
    opts = opts || {};
    var basePath = opts.basePath || '../../';
    if (!printer || (printer.printer_type !== 'ip' && printer.printer_type !== 'usb')) {
      return Promise.resolve({ success: false, message: 'no receipt printer' });
    }
    if (isPagePaper(printer.paper_width)) {
      return Promise.resolve({ success: false, message: 'A4/A5 prints through the print dialog' });
    }
    var widthPx = paperPx(printer.paper_width);
    return toImage(source, widthPx, basePath, opts.extraCss).then(function (dataUri) {
      return $.ajax({
        url: basePath + 'api/pos_backend.php',
        type: 'POST',
        dataType: 'json',
        data: { action: 'print_lan_ticket', printer_id: printer.printer_id, image: dataUri, bw: 1 }
      });
    }).then(function (res) {
      return { success: !!(res && res.success), message: (res && res.message) || '' };
    }, function (err) {
      return { success: false, message: (err && (err.message || err.statusText)) || 'error' };
    });
  }

  window.MiniposLanReceipt = { toImage: toImage, toCanvas: toCanvas, warmUp: warmUp, print: print, imageToDataUri: imageToDataUri, imageMode: imageMode, printHtml: printHtml, latinFontFaces: latinFontFaces, svgHeight: svgHeight, inkCanvas: inkCanvas, loadSvgImage: loadSvgImage, paperPx: paperPx, imageHtml: imageHtml, isBuiltInPrinterDevice: isBuiltInPrinterDevice, dialogPaperPx: dialogPaperPx, printImageReceipt: printImageReceipt, htmlReceiptCss: htmlReceiptCss, dialogPageCss: dialogPageCss, isPagePaper: isPagePaper, receiptPaper: receiptPaper };
})(window, document);