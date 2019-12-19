<?php
class Battle_model
{


  // Int
  private $battle_id;

  // @String
  private $battle_type;

  // @String
  private $battle_text;


  // @Int
  private $battle_winner_user_id;

  // @Int (1,0)
  private $battle_private;

  // @Int (fixed at 2 in DB currently.)
  private $battle_versus;

  // @String
  private $battle_judge_username;

  // @Int
  private $battle_judge_user_id;

  // @Int (1,0)
  private $battle_finished;

  // @date
  private $battle_created;

  // @array of contribution_model.php
  private $battle_contributions = array();

  function __construct(
    $battle_id,
    $battle_type,
    $battle_text,
    $battle_winner_username,
    $battle_winner_user_id,
    $battle_private,
    $battle_versus,
    $battle_judge_username,
    $battle_judge_user_id,
    $battle_finished,
    $battle_created,
    $battle_contributions
  ) {
    $this->battle_id = $battle_id;
    $this->battle_type = $battle_type;
    $this->battle_text = $battle_text;
    $this->battle_winner_username = $battle_winner_username;
    $this->battle_winner_user_id = $battle_winner_user_id;
    $this->battle_private = $battle_private;
    $this->battle_versus = $battle_versus;
    $this->battle_judge_username = $battle_judge_username;
    $this->battle_judge_user_id = $battle_judge_user_id;
    $this->battle_finished = $battle_finished;
    $this->battle_created = $battle_created;
    $this->battle_contributions = $battle_contributions;
  }
}
