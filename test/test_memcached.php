<?php
/**
 * Memcached provides a custom session handler that can be used to store user sessions in memcache. 
 * A completely separate memcached instance is used for that  internally, so you can use a different server pool if necessary. 
 * The session keys are stored under the prefix memc.sess.key., so be aware of this if you use the same server pool for sessions and generic caching.
 *	memcached.sess_prefix	memc.sess.key.	memc.sess.key.
 *
 * You probably don't want to use Memcache, Memcached for storing sessions (in RAM via memcached daemon server), because those sessions 
 * can easily be thrown away/discarded, become unreachable for various reasons, and then your user gets logged out without warning.
 *  What memcached is great for is storing the results of an SQL or MongoDB query, using the md5() hash of the query itself as the key lookup.
 * 用户注册信息的快速响应。
 */
$m = new Memcached();
$m->addServer('localhost', 11211);

$items = array(
	'key1' => 'value1',
	'key2' => 'value2',
	'key3' => 'value3'
);
$m->setMulti($items);
$m->getDelayed(array('key1', 'key3'), true, 'result_cb');

function result_cb($memc, $item)
{
        var_dump($item);
}

////////////////////////////////

class Cache 
{
	private $id;
	private $obj;

	function __construct($id){
		$this->id = $id;
		$this->obj = new Memcached($id);
	}

	public function connect($host='localhost', $port=11211){
		$servers = $this->obj->getServerList();
		if(is_array($servers)) {
			foreach ($servers as $server)
				if($server['host'] == $host and $server['port'] == $port)
					return true;
		}
		return $this->obj->addServer($host , $port);
	}

} 
?>
