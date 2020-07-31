<?php

class RostersController extends \Phalcon\Mvc\Controller
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
		//-- Deny Access if no Priveleges --//
		if(!$this->cap['rosters']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Rosters");
	}
	
    public function indexAction()
    {
        //-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars / sanitize vars --//
			//$limit = $this->request->getPost("limit", "int");
			//$page = $this->request->getPost("pageNum", "int");
			$field = $this->request->getPost("field", "string");
			$lastField = $this->request->getPost("lastField", "string");
			$dir = $this->request->getPost("dir", "string");
			$schoolID = $this->request->getPost("filterSchool", "int");
			$coachID = $this->request->getPost("filterCoach", "int");
			$gradeID = $this->request->getPost("filterGrade", "int");
			$periodID = $this->request->getPost("class-period", "int");
			$searchTerm = $this->request->getPost("filterSearch", "string");
			
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
			    $inputs = '<input type="hidden" name="field" value="lastname" /><input type="hidden" name="dir" value="ASC" />';
				$lastField = '<input type="hidden" name="lastField" value="lastname" /><input type="hidden" name="dir" value="ASC" />';
			}
			
		}else{
		    $inputs = '<input type="hidden" name="field" value="lastname" /><input type="hidden" name="dir" value="ASC" />';
			$lastField = '<input type="hidden" name="lastField" value="lastname" /><input type="hidden" name="dir" value="ASC" />';
		}
		
		//-- Set Vars to Defaults - if not present --//
		/*if(!isset($limit) || !$limit){ $limit = 10; }
		if(!isset($page) || !$page){
			$page = 1;
			$offset = 0;
		}else{
			//-- set offset based on page number --//
			$offset = ($page - 1) * $limit;
		}*/
		if(!isset($field) || !$field){ $field = 'lastname'; }
		if(!isset($dir) || !$dir){ $dir = 'ASC'; }
		
		//-- Figure out Filters --//
		if($this->session->get("user-school")){
			//-- Admins --//
			$schoolID = $this->session->get("user-school");
			$schoolFilter = '';
		}else{
			if(isset($schoolID) && $schoolID){ $schoolFilter = '<input type="hidden" name="filterSchool" value="'.$schoolID.'" />'; }else{ $schoolFilter = ''; }
		}
		if($this->session->get("user-role") == 6){
			//-- APC Coaches --//
			$coachID = $this->session->get("user-id");
			$coachFilter = '';
		}else{
			if(isset($coachID) && $coachID){ $coachFilter = '<input type="hidden" name="filterCoach" value="'.$coachID.'" />'; }else{ $coachFilter = ''; }
		}
		if(isset($gradeID) && $gradeID != ''){ $gradeFilter = '<input type="hidden" name="filterGrade" value="'.$gradeID.'" />'; }else{ $gradeFilter = ''; }
		if(isset($periodID) && $periodID){ $periodFilter = '<input type="hidden" name="filterPeriod" value="'.$periodID.'" />'; }else{ $periodFilter = ''; }
		if(isset($searchTerm) && $searchTerm){ $searchFilter = '<input type="hidden" name="filterSearch" value="'.$searchTerm.'" />'; }else{ $searchFilter = ''; }
		//$limitFilter = '<input type="hidden" name="limit" value="'.$limit.'" />';
		$filters = $schoolFilter.$coachFilter.$gradeFilter.$periodFilter.$searchFilter;
		
		//-- Add Filter inputs --//
		$lastField.= $filters;
		
		//-- Map to real column names --//
		if($field == 'lastname'){ $column = 'lname'; }
		else if($field == 'firstname'){ $column = 'fname'; }
		else if($field == 'grade_level'){ $column = 'grade'; }
		else if($field == 'school'){ $column = 'school'; }
		else if($field == 'coach'){ $column = 'coach'; }
		else if($field == 'class'){ $column = 'turf_period'; }
		else{ $column = 'lname'; }
		
		//-- Figure out filter conditions --//
		$conditions = "active = 1";
		if(isset($schoolID) && $schoolID){
			if($conditions == ''){
				$conditions.= "school = ".$schoolID;
			}else{
				$conditions.= " AND school = ".$schoolID;
			}
		}
		if(isset($gradeID) && $gradeID != ''){
			if($conditions == ''){
				$conditions.= "grade = ".$gradeID;
			}else{
				$conditions.= " AND grade = ".$gradeID;
			}
		}
		if(isset($coachID) && $coachID){
			if($conditions == ''){
				$conditions.= "coach = ".$coachID;
			}else{
				$conditions.= " AND coach = ".$coachID;
			}
		}
		if(isset($periodID) && $periodID != ''){
			if($conditions == ''){
				$conditions.= "turf_period = ".$periodID;
			}else{
				$conditions.= " AND turf_period = ".$periodID;
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
		if($conditions != "active = 1"){
			$students = Students::find(array($conditions, "order" => $column." ".$dir));
			$totalStudents = Students::count(array($conditions, "order" => $column." ".$dir));
		}else{
			$students = 0;
			$totalStudents = 0;
		}
		
		//-- Grab Schools --//
		$school_abbreviations = array();
		if($this->session->get("user-district")){
			$schools = Schools::find(array("district = :dist:", "bind" => array("dist" => $this->session->get("user-district")), "order" => "state ASC, schoolName ASC, city ASC"));
		}else{
			$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		}
		if(!empty($schools)){
			foreach($schools as $school_item){
				$school_abbreviations[$school_item->id] = $school_item->abbreviation;
			}
		}
		
		//-- Grab Grade Levels --//
		$grade_level = GradeLevel::find(array("order" => "id ASC"));
		
		//-- Grab Coaches / filtered by school --//
		$coachConditions = "(role = 5 OR role = 6)";
		if(isset($schoolID) && $schoolID){
			$coachConditions.= " AND school = ".$schoolID;
		}else if($this->session->get("user-district")){
			$coachConditions.= " AND district = ".$this->session->get("user-district");
		}
		
		//-- Coach Lists --//
		$coachFilterList = Users::find(array($coachConditions, "order" => "lname ASC"));
		
		//-- Grab List of All Coaches --//
		$coachList = $coachFilterList;
		
		//-- Create Coach Reference Array - from coachList --//
		if(isset($coachList) && $coachList){
			$coachRef = array();
			foreach($coachList as $cl){
				$coachRef[$cl->id] = $cl->lname.', '.$cl->fname;
			}
		}
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("cap", $this->cap);
        $this->view->setVar("students", $students);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("grade_level", $grade_level);
		$this->view->setVar("coachList", $coachFilterList);
		if(isset($coachRef) && $coachRef){ $this->view->setVar("coachRef", $coachRef); }
		$this->view->setVar("lastField", $lastField);
		$this->view->setVar("inputs", $inputs);
		$this->view->setVar("filters", $filters);
		$this->view->setVar("field", $field);
		$this->view->setVar("dir", strtolower($dir));
		//$this->view->setVar("limit", $limit);
		//$this->view->setVar("pageNum", $page);
		$this->view->setVar("totalStudents", $totalStudents);
		//$this->view->setVar("limitFilter", $limitFilter);
		$this->view->setVar("schoolFilter", $schoolFilter);
		if(isset($schoolID) && $schoolID){ $this->view->setVar("schoolID", $schoolID); }
		if(isset($school_abbreviations)){ $this->view->setVar("school_abbreviations", $school_abbreviations); }
		$this->view->setVar("gradeFilter", $gradeFilter);
		if(isset($gradeID) && $gradeID != ''){ $this->view->setVar("gradeID", $gradeID); }
		$this->view->setVar("coachFilter", $coachFilter);
		if(isset($coachID) && $coachID){ $this->view->setVar("coachID", $coachID); }
		$this->view->setVar("periodFilter", $periodFilter);
		if(isset($periodID) && $periodID != ''){ $this->view->setVar("periodID", $periodID); }
		$this->view->setVar("searchFilter", $searchFilter);
		if(isset($searchTerm) && $searchTerm){ $this->view->setVar("searchTerm", $searchTerm); }
    }
	
	public function searchAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab User --//
			if($this->request->getPost("action") == 'search_student'){
				//-- grab / set vars --//
				$searchAltID = $this->request->getPost("alt", "int");
				$searchName = $this->request->getPost("name", "string");
				$theSchool = $this->request->getPost("school", "int");
				$results = $schoolList = array();
				
				//-- Figure out District --//
				if(!empty($theSchool)){
					$theOne = Schools::findFirst(array(
						"conditions" => "id = :sch:",
						"bind" => array("sch" => $theSchool)
					));
					$district = $theOne->district;
				}else{
					$district = $this->session->get("district");
				}
				
				//-- Find list of schools --//
				$schools = Schools::find(array(
					"conditions" => "district = :Dist:",
					"bind" => array("Dist" => $district)
				));
				foreach($schools as $school){
					$schoolList[] = $school->id;
				}
				//-- Check Alt ID --//
				if(!empty($searchAltID)){
					$students = Students::find(array(
						"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." )",
						"bind" => array("altID" => $searchAltID)
					));
				}
				//-- Check Student Name --//
				if(empty($students) && !empty($searchName)){	
					//-- get rid of any blank values --//
					$names = array();
					$search = explode(' ', $searchName);
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
					//-- Perform Name Search --//
					$students = Students::find(array($search_conditions." AND school IN( ".implode(', ', $schoolList)." )", "order" => "lname ASC, fname ASC"));
				}
				
				//-- Return Results --//
				if(!empty($students) && count($students) > 0){
					$results["result"] = "success";
					foreach($students as $student){
						$results["students"][] = $student;
					}
					$results["count"] = count($students);
				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end search_student --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- searchAction() --//
	
	public function addfoundstudentAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab User --//
			if($this->request->getPost("action") == 'add_found_user'){
				//-- grab / set vars --//
				$studentID = $this->request->getPost("sid", "int");
				$altID = $this->request->getPost("altid", "int");
				$stateID = $this->request->getPost("stateid", "int"); //-- optional --//
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$schoolID = $this->request->getPost("school", "int");
				$gradeID = $this->request->getPost("grade", "int");
				$coachID = $this->request->getPost("coach", "int");
				$period = $this->request->getPost("period", "int"); //-- optional --//
				$results = $schoolList = array();
				
				//-- Verify Permissions --//
				if($this->cap['rosters']['add-to-roster']){
					//-- Truncate vars to certain length (so not too long) --//
					if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
					if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
					if($schoolID && strlen($schoolID) > 5){ $schoolID = substr($schoolID, 0, 5); }
					if($gradeID && strlen($gradeID) > 2){ $gradeID = substr($gradeID, 0, 2); }
					if($coachID && strlen($coachID) > 10){ $coachID = substr($coachID, 0, 10); }
					//-- make sure the required info is present --//
					if($fname && $lname && !empty($altID) && $studentID && !empty($schoolID) && $gradeID != '' && $coachID){
						//-- Figure out District --//
						$theOne = Schools::findFirst(array(
							"conditions" => "id = :sch:",
							"bind" => array("sch" => $schoolID)
						));
						$district = $theOne->district;
				
						//-- Find list of schools --//
						$schools = Schools::find(array(
							"conditions" => "district = :Dist:",
							"bind" => array("Dist" => $district)
						));
						foreach($schools as $school){
							$schoolList[] = $school->id;
						}
						//-- Verify student's Alt ID is unique for their district --//
						$altIDCheck = Students::count(array(
							"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." ) AND id != :id:",
							"bind" => array("altID" => $altID, "id" => $studentID)
						));
						if(empty($altIDCheck)){
							
							//-- Grab the student object --//
							$student = Students::findFirst(array('id = :ID:', "bind" => array("ID" => $studentID)));
							if(!empty($student)){
								//-- Enroll Student & Assign Coach --//
								$student->active = 1;
								$student->coach = $coachID;
								$student->turf_period = $period;
								//-- Update Student Info --//
								$student->alt_id = $altID;
								$student->fname = $fname;
								$student->lname = $lname;
								$student->school = $schoolID;
								$student->grade = $gradeID;
								//-- optional state id --//
								if(!empty($stateID)){
									$student->state_id = $stateID;
								}else{
									$student->state_id = NULL;
								}
								//-- Save Entry --//
								if($student->save() == false){
									$results["result"] = "failed";
									$results["error_title"] = "Failed to Add Student";
									$results["error_msg"] = "Something went wrong, and the student was not added.";
								}else{
									$results["result"] = "success";
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Student Not Found";
								$results["error_msg"] = "For some reason the student was not found... please contact an administrator if this is an issue.";
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Alt_ID In Use";
							$results["error_msg"] = "The Alt ID or SIS ID is already being used in this district of schools. Please contact an administrator if there is a problem.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Missing Information";
						$results["error_msg"] = "Make sure all student information is filled out and they a coach assigned. The state id and class period are the only optional fields.";
					}
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end search_student --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- addfoundstudentAction() --//
	
	
	public function coachaddnewstudentAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add Student --//
			if($this->request->getPost("action") == 'add_new_student'){
				//-- grab / sanitize vars --//
				$altID = $this->request->getPost("altid", "int");
				$stateID = $this->request->getPost("stateid", "int"); //-- optional --//
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$schoolID = $this->request->getPost("school", "int");
				$gradeID = $this->request->getPost("grade", "int");
				$coachID = $this->request->getPost("coach", "int");
				$period = $this->request->getPost("period", "int"); //-- optional --//
				$results = $schoolList = array();
				
				//-- Verify Permissions --//
				if($this->cap['rosters']['create']){
					//-- Truncate vars to certain length (so not too long) --//
					if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
					if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
					if($schoolID && strlen($schoolID) > 5){ $schoolID = substr($schoolID, 0, 5); }
					if($gradeID && strlen($gradeID) > 2){ $gradeID = substr($gradeID, 0, 2); }
					if($coachID && strlen($coachID) > 10){ $coachID = substr($coachID, 0, 10); }
					//-- make sure the required info is present --//
					if($fname && $lname && !empty($altID) && !empty($schoolID) && $gradeID != '' && $coachID){
						//-- Figure out District --//
						$theOne = Schools::findFirst(array(
							"conditions" => "id = :sch:",
							"bind" => array("sch" => $schoolID)
						));
						$district = $theOne->district;
				
						//-- Find list of schools --//
						$schools = Schools::find(array(
							"conditions" => "district = :Dist:",
							"bind" => array("Dist" => $district)
						));
						foreach($schools as $school){
							$schoolList[] = $school->id;
						}
						//-- Verify student's Alt ID is unique for their district --//
						$altIDCheck = Students::count(array(
							"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." )",
							"bind" => array("altID" => $altID)
						));
						if(empty($altIDCheck)){
							/*---------------------------------------------
								Now Add Student -- Passed all validation
							----------------------------------------------*/
							$student = New Students();
							//-- Enroll Student & Assign Coach --//
							$student->active = 1;
							$student->coach = intval($coachID);
							$student->turf_period = $period;
							//-- Update Student Info --//
							$student->alt_id = $altID;
							$student->fname = $fname;
							$student->lname = $lname;
							$student->school = $schoolID;
							$student->grade = $gradeID;
							$student->teacher = 0;
							 //-- State ID --//
							if(!empty($stateID)){
								$student->state_id = $stateID;
							}else{
								$student->state_id = NULL;
							}
							$student->date_added = time();

							//-- Save Entry --//
							if($student->save() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Failed to Add Student";
								$results["error_msg"] = "Something went wrong, and the student was not added.";
							}else{
								$results["result"] = "success";
								$results["student_id"] = $student->id;
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Alt_ID In Use";
							$results["error_msg"] = "The Alt ID or SIS ID is already being used in this district of schools. Please contact an administrator if there is a problem.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Missing Information";
						$results["error_msg"] = "Make sure all student information is filled out and they a coach assigned. The state id and class period are the only optional fields.";
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
		
	} //-- end coachaddnewstudentAction() --//
	
	
	public function removestudentAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'remove_student'){
				//-- grab / set / sanitize vars --//
				$studentID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['rosters']['remove']){
					if($studentID && is_numeric($studentID)){
						//-- Grab User --//
						$student = Students::findFirst(array(
							"conditions" => "id = :userID:",
							"bind" => array('userID' => $studentID)
							));
							
						if($student){
							$student->active = 0;
							$student->coach = null;
							$student->turf_period = 0;
							$student->grade = (intval($student->grade) - 1);
							
							//-- Remove from Class Roster --//
							if($student->save() == false){
							    $results['result'] = "failed";
								$results["error_title"] = "Failed to Remove Student";
								$results["error_msg"] = "Something went wrong, and the student was not removed from your roster.";
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
		
	} //-- end removestudentAction() --//
	
	
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
					$results["school"] = $student->school;
					$results["grade"] = $student->grade;
					$results["coach"] = $student->coach;
					$results["period"] = $student->turf_period;
				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Student --//
			
			//-- Function to Edit Student --//
			if($this->request->getPost("action") == 'edit_student'){
				
				//-- grab / set vars --//
				$studentID = $this->request->getPost("sid", "int");
				$altID = $this->request->getPost("altid", "int");
				$stateID = $this->request->getPost("stateid", "int"); //-- optional --//
				$fname = $this->request->getPost("first", "string");
				$lname = $this->request->getPost("last", "string");
				$schoolID = $this->request->getPost("school", "int");
				$gradeID = $this->request->getPost("grade", "int");
				$coachID = $this->request->getPost("coach", "int");
				$period = $this->request->getPost("period", "int"); //-- optional --//
				$results = $schoolList = array();
				
				//-- Verify Permissions --//
				if($this->cap['rosters']['edit']){
					//-- Truncate vars to certain length (so not too long) --//
					if(strlen($fname) > 40){ $fname = substr($fname, 0, 40); }
					if(strlen($lname) > 40){ $lname = substr($lname, 0, 40); }
					if($schoolID && strlen($schoolID) > 5){ $schoolID = substr($schoolID, 0, 5); }
					if($gradeID && strlen($gradeID) > 2){ $gradeID = substr($gradeID, 0, 2); }
					if($coachID && strlen($coachID) > 10){ $coachID = substr($coachID, 0, 10); }
					//-- make sure the required info is present --//
					if($fname && $lname && !empty($altID) && $studentID && !empty($schoolID) && $gradeID != '' && $coachID){
						//-- Figure out District --//
						$theOne = Schools::findFirst(array(
							"conditions" => "id = :sch:",
							"bind" => array("sch" => $schoolID)
						));
						$district = $theOne->district;
				
						//-- Find list of schools --//
						$schools = Schools::find(array(
							"conditions" => "district = :Dist:",
							"bind" => array("Dist" => $district)
						));
						foreach($schools as $school){
							$schoolList[] = $school->id;
						}
						//-- Verify student's Alt ID is unique for their district --//
						$altIDCheck = Students::count(array(
							"conditions" => "alt_id = :altID: AND school IN( ".implode(', ', $schoolList)." ) AND id != :id:",
							"bind" => array("altID" => $altID, "id" => $studentID)
						));
						if(empty($altIDCheck)){
							
							//-- Grab the student object --//
							$student = Students::findFirst(array('id = :ID:', "bind" => array("ID" => $studentID)));
							if(!empty($student)){
								//-- Update Student Info --//
								$student->alt_id = $altID;
								$student->fname = $fname;
								$student->lname = $lname;
								$student->school = $schoolID;
								$student->grade = $gradeID;
								$student->coach = $coachID;
								$student->turf_period = $period;
								//-- optional state id --//
								if(!empty($stateID)){
									$student->state_id = $stateID;
								}else{
									$student->state_id = NULL;
								}
								//-- Save Entry --//
								if($student->save() == false){
									$results["result"] = "failed";
									$results["error_title"] = "Failed to Update Student";
									$results["error_msg"] = "Something went wrong, and the student was not updated.";
								}else{
									$results["result"] = "success";
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Student Not Found";
								$results["error_msg"] = "For some reason the student was not found... please contact an administrator if this is an issue.";
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Alt_ID In Use";
							$results["error_msg"] = "The Alt ID or SIS ID is already being used in this district of schools. Please contact an administrator if there is a problem.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Missing Information";
						$results["error_msg"] = "Make sure all student information is filled out and that a coach is assigned. The state id and class period are the only optional fields.";
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
		
	} //-- end editstudentAction() --//
	
	
	public function validaltidAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Validate Alt ID --//
			if($this->request->getPost("action") == 'validate_alt_id'){
				//-- Sanitize Vars --//
				$alt_id = $this->request->getPost("altid", "string");
				$schoolID = $this->request->getPost("school", "int");
				$studentID = $this->request->getPost("student_id", "int");
				$results = $schoolList = array();
				
				if(!empty($schoolID)){
					$theSchool = Schools::findFirst(array("id = :id:", "bind" => array("id" => $schoolID)));
					$district = $theSchool->district;
			
					//-- Find list of schools --//
					$schools = Schools::find(array(
						"conditions" => "district = :Dist:",
						"bind" => array("Dist" => $district)
					));
					foreach($schools as $school){
						$schoolList[] = $school->id;
					}
					
					//-- Check to see if alt ID already exists -- Check within Districts --//
					if($studentID){
						$check = Students::count(array("alt_id = :alt: AND school IN( ".implode(', ', $schoolList)." ) AND id != :id:", "bind" => array("alt" => $alt_id, "id" => $studentID)));
					}else{
						$check = Students::count(array("alt_id = :alt: AND school IN( ".implode(', ', $schoolList)." )", "bind" => array("alt" => $alt_id)));
					}
					
					if(!$check){
						$results['result'] = "success";
					}else{
						$results['result'] = "failed";
					}
				}else{
					$results['result'] = "invalid";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end validaltidAction(); --//
	
}
