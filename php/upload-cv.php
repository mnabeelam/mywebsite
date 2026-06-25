<?php
$targetDir = __DIR__ . '/../uploads/cv/';
if(!is_dir($targetDir)) mkdir($targetDir,0775,true);

$file = basename($_FILES['cv']['name']);
move_uploaded_file($_FILES['cv']['tmp_name'],$targetDir.$file);

echo json_encode(['status'=>'uploaded','file'=>$file]);
?>