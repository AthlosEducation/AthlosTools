<?php

class TutorialsController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		//-- Redirect if not logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		//-- Deny Access if no Priveleges --//
		/*if($this->session->get("user-role") > 1){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}*/
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Help");
	}
	
    public function indexAction()
    {
        
    } //-- end indexAction() --//
	
}
