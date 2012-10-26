<?php

$a = array();
$a['all']['key'] = 'abc';
$a['all'] = array(
	'page' => 1,
	'total' => 12345,
	'time' => date('l \t\h\e jS'),
	'total_pages' => 20
);
//array_push($a['all'], $t);
//array_merge($a, $t);
echo "<pre>";print_r($a); echo "</pre>";
