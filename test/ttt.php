<?php
$query = $_GET['query'];

if (!empty($query))
{
 $sphinx->SetMatchMode(SPH_MATCH_ALL);
 $sphinx->AddQuery($query, 'artists');
 $sphinx->AddQuery($query, 'variations');

 $sphinx->SetFilter('name', array(3));

 $sphinx->SetLimits(0, 10);

 $result = $sphinx->RunQueries();

 echo '<pre>';

 switch ($result)
 {
  case false:
   echo 'Query failed: ' . $sphinx->GetLastError() . "\n";
   break;
  default:
   if ($sphinx->GetLastWarning())
   {
    echo 'WARNING: ' . $sphinx->GetLastWarning() . "\n";
   }

   if (is_array($result[0]['matches']) && count($result[0]['matches']))
   {
    foreach ($result[0]['matches'] as $value => $info)
    {
     $artist = artistDetails($value);
     echo $artist['name'] . "\n";
    }
   }
 }
}
echo "</pre>";

?>