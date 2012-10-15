<?php
header('Content-Type: text/html; charset=utf-8'); 

$qqwry=new qqwry('../etc/qqwry.dat');

list($addr1,$addr2)=$qqwry->q('127.0.0.1');
$addr1=iconv('GB2312','UTF-8',$addr1);
$addr2=iconv('GB2312','UTF-8',$addr2);
echo $addr1,'|',$addr2,"<br>\n";

$arr=$qqwry->q('222.216.47.4');
$arr[0]=iconv('GB2312','UTF-8',$arr[0]);
$arr[1]=iconv('GB2312','UTF-8',$arr[1]);
echo $arr[0],'|',$arr[1],"<br>\n";

$arr=$qqwry->q('64.233.187.99');
$arr[0]=iconv('GB2312','UTF-8',$arr[0]);
$arr[1]=iconv('GB2312','UTF-8',$arr[1]);
echo $arr[0],'|',$arr[1],"<br>\n";


$arr=$qqwry->q('207.6.38.43');
$arr[0]=iconv('GB2312','UTF-8',$arr[0]);
$arr[1]=iconv('GB2312','UTF-8',$arr[1]);
echo $arr[0],'|',$arr[1],"<br>\n";

$arr=$qqwry->q($_SERVER['REMOTE_ADDR']);
$arr[0]=iconv('GB2312','UTF-8',$arr[0]);
$arr[1]=iconv('GB2312','UTF-8',$arr[1]);
echo $arr[0],'|',$arr[1],"<br>\n";

?>
