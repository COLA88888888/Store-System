/**
 * MiniPos LAN & Bluetooth receipt rendering — ແປງໃບບິນ HTML ເປັນຮູບດຳ-ຂາວ ຫຼື Canvas
 * ເພື່ອໃຫ້ພາສາລາວ, ໂລໂກ້ ແລະ QR ອອກຄືກັບເຄື່ອງພິມທົ່ວໄປທຸກຢ່າງ.
 */
(function (window, document) {
  // ຟອນບິນ = ຟອນດຽວກັບລະບົບ (Nuanta, ມີທັງລາວ ອັງກິດ ແລະ ຕົວເລກ); ຖ້າໂຫຼດບໍ່ໄດ້ ໃຊ້ Noto Sans Lao ແທນ
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

  // ຟອນຕົວອັກສອນອັງກິດ ແລະ ຕົວເລກ (Receipt Latin) ເພື່ອໃຫ້ຂະໜາດສົມດຸນກັບຟອນລາວ
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

  // ແປງໂລໂກ້ເປັນດຳ-ຂາວຄົມຊັດ ສຳລັບຫົວພິມຄວາມຮ້ອນ
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

  // ແປງ QR ທະນາຄານເປັນດຳ-ຂາວຄົມຊັດເພື່ອໃຫ້ສະແກນງ່າຍ
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

  // Logo files often carry a wide blank border, which printed as a big gap between the logo and
  // the shop name. Cut the (already black/white) canvas down to its black pixels.
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

  // Images inside the SVG must be data: URIs or the canvas is tainted and can't be exported.
  // mode 'logo' (see toBlackWhite) or 'qr' (see qrCanvas) also converts it for the thermal head.
  // ຜົນການແປງໂລໂກ້/QR ຈື່ໄວ້ (ຕາມ src + mode) — ຮູບເດີມທຸກບິນ, ບໍ່ຕ້ອງແປງໃໝ່ທຸກເທື່ອ → ພິມໄວຂຶ້ນ
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

  // ຖອດລະຫັດຮູບ data: ໄວ້ກ່ອນແຕ້ມ SVG — QR ໃຫຍ່ທີ່ຍັງຖອດບໍ່ທັນ ຖືກແຕ້ມເປັນຊ່ອງຂາວ (ບິນອອກມາບໍ່ມີ QR)
  function preDecode(uri) {
    return new Promise(function (resolve) {
      var im = new Image();
      im.onload = im.onerror = function () { resolve(); };
      im.src = uri;
      if (im.decode) im.decode().then(resolve, resolve);
    });
  }

  // Convert the logo/QR inside `root` (in place) to their print-ready black/white data: URIs.
  function convertImages(root) {
    return Promise.all(Array.prototype.map.call(root.querySelectorAll('img'), function (img) {
      if (img.style.display === 'none') return Promise.resolve();
      return imageToDataUri(img.getAttribute('src'), imageMode(img)).then(function (uri) {
        if (uri) img.setAttribute('src', uri); else img.removeAttribute('src');
        return uri ? preDecode(uri) : null;
      });
    }));
  }

  // innerHTML of a receipt element with its logo/QR already converted — for the browser print
  // dialog, so it prints the same logo and QR as the LAN printer.
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

  // Minimal stand-in for the Bootstrap utilities the receipt markup uses — the foreignObject
  // document can't see the page's stylesheets. Matches the POS receipt's print CSS.
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
    ' .receipt-footer-msg{font-size:15px !important;font-weight:400 !important;color:#000 !important;border-top:1px dashed #000 !important;margin-top:10px !important;padding-top:8px !important;text-align:center !important;}' +
    // Logo: bigger; already converted to pure black/white by imageToDataUri(…, true)
    ' img.receipt-logo{max-width:90px !important;max-height:90px !important;height:auto !important;display:block !important;margin:0 auto 12px auto !important;object-fit:contain !important;}' +
    // QR: big enough that each module is several printer dots wide, or phones can't read it
    ' img.receipt-qr-img{width:50% !important;max-width:50% !important;max-height:none !important;height:auto !important;display:block;margin:6px auto 2px auto !important;object-fit:contain !important;}' +
    ' table{width:100%;border-collapse:collapse;margin:4px 0;font-size:15px !important;color:#000 !important;}' +
    ' td,th{padding:4px 0;vertical-align:top;color:#000 !important;font-weight:400 !important;}' +
    ' th{font-weight:400 !important;}' +
    // Headings, net total, change: regular weight + a hair of outline — heavier than the body
    // text but not as thick as Bold (Noto Sans Lao has no weight in between)
    ' .rc-strong,.rc-strong *{font-weight:400 !important;-webkit-text-stroke:0.25px #000 !important;}' +
    // ແຖວສະຫຼຸບ (ຮັບເງິນ, ລວມ, ສ່ວນຫຼຸດ, ອມພ, ເງິນທອນ): ຂະໜາດເທົ່າຕາຕະລາງລາຍການ (15px), ດຳເຂັ້ມ ແຕ່ບໍ່ໜາ;
    // ຍອດສຸດທິ ໃຫຍ່ກວ່າເລັກນ້ອຍ (17px) — layouts/receipt_template.php .rc-sum / .rc-net
    ' .rc-sum,.rc-sum *{font-size:15px !important;font-weight:400 !important;-webkit-text-stroke:0.25px #000 !important;}' +
    ' .rc-net,.rc-net *{font-size:17px !important;font-weight:400 !important;-webkit-text-stroke:0.25px #000 !important;}';

  // CSS ເພີ່ມເຕີມສຳລັບບິນ Bluetooth: ຍັບໜັງສືເຂົ້າໃຫ້ກະທັດຮັດພໍດີ ແລະ ຫົວຂໍ້ໜາດຳຂຶ້ນເລັກນ້ອຍ
  var BT_EXTRA_CSS =
    ' *{letter-spacing:0px !important;}' +
    ' body,.rcpt-root{line-height:1.45 !important;}' +
    ' .rc-strong,.rc-strong *{font-weight:400 !important;-webkit-text-stroke:0.3px #000 !important;}' +
    ' .receipt-header-address,.receipt-header-tel{line-height:1.38 !important;margin-top:2px !important;}' +
    ' .d-flex{padding:1px 0 !important;line-height:1.42 !important;}' +
    ' [style*="dashed"]{margin:6px 0 !important;}' +
    ' table{margin:4px 0 !important;}' +
    ' td,th{padding:3px 0 !important;line-height:1.38 !important;}' +
    ' td:first-child,th:first-child{padding-right:4px !important;}' +
    ' .receipt-qr-box{margin:6px 0 !important;padding-top:6px !important;}' +
    ' .receipt-footer-msg{margin-top:8px !important;padding-top:7px !important;line-height:1.42 !important;}';

  // ແປງ SVG ໃບບິນເປັນ canvas ດຳ-ຂາວ ຕາມຄວາມກວ້າງຈຸດຫົວພິມ (targetDots: 576 ສຳລັບ 80mm, 384 ສຳລັບ 58mm)
  var INK_THRESHOLD = 155;
  var BT_INK_THRESHOLD = 148;
  var PAPER_LUM = 250;
  var PRINT_SHIFT_DOTS = 0;
  function paperDots(widthPx) { return widthPx <= 240 ? 384 : 576; }
  function svgHeight(h) { return Math.ceil(h * 1.65) + 140; }
  function inkCanvas(svgImg, widthPx, h, targetDots) {
    var dots = targetDots || paperDots(widthPx), scale = dots / widthPx;
    var thr = targetDots ? BT_INK_THRESHOLD : INK_THRESHOLD;
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
    octx.putImageData(d, centerShift(p, w, keep, !!targetDots), 0, 0, 0, w, keep);
    out.__inkCount = ink;
    return out;
  }

  function centerShift(p, w, rows, isBt) {
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
    // Bluetooth: ຍັບຊ້າຍມືເຂົ້າຊິດ dot 0 (-minX) ເພື່ອຍັບມາທາງຊ້າຍມືອີກ 1 ຂີດ
    var left = isBt ? 0 : (Math.round((w - inkW) / 2) + PRINT_SHIFT_DOTS);
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

  // ກວດໃຫ້ຟອນໃນ SVG ໂຫຼດຄົບກ່ອນສົ່ງພິມ (ຖ້າໂຫຼດຟອນແລ້ວ ແຕ້ມພຽງ 1 ຮອບທັນທີ ເພື່ອໃຫ້ບິນອອກໄວທີ່ສຸດ)
  // ບິນທີ່ມີຮູບ (ໂລໂກ້/QR): ແຕ້ມຈົນໝຶກເທົ່າກັນ 2 ຮອບຕິດກັນສະເໝີ — ຮອບດຽວບາງເທື່ອ QR ຍັງບໍ່ຖືກແຕ້ມ
  // (ຖອດລະຫັດບໍ່ທັນ) ແຕ່ຕົວໜັງສືມີໝຶກແລ້ວ ຈຶ່ງເຄີຍຮັບຮອບນັ້ນ → ບິນອອກມາມີຊ່ອງຂາວແທນ QR. ໃຊ້ຮອບທີ່ໝຶກຫຼາຍສຸດ.
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

  // ຄືກັບ toImage ແຕ່ສົ່ງຄືນ canvas (ດຳ-ຂາວ, ກວ້າງເທົ່າຈຸດຫົວພິມ targetDots) — ເຄື່ອງພິມ Bluetooth ໃຊ້ໂດຍກົງ
  function toCanvas(source, widthPx, basePath, extraCss, targetDots) {
    return renderReceipt(source, widthPx, basePath, extraCss, targetDots);
  }

  function toImage(source, widthPx, basePath, extraCss) {
    return renderReceipt(source, widthPx, basePath, extraCss).then(function (canvas) {
      return canvas.toDataURL('image/png');
    });
  }

  // ໂຫຼດຟອນ ແລະ ແປງໂລໂກ້/QR ໄວ້ລ່ວງໜ້າ (ຕອນເປີດໜ້າຂາຍ) — ບິນທຳອິດຂອງວັນບໍ່ຕ້ອງລໍໂຫຼດ
  function warmUp(source, widthPx, basePath, extraCss) {
    if (!source) return Promise.resolve();
    return renderReceipt(source, widthPx, basePath, extraCss).then(function () {}, function () {});
  }

  function renderReceipt(source, widthPx, basePath, extraCss, targetDots) {
    if (!source) return Promise.reject(new Error('no receipt element'));
    var pad = targetDots ? '8px 13px 8px 0px' : '8px 12px';
    var renderCss = 'width:' + widthPx + 'px;background:#fff;margin:0;padding:' + pad + ';box-sizing:border-box;';
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
        '<style>' + fontCss + RECEIPT_CSS + (targetDots ? BT_EXTRA_CSS : '') + (extraCss || '') + '</style>' +
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

  // ບິນ USB: ຍັບຊ້າຍ 0.15 mm (ລົບ = ຊ້າຍ). ຮູບບິນ 72 mm ຢູ່ກາງໜ້າ 80 mm.
  var USB_PRINT_SHIFT_MM = -0.15;
  function imageHtml(dataUri, paperWidth) {
    if (isBuiltInPrinterDevice()) {
      return '<img class="receipt-print-img" src="' + dataUri + '" alt="" style="display:block;width:100%;max-width:100%;height:auto;margin:0 auto;">';
    }
    var mm = (parseInt(paperWidth, 10) === 58) ? 48 : 72;
    return '<img class="receipt-print-img" src="' + dataUri + '" alt="" style="display:block;width:' + mm +
      'mm;max-width:none;height:auto;margin:0 auto;position:relative;left:' + USB_PRINT_SHIFT_MM + 'mm;">';
  }

  // ເຄື່ອງຈໍສຳພັດທີ່ມີເຄື່ອງພິມໃນຕົວ (POS ຕັ້ງໂຕະ Windows, ຫຼື Sunmi/iMin Android): ໃຊ້ໂໝດພໍດີເຈ້ຍ
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
  function dialogPaperPx(paperWidth) {
    return isAndroid() && isBuiltInPrinterDevice() ? paperPx(58) : paperPx(paperWidth);
  }
  var FIT_PRINT_SHIFT_MM = -1.5;

  // ເຈ້ຍແຜ່ນ A5 (148) / A4 (210) — ຕັ້ງຢູ່ ຕັ້ງຄ່າ > ເຄື່ອງພິມ (st_tbl_printer.paper_width ເປັນ mm).
  // ເຄື່ອງພິມຄວາມຮ້ອນ (LAN/Bluetooth) ພິມໄດ້ແຕ່ມ້ວນ 58/80, ສະນັ້ນ A4/A5 ພິມຜ່ານໜ້າຕ່າງພິມສະເໝີ
  // ເປັນ HTML ເຕັມໜ້າ (ບໍ່ແມ່ນຮູບແຄບ 72 mm) — ຕາຕະລາງຂະຫຍາຍເຕັມຄວາມກວ້າງຂອງເຈ້ຍ.
  function isPagePaper(paperWidth) {
    var w = parseInt(paperWidth, 10);
    return w === 148 || w === 210;
  }
  // ເຄື່ອງນີ້ເລືອກ A5/A4 ໄວ້ໃນ ຕັ້ງຄ່າ > ເຄື່ອງພິມ > Bluetooth ("ຂະໜາດເຈ້ຍ", ເກັບໃນ localStorage ຂອງເຄື່ອງ —
  // ສ່ວນຫຼາຍແມ່ນໂທລະສັບ) → ຄືນ 148/210, ບໍ່ດັ່ງນັ້ນ null.
  function devicePagePaper() {
    try {
      var s = JSON.parse(localStorage.getItem('StoreMini.bt_printer_settings') || '{}') || {};
      return isPagePaper(s.paper) ? parseInt(s.paper, 10) : null;
    } catch (e) { return null; }
  }
  // ຂະໜາດເຈ້ຍໃບບິນທີ່ໃຊ້ແທ້ໃນເຄື່ອງນີ້: A5/A4 ທີ່ເລືອກໄວ້ໃນເຄື່ອງ ມາກ່ອນ, ບໍ່ດັ່ງນັ້ນຂອງເຄື່ອງພິມໃບບິນຂອງສາຂາ
  function receiptPaper(serverPaper) {
    return devicePagePaper() || serverPaper;
  }
  function pagePaperCss(paperWidth) {
    var a4 = parseInt(paperWidth, 10) === 210;
    var base = a4 ? 16 : 14;   // px
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

  // CSS ທ້າຍສຸດຂອງ iframe ພິມ: A4/A5 → ໜ້າເຕັມ; ເຄື່ອງພິມໃນຕົວ → ພໍດີເຈ້ຍ; ອື່ນໆ → ບໍ່ເພີ່ມ
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

  function print(source, printer, opts) {
    opts = opts || {};
    var basePath = opts.basePath || '../../';
    if (!printer || printer.printer_type !== 'ip') {
      return Promise.resolve({ success: false, message: 'no LAN receipt printer' });
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
        data: { action: 'print_lan_ticket', printer_id: printer.printer_id, image: dataUri }
      });
    }).then(function (res) {
      return { success: !!(res && res.success), message: (res && res.message) || '' };
    }, function (err) {
      return { success: false, message: (err && (err.message || err.statusText)) || 'error' };
    });
  }

  window.MiniposLanReceipt = { toImage: toImage, toCanvas: toCanvas, warmUp: warmUp, print: print, imageToDataUri: imageToDataUri, imageMode: imageMode, printHtml: printHtml, latinFontFaces: latinFontFaces, svgHeight: svgHeight, inkCanvas: inkCanvas, loadSvgImage: loadSvgImage, paperPx: paperPx, imageHtml: imageHtml, isBuiltInPrinterDevice: isBuiltInPrinterDevice, dialogPaperPx: dialogPaperPx, dialogPageCss: dialogPageCss, isPagePaper: isPagePaper, receiptPaper: receiptPaper };
})(window, document);
