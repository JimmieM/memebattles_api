<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/battles/battle_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/images/Image_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/interfaces/icronservice.php');

class Wipe_contribution_data implements ICronService {

    private $helpers;
    private $battle_service;

    function __construct()
    {
      $this->helpers = new Helpers();
      $this->battle_service = new Battle_service(1);
    }

    public function start() {

        // get battles where 14 days after finished has passed.
        $finished_battles_request = $this->battle_service->get_finished_battles();
        if($finished_battles_request->success) {
            $finished_battles = $finished_battles_request->finished_battles;
            
            foreach ($finished_battles as $battle) {
                $battle_id = $battle['battle_id'];
                $battle_created = new DateTime($battle['battle_created']);
                $since_creation = $battle_created->diff(new DateTime($this->helpers->now()));
                if($since_creation->d >= 14) {
                    $this->clear_contribution_data($battle_id);
                }
                continue;
            }
        }
    }

    private function clear_contribution_data(int $battle_id) {
        $contributions = $this->battle_service->get_contributions_image_sources($battle_id);
        if($contributions->success) {
          $Image_service = new Image_service();
          foreach($contributions->contributions as $contribution) {
            $contribution_id = $contribution['contribution_id'];
            $contribution_image_source = $contribution['contribution_image'];
            $remove_image = $Image_service->remove_picture($contribution_image_source);
            if(!$remove_image) {
              Log_Handler::new(2, "Failed to remove contribution image", "Tried to remove contribution image of ID: " . $contribution_id);
            }
          }
        } else {
          Log_Handler::new(1, "Failed to get contributions", "Failed to get contributions for battle id: " . $battle_id . " Message: " . $contributions->message);
        }
      
      }
}
