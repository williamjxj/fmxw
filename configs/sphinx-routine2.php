<?php
// 对应 etc/new9312.conf.
session_start();
error_reporting(E_ALL);

if(empty($_GET['q'))) return;
$q = trim($_GET['q']);

defined("ROOT") or define("ROOT", "./");
header("Content-Type: text/html; charset=utf-8");

// 1. Sphinx 处理。
$cl2 = new SphinxClient;
$cl2->SetServer('localhost', 9312);
$cl2->SetMatchMode(SPH_MATCH_EXTENDED2);
$cl2->SetSortMode(SPH_SORT_EXTENDED, '@random');
$cl2->SetLimits(0, 10);

$res2 = $cl2->Query($q, 'keyRelated delta');
if (!$res2) die($cl2->GetLastError();
elseif ($cl2->GetLastWarning()) echo $cl2->GetLastWarning();

if ($res2['total'] <= 0) {
	echo 'Sphinx Searchd没有找到数据。';
	return;
}
$ids = array_keys($res2['matches']);

///////////////////////////
//// 2. MySQL 处理。
$db = mysql_pconnect('localhost', 'dixitruth', 'dixi123456');
mysql_select_db('dixi', $db);
mysql_query("SET NAMES 'utf8'", $db);

$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ") group by rk";
$ret = mysql_query($sql);

if(mysql_num_rows($ret)<=0) {
	echo 'MySQL没有找到数据。';
	return;
}

$ary = array();
while ($row = mysql_fetch_array($ret, MYSQL_NUM))
	array_push($ary, htmlspecialchars($row[1]);

echo json_encode($ary);
?>
