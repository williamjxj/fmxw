<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

require_once(ROOT . 'coreseekClass.php');
try {
	$cl = new FMXW_Sphinx();
}
catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}
//$cl->assign('config', $config);
$cl->set_coreseek_server();
//$cl->set_sphinx_server();

list($tdir0, $tdir1, $tdir2) = array($config['t0'], $config['t1'], $config['t2']);
//header("Content-Type: text/html; charset=utf-8");

if (isset($_POST['js_form'])) {
	$q = mysql_real_escape_string($_POST['key']);
	$_SESSION[PACKAGE][SEARCH]['key'] = $q;
	$h = $cl->get_parse();
}
elseif (isset($_POST['key'])) {
	$q = mysql_real_escape_string($_POST['key']);
	$_SESSION[PACKAGE][SEARCH]['key'] = $q;
} 
elseif(isset($_GET['page'])) {
	echo "New Page: ".  $_GET['page'] . "<br>\n";
}
elseif(isset($_GET['test'])) {
	echo "<pre>"; print_r($cl); echo "</pre>";
	return;
}
elseif(isset($_GET['js_category'])) {
	echo json_encode($cl->get_categories());
	return;
}
elseif(isset($_GET['js_item'])) {
	echo json_encode($cl->get_items($_GET['cate_id']));
	return;
}
else {
	$cl->init();
	exit;
}

if(empty($q)) {
	$q = isset($_SESSION[PACKAGE][SEARCH]['key'])?$_SESSION[PACKAGE][SEARCH]['key']:'';
}

//在扩展查询模式SPH_MATCH_EXTENDED2中可以使用如下特殊运算符：
$extended2 = array (
	'屌丝 | 苍井空',
	'屌丝 -苍井空',
	'屌丝 !苍井空',
	'@title 屌丝 @content 苍井空',
	'@(title,content) 屌丝 @content 苍井空',
	'屌丝 苍井空',
	'@* 屌丝',
	'屌丝 苍井空',
);

//empty()= !isset($var) || $var == false.
if(empty($_GET['page'])) {
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

//
if($cl->h['limit'] > 100) $cl->h['limit'] = $cl->conf['page']['size'];
if(empty($cl->h['limit'])) $cl->h['limit'] = 30;

$cl->SetLimits($currentOffset,$cl->h['limit']); //current page and number of results

// Do the search
$res = $cl->Query($q, $cl->conf['coreseek']['index']);
if ( $res === false ) {
	echo "Query FAILED for $q: [at " . __FILE__ . ', ' . __LINE__. ']: ' . $cl->GetLastError() . "<br>\n";
	return;
}
else if ( $cl->GetLastWarning() ) {
	echo "WARNING for $q: [at " . __FILE__ . ', ' . __LINE__. ']: ' . $cl->GetLastWarning() . "<br>\n";
}

$query_info = "查询 【'".htmlentities($q)."'】 匹配结果为 ".count($res['matches'])." of 总共$res[total_found] matches in 时间$res[time] sec.\n";

$resultCount = $res['total_found'];
$numberOfPages = ceil($res['total']/$cl->conf['page']['size']);

if (! is_array($res["matches"])) {
	print "<pre class=\"results\">No Results for '".htmlentities($q)."'</pre>";
	return;
}
// Do a query to get additional document info (you could use SphinxSE instead)
$ids1 = array_keys($res['matches']);
$ids = join(",", $ids1);

# $db = $cl->mysql_connect_fmxw() or die("CAN'T connect");

$query = $cl->conf['coreseek']['query'];
$query = "SELECT * from contents where cid in (".$ids.")";
echo $query . "<br>\n";

$res = mysql_query($query);

if(mysql_num_rows($res)<=0) {
	echo "<pre>没有找到相关结果: " . htmlentites($q) . "</pre>";
	return;
}

if(mysql_num_rows($res) > 0) {

	$rows = array();
	while($row = mysql_fetch_assoc($res)) {
		// echo "<pre>"; print_r($row); echo "</pre>";
		$rows[$row['cid']] = $row;
	}
	
	//Call Sphinxes BuildExcerpts function
	if ($cl->conf['page']['content'] == 'excerpt') {
		$docs = array();
		foreach ($ids1 as $c => $id) {
			$docs[$c] = strip_tags($rows[$id]['content']);
		}
		$reply = $cl->BuildExcerpts($docs, $cl->conf['coreseek']['index'], $q);

		//echo "<pre>"; print_r($ids1); echo "</pre>";
		//echo "<pre>"; print_r($docs); echo "</pre>";
		//echo "<pre>"; print_r($reply); echo "</pre>";
	}
	
	if ($numberOfPages > 1 && $currentPage > 1) {
		print "<div class='pagination'>".$cl->pagesString($currentPage,$numberOfPages)."</div>";
	}
	
	//Actully display the Results
	print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";
	foreach ($ids1 as $c => $id) {
		$row = $rows[$id];
		
		$link = htmlentities(str_replace('$id',$row['cid'],$cl->conf['page']['link_format']));
		print "<li><a href=\"$link\">".($row['title'])."</a><br/>";
		
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
	
	print "<pre class=\"results\">$query_info</pre>";
}
?>
