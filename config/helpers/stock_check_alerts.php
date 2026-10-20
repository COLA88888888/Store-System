<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ແຈ້ງເຕືອນເຊັກສະຕັອກ
 * getStockCheckNotifications() — ໃຊ້ໃນ poll_notifications (api/stock_transfer_backend.php) ທຸກ 1.5 ວິນາທີ:
 *   type 'review'   → ຫົວໜ້າ/ຜູ້ອະນຸມັດ: ພະນັກງານສົ່ງໃບມາກວດສອບ (status = pending)
 *   type 'approved' → ພະນັກງານ: ຫົວໜ້າອະນຸມັດແລ້ວ ໃຫ້ປັບສະຕັອກໄດ້ (status = approved) — ສົ່ງຫາຜູ້ທີ່ສົ່ງໃບ,
 *                     ຫຼື ຜູ້ນັບໃນສາຂານັ້ນ ຖ້າໃບບໍ່ມີຜູ້ສົ່ງ (ໃບເກົ່າກ່ອນມີຄໍລຳ submitted_by)
 * key ລວມເວລາເຂົ້າໄປ: ສົ່ງກວດຄືນ/ອະນຸມັດຄືນ ຖືເປັນແຈ້ງເຕືອນໃໝ່ (ອ່ານແລ້ວຂອງຮອບກ່ອນ ບໍ່ປິດຮອບໃໝ່).
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ config/db.php.
 */
if (!function_exists('getStockCheckNotifications')) {
    function getStockCheckNotifications($pdo) {
        $userId = intval($_SESSION['user_id'] ?? 0);
        if ($userId <= 0 || !$pdo) return [];
        try {
            $isBoss     = isOwnerUser() || isAdminAccount($pdo, $userId);
            $canApprove = $isBoss || hasPermission('stock_check', 'edit');
            $canCount   = $isBoss || hasPermission('stock_check') || hasPermission('stock_count');
            if (!$canApprove && !$canCount) return [];

            // ສາຂາທີ່ເບິ່ງໄດ້: ສາຂາຫຼັກ/ເຈົ້າຂອງ = ທຸກສາຂາ, ນອກນັ້ນ = ສາຂາຂອງຕົນເອງ
            $allBranches = function_exists('canViewAllBranches') && canViewAllBranches($pdo);
            $ownStore = intval($_SESSION['store_id'] ?? 0);
            $storeSql = $allBranches ? '1=1' : 'c.store_id = ' . $ownStore;
            $nameSql = function ($a) {
                return "COALESCE(NULLIF(TRIM(CONCAT(IFNULL($a.fname,''), ' ', IFNULL($a.lname,''))), ''), $a.username)";
            };

            $out = [];
            if ($canApprove) {
                $rows = $pdo->query("SELECT c.id, c.check_no, c.store_id, s.store_name, COALESCE(c.submitted_at, c.created_at) AS at,
                                            {$nameSql('u')} AS who,
                                            (SELECT COUNT(*) FROM st_stock_check_items i WHERE i.check_id = c.id AND i.counted_qty IS NOT NULL) AS counted
                                     FROM st_stock_checks c
                                     LEFT JOIN st_tbstore s ON s.store_id = c.store_id
                                     LEFT JOIN st_tbuser u ON u.Id = COALESCE(c.submitted_by, c.user_id)
                                     WHERE c.status = 'pending' AND $storeSql
                                     ORDER BY at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $r['type'] = 'review';
                    $out[] = $r;
                }
            }
            if ($canCount) {
                $st = $pdo->prepare("SELECT c.id, c.check_no, c.store_id, s.store_name, c.approved_at AS at,
                                            {$nameSql('u')} AS who,
                                            (SELECT COUNT(*) FROM st_stock_check_items i WHERE i.check_id = c.id AND i.counted_qty IS NOT NULL) AS counted
                                     FROM st_stock_checks c
                                     LEFT JOIN st_tbstore s ON s.store_id = c.store_id
                                     LEFT JOIN st_tbuser u ON u.Id = c.approved_by
                                     WHERE c.status = 'approved' AND IFNULL(c.approved_by, 0) <> ?
                                       AND (c.submitted_by = ? OR (c.submitted_by IS NULL AND c.store_id = ?))
                                     ORDER BY at DESC LIMIT 20");
                $st->execute([$userId, $userId, $ownStore]);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $r['type'] = 'approved';
                    $out[] = $r;
                }
            }
            foreach ($out as &$r) {
                $ts = $r['at'] ? strtotime($r['at']) : 0;
                $r['key'] = 'sc' . ($r['type'] === 'review' ? 'r' : 'a') . '_' . intval($r['id']) . '_' . $ts;
                $r['at_text'] = $ts ? date('d/m H:i', $ts) : '';
                $r['counted'] = intval($r['counted']);
            }
            unset($r);
            return $out;
        } catch (Throwable $e) {
            return [];   // ແຈ້ງເຕືອນບໍ່ຄວນເຮັດໃຫ້ poll ທັງໝົດລົ້ມ
        }
    }
}
