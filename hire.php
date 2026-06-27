<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

site_page(
    'hire',
    'Hire Me',
    'IT consulting — Oracle, VMware, cloud, cybersecurity, and smart campus advisory.',
    'hire.php',
    function () {
        site_page_intro(
            'Consulting',
            'Hire Me — IT Consulting',
            'Executive advisory and hands-on support for enterprise infrastructure, security, and digital transformation.'
        );
        ?>
<section class="section-block consulting-section reveal theme-amber">
  <div class="consulting-intro section-panel">
    <p>I help universities, enterprises, and IT teams with Oracle, VMware, cloud, cybersecurity, and smart campus initiatives. Every engagement starts with a clear scope and a written proposal — no hidden fees.</p>
    <ul class="consulting-benefits">
      <li>18+ years leading large-scale campus and enterprise IT</li>
      <li>Oracle 19c, Data Guard, VMware, Linux, and AWS experience</li>
      <li>Practical delivery — not slide decks only</li>
    </ul>
  </div>
  <div class="consulting-grid reveal-stagger">
    <article class="consulting-card reveal-child">
      <span class="consulting-tag">Introductory</span>
      <h3>Discovery Call</h3>
      <p class="consulting-price">Free · 30 minutes</p>
      <p>Discuss your goals, current environment, and whether I am the right fit for your project.</p>
      <ul>
        <li>Video or phone call</li>
        <li>High-level recommendations</li>
        <li>No obligation</li>
      </ul>
      <a class="consulting-cta" href="contact.php?service=Discovery+Call" data-service="Discovery Call">Request free call</a>
    </article>
    <article class="consulting-card consulting-card-featured reveal-child">
      <span class="consulting-tag">Popular</span>
      <h3>Advisory Session</h3>
      <p class="consulting-price">Custom quote · per engagement</p>
      <p>Focused consulting on a specific challenge — architecture review, migration planning, or security assessment.</p>
      <ul>
        <li>Oracle / VMware / cloud strategy</li>
        <li>Written summary after session</li>
        <li>Follow-up email support (7 days)</li>
      </ul>
      <a class="consulting-cta" href="contact.php?service=Advisory+Session" data-service="Advisory Session">Get a quote</a>
    </article>
    <article class="consulting-card reveal-child">
      <span class="consulting-tag">Enterprise</span>
      <h3>Project Consulting</h3>
      <p class="consulting-price">Proposal after scope review</p>
      <p>End-to-end support for migrations, smart campus builds, CBT platforms, or multi-month infrastructure programs.</p>
      <ul>
        <li>Scope document and timeline</li>
        <li>On-site or remote delivery</li>
        <li>Milestone-based billing</li>
      </ul>
      <a class="consulting-cta" href="contact.php?service=Project+Consulting" data-service="Project Consulting">Discuss your project</a>
    </article>
  </div>
  <p class="consulting-note">Payment is arranged by invoice or bank transfer after you approve the proposal. The website contact form does not charge cards — it sends your request to me directly.</p>
</section>
        <?php
    }
);
