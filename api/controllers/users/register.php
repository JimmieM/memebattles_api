<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

/**
 *
 */
class Register_controller extends Controller
{
  private $user_service;

  function __construct()
  {
    parent::__construct(true);

    $this->user_service = new User_service();

    $this->register();
  }

  function register() {
    $this->return_json(
      $this->user_service->register_user(
        (string)$this->post_body_arg('email'),
        (string)$this->post_body_arg('username'),
        (string)$this->post_body_arg('password'),
       $this->post_body_arg('profile_picture_base64')
      )
    );
  }
}

new Register_controller()
?>
