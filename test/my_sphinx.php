<?php
header("Content-Type:text/html;charset=utf-8");

require_once('../etc/sphinxapi.php');

class FMXW_Sphinx extends SphinxClient
{
	var $conf = array(), $now;
	function __construct() {
		parent::SphinxClient();
		$this->conf = $this->set_sphinx();
	}

	function get_mode($mode) {
		switch($mode){
		case "any":
			$this->SetMatchMode(SPH_MATCH_ANY);
			break;
		case "all":
			$this->SetMatchMode(SPH_MATCH_ALL);
			break;
		case "exact":
			$this->SetMatchMode(SPH_MATCH_PHRASE);
			break;
		case "boolean":
			$this->SetMatchMode(SPH_MATCH_BOOLEAN);
			break;
		case "extended":
			$this->SetMatchMode(SPH_MATCH_EXTENDED);
			break;
		case "extended2":
			$this->SetMatchMode(SPH_MATCH_EXTENDED2);
			break;
		case NULL:
			$this->SetMatchMode(SPH_MATCH_ANY);
			break;
		}
		return $mode;
	}
		
	function get_dwmy() {
		return array('d'=>'86400', 'w'=>'604800', 'm'=>'2678400', 'y'=>'31536000');
	}
	
	function set_sphinx() {
	  return array(
		'sphinx_host' => 'localhost',
		'sphinx_port' => 9312, //this demo uses the SphinxAPI interface
	
		'mysql_host' => "localhost",
		'mysql_username' => "fmxw",
		'mysql_password' => "fmxw123456",
		'mysql_database' => "fmxw",
	
		'sphinx_index' => "mysql", 
	
		#can use 'excerpt' to highlight using the query, or 'asis' to show description as is.
		'body' => 'excerpt',
	
		#the link for the title (only $id) placeholder supported
		'link_format' => '/page.php?page_id=$id',
	
		#Change this to FALSE on a live site!
		'debug' => TRUE,
	
		#How many results per page
		'page_size' => 25,
	
		#maximum number of results - should match sphinxes max_matches. default 1000
		'max_matches' => 1000,
	  );
	}
	
	function mysql_connect_fmxw()
	{
		define("DBHOST", "localhost:35630000");
		define('DBUSER', 'fmxw');
		define("DBPASS", "fmxw123456");
		define('DBNAME', 'fmxw');
		$db = mysql_pconnect("localhost:3563", DBUSER, DBPASS) or die(mysql_error());
		mysql_select_db(DBNAME, $db);
		//设置字符集
		//mysql_set_charset("utf8");
		mysql_query("SET NAMES 'utf8'", $db);
		return $db;
	}
}

$keyword = isset($_GET['key']) ? mysql_real_escape_string($_GET['key']) : "屌丝";
# $keyword = mysql_real_escape_string($keyword);

$cl = new FMXW_Sphinx();

$cl->SetServer($cl->conf['sphinx_host'], $cl->conf['sphinx_port']);

//$cl->SetConnectTimeout ( 3 );
//$cl->SetArrayResult ( true );
$cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");

//(any, all, exact, boolean, extended,extended2)
$mode = "extended2";
$cl->SetMatchMode($mode);

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

$index = "mysql";

// Do the search
$res = $cl->Query($keyword, $index);
if ( $res === false ) {
	echo "Query failed: " . $cl->GetLastError() . "<br>\n";
	exit;
}
else if ( $cl->GetLastWarning() ) {
	echo "WARNING: " . $cl->GetLastWarning() . "<br>\n";
}

// Do a query to get additional document info (you could use SphinxSE instead)
$ids = array_keys($res['matches']);
$ids = join(",", $ids);

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
  
	// $res = $cl->buildExcerpts($row,"mysql",$keyword,$opts);
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
