<?php
/**
 * ແຊັດຈາກເວັບໄຊ — ພະນັກງານອ່ານ/ຟັງສຽງ/ຕອບ ຂໍ້ຄວາມຈາກໜ້າ "ຕິດຕໍ່" ຂອງເວັບໄຊ (frontend/pages/contact.php).
 * ຊ້າຍ = ລາຍການຫ້ອງ (ໃໝ່ສຸດກ່ອນ, ຕົວເລກ = ຍັງບໍ່ອ່ານ), ຂວາ = ຂໍ້ຄວາມ + ຊ່ອງຕອບ + ສະວິດບັອດ + ປິດຫ້ອງ.
 * ລູກຄ້າຕ່າງປະເທດ: ປ້າຍພາສາ, ຄຳແປລາວໃຕ້ຂໍ້ຄວາມລູກຄ້າ, ຕິກ "ແປຄຳຕອບ" → ພິມລາວ ລູກຄ້າໄດ້ຮັບເປັນພາສາຂອງລາວ.
 * ຂໍ້ມູນ ແລະ AJAX: api/web_chats_backend.php (JS ເອີ້ນໜ້ານີ້ເອງດ້ວຍ ?ajax=… / POST action=…). ດຶງໃໝ່ທຸກ 5 ວິ.
 * CSS: themes/web_chats.css
 * ໜ້າຕາ: ຊ້າຍ = ຊ່ອງຄົ້ນຫາ + ປຸ່ມກອງ (ທັງໝົດ / ຍັງບໍ່ອ່ານ / ສະມາຊິກ / ລູກຄ້າທົ່ວໄປ / ປິດແລ້ວ) + ລາຍການ (ຮູບ, ຊື່, ເວລາ, ຂໍ້ຄວາມລ່າສຸດ, ຈຳນວນບໍ່ອ່ານ);
 *        ຂວາ = ຍັງບໍ່ເລືອກ → ຮູບ + "ກະລຸນາເລືອກແຊັດທາງຊ້າຍມື". ກອງ/ຄົ້ນຫາ ເຮັດໃນ browser (ບໍ່ຍິງ server ເພີ່ມ).
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
require_once __DIR__ . '/../../api/web_chats_backend.php';
require_once __DIR__ . '/../../layouts/header.php';
?>
<?php echo themeCssLink('web_chats.css'); ?>
<div class="content-wrapper bg-light">
  <section class="content pt-3">
    <div class="container-fluid">
      <div class="wc">
        <aside class="wc-side">
          <div class="wc-side-head">
            <label class="wc-search">
              <i class="fas fa-search" aria-hidden="true"></i>
              <input type="search" id="wcSearch" placeholder="ຄົ້ນຫາ" autocomplete="off">
            </label>
            <div class="wc-filter">
              <button type="button" class="wc-filter-btn" id="wcFilterBtn" title="ກອງແຊັດ" aria-haspopup="true">
                <i class="fas fa-filter" aria-hidden="true"></i><small id="wcUnread" class="wc-filter-dot" hidden></small>
              </button>
              <div class="wc-filter-menu" id="wcFilterMenu" hidden>
                <button type="button" data-f="all" class="is-on">ທັງໝົດ</button>
                <button type="button" data-f="unread">ຍັງບໍ່ອ່ານ</button>
                <button type="button" data-f="member">ສະມາຊິກ</button>
                <button type="button" data-f="guest">ລູກຄ້າທົ່ວໄປ</button>
                <button type="button" data-f="closed">ປິດແລ້ວ</button>
              </div>
            </div>
          </div>
          <div class="wc-list" id="wcList"><p class="wc-empty">ກຳລັງໂຫຼດ…</p></div>
        </aside>
        <section class="wc-room" id="wcRoom">
          <div class="wc-pick">
            <svg viewBox="0 0 200 200" width="190" height="190" aria-hidden="true" fill="none" stroke="#cbd5e1" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="100" cy="100" r="88"/>
              <path d="M52 70a14 14 0 0 1 14-14h58a14 14 0 0 1 14 14v34a14 14 0 0 1-14 14H84l-20 16v-16a14 14 0 0 1-12-14z" fill="#f1f5f9"/>
              <path d="M146 92h6a12 12 0 0 1 12 12v26a12 12 0 0 1-12 12h-2v13l-16-13h-30a12 12 0 0 1-12-12" fill="#fff"/>
              <circle cx="78" cy="87" r="3" fill="#cbd5e1"/><circle cx="95" cy="87" r="3" fill="#cbd5e1"/><circle cx="112" cy="87" r="3" fill="#cbd5e1"/>
            </svg>
            <p>ກະລຸນາເລືອກແຊັດທາງຊ້າຍມື<br>ເພື່ອເລີ່ມສົນທະນາ</p>
          </div>
          <div class="wc-head" hidden>
            <div>
              <b id="wcName"></b>
              <a id="wcTel" href="#"></a>
              <span id="wcLang" class="badge badge-info" hidden></span>
              <small id="wcSince"></small>
            </div>
            <div class="wc-tools">
              <label class="wc-switch" title="ບັອດຕອບອັດຕະໂນມັດ"><input type="checkbox" id="wcAi"> <span>ບັອດຕອບ</span></label>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="wcStatus"></button>
            </div>
          </div>
          <div class="wc-log" id="wcLog" hidden></div>
          <?php /* ແຖບລຸ່ມ: ຄຳຕອບດ່ວນ | ແນບໄຟລ໌ | ຮູບ/ວິດີໂອ | ຊ່ອງພິມ (ມົນ) + ອີໂມຈິ | ໄມ (ມີຂໍ້ຄວາມ → ກາຍເປັນປຸ່ມສົ່ງ) */ ?>
          <form class="wc-reply" id="wcReply" hidden>
            <label class="wc-tr-toggle" id="wcTrWrap" hidden><input type="checkbox" id="wcTr" checked> ແປຄຳຕອບເປັນ <b id="wcTrLang"></b> ກ່ອນສົ່ງ</label>
            <div class="wc-bar">
              <div class="wc-pop-wrap">
                <button type="button" class="wc-round rounded-circle" id="wcQuickBtn" title="ຄຳຕອບດ່ວນ"><i class="far fa-comment-dots"></i></button>
                <div class="wc-pop wc-quick" id="wcQuick" hidden>
                  <?php foreach ([
                      'ສະບາຍດີ ຍິນດີຕ້ອນຮັບສູ່ WlaoDev 🙏 ມີຫຍັງໃຫ້ຊ່ວຍບໍ່?',
                      'ກະລຸນາລໍຖ້າບຶດໜຶ່ງ ທີມງານກຳລັງກວດໃຫ້ 🙏',
                      'ກະລຸນາສົ່ງເບີໂທ ຫຼື WhatsApp ໃຫ້ທີມງານຕິດຕໍ່ກັບ',
                      'ໄດ້ຮັບສະລິບແລ້ວ ກຳລັງກວດສອບ ຈະແຈ້ງໃຫ້ຊາບໄວໆນີ້',
                      'ສິນຄ້າຈັດສົ່ງແລ້ວ ເລກໃບຝາກເຄື່ອງ: ',
                      'ຂອບໃຈຫຼາຍໆ ທີ່ໃຊ້ບໍລິການ WlaoDev 🙏',
                  ] as $qr): ?>
                    <button type="button" data-text="<?php echo htmlspecialchars($qr); ?>"><?php echo htmlspecialchars($qr); ?></button>
                  <?php endforeach; ?>
                </div>
              </div>
              <button type="button" class="wc-round rounded-circle" id="wcAttach" title="ສົ່ງໄຟລ໌ເອກະສານ (PDF, Word, Excel…) ໃຫ້ລູກຄ້າ"><i class="far fa-file-alt"></i></button>
              <button type="button" class="wc-round rounded-circle" id="wcImage" title="ສົ່ງຮູບ / ວິດີໂອ ໃຫ້ລູກຄ້າ"><i class="far fa-image"></i></button>
              <input type="file" id="wcFile" hidden multiple accept="<?php echo htmlspecialchars(chatFileAcceptDocs()); ?>" data-accept-docs="<?php echo htmlspecialchars(chatFileAcceptDocs()); ?>" data-accept-media="<?php echo htmlspecialchars(chatFileAcceptMedia()); ?>">
              <div class="wc-input">
                <textarea id="wcText" rows="1" maxlength="2000" placeholder="ຂໍ້ຄວາມ..." title="Enter = ສົ່ງ, Shift+Enter = ແຖວໃໝ່"></textarea>
                <div class="wc-pop-wrap">
                  <button type="button" class="wc-emoji-btn rounded-circle" id="wcEmojiBtn" title="ອີໂມຈິ"><i class="far fa-smile"></i></button>
                  <div class="wc-pop wc-emoji" id="wcEmoji" hidden>
                    <?php foreach (['🙏','😊','😄','👍','👌','❤️','🎉','✅','📦','🚚','💳','🧾','🖨️','🖥️','📱','📞','⏰','🙂','😅','🤝','💯','🔥','⭐','🛒'] as $em): ?><button type="button"><?php echo $em; ?></button><?php endforeach; ?>
                  </div>
                </div>
              </div>
              <button type="button" class="wc-round rounded-circle" id="wcMic" title="ອັດສຽງສົ່ງໃຫ້ລູກຄ້າ"><i class="fas fa-microphone"></i></button>
              <button type="submit" class="wc-round wc-send rounded-circle" id="wcSendBtn" title="ສົ່ງ (Enter)" hidden><i class="fas fa-paper-plane"></i></button>
            </div>
          </form>
          <!-- ກຳລັງອັດສຽງ: ເວລາ + ແຖບລະດັບສຽງ (ບໍ່ຂະຫຍັບ = ໄມບໍ່ໄດ້ຍິນ) + ຍົກເລີກ / ສົ່ງ (ສູງສຸດ 60 ວິ) -->
          <div class="wc-rec" id="wcRec" hidden>
            <span class="wc-rec-dot"></span>
            <b id="wcRecTime">0:00</b>
            <span class="wc-rec-hint">ກຳລັງອັດສຽງ… <span class="wc-rec-level"><i id="wcRecLevel"></i></span></span>
            <button type="button" class="btn btn-light" id="wcRecCancel">ຍົກເລີກ</button>
            <button type="button" class="btn btn-primary" id="wcRecSend"><i class="fas fa-paper-plane"></i> ສົ່ງສຽງ</button>
          </div>
        </section>
      </div>
    </div>
  </section>
</div>
<script>
(function () {
  var self = 'index.php', current = 0, lastId = 0, chats = [];
  var $list = $('#wcList'), $log = $('#wcLog');
  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  // ຊື່ທີ່ສະແດງ + ປ້າຍປະເພດລູກຄ້າ: ສະມາຊິກເວັບໄຊ (#ເລກສະມາຊິກ) ຫຼື ລູກຄ້າທົ່ວໄປ
  function who(c) { return c.member_name || c.name || 'ລູກຄ້າທົ່ວໄປ'; }
  function kind(c) {
    return c.member_no ? '<span class="badge badge-success wc-kind">WL-' + String(c.member_no).padStart(4, '0') + '</span>'
                       : '<span class="badge badge-light wc-kind">ລູກຄ້າທົ່ວໄປ</span>';
  }

  var DEFAULT_AV = <?php echo json_encode($base_path . 'assets/img/users/default.png'); ?>;
  function avatar(c) {
    // ຮູບຜູ້ໃຊ້ເລີ່ມຕົ້ນ (assets/img/users/default.png) — ຮູບ Google ຂອງສະມາຊິກ ຖ້າມີ (ໂຫຼດບໍ່ໄດ້ → ກັບເປັນຮູບເລີ່ມຕົ້ນ)
    var src = c.avatar_url || DEFAULT_AV;
    return '<span class="wc-av"><img src="' + esc(src) + '" alt="" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' + DEFAULT_AV + '\'"></span>';
  }
  var filter = 'all', query = '';
  function pass(c) {
    if (filter === 'unread' && !c.unread_staff) return false;
    if (filter === 'member' && !c.member_no) return false;
    if (filter === 'guest' && c.member_no) return false;
    if (filter === 'closed' ? c.status !== 'closed' : false) return false;
    if (!query) return true;
    var hay = (who(c) + ' ' + (c.tel || '') + ' ' + (c.last_body || '') + ' ' + (c.member_no ? 'WL-' + String(c.member_no).padStart(4, '0') : '')).toLowerCase();
    return hay.indexOf(query) !== -1;
  }
  function renderList() {
    if (!chats.length) { $list.html('<p class="wc-empty">ຍັງບໍ່ມີແຊັດ</p>'); return; }
    var shown = chats.filter(pass);
    if (!shown.length) { $list.html('<p class="wc-empty">ບໍ່ພົບແຊັດທີ່ຄົ້ນຫາ</p>'); return; }
    $list.html(shown.map(function (c) {
      return '<button type="button" class="wc-item' + (c.id === current ? ' is-on' : '') + (c.status === 'closed' ? ' is-closed' : '') + '" data-id="' + c.id + '">' +
        avatar(c) +
        '<span class="wc-item-main">' +
          '<span class="wc-item-top"><b>' + esc(who(c)) + '</b>' + (c.member_no ? kind(c) : '') + '<small>' + esc(c.when) + '</small></span>' +
          '<span class="wc-item-bottom"><span class="wc-item-last">' + (c.ai_enabled ? '' : '<i class="fas fa-user-tie" title="ພະນັກງານຕອບເອງ"></i> ') + esc(c.last_body) + '</span>' +
          (c.unread_staff ? '<span class="wc-badge">' + c.unread_staff + '</span>' : '') + '</span>' +
        '</span>' +
        '</button>';
    }).join(''));
  }
  function loadList() {
    $.getJSON(self, { ajax: 'list' }).done(function (res) {
      if (!res.success) return;
      chats = res.chats;
      $('#wcUnread').text(res.unread).prop('hidden', !res.unread);
      renderList();
    });
  }
  $('#wcSearch').on('input', function () { query = this.value.trim().toLowerCase(); renderList(); });
  $('#wcFilterBtn').on('click', function (e) { e.stopPropagation(); $('#wcFilterMenu').prop('hidden', function (i, h) { return !h; }); });
  $('#wcFilterMenu').on('click', 'button', function () {
    filter = $(this).data('f');
    $(this).addClass('is-on').siblings().removeClass('is-on');
    $('#wcFilterBtn').toggleClass('is-active', filter !== 'all');
    $('#wcFilterMenu').prop('hidden', true);
    renderList();
  });
  $(document).on('click', function (e) { if (!$(e.target).closest('.wc-filter').length) $('#wcFilterMenu').prop('hidden', true); });

  function bubble(m) {
    var who = m.sender === 'visitor' ? 'ລູກຄ້າ' : (m.sender === 'bot' ? 'ບັອດ' : (m.staff || 'ພະນັກງານ'));
    // ລິ້ງ (ເຊັ່ນ ຕຳແໜ່ງລູກຄ້າ Google Maps) ກົດໄດ້ — escape ກ່ອນ ແລ້ວຈຶ່ງຫໍ່ URL ດ້ວຍ <a>
    var f = m.file, kb = f ? (f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB') : '';
    var body = f ? (f.kind === 'image' ? '<a href="' + esc(f.url) + '" target="_blank" rel="noopener"><img class="wc-att-img" src="' + esc(f.url) + '" alt=""></a>'
        : f.kind === 'video' ? '<video class="wc-att-img" src="' + esc(f.url) + '" controls preload="metadata"></video>'
        : '<a href="' + esc(f.url) + '" download="' + esc(f.name) + '"><i class="fas fa-file-download"></i> ' + esc(f.name) + '</a> <small>' + kb + '</small>')
      : m.audio ? '<audio controls preload="metadata" src="' + esc(m.audio) + '"></audio>' + (m.secs ? ' <small>' + m.secs + ' ວິ</small>' : '')
      : esc(m.body).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    // ຄຳແປ: ຂໍ້ຄວາມລູກຄ້າ → ພາສາລາວ, ຄຳຕອບພະນັກງານ → ຕົ້ນສະບັບທີ່ພິມ (ລູກຄ້າເຫັນຄຳແປ)
    if (m.tr) body += '<div class="wc-tr"><i class="fas fa-language"></i> ' + (m.sender === 'staff' ? 'ທີ່ພິມ: ' : 'ແປ: ') + esc(m.tr) + '</div>';
    // ຂໍ້ຄວາມທີ່ສົ່ງຫາລູກຄ້າ (ພະນັກງານ/ບັອດ) ມີສະຖານະ ອ່ານແລ້ວ / ຍັງບໍ່ອ່ານ ຢູ່ລຸ່ມ (paintRead)
    var read = m.sender !== 'visitor' ? '<span class="wc-read"></span>' : '';
    return '<div class="wc-msg is-' + m.sender + '" data-id="' + m.id + '"><span class="wc-who">' + esc(who) + ' · ' + esc(m.time) + '</span><div class="wc-bubble">' + body + '</div>' + read + '</div>';
  }
  // ✓ ຍັງບໍ່ອ່ານ / ✓✓ ອ່ານແລ້ວ — ລູກຄ້າເປີດແຊັດເຫັນແລ້ວ (visitor_read_id = id ຂໍ້ຄວາມສູງສຸດທີ່ລູກຄ້າເຫັນ)
  function paintRead(visitorRead) {
    $log.find('.wc-read').each(function () {
      var seen = +$(this).closest('[data-id]').data('id') <= visitorRead;
      $(this).text(seen ? '✓✓ ລູກຄ້າອ່ານແລ້ວ' : '✓ ຍັງບໍ່ອ່ານ').toggleClass('is-seen', seen);
    });
  }
  function loadMessages(reset) {
    if (!current) return;
    if (reset) { lastId = 0; $log.empty(); }
    $.getJSON(self, { ajax: 'messages', chat_id: current, after: lastId }).done(function (res) {
      if (!res.success || !res.chat) return;
      var c = res.chat;
      $('#wcName').html(esc(who(c)) + ' ' + kind(c));
      $('#wcTel').text(c.tel || 'ຍັງບໍ່ມີເບີໂທ').attr('href', c.tel ? 'tel:' + c.tel.replace(/\s/g, '') : '#');
      $('#wcSince').text('ເລີ່ມ ' + c.created_at);
      var foreign = c.lang && c.lang !== 'lo';
      $('#wcLang').text(c.lang_name ? 'ພາສາລູກຄ້າ: ' + c.lang_name : '').prop('hidden', !foreign);
      $('#wcTrWrap').prop('hidden', !foreign);
      $('#wcTrLang').text(c.lang_name || '');
      $('#wcAi').prop('checked', +c.ai_enabled === 1);
      $('#wcStatus').text(c.status === 'closed' ? 'ເປີດຫ້ອງຄືນ' : 'ປິດຫ້ອງ (ຈົບແລ້ວ)').data('status', c.status);
      if (res.messages.length) {
        res.messages.forEach(function (m) { lastId = m.id; $log.append(bubble(m)); });
        $log.scrollTop($log[0].scrollHeight);
      }
      paintRead(+c.visitor_read_id || 0);
    });
  }

  $list.on('click', '.wc-item', function () {
    current = +$(this).data('id');
    $('.wc-pick').prop('hidden', true);
    $('.wc-head, #wcLog, #wcReply').prop('hidden', false);
    $list.find('.wc-item').removeClass('is-on');
    $(this).addClass('is-on').find('.wc-badge').remove();
    loadMessages(true);
  });

  $('#wcReply').on('submit', function (e) {
    e.preventDefault();
    var text = $('#wcText').val().trim();
    if (!text || !current) return;
    var $btn = $('#wcReply button[type=submit]').prop('disabled', true);
    $.post(self, { action: 'reply', chat_id: current, text: text, translate: $('#wcTr').is(':checked') && !$('#wcTrWrap').prop('hidden') ? 1 : 0 }, null, 'json').always(function () { $btn.prop('disabled', false); }).done(function (res) {
      if (!res.success) return Swal.fire({ icon: 'error', text: res.message });
      if (res.warning) Swal.fire({ icon: 'warning', text: res.warning });
      $('#wcText').val('');
      syncBar();
      loadMessages(false);
      loadList();
    });
  });
  $('#wcText').on('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.originalEvent.isComposing) { e.preventDefault(); $('#wcReply').trigger('submit'); }
  });
  $('#wcAi').on('change', function () {
    $.post(self, { action: 'ai', chat_id: current, on: this.checked ? 1 : 0 }, loadList, 'json');
  });
  $('#wcStatus').on('click', function () {
    var next = $(this).data('status') === 'closed' ? 'open' : 'closed';
    $.post(self, { action: 'status', chat_id: current, status: next }, function () { loadMessages(false); loadList(); }, 'json');
  });

  // ພະນັກງານອັດສຽງ (MediaRecorder) → POST action=voice. ວັດລະດັບສຽງ: ງຽບທັງໝົດ = ບໍ່ສົ່ງ (ໄມບໍ່ໄດ້ຍິນ).
  // ໄມໃຊ້ໄດ້ສະເພາະ https / localhost (ເປີດ backend ຜ່ານ http://192.168.x.x ອັດບໍ່ໄດ້)
  var rec = null, chunks = [], startAt = 0, timer = null, cancelled = false, stream = null, actx = null, analyser = null, peak = 0;
  function showRec(on) { $('#wcRec').prop('hidden', !on); $('#wcReply').prop('hidden', on); }
  function stopRec(cancel) { cancelled = !!cancel; clearInterval(timer); if (rec && rec.state !== 'inactive') rec.stop(); }
  $('#wcMic').on('click', function () {
    if (!current) return;
    if (!window.isSecureContext) return Swal.fire({ icon: 'warning', text: 'ອັດສຽງໄດ້ສະເພາະເມື່ອເປີດລະບົບດ້ວຍ https ຫຼື localhost' });
    if (!navigator.mediaDevices || !window.MediaRecorder) return Swal.fire({ icon: 'warning', text: 'ບຣາວເຊີນີ້ອັດສຽງບໍ່ໄດ້' });
    navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } }).then(function (s) {
      stream = s; chunks = []; cancelled = false; peak = 0; startAt = Date.now();
      try {
        actx = new (window.AudioContext || window.webkitAudioContext)();
        analyser = actx.createAnalyser(); analyser.fftSize = 512;
        actx.createMediaStreamSource(s).connect(analyser);
      } catch (e) { analyser = null; peak = 1; }
      var types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'], mime = '';
      for (var i = 0; i < types.length; i++) if (MediaRecorder.isTypeSupported(types[i])) { mime = types[i]; break; }
      rec = new MediaRecorder(s, mime ? { mimeType: mime } : undefined);
      rec.ondataavailable = function (e) { if (e.data && e.data.size) chunks.push(e.data); };
      rec.onstop = function () {
        stream.getTracks().forEach(function (t) { t.stop(); });
        if (actx) { actx.close().catch(function () {}); actx = null; }
        showRec(false);
        if (cancelled || !chunks.length) return;
        if (peak < 0.03) return Swal.fire({ icon: 'warning', text: 'ບໍ່ໄດ້ຍິນສຽງຈາກໄມໂຄຣໂຟນ — ກວດເບິ່ງວ່າເລືອກໄມຖືກ ແລະ ບໍ່ໄດ້ປິດສຽງ' });
        var type = rec.mimeType || 'audio/webm', fd = new FormData();
        fd.append('action', 'voice');
        fd.append('chat_id', current);
        fd.append('secs', Math.max(1, Math.round((Date.now() - startAt) / 1000)));
        fd.append('audio', new Blob(chunks, { type: type }), 'voice.' + (type.indexOf('mp4') > -1 ? 'm4a' : type.indexOf('ogg') > -1 ? 'ogg' : 'webm'));
        $.ajax({ url: self, method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' }).done(function (res) {
          if (!res.success) return Swal.fire({ icon: 'error', text: res.message });
          loadMessages(false); loadList();
        }).fail(function () { Swal.fire({ icon: 'error', text: 'ສົ່ງສຽງບໍ່ສຳເລັດ' }); });
      };
      rec.start();
      showRec(true);
      $('#wcRecTime').text('0:00');
      timer = setInterval(function () {
        if (analyser) {
          var buf = new Uint8Array(analyser.fftSize), max = 0;
          analyser.getByteTimeDomainData(buf);
          for (var j = 0; j < buf.length; j++) max = Math.max(max, Math.abs(buf[j] - 128) / 128);
          peak = Math.max(peak, max);
          $('#wcRecLevel').css('width', Math.min(100, Math.round(max * 220)) + '%');
        }
        var sec = Math.floor((Date.now() - startAt) / 1000);
        $('#wcRecTime').text(Math.floor(sec / 60) + ':' + ('0' + (sec % 60)).slice(-2));
        if (sec >= 60) stopRec(false);
      }, 100);
    }).catch(function () { Swal.fire({ icon: 'warning', text: 'ກະລຸນາອະນຸຍາດໃຫ້ໃຊ້ໄມໂຄຣໂຟນ' }); });
  });
  // ແນບໄຟລ໌ (ຮູບ / ວິດີໂອ / ເອກະສານ) → action=file ເທື່ອລະໄຟລ໌
  // ປຸ່ມໄຟລ໌ = ເລືອກໄດ້ແຕ່ເອກະສານ, ປຸ່ມຮູບ = ເລືອກໄດ້ແຕ່ຮູບ + ວິດີໂອ — ໃຊ້ #wcFile ອັນດຽວກັນ (ປ່ຽນ accept ກ່ອນເປີດ)
  $('#wcAttach').on('click', function () { if (current) $('#wcFile').attr('accept', $('#wcFile').attr('data-accept-docs')).trigger('click'); });    // ສະເພາະເອກະສານ
  $('#wcImage').on('click', function () { if (current) $('#wcFile').attr('accept', $('#wcFile').attr('data-accept-media')).trigger('click'); });   // ສະເພາະຮູບ + ວິດີໂອ
  // ຊ່ອງພິມ: ສູງຕາມຂໍ້ຄວາມ (ສູງສຸດ 5 ແຖວ); ມີຂໍ້ຄວາມ → ປຸ່ມໄມກາຍເປັນປຸ່ມສົ່ງ
  function syncBar() {
    var t = document.getElementById('wcText');
    t.style.height = 'auto';
    t.style.height = Math.min(t.scrollHeight, 120) + 'px';
    var has = t.value.trim() !== '';
    $('#wcMic').prop('hidden', has);
    $('#wcSendBtn').prop('hidden', !has);
  }
  $('#wcText').on('input', syncBar);
  function insertText(txt) {
    var t = document.getElementById('wcText'), s = t.selectionStart || t.value.length, e2 = t.selectionEnd || t.value.length;
    t.value = t.value.slice(0, s) + txt + t.value.slice(e2);
    t.focus(); t.selectionStart = t.selectionEnd = s + txt.length;
    syncBar();
  }
  $('#wcQuickBtn').on('click', function (e) { e.stopPropagation(); $('#wcEmoji').prop('hidden', true); $('#wcQuick').prop('hidden', function (i, h) { return !h; }); });
  $('#wcEmojiBtn').on('click', function (e) { e.stopPropagation(); $('#wcQuick').prop('hidden', true); $('#wcEmoji').prop('hidden', function (i, h) { return !h; }); });
  $('#wcQuick').on('click', 'button', function () { var t = document.getElementById('wcText'); t.value = ''; insertText($(this).data('text')); $('#wcQuick').prop('hidden', true); });
  $('#wcEmoji').on('click', 'button', function () { insertText($(this).text()); });
  $(document).on('click', function (e) { if (!$(e.target).closest('.wc-pop-wrap').length) $('#wcQuick, #wcEmoji').prop('hidden', true); });
  $('#wcFile').on('change', function () {
    var files = Array.prototype.slice.call(this.files || [], 0, 10);
    this.value = '';
    (function next() {
      var f = files.shift();
      if (!f) { loadMessages(false); loadList(); return; }
      var fd = new FormData();
      fd.append('action', 'file'); fd.append('chat_id', current); fd.append('file', f);
      $.ajax({ url: self, method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' }).done(function (res) {
        if (!res.success) Swal.fire({ icon: 'error', text: f.name + ': ' + res.message });
      }).fail(function () { Swal.fire({ icon: 'error', text: 'ສົ່ງໄຟລ໌ບໍ່ສຳເລັດ: ' + f.name }); }).always(next);
    })();
  });
  $('#wcRecCancel').on('click', function () { stopRec(true); });
  $('#wcRecSend').on('click', function () { stopRec(false); });

  loadList();
  setInterval(function () { if (!document.hidden) { loadList(); loadMessages(false); } }, 5000);
})();
</script>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
