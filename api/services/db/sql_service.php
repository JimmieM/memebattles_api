<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/settings/db/appsettings.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');

class SQL_service extends Service
{

  private $TABLES = ['battles', 'battle_contributions', 'blocked_by', 'chat', 'comments', 'contribution_votes', 'error_log', 'friends', 'meme_collections', 'notifications', 'reports', 'titles', 'users'];

  function __construct()
  {
    parent::__construct(0);
  }

  public function export()
  {
    foreach ($this->TABLES as $table) {
      $this->manage_table($table, true);
    }
  }

  public function import()
  {
    foreach ($this->TABLES as $table) {
      $this->manage_table($table, false);
    }
  }

  private function get_backup_file(string $tableName): string
  {
    return Appsettings::$SQL_BACKUP_DIR . '/' . $this->helpers->now(true) . '/' . $tableName . '.sql';
  }

  private function manage_table(string $tableName, bool $export): Response
  {
    $response = new Response();
    $backupFile = $this->get_backup_file($tableName);
    if ($export) {
      $query_string = "SELECT * INTO OUTFILE '$backupFile' FROM $tableName";
    } else {
      $query_string = "LOAD DATA INFILE '$backupFile' INTO TABLE $tableName";
    }
    $query = $this->db->query($query_string);
    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    } else {
      $response->didFailWithMessage(false, true, $query->mysqli_query);
      return $response;
    }
  }
}
