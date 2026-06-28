<?php
declare(strict_types=1);

function siteLoadProjects(): array
{
    $path = __DIR__ . '/../knowledge/projects.json';
    if (!is_readable($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        return [];
    }

    $projects = [];
    foreach ($decoded as $project) {
        if (!is_array($project)) {
            continue;
        }
        $name = trim((string) ($project['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $projects[] = $project;
    }

    return $projects;
}

function siteFeaturedProjects(int $limit = 4): array
{
    return array_slice(siteLoadProjects(), 0, max(1, $limit));
}
