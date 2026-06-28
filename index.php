<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';
require __DIR__ . '/inc/site-content.php';

$featuredProjects = siteFeaturedProjects(4);

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
    function () use ($featuredProjects) {
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

<section class="section-block reveal theme-cyan">
  <div class="section-header">
    <p class="section-eyebrow">Executive IT Dashboard</p>
    <h2>Infrastructure at a Glance</h2>
    <p class="section-desc">Key metrics from 18+ years leading campus and enterprise IT at GIFT University.</p>
  </div>
  <div class="bento-grid reveal-stagger">
    <div class="bento-about section-panel reveal-child">
      <p>Enterprise Oracle, VMware, Linux, AI, and smart campus platforms — built for reliability at scale.</p>
      <div class="about-highlights">
        <div class="about-item"><strong>18+ Years</strong> IT infrastructure leadership</div>
        <div class="about-item"><strong>120+ Servers</strong> Managed across campus systems</div>
        <div class="about-item"><strong>10,000+ Users</strong> Students and staff supported</div>
        <div class="about-item"><strong>99% Uptime</strong> Service reliability focus</div>
      </div>
      <p class="section-cta"><a href="about.php">Full leadership profile</a></p>
    </div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="120">0</div><p>Servers Managed</p></div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="10000">0</div><p>Students Served</p></div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="500">0</div><p>Network Devices</p></div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="99">0</div><p>Uptime %</p></div>
  </div>
</section>

<?php if ($featuredProjects !== []): ?>
<section class="section-block reveal theme-violet">
  <div class="section-header">
    <p class="section-eyebrow">Portfolio</p>
    <h2>Featured Projects</h2>
    <p class="section-desc">Enterprise infrastructure and smart campus initiatives — full portfolio on the Projects page.</p>
  </div>
  <div class="project-grid reveal-stagger">
    <?php foreach ($featuredProjects as $project): ?>
      <?php
        $name = trim((string) ($project['name'] ?? ''));
        $tag = trim((string) ($project['tag'] ?? 'Project'));
        $details = trim((string) ($project['details'] ?? ''));
        if ($name === '') {
            continue;
        }
      ?>
    <article class="project-card reveal-child">
      <span class="project-tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
      <h3><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h3>
      <?php if ($details !== ''): ?>
      <p class="tech"><?php echo htmlspecialchars(mb_strimwidth($details, 0, 160, '…'), ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="section-cta reveal"><a href="projects.php">View all projects</a></p>
</section>
<?php endif; ?>

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
