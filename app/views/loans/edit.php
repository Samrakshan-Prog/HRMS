<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Edit Loan</h2></div>
  <form method="post" action="loans.php?a=update" class="card card-body">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Employee</label>
        <select name="employee_id" class="form-select" required <?= $role === 'employee' ? 'disabled' : '' ?>>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= (int)$emp['id'] ?>" <?= ((int)$record['employee_id'] === (int)$emp['id']) ? 'selected' : '' ?>><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($role === 'employee'): ?>
          <input type="hidden" name="employee_id" value="<?= (int)$record['employee_id'] ?>">
        <?php endif; ?>
      </div>
      <div class="col-md-4"><label class="form-label">Requested Amount</label><input type="number" step="0.01" name="requested_amount" class="form-control" value="<?= htmlspecialchars($record['requested_amount']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Interest Rate (%)</label><input type="number" step="0.01" name="interest_rate" class="form-control" value="<?= htmlspecialchars($record['interest_rate']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Tenure (months)</label><input type="number" name="tenure_months" class="form-control" value="<?= htmlspecialchars($record['tenure_months']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Approved Amount</label><input type="number" step="0.01" name="approved_amount" class="form-control" value="<?= htmlspecialchars((string)$record['approved_amount']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Request Date</label><input type="date" name="request_date" class="form-control" value="<?= htmlspecialchars($record['request_date']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Status</label>
        <select name="status" class="form-select" <?= $role === 'employee' ? 'disabled' : '' ?>>
          <?php foreach (['pending','approved','rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $record['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
          <?php if ($record['status'] === 'closed'): ?>
            <option value="closed" selected>Closed</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Approved Date</label><input type="date" name="approved_date" class="form-control" value="<?= htmlspecialchars((string)$record['approved_date']) ?>"></div>
      <div class="col-md-12"><label class="form-label">Purpose</label><input type="text" name="purpose" class="form-control" maxlength="255" value="<?= htmlspecialchars((string)$record['purpose']) ?>"></div>
      <div class="col-md-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars((string)$record['remarks']) ?></textarea></div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Update Loan</button>
      <a href="loans.php" class="btn btn-secondary">Back</a>
    </div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
