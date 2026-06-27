<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/knowledge-builder.php';
require_once __DIR__ . '/shop-services.php';

function searchCombinedAssistantAnswer(string $question): array
{
    $shopIntent = shopQuestionHasStoreIntent($question);
    $shopAnswer = searchShopAssistantAnswer($question);
    $knowledgeAnswer = searchKnowledgeAnswer($question);

    if ($shopIntent && $shopAnswer !== null) {
        return [
            'answer' => $shopAnswer,
            'source' => 'shop',
        ];
    }

    if ($knowledgeAnswer !== null) {
        return [
            'answer' => $knowledgeAnswer,
            'source' => 'knowledge-base',
        ];
    }

    if ($shopAnswer !== null) {
        return [
            'answer' => $shopAnswer,
            'source' => 'shop',
        ];
    }

    return [
        'answer' => null,
        'source' => 'none',
    ];
}
