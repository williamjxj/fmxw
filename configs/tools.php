<?php
//辅助的帮助类.

/**
 * http://top.baidu.com/rss_xml.php?p=shishuoxinci
 * “囧”，本义为“光明”。从2008年开始在中文地区的网络社群间成为一种流行的表情符号，成为网络聊天、论坛、博客中使用最最频繁的字之一，它被赋予“郁闷、悲伤、无奈”之意。
 * “囧”被形容为“21世纪最风行的一个汉字”。
 */

/** http://php.net/manual/fr/function.simplexml-load-string.php
 */
function filter_xml($matches) {
    return trim(htmlspecialchars($matches[1]));
}

function __p($vars) {
	if (is_array($vars) || is_object($vars)) {
		echo "<pre>";
		print_r($vars);
		echo "</pre>";
	}
	else
		echo $vars . "<br>\n";
}

//访问IP统计
function ip_block() 
{
	$m = new Memcached();
	$m->addServer('localhost', 11211);
	
	do {
		/* fetch IP list and its token */
		$ips = $m->get('ip_block', null, $cas);
		/* if list doesn't exist yet, create it and do
		   an atomic add which will fail if someone else already added it */
		if ($m->getResultCode() == Memcached::RES_NOTFOUND) {
			$ips = array($_SERVER['REMOTE_ADDR']);
			$m->add('ip_block', $ips);
		/* otherwise, add IP to the list and store via compare-and-swap
		   with the token, which will fail if someone else updated the list */
		} else { 
			$ips[] = $_SERVER['REMOTE_ADDR'];
			$m->cas($cas, 'ip_block', $ips);
		}   
	} while ($m->getResultCode() != Memcached::RES_SUCCESS);
}

//注册用户控制
function user_store()
{
	$m = new Memcached();
	$m->addServer('localhost', 11211); //maybe other memcached server.
	
	do {
		$users = $m->get('user_list', null, $cas);
		if ($m->getResultCode() == Memcached::RES_NOTFOUND) {
			$users = array($_SESSION[PACAKGE]['username']);
			$m->add('user_list', $users);
		} else { 
			$users[] = $_SESSION[PACKAGE]['username'];
			$m->cas($cas, 'user_list', $users);
		}   
	} while ($m->getResultCode() != Memcached::RES_SUCCESS);
}

setlocale(LC_ALL, 'zh_CN');
//If you are looking for a getlocale() function simply pass 0 (zero) as the second parameter to setlocale().
echo setlocale(LC_ALL, 0);


?>
