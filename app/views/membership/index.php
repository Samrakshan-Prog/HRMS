<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="m-0">Membership Information</h2>
    <?php if ($role !== 'employee'): ?><a href="membership.php?a=create" class="btn btn-primary">Add Membership</a><?php endif; ?>
  </div>
  <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

  <table class="table table-bordered table-striped align-middle">
    <thead class="table-primary"><tr><th>ID</th><th>Employee</th><th>Membership No</th><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Approved By</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (count($records) === 0): ?><tr><td colspan="9" class="text-center">No membership records found.</td></tr><?php endif; ?>
    <?php foreach ($records as $row): ?>
      <tr>
        <td><?= (int)$row['id'] ?></td>
        <td><?= htmlspecialchars($row['employee_code'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']) ?></td>
        <td><?= htmlspecialchars($row['membership_no']) ?></td>
        <td><?= htmlspecialchars($row['membership_type']) ?></td>
        <td><?= htmlspecialchars($row['start_date']) ?></td>
        <td><?= htmlspecialchars((string)$row['end_date']) ?></td>
        <td><span class="badge bg-<?= $row['status'] === 'active' ? 'success' : ($row['status'] === 'expired' ? 'secondary' : 'danger') ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
        <td><?= htmlspecialchars((string)$row['approver_name']) ?></td>
        <td>
          <?php if ($role !== 'employee'): ?>
            <form method="post" action="membership.php?a=delete" class="d-inline">
              <?= csrf_input() ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this membership record?')">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
