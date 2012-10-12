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
?>
