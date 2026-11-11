<?php
/**
 * ແຈ້ງເຕືອນພະນັກງານເມື່ອມີອໍເດີເວັບໃໝ່ / ລູກຄ້າແນບສະລິບ (includes/staff_notify.php) — ສຳເນົາເປັນ config/notify.php ແລ້ວແກ້.
 * ວ່າງທັງໝົດ = ບໍ່ແຈ້ງ. ໃຊ້ຊ່ອງທາງດຽວ ຫຼື ທັງສອງກໍໄດ້.
 *
 * Telegram (ຟຣີ, ແຈ້ງເຂົ້າມືຖືທັນທີ):
 *   1. ໃນ Telegram ຄົ້ນຫາ @BotFather → /newbot → ຕັ້ງຊື່ → ໄດ້ "token" (ເຊັ່ນ 123456:ABC-...)
 *   2. ສ້າງກຸ່ມ Telegram ຂອງພະນັກງານ ແລ້ວເພີ່ມ bot ເຂົ້າກຸ່ມ, ພິມຂໍ້ຄວາມໃດໜຶ່ງໃນກຸ່ມ
 *   3. ເປີດ https://api.telegram.org/bot<token>/getUpdates → ເອົາ "chat":{"id": -100...} ມາໃສ່ telegram_chat_id
 *
 * ອີເມວ: ລາຍຊື່ອີເມວພະນັກງານ — ສົ່ງຜ່ານ SMTP ດຽວກັບ config/mail.php
 */
return [
    'telegram_bot_token' => '',
    'telegram_chat_id'   => '',
    'emails'             => [],   // ເຊັ່ນ ['owner@gmail.com', 'sale@gmail.com']
];
