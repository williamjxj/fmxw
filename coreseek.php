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

if (isset($_POST['key'])) {
	$q = isset($_POST['key']) ? mysql_real_escape_string($_POST['key']) : "屌丝";
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

$cl->get_form();
//empty()= !isset($var) || $var == false.
if(empty($_GET['page'])) {
	$currentPage = 1;
	$currentOffset = 0;
}
else {
	$currentPage = intval($_GET['page']);
	if (empty($currentPage) || $currentPage < 1) {$currentPage = 1;}
	
	$currentOffset = ($currentPage -1)* $cl->conf['page']['page_size'];
	
	if ($currentOffset > ($cl->conf['page']['max_matches']-$cl->conf['page']['page_size']) ) {
		die("Only the first {$cl->conf['page']['max_matches']} results accessible");
	}
}

//设置 返回的数据为数组结构
// $c1->SetArrayResult(true);
//$cl->SetFilter( "is_dirty", array (1) );

$cl->SetLimits($currentOffset,$cl->conf['page']['page_size']); //current page and number of results

// Some variables which are used throughout the script
$now = time();

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
$numberOfPages = ceil($res['total']/$cl->conf['page']['page_size']);

if (! is_array($res["matches"])) {
	print "<pre class=\"results\">No Results for '".htmlentities($q)."'</pre>";
	return;
}
// Do a query to get additional document info (you could use SphinxSE instead)
$ids = join(",", array_keys($res['matches']));

# $db = $cl->mysql_connect_fmxw() or die("CAN'T connect");

$query = "SELECT * from contents where cid in (".$ids.")";
echo $query . "<br>\n";

$res = mysql_query($query);

if(mysql_num_rows($res)<=0) {
	echo "<pre>没有找到相关结果: " . htmlentites($q) . "</pre>";
	return;
}

if(mysql_num_rows($res) > 0) {

	$rows = array();
	while($row = mysql_fetch_array($res)) {
		echo "<pre>"; print_r($row); echo "</pre>";
		$rows[$row['cid']] = $row;
	}
	
	//Call Sphinxes BuildExcerpts function
	if ($cl->conf['page']['body'] == 'excerpt') {
		$docs = array();
		foreach ($ids as $c => $id) {
			$docs[$c] = strip_tags($rows[$id]['content']);
		}
		$reply = $cl->BuildExcerpts($docs, $cl->conf['coreseek']['index'], $q);
	}
	
	if ($numberOfPages > 1 && $currentPage > 1) {
		print "<p class='pages'>".$cl->pagesString($currentPage,$numberOfPages)."</p>";
	}
	
	//Actully display the Results
	print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";
	foreach ($ids as $c => $id) {
		$row = $rows[$id];
		
		$link = htmlentities(str_replace('$id',$row['id'],$cl->conf['page']['link_format']));
		print "<li><a href=\"$link\">".htmlentities($row['title'])."</a><br/>";
		
		if ($cl->conf['page']['body'] == 'excerpt' && !empty($reply[$c]))
			print ($reply[$c])."</li>";
		else
			print htmlentities($row['content'])."</li>";
	}
	print "</ol>";
	
	if ($numberOfPages > 1) {
		print "<p class='pages'>Page $currentPage of $numberOfPages. ";
		printf("Result %d..%d of %d. ",($currentOffset)+1,min(($currentOffset)+$cl->conf['page']['page_size'],$resultCount),$resultCount);
		print $cl->pagesString($currentPage,$numberOfPages)."</p>";
	}
	
	print "<pre class=\"results\">$query_info</pre>";
}

/*
echo '<table class="table table-striped table-bordered table-hover">';

while ($row = mysql_fetch_array($res)) {
	// Calculate relevance percentage
	// $row['Percent'] = ceil($res['matches'][$row['B_Number']]['weight'] / $max_weight * 100);
	//$matches[] = $row;
	
	// $res = $cl->buildExcerpts($row,"mysql",$q,$opts);
	//echo "标题：".$row[1]."<br />";
	//echo "内容：".$row[2]."<br />";
	//echo "<hr>";
	//echo "<pre>"; print_r($row); echo "</pre>";
	echo "<tr>\n";
	echo "<td>" . $row['title'] .  "</td>\n";
	echo "</tr>\n";
}
echo "</table>";

// Results are in the $matches array
// echo nl2br(print_r($matches, true));

//mysql_free_result($res);
mysql_close();
*/
?>
