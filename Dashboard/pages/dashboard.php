<?php
// ======================================================================
// dashboard.php — SnapRent Admin Dashboard (UI sesuai mockup, siap pakai)
// Catatan: TOP RENTED pakai gambar dari table camera_images
// Asumsi $pdo (PDO) sudah tersedia dari bootstrap/index.
// ======================================================================


// Base URL untuk gambar kamera (relatif dari folder Dashboard/)
$IMG_URL_BASE = 'uploads/cameras';   // -> uploads/cameras/{camera_id}/{filename}


// Helper aman: idr_compact() bila belum ada
if (!function_exists('idr_compact')) {
  function idr_compact(float $n): string {
    $abs = abs($n);
    if ($abs >= 1_000_000_000) return 'Rp ' . round($n/1_000_000_000,1) . ' M';
    if ($abs >= 1_000_000)     return 'Rp ' . round($n/1_000_000,1) . ' JT';
    if ($abs >= 1_000)         return 'Rp ' . number_format($n,0,',','.');
    return 'Rp ' . (string)intval($n);
  }
}


// Month parameter
$mParam = $_GET['month'] ?? date('Y-m');
$start_month = (new DateTime($mParam . '-01 00:00:00'))->format('Y-m-d H:i:s');
$end_month   = (new DateTime($start_month))->modify('last day of this month 23:59:59')->format('Y-m-d H:i:s');
$prev_start  = (new DateTime($start_month))->modify('first day of previous month 00:00:00')->format('Y-m-d H:i:s');
$prev_end    = (new DateTime($start_month))->modify('last day of previous month 23:59:59')->format('Y-m-d H:i:s');


// ===== KPIs (tetap sesuai punyamu)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$start_month,$end_month]);
$total_sales = (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$prev_start,$prev_end]);
$total_sales_prev = (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND paid_at BETWEEN ? AND ?");
$stmt->execute([$start_month,$end_month]);
$total_revenue = (float)$stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND paid_at BETWEEN ? AND ?");
$stmt->execute([$prev_start,$prev_end]);
$total_revenue_prev = (float)$stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE role='CUSTOMER' AND created_at BETWEEN ? AND ?");
$stmt->execute([$start_month,$end_month]);
$new_customers = (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE role='CUSTOMER' AND created_at BETWEEN ? AND ?");
$stmt->execute([$prev_start,$prev_end]);
$new_customers_prev = (int)$stmt->fetchColumn();


// Percentage calculation (tetap)
$pct = function($cur,$prev){
  if ($prev<=0 && $cur>0) return '+100%';
  if ($prev===$cur) return '0%';
  if ($prev<=0 && $cur<=0) return '0%';
  $v = (($cur-$prev)/max(1e-9,$prev))*100;
  return sprintf('%+.1f%%',$v);
};
$mom_sales = $pct($total_sales,$total_sales_prev);
$mom_rev   = $pct($total_revenue,$total_revenue_prev);
$mom_cust  = $pct($new_customers,$new_customers_prev);


// Weekly data for chart (tetap)
$labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
$map = [
  'Week 1' => ['rev'=>0,'ord'=>0],
  'Week 2' => ['rev'=>0,'ord'=>0],
  'Week 3' => ['rev'=>0,'ord'=>0],
  'Week 4' => ['rev'=>0,'ord'=>0]
];


$q = $pdo->prepare("SELECT DATE(paid_at) d, SUM(amount) amt FROM payments WHERE status='verified' AND paid_at BETWEEN ? AND ? GROUP BY DATE(paid_at)");
$q->execute([$start_month,$end_month]);
foreach($q as $r){
  $weekIdx = min(3, (int)((strtotime($r['d']) - strtotime($start_month)) / (7*86400)));
  $weekLabel = 'Week ' . ($weekIdx + 1);
  if (isset($map[$weekLabel])) $map[$weekLabel]['rev'] += (float)$r['amt'];
}


$q = $pdo->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM rentals WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at)");
$q->execute([$start_month,$end_month]);
foreach($q as $r){
  $weekIdx = min(3, (int)((strtotime($r['d']) - strtotime($start_month)) / (7*86400)));
  $weekLabel = 'Week ' . ($weekIdx + 1);
  if (isset($map[$weekLabel])) $map[$weekLabel]['ord'] += (int)$r['c'];
}


$rev_series = [];
$ord_series = [];
foreach($map as $v){
  $rev_series[] = $v['rev'];
  $ord_series[] = $v['ord'];
}


// ===== TOP RENTED (gambar dari camera_images)
// Ambil 1 gambar per kamera (MIN(id)), lalu ambil filename + camera_id
$top_rented_stmt = $pdo->prepare("
  SELECT 
    cam.id,
    cam.name,
    COUNT(*) AS cnt,
    ci.filename AS img_filename,
    ci.camera_id AS img_camera_id
  FROM rentals rn
  JOIN cameras cam ON cam.id = rn.camera_id
  LEFT JOIN (
    SELECT camera_id, MIN(id) AS img_id
    FROM camera_images
    GROUP BY camera_id
  ) pick ON pick.camera_id = cam.id
  LEFT JOIN camera_images ci ON ci.id = pick.img_id
  WHERE rn.created_at BETWEEN ? AND ?
  GROUP BY cam.id, cam.name, ci.filename, ci.camera_id
  ORDER BY cnt DESC
  LIMIT 3
");
$top_rented_stmt->execute([$start_month,$end_month]);
$top_rented = $top_rented_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lengkapi placeholder jika kurang dari 3
for ($i = count($top_rented); $i < 3; $i++) {
  $top_rented[] = [
    'id'            => null,
    'name'          => '—',
    'cnt'           => 0,
    'img_filename'  => '',
    'img_camera_id' => null
  ];
}


// ===== Recent rentals (STATUS ikut rentals.status)
$recent_orders = $pdo->prepare(
  "SELECT rn.id,
          rn.created_at,
          rn.total_price,
          rn.status AS rental_status,
          acc.username AS customer,
          cam.name AS camera_name,
          cam.daily_price
   FROM rentals rn
   JOIN customers c ON c.customer_id = rn.customer_id
   JOIN accounts  acc ON acc.id = c.customer_id
   JOIN cameras   cam ON cam.id = rn.camera_id
   WHERE rn.created_at BETWEEN ? AND ?
   ORDER BY rn.created_at DESC
   LIMIT 5"
);
$recent_orders->execute([$start_month,$end_month]);
$recent_orders = $recent_orders->fetchAll(PDO::FETCH_ASSOC);


// Nama admin (opsional)
$currentAdminName = $currentAdminName
  ?? ($_SESSION['admin_name'] ?? ($_SESSION['username'] ?? 'Name'));


// Helper kecil untuk buat URL gambar kamera dari camera_images
$buildCameraImgUrl = function($cameraId, $filename) use ($IMG_URL_BASE) {
  $cameraId = (int)$cameraId;
  $filename = (string)$filename;
  if ($cameraId <= 0 || $filename === '') return '';
  $base = rtrim($IMG_URL_BASE, '/');          // uploads/cameras
  return $base . '/' . $cameraId . '/' . ltrim($filename, '/');
};
?>
<!-- ========================= UI (sesuai mockup) ========================= -->
<style>
:root{
  --bg:#F2F4F7; --card:#fff; --text:#34343c; --muted:#6b7280;
  --brand:#4877AF; --brand-200:#BDCEE2; --bluepanel:#6F89B6; --radius:20px;
}
*{box-sizing:border-box}
body{background:var(--bg); font-family:"Poppins",system-ui,Segoe UI,Roboto,Arial}
a{text-decoration:none;color:inherit}


/* Main container only (tanpa sidebar, agar mudah di-embed ke layoutmu) */
.main{padding:28px 30px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.header h1{font-size:28px;font-weight:700;color:var(--text);margin:0}
.pill{display:inline-flex;align-items:center;gap:10px;background:var(--brand-200);padding:8px 14px;border-radius:19px;font-weight:700;color:#284466;font-size:12px}
.pill select{border:none;background:transparent;font:inherit;color:inherit;outline:none;cursor:pointer}
.section-title{font-size:26px;font-weight:700;color:var(--text);margin:22px 0 14px}


/* KPI */
.kpis{display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:18px}
.kpi{background:var(--card);border-radius:19px;padding:18px 22px;box-shadow:0 2px 8px rgba(16,24,40,.06)}
.kpi .label{color:#284466;font-weight:600;font-size:13px}
.kpi .value{font-size:44px;font-weight:800;line-height:1;color:#34343b;margin:8px 0}
.badge{display:inline-block;background:var(--brand);color:#fff;font-size:10px;padding:4px 10px;border-radius:16px;margin-left:10px}
.prev{color:#284466;font-size:13px}


/* Middle - Grid 2 kolom sejajar */
.mid{display:grid;grid-template-columns:1fr 1.1fr;gap:28px;align-items:start}


/* Top Rented - Kolom kiri */
.toprented {
  padding-left: 10px;  /* Geser seluruh section ke kanan dikit */
}
.toprented .section-title {
  margin-top: 35px;
  margin-bottom: 44px;
  font-size: 35px;
}

.toprented .cards{
  display:flex;
  gap:22px;                 /* diperkecil supaya 3 kartu muat sejajar */
  align-items:flex-start;
  justify-content:flex-start;
  flex-wrap:wrap;
}

/* Wrapper untuk card dan badge */
.rentcard-wrap{ 
  width:200px;              /* disamakan dengan rentcard */
  position:relative;
}

/* Rentcard */
.rentcard{
  width:200px;
  height:200px;
  border-radius:16px;
  background:#e9eef6;
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;
  overflow:hidden;
  box-shadow:0 1px 4px rgba(16,24,40,.05);
}

.rentcard img{ 
  width:100%; 
  height:100%; 
  object-fit:cover; 
  display:block; 
}

/* Badge ranking */
.rank{
  position:absolute;
  top:-8px;
  left:-8px;
  background:var(--brand);
  color:#fff;
  min-width:32px;
  height:24px;
  padding:0 8px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  font-size:11px;
  box-shadow:0 2px 8px rgba(72,119,175,.4);
  z-index:10;
}

/* Warna berbeda per ranking */
.rentcard-wrap:nth-child(1) .rank{
  background:#4877AF;
}
.rentcard-wrap:nth-child(2) .rank{
  background:#5F76E8;
}
.rentcard-wrap:nth-child(3) .rank{
  background:#6F89B6;
}

.rank sup{
  font-size:8px;
  margin-left:1px;
  font-weight:700;
}

.rentname{
  margin-top:8px;
  font-size:15px;
  color:var(--text);
  min-height:40px;
  line-height:1.25;
}

.rentname strong{
  font-size:14px;
}


/* Performance panel - Kolom kanan */
.mid section:last-child .section-title {
  margin-top: 30px;
  margin-bottom: 16px;
  font-size: 35px;
}

.panel{
  background:var(--bluepanel);
  border-radius:19px;
  padding:16px;
  box-shadow:0 2px 8px rgba(16,24,40,.06);
  display:flex;
  flex-direction:column;
  gap:10px;
  height:360px;
}

.panel .canvaswrap{
  flex:1;
  position:relative;
  min-height:0;
}

.panel .canvaswrap canvas{
  position:absolute !important;
  top:0;
  left:0;
  width:100% !important;
  height:100% !important;
}


/* Recent table */
.tablewrap{margin-top:24px;background:#fff;border-radius:20px;box-shadow:0 2px 8px rgba(16,24,40,.06);overflow:hidden}
.thead{background:rgba(72,119,175,.21);display:grid;grid-template-columns:1fr 1.2fr 1.5fr 1fr 1fr;padding:16px 22px;font-weight:700;color:#34343c}
.trow{display:grid;grid-template-columns:1fr 1.2fr 1.5fr 1fr 1fr;padding:14px 22px;align-items:center;border-bottom:1px solid #f1f5f9}
.trow:last-child{border-bottom:none}
.status{padding:6px 12px;border-radius:12px;font-size:12px;font-weight:600;color:#fff;text-transform:capitalize;display:inline-block}
.s-verified{background:#4caf50}.s-pending{background:#ff9800}.s-rejected{background:#f44336}


/* Responsive */
@media (max-width:1200px){ .mid{grid-template-columns:1fr} }
@media (max-width:768px){
  .kpis{grid-template-columns:1fr}
  .thead{display:none}
  .trow{grid-template-columns:1fr;gap:6px}
}
</style>


<div class="main">
  <div class="header">
    <h1 class="section-title">Sales Overview</h1>
    <div class="pill">
      <form method="get" action="" style="margin:0;display:flex;align-items:center;gap:8px">
        <input type="hidden" name="page" value="dashboard">
        <select name="month" onchange="this.form.submit()">
          <?php for($i=0;$i<12;$i++): $m=(new DateTime())->modify("-$i month")->format('Y-m'); ?>
            <option value="<?= $m ?>" <?= $m===$mParam?'selected':'' ?>>
              <?= (new DateTime($m))->format('F Y') ?>
            </option>
          <?php endfor; ?>
        </select>
      </form>
    </div>
  </div>


  <div class="kpis">
    <div class="kpi">
      <div class="label">Total Sales</div>
      <div><span class="value"><?= number_format($total_sales) ?></span><span class="badge"><?= $mom_sales ?></span></div>
      <div class="prev">Last Month: <?= number_format($total_sales_prev) ?></div>
    </div>
    <div class="kpi">
      <div class="label">Total Revenue</div>
      <div><span class="value"><?= idr_compact($total_revenue) ?></span><span class="badge"><?= $mom_rev ?></span></div>
      <div class="prev">Last Month: <?= idr_compact($total_revenue_prev) ?></div>
    </div>
    <div class="kpi">
      <div class="label">New Customers</div>
      <div><span class="value"><?= number_format($new_customers) ?></span><span class="badge"><?= $mom_cust ?></span></div>
      <div class="prev">Last Month: <?= number_format($new_customers_prev) ?></div>
    </div>
  </div>


  <div class="mid">
    <!-- Top Rented - Kolom Kiri -->
    <section class="toprented">
      <h2 class="section-title">Top Rented</h2>
      <div class="cards">
        <?php $ordTxt=['st','nd','rd']; foreach ($top_rented as $i=>$t): ?>
          <div class="rentcard-wrap">
            <!-- Badge di luar rentcard -->
            <div class="rank"><?= $i+1 ?><sup><?= $ordTxt[$i]??'th' ?></sup></div>
            
            <!-- Rentcard -->
            <div class="rentcard">
              <?php
                $rawFilename = $t['img_filename'] ?? '';
                $camId       = $t['img_camera_id'] ?? $t['id'];
                $src         = $buildCameraImgUrl($camId, $rawFilename);
              ?>
              <?php if (!empty($src)): ?>
                <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
              <?php else: ?>
                <!-- Placeholder icon -->
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="#9aa7bd" stroke-width="2"/>
                  <path d="M21 15l-5-5-6 6-3-3-4 4" stroke="#9aa7bd" stroke-width="2"/>
                </svg>
              <?php endif; ?>
            </div>
            
            <div class="rentname">
              <strong><?= htmlspecialchars($t['name']) ?></strong><br>
              <small><?= (int)$t['cnt'] ?>x rented</small>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>


    <!-- Performance Overview - Kolom Kanan -->
    <section>
      <h2 class="section-title">Performance Overview</h2>
      <div class="panel">
        <div class="canvaswrap">
          <canvas id="performanceChart"></canvas>
        </div>
      </div>
    </section>
  </div>


  <!-- Recent Rental -->
  <section style="margin-top:22px">
    <h2 class="section-title">Recent Rental</h2>
    <div class="tablewrap">
      <div class="thead">
        <div>Invoice</div><div>Customer</div><div>Camera Name</div><div>Daily Price</div><div>Status</div>
      </div>
      <?php foreach($recent_orders as $o):
        $inv = str_pad($o['id'],5,'0',STR_PAD_LEFT);
        $statusRaw = strtolower($o['rental_status'] ?? 'pending');

        // Mapping warna badge berdasarkan rentals.status
        $statusClassMap = [
          'pending'   => 's-pending',
          'confirmed' => 's-verified',
          'ongoing'   => 's-verified',
          'completed' => 's-verified',
          'cancelled' => 's-rejected',
          'canceled'  => 's-rejected',
          'expired'   => 's-rejected',
        ];
        $cls = $statusClassMap[$statusRaw] ?? 's-pending';
        $txt = ucfirst($statusRaw);
      ?>
        <div class="trow">
          <div>#<?= $inv ?></div>
          <div><?= htmlspecialchars($o['customer']) ?></div>
          <div><?= htmlspecialchars($o['camera_name']) ?></div>
          <div>Rp <?= number_format($o['daily_price'],0,',','.') ?></div>
          <div><span class="status <?= $cls ?>"><?= $txt ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const ctx = document.getElementById('performanceChart');
if (ctx){
  const gradientSales = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
  gradientSales.addColorStop(0, 'rgba(95, 118, 232, 0.3)');
  gradientSales.addColorStop(1, 'rgba(95, 118, 232, 0.02)');
  
  const gradientRevenue = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
  gradientRevenue.addColorStop(0, 'rgba(255, 107, 129, 0.3)');
  gradientRevenue.addColorStop(1, 'rgba(255, 107, 129, 0.02)');
  
  new Chart(ctx, {
    type:'line',
    data:{
      labels: <?= json_encode($labels) ?>,
      datasets:[
        { 
          label:'Total Sales',
          data: <?= json_encode($ord_series) ?>,
          borderColor: '#5F76E8',
          backgroundColor: gradientSales,
          fill: true,
          tension: 0.45,
          borderWidth: 3,
          pointRadius: 0,
          pointHoverRadius: 8,
          pointBackgroundColor: '#5F76E8',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 3,
          pointHoverBackgroundColor: '#5F76E8',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 3
        },
        { 
          label:'Total Revenue',
          data: <?= json_encode($rev_series) ?>,
          borderColor: '#FF6B81',
          backgroundColor: gradientRevenue,
          fill: true,
          tension: 0.45,
          borderWidth: 3,
          pointRadius: 0,
          pointHoverRadius: 8,
          pointBackgroundColor: '#FF6B81',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 3,
          pointHoverBackgroundColor: '#FF6B81',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 3
        }
      ]
    },
    options:{
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          top: 10,
          right: 15,
          bottom: 5,
          left: 5
        }
      },
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            color: '#ffffff',
            usePointStyle: true,
            pointStyle: 'circle',
            padding: 15,
            font: {
              size: 11,
              family: 'Poppins',
              weight: '600'
            },
            boxWidth: 10,
            boxHeight: 10,
            generateLabels: function(chart) {
              const datasets = chart.data.datasets;
              return datasets.map((dataset, i) => ({
                text: dataset.label,
                fillStyle: dataset.borderColor,
                strokeStyle: dataset.borderColor,
                lineWidth: 2,
                hidden: !chart.isDatasetVisible(i),
                index: i,
                pointStyle: 'circle'
              }));
            }
          }
        },
        tooltip: {
          enabled: true,
          backgroundColor: 'rgba(255, 255, 255, 0.98)',
          titleColor: '#34343c',
          bodyColor: '#34343c',
          borderColor: '#e5e7eb',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          displayColors: true,
          titleFont: {
            size: 12,
            family: 'Poppins',
            weight: '700'
          },
          bodyFont: {
            size: 11,
            family: 'Poppins',
            weight: '500'
          },
          usePointStyle: true,
          boxWidth: 8,
          boxHeight: 8,
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              if (context.parsed.y !== null) {
                if (context.datasetIndex === 1) {
                  if (context.parsed.y >= 1000000) {
                    label += 'Rp ' + (context.parsed.y/1000000).toFixed(1) + 'M';
                  } else if (context.parsed.y >= 1000) {
                    label += 'Rp ' + (context.parsed.y/1000).toFixed(0) + 'K';
                  } else {
                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                  }
                } else {
                  label += context.parsed.y + ' Sales';
                }
              }
              return label;
            }
          }
        }
      },
      scales:{
        x: {
          grid: {
            display: true,
            color: 'rgba(255, 255, 255, 0.1)',
            drawBorder: false,
            lineWidth: 1
          },
          ticks: {
            color: '#e9edf7',
            font: {
              size: 10,
              family: 'Poppins',
              weight: '500'
            },
            padding: 8
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(255, 255, 255, 0.12)',
            drawBorder: false,
            lineWidth: 1
          },
          ticks: {  
            color: '#e9edf7',
            font: {
              size: 10,
              family: 'Poppins',
              weight: '500'
            },
            padding: 10,
            callback: function(value) {
              if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
              if (value >= 1000) return (value/1000).toFixed(0) + 'K';
              return value;
            }
          }
        }
      },
      animation: {
        duration: 1800,
        easing: 'easeInOutCubic'
      }
    }
  });
}
</script>
