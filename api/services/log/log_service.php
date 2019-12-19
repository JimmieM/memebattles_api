<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');

class Log_Handler
{
    private $db;
    private $helpers;

    function __construct()
    {
        $this->helpers = new Helpers();
        $this->db = new DB_service();
    }

    public function new_instance(int $log_level, string $log_subject, string $log_message): bool
    {
        $date = self::$helpers->now();
        $query = "INSERT INTO error_log (log_level, log_subject, log_message, log_date) VALUES ($log_level,'$log_subject', '$log_message', '$date')";

        $log = self::$db->query($query);
        if ($log->success) {
            return true;
        }
        return false;
    }
}
