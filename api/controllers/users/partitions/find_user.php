<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Find_user extends Partition_controller
{

  private $user_service;

  function __construct()
  {
    parent::__construct();
    $this->user_service = new User_service($this->user_id);
  }
}

new Find_user();
?>
