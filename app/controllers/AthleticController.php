<?php

date_default_timezone_set('UTC');

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class AthleticController extends \Phalcon\Mvc\Controller
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
		if(!$this->cap['assessments']['view'] && !$this->cap['athletic-curriculum']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Athletic");
		//$this->view->setVar("navPage", "Traits");
	}

	public function indexAction()
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
		$bucket_contents = array(
			1 => $s3->getBucket("athlos-tools-athletic-level-1"),
			2 => $s3->getBucket("athlos-tools-athletic-level-2"),
			3 => $s3->getBucket("athlos-tools-athletic-level-3"),
			4 => $s3->getBucket("athlos-tools-athletic-level-4")
			);
		$this->view->setVar("bucket_contents", $bucket_contents);
	}

	public function testingAction()
	{
		//-- Permissions --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		if(!$this->cap['assessments']['protocols']){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}

		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Assessments");
	}

	public function viewerAction()
	{

	} //-- end viewerAction() --//


	public function curriculumAction()
	{
		//-- Permissions --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		if(!$this->cap['athletic-curriculum']['view']){
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
		$level1_folders = CurriculumFolders::find(array("parent_id = 150"));
		if(!empty($level1_folders)){
			foreach($level1_folders as $l1){
				$folders[$l1->id] = array('name' => $l1->name, 'url' => $l1->url, 'icon' => $l1->icon, 'permissions' => unserialize($l1->permissions));
				$level2 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l1->id)));
				if(!empty($level2)){
					//-- Iterate through L2 subfolders --//
					foreach($level2 as $l2){
						$folders[$l1->id][$l2->id] = array('name' => $l2->name, 'url' => $l2->url, 'icon' => $l2->icon, 'permissions' => unserialize($l2->permissions));
						$folders[$l1->id][$l2->id][0] = $s3->getBucket("athlos-tools", "athletic-curriculum/".$l1->url."/".$l2->url."/");
					}
				}
			}
		}

		//-- Grab Campus States --//
		$districts = Districts::find(array("order" => "abbreviation ASC", "columns" => "id, abbreviation"));
		$icons = array("fa-adjust", "fa-adn", "fa-align-center", "fa-align-justify", "fa-align-left", "fa-align-right", "fa-ambulance", "fa-anchor", "fa-angle-double-down", "fa-angle-double-left", "fa-angle-double-right", "fa-angle-double-up", "fa-angle-down", "fa-angle-left", "fa-angle-right", "fa-angle-up", "fa-archive", "fa-arrow-circle-down", "fa-arrow-circle-left", "fa-arrow-circle-o-down", "fa-arrow-circle-o-left", "fa-arrow-circle-o-right", "fa-arrow-circle-o-up", "fa-arrow-circle-right", "fa-arrow-circle-up", "fa-arrow-down", "fa-arrow-left", "fa-arrow-right", "fa-arrow-up", "fa-asterisk", "fa-backward", "fa-ban", "fa-bar-chart-o", "fa-barcode", "fa-beer", "fa-bell", "fa-bell-o", "fa-bitbucket", "fa-bold", "fa-bolt", "fa-book", "fa-bookmark", "fa-bookmark-o", "fa-briefcase", "fa-bug", "fa-building", "fa-bullhorn", "fa-bullseye", "fa-calendar", "fa-calendar-o", "fa-camera", "fa-camera-retro", "fa-caret-down", "fa-caret-left", "fa-caret-right", "fa-caret-square-o-down", "fa-caret-square-o-left", "fa-caret-square-o-right", "fa-caret-square-o-up", "fa-caret-up", "fa-certificate", "fa-chain", "fa-chain-broken", "fa-check", "fa-check-circle", "fa-check-circle-o", "fa-check-square", "fa-check-square-o", "fa-chevron-circle-down", "fa-chevron-circle-left", "fa-chevron-circle-right", "fa-chevron-circle-up", "fa-chevron-down", "fa-chevron-left", "fa-chevron-right", "fa-chevron-up", "fa-circle", "fa-circle-o", "fa-clipboard", "fa-clock-o", "fa-cloud", "fa-cloud-download", "fa-cloud-upload", "fa-cny", "fa-code", "fa-code-fork", "fa-coffee", "fa-cog", "fa-cogs", "fa-columns", "fa-comment", "fa-comment-o", "fa-comments", "fa-comments-o", "fa-compass", "fa-copy", "fa-credit-card", "fa-crop", "fa-crosshairs", "fa-cut", "fa-cutlery", "fa-dashboard", "fa-dedent", "fa-desktop", "fa-dollar", "fa-dot-circle-o", "fa-download", "fa-dribbble", "fa-dropbox", "fa-edit", "fa-eject", "fa-envelope", "fa-envelope-o", "fa-eraser", "fa-eur", "fa-euro", "fa-exchange", "fa-exclamation", "fa-exclamation-circle", "fa-exclamation-triangle", "fa-external-link", "fa-external-link-square", "fa-eye", "fa-eye-slash", "fa-facebook", "fa-facebook-square", "fa-fast-backward", "fa-fast-forward", "fa-female", "fa-fighter-jet", "fa-file", "fa-file-o", "fa-file-text", "fa-file-text-o", "fa-files-o", "fa-film", "fa-filter", "fa-fire", "fa-fire-extinguisher", "fa-flag", "fa-flag-checkered", "fa-flag-o", "fa-flash", "fa-flask", "fa-flickr", "fa-floppy-o", "fa-folder", "fa-folder-o", "fa-folder-open", "fa-folder-open-o", "fa-font", "fa-forward", "fa-foursquare", "fa-frown-o", "fa-gamepad", "fa-gavel", "fa-gbp", "fa-gear", "fa-gears", "fa-gift", "fa-gittip", "fa-glass", "fa-globe", "fa-google-plus", "fa-google-plus-square", "fa-group", "fa-h-square", "fa-hand-o-down", "fa-hand-o-left", "fa-hand-o-right", "fa-hand-o-up", "fa-headphones", "fa-heart", "fa-heart-o", "fa-home", "fa-inbox", "fa-indent", "fa-info", "fa-info-circle", "fa-inr", "fa-instagram", "fa-italic", "fa-jpy", "fa-key", "fa-keyboard-o", "fa-krw", "fa-laptop", "fa-leaf", "fa-legal", "fa-lemon-o", "fa-level-down", "fa-level-up", "fa-lightbulb-o", "fa-link", "fa-linux", "fa-list", "fa-list-alt", "fa-list-ol", "fa-list-ul", "fa-location-arrow", "fa-lock", "fa-long-arrow-down", "fa-long-arrow-left", "fa-long-arrow-right", "fa-long-arrow-up", "fa-magic", "fa-magnet", "fa-mail-forward", "fa-mail-reply", "fa-mail-reply-all", "fa-male", "fa-map-marker", "fa-maxcdn", "fa-medkit", "fa-meh-o", "fa-microphone", "fa-microphone-slash", "fa-minus", "fa-minus-circle", "fa-minus-square", "fa-minus-square-o", "fa-mobile", "fa-mobile-phone", "fa-money", "fa-moon-o", "fa-music", "fa-outdent", "fa-pagelines", "fa-paperclip", "fa-paste", "fa-pause", "fa-pencil", "fa-pencil-square", "fa-pencil-square-o", "fa-phone", "fa-phone-square", "fa-picture-o", "fa-pinterest", "fa-pinterest-square", "fa-plane", "fa-play", "fa-play-circle", "fa-play-circle-o", "fa-plus", "fa-plus-circle", "fa-plus-square", "fa-power-off", "fa-print", "fa-puzzle-piece", "fa-qrcode", "fa-question", "fa-question-circle", "fa-quote-left", "fa-quote-right", "fa-random", "fa-refresh", "fa-renren", "fa-reorder", "fa-repeat", "fa-reply", "fa-reply-all", "fa-retweet", "fa-rmb", "fa-road", "fa-rocket", "fa-rotate-left", "fa-rotate-right", "fa-rouble", "fa-rss", "fa-rss-square", "fa-rub", "fa-ruble", "fa-rupee", "fa-save", "fa-scissors", "fa-search", "fa-search-minus", "fa-search-plus", "fa-share", "fa-share-square", "fa-share-square-o", "fa-shield", "fa-shopping-cart", "fa-sign-in", "fa-sign-out", "fa-signal", "fa-sitemap", "fa-smile-o", "fa-sort", "fa-sort-alpha-asc", "fa-sort-alpha-desc", "fa-sort-amount-asc", "fa-sort-amount-desc", "fa-sort-asc", "fa-sort-desc", "fa-sort-down", "fa-sort-numeric-asc", "fa-sort-numeric-desc", "fa-sort-up", "fa-spinner", "fa-square", "fa-square-o", "fa-stack-exchange", "fa-stack-overflow", "fa-star", "fa-star-half", "fa-star-half-empty", "fa-star-half-full", "fa-star-half-o", "fa-star-o", "fa-step-backward", "fa-step-forward", "fa-stethoscope", "fa-stop", "fa-strikethrough", "fa-subscript", "fa-suitcase", "fa-sun-o", "fa-superscript", "fa-table", "fa-tablet", "fa-tachometer", "fa-tag", "fa-tags", "fa-tasks", "fa-terminal", "fa-text-height", "fa-text-width", "fa-th", "fa-th-large", "fa-th-list", "fa-thumb-tack", "fa-thumbs-down", "fa-thumbs-o-down", "fa-thumbs-o-up", "fa-thumbs-up", "fa-ticket", "fa-times", "fa-times-circle", "fa-times-circle-o", "fa-tint", "fa-toggle-down", "fa-toggle-left", "fa-toggle-right", "fa-toggle-up", "fa-trash-o", "fa-trello", "fa-trophy", "fa-truck", "fa-twitter", "fa-twitter-square", "fa-umbrella", "fa-underline", "fa-undo", "fa-unlink", "fa-unlock", "fa-unsorted", "fa-upload", "fa-usd", "fa-user", "fa-user-md", "fa-video-camera", "fa-volume-down", "fa-volume-off", "fa-volume-up", "fa-warning", "fa-wheelchair", "fa-wrench");
		$this->view->setVar("icons", $icons);
		$this->view->setVar("districts", $districts);
		$this->view->setVar("folders", $folders);
		unset($folders);
	} //-- end curriculumAction --//

	public function manageAction()
	{
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";

		//-- Permissions --//
		if(!$this->cap['athletic-curriculum']['manage']){
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
		/*-- end instantiate amazon bucket code --*/

		//-- Data was posted --//
		if($this->request->isPost() == true) {
			if($this->request->getPost("action") == 'upload_resource'){

				if(!empty($_FILES['theFile'])){
					$total = count($_FILES['theFile']['name']);
					$folder = $this->request->getPost("level2-folder", "int");
					$successList = $errorList = array();
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

						//-- Grab Multiple Files and upload them (if folder path exists) --//
						if(!empty($folder_str)){
							for($i = 0; $i < $total; $i++){
								//retreive posted file
								$fileName = $_FILES['theFile']['name'][$i];
								$fileTempName = $_FILES['theFile']['tmp_name'][$i];

								//move the file
								if($s3->putObjectFile($fileTempName, "athlos-tools", $folder_str."/".$fileName, S3::ACL_PUBLIC_READ)) {
									$successList[] = $fileName;
								}else{
									$errorList[] = $fileName;
								}
							}
							//-- Success / Error Upload Messages --//
							if(!empty($errorList)){
								$errorStr = '<ul>';
								foreach($errorList as $efile){
									$errorStr.= '<li>'.$efile.'</li>';
								}
								$errorStr.= '</ul>';
								$this->flashSession->error($preMsg."<strong>Error</strong> Something went wrong while uploading your files... The files below failed to upload.<br />".$errorStr);
							}
							if(!empty($successList)){
								$successStr = '<ul>';
								foreach($successList as $efile){
									$successStr.= '<li>'.$efile.'</li>';
								}
								$successStr.= '</ul>';
								$this->flashSession->success($preMsg."<strong>Success</strong> We successfully uploaded your files... The files below were uploaded successfully.<br />".$successStr);
							}
						}
					}else{
						$this->flashSession->error($preMsg."<strong>Error</strong> The folder selected was invalid, please refresh page and try again.");
					}
				}
			}
		}

		//-- Create Basic Folder/Sub-Folder Array --//
		$folders = array();
		$level1_folders = CurriculumFolders::find(array("parent_id = 150"));
		if(!empty($level1_folders)){
			foreach($level1_folders as $l1){
				$folders[$l1->id] = array('name' => $l1->name, 'url' => $l1->url, 'icon' => $l1->icon, 'permissions' => unserialize($l1->permissions));
				$level2 = CurriculumFolders::find(array("parent_id = :pid:", "bind" => array("pid" => $l1->id)));
				if(!empty($level2)){
					//-- Iterate through L2 subfolders --//
					foreach($level2 as $l2){
						$folders[$l1->id][$l2->id] = array('name' => $l2->name, 'url' => $l2->url, 'icon' => $l2->icon, 'permissions' => unserialize($l2->permissions));
						$folders[$l1->id][$l2->id][0] = $s3->getBucket("athlos-tools", "athletic-curriculum/".$l1->url."/".$l2->url."/");
					}
				}
			}
		}

		//-- Grab Campus States --//
		$districts = Districts::find(array("order" => "abbreviation ASC", "columns" => "id, abbreviation"));
		$district_abbr = array();
		foreach($districts as $district){
			$district_abbr[$district->id] = $district->abbreviation;
		}
		$icons = array("fa-adjust", "fa-adn", "fa-align-center", "fa-align-justify", "fa-align-left", "fa-align-right", "fa-ambulance", "fa-anchor", "fa-angle-double-down", "fa-angle-double-left", "fa-angle-double-right", "fa-angle-double-up", "fa-angle-down", "fa-angle-left", "fa-angle-right", "fa-angle-up", "fa-archive", "fa-arrow-circle-down", "fa-arrow-circle-left", "fa-arrow-circle-o-down", "fa-arrow-circle-o-left", "fa-arrow-circle-o-right", "fa-arrow-circle-o-up", "fa-arrow-circle-right", "fa-arrow-circle-up", "fa-arrow-down", "fa-arrow-left", "fa-arrow-right", "fa-arrow-up", "fa-asterisk", "fa-backward", "fa-ban", "fa-bar-chart-o", "fa-barcode", "fa-beer", "fa-bell", "fa-bell-o", "fa-bitbucket", "fa-bold", "fa-bolt", "fa-book", "fa-bookmark", "fa-bookmark-o", "fa-briefcase", "fa-bug", "fa-building", "fa-bullhorn", "fa-bullseye", "fa-calendar", "fa-calendar-o", "fa-camera", "fa-camera-retro", "fa-caret-down", "fa-caret-left", "fa-caret-right", "fa-caret-square-o-down", "fa-caret-square-o-left", "fa-caret-square-o-right", "fa-caret-square-o-up", "fa-caret-up", "fa-certificate", "fa-chain", "fa-chain-broken", "fa-check", "fa-check-circle", "fa-check-circle-o", "fa-check-square", "fa-check-square-o", "fa-chevron-circle-down", "fa-chevron-circle-left", "fa-chevron-circle-right", "fa-chevron-circle-up", "fa-chevron-down", "fa-chevron-left", "fa-chevron-right", "fa-chevron-up", "fa-circle", "fa-circle-o", "fa-clipboard", "fa-clock-o", "fa-cloud", "fa-cloud-download", "fa-cloud-upload", "fa-cny", "fa-code", "fa-code-fork", "fa-coffee", "fa-cog", "fa-cogs", "fa-columns", "fa-comment", "fa-comment-o", "fa-comments", "fa-comments-o", "fa-compass", "fa-copy", "fa-credit-card", "fa-crop", "fa-crosshairs", "fa-cut", "fa-cutlery", "fa-dashboard", "fa-dedent", "fa-desktop", "fa-dollar", "fa-dot-circle-o", "fa-download", "fa-dribbble", "fa-dropbox", "fa-edit", "fa-eject", "fa-envelope", "fa-envelope-o", "fa-eraser", "fa-eur", "fa-euro", "fa-exchange", "fa-exclamation", "fa-exclamation-circle", "fa-exclamation-triangle", "fa-external-link", "fa-external-link-square", "fa-eye", "fa-eye-slash", "fa-facebook", "fa-facebook-square", "fa-fast-backward", "fa-fast-forward", "fa-female", "fa-fighter-jet", "fa-file", "fa-file-o", "fa-file-text", "fa-file-text-o", "fa-files-o", "fa-film", "fa-filter", "fa-fire", "fa-fire-extinguisher", "fa-flag", "fa-flag-checkered", "fa-flag-o", "fa-flash", "fa-flask", "fa-flickr", "fa-floppy-o", "fa-folder", "fa-folder-o", "fa-folder-open", "fa-folder-open-o", "fa-font", "fa-forward", "fa-foursquare", "fa-frown-o", "fa-gamepad", "fa-gavel", "fa-gbp", "fa-gear", "fa-gears", "fa-gift", "fa-gittip", "fa-glass", "fa-globe", "fa-google-plus", "fa-google-plus-square", "fa-group", "fa-h-square", "fa-hand-o-down", "fa-hand-o-left", "fa-hand-o-right", "fa-hand-o-up", "fa-headphones", "fa-heart", "fa-heart-o", "fa-home", "fa-inbox", "fa-indent", "fa-info", "fa-info-circle", "fa-inr", "fa-instagram", "fa-italic", "fa-jpy", "fa-key", "fa-keyboard-o", "fa-krw", "fa-laptop", "fa-leaf", "fa-legal", "fa-lemon-o", "fa-level-down", "fa-level-up", "fa-lightbulb-o", "fa-link", "fa-linux", "fa-list", "fa-list-alt", "fa-list-ol", "fa-list-ul", "fa-location-arrow", "fa-lock", "fa-long-arrow-down", "fa-long-arrow-left", "fa-long-arrow-right", "fa-long-arrow-up", "fa-magic", "fa-magnet", "fa-mail-forward", "fa-mail-reply", "fa-mail-reply-all", "fa-male", "fa-map-marker", "fa-maxcdn", "fa-medkit", "fa-meh-o", "fa-microphone", "fa-microphone-slash", "fa-minus", "fa-minus-circle", "fa-minus-square", "fa-minus-square-o", "fa-mobile", "fa-mobile-phone", "fa-money", "fa-moon-o", "fa-music", "fa-outdent", "fa-pagelines", "fa-paperclip", "fa-paste", "fa-pause", "fa-pencil", "fa-pencil-square", "fa-pencil-square-o", "fa-phone", "fa-phone-square", "fa-picture-o", "fa-pinterest", "fa-pinterest-square", "fa-plane", "fa-play", "fa-play-circle", "fa-play-circle-o", "fa-plus", "fa-plus-circle", "fa-plus-square", "fa-power-off", "fa-print", "fa-puzzle-piece", "fa-qrcode", "fa-question", "fa-question-circle", "fa-quote-left", "fa-quote-right", "fa-random", "fa-refresh", "fa-renren", "fa-reorder", "fa-repeat", "fa-reply", "fa-reply-all", "fa-retweet", "fa-rmb", "fa-road", "fa-rocket", "fa-rotate-left", "fa-rotate-right", "fa-rouble", "fa-rss", "fa-rss-square", "fa-rub", "fa-ruble", "fa-rupee", "fa-save", "fa-scissors", "fa-search", "fa-search-minus", "fa-search-plus", "fa-share", "fa-share-square", "fa-share-square-o", "fa-shield", "fa-shopping-cart", "fa-sign-in", "fa-sign-out", "fa-signal", "fa-sitemap", "fa-smile-o", "fa-sort", "fa-sort-alpha-asc", "fa-sort-alpha-desc", "fa-sort-amount-asc", "fa-sort-amount-desc", "fa-sort-asc", "fa-sort-desc", "fa-sort-down", "fa-sort-numeric-asc", "fa-sort-numeric-desc", "fa-sort-up", "fa-spinner", "fa-square", "fa-square-o", "fa-stack-exchange", "fa-stack-overflow", "fa-star", "fa-star-half", "fa-star-half-empty", "fa-star-half-full", "fa-star-half-o", "fa-star-o", "fa-step-backward", "fa-step-forward", "fa-stethoscope", "fa-stop", "fa-strikethrough", "fa-subscript", "fa-suitcase", "fa-sun-o", "fa-superscript", "fa-table", "fa-tablet", "fa-tachometer", "fa-tag", "fa-tags", "fa-tasks", "fa-terminal", "fa-text-height", "fa-text-width", "fa-th", "fa-th-large", "fa-th-list", "fa-thumb-tack", "fa-thumbs-down", "fa-thumbs-o-down", "fa-thumbs-o-up", "fa-thumbs-up", "fa-ticket", "fa-times", "fa-times-circle", "fa-times-circle-o", "fa-tint", "fa-toggle-down", "fa-toggle-left", "fa-toggle-right", "fa-toggle-up", "fa-trash-o", "fa-trello", "fa-trophy", "fa-truck", "fa-twitter", "fa-twitter-square", "fa-umbrella", "fa-underline", "fa-undo", "fa-unlink", "fa-unlock", "fa-unsorted", "fa-upload", "fa-usd", "fa-user", "fa-user-md", "fa-video-camera", "fa-volume-down", "fa-volume-off", "fa-volume-up", "fa-warning", "fa-wheelchair", "fa-wrench");
		$this->view->setVar("icons", $icons);
		$this->view->setVar("districts", $districts);
		$this->view->setVar("district_abbr", $district_abbr);
		$this->view->setVar("folders", $folders);
		unset($folders);

	} //-- end manageAction() --//

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
				if($this->cap['athletic-curriculum']['manage']){

					//-- Set empty parent to be child of "Athletic Curriculum" Folder --//
					if($parent == 0){ $parent = 150; }

					//-- Make sure the required info is present --//
					if($name && $url && $parent && $icon && !empty($permissions)){
						//-- serialize permissions --//
						$permissions = serialize($permissions);

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
									/*if(!empty($the_folder) && $the_folder->parent_id != 0){
										$results["parents"][] = $the_folder->parent_id;
										while($the_folder->parent_id != 150 && $the_folder->parent_id != 0){
											$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $the_folder->parent_id), "columns" => "parent_id"));
											if(!empty($the_folder)){
												$results["parents"][] = $the_folder->parent_id;
											}
										}
									}else{
										$results["noparent"] = 150;
									}*/
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
				if($this->cap['athletic-curriculum']['manage']){

					//-- Set empty parent to be child of "Athletic Curriculum" Folder --//
					if($parent == 0){ $parent = 150; }

					//-- Make sure the required info is present --//
					if($folderID && $name && $parent && $icon && !empty($permissions)){
						//-- serialize permissions --//
						$permissions = serialize($permissions);

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
								/*if(!empty($the_folder) && $the_folder->parent_id != 0){
									$results["parents"][] = $the_folder->parent_id;
									while($the_folder->parent_id != 150 || $the_folder->parent_id != 0){
										$the_folder = CurriculumFolders::findFirst(array("id = :folder:", "bind" => array('folder' => $the_folder->parent_id), "columns" => "parent_id"));
										if(!empty($the_folder)){
											$results["parents"][] = $the_folder->parent_id;
										}
									}
								}else{
									$results["noparent"] = 150;
								}*/
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
				if($this->cap['athletic-curriculum']['manage']){

					if($folderID && is_numeric($folderID)){

						//-- Grab Folder --//
						$folder = CurriculumFolders::findFirst(array(
							"conditions" => "id = :folderID:",
							"bind" => array('folderID' => $folderID)
						));

						if(!empty($folder)){
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


							if($folder->parent_id == 150){
								//-- Retreive & Delete Child Folders & their Contents--//
								$additional_folders = CurriculumFolders::find(array(
									"conditions" => "parent_id = :folderID:",
									"bind" => array('folderID' => $folderID)
								));
								if(!empty($additional_folders)){
									foreach($additional_folders as $aFolder){
										$files = $s3->getBucket("athlos-tools", "athletic-curriculum/".$folder->url."/".$aFolder->url."/");
										if(count($files)){
											foreach($files as $file){
												if(!empty($file['name'])){
													$s3->deleteObject("athlos-tools", $file['name']);
												}
											}
										}
										//-- Delete Child Folders --//
										$aFolder->delete();
									}
								}
							}else{
								//-- Retrieve Parent URL -- Delete Current folder contents --//
								$parent_folder = CurriculumFolders::findFirst(array(
									"conditions" => "id = :folderID:",
									"bind" => array('folderID' => $folder->parent_id)
								));
								if(!empty($parent_folder)){
									$files = $s3->getBucket("athlos-tools", "athletic-curriculum/".$parent_folder->url."/".$folder->url."/");
									if(count($files)){
										foreach($files as $file){
											if(!empty($file['name'])){
												$s3->deleteObject("athlos-tools", $file['name']);
											}
										}
									}
								}
							}


							//-- Delete Folder from DB --//
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
				if($this->cap['athletic-curriculum']['manage']){

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


	public function suggestionsAction()
    {
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";

		/*------------------------------------
			Validate Suggestion Submission
		-------------------------------------*/
		//-- Check if request has made with POST --//
		if($this->request->isPost() == true && $this->request->getPost("form-action") == 'submit-suggestion'){

			//-- Initialize Error Boolean --//
			$errFound = true;

			//-- Sanitize Inputs --//
			$title = htmlentities($this->request->getPost("post-subject"), ENT_QUOTES);
			$content = htmlentities($this->request->getPost("post_content"), ENT_QUOTES);
			$author_email = $this->request->getPost("author_email", "email");
			$level = $this->request->getPost("athletic-level", "int");
			$topic = $this->request->getPost("lesson-topic", "string");

			//-- Make sure all required fields are present --//
			if($title && $content && $author_email && $level && $topic){

				//-- Send "Suggestion" email notification to Chandler --//
				$to = 'cherdt@athlosacademies.org';
				$subject = "Athletic Curriculum Suggestion";
				$message = "A new suggestion for the athletic curriculum has been submitted\n\nThe submitted contents are as follows:\n\nAthletic Level: ".$level."\nLesson Topic: ".$topic."\n\nTitle / Subject: ".$title."\n\nSuggestion Content:\n".$content."\n\n\nFrom: ".$author_email;

				//-- Setup Mailgun Object --//
				$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

				//-- Send MSG with mailgun --//
				$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
				                  array('from'    => "Athlos Tools <".$author_email.">",
				                        'to'      => $to,
				                        'subject' => $subject,
				                        'text'    => $message));
				//-- Success / Error Messages --//
				if($result){
					//-- Set Error Boolean to False --//
					$errFound = false;
					$this->flashSession->success($preMsg."<strong>Suggestion Sent Successfully!</strong> Your suggestion was sent successfully. Thank you.");
				}else{
					$this->flashSession->error($preMsg."<strong>Failed to Send!</strong> The suggestion was not sent. Please Try Again.");
				}

			}else{
				$this->flashSession->error($preMsg."<strong>Missing Required Fields</strong> Make sure the post has a title, content, Level, and Lesson Topic.");
			}

			//-- Pass Error Boolean to View --//
			$this->view->setVar("errFound", $errFound);

		} //-- end if there is POST data -- end validation --//
		/*--------------------
			end Validation
		---------------------*/

		//-- Set lesson topics --//
		$lv1_topics = array('Body Awareness', 'Body Control', 'Interpretation', 'Traveling');
		$lv2_topics = array('Throwing and Catching', 'Chasing and Dodging', 'Kicking and Striking', 'Dribbling');
		$lv3_topics = array('Basketball', 'Football', 'Handball', 'Hockey', 'Lacrosse', 'Racquet Sports', 'Soccer', 'Ultimate Frisbee', 'Volleyball');
		$lv4_topics = array('Acceleration', 'Multi-Direction', 'Maximum Velocity', 'Acceleration Sport Integration', 'Multi-Direction Sport Integration', 'Maximum Velocity Sport Integration');
		//-- Pass arrays to view --//
		$this->view->setVar("lv1_topics", $lv1_topics);
		$this->view->setVar("lv2_topics", $lv2_topics);
		$this->view->setVar("lv3_topics", $lv3_topics);
		$this->view->setVar("lv4_topics", $lv4_topics);

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
			$to = 'cherdt@athlosacademies.org';
			$subject = "Report Bug: ".$subject;
			$message = "A new bug has been reported in the athletic curriculum.\nIncluded below are the details to the issue as reported by the user. Please review their response as well as take some time to look into the bug being reported.\n\nIssue Details:\n".$issue."\n\nThanks again,\n\n\t- Athlos Tools";

			//-- Setup Mailgun Object --//
			$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');

			//-- Send MSG with mailgun --//
			$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
			                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
			                        'to'      => $to,
			                        'subject' => $subject,
			                        'text'    => $message));
			//-- Success / Error Messages --//
			if($result){
				$this->flashSession->success($preMsg."<strong>Bug reported!</strong> Your report was sent successfully. Thank you.");
			}else{
				$this->flashSession->error($preMsg."<strong>Bug report failed!</strong> The issue was not sent. Please Try Again.");
			}

			return $this->response->redirect("athletic/suggestions");
		}
	} //-- end reportAction() --//


	public function gradingAction()
    {
		//-- Permissions --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		if(!$this->cap['assessments']['view']){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}

		//-- Variables were posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars / sanitize vars --//
			$field = $this->request->getPost("field", "string");
			$lastField = $this->request->getPost("lastField", "string");
			$dir = $this->request->getPost("dir", "string");
			$schoolID = $this->request->getPost("filterSchool", "int");
			$searchTerm = $this->request->getPost("filterSearch", "string");
			$periodID = $this->request->getPost("class-period", "int");
			$intervalID = $this->request->getPost("grading-interval", "int");
			$assessmentID = $this->request->getPost("filterAssessments", "int");
			$gradeID = $this->request->getPost("filterGrade", "int");
			$teacherID = $this->request->getPost("filterTeacher", "int");
			$coachID = $this->request->getPost("filterCoach", "int");

			//-- Finalize Assessment URL --//
			if(isset($assessmentID) && $assessmentID){
				$curAssessment = AthleticAssessments::findFirst($assessmentID);
				if(!empty($curAssessment)){
					$assessmentName = $curAssessment->assessment_name;
					$assessmentUrl = $curAssessment->url_name;
					$assessmentData = $curAssessment->data;
					$assessmentLabel = $curAssessment->data_label;
				}else{
					$assessmentName = '20 Yard Sprint';
					$assessmentUrl = 'sprint';
					$assessmentData = 'seconds';
					$assessmentLabel = 'Seconds';
				}
			}

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

		if(!isset($field) || !$field){ $field = 'firstname'; }
		if(!isset($dir) || !$dir){ $dir = 'ASC'; }

		/*-----------------------
			Figure out Filters
		------------------------*/
		//-- Force school filter for non admin users --//
		if($this->session->get("user-school")){
			$schoolID = $this->session->get("user-school");
			$schoolFilter = '';
		}else{
			if(isset($schoolID) && $schoolID){ $schoolFilter = '<input type="hidden" name="filterSchool" value="'.$schoolID.'" />'; }else{ $schoolFilter = ''; }
		}
		if(isset($gradeID) && $gradeID != ''){ $gradeFilter = '<input type="hidden" name="filterGrade" value="'.$gradeID.'" />'; }else{ $gradeFilter = ''; }
		if(isset($teacherID) && $teacherID){ $teacherFilter = '<input type="hidden" name="filterTeacher" value="'.$teacherID.'" />'; }else{ $teacherFilter = ''; }
		if(isset($coachID) && $coachID){ $coachFilter = '<input type="hidden" name="filterCoach" value="'.$coachID.'" />'; }else{ $coachFilter = ''; }
		//-- Interval / Period Filter --//
		if(isset($intervalID) && $intervalID){ $intervalFilter = '<input type="hidden" name="grading-interval" value="'.$intervalID.'" />'; }else{ $intervalFilter = ''; }
		if(isset($periodID) && $periodID != ''){ $periodFilter = '<input type="hidden" name="class-period" value="'.$periodID.'" />'; }else{ $periodFilter = ''; }

		//-- Search Filter --//
		if(isset($searchTerm) && $searchTerm != ''){ $searchFilter = '<input type="hidden" name="filterSearch" class="filterSearch" value="'.$searchTerm.'" />'; }else{ $searchFilter = '<input type="hidden" name="filterSearch" class="filterSearch" value="" />'; }

		//-- Assessment Filter --//
		if(isset($assessmentID) && $assessmentID){ $assessmentFilter = '<input type="hidden" name="filterAssessments" class="filterAssessments" value="'.$assessmentID.'" />'; }else{ $assessmentFilter = ''; }


		$filters = $schoolFilter.$searchFilter.$periodFilter.$intervalFilter.$assessmentFilter.$gradeFilter.$teacherFilter.$coachFilter;

		//-- Add Filter inputs --//
		$lastField.= $filters;

		//-- Map to real column names --//
		if($field == 'firstname'){ $column = 'fname'; }
		else if($field == 'lastname'){ $column = 'lname'; }
		else if($field == 'teacher'){ $column = 'teacher'; }
		else if($field == 'grade_level'){ $column = 'grade'; }
		else if($field == 'data'){ $column = $assessmentUrl; }
		else if($field == 'data-height'){ $column = 'height'; }
		else if($field == 'data-weight'){ $column = 'weight'; }
		else if($field == 'data-reach'){ $column = 'absolute_reach'; }
		else if($field == 'data-limb'){ $column = 'limb_length'; }
		else if($field == 'data-sl-left'){ $column = 'sl_left'; }
		else if($field == 'data-sl-right'){ $column = 'sl_right'; }
		else{ $column = 'fname'; }

		if(isset($field) && ($field == 'data' || $field == 'data-height' || $field == 'data-weight' || $field == 'data-reach' || $field == 'data-limb' || $field == 'data-sl-left' || $field == 'data-sl-right')){ $s = 's.'; }else{ $s = ''; } //-- makes conditions work on multi table query --//
		//-- Figure out filter conditions --//
		$conditions = $s."active = 1";
		if(isset($schoolID) && $schoolID){
			if($conditions == ''){
				$conditions.= $s."school = ".$schoolID;
			}else{
				$conditions.= " AND ".$s."school = ".$schoolID;
			}
		}
			//-- Turf Class Period --//
		if(isset($periodID) && $periodID != ''){
			if($conditions == ''){
				$conditions.= $s."turf_period = ".$periodID;
			}else{
				$conditions.= " AND ".$s."turf_period = ".$periodID;
			}
		}

		//-- Separate conditions --//
		$conditions2 = '';
		if(isset($gradeID) && $gradeID != ''){
			if($conditions2 == ''){
				$conditions2.= $s."grade = ".$gradeID;
			}else{
				$conditions2.= " AND ".$s."grade = ".$gradeID;
			}
		}
		if(isset($teacherID) && $teacherID){
			if($conditions2 == ''){
				$conditions2.= $s."teacher = ".$teacherID;
			}else{
				$conditions2.= " AND ".$s."teacher = ".$teacherID;
			}
		}
			//-- Coach --//
		if(isset($coachID) && $coachID){
			if($conditions2 == ''){
				$conditions2.= $s."coach = ".$coachID;
			}else{
				$conditions2.= " AND ".$s."coach = ".$coachID;
			}
		}
		//-- Consolidate the 2 condition strings --//
		if($conditions2 != ''){
			$totalConditions = $conditions." AND ".$conditions2;
		}else{
			$totalConditions = $conditions;
		}

		//-- Add Search Term to conditions --//
		/*if(isset($searchTerm) && $searchTerm != ''){
			if($conditions == ''){
				$conditions.= "(fname = '".$searchTerm."' OR fname LIKE '".$searchTerm."%' OR lname = '".$searchTerm."' OR lname LIKE '".$searchTerm."%')";
			}else{
				$conditions.= " AND (fname = '".$searchTerm."' OR fname LIKE '".$searchTerm."%' OR lname = '".$searchTerm."' OR lname LIKE '".$searchTerm."%')";
			}
		}*/

		//-- setup empty coach list array --//
		$coachList = array();

		//-- Be able to sort table by the data value --//
		if(isset($field) && ($field == 'data' || $field == 'data-height' || $field == 'data-weight' || $field == 'data-reach' || $field == 'data-limb' || $field == 'data-sl-left' || $field == 'data-sl-right')){
			//-- Grab students with current data set --//
			$query = "SELECT s.id, s.grade, s.fname, s.lname, IF(a.".$column." IS NULL, 0, a.".$column.") AS data FROM students AS s, athletic_grading AS a WHERE a.student = s.id AND a.semester = ".$this->session->get("current-semester")." AND a.interval = ".$intervalID." AND ".$totalConditions." ORDER BY data ".$dir.", s.fname ASC, s.lname ASC";
			$response = $this->db->query($query, array());
			$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
			$students = $response->fetchAll();

			//-- make list of students i already have --//
			$studentList = '';
			if(!empty($students)){
				foreach($students as $student){
					if($studentList == ''){ $studentList.= $student->id; }else{ $studentList.= ','.$student->id; }
				}
			}
			if($studentList != ''){ $totalConditions.= " AND s.id NOT IN (".$studentList.")"; }

			//-- Grab Students with no data --//
			$query2 = "SELECT s.id, s.grade, s.fname, s.lname FROM students AS s WHERE ".$totalConditions." ORDER BY s.fname ASC, s.lname ASC";
			$response = $this->db->query($query2, array());
			$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
			$students2 = $response->fetchAll();

			//-- Determine Order of Merging arrays --//
			if($dir == 'DESC'){
				$students = array_merge($students, $students2);
			}else{
				$students = array_merge($students2, $students);
			}

			//-- Grab coach list --//
			$query3 = "SELECT s.coach FROM students AS s, users AS u WHERE ".$conditions." GROUP BY s.coach";
			$response = $this->db->query($query3, array());
			$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
			$coach_students = $response->fetchAll();

		}else{
			$students = Students::find(array($totalConditions, "order" => $column." ".$dir));
			//$totalStudents = Students::count(array($totalConditions, "order" => $column." ".$dir));

			//-- Grab Coach List --//
			$coach_students = Students::find(array($conditions, "columns" => 'coach', "group" => 'coach'));
		}

		//-- Compile Coach Query --//
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

		//-- Grab Schools --//
		if($this->session->get("user-district")){
			$schools = Schools::find(array("district = :dist:", "order" => "state ASC, schoolName ASC, city ASC", "bind" => array("dist" => $this->session->get("user-district"))));
		}else{
			$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
		}
		//-- Grab Grade Levels --//
		$grade_level = GradeLevel::find(array("order" => "id ASC"));
		//-- Grab Intervals Object --//
		//$intervals = AthleticIntervals::find(array("order" => "id ASC"));
		//-- Grab Assessments --//
		$assessments = AthleticAssessments::find(array("active = 1", "order" => "assessment_name ASC"));

		//-- Grab Teachers / filtered by school & Grade if those filters are set --//
		$teachConditions = "role = 8";
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
		if(isset($schoolID) && $schoolID){
			$teacherList = Users::find(array("role = 8 AND school = ".$schoolID, "order" => "lname ASC"));
		}else{
			$teacherList = array();
		}

		//-- Create Teacher Reference Array - from teacherList --//
		if(isset($teacherList) && $teacherList){
			$teacherRef = array();
			foreach($teacherList as $tl){
				$teacherRef[$tl->id] = $tl->lname.', '.$tl->fname;
			}
		}

		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Assessments");

		//-- Pass Objects / Vars to View --//
		//$this->view->setVar("intervals", $intervals);
		$this->view->setVar("cap", $this->cap);
		$this->view->setVar("assessments", $assessments);
        $this->view->setVar("students", $students);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("grade_level", $grade_level);
		$this->view->setVar("lastField", $lastField);
		$this->view->setVar("inputs", $inputs);
		$this->view->setVar("filters", $filters);
		$this->view->setVar("field", $field);
		$this->view->setVar("dir", strtolower($dir));
		//$this->view->setVar("totalStudents", $totalStudents);
		$this->view->setVar("schoolFilter", $schoolFilter);
		if(isset($schoolID) && $schoolID){ $this->view->setVar("schoolID", $schoolID); }
		$this->view->setVar("gradeFilter", $gradeFilter);
		if(isset($gradeID) && $gradeID != ''){ $this->view->setVar("gradeID", $gradeID); }
		$this->view->setVar("assessmentFilter", $assessmentFilter);
		if(isset($assessmentUrl) && $assessmentUrl){ $this->view->setVar("assessmentUrl", $assessmentUrl); }
		if(isset($assessmentName) && $assessmentName){ $this->view->setVar("assessmentName", $assessmentName); }
		if(isset($assessmentData) && $assessmentData){ $this->view->setVar("assessmentData", $assessmentData); }
		if(isset($assessmentLabel) && $assessmentLabel){ $this->view->setVar("assessmentLabel", $assessmentLabel); }
		$this->view->setVar("periodFilter", $periodFilter);
		if(isset($periodID) && $periodID != ''){ $this->view->setVar("periodID", $periodID); }
		$this->view->setVar("intervalFilter", $intervalFilter);
		if(isset($intervalID) && $intervalID){ $this->view->setVar("intervalID", $intervalID); }
		if(isset($searchTerm) && $searchTerm != ''){ $this->view->setVar("searchTerm", $searchTerm); }
		$this->view->setVar("searchFilter", $searchFilter);
		$this->view->setVar("teacherList", $teacherFilterList);
		if(isset($teacherRef) && $teacherRef){ $this->view->setVar("teacherRef", $teacherRef); }
		$this->view->setVar("teacherFilter", $teacherFilter);
		if(isset($teacherID) && $teacherID){ $this->view->setVar("teacherID", $teacherID); }
		$coachList = array();
		if(isset($csList)){ $this->view->setVar("csList", $csList); }
		$this->view->setVar("coachList", $coachList);
		$this->view->setVar("coachFilter", $coachFilter);
		if(isset($coachID) && $coachID){ $this->view->setVar("coachID", $coachID); }

	} //-- end gradingAction() --//


	public function checkgradeAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Double Check Grade --//
			if($this->request->getPost("action") == 'check_grade'){

				//-- grab / set vars --//
				$gradeID = $this->request->getPost("theID", "int");
				$studentID = $this->request->getPost("studentID", "int");
				$interval = $this->request->getPost("interval", "int");
				$test = trim($this->request->getPost("test", "string"));
				$results = array();

				//-- Ignore check if gradeID exists --//
				if(!$gradeID){
					//-- Verify that passed data has not been tampered with --//
					$testArray = array('sprint','hex','vjump','sjump','height','weight','pacer','shuttle','pushup','curlup','trunklift','sitreach','plank','absolute_reach','limb_length','balance','sl_left','sl_right','slraise');
					$intervalArray = array(1,2,3,4);
					if(!empty($test) && in_array($test, $testArray) && !empty($interval) && in_array($interval, $intervalArray) && $studentID && is_numeric($studentID)){

						$conditions = "semester = :sem: AND student = :studentID: AND interval = :intID: AND ".$test." != 0";
						$bindArray = array('sem' => $this->session->get("current-semester"), 'studentID' => $studentID, 'intID' => $interval);
						$theGrade = AthleticGrading::findFirst(array($conditions, "bind" => $bindArray));
						if(!empty($theGrade)){
							$results["result"] = "failed";
							$theUser = Users::findFirst(array("id = :evalID:", "bind" => array('evalID' => $theGrade->evaluator)));
							if(!empty($theUser)){
								$results["evaluator"] = $theUser->fname.' '.$theUser->lname;
							}
						}else{
							$results["result"] = "success";
						}
					}else{
						$results["result"] = "failed";
						$results["errorMsg"] = 'Data appears to be Invalid!';
					}
				}else{
					$results["result"] = "success";
				}

				//-- encode results --//
				echo json_encode($results);

			} //-- end Check Grade --//
		}

		//-- Disable View --//
		$this->view->disable();

	} //-- end checkgradeAction() --//

	public function submitgradeAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Submit Grade --//
			if($this->request->getPost("action") == 'submit_grade'){

				//-- grab / set vars --//
				$gradeID = $this->request->getPost("theID", "int");
				$studentID = $this->request->getPost("studentID", "int");
				$dataVal = $this->request->getPost("dataVal", "float");
				$interval = $this->request->getPost("interval", "int");
				$test = trim($this->request->getPost("test", "string"));
				$format = trim($this->request->getPost("format", "string"));
				$results = array();

				//-- Verify that passed data has not been tampered with --//
				$testArray = array('sprint','hex','vjump','sjump','height','weight','pacer','shuttle','pushup','curlup','trunklift','sitreach','plank','absolute_reach','limb_length','balance','sl_left','sl_right','slraise');
				$intervalArray = array(1,2,3,4);
				$formatArray = array('seconds','inches','whole','pounds','centimeters');
				if(!empty($test) && in_array($test, $testArray) && !empty($interval) && in_array($interval, $intervalArray) && !empty($format) && in_array($format, $formatArray) && $studentID && is_numeric($studentID)){
					//-- Permissions --//
					if($this->cap['assessments']['enter']){
						//-- grab more student info --//
						$theStudent = Students::findFirst(array("id = :sid:", "bind" => array('sid' => $studentID)));
						if(!empty($theStudent)){
							$school = $theStudent->school;
							$level = $theStudent->grade;

							//-- validate data entry --//
							if(($format == 'whole' && strpos($dataVal, '.') === false) || (($format == 'inches' || $format == 'centimeters' || $format == 'seconds') && strlen(substr($dataVal, strpos($dataVal, '.')+1)) >= 1) || ($format == 'pounds' && strlen(substr($dataVal, strpos($dataVal, '.')+1)) == 1)){

								//-- Grab Athletic Grade Object --//
								if($gradeID){
									$conditions = "id = :id:";
									$bindArray = array('id' => $gradeID);
								}else{
									$conditions = "semester = :sem: AND student = :studentID: AND interval = :intID:";
									$bindArray = array('sem' => $this->session->get("current-semester"), 'studentID' => $studentID, 'intID' => $interval);
								}

								$theGrade = AthleticGrading::findFirst(array($conditions, "bind" => $bindArray));
								//-- Create new object if grade doesn't already exist --//
								if(!$theGrade){
									$theGrade = new AthleticGrading();
									//-- set values --//
									$theGrade->semester = $this->session->get("current-semester");
									$theGrade->interval = $interval;
									$theGrade->school = $school;
									$theGrade->grade_level = $level;
									$theGrade->student = $studentID;
									//-- Set default assessment values --//
									$theGrade->sprint = 0.00;
									$theGrade->hex = 0.00;
									$theGrade->vjump = 0.00;
									$theGrade->sjump = 0.00;
									$theGrade->height = 0.00;
									$theGrade->weight = 0.0;
									$theGrade->bmi = 0.00;
									$theGrade->pacer = 0;
									$theGrade->shuttle = 0;
									$theGrade->pushup = 0;
									$theGrade->curlup = 0;
									$theGrade->trunklift = 0.00;
									$theGrade->sitreach = 0.00;
									$theGrade->plank = 0.00;
									$theGrade->absolute_reach = 0.00;
									$theGrade->limb_length = 0.00;
									$theGrade->balance = 0.00;
									$theGrade->sl_left = 0;
									$theGrade->sl_right = 0;
									$theGrade->slraise = 0;
								}

								//-- Set New Changed Data --//
								$theGrade->evaluator = $this->session->get("user-id");
								$theGrade->{$test} = $dataVal;

								//-- Detect if BMI Change is Applicable && Calculate --//
								if($test == 'height' || $test == 'weight'){
									if(!empty($theGrade->height) && !empty($theGrade->weight)){
										$theGrade->bmi = round(($theGrade->weight / ($theGrade->height * $theGrade->height)) * 703, 2);
									}
								}

								//-- Detect if Balance Change is Applicable && Calculate --//
								if($test == 'absolute_reach' || $test == 'limb_length'){
									if(!empty($theGrade->absolute_reach) && !empty($theGrade->limb_length)){
										$theGrade->balance = round(($theGrade->absolute_reach / $theGrade->limb_length ) * 100, 2);
									}
								}

								//-- Detect if SL_Left Changed and Apply Average --//
								if($test == 'sl_left' || $test == 'sl_right'){
									if(!empty($theGrade->sl_right) && !empty($theGrade->sl_left)){
										$theGrade->slraise = round(($theGrade->sl_left + $theGrade->sl_right) / 2, 0);
									}else if(!empty($theGrade->sl_right)){
										$theGrade->slraise = $theGrade->sl_right;
									}else if(!empty($theGrade->sl_left)){
										$theGrade->slraise = $theGrade->sl_left;
									}
								}

								//-- Save Entry --//
								if($theGrade->save() == false){
									$results["result"] = "failed";
									$results["entry"] = $theGrade;
									foreach($theGrade->getMessages() as $message){
										$results['msg'][] = $message->getMessage();
									}
								}else{
									$results["result"] = "success";
									$results["entryID"] = $theGrade->id;
									if(!empty($theGrade->bmi)){ $results["entryBMI"] = $theGrade->bmi; }
									if(!empty($theGrade->balance)){ $results["entryBalance"] = $theGrade->balance; }
									if(!empty($theGrade->slraise)){
										$results["entrySLLeft"] = $theGrade->sl_left;
										$results["entrySLRight"] = $theGrade->sl_right;
										$results["entrySLRaise"] = $theGrade->slraise;
									}
								}
							}else{
								$results["result"] = "failed";
								$results["errorMsg"] = 'Invalid Entry';
							}
						}else{
							$results["result"] = "failed";
							$results["errorMsg"] = 'Invalid Student ID';
						}
					}else{
						//-- Not Enough Permissions --//
						$results['result'] = "failed";
						$results["errorMsg"] = "Failure - No Permissions";
					}
				}else{
					$results["result"] = "failed";
					$results["errorMsg"] = 'Data appears to be Invalid!';
				}

				//-- encode results --//
				echo json_encode($results);

			} //-- end Submit Grade --//
		}

		//-- Disable View --//
		$this->view->disable();

	} //-- end submitgradeAction() --//


	public function scorecardAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Generate Report Cards --//
			if($this->request->getPost("action") == 'generate-scorecard'){

				//-- grab / set vars --//
				$cards = $this->request->getPost("cards", "string");

				//-- turn into array --//
				$students = explode(',', $cards);

				//-- Include mPDF Library --//
				require "../app/controllers/mpdf/vendor/autoload.php";
				//$mpdf = new Mpdf('','Letter', 0, 'helvetica');
				$mpdf = new \Mpdf\Mpdf([
					'mode' => '',
					'format' => 'Letter',
					'default_font_size' => 0,
					'default_font' => 'Helvetica',
				]);

				if(!empty($students)){
					//-- Set Footer --//
					//$mpdf->SetHTMLFooter('<div id="footer"><img src="img/Athletic-Scorecard-Footer.png" width="100%" /></div>');

					//-- Grab PDF Content from db --//
					$pdfContent = Options::findFirst("option = 'scorecard-content'");

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
							$schoolInfo = Schools::findFirst(array("id = :schoolid:", "bind" => array("schoolid" => $sInfo->school)));

							//-- School Logo --//
							if($schoolInfo->ilt_school){ $Logo = 'img/logos/ilt-logo.png'; }else{ $Logo = 'img/logos/athlos_logo_mark.png'; }

							//-- Student Assessment Information --//
							$growth = $grade1 = $grade2 = array('sprint' => 0.00, 'hex' => 0.00, 'vjump' => 0.00, 'sjump' => 0.00, 'bmi' => 0.00, 'pacer' => 0, 'shuttle' => 0, 'pushup' => 0, 'curlup' => 0, 'trunklift' => 0, 'sitreach' => 0, 'plank' => 0.00, 'balance' => 0.00, 'slraise' => 0);
							$conditions = "student = :sid: AND semester = :sem:";
							$bind = array("sid" => $studentID, "sem" => $this->session->get("current-semester"));
							$aGrade = AthleticGrading::findFirst(array($conditions." AND interval = 1", "bind" => $bind));
							$aGrade2 = AthleticGrading::findFirst(array($conditions." AND interval = 2", "bind" => $bind));
							foreach($growth as $key => $val){
								$testArray = array('sprint','hex','vjump','sjump','height','weight','pacer','shuttle','pushup','curlup','trunklift','sitreach','plank','balance','slraise');
								if(in_array($key, $testArray)){
									if(!empty($aGrade)){ $grade1[$key] = $aGrade->{$key}; }
									if(!empty($aGrade2)){ $grade2[$key] = $aGrade2->{$key}; }
									//-- Get Growth --//
									$growth[$key] = ($grade2[$key] - $grade1[$key]);
								}
							}

							/*-------------------------------------
								Replace Vars with Values in PDF
							--------------------------------------*/
							$search = array('[*SCHOOL-LOGO*]', '[*STUDENT-NAME*]', '[*STUDENT-GRADE-LEVEL*]', '[*PUSHUP*]', '[*PUSHUP-2*]', '[*PUSHUP-GROWTH*]', '[*PACER*]', '[*PACER-2*]', '[*PACER-GROWTH*]', '[*VJUMP*]', '[*VJUMP-2*]', '[*VJUMP-GROWTH*]', '[*HEX*]', '[*HEX-2*]', '[*HEX-GROWTH*]', '[*SPRINT*]', '[*SPRINT-2*]', '[*SPRINT-GROWTH*]', '[*PLANK*]', '[*PLANK-2*]', '[*PLANK-GROWTH*]', '[*BALANCE*]', '[*BALANCE-2*]', '[*BALANCE-GROWTH*]', '[*SLRAISE*]', '[*SLRAISE-2*]', '[*SLRAISE-GROWTH*]');
							$replace = array($Logo, $sInfo->fname.' '.$sInfo->lname, $sInfo->grade, $grade1['pushup'], $grade2['pushup'], $growth['pushup'], $grade1['pacer'], $grade2['pacer'], $growth['pacer'], $grade1['vjump'], $grade2['vjump'], $growth['vjump'], $grade1['hex'], $grade2['hex'], $growth['hex'], $grade1['sprint'], $grade2['sprint'], $growth['sprint'], $grade1['plank'], $grade2['plank'], $growth['plank'], $grade1['balance'], $grade2['balance'], $growth['balance'], $grade1['slraise'], $grade2['slraise'], $growth['slraise']);
							$output = str_ireplace($search, $replace, $output);

							//-- Write Student's Report Card --//
							$mpdf->WriteHTML($output);

						}
					}

					//-- Output PDF Data --//
					$mpdf->Output();
					exit;
				}
			}
		}else{
			//-- Nothing posted --//
			return $this->response->redirect("athletic/grading");
		}

		//-- Disable View --//
		$this->view->disable();

	} //-- end scorecardAction() --//

	/**
	 * For Data Reports Showing Athletic Improvments & Exporting Athletic Data
	 */
	public function reportsAction()
	{
		//-- Permissions --//
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		if(!$this->cap['dashboard']['reports']){
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}


		//-- Grab Schools --//
		switch($this->session->get("user-role")){
			case 1:
			case 2:
				//	Athlos/Super Admin
				$schools = Schools::find(array("order" => "state ASC, schoolName ASC, city ASC"));
				break;
			case 3:
				//	District Admin
				$schools = Schools::find(array("district = :dist:", "order" => "state ASC, schoolName ASC, city ASC", "bind" => array("dist" => $this->session->get("user-district"))));
				break;
			default:
				$schools = Schools::find(array("id = :schoolID:", "order" => "state ASC, schoolName ASC, city ASC", "bind" => array("schoolID" => $this->session->get("user-school"))));		
		}

		//-- Grab Available School Years --//
		$school_years = Semesters::find(array("", "order" => "id DESC"));
		//-- Grab Grade Levels --//
		$grade_level = GradeLevel::find(array("order" => "id ASC"));
		//-- Grab Intervals Object --//
		$intervals = AthleticIntervals::find(array("order" => "id ASC"));

		//-- Pass Objects / Vars to View --//
		$this->view->setVar("intervals", $intervals);
		$this->view->setVar("schools", $schools);
		$this->view->setVar("school_years", $school_years);
		$this->view->setVar("grade_level", $grade_level);
	} //-- end reportsAction() --//

	public function growthReportAction(){

		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Report of all students with given metrics --//
			if($this->request->getPost("action") == 'get_report'){
				//-- Sanitize Vars --//
				$school = $this->request->getPost("school", "int");
				$grade = $this->request->getPost("grade", "int");
				$growth = $this->request->getPost("growth", "int");
				$test_url = $this->request->getPost("test", "string");
				$measure = $this->request->getPost("measure", "string");
				$columns = array('bmi', 'sprint', 'hex', 'vjump', 'sjump', 'shuttle', 'pacer', 'pushup', 'curlup', 'trunklift', 'sitreach', 'plank', 'balance', 'slraise');
				$results = array();
				$results['report'] = array('grade' => $grade, 'growth' => $growth, 'test' => $test_url, 'school' => $school);

				//-- Figure Out Semester Vars --//
				$semesters = Semesters::find(array("", "order" => "id DESC"));
				if($growth == 2){
					$sem2 = $this->session->get('current-semester');
					$tempSem = 0;
					foreach($semesters as $sem){
						if($tempSem != 0){
							$sem1 = $sem->id;
							break;
						}
						if($sem->active){
							$tempSem = $sem->id;
						}
					}
				}else if($growth == 3){
					$sem3 = $this->session->get('current-semester');
					$tempSem = $tempSem2 = 0;
					foreach($semesters as $sem){
						if($tempSem2 != 0){
							$sem1 = $sem->id;
							break;
						}
						if($tempSem != 0){
							$sem2 = $sem->id;
							$tempSem2 = $sem->id;
						}
						if($sem->active){
							$tempSem = $sem->id;
						}
					}
				}

				//$results['sems'] = array($sem1, $sem2, $sem3);
				//-- Make sure vital data is not empty --//
				if(!empty($test_url) && !empty($growth) && isset($sem1) && $sem1 && !empty($measure)){
					//-- Make sure SQL Metric not tampered with --//
					if(in_array($test_url, $columns)){
						$results['result'] = "success";
						$results['type'] = array('plus' => 'Positive Growth', 'minus' => 'Negative Growth');

						if($school != ''){ //-- Only One School --//

							$results['school']['count'] = $results['school']['progress'] = 0;
							$results['school']['plus']['count'] = $results['school']['plus']['progress'] = 0;
							$results['school']['minus']['count'] = $results['school']['minus']['progress'] = 0;

							//-- Grab School Name --//
							$schoolData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $school)));
							if(!empty($schoolData)){
								$results['school']['name'] = $schoolData->schoolName;
							}

							//-- Build / Run Queries --//
							//-- Grab Student List - for current semester grades --//
							$query = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$this->session->get('current-semester')." AND a.school = ".$school." AND".($grade != '' ? " a.grade_level = ".$grade." AND" : "" )." a.".$test_url." > 0 ORDER BY s.school, s.grade, s.lname ASC, s.fname ASC";
							//$results['query'] = $query;
							$response = $this->db->query($query, array());
							$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
							$grade_response1 = $response->fetchAll();
							$grade_set1 = array();
							if(!empty($grade_response1)){
								foreach($grade_response1 as $resp){
									if(!in_array($resp->id, $grade_set1)){
										$grade_set1[] = $resp->id;
									}
								}
							}

							//-- Grab Student List - for 1st year to compare --//
							$query2 = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$sem1." AND a.school = ".$school." AND a.".$test_url." > 0";
							//$results['query2'] = $query2;
							$response = $this->db->query($query2, array());
							$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
							$grade_response2 = $response->fetchAll();
							$grade_set2 = array();
							if(!empty($grade_response2)){
								foreach($grade_response2 as $resp){
									$grade_set2[] = $resp->id;
								}
							}

							//-- Compare Lists & Compile Final Result List --//
							if(!empty($grade_set1) && !empty($grade_set2)){
								foreach($grade_set1 as $student){
									if(in_array($student, $grade_set2)){

										if($measure != 'bmi'){ //-- higher values - Everything Else --//

											//-- Run queries to grab the data --//  //-- Most Recent --//
											$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid:", "bind" => array('school' => $school, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." DESC", "limit" => 1));
											//-- Past Grade - grab baseline --//
											$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
											//-- Grab Student Name --//
											$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "fname, lname, grade, school"));

											if($grade1->{$test_url} > $grade2->{$test_url}){
												//-- Return as Increased Group of Students --//
												$results['school']['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
												$results['school']['plus']['count']++;
												$results['school']['plus']['progress'] = $results['school']['plus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
											}else{
												//-- Return as Decreased Group of Students --//
												$results['school']['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
												$results['school']['minus']['count']++;
												$results['school']['minus']['progress'] = $results['school']['minus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
											}

										}else{ //-- Returns Lower Values - BMI --//

											//-- Run queries to grab the data --//  //-- Most Recent --//
											$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." ASC", "limit" => 1));
											//-- Past Grade - grab baseline --//
											$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
											//-- Grab Student Name --//
											$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "fname, lname, grade, school"));

											if($grade1->{$test_url} < $grade2->{$test_url}){
												//-- Return as Increased Group of Students --//
												$results['school']['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
												$results['school']['plus']['count']++;
												$results['school']['plus']['progress'] = $results['school']['plus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
											}else{
												//-- Return as Decreased Group of Students --//
												$results['school']['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
												$results['school']['minus']['count']++;
												$results['school']['minus']['progress'] = $results['school']['minus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
											}

										}

										$results['school']['count']++;
										$results['school']['progress'] = $results['school']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});

									}
								}
							}else{
								$results['result'] = "failed";
								$results['reason'] = "Empty Grade Sets";
							}

						}else{ //-- Run report for all schools --//

							//-- Grab all schools --//
							$schools = Schools::find(array("id != 6", "columns" => "id, schoolName"));
							$results['totalCount'] = $results['totalProgress'] = 0;
							foreach($schools as $school){
								//-- Instantiate Vars --//
								$results['school'][$school->id]['count'] = $results['school'][$school->id]['progress'] = 0;
								$results['school'][$school->id]['plus']['count'] = $results['school'][$school->id]['plus']['progress'] = 0;
								$results['school'][$school->id]['minus']['count'] = $results['school'][$school->id]['minus']['progress'] = 0;
								//-- Assign School Name --//
								$results['school'][$school->id]['name'] = $school->schoolName;

								//-- Build / Run Queries --//
								//-- Grab Student List - for current semester grades --//
								$query = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$this->session->get('current-semester')." AND a.school = ".$school->id." AND".($grade != '' ? " a.grade_level = ".$grade." AND" : "" )." a.".$test_url." > 0 ORDER BY s.school, s.grade, s.lname ASC, s.fname ASC";
								//$results['query'] = $query;
								$response = $this->db->query($query, array());
								$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
								$grade_response1 = $response->fetchAll();
								$grade_set1 = array();
								if(!empty($grade_response1)){
									foreach($grade_response1 as $resp){
										if(!in_array($resp->id, $grade_set1)){
											$grade_set1[] = $resp->id;
										}
									}
								}

								//-- Grab Student List - for 1st year to compare --//
								$query2 = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$sem1." AND a.school = ".$school->id." AND a.".$test_url." > 0";
								//$results['query2'] = $query2;
								$response = $this->db->query($query2, array());
								$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
								$grade_response2 = $response->fetchAll();
								$grade_set2 = array();
								if(!empty($grade_response2)){
									foreach($grade_response2 as $resp){
										$grade_set2[] = $resp->id;
									}
								}

								//-- Compare Lists & Compile Final Result List --//
								if(!empty($grade_set1) && !empty($grade_set2)){
									foreach($grade_set1 as $student){
										if(in_array($student, $grade_set2)){

											if($measure != 'bmi'){ //-- higher values - Everything Else --//

												//-- Run queries to grab the data --//  //-- Most Recent --//
												$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid:", "bind" => array('school' => $school->id, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." DESC", "limit" => 1));
												//-- Past Grade - grab baseline --//
												$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school->id, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
												//-- Grab Student Name --//
												$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "fname, lname, grade, school"));
												if($grade1->{$test_url} > $grade2->{$test_url}){
													//-- Return as Increased Group of Students --//
													$results['school'][$school->id]['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
													$results['school'][$school->id]['plus']['count']++;
													$results['school'][$school->id]['plus']['progress'] = $results['school'][$school->id]['plus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
												}else{
													//-- Return as Decreased Group of Students --//
													$results['school'][$school->id]['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
													$results['school'][$school->id]['minus']['count']++;
													$results['school'][$school->id]['minus']['progress'] = $results['school'][$school->id]['minus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
												}

											}else{ //-- Returns Lower Values - BMI --//

												//-- Run queries to grab the data --//  //-- Most Recent --//
												$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school->id, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." ASC", "limit" => 1));
												//-- Past Grade - grab baseline --//
												$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school->id, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
												//-- Grab Student Name --//
												$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "fname, lname, grade, school"));
												if($grade1->{$test_url} < $grade2->{$test_url}){
													//-- Return as Increased Group of Students --//
													$results['school'][$school->id]['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
													$results['school'][$school->id]['plus']['count']++;
													$results['school'][$school->id]['plus']['progress'] = $results['school'][$school->id]['plus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
												}else{
													//-- Return as Decreased Group of Students --//
													$results['school'][$school->id]['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}));
													$results['school'][$school->id]['minus']['count']++;
													$results['school'][$school->id]['minus']['progress'] = $results['school'][$school->id]['minus']['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});
												}

											}

											$results['school'][$school->id]['count']++;
											$results['school'][$school->id]['progress'] = $results['school'][$school->id]['progress'] + ($grade1->{$test_url} - $grade2->{$test_url});

										}
									}
									//-- Update Overall Totals --//
									$results['totalProgress'] = $results['school'][$school->id]['progress'];
									$results['totalCount'] = $results['school'][$school->id]['count'];
								}
							}

						}

					}else{
						$results['result'] = "failed";
						$results['reason'] = "Test Value Was Tampered With";
					}
				}else{
					$results['result'] = "failed";
					$results['reason'] = "Required Information Missing";
				}

				//-- encode results --//
				echo json_encode($results);
			}
		}

		//-- Disable View --//
		$this->view->disable();

	} //-- end growthReportAction() --//


	public function exportAthleticismAction(){

		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Report of all students with given metrics --//
			if($this->request->getPost("action") == 'export_athleticism_report'){
				//-- Sanitize Vars --//
				$school = $this->request->getPost("school", "int");
				$grade = $this->request->getPost("grade", "int");
				$growth = $this->request->getPost("growth", "int");
				$test_url = $this->request->getPost("test", "string");
				$measure = $this->request->getPost("measure", "string");
				$testLabel = $this->request->getPost("testLabel", "string");
				$gradeLabel = $this->request->getPost("gradeLabel", "string");
				$measureLabel = $this->request->getPost("measureLabel", "string");
				$measureText = $this->request->getPost("measureText", "string");
				$growthLabel = $this->request->getPost("growthLabel", "string");
				$columns = array('bmi', 'sprint', 'hex', 'vjump', 'sjump', 'shuttle', 'pacer', 'pushup', 'curlup', 'trunklift', 'sitreach', 'plank', 'balance', 'slraise');
				$results = $report = array();

				//-- Figure Out Semester Vars --//
				$semesters = Semesters::find(array("", "order" => "id DESC"));
				if($growth == 2){
					$sem2 = $this->session->get('current-semester');
					$tempSem = 0;
					foreach($semesters as $sem){
						if($tempSem != 0){
							$sem1 = $sem->id;
							break;
						}
						if($sem->active){
							$tempSem = $sem->id;
						}
					}
				}else if($growth == 3){
					$sem3 = $this->session->get('current-semester');
					$tempSem = $tempSem2 = 0;
					foreach($semesters as $sem){
						if($tempSem2 != 0){
							$sem1 = $sem->id;
							break;
						}
						if($tempSem != 0){
							$sem2 = $sem->id;
							$tempSem2 = $sem->id;
						}
						if($sem->active){
							$tempSem = $sem->id;
						}
					}
				}

				//-- Make sure vital data is not empty --//
				if(!empty($test_url) && !empty($growth) && isset($sem1) && $sem1 && !empty($measure)){
					//-- Make sure SQL Metric not tampered with --//
					if(in_array($test_url, $columns)){
						$results['result'] = "success";
						$report['type'] = array('plus' => 'Positive Growth', 'minus' => 'Negative Growth');

						if($school != ''){ //-- Only One School --//

							$report['school']['count'] = 0;
							$report['school']['plus']['count'] = 0;
							$report['school']['minus']['count'] = 0;

							//-- Grab School Name --//
							$schoolData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $school)));
							if(!empty($schoolData)){
								$report['school']['name'] = $schoolData->schoolName;
							}

							//-- Build / Run Queries --//
							//-- Grab Student List - for current semester grades --//
							$query = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$this->session->get('current-semester')." AND a.school = ".$school." AND".($grade != '' ? " a.grade_level = ".$grade." AND" : "" )." a.".$test_url." > 0 ORDER BY s.school, s.grade, s.lname ASC, s.fname ASC";
							//$results['query'] = $query;
							$response = $this->db->query($query, array());
							$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
							$grade_response1 = $response->fetchAll();
							$grade_set1 = array();
							if(!empty($grade_response1)){
								foreach($grade_response1 as $resp){
									if(!in_array($resp->id, $grade_set1)){
										$grade_set1[] = $resp->id;
									}
								}
							}

							//-- Grab Student List - for 1st year to compare --//
							$query2 = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$sem1." AND a.school = ".$school." AND a.".$test_url." > 0";
							//$results['query2'] = $query2;
							$response = $this->db->query($query2, array());
							$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
							$grade_response2 = $response->fetchAll();
							$grade_set2 = array();
							if(!empty($grade_response2)){
								foreach($grade_response2 as $resp){
									$grade_set2[] = $resp->id;
								}
							}

							//-- Compare Lists & Compile Final Result List --//
							if(!empty($grade_set1) && !empty($grade_set2)){
								foreach($grade_set1 as $student){
									if(in_array($student, $grade_set2)){

										if($measure != 'bmi'){ //-- higher values - Everything Else --//

											//-- Run queries to grab the data --//  //-- Most Recent --//
											$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid:", "bind" => array('school' => $school, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." DESC", "limit" => 1));
											//-- Past Grade - grab baseline --//
											$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
											//-- Grab Student Name --//
											$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "id, alt_id, state_id, fname, lname, grade, school"));

											if($grade1->{$test_url} > $grade2->{$test_url}){
												//-- Return as Increased Group of Students --//
												$report['school']['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
												$report['school']['plus']['count']++;
											}else{
												//-- Return as Decreased Group of Students --//
												$report['school']['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
												$report['school']['minus']['count']++;
											}

										}else{ //-- Returns Lower Values - BMI --//

											//-- Run queries to grab the data --//  //-- Most Recent --//
											$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." ASC", "limit" => 1));
											//-- Past Grade - grab baseline --//
											$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
											//-- Grab Student Name --//
											$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "id, alt_id, state_id, fname, lname, grade, school"));

											if($grade1->{$test_url} < $grade2->{$test_url}){
												//-- Return as Increased Group of Students --//
												$report['school']['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
												$report['school']['plus']['count']++;
											}else{
												//-- Return as Decreased Group of Students --//
												$report['school']['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
												$report['school']['minus']['count']++;
											}

										}
										//-- update School Total Count --//
										$report['school']['count']++;

									}
								}

								if($report['school']['count']){
									//-- CSV File Info --//
									$CSVFilename = "Athlos_".str_ireplace(" ", "_", $report['school']['name'])."_Athleticism_Report_".str_ireplace(" ", "_", $testLabel)."_".str_ireplace(" ", "_", $growthLabel)."_".time().".csv";
									$filepath = $_SERVER['DOCUMENT_ROOT']."/downloads/temp/".$CSVFilename;
									$results['filepath'] = $filepath;
									$results['urlpath'] = "https://tools.athlosacademies.org/downloads/temp/".$CSVFilename;
									$fp = fopen($filepath, 'w');
									//-- Iterate Through Student Data --//
									if($fp !== false){

										//-- Write out report --//
										//fwrite($fp, $report['school']['name']." Student Results (".$growthLabel."), (".$measureText." measured in ".$measureLabel.")\r\n\n");
										//$report['school']['name']." Student Results (".$growthLabel.") (".$measureText." measured in ".$measureLabel.")";
										if($grade != ''){
											fwrite($fp, $report['school']['name'].": ".$gradeLabel."\r\n\n");
										}
										fwrite($fp, "Student Results (".$growthLabel.")\r\n\n");
										fwrite($fp, "(".$measureText." measured in ".$measureLabel.")\r\n\n");

										fwrite($fp, $report['school']['name']." (".$report['school']['count'].")\r\n");
										//-- List out growth reports --//
										foreach($report['type'] as $type => $typeLabel){
											fwrite($fp, ", ".$typeLabel."  ".($type == 'plus' ? "+" : "-")." (".$report['school'][$type]['count'].")\r\n");
											//-- List out student Tables --//
											fwrite($fp, ",,Tools ID,Alt ID,State ID,Last Name, First Name,Baseline,Phase,Current Best,Phase,Grade Level,Progress\r\n"); //-- table headers --//
											foreach($report['school'][$type]['students'] as $student_info){
												fwrite($fp, ",,".$student_info['id'].",".$student_info['alt_id'].",".$student_info['state_id'].",".$student_info['name'].",".$student_info['pastdata'].",".$student_info['pastphase'].",".$student_info['curdata'].",".$student_info['curphase'].",".$student_info['grade']."th,".round($student_info['difference'], 2)."\r\n");
											}
										}
										//-- Close Socket --//
										fclose($fp);

									}else{
										$results['result'] = "failed";
										$results['opened'] = "no";
									}
								}else{
									$results['result'] = "failed";
									$results['reason'] = "No students returned in report";
								}

							}else{
								$results['result'] = "failed";
								$results['reason'] = "Empty Grade Sets";
							}

						}else{ //-- Run report for all schools --//

							//-- CSV File Info --//
							$CSVFilename = "Athlos_All_Schools_Athleticism_Report_".str_ireplace(" ", "_", $testLabel)."_".str_ireplace(" ", "_", $growthLabel)."_".time().".csv";
							$filepath = $_SERVER['DOCUMENT_ROOT']."/downloads/temp/".$CSVFilename;
							$results['filepath'] = $filepath;
							$results['urlpath'] = "https://tools.athlosacademies.org/downloads/temp/".$CSVFilename;
							$fp = fopen($filepath, 'w');
							//-- Iterate Through Student Data --//
							if($fp !== false){

								//-- Write Report Headers --//
								if($grade != ''){
									fwrite($fp, "All Schools: ".$gradeLabel."\r\n\n");
								}else{
									fwrite($fp, "All Schools: \r\n\n");
								}
								fwrite($fp, "Student Results (".$growthLabel.")\r\n\n");
								fwrite($fp, "(".$measureText." measured in ".$measureLabel.")\r\n\n");

								//-- Grab all schools --//
								$schools = Schools::find(array("id != 6", "columns" => "id, schoolName"));
								foreach($schools as $school){
									//-- Instantiate Vars --//
									$report['school'][$school->id]['count'] = 0;
									$report['school'][$school->id]['plus']['count'] = 0;
									$report['school'][$school->id]['minus']['count'] = 0;
									//-- Assign School Name --//
									$report['school'][$school->id]['name'] = $school->schoolName;

									//-- Build / Run Queries --//
									//-- Grab Student List - for current semester grades --//
									$query = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$this->session->get('current-semester')." AND a.school = ".$school->id." AND".($grade != '' ? " a.grade_level = ".$grade." AND" : "" )." a.".$test_url." > 0 ORDER BY s.school, s.grade, s.lname ASC, s.fname ASC";
									//$results['query'] = $query;
									$response = $this->db->query($query, array());
									$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
									$grade_response1 = $response->fetchAll();
									$grade_set1 = array();
									if(!empty($grade_response1)){
										foreach($grade_response1 as $resp){
											if(!in_array($resp->id, $grade_set1)){
												$grade_set1[] = $resp->id;
											}
										}
									}

									//-- Grab Student List - for 1st year to compare --//
									$query2 = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$sem1." AND a.school = ".$school->id." AND a.".$test_url." > 0";
									//$results['query2'] = $query2;
									$response = $this->db->query($query2, array());
									$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
									$grade_response2 = $response->fetchAll();
									$grade_set2 = array();
									if(!empty($grade_response2)){
										foreach($grade_response2 as $resp){
											$grade_set2[] = $resp->id;
										}
									}

									//-- Compare Lists & Compile Final Result List --//
									if(!empty($grade_set1) && !empty($grade_set2)){
										foreach($grade_set1 as $student){
											if(in_array($student, $grade_set2)){

												if($measure != 'bmi'){ //-- higher values - Everything Else --//

													//-- Run queries to grab the data --//  //-- Most Recent --//
													$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid:", "bind" => array('school' => $school->id, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." DESC", "limit" => 1));
													//-- Past Grade - grab baseline --//
													$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school->id, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
													//-- Grab Student Name --//
													$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "id, alt_id, state_id, fname, lname, grade, school"));
													if($grade1->{$test_url} > $grade2->{$test_url}){
														//-- Return as Increased Group of Students --//
														$report['school'][$school->id]['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
														$report['school'][$school->id]['plus']['count']++;
													}else{
														//-- Return as Decreased Group of Students --//
														$report['school'][$school->id]['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
														$report['school'][$school->id]['minus']['count']++;
													}

												}else{ //-- Returns Lower Values - BMI --//

													//-- Run queries to grab the data --//  //-- Most Recent --//
													$grade1 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school->id, 'sem' => $this->session->get('current-semester'), 'sid' => $student), "order" => $test_url." ASC", "limit" => 1));
													//-- Past Grade - grab baseline --//
													$grade2 = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND student = :sid: AND ".$test_url." > 0", "bind" => array('school' => $school->id, 'sem' => $sem1, 'sid' => $student), "order" => "id ASC", "limit" => 1));
													//-- Grab Student Name --//
													$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "id, alt_id, state_id, fname, lname, grade, school"));
													if($grade1->{$test_url} < $grade2->{$test_url}){
														//-- Return as Increased Group of Students --//
														$report['school'][$school->id]['plus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
														$report['school'][$school->id]['plus']['count']++;
													}else{
														//-- Return as Decreased Group of Students --//
														$report['school'][$school->id]['minus']['students'][] = array('curdata' => $grade1->{$test_url}, 'curphase' => $grade1->interval, 'cursem' => $this->session->get('current-semester'), 'pastdata' => $grade2->{$test_url}, 'pastphase' => $grade2->interval, 'pastsem' => $sem1, 'name' => $student_info->lname.', '.$student_info->fname, 'grade' => $student_info->grade, 'difference' => ($grade1->{$test_url} - $grade2->{$test_url}), 'id' => $student_info->id, 'alt_id' => $student_info->alt_id, 'state_id' => $student_info->state_id);
														$report['school'][$school->id]['minus']['count']++;
													}

												}

												$report['school'][$school->id]['count']++;

											}
										}

										if($report['school'][$school->id]['count']){

											//-- Write Out School Report --//
											fwrite($fp, $report['school'][$school->id]['name']." (".$report['school'][$school->id]['count'].")\r\n");
											//-- List out growth reports --//
											foreach($report['type'] as $type => $typeLabel){
												fwrite($fp, ", ".$typeLabel."  ".($type == 'plus' ? "+" : "-")." (".$report['school'][$school->id][$type]['count'].")\r\n");
												//-- List out student Tables --//
												$x = 0;
												fwrite($fp, ",,Tools ID,Alt ID,State ID,Last Name, First Name,Baseline,Phase,Current Best,Phase,Grade Level,Progress\r\n"); //-- table headers --//
												foreach($report['school'][$school->id][$type]['students'] as $student_info){
													fwrite($fp, ",,".$student_info['id'].",".$student_info['alt_id'].",".$student_info['state_id'].",".$student_info['name'].",".$student_info['pastdata'].",".$student_info['pastphase'].",".$student_info['curdata'].",".$student_info['curphase'].",".$student_info['grade']."th,".round($student_info['difference'], 2)."\r\n");
													$x++;
												}
											}
											//-- end write out school report --//

										}
									}
								}

								//-- Close Socket --//
								fclose($fp);

							}else{
								$results['result'] = "failed";
								$results['opened'] = "no";
							}

						}

					}else{
						$results['result'] = "failed";
						$results['reason'] = "Test Value Was Tampered With";
					}
				}else{
					$results['result'] = "failed";
					$results['reason'] = "Required Information Missing";
				}

				//-- encode results --//
				echo json_encode($results);
			}
		}

		//-- Disable View --//
		$this->view->disable();

	} //-- end exportAthleticismAction() --//

	/**
	 * Pull athletic data filtered by params for school year reports.
	 */
	public function exportAthleticDataAction(){
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Function to Report of all students with given metrics --//
			if($this->request->getPost("action") == 'export_athletic_data'){
				//-- Sanitize Vars --//
				$campus = $this->request->getPost("campus", "int");
				$year = $this->request->getPost("year", "int");
				$phase = $this->request->getPost("phase");
				$results = $studentList = array();
				//-- Make Sure vital data exists and is correct --//
				if ($campus && $year && in_array($phase, array('all', 1, 2))) {

					//-- Grab School Name --//
					$campusName = '';
					$campusData = Schools::findFirst(array('id = :sid:', "bind" => array("sid" => $campus)));
					if(!empty($campusData)){
						$campusName = $campusData->schoolName;
					}

					//-- Grab School Year Title --//
					$schoolYear = '';
					$yearData = Semesters::findFirst(array('id = :sid:', "bind" => array("sid" => $year)));
					if(!empty($yearData)){
						$schoolYear = $yearData->semesterName;
					}

					//-- Grab Student List to include in report --//
					if ($phase === 'all') {
						//-- All Phases --//
						$query = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$year." AND a.school = ".$campus." ORDER BY s.lname ASC, s.fname ASC";
					} else {
						//-- Single Phases --//
						$phase = (int)$phase;
						$query = "SELECT s.id FROM athletic_grading AS a, students AS s WHERE s.id = a.student AND a.semester = ".$year." AND a.school = ".$campus." AND a.interval = ".$phase." ORDER BY s.lname ASC, s.fname ASC";
					}
					//-- Grab and assemble the list of Student Ids --//
					$response = $this->db->query($query, array());
					$response->setFetchMode(Phalcon\Db::FETCH_OBJ);
					$list_response = $response->fetchAll();
					//$results['query'] = $query;
					$studentList = array();
					if(!empty($list_response)){
						foreach($list_response as $listItem){
							if(!in_array($listItem->id, $studentList)){
								$studentList[] = $listItem->id;
							}
						}
					}

					//-- Compare Lists & Compile Final Result List --//
					if (!empty($studentList)) {
						foreach ($studentList as $index => $student) {
							if ($index === 0) {
								//-- On First student, Send out document headers --//
								//-- CSV File Info --//
								$CSVFilename = "Athlos_".str_ireplace(" ", "_", $campusName)."_Athletic_Data_Export_".str_ireplace(" ", "_", $schoolYear)."_".time().".csv";
								$filepath = $_SERVER['DOCUMENT_ROOT']."/downloads/temp/".$CSVFilename;
								$results['result'] = 'success';
								$results['filepath'] = $filepath;
								$results['urlpath'] = "https://assessments.athlos.org/downloads/temp/".$CSVFilename;
								$fp = fopen($filepath, 'w');
								if ($fp !== false) {
									//-- table headers --//
									fwrite($fp, "School Name, Student Number, Last Name, First Name, School Year, Assessment Phase, 20 yard sprint, Vertical Jump, Straight Leg Raise (Left Leg), Straight Leg Raise (Right Leg), Push up, Plank, Pacer, Hex Test, Height (BMI), Weight (BMI), BMI, Limb Length (Balance), Reach Distance (Balance)\r\n");
								}
							}

							//-- Grab Student Name --//
							$student_info = Students::findFirst(array("id = :sid:", "bind" => array("sid" => $student), "columns" => "id, alt_id, fname, lname"));
							$reportItems = array();
							//-- Build / Run Grading Queries --//
							if ($phase === 'all') {
								//-- All Phases --//
								$athleticData = AthleticGrading::find(array("school = :school: AND semester = :sem: AND student = :sid:", "bind" => array('school' => $campus, 'sem' => $year, 'sid' => $student), "order" => "interval ASC"));
								if (!empty($athleticData)) {
									foreach ($athleticData as $data) {
										$reportItems[] = $data;
									}
								}
							} else {
								//-- Single Phases --//
								$athleticData = AthleticGrading::findFirst(array("school = :school: AND semester = :sem: AND interval = :phase: AND student = :sid:", "bind" => array('school' => $campus, 'sem' => $year, 'phase' => $phase, 'sid' => $student), "limit" => 1));
								$reportItems[] = $athleticData;
							}

							//-- If file socket is open and working - Iterate over studentList and each Student's Data --//
							if($fp !== false){
								foreach($reportItems as $item){
									//-- Write Out Report Data --//
									fwrite($fp, $campusName.", ".$student_info->alt_id.", ".$student_info->lname.", ".$student_info->fname.", ".$schoolYear.", Phase ".$item->interval.", ".$item->sprint.", ".$item->vjump.", ".$item->sl_left.", ".$item->sl_right.", ".$item->pushup.", ".$item->plank.", ".$item->pacer.", ".$item->hex.", ".$item->height.", ".$item->weight.", ".$item->bmi.", ".$item->limb_length.", ".$item->absolute_reach."\r\n");
								}

							}else{
								$results['result'] = "failed";
								$results['opened'] = "no";
								$results['reason'] = "Failed to Open Socket - Maybe partially worked.";
							}

						} // end foreach

						//-- Close Socket --//
						if ($fp !== false) {
							fclose($fp);
						}

					}else{
						$results['result'] = "failed";
						$results['reason'] = "No Students included in the reports.";
					}

				} else {
					$results['result'] = "failed";
					$results['reason'] = "Required Information Missing";
				}

				//-- encode results --//
				echo json_encode($results);
			}
		}

		//-- Disable View --//
		$this->view->disable();

	} // exportAthleticDataAction

}
