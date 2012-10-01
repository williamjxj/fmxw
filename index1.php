<?php
session_start();
error_reporting(E_ALL);
define("SITEROOT", "./");

require_once(SITEROOT.'configs/common.inc.php');
//require_once(SITEROOT.'configs/mini-app.inc.php');
global $common;

require_once(SITEROOT."configs/config.inc.php");
global $config;

set_lang();
// if(get_env()=='Windows') define('RESOURCES', '.\\data\\june_2011\\');
// else define('RESOURCES', './data/pipe/');

require_once(SITEROOT.'dixiClass.php');

try {
  $obj = new DixiClass($config['site_id']);
} catch (Exception $e) {
  echo $e->getMessage(), "line __LINE__.\n";
}

$tdir = $config['templates'];
$tshared = SITEROOT.'templates/shared/';

$config['url'] = $obj->url;
$config['self'] = $obj->__t($obj->self);
$config['browser'] = $obj->browser_id();
$obj->assign('help_template', $tshared.'help.tpl.html');

// login or not?
if(isset($_SESSION[PACKAGE]['username'])) {
	$config['username'] = $_SESSION[PACKAGE]['username'];
}
$obj->assign('common', $common);
$obj->assign('config', $config);


///////////////////////////////

if(isset($_GET['js_get_tab_list'])) {
	echo json_encode($obj->get_tab_list_1());
	exit;
}
else if(isset($_GET['test'])) {
	header('Content-Type: text/html; charset=utf-8'); 
	//echo "<pre>"; print_r($obj->get_latest()); print_r($obj->get_hot()); print_r($obj->get_loop1()); print_r($obj->get_loop2()); echo "</pre>";
	$t = $obj->get_keywords();
	echo "<pre>"; print_r($t); echo "</pre>";
	exit;
}
else {	
	$obj->assign('config', $config);
	$obj->assign('common', $common);
	
	$obj->assign('menu', $obj->get_menu());
	$obj->assign('aoa_tabs', $obj->get_tabs());
	
	//$obj->assign('nails_first', get_ary_thumbnails());
	$obj->assign('carousel1', $obj->get_carousel1());
	$obj->assign('carousel2', $obj->get_carousel2());
	
	$obj->assign('sitemap', $obj->get_sitemap());
	//$obj->assign('definition', $obj->get_definition());

	$obj->assign('latest', $obj->get_latest());
	$obj->assign('hot', $obj->get_hot());
	$obj->assign('keywords', $obj->get_keywords());

	// 下面的span5方框需要填充,用食品的items.
	$info = $obj->get_items();
	$obj->assign('info', $info);
	$obj->assign('item_template', $tdir.'../general/2/item.tpl.html');
		
	$obj->assign('header_template', $tdir.'header.tpl.html');
	$obj->assign('menu_template', $tdir.'menu.tpl.html');
	$obj->assign('rss_template', $tdir.'rss.tpl.html');
	//$obj->assign('rss_template', $tdir.'bootstrap_rss.tpl.html');
	$obj->assign('left_template', $tdir.'left.tpl.html');
	$obj->assign('main_template', $tdir.'main.tpl.html');
	$obj->assign('right_template', $tdir.'right.tpl.html');
	$obj->assign('footer_template', $tdir.'footer.tpl.html');
	$obj->assign('copyright_template', $tdir.'copyright.tpl.html');
	
	$obj->display($tdir.'layout.tpl.html');
}
?>