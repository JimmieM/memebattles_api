<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/achievements/battle_achievements_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/response.php');

class Test_earn_contribution_achievements extends Controller
{
  function __construct()
  {
    parent::__construct();

    $response = new Response;

    Battle_Achievements_Handler::contributions(1, $response);

    $this->return_json(
       $response
    );
  }
}

new Test_earn_contribution_achievements;

?>
