<?php
/**
 * ໄຟລ໌ຍ່ອຍ JS: ລະບົບອັບໂຫຼດຮູບຜົນງານຫຼາຍຮູບພ້ອມກັນ (ແບບ Facebook) + ໂລໂກ້ລູກຄ້າ
 * ຖືກ require ຈາກ backend/pages/web_works/index.php.
 * ຮອງຮັບ: ເລືອກຫຼາຍຮູບພ້ອມກັນ, ກົດ "+ ເພີ່ມຮູບອີກ" ສະສົມຮູບຕໍ່ເນື່ອງ, ລາກ-ວາງ (Drag & Drop), ວາງຮູບ (Ctrl+V),
 * ເລືອກຮູບໜ້າປົກ, ລຶບຮູບຍ່ອຍກ່ອນໂພສ, ຫຍໍ້ຮູບໃຫຍ່ອັດຕະໂນມັດ ແລະ ແບ່ງສົ່ງເທື່ອລະຊຸດພ້ອມແຖບ % ເພື່ອບໍ່ໃຫ້ຕິດຈຳກັດໄຟລ໌ຂອງ PHP.
 */
?>
<script>
(function () {
  var logoInput = document.getElementById('wwLogoInput');
  var logoPick = document.getElementById('wwLogoPick');
  var logoPreview = document.getElementById('wwLogoPreview');
  var logoPlaceholder = document.getElementById('wwLogoPlaceholder');
  var logoClear = document.getElementById('wwLogoClear');
  var removeLogoFlag = document.getElementById('wwRemoveLogo');

  var photosInput = document.getElementById('wwPhotosInput');
  var dropzone = document.getElementById('wwDropzone');
  var previewGrid = document.getElementById('wwPreviewGrid');
  var photoCountBadge = document.getElementById('wwPhotoCountBadge');
  var form = document.getElementById('wwForm');
  var submitBtn = document.getElementById('wwSubmitBtn');
  var progressWrap = document.getElementById('wwProgressWrap');
  var progressBar = document.getElementById('wwProgressBar');
  var progressText = document.getElementById('wwProgressText');

  // ລາຍການຮູບທັງໝົດໃນຟອມ: { type: 'existing', name: '...', url: '...' } ຫຼື { type: 'new', file: File, url: 'blob:...' }
  var items = (window.WW_EXISTING_PHOTOS || []).map(function (p) {
    return { type: 'existing', name: p.name, url: p.url };
  });

  // --- 1. ໂລໂກ້ລູກຄ້າ ---
  if (logoPick && logoInput) {
    logoPick.addEventListener('click', function (e) {
      if (e.target.closest('#wwLogoClear')) return;
      logoInput.click();
    });
    logoInput.addEventListener('change', function () {
      var f = logoInput.files && logoInput.files[0];
      if (!f) return;
      removeLogoFlag.value = '0';
      logoPreview.src = URL.createObjectURL(f);
      logoPreview.hidden = false;
      logoPlaceholder.hidden = true;
      logoClear.hidden = false;
    });
    logoClear.addEventListener('click', function (e) {
      e.stopPropagation();
      logoInput.value = '';
      removeLogoFlag.value = '1';
      logoPreview.src = '';
      logoPreview.hidden = true;
      logoPlaceholder.hidden = false;
      logoClear.hidden = true;
    });
  }

  // --- 2. ວາດຕາຕະລາງຕົວຢ່າງຮູບຜົນງານ (ແບບ Facebook) ---
  function renderPreviews() {
    previewGrid.innerHTML = '';
    if (!items.length) {
      previewGrid.hidden = true;
      photoCountBadge.textContent = 'ຍັງບໍ່ໄດ້ເລືອກຮູບ';
      photoCountBadge.className = 'badge badge-secondary';
      return;
    }
    previewGrid.hidden = false;
    photoCountBadge.textContent = 'ທັງໝົດ ' + items.length + ' ຮູບ';
    photoCountBadge.className = 'badge badge-primary';

    items.forEach(function (it, idx) {
      var cell = document.createElement('div');
      cell.className = 'ww-prev-item' + (idx === 0 ? ' is-cover' : '');
      var img = document.createElement('img');
      img.src = it.url;
      cell.appendChild(img);

      var b = document.createElement('span');
      b.className = 'ww-prev-badge';
      b.innerHTML = idx === 0 ? '<i class="fas fa-star"></i> #1 ຮູບໜ້າປົກ' : ('#' + (idx + 1));
      cell.appendChild(b);

      if (idx > 0) {
        var coverBtn = document.createElement('button');
        coverBtn.type = 'button';
        coverBtn.className = 'ww-prev-cover-btn';
        coverBtn.innerHTML = '<i class="fas fa-star"></i> ຕັ້ງເປັນ #1';
        coverBtn.addEventListener('click', function () {
          var picked = items.splice(idx, 1)[0];
          picked.promotedCover = true;
          items.unshift(picked);
          renderPreviews();
        });
        cell.appendChild(coverBtn);
      }

      var del = document.createElement('button');
      del.type = 'button';
      del.className = 'ww-prev-del';
      del.title = 'ເອົາຮູບນີ້ອອກ';
      del.innerHTML = '&times;';
      del.addEventListener('click', function () {
        items.splice(idx, 1);
        renderPreviews();
      });
      cell.appendChild(del);
      previewGrid.appendChild(cell);
    });

    // ປຸ່ມ "+ ເພີ່ມຮູບອີກ" ຢູ່ຊ່ອງສຸດທ້າຍ ຄືກັບ Facebook
    var addMore = document.createElement('button');
    addMore.type = 'button';
    addMore.className = 'ww-prev-add';
    addMore.innerHTML = '<i class="fas fa-plus-circle mb-1" style="font-size:1.35rem;"></i><span>+ ເພີ່ມຮູບອີກ</span>';
    addMore.addEventListener('click', function () { photosInput.click(); });
    previewGrid.appendChild(addMore);
  }

  function addFiles(fileList) {
    Array.from(fileList || []).forEach(function (f) {
      if (!f || !/^image\/(jpeg|png|webp|gif)$/i.test(f.type)) return;
      items.push({ type: 'new', file: f, url: URL.createObjectURL(f) });
    });
    renderPreviews();
  }

  if (dropzone && photosInput) {
    dropzone.addEventListener('click', function () { photosInput.click(); });
    photosInput.addEventListener('change', function () {
      if (photosInput.files && photosInput.files.length) {
        addFiles(photosInput.files);
        photosInput.value = '';
      }
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
      dropzone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      dropzone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dropzone.classList.remove('is-dragover'); });
    });
    dropzone.addEventListener('drop', function (e) {
      if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
    });
    // ວາງຮູບດ້ວຍ Ctrl+V
    document.addEventListener('paste', function (e) {
      if (!e.clipboardData || !e.clipboardData.files || !e.clipboardData.files.length) return;
      addFiles(e.clipboardData.files);
    });
  }

  renderPreviews();

  // --- 3. ຫຍໍ້ຮູບຜົນງານທີ່ໃຫຍ່ເກີນ 1600px ໃຫ້ອັບໂຫຼດໄວ (ໂລໂກ້ບໍ່ຫຍໍ້ ເພື່ອຮັກສາພື້ນໂປ່ງໃສ PNG) ---
  function compressPhoto(file) {
    return new Promise(function (resolve) {
      if (file.size <= 700 * 1024) { resolve(file); return; }
      var img = new Image();
      var u = URL.createObjectURL(file);
      img.onload = function () {
        URL.revokeObjectURL(u);
        var maxSide = 1600;
        var w = img.naturalWidth || img.width, h = img.naturalHeight || img.height;
        if (w <= maxSide && h <= maxSide && file.size <= 1400 * 1024) { resolve(file); return; }
        var scale = Math.min(1, maxSide / Math.max(w, h));
        var nw = Math.max(1, Math.round(w * scale)), nh = Math.max(1, Math.round(h * scale));
        var c = document.createElement('canvas');
        c.width = nw; c.height = nh;
        c.getContext('2d').drawImage(img, 0, 0, nw, nh);
        c.toBlob(function (blob) {
          if (!blob) { resolve(file); return; }
          var base = (file.name || 'photo').replace(/\.[^.]+$/, '');
          resolve(new File([blob], base + '.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.88);
      };
      img.onerror = function () { URL.revokeObjectURL(u); resolve(file); };
      img.src = u;
    });
  }

  // --- 4. ບັນທຶກໂພສ + ອັບໂຫຼດຮູບຫຼາຍຮູບພ້ອມກັນ (ແບ່ງສົ່ງເທື່ອລະຊຸດ 8 ຮູບ ຖ້າມີຫຼາຍຮູບ) ---
  if (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var shopName = (form.querySelector('[name="shop_name"]').value || '').trim();
      if (!shopName) {
        Swal.fire({ icon: 'warning', text: 'ກະລຸນາປ້ອນຊື່ຮ້ານລູກຄ້າ' });
        return;
      }

      submitBtn.disabled = true;
      progressWrap.hidden = false;
      progressBar.style.width = '10%';
      progressText.textContent = 'ກຳລັງກຽມຮູບພາບ...';

      try {
        var newFiles = [];
        for (var i = 0; i < items.length; i++) {
          if (items[i].type === 'new') {
            progressText.textContent = 'ກຳລັງກຽມຮູບທີ ' + (newFiles.length + 1) + '...';
            newFiles.push(await compressPhoto(items[i].file));
          }
        }

        // ຊຸດທຳອິດສົ່ງພ້ອມຂໍ້ມູນຮ້ານ ແລະ ໂລໂກ້ (ສູງສຸດ 8 ຮູບຕໍ່ຄັ້ງ ເພື່ອບໍ່ໃຫ້ເກີນ max_file_uploads ຂອງ PHP)
        var chunkSize = 8;
        var firstBatch = newFiles.slice(0, chunkSize);
        var restBatches = [];
        for (var j = chunkSize; j < newFiles.length; j += chunkSize) {
          restBatches.push(newFiles.slice(j, j + chunkSize));
        }

        var fd = new FormData(form);
        fd.delete('work_photos[]');
        fd.append('ajax', '1');
        // ລຳດັບຮູບເກົ່າທີ່ຍັງເຫຼືອ
        var keepExisting = items.filter(function (it) { return it.type === 'existing'; }).map(function (it) { return it.name; });
        fd.set('keep_photos_json', JSON.stringify(keepExisting));
        // ຖ້າຜູ້ໃຊ້ກົດປຸ່ມ "ຕັ້ງເປັນ #1" ໃສ່ຮູບໃໝ່
        fd.set('new_cover_first', (items.length && items[0].type === 'new' && items[0].promotedCover) ? '1' : '0');
        firstBatch.forEach(function (f) { fd.append('work_photos[]', f, f.name); });

        progressBar.style.width = restBatches.length ? '35%' : '75%';
        progressText.textContent = 'ກຳລັງອັບໂຫຼດຂໍ້ມູນ ແລະ ຮູບພາບ...';

        var res = await fetch('index.php', { method: 'POST', body: fd });
        var data = await res.json();
        if (!data || !data.success) {
          throw new Error((data && data.message) || 'ບັນທຶກບໍ່ສຳເລັດ');
        }

        var workId = data.work_id;
        for (var b = 0; b < restBatches.length; b++) {
          var pct = Math.round(35 + ((b + 1) / restBatches.length) * 60);
          progressBar.style.width = pct + '%';
          progressText.textContent = 'ກຳລັງອັບໂຫຼດຮູບຊຸດທີ ' + (b + 2) + '/' + (restBatches.length + 1) + '...';
          var afd = new FormData();
          afd.append('action', 'append_photos');
          afd.append('ajax', '1');
          afd.append('work_id', workId);
          restBatches[b].forEach(function (f) { afd.append('work_photos[]', f, f.name); });
          var ares = await fetch('index.php', { method: 'POST', body: afd });
          var adata = await ares.json();
          if (!adata || !adata.success) {
            throw new Error((adata && adata.message) || 'ອັບໂຫຼດຮູບບາງສ່ວນບໍ່ສຳເລັດ');
          }
        }

        progressBar.style.width = '100%';
        progressText.textContent = 'ອັບໂຫຼດສຳເລັດ!';
        window.location.href = 'index.php?saved=1';
      } catch (err) {
        submitBtn.disabled = false;
        progressWrap.hidden = true;
        Swal.fire({ icon: 'error', text: err.message || 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫຼດ' });
      }
    });
  }

  // --- 5. ລາຍການຜົນງານ: ຄົ້ນຫາສົດ / ໝວດ / ແບ່ງໜ້າ (ດຶງ ?ajax=1 ໂດຍບໍ່ໂຫຼດໜ້າ) + ເປີດ/ປິດແຖບຮູບທັງໝົດ ---
  var listForm = document.getElementById('wwFilter');
  var listRows = document.getElementById('wwRows');
  var listPager = document.getElementById('wwPager');
  if (listForm && listRows) {
    var pageInput = listForm.querySelector('[name=page]');
    var timer = null, reqNo = 0;
    var loadList = function () {
      var qs = new URLSearchParams(new FormData(listForm)).toString();
      var my = ++reqNo;
      listRows.style.opacity = '.5';
      fetch('index.php?ajax=1&' + qs, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (my !== reqNo) return;
          listRows.innerHTML = d.rows;
          listPager.innerHTML = d.pager;
          pageInput.value = d.page;
          // ຮັກສາ ?edit= ໄວ້ ຖ້າກຳລັງແກ້ໄຂ; ຟອມລຶບ (ບໍ່ມີ action) ຈະ POST ໄປ URL ນີ້ → ກັບມາໜ້າເກົ່າ
          var params = new URLSearchParams(new FormData(listForm));
          var edit = new URLSearchParams(location.search).get('edit');
          if (edit) params.set('edit', edit);
          history.replaceState(null, '', 'index.php?' + params.toString());
        })
        .catch(function () {})
        .then(function () { if (my === reqNo) listRows.style.opacity = ''; });
    };
    listForm.querySelector('[name=q]').addEventListener('input', function () { clearTimeout(timer); pageInput.value = 1; timer = setTimeout(loadList, 300); });
    listForm.querySelector('[name=cat]').addEventListener('change', function () { pageInput.value = 1; loadList(); });
    listPager.addEventListener('click', function (e) {
      var a = e.target.closest('[data-page]');
      if (!a) return;
      e.preventDefault();
      if (a.parentNode.classList.contains('disabled') || a.parentNode.classList.contains('active')) return;
      pageInput.value = a.getAttribute('data-page');
      loadList();
    });
    listRows.addEventListener('click', function (e) {
      // ຖ້າກົດຮູບນ້ອຍໃນແຖວເທິງ (ww-thumb) ຂະນະທີ່ແຖວຮູບເປີດຢູ່ແລ້ວ → ເປີດ Lightbox ທັນທີ
      var th = e.target.closest('.ww-thumb[data-thumb-index]');
      var t = e.target.closest('[data-toggle-photos]');
      if (t) {
        var wid = t.getAttribute('data-toggle-photos');
        var panel = document.getElementById('wwAll' + wid);
        if (th && panel && !panel.hidden) {
          var row = th.closest('.ww-row');
          if (row) {
            try {
              var photos = JSON.parse(row.getAttribute('data-photos') || '[]');
              var shop = row.getAttribute('data-shop') || '';
              var idx = parseInt(th.getAttribute('data-thumb-index') || '0', 10);
              openGallery(photos, idx, shop);
              return;
            } catch (err) {}
          }
        }
        if (panel) { panel.hidden = !panel.hidden; t.classList.toggle('is-open', !panel.hidden); }
      }
    });
  }

  // --- 6. ປຸ່ມ "ເພີ່ມຮູບ" ດ່ວນໃນແຕ່ລະແຖວ (ເລືອກຫຼາຍຮູບແລ້ວອັບໂຫຼດເຂົ້າຜົນງານທັນທີ) — delegation ເພາະແຖວຖືກແທນເມື່ອຄົ້ນຫາ ---
  document.addEventListener('change', async function (ev) {
      var inp = ev.target.closest && ev.target.closest('[data-quick-add-input]');
      if (!inp) return;
      var files = Array.from(inp.files || []);
      if (!files.length) return;
      var wid = inp.getAttribute('data-quick-add-input');
      Swal.fire({ title: 'ກຳລັງອັບໂຫຼດ ' + files.length + ' ຮູບ...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
      try {
        var compressed = [];
        for (var i = 0; i < files.length; i++) {
          compressed.push(await compressPhoto(files[i]));
        }
        for (var j = 0; j < compressed.length; j += 8) {
          var chunk = compressed.slice(j, j + 8);
          var fd = new FormData();
          fd.append('action', 'append_photos');
          fd.append('ajax', '1');
          fd.append('work_id', wid);
          chunk.forEach(function (f) { fd.append('work_photos[]', f, f.name); });
          var r = await fetch('index.php', { method: 'POST', body: fd });
          var d = await r.json();
          if (!d || !d.success) throw new Error((d && d.message) || 'ອັບໂຫຼດບໍ່ສຳເລັດ');
        }
        var back = new URLSearchParams(location.search);
        back.set('saved', '1');
        window.location.href = 'index.php?' + back.toString();   // ກັບມາໜ້າ / ຄຳຄົ້ນຫາເກົ່າ
      } catch (err) {
        Swal.fire({ icon: 'error', text: err.message || 'ອັບໂຫຼດບໍ່ສຳເລັດ' });
      }
  });

  // --- 7. Lightbox Gallery ສໍາລັບເບິ່ງຮູບຜົນງານ: ຄລິກເບິ່ງຮູບ + ປຸ່ມປິດ + ປຸ່ມເລື່ອນ (ຖ້າ 2 ຮູບຂຶ້ນໄປ) ---
  var galleryModal = document.getElementById('wwGalleryModal');
  var galleryImg = document.getElementById('wwGalleryImg');
  var galleryShop = document.getElementById('wwGalleryShop');
  var galleryCounter = document.getElementById('wwGalleryCounter');
  var galleryClose = document.getElementById('wwGalleryClose');
  var galleryPrev = document.getElementById('wwGalleryPrev');
  var galleryNext = document.getElementById('wwGalleryNext');
  var gallerySpinner = document.getElementById('wwGallerySpinner');
  var galleryThumbsWrap = document.getElementById('wwGalleryThumbsWrap');
  var galleryThumbs = document.getElementById('wwGalleryThumbs');

  var curGalleryPhotos = [];
  var curGalleryIndex = 0;
  var curGalleryShop = '';

  function openGallery(photos, index, shopName) {
    if (!photos || !photos.length) return;
    curGalleryPhotos = photos;
    curGalleryIndex = Math.max(0, Math.min(index || 0, photos.length - 1));
    curGalleryShop = shopName || '';

    if (galleryModal) {
      galleryModal.style.display = 'flex';
      document.body.classList.add('ww-gallery-open');
      renderGallerySlide();
    }
  }

  function closeGallery() {
    if (!galleryModal) return;
    galleryModal.style.display = 'none';
    document.body.classList.remove('ww-gallery-open');
    if (galleryImg) galleryImg.src = '';
  }

  function renderGallerySlide() {
    if (!curGalleryPhotos.length) return;
    var total = curGalleryPhotos.length;
    var item = curGalleryPhotos[curGalleryIndex];
    if (!item) return;

    if (galleryShop) galleryShop.textContent = curGalleryShop;
    if (galleryCounter) galleryCounter.textContent = (curGalleryIndex + 1) + ' / ' + total;

    // ເງື່ອນໄຂ: ຖ້າ 2 ຮູບຂຶ້ນໄປ ຈຶ່ງສະແດງປຸ່ມເລື່ອນ (Prev / Next) ແລະ ແຖບຮູບນ້ອຍ
    if (total >= 2) {
      if (galleryPrev) galleryPrev.style.display = 'grid';
      if (galleryNext) galleryNext.style.display = 'grid';
      if (galleryThumbsWrap) galleryThumbsWrap.style.display = 'flex';
    } else {
      if (galleryPrev) galleryPrev.style.display = 'none';
      if (galleryNext) galleryNext.style.display = 'none';
      if (galleryThumbsWrap) galleryThumbsWrap.style.display = 'none';
    }

    if (gallerySpinner) gallerySpinner.style.display = 'block';
    if (galleryImg) {
      galleryImg.style.opacity = '0.35';
      var tempImg = new Image();
      tempImg.onload = function () {
        galleryImg.src = item.full;
        galleryImg.style.opacity = '1';
        if (gallerySpinner) gallerySpinner.style.display = 'none';
      };
      tempImg.onerror = function () {
        galleryImg.src = item.full;
        galleryImg.style.opacity = '1';
        if (gallerySpinner) gallerySpinner.style.display = 'none';
      };
      tempImg.src = item.full;
    }

    // ແຖບເລືອກຮູບນ້ອຍດ້ານລຸ່ມ
    if (galleryThumbs && total >= 2) {
      galleryThumbs.innerHTML = '';
      curGalleryPhotos.forEach(function (p, i) {
        var th = document.createElement('div');
        th.className = 'ww-gallery-thumb' + (i === curGalleryIndex ? ' active' : '');
        th.innerHTML = '<img src="' + (p.thumb || p.full) + '" alt="">';
        th.addEventListener('click', function () {
          curGalleryIndex = i;
          renderGallerySlide();
        });
        galleryThumbs.appendChild(th);
      });
      var activeTh = galleryThumbs.querySelector('.active');
      if (activeTh) activeTh.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
  }

  function nextGallerySlide() {
    if (curGalleryPhotos.length < 2) return;
    curGalleryIndex = (curGalleryIndex + 1) % curGalleryPhotos.length;
    renderGallerySlide();
  }

  function prevGallerySlide() {
    if (curGalleryPhotos.length < 2) return;
    curGalleryIndex = (curGalleryIndex - 1 + curGalleryPhotos.length) % curGalleryPhotos.length;
    renderGallerySlide();
  }

  if (galleryClose) galleryClose.addEventListener('click', closeGallery);
  if (galleryNext) galleryNext.addEventListener('click', nextGallerySlide);
  if (galleryPrev) galleryPrev.addEventListener('click', prevGallerySlide);

  if (galleryModal) {
    galleryModal.addEventListener('click', function (e) {
      if (e.target.classList.contains('ww-gallery-backdrop') || e.target.classList.contains('ww-gallery-stage')) {
        closeGallery();
      }
    });
  }

  window.addEventListener('keydown', function (e) {
    if (!galleryModal || galleryModal.style.display === 'none') return;
    if (e.key === 'Escape') {
      closeGallery();
    } else if (e.key === 'ArrowRight') {
      nextGallerySlide();
    } else if (e.key === 'ArrowLeft') {
      prevGallerySlide();
    }
  });

  // Swipe ຮອງຮັບການເລື່ອນເທິງໜ້າຈໍສຳຜັດ (Mobile / Tablet)
  var touchStartX = 0;
  var touchEndX = 0;
  if (galleryModal) {
    galleryModal.addEventListener('touchstart', function (e) {
      touchStartX = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].screenX : 0;
    }, { passive: true });
    galleryModal.addEventListener('touchend', function (e) {
      touchEndX = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].screenX : 0;
      if (touchEndX < touchStartX - 50) {
        nextGallerySlide();
      } else if (touchEndX > touchStartX + 50) {
        prevGallerySlide();
      }
    }, { passive: true });
  }

  // Delegation: ຄລິກຮູບຜົນງານໃນ .ww-row-photo ເພື່ອເປີດ Lightbox
  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('.ww-photo-link');
    if (!link) return;
    e.preventDefault();
    e.stopPropagation();
    var row = link.closest('.ww-row');
    if (!row) return;
    try {
      var photos = JSON.parse(row.getAttribute('data-photos') || '[]');
      var shop = row.getAttribute('data-shop') || '';
      var idx = parseInt(link.getAttribute('data-index') || '0', 10);
      openGallery(photos, idx, shop);
    } catch (err) {
      console.error(err);
    }
  });
})();
</script>
