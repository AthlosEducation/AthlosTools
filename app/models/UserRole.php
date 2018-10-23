<?php

class UserRole extends Phalcon\Mvc\Model
{
	
	public $id;
	public $role_name;
	
	public function getSource(){
		return "user_role";
	}
}