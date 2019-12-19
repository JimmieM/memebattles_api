<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/query_response.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/settings/appsettings.php');

class DB_service {

  private $connection = null;
  /**
   * get_connection
   *
   * @return mysqli
   */
  public function get_connection() : mysqli {

    // make it a singleton.
    if($this->connection !== null) {
      return $this->connection;
    }

    $servername = Appsettings::$DB_SERVERNAME;
    $dbname = Appsettings::$DB_DB_NAME;

    if(Appsettings::$IS_PRODUCTION) {
      $username = Appsettings::$DB_PROD_USERNAME;
      $password = Appsettings::$DB_PROD_PASSWORD;
    } else {
      $username = Appsettings::$DB_DEV_USERNAME;
      $password = Appsettings::$DB_DEV_PASSWORD;
    }


    $conn = new mysqli($servername, $username, $password, $dbname);
    if (mysqli_connect_errno()) {exit();}
    $this->connection = $conn;
    return $conn;
  }

  /**
   * close_connection
   *
   * @return void
   */
  public function close_connection() {
    mysqli_close($this->get_connection());
  }


  /**
   * count_rows
   *
   * @param  mixed $res
   *
   * @return Int
   */
  public function count_rows($res) : int {
    $rows = mysqli_num_rows($res);
    if($rows === null) {
      return 0;
    }
    return $rows;
  }


  /**
   * query
   *
   * @param  mixed $qry
   * @param  mixed $unique_connection
   *
   * @return Query_response
   */
  public function query(string $qry, $unique_connection = false) : Query_response {
    $conn = $this->get_connection();
    $query_response = new Query_response();
    $query = mysqli_query($conn, $qry);

    if($query) {

      // If has "SELECT"
      if (strpos($qry, 'SELECT') !== false) {
        $row_count = $this->count_rows($query);
        if($row_count > 0) {
          $query_response->didSucceedWithQuery(true, $query, $row_count);
        } else {
          $query_response->didFailWithMessage(false, false, "No records were found!");
        }
        return $query_response;
      } else {
        $affected = $conn->affected_rows;
        if($affected == 1) {
          if($unique_connection)  {
            $query_response->didSucceedWithQueryWithConnection(true, $query, $conn);
          } else {
            $query_response->didSucceedWithQuery(true, $query);
          }
        } else {
          Log_Handler::new(1, "INSERT/UPDATE Failure", "Failed to affect row. Query: " . $qry);
          $query_response->didFailWithMessage(false, true, "Record was NOT created");
        }
        return $query_response;
      }

      // if($unique_connection) {
      //   $query_response->didSucceedWithQueryWithConnection(true, $query, $conn);
      // } else {
      //   if($row_count > 0) {
      //     $query_response->didSucceedWithQuery(true, $query);
      //   } else {
      //     $query_response->didFailWithMessage(false, false, "No rows found");
      //   }
      //   return $query_response;
      // }
    } else {
      $query_response->didFailWithMySQLiError(false, true, "Error: " . mysqli_error($conn));
    }
    return $query_response;
  }

  /**
   * cast_query_results
   *
   * @param  mixed $rs
   *
   * @return void
   */
  function cast_query_results($rs) {
    $fields = mysqli_fetch_fields($rs);
    $data = array();
    $types = array();
    foreach($fields as $field) {
        switch($field->type) {
            case 3:
                $types[$field->name] = 'int';
                break;
            case 4:
                $types[$field->name] = 'float';
                break;
            default:
                $types[$field->name] = 'string';
                break;
        }
    }
    while($row=mysqli_fetch_assoc($rs)) array_push($data,$row);
    for($i=0;$i<count($data);$i++) {
        foreach($types as $name => $type) {
            settype($data[$i][$name], $type);
        }
    }
    return $data;
  }


  /**
   * get_latest_key_id
   *
   * @param  mixed $conn
   *
   * @return void
   */
  public function get_latest_key_id($conn) {
    if(!$conn) {
      return mysqli_insert_id($this->get_connection());
    }
    return mysqli_insert_id($conn);
  }

  /*
  Returns the value of the latest {col}

  @param {col} - String
  @param {table} - String
  @returns latest {col value} - Dynamic value.
  */
  public function get_latest_row(string $col, string $table) : ?mysqli_result {
    $query = $this->query("SELECT max($col) FROM $table");
    if($query->success) {
      return $this->get_row($query->mysqli_query);
    }
    return null;
  }


  /**
   * get_row
   *
   * @param  mixed $res
   *
   * @return array || null
   */
  public function get_row(mysqli_result $res) : ?array {
    return mysqli_fetch_assoc($res);
  }

  /*
  @param {res} a mysqli_query
  @returns Array of objects from SQL
  */
  public function get_array($res) {
    $arr = array();
    $i = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        $arr[$i++] = $row;
    }
    return $arr;
  }
}
