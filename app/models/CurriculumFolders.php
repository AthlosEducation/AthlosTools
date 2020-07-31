<?php

class CurriculumFolders extends Phalcon\Mvc\Model
{
	
	public $id;
	public $name;
	public $url;
	public $parent_id;
	public $icon;
	public $permissions;
	
	public function getSource(){
		return "curriculum_folders";
	}
}