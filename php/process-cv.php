<?php
require_once 'openai-client.php';
require_once 'pdf-extractor.php';

$file = $_POST['file'] ?? '';
$text = extractPdfText($file);

$result = openaiExtract($text);

file_put_contents('../knowledge/profile.json', json_encode($result['profile'], JSON_PRETTY_PRINT));
file_put_contents('../knowledge/skills.json', json_encode($result['skills'], JSON_PRETTY_PRINT));
file_put_contents('../knowledge/projects.json', json_encode($result['projects'], JSON_PRETTY_PRINT));
file_put_contents('../knowledge/certifications.json', json_encode($result['certifications'], JSON_PRETTY_PRINT));
file_put_contents('../knowledge/experience.json', json_encode($result['experience'], JSON_PRETTY_PRINT));

echo json_encode(['status'=>'success']);
?>