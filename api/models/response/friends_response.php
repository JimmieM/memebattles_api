<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/response.php');

/**
 * Read Response for comments of funcs.
 */
class Friends_response extends Response
{
  // @Array of friends
  public $friends;

  // @Array of outgoing friend requests.
  public $outgoing_friend_requests;

  // @Array of incoming friend requests.
  public $incoming_friend_requests;

  //@Array of ids of friends.
  public $friends_ids;

  //@Array of ids of pending friend requests from user_ids..
  public $pending_friend_ids;

  /**
   * didSucceedWithGettingFriends
   *
   * @param  mixed $success
   * @param  mixed $friends
   *
   * @return void
   */
  public function didSucceedWithGettingFriends($success, $friends) {
    parent::didSucceed($success);
    $this->friends = $friends;
  }

  /**
   * didSucceedWithGettingOutgoingRequests
   *
   * @param  mixed $success
   * @param  mixed $outgoing_requests
   *
   * @return void
   */
  public function didSucceedWithGettingOutgoingRequests($success, $outgoing_requests) {
    parent::didSucceed($success);
    $this->outgoing_friend_requests = $outgoing_requests;
  }

  /**
   * didSucceedWithGettingIncomingRequests
   *
   * @param  mixed $success
   * @param  mixed $incoming_requests
   *
   * @return void
   */
  public function didSucceedWithGettingIncomingRequests($success, $incoming_requests) {
    parent::didSucceed($success);
    $this->incoming_friend_requests = $incoming_requests;
  }

  /**
   * didSucceedWithGettingAllFriendTypes
   *
   * @param  mixed $success
   * @param  mixed $friends
   * @param  mixed $outgoing_requests
   * @param  mixed $incoming_requests
   *
   * @return void
   */
  public function didSucceedWithGettingAllFriendTypes($success, $friends, $outgoing_requests = null, $incoming_requests = null) {
    parent::didSucceed($success);
    $this->friends = $friends;
    $this->outgoing_friend_requests = $outgoing_requests;
    $this->incoming_friend_requests = $incoming_requests;
  }

  /**
   * didSucceedWithGettingFriendIds
   *
   * @param  mixed $success
   * @param  mixed $friend_ids
   *
   * @return void
   */
  public function didSucceedWithGettingFriendIds($success, $friend_ids, $pending_friend_ids)
  {
    parent::didSucceed($success);
    $this->friends_ids = $friend_ids;
    $this->pending_friend_ids = $pending_friend_ids;
  }
}
?>
