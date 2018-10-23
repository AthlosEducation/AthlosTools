<?php

class Students extends \Phalcon\Mvc\Model
{

	public $id;
	public $alt_id;
	public $state_id;
	public $fname;
	public $lname;
	public $email;
	public $school;
	public $grade;
	public $teacher;
	public $coach;
	public $pass;
	public $active;
	public $turf_period;
	public $date_added;
	
	public function initialize()
	{
		$this->hasMany("id", "ParentRelationship", "student");
		$this->hasMany("id", "Grading", "student");
		$this->belongsTo("school", "Schools", "id");
	}

}