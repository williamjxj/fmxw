<?php
//http://www.dewen.org/q/3418/coreseek+%E9%98%80%E5%80%BC%E5%8C%B9%E9%85%8D

//清除上一次查询设置到过滤器   
$this->sphinx->ResetFilters();
$this->sphinx->SetMatchMode(SPH_MATCH_ANY);

//根据相似度排序
//$this->sphinx->SetRankingMode(SPH_RANK_WORDCOUNT);
$this->sphinx->SetSortMode(SPH_SORT_RELEVANCE);
$this->sphinx->SetArrayResult(true);

//返回10个相似task
$this->sphinx->SetLimits(0,10);

//设置句子单词数
$this->sphinx->SetFilterRange('en_length', 10,50);

?>