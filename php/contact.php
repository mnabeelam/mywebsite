<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/site-settings.php';
require_once __DIR__ . '/lib/site-services.php';

requirePost();
requireSameOrigin();
rateLimit('contact_form', 8, 3600);

$name = sanitizeText($_POST['name'] ?? '', 120);
$email = sanitizeText($_POST['email'] ?? '', 180);
$service = sanitizeText($_POST['service'] ?? 'General Inquiry', 120);
$message = sanitizeText($_POST['message'] ?? '', 3000);

if ($name === '' || $email === '' || $message === '') {
    jsonResponse(['error' => 'Name, email, and message are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Please enter a valid email address.'], 400);
}

try {
    $saved = saveContactMessage($name, $email, $message, $service);
    $emailSent = sendAdminNotification(
        'Portfolio contact: ' . $service . ' — ' . $name,
        "Service: {$service}\nName: {$name}\nEmail: {$email}\n\n{$message}",
        $email
    );

    $response = [
        'status' => 'ok',
        'message' => $emailSent
            ? 'Thank you. Your message was sent successfully.'
            : 'Thank you. Your message was saved — email notification may be delayed on this server.',
        'id' => $saved,
        'email_notified' => $emailSent,
    ];

    jsonResponse($response);
} catch (Throwable $e) {
    appLog('contact-form: ' . $e->getMessage());
    jsonResponse(['error' => 'Could not send your message. Please try again later.'], 500);
}
