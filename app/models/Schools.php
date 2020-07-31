<?php

class Schools extends \Phalcon\Mvc\Model
{

	public $id;
	public $schoolName;
	public $abbreviation;
	public $state;
	public $city;
	public $district;
	public $ilt_school;
	public $notifications;
	public $date_added;
	
	public function initialize()
	{
		$this->belongsTo("district", "Districts", "id");
		$this->hasMany("id", "Students", "school");
		$this->hasMany("id", "Grading", "school");
	}
	
}