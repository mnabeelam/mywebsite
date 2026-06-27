<?php
require __DIR__ . '/../php/lib/bootstrap.php';
require __DIR__ . '/../php/lib/openai.php';
require __DIR__ . '/../php/lib/knowledge.php';

try {
    echo openaiChat(assistantSystemPrompt(), 'What Oracle experience do you have?');
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage();
}
