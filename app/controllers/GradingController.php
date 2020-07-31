<?php

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class GradingController extends \Phalcon\Mvc\Controller
{
	public function initialize()
	{
		//-- Redirect if not already logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Grading");
	}
	
	public function indexAction()
    {
		//-- Deny Access if no Priveleges -- Just Char Coaches / Administrators --//
		if($this->session->get("user-role") > 2){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
		//-- Grab Semesters Object --//
		$semesters = Semesters::find(array("", "order" => "id DESC"));
		//-- Grab Traits Object --//
		$traits = CurriculumTraits::find(array("", "order" => "id ASC"));
		//-- Grab Current School --//
		if($this->session->get("user-school")){
			$school = Schools::findFirst(array("id = ".$this->session->get("user-school")));
			$schools = NULL;
		}else{
			$school = NULL;
			$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		}
		
		//-- Pass Objects to View --//
        $this->view->setVar("semesters", $semesters);
		$this->view->setVar("traits", $traits);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("school", $school);
		
	} //-- end indexAction --//
	
    public function staffAction()
    {
		//-- Deny Access if no Priveleges -- NO PARENTS --//
		if($this->session->get("user-role") > 4){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
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
			$searchTerm = $this->request->getPost("filterSearch", "string");
			$missingGradeRole = $this->request->getPost("filterMissingGrade", "int");
			$missingTrait = $this->request->getPost("filterMissingTrait", "string");
			
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
			    $inputs = '<input type="hidden" name="field" value="username" /><input type="hidden" name="dir" value="ASC" />';
				$lastField = '<input type="hidden" name="lastField" value="username" /><input type="hidden" name="dir" value="ASC" />';
			}
			
		}else{
		    $inputs = '<input type="hidden" name="field" value="username" /><input type="hidden" name="dir" value="ASC" />';
			$lastField = '<input type="hidden" name="lastField" value="username" /><input type="hidden" name="dir" value="ASC" />';
		}
		
		//-- Grab Traits Object --//
		$traits = CurriculumTraits::find('');
		
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
		
		/*-----------------------
			Figure out Filters
		------------------------*/
		//-- Force school filter for non admin users --//
		if($this->session->get("user-role") != 1){
			$schoolID = $this->session->get("user-school");
			$schoolFilter = '';
		}else{
			if(isset($schoolID) && $schoolID){ $schoolFilter = '<input type="hidden" name="filterSchool" value="'.$schoolID.'" />'; }else{ $schoolFilter = ''; }
		}
		//-- Force Grade & Teacher Filters for user roles 'Teachers' && 'TurfCoaches' --//
		if($this->session->get("user-role") == 4 || $this->session->get("user-role") == 3){
			/*---------------------------------------------------
				Limit turfCoach & Teachers to specific grades
			----------------------------------------------------*/
			$gradeLimits = GradeLimit::findFirst(array("user = :id:", "bind" => array("id" => $this->session->get("user-id"))));
			if($gradeLimits && $gradeLimits->grades != NULL){
				if(strpos($gradeLimits->grades, ",")){
					$gradeLimit = explode(',', $gradeLimits->grades);
				}else{
					$gradeLimit = array($gradeLimits->grades);
				}
			}else{
				$gradeLimit = array();
			}
			//-- make sure gradeID is one of the grades the turfcoach/teacher can grade --//
			if(isset($gradeID) && $gradeID != ''){
				if(in_array($gradeID, $gradeLimit)){
					$gradeFilter = '<input type="hidden" name="filterGrade" value="'.$gradeID.'" />';
				}else{
					unset($gradeID);
					$gradeFilter = '';
				}
			}else{ $gradeFilter = ''; }
			
			//-- FORCE TEACHER FILTER FOR TEACHERS --//
			if($this->session->get("user-role") == 3){
				$teacherID = $this->session->get("user-id");
				$teacherFilter = '';
			}else{
				if(isset($teacherID) && $teacherID){ $teacherFilter = '<input type="hidden" name="filterTeacher" value="'.$teacherID.'" />'; }else{ $teacherFilter = ''; }
			}
			/*------------------------------------
				end turfcoach & Teacher limits
			-------------------------------------*/
		}else{
			if(isset($gradeID) && $gradeID != ''){ $gradeFilter = '<input type="hidden" name="filterGrade" value="'.$gradeID.'" />'; }else{ $gradeFilter = ''; }
			if(isset($teacherID) && $teacherID){ $teacherFilter = '<input type="hidden" name="filterTeacher" value="'.$teacherID.'" />'; }else{ $teacherFilter = ''; }
		}
		//-- filter for missing grades for students --//
		if(isset($missingGradeRole) && $missingGradeRole != ''){ $missingGradeFilter = '<input type="hidden" name="filterMissingGrade" value="'.$missingGradeRole.'" />'; }else{ $missingGradeFilter = ''; }
		if(isset($missingTrait) && $missingTrait){ $missingTraitFilter = '<input type="hidden" name="filterMissingTrait" value="'.$missingTrait.'" />'; }else{ $missingTraitFilter = ''; }
		//-- Search Filter --//
		if(isset($searchTerm) && $searchTerm != ''){ $searchFilter = '<input type="hidden" name="filterSearch" value="'.$searchTerm.'" />'; }else{ $searchFilter = ''; }
		
		$limitFilter = '<input type="hidden" name="limit" value="'.$limit.'" />';
		$filters = $limitFilter.$schoolFilter.$gradeFilter.$teacherFilter.$missingGradeFilter.$missingTraitFilter.$searchFilter;
		
		//-- Add Filter inputs --//
		$lastField.= $filters;
		
		//-- Map to real column names --//
		if($field == 'firstname'){ $column = 'fname'; }
		else if($field == 'lastname'){ $column = 'lname'; }
		else if($field == 'teacher'){ $column = 'teacher'; }
		else if($field == 'grade_level'){ $column = 'grade'; }
		else{ $column = 'fname'; }
		
		if(isset($missingGradeRole) && $missingGradeRole != ''){ $s = 's.'; }else{ $s = ''; } //-- makes conditions work on multi table query --//
		//-- Figure out filter conditions --//
		$conditions = $s."active = 1";
		if(isset($schoolID) && $schoolID){
			if($conditions == ''){
				$conditions.= $s."school = ".$schoolID;
			}else{
				$conditions.= " AND ".$s."school = ".$schoolID;
			}
		}
		if(isset($gradeID) && $gradeID != ''){
			if($conditions == ''){
				$conditions.= $s."grade = ".$gradeID;
			}else{
				$conditions.= " AND ".$s."grade = ".$gradeID;
			}
		}else if(($this->session->get("user-role") == 4 || $this->session->get("user-role") == 3) && !empty($gradeLimit)){
			/*---------------------------------------
				limits for turfcoaches / teachers
			----------------------------------------*/
			$i = 0;
			$conditions.= " AND (";
			foreach($gradeLimit as $gLimit){
				if($i == 0){
					$conditions.= $s."grade = ".$gLimit;
				}else{
					$conditions.= " OR ".$s."grade = ".$gLimit;
				}
				$i++;
			}
			$conditions.= ")";
			/*--------------------------
				end turf/teach limits
			---------------------------*/
		}
		if(isset($teacherID) && $teacherID){
			if($conditions == ''){
				$conditions.= $s."teacher = ".$teacherID;
			}else{
				$conditions.= " AND ".$s."teacher = ".$teacherID;
			}
		}
		//-- Add Search Term to conditions --//
		if(isset($searchTerm) && $searchTerm != ''){
			if($conditions == ''){
				$conditions.= "(fname = '".$searchTerm."' OR fname LIKE '".$searchTerm."%' OR lname = '".$searchTerm."' OR lname LIKE '".$searchTerm."%')";
			}else{
				$conditions.= " AND (fname = '".$searchTerm."' OR fname LIKE '".$searchTerm."%' OR lname = '".$searchTerm."' OR lname LIKE '".$searchTerm."%')";
			}
		}
		
		//-- Grab Students Object --//
		if(isset($missingGradeRole) && $missingGradeRole != ''){
			
			//-- Figure out trait if is set --//
			$added_query = "";
			if(isset($missingTrait) && $missingTrait){
				$traitList = array();
				foreach($traits as $trait){
					$traitList[] = $trait->url_name;
				}
				//-- verify trait is correct and not tampered with --//
				if(in_array($missingTrait, $traitList)){
					$added_query = " AND ".$missingTrait." != 0";
				}
			}
			
			//-- Grab current page of students with missing grades --//
			$query = "SELECT DISTINCT s.id, s.grade, s.fname, s.lname, s.teacher FROM students AS s, grading AS g WHERE g.student = s.id AND g.semester = ".$this->session->get("current-semester")." AND ".$conditions." AND s.id NOT IN (SELECT DISTINCT student FROM grading WHERE eval_role = ".$missingGradeRole.$added_query.") ORDER BY ".$column." ".$dir." LIMIT ".$offset.", ".$limit;
			$response = $this->db->query($query, array());
			$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
			$students = $response->fetchAll();
			
			//-- Grab current student count --//
			$countQuery = "SELECT COUNT(DISTINCT s.id) FROM students AS s, grading AS g WHERE g.student = s.id AND g.semester = ".$this->session->get("current-semester")." AND ".$conditions." AND s.id NOT IN (SELECT DISTINCT student FROM grading WHERE eval_role = ".$missingGradeRole.$added_query.") ORDER BY ".$column." ".$dir;
			$result = $this->db->prepare($countQuery); 
			$result->execute();
			$totalStudents = $result->fetchColumn();
			
		}else{
			$students = Students::find(array($conditions, "order" => $column." ".$dir, "limit" => array("number" => $limit, "offset" => $offset)));
			$totalStudents = Students::count(array($conditions, "order" => $column." ".$dir));
		}
		
		//-- Grab Schools --//
		$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		//-- Grab Grade Levels --//
		$grade_level = GradeLevel::find(array("order" => "id ASC"));
		//-- Grab Teachers / filtered by school & Grade if those filters are set --//
		$teachConditions = "role = 3";
		if(isset($schoolID) && $schoolID){
			$teachConditions.= " AND school = ".$schoolID;
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
		if($this->session->get("user-role") != 1){
			$teacherList = Users::find(array("role = 3 AND school = ".$schoolID, "order" => "lname ASC"));
		}else{
			$teacherList = Users::find(array("role = 3", "order" => "lname ASC"));
		}
		
		//-- Create Teacher Reference Array - from teacherList --//
		if(isset($teacherList) && $teacherList){
			$teacherRef = array();
			foreach($teacherList as $tl){
				$teacherRef[$tl->id] = $tl->lname.', '.$tl->fname;
			}
		}
		
		//-- Grab Completed Notifications for school --//
		if(isset($schoolID) && $schoolID){
			$theSchool = Schools::findFirst(array("id = :id:", "bind" => array("id" => $schoolID)));
			$reqNote = unserialize($theSchool->notifications);
			if(!empty($reqNote)){
				//-- unset the unrequired ones --//
				foreach($reqNote as $key => $val){
					if(!$val){
						unset($reqNote[$key]);
					}
				}
			}
		}
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("traits", $traits);
        $this->view->setVar("students", $students);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("grade_level", $grade_level);
		$this->view->setVar("teacherList", $teacherFilterList);
		if(isset($teacherRef) && $teacherRef){ $this->view->setVar("teacherRef", $teacherRef); }
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
		$this->view->setVar("gradeFilter", $gradeFilter);
		if(isset($gradeID) && $gradeID != ''){ $this->view->setVar("gradeID", $gradeID); }
		$this->view->setVar("teacherFilter", $teacherFilter);
		if(isset($teacherID) && $teacherID){ $this->view->setVar("teacherID", $teacherID); }
		$this->view->setVar("missingGradeFilter", $missingGradeFilter);
		if(isset($missingGradeRole) && $missingGradeRole != ''){ $this->view->setVar("missingGradeRole", $missingGradeRole); }
		$this->view->setVar("missingTraitFilter", $missingTraitFilter);
		if(isset($missingTrait) && $missingTrait){ $this->view->setVar("missingTrait", $missingTrait); }
		if(isset($searchTerm) && $searchTerm != ''){ $this->view->setVar("searchTerm", $searchTerm); }
		$this->view->setVar("searchFilter", $searchFilter);
		if(isset($reqNote)){ $this->view->setVar("reqNote", $reqNote); }
		if(($this->session->get("user-role") == 4 || $this->session->get("user-role") == 3) && !empty($gradeLimit)){ $this->view->setVar("gradeLimit", $gradeLimit); }
    } //-- end staffAction() --//
	
	
	public function studentsAction()
    {
		//-- Deny Access if no Priveleges -- Only Students --//
		if($this->session->get("user-id") != NULL && $this->session->get("user-role") < 100){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
		$notes = $traitList = array();
		
		//-- Grab traits that need to be graded from the student's school --//
		$school = Schools::findFirst(array("id = :id:", "bind" => array("id" => $this->session->get("student-school"))));
		if($school){
			$reqNote = unserialize($school->notifications);
				//-- unset the unrequired traits --//
			foreach($reqNote as $key => $val){
				if(!$val){
					unset($reqNote[$key]);
				}
			}
			$notes = $reqNote;
		}
		
		//-- Grab Traits Object --//
		$traits = array();
		$traits = CurriculumTraits::find('');
		foreach($traits as $trait){
			$traitList[$trait->url_name] = array('id' => $trait->id, 'name' => $trait->trait_name);
		}
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("traits", $traits);
		$this->view->setVar("traitList", $traitList);
		$this->view->setVar("notes", $notes);
    } //-- end studentsAction() --//
	
	
	public function parentsAction()
    {
		//-- Deny Access if no Priveleges -- Only PARENTS --//
		if($this->session->get("user-role") != 5){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
		$students = $notes = $traitList = array();
		
        //-- Grab all students of parent --//
		$relationships = ParentRelationship::find(array(
			"conditions" => "parent = :parentID: AND verified = 1",
			"bind" => array('parentID' => $this->session->get("user-id"))
		));
		if($relationships){
			foreach($relationships as $rel){
				$student = $rel->getStudents();
					//-- Grab traits that need to be graded from the student's school --//
					$school = Schools::findFirst(array("id = :id:", "bind" => array("id" => $student->school)));
					$reqNote = unserialize($school->notifications);
						//-- unset the unrequired traits --//
					foreach($reqNote as $key => $val){
						if(!$val){
							unset($reqNote[$key]);
						}
					}
					$notes[$student->id] = $reqNote;
				$students[] = $student;
			}
		}
		
		//-- Grab Traits Object --//
		$traits = array();
		$traits = CurriculumTraits::find('');
		foreach($traits as $trait){
			$traitList[$trait->url_name] = array('id' => $trait->id, 'name' => $trait->trait_name);
		}
		
		//-- Grab all students of parent --//
		$unverified = ParentRelationship::count(array(
			"conditions" => "parent = :parentID: AND verified = 0",
			"bind" => array('parentID' => $this->session->get("user-id"))
		));
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("traits", $traits);
		$this->view->setVar("traitList", $traitList);
        $this->view->setVar("students", $students);
		$this->view->setVar("notes", $notes);
		$this->view->setVar("unverified", $unverified);
    } //-- end parentsAction() --//
	
	
	public function verifychildAction(){
		
		//-- Deny Access if no Priveleges -- ONLY PARENTS --//
		if($this->session->get("user-role") != 5){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
		//-- Grab all students of parent --//
		$relationships = ParentRelationship::find(array(
			"conditions" => "parent = :parentID:",
			"bind" => array('parentID' => $this->session->get("user-id")),
			"order" => "verified DESC"
		));
		
		//-- Grab Student's Info --//
		$students = array();
		if($relationships){
			foreach($relationships as $rel){
				$student = $rel->getStudents();
				$students[$student->id] = $student;
			}
		}
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("relationships", $relationships);
        $this->view->setVar("students", $students);
		
	} //-- end verifychildAction --//
	
	
	public function updaterelationAction(){
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Verify Relationship --//
			if($this->request->getPost("action") == 'verify_child'){
				
				//-- grab / set vars --//
				$relID = $this->request->getPost("relID", "int");
				$results = array();
				
				//-- Verify only parent --//
				if($this->session->get("user-role") == 5){
					//-- Make sure all data is supplied --//
					if($relID && is_numeric($relID)){
						$rel = ParentRelationship::findFirst(array(
							"conditions" => "id = :relID: AND parent = :parentID:",
							"bind" => array('relID' => $relID, 'parentID' => $this->session->get("user-id"))
						));
						
						if($rel){
							/*-------------------------
								Update Relationship
							--------------------------*/
							$rel->verified = 1;
							
							//-- Save --//
							if($rel->save() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Verification Failed";
								$results["error_msg"] = "Could not update the relationship. Refresh page and try again. Contact Administrator if problem continues.";
							}else{
								$results["result"] = "success";
							}
						}else{
							$results["result"] = "failed";
							$results["error_title"] = "Something Went Wrong";
							$results["error_msg"] = "Couldn't find the relationship to update it. Refresh page and try again. Contact Administrator if problem continues.";
						}
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Something is Missing";
						$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page and trying again.";
					}
				}else{
					//no permissions
					$results["result"] = "failed";
					$results["error_title"] = "No permissions";
					$results["error_msg"] = "You have to be a parent to be able to verify a relationship with a student.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end verify_child --//
			
			//-- Function to Remove Student Relationship --//
			if($this->request->getPost("action") == 'remove_child'){
				
				//-- grab / set vars --//
				$relID = $this->request->getPost("relID", "int");
				$results = array();
				
				//-- only parents allowed --//
				if($this->session->get("user-role") == 5){
					//-- Make sure all data is supplied --//
					if($relID && is_numeric($relID)){
						$rel = ParentRelationship::findFirst(array(
							"conditions" => "id = :relID: AND parent = :parentID:",
							"bind" => array('relID' => $relID, 'parentID' => $this->session->get("user-id"))
						));
						
						if($rel){
							/*-------------------------
								Remove Relationship
							--------------------------*/
							if($rel->delete() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Removal Failed";
								$results["error_msg"] = "Could not remove the relationship. Refresh page and try again. Contact Administrator if problem continues.";
							}else{
								$results["result"] = "success";
							}
						}else{
							$results["result"] = "failed";
							$results["error_title"] = "Something Went Wrong";
							$results["error_msg"] = "Couldn't find the relationship to remove it. Refresh page and try again. Contact Administrator if problem continues.";
						}
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Something is Missing";
						$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page and trying again.";
					}
				}else{
					//no permissions
					$results["result"] = "failed";
					$results["error_title"] = "No permissions";
					$results["error_msg"] = "You have to be a parent to be able to remove a relationship with a student.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end remove_child --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end updaterelationAction() --//
	

	public function addsemesterAction()
    {
        //-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add Semester --//
			if($this->request->getPost("action") == 'add_semester'){
				//-- Sanitize Vars --//
				$semesterName = trim($this->request->getPost("theName", "string"));
				$results = array();

				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){

					//-- truncate vars to certain length --//
					if(strlen($semesterName) > 64){ $semesterName = substr($semesterName, 0, 64); }

					//-- Make sure the required info is present --//
					if($semesterName){
						/*---------------------------------------------
							Now Add Semester -- Passed all validation
						----------------------------------------------*/
						$semester = New Semesters();
						$semester->semesterName = $semesterName;
						$semester->active = 0;

						//-- Save Entry --//
						if($semester->save() == false){
							$results["result"] = "failed";
						}else{
							$results["result"] = "success";
							$results["semesterID"] = $semester->id;
							$results["semesterName"] = $semester->semesterName;
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
		
    } //-- end addsemesterAction() --//

	
	public function setsemesterAction()
    {
        //-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Set Semester --//
			if($this->request->getPost("action") == 'set_semester'){
				//-- Sanitize Vars --//
				$semesterID = $this->request->getPost("theID", "int");
				$results = array();

				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){

					//-- Make sure the required info is present --//
					if($semesterID && is_numeric($semesterID)){
						//-- clear old active semester --//
						$sems = Semesters::find(array("active = 1"));
						if($sems){
							foreach($sems as $sem){
								$sem->active = 0;
								if($sem->save() == false){
									$results['cleared'] = "failed";
								}else{
									$results['cleared'] = "success";
								}
							}
						}
						
						/*----------------------
							Now Set Semester
						-----------------------*/
						$semester = Semesters::findFirst(array("id = :id:", "bind" => array("id" => $semesterID)));
						$semester->active = 1;

						//-- Save Entry --//
						if($semester->save() == false){
							$results["result"] = "failed";
						}else{
							$results["result"] = "success";
							$this->session->set("current-semester", $semester->id);
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
		
    } //-- end setsemesterAction() --//
	
	
	public function renamesemesterAction()
    {
        //-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add Semester --//
			if($this->request->getPost("action") == 'rename_semester'){
				//-- Sanitize Vars --//
				$semesterID = $this->request->getPost("theID", "int");
				$semesterName = trim($this->request->getPost("theName", "string"));
				$results = array();

				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){

					//-- truncate vars to certain length --//
					if(strlen($semesterName) > 64){ $semesterName = substr($semesterName, 0, 64); }

					//-- Make sure the required info is present --//
					if($semesterID && is_numeric($semesterID) && $semesterName){
						//-- Grab Semester Object --//
						$semester = Semesters::findFirst(array("id = :id:", "bind" => array('id' => $semesterID)));
						/*----------------------------------------------
							Update Semester -- Passed all validation
						-----------------------------------------------*/
						$semester->semesterName = $semesterName;

						//-- Save Entry --//
						if($semester->save() == false){
							$results["result"] = "failed";
							$results["error_title"] = "Rename Failed";
							$results["error_msg"] = "Something went wrong, and the semester was not renamed.";
						}else{
							$results["result"] = "success";
							$results["semesterName"] = $semester->semesterName;
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Invalid Data";
						$results["error_msg"] = "Some data was not correct, could not update semester. Refresh page and try again.";
					}

				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Not Allowed";
					$results["error_msg"] = "Looks like you don't have permissions to perform this action.";
				}

				//-- encode results --//
				echo json_encode($results);
			}
		}

		//-- Disable View --//
		$this->view->disable();
		
    } //-- end renamesemesterAction() --//
	
	
	public function delsemesterAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete Semester --//
			if($this->request->getPost("action") == 'delete_semester'){
				
				//-- grab / set / sanitize vars --//
				$semesterID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){
					
					if($semesterID && is_numeric($semesterID)){
						//-- Grab School --//
						$semester = Semesters::findFirst(array(
							"conditions" => "id = :semesterID:",
							"bind" => array('semesterID' => $semesterID)
							));
							
						if($semester){
							/*---------------------------------------------------------------------
								if grades have been entered for semester - can't delete semester
							----------------------------------------------------------------------*/
							$gradeCheck = Grading::count(array("semester = :semesterID:", "bind" => array('semesterID' => $semester->id)));
							if(!$semester->active && !$gradeCheck){
								//-- Delete from DB --//
								if($semester->delete() == false){
								    $results['result'] = "failed";
									$results["error_title"] = "Failed to Delete Semester";
									$results["error_msg"] = "Something went wrong, and the semester was not deleted.";
								}else{
									$results["result"] = "success";
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Can't Delete Semester";
								$results["error_msg"] = "Semester is either the current semester or has grades already entered, and can't be deleted.";
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
		
	} //-- end delsemesterAction() --//
	
	
	public function grabnotificationsAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab Grading Notifications --//
			if($this->request->getPost("action") == 'grab_notifications'){
				
				//-- grab / set vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions -- only administrators allowed --//
				if($this->session->get("user-role") == 1){
					//-- make sure valid data --//
					if($schoolID && is_numeric($schoolID)){

						//-- Grab School --//
						$school = Schools::findFirst(array(
							"conditions" => "id = :schoolID:",
							"bind" => array('schoolID' => $schoolID)
							));

						//-- the school notifications json object --//
						if($school->notifications){
							//-- unserialize the notifications array --//
							$notifications = unserialize($school->notifications);
							if(is_array($notifications)){
								$results["result"] = "success";
								$results["notifications"] = $notifications;
							}else{
								$results["result"] = "failed";
							}
						}else{
							//-- Notifications are NULL and don't exist yet --//
							$results["result"] = "success";
							$results["notifications"] = array('grit' => 0, 'focus' => 0, 'optimism' => 0, 'curiosity' => 0, 'leadership' => 0, 'energy' => 0, 'courage' => 0, 'initiative' => 0, 'social' => 0, 'humility' => 0, 'integrity' => 0, 'creativity' => 0);
						}
					}else{
						//invalid input
						$results["result"] = "failed";
					}
				}else{
					//-- Permissions --//
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Notifications --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end grabnotificationsAction() --//
	
	
	public function sendnotificationsAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Send Grading Notifications --//
			if($this->request->getPost("action") == 'send_notifications'){
				
				//-- grab / set vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$trait = $this->request->getPost("trait", "string");
				$roles = $this->request->getPost("users");
				$results = array();
				$empty_notifications = array('grit' => 0, 'focus' => 0, 'optimism' => 0, 'curiosity' => 0, 'leadership' => 0, 'energy' => 0, 'courage' => 0, 'initiative' => 0, 'social' => 0, 'humility' => 0, 'integrity' => 0, 'creativity' => 0);
				
				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){
					//-- Sanitize user roles --//
					foreach($roles as $key => $val){
						$roles[$key] = $this->filter->sanitize($val, "int");
					}
					
					//-- truncate vars to certain length --//
					if(strlen($trait) > 32){ $trait = substr($trait, 0, 32); }
					
					//-- Make sure all data is supplied --//
					if($schoolID && is_numeric($schoolID) && $trait && $roles[0] != ''){

						//-- Grab School --//
						$school = Schools::findFirst(array(
							"conditions" => "id = :schoolID:",
							"bind" => array('schoolID' => $schoolID)
							));
						
						//-- Figure out notifications array --//
						if($school->notifications){
							//-- unserialize the notifications array --//
							$notifications = unserialize($school->notifications);
							if(!is_array($notifications)){
								$notifications = $empty_notifications;
							}
						}else{
							$notifications = $empty_notifications;
						}
						
						//-- update notifications array --//
						if(!$notifications[$trait]){
							$notifications[$trait] = 1;
							$school->notifications = serialize($notifications);
							
							//-- Save Entry --//
							if($school->save() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Failed to Update Notifications";
								$results["error_msg"] = "Something went wrong, and the school notifications were not updated.";
							}else{
								$results["result"] = "success";

								//-- Setup Mailgun Object --//
								$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
								
								//-- pull each user role object --//
								foreach($roles as $val){
									if(is_numeric($val)){
										if($val == 0){
											/*---------------------------
												Student Notifications
											----------------------------*/
											$students = Students::find(array("active = 1 AND school = :schoolID:", "bind" => array('schoolID' => $schoolID)));
											foreach($students as $student){
												if(!empty($student->email)){
													//-- Send Notification --//
													$to = $student->email;
													$subject = "Time to grade yourself on the character trait (".ucfirst($trait).")";
													$message = "It is time to grade your performance on the ".ucfirst($trait)." character trait. You can login to Athlos Tools by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nFeel free to contact your school or character coach and or teacher if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
													//-- Send MSG with Mailgun --//
													$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
													                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
													                        'to'      => $to,
													                        'subject' => $subject,
													                        'text'    => $message));
												}
											}
										}else if($val == 3 || $val == 4){
											/*----------------------------------------
												Teacher / Turf Coach Notifications
											-----------------------------------------*/
											$users = Users::find(array("role = :role: AND school = :schoolID:", "bind" => array('role' => $val, 'schoolID' => $schoolID)));
											foreach($users as $user){
												//-- Send Notification --//
												$to = $user->email;
												$subject = "Time to grade your students on the character trait (".ucfirst($trait).")";
												$message = "It is time to grade your students performance of the ".ucfirst($trait)." character trait. You can login to Athlos Tools by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nFeel free to contact your school or character coach if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
												//-- Send MSG with Mailgun --//
												$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
												                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
												                        'to'      => $to,
												                        'subject' => $subject,
												                        'text'    => $message));
											}
										}
									}
								}
							}
							
						}else{
							$results["result"] = "failed";
							$results["error_title"] = "Notifications Previously Sent";
							$results["error_msg"] = "Notifications for this trait have already been sent, and will not be sent a 2nd time.";
						}
						
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Something is Missing";
						$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page to and trying again.";
					}
				}else{
					//-- permissions issue --//
					$results["result"] = "failed";
					$results["error_title"] = "No Permissions";
					$results["error_msg"] = "Sorry. It seems you don't have permissions to send notifications.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Send_Notifications --//
			
			//-- Function to Send Parent Notifications --//
			if($this->request->getPost("action") == 'send_to_parents'){
				
				//-- grab / set vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){
					
					//-- Make sure all data is supplied --//
					if($schoolID && is_numeric($schoolID)){
						//-- Success --//
						$results["result"] = "success";
						
						//-- Setup Mailgun Object --//
						$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
						
						/*-------------------------
							Parent Notification
						--------------------------*/
						//-- Grab Students in school --//
						$students = Students::find(array(
							"conditions" => "active = 1 AND school = :schoolID:",
							"bind" => array('schoolID' => $schoolID)
							));
						
						//-- Grab all parents --//
						foreach($students as $student){
							foreach($student->parentRelationship as $rel){
								$parent = $rel->getUsers();
								if($parent){
									//-- Send Notification --//
									$to = $parent->email;
									$subject = "Time to grade your student";
									$message = "It is time to log in and grade your student on the character traits learned in the Athlos Curriculum. You can login to Athlos Tools by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nFeel free to contact your school if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
									//-- Send MSG with Mailgun --//
									$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
									                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
									                        'to'      => $to,
									                        'subject' => $subject,
									                        'text'    => $message));
								} 
							}
						}
						
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Something is Missing";
						$results["error_msg"] = "Please make sure a school is selected. Possibly try refreshing the page and trying again.";
					}
				}else{
					//-- permissions issue --//
					$results["result"] = "failed";
					$results["error_title"] = "No Permissions";
					$results["error_msg"] = "Sorry. It seems you don't have permissions to send notifications.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end send_to_parents --//
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end sendnotificationsAction() --//
	
	
	public function resendnotificationsAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Resend Grading Notifications --//
			if($this->request->getPost("action") == 'resend_notifications'){
				
				//-- grab / set vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$trait = $this->request->getPost("trait", "string");
				$roles = $this->request->getPost("users");
				$results = array();
				
				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){
					//-- Sanitize user roles --//
					foreach($roles as $key => $val){
						$roles[$key] = $this->filter->sanitize($val, "int");
					}
					
					//-- truncate vars to certain length --//
					if(strlen($trait) > 32){ $trait = substr($trait, 0, 32); }
					
					//-- Make sure all data is supplied --//
					if($schoolID && is_numeric($schoolID) && $trait && $roles[0] != ''){

						$results["result"] = "success";

						//-- Setup Mailgun Object --//
						$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
						
						//-- pull each user role object --//
						foreach($roles as $val){
							if(is_numeric($val)){
								if($val == 0){
									/*---------------------------
										Student Notifications
									----------------------------*/
									$students = Students::find(array("active = 1 AND school = :schoolID:", "bind" => array('schoolID' => $schoolID)));
									foreach($students as $student){
										if(!empty($student->email)){
											//-- Send Notification --//
											$to = $student->email;
											$subject = "Time to grade yourself on the character trait (".ucfirst($trait).")";
											$message = "It is time to grade your performance on the ".ucfirst($trait)." character trait. You can login to Athlos Tools by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nFeel free to contact your school or character coach and or teacher if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
											//-- Send MSG with Mailgun --//
											$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
											                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
											                        'to'      => $to,
											                        'subject' => $subject,
											                        'text'    => $message));
										}
									}
								}else if($val == 3 || $val == 4){
									/*----------------------------------------
										Teacher / Turf Coach Notifications
									-----------------------------------------*/
									$users = Users::find(array("role = :role: AND school = :schoolID:", "bind" => array('role' => $val, 'schoolID' => $schoolID)));
									foreach($users as $user){
										//-- Send Notification --//
										$to = $user->email;
										$subject = "Time to grade your students on the character trait (".ucfirst($trait).")";
										$message = "It is time to grade your students performance of the ".ucfirst($trait)." character trait. You can login to Athlos Tools by going to this url: https://".$_SERVER['HTTP_HOST']."\n\nFeel free to contact your school or character coach if you have any problems logging in.\n\nThanks again,\n\n\t- Athlos Tools";
										//-- Send MSG with Mailgun --//
										$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
										                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
										                        'to'      => $to,
										                        'subject' => $subject,
										                        'text'    => $message));
									}
								}
							}
						}
						
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Something is Missing";
						$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page and trying again.";
					}
				}else{
					//-- permissions issue --//
					$results["result"] = "failed";
					$results["error_title"] = "No Permissions";
					$results["error_msg"] = "Sorry. It seems you don't have permissions to send notifications.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end resendnotificationsAction() --//
	
	
	public function resetnoteAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Send Grading Notifications --//
			if($this->request->getPost("action") == 'reset_notifications'){
				
				//-- grab / set vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions -- only admin & character coaches allowed --//
				if($this->session->get("user-role") == 1 || $this->session->get("user-role") == 2){
					
					//-- Make sure all data is supplied --//
					if($schoolID && is_numeric($schoolID)){

						//-- Grab School --//
						$school = Schools::findFirst(array(
							"conditions" => "id = :schoolID:",
							"bind" => array('schoolID' => $schoolID)
							));
						
						//-- Set blank notifications array --//
						$notifications = array('grit' => 0, 'focus' => 0, 'optimism' => 0, 'curiosity' => 0, 'leadership' => 0, 'energy' => 0, 'courage' => 0, 'initiative' => 0, 'social' => 0, 'humility' => 0, 'integrity' => 0, 'creativity' => 0);
						$school->notifications = serialize($notifications);
						
						//-- Save Entry --//
						if($school->save() == false){
							$results["result"] = "failed";
							$results["error_title"] = "Failed to Reset Notifications";
							$results["error_msg"] = "Something went wrong, and the school notifications were not reset.";
						}else{
							$results["result"] = "success";
						}
						
					}else{
						//invalid input
						$results["result"] = "failed";
						$results["error_title"] = "Something is Missing";
						$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page to and trying again.";
					}
				}else{
					//-- permissions issue --//
					$results["result"] = "failed";
					$results["error_title"] = "No Permissions";
					$results["error_msg"] = "Sorry. It seems you don't have permissions to reset notifications.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Notifications --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end resetnoteAction() --//
	
	
	public function reviewtraitAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Review Grades --//
			if($this->request->getPost("action") == 'review_grades'){
				
				//-- grab / set vars --//
				$traitID = $this->request->getPost("theID", "int");
				$studentID = $this->request->getPost("studentID", "int");
				$results = array();
				
				//-- Make sure all data is supplied --//
				if($traitID && is_numeric($traitID) && $studentID && is_numeric($studentID)){

					//-- Grab Character Trait --//
					$trait = CurriculumTraits::findFirst(array(
						"conditions" => "id = :traitID:",
						"bind" => array('traitID' => $traitID)
						));
					
					//-- Grab Any Grades --//
					$conditions = "semester = :sem: AND student = :studentID:";
					$bindArray = array('sem' => $this->session->get("current-semester"), 'studentID' => $studentID);
					//-- figure out grades --//
					$grades = Grading::find(array($conditions, "bind" => $bindArray, "order" => "eval_role DESC"));
					foreach($grades as $grade){
						if($grade->eval_role == 3){ $roleName = 'Teacher'; }
						else if($grade->eval_role == 4){ $roleName = 'Turf Coach'; }
						else if($grade->eval_role == 5){ $roleName = 'Parent'; }
						else if($grade->eval_role == 0){ $roleName = 'Student'; }
						else{ $roleName = ''; }
						//-- unserialize observations --//
						if($grade->observations){
							//-- unserialize the observations array --//
							$observations = unserialize($grade->observations);
							if(!is_array($observations)){
								$observations = array();
							}
						}
						if(!empty($observations[$trait->url_name])){
							$results["grades"][] = array('id' => $grade->id, 'role' => $roleName, 'grade' => $grade->{$trait->url_name}, 'reaction' => $observations[$trait->url_name][1], 'observation' => htmlspecialchars_decode($observations[$trait->url_name][0], ENT_QUOTES));
						}else{
							$results["grades"][] = array('id' => $grade->id, 'role' => $roleName, 'grade' => $grade->{$trait->url_name}, 'reaction' => '', 'observation' => '');
						}
					}
					
					//-- JSON Object --//
					$results["result"] = "success";
					$results["trait"] = $trait;
					
				}else{
					//invalid input
					$results["result"] = "failed";
					$results["error_title"] = "Something is Missing";
					$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page to and trying again.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Notifications --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end reviewtraitAction() --//
	
	
	public function grabtraitAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab Trait Details --//
			if($this->request->getPost("action") == 'grab_trait'){
				
				//-- grab / set vars --//
				$traitID = $this->request->getPost("theID", "int");
				$studentID = $this->request->getPost("studentID", "int");
				$results = array();
				
				//-- Make sure all data is supplied --//
				if($traitID && is_numeric($traitID) && $studentID && is_numeric($studentID)){

					//-- Grab Character Trait --//
					$trait = CurriculumTraits::findFirst(array(
						"conditions" => "id = :traitID:",
						"bind" => array('traitID' => $traitID)
						));
					
					//-- Grab Any Grades --//
					$conditions = "semester = :sem: AND student = :studentID:";
					$bindArray = array('sem' => $this->session->get("current-semester"), 'studentID' => $studentID);
					if($this->session->get("user-role") == 3){
						$conditions.= " AND (eval_role = 3 OR eval_role = 0 OR eval_role = 5)"; //-- Teacher --//
					}else if($this->session->get("user-role") == 4){
						$conditions.= " AND eval_role = 4"; //-- Turf Coach --//
					}else if($this->session->get("user-role") == 5){
						$conditions.= " AND eval_role = 5"; //-- Parent --//
					}else if($this->session->get("user-role") == 100){
						$conditions.= " AND eval_role = 0"; //-- Student --//
					}
					//-- figure out grades --//
					$grades = Grading::find(array($conditions, "bind" => $bindArray, "order" => "eval_role DESC"));
					foreach($grades as $grade){
						if($grade->eval_role == 3){ $roleName = 'Teacher'; }
						else if($grade->eval_role == 4){ $roleName = 'TurfCoach'; }
						else if($grade->eval_role == 5){ $roleName = 'Parent'; }
						else if($grade->eval_role == 0){ $roleName = 'Student'; }
						else{ $roleName = ''; }
						//-- unserialize observations --//
						if($grade->observations){
							//-- unserialize the observations array --//
							$observations = unserialize($grade->observations);
							if(!is_array($observations)){
								$observations = array();
							}
						}
						
						$results["grades"][] = array('id' => $grade->id, 'role' => $roleName, 'grade' => $grade->{$trait->url_name}, 'observation' => htmlspecialchars_decode($observations[$trait->url_name][0], ENT_QUOTES));
					}
					
					//-- JSON Object --//
					$results["result"] = "success";
					$results["trait"] = $trait;
					
				}else{
					//invalid input
					$results["result"] = "failed";
					$results["error_title"] = "Something is Missing";
					$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page to and trying again.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Notifications --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end grabtraitAction() --//
	
	
	public function submitgradeAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Submit Grade --//
			if($this->request->getPost("action") == 'submit_grade'){
				
				//-- grab / set vars --//
				$gradeID = $this->request->getPost("theID", "int");
				$studentID = $this->request->getPost("studentID", "int");
				$grade = $this->request->getPost("grade", "int");
				$trait = trim($this->request->getPost("trait", "string"));
				$role = trim($this->request->getPost("role", "string"));
				$observation = trim($this->request->getPost("observation", "string"));
				$reaction = trim($this->request->getPost("reaction", "string"));
				$level = $this->request->getPost("level", "int");
				$type = trim($this->request->getPost("type", "string"));
				$results = array();
				
				if($reaction == 'negative'){ $attitude = 0; }else{ $attitude = 1; }
				
				//-- Make sure all data is supplied --//
				if($studentID && is_numeric($studentID) && $trait && $role && $level != '' && is_numeric($level)){
					
					if(($level < 4 && $observation) || ($level > 3 && $grade)){
						
						//-- truncate vars to certain length --//
						if(strlen($grade) > 1){ $grade = substr($grade, 0, 1); }
						if(strlen($trait) > 32){ $trait = substr($trait, 0, 32); }

						//-- Grab Grading Object --//
						if($gradeID){
							$conditions = "id = :id:";
							$bindArray = array('id' => $gradeID);
						}else{
							$conditions = "semester = :sem: AND student = :studentID:";
							$bindArray = array('sem' => $this->session->get("current-semester"), 'studentID' => $studentID);

							if($role == 'staff'){
								if($this->session->get("user-role") == 3){
									$conditions.= " AND eval_role = 3"; //-- Teacher --//
								}else if($this->session->get("user-role") == 4){
									$conditions.= " AND eval_role = 4"; //-- Turf Coach --//
								}
							}else if($role == 'turf coach'){
								$conditions.= " AND eval_role = 4";
							}else if($role == 'teacher'){
								$conditions.= " AND eval_role = 3";
							}else if($role == 'student'){
								$conditions.= " AND eval_role = 0";
							}else if($role == 'parent'){
								$conditions.= " AND eval_role = 5";
							}
						}

						$grading = Grading::findFirst(array($conditions, "bind" => $bindArray));
						//-- Create new object if grade doesn't already exist --//
						if(!$grading){
							$grading = new Grading();
							//-- Set char traits --//
							$grading->grit = 0;
							$grading->focus = 0;
							$grading->optimism = 0;
							$grading->curiosity = 0;
							$grading->leadership = 0;
							$grading->energy = 0;
							$grading->courage = 0;
							$grading->initiative = 0;
							$grading->social = 0;
							$grading->humility = 0;
							$grading->integrity = 0;
							$grading->creativity = 0;
							
							//-- observations --//
							$observations = array('grit' => array(), 'focus' => array(), 'optimism' => array(), 'curiosity' => array(), 'leadership' => array(), 'energy' => array(), 'courage' => array(), 'initiative' => array(), 'social' => array(), 'humility' => array(), 'integrity' => array(), 'creativity' => array());
							if($observation){
								$observations[$trait] = array($observation, $attitude);
							}

							//-- set values --//
							$grading->semester = $this->session->get("current-semester");
							if($type == 'new'){
								if($role == 'student'){
									$grading->evaluator = $studentID;
								}else if($role == 'teacher'){
									//-- Check for assigned teacher --//
									$student = Students::findFirst(array("id = :sid:", "bind" => array('sid' => $studentID)));
									if($student){
										$grading->evaluator = $student->teacher;
									}else{
										$results["result"] = "failed";
										$results["error_title"] = "No Teacher Assigned";
										$results["error_msg"] = "The student must have a teacher assigned to be able to submit a teacher grade.";
									}
								}else if($role == 'parent'){
									//-- Check for assigned parent --//
									/*$rel = ParentRelationship::findFirst(array("student = :sid: AND verified = 1", "bind" => array('sid' => $studentID)));
									if($rel){
										$grading->evaluator = $rel->parent;
									}else{
										$results["result"] = "failed";
										$results["error_title"] = "No Parent Assigned";
										$results["error_msg"] = "The student must have a parent assigned to be able to submit a parent grade.";
									}*/
									$grading->evaluator = $this->session->get("user-id"); //-- Temporary fix until parents are assigned. --//
								}else{
									$grading->evaluator = $this->session->get("user-id");
								}
							}else{
								//-- Make sure if student submits, they get assigned as the evaluator --//
								if($this->session->get("user-id")){
									$grading->evaluator = $this->session->get("user-id");
								}else{
									$grading->evaluator = $this->session->get("student-id");
								}
							}
							$grading->student = $studentID;
							if($role == 'student'){
								$grading->eval_role = 0;
							}else if($role == 'turf coach'){
								$grading->eval_role = 4;
							}else if($role == 'teacher'){
								$grading->eval_role = 3;
							}else if($role == 'parent'){
								$grading->eval_role = 5;
							}else{
								$grading->eval_role = $this->session->get("user-role");
							}
							//-- Set Student School --//
							$aStudent = Students::findFirst(array("id = :sid:", "bind" => array('sid' => $studentID)));
							if(!empty($aStudent)){
								$grading->school = $aStudent->school;
								$grading->grade_level = $aStudent->grade;
							}else{
								$grading->school = 0;
							}

						}else{
							//-- unserialize observations --//
							if($grading->observations){
								//-- unserialize the observations array --//
								$observations = unserialize($grading->observations);
								if(!is_array($observations)){
									//-- hold off for now... but should be false error --//
								}
							}
							if($observation){
								$observations[$trait] = array($observation, $attitude);
							}
						}

						//-- info saved on update --//
						$grading->observations = serialize($observations);
						if($grade){
							$grading->{$trait} = $grade;
						}
						
						//-- if no errors --//
						if($results["result"] !== "failed"){
							
							//-- Save Entry --//
							if($grading->save() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Grade Failed";
								$results["error_msg"] = "Something went wrong, and the grade was not entered.";
								//$results["entry"] = $grading;
								foreach($grading->getMessages() as $message){
									$results['msg'][] = $message->getMessage();
								}
							}else{
								$results["result"] = "success";
								$results["entry"] = $grading->id;
							}
							
						}
						
					}else{
						$results["result"] = "failed";
						$results["error_title"] = "Invalid Observation / Grade";
						$results["error_msg"] = "Make sure that the grade and or observation is entered correctly.";
					}
				}else{
					//invalid input
					$results["result"] = "failed";
					$results["error_title"] = "Something is Missing";
					$results["error_msg"] = "Please make sure all fields are entered. Possibly try refreshing the page and trying again.";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Notifications --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		 
	} //-- end submitgradeAction() --//
	
	
	public function reportcardAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Generate Report Cards --//
			if($this->request->getPost("action") == 'generate-reportcards'){
				
				//-- grab / set vars --//
				$cards = $this->request->getPost("cards", "string");
				
				//-- turn into array --//
				$students = explode(',', $cards);
				
				//-- Include mPDF Library --//
				require "../app/controllers/mpdf/mpdf.php";
				$mpdf = new mPDF();

				if(!empty($students)){
					//-- Set Footer --//
					$mpdf->SetHTMLFooter('<p style="text-align: center; font-size: 14px; line-height: 12px;"><img src="img/logos/athlos_logo_mark.png" width="50" /><br /><br />https://tools.athlosacademies.org</p>');
					
					//-- Grab PDF Content from db --//
					$pdfContent = Options::findFirst("option = 'report-card-content'");
					
					$num = 0;
					//-- Run through and generate card for each student --//
					foreach($students as $studentID){
						//-- If is valid number --//
						if($studentID && is_numeric($studentID)){
							//-- Create New page if not the first page --//
							if($num != 0){
								$mpdf->AddPage();
							} $num++;
							
							//-- Start fresh pdfcontent --//
							$output = $pdfContent->value;
							
							//-- Grab Student & Teacher Info --//
							$sInfo = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $studentID)));
							$tInfo = Users::findFirst(array("id = :tid:", "bind" => array("tid" => $sInfo->teacher)));
							$schoolInfo = Schools::findFirst(array("id = :schoolid:", "bind" => array("schoolid" => $sInfo->school)));
							
							//-- School Logo --//
							if($schoolInfo->ilt_school){ $Logo = 'img/logos/ilt-logo.png'; }else{ $Logo = 'img/logos/athlos_logo_mark.png'; }
							
							//-- Figure out Grades --//
							$averages = array();
							$chartraits = array('grit', 'focus', 'optimism', 'curiosity', 'leadership', 'energy', 'courage', 'initiative', 'social', 'humility', 'integrity', 'creativity');
							foreach($chartraits as $ct){
								$averages[$ct] = Grading::average(array(
									"column" => $ct,
									"conditions" => "student = :sid: AND semester = :sem: AND ".$ct." != 0",
									"bind" => array("sid" => $studentID, "sem" => $this->session->get("current-semester"))
								));
							}
							//-- Calculate Cumulative --//
							$countAVG = $sumAVG = 0;
							foreach($averages as $avg){
								if($avg >= 1){
									$sumAVG = $sumAVG + $avg;
									$countAVG++;
								}
							}
							if($countAVG > 0){
								$cumTotal = $sumAVG / $countAVG;
							}else{
								$cumTotal = 0;
							}
							
							//-- Grab All Observation Comments --//
							$query = "SELECT eval_role, observations FROM grading WHERE student = :sID AND semester = :sem";
							$theGrades = $this->db->fetchAll($query, Phalcon\Db::FETCH_ASSOC, array('sID' => $studentID, 'sem' => $this->session->get("current-semester")));
							$observations = array();
							foreach($theGrades as $aGrade){
								$temp = unserialize($aGrade['observations']);
								if(!empty($temp)){
									//-- go through each trait --//
									foreach($temp as $comment){
										if(!empty($comment)){
											$comment[2] = $aGrade['eval_role'];
											$observations[] = $comment;
										}
									}
								}
							}
							
							//-- List out Comments --//
							$commentOutput = '';
							$currComment = 0;
							$eval_roles = array(0 => 'Student', 3 => 'Home Room Teacher', 4 => 'Turf Coach', 5 => 'Parent');
							$commentCount = count($observations);
							if($commentCount > 0){
								foreach($observations as $comment){
									$currComment++;
									if($currComment == $commentCount){
										if($comment[1]){
											$commentOutput.= '<p style="margin: 0 0 5px; padding: 0; font-size: 14px;"><span class="staff-comment">"'.$comment[0].'"</span> <span class="staff-name" style="color: #5b5b5b;">- '.$eval_roles[$comment[2]].'</span></p>';
										}else{
											$commentOutput.= '<p style="margin: 0 0 5px; padding: 0; font-size: 14px;"><span class="staff-comment">- - An additional comment has been made regarding your student, please see your home room teacher for more information.</span></p>';
										}
									}else{
										if($comment[1]){
											$commentOutput.= '<p style="margin: 0 0 10px; padding: 0 0 10px; font-size: 14px; border-bottom: 1px dashed #7d7d7d;"><span class="staff-comment">"'.$comment[0].'"</span> <span class="staff-name" style="color: #5b5b5b;">- '.$eval_roles[$comment[2]].'</span></p>';
										}else{
											$commentOutput.= '<p style="margin: 0 0 10px; padding: 0 0 10px; font-size: 14px; border-bottom: 1px dashed #7d7d7d;"><span class="staff-comment">- - An additional comment has been made regarding your student, please see your home room teacher for more information.</span></p>';
										}
									}
								}
							}
							
							/*-------------------------------------
								Replace Vars with Values in PDF
							--------------------------------------*/
							$search = array('[*ATHLOS-GRADE*]', '[*SCHOOL-LOGO*]', '[*STUDENT-NAME*]', '[*STUDENT-GRADE*]', '[*TEACHER-NAME*]', '[*GRIT*]', '[*FOCUS*]', '[*OPTIMISM*]', '[*CURIOSITY*]', '[*LEADERSHIP*]', '[*ENERGY*]', '[*COURAGE*]', '[*INITIATIVE*]', '[*SOCIAL*]', '[*HUMILITY*]', '[*INTEGRITY*]', '[*CREATIVITY*]');
							$replace = array(round($cumTotal, 2), $Logo, $sInfo->fname.' '.$sInfo->lname, $sInfo->grade, $tInfo->lname, round($averages['grit'], 1), round($averages['focus'], 1), round($averages['optimism'], 1), round($averages['curiosity'], 1), round($averages['leadership'], 1), round($averages['energy'], 1), round($averages['courage'], 1), round($averages['initiative'], 1), round($averages['social'], 1), round($averages['humility'], 1), round($averages['integrity'], 1), round($averages['creativity'], 1));
							$output = str_ireplace($search, $replace, $output);
							
							//-- Write Student's Report Card --//
							$mpdf->WriteHTML($output);
							
							//-- Add Comments to 2nd page, if there are any comments --//
							if($commentOutput){
								$mpdf->AddPage();
								$commentOutput = '<!-- COMMENTS --><div id="staff-comments" style="float: left; margin: 0 0 20px; padding: 10px; width: 100%; border: 1px solid #000;"><h3 style="margin: 0 0 10px; font-size: 18px; font-weight: bold;">Comments:</h3>'.$commentOutput.'</div><!-- END COMMENTS -->';
								$mpdf->WriteHTML($commentOutput);
							}
						}
					}
					
					//-- Output PDF Data --//
					$mpdf->Output();
					exit;
				}
			}
		}else{
			//-- Nothing posted --//
			return $this->response->redirect("grading/staff");
		}
		
		//-- Disable View --//
		$this->view->disable();

	} //-- end reportcardAction() --//
	
	
	public function csvexportAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete User --//
			if($this->request->getPost("action") == 'CSVExport'){
				
				//$page = $this->request->getPost("CSVPage");
				$currUrl = parse_url($_SERVER['REQUEST_URI']);
				
				$columns = "student ID,student,trait,observations,attitude";
				
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
				
				//Grab Results and Export CSV -- student
				if(1){
					
					//-- Figure out filter conditions --//
					$conditions = "";
					
					if($this->session->get('user-school') != 0){
						if($conditions == ''){
							$conditions.= "school = ".$this->session->get('user-school');
						}else{
							$conditions.= " AND school = ".$this->session->get('user-school');
						}
					}
					
					if($this->session->get('user-role') == 4 && isset($teacherID)){
						if($conditions == ''){
							$conditions.= "teacher = ".$teacherID;
						}else{
							$conditions.= " AND teacher = ".$teacherID;
						}
					}else if($this->session->get('user-role') == 3){
						if($conditions == ''){
							$conditions.= "teacher = ".$this->session->get('user-id');
						}else{
							$conditions.= " AND teacher = ".$this->session->get('user-id');
						}
					}
					//$statement = $this->db->prepare('SELECT grades FROM athlos_tools.grade_limit WHERE trufcoach = :ID;');
					//error_log("before");
					$gradeLimits = $this->db->fetchAll("SELECT grades FROM athlos_tools.grade_limit WHERE user = :ID", Phalcon\Db::FETCH_ASSOC, array('ID' => $this->session->get('user-id')));
					

					if($gradeLimits){
						$gradeList = $gradeLimits[0]["grades"];
						$gradeLimits = explode(",", $gradeLimits[0]["grades"]);
					}
					
					//error_log(print_r($gradeLimits,TRUE));	
					if(isset($grade) && $this->session->get('user-role') == 4 && (in_array($grade, $gradeLimits)) ){
						if($conditions == ''){
							$conditions.= "grade = ".$grade;
						}else{
							$conditions.= " AND grade = ".$grade;
						}

					}else if($this->session->get('user-role') == 4){
						if($conditions == ''){
							$conditions.= "grade IN (".$gradeList.")";
						}else{
							$conditions.= " AND grade IN (".$gradeList.")";
						}
					}else{
						if($this->session->get('user-grade') != ""){
							if($conditions == ''){
								$conditions.= "grade = ".$this->session->get('user-grade');
							}else{
								$conditions.= " AND grade = ".$this->session->get('user-grade');
							}
						}

					}
					if(isset($ids) && $ids){
						if($conditions == ''){
							$conditions.= "id IN (".$ids.")";
						}else{
							$conditions.= " AND id IN (".$ids.")";
						}
					}
					
					//get users results
					$entries = Students::find(array($conditions));
					//$statement = $this->db->prepare('SELECT grading.id AS id, grading.semester AS semester, CONCAT_WS(' ', users.fname, users.lname) AS evaluator, grading.evaluator AS evaluatorID, grading.eval_role AS eval_role, CONCAT_WS(' ', students.fname, students.lname) AS student, grading.student AS studentID, grading.grit AS grit, grading.focus AS focus, grading.optimism AS optimism, grading.curiosity AS curiosity, grading.leadership AS leadership, grading.energy AS energy, grading.courage AS courage, grading.initiative AS initiative, grading.social AS social, grading.humility AS humility, grading.integrity AS integrity, grading.creativity AS creativity FROM athlos_tools.grading, athlos_tools.students, athlos_tools.users, athlos_tools.schools WHERE athlos_tools.students.id = athlos_tools.grading.student AND athlos_tools.schools.id = athlos_tools.students.school AND athlos_tools.users.id = athlos_tools.grading.evaluator;');
					//$pdoResult = $this->db->executePrepared($statement,array())
				}
				
				
				//print_r($users);
				
				//send CSV File Headers
				$CSVFilename = "Athlos_Grading_Template_" . time() . ".csv";
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
						switch($col){
							case "student ID":
								$tempArray[$col] = $row->id;
								break;
							case "student":
								$tempArray[$col] = $row->fname." ".$row->lname;
								break;
							case "trait":
								$tempArray[$col] = "0";
								break;
							case "observations":
								$tempArray[$col] = "";
								break;
							case "attitude":
								$tempArray[$col] = "";
								break;
						}
						//$tempArray[$col] = $row->$col;
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
				
				$columns = "student ID,student,trait,observations,attitude";
				//read CSV file contents
				$traitList = array("grit","focus","optimism","curiosity","leadership","energy","courage","initiative","social","humility","integrity","creativity");
				$csvFile = dirname($_SERVER['SCRIPT_FILENAME']).'/../app/controllers/fileUpload/files/'.$this->request->getPost("name");
				$csvFileName = $this->request->getPost("name");
				$userType = "Grade(s)";
				$row = 0;
				$recordsAdded = 0;
				$recordsUpdated = 0;
				$cols = explode(",",$columns);
				if (($handle = fopen($csvFile, "r")) !== FALSE) {
				    while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
						//$results["data"] = $data;
						if($row!=0){
							
				        	if(count($data) == 5){
								//if the first variable is a number (ID) then start the process
								if(is_numeric($data[0]) && is_numeric($data[2]) && ($data[2] > 0 && $data[2] < 6)){

									//-- truncate vars to certain length --//
									if(strlen($data[2]) > 1){ $data[2] = substr($data[2], 0, 1); }

										$conditions = "semester = :sem: AND student = :studentID: AND eval_role = :role:";
										$bindArray = array('sem' => $this->session->get("current-semester"), 'studentID' => $data[0], 'role' => $this->session->get("user-role"));
											
										$grading = Grading::findFirst(array($conditions, "bind" => $bindArray));
										//-- Create new object if grade doesn't already exist --//
										if(!$grading){
											$grading = new Grading();
											//-- Set char traits --//
											$x=0;
											foreach($traitList AS $tr){
												if($x == $this->request->getPost("trait") - 1){
													$grading->{$tr} = $data[2];
												}else{
													$grading->{$tr} = 0;
												}
												$x++;
											}

											//-- observations --//
											$observations = array('grit' => array(), 'focus' => array(), 'optimism' => array(), 'curiosity' => array(), 'leadership' => array(), 'energy' => array(), 'courage' => array(), 'initiative' => array(), 'social' => array(), 'humility' => array(), 'integrity' => array(), 'creativity' => array());
											if($data[3] != ""){
												$observations[$traitList[$this->request->getPost("trait") - 1 ]] = array($data[3], $data[4]);
											}

											//-- set values --//
											$grading->semester = $this->session->get("current-semester");
											$grading->evaluator = $this->session->get("user-id");
											$grading->student = $data[0];
											$grading->eval_role = $this->session->get("user-role");
											
											$recordsAdded++;
										}else{
											//-- unserialize observations --//
											if($grading->observations){
												//-- unserialize the observations array --//
												$observations = unserialize($grading->observations);
												if(!is_array($observations)){
													//-- hold off for now... but should be false error --//
												}
											}
											if($data[3] != ""){
												$observations[$traitList[$this->request->getPost("trait") - 1 ]] = array($data[3], $data[4]);
											}
											$recordsUpdated++;
										}

										//-- info saved on update --//
										$grading->observations = serialize($observations);
										if($data[2] != ""){
											$grading->{$traitList[$this->request->getPost("trait") - 1 ]} = $data[2];
										}

										//-- Save Entry --//
										if($grading->save() == false){
											if(isset($results["resultFailed"])){
												$results["resultFailed"] .= ",".$data[1];
											}else{
												$results["resultFailed"] = $data[1];
											}
											foreach($grading->getMessages() as $message){
												$results['msg'][] = $message->getMessage();
											}
										}else{
											if(isset($results["resultSucceeded"])){
												$results["resultSucceeded"] .= ",".$data[1];
											}else{
												$results["resultSucceeded"] = $data[1];
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
				$results["recordsUpdated"] = $recordsUpdated;
				$results["recordsAdded"] = $recordsAdded;
				$results["userType"] = $userType;
				if(isset($updated)){
					$results["updatedRecords"] = $updated;
				}
				$results["rows"] = $row;
				
				echo json_encode($results);
				exit();
				
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end csvimportAction() --//
	
}
