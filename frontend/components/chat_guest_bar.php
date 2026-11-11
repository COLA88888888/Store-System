<?php
/**
 * ແຖບແທນບ່ອນພິມແຊັດ ສຳລັບລູກຄ້າທີ່ຍັງບໍ່ເປັນສະມາຊິກ — ແຊັດໄດ້ສະເພາະສະມາຊິກ (api/chat.php ກັນ POST ຂອງຄົນທົ່ວໄປ).
 * ໃຊ້ໃນ components/floating_chat.php ແລະ pages/contact.php (ກ່ອງແຊັດມີ class "chat-guest" → CSS ເຊື່ອງບ່ອນພິມ/ປຸ່ມຖາມດ່ວນ).
 * ປຸ່ມເປີດ popup ສະມາຊິກ ([data-member-open] — assets/js/member.js). WhatsApp / ໂທ ຍັງໃຊ້ໄດ້ຄືເກົ່າ.
 */
?>
<div class="chat-guest-bar">
    <span><?php echo te('ລົງທະບຽນເປັນສະມາຊິກ ຈຶ່ງພິມແຊັດກັບທີມງານໄດ້ (ຟຣີ)'); ?></span>
    <div class="chat-guest-btns">
        <a class="chat-guest-btn is-primary" href="#register" data-member-open="register" data-member-note="<?php echo te('ກະລຸນາລົງທະບຽນ ຫຼື ເຂົ້າສູ່ລະບົບ ກ່ອນພິມແຊັດ — ສະມາຊິກແຊັດ, ເບິ່ງລາຄາ ແລະ ສັ່ງຊື້ໄດ້ເລີຍ'); ?>"><?php echo te('ລົງທະບຽນ'); ?></a>
        <a class="chat-guest-btn" href="<?php echo url('pages/member_login.php'); ?>" data-member-open="login"><?php echo te('ເຂົ້າສູ່ລະບົບ'); ?></a>
    </div>
</div>
