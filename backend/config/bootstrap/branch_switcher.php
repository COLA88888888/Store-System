<?php
/**
 * ສ່ວນ: ສະຫຼັບສາຂາທີ່ກຳລັງເບິ່ງ
 * ເມື່ອ URL ມີ ?switch_store_id ຫຼື ?store_id ຈະບັນທຶກສາຂາທີ່ເລືອກໄວ້ໃນ session
 * (ສະເພາະຜູ້ທີ່ເບິ່ງທຸກສາຂາໄດ້). ຕ້ອງຢູ່ຫຼັງຟັງຊັນສາຂາ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ============================================================
// Multi-Branch Store Switcher Handler
// Kept at the very bottom: it depends on isMainBranch() and $pdo, both defined above.
// A URL carrying ?switch_store_id=N (explicit switcher) or ?store_id=N (report branch
// filter) parks the choice in $_SESSION['active_store_id'], which getActiveStoreId()
// reads on the next request. Only users who pass canViewAllBranches() may switch; everyone
// else is ignored here and stays locked to their own store.
// ============================================================
if (!empty($_SESSION['user_id'])) {
    $switchStoreParam = null;
    if (isset($_GET['switch_store_id'])) {
        $switchStoreParam = intval($_GET['switch_store_id']);
    } elseif (isset($_GET['store_id']) && $_GET['store_id'] !== '') {
        $switchStoreParam = intval($_GET['store_id']);
    }
    if ($switchStoreParam !== null && $switchStoreParam > 0) {
        $canSwitchStore = canViewAllBranches($pdo);
        if ($canSwitchStore) {
            $_SESSION['active_store_id'] = $switchStoreParam;
        }
    }
}
