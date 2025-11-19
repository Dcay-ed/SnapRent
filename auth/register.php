<?php
// /admin/register.php — SnapRent Register Page (Customer Only)
require __DIR__ . '/auth.php';

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ---- Helper cek kolom tabel ----
if (!function_exists('has_column')) {
  function has_column(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$table, $column]);
    return (int)$st->fetchColumn() > 0;
  }
}

// ---- Ambil konfigurasi kolom ----
$PWD_COL    = get_password_column($pdo);          // fungsi dari auth.php
$HAS_PHONE  = has_column($pdo, 'accounts', 'phone');
$HAS_NAME   = has_column($pdo, 'accounts', 'name');     // di schema sekarang kemungkinan false
$HAS_ACTIVE = has_column($pdo, 'accounts', 'is_active');

// ---- CSRF ----
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$error   = '';
$success = false;

// ---- Submit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $error = 'Session expired. Refresh page.';
  } else {
    $name  = trim($_POST['fullname'] ?? '');
    $user  = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass1 = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($user === '' || $email === '' || $pass1 === '' || $pass2 === '') {
      $error = 'Please fill all required fields.';
    } elseif ($pass1 !== $pass2) {
      $error = 'Passwords do not match.';
    } elseif (strlen($pass1) < 8) {
      $error = 'Password must be at least 8 characters.';
    } else {
      try {
        // cek username / email unik
        $st = $pdo->prepare("SELECT id FROM accounts WHERE username = ? OR email = ? LIMIT 1");
        $st->execute([$user, $email]);
        if ($st->fetch()) {
          $error = 'Username or Email already taken.';
        } else {
          // Mulai transaksi supaya insert accounts + customers konsisten
          $pdo->beginTransaction();

          // === HASH PASSWORD SEBELUM DISIMPAN ===
          $hashedPassword = password_hash($pass1, PASSWORD_DEFAULT);

          // 1) INSERT ke accounts (role = CUSTOMER)
          $cols   = ['username','email',"`{$PWD_COL}`",'role'];
          $vals   = ['?','?','?',"'CUSTOMER'"];
          $params = [$user, $email, $hashedPassword];

          if ($HAS_NAME) {
            $cols[]   = 'name';
            $vals[]   = '?';
            $params[] = $name;
          }
          if ($HAS_PHONE) {
            $cols[]   = 'phone';
            $vals[]   = '?';
            $params[] = $phone;
          }
          if ($HAS_ACTIVE) {
            $cols[] = 'is_active';
            $vals[] = '1';
          }

          $sqlAcc = "INSERT INTO accounts(" . implode(',', $cols) . ")
                     VALUES(" . implode(',', $vals) . ")";
          $stmtAcc = $pdo->prepare($sqlAcc);
          $stmtAcc->execute($params);

          // id akun baru
          $accountId = (int)$pdo->lastInsertId();

          // 2) INSERT ke customers
          //    - customer_id = id akun (supaya 1:1)
          //    - full_name dari form
          //    - address sementara diisi default, karena NOT NULL
          $fullName       = $name !== '' ? $name : $user;
          $defaultAddress = 'Alamat belum diisi';

          $sqlCust = "INSERT INTO customers (customer_id, full_name, address)
                      VALUES (?, ?, ?)";
          $stmtCust = $pdo->prepare($sqlCust);
          $stmtCust->execute([$accountId, $fullName, $defaultAddress]);

          // 3) UPDATE accounts.customer_id supaya relasi dua arah
          $sqlLink  = "UPDATE accounts SET customer_id = ? WHERE id = ?";
          $stmtLink = $pdo->prepare($sqlLink);
          $stmtLink->execute([$accountId, $accountId]);

          // Commit transaksi
          $pdo->commit();

          $success = true;
          header("Refresh:1; URL=login.php");
        }
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        // Untuk debug bisa sementara di-echo:
        // $error = 'Registration failed: ' . $e->getMessage();
        $error = 'Registration failed.';
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
<title>Create Account • SnapRent</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root{
  --bg1:#cfe0ee; --bg2:#9fb7cd;
  --card-bg:rgba(255,255,255,.65); --glass-stroke:rgba(255,255,255,.6);
  --brand:#2f80ed; --brand-dark:#2367c3;
  --shadow:0 20px 50px rgba(21,40,59,.25);
  --icon:#2f80ed; --icon2:#6aa8ff; --bokeh:#e7f1ff;
  --muted:#6c7e90;
}
*{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:Poppins,sans-serif;}
.stage{
  min-height:100%;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(180deg,var(--bg1),var(--bg2));
  padding:20px;position:relative;overflow:hidden;
}
.bg-layer{position:absolute; inset:0; pointer-events:none; overflow:hidden;}

.bokeh-dot{
  position:absolute;border-radius:50%;
  background: radial-gradient(circle at 30% 30%, #ffffff80 0%, #ffffff20 40%, transparent 70%), var(--bokeh);
  opacity:.18; filter:blur(6px);
  animation:floaty linear infinite;
}
@keyframes floaty {0%{transform:translateY(-10vh)}100%{transform:translateY(110vh)}}

.icon-drop{position:absolute;opacity:.10;animation:fall linear infinite, sway ease-in-out infinite;}
@keyframes fall{to{transform:translateY(120vh) rotate(360deg);}}
@keyframes sway{0%{margin-left:-10px;}50%{margin-left:10px;}100%{margin-left:-10px;}}

.panel{
  width:min(1200px,100%);
  min-height:640px;border-radius:26px;
  background:linear-gradient(180deg,rgba(255,255,255,.55),rgba(255,255,255,.35));
  box-shadow:var(--shadow);
  position:relative;z-index:3;overflow:hidden;
}
.grid{display:grid;grid-template-columns:420px 1fr;gap:24px;height:100%;padding:30px 50px;}

.card{
  background:var(--card-bg);border:1px solid var(--glass-stroke);border-radius:18px;
  backdrop-filter:blur(12px);box-shadow:var(--shadow);padding:32px;
}

.brand-mini{text-align:center;margin-bottom:22px;}
.brand-logo{width:120px;height:120px;object-fit:contain;border-radius:12px;}

.h-title{text-align:center;font-weight:700;color:var(--brand);margin-bottom:14px;}

.label{font-size:12px;color:#607287;margin-top:10px;}
.input{width:100%;height:40px;border-radius:8px;border:1px solid #d9e1ea;padding:0 12px;}

.pw-wrap{position:relative;}
.pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;opacity:.6;}
.pw-toggle:hover{opacity:1;}

.strength-bars{display:flex;gap:6px;margin-top:6px;}
.bar{flex:1;height:7px;background:#dbe4ee;border-radius:4px;transition:.25s;}
.bar.fill-1{background:#ff6a6a;}
.bar.fill-2{background:#ffb347;}
.bar.fill-3{background:#73b3ff;}
.bar.fill-4{background:#62d16f;}
.strength-label{font-size:12px;margin-top:4px;color:#607287;}

.btn{
  margin-top:18px;
  width:100%;height:42px;border:none;border-radius:10px;
  background:linear-gradient(180deg,var(--brand),var(--brand-dark));
  color:#fff;font-weight:600;cursor:pointer;
  box-shadow:0 6px 16px rgba(47,128,237,.25);
}
.btn:hover{transform:translateY(-1px);}

.alert,.success{
  padding:10px;border-radius:10px;margin-bottom:12px;font-size:13px;
}
.alert{background:#ffe8e8;border:1px solid #ffcdcd;color:#c0392b;}
.success{background:#e7ffe9;border:1px solid #c2f5c8;color:#1b8a3a;}

.right{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  text-align:center;gap:18px;height:100%;
}
.welcome{font-size:32px;font-weight:800;color:#1f3953;margin-bottom:0;}
.hero-img{
  max-width:500px;width:100%;object-fit:contain;
  filter:drop-shadow(0 8px 20px rgba(0,0,0,.20));
  animation:float 9s ease-in-out infinite;
}
@keyframes float{0%{transform:translateY(0)}50%{transform:translateY(-16px)}100%{transform:translateY(0)}}

.subtxt{text-align:center;margin-top:14px;font-size:12px;color:var(--muted);}
.subtxt a{color:var(--brand);font-weight:600;text-decoration:none;}

@media(max-width:1024px){
  .grid{grid-template-columns:1fr;padding:24px;}
  .hero-img{max-width:70vw;}
}
</style>
</head>
<body>

<!-- SVG Icons -->
<svg width="0" height="0" style="position:absolute;visibility:hidden">
<defs>
<symbol id="ico-camera" viewBox="0 0 64 48">
  <rect x="4" y="10" width="56" height="34" rx="6" fill="var(--icon)"/>
  <circle cx="36" cy="27" r="12" fill="#fff" stroke="var(--icon)" stroke-width="4"/>
</symbol>
<symbol id="ico-video" viewBox="0 0 64 44">
  <rect x="4" y="8" width="40" height="28" rx="6" fill="var(--icon)"/>
</symbol>
<symbol id="ico-film"  viewBox="0 0 64 44">
  <rect x="6" y="8" width="52" height="28" rx="4" fill="var(--icon)"/>
</symbol>
<symbol id="ico-lens"  viewBox="0 0 64 64">
  <circle cx="32" cy="32" r="22" fill="var(--icon)"/>
</symbol>
</defs>
</svg>

<div class="stage">
  <div class="bg-layer" id="bgBokeh"></div>
  <div class="bg-layer" id="rainBack"></div>
  <div class="bg-layer" id="rainMid"></div>
  <div class="bg-layer" id="rainFront"></div>

  <div class="panel">
    <div class="grid">

      <div class="card">
        <div class="brand-mini">
          <img class="brand-logo" src="images/logo.png" alt="SnapRent Logo">
        </div>
        <div class="h-title">Create Your Customer Account</div>

        <?php if ($error): ?>
          <div class="alert"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="success">Registration successful! Redirecting...</div>
        <?php endif; ?>

        <form method="post" autocomplete="off" novalidate>
          <input type="hidden" name="csrf" value="<?php echo e($_SESSION['csrf']); ?>">

          <div class="label">Full Name</div>
          <input class="input" type="text" name="fullname" placeholder="John Anthony">

          <div class="label">Username</div>
          <input class="input" type="text" name="username" placeholder="johncam123" required>

          <div class="label">Email</div>
          <input class="input" type="email" name="email" placeholder="you@example.com" required>

          <?php if ($HAS_PHONE): ?>
          <div class="label">Phone (optional)</div>
          <input class="input" type="text" name="phone" placeholder="08xxxxxxxxxx">
          <?php endif; ?>

          <div class="label">Password</div>
          <div class="pw-wrap">
            <input class="input" id="pw1" type="password" name="password"
                   placeholder="Enter password..." required minlength="8">
            <span class="pw-toggle" data-target="pw1">👁</span>
          </div>

          <div class="strength-bars">
            <div class="bar" id="sb1"></div>
            <div class="bar" id="sb2"></div>
            <div class="bar" id="sb3"></div>
            <div class="bar" id="sb4"></div>
          </div>
          <div class="strength-label">
            Strength: <span id="strengthValue">-</span>
          </div>

          <div class="label">Re-enter Password</div>
          <div class="pw-wrap">
            <input class="input" id="pw2" type="password" name="password2"
                   placeholder="Repeat password..." required minlength="8">
            <span class="pw-toggle" data-target="pw2">👁</span>
          </div>

          <button class="btn" type="submit">Create Account</button>
          <div class="subtxt">
            Already have an account? <a href="login.php">Sign In</a>
          </div>
        </form>
      </div>

      <div class="right">
        <div class="welcome">Welcome to SnapRent</div>
        <img class="hero-img" src="images/mascot register.png" alt="">
      </div>

    </div>
  </div>
</div>

<script>
(function(){
  /* BOKEH */
  const b = document.getElementById('bgBokeh');
  for(let i=0;i<14;i++){
    let d=document.createElement('div');
    d.className='bokeh-dot';
    d.style.width=d.style.height=(80+Math.random()*160)+'px';
    d.style.left=(Math.random()*100)+'vw';
    d.style.top=(-20-Math.random()*60)+'vh';
    d.style.animationDuration=(25+Math.random()*45)+'s';
    b.appendChild(d);
  }

  /* RAIN */
  function rain(id,c,min,max){
    let l=document.getElementById(id);
    if(!l)return;
    for(let i=0;i<c;i++){
      let e=document.createElement('div');
      e.className='icon-drop';
      let sz=min+Math.random()*(max-min);
      e.style.width=e.style.height=sz+'px';
      e.style.left=Math.random()*100+'vw';
      e.style.animationDuration=(12+Math.random()*20)+'s';
      let svg=document.createElementNS('http://www.w3.org/2000/svg','svg');
      svg.setAttribute('viewBox','0 0 64 64');
      let use=document.createElementNS('http://www.w3.org/2000/svg','use');
      use.setAttributeNS('http://www.w3.org/1999/xlink','href','#ico-camera');
      svg.appendChild(use);
      e.appendChild(svg);
      l.appendChild(e);
    }
  }
  rain('rainBack',28,14,28);
  rain('rainMid',36,18,34);
  rain('rainFront',42,22,44);

  /* PARALLAX */
  const layers=[bgBokeh,rainBack,rainMid,rainFront].filter(Boolean);
  window.addEventListener('mousemove',(e)=>{
    const x=(e.clientX/window.innerWidth)-0.5;
    const y=(e.clientY/window.innerHeight)-0.5;
    layers.forEach(el=>{
      let d=parseFloat(el.dataset.depth||.05);
      el.style.transform=`translate(${x*d*80}px,${y*d*60}px)`;
    });
  });

  /* STRENGTH METER */
  const pw1=document.getElementById('pw1');
  const bars=[sb1,sb2,sb3,sb4];
  const lbl=document.getElementById('strengthValue');
  function sc(p){
    let s=0;
    if(p.length>=8) s++;
    if(/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
    if(/\d/.test(p)) s++;
    if(/[^A-Za-z0-9]/.test(p)) s++;
    return s;
  }
  pw1.addEventListener('input',()=>{
    let s=sc(pw1.value);
    bars.forEach((b,i)=>{
      b.className='bar'+(i<s ? ' fill-'+s : '');
    });
    const labels=['-','Weak','Normal','Good','Strong'];
    lbl.textContent = labels[s] || '-';
  });

  /* TOGGLE SHOW/HIDE PWD */
  document.querySelectorAll('.pw-toggle').forEach(t=>{
    t.addEventListener('click',()=>{
      let f=document.getElementById(t.dataset.target);
      f.type = (f.type==='password' ? 'text' : 'password');
    });
  });

})();
</script>

</body>
</html>
