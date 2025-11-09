<?php
// users.php — Srent • User Management (DB-ready; UI/Design tetap)

/* -------------------------------------------------------------
 * Helper aman
 * ----------------------------------------------------------- */
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* -------------------------------------------------------------
 * Bootstrap user (fallback demo)
 * ----------------------------------------------------------- */
if (!isset($user)) {
  $user = ['id'=>1,'username'=>'owner1','role'=>'OWNER','email'=>'owner@srent.com'];
}

/* -------------------------------------------------------------
 * DB Handlers: Create / Edit / Delete (struktur baru)
 * - accounts.id (PK, AUTO_INCREMENT)
 * - owners.owner_id → accounts.id (CASCADE)
 * - staffs.staff_id → accounts.id (CASCADE)
 * - customers.customer_id → accounts.id (CASCADE, address NOT NULL → "" dari UI)
 * - accounts punya owner_id/staff_id/customer_id (FK balik: SET NULL)
 * ----------------------------------------------------------- */
$notice = null;
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
      $action = $_POST['action'] ?? '';

      $currentRole   = strtoupper($user['role'] ?? 'CUSTOMER');
      $currentUserId = (int)($user['id'] ?? 0);

      /* -------------------- CREATE -------------------- */
      if ($action === 'create_user' && $currentRole === 'OWNER') {
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = strtoupper(trim($_POST['role'] ?? 'CUSTOMER'));
        $passRaw  = (string)($_POST['password'] ?? '');

        if ($name === '' || $username === '' || $email === '' || $passRaw === '' || !in_array($role,['STAFF','CUSTOMER'],true)) {
          throw new RuntimeException('Invalid form payload.');
        }

        $password = (strlen($passRaw) >= 60 && preg_match('~^\$2y\$~',$passRaw)) ? $passRaw : password_hash($passRaw, PASSWORD_BCRYPT);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO accounts (username,email,phone,password,role,is_verified,is_active,created_at) VALUES (?,?,?,?,?,1,1,NOW())");
        $stmt->execute([$username,$email,$phone,$password,$role]);
        $newId = (int)$pdo->lastInsertId();

        if ($role === 'STAFF') {
          $pdo->prepare("INSERT INTO staffs (staff_id, full_name) VALUES (?,?)")->execute([$newId, $name]);
          $pdo->prepare("UPDATE accounts SET staff_id=? , owner_id=NULL, customer_id=NULL WHERE id=?")->execute([$newId,$newId]);
        } else { // CUSTOMER
          $pdo->prepare("INSERT INTO customers (customer_id, full_name, address) VALUES (?,?,?)")->execute([$newId, $name, ""]);
          $pdo->prepare("UPDATE accounts SET customer_id=? , owner_id=NULL, staff_id=NULL WHERE id=?")->execute([$newId,$newId]);
        }

        $pdo->commit();
        $notice = 'User created.';
      }

      /* -------------------- EDIT -------------------- */
      if ($action === 'edit_user') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $roleNew  = strtoupper(trim($_POST['role'] ?? ''));
        $passRaw  = (string)($_POST['password'] ?? '');

        if ($id <= 0 || $name === '' || $username === '' || $email === '' || !in_array($roleNew,['OWNER','STAFF','CUSTOMER'],true)) {
          throw new RuntimeException('Invalid form payload.');
        }

        $rowAcc = $pdo->prepare("SELECT id, role FROM accounts WHERE id=?");
        $rowAcc->execute([$id]);
        $acc = $rowAcc->fetch(PDO::FETCH_ASSOC);
        if (!$acc) throw new RuntimeException('User not found.');

        $targetOldRole = strtoupper($acc['role']);

        // STAFF boleh edit CUSTOMER saja, OWNER boleh edit semua
        if ($currentRole === 'STAFF' && $targetOldRole !== 'CUSTOMER') {
          throw new RuntimeException('Forbidden.');
        }

        $pdo->beginTransaction();

        if ($passRaw !== '') {
          $password = (strlen($passRaw) >= 60 && preg_match('~^\$2y\$~',$passRaw)) ? $passRaw : password_hash($passRaw, PASSWORD_BCRYPT);
          $stmt = $pdo->prepare("UPDATE accounts SET username=?, email=?, phone=?, password=?, role=? WHERE id=?");
          $stmt->execute([$username,$email,$phone,$password,$roleNew,$id]);
        } else {
          $stmt = $pdo->prepare("UPDATE accounts SET username=?, email=?, phone=?, role=? WHERE id=?");
          $stmt->execute([$username,$email,$phone,$roleNew,$id]);
        }

        if ($roleNew === 'OWNER') {
          $pdo->prepare("DELETE FROM staffs WHERE staff_id=?")->execute([$id]);
          $pdo->prepare("DELETE FROM customers WHERE customer_id=?")->execute([$id]);

          $exists = $pdo->prepare("SELECT 1 FROM owners WHERE owner_id=?");
          $exists->execute([$id]);
          if ($exists->fetchColumn()) {
            $pdo->prepare("UPDATE owners SET full_name=? WHERE owner_id=?")->execute([$name,$id]);
          } else {
            $pdo->prepare("INSERT INTO owners (owner_id, full_name, address) VALUES (?,?,?)")->execute([$id,$name,'']);
          }
          $pdo->prepare("UPDATE accounts SET owner_id=?, staff_id=NULL, customer_id=NULL WHERE id=?")->execute([$id,$id]);

        } elseif ($roleNew === 'STAFF') {
          $pdo->prepare("DELETE FROM owners WHERE owner_id=?")->execute([$id]);
          $pdo->prepare("DELETE FROM customers WHERE customer_id=?")->execute([$id]);

          $exists = $pdo->prepare("SELECT 1 FROM staffs WHERE staff_id=?");
          $exists->execute([$id]);
          if ($exists->fetchColumn()) {
            $pdo->prepare("UPDATE staffs SET full_name=? WHERE staff_id=?")->execute([$name,$id]);
          } else {
            $pdo->prepare("INSERT INTO staffs (staff_id, full_name) VALUES (?,?)")->execute([$id,$name]);
          }
          $pdo->prepare("UPDATE accounts SET staff_id=?, owner_id=NULL, customer_id=NULL WHERE id=?")->execute([$id,$id]);

        } else { // CUSTOMER
          $pdo->prepare("DELETE FROM owners WHERE owner_id=?")->execute([$id]);
          $pdo->prepare("DELETE FROM staffs WHERE staff_id=?")->execute([$id]);

          $exists = $pdo->prepare("SELECT 1 FROM customers WHERE customer_id=?");
          $exists->execute([$id]);
          if ($exists->fetchColumn()) {
            $pdo->prepare("UPDATE customers SET full_name=? WHERE customer_id=?")->execute([$name,$id]);
          } else {
            $pdo->prepare("INSERT INTO customers (customer_id, full_name, address) VALUES (?,?,?)")->execute([$id,$name,'']);
          }
          $pdo->prepare("UPDATE accounts SET customer_id=?, owner_id=NULL, staff_id=NULL WHERE id=?")->execute([$id,$id]);
        }

        $pdo->commit();
        $notice = 'User updated.';
      }

      /* -------------------- DELETE -------------------- */
      if ($action === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Invalid ID.');
        if ($id === $currentUserId) throw new RuntimeException('Cannot delete yourself.');

        $stmt = $pdo->prepare("SELECT role FROM accounts WHERE id=?");
        $stmt->execute([$id]);
        $roleTarget = strtoupper((string)$stmt->fetchColumn());
        if ($roleTarget === '') throw new RuntimeException('User not found.');

        $allowed = false;
        if ($currentRole === 'OWNER' && in_array($roleTarget,['STAFF','CUSTOMER'],true)) $allowed = true;
        if ($currentRole === 'STAFF' && $roleTarget === 'CUSTOMER') $allowed = true;
        if (!$allowed) throw new RuntimeException('Forbidden.');

        $pdo->prepare("DELETE FROM accounts WHERE id=?")->execute([$id]); // role rows ikut terhapus via CASCADE
        $notice = 'User deleted.';
      }
    }

    /* -------------------------------------------------------------
     * SELECT daftar user: tampilkan ID per kelas (O/S/C + id)
     * ----------------------------------------------------------- */
    $sql = "
      SELECT
        a.id,
        a.username, a.email, a.phone,
        a.role, a.created_at,
        COALESCE(o.full_name, s.full_name, c.full_name) AS name,
        CASE
          WHEN a.role='OWNER'    THEN CONCAT('O', a.id)
          WHEN a.role='STAFF'    THEN CONCAT('S', a.id)
          WHEN a.role='CUSTOMER' THEN CONCAT('C', a.id)
          ELSE CONCAT('X', a.id)
        END AS id_class
      FROM accounts a
      LEFT JOIN owners    o ON o.owner_id    = a.id
      LEFT JOIN staffs    s ON s.staff_id    = a.id
      LEFT JOIN customers c ON c.customer_id = a.id
      ORDER BY FIELD(a.role,'OWNER','STAFF','CUSTOMER'), a.id
    ";
    $users_list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  } catch (Throwable $ex) {
    // Jika terjadi error, biarkan UI jalan dengan demo data di bawah
    // $notice = 'Error: '.$ex->getMessage();
  }
}

/* -------------------------------------------------------------
 * DEMO DATA (fallback jika query gagal / tidak ada data)
 * ----------------------------------------------------------- */
if (!isset($users_list) || !is_array($users_list) || count($users_list) === 0) {
  $users_list = [
    ['id'=>101,'username'=>'sdesi','name'=>'Desi Wiliyanti S','email'=>'Desiwiliyantis@gmail.com','phone'=>'08888888888','role'=>'OWNER','created_at'=>'2025-10-23 22:10:58','id_class'=>'O101'],
    ['id'=>102,'username'=>'staff01','name'=>'Andra','email'=>'andra@srent.com','phone'=>'08881234567','role'=>'STAFF','created_at'=>'2025-10-23 22:10:58','id_class'=>'S102'],
    ['id'=>103,'username'=>'yanti','name'=>'Wiliyanti S','email'=>'wiliyantis@gmail.com','phone'=>'08881111222','role'=>'CUSTOMER','created_at'=>'2025-10-23 22:10:58','id_class'=>'C103'],
    ['id'=>104,'username'=>'lama','name'=>'Alama S','email'=>'alamas@gmail.com','phone'=>'08883334444','role'=>'CUSTOMER','created_at'=>'2025-10-23 22:10:58','id_class'=>'C104'],
  ];
}
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>

<style>
/* ==== Srent Users — isolated styles ==== */
:root{
  --sr-bg:#f5f6f8; --sr-white:#fff; --sr-text:#2b2f3a; --sr-muted:#7b8394;
  --sr-navy:#2f466c; --sr-navy-50:#e8eef7; --sr-accent:#5b8db8;
  --sr-green:#27ae60; --sr-rose:#c97c7c; --sr-shadow:0 2px 8px rgba(27,43,66,.08);
}
body{font-family:"Poppins",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}

/* Header kecil */
.srui-header{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin:18px 0 0}
.srui-header h4{margin:0;font-weight:700}
.srui-header p{margin:6px 0 0;color:var(--sr-muted)}

/* Actions */
.srui-actions{display:flex;align-items:center;gap:12px}
.srui-btn{
  border:0;border-radius:10px;padding:10px 14px;
  display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:600
}
.srui-btn--primary{background:var(--sr-accent);color:#fff}
.srui-btn--primary:hover{filter:brightness(.96)}
.srui-dropdown{position:relative}
.srui-dropdown > .srui-btn{background:var(--sr-accent);color:#fff}
.srui-dropdown .srui-menu{
  position:absolute;top:calc(100% + 6px);right:0;background:#fff;border-radius:10px;
  min-width:180px;box-shadow:var(--sr-shadow);display:none;overflow:hidden;z-index:10
}
.srui-dropdown.open .srui-menu{display:block}
.srui-menu a{display:block;padding:10px 14px;color:var(--sr-text);text-decoration:none;font-size:14px}
.srui-menu a:hover{background:#f6f8fb}

/* Card + Table (dengan jarak dari header actions) */
.srui-card{
  background:#fff;border-radius:12px;box-shadow:var(--sr-shadow);padding:18px;
  margin-top:16px;
}
.srui-table{width:100%;border-collapse:separate;border-spacing:0}
.srui-table thead th{padding:14px;border-bottom:2px solid #eef1f6;text-align:left;font-weight:600;color:#3a4253;font-size:14px}
.srui-table tbody td{padding:14px;border-bottom:1px solid #f1f3f7;font-size:14px}
.srui-table tbody tr:hover{background:#fbfcfe}

/* Badge role — lebih besar */
.badge-owner,.badge-staff,.badge-customer,.badge-admin{
  font-size:14px !important;font-weight:700 !important;
  padding:8px 16px !important;letter-spacing:.2px;border-radius:8px;color:#fff
}
.badge-owner{ background:var(--sr-rose)!important }
.badge-staff,.badge-admin{ background:var(--sr-navy)!important }
.badge-customer{ background:var(--sr-green)!important }

/* Created at: jam miring */
.srui-created .time{font-style:italic;color:#666}

/* Action cell rapi */
.srui-actions-cell{display:flex;gap:10px;justify-content:flex-end;align-items:center}
.srui-iconbtn{
  width:36px;height:36px;border-radius:8px;border:1px solid #e3e7ee;
  background:#fff;display:grid;place-items:center;cursor:pointer
}
.srui-iconbtn:hover{background:#f6f8fb}
.srui-icon{width:16px;height:16px;display:block}

/* Modal modern */
.modal-modern .modal-header{
  background:linear-gradient(135deg,var(--sr-navy) 0%, var(--sr-accent) 100%);
  color:#fff;border-top-left-radius:14px;border-top-right-radius:14px
}
.modal-modern .modal-content{border-radius:14px;box-shadow:var(--sr-shadow)}
.modal-modern .modal-title{font-weight:700}
.modal-modern .form-label{font-weight:600}
.modal-modern .form-control,.modal-modern .form-select{border-radius:10px;padding:10px 12px}
.modal-modern .hint{color:var(--sr-muted);font-size:12px;margin-top:6px}
.modal-modern .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width: 768px){ .modal-modern .grid{grid-template-columns:1fr} }

/* Confirm modal style */
.confirm-icon{
  width:44px;height:44px;border-radius:50%;display:grid;place-items:center;
  background:#fff1f1;color:#e03b3b;margin-right:10px;
  border:1px solid #ffdede
}
</style>

<div class="srui-header">
  <div>
    <h4 class="mb-1">User</h4>
    <p class="text-muted small">Manage user account</p>
  </div>

  <div class="srui-actions">
    <!-- Role Filter -->
    <div class="srui-dropdown" id="srRoleDropdown">
      <button class="srui-btn srui-btn--primary" id="srRoleBtn" type="button">Role ▾</button>
      <div class="srui-menu">
        <a href="#" data-role="All">All</a>
        <a href="#" data-role="OWNER">Owner</a>
        <a href="#" data-role="STAFF">Staff</a>
        <a href="#" data-role="CUSTOMER">Customer</a>
      </div>
    </div>

    <?php if (($user['role'] ?? '') === 'OWNER'): ?>
      <button class="srui-btn srui-btn--primary" type="button"
              data-bs-toggle="modal" data-bs-target="#createStaffModal" id="btnOpenCreate">
        <i class="fas fa-user-plus"></i> New User
      </button>
    <?php endif; ?>
  </div>
</div>

<div class="srui-card">
  <div class="table-responsive">
    <table class="srui-table" id="srUsersTable">
      <thead>
        <tr>
          <th style="width:90px;">ID</th>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>No Handphone</th>
          <th style="width:160px;">Role</th>
          <th style="width:210px;">Created At</th>
          <?php if (in_array(($user['role'] ?? ''), ['OWNER','STAFF'])): ?>
            <th style="width:120px;text-align:right" class="text-end">Action</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users_list as $u):
          $fullName = $u['name'] ?? $u['full_name'] ?? $u['nama'] ?? $u['display_name'] ?? '-';
          $phone    = $u['phone'] ?? $u['no_hp'] ?? $u['handphone'] ?? $u['tel'] ?? '-';

          $roleUpper = strtoupper((string)$u['role']);
          $roleBadgeClass = 'badge-' . strtolower($roleUpper);

          $created = (string)($u['created_at'] ?? '');
          if (strpos($created,' ')!==false) { [$dtDate,$dtTime] = explode(' ', $created, 2); }
          else { $dtDate = $created; $dtTime=''; }

          // ID per kelas/role
          $idClass = $u['id_class'] ?? (
            ($roleUpper==='OWNER' ? 'O' : ($roleUpper==='STAFF' ? 'S' : ($roleUpper==='CUSTOMER' ? 'C' : 'X')))
            . (int)($u['id'] ?? 0)
          );

          // Otorisasi hapus
          $currentRole = $user['role'] ?? '';
          $notSelf = ($u['id'] ?? null) !== ($user['id'] ?? null);
          $targetRole = $roleUpper;
          $canDelete =
            $notSelf && (
              ($currentRole === 'OWNER' && in_array($targetRole, ['STAFF','CUSTOMER'])) ||
              ($currentRole === 'STAFF' && $targetRole === 'CUSTOMER')
            );
        ?>
        <tr data-role="<?= e($roleUpper) ?>">
          <td title="Numeric ID: <?= (int)$u['id'] ?>"><?= e($idClass) ?></td>
          <td><?= e($fullName) ?></td>
          <td>
            <?= e($u['username']) ?>
            <?php if (($u['id'] ?? null) === ($user['id'] ?? null)): ?>
              <span class="badge bg-info text-white ms-1">You</span>
            <?php endif; ?>
          </td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($phone) ?></td>
          <td><span class="badge <?= e($roleBadgeClass) ?> text-uppercase"><?= e($roleUpper) ?></span></td>
          <td class="srui-created">
            <?= e($dtDate) ?> <?php if($dtTime): ?><span class="time"><?= e($dtTime) ?></span><?php endif; ?>
          </td>

          <?php if (in_array(($user['role'] ?? ''), ['OWNER','STAFF'])): ?>
          <td class="srui-actions-cell">
            <!-- Edit -->
            <button type="button" class="srui-iconbtn btn-edit"
                    title="Edit"
                    data-bs-toggle="modal"
                    data-bs-target="#editUserModal"
                    data-id="<?= (int)$u['id'] ?>"
                    data-username="<?= e($u['username']) ?>"
                    data-name="<?= e($fullName) ?>"
                    data-email="<?= e($u['email']) ?>"
                    data-phone="<?= e($phone) ?>"
                    data-role="<?= e($roleUpper) ?>">
              <svg class="srui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
              </svg>
            </button>

            <!-- Delete -->
            <?php if ($canDelete): ?>
              <button type="button" class="srui-iconbtn btn-delete"
                      title="Delete"
                      data-bs-toggle="modal"
                      data-bs-target="#confirmDeleteModal"
                      data-id="<?= (int)$u['id'] ?>"
                      data-name="<?= e($fullName) ?>"
                      data-role="<?= e($roleUpper) ?>">
                <svg class="srui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  <path d="M10 11v6M14 11v6"/>
                  <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                </svg>
              </button>
            <?php else: ?>
              <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ========================= MODALS ========================= -->

<?php if (($user['role'] ?? '') === 'OWNER'): ?>
<!-- New User -->
<div class="modal fade modal-modern" id="createStaffModal" tabindex="-1" aria-labelledby="createStaffLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="">
      <div class="modal-header">
        <h5 class="modal-title" id="createStaffLabel">Create New User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if (isset($csrf)): ?><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><?php endif; ?>
        <input type="hidden" name="action" value="create_user">
        <div class="grid">
          <div>
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" required />
          </div>
          <div>
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required />
          </div>
          <div>
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required />
            <div class="hint">Gunakan email aktif untuk reset password.</div>
          </div>
          <div>
            <label class="form-label">No Handphone</label>
            <input type="text" class="form-control" name="phone" />
          </div>
          <div>
            <label class="form-label">Role</label>
            <select class="form-select" name="role" required>
              <option value="STAFF">Staff</option>
              <option value="CUSTOMER">Customer</option>
            </select>
          </div>
          <div>
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required />
            <div class="hint">Minimal 8 karakter disarankan.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Edit User -->
<div class="modal fade modal-modern" id="editUserModal" tabindex="-1" aria-labelledby="editUserLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserLabel">Edit User</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if (isset($csrf)): ?><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><?php endif; ?>
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="id" id="edit-id">
        <div class="grid">
          <div>
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" id="edit-name" required />
          </div>
          <div>
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="edit-username" required />
          </div>
          <div>
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="edit-email" required />
          </div>
          <div>
            <label class="form-label">No Handphone</label>
            <input type="text" class="form-control" name="phone" id="edit-phone" />
          </div>
          <div>
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="edit-role" required>
              <option value="OWNER">Owner</option>
              <option value="STAFF">Staff</option>
              <option value="CUSTOMER">Customer</option>
            </select>
          </div>
          <div>
            <label class="form-label">Password (optional)</label>
            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm Delete -->
<div class="modal fade modal-modern" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteLabel">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex align-items-start">
        <div class="confirm-icon">🗑️</div>
        <div>
          <p class="mb-1">Anda yakin ingin menghapus user berikut?</p>
          <div><strong id="del-name">-</strong> <span class="text-muted">(<span id="del-role">-</span>)</span></div>
          <div class="hint mt-2">Aksi ini tidak dapat dibatalkan.</div>
        </div>
        <?php if (isset($csrf)): ?><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><?php endif; ?>
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="id" id="del-id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
// Dropdown Role
(function(){
  const dd = document.getElementById('srRoleDropdown');
  if(!dd) return;
  const btn = document.getElementById('srRoleBtn');
  btn.addEventListener('click', function(e){ e.stopPropagation(); dd.classList.toggle('open'); });
  document.addEventListener('click', ()=> dd.classList.remove('open'));

  dd.querySelectorAll('.srui-menu a').forEach(a=>{
    a.addEventListener('click', function(e){
      e.preventDefault();
      const role = this.dataset.role;
      const rows = document.querySelectorAll('#srUsersTable tbody tr');
      rows.forEach(tr=>{
        const r = tr.getAttribute('data-role');
        tr.style.display = (role==='All' || role===r) ? '' : 'none';
      });
      btn.textContent = 'Role ▾ ('+role+')';
    });
  });
})();

// Isi otomatis modal Edit
(function(){
  const editButtons = document.querySelectorAll('.btn-edit');
  const idField = document.getElementById('edit-id');
  const nameField = document.getElementById('edit-name');
  const userField = document.getElementById('edit-username');
  const emailField = document.getElementById('edit-email');
  const phoneField = document.getElementById('edit-phone');
  const roleField = document.getElementById('edit-role');

  editButtons.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      idField.value = btn.dataset.id || '';
      nameField.value = btn.dataset.name || '';
      userField.value = btn.dataset.username || '';
      emailField.value = btn.dataset.email || '';
      phoneField.value = btn.dataset.phone || '';
      roleField.value = btn.dataset.role || 'CUSTOMER';
    });
  });
})();

// Isi otomatis Confirm Delete
(function(){
  const delBtns = document.querySelectorAll('.btn-delete');
  const idField = document.getElementById('del-id');
  const nameEl = document.getElementById('del-name');
  const roleEl = document.getElementById('del-role');

  delBtns.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const uid  = btn.dataset.id || '';
      const name = btn.dataset.name || '';
      const role = btn.dataset.role || '';
      idField.value = uid;
      nameEl.textContent = name;
      roleEl.textContent = role;
    });
  });
})();

// Fallback modal open jika Bootstrap JS tidak tersedia
(function(){
  function ensureBootstrapShown(id){
    const el = document.getElementById(id);
    if (!el) return;
    const hasBs = !!window.bootstrap;
    if (!hasBs){
      el.classList.add('show');
      el.style.display = 'block';
      el.removeAttribute('aria-hidden');  
      el.setAttribute('aria-modal','true');
      el.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn=>{
        btn.addEventListener('click', ()=> {
          el.classList.remove('show');
          el.style.display = 'none';
          el.setAttribute('aria-hidden','true');
          el.removeAttribute('aria-modal');
        });
      });
    }
  }

  const newBtn = document.getElementById('btnOpenCreate');
  if (newBtn){ newBtn.addEventListener('click', ()=> ensureBootstrapShown('createStaffModal')); }
  document.querySelectorAll('[data-bs-target="#editUserModal"]').forEach(b=>{
    b.addEventListener('click', ()=> ensureBootstrapShown('editUserModal'));
  });
  document.querySelectorAll('[data-bs-target="#confirmDeleteModal"]').forEach(b=>{
    b.addEventListener('click', ()=> ensureBootstrapShown('confirmDeleteModal'));
  });
})();
</script>
