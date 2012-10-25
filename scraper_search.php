<?php
function backend_scrape($key)
{
    $key = $key or $_SESSION[PACKAGE][SEARCH]['key'];
	if (empty($key)) return;

	/*
	$pattern = '//i';
	if() {}
	elseif() {}
	else {}
	*/
		
	//存放需要查询的关键词，和它的相关信息，并将它们生成一个字符串。
	$ary = array();
	//如果Memcached 不存在，就生成实例。
	$m = new Memcached(); //memcached
	$m->addServer('localhost', 11211);

	//根据查询关键词，从memcached中找相关的include,exclude。
	$got = $m->get($key); //utf8_encode();mb_detect_encoding();

	if (empty($got)) {
		//if($m->getResultCode() == Memcached::RES_NOTFOUND) echo "没有设置<br>\n";
		//else echo "设置了，但是无法得到信息。[". $key . "]<br>\n";
		$got = $m->get('default');
	}

    //如果'default'也是空，memcached server reset或者stop了，就需要临时赋值。
	if(empty($got)) {
		$ary = array(
			'key' => $key,
			'include' => '丑闻 最新负面新闻 曝光',
			'exclude' => '-优质 -健康 -营养 -美味',
		);
	}
	else {
		$ary = array(
			'key' => $key,
			'include' => implode(' ', $got[0]),
			'exclude' => '-' . implode(' -', $got[1]),
		);
	}
    //这样比较整齐.
	$search_key = $ary['key'] . ' ' . $ary['include'] . ' ' . $ary['exclude'];
	//echo "<pre>"; print_r($got); print_r($ary); echo $search_key; echo "</pre>";

	$slog = "/tmp/scraper.log";
    $sdir = "/home/williamjxj/scraper/";
	$scrapers = array(
		$sdir.'baidu/search.pl',
		$sdir.'google/gg.pl',
		$sdir.'yahoo/yahoo.pl',
		$sdir.'qq/soso.pl'
	);	
	// exec("nohup /home/williamjxj/scraper/baidu/search.pl '" . $key . "' >/dev/null 2>&1 ");
	//foreach ($scrapers as $s) {  
	//	$t = "nohup " . $s . "  '" . $search_key . "' >>" . $slog . " 2>&1 ";
		//echo $t . "<br>\n";
	//	exec($t);
	//}
	
    defined('NP_BAIDU') or define('NP_BAIDU', '/home/williamjxj/pipes/.baidu');
    
    $pipe = fopen(NP_BAIDU, 'r+');
    fwrite($pipe, $s);
    fclose($pipe);

}
?>
