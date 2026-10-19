<?php
/*
 * ສ່ວນ: JS: ກຣາຟລາຍປີ, % ກຳໄລ ແລະ ສິນຄ້າຂາຍດີ
 * ກຣາຟປຽບທຽບລາຍປີ, % ກຳໄລລາຍເດືອນ, 10 ສິນຄ້າຂາຍດີ ແລະ ແປປ້າຍກຣາຟເມື່ອປ່ຽນພາສາ; ປິດ </script>.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/home.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  // ===== 2. Yearly Sales Comparison Chart =====
  var ctxYearly = document.getElementById('yearlySalesChart');
  if (ctxYearly) {
    yearlySalesChartObj = new Chart(ctxYearly, {
      type: 'bar',
      data: {
        labels: yearlyLabels,
        datasets: [{
          label: curLabel(I18N_HOME['home.legend_sales']),
          backgroundColor: ['#2563eb', '#0284c7', '#16a34a', '#d97706', '#8b5cf6'],
          data: yearlySales,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(val) { return curAxis(val); }
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
          backgroundColor: 'rgba(217, 119, 6, 0.15)',
          borderColor: '#d97706',
          pointBackgroundColor: '#b45309',
          borderWidth: 3,
          data: monthlyMargin,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: 100,
            ticks: {
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
    // The "no sales" placeholder always stays on one line
    var formattedTop10Names = noTop10Data ? top10Names.slice() : top10Names.map(function(name) {
      return formatChartLabel(name, 16);
    });

    // Ranking: thin horizontal bars in one hue (a single measure — no rainbow), rank number in
    // front of each name, the quantity written at the end of each bar so no x-axis is needed.
    var TOP_COLOR = '#2a78d6';
    var rankedTop10Names = noTop10Data ? formattedTop10Names : formattedTop10Names.map(function(lbl, i) {
      var lines = Array.isArray(lbl) ? lbl.slice() : [lbl];
      lines[0] = (i + 1) + '. ' + lines[0];
      return lines.length > 1 ? lines : lines[0];
    });
    var topValueLabels = {
      id: 'topValueLabels',
      afterDatasetsDraw: function(chart) {
        if (noTop10Data) return;
        var c = chart.ctx, meta = chart.getDatasetMeta(0);
        c.save();
        c.font = "700 11px 'Noto Sans Lao', 'Souliyo', sans-serif";
        c.fillStyle = '#334155';
        c.textBaseline = 'middle';
        meta.data.forEach(function(bar, i) {
          var v = chart.data.datasets[0].data[i];
          if (v == null) return;
          c.fillText(Number(v).toLocaleString('en-US'), bar.x + 6, bar.y);
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
          backgroundColor: TOP_COLOR,
          hoverBackgroundColor: '#1d5fb0',
          borderRadius: 4,
          borderSkipped: 'start',
          barPercentage: 0.7,
          categoryPercentage: 0.85,
          maxBarThickness: 16
        }]
      },
      plugins: [topValueLabels],
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { left: 4, right: 44, top: 4, bottom: 4 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#ffffff',
            titleColor: '#0f172a',
            bodyColor: '#334155',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 10,
            displayColors: false,
            titleFont: { weight: '700' },
            callbacks: {
              title: function(items) { return items.length ? (top10Names[items[0].dataIndex] || '') : ''; },
              label: function(ctx) { return ctx.dataset.label + ': ' + Number(ctx.parsed.x).toLocaleString('en-US'); }
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
              padding: 6,
              font: { family: "'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif", size: 11, weight: '600' }
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
