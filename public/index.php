<?php

session_start();

require_once __DIR__ . '/../app/config/config.php';

$legacyAction = $_GET['a'] ?? '';
if (in_array($legacyAction, ['login', 'doLogin', 'logout'], true)) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: login.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

$isAuthenticated = !empty($_SESSION['user_id']);
$ctaHref = $isAuthenticated ? 'dashboard.php' : 'login.php';
$ctaLabel = $isAuthenticated ? 'Open Dashboard' : 'Login';
$userName = trim((string)($_SESSION['user_name'] ?? 'Team Member'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars(company_name) ?> | <?= htmlspecialchars(APP_NAME) ?></title>
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
  <link rel="stylesheet" href="css/theme-toggle.css?v=<?= rawurlencode(APP_VERSION) ?>">
  <link rel="stylesheet" href="css/landing.css?v=<?= rawurlencode(APP_VERSION) ?>">
</head>
<body class="landing-body">
  <div class="landing-shell">
    <header class="site-header">
      <div class="site-header-inner">
        <a href="index.php" class="brand-mark">
          <img src="<?= htmlspecialchars(company_logo_home) ?>" alt="<?= htmlspecialchars(company_name) ?>">
          <span><?= htmlspecialchars(company_name) ?></span>
        </a>
        <nav class="site-nav">
          <a href="#overview">Overview</a>
          <a href="#solutions">Solutions</a>
          <a href="#workflow">Workflow</a>
          <a href="#contact">Access</a>
        </nav>
        <div class="site-actions">
          <?php if ($isAuthenticated): ?>
            <span class="welcome-chip">Welcome, <?= htmlspecialchars($userName) ?></span>
          <?php endif; ?>
          <a href="<?= htmlspecialchars($ctaHref) ?>" class="btn-solid"><?= htmlspecialchars($ctaLabel) ?></a>
        </div>
      </div>
    </header>

    <main>
      <section class="hero-section" id="overview">
        <div class="hero-copy">
          <span class="eyebrow">Dynamic Interaction Web Portal for HR and Employee Credit Repay Process</span>
          <h1>One digital workplace for people operations, payroll discipline, and employee credit recovery.</h1>
          <p>
            <?= htmlspecialchars(company_name) ?> uses this portal to connect HR teams, finance teams, and employees in one
            controlled workspace. From leave and attendance to payroll, loan approvals, EMI tracking, messages,
            and notifications, the platform keeps every internal process visible and accountable.
          </p>
          <div class="hero-actions">
            <a href="<?= htmlspecialchars($ctaHref) ?>" class="btn-solid btn-large"><?= htmlspecialchars($ctaLabel) ?></a>
            <a href="#solutions" class="btn-outline btn-large">Explore Features</a>
          </div>
          <div class="hero-tags">
            <span>Employee Management</span>
            <span>Payroll + Leave Deduction</span>
            <span>Loan Approval + Repayment</span>
            <span>Internal Communication</span>
          </div>
        </div>

        <div class="hero-panel">
          <div class="hero-card hero-card-primary">
            <p class="card-kicker">Operational Focus</p>
            <h2>Built for finance companies that need HR and repayment workflows to move together.</h2>
            <p>
              The portal reduces manual follow-up by bringing attendance, salary deduction logic, loan lifecycle,
              repayments, and employee communication into one system.
            </p>
          </div>

          <div class="hero-card-grid">
            <article class="hero-card">
              <strong>HR Control</strong>
              <span>Employees, attendance, leave, performance, and membership records.</span>
            </article>
            <article class="hero-card">
              <strong>Finance Control</strong>
              <span>Loans, EMI schedules, repayment updates, and payroll-linked deductions.</span>
            </article>
            <article class="hero-card">
              <strong>Employee Access</strong>
              <span>Personal dashboard, leave requests, payslips, loans, messages, and alerts.</span>
            </article>
            <article class="hero-card accent-card">
              <strong>Secure Access</strong>
              <span>Role-based access for Admin, HR, Finance, and Employee users.</span>
            </article>
          </div>
        </div>
      </section>

      <section class="feature-section" id="solutions">
        <div class="section-heading">
          <span class="eyebrow">Core Solutions</span>
          <h2>What the portal helps your company do</h2>
          <p>Each module is designed to reduce manual work and keep operational decisions visible across teams.</p>
        </div>
        <div class="feature-grid">
          <article class="feature-card">
            <h3>HR Administration</h3>
            <p>Create employee records, manage joining details, monitor attendance, and track leave status in one place.</p>
          </article>
          <article class="feature-card">
            <h3>Payroll Discipline</h3>
            <p>Generate payslips with leave-based deduction logic and repayment deductions that matter for salary processing.</p>
          </article>
          <article class="feature-card">
            <h3>Employee Credit Process</h3>
            <p>Handle loan requests, approvals, EMI schedules, repayment collection status, and overdue follow-up.</p>
          </article>
          <article class="feature-card">
            <h3>Communication Layer</h3>
            <p>Use messages and notifications so employees and teams stay informed about leave, loans, repayments, and payroll actions.</p>
          </article>
        </div>
      </section>

      <section class="workflow-section" id="workflow">
        <div class="section-heading">
          <span class="eyebrow">Working Flow</span>
          <h2>Designed around real internal movement</h2>
        </div>
        <div class="workflow-grid">
          <article class="workflow-step">
            <span>01</span>
            <h3>Employee Operations</h3>
            <p>Maintain profile, attendance, leave, performance, and membership data without fragmented files.</p>
          </article>
          <article class="workflow-step">
            <span>02</span>
            <h3>Loan Decisioning</h3>
            <p>Capture requests, review them through HR and finance, and keep every approval stage inside the portal.</p>
          </article>
          <article class="workflow-step">
            <span>03</span>
            <h3>Repayment Tracking</h3>
            <p>Monitor due amounts, paid amounts, overdue repayments, and salary-linked recovery points.</p>
          </article>
          <article class="workflow-step">
            <span>04</span>
            <h3>Alerts and Visibility</h3>
            <p>Send updates through notifications and messages so people know what changed and what requires action.</p>
          </article>
        </div>
      </section>

      <section class="access-section" id="contact">
        <div class="access-copy">
          <span class="eyebrow">Portal Access</span>
          <h2>Move from company overview to secure sign-in in one click.</h2>
          <p>
            Use the login page to access the role-based dashboard for HR, finance, or employee operations.
            If you are already signed in, you can continue directly to your dashboard.
          </p>
        </div>
        <div class="access-panel">
          <a href="<?= htmlspecialchars($ctaHref) ?>" class="btn-solid btn-large"><?= htmlspecialchars($ctaLabel) ?></a>
          <?php if ($isAuthenticated): ?>
            <a href="login.php?a=logout" class="btn-outline btn-large">Logout</a>
          <?php else: ?>
            <a href="login.php" class="access-link">Open secure login page</a>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="site-footer-inner">
        <div>
          <strong><?= htmlspecialchars(company_name) ?></strong>
          <p>Dynamic interaction portal for HR operations and employee credit repay process.</p>
        </div>
        <div>
          <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(company_name) ?></span>
          <span class="footer-divider">|</span>
          <span><?= htmlspecialchars(APP_NAME) ?></span>
        </div>
      </div>
    </footer>
    <div class="theme-toggle-dock" aria-live="polite">
      <span class="theme-toggle-label" data-theme-label>Light mode</span>
      <button type="button" class="theme-toggle-ball" data-theme-toggle aria-label="Switch color theme" aria-pressed="false">
        <span class="theme-toggle-core"></span>
        <span class="theme-toggle-shape theme-toggle-shape-one"></span>
        <span class="theme-toggle-shape theme-toggle-shape-two"></span>
        <span class="theme-toggle-spark"></span>
      </button>
    </div>
  </div>
  <script src="js/theme.js?v=<?= rawurlencode(APP_VERSION) ?>"></script>
</body>
</html>
