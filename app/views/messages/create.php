<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Compose Message</h2></div>
  <form method="post" action="messages.php?a=store" class="card card-body">
    <?= csrf_input() ?>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Employee</label>
        <select class="form-select" name="employee_id" required <?= $role === 'employee' ? 'disabled' : '' ?>>
          <option value="">Select employee</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= (int)$emp['id'] ?>" <?= ($employeeId && (int)$employeeId === (int)$emp['id']) ? 'selected' : '' ?>><?= htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($role === 'employee'): ?><input type="hidden" name="employee_id" value="<?= (int)$employeeId ?>"><?php endif; ?>
      </div>
      <div class="col-md-4"><label class="form-label">To User</label>
        <select class="form-select" name="receiver_user_id">
          <option value="">Broadcast/General</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars(($u['full_name'] ?: $u['email']) . ' (' . $u['email'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Context Type</label>
        <select class="form-select" name="context_type">
          <option value="general">General</option><option value="loan">Loan</option><option value="repayment">Repayment</option><option value="attendance">Attendance</option><option value="leave">Leave</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Context ID</label><input type="number" name="context_id" class="form-control" placeholder="Optional"></div>
      <div class="col-md-8"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" required></div>
      <div class="col-md-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Send</button> <a href="messages.php" class="btn btn-secondary">Back</a></div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
