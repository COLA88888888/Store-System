<?php
/**
 * ປຸ່ມແຊັດລອຍ (Floating Chat Widget) — ສະແດງທຸກໜ້າ ມຸມຂວາລຸ່ມ
 * ກົດແລ້ວເປີດກ່ອງແຊັດດ່ວນ (Live Web Chat + ປຸ່ມ WhatsApp, ໂທດ່ວນ ແລະ Messenger)
 */
$fcSubject = !empty($defaultSubject) ? (string)$defaultSubject : '';
$fcWaHref = 'https://wa.me/8562055949007?text=' . rawurlencode($fcSubject !== '' ? $fcSubject : t('ສະບາຍດີ ສົນໃຈສອບຖາມຂໍ້ມູນ ແລະ ລາຄາລະບົບ POS / ອຸປະກອນ'));
$fcContactHref = url('pages/contact.php' . ($fcSubject !== '' ? '?subject=' . rawurlencode($fcSubject) : ''));
$fcTel = 'tel:+8562029777302';
$fcFbHref = fbChatUrl($fcSubject);   // ເປີດ Messenger ແຊັດທັນທີ
$isContactPage = (($currentPage ?? '') === 'contact') || (($GLOBALS['currentPage'] ?? '') === 'contact');
if ($isContactPage) {
    return; // ຖ້າຢູ່ໃນຫ້ອງແຊັດ (ໜ້າຕິດຕໍ່) ແລ້ວ ເຊື່ອງປຸ່ມແຊັດລອຍທັງໝົດ
}
?>
<div class="fc-widget" id="floatingChatWidget" data-floating-chat data-is-contact="<?php echo $isContactPage ? '1' : '0'; ?>">
    <!-- ປຸ່ມແຊັດລອຍຫຼັກ -->
    <button type="button" class="fc-trigger" id="fcTrigger" aria-label="<?php echo te('ເປີດແຊັດສອບຖາມ'); ?>" title="<?php echo te('ແຊັດສອບຖາມພວກເຮົາ'); ?>">
        <span class="fc-trigger-pulse" aria-hidden="true"></span>
        <span class="fc-trigger-dot" aria-hidden="true"></span>
        <!-- ໄອຄອນແຊັດ (ປົກກະຕິ) -->
        <span class="fc-icon-chat" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
        </span>
        <!-- ໄອຄອນປິດ (ຕອນເປີດ) -->
        <span class="fc-icon-close" aria-hidden="true">✕</span>
        <!-- ປ້າຍຂໍ້ຄວາມລອຍຂ້າງໆ -->
        <span class="fc-badge-bubble">
            <span class="fc-badge-wave"><?php echo icon('chat', 15, 2.4); ?></span>
            <span class="fc-badge-text"><?php echo te('ແຊັດສອບຖາມເຮົາໄດ້ເລີຍ'); ?></span>
        </span>
    </button>

    <!-- ກ່ອງໜ້າຕ່າງແຊັດລອຍ (Popup Card) -->
    <div class="fc-popup" id="fcPopup" hidden role="dialog" aria-modal="true" aria-labelledby="fcTitle">
        <!-- ສ່ວນຫົວ -->
        <div class="fc-head">
            <div class="fc-brand">
                <span class="fc-avatar">
                    <img src="<?php echo asset('img/logo.png'); ?>" alt="" width="28" height="28">
                    <span class="fc-status-dot" aria-hidden="true"></span>
                </span>
                <div class="fc-brand-info">
                    <h3 id="fcTitle"><?php echo e($company['name']); ?></h3>
                    <small class="fc-status-text"><i class="fc-online-dot" aria-hidden="true"></i><?php echo te('ອອນລາຍ · ພ້ອມໃຫ້ຄຳປຶກສາຟຣີ'); ?></small>
                </div>
            </div>
            <div class="fc-head-actions">
                <a href="<?php echo e($fcContactHref); ?>" class="fc-btn-icon" title="<?php echo te('ເປີດໜ້າແຊັດເຕັມຈໍ'); ?>" aria-label="<?php echo te('ເປີດໜ້າແຊັດເຕັມຈໍ'); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                </a>
                <button type="button" class="fc-btn-icon" id="fcCloseBtn" aria-label="<?php echo te('ປິດ'); ?>">✕</button>
            </div>
        </div>

        <!-- ແຖບຊ່ອງທາງຕິດຕໍ່ດ່ວນ (ໄອຄອນ WhatsApp ວົງມົນ, Facebook ວົງມົນ, ເບີໂທ) -->
        <div class="fc-fast-bar">
            <div class="fc-fast-circles">
                <a href="<?php echo e($fcWaHref); ?>" target="_blank" rel="noopener" class="fc-circle-btn is-wa" title="WhatsApp" aria-label="WhatsApp">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 1c.3.1.5.2.5.3.1.2.1.7-.1 1.4z"/></svg>
                </a>
                <a href="<?php echo e($fcFbHref); ?>" target="_blank" rel="noopener" class="fc-circle-btn is-fb" title="Facebook" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M15.12 5.32H17V2.14A26.11 26.11 0 0 0 14.26 2c-2.72 0-4.58 1.66-4.58 4.7v2.62H6.61v3.56h3.07V22h3.68v-9.12h3.06l.46-3.56h-3.52V6.65c0-1.05.29-1.73 1.76-1.73z"/></svg>
                </a>
            </div>
            <a href="<?php echo e($fcTel); ?>" class="fc-fast-tel" title="<?php echo te('ໂທຫາພວກເຮົາ: 020 29 777 302'); ?>">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                <span>020 29 777 302</span>
            </a>
        </div>

        <!-- ພື້ນທີ່ຂໍ້ຄວາມແຊັດ -->
        <?php $chatIsGuest = !currentMember($pdo); /* ແຊັດໄດ້ສະເພາະສະມາຊິກ — ຄົນທົ່ວໄປເຫັນແຖບລົງທະບຽນແທນບ່ອນພິມ (components/chat_guest_bar.php) */ ?>
        <div class="fc-chat-area<?php echo $chatIsGuest ? ' chat-guest' : ''; ?>" data-fc-chat data-endpoint="<?php echo e(url('api/chat.php')); ?>">
            <div class="fc-log" id="fcLog" aria-live="polite">
                <div class="chat-msg is-bot">
                    <span class="chat-name"><?php echo e($company['name']); ?></span>
                    <div class="chat-bubble"><?php echo te('ສະບາຍດີ 🙏 ຍິນດີຕ້ອນຮັບສູ່ %s! ທ່ານຕ້ອງການສອບຖາມຂໍ້ມູນລະບົບ POS, ອຸປະກອນ ຫຼື ຂໍລາຄາພິເສດ ພິມຂໍ້ຄວາມຖາມໄດ້ເລີຍ.', $company['name']); ?></div>
                </div>
            </div>


            <!-- ປຸ່ມຄຳຖາມດ່ວນ -->
            <div class="fc-quick-bar" id="fcQuickBar">
                <?php if ($fcSubject !== ''): ?>
                    <button type="button" class="fc-chip" data-fc-ask="<?php echo e($fcSubject); ?>"><?php echo e($fcSubject); ?></button>
                <?php endif; ?>
                <button type="button" class="fc-chip" data-fc-ask="ສອບຖາມລາຄາ ແລະ ໂປຣໂມຊັ່ນ"><?php echo te('ສອບຖາມລາຄາ'); ?></button>
                <button type="button" class="fc-chip" data-fc-ask="ລະບົບ POS ມີແບບໃດແດ່?"><?php echo te('ລະບົບ POS'); ?></button>
                <button type="button" class="fc-chip" data-fc-ask="ອຸປະກອນ POS ມີຫຍັງແດ່?"><?php echo te('ອຸປະກອນ POS'); ?></button>
                <button type="button" class="fc-chip" data-fc-ask="ລົມກັບພະນັກງານ"><?php echo te('ລົມກັບພະນັກງານ'); ?></button>
            </div>

            <!-- ຟອມຝາກຊື່ + ເບີໂທ (ຖ້າຍັງບໍ່ມີ) -->
            <form class="fc-contact-form" id="fcContactForm" hidden>
                <span><?php echo te('ຝາກເບີໂທ ໃຫ້ທີມງານໂທກັບ / ສົ່ງໃບສະເໜີລາຄາ:'); ?></span>
                <div class="fc-contact-inputs">
                    <input type="text" name="name" maxlength="150" placeholder="<?php echo te('ຊື່ ຫຼື ຊື່ຮ້ານ'); ?>">
                    <input type="tel" name="tel" maxlength="50" required placeholder="020 xxxx xxxx">
                    <button type="submit"><?php echo te('ບັນທຶກ'); ?></button>
                </div>
            </form>

            <?php if ($chatIsGuest) { require __DIR__ . '/chat_guest_bar.php'; } ?>
            <!-- ບ່ອນພິມຂໍ້ຄວາມ -->
            <form class="fc-input-bar" id="fcInputForm">
                <?php /* ປຸ່ມໄຟລ໌ (ເອກະສານ) + ປຸ່ມຮູບ (ຮູບ/ວິດີໂອ) ຄືກັບໜ້າແຊັດ pages/contact.php — floating_chat.js ປ່ຽນ accept ກ່ອນເປີດ → api/chat.php action=file */ ?>
                <button type="button" class="fc-tool-btn" data-fc-attach aria-label="<?php echo te('ສົ່ງໄຟລ໌ເອກະສານ'); ?>" title="<?php echo te('ສົ່ງໄຟລ໌ເອກະສານ'); ?>"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></button>
                <button type="button" class="fc-tool-btn" data-fc-image aria-label="<?php echo te('ສົ່ງຮູບ ຫຼື ວິດີໂອ'); ?>" title="<?php echo te('ສົ່ງຮູບ ຫຼື ວິດີໂອ'); ?>"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/></svg></button>
                <input type="file" data-fc-file hidden multiple accept="<?php echo e(chatFileAcceptDocs()); ?>" data-accept-docs="<?php echo e(chatFileAcceptDocs()); ?>" data-accept-media="<?php echo e(chatFileAcceptMedia()); ?>">
                <textarea name="text" id="fcInputText" rows="1" maxlength="1000" placeholder="<?php echo te('ພິມຂໍ້ຄວາມສອບຖາມ…'); ?>"><?php echo e($fcSubject); ?></textarea>
                <!-- ໄອຄອນໄມ (ສະແດງຕອນຍັງບໍ່ມີຂໍ້ຄວາມ) -->
                <button type="button" class="fc-mic-btn" id="fcMicBtn" aria-label="<?php echo te('ອັດສຽງ'); ?>" title="<?php echo te('ກົດເພື່ອອັດສຽງສົ່ງ'); ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="2" width="6" height="12" rx="3"></rect>
                        <path d="M5 10a7 7 0 0 0 14 0M12 17v5"></path>
                    </svg>
                </button>
                <!-- ປຸ່ມສົ່ງ (ສະແດງເມື່ອພິມຂໍ້ຄວາມແລ້ວ) -->
                <button type="submit" class="fc-send-btn" id="fcSendBtn" aria-label="<?php echo te('ສົ່ງ'); ?>" hidden>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>

            <!-- ແຖບອັດສຽງດ່ວນ (ສະແດງຕອນກຳລັງອັດສຽງ) -->
            <div class="fc-rec-bar" id="fcRecBar" hidden>
                <span class="fc-rec-dot" aria-hidden="true"></span>
                <span class="fc-rec-time" id="fcRecTime">0:00</span>
                <span class="fc-rec-waves" aria-hidden="true">
                    <span></span><span></span><span></span><span></span>
                </span>
                <button type="button" class="fc-rec-cancel" id="fcRecCancel" title="<?php echo te('ຍົກເລີກ'); ?>" aria-label="<?php echo te('ຍົກເລີກ'); ?>">✕</button>
                <button type="button" class="fc-rec-send" id="fcRecSend" title="<?php echo te('ສົ່ງສຽງ'); ?>" aria-label="<?php echo te('ສົ່ງສຽງ'); ?>">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M22 2 2 9.5l7.5 3L19 5l-7.5 9.5V22l4-5.5 4.5 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
