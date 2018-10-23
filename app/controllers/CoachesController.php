<?php

class CoachesController extends ControllerBase
{
	public function initialize()
	{
		//-- Redirect if not logged in --//
		if(!$this->session->get("logged_in")){
			return $this->response->redirect("session/");
		}
		//-- Deny Access if no Priveleges --//
		if($this->session->get("user-role") > 1){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Coaches");
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
		
		//-- Figure out Filters --//
		if(isset($schoolID) && $schoolID){ $schoolFilter = '<input type="hidden" name="filterSchool" value="'.$schoolID.'" />'; }else{ $schoolFilter = ''; }
		if(isset($searchTerm) && $searchTerm){ $searchFilter = '<input type="hidden" name="filterSearch" value="'.$searchTerm.'" />'; }else{ $searchFilter = ''; }
		$limitFilter = '<input type="hidden" name="limit" value="'.$limit.'" />';
		$filters = $limitFilter.$schoolFilter.$searchFilter;
		
		//-- Add Filter inputs --//
		$lastField.= $filters;
		
		//-- Map to real column names --//
		if($field == 'username'){ $column = 'usernm'; }
		else if($field == 'firstname'){ $column = 'fname'; }
		else if($field == 'lastname'){ $column = 'lname'; }
		else if($field == 'email'){ $column = 'email'; }
		else if($field == 'school'){ $column = 'school'; }
		else{ $column = 'lname'; }
		
		//-- Figure out filter conditions --//
		$conditions = "role = 2";
		if(isset($schoolID) && $schoolID){
			$conditions.= " AND school = ".$schoolID;
		}
		//-- Coach Search --//
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
			$conditions.= " AND ".$search_conditions;
		}
		//-- Grab Character Coaches Users Object --//
		$users = Users::find(array($conditions, "order" => $column." ".$dir, "limit" => array("number" => $limit, "offset" => $offset)));
		$totalUsers = Users::count(array($conditions, "order" => $column." ".$dir));
		
		//-- Grab Schools --//
		$school_abbreviations = array();
		$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		if(!empty($schools)){
			foreach($schools as $school_item){
				$school_abbreviations[$school_item->id] = $school_item->abbreviation;
			}
		}
		
		//-- Pass Objects / Vars to View --//
        $this->view->setVar("coaches", $users);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("lastField", $lastField);
		$this->view->setVar("inputs", $inputs);
		$this->view->setVar("filters", $filters);
		$this->view->setVar("field", $field);
		$this->view->setVar("dir", strtolower($dir));
		$this->view->setVar("limit", $limit);
		$this->view->setVar("pageNum", $page);
		$this->view->setVar("totalUsers", $totalUsers);
		$this->view->setVar("limitFilter", $limitFilter);
		$this->view->setVar("schoolFilter", $schoolFilter);
		$this->view->setVar("searchFilter", $searchFilter);
		if(isset($searchTerm) && $searchTerm){ $this->view->setVar("searchTerm", $searchTerm); }
		if(isset($schoolID) && $schoolID){ $this->view->setVar("schoolID", $schoolID); }
		if(isset($school_abbreviations)){ $this->view->setVar("school_abbreviations", $school_abbreviations); }
    }

}
