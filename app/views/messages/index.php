<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="m-0">Communication Center</h2>
    <a href="messages.php?a=create" class="btn btn-primary">Compose Message</a>
  </div>
  <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-primary"><tr><th>ID</th><th>Employee</th><th>Subject</th><th>From</th><th>To</th><th>Context</th><th>Date</th></tr></thead>
      <tbody>
      <?php if (count($records) === 0): ?><tr><td colspan="7" class="text-center">No messages found.</td></tr><?php endif; ?>
      <?php foreach ($records as $row): ?>
        <tr>
          <td><?= (int)$row['id'] ?></td>
          <td><?= htmlspecialchars($row['employee_code'] . ' - ' . $row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><strong><?= htmlspecialchars($row['subject']) ?></strong><br><small><?= nl2br(htmlspecialchars(substr($row['message'], 0, 120))) ?></small></td>
          <td><?= htmlspecialchars($row['sender_name']) ?></td>
          <td><?= htmlspecialchars((string)$row['receiver_name']) ?></td>
          <td><?= htmlspecialchars($row['context_type']) ?><?= $row['context_id'] ? ' #' . (int)$row['context_id'] : '' ?></td>
          <td><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
