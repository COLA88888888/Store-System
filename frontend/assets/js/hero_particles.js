/**
 * ເອັບເຟັກ Particles.js ພື້ນຫຼັງ hero ໜ້າຫຼັກ (#heroParticles ໃນ components/hero.php) — ຈຸດສີຟ້າ/ເຫຼືອງ + ເສັ້ນເຊື່ອມ,
 * ເອົາເມົາໄປຖືກ = ດຶງເສັ້ນຈາກເມົາຫາຈຸດໃກ້ (grab), ກົດ = ເພີ່ມຈຸດ (push).
 * ຫ້ອງສະໝຸດ: assets/vendor/particles.min.js (particles.js 2.0.0, MIT — Vincent Garreau).
 * canvas ຢູ່ຫຼັງເນື້ອຫາ (pointer-events: none) → ສົ່ງຕຳແໜ່ງເມົາຈາກ section ເຂົ້າ pJS ເອງ
 * (ໂໝດ 'window' ຂອງ particles.js ຄິດຕຳແໜ່ງຜິດ ເມື່ອ canvas ບໍ່ຢູ່ມຸມຊ້າຍເທິງຂອງຈໍ).
 * ກັບໄປໃຊ້ໂລໂກ້ລອຍເກົ່າ: ປ່ຽນ $pageScripts ໃນ index.php ເປັນ js/logo_net.js ແລະ ໃສ່ canvas [data-logo-net] ຄືນ.
 */
(function () {
  var el = document.getElementById('heroParticles');
  if (!el || typeof particlesJS !== 'function') return;
  var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var small = window.innerWidth < 768;

  particlesJS('heroParticles', {
    particles: {
      number: { value: small ? 35 : 80, density: { enable: true, value_area: 900 } },
      color: { value: ['#1d4ed8', '#3b82f6', '#f59e0b', '#7c3aed'] },
      shape: { type: 'circle' },
      opacity: { value: .55, random: true, anim: { enable: !still, speed: .6, opacity_min: .25, sync: false } },
      size: { value: 3.2, random: true, anim: { enable: false } },
      line_linked: { enable: true, distance: 140, color: '#1d4ed8', opacity: .22, width: 1 },
      move: { enable: !still, speed: 1.4, direction: 'none', random: false, straight: false, out_mode: 'out', bounce: false }
    },
    interactivity: {
      detect_on: 'canvas',
      events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
      modes: { grab: { distance: 170, line_linked: { opacity: .55 } }, push: { particles_nb: 4 } }
    },
    retina_detect: true
  });

  var inst = window.pJSDom && window.pJSDom[window.pJSDom.length - 1];
  var pJS = inst && inst.pJS;
  var host = el.parentElement;
  if (!pJS || !host) return;

  function pos(e) {
    var r = pJS.canvas.el.getBoundingClientRect(), k = pJS.canvas.pxratio || 1;
    return { x: (e.clientX - r.left) * k, y: (e.clientY - r.top) * k };
  }
  host.addEventListener('pointermove', function (e) {
    var p = pos(e);
    pJS.interactivity.mouse.pos_x = p.x;
    pJS.interactivity.mouse.pos_y = p.y;
    pJS.interactivity.status = 'mousemove';
  }, { passive: true });
  host.addEventListener('pointerleave', function () {
    pJS.interactivity.mouse.pos_x = null;
    pJS.interactivity.mouse.pos_y = null;
    pJS.interactivity.status = 'mouseleave';
  });
  // ກົດບ່ອນວ່າງ (ບໍ່ແມ່ນຊ່ອງຄົ້ນຫາ / ລິ້ງ / ປຸ່ມ) → ເພີ່ມຈຸດບ່ອນທີ່ກົດ
  host.addEventListener('click', function (e) {
    if (e.target.closest('a, button, input, form, label, [data-live-search]')) return;
    var p = pos(e);
    if (pJS.particles.array.length < 160) {
      pJS.fn.modes.pushParticles(pJS.interactivity.modes.push.particles_nb, { pos_x: p.x, pos_y: p.y });
    }
  });
})();
