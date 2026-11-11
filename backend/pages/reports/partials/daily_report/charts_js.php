<?php
/*
 * ສ່ວນ: JS: ກຣາຟ ແລະ ນັບຕົວເລກ
 * ເລີ່ມ <script>: ອະນິເມຊັນນັບຕົວເລກ, ກຣາຟຍອດຂາຍລາຍວັນ, ສັດສ່ວນການຊຳລະ, ຈຳນວນບິນ ແລະ
 * ແປໜ່ວຍເມື່ອປ່ຽນພາສາ; ປິດ </script>.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/daily_report.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
document.addEventListener('DOMContentLoaded', function () {

  // ຕັ້ງຟ້ອນ Chart.js ໃຫ້ຄືກັນກັບໜ້າ
  Chart.defaults.font.family = "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif";
  Chart.defaults.font.size   = 11;

  // CountUp Animation
  document.querySelectorAll('.counter-num').forEach(function (el) {
    var target = parseFloat(el.getAttribute('data-target') || '0');
    var decs = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var cfmt = function(v){ return v.toLocaleString('en-US',{minimumFractionDigits:decs,maximumFractionDigits:decs}); };
    var suffix = el.getAttribute('data-suffix') || '';
    if (isNaN(target)) target = 0;
    var startTime = null;
    var duration = 1800;
    function step(ts) {
      if (!startTime) startTime = ts;
      var prog = Math.min((ts - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - prog, 3);
      el.textContent = cfmt(ease * target) + suffix;
      if (prog < 1) requestAnimationFrame(step);
      else el.textContent = cfmt(target) + suffix;
    }
    requestAnimationFrame(step);
  });

  // ຊ່ອງໜ່ວຍ (ບິນ/ອັນ) ຖືກ "ອົບ" ເຂົ້າໃນ text node ດຽວກັນກັບຕົວເລກ (ເຊັ່ນ "16 ບິນ") ຕອນນັບແລ້ວ —
  // ຄຳແປແບບ reverse-match ຂອງ dashboard ຈັບ text ປະສົມແບບນີ້ບໍ່ໄດ້ ຈຶ່ງບໍ່ປ່ຽນພາສາໃຫ້ອັດຕະໂນມັດ.
  // ຟັງເຫດການ pos:langchange (dashboard.php dispatch ໃສ່ iframe ນີ້ຕອນສະຫຼັບພາສາ) ແລ້ວແປງຊ່ອງໜ່ວຍ
  // ໃໝ່ດ້ວຍ dictionary ຂອງ parent window, ຄືນ text ດ້ວຍຄ່າສຸດທ້າຍ (ນັບແລ້ວ) + ຊ່ອງໜ່ວຍພາສາໃໝ່.
  window.addEventListener('pos:langchange', function(e) {
    var lang = (e && e.detail && e.detail.lang) || (window.parent && window.parent.CURRENT_ACTIVE_LANG);
    if (!lang) return;
    var dict = null;
    try { dict = window.parent.I18N_ALL_MERGED && window.parent.I18N_ALL_MERGED[lang]; } catch (err) {}
    if (!dict) return;

    document.querySelectorAll('.counter-num[data-suffix-key]').forEach(function(el) {
      var key = el.getAttribute('data-suffix-key');
      if (!dict[key]) return;
      var newSuffix = ' ' + dict[key];
      el.setAttribute('data-suffix', newSuffix);

      var target = parseFloat(el.getAttribute('data-target') || '0');
      var decs = parseInt(el.getAttribute('data-decimals') || '0', 10);
      if (isNaN(target)) target = 0;
      var cfmt = function(v){ return v.toLocaleString('en-US', {minimumFractionDigits:decs, maximumFractionDigits:decs}); };
      el.textContent = cfmt(target) + newSuffix;
    });
  });

  // Chart data (always defined, 0 when no data)
  var labels   = <?php echo json_encode($chartLabels); ?>;
  var netData  = <?php echo json_encode(__cvtArr($__dc, $chartNet)); ?>;
  var cashData = <?php echo json_encode(__cvtArr($__dc, $chartCash)); ?>;
  var qrData   = <?php echo json_encode(__cvtArr($__dc, $chartQr)); ?>;
  var tipData  = <?php echo json_encode(__cvtArr($__dc, $chartTip)); ?>;
  var billsData = <?php echo json_encode($chartBills); ?>;

  // Chart instances + the translation key behind each label, so the pos:langchange handler
  // below can switch them to another language on an instant (no-reload) language change.
  var CUR_SYM = <?php echo json_encode($__sym, JSON_UNESCAPED_UNICODE); ?>;
  var CUR_SUFFIX = ' (' + CUR_SYM + ')';
  var billTooltipPrefix = <?php echo json_encode(t('reports.chart_tooltip_bill', 'ບິນ:') . ' ', JSON_UNESCAPED_UNICODE); ?>;

  // Bar Chart: Daily Net Sales
  var dailySalesChart = new Chart(document.getElementById('chartDailySales'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        _key: 'reports.chart_legend_net',
        label: <?php echo json_encode(t('reports.chart_legend_net', 'ສຸດທິ'), JSON_UNESCAPED_UNICODE); ?> + CUR_SUFFIX,
        data: netData,
        backgroundColor: 'rgba(37,99,235,0.75)',
        borderColor: '#1d4ed8',
        borderWidth: 1.5,
        borderRadius: 6,
      }, {
        _key: 'reports.chart_legend_cash',
        label: <?php echo json_encode(t('reports.chart_legend_cash', 'ເງິນສົດ'), JSON_UNESCAPED_UNICODE); ?> + CUR_SUFFIX,
        data: cashData,
        backgroundColor: 'rgba(99,102,241,0.55)',
        borderColor: '#4338ca',
        borderWidth: 1,
        borderRadius: 4,
      }, {
        _key: 'reports.chart_legend_transfer',
        label: <?php echo json_encode(t('reports.chart_legend_transfer', 'ໂອນ'), JSON_UNESCAPED_UNICODE); ?> + CUR_SUFFIX,
        data: qrData,
        backgroundColor: 'rgba(6,182,212,0.55)',
        borderColor: '#0e7490',
        borderWidth: 1,
        borderRadius: 4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,   // ສູງຕາມກ່ອງ .daily-sales-chart-box (ໂທລະສັບສູງກວ່າ)
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              return ctx.dataset.label + ': ' + Number(ctx.parsed.y).toLocaleString('en-US',{maximumFractionDigits:<?php echo $__dec; ?>}) + ' ' + CUR_SYM;
            }
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
          grid: { color: 'rgba(0,0,0,0.05)' },
          ticks: {
            font: { size: 10 },
            crossAlign: 'far',   // ຕົວເລກເງິນເລີ່ມຊິດຊ້າຍ ກົງກັບຫົວຂໍ້ "ຍອດຂາຍສຸດທິລາຍວັນ"
            callback: function(v) {
              return Number(v).toLocaleString('en-US', {maximumFractionDigits: <?php echo $__dec; ?>}) + ' ' + CUR_SYM;
            }
          }
        }
      }
    }
  });

  // Donut Chart: Payment Method
  var totalPay = <?php echo (convertFromKip($totalCash + $totalQr, $__dc['code'], $__dc['rates']) ?: 1); ?>;
  var paymentMethodChart = new Chart(document.getElementById('chartPaymentMethod'), {
    type: 'doughnut',
    data: {
      labels: [<?php echo json_encode(t('reports.cash_opt', 'ເງິນສົດ'), JSON_UNESCAPED_UNICODE); ?>, <?php echo json_encode(t('reports.col_transfer', 'ໂອນ'), JSON_UNESCAPED_UNICODE); ?>],
      datasets: [{
        data: [<?php echo convertFromKip($totalCash, $__dc['code'], $__dc['rates']); ?>, <?php echo convertFromKip($totalQr, $__dc['code'], $__dc['rates']); ?>],
        backgroundColor: ['#6366f1', '#06b6d4'],
        borderColor: ['#4338ca', '#0e7490'],
        borderWidth: 2,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              var pct = ((ctx.parsed / totalPay) * 100).toFixed(1);
              return ctx.label + ': ' + Number(ctx.parsed).toLocaleString('en-US',{maximumFractionDigits:<?php echo $__dec; ?>}) + ' ' + CUR_SYM + ' (' + pct + '%)';
            }
          }
        }
      }
    }
  });

  // Line Chart: Daily Bills Count
  var dailyBillsChart = new Chart(document.getElementById('chartDailyBills'), {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        _key: 'reports.chart_legend_bill_count',
        label: <?php echo json_encode(t('reports.chart_legend_bill_count', 'ຈຳນວນບິນ'), JSON_UNESCAPED_UNICODE); ?>,
        data: billsData,
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245,158,11,0.12)',
        borderWidth: 2.5,
        pointRadius: 5,
        pointBackgroundColor: '#f59e0b',
        fill: true,
        tension: 0.35,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: { label: function(ctx) { return billTooltipPrefix + ctx.parsed.y; } }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: { stepSize: 1, font: { size: 10 } }
        }
      }
    }
  });

  // <canvas> labels are painted into the bitmap, so the shell's DOM translation can't reach
  // them — relabel each chart from the new language's dictionary on pos:langchange.
  var PAYMENT_LABEL_KEYS = ['reports.cash_opt', 'reports.col_transfer'];
  window.addEventListener('pos:langchange', function(e) {
    var lang = (e && e.detail && e.detail.lang) || (window.parent && window.parent.CURRENT_ACTIVE_LANG);
    var dict = null;
    try { dict = window.parent.I18N_ALL_MERGED && window.parent.I18N_ALL_MERGED[lang]; } catch (err) {}
    if (!dict) return;

    [dailySalesChart, dailyBillsChart].forEach(function(chart) {
      chart.data.datasets.forEach(function(ds) {
        if (ds._key && dict[ds._key]) ds.label = dict[ds._key] + (chart === dailySalesChart ? CUR_SUFFIX : '');
      });
      chart.update();
    });
    paymentMethodChart.data.labels = PAYMENT_LABEL_KEYS.map(function(k, i) {
      return dict[k] || paymentMethodChart.data.labels[i];
    });
    paymentMethodChart.update();
    if (dict['reports.chart_tooltip_bill']) billTooltipPrefix = dict['reports.chart_tooltip_bill'] + ' ';
  });
});
</script>
