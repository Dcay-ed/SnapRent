<?php
// ===================== ERROR & SESSION =====================
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* ===================== CEK LOGIN CUSTOMER ===================== */
$customerId = $_SESSION['uid'] ?? ($_SESSION['user_id'] ?? null);
$role       = $_SESSION['role'] ?? '';

if (!$customerId || $role !== 'CUSTOMER') {
  // kalau mau tetap bawa parameter rental, bisa tambahkan redirect dengan query string
  header("Location: auth/login.php");
  exit;
}

// ===================== KONEKSI DATABASE =====================
// cari file koneksi yang biasa dipakai (sama pola dengan details.php / payment.php)
$paths = [
  __DIR__ . '/database/db.php',
  __DIR__ . '/Database/db.php',
  __DIR__ . '/db.php',
  __DIR__ . '/includes/db.php',
];
$found = false;
foreach ($paths as $p) {
  if (is_file($p)) { 
    require_once $p; 
    $found = true; 
    break; 
  }
}
if (!$found) {
  http_response_code(500);
  echo "Tidak menemukan file database/db.php. Pastikan path benar relatif dari checkout.php.";
  exit;
}

// deteksi koneksi
$USE_PDO    = isset($pdo)  && ($pdo instanceof PDO);
$USE_MYSQLI = isset($conn) && ($conn instanceof mysqli);
if (!$USE_PDO && !$USE_MYSQLI) {
  http_response_code(500);
  echo "Koneksi DB tidak tersedia. Pastikan database/db.php membuat \$pdo (PDO) atau \$conn (MySQLi).";
  exit;
}

// ===================== DATA RENTAL DARI details.php =====================
$cameraId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$startRaw = $_GET['start'] ?? ($_GET['start_date'] ?? null);
$endRaw   = $_GET['end']   ?? ($_GET['end_date']   ?? null);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// hitung lama sewa (hari)
$days = null;
if ($startRaw && $endRaw) {
  try {
    $startDt = new DateTime($startRaw);
    $endDt   = new DateTime($endRaw);
    $diff    = (int)$startDt->diff($endDt)->days;
    $days    = max(1, $diff); // minimal 1 hari
  } catch (Exception $e) {
    $days = null;
  }
}

// ===================== AMBIL DATA KAMERA (REAL) =====================
$camera = null;
if ($cameraId) {
  if ($USE_PDO) {
    $st = $pdo->prepare("
      SELECT id, name, brand, type, daily_price 
      FROM cameras 
      WHERE id = :id 
      LIMIT 1
    ");
    $st->execute([':id' => $cameraId]);
    $camera = $st->fetch(PDO::FETCH_ASSOC);
  } else {
    $st = $conn->prepare("
      SELECT id, name, brand, type, daily_price 
      FROM cameras 
      WHERE id = ? 
      LIMIT 1
    ");
    $st->bind_param("i", $cameraId);
    $st->execute();
    $res = $st->get_result();
    $camera = $res ? $res->fetch_assoc() : null;
  }
}

// nama kamera / fallback
$cameraTitle = 'Camera #'.$cameraId;
if ($camera && !empty($camera['name'])) {
  $cameraTitle = $camera['name'];
}

// hitung estimasi total harga (kalau data lengkap)
$totalPrice = null;
if ($camera && $days !== null) {
  $daily = (float)$camera['daily_price'];
  if ($daily > 0) {
    $totalPrice = $daily * $days;
  }
}

// helper rupiah
function rp($n){
  return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

// ===================== BUILD QUERY STRING KE payment.php =====================
$params = [];
if ($cameraId) $params['id']    = $cameraId;
if ($startRaw) $params['start'] = $startRaw;
if ($endRaw)   $params['end']   = $endRaw;
if ($days !== null) $params['days'] = $days;

$query = http_build_query($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Method</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 20px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
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
    
    /* Header section outside the container */
    .header-section {
      max-width: 800px;
      width: 100%;
      margin-bottom: 20px;
    }
    
    .step-indicator {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .step {
      display: flex;
      align-items: center;
    }
    
    .step-number {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: #333;
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      margin-right: 10px;
      transition: all 0.3s ease;
    }
    
    .step-number.inactive {
      background-color: #aaa;
    }
    
    .step-text {
      margin-right: 20px;
      font-size: 14px;
    }
    
    .step-line {
      width: 60px;
      height: 2px;
      background-color: #ccc;
      margin: 0 10px;
    }
    
    .page-title {
      text-align: center;
      color: #333;
      margin-bottom: 5px;
    }
    
    .page-subtitle {
      text-align: center;
      color: #666;
    }
    
    /* Main container */
    .container {
      max-width: 800px;
      width: 100%;
      margin: 0 auto;
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      position: relative;
      z-index: 1;
    }
    
    .payment-options {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .payment-option {
      background-color: #f5f5f5;
      padding: 20px;
      border-radius: 8px;
      cursor: pointer;
      position: relative;
      transition: all 0.3s ease;
    }
    
    .payment-option:hover {
      background-color: #e9e9e9;
      transform: translateY(-3px);
      box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }
    
    .payment-option.selected {
      background-color: #e0e0e0;
      border: 2px solid #333;
    }
    
    .radio-button {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 20px;
      height: 20px;
      border: 2px solid #666;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      transition: all 0.3s ease;
    }
    
    .radio-button.selected {
      background-color: #333;
      border-color: #333;
    }
    
    .radio-button.selected::after {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: white;
    }
    
    .payment-icon {
      display: flex;
      justify-content: center;
      margin-bottom: 10px;
      height: 50px;
      align-items: center;
    }
    
    .payment-icon i {
      font-size: 40px;
      color: #333;
    }
    
    .payment-label {
      text-align: center;
      font-weight: bold;
      color: #333;
    }
    
    .terms-container {
      display: flex;
      align-items: center;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    
    .terms-checkbox {
      margin-right: 10px;
      width: 18px;
      height: 18px;
    }
    
    .terms-text {
      font-size: 14px;
    }
    
    .terms-link {
      color: #0066cc;
      text-decoration: none;
    }
    
    .terms-link:hover {
      text-decoration: underline;
    }
    
    .next-button-container {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
    }
    
    .next-button {
      background-color: #333;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .next-button:hover {
      background-color: #555;
    }
    
    .next-button:disabled {
      background-color: #aaa;
      cursor: not-allowed;
    }
    
    /* Responsive design */
    @media (max-width: 600px) {
      .payment-options {
        grid-template-columns: 1fr;
      }
      
      .step-indicator {
        flex-wrap: wrap;
      }
      
      .step-line {
        width: 30px;
      }
    }
  </style>
</head>
<body>
  <!-- Animated background elements -->
  <div class="bg-element"></div>
  <div class="bg-element"></div>
  <div class="bg-element"></div>
  <div class="bg-element"></div>
  
  <!-- Header section outside the container -->
  <div class="header-section">
    <div class="step-indicator">
      <div class="step">
        <div class="step-number">1</div>
        <div class="step-text">Cameras Details</div>
      </div>
      <div class="step-line"></div>
      <div class="step">
        <div class="step-number inactive">2</div>
        <div class="step-text">Checkout</div>
      </div>
      <div class="step-line"></div>
      <div class="step">
        <div class="step-number inactive">3</div>
        <div class="step-text">Confirmation</div>
      </div>
    </div>
    
    <h1 class="page-title">Payment Method</h1>
    <p class="page-subtitle">
      Choose Payment Method •
      <?php if ($cameraId && $startRaw && $endRaw): ?>
        <?php if ($camera): ?>
          <?= e($cameraTitle) ?>,
        <?php else: ?>
          Camera #<?= e($cameraId) ?>,
        <?php endif; ?>
        <?= e($startRaw) ?> – <?= e($endRaw) ?>
        <?php if ($days !== null): ?>
          (<?= e($days) ?> hari
          <?php if ($totalPrice !== null): ?>
            • Est. <?= e(rp($totalPrice)) ?>
          <?php endif; ?>)
        <?php endif; ?>
      <?php else: ?>
        (Data rental tidak lengkap)
      <?php endif; ?>
    </p>
  </div>
  
  <!-- Main container -->
  <div class="container">
    <div class="payment-options">
      <div class="payment-option" onclick="selectPayment(this)">
        <div class="radio-button"></div>
        <div class="payment-icon">
          <i class="fa-solid fa-credit-card"></i>
        </div>
        <div class="payment-label">Credit/debit card</div>
      </div>
      
      <div class="payment-option" onclick="selectPayment(this)">
        <div class="radio-button"></div>
        <div class="payment-icon">
          <i class="fa-solid fa-mobile-screen-button"></i>
        </div>
        <div class="payment-label">OVO</div>
      </div>
      
      <div class="payment-option" onclick="selectPayment(this)">
        <div class="radio-button"></div>
        <div class="payment-icon">
          <i class="fa-brands fa-google-wallet"></i>
        </div>
        <div class="payment-label">Gopay</div>
      </div>
      
      <div class="payment-option" onclick="selectPayment(this)">
        <div class="radio-button"></div>
        <div class="payment-icon">
          <i class="fa-solid fa-building-columns"></i>
        </div>
        <div class="payment-label">Bank</div>
      </div>
    </div>
    
    <div class="terms-container">
      <input type="checkbox" id="terms" class="terms-checkbox">
      <label for="terms" class="terms-text">
        I agree to the <a href="#" class="terms-link">terms and conditions</a>
      </label>
    </div>
    
    <div class="next-button-container">
      <button
        id="nextButton"
        class="next-button"
        disabled
        onclick="window.location.href='payment.php?<?= e($query) ?>'">
        Next <i class="fa-solid fa-arrow-right"></i>
      </button>
    </div>
  </div>

  <script>
    function selectPayment(element) {
      // Remove 'selected' class from all options
      document.querySelectorAll(".payment-option").forEach(option => {
        option.classList.remove("selected");
        option.querySelector(".radio-button").classList.remove("selected");
      });
      
      // Add 'selected' class to clicked option
      element.classList.add("selected");
      element.querySelector(".radio-button").classList.add("selected");
      
      // Check if payment method is selected and terms are agreed
      checkFormValidity();
    }

    function checkFormValidity() {
      const selectedPayment = document.querySelector(".payment-option.selected");
      const termsChecked   = document.getElementById("terms").checked;
      const nextButton     = document.getElementById("nextButton");
      
      // Enable button if payment method is selected and terms are agreed
      if (selectedPayment && termsChecked) {
        nextButton.disabled = false;
      } else {
        nextButton.disabled = true;
      }
    }

    // Event listener for checkbox
    document.getElementById("terms").addEventListener("change", checkFormValidity);

    function proceedToNext() {
      // Placeholder kalau nanti mau dipakai
      alert("Proceeding to next step...");
    }
  </script>
</body>
</html>
