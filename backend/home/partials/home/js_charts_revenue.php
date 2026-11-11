<?php
/*
 * ສ່ວນ: JS: ນັບຕົວເລກ ແລະ ກຣາຟລາຍຮັບ
 * ເລີ່ມ <script>: ອະນິເມຊັນນັບຕົວເລກ, ຂໍ້ມູນຈາກ PHP, ກຣາຟລາຍຮັບ/ຕົ້ນທຶນ/ກຳໄລ ຕາມເວລາ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/home.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
var I18N_HOME = <?php echo tjson([
    'home.legend_sales' => 'ຍອດຂາຍ',
    'home.legend_cost' => 'ຕົ້ນທຶນ',
    'home.legend_profit' => 'ກຳໄລ',
    'home.legend_margin' => 'ອັດຕາກຳໄລ (%)',
    'home.legend_qty_sold' => 'ຈຳນວນຂາຍ',
    'home.no_sales_data' => 'ບໍ່ມີຂໍ້ມູນການຂາຍ',
    'home.year_label' => 'ປີ %d',
    'home.lbl_date' => 'ວັນທີ',
    'home.lbl_month' => 'ເດືອນ',
    'home.lbl_year' => 'ປີ',
    'home.lbl_time' => 'ເວລາ',
    'home.unit_hour' => 'ໂມງ',
    'home.unit_qty' => 'ອັນ',
    'home.million_suffix' => 'ລ້ານກີບ',
    'home.thousand_suffix' => 'ພັນກີບ',
    'home.month_1' => 'ມັງກອນ', 'home.month_2' => 'ກຸມພາ', 'home.month_3' => 'ມີນາ',
    'home.month_4' => 'ເມສາ', 'home.month_5' => 'ພຶດສະພາ', 'home.month_6' => 'ມິຖຸນາ',
    'home.month_7' => 'ກໍລະກົດ', 'home.month_8' => 'ສິງຫາ', 'home.month_9' => 'ກັນຍາ',
    'home.month_10' => 'ຕຸລາ', 'home.month_11' => 'ພະຈິກ', 'home.month_12' => 'ທັນວາ',
]); ?>;

$(document).ready(function() {
  // CountUp Number Animation for Metrics Cards
  $('.counter-num').each(function() {
    var $el = $(this);
    var target = parseFloat($el.attr('data-target') || 0);
    var decimals = parseInt($el.attr('data-decimals') || 0, 10);
    if (isNaN(target)) target = 0;
    
    var startTime = null;
    var duration = 1200;
    
    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var ease = 1 - Math.pow(1 - progress, 3);
      var current = ease * target;
      
      if (decimals > 0) {
        $el.text(current.toFixed(decimals));
      } else {
        $el.text(Math.floor(current).toLocaleString());
      }
      
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        if (decimals > 0) {
          $el.text(target.toFixed(decimals));
        } else {
          $el.text(Math.round(target).toLocaleString());
        }
      }
    }
    requestAnimationFrame(step);
  });

  if (typeof Chart === 'undefined') {
    return;
  }

  try {
    Chart.defaults.font = Chart.defaults.font || {};
    Chart.defaults.font.family = "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif";
  } catch(e) {}

  // Function to format long labels into multi-line arrays respecting Lao script
  function formatChartLabel(str, maxLen) {
    maxLen = maxLen || 14;
    if (!str) return '';
    if (typeof str !== 'string') return str;
    if (str.length <= maxLen) return str;

    var isMark = function (ch) { return /[ັຳ-ຼ່-ໍ]/.test(ch || ''); };

    function chunkLao(text, limit) {
      var chunks = [];
      var start = 0;
      while (start < text.length) {
        if (text.length - start <= limit) {
          chunks.push(text.substring(start));
          break;
        }
        var end = start + limit;
        while (end < text.length && end > start + 1 && isMark(text.charAt(end))) end--;
        chunks.push(text.substring(start, end));
        start = end;
      }
      return chunks;
    }

    if (str.indexOf(' ') !== -1) {
      var words = str.split(' ');
      var lines = [];
      var currentLine = '';
      for (var i = 0; i < words.length; i++) {
        var w = words[i];
        if (!w) continue;
        if (w.length > maxLen) {
          if (currentLine) { lines.push(currentLine); currentLine = ''; }
          var wChunks = chunkLao(w, maxLen);
          for (var j = 0; j < wChunks.length - 1; j++) {
            lines.push(wChunks[j]);
          }
          currentLine = wChunks[wChunks.length - 1];
        } else if ((currentLine ? currentLine + ' ' + w : w).length <= maxLen) {
          currentLine = currentLine ? currentLine + ' ' + w : w;
        } else {
          if (currentLine) lines.push(currentLine);
          currentLine = w;
        }
      }
      if (currentLine) lines.push(currentLine);
      if (lines.length > 1) return lines;
    }

    return chunkLao(str, maxLen);
  }

  // Multi-currency: append the active symbol in brackets to chart legends.
  var CUR_SYM = <?php echo json_encode($__hsym, JSON_UNESCAPED_UNICODE); ?>;
  var CUR_DEC = <?php echo $__hdec; ?>;
  function curLabel(name){ return name + ' (' + CUR_SYM + ')'; }
  function curAxis(v){ return Number(v).toLocaleString('en-US',{maximumFractionDigits:CUR_DEC}) + ' ' + CUR_SYM; }
  // Data variables from PHP
  // Revenue / margin charts follow the selected range: 'hour' | 'day' | 'month' buckets
  var CHART_MODE    = <?php echo json_encode($chart_mode); ?>;
  var chartKeys     = <?php echo json_encode(array_values($chart_keys)); ?>;
  var CHART_FROM = <?php echo json_encode($chartFrom); ?>, CHART_TO = <?php echo json_encode($chartTo); ?>;
  // ຂໍ້ຄວາມຊ່ວງເວລາຂອງຫົວຂໍ້ກຣາຟ (ກົງກັບ $chart_period_text ໃນ data_charts.php) — ວາດໃໝ່ເມື່ອປ່ຽນພາສາ
  window.chartPeriodText = function () {
    var D = I18N_HOME['home.lbl_date'] || '<?php echo tlj('ວັນທີ'); ?>', M = I18N_HOME['home.lbl_month'] || '<?php echo tlj('ເດືອນ'); ?>';
    var dmy = function (s) { return s.slice(8, 10) + '/' + s.slice(5, 7) + '/' + s.slice(0, 4); };
    var my = function (s) { return s.slice(5, 7) + '/' + s.slice(0, 4); };
    if (CHART_MODE === 'hour') return D + ' ' + dmy(CHART_FROM);
    if (CHART_MODE === 'day') return D + ' ' + dmy(CHART_FROM) + ' – ' + D + ' ' + dmy(CHART_TO);
    if (CHART_FROM.slice(0, 4) === CHART_TO.slice(0, 4) && CHART_FROM.slice(5) === '01-01' && CHART_TO.slice(5) === '12-31') {
      return (I18N_HOME['home.year_label'] || '<?php echo tlj('ປີ %'); ?>d').replace('%d', CHART_FROM.slice(0, 4));
    }
    return M + ' ' + my(CHART_FROM) + ' – ' + M + ' ' + my(CHART_TO);
  };
  var CHART_MULTI_YEAR = <?php echo json_encode($chart_mode === 'month' && substr($chartFrom, 0, 4) !== substr($chartTo, 0, 4)); ?>;
  function chartBucketLabel(k) {
    var dPrefix = I18N_HOME['home.lbl_date'] || '<?php echo tlj('ວັນທີ'); ?>';
    var mPrefix = I18N_HOME['home.lbl_month'] || '<?php echo tlj('ເດືອນ'); ?>';
    var hUnit   = I18N_HOME['home.unit_hour'] || '<?php echo tlj('ໂມງ'); ?>';

    if (CHART_MODE === 'hour') return (k < 10 ? '0' : '') + k + ':00 ' + hUnit;
    if (CHART_MODE === 'day') {
      var d = parseInt(k.slice(8, 10), 10);
      var m = parseInt(k.slice(5, 7), 10);
      return dPrefix + ' ' + d + '/' + (m < 10 ? '0' : '') + m;
    }
    var mNum = parseInt(k.slice(5, 7), 10);
    var name = I18N_HOME['home.month_' + mNum] || k.slice(5, 7);
    var fullMonth = mPrefix + ' ' + name;
    return CHART_MULTI_YEAR ? fullMonth + ' ' + k.slice(0, 4) : fullMonth;
  }
  var monthLabels   = chartKeys.map(chartBucketLabel);
  var monthlySales  = <?php echo json_encode(array_map($__hcvt, array_values($chart_sales))); ?>;
  var monthlyCost   = <?php echo json_encode(array_map($__hcvt, array_values($chart_cost))); ?>;
  var monthlyProfit = <?php echo json_encode(array_map($__hcvt, $chart_profit)); ?>;
  var monthlyMargin = <?php echo json_encode($chart_margin); ?>;
  var hasProductCostData = <?php echo json_encode($has_product_cost_data); ?>;

  var yearlyLabels  = <?php echo json_encode($yearly_labels); ?>;
  var yearlySales   = <?php echo json_encode(array_map($__hcvt, $yearly_sales)); ?>;
  // Raw years behind yearlyLabels, kept so refreshChartLangs() can rebuild the "ປີ %d" labels
  // in the new language without re-fetching data from the server.
  var yearlyYears   = <?php echo json_encode(array_keys($yearly_sales_map)); ?>;

  var top10Names    = <?php echo json_encode($top10_names); ?>;
  var top10Qty      = <?php echo json_encode($top10_qty); ?>;

  if (!yearlyLabels.length) {
    yearlyLabels = [<?php echo json_encode(sprintf(t('home.year_label', 'ປີ %d'), $selected_year), JSON_UNESCAPED_UNICODE); ?>];
    yearlySales  = [0];
    yearlyYears  = [<?php echo (int)$selected_year; ?>];
  }

  function fmtYearLabel(year) {
    var pattern = I18N_HOME['home.year_label'] || '<?php echo tlj('ປີ %'); ?>d';
    return pattern.replace('%d', year);
  }

  var noTop10Data = !top10Names.length;
  if (noTop10Data) {
    top10Names = [I18N_HOME['home.no_sales_data']];
    top10Qty   = [0];
  }

  // Chart instances kept so their labels can be re-translated on an instant language switch
  var monthlyRevenueChartObj = null;
  var profitMarginChartObj = null;
  var topSellersChartObj = null;
  var yearlySalesChartObj = null;

  // ===== 1. Revenue / Cost / Profit over time =====
  // Change over time -> lines, not grouped bars. Sales carries a soft area fill; profit and cost
  // are thin lines on the same (single) axis. Hovering anywhere in a column shows a crosshair and
  // one tooltip with every series for that day / hour / month.
  var REV_COLORS = { sales: '#2563eb', cost: '#f43f5e', profit: '#10b981' };
  var CUR_IS_KIP = <?php echo ($__hdc['code']==='KIP')?'true':'false'; ?>;
  function shortMoney(val) {
    return curAxis(val);
  }

  // Dashed vertical guide under the hovered column
  var revCrosshair = {
    id: 'revCrosshair',
    afterDatasetsDraw: function(chart) {
      var act = chart.tooltip && chart.tooltip.getActiveElements ? chart.tooltip.getActiveElements() : [];
      if (!act.length) return;
      var x = act[0].element.x, area = chart.chartArea, c = chart.ctx;
      c.save();
      c.beginPath();
      c.moveTo(x, area.top);
      c.lineTo(x, area.bottom);
      c.lineWidth = 1.5;
      c.strokeStyle = 'rgba(37, 99, 235, 0.3)';
      c.setLineDash([4, 4]);
      c.stroke();
      c.restore();
    }
  };

  var ctxMonthly = document.getElementById('monthlyRevenueChart');
  if (ctxMonthly) {
    var few = monthLabels.length <= 2; // one or two points: show the dots, there is barely a line
    var revLine = function(key, labelKey, data, withFill) {
      return {
        _key: key,
        label: curLabel(I18N_HOME[labelKey]),
        data: data,
        borderColor: REV_COLORS[key],
        backgroundColor: function(ctx) {
          var area = ctx.chart.chartArea;
          if (!area) return 'rgba(37, 99, 235, 0.08)';
          var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
          if (key === 'sales') {
            g.addColorStop(0, 'rgba(37, 99, 235, 0.30)');
            g.addColorStop(0.6, 'rgba(37, 99, 235, 0.08)');
            g.addColorStop(1, 'rgba(37, 99, 235, 0.00)');
          } else if (key === 'profit') {
            g.addColorStop(0, 'rgba(16, 185, 129, 0.22)');
            g.addColorStop(0.6, 'rgba(16, 185, 129, 0.05)');
            g.addColorStop(1, 'rgba(16, 185, 129, 0.00)');
          } else {
            g.addColorStop(0, 'rgba(244, 63, 94, 0.16)');
            g.addColorStop(1, 'rgba(244, 63, 94, 0.00)');
          }
          return g;
        },
        fill: withFill ? 'origin' : false,
        borderWidth: key === 'sales' ? 3 : 2.4,
        tension: 0.38,
        cubicInterpolationMode: 'monotone',
        pointRadius: few ? 5 : 2.5,
        pointHoverRadius: 7,
        pointBackgroundColor: REV_COLORS[key],
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointHoverBorderWidth: 3,
        pointHitRadius: 14
      };
    };
    var revDatasets = [revLine('sales', 'home.legend_sales', monthlySales, true)];
    if (hasProductCostData) revDatasets.push(revLine('cost', 'home.legend_cost', monthlyCost, false));
    revDatasets.push(revLine('profit', 'home.legend_profit', monthlyProfit, true));

    monthlyRevenueChartObj = new Chart(ctxMonthly, {
      type: 'line',
      data: { labels: monthLabels, datasets: revDatasets },
      plugins: [revCrosshair],
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: { padding: { top: 4, right: (window.innerWidth < 768 ? 4 : 8), left: 4, bottom: 0 } },
        plugins: {
          legend: {
            position: 'top',
            align: 'center',
            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 10, boxHeight: 10, padding: 16, color: '#334155', font: { size: (window.innerWidth < 768 ? 11 : 13), weight: '700' } }
          },
          tooltip: {
            backgroundColor: '#ffffff',
            titleColor: '#0f172a',
            bodyColor: '#334155',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 12,
            usePointStyle: true,
            boxPadding: 6,
            titleFont: { weight: '700' },
            callbacks: {
              title: function(items) {
                if (!items.length) return '';
                var idx = items[0].dataIndex;
                var rawKey = chartKeys[idx];
                var dPrefix = I18N_HOME['home.lbl_date'] || '<?php echo tlj('ວັນທີ'); ?>';
                var mPrefix = I18N_HOME['home.lbl_month'] || '<?php echo tlj('ເດືອນ'); ?>';
                var yPrefix = I18N_HOME['home.lbl_year'] || '<?php echo tlj('ປີ'); ?>';
                var tPrefix = I18N_HOME['home.lbl_time'] || '<?php echo tlj('ເວລາ'); ?>';
                var hUnit   = I18N_HOME['home.unit_hour'] || '<?php echo tlj('ໂມງ'); ?>';

                if (CHART_MODE === 'day' && rawKey) {
                  var d = parseInt(rawKey.slice(8, 10), 10);
                  var m = parseInt(rawKey.slice(5, 7), 10);
                  var y = rawKey.slice(0, 4);
                  var mName = I18N_HOME['home.month_' + m] || m;
                  return dPrefix + ' ' + d + ' ' + mPrefix + ' ' + mName + ' ' + yPrefix + ' ' + y;
                }
                if (CHART_MODE === 'month' && rawKey) {
                  var m = parseInt(rawKey.slice(5, 7), 10);
                  var y = rawKey.slice(0, 4);
                  var mName = I18N_HOME['home.month_' + m] || m;
                  return mPrefix + ' ' + mName + ' ' + yPrefix + ' ' + y;
                }
                if (CHART_MODE === 'hour') {
                  return tPrefix + ' ' + items[0].label;
                }
                return items[0].label || '';
              },
              label: function(ctx) { return ' ' + ctx.dataset.label + ': ' + curAxis(ctx.parsed.y); },
              afterBody: function(items) {
                if (!items.length) return '';
                var m = monthlyMargin[items[0].dataIndex];
                return (m && I18N_HOME['home.legend_margin']) ? ['', ' ' + I18N_HOME['home.legend_margin'] + ': ' + m + '%'] : '';
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: true,
              color: 'rgba(226, 232, 240, 0.75)'
            },
            border: { color: '#e2e8f0' },
            ticks: {
              color: '#64748b',
              font: { size: (window.innerWidth < 768 ? 10 : 12), weight: '600' },
              maxRotation: 0,
              autoSkip: true,
              autoSkipPadding: (window.innerWidth < 768 ? 8 : 12)
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              display: true,
              color: 'rgba(226, 232, 240, 0.75)'
            },
            border: { display: false },
            ticks: {
              color: '#64748b',
              font: { size: (window.innerWidth < 768 ? 9.5 : 12), weight: '600' },
              crossAlign: 'far',
              padding: 4,
              maxTicksLimit: (window.innerWidth < 768 ? 5 : 6),
              callback: shortMoney
            },
            afterFit: function(axis) {
              var isMob = (window.innerWidth < 768);
              axis.width = Math.max(axis.width, isMob ? 100 : 125);
            }
          }
        }
      }
    });
  }

