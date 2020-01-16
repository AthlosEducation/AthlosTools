<?php

date_default_timezone_set('UTC');

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class StudentsController extends \Phalcon\Mvc\Controller
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
		//-- Deny Access if no Priveleges --//
		if(!$this->cap['students']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Students");
	}
	
    public function indexAction()
    {
        //-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars / sanitize vars --//
			$limit = $this->request->getPost("limit", "int");
			$page = $this->request->getPost("pageNum", "int");
			$field = $this->request->getPost("field", "string");
			$lastField = $this->request->getPost("lastField", "string");
			$dir = $this->request->getPost("dir", "string");
			$schoolID = $this->request->getPost("filterSchool", "int");
			$gradeID = $this->request->getPost("filterGrade", "int");
			$teacherID = $this->request->getPost("filterTeacher", "int");
			$coachID = $this->request->getPost("filterCoach", "int");
			$searchTerm = $this->request->getPost("filterSearch", "string");
			$activeID = $this->request->getPost("filterActive", "int");
			
			//-- Figure out ordering --//
			if($field){
			    if(!$lastField){
					//-- Do Nothing --//
			    }else if($field == $lastField){
			        if($dir && $dir == 'ASC'){ $dir = 'DESC'; }else{ $dir = 'ASC'; }
			    }else{
			        $dir = 'ASC';
			    }
				//-- Build out fields --//
				$lastField = '<input type="hidden" name="lastField" value="'.$field.'" />';
				$inputs = '<input type="hidden" name="field" value="'.$field.'" />';
				if($dir){
					$inputs.= '<input type="hidden" name="dir" value="'.$dir.'" />';
					$lastField.= '<input type="hidden" name="dir" value="'.$dir.'" />';
				}
			}else{
			    $inputs = '<input type="hidden" name="field" value="firstname" /><input type="hidden" name="dir" value="ASC" />';
				$lastField = '<input type="hidden" name="lastField" value="firstname" /><input type="hidden" name="dir" value="ASC" />';
			}
			
		}else{
		    $inputs = '<input type="hidden" name="field" value="firstname" /><input type="hidden" name="dir" value="ASC" />';
			$lastField = '<input type="hidden" name="lastField" value="firstname" /><input type="hidden" name="dir" value="ASC" />';
		}
		
		//-- Set Vars to Defaults - if not present --//
		if(!isset($limit) || !$limit){ $limit = 10; }
		if(!isset($page) || !$page){
			$page = 1;
			$offset = 0;
		}else{
			//-- set offset based on page number --//
			$offset = ($page - 1) * $limit;
		}
		if(!isset($field) || !$field){ $field = 'firstname'; }
		if(!isset($dir) || !$dir){ $dir = 'ASC'; }
		if(!isset($activeID) || (isset($activeID) && $activeID != 0)){ $activeID = 1; }
		
		/*-----------------------
			Figure out Filters
		------------------------*/
		//-- Force filters for non admin users --//
		if($this->session->get("user-school")){
			$schoolID = $this->session->get("user-school");
			$schoolFilter = '';
		}else{
			if(isset($schoolID) && $schoolID){ $schoolFilter = '<input type="hidden" name="filterSchool" value="'.$schoolID.'" />'; }else{ $schoolFilter = ''; }
		}
		if(isset($gradeID) && $gradeID != ''){ $gradeFilter = '<input type="hidden" name="filterGrade" value="'.$gradeID.'" />'; }else{ $gradeFilter = ''; }
		if(isset($teacherID) && $teacherID){ $teacherFilter = '<input type="hidden" name="filterTeacher" value="'.$teacherID.'" />'; }else{ $teacherFilter = ''; }
		if(isset($coachID) && $coachID){ $coachFilter = '<input type="hidden" name="filterCoach" value="'.$coachID.'" />'; }else{ $coachFilter = ''; }
		if(isset($searchTerm) && $searchTerm){ $searchFilter = '<input type="hidden" name="filterSearch" value="'.$searchTerm.'" />'; }else{ $searchFilter = ''; }
		if(isset($activeID) && $activeID == 0){ $activeFilter = '<input type="hidden" name="filterActive" value="'.$activeID.'" />'; }else{ $activeFilter = ''; }
		$limitFilter = '<input type="hidden" name="limit" value="'.$limit.'" />';
		$filters = $limitFilter.$schoolFilter.$gradeFilter.$teacherFilter.$coachFilter.$activeFilter.$searchFilter;
		
		//-- Add Filter inputs --//
		$lastField.= $filters;
		
		//-- Map to real column names --//
		if($field == 'firstname'){ $column = 'fname'; }
		else if($field == 'lastname'){ $column = 'lname'; }
		else if($field == 'teacher'){ $column = 'teacher'; }
		else if($field == 'grade_level'){ $column = 'grade'; }
		else if($field == 'school'){ $column = 'school'; }
		else if($field == 'coach'){ $column = 'coach'; }
		else{ $column = 'fname'; }
		
		//-- Grab Schools --//
		$school_abbreviations = $schoolList = array();
		if($this->session->get("user-district")){
			$schools = Schools::find(array("district = :dist:", "order" => "state ASC, schoolName ASC, city ASC", "bind" => array("dist" => $this->session->get("user-district"))));
		}else{
			$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		}
		if(!empty($schools)){
			foreach($schools as $school_item){
				$school_abbreviations[$school_item->id] = $school_item->abbreviation;
				$schoolList[] = $school_item->id;
			}
		}
		
		//-- Figure out filter conditions --//
		$conditions = "";
		if(isset($schoolID) && $schoolID){
			if($conditions == ''){
				$conditions.= "school = ".$schoolID;
			}else{
				$conditions.= " AND school = ".$schoolID;
			}
		}else if($this->session->get("user-district") && !empty($schoolList)){
			if($conditions == ''){
				$conditions.= "school IN (".implode(', ', $schoolList).")";
			}else{
				$conditions.= " AND school IN (".implode(', ', $schoolList).")";
			}
		}
		if(isset($gradeID) && $gradeID != ''){
			if($conditions == ''){
				$conditions.= "grade = ".$gradeID;
			}else{
				$conditions.= " AND grade = ".$gradeID;
			}
		}
		if(isset($teacherID) && $teacherID){
			if($conditions == ''){
				$conditions.= "teacher = ".$teacherID;
			}else{
				$conditions.= " AND teacher = ".$teacherID;
			}
		}
		if(isset($coachID) && $coachID){
			if($conditions == ''){
				$conditions.= "coach = ".$coachID;
			}else{
				$conditions.= " AND coach = ".$coachID;
			}
		}
		if(isset($activeID)){
			if($conditions == ''){
				$conditions.= "active = ".$activeID;
			}else{
				$conditions.= " AND active = ".$activeID;
			}
		}
		//-- Student Search --//
		if(isset($searchTerm) && !empty($searchTerm)){
			//-- get rid of any blank values --//
			$names = array();
			$search = explode(' ', $searchTerm);
			foreach($search as $item){
				if(!empty($item)){
					$names[] = $item;
				}
			}
			//-- Setup Name Conditions --//
			$search_conditions = "";
			switch(count($names)){
				case 0:
					break;
				case 1:
					$search_conditions.= "(fname = '".$names[0]."' OR fname LIKE '".$names[0]."%' OR lname = '".$names[0]."' OR lname LIKE '".$names[0]."%')";
					break;
				default:
			        $search_conditions.= "(((fname = '".$names[0]."' OR fname LIKE '".$names[0]."%') AND (lname = '".$names[1]."' OR lname LIKE '".$names[1]."%')) OR (fname = '".$names[0]." ".$names[1]."' OR fname LIKE '".$names[0]." ".$names[1]."%' OR lname = '".$names[0]." ".$names[1]."' OR lname LIKE '".$names[0]." ".$names[1]."%'))";
			}
			//-- Add to Normal Conditions --//
			if($conditions == ''){
				$conditions.= $search_conditions;
			}else{
				$conditions.= " AND ".$search_conditions;
			}
		}
		//-- Grab Students Object --//
		$students = Students::find(array($conditions, "order" => $column." ".$dir, "limit" => array("number" => $limit, "offset" => $offset)));
		$totalStudents = Students::count(array($conditions, "order" => $column." ".$dir));
		
		//-- Grab Grade Levels --//
		$grade_level = GradeLevel::find(array("order" => "id ASC"));
		//-- Grab Teachers / filtered by school & Grade if those filters are set --//
		$teachConditions = "role = 8";
		$coachConditions = "(role = 5 OR role = 6)";
		if(isset($schoolID) && $schoolID){
			$teachConditions.= " AND school = ".$schoolID;
			$coachConditions.= " AND school = ".$schoolID;
		}else if($this->session->get("user-district")){
			$teachConditions.= " AND district = ".$this->session->get("user-district");
			$coachConditions.= " AND district = ".$this->session->get("user-district");
		}
		$teacherFilteredList = Users::find(array($teachConditions, "order" => "lname ASC"));
		$teacherFilterList = array();
		if(isset($gradeID) && $gradeID != ''){
			foreach($teacherFilteredList as $teachFL){
				$gradeLim = $teachFL->getGradeLimit();
				if(isset($gradeLim) && $gradeLim){
					$grades_allowed = explode(',', $gradeLim->grades);
					unset($gradeLim);
					if(in_array($gradeID, $grades_allowed)){
						$teacherFilterList[] = $teachFL;
					}
				}
			}
		}else{
			$teacherFilterList = $teacherFilteredList;
		}
		
		//-- Grab List of All Teachers --//
		if(isset($schoolID) && $schoolID){
			$teacherList = Users::find(array("role = 8 AND school = ".$schoolID, "order" => "lname ASC"));
		}else{
			$teacherList = Users::find(array("role = 8", "order" => "lname ASC"));
		}
		
		//-- Create Teacher Reference Array - from teacherList --//
		if(isset($teacherList) && $teacherList){
			$teacherRef = array();
			foreach($teacherList as $tl){
				$teacherRef[$tl->id] = $tl->lname.', '.$tl->fname;
			}
		}
		
		//-- Coach Lists --//
		$coachFilterList = Users::find(array($coachConditions, "order" => "lname ASC"));
		
		//-- Grab List of All Coaches --//
		if(isset($schoolID) && $schoolID){
			$coachList = Users::find(array("(role = 5 OR role = 6) AND school = ".$schoolID, "order" => "lname ASC"));
		}else if($this->session->get("user-district")){
			$coachList = Users::find(array("(role = 5 OR role = 6) AND district = :dist:", "order" => "lname ASC", "bind" => array("dist" => $this->session->get("user-district"))));
		}else{
			$coachList = Users::find(array("role = 5 OR role = 6", "order" => "lname ASC"));
		}
		
		//-- Create Teacher Reference Array - from teacherList --//
		if(isset($coachList) && $coachList){
			$coachRef = array();
			foreach($coachList as $cl){
				$coachRef[$cl->id] = $cl->lname.', '.$cl->fname;
			}
		}
		
		//-- Pass Objects / Vars to View --//
		//$this->view->setVar("output", $output);
		$this->view->setVar("cap", $this->cap);
        $this->view->setVar("students", $students);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("grade_level", $grade_level);
		$this->view->setVar("teacherList", $teacherFilterList);
		if(isset($teacherRef) && $teacherRef){ $this->view->setVar("teacherRef", $teacherRef); }
		$this->view->setVar("coachList", $coachFilterList);
		if(isset($coachRef) && $coachRef){ $this->view->setVar("coachRef", $coachRef); }
		$this->view->setVar("lastField", $lastField);
		$this->view->setVar("inputs", $inputs);
		$this->view->setVar("filters", $filters);
		$this->view->setVar("field", $field);
		$this->view->setVar("dir", strtolower($dir));
		$this->view->setVar("limit", $limit);
		$this->view->setVar("pageNum", $page);
		$this->view->setVar("totalStudents", $totalStudents);
		$this->view->setVar("limitFilter", $limitFilter);
		$this->view->setVar("schoolFilter", $schoolFilter);
		if(isset($schoolID) && $schoolID){ $this->view->setVar("schoolID", $schoolID); }
		if(isset($school_abbreviations)){ $this->view->setVar("school_abbreviations", $school_abbreviations); }
		$this->view->setVar("gradeFilter", $gradeFilter);
		if(isset($gradeID) && $gradeID != ''){ $this->view->setVar("gradeID", $gradeID); }
		$this->view->setVar("teacherFilter", $teacherFilter);
		if(isset($teacherID) && $teacherID){ $this->view->setVar("teacherID", $teacherID); }
		$this->view->setVar("coachFilter", $coachFilter);
		if(isset($coachID) && $coachID){ $this->view->setVar("coachID", $coachID); }
		$this->view->setVar("searchFilter", $searchFilter);
		if(isset($searchTerm) && $searchTerm){ $this->view->setVar("searchTerm", $searchTerm); }
		$this->view->setVar("activeFilter", $activeFilter);
		if(isset($activeID)){ $this->view->setVar("activeID", $activeID); }
    } //-- end indexAction() --//
	
	
	public function filteredteachersAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Username --//
			if($this->request->getPost("action") == 'grab_teachers'){
				
				//-- Sanitize Vars --//
				$schoolID = $this->request->getPost("school", "int");
				$gradeID = $this->request->getPost("grade", "int");
				$results = array();
				
				//-- Grab Teachers / filtered by school & Grade if those filters are set --//
				$teachConditions = "role = 8";
				if(isset($schoolID) && $schoolID){
					$teachConditions.= " AND school = ".$schoolID;
				}
				$teacherFilterList = Users::find(array($teachConditions, "order" => "lname ASC"));
				$teacherList = array();
				if(isset($gradeID) && $gradeID != ''){
					foreach($teacherFilterList as $teachFL){
						$gradeLim = $teachFL->getGradeLimit();
						if($gradeLim){
							$grades_allowed = explode(',', $gradeLim->grades);
							unset($gradeLim);
							if(in_array($gradeID, $grades_allowed)){
								$teacherList[] = $teachFL;
							}
						}
					}
				}else{
					$teacherList = $teacherFilterList;
				}
				
				//-- Create Teacher Reference Array - from teacherList --//
				if(isset($teacherList)){
					$results['result'] = "success";
					foreach($teacherList as $tl){
						$results['teachers'][] = array('id' => $tl->id, 'name' => $tl->lname.', '.$tl->fname);
					}
				}else{
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end filteredteachersAction(); --//
	
	
	public function teachlistAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Username --//
			if($this->request->getPost("action") == 'grab_teacher_list'){
				
				$results = array();
				
				//-- Grab Teachers / filtered by school if is character coach --//
				$teachConditions = "role = 8";
				if($this->session->get("user-school")){
					$teachConditions.= " AND school = ".$this->session->get("user-school");
				}
				$teacherList = Users::find(array($teachConditions, "order" => "lname ASC"));
				if($teacherList){
					$results['result'] = "success";
					foreach($teacherList as $tl){
						$gradeLim = $tl->getGradeLimit();
						if($gradeLim){
							$grades_taught = explode(',', $gradeLim->grades);
							unset($gradeLim);
							$results['teachers'][] = array('id' => $tl->id, 'last' => $tl->lname, 'first' => $tl->fname, 'school' => $tl->school, 'grades' => $grades_taught);
						}
					}
				}else{
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end teachlistAction(); --//
	
	
	public function coachlistAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Username --//
			if($this->request->getPost("action") == 'grab_coach_list'){
				
				$results = array();
				
				//-- Grab Teachers / filtered by school if is character coach --//
				$coachConditions = "role = 5 OR role = 6";
				if($this->session->get("user-school")){
					$coachConditions.= " AND school = ".$this->session->get("user-school");
				}
				$coachList = Users::find(array($coachConditions, "order" => "lname ASC"));
				if($coachList){
					$results['result'] = "success";
					foreach($coachList as $cl){
						$school = Schools::findFirst(array("id = :school:", "bind" => array("school" => $cl->school)));
						$results['coaches'][] = array('alt_id' => $cl->alt_id, 'last' => $cl->lname, 'first' => $cl->fname, 'school' => $cl->school, 'schoolname' => $school->abbreviation);
					}
				}else{
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end coachlistAction(); --//
	
	
	public function addstudentAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add Student --//
			if($this->request->getPost("action") == 'add_student'){
				//-- Sanitize Vars --//
				$alt_id = $this->request->getPost("altid", "string");
				$state_id = $this->request->getPost("stateid", "string");
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$email = $this->request->getPost("email", "email");
				$school = $this->request->getPost("school", "int");
				$grade = $this->request->getPost("grade", "int");
				$teacher = $this->request->getPost("teacher", "int");
				$coach = $this->request->getPost("coach", "int");
				$pass = $this->request->getPost("pass", "string");
				$send_email = $this->request->getPost("send_email", "int");
				$results = $schoolList = array();
				
				//-- Verify Permissions --//
				if($this->cap['students']['add']){
					
					//-- truncate vars to certain length --//
					if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
					if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
					if($school && strlen($school) > 5){ $school = substr($school, 0, 5); }
					if($grade && strlen($grade) > 2){ $grade = substr($grade, 0, 2); }
					if($teacher && strlen($teacher) > 10){ $teacher = substr($teacher, 0, 10); }
					if($coach && strlen($coach) > 10){ $coach = substr($coach, 0, 10); }
					
					//-- Make sure the required info is present --//
					if($fname && $lname){
						if($school && $grade != ''){
							if((!empty($email) && $pass) || empty($email)){
								//-- Check to see if email is taken by student or user --//
								$checkEmail = Students::count(array("email = :email:", "bind" => array("email" => $email)));
								$checkEmail2 = Users::count(array("email = :email:", "bind" => array("email" => $email)));
								if((!$checkEmail && !$checkEmail2) || empty($email)){
									
									//-- Figure out District --//
									$theSchool = Schools::findFirst(array(
										"conditions" => "id = :sch:",
										"bind" => array("sch" => $school)
									));
									$district = $theSchool->district;
				
									//-- Find list of schools --//
									$campuses = Schools::find(array(
										"conditions" => "district = :Dist:",
										"bind" => array("Dist" => $district)
									));
									foreach($campuses as $campus){
										$schoolList[] = $campus->id;
									}
									
									//-- Check Student's Alt ID is unique for their district --//
									if(!empty($alt_id)){
										$checkAlt = Students::count(array(
											"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." )",
											"bind" => array("altID" => $alt_id)
										));
									}else{
										$checkAlt = false;
									}
									//-- if AltID is empty or else is unique in the district --//
									if(!$checkAlt){
										
										/*---------------------------------------------
											Now Add Student -- Passed all validation
										----------------------------------------------*/
										$student = New Students();
										$student->fname = $fname;
										$student->lname = $lname;
										$student->school = $school;
										$student->grade = $grade;
										if(!empty($teacher)){
											$student->teacher = $teacher;
										}else{
											$student->teacher = 0;
										} //--  coach --//
										if(!empty($coach)){
											$student->coach = intval($coach);
										}else{
											$student->coach = NULL;
										} //-- Alt ID --//
										if(!empty($alt_id)){
											$student->alt_id = $alt_id;
										}else{
											$student->alt_id = NULL;
										} //-- State ID --//
										if(!empty($state_id)){
											$student->state_id = $state_id;
										}else{
											$student->state_id = NULL;
										}
										//-- add password / email - when its provided --//
										if(!empty($email) && !empty($pass)){
											$student->email = $email;
											$student->pass = $this->security->hash($pass);
										}
										$student->active = 1;
										$student->turf_period = 0;
										$student->date_added = time();

										//-- Save Entry --//
										if($student->save() == false){
											$results["result"] = "failed";
											$results["error_title"] = "Failed to Add Student";
											$results["error_msg"] = "Something went wrong, and the student was not added.";
										}else{
											$results["result"] = "success";
											if($send_email && !empty($email) && !empty($pass)){
												//-- Setup Mailgun Object --//
												$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

												//-- Send Mail Message --//
												$to = $email;
												$subject = "Welcome to Athlos Tools";
												$message = "You have just been added to the Athlos Tools Admin. You can now login and have access to resources and grading by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nEmail: ".$email."\nPassword: ".$pass."\n\nFeel free to reply to this email if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
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
								$results["error_title"] = "Password Needed";
								$results["error_msg"] = "A password is required when adding an email address to a student. Thus giving them a login and access to Athlos Tools.";
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Missing Information";
							$results["error_msg"] = "Make sure student has a school, grade level and teacher assigned.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Something Is Missing";
						$results["error_msg"] = "Make sure student has a first and last name entered.";
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
		
	} //-- end addstudentAction() --//

	
	public function editstudentAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab User --//
			if($this->request->getPost("action") == 'grab_student'){
				
				//-- grab / set vars --//
				$studentID = $this->request->getPost("theID", "int");
				$results = array();

				if($studentID && is_numeric($studentID)){

					//-- Grab Student --//
					$student = Students::findFirst(array(
						"conditions" => "id = :userID:",
						"bind" => array('userID' => $studentID)
						));

					//-- the student json object --//
					$results["result"] = "success";
					$results["id"] = $student->id;
					$results["alt_id"] = $student->alt_id;
					$results["state_id"] = $student->state_id;
					$results["first"] = $student->fname;
					$results["last"] = $student->lname;
					$results["email"] = $student->email;
					$results["school"] = $student->school;
					$results["grade"] = $student->grade;
					$results["teacher"] = $student->teacher;
					$results["coach"] = $student->coach;
					
					//-- Grab Teachers / filtered by school & Grade if those filters are set --//
					$teachConditions = "role = 8 AND school = ".$student->school;
					$teacherFilterList = Users::find(array($teachConditions, "order" => "lname ASC"));
					$teacherList = array();
					if($teacherFilterList){
						foreach($teacherFilterList as $teachFL){
							$gradeLim = $teachFL->getGradeLimit();
							if($gradeLim){
								$grades_allowed = explode(',', $gradeLim->grades);
								unset($gradeLim);
								if(in_array($student->grade, $grades_allowed)){
									$teacherList[] = $teachFL;
								}
							}
						}
					}

					//-- Create Teacher Reference Array - from teacherList --//
					if(isset($teacherList) && $teacherList){
						foreach($teacherList as $tl){
							$results['teachers'][] = array('id' => $tl->id, 'name' => $tl->lname.', '.$tl->fname);
						}
					}
				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Student --//
			
			//-- Function to Edit Student --//
			if($this->request->getPost("action") == 'edit_student'){
				
				//-- Sanitize Vars --//
				$studentID = $this->request->getPost("student_id", "int");
				$alt_id = $this->request->getPost("altid", "string");
				$state_id = $this->request->getPost("stateid", "string");
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$email = $this->request->getPost("email", "email");
				$school = $this->request->getPost("school", "int");
				$grade = $this->request->getPost("grade", "int");
				$teacher = $this->request->getPost("teacher", "int");
				$coach = $this->request->getPost("coach", "int");
				$pass = $this->request->getPost("pass", "string");
				$send_email = $this->request->getPost("send_email", "int");
				$results = $schoolList = array();
				
				//-- Verify Permissions --//
				if($this->cap['students']['edit']){
					//-- truncate vars to certain length --//
					if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
					if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
					if($school && strlen($school) > 5){ $school = substr($school, 0, 5); }
					if($grade && strlen($grade) > 2){ $grade = substr($grade, 0, 2); }
					if($teacher && strlen($teacher) > 10){ $teacher = substr($teacher, 0, 10); }
					if($coach && strlen($coach) > 10){ $coach = substr($coach, 0, 10); }
					
					//-- Make sure the required info is present --//
					if($fname && $lname){
						if($school && $grade != ''){
							
							//-- Check to see if email is taken --//
							$checkEmail = Students::count(array("email = :email: AND id != :id:", "bind" => array("email" => $email, "id" => $studentID)));
							$checkEmail2 = Users::count(array("email = :email:", "bind" => array("email" => $email)));
							if((!$checkEmail && !$checkEmail2) || empty($email)){
								//-- Figure out District --//
								$theSchool = Schools::findFirst(array(
									"conditions" => "id = :sch:",
									"bind" => array("sch" => $school)
								));
								$district = $theSchool->district;
			
								//-- Find list of schools --//
								$campuses = Schools::find(array(
									"conditions" => "district = :Dist:",
									"bind" => array("Dist" => $district)
								));
								foreach($campuses as $campus){
									$schoolList[] = $campus->id;
								}
								
								//-- Check Student's Alt ID is unique for their district --//
								if(!empty($alt_id)){
									$checkAlt = Students::count(array(
										"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." ) AND id != :id:",
										"bind" => array("altID" => $alt_id, "id" => $studentID)
									));
								}else{
									$checkAlt = false;
								}
								//-- if AltID is empty or else is unique in the district --//
								if(!$checkAlt){
									/*------------------------
										Grab Student Object
									-------------------------*/
									$student = Students::findFirst(array(
										"conditions" => "id = :userID:",
										"bind" => array('userID' => $studentID)
										));
								
									if((!empty($email) && ($pass || !empty($student->pass))) || empty($email)){

										/*------------------------------------------------
											Now Update Student -- Passed all validation
										-------------------------------------------------*/
										$student->fname = $fname;
										$student->lname = $lname;
										$student->school = $school;
										$student->grade = $grade;
										if(!empty($teacher)){
											$student->teacher = $teacher;
										}else{
											$student->teacher = 0;
										}
										if(!empty($coach)){
											$student->coach = intval($coach);
										}else{
											$student->coach = NULL;
										} //-- Alt ID --//
										if(!empty($alt_id)){
											$student->alt_id = $alt_id;
										}else{
											$student->alt_id = NULL;
										} //-- State ID --//
										if(!empty($state_id)){
											$student->state_id = $state_id;
										}else{
											$student->state_id = NULL;
										}
										//-- make sure password is provided as well with the email --//
										if(!empty($email)){
											if(!empty($pass)){
												$student->email = $email;
												$student->pass = $this->security->hash($pass);
											}else if(!empty($student->pass)){
												$student->email = $email;
											}
										}else{
											$student->email = NULL;
											$student->pass = NULL;
										}

										//-- Save Entry --//
										if($student->save() == false){
											$results["result"] = "failed";
											$results["error_title"] = "Failed to Update User";
											$results["error_msg"] = "Something went wrong, and the user was not updated.";
										}else{
											$results["result"] = "success";

											//-- send message if pwd or username was changed or updated --//
											if($send_email && !empty($pass) && !empty($email)){
												//-- Setup Mailgun Object --//
												$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

												//-- if username is blank give email for the login info --//
												$logcreds = "Email: ".$email;
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
										$results["error_title"] = "Password Needed";
										$results["error_msg"] = "A password is required when adding an email address to a student. Thus giving them a login and access to Athlos Tools.";
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
							$results["error_title"] = "Missing Information";
							$results["error_msg"] = "Make sure student has a school, grade level and teacher assigned.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Something Is Missing";
						$results["error_msg"] = "Make sure student has a first and last name entered.";
					}
					
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}
				
				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Edit_Student --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end edituserAction() --//
	
	
	public function delstudentAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'delete_student'){
				
				//-- grab / set / sanitize vars --//
				$studentID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['students']['delete']){

					if($studentID && is_numeric($studentID)){

						//-- Grab User --//
						$student = Students::findFirst(array(
							"conditions" => "id = :userID:",
							"bind" => array('userID' => $studentID)
							));
							
						if($student){
							//-- Delete from DB --//
							if($student->delete() == false){
							    $results['result'] = "failed";
								$results["error_title"] = "Failed to Delete Student";
								$results["error_msg"] = "Something went wrong, and the student was not deleted.";
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
		
	} //-- end delstudentAction() --//
	
	
	public function enrollAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Un-Enroll Student --//
			if($this->request->getPost("action") == 'enroll_student'){
				
				//-- grab / set / sanitize vars --//
				$results = array();
				$sidList = explode(',', $this->request->getPost("idlist"));
				if(!empty($sidList)){
					foreach($sidList as $key => $val){
						$sidList[$key] = $this->filter->sanitize($val, 'int');
					}
				}
				
				//-- Verify Permissions --//
				if($this->cap['students']['edit']){

					if(!empty($sidList)){
						$confirmCount = 0;
						foreach($sidList as $studentID){
							//-- Grab User --//
							$student = Students::findFirst(array(
								"conditions" => "id = :userID:",
								"bind" => array('userID' => $studentID)
								));
								
							if($student){
								//-- Set to Enrolled --//
								$student->active = 1;
								//-- Save Student --//
								if($student->save() == false){
								    $results['result'] = "failed";
									$results["error_title"] = "Failed to Enroll";
									$results["error_msg"] = "Something went wrong during enrollment. Only ".$confirmCount." / ".count($sidList)." Students Were Enrolled.";
									break;
								}else{
									$confirmCount++;
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Invalid Data Was Submitted";
								$results["error_msg"] = "Something went wrong during enrollment. Only ".$confirmCount." / ".count($sidList)." Students Were Enrolled.";
								break;
							}
						}
						
						//-- Determine Success --//
						if(!isset($results['result'])){
							$results['result'] = "success";
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
		
	} //-- end enrollAction() --//
	
	
	public function unenrollAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Un-Enroll Student --//
			if($this->request->getPost("action") == 'unenroll_student'){
				
				//-- grab / set / sanitize vars --//
				$results = array();
				$sidList = explode(',', $this->request->getPost("idlist"));
				if(!empty($sidList)){
					foreach($sidList as $key => $val){
						$sidList[$key] = $this->filter->sanitize($val, 'int');
					}
				}
				
				//-- Verify Permissions --//
				if($this->cap['students']['edit']){

					if(!empty($sidList)){
						$confirmCount = 0;
						foreach($sidList as $studentID){
							//-- Grab User --//
							$student = Students::findFirst(array(
								"conditions" => "id = :userID:",
								"bind" => array('userID' => $studentID)
								));
								
							if($student){
								//-- Set to Un-Enrolled --//
								$student->active = 0;
								//-- Save Student --//
								if($student->save() == false){
								    $results['result'] = "failed";
									$results["error_title"] = "Un-Enrollment Failed";
									$results["error_msg"] = "Something went wrong during un-enrollment. ".$confirmCount." / ".count($sidList)." Students Were Un-Enrolled.";
									break;
								}else{
									$confirmCount++;
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Invalid Data Was Submitted";
								$results["error_msg"] = "Something went wrong during un-enrollment. ".$confirmCount." / ".count($sidList)." Students Were Un-Enrolled.";
								break;
							}
						}
						
						//-- Determine Success --//
						if(!isset($results['result'])){
							$results['result'] = "success";
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
		
	} //-- end unenrollAction() --//
	

	public function unassigncoachAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Un-Assign Coach --//
			if($this->request->getPost("action") == 'unassign_coach'){
				
				//-- grab / set / sanitize vars --//
				$results = array();
				$sidList = explode(',', $this->request->getPost("idlist"));
				if(!empty($sidList)){
					foreach($sidList as $key => $val){
						$sidList[$key] = $this->filter->sanitize($val, 'int');
					}
				}
				
				//-- Verify Permissions --//
				if($this->cap['students']['edit']){

					if(!empty($sidList)){
						$confirmCount = 0;
						foreach($sidList as $studentID){
							//-- Grab Student --//
							$student = Students::findFirst(array(
								"conditions" => "id = :userID:",
								"bind" => array('userID' => $studentID)
								));
								
							if($student){
								//-- Unassign the Coach --//
								$student->coach = null;
								//-- Save Student --//
								if($student->save() == false){
								    $results['result'] = "failed";
									$results["error_title"] = "Coach Removal Failed";
									$results["error_msg"] = "Something went wrong during unassigning the coach. ".$confirmCount." / ".count($sidList)." Students Were Unassigned.";
									break;
								}else{
									$confirmCount++;
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Invalid Data Was Submitted";
								$results["error_msg"] = "Something went wrong during unassigning the coach. ".$confirmCount." / ".count($sidList)." Students Were Unassigned.";
								break;
							}
						}
						
						//-- Determine Success --//
						if(!isset($results['result'])){
							$results['result'] = "success";
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
		
	} //-- end unassigncoachAction() --//

	
	public function assignturfAction()
	{
		 //-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Un-Enroll Student --//
			if($this->request->getPost("action") == 'assign_turf_period'){

				//-- grab / set / sanitize vars --//
				$results = array();
				$period = $this->request->getPost("period", "int");
				$sidList = explode(',', $this->request->getPost("idlist"));
				if(!empty($sidList)){
					foreach($sidList as $key => $val){
						$sidList[$key] = $this->filter->sanitize($val, 'int');
					}
				}

				//-- Verify Permissions --//
				if($this->cap['students']['edit']){

					if(!empty($sidList) && $period){
						$confirmCount = 0;
						foreach($sidList as $studentID){
							//-- Grab User --//
							$student = Students::findFirst(array(
								"conditions" => "id = :userID:",
								"bind" => array('userID' => $studentID)
								));

							if($student){
								//-- Assign Turf Period --//
								$student->turf_period = $period;
								
								//-- Save Student --//
								if($student->save() == false){
								    $results['result'] = "failed";
									$results["error_title"] = "Failed to Assign Period";
									$results["error_msg"] = "Something went wrong during submission ".$confirmCount." / ".count($sidList)." Students Were Assigned to a Period.";
									break;
								}else{
									$confirmCount++;
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Failed to Assign Period";
								$results["error_msg"] = "Something went wrong during submission ".$confirmCount." / ".count($sidList)." Students Were Assigned to a Period.";
								break;
							}
						}

						//-- Determine Success --//
						if(!isset($results['result'])){
							$results['result'] = "success";
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
		
	} //-- end assignturfAction() --//
	
	
	public function assignparentsAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Verify Permissions --//
			if($this->cap['students']['edit']){
				/*--------------------------------------------
					Function to Grab Parents / Suggestions
				---------------------------------------------*/
				if($this->request->getPost("action") == 'grab_parents'){

					//-- grab / set vars --//
					$studentID = $this->request->getPost("theID", "int");
					$results = $parent_ids = array();

					if($studentID && is_numeric($studentID)){

						//-- Grab Student --//
						$student = Students::findFirst(array(
							"conditions" => "id = :userID:",
							"bind" => array('userID' => $studentID)
							));

						//-- Grab all parents --//
						foreach($student->parentRelationship as $rel){
							$parent = $rel->getUsers();
							$parent_ids[] = $parent->id;
						    $results['parents'][] = array("id" => $rel->id, "first" => $parent->fname, "last" => $parent->lname, "email" => $parent->email, "valid" => $rel->verified);
						}

						//-- Grab parent suggestions --//
						$suggestions = Users::find(array("role = 9 AND lname = '".$student->lname."'", "order" => "fname ASC"));
						if($suggestions){
							foreach($suggestions as $sugg){
								if(!in_array($sugg->id, $parent_ids)){
									$results['suggestions'][] = array("id" => $sugg->id, "first" => $sugg->fname, "last" => $sugg->lname, "email" => $sugg->email);
								}
							}
						}

						//-- Finish JSON object --//
						$results["result"] = "success";
						$results["id"] = $student->id;

					}else{
						//invalid input
						$results["result"] = "failed";
					}

					//-- encode results --//
					echo json_encode($results);

				} //-- end Grab_Parents --//

				/*------------------------------
					Function to Remove Parent
				-------------------------------*/
				if($this->request->getPost("action") == 'remove_parent'){

					//-- grab / set vars --//
					$relID = $this->request->getPost("theID", "int");
					$results = array();

					if($relID && is_numeric($relID)){

						//-- Grab Parent Relationship --//
						$rel = ParentRelationship::findFirst(array(
							"conditions" => "id = :relID:",
							"bind" => array('relID' => $relID)
							));

						if($rel != false){
						    if($rel->delete() == false){
						        $results["result"] = "failed";
						    }else{
						        $results["result"] = "success";
						    }
						}else{
							$results["result"] = "failed";
						}

					}else{
						//invalid input
						$results["result"] = "failed";
					}

					//-- encode results --//
					echo json_encode($results);

				} //-- end Remove_Parents --//

				/*--------------------------------
					Function to Assign a Parent
				---------------------------------*/
				if($this->request->getPost("action") == 'assign_parent'){

					//-- grab / set vars --//
					$parentID = $this->request->getPost("parentID", "int");
					$studentID = $this->request->getPost("studentID", "int");
					$results = array();

					if($parentID && is_numeric($parentID) && $studentID && is_numeric($studentID)){

						//-- New Parent Relationship --//
						$rel = new ParentRelationship();
						$rel->verified = 0;
						$rel->parent = $parentID;
						$rel->student = $studentID;

						if($rel->save() == false){
							$results["result"] = "failed";
							/*foreach($rel->getMessages() as $message){
								$results["reason"][] = $message->getMessage();
							}*/
						}else{
							$results["result"] = "success";
							$results["relID"] = $rel->id;
							
							//-- Grab Parent Object --//
							$parent = $rel->getUsers();
							
							//-- Setup Mailgun Object --//
							$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

							//-- Send Mail Message --//
							$to = $parent->email;
							$subject = "Student Assigned to You";
							$message = "You have just been assigned as a parent or guardian of an Athlos Student. Please login and verify that the student assigned to you is in fact under your guardianship. You can login and verify your relationship to your student by going to this url: https://".$_SERVER['HTTP_HOST']." and logging in with your credentials.\n\nFeel free to contact your school if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
							//-- Send MSG with Mailgun --//
							$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
							                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
							                        'to'      => $to,
							                        'subject' => $subject,
							                        'text'    => $message));
						}

					}else{
						//invalid input
						$results["result"] = "failed";
					}

					//-- encode results --//
					echo json_encode($results);

				} //-- end Assign_Parent --//
				
				/*--------------------------------
					Function to Search Parents
				---------------------------------*/
				if($this->request->getPost("action") == 'search_parents'){

					//-- grab / set vars --//
					$searchVal = trim($this->request->getPost("theVal", "string"));
					$studentID = $this->request->getPost("studentID", "int");
					$results = $parent_ids = $bindArray = array();
					
					//-- truncate value --//
					if(strlen($searchVal) > 81){ $searchVal = substr($searchVal, 0, 81); }
					
					if($searchVal && $studentID && is_numeric($studentID)){
						
						//-- Grab Student & parents to make sure parents don't show up in search results --//
						$student = Students::findFirst(array(
							"conditions" => "id = :userID:",
							"bind" => array('userID' => $studentID)
							));
						//-- Grab all parents --//
						foreach($student->parentRelationship as $rel){
							$parent = $rel->getUsers();
							$parent_ids[] = $parent->id;
						}
						
						//-- Determine what is being searched for (name / email) --//
						$conditions = "role = 9";
						if(strpos($searchVal, ' ')){
							//-- first & last name --//
							$fname = substr($searchVal, 0, strpos($searchVal, ' '));
							$lname = substr($searchVal, strpos($searchVal, ' ') + 1);
							if($fname){
								$conditions.= " AND (fname = :fname: OR fname LIKE :fname2:)";
								$bindArray['fname'] = $fname;
								$bindArray['fname2'] = $fname.'%';
							}
							if($lname){
								$conditions.= " AND (lname = :lname: OR lname LIKE :lname2:)";
								$bindArray['lname'] = $lname;
								$bindArray['lname2'] = $lname.'%';
							}
						}else if(strpos($searchVal, '@')){
							//-- email address --//
							$conditions.= " AND (email = :email: OR email LIKE :email2:)";
							$bindArray['email'] = $searchVal;
							$bindArray['email2'] = $searchVal.'%';
						}else{
							//-- either / both --//
							$conditions.= " AND (fname = :search: OR fname LIKE :search2: OR lname = :search: OR lname LIKE :search2: OR email = :search: OR email LIKE :search2:)";
							$bindArray['search'] = $searchVal;
							$bindArray['search2'] = $searchVal.'%';
						}
						
						//-- Search Parents --//
						$items = Users::find(array($conditions, "order" => "fname ASC, lname ASC, email ASC", "bind" => $bindArray));
						if($items){
							foreach($items as $item){
								if(!in_array($item->id, $parent_ids)){
									$results['items'][] = array("id" => $item->id, "first" => $item->fname, "last" => $item->lname, "email" => $item->email);
								}
							}
						}
						
						$results["result"] = "success";

					}else{
						//invalid input
						$results["result"] = "failed";
					}

					//-- encode results --//
					echo json_encode($results);

				} //-- end Search_Parents --//
				
			}else{
				//-- Not Enough Permissions --//
				$results['result'] = "failed";
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end assignparentsAction() --//
	
	
	public function resourcesAction()
	{
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		//-- Priveleges - ONLY SUPER ADMINS --//
		if(!$this->cap['administration']['view']){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
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
		
		//-- Data was posted --//
		if($this->request->isPost() == true) {
			if($this->request->getPost("action") == 'upload_resource'){
				
				if(!empty($_FILES['theFile'])){
					//retreive posted file
					$fileName = $_FILES['theFile']['name'];
					$fileTempName = $_FILES['theFile']['tmp_name'];
					
					//move the file
					if($s3->putObjectFile($fileTempName, "athlos-tools-resources", $fileName, S3::ACL_PUBLIC_READ)) {
						$this->flashSession->success($preMsg."<strong>Success</strong> We successfully uploaded your file.");
					}else{
						$this->flashSession->error($preMsg."<strong>Error</strong> Something went wrong while uploading your file... sorry.");
					}
				}
			}
		}
		
		// Get the contents of our bucket
		$bucket_contents = $s3->getBucket("athlos-tools-resources");
		$this->view->setVar("bucket_contents", $bucket_contents);
		
	} //-- end resourcesAction() --//
	
	
	public function deleteresourceAction()
	{	
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
		
		//-- Data was posted --//
		if($this->request->isPost() == true){
			if($this->request->getPost("action") == 'delete_resource'){
				
				//-- Verify Permissions -- only super admins --//
				if($this->cap['administration']['manage']){
					
					//-- Sanitize Vars --//
					$filename = trim($this->request->getPost("filename", "string"));
					$results = array();
					
					//-- Make sure the required info is present --//
					if($filename){
						//-- delete the file --//
						if($s3->deleteObject("athlos-tools-resources", $filename)) {
							$results["result"] = "success";
						}else{
							$results["result"] = "failed";
						}
					}else{
						$results['result'] = "failed";
					}
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end deleteresourceAction() --//
	
	
	public function updatecodeAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Update Passcode --//
			if($this->request->getPost("action") == 'update_passcode'){
				//-- Sanitize Vars --//
				$passcode = trim($this->request->getPost("theVal", "string"));
				$results = array();

				//-- Verify Permissions -- only super admins --//
				if($this->cap['administration']['manage']){

					//-- Make sure the required info is present --//
					if($passcode){
						/*---------------------------------------------
							Now Update Passcode -- Passed validation
						----------------------------------------------*/
						$option = Options::findFirst("option = 'resources-passcode'");
						$option->value = $this->security->hash($passcode);

						//-- Save Entry --//
						if($option->save() == false){
							$results["result"] = "failed";
						}else{
							$results["result"] = "success";
						}
					}else{
						$results['result'] = "failed";
					}

				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
			}
		}

		//-- Disable View --//
		$this->view->disable();
		
	} //-- end updatecodeAction() --//
	
	
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
					case "students":
						//$columns = "id,fname,lname,email,school,grade,teacher";
						$columns = "id,alt_id,state_id,fname,lname,email,grade,coach,school";
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
				
				
				//Grab Results and Export CSV -- student
				if($table == 'students'){
					
					//-- Figure out filter conditions --//
					$conditions = "";
					if(isset($schoolID) && $schoolID){
						if($conditions == ''){
							$conditions.= "school = ".$schoolID;
						}else{
							$conditions.= " AND school = ".$schoolID;
						}
					}
					if(isset($teacherID) && $teacherID){
						if($conditions == ''){
							$conditions.= "teacher = ".$teacherID;
						}else{
							$conditions.= " AND teacher = ".$teacherID;
						}
					}
					if(isset($grade) && $grade){
						if($conditions == ''){
							$conditions.= "grade = ".$grade;
						}else{
							$conditions.= " AND grade = ".$grade;
						}
					}
					if(isset($ids) && $ids){
						if($conditions == ''){
							$conditions.= "id IN (".$ids.")";
						}else{
							$conditions.= " AND id IN (".$ids.")";
						}
					}
					if(isset($altIDsOnly) && $altIDsOnly){
						if($conditions == ''){
							$conditions.= "(alt_id IS NULL OR alt_id  = '')";
						}else{
							$conditions.= " AND (alt_id IS NULL OR alt_id  = '')";
						}
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
						if($col == 'school'){
							//-- School - show name --//
							$schools = Schools::findFirst(array("id = :sid:", "bind" => array("sid" => $row->school), "columns" => "schoolName"));
							$row->school = $schools->schoolName;
						}else if($col == 'coach'){
							if (!empty($row->coach)) {
								//-- Grab Coach's Alt ID --//
								$coach = Users::findFirst(array("id = :cid:", "bind" => array("cid" => $row->coach), "columns" => "alt_id"));
								$row->coach = (!empty($coach) && !empty($coach->alt_id)) ? $coach->alt_id : null;
							}
						}
						//-- Spit out all data --//
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
				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){
					//error_log("were in!!");
					//$page = $this->request->getPost("CSVPage");
					$currUrl = parse_url($_SERVER['REQUEST_URI']);
					$page = str_replace("/","",str_replace("csvimport","",$currUrl["path"]));
				
					switch($page){
						case "students":
							//$columns = "id,fname,lname,email,school,grade,teacher,alt_id";
							$columns = "id,alt_id,state_id,fname,lname,email,grade,coach";
							$table = "students";
							$userType = "Student(s)";
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
						
						//-- Setup Mailgun Object --//
						$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
					
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
										if($table == 'students'){
											//check if csv entry exists in the students table
											if(!empty($data[0])){
												$exists = Students::count(array("id = :id:", "bind" => array("id" => $data[0])));
											}else if(!empty($data[1])){
												if($theSchool->ilt_school){
													$exists = Students::count(array("alt_id = :alt: AND school IN( ".implode(', ', $ilt_group)." )", "bind" => array("alt" => $data[1])));
												}else{
													$exists = Students::count(array("alt_id = :alt: AND school = :school:", "bind" => array("alt" => $data[1], "school" => $school)));
												}
											}else{
												//-- Data is missing for user to exist --//
												$exists = false;
											}
											if($exists){
												//-- Grab Student's Data --//
												if(!empty($data[0])){
													$student = Students::findFirst(array(
														"conditions" => "id = :userID:",
														"bind" => array('userID' => $data[0])
														));
												}else if(!empty($data[1])){
													if($theSchool->ilt_school){
														$student = Students::findFirst(array(
															"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $ilt_group)." )",
															"bind" => array("altID" => $data[1])
															));
													}else{
														$student = Students::findFirst(array(
															"conditions" => "alt_id = :altID: AND school = :schoolID:",
															"bind" => array("altID" => $data[1], "schoolID" => $school)
															));
													}
												}
												//-- Grabbed Student Data --//
												if(!empty($student)){
													$x = 0;
													$updates = 0;
													foreach($cols AS $col){
														//-- Determine Number of fields to be Updated --//
														if($data[$x] != $student->$col){
															$updates++;
															if($col == "email"){
																$email = $this->filter->sanitize($data[$x],"email");
																if(filter_var($email, FILTER_VALIDATE_EMAIL)){
																	$data[$x] = $email;
																}else{
																	$data[$x] = NULL;
																	unset($email);
																}
															}
															if($col == "coach"){
																//-- map to the coach alt_id and if not different remove from update count --//
																if(!empty($data[$x]) && !empty($student->coach)){
																	$coach = Users::findFirst(array("id = :id:", "bind" => array("id" => $student->coach)));
																	if(!empty($coach)){
																		if($data[$x] == $coach->alt_id){
																			$updates--;
																		}
																	}
																}
															}
														}
														//-- Build out student name --//
														if($col == "fname" || $col == "lname"){
															if($col == "lname"){
																$studentName .= " ".$data[$x];
															}else{
																$studentName = $data[$x];
															}
														}
														//-- Verify that email is unused for users / students --//
														if($col == "email"){
															if(!empty($data[$x])){
																$emailExists = Students::count(array("email = :email: AND id != :id:", "bind" => array("email" => $data[$x], "id" => $student->id)));
																if($emailExists == 0){
																	$emailExists = Users::count(array("email = :email:", "bind" => array("email" => $data[$x])));
																}
																if($emailExists > 0){
																	//report which users failed to be updated
																	if(isset($results["failed"])){
																		$results["failed"] .= ",".$studentName;
																	}else{
																		$results["failed"] = $studentName;
																	}
																	$results['msg'][] = "A User with the email: ".$data[$x]." Already Exists in the System";
																}
															}else{
																$emailExists = 0;
															}
														} //-- /end-if (col = email) --//
														//-- Verify the Alt ID is unique for the current school --//
														if($col == "alt_id"){
															if(!empty($data[$x])){
																if($theSchool->ilt_school){
																	$altIdExists = Students::count(array("alt_id = :alt: AND school IN( ".implode(', ', $ilt_group)." ) AND id != :id:", "bind" => array("alt" => $data[$x], "id" => $student->id)));
																}else{
																	$altIdExists = Students::count(array("alt_id = :alt: AND school = :school: AND id != :id:", "bind" => array("alt" => $data[$x], "school" => $school, "id" => $student->id)));
																}
																if($altIdExists > 0){
																	//report which users failed to be updated
																	if(isset($results["failed"])){
																		$results["failed"] .= ",".$data[3]." ".$data[4];
																	}else{
																		$results["failed"] = $data[3]." ".$data[4];
																	}
																	$results['msg'][] = "A User with the Alt ID: ".$data[$x]." Already Exists in the System";
																}
															}else{
																$altIdExists = 0;
															}
														} //-- /end-if (col = alt_id) --//
														$x++;
													} //-- /end-foreach --//
													
													//-- If there are updates for the row -- Apply them --//
													if($updates > 0 && $emailExists == 0 && $altIdExists == 0){
														$totalUpdates += $updates;
														$x = 0;
														if($updates > 0){
															foreach($cols AS $col){
																if($x > 0){
																	//-- scrub out password if email is not included --//
																	if($col == "email"){
																		if(!empty($data[$x])){
																			if(!empty($student->email)){
																				$student->email = $data[$x];
																			}else{
																				$student->email = $data[$x];
																				//-- also generate a password for student (Randomly Generate Password) --//
																				$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
																				$newPass = '';
																			    for($i = 0; $i < 10; $i++){
																			        $newPass.= $characters[rand(0, strlen($characters) - 1)];
																			    }
																				$student->pass = $this->security->hash($newPass);
																		
																				//-- Build login instructions --//
																				$logcreds = "Email: ".$student->email;
																				if($newPass){ $logcreds.= "\nPassword: ".$newPass; }

																				//-- Send Updated pwd & login Mail Message --//
																				/*$to = $student->email;
																				$subject = "Login Information Was Updated";
																				$message = "A recent change was made to your Athlos Tools account and your login information was updated. You can login to Athlos Tools  by going to this url: https://".$_SERVER['HTTP_HOST']." and logging in.\n\n".$logcreds."\n\nFeel free to reply to this email if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
																				//-- Send MSG with Mailgun --//
																				$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
																				                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
																				                        'to'      => $to,
																				                        'subject' => $subject,
																				                        'text'    => $message));*/
																			}
																		}else{
																			$student->email = NULL;
																			$student->pass = NULL;
																		}
																	}else if($col == "alt_id"){
																		if(!empty($data[$x])){
																			$student->alt_id = $data[$x];
																		}
																	}else if($col == "coach"){
																		if(!empty($data[$x])){
																			$coach = Users::findFirst(array("alt_id = :alt: AND school = :school:", "bind" => array("alt" => $data[$x], "school" => $school)));
																			if(!empty($coach)){
																				$student->coach = $coach->id;
																			}else{
																				$student->coach = NULL;
																			}
																		}else{
																			$student->coach = NULL;
																		}
																	}else{
																		//-- all other columns --//
																		$student->$col = $data[$x];
																	}
																}
																$x++;
															} //-- /end foreach --//
														}
														
														//-- Set Additional Items --//
														$student->active = 1;
														$student->school = $school;
														$student->teacher = 0;
														$student->turf_period = 0;
														
														if($student->save() == false){
															if(isset($results["failed"])){
																$results["failed"] .= ",".$studentName;
															}else{
																$results["failed"] = $studentName;
															}
														}else{
															//-- Successfully Updated / Saved --//
															if(isset($updated)){
																$updated .= ",".$studentName;
															}else{
																$updated = $studentName;
															}
														}
													}
												} //-- /end-if (empty student) --//
												
											}else{
											
												//-- Add New Student --//
												$aStudent = New Students();
											
												//-- Set Student details --//
												$x = 0;
												foreach($cols AS $col){
													if($x > 0){
														//-- Setup Student Name --//
														if($col == "fname" || $col == "lname"){
															if($col == "lname"){
																$studentName.= " ".$data[$x];	
															}else{
																$studentName = $data[$x];
															}
														}
														//-- Email Address --//
														if($col == "email"){
															$email = $this->filter->sanitize($data[$x],"email");
															if(filter_var($email, FILTER_VALIDATE_EMAIL)){
																$data[$x] = $email;
																//-- Verify that email is unused for users / students --//
																$emailExists = Students::count(array("email = :email:", "bind" => array("email" => $data[$x])));
																if($emailExists == 0){
																	$emailExists = Users::count(array("email = :email:", "bind" => array("email" => $data[$x])));
																}
															}else{
																$data[$x] = NULL;
																unset($email);
																$emailExists = 0;
															}
														}
														//-- Verify the Alt ID is unique for the current school --//
														if($col == "alt_id"){
															if(!empty($data[$x])){
																if($theSchool->ilt_school){
																	$altIdExists = Students::count(array("alt_id = :alt: AND school IN( ".implode(', ', $ilt_group)." )", "bind" => array("alt" => $data[$x])));
																}else{
																	$altIdExists = Students::count(array("alt_id = :alt: AND school = :school:", "bind" => array("alt" => $data[$x], "school" => $school)));
																}
															}else{
																$data[$x] = NULL;
																$altIdExists = 0;
															}
														}
														//-- Get Correct Coach ID --//
														if($col == "coach"){
															if(!empty($data[$x])){
																$coach = Users::findFirst(array("alt_id = :alt: AND school = :school:", "bind" => array("alt" => $data[$x], "school" => $school)));
																if(!empty($coach)){
																	$data[$x] = $coach->id;
																}else{
																	$data[$x] = NULL;
																}
															}else{
																$data[$x] = NULL;
															}
														}
														//-- Assign values to Student Object --//
														$aStudent->$col = $data[$x];
													}
													$x++;
												}
												
												if($emailExists == 0 && $altIdExists == 0){
													$aStudent->active = 1;
													$aStudent->school = $school;
													$aStudent->teacher = 0;
													$aStudent->turf_period = 0;
													$aStudent->date_added = time();
													
													//-- Generate Password if email exists --//
													if(!empty($aStudent->email)){
														//Randomly Generate Password
														$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
														$newPass = '';
													    for ($i = 0; $i < 10; $i++) {
													        $newPass .= $characters[rand(0, strlen($characters) - 1)];
													    }
														$aStudent->pass = $this->security->hash($newPass);
													}
													
													//-- Save Entry --//
													if($aStudent->save() == false){
														//-- Failure to Create Student --//
														if(isset($results["failed"])){
															$results["failed"].= ",".$studentName;
														}else{
															$results["failed"] = $studentName;
														}
														foreach($aStudent->getMessages() as $message){
															$results['msg'][] = $message->getMessage();
														}
													}else{
														//-- Success! Created Student --//
														if(isset($results["addedID"])){
															$results["addedID"] .= ",".$aStudent->id;
														}else{
															$results["addedID"] = $aStudent->id;
														}
														//-- Send Login Details Email Message --//
														/*if(!empty($aStudent->email)){
															$to = $aStudent->email;
															$subject = "Welcome to Athlos Tools";
															$message = "You have just been added to the Athlos Tools Admin. You can now login and view resources and grading by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nEmail: ".$aStudent->email."\nPassword: ".$newPass."\n\nFeel free to reply to this email if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
															//-- Send MSG with Mailgun --//
															$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
															                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
															                        'to'      => $to,
															                        'subject' => $subject,
															                        'text'    => $message));
														}*/
													}
													
												}else{
													//report which users failed to be created due to existing email address
													if(isset($results["failed"])){
														$results["failed"] .= ",".$studentName;
													}else{
														$results["failed"] = $studentName;
													}
													if($emailExists > 0){
														$results['msg'][] = "A User with the email: ".$data[5]." Already Exists in the System";
													}
													if($altIdExists > 0){
														$results['msg'][] = "A User with the Alt ID: ".$data[1]." Already Exists in the System";
													}
												}
											} //-- /end-if (exists) --//
											
										} //-- /end-if (table students) --//
									}
								} //-- /end-if (not 1st row) --//
								$row++;
						    } //-- /end-while --//
						    fclose($handle);
					
						}
					} //-- /end-if (importSchool != '') --//
					
					
					$results["uploaded"] = $csvFileName;

					if(unlink($csvFile)){
						$results["fileLocaiton"] = "none";
					}else{
						$results["fileLocaiton"] = $csvFile;
					}
					$results["totalUpdates"] = $totalUpdates;
					
					$results["userType"] = $userType;
					if(isset($updated)){
						$results["updatedRecords"] = $updated;
						$recordsUpdated = count(explode(",",$updated));
						$results["recordsUpdated"] = $recordsUpdated;
					}
					$results["rows"] = $row;
				}else{
					if(unlink($csvFile)){
						$results["fileLocaiton"] = "none";
					}else{
						$results["fileLocaiton"] = $csvFile;
					}
					$results["uploaded"] = $this->request->getPost("name");
					$results["permissions"] = TRUE;
				}
				
				echo json_encode($results);
				exit();
				
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end csvimportAction() --//
	
	
	public function coachuploadAction()
	{
		//error_log("this is a test");
		error_reporting(E_ALL | E_STRICT);
		require('../app/controllers/fileUpload/CoachUploadHandler.php');
		$upload_handler = new UploadHandler();
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end csvuploadAction() --//
	
	public function coachimportAction()
	{		
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'CSVImport'){
				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){
					//error_log("were in!!");
					//$page = $this->request->getPost("CSVPage");
					$currUrl = parse_url($_SERVER['REQUEST_URI']);
					$page = str_replace("/","",str_replace("coachimport","",$currUrl["path"]));
				
					switch($page){
						case "students":
							$columns = "alt_id,fname,lname,school,grade,coach,cname";
							$table = "students";
							$userType = "Student(s)";
							break;
					}
					
					//read CSV file contents
					$csvFile = dirname($_SERVER['SCRIPT_FILENAME']).'/../app/controllers/fileUpload/files/'.$this->request->getPost("name");
					$csvFileName = $this->request->getPost("name");
					$row = 0;
					$totalUpdates = 0;
					$recordsUpdated = 0;
					$updates = '';
					$cols = explode(",",$columns);
					if (($handle = fopen($csvFile, "r")) !== FALSE) {
					    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
							//$results["data"] = $data;
							if($row!=0){
								if(count($data) > 1){
									if($table == 'students'){
										//check if csv entry is in the users table
										$exists = Students::count(array("alt_id = :id: AND school = :school:", "bind" => array("id" => $data[0], "school" => $data[3])));
										if($data[0] != "" && $exists){
										
											$student = Students::findFirst(array(
												"conditions" => "alt_id = :ID: AND school = :school:",
												"bind" => array('ID' => $data[0], "school" => $data[3])
												));
											
											if(!empty($student)){
												$student->coach = $data[5];
												$studentName = $data[1].' '.$data[2];
												
												//-- Save Entry --//
												if($student->save() == false){
													if(isset($results["failed"])){
														$results["failed"].= ",".$studentName;
													}else{
														$results["failed"] = $studentName;
													}
													foreach($student->getMessages() as $message){
														$results['msg'][] = $message->getMessage();
													}
												}else{
													$totalUpdates++;
													if($updates != ''){
														$updates.= ",".$studentName;
													}else{
														$updates.= $studentName;
													}
												}
											}
										}
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
					
					$results["userType"] = $userType;
					if(isset($totalUpdates)){
						$results["updatedRecords"] = $totalUpdates;
						$recordsUpdated = count(explode(",",$updates));
						$results["recordsUpdated"] = $recordsUpdated;
					}
					$results["rows"] = $row;
				}else{
					if(unlink($csvFile)){
						$results["fileLocaiton"] = "none";
					}else{
						$results["fileLocaiton"] = $csvFile;
					}
					$results["uploaded"] = $this->request->getPost("name");
					$results["permissions"] = TRUE;
				}
				
				echo json_encode($results);
				exit();
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end coachimportAction() --//
	
	
}
