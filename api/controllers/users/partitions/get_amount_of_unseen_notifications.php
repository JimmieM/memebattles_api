<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Get_amount_of_unseen_notifications extends Partition_controller
{

  private $user_service;

  function __construct()
  {
    parent::__construct();
    $this->user_service = new User_service($this->user_id);
    $this->return_json($this->user_service->get_amount_of_unseen_notifications());
  }
}

new Get_amount_of_unseen_notifications();
?>
