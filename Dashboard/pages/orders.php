<?php
// Orders page logic
// NOTE: Disesuaikan dengan skema baru: customers.customer_id & accounts.id
$orders = $pdo->query("
  SELECT
    rn.id,
    rn.created_at,
    rn.status,
    rn.start_date,
    rn.end_date,
    rn.total_price,
    acc.username AS customer,
    cam.name     AS camera,
    COALESCE(v.paid_amount, 0) AS paid_amount,
    v.last_payment_status
  FROM rentals rn
  JOIN customers c
    ON c.customer_id = rn.customer_id           -- was: c.account_id = rn.customer_id
  JOIN accounts acc
    ON acc.id = c.customer_id                   -- was: acc.id = c.account_id
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
          $inv = 'INV-'.str_pad((string)$o['id'], 6, '0', STR_PAD_LEFT);
          $paymentStatus = $o['last_payment_status'] ?: 'pending';
          $paymentStatusClass = 'status-'.strtolower((string)$paymentStatus);
        ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= e($inv) ?></td>
            <td><?= e($o['customer']) ?></td>
            <td><?= e($o['camera']) ?></td>
            <td><?= rupiah($o['total_price']) ?></td>
            <td><span class="badge bg-secondary text-uppercase"><?= e($o['status']) ?></span></td>
            <td>
              <span class="status-badge <?= $paymentStatusClass ?>">
                <?= e($paymentStatus) ?>
              </span>
              <div class="small text-muted"><?= rupiah($o['paid_amount']) ?></div>
            </td>
            <td><?= e($o['created_at']) ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" title="View">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-outline-secondary" title="Invoice">
                <i class="fas fa-printer"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" title="Cancel">
                <i class="fas fa-times-circle"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
