<?php
// app/includes/header.php - top navigation bar
$adminName = $_SESSION['admin_name'] ?? ($_SESSION['user_name'] ?? 'User');
$roleLabel = ucfirst($_SESSION['user_role'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo company_name; ?> | <?php echo APP_NAME; ?></title>
  <script>
    (function () {
      try {
        var storedTheme = localStorage.getItem('tf-portal-theme');
        var systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', storedTheme || systemTheme);
      } catch (error) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="./css/theme-toggle.css?v=<?php echo rawurlencode(APP_VERSION); ?>" rel="stylesheet" />
  <link href="./css/login.css?v=<?php echo rawurlencode(APP_VERSION); ?>" rel="stylesheet" />
</head>
<body class="app-page">
<nav class="navbar navbar-expand-lg app-header">
  <div class="container-fluid app-header-inner">
    <a class="navbar-brand d-flex align-items-center app-brand" href="dashboard.php">
      <img src="<?php echo company_logo_home; ?>" alt="<?php echo company_name; ?>" width="34" height="34" class="me-2 app-brand-logo">
      <span><?php echo company_name; ?></span>
    </a>
    <div class="d-flex align-items-center app-header-user">
      <span class="navbar-text me-3 app-user-chip">
        <?= htmlspecialchars($adminName) ?> (<?= htmlspecialchars($roleLabel) ?>)
      </span>
      <a href="login.php?a=logout" class="btn app-logout-btn">Logout</a>
    </div>
  </div>
</nav>
