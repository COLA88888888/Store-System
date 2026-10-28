<?php
/**
 * 3D Realistic Mini-Mart World Simulation (Three.js r128)
 * - ປ້າຍຊື່ຮ້ານ ແລະ ສາຂາ ເປັນ 3D ຄົມຊັດລະດັບ Ultra-HD (2048px, ບໍ່ມົວ, ບໍ່ບີບໂຕໜັງສື, ເຫັນສະຫຼະ-วรรณยุกต์ລາວແຈ້ງຊັດເຈນ 100%)
 * - ພື້ນຮ້ານຢູ່ນິ້ງ 100% ພ້ອມສະແດງໂລໂກ້ຮ້ານ (tbcompanyinfo.img_url) ຢູ່ເທິງພື້ນ; ບໍ່ມີໂລໂກ້ ຫຼື ໂຫຼດບໍ່ໄດ້ → ໂລໂກ້ລະບົບ WLaoDev
 * - ພະນັກງານເຄົ້າເຕີຈັບປືນຍິງບາໂຄ້ດ ຍິງເລເຊີສີແດງສະແກນສິນຄ້າເທິງສາຍພານທີລະຊິ້ນ + ຈໍຄອມ POS ອັບເດດຕົວຈິງ + ໃບບິນແລ່ນອອກມາ
 * - ລູກຄ້າ 18 ຄົນ ຍ່າງເລືອກຊື້ເຄື່ອງ (ນ້ຳສົ້ມ, ເຂົ້າໜົມ, ເຄື່ອງສຳອາງ) ເດ່ມືหยິບສິນຄ້າໃສ່ກະຕ່າ ແລ້ວຍ່າງໄປຊຳລະເງິນໜ້າເຄົ້າເຕີຄືຄົນຈິງ
 */

$__mmStoreId = function_exists('getPosStoreId') ? (int)getPosStoreId() : (int)($_SESSION['active_store_id'] ?? ($_SESSION['store_id'] ?? 1));
$__compRawName = !empty($company['com_name']) ? trim(str_replace('#', '', (string)$company['com_name'])) : '';
$__mmStoreName = $__compRawName !== '' ? $__compRawName : 'WlaoDev MiniMart';
$__mmBranchName = '';

try {
    $__extractNameAndBranch = function (string $text): array {
        $text = trim(str_replace('#', '', $text));
        if ($text !== '' && preg_match('/^(.*?)\s*(ສາຂາ.*)$/u', $text, $m)) {
            return [
                'name'   => trim($m[1], " \t\n\r\0\x0B-_:"),
                'branch' => trim($m[2]),
            ];
        }
        return ['name' => $text, 'branch' => ''];
    };

    $__parsedComp = $__extractNameAndBranch($__compRawName);
    $__bestStoreName = $__parsedComp['name'];
    $__bestBranchName = $__parsedComp['branch'];

    if (isset($pdo) && $pdo) {
        $__cStmt = $pdo->query("SELECT com_name_la FROM st_tbcompanyinfo ORDER BY branch_id ASC LIMIT 1");
        if ($__cStmt) {
            $__mainCompRaw = trim((string)$__cStmt->fetchColumn());
            $__parsedMain = $__extractNameAndBranch($__mainCompRaw);
            if (mb_strlen($__parsedMain['name'], 'UTF-8') > mb_strlen($__bestStoreName, 'UTF-8')) {
                $__bestStoreName = $__parsedMain['name'];
            }
            if ($__bestBranchName === '' && $__parsedMain['branch'] !== '') {
                $__bestBranchName = $__parsedMain['branch'];
            }
        }

        $__bStmt = $pdo->prepare("SELECT store_name, store_code, is_main FROM st_tbstore WHERE store_id = ? LIMIT 1");
        $__bStmt->execute([$__mmStoreId]);
        $__bRow = $__bStmt->fetch(PDO::FETCH_ASSOC);

        if ($__bRow) {
            $__rawStoreName = trim(str_replace('#', '', (string)($__bRow['store_name'] ?? '')));
            $__storeCode    = trim(str_replace('#', '', (string)($__bRow['store_code'] ?? '')));
            $__isMain       = !empty($__bRow['is_main']);

            $__parsedStore = $__extractNameAndBranch($__rawStoreName);
            if (mb_strlen($__parsedStore['name'], 'UTF-8') > mb_strlen($__bestStoreName, 'UTF-8')) {
                $__bestStoreName = $__parsedStore['name'];
            }
            if ($__parsedStore['branch'] !== '') {
                $__bestBranchName = $__parsedStore['branch'];
            } elseif ($__bestBranchName === '') {
                if ($__rawStoreName !== '' && $__rawStoreName !== $__bestStoreName && mb_strpos($__bestStoreName, $__rawStoreName) === false) {
                    $__bestBranchName = t('cd.branch') . ' ' . $__rawStoreName;
                } elseif ($__storeCode !== '') {
                    $__bestBranchName = t('cd.branch') . ' ' . $__storeCode;
                } elseif ($__isMain) {
                    $__bestBranchName = t('cd.main_branch');
                } else {
                    $__bestBranchName = t('cd.branch') . ' ' . $__mmStoreId;
                }
            }
        }
    }

    if ($__bestStoreName !== '') {
        $__mmStoreName = $__bestStoreName;
    }
    $__mmBranchName = $__bestBranchName;
} catch (Throwable $e) {}
?>
<script>
(function() {
  'use strict';

  var MM_STORE_NAME  = <?php echo json_encode($__mmStoreName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  var MM_BRANCH_NAME = <?php echo json_encode($__mmBranchName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  // ຂໍ້ຄວາມທີ່ແຕ້ມເທິງຈໍ/ໃບບິນໃນສາກ 3D ຕາມພາສາທີ່ເລືອກ (lang/*/customer_display.php)
  var CD_MM_T = <?php echo tjson(array_fill_keys(['cd.col_item', 'cd.col_qty', 'cd.col_total', 'cd.receipt', 'cd.thank_you', 'cd.scanning', 'cd.scan_ok',
                                                  'cd.demo_juice', 'cd.demo_chips', 'cd.demo_lotion', 'cd.demo_lipstick', 'cd.demo_milk'], null)); ?>;

  function initMinimart3D() {
    var container = document.getElementById('minimart3dContainer');
    if (!container || typeof THREE === 'undefined') return;
    if (container.dataset.initialized === 'true') return;

    var width  = container.clientWidth  || Math.max(520, Math.round((window.innerWidth  || 1920) * 0.43));
    var height = container.clientHeight || Math.max(460, Math.round((window.innerHeight || 1080) * 0.78));
    container.dataset.initialized = 'true';

    // Scene, Camera, Renderer (ພື້ນ ແລະ ຮ້ານຢູ່ນິ້ງ 100% — ຈັດມຸມກ້ອງໃຫ້ເຫັນປ້າຍຊື່ຮ້ານດ້ານເທິງ ແລະ ປ້າຍພື້ນ WlaoDev POS ເຕັມ 100%)
    var scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf1f5f9);

    var camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 100);
    camera.position.set(0, 17.5, 19.5);
    camera.lookAt(0, 1.45, 0.0);

    var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
    renderer.domElement.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;display:block;';
    // ຈຳກັດ PixelRatio ໃຫ້ເໝາະສົມກັບຈໍສຳພັດ POS 2 ໜ້າຈໍ ເພື່ອໃຫ້ລื่นໄຫຼ 60 FPS ບໍ່ໜ่วงກາດຈໍ
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.35));
    renderer.setSize(width, height, false);
    renderer.shadowMap.enabled = false;
    container.appendChild(renderer.domElement);

    var maxAnisotropy = (renderer.capabilities && renderer.capabilities.getMaxAnisotropy)
      ? renderer.capabilities.getMaxAnisotropy() : 4;

    function updateSize() {
      if (!container) return;
      var wrap = document.getElementById('minimart3dWrap') || container;
      var w = wrap.clientWidth || container.clientWidth;
      var h = wrap.clientHeight || container.clientHeight;
      if (w <= 0 || h <= 0) return;
      camera.aspect = w / h;
      // ປັບໄລຍະກ້ອງອັດຕະໂນມັດຕາມສັດສ່ວນໜ້າຈໍ ເພື່ອໃຫ້ເຫັນປ້າຍຊື່ຮ້ານດ້ານເທິງ ແລະ ໂລໂກ້ລະບົບຢູ່ພື້ນ ເຕັມ 100%
      var minAspect = 1.15;
      var distScale = (camera.aspect < minAspect) ? (minAspect / camera.aspect) : 1.0;
      camera.position.set(0, 17.5 * distScale, 19.5 * distScale);
      camera.lookAt(0, 1.45, 0.0);
      camera.updateProjectionMatrix();
      renderer.setSize(w, h, false);
    }
    updateSize();
    window.addEventListener('resize', updateSize);
    if (window.ResizeObserver) {
      var ro = new ResizeObserver(updateSize);
      ro.observe(container);
      var wrapEl = document.getElementById('minimart3dWrap');
      if (wrapEl && wrapEl !== container) ro.observe(wrapEl);
    }

    // --- LIGHTING ---
    var ambientLight = new THREE.AmbientLight(0xffffff, 0.85);
    scene.add(ambientLight);

    var dirLight = new THREE.DirectionalLight(0xfffbeb, 0.88);
    dirLight.position.set(10, 22, 14);
    dirLight.castShadow = true;
    dirLight.shadow.mapSize.width = 1024;
    dirLight.shadow.mapSize.height = 1024;
    dirLight.shadow.camera.near = 0.5;
    dirLight.shadow.camera.far = 45;
    var d = 12;
    dirLight.shadow.camera.left = -d;
    dirLight.shadow.camera.right = d;
    dirLight.shadow.camera.top = d;
    dirLight.shadow.camera.bottom = -d;
    dirLight.shadow.bias = -0.0005;
    scene.add(dirLight);

    var chillerLight = new THREE.PointLight(0x38bdf8, 1.2, 14);
    chillerLight.position.set(-1.5, 3.0, -6.5);
    scene.add(chillerLight);

    var counterLight = new THREE.PointLight(0xfef08a, 1.25, 11);
    counterLight.position.set(4.5, 3.8, 4.5);
    scene.add(counterLight);

    // --- MATERIALS & CANVAS TEXTURE GENERATORS ---
    var matWall = new THREE.MeshLambertMaterial({ color: 0xe2e8f0 });
    var matWhite = new THREE.MeshLambertMaterial({ color: 0xffffff });
    var matGlass = new THREE.MeshLambertMaterial({ color: 0xbae6fd, transparent: true, opacity: 0.52 });
    var matMetal = new THREE.MeshLambertMaterial({ color: 0x64748b });
    var matSilver = new THREE.MeshLambertMaterial({ color: 0xe2e8f0 });
    var matGold = new THREE.MeshLambertMaterial({ color: 0xfbbf24 });

    // 0A. ປ້າຍຊື່ຮ້ານ ແລະ ຊື່ສາຂາ 3D ລະດັບ Ultra-HD (2048x500px ເຫັນສະຫຼະ-วรรณยุกต์ລາວດ້ານເທິງຄົບຖ້ວນ ບໍ່ຕົກຂອບ 100%)
    var storeSignCanvas = document.createElement('canvas');
    storeSignCanvas.width = 2048;
    storeSignCanvas.height = 500;
    var storeSignTex = new THREE.CanvasTexture(storeSignCanvas);
    storeSignTex.minFilter = THREE.LinearFilter;
    storeSignTex.magFilter = THREE.LinearFilter;
    storeSignTex.anisotropy = maxAnisotropy;

    function renderStoreSignCanvas() {
      var ctx = storeSignCanvas.getContext('2d');
      var W = storeSignCanvas.width;
      var H = storeSignCanvas.height;

      // ພື້ນຫຼັງປ້າຍ 3D ສີຟ້າເຂັ້ມ Modern Royal Navy ສະອາດຕາ
      var bgGrad = ctx.createLinearGradient(0, 0, 0, H);
      bgGrad.addColorStop(0, '#0f172a');
      bgGrad.addColorStop(0.5, '#1e3a8a');
      bgGrad.addColorStop(1, '#0f172a');
      ctx.fillStyle = bgGrad;
      ctx.fillRect(0, 0, W, H);

      // ຂອບສີຄຳ 3D ອ້ອມປ້າຍ
      ctx.strokeStyle = '#fbbf24';
      ctx.lineWidth = 14;
      ctx.strokeRect(18, 18, W - 36, H - 36);
      ctx.strokeStyle = 'rgba(56, 189, 248, 0.50)';
      ctx.lineWidth = 4;
      ctx.strokeRect(36, 36, W - 72, H - 72);

      // ໝຸດຄຳ 3D ທັງ 4 ມຸມປ້າຍຊື່ຮ້ານ
      var sCorners = [[60, 60], [W - 60, 60], [60, H - 60], [W - 60, H - 60]];
      sCorners.forEach(function(pt) {
        ctx.fillStyle = '#78350f';
        ctx.beginPath(); ctx.arc(pt[0] + 3, pt[1] + 4, 11, 0, Math.PI * 2); ctx.fill();
        ctx.fillStyle = '#fde047';
        ctx.beginPath(); ctx.arc(pt[0], pt[1], 11, 0, Math.PI * 2); ctx.fill();
      });

      var mainText = String(MM_STORE_NAME || 'WlaoDev MiniMart').trim();
      var subText  = String(MM_BRANCH_NAME || '').trim();

      var mSplit = mainText.match(/^(.*?)\s*(ສາຂາ.*)$/);
      if (mSplit && mSplit[1].trim()) {
        mainText = mSplit[1].trim();
        if (!subText) subText = mSplit[2].trim();
      }
      var sSplit = subText.match(/^(.*?)\s*(ສາຂາ.*)$/);
      if (sSplit && sSplit[2].trim()) {
        if (sSplit[1].trim().length > mainText.length) {
          mainText = sSplit[1].trim();
        }
        subText = sSplit[2].trim();
      }

      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      // ປັບຂະໜາດຟອນຊື່ເຕັມຮ້ານອັດຕະໂນມັດໃຫ້ສົມສ່ວນເປັນແຖວດຽວ (ບໍ່ລົ້ນຂອບ ແລະ ເຫັນສະຫຼະ/วรรณยุกต์ລາວຄົບຖ້ວນ)
      var mainFontSize = 136;
      var fontStack = '"Nuanta", "Noto Sans Lao", "Phetsarath OT", "Souliyo", sans-serif';
      ctx.font = '900 ' + mainFontSize + 'px ' + fontStack;
      while (mainFontSize > 48 && ctx.measureText(mainText).width > W - 220) {
        mainFontSize -= 4;
        ctx.font = '900 ' + mainFontSize + 'px ' + fontStack;
      }

      // ວາງ mainY ຢູ່ 202px ເພື່ອໃຫ້ສົມສ່ວນກັບແຖວສາຂາທາງລຸ່ມ
      var mainY = subText ? 202 : 255;
      var maxExtrude = Math.max(5, Math.round(mainFontSize * 0.065));
      var strokeW = Math.max(3, Math.round(mainFontSize * 0.038));

      // 1) ເງົາພື້ນຫຼັງ 3D
      ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
      ctx.fillText(mainText, W / 2 + maxExtrude * 1.1, mainY + maxExtrude * 1.35);

      // 2) ຊັ້ນຄວາມໜາ 3D Extrusion ທາງລຸ່ມ-ຂວາ (ສົມສ່ວນຕາມຂະໜາດຟອນ)
      for (var dStep = maxExtrude; dStep >= 1; dStep--) {
        ctx.fillStyle = (dStep > maxExtrude * 0.5) ? '#78350f' : '#d97706';
        ctx.strokeStyle = '#451a03';
        ctx.lineWidth = strokeW;
        ctx.strokeText(mainText, W / 2 + dStep * 0.85, mainY + dStep * 1.15);
        ctx.fillText(mainText, W / 2 + dStep * 0.85, mainY + dStep * 1.15);
      }

      // 3) ໜ້າຕົວໜັງສື 3D ສີຄຳ-ຂາວ ສະຫວ່າງ ຄົມຊັດລະດັບ Ultra-HD
      var faceGrad = ctx.createLinearGradient(0, mainY - mainFontSize * 0.55, 0, mainY + mainFontSize * 0.45);
      faceGrad.addColorStop(0, '#ffffff');
      faceGrad.addColorStop(0.32, '#fef08a');
      faceGrad.addColorStop(0.72, '#facc15');
      faceGrad.addColorStop(1, '#f59e0b');

      ctx.lineWidth = Math.max(2.5, strokeW * 0.75);
      ctx.strokeStyle = '#ffffff';
      ctx.strokeText(mainText, W / 2, mainY);
      ctx.fillStyle = faceGrad;
      ctx.fillText(mainText, W / 2, mainY);

      // 4) ແຖວທີ 2: ຊື່ສາຂາຢູ່ລຸ່ມ (ບໍ່ມີກາດຫຸ້ມ, ແຖວດຽວສົມສ່ວນ)
      if (subText) {
        var subY = 376;
        var subFontSize = Math.min(76, Math.max(46, Math.round(mainFontSize * 0.68)));
        ctx.font = '800 ' + subFontSize + 'px ' + fontStack;
        while (subFontSize > 40 && ctx.measureText(subText).width > W - 240) {
          subFontSize -= 3;
          ctx.font = '800 ' + subFontSize + 'px ' + fontStack;
        }

        // ເງົາ 3D ທາງລຸ່ມຂອງຊື່ສາຂາ (ບໍ່ມີກາດ/ກອບ Pill ຫຸ້ມ)
        ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
        ctx.fillText(subText, W / 2 + 5, subY + 7);

        ctx.lineWidth = Math.max(2.5, Math.round(subFontSize * 0.05));
        ctx.strokeStyle = '#082f49';
        ctx.strokeText(subText, W / 2 + 2, subY + 3);

        var subGrad = ctx.createLinearGradient(0, subY - subFontSize * 0.5, 0, subY + subFontSize * 0.5);
        subGrad.addColorStop(0, '#ffffff');
        subGrad.addColorStop(0.5, '#e0f2fe');
        subGrad.addColorStop(1, '#7dd3fc');
        ctx.fillStyle = subGrad;
        ctx.fillText(subText, W / 2, subY);
      }

      storeSignTex.needsUpdate = true;
    }

    function create3DStoreSignGroup() {
      renderStoreSignCanvas();
      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(renderStoreSignCanvas).catch(function(){});
      }
      setTimeout(renderStoreSignCanvas, 350);

      var signGroup = new THREE.Group();

      // ໂຄງເສົາເຫຼັກ 3D ຄ້ຳປ້າຍ
      var pillarGeo = new THREE.CylinderGeometry(0.14, 0.14, 1.40, 12);
      var pillarMat = new THREE.MeshLambertMaterial({ color: 0x334155 });
      var pillarL = new THREE.Mesh(pillarGeo, pillarMat);
      pillarL.position.set(-6.8, 3.15, -8.15);
      signGroup.add(pillarL);

      var pillarR = new THREE.Mesh(pillarGeo, pillarMat);
      pillarR.position.set(6.8, 3.15, -8.15);
      signGroup.add(pillarR);

      // ວາງກ່ອງປ້າຍ 3D ຢູ່ລະດັບ y = 3.92 (ຕ່ຳລົງມາໃຫ້ເຫັນຂອບເທິງຄົບ 100% ບໍ່ຖືກຕັດຂອບ!) ແລະ ກວ້າງເຕັມຮ້ານ 17.0 หน่วย
      var signHolder = new THREE.Group();
      signHolder.position.set(0, 3.92, -7.85);
      signHolder.rotation.x = -0.48;

      var outerBox = new THREE.Mesh(
        new THREE.BoxGeometry(17.1, 3.56, 0.36),
        new THREE.MeshLambertMaterial({ color: 0xf59e0b })
      );
      signHolder.add(outerBox);

      var midBevel = new THREE.Mesh(
        new THREE.BoxGeometry(16.86, 3.36, 0.42),
        new THREE.MeshLambertMaterial({ color: 0x0f172a })
      );
      signHolder.add(midBevel);

      var signFace = new THREE.Mesh(
        new THREE.PlaneGeometry(16.76, 3.28),
        new THREE.MeshBasicMaterial({ map: storeSignTex })
      );
      signFace.position.z = 0.22;
      signHolder.add(signFace);

      var hoodMesh = new THREE.Mesh(
        new THREE.BoxGeometry(17.2, 0.14, 0.55),
        new THREE.MeshLambertMaterial({ color: 0xfbbf24 })
      );
      hoodMesh.position.set(0, 1.78, 0.12);
      signHolder.add(hoodMesh);

      signGroup.add(signHolder);
      return signGroup;
    }

    // 0B. ໂລໂກ້ຮ້ານ ຢູ່ເທິງພື້ນຮ້ານດ້ານໜ້າ (Shop Logo on Floor) — ຮ້ານທີ່ບໍ່ໄດ້ຕັ້ງໂລໂກ້ ຫຼື ໂຫຼດຮູບບໍ່ໄດ້ ໃຊ້ໂລໂກ້ລະບົບ WLaoDev ແທນ
    var FLOOR_SHOP_LOGO = <?php echo json_encode((string)($company['com_logo'] ?? '')); ?>;
    var floorLogoCanvas = document.createElement('canvas');
    floorLogoCanvas.width = 1600;
    floorLogoCanvas.height = 1000;
    var floorLogoTex = new THREE.CanvasTexture(floorLogoCanvas);
    floorLogoTex.minFilter = THREE.LinearFilter;
    floorLogoTex.magFilter = THREE.LinearFilter;
    floorLogoTex.anisotropy = maxAnisotropy;

    var sysLogoImg = new Image();
    var sysLogoLoaded = false;
    var shopLogoImg = null;
    var shopLogoLoaded = false;
    var shopLogoWhiteBg = null;
    // ຂະໜາດແຜ່ນໂລໂກ້ເທິງພື້ນ (ໜ່ວຍ 3D)
    var FLOOR_LOGO_W = 10.4, FLOOR_LOGO_H = 5.45;

    // ພື້ນຫຼັງຂອງຮູບໂລໂກ້ (ສີໃດກໍໄດ້) → ສີຂາວ, ໃຫ້ເຫັນແຕ່ໂລໂກ້ເທິງພື້ນຮ້ານ.
    // ເອົາສີທີ່ພົບຫຼາຍສຸດຢູ່ຂອບຮູບເປັນສີພື້ນຫຼັງ ແລ້ວຖົມ (flood fill) ຈາກຂອບເຂົ້າໄປ — ປ່ຽນແຕ່ສ່ວນທີ່ຕິດກັບຂອບ,
    // ສີດຽວກັນທີ່ຢູ່ພາຍໃນໂລໂກ້ ບໍ່ຖືກປ່ຽນ. ສ່ວນໂປ່ງໃສ (PNG) ກໍກາຍເປັນສີຂາວ. ຄືນ canvas ຫຼື null ຖ້າອ່ານຮູບບໍ່ໄດ້.
    function logoOnWhite(img) {
      try {
        var w = img.naturalWidth, h = img.naturalHeight;
        var c = document.createElement('canvas');
        c.width = w; c.height = h;
        var cx = c.getContext('2d');
        cx.fillStyle = '#ffffff';
        cx.fillRect(0, 0, w, h);
        cx.drawImage(img, 0, 0);
        var d = cx.getImageData(0, 0, w, h), p = d.data;

        // ສີພື້ນຫຼັງ = ສີທີ່ພົບຫຼາຍສຸດຕາມຂອບ (ປັດເປັນກຸ່ມ 16 ລະດັບ ເພື່ອບໍ່ໃຫ້ noise ຂອງ JPG ແຍກກຸ່ມ)
        var counts = {}, best = null, bestN = 0;
        function vote(i) {
          var key = (p[i] >> 4) + ',' + (p[i + 1] >> 4) + ',' + (p[i + 2] >> 4);
          var e = counts[key] || (counts[key] = { n: 0, r: 0, g: 0, b: 0 });
          e.n++; e.r += p[i]; e.g += p[i + 1]; e.b += p[i + 2];
          if (e.n > bestN) { bestN = e.n; best = e; }
        }
        for (var x = 0; x < w; x++) { vote(x * 4); vote(((h - 1) * w + x) * 4); }
        for (var y = 0; y < h; y++) { vote(y * w * 4); vote((y * w + w - 1) * 4); }
        var br = best.r / best.n, bg = best.g / best.n, bb = best.b / best.n;

        var TOL = 46, SOFT = 30;   // ໃກ້ສີພື້ນຫຼັງ ≤ TOL → ຂາວ; TOL..TOL+SOFT → ປະສົມໄປຫາຂາວ (ຂອບໂລໂກ້ບໍ່ແຂງ)
        function dist(i) {
          var dr = p[i] - br, dg = p[i + 1] - bg, db = p[i + 2] - bb;
          var dv = Math.sqrt(dr * dr + dg * dg + db * db);
          // ເງົາ ແລະ ລາຍເນື້ອເຈ້ຍທີ່ຕິດກັບພື້ນຫຼັງ (ສີເທົາ/ນ້ຳຕານອ່ອນ ບໍ່ສົດ) ນັບເປັນພື້ນຫຼັງ;
          // ສີເຂັ້ມ ຫຼື ສີສົດຂອງໂລໂກ້ ຍັງເປັນຂອບທີ່ຢຸດການຖົມ
          var mx = Math.max(p[i], p[i + 1], p[i + 2]), mn = Math.min(p[i], p[i + 1], p[i + 2]);
          var lum = 0.299 * p[i] + 0.587 * p[i + 1] + 0.114 * p[i + 2];
          return (dv <= 220 && mx - mn <= 40 && lum >= 60) ? 0 : dv;
        }
        var seen = new Uint8Array(w * h), stack = [];
        function push(px) { if (!seen[px]) { seen[px] = 1; stack.push(px); } }
        for (x = 0; x < w; x++) { push(x); push((h - 1) * w + x); }
        for (y = 0; y < h; y++) { push(y * w); push(y * w + w - 1); }
        while (stack.length) {
          var px = stack.pop(), i = px * 4, dv = dist(i);
          if (dv > TOL + SOFT) continue;
          var a = dv <= TOL ? 1 : 1 - (dv - TOL) / SOFT;
          p[i] += (255 - p[i]) * a; p[i + 1] += (255 - p[i + 1]) * a; p[i + 2] += (255 - p[i + 2]) * a;
          if (dv > TOL) continue;   // ຂອບປະສົມ: ບໍ່ຖົມຕໍ່ເຂົ້າໄປໃນໂລໂກ້
          var px0 = px % w, py0 = (px / w) | 0;
          if (px0 > 0) push(px - 1);
          if (px0 < w - 1) push(px + 1);
          if (py0 > 0) push(px - w);
          if (py0 < h - 1) push(px + w);
        }
        cx.putImageData(d, 0, 0);
        return c;
      } catch (e) { return null; }
    }

    function renderFloorLogoCanvas() {
      var ctx = floorLogoCanvas.getContext('2d');
      var W = floorLogoCanvas.width;
      var H = floorLogoCanvas.height;
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, W, H);

      if (shopLogoImg) {
        // ໂລໂກ້ຮ້ານ: ວາງກາງພື້ນ ຮັກສາສັດສ່ວນຮູບ. ແຜ່ນພື້ນ (7.80 x 4.10) ກວ້າງກວ່າ canvas (1600 x 1000)
        // ຈຶ່ງບີບຄວາມກວ້າງລົງຕາມອັດຕານັ້ນ ເພື່ອບໍ່ໃຫ້ໂລໂກ້ຖືກຍືດອອກຂ້າງເມື່ອແປະເທິງພື້ນ
        if (shopLogoLoaded && shopLogoImg.naturalWidth > 0) {
          var src = shopLogoWhiteBg || shopLogoImg;
          var stretch = (FLOOR_LOGO_W / FLOOR_LOGO_H) / (W / H);
          var maxW = W - 40, maxH = H - 40;
          var lw = src.width / stretch, lh = src.height;
          var k = Math.min(maxW / lw, maxH / lh);
          lw *= k; lh *= k;
          ctx.drawImage(src, (W - lw) / 2, (H - lh) / 2, lw, lh);
        }
      } else if (sysLogoLoaded && sysLogoImg.naturalWidth > 0) {
        // ຕັດຂອບຂາວສ່ວນເກີນຂອງຮູບໂລໂກ້ລະບົບອອກ ເພື່ອໃຫ້ໂລໂກ້ WD WLaoDev ໃຫຍ່ຄົມຊັດເຕັມພື້ນທີ່ພື້ນຮ້ານ
        var iw = sysLogoImg.naturalWidth;
        var ih = sysLogoImg.naturalHeight;
        var sx = Math.round(iw * 0.08);
        var sy = Math.round(ih * 0.18);
        var sw = Math.round(iw * 0.84);
        var sh = Math.round(ih * 0.60);
        ctx.drawImage(sysLogoImg, sx, sy, sw, sh, 60, 40, W - 120, H - 80);
      } else {
        // ແຕ້ມໂລໂກ້ລະບົບ WD WLaoDev ແບບ Vector ທັນທີທີ່ 0ms (ກ່ອນຮູບໂຫຼດສຳເລັດ) ເພື່ອບໍ່ໃຫ້ຫວ່າງເປົ່າ
        ctx.save();
        // 1. ແຕ້ມສັນຍາລັກ W (ສີຂຽວໄລ່เฉด)
        var wGrad = ctx.createLinearGradient(320, 120, 780, 560);
        wGrad.addColorStop(0, '#76d300');
        wGrad.addColorStop(1, '#10b024');
        ctx.fillStyle = wGrad;
        ctx.beginPath();
        ctx.moveTo(310, 120);
        ctx.lineTo(445, 120);
        ctx.lineTo(565, 395);
        ctx.lineTo(655, 205);
        ctx.lineTo(755, 415);
        ctx.lineTo(900, 120);
        ctx.lineTo(1025, 120);
        ctx.lineTo(810, 555);
        ctx.lineTo(705, 555);
        ctx.lineTo(655, 445);
        ctx.lineTo(605, 555);
        ctx.lineTo(495, 555);
        ctx.closePath();
        ctx.fill();

        // 2. ແຕ້ມຂີດສີຟ້າດ້ານເທິງກາງ
        var dotGrad = ctx.createLinearGradient(685, 120, 865, 250);
        dotGrad.addColorStop(0, '#008cff');
        dotGrad.addColorStop(1, '#0062e6');
        ctx.fillStyle = dotGrad;
        ctx.beginPath();
        ctx.moveTo(748, 120);
        ctx.lineTo(868, 120);
        ctx.lineTo(800, 252);
        ctx.lineTo(682, 252);
        ctx.closePath();
        ctx.fill();

        // 3. ແຕ້ມຕົວ D (ສີຟ້າໄລ່เฉด)
        var dGrad = ctx.createLinearGradient(830, 120, 1260, 555);
        dGrad.addColorStop(0, '#008cff');
        dGrad.addColorStop(1, '#0062e6');
        ctx.fillStyle = dGrad;
        ctx.beginPath();
        ctx.moveTo(900, 120);
        ctx.lineTo(1060, 120);
        ctx.bezierCurveTo(1205, 120, 1285, 210, 1285, 338);
        ctx.bezierCurveTo(1285, 466, 1205, 555, 1060, 555);
        ctx.lineTo(845, 555);
        ctx.lineTo(900, 445);
        ctx.lineTo(1045, 445);
        ctx.bezierCurveTo(1125, 445, 1168, 402, 1168, 338);
        ctx.bezierCurveTo(1168, 274, 1125, 225, 1045, 225);
        ctx.lineTo(955, 225);
        ctx.closePath();
        ctx.fill();

        // 4. ຕົວໜັງສື WLaoDev ດ້ານລຸ່ມ
        ctx.font = '800 185px "Segoe UI", Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#33373b';
        ctx.fillText('WLaoDev', W / 2, 760);
        ctx.restore();
      }

      floorLogoTex.needsUpdate = true;
    }

    sysLogoImg.onload = function() {
      sysLogoLoaded = true;
      renderFloorLogoCanvas();
      renderer.render(scene, camera);
    };
    function loadSysFloorLogo() {
      sysLogoImg.src = '../../assets/img/logosystem/wlaodev_system_logo.jpg';
    }

    if (FLOOR_SHOP_LOGO) {
      shopLogoImg = new Image();
      shopLogoImg.onload = function() {
        shopLogoLoaded = true;
        shopLogoWhiteBg = logoOnWhite(shopLogoImg);
        renderFloorLogoCanvas();
        renderer.render(scene, camera);
      };
      shopLogoImg.onerror = function() {
        shopLogoImg = null;
        renderFloorLogoCanvas();
        loadSysFloorLogo();
      };
      shopLogoImg.src = FLOOR_SHOP_LOGO;
    } else {
      loadSysFloorLogo();
    }

    function create3DFloorBrandGroup() {
      renderFloorLogoCanvas();

      var fg = new THREE.Group();
      var topPlane = new THREE.Mesh(
        new THREE.PlaneGeometry(shopLogoImg ? FLOOR_LOGO_W : 7.80, shopLogoImg ? FLOOR_LOGO_H : 4.10),
        new THREE.MeshBasicMaterial({ map: floorLogoTex })
      );
      topPlane.rotation.x = -Math.PI / 2;
      topPlane.position.set(0, 0.025, 0);
      fg.add(topPlane);

      // ວາງໂລໂກ້ລະບົບຢູ່ເຄິ່ງກາງພື້ນດ້ານໜ້າ ໃຫ້ເຫັນເຕັມ 100% ບໍ່ຖືກຕັດຂອບ
      fg.position.set(0, 0, 5.05);
      return fg;
    }

    // 1. Dynamic Canvas Texture ສຳລັບຈໍຄອມ POS (ອັບເດດທຸກຄັ້ງທີ່ພະນັກງານຍິງບາໂຄ້ດສິນຄ້າ!)
    var posCanvas = document.createElement('canvas');
    posCanvas.width = 512;
    posCanvas.height = 320;
    var posCtx = posCanvas.getContext('2d');
    var posTex = new THREE.CanvasTexture(posCanvas);

    var POS_ITEMS = [
      { name: CD_MM_T['cd.demo_juice'] + ' 100%', price: '15,000', color: '#f97316' },
      { name: CD_MM_T['cd.demo_chips'], price: '10,000', color: '#eab308' },
      { name: CD_MM_T['cd.demo_lotion'], price: '45,000', color: '#ec4899' },
      { name: CD_MM_T['cd.demo_lipstick'], price: '35,000', color: '#e11d48' },
      { name: CD_MM_T['cd.demo_milk'], price: '20,000', color: '#3b82f6' }
    ];

    function drawPosScreen(activeScanIndex, isBeepFlash) {
      var ctx = posCtx;
      ctx.fillStyle = '#eef3fb';
      ctx.fillRect(0, 0, 512, 320);

      // Top Header Bar (WlaoDev POS Blue + ຊື່ຮ້ານ)
      ctx.fillStyle = '#244886';
      ctx.fillRect(0, 0, 512, 42);
      ctx.fillStyle = '#ffd45a';
      ctx.font = 'bold 20px sans-serif';
      ctx.fillText('WlaoDev POS', 14, 27);

      ctx.fillStyle = isBeepFlash ? '#22c55e' : '#3b82f6';
      ctx.fillRect(162, 8, 178, 26);
      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 13px sans-serif';
      ctx.fillText(isBeepFlash ? '✓ ' + CD_MM_T['cd.scan_ok'] : 'lll ' + CD_MM_T['cd.scanning'], 172, 26);

      ctx.fillStyle = '#e0f2fe';
      ctx.font = 'bold 13px sans-serif';
      ctx.fillText((MM_BRANCH_NAME || MM_STORE_NAME).substring(0, 18), 352, 26);

      // Left Product Grid Panel
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(10, 50, 200, 206);
      ctx.strokeStyle = '#cbd5e1';
      ctx.lineWidth = 2;
      ctx.strokeRect(10, 50, 200, 206);

      var cardColors = ['#f97316', '#eab308', '#ec4899', '#e11d48', '#3b82f6', '#10b981', '#a855f7', '#06b6d4', '#f59e0b'];
      var idx = 0;
      for (var r = 0; r < 3; r++) {
        for (var col = 0; col < 3; col++) {
          var cx = 18 + col * 62;
          var cy = 58 + r * 64;
          var isSelectedCard = (idx === activeScanIndex);
          ctx.fillStyle = isSelectedCard ? '#dcfce7' : '#f8fafc';
          ctx.fillRect(cx, cy, 54, 56);
          ctx.strokeStyle = isSelectedCard ? '#16a34a' : '#e2e8f0';
          ctx.lineWidth = isSelectedCard ? 3 : 1.5;
          ctx.strokeRect(cx, cy, 54, 56);
          ctx.fillStyle = cardColors[idx % cardColors.length];
          ctx.beginPath();
          ctx.arc(cx + 27, cy + 22, 13, 0, Math.PI * 2);
          ctx.fill();
          ctx.fillStyle = '#1e293b';
          ctx.fillRect(cx + 8, cy + 42, 38, 6);
          idx++;
        }
      }

      // Right Order Table Panel
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(218, 50, 284, 206);
      ctx.strokeStyle = '#cbd5e1';
      ctx.lineWidth = 2;
      ctx.strokeRect(218, 50, 284, 206);

      // Table Header
      ctx.fillStyle = '#dbeafe';
      ctx.fillRect(220, 52, 280, 26);
      ctx.fillStyle = '#1e3a8a';
      ctx.font = 'bold 13px sans-serif';
      ctx.fillText(CD_MM_T['cd.col_item'], 228, 70);
      ctx.fillText(CD_MM_T['cd.col_qty'], 385, 70);
      ctx.fillText(CD_MM_T['cd.col_total'], 448, 70);

      var visibleCount = Math.min(5, (activeScanIndex % 5) + 1);
      var runningTotal = 0;
      var pricesNum = [15000, 10000, 45000, 35000, 20000];
      for (var row = 0; row < visibleCount; row++) {
        var ry = 82 + row * 33;
        var isJustScanned = (row === visibleCount - 1);
        ctx.fillStyle = (isJustScanned && isBeepFlash) ? '#bbf7d0' : (isJustScanned ? '#e0f2fe' : '#ffffff');
        ctx.fillRect(220, ry, 280, 30);

        ctx.fillStyle = POS_ITEMS[row].color;
        ctx.fillRect(226, ry + 5, 20, 20);

        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 12px sans-serif';
        ctx.fillText(POS_ITEMS[row].name, 252, ry + 20);

        ctx.fillStyle = '#2563eb';
        ctx.fillRect(392, ry + 7, 26, 16);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 11px sans-serif';
        ctx.fillText('x1', 399, ry + 19);

        ctx.fillStyle = '#047857';
        ctx.font = 'bold 13px sans-serif';
        ctx.fillText(POS_ITEMS[row].price, 440, ry + 20);
        runningTotal += pricesNum[row];
      }

      // Bottom Green Grand Total Banner
      ctx.fillStyle = '#047857';
      ctx.fillRect(10, 262, 492, 48);
      for (var sb = 0; sb < 4; sb++) {
        ctx.fillStyle = 'rgba(255,255,255,0.20)';
        ctx.fillRect(18 + sb * 82, 269, 74, 34);
      }
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(354, 268, 140, 36);
      ctx.fillStyle = '#065f46';
      ctx.font = 'bold 20px sans-serif';
      ctx.fillText(runningTotal.toLocaleString() + ' <?php echo kipSymbol(); ?>', 368, 293);

      posTex.needsUpdate = true;
    }
    drawPosScreen(2, false);

    // 2. Canvas Texture ສຳລັບໃບບິນທີ່ພິມອອກມາຈາກເຄື່ອງພິມ (ສະແດງຊື່ຮ້ານ ແລະ ສາຂາຕົວຈິງ)
    function createReceiptTexture() {
      var c = document.createElement('canvas');
      c.width = 256;
      c.height = 512;
      var ctx = c.getContext('2d');

      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, 256, 512);

      ctx.fillStyle = '#16a34a';
      ctx.fillRect(0, 0, 256, 10);

      ctx.textAlign = 'center';
      ctx.fillStyle = '#0f172a';
      ctx.font = 'bold 20px sans-serif';
      ctx.fillText((MM_STORE_NAME || 'WlaoDev POS').substring(0, 20), 128, 42);
      ctx.font = 'bold 16px sans-serif';
      ctx.fillStyle = '#244886';
      ctx.fillText(MM_BRANCH_NAME || CD_MM_T['cd.receipt'], 128, 70);
      ctx.textAlign = 'left';

      ctx.fillStyle = '#64748b';
      for (var dx = 16; dx < 240; dx += 14) {
        ctx.fillRect(dx, 90, 8, 3);
      }

      var rItems = [
        ['1. ' + CD_MM_T['cd.demo_juice'] + ' x1', '15,000'],
        ['2. ' + CD_MM_T['cd.demo_chips'] + ' x1', '10,000'],
        ['3. ' + CD_MM_T['cd.demo_lotion'] + ' x1', '45,000'],
        ['4. ' + CD_MM_T['cd.demo_lipstick'] + ' x1', '35,000'],
        ['5. ' + CD_MM_T['cd.demo_milk'] + ' x1', '20,000']
      ];
      ctx.font = 'bold 16px sans-serif';
      for (var l = 0; l < rItems.length; l++) {
        var ly = 124 + l * 36;
        ctx.fillStyle = '#1e293b';
        ctx.fillText(rItems[l][0], 16, ly);
        ctx.fillStyle = '#0f172a';
        ctx.fillText(rItems[l][1], 180, ly);
      }

      ctx.fillStyle = '#64748b';
      for (var dx2 = 16; dx2 < 240; dx2 += 14) {
        ctx.fillRect(dx2, 310, 8, 3);
      }

      ctx.fillStyle = '#dcfce7';
      ctx.fillRect(16, 326, 224, 52);
      ctx.fillStyle = '#15803d';
      ctx.font = 'bold 22px sans-serif';
      ctx.fillText(CD_MM_T['cd.col_total'] + ': 125,000 <?php echo kipSymbol(); ?>', 40, 360);

      ctx.fillStyle = '#0f172a';
      for (var bx = 28; bx < 228; bx += 7) {
        var bw = (bx % 3 === 0) ? 5 : 2.5;
        ctx.fillRect(bx, 405, bw, 65);
      }
      ctx.font = '14px sans-serif';
      ctx.fillText(CD_MM_T['cd.thank_you'], 72, 492);

      var tex = new THREE.CanvasTexture(c);
      tex.needsUpdate = true;
      return tex;
    }

    // 3. Canvas Texture ສຳລັບສະຫຼາກສິນຄ້າ + ບາໂຄ້ດເທິງສິນຄ້າ
    function createProductLabelTexture(bgHex, accentHex, symbolType) {
      var c = document.createElement('canvas');
      c.width = 128;
      c.height = 128;
      var ctx = c.getContext('2d');

      ctx.fillStyle = bgHex;
      ctx.fillRect(0, 0, 128, 128);

      ctx.fillStyle = accentHex;
      ctx.fillRect(0, 8, 128, 16);
      ctx.fillRect(0, 104, 128, 16);

      ctx.fillStyle = '#ffffff';
      ctx.beginPath();
      ctx.arc(48, 60, 26, 0, Math.PI * 2);
      ctx.fill();

      if (symbolType === 'orange') {
        ctx.fillStyle = '#f97316';
        ctx.beginPath();
        ctx.arc(48, 62, 17, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#16a34a';
        ctx.beginPath();
        ctx.ellipse(52, 42, 7, 4, -0.4, 0, Math.PI * 2);
        ctx.fill();
      } else if (symbolType === 'snack') {
        ctx.fillStyle = '#eab308';
        ctx.beginPath();
        ctx.ellipse(44, 58, 13, 10, -0.3, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#ca8a04';
        ctx.beginPath();
        ctx.ellipse(54, 64, 11, 9, 0.4, 0, Math.PI * 2);
        ctx.fill();
      } else if (symbolType === 'cosmetic') {
        ctx.fillStyle = '#ec4899';
        ctx.beginPath();
        ctx.arc(48, 60, 15, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#fbcfe8';
        ctx.beginPath();
        ctx.arc(48, 60, 6, 0, Math.PI * 2);
        ctx.fill();
      } else {
        ctx.fillStyle = '#0284c7';
        ctx.beginPath();
        ctx.arc(48, 62, 15, 0, Math.PI * 2);
        ctx.fill();
      }

      // White Barcode Patch on every product
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(82, 42, 38, 42);
      ctx.fillStyle = '#0f172a';
      for (var b = 85; b < 117; b += 4) {
        ctx.fillRect(b, 46, (b % 3 === 0) ? 2.5 : 1.2, 34);
      }

      var tex = new THREE.CanvasTexture(c);
      tex.needsUpdate = true;
      return tex;
    }

    var texOrangeJuice = createProductLabelTexture('#fb923c', '#16a34a', 'orange');
    var texWater = createProductLabelTexture('#38bdf8', '#0369a1', 'milk');
    var texCola = createProductLabelTexture('#dc2626', '#ffffff', 'other');
    var texGreenSoda = createProductLabelTexture('#10b981', '#fde047', 'other');
    var texChipYellow = createProductLabelTexture('#facc15', '#dc2626', 'snack');
    var texChipRed = createProductLabelTexture('#ef4444', '#facc15', 'snack');
    var texChipOrange = createProductLabelTexture('#f97316', '#ffffff', 'snack');
    var texMilk = createProductLabelTexture('#ffffff', '#2563eb', 'milk');
    var texNoodle = createProductLabelTexture('#ef4444', '#fde047', 'snack');
    var texCosmeticPink = createProductLabelTexture('#f472b6', '#fbbf24', 'cosmetic');
    var texCosmeticPurple = createProductLabelTexture('#c084fc', '#fbbf24', 'cosmetic');

    var matOrangeJuice = new THREE.MeshLambertMaterial({ map: texOrangeJuice });
    var matWater = new THREE.MeshLambertMaterial({ map: texWater });
    var matCola = new THREE.MeshLambertMaterial({ map: texCola });
    var matGreenSoda = new THREE.MeshLambertMaterial({ map: texGreenSoda });
    var matChipYellow = new THREE.MeshLambertMaterial({ map: texChipYellow });
    var matChipRed = new THREE.MeshLambertMaterial({ map: texChipRed });
    var matChipOrange = new THREE.MeshLambertMaterial({ map: texChipOrange });
    var matMilk = new THREE.MeshLambertMaterial({ map: texMilk });
    var matNoodle = new THREE.MeshLambertMaterial({ map: texNoodle });
    var matCosmeticPink = new THREE.MeshLambertMaterial({ map: texCosmeticPink });
    var matCosmeticPurple = new THREE.MeshLambertMaterial({ map: texCosmeticPurple });

    var matOrangeCap = new THREE.MeshLambertMaterial({ color: 0xea580c });
    var matBlueCap = new THREE.MeshLambertMaterial({ color: 0x0284c7 });
    var matOrangeLiquid = new THREE.MeshLambertMaterial({ color: 0xf97316 });
    var matWaterLiquid = new THREE.MeshLambertMaterial({ color: 0xbae6fd });
    var matBread = new THREE.MeshLambertMaterial({ color: 0xd97706 });
    var matBreadCrust = new THREE.MeshLambertMaterial({ color: 0x92400e });
    var matLipstickRed = new THREE.MeshLambertMaterial({ color: 0xe11d48 });
    var matLipstickPink = new THREE.MeshLambertMaterial({ color: 0xec4899 });
    var matBlackCase = new THREE.MeshLambertMaterial({ color: 0x1e293b });

    // --- REALISTIC MINI-MART 3D PRODUCT FACTORIES ---
    function makeOrangeJuiceBottle() {
      var g = new THREE.Group();
      var body = new THREE.Mesh(new THREE.CylinderGeometry(0.105, 0.105, 0.30, 12), matOrangeJuice);
      body.position.y = 0.15;
      g.add(body);
      var shoulder = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.105, 0.09, 12), matOrangeLiquid);
      shoulder.position.y = 0.345;
      g.add(shoulder);
      var cap = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.055, 0.05, 10), matOrangeCap);
      cap.position.y = 0.41;
      g.add(cap);
      return g;
    }

    function makeWaterBottle() {
      var g = new THREE.Group();
      var body = new THREE.Mesh(new THREE.CylinderGeometry(0.095, 0.095, 0.30, 10), matWater);
      body.position.y = 0.15;
      g.add(body);
      var shoulder = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.095, 0.08, 10), matWaterLiquid);
      shoulder.position.y = 0.34;
      g.add(shoulder);
      var cap = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 0.045, 8), matBlueCap);
      cap.position.y = 0.40;
      g.add(cap);
      return g;
    }

    function makeSodaCan(isGreen) {
      var g = new THREE.Group();
      var canBody = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.09, 0.25, 10), isGreen ? matGreenSoda : matCola);
      canBody.position.y = 0.125;
      g.add(canBody);
      var rimTop = new THREE.Mesh(new THREE.CylinderGeometry(0.085, 0.09, 0.025, 10), matSilver);
      rimTop.position.y = 0.26;
      g.add(rimTop);
      return g;
    }

    function makeSnackBag(variant) {
      var g = new THREE.Group();
      var mat = (variant === 0) ? matChipYellow : (variant === 1 ? matChipRed : matChipOrange);
      var bag = new THREE.Mesh(new THREE.BoxGeometry(0.30, 0.38, 0.15), mat);
      bag.position.y = 0.19;
      g.add(bag);
      var sealTop = new THREE.Mesh(new THREE.BoxGeometry(0.32, 0.04, 0.04), matSilver);
      sealTop.position.y = 0.39;
      g.add(sealTop);
      return g;
    }

    function makeCarton(isOrange) {
      var g = new THREE.Group();
      var box = new THREE.Mesh(new THREE.BoxGeometry(0.21, 0.31, 0.19), isOrange ? matOrangeJuice : matMilk);
      box.position.y = 0.155;
      g.add(box);
      var roof = new THREE.Mesh(new THREE.ConeGeometry(0.145, 0.11, 4), isOrange ? matOrangeCap : matBlueCap);
      roof.rotation.y = Math.PI / 4;
      roof.position.y = 0.365;
      g.add(roof);
      return g;
    }

    function makeCupNoodle() {
      var g = new THREE.Group();
      var cup = new THREE.Mesh(new THREE.CylinderGeometry(0.125, 0.09, 0.27, 10), matNoodle);
      cup.position.y = 0.135;
      g.add(cup);
      var lid = new THREE.Mesh(new THREE.CylinderGeometry(0.132, 0.132, 0.025, 10), matWhite);
      lid.position.y = 0.28;
      g.add(lid);
      return g;
    }

    function makeCosmeticLotion(isPurple) {
      var g = new THREE.Group();
      var body = new THREE.Mesh(new THREE.CylinderGeometry(0.095, 0.085, 0.28, 12), isPurple ? matCosmeticPurple : matCosmeticPink);
      body.position.y = 0.14;
      g.add(body);
      var collar = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 0.05, 8), matGold);
      collar.position.y = 0.305;
      g.add(collar);
      var nozzle = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.03, 0.035), matWhite);
      nozzle.position.set(0.02, 0.34, 0);
      g.add(nozzle);
      return g;
    }

    function makeLipstick(isPink) {
      var g = new THREE.Group();
      var base = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.055, 0.16, 10), matBlackCase);
      base.position.y = 0.08;
      g.add(base);
      var goldTube = new THREE.Mesh(new THREE.CylinderGeometry(0.048, 0.048, 0.08, 10), matGold);
      goldTube.position.y = 0.20;
      g.add(goldTube);
      var tip = new THREE.Mesh(new THREE.ConeGeometry(0.045, 0.11, 10), isPink ? matLipstickPink : matLipstickRed);
      tip.position.y = 0.295;
      g.add(tip);
      return g;
    }

    function makeCreamJar() {
      var g = new THREE.Group();
      var jar = new THREE.Mesh(new THREE.CylinderGeometry(0.115, 0.105, 0.14, 12), matCosmeticPink);
      jar.position.y = 0.07;
      g.add(jar);
      var goldLid = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.12, 0.05, 12), matGold);
      goldLid.position.y = 0.165;
      g.add(goldLid);
      return g;
    }

    function makeBreadLoaf() {
      var g = new THREE.Group();
      var base = new THREE.Mesh(new THREE.BoxGeometry(0.30, 0.18, 0.20), matBread);
      base.position.y = 0.09;
      g.add(base);
      var topCrust = new THREE.Mesh(new THREE.CylinderGeometry(0.10, 0.10, 0.30, 8), matBreadCrust);
      topCrust.rotation.z = Math.PI / 2;
      topCrust.position.y = 0.18;
      g.add(topCrust);
      return g;
    }

    function makeRandomMiniProduct(seed) {
      var t = seed % 5;
      if (t === 0) return makeOrangeJuiceBottle();
      if (t === 1) return makeSnackBag(seed % 3);
      if (t === 2) return makeCosmeticLotion(seed % 2 === 0);
      if (t === 3) return makeLipstick(seed % 2 === 0);
      return makeCarton(seed % 2 === 0);
    }

    var storeGroup = new THREE.Group();
    scene.add(storeGroup);

    // --- 1. STORE FLOOR (Checkered Tiles + ປ້າຍ 3D "WlaoDev POS" ຝັງເທິງພື້ນຮ້ານ) ---
    var floorGroup = new THREE.Group();
    var tileGeo = new THREE.PlaneGeometry(1.6, 1.6);
    tileGeo.rotateX(-Math.PI / 2);
    var matTileA = new THREE.MeshLambertMaterial({ color: 0xf8fafc });
    var matTileB = new THREE.MeshLambertMaterial({ color: 0xeef2f6 });

    for (var fx = -5; fx <= 5; fx++) {
      for (var fz = -5; fz <= 5; fz++) {
        var isAlt = (Math.abs(fx + fz) % 2 === 0);
        var tile = new THREE.Mesh(tileGeo, isAlt ? matTileA : matTileB);
        tile.position.set(fx * 1.6, 0, fz * 1.6);
        tile.receiveShadow = true;
        floorGroup.add(tile);
      }
    }
    storeGroup.add(floorGroup);

    // ປ້າຍຊື່ລະບົບ 3D "WlaoDev POS" ເທິງພື້ນຮ້ານ
    storeGroup.add(create3DFloorBrandGroup());

    // Baseboards
    var borderMat = new THREE.MeshLambertMaterial({ color: 0xcbd5e1 });
    var borderGeoH = new THREE.BoxGeometry(17.8, 0.25, 0.25);
    var borderTop = new THREE.Mesh(borderGeoH, borderMat);
    borderTop.position.set(0, 0.12, -8.9);
    storeGroup.add(borderTop);

    var borderBottom = new THREE.Mesh(borderGeoH, borderMat);
    borderBottom.position.set(0, 0.12, 8.9);
    storeGroup.add(borderBottom);

    var borderGeoV = new THREE.BoxGeometry(0.25, 0.25, 17.8);
    var borderLeft = new THREE.Mesh(borderGeoV, borderMat);
    borderLeft.position.set(-8.9, 0.12, 0);
    storeGroup.add(borderLeft);

    var borderRight = new THREE.Mesh(borderGeoV, borderMat);
    borderRight.position.set(8.9, 0.12, 0);
    storeGroup.add(borderRight);

    // --- 2. BACK WALL, ULTRA-HD 3D STORE/BRANCH SIGN & 4 SYMMETRICAL DRINK CHILLERS ---
    var wallBackGeo = new THREE.BoxGeometry(17.6, 2.75, 0.4);
    var wallBack = new THREE.Mesh(wallBackGeo, matWall);
    wallBack.position.set(0, 1.38, -8.7);
    wallBack.receiveShadow = true;
    storeGroup.add(wallBack);

    // ປ້າຍຊື່ຮ້ານ ແລະ ຊື່ສາຂາ 3D ຄົມຊັດລະດັບ Ultra-HD
    storeGroup.add(create3DStoreSignGroup());

    function createChiller(x, chillerType) {
      var g = new THREE.Group();
      var frame = new THREE.Mesh(new THREE.BoxGeometry(3.3, 2.52, 1.20), new THREE.MeshLambertMaterial({ color: 0x1e293b }));
      frame.position.set(0, 1.26, 0);
      frame.castShadow = true;
      frame.receiveShadow = true;
      g.add(frame);

      var innerBack = new THREE.Mesh(new THREE.PlaneGeometry(3.0, 2.22), new THREE.MeshBasicMaterial({ color: 0xe0f2fe }));
      innerBack.position.set(0, 1.26, -0.45);
      g.add(innerBack);

      var rows = [0.38, 0.92, 1.46, 1.98];
      for (var rIdx = 0; rIdx < rows.length; rIdx++) {
        var sy = rows[rIdx];
        var shelfBar = new THREE.Mesh(new THREE.BoxGeometry(3.0, 0.05, 0.95), matWhite);
        shelfBar.position.set(0, sy, 0);
        g.add(shelfBar);

        for (var dx = -1.2; dx <= 1.2; dx += 0.34) {
          var item;
          if (chillerType === 0) {
            item = (rIdx % 2 === 0) ? makeOrangeJuiceBottle() : makeCarton(true);
          } else if (chillerType === 1) {
            item = (rIdx % 2 === 0) ? makeWaterBottle() : makeCarton(false);
          } else {
            item = makeSodaCan(rIdx % 2 === 1);
          }
          item.position.set(dx, sy + 0.03, 0.18);
          g.add(item);
        }
      }

      var glass = new THREE.Mesh(new THREE.BoxGeometry(3.05, 2.26, 0.06), matGlass);
      glass.position.set(0, 1.26, 0.58);
      g.add(glass);

      g.position.set(x, 0, -8.0);
      return g;
    }
    // ຕູ້ແຊ່ເຄື່ອງດື່ມ 4 ຕູ້ ລຽງສົມດຸນຊ້າຍ-ຂວາ ເບິ່ງສະອາດຕາ
    storeGroup.add(createChiller(-5.7, 0));
    storeGroup.add(createChiller(-1.9, 1));
    storeGroup.add(createChiller(1.9, 2));
    storeGroup.add(createChiller(5.7, 0));

    // --- 3. GROCERY & COSMETICS AISLE SHELVES (ຈັດວາງລະບຽບ ສະອາດຕາ) ---
    function createGondola(x, z, length, aisleType) {
      var g = new THREE.Group();
      var isCosmeticAisle = (aisleType === 1);
      var mainMat = new THREE.MeshLambertMaterial({ color: isCosmeticAisle ? 0xfdf2f8 : 0xf8fafc });
      var postMat = new THREE.MeshLambertMaterial({ color: isCosmeticAisle ? 0xdb2777 : 0x0284c7 });
      var stripColor = isCosmeticAisle ? 0xf472b6 : 0xfacc15;

      var base = new THREE.Mesh(new THREE.BoxGeometry(1.3, 0.28, length), mainMat);
      base.position.set(0, 0.14, 0);
      base.castShadow = true;
      g.add(base);

      var backP = new THREE.Mesh(new THREE.BoxGeometry(0.10, 2.45, length), postMat);
      backP.position.set(0, 1.32, 0);
      backP.castShadow = true;
      g.add(backP);

      var endGeo = new THREE.BoxGeometry(1.32, 2.45, 0.12);
      var endA = new THREE.Mesh(endGeo, mainMat);
      endA.position.set(0, 1.32, length / 2);
      g.add(endA);
      var endB = new THREE.Mesh(endGeo, mainMat);
      endB.position.set(0, 1.32, -length / 2);
      g.add(endB);

      var levels = [0.62, 1.30, 1.98];
      for (var lIdx = 0; lIdx < levels.length; lIdx++) {
        var lvl = levels[lIdx];
        var plank = new THREE.Mesh(new THREE.BoxGeometry(1.22, 0.05, length - 0.16), mainMat);
        plank.position.set(0, lvl, 0);
        plank.castShadow = true;
        g.add(plank);

        var strip = new THREE.Mesh(new THREE.BoxGeometry(1.24, 0.04, length - 0.16), new THREE.MeshLambertMaterial({ color: stripColor }));
        strip.position.set(0, lvl, 0);
        g.add(strip);

        var pCount = 0;
        for (var pz = -length / 2 + 0.42; pz <= length / 2 - 0.42; pz += 0.42) {
          var prodL, prodR;
          if (aisleType === 0) {
            prodL = makeSnackBag((lIdx + pCount) % 3);
            prodL.rotation.y = -Math.PI / 2;
            prodR = (lIdx === 0) ? makeOrangeJuiceBottle() : makeSnackBag((lIdx + pCount + 1) % 3);
            if (lIdx !== 0) prodR.rotation.y = Math.PI / 2;
          } else if (aisleType === 1) {
            if (lIdx === 2) {
              prodL = makeLipstick(pCount % 2 === 0);
              prodR = makeCreamJar();
            } else if (lIdx === 1) {
              prodL = makeCosmeticLotion(pCount % 2 === 0);
              prodR = makeLipstick(pCount % 2 === 1);
            } else {
              prodL = makeCreamJar();
              prodR = makeCosmeticLotion(true);
            }
          } else {
            prodL = (lIdx === 2) ? makeBreadLoaf() : ((lIdx === 1) ? makeCupNoodle() : makeOrangeJuiceBottle());
            prodR = (lIdx === 0) ? makeCarton(true) : ((lIdx === 1) ? makeCarton(false) : makeCupNoodle());
          }

          prodL.position.set(-0.34, lvl + 0.025, pz);
          g.add(prodL);

          prodR.position.set(0.34, lvl + 0.025, pz);
          g.add(prodR);
          pCount++;
        }
      }

      g.position.set(x, 0, z);
      return g;
    }

    storeGroup.add(createGondola(-5.6, -2.55, 4.2, 0));
    storeGroup.add(createGondola(-2.2, -2.55, 4.2, 1));
    storeGroup.add(createGondola(1.2, -2.55, 4.2, 2));

    // --- 3B. FRESH FRUIT & ORANGE JUICE PROMO TABLE ---
    var fruitStand = new THREE.Group();
    var standBase = new THREE.Mesh(new THREE.BoxGeometry(2.2, 0.85, 1.5), new THREE.MeshLambertMaterial({ color: 0xd97706 }));
    standBase.position.set(0, 0.425, 0);
    standBase.castShadow = true;
    fruitStand.add(standBase);

    var standTray = new THREE.Mesh(new THREE.BoxGeometry(2.35, 0.12, 1.65), new THREE.MeshLambertMaterial({ color: 0xfef3c7 }));
    standTray.position.set(0, 0.88, 0);
    fruitStand.add(standTray);

    var matOrangeFruit = new THREE.MeshLambertMaterial({ color: 0xf97316 });
    var matAppleFruit = new THREE.MeshLambertMaterial({ color: 0xef4444 });
    var matGreenApple = new THREE.MeshLambertMaterial({ color: 0x22c55e });

    for (var fx2 = -0.85; fx2 <= 0.85; fx2 += 0.28) {
      for (var fz2 = -0.50; fz2 <= 0.50; fz2 += 0.28) {
        if (fx2 < -0.1) {
          var org = new THREE.Mesh(new THREE.SphereGeometry(0.11, 10, 10), matOrangeFruit);
          org.position.set(fx2, 1.02, fz2);
          fruitStand.add(org);
        } else if (fx2 < 0.45) {
          var app = new THREE.Mesh(new THREE.SphereGeometry(0.11, 10, 10), (fz2 > 0) ? matAppleFruit : matGreenApple);
          app.position.set(fx2, 1.02, fz2);
          fruitStand.add(app);
        } else {
          var oj = makeOrangeJuiceBottle();
          oj.position.set(fx2, 0.94, fz2);
          fruitStand.add(oj);
        }
      }
    }
    fruitStand.position.set(5.3, 0, -2.9);
    storeGroup.add(fruitStand);

    // --- 4. CHECKOUT COUNTER, MOVING CONVEYOR BELT ITEMS, BARCODE LASER SCANNER, POS MONITOR & RECEIPT PRINTER ---
    var counterGroup = new THREE.Group();
    var deskMat = new THREE.MeshLambertMaterial({ color: 0x1e293b });
    var deskTopMat = new THREE.MeshLambertMaterial({ color: 0xf59e0b });

    var deskBody = new THREE.Mesh(new THREE.BoxGeometry(4.2, 1.25, 1.25), deskMat);
    deskBody.position.set(0, 0.62, 0);
    deskBody.castShadow = true;
    deskBody.receiveShadow = true;
    counterGroup.add(deskBody);

    var deskTop = new THREE.Mesh(new THREE.BoxGeometry(4.4, 0.12, 1.40), deskTopMat);
    deskTop.position.set(0, 1.28, 0);
    deskTop.castShadow = true;
    counterGroup.add(deskTop);

    var beltMesh = new THREE.Mesh(new THREE.BoxGeometry(2.10, 0.03, 0.82), new THREE.MeshLambertMaterial({ color: 0x334155 }));
    beltMesh.position.set(-0.92, 1.35, 0.08);
    counterGroup.add(beltMesh);

    var scannerPlate = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.035, 0.42), new THREE.MeshBasicMaterial({ color: 0x38bdf8 }));
    scannerPlate.position.set(-0.20, 1.355, 0.08);
    counterGroup.add(scannerPlate);

    var scanRingGeo = new THREE.RingGeometry(0.14, 0.24, 20);
    scanRingGeo.rotateX(-Math.PI / 2);
    var scanRingMat = new THREE.MeshBasicMaterial({ color: 0x22c55e, side: THREE.DoubleSide, transparent: true, opacity: 0.9 });
    var scanFlashRing = new THREE.Mesh(scanRingGeo, scanRingMat);
    scanFlashRing.position.set(-0.20, 1.38, 0.08);
    scanFlashRing.visible = false;
    counterGroup.add(scanFlashRing);

    var beltProducts = [
      makeOrangeJuiceBottle(),
      makeSnackBag(0),
      makeCosmeticLotion(false),
      makeLipstick(false),
      makeCarton(false)
    ];
    beltProducts.forEach(function(p, i) {
      p.position.set(-1.75 + i * 0.38, 1.36, 0.08);
      counterGroup.add(p);
    });

    var bagGroup = new THREE.Group();
    var bagMat = new THREE.MeshLambertMaterial({ color: 0xffffff });
    var bagBody = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.40, 0.28), bagMat);
    bagBody.position.y = 0.20;
    bagGroup.add(bagBody);
    var bagLogo = new THREE.Mesh(new THREE.PlaneGeometry(0.22, 0.16), new THREE.MeshBasicMaterial({ color: 0x16a34a }));
    bagLogo.position.set(0, 0.22, 0.145);
    bagGroup.add(bagLogo);
    bagGroup.position.set(-1.82, 1.36, 0.15);
    counterGroup.add(bagGroup);

    var wingBody = new THREE.Mesh(new THREE.BoxGeometry(0.95, 1.25, 1.65), deskMat);
    wingBody.position.set(1.65, 0.62, 0.85);
    wingBody.castShadow = true;
    counterGroup.add(wingBody);

    // Dual-Screen POS Terminal
    var posScreenMat = new THREE.MeshBasicMaterial({ map: posTex });
    var posFrameMat = new THREE.MeshLambertMaterial({ color: 0x0f172a });

    var posStand = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.13, 0.45, 10), matMetal);
    posStand.position.set(0.62, 1.54, 0.05);
    counterGroup.add(posStand);

    var posBase = new THREE.Mesh(new THREE.BoxGeometry(0.48, 0.05, 0.36), matMetal);
    posBase.position.set(0.62, 1.36, 0.05);
    counterGroup.add(posBase);

    var posMonitorGroup = new THREE.Group();
    posMonitorGroup.position.set(0.62, 1.98, 0.10);
    posMonitorGroup.rotation.x = -0.38;

    var posCase = new THREE.Mesh(new THREE.BoxGeometry(1.38, 0.88, 0.08), posFrameMat);
    posCase.castShadow = true;
    posMonitorGroup.add(posCase);

    var posFrontDisplay = new THREE.Mesh(new THREE.PlaneGeometry(1.28, 0.78), posScreenMat);
    posFrontDisplay.position.set(0, 0, 0.045);
    posMonitorGroup.add(posFrontDisplay);

    var posBackDisplay = new THREE.Mesh(new THREE.PlaneGeometry(1.28, 0.78), posScreenMat);
    posBackDisplay.position.set(0, 0, -0.045);
    posBackDisplay.rotation.y = Math.PI;
    posMonitorGroup.add(posBackDisplay);

    counterGroup.add(posMonitorGroup);

    // --- THERMAL RECEIPT PRINTER WITH ANIMATED PAPER BILL ---
    var printerGroup = new THREE.Group();
    printerGroup.position.set(1.58, 1.34, 0.25);

    var printerBody = new THREE.Mesh(new THREE.BoxGeometry(0.50, 0.34, 0.52), new THREE.MeshLambertMaterial({ color: 0x0f172a }));
    printerBody.position.set(0, 0.17, 0);
    printerBody.castShadow = true;
    printerGroup.add(printerBody);

    var printerTop = new THREE.Mesh(new THREE.BoxGeometry(0.48, 0.05, 0.48), new THREE.MeshLambertMaterial({ color: 0x334155 }));
    printerTop.position.set(0, 0.35, 0);
    printerGroup.add(printerTop);

    var printerSlot = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.025, 0.06), new THREE.MeshBasicMaterial({ color: 0x000000 }));
    printerSlot.position.set(0, 0.38, 0.04);
    printerGroup.add(printerSlot);

    var printerLed = new THREE.Mesh(new THREE.SphereGeometry(0.035, 8, 8), new THREE.MeshBasicMaterial({ color: 0x22c55e }));
    printerLed.position.set(0.16, 0.28, 0.26);
    printerGroup.add(printerLed);

    var receiptTex = createReceiptTexture();
    var receiptMat = new THREE.MeshBasicMaterial({ map: receiptTex, side: THREE.DoubleSide });
    var receiptPaper = new THREE.Mesh(new THREE.PlaneGeometry(0.36, 0.72), receiptMat);
    receiptPaper.position.set(0, 0.62, 0.06);
    receiptPaper.rotation.x = -0.25;
    printerGroup.add(receiptPaper);

    counterGroup.add(printerGroup);

    // ວາງເຄົ້າເຕີຊຳລະເງິນຢູ່ໂຊນກາງ-ຂວາ (z = 1.15) ເພື່ອເປີດພື້ນທີ່ດ້ານໜ້າໃຫ້ປ້າຍ WlaoDev POS ໃຫຍ່ເຕັມພື້ນ ສະອາດຕາ
    counterGroup.position.set(4.85, 0, 1.15);
    storeGroup.add(counterGroup);

    // --- 4B. REALISTIC CASHIER HOLDING BARCODE SCANNER GUN & SCANNING PRODUCTS ---
    var cashierGroup = new THREE.Group();
    var skinMat = new THREE.MeshLambertMaterial({ color: 0xffdbac });
    var shoeMat = new THREE.MeshLambertMaterial({ color: 0x0f172a });

    var cHeadGroup = new THREE.Group();
    cHeadGroup.position.set(0, 2.04, 0);
    var head = new THREE.Mesh(new THREE.SphereGeometry(0.27, 16, 14), skinMat);
    cHeadGroup.add(head);

    var capMat = new THREE.MeshLambertMaterial({ color: 0x2563eb });
    var cap = new THREE.Mesh(new THREE.SphereGeometry(0.285, 16, 12, 0, Math.PI * 2, 0, Math.PI / 2), capMat);
    cap.position.set(0, 0.08, 0);
    cHeadGroup.add(cap);
    var visor = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.045, 0.24), capMat);
    visor.position.set(0, 0.09, 0.22);
    cHeadGroup.add(visor);

    var eyeMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });
    var eyeL = new THREE.Mesh(new THREE.SphereGeometry(0.038, 8, 8), eyeMat);
    eyeL.position.set(-0.09, 0.02, 0.24);
    cHeadGroup.add(eyeL);
    var eyeR = new THREE.Mesh(new THREE.SphereGeometry(0.038, 8, 8), eyeMat);
    eyeR.position.set(0.09, 0.02, 0.24);
    cHeadGroup.add(eyeR);
    cashierGroup.add(cHeadGroup);

    var uniformMat = new THREE.MeshLambertMaterial({ color: 0x2563eb });
    var apronMat = new THREE.MeshLambertMaterial({ color: 0xf59e0b });
    var body = new THREE.Mesh(new THREE.CylinderGeometry(0.23, 0.27, 0.76, 12), uniformMat);
    body.position.set(0, 1.46, 0);
    cashierGroup.add(body);

    var apron = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.56, 0.12), apronMat);
    apron.position.set(0, 1.42, 0.19);
    cashierGroup.add(apron);

    var cArmLGroup = new THREE.Group();
    cArmLGroup.position.set(-0.32, 1.72, 0.05);
    var armL = new THREE.Mesh(new THREE.CylinderGeometry(0.068, 0.062, 0.48, 8), uniformMat);
    armL.position.set(0, -0.20, 0.14);
    armL.rotation.x = Math.PI / 2.8;
    cArmLGroup.add(armL);

    var handL = new THREE.Mesh(new THREE.SphereGeometry(0.075, 8, 8), skinMat);
    handL.position.set(0, -0.34, 0.34);
    cArmLGroup.add(handL);

    var scannerGunGroup = new THREE.Group();
    scannerGunGroup.position.set(0, -0.32, 0.40);
    var gunHandle = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.035, 0.16, 8), new THREE.MeshLambertMaterial({ color: 0x0f172a }));
    gunHandle.rotation.x = 0.3;
    scannerGunGroup.add(gunHandle);
    var gunHead = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.09, 0.18), new THREE.MeshLambertMaterial({ color: 0x2563eb }));
    gunHead.position.set(0, 0.08, 0.05);
    scannerGunGroup.add(gunHead);

    var laserBeamGeo = new THREE.ConeGeometry(0.22, 0.65, 12, 1, true);
    laserBeamGeo.rotateX(-Math.PI / 2);
    var laserBeamMat = new THREE.MeshBasicMaterial({
      color: 0xef4444,
      transparent: true,
      opacity: 0.55,
      side: THREE.DoubleSide
    });
    var laserBeamMesh = new THREE.Mesh(laserBeamGeo, laserBeamMat);
    laserBeamMesh.position.set(0, -0.06, 0.42);
    laserBeamMesh.rotation.x = 0.42;
    scannerGunGroup.add(laserBeamMesh);

    var laserLineMesh = new THREE.Mesh(
      new THREE.BoxGeometry(0.34, 0.025, 0.025),
      new THREE.MeshBasicMaterial({ color: 0xff0000 })
    );
    laserLineMesh.position.set(0, -0.20, 0.62);
    scannerGunGroup.add(laserLineMesh);

    cArmLGroup.add(scannerGunGroup);
    cashierGroup.add(cArmLGroup);

    var cArmRGroup = new THREE.Group();
    cArmRGroup.position.set(0.32, 1.72, 0.05);
    var armR = new THREE.Mesh(new THREE.CylinderGeometry(0.068, 0.062, 0.48, 8), uniformMat);
    armR.position.set(0, -0.20, 0.14);
    armR.rotation.x = Math.PI / 3;
    cArmRGroup.add(armR);
    var handR = new THREE.Mesh(new THREE.SphereGeometry(0.075, 8, 8), skinMat);
    handR.position.set(0, -0.34, 0.34);
    cArmRGroup.add(handR);
    cashierGroup.add(cArmRGroup);

    cashierGroup.position.set(4.50, 0, 0.20);
    cashierGroup.rotation.y = 0;
    storeGroup.add(cashierGroup);

    // --- 5. 18 REALISTIC HUMAN SHOPPER CHARACTERS + ລະບົບຍ່າງຕາມຊ່ອງທາງຍ່າງ (ບໍ່ທະລຸຊັ້ນວາງສິນຄ້າ 100%) ---
    var shoppers = [];
    var shopperCount = 18;

    // ຊ່ອງທາງຍ່າງລະຫວ່າງຊັ້ນວາງສິນຄ້າ (Vertical Aisle Lanes) ແລະ ທາງຍ່າງຕັດຂວາງຫົວ-ທ້າຍຊັ້ນວາງ
    var LANE_XS = [-7.05, -3.90, -0.50, 2.85];
    var Z_BACK_CORRIDOR  = -5.75; // ທາງຍ່າງດ້ານຫຼັງຊັ້ນວາງ (ຕໍ່ໜ້າຕູ້ແຊ່)
    var Z_FRONT_CORRIDOR = 0.32;  // ທາງຍ່າງດ້ານໜ້າຊັ້ນວາງ (ກ່ອນເຖິງເຄົ້າເຕີ)

    // ຂອບເຂດສິ່ງກີດຂວາງ (Collision Boxes: ຊັ້ນວາງສິນຄ້າ 3 ແຖວ, ໂຕະໝາກໄມ້, ເຄົ້າເຕີ, ແລະ ປ້າຍພື້ນ WlaoDev POS)
    var OBSTACLE_BOXES = [
      { minX: -6.58, maxX: -4.62, minZ: -4.96, maxZ: -0.14 }, // Gondola 1 (ຊ້າຍ)
      { minX: -3.18, maxX: -1.22, minZ: -4.96, maxZ: -0.14 }, // Gondola 2 (ກາງ)
      { minX:  0.22, maxX:  2.18, minZ: -4.96, maxZ: -0.14 }, // Gondola 3 (ຂວາ)
      { minX:  3.78, maxX:  6.82, minZ: -4.05, maxZ: -1.75 }, // ໂຕະໝາກໄມ້ & ນ້ຳສົ້ມ
      { minX:  2.30, maxX:  7.35, minZ: -0.45, maxZ:  2.12 }, // ເຄົ້າເຕີພະນັກງານ & ຄອມ POS
      { minX: -8.35, maxX:  8.35, minZ:  3.35, maxZ:  8.10 }  // ປ້າຍພື້ນ WlaoDev POS (ກັນຄົນຍ່າງບັງ)
    ];

    function pushOutOfObstacles(pos) {
      for (var i = 0; i < OBSTACLE_BOXES.length; i++) {
        var b = OBSTACLE_BOXES[i];
        if (pos.x > b.minX && pos.x < b.maxX && pos.z > b.minZ && pos.z < b.maxZ) {
          var dLeft  = pos.x - b.minX;
          var dRight = b.maxX - pos.x;
          var dBack  = pos.z - b.minZ;
          var dFront = b.maxZ - pos.z;
          var minD = Math.min(dLeft, dRight, dBack, dFront);
          if (minD === dLeft)       pos.x = b.minX - 0.03;
          else if (minD === dRight) pos.x = b.maxX + 0.03;
          else if (minD === dBack)  pos.z = b.minZ - 0.03;
          else                      pos.z = b.maxZ + 0.03;
        }
      }
    }

    function getNearestLaneX(x) {
      var best = LANE_XS[0];
      var bestDiff = Math.abs(x - best);
      for (var i = 1; i < LANE_XS.length; i++) {
        var d = Math.abs(x - LANE_XS[i]);
        if (d < bestDiff) {
          bestDiff = d;
          best = LANE_XS[i];
        }
      }
      return best;
    }

    // ສ້າງເສັ້ນທາງຍ່າງອ້ອມຊັ້ນວາງສິນຄ້າ ແລະ ເຄົ້າເຕີ ໂດຍບໍ່ໃຫ້ຍ່າງທະລຸບັອກຂາຍສິນຄ້າເດັດຂາດ
    function buildWalkPath(fromX, fromZ, destPt) {
      var path = [];
      var startLane = getNearestLaneX(fromX);
      var endLane   = (typeof destPt.laneX === 'number') ? destPt.laneX : getNearestLaneX(destPt.x);

      var isFromQueueZone = (fromZ >= 1.6);
      var isToQueueZone   = (destPt.z >= 1.6);

      // ກໍລະນີ 1: ຍ່າງຈາກຊັ້ນວາງສິນຄ້າ ໄປຫາ ຄິວໜ້າເຄົ້າເຕີ ຫຼື ທາງອອກ
      if (!isFromQueueZone && isToQueueZone) {
        if (fromZ <= -5.0) {
          // ຢູ່ໜ້າຕູ້ແຊ່ດ້ານຫຼັງ -> ຍ່າງມາຫາຊ່ອງທາງຍ່າງກ່ອນ
          var safeDownLane = (startLane > 2.0) ? -0.50 : startLane;
          path.push({ x: safeDownLane, z: Z_BACK_CORRIDOR });
          path.push({ x: safeDownLane, z: Z_FRONT_CORRIDOR });
        } else if (startLane > 2.0) {
          // ຢູ່ຊ່ອງຂວາສຸດ (ติดໂຕະໝາກໄມ້/ເຄົ້າເຕີ) -> ອ້ອມຂຶ້ນທາງຫຼັງ ຫຼື ອອກຊ່ອງກາງ
          if (fromZ < -2.2) {
            path.push({ x: startLane, z: Z_BACK_CORRIDOR });
            path.push({ x: -0.50,     z: Z_BACK_CORRIDOR });
            path.push({ x: -0.50,     z: Z_FRONT_CORRIDOR });
          } else {
            path.push({ x: 1.95, z: Z_FRONT_CORRIDOR });
          }
        } else {
          // ຍ່າງອອກມາທາງໜ້າຊັ້ນວາງສິນຄ້າຕາມຊ່ອງທາງຍ່າງຂອງຕົນເອງ
          path.push({ x: startLane, z: Z_FRONT_CORRIDOR });
        }

        // ອ້ອມຂອບຊ້າຍຂອງເຄົ້າເຕີ (x = 1.85) ເພື່ອເຂົ້າແຖວໜ້າເຄົ້າເຕີ ໂດຍບໍ່ບັງປ້າຍພື້ນ WlaoDev POS
        if (destPt.x > 2.0) {
          path.push({ x: 1.85, z: Z_FRONT_CORRIDOR });
          path.push({ x: 1.85, z: 2.60 });
        } else {
          path.push({ x: destPt.x, z: 2.55 });
        }
        path.push(destPt);
        return path;
      }

      // ກໍລະນີ 2: ຍ່າງຈາກໂຊນໜ້າຮ້ານ/ທາງເຂົ້າ ເຂົ້າໄປຫາຊັ້ນວາງສິນຄ້າ
      if (isFromQueueZone && !isToQueueZone) {
        if (fromX > 2.0) {
          path.push({ x: 1.85, z: 2.60 });
          path.push({ x: 1.85, z: Z_FRONT_CORRIDOR });
        } else {
          path.push({ x: fromX, z: Z_FRONT_CORRIDOR });
        }

        if (destPt.z <= -5.0) {
          // ໄປຕູ້ແຊ່ດ້ານຫຼັງ: ຍ່າງຜ່ານຊ່ອງທາງຍ່າງຂຶ້ນໄປທາງຫຼັງສຸດກ່ອນ
          var upLane = (endLane > 2.0) ? -0.50 : endLane;
          path.push({ x: upLane, z: Z_FRONT_CORRIDOR });
          path.push({ x: upLane, z: Z_BACK_CORRIDOR });
          path.push({ x: destPt.x, z: Z_BACK_CORRIDOR });
        } else if (endLane > 2.0) {
          // ໄປຊ່ອງຂວາສຸດ (Gondola 3 ຂວາ / ໂຕະໝາກໄມ້): ອ້ອມທາງຫຼັງຊັ້ນວາງ
          path.push({ x: -0.50,   z: Z_FRONT_CORRIDOR });
          path.push({ x: -0.50,   z: Z_BACK_CORRIDOR });
          path.push({ x: endLane, z: Z_BACK_CORRIDOR });
        } else {
          path.push({ x: endLane, z: Z_FRONT_CORRIDOR });
        }
        path.push(destPt);
        return path;
      }

      // ກໍລະນີ 3: ຍ່າງຢູ່ໃນໂຊນຊັອບປິ້ງ ລະຫວ່າງຊັ້ນວາງສິນຄ້າດ້ວຍກັນ
      var bothInBackCorridor = (fromZ <= -5.0 && destPt.z <= -5.0);
      var sameAisleLane      = (Math.abs(startLane - endLane) < 0.25 && fromZ > -5.0 && destPt.z > -5.0);

      if (bothInBackCorridor || sameAisleLane) {
        path.push(destPt);
        return path;
      }

      // ຖ້າຢູ່ຄົນລະຊ່ອງຊັ້ນວາງ: ຕ້ອງຍ່າງອອກຫົວ ຫຼື ທ້າຍຊັ້ນວາງສິນຄ້າກ່ອນ ແລ້ວຈຶ່ງລ້ຽວເຂົ້າຊ່ອງໃໝ່!
      var useBackCorridor = (fromZ <= -5.0 || destPt.z <= -5.0 || startLane > 2.0 || endLane > 2.0 || ((fromZ + destPt.z) * 0.5 < -2.4));
      var crossZ = useBackCorridor ? Z_BACK_CORRIDOR : Z_FRONT_CORRIDOR;

      if (fromZ > -5.0) {
        path.push({ x: startLane, z: crossZ });
      } else {
        path.push({ x: fromX, z: Z_BACK_CORRIDOR });
      }

      if (destPt.z <= -5.0) {
        path.push({ x: destPt.x, z: Z_BACK_CORRIDOR });
      } else {
        path.push({ x: endLane, z: crossZ });
      }

      path.push(destPt);
      return path;
    }

    var WAYPOINTS = {
      entrance: { x: -7.05, z: 2.45, laneX: -7.05 },
      exit:     { x: -6.20, z: 2.55, laneX: -7.05 },
      aisles: [
        // ຊ່ອງທີ 1 (ຊ້າຍສຸດ x = -7.05): ເບິ່ງຊັ້ນວາງທີ 1 ຝັ່ງຊ້າຍ
        { x: -7.00, z: -3.6, laneX: -7.05, lookX: -5.6, lookZ: -3.6 },
        { x: -7.00, z: -2.4, laneX: -7.05, lookX: -5.6, lookZ: -2.4 },
        { x: -7.00, z: -1.2, laneX: -7.05, lookX: -5.6, lookZ: -1.2 },
        // ຊ່ອງທີ 2 (x = -3.90): ເບິ່ງຊັ້ນວາງທີ 1 ຝັ່ງຂວາ ແລະ ຊັ້ນວາງທີ 2 ຝັ່ງຊ້າຍ
        { x: -4.12, z: -3.5, laneX: -3.90, lookX: -5.6, lookZ: -3.5 },
        { x: -4.12, z: -1.5, laneX: -3.90, lookX: -5.6, lookZ: -1.5 },
        { x: -3.68, z: -3.5, laneX: -3.90, lookX: -2.2, lookZ: -3.5 },
        { x: -3.68, z: -2.3, laneX: -3.90, lookX: -2.2, lookZ: -2.3 },
        { x: -3.68, z: -1.2, laneX: -3.90, lookX: -2.2, lookZ: -1.2 },
        // ຊ່ອງທີ 3 (x = -0.50): ເບິ່ງຊັ້ນວາງທີ 2 ຝັ່ງຂວາ ແລະ ຊັ້ນວາງທີ 3 ຝັ່ງຊ້າຍ
        { x: -0.72, z: -3.5, laneX: -0.50, lookX: -2.2, lookZ: -3.5 },
        { x: -0.72, z: -1.5, laneX: -0.50, lookX: -2.2, lookZ: -1.5 },
        { x: -0.28, z: -3.5, laneX: -0.50, lookX:  1.2, lookZ: -3.5 },
        { x: -0.28, z: -2.3, laneX: -0.50, lookX:  1.2, lookZ: -2.3 },
        { x: -0.28, z: -1.2, laneX: -0.50, lookX:  1.2, lookZ: -1.2 },
        // ຊ່ອງທີ 4 (x = 2.85): ເບິ່ງຊັ້ນວາງທີ 3 ຝັ່ງຂວາ ແລະ ໂຕະໝາກໄມ້-ນ້ຳສົ້ມ
        { x:  2.65, z: -3.5, laneX:  2.85, lookX:  1.2, lookZ: -3.5 },
        { x:  2.65, z: -1.8, laneX:  2.85, lookX:  1.2, lookZ: -1.8 },
        { x:  3.15, z: -2.9, laneX:  2.85, lookX:  5.3, lookZ: -2.9 },
        // ໂຊນໜ້າຕູ້ແຊ່ເຄື່ອງດື່ມດ້ານຫຼັງ 4 ຕູ້ (z = -5.75)
        { x: -5.70, z: -5.75, laneX: -7.05, lookX: -5.7, lookZ: -8.0 },
        { x: -1.90, z: -5.75, laneX: -3.90, lookX: -1.9, lookZ: -8.0 },
        { x:  1.90, z: -5.75, laneX: -0.50, lookX:  1.9, lookZ: -8.0 },
        { x:  5.50, z: -5.75, laneX:  2.85, lookX:  5.7, lookZ: -8.0 }
      ],
      queue: [
        { x: 4.50, z: 2.52 },
        { x: 3.10, z: 2.62 },
        { x: 1.75, z: 2.62 },
        { x: 0.40, z: 2.62 }
      ]
    };

    var CLOTH_PALETTES = [
      { shirt: 0xef4444, pants: 0x1e293b, hair: 0x451a03 },
      { shirt: 0x10b981, pants: 0x0f172a, hair: 0x0f172a },
      { shirt: 0x3b82f6, pants: 0x334155, hair: 0xd97706 },
      { shirt: 0xf59e0b, pants: 0x1e3a8a, hair: 0x78350f },
      { shirt: 0x8b5cf6, pants: 0x0f172a, hair: 0x172554 },
      { shirt: 0xec4899, pants: 0x475569, hair: 0xb45309 },
      { shirt: 0x06b6d4, pants: 0x1e293b, hair: 0x1e1b4b },
      { shirt: 0xf97316, pants: 0x0f172a, hair: 0x000000 }
    ];

    function createShopper(idx) {
      var g = new THREE.Group();
      var palette = CLOTH_PALETTES[idx % CLOTH_PALETTES.length];

      var sMat = new THREE.MeshLambertMaterial({ color: palette.shirt });
      var pMat = new THREE.MeshLambertMaterial({ color: palette.pants });
      var hMat = new THREE.MeshLambertMaterial({ color: palette.hair });

      var headGroup = new THREE.Group();
      headGroup.position.set(0, 1.48, 0);

      var headMesh = new THREE.Mesh(new THREE.SphereGeometry(0.23, 14, 12), skinMat);
      headGroup.add(headMesh);

      var hairMesh = new THREE.Mesh(new THREE.SphereGeometry(0.248, 12, 10, 0, Math.PI * 2, 0, Math.PI * 0.58), hMat);
      hairMesh.position.set(0, 0.04, -0.02);
      headGroup.add(hairMesh);

      if (idx % 2 === 1) {
        var pony = new THREE.Mesh(new THREE.SphereGeometry(0.10, 8, 8), hMat);
        pony.position.set(0, 0.02, -0.24);
        headGroup.add(pony);
      }

      var eye1 = new THREE.Mesh(new THREE.SphereGeometry(0.032, 6, 6), eyeMat);
      eye1.position.set(-0.075, 0.01, 0.21);
      headGroup.add(eye1);
      var eye2 = new THREE.Mesh(new THREE.SphereGeometry(0.032, 6, 6), eyeMat);
      eye2.position.set(0.075, 0.01, 0.21);
      headGroup.add(eye2);
      g.add(headGroup);

      var bodyMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.19, 0.22, 0.56, 12), sMat);
      bodyMesh.position.set(0, 0.96, 0);
      bodyMesh.castShadow = true;
      g.add(bodyMesh);

      var armLGroup = new THREE.Group();
      armLGroup.position.set(-0.24, 1.18, 0);
      var sArmL = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.05, 0.42, 8), sMat);
      sArmL.position.set(0, -0.18, 0);
      armLGroup.add(sArmL);
      var sHandL = new THREE.Mesh(new THREE.SphereGeometry(0.06, 8, 8), skinMat);
      sHandL.position.set(0, -0.38, 0);
      armLGroup.add(sHandL);

      var heldItem = makeRandomMiniProduct(idx);
      heldItem.scale.set(0.65, 0.65, 0.65);
      heldItem.position.set(0, -0.42, 0.10);
      heldItem.visible = false;
      armLGroup.add(heldItem);
      g.add(armLGroup);

      var armRGroup = new THREE.Group();
      armRGroup.position.set(0.24, 1.18, 0);
      var sArmR = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.05, 0.42, 8), sMat);
      sArmR.position.set(0, -0.16, 0.08);
      sArmR.rotation.x = 0.45;
      armRGroup.add(sArmR);
      var sHandR = new THREE.Mesh(new THREE.SphereGeometry(0.06, 8, 8), skinMat);
      sHandR.position.set(0, -0.32, 0.16);
      armRGroup.add(sHandR);

      var basketGroup = new THREE.Group();
      var bMat = new THREE.MeshLambertMaterial({ color: 0xfacc15 });
      var bBox = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.20, 0.28), bMat);
      basketGroup.add(bBox);

      var miniOj = makeOrangeJuiceBottle();
      miniOj.scale.set(0.70, 0.70, 0.70);
      miniOj.position.set(-0.08, 0.06, 0);
      basketGroup.add(miniOj);

      var miniItem2 = (idx % 2 === 0) ? makeSnackBag(idx % 3) : makeCosmeticLotion(idx % 3 === 0);
      miniItem2.scale.set(0.65, 0.65, 0.65);
      miniItem2.position.set(0.08, 0.06, 0);
      basketGroup.add(miniItem2);

      basketGroup.position.set(0.04, -0.36, 0.20);
      armRGroup.add(basketGroup);
      g.add(armRGroup);

      var legGeo = new THREE.CylinderGeometry(0.065, 0.06, 0.48, 8);
      var shoeGeo = new THREE.BoxGeometry(0.11, 0.08, 0.18);

      var legL = new THREE.Group();
      legL.position.set(-0.10, 0.64, 0);
      var legLMesh = new THREE.Mesh(legGeo, pMat);
      legLMesh.position.set(0, -0.24, 0);
      legLMesh.castShadow = true;
      legL.add(legLMesh);
      var shoeL = new THREE.Mesh(shoeGeo, shoeMat);
      shoeL.position.set(0, -0.48, 0.03);
      legL.add(shoeL);
      g.add(legL);

      var legR = new THREE.Group();
      legR.position.set(0.10, 0.64, 0);
      var legRMesh = new THREE.Mesh(legGeo, pMat);
      legRMesh.position.set(0, -0.24, 0);
      legRMesh.castShadow = true;
      legR.add(legRMesh);
      var shoeR = new THREE.Mesh(shoeGeo, shoeMat);
      shoeR.position.set(0, -0.48, 0.03);
      legR.add(shoeR);
      g.add(legR);

      var initPt = WAYPOINTS.aisles[idx % WAYPOINTS.aisles.length];
      g.position.set(initPt.x, 0, initPt.z + (Math.random() - 0.5) * 0.25);
      pushOutOfObstacles(g.position);
      storeGroup.add(g);

      return {
        group: g,
        headGroup: headGroup,
        armLGroup: armLGroup,
        armRGroup: armRGroup,
        heldItem: heldItem,
        legL: legL,
        legR: legR,
        basket: basketGroup,
        state: 'SHOPPING',
        target: initPt,
        path: [initPt],
        // ຄວາມໄວຍ່າງອີງຕາມເວລາຈິງ (Units ຕໍ່ວິນາທີ) — ຍ່າງຄ່ອງແຄ້ວ ພໍດີ ບໍ່ຊ້າ ແລະ ບໍ່ໄວເກີນໄປໃນຈໍສຳພັດ 2 ໜ້າຈໍ
        speed: 3.15 + Math.random() * 0.65,
        walkCycle: Math.random() * Math.PI * 2,
        browseTimer: 0,
        browseMax: 1.55 + Math.random() * 1.05,
        shopStepsLeft: 2 + Math.floor(Math.random() * 3),
        queueIndex: -1,
        payTimer: 0
      };
    }

    for (var i = 0; i < shopperCount; i++) {
      shoppers.push(createShopper(i));
    }

    var queueShoppers = [];

    // ຟັງຊັນຊ່ວຍຍ່າງຕາມຈຸດ Waypoints ໃນ s.path ເທື່ອລະຈຸດ (ອ້ອມຊັ້ນວາງສິນຄ້າ 100%)
    function stepShopperAlongPath(s, moveSpeed, dt, cycleRate) {
      if (!s.path || s.path.length === 0) {
        s.path = [s.target];
      }
      var wp = s.path[0];
      var dx = wp.x - s.group.position.x;
      var dz = wp.z - s.group.position.z;
      var dist = Math.sqrt(dx * dx + dz * dz);
      var stepMove = moveSpeed * dt;

      if (dist <= Math.max(0.16, stepMove)) {
        s.group.position.x = wp.x;
        s.group.position.z = wp.z;
        pushOutOfObstacles(s.group.position);
        s.path.shift();
        return (s.path.length === 0);
      }

      var angle = Math.atan2(dx, dz);
      s.group.rotation.y = angle;
      s.group.position.x += Math.sin(angle) * stepMove;
      s.group.position.z += Math.cos(angle) * stepMove;
      pushOutOfObstacles(s.group.position);

      s.walkCycle += dt * cycleRate;
      s.legL.rotation.x = Math.sin(s.walkCycle) * 0.60;
      s.legR.rotation.x = -Math.sin(s.walkCycle) * 0.60;
      s.armLGroup.rotation.x = -Math.sin(s.walkCycle) * 0.46;
      s.armRGroup.rotation.x = Math.sin(s.walkCycle) * 0.22;
      s.group.position.y = Math.abs(Math.sin(s.walkCycle)) * 0.055;
      return false;
    }

    // Floating Coin
    var coinGeo = new THREE.CylinderGeometry(0.22, 0.22, 0.06, 12);
    coinGeo.rotateX(Math.PI / 2);
    var coinMat = new THREE.MeshBasicMaterial({ color: 0xfbbf24 });
    var coinMesh = new THREE.Mesh(coinGeo, coinMat);
    coinMesh.position.set(4.50, 2.65, 1.85);
    coinMesh.visible = false;
    storeGroup.add(coinMesh);

    // --- ANIMATION LOOP (Delta-Time ຄວບຄຸມຄວາມໄວໃຫ້ສະໝ່ຳສະເໝີໃນຈໍຄອມສຳພັດ 2 ໜ້າຈໍ) ---
    var clock = new THREE.Clock();
    var receiptProgress = 0;
    var scanCycleTimer = 0;
    var scanCycleDuration = 1.15; // 1.15 ວິນາທີຕໍ່ການສະແກນສິນຄ້າ 1 ຊິ້ນ
    var lastBeepTriggered = false;
    var activeScanIdx = 0;
    var elapsedTotal = 0;

    function animate() {
      requestAnimationFrame(animate);

      // ໃຊ້ Delta-Time (dt) ເພື່ອບໍ່ໃຫ້ຄົນຍ່າງຊ້າລົງເວລາ FPS ຕ່ຳໃນຈໍສຳພັດ 2 ໜ້າຈໍ ແລະ ລ໋ອກเพดาน dt ບໍ່ໃຫ້ກະໂດດເກີນ
      var rawDt = clock.getDelta();
      var dt = Math.min(Math.max(rawDt, 0.008), 0.065);
      elapsedTotal += dt;
      var time = elapsedTotal;

      // --- 1. CASHIER BARCODE SCANNING ANIMATION ---
      scanCycleTimer += dt;
      if (scanCycleTimer >= scanCycleDuration) {
        scanCycleTimer = scanCycleTimer % scanCycleDuration;
        lastBeepTriggered = false;
        activeScanIdx = (activeScanIdx + 1) % POS_ITEMS.length;
        drawPosScreen(activeScanIdx, false);
      }
      var scanPhase = scanCycleTimer / scanCycleDuration;
      var isLaserFiring = (scanPhase > 0.30 && scanPhase < 0.74);
      var isJustScannedBeep = (scanPhase >= 0.54 && scanPhase <= 0.80);

      if (scanPhase >= 0.54 && !lastBeepTriggered) {
        lastBeepTriggered = true;
        drawPosScreen(activeScanIdx, true);
      }

      for (var bp = 0; bp < beltProducts.length; bp++) {
        var prodMesh = beltProducts[bp];
        var slotOffset = ((bp - activeScanIdx + beltProducts.length) % beltProducts.length);
        var targetBeltX;
        if (slotOffset === 0) {
          targetBeltX = -0.20 - scanPhase * 0.65;
        } else {
          targetBeltX = -0.20 + slotOffset * 0.34 - scanPhase * 0.34;
          if (targetBeltX > 0.35) targetBeltX -= 1.75;
        }
        prodMesh.position.x = targetBeltX;
        prodMesh.position.y = (slotOffset === 0 && isLaserFiring) ? 1.40 : 1.36;
      }

      cashierGroup.position.y = Math.sin(time * 4.0) * 0.015;
      cHeadGroup.rotation.y = Math.sin(scanPhase * Math.PI * 2) * 0.22;
      cHeadGroup.rotation.x = isLaserFiring ? 0.25 : 0.12;

      cArmLGroup.rotation.x = isLaserFiring ? -0.35 : -0.10;
      cArmLGroup.rotation.z = Math.sin(scanPhase * Math.PI * 2) * 0.18;
      cArmRGroup.rotation.x = -0.15 + Math.sin(scanPhase * Math.PI * 2) * 0.35;

      laserBeamMesh.visible = isLaserFiring;
      laserLineMesh.visible = isLaserFiring;
      scanFlashRing.visible = isJustScannedBeep;
      if (isJustScannedBeep) {
        var pulseScale = 0.8 + (scanPhase - 0.54) * 2.5;
        scanFlashRing.scale.set(pulseScale, pulseScale, 1);
      }

      receiptProgress = (receiptProgress + dt * 0.55) % 1;
      var slideH = 0.40 + receiptProgress * 0.36;
      receiptPaper.position.y = slideH;
      receiptPaper.rotation.x = -0.20 - receiptProgress * 0.22;

      // --- 2. REALISTIC HUMAN SHOPPERS ANIMATION (ຍ່າງອ້ອມຊັ້ນວາງສິນຄ້າ ບໍ່ທະລຸບັອກ 100%) ---
      shoppers.forEach(function(s) {
        if (s.state === 'SHOPPING') {
          s.heldItem.visible = false;
          s.headGroup.rotation.y = 0;

          var arrived = stepShopperAlongPath(s, s.speed, dt, 13.5);
          if (arrived) {
            s.state = 'BROWSING';
            s.browseTimer = 0;
            s.legL.rotation.x = 0;
            s.legR.rotation.x = 0;
            s.group.position.y = 0;
            if (typeof s.target.lookX === 'number') {
              var ldx = s.target.lookX - s.group.position.x;
              var ldz = s.target.lookZ - s.group.position.z;
              s.group.rotation.y = Math.atan2(ldx, ldz);
            }
          }
        } else if (s.state === 'BROWSING') {
          s.browseTimer += dt;
          var bProg = Math.min(1, s.browseTimer / s.browseMax);

          s.headGroup.rotation.y = Math.sin(bProg * Math.PI * 3) * 0.35;

          if (bProg > 0.22 && bProg < 0.65) {
            s.armLGroup.rotation.x = -1.25;
            s.armLGroup.rotation.y = 0;
            s.heldItem.visible = (bProg > 0.38);
          } else if (bProg >= 0.65 && bProg < 0.88) {
            s.armLGroup.rotation.x = -0.65;
            s.armLGroup.rotation.y = 0.55;
            s.heldItem.visible = true;
          } else {
            s.armLGroup.rotation.x = 0;
            s.armLGroup.rotation.y = 0;
            s.heldItem.visible = false;
          }

          if (s.browseTimer >= s.browseMax) {
            s.heldItem.visible = false;
            s.armLGroup.rotation.x = 0;
            s.armLGroup.rotation.y = 0;
            s.headGroup.rotation.y = 0;
            s.shopStepsLeft--;
            if (s.shopStepsLeft <= 0 && queueShoppers.length < WAYPOINTS.queue.length) {
              s.state = 'TO_QUEUE';
              s.queueIndex = queueShoppers.length;
              queueShoppers.push(s);
              s.target = WAYPOINTS.queue[s.queueIndex];
              s.path = buildWalkPath(s.group.position.x, s.group.position.z, s.target);
            } else {
              var nextPt = WAYPOINTS.aisles[Math.floor(Math.random() * WAYPOINTS.aisles.length)];
              s.target = {
                x: nextPt.x,
                z: nextPt.z + (Math.random() - 0.5) * 0.25,
                laneX: nextPt.laneX,
                lookX: nextPt.lookX,
                lookZ: nextPt.lookZ
              };
              s.path = buildWalkPath(s.group.position.x, s.group.position.z, s.target);
              s.state = 'SHOPPING';
            }
          }
        } else if (s.state === 'TO_QUEUE') {
          var qTarget = WAYPOINTS.queue[s.queueIndex] || WAYPOINTS.queue[0];
          s.target = qTarget;
          var qArrived = stepShopperAlongPath(s, s.speed, dt, 13.0);

          if (qArrived) {
            s.legL.rotation.x = 0;
            s.legR.rotation.x = 0;
            s.armLGroup.rotation.x = 0;
            s.group.position.y = 0;
            s.group.rotation.y = Math.PI;

            if (s.queueIndex === 0) {
              s.state = 'PAYING';
              s.payTimer = 0;
            } else {
              s.state = 'IN_QUEUE';
            }
          }
        } else if (s.state === 'IN_QUEUE') {
          s.group.position.y = Math.sin(time * 3.5 + s.walkCycle) * 0.02;
          var myTarget = WAYPOINTS.queue[s.queueIndex];
          if (myTarget) {
            var diffX = myTarget.x - s.group.position.x;
            var diffZ = myTarget.z - s.group.position.z;
            var lerpFactor = Math.min(1, dt * 4.5);
            if (Math.abs(diffX) > 0.05 || Math.abs(diffZ) > 0.05) {
              s.group.position.x += diffX * lerpFactor;
              s.group.position.z += diffZ * lerpFactor;
              pushOutOfObstacles(s.group.position);
            }
            if (s.queueIndex === 0) {
              s.state = 'PAYING';
              s.payTimer = 0;
            }
          }
        } else if (s.state === 'PAYING') {
          s.payTimer = (s.payTimer || 0) + dt;
          s.armLGroup.rotation.x = -0.65 + Math.sin(s.payTimer * 8.0) * 0.35;
          coinMesh.visible = true;
          coinMesh.position.set(4.50, 2.35 + Math.sin(time * 7) * 0.14, 1.85);
          coinMesh.rotation.z += dt * 5.0;

          if (s.payTimer >= 1.55) {
            s.armLGroup.rotation.x = 0;
            coinMesh.visible = false;
            queueShoppers.shift();
            for (var q = 0; q < queueShoppers.length; q++) {
              queueShoppers[q].queueIndex = q;
              if (q === 0) queueShoppers[q].target = WAYPOINTS.queue[0];
            }
            s.state = 'LEAVING';
            s.target = WAYPOINTS.exit;
            s.path = [
              { x: 1.85, z: 2.60 },
              WAYPOINTS.exit
            ];
          }
        } else if (s.state === 'LEAVING') {
          var eArrived = stepShopperAlongPath(s, s.speed * 1.08, dt, 14.0);
          if (eArrived) {
            s.shopStepsLeft = 2 + Math.floor(Math.random() * 3);
            var newShelf = WAYPOINTS.aisles[Math.floor(Math.random() * WAYPOINTS.aisles.length)];
            s.target = {
              x: newShelf.x,
              z: newShelf.z + (Math.random() - 0.5) * 0.25,
              laneX: newShelf.laneX,
              lookX: newShelf.lookX,
              lookZ: newShelf.lookZ
            };
            s.path = buildWalkPath( s.group.position.x, s.group.position.z, s.target );
            s.state = 'SHOPPING';
          }
        }
      });

      renderer.render(scene, camera);
    }

    function savePosterSnapshot() {
      try {
        updateSize();
        renderer.render(scene, camera);
        var posterUrl = renderer.domElement.toDataURL('image/jpeg', 0.82);
        if (posterUrl && posterUrl.length > 100) {
          localStorage.setItem('StoreMini.mm3d_poster_v5', posterUrl);
          container.style.backgroundImage = 'url(' + posterUrl + ')';
        }
      } catch(e) {}
    }

    // ແຕ້ມພາບ 3D ທັນທີທີ່ 0ms (Synchronous First Render) ກ່ອນທີ່ Browser ຈະ Paint ໜ້າຈໍ ເພື່ອບໍ່ໃຫ້ເກີດຈໍຂາວເດັດຂາດ!
    updateSize();
    renderer.render(scene, camera);
    setTimeout(savePosterSnapshot, 350);

    animate();

    window.minimart3dCelebrate = function() {
      coinMesh.visible = true;
      setTimeout(function() { coinMesh.visible = false; }, 2500);
    };
  }

  // ເອີ້ນໃຊ້ທັນທີແບບ 0ms (Synchronous) ໂດຍບໍ່ລໍຖ້າ DOMContentLoaded ຫຼື requestAnimationFrame
  initMinimart3D();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMinimart3D);
  }
  window.addEventListener('load', initMinimart3D);
})();
</script>
