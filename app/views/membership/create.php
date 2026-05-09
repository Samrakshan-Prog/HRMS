<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Add Membership</h2></div>
  <form method="post" action="membership.php?a=store" class="card card-body">
    <?= csrf_input() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Employee</label>
        <select class="form-select" name="employee_id" required>
          <option value="">Select employee</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= (int)$emp['id'] ?>"><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Membership No</label><input type="text" class="form-control" name="membership_no" required></div>
      <div class="col-md-4"><label class="form-label">Membership Type</label><input type="text" class="form-control" name="membership_type" required></div>
      <div class="col-md-4"><label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="col-md-4"><label class="form-label">End Date</label><input type="date" class="form-control" name="end_date"></div>
      <div class="col-md-4"><label class="form-label">Status</label>
        <select class="form-select" name="status"><option value="active">Active</option><option value="expired">Expired</option><option value="suspended">Suspended</option></select>
      </div>
      <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" rows="4" name="notes"></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save Membership</button> <a href="membership.php" class="btn btn-secondary">Back</a></div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
