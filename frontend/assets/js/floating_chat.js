/**
 * floating_chat.js — ລະບົບປຸ່ມແຊັດລອຍ (Floating Chat Widget)
 * ເຊື່ອມຕໍ່ກັບ api/chat.php: ເປີດ/ປິດກ່ອງແຊັດ, ໂຫຼດປະຫວັດ, ສົ່ງຂໍ້ຄວາມ, ຖາມດ່ວນ, ຝາກເບີໂທ
 */
(function () {
  var widget = document.querySelector('[data-floating-chat]');
  if (!widget) return;

  var isContact = widget.getAttribute('data-is-contact') === '1' ||
                  document.body.classList.contains('page-contact') ||
                  !!document.querySelector('.ct-form.chat') ||
                  !!document.querySelector('[data-chat]');
  if (isContact) {
    widget.style.display = 'none';
    if (widget.parentNode) widget.parentNode.removeChild(widget);
    return;
  }
  var trigger = document.getElementById('fcTrigger');
  var popup = document.getElementById('fcPopup');
  var closeBtn = document.getElementById('fcCloseBtn');
  var chatArea = widget.querySelector('[data-fc-chat]');
  var log = document.getElementById('fcLog');
  var inputForm = document.getElementById('fcInputForm');
  var inputText = document.getElementById('fcInputText');
  var sendBtn = document.getElementById('fcSendBtn');
  var micBtn = document.getElementById('fcMicBtn');
  var recBar = document.getElementById('fcRecBar');
  var recTime = document.getElementById('fcRecTime');
  var recCancel = document.getElementById('fcRecCancel');
  var recSend = document.getElementById('fcRecSend');
  var quickBar = document.getElementById('fcQuickBar');
  var contactForm = document.getElementById('fcContactForm');

  var endpoint = chatArea ? chatArea.getAttribute('data-endpoint') : '/api/chat.php';
  var lastId = 0;
  var busy = false;
  var timer = null;
  var isOpen = false;
  var typingEl = null;

  // ສະຫຼັບປຸ່ມ: ຖ້າບໍ່ມີຂໍ້ຄວາມ = ສະແດງໄອຄອນໄມ, ຖ້າມີຂໍ້ຄວາມ = ສະແດງປຸ່ມສົ່ງ
  function updateActionBtn() {
    var hasText = !!(inputText && inputText.value.trim().length > 0);
    if (sendBtn) sendBtn.hidden = !hasText;
    if (micBtn) micBtn.hidden = hasText;
  }
  updateActionBtn();

  var closeTimer = null;

  function togglePopup(open) {
    if (isContact) {
      // ຖ້າຢູ່ໜ້າຕິດຕໍ່ແລ້ວ → ເລື່ອນລົງຫ້ອງແຊັດຫຼັກຢ່າງນຸ່ມນວນ
      var mainChat = document.querySelector('.ct-form.chat');
      if (mainChat) {
        mainChat.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var area = mainChat.querySelector('textarea');
        if (area) area.focus();
        return;
      }
    }

    if (open === undefined) open = !isOpen;
    if (open === isOpen) return;
    isOpen = open;

    if (closeTimer) {
      clearTimeout(closeTimer);
      closeTimer = null;
    }

    if (isOpen) {
      if (typeof window.closeAllOverlays === 'function') {
        window.closeAllOverlays('chat');
      }
      try {
        document.dispatchEvent(new CustomEvent('wlao:close-overlays', { detail: { except: 'chat' } }));
      } catch (err) {}

      popup.hidden = false;
      // Force reflow ເພື່ອໃຫ້ CSS transition ເຮັວຽກສະໂລຂຶ້ນມາ
      void popup.offsetHeight;
      widget.classList.add('is-open');
      popup.classList.remove('is-closing');
      popup.classList.add('is-visible');

      // ເປີດແລ້ວ: ໂຫຼດຂໍ້ຄວາມທັນທີ ແລະ ໂຟກັສຊ່ອງພິມ
      loadMessages();
      startPolling();
      setTimeout(function () {
        if (inputText) inputText.focus();
        scrollBottom();
      }, 250);
    } else {
      widget.classList.remove('is-open');
      popup.classList.remove('is-visible');
      popup.classList.add('is-closing');
      stopPolling();

      // ລໍຖ້າໃຫ້ເອນິເມຊັ່ນສະໂລຍຸບລົງລຸ່ມຫາຍໄປຈົນຈົບກ່ອນ ຈຶ່ງເຊື່ອງ hidden
      closeTimer = setTimeout(function () {
        if (!isOpen) {
          popup.hidden = true;
          popup.classList.remove('is-closing');
        }
      }, 420);
    }
  }

  if (trigger) trigger.addEventListener('click', function () { togglePopup(); });
  if (closeBtn) closeBtn.addEventListener('click', function () { togglePopup(false); });

  window.FloatingChat = {
    isOpen: function () { return isOpen; },
    toggle: function (open) { togglePopup(open); },
    close: function () { if (isOpen) togglePopup(false); }
  };

  document.addEventListener('wlao:close-overlays', function (e) {
    if (e.detail && e.detail.except === 'chat') return;
    if (isOpen) togglePopup(false);
  });

  // ກົດປຸ່ມ Esc ເພື່ອປິດ
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) togglePopup(false);
  });

  function scrollBottom() {
    if (log) {
      try {
        log.scrollTo({ top: log.scrollHeight, behavior: 'smooth' });
      } catch (err) {
        log.scrollTop = log.scrollHeight;
      }
      requestAnimationFrame(function () {
        if (log) log.scrollTop = log.scrollHeight;
      });
    }
  }

  function post(data) {
    var csrf = (window.APP && window.APP.csrf) || '';
    return fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      body: data,
      headers: {
        'X-CSRF-Token': csrf,
        'Accept': 'application/json'
      }
    }).then(function (r) { return r.json(); });
  }

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

  var FC_STICKER_RE;   // emoji ດຽວ (ລວມ ສີຜິວ, ZWJ, ທຸງ 🇱🇦, ປຸ່ມເລກ) — ຄືກັບ assets/js/chat.js
  try { FC_STICKER_RE = new RegExp('^(?:\\p{Regional_Indicator}{2}|[0-9#*]\\uFE0F?\\u20E3|\\p{Extended_Pictographic}(?:\\uFE0F|\\p{Emoji_Modifier})?(?:\\u200D\\p{Extended_Pictographic}(?:\\uFE0F|\\p{Emoji_Modifier})?)*)$', 'u'); } catch (e) { FC_STICKER_RE = /^$/; }
  function fcSize(n) { n = +n || 0; return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
  function fcFileNode(f) {
    var a = document.createElement('a');
    a.href = f.url; a.target = '_blank'; a.rel = 'noopener';
    if (f.kind === 'image') {
      a.className = 'fc-att-img';
      var img = document.createElement('img'); img.src = f.url; img.alt = f.name || ''; img.loading = 'lazy';
      a.appendChild(img);
      return a;
    }
    if (f.kind === 'video') {
      var v = document.createElement('video');
      v.className = 'fc-att-video'; v.src = f.url; v.controls = true; v.preload = 'metadata';
      return v;
    }
    a.className = 'fc-att-file'; a.setAttribute('download', f.name || '');
    var ext = document.createElement('span'); ext.className = 'fc-att-ext';
    ext.textContent = (String(f.name || '').split('.').pop() || 'file').slice(0, 4).toUpperCase();
    var info = document.createElement('span');
    var nm = document.createElement('b'); nm.textContent = f.name || '';
    var sz = document.createElement('small'); sz.textContent = fcSize(f.size);
    info.appendChild(nm); info.appendChild(sz);
    a.appendChild(ext); a.appendChild(info);
    return a;
  }

  // ປຸ່ມໄຟລ໌ (ເອກະສານ) / ປຸ່ມຮູບ (ຮູບ + ວິດີໂອ) → ເລືອກໄດ້ຫຼາຍໄຟລ໌ → ສົ່ງເທື່ອລະໄຟລ໌ (api/chat.php action=file)
  var fcFile = document.querySelector('[data-fc-file]');
  function pickFiles(media) {
    if (!fcFile) return;
    fcFile.accept = fcFile.getAttribute(media ? 'data-accept-media' : 'data-accept-docs') || '';
    fcFile.click();
  }
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-fc-attach]')) { e.preventDefault(); pickFiles(false); }
    else if (e.target.closest('[data-fc-image]')) { e.preventDefault(); pickFiles(true); }
  });
  if (fcFile) fcFile.addEventListener('change', function () {
    var picked = Array.prototype.slice.call(fcFile.files || [], 0, 10);
    fcFile.value = '';
    // ຢືນຢັນກ່ອນສົ່ງ (assets/js/chat_confirm.js)
    if (window.WlaoChatConfirm) WlaoChatConfirm.ask(picked, fcSendFiles); else fcSendFiles(picked);
  });
  function fcSendFiles(files) {
    (function sendNext() {
      var f = files.shift();
      if (!f) return;
      var fd = new FormData();
      fd.append('action', 'file');
      fd.append('file', f);
      fd.append('after', String(lastId));
      post(fd).then(function (res) {
        if (res && res.success && Array.isArray(res.messages)) {
          res.messages.forEach(function (m) { appendBubble(m); if (m.id && m.id > lastId) lastId = m.id; });
        } else if (res && res.message) {
          alert(res.message);
        }
        setTimeout(sendNext, 1200);   // ກັນ "ສົ່ງຖີ່ເກີນໄປ" (chatTooFast)
      }).catch(function () { setTimeout(sendNext, 1200); });
    })();
  }

  function appendBubble(m) {
    if (!log) return;
    if (m.id && log.querySelector('[data-id="' + m.id + '"]')) return;
    if (!m.audio && !m.file && !String(m.body || '').trim()) return;   // ບໍ່ສະແດງກ່ອງຂໍ້ຄວາມເປົ່າ (ເຊັ່ນ bot ຕອບບໍ່ສຳເລັດ)

    var row = document.createElement('div');
    row.className = 'chat-msg is-' + m.sender;
    if (m.id) row.setAttribute('data-id', m.id);

    if (m.sender !== 'visitor') {
      var who = document.createElement('span');
      who.className = 'chat-name';
      who.textContent = 'WlaoDev';
      row.appendChild(who);
    }

    var b = document.createElement('div');
    b.className = 'chat-bubble';
    if (m.file) {   // ໄຟລ໌ແນບ (ຮູບ / ວິດີໂອ / ເອກະສານ ເຊັ່ນ quote.pdf ທີ່ພະນັກງານສົ່ງ)
      b.appendChild(fcFileNode(m.file));
      row.classList.add('is-file', 'is-file-' + m.file.kind);
    } else if (m.audio) {
      var au = document.createElement('audio');
      au.controls = true;
      au.preload = 'metadata';
      au.src = m.audio;
      b.appendChild(au);
    } else {
      linkify(b, m.body || '');
      if (FC_STICKER_RE.test(String(m.body || '').trim())) row.classList.add('is-sticker');   // emoji ດຽວ = ສະຕິກເກີ ໃຫຍ່ ບໍ່ມີຟອງ (ຄືໜ້າແຊັດ chat.js)
    }
    row.appendChild(b);

    log.appendChild(row);
    scrollBottom();
  }

  function showTyping() {
    if (typingEl) return;
    typingEl = document.createElement('div');
    typingEl.className = 'chat-msg is-bot';
    typingEl.innerHTML = '<span class="chat-name">WlaoDev</span><div class="chat-bubble chat-typing"><i></i><i></i><i></i></div>';
    log.appendChild(typingEl);
    scrollBottom();
  }

  function hideTyping() {
    if (typingEl && typingEl.parentNode) {
      typingEl.parentNode.removeChild(typingEl);
    }
    typingEl = null;
  }

  function loadMessages() {
    fetch(endpoint + '?after=' + lastId, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) return;
        if (Array.isArray(res.messages)) {
          res.messages.forEach(function (m) {
            appendBubble(m);
            if (m.id && m.id > lastId) lastId = m.id;
          });
        }
        if (contactForm) {
          contactForm.hidden = !res.need_contact;
        }
      })
      .catch(function () {});
  }

  function startPolling() {
    stopPolling();
    timer = setInterval(function () {
      if (!isOpen || document.hidden) return;
      loadMessages();
    }, 4000);
  }

  function stopPolling() {
    if (timer) { clearInterval(timer); timer = null; }
  }

  function sendMessage(text) {
    text = (text || '').trim();
    if (!text || busy) return;
    busy = true;

    // ສ້າງ bubble ຈຳລອງທັນທີ
    var tempMsg = { sender: 'visitor', body: text };
    appendBubble(tempMsg);
    if (inputText) {
      inputText.value = '';
      updateActionBtn();
    }
    showTyping();

    var fd = new FormData();
    fd.append('action', 'send');
    fd.append('text', text);
    fd.append('after', String(lastId));

    post(fd)
      .then(function (res) {
        hideTyping();
        busy = false;
        if (!res || !res.success) return;
        if (Array.isArray(res.messages)) {
          res.messages.forEach(function (m) {
            appendBubble(m);
            if (m.id && m.id > lastId) lastId = m.id;
          });
        }
        if (contactForm) {
          contactForm.hidden = !res.need_contact;
        }
      })
      .catch(function () {
        hideTyping();
        busy = false;
      });
  }

  if (inputForm) {
    inputForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (inputText) sendMessage(inputText.value);
    });
  }

  if (inputText) {
    inputText.addEventListener('input', updateActionBtn);
    inputText.addEventListener('keyup', updateActionBtn);
    inputText.addEventListener('change', updateActionBtn);
    inputText.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage(inputText.value);
      }
    });
  }

  // ອັດສຽງແບບ WhatsApp (assets/js/voice_rec.js) — ຄືກັບໜ້າແຊັດ: ກົດສັ້ນ = ລັອກ, ກົດຄ້າງ = ປ່ອຍສົ່ງ, ປັດຂຶ້ນ = ລັອກ, ປັດຊ້າຍ = ຍົກເລີກ
  function sendVoice(blob, secs, type) {
    appendBubble({ sender: 'visitor', audio: URL.createObjectURL(blob), time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) });
    showTyping();
    var fd = new FormData();
    fd.append('action', 'voice');
    fd.append('secs', String(secs));
    fd.append('audio', blob, 'voice.' + (type.indexOf('mp4') > -1 ? 'm4a' : type.indexOf('ogg') > -1 ? 'ogg' : 'webm'));
    fd.append('after', String(lastId));
    post(fd)
      .then(function (res) {
        hideTyping();
        if (!res || !res.success) { if (res && res.message) alert(res.message); return; }
        if (Array.isArray(res.messages)) {
          res.messages.forEach(function (m) {
            appendBubble(m);
            if (m.id && m.id > lastId) lastId = m.id;
          });
        }
        if (contactForm) contactForm.hidden = !res.need_contact;
      })
      .catch(function () { hideTyping(); });
  }
  if (window.WlaoVoice && micBtn && inputForm) {
    WlaoVoice.attach({ mic: micBtn, form: inputForm, onSend: sendVoice, onError: function (m) { alert(m); } });
  }

  // ຄຳຖາມດ່ວນ
  if (quickBar) {
    quickBar.querySelectorAll('[data-fc-ask]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var q = btn.getAttribute('data-fc-ask') || btn.textContent;
        sendMessage(q);
      });
    });
  }

  // ຟອມຝາກເບີໂທ
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(contactForm);
      fd.append('action', 'contact');
      post(fd).then(function (res) {
        if (res && res.success) {
          contactForm.hidden = true;
          appendBubble({
            sender: 'bot',
            body: 'ບັນທຶກຂໍ້ມູນຕິດຕໍ່ຮຽບຮ້ອຍແລ້ວ! ທີມງານຈະຕິດຕໍ່ຫາທ່ານໂດຍໄວທີ່ສຸດ ຂອບໃຈ.'
          });
        }
      });
    });
  }

  // ອັດຕະໂນມັດເຊື່ອງປ້າຍຂໍ້ຄວາມລອຍຫຼັງ 10 ວິນາທີ
  setTimeout(function () {
    var bubbleEl = widget.querySelector('.fc-badge-bubble');
    if (bubbleEl) {
      bubbleEl.style.opacity = '0';
      setTimeout(function () { bubbleEl.style.display = 'none'; }, 400);
    }
  }, 10000);

  // ເມື່ອເລື່ອນລົງຮອດສ່ວນທ້າຍ (footer-bottom) ຍົກປຸ່ມແຊັດຂຶ້ນເພື່ອບໍ່ໃຫ້ບັງປຸ່ມ "ກັບຂຶ້ນດ້ານເທິງ"
  var footerBottom = document.querySelector('.footer-bottom');
  if (footerBottom && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      widget.classList.toggle('is-above-footer', entries[0].isIntersecting);
    }, { threshold: 0.05 }).observe(footerBottom);
  }
})();
