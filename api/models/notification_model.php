<?php
class Notification_model
{
  // @Int
  public $notification_id;
  // @int
  public $notification_user_id;
  // @Int
  public $notification_type;
  // @Int
  public $notification_dynamic_id;
  // @String
  public $notification_message;

  function __construct(int $notification_user_id, int $notification_type, int $notification_dynamic_id, string $notification_message) {
    $this->notification_user_id  = $notification_user_id;
    $this->notification_type = $notification_type;
    $this->notification_dynamic_id = $notification_dynamic_id;
    $this->notification_message = $notification_message;
  }
}
?>