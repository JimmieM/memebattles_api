<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/achievement_service.php');

class Get_achievements extends Partition_controller
{
  private $achievement_service;
  function __construct()
  {
    parent::__construct();
    
    $this->achievement_service = new Achievement_service(1);
    $this->return_json($this->achievement_service->get_earned_and_unearned_achievements());
  }
}
new Get_achievements();
?>
