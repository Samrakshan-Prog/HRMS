<?php
$gross = (float)$payroll['basic_salary'] + (float)$payroll['allowances'] + (float)($payroll['bonus'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payslip #<?= (int)$payroll['id'] ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    .wrap { max-width: 900px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; }
    .head { display: flex; justify-content: space-between; margin-bottom: 16px; }
    h2 { margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    .right { text-align: right; }
    .printbar { margin-bottom: 12px; }
    @media print { .printbar { display: none; } body { margin: 0; } .wrap { border: none; } }
  </style>
</head>
<body>
  <div class="printbar">
    <button onclick="window.print()">Print / Save as PDF</button>
    <a href="payroll.php?a=index">Back</a>
  </div>

  <div class="wrap">
    <div class="head">
      <div>
        <h2><?= htmlspecialchars(company_name) ?></h2>
        <div>Payroll Payslip</div>
      </div>
      <div class="right">
        <div>Payslip ID: #<?= (int)$payroll['id'] ?></div>
        <div>Month: <?= htmlspecialchars($payroll['salary_month']) ?></div>
        <div>Generated: <?= htmlspecialchars((string)($payroll['generated_at'] ?? date('Y-m-d H:i:s'))) ?></div>
      </div>
    </div>

    <table>
      <tr><th>Employee Code</th><td><?= htmlspecialchars((string)$payroll['employee_code']) ?></td><th>Name</th><td><?= htmlspecialchars(trim(($payroll['first_name'] ?? '') . ' ' . ($payroll['last_name'] ?? ''))) ?></td></tr>
      <tr><th>Department</th><td><?= htmlspecialchars((string)($payroll['department'] ?? '-')) ?></td><th>Designation</th><td><?= htmlspecialchars((string)($payroll['designation'] ?? '-')) ?></td></tr>
    </table>

    <table>
      <thead>
        <tr><th>Earnings</th><th class="right">Amount</th><th>Deductions</th><th class="right">Amount</th></tr>
      </thead>
      <tbody>
        <tr><td>Basic Salary</td><td class="right"><?= number_format((float)$payroll['basic_salary'], 2) ?></td><td>Leave Deduction (<?= number_format((float)($payroll['leave_days'] ?? 0), 2) ?> days)</td><td class="right"><?= number_format((float)($payroll['leave_deduction'] ?? 0), 2) ?></td></tr>
        <tr><td>Allowances</td><td class="right"><?= number_format((float)$payroll['allowances'], 2) ?></td><td>Loan EMI Deduction</td><td class="right"><?= number_format((float)($payroll['loan_emi_deduction'] ?? 0), 2) ?></td></tr>
        <tr><td>Bonus</td><td class="right"><?= number_format((float)($payroll['bonus'] ?? 0), 2) ?></td><td>Other Deductions</td><td class="right"><?= number_format((float)$payroll['deductions'], 2) ?></td></tr>
      </tbody>
      <tfoot>
        <tr><th>Gross Salary</th><th class="right"><?= number_format($gross, 2) ?></th><th>Total Deductions</th><th class="right"><?= number_format((float)($payroll['total_deductions'] ?? 0), 2) ?></th></tr>
        <tr><th colspan="3">Net Salary</th><th class="right"><?= number_format((float)$payroll['net_salary'], 2) ?></th></tr>
      </tfoot>
    </table>
  </div>
</body>
</html>
