<?php
defined('ROOT') or define('ROOT', './');
require_once(ROOT . 'etc/sphinxapi.php');

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
		'sphinx_port' => 9313, //this demo uses the SphinxAPI interface	
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
	function set_sphinx_server() 
	{
		$this->SetServer($this->conf['sphinx_host'], $this->conf['sphinx_port']);
		
		//$this->SetConnectTimeout ( 3 );
		//$this->SetArrayResult ( true );
		$this->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
		
		//(any, all, exact, boolean, extended,extended2)
		$mode = "extended2";
		$this->SetMatchMode($mode);
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
<link href="include/bootstrap/css/bootstrap.css" rel="stylesheet">
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script src="include/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
  <div class="hero-unit well-large">
    <h3 id="ad_search">负面新闻高级查询表单</h3>
    <form action="" method="POST" id="ad_form">
      <fieldset>
      <legend>负面新闻高级查询表单</legend>
      <table class="table table-striped table-bordered table-hover">
        <tbody>
          <tr>
            <td colspan="2" >查询选项：</td>
          </tr>
          <tr>
            <td align="right">
            <label class="">
            查询词:
            <label>
            </td>
<<<<<<< HEAD
            <td><input name="words" size="30" type="text" placeholder="钓鱼岛争端"  class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证" />
=======
            <td><input name="key" size="30" type="text" placeholder="钓鱼岛争端"  class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证" />
>>>>>>> 8aad20dcb3ad35cd21d71831d3d3725696cf6ac1
            </td>
          </tr>
          <tr>
            <td align="right"><label class="">归档:</label></td>
            <td></td>
          </tr>
          <tr>
            <td align="right"><label class="">栏目:</label></td>
            <td></td>
          </tr>
          <tr>
            <td align="right"><label class=""> 查询模式: </label></td>
            <td><select name="how" id="how" onChange="searchMethod()" data-content="用户名栏不能为空。" data-original-title="用户名验证">
                <option value="all">全部单词all words</option>
                <option value="any">每一个单词any words</option>
                <option value="exact">准确词exact phrase</option>
                <option value="boolean">boolean</option>
              </select>
              <label class=""> 范围</label>
              <select name="where" id="where" data-content="用户名栏不能为空。" data-original-title="用户名验证">
                <option value="subject">标题</option>
                <option value="subject">内容</option>
                <option value="body" selected="selected">标题和内容</option>
              </select></td>
          </tr>
          <tr>
            <td align="right"><label class="">时间早于:</label>
            </td>
            <td><input name="newerval" id="newerval" value="" size="2" type="text" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证">
              <select name="newertype" id="newertype" data-content="用户名栏不能为空。" data-original-title="用户名验证">
                <option value="d">日</option>
                <option value="w">周</option>
                <option value="m">月</option>
                <option value="y" selected="selected">年</option>
              </select>
              并且，时间晚于:
              <input name="olderval" id="olderval" value="" size="2" type="text" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证">
              <select name="oldertype" id="oldertype" data-content="用户名栏不能为空。" data-original-title="用户名验证">
                <option value="d">日</option>
                <option value="w">周</option>
                <option value="m">月</option>
                <option value="y" selected="selected">年</option>
              </select></td>
          </tr>
          <tr>
            <td align="right"><label class="">最少查询词:</label></td>
            <td><input name="minwords" id="minwords" value="" size="4" type="text" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证">
              <label class=""> 最多查询词:</label>
              <input name="maxwords" id="maxwords" value="" size="4" type="text" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证">
            </td>
          </tr>
          <tr>
            <td><label class=""> 查询结果 每页记录数: </label></td>
            <td><input name="limit" id="limit" value="25" size="3" type="text" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证">
              排序方式
              <select name="sort" id="sort" data-content="用户名栏不能为空。" data-original-title="用户名验证">
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
            <td colspan="2"><button class="btn btn-primary" type="submit"> <i class="icon-white icon-search"></i>查询</button>
              <button class="btn" type="rest"> 查询</button></td>
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
	$('input:text, select', '#ad_form').hover(function() {
		$(this).popover('show');
	});
	$('#ad_search').click(function(){
		var f = $('#ad_form');
		if($(f).is(':visible')) $(f).hide();
		else $(f).animate().show();
		return false;
	});
	$('#cate').live('change', function() {
		$('#item').load('?cate_id=1&js_item=1');
	});
});
$(window).load(function() {
	// load category select list.
	$('#cate').load('?cate=1');
});
</script>
<?php
	}
}
