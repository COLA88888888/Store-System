/*
 * pos-sounds.js — ສຽງຂອງລະບົບ (ສັງເຄາະດ້ວຍ Web Audio, ບໍ່ມີໄຟລ໌ສຽງ) ໃຊ້ຮ່ວມກັນທຸກໜ້າ:
 *   MiniposSounds.doorChime()  ກະດິ່ງປະຕູ "ຕິງ-ຕ່ອງ" ແບບຮ້ານສະດວກຊື້ — ພະນັກງານລັອກອິນ (auth/partials/login/login_js.php)
 *                              ແລະ ພະນັກງານຍ່າງເຂົ້າຮ້ານໃນຈໍລູກຄ້າ (ບໍ່ດັງຊ້ຳ ຖ້າຫາກໍດັງໃນ 10 ວິ — ເຄື່ອງດຽວກັນ)
 *   MiniposSounds.saleChime()  ສຽງຂາຍສຳເລັດ "ຈິ໋ງ!" — ລິ້ນຊັກເກັບເງິນເປີດ + ກະດິ່ງຫຼຽນ (ໜ້າຂາຍ ແລະ ຈໍລູກຄ້າ)
 * Browser ຫຼິ້ນສຽງໄດ້ຫຼັງຜູ້ໃຊ້ແຕະ/ກົດໜ້າເວັບເທື່ອທຳອິດ — ໄຟລ໌ນີ້ປົດລັອກສຽງເອງເມື່ອມີການແຕະ/ກົດ.
 */
(function (window, document) {
  if (window.MiniposSounds) return;
  var ctx = null;
  var CHIME_KEY = 'StoreMini.door_chime_at';

  function audio() {
    try {
      if (!ctx) {
        var A = window.AudioContext || window.webkitAudioContext;
        if (!A) return null;
        ctx = new A();
      }
      if (ctx.state === 'suspended') ctx.resume();
      return ctx;
    } catch (e) { return null; }
  }
  ['pointerdown', 'keydown', 'touchstart'].forEach(function (ev) {
    document.addEventListener(ev, audio, { passive: true, capture: true });
  });

  function master(c, vol) {
    var g = c.createGain();
    g.gain.value = vol;
    g.connect(c.destination);
    return g;
  }

  // ສຽງກະດິ່ງໂລຫະ: ຄວາມຖີ່ຫຼັກ + overtone ທີ່ບໍ່ເປັນຄູ່ (ແບບລະຄັງ) ດັງກ້ອງແລ້ວຄ່ອຍໆຈາງ
  var BELL_PARTIALS = [[1, 1], [2.0, 0.32], [2.76, 0.2], [5.4, 0.07]];
  function bell(c, out, freq, at, dur, vol) {
    BELL_PARTIALS.forEach(function (p) {
      var o = c.createOscillator(), g = c.createGain();
      var d = dur / Math.sqrt(p[0]);   // overtone ສູງ ຈາງໄວກວ່າ
      o.type = 'sine';
      o.frequency.setValueAtTime(freq * p[0], at);
      g.gain.setValueAtTime(0.0001, at);
      g.gain.exponentialRampToValueAtTime(vol * p[1], at + 0.006);
      g.gain.exponentialRampToValueAtTime(0.0001, at + d);
      o.connect(g); g.connect(out);
      o.start(at); o.stop(at + d + 0.05);
    });
  }

  // ກະດິ່ງປະຕູ "ຕິງ-ຕ່ອງ": ໂນດສູງ (E6) ຕາມດ້ວຍໂນດຕ່ຳ (C6)
  function doorChime() {
    try {
      var last = parseInt(window.localStorage.getItem(CHIME_KEY) || '0', 10);
      if (Date.now() - last < 10000) return;   // ຫາກໍດັງ (ໜ້າຕ່າງອື່ນໃນເຄື່ອງດຽວກັນ) → ບໍ່ດັງຊ້ຳ
      window.localStorage.setItem(CHIME_KEY, String(Date.now()));
    } catch (e) {}
    var c = audio();
    if (!c) return;
    var t = c.currentTime + 0.03, out = master(c, 0.55);
    bell(c, out, 1318.5, t, 1.5, 0.34);          // ຕິງ
    bell(c, out, 1046.5, t + 0.46, 2.1, 0.38);   // ຕ່ອງ
  }

  // ສຽງຂາຍສຳເລັດ "ຈິ໋ງ!": ສຽງລິ້ນຊັກເປີດ (noise ສັ້ນໆ) + ກະດິ່ງສອງໂນດ + ສຽງຫຼຽນລະຍິບ
  function saleChime() {
    var c = audio();
    if (!c) return;
    var t = c.currentTime + 0.02, out = master(c, 0.5);
    try {
      var len = Math.floor(c.sampleRate * 0.05), buf = c.createBuffer(1, len, c.sampleRate), data = buf.getChannelData(0);
      for (var i = 0; i < len; i++) data[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, 3);
      var src = c.createBufferSource(), bp = c.createBiquadFilter(), ng = c.createGain();
      src.buffer = buf;
      bp.type = 'bandpass'; bp.frequency.value = 2600; bp.Q.value = 1.2;
      ng.gain.value = 0.55;
      src.connect(bp); bp.connect(ng); ng.connect(out);
      src.start(t);
    } catch (e) {}
    bell(c, out, 1567.98, t + 0.05, 0.8, 0.26);   // G6
    bell(c, out, 2093.0, t + 0.13, 1.3, 0.30);    // C7
    bell(c, out, 2637.0, t + 0.21, 0.35, 0.10);   // ຫຼຽນ
    bell(c, out, 3136.0, t + 0.27, 0.30, 0.08);
  }

  window.MiniposSounds = { doorChime: doorChime, saleChime: saleChime, unlock: audio };
})(window, document);
