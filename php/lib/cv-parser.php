<?php
declare(strict_types=1);

function parseCvText(string $text): array
{
    $text = trim($text);
    $lines = array_values(array_filter(array_map('trim', preg_split('/\n+/', $text) ?: [])));

    $profile = [
        'name' => 'Mirza Nabeel Ahmed',
        'title' => 'Deputy Director IT',
        'organization' => 'GIFT University',
        'summary' => '',
        'email' => '',
        'phone' => '',
        'updated' => date('c'),
    ];

    $skills = [];
    $projects = [];
    $certifications = [];
    $experience = [];

    if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $emailMatch)) {
        $profile['email'] = strtolower($emailMatch[0]);
    }

    if (preg_match('/(\+?\d[\d\s\-()]{7,}\d)/', $text, $phoneMatch)) {
        $profile['phone'] = trim($phoneMatch[1]);
    }

    if ($lines !== []) {
        $profile['name'] = $lines[0];
        if (isset($lines[1]) && strlen($lines[1]) < 120) {
            $profile['title'] = $lines[1];
        }
    }

    $section = '';
    foreach ($lines as $line) {
        $lower = strtolower($line);

        if (preg_match('/^(skills|technical skills|core competencies|expertise)\b/i', $line)) {
            $section = 'skills';
            continue;
        }
        if (preg_match('/^(projects|key projects|selected projects)\b/i', $line)) {
            $section = 'projects';
            continue;
        }
        if (preg_match('/^(certifications|certificates|credentials)\b/i', $line)) {
            $section = 'certifications';
            continue;
        }
        if (preg_match('/^(experience|work experience|employment|career history|professional experience)\b/i', $line)) {
            $section = 'experience';
            continue;
        }
        if (preg_match('/^(summary|profile|about)\b/i', $line)) {
            $section = 'summary';
            continue;
        }

        if ($section === 'summary' && $profile['summary'] === '') {
            $profile['summary'] = $line;
            continue;
        }

        if ($section === 'skills') {
            foreach (splitListItems($line) as $item) {
                $skills[] = $item;
            }
            continue;
        }

        if ($section === 'projects') {
            foreach (splitListItems($line) as $item) {
                $projects[] = ['name' => $item, 'details' => ''];
            }
            continue;
        }

        if ($section === 'certifications') {
            foreach (splitListItems($line) as $item) {
                $certifications[] = $item;
            }
            continue;
        }

        if ($section === 'experience') {
            $experience[] = $line;
        }
    }

    $skills = uniqueNonEmpty($skills);
    $projects = uniqueProjects($projects);
    $certifications = uniqueNonEmpty($certifications);
    $experience = uniqueNonEmpty($experience);

    applyDefaultKnowledge($profile, $skills, $projects, $certifications, $experience, $text);

    return [
        'profile' => $profile,
        'skills' => $skills,
        'projects' => $projects,
        'certifications' => $certifications,
        'experience' => $experience,
        'raw_excerpt' => substr($text, 0, 1200),
    ];
}

function splitListItems(string $line): array
{
    $line = trim(preg_replace('/^[-*•]\s*/', '', $line) ?? $line);
    if ($line === '') {
        return [];
    }

    if (strpos($line, ',') !== false) {
        return array_map('trim', explode(',', $line));
    }

    if (strpos($line, '|') !== false) {
        return array_map('trim', explode('|', $line));
    }

    return [$line];
}

function uniqueNonEmpty(array $items): array
{
    $result = [];
    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '' && !in_array($item, $result, true)) {
            $result[] = $item;
        }
    }
    return $result;
}

function uniqueProjects(array $projects): array
{
    $result = [];
    $seen = [];
    foreach ($projects as $project) {
        $name = trim((string) ($project['name'] ?? ''));
        if ($name === '' || isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $result[] = [
            'name' => $name,
            'details' => trim((string) ($project['details'] ?? '')),
        ];
    }
    return $result;
}

function applyDefaultKnowledge(
    array &$profile,
    array &$skills,
    array &$projects,
    array &$certifications,
    array &$experience,
    string $text
): void {
    $defaults = loadDefaultKnowledge();

    if ($profile['summary'] === '' && !empty($defaults['profile'])) {
        $profile['summary'] = is_string($defaults['profile']) ? $defaults['profile'] : json_encode($defaults['profile']);
    }

    if ($skills === [] && !empty($defaults['skills']) && is_array($defaults['skills'])) {
        $skills = $defaults['skills'];
    }

    if ($projects === [] && !empty($defaults['projects']) && is_array($defaults['projects'])) {
        foreach ($defaults['projects'] as $project) {
            $projects[] = ['name' => (string) $project, 'details' => ''];
        }
    }

    if ($certifications === []) {
        $certifications = [
            'Oracle Database 19c Administration',
            'AWS Cloud Architecture',
            'AWS Cloud Foundations',
            'CCNA Training',
        ];
    }

    if ($experience === []) {
        $experience = [
            '2007 - Lab Administrator',
            '2009 - Network Administrator',
            '2017 - Assistant Manager IT',
            '2021 - Deputy Manager IT',
            '2022 - Manager IT',
            '2025 - Deputy Director IT',
        ];
    }

    $keywordSkills = ['Oracle', 'VMware', 'Linux', 'Cyber Security', 'AI', 'AWS', 'Virtualization'];
    foreach ($keywordSkills as $keyword) {
        if (stripos($text, $keyword) !== false && !in_array($keyword, $skills, true)) {
            $skills[] = $keyword;
        }
    }
}

function loadDefaultKnowledge(): array
{
    $path = realpath(__DIR__ . '/../../data/knowledge-base.json');
    if (!$path || !is_readable($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}
