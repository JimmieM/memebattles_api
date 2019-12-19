<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class Join_battle_controller extends Controller
{
  private $battle_service;
  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);

    $this->return_json(
      $this->battle_service->create_contribution(
          (int)$this->post_body_arg('battle_id'),
          $this->post_body_arg('image'),
          $this->user_id,
          $this->username
        )
    );
  }
}

new Join_battle_controller();
