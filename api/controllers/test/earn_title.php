<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Test_earn_title extends Controller
{
  private $user_service;

  function __construct()
  {
    parent::__construct();

    $this->user_service = new User_service(1);

    $this->return_json(
        $this->user_service->did_earn_user_title(1, "agssddgd")
    );
  }
}

new Test_earn_title;

?>
