<?php
/**
 * ອ່ານ / ສະແດງ ແຈ້ງເຕືອນຂອງສະມາຊິກ (ກະດິ່ງໃນເມນູ — components/member_bell.php, api/notifications.php).
 * ແຖວເກັບ "ປະເພດ + ຂໍ້ມູນ" → memberNotifView() ແປເປັນຂໍ້ຄວາມພາສາປັດຈຸບັນດ້ວຍ t(). ການຂຽນ: includes/member_notify.php
 */

/** ຈຳນວນທີ່ຍັງບໍ່ອ່ານ */
function memberNotifUnread(PDO $pdo, int $memberId): int
{
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM w_web_member_notifications WHERE member_id = ? AND is_read = 0");
        $st->execute([$memberId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** ລາຍການລ່າສຸດ (ແປແລ້ວ) */
function memberNotifList(PDO $pdo, int $memberId, int $limit = 20): array
{
    try {
        $st = $pdo->prepare("SELECT id, kind, data, link, is_read, created_at FROM w_web_member_notifications WHERE member_id = ? ORDER BY id DESC LIMIT " . max(1, min(50, $limit)));
        $st->execute([$memberId]);
        return array_map('memberNotifView', $st->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        return [];
    }
}

/** ໄອຄອນເສັ້ນ SVG (ແທນ emoji) ສຳລັບແຈ້ງເຕືອນ — ຂໍ້ຄວາມຄົງທີ່ ບໍ່ມີຂໍ້ມູນຈາກຜູ້ໃຊ້ (JS ໃສ່ເປັນ HTML ໄດ້) */
function memberNotifIcon(string $name): string
{
    $paths = [
        'gift' => '<path d="M20 12v9H4v-9M2 7h20v5H2zM12 21V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>',
        'receipt' => '<path d="M4 2v20l3-2 3 2 2-2 2 2 3-2 3 2V2l-3 2-3-2-2 2-2-2-3 2z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'box' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
        'x' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
        'truck' => '<path d="M1 4h14v12H1zM15 9h4l3 3v4h-7z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['bell']) . '</svg>';
}

/** ແຖວ DB → {id, icon (SVG), tone, title, sub, link, time, is_read} */
function memberNotifView(array $r): array
{
    $d = json_decode((string)$r['data'], true) ?: [];
    $no = (string)($d['order_no'] ?? '');
    $statusText = [
        'pending'   => t('ໄດ້ຮັບອໍເດີແລ້ວ'),
        'confirmed' => t('ຢືນຢັນອໍເດີ ແລະ ຮັບເງິນແລ້ວ'),
        'packing'   => t('ກຳລັງແພັກສິນຄ້າ'),
        'shipped'   => t('ສິນຄ້າກຳລັງເດີນທາງໄປຫາທ່ານ'),
        'arrived'   => t('ເຄື່ອງຮອດສາຂາປາຍທາງແລ້ວ — ໄປຮັບໄດ້'),
        'completed' => t('ຮັບເຄື່ອງຮຽບຮ້ອຍ — ສຳເລັດ'),
        'cancelled' => t('ອໍເດີຖືກຍົກເລີກ'),
    ];
    switch ($r['kind']) {
        case 'welcome':
            [$icon, $tone, $title, $sub] = [memberNotifIcon('gift'), 'green', t('ຍິນດີຕ້ອນຮັບສູ່ WlaoDev!'), t('ທ່ານເຫັນລາຄາ ແລະ ສັ່ງຊື້ອອນລາຍໄດ້ແລ້ວ')];
            break;
        case 'order_new':
            [$icon, $tone, $title, $sub] = [memberNotifIcon('receipt'), 'blue', t('ໄດ້ຮັບອໍເດີ %s ແລ້ວ', $no), t('ກະລຸນາໂອນເງິນ ແລະ ແນບສະລິບ')];
            break;
        case 'order_status':
            $s = (string)($d['status'] ?? '');
            [$icon, $tone, $title, $sub] = [memberNotifIcon($s === 'cancelled' ? 'x' : ($s === 'completed' ? 'check' : 'box')), $s === 'cancelled' ? 'red' : ($s === 'completed' ? 'green' : 'blue'),
                $statusText[$s] ?? t('ອໍເດີມີການອັບເດດ'), t('ອໍເດີ %s', $no)];
            break;
        case 'tracking':
            [$icon, $tone, $title, $sub] = [memberNotifIcon('truck'), 'blue', t('ເລກໃບຝາກເຄື່ອງ: %s', (string)($d['tracking'] ?? '')), t('ອໍເດີ %s', $no)];
            break;
        case 'receipt':
            [$icon, $tone, $title, $sub] = [memberNotifIcon('truck'), 'green', t('ສົ່ງເຄື່ອງແລ້ວ — ເບິ່ງບິນຮັບເຄື່ອງ'), t('ອໍເດີ %s', $no)];
            break;
        case 'price_drop':
            [$icon, $tone, $title, $sub] = [memberNotifIcon('tag'), 'red', t('ລົດລາຄາ -%d%%', (int)($d['pct'] ?? 0)), (string)($d['name'] ?? '')];
            break;
        case 'chat':
            [$icon, $tone, $title, $sub] = [memberNotifIcon('chat'), 'blue', t('ທີມງານຕອບແຊັດຂອງທ່ານ'), (string)($d['preview'] ?? '')];
            break;
        default:
            [$icon, $tone, $title, $sub] = [memberNotifIcon('bell'), 'blue', t('ແຈ້ງເຕືອນ'), ''];
    }
    return [
        'id'      => (int)$r['id'],
        'icon'    => $icon,
        'tone'    => $tone,
        'title'   => $title,
        'sub'     => $sub,
        'link'    => !empty($r['link']) ? url((string)$r['link']) : '',
        'time'    => memberNotifAgo((string)$r['created_at']),
        'is_read' => (bool)$r['is_read'],
    ];
}

/** ເວລາແບບ "5 ນາທີກ່ອນ" */
function memberNotifAgo(string $at): string
{
    $sec = max(0, time() - strtotime($at));
    if ($sec < 60) {
        return t('ຫາກໍ່ນີ້');
    }
    if ($sec < 3600) {
        return t('%d ນາທີກ່ອນ', intdiv($sec, 60));
    }
    if ($sec < 86400) {
        return t('%d ຊົ່ວໂມງກ່ອນ', intdiv($sec, 3600));
    }
    if ($sec < 7 * 86400) {
        return t('%d ມື້ກ່ອນ', intdiv($sec, 86400));
    }
    return date('d/m/Y', strtotime($at));
}

/** ໝາຍວ່າອ່ານແລ້ວ: $id = 0 → ທັງໝົດ */
function memberNotifMarkRead(PDO $pdo, int $memberId, int $id = 0): void
{
    try {
        if ($id > 0) {
            $pdo->prepare("UPDATE w_web_member_notifications SET is_read = 1 WHERE member_id = ? AND id = ?")->execute([$memberId, $id]);
        } else {
            $pdo->prepare("UPDATE w_web_member_notifications SET is_read = 1 WHERE member_id = ? AND is_read = 0")->execute([$memberId]);
        }
    } catch (Throwable $e) {
    }
}
