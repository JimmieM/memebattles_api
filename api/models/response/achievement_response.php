<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/response.php');

/**
 * Read Response for comments of funcs.
 */
class Achievement_response extends Response
{
    // @Array of general achievments / All Achievements.
    public $achievements;

    // @Array of unearned achievements
    public $unearned_achievements;

    // @Array of earned achievements
    public $earned_achievements;

    public $achievement_template;

    public function didSucceedWithAchievements($success, $achievements)
    {
        parent::didSucceed($success);
        $this->achievements = $achievements;
    }

    public function didSucceedWithEarnedAndUnearnedAchievements($success, $earned_achievements, $unearned_achievements) {
        parent::didSucceed($success);
        $this->unearned_achievements = $unearned_achievements;
        $this->earned_achievements = $earned_achievements;
    }

    public function didEarnAnAchievement($success, $hasError, $achievement_template) {
        $this->success = $success;
        $this->hasError = $hasError;
        $this->achievement_template = $achievement_template;
    }
}
?>
