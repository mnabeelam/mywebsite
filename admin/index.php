<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/lib/bootstrap.php';
require_once __DIR__ . '/../php/lib/auth.php';
require_once __DIR__ . '/../php/lib/site-services.php';

requireAdminIpAllowed();

if (isAdminAuthenticated()) {
    redirectTo('dashboard.php');
}

$error = sanitizeText($_GET['error'] ?? '', 300);
$step = sanitizeText($_GET['step'] ?? '', 20);
$configured = adminConfigured();
$pending2fa = pendingAdmin2faUser() !== null;
$show2fa = $step === '2fa' || $pending2fa;
$loginUsernameHint = trim(configValue('ADMIN_USERNAME'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../php/lib/rate-limit.php';
    requireCsrfFromRequest();
    rateLimit('admin_login', 8, 900);

    $action = sanitizeText($_POST['login_action'] ?? 'password', 20);

    if ($action === '2fa') {
        $otp = sanitizeText($_POST['otp_code'] ?? '', 12);
        if ($otp === '') {
            $error = 'Enter the 6-digit authentication code.';
            $show2fa = true;
        } else {
            completeAdminLoginWith2fa($otp);
        }
    } elseif (!$configured) {
        $error = 'Admin login is not configured. Create config/local.php from config/local.example.php.';
    } else {
        $username = sanitizeText($_POST['username'] ?? '', 100);
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } elseif (!verifyAdminCredentials($username, $password)) {
            logSecurityEvent('failed login for user "' . $username . '" from ' . clientIp());
            $error = 'Invalid username or password. If you changed the password in the dashboard, use the new one. Run scripts/reset-admin-login.php locally if needed.';
        } else {
            completeAdminLoginOrRequire2fa($username);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login | Portfolio</title>
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="admin.css">
<script src="login-security.js"></script>
</head>
<body class="admin-login">
<main class="admin-shell">
  <section class="admin-card">
    <h1>Admin Login</h1>
    <p class="admin-note">Upload CV and manage knowledge base. Not linked from the public homepage.</p>

    <?php if (!$configured): ?>
      <p class="message error">Admin is not configured yet. Copy <code>config/local.example.php</code> to <code>config/local.php</code> and set your username/password.</p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <p class="message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <?php if ($show2fa): ?>
    <form method="post" action="index.php?step=2fa" autocomplete="one-time-code">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="login_action" value="2fa">
      <p class="admin-note">Two-factor authentication is enabled. Open Google Authenticator (or similar) and enter the current 6-digit code.</p>
      <label for="otpCode">Authentication code</label>
      <input id="otpCode" name="otp_code" type="text" inputmode="numeric" pattern="[0-9]{6,8}" autocomplete="one-time-code" maxlength="8" required>

      <button type="submit">Verify code</button>
    </form>
    <p class="admin-note"><a href="index.php">Back to username and password</a></p>
    <?php else: ?>
    <form method="post" action="index.php" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="login_action" value="password">
      <?php if ($configured && $loginUsernameHint !== ''): ?>
      <p class="admin-note">Configured username: <strong><?php echo htmlspecialchars($loginUsernameHint, ENT_QUOTES, 'UTF-8'); ?></strong> (set in config/local.php)</p>
      <?php endif; ?>
      <label for="adminUser">Username</label>
      <input id="adminUser" name="username" type="text" autocomplete="username" required value="<?php echo htmlspecialchars($loginUsernameHint, ENT_QUOTES, 'UTF-8'); ?>">

      <label for="adminPass">Password</label>
      <input id="adminPass" name="password" type="password" autocomplete="current-password" required>

      <button type="submit">Login</button>
    </form>
    <?php endif; ?>

    <p class="admin-note"><a href="../index.php">Back to homepage</a></p>
  </section>
</main>
</body>
</html>
