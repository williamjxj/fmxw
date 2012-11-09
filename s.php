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

list($tdir0, $tdir6) = array($config['t0'], $config['t6']);
$obj -> assign('config', $config);

if (isset($_GET['q'])) {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
    if (isset($_SESSION[PACKAGE]['sort'])) unset($_SESSION[PACKAGE]['sort']);

	//做过测试，'   '为真，empty('  ')为假。
	if(empty($_GET['q'])) {
	    $q = '';
		//ALL: SetMatchMode传递参数SPH_MATCH_ALL，然后在调用Query的时候指定要查询的索引是*
		$obj->cl->SetMatchMode(SPH_MATCH_ALL);
		
		//Sort by time segments (last hour/day/week/month) in descending order, and then by relevance in descending order.
		$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
	}
	else {
	    $q = trim($_GET['q']);
		/*XX mongoDB有这个关键词吗？有：更新，count+1,date. 无： insert */
		$obj->set_keywords($q);
		
		/* requiring perfect match.
		 * 准确匹配：将指定的全部词做为一个词组（不包括标点符号）构成查询
		 */
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
			$obj -> assign('_th', $obj -> get_header_label($header));
			$obj -> assign('_tf', $obj -> get_footer_label($footer));
			
			$obj -> assign('sitemap', $obj -> get_sitemap());
			$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');
			
			$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
			$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');
			$obj->display($tdir6.'ns.tpl.html');
			if (!empty($q)) {
				$obj->write_named_pipes($q); // $obj->backend_scrape($q);
			}
			exit;
		}

		$obj->cl->SetSortMode ( SPH_SORT_RELEVANCE );
		$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
		//参数必须是一个hash（关联数组），该hash将代表字段名字的字符串映射到一个整型的权值上。
		$obj->cl->SetFieldWeights(array('title' => 11, 'content' => 10));
	}
	
	//从首页来。
	if(isset($_GET['fm0'])) {}
	//从当前页来。
	elseif(isset($_GET['fm6'])) {}
}
elseif(isset($_GET['js_ct_search'])) {
	$obj->cl -> SetFilter('cate_id', array($_GET['category']));
	$_SESSION[PACKAGE]['sort'] = 'cate_id';

	if (!empty($_GET['item'])) {
		$obj->cl -> SetFilter('iid', array($_GET['item']));
		$_SESSION[PACKAGE]['sort'] = 'iid';
	}

	$q = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	// if (! empty($q)) $obj -> SetFilter("", array($q));
	
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);		
	$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
}
elseif(isset($_GET['js_sortby_dwmy'])) {
	switch($_GET['js_sortby_dwmy']) {
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
	$q = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	// if (! empty($q)) $obj -> SetFilter("@title", $q);
	$_SESSION[PACKAGE]['sort'] = 'created'; // $_GET['js_sortby_dwmy'];
	
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	//先按时间段（最近一小时/天/周/月）降序，再按相关度降序		
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
	$obj->cl->SetFilterRange("created", $min, $obj->now);
}
elseif(isset($_GET['js_sortby_attr'])) {
	$q = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	$_SESSION[PACKAGE]['sort'] = $_GET['js_sortby_attr'];

	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	
	/*
	 * 内部属性的名字必须用特殊符号@开头，用户属性按原样使用就行了
	 * @rank 和 @relevance 只是 @weight 的别名
	 * SPH_SORT_ATTR_DESC 等价于"attribute DESC, @weight DESC, @id ASC"
	  * =$obj->cl->SetSortMode(SPH_SORT_EXTENDED, $_GET['js_sortby_attr'].' desc, @weight DESC, @id ASC');
	 */
	$obj->cl->SetSortMode(SPH_SORT_ATTR_DESC, $_GET['js_sortby_attr']);
}
//以下不需要setmatchmode和setsortmode.
elseif(isset($_GET['js_pk'])) {
	$obj->display($tdir6.'pk.tpl.html');
	return;
}
elseif(isset($_POST['captcha']) && isset($_POST['pk'])) {
	$pid = $obj->insert_pk();
	//$obj->display($tdir6.'single.tpl.html');
	echo "你已经成功提交了如下信息：";
    echo json_encode($_POST);
	return;
}
elseif(isset($_GET['js_category'])) {
	echo json_encode($obj->get_categories());
	return;
}
elseif(isset($_GET['js_item'])) {
	echo json_encode($obj->get_items($_GET['cate_id']));
	return;
}
elseif(isset($_GET['page'])) {
	//翻页显示。
	$q = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
}
elseif(isset($_GET['jsc'])) {
    $row = $obj->get_content_1($_GET['cid']);
    $obj->assign('row', $row);
    $obj->display($tdir6.'single.tpl.html');
    return;
}
elseif (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
	$obj->__p($_REQUEST);
	$obj->__p($_SESSION);
	return;
}
//要区分fm0，fm6吗？
else {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
    if (isset($_SESSION[PACKAGE]['sort'])) unset($_SESSION[PACKAGE]['sort']);
	$q = '';
	$obj->cl->SetMatchMode(SPH_MATCH_ALL);
	
	//Sort by time segments (last hour/day/week/month) in descending order, and then by relevance in descending order.
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
	$obj->cl->SetArrayResult(true);
}

// if(empty($_GET))  goto BASIC;
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
//current page and number of results
$obj -> cl -> SetLimits($currentOffset, $obj->conf['page']['limit']);

/** 开始查询Coreseek-Sphinx索引，并得到相关信息。
 * error, warning, status, fields+attrs, matches, total, total_found, time, words
 */
$res = $obj -> cl -> Query($q, $obj -> conf['coreseek']['index']);
if ($res === false) {
    echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastError() . "<br>\n";
    return;
} else if ($obj -> cl -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}

$_SESSION[PACKAGE][SEARCH]['key'] = empty($q) ? '' : trim($q);

if (empty($res["matches"])) {
    //$summary = "查询【" . $q . "】 没有发现匹配结果，用时【" . $res['time'] . "】秒。";
	//$obj -> __p($summary);
	//SPH_MATCH_PHRASE, 将整个查询看作一个词组，要求按顺序完整匹配; 找不到结果，就直接将显示抓取来的。
	$obj -> assign('_th', $obj -> get_header_label($header));
	$obj -> assign('_tf', $obj -> get_footer_label($footer));
	
	$obj -> assign('sitemap', $obj -> get_sitemap());
	$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');
	
	$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
	$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');
	$obj->display($tdir6.'ns.tpl.html');
	if (!empty($q)) {
		$obj->write_named_pipes($q); // $obj->backend_scrape($q);
	}
	exit;
}

//取得数据成功后，设置SESSION.
// $obj->set_session($res);
$_SESSION[PACKAGE][SEARCH]['page'] = empty($_GET['page']) ? 1 : $_GET['page'];
$_SESSION[PACKAGE][SEARCH]['total'] = $res['total'];
$_SESSION[PACKAGE][SEARCH]['total_pages'] = ceil($res['total'] / ROWS_PER_PAGE);
$_SESSION[PACKAGE][SEARCH]['total_found'] = $res['total_found'];
$_SESSION[PACKAGE][SEARCH]['time'] = $res['time'];

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
// $query = $obj->generate_sql($ids);
// 生成 select cid, title, content, date(created) as date  from contents where cid in (ids) 的语句。
$query = "select *, date(created) as date from contents where cid in (" . $ids . ")";
if (!empty($_SESSION[PACKAGE]['sort'])) {
	$query .= " ORDER BY " . $_SESSION[PACKAGE]['sort'] . " DESC ";
	$t = $_SESSION[PACKAGE]['sort'];
	if(preg_match("/(cate_id|iid)/", $t))
		$query .= " , created DESC ";
}
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
$obj -> assign('reping', $obj -> get_repings($q));
//$obj->__p($obj -> get_repings($q));

//BASIC:
$obj -> assign("nav_template", $tdir6 . 'nav.tpl.html');	
$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));

$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

if (isset($_GET['page']) || isset($_GET['js_sortby_dwmy'])  || isset($_GET['js_sortby_attr']) || isset($_GET['js_ct_search']) ) {
    // 以下是:去掉search.tpl.html ajax 部分,程序仍然能工作.
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
	$obj -> display($tdir6 . 'nav.tpl.html');
} 
else {
    $pagination = $obj -> draw();
	$obj -> assign("pagination", $pagination);
	$obj -> display($tdir6 . 'ss.tpl.html');
	if (!empty($_GET['q'])) $obj->write_named_pipes(trim($_GET['q']));
}
exit;

//array_map()的callback回调函数。
function get_SetArrayResult_Ids($a) {
	return $a['id'];
}
?>
