<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container-fluid"><div class="row">
<?php include __DIR__ . '/../../includes/left.php'; ?>
<main class="col-md-10 ms-sm-auto content-area">
  <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="m-0">Notifications</h2>
    <a class="btn btn-outline-primary" href="notifications.php?a=readAll">Mark All Read</a>
  </div>
  <div class="list-group">
    <?php if (count($records) === 0): ?>
      <div class="list-group-item">No notifications yet.</div>
    <?php endif; ?>
    <?php foreach ($records as $row): ?>
      <div class="list-group-item">
        <div class="d-flex w-100 justify-content-between">
          <h6 class="mb-1"><?= htmlspecialchars($row['title']) ?> <?= (int)$row['is_read'] === 0 ? '<span class="badge bg-warning text-dark">New</span>' : '' ?></h6>
          <small><?= htmlspecialchars($row['created_at']) ?></small>
        </div>
        <p class="mb-1"><?= htmlspecialchars($row['body']) ?></p>
        <?php if (!empty($row['link'])): ?><a href="notifications.php?a=open&id=<?= (int)$row['id'] ?>&link=<?= urlencode($row['link']) ?>">Open</a><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</main></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
