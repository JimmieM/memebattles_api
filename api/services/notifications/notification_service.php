<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/settings/appsettings.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/notification_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/friends/friends_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '../lib/ApnsPHP/ApnsPHP/Autoload.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/settings/appsettings.php');

class Notification_service
{
  private $db;
  private $helpers;

  // GET SET
  // @Int
  private $user_id;

  /**
   * __construct
   *
   * @param  mixed $requestee_user_id
   *
   * @return void
   */
  function __construct($requestee_user_id)
  {
    $this->user_id = $requestee_user_id;
    $this->db = new DB_service();
    $this->helpers = new Helpers();
  }

  /**
   * toggle_notification_seen
   *
   * @param  mixed $notification_id
   * @param  mixed $toggle_seen
   *
   * @return Notification_response
   */
  public function toggle_notification_seen(int $notification_id, int $toggle_seen): Notification_response
  {
    $response = new Notification_response();
    if ($notification_id == null || $toggle_seen == null) {
      $response->didFailWithMessage(false, false, "Empty params");
      return $response;
    }

    $select_query_string = "SELECT notification_seen FROM notifications WHERE notification_id = $notification_id AND notification_seen = $toggle_seen";
    $select_query = $this->db->query($select_query_string);
    if ($this->db->count_rows($select_query->mysqli_query) === 0) {
      $query_string = "UPDATE notifications SET notification_seen = $toggle_seen WHERE notification_id = $notification_id";
      $query = $this->db->query($query_string);

      if ($query->success) {
        $response->didSucceed(true);
        return $response;
      }
      $error_string = "Error: " . $query->mysqli_error . " Query:" . $query_string;
      Log_Handler::new(1, "toggle_notification_seen", $error_string);
      $response->didFailWithMessage(false, true, $error_string);
      return $response;
    }
    $response->didFailWithMessage(false, false, "");
    return $response;
  }

  /**
   * register_for_notifications
   *
   * @param  mixed $device_token
   *
   * @return Notification_response
   */
  public function register_for_notifications(string $device_token, string $platform): Notification_response
  {
    $response = new Notification_response();
    if (empty($this->user_id) || empty($device_token) || empty($platform)) {
      $response->didFailWithMessage(false, false, "Register for notifications failed!");
      return $response;
    }
    $query_string = "UPDATE users SET user_device_token = '$device_token', user_OS = '$platform' WHERE user_id = $this->user_id";
    $create = $this->db->query($query_string);
    if ($create->success) {

      $response->didSucceed(true);
      return $response;
    }

    Log_Handler::new(1, "register_for_notifications", "Failed to Register for notifications. \n Device Token: " . $device_token . "user ID: " . $this->user_id . " OS: " . $platform);
    $response->didFailWithMessage(false, true, $create->mysqli_error);
    return $response;
  }

  /**
   * clear_notifications
   *
   * @return Notification_response
   */
  public function clear_notifications(): Notification_response
  {
    $response = new Notification_response();

    $query_string = "DELETE FROM notifications where notification_user_id = $this->user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    }
    Log_Handler::new(1, "clear_notifications", "Failed to clear notifications. \n Error; " . $query->mysqli_error . " Query: " . $query_string);
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * create_notification
   *
   * @param  mixed $notification_model
   *
   * @return Notification_response
   */
  public function create_notification(Notification_model $notification_model, $create_push = false): Notification_response
  {
    $response = new Notification_response();
    $now = $this->helpers->now();

    $create = $this->db->query(
      "INSERT INTO
      notifications
      (
      notification_user_id,
      notification_type,
      notification_dynamic_id,
      notification_message,
      notification_date
      )
      VALUES
      (
      $notification_model->notification_user_id,
      $notification_model->notification_type,
      $notification_model->notification_dynamic_id,
      '$notification_model->notification_message',
      '$now')"
    );

    if ($create->success) {
      if ($create_push) {
        $this->create_push_notification($notification_model->notification_user_id, $notification_model->notification_message);
      }

      $response->didSucceed(true);
      return $response;
    }

    Log_Handler::new(1, "create_notification", "Failed to create Notification for UserID: " . $this->user_id . " Error: " . $create->mysqli_error);
    $response->didFailWithMessage(false, true, $create->mysqli_error);
    return $response;
  }


  /**
   * creates a push notification
   *
   * @param  mixed $to_user_id - Defines the user_id of the user you want to create one for.
   * @param  mixed $message
   *
   * @return Void
   */
  public function create_push_notification(int $to_user_id, string $message)
  {

    // Dont send push notifs if test env.??
    if (!Appsettings::IS_PRODUCTION) {
      return;
    }

    $get_user = $this->get_device_token_and_platform($to_user_id);

    if (!$get_user->success) {
      Log_Handler::new(1, "create_push_notif", "" . $get_user->message);
      return;
    }
    $device_token = $get_user->device_token;
    $platform = $get_user->platform;
    if (empty($device_token) || empty($platform)) {
      return;
    }

    if ($platform === 'ios') {
      $this->create_ios_notification($device_token, $message);
    } else if ($platform === 'android') { }
    return;
  }


  private function create_android_notification()
  {
    $apiKey = Appsettings::$ANDROID_PUSH_API_KEY;
    // Replace with the real client registration IDs
    $registrationIDs = array("reg id1", "reg id2");

    // Message to be sent
    $message = "Your message e.g. the title of post";

    // Set POST variables
    $url = 'https://android.googleapis.com/gcm/send';

    $fields = array(
      'registration_ids' => $registrationIDs,
      'data' => array("message" => $message),
    );
    $headers = array(
      'Authorization: key=' . $apiKey,
      'Content-Type: application/json'
    );

    // Open connection
    $ch = curl_init();

    // Set the URL, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields));

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($ch, CURLOPT_POST, true);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    // Execute post
    $result = curl_exec($ch);

    // Close connection
    curl_close($ch);
    // print the result if you really need to print else neglate thi
    echo $result;
    //print_r($result);
    //var_dump($result);
  }


  /**
   * create_ios_notification
   *
   * @param  mixed $device_token
   * @param  mixed $text
   *
   * @return void
   */
  private function create_ios_notification(string $device_token, string $text)
  {
    $certificate_path = $_SERVER['DOCUMENT_ROOT'] . '/api/certificates/memebattles-prod-cert.pem';
    $entrust_root_cert = $_SERVER['DOCUMENT_ROOT'] . '/api/certificates/entrust_root_certification_authority.pem';

    $push = new ApnsPHP_Push(
      ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
      $certificate_path
    );
    // Set the Provider Certificate passphrase
    if (Appsettings::$IS_PRODUCTION) {
      $push->setProviderCertificatePassphrase(Appsettings::$IOS_PUSH_PROD_PASSWORD);
    } else {
      $push->setProviderCertificatePassphrase(Appsettings::$IOS_PUSH_TEST_PASSWORD);
    }

    // Set the Root Certificate Autority to verify the Apple remote peer
    $push->setRootCertificationAuthority($entrust_root_cert);
    // Connect to the Apple Push Notification Service
    $push->connect();
    // Instantiate a new Message with a single recipient
    $message = new ApnsPHP_Message($device_token);
    // Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
    // over a ApnsPHP_Message object retrieved with the getErrors() message.
    $message->setCustomIdentifier("Message-Badge-3");
    // Set badge icon to "3"
    $message->setBadge(1); // TODO: fix badge
    // Set a simple welcome text
    $message->setText($text);
    // Play the default sound
    $message->setSound();
    // Set a custom property
    $message->setCustomProperty('acme2', array('bang', 'whiz'));
    // Set another custom property
    $message->setCustomProperty('acme3', array('bing', 'bong'));
    // Set the expiry value to 30 seconds
    $message->setExpiry(1200);
    // Add the message to the message queue
    $push->add($message);
    // Send all messages in the message queue
    $push->send();
    // Disconnect from the Apple Push Notification Service
    $push->disconnect();
    // // Examine the error message container
    // $aErrorQueue = $push->getErrors();
    // if (!empty($aErrorQueue)) {
    //   var_dump($aErrorQueue);
    // }
  }

  /**
   * get_device_token_and_platform
   *
   * @param  mixed $user_id
   *
   * @return User_response
   */
  private function get_device_token_and_platform(int $user_id): User_response
  {
    $user_service = new User_service();
    return $user_service->get_device_token_and_platform($user_id);
  }

  /**
   * get_amount_of_unseen_chats
   *
   * @return Int
   */
  private function get_amount_of_unseen_chats(): Int
  {
    $friend_service = new Friends_service($this->user_id);

    $get_friend_ids = $friend_service->get_friends_ids();
    if (!$get_friend_ids->success) {
      return 0;
    }
    $friend_ids = join("','", $get_friend_ids->friends_ids);

    $select_query = "SELECT chat_id, chat_from_id FROM chat
    WHERE
    chat_to_user_id = $this->user_id AND chat_seen = 0 AND chat_from_user_id IN ('$friend_ids')";

    $query = $this->db->query($select_query);
    if ($query->success) {
      //$rows = $this->db->get_array($query->mysqli_query);
      return $this->db->count_rows($query->mysqli_query);
    }
    return 0;
  }

  /**
   * get_amount_of_unseen_general_notifications
   *
   * @return Int
   */
  private function get_amount_of_unseen_general_notifications(): Int
  {
    $select_query = "SELECT notification_id FROM notifications
    WHERE
    notification_user_id = $this->user_id AND notification_seen = 0";

    $query = $this->db->query($select_query);
    if ($query->success) {
      return $this->db->count_rows($query->mysqli_query);
    }
    return 0;
  }

  /**
   * get_amount_of_unseen_notifications
   *
   * @return Notification_response
   */
  public function get_amount_of_unseen_notifications(): Notification_response
  {
    $response = new Notification_response();
    $response->didSucceedWithAmountOfAllNotifications(true, $this->get_amount_of_unseen_general_notifications(), $this->get_amount_of_unseen_chats());
    return $response;
  }

  /**
   * fetch_notifications
   *
   * @param  mixed $by_type
   *
   * @return Notification_response
   */
  public function fetch_notifications($by_type = null): Notification_response
  {
    $response = new Notification_response();

    $query_string =
      "SELECT
    notification_id,
    notification_user_id,
    notification_type,
    notification_dynamic_id,
    notification_message,
    notification_seen,
    notification_date
    FROM notifications
    WHERE notification_user_id = $this->user_id
    ORDER BY notification_date DESC";

    if ($by_type !== null) {
      $query_string .=
        " AND
      notification_type = $by_type";
    }

    $query = $this->db->query($query_string);

    if ($query->success) {
      $rows = $this->db->get_array($query->mysqli_query);
      for ($i = 0; $i < count($rows); $i++) {
        $rows[$i]['notification_date_ago'] = $this->helpers->get_time_between_as_string($rows[$i]['notification_date'], $this->helpers->now(), "ago");
      }
      $response->didSucceeedWithNotifications(true, $rows);
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }
}
