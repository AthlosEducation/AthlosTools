<?php

date_default_timezone_set('UTC');

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class IndexController extends \Phalcon\Mvc\Controller
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
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Dash");
	}
	
	public function logoutAction()
	{
		//-- Destroy the whole session --//
		$this->session->destroy();
		return $this->response->redirect("session/");
	}

	public function indexAction()
    {
        //-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars / sanitize vars --//
			$schoolID = $this->request->getPost("filterSchool", "int");
			$semesterID = $this->request->getPost("filterTerm", "int");
			$staffID = $this->request->getPost("filterStaff", "int");
			$traitID = $this->request->getPost("filterTrait", "string");
			$intervalID = $this->request->getPost("filterInterval", "int");
			$periodID = $this->request->getPost("filterPeriod", "int");
			//-- Character Comparison --//
			$cc_terms = $this->request->getPost("cc-term");
			$cc_items = $this->request->getPost("cc-items");
			$cc_labels = $this->request->getPost("cc-item-label");
			//-- Athletic Comparison --//
			$acc_terms = $this->request->getPost("acc-term");
			$acc_items = $this->request->getPost("acc-items");
			$acc_labels = $this->request->getPost("acc-item-label");
			$compTest = $this->request->getPost("compare_assessment", "string");
		}
		
		/*-------------------------
			Hard Code Variables
		--------------------------*/
		//-- School ID --//
		if($this->session->get("user-school")){
			$schoolID = $this->session->get("user-school");
		}else if($this->session->get("student-school")){
			$schoolID = $this->session->get("student-school");
		}else if(!isset($schoolID) || !$schoolID){
			$schoolID = NULL;
		}
		//-- District ID --//
		if($this->session->get("user-district")){
			$districtID = $this->session->get("user-district");
		}
		//-- Semester ID --//
		if(!isset($semesterID) || !$semesterID){
			$semesterID = $this->session->get("current-semester");
		}
		//-- Staff ID --//
		if($this->session->get("user-role") == 6 || $this->session->get("user-role") == 8){
			$staffID = $this->session->get("user-id");
		}
		//-- Trait ID --//
		$traitArray = array('none','grit','focus','optimism','curiosity','leadership','energy','courage','initiative','social','humility','integrity','creativity');
		if(!isset($traitID) || !$traitID || (isset($traitID) && !in_array($traitID, $traitArray))){
			$traitID = 'grit';
		}
		
		/*------------------------
			Figure Out Filters
		------------------------*/
		//-- School --//
		if(isset($schoolID) && $schoolID){ $schoolFilter = '<input type="hidden" name="filterSchool" value="'.$schoolID.'" />'; }else{ $schoolFilter = ''; }
		//-- Semester --//
		if(isset($semesterID) && $semesterID){ $termFilter = '<input type="hidden" name="filterTerm" value="'.$semesterID.'" />'; }else{ $termFilter = ''; }
		//-- Staff --//
		if(isset($staffID) && $staffID){ $staffFilter = '<input type="hidden" name="filterStaff" value="'.$staffID.'" />'; }else{ $staffFilter = ''; }
		//-- Traits --//
		if(isset($traitID) && $traitID){ $traitFilter = '<input type="hidden" name="filterTrait" value="'.$traitID.'" />'; }else{ $traitFilter = ''; }
		//-- Intervals --//
		if(isset($intervalID) && $intervalID){ $intervalFilter = '<input type="hidden" name="filterInterval" value="'.$intervalID.'" />'; }else{ $intervalFilter = ''; }
		//-- Class Periods --//
		if(isset($periodID) && $periodID){ $periodFilter = '<input type="hidden" name="filterPeriod" value="'.$periodID.'" />'; }else{ $periodFilter = ''; }
		
		/*-----------------------------
			Grab Information Arrays
		------------------------------*/
		//-- Schools --//
		if(isset($districtID) && $districtID){
			$schools = Schools::find(array("district = :dist:", "order" => "state ASC, schoolName ASC, city ASC", "bind" => array('dist' => $districtID)));
		}else{
			$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		}
		//-- Semesters --//
		$semesters = Semesters::find(array("order" => "id ASC"));
		//-- Staff Members --//
		if(isset($schoolID) && $schoolID){
			$staff_members = Users::find(array("role IN (6,8) AND school = :schoolid:", "order" => "role DESC, lname ASC, fname ASC", "bind" => array("schoolid" => $schoolID)));
		}
		//-- Traits --//
		$traits = CurriculumTraits::find('');
		//-- Grab Intervals Object --//
		$intervals = AthleticIntervals::find(array("order" => "id ASC"));
		$newIntervals = array(array("id" => 1, "intervalName" => "Assessment 1"), array("id" => 2, "intervalName" => "Assessment 2"));
		
		/*----------------------------------
			Pass Objects / Vars to View
		----------------------------------*/
		//-- Capabilities --//
		$this->view->setVar("cap", $this->cap);
		//-- Schools --//
		$this->view->setVar("schools", $schools);
		$this->view->setVar("schoolFilter", $schoolFilter);
		if(isset($schoolID)){ $this->view->setVar("schoolID", $schoolID); }
		//-- Semesters --//
		$this->view->setVar("semesters", $semesters);
		$this->view->setVar("termFilter", $termFilter);
		if(isset($semesterID)){ $this->view->setVar("semesterID", $semesterID); }
		//-- Staff --//
		if(isset($staff_members)){ $this->view->setVar("staff_members", $staff_members); }
		$this->view->setVar("staffFilter", $staffFilter);
		if(isset($staffID)){ $this->view->setVar("staffID", $staffID); }
		//-- Traits --//
		$this->view->setVar("traits", $traits);
		$this->view->setVar("traitFilter", $traitFilter);
		if(isset($traitID)){ $this->view->setVar("traitID", $traitID); }
		//-- Intervals --//
		$this->view->setVar("intervals", $intervals);
		$this->view->setVar("newIntervals", $newIntervals);
		$this->view->setVar("intervalFilter", $intervalFilter);
		if(isset($intervalID)){ $this->view->setVar("intervalID", $intervalID); }
		//-- Class Periods --//
		$this->view->setVar("periodFilter", $periodFilter);
		if(isset($periodID)){ $this->view->setVar("periodID", $periodID); }
		
		
		/* Figure out Character Comparison Widget
		--------------------------------------*/
		if(isset($cc_items) && !empty($cc_items) && isset($cc_terms) && !empty($cc_terms)){
			$compareData = array();
			foreach($cc_items as $key => $val){
				list($ctype,$ccID) = explode('-', $val);
				//echo '<br><br>'.$ctype.' -- '.$ccID.'<br>';
				//-- verify type and id are correct --//
				$possTypes = array('school', 'student', 'class', 'grade');
				if(in_array($ctype, $possTypes) && is_numeric($ccID)){
					
					foreach($cc_terms as $term_key => $cc_term){
						if(is_numeric($cc_term)){
							//-- Set constants --//
							$averages = array();
							$chartraits = array('grit', 'focus', 'optimism', 'curiosity', 'leadership', 'energy', 'courage', 'initiative', 'social', 'humility', 'integrity', 'creativity');
							
							switch($ctype){
								case 'school':
									foreach($chartraits as $ct){
										//-- Grab Trait Averages --//
										$averages[$ct] = Grading::average(array(
											"column" => $ct,
											"conditions" => "school = :sid: AND semester = :sem: AND ".$ct." != 0",
											"bind" => array("sid" => $ccID, "sem" => $cc_term)
										));
									}
									break;
								case 'grade':
									if(!empty($schoolID)){
										foreach($chartraits as $ct){
											//-- Grab Trait Averages --//
											$averages[$ct] = Grading::average(array(
												"column" => $ct,
												"conditions" => "school = :sid: AND semester = :sem: AND grade_level = :gid: AND ".$ct." != 0",
												"bind" => array("sid" => $schoolID, "sem" => $cc_term, "gid" => $ccID)
											));
										}
									}
									break;
								case 'class':
									if(!empty($schoolID)){
										//-- Build student list --//
										$studentList = '';
										$classStudents = Students::find(array("school = :sid: AND teacher = :tid:", "bind" => array("sid" => $schoolID, "tid" => $ccID)));
										if(!empty($classStudents)){
											foreach($classStudents as $student){
												if($studentList == ''){ $studentList.= $student->id; }else{ $studentList.= ', '.$student->id; }
											}
										}
										
										foreach($chartraits as $ct){
											//-- Grab Trait Averages --//
											$averages[$ct] = Grading::average(array(
												"column" => $ct,
												"conditions" => "school = :sid: AND semester = :sem: AND student IN ( ".$studentList." ) AND ".$ct." != 0",
												"bind" => array("sid" => $schoolID, "sem" => $cc_term)
											));
										}
									}
									break;
								case 'student':
									if(!empty($schoolID)){
										$theStudent = Students::findFirst(array('id = :id:', "bind" => array("id" => $ccID)));
										if(!empty($theStudent->id)){
											foreach($chartraits as $ct){
												//-- Grab Trait Averages --//
												$averages[$ct] = Grading::average(array(
													"column" => $ct,
													"conditions" => "student = :sid: AND semester = :sem: AND ".$ct." != 0",
													"bind" => array("sid" => $theStudent->id, "sem" => $cc_term)
												));
											}
										}
									}
									break;
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
							//-- Set Final Data --//
							$compareData[$key][$term_key] = array('termID' => $cc_term, 'athlos_grade' => round($cumTotal, 2), 'traits' => $averages, 'label' => $cc_labels[$key]);
							
						}else{
							$compareData[$key][$term_key] = array('termID' => $cc_term, 'athlos_grade' => 0, 'traits' => array(), 'label' => $cc_labels[$key]);
						}
					}
					
					
				}else{
					//-- Invalid item value --//
					$compareData[$key] = array();
				}
			}
			
			$this->view->setVar("compareData", $compareData);
		} //-- end character comparison widget --//
		
		
		/* Figure out Athletic Comparison Widget
		--------------------------------------*/
		if(isset($acc_items) && !empty($acc_items) && isset($acc_terms) && !empty($acc_terms)){
			$compare_Ath_Data = array();
			$athMax = 1;
			foreach($acc_items as $key => $val){
				list($ctype,$ccID) = explode('-', $val);
				if(strpos($ccID, ':')){
					list($sID,$ccID) = explode(':', $ccID);
				}
				//echo '<br><br>'.$ctype.' -- '.$ccID.'<br>';
				//-- verify type and id are correct --//
				$possTypes = array('school', 'student', 'class', 'grade');
				if(in_array($ctype, $possTypes) && is_numeric($ccID)){
					
					foreach($acc_terms as $term_key => $cc_term){
						if(is_numeric($cc_term)){
							//-- Set constants --//
							$average = '';
							$possTest = array('bmi', 'sprint', 'hex', 'vjump', 'sjump', 'shuttle', 'pacer', 'pushup', 'curlup', 'trunklift', 'sitreach');
							//-- if valid test --//
							if(in_array($compTest, $possTest)){
								switch($ctype){
									case 'school':
										//-- Grab Test Average --//
										$average = AthleticGrading::average(array(
											"column" => $compTest,
											"conditions" => "school = :sid: AND semester = :sem: AND ".$compTest." != 0 AND ".$compTest." != 0.00",
											"bind" => array("sid" => $ccID, "sem" => $cc_term)
										));
										break;
									case 'grade':
										if(isset($sID) && !empty($sID)){
											//-- Grab Test Average --//
											$average = AthleticGrading::average(array(
												"column" => $compTest,
												"conditions" => "school = :sid: AND semester = :sem: AND grade_level = :gid: AND ".$compTest." != 0 AND ".$compTest." != 0.00",
												"bind" => array("sid" => $sID, "sem" => $cc_term, "gid" => $ccID)
											));
										}
										break;
									case 'class':
										if(isset($sID) && !empty($sID)){
											//-- Build student list --//
											$studentList = '';
											$classStudents = Students::find(array("school = :sid: AND teacher = :tid:", "bind" => array("sid" => $sID, "tid" => $ccID)));
											if(!empty($classStudents)){
												foreach($classStudents as $student){
													if($studentList == ''){ $studentList.= $student->id; }else{ $studentList.= ', '.$student->id; }
												}
											}

											//-- Grab Test Average --//
											$average = AthleticGrading::average(array(
												"column" => $compTest,
												"conditions" => "school = :sid: AND semester = :sem: AND student IN ( ".$studentList." ) AND ".$compTest." != 0 AND ".$compTest." != 0.00",
												"bind" => array("sid" => $sID, "sem" => $cc_term)
											));
										}
										break;
									case 'student':
										if(!empty($schoolID)){
											$theStudent = Students::findFirst(array('id = :id:', "bind" => array("id" => $ccID)));
											if(!empty($theStudent->id)){
												//-- Grab Trait Averages --//
												$average = AthleticGrading::average(array(
													"column" => $compTest,
													"conditions" => "student = :sid: AND semester = :sem: AND ".$compTest." != 0 AND ".$compTest." != 0.00",
													"bind" => array("sid" => $theStudent->id, "sem" => $cc_term)
												));
											}
										}
										break;
								}
							}
							
							//-- Set Max Value --//
							if($average > $athMax){ $athMax = $average; }
							
							//-- Set Final Data --//
							$compare_Ath_Data[$key][$term_key] = array('termID' => $cc_term, 'athlos_grade' => round($average, 2), 'label' => $acc_labels[$key]);
							
						}else{
							$compare_Ath_Data[$key][$term_key] = array('termID' => $cc_term, 'athlos_grade' => 0, 'label' => $acc_labels[$key]);
						}
					}
					
				}else{
					//-- Invalid item value --//
					$compare_Ath_Data[$key] = array();
				}
			}
			
			$this->view->setVar("compare_Ath_Data", $compare_Ath_Data);
			$this->view->setVar("athMax", $athMax);
			
		} //-- end athletic comparison widget --//
		
		
    } //-- end indexAction() --//
	
	public function addcompareobjectAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Search Students by Name --//
			if($this->request->getPost("action") == 'grab_compare_object'){
				//-- Sanitize Vars --//
				$objectType = trim($this->request->getPost("type", "string"));
				$schoolID = $this->request->getPost("school", "int");
				$results = array();
				
				//-- Make sure object type is valid --//
				$possibleTypes = array('school', 'grade', 'class');
				if(!empty($objectType) && in_array($objectType, $possibleTypes)){
					
					switch($objectType){
						case 'school':
							$cc_schools = Schools::find('');
							if(!empty($cc_schools)){
								$results['result'] = "success";
								foreach($cc_schools as $school){
									$results['items'][] = array('value' => "school-".$school->id, 'text' => $school->schoolName);
								}
							}else{ $results['result'] = "empty"; }
							break;
						case 'grade':
							if(!empty($schoolID)){
								//-- grab school name --//
								$theSchool = Schools::findFirst(array('id = :id:', "bind" => array("id" => $schoolID)));
								//-- Grab Grade Levels --//
								$levels = array();
								$gradelevels = GradeLevel::find('');
								foreach($gradelevels as $gl){
									$levels[$gl->id] = $gl->gradeName;
								}
								unset($gradelevels);

								//-- Grab Available Grade Levels --//
								$query = "SELECT DISTINCT grade FROM students WHERE school = ".$schoolID." ORDER BY grade ASC";
								$response = $this->db->query($query, array());
								$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
								$grades = $response->fetchAll();
								if(!empty($grades)){
									$results['result'] = "success";
									foreach($grades as $grade){
										$results['items'][] = array('value' => "grade-".$grade->grade, 'text' => $theSchool->schoolName.': '.$levels[$grade->grade]);
									}
								}else{ $results['result'] = "empty"; }
							}else{
								$results['result'] = "failed";
							}
							break;
						case 'class':
							if(!empty($schoolID)){
								//-- grab school name --//
								$theSchool = Schools::findFirst(array('id = :id:', "bind" => array("id" => $schoolID)));
								//-- Grab School Teachers --//
								$teachers = Users::find(array("school = :sid: AND role = 8", "order" => "lname ASC, fname ASC", "bind" => array("sid" => $schoolID)));
								if(!empty($teachers)){
									$results['result'] = "success";
									foreach($teachers as $teacher){
										//-- grab class size count --//
										$size = Students::count(array("school = :sid: AND teacher = ".$teacher->id, "bind" => array("sid" => $schoolID)));
										//-- List out options --//
										$results['items'][] = array('value' => 'class-'.$teacher->id, 'text' => $theSchool->schoolName.': '.$teacher->lname.', '.$teacher->fname.' ('.$size.')');
									}
								}else{ $results['result'] = "empty"; }
							}else{
								$results['result'] = "failed";
							}
							break;
					}
					
				}else{
					$results['result'] = "failed";
					$results['msg'] = 'SchoolID = '.$schoolID.' -- Object: '.$objectType;
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	}//-- end addcompareobjectAction() --//
	
	public function addathleticcompareobjectAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Search Students by Name --//
			if($this->request->getPost("action") == 'grab_compare_object'){
				//-- Sanitize Vars --//
				$objectType = trim($this->request->getPost("type", "string"));
				$schoolID = $this->request->getPost("school", "int");
				$results = array();
				
				//-- Make sure object type is valid --//
				$possibleTypes = array('school', 'grade', 'class');
				if(!empty($objectType) && in_array($objectType, $possibleTypes)){
					
					switch($objectType){
						case 'school':
							$cc_schools = Schools::find('');
							if(!empty($cc_schools)){
								$results['result'] = "success";
								foreach($cc_schools as $school){
									$results['items'][] = array('value' => "school-".$school->id, 'text' => $school->schoolName);
								}
							}else{ $results['result'] = "empty"; }
							break;
						case 'grade':
							if(!empty($schoolID)){
								//-- grab school name --//
								$theSchool = Schools::findFirst(array('id = :id:', "bind" => array("id" => $schoolID)));
								//-- Grab Grade Levels --//
								$levels = array();
								$gradelevels = GradeLevel::find('');
								foreach($gradelevels as $gl){
									$levels[$gl->id] = $gl->gradeName;
								}
								unset($gradelevels);

								//-- Grab Available Grade Levels --//
								$query = "SELECT DISTINCT grade FROM students WHERE school = ".$schoolID." ORDER BY grade ASC";
								$response = $this->db->query($query, array());
								$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
								$grades = $response->fetchAll();
								if(!empty($grades)){
									$results['result'] = "success";
									foreach($grades as $grade){
										$results['items'][] = array('value' => 'grade-'.$schoolID.':'.$grade->grade, 'text' => $theSchool->schoolName.': '.$levels[$grade->grade]);
									}
								}else{ $results['result'] = "empty"; }
							}else{
								$results['result'] = "failed";
							}
							break;
						case 'class':
							if(!empty($schoolID)){
								//-- grab school name --//
								$theSchool = Schools::findFirst(array('id = :id:', "bind" => array("id" => $schoolID)));
								//-- Grab School Teachers --//
								$teachers = Users::find(array("school = :sid: AND role = 8", "order" => "lname ASC, fname ASC", "bind" => array("sid" => $schoolID)));
								if(!empty($teachers)){
									$results['result'] = "success";
									foreach($teachers as $teacher){
										//-- grab class size count --//
										$size = Students::count(array("school = :sid: AND teacher = ".$teacher->id, "bind" => array("sid" => $schoolID)));
										//-- List out options --//
										$results['items'][] = array('value' => 'class-'.$schoolID.':'.$teacher->id, 'text' => $theSchool->schoolName.': '.$teacher->lname.', '.$teacher->fname.' ('.$size.')');
									}
								}else{ $results['result'] = "empty"; }
							}else{
								$results['result'] = "failed";
							}
							break;
					}
					
				}else{
					$results['result'] = "failed";
					$results['msg'] = 'SchoolID = '.$schoolID.' -- Object: '.$objectType;
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	}//-- end addathleticcompareobjectAction() --//
	
	public function studentsearchAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Search Students by Name --//
			if($this->request->getPost("action") == 'search_student_name'){
				//-- Sanitize Vars --//
				$searchVal = trim($this->request->getPost("search", "string"));
				$schoolID = $this->request->getPost("school", "int");
				$results = array();
				
				//-- Make sure search value and school are not empty --//
				if(!empty($searchVal) && !empty($schoolID)){
					
					//-- get rid of any blank values --//
					$names = array();
					$search = explode(' ', $searchVal);
					foreach($search as $item){
						if(!empty($item)){
							$names[] = $item;
						}
					}
					
					//-- Start Conditions with School --//
					$conditions = "school = ".$schoolID;
					
					//-- Setup Name Conditions --//
					switch(count($names)){
						case 0:
							break;
						case 1:
							$conditions.= " AND (fname = '".$names[0]."' OR fname LIKE '".$names[0]."%' OR lname = '".$names[0]."' OR lname LIKE '".$names[0]."%')";
							break;
						default:
					        $conditions.= " AND (((fname = '".$names[0]."' OR fname LIKE '".$names[0]."%') AND (lname = '".$names[1]."' OR lname LIKE '".$names[1]."%')) OR (fname = '".$names[0]." ".$names[1]."' OR fname LIKE '".$names[0]." ".$names[1]."%' OR lname = '".$names[0]." ".$names[1]."' OR lname LIKE '".$names[0]." ".$names[1]."%'))";
					}
					
					//-- Grab Students --//
					$students = Students::find(array($conditions, "order" => "fname ASC, lname ASC", "limit" => 10));
					if(!empty($students)){
						if(count($students) > 0){
							//-- Success --//
							$results['result'] = "success";
							//$results['conditions'] = $conditions;
							$results['student'] = array();
							foreach($students as $student){
								$results['student'][] = array('id' => $student->id, 'first' => $student->fname, 'last' => $student->lname);
							}
						}else{ $results['result'] = "empty"; }
					}else{ $results['result'] = "empty"; }
					
				}else{
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end studentsearchAction() --//
	
	
	public function athleticscoresAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Generate Score Card Values --//
			if($this->request->getPost("action") == 'athletic_card_scores'){
				//-- Sanitize Vars --//
				$studentID = $this->request->getPost("student", "int");
				$results = array('sprint' => 0.00, 'hex' => 0.00, 'vjump' => 0.00, 'sjump' => 0.00, 'bmi' => 0.00, 'pacer' => 0, 'shuttle' => 0, 'pushup' => 0, 'curlup' => 0, 'trunklift' => 0, 'sitreach' => 0);
				
				//-- Make sure studentID not empty --//
				if(!empty($studentID)){
					
					//-- Grab Most Recent Test Entries --//
					$conditions = "student = :sid: AND semester = :sem:";
					$bind = array("sid" => $studentID, "sem" => $this->session->get("current-semester"));
					$aGrade = AthleticGrading::find(array($conditions, "bind" => $bind, "order" => "interval DESC"));
					if(!empty($aGrade)){
						$results['result'] = "success";
						foreach($aGrade as $key => $grade){
							//-- set grade values --//
							if(!empty($grade->sprint) && empty($results['sprint'])){ $results['sprint'] = $grade->sprint; }
							if(!empty($grade->hex) && empty($results['hex'])){ $results['hex'] = $grade->hex; }
							if(!empty($grade->vjump) && empty($results['vjump'])){ $results['vjump'] = $grade->vjump; }
							if(!empty($grade->sjump) && empty($results['sjump'])){ $results['sjump'] = $grade->sjump; }
							if(!empty($grade->bmi) && empty($results['bmi'])){ $results['bmi'] = $grade->bmi; }
							if(!empty($grade->pacer) && empty($results['pacer'])){ $results['pacer'] = $grade->pacer; }
							if(!empty($grade->shuttle) && empty($results['shuttle'])){ $results['shuttle'] = $grade->shuttle; }
							if(!empty($grade->pushup) && empty($results['pushup'])){ $results['pushup'] = $grade->pushup; }
							if(!empty($grade->curlup) && empty($results['curlup'])){ $results['curlup'] = $grade->curlup; }
							if(!empty($grade->trunklift) && empty($results['trunklift'])){ $results['trunklift'] = $grade->trunklift; }
							if(!empty($grade->sitreach) && empty($results['sitreach'])){ $results['sitreach'] = $grade->sitreach; }
						}
					}
					
					//-- Grab PR values for each Test --//
					$conditions = "student = :sid:";
					$bind = array("sid" => $studentID);
					$sprintPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "sprint DESC", "limit" => 1));
					$results['sprint_pr'] = $sprintPR->sprint;
					$hexPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "hex DESC", "limit" => 1));
					$results['hex_pr'] = $hexPR->hex;
					$vjumpPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "vjump DESC", "limit" => 1));
					$results['vjump_pr'] = $vjumpPR->vjump;
					$sjumpPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "sjump DESC", "limit" => 1));
					$results['sjump_pr'] = $sjumpPR->sjump;
					$bmiPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "bmi DESC", "limit" => 1));
					$results['bmi_pr'] = $bmiPR->bmi;
					$pacerPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "pacer DESC", "limit" => 1));
					$results['pacer_pr'] = $pacerPR->pacer;
					$shuttlePR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "shuttle DESC", "limit" => 1));
					$results['shuttle_pr'] = $shuttlePR->shuttle;
					$pushupPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "pushup DESC", "limit" => 1));
					$results['pushup_pr'] = $pushupPR->pushup;
					$curlupPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "curlup DESC", "limit" => 1));
					$results['curlup_pr'] = $curlupPR->curlup;
					$trunkliftPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "trunklift DESC", "limit" => 1));
					$results['trunklift_pr'] = $trunkliftPR->trunklift;
					$sitreachPR = AthleticGrading::findFirst(array($conditions."", "bind" => $bind, "order" => "sitreach DESC", "limit" => 1));
					$results['sitreach_pr'] = $sitreachPR->sitreach;
					
				}else{
					$results['result'] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end athleticscoresAction() --//
	
	
	public function missingstudentAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Generate Score Card Values --//
			if($this->request->getPost("action") == 'missing_assessments_student'){
				//-- Sanitize Vars --//
				$studentID = $this->request->getPost("student", "int");
				$phaseID = $this->request->getPost("phase", "int");
				$performance = array('bmi', 'sprint', 'hex', 'vjump', 'sjump', 'shuttle');
				$fitness = array('bmi', 'pacer', 'pushup', 'curlup', 'trunklift', 'sitreach');
				$results = array();
				
				//-- Make sure studentID not empty --//
				if(!empty($studentID) && !empty($phaseID)){
					//-- Figure out which set to use --//
					if($phaseID == 1 || $phaseID == 4){
						$assessments = $fitness;
					}else{
						$assessments = $performance;
					}
					
					//-- Grab Assessment Info --//
					$assessData = AthleticAssessments::find('');
					foreach($assessData as $data){
						$results['data'][$data->url_name] = array('name' => $data->assessment_name, 'label' => $data->data_label);
					}
					
					//-- Grab Most Recent Test Entries --//
					$conditions = "student = :sid: AND semester = :sem: AND interval = :phase:";
					$bind = array("sid" => $studentID, "sem" => $this->session->get("current-semester"), "phase" => $phaseID);
					$grade = AthleticGrading::findFirst(array($conditions, "bind" => $bind));
					if(!empty($grade)){
						$results['result'] = "success";
						foreach($assessments as $test){
							if(!empty($grade->{$test})){
								$results['scores'][$test] = $grade->{$test};
							}else{
								$results['scores'][$test] = null;
							}
						}
					}else{
						$results['result'] = "success";
						foreach($assessments as $test){
							$results['scores'][$test] = null;
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
		
	} //-- end missingstudentAction() --//
	
	
	public function getcoachesAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab Coach List by school Values --//
			if($this->request->getPost("action") == 'get_coach_list'){
				//-- Sanitize Vars --//
				$schoolID = $this->request->getPost("school", "int");
				$results = array();
				
				//-- Make sure vital data is not empty --//
				if(!empty($schoolID)){
					//-- Compile Coach Query --//
					$coach_students = Students::find(array("school = :sid: AND active = 1", "bind" => array("sid" => $schoolID), "columns" => 'coach', "group" => 'coach'));
					if(!empty($coach_students)){
						$csList = '';
						foreach($coach_students as $cs){
							if(isset($cs->coach) && $cs->coach != 0){
								if($csList == ''){
									$csList.= 'id = '.$cs->coach;
								}else{
									$csList.= ' OR id = '.$cs->coach;
								}
							}
						}
					}
					
					//-- Grab Coaches --//
					if(isset($csList) && $csList){
						$coachList = Users::find(array($csList, "order" => "lname ASC, fname ASC"));
						//-- List Out Coaches --//
						if(!empty($coachList)){
							$results['result'] = "success";
							foreach($coachList as $coach){
								$results['coaches'][] = array($coach->id, $coach->lname.", ".$coach->fname);
							}
						}
					}else{
						$results['result'] = "failed";
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
		
	} //-- end getcoachesAction() --//
	
	
	public function missingassessmentAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Generate Score Card Values --//
			if($this->request->getPost("action") == 'missing_assessments_all'){
				//-- Sanitize Vars --//
				$schoolID = $this->request->getPost("school", "int");
				$coachID = $this->request->getPost("coach", "int");
				$phaseID = $this->request->getPost("phase", "int");
				$test_url = $this->request->getPost("test", "string");
				$testList = array('bmi', 'sprint', 'hex', 'vjump', 'pacer', 'pushup', 'balance', 'plank', 'slraise');
				$results = array();
				
				//-- Make sure vital data is not empty --//
				if(!empty($schoolID) && !empty($phaseID) && !empty($test_url)){
					//-- Figure out which set to use --//
					if($test_url == 'all'){
						$assessments = $testList;
					}else{
						$assessments = array($test_url);
					}
					
					//-- Grab School Name --//
					$schoolData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $schoolID)));
					if(!empty($schoolData)){
						$results['school'] = $schoolData->schoolName;
					}
					
					//-- Grab Assessment Info --//
					$assessData = AthleticAssessments::find('');
					foreach($assessData as $data){
						$results['data'][$data->url_name] = array('name' => $data->assessment_name, 'label' => $data->data_label);
					}
					
					//-- Grab Grade Level Info --//
					$levels = GradeLevel::find('');
					foreach($levels as $level){
						$results['level'][$level->id] = $level->gradeName;
					}
					
					//-- Grab Students --//
					$conditions = "school = :sid: AND active = 1";
					if(!empty($coachID)){
						$conditions.= " AND coach = ".$coachID;
					}
					$bind = array("sid" => $schoolID);
					$students = Students::find(array($conditions, "bind" => $bind, "columns" => "id, grade, fname, lname", "order" => "grade ASC"));
					if(!empty($students)){
						$results['result'] = "success";
						$results['count']['total'] = count($students);
						foreach($students as $student){
							if(!empty($student)){
								
								if(!isset($curGrade)){ $curGrade = $student->grade; }
								if($curGrade != $student->grade){
									$curGrade = $student->grade;
									$results['grade'][$curGrade]['total'] = 0;
									$results['grade'][$curGrade]['missed'] = 0;
									$results['grade'][$curGrade]['complete'] = 0;
								}
								
								$conditions2 = "student = :sid: AND semester = :sem: AND interval = :phase:";
								$bind2 = array("sid" => $student->id, "sem" => $this->session->get("current-semester"), "phase" => $phaseID);
								$grade = AthleticGrading::findFirst(array($conditions2, "bind" => $bind2));
								if(!empty($grade)){
									foreach($assessments as $test){
										//-- Set Arrays / Counts --//
										if(!isset($results['grade'][$curGrade][$test]['total'])){
											$results['grade'][$curGrade][$test]['total'] = 0;
											$results['grade'][$curGrade][$test]['missed'] = 0;
											$results['grade'][$curGrade][$test]['complete'] = 0;
											$results['grade'][$curGrade][$test]['students'] = array();
										}
										
										if(!empty($grade->{$test})){
											//-- Update Completed Counts --//
											$results['grade'][$curGrade]['complete'] = (empty($results['grade'][$curGrade]['complete']) ? 1 : $results['grade'][$curGrade]['complete'] + 1);
											$results['grade'][$curGrade][$test]['complete']++;
										}else{
											//-- Update Missed Counts --//
											$results['grade'][$curGrade]['missed'] = (empty($results['grade'][$curGrade]['missed']) ? 1 : $results['grade'][$curGrade]['missed'] + 1);
											$results['grade'][$curGrade][$test]['missed']++;
											//-- Add Student to Missed Array --//
											$results['grade'][$curGrade][$test]['students'][] = $student->fname." ".$student->lname;
										}
										
										//-- Update Total Counts --//
										$results['grade'][$curGrade]['total'] = (empty($results['grade'][$curGrade]['total']) ? 1 : $results['grade'][$curGrade]['total'] + 1);
										$results['grade'][$curGrade][$test]['total']++;
									}
								}else{
									/* Has no grades set
									-----------------------*/
									foreach($assessments as $test){
										//-- Set Arrays / Counts --//
										if(!isset($results['grade'][$curGrade][$test]['total'])){
											$results['grade'][$curGrade][$test]['total'] = 0;
											$results['grade'][$curGrade][$test]['missed'] = 0;
											$results['grade'][$curGrade][$test]['complete'] = 0;
											$results['grade'][$curGrade][$test]['students'] = array();
										}
										
										//-- Update Missed Counts --//
										$results['grade'][$curGrade]['missed'] = (empty($results['grade'][$curGrade]['missed']) ? 1 : $results['grade'][$curGrade]['missed'] + 1);
										$results['grade'][$curGrade][$test]['missed']++;
										//-- Add Student to Missed Array --//
										$results['grade'][$curGrade][$test]['students'][] = $student->fname." ".$student->lname;
									}
									
									//-- Update Total Counts --//
									$results['grade'][$curGrade]['total'] = (empty($results['grade'][$curGrade]['total']) ? 1 : $results['grade'][$curGrade]['total'] + 1);
									$results['grade'][$curGrade][$test]['total']++;
								}
								
							}
						}
					}else{
						$results['result'] = "failed";
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
		
	} //-- end missingassessmentAction() --//
	
	
	public function getmetricsAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Report of all students with given metrics --//
			if($this->request->getPost("action") == 'retrieve_athletic_metrics'){
				//-- Sanitize Vars --//
				$school_arr = $this->request->getPost("schools");
				$semester = $this->request->getPost("year", "int");
				$phaseID = $this->request->getPost("phase", "int");
				$test_url = $this->request->getPost("test", "string");
				$comparison = $this->request->getPost("comparison", "string");
				$metric = $this->request->getPost("metric", "float");
				$columns = array('bmi', 'sprint', 'hex', 'vjump', 'sjump', 'shuttle', 'pacer', 'pushup', 'curlup', 'trunklift', 'sitreach', 'balance', 'plank', 'slraise');
				$operators = array('greater' => '>', 'less' => '<', 'equal' => '=');
				$results = array();
				
				//-- Validate / Sanitize Schools Array --//
				if(!empty($school_arr)){
					foreach($school_arr as $sid => $grade_arr){
						//-- Sanitize School ID --//
						$new_sid = $this->filter->sanitize($sid, "int");
						//-- Use New Sanitized School ID - OR - Unset entire school if invalid --//
						if(!empty($new_sid)){
							unset($school_arr[$sid]);
							$school_arr[$new_sid] = $grade_arr;
							if(!empty($grade_arr)){
								foreach($grade_arr as $i => $grade){
									//-- Sanitize Grade ID --//
									$new_grade = $this->filter->sanitize($grade, "int");
									//-- Use New Sanitized Grade ID - OR - Unset grade --//
									if(!empty($new_grade)){
										$school_arr[$new_sid][$i] = $new_grade;
									}else{
										unset($school_arr[$new_sid][$i]);
									}
								}
							}
						}else{
							unset($school_arr[$sid]);
						}
					}
				}
				
				//-- Make sure vital data is not empty --//
				if(!empty($school_arr) && !empty($phaseID) && !empty($test_url) && !empty($comparison) && !empty($metric) && !empty($semester)){
					//-- Make sure SQL Metric not tampered with --//
					if(in_array($test_url, $columns) && array_key_exists($comparison, $operators)){
						$results['result'] = "success";
						//-- Grab Assessment Info --//
						$assessData = AthleticAssessments::find('');
						foreach($assessData as $data){
							$results['data'][$data->url_name] = array('name' => $data->assessment_name, 'label' => $data->data_label);
						}
					
						//-- Grab Grade Level Info --//
						$levels = GradeLevel::find('');
						foreach($levels as $level){
							$results['level'][$level->id] = $level->gradeName;
						}
						
						//-- Iterate Through School Array --//
						foreach($school_arr as $school => $grades){
							if(!empty($grades)){
								//-- Grab School Name --//
								$schoolData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $school)));
								if(!empty($schoolData)){
									$results['school'][$school]['name'] = $schoolData->schoolName;
								}
								
								//-- Eliminate Zero from results - unless specifically asked for --//
								if(!empty($metric)){ $notZero = "AND a.".$test_url." > 0 "; }else{ $notZero = ""; }
								
								//-- Build / Run Query --//
								$levels = implode(', ', $grades);
								$query = "SELECT a.".$test_url.", a.grade_level, s.fname, s.lname FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$semester." AND a.school = ".$school." AND a.interval = ".$phaseID." AND a.grade_level IN (".$levels.") AND a.".$test_url." ".$operators[$comparison]." ".$metric." ".$notZero."ORDER BY a.grade_level ASC, s.lname ASC, s.fname ASC";
								$response = $this->db->query($query, array());
								$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
								$aGrades = $response->fetchAll();
								if(!empty($aGrades)){
									$results['school'][$school]['count'] = 0;
									foreach($aGrades as $student_info){
										$results['school'][$school][$student_info->grade_level]['students'][] = array('data' => $student_info->{$test_url}, 'name' => $student_info->lname.', '.$student_info->fname);
										$results['school'][$school]['count']++;
									}
								}
								
							}
						}
					}else{
						$results['result'] = "failed";
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
		
	} //-- end getmetricsAction() --//
	
	
	public function exportmetricsAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Export spreadsheet of all students with given metrics --//
			if($this->request->getPost("action") == 'export_athletic_metrics'){
				//-- Sanitize Vars --//
				$schoolID = $this->request->getPost("school", "int");
				$semester = $this->request->getPost("year", "int");
				$semesterName = $this->request->getPost("year_name", "string");
				$phaseID = $this->request->getPost("phase", "int");
				$grade_arr = $this->request->getPost("grades");
				$test_url = $this->request->getPost("test", "string");
				$comparison = $this->request->getPost("comparison", "string");
				$metric = $this->request->getPost("metric", "float");
				$unit = $this->request->getPost("unit", "string");
				$query_txt = $this->request->getPost("query", "string");
				$columns = array('bmi', 'sprint', 'hex', 'vjump', 'sjump', 'shuttle', 'pacer', 'pushup', 'curlup', 'trunklift', 'sitreach');
				$operators = array('greater' => '>', 'less' => '<', 'equal' => '=');
				$results = $gradeLevels = array();
				
				//-- Validate / Sanitize Grades Array --//
				if(!empty($grade_arr)){
					foreach($grade_arr as $i => $grade){
						//-- Sanitize Grade ID --//
						$new_grade = $this->filter->sanitize($grade, "int");
						//-- Use New Sanitized Grade ID - OR - Unset grade --//
						if(!empty($new_grade)){
							$grade_arr[$i] = $new_grade;
						}else{
							unset($grade_arr[$i]);
						}
					}
				}
				
				//-- Make sure vital data is not empty --//
				if(!empty($schoolID) && !empty($grade_arr) && !empty($phaseID) && !empty($test_url) && !empty($comparison) && !empty($metric) && !empty($unit) && !empty($semester)){
					//-- Make sure SQL Metric not tampered with --//
					if(in_array($test_url, $columns) && array_key_exists($comparison, $operators)){
						
						//-- Grab Grade Level Info --//
						$levels = GradeLevel::find('');
						foreach($levels as $level){
							$gradeLevels[$level->id] = $level->gradeName;
						}
						
						//-- Grab School Name --//
						$schoolData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $schoolID)));
						/*if(!empty($schoolData)){
							$results['school'][$school]['name'] = $schoolData->schoolName;
						}*/
						
						//-- Eliminate Zero from results - unless specifically asked for --//
						if(!empty($metric)){ $notZero = "AND a.".$test_url." > 0 "; }else{ $notZero = ""; }
						
						//-- Build / Run Query --//
						$levels = implode(', ', $grade_arr);
						$query = "SELECT a.".$test_url.", a.grade_level, s.fname, s.lname FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$semester." AND a.school = ".$schoolID." AND a.interval = ".$phaseID." AND a.grade_level IN (".$levels.") AND a.".$test_url." ".$operators[$comparison]." ".$metric." ".$notZero."ORDER BY a.grade_level ASC, s.lname ASC, s.fname ASC";
						$response = $this->db->query($query, array());
						$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
						$aGrades = $response->fetchAll();
						if(!empty($aGrades)){
							//-- Set File Variables --//
							$schoolCount = count($aGrades);
							$curGrade = '';
							$CSVFilename = "Athlos_".str_ireplace(" ", "_", $schoolData->schoolName)."_Athletic_Metrics_".ucfirst($test_url)."_".str_ireplace(" ", "_", $semesterName)."_".time().".csv";
							$filepath = $_SERVER['DOCUMENT_ROOT']."/downloads/temp/".$CSVFilename;
							$results['filepath'] = $filepath;
							$results['urlpath'] = "https://tools.athlosacademies.org/downloads/temp/".$CSVFilename;
							$fp = fopen($filepath, 'w');
							//-- Iterate Through Student Data --//
							if($fp !== false){
								$results['result'] = "success";
								
								//fwrite($fp, $schoolData->schoolName.",".$schoolCount."\r\n");
								fwrite($fp, "School Year: ".$semesterName."\r\n\n");
								fwrite($fp, $query_txt."\r\n\n");
								fwrite($fp, $schoolData->schoolName." (".$schoolCount.")\r\n");
								foreach($aGrades as $student_info){
									if($curGrade != $student_info->grade_level){
										$curGrade = $student_info->grade_level;
										fwrite($fp, "\n,".$gradeLevels[$curGrade]."\r\n");
										fwrite($fp, ",,#,Last,First,".$unit."\r\n");
										$x = 0;
									}
								
									fwrite($fp, ",,".($x + 1).",".$student_info->lname.",".$student_info->fname.",".$student_info->{$test_url}."\r\n");
									$x++;
								}
								fclose($fp);
							}else{
								$results['result'] = "failed";
								$results['opened'] = "no";
							}
							
						}else{
							$results['result'] = "failed";
							$results['empty'] = "yes";
						}
						
					}else{
						$results['result'] = "failed";
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
		
	} //-- end exportmetricsAction() --//
	
	
	public function lockdownAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Unlock Athlos Tools --//
			if($this->request->getPost("action") == 'unlock_athlos'){
				//-- Validate that its an administrator --//
				if($this->cap['administration']['manage']){
					//-- Grab Option --//
					$option = Options::findFirst("option = 'site-lockdown'");
					$option->value = 0;
					//-- Save Option --//
					if($option->save() == false){
						$results["result"] = "failed";
					}else{
						$results['result'] = 'success';
					}
				}else{
					$results['result'] = 'failed';
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
			
			//-- Function to Lock Athlos Tools --//
			if($this->request->getPost("action") == 'lockdown_athlos'){
				//-- Validate that its an administrator --//
				if($this->cap['administration']['manage']){
					//-- Grab Option --//
					$option = Options::findFirst("option = 'site-lockdown'");
					$option->value = 1;
					//-- Save Option --//
					if($option->save() == false){
						$results["result"] = "failed";
					}else{
						$results['result'] = 'success';
					}
				}else{
					$results['result'] = 'failed';
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end lockdownAction(); --//
	
	
	public function secureAction()
	{
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		//-- Priveleges - ONLY SUPER ADMINS --//
		if($this->cap['administration']['manage']){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access this page.");
			return $this->response->redirect("");
		}
		
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Students");
		
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
					$fileName = substr($fileName, 0, strrpos($fileName, '.')).'-'.time().'.'.substr($fileName, strrpos($fileName, '.') + 1);
					$fileTempName = $_FILES['theFile']['tmp_name'];
					$addMsg = $this->request->getPost("add-msg", "string");
					
					//move the file
					if($s3->putObjectFile($fileTempName, "athlos-tools-secure-uploads", $fileName, S3::ACL_PUBLIC_READ)) {
						$this->flashSession->success($preMsg."<strong>Success</strong> We successfully uploaded your file.");
						
						//-- Setup Mailgun Object --//
						$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
						
						//-- Send Email to Staff Member --//
						$to = $this->session->get("user-email");
						$subject = "Athlos Tools - Secure File Uploaded";
						$message = "Your file has been securely uploaded to Athlos Tools. Now web administrators can access your uploads, and with the message you might have added, you can help them know what to do with your file. For reference your file and message details are listed below:\n\nFile Name: ".$fileName."\nMessage:\n".$addMsg."\n\nThanks again,\n\n\t- Athlos Tools";
						//-- Send MSG with Mailgun --//
						$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
						                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
						                        'to'      => $to,
						                        'subject' => $subject,
						                        'text'    => $message));
						
						//-- Send Email to Web Admins --//
						$to = 'trevor@decortinteractive.com,cherdt@athlosacademies.org';
						$subject = "Athlos Tools - Secure File Uploaded";
						$message = "A file has been securely uploaded to Athlos Tools. Below are the file details, please review the user message carefully as it may have instructions for how to handle the file.\n\nFile Name: ".$fileName."\nSubmitted By: ".$this->session->get("user-id")." - ".$this->session->get("user-name")." (".$this->session->get("user-email").")\nMessage:\n".$addMsg."\n\nThanks again,\n\n\t- Athlos Tools";
						//-- Send MSG with Mailgun --//
						$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
						                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
						                        'to'      => $to,
						                        'subject' => $subject,
						                        'text'    => $message));
						
						
					}else{
						$this->flashSession->error($preMsg."<strong>Error</strong> Something went wrong while uploading your file... sorry.");
					}	
				}
			}
		}
		
		// Get the bucket contents
		$bucket_contents = $s3->getBucket("athlos-tools-secure-uploads");
		$this->view->setVar("bucket_contents", $bucket_contents);
		
	} //-- end secureAction() --//
	
	
	public function customizedashAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Customize Dashboard Widgets --//
			if($this->request->getPost("action") == 'customize_dashboard_widgets'){
				//-- Sanitize Vars --//
				$widgets = $this->request->getPost("widgets");
				$results = $newDash = array();
				
				//-- Sanitize / Prepare Widgets Array --//
				if(!empty($widgets)){
					foreach($widgets as $width_key => $widget){
						if(!empty($widget)){
							foreach($widget as $position => $widgetID){
								//-- Sanitize Vars --//
								$widgetID = $this->filter->sanitize($widgetID, "int");
								$position = $this->filter->sanitize($position, "int");
								if($position != '' && $widgetID != ''){
									if($width_key != 6){
										$newDash[$position] = array('id' => $widgetID, 'width' => 12);
									}else{
										$newDash[$position] = array('id' => $widgetID, 'width' => 6);
									}
								}
							}
						}
					}
					
					if(!empty($newDash)){
						ksort($newDash);
						$dashboard = serialize($newDash);
						$userDash = UserDash::findFirst(array("user_id = :uid:", "bind" => array("uid" => $this->session->get('user-id'))));
						if(!empty($userDash)){
							//-- update current dashboard --//
							$userDash->widgets = $dashboard;
							
							//-- Save Entry --//
							if($userDash->save() == false){
								$results["result"] = "failed";
							}else{
								$results["result"] = "success";
								$results["widgets"] = $newDash;
							}
						}else{
							//-- Create New --//
							$dash = New UserDash();
							$dash->user_id = $this->session->get("user-id");
							$dash->widgets = $dashboard;
							
							//-- Save Entry --//
							if($dash->save() == false){
								$results["result"] = "failed";
							}else{
								$results["result"] = "success";
								$results["widgets"] = $newDash;
							}
						}
					}else{
						$results['result'] = "failed";
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
		
	} //-- end customizedashAction() --//
	
	
	public function deletefileAction()
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
			if($this->request->getPost("action") == 'delete_file'){
				
				//-- Verify Permissions -- only super admins --//
				if($this->cap['administration']['manage']){
					
					//-- Sanitize Vars --//
					$filename = trim($this->request->getPost("filename", "string"));
					$results = array();
					
					//-- Make sure the required info is present --//
					if($filename){
						//-- delete the file --//
						if($s3->deleteObject('athlos-tools-secure-uploads', $filename)) {
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
		
	} //-- end deletefileAction() --//
	
	
	public function assessGradedAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab data for all graded assessments --//
			if($this->request->getPost("action") == 'graded_assessments'){
				//-- Sanitize Vars --//
				$school = $this->request->getPost("school", "int");
				$interval = $this->request->getPost("interval", "int");
				$period = $this->request->getPost("period", "string");
				$results = array();
				
				//-- Make sure vital data is not empty --//
				if(!empty($school) && in_array($interval, array(1,2))){
					
					//-- Grab School Name --//
					$schoolData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $school)));
					if(!empty($schoolData)){
						$results['school'] = $schoolData->schoolName;
					}
					
					//-- zero out & initiate counts and percentages --//
					$results['count']['bmi'] = $results['count']['sprint'] = $results['count']['hex'] = $results['count']['vjump'] = $results['count']['pacer'] = $results['count']['pushup'] = $results['count']['balance'] = $results['count']['plank'] = $results['count']['slraise'] = 0;
					$results['per']['bmi'] = $results['per']['sprint'] = $results['per']['hex'] = $results['per']['vjump'] = $results['per']['pacer'] = $results['per']['pushup'] = $results['per']['balance'] = $results['per']['plank'] = $results['per']['slraise'] = 0;
					
					/*-------------------
						Gather Report
					-------------------*/
					$bind = array('school' => $school);
					$extra_conditions = "school = :school:";
					if(isset($period) && $period != ''){
						$all_students = Students::find(array("school = :schoolid: AND turf_period = :turf: AND active = 1", "bind" => array("schoolid" => $school, "turf" => $period)));
						//-- gather list of students for sql queries --//
						$id_str = '';
						foreach($all_students as $student){
							if($id_str == ''){
								$id_str.= $student->id;
							}else{
								$id_str.= ','.$student->id;
							} 
						}
						//-- Run Grade Counting Queries --//
						if($id_str != ''){
							$extra_conditions.= " AND student IN(".$id_str.")";
						}
					}else{
						$all_students = Students::find(array("school = :schoolid: AND active = 1", "bind" => array("schoolid" => $school)));
					}
					$total_students = count($all_students);
			
					//-- Semester Details --//
					$extra_conditions.= " AND semester = :sem:";
					$bind['sem'] = $this->session->get("current-semester");
					//-- Interval Details --//
					$extra_conditions.= " AND interval = :phase:";
					$bind['phase'] = $interval;
			
					if($total_students > 0){
						$results['result'] = "success";
						$results['total'] = $total_students;
						//-- Count Assessments --//
						$results['count']['bmi'] = AthleticGrading::count(array($extra_conditions." AND bmi != 0.00", "bind" => $bind));
						$results['count']['sprint'] = AthleticGrading::count(array($extra_conditions." AND sprint != 0.00", "bind" => $bind));
						$results['count']['hex'] = AthleticGrading::count(array($extra_conditions." AND hex != 0.00", "bind" => $bind));
						$results['count']['vjump'] = AthleticGrading::count(array($extra_conditions." AND vjump != 0.00", "bind" => $bind));
						$results['count']['pacer'] = AthleticGrading::count(array($extra_conditions." AND pacer != 0", "bind" => $bind));
						$results['count']['pushup'] = AthleticGrading::count(array($extra_conditions." AND pushup != 0", "bind" => $bind));
						$results['count']['balance'] = AthleticGrading::count(array($extra_conditions." AND balance != 0.00", "bind" => $bind));
						$results['count']['plank'] = AthleticGrading::count(array($extra_conditions." AND plank != 0.00", "bind" => $bind));
						$results['count']['slraise'] = AthleticGrading::count(array($extra_conditions." AND slraise != 0.00", "bind" => $bind));
				
						//-- Get Percentages --//
						$results['per']['bmi'] = round(($results['count']['bmi'] / $total_students) * 100);
						$results['per']['sprint'] = round(($results['count']['sprint'] / $total_students) * 100);
						$results['per']['hex'] = round(($results['count']['hex'] / $total_students) * 100);
						$results['per']['vjump'] = round(($results['count']['vjump'] / $total_students) * 100);
						$results['per']['pacer'] = round(($results['count']['pacer'] / $total_students) * 100);
						$results['per']['pushup'] = round(($results['count']['pushup'] / $total_students) * 100);
						$results['per']['balance'] = round(($results['count']['balance'] / $total_students) * 100);
						$results['per']['plank'] = round(($results['count']['plank'] / $total_students) * 100);
						$results['per']['slraise'] = round(($results['count']['slraise'] / $total_students) * 100);
					}else{
						$results['result'] = "failed";
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
		
	} //-- end assessGradedAction() --//
	
	
	/*public function specialemailAction(){
		//-- Only allow Trevor --//
		if($this->session->get("user-id") == 1){
			
			//-- Pull all Brooklyn Park Parents --//
			$parents = Users::find(array("role = 5"));
			$parentCount = Users::count(array("role = 5"));
			echo '<br><br>'.$parentCount.'<br><br>';
			
			//-- Setup Mailgun Object --//
			$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
			
			$subject = "Athlos Academies Grading Login Information";
			$message = "Recently you received a log-in e-mail from Athlos Tools.  This log-in allows you to access Athlos Tools from the corporate website at www.athlosacademies.org<http://www.athlosacademies.org>  You will find the Athlos Tools log-in in the upper right hand corner of the website.\n\nAs you know, Athlos curriculum focuses on teaching all the students 12 Performance Character Traits.  We believe that integrating those performance character traits in the school, on the turf and at home is a valuable method of reinforcing those values to the students.  Periodically through out the year, you will receive a notification to log into Athlos Tools and provide feedback and grading for your student on specific performance character traits.  Please keep the log-in information.  You will be provided an additional e-mail, along with instructions on how and when to grade your student.\n\nThank you for your commitment as we continue to teach all of our students the importance of Athlos Performance Character Traits.\n\n\t- Athlos Academies";
			
			$complete = $failed = 0;
			foreach($parents as $parent){
				
				$to = $parent->email;
				
				//-- Send MSG with Mailgun --//
				$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
				                  array('from'    => "Athlos Tools <noreply@athlosacademies.org>",
				                        'to'      => $to,
				                        'subject' => $subject,
				                        'text'    => $message));
				if($result){
					$complete++;
					//echo '<br>'.print_r($result, true);
				}else{
					$failed++;
				}
				
			}
			
			echo 'Total Successfully Sent: '.$complete.'<br>';
			echo 'Total Failures: '.$failed;
			
		}else{
			return $this->response->redirect("index/");
		}
		
	}*/
	
}
