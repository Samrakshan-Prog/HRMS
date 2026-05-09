<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="container-fluid">
  <div class="row">
    <?php include __DIR__ . '/../../includes/left.php'; ?>

    <main class="col-md-10 ms-sm-auto content-area">

      <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h2 class="m-0">Attendance</h2>
        <?php if ($role !== 'employee'): ?>
          <a href="attendance.php?a=create" class="btn btn-primary">Add Attendance</a>
        <?php endif; ?>
      </div>

      <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <?php if ($role === 'employee'): ?>
        <div class="card card-body mb-3">
          <h6 class="mb-2">Today Punch Status</h6>
          <div class="small text-muted mb-2">
            Date: <?= date('Y-m-d') ?> |
            Check In: <?= htmlspecialchars((string)($today['check_in'] ?? '-')) ?> |
            Check Out: <?= htmlspecialchars((string)($today['check_out'] ?? '-')) ?>
          </div>
          <div>
            <form method="post" action="attendance.php?a=punchIn" class="d-inline">
              <?= csrf_input() ?>
              <button type="submit" class="btn btn-success btn-sm">Punch In</button>
            </form>
            <form method="post" action="attendance.php?a=punchOut" class="d-inline">
              <?= csrf_input() ?>
              <button type="submit" class="btn btn-warning btn-sm">Punch Out</button>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <table class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Date</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Status</th>
            <?php if ($role !== 'employee'): ?><th>Action</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['employee_code'].' - '.$row['first_name']) ?></td>
              <td><?= htmlspecialchars($row['attendance_date']) ?></td>
              <td><?= htmlspecialchars((string)($row['check_in'] ?: '-')) ?></td>
              <td><?= htmlspecialchars((string)($row['check_out'] ?: '-')) ?></td>
              <td><span class="badge bg-info"><?= ucfirst($row['status']) ?></span></td>
              <?php if ($role !== 'employee'): ?>
                <td>
                  <a href="attendance.php?a=edit&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                  <form method="post" action="attendance.php?a=delete" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete attendance?')">Delete</button>
                  </form>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </main>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
