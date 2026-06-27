<?php
declare(strict_types=1);

require __DIR__ . '/../php/lib/knowledge-builder.php';

$index = rebuildKnowledgeSearchIndex();
echo 'Knowledge index rebuilt: ' . ($index['entry_count'] ?? 0) . " entries\n";
