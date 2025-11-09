<?php
// /admin/auth.php — SnapRent Auth (multi-role, struktur baru kompatibel)
// Mode: plaintext password (dengan deteksi otomatis bcrypt bila ada)

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* ==========================
   DB CONFIG
========================== */
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'snaprent';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  echo '<h1>DB Connection Failed</h1><pre>'.htmlspecialchars($e->getMessage()).'</pre>';
  exit;
}

/* ==========================
   HELPERS
========================== */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Kembalikan kolom password yang tersedia di accounts:
 * - prioritas 'password', fallback 'password_hash'
 */
function get_password_column(PDO $pdo): string {
  static $col = null;
  if ($col !== null) return $col;

  $sql = "SELECT COLUMN_NAME
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME='accounts'
            AND COLUMN_NAME IN ('password','password_hash')";
  $cols = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
  if (in_array('password', $cols, true))     $col = 'password';
  elseif (in_array('password_hash', $cols, true)) $col = 'password_hash';
  else $col = 'password'; // fallback aman (plaintext)
  return $col;
}

/**
 * Deteksi nama kolom PK untuk tabel role (owner_id vs account_id, dll.)
 * $candidates: urutan prioritas yang ingin dipakai.
 */
function detect_role_pk(PDO $pdo, string $table, array $candidates): string {
  static $cache = [];
  $key = $table.'|'.implode(',', $candidates);
  if (isset($cache[$key])) return $cache[$key];

  $in = implode("','", array_map('addslashes', $candidates));
  $sql = "SELECT COLUMN_NAME
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME IN ('$in')";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$table]);
  $found = $stmt->fetchAll(PDO::FETCH_COLUMN);

  foreach ($candidates as $c) {
    if (in_array($c, $found, true)) {
      return $cache[$key] = $c;
    }
  }
  // fallback paling aman
  return $cache[$key] = $candidates[0];
}

/**
 * Ambil user akun (by username atau email), termasuk kolom role-id (owner_id/staff_id/customer_id bila ada).
 */
function fetch_account_by_identifier(PDO $pdo, string $identifier): ?array {
  $pwdCol = get_password_column($pdo);

  // deteksi apakah kolom *_id ada di accounts (struktur baru)
  $hasOwner = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='accounts' AND COLUMN_NAME='owner_id'")->fetchColumn();
  $hasStaff = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='accounts' AND COLUMN_NAME='staff_id'")->fetchColumn();
  $hasCust  = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
                                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='accounts' AND COLUMN_NAME='customer_id'")->fetchColumn();

  $extraCols = [];
  if ($hasOwner) $extraCols[] = "owner_id";
  if ($hasStaff) $extraCols[] = "staff_id";
  if ($hasCust)  $extraCols[] = "customer_id";
  $extra = $extraCols ? ", ".implode(",", $extraCols) : "";

  $sql = "SELECT id, username, email, phone, role, is_active, is_verified, created_at, {$pwdCol} AS pw{$extra}
          FROM accounts
          WHERE (username = :id OR email = :id)
          LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id'=>$identifier]);
  $row = $stmt->fetch();
  return $row ?: null;
}

/**
 * Verifikasi password:
 * - default plaintext (sesuai permintaan)
 * - kalau value bcrypt ($2y$...), otomatis gunakan password_verify untuk kompatibilitas
 */
function verify_password_compat(string $input, string $stored): bool {
  if ($stored === '') return false;
  if (strncmp($stored, '$2y$', 4) === 0 || strncmp($stored, '$argon2', 7) === 0) {
    return password_verify($input, $stored);
  }
  return hash_equals($stored, $input);
}

/**
 * Ambil profil role beserta id yang benar-benar dipakai (mendukung struktur lama/baru).
 * Return: [role_id => (int), profile => array|null]
 */
function fetch_role_profile(PDO $pdo, string $role, int $accountId, ?int $accRoleIdNullable): array {
  $map = [
    'OWNER'    => ['table'=>'owners',    'candidates'=>['owner_id','account_id']],
    'STAFF'    => ['table'=>'staffs',    'candidates'=>['staff_id','account_id']],
    'CUSTOMER' => ['table'=>'customers', 'candidates'=>['customer_id','account_id']],
  ];
  $role = strtoupper($role);
  if (!isset($map[$role])) return ['role_id'=>null, 'profile'=>null];

  $table = $map[$role]['table'];
  $pk    = detect_role_pk($pdo, $table, $map[$role]['candidates']);

  // Jika accounts sudah punya *_id (struktur baru), utamakan nilai itu; jika null, fallback ke accounts.id
  $joinId = $accRoleIdNullable ?: $accountId;

  $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `$pk` = ? LIMIT 1");
  $stmt->execute([$joinId]);
  $profile = $stmt->fetch();

  $roleId = $profile ? (int)$profile[$pk] : null;
  return ['role_id'=>$roleId, 'profile'=>$profile ?: null];
}

/**
 * Login attempt: identifier (username/email) + password
 * Mengisi $_SESSION:
 *   - uid        : id akun
 *   - role       : OWNER/STAFF/CUSTOMER
 *   - role_id    : id di tabel role (owner_id/staff_id/customer_id/account_id)
 *   - profile    : array profil role minimal (full_name / address bila ada)
 */
function login_attempt(PDO $pdo, string $identifier, string $password): array {
  $acc = fetch_account_by_identifier($pdo, $identifier);
  if (!$acc) return ['ok'=>false, 'error'=>'Akun tidak ditemukan'];

  if (!(int)$acc['is_active']) return ['ok'=>false, 'error'=>'Akun non-aktif'];
  if (!verify_password_compat($password, (string)$acc['pw'])) return ['ok'=>false, 'error'=>'Password salah'];

  // Ambil role & profil
  $role = strtoupper($acc['role'] ?? '');
  $accOwnerId = $acc['owner_id']   ?? null;
  $accStaffId = $acc['staff_id']   ?? null;
  $accCustId  = $acc['customer_id']?? null;

  $accRoleId = null;
  if ($role === 'OWNER')    $accRoleId = $accOwnerId ? (int)$accOwnerId : null;
  if ($role === 'STAFF')    $accRoleId = $accStaffId ? (int)$accStaffId : null;
  if ($role === 'CUSTOMER') $accRoleId = $accCustId  ? (int)$accCustId  : null;

  $rp = fetch_role_profile($pdo, $role, (int)$acc['id'], $accRoleId);

  // Simpan session minimal
  $_SESSION['uid']      = (int)$acc['id'];
  $_SESSION['role']     = $role;
  $_SESSION['role_id']  = $rp['role_id'];  // bisa null bila profil belum dibuat
  $_SESSION['profile']  = $rp['profile'];  // bisa null

  return ['ok'=>true, 'user'=>[
    'id'       => (int)$acc['id'],
    'username' => $acc['username'],
    'email'    => $acc['email'],
    'role'     => $role,
    'role_id'  => $rp['role_id'],
    'profile'  => $rp['profile'],
  ]];
}

/**
 * Ambil user saat ini (tanpa password)
 */
function currentUser(PDO $pdo): ?array {
  if (!isset($_SESSION['uid'])) return null;
  $stmt = $pdo->prepare("SELECT id, username, email, role, is_active, is_verified, created_at
                         FROM accounts WHERE id=? LIMIT 1");
  $stmt->execute([$_SESSION['uid']]);
  $u = $stmt->fetch();
  if (!$u) return null;

  // Sinkronkan role_id/profile di sesi bila belum ada
  if (!isset($_SESSION['role']) || !isset($_SESSION['role_id'])) {
    $role = strtoupper($u['role'] ?? '');
    $rp = fetch_role_profile($pdo, $role, (int)$u['id'], null);
    $_SESSION['role']    = $role;
    $_SESSION['role_id'] = $rp['role_id'];
    $_SESSION['profile'] = $rp['profile'];
  }

  // gabungkan profil minimal (tanpa menimpa kunci existing)
  $u['_role_id'] = $_SESSION['role_id'] ?? null;
  $u['_profile'] = $_SESSION['profile'] ?? null;

  return $u;
}

/**
 * Wajib login (redirect ke /admin/login.php jika tidak valid)
 * NOTE: path relatif dari /Dashboard/index.php -> ../admin/login.php
 */
function require_login(PDO $pdo) {
  $u = currentUser($pdo);
  if (!$u) {
    header('Location: ../admin/login.php');
    exit;
  }
}

/**
 * Opsional: batasi halaman tertentu hanya untuk role spesifik.
 * Contoh: require_role(['OWNER']) untuk halaman admin-owner saja.
 */
function require_role(array $allowedRoles) {
  $role = $_SESSION['role'] ?? null;
  if (!$role || !in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>Anda tidak memiliki akses ke halaman ini.</p>';
    exit;
  }
}

/**
 * Logout sederhana
 */
function logout() {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}
