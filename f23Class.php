<?php
defined('ROOT') or define('ROOT', './');
require_once (ROOT . "configs/base.inc.php");

class f23Class extends BaseClass 
{
    function insert_comments() {
		$fayan = mysql_real_escape_string($_POST['fayan']);
		$cid = intval($_POST['cid']);
		$author = mysql_real_escape_string($_POST['username']);
        $sql = "insert into comments(content, create_time, author, cid) values('" . 
			$fayan . "', now(), '" . 
			$author . "', " . $cid . ")";
			echo $sql;
        mysql_query($sql);
		// return mysql_insert_id();
    }

    function get_comments_3($cid) {
        $ary = array();
        //$sql = "select id, content, author, create_time, cid, area from comments where cid=".$cid." order by id desc";
        $sql = "select id, content, author, create_time, cid, area from comments order by rand()";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        return $ary;
    }
    function get_comments($cid=0) {
        $ary = array();
        $sql = "select id, content, author, date(create_time) created, create_time, cid, area, zhichi from comments order by rand() limit 0,5";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        return $ary;
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
	
   # 随机从数据库中抽????随即生成1-6个记??
    function get_rand_keywords() {
        $ary = array();
        $sql = "select keyword from keywords order by rand() limit 0, 4";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_row($res)) {
            array_push($ary, $row[0]);
        }
        mysql_free_result($res);
        return $ary;
    }

}
?>