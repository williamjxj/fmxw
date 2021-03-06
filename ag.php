<?php
/**
 * AJAX-style search:
 * 本文件是s.php的一个分支，目的是简化http request请求。
 * 当ajax请求发生时，通过该脚本处理，而不是s.php,这样，s.php可以集中处理sphinx和smarty.
 * 程序流程更加清晰。
 */
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

require_once (ROOT . 'sClass.php');
set_lang();

try {
    $obj = new FMXW();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

list($tdir0, $tdir6, $tdir7) = array($config['t0'], $config['t6'],
$config['t7']);
$obj -> assign('config', $config);

//////////////////////////////////////////////
/**
 * 以下不需要setmatchmode和setsortmode.
 * 显示顺序：（1）是显示评论列表js_reping；（2）当用户点击要发表评论时，js_pk（3）当用户提交评论后自动刷新评论列表。
 */
if(isset($_GET['js_category'])) {
	echo json_encode($obj->get_categories());
	return;
}
elseif(isset($_GET['js_item'])) {
	echo json_encode($obj->get_items($_GET['cate_id']));
	return;
}
/** 关于新的3个评论的处理。
 */
elseif(isset($_GET['js_pk1'])) {
    $cid = intval($_GET['cid']);    
    $obj -> assign('pks', $obj -> get_pk3_by_cid($cid, 'A'));
    $obj->display($tdir7.'reping.tpl.html');
    return;
}
elseif(isset($_GET['js_pk2'])) {
    $cid = intval($_GET['cid']);    
    $obj -> assign('pks', $obj -> get_pk3_by_cid($cid, 'B'));
    $obj->display($tdir7.'reping.tpl.html');
    return;
} 
elseif(isset($_POST['captcha']) && isset($_POST['comment']) && isset($_POST['role'])) {
    if (empty($_SESSION['captcha']) || trim(strtolower($_REQUEST['captcha'])) != $_SESSION['captcha']) {
       echo 'N';
       return;
    }        
    $role = $_POST['role'];
    $pid = $obj->insert_pk3();
    if($pid) {
        $obj -> assign('pks', $obj -> get_pk3_by_cid($_POST['cid'], $role));
        $obj->display($tdir7.'reping.tpl.html');       
    }
    else echo "N";
    return;
}
elseif(isset($_GET['js_publish'])) {
    $obj->display($tdir6.'publish.tpl.html');
    return;
}
elseif(isset($_POST['captcha']) && isset($_POST['title']) && isset($_POST['content'])) {
    if (empty($_SESSION['captcha']) || trim(strtolower($_REQUEST['captcha'])) != $_SESSION['captcha']) {
       echo 'N';
       return;
    }        
	echo $obj->add_content();
	return;
}
  
//以下不需要setmatchmode和setsortmode.
/* 显示顺序：（1）是显示评论列表js_reping；（2）当用户点击要发表评论时，js_pk（3）当用户提交评论后自动刷新评论列表。
 */
elseif(isset($_GET['js_pks1'])) {
    $cid = intval($_GET['cid']);    
    $obj -> assign('reping', $obj -> get_repings_by_cid($cid));
	$obj->display($tdir6.'reping.tpl.html');
	return;
}
elseif(isset($_GET['js_pks2'])) {
    $obj->display($tdir6.'pk.tpl.html');
    return;
}
//<a class="talk fancybox.ajax" href="{$config.ag}?js_talk=1&cid={$l.cid}"></a>
elseif(isset($_GET['js_talk'])) {
    $obj->display($tdir6.'pk.tpl.html');
    return;
}
elseif(isset($_POST['captcha']) && isset($_POST['pk'])) {
	$pid = $obj->insert_pk();
    if($pid) {
        $obj -> assign('reping', $obj -> get_repings_by_cid($_POST['cid']));
        $obj->display($tdir6.'reping.tpl.html');       
    }
    else echo "N";
	return;
}
elseif(isset($_GET['jsc'])) {
    $row = $obj->get_content_1($_GET['cid']);
	if($row['tags'] && preg_match("/\(/", $row['tags']))
		$row['tags'] = preg_replace("/\(.*$/", '', $row['tags']);
	$obj->assign('row', $row);
	$obj->display($tdir6.'single.tpl.html');
    return;
}
elseif (isset($_GET['test'])) {
    header('Content-Type: text/html; charset=utf-8');
	$obj->__p($_REQUEST);
	$obj->__p($_SESSION);
    //$obj -> assign('reping', $obj -> get_repings($q));
    //$obj->__p($obj -> get_repings($q));
	return;
}
?>
