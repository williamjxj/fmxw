<?php
// 对应 etc/dixi.conf.
session_start();
error_reporting(E_ALL);

if(empty($_GET['q'))) return;
$q = trim($_GET['q']);

defined("ROOT") or define("ROOT", "./");
header("Content-Type: text/html; charset=utf-8");

// 1. Sphinx 处理。
$cl3 = new SphinxClient;
$cl3->SetServer('localhost', 9313);
$cl3->SetMatchMode(SPH_MATCH_EXTENDED2);
$cl3->SetSortMode(SPH_SORT_RELEVANCE);
$cl3->SetArrayResult(true);

$res3 = $cl3->Query($q, 'contents increment');
if (!$res3) die($cl3->GetLastError();
elseif ($cl3->GetLastWarning()) echo $cl3->GetLastWarning();

if ($res3['total'] <= 0) {
	echo 'Sphinx Searchd没有找到数据。';
	return;
}
$ids = array_keys($res3['matches']);

///////////////////////////
//// 2. MySQL 处理。
$db = mysql_pconnect('localhost', 'dixitruth', 'dixi123456');
mysql_select_db('dixi', $db);
mysql_query("SET NAMES 'utf8'", $db);

$sql = "select * from contents where cid in (" . implode(',',$ids) . ")";
$ret = mysql_query($sql);

if(mysql_num_rows($ret)<=0) {
	echo 'MySQL没有找到数据。';
	return;
}

$ary = array();
while ($row = mysql_fetch_assoc($ret)) {
	array_push($ary, $row);
}

echo json_encode($ary);
?>
