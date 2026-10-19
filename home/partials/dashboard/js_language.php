<?php
/*
 * ສ່ວນ: JS: ປ່ຽນພາສາທັນທີ
 * ເລີ່ມ <script> ຫຼັກ: ເມື່ອກົດທຸງ ຈະແປຂໍ້ຄວາມໃນ shell ແລະ ໃນ iframe ທັນທີໂດຍບໍ່ໂຫຼດໜ້າໃໝ່
 * (ໃຊ້ພົດຈະນານຸກົມຂອງທຸກພາສາ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <script>
    // I18N_LAYOUT: translated strings used by JS-rendered navbar/notification markup
    // (kept in sync with lang/<locale>/layout.php so live-polled notifications stay translated too)
    var I18N_LAYOUT = <?php echo tjson([
        'layout.package_label' => 'ແພັກເກັດ',
        'layout.days_unit' => 'ວັນ',
        'layout.package_unlimited' => 'ບໍ່ມີກຳນົດ',
        'layout.access_expiry_warning_title' => 'ອາຍຸການນຳໃຊ້ໃກ້ໝົດ',
        'layout.access_expiry_warning_text' => 'ບັນຊີຂອງທ່ານຈະໝົດອາຍຸໃນອີກ %d ມື້. ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານເພື່ອຕໍ່ອາຍຸການນຳໃຊ້.',
        'layout.access_expiry_warning_text_today' => 'ບັນຊີຂອງທ່ານຈະໝົດອາຍຸໃນມື້ນີ້! ກະລຸນາຕິດຕໍ່ຜູ້ບໍລິຫານເພື່ອຕໍ່ອາຍຸການນຳໃຊ້.',
        'layout.branch_main_suffix' => '(ສາງຫຼັກ)',
        'layout.items_unit' => 'ລາຍການ',
        'layout.out_of_stock' => 'ໝົດແລ້ວ (0)',
        'layout.remaining' => 'ເຫຼືອ',
        'layout.default_unit' => 'ອັນ',
        'layout.no_low_stock' => 'ບໍ່ມີສິນຄ້າໃກ້ໝົດ ຫຼື ໝົດແລ້ວ',
        'layout.no_new_notif' => 'ບໍ່ມີການແຈ້ງເຕືອນໃໝ່',
        'layout.no_sub_notif' => 'ບໍ່ມີການແຈ້ງເຕືອນໃໝ່ໃນສາຂາ',
        'layout.transfer_list' => 'ລາຍການໂອນສິນຄ້າ',
        'layout.bill_unit' => 'ໃບບິນ',
        'layout.transfer_to' => 'ໂອນໄປຫາ',
        'layout.transfer_from' => 'ໂອນມາຈາກ',
        'layout.branch_sub_default' => 'ສາຂາຍ່ອຍ',
        'layout.branch_main_default' => 'ສາຂາໃຫຍ່',
        'layout.item_transferred' => 'ສິນຄ້າໂອນ',
        'layout.low_stock_own' => 'ສິນຄ້າໃກ້ໝົດ / ໝົດແລ້ວ',
        'layout.new_products' => 'ສິນຄ້າໃໝ່ເພີ່ມເຂົ້າສາຂາ',
        'layout.sub_branch_imports' => 'ສາຂາຍ່ອຍເພີ່ມສິນຄ້າເຂົ້າ',
        'layout.click_to_restock' => 'ກົດເພື່ອໄປໜ້ານຳເຂົ້າສິນຄ້າ',
        'layout.default_product' => 'ສິນຄ້າ',
        'layout.expiry_near_header' => 'ສິນຄ້າໃກ້ໝົດອາຍຸ / ໝົດອາຍຸ',
        'layout.expiry_days_left' => 'ເຫຼືອ %d ມື້',
        'layout.expiry_today' => 'ໝົດອາຍຸມື້ນີ້',
        'layout.expiry_expired' => 'ໝົດອາຍຸແລ້ວ',
        'layout.click_to_expiry' => 'ກົດເພື່ອໄປໜ້າຈັດການສິນຄ້າໝົດອາຍຸ',
        'layout.logout' => 'ອອກຈາກລະບົບ',
        'layout.logout_confirm_title' => 'ຢືນຢັນການອອກຈາກລະບົບ',
        'layout.logout_confirm_text' => 'ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ຫຼືບໍ່?',
        'layout.cancel' => 'ຍົກເລີກ',
    ]); ?>;

    // Own account's remaining access days (owner-only field, Users Management) — null when
    // unlimited, not applicable (owner account), or more than 10 days away. Folded into
    // whichever notification bell (main/sub) this session sees — see pollLiveNotifications().
    var MY_ACCESS_DAYS_LEFT = <?php echo ($myAccessDaysLeftForWarning === null) ? 'null' : intval($myAccessDaysLeftForWarning); ?>;

    // I18N_ALL_LAYOUT: sidebar/navbar strings for EVERY supported language (not just the
    // current one), embedded once so a flag click can repaint the chrome instantly, client-side,
    // with zero network round trip / page reload.
    var I18N_ALL_LAYOUT = <?php
        $allLayoutDict = [];
        foreach (POS_SUPPORTED_LANGS as $lc) {
            $allLayoutDict[$lc] = loadLangDomain($lc, 'layout');
        }
        echo json_encode($allLayoutDict, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>;

    // I18N_ALL_MERGED: every lang/*/*.php domain merged per language (same dict t() uses
    // server-side), embedded once so the CURRENT iframe page's own already-rendered content can
    // also be translated instantly client-side — no reload. Pages don't carry data-i18n
    // attributes on their body content (only the shell does), so translateIframeContent() below
    // works by reverse-matching each visible text node's current string back to its translation
    // key in the outgoing language's dict, then swapping in the incoming language's value for
    // that same key. Text that doesn't match any known dict value (product names, numbers,
    // prices, anything sprintf-formatted with a %d already substituted in) is simply left alone.
    var I18N_ALL_MERGED = <?php
        $allMergedDict = [];
        foreach (POS_SUPPORTED_LANGS as $lc) {
            $allMergedDict[$lc] = loadLangDictionary($lc);
        }
        echo json_encode($allMergedDict, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>;
    var POS_LANG_META_JS = <?php echo json_encode(POS_LANG_META, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    // Tracks the language the iframe's current page content was actually rendered in
    // server-side, so a flag click knows which dict to reverse-match FROM.
    var CURRENT_ACTIVE_LANG = '<?php echo getCurrentLang(); ?>';

    // Language flag switcher: repaints the sidebar/navbar chrome AND the current iframe page's
    // content instantly, client-side — no reload, no waiting on the network. The choice is still
    // persisted server-side in the background so the next real navigation renders pre-translated.
    document.addEventListener('DOMContentLoaded', function() {
      function applyLangInstantly(lang) {
        var dict = I18N_ALL_LAYOUT[lang];
        if (!dict) return;

        // Keep I18N_LAYOUT (used by JS-rendered notification dropdown markup) in sync too.
        I18N_LAYOUT = dict;

        document.querySelectorAll('[data-i18n]').forEach(function(el) {
          var key = el.getAttribute('data-i18n');
          if (dict[key]) el.textContent = dict[key];
        });
        document.querySelectorAll('[data-i18n-title]').forEach(function(el) {
          var key = el.getAttribute('data-i18n-title');
          if (dict[key]) el.setAttribute('title', dict[key]);
        });
        document.documentElement.setAttribute('lang', lang);

        var meta = POS_LANG_META_JS[lang];
        if (meta) {
          var triggerImg = document.getElementById('currentLangFlagImg');
          if (triggerImg) {
            triggerImg.src = '../assets/img/flag_img/' + meta.img;
            triggerImg.alt = lang;
          }
        }

        document.querySelectorAll('.lang-flag-btn').forEach(function(el) {
          var isActive = el.getAttribute('data-lang') === lang;
          el.classList.toggle('active', isActive);
          el.style.background = isActive ? '#eaf2ff' : 'transparent';
        });
      }

      var reverseMapCache = {};
      function getReverseMap(fromLang) {
        if (reverseMapCache[fromLang]) return reverseMapCache[fromLang];
        var dict = I18N_ALL_MERGED[fromLang] || {};
        var rev = {};
        for (var key in dict) {
          if (!Object.prototype.hasOwnProperty.call(dict, key)) continue;
          var val = (dict[key] || '').trim();
          if (val && !(val in rev)) rev[val] = key; // first key wins on a shared-text collision
        }
        reverseMapCache[fromLang] = rev;
        return rev;
      }

      // Dictionary values with %s / %d placeholders, compiled to anchored regexes (longest
      // literal text first, so "ກາຍມາ %s ມື້" wins over the bare "%s ມື້"). Values whose fixed
      // text is under 2 characters are skipped — they'd match almost anything.
      var sprintfPatternCache = {};
      function getSprintfPatterns(fromLang) {
        if (sprintfPatternCache[fromLang]) return sprintfPatternCache[fromLang];
        var dict = I18N_ALL_MERGED[fromLang] || {};
        var list = [], seen = {};
        for (var key in dict) {
          if (!Object.prototype.hasOwnProperty.call(dict, key)) continue;
          var val = (dict[key] || '').trim();
          if (!/%[sd]/.test(val) || seen[val]) continue;
          var literal = val.replace(/%[sd]/g, '').replace(/\s+/g, '');
          // One CJK character is a whole word ("%s 天" = "%s days"); anything else needs 2+.
          if (literal.length < 2 && !/[㐀-鿿]/.test(literal)) continue;
          seen[val] = 1;
          var src = val.split(/%[sd]/).map(function (part) {
            return part.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          }).join('(.+?)');
          list.push({ key: key, re: new RegExp('^' + src + '$'), weight: literal.length });
        }
        list.sort(function (a, b) { return b.weight - a.weight; });
        sprintfPatternCache[fromLang] = list;
        return list;
      }

      function translateIframeContent(fromLang, toLang) {
        var frameEl = document.querySelector('iframe[name="frame"]');
        if (!frameEl) return;
        var frameDoc;
        try {
          frameDoc = frameEl.contentDocument || frameEl.contentWindow.document;
        } catch (e) {
          return; // cross-origin guard, shouldn't happen — the iframe is always same-origin
        }
        if (!frameDoc || !frameDoc.body) return;

        var rev = getReverseMap(fromLang);
        var toDict = I18N_ALL_MERGED[toLang] || {};

        var walker = frameDoc.createTreeWalker(frameDoc.body, NodeFilter.SHOW_TEXT, {
          acceptNode: function(node) {
            var tag = node.parentNode && node.parentNode.tagName;
            if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT') return NodeFilter.FILTER_REJECT;
            return NodeFilter.FILTER_ACCEPT;
          }
        });
        // "ກີບ (₭)" / "BCEL One (ສາຂາຫຼັກ)": a known word followed by a bracketed part that
        // isn't in any dictionary (currency symbol, count, code) — translate the word, keep the rest.
        var BRACKET_TAIL_RE = /^(.+?)(\s*\([^()]*\))$/;
        var patterns = getSprintfPatterns(fromLang);
        function translateText(text) {
          var key = rev[text];
          if (key && toDict[key]) return toDict[key];
          var m = text.match(BRACKET_TAIL_RE);
          if (m) {
            var headKey = rev[m[1].trim()];
            if (headKey && toDict[headKey]) return toDict[headKey] + m[2];
          }
          // "3 ມື້" / "ສະແດງ 1–10 ຈາກ 25 ລາຍການ": a sprintf string with its values already filled
          // in — match it against the %s/%d patterns and refill the target language's pattern.
          for (var i = 0; i < patterns.length; i++) {
            var pm = text.match(patterns[i].re);
            if (!pm) continue;
            var target = toDict[patterns[i].key];
            if (!target) continue;
            var args = pm.slice(1);
            return target.replace(/%[sd]/g, function () { return args.length ? args.shift() : ''; });
          }
          return null;
        }

        var node;
        while ((node = walker.nextNode())) {
          var trimmed = node.nodeValue.trim();
          if (!trimmed) continue;
          var translated = translateText(trimmed);
          if (translated !== null) {
            var lead = node.nodeValue.match(/^\s*/)[0];
            var trail = node.nodeValue.match(/\s*$/)[0];
            node.nodeValue = lead + translated + trail;
          }
        }

        ['placeholder', 'title'].forEach(function(attr) {
          frameDoc.querySelectorAll('[' + attr + ']').forEach(function(el) {
            var val = (el.getAttribute(attr) || '').trim();
            var key = rev[val];
            if (key && toDict[key]) el.setAttribute(attr, toDict[key]);
          });
        });

        frameDoc.querySelectorAll('input[type="submit"], input[type="button"], input[type="reset"]').forEach(function(el) {
          var val = (el.value || '').trim();
          var key = rev[val];
          if (key && toDict[key]) el.value = toDict[key];
        });
      }

      // Re-point the iframe page's own JS i18n dictionaries (I18N_*, PROMO_I18N, …) at the
      // incoming language, so a Swal/alert fired AFTER a flag switch — with no reload — speaks
      // the new language too, not just the already-painted DOM. Every such object is a flat
      // map keyed by the same "domain.key" strings t()/tjson() use, so we can find them by
      // shape on the (same-origin) iframe window and swap each value from I18N_ALL_MERGED.
      function retranslateIframeI18nObjects(toLang) {
        var frameEl = document.querySelector('iframe[name="frame"]');
        if (!frameEl) return;
        var win;
        try { win = frameEl.contentWindow; } catch (e) { return; }
        if (!win) return;
        var toDict = I18N_ALL_MERGED[toLang];
        if (!toDict) return;
        var props;
        try { props = Object.keys(win); } catch (e) { return; }
        // "domain.key" shape — the exact form t()/tjson() keys take.
        var KEY_RE = /^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/;
        var hasOwn = Object.prototype.hasOwnProperty;
        props.forEach(function(prop) {
          var obj;
          try { obj = win[prop]; } catch (e) { return; }
          // Plain dictionary only: object, not null, not an Array/DOM/function. Don't compare
          // prototypes across realms (the iframe has its own Object.prototype) — the key
          // heuristic below is strict enough on its own.
          if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return;
          if (typeof obj.nodeType === 'number') return; // DOM node
          var oKeys;
          try { oKeys = Object.keys(obj); } catch (e) { return; }
          if (!oKeys.length || oKeys.length > 2000) return;
          var isI18n = oKeys.some(function(k) {
            return KEY_RE.test(k) && hasOwn.call(toDict, k);
          });
          if (!isI18n) return;
          oKeys.forEach(function(k) {
            if (hasOwn.call(toDict, k)) {
              try { obj[k] = toDict[k]; } catch (e) {}
            }
          });
        });
      }

      document.querySelectorAll('.lang-flag-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var lang = btn.getAttribute('data-lang');
          if (!lang || lang === CURRENT_ACTIVE_LANG) return;
          var fromLang = CURRENT_ACTIVE_LANG;

          // 1. Instant, client-side repaint of the persistent chrome.
          applyLangInstantly(lang);

          // 2. Instant, client-side translation of the current iframe page's own content —
          //    no reload, matching the shell's zero-network-wait feel.
          translateIframeContent(fromLang, lang);

          // 2b. Re-point that page's JS i18n dictionaries so its popups switch too.
          retranslateIframeI18nObjects(lang);

          // 2c. Notify the iframe page so it can redo anything the DOM walker can't reach —
          //     e.g. redraw <canvas> charts whose labels are baked into the bitmap, or re-render
          //     JS-animated counters whose suffix text got baked into a compound text node
          //     ("16 ບິນ") that never exact-matches a dictionary value on its own.
          try {
            var _frameForEvt = document.querySelector('iframe[name="frame"]');
            if (_frameForEvt && _frameForEvt.contentWindow) {
              _frameForEvt.contentWindow.dispatchEvent(new CustomEvent('pos:langchange', { detail: { lang: lang, fromLang: fromLang } }));
            }
          } catch (e) {}

          CURRENT_ACTIVE_LANG = lang;

          // 3. Persist the choice server-side in the background, purely so the NEXT real
          //    navigation is already rendered in this language from the start.
          fetch('../lang/set_lang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'lang=' + encodeURIComponent(lang)
          }).catch(function() {});
        });
      });
    });

    // Web Audio API Notification Chime Generator with Mobile Autoplay Policy Unlocker
    var sharedAudioCtx = null;
    var isAudioUnlocked = false;

