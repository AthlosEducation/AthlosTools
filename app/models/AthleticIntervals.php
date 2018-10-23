<?php

class AthleticIntervals extends Phalcon\Mvc\Model
{
	
	public $id;
	public $intervalName;
	
	public function getSource(){
		return "athletic_intervals";
	}
}