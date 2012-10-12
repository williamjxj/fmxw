<?php

header('Content-Type: text/html; charset=utf-8');

require_once('fmxw/sphinxapi.php');

$s = new SphinxClient;
$s->setServer("localhost", 9306);
$s->setMatchMode(SPH_MATCH_ANY);
$s->setMaxQueryTime(3);

$result = $s->query("test");

var_dump($result);

?>
