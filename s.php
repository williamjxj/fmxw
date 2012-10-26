<?php
//
session_start();
error_reporting(E_ALL);
define("ROOT", "./");
require_once (ROOT . "configs/config.inc.php");
global $config;

require_once (ROOT . "locales/f0.inc.php");
global $header;
global $search;
global $list;
global $footer;

require_once (ROOT . 'sClass.php');
set_lang();

try {
    $obj = new FMXW_Sphinx();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

$obj -> set_coreseek_server();
//$obj->set_sphinx_server();
//header("Content-Type: text/html; charset=utf-8");

list($tdir0, $tdir1, $tdir2) = array($config['t0'], $config['t1'],$config['t2']);

if (isset($_GET['q'])) {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
    $key = $q = $_SESSION[PACKAGE][SEARCH]['key'] = trim($_GET['q']);
	
	$obj->set_keywords($key);
    $obj -> set_filter();
}
else {
    die('EEEEEEEEEEEEERRRRRRRRRRRRROOOOOOOORRRRRRRRRRRRRRRRR');
}

// 设置当前页和开始的记录号码。
//empty()= !isset($var) || $var == false.
if (empty($_GET['page'])) {
    $currentPage = 1;
    $currentOffset = 0;
}
else {
    $currentPage = intval($_GET['page']);
    if (empty($currentPage) || $currentPage < 1) {
		$currentPage = 1;
    }

    $currentOffset = ($currentPage - 1) * $obj -> conf['page']['limit'];

    if ($currentOffset > ($obj->conf['page']['max_matches'] - $obj->conf['page']['limit'])) {
        die("Only the first {$obj->conf['page']['max_matches']} results accessible");
    }
}

$obj -> cl -> SetLimits($currentOffset, $obj->conf['page']['limit']);
//current page and number of results

/** 开始查询Coreseek-Sphinx索引，并得到相关信息。
 * error, warning, status, fields+attrs, matches, total, total_found, time, words
 */

$obj -> cl -> SetArrayResult(true);

$obj -> cl->SetGroupBy('clicks', SPH_GROUPBY_ATTR);

$res = $obj -> cl -> Query($q, $obj -> conf['coreseek']['index']);
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastError() . "<br>\n";
    return;
} else if ($obj -> cl -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}

if (empty($res["matches"])) {
    $sec = "用时【" . $res['time'] . "】秒。";
    $summary = "查询【" . $q . "】 没有发现匹配结果，" . $sec;
    $obj -> display_summary($summary);
    return;
}

$resultCount = $res['total_found'];
$numberOfPages = ceil($res['total'] / $obj -> conf['page']['limit']);
//Query 'test' retrieved 25 of 2617 matches in 0.000 sec.
$query_info = "查询词：【" . $q . "】， 用时【" . $res['time'] .
"】秒，匹配数【" . $res['total'] . "】, 总共【" .
$res['total_found'] . "】条记录, 共【" . $numberOfPages .
"】页，每页【" . $obj -> conf['page']['limit'] . "】记录<br>\n";

$obj -> display_summary($query_info);

//SetArrayResult(true), $ary_ids = array_keys($res['matches']);
$ary_ids = array_map("get_SetArrayResult_Ids", $res['matches']);
$ids = implode(",", $ary_ids);
$matchs = array();
foreach($res['matches'] as $v) {
	$matches[$v['id']] = $v['weight'];
}
//$obj->__p($matches);
$weights = array('title'=>11, 'content'=>10);

// 在SPH_MATCH_EXTENDED模式中，最终的权值是带权的词组评分和BM25权重的和，再乘以1000并四舍五入到整数。
// $max_weight = (array_sum($weights) * count($res['words']) + 1) * 1000;
$max_weight = (array_sum(array($weights)) * count($res['words']) + 1) * 1000;

$query = "SELECT * from contents where cid in (" . $ids . ")";
//$query = $obj->conf['coreseek']['query'];
echo $query . "<br>\n";

$res = mysql_query($query);

if (mysql_num_rows($res) <= 0) {
    $summary = "查询 【" . $q . "】 没有发现匹配结果。";
    $obj -> display_summary($summary);
    return;
}

$rows = array();
while ($row = mysql_fetch_array($res)) {
    $row['relevance'] = ceil($matches[$row['cid']]['weight'] / $max_weight * 100);
    $rows[$row['cid']] = $row;
}

//Call Sphinxes BuildExcerpts function
$docs = array();
foreach ($ary_ids as $c => $id) {
    $docs[$c] = strip_tags($rows[$id]['content']);
}
//$obj->__p($docs);
//$obj->__p($obj->conf['coreseek']['index']);
//$obj->__p($q);

$newd = $obj->my_process($docs);
// $obj->__p($newd);

$reply = $obj -> cl -> BuildExcerpts($newd, $obj -> conf['coreseek']['index'], $q);
echo "<br>-------[".$obj->conf['coreseek']['index']."],[".$q."]----------<br>\n";

if (!$reply) {
	echo "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa<br>\n";
	$newb = array();
	foreach($newd as $d) {
		$d1 = mb_substr($d, 0, 60);
		echo "==========[".$d1 ."]<br>\n";
		$newb[] = $obj->mb_highlight($d1, $q, '<b>', '</b>');
	}
	$obj->__p($newb);
}
echo "----------------------------------================================<br>\n";
$obj->__p($reply);

exit;

/* $obj -> assign('results', $obj -> select_contents_by_keyword($key)); */
$pagination = $obj -> draw();
$obj -> assign("pagination", $pagination);
$obj -> assign("nav_template", $tdir2 . 'nav.tpl.html');	
$obj -> assign('kr', $obj->get_key_related($key));
$obj -> assign('config', $config);
	
$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));

$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

$obj->display($tdir1 . 'ss.tpl.html');

if (isset($_GET['q'])) {
    $obj -> display($tdir1 . 'ss.tpl.html');
} else {
    $obj -> display($tdir1 . 'index.tpl.html');
}

function get_SetArrayResult_Ids($a) {
	return $a['id'];
}
?>