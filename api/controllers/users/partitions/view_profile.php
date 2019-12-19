<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class View_profile_controller extends Partition_controller
{
  private $user_service;

  function __construct()
  {
    parent::__construct();

    $this->user_service = new User_service($this->user_id);
    $this->view_profile();
  }

  function view_profile() {
    $this->return_json(
      $this->user_service->get_whole_profile(
        (int)$this->post_body_arg(
          'get_profile_by_user_id'
        )
      )
    );
  }
}

new View_profile_controller()
?>
