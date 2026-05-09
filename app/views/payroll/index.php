<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="container-fluid">
  <div class="row">
    <?php include __DIR__ . '/../../includes/left.php'; ?>

    <main class="col-md-10 ms-sm-auto content-area">

      <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h2 class="m-0">Payroll</h2>
        <?php if ($role !== 'employee'): ?>
          <a href="payroll.php?a=create" class="btn btn-primary">Generate Payroll</a>
        <?php endif; ?>
      </div>

      <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
      <?php endif; ?>

      <table class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Month</th>
            <th>Basic</th>
            <th>Allowances</th>
            <th>Bonus</th>
            <th>Leave Deduction</th>
            <th>Loan EMI</th>
            <th>Other Deduction</th>
            <th>Net Salary</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['employee_code'].' - '.$row['first_name']) ?></td>
              <td><?= htmlspecialchars($row['salary_month']) ?></td>
              <td><?= number_format((float)$row['basic_salary'],2) ?></td>
              <td><?= number_format((float)$row['allowances'],2) ?></td>
              <td><?= number_format((float)($row['bonus'] ?? 0),2) ?></td>
              <td><?= number_format((float)($row['leave_deduction'] ?? 0),2) ?></td>
              <td><?= number_format((float)($row['loan_emi_deduction'] ?? 0),2) ?></td>
              <td><?= number_format((float)$row['deductions'],2) ?></td>
              <td><strong><?= number_format((float)$row['net_salary'],2) ?></strong></td>
              <td>
                <?php if ($role !== 'employee'): ?>
                  <a href="payroll.php?a=edit&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <?php endif; ?>
                <a href="payroll.php?a=payslip&id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-info">Payslip</a>
                <?php if ($role !== 'employee'): ?>
                  <form method="post" action="payroll.php?a=delete" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete payroll?')">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </main>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
