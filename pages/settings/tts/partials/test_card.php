<?php
/**
 * ສ່ວນ: ທົດສອບຫຼິ້ນສຽງ — ປ້ອນຂໍ້ຄວາມ ຫຼື ກົດຕົວຢ່າງ ແລ້ວຟັງສຽງຈາກລະບົບທີ່ເລືອກ (api/tts_speak.php)
 * ມາຈາກ pages/settings/tts/index.php
 */
$ttsSamples = [
    ['fa-cash-register',       'ຍອດທີ່ຕ້ອງຈ່າຍ',      'ຍອດລວມທີ່ຕ້ອງຊຳລະ 85,000 ກີບ'],
    ['fa-money-bill-wave',     'ເງິນສົດ (ມີທອນ)',     'ໄດ້ຮັບເງິນສົດ 100,000 ກີບ, ເງິນທອນ 15,000 ກີບ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-money-bill',          'ເງິນສົດ (ທອນ 0)',     'ໄດ້ຮັບເງິນສົດ 85,000 ກີບ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-university',          'ໂອນ BCEL One',        'ໄດ້ຮັບເງິນໂອນ ຜ່ານທະນາຄານ BCEL One 85,000 ກີບ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-university',          'ໂອນ LDB',             'ໄດ້ຮັບເງິນໂອນ ຜ່ານທະນາຄານ LDB 50,000 ກີບ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-random',              'ແຍກຈ່າຍ',             'ໄດ້ຮັບເງິນສົດ 30,000 ກີບ, ເງິນໂອນ ຜ່ານທະນາຄານ BCEL One 50,000 ກີບ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-coins',               'ເງິນບາດ',             'ໄດ້ຮັບເງິນສົດ 500 ບາດ, ເງິນທອນ 50 ບາດ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-dollar-sign',         'ເງິນໂດລາ',            'ໄດ້ຮັບເງິນສົດ 20 ໂດລາ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-yen-sign',            'ເງິນຢວນ',             'ໄດ້ຮັບເງິນໂອນ 100 ຢວນ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['fa-box-open',            'ສິນຄ້າໝົດ',           'ເປັບຊີ 330ml, ສິນຄ້າໝົດແລ້ວ'],
    ['fa-exclamation-triangle','ສິນຄ້າໃກ້ໝົດ',        'ນ້ຳດື່ມຫົວເສືອ 600ml, ສິນຄ້າໃກ້ໝົດແລ້ວ, ເຫຼືອພຽງ 3 ແກ້ວ'],
    ['fa-bullhorn',            'ເອີ້ນຄິວ',            'ຂໍເຊີນຄິວເລກທີ 08 ທີ່ຊ່ອງບໍລິການ'],
];
?>
      <div class="tts-card tts-test-card">
        <div class="tts-card-head">
          <h6 class="tts-card-title">
            <span class="ico" style="background:#f5f3ff; color:#7c3aed;"><i class="fas fa-headphones"></i></span>
            ທົດສອບສຽງ
          </h6>
          <span class="badge badge-pill badge-light border text-dark font-weight-bold px-3 py-2" id="activeEngineBadge">
            <?php echo $currentProvider === 'edge' ? 'Microsoft Edge (ຟຣີ)' : 'SiangMuan API'; ?>
          </span>
        </div>
        <div class="tts-card-body">
          <div class="tts-section-label"><i class="fas fa-keyboard text-secondary"></i> ຂໍ້ຄວາມທົດສອບ</div>
          <textarea id="ttsTextInput" class="form-control mb-3" rows="3">ສະບາຍດີ, ຍິນດີຕ້ອນຮັບສູ່ລະບົບຮ້ານຄ້າ StoreMini. ຍອດເງິນ 50,000 ກີບ ຊຳລະຜ່ານ BCEL OnePay</textarea>

          <div class="tts-section-label"><i class="fas fa-magic text-primary"></i> ກົດຕົວຢ່າງເພື່ອຟັງທັນທີ</div>
          <div class="tts-samples">
            <?php foreach ($ttsSamples as [$sIcon, $sLabel, $sText]): ?>
              <button type="button" class="tts-sample" onclick="setSample(<?php echo htmlspecialchars(json_encode($sText, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>)">
                <i class="fas <?php echo $sIcon; ?>"></i><?php echo htmlspecialchars($sLabel); ?>
              </button>
            <?php endforeach; ?>
          </div>

          <button type="button" class="tts-play-btn" id="ttsPlayBtn" onclick="playVoice()">
            <i class="fas fa-play"></i> <span>ກົດເພື່ອຟັງສຽງ</span>
          </button>

          <div class="tts-now" id="ttsNow">
            <span class="tts-eq"><span></span><span></span><span></span><span></span></span>
            <span id="ttsNowText">ກຳລັງຫຼິ້ນສຽງ</span>
          </div>
          <audio id="ttsAudioPlayer" controls class="w-100" style="display: none;"></audio>
        </div>
      </div>
