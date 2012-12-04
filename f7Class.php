<?php
defined('ROOT') or define('ROOT', './');
require_once (ROOT . "configs/base.inc.php");

class f3Class extends BaseClass {
    var $mdb2, $mysql, $lang, $locale;
    public function __construct() {
        parent::__construct();
        $this -> mdb2 = $this -> pear_connect_admin();
        $this -> mysql = $this -> mysql_connect_fmxw();
        $this -> lang = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '中文';
        $this -> locale = $this -> lang == 'English' ? 'en' : 'cn';
    }

    function set_zhichi($comment_id) {
        mysql_query("update comments set zhichi=zhichi+1 where id=".$comment_id);
    }
    function set_guanzhu($cid) {
        mysql_query("update contents set guanzhu=guanzhu+1 where cid=".$cid);
    }
    function set_likes($cid) {
        mysql_query("update contents set likes=likes+1 where cid=".$cid);
    }
    function set_fandui($cid) {
        mysql_query("update contents set fandui=fandui+1 where cid=".$cid);
    }
    function update_clicks($cid) {
        mysql_query("update contents set clicks=clicks+1 where cid=" . $cid);
    }

    function get_content($cid) {
        $this -> update_clicks($cid);

        $sql = "select * from contents where cid=" . $cid;
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }

}
?>
