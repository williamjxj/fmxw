<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");
define('DEFAULT_SORT', 2);

require_once (ROOT . "configs/config.inc.php");
global $config;

require_once (ROOT . "locales/f0.inc.php");
global $header;
global $search;
global $list;
global $footer;

require_once (ROOT . 'sClass.php');
set_lang();

try {
    $obj = new FMXW();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

$obj -> set_coreseek_server();

list($tdir0, $tdir6) = array($config['t0'], $config['t6']);
$obj -> assign('config', $config);

/**
 * 约定：
 * 1. http request 进来的$_GET['q']存入$key, $key保留原生的输入值。
 * 2. 需要查询的统统都存入$q, $q可以等于$key,也可以是$key的扩展: $key+$mix.
 * 3. 主要是排序：Sphinx的Query需要 SetSortMode(), 而mysql 需要 'order by'.
 * 4. 附加条件，比如SetFilter(), SetFilterRange(), $q的变化。
 * 5. $_SESSION[PACKAGE][SEARCH]['q']=$q, $_SESSION[PACKAGE][SEARCH]['key']=$key
 * Perl: (负面|丑闻|有害|真相) | (新闻|评价|曝光)
 */
list($key, $q) = array('', '');
$mix = '(丑闻|曝光|腐败|贿赂|淫荡|娼妓|滥用|罪恶|讹诈|抢劫|浮躁|偷窃|狡诈|毒害|奸污|诱惑|贪污|魔鬼|过期|变质|掺假|邪恶|疾病|落后|贪心|自私|蛮横|贪婪|姑息|愚昧|假冒|欺诈|悲剧|消极|放纵|虚假|欺骗|倒台|麻烦|厌烦|倒霉|温床|腐败|谎言|试探|次品|舆论|绯闻|露点|情妇|流氓|恶霸|犯罪|辩解|暴发户|浪尖|愤怒|逃避|作恶|作秀|负面|真相|致癌|涉嫌|超标|贬低|炒作|开除|坏) -(模范|开心)';

if (isset($_GET['q'])) {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);

	if(empty($_GET['q'])) {
	    $key = $q = '';
		$_SESSION[PACKAGE][SEARCH]['key'] ='';		
		$_SESSION[PACKAGE][SEARCH]['q'] ='';		
		$_SESSION[PACKAGE][SEARCH]['sort'] = 'created';
		
		$obj->cl->SetMatchMode(SPH_MATCH_FULLSCAN);
		
		$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
	}
	else {
	    $key = trim($_GET['q']);
		$_SESSION[PACKAGE][SEARCH]['key'] = $key;

		$obj->set_keywords($key);
		
		$obj->cl->SetMatchMode(SPH_MATCH_PHRASE);
		
		$obj->cl->SetSortMode(SPH_SORT_RELEVANCE);
		
		$obj->cl->SetLimits(0, 1);

		$res = $obj -> cl -> Query($key, $obj -> conf['coreseek']['index']);
		//注意查询失败和没有结果的区别：
		if ($res === false) {
			$info = array();
			$info['fail'] = '查询失败 - (' .$key . "): [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj->cl->GetLastError() . "<br>\n";
			$obj->assign('info', $info);
			$obj->display($tdir6.'norecord.tpl.html');
			return;
		}
		elseif ($obj -> cl -> GetLastWarning()) {
			echo "WARNING for " . $key . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
		}
		//如果没有结果，就调用ns.tpl.html直接搜索公共引擎。
		if (empty($res["matches"])) {
			$obj -> assign('_th', $obj -> get_header_label($header));
			$obj -> assign('_tf', $obj -> get_footer_label($footer));			
			$obj -> assign('sitemap', $obj -> get_sitemap());
			$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
			$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');
			$obj->display($tdir6.'ns.tpl.html');

			$obj->write_named_pipes($key, __LINE__);
			return;
		}

		//将缺省设置为’相关度‘，而不是’负面度‘: $q ='@title "'.$key.'" '.$mix;

		$q = $key;
		$_SESSION[PACKAGE][SEARCH]['q'] = $q;
		$_SESSION[PACKAGE][SEARCH]['sort'] = DEFAULT_SORT;

		$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);

        $obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
		
		$weights = array('title'=>11, 'content'=>10);

		$obj->cl->SetFieldWeights( $weights );
	}
}
elseif(isset($_GET['js_dwmy'])) {
	switch($_GET['js_dwmy']) {
		case 'day24':
			$min = $obj->now - 86400;
			break;
		case 'week':
			$min = $obj->now - 604800;
			break;
		case 'month':
			$min = $obj->now - 2678400;
			break;
		case 'year':
			$min = $obj->now - 31536000;
			break;
		default:
			$min = 0;
	}
	$_SESSION[PACKAGE][SEARCH]['min'] = $min;
	
	$key = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	$q = isset($_SESSION[PACKAGE][SEARCH]['q']) ? $_SESSION[PACKAGE][SEARCH]['q']: $key;

	$_SESSION[PACKAGE][SEARCH]['sort'] = 'created';
	
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);

	//先按时间段（最近一小时/天/周/月）降序，再按相关度降序	
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');

	$obj->cl->SetFilterRange("created", $min, $obj->now);
}
elseif(isset($_GET['js_core'])) {
	$key = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';

	switch($_GET['js_core']) {
		case 1: //负面度
			$q = '@title "'.$key.'" '.$mix;
			$_SESSION[PACKAGE][SEARCH]['q'] = $q;
			$_SESSION[PACKAGE][SEARCH]['sort'] = 1;			
			break;
		case 2: //相关度
			$q = $key;
			$_SESSION[PACKAGE][SEARCH]['q'] = $key;
			$_SESSION[PACKAGE][SEARCH]['sort'] = 2;
			break;
		case 3: //评论数
			$q = $key;
			$_SESSION[PACKAGE][SEARCH]['sort'] = 'pinglun';
			break;
		default: //以后可能添加。
			$q = $key;
			$_SESSION[PACKAGE][SEARCH]['sort'] = DEFAULT_SORT;
			break;
	}
	
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);

    $obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");        
}
elseif(isset($_GET['js_attr'])) {
	$key = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	$q = isset($_SESSION[PACKAGE][SEARCH]['q']) ? $_SESSION[PACKAGE][SEARCH]['q']: $key;

	$_SESSION[PACKAGE][SEARCH]['sort'] = $_GET['js_attr'];

	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	
	$obj->cl->SetSortMode(SPH_SORT_ATTR_DESC, $_GET['js_attr']);
}
//翻页显示。
elseif(isset($_GET['page'])) 
{
    $obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);

	$key = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	$q = isset($_SESSION[PACKAGE][SEARCH]['q']) ? $_SESSION[PACKAGE][SEARCH]['q']: $key;
	
	switch($_SESSION[PACKAGE][SEARCH]['sort']) {
		case 2:
			$q = $key;
		case 1:
			$obj->cl->SetSortMode( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
			break;
		case 'cate_id':
			if(isset($_SESSION[PACKAGE][SEARCH]['cate_id'])) 
				$obj->cl->SetFilter('cate_id', array($_SESSION[PACKAGE][SEARCH]['cate_id']));
		case 'iid':
			if(isset($_SESSION[PACKAGE][SEARCH]['item'])) 
				$obj->cl->SetFilter('iid', array($_SESSION[PACKAGE][SEARCH]['item']));

			$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
			break;
		case 'created':
			if (isset($_SESSION[PACKAGE][SEARCH]['min']))
				$obj->cl->SetFilterRange("created", $_SESSION[PACKAGE][SEARCH]['min'], $obj->now);
				
			$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
			break;
		default:
			$obj->cl->SetSortMode(SPH_SORT_ATTR_DESC, $_SESSION[PACKAGE][SEARCH]['sort']);
			break;

    }
}
elseif(isset($_GET['js_cate_item']))
{
	// $obj->__p($_GET); $obj->__p($_SESSION);
	$key = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	$q = isset($_SESSION[PACKAGE][SEARCH]['q']) ? $_SESSION[PACKAGE][SEARCH]['q']: $key;

	$_SESSION[PACKAGE][SEARCH]['cate_id'] = $_GET['category']; 	
	$_SESSION[PACKAGE][SEARCH]['sort'] = 'cate_id';
	
	$obj->cl -> SetFilter('cate_id', array($_SESSION[PACKAGE][SEARCH]['cate_id']));

	if (!empty($_GET['item']))
	{
		$_SESSION[PACKAGE][SEARCH]['item'] = $_GET['item'];
		$_SESSION[PACKAGE][SEARCH]['sort'] = 'iid';

		$obj->cl -> SetFilter('iid', array($_SESSION[PACKAGE][SEARCH]['item']));
	}

	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);		

	$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
}
else {
    if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
	$key = $q = '';
	$_SESSION[PACKAGE][SEARCH]['key'] ='';
	$_SESSION[PACKAGE][SEARCH]['q'] ='';
	$_SESSION[PACKAGE][SEARCH]['sort'] = 'created';
	
	$obj->cl->SetMatchMode(SPH_MATCH_ALL);
	
	//Sort by time segments (last hour/day24/week/month) in descending order, and then by relevance in descending order.
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');

	$obj->cl->SetArrayResult(true);
}

if(empty($key)) {
	$key = isset($_SESSION[PACKAGE][SEARCH]['key']) ? $_SESSION[PACKAGE][SEARCH]['key']: '';
	$q = isset($_SESSION[PACKAGE][SEARCH]['q']) ? $_SESSION[PACKAGE][SEARCH]['q']: $key;
}

// 设置当前页和开始的记录号码。
//empty()= !isset($var) || $var == false.
if (empty($_GET['page'])) {
    $currentPage = 1;
    $currentOffset = 0;
}
else {
    $currentPage = intval($_GET['page']);
    if (empty($currentPage) || $currentPage < 1) {
		$currentPage = 1;
    }

    $currentOffset = ($currentPage - 1) * $obj -> conf['page']['limit'];

    if ($currentOffset > ($obj->conf['page']['max_matches'] - $obj->conf['page']['limit'])) {
		$currentOffset = $obj->conf['page']['max_matches'] - $obj->conf['page']['limit'];
    }
}
$obj -> cl -> SetLimits($currentOffset, $obj->conf['page']['limit']);

// echo 'q=['.$q.'], key=['.$key."]<br>\n";

if(empty($weights)) {
	$weights = array('title'=>11, 'content'=>10);
	$obj->cl->SetFieldWeights( $weights );
}


$res = $obj -> cl -> Query($q, $obj -> conf['coreseek']['index']);

if ($res === false) {
	$info = array();
	$info['fail'] = '查询失败 - (' .$q . "): [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj->cl->GetLastError() . "<br>\n";
	$obj->assign('info', $info);
	$obj->display($tdir6.'norecord.tpl.html');
	return;
}
elseif ($obj -> cl -> GetLastWarning()) {
    echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}

$_SESSION[PACKAGE][SEARCH]['q'] = empty($q) ? '' : trim($q);

if (empty($res["matches"])) {
	$info = array();
	foreach($_SESSION[PACKAGE][SEARCH] as $k=>$v) {
		if(! preg_match("/(?:total|q)/", $k)) $info[$k] = htmlspecialchars($v);
	}
	foreach($_GET as $k=>$v) $info[$k] = htmlspecialchars($v);
	$obj -> assign('info', $info);
	$obj->display($tdir6.'norecord.tpl.html');	
	return;
}

// 取得数据成功后，设置SESSION.
$_SESSION[PACKAGE][SEARCH]['page'] = empty($_GET['page']) ? 1 : $_GET['page'];
$_SESSION[PACKAGE][SEARCH]['total'] = $res['total'];
$_SESSION[PACKAGE][SEARCH]['total_pages'] = ceil($res['total'] / ROWS_PER_PAGE);
$_SESSION[PACKAGE][SEARCH]['total_found'] = $res['total_found'];
$_SESSION[PACKAGE][SEARCH]['time'] = $res['time'];

/*
 * SetArrayResult(true), $ary_ids = array_keys($res['matches']) ***not work***;
 * 得到本次查询的所有的cids($_GET， 总共最多25条)。
 */
$ary_ids = array_map("get_SetArrayResult_Ids", $res['matches']);

/* 将 cid=>weight队放入matches中。
 */
$matches = array();
foreach($res['matches'] as $v) {
	$matches[$v['id']] = $v['weight'];
}

// $obj->__p($matches);

if(empty($res['words'])) {
	$max_weight = (array_sum($weights) * count($res) + 1) * 1000;
}
else {
	$max_weight = (array_sum($weights) * count($res['words']) + 1) * 1000;
}

// 将ary_ids 由数组变成逗号分隔的字符串。
$ids = implode(",", $ary_ids);
$query = "select *, date(created) as date from contents where cid in (" . $ids . ") ";

if (!empty($_SESSION[PACKAGE][SEARCH]['sort'])) {
	switch($_SESSION[PACKAGE][SEARCH]['sort']) {
		case 1:
		case 2:
		case 'cate_id':
		case 'iid':
			$query .= ' ORDER BY FIELD(cid, ' .  $ids . ')';
			break;
		case 3:
			$query .= ' ORDER BY pinglun DESC, created DESC ';
			break;
		default:	
			$query .= ' ORDER BY ' . $_SESSION[PACKAGE][SEARCH]['sort'] . ' DESC ';
		break;
	}
}
else
	$query .= ' ORDER BY FIELD(cid, ' .  $ids . ")";	
	
//echo $query . "<br>\n";

// 查询MySQL，并将结果放入$mres数组中。
$mres = mysql_query($query);

if (mysql_num_rows($mres) <= 0) {
	$info = array();
	$info['结果'] = "查询 【" . $q . "】 没有发现匹配结果，耗时约【".$res['time']."】 秒。";
	foreach($res['matches'] as $k=>$v)
		$info[$k] = $v;
	$obj->assign('info', $res);
	$obj->display($tdir6.'norecord.tpl.html');
    return;
}

//生成要显示的完整记录，放入$rows数组中。以下唯一需要提升的是对content列进行BuildExcerpt()。
$rows = array();
while ($row = mysql_fetch_assoc($mres)) 
{
	// echo "[".$matches[$row['cid']]."], [".$max_weight."]<br>\n";
    $relevance = ceil((intval($matches[$row['cid']]) / $max_weight) * 100); //relevance
	if($relevance>100) $relevance = 100;
	if($relevance<1) $relevance = 1;
	$row['r'] = $relevance;
	// echo "[".$matches[$row['cid']]['weight']."], [".$relevance."]<br>\n";
	if (!preg_match("/(?:<b>|<em>)/", $row['title'])) {
		//echo "11111: " . $row['title'] . "<br>\n";
		$row['title'] = $obj->mb_highlight($row['title'], $key, '<b>', '</b>');
		//echo "22222: " . $row['title'] . "<br>\n";
	}

    $rows[$row['cid']] = $row;
}

// $obj->__p($rows);

//strip_tags将所有'<>'全部去掉，很彻底。
$docs = array();
foreach ($ary_ids as $id) {
    $docs[$id] = strip_tags($rows[$id]['content']);
}

/* 这一步基本没有作用，应为返回总是FALSE.BuildExcerpts没有成功.
 * Call Sphinxes BuildExcerpts function
 */
$reply = $obj -> cl -> BuildExcerpts($docs, $obj -> conf['coreseek']['index'], $key);

//只好再手动做一遍。
if (empty($reply)) {
	foreach ($docs as $id => $ct) {
		if (preg_match("/top/", $rows[$id]['createdby'])) {
			$rows[$id]['content'] = '';
		}
		else {
			$d1 = $obj->my_strip( $ct );
			$d2 = mb_substr($d1, 0, 150);
			$rows[$id]['content'] = $obj->mb_highlight($d2, $key, '<b>', '</b>');
		}
	}
}
else {
	echo "Why Never Come Here: 88888888<br>\n";
	foreach($docs as $id => $ct) {
		$rows[$id]['content'] = $reply[$id];
	}
}

$obj -> assign('results', $rows);

$pagination = $obj -> draw();
$obj -> assign("pagination", $pagination);
$obj -> assign('kr', $obj->get_key_related($key));

$obj -> assign("left_template", $tdir6 . 'left.tpl.html');	
$obj -> assign("nav_template", $tdir6 . 'nav.tpl.html');	
$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));
$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('header_template', $tdir6 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

// $http_get = array('page', 'js_dwmy', 'js_attr', 'js_core', 'js_cate_item');
// if(in_array(key($_GET), $http_get) {}

if (isset($_GET['page']) || isset($_GET['js_dwmy'])  || isset($_GET['js_attr']) || isset($_GET['js_core']) || isset($_GET['js_cate_item']) ) {
    // 以下是:去掉search.tpl.html ajax 部分,程序仍然能工作.
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
	$obj -> display($tdir6 . 'nav.tpl.html');
} 
else {
    $pagination = $obj -> draw();
	$obj -> assign("pagination", $pagination);
	$obj -> display($tdir6 . 'ss.tpl.html');
	if (!empty($_GET['q'])) $obj->write_named_pipes(trim($_GET['q']), __LINE__);
}
exit;

//array_map()的callback回调函数。
function get_SetArrayResult_Ids($a) {
	return $a['id'];
}
?>
