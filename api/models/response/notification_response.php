<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');
/**
 *
 */
class Notification_response extends Response
{
  // @Array of notifications
  public $notifications;

  // @Int
  public $general_notifications_amount;
  // @Int
  public $chat_notifications_amount;

  public function didSucceeedWithNotifications($success, $notifications)
  {
    parent::didSucceed($success);
    $this->notifications = $notifications;
  }

  public function didSucceedWithAmountOfAllNotifications($success, $general_notifications_amount, $chat_notifications_amount)
  {
    parent::didSucceed($success);
    $this->general_notifications_amount = $general_notifications_amount;
    $this->chat_notifications_amount = $chat_notifications_amount;
  }
}
