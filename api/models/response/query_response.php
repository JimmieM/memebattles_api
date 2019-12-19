<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');

class Query_response extends Response
{

  public $mysqli_error;
  public $mysqli_query;
  public $connection;
  public $rows;


  public function didSucceedWithQuery($success, $mysqli_query, $rows = null, $message = "")
  {
    parent::didSucceed($success);
    $this->message = $message;
    $this->mysqli_query = $mysqli_query;
    $this->rows = $rows;
  }

  public function didSucceedWithQueryWithConnection($success, $mysqli_query, $connection)
  {
    parent::didSucceed($success);
  }

  public function didFailWithMySQLiError($success, $hasError, $mysqli_error)
  {
    parent::didFail($success, $hasError);
    $this->mysqli_error = $mysqli_error;
  }
}
