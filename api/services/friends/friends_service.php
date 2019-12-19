<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/chat/chat_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/friends_response.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/helpers/helpers.php');

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/notifications/notification_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/notification_model.php');

class Friends_service
{
  // @Int
  private $requestee_user_id;

  private $db;
  private $helpers;

  function __construct($requestee_user_id)
  {
    $this->requestee_user_id = $requestee_user_id ?? null;
    $this->db = new DB_service();

    $this->helpers = new Helpers();
  }

  /**
   * is_friend
   *
   * @param  mixed $user_id
   *
   * @return bool
   */
  public function is_friend(int $user_id) : bool {
    $query_string =
    "SELECT
    friend_id,
    friend_user_id,
    friend_with_user_id,
    friend_since,
    friend_with_has_accepted
    FROM friends WHERE
    (friend_user_id = $this->requestee_user_id AND friend_with_user_id = $user_id AND friend_with_has_accepted = 1)
    OR
    (friend_with_user_id = $this->requestee_user_id AND friend_user_id = $user_id AND friend_with_has_accepted = 1)";

    $request = $this->db->query($query_string);
      if ($request->success) {
        if($request->rows > 0) {
          return true;
        }
      }
      return false;
  }

  /**
   * get_friend_online_status
   * 
   * If returns True, the user is online. Otherwise - offline.
   *
   * @param  mixed $user_id
   *
   * @return bool
   */
  public function get_friend_online_status(int $user_id) : bool {
    $user_service = new User_service($user_id);
    return $user_service->get_user_online_status($user_id);
  }

  /**
   * get_friend_status
   *
   * @param  mixed $user_id
   *
   * @return string
   */
  public function get_friend_status(int $user_id) : string {
    $friends = $this->get_friends_ids();

    $is_friend = in_array($user_id, $friends->friends_ids);
    $is_pending = in_array($user_id, $friends->pending_friend_ids);

    $friend_status = "";

    if($is_friend) {
      $friend_status = "friend";
    } else if($is_pending) {
      $friend_status = "pending";
    } else {
      $friend_status = "unfriend";
    }
    return $friend_status;
  }
  
  /**
   * accept_friend_request
   *
   * @param  mixed $friend_id
   * @param  int $answer
   *
   * @return Friends_response
   */
  public function answer_friend_request(int $friend_id, int $answer) : Friends_response {
    $response = new Friends_response();

    if(empty($friend_id) || empty($answer)) {
      $response->didFailWithMessage(false, true, "Missing parameter");
      return $response;
    }

    $query_string = "";
    if($answer === 1) {
      $query_string = "UPDATE friends SET friend_with_has_accepted = 1 WHERE friend_id = " . $friend_id;
    } else {
      $query_string = "UPDATE friends SET friend_with_has_declined = 1 WHERE friend_id = " . $friend_id;
    }

    $update_friend_status = $this->db->query($query_string);

    if($update_friend_status->success) {
      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(false, true, "Could not accept friend request!");
    return $response;
  }


  /**
   * get_all_friend_types
   *
   * @return Friends_response
   */
  public function get_all_friend_types() : Friends_response {
    $response = new Friends_response();

    $get_outgoing = $this->get_outgoing_friend_requests();
    $get_incoming = $this->get_incoming_friend_requests();
    $get_friends = $this->get_friends();

    $response->didSucceedWithGettingAllFriendTypes(true, $get_friends, $get_outgoing, $get_incoming);
    return $response;
  }

  /**
   * get_incoming_friend_requests
   *
   * @return Friends_response
   */
  private function get_incoming_friend_requests() : Friends_response {
    $response = new Friends_response();
    $user_service = new User_service($this->requestee_user_id);

    $query_string = "SELECT * FROM friends WHERE friend_with_user_id = " . $this->requestee_user_id . " AND friend_with_has_accepted = 0";
    $query = $this->db->query($query_string);

    if($query->success) {
      $rows = array();
      while($row = mysqli_fetch_array($query->mysqli_query)) {
        $incoming_user_id = $row['friend_user_id'];
        $row['friend_with_user_profile_picture'] = $user_service->get_profile_picture($incoming_user_id)->profile_picture_base64;
        $get_username = $user_service->get_username_by_user_id($incoming_user_id);
        $row['friend_with_username'] = $get_username->username ?? "User";

        $rows[] = $row;
      }
      $response->didSucceedWithGettingIncomingRequests(true, $rows);
      return $response;
    }
    $response->didFailWithMessage(false, false, "No incoming requests!");
    return $response;
  }

  /**
   * get_outgoing_friend_requests
   *
   * @return Friends_response
   */
  private function get_outgoing_friend_requests() : Friends_response {
    $response = new Friends_response();

    $query_string = "SELECT * FROM friends WHERE friend_user_id = " . $this->requestee_user_id . " AND friend_with_has_accepted = 0";
    $query = $this->db->query($query_string);
    if($query->success) {
      $rows = $this->db->get_array($query->mysqli_query);
      $response->didSucceedWithGettingOutgoingRequests(true, $rows);
      return $response;
    }
    $response->didFailWithMessage(false, false, "No outgoing friend requests found. Query: " . $query_string);
    return $response;
  }


  /**
   * get_friends_ids
   *
   * Method is used when calling Get_all_users from User_services. To see whether you're friend with the users you're retrieving.
   *
   * @return Friends_response
   */
  public function get_friends_ids() : Friends_response {
    $response = new Friends_response();

    $query_string =
    "SELECT
    friend_id,
    friend_user_id,
    friend_with_user_id,
    friend_with_has_accepted
    FROM friends WHERE
    (friend_user_id = $this->requestee_user_id)
    OR
    (friend_with_user_id = $this->requestee_user_id)";
    $request = $this->db->query($query_string);

    if($request->success) {
      $rows = $this->db->get_array($request->mysqli_query);
      if(count($rows) > 0) {

        $friends_ids = array(); // holds ids of your accepted friends.
        $pending_friend_ids = array(); // holds ids of users_ids where the request is pending

        foreach ($rows as $row) {
          // If you're the "adder", then the friend is {friend_with_user_id}
          if((int)$row['friend_user_id'] === $this->requestee_user_id) {
            $user_id = $row['friend_with_user_id'];
          } else {
            // otherwise, you're the Friend.
            $user_id = $row['friend_user_id'];
          }
          $has_accepted = (int)$row['friend_with_has_accepted'];

          if($has_accepted === 1) {
            $friends_ids[] = $user_id;
          } else {
            $pending_friend_ids[] = $user_id;
          }
        }
        $response->didSucceedWithGettingFriendIds(true, $friends_ids, $pending_friend_ids);
        return $response;
      }

      $response->didFailWithMessage(false, false, "Could not find any friends!");
      return $response;
    }

    $response->didFailWithMessage(false, true, "Error: " . $request->mysqli_error);
    return $response;
  }

  /**
   * get_friends
   *
   * @return Friends_response
   */
  private function get_friends() : Friends_response {
    $response = new Friends_response();
    $user_service = new User_service($this->requestee_user_id);

    $query_string =
    "SELECT
    friend_id,
    friend_user_id,
    friend_with_user_id,
    friend_since
    FROM friends WHERE
    (friend_user_id = $this->requestee_user_id AND friend_with_has_accepted = 1)
    OR
    (friend_with_user_id = $this->requestee_user_id AND friend_with_has_accepted = 1)";

    $request = $this->db->query($query_string);

      if ($request->success) {
        //$rows = $this->db->get_array($request->mysqli_query);
        $rows = array();

        // loop
        while($row = mysqli_fetch_array($request->mysqli_query)) {

          $user_id = null;
          // If you're the "adder", then the friend is {friend_with_user_id}
          if((int)$row['friend_user_id'] === $this->requestee_user_id) {
            $user_id = $row['friend_with_user_id'];

          } else {
            // otherwise, you're the Friend.
            $user_id = $row['friend_user_id'];
            $row['friend_with_user_id'] = $row['friend_user_id'];
          }

          $row['friend_user_id'] = null;

          $row['friend_with_user_profile_picture'] = $user_service->get_profile_picture($user_id)->profile_picture_base64;
          $get_username = $user_service->get_username_by_user_id($user_id);

          $row['friend_with_username'] = $get_username->username ?? "User";

          $chat_service = new Chat_service($this->requestee_user_id, $user_id);
          $latest_chat_message = $chat_service->get_latest_chat_message();
          $chat_message = "";
          $chat_has_been_seen = 1;
          $chat_date = "";
          if($latest_chat_message->success) {
            $chat_message = $latest_chat_message->chat_message;
            $chat_has_been_seen = $latest_chat_message->chat_message_is_seen;
            $chat_date = $latest_chat_message->chat_message_date;

          } else {
            // If the users has no chats between them
            // get the date of friendship.
            $chat_date = $row['friend_since'];
          }
          $row['friend_latest_chat_message'] = array(
            "message" => $chat_message,
            "has_been_seen" => $chat_has_been_seen,
            "date" => $chat_date,
          );

          $rows[] = $row;

          // destroy chat_service
          unset($chat_service);
        }

        usort($rows, function($a, $b)
        {
          return strcmp($a['friend_latest_chat_message']['date'], $b['friend_latest_chat_message']['date']);
        });


        $response->didSucceedWithGettingFriends(true, $rows);
        return $response;
      }
      $response->didFailWithMessage(false, false, "You have no friends :( ");
      return $response;
  }

  /**
   * Checking whether you're blocked by $user_id.
   *
   * @param  mixed $user_id
   *
   * @return bool
   */
  private function user_has_blocked_you(int $user_id) : bool{
    $user_service = new User_service($this->requestee_user_id);
    $blockers = $user_service->get_users_that_blocked_me();
    if(in_array($user_id, $blockers)) {
      return true;
    }
    return false;
  }

  /**
   * add_friend
   *
   * @param  mixed $user_id
   *
   * @return Friends_response
   */
  public function add_friend(int $user_id) : Friends_response {

    $response = new Friends_response();

    if(empty($user_id) || empty($this->requestee_user_id)) {
      $response->didFailWithMessage(false, false, "Missing parameters");
      return $response;
    }
    if($this->user_has_blocked_you($user_id)) {
      $response->didFailWithMessage(true, false, "Blocked.");
      return $response;
    }

    if ($this->is_friend($user_id)) {
      $response->didFailWithMessage(false, false, "You're already friends with this user.");
      return $response;
    }

    $now = $this->helpers->now();
    $query_string =
    "INSERT INTO friends
    (friend_user_id,
    friend_with_user_id,
    friend_since)
    VALUES
    ($this->requestee_user_id,
    $user_id,
    '$now')";

    $add = $this->db->query($query_string);

    if ($add->success) {
      // create notification
      $user_service = new User_service($this->requestee_user_id);
      $added_by_username = "Someone";
      $get_username = $user_service->get_username_by_user_id($this->requestee_user_id);
      if($get_username->success) {
        $added_by_username = $get_username->username;
      }
      $notification_service = new Notification_service($this->requestee_user_id);
      $model = new Notification_model($user_id, 2, $this->requestee_user_id, $added_by_username . " sent you a friend request!");
      $notification_service->create_notification($model);

      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(false, true, $add->mysqli_error);
    return $response;
  }


  /**
   * remove_friend
   *
   * @param  mixed $user_id
   *
   * @return Friends_response
   */
  public function remove_friend(int $user_id) : Friends_response {
    $response = new Friends_response();

    $query_string = 
    "DELETE FROM friends
    WHERE
    (friend_user_id = $this->requestee_user_id
    AND friend_with_user_id = $user_id)
    OR 
    (friend_user_id = $user_id
    AND friend_with_user_id = $this->requestee_user_id)";

    $remove = $this->db->query($query_string);

    if ($remove->success) {
      $response->didSucceed(true);
      return $response;
    }
    Log_Handler::new(1, "rmeove_friend", "Failed to remove Friend. Query: " . $query_string);
    $response->didFailWithMessage(false, true, $remove->mysqli_error);
    return $response;
  }
}
