<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/cron/battle_finished.php');

class Battles_finished_controller extends Partition_controller
{
  function __construct()
  {
    parent::__construct(true);

    $battle_finished = new battle_finished_cron();
    $battle_finished->start();
  }
}
new Battles_finished_controller;
?>
