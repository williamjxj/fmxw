<?php
/**
 * /etc/mongod.conf, 27017, /var/lib/mongo, /var/run/mongodb/mongod.pod, /var/log/mongo/mongod.log
 * 1. Mongo: a connection bwtween PHP and MOngoDb.
 * 2. MongoDB: Instances of this class are used to interact with a database. 数据库一层的操作。
 * 3. MongoCollection: Representations a database collection. 表一层的操作。 
 * 4. MongoCursor: A cursor is used to iterate through the results of a database query.
 */
date_default_timezone_set("Asia/Shanghai");
 

try {
	// connect mongoDB server: localhost:27017
	$connection = new Mongo();
	// select a database
	$db = $connection->words_lib;
}
catch ( MongoConnectionException $e ) 
{
    echo '<p>Couldn\'t connect to mongodb, is the "mongoD" process running?</p>';
    exit();
}

// select a collection
$collection = $db->search;

if($_GET['js_all']) {
	// find everything in the collection
	$cursor = $collection->find();
	
	foreach($cursor as $id => $value) {
		echo "$id: "; print_r($value);
	}
	echo "Total [" . $collection->count() . "]<br>\n";
}
elseif($_GET['js_one'])) {
	$find1 = $collection->findOne(array('key'=>'abc'));
	print_r($find1);
}
elseif($_GET['js_info'])) {
	$collection->getName();
}
elseif($_GET['js_cursor'])) {
	$cursor = $collection->find();
	print_r(iterator_to_array($cursor));
}
elseif($_GET['js_drop'])) {
	$collection->drop();
}
elseif($_GET['test'])) {
}
elseif() {
	update_keywords)();
}
else {

	// add a record
	$obj = array(
		'key' => 'default',
		'count' => 1,
		'synonymous' => array('负面新闻','坏','差','新闻'),
		'antonyms' => array('好','幸福','正确','满意')
	);
	$collection->insert($obj);
	
	$data = array(
		'key' => '微笑局长',
		'title' => '',
		'posted' => new MongoDate,
		'tags' => array(),
		'comments' => array(
		),
		'related' => array(
			'微笑局长杨达才',		
			'微笑局长被撤职',	
			'陕西微笑局长',	
			'微笑局长最新消息',	
			'陕西微笑局长杨达才',
			'微笑局长事件',
			'局长的微笑局长的表',
			'微笑局长消息',
			'微笑局长1600万',	
			'微笑局长之歌'
		),
	);
	$collection->insert($data);
	
	echo "<pre>"; print_r($cursor); echo "</pre>";
	
	$cursor = $collection->find('微笑局长');
}
	
exit;

////////////////////////////////////

function upload_keywords()
{
	$updates = file_get_contents( "http://api.twitter.com/". TWITTER_API_VERSION ."/statuses/public_timeline.json" );
	$updates = json_decode( $updates );
	
	if ( $updates && is_array( $updates ) && count( $updates ) )
	{
		foreach ( $updates as $update )
		{    
			$db->users->insert( $update );
		}
	}
}

?>