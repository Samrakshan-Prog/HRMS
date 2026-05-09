<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Edit Repayment Entry</h2></div>
  <form method="post" action="repayments.php?a=update" class="card card-body">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Loan</label>
        <select class="form-select" name="loan_id" required>
          <?php foreach ($loans as $loan): ?>
            <option value="<?= (int)$loan['id'] ?>" <?= (int)$record['loan_id'] === (int)$loan['id'] ? 'selected' : '' ?>>#<?= (int)$loan['id'] ?> - <?= htmlspecialchars($loan['employee_code'] . ' ' . $loan['first_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($record['due_date']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Paid Date</label><input type="date" name="paid_date" class="form-control" value="<?= htmlspecialchars((string)$record['paid_date']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Amount Due</label><input type="number" step="0.01" name="amount_due" class="form-control" value="<?= htmlspecialchars($record['amount_due']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Amount Paid</label><input type="number" step="0.01" name="amount_paid" class="form-control" value="<?= htmlspecialchars($record['amount_paid']) ?>"></div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($record['payment_status'])) ?> (auto-calculated)" disabled>
      </div>
      <div class="col-md-4"><label class="form-label">Payment Mode</label>
        <select class="form-select" name="payment_mode">
          <?php foreach (['','cash','bank_transfer','upi','salary_deduction'] as $mode): ?>
            <option value="<?= $mode ?>" <?= (string)$record['payment_mode'] === $mode ? 'selected' : '' ?>><?= $mode === '' ? 'Select mode' : ucwords(str_replace('_', ' ', $mode)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Reference No</label><input type="text" class="form-control" name="reference_no" value="<?= htmlspecialchars((string)$record['reference_no']) ?>"></div>
      <div class="col-md-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="3"><?= htmlspecialchars((string)$record['remarks']) ?></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update Repayment</button> <a href="repayments.php" class="btn btn-secondary">Back</a></div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
