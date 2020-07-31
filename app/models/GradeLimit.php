<?php

class GradeLimit extends Phalcon\Mvc\Model
{
	
	public $id;
	public $user;
	public $grades;
	
	public function initialize()
	{
		$this->belongsTo("user", "Users", "id");
	}
	
	public function getSource(){
		return "grade_limit";
	}
}