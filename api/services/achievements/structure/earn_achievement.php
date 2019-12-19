<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/achievements/structure/achievement_definition.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/notifications/notification_service.php');

class earn_achievement extends Achievement_definition
{
  private $requestee_user_id;

  private $helpers;
  private $db;

  private $achievement_id;
  private $current_achievements;
  private $current_achievements_array;

  // @String
  public $achievement_name;

  // @Int
  public $achievement_icon;

  // @String
  public $achievement_description;

  // @Array
  public $achievement_rewards;


  /**
   * __construct
   *
   * @param  mixed $user_id
   * @param  mixed $achievement_id
   *
   * @return void
   */
  function __construct($user_id, $achievement_id) {
    $this->requestee_user_id = $user_id;
    $this->db = new DB_service();
    $this->helpers = new Helpers();
    $this->achievement_id = (string)$achievement_id;
  }

  /**
   * auth_achievement
   * 
   * $earn = if the user should earn it.
   * False, if you just want to check if the user has the achievement, basiacally.
   *
   * @param  mixed $earn
   *
   * @return Array
   */
  public function auth_achievement($earn = true) : Array {

    $return = array();

    $query_string = "SELECT user_achievements FROM users WHERE user_id = $this->requestee_user_id LIMIT 1";
    $query = $this->db->query($query_string);

    if ($query->success) {
      $rows = $this->db->get_row($query->mysqli_query);
    
      $player_achievements = $rows['user_achievements'];

      $this->current_achievements = $player_achievements;
      $this->current_achievements_array = json_decode($player_achievements, true);

      if (!empty($player_achievements)) {
        $player_achievements = json_decode($player_achievements, true);
        // check if user has achievement already!
        
        // doesnt have  it.
        $return['owned'] = false;
        for ($i=0; $i < count($player_achievements); $i++) {
          $return['success'] = false;
          if ($player_achievements[$i]['achievement_id'] === $this->achievement_id) {
            // already has it.
            $return['owned'] = true;
          }
        }

        if (!$return['owned']) {
            if ($earn) {
                // does not have it
                $earn_achi = $this->earn_achievement($this->achievement_id);
                if ($earn_achi['success']) {
                    return $earn_achi;
                } else {
                    $return['success'] = false;
                    $return['error_log'] = $earn['error_log'];
                }
            } else {
              $return['success'] = false;
              $return['owned'] = false;
            }
        }
      } else {
        if ($earn) {
          // user has no achievements, aka not earned.
          $earn_achi = $this->earn_achievement($this->achievement_id);
          if ($earn_achi['success']) {
            return $earn_achi;
          } else {
            $return['success'] = false;
            $return['error_log'] = $earn['error_log'];
          }
        } else {
          $return['success'] = false;
          $return['owned'] = false;
        }
      }
    } else {
      $return['success'] = false;
      $return['error_log'] = 'Failed to find user';
    }
    return $return;
  }

  /**
   * get_reward_as_string
   *
   * @return string
   */
  private function get_reward_as_string() : string {
    $str = "";
    if($this->achievement_rewards !== null || count($this->achievement_rewards) > 0) {
      foreach($this->achievement_rewards as $rewards) {
        foreach ($rewards as $reward_key => $reward_value) {
          $str .= "Earned " . $reward_key . " '". $reward_value ."'";
        }
      } 
    }
    return $str;
  }

  /**
   * achievement_template
   *
   * @param  mixed $achievement_id
   *
   * @return Array
   */
  private function achievement_template($achievement_id) : Array {

    $details = $this->find_achievement($achievement_id);

    if ($details['success']) {

      $this->achievement_name = $details['achievement']['name'];
      $this->achievement_icon = $details['achievement']['icon'];
      $this->achievement_description = $details['achievement']['description'];
      $this->achievement_rewards = $details['achievement']['rewards'];

      $achievement_reward_string = $this->get_reward_as_string();
    
      // return template to insert into mysql && return back to user.
      return array('template' =>
        array(
          'rewards' => $achievement_reward_string,
          'achievement_id' => $achievement_id,
          'earned' => true,
          'date' => $this->helpers->now(),
          'achievement_name' => $details['achievement']['name'],
          'achievement_description' => $details['achievement']['description'],
          'icon' => $details['achievement']['icon']),
        'success' => true);
    } else {
      return array(
        'success' => false,
        'error_log' => $details['error_log']
      );
    }
  }

  
  /**
   * find_achievement
   * 
   * // finds achievement details by {$achievement_id}
   *
   * @param  mixed $achievement_id
   *
   * @return Array
   */
  private function find_achievement($achievement_id) : Array {
    $achievements = $this->return_achievements(false);

    for ($i=0; $i < count($achievements); $i++) {
      if ($achievements[$i]['achievement_id'] === (string)$achievement_id) {
        return array('success' => true, 'achievement' => $achievements[$i]);
      }
    }
    return array('success' => false, 'error_log' => 'Could not find achievement by identifier provided!');
  }


  /**
   * earn_achievement
   *
   * @param  mixed $achievement_id
   *
   * @return Array
   */
  private function earn_achievement($achievement_id) : Array {
    $template = $this->achievement_template($achievement_id);

    // if the template could be created.
    if ($template['success']) {

      $achieved_achievement = $template['template'];

      if (empty($this->current_achievements)) {
        $this->current_achievements_array = array();
      }
      $this->current_achievements_array[] = $achieved_achievement;

      // Create Push Notification!
      $notification_service = new Notification_service($this->requestee_user_id);
      $notification_service->create_push_notification($this->requestee_user_id, "You've earned an achievement!");

      return $this->update_user($this->current_achievements_array, $achieved_achievement);
      
    } else {
      return array(
        'success' => false,
        'error_log' => $template['error_log']
      );
    }
  }

  /**
   * earn_reward
   *
   * @return void
   */
  private function earn_reward() {
    if($this->achievement_rewards !== null || count($this->achievement_rewards) > 0) {
      $user_service = new User_service($this->requestee_user_id);
      foreach($this->achievement_rewards as $rewards)
      {
        foreach ($rewards as $reward_key => $reward_value) {
          // if user earned a title
          if($reward_key === 'title') {
            $earn_title = $user_service->did_earn_user_title($this->requestee_user_id, $reward_value);
            if(!$earn_title->success) {
              Log_Handler::new(1, "earn_reward", "UserId: " . $this->requestee_user_id . " Failed to earn user title");
            }
          }
        }
      }
    }
  }

  /**
   * update_user
   *
   * @param  mixed $template
   *
   * @return Array
   */
  private function update_user($array_of_earned_achievements, $earned_achievement) : Array {
    
    $array_of_earned_achievements = json_encode($array_of_earned_achievements);

    $array_of_earned_achievements = $this->helpers->escape_string($array_of_earned_achievements);

    // array_of_earned_achievements
    $query_string = "UPDATE users SET user_achievements = '$array_of_earned_achievements' WHERE user_id = $this->requestee_user_id";

    $query = $this->db->query($query_string);

    if ($query->success) {
      $this->earn_reward();
      return array('success' => true, 'achievement_template' => $earned_achievement);
    }
    return array('success' => false, 'error_log' => "Error: " . $query->mysqli_error . " Query: " . $query_string);
  }
}


// USAGE: 
// $earn = new earn_achievement(1, '24234');
// $earn->auth_achievement(true);
