<?php
/** http://stackoverflow.com/questions/4115366/whats-the-best-way-to-implement-autocomplete-in-the-server
 * I recommend using setTimeout() to wait for about 200ms before firing the ajax call. If in that 200ms, another keystroke is triggered, 
 * then cancel the last timeout and start another one. This is a really clean solution where it wouldn't hit the db with each keystroke. 
 * I have used it in the past and it works really well.
 */
session_start();

/* auto-suggest: 每次查询如果都从MySQL中查询，性能很不好。改为从：
 * 用MongoDB: Perl's mongoDB 插件安装不成功，不能用MongoDB作为Perl/PHP之间交换数据用，所以就将MongDB用在auto-suggest上。
 * 理想情况: 用memcached控制auto-suggest, 用MengoDB作为Perl/PHP数据交换.
 * Perl采集到同义词表，放入MongoDB Hash-table 中，PHP查询时候，用这些信息进行相关度排序。
 */
try {
	// connect mongoDB server: localhost:27017
	$connection = new Mongo();
	// select a database
	//$db = $connection->words_lib;
	$db = $connection->comedy;
}
catch ( MongoConnectionException $e ) 
{
    echo '<p>Couldn\'t connect to mongodb, is the "mongoD" process running?</p>';
    exit;
}

// select a collection
$collection = $db->cartoons;

if(!empty($_GET['q'])) {

	$q = trim($_GET['q']);
	$regex = new MongoRegex("/$q/i");
	$cursor = $collection->find(array('title'=> $regex));
	//$cursor = $collection->find( { title : /^Alex/i } );
	//$cursor = $collection->find( { title : { $regext: '^Alex' $options: 'i' } } );
	$a = array();
	foreach($cursor as $c) {
		//print_r(json_encode($c));
		array_push($a, iconv('UTF-8', 'UTF-8//TRANSLIT', $c{'title'}));
	}
	echo json_encode($a);
return;
	defined('ROOT') or define('ROOT', './');
	header('Content-Type: text/html; charset=utf-8'); 
	// Twitter Bootstrap - Typeahead Plugin with MySQL.
	// William Jiang on Aug 09, 2012.

	require_once(ROOT.'configs/mini-app.inc.php');
	$mdb2 = pear_connect_fmxw();
	
	$ary = array();
	$q = $_GET['q'];
	$query = "select keyword from keywords where keyword like '%" . $q . "%' order by kid";

	$res = $mdb2->query($query);	
	if (PEAR::isError($res)) die($res->getMessage());
	while($row = $res->fetchRow()) {
		$ary[] = iconv('UTF-8', 'UTF-8//TRANSLIT', $row[0]);
	}
	echo json_encode($ary);
}
/*else {
	echo "输入的字符没有被识别。";
}*/
exit;

//////////////////////////////////

function recursive_iconv(string $in_charset, string $out_charset, $arr){
	if (!is_array($arr)){
		return iconv($in_charset, $out_charset, $arr);
	}
	$ret = $arr;
	function array_iconv(&$val, $key, $userdata){
		$val = iconv($userdata[0], $userdata[1], $val);
	}
	array_walk_recursive($ret, "array_iconv", array($in_charset, $out_charset));
	return $ret;
}

// not used. 	
function get_items($q) {
	$query = "select keyword from keywords where LOWER(keyword) like '%" . $q . "%'";
	$res = $mdb2->queryAll($query, '', MDB2_FETCHMODE_ASSOC);
	if (PEAR::isError($res)) die($res->getMessage());
	echo json_encode($res);
}
?>
