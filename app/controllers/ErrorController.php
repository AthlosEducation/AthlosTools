<?php

class ErrorController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		//-- Redirect if not logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
	}
	
	public function show404Action()
	{
		
	}
	
	public function show500Action()
	{
		
	}
	
}
