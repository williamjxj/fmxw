<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="include/bootstrap/css/bootstrap.css" rel="stylesheet">
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script src="include/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<form class="well form-search" action="<?=$_SERVER['PHP_SELF'];?>" method="get" name="search1">
  <input type="text" name="q" id="q" class="search-query" style="width:399px" data-provide="typeahead" autocomplete="off" placeholder="请输入关键词" />
  <button type="submit" class="btn btn-primary"><i class="icon-search icon-white"></i>搜索</button>
</form>
<?php
if(isset($_GET['q'])) {
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
	
	require_once (ROOT . 'sClass.php');
	set_lang();
	
	try {
		$obj = new FMXW_Sphinx();
	} catch (Exception $e) {
		echo $e -> getMessage(), "line __LINE__.\n";
	}
	
	//$obj -> display($tdir1 . 'ss.tpl.html');
	if (!empty($_GET['q']))
		$obj->backend_scrape($_GET['q']);
	/*	
	$fifo = fopen('/home/williamjxj/scraper/', 'r+');
	fwrite($fifo, $search_key);
	fclose($fifo);
	*/
}
?>
</body>
</html>
