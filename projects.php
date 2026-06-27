<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

$projectsPath = __DIR__ . '/knowledge/projects.json';
$projects = [];
if (is_readable($projectsPath)) {
    $decoded = json_decode((string) file_get_contents($projectsPath), true);
    if (is_array($decoded)) {
        $projects = $decoded;
    }
}

site_page(
    'projects',
    'Projects',
    'Featured enterprise infrastructure and smart campus projects — Oracle, AI, Moodle CBT, DSpace.',
    'projects.php',
    function () use ($projects) {
        site_page_intro(
            'Portfolio',
            'Featured Projects',
            'Enterprise infrastructure and smart campus initiatives.'
        );
        ?>
<section class="project-showcase section-block reveal theme-violet">
  <div class="project-grid reveal-stagger">
    <?php foreach ($projects as $project): ?>
      <?php
        if (!is_array($project)) {
            continue;
        }
        $name = trim((string) ($project['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $tag = trim((string) ($project['tag'] ?? 'Project'));
        $details = trim((string) ($project['details'] ?? ''));
      ?>
    <article class="project-card reveal-child">
      <span class="project-tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
      <h3><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h3>
      <?php if ($details !== ''): ?>
      <p class="tech"><?php echo htmlspecialchars($details, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="real-earth section-block reveal">
  <div class="section-header">
    <p class="section-eyebrow">Immersive</p>
    <h2>Global Technology Leadership</h2>
    <p class="section-desc">Building Smart Campus, AI, Cloud and Enterprise Infrastructure Solutions.</p>
  </div>
  <div class="earth-banner">
    <div class="earth-showcase" aria-hidden="true"></div>
    <div class="earth-banner-copy">
      <p>Explore an interactive 3D demo showcasing infrastructure, earth visualization, and campus technology concepts.</p>
      <p class="section-cta"><a href="pages/demo.html">Open immersive 3D demo</a></p>
    </div>
  </div>
</section>
        <?php
    }
);
