<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <section class="dashboard-shell">
    <div class="dashboard-toolbar">
      <div class="dashboard-heading">
        <span class="dashboard-kicker">Employee Panel</span>
        <h2 class="m-0">Employee Dashboard</h2>
        <p class="dashboard-subtitle">Track your leave, attendance, payslips, loans, and repayment status from one place.</p>
      </div>
    </div>

    <div class="dashboard-stats">
      <div class="card stat-card"><div class="card-body"><h6>My Loans</h6><h3 class="stat-value"><?= (int)$myLoans ?></h3><a href="loans.php" class="stat-link">Open loans</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Pending Loan Requests</h6><h3 class="stat-value"><?= (int)$myPendingLoans ?></h3><a href="loans.php?a=create" class="stat-link">Apply new</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Overdue Repayments</h6><h3 class="stat-value"><?= (int)$myOverdue ?></h3><a href="repayments.php" class="stat-link">Check status</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Attendance This Month</h6><h3 class="stat-value"><?= (int)$myAttendanceThisMonth ?></h3><a href="attendance.php" class="stat-link">Punch attendance</a></div></div>
    </div>

    <div class="card action-card"><div class="card-header">Quick Actions</div><div class="card-body">
      <form method="post" action="attendance.php?a=punchIn">
        <?= csrf_input() ?>
        <button type="submit" class="btn btn-success">Punch In</button>
      </form>
      <form method="post" action="attendance.php?a=punchOut">
        <?= csrf_input() ?>
        <button type="submit" class="btn btn-warning">Punch Out</button>
      </form>
      <a href="loans.php?a=create" class="btn btn-dark">Apply Loan</a>
      <a href="repayments.php" class="btn btn-secondary">Repayment Status</a>
      <a href="payroll.php" class="btn btn-info">My Payslips</a>
    </div></div>
  </section>
</main>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
