<?php 
class foo { 
    public $M = false; 
	    
	public function __construct() { 
		$this->M = new Memcached(); 
		$this->M->addServer('localhost', 11211);        
		$this->M->set('a', 'test'); 
	} 

	public function fun() { 
		echo "Great Success!"; 
	} 
} 

$f = new foo(); 
$f->M->getDelayed(array('a'), false, array($f, 'fun')); 
?> 
