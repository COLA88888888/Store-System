<?php
/**
 * ໜ້າຕິດຕໍ່ = ຫ້ອງແຊັດແບບແອັບ 3 ຖັນ (css/chat_room.css):
 *   1) ແຖບໄອຄອນແຄບ (components/chat_rail.php: ແຊັດ / ຄຳສັ່ງຊື້ / ຕັ້ງຄ່າ / ຊ່ວຍເຫຼືອ)  2) ຫ້ອງແຊັດສົດ (ພິມ / ຕຳແໜ່ງ 📍 / ອັດສຽງ)
 *   3) ໂປຣໄຟລ໌ WlaoDev + ແທັບ: ຄຳສັ່ງຊື້ຂອງສະມາຊິກ / ມີເດຍ ແລະ ໄຟລ໌ (ຮູບ, ວິດີໂອ, ເອກະສານທີ່ສົ່ງໃນແຊັດ) / ຕິດຕໍ່ (ຊ່ອງທາງ + ຂັ້ນຕອນ + ແຜນທີ່)
 * ແຊັດໄດ້ສະເພາະສະມາຊິກ — 1 ສະມາຊິກ = 1 ຫ້ອງ (models/chat.php chatCurrent); ຄົນທົ່ວໄປເຫັນແຖບລົງທະບຽນ.
 * ແຊັດເຮັດວຽກຜ່ານ api/chat.php (models/chat.php) ໂດຍ assets/js/chat.js.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = t('ຕິດຕໍ່ພວກເຮົາ');
$pageDescription = t('ຕິດຕໍ່ Wlaodev: ໂທ 020 29 777 302, WhatsApp ຫຼື ແຊັດ — ປຶກສາລະບົບ POS, ເວັບໄຊ ແລະ ອຸປະກອນຟຣີ, ຕິດຕັ້ງຟຣີ ສອນໃຊ້ງານໃຫ້ຟຣີ');
$currentPage = 'contact';
$pageScripts = ['js/chat.js', 'js/chat_room.js', 'js/chat_stickers.js'];   // chat_stickers.js: ແຜງສະຕິກເກີ + ຢືນຢັນກ່ອນສົ່ງ   // chat_room.js: ແທັບ, popup ຊ່ວຍເຫຼືອ/ຕັ້ງຄ່າ, ສຽງ + ແຈ້ງເຕືອນ, ທີມສີ
$subject = mb_substr(trim((string)($_GET['subject'] ?? '')), 0, 200);
$autoText = $subject !== ''
    ? (preg_match('/^ສົນໃຈ/u', $subject) ? $subject : t('ສົນໃຈ %s', $subject))
    : '';
$quick = [];
foreach ($siteSolutions['pos']['items'] ?? [] as $it) {
    $msg = t('ສົນໃຈລະບົບ %s', $it['title']);
    $quick[] = [$msg, $msg];
}
foreach ($siteSolutions['hardware']['items'] ?? [] as $it) {
    $msg = t('ສົນໃຈ %s', $it['title']);
    $quick[] = [$msg, $msg];
}
$quick[] = [t('ລາຄາເທົ່າໃດ?'), t('ລາຄາເທົ່າໃດ?')];
$quick[] = [t('ຕິດຕັ້ງຟຣີ ສອນໃຊ້ງານໃຫ້ຟຣີບໍ?'), t('ຕິດຕັ້ງຟຣີ ສອນໃຊ້ງານໃຫ້ຟຣີບໍ?')];
$quick[] = [t('ລົມກັບພະນັກງານ'), t('ລົມກັບພະນັກງານ')];

$pageStyles = ['css/chat_room.css'];
// ຂໍ້ມູນ 3 ຖັນ (ຫ້ອງແຊັດແບບແອັບ): ລາຍຊື່ແຊັດ | ແຊັດ | ໂປຣໄຟລ໌ + ຄຳສັ່ງຊື້ + ມີເດຍ — ສະມາຊິກເຫັນສະເພາະຫ້ອງ/ອໍເດີຂອງຕົນ
$crMember = currentMember($pdo);
$crChat = ($crMember && $pdo) ? chatCurrent($pdo) : null;
$crLast = null;
$crUnread = 0;
$crMedia = [];
$crOrders = ($crMember && $pdo) ? memberOrders($pdo, $crMember, '', 10) : [];
if ($crChat) {
    $st = $pdo->prepare("SELECT body, audio_path, sender, created_at FROM w_web_chat_messages WHERE chat_id = ? ORDER BY id DESC LIMIT 1");
    $st->execute([(int)$crChat['id']]);
    $crLast = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    $st = $pdo->prepare("SELECT COUNT(*) FROM w_web_chat_messages WHERE chat_id = ? AND sender <> 'visitor' AND id > ?");
    $st->execute([(int)$crChat['id'], (int)$crChat['visitor_read_id']]);
    $crUnread = (int)$st->fetchColumn();
    // ມີເດຍ ແລະ ໄຟລ໌: ຮູບ / ວິດີໂອ / ເອກະສານ ທີ່ສົ່ງໃນຫ້ອງນີ້ (ທັງສອງຝ່າຍ) — ເກັບໄວ້ເບິ່ງຄືນໄດ້ທັງໝົດ (ໃໝ່ → ເກົ່າ)
    $st = $pdo->prepare("SELECT id, sender, file_path, file_name, file_kind, file_size, created_at FROM w_web_chat_messages
                         WHERE chat_id = ? AND file_path IS NOT NULL ORDER BY id DESC LIMIT 500");
    $st->execute([(int)$crChat['id']]);
    $crMedia = $st->fetchAll(PDO::FETCH_ASSOC);
}
require dirname(__DIR__) . '/layouts/header.php';
?>
<section class="ct">
    <div class="container">
        <div class="ct-workspace cr">
            <?php $railActive = 'chat'; $railUnread = $crUnread; require dirname(__DIR__) . '/components/chat_rail.php'; /* ຖັນ 1: ແຖບໄອຄອນແຄບ */ ?>

            <!-- ຖັນ 2: ຫ້ອງແຊັດສົດ ([data-chat] → assets/js/chat.js) -->
            <?php $chatIsGuest = !currentMember($pdo); /* ແຊັດໄດ້ສະເພາະສະມາຊິກ */ ?><div class="ct-form chat<?php echo $chatIsGuest ? ' chat-guest' : ''; ?>" data-chat data-endpoint="<?php echo e(url('api/chat.php')); ?>" data-i18n="<?php echo e(json_encode([
                'name'     => $company['name'],
                'today'    => t('ມື້ນີ້'),
                'yesterday'=> t('ມື້ວານ'),
                'voice'    => t('ຂໍ້ຄວາມສຽງ'),
                'read'     => t('ອ່ານແລ້ວ'),
                'unread'   => t('ຍັງບໍ່ອ່ານ'),
                'fail'     => t('ສົ່ງບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່'),
                'noMic'    => t('ບຣາວເຊີນີ້ອັດສຽງບໍ່ໄດ້'),
                'micDenied'=> t('ກະລຸນາອະນຸຍາດໃຫ້ໃຊ້ໄມໂຄຣໂຟນ'),
                'needHttps'=> t('ອັດສຽງໄດ້ສະເພາະເວັບທີ່ເປັນ https (ຫຼື localhost) — ກະລຸນາພິມຂໍ້ຄວາມແທນ'),
                'silent'   => t('ບໍ່ໄດ້ຍິນສຽງຈາກໄມໂຄຣໂຟນ — ກວດເບິ່ງວ່າເລືອກໄມຖືກ ແລະ ບໍ່ໄດ້ປິດສຽງ ແລ້ວລອງໃໝ່'),
                'myLoc'    => t('ກຳລັງສົ່ງຕຳແໜ່ງ…'),
                'locHttps' => t('ສົ່ງຕຳແໜ່ງໄດ້ສະເພາະເວັບທີ່ເປັນ https (ຫຼື localhost) — ກົດ "ຂໍເສັ້ນທາງມາຮ້ານ" ແທນ'),
                'fileFail' => t('ສົ່ງໄຟລ໌ບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່'),
                'locDenied'=> t('ບໍ່ໄດ້ຮັບຕຳແໜ່ງ — ກະລຸນາອະນຸຍາດໃຫ້ເວັບໃຊ້ຕຳແໜ່ງ (Location) ແລ້ວລອງໃໝ່'),
            ], JSON_UNESCAPED_UNICODE)); ?>">
                <div class="chat-top">
                    <button type="button" class="cr-rail-open cr-mobile-only" data-cr-rail-open aria-label="<?php echo te('ເປີດເມນູ'); ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6.5h16M4 12h16M4 17.5h16"/></svg></button>
                    <a class="cr-back" href="<?php echo url(); ?>" onclick="if (history.length > 1 && document.referrer.indexOf(location.host) > -1) { history.back(); return false; }" aria-label="<?php echo te('ກັບຄືນ'); ?>"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19l-7-7 7-7"/></svg></a>
                    <button type="button" class="chat-avatar-btn" data-cr-open-media aria-label="<?php echo te('ເບິ່ງມີເດຍ ແລະ ໄຟລ໌'); ?>" title="<?php echo te('ເບິ່ງມີເດຍ ແລະ ໄຟລ໌'); ?>">
                        <span class="chat-avatar"><img src="<?php echo asset('img/logo.png'); ?>" alt="" width="30" height="20"></span>
                    </button>
                    <div class="chat-who" data-cr-open-media title="<?php echo te('ເບິ່ງມີເດຍ ແລະ ໄຟລ໌'); ?>">
                        <h1><?php echo e($company['name']); ?></h1>
                        <small><span class="chat-dot"></span><?php echo te('ອອນລາຍ'); ?></small>
                    </div>
                    <div class="cr-top-actions">
                        <a href="tel:+8562029777302" class="cr-top-phone" title="<?php echo te('ໂທສາຍດ່ວນ'); ?>" aria-label="<?php echo te('ໂທສາຍດ່ວນ'); ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg></a>
                        <a href="<?php echo e('https://wa.me/8562055949007?text=' . rawurlencode($autoText !== '' ? $autoText : t('ສົນໃຈລະບົບ'))); ?>" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp"><svg viewBox="0 0 24 24" width="21" height="21" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.7-.1 1.4z"/></svg></a>
                        <a class="cr-m-orders" href="<?php echo url('pages/my_orders.php'); ?>" title="<?php echo te('ຄຳສັ່ງຊື້'); ?>" aria-label="<?php echo te('ຄຳສັ່ງຊື້'); ?>"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg></a>
                        <button type="button" data-cr-toggle-info data-cr-open-media title="<?php echo te('ຂໍ້ມູນ ແລະ ໄຟລ໌'); ?>" aria-label="<?php echo te('ຂໍ້ມູນ ແລະ ໄຟລ໌'); ?>"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                    </div>
                </div>

                <?php /* ມືຖື: ແຖບ ແຊັດ | ອໍເດີ ໃຕ້ຫົວຫ້ອງແຊັດ (ແບບ Fastwork — CSS .cr-mtabs ໃນ chat_room.css) */ ?>
                <nav class="cr-mtabs" aria-label="<?php echo te('ແຊັດ'); ?>">
                    <span class="is-active"><?php echo te('ແຊັດ'); ?></span>
                    <a href="<?php echo url('pages/my_orders.php'); ?>"><?php echo te('ອໍເດີ'); ?></a>
                </nav>
                <div class="chat-log" data-chat-log aria-live="polite">
                    <?php if (!$crLast): /* ຍັງບໍ່ເຄີຍແຊັດ → ຮູບປະກອບກາງຈໍ (ຫາຍເອງເມື່ອມີຂໍ້ຄວາມທຳອິດ — CSS .chat-log:has(.chat-msg)) */ ?>
                        <div class="cr-start">
                            <svg viewBox="0 0 220 180" width="220" height="180" fill="none" stroke="#cbd5e1" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="110" cy="92" r="82" stroke="#e2e8f0"/>
                                <path d="M48 62h88a14 14 0 0 1 14 14v34a14 14 0 0 1-14 14H90l-24 20v-20H48a14 14 0 0 1-14-14V76a14 14 0 0 1 14-14z" fill="#fff"/>
                                <path d="M58 86h60M58 102h38"/>
                                <path d="M150 92h22a12 12 0 0 1 12 12v26a12 12 0 0 1-12 12h-6v16l-18-16h-10a12 12 0 0 1-12-12v-6" fill="#f8fafc"/>
                                <circle cx="150" cy="118" r="3" fill="#cbd5e1" stroke="none"/><circle cx="162" cy="118" r="3" fill="#cbd5e1" stroke="none"/><circle cx="174" cy="118" r="3" fill="#cbd5e1" stroke="none"/>
                            </svg>
                            <p><?php echo $chatIsGuest ? te('ລົງທະບຽນເປັນສະມາຊິກ ເພື່ອເລີ່ມສົນທະນາກັບທີມງານ') : te('ພິມຂໍ້ຄວາມດ້ານລຸ່ມ ເພື່ອເລີ່ມສົນທະນາກັບທີມງານ'); ?></p>
                        </div>
                    <?php else: ?>
                    <div class="chat-msg is-bot">
                        <div class="chat-bubble"><?php echo te('ສະບາຍດີ 🙏 ບອກພວກເຮົາວ່າຮ້ານຂອງທ່ານຕ້ອງການລະບົບ ຫຼື ອຸປະກອນແບບໃດ — ພວກເຮົາພ້ອມໃຫ້ຄຳປຶກສາຟຣີ, ຕິດຕັ້ງຟຣີ ແລະ ສອນໃຊ້ງານໃຫ້ຟຣີ. ພິມ ຫຼື ກົດໄມໂຄຣໂຟນເພື່ອສົ່ງສຽງໄດ້ເລີຍ.'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>


                <div class="chat-quick-wrap">
                    <button type="button" class="chat-quick-nav is-prev" data-chat-qprev aria-label="<?php echo te('ເລື່ອນຊ້າຍ'); ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <div class="chat-quick" data-chat-quick>
                        <?php foreach ($quick as [$qLabel, $qAsk]): ?>
                            <button type="button" data-chat-ask="<?php echo e($qAsk); ?>"><?php echo e($qLabel); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="chat-quick-nav is-next" data-chat-qnext aria-label="<?php echo te('ເລື່ອນຂວາ'); ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>

                <!-- ຝາກຊື່ ແລະ ເບີໂທ (ສະແດງຫຼັງສົ່ງຂໍ້ຄວາມທຳອິດ ຖ້າຍັງບໍ່ມີເບີ) -->
                <form class="chat-contact" data-chat-contact hidden>
                    <span><?php echo te('ຝາກເບີໂທ ໃຫ້ທີມງານໂທກັບ:'); ?></span>
                    <input name="name" maxlength="150" autocomplete="name" placeholder="<?php echo te('ຊື່ ຫຼື ຊື່ຮ້ານ'); ?>">
                    <input name="tel" type="tel" maxlength="50" autocomplete="tel" required placeholder="020 xxxx xxxx">
                    <button type="submit"><?php echo te('ບັນທຶກ'); ?></button>
                    <button type="button" class="chat-contact-x" data-chat-contact-close aria-label="<?php echo te('ປິດ'); ?>">×</button>
                </form>

                <?php if ($chatIsGuest) { require dirname(__DIR__) . '/components/chat_guest_bar.php'; } ?>
                <!-- ແຜງສະຕິກເກີ (assets/js/chat_stickers.js ເຕີມຈາກ api/chat.php?action=stickers) -->
                <div class="cr-stk" data-stk-panel hidden data-version="<?php echo e(CHAT_STICKERS_VERSION); ?>" data-endpoint="<?php echo e(url('api/chat.php')); ?>" data-i18n="<?php echo e(json_encode(['recent' => t('ໃຊ້ລ່າສຸດ'), 'fail' => t('ໂຫຼດສະຕິກເກີບໍ່ສຳເລັດ')], JSON_UNESCAPED_UNICODE)); ?>">
                    <div class="cr-stk-tabs" data-stk-tabs></div>
                    <div class="cr-stk-grid" data-stk-grid></div>
                </div>
                <!-- ຢືນຢັນກ່ອນສົ່ງສະຕິກເກີ (ເປີດ/ປິດໃນຕັ້ງຄ່າ) -->
                <div class="cr-stk-preview" data-stk-preview hidden>
                    <span class="cr-stk-big" data-stk-big></span>
                    <div>
                        <button type="button" class="cr-stk-cancel" data-stk-cancel><?php echo te('ຍົກເລີກ'); ?></button>
                        <button type="button" class="cr-stk-send" data-stk-send><?php echo te('ສົ່ງ'); ?></button>
                    </div>
                </div>
                <!-- ເມນູປຸ່ມບວກ + (ແນບເອກະສານ / ສົ່ງຕຳແໜ່ງ / ອໍເດີ / ຂໍ້ມູນຮ້ານ) -->
                <div class="cr-plus-menu" data-chat-plus-menu hidden>
                    <button type="button" class="cr-plus-item" data-chat-plus-doc>
                        <span class="cr-plus-icon cr-plus-icon-doc"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                        <span><?php echo te('ເອກະສານ'); ?></span>
                    </button>
                    <button type="button" class="cr-plus-item" data-chat-plus-loc>
                        <span class="cr-plus-icon cr-plus-icon-loc"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span>
                        <span><?php echo te('ຕຳແໜ່ງ'); ?></span>
                    </button>
                    <a class="cr-plus-item" href="<?php echo url('pages/my_orders.php'); ?>">
                        <span class="cr-plus-icon cr-plus-icon-order"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg></span>
                        <span><?php echo te('ອໍເດີ'); ?></span>
                    </a>
                    <button type="button" class="cr-plus-item" data-cr-open-media>
                        <span class="cr-plus-icon cr-plus-icon-info"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></span>
                        <span><?php echo te('ຂໍ້ມູນຮ້ານ'); ?></span>
                    </button>
                </div>

                <form class="chat-input" data-chat-form>
                    <button type="button" class="chat-mic chat-plus-btn" data-chat-plus aria-label="<?php echo te('ຕົວເລືອກເພີ່ມເຕີມ'); ?>" title="<?php echo te('ຕົວເລືອກເພີ່ມເຕີມ'); ?>">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <div class="chat-input-pill">
                        <textarea name="text" rows="1" maxlength="1000" placeholder="<?php echo te('ພິມຂໍ້ຄວາມສອບຖາມ…'); ?>" data-chat-text><?php echo e($autoText); ?></textarea>
                        <button type="button" class="chat-mic chat-stk-btn" data-chat-sticker aria-label="<?php echo te('ສະຕິກເກີ'); ?>" title="<?php echo te('ສະຕິກເກີ'); ?>">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.5 3H6a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h9.5a3 3 0 0 0 3-3V7.5L15.5 3z"/><path d="M15 3v5a1 1 0 0 0 1 1h5"/></svg>
                        </button>
                    </div>
                    <button type="button" class="chat-mic chat-cam-btn" data-chat-image aria-label="<?php echo te('ສົ່ງຮູບ ຫຼື ວິດີໂອ'); ?>" title="<?php echo te('ສົ່ງຮູບ ຫຼື ວິດີໂອ'); ?>">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </button>
                    <button type="button" class="chat-mic chat-mic-btn" data-chat-mic aria-label="<?php echo te('ອັດສຽງ'); ?>" title="<?php echo te('ອັດສຽງ'); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5"/></svg>
                    </button>
                    <button type="submit" class="chat-send" aria-label="<?php echo te('ສົ່ງ'); ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M22 2 2 9.5l7.5 3L19 5l-7.5 9.5V22l4-5.5 4.5 2z"/></svg>
                    </button>
                    <button type="button" data-chat-attach hidden aria-hidden="true" style="display:none"></button>
                    <button type="button" data-chat-loc hidden aria-hidden="true" style="display:none"></button>
                    <input type="file" data-chat-file hidden multiple accept="<?php echo e(chatFileAcceptDocs()); ?>" data-accept-docs="<?php echo e(chatFileAcceptDocs()); ?>" data-accept-media="<?php echo e(chatFileAcceptMedia()); ?>">
                </form>

                <!-- ກຳລັງອັດສຽງ -->
                <div class="chat-rec" data-chat-rec hidden>
                    <span class="chat-rec-dot"></span>
                    <span data-chat-rec-time>0:00</span>
                    <span class="chat-rec-hint"><?php echo te('ກຳລັງອັດສຽງ…'); ?> <span class="chat-rec-level"><i data-chat-rec-level></i></span></span>
                    <button type="button" class="chat-rec-cancel" data-chat-rec-cancel><?php echo te('ຍົກເລີກ'); ?></button>
                    <button type="button" class="chat-send" data-chat-rec-send aria-label="<?php echo te('ສົ່ງ'); ?>">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M22 2 2 9.5l7.5 3L19 5l-7.5 9.5V22l4-5.5 4.5 2z"/></svg>
                    </button>
                </div>
            </div>

            <!-- ຖັນ 3: ໂປຣໄຟລ໌ WlaoDev + ແທັບ ຄຳສັ່ງຊື້ / ມີເດຍ ແລະ ໄຟລ໌ / ຕິດຕໍ່ (ສະຫຼັບແທັບ: script ລຸ່ມສຸດ) -->
            <div class="cr-info-back" data-cr-info-close aria-hidden="true"></div>
            <aside class="cr-info" data-cr-info>
                <div class="cr-info-head">
                    <button type="button" class="cr-info-back-btn" data-cr-info-close aria-label="<?php echo te('ກັບຄືນ'); ?>">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19l-7-7 7-7"/></svg>
                        <span><?php echo te('ກັບຄືນ'); ?></span>
                    </button>
                    <h2 class="cr-info-title"><?php echo te('ໂປຣໄຟລ໌ຮ້ານ'); ?></h2>
                    <div class="cr-info-head-space" aria-hidden="true"></div>
                </div>

                <div class="cr-info-scroll">
                    <div class="cr-profile">
                        <div class="cr-profile-ava-wrap">
                            <span class="cr-profile-ava"><img src="<?php echo asset('img/logo.png'); ?>" alt="" width="80" height="52"></span>
                            <span class="cr-profile-verified" title="<?php echo te('ຢືນຢັນແລ້ວ'); ?>">✓</span>
                        </div>
                        <b class="cr-profile-name"><?php echo e($company['name']); ?></b>
                        <p class="cr-profile-sub"><?php echo te('ລະບົບ POS ແລະ ເວັບໄຊ ສຳລັບທຸລະກິດລາວ'); ?></p>
                        <span class="cr-profile-status"><span class="chat-dot"></span><?php echo te('ອອນໄລນ໌ · ພ້ອມໃຫ້ຄຳປຶກສາຟຣີ'); ?></span>

                        <div class="cr-profile-actions">
                            <a href="tel:+8562029777302" class="cr-prof-act is-call">
                                <span class="cr-prof-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg></span>
                                <span><?php echo te('ໂທສາຍດ່ວນ'); ?></span>
                            </a>
                            <a href="<?php echo e('https://wa.me/8562055949007?text=' . rawurlencode($autoText !== '' ? $autoText : t('ສົນໃຈລະບົບ POS ແລະ ເວັບໄຊ'))); ?>" target="_blank" rel="noopener" class="cr-prof-act is-wa">
                                <span class="cr-prof-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.7-.1 1.4z"/></svg></span>
                                <span>WhatsApp</span>
                            </a>
                            <a href="<?php echo url('pages/about.php'); ?>" class="cr-prof-act is-about">
                                <span class="cr-prof-ico"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></span>
                                <span><?php echo te('ກ່ຽວກັບ'); ?></span>
                            </a>
                        </div>
                    </div>

                    <div class="cr-tabs" role="tablist">
                        <button type="button" class="is-active" role="tab" data-cr-tab="media">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            <span><?php echo te('ມີເດຍ ແລະ ໄຟລ໌'); ?></span>
                        </button>
                        <button type="button" role="tab" data-cr-tab="orders">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
                            <span><?php echo te('ຄຳສັ່ງຊື້'); ?></span>
                        </button>
                    </div>

                    <!-- ເນື້ອໃນແທັບ: ເລື່ອນໄດ້ (ໂປຣໄຟລ໌ + ແທັບ) -->
                    <div class="cr-panes" data-cr-panes>
                    <div class="cr-loading" data-cr-loading hidden aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                <div class="cr-pane" data-cr-pane="orders">
                    <?php if ($crOrders): ?>
                        <?php foreach ($crOrders as $o): $om = orderStatusMeta((string)$o['status']); $ot = orderDisplayTotal($o); ?>
                            <a class="cr-order" href="<?php echo url('pages/my_order.php?no=' . rawurlencode((string)$o['order_no'])); ?>">
                                <img src="<?php echo e($om['img']); ?>" alt="" width="34" height="34" loading="lazy">
                                <span><b><?php echo e($o['order_no']); ?></b><small><?php echo e(date('d/m/Y', strtotime($o['created_at']))); ?><?php echo $ot > 0 ? ' · ' . e(money($ot)) : ''; ?></small></span>
                                <span class="mo-badge is-<?php echo e($om['tone']); ?>"><?php echo e($om['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                        <a class="cr-more" href="<?php echo url('pages/my_orders.php'); ?>"><?php echo te('ເບິ່ງອໍເດີທັງໝົດ →'); ?></a>
                    <?php else: ?>
                        <div class="cr-empty">
                            <strong><?php echo te('ບໍ່ຮູ້ຈະເລີ່ມແນວໃດ?'); ?></strong>
                            <span><?php echo $crMember ? te('ບໍ່ຕ້ອງຫ່ວງ — ເລືອກສິນຄ້າ ແລ້ວກົດ "ສັ່ງຊື້ເລີຍ", ອໍເດີຈະຂຶ້ນຢູ່ບ່ອນນີ້') : te('ລົງທະບຽນຟຣີ ເພື່ອເບິ່ງລາຄາ, ສັ່ງຊື້ ແລະ ຕິດຕາມອໍເດີຢູ່ບ່ອນນີ້'); ?></span>
                            <?php if ($crMember): ?>
                                <button type="button" class="cr-empty-btn" data-cr-open="help">ⓘ <?php echo te('ວິທີສັ່ງຊື້'); ?></button>
                            <?php else: ?>
                                <a class="cr-empty-btn" href="#register" data-member-open="register"><?php echo te('ລົງທະບຽນຟຣີ'); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cr-pane" data-cr-pane="media" hidden>
                    <?php /* ຮູບ / ວິດີໂອ (ຕາຕະລາງ) | ໄຟລ໌ (ລາຍການ) — ໄຟລ໌ທີ່ສົ່ງໃໝ່ chat_room.js ເພີ່ມໃສ່ເອງ (event chat:messages) */
                    $crGallery = array_values(array_filter($crMedia, fn($m) => in_array($m['file_kind'], ['image', 'video'], true)));
                    $crFiles = array_values(array_filter($crMedia, fn($m) => $m['file_kind'] === 'file')); ?>
                    <div class="cr-seg" role="tablist">
                        <button type="button" class="is-active" data-cr-seg="media"><?php echo te('ຮູບພາບ ແລະ ວິດີໂອ'); ?></button>
                        <button type="button" data-cr-seg="file"><?php echo te('ໄຟລ໌'); ?></button>
                    </div>
                    <div class="cr-gallery" data-cr-box="media"><?php foreach ($crGallery as $md): $mu = siteRoot() . $md['file_path']; ?><?php if ($md['file_kind'] === 'image'): ?><a class="cr-g" href="<?php echo e($mu); ?>" target="_blank" rel="noopener" data-cr-view="image" data-id="<?php echo (int)$md['id']; ?>"><img src="<?php echo e($mu); ?>" alt="<?php echo e($md['file_name']); ?>" loading="lazy"></a><?php else: ?><a class="cr-g is-video" href="<?php echo e($mu); ?>" target="_blank" rel="noopener" data-cr-view="video" data-id="<?php echo (int)$md['id']; ?>"><video src="<?php echo e($mu); ?>#t=0.5" preload="metadata" muted playsinline></video><i aria-hidden="true"></i></a><?php endif; ?><?php endforeach; ?></div>
                    <div class="cr-files" data-cr-box="file" hidden><?php foreach ($crFiles as $md): $sz = (int)$md['file_size']; ?><a class="cr-file" href="<?php echo e(siteRoot() . $md['file_path']); ?>" download="<?php echo e($md['file_name']); ?>" data-id="<?php echo (int)$md['id']; ?>"><span class="chat-att-ext"><?php echo e(strtoupper(mb_substr(pathinfo($md['file_name'], PATHINFO_EXTENSION) ?: 'file', 0, 4))); ?></span><span><b><?php echo e($md['file_name']); ?></b><small><?php echo $sz >= 1048576 ? number_format($sz / 1048576, 1) . ' MB' : max(1, (int)round($sz / 1024)) . ' KB'; ?> · <?php echo e(date('d/m/Y', strtotime($md['created_at']))); ?></small></span></a><?php endforeach; ?></div>
                    <div class="cr-empty cr-media-empty" data-cr-seg-empty="media"<?php echo $crGallery ? ' hidden' : ''; ?>>
                        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="3" width="15" height="13" rx="2"/><path d="M3 7v11a3 3 0 0 0 3 3h11"/><circle cx="11" cy="8" r="1.5"/><path d="m21 13-4-4-7 7"/></svg>
                        <span><?php echo te('ຍັງບໍ່ມີຮູບພາບ ຫຼື ວິດີໂອ'); ?></span>
                    </div>
                    <div class="cr-empty cr-media-empty" data-cr-seg-empty="file" hidden>
                        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>
                        <span><?php echo te('ຍັງບໍ່ມີໄຟລ໌'); ?></span>
                    </div>
                </div>

                </div><!-- /.cr-panes -->
                </div><!-- /.cr-info-scroll -->
            </aside>
        </div>
    </div>
</section>
<?php require dirname(__DIR__) . '/components/chat_room_modals.php'; /* popup ຊ່ວຍເຫຼືອ + ຕັ້ງຄ່າ */ ?>
<!-- ເບິ່ງຮູບ / ວິດີໂອ ເຕັມຈໍ ([data-cr-view] → assets/js/chat_room.js) -->
<div class="cr-viewer" data-cr-viewer hidden><button type="button" class="cr-viewer-x" data-cr-viewer-close aria-label="<?php echo te('ປິດ'); ?>">&times;</button><div class="cr-viewer-body" data-cr-viewer-body></div><a class="cr-viewer-dl" data-cr-viewer-dl download><?php echo te('ດາວໂຫຼດ'); ?></a></div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
