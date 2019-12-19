<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/achievements/achievement_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/achievement_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/achievements/earned_achievements_container.php');

/*
Used by Experience Service
*/
class Experience_achievements
{
    private $requestee_user_id;
    private $user_service;
    private $achievement_services;

    /**
     * __construct
     *
     * @param  mixed $user_id
     *
     * @return void
     */
    function __construct(int $user_id)
    {
        $this->requestee_user_id = $user_id;

        $this->user_service = new User_service($this->user_id);
        $this->achievement_services = new Achievement_service($user_id);
    }

    /**
     * levels
     *
     * @param  mixed $current_level
     *
     * @return User_response
     */
    public function levels(?int $current_level): Achievement_response
    {
        $response = new Achievement_response();

        // If didnt give one, then get it,
        if ($current_level === null) {
            $current_level = $this->get_current_level();
            // If the level is zero, or it could not be fetched.
            if ($current_level === 0) {
                // Failed.
                $response->didFail(false, true);
                return $response;
            }
        }

        // keep going
        $sorted_achievements_request = $this->achievement_services->get_all_achievements_divided('player', 'level');
        if ($sorted_achievements_request->success) {
            $sorted_achievements = $sorted_achievements_request->achievements;
            for ($i = 0; $i < count($sorted_achievements); $i++) {
                // earn it.
                if ($current_level >= $sorted_achievements[$i]['achievement_requirements']['amount']) {
                    $achievement_id = $sorted_achievements[$i]['achievement_id'];
                    $earn_request = $this->achievement_services->earn_achievement($achievement_id);
                    if ($earn_request->success) {
                        Earned_achievements_container::add($earn_request);
                    }
                }
            }
        }

        $response->didFail(false, false);
        return $response;
    }

    /**
     * get_current_level
     *
     * @return Int
     */
    public function get_current_level(): Int
    {
        $get_current_level_request = $this->user_service->get_current_level($this->requestee_user_id);
        if ($get_current_level_request->success) {
            return $get_current_level_request->level;
        }
        return 0;
    }
}
