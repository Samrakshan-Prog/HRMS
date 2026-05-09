<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid">
  <div class="row">
    <?php include __DIR__ . '/../../includes/left.php'; ?>
    <main class="col-md-10 ms-sm-auto content-area">
      <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h2 class="m-0">Loan Management</h2>
        <a href="loans.php?a=create" class="btn btn-primary">Apply / Add Loan</a>
      </div>

      <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
      <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

      <div class="row mb-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Total Loans</small><h4><?= (int)($stats['total_loans'] ?? 0) ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Pending</small><h4><?= (int)($stats['pending_loans'] ?? 0) ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Approved</small><h4><?= (int)($stats['approved_loans'] ?? 0) ?></h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><small>Approved Amount</small><h4><?= number_format((float)($stats['approved_amount'] ?? 0), 2) ?></h4></div></div></div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-primary">
            <tr>
              <th>ID</th><th>Employee</th><th>Requested</th><th>Approved</th><th>Interest %</th><th>Tenure</th><th>EMI</th><th>Status</th><th>Request Date</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($records) === 0): ?>
              <tr><td colspan="10" class="text-center">No loan records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($records as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= htmlspecialchars($row['employee_code'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= number_format((float)$row['requested_amount'], 2) ?></td>
                <td><?= number_format((float)($row['approved_amount'] ?? 0), 2) ?></td>
                <td><?= number_format((float)$row['interest_rate'], 2) ?></td>
                <td><?= (int)$row['tenure_months'] ?> months</td>
                <td><?= number_format((float)($row['emi_amount'] ?? 0), 2) ?></td>
                <td><span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : ($row['status'] === 'closed' ? 'secondary' : 'warning')) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
                <td><?= htmlspecialchars($row['request_date']) ?></td>
                <td>
                  <a href="loans.php?a=edit&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                  <?php if ($role !== 'employee'): ?>
                    <form method="post" action="loans.php?a=delete" class="d-inline">
                      <?= csrf_input() ?>
                      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this loan?')">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
