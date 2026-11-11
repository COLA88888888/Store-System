/**
 * ສະຕິກເກີໃນຫ້ອງແຊັດ (pages/contact.php):
 *   - ປຸ່ມ [data-chat-sticker] ເປີດ/ປິດ ແຜງ [data-stk-panel]: ແທັບໝວດ + "ໃຊ້ລ່າສຸດ" (ສູງສຸດ 16, localStorage WlaoDev.stickersRecent)
 *   - ລາຍການມາຈາກ api/chat.php?action=stickers, ເກັບແຄຊໃນ localStorage WlaoDev.stickers
 *     (ຕັ້ງຄ່າ → "ໂຫຼດໃໝ່" ລ້າງແຄຊ ແລ້ວສົ່ງ event chat:stickers-refresh → ດຶງໃໝ່)
 *   - ກົດສະຕິກເກີ: ເປີດ "ຢືນຢັນກ່ອນສົ່ງ" (WlaoDev.chatSettings.stickerConfirm, ຄ່າເລີ່ມຕົ້ນ = ເປີດ) → ຕົວຢ່າງ [data-stk-preview]
 *     ບໍ່ດັ່ງນັ້ນສົ່ງທັນທີ ຜ່ານ box.chatSendText (assets/js/chat.js)
 */
(function () {
  var box = document.querySelector('[data-chat]');
  var btn = document.querySelector('[data-chat-sticker]');
  var panel = document.querySelector('[data-stk-panel]');
  if (!box || !btn || !panel) return;
  var tabsEl = panel.querySelector('[data-stk-tabs]'), grid = panel.querySelector('[data-stk-grid]');
  var preview = document.querySelector('[data-stk-preview]'), big = document.querySelector('[data-stk-big]');
  var L = {};
  try { L = JSON.parse(panel.getAttribute('data-i18n') || '{}'); } catch (e) {}
  var CACHE = 'WlaoDev.stickers', RECENT = 'WlaoDev.stickersRecent';
  var groups = null, current = 'recent', pending = '';

  function read(k, def) { try { return JSON.parse(localStorage.getItem(k) || 'null') || def; } catch (e) { return def; } }
  function write(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
  function needConfirm() { return true; }   // ສະຕິກເກີຕ້ອງຢືນຢັນກ່ອນສົ່ງສະເໝີ (ບໍ່ແມ່ນການຕັ້ງຄ່າອີກແລ້ວ)

  function load(force) {
    var c = force ? null : read(CACHE, null);
    if (c && c.groups && c.version === panel.getAttribute('data-version')) { groups = c.groups; paint(); return; }   // ເວີຊັນໃໝ່ (CHAT_STICKERS_VERSION) → ດຶງໃໝ່
    grid.innerHTML = '<p>…</p>';
    fetch(panel.getAttribute('data-endpoint') + '?action=stickers', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) throw 0;
        groups = res.groups;
        write(CACHE, { version: res.version, groups: groups });
        paint();
      })
      .catch(function () { grid.innerHTML = ''; var p = document.createElement('p'); p.textContent = L.fail || '!'; grid.appendChild(p); });
  }
  function paint() {
    var recent = read(RECENT, []);
    if (current === 'recent' && !recent.length) current = groups[0] ? groups[0].key : 'recent';
    tabsEl.innerHTML = '';
    var list = (recent.length ? [{ key: 'recent', label: L.recent || 'Recent', items: recent }] : []).concat(groups);
    list.forEach(function (g) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = g.label;
      b.className = g.key === current ? 'is-active' : '';
      b.addEventListener('click', function () { current = g.key; paint(); });
      tabsEl.appendChild(b);
    });
    grid.innerHTML = '';
    var g = list.filter(function (x) { return x.key === current; })[0] || list[0];
    (g ? g.items : []).forEach(function (s) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = s;
      b.setAttribute('aria-label', s);
      b.addEventListener('click', function () { pick(s); });
      grid.appendChild(b);
    });
  }
  function pick(s) {
    if (needConfirm() && preview) {
      pending = s;
      big.textContent = s;
      preview.hidden = false;
      return;
    }
    send(s);
  }
  function send(s) {
    var recent = read(RECENT, []).filter(function (x) { return x !== s; });
    recent.unshift(s);
    write(RECENT, recent.slice(0, 16));
    if (preview) preview.hidden = true;
    pending = '';
    toggle(false);
    if (box.chatSendText) box.chatSendText(s);
  }
  function toggle(open) {
    panel.hidden = !open;
    btn.classList.toggle('is-on', open);
    if (open) { if (!groups) load(false); else paint(); }
    else if (preview) preview.hidden = true;
  }

  btn.addEventListener('click', function () { toggle(panel.hidden); });
  if (preview) {
    preview.querySelector('[data-stk-cancel]').addEventListener('click', function () { preview.hidden = true; pending = ''; });
    preview.querySelector('[data-stk-send]').addEventListener('click', function () { if (pending) send(pending); });
  }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) toggle(false); });
  document.addEventListener('chat:stickers-refresh', function () { groups = null; current = 'recent'; if (!panel.hidden) load(true); });
})();
