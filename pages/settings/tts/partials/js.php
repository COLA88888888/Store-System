<?php
/**
 * ສ່ວນ: JS ຂອງໜ້າຕັ້ງຄ່າລະບົບສຽງ — ເລືອກລະບົບສຽງ, ລາຍຊື່ສຽງ, ທົດສອບຫຼິ້ນສຽງ, ວັດຈະນານຸກົມ.
 * ແຈ້ງເຕືອນທັງໝົດຜ່ານ SweetAlert (Swal ໂຫຼດໃນ layouts/header.php): ສຳເລັດ = toast ມຸມເທິງ, ຜິດພາດ/ຢືນຢັນ = ກ່ອງ Swal.
 * ມາຈາກ pages/settings/tts/index.php (ໃຊ້ $base_path, $currentVoice, $message, $message_type)
 */
?>
<script>
var TTS_API = '<?php echo $base_path; ?>api/';
var currentVoiceSaved = <?php echo json_encode($currentVoice, JSON_UNESCAPED_UNICODE); ?>;

var EDGE_VOICES = [
  { id: 'lo-LA-KeomanyNeural', label: 'Keomany (ເກສມະນີ — ສຽງຍິງ, ຫວານ ຊັດເຈນ)' },
  { id: 'lo-LA-ChanthavongNeural', label: 'Chanthavong (ຈັນທະວົງ — ສຽງຊາຍ, ທຸ້ມ ນຸ່ມນວນ)' }
];

var POS_PRESETS = [
  { word: 'StoreMini', pron: 'ສະໂຕ ມິນິ' },
  { word: 'Store', pron: 'ສະໂຕ' },
  { word: 'Mini', pron: 'ມິນິ' },
  { word: 'ml', pron: 'ເອັມແອວ' },
  { word: 'kg', pron: 'ກິໂລ' },
  { word: 'BCEL', pron: 'ບີຊີອີແອວ' },
  { word: 'OnePay', pron: 'ວັນເພ' },
  { word: 'LDB', pron: 'ແອວດີບີ' },
  { word: 'JDB', pron: 'ເຈດີບີ' },
  { word: 'QR', pron: 'ຄິວອາ' },
  { word: 'POS', pron: 'ພີໂອເອັສ' },
  { word: 'VIP', pron: 'ວີໄອພີ' },
  { word: 'KIP', pron: 'ກີບ' }
];

// ---- SweetAlert helpers ----
var ttsToast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200, timerProgressBar: true });
function ttsNotify(icon, title, text) { ttsToast.fire({ icon: icon, title: title, text: text || '' }); }
function ttsError(title, html, extra) {
  return Swal.fire(Object.assign({ icon: 'error', title: title, html: html || '', confirmButtonColor: '#2563eb', confirmButtonText: 'ຕົກລົງ' }, extra || {}));
}
function ttsEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

// ---- ເປີດ/ປິດສຽງ (ສະແດງໃນຫົວໜ້າທັນທີ; ບັນທຶກເມື່ອກົດ "ບັນທຶກການຕັ້ງຄ່າ") ----
function ttsPaintEnabled() {
  var on = document.getElementById('tts_enabled').checked;
  document.getElementById('ttsEnabledLabel').textContent = on ? 'ເປີດໃຊ້ງານສຽງ' : 'ປິດສຽງຢູ່';
  document.getElementById('heroStatusText').textContent = on ? 'ເປີດໃຊ້ງານສຽງ' : 'ປິດສຽງຢູ່';
  document.getElementById('heroStatusChip').classList.toggle('is-off', !on);
}

// ---- ເລືອກລະບົບສຽງ ----
function selectProvider(prov) {
  var isEdge = prov === 'edge';
  document.getElementById(isEdge ? 'provEdge' : 'provSiangmuan').checked = true;
  document.getElementById('cardEdge').classList.toggle('is-active', isEdge);
  document.getElementById('cardSiangmuan').classList.toggle('is-active', !isEdge);
  $('#apiKeyGroup').stop(true, true)[isEdge ? 'slideUp' : 'slideDown'](150);
  document.getElementById('activeEngineBadge').textContent = isEdge ? 'Microsoft Edge (ຟຣີ)' : 'SiangMuan API';
  document.getElementById('heroEngineChip').innerHTML = isEdge
    ? '<i class="fab fa-microsoft"></i> Edge Neural · ຟຣີ ບໍ່ຈຳກັດ'
    : '<i class="fas fa-cloud"></i> SiangMuan AI';
  loadVoicesList();
}

function renderVoiceOptions(voices) {
  var sel = document.getElementById('ttsVoiceSelect');
  var curVal = sel.value || currentVoiceSaved;
  sel.innerHTML = '';
  voices.forEach(function (v) {
    var id = v.name || v.id || v.voice_id;
    var opt = document.createElement('option');
    opt.value = id;
    opt.textContent = v.label || id;
    if (id === curVal) opt.selected = true;
    sel.appendChild(opt);
  });
  if (!sel.value && voices.length > 0) sel.selectedIndex = 0;
}

function loadVoicesList(manual) {
  if (document.getElementById('provEdge').checked) {
    renderVoiceOptions(EDGE_VOICES);
    if (manual) ttsNotify('success', 'ໂຫຼດລາຍຊື່ສຽງແລ້ວ', EDGE_VOICES.length + ' ສຽງ');
    return;
  }
  var icon = document.getElementById('voiceReloadIcon');
  icon.classList.add('fa-spin');
  var fallback = [{ id: 'sengchanh', label: 'sengchanh (ສຽງເລີ່ມຕົ້ນ)' }];
  fetch(TTS_API + 'tts_voices.php?provider=siangmuan')
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d.success && d.voices) {
        var list = Array.isArray(d.voices) ? d.voices : (d.voices.voices || []);
        renderVoiceOptions(list.length ? list : fallback);
        if (manual) ttsNotify('success', 'ໂຫຼດລາຍຊື່ສຽງແລ້ວ', list.length + ' ສຽງ');
      } else {
        renderVoiceOptions(fallback);
        if (manual) ttsNotify('warning', 'ໂຫຼດລາຍຊື່ສຽງບໍ່ໄດ້', d.message || 'ໃຊ້ສຽງເລີ່ມຕົ້ນແທນ');
      }
    })
    .catch(function () {
      renderVoiceOptions(fallback);
      if (manual) ttsNotify('warning', 'ເຊື່ອມຕໍ່ SiangMuan ບໍ່ໄດ້', 'ໃຊ້ສຽງເລີ່ມຕົ້ນແທນ');
    })
    .then(function () { icon.classList.remove('fa-spin'); });
}

function toggleApiKeyVisibility() {
  var inp = document.getElementById('apiKeyInput');
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  document.getElementById('eyeIcon').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}

// ---- ທົດສອບສຽງ ----
function setSample(text) {
  document.getElementById('ttsTextInput').value = text;
  playVoice();
}

function ttsSetPlaying(state, text) {
  var btn = document.getElementById('ttsPlayBtn');
  var now = document.getElementById('ttsNow');
  if (state === 'loading') {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>ກຳລັງສ້າງສຽງ...</span>';
    return;
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-play"></i> <span>ກົດເພື່ອຟັງສຽງ</span>';
  if (state === 'playing') {
    document.getElementById('ttsNowText').textContent = text || 'ກຳລັງຫຼິ້ນສຽງ';
    now.classList.add('is-on');
    now.classList.remove('is-paused');
  } else {
    now.classList.remove('is-on');
  }
}

function playVoice() {
  var text = document.getElementById('ttsTextInput').value.trim();
  var voice = document.getElementById('ttsVoiceSelect').value;
  var prov = document.getElementById('provEdge').checked ? 'edge' : 'siangmuan';
  var player = document.getElementById('ttsAudioPlayer');

  if (!text) {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນຂໍ້ຄວາມກ່ອນ', confirmButtonColor: '#2563eb', confirmButtonText: 'ຕົກລົງ' })
      .then(function () { document.getElementById('ttsTextInput').focus(); });
    return;
  }

  ttsSetPlaying('loading');
  // test=1: ປຸ່ມທົດສອບໃຊ້ໄດ້ເຖິງປິດສຽງຢູ່ (ໜ້າຂາຍ/ຈໍລູກຄ້າ ບໍ່ມີສຽງເມື່ອປິດ — api/tts_speak.php)
  var audioUrl = TTS_API + 'tts_speak.php?test=1&provider=' + encodeURIComponent(prov) + '&text=' + encodeURIComponent(text) + (voice ? '&voice=' + encodeURIComponent(voice) : '');

  fetch(audioUrl)
    .then(function (res) {
      if (!res.ok) {
        return res.json().catch(function () { return { message: 'HTTP ' + res.status }; }).then(function (e) {
          var err = new Error(e.message || e.error || ('HTTP ' + res.status));
          err.status = res.status;
          throw err;
        });
      }
      var provider = res.headers.get('X-TTS-Provider') || prov;
      var source = res.headers.get('X-TTS-Source') || '';
      return res.blob().then(function (blob) { return { blob: blob, provider: provider, source: source }; });
    })
    .then(function (result) {
      player.src = URL.createObjectURL(result.blob);
      player.style.display = 'block';
      return player.play().then(function () {
        var provText = result.provider === 'edge-fallback' ? 'Edge (ສຳຮອງອັດຕະໂນມັດ)'
          : (String(result.provider).indexOf('edge') !== -1 ? 'Microsoft Edge Neural' : 'SiangMuan AI');
        ttsSetPlaying('playing', 'ກຳລັງຫຼິ້ນ · ' + provText + (result.source === 'cache' ? ' (ຈາກ cache)' : ''));
        if (result.provider === 'edge-fallback') {
          ttsNotify('info', 'SiangMuan ໃຊ້ບໍ່ໄດ້ ຕອນນີ້', 'ຫຼິ້ນດ້ວຍ Microsoft Edge ແທນ');
        }
      });
    })
    .catch(function (err) {
      ttsSetPlaying('idle');
      var msg = String(err.message || '');
      var quotaOut = /not enough characters|quota|balance/i.test(msg);
      var rateLimited = err.status === 429 || msg.indexOf('429') !== -1;
      var switchBtn = { showCancelButton: true, confirmButtonText: '<i class="fab fa-microsoft mr-1"></i> ສະຫຼັບໄປ Edge (ຟຣີ)', cancelButtonText: 'ປິດ', confirmButtonColor: '#16a34a' };
      var p;
      if (quotaOut) {
        p = Swal.fire(Object.assign({ icon: 'warning', title: 'ໂຄຕ້າຟຣີ SiangMuan ໝົດແລ້ວ',
          html: 'ສະຫຼັບໄປໃຊ້ <b>Microsoft Edge Neural</b> — ຟຣີ 100% ບໍ່ຈຳກັດ ບໍ່ຕ້ອງເຕີມເງິນ' }, switchBtn));
      } else if (rateLimited) {
        p = Swal.fire(Object.assign({ icon: 'warning', title: 'ຮຽກໃຊ້ SiangMuan ຖີ່ເກີນໄປ',
          html: 'SiangMuan ຈຳກັດ 10 ຄັ້ງ/ນາທີ. ແນະນຳ <b>Microsoft Edge Neural</b> ທີ່ໄວ ແລະ ບໍ່ຈຳກັດ' }, switchBtn));
      } else {
        p = ttsError('ຫຼິ້ນສຽງບໍ່ໄດ້', ttsEsc(msg) + '<div class="small text-muted mt-2">ລະບົບຈະລອງອ່ານດ້ວຍສຽງຂອງ browser ແທນ</div>');
      }
      p.then(function (r) {
        if ((quotaOut || rateLimited) && r.isConfirmed) {
          selectProvider('edge');
          ttsNotify('success', 'ສະຫຼັບເປັນ Microsoft Edge ແລ້ວ', 'ກົດ "ບັນທຶກການຕັ້ງຄ່າ" ເພື່ອໃຊ້ຕະຫຼອດ');
        }
      });
      // ຫຼິ້ນສຽງສຳຮອງຜ່ານ Browser Speech ທັນທີ ເພື່ອບໍ່ໃຫ້ມິດງຽບ
      if (window.LaoTTS && typeof LaoTTS.speak === 'function') LaoTTS.speak(text);
    });
}

document.getElementById('ttsAudioPlayer').addEventListener('ended', function () { ttsSetPlaying('idle'); });
document.getElementById('ttsAudioPlayer').addEventListener('pause', function () {
  document.getElementById('ttsNow').classList.add('is-paused');
});
document.getElementById('ttsAudioPlayer').addEventListener('play', function () {
  document.getElementById('ttsNow').classList.remove('is-paused');
});

// ---- ວັດຈະນານຸກົມການອອກສຽງ ----
function renderPresets() {
  var html = POS_PRESETS.map(function (item, i) {
    return '<div class="tts-lex-item">' +
      '<span class="tts-lex-word">' + ttsEsc(item.word) + '</span>' +
      '<i class="fas fa-long-arrow-alt-right tts-lex-arrow"></i>' +
      '<span class="tts-lex-pron">' + ttsEsc(item.pron) + '</span>' +
      '<button type="button" class="btn btn-sm btn-outline-primary" data-preset="' + i + '"><i class="fas fa-plus mr-1"></i>ເພີ່ມ</button>' +
    '</div>';
  }).join('');
  document.getElementById('presetTableBody').innerHTML = html;
}
$(document).on('click', '#presetTableBody [data-preset]', function () {
  var item = POS_PRESETS[+this.getAttribute('data-preset')];
  if (item) sendLexiconWord(item.word, item.pron, this);
});
renderPresets();

function submitLexicon() {
  var word = document.getElementById('lexWord').value.trim();
  var pron = document.getElementById('lexPron').value.trim();
  if (!word || !pron) {
    Swal.fire({ icon: 'warning', title: 'ກະລຸນາປ້ອນທັງຄຳສັບ ແລະ ຄຳອ່ານ', confirmButtonColor: '#2563eb', confirmButtonText: 'ຕົກລົງ' })
      .then(function () { document.getElementById(word ? 'lexPron' : 'lexWord').focus(); });
    return;
  }
  sendLexiconWord(word, pron, document.getElementById('lexAddBtn'), true);
}

function lexPost(body) {
  return fetch(TTS_API + 'tts_lexicon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  }).then(function (res) { return res.json(); });
}

function sendLexiconWord(word, pron, btn, clearInputs) {
  var oldHtml = btn ? btn.innerHTML : '';
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
  lexPost({ action: 'add_word', word: word, pronunciation: pron })
    .then(function (data) {
      if (data.success) {
        ttsNotify('success', 'ບັນທຶກຄຳສັບແລ້ວ', word + ' → ' + pron);
        if (clearInputs) {
          document.getElementById('lexWord').value = '';
          document.getElementById('lexPron').value = '';
          document.getElementById('lexWord').focus();
        }
      } else {
        ttsError('ບັນທຶກຄຳສັບບໍ່ສຳເລັດ', ttsEsc(data.message || 'ເກີດຂໍ້ຜິດພາດ'));
      }
    })
    .catch(function (err) { ttsError('ເຊື່ອມຕໍ່ຜິດພາດ', ttsEsc(err.message)); })
    .then(function () { if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; } });
}

function addAllPresets() {
  var listHtml = POS_PRESETS.map(function (p) { return '<b>' + ttsEsc(p.word) + '</b> → ' + ttsEsc(p.pron); }).join('<br>');
  Swal.fire({
    icon: 'question',
    title: 'ເພີ່ມຄຳສັບແນະນຳທັງ ' + POS_PRESETS.length + ' ຄຳ?',
    html: '<div style="font-size:0.9rem; line-height:1.8;">' + listHtml + '</div>',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="fas fa-check-double mr-1"></i> ເພີ່ມທັງໝົດ',
    cancelButtonText: 'ຍົກເລີກ',
    showLoaderOnConfirm: true,
    allowOutsideClick: function () { return !Swal.isLoading(); },
    preConfirm: function () {
      return lexPost({ action: 'batch_add', words: POS_PRESETS })
        .then(function (data) {
          if (!data.success) throw new Error(data.message || 'ເກີດຂໍ້ຜິດພາດ');
          return data;
        })
        .catch(function (err) { Swal.showValidationMessage(ttsEsc(err.message)); });
    }
  }).then(function (r) {
    if (r.isConfirmed && r.value) ttsNotify('success', 'ເພີ່ມຄຳສັບແນະນຳແລ້ວ', r.value.message || '');
  });
}

// ---- ບັນທຶກການຕັ້ງຄ່າ ----
document.getElementById('ttsSettingsForm').addEventListener('submit', function () {
  Swal.fire({ title: 'ກຳລັງບັນທຶກ...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
});

document.addEventListener('DOMContentLoaded', function () {
  loadVoicesList();
<?php if ($message !== ''): ?>
  <?php if ($message_type === 'success'): ?>
  ttsNotify('success', <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>);
  <?php else: ?>
  ttsError('ບັນທຶກບໍ່ສຳເລັດ', <?php echo json_encode(htmlspecialchars($message), JSON_UNESCAPED_UNICODE); ?>);
  <?php endif; ?>
<?php endif; ?>
});
</script>
