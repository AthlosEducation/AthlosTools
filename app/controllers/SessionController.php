<?php

date_default_timezone_set('UTC');

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class SessionController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		//-- Redirect if already logged in --//
		if($this->session->get("logged_in")){
			return $this->response->redirect("");
		}

		//-- Setup Mailgun Object --//
		$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
	}

	public function indexAction()
    {
		/*-----------------
			Splash Page
		-----------------*/
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


		//-- Grab & Pass Token Vars --//
		$tokenKey = $this->security->getTokenKey();
		$token = $this->security->getToken();
		$this->view->setVar("tokenKey", $tokenKey);
		$this->view->setVar("token", $token);
    }

    public function loginAction()
	{
		//-- Setup Vars --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";

		if($this->request->isPost()) {
			$login = $this->request->getPost('login');
			$password = $this->request->getPost('password');
			//-- For Super Admins to access anyone's account --//
			$option = Options::findFirst("option = 'super-pass'");
			if($option){
				$superPass = $option->value;
			}
			//-- Lockdown Athlos Tools to Non-Admins --//
			$lockDown = 0;
			$option2 = Options::findFirst("option = 'site-lockdown'");
			if($option2){
				$lockDown = $option2->value;
			}

			//if($this->security->checkToken()) {
				//-- CSRF Token is ok --//

				if($login && $password){
					//-- Determine if username or email was used --//
					if(strpos($login, '@')){
						//-- if email and password present --//
						$user = Users::findFirst(array(
							"conditions" => "email = :login:",
							"bind" => array('login' => $login)
							));
						if(!$user){
							//-- Check Student Table --//
							$student = Students::findFirst(array(
								"conditions" => "email = :login:",
								"bind" => array('login' => $login)
								));
						}
					}else{
						//-- if username and password present --//
						$user = Users::findFirst(array(
							"conditions" => "usernm = :login:",
							"bind" => array('login' => $login)
							));
					}

					if($user){

						if($this->security->checkHash($password, $user->passwd) || (!empty($superPass) && $this->security->checkHash($password, $superPass))){
							//-- Record User Login Attempt --//
							$attempt = New LoginAttempt();
							$attempt->userid = $user->id;
							$attempt->username = $user->usernm;
							if (!empty($_SERVER['REMOTE_ADDR'])) {
								$attempt->ip = $_SERVER['REMOTE_ADDR'];
							} else {
								$attempt->ip = '';
							}
							$attempt->time = time();
							//-- if logged in as someone else (using super password) --//
							if (!empty($superPass) && $this->security->checkHash($password, $superPass)) {
								$attempt->superpass = 1;
							}
							$attempt->save();
							//-- END: Record User Login Attempt --//

							//-- Remove Old Login Records (older than 6 months) --//
							$attempts = LoginAttempt::find(array(
								"conditions" => "time < :newTime:",
								"bind" => array('newTime' => (time() - 15552000)) //-- 6 months --//
							));
							if (!empty($attempts)) {
								foreach($attempts as $item) {
									$item->delete();
								}
							}
							//-- END: Remove Old Login Records --//

							//-- Is Site Locked Down? --//
							if(!$lockDown || ($lockDown && $user->role == 1) || ($lockDown && !empty($superPass) && $this->security->checkHash($password, $superPass))){
								//-- The password is valid - User is logged in --//
								$this->session->set("logged_in", true);
								//-- Set Session Vars --//
								$this->session->set("user-id", $user->id);
								if($user->usernm){
									$this->session->set("user-name", $user->usernm);
								}else{
									$this->session->set("user-name", $user->fname.' '.$user->lname);
								}
								$this->session->set("user-role", $user->role);
								$this->session->set("user-email", $user->email);
								$this->session->set("user-school", $user->school);
								$this->session->set("user-district", $user->district);
								$this->session->set("user-grade", $user->grade);
								if($user->school){
									//-- Grab Campus State & District --//
									$campus_info = Schools::findFirst(array("id = ".$user->school, "columns" => "state, district"));
									$this->session->set("campus-state", $campus_info->state);
									$this->session->set("district", $campus_info->district);
								}

								//-- Capabilities --//
								$capabilities = userCapabilities($user->role);
								$this->session->set("capabilities", $capabilities);

								//-- Grab Current Semester --//
								$semester = Semesters::findFirst(array("active = 1", "order" => "id DESC"));
								$this->session->set("current-semester", $semester->id);
								//-- Redirect to the home page --//
								$this->flashSession->success($preMsg."<strong>You Made It!</strong> You successfully logged in. Congratulations!");
								return $this->response->redirect("");
							}else{
								//-- Site is locked down for user --//
								return $this->response->redirect("session/lockdown");
							}
						}else{
							$this->flashSession->error($preMsg."<strong>Password Is Incorrect!</strong> Please try again, or select the 'Forgot your password' option below.");
							return $this->response->redirect("session");
						}

					}else if($student){

						if((!empty($student->pass) && $this->security->checkHash($password, $student->pass)) || (!empty($superPass) && $this->security->checkHash($password, $superPass))){
							//-- Is Site Locked Down? --//
							if(!$lockDown || ($lockDown && !empty($superPass) && $this->security->checkHash($password, $superPass))){
								//-- The password is valid - User is logged in --//
								$this->session->set("logged_in", true);
								//-- Set Session Vars --//
								$this->session->set("user-id", NULL);
								$this->session->set("student-id", $student->id);
								$this->session->set("student-name", $student->fname.' '.$student->lname);
								$this->session->set("user-role", 100); //-- Set User Role to really low, so they can't see any user functionality --//
								$this->session->set("student-email", $student->email);
								$this->session->set("student-school", $student->school);
								$this->session->set("student-grade", $student->grade);
								//-- Grab Current Semester --//
								$semester = Semesters::findFirst(array("active = 1", "order" => "id DESC"));
								$this->session->set("current-semester", $semester->id);
								//-- Redirect to the home page --//
								$this->flashSession->success($preMsg."<strong>You Made It!</strong> You successfully logged in. Congratulations!");
								return $this->response->redirect("");
							}else{
								//-- Site is locked down for user --//
								return $this->response->redirect("session/lockdown");
							}
						}else{
							$this->flashSession->error($preMsg."<strong>Password Is Incorrect!</strong> Please try again, or select the 'Forgot your password' option below.");
							return $this->response->redirect("session");
						}

					}else{
						$this->flashSession->error($preMsg."<strong>Incorrect Login!</strong> We found no user with that username or email. Please enter a different one.");
						return $this->response->redirect("session");
					}
				}else{
					$this->flashSession->error($preMsg."<strong>Both Username and Password Required!</strong> Please try again, but this time put something in the box.");
					return $this->response->redirect("session");
				}
			/*}else{
				$this->flashSession->error($preMsg."<strong>Code Not Good</strong> Please try again!");
				return $this->response->redirect("session");
			}*/
		}

		//-- Do not allow accessing page without submitting login form --//
		return $this->response->redirect("session");

	} // end loginAction()


	public function forgotAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true) {
			//-- Function to Send RESET Password Email --//
			if($this->request->getPost("action") == 'forgot_password'){
				$forgotVal = trim($this->request->getPost('forgotVal'));
				$results = array();

				if($forgotVal){
					//-- Grab User by either Username or Email --//
					if(strpos($forgotVal, '@')){
						$forgotEmail = $this->filter->sanitize($forgotVal, "email");
						$user = Users::findFirst(array(
							"conditions" => "email = :email:",
							"bind" => array('email' => $forgotEmail)
							));
						if(!$user){
							$student = Students::findFirst(array(
								"conditions" => "email = :email:",
								"bind" => array('email' => $forgotEmail)
								));
							if($student){ $results['msg1'] = 'Found Student'; }
						}
					}else{
						$forgotUser = $this->filter->sanitize($forgotVal, "string");
						$user = Users::findFirst(array(
							"conditions" => "usernm = :login:",
							"bind" => array('login' => $forgotUser)
							));
					}
					//-- if user found --//
					if($user){
						//-- Detect if reset has been entered --//
						$res = ResetPass::findFirst(array("userid = ".$user->id." AND is_student = 0 AND complete = 0", "order" => "id DESC"));
						if(!$res){
							//-- Enter new into reset password table --//
							$res = new ResetPass();
							$res->userid = $user->id;
							$res->is_student = 0;
							$res->complete = 0;
							$res->date = time();
							$res->save();
						}

						if($res->id){
							//-- Generate Reset Link --//
							$resetlink = "https://".$_SERVER['HTTP_HOST']."/session/reset?e=".$res->id;

							//-- Create RESET Password Email --//
							$to = $user->email;
							$subject = "Reset Password?";
							$message = "A request has been made to reset your password. To complete this request simply click the link below and you will reset your password with a generated password which will be sent to your email.\n\n".$resetlink."\n\nIf you did not make this request, or you need further information about your account please contact the character coach at your school. You can also click the 'Support' link on the login page. Thank you.\n\nSincerely,\n\n\t- Athlos Tools";
							//-- Send MSG with mailgun --//
							$result = $this->mailgun->sendMessage("mg.athlosacademies.org", array(
								'from' => "Athlos Tools <admin@athlosacademies.org>",
								'to' => $to,
								'subject' => $subject,
								'text'    => $message
							));
							if($result){
								$results["result"] = "success";
							}else{
								$results["result"] = "failed";
							}

						}else{
							$results["result"] = "failed";
						}

					}else if($student){
						//-- Detect if reset has been entered --//
						$res = ResetPass::findFirst(array("userid = ".$student->id." AND is_student = 1 AND complete = 0", "order" => "id DESC"));
						if(!$res){
							$results['msg2'] = 'inside student section';
							//-- Enter new into reset password table --//
							$res = new ResetPass();
							$res->userid = $student->id;
							$res->is_student = 1;
							$res->complete = 0;
							$res->date = time();
							$res->save();
						}

						if($res->id && !empty($student->email)){
							//-- Generate Reset Link --//
							$resetlink = "https://".$_SERVER['HTTP_HOST']."/session/reset?e=".$res->id;

							//-- Create RESET Password Email --//
							$to = $student->email;
							$subject = "Reset Password?";
							$message = "A request has been made to reset your password. To complete this request simply click the link below and you will reset your password with a generated password which will be sent to your email.\n\n".$resetlink."\n\nIf you did not make this request, or you need further information about your account please contact the character coach at your school. You can also click the 'Support' link on the login page. Thank you.\n\nSincerely,\n\n\t- Athlos Tools";
							//-- Send MSG with mailgun --//
							$result = $this->mailgun->sendMessage("mg.athlosacademies.org", array(
								'from' => "Athlos Tools <admin@athlosacademies.org>",
								'to' => $to,
								'subject' => $subject,
								'text' => $message
							));
							if($result){
								$results["result"] = "success";
							}else{
								$results["result"] = "failed";
								$results['msg4'] = 'Failed to send reset password email.';
							}

						}else{
							$results["result"] = "failed";
							$results['msg3'] = 'failed to grab student from reset pass table';
							$results['res-obj'] = $res;
						}

					}else{
						$results["result"] = "invalid";
					}
				}else{
					$results["result"] = "invalid";
				}
			}

			//-- Disable View --//
			$this->view->disable();

			//-- encode results --//
			echo json_encode($results);
		}
    } // end forgotAction()


	public function resetAction()
    {
		//-- Setup Vars --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";

		/*---------------------------------------------------------------------------
			Validate that url is valid and user is ready for password to be reset
		----------------------------------------------------------------------------*/
		if(isset($_GET['e']) && is_numeric($_GET['e'])){
			$entryID = $this->filter->sanitize($_GET['e'], "int");
			//-- Check to see if the url is still valid and if the password hasn't been reset already --//
			$res = ResetPass::findFirst(array("id = :id: AND complete = 0", "order" => "id DESC", "bind" => array('id' => $entryID)));
			if($res){
				if($res->is_student){
					//-- grab student --//
					$student = Students::findFirst(array(
						"conditions" => "id = :id:",
						"bind" => array('id' => $res->userid)
						));
					if($student && !empty($student->email)){
						//-- Randomly Generate Passwords --//
						$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						$newPass = '';
					    for($i = 0; $i < 10; $i++){
					        $newPass.= $characters[rand(0, strlen($characters) - 1)];
					    }
						//-- Update Password --//
						$student->pass = $this->security->hash($newPass);
						if($student->save() == false){
							$this->flashSession->error($preMsg."<strong>Password Reset Failed</strong> Something went wrong and the password failed to save.");
						}else{
							/*-------------
								SUCCESS
							--------------*/
							//-- update entry in reset pass table --//
							$res->complete = 1;
							$res->save();

							//-- Send "Password Reset" Mail Message --//
							$to = $student->email;
							$subject = "Password Reset";
							$message = "Your password has just been reset for the Athlos Tools Admin. To access the admin go to: https://".$_SERVER['HTTP_HOST']." and log in.\n\nUsername: ".$student->email."\nPassword: ".$newPass."\n\nFeel free to contact your school if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
							//-- Send MSG with Mailgun --//
							$result = $this->mailgun->sendMessage("mg.athlosacademies.org", array(
								'from' => "Athlos Tools <admin@athlosacademies.org>",
								'to' => $to,
								'subject' => $subject,
								'text' => $message
							));
							//-- success msg --//
							$this->flashSession->success($preMsg."<strong>Password Reset</strong> The password was reset successfully.");
						}
					}else{
						//-- if student wasn't found or student has no email address set --//
						$this->flashSession->success($preMsg."<strong>Password Reset Failed</strong> User not found, or has no email address.");
					}

				}else{
					//-- grab user --//
					$user = Users::findFirst(array(
						"conditions" => "id = :id:",
						"bind" => array('id' => $res->userid)
						));
					if($user){
						//-- Randomly Generate Passwords --//
						$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						$newPass = '';
					    for($i = 0; $i < 10; $i++){
					        $newPass.= $characters[rand(0, strlen($characters) - 1)];
					    }
						//-- Update Password --//
						$user->passwd = $this->security->hash($newPass);
						if($user->save() == false){
							$this->flashSession->error($preMsg."<strong>Password Reset Failed</strong> Something went wrong and the password failed to save.");
						}else{
							/*-------------
								SUCCESS
							--------------*/
							//-- update entry in reset pass table --//
							$res->complete = 1;
							$res->save();

							//-- Send "Password Reset" Mail Message --//
							$to = $user->email;
							$subject = "Password Reset";
							$message = "Your password has just been reset for the Athlos Tools Admin. To access the admin go to: https://".$_SERVER['HTTP_HOST']." and log in.\n\nUsername: ".$user->usernm."\nPassword: ".$newPass."\n\nFeel free to contact your school if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
							//-- Send MSG with Mailgun --//
							$result = $this->mailgun->sendMessage("mg.athlosacademies.org", array(
								'from' => "Athlos Tools <admin@athlosacademies.org>",
								'to' => $to,
								'subject' => $subject,
								'text'    => $message
							));
							//-- success msg --//
							$this->flashSession->success($preMsg."<strong>Password Reset</strong> The password was reset successfully.");
						}
					}
				}


			}else{
				$this->flashSession->error($preMsg."<strong>Password Already Reset</strong> To reset your password, fill out the forgot password form.");
			}
		}else{
			$this->flashSession->error($preMsg."<strong>Invalid Url!</strong> Url was incorrectly formed. To reset your password fill out the forgot password form.");
		}

		//-- Disable View --//
		$this->view->disable();

		return $this->response->redirect("session");

    } // end resetAction()

	public function splashAction()
    {
		//-- Display Splash Page --//
		if((isset($_GET['tools']) && $_GET['tools'] == 'resetAthlos') || (isset($_COOKIE['hide-splash']) && $_COOKIE['hide-splash'])){
			//-- Set Cookie -- don't show splash page --//
			setcookie("hide-splash", 1, time()+86400, '/');
			//-- Redirect to splash page --//
			return $this->response->redirect("session");
		}
	}

	public function lockdownAction()
    {
		//-- nothing --//
	}

} //-- End Class: SessionController --//

function userCapabilities($userRole){
	//-- Empty Capabilities Array --//
	$capabilities = array(
		"administration" => array("view" => 0, "manage" => 0),
		"assessments" => array("view" => 0, "enter" => 0, "scorecard" => 0, "protocols" => 0),
		"athletic-curriculum" => array("view" => 0, "manage" => 0, "submit-contribution" => 0),
		"campuses" => array("view" => 0, "add" => 0, "edit" => 0, "delete" => 0),
		"character-curriculum" => array("view" => 0, "manage" => 0, "submit-contribution" => 0),
		"character-grading" => array("view" => 0, "staff-grade" => 0, "parent-grade" => 0, "verify-child" => 0, "reportcard" => 0),
		"dashboard" => array("reports" => 0),
		"districts" => array("view" => 0, "add" => 0, "edit" => 0, "delete" => 0),
		"movement-breaks" => array("view" => 0, "manage" => 0, "submit-contribution" => 0),
		"parents" => array("view" => 0),
		"prepared-mind" => array("view" => 0, "manage" => 0, "submit-contribution" => 0),
		"rosters" => array("view" => 0, "add-to-roster" => 0, "edit" => 0, "create" => 0, "remove" => 0),
		"students" => array("view" => 0, "add" => 0, "edit" => 0, "delete" => 0),
		"users" => array("view" => 0, "add" => 0, "edit" => 0, "delete" => 0)
	);

	//-- Add Capabilities per User Role --//
	switch ($userRole) {
		case 1://-- Super Admin --//
			$capabilities['administration'] = array("view" => 1, "manage" => 1);
		case 2://-- Athlos Admin --//

			//	Management capabilities
			$capabilities['athletic-curriculum']['manage'] = 1;
			$capabilities['character-curriculum']['manage'] = 1;
			$capabilities['movement-breaks']['manage'] = 1;
			$capabilities['prepared-mind']['manage'] = 1;

			$capabilities['campuses'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
			$capabilities['dashboard'] = array("reports" => 1);
			$capabilities['districts'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
			$capabilities['users']['add'] = 1;
			$capabilities['users']['edit'] = 1;


		case 3://--	District Admin --//
		case 4://-- Campus Admin --//
		case 5: //-- Lead APC (Lead Athletic Coach) --//
			$capabilities['rosters']['remove'] = 1;
			$capabilities['users']['view'] = 1;
			$capabilities['users']['delete'] = 1;

		case 6: //-- Athletic Coach --//
		case 7: //-- Character Coach --//
		case 8: //-- Teacher --//
			$capabilities['assessments'] = array("view" => 1, "enter" => 1, "scorecard" => 1, "protocols" => 1);
			$capabilities['athletic-curriculum']['view'] = 1;
			$capabilities['athletic-curriculum']['submit-contribution'] = 1;
			$capabilities['character-curriculum']['view'] = 1;
			$capabilities['character-curriculum']['submit-contribution'] = 1;
			$capabilities['movement-breaks']['view'] = 1;
			$capabilities['movement-breaks']['submit-contribution'] = 1;
			$capabilities['prepared-mind']['view'] = 1;
			$capabilities['prepared-mind']['submit-contribution'] = 1;
			$capabilities['rosters']['view'] = 1;
			$capabilities['rosters']['add-to-roster'] = 1;
			$capabilities['rosters']['edit'] = 1;
			$capabilities['rosters']['create'] = 1;
			$capabilities['students'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		default:
			break;
	}


	/*
	PREVIOUS ATHLOS TOOLS CONFIGURATION -- changed as of August 2020 upon migration to Athlos Athletic Assessments

	if($userRole == 9){ //-- Parent --//

	}else if($userRole == 8){ //-- Teacher --//
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['students']['view'] = 1;
	}else if($userRole == 7){ //-- Character Coach --//
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['students'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
	}else if($userRole == 6){ //-- Athletic Coach --//
		$capabilities['assessments'] = array("view" => 1, "enter" => 1, "scorecard" => 1, "protocols" => 1);
		$capabilities['athletic-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['rosters'] = array("view" => 1, "add-to-roster" => 1, "edit" => 1, "create" => 1, "remove" => 0);
		$capabilities['students']['view'] = 1;
	}else if($userRole == 5){ //-- Lead APC (Lead Athletic Coach) --//
		$capabilities['assessments'] = array("view" => 1, "enter" => 1, "scorecard" => 1, "protocols" => 1);
		$capabilities['athletic-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['rosters'] = array("view" => 1, "add-to-roster" => 1, "edit" => 1, "create" => 1, "remove" => 1);
		$capabilities['students'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		$capabilities['users'] = array("view" => 1, "add" => 0, "edit" => 0, "delete" => 1);
	}else if($userRole == 4){ //-- Campus Admin --//
		$capabilities['assessments'] = array("view" => 1, "enter" => 0, "scorecard" => 0, "protocols" => 1);
		$capabilities['athletic-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['rosters']['view'] = 1;
		$capabilities['students'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		$capabilities['users'] = array("view" => 1, "add" => 0, "edit" => 0, "delete" => 1);
	}else if($userRole == 3){ //-- District Admin --//
		$capabilities['assessments'] = array("view" => 1, "enter" => 0, "scorecard" => 0, "protocols" => 1);
		$capabilities['athletic-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 0, "submit-contribution" => 1);
		$capabilities['rosters']['view'] = 1;
		$capabilities['students']['view'] = 1;
		$capabilities['users'] = array("view" => 1, "add" => 0, "edit" => 0, "delete" => 0);
	}else if($userRole == 2 || $userRole == 1){ //-- Athlos Admins & Super Admins --//
		$capabilities['assessments'] = array("view" => 1, "enter" => 1, "scorecard" => 1, "protocols" => 1);
		$capabilities['athletic-curriculum'] = array("view" => 1, "manage" => 1, "submit-contribution" => 1);
		$capabilities['campuses'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		$capabilities['character-curriculum'] = array("view" => 1, "manage" => 1, "submit-contribution" => 1);
		$capabilities['dashboard'] = array("reports" => 1);
		$capabilities['districts'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		$capabilities['movement-breaks'] = array("view" => 1, "manage" => 1, "submit-contribution" => 1);
		$capabilities['prepared-mind'] = array("view" => 1, "manage" => 1, "submit-contribution" => 1);
		$capabilities['rosters'] = array("view" => 1, "add-to-roster" => 1, "edit" => 1, "create" => 1, "remove" => 1);
		$capabilities['students'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		$capabilities['users'] = array("view" => 1, "add" => 1, "edit" => 1, "delete" => 1);
		//-- Super Admin Extra Capabilities --//
		if($userRole == 1){
			$capabilities['administration'] = array("view" => 1, "manage" => 1);
		}
	}
	*/

	return $capabilities;
}
