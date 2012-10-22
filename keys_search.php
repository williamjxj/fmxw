<?php
/** http://stackoverflow.com/questions/4115366/whats-the-best-way-to-implement-autocomplete-in-the-server
 * I recommend using setTimeout() to wait for about 200ms before firing the ajax call. If in that 200ms, another keystroke is triggered, 
 * then cancel the last timeout and start another one. This is a really clean solution where it wouldn't hit the db with each keystroke. I have used it in the past and it works really well.
 */
session_start();

/* auto-suggest: 每次查询如果都从MySQL中查询，性能很不好。改为从：
 * 用MongoDB: Perl's mongoDB 插件安装不成功，不能用MongoDB作为Perl/PHP之间交换数据用，所以就将MongDB用在auto-suggest上。
 * 理想情况: 用memcached控制auto-suggest, 用MengoDB作为Perl/PHP数据交换.
 * Perl采集到同义词表，放入MongoDB Hash-table 中，PHP查询时候，用这些信息进行相关度排序。
 */
try {
	// connect mongoDB server: localhost:27017
	$m = new Mongo();
	// select a database
	$db = $m->words_lib;
}
catch ( MongoConnectionException $e ) {
    die('<p>Couldn\'t connect to mongodb, is the "mongoD" process running?</p>');
}

$ary = array();
// 所有的查询信息都放在数据库words_lib的search表中。
$collection = $db->search;

if(!empty($_GET['q'])) {

	$q = trim($_GET['q']);

	$regex = new MongoRegex("/$q/i");
	$cursor = $collection->find(array('key'=> $regex));
	//$cursor = $collection->find( { key : /^Alex/i } ); //({ key : { $regext: '^Alex', $options: 'i'}});

	$it = iterator_to_array($cursor);
	if(! empty($it)) {
		$count = 1;
		foreach($cursor as $c) {
			array_push($ary, iconv('UTF-8', 'UTF-8//TRANSLIT', $c{'key'}));
			if( ++$count > 10) break;
		}
	}
	//如果是an empty array，接着查询MySQL->dixi->keywords表， 否则返回结果。
	if(!empty($ary)) {
		echo json_encode($ary);
		return;
	}
	else {
		// Twitter Bootstrap - Typeahead Plugin with MySQL.
		// echo "William Jiang on Aug 09, Oct2l, 2012.\n";

		$mysql = mysql_pconnect('localhost', 'dixitruth', 'dixi123456') or die(mysql_error());
		mysql_select_db('dixi', $mysql);
		mysql_query("SET NAMES 'utf8'", $mysql);

		// 1. keywords
		$query1 = "select keyword from keywords where keyword like '%" . $q . "%' order by keyword";
		array_push_array($ary, mysql2mongo($collection, $query1));

		// 2. key_related
		$query2 = "select rk from key_related where keyword like '%" . $q . "%' order by rk";
		array_push_array($ary, mysql2mongo($collection, $query2));

		echo json_encode($ary);
	}
}
/*else {
	echo "输入的字符没有被识别。";
}*/

function mysql2mongo($c, $sql)
{
	$a = array();
	$res = mysql_query($sql) or mysql_error();
	if(mysql_num_rows($res)>0) {
		while($row = mysql_fetch_array($res, MYSQL_NUM)) {
			$t = iconv('UTF-8', 'UTF-8//TRANSLIT', $row[0]);
			$a[] = $t;
			//将取得的结果放入MongoDB的search表中，以后就可以直接从MongoDB中获得
			$obj = array( 'key' => $t, 'count' => 1 );
			$c->insert($obj);
		}
	}
	return $a;
}
function array_push_array(&$arr) {
	$args = func_get_args();
	array_shift($args);

	if (!is_array($arr)) {
		trigger_error(sprintf("%s: Cannot perform push on something that isn't an array!", __FUNCTION__), E_USER_WARNING);
		return false;
	}

	foreach($args as $v) {
		if (is_array($v)) {
			if (count($v) > 0) {
				array_unshift($v, &$arr);
				call_user_func_array('array_push',  $v);
			}
		} else {
			$arr[] = $v;
		}
	}
	return count($arr);
}

?>
