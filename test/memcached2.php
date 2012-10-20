<?php 
class foo { 
    private $M = false; 
	    
	public function __construct() { 
		$this->M = new Memcached(); 
		$this->M->addServer('localhost', 11211);        
		$this->M->set('a', 'test'); 
	} 

	public function test() { 
		$this->M->getDelayed(array('a'), false, array($this, 'fun')); 
	} 

	public function fun() { 
		echo "Great Success!"; 
	} 
} 

$f = new foo(); 
$f->test(); 
?> 

