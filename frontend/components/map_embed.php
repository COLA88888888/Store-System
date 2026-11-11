<?php
/**
 * ແຜນທີ່ Google ພ້ອມກ່ອງສຳຮອງ — ໃຊ້ໃນ layouts/footer.php ແລະ pages/contact.php
 * ຕ້ອງການ: $mapSrc (URL embed), $mapHref (ລິ້ງເປີດ Google Maps), $mapTitle (ທີ່ຢູ່), $mapHeight (px).
 * iframe ຂອງ Google ບາງເທື່ອໂຫຼດຊ້າ ຫຼື ບໍ່ຂຶ້ນ (ເນັດຊ້າ, ຖືກບລັອກ) → ເມື່ອກ່ອນເປັນກ່ອງເປົ່າ.
 * ດຽວນີ້: ກ່ອງສຳຮອງ (ໝຸດ + ປຸ່ມເປີດ Google Maps) ຢູ່ລຸ່ມ; iframe ໂປ່ງໃສຈົນໂຫຼດແລ້ວ (onload → .is-loaded).
 * CSS: .map-embed ໃນ assets/css/components.css
 */
?>
<div class="map-embed" style="height: <?php echo (int)$mapHeight; ?>px">
    <a class="map-embed-fallback" href="<?php echo e($mapHref); ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
        <span><?php echo te('ເປີດແຜນທີ່ໃນ Google Maps'); ?></span>
    </a>
    <iframe src="<?php echo e($mapSrc); ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            title="<?php echo e($mapTitle); ?>" onload="this.classList.add('is-loaded')"></iframe>
</div>
