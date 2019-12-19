<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/chat_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/notifications/notification_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/images/Image_service.php');

class Chat_service
{
  private $db;
  private $helpers;
  private $user_service;

  private $ACCEPTED_MESSAGE_TYPES = ["image", "text"];
  private $CHAT_TYPE_IMAGE = "image";
  private $CHAT_TYPE_TEXT = "text";

  private $user_id_requestee;
  private $receiving_user_id;

  /**
   * __construct
   *
   * @param  mixed $user_id_requestee - YOU.
   * @param  mixed $receiving_user_id - The one you talk Too.
   *
   * @return void
   */
  function __construct(int $user_id_requestee, int $receiving_user_id)
  {
    $this->user_id_requestee = $user_id_requestee;
    $this->receiving_user_id = $receiving_user_id;

    $this->db = new DB_service();
    $this->helpers = new Helpers();
    $this->user_service = new User_service();
  }

  /**
   * set_chat_messages_as_seen
   *
   * @return Chat_response
   */
  public function set_chat_messages_as_seen(): Chat_response
  {
    $response = new Chat_response();

    if (empty($this->user_id_requestee) && empty($this->receiving_user_id)) {
      $response->didFailWithMessage(false, true, "Missing user IDs between chat");
      return $response;
    }

    $query_string = "UPDATE chat SET chat_seen = 1 WHERE chat_to_user_id = $this->user_id_requestee AND chat_from_user_id = $this->receiving_user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(false, false, "No chats to update!");
    return $response;
  }

  /**
   * new_chat
   *
   * @param  string $message
   * @param  string $chat_type (image, text)
   *
   * @return Chat_response
   */
  public function new_chat(string $message, string $chat_type): Chat_response
  {
    $response = new Chat_response();

    if (!in_array($chat_type, $this->ACCEPTED_MESSAGE_TYPES)) {
      $response->didFailWithMessage(false, false, "Invalid chat type provided! " . $chat_type);
      return $response;
    }

    if (empty($message)) {
      $response->didFailWithMessage(false, false, "No message provided!");
      return $response;
    }

    $now = $this->helpers->now();

    if ($chat_type === "image") {
      // message shall be converted to a filepath
      $Image_service = new Image_service();
      $username_requestee_request = $this->user_service->get_username_by_user_id($this->user_id_requestee);
      if ($username_requestee_request->success) {
        $Image_service->__save_for_chat($username_requestee_request->username);
        $save = $Image_service->save($message);
        if (!$save->success) {
          $response->didFailWithMessage(false, true, $save->message);
          return $response;
        }
        // set message to filepath
        $message = $save->saved_filepath;
      } else {
        $response->didFailWithMessage(false, true, $username_requestee_request->message);
        return $response;
      }
    }

    $query_string = "INSERT INTO chat
      (chat_to_user_id,
      chat_from_user_id,
      chat_message,
      chat_type,
      chat_date)
      VALUES
      ($this->receiving_user_id,
      $this->user_id_requestee,
      '$message',
      '$chat_type',
      '$now')";

    $query = $this->db->query($query_string);

    if ($query->success) {
      $message = "You've recieved a new chat message!";
      $get_username = $this->user_service->get_username_by_user_id($this->user_id_requestee);
      if ($get_username->success) {
        $message = "New message from " . $get_username->username;
      }

      $notification_service = new Notification_service($this->user_id_requestee);

      $notification_service->create_push_notification($this->receiving_user_id, $message);

      $response->didSucceed(true);
      return $response;
    }

    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * get_latest_chat_message
   *
   * @return Chat_response
   */
  public function get_latest_chat_message(): Chat_response
  {
    $response = new Chat_response();

    $select_query = "SELECT chat_message, chat_seen, chat_type, chat_date, chat_from_user_id, chat_to_user_id FROM chat
    WHERE
    (chat_from_user_id = $this->user_id_requestee AND chat_to_user_id = $this->receiving_user_id )
    OR
    (chat_to_user_id = $this->user_id_requestee AND chat_from_user_id = $this->receiving_user_id)
    ORDER BY chat_date DESC LIMIT 1";

    $query = $this->db->query($select_query);
    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);

      $chat_from_u_id = (int) $row['chat_from_user_id'];
      $chat_to_u_id = (int) $row['chat_to_user_id'];
      $chat_type = $row['chat_type'];

      $chat_date = $row['chat_date'];
      // Set as seen to one
      $chat_seen = 1;

      $chat_message = "";
      if ($chat_type === $this->CHAT_TYPE_TEXT) {
        $chat_message = $row['chat_message'];
      } else if ($chat_type === $this->CHAT_TYPE_IMAGE) {
        // If you're the sender.
        if ($chat_from_u_id === $this->user_id_requestee) {
          $chat_message = "You've sent an image";
        } else {
          $chat_message = "You've received an image";
        }
      }

      // Only allow the chat to be colored in UI. IF You're the one the chat is TOO.
      if ($chat_to_u_id === $this->user_id_requestee) {
        $chat_seen = $row['chat_seen'];
      }

      $response->didSucceedWithSingleChatMessage(true, $chat_message, $chat_seen, $chat_date);
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }


  /**
   * get_chats
   *
   * @param  mixed $token_received
   * @param  mixed $amount
   *
   * @return Chat_response
   */
  public function get_chats(string $token_received, int $amount): Chat_response
  {

    $response = new Chat_response();

    $select_query = "SELECT * FROM chat
    WHERE
    (chat_from_user_id = $this->user_id_requestee AND chat_to_user_id = $this->receiving_user_id)
    OR
    (chat_to_user_id = $this->user_id_requestee AND chat_from_user_id = $this->receiving_user_id)
    ORDER BY chat_id ASC
    LIMIT $amount";

    $get_chats_req = $this->db->query($select_query);

    if ($get_chats_req->success) {

      $notifications = 0;


      $chats = array();

      while ($chat = mysqli_fetch_array($get_chats_req->mysqli_query)) {
        $to_user_id = (int) $chats['chat_to_user_id'];

        $chat['chat_date'] = $this->helpers->get_time_between_as_string($chat['chat_date'], $this->helpers->now(), "ago");
        $chat_type = $chat['chat_type'];

        if ($chat_type == $this->CHAT_TYPE_IMAGE) {
          $Image_service = new Image_service();
          $to_base64_request = $Image_service->to_base64($chat['chat_message']);
          if ($to_base64_request->success) {
            $chat['chat_message'] = $to_base64_request->base64_image;
          } else {
            // fallback
            $chat['chat_message'] = $Image_service->to_base64($Image_service->STANDARD_CHAT_IMAGE);
          }
        }

        if ($to_user_id === $this->requestee_user_id) {
          // message has not been seen
          if ((int) $chat['chat_seen'] === 0) {
            $notifications++;
          }
        }
        $chats[] = $chat;
      }

      $this->set_chat_messages_as_seen();
      $response->didSucceedWithChats(true, $chats, $notifications);
      return $response;
    }
    $response->didFailWithMessage(false, $get_chats_req->hasError, $get_chats_req->mysqli_error);
    return $response;
  }

  /**
   * new_chat_token
   *
   * @param  mixed $player1
   * @param  mixed $player2
   *
   * @return void
   */
  private function new_chat_token($player1, $player2)
  {
    $response = new Chat_response();

    // check if chat_inbetweens table has a connection between both players.

    $new_token = $this->new_token();

    $find_connection = $this->db->query("SELECT token FROM chat_betweens
      WHERE
      (player1 = '$player1' AND player2 = '$player2')
      OR
      (player1 = '$player2' AND player2 = '$player1')");



    if ($find_connection->success) {
      $num_rows = $this->db->count_rows($find_connection->mysqli_query);
      if ($num_rows > 0) {

        $update = $this->db->query("UPDATE chat_betweens
          SET token = '$new_token'
          WHERE (player1 = '$player1' AND player2 = '$player2')
          OR
          (player1 = '$player2' AND player2 = '$player1')");

        if ($update->success) {

          $response->didSucceedToGenerateChatToken(true, $new_token);
          return $response;
        } else {
          $response->didFailToGenerateChatToken(false, true, $update->mysqli_error);
          return $response;
        }
      } else {

        $create = $this->db->query("INSERT INTO chat_betweens (player1, player2, token)
        VALUES
        ('$player1', '$player2', '$new_token')");

        if ($create->success) {
          $response->didSucceedToGenerateChatToken(true, $new_token);
          return $response;
        }
        $response->didFailToGenerateChatToken(false, true, $create->mysqli_error);
        return $response;
      }
    }

    $response->didFailToGenerateChatToken(false, true, $find_connection->mysqli_error);
    return $response;
  }

  /*
  @param {player1} - String
  @param {player2} - String
  @param {given_token} - String

  @returns Chat_response.
  */
  public function validate_token($player1, $player2, $given_token)
  {

    $response = new Chat_response();

    $query = $this->db->query("SELECT token FROM chat_betweens
      WHERE
      (player1 = '$player1' AND player2 = '$player2')
      OR
      (player1 = '$player2' AND player2 = '$player1')");

    if ($query->success) {
      $num_rows = $this->db->count_rows($query->mysqli_query);
      if ($num_rows > 0) {

        $row = $this->db->get_row($query->mysqli_query);
        $current_token = $row['token'];

        if ($given_token === $current_token) {

          $response->didSucceedToValidateToken(true, $current_token);
          return $response;
        }
        $response->didFailToValidateToken(false, true, "Failed to validate Token. Token: " . $current_token);
        return $response;
      }
    }
  }

  public function new_token($length = 28)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
}
