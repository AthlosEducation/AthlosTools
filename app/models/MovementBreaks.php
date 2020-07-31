<?php

class MovementBreaks extends \Phalcon\Mvc\Model
{

	public $id;
	public $post_author;
	public $post_content;
	public $post_title;
	public $post_status;
	public $post_type;
	public $post_video_url;
	public $post_video_image;
	public $post_external_url;
	public $post_date;
	public $post_time;
	
	public function initialize()
	{
		$this->belongsTo("post_author", "Users", "id");
	}
	
	public function getSource(){
		return "movement_breaks";
	}
	
}