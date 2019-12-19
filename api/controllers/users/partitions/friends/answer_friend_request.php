<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Answer_friend_request extends Partition_controller
{
  private $user_service;
  function __construct()
  {
    parent::__construct();
    
    $this->user_service = new User_service($this->user_id);
    $this->return_json($this->user_service->answer_friend_request((int)$this->post_body_arg('friend_id'), (int)$this->post_body_arg('friend_answer')));
  }
}
new Answer_friend_request();
?>
