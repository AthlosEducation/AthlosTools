<?php

class BreakTypes extends Phalcon\Mvc\Model
{
	
	public $id;
	public $type_name;
	public $url_name;
	public $icon;
	
	public function getSource(){
		return "break_types";
	}
}