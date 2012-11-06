<?php
session_start();
error_reporting(E_ALL);
define("ROOT", "./");
defined('PACKAGE') or define('PACKAGE', 'fmxw');
define('SMARTY_DIR', ROOT . 'include/Smarty-3.0.4/libs/');
require_once (SMARTY_DIR . 'Smarty.class.php');
require_once (ROOT . 'configs/mysql-connect.php');

class ContactUs extends Smarty {
    function __construct() {
        parent::__construct();
        $this -> db = mysql_connect_fmxw();
        $this -> template = ROOT . 'templates/8/contact.tpl.html';
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

    function save() {
        $name = mysql_real_escape_string(trim($_POST['name']));
        $email = mysql_real_escape_string(trim($_POST['email']));
        $subject = mysql_real_escape_string(trim($_POST['subject']));
        // 不加mysql_real_escape_string， 如果有特殊字符，比如单引号，就无法解析。
        $message = mysql_real_escape_string($_POST['message']);
        $topic = $_POST['topic'];
        $sql = "INSERT INTO contacts(name, email, subject, topic, message, date) VALUES ('" . $name . "', '" . $email . "', '" . $subject . "', '" . $topic . "', '" . $message . "', now())";
        mysql_query($sql);
        $id = mysql_insert_id();

        //这里似乎应该有个转发邮件的功能。
        $this -> send_email($id);

        return $id;
    }

    function send_email($id) {
        $sql = "SELECT * FROM contacts WHERE id=" . $id;
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);

        // by default reply='N', means this content doesn't sent email yet.
        if ($row['reply'] == 'Y') {
            echo "该信息已经发送过了,请不要重复发送. This content have already sent as email, no sending email anymore.";
            return false;
        }

        $message = $row['name'] . ', ' . $row['email'] . ', ' . $row['topic'] . ': ';
        $message .= "\n日期: " . date("Y-m-d h:i:s");
        $message .= $row['message'];

        $to = 'williamjxj@hotmail.com';
        $subject = $row['subject'];

        if (!mail($to, $subject, $message, 'admin@dixitruth.com'))
            echo '邮件没有成功发送.';

        // After sent, set reply='Y' means no re-send.
        $sql = "UPDATE contacts SET reply = 'Y' WHERE id = " . $id;
        mysql_query($sql);
        return true;
    }

}

/* 控制器部分: dispatch http requests. */
$obj = new ContactUs();
$obj -> get_locale();

if (isset($_POST['name'])) {
    if ($obj -> save())
        echo 'Y';
    else
        echo "N";
} else {
    require_once (ROOT . "locales/f8.inc.php");
    global $contact;

    $obj -> assign('label', $obj -> get_label($contact));
    $obj -> display($obj -> template);
}
exit ;
?>
