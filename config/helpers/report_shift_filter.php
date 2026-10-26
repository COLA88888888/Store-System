<?php
/*
 * ຟັງຊັນຊ່ວຍ: ຕົວກັ່ນຕອງ "ກະ" ຂອງໜ້າລາຍງານ (ຖືກ require ຈາກ config/db.php)
 * ບິນ (st_tbsale_save.shift_session_id) → ຮອບເປີດກະ (st_shift_sessions.shift_id) → ກະ (st_shifts).
 * ກະເປັນຂອງແຕ່ລະສາຂາ: ສາຂາທີ່ບໍ່ມີກະ → ບໍ່ມີຕົວເລືອກ → ໜ້າລາຍງານເຊື່ອງບັອກເລືອກກະ.
 * "ທຸກສາຂາ" → ກະຂອງສາຂາທີ່ກຳລັງເບິ່ງ (getActiveStoreId); ເລືອກກະແລ້ວ = ບິນຂອງກະນັ້ນ (ສາຂານັ້ນ) ເທົ່ານັ້ນ.
 *
 * ການໃຊ້ໃນໜ້າລາຍງານ (ຫຼັງເງື່ອນໄຂສາຂາ):
 *   $shiftFilter = reportShiftFilter($pdo, $filter_store_id);
 *   if ($shiftFilter['sql'] !== '') $where[] = $shiftFilter['sql'];        // ບິນ alias "s"
 *   ...ໃນຟອມ: require .../pages/reports/partials/shift_filter_select.php
 *   ...ລິ້ງແບ່ງໜ້າ: . reportShiftQs()
 */
if (!function_exists('reportShiftFilter')) {
    // ['options' => [[id, shift_name, start, end], ...], 'id' => ກະທີ່ເລືອກ (0 = ທຸກກະ), 'sql' => ເງື່ອນໄຂ ຫຼື '']
    // $storeId 0 (ທຸກສາຂາ — ຟອມທີ່ມີສາຂາດຽວສົ່ງ store_id=0 ທຸກເທື່ອທີ່ກົດກັ່ນຕອງ) → ໃຊ້ກະຂອງສາຂາທີ່ກຳລັງເບິ່ງ,
    // ບໍ່ດັ່ງນັ້ນບັອກກະຫາຍໄປທັນທີທີ່ເລືອກກະ. $enabled false (ພະນັກງານທີ່ຖືກລັອກໃສ່ກະຕົນເອງ) → ບໍ່ມີບັອກ.
    function reportShiftFilter($pdo, $storeId, $alias = 's', $enabled = true) {
        static $cache = [];
        $storeId = (int)$storeId;
        if (!$enabled) return ['options' => [], 'id' => 0, 'sql' => ''];
        if ($storeId <= 0) $storeId = (int)getActiveStoreId($pdo);
        $key = $storeId . '|' . $alias;
        if (isset($cache[$key])) return $cache[$key];

        $options = [];
        if ($storeId > 0) {
            try {
                // ກະທີ່ຕັ້ງໄວ້ (ລວມກະທີ່ປິດໃຊ້ແລ້ວ — ຍັງມີປະຫວັດຂາຍ) + ກະທີ່ຖືກລຶບແຕ່ຍັງມີຮອບເປີດກະ
                $st = $pdo->prepare("SELECT id, shift_name, start_time, end_time FROM st_shifts WHERE store_id = ?
                                     UNION
                                     SELECT ss.shift_id, MAX(ss.shift_name), NULL, NULL FROM st_shift_sessions ss
                                     WHERE ss.store_id = ? AND NOT EXISTS (SELECT 1 FROM st_shifts sh WHERE sh.id = ss.shift_id)
                                     GROUP BY ss.shift_id
                                     ORDER BY start_time IS NULL, start_time, shift_name");
                $st->execute([$storeId, $storeId]);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $options[] = [
                        'id'         => (int)$r['id'],
                        'shift_name' => (string)$r['shift_name'],
                        'start'      => $r['start_time'] !== null ? substr($r['start_time'], 0, 5) : '',
                        'end'        => $r['end_time'] !== null ? substr($r['end_time'], 0, 5) : '',
                    ];
                }
            } catch (Throwable $e) {
                $options = [];
            }
        }

        $id = (int)($_GET['shift_id'] ?? 0);
        if ($id > 0 && !in_array($id, array_column($options, 'id'), true)) $id = 0;   // ກະຂອງສາຂາອື່ນ → ບໍ່ສົນ

        $sql = $id > 0 ? "{$alias}.shift_session_id IN (SELECT id FROM st_shift_sessions WHERE shift_id = {$id} AND store_id = {$storeId})" : '';
        return $cache[$key] = ['options' => $options, 'id' => $id, 'sql' => $sql];
    }
}

if (!function_exists('reportShiftQs')) {
    // "&shift_id=N" ສຳລັບລິ້ງແບ່ງໜ້າທີ່ສ້າງເອງ (ບໍ່ມີກະທີ່ເລືອກ → '')
    function reportShiftQs() {
        $id = (int)($_GET['shift_id'] ?? 0);
        return $id > 0 ? '&shift_id=' . $id : '';
    }
}
