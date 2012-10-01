<?php
defined('SITEROOT') or define('SITEROOT', './');
require_once(SITEROOT."configs/base.inc.php");

define('TAB_LIST', 10);
define('PER_TOTAL', 5);
require_once(SITEROOT.'configs/mini-app.inc.php');
mysql_connect_dixi();

class DixiClass extends BaseClass
{
	var $sid, $url, $self;
	public function __construct($site_id) {
		parent::__construct();
		$this -> sid = $site_id;
		$this -> url = $_SERVER['PHP_SELF'];
		$this -> self = basename($this -> url, '.php');
	    $this->mdb2 = $this->pear_connect_admin();
		$this->lang = $_SESSION[PACKAGE]['language'];
	}
	
	// keywords 表.
	function get_keywords() {
		// $sql = "select keyword, total, created from keywords order by total desc, created desc limit 0,6";
		$sql = "select keyword, total from keywords order by updated desc, total desc limit 0,5";
		$res = $this->mdb2->queryAll($sql);
		if (PEAR::isError($res)) {
			die($res->getMessage(). ' - line ' . __LINE__ . ': ' . $sql);
		}
		return $res;
	}
	
	// items 表.
	function get_items($category='食品') {
		$ary = array();
		$query = "select name, iid, description from items where category='" . $category . "' order by weight;";
		$res = $this->mdb2->queryAll($query, '', MDB2_FETCHMODE_ASSOC);
		if (PEAR::isError($res)) {
			die($res->getMessage(). ' - line ' . __LINE__ . ': ' . $sql);
		}
		return $res;
	}
	function get_tab_list_1()
	{
		$frequency = $_GET['group'];
		if(isset($_GET['curl']))  $t1 = " and cs.curl='".trim($_GET['curl'])."' ";
		else $t1 = " and cs.weight=1 ";

		$t = $t1 . " and cs.frequency=$frequency and ct.cate_id=cs.cid and ct.category=cs.name		
			order by rand() limit 0, " . TAB_LIST;
		$sql = "select ct.cid, ct.title, (FLOOR( 1 + RAND( ) *1000 )) AS guanzhu, ct.cate_id from contents ct, categories cs  where language='".$this->lang."' ". $t;
        $res = $this->mdb2->queryAll($sql);
        if (PEAR::isError($res)) die($res->getMessage());
        return $res;
 	}
	function get_tab_list($desc=false, $start=0, $limit=TAB_LIST) {
		if($desc) $order = ' order by cid desc ';
		else $order = ' order by cid ';
		// william add temporily:
		$order = 'order by rand() ';
		$limit = 'limit ' . $start . ', ' . $limit;
		$sql = "select cid, title, (FLOOR( 1 + RAND( ) *1000 )) AS guanzhu from contents where active='Y' and language='".$this->lang."' ". $order . $limit;
        $res = $this->mdb2->queryAll($sql);
        if (PEAR::isError($res)) die($res->getMessage());
        return $res;
 	}
	
	function get_latest() {
		return $this->get_tab_list(true, 0);
	}
	function get_hot() {
		return $this->get_tab_list(true, 10);
	}
	function get_loop1() {
		return $this->get_tab_list(false, 0, 13);
	}
	function get_loop2() {
		return $this->get_tab_list(false, 13, 13);
	}

	// contents 表.
	function get_definition() {
		$sql = "select content from contents where title = '负面新闻' ";
		$res = $this->mdb2->queryOne($sql);
		if (PEAR::isError($res)) {
			die($res->getMessage(). ' - line ' . __LINE__ . ': ' . $sql);
		}
		return $res;
	}
	
	//Same and no use
	function get_contents() {
		$ary = array();
		$sql = "select cid, title from contents where active='Y' and language='".$this->lang."' order by cid desc";
		$res = $this->mdb2->query($sql);
		if (PEAR::isError($res)) {
			die($res->getMessage(). ' - line ' . __LINE__ . ': ' . $sql);
		}
		while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
			array_push($ary, array($row['cid'], $row['title']));
		}
		return $ary;
	}

	function get_tabs() {
		$ary = array();
		$query = "select name, curl, frequency, cid, description from categories order by frequency, weight";
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			if(!isset($ary[$row['frequency']]) || !is_array($ary[$row['frequency']])) {
				$ary[$row['frequency']] = array();
			}
			array_push ($ary[$row['frequency']], $row);
		}
		return $ary;
	}
	
	function get_menu() {
		$ary = array();
		$query = "select cid, curl, name from categories where active='Y' order by frequency, weight";
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			array_push ($ary, $row);
		}
		return $ary;
	}
	
	function get_carousel1() 
	{
		$total=0; $ary1=array(); $ary2=array(); $html='';
		
		$query = "select concat(path,file) as carousel1_file from resources where file like '%300x294%' order by rand()";
		$res = mysql_query($query);
		$total = mysql_num_rows($res);
		while($row = mysql_fetch_assoc($res)) {
			$t = '<img src="'. $row['carousel1_file'] . '" />';
			array_push ($ary1, $t);
		}
	
		$t = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '';
		$sql = "select title, cid from contents where language='". $t ."' order by rand() limit 0,13";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_assoc($res)) {
			array_push($ary2, $row);
		}
	
		for($i=0; $i<$total; $i++) {
			$html .= 
			'<div class="item">' . '<a href="./general.php?cid=' . $ary2[$i]['cid'] . '">' . $ary1[$i] . '</a>' .
			'  <div class="carousel-caption">' . 
			'    <h4>' . $ary2[$i]['title'] . '</h4>' .
			'   </div>' .
			"</div>\n";
		}
		return $html;
	}
	
	function get_carousel2() {
		//1. 图片
		$ary = array();
		$query = "select concat(path,file) as carousel2_file from resources where file like '%220x130%' order by rand()";
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			$t = '<img src="'. $row['carousel2_file'] . '" />';
			array_push ($ary, $t);
		}
		
		//2. 内容
		$ary2 = array();
		$t = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '';
		$sql = "select title, cid from contents where language='". $t ."' order by rand() limit 0,13";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_assoc($res)) {
			$t = array('h4'=>$row['title'], 'a'=>'./general.php?cid='.$row['cid']);
			array_push($ary2, $t);
		}
		
		//3. 关联上面两部分：
		$count = 1;
		$nails_rest = array();
		$c = '';
		$loop = 1; $x = 0;
		$n = '<ul class="thumbnails">';
	
		foreach($ary as $t) {
			$c  = '<li class="span3"><div class="thumbnail">'."\n";
			$c .= '<a href="#">';
			$c .= $t;
			$c .= '</a>';
			$c .= '<div class="caption"><h4><a href="'.$ary2[$x]['a'].'">'.$ary2[$x]['h4'].'</a></h4>';
			$c .= '<p>'.$ary2[$x++]['h4'].'</p>';
			$c .= "</div></div></li>\n";
			$n .= $c;
			$c = '';
			$count++;
			if ($count == PER_TOTAL) {
				$n .= "</ul>\n";
				$nails_rest[] = $n;
				$n = '<ul class="thumbnails">';
				$count = 1;
				$loop ++;
			}
		}
		if($count != PER_TOTAL) {
			$n .= "</ul>\n";
			$nails_rest[] = $n;
		}
		return $nails_rest;
	}
}
?>
