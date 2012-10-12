<?php
/**
 当前，searchd的端口号 为 9313
 [error]
 [warning]
 [status]
 [fields] => (10项, 参考conf文件的设置
 【attrs】=> attrs + fields = 所有的项目
 【matches】 => cid 为主键的array(),包括[weight] + [attrs], 
 [total] => 11
 [total_found] => 11
 [time] => 0.006
 [words] => (docs, hits) ?
 */

header('Content-Type: text/html; charset=utf-8');

require_once('../etc/sphinxapi.php');
require_once('../configs/toosl.php');

$s = new SphinxClient;

$s->setServer("localhost", 9313);

$s->setMatchMode(SPH_MATCH_ANY);

$s->setMaxQueryTime(3);

$result = $s->query("test");

__p($result);

?>
