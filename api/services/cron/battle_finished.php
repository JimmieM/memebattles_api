<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/battles/battle_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/images/Image_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/interfaces/icronservice.php');

class battle_finished_cron implements ICronService
{
  private $helpers;
  private $battle_service;
  // A battle shall have 30 hours after set as Finished.
  private $battle_duration_hours = 30;

  /**
   * __construct
   *
   * @return void
   */
  function __construct()
  {
    $this->helpers = new Helpers();
    $this->battle_service = new Battle_service(1);
  }

  /**
   * collect_battles
   *
   * @return bool
   */
  public function start()
  {
    $battles = $this->battle_service->get_closed_battles_as_finished();

    if ($battles == null) {
      die(json_encode("No closed battles were found"));
    }

    // find all finished battles, where a winner hasnt been selected.

    foreach ($battles as $battle) {
      $battle_until = $battle['battle_until_date'];

      $has_exceeded = $this->battle_service->battle_has_exceeded_time_limit($battle_until);
      if ($has_exceeded) {
        // Pick Winner
        $select_winner = $this->battle_service->select_winner($battle['battle_id']);
        if (!$select_winner->success) {
          Log_Handler::new(2, "Failed to select winner!", "Failed to selected winner: " . $select_winner->message);
          continue;
        }
      } else {
        echo $battle['battle_id'] .  " Has NOT exceeded";
      }
    }
  }
}
