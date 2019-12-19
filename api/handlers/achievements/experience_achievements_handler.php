<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/interfaces/isingletonhandler.php');



class Experience_Achievements_Handler implements ISingletonHandler
{
    private static $user_service;
    private static $initialized = false;
    private static function initialize(int $user_id)
    {
        if (self::$initialized)
            return;
        self::$user_service = new User_service($user_id);
        self::$initialized = true;
    }

    public static function earn(int $user_id, int $amount)
    {
        self::initialize($user_id);
        self::$user_service->earn_experience($amount);
    }
}
