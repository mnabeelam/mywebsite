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
$configured = adminConfigured();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../php/lib/rate-limit.php';
    require_once __DIR__ . '/../php/lib/site-services.php';
    requireCsrfFromRequest();
    rateLimit('admin_login', 8, 900);

    if (!$configured) {
        $error = 'Admin login is not configured. Create config/local.php from config/local.example.php.';
    } else {
        $username = sanitizeText($_POST['username'] ?? '', 100);
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } elseif (!verifyAdminCredentials($username, $password)) {
            logSecurityEvent('failed login for user "' . $username . '" from ' . clientIp());
            $error = 'Invalid username or password.';
        } else {
            loginSuccessResponse($username);
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

    <form method="post" action="index.php" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
      <label for="adminUser">Username</label>
      <input id="adminUser" name="username" type="text" autocomplete="username" required>

      <label for="adminPass">Password</label>
      <input id="adminPass" name="password" type="password" autocomplete="current-password" required>

      <button type="submit">Login</button>
    </form>

    <p class="admin-note"><a href="../index.php">Back to homepage</a></p>
  </section>
</main>
</body>
</html>
