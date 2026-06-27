<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

$extraHead = '<link rel="stylesheet" href="css/career-page-v5.css">';

site_page(
    'career',
    'Career',
    'Electronic career matrix — 18+ years from lab operations to Deputy Director IT at GIFT University.',
    'career.php',
    function () {
        site_page_intro(
            'Career',
            'Digital Career Matrix',
            '18+ years of infrastructure evolution — from lab terminals to enterprise Oracle, VMware, AI, and smart campus leadership at GIFT University.'
        );
        ?>
<section class="career-stats section-block reveal">
  <div class="career-stats-grid reveal-stagger">
    <article class="career-stat-node reveal-child">
      <span class="career-stat-label">SYS.EXPERIENCE</span>
      <strong class="career-stat-value">18+</strong>
      <span class="career-stat-unit">Years in IT</span>
    </article>
    <article class="career-stat-node reveal-child">
      <span class="career-stat-label">ROLE.ELEVATION</span>
      <strong class="career-stat-value">6</strong>
      <span class="career-stat-unit">Career promotions</span>
    </article>
    <article class="career-stat-node reveal-child">
      <span class="career-stat-label">ORG.AFFILIATION</span>
      <strong class="career-stat-value">GIFT</strong>
      <span class="career-stat-unit">University · Gujranwala</span>
    </article>
    <article class="career-stat-node reveal-child">
      <span class="career-stat-label">INFRA.SCALE</span>
      <strong class="career-stat-value">120+</strong>
      <span class="career-stat-unit">Servers · 10K+ users</span>
    </article>
    <article class="career-stat-node reveal-child">
      <span class="career-stat-label">TENURE.GIFT</span>
      <strong class="career-stat-value">19</strong>
      <span class="career-stat-unit">Years · same organization</span>
    </article>
  </div>
</section>

<section class="career-matrix section-block reveal">
  <div class="section-header">
    <p class="section-eyebrow">System Log</p>
    <h2>Career Progression &amp; Tenure Timeline</h2>
    <p class="section-desc">Role upgrades, years served in each position, and cumulative experience at GIFT University — from lab operations in 2007 to deputy director leadership today.</p>
  </div>

  <div class="career-timeline-digital reveal-stagger">
    <article class="career-node reveal-child is-current" data-level="6">
      <div class="career-node-header">
        <span class="career-node-id">NODE-2025 · DDIT</span>
        <span class="career-node-status is-live">ACTIVE</span>
      </div>
      <div class="career-tenure-inline">
        <time datetime="2025">2025 — Present</time>
        <span class="career-tenure-badge is-live">~1.5 yrs in role</span>
      </div>
      <h3>Deputy Director IT</h3>
      <p class="career-org">GIFT University</p>
      <p class="career-desc">Strategic IT leadership driving digital transformation, smart campus platforms, AI integration, and enterprise-wide infrastructure governance.</p>
      <ul class="career-stack">
        <li>Oracle 19c &amp; Data Guard</li>
        <li>Smart Campus / AI</li>
        <li>Cyber Security</li>
        <li>Digital Transformation</li>
      </ul>
      <div class="career-node-tenure">
        <div class="career-node-tenure-head">
          <span>Role duration</span>
          <span class="career-tenure-cum">19 yrs @ GIFT cumulative</span>
        </div>
        <div class="career-tenure-bar-wrap"><div class="career-tenure-bar" style="--tenure-pct:8%"><span>~1.5 yrs</span></div></div>
      </div>
      <p class="career-meta">Scope: 120+ servers · 10,000+ users · 99% uptime focus</p>
    </article>

    <article class="career-node reveal-child" data-level="5">
      <div class="career-node-header">
        <span class="career-node-id">NODE-2022 · MGR</span>
        <span class="career-node-status">ARCHIVED</span>
      </div>
      <div class="career-tenure-inline">
        <time datetime="2022">2022 — 2025</time>
        <span class="career-tenure-badge">3 yrs in role</span>
      </div>
      <h3>Manager IT</h3>
      <p class="career-org">GIFT University</p>
      <p class="career-desc">Led infrastructure and IT service management — overseeing campus servers, virtualization clusters, and operational delivery teams.</p>
      <ul class="career-stack">
        <li>VMware ESXi</li>
        <li>Service Management</li>
        <li>Linux Infrastructure</li>
        <li>Nagios Monitoring</li>
      </ul>
      <div class="career-node-tenure">
        <div class="career-node-tenure-head">
          <span>Role duration</span>
          <span class="career-tenure-cum">18 yrs @ GIFT cumulative</span>
        </div>
        <div class="career-tenure-bar-wrap"><div class="career-tenure-bar" style="--tenure-pct:16%"><span>3 yrs</span></div></div>
      </div>
      <p class="career-meta">Scope: Multi-team IT operations · ERP &amp; campus systems</p>
    </article>

    <article class="career-node reveal-child" data-level="4">
      <div class="career-node-header">
        <span class="career-node-id">NODE-2021 · DMGR</span>
        <span class="career-node-status">ARCHIVED</span>
      </div>
      <div class="career-tenure-inline">
        <time datetime="2021">2021 — 2022</time>
        <span class="career-tenure-badge">1 yr in role</span>
      </div>
      <h3>Deputy Manager IT</h3>
      <p class="career-org">GIFT University</p>
      <p class="career-desc">Managed enterprise systems and day-to-day IT operations — coordinating server platforms, backups, and cross-department technical support.</p>
      <ul class="career-stack">
        <li>Enterprise Systems</li>
        <li>Backup &amp; DR</li>
        <li>Virtualization</li>
        <li>Windows / Linux</li>
      </ul>
      <div class="career-node-tenure">
        <div class="career-node-tenure-head">
          <span>Role duration</span>
          <span class="career-tenure-cum">15 yrs @ GIFT cumulative</span>
        </div>
        <div class="career-tenure-bar-wrap"><div class="career-tenure-bar" style="--tenure-pct:5%"><span>1 yr</span></div></div>
      </div>
      <p class="career-meta">Scope: Server farms · Academic ERP support</p>
    </article>

    <article class="career-node reveal-child" data-level="3">
      <div class="career-node-header">
        <span class="career-node-id">NODE-2017 · AMGR</span>
        <span class="career-node-status">ARCHIVED</span>
      </div>
      <div class="career-tenure-inline">
        <time datetime="2017">2017 — 2021</time>
        <span class="career-tenure-badge">4 yrs in role</span>
      </div>
      <h3>Assistant Manager IT</h3>
      <p class="career-org">GIFT University</p>
      <p class="career-desc">Campus network and server administration — expanded from pure networking into full-stack infrastructure ownership across labs and data centers.</p>
      <ul class="career-stack">
        <li>Campus LAN/WAN</li>
        <li>Server Admin</li>
        <li>Firewall / Security</li>
        <li>Apache / Squid</li>
      </ul>
      <div class="career-node-tenure">
        <div class="career-node-tenure-head">
          <span>Role duration</span>
          <span class="career-tenure-cum">14 yrs @ GIFT cumulative</span>
        </div>
        <div class="career-tenure-bar-wrap"><div class="career-tenure-bar" style="--tenure-pct:21%"><span>4 yrs</span></div></div>
      </div>
      <p class="career-meta">Scope: 500+ network devices · Server room ops</p>
    </article>

    <article class="career-node reveal-child" data-level="2">
      <div class="career-node-header">
        <span class="career-node-id">NODE-2009 · NET</span>
        <span class="career-node-status">ARCHIVED</span>
      </div>
      <div class="career-tenure-inline">
        <time datetime="2009">2009 — 2017</time>
        <span class="career-tenure-badge is-longest">8 yrs in role · longest</span>
      </div>
      <h3>Network Administrator</h3>
      <p class="career-org">GIFT University</p>
      <p class="career-desc">Built and maintained network infrastructure and security — routing, switching, access control, and uptime for academic connectivity.</p>
      <ul class="career-stack">
        <li>Routing &amp; Switching</li>
        <li>CCNA Training</li>
        <li>Network Security</li>
        <li>Wi-Fi / VLAN</li>
      </ul>
      <div class="career-node-tenure">
        <div class="career-node-tenure-head">
          <span>Role duration</span>
          <span class="career-tenure-cum">10 yrs @ GIFT cumulative</span>
        </div>
        <div class="career-tenure-bar-wrap"><div class="career-tenure-bar" style="--tenure-pct:42%"><span>8 yrs</span></div></div>
      </div>
      <p class="career-meta">Scope: Campus backbone · Security hardening</p>
    </article>

    <article class="career-node reveal-child" data-level="1">
      <div class="career-node-header">
        <span class="career-node-id">NODE-2007 · LAB</span>
        <span class="career-node-status">ORIGIN</span>
      </div>
      <div class="career-tenure-inline">
        <time datetime="2007">2007 — 2009</time>
        <span class="career-tenure-badge">2 yrs in role</span>
      </div>
      <h3>Lab Administrator</h3>
      <p class="career-org">GIFT University</p>
      <p class="career-desc">IT lab operations and end-user support — foundation role managing computer labs, software deployment, and student/staff technical assistance.</p>
      <ul class="career-stack">
        <li>PC Labs</li>
        <li>Help Desk</li>
        <li>Software Imaging</li>
        <li>User Support</li>
      </ul>
      <div class="career-node-tenure">
        <div class="career-node-tenure-head">
          <span>Role duration</span>
          <span class="career-tenure-cum">2 yrs @ GIFT · career start</span>
        </div>
        <div class="career-tenure-bar-wrap"><div class="career-tenure-bar" style="--tenure-pct:11%"><span>2 yrs</span></div></div>
      </div>
      <p class="career-meta">Scope: Lab fleet · First-line IT support</p>
    </article>
  </div>

  <p class="career-tenure-footnote">Total continuous tenure at GIFT University: <strong>19 years</strong> (2007 — 2026) · 6 role elevations · longest single role: Network Administrator (8 years)</p>
</section>

<section class="career-evolution section-block reveal">
  <div class="section-header">
    <p class="section-eyebrow">Tech Stack Evolution</p>
    <h2>Infrastructure Depth Over Time</h2>
    <p class="section-desc">Technology scope expanded from lab support to full enterprise stack — Oracle, VMware, cloud, AI, and cybersecurity.</p>
  </div>
  <div class="career-evolution-grid reveal-stagger">
    <div class="career-evolution-lane reveal-child">
      <span class="career-lane-label">2007–2009</span>
      <div class="career-lane-track">
        <span>PC Labs</span><span>Help Desk</span><span>Basic Networking</span>
      </div>
    </div>
    <div class="career-evolution-lane reveal-child">
      <span class="career-lane-label">2009–2017</span>
      <div class="career-lane-track">
        <span>LAN/WAN</span><span>Firewalls</span><span>Server Admin</span><span>Monitoring</span>
      </div>
    </div>
    <div class="career-evolution-lane reveal-child">
      <span class="career-lane-label">2017–2022</span>
      <div class="career-lane-track">
        <span>VMware</span><span>Linux</span><span>ERP Systems</span><span>Virtualization</span>
      </div>
    </div>
    <div class="career-evolution-lane reveal-child">
      <span class="career-lane-label">2022–2025</span>
      <div class="career-lane-track">
        <span>Oracle 19c</span><span>Data Guard</span><span>AWS Cloud</span><span>Service Mgmt</span>
      </div>
    </div>
    <div class="career-evolution-lane reveal-child is-highlight">
      <span class="career-lane-label">2025+</span>
      <div class="career-lane-track">
        <span>AI / Face Recognition</span><span>Smart Campus</span><span>Cyber Security</span><span>Digital Transform</span>
      </div>
    </div>
  </div>
</section>

<section class="career-terminal section-block reveal">
  <div class="career-terminal-panel">
    <div class="career-terminal-bar">
      <span></span><span></span><span></span>
      <code>career@gift.edu.pk — session log</code>
    </div>
    <pre class="career-terminal-body"><span class="t-prompt">$</span> cat /var/log/career/timeline.log
[2007] INIT     Lab Administrator        — 2 yrs   · cumulative 2 yrs @ GIFT
[2009] UPGRADE  Network Administrator    — 8 yrs   · cumulative 10 yrs @ GIFT  ★ longest tenure
[2017] UPGRADE  Assistant Manager IT     — 4 yrs   · cumulative 14 yrs @ GIFT
[2021] UPGRADE  Deputy Manager IT        — 1 yr    · cumulative 15 yrs @ GIFT
[2022] UPGRADE  Manager IT               — 3 yrs   · cumulative 18 yrs @ GIFT
[2025] ACTIVE   Deputy Director IT       — ~1.5 yrs · cumulative 19 yrs @ GIFT
<span class="t-prompt">$</span> uptime --experience
19 years continuous @ GIFT University (2007–2026) | 6 role elevations | Oracle · VMware · Linux · AI · Cyber Security
<span class="t-cursor" aria-hidden="true">▋</span></pre>
  </div>
</section>
        <?php
    },
    $extraHead
);
