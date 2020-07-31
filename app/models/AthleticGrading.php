<?php

class AthleticGrading extends Phalcon\Mvc\Model
{
	
	public $id;
	public $semester;
	public $interval;
	public $school;
	public $grade_level;
	public $evaluator;
	public $student;
	public $sprint;
	public $hex;
	public $vjump;
	public $sjump;
	public $height;
	public $weight;
	public $bmi;
	public $pacer;
	public $shuttle;
	public $pushup;
	public $curlup;
	public $trunklift;
	public $sitreach;
	public $plank;
	public $absolute_reach;
	public $limb_length;
	public $balance;
	public $sl_left;
	public $sl_right;
	public $slraise;
	
	public function getSource(){
		return "athletic_grading";
	}
	
}