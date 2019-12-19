<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/achievement_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/experience/experience_achievements.php');

class Experience_service
{
  private $user_id;

  private $db;
  private $user_service;
  private $achievement_service;

  function __construct($user_id)
  {
    $this->user_id = $user_id;
    $this->user_service = new User_service($user_id);
    $this->achievement_service = new Achievement_service($user_id);
    $this->db = new DB_service();
  }

  /**
   * returns Level and Experience.
   *
   * @return User_response
   */
  public function get_current_level() : User_response {
    $response = new User_response();
    $query_string = "SELECT user_level, user_experience, user_experience_range FROM users WHERE user_id = $this->user_id";
    $query = $this->db->query($query_string);
    if($query->success) {

      $row = $this->db->get_row($query->mysqli_query);
      $response->didSucceedWithCurrentLevel(true, $row['user_level'], $row['user_experience'], $row['user_experience_range']);
      return $response;
    }
    $error_msg = "Error: " . $query->mysqli_error . " Query: " . $query_string;
    Log_Handler::new(1, "Get_current_level", $error_msg);
    $response->didFailWithMessage(false, true, $error_msg);
    return $response;
  }

  /**
   * earn_experience
   *
   * @param  mixed $experience_earned
   *
   * @return User_response
   */
  public function earn_experience(int $experience_earned) : User_response {
    $user_response = new User_response();

    $current_stats = $this->get_current_level();

    if(!$current_stats->success) {
      $user_response->didFailWithMessage(false, $current_stats->hasError, "Failed to fetch Current Stats" . $current_stats->message);
      return $user_response;
    }

    $current_experience = $current_stats->experience;

    $gained_experience = $current_experience + $experience_earned;

    $current_level = $current_stats->level;
    $experience_range = $current_stats->experience_range;

    $has_leveled_up = false;
    while ($gained_experience >= $experience_range) {
      $gained_experience += - $experience_range;
      $current_level += 1;
      $experience_range = $experience_range * 1.2;

      $has_leveled_up = true;
    }

    if($has_leveled_up) {
      $query_string = "UPDATE users
      SET user_experience = $gained_experience, user_level = $current_level, user_experience_range = $experience_range
      WHERE user_id = $this->user_id";
    } else {
      $query_string = "UPDATE users
      SET user_experience = $gained_experience, user_experience_range = $experience_range
      WHERE user_id = $this->user_id";
    }

    $query = $this->db->query($query_string);
    if ($query->success) {

      $experience_achievements = new Experience_achievements($this->user_id);
      $earn = $experience_achievements->levels($current_level);

      $user_response->didSucceed(true);
      return $user_response;
    }
    $user_response->didFailWithMessage(false, true, $query->mysqli_error . " Query provided: " . $query_string);
    return $user_response;
  }
}

?>
