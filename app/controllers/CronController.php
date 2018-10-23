<?php

date_default_timezone_set('UTC');

class CronController extends \Phalcon\Mvc\Controller
{
	
	public function bkpdbAction()
	{
		//-- Redirect if not correct key --//
		if(isset($_GET['accesskey']) && $_GET['accesskey'] === 'bZCbVzeAqkaQGnHrKK6XsM9mcZmCjfgM'){
			//-- do nothing, key is correct --//
		}else{
			return $this->response->redirect("session/");
		}
		
		/*--------------------------------------
			Instantiate Amazon Bucket S3 Code
		---------------------------------------*/
		//include the S3 class              
		if(!class_exists('S3'))require_once('amazon-s3/S3.php');
		//AWS access info
		if (!defined('awsAccessKey')) define('awsAccessKey', 'AKIAJIEMIW6FVXKO2QOQ');
		if (!defined('awsSecretKey')) define('awsSecretKey', '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');
		//instantiate the class
		$s3 = new S3(awsAccessKey, awsSecretKey);
		/*-- end instantiate amazon bucket code -*/
		
		$bucket_contents = $s3->getBucket("athlos-tools-bkps");
		//-- Loop through bucket contents and compare against last entry --//
		if($bucket_contents){
			$bkp_count = count($bucket_contents);
			$cur_item = 0;
			foreach($bucket_contents as $key => $file){
				$cur_item++;
				
				//-- Delete Older Backups after so many backups exist --//
				if($bkp_count > 60){
					if($cur_item <= ($bkp_count - 60)){
					//if($cur_item > 60){
						$s3->deleteObject("athlos-tools-bkps", $key);
						echo 'Deleted - '.$key.'<br />';
						
						if(file_exists('/bkps/'.$file['name'])){
							//-- Delete local bkp file --//
							unlink('/bkps/'.$file['name']);
						}
					}
				}
				
				if($bkp_count === $cur_item){
					if($file['time'] < (time() - 83000)){
						//-- If it has been over 24 hours since the last backup, create a new one (83,000) --//
						
						/*-----------------------------------
							Perform DB Backup Cron Script
							- push to Amazon S3 Bucket -
						------------------------------------*/
						//ENTER THE RELEVANT INFO BELOW
						$mysqlDatabaseName = 'athlos_tools';
						$mysqlUserName = 'root';
						$mysqlPassword = 'D1zzkn33l@nd';
						$mysqlHostName = '192.168.128.94';

						//-- Filename --//
						$filename = time().'-bkp-db-'.date('m').'.'.date('d').'.'.date('Y').'.sql';
						$mysqlExportPath ='../../tmp/'.$filename;
						$output=array();

						//DO NOT EDIT BELOW THIS LINE
						//Export the database and output the status to the page
						$command='mysqldump --skip-dump-date -h '.$mysqlHostName.' -u '.$mysqlUserName.' -p'.$mysqlPassword.' '.$mysqlDatabaseName.' > ~/'.$mysqlExportPath;
						exec($command,$output,$worked);
						switch($worked){
							case 0:
							echo 'Database <b>' .$mysqlDatabaseName .'</b> successfully exported to <b>~/' .$mysqlExportPath .'</b>';
							break;
							case 1:
							echo 'There was a warning during the export of <b>' .$mysqlDatabaseName .'</b> to <b>~/' .$mysqlExportPath .'</b>';
							break;
							case 2:
							echo 'There was an error during export. Please check your values:<br/><br/><table><tr><td>MySQL Database Name:</td><td><b>' .$mysqlDatabaseName .'</b></td></tr><tr><td>MySQL User Name:</td><td><b>' .$mysqlUserName .'</b></td></tr><tr><td>MySQL Password:</td><td><b>NOTSHOWN</b></td></tr><tr><td>MySQL Host Name:</td><td><b>' .$mysqlHostName .'</b></td></tr></table>';
							break;
						}
						
						//-- Push to Amazon Bucket / local backup on server --//
						if(file_exists('/tmp/'.$filename)){
							echo '<br />Server Address: '.$_SERVER['SERVER_ADDR'].'<br /><br />';
							$md5Str = md5(file_get_contents('http://athlos-tools-bkps.s3.amazonaws.com/'.$file['name']));
							$md5File = md5_file('/tmp/'.$filename);
							if($md5Str != $md5File){
								//-- Place into amazon s3 --//
								if($s3->putObjectFile('/tmp/'.$filename, "athlos-tools-bkps", $filename, S3::ACL_PUBLIC_READ)) {
									echo "\n\n<br /><strong>Success</strong> We successfully uploaded your file.<br />";
									
									//-- DB Local Backup --//
									if(copy('http://athlos-tools-bkps.s3.amazonaws.com/'.$filename, '/bkps/'.$filename)){
										echo "\n\n<br /><strong>Success</strong> We successfully created local backup.<br />";
									}else{
										echo "\n\n<br /><strong>Error</strong> Local Backup failed!<br />";
									}
									
								}else{
									echo "\n\n<br /><strong>Error</strong> Something went wrong while uploading your file... sorry.<br />";
								}
								
							}else{
								echo "\n\n<br /><strong>DB Hasn't Changed</strong> Did not backup file, no changes have been made to DB since last backup.<br />";
							}
							
							echo "\n\nremote: ".$md5Str." local: ".$md5File."\n";
							
							//-- Delete bkp file --//
							unlink('/tmp/'.$filename);
						}
						
					}
				}
			}
		}
		
		//-- List out amazon bucket contents --//
		if(isset($_GET['showlist']) && $_GET['showlist'] == true){
			$bucket_contents = $s3->getBucket("athlos-tools-bkps");
			if(isset($bucket_contents) && $bucket_contents){
				echo '<h3>Athlos Tools DB Backups</h3>';
				foreach($bucket_contents as $file){
				    $fname = $file['name'];
				    $furl = "http://athlos-tools-bkps.s3.amazonaws.com/".$fname;
					$fsize = $file['size'];
					$ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));

				    //-- Output link to file --//
					echo '<div>';
				    echo '<a href="'.$furl.'">'.$fname.'</a>';
					echo '<span>('.round($fsize / 1024, 1).' KB)</span>';
					echo '</div>';
				}
			}
		}
		
		exit();
		/*-- end DB Bkp Cron Script --*/
		
		//-- Disable View --//
		$this->view->disable();
	} //-- end bkpdbAction() --//
	
	
	public function dashstatsAction()
	{
		//-- Redirect if not correct key --//
		if(isset($_GET['accesskey']) && $_GET['accesskey'] === 'bZCbVzeAqkaQGnHrKK6XsM9mcZmCjfgM'){
			//-- do nothing, key is correct --//
		}else{
			return $this->response->redirect("session/");
		}
		
		/*--------------------------------
		 	Character Curriculum Stats
		---------------------------------*/
		$curriculum = Curriculum::find(array("post_status = 'publish'", "columns" => "id, post_author"));
		$finalSchoolStats = $schoolstats = array();
		$adminStats = $otherStats = $totalPosts = 0;
		foreach($curriculum as $post){
			$author = $post->post_author;
			$user = Users::findFirst(array("id = :id:", "columns" => "id, school", "bind" => array("id" => $author)));
			if($user && $user->school != 0){
				if($schoolstats[$user->school]){
					$schoolstats[$user->school]++;
				}else{
					$schoolstats[$user->school] = 1;
				}
				$totalPosts++;
			}else{
				$adminStats++;
			}
		}
		//-- sort from high to low - but keep key association --//
		arsort($schoolstats);
		
		//-- limit stats to 6 --//
		if(count($schoolstats) > 6){
			$x = 1;
			foreach($schoolstats as $key => $val){
				if($x > 5){
					$otherStats = $otherStats + $val;
					unset($schoolstats[$key]);
				}
				$x++;
			}
		}
		//-- Add School Labels & percentage --//
		foreach($schoolstats as $stat_key => $stat_val){
			//-- percentage --//
			$percent = round(($stat_val / $totalPosts) * 100);
			//-- grab school --//
			$school = Schools::findFirst(array("id = :id:", "columns" => "id, schoolName", "bind" => array("id" => $stat_key)));
			$finalSchoolStats[$stat_key] = array("label" => $school->schoolName, "count" => $stat_val, "percent" => $percent);
		}
		
		//-- Add Other Schools (if too many) --//
		if($otherStats > 0){
			$percent = round(($otherStats / $totalPosts) * 100);
			$finalSchoolStats['other'] = array("label" => "Other Schools", "count" => $otherStats, "percent" => $percent);
		}
		
		//-- Add Administration --//
		$finalSchoolStats['admin'] = array("label" => "Administration", "count" => $adminStats);
		
		//-- Prepare for DB --//
		$finalStats = serialize($finalSchoolStats);
		
		//-- Enter DB --//
		$option = Options::findFirst("option = 'character-curriculum-stats'");
		$option->value = $finalStats;
		if($option->save() == false){
			echo "\n\nCharacter Curriculum Stats Update FAILED!!<br><br>\n\n";
		}else{
			echo "\n\nCharacter Curriculum Stats Updated!<br><br>\n\n";
		}
		
		exit();
		/*-- end Dash Stats Cron Script --*/
		
		//-- Disable View --//
		$this->view->disable();
	} //-- end dashstatsAction() --//
	
	
	public function cleandownloadsAction()
	{
		//-- Redirect if not correct key --//
		if(isset($_GET['accesskey']) && $_GET['accesskey'] === 'bZCbVzeAqkaQGnHrKK6XsM9mcZmCjfgM'){
			//-- do nothing, key is correct --//
		}else{
			return $this->response->redirect("session/");
		}
		
		/*---------------------------------------
			Remove all files in Temp Downloads 
		----------------------------------------*/
		$x = 0;
		foreach (glob($_SERVER['DOCUMENT_ROOT']."/downloads/temp/*") as $filepath) {
			unlink($filepath);
			$x++;
		}
		
		echo "\n\nAll Temporary Downloads Have Been Removed! Count (".$x.")\n\n";
		
		exit();
		/*-- end Clean Up Temporary Downloads Script --*/
		
		//-- Disable View --//
		$this->view->disable();
	} //-- end cleandownloadsAction() --//
	
}
