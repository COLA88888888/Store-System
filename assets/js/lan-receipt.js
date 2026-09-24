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
    ['NotoSansLaoLooped-Regular.ttf', 'NotoSansLaoLooped-Bold.ttf']
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
      fontCache = css;
      return css;
    });
  }

  // Images inside the SVG must be data: URIs or the canvas is tainted and can't be exported.
  function imageToDataUri(src) {
    return new Promise(function (resolve) {
      if (!src || src.indexOf('data:') === 0) { resolve(src || ''); return; }
      var img = new Image();
      img.onload = function () {
        try {
          var c = document.createElement('canvas');
          c.width = img.naturalWidth || 1;
          c.height = img.naturalHeight || 1;
          c.getContext('2d').drawImage(img, 0, 0);
          resolve(c.toDataURL('image/png'));
        } catch (e) { resolve(''); }
      };
      img.onerror = function () { resolve(''); };
      img.src = src;
    });
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
    ' *{box-sizing:border-box;font-family:"Receipt Lao",Arial,sans-serif !important;color:#000 !important;}' +
    ' body{margin:0;font-size:17px;font-weight:600;line-height:1.5;background:#fff;}' +
    ' h1,h2,h3,h4,h5,h6{margin:0 !important;} h6{font-size:20px !important;}' +
    ' .text-center{text-align:center !important;} .text-right{text-align:right !important;}' +
    ' .font-weight-bold{font-weight:700 !important;color:#000 !important;}' +
    ' .small{font-size:14px !important;color:#000 !important;font-weight:600 !important;}' +
    ' .d-flex{display:flex;} .justify-content-between{justify-content:space-between;}' +
    ' [style*="display: none"],[style*="display:none"],.d-none{display:none !important;}' +
    ' .mb-0{margin-bottom:0 !important;} .mb-1{margin-bottom:4px !important;} .mb-2{margin-bottom:8px !important;}' +
    ' .mt-2{margin-top:8px !important;}' +
    ' .text-muted{color:#000 !important;font-weight:600 !important;}' +
    ' .receipt-header-address,.receipt-header-tel{font-size:15px !important;font-weight:600 !important;color:#000 !important;line-height:1.4 !important;}' +
    ' .receipt-footer-msg{font-size:15px !important;font-weight:700 !important;color:#000 !important;border-top:1px dashed #000 !important;margin-top:10px !important;padding-top:8px !important;text-align:center !important;}' +
    ' img.receipt-logo{max-width:80px !important;max-height:80px !important;height:auto !important;display:block !important;margin:2px auto 2px auto !important;object-fit:contain !important;}' +
    ' img.receipt-qr-img{max-width:130px !important;max-height:130px !important;height:auto !important;display:block !important;margin:6px auto 2px auto !important;object-fit:contain !important;}' +
    ' table{width:100%;border-collapse:collapse;margin:4px 0;font-size:15px;color:#000 !important;}' +
    ' td,th{padding:4px 0;vertical-align:top;color:#000 !important;font-weight:600 !important;}' +
    ' th{font-weight:700 !important;}';

  function toImage(source, widthPx, basePath, extraCss) {
    if (!source) return Promise.reject(new Error('no receipt element'));
    var renderCss = 'width:' + widthPx + 'px;background:#fff;margin:0;padding:8px 6px;box-sizing:border-box;';
    var clone = source.cloneNode(true);
    clone.removeAttribute('id');
    clone.style.cssText = 'position:fixed;left:-99999px;top:0;display:block;' + renderCss;
    document.body.appendChild(clone);

    var swaps = Array.prototype.map.call(clone.querySelectorAll('img'), function (img) {
      if (img.style.display === 'none') return Promise.resolve();
      return imageToDataUri(img.getAttribute('src')).then(function (uri) {
        if (uri) img.setAttribute('src', uri); else img.removeAttribute('src');
      });
    });

    return Promise.all(swaps).then(function () { return fontFaces(basePath); }).then(function (fontCss) {
      var h = Math.max(clone.scrollHeight, 80);
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
        '<div xmlns="http://www.w3.org/1999/xhtml">' +
        '<style>' + fontCss + RECEIPT_CSS + (extraCss || '') + '</style>' +
        html +
        '</div></foreignObject></svg>';
      var svgUri = 'data:image/svg+xml;charset=utf-8;base64,' + btoa(unescape(encodeURIComponent(svg)));

      return new Promise(function (resolve, reject) {
        var svgImg = new Image();
        function rasterize() {
          try {
            var scale = 2;
            var canvas = document.createElement('canvas');
            canvas.width = widthPx * scale;
            canvas.height = h * scale;
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.scale(scale, scale);
            ctx.drawImage(svgImg, 0, 0, widthPx, h);
            resolve(canvas.toDataURL('image/png'));
          } catch (e) { reject(e); }
        }
        svgImg.onerror = function () { reject(new Error(svgParseError(svg) || 'svg image failed to load')); };
        svgImg.src = svgUri;
        if (svgImg.decode) {
          svgImg.decode().then(rasterize, function (e) {
            reject(new Error(svgParseError(svg) || (e && e.message) || 'svg decode failed'));
          });
        } else {
          svgImg.onload = rasterize;
        }
      });
    }).catch(function (err) {
      if (clone.parentNode) document.body.removeChild(clone);
      throw err;
    });
  }

  function print(source, printer, opts) {
    opts = opts || {};
    var basePath = opts.basePath || '../../';
    if (!printer || printer.printer_type !== 'ip') {
      return Promise.resolve({ success: false, message: 'no LAN receipt printer' });
    }
    var widthPx = (parseInt(printer.paper_width, 10) === 58) ? 240 : 320;
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

  window.MiniposLanReceipt = { toImage: toImage, print: print };
})(window, document);
