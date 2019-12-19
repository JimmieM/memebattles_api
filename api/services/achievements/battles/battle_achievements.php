<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/achievement_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/achievement_response.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');

class Battle_achievements
{
    private $requestee_user_id;
    private $user_service;
    private $achievement_services;

    /**
     * __construct
     *
     * @param  mixed $user_id
     */
    function __construct(int $user_id)
    {
      $this->requestee_user_id = $user_id;

      $this->user_service = new User_service($this->user_id);
      $this->achievement_services = new Achievement_service($user_id);
    }

    /**
     * battle_contributions
     *
     * @return Achievement_response
     */
    public function battle_contributions() : Achievement_response {

      $contributions = $this->get_amount_of_contributions();
      $sorted_achievements_request = $this->achievement_services->get_all_achievements_divided('battles', 'contributions');
      if($sorted_achievements_request->success) {
        $sorted_achievements = $sorted_achievements_request->achievements;
        return $this->earn_achievement($sorted_achievements, $contributions);
      }
      $response = new Achievement_response();
      $response->didFail(false, false);
      return $response;
    }

    /**
     * earn_achievement
     *
     * @param  mixed $sorted_achievements
     * @param  mixed $amount_requirement
     *
     * @return Achievement_response
     */
    private function earn_achievement($sorted_achievements, $amount_requirement) : Achievement_response {
      $response = new Achievement_response();

      for ($i=0; $i < count($sorted_achievements); $i++) {
        if((int)$amount_requirement >= (int)$sorted_achievements[$i]['achievement_requirements']['amount']) {
          $achievement_id = $sorted_achievements[$i]['achievement_id'];
          $earn_request = $this->achievement_services->earn_achievement($achievement_id);
          if($earn_request->success) {
            Earned_achievements_container::add($earn_request);
          }
        }
      }
      $response->didSucceed(true);
      return $response;
    }

    /**
     * get_amount_of_contributions
     *
     * @return Int
     */
    private function get_amount_of_contributions() : Int {
      return $this->user_service->get_amount_of_contributions($this->requestee_user_id);
    }

    /**
     * win_battles
     *
     * @return Achievement_response
     */
    public function win_battles() : Achievement_response {
      $wins = $this->get_amount_of_battle_wins();
      $sorted_achievements_request = $this->achievement_services->get_all_achievements_divided('battles', 'wins');
      if($sorted_achievements_request->success) {
        $sorted_achievements = $sorted_achievements_request->achievements;
        return $this->earn_achievement($sorted_achievements, $wins);
      }
      $response = new Achievement_response();
      $response->didFail(false, false);
      return $response;
    }

    /**
     * get_amount_of_battle_wins
     *
     * @return Int
     */
    private function get_amount_of_battle_wins() : Int {
      return $this->user_service->get_amount_of_battle_wins($this->requestee_user_id);
    }
}
