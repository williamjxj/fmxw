<?php
header("Content-Type: text/html; charset=utf-8");

$t = '微笑局长';
if($t == '微笑局长1') echo 'YYYYYYYYYYYYYY';
elseif(preg_match("/微笑/", $t)) echo '匹配';
else echo "NNNNNOOOOO<br>\n";

?>