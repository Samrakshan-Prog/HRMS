<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Add Repayment Entry</h2></div>
  <form method="post" action="repayments.php?a=store" class="card card-body">
    <?= csrf_input() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Loan</label>
        <select class="form-select" name="loan_id" required>
          <option value="">Select Loan</option>
          <?php foreach ($loans as $loan): ?>
            <option value="<?= (int)$loan['id'] ?>">#<?= (int)$loan['id'] ?> - <?= htmlspecialchars($loan['employee_code'] . ' ' . $loan['first_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Paid Date</label><input type="date" name="paid_date" class="form-control"></div>
      <div class="col-md-4"><label class="form-label">Amount Due</label><input type="number" step="0.01" name="amount_due" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Amount Paid</label><input type="number" step="0.01" name="amount_paid" class="form-control" value="0"></div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <input type="text" class="form-control" value="Auto-calculated from due date and amount paid" disabled>
      </div>
      <div class="col-md-4"><label class="form-label">Payment Mode</label>
        <select class="form-select" name="payment_mode">
          <option value="">Select mode</option><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="upi">UPI</option><option value="salary_deduction">Salary Deduction</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Reference No</label><input type="text" class="form-control" name="reference_no"></div>
      <div class="col-md-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="3"></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save Repayment</button> <a href="repayments.php" class="btn btn-secondary">Back</a></div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
