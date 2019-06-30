<?php

class Password{
	public $text;
	public $encrypted;
	private $iv = "Wjl4VtOo+hN61lJk"; // Don't change this
	private $key;
	
	function __construct($pass, $encrypted){
		$this->text = $pass;
		$this->encrypted = $encrypted;
	}
	
	public function createKey($name){
		$this->key = sha1(strtolower($name) . $this->iv);
	}
	
	public function encrypt(){
		if($this->encrypted)
			return;
		
		return openssl_encrypt($this->text, "aes-256-cbc", $this->key, 0, $this->iv);
	}
	
	public function decrypt(){
		if(!$this->encrypted)
			return;
		
		return openssl_decrypt($this->text, "aes-256-cbc", $this->key, 0, $this->iv);
	}
}