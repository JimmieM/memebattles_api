<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/partition_controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/notifications/notification_service.php');

class Toggle_notification_seen extends Partition_controller
{
  private $notification_service;

  function __construct()
  {
    parent::__construct();
    $this->notification_service = new Notification_service($this->user_id);
    $this->return_json($this->notification_service->toggle_notification_seen(
      (int)$this->post_body_arg('notification_id'),
      (int)$this->post_body_arg('toggle_notification_seen')
    ));
  }
}

new Toggle_notification_seen();
?>
