<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/achievements/battles/battle_achievements.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/interfaces/isingletonhandler.php');

class Battle_Achievements_Handler implements ISingletonHandler
{
    private static $battle_achievements;
    private static $initialized = false;

    private static function initialize(int $user_id)
    {
        if (self::$initialized)
            return;
        self::$battle_achievements = new Battle_achievements($user_id);
        self::$initialized = true;
    }

    /**
     * battle_wins
     *
     * @param  mixed $user_id
     * @param  mixed $response
     *
     * @return void
     */
    public static function battle_wins(int $user_id)
    {
        self::initialize($user_id);
        self::$battle_achievements->win_battles();
    }

    /**
     * contributions
     *
     * @param  mixed $user_id
     * @param  mixed $response
     *
     * @return void
     */
    public static function contributions(int $user_id)
    {
        self::initialize($user_id);
        self::$battle_achievements->battle_contributions();
    }
}
