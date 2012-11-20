<?php
header('Content-Type: text/html; charset=utf-8');
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
echo  count($res) . "<br>\n";

$search_key = '湿露露';
$keys = array($search_key, $search_key.'(负面|丑闻|真相)(新闻|评价|曝光)');
$ary  = array('.baidu', '.soso', '.google', '.yahoo');

foreach($ary as $p) {
	foreach($keys as $k) {
		echo $p . ', ' . $k . "<br>\n";
	}
}		
?>
