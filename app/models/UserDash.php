<?php

class UserDash extends Phalcon\Mvc\Model
{
	
	public $id;
	public $user_id;
	public $widgets;
	
	public function getSource(){
		return "user_dashboard";
	}
}