<?php
/*
 * ສ່ວນ: JS: ກຣາຟລາຍປີ, % ກຳໄລ ແລະ ສິນຄ້າຂາຍດີ
 * ກຣາຟປຽບທຽບລາຍປີ, % ກຳໄລລາຍເດືອນ, 10 ສິນຄ້າຂາຍດີ ແລະ ແປປ້າຍກຣາຟເມື່ອປ່ຽນພາສາ; ປິດ </script>.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/home.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  // ===== 2. Yearly Sales Comparison Chart =====
  var ctxYearly = document.getElementById('yearlySalesChart');
  if (ctxYearly) {
    var yearlyPalette = [
      ['#3b82f6', '#1d4ed8'], // Royal Blue
      ['#06b6d4', '#0284c7'], // Cyan
      ['#10b981', '#047857'], // Emerald
      ['#f59e0b', '#b45309'], // Amber
      ['#8b5cf6', '#6d28d9']  // Violet
    ];
    yearlySalesChartObj = new Chart(ctxYearly, {
      type: 'bar',
      data: {
        labels: yearlyLabels,
        datasets: [{
          label: curLabel(I18N_HOME['home.legend_sales']),
          backgroundColor: function(ctx) {
            var area = ctx.chart.chartArea;
            if (!area) return '#2563eb';
            var pair = yearlyPalette[ctx.dataIndex % yearlyPalette.length];
            var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
            g.addColorStop(0, pair[0]);
            g.addColorStop(1, pair[1]);
            return g;
          },
          data: yearlySales,
          borderRadius: 10,
          borderSkipped: false,
          maxBarThickness: 46
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: {
            left: 4,
            right: (window.innerWidth < 768 ? 4 : 8),
            top: 4,
            bottom: 0
          }
        },
        plugins: {
          legend: { display: false },
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
              label: function(ctx) { return ' ' + ctx.dataset.label + ': ' + curAxis(ctx.parsed.y); }
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
              maxRotation: (window.innerWidth < 768 ? 35 : 0),
              autoSkip: false
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
              maxTicksLimit: 5,
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

  // ===== 3. Profit Margin % Chart =====
  var ctxMargin = document.getElementById('profitMarginChart');
  if (ctxMargin) {
    profitMarginChartObj = new Chart(ctxMargin, {
      type: 'line',
      data: {
        labels: monthLabels,
        datasets: [{
          label: I18N_HOME['home.legend_margin'],
          backgroundColor: function(ctx) {
            var area = ctx.chart.chartArea;
            if (!area) return 'rgba(245, 158, 11, 0.15)';
            var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
            g.addColorStop(0, 'rgba(245, 158, 11, 0.35)');
            g.addColorStop(0.7, 'rgba(245, 158, 11, 0.08)');
            g.addColorStop(1, 'rgba(245, 158, 11, 0.00)');
            return g;
          },
          borderColor: '#f59e0b',
          borderWidth: 3,
          pointBackgroundColor: '#f59e0b',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointHoverBorderWidth: 3,
          data: monthlyMargin,
          fill: true,
          tension: 0.42,
          cubicInterpolationMode: 'monotone'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        layout: {
          padding: {
            left: 0,
            right: (window.innerWidth < 768 ? 4 : 8),
            top: 4,
            bottom: 0
          }
        },
        plugins: {
          legend: { display: false },
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
              label: function(ctx) { return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%'; }
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
            suggestedMax: 100,
            grid: {
              display: true,
              color: 'rgba(226, 232, 240, 0.75)'
            },
            border: { display: false },
            ticks: {
              color: '#64748b',
              font: { size: (window.innerWidth < 768 ? 10 : 12), weight: '600' },
              padding: (window.innerWidth < 768 ? 2 : 6),
              maxTicksLimit: 5,
              callback: function(val) { return val + '%'; }
            }
          }
        }
      }
    });
  }

  // ===== 4. Top 10 Best Sellers Chart =====
  var ctxTop = document.getElementById('topSellersChart');
  if (ctxTop) {
    function formatTop10Name(name, rank, maxLen) {
      var prefix = rank + '. ';
      var full = prefix + name;
      if (full.length <= maxLen) {
        return full;
      }
      var indent = (rank >= 10 ? '    ' : '   ');
      if (name.indexOf(' ') !== -1) {
        var words = name.split(' ');
        var line1 = prefix;
        var line2 = '';
        for (var i = 0; i < words.length; i++) {
          var w = words[i];
          if (!w) continue;
          if (line2 === '' && (line1 + (line1 === prefix ? '' : ' ') + w).length <= maxLen) {
            line1 += (line1 === prefix ? '' : ' ') + w;
          } else {
            line2 += (line2 === '' ? '' : ' ') + w;
          }
        }
        if (line2 !== '') {
          if (line2.length > maxLen + 2) {
            line2 = line2.substring(0, maxLen) + '…';
          }
          return [line1, indent + line2];
        }
      }
      var isMark = function (ch) { return /[ັຳ-ຼ່-ໍ]/.test(ch || ''); };
      var end = Math.min(maxLen, full.length);
      while (end > 2 && isMark(full.charAt(end))) end--;
      var rem = full.substring(end);
      if (rem.length > maxLen + 2) {
        rem = rem.substring(0, maxLen) + '…';
      }
      return [full.substring(0, end), indent + rem];
    }

    var isMobileTop = (window.innerWidth < 768);
    var maxCharsPerLine = isMobileTop ? 18 : 22;

    var rankedTop10Names = noTop10Data ? [I18N_HOME['home.no_sales_data']] : top10Names.map(function(name, i) {
      return formatTop10Name(name, i + 1, maxCharsPerLine);
    });

    var topValueLabels = {
      id: 'topValueLabels',
      afterDatasetsDraw: function(chart) {
        if (noTop10Data) return;
        var c = chart.ctx, meta = chart.getDatasetMeta(0);
        var isMob = (window.innerWidth < 768);
        c.save();
        c.font = (isMob ? "700 10px " : "700 12px ") + "'Nuanta', 'Noto Sans Lao', 'Souliyo', sans-serif";
        c.fillStyle = '#0f172a';
        c.textBaseline = 'middle';
        meta.data.forEach(function(bar, i) {
          var v = chart.data.datasets[0].data[i];
          if (v == null) return;
          var xPos = Math.min(bar.x + 6, chart.width - 2);
          c.fillText(Number(v).toLocaleString('en-US') + ' ' + (I18N_HOME['home.unit_qty'] || 'ອັນ'), xPos, bar.y);
        });
        c.restore();
      }
    };
    topSellersChartObj = new Chart(ctxTop, {
      type: 'bar',
      data: {
        labels: rankedTop10Names,
        datasets: [{
          label: I18N_HOME['home.legend_qty_sold'],
          data: top10Qty,
          backgroundColor: function(ctx) {
            var area = ctx.chart.chartArea;
            if (!area) return '#2563eb';
            var g = ctx.chart.ctx.createLinearGradient(area.left, 0, area.right, 0);
            if (ctx.dataIndex === 0) {
              g.addColorStop(0, '#f59e0b');
              g.addColorStop(1, '#fde047');
            } else if (ctx.dataIndex === 1) {
              g.addColorStop(0, '#0284c7');
              g.addColorStop(1, '#38bdf8');
            } else if (ctx.dataIndex === 2) {
              g.addColorStop(0, '#6366f1');
              g.addColorStop(1, '#a5b4fc');
            } else {
              g.addColorStop(0, '#2563eb');
              g.addColorStop(1, '#60a5fa');
            }
            return g;
          },
          hoverBackgroundColor: function(ctx) {
            if (ctx.dataIndex === 0) return '#d97706';
            if (ctx.dataIndex === 1) return '#0369a1';
            if (ctx.dataIndex === 2) return '#4f46e5';
            return '#1d4ed8';
          },
          borderRadius: 8,
          borderSkipped: false,
          barPercentage: 0.65,
          categoryPercentage: 0.85,
          maxBarThickness: (window.innerWidth < 768 ? 15 : 18)
        }]
      },
      plugins: [topValueLabels],
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: {
            left: (window.innerWidth < 768 ? 4 : 8),
            right: (window.innerWidth < 768 ? 42 : 52),
            top: 4,
            bottom: 4
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#ffffff',
            titleColor: '#0f172a',
            bodyColor: '#334155',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 12,
            displayColors: false,
            titleFont: { weight: '700' },
            callbacks: {
              title: function(items) { return items.length ? (top10Names[items[0].dataIndex] || '') : ''; },
              label: function(ctx) {
                var unit = I18N_HOME['home.unit_qty'] || 'ອັນ';
                return ctx.dataset.label + ': ' + Number(ctx.parsed.x).toLocaleString('en-US') + ' ' + unit;
              }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            display: false,
            grid: { display: false }
          },
          y: {
            grid: { display: false },
            border: { display: false },
            ticks: {
              autoSkip: false,
              color: '#334155',
              crossAlign: 'far',
              padding: 4,
              font: {
                family: "'Nuanta', 'Noto Sans Lao', 'Phetsarath OT', 'Souliyo', sans-serif",
                size: (window.innerWidth < 768 ? 9.5 : 11),
                weight: '600'
              }
            },
            afterFit: function(axis) {
              var isMob = (window.innerWidth < 768);
              axis.width = Math.max(axis.width, isMob ? 125 : 160);
            }
          }
        }
      }
    });
  }

  // The dashboard language switch is instant (no reload), so the chart <canvas> bitmaps keep
  // their build-time labels. The shell updates I18N_HOME in place then fires 'pos:langchange';
  // rebuild the affected charts' month-axis and legend labels from the refreshed dictionary.
  function refreshChartLangs() {
    monthLabels = chartKeys.map(chartBucketLabel);
    if (window.chartPeriodText) $('.chart-period-text').text(window.chartPeriodText());   // "(ວັນທີ … – ວັນທີ …)" ໃນຫົວຂໍ້ກຣາຟ

    if (monthlyRevenueChartObj) {
      monthlyRevenueChartObj.data.labels = monthLabels;
      var mdsByKey = { sales: 'home.legend_sales', cost: 'home.legend_cost', profit: 'home.legend_profit' };
      monthlyRevenueChartObj.data.datasets.forEach(function(ds) {
        if (ds._key && mdsByKey[ds._key]) ds.label = curLabel(I18N_HOME[mdsByKey[ds._key]]);
      });
      monthlyRevenueChartObj.update();
    }
    if (profitMarginChartObj) {
      profitMarginChartObj.data.labels = monthLabels;
      if (profitMarginChartObj.data.datasets[0]) {
        profitMarginChartObj.data.datasets[0].label = I18N_HOME['home.legend_margin'];
      }
      profitMarginChartObj.update();
    }
    if (topSellersChartObj && topSellersChartObj.data.datasets[0]) {
      // Only the dataset label needs updating — the tooltip title callback reads top10Names
      // directly (product names, not translated) and the axis ticks use formattedTop10Names.
      topSellersChartObj.data.datasets[0].label = I18N_HOME['home.legend_qty_sold'];
      if (noTop10Data) topSellersChartObj.data.labels = [I18N_HOME['home.no_sales_data']];
      topSellersChartObj.update();
    }
    if (yearlySalesChartObj) {
      yearlyLabels = yearlyYears.map(fmtYearLabel);
      yearlySalesChartObj.data.labels = yearlyLabels;
      if (yearlySalesChartObj.data.datasets[0]) {
        yearlySalesChartObj.data.datasets[0].label = curLabel(I18N_HOME['home.legend_sales']);
      }
      yearlySalesChartObj.update();
    }
  }
  window.addEventListener('pos:langchange', refreshChartLangs);

});
</script>
