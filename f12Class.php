<?php
defined('ROOT') or define('ROOT', './');
require_once (ROOT . "configs/base.inc.php");

class f12Class extends BaseClass 
{
    function select_contents_by_keyword($key) {
        $this -> set_keywords($key);
        $t = '';
        $name = '';
        if ($this -> lang == 'English') {
            $t = 'All Records';
            $name = 'Search - ';
        } else {
            $t = '所有记录';
            $name = '搜索 - ';
        }
        $_SESSION[PACKAGE][SEARCH]['key'] = $key ? mysql_real_escape_string($key) : $t;

        //计算对于此关键词，总共多少记录? $total=mysql_num_rows($res);
        $total = $this -> get_contents_count($key);
        $total_pages = ceil($total / ROWS_PER_PAGE);
        $_SESSION[PACKAGE][SEARCH]['total'] = $total;
        $_SESSION[PACKAGE][SEARCH]['total_pages'] = $total_pages;

        //第一页：
        $_SESSION[PACKAGE][SEARCH]['page'] = 1;

        //当前从第几条记录开始显示?
        $row_no = 0;

        //生成新的查询语句�?
        $lang_case = " and language = '" . $this -> lang . "' ";
        /* select cid, title, date(created) as date, match(title, content) against('不理性行为' in boolean mode) as relevance
         * from contents where match(title, content) against ('不理性行为' in boolean mode)
         * and language = '中文' order by relevance desc  limit 0,25
         *
         $sql = "select cid, title, date(created) as date,
         MATCH(title, content) AGAINST('$key' in boolean mode) as relevancy
         from contents
         where MATCH(title, content) AGAINST('$key' in boolean mode) "
         .$lang_case." order by relevancy desc";
         */

        $sql = "select cid, title, date(created) as date from contents
			where content like '%" . $key . "%' " . " or title like '%" . $key . "%' " . $lang_case . " order by cid desc";

        $_SESSION[PACKAGE][SEARCH]['sql'] = $sql;
        $sql .= " limit  " . $row_no . "," . ROWS_PER_PAGE;

        $ary = array();
        $res = mysql_query($sql);
        //mysql_num_rows($res)得到总行数,不需要两次查询: 去掉get_contents_count().
        // echo $sql;
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        mysql_free_result($res);
        //返回生成的结果�?
        return $ary;
    }

    function set_keywords($key) {
        //将关键词写入keywords表�?
        if ($key != '') {
            $user = isset($_SESSION[PACKAGE]['username']) ? $_SESSION[PACKAGE]['username'] : '';
            if (empty($user))
                $user = basename(__FILE__) . ', search';

            $query = "INSERT INTO keywords (keyword,createdby, created) VALUES " . "('" . $key . "', '" . $user . "', now()) ON DUPLICATE KEY UPDATE total=total+1";
            mysql_query($query);

            $query = "insert into tags (name, createdby, created) values " . "('" . $key . "', '" . $user . "', now()) ON DUPLICATE KEY UPDATE total=total+1";
            mysql_query($query);
        }
        return true;
    }

	//select count(*) from contents where content like '%微笑局长%' or title like '%微笑局长%' and language='中文'
    function get_contents_count($key) {
        $sql = "select count(*) from contents 
			where content like '%" . $key . "%' " . " or title like '%" . $key . "%' and language='" . $this -> lang . "'";
        $result = mysql_query($sql);
        $num = mysql_fetch_row($result);
        mysql_free_result($result);
        return $num[0];
    }

    function draw() {
	$this->__p($_SESSION); $this->__p($_GET);
        $current_page = $_SESSION[PACKAGE][SEARCH]['page'] ? $_SESSION[PACKAGE][SEARCH]['page'] : 1;
        $total_pages = $_SESSION[PACKAGE][SEARCH]['total_pages'] ? $_SESSION[PACKAGE][SEARCH]['total_pages'] : 1;
        $links = array();
        $queryURL = '';
        if (count($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key != 'page')
                    $queryURL .= '&' . $key . '=' . $value;
            }
        }
        if (($total_pages) > 1) {
            if ($current_page != 1) {
                $links[] = '<a href="?page=1' . $queryURL . '">&laquo;&laquo; 首页 </a>';
                $links[] = '<a href="?page=' . ($current_page - 1) . $queryURL . '">&laquo; 前页</a>';
            }

            for ($j = ($current_page - 4); $j < ($current_page + 4); $j++) {
                if ($j < 1)
                    continue;
                if ($j > $total_pages)
                    break;
                if ($current_page == $j) {
                    $links[] = '<a href="javascript:;">' . $j . '</a>';
                } else {
                    $links[] = '<a href="?page=' . $j . $queryURL . '">' . $j . '</a>';
                }
            }

            if ($current_page < $total_pages) {
                $links[] = '<a href="?page=' . ($current_page + 1) . $queryURL . '"> 下页 &raquo; </a>';
                $links[] = '<a href="?page=' . ($total_pages) . $queryURL . '"> 末页 &raquo;&raquo; </a>';
            }
            return $links;
        }
    }

    function get_key_related($q) {
        $sql = "select rid, rk, kurl from key_related where keyword like '%" . mysql_real_escape_string($q) . "%' order by rand() limit 0, " . TAB_LIST;
        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }

    // 输出内容.
    function get_content_1($cid) {
        $sql = "select * from contents where cid=" . $cid;
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
        mysql_free_result($res);
        return $row;
    }

    function select_contents_by_page() {
        //计算共有多少页？
        $total_pages = isset($_SESSION[PACKAGE][SEARCH]['total_pages']) ? $_SESSION[PACKAGE][SEARCH]['total_pages'] : 1;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        if ($page > $total_pages)
            $page = $total_pages;
        if ($page < 1)
            $page = 1;
        $_SESSION[PACKAGE][SEARCH]['page'] = $page;

        //当前从第几条记录开始显示�?
        $row_no = ((int)$page - 1) * ROWS_PER_PAGE;

        //生成新的查询语句.
        if (preg_match("/limit/i", $_SESSION[PACKAGE][SEARCH]['sql']))
            $_SESSION[PACKAGE][SEARCH]['sql'] = preg_replace("/limit.*$/i", '', $_SESSION[PACKAGE][SEARCH]['sql']);

        $sql = $_SESSION[PACKAGE][SEARCH]['sql'];
        $sql .= " limit  " . $row_no . "," . ROWS_PER_PAGE;
        $_SESSION[PACKAGE][SEARCH]['sql'] = $sql;

        $ary = array();
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        mysql_free_result($res);

        //返回生成的结果�?
        return $ary;
    }

}
?>