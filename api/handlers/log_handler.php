<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/log_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/interfaces/isingletonhandler.php');

class Log_Handler implements ISingletonHandler
{
    private static $db;
    private static $helpers;
    private static $initialized = false;

    function __construct()
    {
        self::initialize();
    }

    private static function initialize()
    {
        if (self::$initialized)
            return;

        self::$helpers = new Helpers();
        self::$db = new DB_service();
        self::$initialized = true;
    }

    public static function new(int $log_level, string $log_subject, string $log_message): bool
    {
        self::initialize();

        $date = self::$helpers->now();
        $query = "INSERT INTO error_log (log_level, log_subject, log_message, log_date) VALUES ($log_level,'$log_subject', '$log_message', '$date')";

        $log = self::$db->query($query);
        if ($log->success) {
            return true;
        }
        return false;
    }
}
