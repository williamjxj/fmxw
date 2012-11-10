<?php
define("DBHOST", "localhost");
define('DBUSER', 'dixitruth');
define("DBPASS", "dixi123456");
define('DBNAME', 'dixi');

//1. MySQL
function mysql_connect_fmxw()
{
	$db = mysql_pconnect(DBHOST, DBUSER, DBPASS) or die(mysql_error());
	mysql_select_db(DBNAME, $db);
	mysql_query("SET NAMES 'utf8'", $db);
	return $db;
}

//2. MDB2
require_once('MDB2.php');
function pear_connect_fmxw() 
{
	$dsn = array (
		'phptype' => 'mysqli',
		'username' => DBUSER,
		'password' => DBPASS,
		'hostspec' => DBHOST,
		'database' => DBNAME
	);
	$options = array(
		'debug'       => 2,
		'persistent'  => true,
		'portability' => MDB2_PORTABILITY_ALL,
	);
	$mdb2 = MDB2::factory($dsn, $options);
	if (PEAR::isError($mdb2)) {
		die($mdb2->getMessage());
	}
	$mdb2->query("SET NAMES 'utf8'");
	return $mdb2;
}

//3. Sphinx
//root 21945 1  0 Nov08 ? 00:00:01 /usr/local/coreseek/bin/searchd --config /home/williamjxj/fmxw/etc/dixi.conf
//root 21952 1  0 Nov08 ? 00:00:00 /usr/local/coreseek/bin/searchd --config /home/williamjxj/fmxw/etc/new9312.conf
/**
 * 因为性能原因, 创建该文件, 仅用于连接sphinx searched daemon, 最少开销.
 * 9312: keywords
 * 9313: contents
 */
require_once ("etc/coreseek.php");

// $index='keyRelated delta'
function sphinx_connect_9312()
{
	$cl2 = new SphinxClient;
	$cl2->SetServer('localhost', 9312);
	$cl2->SetMatchMode ( SPH_MATCH_EXTENDED2 );
	$cl2->SetSortMode(SPH_SORT_EXTENDED, '@random');
	$cl2->SetLimits(0, 10);
	return $cl2;
}

//$index='contents increment'
function sphinx_connect_9313()
{
	$cl3 = new SphinxClient;
	$cl3->SetServer('localhost', 9313);
	$cl3->SetMatchMode(SPH_MATCH_EXTENDED2);
	$cl3->SetSortMode(SPH_SORT_RELEVANCE);
	$cl3->SetArrayResult(true);
	return $cl3;
}


// 4. Memcached: ALL in shared-memory.
//496 1537 1  0 Oct26 ? 00:00:28 memcached -d -p 11211 -u memcached -m 64 -c 1024 -P /var/run/memcached/memcached.pid
function memcached_connect()
{
	$m = new Memcached();
	$m->addServer('localhost', 11211);
	return $m;
}

//5. mongoDB: 连接到localhost:27017
//mongod   30485 1  0 Oct21 ? 00:14:27 /usr/bin/mongod -f /etc/mongod.conf
function mongo_connect()
{
	$m = new Mongo();
	$db = $m -> search_lib; //数据库。
	$collection = $db -> keywords;  //表。
	return $collection;
}

//有名管道。
function write_named_pipes($search_key) {
	$dir = '/home/williamjxj/scraper/';
	$pipes = array(
		'baidu' => array($dir . '.baidu'),
		'soso' => array($dir . '.soso'),
		'google' => array($dir . '.google'),
		'yahoo' => array($dir . '.yahoo'),
	);

	foreach ($pipes as $p) {
		$fifo = fopen($p[0], 'r+');
		fwrite($fifo, $search_key);
		fclose($fifo);
	}
}

?>
