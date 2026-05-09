<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <section class="dashboard-shell">
    <div class="dashboard-toolbar">
      <div class="dashboard-heading">
        <span class="dashboard-kicker">Operations Overview</span>
        <h2 class="m-0">Admin / HR Dashboard</h2>
        <p class="dashboard-subtitle">Monitor people operations, finance movement, and pending actions without wasted space.</p>
      </div>
    </div>

    <div class="dashboard-stats">
      <div class="card stat-card"><div class="card-body"><h6>Total Employees</h6><h3 class="stat-value"><?= (int)$totalEmployees ?></h3><a href="employees.php" class="stat-link">View employees</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Active Employees</h6><h3 class="stat-value"><?= (int)$activeEmployees ?></h3><a href="employees.php" class="stat-link">View active</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Today's Attendance</h6><h3 class="stat-value"><?= (int)$todayAttendance ?></h3><a href="attendance.php" class="stat-link">View attendance</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Pending Leaves</h6><h3 class="stat-value"><?= (int)$pendingLeaves ?></h3><a href="leaves.php" class="stat-link">View leaves</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Pending Loans</h6><h3 class="stat-value"><?= (int)$pendingLoans ?></h3><a href="loans.php" class="stat-link">View loans</a></div></div>
      <div class="card stat-card"><div class="card-body"><h6>Overdue Repayments</h6><h3 class="stat-value"><?= (int)$overdueRepayments ?></h3><a href="repayments.php" class="stat-link">View repayments</a></div></div>
    </div>

    <div class="card action-card"><div class="card-header">Quick Actions</div><div class="card-body">
      <a href="employees.php?a=create" class="btn btn-primary">Add Employee</a>
      <a href="attendance.php?a=create" class="btn btn-success">Mark Attendance</a>
      <a href="payroll.php?a=create" class="btn btn-info">Generate Payroll</a>
      <a href="loans.php" class="btn btn-dark">Loan Desk</a>
      <a href="performance.php" class="btn btn-warning">Performance</a>
    </div></div>
  </section>
</main>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
