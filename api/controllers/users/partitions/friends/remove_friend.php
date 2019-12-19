<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Remove_friend extends Partition_controller
{

  private $user_service;

  function __construct()
  {
    parent::__construct();
    $this->user_service = new User_service($this->user_id);
    $this->return_json($this->user_service->remove_friend((int)$this->post_body_arg('remove_friend_user_id')));
  }
}

new Remove_friend();
?>
