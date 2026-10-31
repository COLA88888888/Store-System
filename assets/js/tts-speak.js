/**
 * tts-speak.js — Lao Text-to-Speech Player (Siangmuan API)
 * StoreMini Audio System
 */
(function (window) {
  'use strict';

  var queue = [];
  var isPlaying = false;
  var currentAudio = null;
  var selectedVoice = '';
  var preloadedAudio = {};

  // ການຕັ້ງຄ່າສຽງ (ຕັ້ງຄ່າ › ລະບົບສຽງ = config/tts_config.php): window.TTS_CONFIG ໃສ່ມາຈາກໜ້າ (pos.php,
  // customer_display.php) ແລະ ອ່ານຄືນທຸກ 10 ວິນາທີ — ປິດສະວິດ "ເປີດໃຊ້ງານສຽງ" → ບໍ່ມີສຽງເວົ້າເລີຍ (ລວມສຽງສຳຮອງຂອງ browser);
  // ປິດສະວິດຈຸດໃດ (pos_total / pos_sale / pos_stock / pos_queue / pos_welcome) → ຈຸດນັ້ນບໍ່ເວົ້າ.
  // ບໍ່ມີ TTS_CONFIG (ໜ້າອື່ນ) → ເປີດຢູ່ຄືເກົ່າ.
  var ttsCfg = (window.TTS_CONFIG && typeof window.TTS_CONFIG === 'object') ? window.TTS_CONFIG : null;
  function ttsAllowed(kind) {
    if (!ttsCfg) return true;
    if (!ttsCfg.enabled) return false;
    if (kind && ttsCfg[kind] === false) return false;
    return true;
  }

  var basePath = (function () {
    // ກວດສອບ base path ອັດຕະໂນມັດ ຕາມ folder ຂອງໜ້າທີ່ເປີດຢູ່
    var p = window.location.pathname;
    if (p.indexOf('/pages/') !== -1) {
      return '../../';
    }
    return '';
  })();

  function cleanSpeechUnits(text) {
    if (!text || typeof text !== 'string') return '';
    text = text.replace(/\bStoreMini\b/gi, 'ສະໂຕ ມິນິ');
    text = text.replace(/\bStore\s+Mini\b/gi, 'ສະໂຕ ມິນິ');
    text = text.replace(/(\d+(?:\.\d+)?)\s*(ml|mL|ML)\b/gi, '$1 ເອັມແອວ');
    text = text.replace(/\b(ml|mL|ML)\b/gi, 'ເອັມແອວ');
    text = text.replace(/(\d+(?:\.\d+)?)\s*(kg|Kg|KG)\b/gi, '$1 ກິໂລ');
    text = text.replace(/\b(kg|Kg|KG)\b/gi, 'ກິໂລ');
    text = text.replace(/(\d+(?:\.\d+)?)\s*(cc|CC)\b/gi, '$1 ຊີຊີ');
    return text;
  }

  function getAudioUrl(text, voice) {
    var v = voice || selectedVoice;
    var cleaned = cleanSpeechUnits(text);
    return basePath + 'api/tts_speak.php?text=' + encodeURIComponent(cleaned) + (v ? '&voice=' + encodeURIComponent(v) : '');
  }

  function preload(text, voice, kind) {
    if (!ttsAllowed(kind)) return;
    if (!text || typeof text !== 'string') return;
    text = text.trim();
    if (!text) return;
    var url = getAudioUrl(text, voice);
    if (preloadedAudio[url]) return;
    try {
      var a = new Audio();
      a.preload = 'auto';
      a.src = url;
      preloadedAudio[url] = a;
    } catch (e) {}
  }

  function fallbackWebSpeech(text, onDone) {
    if (!ttsAllowed()) return false;
    try {
      if ('speechSynthesis' in window) {
        var u = new SpeechSynthesisUtterance(text);
        u.lang = 'lo-LA';
        u.rate = 1.0;
        u.onend = function () { if (typeof onDone === 'function') onDone(); };
        u.onerror = function () { if (typeof onDone === 'function') onDone(); };
        window.speechSynthesis.speak(u);
        return true;
      }
    } catch (e) {}
    return false;
  }

  function playNext() {
    if (queue.length === 0) {
      isPlaying = false;
      currentAudio = null;
      return;
    }

    isPlaying = true;
    var item = queue.shift();
    var url = getAudioUrl(item.text, item.voice);

    var audio = preloadedAudio[url] || new Audio(url);
    currentAudio = audio;
    try { audio.currentTime = 0; } catch (e) {}

    audio.onended = function () {
      if (typeof item.onEnd === 'function') item.onEnd();
      playNext();
    };

    audio.onerror = function (e) {
      console.warn('[LaoTTS] Error playing API audio for:', item.text, e);
      var handled = fallbackWebSpeech(item.text, function () {
        if (typeof item.onEnd === 'function') item.onEnd();
        playNext();
      });
      if (!handled) {
        if (typeof item.onError === 'function') item.onError(e);
        playNext();
      }
    };

    audio.play().catch(function (err) {
      console.warn('[LaoTTS] Autoplay blocked or failed. Attempting fallback...', err);
      var handled = fallbackWebSpeech(item.text, function () {
        if (typeof item.onEnd === 'function') item.onEnd();
        playNext();
      });
      if (!handled) {
        if (typeof item.onError === 'function') item.onError(err);
        playNext();
      }
    });
  }

  var LaoTTS = {
    /**
     * ຫຼິ້ນສຽງຂໍ້ຄວາມພາສາລາວ (ເຂົ້າຄິວຫຼິ້ນຕາມລຳດັບ ບໍ່ຊ້ອນກັນ)
     * @param {string} text - ຂໍ້ຄວາມທີ່ຕ້ອງການໃຫ້ເວົ້າ
     * @param {function} onEnd - callback ເມື່ອສຽງຫຼິ້ນຈົບ
     * @param {function} onError - callback ເມື່ອເກີດຂໍ້ຜິດພາດ
     */
    speak: function (text, onEnd, onError, voice, kind) {
      if (!ttsAllowed(kind)) return;
      if (!text || typeof text !== 'string') return;
      text = text.trim();
      if (!text) return;

      queue.push({
        text: text,
        onEnd: onEnd,
        onError: onError,
        voice: voice
      });

      if (!isPlaying) {
        playNext();
      }
    },

    /**
     * ຕັ້ງຄ່າສຽງເລີ່ມຕົ້ນ (Voice ID)
     */
    setVoice: function (voiceId) {
      selectedVoice = voiceId || '';
    },

    getVoice: function () {
      return selectedVoice;
    },

    /**
     * ໂຫຼດສຽງໄວ້ລ່ວງໜ້າໃນ Background (Preload) ເພື່ອໃຫ້ຕອນກົດຫຼິ້ນແທ້ ຫຼິ້ນໄດ້ທັນທີ 0 millisecond
     */
    preload: function (text, voice, kind) {
      preload(text, voice, kind);
    },

    /**
     * ແປງລະຫັດສະກຸນເງິນເປັນຄຳອ່ານພາສາລາວ (KIP -> ກີບ, THB -> ບາດ, USD -> ໂດລາ, CNY -> ຢວນ)
     */
    resolveCurrencyName: function (c) {
      if (!c) return 'ກີບ';
      var u = String(c).trim().toUpperCase();
      if (u === 'THB' || u === 'BAHT' || u === 'ບາດ' || u === '฿') return 'ບາດ';
      if (u === 'USD' || u === 'DOLLAR' || u === 'ໂດລາ' || u === '$') return 'ໂດລາ';
      if (u === 'CNY' || u === 'YUAN' || u === 'RMB' || u === 'ຢວນ' || u === '¥') return 'ຢວນ';
      if (u === 'KIP' || u === 'LAK' || u === 'ກີບ' || u === '₭') return 'ກີບ';
      return String(c);
    },

    /**
     * ແປງຕົວເລກເປັນຄຳອ່ານພາສາລາວທຳມະຊາດ:
     * 15,000 -> "ສິບຫ້າພັນ", 20,000 -> "ຊາວພັນ", 100,000 -> "ໜຶ່ງແສນ"
     */
    numberToLaoWords: function (num) {
      if (num === null || num === undefined || num === '') return '';
      var numStr = String(num).replace(/[^\d.]/g, '');
      if (!numStr || isNaN(numStr)) return '';

      var parts = numStr.split('.');
      var intPart = parts[0].replace(/^0+/, '');
      if (!intPart) intPart = '0';

      var digits = ['', 'ໜຶ່ງ', 'ສອງ', 'ສາມ', 'ສີ່', 'ຫ້າ', 'ຫົກ', 'ເຈັດ', 'ແປດ', 'ເກົ້າ'];

      function underHundred(n) {
        if (n <= 0) return '';
        if (n < 10) return digits[n];
        var tens = Math.floor(n / 10);
        var units = n % 10;
        var r = '';
        if (tens === 1) r = 'ສິບ';
        else if (tens === 2) r = 'ຊາວ';
        else if (tens > 2) r = digits[tens] + 'ສິບ';
        if (units === 1) r += 'ເອັດ';
        else if (units > 1) r += digits[units];
        return r;
      }

      function underThousand(s) {
        while (s.length < 3) s = '0' + s;
        var h = parseInt(s[0], 10);
        var res = '';
        if (h > 0) res += digits[h] + 'ຮ້ອຍ';
        var sub = parseInt(s.substring(1), 10);
        if (sub > 0) res += underHundred(sub);
        return res;
      }

      function convertInt(s) {
        if (!s) return '';
        var len = s.length;
        if (len > 6) {
          var m = s.substring(0, len - 6);
          var rem = s.substring(len - 6).replace(/^0+/, '');
          return convertInt(m) + 'ລ້ານ' + (rem ? convertInt(rem) : '');
        }
        if (len === 6) {
          var first = parseInt(s[0], 10);
          var rest = s.substring(1).replace(/^0+/, '');
          return digits[first] + 'ແສນ' + (rest ? convertInt(rest) : '');
        }
        if (len === 5) {
          var th = parseInt(s.substring(0, 2), 10);
          var rest5 = s.substring(2);
          return underHundred(th) + 'ພັນ' + underThousand(rest5);
        }
        if (len === 4) {
          var f4 = parseInt(s[0], 10);
          var rest4 = s.substring(1);
          return digits[f4] + 'ພັນ' + underThousand(rest4);
        }
        if (len === 3) return underThousand(s);
        return underHundred(parseInt(s, 10));
      }

      var result = (intPart === '0') ? 'ສູນ' : convertInt(intPart);
      if (parts[1] && parseInt(parts[1], 10) > 0) {
        result += ' ຈຸດ';
        var dMap = { '0':'ສູນ','1':'ໜຶ່ງ','2':'ສອງ','3':'ສາມ','4':'ສີ່','5':'ຫ້າ','6':'ຫົກ','7':'ເຈັດ','8':'ແປດ','9':'ເກົ້າ' };
        for (var i = 0; i < parts[1].length; i++) {
          result += ' ' + (dMap[parts[1][i]] || parts[1][i]);
        }
      }
      return result.trim();
    },

    preloadTotalToPay: function (amount, currency) {
      if (!amount || amount <= 0) return;
      var curName = this.resolveCurrencyName(currency);
      var numWords = this.numberToLaoWords(amount) || Number(amount).toLocaleString();
      preload('ຍອດລວມທີ່ຕ້ອງຊຳລະ ' + numWords + ' ' + curName, undefined, 'pos_total');
    },

    /**
     * ສ້າງຂໍ້ຄວາມສຽງເວົ້າສຳລັບການຊຳລະ:
     * - ບອກຊ່ອງທາງການຊຳລະ: "ເງິນສົດ", "ເງິນໂອນ ຜ່ານທະນາຄານ [ຊື່ທະນາຄານ]" ຫຼື "ແຍກຈ່າຍ"
     * - ເງິນທອນ: ຖ້າເປັນ 0 ຈະບໍ່ອ່ານ, ຖ້າມີເງິນທອນ > 0 ຈຶ່ງອ່ານ
     * - ຕົວເລກອ່ານເປັນພາສາລາວທຳມະຊາດ (ເຊັ່ນ: ສິບຫ້າພັນ, ຊາວພັນ, ໜຶ່ງແສນ ບໍ່ອ່ານ ສູນໆໆ)
     */
    buildPaymentSpeechMessage: function (received, change, isQr, currency, splitData, bankName) {
      var curName = this.resolveCurrencyName(currency);
      var changeNum = Number(change || 0);
      var changeWords = this.numberToLaoWords(changeNum) || changeNum.toLocaleString();
      var msg = '';

      var bank = (typeof bankName === 'string') ? bankName : ((splitData && typeof splitData.bank === 'string') ? splitData.bank : ((typeof splitData === 'string') ? splitData : ''));
      bank = (bank || '').trim();

      var bankSpoken = '';
      if (bank) {
        if (bank.indexOf('ທະນາຄານ') !== -1) {
          bankSpoken = 'ຜ່ານ' + bank;
        } else {
          bankSpoken = 'ຜ່ານທະນາຄານ ' + bank;
        }
      }

      var isSplit = (isQr === 'split' || isQr === 'ເງິນສົດ + ໂອນ') || (splitData && splitData.cash && splitData.qr);
      var isTransfer = (isQr === true || isQr === 'qr' || isQr === 'transfer' || isQr === 'ເງິນໂອນ' || (typeof isQr === 'string' && (isQr.indexOf('ໂອນ') !== -1 || isQr.indexOf('QR') !== -1)));
      var isCredit = (isQr === 'credit' || isQr === 'ຕິດໜີ້');

      if (isCredit) {
        var recvWords = this.numberToLaoWords(received) || Number(received || 0).toLocaleString();
        msg = 'ບັນທຶກຂາຍຕິດໜີ້ ' + recvWords + ' ' + curName;
      } else if (isSplit) {
        var cashWords = this.numberToLaoWords(splitData && splitData.cash) || Number((splitData && splitData.cash) || 0).toLocaleString();
        var qrWords = this.numberToLaoWords(splitData && splitData.qr) || Number((splitData && splitData.qr) || 0).toLocaleString();
        msg = 'ໄດ້ຮັບເງິນສົດ ' + cashWords + ' ' + curName + ', ເງິນໂອນ ' + (bankSpoken ? bankSpoken + ' ' : '') + qrWords + ' ' + curName;
      } else if (isTransfer) {
        var recvWords = this.numberToLaoWords(received) || Number(received || 0).toLocaleString();
        msg = 'ໄດ້ຮັບເງິນໂອນ ' + (bankSpoken ? bankSpoken + ' ' : '') + recvWords + ' ' + curName;
      } else {
        var recvWords = this.numberToLaoWords(received) || Number(received || 0).toLocaleString();
        msg = 'ໄດ້ຮັບເງິນສົດ ' + recvWords + ' ' + curName;
      }

      // ເງິນທອນ: ຖ້າເປັນ 0 ບໍ່ອ່ານ, ຖ້າມີເງິນທອນ > 0 ຈຶ່ງອ່ານ
      if (changeNum > 0) {
        msg += ', ເງິນທອນ ' + changeWords + ' ' + curName;
      }
      msg += ', ຂອບໃຈທີ່ໃຊ້ບໍລິການ';

      return msg;
    },

    preloadPaymentAndChange: function (total, received, change, isQr, currency, splitData, bankName) {
      var bank = (typeof bankName === 'string') ? bankName : ((typeof splitData === 'string') ? splitData : ((splitData && splitData.bank) || ''));
      var sData = (typeof splitData === 'object' && splitData !== null) ? splitData : null;
      var msg = this.buildPaymentSpeechMessage(received, change, isQr, currency, sData, bank);
      preload(msg, undefined, 'pos_sale');
    },

    preloadOutOfStock: function (productName) {
      if (!productName) return;
      preload(productName + ', ສິນຄ້າໝົດແລ້ວ', undefined, 'pos_stock');
    },

    preloadLowStock: function (productName, remainingQty, unitName) {
      if (!productName) return;
      var u = unitName || 'ອັນ';
      var q = Number(remainingQty || 0).toLocaleString();
      preload(productName + ', ສິນຄ້າໃກ້ໝົດແລ້ວ, ເຫຼືອພຽງ ' + q + ' ' + u, undefined, 'pos_stock');
    },

    /**
     * ຢຸດສຽງທີ່ກຳລັງຫຼິ້ນ ແລະ ລ້າງຄິວສຽງ
     */
    stop: function () {
      if (currentAudio) {
        try {
          currentAudio.pause();
          currentAudio.currentTime = 0;
        } catch (e) {}
      }
      queue = [];
      isPlaying = false;
      currentAudio = null;
    },

    /**
     * ເວົ້າແຈ້ງຍອດລວມທີ່ຕ້ອງຊຳລະ (ຮອງຮັບຫຼາຍສະກຸນເງິນ)
     * @param {number|string} amount - ຈຳນວນເງິນທີ່ຕ້ອງຈ່າຍ
     * @param {string} currency - ສະກຸນເງິນ (KIP, THB, USD, CNY...)
     */
    speakTotalToPay: function (amount, currency) {
      if (!amount || amount <= 0) return;
      var curName = this.resolveCurrencyName(currency);
      var numWords = this.numberToLaoWords(amount) || Number(amount).toLocaleString();
      this.speak('ຍອດລວມທີ່ຕ້ອງຊຳລະ ' + numWords + ' ' + curName, null, null, undefined, 'pos_total');
    },

    /**
     * ເວົ້າແຈ້ງການຮັບເງິນ ແລະ ເງິນທອນ (ຮອງຮັບຫຼາຍສະກຸນເງິນ: ກີບ, ບາດ, ໂດລາ, ຢວນ...)
     * ພ້ອມບອກຊ່ອງທາງການຊຳລະ ແລະ ທະນາຄານ
     * @param {number|string} total - ຍອດລວມ
     * @param {number|string} received - ເງິນທີ່ຮັບມາ
     * @param {number|string} change - ເງິນທອນ (ຖ້າ 0 ຈະບໍ່ອ່ານ)
     * @param {boolean|string} isQr - ເປັນເງິນໂອນ/QR ຫຼື ບໍ່ ('split' ສຳລັບແຍກຈ່າຍ)
     * @param {string} currency - ສະກຸນເງິນ (KIP, THB, USD, CNY...)
     * @param {object|string} splitData - ຂໍ້ມູນແຍກຈ່າຍ { cash, qr } ຫຼື ຊື່ທະນາຄານ
     * @param {string} bankName - ຊື່ທະນາຄານ (ເຊັ່ນ: BCEL One, LDB, JDB...)
     */
    speakPaymentAndChange: function (total, received, change, isQr, currency, splitData, bankName) {
      var bank = (typeof bankName === 'string') ? bankName : ((typeof splitData === 'string') ? splitData : ((splitData && splitData.bank) || ''));
      var sData = (typeof splitData === 'object' && splitData !== null) ? splitData : null;
      var msg = this.buildPaymentSpeechMessage(received, change, isQr, currency, sData, bank);
      this.speak(msg, null, null, undefined, 'pos_sale');
    },

    /**
     * ເວົ້າແຈ້ງເຕືອນສິນຄ້າໝົດແລ້ວ
     * @param {string} productName - ຊື່ສິນຄ້າ
     */
    speakOutOfStock: function (productName) {
      if (!productName) return;
      this.speak(productName + ', ສິນຄ້າໝົດແລ້ວ', null, null, undefined, 'pos_stock');
    },

    /**
     * ເວົ້າແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດແລ້ວ
     * @param {string} productName - ຊື່ສິນຄ້າ
     * @param {number|string} remainingQty - ຈຳນວນທີ່ເຫຼືອ
     * @param {string} unitName - ຫົວໜ່ວຍ
     */
    speakLowStock: function (productName, remainingQty, unitName) {
      if (!productName) return;
      var u = unitName || 'ອັນ';
      var q = Number(remainingQty || 0).toLocaleString();
      this.speak(productName + ', ສິນຄ້າໃກ້ໝົດແລ້ວ, ເຫຼືອພຽງ ' + q + ' ' + u, null, null, undefined, 'pos_stock');
    },

    /**
     * ເວົ້າແຈ້ງຍອດເງິນຂາຍສຳເລັດ
     * @param {number|string} amount - ຈຳນວນເງິນ
     * @param {string} suffix - ຄຳລົງທ້າຍ (ເລີ່ມຕົ້ນ: "ຂອບໃຈທີ່ໃຊ້ບໍລິການ")
     */
    speakSale: function (amount, suffix) {
      var numStr = Number(amount || 0).toLocaleString();
      var msg = 'ຍອດເງິນ ' + numStr + ' ກີບ';
      if (suffix !== false) {
        msg += ', ' + (suffix || 'ຂອບໃຈທີ່ໃຊ້ບໍລິການ');
      }
      this.speak(msg, null, null, undefined, 'pos_sale');
    },

    /**
     * ເວົ້າຮ້ອງຄິວ
     * @param {number|string} queueNo - ເລກທີຄິວ
     */
    speakQueue: function (queueNo) {
      if (!queueNo) return;
      var msg = 'ຂໍເຊີນຄິວເລກທີ ' + queueNo;
      this.speak(msg, null, null, undefined, 'pos_queue');
    },

    /**
     * ເວົ້າຕ້ອນຮັບລູກຄ້າ
     * @param {string} storeName - ຊື່ຮ້ານ
     */
    speakWelcome: function (storeName) {
      var msg = 'ສະບາຍດີ, ຍິນດີຕ້ອນຮັບ';
      if (storeName) {
        msg += 'ສູ່ ' + storeName;
      }
      this.speak(msg, null, null, undefined, 'pos_welcome');
    }
  };

  LaoTTS.isEnabled = function (kind) { return ttsAllowed(kind); };
  window.LaoTTS = LaoTTS;

  // ອ່ານການຕັ້ງຄ່າສຽງຄືນ (ຜູ້ບໍລິຫານອາດປິດ/ເປີດ ລະຫວ່າງໜ້າຂາຍເປີດຢູ່) — ປິດແລ້ວ ຢຸດສຽງທີ່ກຳລັງເວົ້າທັນທີ
  try {
    var meCfg = document.currentScript && document.currentScript.src;
    if (meCfg && ttsCfg) {
      var cfgUrl = meCfg.replace(/assets\/js\/tts-speak\.js.*$/, 'api/tts_speak.php?config=1');
      var refreshCfg = function () {
        fetch(cfgUrl, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (c) {
          if (!c || typeof c !== 'object' || !('enabled' in c)) return;
          ttsCfg = c;
          if (!c.enabled) LaoTTS.stop();
        }).catch(function () {});
      };
      setInterval(refreshCfg, 10000);
      document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshCfg(); });
    }
  } catch (e) {}

  // ປຸກເຄື່ອງສ້າງສຽງຢູ່ເຊີເວີ (api/tts_daemon.py) ຕອນເປີດໜ້າ — ສຽງທຳອິດບໍ່ຕ້ອງລໍເປີດ Python (~5 ວິນາທີ).
  // ເອົາທີ່ຢູ່ຈາກ src ຂອງໄຟລ໌ນີ້ເອງ ຈຶ່ງຖືກທຸກຄວາມເລິກຂອງໜ້າ. ເທື່ອດຽວຕໍ່ແຖບ.
  try {
    var me = document.currentScript && document.currentScript.src;
    if (me && !window.__ttsWarmed) {
      window.__ttsWarmed = true;
      var warmUrl = me.replace(/assets\/js\/tts-speak\.js.*$/, 'api/tts_speak.php?warm=1');
      setTimeout(function () { fetch(warmUrl, { cache: 'no-store' }).catch(function () {}); }, 800);
    }
  } catch (e) {}
})(window);
