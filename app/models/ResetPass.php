<?php

class ResetPass extends Phalcon\Mvc\Model
{
	
	public $id;
	public $userid;
	public $is_student;
	public $complete;
	public $date;
	
	public function getSource(){
		return "reset_pass";
	}
}