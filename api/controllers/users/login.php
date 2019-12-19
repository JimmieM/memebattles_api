<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

/**
 *
 */
class Login_controller extends Controller
{
  private $user_service;

  function __construct()
  {
    parent::__construct(true);

    $this->user_service = new User_service();

    $this->login();
  }

  function login() {
    $this->return_json(
      $this->user_service->validate_login(
        $this->post_body_arg('username'),
        $this->post_body_arg('password')
      )
    );
  }
}

new Login_controller()
?>
