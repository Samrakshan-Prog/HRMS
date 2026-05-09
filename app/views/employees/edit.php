<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="container-fluid">
  <div class="row">

    <?php include __DIR__ . '/../../includes/left.php'; ?>

    <main class="col-md-10 ms-sm-auto content-area">
      <h2 class="mb-4">Edit Employee</h2>

      <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
      <?php endif; ?>

      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <form method="POST" action="employees.php?a=update">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= $employee['id'] ?>">

        <div class="mb-3">
          <label class="form-label">First Name</label>
          <input type="text" class="form-control" name="first_name" required
                 value="<?= htmlspecialchars($employee['first_name']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" class="form-control" name="last_name" required
                 value="<?= htmlspecialchars($employee['last_name']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Phone</label>
          <input type="text" class="form-control" name="phone" required
                 value="<?= htmlspecialchars($employee['phone']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Department</label>
          <input type="text" class="form-control" name="department" required
                 value="<?= htmlspecialchars($employee['department']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Designation</label>
          <input type="text" class="form-control" name="designation" required
                 value="<?= htmlspecialchars($employee['designation']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Date of Joining</label>
          <input type="date" class="form-control" name="date_of_joining" required
                 value="<?= htmlspecialchars($employee['date_of_joining']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Salary</label>
          <input type="number" step="0.01" class="form-control" name="salary" required
                 value="<?= htmlspecialchars($employee['salary']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" required>
            <option value="1" <?= $employee['status'] == 1 ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= $employee['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="card card-body bg-light mb-3">
          <h5 class="mb-3">Employee Login Account</h5>
          <?php if (!empty($employee['user_id'])): ?>
            <div class="alert alert-info py-2">
              Linked login account ID: <?= (int)$employee['user_id'] ?>
            </div>
          <?php else: ?>
            <div class="alert alert-warning py-2">
              This employee does not have a login account yet. Fill the fields below to create one.
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required
                   value="<?= htmlspecialchars((string)($employee['username'] ?? '')) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required
                   value="<?= htmlspecialchars((string)($employee['email'] ?? '')) ?>">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">New Password</label>
              <input type="password" class="form-control" name="password">
              <small class="text-muted">Leave blank to keep the current password.</small>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Confirm Password</label>
              <input type="password" class="form-control" name="confirm_password">
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="employees.php?a=index" class="btn btn-secondary ms-2">Cancel</a>
      </form>
    </main>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
