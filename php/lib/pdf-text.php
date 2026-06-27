<?php
declare(strict_types=1);

function normalizeExtractedText(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
    $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
    return trim($text);
}

function decodePdfString(string $value): string
{
    $value = preg_replace('/\\\\([\\\\()nrt])/', '', $value) ?? $value;
    return $value;
}

function extractPdfText(string $file): string
{
    if (!is_readable($file)) {
        return '';
    }

    $data = file_get_contents($file);
    if ($data === false || $data === '') {
        return '';
    }

    $parts = [];

    if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $data, $matches)) {
        foreach ($matches[0] as $chunk) {
            $decoded = decodePdfString(substr($chunk, 1, -1));
            if (strlen(trim($decoded)) > 1) {
                $parts[] = $decoded;
            }
        }
    }

    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $data, $matches)) {
        foreach ($matches[1] as $chunk) {
            if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $chunk, $inner)) {
                foreach ($inner[0] as $piece) {
                    $parts[] = decodePdfString(substr($piece, 1, -1));
                }
            }
        }
    }

    return normalizeExtractedText(implode("\n", $parts));
}

function extractDocxText(string $file): string
{
    if (!class_exists(ZipArchive::class) || !is_readable($file)) {
        return '';
    }

    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        return '';
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        return '';
    }

    $text = preg_replace('/<w:tab\/>/', "\t", $xml) ?? $xml;
    $text = preg_replace('/<w:br\/>/', "\n", $text) ?? $text;
    $text = preg_replace('/<\/w:p>/', "\n", $text) ?? $text;
    $text = strip_tags($text);

    return normalizeExtractedText(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function extractDocumentText(string $file): string
{
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($extension === 'pdf') {
        return extractPdfText($file);
    }

    if ($extension === 'docx') {
        return extractDocxText($file);
    }

    if ($extension === 'doc') {
        return normalizeExtractedText((string) file_get_contents($file));
    }

    return '';
}
