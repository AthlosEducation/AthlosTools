<?php

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class ControllerBase extends \Phalcon\Mvc\Controller
{
	
	public $cap;
	
	/*----------------------------------------------------------------------
		These Actions are Available to All Controllers if they want them
	-----------------------------------------------------------------------*/
	public function validnameAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Username --//
			if($this->request->getPost("action") == 'validate_username'){
				//-- Sanitize Vars --//
				$username = $this->request->getPost("user_name", "string");
				$userID = $this->request->getPost("user_id", "int");
				$results = array();
				//-- Check to see if username exists --//
				if($userID){
					$check = Users::count(array("usernm = :login: AND id != :id:", "bind" => array("login" => $username, "id" => $userID)));
				}else{
					$check = Users::count(array("usernm = :login:", "bind" => array("login" => $username)));
				}
				if(!$check){
					$results['result'] = "success";
					$results['username'] = $username;
				}else{
					$results['result'] = "failed";
				}
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end validnameAction(); --//
	
	
	public function validaltidAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Alt ID --//
			if($this->request->getPost("action") == 'validate_alt_id'){
				//-- Sanitize Vars --//
				$alt_id = $this->request->getPost("altid", "string");
				$schoolID = $this->request->getPost("school", "int");
				$districtID = $this->request->getPost("district", "int");
				$userID = $this->request->getPost("user_id", "int");
				$userRole = $this->request->getPost("user_role", "int");
				$results = $schoolList = array();
				
				if(!empty($schoolID) || !empty($districtID)){
					if(!empty($schoolID)){
						$theSchool = Schools::findFirst(array("id = :id:", "bind" => array("id" => $schoolID)));
						$district = $theSchool->district;
						//-- Find list of schools --//
						$schools = Schools::find(array(
							"conditions" => "district = :Dist:",
							"bind" => array("Dist" => $district)
						));
					}else{
						//-- Find list of schools --//
						$schools = Schools::find(array(
							"conditions" => "district = :Dist:",
							"bind" => array("Dist" => $districtID)
						));
					}
					//-- Create list of schools --//
					foreach($schools as $school){
						$schoolList[] = $school->id;
					}
					
					//-- Check to see if alt ID already exists -- Check within School Districts --//
					if($userID){
						$check = Users::count(array("alt_id = :alt: AND school IN( ".implode(', ', $schoolList)." ) AND id != :id:", "bind" => array("alt" => $alt_id, "id" => $userID)));
					}else{
						$check = Users::count(array("alt_id = :alt: AND school IN( ".implode(', ', $schoolList)." )", "bind" => array("alt" => $alt_id)));
					}
				}else if($userRole <= 2){
					if($userID){
						$check = Users::count(array("alt_id = :alt: AND school = 0 AND id != :id:", "bind" => array("alt" => $alt_id, "id" => $userID)));
					}else{
						$check = Users::count(array("alt_id = :alt: AND school = 0", "bind" => array("alt" => $alt_id)));
					}
				}
				
				if(!isset($check)){
					$results['result'] = "invalid";
				}else if(!$check){
					$results['result'] = "success";
				}else{
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end validaltidAction(); --//
	
	
	public function validemailAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Email Address --//
			if($this->request->getPost("action") == 'validate_email'){
				//-- Sanitize Vars --//
				$email = $this->request->getPost("email", "email");
				$userID = $this->request->getPost("user_id", "int");
				$type = $this->request->getPost("type", "string");
				$results = array();
				//-- Check to see if email address exists --//
				if(isset($type) && $type == 'student'){
					if($userID){
						$check = Students::count(array("email = :email: AND id != :id:", "bind" => array("email" => $email, "id" => $userID)));
					}else{
						$check = Students::count(array("email = :email:", "bind" => array("email" => $email)));
					}
					//-- Include checking user table --//
					$check2 = Users::count(array("email = :email:", "bind" => array("email" => $email)));
				}else{
					if($userID){
						$check = Users::count(array("email = :email: AND id != :id:", "bind" => array("email" => $email, "id" => $userID)));
					}else{
						$check = Users::count(array("email = :email:", "bind" => array("email" => $email)));
					}
					//-- Include checking student table --//
					$check2 = Students::count(array("email = :email:", "bind" => array("email" => $email)));
				}
				
				if(!$check && !$check2){
					$results['result'] = "success";
				}else{
					$results['result'] = "failed";
				}
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end validemailAction(); --//
	
	
	public function adduserAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add User --//
			if($this->request->getPost("action") == 'add_user'){
				//-- Sanitize Vars --//
				$alt_id = $this->request->getPost("altid", "string");
				$state_id = $this->request->getPost("stateid", "string");
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$email = $this->request->getPost("email", "email");
				$phone = $this->filter->sanitize($this->request->getPost("phone", "int"),"alphanum");
				$username = $this->request->getPost("user_name", "string");
				$role = $this->request->getPost("type", "int");
				$school = $this->request->getPost("school", "int");
				$district = $this->request->getPost("district", "int");
				$grade = $this->request->getPost("grade", "int");
				$pass = $this->request->getPost("pass", "string");
				$send_email = $this->request->getPost("send_email", "int");
				$results = $schoolList = array();
				
				//-- Grab Capabilities --//
				$this->cap = $this->session->get("capabilities");
				
				//-- don't send email if user is a parent --//
				if(!empty($role) && $role == 9){ $send_email = false; }
				
				//-- Verify Permissions --//
				if($this->cap['users']['add']){
					//-- Make sure user can only create users with the same access or less --//
					if($role < (int)$this->session->get("user-role")){
						//-- error out - not enough permissions - illegal action --//
						$results['result'] = "failed";
						$results["error_title"] = "Failure - Illegal Action";
						$results["error_msg"] = "Something went wrong. If this continues, contact the web administrator.";
					}else{
						//-- eliminate special chars & spaces in username --//
						if($username){
							$sChars = array(' ','<','>','&','{','}','*','!','@','#','$','%','^','(',')','[',']','"',"'",';',':','?','/','`','+','=','~');
							$username = str_replace($sChars, array(''), $username);
						}
						
						//-- truncate vars to certain length --//
						if($username && strlen($username) > 32){ $username = substr($username, 0, 32); }
						if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
						if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
						if(strlen($role) > 5){ $role = substr($role, 0, 5); }
						if($phone && strlen($phone) > 10){ $phone = substr($phone, 0, 10); }
						if($school && strlen($school) > 5){ $school = substr($school, 0, 5); }
						if($district && strlen($district) > 5){ $district = substr($district, 0, 5); }
						if($grade && strlen($grade) > 2){ $grade = substr($grade, 0, 2); }
						
						//-- Make sure the required info is present --//
						if($fname && $lname && $email && $role && $pass){
							//-- require school --//
							if($role <= 3 || $role == 9 || $school){
								//-- require district --//
								if($role <= 2 || $role == 9 || $district){
									//-- check length of username --//
									if(!$username || strlen($username) > 4){
										//-- Check to see if username exists --//
										$check = Users::count(array("usernm = :login:", "bind" => array("login" => $username)));
										if(!$username || !$check){
											//-- Check to see if email is taken --//
											$checkEmail = Users::count(array("email = :email:", "bind" => array("email" => $email)));
											if(!$checkEmail){
												//-- Find list of schools --//
												if(!empty($district)){
													$schools = Schools::find(array(
														"conditions" => "district = :Dist:",
														"bind" => array("Dist" => $district)
													));
													foreach($schools as $sch){
														$schoolList[] = $sch->id;
													}
												}else{
													$schoolList[] = 0;
												}
												
												//-- Verify user's Alt ID is unique for their district --//
												if(!empty($alt_id)){
													$checkAlt = Users::count(array(
														"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." )",
														"bind" => array("altID" => $alt_id)
													));
												}else{
													$checkAlt = false;
												}
												if(!$checkAlt){
													/*------------------------------------------
														Now Add User -- Passed all validation
													--------------------------------------------*/
													$aUser = New Users();
													if($username){
														$aUser->usernm = $username;
													}else{
														$aUser->usernm = null;
													}
													$aUser->passwd = $this->security->hash($pass);
													$aUser->role = $role;
													$aUser->fname = $fname;
													$aUser->lname = $lname;
													$aUser->email = $email;
													if($phone){ $aUser->phone = $phone; }
													if($school){
														$aUser->school = $school;
													}else{
														$aUser->school = 0;
													}
													if($district){
														$aUser->district = $district;
													}else{
														$aUser->district = 0;
													} //-- Alt ID --//
													if(!empty($alt_id)){
														$aUser->alt_id = $alt_id;
													}else{
														$aUser->alt_id = NULL;
													} //-- State ID --//
													if(!empty($state_id)){
														$aUser->state_id = $state_id;
													}else{
														$aUser->state_id = NULL;
													}
													/*if($grade != ''){
														$aUser->grade = $grade;
													}*/
													$aUser->date_add = time();

													//-- Save Entry --//
													if($aUser->save() == false){
														$results["result"] = "failed";
														$results["error_title"] = "Failed to Add User";
														$results["error_msg"] = "Something went wrong, and the user was not created.";
														//$results["user"] = $aUser;
													}else{
														$results["result"] = "success";
														if($send_email){
															//-- Setup Mailgun Object --//
															$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

															//-- if username is blank give email for the login info --//
															if($username){ $logcreds = "Username: ".$username; }else{ $logcreds = "Email: ".$email; }

															//-- Send Mail Message --//
															$to = $email;
															$subject = "Welcome to Athlos Tools";
															$message = "You have just been added to the Athlos Tools Admin. You can now login and perform actions like grading students by going to this url: https://".$_SERVER['HTTP_HOST']."\n\n".$logcreds."\nPassword: ".$pass."\n\nFeel free to reply to this email if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
															//-- Send MSG with Mailgun --//
															$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
															                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
															                        'to'      => $to,
															                        'subject' => $subject,
															                        'text'    => $message));
														}
													}
													
												}else{
													$results['result'] = "failed";
													$results["error_title"] = "Alt_ID In Use";
													$results["error_msg"] = "The Alt ID or SIS ID is already being used in this school. Please use another.";
												}
											}else{
												$results['result'] = "failed";
												$results["error_title"] = "Email Already In Use";
												$results["error_msg"] = "The Email Address is already being used. Please use another.";
											}
										}else{
											$results['result'] = "failed";
											$results["error_title"] = "Username Already In Use";
											$results["error_msg"] = "Username is already being used. Please choose another.";
										}
									}else{
										$results['result'] = "failed";
										$results["error_title"] = "Username Too Short";
										$results["error_msg"] = "Username needs to be a minimum of 5 characters.";
									}
								}else{
									$results['result'] = "failed";
									$results["error_title"] = "District Required";
									$results["error_msg"] = "Please assign user a district.";
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "School Required";
								$results["error_msg"] = "Please assign user to a school.";
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Something Is Missing";
							$results["error_msg"] = "Make sure all the fields are filled out correctly.";
						}
					}
					
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end adduserAction() --//
	
	public function edituserAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab User --//
			if($this->request->getPost("action") == 'grab_user'){
				
				//-- grab / set vars --//
				$userID = $this->request->getPost("theID", "int");
				$results = array();

				if($userID && is_numeric($userID)){

					//-- Grab User --//
					$user = Users::findFirst(array(
						"conditions" => "id = :userID:",
						"bind" => array('userID' => $userID)
						));

					//-- the user json object --//
					$results["result"] = "success";
					$results["id"] = $user->id;
					$results["alt_id"] = $user->alt_id;
					$results["state_id"] = $user->state_id;
					$results["username"] = $user->usernm;
					$results["first"] = $user->fname;
					$results["last"] = $user->lname;
					$results["email"] = $user->email;
					$results["phone"] = $user->phone;
					$results["type"] = $user->role;	
					if($user->school != 0){
						$results["school"] = $user->school;
					}else{
						$results["school"] = '';
					}
					if($user->district != 0){
						$results["district"] = $user->district;
					}else{
						$results["district"] = '';
					}
					$results["grade"] = '';

				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_User --//
			
			//-- Function to Edit User --//
			if($this->request->getPost("action") == 'edit_user'){
				
				//-- Sanitize Vars --//
				$userID = $this->request->getPost("user_id", "int");
				$alt_id = $this->request->getPost("altid", "string");
				$state_id = $this->request->getPost("stateid", "string");
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$email = $this->request->getPost("email", "email");
				$phone = $this->filter->sanitize($this->request->getPost("phone", "int"),"alphanum");
				$role = $this->request->getPost("type", "int"); //-- used only to determine what user role is being updated (DO NOT ALLOW TO UPDATE USER ROLE) --//
				$updateRole = $this->request->getPost("updateRole", "int"); //-- New var helping me know which script is asking to update user (then UPDATE USER ROLE) --//
				$username = $this->request->getPost("user_name", "string");
				$school = $this->request->getPost("school", "int");
				$district = $this->request->getPost("district", "int");
				$grade = $this->request->getPost("grade", "int");
				$pass = $this->request->getPost("pass", "string");
				$send_email = $this->request->getPost("send_email", "int");
				$results = $schoolList = array();
				
				//-- Grab Capabilities --//
				$this->cap = $this->session->get("capabilities");
				
				//-- Verify Permissions --//
				if($this->cap['users']['edit']){
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
					if($school && strlen($school) > 5){ $school = substr($school, 0, 5); }
					if($district && strlen($district) > 5){ $district = substr($district, 0, 5); }
					if($grade && strlen($grade) > 2){ $grade = substr($grade, 0, 2); }
					
					//-- Make sure the required info is present --//
					if($userID && $fname && $lname && $email){
						//-- require school --//
						if($role <= 3 || $role == 9 || $school){
							//-- require district --//
							if($role <= 2 || $role == 9 || $district){
								//-- check length of username --//
								if(!$username || strlen($username) > 4){
									//-- Check to see if username exists --//
									$check = Users::count(array("usernm = :login: AND id != :id:", "bind" => array("login" => $username, "id" => $userID)));
									if(!$username || !$check){
										//-- Check to see if email is taken --//
										$checkEmail = Users::count(array("email = :email: AND id != :id:", "bind" => array("email" => $email, "id" => $userID)));
										if(!$checkEmail){
											//-- Find list of schools --//
											if(!empty($district)){
												$schools = Schools::find(array(
													"conditions" => "district = :Dist:",
													"bind" => array("Dist" => $district)
												));
												foreach($schools as $sch){
													$schoolList[] = $sch->id;
												}
											}else{
												$schoolList[] = 0;
											}
											//-- Verify user's Alt ID is unique for their district --//
											if(!empty($alt_id)){
												$checkAlt = Users::count(array(
													"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." ) AND id != :id:",
													"bind" => array("altID" => $alt_id, "id" => $userID)
												));
											}else{
												$checkAlt = false;
											}
											if(!$checkAlt){
												/*------------------------------------------------------
													Grab User Object && See if username / pwd changed
												-------------------------------------------------------*/
												$aUser = Users::findFirst(array(
													"conditions" => "id = :userID:",
													"bind" => array('userID' => $userID)
													));
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
												if(!empty($updateRole) && $updateRole == 1){
													$aUser->role = $role;
												}
												if($pass){ $aUser->passwd = $this->security->hash($pass); }
												if($phone){
													$aUser->phone = $phone;
												}else{
													$aUser->phone = null;
												}
												if($school){
													$aUser->school = $school;
												}else{
													$aUser->school = 0;
												}
												if($district){
													$aUser->district = $district;
												}else{
													$aUser->district = 0;
												} //-- Alt ID --//
												if(!empty($alt_id)){
													$aUser->alt_id = $alt_id;
												}else{
													$aUser->alt_id = NULL;
												} //-- State ID --//
												if(!empty($state_id)){
													$aUser->state_id = $state_id;
												}else{
													$aUser->state_id = NULL;
												}
												/*if($grade != ''){
													$aUser->grade = $grade;
												}*/

												//-- Save Entry --//
												if($aUser->save() == false){
													$results["result"] = "failed";
													$results["error_title"] = "Failed to Update User";
													$results["error_msg"] = "Something went wrong, and the user was not updated.";
												}else{
													$results["result"] = "success";

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
														$message = "A recent change was made to your account and your login information was updated. You can login to Athlos Tools  by going to this url: https://".$_SERVER['HTTP_HOST']." and logging in.\n\n".$logcreds."\n\nFeel free to reply to this email if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
														//-- Send MSG with Mailgun --//
														$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
														                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
														                        'to'      => $to,
														                        'subject' => $subject,
														                        'text'    => $message));
													}
												}
												
											}else{
												$results['result'] = "failed";
												$results["error_title"] = "Alt_ID In Use";
												$results["error_msg"] = "The Alt ID or SIS ID is already being used in this school. Please use another.";
											}
										}else{
											$results['result'] = "failed";
											$results["error_title"] = "Email Already In Use";
											$results["error_msg"] = "The Email Address is already being used. Please use another.";
										}
									}else{
										$results['result'] = "failed";
										$results["error_title"] = "Username Already In Use";
										$results["error_msg"] = "Username is already being used. Please choose another.";
									}
								}else{
									$results['result'] = "failed";
									$results["error_title"] = "Username Too Short";
									$results["error_msg"] = "Username needs to be a minimum of 5 characters.";
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "District Required";
								$results["error_msg"] = "Please assign user to a district.";
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "School Required";
							$results["error_msg"] = "Please assign user to a school.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Something Is Missing";
						$results["error_msg"] = "Make sure all the fields are filled out correctly.";
					}
					
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}
				
				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Edit_User --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end edituserAction() --//
	
	public function deluserAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'delete_user'){
				
				//-- grab / set / sanitize vars --//
				$userID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Grab Capabilities --//
				$this->cap = $this->session->get("capabilities");
				
				//-- Verify Permissions --//
				if($this->cap['users']['delete']){
					if($userID && is_numeric($userID)){

						//-- Grab User --//
						$user = Users::findFirst(array(
							"conditions" => "id = :userID:",
							"bind" => array('userID' => $userID)
							));
							
						if($user){
							//-- Delete from DB --//
							if($user->delete() == false){
							    $results['result'] = "failed";
								$results["error_title"] = "Failed to Delete User";
								$results["error_msg"] = "Something went wrong, and the user was not deleted.";
							}else{
								$results["result"] = "success";
							}
						}else{
							$results["result"] = "invalid";
						}

					}else{
						$results["result"] = "invalid";
					}

				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end deluserAction() --//
	
	
	public function assigngradesAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab Grade Levels --//
			if($this->request->getPost("action") == 'grab_grade_levels'){
				
				//-- grab / set vars --//
				$userID = $this->request->getPost("theID", "int");
				$results = array();

				if($userID && is_numeric($userID)){

					//-- Grab User --//
					$gLimit = GradeLimit::findFirst(array(
						"conditions" => "user = :userID:",
						"bind" => array('userID' => $userID)
						));
					
					if($gLimit){
						$results["result"] = "success";
						$results["grades"] = $gLimit->grades;
					}else{
						$results["result"] = "success";
						$results["grades"] = NULL;
					}

				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab Grade Levels --//
			
			//-- Function to Update Grade Levels --//
			if($this->request->getPost("action") == 'update_grade_levels'){
				
				//-- grab / set vars --//
				$userID = $this->request->getPost("user_id", "int");
				$gradeVal = trim($this->request->getPost("gradeVal", "string"));
				$results = array();
				
				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){
					
					if($userID && is_numeric($userID)){

						//-- Truncate Grade Value --//
						if(strlen($gradeVal) > 30){ $gradeVal = substr($gradeVal, 0, 30); }

						//-- Grab User --//
						$gLimit = GradeLimit::findFirst(array(
							"conditions" => "user = :userID:",
							"bind" => array('userID' => $userID)
							));

						//-- update the object --//
						if($gLimit){
							$gLimit->grades = $gradeVal;
						}else{
							$gLimit = new GradeLimit();
							$gLimit->user = $userID;
							$gLimit->grades = $gradeVal;
						}

						//-- save to db --//
						if($gLimit->save() == false){
							$results["result"] = "failed";
							$results["error_title"] = "Failed to Update";
							$results["error_msg"] = "Something went wrong, and the grade levels were not updated.";
						}else{
							$results["result"] = "success";
						}
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Invalid Data";
						$results["error_msg"] = "The information sent was not correct, and the grade levels were not updated. Try refreshing the page.";
					}
					
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Update Grade Levels --//
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end assigngradesAction() --//
	
	
	public function csvexportAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'CSVExport'){
				
				//$page = $this->request->getPost("CSVPage");
				$currUrl = parse_url($_SERVER['REQUEST_URI']);
				$page = str_replace("/","",str_replace("csvexport","",$currUrl["path"]));
				
				switch($page){
					case "administrators":
						$role = 1;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						break;
					case "coaches":
						$role = 2;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						break;
					case "turfcoach":
						$role = 4;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						break;
					case "teachers":
						$role = 3;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						break;
					case "parents":
						$role = 5;
						$columns = "id,fname,lname,email,phone";
						$table = "users";
						break;
					case "students":
						$columns = "id,fname,lname,school,grade,teacher";
						$table = "students";
						break;
				}
				if($this->request->getPost("CSVRows") != ""){
					$ids = $this->request->getPost("CSVRows");
				}
				
				if($this->request->getPost("CSVschool") != ""){
					$schoolID = $this->request->getPost("CSVschool");
				}
				
				if($this->request->getPost("CSVgrade") != ""){
					$grade = $this->request->getPost("CSVgrade");
				}
				
				if($this->request->getPost("CSVteacher") != ""){
					$teacherID = $this->request->getPost("CSVteacher");
				}
				
				if($this->request->getPost("CSVonlyAltIDs") != ""){
					$altIDsOnly = $this->request->getPost("CSVonlyAltIDs");
				}
				
				
				//Grab Results and Export CSV -- admin,character,turf,teacher,parent,student
				if($table == 'users'){
					
					//-- Figure out filter conditions --//
					$conditions = "role = ".$role;
					if(isset($schoolID) && $schoolID){
						$conditions.= " AND school = ".$schoolID;
					}
					if(isset($ids) && $ids){
						$conditions.= " AND id IN (".$ids.")";
					}
					if(isset($altIDsOnly) && $altIDsOnly){
						$conditions.= " AND (alt_id IS NULL OR alt_id  = '')";
					}
					
					//get users results
					$entries = Users::find(array($conditions, "columns" => $columns));
				}
				
				//Grab Results and Export CSV -- student
				if($table == 'students'){
					
					//-- Figure out filter conditions --//
					$conditions = "1";
					if(isset($schoolID) && $schoolID){
						$conditions.= " AND school = ".$schoolID;
					}
					if(isset($teacherID) && $teacherID){
						$conditions.= " AND teacher = ".$schoolID;
					}
					if(isset($grade) && $grade){
						$conditions.= " AND grade = ".$grade;
					}
					if(isset($ids) && $ids){
						$conditions.= " AND id IN (".$ids.")";
					}
					
					//get users results
					$entries = Students::find(array($conditions, "columns" => $columns));
				}
				
				
				//print_r($users);
				
				//send CSV File Headers
				$CSVFilename = "Athlos_" .$page. "_" . time() . ".csv";
				header("Pragma: public");
			    header("Expires: 0");
			    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			    header("Content-Type: application/force-download");
			    header("Content-Type: application/octet-stream");
			    header("Content-Type: application/download"); 
			    header("Content-Disposition: attachment;filename={$CSVFilename}");
			    header("Content-Transfer-Encoding: binary");
			
				//echo out CSV File Contents
				$newArray = array();
				foreach($entries AS $row){
					$tempArray = array();
					$cols = explode(",",$columns);
					foreach($cols AS $col){
						$tempArray[$col] = $row->$col;
					}
					array_push($newArray, $tempArray);
				}
				if (count($newArray) == 0) {
				  exit();
				}
				$fp = fopen("php://output", 'w');
				fputcsv($fp, array_keys(reset($newArray)));
				foreach ($newArray as $row) {
				   fputcsv($fp, $row);
				}
				fclose($fp);
				exit();
				
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end csvExportAction() --//
	
	public function csvuploadAction()
	{
		//error_log("this is a test");
		error_reporting(E_ALL | E_STRICT);
		require('../app/controllers/fileUpload/UploadHandler.php');
		$upload_handler = new UploadHandler();
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end csvuploadAction() --//
	
	public function csvimportAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'CSVImport'){
				//error_log("were in!!");
				//$page = $this->request->getPost("CSVPage");
				$currUrl = parse_url($_SERVER['REQUEST_URI']);
				$page = str_replace("/","",str_replace("csvimport","",$currUrl["path"]));
				
				switch($page){
					case "administrators":
						$role = 1;
						$columns = "id,alt_id,usernm,fname,lname,email,phone";
						$table = "users";
						$userType = "Administrator(s)";
						break;
					case "coaches":
						$role = 2;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						$userType = "Character Coach(s)";
						break;
					case "turfcoach":
						$role = 4;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						$userType = "Turf Coach(s)";
						break;
					case "teachers":
						$role = 3;
						$columns = "id,alt_id,fname,lname,email,phone";
						$table = "users";
						$userType = "Teacher(s)";
						break;
					case "parents":
						$role = 5;
						$columns = "id,fname,lname,email,phone";
						$table = "users";
						$userType = "Parent(s)";
						break;
					case "students":
						break;
				}
				
				//-- import school --//
				$importSchool = $this->request->getPost("importSchool", "int");
				if($importSchool != ''){
					$school = $importSchool; //-- Important for all non admins --//
					$theSchool = Schools::findFirst(array("id = :id:", "bind" => array("id" => $school)));
					//-- School Groups --//
					$ilt_group = array();
					$ilt_Schools = Schools::find(array("ilt_school = 1", "columns" => "id"));
					if(!empty($ilt_Schools)){
						foreach($ilt_Schools as $ilt_School){
							$ilt_group[] = $ilt_School->id;
						}
					}
					
					//read CSV file contents
				
					$csvFile = dirname($_SERVER['SCRIPT_FILENAME']).'/../app/controllers/fileUpload/files/'.$this->request->getPost("name");
					$csvFileName = $this->request->getPost("name");
					$row = 0;
					$totalUpdates = 0;
					$recordsUpdated = 0;
					$cols = explode(",",$columns);
					if (($handle = fopen($csvFile, "r")) !== FALSE) {
					    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
							//$results["data"] = $data;
							if($row!=0){
							
					        	if(count($data) > 1){
									if($table == 'users'){
										//check if csv entry is in the users table
										if(!empty($data[0])){
											$exists = Users::count(array("id = :id:", "bind" => array("id" => $data[0])));
										}else if(!empty($data[1])){
											if($theSchool->ilt_school){
												$exists = Users::count(array("alt_id = :alt: AND school IN( ".implode(', ', $ilt_group)." )", "bind" => array("alt" => $data[1])));
											}else{
												$exists = Users::count(array("alt_id = :alt: AND school = :school:", "bind" => array("alt" => $data[1], "school" => $school)));
											}
										}else{
											//-- Data is missing for user to exist --//
											$exists = false;
										}
										if($exists){
											//-- Grab User's Data --//
											if(!empty($data[0])){
												$user = Users::findFirst(array(
													"conditions" => "id = :userID:",
													"bind" => array('userID' => $data[0])
													));
											}else if(!empty($data[1])){
												if($theSchool->ilt_school){
													$user = Users::findFirst(array(
														"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $ilt_group)." )",
														"bind" => array("altID" => $data[1])
														));
												}else{
													$user = Users::findFirst(array(
														"conditions" => "alt_id = :altID: AND school = :schoolID:",
														"bind" => array("altID" => $data[1], "schoolID" => $school)
														));
												}
											}
											//-- Grabbed User Data --//
											if(!empty($user)){
												$x = 0;
												$updates = 0;
												foreach($cols AS $col){
													//$results["gotHere"] = TRUE;
													if($data[$x] != $user->$col && $x > 0){
														//count up the number of fields to be updated
														$updates++;
													}
													$x++;
												}
												//ifupdates exist for the row, apply them
												if($updates > 0){
													$totalUpdates += $updates;
											
													$x = 0;
													foreach($cols AS $col){
														if($x > 0){
															if($col == "email"){
																$email = $data[$x];
															}
														}
														$x++;
													}
													if($email != $user->email){
														$emailExists = Users::count(array("email = :email:", "bind" => array("email" => $email)));
													}else{
														$emailExists = FALSE;
													}
													
													//-- Reporting Name: use real name --//
													$subname = $user->fname.' '.$user->lname;
													
													if(!$emailExists){
														//edit users
														$x = 0;
														foreach($cols AS $col){
															if($x > 0){
																switch($col){
																	case "email":
																		$user->$col = $this->filter->sanitize($data[$x],"email");
																		break;
																	case "phone":
																		$user->$col = $this->filter->sanitize($this->filter->sanitize($data[$x],"int"),"alphanum");
																		break;
																	case "alt_id":
																		$user->$col = $data[$x];
																		break;
																	default;
																		$user->$col = $data[$x];
																		break;
																}
															}
															$x++;
														}
														
														//-- Set school for existing user --//
														$user->school = $school;
														
														//-- Save and then count with successes / fails --//
														if($user->save() == false){
															//-- Failed to Update --//
															if(isset($results["failed"])){
																$results["failed"] .= ",".$subname;
															}else{
																$results["failed"] = $subname;
															}
															foreach($user->getMessages() as $message){
																$results['msg'][] = $message->getMessage();
															}
														}else{
															//-- Successfully Updated --//
															if(isset($updated)){
																$updated .= ",".$subname;
															}else{
																$updated = $subname;
															}
															//count the number of records updated
															$recordsUpdated++;
														}
														
													}else{
														
														//report which users failed to be updated
														if(isset($results["failed"])){
															$results["failed"] .= ",".$subname;
														}else{
															$results["failed"] = $subname;
														}
														//-- Email already exists --//
														$results['msg'][] = "A User with the email: ".$email." Already Exists in the System";
												
													} //-- end if (email already in use) --//
												}
											} //-- end if (grabbed User Object) --//
										}else{
											//-- Add new users --//
											$x = 0;
											foreach($cols AS $col){
												if($x > 0){
													if($col == "email"){
														$email = $data[$x];
													}
													if($col == "fname"){
														$firstName = $data[$x];
													}
													if($col == "lname"){
														$lastName = $data[$x];
													}
													if($col == "alt_id"){
														$alt_id = $data[$x];
													}
												}
												$x++;
											}
											
											$emailExists = Users::count(array("email = :email:", "bind" => array("email" => $email)));
										
											if(!$emailExists){
												//-- Create new user Object --//
												$aUser = New Users();
									
												$x = 0;
												foreach($cols AS $col){
													if($x > 0){
														$aUser->$col = $data[$x];
														if($col == "email"){
															$email = $data[$x];
														}
														if($col == "phone"){
															$aUser->$col = $this->filter->sanitize($this->filter->sanitize($data[$x],"int"),"alphanum");
														}
													}
													$x++;
												}
												//Randomly Generate Passwords
												$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
												$newPass = '';
											    for ($i = 0; $i < 10; $i++) {
											        $newPass .= $characters[rand(0, strlen($characters) - 1)];
											    }
												$aUser->passwd = $this->security->hash($newPass);
												$aUser->role = $role;
												if(isset($school)){
													$aUser->school = $school;
												}else{
													$aUser->school = 0;
												}
												$aUser->date_add = time();
											
												//-- Alternate ID --//
												if(isset($alt_id) && !empty($alt_id)){
													$aUser->alt_id = $alt_id;
												}else{
													unset($alt_id);
												}
												
												//-- Username not added in import | set to blank --//
												$aUser->usernm = '';
											
												//-- use real name / email --//
												$subname = $aUser->fname.' '.$aUser->lname;
												$logname = $aUser->email;
											
												//-- Save Entry --//
												if($aUser->save() == false){
													if(isset($results["failed"])){
														$results["failed"] .= ",".$subname;
													}else{
														$results["failed"] = $subname;
													}
													foreach($aUser->getMessages() as $message){
														$results['msg'][] = $message->getMessage();
													}
										
												}else{
													if(isset($added)){
														$added .= ",".$subname;
													}else{
														$added = $subname;
													}
													
													if(isset($results["added"])){
														$results["added"] .= ",".$subname;
													}else{
														$results["added"] = $subname;
													}
													//-- Do not send welcome email to parents, but yes to all other users --//
													if($role != 5){
														//-- Setup Mailgun Object --//
														$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

														//-- Send Mail Message --//
														$to = $email;
														$subject = "Welcome to Athlos Tools";
														$message = "You have just been added to the Athlos Tools Admin. You can now login and access resources as well as perform actions like grading students by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nUsername: ".$logname."\nPassword: ".$newPass."\n\nFeel free to reply to this email if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
														//-- Send MSG with Mailgun --//
														$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
														                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
														                        'to'      => $to,
														                        'subject' => $subject,
														                        'text'    => $message));
													}
												}
											}else{
												//-- use real name if no username --//
												$subname = $firstName.' '.$lastName;
											
												//report which users failed to be created
												if(isset($results["failed"])){
													$results["failed"] .= ",".$subname;
												}else{
													$results["failed"] = $subname;
												}
												//-- email already exists --//
												$results['msg'][] = "A User with the email: ".$email." Already Exists in the System";
											
											} //-- end if (username or email already in use) --//
										} //-- end if (user exists) --//
									}
								}
							}
							$row++;
					    }
					    fclose($handle);
					
					}
					
					$results["uploaded"] = $csvFileName;
				
					if(unlink($csvFile)){
						$results["fileLocaiton"] = "none";
					}else{
						$results["fileLocaiton"] = $csvFile;
					}
					$results["totalUpdates"] = $totalUpdates;
					$results["recordsUpdated"] = $recordsUpdated;
					$results["userType"] = $userType;
					if(isset($updated)){
						$results["updatedRecords"] = $updated;
					}
					$results["rows"] = $row;
				
					echo json_encode($results);
					
				} //-- end if (has importSchool) --//
				
				exit();
				
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end csvimportAction() --//
	
}