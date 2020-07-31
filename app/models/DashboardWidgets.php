<?php

class DashboardWidgets extends Phalcon\Mvc\Model
{
	
	public $id;
	public $name;
	public $path;
	public $group;
	
	public function getSource(){
		return "dashboard_widgets";
	}
}