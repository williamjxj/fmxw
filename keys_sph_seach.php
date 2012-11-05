<?php
session_start();
error_reporting(E_ALL);
defined("ROOT") or define("ROOT", "./");

require_once(ROOT . 'coreseekClass.php');

if(empty($_GET['q'])) return;

$q = trim($_GET['q']);
$obj->cl->SetMatchMode(SPH_MATCH_ALL);
$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
$obj->cl->SetArrayResult(true);
SetLimits($currentOffset, $obj->conf['page']['limit']);

Query("select rid, rk, kurl from key_related where keyword like '%" . $q . "%' order by rand() limit 0, 15");
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastError() . "<br>\n";
    return;
} else if ($obj -> cl -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}

//select keyword from keywords where keyword like '%" . $q . "%' order by keyword
//select rk from key_related where keyword like '%" . $q . "%' and keyword != '" . $q . "' order by rk

$sql = ;
mysql_query($sql);

while ($row = mysql_fetch_assoc($mres)) {
    $row['r'] = ceil($matches[$row['cid']] / $max_weight * 100); //relevance
	if (!preg_match("/(<b>|<em>)/", $row['title']))
		$row['title'] = $obj->mb_highlight($row['title'], $q, '<b>', '</b>');

    $rows[$row['cid']] = $row;
}

echo json_encode($ary);
?>
