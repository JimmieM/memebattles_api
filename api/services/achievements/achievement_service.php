<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/structure/achievement_definition.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/structure/earn_achievement.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/achievement_response.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/service.php');

class Achievement_service
{

  private $user_id;

  private $achievement_definiton;

  function __construct($user_id)
  {
    $this->user_id = $user_id;
    $this->achievement_definiton = new Achievement_definition($user_id);
  }

  /**
   * get_all_achievements_divied
   *
   * @param  mixed $tree_root
   * @param  mixed $sub_root
   *
   * @return Achievement_response
   */
  function get_all_achievements_divided(string $tree_root, string $sub_root) : Achievement_response{
    $response = new Achievement_response();
    $response->didSucceedWithAchievements(true, $this->achievement_definiton->achievements_sorted($tree_root, $sub_root));
    return $response;
  }


  /**
   * get_all_achievements
   *
   * @return Achievement_response
   */
  function get_all_achievements() : Achievement_response {
    $response = new Achievement_response();
    $response->didSucceedWithAchievements(true, $this->achievement_definiton->return_achievements(false));
    return $response;
  }

  public function get_earned_and_unearned_achievements() : Achievement_response{
    $response = new Achievement_response();

    $earned = $this->get_earned_achievements();
    $un_earned = $this->get_unearned_achievements();

    $response->didSucceedWithEarnedAndUnearnedAchievements(true, $earned, $un_earned);
    return $response;
  }


  /**
   * get_earned_achievements
   *
   * @return Achievement_response
   */
  public function get_earned_achievements() : Achievement_response{
    $response = new Achievement_response();
    $response->didSucceedWithAchievements(true,  $this->achievement_definiton->get_earned_achievements());
    return $response;
  }


  /**
   * get_unearned_achievements
   *
   * @return Achievement_response
   */
  private function get_unearned_achievements() : Achievement_response{
    $response = new Achievement_response();
    $response->didSucceedWithAchievements(true,  $this->achievement_definiton->unearned_achievements());
    return $response;
  }


  /**
   *
   * earn_achievement
   *
   * @param  mixed $achievement_id
   *
   * @return Achievement_response
   */
  function earn_achievement($achievement_id) : Achievement_response {
    $response = new Achievement_response();
    $earn = new earn_achievement($this->user_id, $achievement_id);
    $try_to_earn = $earn->auth_achievement(true);
    if($try_to_earn['success']) {
      $response->didEarnAnAchievement(true, false, $try_to_earn);
      return $response;
    }

    if($try_to_earn['error_log'] !== null) {
      Log_Handler::new(1, "earn_achievement", "Achievement ID: " . $achievement_id, " UserID" . $this->user_id . " Failed to earn Achievement. "  .  $try_to_earn['error_log']);
      $response->didFailWithMessage(false, true, $try_to_earn['error_log']);
    } else {
      $response->didFailWithMessage(false, false, "Not auth to earn achievement.");
    }
    return $response;
  }
}
