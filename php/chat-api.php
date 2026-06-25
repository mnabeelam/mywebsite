<?php
header('Content-Type: application/json');

$apiKey = getenv('OPENAI_API_KEY');
$question = $_POST['question'] ?? '';

if(!$apiKey){
    http_response_code(500);
    echo json_encode(['error'=>'OPENAI_API_KEY not configured']);
    exit;
}

/* Implement OpenAI API call here */
echo json_encode([
  'status'=>'ready',
  'question'=>$question,
  'answer'=>'OpenAI integration point ready.'
]);
?>