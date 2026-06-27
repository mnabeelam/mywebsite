<?php
declare(strict_types=1);

require_once __DIR__ . '/pdf-text.php';

function knownCertIssuers(): array
{
    return [
        'Oracle' => ['oracle'],
        'Amazon Web Services' => ['amazon web services', ' aws ', 'aws certified'],
        'Cisco' => ['cisco', 'ccna', 'ccnp', 'ccie'],
        'Microsoft' => ['microsoft', ' azure ', 'microsoft certified'],
        'Google' => ['google cloud', 'google certified'],
        'VMware' => ['vmware'],
        'CompTIA' => ['comptia'],
        'Red Hat' => ['red hat'],
        'EC-Council' => ['ec-council', 'ceh'],
        'PMI' => ['project management institute', ' pmp '],
    ];
}

function certBoilerplatePatterns(): array
{
    return [
        '/^certificate of\b/i',
        '/^this (is to )?certif/i',
        '/^presented to\b/i',
        '/^awarded to\b/i',
        '/^successfully completed\b/i',
        '/^has successfully\b/i',
        '/^in recognition of\b/i',
        '/^verify (at|this certificate)\b/i',
        '/^credential id\b/i',
        '/^validation (code|number)\b/i',
        '/^www\./i',
        '/^http/i',
    ];
}

function isCertBoilerplateLine(string $line): bool
{
    $line = trim($line);
    if ($line === '' || strlen($line) < 4) {
        return true;
    }

    foreach (certBoilerplatePatterns() as $pattern) {
        if (preg_match($pattern, $line)) {
            return true;
        }
    }

    if (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i', $line)) {
        return true;
    }

    if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/', $line)) {
        return true;
    }

    return false;
}

function parseCertFilenameHints(string $filename): array
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $base = trim(preg_replace('/[_\-]+/', ' ', $base) ?? $base);
    $base = trim(preg_replace('/\s+/', ' ', $base) ?? $base);

    $year = '';
    if (preg_match('/\b((?:19|20)\d{2})\b/', $base, $match)) {
        $year = $match[1];
        $base = trim(str_replace($year, '', $base));
    }

    $title = sanitizeText($base, 200);
    $issuer = detectCertIssuer($title);

    return [
        'title' => $title,
        'issuer' => $issuer,
        'year' => $year,
        'description' => '',
    ];
}

function detectCertIssuer(string $text): string
{
    $haystack = ' ' . strtolower($text) . ' ';

    foreach (knownCertIssuers() as $label => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return $label;
            }
        }
    }

    if (preg_match('/(?:issued by|presented by|awarded by|from)\s+([A-Z][A-Za-z0-9&.,\-\s]{2,80})/i', $text, $match)) {
        return sanitizeText(trim($match[1]), 120);
    }

    return '';
}

function extractCertYear(string $text): string
{
    $patterns = [
        '/(?:issued|issue date|date issued|completed|earned|awarded|valid from|granted).{0,40}\b((?:19|20)\d{2})\b/i',
        '/\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+((?:19|20)\d{2})\b/i',
        '/\b((?:19|20)\d{2})\b/',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match)) {
            return $match[1];
        }
    }

    return '';
}

function scoreCertTitleLine(string $line): int
{
    if (isCertBoilerplateLine($line)) {
        return -100;
    }

    $length = strlen($line);
    if ($length < 8 || $length > 180) {
        return -50;
    }

    $score = 0;
    $lower = strtolower($line);
    $keywords = [
        'certified' => 12,
        'certification' => 10,
        'certificate' => 8,
        'professional' => 8,
        'administrator' => 7,
        'architect' => 7,
        'specialist' => 7,
        'associate' => 6,
        'foundations' => 6,
        'training' => 5,
        'oracle' => 8,
        'aws' => 8,
        'cisco' => 8,
        'ccna' => 9,
        'microsoft' => 7,
        'cloud' => 5,
        'database' => 5,
    ];

    foreach ($keywords as $word => $points) {
        if (str_contains($lower, $word)) {
            $score += $points;
        }
    }

    if (preg_match('/\b\d{2,4}[a-z]?\b/i', $line)) {
        $score += 3;
    }

    if (preg_match('/^[A-Z0-9][A-Za-z0-9\s&.,:\-]+$/', $line)) {
        $score += 2;
    }

    return $score;
}

function extractCertTitleFromFullText(string $text): string
{
    $patterns = [
        '/\b((?:Oracle|AWS|Amazon Web Services|Cisco|Microsoft|Google|VMware|CompTIA|Red Hat)[^\n\r]{0,40}(?:Certified|certification|Professional|Associate|Administrator|Architect|Specialist|Foundations|Expert)[^\n\r]{0,80})/i',
        '/\b((?:Certified|Certificate of)[^\n\r]{10,160})/i',
        '/\b(CCNA|CCNP|CCIE|OCP|OCA|MCSE|MCSA|PMP|CEH)[^\n\r]{0,80}/i',
        '/\b([A-Z][A-Za-z0-9&.,:\-\s]{10,120}(?:Administration|Architecture|Foundations|Training))\b/',
    ];

    foreach ($patterns as $pattern) {
        if (!preg_match($pattern, $text, $match)) {
            continue;
        }
        $candidate = trim(preg_replace('/\s+/', ' ', $match[1]) ?? $match[1]);
        if ($candidate !== '' && !isCertBoilerplateLine($candidate)) {
            return sanitizeText($candidate, 200);
        }
    }

    return '';
}

function extractCertTitle(string $text): string
{
    $fromBody = extractCertTitleFromFullText($text);
    if ($fromBody !== '') {
        return $fromBody;
    }

    $lines = array_values(array_filter(array_map('trim', preg_split('/\n+/', $text) ?: [])));
    $bestLine = '';
    $bestScore = 0;

    foreach ($lines as $line) {
        $score = scoreCertTitleLine($line);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestLine = $line;
        }
    }

    if ($bestScore >= 8) {
        return sanitizeText($bestLine, 200);
    }

    foreach ($lines as $line) {
        if (!isCertBoilerplateLine($line) && strlen($line) >= 10 && strlen($line) <= 120) {
            return sanitizeText($line, 200);
        }
    }

    return '';
}

function extractCertDescription(string $text, string $title): string
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\n+/', $text) ?: [])));
    $parts = [];

    foreach ($lines as $line) {
        if ($line === $title || isCertBoilerplateLine($line)) {
            continue;
        }

        if (preg_match('/\b(credential|validation|certificate id|license|badge|exam|demonstrated|skills|competenc)/i', $line)) {
            $parts[] = $line;
            continue;
        }

        if (strlen($line) >= 20 && strlen($line) <= 220 && count($parts) < 3) {
            $parts[] = $line;
        }
    }

    $description = sanitizeText(implode(' ', $parts), 1000);

    if ($description === '' && $title !== '') {
        $description = sanitizeText('Professional certification: ' . $title . '.', 1000);
    }

    return $description;
}

function parseCertDocumentText(string $text, string $originalFilename = ''): array
{
    $text = normalizeExtractedText($text);
    $filenameHints = parseCertFilenameHints($originalFilename);

    if ($text === '') {
        return array_merge($filenameHints, [
            'source' => 'filename',
        ]);
    }

    $title = extractCertTitle($text);
    $issuer = detectCertIssuer($text);
    if ($issuer === '' && $title !== '') {
        $issuer = detectCertIssuer($title);
    }
    $year = extractCertYear($text);
    $description = extractCertDescription($text, $title);

    if ($title === '') {
        $title = $filenameHints['title'];
    }
    if ($issuer === '') {
        $issuer = $filenameHints['issuer'];
    }
    if ($year === '') {
        $year = $filenameHints['year'];
    }
    if ($description === '' || $description === 'Professional certification: .') {
        $description = $filenameHints['description'] !== ''
            ? $filenameHints['description']
            : extractCertDescription($text, $title);
    }

    return [
        'title' => $title,
        'issuer' => $issuer,
        'year' => $year,
        'description' => $description,
        'source' => $filenameHints['title'] !== '' && $title === $filenameHints['title'] ? 'mixed' : 'pdf_text',
    ];
}

function extractImageCertHints(string $file, string $originalFilename): array
{
    $hints = parseCertFilenameHints($originalFilename);

    if (function_exists('exif_read_data') && is_readable($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            $exif = @exif_read_data($file, 'ANY_TAG', true);
            if (is_array($exif)) {
                $candidates = [];
                foreach (['ImageDescription', 'DocumentName', 'Title', 'Comments'] as $key) {
                    if (!empty($exif['IFD0'][$key])) {
                        $candidates[] = (string) $exif['IFD0'][$key];
                    }
                    if (!empty($exif['COMPUTED'][$key])) {
                        $candidates[] = (string) $exif['COMPUTED'][$key];
                    }
                }

                $joined = normalizeExtractedText(implode("\n", $candidates));
                if ($joined !== '') {
                    $parsed = parseCertDocumentText($joined, $originalFilename);
                    if (($parsed['title'] ?? '') !== '') {
                        return array_merge($parsed, ['source' => 'image_metadata']);
                    }
                }
            }
        }
    }

    if (($hints['title'] ?? '') !== '' && ($hints['description'] ?? '') === '') {
        $hints['description'] = '';
    }

    return array_merge($hints, ['source' => 'filename']);
}

function validateCertUploadForExtract(array $file): void
{
    inspectCertUpload($file);
}

function extractCertFieldsFromUpload(array $file): array
{
    validateCertUploadForExtract($file);

    $originalName = sanitizeText((string) ($file['name'] ?? ''), 200);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mime === 'application/pdf') {
        $text = extractPdfText($file['tmp_name']);
        $parsed = parseCertDocumentText($text, $originalName);

        if (($parsed['title'] ?? '') === '' && $text === '') {
            $parsed = parseCertFilenameHints($originalName);
            $parsed['source'] = 'filename';
            $parsed['message'] = 'Could not read text from this PDF. Basic details were guessed from the file name — please review before saving.';
        }

        return $parsed;
    }

    return extractImageCertHints($file['tmp_name'], $originalName);
}

function mergeCertInputWithExtracted(array $input, array $extracted): array
{
    foreach (['title', 'issuer', 'year', 'description'] as $field) {
        $current = trim((string) ($input[$field] ?? ''));
        $suggested = trim((string) ($extracted[$field] ?? ''));
        if ($current === '' && $suggested !== '') {
            $input[$field] = $suggested;
        }
    }

    return $input;
}
