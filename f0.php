<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

set_lang();

require_once (ROOT . 'f0Class.php');
$obj = new f0Class();

$tdir = $config['t0'];

// login or not?
if (isset($_SESSION[PACKAGE]['username'])) {
    $config['username'] = $_SESSION[PACKAGE]['username'];
}

if (isset($_GET['q'])) {
	$obj->typeahead();
} 
else if (isset($_GET['js_get_category'])) {
	$obj->get_category();
}
else if (isset($_GET['js_get_related'])) {
    $obj->assign('krs', $obj->get_key_related($_GET['kw']));
    $obj->display($tdir.'related_keywords.tpl.html');
    exit;
}
else if (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
    $obj -> __p($obj -> get_keywords());
	$obj -> __p($_COOKIE, false);$obj -> __p($_SESSION, false);
    exit;
} 
else {
    require_once (ROOT . "locales/f0.inc.php");
    global $header;
	global $search;
	global $list;
    global $footer;

	//热门查询
    $obj->assign('keywords', $obj->get_hotest_keywords());
	//最新查询. $tickers = array('王石', '温家宝', '美国总统大选');
	$obj -> assign('tickers', $obj->get_latest_keywords());
	
    $obj -> assign('_th', $obj -> get_header_label($header));
    $obj -> assign('_ts', $obj -> get_search_label($search));
    $obj -> assign('_tl', $obj -> get_list_label($list));
    $obj -> assign('_tf', $obj -> get_footer_label($footer));

    $obj -> assign('config', $config);
    $obj -> assign('sitemap', $obj -> get_sitemap());
	$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

    $obj -> assign('header_template', $tdir . 'header0.tpl.html');
    $obj -> assign('search_template', $tdir . 'search.tpl.html');
    $obj -> assign('footer_template', $tdir . 'footer.tpl.html');

	$obj->assign('list', $obj->get_categories());
	$obj->assign('list_template', $tdir.'list.tpl.html');

    $obj -> display($tdir . 'index.tpl.html');
}

?>
