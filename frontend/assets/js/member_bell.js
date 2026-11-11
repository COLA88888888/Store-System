/**
 * ກະດິ່ງແຈ້ງເຕືອນສະມາຊິກ ([data-bell] ໃນ components/member_bell.php) ↔ api/notifications.php
 *   - ກົດກະດິ່ງ → ດຶງລາຍການ 20 ອັນລ່າສຸດ; ກົດລາຍການ → ໝາຍອ່ານແລ້ວ + ໄປໜ້າທີ່ກ່ຽວຂ້ອງ
 *   - "ອ່ານແລ້ວທັງໝົດ" → ລ້າງຕົວເລກ
 *   - ກວດຈຳນວນໃໝ່ທຸກ 30 ວິ (ສະເພາະຕອນແທັບເປີດຢູ່); URL …#notifications → ເປີດກະດິ່ງເອງ
 */
(function () {
  var box = document.querySelector('[data-bell]');
  if (!box) return;
  var endpoint = box.getAttribute('data-endpoint');
  var btn = box.querySelector('[data-bell-toggle]');
  var panel = box.querySelector('[data-bell-panel]');
  var list = box.querySelector('[data-bell-list]');
  var countEl = box.querySelector('[data-bell-count]');
  var csrf = (window.APP && window.APP.csrf) || '';

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  function setCount(n) {
    n = parseInt(n, 10) || 0;
    countEl.textContent = n > 99 ? '99+' : n;
    countEl.hidden = n === 0;
  }
  function post(data) {
    var fd = new FormData();
    fd.append('csrf', csrf);
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); });
  }
  function render(items) {
    if (!items.length) { list.innerHTML = '<p class="nb-empty"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg><br>' + esc(box.getAttribute('data-empty')) + '</p>'; return; }
    list.innerHTML = items.map(function (n) {
      return '<a class="nb-item' + (n.is_read ? '' : ' is-unread') + '" href="' + esc(n.link || '#') + '" data-id="' + n.id + '">' +
        '<span class="nb-ico is-' + esc(n.tone) + '">' + n.icon + '</span>'   /* SVG ຈາກ server (memberNotifIcon — ຄົງທີ່) */ +
        '<span class="nb-text"><b>' + esc(n.title) + '</b>' + (n.sub ? '<small>' + esc(n.sub) + '</small>' : '') +
        '<time>' + esc(n.time) + '</time></span>' +
        (n.is_read ? '' : '<i class="nb-dot" aria-hidden="true"></i>') + '</a>';
    }).join('');
  }
  function load() {
    list.innerHTML = '<p class="nb-empty">' + esc(box.getAttribute('data-loading')) + '</p>';
    fetch(endpoint, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) { if (!res.success) return; setCount(res.unread); render(res.items || []); })
      .catch(function () { list.innerHTML = ''; });
  }
  function open(on) {
    if (on) {
      if (typeof window.closeAllOverlays === 'function') window.closeAllOverlays('bell');
      try { document.dispatchEvent(new CustomEvent('wlao:close-overlays', { detail: { except: 'bell' } })); } catch (e) {}
    }
    panel.hidden = !on;
    btn.setAttribute('aria-expanded', on ? 'true' : 'false');
    if (on) load();
  }

  document.addEventListener('wlao:close-overlays', function (e) {
    if (e.detail && e.detail.except === 'bell') return;
    if (!panel.hidden) open(false);
  });

  btn.addEventListener('click', function (e) { e.stopPropagation(); open(panel.hidden); });
  document.addEventListener('click', function (e) { if (!panel.hidden && !box.contains(e.target)) open(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) open(false); });

  box.querySelector('[data-bell-readall]').addEventListener('click', function () {
    post({ action: 'read' }).then(function (res) {
      setCount(res.unread || 0);
      list.querySelectorAll('.is-unread').forEach(function (a) { a.classList.remove('is-unread'); });
      list.querySelectorAll('.nb-dot').forEach(function (d) { d.remove(); });
    });
  });
  list.addEventListener('click', function (e) {
    var a = e.target.closest('.nb-item');
    if (!a) return;
    var href = a.getAttribute('href');
    if (a.classList.contains('is-unread')) {
      e.preventDefault();
      post({ action: 'read', id: a.getAttribute('data-id') }).then(function () {}, function () {}).then(function () {
        if (href && href !== '#') location.href = href; else load();
      });
    } else if (!href || href === '#') {
      e.preventDefault();
    }
  });

  // ລິ້ງ …#notifications (ເຊັ່ນ ຈາກອີເມວ) → ເປີດກະດິ່ງໃຫ້ເລີຍ
  if (location.hash === '#notifications') open(true);

  setInterval(function () {
    if (document.hidden || !panel.hidden) return;
    fetch(endpoint + '?unread=1', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) { if (res.success) setCount(res.unread); })
      .catch(function () {});
  }, 30000);
})();
