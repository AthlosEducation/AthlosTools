<?php

class CurriculumType extends Phalcon\Mvc\Model
{
	
	public $id;
	public $type_name;
	
	public function getSource(){
		return "curriculum_type";
	}
}