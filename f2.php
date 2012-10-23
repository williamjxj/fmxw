<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

set_lang();

require_once (ROOT . 'f2Class.php');
$obj = new f2Class();

list($tdir0, $tdir1, $tdir2, $tdir3) = array($config['t0'], $config['t1'], $config['t2'], $config['t3']);
$obj -> assign('config', $config);

if(isset($_GET['js_get_content'])) {
	$row = $obj->get_content_1($_GET['cid']);
	$obj->assign('row', $row);
	$obj->display($tdir2.'single.tpl.html');
	exit;
}
elseif (isset($_POST['q'])) {
    if (isset($_SESSION[PACKAGE][SEARCH]))
        unset($_SESSION[PACKAGE][SEARCH]);
    $key = trim($_POST['q']);
    $obj -> assign('results', $obj -> select_contents_by_keyword($key));
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
    $obj -> assign("nav_template", $tdir2 . 'nav.tpl.html');
    $obj -> assign('search_template', $tdir2 . 'search.tpl.html');

    require_once('scraper_search.php');
    backend_scrape($key);
    return;
    
} elseif (isset($_GET['page'])) {
    $obj -> assign('results', $obj -> select_contents_by_page());
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
    if (isset($_GET['js_page'])) {
        $obj -> display($tdir2 . 'nav.tpl.html');
        exit ;
    } else {
	    $obj -> assign("nav_template", $tdir2 . 'nav.tpl.html');
        $obj -> assign('search_template', $tdir2 . 'search.tpl.html');
    }
} elseif (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
    $obj->__p($obj -> get_item_count());
    exit ;
}
elseif(isset($_POST['js_zhichi'])) {
	$obj->set_zhichi($_POST['id']);
}
elseif(isset($_POST['fayan'])) {
	if (!empty($_REQUEST['captcha'])) {
		if (empty($_SESSION['captcha']) || trim(strtolower($_REQUEST['captcha'])) != $_SESSION['captcha']) {
			echo 'N';
			exit;
		}
	}
	$obj->insert_comments();
	echo 'Y';
	exit;
}
else {
}

//
$obj -> assign('reping', $obj -> get_comments());

require_once (ROOT . "locales/f0.inc.php");
global $header;
global $footer;

$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));

$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

$obj -> display($tdir2 . 'index.tpl.html');
?>
