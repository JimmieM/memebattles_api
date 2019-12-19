<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class New_battle_controller extends Controller
{
  private $battle_service;
  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);

    $this->return_json(
      $this->battle_service->create_battle(
          $this->post_body_arg('battle_type'),
          $this->post_body_arg('battle_text'),
          (int)$this->post_body_arg('battle_private'),
          $this->post_body_arg('image_base64'),
          $this->username,
          $this->user_id,
          $this->post_body_arg('battle_opponent_user_id'),
          $this->post_body_arg(('battle_tags'))
        )
    );
  }
}

new New_battle_controller()
