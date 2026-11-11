<?php
/**
 * ໜ້ານະໂຍບາຍ ແລະ ເງື່ອນໄຂການໃຊ້ບໍລິການ — ແຍກສະແດງຕາມເມນູ (terms = ເງື່ອນໄຂການໃຊ້ບໍລິການ, privacy = ນະໂຍບາຍຄວາມເປັນສ່ວນຕົວ)
 * ໃນຮູບແບບກາດຍາວກາດດຽວ.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

$type = ($_GET['type'] ?? 'terms') === 'privacy' ? 'privacy' : 'terms';

if ($type === 'privacy') {
    $pageTitle = t('ນະໂຍບາຍຄວາມເປັນສ່ວນຕົວ');
    $pageDescription = t('ນະໂຍບາຍການເກັບກຳ, ນຳໃຊ້ ແລະ ປົກປ້ອງຂໍ້ມູນສ່ວນຕົວ ແລະ ຂໍ້ມູນທຸລະກິດຂອງລູກຄ້າ');
} else {
    $pageTitle = t('ເງື່ອນໄຂການໃຊ້ບໍລິການ');
    $pageDescription = t('ເງື່ອນໄຂການໃຫ້ບໍລິການລະບົບ POS, ອຸປະກອນ, ການຕິດຕັ້ງ ແລະ ການຮັບປະກັນຫຼັງການຂາຍ');
}

$currentPage = 'policy';
require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/components/page_hero.php';
?>
<section class="section">
    <div class="container">
        <?php if ($type === 'privacy'): ?>
            <div class="card" style="padding: 2.5rem;">
                <h2>1. <?php echo te('ການເກັບກຳຂໍ້ມູນ'); ?></h2>
                <p><?php echo te('ພວກເຮົາໃຫ້ຄວາມສຳຄັນສູງສຸດຕໍ່ຄວາມປອດໄພຂອງຂໍ້ມູນລູກຄ້າ ແລະ ຂໍ້ມູນການຂາຍຂອງຮ້ານຄ້າ ໂດຍເກັບກຳສະເພາະຂໍ້ມູນທີ່ຈຳເປັນຕໍ່ການໃຫ້ບໍລິການ:'); ?></p>
                <ul class="check-list" style="margin-bottom: 2rem;">
                    <li><?php echo te('ຂໍ້ມູນການຕິດຕໍ່ ເຊັ່ນ: ຊື່-ນາມສະກຸນ, ຊື່ຮ້ານ/ບໍລິສັດ, ເບີໂທລະສັບ, WhatsApp, ອີເມວ ແລະ ທີ່ຢູ່ຈັດສົ່ງ ທີ່ລູກຄ້າແຈ້ງຜ່ານເວັບໄຊ.'); ?></li>
                    <li><?php echo te('ຂໍ້ມູນລາຍລະອຽດການສັ່ງຊື້ອຸປະກອນ POS, ແພັກເກດລະບົບ ແລະ ປະຫວັດການຮັບບໍລິການຫຼັງການຂາຍ.'); ?></li>
                </ul>

                <h2>2. <?php echo te('ວັດຖຸປະສົງການໃຊ້ຂໍ້ມູນ'); ?></h2>
                <ul class="check-list" style="margin-bottom: 2rem;">
                    <li><?php echo te('ໃຊ້ເພື່ອຕິດຕໍ່ກັບ, ຢືນຢັນຄຳສັ່ງຊື້, ຈັດສົ່ງອຸປະກອນ ແລະ ນັດໝາຍຕິດຕັ້ງລະບົບ.'); ?></li>
                    <li><?php echo te('ໃຊ້ເພື່ອການດູແລຫຼັງການຂາຍ, ການອັບເດດລະບົບ ແລະ ການແກ້ໄຂບັນຫາດ້ານເຕັກນິກໃຫ້ແກ່ລູກຄ້າ.'); ?></li>
                </ul>

                <h2>3. <?php echo te('ການຮັກສາຄວາມປອດໄພຂອງຂໍ້ມູນຮ້ານຄ້າ'); ?></h2>
                <ul class="check-list" style="margin-bottom: 2rem;">
                    <li><?php echo te('ຂໍ້ມູນສິນຄ້າ, ສະຕັອກ, ລາຍຊື່ລູກຄ້າ ແລະ ຍອດຂາຍໃນລະບົບ POS ຖືກເກັບຮັກສາເປັນຄວາມລັບສູງສຸດຂອງຮ້ານຄ້າ.'); ?></li>
                    <li><?php echo te('ບໍ່ມີການເປີດເຜີຍ, ຈຳໜ່າຍ ຫຼື ສົ່ງຕໍ່ຂໍ້ມູນຂອງລູກຄ້າໃຫ້ບຸກຄົນທີສາມໂດຍບໍ່ໄດ້ຮັບອະນຸຍາດ.'); ?></li>
                </ul>

                <h2>4. <?php echo te('ສິດທິຂອງລູກຄ້າ ແລະ ຊ່ອງທາງຕິດຕໍ່'); ?></h2>
                <ul class="check-list">
                    <li><?php echo te('ລູກຄ້າສາມາດຕິດຕໍ່ທີມງານເພື່ອຂໍກວດສອບ, ແກ້ໄຂ ຫຼື ລຶບຂໍ້ມູນການຕິດຕໍ່ຂອງຕົນເອງໄດ້ທຸກເວລາ ໃນເວລາທຳການ ຈັນ–ເສົາ 8:30–17:00:'); ?></li>
                    <li><?php echo te('ໂທລະສັບ (ຄຣິກເພື່ອໂທເລີຍ):'); ?> <a href="tel:+8562029777302" style="font-weight:700;color:var(--primary);">020 29 777 302</a></li>
                    <li>WhatsApp: <a href="https://wa.me/8562055949007" target="_blank" rel="noopener" style="font-weight:700;color:#16a34a;">+856 20 55 949 007</a> / <a href="https://wa.me/8562095321848" target="_blank" rel="noopener" style="font-weight:700;color:#16a34a;">+856 20 95 321 848</a></li>
                    <li>Facebook Page: <a href="<?php echo e(fbChatUrl()); ?>" target="_blank" rel="noopener" style="font-weight:700;color:#1877f2;">Wlaodev Facebook Page</a></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="card" style="padding: 2.5rem;">
                <h2>1. <?php echo te('ຂອບເຂດການໃຫ້ບໍລິການ'); ?></h2>
                <p><?php echo te('%s ໃຫ້ບໍລິການພັດທະນາ ແລະ ຕິດຕັ້ງລະບົບຂາຍໜ້າຮ້ານ (POS), ຈຳໜ່າຍອຸປະກອນ POS ຄົບຊຸດ ແລະ ພັດທະນາເວັບໄຊ ສຳລັບຮ້ານຄ້າ ແລະ ທຸລະກິດໃນ ສປປ ລາວ.', $company['name']); ?></p>
                <ul class="check-list" style="margin-bottom: 2rem;">
                    <li><?php echo te('ຕິດຕັ້ງລະບົບ ແລະ ຕັ້ງຄ່າອຸປະກອນພ້ອມໃຊ້ງານ ໂດຍທີມງານຜູ້ຊ່ຽວຊານ.'); ?></li>
                    <li><?php echo te('ຝຶກອົບຮົມການໃຊ້ງານໃຫ້ເຈົ້າຂອງຮ້ານ ແລະ ພະນັກງານ ພ້ອມຄູ່ມືພາສາລາວ.'); ?></li>
                    <li><?php echo te('ໃຫ້ຄຳປຶກສາ ແລະ ຊ່ວຍເຫຼືອດ້ານເຕັກນິກໃນເວລາທຳການ ຈັນ–ເສົາ 8:30–17:00.'); ?></li>
                </ul>

                <h2>2. <?php echo te('ການສອບຖາມລາຄາ ແລະ ການສັ່ງຊື້'); ?></h2>
                <ul class="check-list" style="margin-bottom: 2rem;">
                    <li><?php echo te('ລາຄາສິນຄ້າ, ອຸປະກອນ POS ແລະ ແພັກເກດລະບົບ ແມ່ນບໍ່ສະແດງຢູ່ໜ້າເວັບໄຊ — ລູກຄ້າສາມາດຕິດຕໍ່ສອບຖາມລາຄາ ແລະ ຂໍໃບສະເໜີລາຄາໂດຍກົງນຳທີມງານ ເພື່ອຮັບຂໍ້ສະເໜີທີ່ເໝາະສົມກັບທຸລະກິດ.'); ?></li>
                    <li><?php echo te('ໂທລະສັບ (ຄຣິກເພື່ອໂທເລີຍ):'); ?> <a href="tel:+8562029777302" style="font-weight:700;color:var(--primary);">020 29 777 302</a></li>
                    <li>WhatsApp: <a href="https://wa.me/8562055949007" target="_blank" rel="noopener" style="font-weight:700;color:#16a34a;">+856 20 55 949 007</a> / <a href="https://wa.me/8562095321848" target="_blank" rel="noopener" style="font-weight:700;color:#16a34a;">+856 20 95 321 848</a></li>
                    <li>Facebook Page: <a href="<?php echo e(fbChatUrl()); ?>" target="_blank" rel="noopener" style="font-weight:700;color:#1877f2;">Wlaodev Facebook Page</a></li>
                    <li><?php echo te('ທຸກຄຳສັ່ງຊື້ ຈະມີພະນັກງານຢືນຢັນລາຍການອຸປະກອນ ແລະ ສະຖານທີ່ຈັດສົ່ງ/ຕິດຕັ້ງກ່ອນທຸກຄັ້ງ.'); ?></li>
                </ul>

                <h2>3. <?php echo te('ການຕິດຕັ້ງ ແລະ ການຝຶກອົບຮົມ'); ?></h2>
                <ul class="check-list" style="margin-bottom: 2rem;">
                    <li><?php echo te('ບໍລິການຕິດຕັ້ງເຖິງສະຖານທີ່ ຫຼື ຜ່ານລະບົບອອນລາຍ (Remote) ຕາມຄວາມສະດວກຂອງລູກຄ້າ.'); ?></li>
                    <li><?php echo te('ສອນການໃຊ້ງານຕັ້ງແຕ່ການເພີ່ມສິນຄ້າ, ການຂາຍໜ້າຮ້ານ, ການຕັດສະຕັອກ ຈົນເຖິງການເບິ່ງລາຍງານຍອດຂາຍ.'); ?></li>
                </ul>

                <h2>4. <?php echo te('ການຮັບປະກັນອຸປະກອນ ແລະ ການດູແລຫຼັງການຂາຍ'); ?></h2>
                <ul class="check-list">
                    <li><?php echo te('ອຸປະກອນ POS ທຸກຊະນິດມີການຮັບປະກັນຕາມເງື່ອນໄຂມາດຕະຖານ (ກໍລະນີເກີດຈາກຄວາມຜິດພາດຂອງໂຮງງານຜະລິດ) ພ້ອມບໍລິການກວດເຊັກ ແລະ ແກ້ໄຂ.'); ?></li>
                    <li><?php echo te('ດູແລລະບົບຊອບແວ ແລະ ໃຫ້ຄຳປຶກສາຕະຫຼອດອາຍຸການໃຊ້ງານແພັກເກດ.'); ?></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
