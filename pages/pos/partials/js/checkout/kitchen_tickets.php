<?php
/*
 * ສ່ວນ: ບິນຍ່ອຍຄົວ / ເຄື່ອງພິມຕາມໝວດ
 * ແຍກລາຍການຕາມເຄື່ອງພິມຂອງແຕ່ລະໝວດ, ພິມຜ່ານ browser ຫຼື ແຕ້ມເປັນຮູບ (canvas) ສົ່ງໄປເຄື່ອງພິມ LAN
 * ໂດຍກົງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/pos/partials/js/pos_checkout_js.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>// ============================
// KITCHEN TICKETS — split the print by product category to a separate printer each
// ============================
// Group the cart by the printer assigned to each item's category (window.POS_KITCHEN_ROUTES).
function buildKitchenTickets(items) {
  var routes = window.POS_KITCHEN_ROUTES || {};
  if (!routes || !Object.keys(routes).length) return [];
  var groups = {};
  (items || []).forEach(function(it) {
    if (!it) return;
    var r = routes[String(it.category_id)];
    if (!r) return;
    var g = groups[r.printer_id] || (groups[r.printer_id] = { printer_id: r.printer_id, name: r.printer_name || ('ເຄື່ອງພິມ ' + r.printer_id), type: r.printer_type || 'browser', paper: parseInt(r.paper_width, 10) || 80, items: [] });
    g.items.push({ name: it.product_name || '', qty: parseFloat(it.quantity) || 1, unit: it.unit_name || '' });
  });
  return Object.keys(groups).map(function(k) { return groups[k]; }).filter(function(g) { return g.items.length; });
}

function kitchenTicketHtml(group, meta) {
  // Category / kitchen ticket: date-time and seller on top (large, left), then the station
  // name and the items in a large font. No shop name, no bill number, no divider lines.
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function(c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]; }); }
  var totalQty = group.items.reduce(function(s, i) { return s + i.qty; }, 0);
  var rows = group.items.map(function(it) {
    return '<tr><td style="padding:5px 0;font-size:21px;font-weight:800;line-height:1.3;">' + esc(it.name) +
      '</td><td style="text-align:right;padding:5px 0 5px 8px;font-size:23px;font-weight:900;white-space:nowrap;vertical-align:top;">x' +
      it.qty + (it.unit ? ' ' + esc(it.unit) : '') + '</td></tr>';
  }).join('');
  return '<!DOCTYPE html><html><head><meta charset="utf-8">' +
    '<link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">' +
    '<style>@page{size:80mm auto;margin:0}' +
    '*{box-sizing:border-box;font-family:\'Noto Sans Lao\',\'Souliyo\',\'Boon\',Arial,sans-serif !important;color:#000 !important}' +
    'html,body{width:80mm;margin:0 auto;padding:10px 8px}' +
    '.meta{text-align:left;font-size:19px;font-weight:800;line-height:1.35}' +
    'h2{font-size:22px;margin:10px 0 6px;text-align:center;font-weight:900}' +
    'table{width:100%;border-collapse:collapse}' +
    '.foot{text-align:right;font-size:19px;font-weight:900;margin-top:8px}</style></head><body>' +
    (meta && meta.date ? '<div class="meta">' + esc(meta.date) + '</div>' : '') +
    (meta && meta.cashier ? '<div class="meta">' + esc(posT('pos.kitchen_cashier_label')) + ' ' + esc(meta.cashier) + '</div>' : '') +
    '<h2>' + esc(group.name) + '</h2>' +
    '<table>' + rows + '</table>' +
    '<div class="foot">' + esc(posT('pos.kitchen_total', totalQty)) + '</div>' +
    '</body></html>';
}

// Network LAN printers get their ticket straight from the server (no dialog). USB / driver
// printers still go through the browser, one print dialog per ticket; the cashier picks the
// right printer (Chrome/Edge remember the destination per print after the first setup).
function printKitchenTickets(allGroups, meta) {
  if (!allGroups || !allGroups.length) return;
  sendKitchenTicketsLan(allGroups.filter(function (g) { return g.type === 'ip'; }), meta);
  var groups = allGroups.filter(function (g) { return g.type !== 'ip'; });
  if (!groups.length) return;
  var frame = document.getElementById('posKitchenIframe');
  if (!frame) {
    frame = document.createElement('iframe');
    frame.id = 'posKitchenIframe';
    frame.style.cssText = 'position:fixed;right:-9999px;bottom:-9999px;width:320px;height:320px;border:none;opacity:0;pointer-events:none;';
    document.body.appendChild(frame);
  }
  var idx = 0;
  function next() {
    if (idx >= groups.length) return;
    var g = groups[idx++];
    var doc = frame.contentWindow.document;
    doc.open(); doc.write(kitchenTicketHtml(g, meta)); doc.close();
    var win = frame.contentWindow;
    var advanced = false;
    var go = function () { if (advanced) return; advanced = true; setTimeout(next, 500); };
    win.onafterprint = go;
    setTimeout(function () {
      try { win.focus(); win.print(); } catch (e) {}
      setTimeout(go, 3000); // fallback if onafterprint never fires
    }, 350);
  }
  // Let the customer receipt fire its own dialog first.
  setTimeout(next, 1600);
}

// ---- LAN kitchen tickets: drawn on a canvas (so the browser shapes the Lao text), sent as PNG ----
var LAN_TICKET_FONT = '"Noto Sans Lao Local", "Noto Sans Lao", Arial, sans-serif';

function ensureLanTicketFont() {
  if (!document.fonts || !document.fonts.load) return Promise.resolve();
  var loads = Promise.all([
    document.fonts.load('400 24px "Noto Sans Lao Local"', 'ກ'),
    document.fonts.load('700 24px "Noto Sans Lao Local"', 'ກ')
  ]).catch(function () {});
  return Promise.race([loads, new Promise(function (resolve) { setTimeout(resolve, 1500); })]);
}

function lanTicketSegments(text, granularity) {
  text = String(text == null ? '' : text);
  if (window.Intl && Intl.Segmenter) {
    return Array.from(new Intl.Segmenter('lo', { granularity: granularity }).segment(text), function (seg) { return seg.segment; });
  }
  if (granularity === 'word') {
    return text.split(' ').map(function (w, i, arr) { return i < arr.length - 1 ? w + ' ' : w; });
  }
  return Array.from(text);
}

// Draw one kitchen ticket at the printer's native width (576 dots = 80 mm, 384 = 58 mm).
function kitchenTicketCanvas(group, meta, widthPx) {
  var W = widthPx;
  var s = W / 576;
  var pad = Math.round(14 * s);
  // Large print for the kitchen: date-time / seller on top, then station name and items.
  var sizes = { title: Math.round(44 * s), item: Math.round(42 * s), qty: Math.round(46 * s), foot: Math.round(38 * s), meta: Math.round(36 * s) };
  var measure = document.createElement('canvas').getContext('2d');
  function font(px, bold) { return (bold ? '700 ' : '400 ') + px + 'px ' + LAN_TICKET_FONT; }
  function wrap(str, px, bold, maxW) {
    measure.font = font(px, bold);
    var lines = [];
    var cur = '';
    lanTicketSegments(str, 'word').forEach(function (part) {
      if (measure.measureText(cur + part).width <= maxW) { cur += part; return; }
      if (cur.trim() !== '') { lines.push(cur); cur = ''; }
      if (measure.measureText(part).width <= maxW) { cur = part.trimStart(); return; }
      lanTicketSegments(part, 'grapheme').forEach(function (gr) {
        if (cur !== '' && measure.measureText(cur + gr).width > maxW) { lines.push(cur); cur = gr; } else { cur += gr; }
      });
    });
    if (cur.trim() !== '') lines.push(cur);
    return lines.length ? lines : [''];
  }

  var ops = [];
  var y = pad;
  function text(str, px, bold, align) {
    wrap(str, px, bold, W - pad * 2).forEach(function (ln) {
      ops.push({ kind: 'text', str: ln, px: px, bold: bold, align: align, y: y });
      y += Math.round(px * 1.4);
    });
  }

  // Date-time and seller first (large, flush left); no shop name, no bill number, no rules.
  if (meta && meta.date) text(meta.date, sizes.meta, true, 'left');
  if (meta && meta.cashier) text(posT('pos.kitchen_cashier_label') + ' ' + meta.cashier, sizes.meta, true, 'left');
  y += Math.round(14 * s);
  text(group.name, sizes.title, true, 'center');
  y += Math.round(10 * s);

  var totalQty = 0;
  group.items.forEach(function (it) {
    totalQty += it.qty;
    var qtyStr = 'x' + it.qty + (it.unit ? ' ' + it.unit : '');
    measure.font = font(sizes.qty, true);
    var qtyW = Math.ceil(measure.measureText(qtyStr).width);
    ops.push({ kind: 'text', str: qtyStr, px: sizes.qty, bold: true, align: 'right', y: y });
    wrap(it.name, sizes.item, true, W - pad * 2 - qtyW - Math.round(16 * s)).forEach(function (ln) {
      ops.push({ kind: 'text', str: ln, px: sizes.item, bold: true, align: 'left', y: y });
      y += Math.round(sizes.item * 1.4);
    });
    y += Math.round(6 * s);
  });
  y += Math.round(10 * s);
  text(posT('pos.kitchen_total', totalQty), sizes.foot, true, 'right');
  y += pad * 2;

  var canvas = document.createElement('canvas');
  canvas.width = W;
  canvas.height = y;
  var c = canvas.getContext('2d');
  c.fillStyle = '#ffffff';
  c.fillRect(0, 0, W, y);
  c.fillStyle = '#000000';
  c.strokeStyle = '#000000';
  c.textBaseline = 'top';
  ops.forEach(function (op) {
    if (op.kind === 'rule') {
      c.setLineDash([Math.round(10 * s), Math.round(6 * s)]);
      c.lineWidth = Math.max(2, Math.round(2 * s));
      c.beginPath();
      c.moveTo(pad, op.y);
      c.lineTo(W - pad, op.y);
      c.stroke();
      c.setLineDash([]);
      return;
    }
    c.font = font(op.px, op.bold);
    c.textAlign = op.align;
    var x = op.align === 'center' ? W / 2 : (op.align === 'right' ? W - pad : pad);
    c.fillText(op.str, x, op.y);
  });
  return canvas;
}

function sendKitchenTicketsLan(groups, meta) {
  if (!groups || !groups.length) return;
  ensureLanTicketFont().then(function () {
    groups.forEach(function (g) {
      var canvas = kitchenTicketCanvas(g, meta, g.paper === 58 ? 384 : 576);
      $.ajax({
        url: window.POS_BACKEND_URL || '../../api/pos_backend.php',
        type: 'POST',
        dataType: 'json',
        data: { action: 'print_lan_ticket', printer_id: g.printer_id, image: canvas.toDataURL('image/png') }
      }).done(function (res) {
        if (!res || !res.success) lanTicketFailed(g, res && res.message);
      }).fail(function () {
        lanTicketFailed(g, '');
      });
    });
  });
}

function lanTicketFailed(group, message) {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'error',
    title: posT('pos.lan_print_failed', group.name),
    text: message || '',
    showConfirmButton: false,
    timer: 7000,
    timerProgressBar: true
  });
}

