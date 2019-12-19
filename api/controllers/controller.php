<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
  header("Access-Control-Allow-Origin: *");
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Max-Age: 86400'); // cache for 1 day
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    // may also be using PUT, PATCH, HEAD etc
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

  exit(0);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/json_post.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/settings/appsettings.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');

class Controller
{
  protected $helpers;
  protected $json_post;

  protected $post_data;
  protected $get_data;

  protected $username;
  protected $user_id;
  protected $token;


  function __construct($bypass_token_requirement = false)
  {
    if (Appsettings::$DISPLAY_ERRORS) {
      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ERROR);
    }
    $this->helpers = new Helpers();
    $this->json_post = new Json_post(true);


    // assign the _POST
    $this->post_data = $_POST;
    $this->get_data = $_GET;

    $this->handle_credentials($bypass_token_requirement);
    //$this->post_data = $this->helpers->clean_input($post_body);
  }

  /**
   * validate_token
   *
   * @return bool
   */
  protected function validate_token(): bool
  {
    if ($this->token === null) {
      return false;
    }
    $user_service = new User_service($this->user_id);

    $validate = $user_service->validate_token_incoming($this->token);

    if ($validate->success) {
      return true;
    }
    return false;
  }


  /**
   * return_json
   *
   * @param  mixed $data
   * @param  mixed $kill_connection
   *
   * @return void
   */
  protected function return_json(Response $data, $kill_connection = true)
  {
    //echo json_encode($data);
    $data->getEarnedAchievements();
    if ($data !== null) {
      return $this->helpers->printjson($data, $kill_connection);
    }
  }

  /**
   * handle_credentials
   *
   * @param  mixed $bypass_token_requirement
   *
   * @return void
   */
  private function handle_credentials(bool $bypass_token_requirement)
  {
    $response = new Response;
    $this->username = (string) $_POST['credentials']['username'];
    $this->user_id = (int) $_POST['credentials']['user_id'];
    $this->token = (string) $_POST['credentials']['token'];
    if (!$bypass_token_requirement) {
      if (!$this->validate_token()) {
        $response->didFailWithUserToken();
        // Will kill connection
        $this->return_json($response);
      }
    }
  }

  /**
   * post_body_arg
   *
   * @param  mixed $string
   *
   * @return string
   */
  protected function post_body_arg($string): string
  {
    if ($string !== null) {
      return $this->helpers->clean_input($string);
    }
    return '';
  }

  /**
   * register_connection
   *
   * @return void
   */
  protected function register_connection()
  { }
}
