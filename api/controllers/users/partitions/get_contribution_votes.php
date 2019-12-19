<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class Get_contribution_votes extends Partition_controller
{
  private $battle_service;
  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);

    $this->return_json($this->battle_service->get_contribution_votes());
  }
}
new Get_contribution_votes;
 ?>
