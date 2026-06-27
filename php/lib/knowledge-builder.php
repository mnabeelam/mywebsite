<?php
declare(strict_types=1);

function knowledgeDirectory(): string
{
    $dir = realpath(__DIR__ . '/../../knowledge');
    if ($dir === false) {
        $target = __DIR__ . '/../../knowledge';
        if (!is_dir($target) && !mkdir($target, 0750, true)) {
            throw new RuntimeException('Knowledge directory could not be created.');
        }
        $dir = realpath($target);
    }

    if ($dir === false) {
        throw new RuntimeException('Knowledge directory is not available.');
    }

    return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function saveKnowledgeFiles(array $data): void
{
    backupKnowledgeFiles();
    $dir = knowledgeDirectory();

    file_put_contents($dir . 'profile.json', json_encode($data['profile'] ?? [], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'skills.json', json_encode($data['skills'] ?? [], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'projects.json', json_encode($data['projects'] ?? [], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'certifications.json', json_encode($data['certifications'] ?? [], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'experience.json', json_encode($data['experience'] ?? [], JSON_PRETTY_PRINT));

    if (!empty($data['raw_excerpt'])) {
        file_put_contents($dir . 'raw-excerpt.txt', (string) $data['raw_excerpt']);
    }

    rebuildKnowledgeSearchIndex();
}

function rebuildKnowledgeSearchIndex(): array
{
    seedDefaultKnowledgeFilesIfMissing();

    $entries = [];
    $dir = knowledgeDirectory();

    $profile = readJsonFile($dir . 'profile.json');
    if ($profile !== []) {
        $summary = trim((string) ($profile['summary'] ?? ''));
        $title = trim((string) ($profile['title'] ?? 'Deputy Director IT'));
        $org = trim((string) ($profile['organization'] ?? 'GIFT University'));
        $name = trim((string) ($profile['name'] ?? 'Mirza Nabeel Ahmed'));
        $entries[] = makeEntry(
            'profile',
            ['profile', 'about', 'who', 'name', 'role', 'gift', 'university', 'director'],
            $name . ' — ' . $title . ' at ' . $org . '. ' . $summary
        );
    }

    $skills = readJsonFile($dir . 'skills.json');
    if ($skills !== []) {
        $skillList = is_array($skills) ? implode(', ', array_map('strval', $skills)) : (string) $skills;
        $entries[] = makeEntry(
            'skills',
            ['skills', 'expertise', 'technology', 'technical', 'stack'],
            'Core skills: ' . $skillList . '.'
        );
        foreach ($skills as $skill) {
            $skillText = trim((string) $skill);
            if ($skillText === '') {
                continue;
            }
            $entries[] = makeEntry(
                slugTopic($skillText),
                [strtolower($skillText), 'skill', 'experience'],
                'Experience and expertise in ' . $skillText . '.'
            );
        }
    }

    $projects = readJsonFile($dir . 'projects.json');
    if ($projects !== []) {
        $names = [];
        foreach ($projects as $project) {
            if (is_array($project)) {
                $name = trim((string) ($project['name'] ?? ''));
                $details = trim((string) ($project['details'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $names[] = $name;
                $entries[] = makeEntry(
                    slugTopic($name),
                    [strtolower($name), 'project', 'projects'],
                    'Project: ' . $name . ($details !== '' ? '. ' . $details : '.')
                );
            } else {
                $names[] = (string) $project;
            }
        }
        if ($names !== []) {
            $entries[] = makeEntry(
                'projects',
                ['projects', 'project', 'work', 'portfolio'],
                'Featured projects include ' . implode(', ', $names) . '.'
            );
        }
    }

    $certifications = readJsonFile($dir . 'certifications.json');
    if ($certifications !== []) {
        $certList = is_array($certifications) ? implode(', ', array_map('strval', $certifications)) : (string) $certifications;
        $entries[] = makeEntry(
            'certifications',
            ['certification', 'certifications', 'certified', 'certificate', 'credentials'],
            'Certifications include ' . $certList . '.'
        );
    }

    $experience = readJsonFile($dir . 'experience.json');
    if ($experience !== []) {
        $lines = is_array($experience) ? implode("\n", array_map('strval', $experience)) : (string) $experience;
        $entries[] = makeEntry(
            'experience',
            ['experience', 'career', 'timeline', 'years', 'history', 'background'],
            "Career timeline:\n" . $lines
        );
    }

    $entries[] = makeEntry(
        'contact',
        ['contact', 'email', 'phone', 'reach', 'linkedin', 'connect'],
        'Contact: mnabeelam@gmail.com | dd.it@gift.edu.pk | LinkedIn available on the homepage.'
    );

    $entries = dedupeEntries($entries);

    $index = [
        'updated' => date('c'),
        'entry_count' => count($entries),
        'entries' => $entries,
    ];

    file_put_contents($dir . 'search-index.json', json_encode($index, JSON_PRETTY_PRINT));

    return $index;
}

function makeEntry(string $topic, array $keywords, string $answer): array
{
    return [
        'topic' => strtolower($topic),
        'keywords' => array_values(array_unique(array_map('strtolower', $keywords))),
        'answer' => trim($answer),
    ];
}

function slugTopic(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
    return trim($value, '-') ?: 'topic';
}

function dedupeEntries(array $entries): array
{
    $seen = [];
    $result = [];
    foreach ($entries as $entry) {
        $key = ($entry['topic'] ?? '') . '|' . ($entry['answer'] ?? '');
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $result[] = $entry;
    }
    return $result;
}

function readJsonFile(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function loadKnowledgeSearchIndex(): array
{
    $path = knowledgeDirectory() . 'search-index.json';
    if (!is_readable($path)) {
        return rebuildKnowledgeSearchIndex();
    }

    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : rebuildKnowledgeSearchIndex();
}

function searchKnowledgeAnswer(string $question): ?string
{
    $question = strtolower(trim($question));
    if ($question === '') {
        return null;
    }

    $index = loadKnowledgeSearchIndex();
    $entries = $index['entries'] ?? [];
    $words = preg_split('/\s+/', $question) ?: [];
    $bestScore = 0;
    $bestAnswer = null;

    foreach ($entries as $entry) {
        $score = scoreKnowledgeEntry($entry, $question, $words);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestAnswer = $entry['answer'] ?? null;
        }
    }

    return $bestScore >= 2 ? $bestAnswer : null;
}

function scoreKnowledgeEntry(array $entry, string $question, array $words): int
{
    $score = 0;
    $topic = (string) ($entry['topic'] ?? '');
    $answer = strtolower((string) ($entry['answer'] ?? ''));
    $keywords = $entry['keywords'] ?? [];

    if ($topic !== '' && strpos($question, $topic) !== false) {
        $score += 5;
    }

    foreach ($words as $word) {
        if (strlen($word) < 3) {
            continue;
        }
        if ($topic !== '' && strpos($topic, $word) !== false) {
            $score += 3;
        }
        if (strpos($answer, $word) !== false) {
            $score += 2;
        }
        foreach ($keywords as $keyword) {
            $keyword = (string) $keyword;
            if ($keyword === $word || strpos($keyword, $word) !== false || strpos($word, $keyword) !== false) {
                $score += 2;
            }
        }
    }

    return $score;
}

function backupKnowledgeFiles(): void
{
    $sourceDir = knowledgeDirectory();
    $backupRoot = __DIR__ . '/../storage/knowledge-backups';
    if (!is_dir($backupRoot)) {
        mkdir($backupRoot, 0700, true);
    }

    $stamp = date('Ymd-His');
    $targetDir = $backupRoot . DIRECTORY_SEPARATOR . $stamp;
    if (!mkdir($targetDir, 0700, true)) {
        return;
    }

    $files = ['profile.json', 'skills.json', 'projects.json', 'certifications.json', 'experience.json', 'search-index.json', 'raw-excerpt.txt'];
    foreach ($files as $file) {
        $source = $sourceDir . $file;
        if (is_file($source)) {
            copy($source, $targetDir . DIRECTORY_SEPARATOR . $file);
        }
    }

    $existing = glob($backupRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
    rsort($existing);
    foreach (array_slice($existing, 5) as $oldDir) {
        foreach (glob($oldDir . DIRECTORY_SEPARATOR . '*') ?: [] as $oldFile) {
            if (is_file($oldFile)) {
                unlink($oldFile);
            }
        }
        rmdir($oldDir);
    }
}

function seedDefaultKnowledgeFilesIfMissing(): void
{
    $dir = knowledgeDirectory();
    $profile = readJsonFile($dir . 'profile.json');

    if ($profile !== []) {
        return;
    }

    $data = [
        'profile' => [
            'name' => 'Mirza Nabeel Ahmed',
            'title' => 'Deputy Director IT',
            'organization' => 'GIFT University',
            'summary' => '18+ years in IT Infrastructure, Cyber Security, Virtualization and Digital Transformation.',
            'email' => 'mnabeelam@gmail.com',
            'updated' => date('c'),
        ],
        'skills' => ['Oracle', 'VMware', 'Linux', 'Cyber Security', 'AI', 'Smart Campus', 'AWS'],
        'projects' => [
            ['name' => 'Oracle 19c Migration + Data Guard', 'details' => ''],
            ['name' => 'Face Recognition System', 'details' => ''],
            ['name' => 'Moodle CBT Deployment', 'details' => ''],
            ['name' => 'DSpace Repository', 'details' => ''],
        ],
        'certifications' => [
            'Oracle Database 19c Administration',
            'AWS Cloud Architecture',
            'AWS Cloud Foundations',
            'CCNA Training',
        ],
        'experience' => [
            '2007 - Lab Administrator',
            '2009 - Network Administrator',
            '2017 - Assistant Manager IT',
            '2021 - Deputy Manager IT',
            '2022 - Manager IT',
            '2025 - Deputy Director IT',
        ],
    ];

    file_put_contents($dir . 'profile.json', json_encode($data['profile'], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'skills.json', json_encode($data['skills'], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'projects.json', json_encode($data['projects'], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'certifications.json', json_encode($data['certifications'], JSON_PRETTY_PRINT));
    file_put_contents($dir . 'experience.json', json_encode($data['experience'], JSON_PRETTY_PRINT));
}

function knowledgeStatus(): array
{
    $dir = knowledgeDirectory();
    $files = ['profile.json', 'skills.json', 'projects.json', 'certifications.json', 'experience.json', 'search-index.json'];
    $present = [];

    foreach ($files as $file) {
        $present[$file] = is_file($dir . $file);
    }

    $index = loadKnowledgeSearchIndex();

    return [
        'updated' => $index['updated'] ?? null,
        'entry_count' => $index['entry_count'] ?? 0,
        'files' => $present,
    ];
}
