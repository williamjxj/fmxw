<?php
// 当前，searchd的端口号 为 9313
header('Content-Type: text/html; charset=utf-8');

require_once('../etc/sphinxapi.php');

$s = new SphinxClient;

$s->setServer("localhost", 9313);

$s->setMatchMode(SPH_MATCH_ANY);

$s->setMaxQueryTime(3);

$result = $s->query("test");

var_dump($result);

?>
