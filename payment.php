<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* ===================== KONEKSI DATABASE ===================== */
/* Sesuaikan path dengan file lain (index.php, details.php, dll.) */
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
  echo "Tidak menemukan file database/db.php. Pastikan path benar relatif dari checkout.php.";
  exit;
}

/* deteksi koneksi */
$USE_PDO    = isset($pdo)  && ($pdo instanceof PDO);
$USE_MYSQLI = isset($conn) && ($conn instanceof mysqli);
if (!$USE_PDO && !$USE_MYSQLI) {
  http_response_code(500);
  echo "Koneksi database tidak ditemukan. Pastikan database/db.php mengisi \$pdo atau \$conn.";
  exit;
}

/* ===================== AMBIL PARAMETER ===================== */
// id kamera
$camera_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// tanggal sewa (dukung beberapa nama param)
$start_raw = $_GET['start']      ?? $_GET['start_date'] ?? null;
$end_raw   = $_GET['end']        ?? $_GET['end_date']   ?? null;

if (!$camera_id || !$start_raw || !$end_raw) {
  http_response_code(400);
  echo "Bad request: id kamera atau tanggal sewa tidak lengkap.";
  exit;
}

/* ===================== AMBIL DATA KAMERA ===================== */
if ($USE_PDO) {
  $stmt = $pdo->prepare("SELECT id, name, brand, type, daily_price FROM cameras WHERE id = :id");
  $stmt->execute([':id' => $camera_id]);
  $camera = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  $stmt = $conn->prepare("SELECT id, name, brand, type, daily_price FROM cameras WHERE id = ?");
  $stmt->bind_param("i", $camera_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $camera = $result->fetch_assoc();
}

if (!$camera) {
  http_response_code(404);
  echo "Kamera tidak ditemukan.";
  exit;
}

/* ===================== HITUNG HARI & TOTAL (+ DISKON 15% JIKA 7+ HARI) ===================== */
try {
  $start_dt = new DateTime($start_raw);
  $end_dt   = new DateTime($end_raw);
} catch (Exception $e) {
  http_response_code(400);
  echo "Format tanggal tidak valid.";
  exit;
}

// durasi sewa (minimal 1 hari)
$days = (int)$start_dt->diff($end_dt)->days;
if ($days <= 0) {
  $days = 1;
}

// harga harian dari DB (decimal string -> float -> int)
$daily_rate = (int)round((float)$camera['daily_price']); // contoh: "120000.00" -> 120000

// deposit bisa kamu ubah nanti kalau sudah ada aturan di DB
$security_deposit = 50000;

// SUBTOTAL: sebelum diskon
$subtotal = $daily_rate * $days;

// DISKON 15% kalau sewa 7 hari atau lebih
$discount_rate   = ($days >= 7) ? 0.15 : 0;
$discount_amount = (int)round($subtotal * $discount_rate);

// TOTAL: subtotal - diskon + deposit
$total = $subtotal - $discount_amount + $security_deposit;

// format tanggal untuk tampilan
$start_date = $start_dt->format('M d, Y');
$end_date   = $end_dt->format('M d, Y');

// perkiraan jam ambil & kembali
$pickup_date = $start_dt->format('M d, Y') . ' at 10:00 AM';
$return_date = $end_dt->format('M d, Y') . ' by 6:00 PM';

// nama & tipe kamera untuk tampilan
$camera_name = $camera['name'];
$camera_type = $camera['type'] ?: $camera['brand'];

// query string untuk dilempar (kalau mau dipakai lagi)
$query = http_build_query([
  'id'    => $camera_id,
  'start' => $start_raw,
  'end'   => $end_raw,
]);

// Fungsi format uang Rupiah
function formatRupiah($amount) {
  return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background elements */
        .bg-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.03);
            animation: float 20s infinite linear;
            z-index: -1;
        }
        
        .bg-element:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }
        
        .bg-element:nth-child(2) {
            width: 150px;
            height: 150px;
            bottom: 15%;
            right: 8%;
            animation-delay: 5s;
        }
        
        .bg-element:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 60%;
            left: 10%;
            animation-delay: 10s;
        }
        
        .bg-element:nth-child(4) {
            width: 120px;
            height: 120px;
            top: 20%;
            right: 12%;
            animation-delay: 15s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.7;
            }
            25% {
                transform: translateY(-20px) rotate(90deg);
                opacity: 0.9;
            }
            50% {
                transform: translateY(0) rotate(180deg);
                opacity: 0.7;
            }
            75% {
                transform: translateY(20px) rotate(270deg);
                opacity: 0.9;
            }
            100% {
                transform: translateY(0) rotate(360deg);
                opacity: 0.7;
            }
        }

        /* Header untuk Progress Bar dan Judul */
        .header-section {
            width: 100%;
            max-width: 800px;
            margin-bottom: 20px;
            text-align: center;
        }

        h1 {
            font-weight: bold;
            margin-bottom: 30px;
            color: #2d3748;
        }

        /* Progress Bar */
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
        }
        .step {
            display: flex;
            align-items: center;
            position: relative;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
        }
        .step-label {
            font-size: 14px;
            white-space: nowrap;
        }
        .step-line {
            flex: 1;
            height: 2px;
            background-color: #cbd5e0;
            margin: 0 10px;
        }

        .step.active .step-circle {
            background-color: #2ecc71;
            color: white;
        }
        .step.active .step-label {
            color: #2ecc71;
        }
        .step.completed .step-circle {
            background-color: #2ecc71;
            color: white;
        }
        .step.completed .step-label {
            color: #2ecc71;
        }
        .step.current .step-circle {
            background-color: #2d3748;
            color: white;
        }
        .step.current .step-label {
            color: #2d3748;
        }
        .step.inactive .step-label {
            color: #94a3b8;
        }
        .step.inactive .step-circle {
            background-color: #94a3b8;
            color: white;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }

        /* Card Styling */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .card-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2d3748;
        }

        .rental-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .rental-image {
            width: 80px;
            height: 80px;
            background-color: #a0aec0;
            border-radius: 8px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            text-align: center;
        }
        .rental-info h3 {
            font-size: 20px;
            margin: 0 0 5px 0;
            color: #2d3748;
        }
        .rental-info p {
            font-size: 14px;
            color: #718096;
            margin: 0;
        }

        .rental-dates {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #718096;
            margin-top: 10px;
        }

        .summary-list {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary-label {
            font-size: 16px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }

        .total-row {
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
        }

        .order-summary .summary-label {
            color: #4a5568;
        }
        .order-summary .summary-value {
            color: #2d3748;
        }
        .order-summary .total-row .summary-label {
            color: #3182ce;
        }
        .order-summary .total-row .summary-value {
            color: #3182ce;
        }

        .date-info {
            margin-top: 20px;
            font-size: 14px;
            color: #4a5568;
        }
        .date-info strong {
            display: block;
            margin-top: 5px;
            color: #2d3748;
        }

        .continue-button {
            background-color: #333;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        
        .continue-button:hover {
            background-color: #555;
        }
        
        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 600px) {
            .rental-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .rental-image {
                margin-bottom: 10px;
            }
            .summary-row {
                flex-direction: column;
                gap: 5px;
            }
            .progress-bar {
                flex-direction: column;
                gap: 10px;
            }
            .step-line {
                display: none;
            }
        }
    </style>
</head>
<body>
<div id="loadingOverlay" 
     style="
        position: fixed;
        top:0; left:0;
        width:100%; height:100%;
        background: rgba(255,255,255,0.85);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:22px;
        color:#333;
        z-index:9999;
        display:none;
     ">
    <div style="text-align:center;">
        <div class="loader" 
             style="
                border:6px solid #f3f3f3;
                border-top:6px solid #333;
                border-radius:50%;
                width:50px;
                height:50px;
                animation:spin 1s linear infinite;
                margin:0 auto 15px auto;
             ">
        </div>
        Processing your payment...
    </div>
</div>
    <!-- Animated background elements -->
    <div class="bg-element"></div>
    <div class="bg-element"></div>
    <div class="bg-element"></div>
    <div class="bg-element"></div>
    
    <!-- Header Section (Progress Bar & Title) -->
    <div class="header-section">
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="step completed">
                <div class="step-circle">✓</div>
                <span class="step-label">Cameras Details</span>
            </div>
            <div class="step-line"></div>
            <div class="step current">
                <div class="step-circle">2</div>
                <span class="step-label">Checkout</span>
            </div>
            <div class="step-line"></div>
            <div class="step inactive">
                <div class="step-circle">3</div>
                <span class="step-label">Confirmation</span>
            </div>
        </div>

        <h1>Secure Checkout</h1>
    </div>

    <!-- Main Content Container -->
    <div class="container">

        <!-- Rental Summary -->
        <div class="card">
            <div class="card-title">Rental Summary</div>
            <div class="rental-item">
                <div class="rental-image">Camera Image</div>
                <div class="rental-info">
                    <h3><?= htmlspecialchars($camera_name) ?></h3>
                    <p><?= htmlspecialchars($camera_type) ?></p>
                </div>
            </div>
            <div class="rental-dates">
                <span><?= htmlspecialchars($start_date) ?> → <?= htmlspecialchars($end_date) ?></span>
                <span><?= (int)$days ?> Days</span>
            </div>

            <div class="summary-list">
                <div class="summary-row">
                    <span class="summary-label">Daily rate x <?= (int)$days ?> days</span>
                    <span class="summary-value"><?= formatRupiah($subtotal) ?></span>
                </div>

                <?php if ($discount_amount > 0): ?>
                <div class="summary-row">
                    <span class="summary-label">Weekly Discount (15% for 7+ days)</span>
                    <span class="summary-value">-<?= formatRupiah($discount_amount) ?></span>
                </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span class="summary-label">Rental Period</span>
                    <span class="summary-value"><?= (int)$days ?> Days</span>
                </div>
                <div class="summary-row total-row">
                    <span class="summary-label">Total</span>
                    <span class="summary-value"><?= formatRupiah($total) ?></span>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="card order-summary">
            <div class="card-title">Order Summary</div>
            <div class="summary-list">
                <div class="summary-row">
                    <span class="summary-label">Rental Period</span>
                    <span class="summary-value"><?= (int)$days ?> Days</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Daily Rate</span>
                    <span class="summary-value"><?= formatRupiah($daily_rate) ?></span>
                </div>

                <?php if ($discount_amount > 0): ?>
                <div class="summary-row">
                    <span class="summary-label">Weekly Discount (7+ days)</span>
                    <span class="summary-value">-<?= formatRupiah($discount_amount) ?></span>
                </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span class="summary-label">Security Deposit</span>
                    <span class="summary-value"><?= formatRupiah($security_deposit) ?></span>
                </div>
                <div class="summary-row total-row">
                    <span class="summary-label">Total</span>
                    <span class="summary-value"><?= formatRupiah($total) ?></span>
                </div>
            </div>
        
            <div class="date-info">
                <strong>Estimated Pick-up:</strong>
                <?= htmlspecialchars($pickup_date) ?>
            </div>
            <div class="date-info">
                <strong>Return Date:</strong>
                <?= htmlspecialchars($return_date) ?>
            </div>
        </div>
        
        <!-- Continue Button -->
        <div class="button-container">
            <button class="continue-button" onclick="goToPayment()">
                Continue to Payment
            </button>
        </div>
    <script>
function goToPayment() {
    // Tampilkan overlay loading
    document.getElementById('loadingOverlay').style.display = 'flex';

    // Redirect setelah 1.5 detik
    setTimeout(() => {
        window.location.href = "receipt.php?<?= $query ?>";
    }, 1500);
}

// animasi spinner
const style = document.createElement('style');
style.innerHTML = `
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);
</script>
    </div>
</body>
</html>
