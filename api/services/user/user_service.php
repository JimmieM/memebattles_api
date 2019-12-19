<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/user_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/images/Image_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/achievements/achievement_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/notifications/notification_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/friends/friends_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/experience/experience_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/achievements/achievement_service.php');

class User_service
{
  private $conn;
  private $db;
  private $helpers;
  private $friends_service;

  // @int - shall be the requesters ID.
  // @Boolean if for example, creating a user and this class has to be used.
  public $requestee_user_id;

  function __construct($requestee_user_id = false)
  {
    $this->db = new DB_service();

    $this->friends_service = new Friends_service($requestee_user_id);
    $this->conn = $this->db->get_connection();

    $this->helpers = new Helpers();

    if ($requestee_user_id !== null) {
      $this->requestee_user_id = $requestee_user_id;
    }

    if (!$requestee_user_id) {
      $this->register_request();
    }
  }

  /**
   * register_request
   *
   * @return void
   */
  private function register_request()
  { }

  /**
   * get_friend_online_status
   * 
   * If returns True, the user is online. Otherwise - offline.
   *
   * @param  mixed $user_id
   *
   * @return bool
   */
  public function get_user_online_status(int $user_id): bool
  {
    $query_string = "SELECT user_latest_online FROM users WHERE user_id = $user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);
      if ($row !== null) {
        $latest_online = new DateTime($row['user_latest_online']);
        $since_now = $latest_online->diff(new DateTime($this->helpers->now()));

        // if the user has been online 2 minutes ago or less.
        $minutes_since = $since_now->i;
        if ($minutes_since < 2) {
          return true;
        }
        return false;
      }
      return false;
    }
    return false;
  }

  /**
   * validate_token_incoming
   *
   * @param  mixed $token
   *
   * @return User_response
   */
  public function validate_token_incoming(string $token): User_response
  {
    $response = new User_response;
    $query_string = "SELECT user_token FROM users WHERE user_id = $this->requestee_user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);
      if ($row !== null) {
        $stored_token = $row['user_token'];
        if ($token == $stored_token) {
          $response->didSucceed(true);
        } else {
          Log_Handler::new(1, "validate_token_incoming", "Incorrect token for userId " . $this->requestee_user_id . " \n\n stored token: " . $stored_token . " \n\n" . "Given token: " . $token);
          $response->didFailWithMessage(false, true, "Incorrect token!");
        }
        return $response;
      }

      Log_Handler::new(1, "validate_token_incoming", "Failed to get rows of user id: " . $this->requestee_user_id);
      $response->didFailWithMessage(false, true, "Could not get rows");
      return $response;
    }
    $response->didFailWithMessage(false, true, "Could not find user!");
    return $response;
  }


  /**
   * get_notifications
   *
   * @param  mixed $by_type
   *
   * @return Response
   */
  public function get_notifications($by_type = null): Response
  {
    $response = new User_response();
    $notification_service = new Notification_service($this->requestee_user_id);

    $notifications_req = $notification_service->fetch_notifications();

    if ($notifications_req->success) {
      $response->didSucceeedWithNotifications(true, $notifications_req->notifications);
      return $response;
    }

    $response->didFailWithMessage(false, true, $notifications_req->message);
    return $response;
  }

  /**
   * clear_notifications
   *
   * @return Notification_response
   */
  public function clear_notifications(): Notification_response
  {
    $notification_service = new Notification_service($this->requestee_user_id);
    return $notification_service->clear_notifications();
  }


  /**
   * Getting all users.
   *
   * @return User_response
   */
  public function get_all_users(): User_response
  {
    $response = new User_response();

    $query_string = "SELECT user_id, user_username, user_profile_picture, user_created, user_level, user_experience, user_created FROM users WHERE user_id NOT LIKE " . $this->requestee_user_id;
    $query = $this->db->query($query_string);

    if ($query->success) {
      $friends_service = new Friends_service($this->requestee_user_id);

      $users = $this->db->get_array($query->mysqli_query);

      for ($i = 0; $i < count($users); $i++) {
        $users[$i]['user_friend_status'] = $friends_service->get_friend_status($users[$i]['user_id']);
      }
      $response->didSucceedWithUsers(true, $users);
      return $response;
    }
    $response->didFailWithMessage(false, true, "Error: " . $query->mysqli_error);
    return $response;
  }

  /**
   * get_profile_picture
   *
   * Will always return $profile_picture_base64
   *
   * @return User_response
   */
  public function get_profile_picture($user_id): User_response
  {
    $response = new User_response();

    if ($user_id === null) {
      if ($this->requestee_user_id === null) {
        Log_Handler::new(1, "get_profile_picture", "No user ID provided for either method call or Class init.");
        $response->didFailWithMessage(false, false, "No user ID provided!");
        return $response;
      }
      $user_id = $this->requestee_user_id;
    }


    $Image_service = new Image_service();
    $query_string = "SELECT user_profile_picture FROM users WHERE user_id = $user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $profile = $this->db->get_row($query->mysqli_query);
      $picture = $profile['user_profile_picture'];
      $base64_image = "";

      $get_base64 = $Image_service->to_base64($picture);
      if ($get_base64->success) {
        $base64_image = $get_base64->base64_image;
      } else {
        $base64_image = $Image_service->get_standard_user_image();
      }

      $response->DidSucceedToGetProfilePicture(true, $base64_image);

      return $response;
    }
    Log_Handler::new(1, "Get_profile_picture", "Failed: " . $query->message . " Query: " . $query_string);
    $backup_picture = $Image_service->get_standard_user_image();
    $response->DidSucceedToGetProfilePicture(true, $backup_picture);
    return $response;
  }

  /**
   * delete_account
   *
   * @param  int $user_id
   *
   * @return User_response
   */
  public function delete_account(int $user_id): User_response
  {
    $response = new User_response;

    $query_string = "DELETE FROM users WHERE user_id = $user_id; 
    DELETE FROM battles WHERE battle_created_by_user_id = $user_id;
    DELETE FROM battle_contributions WHERE contribution_user_id = $user_id;";

    $query = $this->db->query($query_string);
    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * validate_login
   *
   * @param  mixed $username
   * @param  mixed $password
   *
   * @return User_response
   */
  public function validate_login(string $username, string $password): User_response
  {

    $response = new User_response();

    if (empty($username) || empty($password)) {
      $response->didFailWithMessage(false, false, "No username or password provided");
      return $response;
    }

    $query_string = "SELECT user_id, user_password, user_token from users WHERE user_username = '$username'";
    $validate = $this->db->query($query_string);

    if ($validate->success) {
      $profile = $this->db->get_row($validate->mysqli_query);
      // TODO: Apply Token!
      if ($profile !== null) {

        $user_password_encrypted = $profile['user_password'];
        $user_id = $profile['user_id'];
        $stored_token = $profile['user_token'];
        $checked = password_verify($password, $user_password_encrypted);
        if ($checked) {
          $response->didSucceedWithLoginWithTokenAndUserId(true, $stored_token, $user_id); // TODO: Add Token!
          return $response;
        } else {
          $response->didFailWithMessage(false, false, "Wrong password");
          return $response;
        }
      }
      $response->didFailWithMessage(false, false, "Wrong username");
      return $response;
    }
    $response->didFailWithMessage(false, true, "Wrong username or password");
    return $response;
  }

  /**
   * generate_user_token
   *
   * @return void
   */
  private function generate_user_token()
  {
    return $this->helpers->create_token(25);
  }

  /**
   * register_user
   *
   * @param  mixed $email
   * @param  mixed $username
   * @param  mixed $password
   * @param  mixed $profile_picture_base64 - Base64 String.
   *
   * @return User_response
   */
  public function register_user(string $email, string $username, string $password, $profile_picture_base64): User_response
  {
    $response = new User_response();

    // simple edgecase if the user already exists.
    $exist = $this->check_if_user_exists($username);
    if ($exist->boolean) {
      $response->didFailWithMessage(false, false, 'Username is already in use!');
      return $response;
    }

    echo json_encode($exist);
    die();

    $password_encrypted = password_hash($password, PASSWORD_DEFAULT);

    // create the src of the image
    // if no picture provided, then set_new_profile will set base pic
    $set_new_image = $this->set_new_profile_picture($username, $profile_picture_base64);

    $image_src = $set_new_image->saved_filepath;

    $create_token = $this->generate_user_token();

    $now = $this->helpers->now();
    $create = $this->db->query("INSERT INTO users
      (user_email, user_username, user_password, user_profile_picture, user_created, user_token)
      VALUES
      ('$email', '$username', '$password_encrypted', '$image_src', '$now', '$create_token');
    ");

    if ($create->success) {

      $get_user_id_request = $this->get_user_id_by_username($username);
      if ($get_user_id_request->success) {
        // TODO: Add Token!
        $response->didSucceedWithRegister(true, $create_token, $get_user_id_request->integer);
        return $response;
      }
      $response->didFailWithMessage(false, true, "Could not get your user ID");
      return $response;
    }

    $response->didFailWithMessage(false, true, $create->mysqli_error);
    return $response;
  }


  public function get_amount_of_unseen_notifications(): Notification_response
  {
    $notification_service = new Notification_service($this->requestee_user_id);
    return $notification_service->get_amount_of_unseen_notifications();
  }


  /**
   * check_if_user_exists
   *
   * @param  mixed $username
   *
   * @return User_response
   */
  private function check_if_user_exists(string $username): User_response
  {
    $response = new User_response();

    $query = $this->db->query("SELECT user_id FROM users WHERE user_username = '$username'");
    if ($query->success) {
      $num = $this->db->count_rows($query->mysqli_query);
      if ($num > 0) {
        $response->didSucceedWithABoolean(true, true);
        return $response;
      }
      $response->didSucceedWithABoolean(false, false);
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * get_user_id_by_username
   *
   * @param  mixed $username
   *
   * @return User_response
   */
  public function get_user_id_by_username(string $username): User_response
  {
    $response = new User_response();

    if ($username === null || empty($username)) {
      Log_Handler::new(1, "get_user_id_by_username", "Missing Username as parameter.");
      $response->didFailWithMessage(false, false, "Missing Username");
    }

    $query_string = "SELECT user_id FROM users WHERE user_username = '$username'";

    $query = $this->db->query($query_string);
    if ($query->success) {
      $user_object = $this->db->get_row($query->mysqli_query);
      $user_id = $user_object['user_id'];

      if (!empty($user_id)) {
        $response->didSucceedWithAnInteger(true, $user_id);
      } else {
        Log_Handler::new(1, "get_user_id_by_username", "Could not get user_id by username " . $username);
        $response->didFailWithMessage(false, true, "Username missing.");
      }
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * get_username_by_user_id
   *
   * @param  mixed $user_id
   *
   * @return User_response
   */
  public function get_username_by_user_id(int $user_id): User_response
  {
    $response = new User_response();

    if ($user_id === null || !$user_id) {
      Log_Handler::new(1, "get_username_by_userid", "Missing User ID as parameter.");
      $response->didFailWithMessage(false, false, "Missing User ID");
    }

    $query_string = "SELECT user_username FROM users WHERE user_id = $user_id";

    $query = $this->db->query($query_string);
    if ($query->success) {
      $user_object = $this->db->get_row($query->mysqli_query);
      $username = $user_object['user_username'];

      if (!empty($username)) {
        $response->didSucceedWithUsername(true, $username);
      } else {
        Log_Handler::new(1, "get_username_by_user_id", "Could not get Username by User_id: " . $user_id);
        $response->didFailWithMessage(false, true, "Username missing.");
      }
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }


  /**
   * set_new_profile_picture
   *
   * @param  mixed $username
   * @param  mixed $base64
   *
   * @return User_response
   */
  public function set_new_profile_picture(string $username, $base64): Image_service_response
  {
    $Image_service = new Image_service();
    $Image_service->__save_for_userprofile($username);

    // fallback
    if (empty($base64)) {
      $base64 = $Image_service->STANDARD_USER_BASE_IMAGE;
    }
    return $Image_service->save($base64);
  }

  /**
   * get_amount_of_contributions
   *
   * @param  mixed $user_id
   *
   * @return Int
   */
  public function get_amount_of_contributions(?int $user_id): Int
  {
    $search_by_user_id = $user_id;
    if ($user_id === null) {
      $search_by_user_id = $this->requestee_user_id;
    }
    $query_string = "SELECT contribution_id FROM battle_contributions WHERE contribution_user_id = $search_by_user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      return $this->db->count_rows($query->mysqli_query);
    }
    return 0;
  }

  /**
   * did_earn_title
   *
   * @param  mixed $user_id
   * @param  mixed $title_name
   *
   * @return User_response
   */
  public function did_earn_user_title(int $user_id, string $title_name): User_response
  {
    $response = new User_response();
    $now = $this->helpers->now(true);

    if ($this->user_has_title($user_id, $title_name)) {
      $response->didFailWithMessage(false, false, "Already has title");
      return $response;
    }

    $user_titles = $this->get_user_titles($user_id);
    if ($user_titles === null) {
      $response->didFailWithMessage(false, true, "Could not get titles");
      return $response;
    }

    $user_titles[] = array(
      "title" => $title_name,
      "earned_date" => $now
    );
    $encode_titles = json_encode($user_titles);

    $update = $this->db->query("UPDATE users SET user_available_titles = '$encode_titles' WHERE user_id = $user_id");
    if ($update->success) {
      $response->didSucceed(true);
      return $response;
    }

    $response->didFailWithMessage(false, true, "Error: " . $update->mysqli_error);
    return $response;
  }

  /**
   * user_has_title
   *
   * @param  mixed $user_id
   * @param  mixed $title_name
   *
   * @return bool
   */
  private function user_has_title(int $user_id, string $title_name): bool
  {
    $titles = $this->get_user_titles($user_id);

    if (empty($titles) || count($titles) === 0) {
      return false;
    }

    for ($i = 0; $i < count($titles); $i++) {
      if ($titles[$i]['title'] === $title_name) {
        return true;
      }
    }
    return false;
  }

  /**
   * get_user_titles
   *
   * @param  mixed $user_id
   *
   * @return Array
   */
  private function get_user_titles(int $user_id): ?array
  {
    $query_string = "SELECT user_available_titles FROM users WHERE user_id = $user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);
      $titles = $row['user_available_titles'];
      if (empty($titles) || $titles === null) {
        return array();
      }
      return json_decode($titles, true);
    }
    // Return null if select query failed.
    return null;
  }

  /**
   * change_user_title
   *
   * @param  mixed $user_id
   * @param  mixed $title_name
   *
   * @return User_response
   */
  public function change_user_title(int $user_id, string $title_name): User_response
  {
    $response = new User_response();
    $has_title = $this->user_has_title($user_id, $title_name);
    if ($has_title) {
      $update = $this->db->query("UPDATE users SET user_title = '$title_name' WHERE user_id = $user_id");
      if ($update->success) {
        $response->didSucceed(true);
        return $response;
      }
      $response->didFailWithMessage(false, true, $update->mysqli_error);
      return $response;
    }
    $response->didFailWithMessage(false, true, "Sorry! Something happened!");
    return $response;
  }

  /**
   * unblock_user
   *
   * @param  mixed $user_id
   *
   * @return User_response
   */
  public function unblock_user(int $user_id): User_response
  {
    $response = new User_response;
    $query_string = "DELETE FROM blocked_by WHERE block_blocked_user_id = $user_id AND block_blocked_by_user_id = $this->requestee_user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    }
    Log_Handler::new(1, "unblock_user", "Failed to unblock user. Query: " . $query_string);
    $response->didFailWithMessage(false, true, "Failed to unblock user");
    return $response;
  }

  /**
   * block_user
   *
   * @param  mixed $user_id
   *
   * @return void
   */
  public function block_user(int $user_id): User_response
  {
    $response = new User_response();
    $my_blocked_users = $this->get_my_blocked_users();
    $users_blocked_me = $this->get_users_that_blocked_me();
    $now = $this->helpers->now(true);

    if (!in_array($this->requestee_user_id, $my_blocked_users)) {
      $query_string =
        "INSERT INTO
      blocked_by 
      (block_blocked_user_id, block_blocked_by_user_id, block_since)
      VALUES
      ($user_id, $this->requestee_user_id, '$now')";

      $query = $this->db->query($query_string);
      if ($query->success) {
        $remove_friend = $this->friends_service->remove_friend($user_id);
        if ($remove_friend->success) {
          $response->didSucceed(true);
          return $response;
        }
        $response->didFailWithMessage(false, true, "");
        return $response;
      }
      Log_Handler::new(1, "block_user", "Failed to block user_id: " . $user_id . " Requestee user_id: " . $this->requestee_user_id . " Query: " . $query_string);
      $response->didFailWithMessage(false, true, $query->mysqli_error);
      return $response;
    }
  }

  /**
   * get_my_blocked_users
   * 
   * Returns array of user Ids
   *
   * @return Array
   */
  public function get_my_blocked_users(): ?array
  {
    $user_ids = array();

    $query_string = "SELECT 
      block_id,
      block_blocked_user_id,
      block_since
      FROM blocked_by
      WHERE block_blocked_by_user_id = $this->requestee_user_id";

    $query = $this->db->query($query_string);
    if ($query->success) {
      $rows = $this->db->get_array($query->mysqli_query);
      foreach ($rows as $row) {
        $user_ids[] = (int) $row['block_blocked_user_id'];
      }
      return $user_ids;
    }
    return null;
  }

  /**
   * get_users_that_blocked_me
   *
   * @return Array
   */
  public function get_users_that_blocked_me(): ?array
  {
    $user_ids = array();

    $query_string = "SELECT 
      block_id,
      block_blocked_by_user_id,
      block_since 
      from blocked_by
      WHERE block_blocked_user_id,= $this->requestee_user_id";

    $query = $this->db->query($query_string);
    if ($query->success) {
      $rows = $this->db->get_array($query->mysqli_query);
      foreach ($rows as $row) {
        $user_ids[] = $row['block_blocked_by_user_id'];
      }
      return $user_ids;
    }
    return null;
  }

  /**
   * set_new_profile_picture
   *
   * @param  mixed $user_id
   *
   * @return User_response
   */
  public function get_whole_profile(int $user_id): User_response
  {
    $response = new User_response();

    if (empty($user_id) || $user_id === null) {
      $response->didFailWithMessage(false, false, "Missing User ID");
      return $response;
    }

    $query = $this->db->query("SELECT user_id, user_username, user_profile_picture, user_created, user_level, user_experience, user_experience_range, user_created, user_title FROM users WHERE user_id = " . $user_id);
    if ($query->success) {
      try {
        $get_starred_collection = $this->get_meme_collection($user_id, 15, true);

        $row = $this->db->get_row($query->mysqli_query);

        // If you're requesting another users profile
        if ($this->requestee_user_id !== $user_id) {

          $friends_service = new Friends_service($this->requestee_user_id);

          $row['user_is_blocked'] = in_array($user_id, $this->get_my_blocked_users());
          $row['user_friend_status'] = $friends_service->get_friend_status($user_id);
          $row['user_is_you'] = false;
        } else {
          $row['user_available_titles'] = $this->get_user_titles($user_id);
          $row['user_is_you'] = true;
        }

        // collect stats.
        $row['user_stats'] = array(
          "battles_won" => $this->get_amount_of_battle_wins($user_id),
          "contributions" => $this->get_amount_of_contributions($user_id)
        );

        if ($get_starred_collection->success) {
          $row['user_starred_memecollection'] = $get_starred_collection;
        }
        $row['user_profile_picture'] = $this->get_profile_picture($user_id)->profile_picture_base64;
        $row['user_experience_bar'] = (int) $row['user_experience'] / (int) $row['user_experience_range'];

        $row['user_earned_achievements'] = $this->get_earned_achievements($user_id);

        $response->didSucceedWithUserWholeProfile(true, $row);
        return $response;
      } catch (\Exception $e) {
        $response->didFailWithMessage(false, true, "Exception: " . $e);
        return $response;
      }
    }

    $response->didFailWithMessage(false, true, "Error: " . $query->mysqli_error);
    return $response;
  }


  /**
   * get_earned_achievements
   *
   * @param  mixed $user_id
   *
   * @return Achievement_response
   */
  private function get_earned_achievements(int $user_id): ?array
  {
    $achievement_service = new Achievement_service($user_id);
    $get = $achievement_service->get_earned_achievements();
    if ($get->success) {
      return $get->achievements;
    }
    return null;
  }

  /**
   * get_current_level
   *
   * @param  mixed $user_id
   *
   * @return User_response
   */
  public function get_current_level(int $user_id): User_response
  {
    $response = new User_response();

    if ($user_id === null) {
      $message = "No user ID provided";
      Log_Handler::new(1, "get_current_level", $message);
      $response->didFailWithMessage(false, false, $message);
      return $response;
    }
    $experience_service = new Experience_service($user_id);
    return $experience_service->get_current_level();
  }

  /**
   * gain_experience
   *
   * @return User_response
   */
  public function earn_experience(int $amount): User_response
  {
    $experience_service = new Experience_service($this->requestee_user_id);
    return $experience_service->earn_experience($amount);
  }

  /**
   * earn_achievement
   *
   * @param  mixed $achievement_id
   *
   * @return Achievement_response
   */
  public function earn_achievement(int $achievement_id): Achievement_response
  {
    $achievement_service = new Achievement_service($this->requestee_user_id);
    return $achievement_service->earn_achievement($achievement_id);
  }

  /**
   * get_amount_of_battle_wins
   *
   * @return Int
   */
  public function get_amount_of_battle_wins(?int $user_id): Int
  {
    $search_by_user_id = $user_id;
    if ($user_id === null) {
      $search_by_user_id = $this->requestee_user_id;
    }
    $query_string = "SELECT battle_id FROM battles WHERE battle_winner_user_id = $search_by_user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      return $this->db->count_rows($query->mysqli_query);
    }
    return 0;
  }

  /**
   * get_active_battles
   *
   * @param integer $user_id
   * @return void
   */
  public function get_active_battle_ids(int $user_id): User_response
  {
    $response = new User_response();
    $contributions = $this->db->query("SELECT contribution_battle_id FROM battle_contributions WHERE contribution_user_id = $user_id");

    if ($contributions->success) {
      $rows = $this->db->get_array($contributions->mysqli_query);
      $response->didSucceedWithGettingActiveBattleIds(true, $rows);
      return $response;
    }
    $response->didFailWithMessage(false, true, $contributions->mysqli_error);
    return $response;
  }

  /**
   * get_friends
   *
   * @return Friends_response
   */
  public function get_friends(): Friends_response
  {
    return $this->friends_service->get_all_friend_types();
  }


  public function answer_friend_request(int $friend_id, int $answer): Friends_response
  {
    return $this->friends_service->answer_friend_request($friend_id, $answer);
  }


  /**
   * add_friend
   *
   * @param  mixed $user_id
   *
   * @return Friends_response
   */
  public function add_friend(int $user_id): Friends_response
  {
    return $this->friends_service->add_friend($user_id);
  }

  /**
   * Get device token and platform by user_id
   *
   * @param  mixed $user_id
   *
   * @return User_response
   */
  public function get_device_token_and_platform(int $user_id): User_response
  {
    $response = new User_response();

    $query_string = "SELECT user_device_token, user_OS from users WHERE user_id = $user_id";

    $query = $this->db->query($query_string);
    if ($query->success) {
      $row = $this->db->get_row($query->mysqli_query);
      $device_token = $row['user_device_token'];
      $platform = $row['user_OS'];

      if (!empty($device_token) || !empty($platform)) {
        $response->didSucceedWithDeviceTokenAndPlatform(true, $device_token, $platform);
        return $response;
      }
      $response->didFailWithMessage(false, false, "User Id: " . $user_id . " has no Platform and/or Device Token");
      return $response;
    }
    $response->didFailWithMessage(false, false, $query->mysqli_error);
    return $response;
  }

  /**
   * remove_friend
   *
   * @param  mixed $user_id
   *
   * @return Friends_response
   */
  public function remove_friend($user_id): Friends_response
  {
    return $this->friends_service->remove_friend($user_id);
  }

  /**
   * get_meme_collection
   *
   * @param  mixed $user_id
   * @param  mixed $amount
   * @param  mixed $only_get_starred
   *
   * @return User_response
   */
  public function get_meme_collection(int $user_id, int $amount, $only_get_starred = false): User_response
  {
    $response = new User_response();

    if ($user_id === null || $user_id == 0 || $amount == null) {
      $response->didFailWithMessage(false, false, "Missing params");
    }
    $query_string = "SELECT collection_id, collection_image_src, collection_is_starred FROM meme_collections WHERE collection_user_id = $user_id";
    if ($only_get_starred) {
      $query_string .= " AND collection_is_starred = 1";
    }
    $query_string .= " LIMIT $amount";

    $Image_service = new Image_service();

    $query = $this->db->query($query_string);
    if ($query->success) {
      $collection = array();
      $rows = $this->db->get_array($query->mysqli_query);
      $i = 0;
      foreach ($rows as $row) {
        $get_meme = $Image_service->to_base64($row['collection_image_src']);

        $meme = $get_meme->base64_image ?? $Image_service->get_standard_meme_image();

        $row['collection_image_src'] = $meme;
        $collection[$i++] = $row;
      }
      $response->didSucceedWithGettingMemeCollection(true, $collection);
      return $response;
    }
    $response->didFailWithMessage(false, false, "");
    return $response;
  }
}
