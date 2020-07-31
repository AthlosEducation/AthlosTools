<?php

date_default_timezone_set('UTC');

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class PreparedController extends \Phalcon\Mvc\Controller
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
		if(!$this->cap['prepared-mind']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Prepared-Mind");
		//$this->view->setVar("navPage", "Traits");
	}
	
	public function indexAction()
	{
		/*--------------------------------------
			Instantiate Amazon Bucket S3 Code
		---------------------------------------*/
		//include the S3 class              
		/*if (!class_exists('S3'))require_once('amazon-s3/S3.php');
		//AWS access info
		if (!defined('awsAccessKey')) define('awsAccessKey', 'AKIAJIEMIW6FVXKO2QOQ');
		if (!defined('awsSecretKey')) define('awsSecretKey', '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');
		//instantiate the class
		$s3 = new S3(awsAccessKey, awsSecretKey);*/
		/*-- end instantiate amazon bucket code -*/
		
		/*$s3->putBucket("athlos-tools-prepared-minds-science-grade-0");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-1");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-2");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-3");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-4");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-5");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-6");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-7");
		$s3->putBucket("athlos-tools-prepared-minds-science-grade-8");
		
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-0");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-1");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-2");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-3");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-4");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-5");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-6");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-7");
		$s3->putBucket("athlos-tools-prepared-minds-social-studies-grade-8");
		
		$bucketList = $s3->listBuckets();
		$this->view->setVar("bList", $bucketList);*/
		
		return $this->response->redirect("prepared/curriculum");
	}
	
	public function curriculumAction()
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
			
		// Get the contents of our buckets
		$grades = array(0 => 'Kindergarten', 1 => '1st Grade', 2 => '2nd Grade', 3 => '3rd Grade', 4 => '4th Grade', 5 => '5th Grade', 6 => '6th Grade', 7 => '7th Grade', 8 => '8th Grade');
		$units = array('science', 'social-studies');
		$icon_map = array('science' => 'fa-flask', 'social-studies' => 'fa-group');
		$bucket_contents = array();
		//-- Setup Bucket Contents --//
		foreach($units as $unit){
			foreach($grades as $grade => $gradeName){
				$bucket_contents[$unit][$grade] = array($gradeName, $s3->getBucket("athlos-tools-prepared-minds-".$unit."-grade-".$grade));
			}
		}
		$this->view->setVar("bucket_contents", $bucket_contents);
		$this->view->setVar("icon_map", $icon_map);
		unset($bucket_contents);
	}
	
	public function newcurriculumAction()
	{
		//-- Priveleges --//
		if(!$this->cap['prepared-mind']['view']){
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
		
		//-- Create Basic Folder/Sub-Folder Array --//
		$folders = array();
		$level1_folders = CurriculumFolders::find(array("parent_id = 1"));
		if(!empty($level1_folders)){
			foreach($level1_folders as $l1){
				$folders[$l1->id] = array('name' => $l1->name, 'url' => $l1->url, 'icon' => $l1->icon, 'permissions' => unserialize($l1->permissions));
				$level2 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l1->id)));
				if(!empty($level2)){
					//-- Iterate through L2 subfolders --//
					foreach($level2 as $l2){
						$folders[$l1->id][$l2->id] = array('name' => $l2->name, 'url' => $l2->url, 'icon' => $l2->icon, 'permissions' => unserialize($l2->permissions));
						$level3 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l2->id)));
						if(!empty($level3)){
							//-- Iterate through L3 subfolders --//
							foreach($level3 as $l3){
								$folders[$l1->id][$l2->id][$l3->id] = array('name' => $l3->name, 'url' => $l3->url, 'icon' => $l3->icon, 'permissions' => unserialize($l3->permissions));
								$level4 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l3->id)));
								if(!empty($level4)){
									//-- Iterate through L4 subfolders --//
									foreach($level4 as $l4){
										$folders[$l1->id][$l2->id][$l3->id][$l4->id] = array('name' => $l4->name, 'url' => $l4->url, 'icon' => $l4->icon, 'permissions' => unserialize($l4->permissions));
										$folders[$l1->id][$l2->id][$l3->id][$l4->id][0] = $s3->getBucket("athlos-tools", "prepared-minds/".$l1->url."/".$l2->url."/".$l3->url."/".$l4->url."/");
									}
								}
							}
						}
					}
				}
			}
		}
		
		//-- Grab Campus States --//
		$campus_states = Schools::find(array("order" => "state ASC", "group" => "state", "columns" => "state"));
		$icons = array("fa-adjust", "fa-adn", "fa-align-center", "fa-align-justify", "fa-align-left", "fa-align-right", "fa-ambulance", "fa-anchor", "fa-angle-double-down", "fa-angle-double-left", "fa-angle-double-right", "fa-angle-double-up", "fa-angle-down", "fa-angle-left", "fa-angle-right", "fa-angle-up", "fa-archive", "fa-arrow-circle-down", "fa-arrow-circle-left", "fa-arrow-circle-o-down", "fa-arrow-circle-o-left", "fa-arrow-circle-o-right", "fa-arrow-circle-o-up", "fa-arrow-circle-right", "fa-arrow-circle-up", "fa-arrow-down", "fa-arrow-left", "fa-arrow-right", "fa-arrow-up", "fa-asterisk", "fa-backward", "fa-ban", "fa-bar-chart-o", "fa-barcode", "fa-beer", "fa-bell", "fa-bell-o", "fa-bitbucket", "fa-bold", "fa-bolt", "fa-book", "fa-bookmark", "fa-bookmark-o", "fa-briefcase", "fa-bug", "fa-building", "fa-bullhorn", "fa-bullseye", "fa-calendar", "fa-calendar-o", "fa-camera", "fa-camera-retro", "fa-caret-down", "fa-caret-left", "fa-caret-right", "fa-caret-square-o-down", "fa-caret-square-o-left", "fa-caret-square-o-right", "fa-caret-square-o-up", "fa-caret-up", "fa-certificate", "fa-chain", "fa-chain-broken", "fa-check", "fa-check-circle", "fa-check-circle-o", "fa-check-square", "fa-check-square-o", "fa-chevron-circle-down", "fa-chevron-circle-left", "fa-chevron-circle-right", "fa-chevron-circle-up", "fa-chevron-down", "fa-chevron-left", "fa-chevron-right", "fa-chevron-up", "fa-circle", "fa-circle-o", "fa-clipboard", "fa-clock-o", "fa-cloud", "fa-cloud-download", "fa-cloud-upload", "fa-cny", "fa-code", "fa-code-fork", "fa-coffee", "fa-cog", "fa-cogs", "fa-columns", "fa-comment", "fa-comment-o", "fa-comments", "fa-comments-o", "fa-compass", "fa-copy", "fa-credit-card", "fa-crop", "fa-crosshairs", "fa-cut", "fa-cutlery", "fa-dashboard", "fa-dedent", "fa-desktop", "fa-dollar", "fa-dot-circle-o", "fa-download", "fa-dribbble", "fa-dropbox", "fa-edit", "fa-eject", "fa-envelope", "fa-envelope-o", "fa-eraser", "fa-eur", "fa-euro", "fa-exchange", "fa-exclamation", "fa-exclamation-circle", "fa-exclamation-triangle", "fa-external-link", "fa-external-link-square", "fa-eye", "fa-eye-slash", "fa-facebook", "fa-facebook-square", "fa-fast-backward", "fa-fast-forward", "fa-female", "fa-fighter-jet", "fa-file", "fa-file-o", "fa-file-text", "fa-file-text-o", "fa-files-o", "fa-film", "fa-filter", "fa-fire", "fa-fire-extinguisher", "fa-flag", "fa-flag-checkered", "fa-flag-o", "fa-flash", "fa-flask", "fa-flickr", "fa-floppy-o", "fa-folder", "fa-folder-o", "fa-folder-open", "fa-folder-open-o", "fa-font", "fa-forward", "fa-foursquare", "fa-frown-o", "fa-gamepad", "fa-gavel", "fa-gbp", "fa-gear", "fa-gears", "fa-gift", "fa-gittip", "fa-glass", "fa-globe", "fa-google-plus", "fa-google-plus-square", "fa-group", "fa-h-square", "fa-hand-o-down", "fa-hand-o-left", "fa-hand-o-right", "fa-hand-o-up", "fa-headphones", "fa-heart", "fa-heart-o", "fa-home", "fa-inbox", "fa-indent", "fa-info", "fa-info-circle", "fa-inr", "fa-instagram", "fa-italic", "fa-jpy", "fa-key", "fa-keyboard-o", "fa-krw", "fa-laptop", "fa-leaf", "fa-legal", "fa-lemon-o", "fa-level-down", "fa-level-up", "fa-lightbulb-o", "fa-link", "fa-linux", "fa-list", "fa-list-alt", "fa-list-ol", "fa-list-ul", "fa-location-arrow", "fa-lock", "fa-long-arrow-down", "fa-long-arrow-left", "fa-long-arrow-right", "fa-long-arrow-up", "fa-magic", "fa-magnet", "fa-mail-forward", "fa-mail-reply", "fa-mail-reply-all", "fa-male", "fa-map-marker", "fa-maxcdn", "fa-medkit", "fa-meh-o", "fa-microphone", "fa-microphone-slash", "fa-minus", "fa-minus-circle", "fa-minus-square", "fa-minus-square-o", "fa-mobile", "fa-mobile-phone", "fa-money", "fa-moon-o", "fa-music", "fa-outdent", "fa-pagelines", "fa-paperclip", "fa-paste", "fa-pause", "fa-pencil", "fa-pencil-square", "fa-pencil-square-o", "fa-phone", "fa-phone-square", "fa-picture-o", "fa-pinterest", "fa-pinterest-square", "fa-plane", "fa-play", "fa-play-circle", "fa-play-circle-o", "fa-plus", "fa-plus-circle", "fa-plus-square", "fa-power-off", "fa-print", "fa-puzzle-piece", "fa-qrcode", "fa-question", "fa-question-circle", "fa-quote-left", "fa-quote-right", "fa-random", "fa-refresh", "fa-renren", "fa-reorder", "fa-repeat", "fa-reply", "fa-reply-all", "fa-retweet", "fa-rmb", "fa-road", "fa-rocket", "fa-rotate-left", "fa-rotate-right", "fa-rouble", "fa-rss", "fa-rss-square", "fa-rub", "fa-ruble", "fa-rupee", "fa-save", "fa-scissors", "fa-search", "fa-search-minus", "fa-search-plus", "fa-share", "fa-share-square", "fa-share-square-o", "fa-shield", "fa-shopping-cart", "fa-sign-in", "fa-sign-out", "fa-signal", "fa-sitemap", "fa-smile-o", "fa-sort", "fa-sort-alpha-asc", "fa-sort-alpha-desc", "fa-sort-amount-asc", "fa-sort-amount-desc", "fa-sort-asc", "fa-sort-desc", "fa-sort-down", "fa-sort-numeric-asc", "fa-sort-numeric-desc", "fa-sort-up", "fa-spinner", "fa-square", "fa-square-o", "fa-stack-exchange", "fa-stack-overflow", "fa-star", "fa-star-half", "fa-star-half-empty", "fa-star-half-full", "fa-star-half-o", "fa-star-o", "fa-step-backward", "fa-step-forward", "fa-stethoscope", "fa-stop", "fa-strikethrough", "fa-subscript", "fa-suitcase", "fa-sun-o", "fa-superscript", "fa-table", "fa-tablet", "fa-tachometer", "fa-tag", "fa-tags", "fa-tasks", "fa-terminal", "fa-text-height", "fa-text-width", "fa-th", "fa-th-large", "fa-th-list", "fa-thumb-tack", "fa-thumbs-down", "fa-thumbs-o-down", "fa-thumbs-o-up", "fa-thumbs-up", "fa-ticket", "fa-times", "fa-times-circle", "fa-times-circle-o", "fa-tint", "fa-toggle-down", "fa-toggle-left", "fa-toggle-right", "fa-toggle-up", "fa-trash-o", "fa-trello", "fa-trophy", "fa-truck", "fa-twitter", "fa-twitter-square", "fa-umbrella", "fa-underline", "fa-undo", "fa-unlink", "fa-unlock", "fa-unsorted", "fa-upload", "fa-usd", "fa-user", "fa-user-md", "fa-video-camera", "fa-volume-down", "fa-volume-off", "fa-volume-up", "fa-warning", "fa-wheelchair", "fa-wrench");
		$this->view->setVar("icons", $icons);
		$this->view->setVar("campus_states", $campus_states);
		$this->view->setVar("folders", $folders);
		$this->view->setVar("cap", $this->cap);
		unset($folders);
	} //-- end newcurriculumAction --//
	
	public function newmanageAction()
	{
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		//-- Priveleges - ONLY SUPER ADMINS --//
		if(!$this->cap['prepared-mind']['manage']){
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
					$folder = $this->request->getPost("level4-folder", "int");
					$folder_str = '';
					
					//-- Verify folder was sent, and is acceptable value --//
					if(isset($folder) && !empty($folder) && is_numeric($folder)){
						//-- Grab Folder and it's parent folders --//
						$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $folder)));
						if(!empty($the_folder)){
							$folder_str = $the_folder->url;
							while($the_folder->parent_id != 0){
								$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $the_folder->parent_id)));
								if(!empty($the_folder)){
									$folder_str = $the_folder->url.'/'.$folder_str;
								}
							}
						}else{
							$this->flashSession->error($preMsg."<strong>Error</strong> The folder selected was invalid, please refresh page and try again.");
						}
						
						//move the file
						if($s3->putObjectFile($fileTempName, "athlos-tools", $folder_str."/".$fileName, S3::ACL_PUBLIC_READ)) {
							$this->flashSession->success($preMsg."<strong>Success</strong> We successfully uploaded your file.");
						}else{
							$this->flashSession->error($preMsg."<strong>Error</strong> Something went wrong while uploading your file... sorry.");
						}
					}else{
						$this->flashSession->error($preMsg."<strong>Error</strong> The folder selected was invalid, please refresh page and try again.");
					}	
				}
			}
		}
		
		//-- Create Basic Folder/Sub-Folder Array --//
		$folders = array();
		$level1_folders = CurriculumFolders::find(array("parent_id = 1"));
		if(!empty($level1_folders)){
			foreach($level1_folders as $l1){
				$folders[$l1->id] = array('name' => $l1->name, 'url' => $l1->url, 'icon' => $l1->icon, 'permissions' => unserialize($l1->permissions));
				$level2 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l1->id)));
				if(!empty($level2)){
					//-- Iterate through L2 subfolders --//
					foreach($level2 as $l2){
						$folders[$l1->id][$l2->id] = array('name' => $l2->name, 'url' => $l2->url, 'icon' => $l2->icon, 'permissions' => unserialize($l2->permissions));
						$level3 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l2->id)));
						if(!empty($level3)){
							//-- Iterate through L3 subfolders --//
							foreach($level3 as $l3){
								$folders[$l1->id][$l2->id][$l3->id] = array('name' => $l3->name, 'url' => $l3->url, 'icon' => $l3->icon, 'permissions' => unserialize($l3->permissions));
								$level4 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l3->id)));
								if(!empty($level4)){
									//-- Iterate through L4 subfolders --//
									foreach($level4 as $l4){
										$folders[$l1->id][$l2->id][$l3->id][$l4->id] = array('name' => $l4->name, 'url' => $l4->url, 'icon' => $l4->icon, 'permissions' => unserialize($l4->permissions));
										$folders[$l1->id][$l2->id][$l3->id][$l4->id][0] = $s3->getBucket("athlos-tools", "prepared-minds/".$l1->url."/".$l2->url."/".$l3->url."/".$l4->url."/");
									}
								}
							}
						}
					}
				}
			}
		}
		
		//-- Grab Campus States --//
		$campus_states = Schools::find(array("order" => "state ASC", "group" => "state", "columns" => "state"));
		$icons = array("fa-adjust", "fa-adn", "fa-align-center", "fa-align-justify", "fa-align-left", "fa-align-right", "fa-ambulance", "fa-anchor", "fa-angle-double-down", "fa-angle-double-left", "fa-angle-double-right", "fa-angle-double-up", "fa-angle-down", "fa-angle-left", "fa-angle-right", "fa-angle-up", "fa-archive", "fa-arrow-circle-down", "fa-arrow-circle-left", "fa-arrow-circle-o-down", "fa-arrow-circle-o-left", "fa-arrow-circle-o-right", "fa-arrow-circle-o-up", "fa-arrow-circle-right", "fa-arrow-circle-up", "fa-arrow-down", "fa-arrow-left", "fa-arrow-right", "fa-arrow-up", "fa-asterisk", "fa-backward", "fa-ban", "fa-bar-chart-o", "fa-barcode", "fa-beer", "fa-bell", "fa-bell-o", "fa-bitbucket", "fa-bold", "fa-bolt", "fa-book", "fa-bookmark", "fa-bookmark-o", "fa-briefcase", "fa-bug", "fa-building", "fa-bullhorn", "fa-bullseye", "fa-calendar", "fa-calendar-o", "fa-camera", "fa-camera-retro", "fa-caret-down", "fa-caret-left", "fa-caret-right", "fa-caret-square-o-down", "fa-caret-square-o-left", "fa-caret-square-o-right", "fa-caret-square-o-up", "fa-caret-up", "fa-certificate", "fa-chain", "fa-chain-broken", "fa-check", "fa-check-circle", "fa-check-circle-o", "fa-check-square", "fa-check-square-o", "fa-chevron-circle-down", "fa-chevron-circle-left", "fa-chevron-circle-right", "fa-chevron-circle-up", "fa-chevron-down", "fa-chevron-left", "fa-chevron-right", "fa-chevron-up", "fa-circle", "fa-circle-o", "fa-clipboard", "fa-clock-o", "fa-cloud", "fa-cloud-download", "fa-cloud-upload", "fa-cny", "fa-code", "fa-code-fork", "fa-coffee", "fa-cog", "fa-cogs", "fa-columns", "fa-comment", "fa-comment-o", "fa-comments", "fa-comments-o", "fa-compass", "fa-copy", "fa-credit-card", "fa-crop", "fa-crosshairs", "fa-cut", "fa-cutlery", "fa-dashboard", "fa-dedent", "fa-desktop", "fa-dollar", "fa-dot-circle-o", "fa-download", "fa-dribbble", "fa-dropbox", "fa-edit", "fa-eject", "fa-envelope", "fa-envelope-o", "fa-eraser", "fa-eur", "fa-euro", "fa-exchange", "fa-exclamation", "fa-exclamation-circle", "fa-exclamation-triangle", "fa-external-link", "fa-external-link-square", "fa-eye", "fa-eye-slash", "fa-facebook", "fa-facebook-square", "fa-fast-backward", "fa-fast-forward", "fa-female", "fa-fighter-jet", "fa-file", "fa-file-o", "fa-file-text", "fa-file-text-o", "fa-files-o", "fa-film", "fa-filter", "fa-fire", "fa-fire-extinguisher", "fa-flag", "fa-flag-checkered", "fa-flag-o", "fa-flash", "fa-flask", "fa-flickr", "fa-floppy-o", "fa-folder", "fa-folder-o", "fa-folder-open", "fa-folder-open-o", "fa-font", "fa-forward", "fa-foursquare", "fa-frown-o", "fa-gamepad", "fa-gavel", "fa-gbp", "fa-gear", "fa-gears", "fa-gift", "fa-gittip", "fa-glass", "fa-globe", "fa-google-plus", "fa-google-plus-square", "fa-group", "fa-h-square", "fa-hand-o-down", "fa-hand-o-left", "fa-hand-o-right", "fa-hand-o-up", "fa-headphones", "fa-heart", "fa-heart-o", "fa-home", "fa-inbox", "fa-indent", "fa-info", "fa-info-circle", "fa-inr", "fa-instagram", "fa-italic", "fa-jpy", "fa-key", "fa-keyboard-o", "fa-krw", "fa-laptop", "fa-leaf", "fa-legal", "fa-lemon-o", "fa-level-down", "fa-level-up", "fa-lightbulb-o", "fa-link", "fa-linux", "fa-list", "fa-list-alt", "fa-list-ol", "fa-list-ul", "fa-location-arrow", "fa-lock", "fa-long-arrow-down", "fa-long-arrow-left", "fa-long-arrow-right", "fa-long-arrow-up", "fa-magic", "fa-magnet", "fa-mail-forward", "fa-mail-reply", "fa-mail-reply-all", "fa-male", "fa-map-marker", "fa-maxcdn", "fa-medkit", "fa-meh-o", "fa-microphone", "fa-microphone-slash", "fa-minus", "fa-minus-circle", "fa-minus-square", "fa-minus-square-o", "fa-mobile", "fa-mobile-phone", "fa-money", "fa-moon-o", "fa-music", "fa-outdent", "fa-pagelines", "fa-paperclip", "fa-paste", "fa-pause", "fa-pencil", "fa-pencil-square", "fa-pencil-square-o", "fa-phone", "fa-phone-square", "fa-picture-o", "fa-pinterest", "fa-pinterest-square", "fa-plane", "fa-play", "fa-play-circle", "fa-play-circle-o", "fa-plus", "fa-plus-circle", "fa-plus-square", "fa-power-off", "fa-print", "fa-puzzle-piece", "fa-qrcode", "fa-question", "fa-question-circle", "fa-quote-left", "fa-quote-right", "fa-random", "fa-refresh", "fa-renren", "fa-reorder", "fa-repeat", "fa-reply", "fa-reply-all", "fa-retweet", "fa-rmb", "fa-road", "fa-rocket", "fa-rotate-left", "fa-rotate-right", "fa-rouble", "fa-rss", "fa-rss-square", "fa-rub", "fa-ruble", "fa-rupee", "fa-save", "fa-scissors", "fa-search", "fa-search-minus", "fa-search-plus", "fa-share", "fa-share-square", "fa-share-square-o", "fa-shield", "fa-shopping-cart", "fa-sign-in", "fa-sign-out", "fa-signal", "fa-sitemap", "fa-smile-o", "fa-sort", "fa-sort-alpha-asc", "fa-sort-alpha-desc", "fa-sort-amount-asc", "fa-sort-amount-desc", "fa-sort-asc", "fa-sort-desc", "fa-sort-down", "fa-sort-numeric-asc", "fa-sort-numeric-desc", "fa-sort-up", "fa-spinner", "fa-square", "fa-square-o", "fa-stack-exchange", "fa-stack-overflow", "fa-star", "fa-star-half", "fa-star-half-empty", "fa-star-half-full", "fa-star-half-o", "fa-star-o", "fa-step-backward", "fa-step-forward", "fa-stethoscope", "fa-stop", "fa-strikethrough", "fa-subscript", "fa-suitcase", "fa-sun-o", "fa-superscript", "fa-table", "fa-tablet", "fa-tachometer", "fa-tag", "fa-tags", "fa-tasks", "fa-terminal", "fa-text-height", "fa-text-width", "fa-th", "fa-th-large", "fa-th-list", "fa-thumb-tack", "fa-thumbs-down", "fa-thumbs-o-down", "fa-thumbs-o-up", "fa-thumbs-up", "fa-ticket", "fa-times", "fa-times-circle", "fa-times-circle-o", "fa-tint", "fa-toggle-down", "fa-toggle-left", "fa-toggle-right", "fa-toggle-up", "fa-trash-o", "fa-trello", "fa-trophy", "fa-truck", "fa-twitter", "fa-twitter-square", "fa-umbrella", "fa-underline", "fa-undo", "fa-unlink", "fa-unlock", "fa-unsorted", "fa-upload", "fa-usd", "fa-user", "fa-user-md", "fa-video-camera", "fa-volume-down", "fa-volume-off", "fa-volume-up", "fa-warning", "fa-wheelchair", "fa-wrench");
		$this->view->setVar("icons", $icons);
		$this->view->setVar("campus_states", $campus_states);
		$this->view->setVar("folders", $folders);
		$this->view->setVar("cap", $this->cap);
		unset($folders);
		
	} //-- end newmanageAction() --//
	
	public function addfolderAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Add Student --//
			if($this->request->getPost("action") == 'add_folder'){
				//-- Sanitize Vars --//
				$name = $this->request->getPost("folder", "string");
				$url = $this->request->getPost("url", "string");
				$parent = $this->request->getPost("fparent", "int");
				$icon = $this->request->getPost("icon", "string");
				$permissions = $this->request->getPost("permissions");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['prepared-mind']['manage']){
					
					//-- Make sure the required info is present --//
					if($name && $url && $icon && !empty($permissions)){
						//-- serialize permissions --//
						$permissions = serialize($permissions);
						
						//-- Set empty parent to be child of "Prepared Minds" Folder --//
						if(empty($parent)){ $parent = 1; }
						
						//-- Make sure parent exists --//
						$checkParent = CurriculumFolders::findFirst(array('id = :id:', "bind" => array('id' => $parent)));
						if(!empty($checkParent)){
							//-- Check to see folder under parent already exists --//
							$checkFolder = CurriculumFolders::count(array("url = :url: AND parent_id = :parent:", "bind" => array("url" => $url, "parent" => $parent)));
							if(!$checkFolder || empty($checkFolder)){
								/*---------------------------------------------
									Now Add Student -- Passed all validation
								----------------------------------------------*/
								$folder = New CurriculumFolders();
								$folder->name = $name;
								$folder->url = $url;
								$folder->parent_id = $parent;
								$folder->icon = $icon;
								$folder->permissions = $permissions;

								//-- Save Entry --//
								if($folder->save() == false){
									$results["result"] = "failed";
									$results["error_title"] = "Failed to Add Folder";
									$results["error_msg"] = "Something went wrong, and the folder was not added.";
								}else{
									$results["result"] = "success";
									$results["newid"] = $folder->id;
									$results["visible"] = implode(',', unserialize($folder->permissions));
									$the_folder = CurriculumFolders::findFirst(array('id = :pid:', 'bind' => array('pid' => $folder->parent_id), "columns" => "parent_id"));
									//-- Return Folder Ancestors --//
									if(!empty($the_folder) && $the_folder->parent_id != 1){
										$results["parents"][] = $the_folder->parent_id;
										while($the_folder->parent_id != 1){
											$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $the_folder->parent_id), "columns" => "parent_id"));
											if(!empty($the_folder)){
												$results["parents"][] = $the_folder->parent_id;
											}
										}
									}else{
										$results["noparent"] = 1;
									}
								}
							}else{
								$results['result'] = "failed";
								$results["error_title"] = "Duplicate Folder";
								$results["error_msg"] = "Folder url already exists under current parent. Change name or parent of folder.";
							}
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Incorrect Parent Folder";
							$results["error_msg"] = "Parent Folder does not exist, please select a new one or refresh your page and try again.";
						}	
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Something Is Missing";
						$results["error_msg"] = "Make sure folder name, icon and folder permissions have been entered.";
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
		
	} //-- end addfolderAction() --//
	
	public function editfolderAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Grab User --//
			if($this->request->getPost("action") == 'grab_folder'){
				
				//-- grab / set vars --//
				$folderID = $this->request->getPost("theID", "int");
				$results = array();

				if($folderID && is_numeric($folderID)){

					//-- Grab Student --//
					$folder = CurriculumFolders::findFirst(array(
						"conditions" => "id = :folderID:",
						"bind" => array('folderID' => $folderID)
						));

					//-- the student json object --//
					$results["result"] = "success";
					$results["id"] = $folder->id;
					$results["name"] = $folder->name;
					$results["url"] = $folder->url;
					$results["parent"] = $folder->parent_id;
					$results["icon"] = $folder->icon;
					$results["permissions"] = unserialize($folder->permissions);
					
				}else{
					//invalid input
					$results["result"] = "failed";
				}

				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Grab_Folder --//
			
			//-- Function to Edit Student --//
			if($this->request->getPost("action") == 'edit_folder'){
				
				//-- Sanitize Vars --//
				$folderID = $this->request->getPost("folder_id", "int");
				$name = $this->request->getPost("folder", "string");
				$parent = $this->request->getPost("fparent", "int");
				$icon = $this->request->getPost("icon", "string");
				$permissions = $this->request->getPost("permissions");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['prepared-mind']['manage']){
					
					//-- Make sure the required info is present --//
					if($folderID && $name && ($parent || $parent == '') && $icon && !empty($permissions)){
						//-- serialize permissions --//
						$permissions = serialize($permissions);
						
						//-- Set empty parent to be child of "Prepared Minds" Folder --//
						if($parent == ''){ $parent = 1; }
						
						//-- Make sure parent exists --//
						$checkParent = CurriculumFolders::findFirst(array('id = :id:', "bind" => array('id' => $parent)));
						if(!empty($checkParent)){
							/*------------------------
								Grab Folder Object
							-------------------------*/
							$folder = CurriculumFolders::findFirst(array(
								"conditions" => "id = :folderID:",
								"bind" => array('folderID' => $folderID)
							));
							
							/*------------------------------------------------
								Now Update Folder -- Passed all validation
							-------------------------------------------------*/
							$folder->name = $name;
							$folder->parent_id = $parent;
							$folder->icon = $icon;
							$folder->permissions = $permissions;

							//-- Save Entry --//
							if($folder->save() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Failed to Update Folder";
								$results["error_msg"] = "Something went wrong, and the folder was not updated.";
							}else{
								$results["result"] = "success";
								$results["newid"] = $folder->id;
								$results["visible"] = implode(',', unserialize($folder->permissions));
								$the_folder = CurriculumFolders::findFirst(array('id = :pid:', 'bind' => array('pid' => $folder->parent_id), "columns" => "parent_id"));
								//-- Return Folder Ancestors --//
								if(!empty($the_folder) && $the_folder->parent_id != 1){
									$results["parents"][] = $the_folder->parent_id;
									while($the_folder->parent_id != 1){
										$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $the_folder->parent_id), "columns" => "parent_id"));
										if(!empty($the_folder)){
											$results["parents"][] = $the_folder->parent_id;
										}
									}
								}else{
									$results["noparent"] = 1;
								}
							}
														
						}else{
							$results['result'] = "failed";
							$results["error_title"] = "Incorrect Parent Folder";
							$results["error_msg"] = "Parent Folder does not exist, please select a new one or refresh your page and try again.";
						}
					}else{
						$results['result'] = "failed";
						$results["error_title"] = "Something Is Missing";
						$results["error_msg"] = "Make sure folder name, icon and folder permissions have been entered.";
					}
					
				}else{
					//-- Not Enough Permissions --//
					$results['result'] = "failed";
					$results["error_title"] = "Failure - No Permissions";
					$results["error_msg"] = "Oops! Looks like your not allowed here. You can not perform that action.";
				}
				
				//-- encode results --//
				echo json_encode($results);
				
			} //-- end Edit_Folder --//
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end editfolderAction() --//
	
	public function delfolderAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Delete Folder --//
			if($this->request->getPost("action") == 'delete_folder'){
				
				//-- grab / set / sanitize vars --//
				$folderID = $this->request->getPost("theID", "int");
				$results = array();
				
				//-- Verify Permissions --//
				if($this->cap['prepared-mind']['manage']){

					if($folderID && is_numeric($folderID)){

						//-- Grab Folder --//
						$folder = CurriculumFolders::findFirst(array(
							"conditions" => "id = :folderID:",
							"bind" => array('folderID' => $folderID)
						));
							
						if($folder){
							//-- Retreive & Delete Child Folders --//
							$additional_folders = CurriculumFolders::find(array(
								"conditions" => "parent_id = :folderID:",
								"bind" => array('folderID' => $folderID)
							));
							if(!empty($additional_folders)){
								foreach($additional_folders as $aFolder){
									//-- Retreive & Delete GrandChild Folders --//
									$more_folders = CurriculumFolders::find(array(
										"conditions" => "parent_id = :folderID:",
										"bind" => array('folderID' => $aFolder->id)
									));
									if(!empty($more_folders)){
										foreach($more_folders as $mFolder){
											//-- Retreive & Delete GrandChild Folders --//
											$last_folders = CurriculumFolders::find(array(
												"conditions" => "parent_id = :folderID:",
												"bind" => array('folderID' => $mFolder->id)
											));
											if(!empty($last_folders)){
												foreach($last_folders as $lFolder){
													//-- Delete Great GrandChild folders --//
													$lFolder->delete();
												}
											}
											//-- Delete GrandChild folders --//
											$mFolder->delete();
										}
									}
									//-- Delete Child Folders --//
									$aFolder->delete();
								}
							}
							
							//-- Delete from DB --//
							if($folder->delete() == false){
							    $results['result'] = "failed";
								$results["error_title"] = "Failed to Delete Folder";
								$results["error_msg"] = "Something went wrong, and the folder was not deleted.";
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
		
	} //-- end delfolderAction() --//
	
	
	public function manageAction()
	{
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		//-- Priveleges - ONLY SUPER ADMINS --//
		if($this->cap['prepared-mind']['manage']){
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
					$gLevel = $this->request->getPost("gradelevel", "int");
					$cUnit = $this->request->getPost("curric-unit", "string");
					$levels = array(100, 1, 2, 3, 4, 5, 6, 7, 8);
					$units = array('science', 'social-studies');
					
					//-- Verify Level was sent, and is acceptable value --//
					if(isset($gLevel) && in_array($gLevel, $levels)){
						$theLevel = $gLevel;
						if($theLevel == 100){ $theLevel = 0; }
						//-- Verify Unit was sent, and is acceptable value --//
						if(isset($cUnit) && in_array($cUnit, $units)){
							//move the file
							if($s3->putObjectFile($fileTempName, "athlos-tools-prepared-minds-".$cUnit."-grade-".$theLevel, $fileName, S3::ACL_PUBLIC_READ)) {
								$this->flashSession->success($preMsg."<strong>Success</strong> We successfully uploaded your file.");
							}else{
								$this->flashSession->error($preMsg."<strong>Error</strong> Something went wrong while uploading your file... sorry.");
							}
						}else{
							$this->flashSession->error($preMsg."<strong>Error</strong> Curricular Unit was either not indicated or had an invalid value.");
						}
					}else{
						$this->flashSession->error($preMsg."<strong>Error</strong> Grade Level was either not indicated or had an invalid value.");
					}	
				}
			}
		}
		
		// Get the contents of our buckets
		$grades = array(0 => 'Kindergarten', 1 => '1st Grade', 2 => '2nd Grade', 3 => '3rd Grade', 4 => '4th Grade', 5 => '5th Grade', 6 => '6th Grade', 7 => '7th Grade', 8 => '8th Grade');
		$units = array('science', 'social-studies');
		$icon_map = array('science' => 'fa-flask', 'social-studies' => 'fa-group');
		$bucket_contents = array();
		//-- Setup Bucket Contents --//
		foreach($units as $unit){
			foreach($grades as $grade => $gradeName){
				$bucket_contents[$unit][$grade] = array($gradeName, $s3->getBucket("athlos-tools-prepared-minds-".$unit."-grade-".$grade));
			}
		}
		$this->view->setVar("bucket_contents2", $bucket_contents);
		$this->view->setVar("icon_map", $icon_map);
		unset($bucket_contents);
		
	} //-- end manageAction() --//
	
	
	public function newdeleteresourceAction()
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
				
				//-- Verify Permissions --//
				if($this->cap['prepared-mind']['manage']){
					
					//-- Sanitize Vars --//
					$filename = trim($this->request->getPost("filename", "string"));
					$folder = trim($this->request->getPost("folder", "int"));
					$results = array();
					
					//-- Make sure the required info is present --//
					if($filename && $folder && !empty($folder) && is_numeric($folder)){
						//-- Build folder string --//
						$folder_str = '';
						//-- Grab Folder and it's parent folders --//
						$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $folder)));
						if(!empty($the_folder)){
							$folder_str = $the_folder->url;
							while($the_folder->parent_id != 0){
								$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $the_folder->parent_id)));
								if(!empty($the_folder)){
									$folder_str = $the_folder->url.'/'.$folder_str;
								}
							}
							
							//-- delete the file --//
							if($s3->deleteObject("athlos-tools", $folder_str."/".$filename)) {
								$results["result"] = "success";
							}else{
								$results["result"] = "failed";
							}
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
		
	} //-- end newdeleteresourceAction() --//
	
	
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
				
				//-- Verify Permissions --//
				if($this->cap['prepared-mind']['manage']){
					
					//-- Sanitize Vars --//
					$filename = trim($this->request->getPost("filename", "string"));
					$gLevel = trim($this->request->getPost("level", "int"));
					$unit = trim($this->request->getPost("unit", "string"));
					$units = array('science', 'social-studies');
					$results = array();
					
					//-- Make sure the required info is present --//
					if($filename && $gLevel && is_numeric($gLevel) && in_array($unit, $units)){
						//-- form file url --//
						$folderUrl = "athlos-tools-prepared-minds-".$unit."-grade-".$gLevel;
						
						//-- delete the file --//
						if($s3->deleteObject($folderUrl, $filename)) {
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
	
	
	public function suggestionsAction()
    {
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		/*------------------------------------
			Validate Suggestion Submission
		-------------------------------------*/
		$grades = array(100 => 'Kindergarten', 1 => '1st Grade', 2 => '2nd Grade', 3 => '3rd Grade', 4 => '4th Grade', 5 => '5th Grade', 6 => '6th Grade', 7 => '7th Grade', 8 => '8th Grade');
		$units = array('science', 'social-studies');
		
		//-- Check if request has made with POST --//
		if($this->request->isPost() == true && $this->request->getPost("form-action") == 'submit-suggestion'){
			
			//-- Initialize Error Boolean --//
			$errFound = true;
			
			//-- Sanitize Inputs --//
			$title = htmlentities($this->request->getPost("post-subject"), ENT_QUOTES);
			$content = htmlentities($this->request->getPost("post_content"), ENT_QUOTES);
			$author_email = $this->request->getPost("author_email", "email");
			$level = $this->request->getPost("gradelevel", "int");
			$unit = $this->request->getPost("curric-unit", "string");
			
			//-- Make sure all required fields are present --//
			if($title && $content && $author_email && $level && $unit){
				
				//-- Send "Suggestion" email notification to Chandler --//
				//$to = 'amacdonald@athlosacademies.org';
				$to = 'cherdt@athlosacademies.org';
				$subject = "Prepared Mind: Suggestion";
				$message = "A new suggestion for the Prepared Mind Curriculum has been submitted<br /><br />The submitted contents are as follows:<br /><br />Curricular Unit: ".ucwords(str_ireplace('-', ' ', $unit))."<br />Grade Level: ".$grades[$level]."<br /><br />Suggestion Title: ".$title."<br /><br />Suggestion Content:<br />".$content."<br /><br />From: ".$author_email;
				
				//-- Setup Mailgun Object --//
				$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
				
				//-- Send MSG with mailgun --//
				$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
				                  array('from'    => "Athlos Tools <".$author_email.">",
				                        'to'      => $to,
				                        'subject' => $subject,
				                        'html'    => $message));
				//-- Success / Error Messages --//
				if($result){
					//-- Set Error Boolean to False --//
					$errFound = false;
					$this->flashSession->success($preMsg."<strong>Suggestion Sent Successfully!</strong> Your suggestion was sent successfully. Thank you.");
				}else{
					$this->flashSession->error($preMsg."<strong>Failed to Send!</strong> The suggestion was not sent. Please Try Again.");
				}
				
			}else{
				$this->flashSession->error($preMsg."<strong>Missing Required Fields</strong> Make sure the post has a title, content, Unit, and Grade Level.");
			}	
				
			//-- Pass Error Boolean to View --//
			$this->view->setVar("errFound", $errFound);
			
		} //-- end if there is POST data -- end validation --//
		/*--------------------
			end Validation
		---------------------*/
		
		//-- Pass arrays to view --//
		$this->view->setVar("grades", $grades);
		$this->view->setVar("units", $units);
		
    } //-- end suggestionsAction() --//
	
	
	public function reportAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars --//
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$subject = $this->request->getPost("issue_subject", "string");
			$issue = $this->request->getPost("issue_content", "string");

			//-- Send "Issue Report" email notification to Super Admins --//
			//$to = 'amacdonald@athlosacademies.org';
			$to = 'cherdt@athlosacademies.org';
			$subject = "Prepared Minds: Report Bug";
			$message = "A new bug has been reported in the Prepared Mind Curriculum.<br />Included below are the details to the issue as reported by the user. Please review their response as well as take some time to look into the bug being reported.<br /><br />Issue Title: ".$subject."<br /><br />Issue Details:<br />".$issue."<br /><br />Thanks again,<br /><br />- Athlos Tools";
			
			//-- Setup Mailgun Object --//
			$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
			
			//-- Send MSG with mailgun --//
			$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
			                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
			                        'to'      => $to,
			                        'subject' => $subject,
			                        'html'    => $message));
			//-- Success / Error Messages --//
			if($result){
				$this->flashSession->success($preMsg."<strong>Bug reported!</strong> Your report was sent successfully. Thank you.");
			}else{
				$this->flashSession->error($preMsg."<strong>Bug report failed!</strong> The issue was not sent. Please Try Again.");
			}

			return $this->response->redirect("prepared/suggestions");
		}
	} //-- end reportAction() --//
	
}
