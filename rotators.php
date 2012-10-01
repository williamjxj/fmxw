<?php
session_start();
define('MAXLEN', 200);

// header ("content-type: text/xml; charset=utf-8");
// if(!headers_sent()) header('Content-Type: application/json; charset=utf-8', true,200);
//BBC,
$rss_array = array(
	'cn' => 'http://rss.sina.com.cn/news/marquee/ddt.xml',
	'cn_sina' => 'http://rss.sina.com.cn/news/marquee/ddt.xml',
	'cn_163' => 'http://news.163.com/special/00011K6L/rss_newstop.xml',
	'cn_qq' => 'http://news.qq.com/newsgn/rss_newsgn.xml',
	'cn_sohu' => 'http://news.sohu.com/rss/pfocus.xml',
	'hk' => 'http://mingpao.feedsportal.com/c/33528/f/585672/index.rss',
	'tw' => 'http://www.etaiwannews.com/rss/news_onlytn.xml',
	'sg' => 'http://zaobao.feedsportal.com/c/34003/f/616929/index.rss',
	'na' => 'http://zaobao.feedsportal.com/c/34003/f/616930/index.rss',
	'default' => 'http://rss.sina.com.cn/news/marquee/ddt.xml',
);

$rss_world_array = array(

	'daily' => 'http://www.chinadaily.com.cn/rss/china_rss.xml',
	'post' => 'http://www.chinapost.com.tw/rss/front.xml',
	'sh' => 'http://www.shanghaidaily.com/rss/latest/',
	'bbc' => 'http://feeds.bbci.co.uk/news/world-asia-pacific-11710880/rss.xml',
);

if (isset($_GET['ww']) && (! preg_match("/cn_/",$_GET['ww']))) {
	if(isset($_GET['rss'])) {
		$rss_url = $rss_world_array[$_GET['rss']];
	}
	else {
		$rss_url = $rss_world_array['daily'];
	}
}
else {
	if(isset($_GET['ww'])) {
		$rss_url = $rss_array[$_GET['ww']];
	}
	else {
		$rss_url = $rss_array[$_GET['rss']];
	}
}


$rawFeed = file_get_contents($rss_url);

$xml = simplexml_load_string($rawFeed);
//$xml = new SimpleXmlElement($rawFeed);

if(count($xml) == 0) return;

$ary = array();
foreach($xml->channel->item as $item) {
	$sa = array();
	$sa['title'] = (string)parse_cdata(trim($item->title));
	$sa['text'] = parse_desc(parse_cdata(trim($item->description)));
	$sa['link'] = (string)trim($item->link);
	$sa['date'] = get_datetime((string)$item->pubDate);
	
	//array_push($ary, $sa);
	array_push($ary, $sa);
}

//echo "<pre>"; print_r($ary); echo "</pre>";
echo json_encode($ary);
exit;


function parse_cdata($str) {
	if(preg_match("/CDATA/", $str)) {
		$str = preg_replace("/^.*CDATA[/", '', $str);
		$str = preg_replace("/]]$/", '', $str);		
	}
	return $str;
}

function parse_desc($summary) {
	if (!isset($summary) || empty($summary) || preg_match("/^\s+$/", $summary))		return '';

	// echo "\n[".$summary."]\n";
	// Create summary as a shortened body and remove images, extraneous line breaks, etc.
	$summary = preg_replace("/<img[^>]*>/i", "", $summary);
	$summary = preg_replace("/^(<br[\s]?\/>)*/i", "", $summary);
	$summary = preg_replace("/(<br[\s]?\/>)*$/i", "", $summary);
	$summary = preg_replace("/^\s+/", "", $summary);
	$summary = preg_replace("/\s+$/", "", $summary);
	
	$summary = trim($summary);
	// Truncate summary line to 100 characters, NOT WORK!
	// if(strlen($summary) > MAXLEN)
	//   $summary = substr($summary, 0, MAXLEN) . '...';

	return $summary;
}

function get_datetime($dt) {
	return date("m/d H:i",  strtotime(trim($dt)));
}
?>
