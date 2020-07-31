<?php

date_default_timezone_set('UTC');

class InstructionController extends \Phalcon\Mvc\Controller
{
	public $cap;
	
	public function initialize()
	{
		//-- Redirect if not logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		//-- Grab Capabilities --//
		$this->cap = $this->session->get("capabilities");
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Character");
		$this->view->setVar("navPage", "Instruction");
	}
	
    public function indexAction()
    {
		//-- Deny Access if no Priveleges --//
		if(!$this->cap['character-curriculum']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
        //-- Grab Traits Object --//
		$traits = CurriculumTraits::find('');
		//-- Grab Content Types Object --//
		$types = CurriculumType::find('');
		
		//-- Pass Objects to View --//
        $this->view->setVar("traits", $traits);
		$this->view->setVar("types", $types);
    }

	public function triggerAction()
	{
		//-- Deny Access if no Priveleges --//
		if(!$this->cap['character-curriculum']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
		//-- Instruction Page for Trigger Day --//
		$this->view->setVar("navGroup", "Trigger");
	}

}
