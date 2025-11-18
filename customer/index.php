<?php
session_start();

/* ===================== KONEKSI DATABASE ===================== */
require __DIR__ . '/../database/db.php';

/* ===================== CEK LOGIN & ROLE CUSTOMER ===================== */
if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'CUSTOMER') {
    header("Location: ../auth/login.php");
    exit;
}

$accountId = (int)$_SESSION['uid'];

/* ===================== DETEKSI KONEKSI ===================== */
$USE_PDO    = isset($pdo)  && ($pdo instanceof PDO);
$USE_MYSQLI = isset($conn) && ($conn instanceof mysqli);

$success = '';
$error   = '';

/* ===================== HELPER ===================== */
if (!function_exists('e')) {
    function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/* ===================== AMBIL customer_id UNTUK AKSI ===================== */
$customerIdDb = null;
try {
    if ($USE_PDO) {
        $st = $pdo->prepare("SELECT customer_id FROM accounts WHERE id = :id LIMIT 1");
        $st->execute([':id' => $accountId]);
        $customerIdDb = (int)$st->fetchColumn();
    } elseif ($USE_MYSQLI) {
        if ($stmt = $conn->prepare("SELECT customer_id FROM accounts WHERE id = ? LIMIT 1")) {
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $stmt->bind_result($cid);
            if ($stmt->fetch()) {
                $customerIdDb = (int)$cid;
            }
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    // biarkan null jika gagal
}

/* ===================== HANDLE POST (AKSI) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---------- UPLOAD FOTO ---------- */
    if ($action === 'upload_photo') {
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Gagal mengunggah foto. Coba lagi.';
        } else {
            $tmpName  = $_FILES['profile_photo']['tmp_name'];

            // Validasi mime type
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            } else {
                $mime = mime_content_type($tmpName);
            }

            if (!isset($allowed[$mime])) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
            } else {
                $ext = $allowed[$mime];

                // Folder: /uploads/customers/{customer_id atau accountId}/
                $baseDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'customers';
                if (!is_dir($baseDir)) {
                    @mkdir($baseDir, 0775, true);
                }

                $dirName   = $customerIdDb ?: $accountId;
                $targetDir = $baseDir . DIRECTORY_SEPARATOR . $dirName;
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }

                $newName  = 'profile_' . time() . '.' . $ext;
                $fullPath = $targetDir . DIRECTORY_SEPARATOR . $newName;

                if (!move_uploaded_file($tmpName, $fullPath)) {
                    $error = 'Gagal menyimpan file di server.';
                } else {
                    $relativePath = 'uploads/customers/' . $dirName . '/' . $newName;

                    try {
                        if ($USE_PDO) {
                            $sql = "UPDATE customers SET profile_pic = :pic WHERE customer_id = :cid";
                            $st  = $pdo->prepare($sql);
                            $st->execute([
                                ':pic' => $relativePath,
                                ':cid' => $customerIdDb
                            ]);
                        } elseif ($USE_MYSQLI) {
                            $sql = "UPDATE customers SET profile_pic = ? WHERE customer_id = ?";
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("si", $relativePath, $customerIdDb);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                        $success = 'Foto profil berhasil diperbarui.';
                    } catch (Throwable $e) {
                        $error = 'Terjadi kesalahan saat menyimpan foto di database.';
                    }
                }
            }
        }
    }

    /* ---------- UPDATE USERNAME ---------- */
    if ($action === 'update_username') {
        $newUsername = trim($_POST['username'] ?? '');
        if ($newUsername === '') {
            $error = 'Username tidak boleh kosong.';
        } else {
            try {
                if ($USE_PDO) {
                    $sql = "UPDATE accounts SET username = :u WHERE id = :id";
                    $st  = $pdo->prepare($sql);
                    $st->execute([':u' => $newUsername, ':id' => $accountId]);
                } elseif ($USE_MYSQLI) {
                    $sql = "UPDATE accounts SET username = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("si", $newUsername, $accountId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                $_SESSION['uname'] = $newUsername;
                $success = 'Username berhasil diperbarui.';
            } catch (Throwable $e) {
                $error = 'Gagal memperbarui username.';
            }
        }
    }

    /* ---------- UPDATE EMAIL ---------- */
    if ($action === 'update_email') {
        $newEmail = trim($_POST['email'] ?? '');
        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email tidak valid.';
        } else {
            try {
                if ($USE_PDO) {
                    $sql = "UPDATE accounts SET email = :e WHERE id = :id";
                    $st  = $pdo->prepare($sql);
                    $st->execute([':e' => $newEmail, ':id' => $accountId]);
                } elseif ($USE_MYSQLI) {
                    $sql = "UPDATE accounts SET email = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("si", $newEmail, $accountId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                $success = 'Email berhasil diperbarui.';
            } catch (Throwable $e) {
                $error = 'Gagal memperbarui email.';
            }
        }
    }

    /* ---------- UPDATE PHONE ---------- */
    if ($action === 'update_phone') {
        $newPhone = trim($_POST['phone'] ?? '');
        if ($newPhone === '') {
            $error = 'Nomor telepon tidak boleh kosong.';
        } else {
            try {
                if ($USE_PDO) {
                    $sql = "UPDATE accounts SET phone = :p WHERE id = :id";
                    $st  = $pdo->prepare($sql);
                    $st->execute([':p' => $newPhone, ':id' => $accountId]);
                } elseif ($USE_MYSQLI) {
                    $sql = "UPDATE accounts SET phone = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("si", $newPhone, $accountId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                $success = 'Nomor telepon berhasil diperbarui.';
            } catch (Throwable $e) {
                $error = 'Gagal memperbarui nomor telepon.';
            }
        }
    }

    /* ---------- UPDATE FULL NAME (customers.full_name) ---------- */
    if ($action === 'update_full_name') {
        $newFullName = trim($_POST['full_name'] ?? '');
        if ($newFullName === '') {
            $error = 'Nama lengkap tidak boleh kosong.';
        } else {
            try {
                if ($USE_PDO) {
                    $sql = "UPDATE customers SET full_name = :f WHERE customer_id = :cid";
                    $st  = $pdo->prepare($sql);
                    $st->execute([':f' => $newFullName, ':cid' => $customerIdDb]);
                } elseif ($USE_MYSQLI) {
                    $sql = "UPDATE customers SET full_name = ? WHERE customer_id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("si", $newFullName, $customerIdDb);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                $success = 'Nama lengkap berhasil diperbarui.';
            } catch (Throwable $e) {
                $error = 'Gagal memperbarui nama lengkap.';
            }
        }
    }

    /* ---------- GANTI PASSWORD ---------- */
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $error = 'Semua field password wajib diisi.';
        } elseif ($new !== $confirm) {
            $error = 'Konfirmasi password baru tidak sama.';
        } else {
            try {
                $hashDb = null;
                if ($USE_PDO) {
                    $st = $pdo->prepare("SELECT password FROM accounts WHERE id = :id LIMIT 1");
                    $st->execute([':id' => $accountId]);
                    $hashDb = $st->fetchColumn();
                } elseif ($USE_MYSQLI) {
                    if ($stmt = $conn->prepare("SELECT password FROM accounts WHERE id = ? LIMIT 1")) {
                        $stmt->bind_param("i", $accountId);
                        $stmt->execute();
                        $stmt->bind_result($pwd);
                        if ($stmt->fetch()) {
                            $hashDb = $pwd;
                        }
                        $stmt->close();
                    }
                }

                if (!$hashDb || !password_verify($current, $hashDb)) {
                    $error = 'Password lama salah.';
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    if ($USE_PDO) {
                        $st = $pdo->prepare("UPDATE accounts SET password = :p WHERE id = :id");
                        $st->execute([':p' => $newHash, ':id' => $accountId]);
                    } elseif ($USE_MYSQLI) {
                        if ($stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?")) {
                            $stmt->bind_param("si", $newHash, $accountId);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                    $success = 'Password berhasil diganti.';
                }
            } catch (Throwable $e) {
                $error = 'Gagal mengganti password.';
            }
        }
    }

    /* ---------- HAPUS AKUN ---------- */
    if ($action === 'remove_account') {
        try {
            if ($USE_PDO) {
                $st = $pdo->prepare("DELETE FROM accounts WHERE id = :id");
                $st->execute([':id' => $accountId]);
                // opsional: DELETE FROM customers WHERE customer_id = :cid
            } elseif ($USE_MYSQLI) {
                if ($stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?")) {
                    $stmt->bind_param("i", $accountId);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            session_unset();
            session_destroy();
            header("Location: ../auth/login.php?removed=1");
            exit;
        } catch (Throwable $e) {
            $error = 'Gagal menghapus akun.';
        }
    }
}

/* ===================== AMBIL DATA CUSTOMER DARI DATABASE ===================== */
$user = [
    'username'      => $_SESSION['uname'] ?? 'Customer',
    'email'         => '-',
    'phone'         => '-',
    'full_name'     => null,
    'address'       => null,
    'customer_code' => null,
    'profile_pic'   => 'https://via.placeholder.com/150?text=Profile+Photo',
    'customer_id'   => null,
];

try {
    if ($USE_PDO) {
        $sql = "
            SELECT 
                a.username,
                a.email,
                COALESCE(a.phone, '-') AS phone,
                c.full_name,
                c.address,
                c.customer_code,
                c.customer_id,
                c.profile_pic
            FROM accounts a
            LEFT JOIN customers c 
                   ON c.customer_id = a.customer_id
            WHERE a.id = :id
            LIMIT 1
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $accountId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $user['username']      = $row['username']      ?? $user['username'];
            $user['email']         = $row['email']         ?? $user['email'];
            $user['phone']         = $row['phone']         ?? $user['phone'];
            $user['full_name']     = $row['full_name']     ?? null;
            $user['address']       = $row['address']       ?? null;
            $user['customer_code'] = $row['customer_code'] ?? null;
            $user['customer_id']   = $row['customer_id']   ?? null;

            if (!empty($row['profile_pic'])) {
                $pic = $row['profile_pic'];
                if (preg_match('~^https?://~i', $pic) || str_starts_with($pic, '//')) {
                    $user['profile_pic'] = $pic;
                } else {
                    $user['profile_pic'] = '../' . ltrim($pic, '/');
                }
            }
        }

    } elseif ($USE_MYSQLI) {
        $sql = "
            SELECT 
                a.username,
                a.email,
                COALESCE(a.phone, '-') AS phone,
                c.full_name,
                c.address,
                c.customer_code,
                c.customer_id,
                c.profile_pic
            FROM accounts a
            LEFT JOIN customers c 
                   ON c.customer_id = a.customer_id
            WHERE a.id = ?
            LIMIT 1
        ";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $user['username']      = $row['username']      ?? $user['username'];
                $user['email']         = $row['email']         ?? $user['email'];
                $user['phone']         = $row['phone']         ?? $user['phone'];
                $user['full_name']     = $row['full_name']     ?? null;
                $user['address']       = $row['address']       ?? null;
                $user['customer_code'] = $row['customer_code'] ?? null;
                $user['customer_id']   = $row['customer_id']   ?? null;

                if (!empty($row['profile_pic'])) {
                    $pic = $row['profile_pic'];
                    if (preg_match('~^https?://~i', $pic) || str_starts_with($pic, '//')) {
                        $user['profile_pic'] = $pic;
                    } else {
                        $user['profile_pic'] = '../' . ltrim($pic, '/');
                    }
                }
            }
            $res->free();
            $stmt->close();
        }
    }

} catch (Throwable $e) {
    // fallback diam
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Info - SnapRent</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .alert {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        .alert.success {
            background: #e6ffed;
            color: #137333;
            border: 1px solid #c3f3d3;
        }
        .alert.error {
            background: #ffe8e6;
            color: #c5221f;
            border: 1px solid #ffb3ad;
        }
        .upload-form {
            display: inline-block;
        }
        /* form hidden tidak ganggu desain lama */
        .hidden-form {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Sidebar -->
        <aside class="sidebar">
            <nav>
                <!-- BACK TO HOME di paling atas -->
                <a href="../index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>

                <!-- Menu utama customer -->
                <a href="index.php" class="active">
                    <i class="fas fa-users"></i>
                    <span>Profile</span>
                </a>
                <a href="booking.php">
                    <i class="far fa-folder-open"></i>
                    <span>Booking</span>
                </a>
                <a href="notification.php">
                    <i class="far fa-bell"></i>
                    <span>Notification</span>
                </a>
            </nav>

            <!-- Logout BENAR-BENAR di luar nav supaya bisa nempel bawah -->
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h1>Personal Info</h1>

            <?php if ($success): ?>
                <div class="alert success"><?= e($success) ?></div>
            <?php elseif ($error): ?>
                <div class="alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="profile-header">
                <img src="<?= e($user['profile_pic']) ?>" 
                     alt="Profile Picture" class="profile-pic">

                <h2><?= e($user['username']) ?></h2>

                <!-- TETAP BUTTON Upload Photo seperti desain, tapi pakai form hidden -->
                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="action" value="upload_photo">
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" style="display:none" required>
                    <button type="button" class="upload-btn">Upload Photo</button>
                </form>
            </div>

            <div class="info-card">

                <!-- USERNAME (accounts.username) – desain lama: label + p + tombol Edit -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Username</strong>
                        <p><?= e($user['username']) ?></p>
                    </div>
                    <button class="edit-btn" data-field="username">Edit</button>
                </div>

                <!-- EMAIL (accounts.email) -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Email</strong>
                        <p><?= e($user['email']) ?></p>
                    </div>
                    <button class="edit-btn" data-field="email">Edit</button>
                </div>

                <!-- PHONE (accounts.phone) -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Phone Number</strong>
                        <p><?= e($user['phone']) ?></p>
                    </div>
                    <button class="edit-btn" data-field="phone">Edit</button>
                </div>

                <!-- USERNAME AGAIN (sesuai desain) tapi dihubungkan ke customers.full_name -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Username</strong>
                        <p><?= e($user['full_name'] ?? '') ?></p>
                    </div>
                    <button class="edit-btn" data-field="full_name">Edit</button>
                </div>

                <!-- PASSWORD (Change Password) -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Password</strong>
                        <button class="change-pass-btn" type="button">Change Password</button>
                    </div>
                </div>

                <!-- REMOVE ACCOUNT -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Account Removal</strong>
                        <button class="remove-account-btn" type="button">Remove Account</button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- FORM HIDDEN untuk submit tanpa mengubah desain -->
    <form id="form-username" class="hidden-form" method="post">
        <input type="hidden" name="action" value="update_username">
        <input type="hidden" name="username" id="hidden-username">
    </form>

    <form id="form-email" class="hidden-form" method="post">
        <input type="hidden" name="action" value="update_email">
        <input type="hidden" name="email" id="hidden-email">
    </form>

    <form id="form-phone" class="hidden-form" method="post">
        <input type="hidden" name="action" value="update_phone">
        <input type="hidden" name="phone" id="hidden-phone">
    </form>

    <form id="form-fullname" class="hidden-form" method="post">
        <input type="hidden" name="action" value="update_full_name">
        <input type="hidden" name="full_name" id="hidden-fullname">
    </form>

    <form id="form-password" class="hidden-form" method="post">
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="current_password" id="hidden-current-password">
        <input type="hidden" name="new_password" id="hidden-new-password">
        <input type="hidden" name="confirm_password" id="hidden-confirm-password">
    </form>

    <form id="form-remove" class="hidden-form" method="post">
        <input type="hidden" name="action" value="remove_account">
    </form>

    <script>
        // Upload Photo – tetap desain lama (satu tombol), tapi fungsional
        const uploadBtn  = document.querySelector('.upload-btn');
        const fileInput  = document.getElementById('profile_photo');
        const uploadForm = document.querySelector('.upload-form');

        if (uploadBtn && fileInput && uploadForm) {
            uploadBtn.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    uploadForm.submit();
                }
            });
        }

        // Edit field (Username, Email, Phone, Full Name) pakai prompt agar desain card tetap
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const field = btn.dataset.field;
                const labelDiv = btn.closest('.info-row').querySelector('.info-label p');
                const currentValue = labelDiv ? labelDiv.textContent.trim() : '';

                let placeholder = 'Masukkan nilai baru';
                if (field === 'username') placeholder = 'Masukkan username baru';
                if (field === 'email')    placeholder = 'Masukkan email baru';
                if (field === 'phone')    placeholder = 'Masukkan nomor telepon baru';
                if (field === 'full_name')placeholder = 'Masukkan nama lengkap baru';

                const newValue = prompt(placeholder + ':', currentValue);
                if (newValue === null) return; // user cancel
                const trimmed = newValue.trim();
                if (!trimmed) {
                    alert('Nilai tidak boleh kosong.');
                    return;
                }

                if (field === 'username') {
                    document.getElementById('hidden-username').value = trimmed;
                    document.getElementById('form-username').submit();
                } else if (field === 'email') {
                    document.getElementById('hidden-email').value = trimmed;
                    document.getElementById('form-email').submit();
                } else if (field === 'phone') {
                    document.getElementById('hidden-phone').value = trimmed;
                    document.getElementById('form-phone').submit();
                } else if (field === 'full_name') {
                    document.getElementById('hidden-fullname').value = trimmed;
                    document.getElementById('form-fullname').submit();
                }
            });
        });

        // Change Password – tetap satu tombol, input via prompt
        const changePassBtn = document.querySelector('.change-pass-btn');
        if (changePassBtn) {
            changePassBtn.addEventListener('click', () => {
                const current = prompt('Masukkan password lama:');
                if (current === null || current === '') return;

                const newPass = prompt('Masukkan password baru:');
                if (newPass === null || newPass === '') return;

                const confirmPass = prompt('Konfirmasi password baru:');
                if (confirmPass === null || confirmPass === '') return;

                document.getElementById('hidden-current-password').value = current;
                document.getElementById('hidden-new-password').value = newPass;
                document.getElementById('hidden-confirm-password').value = confirmPass;
                document.getElementById('form-password').submit();
            });
        }

        // Remove Account – tetap tombol, tapi beneran hapus
        const removeBtn = document.querySelector('.remove-account-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                if (confirm('Yakin ingin menghapus akun Anda? Tindakan ini tidak dapat dibatalkan.')) {
                    document.getElementById('form-remove').submit();
                }
            });
        }
    </script>

</body>
</html>
