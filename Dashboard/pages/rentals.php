<?php
// rentals.php — SnapRent • Rentals page UI (design-only patch, DB structure updated)
// Notes:
// - Does NOT alter your header, footer, or sidebar.
// - Works with an existing $rentals dataset. If not provided, it will try a safe fetch with $pdo.
// - If neither is available, it renders demo rows (so you can see the design immediately).
// - CSV export available via ?export=csv

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('rupiah')) {
  function rupiah($n){
    if ($n === null || $n === '') return 'Rp 0';
    return 'Rp '.number_format((float)$n,0,',','.');
  }
}

// --------------- Data source resolution ---------------
$rows = [];
$had_source = false;

// If controller already set $rentals (use it as-is)
if (isset($rentals) && is_iterable($rentals)) {
  foreach ($rentals as $r) { $rows[] = $r; }
  $had_source = true;
}

// Else, try a minimal best-effort fetch using $pdo (aligned to NEW schema)
if (!$had_source && isset($pdo) && $pdo) {
  try {
    // NEW: joins & aliases mengikuti skema baru:
    // - customers.customer_id (bukan customers.id)
    // - rentals.total_price, rentals.status
    // - camera_images.filename -> 'uploads/<filename>'
    // - payment via view v_rental_payment_status
    $sql = "
      SELECT
        rn.id,
        rn.id                              AS invoice,         -- fallback invoice
        c.full_name                        AS customer,
        cam.brand                          AS camera_brand,
        cam.name                           AS camera_model,
        -- total_days: pakai kolom yang ada; jika tidak ada, hitung dari tanggal
        COALESCE(rn.total_days,
                 rn.duration_days,
                 GREATEST(DATEDIFF(rn.end_date, rn.start_date), 0)) AS total_days,
        COALESCE(rn.total_amount, rn.total_price, 0) AS total_amount,
        rn.status                          AS rental_status,
        COALESCE(v.last_payment_status,
                 CASE WHEN COALESCE(v.paid_amount,0) > 0 THEN 'Paid' ELSE 'Unpaid' END) AS payment_status,
        rn.created_at                      AS created_at,
        (
          SELECT CONCAT('uploads/', ci.filename)
          FROM camera_images ci
          WHERE ci.camera_id = rn.camera_id
          ORDER BY ci.id ASC
          LIMIT 1
        ) AS photo
      FROM rentals rn
      JOIN customers c ON c.customer_id = rn.customer_id
      JOIN cameras   cam ON cam.id = rn.camera_id
      LEFT JOIN v_rental_payment_status v ON v.rental_id = rn.id
      ORDER BY rn.created_at DESC, rn.id DESC
      LIMIT 200
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $had_source = true;
  } catch (Throwable $t) {
    // ignore, will use demo
  }
}

// Demo fallback (design preview)
if (!$had_source || empty($rows)) {
  $rows = [
    ['invoice'=>'F0001','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Confirmed','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0002','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Pending','payment_status'=>'Unpaid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0003','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Confirmed','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0004','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Cancelled','payment_status'=>'Unpaid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0005','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Confirmed','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0006','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Rented','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0007','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Overdue','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0008','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Confirmed','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0009','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Confirmed','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0010','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Returned','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
    ['invoice'=>'F0011','customer'=>'Mala Sukanita','camera_brand'=>'Canon','camera_model'=>'EOS R6','total_days'=>2,'total_amount'=>200000,'rental_status'=>'Confirmed','payment_status'=>'Paid','created_at'=>'2025-10-23','photo'=>null],
  ];
}

// --------------- CSV Export ---------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=rentals_export_'.date('Ymd_His').'.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Invoice','Customer','Camera','Total Day','Total','Rental Status','Payment','Created']);
  foreach ($rows as $i => $r) {
    $camera = trim((($r['camera_brand'] ?? '') . ' ' . ($r['camera_model'] ?? '')));
    fputcsv($out, [
      $r['invoice'] ?? $r['invoice_no'] ?? $r['id'] ?? '',
      $r['customer'] ?? '',
      $camera,
      ($r['total_days'] ?? 0).' Day',
      ($r['total_amount'] ?? 0),
      $r['rental_status'] ?? '',
      $r['payment_status'] ?? '',
      substr((string)($r['created_at'] ?? ''), 0, 10),
    ]);
  }
  fclose($out);
  exit;
}

// --------------- Helpers for UI ---------------
function status_badge_class($status){
  $s = strtolower((string)$status);
  return match(true){
    str_contains($s,'confirm') => 'badge-success',
    str_contains($s,'pend')    => 'badge-warning',
    str_contains($s,'cancel')  => 'badge-danger',
    str_contains($s,'rent')    => 'badge-info',
    str_contains($s,'overdue') => 'badge-gold',
    str_contains($s,'return')  => 'badge-mocha',
    default                    => 'badge-neutral'
  };
}
function pay_badge_class($status){
  $s = strtolower((string)$status);
  return str_contains($s,'paid') || str_contains($s,'verify') ? 'badge-success' : 'badge-danger';
}
?>

<style>
/* ---- Rentals table skin (scoped) ---- */
:root{
  --bg:#f4f6f8;
  --card:#ffffff;
  --line:#e6eaef;
  --text:#2a2f36;
  --muted:#6b7280;
  --success:#22c55e;
  --danger:#ef4444;
  --warning:#f97316;
  --info:#2563eb;
  --gold:#eab308;
  --mocha:#9b6b64;
  --neutral:#64748b;
  --chip-bg:#f3f4f6;
}

.rentals-wrap{ padding: 20px 24px; background: var(--bg); border-radius: 12px; }
.rentals-header{ display:flex; align-items:center; justify-content:space-between; gap: 16px; margin-bottom: 14px; }
.rentals-title{ display:flex; flex-direction:column; }
.rentals-title h1{ font-size: 22px; font-weight:700; color:var(--text); margin:0; }
.rentals-title small{ color: var(--muted); }

.btn-export{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px; border-radius:10px; border:1px solid var(--line);
  background:#e9eef6; color:#1f2937; font-weight:600; text-decoration:none;
}
.btn-export:hover{ filter:brightness(.98); }

.table-card{ background: var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden; }
.table{ width:100%; border-collapse:separate; border-spacing:0; }
.table thead th{
  text-align:left; font-size:13px; letter-spacing:.3px; text-transform:none;
  color:var(--muted); background:#f9fafb; padding:14px 16px; border-bottom:1px solid var(--line);
}
.table tbody td{ padding:16px; border-bottom:1px solid var(--line); color:var(--text); font-size:14px; }
.table tbody tr:hover{ background:#fafafa; }

.col-idx{ width:56px; color:#94a3b8; }
.col-photo{ width:76px; }
.photo-chip{
  width:36px; height:36px; border-radius:50%;
  background:#e5e7eb; display:flex; align-items:center; justify-content:center;
  border:1px solid var(--line); overflow:hidden;
}
.photo-chip img{ width:100%; height:100%; object-fit:cover; }

.badge{
  display:inline-block; padding:6px 10px; font-size:12px; line-height:1; border-radius:8px; font-weight:700;
  background: var(--chip-bg); color:#111827; border:1px solid rgba(0,0,0,0.05);
}
.badge-success{ background:#e8f8ee; color:#066c2d; border-color:#b8e8c9; }
.badge-danger{ background:#fde8e8; color:#9b1c1c; border-color:#f7b4b4; }
.badge-warning{ background:#fff1e6; color:#9a3412; border-color:#ffd1b7; }
.badge-info{ background:#e6efff; color:#1e3a8a; border-color:#c3d2ff; }
.badge-gold{ background:#fef7e5; color:#854d0e; border-color:#fde68a; }
.badge-mocha{ background:#f4e8e6; color:#6b3f39; border-color:#d8b7b1; }
.badge-neutral{ background:#eef2f7; color:#334155; border-color:#d1d5db; }

.currency{ white-space:nowrap; }
.day{ white-space:nowrap; }
.created{ color:#475569; white-space:nowrap; }
.invoice{ color:#334155; font-weight:600; }
.camera{ color:#334155; }
.customer{ color:#111827; }
</style>

<div class="rentals-wrap">
  <div class="rentals-header">
    <div class="rentals-title">
      <h1>Rentals</h1>
      <small>Manage all camera rental orders</small>
    </div>
    <a class="btn-export" href="?export=csv" title="Export as CSV">
      <span>Export</span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="#111827" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>
  </div>

  <div class="table-card">
    <table class="table">
      <thead>
        <tr>
          <th class="col-idx">#</th>
          <th>Invoice</th>
          <th class="col-photo">Photo</th>
          <th>Customer</th>
          <th>Camera</th>
          <th>Total Day</th>
          <th>Total</th>
          <th>Rental Status</th>
          <th>Payment</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = 1;
        foreach ($rows as $r):
          $invoice        = $r['invoice'] ?? $r['invoice_no'] ?? ($r['id'] ?? ('F'.str_pad((string)$i,4,'0',STR_PAD_LEFT)));
          $customer       = $r['customer'] ?? $r['customer_name'] ?? '—';
          $camera         = trim((($r['camera_brand'] ?? '') . ' ' . ($r['camera_model'] ?? '')));
          if ($camera === '') $camera = $r['camera'] ?? '—';
          $total_days     = (int)($r['total_days'] ?? 0);
          $total_amount   = (float)($r['total_amount'] ?? 0);
          $rental_status  = $r['rental_status'] ?? $r['status'] ?? '—';
          $payment_status = $r['payment_status'] ?? '—';
          $created_at     = substr((string)($r['created_at'] ?? ''), 0, 10);
          $photo          = $r['photo'] ?? $r['photo_url'] ?? null;
        ?>
        <tr>
          <td class="col-idx"><?php echo $i++; ?></td>
          <td class="invoice"><?php echo e($invoice); ?></td>
          <td class="col-photo">
            <div class="photo-chip">
              <?php if ($photo): ?>
                <img src="<?php echo e($photo); ?>" alt="photo">
              <?php else: ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 7h4l2-2h4l2 2h4v12H4V7Z" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="13" r="4" stroke="#6b7280" stroke-width="1.5"/>
                </svg>
              <?php endif; ?>
            </div>
          </td>
          <td class="customer"><?php echo e($customer); ?></td>
          <td class="camera"><?php echo e($camera); ?></td>
          <td class="day"><?php echo e($total_days); ?> Day</td>
          <td class="currency"><?php echo rupiah($total_amount); ?></td>
          <td><span class="badge <?php echo status_badge_class($rental_status); ?>"><?php echo e($rental_status); ?></span></td>
          <td><span class="badge <?php echo pay_badge_class($payment_status); ?>"><?php echo e($payment_status); ?></span></td>
          <td class="created"><?php echo e($created_at); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
