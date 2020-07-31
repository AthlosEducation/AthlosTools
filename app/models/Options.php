<?php

class Options extends Phalcon\Mvc\Model
{
	
	public $id;
	public $option;
	public $value;
	
	public function getSource(){
		return "site_options";
	}
	
}