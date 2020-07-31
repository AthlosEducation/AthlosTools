<?php

class SchoolsController extends \Phalcon\Mvc\Controller
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
		if(!$this->cap['campuses']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Schools");
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
			    $inputs = '<input type="hidden" name="field" value="school-name" /><input type="hidden" name="dir" value="ASC" />';
				$lastField = '<input type="hidden" name="lastField" value="school-name" /><input type="hidden" name="dir" value="ASC" />';
			}
			
		}else{
		    $inputs = '<input type="hidden" name="field" value="school-name" /><input type="hidden" name="dir" value="ASC" />';
			$lastField = '<input type="hidden" name="lastField" value="school-name" /><input type="hidden" name="dir" value="ASC" />';
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
		if(!isset($field) || !$field){ $field = 'school-name'; }
		if(!isset($dir) || !$dir){ $dir = 'ASC'; }
		
		//-- Figure out Filters --//
		$limitFilter = '<input type="hidden" name="limit" value="'.$limit.'" />';
		$filters = $limitFilter;
		
		//-- Add Filter inputs --//
		$lastField.= $filters;
		
		//-- Map to real column names --//
		if($field == 'school-name'){ $column = 'schoolName'; }
		else if($field == 'state'){ $column = 'state'; }
		else if($field == 'city'){ $column = 'city'; }
		else{ $column = 'school-name'; }
		
		//-- Grab School Object --//
		$districts = Districts::find(array("", "order" => "districtName ASC"));
		$schools = Schools::find(array("", "order" => $column." ".$dir, "limit" => array("number" => $limit, "offset" => $offset)));
		$totalSchools = Schools::count(array("", "order" => $column." ".$dir));
		
		//-- Pass Objects / Vars to View --//
		$this->view->setVar("cap", $this->cap);
		$this->view->setVar("districts", $districts);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("lastField", $lastField);
		$this->view->setVar("inputs", $inputs);
		$this->view->setVar("filters", $filters);
		$this->view->setVar("field", $field);
		$this->view->setVar("dir", strtolower($dir));
		$this->view->setVar("limit", $limit);
		$this->view->setVar("pageNum", $page);
		$this->view->setVar("totalSchools", $totalSchools);
		$this->view->setVar("limitFilter", $limitFilter);
    } //-- end indexAction() --//
	
	public function addschoolAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add User --//
			if($this->request->getPost("action") == 'add_school'){
				//-- Sanitize Vars --//
				$schoolName = trim($this->request->getPost("name", "string"));
				$abbreviation = trim($this->request->getPost("abbr", "string"));
				$district = $this->request->getPost("district", "int");
				$state = $this->request->getPost("state", "string");
				$city = $this->request->getPost("city", "string");
				$ilt = $this->request->getPost("ilt", "int");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['campuses']['add']){
					
					//-- truncate vars to certain length --//
					if($schoolName && strlen($schoolName) > 64){ $schoolName = substr($schoolName, 0, 64); }
					if($abbreviation && strlen($abbreviation) > 30){ $abbreviation = substr($abbreviation, 0, 30); }
					if($state && strlen($state) > 2){ $state = substr($state, 0, 2); }
					if($city && strlen($city) > 32){ $city = substr($city, 0, 32); }
					
					//-- Make sure the required info is present --//
					if($schoolName && $abbreviation && $district && $state && $city){
						/*------------------------------------------
							Now Add School -- Passed all validation
						--------------------------------------------*/
						$school = New Schools();
						$school->schoolName = $schoolName;
						$school->abbreviation = $abbreviation;
						$school->district = $district;
						$school->state = $state;
						$school->city = $city;
						if($ilt){ $school->ilt_school = 1; }else{ $school->ilt_school = 0; }
						$school->date_added = time();

						//-- Save Entry --//
						if($school->save() == false){
							$results["result"] = "failed";
							$results["error_title"] = "Failed to Add School";
							$results["error_msg"] = "Something went wrong, and the school was not created.";
						}else{
							$results["result"] = "success";
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
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end addschoolAction() --//
	
	
	public function editschoolAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab School --//
			if($this->request->getPost("action") == 'grab_school'){
				
				//-- grab / set vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$results = array();

				if($schoolID && is_numeric($schoolID)){
					//-- Grab School --//
					$school = Schools::findFirst(array(
						"conditions" => "id = :schoolID:",
						"bind" => array('schoolID' => $schoolID)
						));

					//-- the school json object --//
					$results["result"] = "success";
					$results["id"] = $school->id;
					$results["schoolname"] = $school->schoolName;
					$results["abbreviation"] = $school->abbreviation;
					$results["district"] = $school->district;
					$results["state"] = $school->state;
					$results["city"] = $school->city;
					$results["ilt"] = $school->ilt_school;

				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_School --//
			
			//-- Function to Edit School --//
			if($this->request->getPost("action") == 'edit_school'){
				
				//-- Sanitize Vars --//
				$schoolID = $this->request->getPost("school_id", "int");
				$name = trim($this->request->getPost("name", "string"));
				$abbreviation = trim($this->request->getPost("abbr", "string"));
				$district = $this->request->getPost("district", "int");
				$state = $this->request->getPost("state", "string");
				$city = $this->request->getPost("city", "string");
				$ilt = $this->request->getPost("ilt", "int");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['campuses']['edit']){
					
					//-- truncate vars to certain length --//
					if(strlen($name) > 64){ $name = substr($name, 0, 64); }
					if(isset($abbreviation) && strlen($abbreviation) > 30){ $abbreviation = substr($abbreviation, 0, 30); }
					if(strlen($city) > 32){ $city = substr($city, 0, 32); }
					if(strlen($state) > 2){ $state = substr($state, 0, 2); }
					
					//-- Make sure the required info is present --//
					if($schoolID && $name && $abbreviation && $district && $state && $city){
						/*------------------------
							Grab School Object 
						-------------------------*/
						$school = Schools::findFirst(array(
							"conditions" => "id = :schoolID:",
							"bind" => array('schoolID' => $schoolID)
							));

						/*---------------------------------------------
							Now Update School -- Passed all validation
						----------------------------------------------*/
						$school->schoolName = $name;
						$school->abbreviation = $abbreviation;
						$school->district = $district;
						$school->state = $state;
						$school->city = $city;
						if($ilt){ $school->ilt_school = 1; }else{ $school->ilt_school = 0; }

						//-- Save Entry --//
						if($school->save() == false){
							$results["result"] = "failed";
							$results["error_title"] = "Failed to Update School";
							$results["error_msg"] = "Something went wrong, and the school was not updated.";
						}else{
							$results["result"] = "success";
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
				
			} //-- end Edit_School --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end editschoolAction() --//
	
	
	public function delschoolAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete School --//
			if($this->request->getPost("action") == 'delete_school'){
				
				//-- grab / set / sanitize vars --//
				$schoolID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['campuses']['delete']){
					
					if($schoolID && is_numeric($schoolID)){
						//-- Grab School --//
						$school = Schools::findFirst(array(
							"conditions" => "id = :schoolID:",
							"bind" => array('schoolID' => $schoolID)
							));
							
						if($school){
							//-- Delete from DB --//
							if($school->delete() == false){
							    $results['result'] = "failed";
								$results["error_title"] = "Failed to Delete School";
								$results["error_msg"] = "Something went wrong, and the school was not deleted.";
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
		
	} //-- end delschoolAction() --//
	
}
