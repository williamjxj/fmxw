<?php
/**
 ��ǰ��searchd�Ķ˿ں� Ϊ 9313
 [error]
 [warning]
 [status]
 [fields] => (10��, �ο�conf�ļ�������
 ��attrs��=> attrs + fields = ���е���Ŀ
 ��matches�� => cid Ϊ������array(),����[weight] + [attrs], 
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
