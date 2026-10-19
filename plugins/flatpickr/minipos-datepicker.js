/**
 * MiniPos date picker — every <input type="date"> in the app becomes a flatpickr calendar with
 * month/weekday names in the app's language (Lao by default), so all date boxes look and work
 * the same. The native date input follows the browser's language and can't be switched.
 *
 * Loaded globally in <head> by layouts/header.php. What it guarantees so existing page code keeps working:
 *  - The original input keeps its name/id and still holds the value as Y-m-d (what the backends
 *    expect); the box the user sees shows d/m/Y and accepts typing (auto-inserts the slashes).
 *  - Setting the value from code ($('#x').val('2026-09-24') or el.value = ...) updates the
 *    calendar too, and picking a date fires 'change' + 'input' on the original input.
 *  - min/max, required, disabled, readonly, placeholder are carried over.
 *  - Inputs added later (AJAX, cloned rows, modals) are converted automatically.
 *  - Rebuilds with the new language on the shell's 'pos:langchange' event.
 * Opt out per input with data-native-date="1".
 */
(function (window, document) {
  if (window.miniposDatePicker) return;   // already loaded (header + footer fallback)
  if (typeof window.flatpickr !== 'function') return;
  // Lets minipos-datepicker.css hide the native box's text only while this script is active
  document.documentElement.classList.add('mp-dp-on');

  var LOCALES = {
    lo: { firstDayOfWeek: 0,
      weekdays: { shorthand: ['ອາ', 'ຈ', 'ອ', 'ພ', 'ພຫ', 'ສຸ', 'ສ'], longhand: ['ອາທິດ', 'ຈັນ', 'ອັງຄານ', 'ພຸດ', 'ພະຫັດ', 'ສຸກ', 'ເສົາ'] },
      months: { shorthand: ['ມ.ກ', 'ກ.ພ', 'ມ.ນ', 'ມ.ສ', 'ພ.ພ', 'ມິ.ຖ', 'ກ.ລ', 'ສ.ຫ', 'ກ.ຍ', 'ຕ.ລ', 'ພ.ຈ', 'ທ.ວ'],
                longhand: ['ມັງກອນ', 'ກຸມພາ', 'ມີນາ', 'ເມສາ', 'ພຶດສະພາ', 'ມິຖຸນາ', 'ກໍລະກົດ', 'ສິງຫາ', 'ກັນຍາ', 'ຕຸລາ', 'ພະຈິກ', 'ທັນວາ'] } },
    th: { firstDayOfWeek: 0,
      weekdays: { shorthand: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'], longhand: ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'] },
      months: { shorthand: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                longhand: ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'] } },
    zh: { firstDayOfWeek: 1,
      weekdays: { shorthand: ['日', '一', '二', '三', '四', '五', '六'], longhand: ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'] },
      months: { shorthand: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                longhand: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'] } },
    en: 'default'
  };

  // Placeholder shown in an empty box, in the app's language
  var PLACEHOLDERS = { lo: 'ວັນ/ເດືອນ/ປີ', th: 'วัน/เดือน/ปี', zh: '日/月/年', en: 'dd/mm/yyyy' };

  // Phone / tablet: tapping a typeable box pops the keyboard and (iOS, font < 16px) zooms the
  // page in. On touch screens the box is tap-to-open-calendar only — no keyboard, no zoom.
  var TOUCH = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);

  // Touch: lock page zoom ONLY while a date box is being tapped / its calendar is open, then
  // give it back — the rest of the app keeps normal zoom. The viewport meta that matters is the
  // shell's (window.top), since pages live in an iframe; this page's own meta is locked too.
  var zoomLocks = 0;
  function viewportMetas() {
    var docs = [document];
    try { if (window.top !== window && window.top.document) docs.push(window.top.document); } catch (e) {}
    return docs.map(function (d) { return d.querySelector('meta[name="viewport"]'); }).filter(Boolean);
  }
  function lockZoom(fp) {
    if (!TOUCH || fp._mpZoomLocked) return;
    fp._mpZoomLocked = true;
    if (zoomLocks++ > 0) return;
    viewportMetas().forEach(function (m) {
      var orig = m.getAttribute('content') || 'width=device-width, initial-scale=1';
      m._mpOrigContent = orig;
      m.setAttribute('content', orig.replace(/,?\s*(maximum-scale|user-scalable)\s*=[^,]*/gi, '') + ', maximum-scale=1, user-scalable=no');
    });
  }
  function unlockZoom(fp) {
    if (!fp._mpZoomLocked) return;
    fp._mpZoomLocked = false;
    if (--zoomLocks > 0) return;
    zoomLocks = 0;
    viewportMetas().forEach(function (m) {
      if (m._mpOrigContent !== undefined) { m.setAttribute('content', m._mpOrigContent); delete m._mpOrigContent; }
    });
  }
  if (TOUCH) {
    // iOS Safari ignores user-scalable=no for pinch — block pinch while a calendar is open
    ['gesturestart', 'gesturechange'].forEach(function (type) {
      document.addEventListener(type, function (e) { if (zoomLocks > 0) e.preventDefault(); }, { passive: false });
    });
    document.addEventListener('touchmove', function (e) {
      if (zoomLocks > 0 && e.touches && e.touches.length > 1) e.preventDefault();
    }, { passive: false });
  }

  var NATIVE_VALUE =Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
  var inputs = [];
  var currentLang = document.documentElement.lang || 'lo';

  function nativeGet(el) { return NATIVE_VALUE.get.call(el); }
  function nativeSet(el, v) { NATIVE_VALUE.set.call(el, v); }

  function toYmd(fp) {
    return fp && fp.selectedDates.length ? fp.formatDate(fp.selectedDates[0], 'Y-m-d') : '';
  }

  // Make el.value = '...' (what jQuery .val() does) also move the calendar
  function hookValue(el) {
    if (el._mpValueHooked) return;
    el._mpValueHooked = true;
    Object.defineProperty(el, 'value', {
      configurable: true,
      get: function () { return nativeGet(this); },
      set: function (v) {
        v = (v === null || v === undefined) ? '' : String(v);
        nativeSet(this, v);
        var fp = this._flatpickr;
        if (!fp || this._mpSyncing || v === toYmd(fp)) return;
        this._mpSyncing = true;
        try { fp.setDate(v || null, false, 'Y-m-d'); } finally { this._mpSyncing = false; }
      }
    });
  }

  // Typing in the visible box: keep digits only and insert the slashes (24092026 -> 24/09/2026)
  function onAltTyping(e) {
    var box = e.target;
    var digits = box.value.replace(/\D/g, '').slice(0, 8);
    var out = digits;
    if (digits.length > 4) out = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
    else if (digits.length > 2) out = digits.slice(0, 2) + '/' + digits.slice(2);
    if (out !== box.value) box.value = out;
  }

  function build(el) {
    if (typeof window.flatpickr !== 'function' || !el || !el.isConnected) return;
    var val = nativeGet(el);
    var readOnly = el.readOnly;
    if (el._flatpickr) { unlockZoom(el._flatpickr); el._flatpickr.destroy(); }
    if (el.type === 'date') el.type = 'text';
    hookValue(el);
    nativeSet(el, val);

    var fp = window.flatpickr(el, {
      locale: LOCALES[currentLang] || LOCALES.lo,
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'd/m/Y',
      altInputClass: (el.className || 'form-control') + ' mp-date',
      allowInput: !readOnly && !TOUCH,
      clickOpens: !readOnly && !el.disabled,
      disableMobile: true,
      minDate: el.getAttribute('min') || null,
      maxDate: el.getAttribute('max') || null,
      onOpen: [function (s, d, inst) { lockZoom(inst); }],
      // Small delay so the tap that closes the calendar can't turn into a double-tap zoom
      onClose: [function (s, d, inst) { setTimeout(function () { unlockZoom(inst); }, 350); }]
    });
    if (!fp || !fp.altInput) return;

    // Touch: the header's year box would pop the keyboard (and zoom) — change year with its
    // up/down arrows or the month arrows instead.
    if (TOUCH && fp.currentYearElement) {
      fp.currentYearElement.readOnly = true;
      fp.currentYearElement.inputMode = 'none';
    }

    var alt = fp.altInput;
    alt.style.cssText = el.style.cssText;
    alt.placeholder = el.getAttribute('placeholder') || PLACEHOLDERS[currentLang] || PLACEHOLDERS.lo;
    if (el.title) alt.title = el.title;
    alt.required = el.required;       // the original is now hidden, so the visible box carries "required"
    alt.disabled = el.disabled;
    alt.readOnly = readOnly || TOUCH;   // readonly = no keyboard; clickOpens still opens the calendar
    alt.autocomplete = 'off';
    if (TOUCH) {
      alt.inputMode = 'none';
      alt.classList.add('mp-date-touch');
      // Lock before the tap lands, so even a double-tap on the box itself can't zoom
      alt.addEventListener('touchstart', function () {
        lockZoom(fp);
        setTimeout(function () { if (!fp.isOpen) unlockZoom(fp); }, 800);
      }, { passive: true });
    } else {
      alt.inputMode = 'numeric';
    }
    alt.addEventListener('input', onAltTyping);
    // Commit what was typed when leaving the box: a full d/m/Y sets the date, an empty box
    // clears it, anything incomplete snaps back to the current date.
    function commitTyped() {
      var txt = alt.value.trim();
      if (txt === '') { if (fp.selectedDates.length) fp.clear(); return; }
      var cur = fp.selectedDates.length ? fp.formatDate(fp.selectedDates[0], 'd/m/Y') : '';
      if (txt === cur) return;
      var d = /^\d{2}\/\d{2}\/\d{4}$/.test(txt) ? fp.parseDate(txt, 'd/m/Y') : null;
      if (d && fp.formatDate(d, 'd/m/Y') === txt) fp.setDate(d, true);
      else alt.value = cur;
    }
    // Capture phase so this runs before flatpickr's own blur parser, which would roll an
    // impossible date like 31/02 over into March.
    alt.addEventListener('blur', commitTyped, true);
    alt.addEventListener('change', commitTyped, true);
  }

  function attach(el) {
    if (!el || el.getAttribute('data-native-date') === '1') return;
    if (inputs.indexOf(el) === -1) inputs.push(el);
    build(el);
  }

  function scan(root) {
    if (!root || !root.querySelectorAll) return;
    if (root.matches && root.matches('input[type="date"]')) attach(root);
    root.querySelectorAll('input[type="date"]').forEach(attach);
  }

  window.miniposDatePicker = attach;

  // Set a picker's date from code (value Y-m-d, or '' to clear)
  window.miniposSetDate = function (el, value) {
    if (el) el.value = value || '';
  };

  // form.reset() restores the hidden input but not the visible box — resync after it runs
  document.addEventListener('reset', function (e) {
    setTimeout(function () {
      inputs.forEach(function (el) {
        if (el.form === e.target && el._flatpickr) {
          var v = nativeGet(el);
          el._mpSyncing = true;
          try { el._flatpickr.setDate(v || null, false, 'Y-m-d'); } finally { el._mpSyncing = false; }
        }
      });
    }, 0);
  }, true);

  // Bootstrap modals pull focus back into the modal; the calendar lives on <body>, so let
  // focus stay in it (month dropdown / year box) instead of being yanked away.
  function patchModalFocus() {
    var $ = window.jQuery;
    if (!$ || !$.fn.modal || !$.fn.modal.Constructor || $.fn.modal.Constructor.prototype._mpPatched) return;
    var proto = $.fn.modal.Constructor.prototype;
    var orig = proto._enforceFocus;
    if (typeof orig !== 'function') return;
    proto._mpPatched = true;
    proto._enforceFocus = function () {
      var self = this;
      $(document).off('focusin.bs.modal').on('focusin.bs.modal', function (event) {
        var t = event.target;
        if (t && t.closest && (t.closest('.flatpickr-calendar') || t.closest('.select2-container'))) return;
        if (document !== t && self._element !== t && $(self._element).has(t).length === 0) {
          self._element.focus();
        }
      });
    };
  }

  window.addEventListener('pos:langchange', function (e) {
    currentLang = (e.detail && e.detail.lang) || currentLang;
    inputs = inputs.filter(function (el) { return el.isConnected; });
    inputs.forEach(build);
  });

  var observer = null;
  function startObserver() {
    if (observer || !('MutationObserver' in window) || !document.documentElement) return;
    observer = new MutationObserver(function (muts) {
      for (var i = 0; i < muts.length; i++) {
        var added = muts[i].addedNodes;
        for (var j = 0; j < added.length; j++) {
          if (added[j].nodeType === 1) scan(added[j]);
        }
      }
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
  }

  // A calendar popup built while the page is still parsing can end up detached from <body>
  // by the time loading finishes (it then never opens), so re-attach any such popup once the
  // page is ready.
  function reattachCalendars() {
    inputs.forEach(function (el) {
      var fp = el._flatpickr;
      if (fp && fp.calendarContainer && !fp.calendarContainer.isConnected && document.body) {
        document.body.appendChild(fp.calendarContainer);
      }
    });
  }

  // Loaded in <head> (layouts/header.php): the observer converts each date input the moment
  // the parser adds it, so the box shows its date on first paint instead of sitting blank
  // until the end-of-page scripts arrive (slow on the live server). One more sweep and a
  // popup re-attach once the page is ready.
  startObserver();
  if (document.body) scan(document.body);
  document.addEventListener('DOMContentLoaded', function () {
    patchModalFocus();
    scan(document.body);
    reattachCalendars();
  });
  window.addEventListener('load', reattachCalendars);
})(window, document);
