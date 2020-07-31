<?php

class Grading extends Phalcon\Mvc\Model
{
	
	public $id;
	public $semester;
	public $school;
	public $grade_level;
	public $evaluator;
	public $eval_role;
	public $student;
	public $grit;
	public $focus;
	public $optimism;
	public $curiosity;
	public $leadership;
	public $energy;
	public $courage;
	public $initiative;
	public $social;
	public $humility;
	public $integrity;
	public $creativity;
	public $observations;
	
	public function initialize()
	{
		$this->belongsTo("student", "Students", "id");
		$this->belongsTo("school", "Schools", "id");
	}
	
}