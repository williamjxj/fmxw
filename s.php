<?php
//
session_start();
error_reporting(E_ALL);
define("ROOT", "./");
defined('CS') or define('CS', 'coreseek_sphinx');
require_once (ROOT . "configs/config.inc.php");
global $config;
require_once (ROOT . 'adsearchClass.php');
set_lang();

try {
    $obj = new FMXW_Sphinx();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

$obj -> set_coreseek_server();
//$obj->set_sphinx_server();
//header("Content-Type: text/html; charset=utf-8");

if (isset($_POST['js_form'])) {
    $obj -> get_parse();
    $obj -> set_filter();
} else {
    die('EEEEEEEEEEEEERRRRRRRRRRRRROOOOOOOORRRRRRRRRRRRRRRRR');
}

$h = $_SESSION[PACKAGE][CS];
$q = $_SESSION[PACKAGE][CS]['q'];
$q1 = $_SESSION[PACKAGE][CS]['key'];

// 设置当前页和开始的记录号码。
//empty()= !isset($var) || $var == false.
if (empty($_GET['page'])) {
    $currentPage = 1;
    $currentOffset = 0;
} else {
    $currentPage = intval($_GET['page']);
    if (empty($currentPage) || $currentPage < 1) {$currentPage = 1;
    }

    $currentOffset = ($currentPage - 1) * $obj -> conf['page']['size'];

    if ($currentOffset > ($obj -> conf['page']['max_matches'] - $obj -> conf['page']['size'])) {
        die("Only the first {$obj->conf['page']['max_matches']} results accessible");
    }
}

$obj -> cl -> SetLimits($currentOffset, $h['limit']);
//current page and number of results

/** 开始查询Coreseek-Sphinx索引，并得到相关信息。
 * error, warning, status, fields+attrs, matches, total, total_found, time, words
 */

$obj -> cl -> SetArrayResult(true);

//created, pubdate, tags, pinglun, guanzhu, clicks, createdby, language, iid, cate_id
// $obj->cl->SetGroupBy('pubdate', SPH_GROUPBY_MONTH, "@group DESC");
//$obj->cl->SetGroupBy ('cate_id', SPH_GROUPBY_ATTR, "@count desc");
$obj -> SetGroupBy('clicks', SPH_GROUPBY_ATTR);
//$obj->cl->SetGroupBy ('guanzhu', SPH_GROUPBY_ATTR, "@count desc");
//$obj->cl->SetGroupBy ('pinglun', SPH_GROUPBY_ATTR, "@count desc");

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
$numberOfPages = ceil($res['total'] / $obj -> conf['page']['size']);
//Query 'test' retrieved 25 of 2617 matches in 0.000 sec.
$query_info = "查询词：【" . $q . "】， 用时【" . $res['time'] . "】秒，匹配数【" . $res['total'] . "】, 总共【" . $res['total_found'] . "】条记录, 共【" . $numberOfPages . "】页，每页【" . $obj -> conf['page']['size'] . "】记录<br>\n";

$obj -> display_summary($query_info);

$ids1 = array_keys($res['matches']);
$ids = implode(",", $ids1);

$matches = $res['matches'];

// $obj->__p($res);
// 在SPH_MATCH_EXTENDED模式中，最终的权值是带权的词组评分和BM25权重的和，再乘以1000并四舍五入到整数。
// $max_weight = (array_sum($h['weights']) * count($res['words']) + 1) * 1000;
$max_weight = (array_sum(array($h['weights'])) * count($res['words']) + 1) * 1000;

$query = "SELECT * from contents where cid in (" . $ids . ")";
// $query = $obj->conf['coreseek']['query'];

$res = mysql_query($query);

if (mysql_num_rows($res) <= 0) {
    $summary = "查询 【" . $q . "】 没有发现匹配结果。";
    $obj -> display_summary($summary);
    return;
}

$rows = array();
while ($row = mysql_fetch_assoc($res, MYSQL_ASSOC)) {
    $row['relevance'] = ceil($matches[$row['cid']]['weight'] / $max_weight * 100);
    $rows[$row['cid']] = $row;
}

//Call Sphinxes BuildExcerpts function
$docs = array();
foreach ($ids1 as $c => $id) {
    $docs[$c] = strip_tags($rows[$id]['content']);
}
//echo "<br>-------[".$q."],[".$q1."]----------<br>\n";
//$obj->__p($docs);
$reply = $obj -> cl -> BuildExcerpts($docs, $obj -> conf['coreseek']['index'], $q);
//echo "<br>-------[".$obj->conf['coreseek']['index']."],[".$q."]----------<br>\n";
//$obj->__p($reply);

$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

if (isset($_POST['q'])) {
    $obj -> display($tdir1 . 'ss.tpl.html');
} else {
    $obj -> display($tdir1 . 'index.tpl.html');
}
?>
