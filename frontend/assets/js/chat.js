/**
 * ຫ້ອງແຊັດໜ້າຕິດຕໍ່ ([data-chat] ໃນ pages/contact.php) ↔ api/chat.php
 *   - ໂຫຼດປະຫວັດຂອງ session ນີ້, ດຶງຂໍ້ຄວາມໃໝ່ທຸກ 4 ວິ (ສະເພາະຕອນເປີດແທັບຢູ່) — ຄຳຕອບຂອງພະນັກງານຈຶ່ງຂຶ້ນເອງ
 *   - ພິມ (Enter = ສົ່ງ, Shift+Enter = ແຖວໃໝ່), ປຸ່ມຖາມດ່ວນ, ສົ່ງຕຳແໜ່ງ 📍 (geolocation), ອັດສຽງ (MediaRecorder, ສູງສຸດ 60 ວິ)
 *   - ແນບໄຟລ໌ [data-chat-attach] → action=file (ຮູບ / ວິດີໂອ / ເອກະສານ, ສູງສຸດ 10 ໄຟລ໌ຕໍ່ຄັ້ງ, ສົ່ງເທື່ອລະໄຟລ໌)
 *   - ທຸກຂໍ້ຄວາມໃໝ່ → event chat:messages (assets/js/chat_room.js ເພີ່ມໄຟລ໌ໃສ່ແທັບ "ມີເດຍ ແລະ ໄຟລ໌")
 *   - ຍັງບໍ່ມີເບີໂທ → ສະແດງຟອມຝາກເບີ (ປິດໄດ້; ຈື່ໄວ້ໃນ sessionStorage 'WlaoDev.chatContactClosed')
 */
(function () {
  var box = document.querySelector('[data-chat]');
  if (!box) return;
  var endpoint = box.getAttribute('data-endpoint');
  var L = {};
  try { L = JSON.parse(box.getAttribute('data-i18n') || '{}'); } catch (e) {}
  var log = box.querySelector('[data-chat-log]');
  var form = box.querySelector('[data-chat-form]');
  var input = box.querySelector('[data-chat-text]');
  var contact = box.querySelector('[data-chat-contact]');
  var micBtn = box.querySelector('[data-chat-mic]');
  var recBar = box.querySelector('[data-chat-rec]');
  var recTime = box.querySelector('[data-chat-rec-time]');
  var lastId = 0, busy = false, typingEl = null, lastDay = '';
  var CLOSED_KEY = 'WlaoDev.chatContactClosed';
  var STICKER_RE;
  // emoji ດຽວ (ລວມ ສີຜິວ, ZWJ ເຊັ່ນ ❤️‍🔥, ທຸງ 🇱🇦, ປຸ່ມເລກ 1️⃣) = ສະຕິກເກີ
  try { STICKER_RE = new RegExp('^(?:\\p{Regional_Indicator}{2}|[0-9#*]\\uFE0F?\\u20E3|\\p{Extended_Pictographic}(?:\\uFE0F|\\p{Emoji_Modifier})?(?:\\u200D\\p{Extended_Pictographic}(?:\\uFE0F|\\p{Emoji_Modifier})?)*)$', 'u'); } catch (e) { STICKER_RE = /^$/; }

  function post(data) {
    return fetch(endpoint, {
      method: 'POST', credentials: 'same-origin', body: data,
      headers: { 'X-CSRF-Token': window.APP.csrf, 'Accept': 'application/json' }
    }).then(function (r) { return r.json(); });
  }

  // ຂໍ້ຄວາມ → DOM (textContent ທັງໝົດ; URL ກາຍເປັນລິ້ງ)
  function linkify(el, text) {
    var re = /(https?:\/\/[^\s<]+)/g, last = 0, m;
    while ((m = re.exec(text))) {
      el.appendChild(document.createTextNode(text.slice(last, m.index)));
      var a = document.createElement('a');
      a.href = m[1]; a.textContent = m[1]; a.target = '_blank'; a.rel = 'noopener';
      el.appendChild(a);
      last = m.index + m[1].length;
    }
    el.appendChild(document.createTextNode(text.slice(last)));
  }
  // ໄຟລ໌ແນບ → DOM: ຮູບ (ກົດ = ເປີດເບິ່ງ [data-cr-view]), ວິດີໂອ (ຫຼິ້ນໃນແຊັດ), ເອກະສານ (ກົດ = ດາວໂຫຼດ)
  function fmtSize(n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
  function fileNode(f) {
    var a = document.createElement('a');
    a.href = f.url; a.target = '_blank'; a.rel = 'noopener';
    if (f.kind === 'image') {
      a.className = 'chat-att-img'; a.setAttribute('data-cr-view', 'image');
      var img = document.createElement('img'); img.src = f.url; img.alt = f.name; img.loading = 'lazy';
      a.appendChild(img);
      return a;
    }
    if (f.kind === 'video') {
      var v = document.createElement('video');
      v.className = 'chat-att-video'; v.src = f.url; v.controls = true; v.preload = 'metadata';
      return v;
    }
    a.className = 'chat-att-file'; a.setAttribute('download', f.name);
    var ext = document.createElement('span'); ext.className = 'chat-att-ext';
    ext.textContent = (f.name.split('.').pop() || 'file').slice(0, 4).toUpperCase();
    var info = document.createElement('span');
    var nm = document.createElement('b'); nm.textContent = f.name;
    var sz = document.createElement('small'); sz.textContent = fmtSize(f.size);
    info.appendChild(nm); info.appendChild(sz);
    a.appendChild(ext); a.appendChild(info);
    return a;
  }
  function bubble(m) {
    var row = document.createElement('div');
    row.className = 'chat-msg is-' + m.sender;
    if (m.id) row.setAttribute('data-id', m.id);
    if (m.sender !== 'visitor') {
      var who = document.createElement('span');
      who.className = 'chat-name';
      who.textContent = L.name;   // ບັອດ ແລະ ພະນັກງານ ສະແດງຊື່ດຽວກັນ (ຊື່ບໍລິສັດ)
      row.appendChild(who);
    }
    var b = document.createElement('div');
    b.className = 'chat-bubble';
    if (m.file) {
      b.appendChild(fileNode(m.file));
      row.classList.add('is-file', 'is-file-' + m.file.kind);
    } else if (m.audio) {
      var au = document.createElement('audio');
      au.controls = true; au.preload = 'metadata'; au.src = m.audio;
      b.appendChild(au);
    } else {
      linkify(b, (m.body || '').replace(/^(?:📍|⚠️?|📎|🎤)\s*/u, ''));   // ຂໍ້ຄວາມເກົ່າທີ່ມີ emoji ນຳໜ້າ (ຕຳແໜ່ງ/ເຕືອນ) → ບໍ່ສະແດງ emoji
      if (STICKER_RE.test(m.body || '')) row.classList.add('is-sticker');   // emoji ດຽວ = ສະຕິກເກີ (ສະແດງໃຫຍ່, ບໍ່ມີຟອງ)
    }
    row.appendChild(b);
    if (m.time) {
      var t = document.createElement('span');
      t.className = 'chat-time';
      t.textContent = m.time;
      if (m.sender === 'visitor' && m.id) {   // ສະຖານະອ່ານ ໃຕ້ຂໍ້ຄວາມຂອງລູກຄ້າ (paintRead)
        var r = document.createElement('span');
        r.className = 'chat-read';
        t.appendChild(r);
      }
      row.appendChild(t);
    }
    return row;
  }
  // ປ້າຍວັນທີລະຫວ່າງຂໍ້ຄວາມ: m.date = 'd/m/Y' (models/chat.php) → "ມື້ນີ້" / "ມື້ວານ" / 09/11/2026
  function dmy(d) { return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear(); }
  function dayNode(date) {
    var now = new Date(), y = new Date(now.getTime() - 86400000);
    var el = document.createElement('div');
    el.className = 'chat-day';
    el.innerHTML = '<span></span>';
    el.firstChild.textContent = date === dmy(now) ? (L.today || date) : (date === dmy(y) ? (L.yesterday || date) : date);
    return el;
  }
  var isInitialLoad = true;

  function isNearBottom() {
    return (log.scrollHeight - log.scrollTop - log.clientHeight) <= 90;
  }
  function scrollDown(force) {
    if (!log) return;
    if (force || isNearBottom() || isInitialLoad) {
      try {
        log.scrollTo({ top: log.scrollHeight, behavior: force ? 'auto' : 'smooth' });
      } catch (err) {
        log.scrollTop = log.scrollHeight;
      }
      requestAnimationFrame(function () {
        if (log) log.scrollTop = log.scrollHeight;
      });
    }
  }
  // ✓ ຍັງບໍ່ອ່ານ / ✓✓ ອ່ານແລ້ວ — ພະນັກງານເປີດຫ້ອງນີ້ໃນ backend ແລ້ວ (staff_read = id ຂໍ້ຄວາມສູງສຸດທີ່ເຫັນ)
  function paintRead(staffRead) {
    log.querySelectorAll('.chat-msg.is-visitor[data-id] .chat-read').forEach(function (el) {
      var seen = +el.closest('[data-id]').getAttribute('data-id') <= staffRead;
      el.textContent = seen ? ' · ✓✓ ' + L.read : ' · ✓ ' + L.unread;
      el.classList.toggle('is-seen', seen);
    });
  }
  function render(res) {
    if (!res || !res.success) return;
    var hadNew = false;
    var prevScrollTop = log.scrollTop;
    var incoming = [];   // ຂໍ້ຄວາມໃໝ່ຈາກທີມງານ/ບັອດ (ບໍ່ນັບຕອນໂຫຼດເທື່ອທຳອິດ) → event chat:incoming (assets/js/chat_room.js: ສຽງ + ແຈ້ງເຕືອນ)
    (res.messages || []).forEach(function (m) {
      if (m.id <= lastId) return;
      lastId = m.id;
      if (m.date && m.date !== lastDay) { lastDay = m.date; log.appendChild(dayNode(m.date)); }   // ເສັ້ນແບ່ງວັນ: ມື້ນີ້ / ມື້ວານ / ວັນທີ
      log.appendChild(bubble(m));
      hadNew = true;
      if (!isInitialLoad && m.sender !== 'visitor') incoming.push(m);
    });
    if (incoming.length) box.dispatchEvent(new CustomEvent('chat:incoming', { bubbles: true, detail: { messages: incoming } }));
    if (res.messages && res.messages.length) box.dispatchEvent(new CustomEvent('chat:messages', { bubbles: true, detail: { messages: res.messages } }));
    paintRead(res.staff_read || 0);
    var closed = sessionStorage.getItem(CLOSED_KEY) === '1';
    contact.hidden = !(res.has_chat && res.need_contact && !closed);

    if (isInitialLoad) {
      log.scrollTop = log.scrollHeight;
      requestAnimationFrame(function () { if (log) log.scrollTop = log.scrollHeight; });
      setTimeout(function () { if (log) log.scrollTop = log.scrollHeight; }, 120);
      isInitialLoad = false;
    } else if (hadNew) {
      // ຖ້າຜູ້ໃຊ້ຢູ່ລຸ່ມສຸດຢູ່ແລ້ວ → ເລື່ອນລົງລຸ່ມໃຫ້; ແຕ່ຖ້າເລື່ອນຢູ່ເທິງຄ້າງໄວ້ ບໍ່ໃຫ້ເລື່ອນລົງລຸ່ມອໍໂຕ້
      if (isNearBottom()) {
        log.scrollTop = log.scrollHeight;
        requestAnimationFrame(function () { if (log) log.scrollTop = log.scrollHeight; });
      } else {
        log.scrollTop = prevScrollTop;
      }
    }
    // ຖ້າບໍ່ມີຂໍ້ຄວາມໃໝ່ (poll ປົກກະຕິ) → ບໍ່ປ່ຽນ log.scrollTop ເດັດຂາດ (ຄ້າງໄວ້ບ່ອນເກົ່າ)
  }
  function typing(on) {
    if (on && !typingEl) {
      typingEl = document.createElement('div');
      typingEl.className = 'chat-msg is-bot';
      typingEl.innerHTML = '<div class="chat-bubble chat-typing"><i></i><i></i><i></i></div>';
      log.appendChild(typingEl);
      if (isNearBottom()) log.scrollTop = log.scrollHeight;
    } else if (!on && typingEl) {
      typingEl.remove();
      typingEl = null;
    }
  }
  function fail(msg) {
    typing(false);
    var row = bubble({ sender: 'bot', body: msg || L.fail });
    row.classList.add('is-error');
    log.appendChild(row);
    if (isNearBottom()) log.scrollTop = log.scrollHeight;
  }

  function send(data, pendingText) {
    if (busy) return;
    busy = true;
    data.append('after', lastId);
    if (pendingText) {
      var temp = bubble({ sender: 'visitor', body: pendingText });
      temp.classList.add('is-pending');
      log.appendChild(temp);
      log.scrollTop = log.scrollHeight;
    }
    typing(true);
    post(data).then(function (res) {
      log.querySelectorAll('.is-pending').forEach(function (el) { el.remove(); });
      typing(false);
      if (!res.success) return fail(res.message);
      render(res);
      log.scrollTop = log.scrollHeight;
    }).catch(function () {
      log.querySelectorAll('.is-pending').forEach(function (el) { el.remove(); });
      fail(window.APP.i18n && window.APP.i18n.offline);
    }).finally(function () { busy = false; });
  }
  function sendText(text) {
    text = (text || '').trim();
    if (!text) return;
    var fd = new FormData();
    fd.append('action', 'send');
    fd.append('text', text);
    send(fd, text);
  }
  box.chatSendText = sendText;

  // ແນບໄຟລ໌: ເລືອກໄດ້ຫຼາຍໄຟລ໌ → ສົ່ງເທື່ອລະໄຟລ໌ (ແຕ່ລະໄຟລ໌ = 1 ຂໍ້ຄວາມ); ຜິດພາດ (ໃຫຍ່ເກີນ / ບໍ່ຮອງຮັບ) → ຂໍ້ຄວາມເຕືອນ
  var attachBtn = box.querySelector('[data-chat-attach]'), attachInput = box.querySelector('[data-chat-file]');
  if (attachBtn && attachInput) {
    // ປຸ່ມໄຟລ໌ → ເລືອກໄດ້ແຕ່ເອກະສານ; ປຸ່ມຮູບ [data-chat-image] → ແຕ່ຮູບ + ວິດີໂອ (input ດຽວກັນ ປ່ຽນ accept ກ່ອນເປີດ)
    attachBtn.addEventListener('click', function () {
      if (attachInput.getAttribute('data-accept-docs')) attachInput.setAttribute('accept', attachInput.getAttribute('data-accept-docs'));
      attachInput.click();
    });
    var imageBtn = box.querySelector('[data-chat-image]');
    if (imageBtn) imageBtn.addEventListener('click', function () {
      if (attachInput.getAttribute('data-accept-media')) attachInput.setAttribute('accept', attachInput.getAttribute('data-accept-media'));
      attachInput.click();
    });
    attachInput.addEventListener('change', function () {
      var picked = Array.prototype.slice.call(attachInput.files || [], 0, 10);
      attachInput.value = '';
      // ຢືນຢັນກ່ອນສົ່ງ (assets/js/chat_confirm.js): ເບິ່ງຕົວຢ່າງ, ເອົາອອກໄດ້, ກົດ "ສົ່ງ" ຈຶ່ງອັບໂຫຼດ
      if (window.WlaoChatConfirm) WlaoChatConfirm.ask(picked, sendFiles); else sendFiles(picked);
    });
    function sendFiles(files) {
      (function next() {
        var f = files.shift();
        if (!f) return;
        var fd = new FormData();
        fd.append('action', 'file');
        fd.append('file', f);
        fd.append('after', lastId);
        var temp = bubble({ sender: 'visitor', body: f.name + ' (' + fmtSize(f.size) + ') …' });
        temp.classList.add('is-pending');
        log.appendChild(temp);
        log.scrollTop = log.scrollHeight;
        post(fd).then(function (res) {
          temp.remove();
          if (!res.success) return fail(res.message);
          render(res);
          log.scrollTop = log.scrollHeight;
        }).catch(function () { temp.remove(); fail(L.fileFail); }).then(next);
      })();
    }
  }   // assets/js/chat_stickers.js ສົ່ງສະຕິກເກີຜ່ານອັນນີ້

  var sendBtn = form.querySelector('.chat-send');
  function updateActionBtn() {
    var has = !!(input && input.value.trim().length > 0);
    if (sendBtn) sendBtn.hidden = !has;
    if (micBtn) micBtn.hidden = has;
  }
  updateActionBtn();

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    sendText(input.value);
    input.value = '';
    autosize();
    updateActionBtn();
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); form.requestSubmit(); }
  });
  function autosize() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; }
  input.addEventListener('input', function () {
    autosize();
    updateActionBtn();
  });
  input.addEventListener('focus', function () {
    setTimeout(function () {
      if (log) {
        log.scrollTop = log.scrollHeight;
        requestAnimationFrame(function () { if (log) log.scrollTop = log.scrollHeight; });
      }
    }, 250);
  });
  var quickBar = box.querySelector('[data-chat-quick]');
  var qPrev = box.querySelector('[data-chat-qprev]');
  var qNext = box.querySelector('[data-chat-qnext]');
  var qDragged = false;
  if (quickBar) {
    function syncQuickNav() {
      var maxScroll = quickBar.scrollWidth - quickBar.clientWidth;
      if (qPrev) qPrev.disabled = quickBar.scrollLeft <= 2;
      if (qNext) qNext.disabled = quickBar.scrollLeft >= maxScroll - 2;
    }
    if (qPrev) {
      qPrev.addEventListener('click', function () {
        quickBar.scrollBy({ left: -260, behavior: 'smooth' });
      });
    }
    if (qNext) {
      qNext.addEventListener('click', function () {
        quickBar.scrollBy({ left: 260, behavior: 'smooth' });
      });
    }
    quickBar.addEventListener('scroll', syncQuickNav, { passive: true });
    window.addEventListener('resize', syncQuickNav);
    setTimeout(syncQuickNav, 60);

    // ໝຸນລູກກິ້ງເມົາ (Mouse Wheel) ເພື່ອເລື່ອນຊ້າຍ-ຂວາ
    quickBar.addEventListener('wheel', function (e) {
      if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
        e.preventDefault();
        quickBar.scrollLeft += e.deltaY;
      }
    }, { passive: false });

    // ຄຣິກລາກເມົາຊ້າຍ-ຂວາ ເພື່ອເລື່ອນເບິ່ງຂໍ້ຄວາມດ່ວນ
    var isDown = false, startX = 0, startLeft = 0;
    quickBar.addEventListener('mousedown', function (e) {
      isDown = true;
      qDragged = false;
      startX = e.pageX;
      startLeft = quickBar.scrollLeft;
      quickBar.classList.add('is-dragging');
    });
    window.addEventListener('mousemove', function (e) {
      if (!isDown) return;
      var dx = e.pageX - startX;
      if (Math.abs(dx) > 5) qDragged = true;
      if (qDragged) {
        quickBar.scrollLeft = startLeft - dx;
      }
    });
    window.addEventListener('mouseup', function () {
      if (!isDown) return;
      isDown = false;
      quickBar.classList.remove('is-dragging');
      setTimeout(function () { qDragged = false; }, 0);
    });
  }

  box.querySelectorAll('[data-chat-ask]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      if (qDragged) {
        e.preventDefault();
        return;
      }
      sendText(b.getAttribute('data-chat-ask') || b.textContent);
    });
  });

  contact.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(contact);
    fd.append('action', 'contact');
    contact.hidden = true;
    send(fd, null);
  });
  box.querySelector('[data-chat-contact-close]').addEventListener('click', function () {
    sessionStorage.setItem(CLOSED_KEY, '1');
    contact.hidden = true;
  });

  // ສົ່ງຕຳແໜ່ງ ([data-chat-loc]) → api action=location → ຄຳຕອບມີລິ້ງນຳທາງມາຮ້ານ + ໄລຍະທາງ. ໃຊ້ໄດ້ສະເພາະ https / localhost
  box.querySelector('[data-chat-loc]').addEventListener('click', function () {
    if (!window.isSecureContext || !navigator.geolocation) return fail(L.locHttps);
    if (busy) return;
    var btn = this;
    btn.disabled = true;
    navigator.geolocation.getCurrentPosition(function (pos) {
      btn.disabled = false;
      var fd = new FormData();
      fd.append('action', 'location');
      fd.append('lat', pos.coords.latitude);
      fd.append('lng', pos.coords.longitude);
      send(fd, L.myLoc);
    }, function () {
      btn.disabled = false;
      fail(L.locDenied);
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
  });

  // ອັດສຽງແບບ WhatsApp (assets/js/voice_rec.js): ກົດສັ້ນ = ລັອກ (ຖັງຂີ້ເຫຍື້ອ / ຢຸດຊົ່ວຄາວ / ສົ່ງ), ກົດຄ້າງ = ປ່ອຍສົ່ງ,
  // ປັດຂຶ້ນ = ລັອກ, ປັດຊ້າຍ = ຍົກເລີກ; ຄື້ນສຽງສົດ; ສຽງງຽບ = ບໍ່ສົ່ງ
  if (window.WlaoVoice && micBtn) {
    WlaoVoice.attach({
      mic: micBtn,
      form: form,
      onError: fail,
      onSend: function (blob, secs, type) {
        var fd = new FormData();
        fd.append('action', 'voice');
        fd.append('secs', secs);
        fd.append('audio', blob, 'voice.' + (type.indexOf('mp4') > -1 ? 'm4a' : type.indexOf('ogg') > -1 ? 'ogg' : 'webm'));
        send(fd, L.voice + ' (' + secs + 's)');
      }
    });
  }

  // ປະຫວັດ + ດຶງຂໍ້ຄວາມໃໝ່
  function poll() {
    if (busy || document.hidden) return;
    fetch(endpoint + '?after=' + lastId, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); }).then(render).catch(function () {});
  }
  poll();
  setInterval(poll, 4000);
  autosize();
})();
