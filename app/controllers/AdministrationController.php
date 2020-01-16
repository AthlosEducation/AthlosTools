<?php

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class AdministrationController extends \Phalcon\Mvc\Controller
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
		if(!$this->cap['administration']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Administration");
	}
	
	public function indexAction()
    {	
		//-- Grab Semesters Object --//
		$semesters = Semesters::find(array("", "order" => "id DESC"));
		
		//-- Pass Objects to View --//
        $this->view->setVar("semesters", $semesters);
		
	} //-- end indexAction --//
	
	public function newyearAction()
	{
		//-- Grab Current Semester Object --//
		$activeSemester = Semesters::findFirst(array("active = 1"));
		//-- Districts --//
		$districts = Districts::find(array("", "order" => "id ASC"));
		//-- Setup Campus Array --//
		$campuses = array();
		foreach($districts as $district){
			$campusList = Schools::find(array("district = :dist:", "bind" => array("dist" => $district->id), "order" => "schoolName ASC"));
			if($campusList){
				foreach($campusList as $campus){
					$campuses[$district->id][] = array($campus->id, $campus->schoolName);
				}
			}
		}
		
		//-- Pass Objects to View --//
        $this->view->setVar("activeSemester", $activeSemester->semesterName);
		$this->view->setVar("districts", $districts);
		$this->view->setVar("campuses", $campuses);
	} //-- END: newyearAction() --//
	
	public function clearrostersAction()
	{
        //-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Clear Rosters --//
			if($this->request->getPost("action") == 'unenroll_students'){
				$results = array();

				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){

					/*---------------------------------------------------------------------
						Now Remove Coach / Teacher Associations && Unenroll All Students
					----------------------------------------------------------------------*/
					$failCount = 0;
					$allStudents = Students::find(array(''));
					if($allStudents){
						$totalStudents = count($allStudents);
						foreach($allStudents as $student){
							$student->active = 0;
							$student->teacher = 0;
							$student->coach = null;
							$student->turf_period = 0;
							//-- Save Changes --//
							if($student->save() == false){
								$failCount++;
							}
						}
						//-- if any fails, show error message --//
						if($failCount > 0){
						    $results['result'] = "failed";
							$results["error_title"] = "Clearing Rosters Failed";
							$results["error_msg"] = "Something went wrong during un-enrollment &amp; clearing rosters. ".$failCount." / ".$totalStudents." Student Updates Failed!";
						}else{
							$results["result"] = "success";
							$results["failed"] = $failCount;
							$results["count"] = $totalStudents;
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
		
	} //-- END: clearrostersAction() --//
	
	public function addsemesterAction()
    {
        //-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add Semester --//
			if($this->request->getPost("action") == 'add_semester'){
				//-- Sanitize Vars --//
				$semesterName = trim($this->request->getPost("theName", "string"));
				$results = array();

				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){

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

				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){

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

				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){

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
							$results["error_msg"] = "Something went wrong, and the school year was not renamed.";
						}else{
							$results["result"] = "success";
							$results["semesterName"] = $semester->semesterName;
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Invalid Data";
						$results["error_msg"] = "Some data was not correct, could not update school year. Refresh page and try again.";
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
				
				//-- Verify Permissions --//
				if($this->cap['administration']['manage']){
					
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
							$gradeCheck = AthleticGrading::count(array("semester = :semesterID:", "bind" => array('semesterID' => $semester->id)));
							if(!$semester->active && !$gradeCheck){
								//-- Delete from DB --//
								if($semester->delete() == false){
								    $results['result'] = "failed";
									$results["error_title"] = "Failed to Delete School Year";
									$results["error_msg"] = "Something went wrong, and the school year was not deleted.";
								}else{
									$results["result"] = "success";
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Can't Delete School Year";
								$results["error_msg"] = "School year is either the current year or has grades already entered, and can't be deleted.";
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

	public function accesslogAction()
    {
        //-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars / sanitize vars --//
			$limit = $this->request->getPost("limit", "int");
			$page = $this->request->getPost("pageNum", "int");
			$field = $this->request->getPost("field", "string");
			$lastField = $this->request->getPost("lastField", "string");
			$dir = $this->request->getPost("dir", "string");
			
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
		if(!isset($limit) || !$limit){ $limit = 10; }
		if(!isset($page) || !$page){
			$page = 1;
			$offset = 0;
		}else{
			//-- set offset based on page number --//
			$offset = ($page - 1) * $limit;
		}
		if(!isset($field) || !$field){ $field = 'lastname'; }
		if(!isset($dir) || !$dir){ $dir = 'ASC'; }
		
		$limitFilter = '<input type="hidden" name="limit" value="'.$limit.'" />';
		$filters = $limitFilter;
		
		//-- Add Filter inputs --//
		$lastField.= $filters;
		
		//-- Map to real column names --//
		if($field == 'userid'){ $column = 'userid'; }
		else if($field == 'username'){ $column = 'username'; }
		else if($field == 'ip'){ $column = 'ip'; }
		else if($field == 'superpass'){ $column = 'superpass'; }
		else{ $column = 'time'; }
		
		//-- Grab Login Attempts Object --//
		$loginAttempts = LoginAttempt::find(array($conditions, "order" => $column." ".$dir, "limit" => array("number" => $limit, "offset" => $offset)));
		$totalAttempts = LoginAttempt::count(array($conditions, "order" => $column." ".$dir));
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("cap", $this->cap);
        $this->view->setVar("loginAttempts", $loginAttempts);
		$this->view->setVar("lastField", $lastField);
		$this->view->setVar("inputs", $inputs);
		$this->view->setVar("filters", $filters);
		$this->view->setVar("field", $field);
		$this->view->setVar("dir", strtolower($dir));
		$this->view->setVar("limit", $limit);
		$this->view->setVar("pageNum", $page);
		$this->view->setVar("totalAttempts", $totalAttempts);
    } //-- Access Logs --//
	
}
