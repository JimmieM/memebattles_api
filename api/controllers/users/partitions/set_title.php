<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Set_title_controller extends Partition_controller
{
  function __construct()
  {
    parent::__construct();

    $user_service = new User_service($this->user_id);
    $this->return_json($user_service->change_user_title($this->user_id, $this->post_body_arg("user_title")));
  }
}
new Set_title_controller;
 ?>
