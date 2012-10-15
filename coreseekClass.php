<?php
defined('ROOT') or define('ROOT', './');
require_once(ROOT . 'etc/sphinxapi.php');

class FMXW_Sphinx extends SphinxClient
{
	var $conf = array(), $db, $now;
	function __construct() {
		parent::SphinxClient();
		$this->conf = $this->get_config();
		$this->db = $this->mysql_connect_fmxw();
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

	function get_parse() {
		$where      = $_POST['where'];      // 'subject' or 'body'
		$tosearch   = $_POST['tosearch'];   // 'both' or 'main'
		$how        = $_POST['how'];        // 'all', 'any', 'exact' or 'boolean'
		$words      = $_POST['words'];      // search terms
		$namebox    = $_POST['namebox'];    // search name
		$newerval   = $_POST['newerval'];   // newer text
		$newertype  = $_POST['newertype'];  // d(ay), w(eek), m(onth) or y(ear)
		$olderval   = $_POST['olderval'];   // older text
		$oldertype  = $_POST['oldertype'];  // d(ay), w(eek), m(onth) or y(ear)
		$limit      = $_POST['limit'];      // # of results
		$sort       = $_POST['sort'];       // (r)elevance, (d)ate, (f)orum, (s)ubject or (u)sername
		$way        = $_POST['way'];        // (a)sc or (d)esc
		$page       = $_POST['page'];       // page of results
		$showmain   = $_POST['showmain'];   // show only one result per thread
		$forum      = $_POST['forum'];      // which forum to search
	}
			
	function get_dwmy() {
		return array('d'=>'86400', 'w'=>'604800', 'm'=>'2678400', 'y'=>'31536000');
	}
	function get_categories() {
		$ary = array();
		$sql = "select cid, name from categories order by weight";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res, MYSQL_NUM)) array_push($ary, $row);
		return $ary;
	}
	function get_items($cid) {
		$ary = array();
		$sql = "select iid, name from items where cid=$cid order by weight";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res, MYSQL_NUM)) array_push($ary, $row);
		return $ary;
	}

	function get_config() {
		return $conf = array(
			'coreseek' => array(
				'host' => 'localhost',
				'port' => 9313,
				'index' => "contents",
				'query' => 'SELECT * from contents where cid in ($ids)',
			),
			'sphinx' => array(
				'host' => 'localhost',
				'port' => 9312,
				'index' => "contents increment", 
				'query' => 'SELECT * from contents where cid in ($ids)',
			),
			'mysql' => array(
				'host' => "localhost:3563",
				'username' => "fmxw",
				'password' => "fmxw123456",
				'database' => "fmxw",
			),
			'page' => array(
				#can use 'excerpt' to highlight using the query, or 'asis' to show description as is.
				'content' => 'excerpt',
				#the link for the title (only $id) placeholder supported
				'link_format' => 'f3.php?cid=$id',
				#Change this to FALSE on a live site!
				'debug' => TRUE,
				#How many results per page
				'size' => 25,
				#maximum number of results - should match sphinxes max_matches. default 1000
				'max_matches' => 1000,
			)
		);
	}
	// 参看:/etc/my.cnf
	function mysql_connect_fmxw()
	{
		$db = mysql_pconnect($this->conf['mysql']['host'], $this->conf['mysql']['username'], $this->conf['mysql']['password']) or die(mysql_error());
		mysql_select_db($this->conf['mysql']['database'], $db);
		//设置字符集,  mysql_set_charset("utf8");
		mysql_query("SET NAMES 'utf8'", $db);
		return $db;
	}
	function set_coreseek_server()
	{
		$this->SetServer($this->conf['coreseek']['host'], $this->conf['coreseek']['port']);
		
		//$this->SetConnectTimeout ( 3 );
		//$this->SetArrayResult ( true );
		$this->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
		
		//(any, all, exact, boolean, extended,extended2)
		$mode = "extended2";
		$this->SetMatchMode($mode);
	}
	function set_sphinx_server()
	{
		$this->SetServer($this->conf['sphinx']['host'], $this->conf['sphinx']['port']);
		$this->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
		$this->SetMatchMode('extended2');
	}

	function get_form()
	{
?>
<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" id="search_form">
  <fieldset>
  <legend>负面新闻高级查询表单</legend>
  <table class="table table-striped table-bordered table-hover">
    <tbody>
      <tr>
        <td align="right"><span class="alert">查询词:</span></td>
        <td><input name="key" size="30" type="text" placeholder="比如：钓鱼岛争端 苍井空"  class="input-xlarge" data-content="请输入要查询的词，词组，语句。" data-original-title="查询关键词" /></td>
      </tr>
      <tr>
        <td colspan="2"><table class="table">
            <tr>
              <td align="right"><span class="">类别:</span></td>
              <td><select name="category" id="category" data-content="可选项：要查询哪个类别？" data-original-title="查询类别Category">
                  <option value="">--- 请选择 ---</option>
                </select></td>
              <td align="right"><span class="">栏目:</span></td>
              <td><select name="item" id="item" data-content="可选项：要查询哪个栏目类别？" data-original-title="查询栏目Item">
                  <option value="">--- 请选择 ---</option>
                </select></td>
            </tr>
          </table></td>
      </tr>
      <tr>
        <td colspan="2"><table class="table">
            <tr>
              <td align="right"><span class="">查询模式:</span></td>
              <td><select name="how" id="how" data-content="可选项：请选择查询模式，缺省：。" data-original-title="查询模式">
                  <option value="all" selected="selected">匹配全部单词all words</option>
                  <option value="any">匹配任何一个单词any words</option>
                  <option value="exact">准确匹配exact phrase</option>
                  <option value="boolean">布尔boolean</option>
                </select></td>
              <td><span class="">查询范围</span></td>
              <td><select name="where" id="where" data-content="可选项：请选择查询范围，缺省：标题和内容。" data-original-title="查询范围">
                  <option value="sc" selected="selected">标题和内容</option>
                  <option value="subject">标题</option>
                  <option value="content">内容</option>
                </select></td>
            </tr>
          </table></td>
      </tr>
      <tr>
        <td colspan="2"><table class="table">
            <tr>
              <td align="right"><span class="">查询的起始时间</span></td>
              <td><input name="newerval" id="newerval" size="2" type="text" class="input-xlarge" data-content="可选项：查询的起始时间。" data-original-title="查询的起始时间">
                <select name="newertype" id="newertype" data-content="可选项：查询的起始时间。" data-original-title="查询的起始时间">
                  <option value="d" selected="selected">天</option>
                  <option value="w">周</option>
                  <option value="m">月</option>
                  <option value="y">年</option>
                </select></td>
              <td>查询的终止时间</td>
              <td><input name="olderval" id="olderval" size="2" type="text" class="input-xlarge" data-content="可选项：查询的终止时间。" data-original-title="查询的终止时间。">
                <select name="oldertype" id="oldertype" data-content="可选项：查询的终止时间。" data-original-title="查询的终止时间">
                  <option value="d" selected="selected">天</option>
                  <option value="w">周</option>
                  <option value="m">月</option>
                  <option value="y">年</option>
                </select></td>
            </tr>
          </table></td>
      </tr>
      <tr>
        <td colspan="2"><table class="table">
            <tr>
              <td align="right"><span class="">最少查询词:</span></td>
              <td><input name="minwords" id="minwords" size="4" type="text" class="input-xlarge" data-content="可选项：最少查询词。" data-original-title="最少查询词"></td>
              <td><span class="">最多查询词:</span></td>
              <td><input name="maxwords" id="maxwords" size="4" type="text" class="input-xlarge" data-content="可选项：最多查询词。" data-original-title="最多查询词"></td>
            </tr>
          </table></td>
      <tr>
        <td colspan="2"><table class="table">
            <tr>
              <td><span class="">每页记录数:</span></td>
              <td><input name="limit" id="limit" value="25" size="3" type="text" class="input-xlarge" data-content="查询结果每页记录数。" data-original-title="查询结果每页记录数"></td>
              <td><span class="">排序方式:</span></td>
              <td><select name="sort" id="sort" data-content="查询结排序方式果。" data-original-title="查询结排序方式果">
                  <option value="r">相关性 relevance</option>
                  <option value="d">日期 date</option>
                  <option value="s">主题 title</option>
                  <option value="u">关注 guanzhu</option>
                  <option value="v">点击数 clicks</option>
                  <option value="p">回复 pinglun</option>
                  <option value="w">标签 tags</option>
                </select></td>
              <td>排序</td>
              <td><select name="way" id="way">
                  <option value="d">降序</option>
                  <option value="a">升序</option>
                </select></td>
            </tr>
          </table></td>
      <tr>
        <td colspan="2"><button class="btn btn-primary" type="submit"><i class="icon-white icon-search"></i>查 询 ！</button>
          <button class="btn" type="rest">重 置</button></td>
      </tr>
    </tbody>
  </table>
  </fieldset>
</form>
<script type="text/javascript">
$(function() {
	$('input:text, select', '#search_form').hover(function() {
		$(this).popover('show');
	});
	$('#ad_search').click(function(){
		var f = $('#search_form');
		if($(f).is(':visible')) $(f).hide();
		else $(f).animate().show();
		return false;
	});
	$('#search_form').submit(function(e) {
		var d = $('#div_list');
		var f = $(this);
		$.ajax({
			url: f.attr('action'),
			type: f.attr('method'),
			data: f.serialize()+'&js_form=1',
			cache: false,
			beforeSend: function() {
				$('<div></div>').addClass('ajaxloading').appendTo(d);
				$('button:submit', f).attr('disabled', true);
			},
			success: function(data) {
				d.html(data).fadeIn(100);
				$('button:submit', f).attr('disabed', false);
			}
		});
		return false;
	});
});
</script>
<?php    
	}    
    function init()
    {
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>负面新闻高级查询表单</title>
<link href="include/bootstrap/css/bootstrap.css" rel="stylesheet">
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script src="include/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
  <div class="hero-unit well-large">
    <h3 id="ad_search">负面新闻高级查询表单</h3>
    <?php $this->get_form(); ?>
  </div>
  <div id="div_list"></div>
</div>
</body>
</html>
<script type="text/javascript">
$(function() {
	$('#category').change(function() {
		cate_id = $(this).attr('value');
		$.getJSON("?js_item=1&cate_id="+cate_id, function(data) {
			var items = [];
			console.log(data);
			$.each(data, function(id, name) {
				items.push('<option value="' + name[0] + '">' + name[1] + '</option>');
			});
			$('#item').append(items);
		});
	});
	$('a', 'div.pagination').live('click', function() {
		var d = $('#div_list');
		d.html($('<div></div>').addClass('ajaxloading'));
		d.load($(this).attr('href')).fadeIn(200);
		return false;
	});
});
$(window).load(function() {
	$.getJSON('?js_category=1', function(data) {
		console.log(data);
		var cates = [];
		$.each(data, function(key, val) {
			cates.push('<option value="' + val[0] + '">' + val[1] + '</option>');
		});
		$('#category').append(cates);
	});
});
</script>
<?php
	}
	
	
	function linktoself($params,$selflink= '') {
		$a = array();
		$b = explode('?',$_SERVER['REQUEST_URI']);
		if (isset($b[1])) 
			parse_str($b[1],$a);
	
		if (isset($params['value']) && isset($a[$params['name']])) {
			if ($params['value'] == 'null') {
				unset($a[$params['name']]);
			} else {
				$a[$params['name']] = $params['value'];
			}
	
		} else {
			foreach ($params as $key => $value)
				$a[$key] = $value;
		}
	
		if (!empty($params['delete'])) {
			if (is_array($params['delete'])) {
				foreach ($params['delete'] as $del) {
					unset($a[$del]);
				}
			} else {
				unset($a[$params['delete']]);
			}
			unset($a['delete']);
		} 
		if (empty($selflink)) {
			$selflink = $_SERVER['SCRIPT_NAME'];
		} 
		if ($selflink == '/index.php') {
			$selflink = '/';
		}
	
		return htmlentities($selflink.(count($a)?("?".http_build_query($a,'','&')):''));
	}
	
	function pagesString($currentPage,$numberOfPages,$postfix = '',$extrahtml ='') {
		static $r;
		if (!empty($r))
			return($r);
	
		if ($currentPage > 1) 
			$r .= "<a href=\"".$this->linktoself(array('page'=>$currentPage-1))."$postfix\"$extrahtml>&lt; &lt; prev</a> ";
		$start = max(1,$currentPage-5);
		$endr = min($numberOfPages+1,$currentPage+8);
	
		if ($start > 1)
			$r .= "<a href=\"".$this->linktoself(array('page'=>1))."$postfix\"$extrahtml>1</a> ... ";
	
		for($index = $start;$index<$endr;$index++) {
			if ($index == $currentPage) 
				$r .= "<b>$index</b> "; 
			else
				$r .= "<a href=\"".$this->linktoself(array('page'=>$index))."$postfix\"$extrahtml>$index</a> ";
		}
		if ($endr < $numberOfPages+1) 
			$r .= "... ";
	
		if ($numberOfPages > $currentPage) 
			$r .= "<a href=\"".$this->linktoself(array('page'=>$currentPage+1))."$postfix\"$extrahtml>next &gt;&gt;</a> ";
	
		return $r;
	}

}
?>
