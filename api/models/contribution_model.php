<?php

class Contribution_model
{
  // @Int
  private $con_id;
  // @Int
  private $con_battle_id;
  // @Base64 String
  private $con_image;
  // @int
  private $con_judge_votes;
  // @String
  private $con_username;
  // @Int
  private $con_user_id;
  // @String
  private $con_date;
  // @Int
  private $con_votes;

 function __construct($con_id, $con_battle_id, $con_image, $con_judge_votes, $con_username, $con_user_id, $con_date)
 {
   $this->con_id = $con_id;
   $this->con_battle_id = $con_battle_id;
   $this->con_image = $con_image;
   $this->con_judge_votes = $con_judge_votes;
   $this->con_username = $con_username;
   $this->con_user_id = $con_user_id;
   $this->con_date = $con_date;
 }

 public function votes($con_id, $con_votes) {
   $this->con_id = $con_id;
 }
}


?>
