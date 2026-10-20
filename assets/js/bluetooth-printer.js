/*
 * bluetooth-printer.js — ພິມໃບບິນຜ່ານ Bluetooth (ບໍ່ຕ້ອງໃຊ້ສາຍ)
 * ໃຊ້ຂົວຕໍ່ Windows BLE ອັດຕະໂນມັດ (ble_bridge.php) + Web Bluetooth ເພື່ອພິມຕໍ່ເນື່ອງທຸກບິນໂດຍບໍ່ຕ້ອງເຊື່ອມຕໍ່ຫຼາຍຄັ້ງ.
 * ພິມເປັນຮູບດຳ-ຂາວ (ESC/POS raster "GS v 0") ໂຄງຮ່າງດຽວກັບເຄື່ອງພິມທົ່ວໄປ (assets/js/lan-receipt.js).
 */
(function (window) {
  'use strict';

  var VERSION = 12;
  var W = (function () { try { return window.top || window; } catch (e) { return window; } })();

  try {
    if (W !== window && W.MiniposBtPrinter) {
      if (W.MiniposBtPrinter.__v === VERSION) {
        window.MiniposBtPrinter = W.MiniposBtPrinter;
        return;
      }
      // ປິດໂຕເກົ່າທີ່ຄ້າງໃນໜ້າຫຼັກ (ຢຸດ keepAlive / retryTimer ເກົ່າ ແລະ ປ່ອຍສັນຍານ GATT ທີ່ຄ້າງ)
      try { W.MiniposBtPrinter.disconnect(); } catch (e) {}
      try { W.localStorage.removeItem('StoreMini.bt_manual_off'); } catch (e) {}
    }
  } catch (e) {}

  var Esc = W.MiniposEscPos || window.MiniposEscPos;
  var BLE_SERVICES = Esc.BLE_SERVICES;
  var STORE_KEY = 'StoreMini.bt_printer_device', LIST_KEY = 'StoreMini.bt_printers';
  var MANUAL_KEY = 'StoreMini.bt_manual_off', SPEED_KEY = 'StoreMini.bt_speed2';

  var BRIDGE_URL = (function () {
    var s = document.querySelector && document.querySelector('script[src*="bluetooth-printer.js"]');
    var src = (s && s.src) || '', i = src.indexOf('assets/js/bluetooth-printer.js');
    return (i >= 0 ? src.slice(0, i) : '/Project/StoreMini/') + 'pages/settings/printers/ble_bridge.php';
  })();

  var BLE_SIZES = [244, 180, 100], SPEEDS = { fast: 9500, normal: 9500, slow: 5000 }, CONNECT_TIMEOUT = 8000;
  var state = { mode: null, name: '', device: null, ch: null, noResp: false, bridge: false, port: null, writer: null, relinking: false, looping: false, restoreKey: '' };
  var listeners = [], retryTimer = null, busy = false, reconnecting = null;

  function isAndroid() { return /Android/i.test((W.navigator && W.navigator.userAgent) || ''); }
  function sendRawBt(bytes) {
    return new Promise(function (resolve) {
      var b64 = toBase64(bytes);
      var a = document.createElement('a');
      a.href = 'rawbt:base64,' + b64;
      a.style.display = 'none';
      document.body.appendChild(a);
      a.click();
      W.setTimeout(function () {
        if (a.parentNode) a.parentNode.removeChild(a);
        resolve(true);
      }, 150);
    });
  }

  function speedName() {
    var v = '';
    try { v = W.localStorage.getItem(SPEED_KEY) || ''; } catch (e) {}
    return SPEEDS[v] ? v : 'fast';
  }
  function isManualOff() { try { return W.localStorage.getItem(MANUAL_KEY) === '1'; } catch (e) { return false; } }
  function setManualOff(v) { try { if (v) W.localStorage.setItem(MANUAL_KEY, '1'); else W.localStorage.removeItem(MANUAL_KEY); } catch (e) {} }
  function isEnabledSetting() {
    try { var s = JSON.parse(W.localStorage.getItem('StoreMini.bt_printer_settings') || '{}') || {}; return s.enabled !== false; } catch (e) { return true; }
  }
  function emit(message) {
    var s = { connected: shownConnected(), name: state.name, mode: state.mode, message: message || '' };
    listeners.slice().forEach(function (fn) { try { fn(s); } catch (e) {} });
  }
  function sleep(ms) { return new Promise(function (r) { W.setTimeout(r, ms); }); }
  function withTimeout(p, ms, msg) {
    return Promise.race([p, new Promise(function (_, rej) { W.setTimeout(function () { rej(new Error(msg || 'timeout')); }, ms); })]);
  }

  // ---------- ລາຍຊື່ເຄື່ອງພິມທີ່ຕັ້ງໄວ້ ----------
  function loadList() {
    var list = [];
    try { list = JSON.parse(W.localStorage.getItem(LIST_KEY) || '[]') || []; } catch (e) { list = []; }
    if (!list.length) {
      try {
        var old = JSON.parse(W.localStorage.getItem(STORE_KEY) || 'null');
        if (old && old.mode) list = [{ key: old.mode + ':' + (old.id || old.name), mode: old.mode, name: old.name, id: old.id || '', last: 1 }];
      } catch (e) {}
    }
    return list.sort(function (a, b) { return (b.last || 0) - (a.last || 0); });
  }
  function saveList(list) { try { W.localStorage.setItem(LIST_KEY, JSON.stringify(list)); } catch (e) {} }
  function currentKey() {
    if (state.mode === 'ble' && state.device) return 'ble:' + state.device.id;
    if (state.mode === 'serial' && state.name) return 'serial:' + state.name;
    if (state.restoreKey) return state.restoreKey;
    var last = loadList()[0];
    return last ? last.key : '';
  }
  function remember() {
    var key = currentKey();
    if (!key) return;
    var list = loadList().filter(function (p) { return p.key !== key; });
    list.unshift({ key: key, mode: state.mode, name: state.name, id: state.device && state.mode === 'ble' ? state.device.id : '', last: Date.now() });
    saveList(list);
  }
  function remembered() { return loadList()[0] || null; }

  function nav() { return W.navigator || window.navigator; }
  function isSupported(mode) {
    if (isAndroid()) return true;
    var n = nav();
    if (mode === 'serial') return !!(n && n.serial);
    return !!(n && n.bluetooth);
  }
  function hasLiveBle() {
    return !!(state.mode === 'ble' && state.device && state.device.gatt && state.device.gatt.connected && state.ch);
  }
  function isConnected() {
    if (state.mode === 'rawbt') return !isManualOff();
    if (state.mode === 'ble') return hasLiveBle() || !!state.bridge;
    if (state.mode === 'serial') return !!(state.port && state.writer);
    return false;
  }
  function shownConnected() { return isConnected() || !!state.relinking; }

  function scheduleRetry(delay) {
    W.clearTimeout(retryTimer);
    if (isManualOff() || !isEnabledSetting() || !loadList().length) { state.looping = false; return; }
    state.looping = true;
    retryTimer = W.setTimeout(function () {
      state.looping = false;
      if (!isConnected() && !isManualOff() && isEnabledSetting()) reconnect();
    }, delay || 3000);
  }

  var findWritable = function (server) { return (W.MiniposEscPos || Esc).findWritable(server); };

  function attachBle(device, fallbackName) {
    device.removeEventListener('gattserverdisconnected', onBleDrop);
    device.addEventListener('gattserverdisconnected', onBleDrop);
    return device.gatt.connect().then(function (server) {
      if (device.__btSvcUuid && device.__btCharUuid) {
        return server.getPrimaryService(device.__btSvcUuid)
          .then(function (svc) { return svc.getCharacteristic(device.__btCharUuid); })
          .catch(function () { return findWritable(server); });
      }
      return findWritable(server);
    }).then(function (ch) {
      try {
        if (ch && ch.service && ch.service.uuid) device.__btSvcUuid = ch.service.uuid;
        if (ch && ch.uuid) device.__btCharUuid = ch.uuid;
      } catch (e) {}
      state.mode = 'ble';
      state.device = device;
      state.name = device.name || fallbackName || state.name || 'Bluetooth Printer';
      state.ch = ch;
      state.bridge = true;
      state.noResp = !!ch.properties.writeWithoutResponse;
      state.chunkIdx = 0;
      W.clearTimeout(retryTimer);
      state.relinking = false; state.looping = false; state.restoreKey = 'ble:' + device.id;
      remember();
      emit('ເຊື່ອມຕໍ່ແລ້ວ');
      return state.name;
    });
  }

  function onBleDrop(ev) {
    var dev = ev && ev.target;
    if (dev && dev !== state.device) return;
    state.ch = null;
    if (state.expectDrop || isManualOff()) {
      state.expectDrop = false; state.bridge = false; state.relinking = false;
      emit('ຕັດການເຊື່ອມຕໍ່ແລ້ວ');
      return;
    }
    state.bridge = true;
    state.relinking = false;
  }

  function onSerialDrop() {
    if (state.mode !== 'serial') return;
    var port = state.port, writer = state.writer;
    state.writer = null;
    try { if (writer) writer.releaseLock(); } catch (e) {}
    if (port) port.close().catch(function () {});
    state.port = null;
    if (isManualOff()) { state.relinking = false; emit('ຕັດການເຊື່ອມຕໍ່ແລ້ວ'); return; }
    state.relinking = true;
    scheduleRetry(1000);
  }
  if (isSupported('serial') && nav().serial.addEventListener) {
    nav().serial.addEventListener('disconnect', function (e) { if (e.target === state.port) onSerialDrop(); });
    nav().serial.addEventListener('connect', function () { if (!isConnected() && !isManualOff()) scheduleRetry(300); });
  }

  function writeBle(bytes) {
    var off = 0, sent = 0, t0 = Date.now(), sp = speedName(), rate = SPEEDS[sp];
    function writePart(part) {
      var w = state.noResp
        ? state.ch.writeValueWithoutResponse(part)
        : (state.ch.writeValueWithResponse ? state.ch.writeValueWithResponse(part) : state.ch.writeValue(part));
      return withTimeout(w, 3000, 'ble write timeout').then(function () {
        sent += part.length;
        if (sent <= 2500) return null;
        var ahead = (sent - 2500) * 1000 / rate - (Date.now() - t0);
        return ahead > 4 ? sleep(ahead) : null;
      });
    }
    function next() {
      if (off >= bytes.length) return Promise.resolve();
      var size = BLE_SIZES[state.chunkIdx || 0];
      var part = bytes.slice(off, off + size);
      return writePart(part).then(function () {
        off += part.length;
        return next();
      }, function (err) {
        if (off === 0 && (state.chunkIdx || 0) < BLE_SIZES.length - 1 && state.ch && state.device && state.device.gatt.connected) {
          state.chunkIdx = (state.chunkIdx || 0) + 1;
          return next();
        }
        throw err;
      });
    }
    return next();
  }

  // ສົ່ງຂໍ້ມູນໃບບິນໄປເຄື່ອງພິມ Bluetooth ຜ່ານ Windows ໂດຍກົງ (ບໍ່ຕ້ອງເຊື່ອມໃໝ່ລະຫວ່າງບິນ)
  function toBase64(bytes) {
    var bin = '', chunk = 8192;
    for (var i = 0; i < bytes.length; i += chunk) {
      bin += String.fromCharCode.apply(null, bytes.subarray(i, Math.min(i + chunk, bytes.length)));
    }
    return W.btoa(bin);
  }
  function releaseBrowserGatt() {
    if (state.device && state.device.gatt && state.device.gatt.connected) {
      state.expectDrop = true;
      try { state.device.gatt.disconnect(); } catch (e) {}
    }
    state.ch = null;
    state.bridge = true;
  }
  function writeBridge(bytes) {
    var name = state.name || (remembered() && remembered().name) || '';
    if (!name) return Promise.reject(new Error('ຍັງບໍ່ໄດ້ຕັ້ງເຄື່ອງພິມ Bluetooth'));
    releaseBrowserGatt();
    return W.fetch(BRIDGE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: name, data: toBase64(bytes) })
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res || res.status !== 'success') throw new Error((res && res.message) || 'ພິມຜ່ານ Bluetooth ບໍ່ສຳເລັດ');
    });
  }

  // ---------- Serial (Classic Bluetooth SPP / COM port) ----------
  function attachSerial(port, name) {
    return port.open({ baudRate: 115200 }).catch(function (e) {
      if (!/already open/i.test(String(e && e.message))) throw e;
    }).then(function () {
      state.mode = 'serial';
      state.port = port;
      state.writer = port.writable.getWriter();
      var info = port.getInfo ? port.getInfo() : {};
      state.name = name || ('COM' + (info.usbProductId ? ' ' + info.usbProductId : '') + ' (Bluetooth)');
      W.clearTimeout(retryTimer);
      state.relinking = false; state.looping = false; state.restoreKey = 'serial:' + state.name;
      remember();
      emit('ເຊື່ອມຕໍ່ແລ້ວ');
      return state.name;
    });
  }
  function writeSerial(bytes) {
    var off = 0, size = 4096;
    function next() {
      if (off >= bytes.length) return Promise.resolve();
      var part = bytes.slice(off, off + size);
      off += size;
      return state.writer.write(part).then(next);
    }
    return next();
  }

  // ---------- connect / reconnect ----------
  function connect(mode, filterName) {
    mode = mode === 'serial' ? 'serial' : 'ble';
    if (!isSupported(mode)) {
      return Promise.reject(new Error(mode === 'serial'
        ? 'browser ນີ້ບໍ່ຮອງຮັບ COM port — ກະລຸນາໃຊ້ Chrome ຫຼື Edge ໃນຄອມ'
        : 'browser ນີ້ບໍ່ຮອງຮັບ Bluetooth — ກະລຸນາໃຊ້ Chrome ຫຼື Edge (ແລະ ເປີດຜ່ານ https ຫຼື localhost)'));
    }
    setManualOff(false);
    return dropConn(true).then(function () {
      if (mode === 'serial') {
        return nav().serial.requestPort().then(function (port) { return attachSerial(port, filterName); });
      }
      var opts = filterName
        ? { filters: [{ name: filterName }], optionalServices: BLE_SERVICES }
        : { acceptAllDevices: true, optionalServices: BLE_SERVICES };
      return nav().bluetooth.requestDevice(opts).then(function (dev) {
        if (isAndroid()) {
          return attachBle(dev, filterName);
        }
        state.mode = 'ble';
        state.device = dev;
        state.name = dev.name || filterName || 'Bluetooth Printer';
        state.bridge = true;
        state.relinking = false;
        state.restoreKey = 'ble:' + dev.id;
        remember();
        emit('ເຊື່ອມຕໍ່ແລ້ວ');
        return state.name;
      });
    });
  }

  function reconnect(force) {
    if (force === true) setManualOff(false);
    else if (isManualOff()) return Promise.resolve(false);
    if (isConnected()) return Promise.resolve(true);
    if (reconnecting) return reconnecting;
    var list = loadList();
    if (!list.length) {
      if (isAndroid()) {
        state.mode = 'rawbt';
        state.name = 'ເຄື່ອງພິມໃນຕົວ (57x40mm)';
        state.restoreKey = 'rawbt:builtin';
        remember();
        emit('ເຊື່ອມຕໍ່ແລ້ວ');
        return Promise.resolve(true);
      }
      return Promise.resolve(false);
    }

    var last = list[0];
    if (last && last.mode === 'rawbt') {
      state.mode = 'rawbt';
      state.name = last.name || 'ເຄື່ອງພິມໃນຕົວ (57x40mm)';
      state.restoreKey = last.key;
      state.relinking = false;
      emit('ເຊື່ອມຕໍ່ແລ້ວ');
      return Promise.resolve(true);
    }
    if (last && last.mode === 'ble') {
      if (isAndroid() && nav() && nav().bluetooth && nav().bluetooth.getDevices) {
        return nav().bluetooth.getDevices().then(function (devs) {
          var dev = (devs || []).filter(function (d) { return d.id === last.id || d.name === last.name; })[0] || devs[0];
          if (!dev) return false;
          return withTimeout(attachBle(dev, last.name), CONNECT_TIMEOUT, 'off').then(function () { return true; }, function () { return false; });
        }).catch(function () { return false; });
      }
      state.mode = 'ble';
      state.name = last.name || 'Bluetooth Printer';
      state.restoreKey = last.key;
      state.bridge = true;
      state.relinking = false;
      emit('ເຊື່ອມຕໍ່ແລ້ວ');
      return Promise.resolve(true);
    }

    var ports = isSupported('serial') ? nav().serial.getPorts().catch(function () { return []; }) : Promise.resolve([]);
    reconnecting = ports.then(function (serialPorts) {
      if (last && last.mode === 'serial' && serialPorts && serialPorts.length) {
        return withTimeout(attachSerial(serialPorts[0], last.name), CONNECT_TIMEOUT, 'off').then(function () { return true; }, function () { return false; });
      }
      return false;
    }).then(function (ok) { reconnecting = null; return ok; }, function () { reconnecting = null; return false; });
    return reconnecting;
  }

  function disconnect() {
    setManualOff(true);
    return dropConn(false);
  }

  function dropConn(silent) {
    var p = Promise.resolve();
    W.clearTimeout(retryTimer);
    state.relinking = false; state.looping = false; state.bridge = false; state.restoreKey = '';
    if (state.mode === 'ble' && state.device && state.device.gatt && state.device.gatt.connected) {
      state.expectDrop = true;
      try { state.device.gatt.disconnect(); } catch (e) {}
    }
    if (state.mode === 'serial' && state.port) {
      var port = state.port, writer = state.writer;
      p = Promise.resolve().then(function () { if (writer) writer.releaseLock(); })
        .then(function () { return port.close(); }).catch(function () {});
    }
    state.ch = null; state.writer = null; state.port = null;
    return p.then(function () { if (!silent) emit('ຕັດການເຊື່ອມຕໍ່ແລ້ວ'); });
  }

  function forget(key) {
    key = key || currentKey();
    try { W.localStorage.removeItem(STORE_KEY); } catch (e) {}
    var remain = loadList().filter(function (p) { return p.key !== key; });
    saveList(remain);
    var isCurrent = key && key === currentKey();
    var p = isCurrent ? dropConn(true).then(function () {
      state.device = null; state.name = ''; state.mode = null; state.restoreKey = '';
      if (remain[0] && !isManualOff()) reconnect();
    }) : Promise.resolve();
    return p.then(function () { emit('ລຶບເຄື່ອງພິມແລ້ວ'); });
  }

  function use(key) {
    var p = loadList().filter(function (x) { return x.key === key; })[0];
    if (!p) return Promise.reject(new Error('ບໍ່ພົບເຄື່ອງພິມນີ້ໃນລາຍຊື່'));
    setManualOff(false);
    return dropConn(true).then(function () {
      state.mode = p.mode; state.name = p.name; state.restoreKey = p.key;
      if (p.mode === 'serial') {
        state.relinking = true; emit('');
        return nav().serial.getPorts().then(function (ports) {
          if (!ports.length) return connect('serial', p.name);
          return withTimeout(attachSerial(ports[0], p.name), CONNECT_TIMEOUT, 'ເຄື່ອງພິມບໍ່ຕອບ — ກວດວ່າເປີດເຄື່ອງແລ້ວ');
        });
      }
      state.bridge = true; state.relinking = false; remember(); emit('ເຊື່ອມຕໍ່ແລ້ວ');
      return p.name;
    });
  }

  function list() {
    var cur = shownConnected() ? currentKey() : '';
    return loadList().map(function (p) { return { key: p.key, mode: p.mode, name: p.name, connected: p.key === cur }; });
  }

  // ສົ່ງຂໍ້ມູນພິມ: ຖ້າເປັນເຄື່ອງ POS ມືຖື Android (RawBT) ສົ່ງອອກຫົວພິມໃນຕົວເລີຍ; ຖ້າເປັນ Windows ໃຊ້ BLE Bridge ຫຼື Web Bluetooth
  function write(bytes) {
    if (!isConnected()) return Promise.reject(new Error('ຍັງບໍ່ໄດ້ເຊື່ອມຕໍ່ເຄື່ອງພິມ Bluetooth'));
    busy = true;
    var done = function () { busy = false; };
    var p;
    if (state.mode === 'rawbt') {
      p = sendRawBt(bytes);
    } else if (state.mode === 'serial') {
      p = writeSerial(bytes);
    } else if (isAndroid()) {
      p = (hasLiveBle() ? Promise.resolve() : (state.device ? withTimeout(attachBle(state.device, state.name), 5000, 'off') : Promise.reject(new Error('off'))))
        .then(function () { return writeBle(bytes); })
        .catch(function () { return sendRawBt(bytes); });
    } else {
      p = writeBridge(bytes).catch(function (bridgeErr) {
        if (state.device) {
          return withTimeout(attachBle(state.device, state.name), 4000, 'off').then(function () {
            return writeBle(bytes);
          }).then(function () {
            releaseBrowserGatt();
          }, function () {
            throw bridgeErr;
          });
        }
        throw bridgeErr;
      });
    }
    return p.then(done, function (e) { done(); throw e; });
  }

  // ---------- ESC/POS ----------
  function concat(parts) { return (W.MiniposEscPos || Esc).concat(parts); }
  function loadCanvas(src) { return (W.MiniposEscPos || Esc).loadCanvas(src); }
  function rasterBytes(canvas, maxDots) { return (W.MiniposEscPos || Esc).rasterBytes(canvas, maxDots); }

  function printImage(src, opts) {
    opts = opts || {};
    var prep = isConnected() ? Promise.resolve(true) : reconnect();
    return Promise.all([loadCanvas(src), prep]).then(function (res) {
      var canvas = res[0];
      var maxDots = opts.dots || (canvas.width > 400 ? 576 : 384);
      var bytes = concat([
        new Uint8Array([0x1b, 0x40]),                       // ESC @  ລ້າງຄ່າເຄື່ອງພິມ
        new Uint8Array([0x1c, 0x2e]),                       // FS .   ປິດໂໝດຕົວຈີນ
        new Uint8Array([0x1b, 0x61, 0x00]),                 // ESC a 0 ຊິດຊ້າຍ (ຍັບໜັງສືມາຊ້າຍມືເລັກນ້ອຍ)
        rasterBytes(canvas, maxDots),
        new Uint8Array([0x1b, 0x64, opts.feed == null ? 3 : opts.feed]),   // ເລື່ອນເຈ້ຍພໍດີຂອບຕັດ
        opts.cut === false ? new Uint8Array(0) : new Uint8Array([0x1d, 0x56, 0x42, 0x00])  // ຕັດເຈ້ຍ
      ]);
      return write(bytes);
    });
  }

  function printTest(dots) {
    dots = dots || 384;
    return printImage((W.MiniposEscPos || Esc).testCanvas(dots, state.name), { dots: dots });
  }

  (function restore() {
    var last = loadList()[0];
    if (!last && isAndroid()) {
      last = { key: 'rawbt:builtin', mode: 'rawbt', name: 'ເຄື່ອງພິມໃນຕົວ (57x40mm)', id: '', last: Date.now() };
      saveList([last]);
      try {
        var curCfg = JSON.parse(W.localStorage.getItem('StoreMini.bt_printer_settings') || '{}') || {};
        if (!curCfg.paper) curCfg.paper = 58;
        if (curCfg.enabled === undefined) curCfg.enabled = true;
        W.localStorage.setItem('StoreMini.bt_printer_settings', JSON.stringify(curCfg));
      } catch (e) {}
    }
    if (!last || isManualOff() || !isEnabledSetting()) return;
    state.mode = last.mode;
    state.name = last.name || 'Bluetooth Printer';
    state.restoreKey = last.key;
    if (last.mode === 'ble' && !isAndroid()) state.bridge = true;
    else if (last.mode !== 'rawbt') state.relinking = true;
    W.setTimeout(function () { reconnect(); }, 50);
  })();

  var api = {
    __v: VERSION,
    canRestore: function () { return true; },
    isSupported: isSupported, connect: connect, reconnect: reconnect, disconnect: disconnect, forget: forget, list: list, use: use,
    isConnected: isConnected, printImage: printImage, printTest: printTest,
    speed: function (v) { if (v && SPEEDS[v]) { try { W.localStorage.setItem(SPEED_KEY, v); } catch (e) {} } return speedName(); },
    rasterBytes: rasterBytes,
    info: function () { var m = remembered(); return { mode: state.mode || (m && m.mode), name: state.name || (m && m.name) || '', connected: shownConnected(), remembered: !!m }; },
    onStatus: function (fn) { if (typeof fn === 'function') listeners.push(fn); },
    offStatus: function (fn) { listeners = listeners.filter(function (x) { return x !== fn; }); }
  };
  window.MiniposBtPrinter = api;
  try { if (W !== window) W.MiniposBtPrinter = api; } catch (e) {}
})(window);
