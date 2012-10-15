<?php
/**
 * etc/目录下有两个sphinxapi，一个是from sphinx2.0.5, 一个是from coreseek,用coreseek的！
 */
defined('ROOT') or define('ROOT', './');
require_once(ROOT . 'etc/sphinxapi_coreseek.php');

class FMXW_Sphinx extends SphinxClient
{
	var $conf = array(), $db, $now, $h;
	function __construct() {
		parent::SphinxClient();
		$this->conf = $this->get_config();
		$this->db = $this->mysql_connect_fmxw();
		// Some variables which are used throughout the script
		$this->now = time();
        $this->h = array();
	}

    function get_parse() {
        $h = array();
        $q = mysql_real_escape_string($_POST['key']);
        $_SESSION[PACKAGE][SEARCH]['key'] = $h['key'] = $q;
        $h['cate_id']   = intval($_POST['category']);
        $h['item_id']   = intval($_POST['item']);
        $h['how']        = $_POST['how'];        // 'all', 'any', 'exact' or 'boolean'
        $h['where']      = $_POST['where'];      // 'subject' or 'body'
        $h['newerval']   = intval($_POST['newerval']);   // newer text
        $h['newertype']  = $_POST['newertype'];  // d(ay), w(eek), m(onth) or y(ear)
        $h['olderval']   = intval($_POST['olderval']);   // older text
        $h['oldertype']  = $_POST['oldertype'];  // d(ay), w(eek), m(onth) or y(ear)
        $h['limit']      = intval($_POST['limit']);      // # of results
        $h['sort']       = $_POST['sort'];       // (r)elevance, (d)ate, (f)orum, (s)ubject or (u)sername
        $h['way']        = $_POST['way'];        // (a)sc or (d)esc
        $this->$h = $h;
        return $h;
    }
	
	function get_dwmy() {
		return array('d'=>'86400', 'w'=>'604800', 'm'=>'2678400', 'y'=>'31536000');
	}
	function get_sort() {
		return array('d' => 'DESC', 'a' => 'ASC');
	}
	
    function get_matchmode($mode) {
        switch($mode){
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
	 * http://www.coreseek.cn/docs/coreseek_4.1-sphinx_2.0.1-beta.html#api-func-setfieldweights
	 * SPH_SORT_RELEVANCE忽略任何附加的参数，永远按相关度评分排序。所有其余的模式都要求额外的排序子句，
	 */
	function get_sortmode($sort)
	{
        switch($sort){
        case "r":
			//按相关度降序排列（最好的匹配排在最前面）: @weight DESC, @id ASC
            $this->SetSortMode(SPH_SORT_RELEVANCE);
            break;
        case "d":
			//按照发布时间倒序排列获取的结果:attribute DESC, @weight DESC, @id ASC
            $this->SetSortMode (SPH_SORT_ATTR_DESC, "pubdate");
			//在SPH_SORT_TIME_SEGMENTS模式中，属性值被分割成“时间段”，然后先按时间段排序，再按相关度排序。 
			$this->SetSortMode (SPH_SORT_TIME_SEGMENTS, "pubdate");
            break;
        case "s":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'title, @weight DESC, @id DESC');
            break;
        case "u":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'guanzhu DESC, @weight DESC, @id DESC');
            break;
        case "v":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'clicks DESC, @weight DESC, @id DESC');
            break;
        case "p":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'pinglun DESC, @rank DESC, @id DESC');
            break;
        case "w":
            $this->SetSortMode (SPH_SORT_EXTENDED, 'tags, @relevance DESC, @id DESC');
            break;
		default:
			$this->SetSortMode(SPH_SORT_RELEVANCE);
		}
	}
	
	function set_filter() {
	    $h = $this->h;
		if(!empty($h['olderval'])) {
			$max = $this->now - $olderval * $this->get_dwmy[$oldertype];
			$this->SetFilterRange('pubdate', 0, $max); //or: created?
		}
		if(!empty($h['newerval'])) {
			$min = $this->now - $newerval * $this->get_dwmy[$newertype];
			$this->SetFilterRange('pubdate', $min, $this->now); //or: created?
		}
        $this->get_matchmode($h['how']);
		
		// Search by subject only, or both body and subject?
		$weights = $h['where'] == 'subject' ? array('title' => 1) : array('title' => 11, 'content' => 10);
		//????????
		$sphinxq = "@(".join(',', array_keys($weights)).") $sphinxq";
		$this->SetFieldWeights($weights);

		//排序模式
		$sortfields  = array('r' => '@weight', 'd' => 'pubdate', 's' => 'title', 'u' => 'guanzhu', 'v' => 'clicks', 'p'=>'pinglun', 'w'=>'tags');
		$sphway = "{$sortfields[$h['sort']]} {$this->get_sort($h['way'])}, @id {$this->get_sort($h['way'])}";
		$this->SetSortMode(SPH_SORT_EXTEBDED, $sphway);
		
		//SetRankingMode （设置评分模式）
		//SPH_RANK_PROXIMITY_BM25: 默认模式，同时使用词组评分和BM25评分，并且将二者结合。
		// SPH_RANK_WORDCOUNT, 根据关键词出现次数排序。这个排序器计算每个字段中关键字的出现次数，然后把计数与字段的权重相乘，最后将积求和，作为最终结果。 
		$this->SetRankingMode(SPH_RANK_PROXIMITY_BM25);
		
		//结果分组（聚类）
		
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
<form action="<?=$_SERVER['PHP_SELF']; ?>" method="POST" id="search_form">
	<fieldset>
		<legend>
			负面新闻高级查询表单
		</legend>
		<table class="table table-striped table-bordered table-hover">
			<tbody>
				<tr>
					<td><span class="alert">查询词:</span></td>
					<td>
					<input name="key" size="30" type="text" placeholder="比如：钓鱼岛争端 苍井空"  class="input-xlarge" data-content="请输入要查询的词，词组，语句。" data-original-title="查询关键词" />
					</td>
				</tr>
				<tr>
					<td colspan="2">
					<table class="table">
						<tr>
							<td><span class="">类别:</span></td>
							<td>
							<select name="category" id="category" data-content="可选项：要查询哪个类别？" data-original-title="查询类别Category">
								<option value="">--- 请选择 ---</option>
							</select></td>
							<td><span class="">栏目:</span></td>
							<td>
							<select name="item" id="item" data-content="可选项：要查询哪个栏目类别？" data-original-title="查询栏目Item">
								<option value="">--- 请选择 ---</option>
							</select></td>
						</tr>
					</table></td>
				</tr>
				<tr>
					<td colspan="2">
					<table class="table">
						<tr>
							<td><span class="">查询模式:</span></td>
							<td>
							<select name="how" id="how" data-content="可选项：请选择查询模式，缺省：扩展模式2。" data-original-title="查询模式">
								<option value="ext2" selected="selected">扩展模式2</option>
								<option value="ext">扩展模式</option>
								<option value="all">匹配全部单词all words</option>
								<option value="any">匹配任何一个单词any words</option>
								<option value="exact">准确匹配exact phrase</option>
								<option value="bool">布尔boolean</option>
							</select></td>
							<td><span class="">查询范围</span></td>
							<td>
							<select name="where" id="where" data-content="可选项：请选择查询范围，缺省：标题和内容。" data-original-title="查询范围">
								<option value="sc" selected="selected">标题和内容</option>
								<option value="subject">标题</option>
								<option value="content">内容</option>
							</select></td>
						</tr>
					</table></td>
				</tr>
				<tr>
					<td colspan="2">
					<table class="table">
						<tr>
							<td><span class="">查询的起始时间</span></td>
							<td>
							<input name="newerval" id="newerval" size="2" type="text" data-content="可选项：查询的起始时间。" data-original-title="查询的起始时间">
							<select name="newertype" id="newertype" data-content="可选项：查询的起始时间。" data-original-title="查询的起始时间">
								<option value="d" selected="selected">天</option>
								<option value="w">周</option>
								<option value="m">月</option>
								<option value="y">年</option>
							</select></td>
							<td>查询的终止时间</td>
							<td>
							<input name="olderval" id="olderval" size="2" type="text" data-content="可选项：查询的终止时间。" data-original-title="查询的终止时间。">
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
					<td colspan="2">
					<table class="table">
						<tr>
							<td><span class="">最少查询词:</span></td>
							<td>
							<input name="minwords" id="minwords" size="4" type="text" data-content="可选项：最少查询词。" data-original-title="最少查询词">
							</td>
							<td><span class="">最多查询词:</span></td>
							<td>
							<input name="maxwords" id="maxwords" size="4" type="text" data-content="可选项：最多查询词。" data-original-title="最多查询词">
							</td>
						</tr>
					</table></td>
				<tr>
					<td colspan="2">
					<table class="table">
						<tr>
							<td><span class="">每页记录数:</span></td>
							<td>
							<input name="limit" id="limit" value="25" size="3" type="text" data-content="查询结果每页记录数。" data-original-title="查询结果每页记录数">
							</td>
							<td><span class="">排序方式:</span></td>
							<td>
							<select name="sort" id="sort" data-content="查询结排序方式果。" data-original-title="查询结排序方式果">
								<option value="r">相关性 relevance</option>
								<option value="d">日期 date</option>
								<option value="s">主题 title</option>
								<option value="u">关注 guanzhu</option>
								<option value="v">点击数 clicks</option>
								<option value="p">回复 pinglun</option>
								<option value="w">标签 tags</option>
							</select></td>
							<td>排序</td>
							<td>
							<select name="way" id="way">
								<option value="d">降序</option>
								<option value="a">升序</option>
							</select></td>
						</tr>
					</table></td>
				<tr>
					<td colspan="2">
					<button class="btn btn-primary" type="submit">
						<i class="icon-white icon-search"></i>查 询 ！
					</button>
					<button class="btn" type="rest">
						重 置
					</button></td>
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
                    $('button:submit', f).attr('disabled', true);
                },
                success : function(data) {
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
		<link rel="stylesheet" type="text/css" href="include/bootstrap/css/bootstrap.css">
		<link rel="stylesheet" type="text/css" href="css/dixi.css">
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<script src="include/bootstrap/js/bootstrap.min.js"></script>
	</head>
	<body>
		<div class="container">
			<div class="hero-unit well-large">
				<h3 id="ad_search" class="label">负面新闻高级查询表单</h3>
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
