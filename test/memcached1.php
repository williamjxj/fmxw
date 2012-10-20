<?php
$m = new Memcached();
$m->addServer('localhost', 11211);
$items = array(
	'key1' => 'value1',
	'key2' => 'value2',
	'key3' => 'value3'
);
$m->setMulti($items);
$m->getDelayed(array('my_key', 'complex'), true, 'result_cb');

function result_cb($memc, $item)
{
	var_dump($item);
}
?>
