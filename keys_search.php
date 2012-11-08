<?php
// Keys_sph_search
session_start();
error_reporting(E_ALL);
defined("ROOT") or define("ROOT", "./");

header("Content-Type: text/html; charset=utf-8");
if(empty($_GET['q'])) {
	echo "No query words.";
	return;
}
$q = trim($_GET['q']);

require_once(ROOT . 'etc/coreseek.php');

$kss = new SphinxClient;
$kss->SetServer('localhost', 9312);
$kss->SetMatchMode(SPH_MATCH_EXTENDED2); //SPH_MATCH_ALL
$kss->SetSortMode(SPH_SORT_EXTENDED,'@random');
$kss->SetLimits(0, 10);
//$kss->SetArrayResult(true);

$index = 'keyRelated delta';
$res = $kss->Query($q, $index);
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $kss -> GetLastError() . "<br>\n";
    return;
} else if ($kss -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $kss -> GetLastWarning() . "<br>\n";
}

/*
 error, warning, status, fields, attrs, matches, total, total_found, time=0.001, words.
 echo "<pre>"; print_r($res); echo "</pre>";
 */

if ($res['total'] > 0) {
	$ids = array_keys($res['matches']);
}
else {
	die("No this keyword: ".$q."<br>\n");
}

require_once(ROOT . 'configs/mysql-connect.php');	
$db = mysql_connect_fmxw();

//$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ")";
$sql = "select rk from key_related where rid in (" . implode(',',$ids) . ")";
$ary = array();

$kss_ret = mysql_query($sql, $db);
while ($row = mysql_fetch_array($kss_ret, MYSQL_NUM)) {
	array_push($ary, $row[0]);
}

echo json_encode($ary);
// return json_encode($ary);
?>
