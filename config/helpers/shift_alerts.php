<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ແຈ້ງເຕືອນປິດກະ
 * getShiftCloseNotifications() — ໃຊ້ໃນ poll_notifications (api/stock_transfer_backend.php):
 *   ຜູ້ບໍລິຫານ / ຜູ້ຈັດການ (ທີ່ມີສິດເບິ່ງລາຍງານປິດກະ) ເຫັນກະທີ່ຖືກປິດພາຍໃນ 3 ວັນ ທີ່ຄົນອື່ນເປັນຜູ້ປິດ —
 *   ປິດເອງ ຫຼື ປິດເພາະກົດອອກຈາກລະບົບ (notes = SHIFT_LOGOUT_NOTE, config/helpers/shift_auto.php).
 *   ກົດແຈ້ງເຕືອນ → ລາຍງານປິດກະ ເປີດໃບສະຫຼຸບ + ລາຍການຂາຍຂອງກະນັ້ນ (home/partials/dashboard/js_shift_close_notif.php).
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ config/db.php.
 */
if (!function_exists('getShiftCloseNotifications')) {
    function getShiftCloseNotifications($pdo) {
        $userId = intval($_SESSION['user_id'] ?? 0);
        if ($userId <= 0 || !$pdo) return [];
        try {
            $isBoss = isOwnerUser() || isAdminAccount($pdo, $userId);
            $isMgr  = function_exists('isManagerRole') && isManagerRole();
            if ((!$isBoss && !$isMgr) || !hasPermission('shift_close_report')) return [];

            // ສາຂາທີ່ເບິ່ງໄດ້: ສາຂາຫຼັກ/ເຈົ້າຂອງ = ທຸກສາຂາ, ນອກນັ້ນ = ສາຂາຂອງຕົນເອງ
            $allBranches = function_exists('canViewAllBranches') && canViewAllBranches($pdo);
            $storeSql = $allBranches ? '1=1' : 'ss.store_id = ' . intval($_SESSION['store_id'] ?? 0);

            $st = $pdo->prepare("SELECT ss.id, ss.shift_name, ss.store_id, s.store_name, ss.closed_by_name AS who, ss.close_time AS at,
                                        ss.system_total_sales AS total, ss.system_total_bills AS bills, ss.notes
                                 FROM st_shift_sessions ss
                                 LEFT JOIN st_tbstore s ON s.store_id = ss.store_id
                                 WHERE ss.status = 'closed' AND ss.close_time >= DATE_SUB(NOW(), INTERVAL 3 DAY)
                                   AND IFNULL(ss.closed_by_user_id, 0) <> ? AND $storeSql
                                 ORDER BY ss.close_time DESC LIMIT 20");
            $st->execute([$userId]);
            $out = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $ts = $r['at'] ? strtotime($r['at']) : 0;
                $out[] = [
                    'key'        => 'shc_' . intval($r['id']),
                    'id'         => intval($r['id']),
                    'shift_name' => $r['shift_name'],
                    'store_name' => $r['store_name'],
                    'who'        => $r['who'],
                    'at_text'    => $ts ? date('d/m H:i', $ts) : '',
                    'total'      => (float)$r['total'],
                    'bills'      => intval($r['bills']),
                    'by_logout'  => (trim((string)$r['notes']) === SHIFT_LOGOUT_NOTE),
                ];
            }
            return $out;
        } catch (Throwable $e) {
            return [];   // ແຈ້ງເຕືອນບໍ່ຄວນເຮັດໃຫ້ poll ທັງໝົດລົ້ມ
        }
    }
}
