/**
 * ອັດສຽງແບບ WhatsApp — ໃຊ້ຮ່ວມ: ໜ້າແຊັດ (assets/js/chat.js) ແລະ ແຊັດລອຍ (assets/js/floating_chat.js).
 *   - ກົດປຸ່ມໄມແລ້ວປ່ອຍ (ກົດສັ້ນ)  → ເລີ່ມອັດແບບລັອກ: ແຖບ [ຖັງຂີ້ເຫຍື້ອ] ● 0:03 [ຄື້ນສຽງ] [ຢຸດຊົ່ວຄາວ] [ສົ່ງ]
 *   - ກົດຄ້າງໄວ້                 → ອັດສຽງ; ປ່ອຍນິ້ວ = ສົ່ງ, ປັດຂຶ້ນ = ລັອກ (ປ່ອຍນິ້ວໄດ້), ປັດຊ້າຍ = ຍົກເລີກ
 *   - ຢຸດຊົ່ວຄາວ (MediaRecorder.pause) → ກົດອັດຕໍ່ ຫຼື ກົດສົ່ງໄດ້ເລີຍ
 *   - ຄື້ນສຽງສົດ (AnalyserNode), ສູງສຸດ 120 ວິ (ຄົບແລ້ວສົ່ງເອງ), ສຽງງຽບທັງໝົດ = ບໍ່ສົ່ງ (ແຈ້ງໃຫ້ກວດໄມ)
 *   - ມືຖື (≤640px): ແຖບ 2 ແຖວ — ເທິງ: ເວລາ + ຄື້ນສຽງ, ລຸ່ມ: ຖັງຂີ້ເຫຍື້ອ | ຢຸດ | ສົ່ງ (CSS .vr-* ໃນ assets/css/components.css)
 * ໃຊ້: WlaoVoice.attach({ mic, form, onSend: function (blob, secs, type) {}, onError: function (msg) {}, labels: {...} })
 *   form = ຟອມພິມຂໍ້ຄວາມທີ່ມີປຸ່ມໄມ (ກົດຄ້າງ → ແຖບວາງທັບຟອມ, ລັອກ → ແຖບແທນຟອມ)
 * ຂໍ້ຄວາມ (ແປແລ້ວ) ຈາກ window.WLAO_VOICE_L (layouts/footer.php)
 */
(function () {
  var MAX_SECS = 120, BARS = 34, TAP_MS = 350;
  var IC = {
    trash: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg>',
    pause: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1.2"/><rect x="14" y="5" width="4" height="14" rx="1.2"/></svg>',
    mic: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5"/></svg>',
    send: '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6 3.4 10l12.6 2-12.6 2z"/></svg>',
    lock: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
    up: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>'
  };
  function lbl() {
    var d = { cancel: 'ຍົກເລີກ', pause: 'ຢຸດຊົ່ວຄາວ', resume: 'ອັດຕໍ່', send: 'ສົ່ງ', slideCancel: 'ປັດຊ້າຍເພື່ອຍົກເລີກ', lock: 'ປັດຂຶ້ນເພື່ອລັອກ',
              noMic: 'ບຣາວເຊີນີ້ບໍ່ຮອງຮັບການອັດສຽງ', denied: 'ກະລຸນາອະນຸຍາດໃຫ້ໃຊ້ໄມໂຄຣໂຟນ ເພື່ອອັດສຽງ',
              https: 'ການອັດສຽງໃຊ້ໄດ້ສະເພາະ https', silent: 'ບໍ່ໄດ້ຍິນສຽງ — ກະລຸນາກວດໄມໂຄຣໂຟນ ແລ້ວລອງໃໝ່' };
    var o = window.WLAO_VOICE_L || {};
    for (var k in o) if (o[k]) d[k] = o[k];
    return d;
  }
  function pickMime() {
    var types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    for (var i = 0; i < types.length; i++) if (window.MediaRecorder && MediaRecorder.isTypeSupported(types[i])) return types[i];
    return '';
  }
  function fmt(s) { return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }

  function attach(o) {
    var mic = o.mic, form = o.form;
    if (!mic || !form) return;
    var L = lbl(), onErr = o.onError || function (m) { alert(m); };

    // ---------- ແຖບອັດສຽງ ----------
    var bar = document.createElement('div');
    bar.className = 'vr-bar';
    bar.hidden = true;
    bar.innerHTML =
      '<button type="button" class="vr-btn vr-trash" data-vr-cancel aria-label="' + L.cancel + '" title="' + L.cancel + '">' + IC.trash + '</button>' +
      '<span class="vr-time"><i class="vr-dot"></i><b data-vr-time>0:00</b></span>' +
      '<span class="vr-wave" data-vr-wave></span>' +
      '<span class="vr-slide" data-vr-slide>‹ ' + L.slideCancel + '</span>' +
      '<button type="button" class="vr-btn vr-pause" data-vr-pause aria-label="' + L.pause + '" title="' + L.pause + '">' + IC.pause + '</button>' +
      '<button type="button" class="vr-btn vr-send" data-vr-send aria-label="' + L.send + '" title="' + L.send + '">' + IC.send + '</button>';
    var lockHint = document.createElement('div');
    lockHint.className = 'vr-lockhint';
    lockHint.hidden = true;
    lockHint.innerHTML = IC.lock + IC.up + '<small>' + L.lock + '</small>';
    var wave = bar.querySelector('[data-vr-wave]'), timeEl = bar.querySelector('[data-vr-time]'), pauseBtn = bar.querySelector('[data-vr-pause]');
    var bars = [];
    for (var i = 0; i < BARS; i++) { var sp = document.createElement('i'); wave.appendChild(sp); bars.push(sp); }
    var levels = [];

    // ---------- ສະຖານະ ----------
    var rec = null, stream = null, chunks = [], actx = null, analyser = null, peak = 0;
    var mode = 'idle';            // idle | starting | hold | locked
    var paused = false, activeMs = 0, segStart = 0, tick = null, cancelled = false;
    var downAt = 0, downX = 0, downY = 0, pointerId = null, releasedEarly = false;

    function elapsed() { return Math.floor((activeMs + (paused || !segStart ? 0 : Date.now() - segStart)) / 1000); }
    function paintWave() {
      for (var i = 0; i < BARS; i++) {
        var v = levels[levels.length - BARS + i];
        bars[i].style.height = (v === undefined ? 3 : Math.max(3, Math.round(v * 26))) + 'px';
        bars[i].classList.toggle('is-on', v !== undefined);
      }
    }
    function sample() {
      if (paused || !analyser) return;
      var buf = new Uint8Array(analyser.fftSize), max = 0;
      analyser.getByteTimeDomainData(buf);
      for (var i = 0; i < buf.length; i++) max = Math.max(max, Math.abs(buf[i] - 128) / 128);
      peak = Math.max(peak, max);
      levels.push(Math.min(1, max * 2.6));
      if (levels.length > 400) levels.splice(0, levels.length - BARS);
      paintWave();
    }
    function showBar(m) {
      mode = m;
      bar.hidden = false;
      bar.classList.toggle('is-hold', m === 'hold');
      bar.classList.toggle('is-locked', m === 'locked');
      if (m === 'hold') {
        if (bar.parentNode !== form) form.appendChild(bar);   // ກົດຄ້າງ: ແຖບວາງທັບຟອມ (ປຸ່ມໄມຢູ່ເທິງສຸດ — ນິ້ວຍັງຢູ່ທີ່ເກົ່າ)
        if (getComputedStyle(form).position === 'static') form.style.position = 'relative';
        if (lockHint.parentNode !== form) form.appendChild(lockHint);
        lockHint.hidden = false;
        mic.classList.add('vr-holding');
      } else {
        form.parentNode.insertBefore(bar, form.nextSibling);   // ລັອກ: ແຖບແທນຟອມ
        form.hidden = true; form.classList.add('vr-form-off');   // class ກັນ CSS ຂອງໜ້າ (display:flex) ທັບ [hidden]
        lockHint.hidden = true;
        mic.classList.remove('vr-holding');
        bar.querySelector('[data-vr-slide]').style.transform = '';
      }
    }
    function reset() {
      clearInterval(tick); tick = null;
      if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
      if (actx) { actx.close().catch(function () {}); }
      stream = null; actx = null; analyser = null; rec = null;
      mode = 'idle'; paused = false; activeMs = 0; segStart = 0; levels = []; paintWave();
      bar.hidden = true; lockHint.hidden = true; form.hidden = false; form.classList.remove('vr-form-off');
      bar.classList.remove('is-paused', 'is-hold', 'is-locked');
      mic.classList.remove('vr-holding');
      pauseBtn.innerHTML = IC.pause; pauseBtn.setAttribute('aria-label', L.pause); pauseBtn.title = L.pause;
    }
    function start(asLocked) {
      if (!window.isSecureContext) { onErr(L.https); return; }
      if (!navigator.mediaDevices || !window.MediaRecorder) { onErr(L.noMic); return; }
      mode = 'starting'; releasedEarly = false;
      navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } }).then(function (s) {
        if (mode !== 'starting') { s.getTracks().forEach(function (t) { t.stop(); }); return; }   // ຍົກເລີກກ່ອນໄມພ້ອມ
        stream = s; peak = 0; chunks = []; cancelled = false;
        try {
          actx = new (window.AudioContext || window.webkitAudioContext)();
          analyser = actx.createAnalyser(); analyser.fftSize = 512;
          actx.createMediaStreamSource(s).connect(analyser);
        } catch (e) { analyser = null; peak = 1; }
        var mime = pickMime();
        rec = new MediaRecorder(s, mime ? { mimeType: mime } : undefined);
        rec.ondataavailable = function (e) { if (e.data && e.data.size) chunks.push(e.data); };
        rec.onstop = function () {
          var secs = Math.max(1, elapsed()), type = (rec && rec.mimeType) || mime || 'audio/webm', c = chunks, pk = peak, cx = cancelled;
          reset();
          if (cx || !c.length) return;
          if (pk < 0.03) { onErr(L.silent); return; }
          o.onSend(new Blob(c, { type: type }), secs, type);
        };
        rec.start(250);
        activeMs = 0; segStart = Date.now(); paused = false;
        timeEl.textContent = '0:00';
        showBar(asLocked || releasedEarly ? 'locked' : 'hold');
        tick = setInterval(function () {
          sample();
          var s = elapsed();
          timeEl.textContent = fmt(s);
          if (s >= MAX_SECS) finish(false);
        }, 90);
      }).catch(function () { mode = 'idle'; onErr(L.denied); });
    }
    function finish(cancel) {
      if (mode === 'starting') { mode = 'idle'; return; }
      if (!rec) { reset(); return; }
      cancelled = !!cancel;
      if (paused && segStart) segStart = 0;
      if (!paused) { activeMs += Date.now() - segStart; segStart = 0; }
      paused = true;
      clearInterval(tick); tick = null;
      try { if (rec.state === 'paused') rec.resume(); } catch (e) {}
      if (rec.state !== 'inactive') rec.stop(); else reset();
    }
    function togglePause() {
      if (!rec) return;
      if (!paused) {
        try { rec.pause(); } catch (e) {}
        activeMs += Date.now() - segStart; segStart = 0; paused = true;
        bar.classList.add('is-paused');
        pauseBtn.innerHTML = IC.mic; pauseBtn.setAttribute('aria-label', L.resume); pauseBtn.title = L.resume;
      } else {
        try { rec.resume(); } catch (e) {}
        segStart = Date.now(); paused = false;
        bar.classList.remove('is-paused');
        pauseBtn.innerHTML = IC.pause; pauseBtn.setAttribute('aria-label', L.pause); pauseBtn.title = L.pause;
      }
    }

    // ---------- ປຸ່ມໄມ: ກົດສັ້ນ = ລັອກ, ກົດຄ້າງ = ປ່ອຍສົ່ງ / ປັດຂຶ້ນລັອກ / ປັດຊ້າຍຍົກເລີກ ----------
    mic.style.touchAction = 'none';
    mic.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    mic.addEventListener('click', function (e) { e.preventDefault(); e.stopImmediatePropagation(); }, true);   // ກັນຕົວຈັດການ click ເກົ່າ
    mic.addEventListener('pointerdown', function (e) {
      if (mode !== 'idle' || (e.button !== undefined && e.button !== 0)) return;
      e.preventDefault();
      downAt = Date.now(); downX = e.clientX; downY = e.clientY; pointerId = e.pointerId;
      try { mic.setPointerCapture(e.pointerId); } catch (err) {}
      start(false);
    });
    mic.addEventListener('pointermove', function (e) {
      if (e.pointerId !== pointerId || mode !== 'hold') return;
      var dx = e.clientX - downX, dy = e.clientY - downY;
      if (dy < -70) { showBar('locked'); pointerId = null; return; }        // ປັດຂຶ້ນ → ລັອກ
      if (dx < -90) { pointerId = null; finish(true); return; }            // ປັດຊ້າຍ → ຍົກເລີກ
      bar.querySelector('[data-vr-slide]').style.transform = 'translateX(' + Math.min(0, dx) + 'px)';
      lockHint.style.transform = 'translateY(' + Math.min(0, dy * 0.5) + 'px)';
    });
    function up(e) {
      if (e.pointerId !== pointerId) return;
      pointerId = null;
      lockHint.style.transform = '';
      var quick = Date.now() - downAt < TAP_MS;
      if (mode === 'starting') { releasedEarly = true; return; }   // ໄມຍັງບໍ່ພ້ອມ → ເປີດແບບລັອກ
      if (mode !== 'hold') return;
      if (quick) { showBar('locked'); return; }                     // ກົດສັ້ນ → ລັອກ (ກົດສົ່ງ / ຢຸດ ໄດ້)
      finish(e.type === 'pointercancel');                            // ປ່ອຍນິ້ວ → ສົ່ງ
    }
    mic.addEventListener('pointerup', up);
    mic.addEventListener('pointercancel', up);

    bar.querySelector('[data-vr-cancel]').addEventListener('click', function () { finish(true); });
    bar.querySelector('[data-vr-send]').addEventListener('click', function () { finish(false); });
    pauseBtn.addEventListener('click', togglePause);
    window.addEventListener('pagehide', function () { if (mode !== 'idle') finish(true); });   // ອອກຈາກໜ້າ / ປິດແທັບ ລະຫວ່າງອັດ = ຍົກເລີກ (ບໍ່ສົ່ງເອງ)
    paintWave();
    return { cancel: function () { finish(true); } };
  }

  window.WlaoVoice = { attach: attach };
})();
