<?php
header('Content-Type: application/json');

$apiKey = getenv('OPENAI_API_KEY');

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

echo json_encode([
 'status'=>'demo',
 'message'=>'Backend endpoint ready. Connect OpenAI API.'
]);
?>