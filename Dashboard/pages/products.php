<?php
// ======================================================================
// products.php — SnapRent Admin • Products (multi-upload + preview + robust AJAX delete)
// - Multi upload up to 6 per product, server-side strict
// - Square crop 1:1 (GD), simpan di uploads/cameras/{camera_id}/filename.ext
// - New Product: preview di bawah input + panel kanan
// - Edit Product: preview server + file baru, hapus gambar via AJAX (tanpa nested form)
// - Modal Edit dibangun via output buffering (ob_start)
// - PATCH: slot kosong pada upload diabaikan → tidak ada lagi "File #1 gagal diupload"
// ======================================================================

// ---------- Konfigurasi ----------
const MAX_IMAGES_PER_PRODUCT = 6;
const MAX_FILE_SIZE_BYTES    = 8 * 1024 * 1024; // 8MB  
const ALLOWED_MIMES = ['image/jpeg'=>'.jpg','image/png'=>'.png','image/webp'=>'.webp'];

// ---------- Helpers ----------
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('rupiah')) { function rupiah($n){ if ($n === null || $n === '') return 'Rp 0'; return 'Rp '.number_format((float)$n,0,',','.'); } }

// ---------- Upload base ----------
$UPLOAD_DIR_FS = realpath(__DIR__ . '/../uploads');
if ($UPLOAD_DIR_FS === false) {
  $UPLOAD_DIR_FS = __DIR__ . '/../uploads';
  if (!is_dir($UPLOAD_DIR_FS)) @mkdir($UPLOAD_DIR_FS,0777,true);
}

// URL base ke /uploads
$DOCROOT = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$UPLOAD_PATH_NORM = rtrim(str_replace('\\','/', $UPLOAD_DIR_FS), '/');
if ($DOCROOT && strpos($UPLOAD_PATH_NORM, $DOCROOT) === 0) {
  $UPLOAD_URL = substr($UPLOAD_PATH_NORM, strlen($DOCROOT));
  if ($UPLOAD_URL === '') $UPLOAD_URL = '/';
  if ($UPLOAD_URL[0] !== '/') $UPLOAD_URL = '/'.$UPLOAD_URL;
  if (substr($UPLOAD_URL,-1) !== '/') $UPLOAD_URL .= '/';
} else {
  $BASE_DIR = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'); // ex: /SnapRent/Dashboard
  $UPLOAD_URL = $BASE_DIR . '/uploads/';
}

function cameraDirFs(int $cid): string {
  global $UPLOAD_DIR_FS;
  $p = rtrim($UPLOAD_DIR_FS,'/\\').DIRECTORY_SEPARATOR.'cameras'.DIRECTORY_SEPARATOR.$cid;
  if (!is_dir($p)) @mkdir($p,0777,true);
  return $p;
}
function cameraDirUrl(int $cid): string {
  global $UPLOAD_URL; return rtrim($UPLOAD_URL,'/').'/cameras/'.$cid.'/';
}
function rrmdir(string $dir): void {
  if (!is_dir($dir)) return;
  foreach (scandir($dir) ?: [] as $f) {
    if ($f==='.'||$f==='..') continue;
    $full=$dir.DIRECTORY_SEPARATOR.$f;
    if (is_dir($full)) rrmdir($full); else @unlink($full);
  }
  @rmdir($dir);
}

// ---------- Image Processing (square crop 1:1) ----------
function crop_to_square_and_save(string $tmp, string $dest, string $mime): bool {
  if (!function_exists('imagecreatetruecolor')) return move_uploaded_file($tmp,$dest);
  switch ($mime) {
    case 'image/jpeg': $src=@imagecreatefromjpeg($tmp); $out='jpeg'; break;
    case 'image/png' : $src=@imagecreatefrompng($tmp);  $out='png';  break;
    case 'image/webp': $src=@imagecreatefromwebp($tmp); $out='webp'; break;
    default: $src=null; $out=null;
  }
  if(!$src) return move_uploaded_file($tmp,$dest);
  $w=imagesx($src); $h=imagesy($src); $s=min($w,$h); $sx=(int)(($w-$s)/2); $sy=(int)(($h-$s)/2);
  $dst=imagecreatetruecolor($s,$s);
  if($out==='png'||$out==='webp'){
    imagealphablending($dst,false); imagesavealpha($dst,true);
    $t=imagecolorallocatealpha($dst,0,0,0,127); imagefilledrectangle($dst,0,0,$s,$s,$t);
  }
  imagecopyresampled($dst,$src,0,0,$sx,$sy,$s,$s,$s,$s);
  $ok=false;
  switch($out){
    case 'jpeg': $ok=imagejpeg($dst,$dest,90); break;
    case 'png' : $ok=imagepng($dst,$dest); break;
    case 'webp': $ok=function_exists('imagewebp')?imagewebp($dst,$dest,85):false; break;
  }
  imagedestroy($src); imagedestroy($dst);
  if(!$ok) return move_uploaded_file($tmp,$dest);
  @unlink($tmp);
  return true;
}

// ---------- DB helpers ----------
function getImages(PDO $pdo, int $cid, int $limit = MAX_IMAGES_PER_PRODUCT): array {
  $st=$pdo->prepare("SELECT id, filename FROM camera_images WHERE camera_id=? ORDER BY id DESC LIMIT ?");
  $st->bindValue(1,$cid,PDO::PARAM_INT); $st->bindValue(2,$limit,PDO::PARAM_INT);
  $st->execute(); return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
function countImages(PDO $pdo, int $cid): int {
  $st=$pdo->prepare("SELECT COUNT(*) FROM camera_images WHERE camera_id=?"); $st->execute([$cid]);
  return (int)$st->fetchColumn();
}

// ---------- Multi upload (PATCH: skip slot kosong) ----------
function normalizeFilesArray(?array $files): array {
  if (!$files || !isset($files['name'])) return [];
  $names = $files['name'];
  $tmps  = $files['tmp_name'];
  $errs  = $files['error'];
  $sizes = $files['size'];

  if (!is_array($names)) {
    $names = [$names];
    $tmps  = [$tmps];
    $errs  = [$errs];
    $sizes = [$sizes];
  }

  $out = [];
  foreach ($names as $i => $n) {
    $name = $n ?? '';
    $tmp  = $tmps[$i]  ?? '';
    $err  = $errs[$i]  ?? UPLOAD_ERR_NO_FILE;
    $size = (int)($sizes[$i] ?? 0);

    // Abaikan slot kosong sepenuhnya
    if ($err === UPLOAD_ERR_NO_FILE || $size === 0 || !$tmp) {
      continue;
    }
    $out[] = ['name'=>$name, 'tmp_name'=>$tmp, 'error'=>$err, 'size'=>$size];
  }
  return $out;
}

function saveImageForCamera(int $cid, string $tmp, string $mime): ?string {
  if(!isset(ALLOWED_MIMES[$mime])) return null;
  $ext=ALLOWED_MIMES[$mime];
  $dir=cameraDirFs($cid);
  $new=uniqid('cam_',true).$ext;
  $dest=rtrim($dir,'/\\').DIRECTORY_SEPARATOR.$new;
  if(!crop_to_square_and_save($tmp,$dest,$mime)) return null;
  return $new;
}

function handleMultiUploadsForCamera(PDO $pdo, int $cid, string $field, int $maxAdd): array {
  if ($maxAdd <= 0) return ['saved'=>[], 'skipped'=>0, 'errors'=>['Gallery full']];

  // Ambil hanya file yang benar-benar ada (slot kosong sudah di-skip)
  $list = normalizeFilesArray($_FILES[$field] ?? null);
  if (!$list) return ['saved'=>[], 'skipped'=>0, 'errors'=>[]];

  $saved = []; $skipped = 0; $errors = [];
  $finfo = finfo_open(FILEINFO_MIME_TYPE);

  foreach ($list as $i => $f) {
    if (count($saved) >= $maxAdd) { $skipped++; continue; }

    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
      // Ini benar-benar error file, bukan slot kosong
      $errors[] = "File #".($i+1)." gagal diupload.";
      continue;
    }
    if ($f['size'] > MAX_FILE_SIZE_BYTES) { $errors[] = "File #".($i+1)." > ".(int)(MAX_FILE_SIZE_BYTES/1048576)."MB."; continue; }

    $mime = finfo_file($finfo, $f['tmp_name']);
    if (!isset(ALLOWED_MIMES[$mime])) { $errors[] = "File #".($i+1)." tipe tidak didukung."; continue; }

    $fn = saveImageForCamera($cid, $f['tmp_name'], $mime);
    if ($fn) {
      $pdo->prepare("INSERT INTO camera_images(camera_id, filename) VALUES(?,?)")->execute([$cid,$fn]);
      $saved[] = $fn;
    } else {
      $errors[] = "File #".($i+1)." gagal disimpan.";
    }
  }

  finfo_close($finfo);
  return ['saved'=>$saved, 'skipped'=>$skipped, 'errors'=>$errors];
}

// ---------- CSRF ----------
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

// ---------- Actions ----------
$alerts=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!isset($_POST['csrf']) || $_POST['csrf'] !== $csrf) { http_response_code(400); die('CSRF invalid'); }
  $action = $_POST['action'] ?? '';

  // CREATE
  if ($action==='create_camera') {
    $name=trim($_POST['name']??''); $brand=trim($_POST['brand']??''); $type=trim($_POST['type']??''); $problem=trim($_POST['problem']??'');
    $daily=(float)($_POST['daily_price']??0); $status=trim($_POST['status']??'available');
    if ($name && $brand && $daily>0) {
      $pdo->prepare("INSERT INTO cameras(name,brand,type,problem,daily_price,status,created_at) VALUES(?,?,?,?,?,?,NOW())")
          ->execute([$name,$brand,$type,$problem,$daily,$status]);
      $newId=(int)$pdo->lastInsertId();
      $res = handleMultiUploadsForCamera($pdo,$newId,'images',MAX_IMAGES_PER_PRODUCT);
      $qs='created=1&added='.count($res['saved']); if($res['skipped']>0) $qs.='&skipped='.$res['skipped']; if(!empty($res['errors'])) $qs.='&err='.urlencode(implode(' | ',$res['errors']));
      header('Location: ?page=products&'.$qs); exit;
    } else { $alerts[]=['type'=>'danger','msg'=>'Nama, brand, dan harga wajib diisi (harga > 0).']; }
  }

  // UPDATE
  if ($action==='update_camera') {
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??''); $brand=trim($_POST['brand']??''); $type=trim($_POST['type']??''); $problem=trim($_POST['problem']??'');
    $daily=(float)($_POST['daily_price']??0); $status=trim($_POST['status']??'available');
    if ($id>0 && $name && $brand && $daily>0) {
      $pdo->prepare("UPDATE cameras SET name=?, brand=?, type=?, problem=?, daily_price=?, status=? WHERE id=?")
          ->execute([$name,$brand,$type,$problem,$daily,$status,$id]);
      $current=countImages($pdo,$id); $remain=MAX_IMAGES_PER_PRODUCT-$current;
      $res = handleMultiUploadsForCamera($pdo,$id,'images',max(0,$remain));
      $qs='updated=1&added='.count($res['saved']); if($res['skipped']>0) $qs.='&skipped='.$res['skipped']; if(!empty($res['errors'])) $qs.='&err='.urlencode(implode(' | ',$res['errors']));
      header('Location: ?page=products&'.$qs); exit;
    } else { $alerts[]=['type'=>'danger','msg'=>'Nama, brand, dan harga wajib diisi (harga > 0).']; }
  }

  // DELETE product
  if ($action==='delete_camera') {
    $id=(int)$_POST['id'];
    if($id>0){
      $pdo->beginTransaction();
      try{
        $pdo->prepare("DELETE FROM camera_images WHERE camera_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM cameras WHERE id=?")->execute([$id]);
        $pdo->commit();
      }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
      $dir=cameraDirFs($id); if(is_dir($dir)) rrmdir($dir);
    }
    header('Location: ?page=products&deleted=1'); exit;
  }

  // DELETE single image (AJAX robust)
  if ($action==='delete_image') {
    $imgId=(int)($_POST['image_id']??0); $cid=(int)($_POST['camera_id']??0);
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    $ok=false;
    if ($imgId>0 && $cid>0) {
      $st=$pdo->prepare("SELECT filename FROM camera_images WHERE id=? AND camera_id=?");
      $st->execute([$imgId,$cid]);
      if ($fn=$st->fetchColumn()) {
        $full=rtrim(cameraDirFs($cid),'/\\').DIRECTORY_SEPARATOR.$fn;
        if (is_file($full)) @unlink($full);
        $pdo->prepare("DELETE FROM camera_images WHERE id=?")->execute([$imgId]);
        $ok=true;
      }
    }
    if ($isAjax){
      if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
      header_remove('Content-Type');
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>$ok], JSON_UNESCAPED_SLASHES);
      exit;
    }
    header('Location: ?page=products&updated=1&imgdel='.($ok?1:0)); exit;
  }
}

// ---------- Filters ----------
$need   = isset($_GET['need']) ? (int)$_GET['need'] : 0;
$q      = trim($_GET['q'] ?? ''); $brandF = trim($_GET['brand'] ?? ''); $statusF= trim($_GET['status'] ?? '');
$minp   = trim($_GET['min_price'] ?? ''); $maxp = trim($_GET['max_price'] ?? '');
$where=[]; $args=[];
if ($q!==''){ $where[]='(cam.name LIKE ? OR cam.type LIKE ? OR cam.brand LIKE ? OR cam.problem LIKE ?)'; $args[]="%$q%"; $args[]="%$q%"; $args[]="%$q%"; $args[]="%$q%";}
if ($brandF!==''){ $where[]='cam.brand = ?'; $args[]=$brandF; }
if ($statusF!==''){ $where[]='cam.status = ?'; $args[]=$statusF; }
if ($need){ $where[]="cam.status IN ('unavailable','maintenance')"; }
if ($minp!==''){ $where[]='cam.daily_price >= ?'; $args[]=(float)$minp; }
if ($maxp!==''){ $where[]='cam.daily_price <= ?'; $args[]=(float)$maxp; }
$whereSql=$where?('WHERE '.implode(' AND ',$where)):'';

// ---------- Pagination ----------
// ---------- Pagination & Sorting ----------
$per = min(50, max(1, (int)($_GET['per'] ?? 50)));      // default 50, batas maksimal 50
$pageNo = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pageNo - 1) * $per;

// Sorting whitelist
$sort = $_GET['sort'] ?? 'created_at';
$dir  = strtolower($_GET['dir'] ?? 'desc');
$allowedSort = ['created_at','name','brand','type','daily_price','status'];
if (!in_array($sort, $allowedSort, true)) $sort = 'created_at';
$dir = ($dir === 'asc') ? 'ASC' : 'DESC';
$orderSql = "ORDER BY cam.$sort $dir";

// Helper buat link sorting & pagination (preserve query)
function build_qs(array $overrides = []) {
  $params = $_GET;
  foreach ($overrides as $k=>$v) {
    if ($v === null) unset($params[$k]); else $params[$k] = $v;
  }
  return '?'.http_build_query($params);
}
function sort_link($key, $label) {
  $curSort = $_GET['sort'] ?? 'created_at';
  $curDir  = strtolower($_GET['dir'] ?? 'desc');
  $nextDir = ($curSort === $key && $curDir === 'asc') ? 'desc' : 'asc';
  $qs = build_qs(['sort'=>$key, 'dir'=>$nextDir, 'p'=>1]);
  $icon = ($curSort === $key) ? ($curDir === 'asc' ? ' ▲' : ' ▼') : '';
  return '<a href="'.e($qs).'" class="text-decoration-none">'.$label.$icon.'</a>';
}

// ---------- Data ----------
$brand_list=$pdo->query("SELECT DISTINCT brand FROM cameras ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);
$stmt=$pdo->prepare("SELECT COUNT(*) FROM cameras cam $whereSql"); $stmt->execute($args); $total=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare("
  SELECT cam.id, cam.name, cam.brand, cam.type, cam.problem, cam.daily_price, cam.status, cam.created_at,
         (SELECT ci.filename FROM camera_images ci WHERE ci.camera_id = cam.id ORDER BY ci.id DESC LIMIT 1) AS image
  FROM cameras cam
  $whereSql
  $orderSql
  LIMIT $per OFFSET $offset
"); $stmt->execute($args);
$cameras=$stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- UI ----------
?>
<style>
:root{ --bg:#f5f7fb; --card:#fff; --line:#e9edf3; --text:#2b2f39; --muted:#6b7280; --accent:#e11d48; --green:#22c55e; --red:#d21f3c; }
body{background:var(--bg); font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:var(--text);}
.page-card{background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px;}
.header-actions .btn{border-radius:12px; padding:.6rem 1rem; font-weight:600;}
.btn-primary-modern{background:#1f6feb; color:#fff; border:none;} .btn-primary-modern:hover{filter:brightness(.95);}
.products-tabs{display:flex; gap:28px; border-bottom:2px solid var(--line); margin:6px 0 18px;}
.products-tabs a{padding:10px 0; font-weight:600; color:#6b7280; text-decoration:none; position:relative;}
.products-tabs a.active{color:var(--text);} .products-tabs a.active::after{content:''; position:absolute; left:0; right:0; bottom:-2px; height:3px; background:var(--accent); border-radius:3px 3px 0 0;}
.filter-grid .form-label{font-size:12px; color:var(--muted); margin-bottom:6px;}
.form-control,.form-select{border-radius:10px; border:1px solid var(--line); background:#fff;}
.table-wrap{background:var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden;}
.table{margin-bottom:0;} .table thead th{background:#f3f6fb; color:#6b7280; font-weight:700; font-size:13px; border-bottom:1px solid var(--line);}
.table tbody td{vertical-align:middle; border-top:1px solid var(--line);} .table tbody tr:hover{background:#fafbfe;}
.thumb{width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid var(--line); background:#f6f7fb;}
.status-badge{display:inline-block; padding:6px 10px; border-radius:10px; font-size:12px; font-weight:700;}
.status-available{background:#eafaf0; color:#09824a; border:1px solid #bfe6cc;} .status-unavailable{background:#fde5e5; color:var(--red); border:1px solid #f1b3bc;}
.count-label{font-weight:700; margin:10px 2px 14px 6px;}
.action-btn{width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid var(--line); background:#fff;}
.action-btn:hover{background:#f3f6fb;}

/* New/Edit tiles & thumbs */
.np-tile{flex:0 0 140px;height:100px;border:2px dashed #cfd6e4;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-direction:column;font-size:12px;color:#6b7280;cursor:pointer}
.np-tile input{display:none}
.np-thumbs{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
.np-thumb{position:relative;width:72px;height:72px;border:1px solid #e9edf3;border-radius:10px;background:#e9edf3;background-size:cover;background-position:center}
.np-thumb .np-del{position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;border:none;background:#d21f3c;color:#fff;font-size:14px;line-height:22px;text-align:center;cursor:pointer}
.np-thumb .np-del:hover{filter:brightness(.9)}

/* Preview card */
.pv-card .pv-title{font-weight:700;margin-bottom:12px}
.pv-card .pv-hero{width:100%;aspect-ratio:1/1;border-radius:12px;background:#e5e7eb;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e9edf3}
.pv-card .pv-hero img{width:100%;height:100%;object-fit:cover}
.pv-card .pv-hero .pv-ph{display:flex;align-items:center;justify-content:center;width:64px;height:64px;border:1px dashed #cbd5e1}
.pv-card .pv-thumbs{display:flex;gap:8px;margin-top:10px}
.pv-card .pv-thumb{width:30px;height:30px;border-radius:6px;border:1px solid #e5e7eb;background:#e5e7eb;background-size:cover;background-position:center;cursor:pointer;opacity:.9}
.pv-card .pv-thumb:hover{opacity:1}
.pv-card .pv-name{font-weight:700;text-align:center;margin-top:12px}
.pv-card .pv-sub{color:#6b7280;font-size:12px;text-align:center;margin-top:2px}
.pv-card .pv-price{margin-top:6px;text-align:center}
.pv-card .pv-price .rp{font-weight:600}
.pv-card .pv-list{margin:8px 0 10px 16px;font-size:14px;color:#111827}
.pv-card .pv-cta{text-align:center}
.pv-card .pv-cta .btn{padding:.35rem .8rem;border-radius:8px}
.pv-card .pv-note{font-size:11px;color:#6b7280;font-style:italic;text-align:center;margin-top:6px}
</style>

<!-- GLOBAL: Price masking util -->
<script>
(function(){
  function onlyDigits(s){ return (s||'').replace(/\D+/g,''); }
  function formatIDR(numStr){ return numStr ? 'Rp ' + numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : 'Rp 0'; }
  function setCaret(input, pos){ try{ input.setSelectionRange(pos,pos);}catch(e){} }
  function wirePriceInput(viewEl, hiddenEl){
    if(!viewEl||!hiddenEl) return;
    const seed = onlyDigits(viewEl.value); viewEl.value = formatIDR(seed); hiddenEl.value = seed || '0';
    viewEl.addEventListener('beforeinput', (e)=>{
      const sel=viewEl.selectionStart??0;
      if((e.inputType==='deleteContentBackward'||e.inputType==='deleteContentForward') && sel<=3 && viewEl.selectionStart===viewEl.selectionEnd){ e.preventDefault(); }
      if(e.inputType==='insertText' && e.data && /\D/.test(e.data)) e.preventDefault();
    });
    viewEl.addEventListener('input', ()=>{
      const old=viewEl.value; const pos=viewEl.selectionStart ?? old.length;
      const digits = onlyDigits(old); viewEl.value = formatIDR(digits);
      const leftDigits = onlyDigits(old.slice(0,pos)).length; const groups=Math.floor(Math.max(0,leftDigits-1)/3);
      const newPos = 3 + leftDigits + groups; setCaret(viewEl, Math.min(newPos, viewEl.value.length));
      hiddenEl.value = digits || '0';
    });
    viewEl.addEventListener('blur', ()=>{ viewEl.value = formatIDR(onlyDigits(viewEl.value)); });
  }
  window.__wirePriceInput = wirePriceInput;
})();
</script>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-2">
  <div><h4 class="mb-1">Products</h4><div class="small text-muted">Manage camera inventory</div></div>
  <div class="header-actions"><button class="btn btn-primary-modern" data-bs-toggle="modal" data-bs-target="#createProductModal"><i class="fas fa-plus"></i> New Product</button></div>
</div>

<?php $need = isset($_GET['need']) ? (int)$_GET['need'] : 0; ?>
<div class="products-tabs">
  <a href="?page=products" class="<?= $need ? '' : 'active' ?>">All</a>
  <a href="?page=products&need=1" class="<?= $need ? 'active' : '' ?>">Need Action</a>
</div>

<?php
$getAlerts=[];
if(isset($_GET['created'])) $getAlerts[]=['type'=>'success','msg'=>'Product created successfully.'];
if(isset($_GET['updated'])) $getAlerts[]=['type'=>'success','msg'=>'Product updated.'];
if(isset($_GET['deleted'])) $getAlerts[]=['type'=>'warning','msg'=>'Product deleted.'];
if(isset($_GET['imgdel']))  $getAlerts[]=['type'=>'info','msg'=>'Image deleted.'];
if(isset($_GET['added']))   $getAlerts[]=['type'=>'info','msg'=>(int)$_GET['added'].' new photo(s) added.'];
if(isset($_GET['skipped'])) $getAlerts[]=['type'=>'warning','msg'=>(int)$_GET['skipped'].' file(s) skipped (gallery full).'];
if(isset($_GET['err']))     $getAlerts[]=['type'=>'danger','msg'=>e($_GET['err'])];
foreach(array_merge($getAlerts,$alerts) as $al): ?>
  <div class="alert alert-<?= e($al['type']) ?> alert-dismissible fade show"><?= $al['msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endforeach; ?>

<!-- Filter -->
<form class="page-card mb-3 filter-grid" method="get">
  <input type="hidden" name="page" value="products"><?php if($need): ?><input type="hidden" name="need" value="1"><?php endif; ?>
  <div class="row g-3 align-items-end">
    <div class="col-12 col-lg-3"><label class="form-label">Search</label><input type="text" class="form-control" name="q" value="<?= e($q ?? '') ?>" placeholder="Name / Type"></div>
    <div class="col-6 col-lg-2"><label class="form-label">Brand</label><select class="form-select" name="brand"><option value="">All</option><?php foreach($brand_list as $b): ?><option value="<?= e($b) ?>" <?= ($brandF ?? '')===$b?'selected':'' ?>><?= e($b) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-lg-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All</option><option value="available"   <?= ($statusF ?? '')==='available'   ? 'selected':'' ?>>Available</option><option value="unavailable" <?= ($statusF ?? '')==='unavailable' ? 'selected':'' ?>>Unavailable</option><option value="maintenance" <?= ($statusF ?? '')==='maintenance' ? 'selected':'' ?>>Maintenance</option></select></div>
    <div class="col-6 col-lg-2"><label class="form-label">Min Price</label><input type="number" step="0.01" class="form-control" name="min_price" value="<?= e($minp ?? '') ?>"></div>
    <div class="col-6 col-lg-2"><label class="form-label">Max Price</label><input type="number" step="0.01" class="form-control" name="max_price" value="<?= e($maxp ?? '') ?>"></div>
    <div class="col-6 col-lg-2">
      <label class="form-label">Per Page</label>
      <select class="form-select" name="per">
        <?php
          $perChoices = [10,25,50];
          foreach($perChoices as $pc){
            $sel = ($per == $pc) ? 'selected' : '';
            echo "<option value=\"$pc\" $sel>$pc</option>";
          }
        ?>
      </select>
    </div>
    <div class="col-12 col-lg-1 d-grid"><button class="btn btn-primary-modern">Filter</button></div>
  </div>
</form>

<div class="count-label"><?= (int)$total ?> Produk</div>

<div class="table-wrap">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <?php if(!$need): ?>
    <tr>
      <th>#</th>
      <th>Photo</th>
      <th><?= sort_link('name','Name') ?></th>
      <th><?= sort_link('brand','Brand') ?></th>
      <th><?= sort_link('type','Type') ?></th>
      <th><?= sort_link('daily_price','Daily Price') ?></th>
      <th><?= sort_link('status','Status') ?></th>
      <th><?= sort_link('created_at','Created') ?></th>
      <th class="text-end">Actions</th>
    </tr>
    <?php else: ?>
    <tr>
      <th>#</th>
      <th>Photo</th>
      <th><?= sort_link('name','Name') ?></th>
      <th><?= sort_link('brand','Brand') ?></th>
      <th><?= sort_link('type','Type') ?></th>
      <th>Problem</th>
      <th><?= sort_link('status','Status') ?></th>
      <th><?= sort_link('created_at','Created') ?></th>
      <th class="text-end">Action</th>
    </tr>
    <?php endif; ?>
  </thead>
  <tbody>
<?php
$i=$offset+1; $modalBuffer='';
foreach($cameras as $cam):
  $cid=(int)$cam['id'];
  $thumb = !empty($cam['image']) ? cameraDirUrl($cid).$cam['image'] : null;
  $gallery = getImages($pdo,$cid,MAX_IMAGES_PER_PRODUCT);
?>
  <tr>
    <td><?= $i++ ?></td>
    <td><?php if($thumb): ?><img class="thumb" src="<?= e($thumb) ?>" alt="<?= e($cam['name']) ?>" onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('div'),{className:'text-muted',textContent:'—'}));"><?php else: ?><div class="text-muted">—</div><?php endif; ?></td>
    <td><?= e($cam['name']) ?></td>
    <td><?= e($cam['brand']) ?></td>
    <td><?= e($cam['type']) ?></td>
    <?php if(!$need): ?>
      <td><?= rupiah($cam['daily_price']) ?></td>
      <?php $cls=(strtolower($cam['status'])==='available')?'status-available':'status-unavailable'; ?>
      <td><span class="status-badge <?= $cls ?>"><?= ucfirst(e($cam['status'])) ?></span></td>
    <?php else: ?>
      <td><?= e($cam['problem'] ?: '-') ?></td>
      <td><span class="status-badge status-unavailable"><?= ucfirst(e($cam['status'])) ?></span></td>
    <?php endif; ?>
    <td><?= e($cam['created_at']) ?></td>
    <td class="text-end">
      <button class="action-btn" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $cid ?>"><i class="fas fa-pencil-alt"></i></button>
      <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?');">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="delete_camera">
        <input type="hidden" name="id" value="<?= $cid ?>">
        <button class="action-btn"><i class="fas fa-trash"></i></button>
      </form>
    </td>
  </tr>
<?php
  // ----- Build Edit Modal dengan output buffering -----
  ob_start();
  ?>
  <div class="modal fade" id="editProductModal<?= (int)$cid ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
      <form method="post" class="modal-content" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="update_camera">
        <input type="hidden" name="id" value="<?= (int)$cid ?>">
        <input type="hidden" name="daily_price" id="ep_daily_price_real_<?= (int)$cid ?>">

        <div class="modal-header"><h5 class="modal-title">Edit Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <style>
            #editProductModal<?= (int)$cid ?> .np-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:24px;}
            #editProductModal<?= (int)$cid ?> .np-card{background:#fff;border:1px solid #e9edf3;border-radius:14px;padding:16px;}
            #editProductModal<?= (int)$cid ?> .np-photos{display:flex;gap:12px;flex-wrap:wrap}
            #editProductModal<?= (int)$cid ?> .np-thumbs{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
          </style>

          <div class="np-grid">
            <div class="np-card">
              <div class="np-title" style="font-weight:700;margin-bottom:12px;">Product Information</div>

              <div class="mb-3">
                <div class="form-label">Product Photos</div>
                <div class="np-photos">
                  <label class="np-tile">
                    <span>Add photos (<span id="ep_count_<?= (int)$cid ?>">0</span>/<?= MAX_IMAGES_PER_PRODUCT ?>)</span>
                    <input type="file" name="images[]" accept="image/*" id="ep_images_<?= (int)$cid ?>" multiple>
                  </label>
                </div>
                <div class="np-thumbs" id="ep_thumbs_<?= (int)$cid ?>">
                  <?php foreach($gallery as $g): $gUrl = cameraDirUrl($cid).$g['filename']; ?>
                    <div class="np-thumb" style="background-image:url('<?= e($gUrl) ?>')">
                      <button type="button" class="np-del" data-cam="<?= (int)$cid ?>" data-img="<?= (int)$g['id'] ?>" title="Delete">✕</button>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="small text-muted mt-1">*max <?= MAX_IMAGES_PER_PRODUCT ?> photos (server accepts remaining slots only)</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input class="form-control" name="name" id="ep_name_<?= (int)$cid ?>" value="<?= e($cam['name']) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Product Price</label>
                <?php $priceFmt = 'Rp '.number_format((float)$cam['daily_price'],0,',','.'); ?>
                <input class="form-control" id="ep_daily_price_view_<?= (int)$cid ?>" value="<?= e($priceFmt) ?>" inputmode="numeric" autocomplete="off" required>
                <div class="small text-muted">Per day. Saved as a number.</div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Product Category</label>
                    <select class="form-select" name="type" id="ep_category_<?= (int)$cid ?>">
                      <?php
                        // Hanya 4 kategori baru
                        $opts = ['Analog','Digicam','DSLR','Mirrorless'];
                        $sel  = (string)($cam['type'] ?? '');

                        echo '<option value="">Category</option>';

                        // Jika data lama (mis. "Action Cam", "Lens", dll) masih ada di DB,
                        // tampilkan sebagai fallback agar data tidak hilang saat edit.
                        if ($sel !== '' && !in_array($sel, $opts, true)) {
                          echo '<option selected>'.e($sel).'</option>';
                        }

                        foreach ($opts as $o) {
                          $s = ($sel === $o) ? 'selected' : '';
                          echo '<option '.$s.'>'.e($o).'</option>';
                        }
                      ?>
                    </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Brand</label>
                  <input class="form-control" name="brand" id="ep_brand_<?= (int)$cid ?>" value="<?= e($cam['brand']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status" id="ep_status_<?= (int)$cid ?>">
                    <?php
                      $st = strtolower((string)$cam['status']);
                      $opts = ['available','unavailable','maintenance'];
                      foreach($opts as $o){
                        $s = ($st===$o) ? 'selected' : '';
                        echo '<option value="'.e($o).'" '.$s.'>'.ucfirst($o).'</option>';
                      }
                    ?>
                  </select>
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label">Product Description</label>
                <textarea class="form-control" rows="5" name="problem" id="ep_desc_<?= (int)$cid ?>" placeholder="Add product description"><?= e($cam['problem']) ?></textarea>
              </div>

              <div class="np-actions-bar" style="background:#284466;padding:16px;border-radius:0 0 14px 14px;display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Back</button>
                <button class="btn btn-primary" type="submit" id="ep_submit_<?= (int)$cid ?>">Save &amp; Display</button>
              </div>
            </div>

            <?php
              $serverUrls = array_map(fn($g)=>cameraDirUrl($cid).$g['filename'],$gallery);
              $serverUrlsJson = htmlspecialchars(json_encode($serverUrls, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
              $typeTxt = e($cam['type'] ?: 'Category');
              $descLines = array_slice(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string)($cam['problem'] ?? '')))),0,4);
              if(!$descLines){ $descLines=['dummy text of the printing and typesetting','it over 2000 years old','variations of passages','popularised in the 1960s']; }
            ?>
            <aside class="np-preview pv-card" id="ep_preview_<?= (int)$cid ?>" data-images="<?= $serverUrlsJson ?>">
              <div class="pv-title">Preview</div>
              <div class="pv-hero">
                <div class="pv-ph" id="ep_pv_ph_<?= (int)$cid ?>">
                  <svg viewBox="0 0 24 24" width="28" height="28"><path d="M9.5 4h5l1 2H19a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a3 3 0 0 1 3-3h3.5l1-2Zm2.5 4a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 2.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Z"/></svg>
                </div>
                <img id="ep_pv_img_<?= (int)$cid ?>" alt="" style="display:none;">
              </div>
              <div class="pv-thumbs" id="ep_pv_thumbs_<?= (int)$cid ?>"></div>

              <div class="pv-name" id="ep_pv_name_<?= (int)$cid ?>"><?= e($cam['name']) ?></div>
              <div class="pv-sub" id="ep_pv_sub_<?= (int)$cid ?>"><?= $typeTxt ?></div>
              <div class="pv-price"><span class="rp" id="ep_pv_price_<?= (int)$cid ?>"><?= e($priceFmt) ?></span> <span class="text-muted">/hari</span></div>
              <ul class="pv-list" id="ep_pv_list<?= (int)$cid ?>">
                <?php foreach($descLines as $dl): ?><li><?= e($dl) ?></li><?php endforeach; ?>
              </ul>
              <div class="pv-cta"><button type="button" class="btn btn-primary btn-sm" disabled>Add to cart</button></div>
              <div class="pv-note">*just for reference only, when on the web it will be a little different</div>
            </aside>
          </div>

          <script>
          (function(){
            const modal = document.getElementById("editProductModal<?= (int)$cid ?>"); if(!modal) return;
            const $ = s => modal.querySelector(s);

            // Harga
            const priceView = $("#ep_daily_price_view_<?= (int)$cid ?>");
            const priceReal = $("#ep_daily_price_real_<?= (int)$cid ?>");
            window.__wirePriceInput(priceView, priceReal);
            priceView?.addEventListener("input", ()=>{ const p=$("#ep_pv_price_<?= (int)$cid ?>"); if(p) p.textContent = priceView.value || "Rp 0"; });

            // Name/category mirrors
            const name=$("#ep_name_<?= (int)$cid ?>"), pvName=$("#ep_pv_name_<?= (int)$cid ?>");
            const cat=$("#ep_category_<?= (int)$cid ?>"), pvSub=$("#ep_pv_sub_<?= (int)$cid ?>");
            name?.addEventListener("input", ()=>{ pvName.textContent = name.value || "—"; });
            cat?.addEventListener("change", ()=>{ pvSub.textContent = cat.value || "Category"; });

            // Desc → UL
            const desc=$("#ep_desc_<?= (int)$cid ?>"); const pvList=document.getElementById("ep_pv_list<?= (int)$cid ?>");
            function renderDesc(text){ const lines=(text||"").split(/\r?\n/).map(s=>s.trim()).filter(Boolean).slice(0,4);
              const fallback=["dummy text of the printing and typesetting","it over 2000 years old","variations of passages","popularised in the 1960s"];
              const items=lines.length?lines:fallback; if(pvList) pvList.innerHTML=items.map(t=>`<li>${t.replace(/</g,"&lt;").replace(/>/g,"&gt;")}</li>`).join(""); }
            renderDesc(desc?desc.value:""); desc&&desc.addEventListener("input",()=>renderDesc(desc.value));

            // Preview hero & thumbs
            const pvRoot=document.getElementById("ep_preview_<?= (int)$cid ?>");
            const heroImg=document.getElementById("ep_pv_img_<?= (int)$cid ?>"); const ph=document.getElementById("ep_pv_ph_<?= (int)$cid ?>");
            const pvThumbs=document.getElementById("ep_pv_thumbs_<?= (int)$cid ?>");
            const input=$("#ep_images_<?= (int)$cid ?>");
            const serverWrap=$("#ep_thumbs_<?= (int)$cid ?>");
            const countEl=$("#ep_count_<?= (int)$cid ?>");

            function setHero(src){ if(src){ heroImg.src=src; heroImg.style.display="block"; ph.style.display="none"; } else { heroImg.style.display="none"; ph.style.display="flex"; } }
            function buildPvThumbs(urls){
              pvThumbs.innerHTML = "";
              urls.slice(0, <?= MAX_IMAGES_PER_PRODUCT ?>).forEach((u, i) => {
                const d = document.createElement("div");
                d.className = "pv-thumb";
                d.style.backgroundImage = `url(${u})`;
                d.title = "Photo " + (i + 1);
                d.addEventListener("click", () => setHero(u));
                pvThumbs.appendChild(d);
              });
            }
            function getServerUrlsFromDom(){
              return Array.from(serverWrap.querySelectorAll(".np-thumb")).map(el=>{
                const bg=getComputedStyle(el).backgroundImage||""; const m=bg.match(/url\(["']?(.*?)["']?\)/i); return m?m[1]:null;
              }).filter(Boolean);
            }
            const initial = (()=>{ try{return JSON.parse(pvRoot.dataset.images||"[]")}catch(_){return []} })();
            if(initial.length){ setHero(initial[0]); buildPvThumbs(initial); } else { setHero(null); buildPvThumbs([]); }

            // New files preview
            input?.addEventListener("change", ()=>{
              const files = Array.from(input.files||[]).slice(0, <?= MAX_IMAGES_PER_PRODUCT ?>);
              if(countEl) countEl.textContent = String(files.length);
              if(!files.length){ const cur=getServerUrlsFromDom(); setHero(cur[0]||null); buildPvThumbs(cur); return; }
              const urls=[]; let done=0;
              files.forEach((f,idx)=>{ const r=new FileReader(); r.onload=e=>{ urls[idx]=e.target.result; done++; if(done===1) setHero(urls[0]); if(done===files.length) buildPvThumbs(urls); }; r.readAsDataURL(f); });
            });

            // AJAX delete (parser toleran)
            serverWrap?.addEventListener("click", async (ev)=>{
              const btn=ev.target.closest(".np-del"); if(!btn) return;
              const imgId=btn.dataset.img, camId=btn.dataset.cam;
              if(!imgId||!camId) return;
              btn.disabled=true;
              try{
                const fd=new FormData();
                fd.append("csrf","<?= e($csrf) ?>");
                fd.append("action","delete_image");
                fd.append("image_id",imgId);
                fd.append("camera_id",camId);
                fd.append("ajax","1");

                const res=await fetch(location.href,{method:"POST",body:fd});

                let ok=false;
                if(res.ok){
                  const ct=(res.headers.get("content-type")||"").toLowerCase();
                  if(ct.includes("application/json")){
                    const txt=await res.text();
                    const trimmed=txt.trim();
                    if(trimmed){
                      try{ const js=JSON.parse(trimmed); ok=!!(js&&js.ok); }
                      catch(_){ ok=true; }
                    } else { ok=true; }
                  } else { ok=true; }
                }

                if(ok){
                  const thumb=btn.closest(".np-thumb"); if(thumb) thumb.remove();
                  if(!(input?.files && input.files.length)){
                    const cur=getServerUrlsFromDom(); setHero(cur[0]||null); buildPvThumbs(cur);
                  }
                }else{
                  btn.disabled=false; alert("Gagal menghapus gambar.");
                }
              } catch(e){
                const thumb=btn.closest(".np-thumb"); if(thumb) thumb.remove();
                if(!(input?.files && input.files.length)){
                  const cur=getServerUrlsFromDom(); setHero(cur[0]||null); buildPvThumbs(cur);
                }
              }
            });
          })();
          </script>
        </div>
      </form>
    </div>
  </div>
  <?php
  $modalBuffer .= ob_get_clean();
endforeach;
?>
  </tbody>
</table>
<?php
$totalPages = (int)ceil($total / $per);
if ($totalPages > 1):
  $prev = ($pageNo > 1) ? build_qs(['p'=>$pageNo-1]) : null;
  $next = ($pageNo < $totalPages) ? build_qs(['p'=>$pageNo+1]) : null;
?>
  <div class="d-flex justify-content-between align-items-center mt-2">
    <div class="small text-muted">
      Page <?= (int)$pageNo ?> / <?= (int)$totalPages ?> • Showing <?= (int)min($per, $total - $offset) ?> of <?= (int)$total ?> items
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm <?= $prev?'':'disabled' ?>" href="<?= e($prev ?? '#') ?>">« Prev</a>
      <a class="btn btn-outline-secondary btn-sm <?= $next?'':'disabled' ?>" href="<?= e($next ?? '#') ?>">Next »</a>
    </div>
  </div>
<?php endif; ?>
</div>

<!-- Edit Modals (hasil buffer) -->
<?= $modalBuffer ?? '' ?>

<!-- Create Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <form method="post" class="modal-content" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="create_camera">
      <input type="hidden" name="daily_price" id="np_daily_price_real">
      <div class="modal-header"><h5 class="modal-title">New Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <style>
          #createProductModal .np-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:24px;}
          #createProductModal .np-card{background:#fff;border:1px solid #e9edf3;border-radius:14px;padding:16px;}
          #createProductModal .np-title{font-weight:700;margin-bottom:12px;}
          #createProductModal .np-photos{display:flex;gap:12px;flex-wrap:wrap}
          #createProductModal .np-thumbs{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
          #createProductModal .np-thumb{width:72px;height:72px;border:1px solid #e9edf3;border-radius:10px;background:#e9edf3;background-size:cover;background-position:center}
          #createProductModal .np-img{width:100%;aspect-ratio:1/1;background:#e9edf3;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:22px}
          #createProductModal .np-note{font-size:12px;color:#6b7280}
        </style>

        <div class="np-grid">
          <div class="np-card">
            <div class="np-title">Product Information</div>

            <div class="mb-3">
              <div class="form-label">Product Photos</div>
              <div class="np-photos">
                <label class="np-tile"><span>Add photos (<span id="np_count">0</span>/<?= MAX_IMAGES_PER_PRODUCT ?>)</span><input type="file" name="images[]" accept="image/*" id="np_images" multiple></label>
              </div>
              <!-- PREVIEW DI BAWAH INPUT -->
              <div class="np-thumbs" id="np_thumbs"></div>
              <div class="small text-muted mt-1">*max <?= MAX_IMAGES_PER_PRODUCT ?> photos • images will be center-cropped to 1:1</div>
            </div>

            <div class="mb-3"><label class="form-label">Product Name</label><input class="form-control" name="name" id="np_name" required></div>

            <div class="mb-3"><label class="form-label">Product Price</label><input class="form-control" id="np_daily_price_view" placeholder="Rp 500.000" inputmode="numeric" autocomplete="off" required><div class="np-note">Per day. Saved as a number.</div></div>

            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Product Category</label><select class="form-select" name="type" id="np_category"><option value="">Category</option><option>Analog</option><option>Digicam</option><option>DLSR</option><option>Mirrorless</option></select></div>
              <div class="col-md-6"><label class="form-label">Brand</label><input class="form-control" name="brand" id="np_brand" required></div>
              <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status" id="np_status"><option value="available">Available</option><option value="unavailable">Unavailable</option><option value="maintenance">Maintenance</option></select></div>
            </div>

            <div class="mt-3"><label class="form-label">Product Description</label><textarea class="form-control" rows="5" name="problem" id="np_desc" placeholder="Add product description"></textarea></div>

            <div class="np-actions-bar" style="background:#284466;padding:16px;border-radius:0 0 14px 14px;display:flex;justify-content:flex-end;gap:10px;">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Back</button>
              <button class="btn btn-primary" type="submit" id="np_submit">Save &amp; Display</button>
            </div>
          </div>

          <!-- PREVIEW KANAN -->
          <aside class="np-preview pv-card" id="np_preview">
            <div class="pv-title">Preview</div>
            <div class="pv-hero"><div class="pv-ph" id="np_pv_ph">
              <svg viewBox="0 0 24 24" width="28" height="28"><path d="M9.5 4h5l1 2H19a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V9a3 3 0 0 1 3-3h3.5l1-2Zm2.5 4a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 2.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Z"/></svg>
            </div><img id="np_pv_img" alt="" style="display:none;"></div>
            <div class="pv-thumbs" id="np_pv_thumbs"></div>
            <div class="pv-name" id="np_pv_name">—</div>
            <div class="pv-sub" id="np_pv_sub">Category</div>
            <div class="pv-price"><span class="rp" id="np_pv_price">Rp 0</span> <span class="text-muted">/hari</span></div>
            <ul class="pv-list" id="np_pv_list"></ul>
            <div class="pv-cta"><button type="button" class="btn btn-primary btn-sm" disabled>Add to cart</button></div>
            <div class="pv-note">*just for reference only, when on the web it will be a little different</div>
          </aside>
        </div>

        <script>
        (function(){
          const modal=document.getElementById('createProductModal'); if(!modal) return;
          const $=s=>modal.querySelector(s);

          const input=$('#np_images'); const cnt=$('#np_count');
          const tilesWrap=$('#np_thumbs'); // preview bawah input
          const pvThumbs=document.getElementById('np_pv_thumbs');
          const hero=document.getElementById('np_pv_img'); const ph=document.getElementById('np_pv_ph');

          function setHero(src){ if(src){ hero.src=src; hero.style.display='block'; ph.style.display='none'; } else { hero.style.display='none'; ph.style.display='flex'; } }
          function buildPvThumbs(urls){ pvThumbs.innerHTML=''; urls.slice(0,4).forEach((u,i)=>{ const d=document.createElement('div'); d.className='pv-thumb'; d.style.backgroundImage=`url(${u})`; d.title='Photo '+(i+1); d.addEventListener('click',()=>setHero(u)); pvThumbs.appendChild(d); }); }
          function buildTiles(urls){ tilesWrap.innerHTML=''; urls.forEach(u=>{ const t=document.createElement('div'); t.className='np-thumb'; t.style.backgroundImage=`url(${u})`; tilesWrap.appendChild(t); }); }

          input?.addEventListener('change', ()=>{
            const files=Array.from(input.files||[]).slice(0, <?= MAX_IMAGES_PER_PRODUCT ?>);
            if(cnt) cnt.textContent=String(files.length);
            if(!files.length){ setHero(null); buildPvThumbs([]); buildTiles([]); return; }
            const urls=[]; let done=0;
            files.forEach((f,idx)=>{ const r=new FileReader(); r.onload=e=>{ urls[idx]=e.target.result; done++; if(done===1) setHero(urls[0]); if(done===files.length){ buildPvThumbs(urls); buildTiles(urls); } }; r.readAsDataURL(f); });
          });

          // Price mask
          const priceView=$('#np_daily_price_view'); const priceReal=document.getElementById('np_daily_price_real');
          window.__wirePriceInput(priceView, priceReal);
          priceView?.addEventListener('input', ()=>{ const p=document.getElementById('np_pv_price'); if(p) p.textContent=priceView.value || 'Rp 0'; });

          // Name/Category mirror
          const name=$('#np_name'); const pvName=document.getElementById('np_pv_name'); name?.addEventListener('input',()=>{ pvName.textContent=name.value||'—'; });
          const cat=$('#np_category'); const pvSub=document.getElementById('np_pv_sub'); cat?.addEventListener('change',()=>{ pvSub.textContent=cat.value||'Category'; });

          // Desc → UL
          const desc=$('#np_desc'); const pvList=document.getElementById('np_pv_list');
          function renderDesc(text){ const lines=(text||'').split(/\r?\n/).map(s=>s.trim()).filter(Boolean).slice(0,4);
            const fallback=['dummy text of the printing and typesetting','it over 2000 years old','variations of passages','popularised in the 1960s'];
            const items=lines.length?lines:fallback; pvList.innerHTML=items.map(t=>`<li>${t.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</li>`).join(''); }
          renderDesc(desc?desc.value:''); desc&&desc.addEventListener('input',()=>renderDesc(desc.value));

          setHero(null); buildPvThumbs([]); buildTiles([]);
        })();
        </script>
      </div>
    </form>
  </div>
</div>

<?php
// DDL saran (jalankan sekali bila belum ada):
// CREATE TABLE IF NOT EXISTS camera_images (
//   id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
//   camera_id BIGINT UNSIGNED NOT NULL,
//   filename VARCHAR(255) NOT NULL,
//   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
//   PRIMARY KEY (id),
//   KEY idx_camera (camera_id, id),
//   CONSTRAINT fk_camimg_camera FOREIGN KEY (camera_id) REFERENCES cameras(id) ON DELETE CASCADE
// ) ENGINE=InnoDB;
?>
