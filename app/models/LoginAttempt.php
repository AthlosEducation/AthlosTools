<?php

class LoginAttempt extends \Phalcon\Mvc\Model
{

	public $id;
	public $userid;
	public $username;
	public $ip;
	public $superpass;
	public $time;
	
	public function initialize()
	{
		$this->belongsTo("userid", "Users", "id");
	}

	public function getSource(){
		return "login_attempts";
	}
}