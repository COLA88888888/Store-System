<?php
/**
 * ສ່ວນ: ວັດຈະນານຸກົມການອອກສຽງ (Lexicon) — ສອນໃຫ້ລະບົບສຽງອ່ານຄຳສັບສະເພາະໃຫ້ຖືກ (api/tts_lexicon.php)
 * ມາຈາກ pages/settings/tts/index.php. ລາຍການຄຳແນະນຳ ສ້າງໂດຍ js.php (renderPresets)
 */
?>
      <div class="tts-card">
        <div class="tts-card-head">
          <h6 class="tts-card-title">
            <span class="ico" style="background:#f0fdf4; color:#16a34a;"><i class="fas fa-book"></i></span>
            ວັດຈະນານຸກົມການອອກສຽງ
          </h6>
          <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" style="border-radius: 8px;" onclick="addAllPresets()">
            <i class="fas fa-check-double mr-1"></i> ເພີ່ມຄຳແນະນຳທັງໝົດ
          </button>
        </div>
        <div class="tts-card-body">
          <p class="text-muted small mb-3">ສອນໃຫ້ລະບົບອ່ານຄຳສັບສະເພາະໃຫ້ຖືກ ເຊັ່ນ BCEL → ບີຊີອີແອວ, OnePay → ວັນເພ, QR → ຄິວອາ</p>
          <div class="tts-lex-row">
            <input type="text" id="lexWord" class="form-control" placeholder="ຄຳສັບ ເຊັ່ນ BCEL" onkeydown="if (event.key === 'Enter') { event.preventDefault(); submitLexicon(); }">
            <input type="text" id="lexPron" class="form-control" placeholder="ຄຳອ່ານລາວ ເຊັ່ນ ບີຊີອີແອວ" onkeydown="if (event.key === 'Enter') { event.preventDefault(); submitLexicon(); }">
            <button type="button" class="btn btn-success" onclick="submitLexicon()" id="lexAddBtn">
              <i class="fas fa-plus mr-1"></i> ເພີ່ມ
            </button>
          </div>
          <div class="tts-section-label mb-2"><i class="fas fa-lightbulb text-warning"></i> ຄຳສັບແນະນຳ</div>
          <div class="tts-lex-list" id="presetTableBody"></div>
        </div>
      </div>
