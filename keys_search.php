<?php
// Keys_sph_search
session_start();
error_reporting(E_ALL);
defined("ROOT") or define("ROOT", "./");

header("Content-Type: text/html; charset=utf-8");
if(empty($_GET['q'])) die("No query words.");

require_once(ROOT . 'etc/coreseek.php');

$q = trim($_GET['q']);
$kss = new SphinxClient;
$kss->SetServer('localhost', 9312);
$kss->SetMatchMode(SPH_MATCH_EXTENDED2); //SPH_MATCH_ALL
$kss->SetSortMode(SPH_SORT_EXTENDED,'@random');
$kss->SetLimits(0, 10);
//$kss->SetArrayResult(true); 应该为false

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
if ($res['total'] <= 0) {
	echo '<div class="alert">没有数据</div>';
	return;
}
$ids = array_keys($res['matches']);

require_once(ROOT . 'configs/mysql-connect.php');	
$db = mysql_connect_fmxw();

//f0: 首页的meego123.net
if(isset($_GET['hoverCard'])) {
	// avoid duplicated.
	$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ") group by rk";
	$mret = mysql_query($sql);

    if(mysql_num_rows($mret)<=0) {
		echo '<div class="alert">没有数据</div>';
		return;
	}
	echo '<ul class="nav nav-pills nav-stacked">';
    while ($row = mysql_fetch_array($mret, MYSQL_NUM)) {
        echo '<li><a id="rk_'.$row[0].'" href="'.$row[2].'" style="padding-bottom:0px;">'.htmlspecialchars($row[1])."</a></li>\n";
    }
    echo "</ul>\n";
}
//f0: auto suggest.
else {
	//$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ")";
	$sql = "select rk from key_related where rid in (" . implode(',',$ids) . ")";
	$ary = array();
	
	$mret = mysql_query($sql, $db);
	while ($row = mysql_fetch_array($mret, MYSQL_NUM)) {
		array_push($ary, htmlspecialchars($row[0]));
	}	
	echo json_encode($ary);
	// return json_encode($ary);
}
?>
