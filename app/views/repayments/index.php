<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="m-0">Loan Repayment Tracking</h2>
    <?php if ($role !== 'employee'): ?>
      <a href="repayments.php?a=create" class="btn btn-primary">Add Repayment</a>
    <?php endif; ?>
  </div>

  <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-primary">
      <tr>
        <th>ID</th><th>Loan ID</th><th>Employee</th><th>Due</th><th>Paid</th><th>Amount Due</th><th>Amount Paid</th><th>Status</th><th>Mode</th><th>Actions</th>
      </tr>
      </thead>
      <tbody>
      <?php if (count($records) === 0): ?><tr><td colspan="10" class="text-center">No repayment records found.</td></tr><?php endif; ?>
      <?php foreach ($records as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td><?= (int)$row['loan_id'] ?></td>
          <td><?= htmlspecialchars($row['employee_code'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><?= htmlspecialchars($row['due_date']) ?></td>
          <td><?= htmlspecialchars((string)$row['paid_date']) ?></td>
          <td><?= number_format((float)$row['amount_due'], 2) ?></td>
          <td><?= number_format((float)$row['amount_paid'], 2) ?></td>
          <td><span class="badge bg-<?= $row['payment_status'] === 'paid' ? 'success' : ($row['payment_status'] === 'overdue' ? 'danger' : ($row['payment_status'] === 'partial' ? 'info' : 'warning')) ?>"><?= htmlspecialchars(ucfirst($row['payment_status'])) ?></span></td>
          <td><?= htmlspecialchars((string)$row['payment_mode']) ?></td>
          <td>
            <a href="repayments.php?a=edit&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <?php if ($role !== 'employee'): ?>
              <form method="post" action="repayments.php?a=delete" class="d-inline">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this repayment?')">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
