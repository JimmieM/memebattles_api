<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/achievement_response.php');

class Achievement_definition
{
  private $db;

  private $user_id;

  private $achievements;
  private $tree_roots;

  function __construct($user_id, $divided = false)
  {
    $this->db = new DB_service();
    $this->user_id = $user_id;

    // define the two roots of achievements
    $this->tree_roots = array('battles', 'player');

    // if the achievements should return divided by object.
    // 1 object with achievements regarding 'pets' and another for 'player'
    // else, return all in 1 object.
    $this->achievements = $this->return_achievements($divided);
  }


  /**
   * achievements_sorted
   * 
   * // will return a partion of selected root / sub root as Array
   *
   * @param  mixed $root
   * @param  mixed $subroot
   *
   * @return Array
   */
  public function achievements_sorted($root, $subroot): array
  {
    $this->achievements = $this->return_achievements(true);

    $container = array();

    $partion = $this->achievements['achievements'][$root];

    for ($i = 0; $i < count($partion); $i++) {
      if ($partion[$i]['sub_root'] === $subroot) {
        $container[] = $partion[$i];
      }
    }

    return $container;
  }


  /**
   * get_earned_achievements
   * 
   *
   * @return ?Array
   */
  public function get_earned_achievements()
  {
    $query = $this->db->query("SELECT user_achievements FROM users WHERE user_id = $this->user_id LIMIT 1");
    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);
      $achis = $row['user_achievements'];
      if (!empty($achis)) {
        return json_decode($achis);
      }
      return null;
    }
    return null;
  }


  /** 
   * returns achievements which are not earned by {$user_id}
   *
   *
   * @return Achievement_response
   */
  public function unearned_achievements(): Achievement_response
  {

    $response = new Achievement_response();

    $query = $this->db->query("SELECT user_achievements FROM users WHERE user_id = $this->user_id LIMIT 1");

    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);

      // Get the players achievements.
      $player_achievements = $row['user_achievements'];

      // Get all achievements.
      $all_achievements = $this->return_achievements(false);

      $this->current_achievements = $player_achievements;
      $this->current_achievements_array = json_decode($player_achievements);

      if (!empty($player_achievements)) {
        $player_achievements = json_decode($player_achievements, true);
        // check if user has achievement already!

        $player_ids = array();
        for ($x = 0; $x < count($player_achievements); $x++) {
          $player_ids[] = $player_achievements[$x]['achievement_id'];
        }

        //echo json_encode($player_ids) . "\n\n\n";

        for ($i = 0; $i < count($all_achievements); $i++) {

          foreach ($player_ids as $achievement_id) {

            //echo $all_achievements[$i]['achievement_id'] . "  WITH  " . $achievement_id . " \n\n\n";
            if ($all_achievements[$i]['achievement_id'] === $achievement_id) {
              $all_achievements[$i]['earned'] = true;
            }
          }
        }

        $response->didSucceedWithAchievements(true, $this->divide_tree_root($all_achievements));
        return $response;
      } else {
        // return ALL achievements
        $response->didSucceedWithAchievements(true, $this->return_achievements(true));

        return $response;;
      }
    } else {
      $response->didFailWithMessage(false, true, $query->mysqli_error);
    }


    return $response;
  }

  /*
  divides achievements into tree-roots.
  pets and player.

  divided - boolean
  */
  public function divide_tree_root($achievements): array
  {
    $divided_array = array();
    $this->achievements = $achievements;

    foreach ($this->tree_roots as $root) {
      for ($i = 0; $i < count($this->achievements); $i++) {

        if ($this->achievements[$i]['tree_root'] === $root) {
          $divided_array['achievements'][$root][] = $this->achievements[$i];
        }
      }
    }

    return $divided_array;
  }


  /*
  Divided = boolean
  */
  public function return_achievements($divided)
  {

    /*
    For now, this object defines the achievements of the game.

    Should be moved to MySQL.
    */
    $this->achievements = [
      0 => [
        'name' => 'Getting started',
        'description' => 'Win a meme battle',
        'achievement_requirements' => [
          'amount' => 1
        ],
        'rewards' => [],
        'earned' => false,
        'achievement_id' => '24234',
        'unlocked' => true,
        'icon' => 15,
        'tree_root' => 'battles',
        'sub_root' => 'wins'
      ],
      1 => [
        'name' => "A real contributor indeed!",
        'description' => 'Contribute to 5 battles',
        'achievement_requirements' => [
          'amount' => 5
        ],
        'rewards' => [],
        'earned' => false,
        'achievement_id' => '234',
        'unlocked' => true,
        'icon' => 15,
        'tree_root' => 'battles',
        'sub_root' => 'contributions'
      ],
      2 => [
        'name' => "A challenging combatant",
        'description' => 'Win 5 battles',
        'achievement_requirements' => [
          'amount' => 5,
        ],
        'rewards' => [
          [
            'title' => "Challenger",
          ]
        ],
        'earned' => false,
        'achievement_id' => '21333',
        'unlocked' => true,
        'icon' => 15,
        'tree_root' => 'battles',
        'sub_root' => 'wins'
      ],
      3 => [
        'name' => 'Upgrade!',
        'description' => 'Become level 5',
        'achievement_requirements' => [
          'amount' => 5
        ],
        'rewards' => [],
        'earned' => false,
        'achievement_id' => '22221111',
        'unlocked' => true,
        'icon' => 50,
        'tree_root' => 'player',
        'sub_root' => 'level'
      ],
      4 => [
        'name' => "A real memer",
        'description' => 'Get 50 contribution votes',
        'achievement_requirements' => [
          'amount' => 5000
        ],
        'rewards' => [],
        'earned' => false,
        'achievement_id' => '9198',
        'unlocked' => true,
        'icon' => 30,
        'tree_root' => 'battles',
        'sub_root' => 'contribution_votes'
      ],

      5 => [
        'name' => 'Insane progression',
        'description' => 'Reach level 10',
        'achievement_requirements' => [
          'amount' => 10,
        ],
        'earned' => false,
        'rewards' => [
          [
            'title' => "Player",
          ]
        ],
        'achievement_id' => '238722',
        'unlocked' => true,
        'icon' => 10,
        'tree_root' => 'player',
        'sub_root' => 'level'
      ],
      6 => [
        'name' => 'Sprinting between levels',
        'description' => 'Reach level 25',
        'achievement_requirements' => [
          'amount' => 25,
        ],
        'rewards' => [
          [
            'title' => 'Duelist',
          ]
        ],
        'earned' => false,
        'achievement_id' => '998822',
        'unlocked' => true,
        'icon' => 15,
        'tree_root' => 'player',
        'sub_root' => 'level'
      ],
      7 => [
        'name' => "Becoming insane",
        'description' => 'Reach level 40',
        'achievement_requirements' => [
          'amount' => 40,
        ],
        'rewards' => [
          [
            'title' => 'The Insane',
          ]
        ],
        'earned' => false,
        'achievement_id' => '9982445',
        'unlocked' => true,
        'icon' => 25,
        'tree_root' => 'player',
        'sub_root' => 'level'
      ],
      8 => [
        'name' => "Doing something with your memes",
        'description' => 'Contribute to 15 battles',
        'achievement_requirements' => [
          'amount' => 15
        ],
        'rewards' => [],
        'earned' => false,
        'achievement_id' => '19398833',
        'unlocked' => true,
        'icon' => 15,
        'tree_root' => 'battles',
        'sub_root' => 'contributions'
      ],
      9 => [
        'name' => "Contributor!",
        'description' => 'Contribute to 30 battles',
        'achievement_requirements' => [
          'amount' => 30
        ],
        'rewards' => [
          [
            'title' => 'Contributor',
          ]
        ],
        'earned' => false,
        'achievement_id' => '12397111',
        'unlocked' => true,
        'icon' => 15,
        'tree_root' => 'battles',
        'sub_root' => 'contributions'
      ],
    ];

    if ($divided) {
      return $this->divide_tree_root($this->achievements);
    }
    return $this->achievements;
  }
}
