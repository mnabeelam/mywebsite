<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

$extraHead = <<<'HTML'
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Mirza Nabeel Ahmed",
  "jobTitle": "Deputy Director IT",
  "worksFor": { "@type": "Organization", "name": "GIFT University" },
  "email": "mailto:mnabeelam@gmail.com",
  "url": "https://it.gift.edu.pk",
  "sameAs": [
    "https://www.linkedin.com/in/nabeel-a-1370b4358",
    "https://github.com/mnabeelam"
  ]
}
</script>
HTML;

site_page(
    'home',
    'Deputy Director IT',
    'Mirza Nabeel Ahmed — Deputy Director IT. Oracle, VMware, AI, Cyber Security, Smart Campus.',
    'index.php',
    function () {
        ?>
<section class="hero-v5 reveal" aria-label="Introduction">
  <div class="hero-v5-grid">
    <div class="hero-v5-copy">
      <p class="section-eyebrow">Deputy Director IT · GIFT University</p>
      <h1>Technology Leadership &amp; Innovation</h1>
      <p class="hero-v5-lead">Oracle · VMware · Linux · AI · Smart Campus · Cybersecurity</p>
      <div class="hero-actions">
        <a href="hire.php" class="hero-btn hero-btn-primary">Hire Me</a>
        <a href="projects.php" class="hero-btn hero-btn-secondary">View Projects</a>
      </div>
      <div class="hero-trust-pills">
        <span>18+ years experience</span>
        <span>120+ servers managed</span>
        <span>99% uptime focus</span>
      </div>
    </div>
    <div class="hero-profile-card" id="profile">
      <div class="profile-photo">
        <img id="heroProfileImg" src="assets/profile.png" width="140" height="140" alt="Mirza Nabeel Ahmed" loading="eager" decoding="async">
      </div>
      <h2>Mirza Nabeel Ahmed</h2>
      <p class="profile-role">Deputy Director IT — GIFT University</p>
      <p>AI · Cyber Security · Virtualization · Digital Transformation</p>
    </div>
  </div>
</section>

<section class="section-block reveal home-explore">
  <div class="section-header">
    <p class="section-eyebrow">Explore</p>
    <h2>Portfolio Sections</h2>
    <p class="section-desc">Each topic has its own page — use the navigation above or jump in below.</p>
  </div>
  <div class="home-link-grid reveal-stagger">
    <a class="home-link-card reveal-child" data-accent="about" href="about.php"><span>About</span><small>Leadership &amp; KPIs</small></a>
    <a class="home-link-card reveal-child" data-accent="projects" href="projects.php"><span>Projects</span><small>Enterprise portfolio</small></a>
    <a class="home-link-card reveal-child" data-accent="career" href="career.php"><span>Career</span><small>Timeline &amp; roles</small></a>
    <a class="home-link-card reveal-child" data-accent="certs" href="certs.php"><span>Certs</span><small>Credentials</small></a>
    <a class="home-link-card reveal-child" data-accent="hire" href="hire.php"><span>Hire Me</span><small>Consulting services</small></a>
    <a class="home-link-card reveal-child" data-accent="shop" href="shop.php"><span>IT Shop</span><small>Gadgets &amp; gear</small></a>
    <a class="home-link-card reveal-child" data-accent="contact" href="contact.php"><span>Contact</span><small>Request a quote</small></a>
    <a class="home-link-card reveal-child" data-accent="demo" href="pages/demo.html"><span>3D Demo</span><small>Interactive showcase</small></a>
  </div>
</section>
        <?php
    },
    $extraHead
);
