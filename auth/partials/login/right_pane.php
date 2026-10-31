<?php
/*
 * ສ່ວນ: ຮູບກາຕູນເຄິ່ງຂວາ
 * ປ້າຍຟີເຈີລະບົບ ແລະ ຮູບ SVG ແມວນຳໂຊກຖືຄອມພິວເຕີ ພ້ອມແອນິເມຊັນ (ເງິນ, ຫຼຽນ, ແຂນ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ auth/login.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>            <!-- ================= RIGHT PANE: CUTE MULTI-ARM ANIMAL MASCOT HOLDING COMPUTER & MONEY ================= -->
            <div class="login-right-pane" id="loginRightPane">
                <!-- 4 ປ້າຍຟີເຈີລະບົບພ້ອມເອັບເຟັກ Shimmer & 3D Wave -->
                <div class="cartoon-badge badge-1">
                    <span class="cb-icon"><i class="fas fa-barcode"></i></span>
                    <span><?php echo tlh('ຂາຍໜ້າຮ້ານວ່ອງໄວ'); ?></span>
                </div>

                <div class="cartoon-badge badge-2">
                    <span class="cb-icon"><i class="fas fa-boxes"></i></span>
                    <span><?php echo tlh('ຕັດສະຕັອກອັດຕະໂນມັດ'); ?></span>
                </div>

                <div class="cartoon-badge badge-3">
                    <span class="cb-icon"><i class="fas fa-chart-pie"></i></span>
                    <span><?php echo tlh('ລາຍງານກຳໄລ'); ?> Real-Time</span>
                </div>

                <div class="cartoon-badge badge-4">
                    <span class="cb-icon"><i class="fas fa-store-alt"></i></span>
                    <span><?php echo tlh('ຮອງຮັບຫຼາຍສາຂາ'); ?></span>
                </div>

                <!-- ກະຕູນສັດໜ້າຮັກ (ແມວມະຫາເສດຖີ Superhero ຫຼາຍມື) ເຄື່ອນທີ່ຈັບຄອມ ຈັບເງິນ ຈັບເຄື່ອງຍິງບາໂຄ້ດ ຈັບບິນ -->
                <div class="cute-cartoon-stage">
                    <svg class="cute-cartoon-svg" viewBox="0 0 460 430" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <!-- Cute Fortune Cat Fur Gradients -->
                            <linearGradient id="catBodyGrad" x1="150" y1="55" x2="310" y2="355" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#FFFFFF"/>
                                <stop offset="70%" stop-color="#FFFBEB"/>
                                <stop offset="100%" stop-color="#FEF3C7"/>
                            </linearGradient>
                            <linearGradient id="catGoldPatch" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#FBBF24"/>
                                <stop offset="100%" stop-color="#D97706"/>
                            </linearGradient>
                            <linearGradient id="catEarPink" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#FDA4AF"/>
                                <stop offset="100%" stop-color="#F43F5E"/>
                            </linearGradient>
                            <!-- Superhero Cape Gradients -->
                            <linearGradient id="superCapeBack" x1="110" y1="160" x2="350" y2="365" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#1D4ED8"/>
                                <stop offset="100%" stop-color="#1E3A8A"/>
                            </linearGradient>
                            <linearGradient id="superCapeStrap" x1="150" y1="150" x2="310" y2="195" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#38BDF8"/>
                                <stop offset="50%" stop-color="#2563EB"/>
                                <stop offset="100%" stop-color="#1D4ED8"/>
                            </linearGradient>
                            <!-- Laptop & Dashboard Gradients -->
                            <linearGradient id="screenFrame" x1="80" y1="200" x2="380" y2="365" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#334155"/>
                                <stop offset="100%" stop-color="#0F172A"/>
                            </linearGradient>
                            <linearGradient id="dashBg" x1="90" y1="205" x2="370" y2="355" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="#0F172A"/>
                                <stop offset="100%" stop-color="#1E293B"/>
                            </linearGradient>
                            <linearGradient id="barBlue" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#38BDF8"/>
                                <stop offset="100%" stop-color="#0284C7"/>
                            </linearGradient>
                            <linearGradient id="barGreen" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#4ADE80"/>
                                <stop offset="100%" stop-color="#16A34A"/>
                            </linearGradient>
                            <linearGradient id="barGold" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#FDE047"/>
                                <stop offset="100%" stop-color="#D97706"/>
                            </linearGradient>
                            <!-- Money & Gold Coin Gradients -->
                            <linearGradient id="kipNoteGreen" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#34D399"/>
                                <stop offset="100%" stop-color="#059669"/>
                            </linearGradient>
                            <linearGradient id="kipNoteTeal" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#2DD4BF"/>
                                <stop offset="100%" stop-color="#0D9488"/>
                            </linearGradient>
                            <linearGradient id="goldCoinGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#FEF08A"/>
                                <stop offset="45%" stop-color="#FACC15"/>
                                <stop offset="100%" stop-color="#CA8A04"/>
                            </linearGradient>
                            <linearGradient id="scanLaserGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#38BDF8" stop-opacity="0"/>
                                <stop offset="50%" stop-color="#38BDF8" stop-opacity="0.38"/>
                                <stop offset="100%" stop-color="#34D399" stop-opacity="0"/>
                            </linearGradient>
                            <!-- Camera Photo Flash Gradients for Barcode Scanner -->
                            <radialGradient id="cameraFlashGlow" cx="50%" cy="50%" r="50%">
                                <stop offset="0%" stop-color="#FFFFFF" stop-opacity="1"/>
                                <stop offset="35%" stop-color="#FFFFFF" stop-opacity="0.95"/>
                                <stop offset="65%" stop-color="#E0F2FE" stop-opacity="0.65"/>
                                <stop offset="100%" stop-color="#38BDF8" stop-opacity="0"/>
                            </radialGradient>
                            <radialGradient id="cameraFlashAmbient" cx="50%" cy="50%" r="50%">
                                <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.85"/>
                                <stop offset="40%" stop-color="#BAE6FD" stop-opacity="0.45"/>
                                <stop offset="75%" stop-color="#38BDF8" stop-opacity="0.15"/>
                                <stop offset="100%" stop-color="#38BDF8" stop-opacity="0"/>
                            </radialGradient>
                        </defs>

                        <!-- ================= RISING MONEY FOUNTAIN STREAM ================= -->
                        <g class="money-fountain-stream s1">
                            <circle cx="135" cy="185" r="10" fill="url(#goldCoinGrad)" stroke="#FEF08A" stroke-width="1.5"/>
                            <text x="135" y="189" fill="#78350F" font-size="10" font-weight="900" text-anchor="middle">₭</text>
                        </g>
                        <g class="money-fountain-stream s2">
                            <rect x="305" y="175" width="46" height="22" rx="4" fill="url(#kipNoteGreen)" stroke="#ECFDF5" stroke-width="1.2"/>
                            <text x="328" y="189" fill="#FEF08A" font-size="8" font-weight="900" text-anchor="middle">+100K <?php echo kipSymbol(); ?></text>
                        </g>
                        <g class="money-fountain-stream s3">
                            <circle cx="315" cy="182" r="9.5" fill="url(#goldCoinGrad)" stroke="#FEF08A" stroke-width="1.5"/>
                            <text x="315" y="186" fill="#78350F" font-size="9.5" font-weight="900" text-anchor="middle">₭</text>
                        </g>

                        <!-- ================= SUPERHERO BLUE CAPE FLUTTERING BEHIND ANIMAL ================= -->
                        <path class="anim-super-cape" d="M148 168 L98 340 Q165 362 230 348 Q295 362 362 340 L312 168 Z" fill="url(#superCapeBack)" stroke="#60A5FA" stroke-width="2"/>

                        <!-- ================= 6 MULTI-ARMS STEMMING FROM THE SAME ARM BASE (ອອກມາຈາກແຂນດຽວກັນ: ຖັດກັນຂຶ້ນ ແລ້ວ ຖັດກັນລົງມາ) ================= -->

                        <!-- ARM 1 (ຄູ່ທີ 1 - ຊ້າຍກາງ): ຈັບບາໂຄ້ດສະແກນສິນຄ້າ -->
                        <g class="anim-cycle-arm cycle-arm-left arm-seq-1">
                            <path d="M158 182 C120 176, 88 165, 62 152" stroke="#FFFFFF" stroke-width="24" stroke-linecap="round"/>
                            <path d="M158 182 C120 176, 88 165, 62 152" stroke="#FDE68A" stroke-width="24" stroke-linecap="round" stroke-opacity="0.22"/>
                            <circle cx="60" cy="150" r="14" fill="#FFFFFF" stroke="#FCD34D" stroke-width="1.8"/>
                            <circle cx="60" cy="150" r="5.5" fill="#FDA4AF"/>
                            <!-- Barcode Scanner Gun + Product Barcode Tag (100% inside viewBox) -->
                            <g transform="translate(18, 106) rotate(-8)">
                                <!-- Barcode Tag Being Scanned -->
                                <rect x="0" y="-22" width="40" height="20" rx="3.5" fill="#FFFFFF" stroke="#38BDF8" stroke-width="1.4"/>
                                <line x1="6" y1="-18" x2="6" y2="-6" stroke="#0F172A" stroke-width="1.8"/>
                                <line x1="10" y1="-18" x2="10" y2="-6" stroke="#0F172A" stroke-width="1.1"/>
                                <line x1="14" y1="-18" x2="14" y2="-6" stroke="#0F172A" stroke-width="2.2"/>
                                <line x1="19" y1="-18" x2="19" y2="-6" stroke="#0F172A" stroke-width="1.2"/>
                                <line x1="23" y1="-18" x2="23" y2="-6" stroke="#0F172A" stroke-width="1.8"/>
                                <line x1="28" y1="-18" x2="28" y2="-6" stroke="#0F172A" stroke-width="1.4"/>
                                <line x1="33" y1="-18" x2="33" y2="-6" stroke="#0F172A" stroke-width="2"/>
                                <!-- Barcode Tag Surface Flash Reflection -->
                                <rect class="flash-barcode-overlay" x="0" y="-22" width="40" height="20" rx="3.5" fill="#FFFFFF"/>
                                <!-- Laser Scan Beam -->
                                <polygon class="anim-live-pill" points="26,18 2,-6 38,-6" fill="#EF4444" fill-opacity="0.4"/>
                                <line x1="0" y1="-6" x2="40" y2="-6" stroke="#EF4444" stroke-width="2.4" stroke-linecap="round"/>
                                <!-- Scanner Gun -->
                                <rect x="16" y="12" width="38" height="18" rx="6" fill="#0284C7" stroke="#E0F2FE" stroke-width="1.5"/>
                                <rect x="18" y="15" width="8" height="12" rx="2" fill="#0F172A"/>
                                <path d="M36 29 L42 48 L52 46 L46 29 Z" fill="#1E293B" stroke="#94A3B8" stroke-width="1.2"/>
                                <rect x="33" y="30" width="5" height="7" rx="2" fill="#FACC15"/>
                                <!-- Camera Photo Flash Effect Burst (Centered on Barcode Tag at 20, -11) -->
                                <g class="anim-camera-flash" transform="translate(20, -11)">
                                    <circle class="flash-ambient" cx="0" cy="0" r="42" fill="url(#cameraFlashAmbient)"/>
                                    <circle class="flash-ring" cx="0" cy="0" r="10" fill="none" stroke="#FFFFFF" stroke-width="2.5"/>
                                    <circle class="flash-core" cx="0" cy="0" r="15" fill="url(#cameraFlashGlow)"/>
                                    <g class="flash-star">
                                        <path d="M0 -38 L 2.5 -4 L 38 0 L 2.5 4 L 0 38 L -2.5 4 L -38 0 L -2.5 -4 Z" fill="#FFFFFF"/>
                                        <path d="M0 -24 L 1.8 -2.5 L 24 0 L 1.8 2.5 L 0 24 L -1.8 2.5 L -24 0 L -1.8 -2.5 Z" fill="#FFFFFF" fill-opacity="0.9" transform="rotate(45)"/>
                                        <circle cx="0" cy="0" r="4.5" fill="#FFFFFF"/>
                                    </g>
                                </g>
                            </g>
                        </g>

                        <!-- ARM 2 (ຄູ່ທີ 1 - ຂວາກາງ): ຈັບໃບບິນຂາຍສິນຄ້າ POS -->
                        <g class="anim-cycle-arm cycle-arm-right arm-seq-2">
                            <path d="M302 182 C340 176, 372 165, 398 152" stroke="#FFFFFF" stroke-width="24" stroke-linecap="round"/>
                            <path d="M302 182 C340 176, 372 165, 398 152" stroke="#FDE68A" stroke-width="24" stroke-linecap="round" stroke-opacity="0.22"/>
                            <circle cx="400" cy="150" r="14" fill="#FFFFFF" stroke="#FCD34D" stroke-width="1.8"/>
                            <circle cx="400" cy="150" r="5.5" fill="#FDA4AF"/>
                            <!-- Cute POS Receipt Bill Held in Right Paw -->
                            <g transform="translate(384, 104) rotate(12)">
                                <path d="M0 0 L46 0 L46 58 L38 53 L31 58 L23 53 L15 58 L8 53 L0 58 Z" fill="#FFFFFF" stroke="#38BDF8" stroke-width="1.6"/>
                                <rect x="6" y="6" width="34" height="7" rx="2" fill="#2563EB"/>
                                <text x="23" y="11.2" fill="#FFFFFF" font-size="5.2" font-weight="900" text-anchor="middle">RECEIPT</text>
                                <line x1="7" y1="18" x2="39" y2="18" stroke="#64748B" stroke-width="2" stroke-linecap="round"/>
                                <line x1="7" y1="24" x2="32" y2="24" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"/>
                                <line x1="7" y1="30" x2="37" y2="30" stroke="#94A3B8" stroke-width="2" stroke-linecap="round"/>
                                <rect x="6" y="36" width="34" height="10" rx="2.5" fill="#DCFCE7" stroke="#86EFAC" stroke-width="1"/>
                                <text x="23" y="43" fill="#15803D" font-size="6.5" font-weight="900" text-anchor="middle">PAID <?php echo kipSymbol(); ?></text>
                            </g>
                        </g>

                        <!-- ARM 3 (ຄູ່ທີ 2 - ຊ້າຍເທິງ): ຈັບປຶກເງິນກີບ 100,000 ₭ -->
                        <g class="anim-cycle-arm cycle-arm-left arm-seq-3">
                            <path d="M158 182 C132 140, 104 105, 80 80" stroke="#FFFFFF" stroke-width="25" stroke-linecap="round"/>
                            <path d="M158 182 C132 140, 104 105, 80 80" stroke="#FDE68A" stroke-width="25" stroke-linecap="round" stroke-opacity="0.25"/>
                            <circle cx="78" cy="78" r="14.5" fill="#FFFFFF" stroke="#FCD34D" stroke-width="1.8"/>
                            <circle cx="78" cy="78" r="5.5" fill="#FDA4AF"/>
                            <!-- Fan of 3 Kip Banknotes Held in Left Upper Paw -->
                            <g transform="translate(20, 26) rotate(-15)">
                                <rect x="10" y="-8" width="76" height="38" rx="6" transform="rotate(14 48 11)" fill="#047857" stroke="#A7F3D0" stroke-width="1.2"/>
                                <rect x="4" y="-4" width="76" height="38" rx="6" transform="rotate(6 42 15)" fill="url(#kipNoteTeal)" stroke="#CCFBF1" stroke-width="1.2"/>
                                <rect x="0" y="0" width="78" height="39" rx="6" fill="url(#kipNoteGreen)" stroke="#ECFDF5" stroke-width="1.8"/>
                                <rect x="5" y="5" width="68" height="29" rx="4" fill="none" stroke="#A7F3D0" stroke-width="1" stroke-dasharray="3 2"/>
                                <circle cx="21" cy="19.5" r="9" fill="#065F46" fill-opacity="0.55"/>
                                <text x="21" y="23.5" fill="#FEF08A" font-size="12" font-weight="900" text-anchor="middle">₭</text>
                                <text x="51" y="18.5" fill="#FFFFFF" font-size="9" font-weight="900" text-anchor="middle">100,000</text>
                                <text x="51" y="28" fill="#D1FAE5" font-size="6" font-weight="800" text-anchor="middle">LAO KIP</text>
                            </g>
                        </g>

                        <!-- ARM 4 (ຄູ່ທີ 2 - ຂວາເທິງ): ຈັບປຶກເງິນກີບ 50,000 ₭ + ຫຼຽນຄຳ -->
                        <g class="anim-cycle-arm cycle-arm-right arm-seq-4">
                            <path d="M302 182 C328 140, 356 105, 380 80" stroke="#FFFFFF" stroke-width="25" stroke-linecap="round"/>
                            <path d="M302 182 C328 140, 356 105, 380 80" stroke="#FDE68A" stroke-width="25" stroke-linecap="round" stroke-opacity="0.25"/>
                            <circle cx="382" cy="78" r="14.5" fill="#FFFFFF" stroke="#FCD34D" stroke-width="1.8"/>
                            <circle cx="382" cy="78" r="5.5" fill="#FDA4AF"/>
                            <!-- Stack of 50,000 Kip Banknotes + Gold Coin Held in Right Upper Paw -->
                            <g transform="translate(352, 26) rotate(15)">
                                <rect x="-6" y="5" width="76" height="38" rx="6" fill="#0F766E" stroke="#99F6E4" stroke-width="1.2"/>
                                <rect x="0" y="0" width="76" height="38" rx="6" fill="url(#kipNoteTeal)" stroke="#F0FDFA" stroke-width="1.8"/>
                                <rect x="5" y="5" width="66" height="28" rx="4" fill="none" stroke="#99F6E4" stroke-width="1" stroke-dasharray="3 2"/>
                                <circle cx="21" cy="19" r="8.5" fill="#115E59" fill-opacity="0.55"/>
                                <text x="21" y="23" fill="#FEF08A" font-size="11.5" font-weight="900" text-anchor="middle">₭</text>
                                <text x="50" y="18" fill="#FFFFFF" font-size="8.5" font-weight="900" text-anchor="middle">50,000</text>
                                <text x="50" y="27" fill="#CCFBF1" font-size="6" font-weight="800" text-anchor="middle">LAO KIP</text>
                                <circle cx="72" cy="4" r="13.5" fill="url(#goldCoinGrad)" stroke="#FEF9C3" stroke-width="1.8"/>
                                <text x="72" y="9" fill="#78350F" font-size="13.5" font-weight="900" text-anchor="middle">₭</text>
                            </g>
                        </g>

                        <!-- ARM 5 (ຄູ່ທີ 3 - ຊ້າຍລຸ່ມ): ຈັບຖົງເງິນຄຳ ₭ -->
                        <g class="anim-cycle-arm cycle-arm-left arm-seq-5">
                            <path d="M158 182 C120 206, 84 225, 48 230" stroke="#FFFFFF" stroke-width="23" stroke-linecap="round"/>
                            <circle cx="46" cy="228" r="13.5" fill="#FFFFFF" stroke="#FCD34D" stroke-width="1.8"/>
                            <!-- Rich Gold Money Bag Held in Left Lower Paw -->
                            <g transform="translate(14, 196) rotate(-8)">
                                <path d="M20 12 L12 2 C18 0, 28 0, 34 2 L26 12 Z" fill="#F59E0B" stroke="#FEF08A" stroke-width="1.2"/>
                                <path d="M8 28 C8 14, 38 14, 38 28 C42 44, 36 52, 23 52 C10 52, 4 44, 8 28 Z" fill="url(#goldCoinGrad)" stroke="#FEF9C3" stroke-width="1.6"/>
                                <rect x="15" y="11" width="16" height="4" rx="2" fill="#EF4444"/>
                                <text x="23" y="39" fill="#78350F" font-size="17" font-weight="900" text-anchor="middle">₭</text>
                            </g>
                        </g>

                        <!-- ARM 6 (ຄູ່ທີ 3 - ຂວາລຸ່ມ): ຈັບເຄື່ອງຄິດໄລ່ຍອດຂາຍ -->
                        <g class="anim-cycle-arm cycle-arm-right arm-seq-6">
                            <path d="M302 182 C340 206, 376 225, 412 230" stroke="#FFFFFF" stroke-width="23" stroke-linecap="round"/>
                            <circle cx="414" cy="228" r="13.5" fill="#FFFFFF" stroke="#FCD34D" stroke-width="1.8"/>
                            <!-- Cute Calculator / Payment Terminal Held in Right Lower Paw -->
                            <g transform="translate(394, 198) rotate(12)">
                                <rect x="0" y="0" width="38" height="48" rx="6" fill="#1E293B" stroke="#FACC15" stroke-width="1.8"/>
                                <rect x="5" y="5" width="28" height="11" rx="2.5" fill="#10B981"/>
                                <text x="31" y="13" fill="#FFFFFF" font-size="6.5" font-weight="900" text-anchor="end">888K</text>
                                <rect x="5" y="20" width="7" height="6" rx="1.5" fill="#38BDF8"/>
                                <rect x="15" y="20" width="7" height="6" rx="1.5" fill="#38BDF8"/>
                                <rect x="25" y="20" width="8" height="6" rx="1.5" fill="#F59E0B"/>
                                <rect x="5" y="29" width="7" height="6" rx="1.5" fill="#475569"/>
                                <rect x="15" y="29" width="7" height="6" rx="1.5" fill="#475569"/>
                                <rect x="25" y="29" width="8" height="14" rx="1.5" fill="#22C55E"/>
                                <rect x="5" y="38" width="17" height="5" rx="1.5" fill="#475569"/>
                            </g>
                        </g>

                        <!-- ================= FLOATING 3D GOLD COINS & SPARKLES ================= -->
                        <g class="anim-gold-coin coin-b">
                            <circle cx="28" cy="316" r="14.5" fill="url(#goldCoinGrad)" stroke="#FEF9C3" stroke-width="1.8"/>
                            <text x="28" y="321" fill="#78350F" font-size="14.5" font-weight="900" text-anchor="middle">₭</text>
                        </g>
                        <g class="anim-gold-coin coin-c">
                            <circle cx="432" cy="314" r="14.5" fill="url(#goldCoinGrad)" stroke="#FEF9C3" stroke-width="1.8"/>
                            <text x="432" y="319" fill="#78350F" font-size="14.5" font-weight="900" text-anchor="middle">₭</text>
                        </g>
                        <g class="anim-sparkle sp-1" transform="translate(136, 34)">
                            <path d="M0 -8 L2.2 -2.2 L8 0 L2.2 2.2 L0 8 L-2.2 2.2 L-8 0 L-2.2 -2.2 Z" fill="#FDE047"/>
                        </g>
                        <g class="anim-sparkle sp-2" transform="translate(324, 36)">
                            <path d="M0 -7 L2 -2 L7 0 L2 2 L0 7 L-2 2 L-7 0 L-2 -2 Z" fill="#34D399"/>
                        </g>

                        <!-- ================= CUTE CHUBBY FORTUNE CAT MASCOT BODY & HEAD ================= -->
                        <!-- Cute Kicking Orange-Gold Cat Feet Below Laptop -->
                        <g class="anim-leg-left">
                            <path d="M198 345 L192 384" stroke="#F59E0B" stroke-width="18" stroke-linecap="round"/>
                            <ellipse cx="188" cy="390" rx="20" ry="11" fill="#FBBF24" stroke="#FFFFFF" stroke-width="2.5"/>
                            <circle cx="180" cy="388" r="3" fill="#FFFBEB"/>
                            <circle cx="188" cy="386" r="3" fill="#FFFBEB"/>
                            <circle cx="196" cy="388" r="3" fill="#FFFBEB"/>
                        </g>
                        <g class="anim-leg-right">
                            <path d="M262 345 L268 384" stroke="#F59E0B" stroke-width="18" stroke-linecap="round"/>
                            <ellipse cx="272" cy="390" rx="20" ry="11" fill="#FBBF24" stroke="#FFFFFF" stroke-width="2.5"/>
                            <circle cx="264" cy="388" r="3" fill="#FFFBEB"/>
                            <circle cx="272" cy="386" r="3" fill="#FFFBEB"/>
                            <circle cx="280" cy="388" r="3" fill="#FFFBEB"/>
                        </g>

                        <!-- Chubby White-Cream Cat Body & Head Group -->
                        <g class="anim-chibi-head" id="chibiHeadGroup">
                            <!-- Left Pointed Cat Ear (Twitching) -->
                            <g class="anim-cat-ear-left">
                                <path d="M162 96 L148 36 Q182 46 198 72 Z" fill="url(#catBodyGrad)" stroke="#F59E0B" stroke-width="2.5" stroke-linejoin="round"/>
                                <path d="M164 86 L155 48 Q178 55 189 72 Z" fill="url(#catEarPink)"/>
                            </g>

                            <!-- Right Pointed Cat Ear (Twitching) -->
                            <g class="anim-cat-ear-right">
                                <path d="M298 96 L312 36 Q278 46 262 72 Z" fill="url(#catBodyGrad)" stroke="#F59E0B" stroke-width="2.5" stroke-linejoin="round"/>
                                <path d="M296 86 L305 48 Q282 55 271 72 Z" fill="url(#catEarPink)"/>
                            </g>

                            <!-- Chubby Pear-Shaped Cat Head & Upper Torso (Like Reference Mascot Shape, but Cute Cat!) -->
                            <path d="M156 122 C156 64, 304 64, 304 122 C312 162, 318 205, 318 235 L142 235 C142 205, 148 162, 156 122 Z" fill="url(#catBodyGrad)" stroke="#FDE68A" stroke-width="2"/>

                            <!-- Cute Golden Forehead Tabby Stripes / Fortune Mark -->
                            <path d="M214 68 C220 82, 240 82, 246 68 Z" fill="url(#catGoldPatch)"/>
                            <rect x="226" y="65" width="8" height="18" rx="4" fill="#D97706"/>
                            <rect x="212" y="70" width="6" height="13" rx="3" transform="rotate(-18 212 70)" fill="#F59E0B"/>
                            <rect x="242" y="70" width="6" height="13" rx="3" transform="rotate(18 242 70)" fill="#F59E0B"/>

                            <!-- Cute Big Sparkling Cat Eyes (with Blink + Mouse Tracking) -->
                            <g class="anim-chibi-eyes">
                                <g class="anim-eye-pupils" id="chibiEyePupils">
                                    <!-- Left Eye -->
                                    <ellipse cx="198" cy="116" rx="12" ry="14" fill="#0F172A"/>
                                    <ellipse cx="198" cy="118" rx="9" ry="10" fill="#1D4ED8"/>
                                    <circle cx="194" cy="110" r="4.5" fill="#FFFFFF"/>
                                    <circle cx="202" cy="121" r="2.4" fill="#FFFFFF"/>
                                    <!-- Right Eye -->
                                    <ellipse cx="262" cy="116" rx="12" ry="14" fill="#0F172A"/>
                                    <ellipse cx="262" cy="118" rx="9" ry="10" fill="#1D4ED8"/>
                                    <circle cx="258" cy="110" r="4.5" fill="#FFFFFF"/>
                                    <circle cx="266" cy="121" r="2.4" fill="#FFFFFF"/>
                                </g>
                            </g>

                            <!-- Cute Rosy Pink Cheeks -->
                            <ellipse cx="177" cy="130" rx="11.5" ry="6.5" fill="#FB7185" fill-opacity="0.62"/>
                            <ellipse cx="283" cy="130" rx="11.5" ry="6.5" fill="#FB7185" fill-opacity="0.62"/>

                            <!-- Cute Cat Whiskers Left & Right -->
                            <line x1="146" y1="122" x2="168" y2="125" stroke="#94A3B8" stroke-width="2.2" stroke-linecap="round"/>
                            <line x1="144" y1="132" x2="167" y2="132" stroke="#94A3B8" stroke-width="2.2" stroke-linecap="round"/>
                            <line x1="314" y1="122" x2="292" y2="125" stroke="#94A3B8" stroke-width="2.2" stroke-linecap="round"/>
                            <line x1="316" y1="132" x2="293" y2="132" stroke="#94A3B8" stroke-width="2.2" stroke-linecap="round"/>

                            <!-- Cute Pink Triangle Cat Nose & Happy ":3" Cat Mouth -->
                            <polygon points="224,121 236,121 230,128" fill="#F43F5E"/>
                            <g class="anim-chibi-mouth">
                                <path d="M220 134 Q225 142 230 134 Q235 142 240 134 Z" fill="#FB7185"/>
                                <path d="M216 131 Q223 139 230 131 Q237 139 244 131" stroke="#881337" stroke-width="2.8" stroke-linecap="round" fill="none"/>
                            </g>

                            <!-- Royal Blue Superhero Cape Front Strap & Golden Kip Bell Collar -->
                            <path d="M148 164 Q230 190 312 164 L306 176 Q230 202 154 176 Z" fill="url(#superCapeStrap)"/>
                            <!-- Golden Fortune Bell on Collar -->
                            <circle cx="230" cy="182" r="12" fill="url(#goldCoinGrad)" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="230" y="186.5" fill="#78350F" font-size="11" font-weight="900" text-anchor="middle">₭</text>
                        </g>

                        <!-- ================= LARGE WIDESCREEN COMPUTER HELD IN FRONT WITH LIVE DASHBOARD ================= -->
                        <g class="anim-laptop-group" transform="translate(72, 192)">
                            <!-- Outer Monitor / Tablet-Laptop Frame -->
                            <rect x="0" y="0" width="316" height="168" rx="14" fill="url(#screenFrame)" stroke="#60A5FA" stroke-width="2.5"/>
                            <!-- Inner Dashboard Screen -->
                            <rect x="8" y="8" width="300" height="152" rx="9" fill="url(#dashBg)"/>

                            <!-- 1. Top Dashboard Header Bar -->
                            <rect x="8" y="8" width="300" height="20" rx="9" fill="#0284C7"/>
                            <rect x="8" y="18" width="300" height="10" fill="#0284C7"/>
                            <!-- Window Dots -->
                            <circle cx="19" cy="18" r="3" fill="#FCA5A5"/>
                            <circle cx="29" cy="18" r="3" fill="#FDE047"/>
                            <circle cx="39" cy="18" r="3" fill="#86EFAC"/>
                            <text x="52" y="21" fill="#FFFFFF" font-size="8.5" font-weight="900" letter-spacing="0.3">WlaoDev POS • <?php echo tlh('ລາຍງານ &amp; ກາຟວິເຄາະລະອຽດ'); ?></text>
                            <rect class="anim-live-pill" x="268" y="13" width="32" height="10" rx="5" fill="#10B981"/>
                            <text x="284" y="20" fill="#FFFFFF" font-size="6" font-weight="900" text-anchor="middle">LIVE</text>

                            <!-- 2. Top 3 KPI Summary Cards (Numbers Tick Live via JS!) -->
                            <!-- KPI 1: Total Sales -->
                            <rect x="16" y="33" width="90" height="27" rx="5" fill="#1E293B" stroke="#38BDF8" stroke-width="1"/>
                            <text x="22" y="43" fill="#94A3B8" font-size="6.2" font-weight="700"><?php echo tlh('ຍອດຂາຍລວມ'); ?></text>
                            <text id="svgLiveSales" x="22" y="55" fill="#38BDF8" font-size="9.2" font-weight="900">48,500,000 <?php echo kipSymbol(); ?></text>
                            <rect x="84" y="37" width="18" height="8" rx="3" fill="#064E3B"/>
                            <text x="93" y="43" fill="#34D399" font-size="5.5" font-weight="900" text-anchor="middle">+24%</text>

                            <!-- KPI 2: Net Profit -->
                            <rect x="113" y="33" width="90" height="27" rx="5" fill="#1E293B" stroke="#4ADE80" stroke-width="1"/>
                            <text x="119" y="43" fill="#94A3B8" font-size="6.2" font-weight="700"><?php echo tlh('ກຳໄລສຸດທິ'); ?></text>
                            <text id="svgLiveProfit" x="119" y="55" fill="#4ADE80" font-size="9.2" font-weight="900">14,200,000 <?php echo kipSymbol(); ?></text>
                            <rect x="181" y="37" width="18" height="8" rx="3" fill="#064E3B"/>
                            <text x="190" y="43" fill="#34D399" font-size="5.5" font-weight="900" text-anchor="middle">+18%</text>

                            <!-- KPI 3: Total Orders -->
                            <rect x="210" y="33" width="90" height="27" rx="5" fill="#1E293B" stroke="#FACC15" stroke-width="1"/>
                            <text x="216" y="43" fill="#94A3B8" font-size="6.2" font-weight="700"><?php echo tlh('ບິນຂາຍມື້ນີ້'); ?></text>
                            <text id="svgLiveBills" x="216" y="55" fill="#FDE047" font-size="9.5" font-weight="900">328 <?php echo tlh('ໃບບິນ'); ?></text>
                            <circle class="anim-live-pill" cx="290" cy="47" r="5.5" fill="#F59E0B" fill-opacity="0.38"/>
                            <circle cx="290" cy="47" r="2.6" fill="#FDE047"/>

                            <!-- 3. Left Main Panel: Detailed Multi-Bar & Rising Trend Line Chart -->
                            <rect x="16" y="66" width="186" height="88" rx="6" fill="#0B1324" stroke="#334155" stroke-width="1"/>
                            <text x="24" y="77" fill="#E2E8F0" font-size="6.8" font-weight="800"><?php echo tlh('ກາຟຍອດຂາຍ &amp; ແນວໂນ້ມກຳໄລ (7 ວັນ)'); ?></text>
                            <!-- Legend Dots -->
                            <circle cx="152" cy="74.5" r="2.5" fill="#38BDF8"/>
                            <text x="157" y="77" fill="#94A3B8" font-size="5.5" font-weight="700"><?php echo tlh('ຍອດຂາຍ'); ?></text>
                            <circle cx="182" cy="74.5" r="2.5" fill="#4ADE80"/>
                            <text x="187" y="77" fill="#94A3B8" font-size="5.5" font-weight="700"><?php echo tlh('ກຳໄລ'); ?></text>

                            <!-- Horizontal Chart Grid Lines -->
                            <line x1="24" y1="88" x2="194" y2="88" stroke="#1E293B" stroke-width="1" stroke-dasharray="2 2"/>
                            <line x1="24" y1="104" x2="194" y2="104" stroke="#1E293B" stroke-width="1" stroke-dasharray="2 2"/>
                            <line x1="24" y1="120" x2="194" y2="120" stroke="#1E293B" stroke-width="1" stroke-dasharray="2 2"/>
                            <line x1="24" y1="138" x2="194" y2="138" stroke="#334155" stroke-width="1"/>

                            <!-- Sweeping Analytics Scanner Beam Across Chart -->
                            <rect class="anim-chart-scanner" x="24" y="80" width="18" height="58" fill="url(#scanLaserGrad)"/>

                            <!-- 7 Grouped Dynamic Animated Bar Columns -->
                            <rect class="anim-bar b1" x="30" y="116" width="7" height="22" rx="1.5" fill="url(#barBlue)"/>
                            <rect class="anim-bar b2" x="38" y="122" width="6" height="16" rx="1.5" fill="url(#barGreen)"/>
                            <rect class="anim-bar b3" x="54" y="108" width="7" height="30" rx="1.5" fill="url(#barBlue)"/>
                            <rect class="anim-bar b4" x="62" y="115" width="6" height="23" rx="1.5" fill="url(#barGreen)"/>
                            <rect class="anim-bar b2" x="78" y="112" width="7" height="26" rx="1.5" fill="url(#barBlue)"/>
                            <rect class="anim-bar b1" x="86" y="118" width="6" height="20" rx="1.5" fill="url(#barGreen)"/>
                            <rect class="anim-bar b4" x="102" y="100" width="7" height="38" rx="1.5" fill="url(#barBlue)"/>
                            <rect class="anim-bar b3" x="110" y="108" width="6" height="30" rx="1.5" fill="url(#barGreen)"/>
                            <rect class="anim-bar b1" x="126" y="94" width="7" height="44" rx="1.5" fill="url(#barBlue)"/>
                            <rect class="anim-bar b2" x="134" y="102" width="6" height="36" rx="1.5" fill="url(#barGreen)"/>
                            <rect class="anim-bar b3" x="150" y="88" width="7" height="50" rx="1.5" fill="url(#barBlue)"/>
                            <rect class="anim-bar b4" x="158" y="96" width="6" height="42" rx="1.5" fill="url(#barGreen)"/>
                            <rect class="anim-bar b1" x="174" y="81" width="7" height="57" rx="1.5" fill="url(#barGold)"/>
                            <rect class="anim-bar b3" x="182" y="89" width="6" height="49" rx="1.5" fill="url(#barGreen)"/>

                            <!-- Glowing Animated Trend Line Over Bars -->
                            <polyline class="anim-trend-line" points="35,113 59,104 83,108 107,96 131,89 155,83 179,76" fill="none" stroke="#FDE047" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="35" cy="113" r="2.3" fill="#FDE047"/>
                            <circle cx="59" cy="104" r="2.3" fill="#FDE047"/>
                            <circle cx="83" cy="108" r="2.3" fill="#FDE047"/>
                            <circle cx="107" cy="96" r="2.3" fill="#FDE047"/>
                            <circle cx="131" cy="89" r="2.3" fill="#FDE047"/>
                            <circle cx="155" cy="83" r="2.3" fill="#FDE047"/>
                            <circle class="anim-peak-dot" cx="179" cy="76" r="3.6" fill="#FFFFFF" stroke="#F59E0B" stroke-width="1.6"/>

                            <!-- X-Axis Labels -->
                            <text x="36" y="147" fill="#64748B" font-size="5.5" font-weight="700" text-anchor="middle"><?php echo tlh('ຈັນ'); ?></text>
                            <text x="60" y="147" fill="#64748B" font-size="5.5" font-weight="700" text-anchor="middle"><?php echo tlh('ອັງຄານ'); ?></text>
                            <text x="84" y="147" fill="#64748B" font-size="5.5" font-weight="700" text-anchor="middle"><?php echo tlh('ພຸດ'); ?></text>
                            <text x="108" y="147" fill="#64748B" font-size="5.5" font-weight="700" text-anchor="middle"><?php echo tlh('ພະຫັດ'); ?></text>
                            <text x="132" y="147" fill="#64748B" font-size="5.5" font-weight="700" text-anchor="middle"><?php echo tlh('ສຸກ'); ?></text>
                            <text x="156" y="147" fill="#64748B" font-size="5.5" font-weight="700" text-anchor="middle"><?php echo tlh('ເສົາ'); ?></text>
                            <text x="180" y="147" fill="#FDE047" font-size="5.5" font-weight="800" text-anchor="middle"><?php echo tlh('ອາທິດ'); ?></text>

                            <!-- 4. Right Panel on Screen: Continuous Rotating Donut Chart & Dynamic Bars -->
                            <rect x="208" y="66" width="92" height="88" rx="6" fill="#0B1324" stroke="#334155" stroke-width="1"/>
                            <text x="254" y="77" fill="#E2E8F0" font-size="6.5" font-weight="800" text-anchor="middle"><?php echo tlh('ສັດສ່ວນສິນຄ້າຂາຍດີ'); ?></text>

                            <!-- Multi-Segment Continuous Spinning Donut Chart -->
                            <g class="anim-donut-ring">
                                <circle cx="254" cy="103" r="17" fill="none" stroke="#1E293B" stroke-width="7"/>
                                <circle cx="254" cy="103" r="17" fill="none" stroke="#38BDF8" stroke-width="7" stroke-dasharray="48 60" stroke-dashoffset="0"/>
                                <circle cx="254" cy="103" r="17" fill="none" stroke="#4ADE80" stroke-width="7" stroke-dasharray="32 76" stroke-dashoffset="-48"/>
                                <circle cx="254" cy="103" r="17" fill="none" stroke="#FACC15" stroke-width="7" stroke-dasharray="27 81" stroke-dashoffset="-80"/>
                            </g>
                            <text x="254" y="105.5" fill="#FFFFFF" font-size="6.8" font-weight="900" text-anchor="middle">100%</text>

                            <!-- Animated Progress Bars Below Donut -->
                            <rect x="216" y="129" width="76" height="4" rx="2" fill="#1E293B"/>
                            <rect class="anim-prog-bar p1" x="216" y="129" width="58" height="4" rx="2" fill="#38BDF8"/>
                            <rect x="216" y="137" width="76" height="4" rx="2" fill="#1E293B"/>
                            <rect class="anim-prog-bar p2" x="216" y="137" width="46" height="4" rx="2" fill="#4ADE80"/>
                            <rect x="216" y="145" width="76" height="4" rx="2" fill="#1E293B"/>
                            <rect class="anim-prog-bar p3" x="216" y="145" width="34" height="4" rx="2" fill="#FACC15"/>

                            <!-- Laptop Base Rim -->
                            <path d="M-10 168 L326 168 L316 177 L0 177 Z" fill="#94A3B8"/>
                            <rect x="132" y="168" width="52" height="4" rx="2" fill="#E2E8F0"/>
                        </g>

                        <!-- Cute Fluffy Cat Front Paws Holding & Tapping the Sides of the Computer -->
                        <g class="anim-hand-left">
                            <ellipse cx="74" cy="276" rx="15" ry="13" fill="#FFFFFF" stroke="#F59E0B" stroke-width="2"/>
                            <circle cx="69" cy="273" r="3" fill="#FDA4AF"/>
                            <circle cx="76" cy="270" r="3" fill="#FDA4AF"/>
                            <circle cx="75" cy="278" r="5" fill="#FDA4AF"/>
                        </g>
                        <g class="anim-hand-right">
                            <ellipse cx="386" cy="276" rx="15" ry="13" fill="#FFFFFF" stroke="#F59E0B" stroke-width="2"/>
                            <circle cx="391" cy="273" r="3" fill="#FDA4AF"/>
                            <circle cx="384" cy="270" r="3" fill="#FDA4AF"/>
                            <circle cx="385" cy="278" r="5" fill="#FDA4AF"/>
                        </g>
                    </svg>
                    <div class="cute-cartoon-floor-shadow"></div>
                </div>
            </div>
