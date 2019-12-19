<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');

/**
 * Read Response for comments of funcs.
 */
class Battle_response extends Response
{

  public $closed_battles;

  // @var
  public $finished_battles;

  // @Array
  public $open_battles;

  // @Array dynaic battles
  public $battles;

  // @Array
  public $dynamic_battles;

  // @Obj
  public $battle;

  // @Array
  public $contributions;

  public $contribution;

  // @int
  public $contribution_id;

  // @Int
  public $winner_user_id;

  // @Boolean
  public $request_battle_restart;

  // @String
  public $battle_status;

  /**
   * didSucceedWithClosedBattles
   *
   * @param  mixed $success
   * @param  mixed $closed_battles
   *
   * @return void
   */
  public function didSucceedWithClosedBattles($success, $closed_battles)
  {
    parent::didSucceed($success);
    $this->closed_battles = $closed_battles;
  }


  /**
   * didSucceedWithFinishedBattles
   *
   * @param  mixed $success
   * @param  mixed $finished_battles
   *
   * @return void
   */
  public function didSucceedWithFinishedBattles($success, $finished_battles)
  {
    parent::didSucceed($success);
    $this->finished_battles = $finished_battles;
  }

  /**
   * didSucceedWithFinishedBattles
   *
   * @param  mixed $success
   * @param  mixed $finished_battles
   *
   * @return void
   */
  public function didSucceedWithAllBattles($success, $finished_battles, $open_battles)
  {
    parent::didSucceed($success);
    $this->finished_battles = $finished_battles;
    $this->open_battles = $open_battles;
  }

  /**
   * didSucceedWithContribution
   *
   * @param  mixed $success
   * @param  mixed $contribution
   *
   * @return void
   */
  public function didSucceedWithContribution($success, $contribution)
  {
    parent::didSucceed($success);
    $this->contribution = $contribution;
  }

  /**
   * didSucceedWithBattles
   *
   * @param  mixed $success
   * @param  mixed $finished_battles
   * @param  mixed $open_battles
   *
   * @return void
   */
  public function didSucceedWithBattles($success, $battles)
  {
    parent::didSucceed($success);

    $this->battles = $battles;
  }

  /**
   * didReturnIfUserHasContributed
   *
   * @param  mixed $success
   * @param  mixed $has_contributed
   * @param  mixed $contribution_id
   * @param  mixed $message
   *
   * @return void
   */
  public function didReturnIfUserHasContributed($success, $has_contributed, $contribution_id = null, $message = null)
  {
    parent::didSucceed($success);
    $this->has_contributed = $has_contributed;
    $this->contribution_id = $contribution_id;
    $this->message = $message;
  }

  /**
   * didReturnIfUserHasVoted
   *
   * @param  mixed $success
   * @param  mixed $has_voted
   * @param  mixed $contribution_id
   * @param  mixed $message
   *
   * @return void
   */
  public function didReturnIfUserHasVoted($success, $has_voted, $contribution_id = null, $message = null)
  {
    parent::didSucceed($success);
    $this->has_voted = $has_voted;
    $this->contribution_id = $contribution_id;
    $this->message = $message;
  }


  /**
   * didSucceedWithContributions
   *
   * @param  mixed $success
   * @param  mixed $contributions
   *
   * @return void
   */
  public function didSucceedWithContributions($success, $contributions)
  {
    parent::didSucceed($success);
    $this->contributions = $contributions;
  }

  /**
   * didSucceedWithABattle
   *
   * @param  mixed $success
   * @param  mixed $battle
   *
   * @return void
   */
  public function didSucceedWithABattle($success, $battle)
  {
    parent::didSucceed($success);
    $this->battle = $battle;
  }


  /**
   * didSucceedWithSelectingAWinner
   *
   * @param  mixed $success
   * @param  mixed $winner_user_id
   *
   * @return void
   */
  public function didSucceedWithSelectingAWinner($success, $winner_user_id)
  {
    parent::didSucceed($success);
    $this->winner_user_id = $winner_user_id;
  }


  /**
   * didFailWithSelectingAWinnerWithBattleRestart
   *
   * @param  mixed $success
   * @param  mixed $hasError
   * @param  mixed $request_battle_restart
   *
   * @return void
   */
  public function didFailWithSelectingAWinnerWithBattleRestart($success, $hasError, $request_battle_restart = true)
  {
    parent::didFail($success, $hasError);
    $this->request_battle_restart = $request_battle_restart;
  }

  public function didSucceedWithGettingBattleStatus($success, $status)
  {
    parent::didSucceed($success);
    $this->battle_status = $status;
  }
}
