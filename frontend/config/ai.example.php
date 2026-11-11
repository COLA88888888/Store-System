<?php
/**
 * ຕົວຢ່າງການຕັ້ງຄ່າ AI ຂອງແຊັດບັອດ (models/chat.php). ກັອບປີ້ໄຟລ໌ນີ້ເປັນ config/ai.php ແລ້ວໃສ່ key.
 * config/ai.php ຢູ່ໃນ .gitignore — ຢ່າ commit key. ບໍ່ມີໄຟລ໌ / key ຫວ່າງ → ບັອດຕອບຕາມຄຳສຳຄັນແທນ (ບໍ່ເສຍເງິນ).
 *   anthropic_key : API key ຈາກ https://console.anthropic.com (ເສຍເງິນຕາມຈຳນວນຂໍ້ຄວາມ)
 *   model         : ຮຸ່ນ Claude
 *   effort        : low = ຕອບໄວ ປະຢັດ (ພໍສຳລັບແຊັດ), medium / high = ຄິດຫຼາຍຂຶ້ນ
 *   daily_limit   : ຈຳນວນຄຳຕອບ AI ສູງສຸດຕໍ່ 1 ຫ້ອງແຊັດຕໍ່ມື້ (ກັນຄ່າໃຊ້ຈ່າຍບານປາຍ)
 */
return [
    'anthropic_key' => '',
    'model'         => 'claude-opus-5-5',
    'effort'        => 'low',
    'daily_limit'   => 30,
];
