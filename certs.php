<?php
declare(strict_types=1);
require __DIR__ . '/inc/public-site.php';

site_page(
    'certs',
    'Certifications',
    'Professional certifications across database, cloud, and networking.',
    'certs.php',
    function () {
        site_page_intro(
            'Credentials',
            'Certifications',
            'Professional credentials across database, cloud, and networking. Certificate documents are kept private — only summary details are shown here.'
        );
        ?>
<section class="section-block reveal theme-violet">
  <div class="skills-grid reveal-stagger" id="certGrid"></div>
  <p id="certStatus" class="cert-status" role="status" aria-live="polite"></p>
</section>
        <?php
    }
);
