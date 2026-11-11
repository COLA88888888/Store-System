/**
 * ຢືນຢັນກ່ອນສົ່ງຮູບ / ວິດີໂອ / ໄຟລ໌ ໃນແຊັດ — ໃຊ້ຮ່ວມ: ໜ້າແຊັດ (assets/js/chat.js) ແລະ ແຊັດລອຍ (assets/js/floating_chat.js).
 *   WlaoChatConfirm.ask(files, function (keep) { ... })  → popup ຕົວຢ່າງ (ຮູບ/ວິດີໂອ ເປັນຮູບຫຍໍ້, ໄຟລ໌ ເປັນຊື່ + ຂະໜາດ),
 *   ກົດ × ເອົາແຕ່ລະອັນອອກໄດ້; "ສົ່ງ (N)" → callback ດ້ວຍລາຍການທີ່ເຫຼືອ; "ຍົກເລີກ" / ກົດພື້ນ / Esc → ບໍ່ສົ່ງ.
 * ຂໍ້ຄວາມແປຈາກ window.WLAO_SEND_L (layouts/footer.php). CSS: .ccf-* ໃນ assets/css/components.css
 */
(function () {
  function L() {
    var d = { title: 'ສົ່ງໄຟລ໌ນີ້?', titleN: 'ສົ່ງ %d ລາຍການນີ້?', send: 'ສົ່ງ', cancel: 'ຍົກເລີກ', remove: 'ເອົາອອກ' };
    var o = window.WLAO_SEND_L || {};
    for (var k in o) if (o[k]) d[k] = o[k];
    return d;
  }
  function size(n) { n = +n || 0; return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }

  function ask(files, done) {
    files = Array.prototype.slice.call(files || []);
    if (!files.length) return;
    var t = L(), urls = [];
    var wrap = document.createElement('div');
    wrap.className = 'ccf';
    wrap.innerHTML = '<div class="ccf-box" role="dialog" aria-modal="true"><h3 class="ccf-title"></h3><div class="ccf-list"></div>' +
      '<div class="ccf-btns"><button type="button" class="ccf-cancel"></button><button type="button" class="ccf-send"></button></div></div>';
    var list = wrap.querySelector('.ccf-list'), title = wrap.querySelector('.ccf-title'), sendBtn = wrap.querySelector('.ccf-send');
    wrap.querySelector('.ccf-cancel').textContent = t.cancel;

    function paint() {
      list.innerHTML = '';
      title.textContent = files.length > 1 ? t.titleN.replace('%d', files.length) : t.title;
      sendBtn.textContent = t.send + (files.length > 1 ? ' (' + files.length + ')' : '');
      list.classList.toggle('is-single', files.length === 1);
      files.forEach(function (f, i) {
        var it = document.createElement('div');
        it.className = 'ccf-item';
        var type = f.type || '';
        if (type.indexOf('image/') === 0 || type.indexOf('video/') === 0) {
          var u = URL.createObjectURL(f); urls.push(u);
          var m = document.createElement(type.indexOf('image/') === 0 ? 'img' : 'video');
          m.src = u; m.className = 'ccf-media';
          if (m.tagName === 'VIDEO') { m.muted = true; m.playsInline = true; m.preload = 'metadata'; }
          it.appendChild(m);
          it.classList.add('is-media');
        } else {
          var ext = document.createElement('span'); ext.className = 'ccf-ext';
          ext.textContent = (f.name.split('.').pop() || 'file').slice(0, 4).toUpperCase();
          it.appendChild(ext);
        }
        var cap = document.createElement('span'); cap.className = 'ccf-cap';
        cap.innerHTML = '<b></b><small></small>';
        cap.querySelector('b').textContent = f.name;
        cap.querySelector('small').textContent = size(f.size);
        it.appendChild(cap);
        if (files.length > 1) {
          var x = document.createElement('button');
          x.type = 'button'; x.className = 'ccf-x'; x.setAttribute('aria-label', t.remove); x.innerHTML = '&times;';
          x.addEventListener('click', function () { files.splice(i, 1); if (!files.length) close(false); else paint(); });
          it.appendChild(x);
        }
        list.appendChild(it);
      });
    }
    function close(ok) {
      document.removeEventListener('keydown', onKey);
      wrap.remove();
      setTimeout(function () { urls.forEach(function (u) { URL.revokeObjectURL(u); }); }, 1000);
      if (ok && files.length) done(files);
    }
    function onKey(e) { if (e.key === 'Escape') close(false); else if (e.key === 'Enter') close(true); }
    wrap.addEventListener('click', function (e) { if (e.target === wrap) close(false); });
    wrap.querySelector('.ccf-cancel').addEventListener('click', function () { close(false); });
    sendBtn.addEventListener('click', function () { close(true); });
    document.addEventListener('keydown', onKey);
    paint();
    document.body.appendChild(wrap);
    sendBtn.focus();
  }

  window.WlaoChatConfirm = { ask: ask };
})();
