<?php

class ParentRelationship extends Phalcon\Mvc\Model
{
	
	public $id;
	public $verified;
	public $parent;
	public $student;
	
	public function initialize()
	{
		$this->belongsTo("parent", "Users", "id");
		$this->belongsTo("student", "Students", "id");
	}
	
	public function getSource(){
		return "parent_relationship";
	}
	
}