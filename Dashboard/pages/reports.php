<?php
// ======================================================================
// reports.php — SnapRent Reports (Analytic & Insight)
// ======================================================================

// ===================== 1. PERFORMANCE OVERVIEW (Last 7 Days) =====================
$labels_7d  = [];
$revenue_7d = [];
$orders_7d  = [];
$map        = [];

for ($i = 6; $i >= 0; $i--) {
  $d = (new DateTime("today -{$i} days"))->format('Y-m-d');
  $labels_7d[] = (new DateTime($d))->format('D');   // Mon, Tue, dst
  $map[$d] = ['rev' => 0.0, 'ord' => 0];
}

// Revenue dari payments
$sqlRev = "
  SELECT DATE(paid_at) AS d, SUM(amount) AS amt
  FROM payments
  WHERE status = 'verified'
    AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(paid_at)
";
foreach ($pdo->query($sqlRev) as $r) {
  if (isset($map[$r['d']])) {
    $map[$r['d']]['rev'] = (float)$r['amt'];
  }
}

// Orders dari rentals
$sqlOrd = "
  SELECT DATE(created_at) AS d, COUNT(*) AS c
  FROM rentals
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(created_at)
";
foreach ($pdo->query($sqlOrd) as $r) {
  if (isset($map[$r['d']])) {
    $map[$r['d']]['ord'] = (int)$r['c'];
  }
}

foreach ($map as $v) {
  $revenue_7d[] = $v['rev'];
  $orders_7d[]  = $v['ord'];
}


// ===================== 2. CUSTOMER SATISFACTION (reviews) =====================
$rowCsat = $pdo->query("SELECT AVG(rating) AS avg_rating FROM reviews")
               ->fetch(PDO::FETCH_ASSOC);

$csatAverage = $rowCsat && $rowCsat['avg_rating'] !== null
  ? (float)$rowCsat['avg_rating']
  : 0.0;

$csatBuckets = [5,4,3,2,1];
$csatCountsAssoc = [5=>0,4=>0,3=>0,2=>0,1=>0];

$sqlCsatDist = "SELECT rating, COUNT(*) AS c FROM reviews GROUP BY rating";
foreach ($pdo->query($sqlCsatDist) as $r) {
  $rate = (int)$r['rating'];
  if (isset($csatCountsAssoc[$rate])) {
    $csatCountsAssoc[$rate] = (int)$r['c'];
  }
}

$csatCounts = [];
foreach ($csatBuckets as $b) {
  $csatCounts[] = $csatCountsAssoc[$b];
}


// ===================== 3. STOCK OVERVIEW (cameras) =====================
$stockBreakdown = [
  "Rent"           => 0,
  "Available"      => 0,
  "Under Treatment"=> 0,
];

$sqlStock = "SELECT status, COUNT(*) AS c FROM cameras GROUP BY status";
foreach ($pdo->query($sqlStock) as $row) {
  if ($row['status'] === 'available')      $stockBreakdown['Available']       = (int)$row['c'];
  if ($row['status'] === 'unavailable')    $stockBreakdown['Rent']            = (int)$row['c'];
  if ($row['status'] === 'maintenance')    $stockBreakdown['Under Treatment'] = (int)$row['c'];
}


// ===================== 4. LATE RETURNS SUMMARY (rentals) =====================
// Logika:
// - On time: semua rentals dengan status = 'returned'
// - Late   : rentals yang status BUKAN 'returned' DAN end_date < hari ini (sudah lewat tanggal pengembalian)
$rowLate = $pdo->query("
  SELECT
    SUM(
      CASE
        WHEN status = 'returned' THEN 1
        ELSE 0
      END
    ) AS ontime_cnt,
    SUM(
      CASE
        WHEN status <> 'returned' AND end_date < CURDATE() THEN 1
        ELSE 0
      END
    ) AS late_cnt
  FROM rentals
")->fetch(PDO::FETCH_ASSOC);

$lateReturn = [
  "Late"    => (int)($rowLate['late_cnt'] ?? 0),
  "On time" => (int)($rowLate['ontime_cnt'] ?? 0)
];


// ===================== 5. TOP RENTED (30 hari terakhir) =====================
$sqlTop = "
  SELECT 
    c.id,
    c.name,
    c.brand,
    COUNT(*) AS cnt
  FROM rentals rn
  JOIN cameras c ON c.id = rn.camera_id
  WHERE rn.start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND rn.status IN ('confirmed','rented','returned')
  GROUP BY c.id, c.name, c.brand
  ORDER BY cnt DESC
  LIMIT 10
";
$top_products = $pdo->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ===================== CONTENT AREA (DESIGN MOCKUP) ===================== -->
<style>
  .sr-page{
    font-family:'Poppins',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    background:#e5ecf6;
    width:100%;
    padding:14px 18px 18px;
    min-height:calc(100vh - 80px);
    box-sizing:border-box;
  }
  .sr-page *{box-sizing:border-box;}

  .sr-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:10px;
  }
  .sr-title-wrap h1{
    margin:0;
    font-size:26px;
    font-weight:800;
    color:#111827;
  }
  .sr-title-wrap .sr-subtitle{
    margin-top:2px;
    font-size:12px;
    color:#6b7280;
  }

  .sr-actions{
    display:flex;
    gap:8px;
  }
  .sr-btn{
    border-radius:10px;
    padding:6px 12px;
    font-size:12px;
    font-weight:600;
    border:1px solid #cbd4e6;
    background:#dbe3f5;
    color:#111827;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:6px;
    box-shadow:0 4px 10px rgba(15,23,42,.12);
    text-decoration:none;
  }
  .sr-btn.secondary{
    background:#f3f4f6;
    border-color:#d1d5db;
    box-shadow:none;
  }

  .sr-dd-wrap{position:relative;}
  .sr-dd{
    position:absolute;
    right:0;
    top:32px;
    min-width:130px;
    background:#ffffff;
    border-radius:10px;
    border:1px solid #d1d5db;
    box-shadow:0 10px 24px rgba(15,23,42,.18);
    padding:4px 0;
    display:none;
    z-index:40;
  }
  .sr-dd a{
    display:block;
    padding:6px 10px;
    font-size:12px;
    color:#111827;
    text-decoration:none;
  }
  .sr-dd a:hover{background:#f3f4f6;}

  .sr-grid-top{
    display:grid;
    grid-template-columns:2.1fr 1fr;
    gap:12px;
  }
  .sr-grid-bottom{
    display:grid;
    grid-template-columns:1fr 1fr 1.3fr;
    gap:12px;
    margin-top:10px;
  }

  .sr-card{
    background:#ffffff;
    border-radius:24px;
    padding:10px 14px 10px;
    border:1px solid #d7dfef;
    box-shadow:0 14px 30px rgba(15,23,42,.08);
    display:flex;
    flex-direction:column;
  }
  .sr-card h3{
    margin:0;
    font-size:15px;
    font-weight:700;
    color:#111827;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .sr-chip{
    background:#edf2ff;
    color:#1d4ed8;
    font-size:10px;
    padding:3px 8px;
    border-radius:999px;
    font-weight:600;
  }

  /* HEADER DI DALAM CARD (UNTUK SEE DETAILS DI KANAN) */
  .sr-card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:4px;
    gap:8px;
  }
  .sr-card-header .sr-btn{
    padding:5px 10px;
    font-size:11px;
    box-shadow:none;
  }

  /* INI BAGIAN PENTING: kita pastikan canvas mengisi tinggi card */
  .sr-chart{
    flex:1;
    min-height:180px;
    max-height:210px;
  }

  /* ==== PERPANJANG KHUSUS 2 KARTU ATAS ==== */
  .sr-grid-top .sr-card {
      min-height: 420px; /* tambah tinggi container */
  }

  .sr-grid-top .sr-card .sr-chart {
      min-height: 260px; /* tambah tinggi grafik */
      max-height: 260px;
  }

  .sr-chart canvas{
    width:100% !important;
    height:100% !important;
  }

  .sr-csat-value{
    text-align:right;
    font-size:20px;
    font-weight:700;
    margin-bottom:2px;
    color:#111827;
  }
  .sr-csat-value span{
    font-size:13px;
    font-weight:500;
    color:#6b7280;
  }

  .sr-legend{
    display:flex;
    gap:12px;
    font-size:11px;
    color:#4b5563;
    margin-top:4px;
  }
  .sr-dot{
    width:9px;
    height:9px;
    border-radius:999px;
    display:inline-block;
    margin-right:4px;
  }

  .sr-topr-list{
    margin-top:4px;
    display:flex;
    flex-direction:column;
    gap:6px;
  }
  .sr-topr-item{
    display:grid;
    grid-template-columns:auto 1fr auto;
    gap:8px;
    align-items:center;
    padding:7px 9px;
    border-radius:14px;
    border:1px solid #d4d7e5;
    background:#f8fbff;
  }
  .sr-rank{
    width:24px;
    height:24px;
    border-radius:9px;
    background:#111827;
    color:#f9fafb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:700;
  }
  .sr-cam-name{
    font-size:12px;
    font-weight:600;
    color:#111827;
  }
  .sr-cam-meta{
    font-size:10px;
    color:#6b7280;
  }
  .sr-cam-count{
    font-size:11px;
    font-weight:700;
    color:#111827;
  }

  @media(max-width:1100px){
    .sr-grid-top,.sr-grid-bottom{grid-template-columns:1fr;}
    .sr-header{flex-direction:column;gap:8px;}
    .sr-actions{align-self:flex-end;}
  }
</style>

<div class="sr-page">
  <!-- Header -->
  <div class="sr-header">
    <div class="sr-title-wrap">
      <h1>Reports</h1>
      <div class="sr-subtitle">Analytic &amp; insight</div>
    </div>
    <div class="sr-actions">
      <button class="sr-btn secondary" onclick="window.print();return false;">Export</button>
      <div class="sr-dd-wrap">
        <button id="srBtnExport" class="sr-btn">
          Export <span style="font-size:11px;">▼</span>
        </button>
        <div id="srDd" class="sr-dd">
          <a href="#" onclick="window.print();return false;">PDF</a>
          <a href="#" onclick="srExportCSV();return false;">Excel (CSV)</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Top row: Performance + CSAT -->
  <div class="sr-grid-top">
    <section class="sr-card">
      <div class="sr-card-header">
        <h3>Performance Overview <span class="sr-chip">Last 7 Days</span></h3>
      </div>
      <div class="sr-chart">
        <canvas id="srPerf"></canvas>
      </div>
      <div class="sr-legend">
        <span><span class="sr-dot" style="background:#b91c1c;"></span>Total Sales</span>
        <span><span class="sr-dot" style="background:#4338ca;"></span>Total Revenue</span>
      </div>
    </section>

    <section class="sr-card">
      <div class="sr-card-header">
        <h3>Customer Satisfaction Overview</h3>
        <a href="?page=reviews" class="sr-btn secondary">
          See Details <span style="font-size:11px; margin-left:4px;">→</span>
        </a>
      </div>

      <div class="sr-csat-value">
        <?= number_format($csatAverage,1) ?><span>/5</span>
      </div>

      <div class="sr-chart">
        <canvas id="srCsat"></canvas>
      </div>
    </section>
  </div>

  <!-- Bottom row: Stock, Late, Top Rented -->
  <div class="sr-grid-bottom">
    <section class="sr-card">
      <h3>Stock Overview</h3>
      <div class="sr-chart">
        <canvas id="srStock"></canvas>
      </div>
      <div class="sr-legend">
        <span><span class="sr-dot" style="background:#b91c1c;"></span>Rent</span>
        <span><span class="sr-dot" style="background:#1d4ed8;"></span>Available</span>
        <span><span class="sr-dot" style="background:#16a34a;"></span>Under Treatment</span>
      </div>
    </section>

    <section class="sr-card">
      <h3>Late Returns Summary</h3>
      <div class="sr-chart">
        <canvas id="srLate"></canvas>
      </div>
      <div class="sr-legend">
        <span><span class="sr-dot" style="background:#b91c1c;"></span>Late</span>
        <span><span class="sr-dot" style="background:#1d4ed8;"></span>On time</span>
      </div>
    </section>

    <section class="sr-card">
      <h3>Top Rented</h3>
      <div class="sr-topr-list">
        <?php
        $rank = 1;
        foreach ($top_products as $p):
          if ($rank > 4) break;
          $name  = htmlspecialchars($p['name']);
          $brand = htmlspecialchars($p['brand'] ?? '');
          $cnt   = (int)$p['cnt'];
        ?>
          <div class="sr-topr-item">
            <div class="sr-rank"><?= $rank ?>.</div>
            <div>
              <div class="sr-cam-name"><?= $name ?></div>
              <?php if ($brand): ?>
                <div class="sr-cam-meta"><?= $brand ?></div>
              <?php endif; ?>
            </div>
            <div class="sr-cam-count"><?= $cnt ?>x disewa</div>
          </div>
        <?php
          $rank++;
        endforeach;
        if ($rank === 1): ?>
          <div style="font-size:12px;color:#6b7280;">Belum ada data rental 30 hari terakhir.</div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>

<!-- ===================== Chart.js CDN ===================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // ===== Dropdown Export =====
  (function(){
    const btn = document.getElementById('srBtnExport');
    const dd  = document.getElementById('srDd');
    if (!btn || !dd) return;
    btn.addEventListener('click', e => {
      e.preventDefault();
      dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', e => {
      if (!btn.contains(e.target) && !dd.contains(e.target)) {
        dd.style.display = 'none';
      }
    });
  })();

  // ===== Data dari PHP =====
  const perfLabels = <?= json_encode($labels_7d) ?>;
  const perfSales  = <?= json_encode($orders_7d) ?>;
  const perfRev    = <?= json_encode($revenue_7d) ?>;
  const csatLabels = <?= json_encode($csatBuckets) ?>;
  const csatCounts = <?= json_encode($csatCounts) ?>;
  const stockLabels= <?= json_encode(array_keys($stockBreakdown)) ?>;
  const stockVals  = <?= json_encode(array_values($stockBreakdown)) ?>;
  const lateLabels = <?= json_encode(array_keys($lateReturn)) ?>;
  const lateVals   = <?= json_encode(array_values($lateReturn)) ?>;

  // helper opsi supaya semua chart padat & tanpa space berlebihan
  const baseChartOpts = {
    responsive:true,
    maintainAspectRatio:false,
    layout:{padding:{top:4,right:6,bottom:4,left:6}},
  };

  // ===== Charts =====
  new Chart(document.getElementById('srPerf'),{
    type:'line',
    data:{
      labels:perfLabels,
      datasets:[
        {
          label:'Total Sales',
          data:perfSales,
          borderColor:'#b91c1c',
          backgroundColor:'rgba(185,28,28,0.10)',
          borderWidth:2,
          pointRadius:3,
          tension:.35
        },
        {
          label:'Total Revenue',
          data:perfRev,
          borderColor:'#4338ca',
          backgroundColor:'rgba(67,56,202,0.10)',
          borderWidth:2,
          pointRadius:3,
          tension:.35
        }
      ]
    },
    options:{
      ...baseChartOpts,
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{color:'#e5e7eb'}, ticks:{font:{size:11}}},
        y:{grid:{color:'#e5e7eb'}, ticks:{font:{size:11}}}
      }
    }
  });

  new Chart(document.getElementById('srCsat'),{
    type:'bar',
    data:{
      labels:csatLabels,
      datasets:[{
        data:csatCounts,
        backgroundColor:'#3b82f6',
        borderRadius:7,
        maxBarThickness:40
      }]
    },
    options:{
      ...baseChartOpts,
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false}, ticks:{font:{size:11}}},
        y:{grid:{color:'#e5e7eb'}, ticks:{font:{size:11}, precision:0}}
      }
    }
  });

  new Chart(document.getElementById('srStock'),{
    type:'pie',
    data:{
      labels:stockLabels,
      datasets:[{
        data:stockVals,
        backgroundColor:['#b91c1c','#1d4ed8','#16a34a']
      }]
    },
    options:{
      ...baseChartOpts,
      plugins:{legend:{display:false}}
    }
  });

  new Chart(document.getElementById('srLate'),{
    type:'doughnut',
    data:{
      labels:lateLabels,
      datasets:[{
        data:lateVals,
        backgroundColor:['#b91c1c','#1d4ed8'],
        borderWidth:0
      }]
    },
    options:{
      ...baseChartOpts,
      cutout:'60%',
      plugins:{legend:{display:false}}
    }
  });

  // ===== Export CSV Top Rented =====
  function srExportCSV(){
    const rows = [['Camera','Brand','Times Rented']];
    <?php foreach($top_products as $p): ?>
      rows.push([
        <?= json_encode($p['name']) ?>,
        <?= json_encode($p['brand']) ?>,
        <?= (int)$p['cnt'] ?>
      ]);
    <?php endforeach; ?>

    const csv = rows
      .map(r => r.map(v => `"${String(v).replaceAll('"','""')}"`).join(','))
      .join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = 'top_rented.csv';
    a.click();
    URL.revokeObjectURL(url);
  }
</script>
