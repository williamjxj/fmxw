<?php
/**
 * etc/目录下有两个sphinxapi，一个是from sphinx2.0.5, 一个是from coreseek,用coreseek的！
 */
defined('ROOT') or define('ROOT', './');
require_once(ROOT . 'etc/coreseek.php');

/**
 * 这里用继承，而不是创建新目标。
 */
class FMXW_Sphinx extends SphinxClient
{
	var $conf, $db, $now, $dwmy, $st, $q, $h;
	function __construct() {
	    
		parent::SphinxClient();
		
		$this->conf = $this->get_config();
		$this->db = $this->mysql_connect_fmxw();
		// Some variables which are used throughout the script
		$this->now = time();
		$this->dwmy = $this->get_dwmy();
		$this->st = $this->get_sort();
        //存储每次的查询词。
        $this->q = '';
        //存储parsed的查询表单的输入参数。$_SESSION已经有存储，这里只是方便调用。
        $this->h = array();

        // 如果不设置，date()等时间函数调用时，就会warning.
		$timezone = "Asia/Shanghai";
		if(function_exists('date_default_timezone_set'))
            date_default_timezone_set($timezone);
	}

    //没有用constant, 而是用数组，因为变量较多，放在数组中便于调整。
	function get_config() {
		return $conf = array(
			'coreseek' => array(
				'host' => 'localhost',
				'port' => 9313,
				'index' => "contents increment", //increment
				'query' => 'SELECT * from contents where cid in ($ids)',
			),
			'sphinx' => array(
				'host' => 'localhost',
				'port' => 9312,
				'index' => "contents increment", 
				'query' => 'SELECT * from contents where cid in ($ids)',
			),
			'mysql' => array(
				'host' => "localhost",
				'username' => "fmxw",
				'password' => "fmxw123456",
				'database' => "dixi",
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
        //以下是缺省设置，后面将会动态调整。
		$this->SetMatchMode( SPH_MATCH_EXTENDED2 );
		$this->SetSortMode( SPH_SORT_RELEVANCE );
		//$this->SetConnectTimeout ( 3 ); $this->SetArrayResult ( true );
	}
	function set_sphinx_server()
	{
		$this->SetServer($this->conf['sphinx']['host'], $this->conf['sphinx']['port']);
        //以下是缺省设置，后面将会动态调整。
		$this->SetMatchMode ( SPH_MATCH_EXTENDED2 );
		$this->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
	}

    // 日，周，月，年有多少秒？
	function get_dwmy() {
		return array('d'=>'86400', 'w'=>'604800', 'm'=>'2678400', 'y'=>'31536000');
	}
    // 升序还是降序？
	function get_sort() {
		return array('d' => 'DESC', 'a' => 'ASC');
	}
	
    // 由用户的查询模式<select>选择菜单，来决定查询模式。有关联性，所以不一定准确，仅仅试验。
    function get_matchmode1($how) {
        switch($how){
        case "ext2":
            $this->SetMatchMode(SPH_MATCH_EXTENDED2);
            break;
        case "ext":
            $this->SetMatchMode(SPH_MATCH_EXTENDED);
            break;
        case "any":
            $this->SetMatchMode(SPH_MATCH_ANY);
            break;
        case "all":
            $this->SetMatchMode(SPH_MATCH_ALL);
            break;
        case "exact":
            //全字准确匹配
            $this->SetMatchMode(SPH_MATCH_PHRASE);
            break;
        case "bool":
            $this->SetMatchMode(SPH_MATCH_BOOLEAN);
            break;
        default:
            $this->SetMatchMode(SPH_MATCH_EXTENDED2);
            break;
        }
        return $mode;
    }
    /**
     * http://www.shroomery.org/forums/dosearch.php.txt
     * 在这两个函数之间切换，看看效果。
     * $this-h['key']不变，$this->变化。
     */
    function get_matchmode($how)
    {
        $this->SetMatchMode(SPH_MATCH_EXTENDED2);
        $this->q = $how == 'bool' ? 
            $this->h['key'] : preg_replace('/[\s\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7E]+/', ' ', $this->h['key']);
        if ($how == 'any') {
            $this->q = preg_replace("\s+", '|', $this->q);
        } else if ($how == 'exact'){
            $this->q = "\"$this->q\"";  
        }        
    }
	/**
	 * http://www.coreseek.cn/docs/coreseek_4.1-sphinx_2.0.1-beta.html#api-func-setfieldweights
	 * SPH_SORT_RELEVANCE忽略任何附加的参数，永远按相关度评分排序。所有其余的模式都要求额外的排序子句，
     *  由用户的查询模式<select>选择菜单，来决定查询模式。有关联性，所以不一定准确，仅仅试验。
     */
	function get_sortmode1($sort)
	{
        switch($sort){
        case "r":
			//按相关度降序排列（最好的匹配排在最前面）: @weight DESC, @id ASC
            $this->SetSortMode(SPH_SORT_RELEVANCE);
            break;
        case "d":
			//按照发布时间倒序排列获取的结果:attribute DESC, @weight DESC, @id ASC
			//pubdate是varchar,所以用created(timestamp)
            $this->SetSortMode (SPH_SORT_ATTR_DESC, "created");
			//在SPH_SORT_TIME_SEGMENTS模式中，属性值被分割成“时间段”，然后先按时间段排序，再按相关度排序。 
			$this->SetSortMode (SPH_SORT_TIME_SEGMENTS, "created");
            break;
        case "s":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'title, @weight DESC, @id DESC');
            break;
        case "g":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'guanzhu DESC, @weight DESC, @id DESC');
            break;
        case "c":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'clicks DESC, @weight DESC, @id DESC');
            break;
        case "p":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'pinglun DESC, @rank DESC, @id DESC');
            break;
        case "t":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'tags, @relevance DESC, @id DESC');
            break;
		default:
			$this->SetSortMode(SPH_SORT_RELEVANCE);
		}
	}
    function get_sortmode($sort)
    {           
        $sortfields  = array(
            'r' => '@weight', 
            'd' => 'pubdate', 
            's' => 'title', 
            'g' => 'guanzhu', 
            'c' => 'clicks', 
            'p'=>'pinglun', 
            't'=>'tags'
        );
        $sphway = "{$sortfields[$sort]} {$this->st[$this->h['way']]},
		@id {$this->st[$this->h['way']]}";
		// @weight DESC, @id DESC
        $this->SetSortMode(SPH_SORT_EXTENDED, $sphway);
        // $this->__p($sphway);
    }
    
    //当<form>提交时执行, 输入参数存入$_SESSION和object中.
    function get_parse() 
    {
        $_SESSION[PACKAGE][SEARCH]['key'] = $this->q = trim($_POST['key']);

        $h = array();
        $h['key']        = $_POST['key'] ? trim($_POST['key']): '';
        $h['cate_id']    = $_POST['category'] ? intval($_POST['category']) : 0;
        $h['item_id']    = $_POST['item'] ? intval($_POST['item']) : 0;
        $h['how']        = $_POST['how'];        // 'all', 'any', 'exact' or 'boolean'
        $h['where']      = $_POST['where'];      // 'subject' or 'body'
        $h['newerval']   = intval($_POST['newerval']);   // newer text
        $h['newertype']  = $_POST['newertype'];  // d(ay), w(eek), m(onth) or y(ear)
        $h['olderval']   = intval($_POST['olderval']);   // older text
        $h['oldertype']  = $_POST['oldertype'];  // d(ay), w(eek), m(onth) or y(ear)
        $h['limit']      = intval($_POST['limit']);      // # of results
        $h['sort']       = $_POST['sort'];       // (r)elevance, (d)ate, (f)orum, (s)ubject or (u)sername
        $h['way']        = $_POST['way'];        // (a)sc or (d)esc		

		$this->h = $h;
        return $h;
    }
	
    //解析输入参数.
	function set_filter()
	{
	    //这样做就是为了简单, 操作起来方便,也便于阅读.
	    $h = $this->h;

        //(.) 处理时间范围,如果用户选择,就设置属性范围 SetFilterRange()
		if(!empty($h['olderval'])) {
			$max = $this->now - $h['olderval'] * $this->dwmy[$h['oldertype']];
			//echo "max[". $max."]". date("D, d M Y", $max) . "<br>\n";
			$this->SetFilterRange('created', 0, $max);
		}
		if(!empty($h['newerval'])) {
			$min = $this->now - $h['newerval'] * $this->dwmy[$h['newertype']];
			//echo "min[". $min."], [". $this->now."]". date("D, d M Y", $min).", ".date("D, d M Y", $this->now)."<br>\n";			
			$this->SetFilterRange('created', $min, $this->now);
		}
        
        //(.) SetMatchMode(SPH_MATCH_EXTENDED2) 
        $this->get_matchmode($h['how']);
		
		//(.) 'sc','subject','content'
        // $h['weights'] = $h['where'] == 'subject' ? array('title' => 1) : array('title' => 11, 'content' => 10);
        // $this->q = "@(".implode(',', array_keys($h['weights'])).") $this->q";
        // $this->SetFieldWeights($h['weights']);

		if ($h['where'] == 'subject' && $this->h['key']){
			$this->q = "@title $this->q";
			$weightsum = 1;
		} else {
			$this->SetFieldWeights(array('title' => 11, 'content' => 10));
			$weightsum = 21;
		}

		/**
		 * http://www.php.net/manual/en/sphinxclient.setfilter.php
		 * public bool SphinxClient::setFilter ( string $attribute , array $values [, bool $exclude = false ] )
		 */
		if(!empty($h['cate_id'])) {
			$this->SetFilter('cate_id', array($h['cate_id'])); 
		}
		if(!empty($h['item_id'])) {
			$this->SetFilter('iid', array($h['item_id']));
		}

		//排序模式
		$this->get_sortmode($h['sort']);
		
        if(empty($h['key'])) {
            $this->SetRankingMode(SPH_RANK_NONE);
        }
        else {
            //SetRankingMode （设置评分模式）
            //SPH_RANK_PROXIMITY_BM25: 默认模式，同时使用词组评分和BM25评分，并且将二者结合。
            // SPH_RANK_WORDCOUNT, 根据关键词出现次数排序。这个排序器计算每个字段中关键字的出现次数，然后把计数与字段的权重相乘，最后将积求和，作为最终结果。 
            $this->SetRankingMode(SPH_RANK_PROXIMITY_BM25);            
        }
		
        // 每页显示多少条记录？
        if(empty($h['limit']) || ($h['limit']>100)) $h['limit'] = $this->conf['page']['size'];
        
		//结果分组（聚类）
		// if(!empty($h['weights'])) $_SESSION[PACKAGE][CS]['weights'] = $h['weights'];
		if ($weightsum) $h['weights'] = $weightsum;
        
        /* 将结果保存在SESSION中，以便翻页时调用*/
        $_SESSION[PACKAGE][CS] = $h;
        $_SESSION[PACKAGE][CS]['q'] = $this->q;
		return $h;
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

	function get_form()
	{
?>
<form action="<?=$_SERVER['PHP_SELF']; ?>" method="POST" id="search_form">
  <table class="table table-striped table-bordered table-hover ">
    <tbody>
      <tr>
        <td><label class="alert alert-error fade in" for="key">请输入查询词:</label></td>
        <td colspan="3"><input type="text" name="key" id="key" class="input-xlarge search-query" style="width:400px;height:35px;" placeholder="比如：钓鱼岛争端 苍井空"  data-content="请输入要查询的词，词组，语句。" data-original-title="查询关键词" /></td>
      </tr>
      <tr>
        <td nowrap="nowrap" align="right"><label class="alert" for="category">类别:</label></td>
        <td align="right"><select name="category" id="category" data-content="可选项：要查询哪个类别？" data-original-title="查询类别">
            <option value="">--- 请选择 ---</option>
          </select></td>
        <td nowrap><label class="alert fade in" for="item">栏目:</label></td>
        <td align="right"><select name="item" id="item" data-content="可选项：要查询哪个栏目类别？" data-original-title="查询栏目">
            <option value="">--- 请选择 ---</option>
          </select></td>
      </tr>
      <tr>
        <td nowrap><label class="alert" for="how">查询模式:</label></td>
        <td align="right"><select name="how" id="how" data-content="可选项：请选择查询模式，缺省：扩展模式2。" data-original-title="查询模式">
            <option value="ext2" selected="selected">扩展模式2</option>
            <option value="ext">扩展模式： 变质食品 -(过期|火腿肠)</option>
            <option value="all">匹配全部单词</option>
            <option value="any">匹配任何一个单词</option>
            <option value="exact">按顺序完整准确匹配</option>
            <option value="bool">按照布尔表达式查询：钓鱼岛 -美国</option>
          </select></td>
        <td nowrap><label class="alert" for="where">查询范围</label></td>
        <td align="right"><select name="where" id="where" data-content="可选项：请选择查询范围，缺省：标题和内容。" data-original-title="查询范围">
            <option value="sc" selected="selected">标题和内容</option>
            <option value="subject">标题</option>
            <option value="content">内容</option>
          </select></td>
      </tr>
      <tr>
        <td nowrap><label class="alert" for="newerval">查询的起始时间:</label></td>
        <td align="right"><input name="newerval" id="newerval"  type="text" data-content="可选项：查询的起始时间。" data-original-title="查询的起始时间">
          <select name="newertype" id="newertype" data-content="可选项：查询的起始时间。" data-original-title="查询的起始时间">
            <option value="d" selected="selected">天</option>
            <option value="w">周</option>
            <option value="m">月</option>
            <option value="y">年</option>
          </select></td>
        <td nowrap><label class="alert" for="olderval">查询的终止时间:</label></td>
        <td align="right"><input name="olderval" id="olderval" type="text" data-content="可选项：查询的终止时间。" data-original-title="查询的终止时间。">
          <select name="oldertype" id="oldertype" data-content="可选项：查询的终止时间。" data-original-title="查询的终止时间">
            <option value="d" selected="selected">天</option>
            <option value="w">周</option>
            <option value="m">月</option>
            <option value="y">年</option>
          </select></td>
      </tr>
      <tr>
        <td nowrap><label class="alert" for="sort">排序方式:</label></td>
        <td align="right"><select name="sort" id="sort" data-content="查询结排序方式果。" data-original-title="查询结排序方式果">
            <option value="r">相关性</option>
            <option value="d">日期</option>
            <option value="s">主题</option>
            <option value="g">关注</option>
            <option value="c">点击数</option>
            <option value="p">回复</option>
            <option value="t">标签</option>
          </select></td>
        <td><label class="alert" for="way">排序:</label></td>
        <td align="right"><select name="way" id="way">
            <option value="d">降序</option>
            <option value="a">升序</option>
          </select></td>
      </tr>
      <tr>
        <td nowrap><label class="alert" for="limit">每页记录数:</label></td>
        <td align="right"><input name="limit" id="limit" value="25" size="3" type="text" data-content="查询结果每页记录数。" data-original-title="查询结果每页记录数"></td>
        <td nowrap><label class="alert" for=""></label></td>
        <td align="right"></td>
      </tr>
      <tr align="center">
        <td colspan="4"><button class="btn btn-primary" type="submit"><i class="icon-white icon-search"></i>查 询</button>
          <button class="btn" type="reset">重 置</button></td>
      </tr>
    </tbody>
  </table>
</form>
<script type="text/javascript">
    $(function() {
        $('input:text, select', '#search_form').hover(function() {
            $(this).popover('show');
        }, function() {
            $(this).popover('hide');
		});
        $('#ad_search').click(function() {
            var f = $('#search_form');
            if ($(f).is(':visible'))
                $(f).hide();
            else
                $(f).animate().show();
            return false;
        });
        $('#search_form').submit(function(e) {
            var d = $('#div_list');
            var f = $(this);
            $.ajax({
                url : f.attr('action'),
                type : f.attr('method'),
                data : f.serialize() + '&js_form=1',
                cache : false,
                beforeSend : function() {
                    $('<div></div>').addClass('ajaxloading').appendTo(d);
                    $('input[type="submit"]', f).attr('disabled', true);
                },
                success : function(data) {
                    $('input[type="submit"]', f).attr('disabed', false);
                    d.html(data).fadeIn(100);
					$('html,body').animate({scrollTop: $('#div_list').offset().top}, 'slow');
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
<link rel="stylesheet" type="text/css" href="include/bootstrap/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="css/coreseek.css">
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script src="include/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
  <div class="box">
    <div class="fmxwlogo">
      <h3 id="ad_search" class="head1">负面新闻高级查询表单</h3>
    </div>
    <?php $this -> get_form(); ?>
  </div>
  <div id="div_list"></div>
</div>
</body>
</html>
<script type="text/javascript">
$(function() {
	$('#category').change(function() {
		cate_id = $(this).attr('value');
		$.getJSON("?js_item=1&cate_id=" + cate_id, function(data) {
			var items = [];
			//console.log(data);
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
		$('html,body').animate({scrollTop: $('#div_list').offset().top}, 'slow');
		return false;
	});
	$('#key').focus();
});
$(window).load(function() {
	$.getJSON('?js_category=1', function(data) {
		// console.log(data);
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

	//下面的是参考：http://www.nearby.org.uk/sphinx/example5.php?q=test&page=10
	function linktoself($params,$selflink= '') {
		$a = array();
		$b = explode('?',$_SERVER['REQUEST_URI']);
		if (isset($b[1])) parse_str($b[1],$a);
		
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
			}
			else {
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
		if (!empty($r)) return($r);
	
		$r .= "<ul>";
		if ($currentPage > 1)
			$r .= "<li><a href=\"".$this->linktoself(array('page'=>$currentPage-1))."$postfix\"$extrahtml>&lt; &lt; prev</a></li>";

		$start = max(1,$currentPage-5);
		$endr = min($numberOfPages+1,$currentPage+8);
		
		if ($start > 1)
			$r .= "<li><a href=\"".$this->linktoself(array('page'=>1))."$postfix\"$extrahtml>1</a> ... </li>";
		
		for($index = $start;$index<$endr;$index++) {
			if ($index == $currentPage)
				$r .= "<li class='active'><a href='#'>$index</a></li>";
			else
				$r .= "<li><a href=\"".$this->linktoself(array('page'=>$index))."$postfix\"$extrahtml>$index</a></li> ";
		}
		
		if ($endr < $numberOfPages+1)
			$r .= " .... ";			

		if ($numberOfPages > $currentPage)
			$r .= "<li><a href=\"".$this->linktoself(array('page'=>$currentPage+1))."$postfix\"$extrahtml>next &gt;&gt;</a></li>";	

		$r .= "</ul>";
		return $r;
	}

	//error, warning, status, fields+attrs, matches, total, total_found, time, words
	function get_res($res) 
	{
		return array(
			'total' => $res['total'],
			'total_found' => $res['total_found'],
			'time' => $res['time'],
			'ids' => array_keys($res['matches']),
		);		
	}
	
	function __p($vars, $debug=true)
	{
        if (!$debug) return;
        if (is_array($vars) || is_object($vars)) {
            echo "<pre>"; print_r($vars); echo "</pre>";
        } else
            echo $vars . "<br>\n";
    }
	
	function display_summary($results, $title="查询结果")
	{
?>
<div class="alert alert-block">
  <button type="button" class="close" data-dismiss="alert">×</button>
  <h4>
    <?=$title;?>
  </h4>
  <p><?php echo $results;?></p>
</div>
<?php	
	}

	function pretty_print($result)
	{
		// query OK, pretty-print the result set
		// begin with general statistics
		$got = count ( $result["matches"] );
		print "Query matched $result[total_found] documents total.<br>\n";
		print "Showing matches 1 to $got of $result[total] accessible.<br>\n";

		// print out matches themselves now
		$n = 1;
		foreach ( $result["matches"] as $match ) {
			// print number, document ID, and weight
			print "$n. id=$match[id], weight=$match[weight], <br>\n";
			$n++;
			// print group_id attribute value
			print "group_id=$match[attrs][group_id]<br>\n";
		}
	}

}
?>
