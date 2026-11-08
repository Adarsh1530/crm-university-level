<?php
/**
 * Professional Login Page
 * University-Level Student CRM Demo
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard/index.php');
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

// Handle Direct 1-Click Demo Login from buttons
if (isset($_GET['demo_as'])) {
    $demoUserId = (int)$_GET['demo_as'];
    $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.label AS role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.status = 'Active'");
    $stmt->execute([$demoUserId]);
    $demoUser = $stmt->fetch();

    if ($demoUser) {
        clear_flashes();
        $_SESSION['user_id'] = $demoUser['id'];
        $_SESSION['user'] = $demoUser;
        $GLOBALS['currentUser'] = $demoUser;
        $GLOBALS['crm_perm_cache'] = [];

        log_activity($demoUser['id'], null, 'Demo User Login', 'Logged in via demo quick switcher as ' . $demoUser['role_label']);
        set_flash('success', 'Welcome, ' . htmlspecialchars($demoUser['name']) . '! You are logged in as <strong>' . htmlspecialchars($demoUser['role_label']) . '</strong>.');
        
        $targetUrl = 'dashboard/index.php';
        if (!empty($redirect) && is_url_permitted_for_user($redirect, $demoUser)) {
            $targetUrl = $redirect;
        }
        redirect($targetUrl);
    }
}

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.label AS role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'Active') {
                $error = 'Your account has been deactivated. Please consult your CRM Administrator.';
            } else {
                // Successful login
                clear_flashes();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user;
                $GLOBALS['currentUser'] = $user;
                $GLOBALS['crm_perm_cache'] = [];

                log_activity($user['id'], null, 'User Login', 'User signed into University CRM portal');
                set_flash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
                
                $targetUrl = 'dashboard/index.php';
                if (!empty($redirect) && is_url_permitted_for_user($redirect, $user)) {
                    $targetUrl = $redirect;
                }
                redirect($targetUrl);
            }
        } else {
            $error = 'Invalid email address or password. Please verify your credentials and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | UniCRM Admissions Cloud</title>
  
  <!-- Google Fonts: Instrument Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Master Stylesheet -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">

  <style>
    body.login-page {
      background: #F4F5FA;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
      font-family: 'Instrument Sans', sans-serif;
    }
    
    .login-container {
      width: 100%;
      max-width: 980px;
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid #E6EAF0;
      box-shadow: 0 10px 40px -10px rgba(12, 36, 60, 0.08);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    .login-banner {
      background: linear-gradient(135deg, #5955D1 0%, #29294B 100%);
      color: #ffffff;
      padding: 3rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
    }

    .login-banner::after {
      content: '';
      position: absolute;
      bottom: -60px;
      right: -60px;
      width: 240px;
      height: 240px;
      background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
      border-radius: 50%;
    }

    .banner-brand {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .banner-icon {
      width: 48px;
      height: 48px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: #ffffff;
    }

    .banner-brand h2 {
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin: 0;
      color: #ffffff;
    }
    .banner-brand span {
      font-size: 0.75rem;
      color: #EEEDFC;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 600;
    }

    .banner-content h3 {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 1rem;
      line-height: 1.3;
      color: #ffffff;
    }
    .banner-content p {
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.9rem;
      line-height: 1.6;
    }

    .banner-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.15);
    }
    .stat-item strong {
      display: block;
      font-size: 1.3rem;
      color: #ffffff;
    }
    .stat-item span {
      font-size: 0.75rem;
      color: rgba(255, 255, 255, 0.75);
    }

    .login-form-pane {
      padding: 3rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-header {
      margin-bottom: 1.75rem;
    }
    .login-header h2 {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 0.35rem;
    }
    .login-header p {
      font-size: 0.875rem;
      color: var(--text-muted);
    }

    .demo-credentials-box {
      margin-top: 1.75rem;
      padding: 1.25rem;
      background: #F8FAFC;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
    }

    .demo-credentials-box h4 {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--text-muted);
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .demo-role-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
    }

    .btn-demo-quick {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.55rem 0.75rem;
      border-radius: var(--radius-md);
      background: #ffffff;
      border: 1px solid var(--border-color);
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-main);
      transition: var(--transition);
      text-align: left;
      text-decoration: none;
    }
    .btn-demo-quick:hover {
      background: #EEEDFC;
      border-color: #5955D1;
      color: #5955D1;
    }

    @media (max-width: 860px) {
      .login-container {
        grid-template-columns: 1fr;
        max-width: 480px;
      }
      .login-banner {
        display: none;
      }
      .login-form-pane {
        padding: 2.25rem 1.75rem;
      }
    }
  </style>
</head>
<body class="login-page">

<div class="login-container">
  
  <!-- Left Side: Enterprise University Branding -->
  <div class="login-banner">
    <div class="banner-brand">
      <div class="banner-icon">
        <i class="fa-solid fa-graduation-cap"></i>
      </div>
      <div>
        <h2>UniCRM</h2>
        <span>Admissions Cloud v1.0</span>
      </div>
    </div>

    <div class="banner-content">
      <h3>Empowering University Admissions & Global Enrolment</h3>
      <p>A centralized platform for higher-education recruiters to capture leads, track student application stages, coordinate follow-ups, and elevate conversions.</p>
    </div>

    <div class="banner-stats">
      <div class="stat-item">
        <strong>9 Branches</strong>
        <span>Regional Hubs</span>
      </div>
      <div class="stat-item">
        <strong>6 Stages</strong>
        <span>Pipeline</span>
      </div>
      <div class="stat-item">
        <strong>100%</strong>
        <span>Audit Trail</span>
      </div>
    </div>
  </div>

  <!-- Right Side: Sign In Form & Demo Buttons -->
  <div class="login-form-pane">
    <div class="login-header">
      <h2>Welcome Back</h2>
      <p>Enter your institutional credentials to access your dashboard.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
        <div><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <?php foreach (get_flashes() as $flash): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 1.25rem;">
        <div><?= $flash['message'] ?></div>
      </div>
    <?php endforeach; ?>

    <form method="POST" action="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="email">Institutional Email</label>
        <div style="position: relative;">
          <input type="email" id="email" name="email" class="form-control" style="width: 100%; padding-left: 2.5rem;" placeholder="name@university.edu" value="superadmin@university.edu" required>
          <i class="fa-regular fa-envelope" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
        </div>
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
          <label class="form-label" for="password" style="margin: 0;">Password</label>
          <span style="font-size: 0.75rem; color: var(--text-muted);">Default: <code>admin123</code></span>
        </div>
        <div style="position: relative;">
          <input type="password" id="password" name="password" class="form-control" style="width: 100%; padding-left: 2.5rem;" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" value="admin123" required>
          <i class="fa-solid fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Dashboard
      </button>
    </form>

    <!-- Quick Access Role Switcher -->
    <div class="demo-credentials-box">
      <h4><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Quick Access Profiles</h4>
      <div class="demo-role-grid">
        <a href="login.php?demo_as=1<?= !empty($redirect) ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn-demo-quick">
          <i class="fa-solid fa-crown" style="color: #ef4444;"></i>
          <div>
            <strong>Super Admin</strong>
            <span style="display:block; font-size:0.68rem; color:var(--text-muted);">Dr. Arthur Vance</span>
          </div>
        </a>
        <a href="login.php?demo_as=2<?= !empty($redirect) ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn-demo-quick">
          <i class="fa-solid fa-building-user" style="color: #f59e0b;"></i>
          <div>
            <strong>Admissions Manager</strong>
            <span style="display:block; font-size:0.68rem; color:var(--text-muted);">Marcus Sterling</span>
          </div>
        </a>
        <a href="login.php?demo_as=4<?= !empty($redirect) ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn-demo-quick">
          <i class="fa-solid fa-headset" style="color: #10b981;"></i>
          <div>
            <strong>Admissions Counselor</strong>
            <span style="display:block; font-size:0.68rem; color:var(--text-muted);">David Miller</span>
          </div>
        </a>
        <a href="login.php?demo_as=5<?= !empty($redirect) ? '&redirect=' . urlencode($redirect) : '' ?>" class="btn-demo-quick">
          <i class="fa-solid fa-user-tie" style="color: #8b5cf6;"></i>
          <div>
            <strong>Academic Advisor</strong>
            <span style="display:block; font-size:0.68rem; color:var(--text-muted);">Sarah Jenkins</span>
          </div>
        </a>
      </div>
    </div>

  </div>

</div>

</body>
</html>
