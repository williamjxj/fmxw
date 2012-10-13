<?php
header("Content-Type: text/html; charset=utf-8");

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
	function get_categories() {
		$ary = array();
		$sql = "select * from categories";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res)) {
			array_push($ary, $row[1], $row[2]);
		}
		return $res;
	}
	function get_items() {
		$ary = array();
		$sql = "select * from items";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res)) {
			array_push($ary, $row[1], $row[2]);
		}
		return $ary;
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
	
	// 参看:/etc/my.cnf
	function mysql_connect_fmxw()
	{
		define("DBHOST", "localhost:3563");
		define('DBUSER', 'fmxw');
		define("DBPASS", "fmxw123456");
		define('DBNAME', 'fmxw');
		$db = mysql_pconnect("localhost:3563", DBUSER, DBPASS) or die(mysql_error());
		mysql_select_db(DBNAME, $db);
		//设置字符集,  mysql_set_charset("utf8");
		mysql_query("SET NAMES 'utf8'", $db);
		return $db;
	}
    
    function init()
    {
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Bootstrap 模板文件</title>
<link href="../include/bootstrap/css/bootstrap.css" rel="stylesheet">
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script src="../include/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
<div class="hero-unit well-large">
  <form action="" method="POST">
  <fieldset>
  <legend>FMXW</legend>
  <table class="table table-striped table-bordered table-hover">
    <tbody>
      <tr>
        <td colspan="2" class="tdheader">查询选项：</td>
      </tr>
      <tr>
        <td align="right">查询词:</td>
        <td><input name="words" size="30" type="text" placeholder="钓鱼岛争端" />
        </td>
      </tr>
      <tr>
        <td align="right">归档:</td>
        <td></td>
      </tr>
      <tr>
        <td align="right">栏目:</td>
        <td></td>
      </tr>
      <tr>
        <td align="right"> 查询模式: </td>
        <td><select name="how" id="how" onChange="searchMethod()" ;="">
            <option value="all">全部单词all words</option>
            <option value="any">每一个单词any words</option>
            <option value="exact">准确词exact phrase</option>
            <option value="boolean">boolean</option>
          </select>
          范围
          <select name="where" id="where">
            <option value="subject">标题</option>
            <option value="subject">内容</option>
            <option value="body" selected="selected">标题和内容</option>
          </select></td>
      </tr>
      <tr>
        <td align="right"> 时间早于: </td>
        <td><input name="newerval" id="newerval" value="" size="2" type="text">
          <select name="newertype" id="newertype" >
            <option value="d">日</option>
            <option value="w">周</option>
            <option value="m">月</option>
            <option value="y" selected="selected">年</option>
          </select>
          并且，时间晚于:
          <input name="olderval" id="olderval" value="" size="2" type="text">
          <select name="oldertype" id="oldertype" >
            <option value="d">日</option>
            <option value="w">周</option>
            <option value="m">月</option>
            <option value="y" selected="selected">年</option>
          </select></td>
      </tr>
      <tr>
        <td align="right">最少查询词:</td>
        <td><input name="minwords" id="minwords" value="" size="4" type="text">
          最多查询词:
          <input name="maxwords" id="maxwords" value="" size="4" type="text">
        </td>
      </tr>
      <tr>
        <td> 查询结果 每页记录数: </td>
        <td><input name="limit" id="limit" value="25" size="3" type="text">
          排序方式
          <select name="sort" id="sort">
            <option value="r">相关性 relevance</option>
            <option value="d">日期 date</option>
            <option value="s">主题 title</option>
            <option value="u">关注 guanzhu</option>
            <option value="v">点击数 clicks</option>
            <option value="p">回复 pinglun</option>
            <option value="w">标签 tags</option>
          </select>
          <select name="way" id="way">
            <option value="d">降序</option>
            <option value="a">升序</option>
          </select></td>
      </tr>
	  <tr>
	  	<td colspan="2"><button class="btn btn-primary"><i class=""></i>Submit</td>
	  </tr>
    </tbody>
  </table>
  </fieldset>
  </form>
</div>
</div>
</body>
</html>
<script type="text/javascript">
$(function() {
});
</script>
<?php
	}
}

$keyword = isset($_GET['key']) ? mysql_real_escape_string($_GET['key']) : "屌丝";
# $keyword = mysql_real_escape_string($keyword);

$cl = new FMXW_Sphinx();

$cl->init();

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
