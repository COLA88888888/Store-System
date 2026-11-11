<?php
/**
 * ສ່ວນທ້າຍຂອງທຸກໜ້າ: ພື້ນສີເຂັ້ມລາຍຕາຕະລາງ + ລິ້ງໝວດລະບົບ POS, ອຸປະກອນ, ກ່ຽວກັບ, ຕິດຕໍ່
 * ແລະ ແຖບທ້າຍສີດຽວກັນ ມີເສັ້ນຂາວບາງຄັ່ນ (3 ກ່ອງສະຖິຕິ: ຜູ້ເຂົ້າຊົມມື້ນີ້/ທັງໝົດ, ສະມາຊິກ — models/visitors.php,
 * ລິຂະສິດ), ໄຟລ໌ JS, ແລ້ວປິດ </body></html>.
 * ໜ້າທີ່ຕ້ອງການ JS ເພີ່ມ ໃຫ້ຕັ້ງ $pageScripts = ['js/xxx.js'] ກ່ອນ require ໄຟລ໌ນີ້.
 */
$ftTel       = '020 29 777 302';
$ftTelDigits = '+8562029777302';
$ftWaLabel   = '+856 20 55 949 007';
$ftWaHref    = 'https://wa.me/8562055949007';
// WhatsApp ທຸກເບີ (ສະແດງເປັນແຖວລະເບີ ໃນ "ຕິດຕໍ່ພວກເຮົາ") — ເພີ່ມເບີ = ເພີ່ມແຖວນີ້
$ftWaList    = [
    [$ftWaLabel, $ftWaHref],
    ['+856 20 95 321 848', 'https://wa.me/8562095321848'],
];
$ftFbHref    = fbChatUrl();   // ເປີດ Messenger ແຊັດທັນທີ
$ftAddr      = (string)$company['address'];   // ທີ່ຢູ່ ແລະ ພິກັດ ຕັ້ງໃນ backend → ປັກໝຸດຕຳແໜ່ງຮ້ານ (models/company.php)
$ftLat       = (string)$company['lat'];   // ປັກໝຸດໃນ backend → ປັກໝຸດຕຳແໜ່ງຮ້ານ
$ftLng       = (string)$company['lng'];
$ftPinQuery  = $ftLat . ',' . $ftLng . ' (' . rawurlencode('WlaoDev POS') . ')';
$ftMapHref   = !empty($company['maps_url']) ? $company['maps_url'] : ('https://www.google.com/maps/place/WlaoDev+POS/@' . $ftLat . ',' . $ftLng . ',17z');
$ftMapEmbed  = 'https://maps.google.com/maps?q=' . rawurlencode('WlaoDev POS, ' . $ftLat . ',' . $ftLng) . '&hl=lo&z=17&ie=UTF8&iwloc=B&output=embed';
$ftPosItems  = $siteSolutions['pos']['items'] ?? [];
$ftHwItems   = array_slice($siteSolutions['hardware']['items'] ?? [], 0, 7);
$ftStats     = getSiteStats($pdo);
?>
</main>
<footer class="site-footer">
    <div class="footer-main">
        <div class="container footer-grid">
            <div>
                <h4><?php echo te('ລະບົບ POS'); ?></h4>
                <ul class="footer-links">
                    <?php foreach ($ftPosItems as $it): ?>
                        <li><a href="<?php echo url('pages/solution.php?slug=' . urlencode($it['slug'])); ?>"><?php echo e($it['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h4><?php echo te('ອຸປະກອນ POS'); ?></h4>
                <ul class="footer-links">
                    <?php foreach ($ftHwItems as $it): ?>
                        <li><a href="<?php echo url('pages/solution.php?slug=' . urlencode($it['slug'])); ?>"><?php echo e($it['title']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo url('pages/equipment_list.php'); ?>"><?php echo te('ອຸປະກອນ POS ທັງໝົດ'); ?></a></li>
                </ul>
            </div>

            <div>
                <h4><?php echo te('ກ່ຽວກັບພວກເຮົາ'); ?></h4>
                <ul class="footer-links">
                    <li><a href="<?php echo url('pages/about.php'); ?>"><?php echo te('ພວກເຮົາແມ່ນໃຜ'); ?></a></li>
                    <li><a href="<?php echo url('pages/services.php'); ?>"><?php echo te('ບໍລິການຂອງພວກເຮົາ'); ?></a></li>
                    <?php if (hasWorks()): ?><li><a href="<?php echo url('pages/portfolio.php'); ?>"><?php echo te('ຜົນງານຕິດຕັ້ງລະບົບ'); ?></a></li><?php endif; ?>
                    <li><a href="<?php echo url('pages/contact.php'); ?>"><?php echo te('ຂໍໃບສະເໜີລາຄາ'); ?></a></li>
                    <li><a href="<?php echo url('pages/policy.php?type=terms'); ?>"><?php echo te('ເງື່ອນໄຂການໃຊ້ບໍລິການ'); ?></a></li>
                    <li><a href="<?php echo url('pages/policy.php?type=privacy'); ?>"><?php echo te('ນະໂຍບາຍຄວາມເປັນສ່ວນຕົວ'); ?></a></li>
                </ul>
            </div>

            <div>
                <h4><?php echo te('ຕິດຕໍ່ພວກເຮົາ'); ?></h4>
                <ul class="footer-links footer-contact">
                    <li>
                        <a href="tel:<?php echo e($ftTelDigits); ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                            <span><?php echo e($ftTel); ?></span>
                        </a>
                    </li>
                    <?php foreach ($ftWaList as [$waLabel, $waHref]): ?>
                    <li>
                        <a href="<?php echo e($waHref); ?>" target="_blank" rel="noopener">
                            <svg class="ico-wa" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.5 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1.1.1-1.8-.1a16.4 16.4 0 0 1-6.5-5.7c-.9-1.2-1.5-2.6-1.5-3.9 0-1.3.7-2 1-2.3.3-.3.6-.3.8-.3h.6c.2 0 .5 0 .7.5.2.6.8 2 .9 2.1.1.2.1.4 0 .6-.1.2-.2.4-.4.6l-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.4 1.8 2.2 1.2 1.1 2.2 1.4 2.5 1.6.3.1.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.4-.2.7-.1.3.1 1.8.9 2.1 1 .3.2.5.2.6.4.1.1.1.7-.1 1.3z"/></svg>
                            <span>WhatsApp: <?php echo e($waLabel); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li>
                        <a href="<?php echo e($ftFbHref); ?>" target="_blank" rel="noopener">
                            <svg class="ico-fb" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12z"/></svg>
                            <span>Facebook Page</span>
                        </a>
                    </li>
                    <?php if ($company['email']): ?>
                        <li>
                            <a href="mailto:<?php echo e($company['email']); ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                                <span><?php echo e($company['email']); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="footer-addr">
                        <a href="<?php echo e($ftMapHref); ?>" target="_blank" rel="noopener">
                            <svg class="ico-pin" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                            <span><?php echo te($ftAddr); ?></span>
                        </a>
                    </li>
                    <li class="footer-hours"><?php echo te('ຈັນ–ອາທິດ 8:30–17:00'); ?></li>
                </ul>
                <div class="footer-map">
                    <?php $mapSrc = $ftMapEmbed; $mapHref = $ftMapHref; $mapTitle = t($ftAddr); $mapHeight = 135;
                          require dirname(__DIR__) . '/components/map_embed.php'; ?>
                </div>
            </div>
        </div>
        <div class="footer-watermark" aria-hidden="true"><?php echo e($company['name']); ?></div>
    </div>

    <div class="footer-bottom">
        <!-- ເສັ້ນຂາວຜ່ານກາງກ່ອງ (.footer-stats::before); .footer-stats-group ມີພື້ນສີດຽວກັບ footer ຈຶ່ງບັງເສັ້ນລະຫວ່າງກ່ອງ -->
        <div class="footer-stats">
            <div class="footer-stats-group">
                <div class="footer-stat">
                    <span class="footer-stat-label">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php echo te('ຜູ້ເຂົ້າຊົມມື້ນີ້'); ?>
                    </span>
                    <span class="footer-stat-num"><strong data-count="<?php echo (int)$ftStats['today']; ?>"><?php echo number_format($ftStats['today']); ?></strong> <?php echo te('ທ່ານ'); ?></span>
                </div>
                <div class="footer-stat">
                    <span class="footer-stat-label">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><circle cx="12" cy="7.5" r="4.5"/><path d="M3 21a9 7 0 0 1 18 0z"/></svg>
                        <?php echo te('ຜູ້ເຂົ້າຊົມທັງໝົດ'); ?>
                    </span>
                    <span class="footer-stat-num"><strong data-count="<?php echo (int)$ftStats['total']; ?>"><?php echo number_format($ftStats['total']); ?></strong> <?php echo te('ທ່ານ'); ?></span>
                </div>
                <div class="footer-stat">
                    <span class="footer-stat-label">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M1.5 21a7.5 6.5 0 0 1 15 0z"/><circle cx="17.5" cy="9" r="3"/><path d="M15.5 14.2A6 6 0 0 1 22.5 20h-4.6a9 9 0 0 0-2.4-5.8z"/></svg>
                        <?php echo te('ສະມາຊິກ'); ?>
                    </span>
                    <span class="footer-stat-num"><strong data-count="<?php echo (int)$ftStats['members']; ?>"><?php echo number_format($ftStats['members']); ?></strong> <?php echo te('ທ່ານ'); ?></span>
                </div>
            </div>
        </div>
        <div class="container footer-bottom-inner">
            <span></span>
            <div class="footer-copy">© <?php echo date('Y'); ?> <?php echo e($company['name']); ?>. All Rights Reserved.</div>
            <a class="footer-top" href="#top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' }); return false;">
                <?php echo te('ກັບຂຶ້ນດ້ານເທິງ'); ?>
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="currentColor"/><path d="m7.5 13.5 4.5-4.5 4.5 4.5" fill="none" stroke="#0a3b8c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>
</footer>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.WLAO_TRACK_URL = <?php echo json_encode(url('api/track_click.php')); ?>; /* ນັບການກົດປຸ່ມຕິດຕໍ່ (main.js) — ທຸກໜ້າ */</script>
<?php if (!empty($GLOBALS['dbTrMissing'])): /* ໜ້ານີ້ມີຊື່ສິນຄ້າ/ໝວດທີ່ຍັງບໍ່ໄດ້ແປເປັນພາສາທີ່ເລືອກ → ສັ່ງແປໃນເບື້ອງຫຼັງ (models/translations.php) */ ?>
<script>try { navigator.sendBeacon(<?php echo json_encode(url('api/translate_missing.php')); ?>); } catch (e) {}</script>
<?php endif; ?>
<?php if (visitBeaconNeeded()): /* ນັບຜູ້ເຂົ້າຊົມ: ສົ່ງຫຼັງໂຫຼດໜ້າ (ບັອດທີ່ບໍ່ແລ່ນ JS ບໍ່ຖືກນັບ) — models/visitors.php */ ?>
<script>window.addEventListener('load', function () { try { fetch(<?php echo json_encode(url('api/visit.php')); ?>, { method: 'POST', credentials: 'same-origin', keepalive: true }); } catch (e) {} });</script>
<?php endif; ?>
<?php if (!currentMember($pdo)): /* popup ສະໝັກ / ເຂົ້າສູ່ລະບົບສະມາຊິກ (ປຸ່ມ "ສະໝັກສະມາຊິກ" ໃນເມນູ) */ ?>
<?php require dirname(__DIR__) . '/components/member_modal.php'; ?>
<script src="<?php echo asset('js/member.js'); ?>"></script>
<?php else: /* ສະມາຊິກ: ກະດິ່ງແຈ້ງເຕືອນ (components/member_bell.php) */ ?>
<script src="<?php echo asset('js/member_bell.js'); ?>"></script>
<script src="<?php echo asset('js/member_logout.js'); ?>"></script><?php /* popup ຢືນຢັນອອກຈາກລະບົບ */ ?>
<?php endif; ?>
<!-- ປ້າຍໂລໂກ້ຕາມເມົາ — ສະເພາະພາກທີ່ມີ [data-cursor-zone] (assets/js/main.js) -->
<div class="cursor-badge" data-cursor-badge aria-hidden="true"><img src="<?php echo asset('img/logo.png'); ?>" alt="" width="15" height="15"><span><?php echo e($company['name']); ?></span></div>

<?php /* ອັດສຽງແບບ WhatsApp (ແຊັດລອຍ + ໜ້າແຊັດ) — ຂໍ້ຄວາມແປຕາມພາສາ */ ?>
<script>window.WLAO_VOICE_L = <?php echo json_encode([
    'cancel' => t('ຍົກເລີກ'), 'pause' => t('ຢຸດຊົ່ວຄາວ'), 'resume' => t('ອັດຕໍ່'), 'send' => t('ສົ່ງ'),
    'slideCancel' => t('ປັດຊ້າຍເພື່ອຍົກເລີກ'), 'lock' => t('ປັດຂຶ້ນເພື່ອລັອກ'),
    'noMic' => t('ບຣາວເຊີນີ້ບໍ່ຮອງຮັບການອັດສຽງ'), 'denied' => t('ກະລຸນາອະນຸຍາດໃຫ້ໃຊ້ໄມໂຄຣໂຟນ ເພື່ອອັດສຽງ'),
    'https' => t('ການອັດສຽງໃຊ້ໄດ້ສະເພາະ https'), 'silent' => t('ບໍ່ໄດ້ຍິນສຽງ — ກະລຸນາກວດໄມໂຄຣໂຟນ ແລ້ວລອງໃໝ່'),
], JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="<?php echo asset('js/voice_rec.js'); ?>"></script>
<script>window.WLAO_SEND_L = <?php echo json_encode(['title' => t('ສົ່ງໄຟລ໌ນີ້?'), 'titleN' => t('ສົ່ງ %d ລາຍການນີ້?'), 'send' => t('ສົ່ງ'), 'cancel' => t('ຍົກເລີກ'), 'remove' => t('ເອົາອອກ')], JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="<?php echo asset('js/chat_confirm.js'); ?>"></script><?php /* ຢືນຢັນກ່ອນສົ່ງຮູບ/ໄຟລ໌ໃນແຊັດ */ ?>
<!-- ປຸ່ມແຊັດລອຍ (Floating Chat Widget) — ບໍ່ສະແດງເມື່ອຢູ່ໃນໜ້າຕິດຕໍ່/ຫ້ອງແຊັດ -->
<?php if (($currentPage ?? '') !== 'contact' && ($GLOBALS['currentPage'] ?? '') !== 'contact'): ?>
<?php require dirname(__DIR__) . '/components/floating_chat.php'; ?>
<script src="<?php echo asset('js/floating_chat.js'); ?>"></script>
<?php endif; ?>

<?php /* ແຖບ ໂທ / WhatsApp / ແຊັດ ຕິດລຸ່ມຈໍມືຖື (.mbar) ເອົາອອກແລ້ວ 11/2026 — ຕິດຕໍ່ຜ່ານປຸ່ມແຊັດລອຍ ແລະ ເມນູ */ ?>

<script src="<?php echo asset('js/main.js'); ?>"></script>
<script src="<?php echo asset('js/cart.js'); ?>"></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
<script src="<?php echo asset($script); ?>"></script>
<?php endforeach; ?>
</body>
</html>
