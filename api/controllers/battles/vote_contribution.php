<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');

class Vote_contribution_controller extends Controller
{
  private $battle_service;

  function __construct()
  {
    parent::__construct();

    $this->battle_service = new Battle_service($this->user_id);

    return $this->return_json(
      $this->battle_service->vote_contribution(
          (int)$this->post_body_arg('contribution_id'),
          (int)$this->post_body_arg('battle_id'),
          (int)$this->user_id
        )
    );
  }
}
new Vote_contribution_controller()
