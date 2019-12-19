<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');

/**
 * Read Response for comments of funcs.
 */
class User_response extends Response
{

  // @Int
  public $user_id;

  // @String
  public $username;

  // @Int
  public $user_battle_wins;

  // @Array<Int>
  public $active_battle_ids;

  // @Int
  public $level;

  // @string
  public $device_token;

  // @string
  public $platform;

  // @Int
  public $experience;

  // @int
  public $experience_range;

  // @String, @String
  public $profile_picture_src;
  public $profile_picture_base64;

  // @String
  public $token;

  // @object of a profile from database
  public $profile;

  // @array of object of achievements
  public $achievements;

  // @array of objects of battles.
  public $battles;

  // @array of notifications
  public $notifications;

  //@array of meme_collections
  public $meme_collection;

  //@Array of users
  public $users;


  /**
   * didSucceedWithUserId
   *
   * @param  mixed $success
   * @param  mixed $user_id
   *
   * @return void
   */
  public function didSucceedWithUserId($success, $user_id)
  {
    parent::didSucceed($success);
    $this->user_id = $user_id;
  }


  /**
   * didSucceedWithUsername
   *
   * @param  mixed $success
   * @param  mixed $username
   *
   * @return void
   */
  public function didSucceedWithUsername($success, $username)
  {
    parent::didSucceed($success);
    $this->username = $username;
  }

  /**
   * didSucceedWithUserBattleWins
   *
   * @param  mixed $success
   * @param  mixed $battle_wins
   *
   * @return void
   */
  public function didSucceedWithUserBattleWins($success, $battle_wins)
  {
    parent::didSucceed($success);
    $this->user_battle_wins = $battle_wins;
  }


  /**
   * didSucceedWithRegister
   *
   * @param  mixed $success
   * @param  mixed $token
   *
   * @return void
   */
  public function didSucceedWithRegister($success, $token, $user_id)
  {
    parent::didSucceed($success);
    $this->token = $token;
    $this->user_id = $user_id;
  }

  /**
   * didSucceedWithGettingMemeCollection
   *
   * @param  mixed $success
   * @param  mixed $meme_collection
   *
   * @return void
   */
  public function didSucceedWithGettingMemeCollection($success, $meme_collection)
  {
    parent::didSucceed($success);
    $this->meme_collection = $meme_collection;
  }

  /**
   * DidSucceedWithGettingActiveBattleIds
   *
   * @param bool $success
   * @param Array<Int> $active_battle_ids
   * @return void
   */
  public function didSucceedWithGettingActiveBattleIds($success, $active_battle_ids)
  {
    parent::didSucceed($success);
    $this->active_battle_ids = $active_battle_ids;
  }

  /**
   * didSucceedWithUsers
   *
   * @param bool $success
   * @param Array $users
   * @return void
   */
  public function didSucceedWithUsers($success, $users)
  {
    parent::didSucceed($success);
    $this->users = $users;
  }

  /**
   * didSucceedToSetNewProfilePicture
   *
   * @param  mixed $success
   * @param  mixed $src
   * @param  mixed $base64
   *
   * @return void
   */
  public function didSucceedToSetNewProfilePicture($success, $src, $base64)
  {
    parent::didSucceed($success);
    $this->profile_picture_src = $src;
    $this->profile_picture_base64 = $base64;
  }

  /**
   * didSucceedToGetProfilePicture
   *
   * @param  mixed $success
   * @param  mixed $base64
   *
   * @return void
   */
  public function didSucceedToGetProfilePicture($success, $base64)
  {
    parent::didSucceed($success);
    $this->profile_picture_base64 = $base64;
  }

  /**
   * didSucceedWithDeviceTokenAndPlatform
   *
   * @param  mixed $success
   * @param  mixed $device_token
   * @param  mixed $platform
   *
   * @return void
   */
  public function didSucceedWithDeviceTokenAndPlatform($success, $device_token, $platform)
  {
    parent::didSucceed($success);
    $this->device_token = $device_token;
    $this->platform = $platform;
  }


  /**
   * didSucceedWithUserWholeProfile
   *
   * @param  mixed $success
   * @param  mixed $profile
   *
   * @return void
   */
  public function didSucceedWithUserWholeProfile($success, $profile)
  {
    $this->success = $success;
    $this->profile = $profile;
  }


  /**
   * didSucceedWithLoginWithTokenAndUserId
   *
   * @param  mixed $success
   * @param  mixed $token
   * @param  mixed $user_id
   *
   * @return void
   */
  public function didSucceedWithLoginWithTokenAndUserId($success, $token, $user_id)
  {
    parent::didSucceed($success);
    $this->token = $token;
    $this->user_id = $user_id;
  }

  /**
   * didSucceedWithCurrentLevel
   *
   * @param  mixed $success
   * @param  mixed $level
   * @param  mixed $experience
   * @param  mixed $experience_range
   *
   * @return void
   */
  public function didSucceedWithCurrentLevel($success, $level, $experience, $experience_range)
  {
    parent::didSucceed($success);
    $this->level = $level;
    $this->experience = $experience;
    $this->experience_range = $experience_range;
  }

  /**
   * didSucceeedWithNotifications
   *
   * @param  mixed $success
   * @param  mixed $notifications
   *
   * @return void
   */
  public function didSucceeedWithNotifications($success, $notifications)
  {
    parent::didSucceed($success);
    $this->notifications = $notifications;
  }


  /**
   * didSucceedWithOpenBattles
   *
   * @param  mixed $success
   * @param  mixed $battles
   *
   * @return void
   */
  public function didSucceedWithOpenBattles($success, $battles)
  {
    parent::didSucceed($success);
    $this->battles = $battles;
  }

  /**
   * didSucceedWithEarnedAchievements
   *
   * @param  mixed $success
   * @param  mixed $achievements
   *
   * @return void
   */
  public function didSucceedWithEarnedAchievements($success, $achievements)
  {
    parent::didSucceed($success);
    $this->achievements = $achievements;
  }
}
