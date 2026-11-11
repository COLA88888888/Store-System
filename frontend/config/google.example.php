<?php
/**
 * ເຂົ້າສູ່ລະບົບ / ລົງທະບຽນສະມາຊິກດ້ວຍ Google — ສຳເນົາໄຟລ໌ນີ້ເປັນ config/google.php ແລ້ວໃສ່ Client ID.
 * ວິທີເອົາ Client ID (ຟຣີ):
 *   1. https://console.cloud.google.com/apis/credentials → Create credentials → OAuth client ID
 *   2. Application type = Web application
 *   3. Authorized JavaScript origins = URL ຂອງເວັບ ເຊັ່ນ http://localhost ແລະ https://wlaodev.com
 *   4. ສຳເນົາ "Client ID" (…apps.googleusercontent.com) ມາໃສ່ລຸ່ມນີ້
 * ວ່າງ = ບໍ່ສະແດງປຸ່ມ Google (ລົງທະບຽນດ້ວຍເບີໂທ / ອີເມວ ໄດ້ຕາມປົກກະຕິ). ໃຊ້ໂດຍ models/member_google.php
 */
return [
    'client_id' => '',
];
