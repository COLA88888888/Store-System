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

  // Function to format long labels into multi-line arrays
  function formatChartLabel(str, maxLen) {
    maxLen = maxLen || 16;
    if (!str) return '';
    if (typeof str !== 'string') return str;
    if (str.length <= maxLen) return str;
    
    if (str.indexOf(' ') !== -1) {
      var words = str.split(' ');
      var lines = [];
      var currentLine = '';
      for (var i = 0; i < words.length; i++) {
        var w = words[i];
        if ((currentLine ? currentLine + ' ' + w : w).length <= maxLen) {
          currentLine = currentLine ? currentLine + ' ' + w : w;
        } else {
          if (currentLine) lines.push(currentLine);
          currentLine = w;
        }
      }
      if (currentLine) lines.push(currentLine);
      if (lines.length > 1) return lines;
    }
    
    // No spaces (typical Lao): cut every maxLen chars, but never in front of a Lao
    // vowel / tone mark — it belongs to the consonant before it
    var isMark = function (ch) { return /[ັຳ-ຼ່-ໍ]/.test(ch || ''); };
    var chunks = [];
    var start = 0;
    while (start < str.length) {
      var end = Math.min(start + maxLen, str.length);
      while (end < str.length && end > start + 1 && isMark(str.charAt(end))) end--;
      chunks.push(str.substring(start, end));
      start = end;
    }
    return chunks;
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
  var CHART_MULTI_YEAR = <?php echo json_encode($chart_mode === 'month' && substr($chartFrom, 0, 4) !== substr($chartTo, 0, 4)); ?>;
  function chartBucketLabel(k) {
    if (CHART_MODE === 'hour') return (k < 10 ? '0' : '') + k + ':00';
    if (CHART_MODE === 'day') return k.slice(8, 10) + '/' + k.slice(5, 7);
    var name = I18N_HOME['home.month_' + parseInt(k.slice(5, 7), 10)] || k.slice(5, 7);
    return CHART_MULTI_YEAR ? name + ' ' + k.slice(0, 4) : name;
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
    var pattern = I18N_HOME['home.year_label'] || 'ປີ %d';
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
  var REV_COLORS = { sales: '#2a78d6', cost: '#eb6834', profit: '#1baf7a' };
  var CUR_IS_KIP = <?php echo ($__hdc['code']==='KIP')?'true':'false'; ?>;
  function shortMoney(val) {
    if (!CUR_IS_KIP) return curAxis(val);
    var a = Math.abs(val);
    if (a >= 1000000) return (val / 1000000).toLocaleString('en-US', { maximumFractionDigits: 1 }) + 'M';
    if (a >= 1000) return (val / 1000).toLocaleString('en-US', { maximumFractionDigits: 0 }) + 'k';
    return val;
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
      c.lineWidth = 1;
      c.strokeStyle = 'rgba(15, 23, 42, 0.25)';
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
        backgroundColor: withFill ? function(ctx) {
          var area = ctx.chart.chartArea;
          if (!area) return 'rgba(42, 120, 214, 0.12)';
          var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
          g.addColorStop(0, 'rgba(42, 120, 214, 0.22)');
          g.addColorStop(1, 'rgba(42, 120, 214, 0.01)');
          return g;
        } : REV_COLORS[key],
        fill: withFill ? 'origin' : false,
        borderWidth: 2,
        tension: 0.35,
        cubicInterpolationMode: 'monotone',
        pointRadius: few ? 4 : 0,
        pointHoverRadius: 5,
        pointBackgroundColor: REV_COLORS[key],
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointHitRadius: 12
      };
    };
    var revDatasets = [revLine('sales', 'home.legend_sales', monthlySales, true)];
    if (hasProductCostData) revDatasets.push(revLine('cost', 'home.legend_cost', monthlyCost, false));
    revDatasets.push(revLine('profit', 'home.legend_profit', monthlyProfit, false));

    monthlyRevenueChartObj = new Chart(ctxMonthly, {
      type: 'line',
      data: { labels: monthLabels, datasets: revDatasets },
      plugins: [revCrosshair],
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: { padding: { top: 4, right: 8 } },
        plugins: {
          legend: {
            position: 'top',
            align: 'center',
            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 11, boxHeight: 11, padding: 22, color: '#334155', font: { size: 14, weight: '600' } }
          },
          tooltip: {
            backgroundColor: '#ffffff',
            titleColor: '#0f172a',
            bodyColor: '#334155',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 10,
            usePointStyle: true,
            boxPadding: 6,
            titleFont: { weight: '700' },
            callbacks: {
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
            grid: { display: false },
            border: { color: '#e2e8f0' },
            ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, autoSkipPadding: 12 }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#eef2f7' },
            border: { display: false },
            ticks: { color: '#64748b', padding: 8, maxTicksLimit: 6, callback: shortMoney }
          }
        }
      }
    });
  }

