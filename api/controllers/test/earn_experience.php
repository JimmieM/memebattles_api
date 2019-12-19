<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/experience/Experience_Achievements_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/response.php');

class Test_earn_experience extends Controller
{
  function __construct()
  {

    parent::__construct();

    Experience_Achievements_handler::earn(1, 5000);

    $this->return_json($response);

  }
}

new Test_earn_experience;
