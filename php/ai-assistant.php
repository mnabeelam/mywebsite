<?php
header('Content-Type: application/json');
$q=$_POST['question']??'';
echo json_encode([
 'question'=>$q,
 'answer'=>'Knowledge platform connected. Ready for OpenAI integration.'
]);
?>