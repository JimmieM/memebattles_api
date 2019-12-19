<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/images/Image_service.php');

class Achievement_model
{
  public $achievement_id;
  public $achievement_category;
  public $achievement_title;
  public $achievement_description;
  public $achievement_icon_src;

  // @whats a reward type? A class heirarchy???
  public $achievement_reward_type;

  public $achievement_sub_root;
  public $achievement_tree_root;

  public $achievement_unlocked;
  public $achievement_earned;



  function __construct($achievement_id, $achievement_category, $achievement_title, $achievement_description, $achievement_icon_src, $achievement_reward_type)
  {
    $this->achievement_id = $achievement_id;
    $this->achievement_category = $achievement_category;
    $this->achievement_title = $achievement_title;
    $this->achievement_description = $achievement_description;
    $this->achievement_icon_src = $achievement_icon_src;
    $this->achievement_reward_type = $achievement_reward_type;
  }

  /*
  @returns base64 String
  */
  public function get_achievement_icon() {
    $this->Image_service = new Image_service();
    return $this->Image_service->to_base64($this->achievement_icon_src);
  }

  /*
  @returns Child class of Achievement_definition
  */
  public function achievement_type_object() {

  }

}

?>
