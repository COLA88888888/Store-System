-- StoreMini branch backup: store_id=1
-- ຮ້ານ ຂາຍດີ ຈະເລີນຊັບ · 2026-10-31 15:39:20
DELETE FROM `st_tbsale_save_detail` WHERE `save_bill` IN (SELECT `sale_save_bill` FROM `st_tbsale_save` WHERE `store_id` = 1);
DELETE FROM `st_import_details` WHERE `import_id` IN (SELECT `import_id` FROM `st_imports` WHERE `store_id` = 1);
DELETE FROM `st_stock_transfer_details` WHERE `transfer_id` IN (SELECT `transfer_id` FROM `st_stock_transfers` WHERE `from_store_id` = 1 OR `to_store_id` = 1);
DELETE FROM `st_stock_check_items` WHERE `check_id` IN (SELECT `id` FROM `st_stock_checks` WHERE `store_id` = 1);
DELETE FROM `st_stock_check_count_log` WHERE `check_id` IN (SELECT `id` FROM `st_stock_checks` WHERE `store_id` = 1);
DELETE FROM `st_product_units` WHERE `product_id` IN (SELECT `product_id` FROM `st_products` WHERE `store_id` = 1);
DELETE FROM `st_stock_transfers` WHERE (`from_store_id` = 1 OR `to_store_id` = 1);
DELETE FROM `st_bank_accounts` WHERE `store_id` = 1;
DELETE FROM `st_categories` WHERE `store_id` = 1;
DELETE FROM `st_customers` WHERE `store_id` = 1;
DELETE FROM `st_held_orders` WHERE `store_id` = 1;
DELETE FROM `st_imports` WHERE `store_id` = 1;
DELETE FROM `st_loyalty_points_log` WHERE `store_id` = 1;
DELETE FROM `st_loyalty_rewards` WHERE `store_id` = 1;
DELETE FROM `st_price_adjustments` WHERE `branch_id` = 1;
DELETE FROM `st_print_jobs` WHERE `store_id` = 1;
DELETE FROM `st_product_batches` WHERE `store_id` = 1;
DELETE FROM `st_products` WHERE `store_id` = 1;
DELETE FROM `st_promotions` WHERE `branch_id` = 1;
DELETE FROM `st_sale_batch_allocations` WHERE `store_id` = 1;
DELETE FROM `st_sales` WHERE `store_id` = 1;
DELETE FROM `st_shelves` WHERE `store_id` = 1;
DELETE FROM `st_shift_sessions` WHERE `store_id` = 1;
DELETE FROM `st_shifts` WHERE `store_id` = 1;
DELETE FROM `st_stock_checks` WHERE `store_id` = 1;
DELETE FROM `st_stock_writeoffs` WHERE `store_id` = 1;
DELETE FROM `st_suppliers` WHERE `store_id` = 1;
DELETE FROM `st_tbcompanyinfo` WHERE `branch_id` = 1;
DELETE FROM `st_tbexchange` WHERE `branch_id` = 1;
DELETE FROM `st_tbl_printer` WHERE `branch_id` = 1;
DELETE FROM `st_tbsale_save` WHERE `store_id` = 1;
DELETE FROM `st_units` WHERE `store_id` = 1;
DELETE FROM `st_zones` WHERE `store_id` = 1;

-- rows `st_tbsale_save_detail` 0
-- rows `st_import_details` 0
-- rows `st_stock_transfer_details` 0
-- rows `st_stock_check_items` 0
-- rows `st_stock_check_count_log` 0
INSERT IGNORE INTO `st_product_units` (`id`, `product_id`, `unit_name`, `multiplier`, `bprice`, `price`, `barcode`, `created_at`) VALUES
('18', '11002', 'ແພັກ', '6', '72000.00', '96000.00', '2022595983313', '2026-10-28 10:03:53'),
('20', '1120001', 'ແພັກ', '6', '42000.00', '60000.00', '2040739106862', '2026-10-30 15:12:51');
-- rows `st_product_units` 2
-- rows `st_stock_transfers` 0
INSERT IGNORE INTO `st_bank_accounts` (`id`, `bank_name`, `account_number`, `account_name`, `bank_code`, `bank_logo`, `qr_code_img`, `is_active`, `created_at`, `store_id`, `branch_no`) VALUES
('1', 'BCEL One', '123456789', 'Khola Dev', 'BCEL ONE', 'bank_1789632020_771.png', 'qr_bank_1789632020_540.jpeg', '1', '2026-09-17 15:00:20', '1', '1'),
('2', 'ພົງສະຫວັນ', 'aa', '344', 'ພົງສະຫວັນ', 'bank_1792480748_822.jfif', 'qr_bank_1792480748_885.jpeg', '1', '2026-10-20 14:19:08', '1', '2'),
('3', 'LDB', 'f', '554', 'LDB', 'bank_1792480933_324.png', 'qr_bank_1792480933_169.jpeg', '1', '2026-10-20 14:22:13', '1', '3'),
('4', 'JDB', 'ghfdgh', '23456', 'JDB', 'bank_1793426915_143.png', 'qr_bank_1792654945_186.jpeg', '1', '2026-10-22 14:42:25', '1', '4');
-- rows `st_bank_accounts` 4
INSERT IGNORE INTO `st_categories` (`category_id`, `category_name`, `description`, `created_at`, `store_id`, `branch_no`, `printer_id`) VALUES
('1', 'ເຄື່ອງດື່ມ', 'ນ້ຳດື່ມ, ນ້ຳຫວານ ແລະ ເຄື່ອງດື່ມທົ່ວໄປ', '2026-10-14 16:33:02', '1', '1', NULL),
('2', 'ຢາສູບ', 'ຢາສູບ ແລະ ອຸປະກອນ', '2026-10-14 16:33:02', '1', '2', NULL),
('3', 'ເຄື່ອງສຳອາງ', 'ເຄື່ອງສຳອາງ ແລະ ບຳລຸງຜິວ', '2026-10-14 16:33:02', '1', '3', NULL),
('4', 'ເບຍ', 'ເບຍລາວ, ເບຍນອກ ແລະ ເຄື່ອງດື່ມແອນກໍຮໍ', '2026-10-14 16:33:02', '1', '4', NULL),
('5', 'ສິນຄ້າ ບໍ່ມີບາໂຄ້ດ', 'ສິນຄ້າທົ່ວໄປ ບໍ່ມີບາໂຄ້ດ', '2026-10-14 16:33:02', '1', '5', NULL),
('6', 'ເຄື່ອງໃຊ້ຫ້ອງການ', 'ເຈ້ຍ A4 ແລະ ອຸປະກອນເຄື່ອງຂຽນ', '2026-10-14 16:33:02', '1', '6', NULL),
('7', 'ເຄື່ອງປຸງ & ໃຊ້ຄົວເຮືອນ', 'ແປງນົວ, ນ້ຳປາ, ນ້ຳມັນພືດ', '2026-10-14 16:33:02', '1', '7', NULL),
('8', 'ນົມ', 'ນົມກ່ອງ, ນົມສົ້ມ, ນົມຖົ່ວເຫຼືອງ', '2026-10-14 16:33:02', '1', '8', NULL),
('9', 'ເຂົ້າໜົມ', 'ເຂົ້າໜົມ, ມັນຝຣັ່ງທອດ, ບິສກິດ', '2026-10-14 16:33:02', '1', '9', NULL),
('10', 'ຫມີ່', 'ຫມີ່ໄວໄວ, ມ່າມ່າ, ຫມີ່ເກົາຫຼີ', '2026-10-14 16:33:02', '1', '10', NULL),
('11', 'ອາຫານກະປ໋ອງ', 'ປາກະປ໋ອງ ແລະ ອາຫານສຳເລັດຮູບ', '2026-10-14 16:33:02', '1', '11', NULL),
('12', 'ຂອງໃຊ້ສ່ວນຕົວ', 'ຢາສີຟັນ, ສະບູ, ຢາສະຜົມ', '2026-10-14 16:33:02', '1', '12', NULL),
('13', 'ຂອງຫວານ', '', '2026-10-19 12:50:15', '1', '13', NULL);
-- rows `st_categories` 13
-- rows `st_customers` 0
-- rows `st_held_orders` 0
-- rows `st_imports` 0
-- rows `st_loyalty_points_log` 0
-- rows `st_loyalty_rewards` 0
-- rows `st_price_adjustments` 0
-- rows `st_print_jobs` 0
INSERT IGNORE INTO `st_product_batches` (`batch_id`, `product_id`, `store_id`, `import_detail_id`, `batch_no`, `expiry_date`, `initial_qty`, `quantity`, `created_at`) VALUES
('1', '130004', '1', '1', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('2', '80005', '1', '2', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('3', '60003', '1', '3', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('4', '60002', '1', '4', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('5', '170006', '1', '5', NULL, NULL, '100', '0', '2026-09-25 10:12:45'),
('6', '130004', '1', '6', NULL, NULL, '100', '55', '2026-09-29 10:38:48'),
('7', '80005', '1', '7', NULL, NULL, '100', '76', '2026-09-29 10:38:48'),
('8', '60003', '1', '8', NULL, NULL, '100', '100', '2026-09-29 10:38:48'),
('9', '60002', '1', '9', NULL, NULL, '100', '31', '2026-09-29 10:38:48'),
('10', '170006', '1', '10', NULL, NULL, '100', '98', '2026-09-29 10:38:48'),
('11', '60002', '1', '11', NULL, NULL, '10', '10', '2026-09-29 10:42:07'),
('15', '30001', '1', '1', NULL, NULL, '20', '7', '2026-09-30 11:28:40'),
('16', '10002', '1', '2', NULL, NULL, '30', '9', '2026-09-30 11:28:40'),
('17', '30003', '1', '3', NULL, NULL, '12', '11', '2026-10-06 10:59:48'),
('27', '30003', '1', '11', NULL, NULL, '1', '1', '2026-10-09 10:25:50'),
('28', '30001', '1', '12', NULL, NULL, '1', '1', '2026-10-09 10:25:50'),
('29', '10002', '1', '13', NULL, NULL, '1', '1', '2026-10-09 10:25:50'),
('31', '30003', '1', '15', NULL, NULL, '5', '5', '2026-10-09 12:44:59'),
('32', '100004', '1', '16', NULL, NULL, '5', '5', '2026-10-09 12:44:59'),
('33', '30001', '1', '17', NULL, NULL, '5', '5', '2026-10-09 12:44:59');
-- rows `st_product_batches` 20
INSERT IGNORE INTO `st_products` (`code1`, `product_id`, `barcode`, `product_name`, `category_id`, `bprice`, `price`, `qty`, `unit`, `zone`, `shelf`, `img_url`, `cook`, `cut_qty`, `branch_id`, `store_id`, `branch_no`, `unit_id`, `supplier_id`, `generic_name`, `dosage_form`, `strength`, `drug_type`, `usage_instructions`, `drug_warning`, `min_qty`, `expiry_date`) VALUES
('1', '10001', '2048466388643', 'ເບຍລາວ ຂວດໃຫຍ່', '4', '10000.00', '15000.00', '150', 'ແກ້ວ', NULL, NULL, 'prod_beerlao_bottle_640ml.jpg', '0', '1', '1', '1', '1', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('2', '40012', '2004456587038', 'ເບຍລາວ ປ໋ອງສັ້ນ', '4', '9000.00', '12000.00', '120', 'ປ໋ອງ', NULL, NULL, 'prod_beerlao_can_330ml.jpg', '0', '1', '1', '1', '2', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('3', '40011', '2077782445808', 'ເບຍໄຮນິເກັນ', '4', '14000.00', '18000.00', '80', 'ແກ້ວ', NULL, NULL, 'prod_1792489130_7561.png', '0', '1', '1', '1', '3', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('4', '80002', '2073203999158', 'ນົມເລັກຕາຊອຍ', '8', '8000.00', '12000.00', '90', 'ກ່ອງ', NULL, NULL, 'prod_lactasoy_250ml.jpg', '0', '1', '1', '1', '4', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('5', '10002', '8851717041234', 'ນ້ຳດື່ມຫົວເສືອ', '1', '3000.00', '5000.00', '195', 'ຕຸກ', NULL, NULL, 'prod_tigerhead_water_600ml.jpg', '0', '1', '1', '1', '5', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('6', '10003', '8851959132014', 'ໂຄກກະປ໋ອງ', '1', '6000.00', '9000.00', '110', 'ປ໋ອງ', NULL, NULL, 'prod_cocacola_can_325ml.jpg', '0', '1', '1', '1', '6', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('7', '10004', '8851959142021', 'ເປັບຊີກະປ໋ອງ', '1', '6000.00', '9000.00', '105', 'ປ໋ອງ', NULL, NULL, 'prod_pepsi_can_325ml.jpg', '0', '1', '1', '1', '7', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('8', '10005', '2078263953966', 'ສະຕິງຂວດເຫຼືອງ', '1', '7000.00', '10000.00', '82', 'ຕຸກ', NULL, NULL, 'prod_carabao_150ml.jpg', '0', '1', '1', '1', '8', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('9', '10009', '2021360299994', 'M-150 ເຄື່ອງດື່ມຊູກຳລັງ', '1', '6000.00', '9000.00', '79', 'ແກ້ວ', NULL, NULL, 'prod_kratingdaeng_150ml.jpg', '0', '1', '1', '1', '9', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('10', '10010', '8850188200115', 'ຄາຣາບາວແດງ', '1', '6000.00', '9000.00', '94', 'ແກ້ວ', NULL, NULL, 'prod_carabao_150ml.jpg', '0', '1', '1', '1', '10', '22', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('11', '10011', '8850188300221', 'ກາເຟ ເບີດີ້ ໂຣບັສຕ້າ', '1', '8000.00', '12000.00', '14', 'ປ໋ອງ', NULL, NULL, 'prod_birdy_robusta_180ml.jpg', '0', '1', '1', '1', '11', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('12', '90004', '2075318035929', 'ເລ ມັນຝຣັ່ງທອດ', '9', '8000.00', '12000.00', '89', 'ຖົງ', NULL, NULL, 'prod_lays_classic_50g.jpg', '0', '1', '1', '1', '12', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('13', '90005', '8850029012345', 'ໂອຣິໂອ້ ລົດດັ້ງເດີມ', '9', '10000.00', '15000.00', '70', 'ຊອງ', NULL, NULL, 'prod_oreo_original_133g.jpg', '0', '1', '1', '1', '13', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('14', '90006', '8850029054321', 'ຄິດແຄັດ ຊັອກໂກແລັດ', '9', '8000.00', '12000.00', '80', 'ຊອງ', NULL, NULL, 'prod_kitkat_35g.jpg', '0', '1', '1', '1', '14', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('15', '100013', '2047888140464', 'ຫມີ່ໄວໄວ ຕົ້ມຍຳ', '10', '3000.00', '5000.00', '180', 'ຫໍ່', NULL, NULL, 'prod_mama_pork_60g.jpg', '0', '1', '1', '1', '15', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('16', '100016', '2094815295841', 'ມ່າມ່າ ຕົ້ມຍຳກຸ້ງ', '10', '4000.00', '6000.00', '153', 'ຫໍ່', NULL, NULL, 'prod_mama_tomyum_55g.jpg', '0', '1', '1', '1', '16', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('17', '70006', '2000458862838', 'ແປງນົວ ອາຍິໂນະໂມະໂຕ', '7', '12000.00', '16000.00', '58', 'ຖົງ', NULL, NULL, 'prod_ajinomoto_msg_250g.jpg', '0', '1', '1', '1', '17', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('18', '70007', '8850157001015', 'ນ້ຳປາ ທິບພະຣົດ', '7', '15000.00', '20000.00', '53', 'ຕຸກ', NULL, NULL, 'prod_tiparos_fishsauce_700ml.jpg', '0', '1', '1', '1', '18', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('19', '70008', '8850157002022', 'ນ້ຳມັນພືດ ມໍຣະກົດ', '7', '22000.00', '28000.00', '43', 'ຕຸກ', NULL, NULL, 'prod_morakot_palmoil_1l.jpg', '0', '1', '1', '1', '19', NULL, NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('20', '11001', '8850157003033', 'ປາກະປ໋ອງ ໂຣຊ່າ', '11', '12000.00', '16000.00', '80', 'ປ໋ອງ', NULL, NULL, 'prod_roza_mackerel_155g.jpg', '0', '1', '1', '1', '20', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('21', '11002', '8850157004044', 'ປາກະປ໋ອງ ສາມແມ່ຄົວ', '11', '12000.00', '16000.00', '22', 'ປ໋ອງ', NULL, NULL, 'prod_threelady_sardines_155g.jpg', '0', '1', '1', '1', '21', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('22', '12001', '8850157005055', 'ຢາສີຟັນ ຄອນເກດ', '12', '15000.00', '22000.00', '60', 'ກ່ອງ', NULL, NULL, 'prod_colgate_regular_150g.jpg', '0', '1', '1', '1', '22', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('23', '130023', '2026880235040', 'ຊານົມ', '13', '20000.00', '45000.00', '2', 'ອັນ', NULL, NULL, NULL, '0', '1', '1', '1', '23', '16', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '5', NULL),
('24', '110001', '8851959132052', 'ແຟນຕ້າ ນ້ຳສົ້ມ ກະປ໋ອງ', '1', '7000.00', '10000.00', '55', 'ປ໋ອງ', NULL, NULL, 'prod_fanta_orange_325ml.jpg', '0', '1', '1', '1', '24', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-09-30'),
('25', '110002', '8851959132038', 'ສະໄປຣທ໌ ກະປ໋ອງ', '1', '7000.00', '10000.00', '55', 'ປ໋ອງ', NULL, NULL, 'prod_sprite_can_325ml.jpg', '0', '1', '1', '1', '25', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-09-30'),
('26', '110003', '8850228000205', 'ສະປອນເຊີ ເຄື່ອງດື່ມເກືອແຮ່', '1', '8000.00', '12000.00', '44', 'ຂວດ', NULL, NULL, 'prod_sponsor_250ml.jpg', '0', '1', '1', '1', '26', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-10-31'),
('27', '110004', '8854698005012', 'ຊາຂຽວ ໂອອິຊິ ນ້ຳເຜິ້ງໝາກນາວ', '1', '10000.00', '14000.00', '55', 'ຂວດ', NULL, NULL, 'prod_oishi_greentea_380ml.jpg', '0', '1', '1', '1', '27', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '20', '2027-08-31'),
('28', '110005', '8850124064555', 'ກາເຟ ເນສກາເຟ ເຣດຄັບ', '1', '28000.00', '35000.00', '81', 'ຂວດ', NULL, NULL, 'prod_nescafe_redcup_45g.jpg', '0', '1', '1', '1', '28', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '10', '2028-03-31'),
('29', '180001', '8851717901019', 'ນົມສົ້ມ ດັຊມິວ 4in1', '8', '5500.00', '8000.00', '19', 'ກ່ອງ', NULL, NULL, 'prod_dutchmill_4in1_180ml.jpg', '0', '1', '1', '1', '29', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-06-30'),
('30', '180002', '8850188101016', 'ນົມຈືດ ໂຟຣໂມສ', '8', '6500.00', '9000.00', '29', 'ກ່ອງ', NULL, NULL, 'prod_foremost_milk_225ml.jpg', '0', '1', '1', '1', '30', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-07-31'),
('31', '180003', '8850124041808', 'ນົມໄມໂລ UHT', '8', '6500.00', '9000.00', '50', 'ກ່ອງ', NULL, NULL, 'prod_milo_uht_180ml.jpg', '0', '1', '1', '1', '31', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-07-31'),
('32', '180004', '8850124011405', 'ນົມກາໝີ ກະປ໋ອງ', '8', '9000.00', '13000.00', '17', 'ປ໋ອງ', NULL, NULL, 'prod_bearbrand_milk_140ml.jpg', '0', '1', '1', '1', '32', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '24', '2027-11-30'),
('33', '180005', '8850135003808', 'ນົມຂຸ້ນຫວານ ກາມະລິ', '8', '16000.00', '22000.00', '10', 'ປ໋ອງ', NULL, NULL, 'prod_mali_condensed_milk_380g.jpg', '0', '1', '1', '1', '33', '11', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-02-28'),
('34', '190001', '8850101001012', 'ປ໊ອກກີ້ ລົດຊັອກໂກແລັດ', '9', '11000.00', '15000.00', '8', 'ກ່ອງ', NULL, NULL, 'prod_pocky_chocolate_47g.jpg', '0', '1', '1', '1', '34', '12', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2027-08-31'),
('35', '190002', '8850332101011', 'ປາມຶກອົບ ເບນໂຕະ ລົດເຜັດ', '9', '11000.00', '15000.00', '38', 'ຊອງ', NULL, NULL, 'prod_bento_squid_20g.jpg', '0', '1', '1', '1', '35', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2027-09-30'),
('36', '190003', '8858705600015', 'ສາຫຼ່າຍທອດ ເຖົ້າແກ່ນ້ອຍ', '9', '18000.00', '24000.00', '48', 'ຊອງ', NULL, NULL, 'prod_taokaenoi_seaweed_32g.jpg', '0', '1', '1', '1', '36', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2027-08-31'),
('37', '190004', '8850157106017', 'ຂ້າວໜົມກຸ້ງ ຮານາມິ', '9', '11000.00', '15000.00', '11', 'ຖົງ', NULL, NULL, 'prod_hanami_prawn_60g.jpg', '0', '1', '1', '1', '37', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2027-07-31'),
('38', '1100001', '8801073110502', 'ໝີ່ເຜັດເກົາຫຼີ ຊຳຢັງ', '10', '22000.00', '28000.00', '65', 'ຊອງ', NULL, NULL, 'prod_samyang_buldak_140g.jpg', '0', '1', '1', '1', '38', '7', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '20', '2027-10-31'),
('39', '170001', '8850250001553', 'ຜົງປຸງລົດ ຣົດດີ ລົດໝູ', '7', '12000.00', '16000.00', '76', 'ຖົງ', NULL, NULL, 'prod_rosdee_pork_155g.jpg', '0', '1', '1', '1', '39', '15', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2028-06-30'),
('40', '170002', '8850124002007', 'ຊອດປຸງລົດ ແມັກກີ້', '7', '14000.00', '19000.00', '52', 'ຂວດ', NULL, NULL, 'prod_maggi_sauce_200ml.jpg', '0', '1', '1', '1', '40', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-05-31'),
('41', '170003', '8850643006004', 'ນ້ຳມັນຫອຍ ກາແມ່ຄົວ', '7', '26000.00', '34000.00', '28', 'ຂວດ', NULL, NULL, 'prod_maekrua_oyster_sauce_600ml.jpg', '0', '1', '1', '1', '41', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-06-30'),
('42', '170004', '8851932350013', 'ນ້ຳຢາລ້າງຖ້ວຍ ຊັນໄລ', '7', '17000.00', '23000.00', '47', 'ຂວດ', NULL, NULL, 'prod_sunlight_dishwash_500ml.jpg', '0', '1', '1', '1', '42', '5', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '12', '2028-12-31'),
('43', '1120001', '8851932100700', 'ສະບູກ້ອນ ລັກສ໌', '12', '7000.00', '10000.00', '11', 'ກ້ອນ', NULL, NULL, 'prod_lux_soap_70g.jpg', '0', '1', '1', '1', '43', '24', NULL, NULL, NULL, NULL, 'general', NULL, NULL, '15', '2028-12-31');
-- rows `st_products` 43
-- rows `st_promotions` 0
-- rows `st_sale_batch_allocations` 0
-- rows `st_sales` 0
-- rows `st_shelves` 0
INSERT IGNORE INTO `st_shift_sessions` (`id`, `shift_id`, `shift_name`, `store_id`, `branch_id`, `opened_by_user_id`, `opened_by_name`, `staff_names`, `open_time`, `last_active_at`, `initial_cash`, `closed_by_user_id`, `closed_by_name`, `close_time`, `system_sales_cash`, `system_sales_transfer`, `system_sales_debt`, `system_total_sales`, `system_total_bills`, `counted_sales_cash`, `difference_cash`, `status`, `notes`, `created_at`, `updated_at`) VALUES
('7', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-23 16:13:05', '2026-10-23 16:13:05', '0.00', NULL, 'ລະບົບ (ປິດອໍໂຕ້)', '2026-10-23 16:15:00', '0.00', '132000.00', '0.00', '132000.00', '1', NULL, NULL, 'closed', 'ປິດອໍໂຕ້: ຮອດໂມງປິດກະແລ້ວ ແລະ ບໍ່ມີພະນັກງານໃຊ້ງານ 15 ນາທີ', '2026-10-23 16:13:05', '2026-10-31 10:37:21'),
('8', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-23 16:29:12', '2026-10-23 16:29:12', '0.00', NULL, 'ລະບົບ (ປິດອໍໂຕ້)', '2026-10-23 16:29:12', '0.00', '125000.00', '0.00', '125000.00', '2', NULL, NULL, 'closed', 'ປິດອໍໂຕ້: ຮອດໂມງປິດກະແລ້ວ ແລະ ບໍ່ມີພະນັກງານໃຊ້ງານ 15 ນາທີ', '2026-10-23 16:29:12', '2026-10-31 10:37:21'),
('9', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-23 16:47:00', '2026-10-23 16:47:00', '0.00', '3', 'la', '2026-10-23 16:50:16', '39000.00', '153000.00', '0.00', '92000.00', '2', '39.00', '-38961.00', 'closed', '', '2026-10-23 16:47:00', '2026-10-31 10:37:21'),
('10', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-26 09:32:49', '2026-10-26 09:32:49', '100000.00', '3', 'la', '2026-10-26 10:55:50', '0.00', '622000.00', '0.00', '622000.00', '3', '0.00', '0.00', 'closed', '', '2026-10-26 09:32:49', '2026-10-31 10:37:21'),
('11', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-26 14:05:22', '2026-10-26 14:05:22', '50000.00', '3', 'la', '2026-10-26 14:08:04', '700000.00', '0.00', '0.00', '674000.00', '1', '680000.00', '-20000.00', 'closed', '', '2026-10-26 14:05:22', '2026-10-31 10:37:21'),
('12', '8', 'ກະແລງ', '1', '1', '3', 'la', 'la', '2026-10-26 14:09:07', '2026-10-26 14:09:07', '0.00', '3', 'la', '2026-10-26 14:11:55', '135000.00', '129000.00', '0.00', '264000.00', '3', '259000.00', '124000.00', 'closed', '', '2026-10-26 14:09:07', '2026-10-31 10:37:21'),
('16', '11', 'ກະບ່າຍ', '1', '1', '3', 'la', 'la', '2026-10-30 11:42:08', '2026-10-30 11:42:08', '50000.00', '3', 'la', '2026-10-30 11:43:08', '136000.00', '0.00', '0.00', '136000.00', '1', NULL, NULL, 'closed', 'ປິດກະອັດຕະໂນມັດ: ອອກຈາກລະບົບ', '2026-10-30 11:42:08', '2026-10-31 10:37:21'),
('17', '11', 'ກະບ່າຍ', '1', '1', '3', 'la', 'la', '2026-10-30 11:43:48', '2026-10-30 11:43:48', '50000.00', '3', 'la', '2026-10-30 11:44:17', '106000.00', '0.00', '0.00', '106000.00', '1', '106000.00', '0.00', 'closed', '', '2026-10-30 11:43:48', '2026-10-31 10:37:21'),
('18', '11', 'ກະບ່າຍ', '1', '1', '3', 'la', 'la', '2026-10-30 14:08:53', '2026-10-30 14:08:53', '50000.00', '0', 'ລະບົບ (ປິດອໍໂຕ້)', '2026-10-30 14:12:00', '0.00', '74000.00', '0.00', '74000.00', '1', NULL, NULL, 'closed', 'ປິດກະອັດຕະໂນມັດ: ໝົດໂມງກະ (ພະນັກງານບໍ່ອອນລາຍ)', '2026-10-30 14:08:53', '2026-10-31 10:37:21');
-- rows `st_shift_sessions` 9
-- rows `st_shifts` 0
-- rows `st_stock_checks` 0
-- rows `st_stock_writeoffs` 0
-- rows `st_suppliers` 0
INSERT IGNORE INTO `st_tbcompanyinfo` (`Id`, `com_name_la`, `com_address`, `com_tel`, `com_email`, `tax_id`, `img_url`, `qr_img`, `barcode`, `barcode2`, `latitude`, `longitude`, `start_date`, `expire_date`, `branch_id`, `vat_percent`, `tax_type`, `license_start_date`, `license_expire_date`, `receipt_footer_en`, `receipt_footer_th`, `receipt_footer_zh`) VALUES
('2', 'ຮ້ານ ຂາຍດີ ຈະເລີນຊັບ', 'ບ້ານ ໂນນຄໍ້ເໜືອ ເມືອງ ໄຊເສດຖາ ນະຄອນຫຼວງວຽງຈັນ', '020 55 949 007', '', '', 'logo_1792724248_506.jpg', '', '**ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່**', NULL, NULL, NULL, NULL, NULL, '1', '0.00', 'none', '2026-09-15', '2027-09-15', NULL, NULL, NULL);
-- rows `st_tbcompanyinfo` 1
-- rows `st_tbexchange` 0
INSERT IGNORE INTO `st_tbl_printer` (`id`, `name`, `ip_address`, `type`, `status`, `branch_id`, `paper_width`, `is_receipt_printer`, `print_offset_mm`) VALUES
('9', 'ເຮືອນຄົວ', '', 'browser', '1', '1', '80', '0', '0.0'),
('11', 'ທົດລອງ', '192.168.1.8', 'ip', '1', '1', '80', '0', '0.0'),
('12', 'XP-80C', 'XP-80C', 'usb', '1', '1', '80', '1', '0.0');
-- rows `st_tbl_printer` 3
-- rows `st_tbsale_save` 0
INSERT IGNORE INTO `st_units` (`unit_id`, `unit_name`, `description`, `created_at`, `store_id`, `branch_no`) VALUES
('5', 'ຂວດ', 'ຫົວໜ່ວຍບັນຈຸເປັນຂວດ (Bottle)', '2026-10-14 11:42:08', '1', '1'),
('6', 'ຫຼອດ', 'ຫົວໜ່ວຍບັນຈຸເປັນຫຼອດ (Tube)', '2026-10-14 11:42:08', '1', '2'),
('7', 'ຊອງ', 'ຫົວໜ່ວຍບັນຈຸເປັນຊອງ (Sachet)', '2026-10-14 11:42:08', '1', '3'),
('11', 'ປ໋ອງ', 'ຫົວໜ່ວຍບັນຈຸເປັນກະປ໋ອງ (Can)', '2026-10-14 15:07:38', '1', '4'),
('12', 'ກ່ອງ', 'ຫົວໜ່ວຍບັນຈຸເປັນກ່ອງ (Box)', '2026-10-14 15:07:38', '1', '5'),
('13', 'ແພັກ', 'ຫົວໜ່ວຍບັນຈຸເປັນແພັກ (Pack)', '2026-10-14 15:07:38', '1', '6'),
('14', 'ແກັດ', 'ຫົວໜ່ວຍບັນຈຸເປັນແກັດ ຫຼື ລັງ (Carton / Case)', '2026-10-14 15:07:38', '1', '7'),
('15', 'ຖົງ', 'ຫົວໜ່ວຍບັນຈຸເປັນຖົງ (Bag)', '2026-10-14 15:07:38', '1', '8'),
('16', 'ອັນ', 'ຫົວໜ່ວຍນັບເປັນອັນ (Piece)', '2026-10-14 15:07:38', '1', '9'),
('24', 'ກ້ອນ', 'ຫົວໜ່ວຍນັບເປັນກ້ອນ (Bar)', '2026-10-19 14:25:36', '1', '10'),
('22', 'ແກ້ວ', '', '2026-10-20 16:38:58', '1', '11');
-- rows `st_units` 11
-- rows `st_zones` 0
