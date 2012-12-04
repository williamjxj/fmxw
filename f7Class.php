<?php
defined('ROOT') or define('ROOT', './');
require_once (ROOT . "f23Class.php");

class f7Class extends f23Class {
    var $mdb2, $mysql, $lang, $locale;
    public function __construct() {
        parent::__construct();
        $this -> mdb2 = $this -> pear_connect_admin();
        $this -> mysql = $this -> mysql_connect_fmxw();
        $this -> lang = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '中文';
        $this -> locale = $this -> lang == 'English' ? 'en' : 'cn';
    }

    function get_item_count() {
        $ary = array();
        $sql = "select iid, count(*) total from contents group by iid";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        mysql_free_result($res);
        return $ary;
    }

    function get_category_count() {
        $ary = array();
        $sql = "select cid, count(*) total from items group by cid";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        mysql_free_result($res);
        return $ary;
    }

    //////////////// Contents ////////////////
    //上下文应该是同一个category或item下的所有内容，而不是所有的，连续的cid.
    function get_content($cid) {
        $this -> update_clicks($cid);

        $sql = "select * from contents where cid=" . $cid;
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }
	
    function get_content_previous($cid) {
        $sql = "select cid, title from contents where cid < " . $cid . " order by cid desc limit 1";
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }

    function get_content_next($cid) {
        $sql = "select cid, title from contents where cid >" . $cid . " order by cid limit 1";
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }

    function get_contents_list($iid) {
        $ary = array();
        $sql = "select title, cid, category, cate_id, item, iid from contents where iid=" . $iid . " order by cid desc";
        $res = mysql_query($sql);

        list($cate_id, $category, $item) = array(0, '', '');
        $t = '<ul class="nav nav-pills nav-stacked">';
        while ($row = mysql_fetch_assoc($res)) {
            if (!$cate_id && !$category && !$item) {
                $cate_id = $row['cate_id'];
                $category = $row['category'];
                $item = $row['item'];
            }
            $t .= '<li><a href="' . $this -> general . '?cid=' . $row['cid'] . '">' . $row['title'] . "</a></li>\n";
        }
        $t .= '</ul>';
        mysql_free_result($res);
        return $t;
    }
	
	function assemble_menu($menu) {
        $info = array();
        if (preg_match("/English/i", $this -> lang)) {
            $info['title'] = $menu['curl'];
            $t = 'Category' . $menu['curl'] . "<br>\n";
            $t .= "Currently this model is still under developing, will be ready shortly. Thanks for the visiting.<br>\n";
            $info['content'] = $t;
        } else {
            $info['title'] = $menu['name'];
            $t = '分类为：' . $menu['name'] . "<br>\n";
            $t .= '详细信息为：' . $menu['description'] . "<br>\n";
            $t .= '标签为：' . $menu['tag'] ? $menu['tag'] : $menu['name'] . "<br>\n";
            $t .= "目前该分类还处在开发阶段，很快就会有内容呈现。谢谢关注�?br>\n";
            $info['content'] = $t;
        }
        return $info;
    }

    function assemble_sitemap($sm) {
        $info = array();
        if (preg_match("/English/i", $this -> lang)) {
            $info['title'] = $sm[1];
            $info['content'] = "Currently this model is under developing, will be ready shortly.<br>\n";
        } else {
            $info['title'] = $sm[0];
            $info['content'] = "目前该分类还处在开发阶段，很快就会有内容呈现。谢谢关注�?br>\n";
        }
        return $info;
    }

    function get_relative_articles($cid, $iid, $cate_id) {
        $ary = array();
        $sql = "select cid, title, (FLOOR( 1 + RAND( ) *1000 )) AS guanzhu  from contents where cid!=$cid and iid=$iid order by pubdate desc limit 0,6";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_array($res)) {
            array_push($ary, $row);
        }
        mysql_free_result($res);
        return $ary;
    }

    function get_relative_references($cid, $iid, $cate_id) {
        $sql = "select cid, title, (FLOOR( 1 + RAND( ) *1000 )) AS guanzhu  from contents where cid!=$cid and iid=$iid order by rand() limit 0,6";
        $res = mysql_query($sql);
        $html = "<ul>\n";
        while ($row = mysql_fetch_array($res)) {
            $html .= '<li class="tab_list"><i class="icon-circle-arrow-right"></i> <a href="?cid=';
            $html .= $row[0] . '">' . $row[1] . '</a><span class="renshu">';
            $html .= $row[2] . '</span></li>' . "\n";
        }
        $html .= "</ul>\n";
        mysql_free_result($res);
        return $html;
    }

    function update_clicks($cid) {
        mysql_query("update contents set clicks=clicks+1 where cid=" . $cid);
    }

}
?>
