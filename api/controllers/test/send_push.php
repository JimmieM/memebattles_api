<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/notifications/notification_service.php');

class Test_send_push extends Controller
{
  function __construct()
  {
    parent::__construct();

    $notification_service = new Notification_service(1);

    $this->return_json(
        $notification_service->create_push_notification(1, "Hello")
    );
  }
}

new Test_send_push;

?>
