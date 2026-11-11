<?php
/**
 * popup ຂອງຫ້ອງແຊັດ (pages/contact.php) — ເປີດດ້ວຍໄອຄອນ ? / ⚙ ([data-cr-open="help|settings"], assets/js/chat_room.js):
 *   help     = "ວິທີສັ່ງຊື້" 3 ຂັ້ນຕອນ + ຄຳແນະນຳ + ປຸ່ມ "ເຂົ້າໃຈແລ້ວ"
 *   settings = ເວີຊັນ + ການແຈ້ງເຕືອນ (ສຽງ, ແຈ້ງເຕືອນຂອງ browser) + ສະຕິກເກີ (ຢືນຢັນກ່ອນສົ່ງ, ໂຫຼດຂໍ້ມູນໃໝ່) + ທີມສີ 5 ແບບ
 * ຄ່າທີ່ຕັ້ງເກັບໃນ localStorage ຂອງ browser ນັ້ນ (WlaoDev.chatSettings). CSS: .crm-* ໃນ assets/css/chat_room.css
 */
$crmSteps = [
    [t('ສອບຖາມ ແລະ ເລືອກສິນຄ້າ'), [t('ແຊັດຖາມລາຍລະອຽດ ແລະ ລາຄາກັບທີມງານ'), t('ເລືອກສິນຄ້າ ແລະ ຈຳນວນ'), t('ກົດ "ສັ່ງຊື້ເລີຍ" ຫຼື ໃສ່ກະຕ່າ')]],
    [t('ໂອນເງິນ ແລະ ແນບສະລິບ'), [t('ເລືອກທະນາຄານ ແລະ ໂອນຕາມຍອດ'), t('ແນບຮູບສະລິບໃນໜ້າອໍເດີ'), t('ທີມງານກວດຍອດ ແລະ ຢືນຢັນອໍເດີ')]],
    [t('ຈັດສົ່ງ ແລະ ຮັບເຄື່ອງ'), [t('ທີມງານແພັກ ແລະ ຝາກເຄື່ອງກັບຂົນສົ່ງ'), t('ຮັບເລກໃບຝາກເຄື່ອງທາງອີເມວ ແລະ ໃນໜ້າອໍເດີ'), t('ໄປຮັບເຄື່ອງທີ່ສາຂາຂົນສົ່ງປາຍທາງ')]],
];
// [ຊື່, ສີພື້ນ, ສີຟອງຂໍ້ຄວາມຂອງລູກຄ້າ] — ຄືກັບ .cr[data-theme] ໃນ assets/css/chat_room.css
$crmThemes = [
    'default' => ['Default', '#eef1f6', '#1d4ed8'],
    'sky'     => ['Sky Line', '#9ab6ee', '#a3e635'],
    'sun'     => ['Sunny', '#fff7c2', '#facc15'],
    'mint'    => ['Mint', '#d3f2df', '#047857'],
    'rose'    => ['Rose', '#fde9e4', '#c81e1e'],
];
// ເວີຊັນ = ວັນທີແກ້ໄຂລ່າສຸດຂອງ JS ຫ້ອງແຊັດ (ປ່ຽນເອງທຸກຄັ້ງທີ່ອັບເດດ)
$crmVersion = date('Y.m.d', max(array_map(fn($f) => (int)@filemtime(dirname(__DIR__) . '/assets/js/' . $f), ['chat.js', 'chat_room.js', 'chat_stickers.js'])));
$crmX = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
?>
<div class="crm" data-crm="help" hidden>
    <div class="crm-backdrop" data-crm-close></div>
    <div class="crm-box" role="dialog" aria-modal="true" aria-labelledby="crmHelpTitle">
        <button type="button" class="crm-x" data-crm-close aria-label="<?php echo te('ປິດ'); ?>"><?php echo $crmX; ?></button>
        <h2 id="crmHelpTitle"><?php echo te('ວິທີສັ່ງຊື້'); ?></h2>
        <div class="crm-body">
            <?php foreach ($crmSteps as $i => [$title, $points]): ?>
                <div class="crm-step">
                    <span class="crm-num"><?php echo $i + 1; ?></span>
                    <div>
                        <b><?php echo e($title); ?></b>
                        <ul><?php foreach ($points as $pt): ?><li><?php echo e($pt); ?></li><?php endforeach; ?></ul>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="crm-tip-head"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg> <?php echo te('ຄຳແນະນຳເພີ່ມເຕີມ'); ?></div>
            <div class="crm-tip">
                <p><?php echo te('ກະລຸນາໂອນເງິນເຂົ້າບັນຊີທີ່ສະແດງໃນໜ້າສັ່ງຊື້ຂອງເວັບ WlaoDev ເທົ່ານັ້ນ — ທີມງານບໍ່ມີນະໂຍບາຍໃຫ້ໂອນຜ່ານລິ້ງ ຫຼື ບັນຊີອື່ນ.'); ?></p>
                <p><?php echo te('ຫາກມີຄຳຖາມ ຫຼື ຂໍ້ສົງໄສເພີ່ມເຕີມ ກະລຸນາແຊັດ ຫຼື ໂທ'); ?> <a href="tel:+8562029777302">020 29 777 302</a></p>
            </div>
        </div>
        <button type="button" class="crm-ok" data-crm-close><?php echo te('ເຂົ້າໃຈແລ້ວ'); ?></button>
    </div>
</div>

<div class="crm" data-crm="settings" hidden>
    <div class="crm-backdrop" data-crm-close></div>
    <div class="crm-box" role="dialog" aria-modal="true" aria-labelledby="crmSetTitle">
        <button type="button" class="crm-x" data-crm-close aria-label="<?php echo te('ປິດ'); ?>"><?php echo $crmX; ?></button>
        <h2 id="crmSetTitle"><?php echo te('ຕັ້ງຄ່າການໃຊ້ງານ'); ?></h2>
        <p class="crm-version">version <?php echo e($crmVersion); ?></p>
        <div class="crm-body">
            <div class="crm-group"><?php echo te('ການແຈ້ງເຕືອນ'); ?></div>
            <label class="crm-row">
                <span class="crm-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/></svg></span>
                <span class="crm-text"><b><?php echo te('ສຽງແຈ້ງເຕືອນ'); ?></b><small><?php echo te('ຫຼິ້ນສຽງເມື່ອມີຂໍ້ຄວາມໃໝ່ຈາກທີມງານ'); ?></small></span>
                <input type="checkbox" class="crm-switch" data-crm-set="sound">
            </label>
            <div class="crm-row">
                <span class="crm-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg></span>
                <span class="crm-text"><b><?php echo te('ການຮັບແຈ້ງເຕືອນ'); ?></b><small data-crm-notify-state><?php echo te('ແຈ້ງເຕືອນຂອງ browser ເມື່ອປິດແທັບແຊັດໄວ້'); ?></small></span>
                <button type="button" class="crm-btn" data-crm-notify><?php echo te('ເປີດໃຊ້ງານ'); ?></button>
            </div>
            <div class="crm-group"><?php echo te('ສະຕິກເກີ'); ?></div>
            <?php /* ຢືນຢັນກ່ອນສົ່ງສະຕິກເກີ: ບັງຄັບສະເໝີແລ້ວ (assets/js/chat_stickers.js) — ບໍ່ມີສະວິດປິດ */ ?>
            <div class="crm-row">
                <span class="crm-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.3-4.9L4 8"/><path d="M4 3v5h5"/><path d="M4 13a8 8 0 0 0 14.3 4.9L20 16"/><path d="M20 21v-5h-5"/></svg></span>
                <span class="crm-text"><b><?php echo te('ໂຫຼດຂໍ້ມູນສະຕິກເກີໃໝ່'); ?></b><small><?php echo te('ລ້າງແຄຊສະຕິກເກີ ແລະ ໂຫຼດໃໝ່ຈາກເຊີບເວີ'); ?></small></span>
                <button type="button" class="crm-btn is-outline" data-crm-stk-refresh data-done="<?php echo te('ໂຫຼດໃໝ່ແລ້ວ'); ?>"><?php echo te('ໂຫຼດໃໝ່'); ?></button>
            </div>
            <div class="crm-group"><?php echo te('ປັບແຕ່ງແຊັດ'); ?></div>
            <div class="crm-row">
                <span class="crm-ico"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22a10 10 0 1 1 10-10c0 2.8-2.2 4-4.5 4H16a2 2 0 0 0-1.5 3.3A1.6 1.6 0 0 1 12 22z"/><circle cx="7.5" cy="11" r="1.2" fill="currentColor"/><circle cx="10.5" cy="7" r="1.2" fill="currentColor"/><circle cx="15" cy="7.5" r="1.2" fill="currentColor"/></svg></span>
                <span class="crm-text"><b><?php echo te('ປ່ຽນທີມຫ້ອງແຊັດ'); ?></b></span>
                <span class="crm-theme-name" data-crm-theme-name>Default</span>
            </div>
            <div class="crm-themes">
                <?php foreach ($crmThemes as $key => [$tName, $bg, $bubble]): /* ກາດຕົວຢ່າງ: ພື້ນເຕັມກາດ + ຟອງລູກຄ້າ (ສີທີມ) + ຟອງທີມງານ (ຂາວ) */ ?>
                    <button type="button" class="crm-theme" data-crm-theme="<?php echo e($key); ?>" data-name="<?php echo e($tName); ?>" style="--bg: <?php echo $bg; ?>; --bubble: <?php echo $bubble; ?>" aria-label="<?php echo e($tName); ?>">
                        <i class="crm-b1"></i><i class="crm-b2"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
