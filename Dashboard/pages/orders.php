<?php
// ======================================================================
// rentals/orders page — SnapRent Admin
// - Handle EDIT & CANCEL di file yang sama (POST)
// - Modal Edit Bootstrap
// - Payment disederhanakan → hanya tampil "Paid"
// ======================================================================


// ========== HANDLE POST (EDIT / CANCEL) DI FILE INI ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['rental_id'])) {

    $action    = $_POST['action'];
    $rental_id = (int)$_POST['rental_id'];

    if ($action === 'edit') {

        $start_raw = $_POST['start_date'] ?? null;
        $end_raw   = $_POST['end_date'] ?? null;
        $status    = $_POST['status'] ?? 'pending';
        $total     = $_POST['total_price'] ?? 0;

        // Convert datetime-local → MySQL
        $start = $start_raw ? str_replace('T', ' ', $start_raw) . ':00' : null;
        $end   = $end_raw   ? str_replace('T', ' ', $end_raw)   . ':00' : null;

        $stmt = $pdo->prepare("
            UPDATE rentals
            SET start_date = :start_date,
                end_date = :end_date,
                status = :status,
                total_price = :total_price
            WHERE id = :id
        ");
        $stmt->execute([
            ':start_date'  => $start,
            ':end_date'    => $end,
            ':status'      => $status,
            ':total_price' => $total,
            ':id'          => $rental_id,
        ]);

    } elseif ($action === 'cancel') {

        $stmt = $pdo->prepare("
            UPDATE rentals
            SET status = 'cancelled'
            WHERE id = :id
        ");
        $stmt->execute([':id' => $rental_id]);
    }

    // Mencegah resubmit F5
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}



// ========== LOAD DATA RENTALS ==========
$orders = $pdo->query("
  SELECT
    rn.id,
    rn.created_at,
    rn.status,
    rn.start_date,
    rn.end_date,
    rn.total_price,
    acc.username AS customer,
    cam.name AS camera,
    COALESCE(v.paid_amount, 0) AS paid_amount,
    v.last_payment_status
  FROM rentals rn
  JOIN customers c
    ON c.customer_id = rn.customer_id
  JOIN accounts acc
    ON acc.id = c.customer_id
  JOIN cameras cam
    ON cam.id = rn.camera_id
  LEFT JOIN v_rental_payment_status v
    ON v.rental_id = rn.id
  ORDER BY rn.created_at DESC
  LIMIT 200
")->fetchAll();
?>



<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Rentals</h4>
    <p class="text-muted small">Manage all camera rental orders</p>
  </div>
  <button class="btn btn-outline-modern" onclick="window.print()">
    <i class="fas fa-download"></i> Export/Print
  </button>
</div>



<div class="page-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Invoice</th>
          <th>Customer</th>
          <th>Camera</th>
          <th>Total</th>
          <th>Rental Status</th>
          <th>Payment</th>
          <th>Created</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php $i=1; foreach ($orders as $o):
          $inv = 'INV-' . str_pad((string)$o['id'], 6, '0', STR_PAD_LEFT);
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= e($inv) ?></td>
          <td><?= e($o['customer']) ?></td>
          <td><?= e($o['camera']) ?></td>
          <td><?= rupiah($o['total_price']) ?></td>

          <td>
            <span class="badge bg-secondary text-uppercase">
              <?= e($o['status']) ?>
            </span>
          </td>

          <!-- PAYMENT = Paid -->
          <td>
            <span class="badge bg-success">Paid</span>
          </td>

          <td><?= e($o['created_at']) ?></td>

          <td class="text-end">

            <!-- BUTTON EDIT -->
            <button class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#editRentalModal<?= $o['id'] ?>">
              <i class="fas fa-edit"></i> Edit
            </button>

            <!-- BUTTON CANCEL -->
            <form method="POST" class="d-inline">
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="rental_id" value="<?= $o['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Yakin cancel rental ini?')">
                <i class="fas fa-times"></i> Cancel
              </button>
            </form>

          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>

    </table>
  </div>
</div>




<!-- ======================= MODAL EDIT RENTAL ======================= -->
<?php foreach ($orders as $o): ?>

<?php
$startValue = !empty($o['start_date']) ? date('Y-m-d\TH:i', strtotime($o['start_date'])) : '';
$endValue   = !empty($o['end_date'])   ? date('Y-m-d\TH:i', strtotime($o['end_date']))   : '';
?>

<div class="modal fade" id="editRentalModal<?= $o['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="rental_id" value="<?= $o['id'] ?>">

        <div class="modal-header">
          <h5 class="modal-title">Edit Rental #<?= $o['id'] ?> — <?= e($o['camera']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="datetime-local" name="start_date" class="form-control"
                   value="<?= $startValue ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">End Date</label>
            <input type="datetime-local" name="end_date" class="form-control"
                   value="<?= $endValue ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <?php $statuses = ['pending','confirmed','rented','returned','cancelled']; ?>
              <?php foreach ($statuses as $st): ?>
                <option value="<?= $st ?>" <?= $o['status'] === $st ? 'selected' : '' ?>>
                  <?= ucfirst($st) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Total Price</label>
            <input type="number" name="total_price" class="form-control"
                   value="<?= $o['total_price'] ?>" step="1000">
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-modern">Save changes</button>
        </div>

      </form>

    </div>
  </div>
</div>

<?php endforeach; ?>
