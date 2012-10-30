<?php
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
$obj -> assign('config', $config);

if (isset($_GET['q'])) {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
	//做过测试，'   '为真，empty('  ')为假。
    $key = $q = empty($_GET['q']) ? '' : trim($_GET['q']);
	
	$obj->set_keywords($key);
    $obj -> set_filter($key);

	if(isset($_GET['js_sortby'])) {
		switch($_GET['js_sortby']) {
			case 'day':
				$min = $obj->now - 86400;
				break;
			case 'week':
				$min = $obj->now - 604800;
				break;
			case 'month':
				$min = $obj->now - 2678400;
				break;
			case 'year':
				$min = $obj->now - 31536000;
				break;
			default:
				$min = 0;
		}
		$obj->cl->SetFilterRange("created", $min, $obj->now);
	}
}
elseif(isset($_GET['page'])) {
}
elseif(isset($_GET['js_get_content'])) {
    $row = $obj->get_content_1($_GET['cid']);
    $obj->assign('row', $row);
    $obj->display($tdir2.'single.tpl.html');
    return;
} 
elseif (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
}
else {
	echo "请输入查询词进行查询。";
	return;
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

//$obj -> cl->SetGroupBy('clicks', SPH_GROUPBY_ATTR);

$obj -> cl -> SetArrayResult(true);

$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "created DESC");

$res = $obj -> cl -> Query($q, $obj -> conf['coreseek']['index']);
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastError() . "<br>\n";
    return;
} else if ($obj -> cl -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}

if (empty($res["matches"])) {
    $summary = "查询【" . $q . "】 没有发现匹配结果，用时【" . $res['time'] . "】秒。";;
    $obj -> __p($summary);
    return;
}

$obj->set_session($res);

/*
 * SetArrayResult(true), $ary_ids = array_keys($res['matches']) ***not work***;
 * 得到本次查询的所有的cids($_GET， 总共最多25条)。
 */
$ary_ids = array_map("get_SetArrayResult_Ids", $res['matches']);

/* 将 cid=>weigth队放入matches中。
 */
$matchs = array();
foreach($res['matches'] as $v) {
	$matches[$v['id']] = $v['weight'];
}

/* 如何设置weights的缺省值？这里仿造：http://www.shroomery.org/forums/dosearch.php.txt
 * 结果不对。
 */
$weights = array('title'=>11, 'content'=>10);
$obj->cl->SetFieldWeights( $weights );

// 在SPH_MATCH_EXTENDED模式中，最终的权值是带权的词组评分和BM25权重的和，再乘以1000并四舍五入到整数。
if(empty($res['words'])) {
	$max_weight = (array_sum($weights) * count($res) + 1) * 1000;
}
else {
	$max_weight = (array_sum($weights) * count($res['words']) + 1) * 1000;
}

// 将ary_ids 由数组变成逗号分隔的字符串。
$ids = implode(",", $ary_ids);
// 生成 select cid, title, content, date(created) as date  from contents where cid in (ids) 的语句。
$query = $obj->generate_sql($ids);
//echo $query . "<br>\n";

// 查询MySQL，并将结果放入$mres数组中。
$mres = mysql_query($query);

if (mysql_num_rows($mres) <= 0) {
    $summary = "查询 【" . $q . "】 没有发现匹配结果，耗时约【".$res['time']."】 秒。";
    $obj -> __p($summary);
    return;
}

//生成要显示的完整记录，放入$rows数组中。以下唯一需要提升的是对content列进行BuildExcerpt()。
$rows = array();
while ($row = mysql_fetch_assoc($mres)) {
    $row['r'] = ceil($matches[$row['cid']] / $max_weight * 100); //relevance
	if (!preg_match("/(<b>|<em>)/", $row['title']))
		$row['title'] = $obj->mb_highlight($row['title'], $q, '<b>', '</b>');

    $rows[$row['cid']] = $row;
}

//strip_tags将所有'<>'全部去掉，很彻底。
$docs = array();
foreach ($ary_ids as $id) {
    $docs[$id] = strip_tags($rows[$id]['content']);
}

/* 这一步基本没有作用，应为返回总是FALSE.BuildExcerpts没有成功.
 * Call Sphinxes BuildExcerpts function
 */
$reply = $obj -> cl -> BuildExcerpts($docs, $obj -> conf['coreseek']['index'], $q);

//只好在手动做一遍。
if (empty($reply)) {
	foreach ($docs as $id => $ct) {
		$d1 = $obj->my_strip( $ct );
		$d2 = mb_substr($d1, 0, 150);
		$rows[$id]['content'] = $obj->mb_highlight($d2, $q, '<b>', '</b>');
	}
}
else {
	echo "8888888888888888888888888888888888888888888888<br>\n";
	foreach($docs as $id => $ct) {
		$rows[$id]['content'] = $reply[$id];
	}
}

$obj -> assign('results', $rows);

$pagination = $obj -> draw();
$obj -> assign("pagination", $pagination);
$obj -> assign("nav_template", $tdir1 . 'nav.tpl.html');	
$obj -> assign('kr', $obj->get_key_related($key));
	
$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));

$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

if (isset($_GET['page']) || isset($_GET['js_sortby'])) {
    // 以下是:去掉search.tpl.html ajax 部分,程序仍然能工作.
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
	$obj -> display($tdir1 . 'nav.tpl.html');
} 
else {
	$obj -> display($tdir1 . 'ss.tpl.html');
	if (!empty($_GET['q']))
		$obj->backend_scrape($_GET['q']);
}
exit;

//array_map()的callback回调函数。
function get_SetArrayResult_Ids($a) {
	return $a['id'];
}
?>
