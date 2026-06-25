<?php
file_put_contents(
 __DIR__.'/../knowledge/profile.json',
 json_encode(['last_update'=>date('c')],JSON_PRETTY_PRINT)
);
echo 'Knowledge rebuilt';
?>