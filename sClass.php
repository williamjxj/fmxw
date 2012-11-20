<?php
require_once (ROOT . 'etc/coreseek.php');
require_once (ROOT . 'f12Class.php');

class FMXW_Sphinx extends f12Class 
{
    var $cl, $mdb2, $conf, $db, $memd, $now, $pipes;
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
		
		//已经在baseClass中定义了。
        //$this -> lang = $_SESSION[PACKAGE]['language'];
        //$this -> locale = $_SESSION[PACKAGE]['language'] == 'English' ? 'en' : 'cn';
        
		// Some variables which are used throughout the script
        // $this -> m = $this -> get_mongo();
        //$this -> dwmy = $this -> get_dwmy();
        //$this -> st = $this -> get_sort();
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

    function get_matchmode($q) {
        //Choose an appriate mode (depending on the query)
        $mode = SPH_MATCH_ALL;
        if (strpos($q, '~') === 0) {
            $q = preg_replace('/^\~/', '', $q);
            if (substr_count($q, ' ') > 1)//over 2 words
                $mode = SPH_MATCH_ANY;
        } elseif (preg_match('/[\|\(\)"\/=-]/', $q)) {
            $mode = SPH_MATCH_EXTENDED;
        }
    }

    // 加入 MongoDB 和 Memcached。
    //XX 连接到localhost:27017
    function get_mongo() {
        $m = new Mongo();
        $db = $m -> search_lib;
        $c = $db -> keywords;
        return $c;
    }

    // 连接到localhost:11211
    function get_memcached() {
        $memd = new Memcached();
        $memd -> addServer('localhost', 11211);
        return $memd;
    }

    //XX 不要插入keyword和tags表了，代替用
    function set_keywords_old($key) {
        if (empty($key))
            return;			
        $matched = $this -> m -> findOne(array('q' => $key));
        if (empty($matched)) {
            $this -> m -> insert(array('q' => $key, 'count' => 1, 'date' => new MongoDate()));
        } else {
            // quicker than 'q'?
            $id = (string)$matched['_id'];
            $this -> m -> update(
				array('_id' => new MongoId($id)), 
				array('$inc' => array('count' => 1), '$set' => array('date' => new MongoDate())), 
				array('upsert' => true)
			);
        }
        //return $this->m->findOne(array('q' => $key));
		/*将关键词的相关词放在哪里？这里插入数据库的keywords表，Perl的Scraper从数据库中找到kid，然后将相关词插入key_related表。 */
		$user = isset($_SESSION[PACKAGE]['username']) ? $_SESSION[PACKAGE]['username'] : '';
		if (empty($user))
			$user = basename(__FILE__) . ', search';

		$query = "INSERT INTO keywords (keyword,createdby, created) VALUES " . "('" . $key . "', '" . $user . "', now()) ON DUPLICATE KEY UPDATE total=total+1";
		mysql_query($query);
    
	}

	// XX 以后修改之，现在mongoDB对应项为空，所以需要从mysql传过来。
    function get_key_related_old($q) {
        $sql = "select rid, rk, kurl from key_related where keyword like '%" . $q . "%' order by rand() limit 0, " . TAB_LIST;
        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        foreach ($res as $v) {
            $this -> m -> update(
				array('key' => $v{'rk'}), 
				array('$inc' => array('count' => 1), '$set' => array('date' => new MongoDate())), 
				array('upsert' => true)
			);
        }
        return $res;
    }

	// 替代f12Class的get_key_related, 用sphinx 的/etc/new9313.conf
	// 'localhost', 9312, "keyRelated delta"
	function get_key_related($q) {
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
	
    function get_repings_by_keyword($q){
        $ary = array();
        $sql = "select * from pk  where  keyword='". mysql_real_escape_string($q) . "' ORDER BY created DESC";
        $res = mysql_query($sql);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        return $ary;
    }
	
    //error, warning, status, fields+attrs, matches, total, total_found, time, words
    function set_session($res) 
	{
        //根据 Sphinx Query返回的结果填充SESSION,该SESSION存于memcached中。
        $_SESSION[PACKAGE][SEARCH]['key'] = empty($_GET['q']) ? '' : trim($_GET['q']);
        $_SESSION[PACKAGE][SEARCH]['page'] = empty($_GET['page']) ? 1 : $_GET['page'];
        $_SESSION[PACKAGE][SEARCH]['total'] = $res['total'];
        $_SESSION[PACKAGE][SEARCH]['total_pages'] = ceil($res['total'] / ROWS_PER_PAGE);
        $_SESSION[PACKAGE][SEARCH]['total_found'] = $res['total_found'];
        $_SESSION[PACKAGE][SEARCH]['time'] = $res['time'];
    }

    function generate_sql($ids) {
        $lang_case = " and language = '" . $this -> lang . "' ";
        //$sql = "select cid, title, content from contents where cid in (".$ids.") " . $lang_case . " order by cid desc limit  " . $row_no . "," . ROWS_PER_PAGE;

        $sql = "select cid, title, content, date(created) as date, createdby  from contents where cid in (" . $ids . ") order by created desc";
        $_SESSION[PACKAGE][SEARCH]['sql'] = $sql;
        return $sql;
    }

	// XX mongo数据库一律用shpinx代替！
    function backend_scrape_mongo($key) {
        if (empty($key))
            return;

        //存放需要查询的关键词，和它的相关信息，并将它们生成一个字符串。
        $ary = array();
        //如果Memcached 不存在，就生成实例。
        $m = $this -> m;

        //如果找到这个关键词，直接用。
        //如果没有找到这个关键词，就尽量match它。
        $got = $m -> findOne(array('key' => $key));
        if (!$got) {
            $regex = new MongoRegex("/$key/i");
            $got = $m -> findOne(array('key' => $regex));
            //返回第一个就可以，如果返回所有的，就没有必要。
            //$got = iterator_to_array($cursor);
        }

        //如果'default'也是空，memcached server reset或者stop了，就需要临时赋值。
        if (empty($got)) {
            $ary = array(
				'key' => $key, 
				'include' => '最新负面新闻 丑闻曝光', 
				'exclude' => '-(优质 | 健康 | 营养 | 美味)', 
			);
        } else {
            $ary = array(
				'key' => $key, 
				'include' => implode(' ', $got[0]), 
				'exclude' => '-(' . implode(' | ', $got[1]) . ')', 
			);
        }
        //这样比较整齐.
        $search_key = $ary['key'] . ' ' . $ary['include'] . ' ' . $ary['exclude'];

        // $this -> write_named_pipes($search_key);
    }

	//XX 'sogou' => array($dir . '.sogou'),
    function write_named_pipes_old($search_key) {
        //每次点击都搜索，好像不太好。
        //改为：如果今天点击过了，就不再搜索了。
        foreach ($this->pipes as $p) {
            $fifo = fopen($p, 'r+');
            fwrite($fifo, $search_key);
            fclose($fifo);
        }
    }
    function write_named_pipes($search_key, $where) {
        $count = 1;
        $dir = '/home/williamjxj/scraper/';
        //劣质 过期 腐烂 变质 腐败 丑闻 最新负面新闻 曝光 内部 传闻
        $keys = array($search_key, $search_key.'(负面|丑闻|真相)(新闻|评价|曝光)');
        $ary  = array('.baidu', '.soso', '.google', '.yahoo');
        $fh = fopen($dir.'/logs/web.log', 'a+') or die("Can't open file at __FILE__");
        
        foreach($ary as $p) {
            $fifo = fopen($dir.$p, 'w+');
            foreach($keys as $k) {
                fwrite($fifo, $k);
                fwrite($fh, $where. ', ' . $p.'-'.$count++.', ['.$k."]\n");                
            }
            fclose($fifo);            
        }
        fflush($fh);
        fclose($fh);
    }

	//XX 找到匹配的添加词，添加到关键词之后用于查询。
    function backend_scrape($key) {
        if (empty($key))
            return;

        //存放需要查询的关键词，和它的相关信息，并将它们生成一个字符串。
        $ary = array();
        $m = $this -> memd;

        //根据查询关键词，从memcached中找相关的include,exclude。
        $got = $m -> get($key);
        //utf8_encode();mb_detect_encoding();

        if (empty($got)) {
            //if($m->getResultCode() == Memcached::RES_NOTFOUND) echo "没有设置<br>\n";
            //else echo "设置了，但是无法得到信息。[". $key . "]<br>\n";
            $got = $m -> get('default');
        }

        //如果'default'也是空，memcached server reset或者stop了，就需要临时赋值。
        if (empty($got)) {
            $ary = array(
				'key' => $key, 
				'include' => '最新负面新闻 丑闻曝光', 
				'exclude' => '-(优质 | 健康 | 营养 | 美味)', 
			);
        } else {
            $ary = array(
				'key' => $key, 
				'include' => implode(' ', $got[0]), 
				'exclude' => '-(' . implode(' | ', $got[1])  . ')'
			);
        }
        //这样比较整齐.
        $search_key = $ary['key'] . ' ' . $ary['include'] . ' ' . $ary['exclude'];
        $this -> write_named_pipes($search_key);
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

    //XX Not use anymore.
    function my_process($docs) {
        $newd = array();
        foreach ($docs as $str) {
            $t = preg_replace("/^\s*lang=\"zh\">/", '', $str);			
            $t = preg_replace("/^\s+/s", '', $t);
            //remove leading space/lines.
            $t = preg_replace("/\s+$/g", '', $t);
            //remove tail space/lines.
            $t = preg_replace("/&nbsp;/s", ' ', $t);
            $newd[] = trim($t);
        }
        return $newd;
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
		return mysql_insert_id();
    }	

}
?>
