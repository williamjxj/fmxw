<?php
$weights = array('title'=>11, 'content'=>10);
echo array_sum($weights) . "\n";


$res = array(
    'a' => array (
		'docs' => 525,
		'hits' => 753,
	),
	'b' => Array (
		'docs' => 1489,
		'hits' => 2172,
	),
);
echo  count($res) . "\n";

?>
