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
catch(catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}
$cl->assign('config', $config);

list($tdir0, $tdir1, $tdir2) = array($config['t0'], $config['t1'], $config['t2']);
//header("Content-Type: text/html; charset=utf-8");

if (isset($_GET['key'])) {
	$q = isset($_GET['key']) ? mysql_real_escape_string($_GET['key']) : "屌丝";
} 
elseif(isset($_GET['test'])) {
	$cl->__p('');
}
else {
	$cl->init();
	exit;
}

$cl->set_sphinx_server();


//在扩展查询模式SPH_MATCH_EXTENDED2中可以使用如下特殊运算符：
$extended2 = array (
	'屌丝 | 苍井空',
	'屌丝 -苍井空',
	'屌丝 !苍井空',
	'@title 屌丝 @body 苍井空',
	'@(title,body) 屌丝 @body 苍井空',
	'屌丝 苍井空',
	'@* 屌丝',
	'屌丝 苍井空',
);

//设置 返回的数据为数组结构
// $c1->SetArrayResult(true);

//$cl->SetFilter( "is_dirty", array (1) );

$cl->SetLimits(0,$cl->conf['page_size']); //current page and number of results

// Some variables which are used throughout the script
$now = time();

////////////////////////////////////

$index = "contents";

// Do the search
$res = $cl->Query($q, $index);
if ( $res === false ) {
	echo "Query FAILED for $q: [at " . __FILE__ . ', ' . __LINE__. ']: ' . $cl->GetLastError() . "<br>\n";
	exit;
}
else if ( $cl->GetLastWarning() ) {
	echo "WARNING for $q: [at " . __FILE__ . ', ' . __LINE__. ']: ' . $cl->GetLastWarning() . "<br>\n";
}

// Do a query to get additional document info (you could use SphinxSE instead)
$ids = join(",", array_keys($res['matches']);

echo "<pre>"; print_r($ids); echo "</pre>";

$opts = array(
	#格式化摘要，高亮字体设置
	#在匹配关键字之前插入的字符串，默认是<b>
	"before_match" => "<span style='font-weight:bold;color:red'>",
	#在匹配关键字之后插入的字符串，默认是</b>
	"after_match" => "</span>"
);

$weights = 1;
$max_weight = 1000;
// Max possible weight can be used to calculate absolute relevance for results.
#$max_weight = (array_sum($weights) * count($res['words']) + 1) * 1000;

$db = $cl->mysql_connect_fmxw() or die("CAN'T connect");

$query = "SELECT * from contents where cid in (".$ids.")";
echo $query . "<br>\n";

$res = mysql_query($query, $db);
echo mysql_num_rows($res). "<br>\n";

echo "<table>";

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
	echo "<td>" . $row['content'] .  "</td>\n";
	echo "</tr>\n";
}
echo "</table>";

// Results are in the $matches array
// echo nl2br(print_r($matches, true));

//mysql_free_result($res);
mysql_close();
?>
