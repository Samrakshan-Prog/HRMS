<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Apply / Add Loan</h2></div>
  <form method="post" action="loans.php?a=store" class="card card-body">
    <?= csrf_input() ?>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Employee</label>
        <select name="employee_id" class="form-select" required <?= $role === 'employee' ? 'disabled' : '' ?>>
          <option value="">Select employee</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= (int)$emp['id'] ?>" <?= ($employeeId && (int)$employeeId === (int)$emp['id']) ? 'selected' : '' ?>><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($role === 'employee'): ?>
          <input type="hidden" name="employee_id" value="<?= (int)$employeeId ?>">
        <?php endif; ?>
      </div>
      <div class="col-md-4"><label class="form-label">Requested Amount</label><input type="number" step="0.01" name="requested_amount" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Interest Rate (%)</label><input type="number" step="0.01" name="interest_rate" class="form-control" value="10"></div>
      <div class="col-md-4"><label class="form-label">Tenure (months)</label><input type="number" name="tenure_months" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Approved Amount</label><input type="number" step="0.01" name="approved_amount" class="form-control"></div>
      <div class="col-md-4"><label class="form-label">Request Date</label><input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Status</label>
        <select name="status" class="form-select" <?= $role === 'employee' ? 'disabled' : '' ?>>
          <option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Approved Date</label><input type="date" name="approved_date" class="form-control"></div>
      <div class="col-md-12"><label class="form-label">Purpose</label><input type="text" name="purpose" class="form-control" maxlength="255"></div>
      <div class="col-md-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Save Loan</button>
      <a href="loans.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
