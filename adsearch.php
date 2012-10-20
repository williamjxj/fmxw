<?php
session_start();
error_reporting(E_ALL);
defined('PACKAGE') or define('PACKAGE', 'fmxw');
defined('CS') or define('CS', 'coreseek_sphinx');
defined('SEARCH') or define('SEARCH', 'search');
defined("ROOT") or define("ROOT", "./");

require_once(ROOT . 'adsearchClass.php');
try {
	$cl = new FMXW_Sphinx();
}
catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

$cl->set_coreseek_server();
//$cl->set_sphinx_server();
//header("Content-Type: text/html; charset=utf-8");

/* 控制器部分 */
if(isset($_GET['js_category'])) {
	echo json_encode($cl->get_categories());
	return;
}
elseif(isset($_GET['js_item'])) {
	echo json_encode($cl->get_items($_GET['cate_id']));
	return;
}
elseif(isset($_GET['test'])) {
	echo "<pre>"; print_r($cl); echo "</pre>";
	return;
}
elseif(empty($_POST) && empty($_GET)) {
	$cl->init();
	exit;
}

//////////////////////////////////////////////////////////////
// js_form: 用户提交之后，调用ajax，instead of 直接form 调用.
if (isset($_POST['js_form'])) {
	$cl->get_parse();
	$cl->set_filter();
}

$h = $_SESSION[PACKAGE][CS];
$q = $_SESSION[PACKAGE][CS]['q'];
$q1 = $_SESSION[PACKAGE][CS]['key'];

// 设置当前页和开始的记录号码。
//empty()= !isset($var) || $var == false.
if (empty($_GET['page'])) {
	$currentPage = 1;
	$currentOffset = 0;
}
else {
	$currentPage = intval($_GET['page']);
	if (empty($currentPage) || $currentPage < 1) {$currentPage = 1;}
	
	$currentOffset = ($currentPage -1)* $cl->conf['page']['size'];
	
	if ($currentOffset > ($cl->conf['page']['max_matches']-$cl->conf['page']['size']) ) {
		die("Only the first {$cl->conf['page']['max_matches']} results accessible");
	}
}

$cl->SetLimits($currentOffset,$h['limit']); //current page and number of results


/** 开始查询Coreseek-Sphinx索引，并得到相关信息。
 * error, warning, status, fields+attrs, matches, total, total_found, time, words 
 */

$cl->SetArrayResult( true );

//created, pubdate, tags, pinglun, guanzhu, clicks, createdby, language, iid, cate_id
// $cl->SetGroupBy('pubdate', SPH_GROUPBY_MONTH, "@group DESC");
//$cl->SetGroupBy ('cate_id', SPH_GROUPBY_ATTR, "@count desc");
$cl->SetGroupBy ('clicks', SPH_GROUPBY_ATTR);
//$cl->SetGroupBy ('guanzhu', SPH_GROUPBY_ATTR, "@count desc");
//$cl->SetGroupBy ('pinglun', SPH_GROUPBY_ATTR, "@count desc");

$res = $cl->Query($q, $cl->conf['coreseek']['index']);
if ( $res === false ) {
	echo "查询失败 - ".$q.": [at " . __FILE__ . ', ' . __LINE__. ']: ' . $cl->GetLastError() . "<br>\n";
	return;
}
else if ( $cl->GetLastWarning() ) {
	echo "WARNING for ".$q.": [at " . __FILE__ . ', ' . __LINE__. ']: ' . $cl->GetLastWarning() . "<br>\n";
}

// $cl->pretty_print($res);

if (empty($res["matches"])) {
	$sec = "用时【" . $res['time'] . "】秒。";
	$summary =  "查询【".$q."】 没有发现匹配结果，" . $sec;
	$cl->display_summary($summary);
	return;
}

$resultCount = $res['total_found'];
$numberOfPages = ceil($res['total']/$cl->conf['page']['size']);
//Query 'test' retrieved 25 of 2617 matches in 0.000 sec.
$query_info = "查询词：【".$q."】， 用时【".$res['time']."】秒，匹配数【".$res['total']."】, 总共【".$res['total_found']."】条记录, 共【".$numberOfPages."】页，每页【".$cl->conf['page']['size']."】记录<br>\n";

$cl->display_summary($query_info);

$ids1 = array_keys($res['matches']);
$ids = implode(",", $ids1);

$matches = $res['matches'];

// $cl->__p($res);
// $max_weight = (array_sum($h['weights']) * count($res['words']) + 1) * 1000;
$max_weight = (array_sum(array($h['weights'])) * count($res['words']) + 1) * 1000;

$query = "SELECT * from contents where cid in (".$ids.")";
// $query = $cl->conf['coreseek']['query'];

//echo $query;
$res = mysql_query($query);

if(mysql_num_rows($res)<=0) {
	$summary =  "查询 【".$q."】 没有发现匹配结果。";
	$cl->display_summary($summary);
	return;
}

if(mysql_num_rows($res) > 0) {
	$rows = array();
	while($row = mysql_fetch_assoc($res)) {
		$row['relevance'] = ceil($matches[$row['cid']]['weight'] / $max_weight * 100);
		$rows[$row['cid']] = $row;
	}
	
	//Call Sphinxes BuildExcerpts function
	if ($cl->conf['page']['content'] == 'excerpt') {
		$docs = array();
		foreach ($ids1 as $c => $id) {
			$docs[$c] = strip_tags($rows[$id]['content']);
		}
		//echo "<br>-------[".$q."],[".$q1."]----------<br>\n";
		//$cl->__p($docs);
		$reply = $cl->BuildExcerpts($docs, $cl->conf['coreseek']['index'], $q);
		//echo "<br>-------[".$cl->conf['coreseek']['index']."],[".$q."]----------<br>\n";
		//$cl->__p($reply);
	}
	
	if ($numberOfPages > 1 && $currentPage > 1) {
		print "<div class='pagination'>".$cl->pagesString($currentPage,$numberOfPages)."</div>";
	}
	
	//Actully display the Results
	print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";
	foreach ($ids1 as $c => $id) {
		$row = $rows[$id];
		
		$link = htmlentities(str_replace('$id',$row['cid'],$cl->conf['page']['link_format']));
		print "<li><a href=\"$link\">".($row['title'])."</a>&nbsp;&nbsp;(" . $row['relevance'] .")<br/>";
		
		if ($cl->conf['page']['content'] == 'excerpt' && !empty($reply[$c]))
			print ($reply[$c])."</li>";
		else
			print $row['content']."</li>";
	}
	print "</ol>";
	
	if ($numberOfPages > 1) {
		print "<div class='pagination'>Page $currentPage of $numberOfPages. ";
		printf("Result %d..%d of %d. ",($currentOffset)+1,min(($currentOffset)+$cl->conf['page']['size'],$resultCount),$resultCount);
		print $cl->pagesString($currentPage,$numberOfPages)."</div>";
	}
	
	$cl->display_summary($query_info);

}
?>
