<?php

$m = '?page=2&q=&_=1352151347461';
$n = preg_match("/page=(\d+)/", $m);

echo $n . "\n";
?>
