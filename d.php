<?php
session_start();
error_reporting(E_ALL);
define("SITEROOT", "./");

require_once(SITEROOT.'configs/common.inc.php');
global $common;
require_once(SITEROOT."configs/config.inc.php");
global $config;

//显示 general模板 还是 no_record 模板.
$no_record = false;

require_once(SITEROOT.'generalClass.php');

/*
任何情况下,$_GET, $_POST,都有设置,但可能为空.
echo "<pre>"; print_r($_REQUEST);print_r($_POST);print_r($_GET);print_r($_COOKIE); echo "</pre>";
*/

try {
  $obj = new GeneralClass($config['site_id']);
} catch (Exception $e) {
  echo $e->getMessage(), "line __LINE__.\n";
}


$tdir = SITEROOT.'templates/general/';
$tshared = SITEROOT.'templates/shared/';

$config['browser'] = $obj->browser_id();
$obj->assign('config', $config);
$obj->assign('common', $common);
$obj->assign('help_template', $tshared.'help.tpl.html');

//每次，在url下添加?d， 比如：localhost/fmxw?d, localhost/fmxw/?d=1,则打印出中间测试数据。
//AJAX 的'js_'开头的放在前面,不然$_GET['cid']就会截获ajax的请求.
if(isset($_REQUEST['d']) && $_REQUEST['d'])
	$config['debug'] = true;
else 
	$config['debug'] = false;

if(!empty($_GET)) {
	if(isset($_GET['js_get_content'])) {
		$row = $obj->get_content_1($_GET['cid']);
		$obj->assign('row', $row);
		$obj->display($tdir.'2/single.tpl.html');
		exit;
	}
	elseif(isset($_GET['js_get_breadcrumb'])) {
		if(isset($_GET['sitemap'])) {
			$t = $obj->get_sitemap($_GET['sitemap']);
			$name = $obj->lang=='English' ? $t[1] : $t[0];
			$obj->set_breadcrumb(array(array('name'=>$name, 'active'=>1)));
		}
		//$obj->assign('breadcrumb', $obj->get_breadcrumb());
		$obj->assign('breadcrumb', $_SESSION[PACKAGE]['breadcrumb']);
		$obj->display($tdir.'breadcrumb.tpl.html');
		exit;
	}
	elseif(isset($_GET['js_get_recommand'])) {
		echo $obj->get_relative_references($_GET['cid'], $_GET['iid'], $_GET['cate_id']);
		exit;
	}	
	elseif(isset($_GET['js_get_contents_list'])) {
		echo $obj->get_contents_list($_GET['iid']);
		exit;
	}
	elseif(isset($_GET['iid'])) {
		$info = $obj->get_contents_list($_GET['iid']);
		$obj->assign('info', $info);
		$obj->assign('content_template', $tdir.'2/contents.tpl.html');
	}	
	elseif(isset($_GET['sitemap']) || (isset($_GET['js_sitemap'])) ) {
		$sm = $obj->get_sitemap($_GET['sitemap']);
		$info = $obj->assemble_sitemap($sm);
		if(isset($_GET['js_sitemap'])) {
			$obj->assign('info', $info);
			$obj->display($tdir.'norecord.tpl.html');
			exit;
		}
		else {
			$no_record = true;
			$t = $obj->get_sitemap($_GET['sitemap']);
			$name = $obj->lang=='English' ? $t[1] : $t[0];
			$obj->set_breadcrumb(array(array('name'=>$name, 'active'=>1)));
			$obj->assign('info', $info);
		}
	}
	elseif(isset($_GET['js_get_item_count'])) {
		echo json_encode($obj->get_item_count());
		exit;
	}
	elseif(isset($_GET['js_get_category_count'])) {
		echo json_encode($obj->get_category_count());
		exit;
	}
	elseif(isset($_GET['cmenu'])) {
		$info = $obj->get_item_list($_GET['cmenu']);
		$obj->assign('info', $info);
		$obj->assign('item_template', $tdir.'2/item.tpl.html');
	}
	elseif(isset($_GET['cate_id'])) {
		$info = $obj->get_category_list();
		$obj->assign('info', $info);
		$obj->assign('category_template', $tdir.'2/category.tpl.html');
	}
	elseif(isset($_GET['cid'])) {
		$info = array();
		$row = $obj->get_content($_GET['cid']);
		$info['row'] = $row;
		$info['cid'] = $row['cid'];
		$info['title'] = $row['title'];
		$info['content'] = '<div class="display_content">'.$row['content'].'</div>';
		$info['cate_id'] = $row['cate_id'];
		$info['iid'] = $row['iid'];
		
		$prev = $obj->get_content_previous($_GET['cid']);
		$info['previous'] = array(
			'cid' => $prev['cid'],
			'title' => $prev['title'],
		);
		$next = $obj->get_content_next($_GET['cid']);
		$info['next'] = array(
			'cid' => $next['cid'],
			'title' => $next['title'],
		);
		$ary = $obj->get_rand_keywords();
		$info['keywords'] = array_slice($ary, rand(0,3));

		$info['articles'] = $obj->get_relative_articles($_GET['cid'], $info['iid'], $info['cate_id']);
		
		$info['rps'] = $obj->get_comments($_GET['cid']);
		$obj->assign('info', $info);
	}
	elseif(isset($_GET['test'])) {
		header('Content-Type: text/html; charset=utf-8'); 
		echo "<pre>"; print_r($obj->get_item_list()); print_r($obj->get_item_count()); echo "</pre>";		
		/*
		echo "<pre>"; print_r($obj->select_contents_by_keyword($_GET['test'])); echo "</pre>";
		echo "<pre>"; print_r($_SESSION); echo "</pre>";
		*/
		exit;
	}
	elseif(isset($_GET['page'])) {
		$obj->assign('results', $obj->select_contents_by_page());
		$pagination = $obj->draw();	
		$obj->assign("pagination", $pagination);
		// 以下是:去掉search.tpl.html ajax 部分,程序仍然能工作.
		if(isset($_GET['js_page'])) {
			$obj->display($tdir.'2/search.tpl.html');
			exit;
		}
		else {
			echo "stop";
			exit;
			$obj->assign('search_template', $tdir.'2/d2.tpl.html');
		}
	}
	else {
		header('Content-Type: text/html; charset=utf-8'); 
		echo '啊，出错啦，不可能到这里。';
		echo "<br>\n" . '<a class="btn btn-primary" href="index.php">回到首页</a>';
		exit;
	}
}
/**
 * [key] => 负面新闻, 要做如下的事情:
 *  1. 更新keywords表,插入/计数新的关键词.
 *  2. 搜索并显示结果
 *  3. 百度的<script>调用。
 *  4. 网页抓取程序的调用：百度查询，并将结果存入数据库。
 *  exec('bash -c "exec nohup setsid /home/williamjxj/scraper/baidu/search.pl \'" . $key ."\' >/dev/null 2>&1 &"');
 *  exec("nohup /home/williamjxj/scraper/baidu/search.pl '" . $key ."' >>/tmp/123456 2>&1 &");
 */
elseif(isset($_POST['key'])) {
	if (isset($_SESSION[PACKAGE][SEARCH])) unset($_SESSION[PACKAGE][SEARCH]);
	$key = trim($_POST['key']);
	$obj->assign('results', $obj->select_contents_by_keyword($key));
	//$obj->assign('search_template', $tdir.'2/d2.tpl.html');
	$obj->assign('search_template', $tdir.'2/search.tpl.html');
	$pagination = $obj->draw();	
	$obj->assign("pagination", $pagination);
	if (!empty($key)) {
		exec("/home/williamjxj/scraper/baidu/search.pl '" . $key ."' >/dev/null 2>&1 &");
		exec("/home/williamjxj/scraper/google/gg.pl '" . $key ."' >/dev/null 2>&1 &");
		exec("/home/williamjxj/scraper/yahoo.pl '" . $key ."' >/dev/null 2>&1 &");
		exec("/home/williamjxj/scraper/qq/soso.pl '" . $key ."' >/dev/null 2>&1 &");
	}
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
	header('Location: login.php');
	exit;
}

if($no_record) {
	$obj->assign('norecord_template', $tdir.'norecord.tpl.html');
}
else {
	$obj->assign('general_template', $tdir.'3/d3.tpl.html');
}
$obj->assign('header_template', $tdir.'0/header.tpl.html');
$obj->assign('sitemap', $obj->get_sitemap());
$obj->assign('footer_template', $tdir.'0/footer.tpl.html');

$obj->display($tdir.'0/layout.tpl.html');
?>
