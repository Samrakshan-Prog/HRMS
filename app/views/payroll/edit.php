<?php
$salaryMonthValue = preg_match('/^\d{4}-\d{2}$/', (string)$payroll['salary_month'])
    ? (string)$payroll['salary_month']
    : date('Y-m');
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include __DIR__ . '/../../includes/left.php'; ?>

    <main class="col-md-10 ms-sm-auto content-area">
      <h2 class="mb-4 mt-4">Edit Payroll</h2>

      <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
      <?php endif; ?>

      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <form method="POST" action="payroll.php?a=update" id="payrollEditForm">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$payroll['id'] ?>">

        <div class="mb-3">
          <label class="form-label">Employee</label>
          <select name="employee_id" class="form-select" required>
            <?php foreach ($employees as $emp): ?>
              <option value="<?= (int)$emp['id'] ?>" <?= (int)$emp['id'] === (int)$payroll['employee_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Salary Month</label>
          <input type="month" name="salary_month" class="form-control" value="<?= htmlspecialchars($salaryMonthValue) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Basic Salary</label>
          <input type="number" step="0.01" name="basic_salary" class="form-control" value="<?= htmlspecialchars($payroll['basic_salary']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Allowances</label>
          <input type="number" step="0.01" name="allowances" class="form-control" value="<?= htmlspecialchars($payroll['allowances']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Bonus</label>
          <input type="number" step="0.01" name="bonus" class="form-control" value="<?= htmlspecialchars((string)($payroll['bonus'] ?? 0)) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Other Deductions</label>
          <input type="number" step="0.01" name="deductions" class="form-control" value="<?= htmlspecialchars($payroll['deductions']) ?>">
        </div>

        <div class="card card-body mb-3 bg-light">
          <h6 class="mb-2">Auto Calculation Preview</h6>
          <div id="previewBox" class="small text-muted">Leave and EMI deductions are auto-calculated from attendance and repayment data.</div>
        </div>

        <button type="button" class="btn btn-outline-primary" id="previewBtn">Preview</button>
        <button class="btn btn-primary">Save</button>
        <a href="payroll.php?a=index" class="btn btn-secondary ms-2">Cancel</a>
      </form>

    </main>
  </div>
</div>

<script>
document.getElementById('previewBtn').addEventListener('click', async function () {
  const form = document.getElementById('payrollEditForm');
  const data = new FormData(form);
  const response = await fetch('payroll.php?a=preview', { method: 'POST', body: data });
  const result = await response.json();
  document.getElementById('previewBox').innerHTML =
    'Leave Days: <b>' + result.leave_days + '</b><br>' +
    'Leave Deduction: <b>' + Number(result.leave_deduction).toFixed(2) + '</b><br>' +
    'Loan EMI Deduction: <b>' + Number(result.loan_emi_deduction).toFixed(2) + '</b><br>' +
    'Total Deductions: <b>' + Number(result.total_deductions).toFixed(2) + '</b><br>' +
    'Net Salary: <b>' + Number(result.net_salary).toFixed(2) + '</b>';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
