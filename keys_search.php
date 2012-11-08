<<<<<<< HEAD
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
if ($res['total'] <= 0) return;

$ids = array_keys($res['matches']);

require_once(ROOT . 'configs/mysql-connect.php');	
$db = mysql_connect_fmxw();

//f0: 首页的meego123.net
if(isset($_GET['hoverCard'])) {
	$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ")";
	$ary = array();
	$r = mysql_query($sql);

    if(mysql_num_rows<=0) return;
?>
<div class="alert">
  <button type="button" class="close" data-dismiss="alert" onclick="$('div.related_tag').hide();return false;">×</button>
  <strong>也查关联词:</strong> </div>
<ul class="nav nav-tabs nav-stacked">
<?php
    while ($row = mysql_fetch_array($kss_ret, MYSQL_NUM)) {
        echo '<li><a id="rk_$row[0]" href="$row[2]">'.htmlspecialchars($row[1])."</a></li>\n";
    }
    echo "</ul>\n";    
>>>>>>> ad3e3436540aa2f84daacd5c4a437c744d853314
}
//f0: auto suggest.
else {
	//$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ")";
	$sql = "select rk from key_related where rid in (" . implode(',',$ids) . ")";
	$ary = array();
	
	$kss_ret = mysql_query($sql, $db);
	while ($row = mysql_fetch_array($kss_ret, MYSQL_NUM)) {
		array_push($ary, $row[0]);
	}	
	echo json_encode($ary);
	// return json_encode($ary);
}
?>
=======
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
if ($res['total'] <= 0) return;

$ids = array_keys($res['matches']);

require_once(ROOT . 'configs/mysql-connect.php');	
$db = mysql_connect_fmxw();

//f0: 首页的meego123.net
if(isset($_GET['hoverCard'])) {
	$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ")";
	$mret = mysql_query($sql);

    if(mysql_num_rows($mret)<=0) return;
?>
<div class="alert">
  <strong>也查关联词:</strong> </div>
    <ul class="nav nav-tabs nav-stacked">
<?php
    while ($row = mysql_fetch_array($mret, MYSQL_NUM)) {
        echo '<li><a id="rk_$row[0]" href="$row[2]">'.htmlspecialchars($row[1])."</a></li>\n";
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
		array_push($ary, $row[0]);
	}	
	echo json_encode($ary);
	// return json_encode($ary);
}
?>
>>>>>>> 88d1adb7aefe43879991761da1778b1a531bd60e
