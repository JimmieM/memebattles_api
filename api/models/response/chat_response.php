<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/response.php');

class Chat_response extends Response
{

  // @String
  public $token;

  // @Int
  public $chat_notifications;

  // @Array of chats
  public $chats;

  // @string
  public $chat_message;
  
  // @int
  public $chat_message_is_seen;

  //@Int
  public $chat_message_date;

  public function didSucceedWithChats($success, $chats, $notifications) {
    parent::didSucceed($success);
    $this->chats = $chats;
    $this->chat_notifications = $notifications;
  }

  // did succeed to validate a token between two players.
  function didSucceedToValidateToken($success, $token) {
    parent::didSucceed($success);
    $this->token = $token;
  }

  // did fail to validate the token between players. Query failure etc.
  function didFailToValidateToken($success, $hasError, $message) {
    parent:didFailWithMessage($success, $hasError, $message);
  }

  function didSucceedToGenerateChatToken($success, $token) {
    parent::didSucceed($success);
    $this->token = $token;
  }

  function didFailToGenerateChatToken($success, $hasError, $message) {
    parent:didFailWithMessage($success, $hasError, $message);
  }

  public function didSucceedWithSingleChatMessage($success, $chat_message, $chat_is_seen, $chat_date) {
    $this->success = $success;
    $this->chat_message = $chat_message;
    $this->chat_message_is_seen = $chat_is_seen;
    $this->chat_message_date = $chat_date;
  }
}

?>
