<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/chat/chat_service.php');

class New_chat extends Controller
{
  private $chat_service;

  function __construct()
  {
    parent::__construct();

    $this->chat_service = new Chat_service($this->user_id, (int)$this->post_body_arg('to_user_id'));
    
    $this->return_json(
      $this->chat_service->new_chat((string)$this->post_body_arg('chat_message'), (string)$this->post_body_arg("chat_message_type"))
    );
  }
}

new New_chat();
