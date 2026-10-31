<?php
/**
 * ສ່ວນ: ຟອມຕັ້ງຄ່າລະບົບສຽງ — ເປີດ/ປິດສຽງ, ເລືອກລະບົບສຽງ (Edge / SiangMuan), API Key, ສຽງເວົ້າ ແລະ ຈຸດທີ່ໃຫ້ເວົ້າອັດຕະໂນມັດ
 * ມາຈາກ pages/settings/tts/index.php. ຊື່ input ຄືເກົ່າທັງໝົດ (POST save_tts_settings ຢູ່ index.php)
 */
$ttsTriggers = [
    ['pos_total',   'fa-cash-register', '#2563eb', '#eff6ff', 'ເປີດໜ້າຈ່າຍເງິນ (POS)',       'ຍອດລວມທີ່ຕ້ອງຊຳລະ 85,000 ກີບ'],
    ['pos_sale',    'fa-check-circle',  '#16a34a', '#f0fdf4', 'ຄິດໄລ່ເງິນ / ຂາຍສຳເລັດ (POS)', 'ໄດ້ຮັບເງິນສົດ 100,000 ກີບ, ເງິນທອນ 15,000 ກີບ, ຂອບໃຈທີ່ໃຊ້ບໍລິການ'],
    ['pos_stock',   'fa-box-open',      '#dc2626', '#fef2f2', 'ສະແກນສິນຄ້າໝົດ ຫຼື ໃກ້ໝົດ',   'ເປັບຊີ 330ml, ສິນຄ້າໝົດແລ້ວ'],
    ['pos_queue',   'fa-users',         '#7c3aed', '#f5f3ff', 'ເອີ້ນຄິວ (Queue System)',        'ຂໍເຊີນຄິວເລກທີ 08 ທີ່ຊ່ອງບໍລິການ'],
    ['pos_welcome', 'fa-hand-sparkles', '#d97706', '#fffbeb', 'ຕ້ອນຮັບລູກຄ້າ (ຈໍລູກຄ້າ)',      'ສະບາຍດີ, ຍິນດີຕ້ອນຮັບ'],
];
?>
      <form method="post" action="" id="ttsSettingsForm">
        <div class="tts-card">
          <div class="tts-card-head">
            <h6 class="tts-card-title">
              <span class="ico" style="background:#eff6ff; color:#2563eb;"><i class="fas fa-sliders-h"></i></span>
              ເຄື່ອງຈັກສຽງເວົ້າ
            </h6>
            <label class="tts-switch" for="tts_enabled">
              <input type="checkbox" id="tts_enabled" name="tts_enabled" value="1" <?php echo $ttsEnabled ? 'checked' : ''; ?> onchange="ttsPaintEnabled()">
              <span class="track"></span>
              <span id="ttsEnabledLabel"><?php echo $ttsEnabled ? 'ເປີດໃຊ້ງານສຽງ' : 'ປິດສຽງຢູ່'; ?></span>
            </label>
          </div>

          <div class="tts-card-body">
            <!-- 1. ເລືອກລະບົບສຽງ -->
            <div class="tts-section-label"><i class="fas fa-microchip text-primary"></i> ລະບົບສຽງເວົ້າ</div>
            <div class="row mb-3" style="row-gap: 12px;">
              <div class="col-md-6">
                <label class="tts-provider is-edge <?php echo $currentProvider === 'edge' ? 'is-active' : ''; ?>" id="cardEdge" for="provEdge">
                  <input type="radio" id="provEdge" name="provider" value="edge" <?php echo $currentProvider === 'edge' ? 'checked' : ''; ?> onchange="selectProvider('edge')">
                  <span class="check"><i class="fas fa-check"></i></span>
                  <div class="name"><i class="fab fa-microsoft text-success"></i> Microsoft Edge Neural</div>
                  <span class="tag" style="background:#dcfce7; color:#15803d;">⭐ ແນະນຳ · ຟຣີ 100%</span>
                  <ul>
                    <li><i class="fas fa-infinity text-success"></i> ບໍ່ຈຳກັດຕົວອັກສອນ, ບໍ່ຕິດໂຄຕ້າ</li>
                    <li><i class="fas fa-key text-success"></i> ບໍ່ຕ້ອງໃຊ້ API Key</li>
                    <li><i class="fas fa-wave-square text-success"></i> ສຽງ AI ທຳມະຊາດ ຄົມຊັດ</li>
                  </ul>
                </label>
              </div>
              <div class="col-md-6">
                <label class="tts-provider <?php echo $currentProvider === 'siangmuan' ? 'is-active' : ''; ?>" id="cardSiangmuan" for="provSiangmuan">
                  <input type="radio" id="provSiangmuan" name="provider" value="siangmuan" <?php echo $currentProvider === 'siangmuan' ? 'checked' : ''; ?> onchange="selectProvider('siangmuan')">
                  <span class="check"><i class="fas fa-check"></i></span>
                  <div class="name"><i class="fas fa-cloud text-primary"></i> SiangMuan AI</div>
                  <span class="tag" style="background:#e0f2fe; color:#0369a1;">ຕ້ອງມີ API Key</span>
                  <ul>
                    <li><i class="fas fa-coins text-warning"></i> ໂຄຕ້າຟຣີ 1,500 ຕົວອັກສອນ</li>
                    <li><i class="fas fa-shield-alt text-success"></i> ໃຊ້ Edge ແທນເອງ ເມື່ອໃຊ້ບໍ່ໄດ້</li>
                    <li><i class="fas fa-external-link-alt text-info"></i> <a href="https://siangmuan.com" target="_blank" rel="noopener" onclick="event.stopPropagation();">siangmuan.com</a></li>
                  </ul>
                </label>
              </div>
            </div>

            <!-- 2. API Key (SiangMuan ເທົ່ານັ້ນ) -->
            <div id="apiKeyGroup" class="mb-3" style="<?php echo $currentProvider === 'edge' ? 'display: none;' : ''; ?>">
              <div class="tts-section-label"><i class="fas fa-key text-warning"></i> API Key (SiangMuan)</div>
              <div class="input-group">
                <input type="password" id="apiKeyInput" name="api_key" class="form-control" placeholder="ວາງ API Key ຂອງທ່ານຢູ່ນີ້..." value="<?php echo htmlspecialchars($config['api_key'] ?? ''); ?>" autocomplete="off">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary" onclick="toggleApiKeyVisibility()" title="ສະແດງ / ເຊື່ອງ">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                  </button>
                </div>
              </div>
              <small class="text-muted">ຄັດລອກ API Key ຈາກໂປຣໄຟລ໌ຂອງທ່ານໃນ siangmuan.com</small>
            </div>

            <!-- 3. ສຽງເວົ້າ -->
            <div class="mb-4">
              <div class="tts-section-label"><i class="fas fa-user-circle text-info"></i> ສຽງເວົ້າເລີ່ມຕົ້ນ</div>
              <div class="input-group">
                <select id="ttsVoiceSelect" name="voice" class="form-control"></select>
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-info" onclick="loadVoicesList(true)" title="ໂຫຼດລາຍຊື່ສຽງ">
                    <i class="fas fa-sync-alt" id="voiceReloadIcon"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- 4. ຈຸດທີ່ໃຫ້ເວົ້າອັດຕະໂນມັດ -->
            <div class="tts-section-label"><i class="fas fa-bell text-warning"></i> ໃຫ້ລະບົບເວົ້າອັດຕະໂນມັດເມື່ອ</div>
            <?php foreach ($ttsTriggers as [$tKey, $tIcon, $tColor, $tBg, $tTitle, $tSay]): ?>
              <label class="tts-trigger" for="<?php echo $tKey; ?>">
                <span class="t-ico" style="background: <?php echo $tBg; ?>; color: <?php echo $tColor; ?>;"><i class="fas <?php echo $tIcon; ?>"></i></span>
                <span class="t-body">
                  <span class="t-title d-block"><?php echo htmlspecialchars($tTitle); ?></span>
                  <span class="t-say d-block"><i class="fas fa-quote-left"></i><?php echo htmlspecialchars($tSay); ?></span>
                </span>
                <span class="tts-switch">
                  <input type="checkbox" id="<?php echo $tKey; ?>" name="<?php echo $tKey; ?>" value="1" <?php echo !empty($config[$tKey]) ? 'checked' : ''; ?>>
                  <span class="track"></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="tts-save-bar">
            <button type="submit" name="save_tts_settings" value="1" class="btn tts-btn-primary">
              <i class="fas fa-save mr-1"></i> ບັນທຶກການຕັ້ງຄ່າ
            </button>
          </div>
        </div>
      </form>
