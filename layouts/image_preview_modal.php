<?php
/**
 * Global Image Lightbox Preview Modal
 * ສ່ວນສະແດງຮູບພາບຂະໜາດໃຫຍ່ (Modal Lightbox) ເມື່ອຄລິກຮູບສິນຄ້າ ຫຼື ຮູບຜູ້ນຳໃຊ້
 */
?>
<!-- GLOBAL IMAGE LIGHTBOX PREVIEW MODAL -->
<div id="globalImagePreviewModal" class="global-img-preview-backdrop" style="display:none;" aria-hidden="true">
  <button type="button" class="global-img-preview-close" aria-label="Close" title="ປິດ (ESC)">&times;</button>
  <div class="global-img-preview-wrap">
    <img id="globalImagePreviewTarget" class="global-img-preview-img" src="" alt="Image Preview">
    <div id="globalImagePreviewCaption" class="global-img-preview-caption" style="display:none;"></div>
  </div>
</div>

<style>
.global-img-preview-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.78);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  z-index: 9999999;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.22s ease, visibility 0.22s ease;
  padding: 24px;
  box-sizing: border-box;
  cursor: zoom-out;
}
.global-img-preview-backdrop.active {
  opacity: 1;
  visibility: visible;
}
.global-img-preview-wrap {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  max-width: 92vw;
  max-height: 90vh;
  cursor: default;
}
.global-img-preview-img {
  max-width: 88vw;
  max-height: 82vh;
  object-fit: contain;
  border-radius: 12px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.15);
  background: #0f172a;
  transform: scale(0.92);
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
  -webkit-user-drag: none;
  cursor: zoom-out;
}
.global-img-preview-backdrop.active .global-img-preview-img {
  transform: scale(1);
}
.global-img-preview-caption {
  margin-top: 14px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: #f8fafc;
  padding: 6px 20px;
  border-radius: 999px;
  font-size: 0.95rem;
  font-weight: 500;
  max-width: 85vw;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.15);
  text-align: center;
  letter-spacing: 0.2px;
  pointer-events: none;
}
.global-img-preview-close {
  position: fixed;
  top: 22px;
  right: 24px;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #ffffff;
  font-size: 28px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  z-index: 10000000;
  padding: 0;
  outline: none;
}
.global-img-preview-close:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: scale(1.08);
  color: #ffffff;
}
.previewable-image, img.previewable-img, [data-preview-img="true"] {
  cursor: pointer !important;
  transition: transform 0.15s ease, box-shadow 0.15s ease !important;
}
.previewable-image:hover, img.previewable-img:hover, [data-preview-img="true"]:hover {
  transform: scale(1.08) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}
</style>

<script>
(function() {
  window.viewImagePreview = function(src, title) {
    if (!src) return;
    // ຖ້າຢູ່ໃນ iframe ແລະ ໜ້າຫຼັກ top window ມີຟັງຊັນນີ້ → ສົ່ງໄປເປີດຢູ່ top window ເພື່ອໃຫ້ປົກຄຸມທົ່ວໜ້າຈໍ
    if (window.top && window.top !== window && typeof window.top.viewImagePreview === 'function') {
      try {
        var abs = new URL(src, window.location.href).href;
        window.top.viewImagePreview(abs, title);
        return;
      } catch (err) {}
    }
    var modal = document.getElementById('globalImagePreviewModal');
    if (!modal) return;
    var img = document.getElementById('globalImagePreviewTarget');
    var cap = document.getElementById('globalImagePreviewCaption');
    if (img) img.src = src;
    if (cap) {
      if (title && String(title).trim() !== '') {
        cap.textContent = title;
        cap.style.display = 'block';
      } else {
        cap.textContent = '';
        cap.style.display = 'none';
      }
    }
    modal.style.display = 'flex';
    setTimeout(function() {
      modal.classList.add('active');
    }, 10);
    document.body.classList.add('img-preview-open');
  };

  window.closeImagePreview = function() {
    if (window.top && window.top !== window && typeof window.top.closeImagePreview === 'function') {
      try { window.top.closeImagePreview(); } catch (e) {}
    }
    var modal = document.getElementById('globalImagePreviewModal');
    if (!modal) return;
    modal.classList.remove('active');
    setTimeout(function() {
      modal.style.display = 'none';
      var img = document.getElementById('globalImagePreviewTarget');
      if (img) img.src = '';
      document.body.classList.remove('img-preview-open');
    }, 220);
  };

  // ຄລິກພື້ນຫຼັງ / ປຸ່ມປິດ / ຕົວຮູບພາບ ເພື່ອປິດ
  document.addEventListener('click', function(e) {
    var modal = document.getElementById('globalImagePreviewModal');
    if (!modal || !modal.classList.contains('active')) return;
    if (e.target.closest('#globalImagePreviewCaption')) return;
    if (e.target === modal || e.target.closest('.global-img-preview-close') || e.target.id === 'globalImagePreviewTarget') {
      window.closeImagePreview();
    }
  });

  // ກົດປຸ່ມ ESC ເພື່ອປິດ
  window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      var modal = document.getElementById('globalImagePreviewModal');
      var isTop = (window.top && window.top !== window && window.top.document.getElementById('globalImagePreviewModal'));
      var topModal = isTop ? window.top.document.getElementById('globalImagePreviewModal') : null;

      if (topModal && topModal.classList.contains('active')) {
        e.preventDefault();
        e.stopPropagation();
        window.top.closeImagePreview();
        return;
      }
      if (modal && modal.classList.contains('active')) {
        e.preventDefault();
        e.stopPropagation();
        window.closeImagePreview();
        return;
      }
    }
  }, true);

  // ດັກຈັບການຄລິກຮູບພາບສິນຄ້າ ແລະ ຮູບຜູ້ນຳໃຊ້ອັດຕະໂນມັດ
  document.addEventListener('click', function(e) {
    var target = e.target;
    if (!target) return;
    var img = target.closest('.previewable-image, img[data-preview="true"], td img, #view_avatar');
    if (!img || img.tagName !== 'IMG') return;

    if (img.classList.contains('no-preview')) return;
    var rawSrc = img.src || img.getAttribute('src');
    if (!rawSrc) return;

    var isProd = rawSrc.indexOf('product_img') !== -1 || img.closest('.product-code-cell, td.text-center, #productTable');
    var isUser = rawSrc.indexOf('users') !== -1 || img.id === 'view_avatar' || img.closest('#usersTable');
    var isExplicit = img.classList.contains('previewable-image') || img.getAttribute('data-preview') === 'true';

    if (isExplicit || isProd || isUser) {
      if (img.id === 'edit_img_preview' || img.id === 'add_img_preview') return;
      e.preventDefault();
      e.stopPropagation();
      var title = img.getAttribute('data-preview-title') || img.getAttribute('alt') || '';
      if (!title) {
        var row = img.closest('tr');
        if (row) {
          var nameEl = row.querySelector('.product-name-cell, td:nth-child(4), td:nth-child(3)');
          if (nameEl) title = nameEl.textContent.trim();
        }
      }
      var abs = img.src;
      try { abs = new URL(rawSrc, window.location.href).href; } catch (err) {}
      window.viewImagePreview(abs, title);
    }
  }, true);
})();
</script>
