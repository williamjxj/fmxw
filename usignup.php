<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");
defined('PACKAGE') or define('PACKAGE', 'fmxw');
define('SMARTY_DIR', ROOT . 'include/Smarty-3.0.4/libs/');
require_once (SMARTY_DIR . 'Smarty.class.php');
require_once (ROOT . 'configs/mysql-connect.php');

class UserSignup extends Smarty {
    function __construct() {
        parent::__construct();
        $this -> db = mysql_connect_fmxw();
        $this -> template = ROOT . 'templates/8/signup.tpl.html';
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

    public function register() {
        $name = mysql_real_escape_string(trim($_POST['username']));
        $email = mysql_real_escape_string(trim($_POST['email']));
        $pass = $_POST['passwd1'];
        $query = "INSERT INTO users(id, username, password, email) VALUES (NULL, '" . $name . "', '" . $pass . "', '" . $email . "')";
        mysql_query($query);
        $uid = mysql_insert_id();

        // Expire in 20 days
        $expire = time() + 1728000;
        setcookie('fmxw[username]', $name, $expire);
        setcookie('fmxw[password]', $pass, $expire);

        $_SESSION[PACKAGE]['username'] = $name;
        $_SESSION[PACKAGE]['userid'] = $uid;
        return $uid;
    }

    public function get_user() {
        $sql = "select * from users where id=" . $_SESSION[PACKAGE]['userid'];
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }

}

/**
 * 控制器部分: dispatch http requests.
 */
$obj = new UserSignup();
$obj -> get_locale();

if (isset($_POST['username'])) {
    echo $obj -> register();
} else {
    require_once (ROOT . "locales/f8.inc.php");
    global $signup;
    $obj -> assign('_sign', $obj -> get_label($signup));

    if (isset($_SESSION[PACKAGE]['userid'])) {
        $user = $obj -> get_user();
        $obj -> assign('user', $user);
    }
    
    $obj -> display($obj -> template);
}
exit ;
?>
