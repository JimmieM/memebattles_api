<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class Get_battles_controller extends Controller
{
  private $battle_service;
  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);
    $this->get_battles();
  }

  function get_battles() {
    $this->return_json($this->battle_service->get_all_battles());
  }
}

// initiate
new Get_battles_controller();
