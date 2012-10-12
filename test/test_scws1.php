<?php
$sh=scws_open();
scws_set_charset($sh,'utf8');
$str='现在的互联网上，很多网站都提供了全文搜索功能，浏览者可以通过输入关键字或者是短语来搜索特定的资料。在PHP+MySQL构架的网站中';
scws_send_text($sh,$str);
scws_set_ignore($sh,true);
$rs=scws_get_result($sh);
foreach($rs as $r){
 echo $r['word'].'--'; 
}

echo "<br><br>2:<br><br>\n";
$so = scws_new();
$so->set_charset('utf8');
$so->set_dict(ini_get('scws.default.fpath') .  '/dict.utf8.xdb');
$so->set_rule(ini_get('scws.default.fpath') .  '/rules.utf8.ini');
// 这里没有调用 set_dict 和 set_rule 系统会自动试调用 ini 中指定路径下的词典和规则文件
$so->send_text("我是一个中国人,我会C++语言,我也有很多T恤衣服");
echo "<pre>";
while ($tmp = $so->get_result())
{
  print_r($tmp);
}
echo "</pre>";
$so->close();
?>
