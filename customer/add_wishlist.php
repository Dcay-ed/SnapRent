<?php
// customer/add_wishlist.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

// ================== CEK LOGIN CUSTOMER ==================
$customerId = $_SESSION['uid'] ?? ($_SESSION['user_id'] ?? null);

if (!$customerId) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit;
}

// ================== KONEKSI DATABASE ==================
$paths = [
    __DIR__ . '/../database/db.php',
    __DIR__ . '/../Database/db.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../includes/db.php',
];
$found = false;
foreach ($paths as $p) {
    if (is_file($p)) { require_once $p; $found = true; break; }
}
if (!$found) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'File koneksi database tidak ditemukan.'
    ]);
    exit;
}

$USE_PDO    = isset($pdo)  && ($pdo instanceof PDO);
$USE_MYSQLI = isset($conn) && ($conn instanceof mysqli);

if (!$USE_PDO && !$USE_MYSQLI) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koneksi DB tidak tersedia.'
    ]);
    exit;
}

// ================== VALIDASI INPUT ==================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Method harus POST.'
    ]);
    exit;
}

$cameraId = $_POST['camera_id'] ?? null;
$cameraId = filter_var($cameraId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$cameraId) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'camera_id tidak valid.'
    ]);
    exit;
}

// ================== CEK SUDAH ADA DI WISHLIST BELUM ==================
$table = 'wishlists'; // ganti kalau nama tabel kamu beda

try {
    if ($USE_PDO) {
        // Cek
        $st = $pdo->prepare("SELECT id FROM {$table} WHERE customer_id = ? AND camera_id = ? LIMIT 1");
        $st->execute([$customerId, $cameraId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // sudah ada -> hapus (remove wishlist)
            $del = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
            $del->execute([$row['id']]);

            echo json_encode([
                'status'      => 'ok',
                'in_wishlist' => false,
                'message'     => 'Removed from wishlist'
            ]);
            exit;
        } else {
            // belum ada -> insert (add wishlist)
            $ins = $pdo->prepare(
                "INSERT INTO {$table} (customer_id, camera_id, created_at) VALUES (?, ?, NOW())"
            );
            $ins->execute([$customerId, $cameraId]);

            echo json_encode([
                'status'      => 'ok',
                'in_wishlist' => true,
                'message'     => 'Added to wishlist'
            ]);
            exit;
        }
    } else {
        // ====== MySQLi versi ======
        // cek
        $stmt = $conn->prepare("SELECT id FROM {$table} WHERE customer_id = ? AND camera_id = ? LIMIT 1");
        $stmt->bind_param('ii', $customerId, $cameraId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            $del = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
            $del->bind_param('i', $row['id']);
            $del->execute();
            $del->close();

            echo json_encode([
                'status'      => 'ok',
                'in_wishlist' => false,
                'message'     => 'Removed from wishlist'
            ]);
            exit;
        } else {
            $ins = $conn->prepare(
                "INSERT INTO {$table} (customer_id, camera_id, created_at) VALUES (?, ?, NOW())"
            );
            $ins->bind_param('ii', $customerId, $cameraId);
            $ins->execute();
            $ins->close();

            echo json_encode([
                'status'      => 'ok',
                'in_wishlist' => true,
                'message'     => 'Added to wishlist'
            ]);
            exit;
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan: '.$e->getMessage()
    ]);
    exit;
}
