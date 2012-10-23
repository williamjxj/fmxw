<?php
function backend_scrape($key)
{
	if (empty($key)) return;
	$key = $key or $_SESSION[PACKAGE][SEARCH]['key'];

	/*
	$pattern = '//i';
	if() {}
	elseif() {}
	else {}
	*/
		
	//存放需要查询的关键词，和它的相关信息，并将它们生成一个字符串。
	$ary = array();
	// 如果Memcached 不存在，就生成实例。
	$m = new Memcached(); //memcached
	$m->addServer('localhost', 11211);

	//根据查询关键词，从memcached中找相关的include,exclude。
	$got = $m->get($key); //utf8_encode();mb_detect_encoding();

	if (empty($got)) {
		if($m->getResultCode() == Memcached::RES_NOTFOUND) echo "没有设置<br>\n";
		else echo "设置了，但是无法得到信息。[". $key . "]<br>\n";
		$got = $m->get('default');
	}

	if(empty($got)) {
		$ary = array(
			'key' => $key,
			'include' => '+ 丑闻 最新负面新闻 曝光',
			'exclude' => '- 优质 健康 营养 美味',
		);
	}
	else {
		$ary = array(
			'key' => implode(' ', $got['keyword']),
			'include' => implode(' ', $got['include']),
			'exclude' => implode(' ', $got['exclude']),
		);
	}
	$search_key = $ary['key'] . ' ' . $ary['include'] . ' ' . $ary['exclude'];
	echo "<pre>"; print_r($got); print_r($ary); echo $search_key; echo "</pre>";

	$slog = "/tmp/scraper.log";
	$scrapers = array
		'/home/williamjxj/scraper/baidu/search.pl',
		'/home/williamjxj/scraper/google/gg.pl',
		'/home/williamjxj/scraper/yahoo/yahoo.pl',
		'/home/williamjxj/scraper/qq/soso.pl'
	);
	
	// exec("nohup /home/williamjxj/scraper/baidu/search.pl '" . $key . "' >/dev/null 2>&1 ");
	foreach ($scrapers as $s) {  
		$t = "nohup " . $s . "'" . $search_key . "' >" . $slog . " 2>&1 ";
		echo $t . "<br>\n";
		// exec($t);
	}
}
?>
