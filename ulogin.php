<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");
defined('PACKAGE') or define('PACKAGE', 'fmxw');
define('SMARTY_DIR', ROOT . 'include/Smarty-3.0.4/libs/');
require_once (SMARTY_DIR . 'Smarty.class.php');
require_once (ROOT . 'configs/mysql-connect.php');

class UserLogin extends Smarty {
    function __construct() {
        parent::__construct();
        $this -> db = mysql_connect_fmxw();
        $this -> template = ROOT . 'templates/8/login.tpl.html';
		$timezone = "Asia/Shanghai";
		if(function_exists('date_default_timezone_set')) date_default_timezone_set($timezone);
    }

    function get_locale() {
        $this -> lang = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '中文';
        $this -> locale = $this -> lang == 'English' ? 'en' : 'cn';
    }

    public function get_label($array) {
        $ary = array();
        foreach ($array as $k => $v)
            $ary[$k] = $v[$this -> locale];
        return $ary;
    }

    function check() {
        $name = mysql_real_escape_string(trim($_POST['username']));
        $pass = $_POST['passwd'];

        $sql = "select id from users where username='" . $name . "' and password='" . $pass . "' ";
        // echo $sql; //select id from users where username='一地鸡毛' and password='一地鸡毛' 
        $result = mysql_query($sql);
        $num = mysql_fetch_row($result);
        mysql_free_result($result);
        $uid = $num[0];

        // Expire in 20 days
        $expire = time() + 1728000;
        setcookie('fmxw[username]', $name, $expire);
        setcookie('fmxw[password]', $pass, $expire);

        $_SESSION[PACKAGE]['username'] = $name;
        $_SESSION[PACKAGE]['userid'] = $uid;
        return $uid;
    }

}

/* 控制器部分: dispatch http requests. */

$obj = new UserLogin();
$obj -> get_locale();

if (isset($_POST['username'])) {
    if ($obj -> check())
        echo 'Y';
    else 
        echo "N";
} elseif (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
} else {
    require_once (ROOT . "locales/f8.inc.php");
    global $login;

    $obj -> assign('label', $obj -> get_label($login));
    $obj -> display($obj -> template);
}
exit ;
?>
