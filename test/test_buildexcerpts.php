<?php
$ary = array(
	'charset="zh_CN">abcdlkfdk<h3>lglkf</h3>mmmmmmm',
	'<li>abcdlkfdklglkf</li><br />',
	'william do the test and right line.',
	'<a>without full tags</a',
	'<a href="" title="">ttttt</a></li>'
);
foreach($ary as $t) {
	$m = strip_tags($t);
	echo "$m" . "<br>\n";
}

$t =
'中新网10月22日电海外华文媒体持续关注钓鱼岛问题。中国海军在东海举行大规模海上演习引起各方关注。与此同时，日本外相玄叶光一郎对法英德访问遭冷遇；海外舆论普遍认为，中国近来的频密举动具警示之意，清晰...';

echo mb_strlen($t) . "\n";
echo strlen($t) . "\n";

echo mb_substr($t, 0, 30) . "\n";
echo mb_highlight($t, '22', '<b>', '</b>');

$t1 = '

lang="zh">abced
lang="zh">efg

';

echo "\n";
echo preg_replace("/\s+/s", '', $t1);
echo "\n";
echo preg_replace("/^\s*lang=\"zh\">/", '', $t1);
echo "\n";

function mb_highlight($data, $query, $ins_before, $ins_after)
{
	$result = '';
	while (($poz = mb_strpos(mb_strtolower($data), mb_strtolower($query))) !== false)
	{
		$query_len = mb_strlen ($query);
		$result .= mb_substr ($data, 0, $poz).
		$ins_before.
		mb_substr ($data, $poz, $query_len).
		$ins_after;
		$data = mb_substr ($data, $poz+$query_len);
	}
	return $result;
}
