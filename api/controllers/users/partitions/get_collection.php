<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Get_collection extends Partition_controller
{

  private $user_service;

  function __construct()
  {
    parent::__construct();
    $this->user_service = new User_service($this->user_id);
    $this->return_json($this->user_service->get_meme_collection((int)$this->post_body_arg("request_user_id"), (int)$this->post_body_arg("amount")));
  }
}

new Get_collection();
?>
