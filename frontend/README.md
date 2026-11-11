# frontend/ — ເວັບໄຊໜ້າບ້ານ

ເວັບໄຊທີ່ລູກຄ້າເຫັນ: ແນະນຳບໍລິສັດ ແລະ ຮ້ານຄ້າອອນລາຍ. ຂຽນດ້ວຍ PHP + HTML/CSS/JS ທຳມະດາ
(ບໍ່ມີ npm, ບໍ່ຕ້ອງ build, ບໍ່ໃຊ້ jQuery). ແກ້ໄຟລ໌ແລ້ວ reload ໄດ້ເລີຍ.

- ເວັບໄຊ: `http://localhost/websizewlaodev/` — ໜ້າຫຼັກຂອງໂປຣເຈັກຄື frontend. `.htaccess` ທີ່ root ສົ່ງທຸກ URL (ຍົກເວັ້ນ `backend/`) ເຂົ້າ `frontend/` ພາຍໃນ, ສະນັ້ນ URL ບໍ່ມີ `/frontend/` (ເຊັ່ນ `/websizewlaodev/pages/shop.php`). ລິ້ງເກົ່າທີ່ມີ `/frontend/` ຖືກ redirect ມາ URL ສັ້ນ
- ລະບົບຫຼັງບ້ານ (POS): `http://localhost/websizewlaodev/backend/`
- ຖານຂໍ້ມູນ: `webwlaodev` (ຕາຕະລາງ `w_`) ໃຊ້ຮ່ວມກັບ backend
- Session: cookie `WLAOWEBSSID` ແຍກຈາກ session ພະນັກງານ (`WLAODEVSSID`)

## ໂຄງສ້າງໄຟລ໌

```
frontend/
├── index.php                  ໜ້າຫຼັກ
├── pages/                     ໜ້າເວັບ (ເປີດຈາກ browser ໄດ້)
│   ├── about.php              ກ່ຽວກັບພວກເຮົາ
│   ├── services.php           ບໍລິການ
│   ├── portfolio.php          ຜົນງານ (ມີປຸ່ມກັ່ນຕອງ)
│   ├── shop.php               ຮ້ານຄ້າ: ຄົ້ນຫາ, ໝວດ, ຮຽງ, ແບ່ງໜ້າ
│   ├── product.php            ລາຍລະອຽດສິນຄ້າ (?id=)
│   ├── cart.php               ກະຕ່າສິນຄ້າ
│   ├── checkout.php           ຟອມສັ່ງຊື້ → ບັນທຶກລົງ w_web_orders
│   ├── order_success.php      ສັ່ງຊື້ສຳເລັດ
│   └── contact.php            ຫ້ອງແຊັດ (assets/js/chat.js ↔ api/chat.php, models/chat.php) → w_web_chats / w_web_chat_messages;
│                              ບັອດ AI ເມື່ອມີ key ໃນ config/ai.php (ກັອບຈາກ ai.example.php), ພະນັກງານຕອບທີ່ backend/pages/web_chats/
├── api/                       JSON endpoint (ເອີ້ນຈາກ JS)
│   ├── cart.php               ເພີ່ມ/ແກ້/ລຶບ ກະຕ່າ
│   └── products.php           ລາຍການສິນຄ້າ (ອ່ານຢ່າງດຽວ)
├── config/        🔒          ການຕັ້ງຄ່າ
│   ├── app.php                ຊື່ເວັບ, ຖານຂໍ້ມູນ, ຊື່ session  ← ແກ້ກ່ອນຂຶ້ນ server
│   ├── database.php           ເຊື່ອມ $pdo
│   └── session.php            ເລີ່ມ session
├── includes/      🔒          ໂຄ້ດຊ່ວຍ
│   ├── bootstrap.php          ທຸກໜ້າ require ໄຟລ໌ນີ້ໄຟລ໌ດຽວ
│   ├── helpers.php            url(), asset(), e(), money(), csrf, jsonOut()
│   ├── i18n.php               ແປພາສາ t() / te(), ທຸງ 5 ພາສາ, ປ່ຽນພາສາດ້ວຍ ?lang=xx
│   ├── content.php            ຂໍ້ຄວາມ ບໍລິການ/ຜົນງານ  ← ແກ້ເນື້ອຫາເວັບທີ່ນີ້
│   └── cart.php               ກະຕ່າໃນ session
├── lang/          🔒          ຄຳແປ: th.php, en.php, zh.php, vi.php (ພາສາລາວ = ຂໍ້ຄວາມໃນໂຄ້ດ)
├── models/        🔒          ອ່ານ/ຂຽນ ຖານຂໍ້ມູນ
│   ├── company.php            ຂໍ້ມູນຮ້ານ (w_tbcompanyinfo)
│   ├── products.php           ສິນຄ້າ ແລະ ໝວດ (w_products, w_categories)
│   ├── orders.php             ຄຳສັ່ງຊື້ (w_web_orders, w_web_order_items)
│   ├── contacts.php           ຂໍ້ຄວາມຕິດຕໍ່ (w_web_contacts)
│   └── banners.php            ສ້າງ WebP 1920px ຈາກ backend/assets/img/baner/ → assets/img/banner/ (ໜ້າຫຼັກບໍ່ໃຊ້ແລ້ວ; models/solutions.php ໃຊ້ຮູບເຫຼົ່ານີ້)
├── layouts/       🔒          header.php, navbar.php, footer.php
├── components/    🔒          hero (ສີເຫຼືອງ + ຊ່ອງຄົ້ນຫາ), product_card, service_card, page_hero, pagination
└── assets/
    ├── css/                   fonts → base → layout → components → pages (ລຳດັບສຳຄັນ)
    ├── fonts/                 Nuanta (Regular/Medium/Bold) — ຟ້ອນດຽວກັບ backend
    ├── js/                    main.js (ທົ່ວໄປ), cart.js (ກະຕ່າ)
    └── img/                   logo.png, flags/ (ທຸງພາສາ), banner/ (ສ້າງອັດຕະໂນມັດ)
```

🔒 = ມີ `.htaccess` `Require all denied` (ເປີດຈາກ browser ໂດຍກົງບໍ່ໄດ້). ສ້າງໂຟນເດີໃໝ່ທີ່ມີແຕ່ໄຟລ໌ require ໃຫ້ copy `.htaccess` ໄປນຳ.

## ເພີ່ມໜ້າໃໝ່

1. ສ້າງ `pages/<ຊື່>.php` ຕາມຮູບແບບນີ້:
   ```php
   <?php
   require dirname(__DIR__) . '/includes/bootstrap.php';
   $pageTitle = 'ຊື່ໜ້າ';
   $currentPage = '<ຊື່>';
   require dirname(__DIR__) . '/layouts/header.php';
   require dirname(__DIR__) . '/components/page_hero.php';
   ?>
   ... HTML ...
   <?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
   ```
2. ເພີ່ມເມນູໃນ `$navItems` ຂອງ `layouts/navbar.php`
3. CSS ສະເພາະໜ້າ → `assets/css/pages.css`; JS ສະເພາະໜ້າ → `assets/js/<ຊື່>.js` ແລ້ວຕັ້ງ `$pageScripts = ['js/<ຊື່>.js'];` ກ່ອນ require footer

## ກົດທີ່ຕ້ອງຮູ້

- ໃຊ້ `url('pages/x.php')` ແລະ `asset('css/x.css')` ສະເໝີ — ບໍ່ຂຽນ path ເອງ
- Escape ທຸກຂໍ້ມູນທີ່ສະແດງດ້ວຍ `e()`; SQL ໃຊ້ `$pdo->prepare()` ເທົ່ານັ້ນ
- ທຸກຟອມ POST ຕ້ອງມີ `csrfField()` ແລະ ກວດດ້ວຍ `checkCsrf()`; JS ໃຊ້ `apiPost()` (ສົ່ງ token ໃຫ້ເອງ)
- ລາຄາ ແລະ ສະຕັອກ ອ່ານຈາກ DB ທຸກຄັ້ງ — ກະຕ່າເກັບພຽງ id ແລະ ຈຳນວນ
- ສິນຄ້າທີ່ສະແດງ = ສາຂາໃຫຍ່ (`is_main = 1`), ລາຄາ > 0. ຮູບສິນຄ້າມາຈາກ `backend/assets/product_img/`
- ຄຳສັ່ງຊື້ **ບໍ່ຕັດສະຕັອກ** — ສະຖານະ `pending` ຈົນພະນັກງານຢືນຢັນ
- ຕາຕະລາງໃໝ່: ເພີ່ມໃນ `backend/config/migrations/schema_website.php` ແລ້ວປ່ຽນເລກ `vN` ໃນ `backend/config/db.php` — frontend ບໍ່ສ້າງຕາຕະລາງເອງ

## ແປພາສາ (5 ພາສາ: ລາວ, ໄທ, ອັງກິດ, ຈີນ, ຫວຽດ)

- ທຸກຂໍ້ຄວາມທີ່ຜູ້ໃຊ້ເຫັນ ຂຽນເປັນພາສາລາວ ແລ້ວຫຸ້ມດ້ວຍ `t('...')` (ໃນ PHP) ຫຼື `<?php echo te('...'); ?>` (ໃນ HTML — escape ໃຫ້ແລ້ວ)
- ມີຕົວແປ: `t('ມີສິນຄ້າພຽງ %d %s', $max, $unit)` — ຢ່າຕໍ່ string ດ້ວຍ `.` ເພາະລຳດັບຄຳແຕ່ລະພາສາບໍ່ຄືກັນ
- ເພີ່ມຂໍ້ຄວາມໃໝ່ → ເພີ່ມ key ດຽວກັນ (ຂໍ້ຄວາມລາວ) ໃນ `lang/th.php`, `en.php`, `zh.php`, `vi.php`. ບໍ່ມີຄຳແປ = ສະແດງພາສາລາວ
- ພາສາທີ່ເລືອກ ເກັບໃນ session ຂອງເວັບໄຊ + cookie `wlaoweb_lang` (ແຍກຈາກ backend). ຊື່ສິນຄ້າ ແລະ ຂໍ້ມູນຮ້ານ ມາຈາກ DB — ບໍ່ຖືກແປ
- ຂໍ້ຄວາມໃນ JS: ສົ່ງຜ່ານ `window.APP.i18n` ໃນ `layouts/header.php`
