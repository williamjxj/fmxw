<?php
require_once (ROOT . "configs/base.inc.php");

class f0Class extends BaseClass 
{
    var $mdb2, $mysql, $lang, $locale;
    public function __construct() {
        parent::__construct();
        $this -> mdb2 = $this -> pear_connect_admin();
		$this -> mysql = $this -> mysql_connect_fmxw();
        $this -> lang = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '中文';
        $this -> locale = $this -> lang == 'English' ? 'en' : 'cn';
    }

    /* 寻找输入框的输入处理. */
	public function typeahead() {
		$ary = array();
		$q = $_GET['q'];
		$query = "select keyword from keywords where keyword like '%" . $q . "%' order by kid";

		$res = $this->mdb2->query($query);
		if (PEAR::isError($res)) die($res->getMessage());
		while($row = $res->fetchRow()) {
			$ary[] = iconv('UTF-8', 'UTF-8//TRANSLIT', $row[0]);
		}
		echo json_encode($ary);
	}

    /* 获取所有categories */
	public function get_categories() {
        $ary = array();
        $sql = "select cid, curl, name from categories where active='Y' order by frequency, weight";
	        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res))
            die($res -> getMessage());
        return $res;
	}
	public function get_category() {
        $ary = array();
        $query = "select cid, curl, name from categories where active='Y' order by frequency, weight";
        $res = mysql_query($query);
        while($row = mysql_fetch_assoc($res)) {
            array_push ($ary, $row);
        }
        return $ary;
    }

    /* 中英文切换,根据session.fmxw.language来决定label的显示. */
    public function get_search_label($search) {
        return $this->_get_label($search);
    }
    public function get_list_label($list) {
        return $this->_get_label($list);
    }
 
    function get_hotest_keywords() {
        $sql = "select keyword from keywords order by kid desc limit 0," . PER_TOTAL; 
        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }    
    function get_latest_keywords() {
        $sql = "select keyword, total from keywords order by total desc limit 0," . PER_TOTAL; 
        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }
    // keywords 表:提取最新的，查询次数最多的关键词.
    function get_keywords($order = '') {
        if (!$order) $order = ' order by updated desc, total desc';
        $sql = "select keyword, total from keywords " . $order . " limit 0, " . PER_TOTAL;
        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }

    function get_key_related($q) {
        $sql = "select rid, rk, kurl from key_related where keyword like '%" . mysql_real_escape_string($q) . "%' order by rand() limit 0, " . TAB_LIST;
        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }
}
?>
