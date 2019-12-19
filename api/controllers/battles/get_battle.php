<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class Get_battle_controller extends Controller
{
  private $battle_service;
  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);
    $d = $this->battle_service->get_battle((int)$this->post_body_arg("battle_id"));

    $this->return_json($d);
  }
}
new Get_battle_controller();
?>
