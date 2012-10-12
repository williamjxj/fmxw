<?
// This is a stripped down example of using Sphinx from http://www.shroomery.org/forums/search.php
// See http://www.shroomery.org/forums/dosearch.php.txt for the unabridged copy.
// This script requires Sphinx 0.9.8-rc1+

// Source definitions from sphinx.conf:
/*
source full
{
  type      = mysql
  sql_host  = localhost
  sql_user  = username
  sql_pass  = password
  sql_db    = forumsdb
  sql_port  = 3306
  sql_query_pre   = SET SESSION query_cache_type=OFF
  sql_query_pre   = SET NAMES 'utf8'
  sql_query_pre   = REPLACE INTO sph_counter SELECT 1, MAX(B_Number) FROM w3t_Posts
  sql_query_range = SELECT MIN(B_Number), MAX(B_Number) FROM w3t_Posts
  sql_range_step  = 1000
  sql_query       = SELECT B_Number, B_PosterId, B_Board, B_Topic, B_Main, B_Posted, LOWER(TRIM(B_Subject)) AS B_SubjectOrd, LOWER(U_Username) AS U_UsernameOrd, LOWER(Bo_Title) AS Bo_TitleOrd, B_Subject, B_Body FROM w3t_Posts LEFT JOIN w3t_Users ON B_PosterId = U_Number LEFT JOIN w3t_Boards ON Bo_Number = B_Board WHERE B_Number >= $start AND B_Number <= $end
  sql_attr_uint         = B_PosterId
  sql_attr_uint         = B_Board
  sql_attr_uint         = B_Topic
  sql_attr_uint         = B_Main
  sql_attr_timestamp    = B_Posted
  sql_attr_str2ordinal  = B_SubjectOrd
  sql_attr_str2ordinal  = U_UsernameOrd
  sql_attr_str2ordinal  = Bo_TitleOrd
}

source fulldelta : full
{
  sql_query_pre   = SET SESSION query_cache_type=OFF
  sql_query_pre   = SET NAMES 'utf8'
  sql_query_range =
  sql_range_step  =
  sql_query       = SELECT B_Number, B_PosterId, B_Board, B_Topic, B_Main, B_Posted, LOWER(TRIM(B_Subject)) AS B_SubjectOrd, LOWER(U_Username) AS U_UsernameOrd, LOWER(Bo_Title) AS Bo_TitleOrd, B_Subject, B_Body FROM w3t_Posts LEFT JOIN w3t_Users ON B_PosterId = U_Number LEFT JOIN w3t_Boards ON Bo_Number = B_Board WHERE B_Number > (SELECT max_doc_id FROM sph_counter WHERE counter_id=1)
}
*/

require("sphinxapi.php");

// Assign input
$where      = $_POST['where'];      // 'subject' or 'body'
$tosearch   = $_POST['tosearch'];   // 'both' or 'main'
$how        = $_POST['how'];        // 'all', 'any', 'exact' or 'boolean'
$words      = $_POST['words'];      // search terms
$namebox    = $_POST['namebox'];    // search name
$newerval   = $_POST['newerval'];   // newer text
$newertype  = $_POST['newertype'];  // d(ay), w(eek), m(onth) or y(ear)
$olderval   = $_POST['olderval'];   // older text
$oldertype  = $_POST['oldertype'];  // d(ay), w(eek), m(onth) or y(ear)
$limit      = $_POST['limit'];      // # of results
$sort       = $_POST['sort'];       // (r)elevance, (d)ate, (f)orum, (s)ubject or (u)sername
$way        = $_POST['way'];        // (a)sc or (d)esc
$page       = $_POST['page'];       // page of results
$showmain   = $_POST['showmain'];   // show only one result per thread
$forum      = $_POST['forum'];      // which forum to search

// Some variables which are used throughout the script
$now = time();
$mult = array('d'=>'86400', 'w'=>'604800', 'm'=>'2678400', 'y'=>'31536000');

// Create Sphinx client
$cl = new SphinxClient();
$cl->SetServer("192.168.0.20", 3312);
$index = 'full fulldelta';

// Filter by date
if ($olderval){
  $max = $now - $olderval * $mult[$oldertype];
  $cl->SetFilterRange("B_Posted", 0, $max);
}
if ($newerval){
  $min = $now - $newerval * $mult[$newertype];
  $cl->SetFilterRange("B_Posted", $min, $now);
}

// Filter by user
if ($namebox){
  $namebox = mysql_real_escape_string($namebox);
  $query = "SELECT U_Number FROM w3t_Users WHERE U_Username = '$namebox' OR U_LoginName = '$namebox'";
  $result = mysql_query($query);
  if (list($unum) = mysql_fetch_array($result)){
    $cl->SetFilter("B_PosterId", array(intval($unum)));
  }  
}

// Filter by forum
!$forum or $cl->SetFilter("B_Board", $forum);

// Handle search mode
$cl->SetMatchMode(SPH_MATCH_EXTENDED2);
$sphinxq = $how == 'boolean' ? $words : preg_replace('/[^\w ]*/', ' ', $words);
$how != 'any' or $sphinxq = str_replace(' ', '|', $sphinxq);
$how != 'exact' or $sphinxq = "\"$sphinxq\"";

// Search by subject only, or both body and subject?
$weights = $where == 'subject' ? array('B_Subject' => 1) : array('B_Subject' => 11, 'B_Body' => 10);
$sphinxq = "@(".join(',', array_keys($weights)).") $sphinxq";
$cl->SetFieldWeights($weights);

// Search only main topics?
if ($tosearch == 'main'){
  $cl->SetFilter("B_Topic", array(2));
}

// Handle sort method
$attrmodes = array('d' => 'DESC', 'a' => 'ASC');
$sortfields  = array('r' => '@weight', 'd' => 'B_Posted', 's' => 'B_SubjectOrd', 'u' => 'U_UsernameOrd', 'f' => 'Bo_TitleOrd');
$sphway = "{$sortfields[$sort]} {$attrmodes[$way]}, @id {$attrmodes[$way]}";
$cl->SetSortMode(SPH_SORT_EXTENDED, $sphway);
$words or $cl->SetRankingMode(SPH_RANK_NONE);

// Show only one result per thread?
if ($showmain){
  $cl->SetGroupBy('B_Main', SPH_GROUPBY_ATTR, $sphway);
}

// Set offset
$limit && $limit <= 100 or $limit = 25;
$offset = $page * $limit;
$cl->SetLimits(intval($offset), intval($limit));

// Do the search
$res = $cl->Query($sphinxq, $index);
$res['matches'] or exit("No results were returned.");

// Do a query to get additional document info (you could use SphinxSE instead)
foreach ($res['matches'] AS $key => $row){
  $doc_arr[] = $key;
}
$doc_csv = join(',', $doc_arr);
$query = "SELECT B_Number, B_Subject, B_Icon, B_Board, B_PosterId, B_Posted, U_Username, Bo_Title 
          FROM w3t_Posts AS t1, w3t_Users AS t2, w3t_Boards AS t3 
          WHERE t1.B_Number IN ($doc_csv) AND t1.B_PosterId = t2.U_Number AND t1.B_Board = t3.Bo_Number 
          ORDER BY FIELD(B_Number, $doc_csv)";
$result = mysql_query($query);

// Max possible weight can be used to calculate absolute relevance for results.
$max_weight = (array_sum($weights) * count($res['words']) + 1) * 1000;

while ($row = mysql_fetch_array($result)){
  $row['Percent'] = ceil($res['matches'][$row['B_Number']]['weight'] / $max_weight * 100); // Calculate relevance percentage
  $matches[] = $row;
}

// Results are in the $matches array
echo nl2br(print_r($matches, true));
?>