<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Report_bug_controller extends Partition_controller
{
  function __construct()
  {
    parent::__construct();

    // $user_service = new User_service($this->user_id);
    // $this->return_json($user_service->delete_account($this->user_id));
  }
}
new Report_bug_controller;
 ?>
