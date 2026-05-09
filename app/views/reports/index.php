<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Reports & Export</h2></div>
  <div class="row g-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><h5>Attendance</h5><p class="mb-2">Records: <?= (int)$attendanceCount ?></p><a class="btn btn-sm btn-outline-primary" href="reports.php?a=export&type=attendance">Export CSV</a></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h5>Payroll</h5><p class="mb-2">Records: <?= (int)$payrollCount ?></p><a class="btn btn-sm btn-outline-primary" href="reports.php?a=export&type=payroll">Export CSV</a></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h5>Loans</h5><p class="mb-2">Records: <?= (int)$loanCount ?></p><a class="btn btn-sm btn-outline-primary" href="reports.php?a=export&type=loans">Export CSV</a></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h5>Repayments</h5><p class="mb-2">Records: <?= (int)$repaymentCount ?></p><a class="btn btn-sm btn-outline-primary" href="reports.php?a=export&type=repayments">Export CSV</a></div></div></div>
  </div>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
