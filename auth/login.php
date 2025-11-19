<?php
// /admin/login.php — SnapRent Login Page (UI + SVG rain) + Forgot Password Modal (glass, meteran strength)
// NOTE: Tidak mengubah schema/DB. Reset password tetap via kolom yang sama ($PWD_COL).

require __DIR__ . '/auth.php';

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ---------- Helper functions ----------
if (!function_exists('has_column')) {
  function has_column(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$table, $column]);
    return (int)$st->fetchColumn() > 0;
  }
}

if (!function_exists('role_redirect')) {
  function role_redirect(string $role, array $routes, string $fallback){
    $R = strtoupper($role);
    $to = $routes[$R] ?? $fallback;
    header("Location: $to");
    exit;
  }
}

// ---------- Redirect map ----------
$ROUTES = [
  'OWNER'    => '../Dashboard/index.php',
  'STAFF'    => '../Dashboard/index.php',
  'CUSTOMER' => '../index.php',
  'COSTUMER' => '../index.php',
];
$DEFAULT_ROUTE = '../Dashboard/index.php';

// ---------- Auto-redirect jika sudah login ----------
if (isset($_SESSION['uid']) && function_exists('currentUser') && currentUser($pdo)) {
  $role = strtoupper((string)($_SESSION['role'] ?? ''));
  role_redirect($role, $ROUTES, $DEFAULT_ROUTE);
}

// ---------- UI constants ----------
$FORGOT_URL   = '#';              // dibuka modal via JS
$REGISTER_URL = 'register.php';

// ---------- CSRF ----------
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

// ---------- DB Columns ----------
$PWD_COL    = get_password_column($pdo);
$HAS_PHONE  = has_column($pdo, 'accounts', 'phone');
$HAS_ACTIVE = has_column($pdo, 'accounts', 'is_active');

// ---------- AJAX: Forgot Password ----------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'forgot') {
  header('Content-Type: application/json; charset=utf-8');
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    echo json_encode(['ok'=>false,'message'=>'Session expired. Refresh page.']); exit;
  }
  $email = trim((string)($_POST['email'] ?? ''));
  $pwd1  = (string)($_POST['password'] ?? '');
  $pwd2  = (string)($_POST['password2'] ?? '');

  if ($email === '' || $pwd1 === '' || $pwd2 === '') {
    echo json_encode(['ok'=>false,'message'=>'Please fill all fields.']); exit;
  }
  if ($pwd1 !== $pwd2) {
    echo json_encode(['ok'=>false,'message'=>'Password confirmation does not match.']); exit;
  }
  if (strlen($pwd1) < 8) { // sesuai placeholder contoh (minimal 8)
    echo json_encode(['ok'=>false,'message'=>'Password must be at least 8 characters.']); exit;
  }

  try {
    $activeExpr = $HAS_ACTIVE ? 'is_active = 1' : '1=1';
    $st = $pdo->prepare("
      SELECT id FROM accounts
      WHERE email = ?
        AND UPPER(role) IN ('OWNER','STAFF','CUSTOMER','COSTUMER')
        AND {$activeExpr}
      LIMIT 1
    ");
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u) {
      echo json_encode(['ok'=>false,'message'=>'Account not found or inactive.']); exit;
    }

    // === SIMPAN PASSWORD BARU DALAM BENTUK HASH ===
    $newHash = password_hash($pwd1, PASSWORD_DEFAULT);

    $upd = $pdo->prepare("UPDATE accounts SET `{$PWD_COL}` = :pwd WHERE id = :id");
    $upd->execute([
      ':pwd' => $newHash,
      ':id'  => $u['id']
    ]);

    echo json_encode(['ok'=>true,'message'=>'Password has been reset. You can sign in now.']); exit;
  } catch (Throwable $e) {
    echo json_encode(['ok'=>false,'message'=>'Reset failed.']); exit;
  }
}

// ---------- LOGIN normal ----------
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'forgot') {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $error = 'Session expired. Refresh page.';
  } else {
    $ident = trim((string)($_POST['identity'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    if ($ident === '' || $pass === '') {
      $error = 'Please fill all fields.';
    } else {
      try {
        $wheres = ['username = ?', 'email = ?'];
        $params = [$ident, $ident];
        if ($HAS_PHONE) { $wheres[] = 'phone = ?'; $params[] = $ident; }
        $whereIdent = implode(' OR ', $wheres);

        $activeExpr = $HAS_ACTIVE ? 'is_active' : '1 AS is_active';
        $st = $pdo->prepare("
          SELECT id, username, email, role, {$activeExpr}, `{$PWD_COL}` AS pwd
          FROM accounts
          WHERE ({$whereIdent})
            AND UPPER(role) IN ('OWNER','STAFF','CUSTOMER','COSTUMER')
          LIMIT 1
        ");
        $st->execute($params);
        $u = $st->fetch();

        if (!$u) {
          $error = 'Account not found or role not allowed.';
        } elseif ((int)$u['is_active'] !== 1) {
          $error = 'Account is inactive.';
        } else {
          // ===== VERIFIKASI PASSWORD HASH =====
          $storedHash = (string)($u['pwd'] ?? '');

          if ($storedHash !== '' && password_verify($pass, $storedHash)) {
            // (Opsional) rehash jika algoritma sudah usang
            if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
              try {
                $newHash = password_hash($pass, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE accounts SET `{$PWD_COL}` = :pwd WHERE id = :id");
                $upd->execute([
                  ':pwd' => $newHash,
                  ':id'  => $u['id']
                ]);
              } catch (Throwable $e) {
                // abaikan error rehash, tidak mengganggu login
              }
            }

            // ====== LOGIN SUKSES: gunakan $_SESSION['uid'] untuk SEMUA ROLE ======
            $role = strtoupper((string)$u['role']);

            // ID akun utama (accounts.id) — dipakai di semua halaman
            $_SESSION['uid']   = (int)$u['id'];
            $_SESSION['role']  = $role;
            $_SESSION['uname'] = (string)($u['username'] ?? '');
            $_SESSION['email'] = (string)($u['email'] ?? '');

            // Normalisasi ID per-role, semuanya berbasis $_SESSION['uid']
            unset($_SESSION['owner_id'], $_SESSION['staff_id'], $_SESSION['customer_id']);
            if ($role === 'OWNER') {
              $_SESSION['owner_id'] = $_SESSION['uid'];
            } elseif ($role === 'STAFF') {
              $_SESSION['staff_id'] = $_SESSION['uid'];
            } elseif ($role === 'CUSTOMER' || $role === 'COSTUMER') {
              $_SESSION['customer_id'] = $_SESSION['uid'];
            }

            // Redirect berdasarkan role SETELAH login berhasil
            if ($role === 'CUSTOMER' || $role === 'COSTUMER') {
              header('Location: ../index.php');
            } else {
              header('Location: ../Dashboard/index.php');
            }
            exit;
          } else {
            $error = 'Wrong password.';
          }
        }
      } catch (Throwable $e) {
        $error = 'Login error.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in • SnapRent</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg1:#cfe0ee; --bg2:#9fb7cd;
    --card-bg:rgba(255,255,255,.65); --glass-stroke:rgba(255,255,255,.6);
    --brand:#2f80ed; --brand-dark:#2367c3; --radius:18px;
    --shadow:0 20px 50px rgba(21,40,59,.25);
    --icon:#2f80ed; --icon2:#6aa8ff; --bokeh:#e7f1ff;
    --ok:#1b8a3a; --warn:#c0392b; --muted:#6c7e90;
  }
  *{box-sizing:border-box}
  html,body{height:100%;margin:0;font-family:Poppins, sans-serif;}
  .stage{
    min-height:100%;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(180deg,var(--bg1),var(--bg2));
    padding:16px 28px 28px; position:relative;overflow:hidden;
  }
  .bg-layer{ position:absolute; inset:0; pointer-events:none; overflow:hidden; }
  .bg-bokeh{ z-index:0; } .rain-back{ z-index:1; } .rain-mid{ z-index:1; } .rain-front{ z-index:1; }
  .bokeh-dot{ position:absolute; border-radius:50%;
    background: radial-gradient(circle at 30% 30%, #ffffff80 0%, #ffffff20 40%, transparent 70%), var(--bokeh);
    opacity:.18; filter: blur(6px); will-change: transform, opacity; animation: floaty linear infinite;
  }
  @keyframes floaty{0%{transform:translateY(-10vh)}100%{transform:translateY(110vh)}}
  .icon-drop{ position:absolute; top:-15%; opacity:.10; will-change:transform,opacity;
    animation: fall linear infinite, sway ease-in-out infinite; filter: drop-shadow(0 2px 4px rgba(0,0,0,.12)); }
  .icon-drop svg{ display:block; width:100%; height:100%; }
  @keyframes fall{to{transform:translateY(120vh) rotate(360deg)}}
  @keyframes sway{0%{margin-left:-10px}50%{margin-left:10px}100%{margin-left:-10px}}

  .panel{
    width:min(1200px,100%);min-height:640px;border-radius:26px;
    background:linear-gradient(180deg,rgba(255,255,255,.55),rgba(255,255,255,.35));
    box-shadow:var(--shadow);position:relative;z-index:3;overflow:hidden;
  }
  .grid{ display:grid; grid-template-columns:420px 1fr; gap:24px; height:100%;
    padding:28px 56px 48px; margin-top:0; }
  .card{
    background:var(--card-bg);border:1px solid var(--glass-stroke);border-radius:var(--radius);
    backdrop-filter:blur(10px);box-shadow:var(--shadow);padding:32px 28px;z-index:4;
  }

  .brand-mini{ display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;width:100%;text-align:center;margin-bottom:26px; }
  .brand-logo{ width:126px;height:126px;object-fit:contain;border-radius:14px; }
  .h-title{font-weight:700;color:var(--brand);margin:8px 0 22px;text-align:center;}
  .label{font-size:12px;color:#607287;margin:12px 2px 8px}
  .input{width:100%;height:40px;border-radius:8px;border:1px solid #e2e8f0;padding:0 12px;}
  .helpbar{text-align:right;margin-top:6px;}
  .helpbar a{font-size:12px;color:var(--muted);text-decoration:none;}

  .btn{
    width:100%;height:42px;border:none;border-radius:10px;
    background:linear-gradient(180deg,var(--brand),var(--brand-dark));
    color:#fff;font-weight:600;margin:14px 0;cursor:pointer;
    transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
    box-shadow: 0 6px 16px rgba(47,128,237,.25);
  }
  .btn:hover{ transform: translateY(-1px); box-shadow: 0 10px 26px rgba(47,128,237,.35), 0 0 0 4px rgba(106,168,255,.18) inset; filter: saturate(1.06); }
  .btn:active{ transform: translateY(0); box-shadow: 0 6px 16px rgba(47,128,237,.28); }
  .subtxt{text-align:center;font-size:12px;color:var(--muted);}
  .subtxt a{color:#2f80ed;text-decoration:none;font-weight:600;}

  .right{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:22px; height:100%; text-align:center; }
  .welcome{ font-size:34px; font-weight:800; color:#1f3953; margin:0; padding-top:24px; }
  .hero-img{ width:100%; max-width:480px; height:auto; object-fit:contain; filter:drop-shadow(0 8px 20px rgba(0,0,0,.2)); border-radius:18px; animation: float 9s ease-in-out infinite; }
  @keyframes float{0%{transform:translateY(0) translateX(0)}50%{transform:translateY(-18px) translateX(6px)}100%{transform:translateY(0) translateX(0)}}
  .alert{background:#ffe8e8;border:1px solid #ffcdcd;color:#c0392b;padding:10px;border-radius:10px;margin-bottom:10px;}

  /* ===== Modal Forgot Password (glass + desain seperti gambar) ===== */
  .modal-wrap{
    position:fixed; inset:0; display:none;
    z-index:50; background:rgba(15,32,55,.35); backdrop-filter: blur(2px);
  }
  .modal{
    width:min(520px, 92vw);
    background:linear-gradient(180deg,rgba(255,255,255,.80),rgba(255,255,255,.60));
    border:1px solid var(--glass-stroke);
    border-radius:28px; box-shadow:var(--shadow); padding:28px 26px 24px;
    position:fixed; left:50%; top:50%; transform:translate(-50%,-50%);
  }
  .modal-head{ display:flex; flex-direction:column; align-items:center; gap:12px; margin-bottom:10px; }
  .modal-icon{
    width:72px; height:72px; border-radius:18px; display:grid; place-items:center;
    background: radial-gradient(120% 120% at 50% 0%, #e6f0ff 0%, #ffffff 60%);
    border:1px solid #e7eefc;
  }
  .modal-title{ font-size:22px; font-weight:800; color:#253b58; text-align:center; }
  .modal .label{ margin-top:14px; font-size:13px; color:#2b405b; }
  .modal .input{ height:44px; border-radius:12px; background:rgba(255,255,255,.8); }
  .modal .hint{ font-size:12px; color:#8aa0b6; margin-top:6px; }

  .strength-bars{ display:flex; gap:10px; margin:10px 0 0; }
  .bar{ flex:1; height:8px; border-radius:6px; background:#e6edf7; overflow:hidden; }
  .bar.fill{ background:#a7dfb0; }
  .strength-text{ font-size:12px; margin-top:6px; }
  .strength-text .val.ok{ color:var(--ok); }
  .strength-text .val.warn{ color:var(--warn); }

  .modal .actions{ display:flex; gap:10px; margin-top:18px; }
  .modal .btn{ height:46px; border-radius:12px; margin:0; }
  .modal .btn.secondary{
    background:#eef3ff; color:#2f80ed; box-shadow:none; border:1px solid #d7e5ff; background-image:none;
  }

  @media(max-width:1024px){ .grid{grid-template-columns:1fr;padding:20px 24px 28px;margin-top:0} .welcome{font-size:26px} .hero-img{max-width:70vw} }
  @media (prefers-reduced-motion: reduce){
    .icon-drop, .bokeh-dot{ animation-duration: 1ms !important; animation-iteration-count: 1 !important; }
    .btn{ transition:none; }
  }
</style>
</head>
<body>

<!-- SVG SPRITE -->
<svg width="0" height="0" style="position:absolute;visibility:hidden" aria-hidden="true">
  <defs>
    <symbol id="ico-camera" viewBox="0 0 64 48">
      <rect x="4" y="10" width="56" height="34" rx="6" fill="white"/>
      <rect x="4" y="10" width="56" height="34" rx="6" fill="url(#g1)" opacity=".12"/>
      <rect x="12" y="6" width="16" height="10" rx="3" fill="var(--icon)"/>
      <circle cx="36" cy="27" r="12" fill="#fff" stroke="var(--icon)" stroke-width="4"/>
      <circle cx="36" cy="27" r="6" fill="var(--icon)"/>
      <circle cx="48" cy="18" r="2" fill="var(--icon)"/>
      <rect x="8" y="16" width="10" height="4" rx="2" fill="var(--icon2)"/>
    </symbol>
    <symbol id="ico-video" viewBox="0 0 64 44">
      <rect x="4" y="8" width="40" height="28" rx="6" fill="#fff" stroke="var(--icon)" stroke-width="4"/>
      <path d="M46 14 L60 9 V35 L46 30 Z" fill="var(--icon)"/>
      <circle cx="18" cy="22" r="6" fill="var(--icon2)"/>
    </symbol>
    <symbol id="ico-film" viewBox="0 0 64 44">
      <rect x="6" y="8" width="52" height="28" rx="4" fill="#fff" stroke="var(--icon)" stroke-width="4"/>
      <rect x="16" y="12" width="32" height="20" fill="#e9f2ff" stroke="var(--icon2)" stroke-width="2"/>
      <g fill="var(--icon)">
        <rect x="8" y="10" width="6" height="4" rx="1"/>
        <rect x="8" y="18" width="6" height="4" rx="1"/>
        <rect x="8" y="26" width="6" height="4" rx="1"/>
        <rect x="50" y="10" width="6" height="4" rx="1"/>
        <rect x="50" y="18" width="6" height="4" rx="1"/>
        <rect x="50" y="26" width="6" height="4" rx="1"/>
      </g>
    </symbol>
    <symbol id="ico-lens" viewBox="0 0 64 64">
      <circle cx="32" cy="32" r="22" fill="#fff" stroke="var(--icon)" stroke-width="4"/>
      <circle cx="32" cy="32" r="14" fill="none" stroke="var(--icon2)" stroke-width="4" stroke-dasharray="10 6"/>
      <circle cx="40" cy="24" r="4" fill="var(--icon2)"/>
    </symbol>
    <linearGradient id="g1" x1="0" y1="10" x2="0" y2="44"><stop offset="0" stop-color="var(--icon2)"/><stop offset="1" stop-color="var(--icon)"/></linearGradient>
  </defs>
</svg>

<div class="stage">
  <div class="bg-layer bg-bokeh" id="bgBokeh" data-depth="0.02"></div>
  <div class="bg-layer rain-back icon-layer" id="rainBack" data-depth="0.05"></div>
  <div class="bg-layer rain-mid  icon-layer" id="rainMid"  data-depth="0.08"></div>
  <div class="bg-layer rain-front icon-layer" id="rainFront" data-depth="0.12"></div>

  <div class="panel">
    <div class="grid">
      <div class="card">
        <div class="brand-mini"><img class="brand-logo" src="images/logo.png" alt="SnapRent Logo"></div>
        <div class="h-title">Sign in to Your Account</div>
        <?php if($error): ?><div class="alert"><?php echo e($error); ?></div><?php endif; ?>

        <form method="post" autocomplete="off" novalidate>
          <input type="hidden" name="csrf" value="<?php echo e($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="login">
          <div class="label">Username</div>
          <input class="input" type="text" name="identity" placeholder="username/email<?php echo $HAS_PHONE ? '/telephone' : '' ?>" required>
          <div class="label">Password</div>
          <input class="input" type="password" name="password" minlength="1" placeholder="Your password" required>
          <div class="helpbar"><a id="forgotLink" href="<?php echo e($FORGOT_URL); ?>">Forgot Password?</a></div>
          <button class="btn" type="submit">Sign in</button>
          <div class="subtxt">Don't have an account yet? <a href="<?php echo e($REGISTER_URL); ?>">Register for free</a></div>
        </form>
      </div>

      <div class="right">
        <div class="welcome">Welcome To SnapRent</div>
        <img class="hero-img" src="images/mascot.png" alt="SnapRent Mascot">
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal Forgot Password (desain baru) ===== -->
<div class="modal-wrap" id="forgotModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="fpTitle">
    <div class="modal-head">
      <div class="modal-icon">
        <img src="images/reset-password.png" 
            alt="Reset Password Icon"
            style="width:48px;height:48px;object-fit:contain;display:block;">
      </div>
      <div class="modal-title" id="fpTitle">Reset Your Password</div>
    </div>

    <form id="forgotForm" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?php echo e($_SESSION['csrf']); ?>">
      <input type="hidden" name="action" value="forgot">

      <div class="label">Email</div>
      <input class="input" type="email" name="email" placeholder="you@example.com" required>

      <div class="label" style="margin-top:16px">New Password</div>
      <input class="input" id="pwdNew" type="password" name="password" minlength="8" placeholder="New Password minimal 8 characters" required>

      <!-- Strength meter -->
      <div class="strength-bars" aria-hidden="true">
        <div class="bar" id="sb1"></div>
        <div class="bar" id="sb2"></div>
        <div class="bar" id="sb3"></div>
        <div class="bar" id="sb4"></div>
      </div>
      <div class="strength-text">Password strength: <span class="val" id="strengthVal">-</span></div>

      <div class="label" style="margin-top:16px">Re-enter Password</div>
      <input class="input" id="pwdNew2" type="password" name="password2" minlength="8" placeholder="Re-enter Password" required>

      <div class="actions">
        <button type="button" class="btn secondary" id="cancelForgot">Cancel</button>
        <button type="submit" class="btn">Reset Password</button>
      </div>
      <div class="hint" id="fpMsg" aria-live="polite"></div>
    </form>
  </div>
</div>

<script>
(function(){
  // ===== Background generators (tetap) =====
  const bokehWrap = document.getElementById('bgBokeh');
  if (bokehWrap){
    const DOTS = 14;
    for (let i=0;i<DOTS;i++){
      const d = document.createElement('div'); d.className='bokeh-dot';
      const size = 80 + Math.random()*160;
      d.style.width = d.style.height = size+'px';
      d.style.left = (Math.random()*100)+'vw';
      d.style.top  = (-20 - Math.random()*60)+'vh';
      d.style.animationDuration = (25 + Math.random()*45)+'s';
      d.style.animationDelay    = (Math.random()*20)+'s';
      d.style.opacity = (0.10 + Math.random()*0.12).toFixed(2);
      d.style.filter  = 'blur('+(4+Math.random()*6)+'px)';
      bokehWrap.appendChild(d);
    }
  }
  const ICONS = ['ico-camera','ico-video','ico-film','ico-lens'];
  function spawnRain(layerEl, cnt,a,b,c,d,e,f, extra=-15){
    if(!layerEl) return;
    for (let i=0;i<cnt;i++){
      const box = document.createElement('div'); box.className='icon-drop';
      const size = a + Math.random()*(b-a);
      box.style.width = size+'px'; box.style.height = size+'px';
      const left = Math.random()*100; const d1 = c + Math.random()*(d-c);
      const d2 = e + Math.random()*(f-e); const delay = Math.random()*14;
      box.style.left = left+'vw'; box.style.top = (extra - Math.random()*10)+'vh';
      box.style.animationDuration = d1+'s, '+d2+'s'; box.style.animationDelay = delay+'s, '+(delay/2)+'s';
      const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
      svg.setAttribute('viewBox','0 0 64 64'); svg.setAttribute('preserveAspectRatio','xMidYMid meet');
      const use = document.createElementNS('http://www.w3.org/2000/svg','use');
      use.setAttributeNS('http://www.w3.org/1999/xlink','href','#'+ICONS[Math.floor(Math.random()*ICONS.length)]);
      svg.appendChild(use); box.appendChild(svg); layerEl.appendChild(box);
    }
  }
  spawnRain(document.getElementById('rainBack'), 34, 14,28,24,42,5,10,-18);
  spawnRain(document.getElementById('rainMid'),  40, 18,36,16,30,4,8,-15);
  spawnRain(document.getElementById('rainFront'),48, 20,44,11,22,4,7,-12);

  // Parallax
  const layers=[document.getElementById('bgBokeh'),document.getElementById('rainBack'),document.getElementById('rainMid'),document.getElementById('rainFront')].filter(Boolean);
  function parallax(x,y){ layers.forEach(el=>{ const depth=parseFloat(el.dataset.depth||'0.05');
    el.style.transform=`translate3d(${(x*100*depth).toFixed(2)}px, ${(y*60*depth).toFixed(2)}px, 0)`; }); }
  let vw=Math.max(document.documentElement.clientWidth, window.innerWidth||0);
  let vh=Math.max(document.documentElement.clientHeight, window.innerHeight||0);
  window.addEventListener('mousemove',e=>parallax((e.clientX/vw)-0.5,(e.clientY/vh)-0.5));
  window.addEventListener('deviceorientation',e=>parallax((e.gamma||0)/60,(e.beta||0)/90),{passive:true});
  window.addEventListener('resize',()=>{vw=Math.max(document.documentElement.clientWidth, window.innerWidth||0); vh=Math.max(document.documentElement.clientHeight, window.innerHeight||0);});

  // ===== Modal open/close =====
  const forgotLink = document.getElementById('forgotLink');
  const modalWrap  = document.getElementById('forgotModal');
  const modalBox   = modalWrap ? modalWrap.querySelector('.modal') : null;
  const cancelBtn  = document.getElementById('cancelForgot');
  const form       = document.getElementById('forgotForm');
  const msg        = document.getElementById('fpMsg');
  const pwd1       = document.getElementById('pwdNew');
  const pwd2       = document.getElementById('pwdNew2');
  const bars       = [document.getElementById('sb1'),document.getElementById('sb2'),document.getElementById('sb3'),document.getElementById('sb4')];
  const sval       = document.getElementById('strengthVal');
  const loginCard  = document.querySelector('.grid .card');

  function centerModal(){
    if (!modalBox) return;
    modalBox.style.left = '50%';
    modalBox.style.top = '50%';
    modalBox.style.transform = 'translate(-50%,-50%)';
  }

  function positionModalToCard(){
    if (!modalBox || !loginCard) { centerModal(); return; }

    const r = loginCard.getBoundingClientRect();
    const vw = window.innerWidth || document.documentElement.clientWidth;

    if (vw <= 1024) { centerModal(); return; }

    const left = Math.round(r.left + window.scrollX);
    const top  = Math.round(r.top  + window.scrollY);

    modalBox.style.transform = 'none';
    modalBox.style.left = left + 'px';
    modalBox.style.top  = top  + 'px';
  }

  function openModal(){
    if (!modalWrap) return;
    modalWrap.style.display = 'block';
    modalWrap.setAttribute('aria-hidden','false');

    if (form) { form.reset(); }
    if (msg)  { msg.textContent=''; msg.className='hint'; }

    positionModalToCard();

    window.addEventListener('resize', positionModalToCard);
    window.addEventListener('scroll', positionModalToCard, { passive:true });
  }

  function closeModal(){
    if (!modalWrap) return;
    modalWrap.style.display = 'none';
    modalWrap.setAttribute('aria-hidden','true');

    window.removeEventListener('resize', positionModalToCard);
    window.removeEventListener('scroll', positionModalToCard);
  }

  if (forgotLink){ forgotLink.addEventListener('click', e=>{ e.preventDefault(); openModal(); }); }
  if (cancelBtn){  cancelBtn.addEventListener('click', closeModal); }
  if (modalWrap){
    modalWrap.addEventListener('click', e=>{ if(e.target===modalWrap) closeModal(); });
    window.addEventListener('keydown', e=>{ if(e.key==='Escape' && modalWrap.getAttribute('aria-hidden')==='false') closeModal(); });
  }

  function score(p){ let s=0; if(p.length>=8) s++; if(/[a-z]/.test(p)&&/[A-Z]/.test(p)) s++; if(/\d/.test(p)) s++; if(/[^A-Za-z0-9]/.test(p)) s++; return s; }
  function updateStrength(p){
    const sc=score(p);
    bars.forEach((b,i)=>b && b.classList.toggle('fill', i<sc));
    if (!sval) return;
    let text='-'; let cls='warn';
    if (sc<=1){ text='weak'; } else if (sc===2){ text='fair'; }
    else if (sc===3){ text='good'; cls='ok'; } else { text='strong'; cls='ok'; }
    sval.textContent=text; sval.className='val '+cls;
  }
  if (pwd1){ pwd1.addEventListener('input', e=>updateStrength(e.target.value||'')); }

  if (form){
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      if ((pwd1.value||'').length < 8){ msg.textContent='Password must be at least 8 characters.'; msg.style.color='var(--warn)'; return; }
      if (pwd1.value !== pwd2.value){ msg.textContent='Password confirmation does not match.'; msg.style.color='var(--warn)'; return; }
      msg.textContent='Updating password...'; msg.style.color='var(--muted)';
      const fd = new FormData(form);
      try{
        const res = await fetch(location.href, { method:'POST', headers:{'Accept':'application/json'}, body:fd });
        const data = await res.json();
        if (data && data.ok){
          msg.textContent = data.message || 'Password updated.'; msg.style.color='var(--ok)';
          setTimeout(()=>closeModal(), 900);
        } else {
          msg.textContent = (data && data.message) ? data.message : 'Reset failed.'; msg.style.color='var(--warn)';
        }
      }catch(err){
        msg.textContent = 'Network error.'; msg.style.color='var(--warn)';
      }
    });
  }
})();
</script>

</body>
</html>
