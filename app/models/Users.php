<?php

class Users extends \Phalcon\Mvc\Model
{

	public $id;
	public $alt_id;
	public $state_id;
	public $usernm;
	public $passwd;
	public $role;
	public $school;
	public $grade;
	public $fname;
	public $lname;
	public $email;
	public $phone;
	public $date_add;
	
	public function initialize()
	{
		$this->hasMany("id", "Curriculum", "post_author");
		$this->hasMany("id", "ParentRelationship", "parent");
		$this->hasOne("id", "GradeLimit", "user");
	}
	
	public function getPass(){
		return $this->passwd;
	}
}