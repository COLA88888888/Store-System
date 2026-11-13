<?php
/*
 * ສະແກນບາໂຄ້ດດ້ວຍກ້ອງມືຖື — ກົດປຸ່ມກ້ອງ (#btnCameraScan) ຢູ່ຂວາມືຂອງບັອກຄົ້ນຫາ
 * ໂຫຼດຈາກ pages/pos/partials/pos_js.php ຕໍ່ຈາກ pos_barcode_js.php (ໃຊ້ findAndAddByBarcode,
 * _executeFilterGrid, barcodeShowingLastScan ຂອງໄຟລ໌ນັ້ນ).
 * CSS: themes/pos/06-camera-scan.css
 *
 * ການອ່ານບາໂຄ້ດ (ເລືອກອັດຕະໂນມັດ ຕາມລຳດັບຄວາມໄວ):
 *   1. BarcodeDetector ຂອງເຄື່ອງ (Android Chrome) — ອ່ານທຸກເຟຣມ ໄວທີ່ສຸດ, ບໍ່ຕ້ອງໂຫຼດໄລບຣາຣີ
 *   2. plugins/html5-qrcode/html5-qrcode.min.js (v2.3.8) — iPhone/ບຣາວເຊີອື່ນ (ໂຫຼດລ່ວງໜ້າໃນມືຖື)
 *   3. ເວັບເປີດຜ່ານ http://IP (ບຣາວເຊີບໍ່ໃຫ້ເປີດກ້ອງສົດ) → ເປີດແອັບກ້ອງຖ່າຍຮູບ ແລ້ວອ່ານຈາກຮູບ (scanPhoto)
 * ອ່ານໄດ້ປຸ໊ບ → ປິດກ້ອງ ແລະ ເພີ່ມສິນຄ້າເຂົ້າກະຕ່າທັນທີ (ບໍ່ລໍຖ້າກ້ອງປິດ).
 */
?>
<script>
(function() {
  var LIB_URL = '<?php echo $base_path ?? '../../'; ?>plugins/html5-qrcode/html5-qrcode.min.js';
  var WANTED = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'code_93', 'itf', 'codabar', 'qr_code'];
  var VIDEO_CONSTRAINTS = {
    facingMode: { ideal: 'environment' },
    width: { ideal: 1280 }, height: { ideal: 720 },
    advanced: [{ focusMode: 'continuous' }]
  };
  var libPromise = null;
  var scanner = null;      // html5-qrcode instance
  var nativeCam = null;    // { stream, video, raf } ຂອງ BarcodeDetector
  var nativeDetector = null;
  var overlay = null;
  var handled = false;

  function loadLib() {
    if (window.Html5Qrcode) return Promise.resolve();
    if (libPromise) return libPromise;
    libPromise = new Promise(function(resolve, reject) {
      var s = document.createElement('script');
      s.src = LIB_URL;
      s.onload = function() { resolve(); };
      s.onerror = function() { libPromise = null; reject(new Error('<?php echo tlj('ໂຫຼດໄຟລ໌ສະແກນບໍ່ໄດ້'); ?> (plugins/html5-qrcode)')); };
      document.head.appendChild(s);
    });
    return libPromise;
  }

  // ຕົວອ່ານຂອງເຄື່ອງເອງ (BarcodeDetector) — null ຖ້າເຄື່ອງບໍ່ຮອງຮັບ
  var nativeReady = (function() {
    if (!('BarcodeDetector' in window)) return Promise.resolve(null);
    return Promise.resolve().then(function() {
      return BarcodeDetector.getSupportedFormats ? BarcodeDetector.getSupportedFormats() : WANTED;
    }).then(function(sup) {
      var fmts = WANTED.filter(function(f) { return sup.indexOf(f) !== -1; });
      if (!fmts.length) return null;
      nativeDetector = new BarcodeDetector({ formats: fmts });
      return nativeDetector;
    }).catch(function() { return null; });
  })();

  // ມືຖືທີ່ບໍ່ມີ BarcodeDetector → ໂຫຼດໄລບຣາຣີໄວ້ກ່ອນ ໃຫ້ກົດກ້ອງແລ້ວເປີດໄດ້ທັນທີ
  window.addEventListener('load', function() {
    if (window.innerWidth >= 768) return;
    nativeReady.then(function(d) { if (!d) setTimeout(function() { loadLib().catch(function() {}); }, 1500); });
  });

  function buildOverlay() {
    if (overlay) return overlay;
    overlay = document.createElement('div');
    overlay.className = 'pos-cam-overlay';
    overlay.id = 'posCameraOverlay';
    overlay.style.display = 'none';
    overlay.innerHTML =
      '<div class="pos-cam-header">' +
        '<span class="pos-cam-title"><i class="fas fa-camera mr-2"></i><?php echo tlj('ສະແກນບາໂຄ້ດດ້ວຍກ້ອງ'); ?></span>' +
        '<button type="button" class="pos-cam-close" id="posCameraClose" aria-label="close"><i class="fas fa-times"></i></button>' +
      '</div>' +
      '<div class="pos-cam-body">' +
        '<div class="pos-cam-status" id="posCameraStatus"><?php echo tlj('ກຳລັງເປີດກ້ອງ...'); ?></div>' +
        '<div id="posCameraReader"></div>' +
        '<div class="pos-cam-guide" id="posCameraGuide"><span class="pos-cam-line"></span></div>' +
      '</div>' +
      '<div class="pos-cam-footer"><?php echo tlj('ຫັນກ້ອງໃສ່ບາໂຄ້ດໃຫ້ຢູ່ໃນກອບ'); ?></div>';
    document.body.appendChild(overlay);
    // ບໍ່ໃຫ້ຕົວຈັບຄລິກຂອງໜ້າ POS ດຶງໂຟກັສກັບໄປຊ່ອງສະແກນ ຂະນະເປີດກ້ອງ
    overlay.addEventListener('click', function(e) { e.stopPropagation(); });
    overlay.querySelector('#posCameraClose').addEventListener('click', function() { closePosCameraScanner(); });
    return overlay;
  }

  function setStatus(text) {
    var el = document.getElementById('posCameraStatus');
    if (el) { el.textContent = text || ''; el.style.display = text ? '' : 'none'; }
  }
  function showGuide(on) {
    var g = document.getElementById('posCameraGuide');
    if (g) g.style.display = on ? '' : 'none';
  }

  function stopScanner() {
    if (nativeCam) {
      var n = nativeCam;
      nativeCam = null;
      if (n.raf) cancelAnimationFrame(n.raf);
      try { n.stream && n.stream.getTracks().forEach(function(t) { t.stop(); }); } catch (e) {}
      try { n.video.srcObject = null; n.video.remove(); } catch (e) {}
    }
    var s = scanner;
    scanner = null;
    if (!s) return Promise.resolve();
    var p;
    try { p = s.isScanning ? s.stop() : Promise.resolve(); } catch (e) { p = Promise.resolve(); }
    return p.catch(function() {}).then(function() { try { s.clear(); } catch (e) {} });
  }

  function showCameraError(title, text) {
    Swal.fire({
      icon: 'warning',
      title: title,
      html: '<div style="font-size:0.95rem;">' + text + '</div>',
      confirmButtonText: '<i class="fas fa-check mr-1"></i> <?php echo tlj('ຕົກລົງ'); ?>',
      confirmButtonColor: '#f59e0b'
    });
  }

  function applyScannedCode(code) {
    try { if (navigator.vibrate) navigator.vibrate(60); } catch (e) {}
    var input = document.getElementById('barcodeInput');
    if (input) input.value = code;
    barcodeShowingLastScan = true;
    findAndAddByBarcode(code);
    _executeFilterGrid();
  }

  function onScanSuccess(decodedText) {
    if (handled) return;
    var code = String(decodedText || '').trim();
    if (!code) return;
    handled = true;
    closePosCameraScanner();   // ເຊື່ອງທັນທີ, ປິດກ້ອງຢູ່ເບື້ອງຫຼັງ
    applyScannedCode(code);    // ສິນຄ້າຂຶ້ນກະຕ່າເລີຍ ບໍ່ລໍຖ້າກ້ອງປິດ
  }

  // ---- 1. BarcodeDetector ຂອງເຄື່ອງ: ອ່ານທຸກເຟຣມ ----
  function startNative(detector) {
    return navigator.mediaDevices.getUserMedia({ video: VIDEO_CONSTRAINTS, audio: false }).then(function(stream) {
      if (overlay.style.display === 'none') { stream.getTracks().forEach(function(t) { t.stop(); }); return; }
      var video = document.createElement('video');
      video.setAttribute('playsinline', '');
      video.muted = true;
      video.autoplay = true;
      video.srcObject = stream;
      video.className = 'pos-cam-video';
      document.getElementById('posCameraReader').appendChild(video);
      var cam = nativeCam = { stream: stream, video: video, raf: 0 };
      var busy = false;
      function tick() {
        if (nativeCam !== cam) return;
        if (!busy && video.readyState >= 2) {
          busy = true;
          detector.detect(video).then(function(codes) {
            busy = false;
            if (codes && codes.length && nativeCam === cam) onScanSuccess(codes[0].rawValue);
          }, function() { busy = false; });
        }
        cam.raf = requestAnimationFrame(tick);
      }
      return video.play().catch(function() {}).then(function() {
        setStatus('');
        showGuide(true);
        tick();
      });
    });
  }

  // ---- 2. html5-qrcode (ປັບໃຫ້ໄວ: fps ສູງ, ບໍ່ກັບຮູບ, ໂຟກັສອັດຕະໂນມັດ) ----
  function startLib() {
    return loadLib().then(function() {
      if (overlay.style.display === 'none') return;
      var F = window.Html5QrcodeSupportedFormats;
      var s = scanner = new Html5Qrcode('posCameraReader', {
        formatsToSupport: F ? [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.QR_CODE] : undefined,
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
        verbose: false
      });
      return s.start(
        { facingMode: 'environment' },
        {
          fps: 25,
          disableFlip: true,
          videoConstraints: VIDEO_CONSTRAINTS,
          qrbox: function(w, h) {
            return { width: Math.max(120, Math.floor(w * 0.82)), height: Math.max(80, Math.floor(Math.min(w, h) * 0.42)) };
          }
        },
        onScanSuccess,
        function() {} // ຍັງບໍ່ພົບບາໂຄ້ດໃນເຟຣມນີ້
      ).then(function() {
        setStatus('');
        // ເສັ້ນແດງເລື່ອນລົງ ພາຍໃນກອບສະແກນຂອງໄລບຣາຣີ
        var region = document.getElementById('qr-shaded-region');
        if (region && !region.querySelector('.pos-cam-line')) {
          var line = document.createElement('span');
          line.className = 'pos-cam-line';
          region.appendChild(line);
        }
        // ຜູ້ໃຊ້ກົດປິດ ຂະນະກ້ອງຍັງເປີດບໍ່ທັນ → ປິດກ້ອງທີ່ຫາກໍເປີດ
        if (scanner !== s) { s.stop().catch(function() {}).then(function() { try { s.clear(); } catch (e) {} }); }
      });
    });
  }

  // ---- 3. ທາງສຳຮອງ http://: ເປີດແອັບກ້ອງໂທລະສັບຖ່າຍຮູບ ແລ້ວອ່ານບາໂຄ້ດຈາກຮູບ ----
  var fileInput = null;
  function getFileInput() {
    if (fileInput) return fileInput;
    fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.setAttribute('capture', 'environment');
    fileInput.style.display = 'none';
    fileInput.addEventListener('change', function() {
      var f = fileInput.files && fileInput.files[0];
      fileInput.value = '';
      if (f) scanPhoto(f);
    });
    document.body.appendChild(fileInput);
    return fileInput;
  }

  // ຮູບຈາກກ້ອງໂທລະສັບໃຫຍ່ຫຼາຍ (12MP+) → ຫຍໍ້ລົງກ່ອນ ໃຫ້ອ່ານບາໂຄ້ດໄວ ແລະ ແມ່ນກວ່າ
  function shrinkImage(file, maxSide) {
    return new Promise(function(resolve) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function() {
        var scale = Math.min(1, maxSide / Math.max(img.naturalWidth, img.naturalHeight));
        if (scale >= 1) { URL.revokeObjectURL(url); resolve(file); return; }
        var c = document.createElement('canvas');
        c.width = Math.round(img.naturalWidth * scale);
        c.height = Math.round(img.naturalHeight * scale);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(function(b) {
          resolve(b ? new File([b], 'scan.jpg', { type: 'image/jpeg' }) : file);
        }, 'image/jpeg', 0.92);
      };
      img.onerror = function() { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  }

  function decodeFile(file) {
    // ມີ BarcodeDetector → ອ່ານຈາກຮູບໂດຍກົງ (ໄວກວ່າ)
    if (nativeDetector && window.createImageBitmap) {
      return createImageBitmap(file).then(function(bmp) { return nativeDetector.detect(bmp); }).then(function(codes) {
        if (codes && codes.length) return codes[0].rawValue;
        throw new Error('notfound');
      });
    }
    return loadLib().then(function() {
      var holder = document.getElementById('posCameraFileReader');
      if (!holder) {
        holder = document.createElement('div');
        holder.id = 'posCameraFileReader';
        holder.style.display = 'none';
        document.body.appendChild(holder);
      }
      var F = window.Html5QrcodeSupportedFormats;
      var q = new Html5Qrcode('posCameraFileReader', {
        formatsToSupport: F ? [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.CODE_39, F.CODE_93,
                               F.ITF, F.CODABAR, F.QR_CODE] : undefined,
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
        verbose: false
      });
      return q.scanFile(file, false).finally(function() { try { q.clear(); } catch (e) {} });
    });
  }

  function scanPhoto(file) {
    Swal.fire({
      title: '<?php echo tlj('ກຳລັງອ່ານບາໂຄ້ດ...'); ?>',
      allowOutsideClick: false, showConfirmButton: false,
      didOpen: function() { Swal.showLoading(); }
    });
    nativeReady.then(function() {
      // ລອງຮູບຫຍໍ້ກ່ອນ, ອ່ານບໍ່ອອກ → ລອງຂະໜາດໃຫຍ່ຂຶ້ນ
      return shrinkImage(file, 1280).then(decodeFile).catch(function() {
        return shrinkImage(file, 2048).then(decodeFile);
      });
    }).then(function(text) {
      var code = String(text || '').trim();
      Swal.close();
      if (code) applyScannedCode(code);
    }).catch(function() {
      Swal.fire({
        icon: 'warning',
        title: '<?php echo tlj('ອ່ານບາໂຄ້ດບໍ່ອອກ'); ?>',
        html: '<div style="font-size:0.95rem;"><?php echo tlj('ກະລຸນາຖ່າຍໃໝ່ ໃຫ້ບາໂຄ້ດຢູ່ກາງຮູບ ຊັດ ແລະ ບໍ່ສະທ້ອນແສງ'); ?></div>',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-camera mr-1"></i> <?php echo tlj('ຖ່າຍໃໝ່'); ?>',
        cancelButtonText: '<?php echo tlj('ປິດ'); ?>',
        confirmButtonColor: '#244886'
      }).then(function(r) { if (r.isConfirmed) getFileInput().click(); });
    });
  }

  window.openPosCameraScanner = function() {
    try { openScannerInner(); } catch (err) {
      // ບໍ່ໃຫ້ກົດແລ້ວງຽບ — ສະແດງສາເຫດອອກມາ
      showCameraError('<?php echo tlj('ເປີດກ້ອງບໍ່ໄດ້'); ?>', $('<div>').text(String((err && err.message) || err)).html());
    }
  };

  function openScannerInner() {
    if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      getFileInput().click(); // ຕ້ອງເອີ້ນທັນທີໃນການກົດ ບໍ່ດັ່ງນັ້ນບຣາວເຊີບລັອກ
      if (!('BarcodeDetector' in window)) loadLib().catch(function() {}); // ໂຫຼດລ່ວງໜ້າ ລະຫວ່າງຖ່າຍຮູບ
      return;
    }
    if (scanner || nativeCam) return;
    handled = false;
    var el = buildOverlay();
    el.style.display = 'flex';
    showGuide(false);
    setStatus('<?php echo tlj('ກຳລັງເປີດກ້ອງ...'); ?>');
    try { document.activeElement && document.activeElement.blur(); } catch (e) {}

    nativeReady.then(function(detector) {
      if (el.style.display === 'none') return;
      return detector ? startNative(detector) : startLib();
    }).catch(function(err) {
      var msg = String((err && (err.name || err.message)) || err || '');
      closePosCameraScanner();
      if (/NotAllowed|Permission|denied/i.test(msg)) {
        showCameraError('<?php echo tlj('ບໍ່ໄດ້ຮັບອະນຸຍາດໃຫ້ໃຊ້ກ້ອງ'); ?>',
          '<?php echo tlj('ກະລຸນາອະນຸຍາດໃຫ້ເວັບນີ້ໃຊ້ກ້ອງ ໃນການຕັ້ງຄ່າບຣາວເຊີ ແລ້ວລອງໃໝ່'); ?>');
      } else if (/NotFound|OverconstrainedError|no camera/i.test(msg)) {
        showCameraError('<?php echo tlj('ບໍ່ພົບກ້ອງ'); ?>', '<?php echo tlj('ເຄື່ອງນີ້ບໍ່ມີກ້ອງ ຫຼື ກ້ອງກຳລັງຖືກໃຊ້ໂດຍແອັບອື່ນ'); ?>');
      } else {
        showCameraError('<?php echo tlj('ເປີດກ້ອງບໍ່ໄດ້'); ?>', $('<div>').text(msg).html());
      }
    });
  }

  window.closePosCameraScanner = function() {
    if (overlay) overlay.style.display = 'none';
    return stopScanner();
  };

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && overlay && overlay.style.display !== 'none') closePosCameraScanner();
  });
  // ອອກຈາກໜ້າ/ສະຫຼັບແອັບ → ປິດກ້ອງ
  document.addEventListener('visibilitychange', function() {
    if (document.hidden && (scanner || nativeCam)) closePosCameraScanner();
  });
  window.addEventListener('pagehide', function() { if (scanner || nativeCam) closePosCameraScanner(); });
})();
</script>
