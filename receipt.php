<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

/* ===================== KONEKSI DATABASE ===================== */
$paths = [
    __DIR__ . '/database/db.php',
    __DIR__ . '/Database/db.php',
    __DIR__ . '/db.php',
    __DIR__ . '/includes/db.php',
];
$found = false;
foreach ($paths as $p) {
    if (is_file($p)) { require_once $p; $found = true; break; }
}
if (!$found) {
    http_response_code(500);
    echo "Tidak menemukan file database/db.php. Pastikan path benar relatif dari receipt.php.";
    exit;
}

/* Deteksi koneksi */
$USE_PDO    = isset($pdo)  && ($pdo instanceof PDO);
$USE_MYSQLI = isset($conn) && ($conn instanceof mysqli);
if (!$USE_PDO && !$USE_MYSQLI) {
    http_response_code(500);
    echo "Koneksi DB tidak tersedia. Pastikan database/db.php membuat \$pdo (PDO) atau \$conn (MySQLi).";
    exit;
}

/* ===================== AMBIL PARAMETER RENTAL ===================== */
// Dilewatkan dari payment/checkout: ?id=CAMERA_ID&start=YYYY-MM-DD&end=YYYY-MM-DD
$camera_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$start_raw = $_GET['start'] ?? ($_GET['start_date'] ?? null);
$end_raw   = $_GET['end']   ?? ($_GET['end_date']   ?? null);

/* Optional: nama customer & metode pembayaran dari query string */
$customer_name   = trim($_GET['name']   ?? '');
$payment_method  = trim($_GET['method'] ?? '');
if ($customer_name === '') {
    $customer_name = 'SnapRent Customer';
}
if ($payment_method === '') {
    $payment_method = 'Credit Card'; // default, bisa diganti "E-banking" kalau mau
}

/* VALIDASI DASAR */
if (!$camera_id || !$start_raw || !$end_raw) {
    http_response_code(400);
    echo "Data rental tidak lengkap. Pastikan URL berisi ?id=...&start=...&end=....";
    exit;
}

/* ===================== AMBIL DATA KAMERA DARI DB ===================== */
$camera = null;
if ($USE_PDO) {
    $st = $pdo->prepare("
        SELECT id, name, brand, type, daily_price
        FROM cameras
        WHERE id = :id
        LIMIT 1
    ");
    $st->execute([':id' => $camera_id]);
    $camera = $st->fetch(PDO::FETCH_ASSOC);
} else {
    $st = $conn->prepare("
        SELECT id, name, brand, type, daily_price
        FROM cameras
        WHERE id = ?
        LIMIT 1
    ");
    $st->bind_param("i", $camera_id);
    $st->execute();
    $res = $st->get_result();
    $camera = $res ? $res->fetch_assoc() : null;
}

if (!$camera) {
    http_response_code(404);
    echo "Kamera tidak ditemukan.";
    exit;
}

/* ===================== HITUNG LAMA SEWA & TOTAL ===================== */
try {
    $start_dt = new DateTime($start_raw);
    $end_dt   = new DateTime($end_raw);
} catch (Exception $e) {
    http_response_code(400);
    echo "Format tanggal tidak valid.";
    exit;
}

// durasi minimal 1 hari
$days = (int)$start_dt->diff($end_dt)->days;
if ($days <= 0) {
    $days = 1;
}

// daily_price di DB biasanya DECIMAL/string → ubah ke float lalu int
$daily_rate = (int)round((float)$camera['daily_price']);

// deposit (samakan dengan payment.php)
$security_deposit = 50000;

// TOTAL BAYAR (disimpan ke DB dalam bentuk angka, ditampilkan format rupiah)
$total = ($daily_rate * $days) + $security_deposit;

// periode untuk tampilan, contoh: "Oct 27 – Oct 30 (3 days)"
$rental_period = $start_dt->format('M d') . ' – ' . $end_dt->format('M d') . ' (' . $days . ' days)';

// tanggal untuk disimpan ke DB (DATETIME)
$start_db = $start_dt->format('Y-m-d 00:00:00');
$end_db   = $end_dt->format('Y-m-d 23:59:59');

// helper format rupiah
function formatRupiah($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/* ===================== TENTUKAN CUSTOMER ID ===================== */
/*
   Ideal: customer login → simpan di $_SESSION['customer_id'].
   Untuk sementara, kalau belum ada session, pakai ID demo (misal: 3)
   supaya tidak error NOT NULL di kolom customer_id.
*/
$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 3;

/* ===================== SIMPAN / PASTIKAN DATA DI TABLE rentals ===================== */
$rental_id = null;

if ($USE_PDO) {
    // Cek dulu apakah sudah ada rental yang sama (anti dobel kalau user refresh)
    $check = $pdo->prepare("
        SELECT id FROM rentals
        WHERE customer_id = :cid
          AND camera_id   = :cam
          AND start_date  = :sd
          AND end_date    = :ed
        LIMIT 1
    ");
    $check->execute([
        ':cid' => $customer_id,
        ':cam' => $camera_id,
        ':sd'  => $start_db,
        ':ed'  => $end_db,
    ]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $rental_id = (int)$existing['id'];
    } else {
        $ins = $pdo->prepare("
            INSERT INTO rentals (customer_id, staff_id, camera_id, start_date, end_date, total_price, status, late_fee)
            VALUES (:cid, NULL, :cam, :sd, :ed, :total, 'confirmed', 0.00)
        ");
        $ins->execute([
            ':cid'   => $customer_id,
            ':cam'   => $camera_id,
            ':sd'    => $start_db,
            ':ed'    => $end_db,
            ':total' => $total,
        ]);
        $rental_id = (int)$pdo->lastInsertId();
    }
} else {
    // MySQLi
    $check = $conn->prepare("
        SELECT id FROM rentals
        WHERE customer_id = ?
          AND camera_id   = ?
          AND start_date  = ?
          AND end_date    = ?
        LIMIT 1
    ");
    $check->bind_param("iiss", $customer_id, $camera_id, $start_db, $end_db);
    $check->execute();
    $res = $check->get_result();
    $existing = $res ? $res->fetch_assoc() : null;

    if ($existing) {
        $rental_id = (int)$existing['id'];
    } else {
        $ins = $conn->prepare("
            INSERT INTO rentals (customer_id, staff_id, camera_id, start_date, end_date, total_price, status, late_fee)
            VALUES (?, NULL, ?, ?, ?, ?, 'confirmed', 0.00)
        ");
        $ins->bind_param("iissd", $customer_id, $camera_id, $start_db, $end_db, $total);
        $ins->execute();
        $rental_id = (int)$conn->insert_id;
    }
}

/* ===================== SUSUN ARRAY $transaction UNTUK TAMPILAN ===================== */
$order_number   = 'SR-' . $camera_id . '-' . date('YmdHis');
$transaction_id = 'TXN-' . ($rental_id ?: date('YmdHis')); // kalau ada rental_id, pakai, kalau tidak fallback waktu
$date_time      = date('F d, Y – H:i');

$item_label = trim(($camera['brand'] ?? '') . ' ' . ($camera['name'] ?? ''));
if ($item_label === '') {
    $item_label = 'Camera #' . $camera_id;
}
$item_label .= ' x 1';

$transaction = [
    'order_number'   => '#' . $order_number,
    'customer_name'  => $customer_name,
    'item'           => $item_label,
    'rental_period'  => $rental_period,
    'price_per_day'  => formatRupiah($daily_rate),
    'total'          => formatRupiah($total),
    'payment_method' => $payment_method,
    'payment_status' => 'Success',
    'transaction_id' => $transaction_id,
    'date_time'      => $date_time,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - SnapRent</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #fff;
            color: #333;
            line-height: 1.6;
        }

        .header-step {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }

        .step {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }

        .step-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .step-circle.completed {
            background-color: #00e676;
            color: white;
        }

        .step-circle.current {
            background-color: #2c3e50;
            color: white;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background-color: #ccc;
            margin: 0 10px;
        }

        .step-line.completed {
            background-color: #00e676;
        }

        .step-label {
            font-size: 12px;
            color: #666;
        }

        .step-label.completed {
            color: #00e676;
        }

        .step-label.current {
            color: #2c3e50;
        }

        .success-banner {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 50px 20px;
            margin-bottom: 40px;
        }

        .success-icon {
            font-size: 80px;
            color: #4fc3f7;
            margin-bottom: 20px;
        }

        .success-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .success-subtitle {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .success-date {
            font-size: 14px;
            opacity: 0.8;
        }

        .receipt-card {
            max-width: 600px;
            margin: 0 auto 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .receipt-header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 1px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .logo::before, .logo::after {
            content: "○";
            font-size: 16px;
        }

        .receipt-subtitle {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .receipt-body {
            padding: 30px;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;
        }

        .value {
            text-align: right;
        }

        .section-title {
            font-weight: bold;
            margin: 20px 0 10px;
            font-size: 18px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #2c3e50;
            color: white;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            color: #2c3e50;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-print {
            display: inline-block;
            width: auto;
            margin-right: 10px;
        }

        .btn-new {
            display: inline-block;
            width: auto;
            margin-left: 10px;
        }

        .footer-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
            padding: 30px;
        }

        .footer-title {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .footer-contact {
            font-size: 16px;
            line-height: 1.8;
        }

        .secure-note {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .icon-print {
            margin-right: 5px;
        }

        .icon-plus {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Progress Step -->
    <div class="header-step">
        <div class="step">
            <div class="step-circle completed">✓</div>
            <span class="step-label completed">Payment Method</span>
        </div>
        <div class="step-line completed"></div>
        <div class="step">
            <div class="step-circle completed">✓</div>
            <span class="step-label completed">Checkout</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-circle current">3</div>
            <span class="step-label current">Confirmation</span>
        </div>
    </div>

    <!-- Success Banner -->
    <div class="success-banner">
        <div class="success-icon">✓</div>
        <h1 class="success-title">Payment Successful!</h1>
        <p class="success-subtitle">Your camera rental has been confirmed</p>
        <p class="success-date">
            Transaction completed securely on <?php echo htmlspecialchars($transaction['date_time'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>

    <!-- Receipt Card -->
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="logo">SNAPRENT</div>
            <div class="receipt-subtitle">ORDER SUMMARY</div>
        </div>
        <div class="receipt-body">
            <div class="receipt-row">
                <span class="label">Order Number:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['order_number'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Customer Name:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['customer_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="section-title">Item Details</div>
            <div class="receipt-row">
                <span><?php echo htmlspecialchars($transaction['item'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Rental Period:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['rental_period'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Price per Day:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['price_per_day'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Total:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['total'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="section-title">Payment Details</div>
            <div class="receipt-row">
                <span class="label">Payment Method:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['payment_method'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Payment Status:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['payment_status'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="label">Transaction ID:</span>
                <span class="value"><?php echo htmlspecialchars($transaction['transaction_id'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <button class="btn btn-primary">Download PDF Receipt</button>

            <div style="display: flex; justify-content: center; gap: 10px;">
                <button class="btn btn-primary btn-print">
                    <span class="icon-print">🖨️</span> Print Receipt
                </button>
                <button class="btn btn-secondary btn-new">
                    <span class="icon-plus">➕</span> New Rental
                </button>
            </div>
        </div>
    </div>

    <!-- Footer Card -->
    <div class="footer-card">
        <div class="footer-title">THANK YOU FOR RENTING WITH US!</div>
        <div class="footer-contact">
            <strong>LENSPOINT CAMERA RENTAL</strong><br>    
            Jl. Studio Raya No. 45, Jakarta<br>
            Contact: +62 812 3456 7890
        </div>
        <div class="secure-note">
            🔒 Your payment is processed securely
        </div>
    </div>

    <script>
        document.querySelector('.btn-print').addEventListener('click', function() {
            window.print();
        });

        document.querySelector('.btn-new').addEventListener('click', function() {
             window.location.href = 'index-cameras.php';
        });
    </script>
</body>
</html>
