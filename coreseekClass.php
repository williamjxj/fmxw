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
			),
			'sphinx' => array(
				'host' => 'localhost',
				'port' => 9312,
				'index' => "contents increment_contents", 
			),
			'mysql' => array(
				'host' => "localhost:3563",
				'username' => "fmxw",
				'password' => "fmxw123456",
				'database' => "fmxw",
			),
			'page' => array(
				#can use 'excerpt' to highlight using the query, or 'asis' to show description as is.
				'body' => 'excerpt',
				#the link for the title (only $id) placeholder supported
				'link_format' => '?page_id=$id',
				#Change this to FALSE on a live site!
				'debug' => TRUE,
				#How many results per page
				'page_size' => 25,
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
<form action="" method="POST" id="ad_form">
  <fieldset>
  <legend>负面新闻高级查询表单</legend>
  <table class="table table-striped table-bordered table-hover">
    <tbody>
      <tr>
        <th colspan="2" >查询选项：</th>
      </tr>
      <tr>
        <td align="right"><label class="aaa">查询词:</label></td>
        <td><input name="key" size="30" type="text" placeholder="钓鱼岛争端"  class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证" />
      </tr>
      <tr>
        <td align="right"><label class="">归档:</label></td>
        <td><select name="category" id="category" onChange="" data-content="用户名栏不能为空。" data-original-title="用户名验证"></select></td>
      </tr>
      <tr>
        <td align="right"><label class="">栏目:</label></td>
        <td><select name="item" id="item" data-content="用户名栏不能为空。" data-original-title="用户名验证"></select></td>
      </tr>
      <tr>
        <td align="right"><label class="">查询模式:</label></td>
        <td><select name="how" id="how" onChange="searchMethod()" data-content="用户名栏不能为空。" data-original-title="用户名验证">
            <option value="all">全部单词all words</option>
            <option value="any">每一个单词any words</option>
            <option value="exact">准确词exact phrase</option>
            <option value="boolean">boolean</option>
          </select>
          <label class="">范围</label>
          <select name="where" id="where" data-content="用户名栏不能为空。" data-original-title="用户名验证">
            <option value="subject">标题</option>
            <option value="content">内容</option>
            <option value="sc" selected="selected">标题和内容</option>
          </select></td>
      </tr>
      <tr>
        <td align="right"><label class="">时间早于:</label></td>
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
          <label class="">最多查询词:</label>
          <input name="maxwords" id="maxwords" value="" size="4" type="text" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证"></td>
      </tr>
      <tr>
        <td><label class="">查询结果 每页记录数:</label></td>
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
        <td colspan="2"><button class="btn btn-primary" type="submit"><i class="icon-white icon-search"></i>查询</button>
          <button class="btn" type="rest">查询</button></td>
      </tr>
    </tbody>
  </table>
  </fieldset>
</form>
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
</div>
</body>
</html>
<script type="text/javascript">
$(function() {
	$('#category').click(function() {
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

	function pagination() {
	
            //Call Sphinxes BuildExcerpts function
            if ($conf['page']['body'] == 'excerpt') {
                $docs = array();
                foreach ($ids as $c => $id) {
                    $docs[$c] = strip_tags($rows[$id]['content']);
                }
                $reply = $cl->BuildExcerpts($docs, $conf['coreseek']['index'], $q);
            }
            
            if ($numberOfPages > 1 && $currentPage > 1) {
                print "<p class='pages'>".pagesString($currentPage,$numberOfPages)."</p>";
            }
            
            //Actully display the Results
            print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";
            foreach ($ids as $c => $id) {
                $row = $rows[$id];
                
                $link = htmlentities(str_replace('$id',$row['id'],$conf['page']['link_format']));
                print "<li><a href=\"$link\">".htmlentities($row['title'])."</a><br/>";
                
                if ($conf['page']['body'] == 'excerpt' && !empty($reply[$c]))
                    print ($reply[$c])."</li>";
                else
                    print htmlentities($row['content'])."</li>";
            }
            print "</ol>";
            
            if ($numberOfPages > 1) {
                print "<p class='pages'>Page $currentPage of $numberOfPages. ";
                printf("Result %d..%d of %d. ",($currentOffset)+1,min(($currentOffset)+$conf['page']['page_size'],$resultCount),$resultCount);
                print pagesString($currentPage,$numberOfPages)."</p>";
            }
            
            print "<pre class=\"results\">$query_info</pre>";

	
	
	
	
	}	
}
?>
