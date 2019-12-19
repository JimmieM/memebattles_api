<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/notifications/notification_service.php');

class Register_notifications extends Partition_controller
{
  private $notification_service;

  function __construct()
  {
    parent::__construct();
    $this->notification_service = new Notification_service($this->user_id);
    $this->return_json($this->notification_service->register_for_notifications(
      $this->post_body_arg('device_token'),
      $this->post_body_arg('platform')
    ));
  }
}

new Register_notifications();
?>
