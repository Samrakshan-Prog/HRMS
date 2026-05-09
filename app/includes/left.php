<?php
$role = $_SESSION['user_role'] ?? 'admin';
$isEmployee = $role === 'employee';
$isFinance = $role === 'finance';
$isHrAdmin = in_array($role, ['admin', 'hr'], true);
?>
<nav class="col-md-2 sidebar">
  <ul class="nav flex-column">
    <li class="nav-item mb-2">
      <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    </li>

    <?php if ($isHrAdmin): ?>
      <li class="nav-item mb-2"><a href="employees.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>">Employees</a></li>
    <?php endif; ?>

    <?php if ($isHrAdmin || $isEmployee): ?>
      <li class="nav-item mb-2"><a href="attendance.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">Attendance</a></li>
    <?php endif; ?>

    <?php if ($isHrAdmin || $isEmployee): ?>
      <li class="nav-item mb-2"><a href="leaves.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'leaves.php' ? 'active' : '' ?>"><?= $isEmployee ? 'My Leaves' : 'Leave Management' ?></a></li>
    <?php endif; ?>

    <?php if (!$isEmployee): ?>
      <li class="nav-item mb-2"><a href="payroll.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payroll.php' ? 'active' : '' ?>">Payroll</a></li>
    <?php else: ?>
      <li class="nav-item mb-2"><a href="payroll.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payroll.php' ? 'active' : '' ?>">My Payslips</a></li>
    <?php endif; ?>

    <li class="nav-item mb-2"><a href="loans.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : '' ?>">Loans</a></li>
    <li class="nav-item mb-2"><a href="repayments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'repayments.php' ? 'active' : '' ?>">Repayments</a></li>
    <li class="nav-item mb-2"><a href="messages.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : '' ?>">Communication</a></li>

    <?php if ($isHrAdmin || $isEmployee): ?>
      <li class="nav-item mb-2"><a href="performance.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'performance.php' ? 'active' : '' ?>">Performance</a></li>
    <?php endif; ?>

    <?php if ($isHrAdmin || $isEmployee): ?>
      <li class="nav-item mb-2"><a href="membership.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'membership.php' ? 'active' : '' ?>">Membership</a></li>
    <?php endif; ?>

    <?php if (!$isEmployee): ?>
      <li class="nav-item mb-2"><a href="reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">Reports</a></li>
    <?php endif; ?>

    <li class="nav-item mb-2"><a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">Notifications</a></li>
    <li class="nav-item mb-2"><a href="change_password.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'change_password.php' ? 'active' : '' ?>">Change Password</a></li>
    <li class="nav-item mb-2"><a href="login.php?a=logout" class="nav-link text-danger">Logout</a></li>
  </ul>
</nav>
