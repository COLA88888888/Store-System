<?php
/*
 * ສ່ວນ: ໜ້າຕາລາຍງານ "ລາຍຮັບ ເວັບໄຊ vs ໜ້າຮ້ານ" — ຖືກ require ຈາກ pages/reports/channel_report.php
 * ໃຊ້ຕົວແປຈາກ partials/channel_report/data.php. ສະໄຕລ໌: themes/channel_report.css (.ch-*)
 */
$avgShop = $shop['count'] > 0 ? $shop['revenue'] / $shop['count'] : 0;
$avgWeb  = $web['count'] > 0 ? $web['revenue'] / $web['count'] : 0;
$diff = abs($shop['revenue'] - $web['revenue']);
?>
<div class="content-wrapper bg-light">
  <section class="content pt-3 pb-4">
    <div class="container-fluid ch-wrap">
      <div class="ch-head">
        <div>
          <h1 class="ch-title">ລາຍຮັບ: ເວັບໄຊ vs ລູກຄ້າໜ້າຮ້ານ</h1>
          <div class="ch-sub"><?php echo date('d/m/Y', strtotime($fromDate)); ?> – <?php echo date('d/m/Y', strtotime($toDate)); ?></div>
        </div>
        <form method="get" class="ch-filter no-print">
          <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
          <span class="ch-dash">–</span>
          <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
          <?php if ($branches): ?>
            <select name="store_id" class="form-control">
              <option value="0">ທຸກສາຂາ</option>
              <?php foreach ($branches as $b): ?>
                <option value="<?php echo (int)$b['store_id']; ?>"<?php echo $storeId === (int)$b['store_id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($b['store_name']); ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
          <button class="btn ch-btn">ເບິ່ງລາຍງານ</button>
          <button type="button" class="btn ch-btn is-light" onclick="window.print()">ພິມ</button>
        </form>
      </div>

      <!-- ສອງຊ່ອງທາງ -->
      <div class="ch-cards">
        <div class="ch-card is-shop<?php echo $winner === 'shop' ? ' is-win' : ''; ?>">
          <div class="ch-card-label">ລູກຄ້າມາຊື້ໃນຮ້ານ (POS)<?php if ($winner === 'shop'): ?><span class="ch-win">ລາຍຮັບສູງກວ່າ</span><?php endif; ?></div>
          <div class="ch-card-value"><?php echo $money($shop['revenue']); ?></div>
          <div class="ch-card-meta">
            <span><?php echo number_format($shop['count']); ?> ບິນ</span>
            <span>ສະເລ່ຍ <?php echo $money($avgShop); ?> / ບິນ</span>
            <span><?php echo $shopShare; ?>% ຂອງລາຍຮັບທັງໝົດ</span>
          </div>
        </div>
        <div class="ch-card is-web<?php echo $winner === 'web' ? ' is-win' : ''; ?>">
          <div class="ch-card-label">ລູກຄ້າສັ່ງຈາກເວັບໄຊ<?php if ($winner === 'web'): ?><span class="ch-win">ລາຍຮັບສູງກວ່າ</span><?php endif; ?></div>
          <div class="ch-card-value"><?php echo $money($web['revenue']); ?></div>
          <div class="ch-card-meta">
            <span><?php echo number_format($web['count']); ?> ອໍເດີສຳເລັດ</span>
            <span>ສະເລ່ຍ <?php echo $money($avgWeb); ?> / ອໍເດີ</span>
            <span><?php echo $webShare; ?>% ຂອງລາຍຮັບທັງໝົດ</span>
          </div>
          <div class="ch-card-note">
            ກຳລັງດຳເນີນການ <?php echo number_format($web['open']); ?> ອໍເດີ · ຍົກເລີກ <?php echo number_format($web['cancelled']); ?>
            <?php if ($web['noprice']): ?><br><span class="text-danger"><?php echo $web['noprice']; ?> ອໍເດີສຳເລັດແຕ່ບໍ່ມີລາຄາ (ສິນຄ້າຍັງບໍ່ໄດ້ຕັ້ງລາຄາຂາຍ)</span><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ສັດສ່ວນລາຍຮັບ -->
      <div class="ch-panel">
        <div class="ch-share-head"><span>ໜ້າຮ້ານ <?php echo $shopShare; ?>%</span><b>ລາຍຮັບລວມ <?php echo $money($totalRevenue); ?></b><span>ເວັບໄຊ <?php echo $webShare; ?>%</span></div>
        <div class="ch-share" role="img" aria-label="ໜ້າຮ້ານ <?php echo $shopShare; ?>%, ເວັບໄຊ <?php echo $webShare; ?>%">
          <?php if ($totalRevenue > 0): ?>
            <span class="is-shop" style="width: <?php echo $shopShare; ?>%"></span><span class="is-web" style="width: <?php echo $webShare; ?>%"></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- ສະຫຼຸບ + ຄຳແນະນຳການຕະຫຼາດ -->
      <div class="ch-panel ch-advice">
        <h2>ສະຫຼຸບ ແລະ ແນວທາງການຕະຫຼາດ</h2>
        <?php if ($winner === 'none'): ?>
          <p>ຍັງບໍ່ມີລາຍຮັບໃນຊ່ວງວັນທີນີ້ — ລອງເລືອກຊ່ວງວັນທີອື່ນ.</p>
        <?php elseif ($winner === 'tie'): ?>
          <p>ລາຍຮັບສອງຊ່ອງທາງເທົ່າກັນ — ຮັກສາທັງສອງຊ່ອງທາງ ແລະ ທົດລອງໂປຣໂມຊັນໃນຊ່ອງທາງໃດໜຶ່ງເພື່ອປຽບທຽບ.</p>
        <?php elseif ($winner === 'shop'): ?>
          <p><b>ລູກຄ້າໜ້າຮ້ານສ້າງລາຍຮັບຫຼາຍກວ່າ <?php echo $money($diff); ?></b> (<?php echo $shopShare; ?>% ຂອງລາຍຮັບ). ເວັບໄຊຍັງມີໂອກາດເຕີບໂຕ:</p>
          <ul>
            <li>ເອົາສິນຄ້າຂາຍດີໜ້າຮ້ານ (ລາຍຊື່ລຸ່ມນີ້) ໄປໂພສ Facebook ແລະ ໜ້າເວັບ ພ້ອມລາຄາຊັດເຈນ</li>
            <li>ເຮັດໂປຣສະເພາະການສັ່ງອອນລາຍ ເຊັ່ນ ສ່ວນຫຼຸດ ຫຼື ສົ່ງຟຣີ</li>
            <li>ຕິດ QR ໜ້າເວັບໄຊໃນຮ້ານ ແລະ ໃນໃບບິນ ໃຫ້ລູກຄ້າສັ່ງຊ້ຳທາງອອນລາຍ</li>
            <li>ຕັ້ງລາຄາຂາຍໃຫ້ສິນຄ້າທຸກລາຍການ ເພື່ອແຈ້ງລາຄາລູກຄ້າເວັບໄດ້ທັນທີ</li>
          </ul>
        <?php else: ?>
          <p><b>ເວັບໄຊສ້າງລາຍຮັບຫຼາຍກວ່າ <?php echo $money($diff); ?></b> (<?php echo $webShare; ?>% ຂອງລາຍຮັບ). ໃຊ້ຈຸດແຂງອອນລາຍດຶງລູກຄ້າເຂົ້າຮ້ານ:</p>
          <ul>
            <li>ເພີ່ມງົບໂຄສະນາອອນລາຍໃຫ້ສິນຄ້າທີ່ຂາຍດີໃນເວັບ (ລາຍຊື່ລຸ່ມນີ້)</li>
            <li>ເຮັດໂປຣສະເພາະລູກຄ້າທີ່ມາຊື້ໃນຮ້ານ ແລະ ວາງສິນຄ້າຂາຍດີອອນລາຍໄວ້ໜ້າຮ້ານ</li>
            <li>ໂທຫາລູກຄ້າເວັບທີ່ຍັງບໍ່ປິດອໍເດີ (<?php echo number_format($web['open']); ?> ອໍເດີ) ເພື່ອປິດການຂາຍ</li>
          </ul>
        <?php endif; ?>
      </div>

      <!-- ສິນຄ້າຂາຍດີແຕ່ລະຊ່ອງທາງ -->
      <div class="ch-tops">
        <?php foreach ([['ສິນຄ້າຂາຍດີ ໜ້າຮ້ານ', $topShop, 'is-shop'], ['ສິນຄ້າຂາຍດີ ເວັບໄຊ', $topWeb, 'is-web']] as [$ttl, $rows, $cls]): ?>
          <div class="ch-panel">
            <h2 class="<?php echo $cls; ?>"><?php echo $ttl; ?></h2>
            <?php if (!$rows): ?>
              <p class="text-muted mb-0">ຍັງບໍ່ມີຂໍ້ມູນ</p>
            <?php else: ?>
              <table class="table table-sm mb-0 ch-table">
                <thead><tr><th>#</th><th>ສິນຄ້າ</th><th class="text-right">ຈຳນວນ</th><th class="text-right">ລາຍຮັບ</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $i => $r): ?>
                    <tr><td><?php echo $i + 1; ?></td><td><?php echo htmlspecialchars($r['name']); ?></td><td class="text-right"><?php echo number_format((float)$r['qty']); ?></td><td class="text-right"><?php echo $money($r['amount']); ?></td></tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ລາຍວັນ -->
      <div class="ch-panel">
        <h2>ລາຍຮັບລາຍວັນ</h2>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 ch-table">
            <thead><tr><th>ວັນທີ</th><th class="text-right">ໜ້າຮ້ານ</th><th class="text-right">ເວັບໄຊ</th><th class="text-right">ລວມ</th><th>ຊ່ອງທາງທີ່ສູງກວ່າ</th></tr></thead>
            <tbody>
              <?php if (!$daily): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">ບໍ່ມີລາຍຮັບໃນຊ່ວງວັນທີນີ້</td></tr>
              <?php endif; ?>
              <?php foreach ($daily as $d => $v):
                $s = $v['shop'] ?? 0; $w = $v['web'] ?? 0; ?>
                <tr>
                  <td><?php echo date('d/m/Y', strtotime($d)); ?></td>
                  <td class="text-right"><?php echo $money($s); ?> <small class="text-muted">(<?php echo (int)($v['shop_n'] ?? 0); ?>)</small></td>
                  <td class="text-right"><?php echo $money($w); ?> <small class="text-muted">(<?php echo (int)($v['web_n'] ?? 0); ?>)</small></td>
                  <td class="text-right font-weight-bold"><?php echo $money($s + $w); ?></td>
                  <td><?php echo $s > $w ? '<span class="ch-tag is-shop">ໜ້າຮ້ານ</span>' : ($w > $s ? '<span class="ch-tag is-web">ເວັບໄຊ</span>' : '<span class="ch-tag">ເທົ່າກັນ</span>'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if ($daily): ?>
              <tfoot><tr><th>ລວມ</th><th class="text-right"><?php echo $money($shop['revenue']); ?></th><th class="text-right"><?php echo $money($web['revenue']); ?></th><th class="text-right"><?php echo $money($totalRevenue); ?></th><th></th></tr></tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
