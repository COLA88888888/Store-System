<!-- ============================================================
     print_barcode_footer.php - ແຖບ Action Bar ດ້ານລຸ່ມ ແລະ Iframe ສັ່ງພິມ
     ============================================================ -->
<!-- Sticky Action Bar ດ້ານລຸ່ມ ສະແດງຈຳນວນເລືອກ ແລະ ປຸ່ມສັ່ງພິມ -->
<div class="sticky-print-bar p-3 no-print">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div id="pageBarcodeSummary" class="font-weight-bold text-dark" style="font-size: 1.05rem;">
      <i class="fas fa-info-circle text-primary mr-1"></i> <?php echo htmlspecialchars(t('print_barcode.footer_summary_initial', 'ເລືອກແລ້ວ: 0 ລາຍການ (ລວມ 0 ດວງ)')); ?>
    </div>

    <div class="ml-auto mr-3 d-none align-items-center" id="bcBtTargetWrap">
      <i class="fab fa-bluetooth-b text-primary mr-2"></i>
      <select id="bcBtTarget" class="form-control form-control-sm" style="min-width: 260px;"></select>
    </div>

    <button type="button" class="btn btn-primary font-weight-bold px-4 py-2 text-white shadow" style="border-radius: 8px; font-size: 1rem; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="executePageBarcodePrint()">
      <i class="fas fa-print mr-2"></i> <?php echo htmlspecialchars(t('print_barcode.print_button', 'ພິມບາໂຄ້ດ')); ?>
    </button>
  </div>
</div>

<!-- Hidden Iframe ສຳລັບສົ່ງຄຳສັ່ງພິມບາໂຄ້ດ -->
<iframe id="pageBarcodePrintIframe" style="display: none; width: 0; height: 0; border: none;"></iframe>