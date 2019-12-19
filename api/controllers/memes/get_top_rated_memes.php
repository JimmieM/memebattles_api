<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class Get_top_rated_contributions extends Controller
{
  private $battle_service;
  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);

    $this->return_json($this->battle_service->get_top_rated_contributions((int)$this->post_body_arg("meme_limit")));
  }
}
new Get_top_rated_contributions();
?>
