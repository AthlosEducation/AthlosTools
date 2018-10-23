<?php

class GradeLevel extends Phalcon\Mvc\Model
{
	
	public $id;
	public $gradeName;
	
	public function getSource(){
		return "grade_level";
	}
}