<?php
require_once (ROOT . 'etc/coreseek.php');
require_once (ROOT . 'configs/base.inc.php');

class FMXW extends BaseClass 
{
    var $cl, $mdb2, $conf, $db, $memd, $now, $pipes, $search;
    function __construct() 
    {
        parent::__construct();
        $this -> cl = new SphinxClient();
        $this -> mdb2 = $this -> pear_connect_admin();

        $this -> conf = $this -> get_config();
        $this -> db = $this -> mysql_connect_fmxw();
        $this -> memd = $this -> get_memcached();
        $this -> now = time();
        $this -> pipes = $this -> get_pipes();
        $this->search = $this->init_search();
    }

    /*
     * 这里维护一个数组，存放所有关于'search'的信息。将它传递给$_SESSION[PACKAGE][SEARCH]
     */
    function init_search() {
        return array(
            'q' => '',
            'e' => '( 负面|丑闻|真相 ) | ( 新闻|评价|曝光 )',
            'key' => '',
            'page' => 1,
            'total' => 0,
            'total_pages' => 0,
            'total_found' => 0,
            'time' => 0,
            'category' => '',
            'cate_id' => 0,
            'item' => '',
            'iid' => 0,
            'cid' => 0,
            'dwmy' => '', //day24,week,month,year
            'core' => 1, //1-负面度,2-相关度,3-评论数
            'attr' => '', //clicks,guanzhu,pinglun,likes,fandui
            'sort' => '',
        );       
    }
    function unset_search() {
        foreach(array_keys($_SESSION[PACKAGE][SEARCH]) as $k) unset($$_SESSION[PACKAGE][SEARCH][$k]);
    }
    
    function set_keywords($key) {
        //将关键词写入keywords表
        if ($key != '') {
            $user = isset($_SESSION[PACKAGE]['username']) ? $_SESSION[PACKAGE]['username'] : '';

            $query = "INSERT INTO keywords (keyword,createdby,created) VALUES " . "('" . $key . "', '" . $user . "', now()) ON DUPLICATE KEY UPDATE total=total+1";
            mysql_query($query);
        }
        return true;
    }

    function get_pipes() {
        $dir = '/home/williamjxj/scraper/';
        return array($dir.'.baidu', $dir.'.soso', $dir.'.google', $dir.'.yahoo');
    }
    //没有用constant, 而是用数组，因为变量较多，放在数组中便于调整。
    function get_config() {
        return $conf = array(
			'coreseek' => array(
				'host' => 'localhost', 
				'port' => 9313, 
				'index' => "contents increment", 
				'query' => 'SELECT * from contents where cid in ($ids)', 
			), 
			'sphinx' => array(
				'host' => 'localhost', 
				'port' => 9312, 
				'index' => "keyRelated delta", 
			), 
			'mysql' => array(
				'host' => "localhost", 
				'username' => "fmxw", 
				'password' => "fmxw123456", 
				'database' => "dixi", 
			), 
			'page' => array(
				'limit' => 25, 
				'max_matches' => 1000, 
			)
		);
    }

    // 参看:/etc/my.cnf
    //这里我用了overwrite,应为想用不同的用户来建立链接，提高访问性能。
    function mysql_connect_fmxw() {
        $db = mysql_pconnect($this -> conf['mysql']['host'], $this -> conf['mysql']['username'], $this -> conf['mysql']['password']) or die(mysql_error());
        mysql_select_db($this -> conf['mysql']['database'], $db);
        //设置字符集,  mysql_set_charset("utf8");
        mysql_query("SET NAMES 'utf8'", $db);
        return $db;
    }

    function set_coreseek_server() {
        $this -> cl -> SetServer($this -> conf['coreseek']['host'], $this -> conf['coreseek']['port']);
        //以下是缺省设置，后面将会动态调整。
		$this -> cl -> SetMatchMode(SPH_MATCH_EXTENDED2);
        $this -> cl -> SetSortMode(SPH_SORT_RELEVANCE);
        $this -> cl -> SetArrayResult(true);
    }

    // 日，周，月，年有多少秒？
    function get_dwmy() {
        return array('d' => '86400', 'w' => '604800', 'm' => '2678400', 'y' => '31536000');
    }

    // 升序还是降序？
    function get_sort() {
        return array('d' => 'DESC', 'a' => 'ASC');
    }

    // 连接到localhost:11211
    function get_memcached() {
        $memd = new Memcached();
        $memd -> addServer('localhost', 11211);
        return $memd;
    }

    //还有用，网友在查。
    function get_key_related($q) {
        $sql = "select rid, rk, kurl from key_related where keyword like '%" . mysql_real_escape_string($q) . "%' order by rand() limit 0, " . TAB_LIST;
        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }

	// 替代f12Class的get_key_related, 用sphinx 的/etc/new9313.conf
	// 'localhost', 9312, "keyRelated delta"
	function get_key_related_1($q) {
		if (empty($q)) return;
		$kss = new SphinxClient;
        $kss->SetServer($this->conf['sphinx']['host'], $this->conf['sphinx']['port']);
        $kss->SetMatchMode ( SPH_MATCH_EXTENDED2 );
		$kss->SetSortMode(SPH_SORT_EXTENDED,'@random');
		$kss->SetLimits(0, 10);

		$res = $kss->Query($q, $this->conf['sphinx']['index']);
		if ($res === false) {
			echo "查询失败 - " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $kss -> GetLastError() . "<br>\n";
			return;
		} else if ($kss -> GetLastWarning()) {
			echo "WARNING for " . $q . ": [at " . __FILE__ . ', ' . __LINE__ . ']: ' . $kss -> GetLastWarning() . "<br>\n";
		}
		if($res['total']<=0) return;
		$ids = array_keys($res['matches']);
		$sql = "select rid, rk, kurl from key_related where rid in (" . implode(',',$ids) . ")";
		$ary = array();
		//echo $sql;
		$r = mysql_query($sql);
		while($row = mysql_fetch_assoc($r)) {
			array_push($ary, $row);
		}
		return $ary;
	}
	
	function get_repings_by_cid($cid){
		$ary = array();
		$sql = "select * from pk where cid=". $cid . " ORDER BY id DESC";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_assoc($res)) {
			array_push($ary, $row);
		}
		return $ary;
	}
	function get_contents_by_cid($cid){
		$sql = "select title, pinglun from contents where cid=". $cid;
		$res = mysql_query($sql);
		$row = mysql_fetch_assoc($res);
		return $row['title'];
	}

    function get_repings_by_keyword($q){
        $ary = array();
        $sql = "select * from pk  where  keyword='". mysql_real_escape_string($q) . "' ORDER BY created DESC";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        return $ary;
    }

    function generate_sql($ids) {
        $lang_case = " and language = '" . $this -> lang . "' ";
        //$sql = "select cid, title, content from contents where cid in (".$ids.") " . $lang_case . " order by cid desc limit  " . $row_no . "," . ROWS_PER_PAGE;

        $sql = "select cid, title, content, date(created) as date, createdby  from contents where cid in (" . $ids . ") order by created desc";
        $_SESSION[PACKAGE][SEARCH]['sql'] = $sql;
        return $sql;
    }

    function write_named_pipes($search_key, $where='行数') {
        $count = 1;
        $dir = '/home/williamjxj/scraper/';
        //劣质 过期 腐烂 变质 腐败 丑闻 最新负面新闻 曝光 内部 传闻
        $keys = array($search_key, $search_key.'(负面|丑闻|真相)(新闻|评价|曝光)');
        $ary  = array('.baidu', '.soso', '.google', '.yahoo');
        $fh = fopen($dir.'/logs/web.log', 'a+') or die("Can't open file at __FILE__");
        
        foreach($ary as $p) {
            foreach($keys as $k) {
                $fifo = fopen($dir.$p, 'w+');
                fwrite($fifo, $k);
                fclose($fifo);
                fwrite($fh, $where. ', ' . $p.'-'.$count++.', ['.$k."]\n");                
            }
        }
        fflush($fh);
        fclose($fh);
    }

	// 用于摘要。
    function mb_highlight($data, $query, $ins_before, $ins_after) {
		if (empty($query)) return $data;
        $result = '';
        while (($poz = mb_strpos(mb_strtolower($data), mb_strtolower($query))) !== false) {
            $query_len = mb_strlen($query);
            $result .= mb_substr($data, 0, $poz) . $ins_before . mb_substr($data, $poz, $query_len) . $ins_after;
            $data = mb_substr($data, $poz + $query_len);
        }
        if (empty($result))
            $result = $data;
        //no keywords.
        return $result;
    }

    function my_strip($str) {
		//$t = strip_tags($str);
        $t = preg_replace("/^\s*lang=\"zh\">/", '', $str);
        $t = preg_replace("/^\s+/s", '', $t); //remove leading space/lines.
        $t = preg_replace("/\s+$/s", '', $t); //remove tail space/lines.
        $t = preg_replace("/&nbsp;/s", ' ', $t);
        return $t;
    }

	function get_categories() {
		$ary = array();
		$sql = "select cid, name from categories order by weight";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res, MYSQL_NUM)) array_push($ary, $row);
		return $ary;
	}
	function get_items($cid) {
		$ary = array();
		$sql = "select iid, name from items where cid=$cid order by weight";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res, MYSQL_NUM)) array_push($ary, $row);
		return $ary;
	}
	
    function insert_pk() 
	{
		$fayan = mysql_real_escape_string(trim($_POST['fayan']));
		$keyword = mysql_real_escape_string(trim($_POST['kw']));
        $cid = intval($_POST['cid']);
		$pk = $_POST['pk'];
        $captcha = $_POST['captcha'];
		
		if(empty($_POST['zhichi'])) $zhichi = rand(10, 1000);
		else $zhichi = $_POST['zhichi'];

		$qqwry=new qqwry('etc/qqwry.dat');
		$arr=$qqwry->q($_SERVER['REMOTE_ADDR']);
		$arr[0]=iconv('GB2312','UTF-8',$arr[0]);
		$arr[1]=iconv('GB2312','UTF-8',$arr[1]);
		$area = $arr[1] ? $arr[0].'|'.$arr[1] : $arr[0];
		$area = mysql_real_escape_string($area);
		
		if(empty($_POST['author'])) 
			$author = isset($_SESSION[PACKAGE]['username']) ?  $_SESSION[PACKAGE]['username'] : '访问用户';
		else $author = mysql_real_escape_string(trim($_POST['author']));

        $sql = "insert into pk(pk, author, keyword, zhichi, fayan, created, area, cid, captcha) values('" . 
			$pk		. "', '" .
			$author . "', '" .
			$keyword. "', " .
			$zhichi . ", '" .
			$fayan	. "', now(), '" . 
			$area . "', " .
            $cid . ", '" .
            $captcha . "')";
		
        $res = mysql_query($sql);
        if(! $res) return false;
        $pid = mysql_insert_id();

        $sql = "update contents set pinglun=pinglun+1 where cid=".$cid;
        $res = mysql_query($sql);

		return $pid;
    }	

    function draw()
    {
        $current_page = $_SESSION[PACKAGE][SEARCH]['page'] ? $_SESSION[PACKAGE][SEARCH]['page'] : 1;
        $total_pages = $_SESSION[PACKAGE][SEARCH]['total_pages'] ? $_SESSION[PACKAGE][SEARCH]['total_pages'] : 1;
        $links = array();
        $queryURL = '';
		/**
        if (count($_GET)) {
            foreach ($_GET as $key => $value) {
                if ( $key=='q' ) $queryURL .= '&' . $key . '=' . $value;
            }
        }
        foreach($_SESSION[PACKAGE][SEARCH] as $k=$v) {
          $queryURL .= '&' . $k . '=' . urlencode($v);
        }
		*/
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

    // 输出内容.
    function get_content_1($cid) {
        $sql = "select * from contents where cid=" . $cid;
        $res = mysql_query($sql);
        $row = mysql_fetch_assoc($res);
		//if(mysql_num_rows($res)>0) $_SESSION[PACKAGE][SEARCH]['title']=htmlspecialchars($row['title']);
		// $this->__p($_SESSION);
        mysql_free_result($res);
        return $row;
    }
}
?>
