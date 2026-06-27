<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

site_page(
    'about',
    'About',
    'Executive IT leadership — 18+ years driving infrastructure, security, and digital transformation.',
    'about.php',
    function () {
        site_page_intro(
            'About',
            'Executive IT Leadership',
            '18+ years driving infrastructure, security, and digital transformation for large academic environments.'
        );
        ?>
<section class="section-block reveal">
  <div class="bento-grid reveal-stagger">
    <div class="bento-about section-panel reveal-child">
      <p>Experienced in enterprise Oracle databases, VMware virtualization, smart campus platforms, and AI-enabled IT services.</p>
      <div class="about-highlights">
        <div class="about-item"><strong>18+ Years</strong> IT infrastructure leadership</div>
        <div class="about-item"><strong>120+ Servers</strong> Managed across campus systems</div>
        <div class="about-item"><strong>10,000+ Users</strong> Students and staff supported</div>
        <div class="about-item"><strong>99% Uptime</strong> Service reliability focus</div>
      </div>
    </div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="120">0</div><p>Servers Managed</p></div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="10000">0</div><p>Students Served</p></div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="500">0</div><p>Network Devices</p></div>
    <div class="kpi-card reveal-child"><div class="kpi-number" data-target="99">0</div><p>Uptime %</p></div>
  </div>
</section>
        <?php
    }
);
