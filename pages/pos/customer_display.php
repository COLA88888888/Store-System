<?php
// ============================================================
// customer_display.php - DUAL-SCREEN POS CUSTOMER DISPLAY
// ໜ້າຈໍສະແດງຜົນລູກຄ້າ Real-Time (POS System Primary Blue Theme)
// ============================================================
require_once __DIR__ . '/../../config/db.php';

// Store Info Retrieval directly from Database
$company = [
    'com_name'    => '',
    'com_tel'     => '',
    'com_address' => '',
    'com_logo'    => ''
];

try {
    if (isset($pdo)) {
        $cinfo = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo));
        if ($cinfo) {
            $company['com_name']    = !empty($cinfo['com_name_la']) ? $cinfo['com_name_la'] : 'POS Retail Store';
            $company['com_tel']     = $cinfo['com_tel'] ?? '';
            $company['com_address'] = $cinfo['com_address'] ?? '';
            $company['qr_img']      = !empty($cinfo['qr_img']) ? ('../../assets/img/qr/' . $cinfo['qr_img']) : '';
            if (!empty($cinfo['img_url'])) {
                $company['com_logo'] = '../../assets/img/logo/' . $cinfo['img_url'];
            }
        } else {
            $company['com_name'] = 'POS Retail Store';
        }
    }
} catch (Throwable $e) {}

// Active bank accounts — shown as payment QR codes on the customer screen (replaces the
// old "thank you" card). Only the bank name + QR image are shown; account name / number
// are deliberately kept off the public-facing screen.
$cd_bank_accounts = [];
try {
    if (isset($pdo)) {
        // This branch's own banks, plus any shared across every branch (store_id = 0).
        $bStmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC");
        $bStmt->execute([getActiveStoreId($pdo)]);
        if ($bStmt) {
            foreach ($bStmt->fetchAll(PDO::FETCH_ASSOC) as $ba) {
                $qr = resolveBankQr($ba['qr_code_img'] ?? '', $ba['bank_code'] ?? '');
                if ($qr === '') continue; // no QR uploaded -> nothing to scan, skip
                $cd_bank_accounts[] = [
                    'bank_name' => trim($ba['bank_name'] ?? '') ?: strtoupper(trim($ba['bank_code'] ?? '')),
                    'logo'      => resolveBankLogo($ba['bank_logo'] ?? '', $ba['bank_code'] ?? ''),
                    'qr'        => $qr,
                ];
            }
        }
    }
} catch (Throwable $e) {}

// Fall back to the single company-wide QR if no per-bank QR is configured.
if (empty($cd_bank_accounts) && !empty($company['qr_img'])) {
    $cd_bank_accounts[] = [
        'bank_name' => $company['com_name'],
        'logo'      => '',
        'qr'        => $company['qr_img'],
    ];
}

$base_path = '../../';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Customer Display - <?php echo htmlspecialchars($company['com_name']); ?></title>

  <link rel="shortcut icon" href="<?php echo htmlspecialchars($company['com_logo']); ?>" type="image/x-icon">
  <link rel="icon" href="<?php echo htmlspecialchars($company['com_logo']); ?>" type="image/png">

  <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/main.min.css">
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom.css">

  <style>
    @keyframes pulseHeart {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.18); }
    }
    @keyframes glowTotal {
      0%, 100% { box-shadow: 0 6px 20px rgba(5, 150, 105, 0.30); }
      50% { box-shadow: 0 8px 32px rgba(5, 150, 105, 0.55); }
    }
    @keyframes headerShine {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      background: #eef3fb;
      color: #0f172a;
      font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;
      overflow: hidden;
      user-select: none;
    }

    .display-wrapper {
      display: flex;
      flex-direction: column;
      height: 100vh;
      background: radial-gradient(circle at 15% 10%, #e3ecfb 0%, #eef3fb 45%, #e8eef9 100%);
    }

    /* ===== 1. TOP HEADER BAR: SYSTEM BRAND COLOR, SINGLE ROW ===== */
    .display-header {
      background: linear-gradient(120deg, #2c5aa0 0%, #244886 50%, #1a3666 100%);
      background-size: 200% 200%;
      animation: headerShine 8s ease infinite;
      padding: 8px 24px;
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      box-shadow: 0 4px 16px rgba(36, 72, 134, 0.35);
      z-index: 100;
    }
    .header-left-spacer {
      grid-column: 1;
    }

    .fullscreen-toggle-btn {
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 200;
      background: rgba(255,255,255,0.18);
      border: 1.5px solid rgba(255,255,255,0.4);
      color: #ffffff;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      backdrop-filter: blur(6px);
      transition: background 0.15s ease, transform 0.15s ease;
    }
    .fullscreen-toggle-btn:hover {
      background: rgba(255,255,255,0.32);
      transform: scale(1.08);
    }
    .header-system-title {
      grid-column: 2;
      color: #ffffff;
      font-size: 1.9rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      text-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    .header-datetime {
      grid-column: 3;
      justify-self: end;
      margin-right: 42px;
      display: flex;
      align-items: baseline;
      gap: 10px;
      color: #ffffff;
    }
    .header-datetime .header-clock {
      font-size: 1.2rem;
      font-weight: 800;
      letter-spacing: 0.5px;
      color: #ffd45a;
    }
    .header-datetime .header-date {
      font-size: 0.92rem;
      font-weight: 600;
      color: rgba(255,255,255,0.75);
    }

    /* ===== MAIN DUAL PANEL (Left = Large Logo & Store Info, Right = Order List & Payment) ===== */
    .display-main {
      flex: 1;
      display: flex;
      overflow: hidden;
      padding: 16px;
      gap: 16px;
    }

    /* ===== LEFT PANEL: LARGE STORE LOGO & STORE INFO (40%) ===== */
    .left-store-panel {
      flex: 0.85;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    /* Give the left panel more width when 3 payment QR cards sit side by side */
    .left-store-panel.has-3-qr {
      flex: 1.15;
    }
    .store-card {
      flex: 1;
      background: linear-gradient(135deg, #eef2fb 0%, #ffffff 50%, #eef2fb 100%);
      border-radius: 14px;
      border: 2px solid #cbd5e1;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: center;
      text-align: center;
      padding: 28px 22px;
      box-shadow: 0 10px 28px rgba(2, 99, 255, 0.08);
      position: relative;
      z-index: 0;
      overflow: hidden;   /* customer-facing screen: never show a scrollbar here */
      scrollbar-width: none !important;
    }
    /* Defense in case any descendant still scrolls — kill the bar (beats global-custom.css `::-webkit-scrollbar{width:5px!important}`) */
    .store-card::-webkit-scrollbar,
    .store-card *::-webkit-scrollbar { width: 0 !important; height: 0 !important; display: none !important; }
    .store-card * { scrollbar-width: none !important; }
    .particle-canvas {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      pointer-events: none;
    }
    .store-card-content {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      margin: 0;   /* top-aligned, not vertically centred */
    }

    .large-logo-box {
      width: 220px;
      height: 220px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
    }
    .large-logo-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      filter: drop-shadow(0 6px 12px rgba(0,0,0,0.12));
    }
    .large-fallback-icon {
      font-size: 5.5rem;
      color: #0263ff;
    }

    .store-name-title {
      font-size: 2.2rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 8px;
      line-height: 1.2;
    }
    .store-info-text {
      font-size: 1.1rem;
      color: #475569;
      font-weight: 600;
      max-width: 100%;
      margin-bottom: 16px;
      line-height: 1.5;
    }
    /* Address / phone each stay on a single long line instead of wrapping narrow */
    .store-info-text > div {
      white-space: nowrap;
    }

    .welcome-banner-box {
      background: linear-gradient(135deg, #eef2fb 0%, #dbe3f3 100%);
      border: 2px solid #c7d2e8;
      border-radius: 12px;
      padding: 20px 24px;
      width: 92%;
      margin-top: 15px;
      box-shadow: 0 6px 20px rgba(36, 72, 134, 0.08);
      position: relative;
      z-index: 1;
    }
    .welcome-banner-box i.fa-heart {
      display: inline-block;
      animation: pulseHeart 1.4s ease-in-out infinite;
    }

    /* ===== STORE INFO ABOVE THE LOGO ===== */
    .store-info-block {
      margin-bottom: 14px;
    }
    .store-info-block .store-name-title { margin-bottom: 6px; }
    .store-info-block .store-info-text { margin-bottom: 0; }
    /* Logo shrinks once it sits between the info and the QR cards */
    .large-logo-box.logo-below {
      width: 150px;
      height: 150px;
      margin: 2px 0 10px;
    }

    /* ===== HEADER ROW: LOGO ON THE LEFT, NAME/ADDRESS/TEL ON THE RIGHT ===== */
    .store-header-row {
      display: flex;
      align-items: center;
      gap: 20px;
      width: 100%;
      margin-bottom: 16px;
      text-align: left;
    }
    .store-header-row .large-logo-box {
      width: 100px;
      height: 100px;
      margin: 0;
      flex-shrink: 0;
    }
    .store-header-row .store-info-block {
      margin-bottom: 0;
      flex: 1;
      min-width: 0;
      text-align: left;
    }
    .store-header-row .store-name-title,
    .store-header-row .store-info-text {
      text-align: left;
    }

    /* ===== PAYMENT QR CODES (replaces the old "thank you" card) ===== */
    .pay-qr-heading {
      font-size: 1.15rem;
      font-weight: 800;
      color: #244886;
      margin: 6px 0 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .pay-qr-wrap {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;   /* a single card ends up centred automatically */
      align-items: stretch;
      gap: 16px;
      width: 100%;
    }
    .pay-qr-card {
      background: #ffffff;
      border: 2px solid #c7d2e8;
      border-radius: 14px;
      padding: 14px 16px 16px;
      box-shadow: 0 6px 20px rgba(36, 72, 134, 0.10);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      flex: 0 1 auto;
    }
    .pay-qr-card .pqc-bank {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1.05rem;
      font-weight: 800;
      color: #1e293b;
    }
    .pay-qr-card .pqc-bank img {
      width: 28px;
      height: 28px;
      object-fit: contain;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      background: #fff;
      padding: 2px;
    }
    .pay-qr-card .pqc-qr {
      width: clamp(140px, 15vw, 200px);
      height: clamp(140px, 15vw, 200px);
      object-fit: contain;
      background: #ffffff;
      border: 2.5px solid #6366f1;
      border-radius: 12px;
      padding: 6px;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.18);
    }
    /* 3 cards in one row: split the panel width evenly and shrink the pieces so the
       bank name + QR stay fully on screen. */
    .pay-qr-wrap.count-3 {
      flex-wrap: nowrap;
      gap: 10px;
      align-items: flex-start;
    }
    .pay-qr-wrap.count-3 .pay-qr-card {
      flex: 1 1 0;
      min-width: 0;
      padding: 10px 8px 12px;
      gap: 6px;
    }
    .pay-qr-wrap.count-3 .pqc-bank {
      font-size: 0.9rem;
      max-width: 100%;
    }
    .pay-qr-wrap.count-3 .pqc-bank span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .pay-qr-wrap.count-3 .pqc-bank img { width: 22px; height: 22px; }
    .pay-qr-wrap.count-3 .pqc-qr {
      width: 100%;
      height: auto;
      max-width: 190px;
      aspect-ratio: 1 / 1;
      padding: 4px;
    }

    /* ===== RIGHT PANEL: ORDER LIST & PAYMENT SUMMARY (60%) ===== */
    .right-order-panel {
      flex: 1.15;
      display: flex;
      flex-direction: column;
      gap: 8px;
      overflow: hidden;
    }

    .cart-card {
      flex: 1;
      background: #ffffff;
      border-radius: 8px;
      border: 2px solid #cbd5e1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    }

    /* Header and body are separate <table>s so columns stay aligned via a shared
       colgroup + fixed layout instead of each table auto-sizing its own columns. */
    .display-cols-table {
      table-layout: fixed;
    }

    /* Plain header — same style as the rest of the app's tables (e.g. Settings > Printers). */
    .display-table-head {
      color: #475569;
      font-weight: 700;
      font-size: 0.88rem;
    }
    .display-table-head th {
      background: #f8fafc;
      padding: 10px 14px;
      border: none;
      border-bottom: 2px solid #e2e8f0;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .display-table-body {
      position: relative;   /* anchors the empty-state overlay below */
      overflow-y: auto;
      flex: 1;
      scrollbar-width: none;
    }
    .display-table-body::-webkit-scrollbar {
      display: none;
    }
    /* Cart empty: same technique the POS cart panel itself uses (pos_cart_js.php) — a plain
       flex-centred div, not a table row/cell, so it centres reliably regardless of table
       height quirks. Sits over the table body, below the sticky header. */
    .display-cart-empty {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      gap: 10px;
      padding: 20px;
    }
    .display-cart-empty i {
      font-size: 3rem;
      color: #94a3b8;
      opacity: 0.4;
    }
    .display-cart-empty h4 {
      font-weight: 700;
      color: #64748b;
      margin: 0;
    }
    .display-cart-empty p {
      color: #94a3b8;
      margin: 0;
    }
    .display-table-body td {
      padding: 9px 14px;
      vertical-align: middle;
      font-size: 1.05rem;
      color: #0f172a;
      height: 62px;
      box-sizing: border-box;
    }
    .display-table-body tr:nth-child(even) {
      background: #f8fafc;
    }
    /* Latest scanned/tapped item is rendered first and highlighted so the customer
       can immediately spot what the cashier just added. */
    .display-table-body tr.display-row-newest td {
      background: #dbeafe;
      animation: newRowIn 0.35s ease;
    }
    @keyframes newRowIn {
      from { opacity: 0; transform: translateY(-6px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ENLARGED PRODUCT IMAGE & DETAILS */
    .display-pro-img {
      width: 46px;
      height: 46px;
      object-fit: cover;
      border-radius: 6px;
      border: 2px solid #cbd5e1;
      background: #ffffff;
      padding: 2px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .badge-qty {
      background: #0263ff;
      color: #ffffff;
      font-size: 0.95rem;
      padding: 3px 11px;
      border-radius: 7px;
      font-weight: 800;
      box-shadow: 0 2px 6px rgba(2, 99, 255, 0.3);
    }

    /* GREEN GRAND-TOTAL BOX — full-width bar below both panels, everything in ONE row:
       every payment stat, then the grand total at the end. */
    .grand-total-banner {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      border-radius: 10px;
      padding: 10px 22px;
      margin: 0 16px 12px;
      box-sizing: border-box;
      color: #ffffff;
      box-shadow: 0 6px 18px rgba(5, 150, 105, 0.30);
      animation: glowTotal 2.4s ease-in-out infinite;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }
    .gt-substats {
      display: flex;
      align-items: center;
      justify-content: space-evenly;
      gap: 18px;
      flex: 1;
      min-width: 0;
    }
    .gt-substat {
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }
    .gt-substat-label {
      font-size: 0.92rem;
      font-weight: 700;
      color: #d1fae5;
      display: flex;
      align-items: center;
      gap: 5px;
      white-space: nowrap;
    }
    .gt-substat-value {
      font-size: 1.4rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.15;
      white-space: nowrap;
    }
    #displayItemCount {
      font-size: 1.25rem;
    }
    .gt-final {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      text-align: right;
      flex-shrink: 0;
      padding-left: 22px;
      border-left: 2px solid rgba(255,255,255,0.3);
    }
    .gt-final-label {
      text-transform: uppercase;
      color: #dcfce7;
      letter-spacing: 0.5px;
      font-size: 0.85rem;
      font-weight: 700;
      white-space: nowrap;
    }
    .gt-final h2 {
      font-weight: 800;
      margin: 2px 0 0;
      font-size: 2.1rem;
      color: #ffffff;
      font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;
      text-shadow: 0 2px 5px rgba(0,0,0,0.25);
      white-space: nowrap;
    }

    /* Short customer-display monitors (e.g. 1024x600): tighten the green summary box
       so the product list keeps enough visible rows instead of being squeezed. */
    @media (max-height: 720px) {
      .display-main { padding: 10px; gap: 10px; }
      .grand-total-banner { padding: 8px 16px; gap: 10px; }
      .gt-substats { gap: 12px; }
      .gt-substat-label { font-size: 0.8rem; }
      .gt-substat-value { font-size: 1.15rem; }
      #displayItemCount { font-size: 1.02rem; }
      .gt-final { padding-left: 14px; }
      .gt-final-label { font-size: 0.72rem; }
      .gt-final h2 { font-size: 1.7rem; }
      .display-table-body td { height: 52px; font-size: 0.98rem; padding: 6px 12px; }
      .display-table-head th { padding: 7px 12px; font-size: 0.9rem; }

      .store-card { padding: 18px 16px; }
      .store-name-title { font-size: 1.7rem; }
      .store-info-text { font-size: 0.95rem; }
      .store-info-block { margin-bottom: 10px; }
      .large-logo-box.logo-below { width: 110px; height: 110px; margin: 2px 0 8px; }
      .pay-qr-heading { font-size: 1rem; margin: 4px 0 8px; }
      .pay-qr-wrap { gap: 10px; }
      .pay-qr-card { padding: 10px 12px 12px; gap: 5px; border-radius: 12px; }
      .pay-qr-card .pqc-qr { width: clamp(110px, 12vw, 150px); height: clamp(110px, 12vw, 150px); }
      .pay-qr-card .pqc-bank { font-size: 0.92rem; }
      .pay-qr-wrap.count-3 .pqc-qr { max-width: 150px; height: auto; }
    }
  </style>
</head>
<body>

<div class="display-wrapper">
  <button type="button" class="fullscreen-toggle-btn" id="fullscreenToggleBtn" title="ເຕັມຈໍ / ອອກຈາກເຕັມຈໍ">
    <i class="fas fa-expand" id="fullscreenToggleIcon"></i>
  </button>

  <!-- Queue-number flash (coffee-shop style) — shown briefly on payment success -->
  <div id="queueFlash" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(15,23,42,0.94);
       flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;">
    <div style="font-size:2rem;font-weight:700;letter-spacing:2px;color:#93c5fd;">ບັດຄິວທີ</div>
    <div id="queueFlashNo" style="font-size:26vh;font-weight:900;line-height:1;text-shadow:0 8px 30px rgba(0,0,0,0.5);">0</div>
    <div style="font-size:1.6rem;font-weight:700;color:#cbd5e1;margin-top:10px;">ຂອບໃຈທີ່ມາອຸດໜູນ</div>
  </div>

  <!-- 1. TOP HEADER BAR: SYSTEM NAME (CENTERED) + TIME/DATE, SINGLE ROW, NO BOXES -->
  <div class="display-header">
    <div class="header-left-spacer"></div>

    <div class="header-system-title">
      <i class="fas fa-desktop text-warning"></i>
      <span>Wlaodev POS</span>
    </div>

    <div class="header-datetime">
      <span class="header-clock" id="liveClock">00:00:00</span>
      <span class="header-date" id="liveDate">--/--/----</span>
    </div>
  </div>

  <!-- MAIN DUAL PANEL -->
  <div class="display-main">

    <!-- LEFT PANEL: LARGE STORE LOGO & ADDRESS INFO (40%) -->
    <div class="left-store-panel<?php echo count($cd_bank_accounts) >= 3 ? ' has-3-qr' : ''; ?>">
      <div class="store-card">
        <canvas class="particle-canvas" id="storeCardParticles"></canvas>
        <div class="store-card-content">
          <!-- Logo on the left, store name/address/tel on the right -->
          <div class="store-header-row">
            <div class="large-logo-box">
              <img src="<?php echo htmlspecialchars($company['com_logo']); ?>" alt="Store Logo" class="large-logo-img"
                   onerror="this.style.display='none'; document.getElementById('largeFallbackIcon').style.display='block';">
              <i class="fas fa-store large-fallback-icon" id="largeFallbackIcon" style="display:none;"></i>
            </div>
            <div class="store-info-block">
              <div class="store-name-title"><?php echo htmlspecialchars($company['com_name']); ?></div>
              <div class="store-info-text">
                <?php if(!empty($company['com_address'])): ?>
                  <div class="mb-1"><i class="fas fa-map-marker-alt text-danger mr-2"></i> <?php echo htmlspecialchars($company['com_address']); ?></div>
                <?php endif; ?>
                <?php if(!empty($company['com_tel'])): ?>
                  <div><i class="fas fa-phone-alt text-primary mr-2"></i> ເບີໂທ: <strong><?php echo htmlspecialchars($company['com_tel']); ?></strong></div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if (!empty($cd_bank_accounts)): ?>
            <!-- Payment QR codes (per bank account). One card -> centred; 2-3 -> in a row. -->
            <div class="pay-qr-heading">
              <i class="fas fa-qrcode"></i> <span>ສະແກນ QR ເພື່ອຊຳລະເງິນ</span>
            </div>
            <div class="pay-qr-wrap count-<?php echo count($cd_bank_accounts); ?>">
              <?php foreach ($cd_bank_accounts as $ba): ?>
                <div class="pay-qr-card">
                  <div class="pqc-bank">
                    <?php if (!empty($ba['logo'])): ?>
                      <img src="<?php echo htmlspecialchars($ba['logo']); ?>" alt="" onerror="this.style.display='none'">
                    <?php else: ?>
                      <i class="fas fa-university text-primary"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($ba['bank_name']); ?></span>
                  </div>
                  <img class="pqc-qr" src="<?php echo htmlspecialchars($ba['qr']); ?>" alt="QR"
                       onerror="this.style.display='none'">
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="welcome-banner-box">
              <i class="fas fa-heart text-danger fa-2x mb-2"></i>
              <h4 class="font-weight-bold text-dark mb-1" style="font-size: 1.35rem;">ຂໍຂອບໃຈທີ່ມາອຸດໜູນ</h4>
              <p class="text-secondary mb-0" style="font-size: 1.02rem; font-weight: 600;">ຍິນດີຕ້ອນຮັບ! ຂໍໃຫ້ທ່ານມີຄວາມສຸກໃນການຊື້ສິນຄ້າ</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL: CUSTOMER ORDER LIST & COMPLETE PAYMENT DETAILS (60%) -->
    <div class="right-order-panel">
      <!-- Order Items Table -->
      <div class="cart-card">
        <div class="display-table-body">
          <table class="w-100 border-0 mb-0 display-cols-table">
            <colgroup>
              <col style="width: 6%;">
              <col style="width: 12%;">
              <col style="width: 21%;">
              <col style="width: 10%;">
              <col style="width: 12%;">
              <col style="width: 18%;">
              <col style="width: 21%;">
            </colgroup>
            <thead class="display-table-head">
              <tr>
                <th class="text-center">ລຳດັບ</th>
                <th class="text-center">ຮູບສິນຄ້າ</th>
                <th class="text-center">ຊື່ສິນຄ້າ</th>
                <th class="text-center">ຫົວໜ່ວຍ</th>
                <th class="text-center">ຈຳນວນ</th>
                <th class="text-right">ລາຄາ</th>
                <th class="text-right">ລວມ</th>
              </tr>
            </thead>
            <tbody id="displayCartBody"></tbody>
          </table>
          <div class="display-cart-empty" id="displayCartEmpty">
            <i class="fas fa-shopping-basket"></i>
            <h4>ຍັງບໍ່ມີລາຍການສິນຄ້າ</h4>
            <p>ກະລຸນາລໍຖ້າພະນັກງານສະແກນ ຫຼື ເລືອກສິນຄ້າ...</p>
          </div>
        </div>
      </div>

    </div>

  </div>

  <!-- GREEN GRAND-TOTAL BOX: full-width bar below both panels, every payment stat + grand total -->
  <div class="grand-total-banner">
    <div class="gt-substats">
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-boxes"></i> ລາຍການສິນຄ້າ</span>
        <span class="gt-substat-value" id="displayItemCount">0 ລາຍການ / 0 ອັນ</span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-calculator"></i> ຍອດລວມ</span>
        <span class="gt-substat-value" id="displaySubtotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-percent"></i> ສ່ວນຫຼຸດ</span>
        <span class="gt-substat-value" id="displayDiscount">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-qrcode"></i> ຮັບເງິນໂອນ</span>
        <span class="gt-substat-value" id="displayQrReceived">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-money-bill-wave"></i> ຮັບເງິນສົດ</span>
        <span class="gt-substat-value" id="displayCashReceived">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
      <div class="gt-substat">
        <span class="gt-substat-label"><i class="fas fa-hand-holding-usd"></i> ເງິນທອນ</span>
        <span class="gt-substat-value" id="displayChangeAmount">0 <?php echo htmlspecialchars($cd['symbol']); ?></span>
      </div>
    </div>

    <div class="gt-final">
      <span class="gt-final-label">ຍອດຊຳລະ (ຍອດລວມ - ສ່ວນຫຼຸດ)</span>
      <h2 id="displayGrandTotal">0 <?php echo htmlspecialchars($cd['symbol']); ?></h2>
    </div>
  </div>
</div>

<script src="<?php echo $base_path; ?>plugins/jquery/jquery.min.js"></script>
<script>
// ===== Display currency (cart data arrives in KIP; shown in the active currency) =====
<?php
  $cd = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
      : ['code' => 'KIP', 'symbol' => '₭', 'decimals' => 0, 'rate' => 1];
?>
window.CD_CUR = {
  code: <?php echo json_encode($cd['code']); ?>,
  symbol: <?php echo json_encode($cd['symbol'], JSON_UNESCAPED_UNICODE); ?>,
  decimals: <?php echo (int)$cd['decimals']; ?>,
  rate: <?php echo json_encode((float)$cd['rate']); ?>,
  isKip: <?php echo ($cd['code'] === 'KIP') ? 'true' : 'false'; ?>
};
function cdFmt(v) {
  v = parseFloat(v) || 0;
  return v.toLocaleString('en-US', { minimumFractionDigits: CD_CUR.decimals, maximumFractionDigits: CD_CUR.decimals }) + ' <span class="cur-sym">' + CD_CUR.symbol + '</span>';
}
// kip -> formatted display string
function cdMoney(kip) {
  kip = parseFloat(kip) || 0;
  return cdFmt(CD_CUR.isKip ? Math.round(kip) : (kip / CD_CUR.rate));
}

// Fullscreen: try automatically on load (works when opened via a user click), and
// always offer the manual toggle button since browsers can silently block the auto request.
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    (document.documentElement.requestFullscreen && document.documentElement.requestFullscreen())?.catch(function(){});
  } else {
    document.exitFullscreen && document.exitFullscreen().catch(function(){});
  }
}
document.getElementById('fullscreenToggleBtn').addEventListener('click', toggleFullscreen);
document.addEventListener('fullscreenchange', function() {
  var icon = document.getElementById('fullscreenToggleIcon');
  icon.className = document.fullscreenElement ? 'fas fa-compress' : 'fas fa-expand';
});
try {
  if (document.documentElement.requestFullscreen) {
    document.documentElement.requestFullscreen().catch(function(){});
  }
} catch(e) {}

// Particle Background — soft drifting dots behind the store logo/info panel
(function() {
  var canvas = document.getElementById('storeCardParticles');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var card = canvas.closest('.store-card');
  var particles = [];
  var colors = ['rgba(2,99,255,0.55)', 'rgba(109,40,217,0.50)', 'rgba(5,150,105,0.50)', 'rgba(255,212,90,0.55)'];
  var dpr = window.devicePixelRatio || 1;
  var w = 0, h = 0;

  function resize() {
    w = card.clientWidth;
    h = card.clientHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function makeParticle() {
    var r = 1.5 + Math.random() * 3;
    return {
      x: Math.random() * w,
      y: Math.random() * h,
      r: r,
      vx: (Math.random() - 0.5) * 0.25,
      vy: (Math.random() - 0.5) * 0.25,
      color: colors[Math.floor(Math.random() * colors.length)],
      twinkle: Math.random() * Math.PI * 2
    };
  }

  function init() {
    resize();
    var count = Math.max(24, Math.round((w * h) / 9000));
    particles = [];
    for (var i = 0; i < count; i++) particles.push(makeParticle());
  }

  function step() {
    ctx.clearRect(0, 0, w, h);
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += p.vx;
      p.y += p.vy;
      p.twinkle += 0.02;

      if (p.x < -10) p.x = w + 10;
      if (p.x > w + 10) p.x = -10;
      if (p.y < -10) p.y = h + 10;
      if (p.y > h + 10) p.y = -10;

      var pulse = 0.55 + Math.sin(p.twinkle) * 0.35;
      ctx.beginPath();
      ctx.fillStyle = p.color;
      ctx.globalAlpha = pulse;
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalAlpha = 1;

    // Faint connecting lines between nearby particles for a "network" feel
    ctx.strokeStyle = 'rgba(100,116,139,0.12)';
    ctx.lineWidth = 1;
    for (var a = 0; a < particles.length; a++) {
      for (var b = a + 1; b < particles.length; b++) {
        var dx = particles[a].x - particles[b].x;
        var dy = particles[a].y - particles[b].y;
        var dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 90) {
          ctx.beginPath();
          ctx.moveTo(particles[a].x, particles[a].y);
          ctx.lineTo(particles[b].x, particles[b].y);
          ctx.stroke();
        }
      }
    }

    requestAnimationFrame(step);
  }

  init();
  requestAnimationFrame(step);
  window.addEventListener('resize', init);
})();

// Live Clock
function updateClock() {
  var now = new Date();
  $('#liveClock').text(now.toLocaleTimeString('lo-LA'));
  $('#liveDate').text(now.toLocaleDateString('lo-LA'));
}
setInterval(updateClock, 1000);

// Big queue-number takeover for a few seconds after a sale completes.
var _queueFlashTimer = null;
function showQueueFlash(num) {
  var el = document.getElementById('queueFlash');
  if (!el) return;
  document.getElementById('queueFlashNo').textContent = num;
  el.style.display = 'flex';
  if (_queueFlashTimer) clearTimeout(_queueFlashTimer);
  _queueFlashTimer = setTimeout(function () { el.style.display = 'none'; }, 6000);
}
updateClock();

// Broadcast Channel & Storage Sync
var channel = null;
try {
  channel = new BroadcastChannel('pos_customer_display_channel');
  channel.onmessage = function(e) {
    if (e.data) renderDisplayData(e.data);
  };
} catch(e) {}

window.addEventListener('storage', function(e) {
  if (e.key === 'pos_customer_display_data') {
    try {
      renderDisplayData(JSON.parse(e.newValue));
    } catch(err) {}
  }
});

function loadInitialData() {
  var raw = localStorage.getItem('pos_customer_display_data');
  if (raw) {
    try {
      renderDisplayData(JSON.parse(raw));
    } catch(e) {}
  }
}
loadInitialData();

function renderDisplayData(data) {
  if (!data) return;

  // Queue number flash on payment success (coffee-shop style)
  if (data.checkoutState === 'payment_success' && data.queueNumber) {
    showQueueFlash(data.queueNumber);
  }

  // 1. Cart Table (Enlarged Image, Name, Unit, Qty, Price, Total)
  var tbody = $('#displayCartBody');
  tbody.empty();

  var cart = data.cart || [];
  $('#displayCartEmpty').toggle(cart.length === 0);
  if (cart.length === 0) {
    // Nothing to render — #displayCartEmpty (shown above) carries the message.
  } else {
    // Render newest first: the most recently scanned/tapped item shows at the top and
    // is highlighted. Prefer the "_touch" stamp set by the POS cart; fall back to the
    // cart's own order. The "ລຳດັບ" number still reflects the original add order.
    var origIndex = new Map();
    cart.forEach(function(it, n) { origIndex.set(it, n); });
    var orderedCart = cart.slice().sort(function(a, b) {
      var ta = (typeof a._touch === 'number') ? a._touch : origIndex.get(a);
      var tb = (typeof b._touch === 'number') ? b._touch : origIndex.get(b);
      return tb - ta;
    });
    orderedCart.forEach(function(item, i) {
      var idx = origIndex.get(item);
      var isNewest = (i === 0);
      var isGift = !!item.is_free_gift;
      var itemTotal = isGift ? cdMoney(0) : cdMoney(item.quantity * item.unit_price);
      var unitPriceStr = isGift ? cdMoney(0) : cdMoney(item.unit_price);
      var unitNameStr = item.unit_name ? item.unit_name : 'ອັນ';
      var giftBadgeHtml = isGift ? `<span class="badge badge-success font-weight-bold ml-1" style="font-size:0.82rem; padding:2px 6px; border-radius:4px;"><i class="fas fa-gift mr-1"></i>ແຖມຟຣີ</span>` : '';

      var rawImg = item.image || item.img_url || '';
      var imgName = rawImg ? rawImg.split('/').pop().split('\\').pop() : 'image.jpg';
      var imgSrc = (!imgName || imgName === 'image.jpg') ? '../../assets/img/image.jpg' : '../../assets/product_img/' + imgName;

      tbody.append(`
        <tr class="${isNewest ? 'display-row-newest' : ''}" style="${isGift ? 'background:#f0fdf4;' : ''}">
          <td class="text-center text-muted font-weight-bold">${idx + 1}</td>
          <td class="text-center">
            <img src="${imgSrc}" alt="${item.product_name}" class="display-pro-img" onerror="this.src='../../assets/img/image.jpg';">
          </td>
          <td class="font-weight-bold text-dark" style="font-size: 1.1rem;">
            ${item.product_name} ${giftBadgeHtml}
            <small class="text-muted d-block font-weight-normal" style="font-size:0.80rem; ${item.barcode ? '' : 'visibility:hidden;'}">${item.barcode || ' '}</small>
          </td>
          <td class="text-center font-weight-bold text-primary">${unitNameStr}</td>
          <td class="text-center"><span class="badge-qty">x${item.quantity}</span></td>
          <td class="text-right ${isGift ? 'text-success' : 'text-primary'} font-weight-bold" style="white-space: nowrap;">${unitPriceStr}</td>
          <td class="text-right text-success font-weight-bold" style="font-size: 1.15rem; white-space: nowrap;">${itemTotal}</td>
        </tr>
      `);
    });
  }

  // 2. Summary Totals Calculation
  var lineCount = cart.length;
  var totalQty = 0;
  var subtotal = 0;
  cart.forEach(function(i) {
    totalQty += i.quantity;
    subtotal += i.quantity * i.unit_price;
  });

  var discountVal = parseFloat((data.discount || '0').toString().replace(/[^\d]/g, '')) || 0;
  var netGrandTotal = Math.max(0, subtotal - discountVal);

  // Animated number counter helper. targetKip is in KIP; displayed in CD_CUR.
  function animateNumber(elementId, targetKip) {
    var $el = $('#' + elementId);
    var target = CD_CUR.isKip ? Math.round(targetKip) : (parseFloat(targetKip) || 0) / CD_CUR.rate;
    var startValue = parseFloat(($el.text() || '').replace(/[^0-9.]/g, '')) || 0;
    if (startValue === target) { $el.html(cdFmt(target)); return; }
    var duration = 400;
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var easeProgress = 1 - Math.pow(1 - progress, 3); // easeOutCubic
      var val = startValue + (target - startValue) * easeProgress;
      $el.html(cdFmt(val));
      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        $el.html(cdFmt(target));
      }
    }
    window.requestAnimationFrame(step);
  }

  $('#displayItemCount').text(lineCount + ' ລາຍການ / ' + totalQty + ' ອັນ');
  animateNumber('displaySubtotal', subtotal);
  animateNumber('displayDiscount', discountVal);
  animateNumber('displayGrandTotal', netGrandTotal);

  // 3. Cash, QR, and Change Breakdown
  var cashRec = parseFloat(data.cashReceived || 0);
  var qrRec = parseFloat(data.qrReceived || 0);
  var changeAmt = parseFloat(data.changeAmount || 0);

  animateNumber('displayCashReceived', cashRec);
  animateNumber('displayQrReceived', qrRec);
  animateNumber('displayChangeAmount', changeAmt);
}
</script>

</body>
</html>
