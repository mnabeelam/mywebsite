<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/site-services.php';

requirePost();
requireSameOrigin();
rateLimit('visitor_track', 120, 3600);

jsonResponse([
    'status' => 'ok',
    'visitors' => trackVisit(),
]);
