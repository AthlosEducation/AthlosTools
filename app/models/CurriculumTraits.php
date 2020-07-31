<?php

class CurriculumTraits extends Phalcon\Mvc\Model
{
	
	public $id;
	public $trait_name;
	public $url_name;
	public $icon;
	public $description;
	
	public function getSource(){
		return "curriculum_traits";
	}
}