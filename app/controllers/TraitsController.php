<?php

date_default_timezone_set('UTC');

class TraitsController extends \Phalcon\Mvc\Controller
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
		
		//-- Lock Down to just Admins - *Temporarily* --//
		/*if($this->session->get("user-role") > 1){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Temporarily Locked Down!</strong> If you need access, talk with an administrator until access is restored.");
			//return $this->response->redirect("");
		}*/
		
		//-- Deny Access if no Priveleges --//
		if(!$this->cap['character-curriculum']['view']){
			$preMsg = "<a class='close' data-dismiss='alert' href='#' aria-hidden='true'>×</a>";
			$this->flashSession->warning($preMsg."<strong>Access Denied!</strong> You have insufficient privileges to access that page.");
			return $this->response->redirect("");
		}
		//-- Setup Page Titles --//
		$this->tag->setTitle("Athlos Grading | ");
		//-- Set Navigation Group --//
		$this->view->setVar("navGroup", "Character");
		$this->view->setVar("navPage", "Traits");
		
		//-- Grab Traits Object --//
		$traits = CurriculumTraits::find('');
		//-- Grab Content Types Object --//
		$types = CurriculumType::find('');
		//-- Grab All Group Activities --//
		$activities = Curriculum::query()
			->andWhere("post_type = 3")
			->andWhere("post_status = 'publish'")
			->order("post_title")
			->execute();
		
		//-- Pass Objects to View --//
        $this->view->setVar("traits", $traits);
		$this->view->setVar("types", $types);
		$this->view->setVar("activities", $activities);
	}
	
    public function indexAction()
    {
		//-- Set Current Trait as GRIT --//
		$this->view->setVar("curTrait", 1);
    }

	public function gritAction()
    {
		$this->view->setVar("curTrait", 1);
    }

	public function focusAction()
    {
		$this->view->setVar("curTrait", 2);
    }

	public function optimismAction()
    {
		$this->view->setVar("curTrait", 3);
    }

	public function curiosityAction()
    {
		$this->view->setVar("curTrait", 4);
    }

	public function leadershipAction()
    {
		$this->view->setVar("curTrait", 5);
    }

	public function energyAction()
    {
		$this->view->setVar("curTrait", 6);
    }

	public function courageAction()
    {
		$this->view->setVar("curTrait", 7);
    }

	public function initiativeAction()
    {
		$this->view->setVar("curTrait", 8);
    }

	public function socialAction()
    {
		$this->view->setVar("curTrait", 9);
    }

	public function humilityAction()
    {
		$this->view->setVar("curTrait", 10);
    }

	public function integrityAction()
    {
		$this->view->setVar("curTrait", 11);
    }

	public function creativityAction()
    {
		$this->view->setVar("curTrait", 12);
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
				$trait = $this->request->getPost("theTrait");
				$type = $this->request->getPost("theType");
				$results = array();
				
				//-- Sanitize vars --//
				$curPage = $this->filter->sanitize($curPage, "int");
				$limit = $this->filter->sanitize($limit, "int");
				$trait = $this->filter->sanitize($trait, "int");
				$type = $this->filter->sanitize($type, "int");
				
				if($curPage && $limit && $trait && $type){
					
					//-- Figure out other vars --//
					$offset = ($curPage - 1) * $limit;
					
					//-- Grab Posts --//
					if($type == 3){
						$posts = Curriculum::query()
							->where("post_type = ".$type)
							->andWhere("post_status = 'publish'")
							->order("post_title")
							->limit($limit, $offset)
							->execute();
					}else{
						$posts = Curriculum::query()
							->where("post_trait = ".$trait)
							->andWhere("post_type = ".$type)
							->andWhere("post_status = 'publish'")
							->order("post_title")
							->limit($limit, $offset)
							->execute();
					}
					
					$i = 0;
					ob_start();
					foreach($posts as $post){ ?>
						<div class="col-md-4 col-sm-6" style="max-width: 360px;">

							<?php
							//-- Grab author object --//
							$authorName = 'admin';
							$author = $post->users;
							
							//-- Determine Layout - based on post properties --//
							if($post->post_video_url || $post->post_image_url){
								//-- Video / Image Portlets --// ?>

								<div class="thumbnail">
									<?php
									//-- Setup Image to Display --//
									if(!$post->post_image_url){
										if($post->post_video_image){
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
									}else{
										//-- Make sure image is pulling from https --//
										$post->post_image_url = str_ireplace('http://', 'https://', $post->post_image_url);
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
										<div class="curr-display-content">
											<?php //-- Truncate visible chars in content to specified amount --//
											$currContent = stripslashes(html_entity_decode($post->post_content, ENT_QUOTES));
											//-- Display Content --//
											echo strip_tags($currContent, '<p><a><b><em><i><ul><blockquote><span><u><li><ol>'); ?>
										</div>
										<p class="curr-display-actions">
											<a href="javascript:;" class="btn btn-primary btn-sm viewComplex">View</a>
											<?php
											//-- Open PDF --//
											if($post->post_lesson_path){
												$ext = strtolower(pathinfo($post->post_lesson_path, PATHINFO_EXTENSION));
												$newName = str_ireplace('.'.$ext, '', substr($post->post_lesson_path, strrpos($post->post_lesson_path, '/')+1));
												echo '<a href="/athletic/viewer/?name='.urlencode($newName).'&path='.urlencode('https://s3.amazonaws.com/'.$post->post_lesson_path).'" target="_blank" class="btn btn-secondary btn-sm">Open</a>';
											}
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
											echo strip_tags($currContent, '<p><a><b><em><i><ul><blockquote><span><u><li><ol>'); ?>
										</div>
										<p class="curr-display-actions">
											<a class="btn btn-primary btn-sm btn-sm viewPlain">View</a>
											<?php
											//-- Open PDF --//
											if($post->post_lesson_path){
												$ext = strtolower(pathinfo($post->post_lesson_path, PATHINFO_EXTENSION));
												$newName = str_ireplace('.'.$ext, '', substr($post->post_lesson_path, strrpos($post->post_lesson_path, '/')+1));
												echo '<a href="/athletic/viewer/?name='.urlencode($newName).'&path='.urlencode('https://s3.amazonaws.com/'.$post->post_lesson_path).'" target="_blank" class="btn btn-secondary btn-sm">Open</a>';
											}
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
	
}
