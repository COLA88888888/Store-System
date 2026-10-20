<?php
/*
 * ສ່ວນ: CSS (3): ໂສມໃໝ່ຂອງຈໍລູກຄ້າ
 * ໂລໂກ້ + ຊື່ຮ້ານ ຢູ່ແຖບເທິງ, ແຜງ QR ຊຳລະຢູ່ກາງ, ຕາຕະລາງລາຍການ ແລະ ແຖບຍອດລວມ ແບບໃໝ່.
 * ໂຫຼດຫຼັງ styles_payment.php ໃນ pages/pos/customer_display.php — ທັບກົດເກົ່າ (ລຳດັບ CSS ສຳຄັນ).
 */
?>  <style>
    /* ================================================================
       ໂສມໃໝ່: ໂລໂກ້ + ຊື່ຮ້ານ ຢູ່ແຖບເທິງ, ແຜງຊ້າຍເປັນ QR ຊຳລະ, ຕາຕະລາງ ແລະ ຍອດລວມ ສະອາດຂຶ້ນ.
       ຢູ່ທ້າຍໄຟລ໌ → ທັບກົດເກົ່າຂ້າງເທິງ (ລຳດັບ CSS ສຳຄັນ).
       ================================================================ */
    .display-header {
      grid-template-columns: 1fr auto 1fr;
      padding: 10px 22px;
      min-height: 84px;
      background: linear-gradient(120deg, #1a3666 0%, #244886 45%, #2c5aa0 100%);
      background-size: 200% 200%;
      border-bottom: 3px solid #ffd45a;
    }
    .header-brand {
      grid-column: 1;
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }
    .header-logo {
      width: 64px;
      height: 64px;
      flex-shrink: 0;
      border-radius: 16px;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 5px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
      overflow: hidden;
    }
    .header-logo img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    .header-logo i { font-size: 1.9rem; color: #244886; }
    .header-brand-text { min-width: 0; }
    .header-store-name {
      color: #ffffff;
      font-size: 1.6rem;
      font-weight: 800;
      line-height: 1.15;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      text-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
    }
    .header-store-sub {
      display: flex;
      gap: 14px;
      margin-top: 3px;
      font-size: 0.86rem;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.8);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .header-store-sub i { color: #ffd45a; margin-right: 4px; }
    .header-welcome {
      grid-column: 2;
      color: #ffffff;
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 6px 20px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.22);
    }
    .header-welcome i { color: #fb7185; animation: pulseHeart 1.4s ease-in-out infinite; }
    .header-datetime {
      flex-direction: column;
      align-items: flex-end;
      gap: 0;
      line-height: 1.15;
    }
    .header-datetime .header-clock { font-size: 1.7rem; }
    .header-datetime .header-date { font-size: 0.9rem; }

    /* ແຜງຊ້າຍ: QR ຢູ່ກາງ, ບັດສີຂາວສະອາດ */
    .store-card {
      justify-content: center;
      background: #ffffff;
      border: 1px solid #dbe3f0;
      border-radius: 18px;
      box-shadow: 0 12px 32px rgba(36, 72, 134, 0.10);
    }
    .pay-qr-heading {
      font-size: 1.3rem;
      padding: 8px 18px;
      border-radius: 999px;
      background: #eef2fb;
      margin: 0 auto 16px;
      width: fit-content;
    }
    .pay-qr-card {
      border: 1px solid #dbe3f0;
      border-radius: 16px;
      box-shadow: 0 8px 22px rgba(36, 72, 134, 0.10);
    }
    .pay-qr-card .pqc-qr {
      width: clamp(170px, 19vw, 260px);
      height: clamp(170px, 19vw, 260px);
      border: 2px solid #244886;
      box-shadow: none;
    }
    .welcome-banner-box {
      background: linear-gradient(135deg, #eef2fb 0%, #ffffff 100%);
      border: 1px solid #dbe3f0;
      border-radius: 16px;
    }

    /* ຕາຕະລາງລາຍການ */
    .cart-card {
      border: 1px solid #dbe3f0;
      border-radius: 18px;
      box-shadow: 0 12px 32px rgba(36, 72, 134, 0.10);
    }
    .display-table-head th {
      background: #244886;
      color: #ffffff;
      font-size: 0.95rem;
      letter-spacing: 0.3px;
      border-bottom: none;
      padding: 12px 14px;
    }
    .display-cols-table { border-collapse: collapse; border-spacing: 0; }
    .display-table-head th { border-left: none; border-right: 1px solid rgba(255, 255, 255, 0.12); }
    .display-table-head th:last-child { border-right: none; }
    .display-table-body td { border: none; border-bottom: 1px solid #eef2f7; }
    .display-table-body tr:nth-child(even) { background: #fafbfe; }
    .display-table-body tr.display-row-newest td { background: #e0ecff; }
    .display-table-body tr.display-row-newest td:first-child { box-shadow: inset 4px 0 0 #2563eb; }
    .display-pro-img {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      object-fit: contain;
    }
    .badge-qty {
      background: #eef2fb;
      color: #244886;
      border: 1px solid #c7d2e8;
      box-shadow: none;
      border-radius: 999px;
      padding: 3px 13px;
    }

    /* ແຖບຍອດລວມ */
    .grand-total-banner {
      border-radius: 18px;
      padding: 12px 24px;
      background: linear-gradient(135deg, #047857 0%, #059669 60%, #10b981 100%);
    }
    .gt-substat {
      padding: 4px 12px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.10);
    }
    .gt-final h2 { font-size: 2.5rem; }

    @media (max-height: 720px) {
      .display-header { min-height: 66px; padding: 6px 16px; }
      .header-logo { width: 50px; height: 50px; border-radius: 12px; }
      .header-store-name { font-size: 1.3rem; }
      .header-store-sub { font-size: 0.76rem; }
      .header-welcome { font-size: 1.05rem; padding: 4px 14px; }
      .header-datetime .header-clock { font-size: 1.35rem; }
      .pay-qr-heading { font-size: 1.05rem; margin-bottom: 10px; }
      .pay-qr-card .pqc-qr { width: clamp(130px, 15vw, 180px); height: clamp(130px, 15vw, 180px); }
      .gt-final h2 { font-size: 1.9rem; }
    }
    /* QR ທະນາຄານທີ່ເລືອກຊຳລະ — ເດັ້ງກາງຈໍ ພ້ອມຈຳນວນເງິນທີ່ຕ້ອງໂອນ */
    .bank-pay-popup {
      position: fixed;
      inset: 0;
      z-index: 2500;
      background: rgba(15, 23, 42, 0.55);
      backdrop-filter: blur(3px);
      display: flex;
      align-items: center;
      justify-content: center;
      animation: bppFade 0.2s ease;
    }
    @keyframes bppFade { from { opacity: 0; } to { opacity: 1; } }
    @keyframes bppPop { from { transform: scale(0.92); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .bpp-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 24px 34px 26px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
      border-top: 6px solid #244886;
      animation: bppPop 0.25s ease;
      max-height: 92vh;
    }
    .bpp-bank {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.6rem;
      font-weight: 800;
      color: #1e293b;
    }
    .bpp-bank img {
      width: 44px;
      height: 44px;
      object-fit: contain;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      padding: 3px;
    }
    .bpp-qr {
      width: min(52vh, 420px);
      height: min(52vh, 420px);
      object-fit: contain;
      border: 3px solid #244886;
      border-radius: 18px;
      padding: 10px;
      background: #ffffff;
    }
    .bpp-amount-label { font-size: 1.05rem; font-weight: 700; color: #64748b; margin-top: 4px; }
    .bpp-amount { font-size: 2.6rem; font-weight: 900; color: #7c3aed; line-height: 1.1; }
    .bpp-hint { font-size: 1rem; font-weight: 700; color: #475569; }
    .bpp-hint i { color: #244886; margin-right: 6px; }

    /* ຈໍແຄບ: ເຊື່ອງຄຳຕ້ອນຮັບ ໃຫ້ຊື່ຮ້ານມີບ່ອນ */
    @media (max-width: 1100px) {
      .header-welcome { display: none; }
      .display-header { grid-template-columns: 1fr auto; }
      .header-datetime { grid-column: 2; }
    }
  </style>
