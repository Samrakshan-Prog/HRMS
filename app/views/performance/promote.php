<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="mt-4 mb-3"><h2>Promotion Decision</h2></div>
  <form method="post" action="performance.php?a=approvePromotion" class="card card-body">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Employee</label><input class="form-control" value="<?= htmlspecialchars($record['employee_code'] . ' - ' . $record['first_name'] . ' ' . $record['last_name']) ?>" disabled></div>
      <div class="col-md-6"><label class="form-label">Current Designation</label><input class="form-control" value="<?= htmlspecialchars((string)$record['designation']) ?>" disabled></div>
      <div class="col-md-4"><label class="form-label">Suggested Designation</label><input type="text" name="promoted_designation" class="form-control" value="<?= htmlspecialchars((string)$record['promoted_designation']) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Effective Date</label><input type="date" name="promotion_effective_date" class="form-control" value="<?= htmlspecialchars((string)($record['promotion_effective_date'] ?? date('Y-m-d'))) ?>" required></div>
      <div class="col-md-4"><label class="form-label">Action</label>
        <select name="action_type" class="form-select">
          <option value="approve">Approve Promotion</option>
          <option value="reject">Reject Promotion</option>
        </select>
      </div>
      <div class="col-md-12"><label class="form-label">Decision Note</label><textarea name="decision_note" class="form-control" rows="3"></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Submit Decision</button> <a href="performance.php" class="btn btn-secondary">Back</a></div>
  </form>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
