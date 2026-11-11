<?php
/**
 * ໜ້າລາຍລະອຽດອໍເດີເກົ່າ — ເລີກໃຊ້ແລ້ວ (11/2026): ລາຍລະອຽດ + ຕິດຕາມ + ແນບສະລິບ ຢູ່ໃນ modal ຂອງ pages/my_orders.php.
 * ຍັງເກັບ URL ນີ້ໄວ້ ເພາະລິ້ງເກົ່າ (ກະດິ່ງແຈ້ງເຕືອນ, ອີເມວ, ໜ້າແຊັດ, ຫຼັງສັ່ງຊື້) ຊີ້ມາ → ສົ່ງຕໍ່ໄປ my_orders.php?track=ເລກອໍເດີ
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$orderNo = (string)($_GET['no'] ?? '');
$target = 'pages/my_orders.php?s=all' . ($orderNo !== '' ? '&track=' . rawurlencode($orderNo) : '');
if (!currentMember($pdo)) {
    redirect('pages/member_login.php?next=' . rawurlencode($target));
}
redirect($target);
