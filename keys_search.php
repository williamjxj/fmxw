<?php
session_start();
if(isset($_GET['q']) && !empty($_GET['q'])) {
	defined('ROOT') or define('ROOT', './');
	header('Content-Type: text/html; charset=utf-8'); 
	// Twitter Bootstrap - Typeahead Plugin with MySQL.
	// William Jiang on Aug 09, 2012.

	require_once(ROOT.'configs/mini-app.inc.php');
	$mdb2 = pear_connect_fmxw();
	
	$ary = array();
	$q = $_GET['q'];
	$query = "select keyword from keywords where keyword like '%" . $q . "%' order by kid";

	$res = $mdb2->query($query);	
	if (PEAR::isError($res)) die($res->getMessage());
	while($row = $res->fetchRow()) {
		$ary[] = iconv('UTF-8', 'UTF-8//TRANSLIT', $row[0]);
	}
	echo json_encode($ary);
}
/*else {
	echo "输入的字符没有被识别。";
}*/
exit;

//////////////////////////////////

function recursive_iconv(string $in_charset, string $out_charset, $arr){
	if (!is_array($arr)){
		return iconv($in_charset, $out_charset, $arr);
	}
	$ret = $arr;
	function array_iconv(&$val, $key, $userdata){
		$val = iconv($userdata[0], $userdata[1], $val);
	}
	array_walk_recursive($ret, "array_iconv", array($in_charset, $out_charset));
	return $ret;
}

// not used. 	
function get_items($q) {
	$query = "select keyword from keywords where LOWER(keyword) like '%" . $q . "%'";
	$res = $mdb2->queryAll($query, '', MDB2_FETCHMODE_ASSOC);
	if (PEAR::isError($res)) die($res->getMessage());
	echo json_encode($res);
}
?>