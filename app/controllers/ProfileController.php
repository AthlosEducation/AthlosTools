<?php

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class ProfileController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		//-- Redirect if not logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Profile");
	}
	
    public function indexAction()
    {
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		//-- Deny Access to Students --//
		if($this->session->get("user-role") > 9){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
        //-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- Sanitize Vars --//
			$fname = $this->request->getPost("first", "string");
			$lname = $this->request->getPost("last", "string");
			$email = $this->request->getPost("email", "email");
			$phone = $this->request->getPost("phone", "int");
			$username = $this->request->getPost("username", "string");
			$pass = $this->request->getPost("pass", "string");
			$send_email = $this->request->getPost("send_email", "int");
			
			//-- eliminate special chars & spaces in username --//
			if($username){
				$sChars = array(' ','<','>','&','{','}','*','!','@','#','$','%','^','(',')','[',']','"',"'",';',':','?','/','`','+','=','~');
				$username = str_replace($sChars, array(''), $username);
			}
			
			//-- truncate vars to certain length --//
			if($username && strlen($username) > 32){ $username = substr($username, 0, 32); }
			if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
			if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
			if($phone && strlen($phone) > 10){ $phone = substr($phone, 0, 10); }
			
			//-- Make sure the required info is present --//
			if($fname && $lname && $email){
				//-- check length of username --//
				if(!$username || strlen($username) > 4){
					//-- Check to see if username exists --//
					$check = Users::count(array("usernm = :login: AND id != ".$this->session->get("user-id"), "bind" => array("login" => $username)));
					if(!$username || !$check){
						//-- Check to see if email is taken --//
						$checkEmail = Users::count(array("email = :email: AND id != ".$this->session->get("user-id"), "bind" => array("email" => $email)));
						$checkEmail2 = Students::count(array("email = :email:", "bind" => array("email" => $email)));
						if(!$checkEmail && !$checkEmail2){
							/*------------------------------------------------------
								Grab User Object && See if username / pwd changed
							-------------------------------------------------------*/
							$aUser = Users::findFirst(array("id = ".$this->session->get("user-id")));
							//-- grab old username --//	
							$old_username = $aUser->usernm;

							/*---------------------------------------------
								Now Update User -- Passed all validation
							----------------------------------------------*/
							if($username){
								$aUser->usernm = $username;
							}else{
								$aUser->usernm = null;
							}
							$aUser->fname = $fname;
							$aUser->lname = $lname;
							$aUser->email = $email;
							if($pass){ $aUser->passwd = $this->security->hash($pass); }
							if($phone){
								$aUser->phone = $phone;
							}else{
								$aUser->phone = null;
							}

							//-- Save Entry --//
							if($aUser->save() == false){
								$this->flashSession->error($preMsg."<strong>Failed to Update Profile!</strong> Something went wrong, and your profile was not updated.");
							}else{
								$this->flashSession->success($preMsg."<strong>Profile Updated!</strong> Your profile was updated successfully.");

								//-- Use Updated info for profile --//
								$profile = $aUser;

								//-- send message if pwd or username was changed or updated --//
								if($send_email && ($pass || $old_username != $username)){
									//-- Setup Mailgun Object --//
									$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

									//-- if username is blank give email for the login info --//
									if($username){ $logcreds = "Username: ".$username; }else{ $logcreds = "Email: ".$email; }
									if($pass){ $logcreds.= "\nPassword: ".$pass; }

									//-- Send Updated pwd & username Mail Message --//
									$to = $email;
									$subject = "Login Information Was Updated";
									$message = "A recent change was made to your account and your login information was updated. You can login to Athlos Tools by going to this url: https://".$_SERVER['HTTP_HOST']."\n\n".$logcreds."\n\nFeel free to contact your school if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
									//-- Send MSG with Mailgun --//
									$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
									                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
									                        'to'      => $to,
									                        'subject' => $subject,
									                        'text'    => $message));
								}
							}
						}else{
							$this->flashSession->error($preMsg."<strong>Email Already In Use!</strong> The Email Address is already being used. Please use another.");
						}
					}else{
						$this->flashSession->error($preMsg."<strong>Username Already In Use!</strong> Username is already being used. Please choose another.");
					}
				}else{
					$this->flashSession->error($preMsg."<strong>Username Too Short!</strong> Username needs to be a minimum of 5 characters.");
				}
			}else{
				$this->flashSession->error($preMsg."<strong>Something Is Missing!</strong> Make sure all the fields are filled out correctly.");
			}
			
			//-- use postdata in profile page --//
			if(!isset($profile)){
				$profile = new stdClass();
				$profile->fname = $fname;
				$profile->lname = $lname;
				$profile->email = $email;
				$profile->phone = $phone;
				$profile->usernm = $username;
			}
			
		}//-- end if data posted --//
		
		//-- Grab User Object & use in profile --//
		if(!isset($profile)){
			$profile = Users::findFirst(array("id = ".$this->session->get("user-id")));
		}
		
		//-- Pass Objects / Vars to View --//
        $this->view->setVar("profile", $profile);
    }//-- end indexAction() --//

	public function studentAction()
    {
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		//-- Deny Access to Users / Staff --//
		if($this->session->get("user-role") < 9){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> This page is only for students.");
			return $this->response->redirect("");
		}
		
        //-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- Sanitize Vars --//
			$fname = $this->request->getPost("first", "string");
			$lname = $this->request->getPost("last", "string");
			$email = $this->request->getPost("email", "email");
			$pass = $this->request->getPost("pass", "string");
			$send_email = $this->request->getPost("send_email", "int");
			
			//-- truncate vars to certain length --//
			if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
			if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
			
			//-- Make sure the required info is present --//
			if($fname && $lname && $email){
				
				//-- Check to see if email is taken --//
				$checkEmail = Students::count(array("email = :email: AND id != ".$this->session->get("student-id"), "bind" => array("email" => $email)));
				$checkEmail2 = Users::count(array("email = :email:", "bind" => array("email" => $email)));
				if(!$checkEmail && !$checkEmail2){

					/*---------------------------------------------
						Now Update User -- Passed all validation
					----------------------------------------------*/
					$aStudent = Students::findFirst(array("id = ".$this->session->get("student-id")));
					$aStudent->fname = $fname;
					$aStudent->lname = $lname;
					$aStudent->email = $email;
					if($pass){ $aStudent->pass = $this->security->hash($pass); }

					//-- Save Entry --//
					if($aStudent->save() == false){
						$this->flashSession->error($preMsg."<strong>Failed to Update Profile!</strong> Something went wrong, and your profile was not updated.");
					}else{
						$this->flashSession->success($preMsg."<strong>Profile Updated!</strong> Your profile was updated successfully.");

						//-- Use Updated info for profile --//
						$profile = $aStudent;

						//-- send message if pwd or username was changed or updated --//
						if($send_email && $pass){
							//-- Setup Mailgun Object --//
							$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

							$logcreds = "Email: ".$email;
							if($pass){ $logcreds.= "\nPassword: ".$pass; }

							//-- Send Updated pwd & username Mail Message --//
							$to = $email;
							$subject = "Login Information Was Updated";
							$message = "A recent change was made to your account and your login information was updated. You can login to Athlos Tools  by going to this url: https://".$_SERVER['HTTP_HOST']."\n\n".$logcreds."\n\nFeel free to contact your school if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
							//-- Send MSG with Mailgun --//
							$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
							                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
							                        'to'      => $to,
							                        'subject' => $subject,
							                        'text'    => $message));
						}
					}
				}else{
					$this->flashSession->error($preMsg."<strong>Email Already In Use!</strong> The Email Address is already being used. Please use another.");
				}
				
			}else{
				$this->flashSession->error($preMsg."<strong>Something Is Missing!</strong> Make sure all the fields are filled out correctly.");
			}
			
			//-- use postdata in profile page --//
			if(!isset($profile)){
				$profile = new stdClass();
				$profile->fname = $fname;
				$profile->lname = $lname;
				$profile->email = $email;
			}
			
		}//-- end if data posted --//
		
		//-- Grab User Object & use in profile --//
		if(!isset($profile)){
			$profile = Students::findFirst(array("id = ".$this->session->get("student-id")));
		}
		
		//-- Pass Objects / Vars to View --//
        $this->view->setVar("profile", $profile);
    }//-- end studentAction() --//

}
