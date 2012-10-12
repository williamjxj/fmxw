<?php
/**
 * I am implementing full-text search for a large collection of databases Using Sphinxsearch. 
 * My final goal is to enable simultaneous search on all these databases through the web, 
 * with a google-like interface.
 ** http://nearby.org.uk/sphinx/search-example5-withcomments.phps
 */
/**
 * This file copyright (C) 2010 Barry Hunter (sphinx@barryhunter.co.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

# Version 0.1 - First release (very basic and not fully functional)
# Version 0.2 - Made the Configuration section usuable
# Version 0.3 - (withdrawn - had broken implementation)
# Version 0.4 - Added support for highlighted excerpts/snippets body in the results
# Version 0.5 - support for paging! (and configuable page size)

# See a running demo of THIS code: http://www.nearby.org.uk/sphinx/example5.php?q=test
#  nothing changed, other than hooking it up with a sphinx index, and mysql database. 

######################
# Change this settings to match your setup...

$CONF = array();

$CONF['sphinx_host'] = 'localhost';
$CONF['sphinx_port'] = 9312; //this demo uses the SphinxAPI interface

$CONF['mysql_host'] = "localhost";
$CONF['mysql_username'] = "user";
$CONF['mysql_password'] = "password";
$CONF['mysql_database'] = "data";

$CONF['sphinx_index'] = "yourindex"; // can also be a list of indexes, "main, delta"

#can use 'excerpt' to highlight using the query, or 'asis' to show description as is.
$CONF['body'] = 'excerpt';

#the link for the title (only $id) placeholder supported
$CONF['link_format'] = '/page.php?page_id=$id';

#Change this to FALSE on a live site!
$CONF['debug'] = TRUE;

#How many results per page
$CONF['page_size'] = 25;

#maximum number of results - should match sphinxes max_matches. default 1000
$CONF['max_matches'] = 1000;


######################
#mysql query to fetch results, needs `id`, `title` and `body` columns in the final result.
#$ids is replaced by the list of ids
#this query can be as arbitary complex as required - but mysql has be able to run it quickly

#DO NOT include a order by (but if use GROUP BY, put ORDER BY NULL) - the order of the results doesnt matter

#TIP can also do :: CONCAT(description,' Category:',category) AS body :: for exmaple

$CONF['mysql_query'] = '
SELECT page_id AS id, title AS title, description AS body
FROM your_table
WHERE page_id IN ($ids)
';

#might need to put in path to your file
if (!empty($_GET['q'])) require("sphinxapi.php");

######################
# change the look and feel

?>
<style type="text/css">
form#search {
    background-color:silver:
    padding:10px;
}
ul.results {
    border:1px solid silver;
}
.results li {
    font-size:0.9em;
}
.results li a {
    font-weight:bold;
    font-size:1.2em;
}

.pages a {
    color:brown;
    text-decoration: none;
    padding:4px;
    margin:2px;
    border:1px solid silver;
    background-color:#eeeeee;
}
.pages b {
    padding:4px;
    margin:2px;
    background-color:#eeeeee;
}

</style>
<?php

##################################################################
##################################################################
#
# Nothing below should need changing - should work as is
#  but of course this is only a basic demo, can customise it to your needs
#



//Sanitise the input
$q = isset($_GET['q'])?$_GET['q']:'';

$q = preg_replace('/ OR /',' | ',$q);

$q = preg_replace('/[^\w~\|\(\)"\/=-]+/',' ',trim(strtolower($q)));

//Display the HTML search form
?>
    <form action="?" method="get" id="search">
        Search: <input name="q" type="text" value="<? echo htmlentities($q); ?>"/>
        <input type="submit" value="Search"/>
    </form>
<?php

//If the user entered something
if (!empty($q)) {
    //produce a version for display
    $qo = $q;
    if (strlen($qo) > 64) {
        $qo = '--complex query--';
    }
    
    if (1) {
        //Choose an appriate mode (depending on the query)
        $mode = SPH_MATCH_ALL;
        if (strpos($q,'~') === 0) {
            $q = preg_replace('/^\~/','',$q);
            if (substr_count($q,' ') > 1) //over 2 words
                $mode = SPH_MATCH_ANY;
        } elseif (preg_match('/[\|\(\)"\/=-]/',$q)) {
            $mode = SPH_MATCH_EXTENDED;
        }
        
        //setup paging...
        if (!empty($_GET['page'])) {
            $currentPage = intval($_GET['page']);
            if (empty($currentPage) || $currentPage < 1) {$currentPage = 1;}
            
            $currentOffset = ($currentPage -1)* $CONF['page_size'];
            
            if ($currentOffset > ($CONF['max_matches']-$CONF['page_size']) ) {
                die("Only the first {$CONF['max_matches']} results accessible");
            }
        } else {
            $currentPage = 1;
            $currentOffset = 0;
        }
        
        //Connect to sphinx, and run the query
        $cl = new SphinxClient();
        $cl->SetServer($CONF['sphinx_host'], $CONF['sphinx_port']);
        $cl->SetSortMode(SPH_SORT_EXTENDED, "@relevance DESC, @id DESC");
        $cl->SetMatchMode($mode);
        $cl->SetLimits($currentOffset,$CONF['page_size']); //current page and number of results
        
        $res = $cl->Query($q, $CONF['sphinx_index']);
        
        //Check for failure
        if (empty($res)) {
            print "Query failed: -- please try again later.\n";
            if ($CONF['debug'] && $cl->GetLastError())
                print "<br/>Error: ".$cl->GetLastError()."\n\n";
            return;
        } else {
            //We have results to display!
            if ($CONF['debug'] && $cl->GetLastWarning())
                print "<br/>WARNING: ".$cl->GetLastWarning()."\n\n";
            $query_info = "Query '".htmlentities($qo)."' retrieved ".count($res['matches'])." of $res[total_found] matches in $res[time] sec.\n";
            
            $resultCount = $res['total_found'];
            $numberOfPages = ceil($res['total']/$CONF['page_size']);
        }
        
        if (is_array($res["matches"])) {
            //Build a list of IDs for use in the mysql Query and looping though the results
            $ids = array_keys($res["matches"]);
        } else {
            print "<pre class=\"results\">No Results for '".htmlentities($qo)."'</pre>";
        }
    }
    
    //We have results to display
    if (!empty($ids)) {

        //Setup Database Connection
        $db = mysql_connect($CONF['mysql_host'],$CONF['mysql_username'],$CONF['mysql_password']) or die("ERROR: unable to connect to database");
        mysql_select_db($CONF['mysql_database'], $db) or die("ERROR: unable to select database");
        
        //Run the Mysql Query
        $sql = str_replace('$ids',implode(',',$ids),$CONF['mysql_query']);
        $result = mysql_query($sql) or die($CONF['debug']?("ERROR: mysql query failed: ".mysql_error()):"ERROR: Please try later");
        
        if (mysql_num_rows($result) > 0) {

            //Fetch Results from Mysql (Store in an accociative array, because they wont be in the right order)
            $rows = array();
            while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
                $rows[$row['id']] = $row;
            }

            //Call Sphinxes BuildExcerpts function
            if ($CONF['body'] == 'excerpt') {
                $docs = array();
                foreach ($ids as $c => $id) {
                    $docs[$c] = strip_tags($rows[$id]['body']);
                }
                $reply = $cl->BuildExcerpts($docs, $CONF['sphinx_index'], $q);
            }
            
            if ($numberOfPages > 1 && $currentPage > 1) {
                print "<p class='pages'>".pagesString($currentPage,$numberOfPages)."</p>";
            }
            
            //Actully display the Results
            print "<ol class=\"results\" start=\"".($currentOffset+1)."\">";
            foreach ($ids as $c => $id) {
                $row = $rows[$id];
                
                $link = htmlentities(str_replace('$id',$row['id'],$CONF['link_format']));
                print "<li><a href=\"$link\">".htmlentities($row['title'])."</a><br/>";
                
                if ($CONF['body'] == 'excerpt' && !empty($reply[$c]))
                    print ($reply[$c])."</li>";
                else
                    print htmlentities($row['body'])."</li>";
            }
            print "</ol>";
            
            if ($numberOfPages > 1) {
                print "<p class='pages'>Page $currentPage of $numberOfPages. ";
                printf("Result %d..%d of %d. ",($currentOffset)+1,min(($currentOffset)+$CONF['page_size'],$resultCount),$resultCount);
                print pagesString($currentPage,$numberOfPages)."</p>";
            }
            
            print "<pre class=\"results\">$query_info</pre>";

        } else {

            //Error Message
            print "<pre class=\"results\">Unable to get results for '".htmlentities($qo)."'</pre>";

        }
    }
}



#########################################
# Functions 
# Created by Barry Hunter for use in the geograph.org.uk project, reused here because convenient :)

function linktoself($params,$selflink= '') {
    $a = array();
    $b = explode('?',$_SERVER['REQUEST_URI']);
    if (isset($b[1])) 
        parse_str($b[1],$a);

    if (isset($params['value']) && isset($a[$params['name']])) {
        if ($params['value'] == 'null') {
            unset($a[$params['name']]);
        } else {
            $a[$params['name']] = $params['value'];
        }

    } else {
        foreach ($params as $key => $value)
            $a[$key] = $value;
    }

    if (!empty($params['delete'])) {
        if (is_array($params['delete'])) {
            foreach ($params['delete'] as $del) {
                unset($a[$del]);
            }
        } else {
            unset($a[$params['delete']]);
        }
        unset($a['delete']);
    } 
    if (empty($selflink)) {
        $selflink = $_SERVER['SCRIPT_NAME'];
    } 
    if ($selflink == '/index.php') {
        $selflink = '/';
    }

    return htmlentities($selflink.(count($a)?("?".http_build_query($a,'','&')):''));
}


function pagesString($currentPage,$numberOfPages,$postfix = '',$extrahtml ='') {
    static $r;
    if (!empty($r))
        return($r);

    if ($currentPage > 1) 
        $r .= "<a href=\"".linktoself(array('page'=>$currentPage-1))."$postfix\"$extrahtml>&lt; &lt; prev</a> ";
    $start = max(1,$currentPage-5);
    $endr = min($numberOfPages+1,$currentPage+8);

    if ($start > 1)
        $r .= "<a href=\"".linktoself(array('page'=>1))."$postfix\"$extrahtml>1</a> ... ";

    for($index = $start;$index<$endr;$index++) {
        if ($index == $currentPage) 
            $r .= "<b>$index</b> "; 
        else
            $r .= "<a href=\"".linktoself(array('page'=>$index))."$postfix\"$extrahtml>$index</a> ";
    }
    if ($endr < $numberOfPages+1) 
        $r .= "... ";

    if ($numberOfPages > $currentPage) 
        $r .= "<a href=\"".linktoself(array('page'=>$currentPage+1))."$postfix\"$extrahtml>next &gt;&gt;</a> ";

    return $r;
}