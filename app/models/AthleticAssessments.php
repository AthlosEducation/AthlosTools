<?php

class AthleticAssessments extends Phalcon\Mvc\Model
{
	
	public $id;
	public $assessment_name;
	public $url_name;
	public $data;
	public $data_label;
	
	public function getSource(){
		return "athletic_assessments";
	}
}