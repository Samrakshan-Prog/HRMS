<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Add Performance Evaluation</h2></div>
  <form method="post" action="performance.php?a=store" class="card card-body">
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
      <div class="col-md-4"><label class="form-label">Evaluation Month</label><input type="text" class="form-control" name="evaluation_month" value="<?= date('F Y') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Online Test Score</label><input type="number" step="0.01" min="0" max="100" class="form-control" name="test_score" required></div>
      <div class="col-md-4"><label class="form-label">Attendance Score</label><input type="number" step="0.01" min="0" max="100" class="form-control" name="attendance_score" required></div>
      <div class="col-md-4"><label class="form-label">Recommended Designation</label><input type="text" class="form-control" name="recommended_designation" placeholder="Optional"></div>
      <div class="col-md-4"><label class="form-label">Recommended Effective Date</label><input type="date" class="form-control" name="recommended_effective_date"></div>
      <div class="col-md-12"><label class="form-label">Promotion Note</label><textarea class="form-control" rows="2" name="promotion_note" placeholder="Optional recommendation note"></textarea></div>
      <div class="col-md-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="4" name="remarks"></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save Evaluation</button> <a href="performance.php" class="btn btn-secondary">Back</a></div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
