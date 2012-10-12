<?php
header('Content-Type: text/html; charset=utf-8'); 

echo "hello world.<br>\n";

$so = scws_new();
$so->set_charset('utf8');
$so->set_dict(ini_get('scws.default.fpath') .  '/dict.utf8.xdb');
$so->set_rule(ini_get('scws.default.fpath') .  '/rules.utf8.ini');
if (file_exists('dict_user.txt')) $scws->add_dict('dict_user.txt', SCWS_XDICT_TXT);

// 这里没有调用 set_dict 和 set_rule 系统会自动试调用 ini 中指定路径下的词典和规则文件
// $so->send_text("我是一个中国人,我会C++语言,我也有很多T恤衣服");
$so->send_text("莫言获诺奖 中国籍作家实现零的突破(图)");
$so->send_text("我是一个中国人,我会C++语言,我也有很多T恤衣服");
echo "<pre>";
while ($tmp = $so->get_result())
{
  print_r($tmp);
}
echo "</pre>";
$so->close();

function get_result($string)
{
    $mydata = $string;
    $cws = scws_new();
    $cws->set_charset('utf8');
    $cws->set_ignore(TRUE);
    $cws->set_multi(1);
    $cws->send_text($mydata); 
    $tmp =$cws->get_tops(100, '~'); 
    $str = '';
    if(!empty($tmp) && is_array($tmp))
    {
        foreach($tmp as $val)
        {
            $str .= $val['word'].' ';
        }
    }
    $cws->close();
    return $str;
}

echo get_result('在平时的查询中对于查询一些标题的操作比较多，使用like结合%是大家最容易想到的办法。使用这种办法，在数据量到达一定程度后查询的速度会让人无法忍受的。使用scws对什么相关内容进行分词将分词的结构保存到相应的字段中，使用mysql建立fulltext索引，在进行查询的时候利用该索引，这样查询的效率会大大地提升。
scws的安装请参考–http://www.hightman.cn/?scws
注意：在安装的时候一定注意，你的项目使用的是什么字符集，安装对应的scws，否则在分词的过程中会出现无法分词的现象。死皮赖脸亮晶晶');

?>