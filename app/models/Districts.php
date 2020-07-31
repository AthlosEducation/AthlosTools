<?php

class Districts extends \Phalcon\Mvc\Model
{

	public $id;
	public $districtName;
	public $abbreviation;
	public $date_added;
	
	public function initialize()
	{
		$this->hasMany("id", "Schools", "district");
	}
	
}