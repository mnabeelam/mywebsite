<?php
/* Foundation for OpenAI CV extraction */
$apiKey = getenv('OPENAI_API_KEY');

function extractCV($text){
    return [
      'profile'=>[],
      'skills'=>[],
      'projects'=>[],
      'certifications'=>[]
    ];
}
?>