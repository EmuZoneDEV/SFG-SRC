<?php

// Achievements by Greg

/*
	- List (Hungarian) -
	
	038 - A boldog 18-as
	001 - Világjáró
	017 - Sárkánylovas
	042 - Naturista
	012 - Lehúzók lehúzója
*/

class Achievements{
	public $data = [];
	
	function __construct($data){
		$this->data = $data;
		
		if($this->complete())
			$this->data = "1";
		else{
			$this->data = explode("/", $this->data);
			
			if(count($this->data) != 59)
				$this->generate();
		}
	}
	
	public function complete(){
		if($this->data == "1")
			return true;
		
		return false;
	}
	
	public function required($key){
		return -1;
	}
	
	public function generate(){
		for($i = 0; $i < 60; $i++)
			$this->data[$i] = 0;
	}
	
	public function save($key, $val){
	}
	
	public function getText(){
		$achi = [];
		
		for($i = 0; $i < 120; $i++)
			$achi[] = 0;
		$achi[] = "";

		
		return $achi;
	}
}