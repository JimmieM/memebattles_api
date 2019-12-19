<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Get_friends extends Controller
{
  private $user_service;

  function __construct()
  {
    parent::__construct();
    
    $this->user_service = new User_service($this->user_id);
    
    $this->return_json($this->user_service->get_friends());
  }
}
new Get_friends();
?>
