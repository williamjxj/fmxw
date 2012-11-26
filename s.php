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
    $obj = new FMXW();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

$obj -> set_coreseek_server();

list($tdir0, $tdir6) = array($config['t0'], $config['t6']);
$obj -> assign('config', $config);

list($q, $key, $sort) = array('', '', '');

if (isset($_GET['q'])) {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
	$obj->s = $obj->init_search();

	if(empty($_GET['q'])) {
	    $q = $key = '';
		
		$obj->cl->SetMatchMode(SPH_MATCH_FULLSCAN);
		
		$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
	}
	else {
	    $q = trim($_GET['q']);

		$obj->set_keywords($q);
		
		$obj->cl->SetMatchMode(SPH_MATCH_PHRASE);
		
		$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
		
		$obj->cl->SetLimits(0, 10);

		$res = $obj -> cl -> Query($q, $obj -> conf['coreseek']['index']);
		if ($res === false) {
			echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastError() . "<br>\n";
			return;
		} else if ($obj -> cl -> GetLastWarning()) {
			echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
		}
		if (empty($res["matches"])) {
			$_SESSION[PACKAGE][SEARCH]['key'] = $q;
			$obj -> assign('_th', $obj -> get_header_label($header));
			$obj -> assign('_tf', $obj -> get_footer_label($footer));			
			$obj -> assign('sitemap', $obj -> get_sitemap());
			$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
			$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');
			$obj->display($tdir6.'ns.tpl.html');

			$obj->write_named_pipes($q, __LINE__);
			return;
		}

		$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);

		$weights = array('title'=>11, 'content'=>10);

		$obj->cl->SetFieldWeights( $weights );
	}

    $obj -> cl -> SetArrayResult(true);
}
elseif(isset($_GET['js_dwmy'])) {
	switch($_GET['js_dwmy']) {
		case 'day24':
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
	$obj->s['sort'] = 'created';

	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);

	//先按时间段（最近一小时/天/周/月）降序，再按相关度降序	
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');

	$obj->cl->SetFilterRange("created", $min, $obj->now);
}
elseif(isset($_GET['js_core'])) {
	$q = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	switch($_GET['js_core']) {
		case 1: //负面度
			$key = $q . $e;
			$obj->s['sort'] = 1;
			break;
		case 2: //相关度
			$key = $q;
			$obj->s['sort'] = 1;
			break;
		case 3: //评论数
			$key = $q;
			$obj->s['sort'] = 2;
			break;
		default:
			$key = $q;
			$obj->s['sort'] = 2;
	}
	
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
}
//翻页显示。
elseif(isset($_GET['page'])) {
	$obj->s = $_SESSION[PACKAGE][SEARCH];
}
elseif(isset($_GET['js_attr'])) {

	$obj->s['sort'] = $_GET['js_attr'];

	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	
	$obj->cl->SetSortMode(SPH_SORT_ATTR_DESC, $_GET['js_attr']);
}
elseif(isset($_GET['js_ct_search'])) {

	$obj->cl -> SetFilter('cate_id', array($_GET['category']));

	$obj->s['sort'] = 'cate_id';

	if (!empty($_GET['item'])) {
		$obj->cl -> SetFilter('iid', array($_GET['item']));
		$obj->s['sort'] = 'iid';
	}

	$q = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);		

	$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
}
else {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
	$q = $key = '';
	
	$obj->cl->SetMatchMode(SPH_MATCH_ALL);
	
	//Sort by time segments (last hour/day24/week/month) in descending order, and then by relevance in descending order.
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');

	$obj->cl->SetArrayResult(true);
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
		$currentOffset = $obj->conf['page']['max_matches'] - $obj->conf['page']['limit'];
    }
}
$obj -> cl -> SetLimits($currentOffset, $obj->conf['page']['limit']);

$res = $obj -> cl -> Query($key, $obj -> conf['coreseek']['index']);
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastError() . "<br>\n";
    return;
}
elseif ($obj -> cl -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}

if (empty($res["matches"])) {
	$_SESSION[PACKAGE][SEARCH]['key'] = empty($q) ? '' : trim($q);
	$obj -> assign('_th', $obj -> get_header_label($header));
	$obj -> assign('_tf', $obj -> get_footer_label($footer));	
	$obj -> assign('sitemap', $obj -> get_sitemap());
	$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
	$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');
	$obj->display($tdir6.'ns.tpl.html');
	if (!empty($q)) {
		$obj->write_named_pipes($q, __LINE__); // $obj->backend_scrape($q);
	}
	return;
}

// 取得数据成功后，设置SESSION.
$obj->s['page'] = empty($_GET['page']) ? 1 : $_GET['page'];
$obj->s['total'] = $res['total'];
$obj->s['total_pages'] = ceil($res['total'] / ROWS_PER_PAGE);
$obj->s['total_found'] = $res['total_found'];
$obj->s['time'] = $res['time'];

$_SESSION[PACKAGE][SEARCH] = $obj->s;

/*
 * SetArrayResult(true), $ary_ids = array_keys($res['matches']) ***not work***;
 * 得到本次查询的所有的cids($_GET， 总共最多25条)。
 */
$ary_ids = array_map("get_SetArrayResult_Ids", $res['matches']);

/* 将 cid=>weigth队放入matches中。
 */
$matches = array();
foreach($res['matches'] as $v) {
	$matches[$v['id']] = $v['weight'];
}

// $obj->__p($matches);
/* 如何设置weights的缺省值？这里仿造：http://www.shroomery.org/forums/dosearch.php.txt
 * 结果不对。
 */
if(empty($weights)) {
	$weights = array('title'=>11, 'content'=>10);
	$obj->cl->SetFieldWeights( $weights );
}

// 在SPH_MATCH_EXTENDED模式中，最终的权值是带权的词组评分和BM25权重的和，再乘以1000并四舍五入到整数。
if(empty($res['words'])) {
	$max_weight = (array_sum($weights) * count($res) + 1) * 1000;
}
else {
	$max_weight = (array_sum($weights) * count($res['words']) + 1) * 1000;
}

// 将ary_ids 由数组变成逗号分隔的字符串。
$ids = implode(",", $ary_ids);
// $query = $obj->generate_sql($ids);
// 生成 select cid, title, content, date(created) as date  from contents where cid in (ids) 的语句。
$query = "select *, date(created) as date from contents where cid in (" . $ids . ")";

if (!empty($_SESSION[PACKAGE][SEARCH]['sort'])) {
	$query .= " ORDER BY " . $_SESSION[PACKAGE][SEARCH]['sort'] . " DESC ";
	$t = $_SESSION[PACKAGE][SEARCH]['sort'];
	if(preg_match("/(cate_id|iid)/", $t))
		$query .= " , created DESC ";
}
else
	$query .= ' ORDER BY FIELD(cid, ' .  $ids . ")";
// echo $query;

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
	//echo "[".$matches[$row['cid']]."], [".$max_weight."]<br>\n";
    $relevance = ceil(($matches[$row['cid']] / $max_weight) * 100); //relevance
	if($relevance>100) $relevance = 100;
	if($relevance<1) $relevance = 1;
	$row['r'] = $relevance;
	//echo "[".$matches[$row['cid']]['weight']."], [".$relevance."]<br>\n";
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

//只好再手动做一遍。
if (empty($reply)) {
	foreach ($docs as $id => $ct) {
		if (preg_match("/_top/", $rows[$id]['createdby'])) {
			$rows[$id]['content'] = '';
		}
		else {
			$d1 = $obj->my_strip( $ct );
			$d2 = mb_substr($d1, 0, 150);
			$rows[$id]['content'] = $obj->mb_highlight($d2, $q, '<b>', '</b>');
		}
	}
}
else {
	echo "Why Never Come Here: 88888888<br>\n";
	foreach($docs as $id => $ct) {
		$rows[$id]['content'] = $reply[$id];
	}
}

$obj -> assign('results', $rows);

$pagination = $obj -> draw();
$obj -> assign("pagination", $pagination);
$obj -> assign('kr', $obj->get_key_related($q));

$obj -> assign("nav_template", $tdir6 . 'nav.tpl.html');	
$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));

$obj -> assign('sitemap', $obj -> get_sitemap());

$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

$http_get = array('page', 'js_dwmy', 'js_attr', 'js_core', 'js_ct_search');
if(in_array(key($_GET), $http_get) {}

if (isset($_GET['page']) || isset($_GET['js_dwmy'])  || isset($_GET['js_attr']) || isset($_GET['js_ct_search']) || isset($_GET['js_core']) ) {
    // 以下是:去掉search.tpl.html ajax 部分,程序仍然能工作.
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
	$obj -> display($tdir6 . 'nav.tpl.html');
} 
else {
    $pagination = $obj -> draw();
	$obj -> assign("pagination", $pagination);
	$obj -> display($tdir6 . 'ss.tpl.html');
	if (!empty($_GET['q'])) $obj->write_named_pipes(trim($_GET['q']), __LINE__);
}
exit;

//array_map()的callback回调函数。
function get_SetArrayResult_Ids($a) {
	return $a['id'];
}
?>
