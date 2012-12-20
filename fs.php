<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

require_once (ROOT . "locales/f0.inc.php");
global $header;
global $search;
global $list;
global $footer;

set_lang();

require_once (ROOT . 'f1Class.php');

$obj = new f1Class();
$obj->cl->SetArrayResult(false);

$obj -> set_coreseek_server();

list($tdir0, $tdir1, $tdir6) = array($config['t0'], $config['t1'], $config['t6']);
$obj -> assign('config', $config);

$mix = '(丑闻|曝光|腐败|贿赂|淫荡|娼妓|滥用|罪恶|讹诈|抢劫|浮躁|偷窃|狡诈|毒害|奸污|诱惑|贪污|魔鬼|过期|变质|掺假|邪恶|疾病|落后|贪心|自私|蛮横|贪婪|姑息|愚昧|假冒|欺诈|悲剧|消极|放纵|虚假|欺骗|倒台|麻烦|厌烦|倒霉|温床|腐败|谎言|试探|次品|舆论|绯闻|露点|情妇|流氓|恶霸|犯罪|辩解|暴发户|浪尖|愤怒|逃避|作恶|作秀|负面|真相|致癌|涉嫌|超标|贬低|炒作|开除|坏) -(模范|开心)';
$rpp = 100;

if(isset($_GET['page'])) 
{
    $obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	
	$obj->cl->SetSortMode( SPH_SORT_EXTENDED, "@relevance DESC, @id DESC" );
	
	if(isset($_SESSION[PACKAGE]['cate_item']['cate_id'])) $obj->cl->SetFilter('cate_id', array($_SESSION[PACKAGE]['cate_item']['cate_id']));

	if(isset($_SESSION[PACKAGE]['cate_item']['iid'])) $obj->cl->SetFilter('iid', array($_SESSION[PACKAGE]['cate_item']['iid']));

}
elseif(isset($_GET['js_item'])) {
	echo json_encode($obj->get_items_new($_GET['cid']));
	return;
} 
elseif (isset($_GET['cate_id'])) {
	if (isset($_SESSION[PACKAGE]['cate_item'])) unset($_SESSION[PACKAGE]['cate_item']);
	$_SESSION[PACKAGE]['cate_item']['cate_id'] = $_GET['cate_id'];
	$_SESSION[PACKAGE]['cate_item']['category'] = $obj->get_category_by_id($_GET['cate_id']);
	
	$obj->cl->SetFilter('cate_id', array($_GET['cate_id']));
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");

	$_SESSION[PACKAGE]['cate_item']['title'] = $_SESSION[PACKAGE]['cate_item']['category'];
} 
elseif (isset($_GET['iid'])) {
	$_SESSION[PACKAGE]['cate_item']['iid'] = $_GET['iid'];
	$_SESSION[PACKAGE]['cate_item']['item'] = $obj->get_item_by_id($_GET['iid']);

	$obj->cl->SetFilter('iid', array($_GET['iid']));
	$obj->cl->SetMatchMode(SPH_MATCH_EXTENDED2);
	$obj->cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");

	$_SESSION[PACKAGE]['cate_item']['title'] =
		$_SESSION[PACKAGE]['cate_item']['category'].' - '.$_SESSION[PACKAGE]['cate_item']['item'];
}
elseif (isset($_GET['sitemap'])) {
    $sm = $obj -> get_sitemap($_GET['sitemap']);
    $info = $obj -> assemble_sitemap($sm);
    if (isset($_GET['js_sitemap'])) {
        echo json_encode($info);
        exit ;
    } else {
        $obj -> assign('info', $info);
        $obj -> assign('sitemap_template', $tdir1 . 'sitemap.tpl.html');
    }
}
else {
    if (isset($_SESSION[PACKAGE]['cate_item'])) unset($_SESSION[PACKAGE]['cate_item']);
	
	$obj->cl->SetMatchMode(SPH_MATCH_ALL);
	$obj->cl->SetSortMode(SPH_SORT_TIME_SEGMENTS, 'created');
}

if (empty($_GET['page'])) {
    $currentPage = 1;
    $currentOffset = 0;
}
else {
    $currentPage = intval($_GET['page']);
    if (empty($currentPage) || $currentPage < 1) {
		$currentPage = 1;
    }

    $currentOffset = ($currentPage - 1) * $rpp;

    if ($currentOffset > (1000 - $rpp)) {
		$currentOffset = 1000 - $rpp;
    }
}
$obj -> cl -> SetLimits($currentOffset, $rpp);

/****** 正式开始 ******/
$res = $obj -> cl -> Query($mix, 'contents increment');

if ($res === false) {
	$info = array();
	$info['fail'] = '查询失败 [at ' . __FILE__ . ', ' . __LINE__ . ']: ' . $obj->cl->GetLastError() . "<br>\n";
	$obj->assign('info', $info);
	$obj->display($tdir6.'norecord.tpl.html');
	return;
}
elseif ($obj -> cl -> GetLastWarning()) {
    echo "WARNING [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $obj -> cl -> GetLastWarning() . "<br>\n";
}
if (empty($res["matches"])) {
	$info = array();
	foreach($_SESSION[PACKAGE]['cate_item'] as $k=>$v) 
		$info[$k] = htmlspecialchars($v);
	$obj -> assign('info', $info);
	$obj->display($tdir6.'norecord.tpl.html');	
	return;
}

// 取得数据成功后，设置SESSION.
$_SESSION[PACKAGE]['cate_item']['page'] = empty($_GET['page']) ? 1 : $_GET['page'];
$_SESSION[PACKAGE]['cate_item']['total'] = $res['total'];
$_SESSION[PACKAGE]['cate_item']['total_pages'] = ceil($res['total'] / 50);
$_SESSION[PACKAGE]['cate_item']['total_found'] = $res['total_found'];
$_SESSION[PACKAGE]['cate_item']['time'] = $res['time'];

// 将ary_ids 由数组变成逗号分隔的字符串。
$all = array();
foreach ($res['matches'] AS $key => $row){
  $all[] = $key;
}
$ids = implode(",", $all);

$query = "select cid, title, url, category, cate_id, item, iid, created, date(created) as date  from contents where cid in (" . $ids . ')  ORDER BY FIELD(cid, ' .  $ids . ')';
	
$mres = mysql_query($query);

if (mysql_num_rows($mres) <= 0) {
	$info = array();
	$info['结果'] = "查询 【" . $q . "】 没有发现匹配结果。";
	foreach($res['matches'] as $k=>$v) $info[$k] = $v;
	$obj->assign('info', $res);
	$obj->display($tdir6.'norecord.tpl.html');
    return;
}

$list = array();
while ($row = mysql_fetch_assoc($mres)) {
    $list[] = $row;
}

$obj -> assign("list", $list);

$pagination = $obj -> draw_cate_item();
$obj -> assign("pagination", $pagination);

$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));
$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

$obj -> display($tdir1 . 'wenxuecity.tpl.html');
exit;

?>
