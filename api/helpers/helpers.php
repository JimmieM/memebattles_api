<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/db/db_service.php');

class Helpers {

  private $conn;

  /**
   * __construct
   *
   * @return void
   */
  function __construct() {
    date_default_timezone_set('UTC');
    $db = new DB_service();
    $this->conn = $db->get_connection();
  }

  /*
  Guards an array of values and returns false if any are null or undefined.

  @param {params} Array
  @returns ['success' => Boolean, 'params' => []]
  */
  public function guard($params) {

    $succeeded_params = [];
    $has_failed = false;

    for($i = 0; $i < count($params); $i++) {
      if(!$params[$i] || $params[$i] === null) {
        $succeeded_params[] = $params[$i];
        $has_failed = true;
      }
    }

    return ['success' => $has_failed, 'params' => $succeeded_params];
  }


  /**
   * guard_connection
   * Guards an array of values and returns false if any are null or undefined.
  *  Will kill the connection if returns false.
   * @param  mixed $params
   *
   * @die
   */
  public function guard_connection($params) {
    $guard = $this->guard($params);
    if(!$guard['success']) {

      die($this->printjson($guard['params'], true));
    }
  }
  
  /**
   * get_time_between
   *
   * @param  mixed $then
   *
   * @return array
   */
  function get_time_between(string $then) : ?array {
    if(!empty($then)) {
      $start_date = new DateTime($then);
      $since_start = $start_date->diff(new DateTime($this->now()));
      return array(
        "years" => $start_date->y,
        "months" => $since_start->m,
        "days" => $since_start->d,
        "hours" => $since_start->h,
        "minutes" => $since_start->i,
        "seconds" => $since_start->s,
      );
    }
    return null;
  }


  /**
   * get_time_between_as_string
   *
   * @param  mixed $from
   * @param  mixed $to
   * @param  mixed $ending_text
   *
   * @return string
   */
  function get_time_between_as_string(string $from, string $to, string $ending_text, $literal = true) : string {
    $time_ago = strtotime($from);
    $cur_time   = strtotime($to);
    $time_elapsed   = $cur_time - $time_ago;
    $seconds    = $time_elapsed ;
    $minutes    = round($time_elapsed / 60 );
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400 );
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640 );
    $years      = round($time_elapsed / 31207680 );
  
    // Seconds
    if($seconds <= 60){
      return "just now";
    }
    //Minutes
    else if($minutes <=60){
        if($minutes==1){
            return "one minute " . $ending_text;
        }
        else{
            return $minutes ." minutes " . $ending_text;
        }
    }
    //Hours
    else if($hours <=24){
        if($hours==1){
            return "an hour " . $ending_text;
        }else{
            return $hours. " hrs " . $ending_text;
        }
    }
    //Days
    else if($days <= 7){
        if($literal) {
          if($days==1){
            return "yesterday";
          }
        }
        return $days ." days " . $ending_text;
  
    }
    //Weeks
    else if($weeks <= 4.3){
        if($weeks==1){
            return "a week " . $ending_text;
        }else{
            return $weeks . " weeks " . $ending_text;
        }
    }
    //Months
    else if($months <=12){
        if($months==1){
            return "a month " . $ending_text;
        }else{
            return $months . " months " . $ending_text;
        }
    }
    //Years
    else{
        if($years==1){
            return "one year " . $ending_text;
        }else{
            return $years ." years " . $ending_text;
        }
    }
  }

  /**
   * path
   *
   * @param  mixed $path
   *
   * @return void
   */
  function path(string $path) {
    $root = realpath($_SERVER['DOCUMENT_ROOT']);

    return $root . "/api/app/v3/" . $path;
  }

  /**
   * replace_string
   *
   * @param  mixed $value
   * @param  mixed $find
   * @param  mixed $replace
   *
   * @return void
   */
  function replace_string($value, $find = ' ', $replace = '_') {
    return str_replace($find,$replace, $value);
  }

  /**
   * now
   *
   * @param  mixed $minimal
   *
   * @return string
   */
  function now($minimal = false) : string {
    if (!$minimal) {
        return date("Y-m-d H:i:s");
    }
    return date("Y-m-d");
  }

  /**
   * set_time
   *
   * @param  mixed $time '+2 hours', '+10 minutes'
   *
   * @return void
   */
  function set_time($time) {
    // $time can be "+2 hours" or "+2 minutes"
    $hrs = (strtotime($time));

    return date('Y-m-d H:i:s',$hrs);
  }

  /**
   * create_token
   *
   * @param  mixed $length
   *
   * @return void
   */
  function create_token($length = 28) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  // custom function to print JSON array
  /**
   * printjson
   *
   * @param  mixed $string
   * @param  mixed $die
   *
   * @return void
   */
  public function printjson($string, $die = true) {
    header('Content-Type: application/json');
    $json = json_encode($string);
    //echo preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $json);
    echo $json;
    if ($die) {
      die();
    }
  }

  /**
   * escape_string
   *
   * @param  mixed $string
   *
   * @return void
   */
  function escape_string($string) {
    return mysqli_real_escape_string($this->conn, $string);
  }

  /**
   * clean_input
   *
   * @param  mixed $POST
   *
   * @return void
   */
  function clean_input($POST) {
    $POST = mysqli_real_escape_string($this->conn, $_POST[$POST]);
    $POST = strip_tags($POST);

    return $POST;
  }
}
