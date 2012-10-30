<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");

require_once (ROOT . "configs/config.inc.php");
global $config;

set_lang();

require_once (ROOT . 'configs/base.inc.php');
try {
    $obj = new BaseClass();
} catch (Exception $e) {
    echo $e -> getMessage(), "line __LINE__.\n";
}

if (isset($_SESSION[PACKAGE]['username'])) {
    $config['username'] = $_SESSION[PACKAGE]['username'];
}

if (isset($_POST['js_pk'])) {
	echo "你已经成功提交了如下信息：";
	
    echo json_encode($_POST);
    exit ;
} 

?>
