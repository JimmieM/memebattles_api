<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/notifications/notification_service.php');

class Test_create_notification extends Controller
{
  function __construct()
  {
    parent::__construct();

    $notification_service = new Notification_service(1);
    $notification_model = new Notification_model(1, 2, 1, "Test Notification");
    $create = $notification_service->create_notification($notification_model);
    $this->return_json($create);
  }
}

new Test_create_notification;
