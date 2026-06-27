<?php
declare(strict_types=1);

require_once __DIR__ . '/shop-services.php';

function knowledgeContext(): string
{
    $sections = [];

    $baseFile = realpath(__DIR__ . '/../../data/knowledge-base.json');
    if ($baseFile && is_readable($baseFile)) {
        $sections[] = "Portfolio knowledge:\n" . file_get_contents($baseFile);
    }

    $knowledgeDir = realpath(__DIR__ . '/../../knowledge');
    if ($knowledgeDir) {
        foreach (glob($knowledgeDir . '/*.json') ?: [] as $file) {
            $sections[] = basename($file) . ":\n" . file_get_contents($file);
        }
    }

    $sections[] = shopCatalogAssistantContext();

    if ($sections === []) {
        return 'Mirza Nabeel Ahmed is Deputy Director IT at GIFT University with expertise in Oracle, VMware, Linux, Cyber Security, AI, and Smart Campus projects.';
    }

    return implode("\n\n", $sections);
}

function assistantSystemPrompt(): string
{
    return implode("\n", [
        'You are the AI assistant on Mirza Nabeel Ahmed\'s executive IT portfolio website.',
        'Answer visitor questions professionally, clearly, and concisely in 2-5 sentences unless more detail is requested.',
        'Use the knowledge below, including the IT online store product list when visitors ask about shop items, prices, stock, or ordering.',
        'If the answer is not in the knowledge, say you do not have that detail and suggest contacting mnabeelam@gmail.com or dd.it@gift.edu.pk, or browsing the IT Shop section to order online.',
        '',
        'Knowledge:',
        knowledgeContext(),
    ]);
}
