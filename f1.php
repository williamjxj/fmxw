<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

set_lang();

require_once (ROOT . 'f1Class.php');
try {
    $obj = new f1Class();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

if (isset($_SESSION[PACKAGE]['username'])) {
    $config['username'] = $_SESSION[PACKAGE]['username'];
}

///////////////////////////////
list($tdir0, $tdir1, $tdir2) = array($config['t0'], $config['t1'], $config['t2']);

if (isset($_GET['js_get_cc'])) {
    $obj -> __p($obj -> get_category_contents());
    exit ;
} 
elseif (isset($_GET['cate_id'])) {
    $list = $obj -> get_category_contents($_GET['cate_id']);
    // $obj -> __p($list);
    $obj -> assign('list', $list);
    $obj -> assign('cc_template', $tdir1 . 'category_contents.tpl.html');
} 
elseif ($_GET['iid']) {
    $list = $obj -> get_item_contents($_GET['iid']);
    $obj -> assign('list', $list);
    $obj -> assign('ic_template', $tdir1 . 'item_contents.tpl.html');
}
elseif ($_GET['sitemap']) {
    $sm = $obj -> get_sitemap($_GET['sitemap']);
    $info = $obj -> assemble_sitemap($sm);
    if (isset($_GET['js_sitemap'])) {
        //$obj -> display($tdir1 . 'sitemap.tpl.html');
        echo json_encode($info);
        exit ;
    } else {
        $obj -> assign('info', $info);
        $obj -> assign('sitemap_template', $tdir1 . 'sitemap.tpl.html');
    }
} elseif ($_GET['js_get_news']) {

    require_once (ROOT . "locales/f0.inc.php");
    global $header;
    global $search;
    global $list;
    global $footer;

    $obj -> assign('_th', $obj -> get_header_label($header));
    $obj -> assign('_tf', $obj -> get_footer_label($footer));

    $obj -> assign('config', $config);
    $obj -> assign('sitemap', $obj -> get_sitemap());
    $obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

    $obj -> assign('header_template', $tdir0 . 'header.tpl.html');
    $obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

    $obj -> display($tdir1 . 'news.tpl.html');
    exit ;
} elseif ($_GET['js_f1']) {
    $rss = $obj -> get_rss($obj -> rss[$_GET['js_f1']]);
    $obj -> assign('rss', $rss);
    $obj -> assign('rss_template', $tdir1 . 'rss.tpl.html');
} elseif (isset($_GET['page'])) {
    $obj -> assign('results', $obj -> select_contents_by_page());
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
    // 以下是:去掉search.tpl.html ajax 部分,程序仍然能工作.
    if (isset($_GET['js_page'])) {
        $obj -> display($tdir . '2/search.tpl.html');
        exit ;
    } else {
        echo "stop";
        exit ;
        $obj -> assign('search_template', $tdir . '2/d2.tpl.html');
    }
} 
elseif (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
}
elseif (isset($_POST['q'])) {
	//$obj -> assign('ss_template', $tdir1 . 'ss.tpl.html');
    if (isset($_SESSION[PACKAGE][SEARCH]))
        unset($_SESSION[PACKAGE][SEARCH]);
    $key = trim($_POST['q']);
    $obj -> assign('results', $obj -> select_contents_by_keyword($key));
    $pagination = $obj -> draw();
    $obj -> assign("pagination", $pagination);
    $obj -> assign("pagination_template", $tdir2 . 'pagination.tpl.html');
	
	$obj -> assign('kr', $obj->get_key_related($key));
}
else {
    $obj -> __p($_REQUEST);
    die("Error, no http request at: [" . __FILE__ . '], line ' . __LINE__);
}

require_once (ROOT . "locales/f0.inc.php");
global $header;
global $footer;

$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));

$obj -> assign('config', $config);
$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

if (isset($_POST['q'])) {
	$obj -> display($tdir1 . 'ss.tpl.html');
}
else {
	$obj -> display($tdir1 . 'index.tpl.html');
}
?>
