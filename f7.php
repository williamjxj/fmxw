<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

set_lang();

require_once (ROOT . 'f7Class.php');
$obj = new f3Class();

list($tdir0, $tdir1, $tdir6, $tdir7) = array($config['t0'], $config['t1'], $config['t6'], $config['t7']);

$obj -> assign('config', $config);

if(isset($_POST['js_zhichi'])) {
    $obj->set_zhichi($_POST['cid']);
    exit;
}
//使用之
elseif(isset($_GET['js_vote'])) {
    $cid = intval($_GET['cid']);    
    $obj -> assign('cid', $cid);
    $obj->display($tdir7.'vote.tpl.html');
    return;
}
elseif(isset($_POST['js_likes'])) {
    $obj->set_likes($_POST['cid']);
    exit;
}
elseif(isset($_POST['js_fandui'])) {
    $obj->set_fandui($_POST['cid']);
    exit;
}
elseif(isset($_GET['js_get_recommand'])) {
	echo $obj->get_relative_references($_GET['cid'], $_GET['iid'], $_GET['cate_id']);
	exit;
}
elseif (isset($_GET['cid'])) {	
	$info['breadcrumb'] = array();
	if (isset($info['cate_id']) && isset($info['category'])) {
		array_push($info['breadcrumb'], array('name'=>$info['category'], 'link'=>'cate_id='.$info['cate_id']));
	}
	if (isset($info['iid']) && isset($info['item'])) {
		array_push($info['breadcrumb'], array('name'=>$info['item'], 'link'=>'iid='.$info['iid']));
	}

    // $info['rps'] = $obj -> get_comments($_GET['cid']);

    $obj -> assign('info', $info);
} 
elseif (isset($_POST['fayan'])) {
    if (!empty($_REQUEST['captcha'])) {
        if (empty($_SESSION['captcha']) || trim(strtolower($_REQUEST['captcha'])) != $_SESSION['captcha']) {
            echo 'N';
            exit ;
        }
    }
    $obj -> insert_comments();
    echo 'Y';
    exit ;
}
elseif (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
	$obj->__p($obj -> get_item_count());
    exit ;
}
else {

}
require_once (ROOT . "locales/f0.inc.php");
global $header;
global $footer;

$obj -> assign('_th', $obj -> get_header_label($header));
$obj -> assign('_tf', $obj -> get_footer_label($footer));


$obj -> assign('sitemap', $obj -> get_sitemap());
$obj -> assign('help_template', $config['shared'] . 'help.tpl.html');

$obj -> assign('header_template', $tdir1 . 'header1.tpl.html');
$obj -> assign('footer_template', $tdir0 . 'footer.tpl.html');

$obj->assign('detail_template', $tdir7.'detail.tpl.html');

$obj -> display($tdir7 . 'index.tpl.html');
?>
