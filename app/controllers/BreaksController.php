<?php

date_default_timezone_set('UTC');

//-- Include Mailgun Libraries --//
require "../app/controllers/mailgun/vendor/autoload.php";
use Mailgun\Mailgun;

class BreaksController extends \Phalcon\Mvc\Controller
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
		if(!$this->cap['movement-breaks']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Mailgun Object --//
		$this->mailgun = new Mailgun('key-9smg5kx05w1kjd5l3kd1j8zs252p2-h6');
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Breaks");
		//$this->view->setVar("navPage", "Suggestions");
	}
	
	
    public function lessonsAction()
    {
		//-- Grab Content Types Object --//
		$types = BreakTypes::find('');
		
		//-- Pass Objects to View --//
		$this->view->setVar("types", $types);
    }
	
	//-- Ajax Action to grab pages for curriculum content --//
	public function pageAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			
			//-- Function to Grab New Page of Content --//
			if($this->request->getPost("action") == 'get_new_page'){
				//-- grab / set vars --//
				$curPage = $this->request->getPost("thePage");
				$limit = $this->request->getPost("theLimit");
				$type = $this->request->getPost("theType");
				$results = array();
				
				//-- Sanitize vars --//
				$curPage = $this->filter->sanitize($curPage, "int");
				$limit = $this->filter->sanitize($limit, "int");
				$type = $this->filter->sanitize($type, "int");
				
				if($curPage && $limit && $type){
					
					//-- Figure out other vars --//
					$offset = ($curPage - 1) * $limit;
					
					//-- Grab Posts --//
					$posts = MovementBreaks::query()
						->where("post_type = ".$type)
						->andWhere("post_status = 'publish'")
						->order("post_title")
						->limit($limit, $offset)
						->execute();
					
					$row = $i = 0;
					ob_start();
					foreach($posts as $post){
						if($i == 0){
							if($row != 0){ echo '</div> <!-- /.row -->'; }
							echo '<div class="row">';
							$row++;
						} ?>
						<div class="col-md-4 col-sm-6" style="max-width: 360px;">

							<?php
							//-- Grab author object --//
							$authorName = 'admin';
							$author = $post->users;
							
							//-- Determine Layout - based on post properties --//
							if(!empty($post->post_video_url)){
								//-- Video / Image Portlets --// ?>

								<div class="thumbnail">
									<?php
									if(!empty($post->post_video_image)){
										//-- make sure video image is pulling from https --//
										$post->post_video_image = str_ireplace('http://', 'https://', $post->post_video_image);
										if(getimagesize($post->post_video_image) !== false){
											$post->post_image_url = $post->post_video_image;
										}else{
											$post->post_image_url = '/img/empty-photo_320x180.jpg';
										}
									}else{
										$post->post_image_url = '/img/empty-photo_320x180.jpg';
									}
									
									//-- Set lightbox Url && Classes --//
									if(!empty($post->post_video_url)){
										$post->url = $post->post_video_url;
										$lightboxClass = 'ui-lightbox-video';
									}else if(isset($post->post_image_url)){
										$post->url = $post->post_image_url;
										$lightboxClass = 'ui-lightbox';
									}

									?>
									<div class="thumbnail-view">
										<a href="<?php echo $post->url; ?>" class="thumbnail-view-hover <?php echo $lightboxClass; ?>"></a>
								        <img src="<?php echo $post->post_image_url; ?>" style="width: 100%" alt="Gallery Image" />
								    </div>
								    <div class="caption">
										<h3 class="curr-display-head"><?php echo stripslashes(html_entity_decode($post->post_title, ENT_QUOTES)); ?></h3>
										<div class="curr-display-content">
											<?php //-- Truncate visible chars in content to specified amount --//
											$currContent = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
											//-- Display Content --//
											echo strip_tags($currContent, '<p><a><b><em><i><ul><blockquote><span><u><li><ol><br>'); ?>
										</div>
										<p class="curr-display-actions">
											<a href="javascript:;" class="btn btn-primary btn-sm btn-sm viewComplex">View</a>
											<?php
											//-- include button for external link if there is one --//
											if($post->post_external_url){ ?>
												<a href="<?php echo $post->post_external_url; ?>" target="_blank" class="btn btn-secondary btn-sm btn-sm">Resource</a>
											<?php }else if($post->post_video_url){ ?>
												<a href="<?php echo $post->post_video_url; ?>" target="_blank" class="btn btn-secondary btn-sm btn-sm">Video</a>
											<?php } ?>
										</p>
								    </div>
								    <div class="thumbnail-footer">
								    	<div class="pull-left">
								        	<a class="post-author"><i class="fa fa-user"></i>
												&nbsp;<?php if(isset($author->usernm) && $author->usernm){ echo $author->usernm; }else{ echo $authorName; } ?>
											</a>
								        </div>

								        <div class="pull-right">
								        	<a class="post-time"><i class="fa fa-clock-o"></i> <?php echo date('M j, Y', $post->post_time); ?></a>
										</div>
									</div>
								</div>

								<?php
							}else{
								//-- Plain Portlets --// ?>
								<div class="portlet portlet-plain">
									<div class="portlet-header">
										<h3><?php echo stripslashes(html_entity_decode($post->post_title, ENT_QUOTES)); ?></h3>
									</div> <!-- /.portlet-header -->
									<div class="portlet-content">
										
										<div class="curr-display-content">
											<?php //-- Truncate visible chars in content to specified amount --//
											$currContent = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
											//-- Display Content --//
											echo str_ireplace('Description', '', strip_tags($currContent, '<p><a><b><em><i><blockquote><span><u><br>')); ?>
										</div>
										<div class="curr-modal-content">
											<?php //-- Truncate visible chars in content to specified amount --//
											$currContent = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
											//-- Display Content --//
											echo strip_tags($currContent, '<p><a><b><em><i><ul><blockquote><span><u><li><ol><h1><h2><br>'); ?>
										</div>
										<p class="curr-display-actions">
											<a class="btn btn-primary btn-sm btn-sm viewPlain">View</a>
											<?php
											//-- include button for external link if there is one --//
											if($post->post_external_url){ ?>
												<a href="<?php echo $post->post_external_url; ?>" target="_blank" class="btn btn-secondary btn-sm btn-sm">Resource</a>
											<?php } ?>
										</p>
										
										<div class="thumbnail-footer">
									    	<div class="pull-left">
									        	<a class="post-author"><i class="fa fa-user"></i>
													&nbsp;<?php if(isset($author->usernm) && $author->usernm){ echo $author->usernm; }else{ echo $authorName; } ?>
												</a>
									        </div>

									        <div class="pull-right">
									        	<a class="post-time"><i class="fa fa-clock-o"></i> <?php echo date('M j, Y', $post->post_time); ?></a>
											</div>
										</div>
									</div> <!-- /.portlet-content -->

								</div> <!-- /.portlet --><?php
							} ?>

						</div> <!-- /.col -->
						<?php
						$i++; if($i == 3){ $i = 0; }		
					}
					//-- add closing div if posts were returned --//
					if(count($posts) > 0){ echo '</div>'; }
					
					$results['content'] = ob_get_clean();
					$results["result"] = "success";
					
				}else{
					//invalid input
					$results["result"] = "failed";
				}
				
				//-- encode results --//
				echo json_encode($results);
			}
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	}
	
	
    public function suggestionsAction()
    {
		$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
		
		/*------------------------------------
			Validate Suggestion Submission
		-------------------------------------*/
		//-- Check if request has made with POST --//
		if($this->request->isPost() == true && $this->request->getPost("form-action") == 'submit-suggestion'){
			//-- Make sure user has access --//
			if($this->cap['movement-breaks']['submit-contribution']){
				//-- Initialize Error Boolean --//
				$errFound = true;
				
				//-- Sanitize Inputs --//
				$title = htmlentities($this->request->getPost("post_title"), ENT_QUOTES);
				$content = htmlentities($this->request->getPost("post_content"), ENT_QUOTES);
				$author = $this->request->getPost("post_author");
				$content_type = $this->request->getPost("content_type");
				$external_url = $this->request->getPost("post_resource");
				//$image_url = $this->request->getPost("post_image");
				$video_url = $this->request->getPost("post_video");
				$video_image = NULL;
				
				//-- Anticipate incorrect urls --//
				if($external_url && strpos($external_url, '://') === false){ $external_url = 'http://'.$external_url; }
				//if($image_url && strpos($image_url, '://') === false){ $image_url = 'http://'.$image_url; }
				if($video_url && strpos($video_url, '://') === false){ $video_url = 'http://'.$video_url; }
				
				//-- validate data types --//
				$invalid = array();
				if(!is_numeric($author) || strlen($author) > 20){
					$invalid[] = "<strong>Invalid Data</strong> Incorrect Value for Author Indicated. Please try again.";
				}
				if(!is_numeric($content_type) || strlen($content_type) > 5){
					$invalid[] = "<strong>Invalid Data</strong> Incorrect Content Type Indicated. Please try again with a different value.";
				}
				if(isset($external_url) && $external_url){
					if(!filter_var($external_url, FILTER_VALIDATE_URL) || strlen($external_url) > 255){
						$invalid[] = "<strong>External Resource Url Invalid or Too Long</strong> Make sure the URL is valid and starts with 'http://'  --  If URL is too long try a url shortener <a href='http://goo.gl/' target='_blank'>goo.gl</a>. Then try again with the shortened url.";
					}
				}
				/*if(isset($image_url) && $image_url){
					if(!filter_var($image_url, FILTER_VALIDATE_URL) || strlen($image_url) > 255){
						$invalid[] = "<strong>Image Url Invalid or Too Long</strong> Make sure the URL is valid and starts with 'http://'  --  If URL is too long try a url shortener <a href='http://goo.gl/' target='_blank'>goo.gl</a>. Then try again with the shortened url.";
					}
				}*/
				if(isset($video_url) && $video_url){
					if(!filter_var($video_url, FILTER_VALIDATE_URL) || strlen($video_url) > 255){
						$invalid[] = "<strong>Video Url Invalid or Too Long</strong> Make sure the URL is valid and starts with 'http://'  --  If URL is too long try a url shortener <a href='http://goo.gl/' target='_blank'>goo.gl</a>. Then try again with the shortened url.";
					}
				}
				
				//-- Make sure all required fields are present --//
				if($title && $content && $author && $content_type){
					
					//-- if video is being posted make sure video url is present --//
					if($content_type == 1 && !$video_url){
						$invalid[] = "<strong>Video Url Required</strong> When submitting a video post, you have to include a video url. Please enter a video url and try again.";
					}
					
					if(empty($invalid)){
						//-- Grab Video Image if present --//
						if($video_url){
							if(strpos($video_url, 'youtube.') || strpos($video_url, 'youtu.be')){
								//-- Handle a youtube video --//
								if(strpos($video_url, 'watch?v=')){
									$videoID = substr($video_url, strripos($video_url, '?v=')+3);
									if(strpos($videoID, '&')){
										$videoID = substr($videoID, 0, strpos($videoID, '&'));
									}
									$video_image = 'https://img.youtube.com/vi/'.$videoID.'/mqdefault.jpg';
									//-- New video url -- force certain format --//
									$video_url = 'http://www.youtube.com/watch?v='.$videoID;
								}else{
									$videoID = substr($video_url, strripos($video_url, '/')+1);
									$video_image = 'https://img.youtube.com/vi/'.$videoID.'/mqdefault.jpg';
									//-- New video url -- force certain format --//
									$video_url = 'http://www.youtube.com/watch?v='.$videoID;
								}
							}else if(strpos($video_url, 'vimeo.')){
								//-- Handle a vimeo video --//
								$videoID = substr($video_url, strripos($video_url, '/')+1);
								$videoID = $this->filter->sanitize($videoID, "int");
								$json = file_get_contents('http://vimeo.com/api/v2/video/'.$videoID.'.json');
								$obj = json_decode($json);
								$video_image = $obj[0]->thumbnail_large;
							}
						}
						
						//-- Enter into DB if no errors --//
						$post = New MovementBreaks();
						$post->post_author = $author;
						$post->post_content = $content;
						$post->post_title = $title;
						$post->post_type = $content_type;
						if($external_url){ $post->post_external_url = $external_url; }
						//if($image_url){ $post->post_image_url = $image_url; }
						if($video_url){ $post->post_video_url = $video_url; }
						if($video_image){ $post->post_video_image = $video_image; }
						$post->post_date = date('Y-m-d H:i:s');
						$post->post_time = time();
							//-- if super admin submits -- just approve --//
						if($this->session->get("user-role") == 1){
							$post->post_status = 'publish';
						}else{
							$post->post_status = 'draft';
						}
						
						//-- Save Entry --//
						if($post->save() == false){
							$this->flashSession->error($preMsg."<strong>Post Failed!</strong> Your suggestion failed to submit... Try Again...");
						}else{
							//-- Set Error Boolean to False --//
							$errFound = false;
							$this->flashSession->success($preMsg."<strong>Congratulations!</strong> Your suggestion was submitted successfully...");
							
							//-- Send "New Pending Content" email notification to Super Admins if submitted by a non-super-admin --//
							if(!$this->cap['movement-breaks']['manage']){
								$to = 'cherdt@athlosacademies.org';
								$subject = "New Pending Movement Break";
								$message = "Athlos has new movement break content that is now pending and waiting to be approved.\n Please login and view the pending content by going to the 'Suggestions' Tab under the Movement Breaks Section in the admin.\nOnce there you may approve, view, edit or trash the new submission based on whether or not it fits Athlos Content Standards.\n\nThanks again,\n\n\t- Athlos Tools";
								//-- Send MSG with Mailgun --//
								$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
								                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
								                        'to'      => $to,
								                        'subject' => $subject,
								                        'text'    => $message));
							}
						}

					}else{
						//-- gather the error and display it --//
						$errStr = '';
						foreach($invalid as $err){
							if($errStr == ''){
								$errStr = $err;
							}else{
								$errStr.= '<br />'.$err;
							}
						}
						$this->flashSession->error($preMsg.$errStr);
					}
					
				}else{
					$this->flashSession->error($preMsg."<strong>Missing Required Fields</strong> Make sure the post has a title, some content and a content type.");
				}
				
				//-- Pass Error Boolean to View --//
		        $this->view->setVar("errFound", $errFound);
		
			}else{
				$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to perform this action.");
			}
			
		} //-- end if there is POST data -- end validation --//
		/*--------------------
			end Validation
		---------------------*/
		
		
		//echo stripslashes(html_entity_decode($content, ENT_QUOTES));
		
		//-- Grab Content Types Object --//
		$types = BreakTypes::find('');
		//-- Grab List of Users --//
		$authorList = Users::find('role != 9');
		
		//-- Pass Objects to View --//
		$this->view->setVar("cap", $this->cap);
		$this->view->setVar("types", $types);
		$this->view->setVar("authorList", $authorList);
		
		if($this->cap['movement-breaks']['manage']){
			//-- Super Admin Objects --//
			$pending = MovementBreaks::find("post_status = 'draft'");
			$this->view->setVar("pendingPosts", $pending); //-- Pending Posts --//
			$approved = MovementBreaks::find(array("post_status = 'publish'", 'order' => 'id'));
			$this->view->setVar("approvedPosts", $approved); //-- Approved Posts --//
			$trashed = MovementBreaks::find(array("post_status = 'trash'", 'order' => 'id'));
			$this->view->setVar("trashedPosts", $trashed); //-- Trashed Posts --//
		}else{
			//-- Grab all user's suggestions --//
			$myPosts = MovementBreaks::find(array(
				"conditions" => "post_author = :userID:",
				"bind" => array('userID' => $this->session->get("user-id"))
				));
			$this->view->setVar("myPosts", $myPosts);
		}
		
		
    } //-- end suggestionsAction() --//
	
	/*----------------------------------
			Ajax Function Actions
	-----------------------------------*/
	public function approveAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			
			//-- Function to Approve Suggestion --//
			if($this->request->getPost("action") == 'approve_post'){
				//-- Make sure user has access --//
				if($this->cap['movement-breaks']['manage']){
					//-- grab / set vars --//
					$postID = $this->request->getPost("theID");
					$reply = $this->request->getPost("theReply");
					$results = array();

					if(is_numeric($postID)){

						//-- Sanitize vars --//
						$postID = $this->filter->sanitize($postID, "int");

						//-- Grab Post --//
						$post = MovementBreaks::findFirst(array(
							"conditions" => "id = :postID:",
							"bind" => array('postID' => $postID)
							));
						$post->post_status = 'publish';

						//-- Save to DB --//
						if($post->save() == false){
						    $results["result"] = "failed";
							$results["message"] = '';
							foreach ($post->getMessages() as $message) {
							    $results["message"].= $message;
							  }
						}else{
							$results["itemID"] = $postID;
							$results["result"] = "success";

							//-- Send "Approved" email notification --//
							if($reply){
								$author = $post->users;
								if($author){
									$to = $author->email;
									$subject = "Movement Break Submission Approved!";
									$message = "After reviewing your submission, we found that the content you posted complies with our movement break content standards.\n You can now login and view your submission in the list of Approved Movement Breaks. We encourage you to continue posting relevant content, and thank you for your submission.\n\nSincerely,\n\n\t- Athlos Tools";
									//-- Send MSG with mailgun --//
									$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
									                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
									                        'to'      => $to,
									                        'subject' => $subject,
									                        'text'    => $message));
									if($result){
										$results["mail"] = "success";
									}else{
										$results["mail"] = "failed";
									}
								}
							}
						}

					}else{
						//invalid input
						$results["result"] = "invalid";
					}

					//-- encode results --//
					echo json_encode($results);
				}
			}
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end approveAction() --//
	
	public function trashAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			
			//-- Function to Approve Suggestion --//
			if($this->request->getPost("action") == 'trash_post'){
				//-- Make sure user has access --//
				if($this->cap['movement-breaks']['manage']){
					//-- grab / set vars --//
					$postID = $this->request->getPost("theID");
					$reason = $this->request->getPost("theReason");
					$results = array();

					if(is_numeric($postID)){

						//-- Sanitize vars --//
						$postID = $this->filter->sanitize($postID, "int");
						if($reason){
							$reason = $this->filter->sanitize($reason, "string");
						}

						//-- Grab Post --//
						$post = MovementBreaks::findFirst(array(
							"conditions" => "id = :postID:",
							"bind" => array('postID' => $postID)
							));
						$post->post_status = 'trash';
						//-- Add Reason --//
						if($reason){
							$post->post_reason = $reason;
						}

						//-- Save to DB --//
						if($post->save() == false){
						    $results["result"] = "failed";
							$results["message"] = '';
							foreach ($post->getMessages() as $message) {
							    $results["message"].= $message;
							  }
						}else{
							$results["itemID"] = $postID;
							$results["result"] = "success";

							//-- Send "Declined" email notification --//
							if($reason){
								$author = $post->users;
								if($author){
									$to = $author->email;
									$subject = "Movement Breaks Submission Declined.";
									$message = "After reviewing your submission, we found that the content posted does not comply with our movement break content standards.\n Included below is a response from a site admin as to why your submission was declined. We encourage you to review the response, make the necessary alterations to your post and then re-submit your changes. Thank you.\n\n\n\nAdmin Response:\n\n".$reason."\n\nSincerely,\n\n\t- Athlos Tools";
									//-- Send MSG with mailgun --//
									$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
									                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
									                        'to'      => $to,
									                        'subject' => $subject,
									                        'text'    => $message));
									if($result){
										$results["mail"] = "success";
									}else{
										$results["mail"] = "failed";
									}
								}
							}
						}

					}else{
						//invalid input
						$results["result"] = "invalid";
					}

					//-- encode results --//
					echo json_encode($results);
				}
			}
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end trashAction() --//
	
	public function deleteAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			
			//-- Function to Delete Suggestion --//
			if($this->request->getPost("action") == 'delete_post'){
				//-- Make sure user has access --//
				if($this->cap['movement-breaks']['manage']){
					//-- grab / set vars --//
					$postID = $this->request->getPost("theID");
					$results = array();

					if(is_numeric($postID)){

						//-- Sanitize vars --//
						$postID = $this->filter->sanitize($postID, "int");

						//-- Grab Post --//
						$post = MovementBreaks::findFirst(array(
							"conditions" => "id = :postID:",
							"bind" => array('postID' => $postID)
							));
						$post->post_status = 'trash';

						//-- Delete from DB --//
						if($post->delete() == false){
						    $results["result"] = "failed";
							$results["message"] = '';
							foreach ($post->getMessages() as $message) {
							    $results["message"].= $message;
							  }
						}else{
							$results["itemID"] = $postID;
							$results["result"] = "success";
						}

					}else{
						//invalid input
						$results["result"] = "invalid";
					}

					//-- encode results --//
					echo json_encode($results);
				}
			}
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end deleteAction() --//
	
	public function editAction()
	{
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- Make sure user has access --//
			if($this->cap['movement-breaks']['manage']){
				
				//-- Function to Grab Suggestion to Edit --//
				if($this->request->getPost("action") == 'grab_post'){
					//-- grab / set vars --//
					$postID = $this->request->getPost("theID");
					$results = array();

					if(is_numeric($postID)){

						//-- Sanitize vars --//
						$postID = $this->filter->sanitize($postID, "int");

						//-- Grab Post --//
						$post = MovementBreaks::findFirst(array(
							"conditions" => "id = :postID:",
							"bind" => array('postID' => $postID)
							));

						//-- the post json object --//
						$results["result"] = "success";
						$results["id"] = $post->id;
						$results["author"] = $post->post_author;
						$results["content"] = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
						$results["title"] = stripslashes(html_entity_decode($post->post_title, ENT_QUOTES));
						$results["type"] = $post->post_type;
						$results["ext_url"] = $post->post_external_url;
						$results["vid_url"] = $post->post_video_url;

					}else{
						//invalid input
						$results["result"] = "failed";
					}

					//-- encode results --//
					echo json_encode($results);
				} // end action = grab_post

				//-- Function to Edit Suggestion --//
				if($this->request->getPost("action") == 'edit_post'){

					//-- Sanitize Inputs --//
					$postID = $this->filter->sanitize($this->request->getPost("theID"), "int");
					$title = htmlentities($this->request->getPost("theTitle"), ENT_QUOTES);
					$content = htmlentities($this->request->getPost("theContent"), ENT_QUOTES);
					$author = $this->filter->sanitize($this->request->getPost("theAuthor"), "int");
					$content_type = $this->filter->sanitize($this->request->getPost("theType"), "int");
					$external_url = $this->request->getPost("theResource");
					$video_url = $this->request->getPost("theVideo");
					$video_image = NULL;

					//-- Anticipate incorrect urls --//
					if($external_url && strpos($external_url, '://') === false){ $external_url = 'http://'.$external_url; }
					if($video_url && strpos($video_url, '://') === false){ $video_url = 'http://'.$video_url; }

					//-- validate data types --//
					$invalid = array();
					if(!is_numeric($author) || strlen($author) > 20){
						$invalid[] = "Invalid Data**!#$**Incorrect Value for Author Indicated. Please try again.";
					}
					if(!is_numeric($content_type) || strlen($content_type) > 5){
						$invalid[] = "Invalid Data**!#$**Incorrect Content Type Indicated. Please try again with a different value.";
					}
					if(isset($external_url) && $external_url){
						if(!filter_var($external_url, FILTER_VALIDATE_URL) || strlen($external_url) > 255){
							$invalid[] = "External Resource Url Invalid or Too Long**!#$**Make sure the URL is valid and starts with 'http://'";
						}
					}
					if(isset($video_url) && $video_url){
						if(!filter_var($video_url, FILTER_VALIDATE_URL) || strlen($video_url) > 255){
							$invalid[] = "Video Url Invalid or Too Long**!#$**Make sure the URL is valid and starts with 'http://'";
						}
					}

					//-- Make sure all required fields are present --//
					if($postID && $title && $content && $author && $content_type){

						//-- if video is being posted make sure video url is present --//
						if($content_type == 1 && !$video_url){
							$invalid[] = "Video Url Required**!#$**When submitting a video post, you have to include a video url.";
						}

						if(empty($invalid)){
							//-- Grab Video Image if present --//
							if($video_url){
								if(strpos($video_url, 'youtube.') || strpos($video_url, 'youtu.be')){
									//-- Handle a youtube video --//
									if(strpos($video_url, 'watch?v=')){
										$videoID = substr($video_url, strripos($video_url, '?v=')+3);
										if(strpos($videoID, '&')){
											$videoID = substr($videoID, 0, strpos($videoID, '&'));
										}
										$video_image = 'http://img.youtube.com/vi/'.$videoID.'/mqdefault.jpg';
										//-- New video url -- force certain format --//
										$video_url = 'http://www.youtube.com/watch?v='.$videoID;
									}else{
										$videoID = substr($video_url, strripos($video_url, '/')+1);
										$video_image = 'http://img.youtube.com/vi/'.$videoID.'/mqdefault.jpg';
										//-- New video url -- force certain format --//
										$video_url = 'http://www.youtube.com/watch?v='.$videoID;
									}
								}else if(strpos($video_url, 'vimeo.')){
									//-- Handle a vimeo video --//
									$videoID = substr($video_url, strripos($video_url, '/')+1);
									$videoID = $this->filter->sanitize($videoID, "int");
									$json = file_get_contents('http://vimeo.com/api/v2/video/'.$videoID.'.json');
									$obj = json_decode($json);
									$video_image = $obj[0]->thumbnail_large;
								}
							}

							//-- Grab Post / Modify values / Enter into DB if no errors --//
							$post = MovementBreaks::findFirst(array(
								"conditions" => "id = :postID:",
								"bind" => array('postID' => $postID)
								));
							$post->post_author = $author;
							$post->post_content = $content;
							$post->post_title = $title;
							$post->post_type = $content_type;
							if($external_url){ $post->post_external_url = $external_url; }
							if($video_url){ $post->post_video_url = $video_url; }
							if($video_image){ $post->post_video_image = $video_image; }

							//-- Save Entry --//
							if($post->save() == false){
								$results["result"] = "failed";
								$results["error_title"] = "Post Update Failed!";
								$results["error_msg"] = "Your suggestion failed to submit... Try Again...";
							}else{
								$results["result"] = "success";
							}

						}else{
							//-- gather the error and display it --//
							if(!empty($invalid[0])){
								$errorParts = explode('**!#$**', $invalid[0]);
								$results["result"] = "failed";
								$results["error_title"] = $errorParts[0];
								$results["error_msg"] = $errorParts[1];
							}
						}

					}else{
						$results["result"] = "failed";
						$results["error_title"] = "Missing Required Fields";
						$results["error_msg"] = "Make sure the post has a title, content and a content type set.";
					}

					//-- encode results --//
					echo json_encode($results);

				} // end action = edit_post
				
			}
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} // end editAction()
	
	public function viewAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			
			//-- Function to View Suggestion --//
			if($this->request->getPost("action") == 'view_post'){
				//-- Make sure user has access --//
				if($this->cap['movement-breaks']['view']){
					//-- grab / set vars --//
					$postID = $this->request->getPost("theID");
					$results = array();

					if(is_numeric($postID)){

						//-- Sanitize vars --//
						$postID = $this->filter->sanitize($postID, "int");

						//-- Grab Post --//
						$post = MovementBreaks::findFirst(array(
							"conditions" => "id = :postID:",
							"bind" => array('postID' => $postID)
							));

						//-- Print out one post --//
						ob_start(); ?>
							<div class="col-md-10 col-sm-6" style="float: none; max-width: 360px;">

								<?php
								//-- Grab author object --//
								$authorName = 'admin';
								$author = $post->users;

								//-- Determine Layout - based on post properties --//
								if(!empty($post->post_video_url)){
									//-- Video / Image Portlets --// ?>

									<div class="thumbnail">
										<?php
										//-- Setup Image to Display --//
										if($post->post_video_image){
											if(getimagesize($post->post_video_image) !== false){
												$post->post_image_url = $post->post_video_image;
											}else{
												$post->post_image_url = '/img/empty-photo_320x180.jpg';
											}
										}else{
											$post->post_image_url = '/img/empty-photo_320x180.jpg';
										}

										//-- Set lightbox Url && Classes --//
										if($post->post_video_url){
											$post->url = $post->post_video_url;
											$lightboxClass = 'ui-lightbox-video';
										}else if($post->post_image_url){
											$post->url = $post->post_image_url;
											$lightboxClass = 'ui-lightbox';
										}

										?>
										<div class="thumbnail-view">
											<a href="<?php echo $post->url; ?>" class="thumbnail-view-hover <?php echo $lightboxClass; ?>"></a>
									        <img src="<?php echo $post->post_image_url; ?>" style="width: 100%" alt="Gallery Image" />
									    </div>
									    <div class="caption">
											<h3 class="curr-display-head"><?php echo stripslashes(html_entity_decode($post->post_title, ENT_QUOTES)); ?></h3>
											<div style="padding-bottom: 12px;">
												<?php //-- Truncate visible chars in content to specified amount --//
												$currContent = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
												//-- Display Content --//
												echo strip_tags($currContent, '<p><a><b><em><i><ul><blockquote><span><u><li><ol>'); ?>
											</div>
											<p class="curr-display-actions">
												<?php
												//-- include button for external link if there is one --//
												if($post->post_external_url){ ?>
													<a href="<?php echo $post->post_external_url; ?>" target="_blank" class="btn btn-secondary btn-sm btn-sm">Resource</a>
												<?php }else if($post->post_video_url){ ?>
													<a href="<?php echo $post->post_video_url; ?>" target="_blank" class="btn btn-secondary btn-sm btn-sm">Video</a>
												<?php } ?>
											</p>
									    </div>
									    <div class="thumbnail-footer">
									    	<div class="pull-left">
									        	<a class="post-author"><i class="fa fa-user"></i>
													&nbsp;<?php if(isset($author->usernm) && $author->usernm){ echo $author->usernm; }else{ echo $authorName; } ?>
												</a>
									        </div>

									        <div class="pull-right">
									        	<a class="post-time"><i class="fa fa-clock-o"></i> <?php echo date('M j, Y', $post->post_time); ?></a>
											</div>
										</div>
									</div>

									<?php
								}else{
									//-- Plain Portlets --// ?>
									<div class="portlet portlet-plain">
										<div class="portlet-header">
											<h3><?php echo stripslashes(html_entity_decode($post->post_title, ENT_QUOTES)); ?></h3>
										</div> <!-- /.portlet-header -->
										<div class="portlet-content">

											<div style="padding-bottom: 12px;">
												<?php //-- Truncate visible chars in content to specified amount --//
												$currContent = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
												//-- Display Content --//
												echo strip_tags($currContent, '<p><a><b><em><i><ul><blockquote><span><u><li><ol>'); ?>
											</div>
											<p class="curr-display-actions">
												<?php
												//-- include button for external link if there is one --//
												if($post->post_external_url){ ?>
													<a href="<?php echo $post->post_external_url; ?>" target="_blank" class="btn btn-secondary btn-sm btn-sm">Resource</a>
												<?php } ?>
											</p>

											<div class="thumbnail-footer">
										    	<div class="pull-left">
										        	<a class="post-author"><i class="fa fa-user"></i>
														&nbsp;<?php if(isset($author->usernm) && $author->usernm){ echo $author->usernm; }else{ echo $authorName; } ?>
													</a>
										        </div>

										        <div class="pull-right">
										        	<a class="post-time"><i class="fa fa-clock-o"></i> <?php echo date('M j, Y', $post->post_time); ?></a>
												</div>
											</div>
										</div> <!-- /.portlet-content -->

									</div> <!-- /.portlet --><?php
								} ?>

							</div> <!-- /.col --><?php

						$results['content'] = ob_get_clean();
						if(!$author){
							$results["author"] = 'Author Does Not Exist';
						}else{
							$results["author"] = $author->fname." ".$author->lname;
						}
						$results["result"] = "success";

					}else{
						//invalid input
						$results["result"] = "failed";
					}

					//-- encode results --//
					echo json_encode($results);
				}
			}
			
		}
		
		//-- Disable View --//
		$this->view->disable();
		
	} //-- end viewAction() --//
	/*---------------------
		End Ajax Actions
	----------------------*/
	
	public function reportAction()
    {
		//-- Data was posted --//
		if($this->request->isPost() == true){
			//-- grab / set vars --//
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$subject = $this->request->getPost("issue_subject");
			$issue = $this->request->getPost("issue_content");
			//-- Sanitize Vars --//
			if($subject){ $subject = $this->filter->sanitize($subject, "string"); }
			if($issue){ $issue = $this->filter->sanitize($issue, "string"); }

			//-- Send "Issue Report" email notification to Super Admins --//
			$to = 'cherdt@athlosacademies.org';
			$subject = "Movement Break Issue: ".$subject;
			$message = "A new issue has been reported in the movement breaks DB.\nIncluded below are the details to the issue as reported by the user. Please review their response as well as take some time to look into the issue being reported.\n\nIssue Details:\n".$issue."\n\nThanks again,\n\n\t- Athlos Tools";
			//-- Send MSG with mailgun --//
			$result = $this->mailgun->sendMessage("mg.athlosacademies.org",
			                  array('from'    => "Athlos Tools <admin@athlosacademies.org>",
			                        'to'      => $to,
			                        'subject' => $subject,
			                        'text'    => $message));
			//-- Success / Error Messages --//
			if($result){
				$this->flashSession->success($preMsg."<strong>Issue reported!</strong> Your report was sent successfully. Thank you.");
			}else{
				$this->flashSession->error($preMsg."<strong>Issue report failed!</strong> The issue was not sent. Please Try Again.");
			}

			return $this->response->redirect("breaks/suggestions");
		}
	}
	
}