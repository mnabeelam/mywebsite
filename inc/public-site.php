<?php
declare(strict_types=1);

function siteShowAdminLink(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '' || $host === '127.0.0.1' || str_starts_with($host, 'localhost')) {
        return true;
    }

    return $host === 'mywebsite.local';
}

function site_page(string $active, string $title, string $description, string $canonicalPath, callable $content, string $extraHead = ''): void
{
    $showAdminLink = siteShowAdminLink();
    $canonical = 'https://it.gift.edu.pk/' . ltrim($canonicalPath, '/');
    $fullTitle = $title . ' | Mirza Nabeel Ahmed';

    $navItems = [
        'about' => ['href' => 'about.php', 'label' => 'About'],
        'projects' => ['href' => 'projects.php', 'label' => 'Projects'],
        'career' => ['href' => 'career.php', 'label' => 'Career'],
        'certs' => ['href' => 'certs.php', 'label' => 'Certs'],
        'hire' => ['href' => 'hire.php', 'label' => 'Hire Me'],
        'shop' => ['href' => 'shop.php', 'label' => 'IT Shop'],
        'contact' => ['href' => 'contact.php', 'label' => 'Contact'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="theme-color" content="#070b14">
<meta property="og:title" content="<?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image" content="https://it.gift.edu.pk/assets/og-image.svg">
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">
<title><?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="assets/icon-192.png">
<link rel="manifest" href="pwa/manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/homepage-v5.css">
<link rel="stylesheet" href="css/homepage-components-v5.css">
<link rel="stylesheet" href="css/homepage-themes-v5.css">
<link rel="stylesheet" href="css/mobile-v5.css">
<?php echo $extraHead; ?>
</head>
<body class="page-<?php echo htmlspecialchars($active, ENT_QUOTES, 'UTF-8'); ?>">

<a class="skip-link" href="#main-content">Skip to content</a>
<noscript><style>.reveal, .reveal-child { opacity: 1; transform: none; }</style></noscript>

<div class="site-bg" aria-hidden="true">
  <span class="site-orb site-orb-1"></span>
  <span class="site-orb site-orb-2"></span>
  <span class="site-orb site-orb-3"></span>
</div>

<div id="progressBar" aria-hidden="true"></div>

<nav class="site-nav" aria-label="Primary">
  <a class="nav-brand" href="index.php">Mirza Nabeel Ahmed</a>
  <button type="button" id="navToggle" class="nav-toggle" aria-expanded="false" aria-controls="navLinks">Menu</button>
  <div class="nav-links" id="navLinks">
    <?php foreach ($navItems as $key => $item): ?>
      <?php
        $isActive = $key === $active;
        $aria = $isActive ? ' aria-current="page"' : '';
      ?>
      <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $isActive ? 'is-active' : ''; ?>"<?php echo $aria; ?>><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endforeach; ?>
    <a href="pages/demo.html">3D Demo</a>
    <?php if ($showAdminLink): ?>
    <a href="admin/index.php" class="nav-admin-link">Admin</a>
    <?php endif; ?>
  </div>
</nav>

<main id="main-content">
<?php $content(); ?>
</main>

<footer class="executive-footer">
  <div class="footer-brand">Mirza Nabeel Ahmed</div>
  <p>Deputy Director IT | Oracle | VMware | Cyber Security | AI</p>
  <div class="footer-links">
    <a href="about.php">About</a>
    <a href="projects.php">Projects</a>
    <a href="shop.php">IT Shop</a>
    <a href="contact.php">Contact</a>
    <?php if ($showAdminLink): ?>
    <a href="admin/index.php">Admin</a>
    <?php endif; ?>
    <a href="pages/demo.html">3D Demo</a>
  </div>
  <p>© <?php echo date('Y'); ?> All Rights Reserved</p>
</footer>

<button id="backTop" type="button" aria-label="Back to top">↑</button>

<div id="visitor-counter" class="visitor-counter" aria-live="polite">
  <span class="visitor-label">Visitors</span>
  <strong id="visitor-total">—</strong>
  <span>Today: <strong id="visitor-today">—</strong></span>
  <span>Views: <strong id="visitor-views">—</strong></span>
</div>

<div id="ai-widget" class="ai-widget" aria-label="Quick Assistant">
  <button type="button" id="aiFab" class="ai-fab" aria-expanded="false" aria-controls="aiPanel">AI</button>
  <div id="aiPanel" class="ai-panel" hidden>
    <h3>Quick Assistant</h3>
    <div id="ai-answer" role="status" aria-live="polite">Ask about career, certifications, IT shop products, prices, or contact.</div>
    <label class="visually-hidden" for="ai-input">Ask about experience, shop products, or contact</label>
    <input id="ai-input" type="text" placeholder="e.g. What IT gadgets are in the shop?" autocomplete="off">
    <button id="ai-submit" type="button">Ask</button>
  </div>
</div>

<script src="js/site-protection.js"></script>
<script src="js/homepage.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('pwa/service-worker.js').catch(function () {});
}
</script>
</body>
</html>
    <?php
}

function site_page_intro(string $eyebrow, string $title, string $desc): void
{
    ?>
<section class="page-intro reveal page-intro-animated">
  <p class="section-eyebrow"><?php echo htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
  <h1 class="page-intro-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
  <p class="section-desc"><?php echo htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'); ?></p>
</section>
    <?php
}
