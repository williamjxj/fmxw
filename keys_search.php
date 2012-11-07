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

require_once(ROOT . 'coreseekClass.php');
require_once(ROOT . 'configs/mysql-connect.php');

$kss = new SphinxClient;
$kss->StServer('localhost', 9312);
$kss->SetMatchMode(SPH_MATCH_EXTENDED2); //SPH_MATCH_ALL
$kss->SetSortMode(SPH_SORT_EXTENDED,'@random');
$kss->SetLimits(0, 10);
//$kss->SetArrayResult(true);

$index = 'keywords';
$res = $kss->Query($q, $index);
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $kss -> GetLastError() . "<br>\n";
    return;
} else if ($kss -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $kss -> GetLastWarning() . "<br>\n";
}

echo "<pre>"; print_r($res); echo "</pre>";

if ($res['total'] > 0) {
	$ids = array_keys($res['matches']);
}
else {
	die("No this keyword: ".$q."<br>\n");
}
	
mysql_connect_fmxw();

//select keyword from keywords where keyword like '%" . $q . "%' order by keyword
//select rk from key_related where keyword like '%" . $q . "%' and keyword != '" . $q . "' order by rk
$sql = "select rid, rk, kurl from key_related where rid in ($ids)";

$kss_ret = mysql_query($sql);

echo "<pre>"; print_r($kss_ret); echo "</pre>";

while ($row = mysql_fetch_assoc($kss_ret)) {}

//greecho json_encode($ary);
?>
