<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <section class="dashboard-shell">
    <div class="dashboard-toolbar">
      <div class="dashboard-heading">
        <span class="dashboard-kicker">Finance Control</span>
        <h2 class="m-0">Finance Dashboard</h2>
        <p class="dashboard-subtitle">Keep payroll movement, recovery flow, pending approvals, and overdue items together.</p>
      </div>
    </div>

    <div class="dashboard-stats">
      <div class="card stat-card"><div class="card-body"><h6>Monthly Payroll Payout</h6><h3 class="stat-value"><?= number_format((float)$monthlyPayroll, 2) ?></h3><a href="payroll.php" class="stat-link">View payroll</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Monthly Loan Recovery</h6><h3 class="stat-value"><?= number_format((float)$monthlyLoanRecovery, 2) ?></h3><a href="repayments.php" class="stat-link">View repayments</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Pending Loan Requests</h6><h3 class="stat-value"><?= (int)$pendingLoans ?></h3><a href="loans.php" class="stat-link">Review loans</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Overdue Repayments</h6><h3 class="stat-value"><?= (int)$overdueRepayments ?></h3><a href="repayments.php" class="stat-link">Follow up</a></div></div>
    </div>

    <div class="card action-card"><div class="card-header">Quick Actions</div><div class="card-body">
      <a href="loans.php" class="btn btn-dark">Loan Approval Queue</a>
      <a href="repayments.php?a=create" class="btn btn-secondary">Add Repayment</a>
      <a href="payroll.php?a=create" class="btn btn-info">Generate Payroll</a>
      <a href="reports.php" class="btn btn-outline-primary">Export Reports</a>
    </div></div>
  </section>
</main>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
