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
	$connection = new Mongo();
	// select a database
	//$db = $connection->words_lib;
	$db = $connection->words_lib;
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
	//$cursor = $collection->find( { key : /^Alex/i } );
	//$cursor = $collection->find( { key : { $regext: '^Alex', $options: 'i' } } );

	foreach($cursor as $c) {
		array_push($ary, iconv('UTF-8', 'UTF-8//TRANSLIT', $c{'key'}));
	}
	//如果是an empty array，接着查询MySQL->dixi->keywords表， 否则返回结果。
	if(!empty($ary) {
		echo json_encode($a);
		return;
	}
	else {
		// Twitter Bootstrap - Typeahead Plugin with MySQL.
		// William Jiang on Aug 09, 2012.

		$db = mysql_pconnect('localhost', 'dixitruth', 'dixi123456') or die(mysql_error());
		mysql_select_db('dixi', $db);
		mysql_query("SET NAMES 'utf8'", $db);
			
		$query = "select keyword from keywords where keyword like '%" . $q . "%' order by kid";
	
		$res = $mdb2->query($query);	
		if (PEAR::isError($res)) die($res->getMessage());
		while($row = $res->fetchRow()) {
			$ary[] = iconv('UTF-8', 'UTF-8//TRANSLIT', $row[0]);
			
			//将取得的结果放入MongoDB的search表中，以后就可以直接从MongoDB中获得
			$obj = array( "key" => $row[0], "count" => 1 );
			$collection->insert($obj);
		}
		echo json_encode($ary);
	}
}
/*else {
	echo "输入的字符没有被识别。";
}*/

?>
