<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="container-fluid">
  <div class="row">
    <?php include __DIR__ . '/../../includes/left.php'; ?>

    <main class="col-md-10 ms-sm-auto content-area">
      <h2 class="mb-4"><?= $role === 'employee' ? 'Apply Leave' : 'Add Leave' ?></h2>

      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <form method="POST" action="leaves.php?a=store">
        <?= csrf_input() ?>

        <?php if ($role === 'employee'): ?>
          <input type="hidden" name="employee_id" value="<?= (int)$employeeId ?>">
          <div class="alert alert-info">Your leave request will be submitted for approval.</div>
        <?php else: ?>
          <div class="mb-3">
            <label class="form-label">Employee</label>
            <select name="employee_id" class="form-select" required>
              <option value="">Select Employee</option>
              <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>">
                  <?= $emp['employee_code'] ?> - <?= $emp['first_name'] ?> <?= $emp['last_name'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>


        <div class="mb-3">
          <label class="form-label">Leave Type</label>
          <select name="leave_type" class="form-select">
            <option value="casual">Casual</option>
            <option value="sick">Sick</option>
            <option value="paid">Paid</option>
            <option value="unpaid">Unpaid</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea name="reason" class="form-control"></textarea>
        </div>

        <?php if ($role !== 'employee'): ?>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        <?php endif; ?>

        <button class="btn btn-primary">Submit</button>
        <a href="leaves.php?a=index" class="btn btn-secondary ms-2">Cancel</a>

      </form>
    </main>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
