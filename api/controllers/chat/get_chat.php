<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/chat/chat_service.php');

class Fetch_chat extends Controller
{
  private $chat_service;

  function __construct()
  {
    parent::__construct();

    $this->chat_service = new Chat_service($this->user_id, (int)$this->post_body_arg('user_id_receiving'));

    $token = $this->post_body_arg('chat_token');
    $amount = (int)$this->post_body_arg('chat_amount');

    $this->return_json(
      $this->chat_service->get_chats($token, $amount)
    );
  }
}

new Fetch_chat();
?>
