<?php
require_once (ROOT . "f12Class.php");

class f1Class extends f12Class {

    var $url, $mdb2, $lang, $locale;
    public function __construct() {
        parent::__construct();
        $this -> url = $_SERVER['PHP_SELF'];
        $this -> mdb2 = $this -> pear_connect_admin();
        $this -> dbh = $this -> mysql_connect_fmxw();
        $this -> lang = $_SESSION[PACKAGE]['language'];
        $this -> locale = $_SESSION[PACKAGE]['language'] == 'English' ? 'en' : 'cn';
        $this -> ary = array('top10', 'weekhotspot', 'top_keyword', 'shishuoxinci', 'shijian', 'shijian_lastweek', 'shijian_lastmonth', 'hotman', 'girls', 'boys', 'FStar', 'MStar', 'ygeshou', 'ngeshou', 'titan', 'internet', 'mingjia', 'caijing', 'rich', 'zhengtan', 'lishiren', 'relation', 'cishan', 'fangchanqy');
        $this -> rss = array('guanzhu' => 'http://top.baidu.com/rss_xml.php?p=top10', 'weekhot' => 'http://top.baidu.com/rss_xml.php?p=weekhotspot', 'keyword' => 'http://top.baidu.com/rss_xml.php?p=top_keyword', 'xinxian' => 'http://top.baidu.com/rss_xml.php?p=shishuoxinci', 'events' => 'http://top.baidu.com/rss_xml.php?p=shijian', '1week' => 'http://top.baidu.com/rss_xml.php?p=shijian_lastweek', '1month' => 'http://top.baidu.com/rss_xml.php?p=shijian_lastmonth', 'person' => 'http://top.baidu.com/rss_xml.php?p=hotman', 'star' => 'http://top.baidu.com/rss_xml.php?p=FStar', );

    }

    // news.baidu.com
    //<script language="JavaScript" type="text/JavaScript" src="http://news.baidu.com/n?cmd=1&class=civilnews&pn=1&tn=newsbrofcu"></ script>
    public function process_news() {
        $news = array('civilnews', 'internews', 'mil', 'finannews', 'internet', 'housenews', 'autonews', 'sportnews', 'enternews', 'gamenews', 'edunews', 'healthnews', 'socianews', 'technnews');

        foreach ($news as $n) {

        }
    }

    ///////////// RSS 操作函数  ////////////

    function get_rss($rss_url)
	{
        $rawFeed = file_get_contents($rss_url);

        //if (preg_match("/(shishuoxinci|weekhotspot)/", $rss_url)) {
        if (preg_match("/(shishuoxinci|weekhot|keyword|hotman)/", $rss_url)) {
            //$rawFeed = iconv("GB2312", "UTF-8//TRANSLIT", $rawFeed);
            //$rawFeed = iconv("UTF-8", "GB2312", $rawFeed);
             $rawFeed = mb_convert_encoding($rawFeed, "UTF-8", "GB2312");
            //$rawFeed = preg_replace_callback('/<!\[CDATA\[(.*)\]\]>/', 'filter_xml', $rawFeed);
            // $this -> write_file($rawFeed);
            // $this->__p($rawFeed); exit;
            // return $rawFeed;
            return $this -> parse_premature($rawFeed);
        }

        if (preg_match("/keyword/", $rss_url)) {
            $rawFeed = iconv("GB2312", "UTF-8", $rawFeed);
		}
		$xml = simplexml_load_string($rawFeed);
		// echo $rss_url; $this->__p($xml);

        if (count($xml) == 0) return;

        $ary = array();
        foreach ($xml->channel->item as $item) {
            $sa = array();
            $sa['title'] = (string)$this -> parse_cdata(trim($item -> title));
            $text = $this -> parse_desc($this -> parse_cdata(trim($item -> description)));
            $sa['text'] = $this -> assembly_text($text);
            $sa['link'] = (string)trim($item -> link);
            $sa['date'] = $this -> get_datetime((string)$item -> pubDate);
            array_push($ary, $sa);
        }
        return $ary;
    }

    function parse_cdata($str) {
        if (preg_match("/CDATA/", $str)) {
            $str = preg_replace("/^.*CDATA[/", '', $str);
            $str = preg_replace("/]]$/", '', $str);
        }
        return $str;
    }

    function parse_desc($str) {

        if (!isset($str) || empty($str) || preg_match("/^\s+$/", $str))
            return '';

        $str = preg_replace("/<img[^>]*>/i", "", $str);
        $str = preg_replace("/^(<br[\s]?\/>)*/i", "", $str);
        $str = preg_replace("/(<br[\s]?\/>)*$/i", "", $str);
        $str = preg_replace("/^\s+/", "", $str);
        $str = preg_replace("/\s+$/", "", $str);

        $str = trim($str);
        return $str;
    }

    function assembly_text($text) {
        $rss = preg_replace("/<table>/", '<table class="table table-bordered table-hover table-striped table-condensed">', $text);
        $rss = preg_replace("/<th>(\d+)<\/th>/", '<th><span class="badge badge-warning">\1</span></th>', $rss);
        return $rss;
    }

    function get_datetime($dt) {
        return date("m/d H:i", strtotime(trim($dt)));
    }

    ///////////// 处理百度的top.baidu.com部分  ////////////
    function get_guanzhu() {
        $rurl = 'http://top.baidu.com/rss_xml.php?p=top10';
        return $this -> get_rss($rurl);
        //$this->__p($this->get_rss($rurl));
    }

    function parse_premature($content) {
        $a = array();
        $ary = array();
        preg_match("/<item>(.*?)<\/item>/s", $content, $matches);
        $t = $matches[1];
        preg_match("/<title>(.*?)<\/title>/s", $t, $t1);
        preg_match("/<table>(.*?)<\/table>/s", $t, $t2);
        preg_match("/<link>(.*?)<\/link>/s", $t, $t3);
        preg_match("/<pubDate>(.*?)<\/pubDate>/s", $t, $t4);
        $a['title'] = $t1[1];
        $a['text'] = '<table class="table table-bordered table-hover table-striped table-condensed">' . $t2[1] . "</table>";
        $a['link'] = $t3[1];
        $a['date'] = $t4[1];
        array_push($ary, $a);
        return $ary;
    }

    ///////////////////////////////////////////////////
    public function get_category() {
        $ary = array();
        $query = "select cid, curl, name from categories where active='Y' order by frequency, weight";
        $res = mysql_query($query);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        return $ary;
    }

    public function get_news_label($footer) {
        return $this -> _get_label($footer);
    }

    function get_category_contents($cate_id) 
	{		
        $sql = "select cid, title, url, pubdate, author, source, clicks, tags, likes, fandui, guanzhu, pinglun,
        category, cate_id, item, iid, created 
        from contents  where language='" . $this -> lang . "' and cate_id=$cate_id order by cid desc limit 0,".ROWS_PER_PAGE;

		if(!isset($_SESSION[PACKAGE]['cate_item']) || empty($_SESSION[PACKAGE]['cate_item']['total_pages'])) {
			$total = $this->get_category_count($cate_id);
			$total_pages = ceil($total / ROWS_PER_PAGE);
			$_SESSION[PACKAGE]['cate_item']['total'] = $total;
			$_SESSION[PACKAGE]['cate_item']['total_pages'] = $total_pages;
			
			$_SESSION[PACKAGE]['cate_item']['page'] = 1;
			$_SESSION[PACKAGE]['cate_item']['sql'] = $sql;
		}
        else {
            $this->__p($_SESSION[PACKAGE]);
            exit;        
        }			

        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res))
            die($res -> getMessage());
		if (!empty($res[0]['category'])) {
			$_SESSION[PACKAGE][SEARCH]['key'] = $res[0]['category'];
			//array_push($_SESSION[PACKAGE][SEARCH], array('key' => $res[0]['category']));
			//$this->__p($_SESSION);
		}
        return $res;
    }

    function get_category_count($cate_id) {
        $sql = "select count(*) from contents where cate_id =" . $cate_id;
        $res = mysql_query($sql);
        $row = mysql_fetch_row($res);
        mysql_free_result($res);
        return $row[0];
    }

    function get_item_count($iid) {
        $sql = "select count(*) from contents where iid=" . $iid;
        $res = mysql_query($sql);
        $row = mysql_fetch_row($res);
        mysql_free_result($res);
        return $row[0];
    }

    //////////////// Items ////////////////
    function get_items($cate_id = 0) {
        if ($cate_id)
            $where = " where cid=" . $cate_id;
        else
            $where = '';
        $order = " order by weight ";
        $limit = "";
        $sql = "select iid, name, iurl, category, cid, description from items " . $where . $order . $limit;
        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        return $res;
    }

    function get_item_contents($iid) 
    {
        $sql = "select cid, title, url, pubdate, author, source, clicks, tags, likes, fandui, guanzhu, pinglun,
        category, cate_id, item, iid, created 
        from contents  where language='" . $this -> lang . "' and iid=$iid order by iid desc limit 0, ".ROWS_PER_PAGE;

        if(!isset($_SESSION[PACKAGE]['cate_item']) || empty($_SESSION[PACKAGE]['cate_item']['total_pages'])) {
            $total = $this->get_item_count($iid);
            $total_pages = ceil($total / ROWS_PER_PAGE);
            $_SESSION[PACKAGE]['cate_item']['total'] = $total;
            $_SESSION[PACKAGE]['cate_item']['total_pages'] = $total_pages;
            
            $_SESSION[PACKAGE]['cate_item']['page'] = 1;
            $_SESSION[PACKAGE]['cate_item']['sql'] = $sql;
        }
        else {
            $this->__p($_SESSION[PACKAGE]);
            exit;        
        }

        $res = $this -> mdb2 -> queryAll($sql, '', MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($res))
            die($res -> getMessage());
		if ($res[0]['item'])
			$_SESSION[PACKAGE][SEARCH]['key'] = $res[0]['item'];
        return $res;
    }


    function assemble_sitemap($sm) {
        $info = array();
        if (preg_match("/English/i", $this -> lang)) {
            $info['title'] = $sm[1];
            $info['content'] = "Currently this model is under developing, will be ready shortly.<br>\n";
        } else {
            $info['title'] = $sm[0];
            $info['content'] = "目前该分类还处在开发阶段，很快就会有内容呈现。谢谢关注。<br>\n";
        }
        return $info;
    }
	// NO USE.
	function get_item_list($cate_id) 
	{
		$sql = "select name, iid, description, category, cid from items where cid=$cate_id order by weight;";
        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res))
            die($res -> getMessage());
        return $res;
	}

    //////////////// Category ////////////////
    function get_categories($cate_id = 0) {
        if ($cate_id)
            $where = " where cid=" . $cate_id;
        else
            $where = '';
        $sql = "select cid, name, curl, description from categories " . $where . " order by weight;";
        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res))
            die($res -> getMessage());
        return $res;
    }

    function get_tabs($cate_id = 0, $start = 0) {
        $ary = array();
        if ($cate_id)
            $where = " where cid = " . $cate_id;
        $order = " order by frequency, weight ";
        $limit = " limit $start, " . ROWS_PER_PAGE;
        $query = "select cid, name, curl, frequency, description from categories " . $where . $order . $limit;
        $res = mysql_query($query);
        while ($row = mysql_fetch_assoc($res)) {
            if (!isset($ary[$row['frequency']]) || !is_array($ary[$row['frequency']])) {
                $ary[$row['frequency']] = array();
            }
            array_push($ary[$row['frequency']], $row);
        }
        return $ary;
    }

    function get_contents_by_category($cate_id = 0, $start = 0) {
        $and = '';
        if ($cate_id)
            $and = ' and cate_id = ' . $cate_id;
        $order = 'order by cid desc ';
        $limit = ' limit ' . $start . ', ' . ROWS_PER_PAGE;
        $sql = $this -> content_sql . $and . $order . $limit;
        $res = $this -> mdb2 -> queryAll($sql);
        if (PEAR::isError($res))
            die($res -> getMessage());
        return $res;
    }

    function get_contents_by_item($iid = 0, $start = 0) {
        $ary = array();
        $and = '';
        if ($iid)
            $and = " and iid = " . $iid;
        $order = 'order by cid desc ';
        $limit = ' limit ' . $start . ', ' . ROWS_PER_PAGE;
        $sql = $this -> content_sql . $and . $order . $limit;
        $res = $this -> mdb2 -> query($sql);
        if (PEAR::isError($res)) {
            die($res -> getMessage() . ' - line ' . __LINE__ . ': ' . $sql);
        }
        while ($row = $res -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
            array_push($ary, $row);
        }
        return $ary;
    }

    function get_menu() {
        $ary = array();
        $query = "select cid, curl, name from categories where active='Y' order by frequency, weight";
        $res = mysql_query($query);
        while ($row = mysql_fetch_assoc($res)) {
            array_push($ary, $row);
        }
        return $ary;
    }
	
}
?>
