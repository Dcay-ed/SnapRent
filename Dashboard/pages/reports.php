<?php
// ====== (Tetap) DATA dari DB 7 hari terakhir ======
$labels_7d = [];
$revenue_7d = [];
$orders_7d = [];
$map = [];

for ($i=6; $i>=0; $i--){
  $d=(new DateTime("today -{$i} days"))->format('Y-m-d');
  $labels_7d[]=(new DateTime($d))->format('D');
  $map[$d]=['rev'=>0,'ord'=>0];
}

foreach ($pdo->query("SELECT DATE(paid_at)d,SUM(amount)amt FROM payments WHERE status='verified' AND paid_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(paid_at)") as $r){
  $map[$r['d']]['rev']=(float)$r['amt'];
}

foreach ($pdo->query("SELECT DATE(created_at)d,COUNT(*)c FROM rentals WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at)") as $r){
  $map[$r['d']]['ord']=(int)$r['c'];
}

foreach ($map as $vals){
  $revenue_7d[]=$vals['rev'];
  $orders_7d[]=$vals['ord'];
}

// ====== (Dummy ringan, TANPA ubah DB) ======
// Boleh Anda ganti dgn query jika sudah tersedia.
$csatBuckets = [5,4,3,2,1];
$csatCounts  = [12,8,10,9,3];
$csatAverage = 4.7;

$stockBreakdown = [
  "Rent" => 50,
  "Available" => 25,
  "Under Treatment" => 25
];

$lateReturn = [
  "Late" => 50,
  "On time" => 50
];

// $top_products sudah ada di file Anda; dipakai di “Top Rented”
?>

<!-- ====== CONTENT AREA SAJA (tidak mengubah header/footer/sidebar) ====== -->
<style>
  /* Scoped agar tidak bentrok */
  .sr-reports *{box-sizing:border-box}
  .sr-reports .page-title{font-size:28px;font-weight:800;margin:0}
  .sr-reports .subtitle{color:#64748b;font-size:13px;margin:4px 0 0}
  .sr-reports .grid{display:grid;gap:18px;grid-template-columns:2.2fr 1fr}
  .sr-reports .row3{display:grid;gap:18px;grid-template-columns:1fr 1fr 1fr;margin-top:18px}
  .sr-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.08);padding:18px}
  .sr-card h5{font-size:16px;margin:0 0 10px}
  .sr-chart{height:280px}
  .sr-chip{background:#e7f2ff;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;color:#1d4ed8;margin-left:8px}
  .sr-top{display:flex;align-items:center;gap:14px;margin-bottom:16px}
  .sr-export{margin-left:auto;position:relative}
  .sr-btn{background:#e8eefc;border:1px solid #cfe0ff;padding:8px 12px;border-radius:12px;font-weight:600;cursor:pointer}
  .sr-dd{position:absolute;right:0;top:40px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 30px rgba(15,23,42,.08);display:none;min-width:140px;overflow:hidden}
  .sr-dd a{display:block;padding:10px 12px;text-decoration:none;color:#111827}
  .sr-dd a:hover{background:#f3f4f6}
  .sr-toprented{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-top:8px}
  .sr-topitem{display:flex;align-items:center;gap:10px;padding:10px;border:1px dashed #d8d8d8;border-radius:14px;background:#fff7f7}
  .sr-thumb{width:48px;height:48px;border-radius:12px;background:#f8fafc;object-fit:cover;border:1px solid #e2e8f0}
  @media(max-width:1100px){.sr-reports .grid{grid-template-columns:1fr}.sr-reports .row3{grid-template-columns:1fr}}
  @media print{.sr-export{display:none}.sr-card{page-break-inside:avoid}}
</style>

<div class="sr-reports">
  <div class="sr-top">
    <div>
      <div class="page-title">Reports</div>
      <div class="subtitle">Analytic &amp; insight</div>
    </div>
    <div class="sr-export">
      <button id="srBtnExport" class="sr-btn">Export ▾</button>
      <div id="srDd" class="sr-dd">
        <a href="#" onclick="window.print();return false;">PDF</a>
        <a href="#" onclick="srExportCSV();return false;">Excel (CSV)</a>
      </div>
    </div>
  </div>

  <div class="grid">
    <!-- Performance Overview -->
    <section class="sr-card">
      <h5>Performance Overview <span class="sr-chip">Last 7 Days</span></h5>
      <div class="sr-chart"><canvas id="srLine"></canvas></div>
    </section>

    <!-- CSAT -->
    <section class="sr-card">
      <h5>Customer Satisfaction Overview</h5>
      <div class="subtitle" style="font-weight:700;margin:0 0 8px"><?php echo number_format($csatAverage,1) ?>/5</div>
      <div class="sr-chart"><canvas id="srBar"></canvas></div>
    </section>
  </div>

  <div class="row3">
    <!-- Stock -->
    <section class="sr-card">
      <h5>Stock Overview</h5>
      <div class="sr-chart"><canvas id="srPie"></canvas></div>
    </section>

    <!-- Late Returns -->
    <section class="sr-card">
      <h5>Late Returns Summary</h5>
      <div class="sr-chart"><canvas id="srDonut"></canvas></div>
    </section>

    <!-- Top Rented -->
    <section class="sr-card">
      <h5>Top Rented</h5>
      <div class="sr-toprented">
        <?php
        // Ambil 4 teratas dari $top_products (sudah ada di file Anda)
        $__i=0;
        foreach ($top_products as $p){
          if ($__i++>=4) break;
          $thumb = "https://placehold.co/80x80/png";
          ?>
          <div class="sr-topitem">
            <img class="sr-thumb" src="<?= htmlspecialchars($thumb) ?>" alt="">
            <div style="font-size:13px;font-weight:600"><?= e($p['name']) ?></div>
          </div>
          <?php
        }
        ?>
      </div>
    </section>
  </div>
</div>

<script>
  // Dropdown export
  (function(){
    const b=document.getElementById('srBtnExport'), d=document.getElementById('srDd');
    if(!b||!d) return;
    b.addEventListener('click', ()=>{ d.style.display = d.style.display==='block'?'none':'block'; });
    document.addEventListener('click', (e)=>{ if(!b.contains(e.target)&&!d.contains(e.target)) d.style.display='none'; });
  })();

  // Data dari PHP
  const perfLabels = <?= json_encode($labels_7d) ?>;
  const perfSales  = <?= json_encode($orders_7d) ?>;
  const perfRev    = <?= json_encode($revenue_7d) ?>;
  const csatLabels = <?= json_encode($csatBuckets) ?>;
  const csatCounts = <?= json_encode($csatCounts) ?>;
  const stockLabels= <?= json_encode(array_keys($stockBreakdown)) ?>;
  const stockVals  = <?= json_encode(array_values($stockBreakdown)) ?>;
  const lateLabels = <?= json_encode(array_keys($lateReturn)) ?>;
  const lateVals   = <?= json_encode(array_values($lateReturn)) ?>;

  // Buat chart hanya jika Chart.js tersedia (di-load oleh footer Anda)
  if (window.Chart){
    new Chart(document.getElementById('srLine'), {
      type:'line',
      data:{ labels: perfLabels, datasets:[
        {label:'Total Sales', data: perfSales, tension:.35, borderWidth:3, pointRadius:3},
        {label:'Total Revenue', data: perfRev, tension:.35, borderWidth:3, pointRadius:3}
      ]},
      options:{responsive:true, plugins:{legend:{position:'top'}},
        scales:{y:{grid:{color:'#eef2f7'}}, x:{grid:{color:'#f5f7fb'}}}}
    });

    new Chart(document.getElementById('srBar'), {
      type:'bar',
      data:{ labels: csatLabels, datasets:[{label:'Reviews', data: csatCounts}] },
      options:{ plugins:{legend:{display:false}} }
    });

    new Chart(document.getElementById('srPie'), {
      type:'pie',
      data:{ labels: stockLabels, datasets:[{ data: stockVals }] },
      options:{ plugins:{legend:{position:'right'}} }
    });

    new Chart(document.getElementById('srDonut'), {
      type:'doughnut',
      data:{ labels: lateLabels, datasets:[{ data: lateVals }] },
      options:{ plugins:{legend:{position:'bottom'}}, cutout:'60%' }
    });
  }

  // Export CSV sederhana (contoh: Top Rented)
  function srExportCSV(){
    const rows = [['Title','Count']];
    <?php foreach($top_products as $p): ?>
      rows.push([<?= json_encode($p['name']) ?>, <?= (int)$p['cnt'] ?>]);
    <?php endforeach; ?>
    const csv = rows.map(r=>r.map(v=>`"${String(v).replaceAll('"','""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href=url; a.download='top_rented.csv'; a.click();
    URL.revokeObjectURL(url);
  }
</script>
