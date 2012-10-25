<?php
defined('ROOT') or define('ROOT', './');
require_once(ROOT . 'etc/coreseek.php');
require_once(ROOT . 'f12Class.php');

class FMXW_Sphinx extends f12Class
{
	var $conf, $db, $now, $dwmy, $st, $q, $h;
	function __construct() {
	    
		$this->cl = new SphinxClient();
		
		$this->conf = $this->get_config();
		$this->db = $this->mysql_connect_fmxw();
		// Some variables which are used throughout the script
		$this->now = time();
		$this->dwmy = $this->get_dwmy();
		$this->st = $this->get_sort();
        //存储每次的查询词。
        $this->q = '';
        //存储parsed的查询表单的输入参数。$_SESSION已经有存储，这里只是方便调用。
        $this->h = array();

        // 如果不设置，date()等时间函数调用时，就会warning.
		$timezone = "Asia/Shanghai";
		if(function_exists('date_default_timezone_set')) {
			//能不能根据IP判断？
			$_SESSION['timezone'] = $timezone;
            date_default_timezone_set($_SESSION['timezone']);
		}
	}

	// 加入 MongoDB 和 Memcached。
	// 连接到localhost:27017
	function get_mongo() {
		$m = new Mongo();
		$db = $m->search_lib;
		$c = $db->search;
		$this->mc = $c;
	}
	// 连接到localhost:11211
	function get_memcached() {
		$memd = new Memcached();
		$memd->addServer('localhost', 11211);
		$this->memd = $memd;
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
				'index' => "contents increment", 
				'query' => 'SELECT * from contents where cid in ($ids)',
			),
			'mysql' => array(
				'host' => "localhost",
				'username' => "fmxw",
				'password' => "fmxw123456",
				'database' => "dixi",
			),
			'page' => array(
				'size' => 25,
				'max_matches' => 1000,
			)
		);
	}
	// 参看:/etc/my.cnf
	function mysql_connect_fmxw()
	{
		$db = mysql_pconnect($this->conf['mysql']['host'], $this->conf['mysql']['username'], $this->conf['mysql']['password']) or die(mysql_error());
		mysql_select_db($this->conf['mysql']['database'], $db);
		//设置字符集,  mysql_set_charset("utf8");
		mysql_query("SET NAMES 'utf8'", $db);
		return $db;
	}
	function set_coreseek_server()
	{
		$this->cl->SetServer($this->conf['coreseek']['host'], $this->conf['coreseek']['port']);
        //以下是缺省设置，后面将会动态调整。
		$this->cl->SetMatchMode( SPH_MATCH_EXTENDED2 );
		$this->cl->SetSortMode( SPH_SORT_RELEVANCE );
		$this->cl->SetArrayResult ( true );
	}

    // 日，周，月，年有多少秒？
	function get_dwmy() {
		return array('d'=>'86400', 'w'=>'604800', 'm'=>'2678400', 'y'=>'31536000');
	}
    // 升序还是降序？
	function get_sort() {
		return array('d' => 'DESC', 'a' => 'ASC');
	}
	
	function get_matchmode($q)
	{
	    //Choose an appriate mode (depending on the query)
        $mode = SPH_MATCH_ALL;
        if (strpos($q,'~') === 0) {
            $q = preg_replace('/^\~/','',$q);
            if (substr_count($q,' ') > 1) //over 2 words
                $mode = SPH_MATCH_ANY;
        } elseif (preg_match('/[\|\(\)"\/=-]/',$q)) {
            $mode = SPH_MATCH_EXTENDED;
        }
	}
    
	
    //解析输入参数.
	function set_filter()
	{
	    //这样做就是为了简单, 操作起来方便,也便于阅读.
	    $h = $this->h;

		if(!empty($h['cate_id'])) {
			$this->SetFilter('cate_id', array($h['cate_id'])); 
		}
		if(!empty($h['item_id'])) {
			$this->SetFilter('iid', array($h['item_id']));
		}

		//排序模式
		$this->get_sortmode($h['sort']);
		
        if(empty($h['key'])) {
            $this->SetRankingMode(SPH_RANK_NONE);
        }
        else {
            $this->SetRankingMode(SPH_RANK_PROXIMITY_BM25);            
        }
		
        // 每页显示多少条记录？
        if(empty($h['limit']) || ($h['limit']>100)) $h['limit'] = $this->conf['page']['size'];
        
		//结果分组（聚类）
		// if(!empty($h['weights'])) $_SESSION[PACKAGE][CS]['weights'] = $h['weights'];
		if ($weightsum) $h['weights'] = $weightsum;
        
        /* 将结果保存在SESSION中，以便翻页时调用*/
        $_SESSION[PACKAGE][CS] = $h;
        $_SESSION[PACKAGE][CS]['q'] = $this->q;
		return $h;
	}

	//error, warning, status, fields+attrs, matches, total, total_found, time, words
	function get_res($res) 
	{
		return array(
			'total' => $res['total'],
			'total_found' => $res['total_found'],
			'time' => $res['time'],
			'ids' => array_keys($res['matches']),
		);		
	}
	
	function __p($vars, $debug=true)
	{
        if (!$debug) return;
        if (is_array($vars) || is_object($vars)) {
            echo "<pre>"; print_r($vars); echo "</pre>";
        } else
            echo $vars . "<br>\n";
    }

	function backend_scrape($key)
	{
		if (empty($key)) return;
	
		//存放需要查询的关键词，和它的相关信息，并将它们生成一个字符串。
		$ary = array();
		//如果Memcached 不存在，就生成实例。
		$m = $this->mc;
		
		//如果找到这个关键词，直接用。
		//如果没有找到这个关键词，就尽量match它。
		$got = $m->findOne(array('key'=>$key));
		if(! $got) {
			$regex = new MongoRegex("/$key/i");
			$cursor = $m->find(array('key'=> $regex));
			$got = iterator_to_array($cursor);
		}
		
		//如果'default'也是空，memcached server reset或者stop了，就需要临时赋值。
		if(empty($got)) {
			$ary = array(
				'key' => $key,
				'include' => '最新负面新闻 丑闻曝光',
				'exclude' => '-(优质 | 健康 | 营养 | 美味)',
			);
		}
		else {
			$ary = array(
				'key' => $key,
				'include' => implode(' ', $got[0]),
				'exclude' => '-(' . implode(' | ', $got[1]) . ')',
			);
		}
		//这样比较整齐.
		$search_key = $ary['key'] . ' ' . $ary['include'] . ' ' . $ary['exclude'];
	
		$dir1='/home/williamjxj/pipes/';
		$dir2='/home/williamjxj/scraper/';
		$pipes = array(
			'baidu' => array($dir1.'.baidu', $dir2.'baidu/search.pl'),
			'soso' => array($dir1.'.soso', $dir2.'qq/soso.pl'),
			'sogou' => array($dir1.'.sogou', $dir2.'sohu/sogou.pl'),
			'google' => array($dir1.'.google', $dir2.'google/gg.pl'),
			'yahoo' => array($dir1.'.yahoo', $dir2.'yahoo/yahoo.pl'),
		);	
	
		foreach($pipes as $p) {    
			$fifo = fopen($p[0], 'r+');
			fwrite($fifo, $search_key);
			fclose($fifo);
		}
	}
	
	function display_summary($results, $title="查询结果")
	{
?>

<div class="alert alert-block">
  <button type="button" class="close" data-dismiss="alert">×</button>
  <h4>
    <?=$title; ?>
  </h4>
  <p><?php echo $results; ?></p>
</div>
<?php
    }

    }
?>
