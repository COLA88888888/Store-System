<?php
/*
 * ສ່ວນ: ພະນັກງານເຄົ້າເຕີ + ປ້າຍຊື່ເທິງຫົວ + ລະບົບຍ່າງເຂົ້າ/ຍ່າງອອກ Real-Time (ສາກ 3D ຈໍລູກຄ້າ)
 * - ສະແດງສະເພາະ "ຊື່ທີ່ລັອກອິນ" ເທິງຫົວພະນັກງານເທົ່ານັ້ນ (ບໍ່ໃສ່ຄຳວ່າ ປະຈຳການ ຫຼື ຄຳອື່ນໆ).
 * - ເມື່ອພະນັກງານເຂົ້າ (Login): ຍ່າງເຂົ້າມາຈາກປະຕູໜ້າຮ້ານ ພ້ອມຊື່ລັອກອິນ, ໂບກມື, ຍ່າງໄປປະຈຳການຢູ່ເຄົ້າເຕີ.
 * - ເມື່ອພະນັກງານອອກ (Logout): ຍ່າງອອກຈາກເຄົ້າເຕີ ຜ່ານປະຕູອອກໄປນອກຮ້ານ ແລ້ວຫາຍຕົວເລີຍ.
 * ໄຟລ໌ນີ້ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ໃນ pages/pos/partials/customer_display/minimart_3d.php.
 */
$__cdNames = [];
try { if (isset($pdo) && function_exists('cdOnlineCashierNames')) $__cdNames = cdOnlineCashierNames($pdo, getPosStoreId()); } catch (Throwable $e) {}
?>
    // ===== ປ້າຍຊື່ເທິງຫົວ (Sprite ທີ່ແຕ້ມຈາກ canvas — ຫັນໜ້າຫາກ້ອງສະເໝີ) =====
    var CD_TAG_FONT = '"Nuanta", "Noto Sans Lao", "Phetsarath OT", "Saysettha OT", sans-serif';
    var cdAllTags = [];

    function cdDrawTag(tag) {
      var c = tag.canvas, ctx = c.getContext('2d'), fs = 46;
      var font = 'bold ' + fs + 'px ' + CD_TAG_FONT;
      ctx.font = font;

      var displayText = String(tag.text || '').trim();
      var textW = Math.ceil(ctx.measureText(displayText).width);
      var w = Math.max(150, textW + 54);
      var h = fs + 24;

      c.width = w;
      c.height = h;
      ctx.font = font;

      var r = h / 2;
      ctx.beginPath();
      ctx.moveTo(r, 3);
      ctx.lineTo(w - r, 3);
      ctx.arc(w - r, r, r - 3, -Math.PI / 2, Math.PI / 2);
      ctx.lineTo(r, h - 3);
      ctx.arc(r, r, r - 3, Math.PI / 2, Math.PI * 1.5);
      ctx.closePath();

      ctx.shadowColor = 'rgba(0, 0, 0, 0.40)';
      ctx.shadowBlur = 10;
      ctx.shadowOffsetY = 4;

      ctx.fillStyle = tag.bg || 'rgba(37, 99, 235, 0.95)';
      ctx.fill();

      ctx.shadowColor = 'transparent';
      ctx.lineWidth = tag.borderWidth || 4;
      ctx.strokeStyle = tag.borderColor || 'rgba(255, 255, 255, 0.95)';
      ctx.stroke();

      ctx.fillStyle = '#ffffff';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(displayText, w / 2, h / 2 + 2);

      tag.tex.needsUpdate = true;
      tag.sprite.scale.set(tag.worldH * w / h, tag.worldH, 1);
      tag.baseScaleW = tag.worldH * w / h;
      tag.baseScaleH = tag.worldH;
    }

    function cdMakeTag(text, bg, worldH, y, isCashier) {
      var canvas = document.createElement('canvas');
      var tex = new THREE.CanvasTexture(canvas);
      tex.minFilter = THREE.LinearFilter;
      var sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
      sprite.position.set(0, y, 0);
      var tag = {
        canvas: canvas, tex: tex, sprite: sprite,
        text: text, bg: bg, worldH: worldH, isCashier: !!isCashier,
        baseScaleW: worldH, baseScaleH: worldH,
        borderColor: isCashier ? 'rgba(255, 255, 255, 0.98)' : 'rgba(255, 255, 255, 0.90)',
        borderWidth: isCashier ? 4.5 : 3.5
      };
      cdDrawTag(tag);
      cdAllTags.push(tag);
      return tag;
    }

    function cdSetTagText(tag, text) {
      if (tag.text === text) return;
      tag.text = text;
      cdDrawTag(tag);
    }

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () { cdAllTags.forEach(cdDrawTag); });
    }

    // ===== ລູກຄ້າ: "ລູກຄ້າທົ່ວໄປ" =====
    function cdShopperLabel() { return CD_MM_T['cd.general_customer'] || 'ລູກຄ້າທົ່ວໄປ'; }
    shoppers.forEach(function (s) {
      s.tag = cdMakeTag(cdShopperLabel(), 'rgba(71, 85, 105, 0.88)', 0.40, 2.08, false);
      s.group.add(s.tag.sprite);
    });
    function cdRelabelShoppers() {
      shoppers.forEach(function (s) { if (s.tag) cdSetTagText(s.tag, cdShopperLabel()); });
    }

    // ===== ລະບົບພະນັກງານເຄົ້າເຕີ: ຍ່າງເຂົ້າ/ຍ່າງອອກ Real-Time =====
    var CD_CASHIER_X = [4.50, 5.72, 3.30, 6.70];
    var cdCashiers = [];
    var cdKnownNames = [];

    var mainCashier = {
      group: cashierGroup,
      head: cHeadGroup,
      armL: cArmLGroup,
      armR: cArmRGroup,
      legL: cashierGroup.getObjectByName('legL') || cLegL,
      legR: cashierGroup.getObjectByName('legR') || cLegR,
      gun: scannerGunGroup,
      laser: laserBeamMesh,
      laserLine: laserLineMesh,
      tag: null,
      name: '',
      state: 'STATIONED',
      targetX: CD_CASHIER_X[0],
      targetZ: 0.20,
      path: [],
      walkCycle: 0,
      arrivedTimer: 0,
      walkSpeed: 3.05,
      main: true
    };
    cdCashiers.push(mainCashier);

    function cdBuildCashierWalkPath(targetX) {
      return [
        { x: -7.20, z: 1.25 },
        { x: -3.50, z: 1.25 },
        { x: 1.85,  z: 1.25 },
        { x: 1.85,  z: 0.20 },
        { x: targetX, z: 0.20 }
      ];
    }

    function cdBuildCashierWalkOutPath(currentX) {
      return [
        { x: 1.85,  z: 0.20 },
        { x: 1.85,  z: 1.25 },
        { x: -3.50, z: 1.25 },
        { x: -7.05, z: 1.25 },
        { x: -9.50, z: 1.25 }
      ];
    }

    function cdCloneCashier(i, name) {
      var g = cashierGroup.clone(true);
      var head = g.getObjectByName('head') || g.children[0];
      var armL = g.getObjectByName('armL');
      var armR = g.getObjectByName('armR');
      var legL = g.getObjectByName('legL');
      var legR = g.getObjectByName('legR');
      var gun  = g.getObjectByName('scannerGun');
      var laser = g.getObjectByName('laserBeam');
      var laserLine = g.getObjectByName('laserLine');

      var sprites = [];
      g.traverse(function (o) {
        if (o.isMesh && (o.material === laserBeamMat || (o.material && o.material.color && o.material.color.getHex() === 0xff0000))) {
          o.visible = false;
        }
        if (o.isSprite) sprites.push(o);
      });
      sprites.forEach(function (s) { if (s.parent) s.parent.remove(s); });

      if (laser) laser.visible = false;
      if (laserLine) laserLine.visible = false;
      if (gun) gun.visible = false;

      var tx = CD_CASHIER_X[i] || 4.50;
      g.position.set(tx, 0, 0.20);
      storeGroup.add(g);

      return {
        group: g, head: head, armL: armL, armR: armR,
        legL: legL, legR: legR, gun: gun, laser: laser, laserLine: laserLine,
        tag: null, name: name || '', state: 'STATIONED',
        targetX: tx, targetZ: 0.20, path: [],
        walkCycle: 0, arrivedTimer: 0, walkSpeed: 3.05, main: false
      };
    }

    // ພະນັກງານຍ່າງເຂົ້າມາຈາກປະຕູຮ້ານຫາເຄົ້າເຕີ: ສະແດງສະເພາະ "ຊື່ທີ່ລັອກອິນ"
    function cdTriggerCashierWalkIn(name, data) {
      if (!name) return;
      name = String(name).trim();
      if (!name) return;

      var targetObj = null;
      var slotIdx = -1;

      for (var i = 0; i < cdCashiers.length; i++) {
        if (cdCashiers[i].name === name) {
          targetObj = cdCashiers[i];
          slotIdx = i;
          break;
        }
      }

      if (!targetObj) {
        for (var j = 0; j < cdCashiers.length; j++) {
          if (!cdCashiers[j].name || cdCashiers[j].state === 'IDLE') {
            targetObj = cdCashiers[j];
            slotIdx = j;
            break;
          }
        }
      }

      if (!targetObj) {
        slotIdx = cdCashiers.length;
        if (slotIdx < CD_CASHIER_X.length) {
          targetObj = cdCloneCashier(slotIdx, name);
          cdCashiers.push(targetObj);
        } else {
          slotIdx = 0;
          targetObj = cdCashiers[0];
        }
      }

      targetObj.name = name;
      targetObj.targetX = CD_CASHIER_X[slotIdx] || 4.50;
      targetObj.targetZ = 0.20;

      var path = cdBuildCashierWalkPath(targetObj.targetX);
      targetObj.path = path.slice(1);
      targetObj.state = 'WALKING_IN';
      targetObj.walkCycle = 0;
      targetObj.arrivedTimer = 0;

      var startPt = path[0];
      targetObj.group.position.set(startPt.x, 0, startPt.z);
      targetObj.group.visible = true;
      targetObj.group.rotation.y = Math.PI / 2;

      if (targetObj.legL) targetObj.legL.rotation.x = 0;
      if (targetObj.legR) targetObj.legR.rotation.x = 0;
      if (targetObj.laser) targetObj.laser.visible = false;
      if (targetObj.laserLine) targetObj.laserLine.visible = false;

      // ປ້າຍຊື່ເທິງຫົວ: ໃສ່ສະເພາະ "ຊື່ທີ່ລັອກອິນ" ເທົ່ານັ້ນ ບໍ່ໃສ່ອັນອື່ນ
      if (!targetObj.tag) {
        targetObj.tag = cdMakeTag(name, 'rgba(37, 99, 235, 0.95)', 0.48, 2.75, true);
        targetObj.group.add(targetObj.tag.sprite);
      } else {
        targetObj.tag.bg = 'rgba(37, 99, 235, 0.95)';
        cdSetTagText(targetObj.tag, name);
        targetObj.tag.sprite.visible = true;
      }

      if (cdKnownNames.indexOf(name) === -1) {
        cdKnownNames.push(name);
      }
    }

    // ພະນັກງານຍ່າງອອກຈາກເຄົ້າເຕີ ອອກໄປນອກຮ້ານ ແລ້ວຫາຍເລີຍ (Logout)
    function cdTriggerCashierWalkOut(name, data) {
      var targetObj = null;

      if (name) {
        name = String(name).trim();
        for (var i = 0; i < cdCashiers.length; i++) {
          if (cdCashiers[i].name === name) {
            targetObj = cdCashiers[i];
            break;
          }
        }
      }

      if (!targetObj) {
        for (var j = cdCashiers.length - 1; j >= 0; j--) {
          if (cdCashiers[j].state !== 'WALKING_OUT' && cdCashiers[j].name) {
            targetObj = cdCashiers[j];
            break;
          }
        }
      }

      if (!targetObj) targetObj = cdCashiers[0];
      if (!targetObj || targetObj.state === 'WALKING_OUT' || targetObj.state === 'IDLE') return;

      var departingName = targetObj.name || (name || '');

      if (targetObj.laser) targetObj.laser.visible = false;
      if (targetObj.laserLine) targetObj.laserLine.visible = false;
      if (scanFlashRing) scanFlashRing.visible = false;

      // ປ້າຍຊື່ຍັງຄົງເປັນ "ຊື່ທີ່ລັອກອິນ" ຈົນກວ່າຍ່າງອອກໄປນອກຮ້ານ
      if (targetObj.tag && departingName) {
        targetObj.tag.bg = 'rgba(71, 85, 105, 0.95)';
        cdSetTagText(targetObj.tag, departingName);
        targetObj.tag.sprite.visible = true;
      }

      targetObj.path = cdBuildCashierWalkOutPath(targetObj.group.position.x);
      targetObj.state = 'WALKING_OUT';
      targetObj.walkCycle = 0;

      var kIdx = cdKnownNames.indexOf(departingName);
      if (kIdx !== -1) cdKnownNames.splice(kIdx, 1);
    }

    function stepCashierAlongPath(c, moveSpeed, dt) {
      if (!c.path || c.path.length === 0) return true;
      var wp = c.path[0];
      var dx = wp.x - c.group.position.x;
      var dz = wp.z - c.group.position.z;
      var dist = Math.sqrt(dx * dx + dz * dz);
      var stepMove = moveSpeed * dt;

      if (dist <= Math.max(0.12, stepMove)) {
        c.group.position.x = wp.x;
        c.group.position.z = wp.z;
        c.path.shift();
        return (c.path.length === 0);
      }

      var targetAngle = Math.atan2(dx, dz);
      var diff = targetAngle - c.group.rotation.y;
      while (diff < -Math.PI) diff += Math.PI * 2;
      while (diff > Math.PI) diff -= Math.PI * 2;
      c.group.rotation.y += diff * Math.min(1.0, dt * 10.0);

      c.group.position.x += Math.sin(c.group.rotation.y) * stepMove;
      c.group.position.z += Math.cos(c.group.rotation.y) * stepMove;

      c.walkCycle += dt * 10.5;
      if (c.legL) c.legL.rotation.x = Math.sin(c.walkCycle) * 0.65;
      if (c.legR) c.legR.rotation.x = -Math.sin(c.walkCycle) * 0.65;
      if (c.armL) c.armL.rotation.x = -Math.sin(c.walkCycle) * 0.38;

      if (c.armR) {
        c.armR.rotation.x = -0.75 + Math.sin(c.walkCycle * 0.7) * 0.25;
        c.armR.rotation.z = 0.45 + Math.cos(c.walkCycle * 0.7) * 0.20;
      }

      c.group.position.y = Math.abs(Math.sin(c.walkCycle)) * 0.055;
      return false;
    }

    function cdAnimateCashiers(dt, time, scanPhase, isLaserFiring, isJustScannedBeep) {
      for (var i = 0; i < cdCashiers.length; i++) {
        var c = cdCashiers[i];

        if (c.state === 'WALKING_IN') {
          if (c.main) {
            if (laserBeamMesh) laserBeamMesh.visible = false;
            if (laserLineMesh) laserLineMesh.visible = false;
          }
          var reached = stepCashierAlongPath(c, c.walkSpeed, dt);
          if (reached) {
            c.state = 'ARRIVED';
            c.group.position.set(c.targetX, 0, c.targetZ);
            c.group.rotation.y = 0;
            if (c.legL) c.legL.rotation.x = 0;
            if (c.legR) c.legR.rotation.x = 0;
            c.group.position.y = 0;
            c.arrivedTimer = 0;
          }
        } else if (c.state === 'ARRIVED') {
          c.arrivedTimer += dt;
          var nod = Math.sin((c.arrivedTimer / 0.85) * Math.PI);
          c.head.rotation.x = nod * 0.28;
          if (c.armR) {
            c.armR.rotation.x = -0.15 * (1 - nod);
            c.armR.rotation.z = 0;
          }
          if (c.arrivedTimer >= 0.85) {
            c.state = 'STATIONED';
            c.head.rotation.x = 0;
            c.head.rotation.y = 0;
            if (c.tag && c.name) cdSetTagText(c.tag, c.name);
          }
        } else if (c.state === 'WALKING_OUT') {
          if (c.main) {
            if (laserBeamMesh) laserBeamMesh.visible = false;
            if (laserLineMesh) laserLineMesh.visible = false;
          }
          var outReached = stepCashierAlongPath(c, c.walkSpeed * 1.05, dt);
          if (outReached) {
            c.group.visible = false;
            c.state = 'IDLE';
            c.name = '';

            if (c.main) {
              var nextStationed = null;
              for (var k = 1; k < cdCashiers.length; k++) {
                if (cdCashiers[k].state === 'STATIONED') {
                  nextStationed = cdCashiers[k];
                  break;
                }
              }
              if (nextStationed) {
                nextStationed.main = true;
                nextStationed.targetX = CD_CASHIER_X[0];
                nextStationed.group.position.x = CD_CASHIER_X[0];
                if (nextStationed.gun) nextStationed.gun.visible = true;
              }
            } else {
              storeGroup.remove(c.group);
              cdCashiers.splice(i, 1);
              i--;
            }
          }
        } else {
          // STATIONED
          if (c.main) {
            cashierGroup.position.y = Math.sin(time * 4.0) * 0.015;
            cHeadGroup.rotation.y = Math.sin(scanPhase * Math.PI * 2) * 0.22;
            cHeadGroup.rotation.x = isLaserFiring ? 0.25 : 0.12;
            cArmLGroup.rotation.x = isLaserFiring ? -0.35 : -0.10;
            cArmLGroup.rotation.z = Math.sin(scanPhase * Math.PI * 2) * 0.18;
            cArmRGroup.rotation.x = -0.15 + Math.sin(scanPhase * Math.PI * 2) * 0.35;
            if (laserBeamMesh) laserBeamMesh.visible = isLaserFiring;
            if (laserLineMesh) laserLineMesh.visible = isLaserFiring;
          } else {
            c.group.position.y = Math.sin(time * 3.5 + i * 1.5) * 0.012;
            c.head.rotation.y = Math.sin(time * 0.8 + i * 2.1) * 0.32;
            if (c.legL) c.legL.rotation.x = 0;
            if (c.legR) c.legR.rotation.x = 0;
          }
        }
      }
    }

    // Sync ລາຍຊື່ພະນັກງານຈາກເຊີເວີ: ໃສ່ສະເພາະ "ຊື່ທີ່ລັອກອິນ"
    function cdSyncCashiersList(names) {
      names = (names || []).slice(0, CD_CASHIER_X.length);

      cdKnownNames.forEach(function (oldName) {
        if (names.indexOf(oldName) === -1) {
          cdTriggerCashierWalkOut(oldName);
        }
      });

      names.forEach(function (n, idx) {
        if (!n) return;
        if (cdKnownNames.indexOf(n) === -1) {
          cdTriggerCashierWalkIn(n);
          // ພະນັກງານລັອກອິນໃໝ່ (ລວມຈາກເຄື່ອງອື່ນ) → ກະດິ່ງປະຕູ; ຫາກໍດັງທີ່ໜ້າລັອກອິນໃນເຄື່ອງນີ້ → ບໍ່ດັງຊ້ຳ (pos-sounds.js)
          if (window.MiniposSounds) MiniposSounds.doorChime();
        } else {
          var cObj = cdCashiers[idx];
          if (cObj && cObj.state === 'STATIONED') {
            cObj.name = n;
            if (!cObj.tag) {
              cObj.tag = cdMakeTag(n, 'rgba(37, 99, 235, 0.95)', 0.48, 2.75, true);
              cObj.group.add(cObj.tag.sprite);
            } else {
              cdSetTagText(cObj.tag, n);
              cObj.tag.sprite.visible = true;
            }
          }
        }
      });
      cdKnownNames = names.slice();
    }

    window.cdTriggerCashierWalkIn = cdTriggerCashierWalkIn;
    window.cdTriggerCashierWalkOut = cdTriggerCashierWalkOut;
    window.cdTestCashierWalkIn = function(name) { cdTriggerCashierWalkIn(name || 'ສົມສັກ'); };
    window.cdTestCashierWalkOut = function(name) { cdTriggerCashierWalkOut(name); };

    var initialNames = <?php echo json_encode($__cdNames, JSON_UNESCAPED_UNICODE); ?> || [];
    if (initialNames.length > 0) {
      setTimeout(function () {
        initialNames.forEach(function (name, idx) {
          setTimeout(function () { cdTriggerCashierWalkIn(name); }, idx * 900);
        });
      }, 500);
    }

    setInterval(function () {
      fetch('customer_display.php?action=cashiers', { cache: 'no-store', credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && Array.isArray(res.names)) {
            cdSyncCashiersList(res.names);
          }
        })
        .catch(function () {});
    }, 3500);
