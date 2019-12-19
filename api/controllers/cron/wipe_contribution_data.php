<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/cron/wipe_contribution_data.php');

class Wipe_contribution_data_controller extends Partition_controller
{
  function __construct()
  {
    parent::__construct(true);

    $battle_finished = new Wipe_contribution_data();
    $battle_finished->start();
  }
}
new Wipe_contribution_data_controller;
?>
