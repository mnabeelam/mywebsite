<?php
function openaiExtract($text){
  $apiKey = getenv('OPENAI_API_KEY');

  $prompt = file_get_contents(__DIR__.'/../prompts/cv-extract-prompt.txt');

  return [
    'profile'=>[],
    'skills'=>[],
    'projects'=>[],
    'certifications'=>[],
    'experience'=>[]
  ];
}
?>