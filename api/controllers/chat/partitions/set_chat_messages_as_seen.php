<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/chat/chat_service.php');

class Set_chat_messages_as_seen extends Controller
{
  private $chat_service;

  function __construct()
  {
    parent::__construct();

    $this->chat_service = new Chat_service($this->user_id, (int)$this->post_body_arg('to_user_id'));

    $this->return_json(
      $this->chat_service->set_chat_messages_as_seen()
    );
  }
}

new set_chat_messages_as_seen();
