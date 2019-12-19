<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/achievements/earned_achievements_container.php');

/*
child classes of this class shall be extended for the purspose
of extending method parameters.

Ex.
If a task is successful and within a "DidSucceed", you'd want to extend the didSucceed or DidFail with external parameters.

DidSucceedWithAVariable(success, theVariable);

This makes the whole response API easier to manage and understand the callbacks from the service components.

Base methods such as:
didFail
didSucceed
didSucceedWithMessage
didFailWithInteger
didFailWithMessage

shall be used when nessecary and not overwritten by a child class..
*/

class Response
{

  // @Boolean
  public $success;
  // @Boolean
  public $hasError;
  // @Int
  public $integer;
  // @String
  public $message;
  // @Boolean
  public $boolean;

  // @Boolean
  public $correct_user_token = true;

  // @Array of achievements
  public $achievements_earned;

  /*
  @param {success} Boolean
  */
  public function didSucceed($success)
  {
    $this->success = $success;
  }

  /*
  @param {success} Boolean
  @param {message} String
  */
  public function didSucceedWithMessage($success, $message)
  {
    $this->success = $success;
    $this->message = $message;
  }

  /*
  @param {success} Boolean
  @param {hasError} Boolean - If a bool shall be sent within the response message. The bool could be
  anything. Ex.

  API:
  Is_user_something() -> Response {
    return new Response()->DidSucceedWithBoolean(true, $db->query("select bool from table"));
  }

  Call:

  // this call shall be aware that the response->boolean shall have the requested variable if Success variable is true.
  $response = Is_user_something()
  if ($response->success) {
    $theBool = $response->boolean
  }
  */
  public function didSucceedWithABoolean($success, $boolean)
  {
    $this->success = $success;
    $this->boolean = $boolean;
  }

  /**
   * didFailWithUserToken
   *
   * @return void
   */
  public function didFailWithUserToken()
  {
    $this->success = false;
    $this->correct_user_token = false;
  }

  /*
  @param {success} Boolean
  @param {integer} - Int
  */
  public function didSucceedWithAnInteger($success, $integer)
  {
    $this->success = $success;
    $this->integer = $integer;
  }

  /*
  @param {success} Boolean
  @param {hasError} - Boolean
  */
  public function didFail($success, $hasError)
  {
    $this->success = $success;
    $this->hasError = $hasError;
  }

  /*
  @param {success} Boolean
  @param {hasError} Boolean - if there was an error. Could in many cases be a task that didnt complete the request
  such as using wrong params etc.
  @param {integer} Int
  */
  public function didFailWithInteger($success, $hasError, $integer)
  {
    $this->success = $success;
    $this->hasError = $hasError;
    $this->integer = $integer;
  }

  /*
  @param {success} Boolean
  @param {hasError} Boolean - if there was an error. Could in many cases be a task that didnt complete the request
  such as using wrong params etc.
  @param {message} String
  */
  public function didFailWithMessage($success, $hasError, $message)
  {
    $this->success = $success;
    $this->hasError = $hasError;
    $this->message = $message;
  }

  /**
   * getEarnedAchievements
   */
  public function getEarnedAchievements()
  {
    $this->achievements_earned = Earned_achievements_container::get();
  }
}
