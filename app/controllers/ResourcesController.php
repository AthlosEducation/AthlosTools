<?php

class ResourcesController extends \Phalcon\Mvc\Controller
{
	public $cap;
	
	public function initialize()
	{
		//-- Redirect if not already logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		//-- Grab Capabilities --//
		$this->cap = $this->session->get("capabilities");
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Tools | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Students");
	}
	
	public function indexAction()
    {	
		//-- Redirect if not already logged in --//
		/*if(!$this->session->get("resource-access") && !$this->session->get("logged_in")){
			return $this->response->redirect("resources/login");
		}*/
		
		/*--------------------------------------
			Instantiate Amazon Bucket S3 Code
		---------------------------------------*/
		//include the S3 class              
		if (!class_exists('S3'))require_once('amazon-s3/S3.php');
		//AWS access info
		if (!defined('awsAccessKey')) define('awsAccessKey', 'AKIAJIEMIW6FVXKO2QOQ');
		if (!defined('awsSecretKey')) define('awsSecretKey', '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');
		//instantiate the class
		$s3 = new S3(awsAccessKey, awsSecretKey);
		/*-- end instantiate amazon bucket code -*/
		
		// Get the contents of our bucket
		$bucket_contents = $s3->getBucket("athlos-tools-resources");
		$this->view->setVar("bucket_contents", $bucket_contents);
		
		//-- Set Nav Page --//
		$this->view->setVar("navPage", "Resources");
    }
	
    /*public function loginAction()
	{
		//-----------------
		//	Splash Page
		//-----------------//
		//-- Grab splash page setting --//
		$option = Options::findFirst("option = 'display-splash-page'");
		if($option->value == 1){
			if((isset($_GET['tools']) && $_GET['tools'] == 'resetAthlos') || (isset($_COOKIE['hide-splash']) && $_COOKIE['hide-splash'])){
				//-- Set Cookie -- don't show splash page --//
				setcookie("hide-splash", 1, time()+86400, '/');
			}else{
				//-- Redirect to splash page --//
				return $this->response->redirect("session/splash");
			}
		}
		
		//-- Redirect if already has access --//
		if($this->session->get("resource-access") || $this->session->get("logged_in")){
			return $this->response->redirect("resources");
		}
		
		//-- Setup Vars --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		if($this->request->isPost()) {
			//-- grab vars --//
			$password = $this->request->getPost('password');
			
			if($password){
				$option = Options::findFirst("option = 'resources-passcode'");
				
				if($this->security->checkHash($password, $option->value)){
					//-- The password is valid - User is logged in --//
					$this->session->set("resource-access", true);
					
					//-- Redirect to the home page --//
					$this->flashSession->success($preMsg."<strong>You Made It!</strong> You successfully logged in. Congratulations!");
					return $this->response->redirect("resources");
				}else{
					$this->flashSession->error($preMsg."<strong>Password Is Incorrect!</strong> Please try again.");
					return $this->response->redirect("resources/login");
				}
			}else{
				$this->flashSession->error($preMsg."<strong>Password Is Incorrect!</strong> Please try again.");
				return $this->response->redirect("resources/login");
			}
		}
		
	} // end loginAction() */
	
}
