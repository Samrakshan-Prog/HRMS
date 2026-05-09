<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | <?php echo company_name; ?></title>
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
  <link rel="stylesheet" href="css/theme-toggle.css?v=<?php echo rawurlencode(APP_VERSION); ?>">
  <link rel="stylesheet" href="css/style.css?v=<?php echo rawurlencode(APP_VERSION); ?>">
</head>
<body class="login-page">
  <div class="login-scene">
    <div class="login-orb orb-one"></div>
    <div class="login-orb orb-two"></div>

    <header class="login-topbar">
      <a href="index.php" class="brand-link">
        <img src="<?php echo company_logo_home; ?>" alt="<?php echo company_name; ?>" class="logo-top">
        <span><?php echo company_name; ?></span>
      </a>
      <a href="index.php" class="back-link">Back to Home</a>
    </header>

    <main class="login-layout container">
      <section class="login-copy">
        <span class="login-badge">Secure Finance Portal</span>
        <h1>HR operations and employee credit recovery in one secure workspace.</h1>
        <p>
          Sign in to manage employee records, attendance, leave, payroll, loan approvals,
          repayment tracking, messages, and notifications through your role-based dashboard.
        </p>
        <div class="login-points">
          <span>Admin, HR, Finance, and Employee roles</span>
          <span>Protected login session</span>
          <span>Payroll, loans, and repayment workflows</span>
        </div>
      </section>

      <section id="login-form" class="auth-form">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger auth-alert">
            <?php
            switch ($error) {
              case 'missing_fields':   echo "Please fill in all required fields."; break;
              case 'invalid_email':    echo "Invalid email address."; break;
              case 'password_mismatch':echo "Passwords do not match."; break;
              case 'db_error':         echo "Database error occurred. Try again."; break;
              case 'invalid_login':    echo "Invalid email or password."; break;
              case 'inactive_account': echo "Your account is inactive. Please contact HR or the administrator."; break;
              case 'invalid_csrf':     echo "Security token expired. Please try again."; break;
              default:                 echo "An unknown error occurred.";
            }
            ?>
          </div>
        <?php endif; ?>

        <div class="auth-heading">
          <p class="form-kicker">Portal Sign In</p>
          <h4 class="form-title">Welcome Back</h4>
          <span class="form-subtitle">Use your registered company email and password to continue.</span>
        </div>

        <form action="login.php?a=doLogin" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
          <input type="text" name="website" class="honeypot">

          <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" placeholder="name@company.com" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="auth-note">
          <span>Need access? Contact HR or the system administrator.</span>
        </div>
      </section>
    </main>
  </div>

  <?php include __DIR__ . '/../../includes/footer.php'; ?>
