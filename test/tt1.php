<?php
// 207.6.38.43 
header('Content-Type: text/html; charset=utf-8');

echo "江南style<br>\n";

$ip = '207.6.38.43';
$record = geoip_record_by_name($ip);
echo $record['city'];


//$ip = $_SERVER['REMOTE_ADDR'];
// remember chmod 0777 for folder 'cache'
$file = "./cache_".$ip;
if(!file_exists($file)) {
    // request
    $json = file_get_contents("http://api.easyjquery.com/ips/?ip=".$ip."&full=true");
    $f = fopen($file,"w+");
    fwrite($f,$json);
    fclose($f);
} else {
    $json = file_get_contents($file);
}

$json = json_decode($json,true);
echo "<pre>";
print_r($json);
echo "</pre>";
?>