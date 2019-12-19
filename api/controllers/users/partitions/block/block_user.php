<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Block_user_controller extends Partition_controller
{
  function __construct()
  {
    parent::__construct();

    $user_service = new User_service($this->user_id);
    $this->return_json($user_service->block_user((int)$this->post_body_arg("block_user_id")));
  }
}
new Block_user_controller;
 ?>
