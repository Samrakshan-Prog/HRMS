<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="m-0">Employee Performance</h2>
    <?php if ($role !== 'employee'): ?><a href="performance.php?a=create" class="btn btn-primary">Add Evaluation</a><?php endif; ?>
  </div>
  <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
  <table class="table table-bordered table-striped align-middle">
    <thead class="table-primary"><tr><th>ID</th><th>Employee</th><th>Month</th><th>Test</th><th>Attendance</th><th>Overall</th><th>Grade</th><th>Promotion</th><th>Reviewer</th><th>Action</th></tr></thead>
    <tbody>
      <?php if (count($records) === 0): ?><tr><td colspan="10" class="text-center">No performance records found.</td></tr><?php endif; ?>
      <?php foreach ($records as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td><?= htmlspecialchars($row['employee_code'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><?= htmlspecialchars($row['evaluation_month']) ?></td>
          <td><?= number_format((float)$row['test_score'], 2) ?></td>
          <td><?= number_format((float)$row['attendance_score'], 2) ?></td>
          <td><strong><?= number_format((float)$row['overall_score'], 2) ?></strong></td>
          <td><?= htmlspecialchars((string)$row['grade']) ?></td>
          <td>
            <span class="badge bg-<?= $row['promotion_status'] === 'approved' ? 'success' : ($row['promotion_status'] === 'rejected' ? 'danger' : ($row['promotion_status'] === 'recommended' ? 'warning text-dark' : 'secondary')) ?>">
              <?= htmlspecialchars(ucfirst($row['promotion_status'])) ?>
            </span>
            <?php if (!empty($row['promoted_designation'])): ?>
              <div class="small"><?= htmlspecialchars($row['promoted_designation']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string)$row['reviewer_name']) ?></td>
          <td>
            <?php if ($role !== 'employee'): ?>
              <?php if (($row['promotion_status'] ?? 'none') !== 'approved'): ?>
                <a href="performance.php?a=promote&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-warning">Promotion</a>
              <?php endif; ?>
              <form method="post" action="performance.php?a=delete" class="d-inline">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
