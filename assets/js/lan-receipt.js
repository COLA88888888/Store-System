/**
 * MiniPos LAN receipt printing (shared) — renders a receipt element to a PNG in the browser
 * (so Lao text is shaped correctly; printer fonts can't draw Lao) and sends it to a Network LAN
 * printer through api/pos_backend.php?action=print_lan_ticket. No print dialog / preview.
 *
 * Same technique as the POS checkout receipt (pages/pos/partials/js/pos_checkout_js.php):
 * clone the receipt → serialize as XHTML into an SVG <foreignObject> with the Lao font embedded
 * → draw onto a canvas → PNG. Used by the report pages to reprint a past bill straight to the
 * shop's receipt printer.
 *
 *   MiniposLanReceipt.print(element, printer, { basePath: '../../' })
 *     → Promise<{ success, message }>
 *   printer = { printer_id, printer_name, printer_type: 'ip', paper_width: 80|58 }
 */
(function (window, document) {
  var FONT_FILES = [
    ['NotoSansLao-Regular.ttf', 'NotoSansLao-Bold.ttf'],
  ];
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
      // Same file shipped as Regular and Bold: don't declare it as 700 or bold prints thin.
      var css = face(b64[0], 400);
      if (b64[1] && b64[1] !== b64[0]) css += face(b64[1], 700);
      return latinFontFaces(basePath).then(function (latin) {
        fontCache = latin + css;
        return fontCache;
      });
    });
  }

  // Noto Sans Lao has no Latin letters or digits, so English text and every number fell back
  // to Arial. "Receipt Latin" (Noto Sans, Latin subset) is listed first in the receipt CSS;
  // Lao characters it doesn't have fall through to "Receipt Lao". Missing files → '' (Arial).
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
        // Latin letters are drawn larger than Lao at the same font-size, so an English bill looked
        // a size bigger than the Lao one. Letters get a second, 90% face (declared last, so it
        // wins for its range); digits and symbols keep full size to match the Lao bill's numbers.
        return '@font-face{font-family:"Receipt Latin";' + src + '}' +
          '@font-face{font-family:"Receipt Latin";' + src + 'size-adjust:90%;unicode-range:' + LATIN_LETTERS + ';}';
      }).catch(function () { return ''; });
    })).then(function (faces) {
      latinCache = faces.join('');
      return latinCache;
    });
  }

  // Luminance of pixel i, blended with white paper by alpha so transparent backgrounds stay white.
  function paperLum(p, i) {
    var lum = (p[i] * 299 + p[i + 1] * 587 + p[i + 2] * 114) / 1000;
    return lum * (p[i + 3] / 255) + 255 * (1 - p[i + 3] / 255);
  }

  // Otsu's method: the cut-off that best separates the image's two tones.
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

  // Logo -> pure black/white. A thermal head only prints black dots, so light/colored parts of a
  // logo (e.g. a pale cyan gradient) were dithered into a faint speckle. Every pixel that isn't
  // near-white (and isn't transparent) becomes solid black; the rest becomes paper white.
  // A light-on-dark logo (pale lettering on a black square) came out as one solid black block
  // that way, so when most of the logo is dark it is printed inverted — dark lettering on paper —
  // split at the Otsu cut-off, since JPEG noise in a dark background would cross a fixed one.
  var LOGO_BW_THRESHOLD = 215; // 0–255 luminance; darker than this prints black
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

  // Bank QR -> hard black/white. Gray, anti-aliased module edges get dithered into speckle by the
  // printer and the code stops scanning; a small QR is first enlarged in whole-pixel steps so
  // every module stays a clean square when the receipt is scaled to the printer's dots.
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
  function imageToDataUri(src, mode) {
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

  // Convert the logo/QR inside `root` (in place) to their print-ready black/white data: URIs.
  function convertImages(root) {
    return Promise.all(Array.prototype.map.call(root.querySelectorAll('img'), function (img) {
      if (img.style.display === 'none') return Promise.resolve();
      return imageToDataUri(img.getAttribute('src'), imageMode(img)).then(function (uri) {
        if (uri) img.setAttribute('src', uri); else img.removeAttribute('src');
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
    ' *{box-sizing:border-box;font-family:"Receipt Latin","Receipt Lao",Arial,sans-serif !important;color:#000 !important;}' +
    ' body,.rcpt-root{margin:0;font-size:17px;font-weight:400;line-height:1.5;background:#fff;}' +
    ' h1,h2,h3,h4,h5,h6{margin:0 !important;} h6{font-size:20px !important;white-space:nowrap;line-height:1.3 !important;}' +
    ' .text-center{text-align:center !important;} .text-right{text-align:right !important;}' +
    ' .font-weight-bold{font-weight:700 !important;color:#000 !important;}' +
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
    ' th{font-weight:700 !important;}' +
    // Headings, net total, change: regular weight + a hair of outline — heavier than the body
    // text but not as thick as Bold (Noto Sans Lao has no weight in between)
    ' .rc-strong,.rc-strong *{font-weight:400 !important;-webkit-text-stroke:0.25px #000;}';

  // Rasterize the receipt SVG straight at the printer's dot width (576 dots on 80 mm paper, 384 on
  // 58 mm), so the server has nothing to resample. The SVG is drawn taller than measured (the page
  // measures the clone with its own, smaller fonts, which cut the footer off), then the blank
  // paper below the last line is trimmed.
  // INK_THRESHOLD > 0 turns every pixel pure black/white at that luminance, so text prints as
  // solid as the old vector (HTML) print did; 0 leaves grey edges to the server's dithering,
  // which prints thinner. Raise it for bolder text, lower it for thinner.
  var INK_THRESHOLD = 160;
  var PAPER_LUM = 250; // lighter than this counts as blank paper when trimming the tail
  function paperDots(widthPx) { return widthPx <= 240 ? 384 : 576; }
  function svgHeight(h) { return Math.ceil(h * 1.6) + 120; }
  function inkCanvas(svgImg, widthPx, h) {
    var dots = paperDots(widthPx), scale = dots / widthPx;
    var canvas = document.createElement('canvas');
    canvas.width = dots;
    canvas.height = Math.ceil(h * scale);
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.scale(scale, scale);
    ctx.drawImage(svgImg, 0, 0, widthPx, h);
    var d = ctx.getImageData(0, 0, canvas.width, canvas.height), p = d.data, w = canvas.width;
    var lastInkRow = 0;
    for (var i = 0; i < p.length; i += 4) {
      var lum = paperLum(p, i);
      if (INK_THRESHOLD > 0) {
        var v = lum < INK_THRESHOLD ? 0 : 255;
        p[i] = p[i + 1] = p[i + 2] = v;
        p[i + 3] = 255;
      }
      if (lum < PAPER_LUM) lastInkRow = (i / 4 / w) | 0;
    }
    var keep = Math.min(canvas.height, lastInkRow + Math.round(16 * scale));
    var out = document.createElement('canvas');
    out.width = w;
    out.height = Math.max(1, keep);
    var octx = out.getContext('2d');
    octx.fillStyle = '#ffffff';
    octx.fillRect(0, 0, out.width, out.height);
    octx.putImageData(d, centerShift(p, w, keep), 0, 0, 0, w, keep);
    return out;
  }

  // Horizontal centring. The bill came out closer to the right edge than the left: the ink is
  // moved so the blank paper either side is equal, then nudged by PRINT_SHIFT_DOTS for printers
  // whose head sits off-centre (8 dots = 1 mm; negative = left, positive = right). Never shifts
  // any ink off the paper.
  var PRINT_SHIFT_DOTS = 0;
  function centerShift(p, w, rows) {
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
    var left = Math.round((w - inkW) / 2) + PRINT_SHIFT_DOTS;
    left = Math.max(0, Math.min(w - inkW, left));
    return left - minX;
  }

  // Decode the receipt SVG into an <img> ready to draw.
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
    var p = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data, n = 0;
    for (var i = 0; i < p.length; i += 4) if (p[i] < 128) n++;
    return n;
  }

  // The fonts embedded in the SVG load lazily, per face, the first time a glyph needs them —
  // so a line whose font wasn't loaded yet came out blank. A one-time warm-up per page wasn't
  // enough: after switching language (Lao → English, …) the new text needs faces the earlier
  // bills never used, and the first bill printed with only the logo, lines and bold headings.
  // So every print renders until two passes in a row carry the same amount of ink.
  var RENDER_MAX_PASSES = 6, RENDER_PASS_WAIT = 250;
  function renderStable(svg, svgUri, widthPx, h) {
    var last = -1, pass = 0;
    function once() {
      return decodeSvg(svg, svgUri).then(function (img) {
        var c = inkCanvas(img, widthPx, h), n = inkCount(c);
        pass++;
        if (n === last || pass >= RENDER_MAX_PASSES) return c;
        last = n;
        return new Promise(function (r) { setTimeout(r, RENDER_PASS_WAIT); }).then(once);
      });
    }
    return once();
  }
  function loadSvgImage(svg, svgUri) { return decodeSvg(svg, svgUri); }

  function toImage(source, widthPx, basePath, extraCss) {
    if (!source) return Promise.reject(new Error('no receipt element'));
    var renderCss = 'width:' + widthPx + 'px;background:#fff;margin:0;padding:8px 12px;box-sizing:border-box;';
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

      return renderStable(svg, svgUri, widthPx, h).then(function (canvas) {
        return canvas.toDataURL('image/png');
      });
    }).catch(function (err) {
      if (clone.parentNode) document.body.removeChild(clone);
      throw err;
    });
  }

  // Render width for a paper size: the full paper width in CSS px (80 mm = 302 px, 58 mm =
  // 219 px) — the layout the old HTML print had — then printed into the printable width
  // (72 / 48 mm, see imageHtml; the LAN head's 576 / 384 dots), which scales the text about
  // 10% smaller than a 1:1 print. Smaller number = bigger text.
  function paperPx(paperWidth) {
    return (parseInt(paperWidth, 10) === 58) ? 219 : 302;
  }

  // The receipt PNG as an <img> for the browser print dialog: printable width, centred on the
  // page — a full 80 mm image ran off one side of the 72 mm the printer can reach.
  // USB-only horizontal nudge, in mm (positive = right). The LAN printer gets the image dot for
  // dot; over USB the printer driver places the page itself and the same bill came out with more
  // blank paper on the right than the LAN one. This moves only the USB bill so both match.
  // ບິນ USB: ຍັບຊ້າຍ 0.15 mm (ລົບ = ຊ້າຍ). ວັດຈາກຮູບບິນທີ່ພິມດ້ວຍ −0.5: ຂອບຊ້າຍ ~5.4 mm,
  // ຂວາ ~6.1 mm → ຍັບຂວາອີກ ~0.35 mm. ຮູບບິນ 72 mm ຢູ່ກາງໜ້າ 80 mm (ຫວ່າງຂ້າງລະ ~4 mm).
  // LAN ມີຄ່າຂອງມັນເອງ: LAN_PRINT_SHIFT_DOTS ໃນ api/lan_printer.php.
  var USB_PRINT_SHIFT_MM = -0.15;
  function imageHtml(dataUri, paperWidth) {
    if (isBuiltInPrinterDevice()) {
      // ເຄື່ອງ POS ທີ່ມີເຄື່ອງພິມໃນຕົວ: ຮູບເຕັມຄວາມກວ້າງເຈ້ຍທີ່ເຄື່ອງລາຍງານ (ບໍ່ລ໋ອກ 72mm ທີ່ລົ້ນຂວາ)
      return '<img class="receipt-print-img" src="' + dataUri + '" alt="" style="display:block;width:100%;max-width:100%;height:auto;margin:0 auto;">';
    }
    var mm = (parseInt(paperWidth, 10) === 58) ? 48 : 72;
    return '<img class="receipt-print-img" src="' + dataUri + '" alt="" style="display:block;width:' + mm +
      'mm;max-width:none;height:auto;margin:0 auto;position:relative;left:' + USB_PRINT_SHIFT_MM + 'mm;">';
  }

  // ເຄື່ອງຈໍສຳພັດທີ່ມີເຄື່ອງພິມໃນຕົວ (POS ຕັ້ງໂຕະ Windows, ຫຼື Sunmi/iMin Android) ພິມຜ່ານໜ້າຕ່າງພິມ
  // ຂອງ browser, ແຕ່ driver ຂອງເຄື່ອງພິມໃນຕົວ ລາຍງານໜ້າເຈ້ຍແຄບກວ່າ 80mm — ບິນທີ່ບັງຄັບ @page 80mm
  // + ຮູບ 72mm ຈຶ່ງຖືກຕັດດ້ານຂວາ (ລາຄາ, ₭, ເລກບິນ). ໃນເຄື່ອງເຫຼົ່ານີ້ ("ໂໝດພໍດີເຈ້ຍ"): ໃຊ້ຂະໜາດໜ້າ
  // ທີ່ driver ບອກ ແລ້ວຂະຫຍາຍຮູບບິນໃຫ້ພໍດີຄວາມກວ້າງນັ້ນ. ຄອມທຳມະດາ (USB ນອກ) ແລະ LAN ບໍ່ປ່ຽນ.
  // ກວດອັດຕະໂນມັດຈາກຈໍສຳພັດ; ບັງຄັບເອງໄດ້ໃນ console ຂອງເຄື່ອງນັ້ນ:
  //   localStorage.setItem('StoreMini.receipt_fit_paper', '1')   // ເປີດ
  //   localStorage.setItem('StoreMini.receipt_fit_paper', '0')   // ປິດ
  //   localStorage.removeItem('StoreMini.receipt_fit_paper')     // ກັບໄປກວດອັດຕະໂນມັດ
  function isAndroid() {
    return /Android/i.test(navigator.userAgent || '');
  }
  function isBuiltInPrinterDevice() {
    try {
      var forced = window.localStorage && localStorage.getItem('StoreMini.receipt_fit_paper');
      if (forced === '1') return true;
      if (forced === '0') return false;
    } catch (e) {}
    if (isAndroid()) return true;
    var touch = (navigator.maxTouchPoints || 0) > 0;
    var coarse = false;
    try { coarse = !!(window.matchMedia && window.matchMedia('(any-pointer: coarse)').matches); } catch (e) {}
    return touch && coarse;
  }
  // ຄວາມກວ້າງ (CSS px) ທີ່ໃຊ້ແຕ້ມບິນ ສຳລັບໜ້າຕ່າງພິມຂອງ browser. Android (ສ່ວນຫຼາຍເຈ້ຍ 58mm) ວາງແບບ
  // 58mm; ຈໍສຳພັດ Windows ໃຊ້ຂະໜາດຕາມການຕັ້ງຄ່າ (80mm) ຄືເກົ່າ — ຂະໜາດໜັງສືຄືບິນ USB/LAN.
  function dialogPaperPx(paperWidth) {
    return isAndroid() && isBuiltInPrinterDevice() ? paperPx(58) : paperPx(paperWidth);
  }
  // ຍັບບິນຊ້າຍ/ຂວາ ສະເພາະໂໝດພໍດີເຈ້ຍ (ເຄື່ອງພິມໃນຕົວ), ໜ່ວຍ mm: ລົບ = ຊ້າຍ, ບວກ = ຂວາ.
  // ທົດລອງກັບເຄື່ອງແລ້ວ: 0, −1 = ໜັງສືຍັງຊິດຂວາ → −1.5.
  // USB ນອກ ແລະ LAN ມີຄ່າຂອງມັນເອງ (USB_PRINT_SHIFT_MM, LAN_PRINT_SHIFT_DOTS).
  var FIT_PRINT_SHIFT_MM = -1.5;
  // CSS ເພີ່ມທ້າຍ <style> ຂອງ iframe ພິມ — ທັບ @page 80mm ແລະ ຄວາມກວ້າງ body 80mm ສະເພາະເຄື່ອງນີ້.
  // ຂອບ 1.5mm ສອງຂ້າງ ກັນບໍ່ໃຫ້ຕົກຂອບທີ່ຫົວພິມໄປບໍ່ເຖິງ.
  function dialogPageCss() {
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

  window.MiniposLanReceipt = { toImage: toImage, print: print, imageToDataUri: imageToDataUri, imageMode: imageMode, printHtml: printHtml, latinFontFaces: latinFontFaces, svgHeight: svgHeight, inkCanvas: inkCanvas, loadSvgImage: loadSvgImage, paperPx: paperPx, imageHtml: imageHtml, isBuiltInPrinterDevice: isBuiltInPrinterDevice, dialogPaperPx: dialogPaperPx, dialogPageCss: dialogPageCss };
})(window, document);
