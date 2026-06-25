<?php
function extractPdfText($file){
   return file_exists($file) ? file_get_contents($file) : '';
}
?>