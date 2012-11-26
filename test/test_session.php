<?php
session_start();
/** sph_match_extended2
 *  负面 | 丑闻 | 真相
 *  新闻 | 评价 | 曝光
 *  -正面 !好
 * (负面|丑闻|真相)(新闻|评价|曝光) -(正面|榜样)
 */

function initial() {
	return array(
		'q' => '姜小军',
		'key' => '姜小军',
		'e' => '( 负面|丑闻|真相 ) | ( 新闻|评价|曝光 )',
		'page' => 1,
		'total' => 0,
		'total_pages' => 0,
		'total_found' => 0,
		'time' => 0,
		'category' => '',
		'cate_id' => 0,
		'item' => '',
		'iid' => 0,
		'cid' => 0,
		'dwmy' => '', //day24,week,month,year
		'core' => 1, //1-负面度,2-相关度,3-评论数
		'attr' => '', //clicks,guanzhu,pinglun,likes,fandui
		'sort' => '',
	);
}
function unset_search($search)
{
	foreach (array_keys($search) as $k) unset($search[$k]);
	return $search;
}

$_SESSION['fmxw']['search'] = initial();

$_SESSION['fmxw']['search']['userid'] = 1;
array_push($_SESSION['fmxw']['search'], array('user'=>'williamjiang'));

//array_push($_SESSION['fmxw']['search']['a'], 'aaa');
//array_push($_SESSION['fmxw']['search']['user'], array('userid'=>1,'user'=>'williamjiang'));

unset($_SESSION['fmxw']['search']['key']);


$t = $_SESSION['fmxw']['search'];
$t['abc'] = 'abc.com';

echo "<pre>"; print_r($_SESSION); echo "</pre>";
echo "<pre>"; print_r($t); echo "</pre>";

?>