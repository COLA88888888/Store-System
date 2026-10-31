<?php
/**
 * ສ່ວນ: ຫົວໜ້າຕັ້ງຄ່າລະບົບສຽງ — ຊື່ໜ້າ, ສະຖານະເປີດ/ປິດ, ລະບົບສຽງທີ່ໃຊ້ຢູ່ ແລະ ໂຄຕ້າ SiangMuan
 * ມາຈາກ pages/settings/tts/index.php (ໃຊ້ $ttsEnabled, $currentProvider, $quotaRemaining, $quotaInfo)
 */
?>
  <div class="tts-hero">
    <div class="d-flex align-items-center flex-wrap" style="gap: 16px; position: relative; z-index: 1;">
      <div class="tts-hero-icon"><i class="fas fa-volume-up"></i></div>
      <div class="flex-grow-1" style="min-width: 220px;">
        <h5>ຕັ້ງຄ່າລະບົບສຽງເວົ້າພາສາລາວ</h5>
        <p>ສຽງ AI ພາສາລາວ ສຳລັບໜ້າຂາຍ (POS), ຈໍລູກຄ້າ ແລະ ລະບົບຄິວ</p>
      </div>
      <div class="d-flex flex-wrap" style="gap: 8px;">
        <span class="tts-chip <?php echo $ttsEnabled ? '' : 'is-off'; ?>" id="heroStatusChip">
          <span class="dot"></span><span id="heroStatusText"><?php echo $ttsEnabled ? 'ເປີດໃຊ້ງານສຽງ' : 'ປິດສຽງຢູ່'; ?></span>
        </span>
        <span class="tts-chip" id="heroEngineChip">
          <?php if ($currentProvider === 'edge'): ?>
            <i class="fab fa-microsoft"></i> Edge Neural · ຟຣີ ບໍ່ຈຳກັດ
          <?php else: ?>
            <i class="fas fa-cloud"></i> SiangMuan AI
          <?php endif; ?>
        </span>
        <?php if ($currentProvider !== 'edge' && $quotaRemaining !== null): ?>
          <span class="tts-chip" title="ອັບເດດຫຼ້າສຸດ: <?php echo htmlspecialchars($quotaInfo['updated_at'] ?? ''); ?>">
            <i class="fas fa-tachometer-alt"></i> ໂຄຕ້າເຫຼືອ <?php echo number_format($quotaRemaining); ?> / 1,500
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
