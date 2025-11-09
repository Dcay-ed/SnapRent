  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Check if performance chart exists
      const perf = document.getElementById('performanceChart');
      if (perf && typeof perfChartData !== 'undefined') {
        new Chart(perf, {
          type: 'line',
          data: {
            labels: perfChartData.labels,
            datasets: [
              { 
                label: 'Total Sales', 
                data: perfChartData.orders,
                borderColor: '#c76d7e',
                backgroundColor: 'rgba(199, 109, 126, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#c76d7e',
                borderWidth: 2
              },
              { 
                label: 'Total Revenue', 
                data: perfChartData.revenue,
                borderColor: '#5b4d9e',
                backgroundColor: 'rgba(91, 77, 158, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#5b4d9e',
                borderWidth: 2,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
              legend: {
                display: true,
                position: 'top',
                align: 'end',
                labels: {
                  color: 'white',
                  usePointStyle: true,
                  padding: 15,
                  font: { size: 12 }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                position: 'left',
                grid: { color: 'rgba(255, 255, 255, 0.2)' },
                ticks: { 
                  color: 'white',
                  font: { size: 11 }
                }
              },
              y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: { 
                  color: 'white',
                  font: { size: 11 }
                }
              },
              x: {
                grid: { color: 'rgba(255, 255, 255, 0.2)' },
                ticks: { 
                  color: 'white',
                  font: { size: 11 }
                }
              }
            }
          }
        });
      }

      // Check if orders chart exists
      const ordersCtx = document.getElementById('ordersChart');
      if (ordersCtx && typeof ordersChartData !== 'undefined') {
        new Chart(ordersCtx, {
          type: 'bar',
          data: { 
            labels: ordersChartData.labels, 
            datasets: [{ 
              label: 'Orders', 
              data: ordersChartData.orders,
              backgroundColor: '#5b8bd8'
            }] 
          },
          options: { 
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      }
    });
  </script>
</body>
</html>
