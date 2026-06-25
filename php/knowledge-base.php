<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents(__DIR__.'/../data/knowledge-base.json'), true);

$q = strtolower($_GET['q'] ?? '');

$response = "No match found.";

foreach($data as $k=>$v){
    if(stripos(json_encode($v), $q)!==false){
        $response = json_encode($v);
        break;
    }
}

echo json_encode([
 'query'=>$q,
 'answer'=>$response
]);
?>