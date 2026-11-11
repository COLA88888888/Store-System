<?php
/**
 * ແຖວບັດສະຖິຕິເວັບໄຊ ເທິງຕາຕະລາງຄຳສັ່ງຊື້ (ຕັດອອກມາຈາກ pages/web_orders/index.php — require ຢູ່ລະຫວ່າງ .wo-head ແລະ .wo-card).
 * ອໍເດີມື້ນີ້ / ລໍຖ້າຢືນຢັນ / ອໍເດີ 7 ມື້ / ຍອດຂາຍອອນລາຍ 30 ມື້ (ບໍ່ນັບຍົກເລີກ ແລະ ອໍເດີທີ່ຍັງບໍ່ຢືນຢັນ)
 * ໃຊ້ $pdo ຈາກ config/db.php. CSS: .wo-stats ໃນ themes/web_orders.css
 */
$woStats = ['today' => 0, 'pending' => 0, 'week' => 0, 'revenue' => 0.0, 'slips' => 0];
try {
    $woStats = array_merge($woStats, $pdo->query("SELECT
            SUM(DATE(created_at) = CURDATE()) AS today,
            SUM(status = 'pending') AS pending,
            SUM(created_at >= CURDATE() - INTERVAL 6 DAY) AS week,
            COALESCE(SUM(CASE WHEN status NOT IN ('pending', 'cancelled') AND created_at >= CURDATE() - INTERVAL 29 DAY THEN total_amount END), 0) AS revenue,
            SUM(status = 'pending' AND slip_img IS NOT NULL AND slip_img <> '') AS slips
        FROM w_web_orders")->fetch(PDO::FETCH_ASSOC) ?: []);
} catch (Throwable $e) {
    error_log('web_orders stats: ' . $e->getMessage());
}
$woCards = [
    ['fa-shopping-bag', 'ອໍເດີມື້ນີ້', number_format((int)$woStats['today']), 'is-blue'],
    ['fa-hourglass-half', 'ລໍຖ້າຢືນຢັນ', number_format((int)$woStats['pending']) . ((int)$woStats['slips'] > 0 ? ' <small>(ສະລິບລໍຖ້າກວດ ' . (int)$woStats['slips'] . ')</small>' : ''), 'is-amber'],
    ['fa-calendar-week', 'ອໍເດີ 7 ມື້', number_format((int)$woStats['week']), 'is-blue'],
    ['fa-coins', 'ຍອດຂາຍ 30 ມື້', number_format((float)$woStats['revenue']) . ' ₭', 'is-green'],
];
?>
      <div class="wo-stats">
        <?php foreach ($woCards as [$ico, $lbl, $val, $tone]): ?>
          <div class="wo-stat <?php echo $tone; ?>">
            <i class="fas <?php echo $ico; ?>" aria-hidden="true"></i>
            <div><span><?php echo htmlspecialchars($lbl); ?></span><b><?php echo $val; /* ຕົວເລກ + HTML ທີ່ສ້າງເອງ (ບໍ່ມີຂໍ້ມູນຈາກຜູ້ໃຊ້) */ ?></b></div>
          </div>
        <?php endforeach; ?>
      </div>
