<?php
/**
 * 因为性能原因, 创建该文件, 仅用于连接数据库, 最少开销.
 * 该文件可用于不同的应用, 提供数据库连接访问.
 */

defined('DBHOST') or define("DBHOST", "localhost");
defined('DBUSER') or define('DBUSER', 'dixitruth');
defined('DBPASS') or define("DBPASS", "dixi123456");
defined('DBNAME') or define('DBNAME', 'dixi');

function mysql_connect_fmxw()
{
	$db = mysql_pconnect(DBHOST, DBUSER, DBPASS) or die(mysql_error());
	mysql_select_db(DBNAME, $db);
	mysql_query("SET NAMES 'utf8'", $db);
	return $db;
}

?>
