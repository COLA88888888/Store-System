<?php
/**
 * ສ່ວນ: ການກະທຳ POST
 * ບັນທຶກກົດການໄດ້ຄະແນນ ແລະ ເພີ່ມ/ແກ້ໄຂ/ເປີດປິດ/ລົບ ລາງວັນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/loyalty/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ===== Earning rule =====
if ($postAction === 'save_loyalty') {
    $enabled = isset($_POST['loyalty_enabled']) ? '1' : '0';
    $spend   = (int)str_replace(',', '', $_POST['loyalty_spend_amount'] ?? '0');
    $points  = (int)str_replace(',', '', $_POST['loyalty_points_earned'] ?? '0');
    // Fall back to sensible values so the rule stays meaningful; saving always goes through
    if ($spend < 1)  $spend  = 100000;
    if ($points < 1) $points = 1;
    try {
        $up = $pdo->prepare("INSERT INTO st_system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $up->execute(['loyalty_enabled', $enabled]);
        $up->execute(['loyalty_spend_amount', (string)$spend]);
        $up->execute(['loyalty_points_earned', (string)$points]);
        $message = t('loyalty.msg_saved', 'ບັນທຶກການຕັ້ງຄ່າຄະແນນສະສົມສຳເລັດ!');
        $message_type = 'success';
        logActivity($pdo, 'ຕັ້ງຄ່າຄະແນນສະສົມ', "enabled=$enabled, spend=$spend, points=$points");
    } catch (Exception $e) {
        $message = t('loyalty.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
        $message_type = 'danger';
    }
}

// ===== Redemption rewards: add / edit / toggle / delete =====
if (in_array($postAction, ['add_reward', 'edit_reward', 'toggle_reward', 'delete_reward'], true)) {
    try {
        $rewardId = (int)($_POST['reward_id'] ?? 0);
        if ($postAction === 'add_reward' || $postAction === 'edit_reward') {
            $rwType   = ($_POST['reward_type'] ?? '') === 'product' ? 'product' : 'discount';
            $rwPoints = (int)str_replace(',', '', $_POST['points_cost'] ?? '0');
            $rwAmount = (float)str_replace(',', '', $_POST['discount_amount'] ?? '0');
            // Minimum purchase before a discount reward can be used (0 = none; the POS still
            // always requires the purchase to be more than the discount itself).
            $rwMinSpend = max(0, (float)str_replace(',', '', $_POST['min_spend'] ?? '0'));
            $rwProdId = (int)($_POST['product_id'] ?? 0);
            $rwQty    = max(1, (int)($_POST['product_qty'] ?? 1));
            $rwName   = trim($_POST['reward_name'] ?? '');

            $rwProduct = null;
            if ($rwType === 'product' && $rwProdId > 0) {
                $pStmt = $pdo->prepare("SELECT product_name, unit FROM st_products WHERE product_id = ? AND store_id = ? LIMIT 1");
                $pStmt->execute([$rwProdId, $activeStoreId]);
                $rwProduct = $pStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if ($rwPoints < 1) {
                $message = t('loyalty.reward_err_points', 'ກະລຸນາໃສ່ຈຳນວນຄະແນນທີ່ໃຊ້ແລກ (ຢ່າງໜ້ອຍ 1)');
                $message_type = 'warning';
            } elseif ($rwType === 'discount' && $rwAmount <= 0) {
                $message = t('loyalty.reward_err_amount', 'ກະລຸນາໃສ່ມູນຄ່າສ່ວນຫຼຸດ');
                $message_type = 'warning';
            } elseif ($rwType === 'product' && !$rwProduct) {
                $message = t('loyalty.reward_err_product', 'ກະລຸນາເລືອກສິນຄ້າທີ່ຈະໃຫ້ແລກ');
                $message_type = 'warning';
            } else {
                // Name defaults to a readable description of the reward itself.
                if ($rwName === '') {
                    $rwName = ($rwType === 'product')
                        ? $rwProduct['product_name'] . ' ' . $rwQty . ' ' . ($rwProduct['unit'] ?: t('loyalty.unit_generic', 'ອັນ'))
                        : sprintf(t('loyalty.reward_default_discount_name', 'ສ່ວນຫຼຸດ %s ກີບ'), number_format($rwAmount));
                }
                $vals = [$rwName, $rwPoints, $rwType, $rwType === 'discount' ? $rwAmount : 0, $rwType === 'discount' ? $rwMinSpend : 0, $rwType === 'product' ? $rwProdId : null, $rwType === 'product' ? $rwQty : 1];
                if ($postAction === 'edit_reward' && $rewardId > 0) {
                    $pdo->prepare("UPDATE st_loyalty_rewards SET reward_name = ?, points_cost = ?, reward_type = ?, discount_amount = ?, min_spend = ?, product_id = ?, product_qty = ? WHERE id = ?")
                        ->execute(array_merge($vals, [$rewardId]));
                    $message = t('loyalty.reward_msg_saved', 'ແກ້ໄຂລາຍການແລກຄະແນນສຳເລັດ!');
                    logActivity($pdo, 'ແກ້ໄຂລາຍການແລກຄະແນນ', "#$rewardId $rwName = $rwPoints points");
                } else {
                    $pdo->prepare("INSERT INTO st_loyalty_rewards (reward_name, points_cost, reward_type, discount_amount, min_spend, product_id, product_qty) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute($vals);
                    $message = t('loyalty.reward_msg_added', 'ເພີ່ມລາຍການແລກຄະແນນສຳເລັດ!');
                    logActivity($pdo, 'ເພີ່ມລາຍການແລກຄະແນນ', "$rwName = $rwPoints points");
                }
                $message_type = 'success';
            }
        } elseif ($postAction === 'toggle_reward') {
            $pdo->prepare("UPDATE st_loyalty_rewards SET is_active = 1 - is_active WHERE id = ?")->execute([$rewardId]);
            $message = t('loyalty.reward_msg_updated', 'ອັບເດດສະຖານະສຳເລັດ!');
            $message_type = 'success';
        } elseif ($postAction === 'delete_reward') {
            $pdo->prepare("DELETE FROM st_loyalty_rewards WHERE id = ?")->execute([$rewardId]);
            $message = t('loyalty.reward_msg_deleted', 'ລຶບລາຍການແລກຄະແນນສຳເລັດ!');
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = t('loyalty.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
        $message_type = 'danger';
    }
}

