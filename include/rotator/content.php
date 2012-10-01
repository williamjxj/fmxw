<?php
function rotator_content() {
	// mock DB tables, return JSON format.
	return array(
		array(
			'id'=>1,
			'title'=> '1. 日本新“在留卡” 台湾人不再填中国，神州震怒',
			'link' => 'http://www.ibenguo.cn/news/1167/42/1167.htm', 
		),
		array(
			'id'=>2,
			'title'=> '2. 洪博培关注薄熙来和18大：中国政改是必然(图)',
			'link' => 'http://www.wenxuecity.com/news/2012/07/17/1873377.html', 
		),
		array(
			'id'=>3,
			'title'=> '3. 人保部提出退休年龄推迟至65岁，激起网民热议',
			'link' => 'http://www.ibenguo.cn/news/1101/42/1101.htm', 
		),
		array(
			'id'=>4,
			'title'=> '4. 更多公职人员宽容“裸官”背后 值得警醒',
			'link' => 'http://www.ibenguo.cn/news/165/42/165.htm', 
		),
		array(
			'id'=>5,
			'title'=> '5.【温州公务接待“减肥”仅限工作餐】不含接待上级（图）',
			'link' => 'http://www.ibenguo.cn/news/1191/73/1191.htm', 
		),
		array(
			'id'=>6,
			'title'=> '6.【南京长江大桥】暴雨后被“冲出”117个坑洞（多图）',
			'link' => 'http://www.ibenguo.cn/news/1196/71/1196.htm', 
		),
		array(
			'id'=>7,
			'title'=> '7. 福建富二代醉驾撞6车致2死】扬言“家里有钱赔”',
			'link' => 'http://www.ibenguo.cn/news/1194/71/1194.htm', 
		)
	);
}
/*
echo "<pre>";
print_r(rotator_content());
echo "</pre>";
*/
echo json_encode(rotator_content());
?>