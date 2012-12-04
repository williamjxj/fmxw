<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

set_lang();

require_once (ROOT . 'f7Class.php');
$obj = new f7Class();

list($tdir0, $tdir1, $tdir6, $tdir7) = array($config['t0'], $config['t1'], $config['t6'], $config['t7']);

$obj -> assign('config', $config);

if(isset($_GET['js_zhichi'])) {
    $obj->set_zhichi($_GET['cid']);
    exit;
}
//使用之, 赞成，反对，总数，各自的百分比。
elseif(isset($_GET['js_vote'])) {
    $cid = intval($_GET['cid']);
    $info = array();
    $info = $obj -> get_likes_fandui($cid);
    $info['p1'] = round($info['likes']/$info['total'], 2) * 100;
    $info['p2'] = round($info['fandui']/$info['total'], 2) * 100;
	$info['cid'] = $cid;
    $obj -> assign('info', $info);
    $obj->display($tdir7.'vote.tpl.html');
    return;
}
elseif(isset($_GET['js_likes'])) {
    $info = array();
    $info = $obj->set_likes($_GET['cid']);
    $info['p1'] = round($info['likes']/$info['total'], 2) * 100;
    $info['p2'] = round($info['fandui']/$info['total'], 2) * 100;
    $info['cid'] = $cid;
    echo json_encode($info);
	return;
}
elseif(isset($_GET['js_fandui'])) {
    $info = array();
    $info = $obj->set_fandui($_GET['cid']);
    $info['p1'] = round($info['likes']/$info['total'], 2) * 100;
    $info['p2'] = round($info['fandui']/$info['total'], 2) * 100;
    $info['cid'] = $cid;
    echo json_encode($info);
	return;
}
elseif(isset($_GET['js_get_recommand'])) {
	echo $obj->get_relative_references($_GET['cid'], $_GET['iid'], $_GET['cate_id']);
	exit;
}
elseif (isset($_GET['cid'])) {
    //总阅览次数加1: $obj->update_clicks($_GET['cid']);
    $info = $obj -> get_content($_GET['cid']);
 
    $prev = $obj -> get_content_previous($_GET['cid']);
    $info['previous'] = array('cid' => $prev['cid'], 'title' => $prev['title']);

    $next = $obj -> get_content_next($_GET['cid']);
    $info['next'] = array('cid' => $next['cid'], 'title' => $next['title']);

    $ary = $obj -> get_rand_keywords();
    $info['keywords'] = array_slice($ary, rand(0, 3));

    $info['articles'] = $obj -> get_relative_articles($_GET['cid'], $info['iid'], $info['cate_id']);
	
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
