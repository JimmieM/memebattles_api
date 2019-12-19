 <?php
 require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/response.php');
class Earned_achievements_container {
 
    private static $instance = null;

    // @Array of Response Models.
    private static $earned_achievements = array();


    /**
     * Get
     *
     * @return Array or null
     */
    public static function get() : ?Array {
        return self::$earned_achievements;
    }

    /**
     * Add
     *
     * @param  Response $achievement
     *
     * @return void
     */
    public static function add(Response $achievement)
    {
        if (self::$instance == null)
        {
            self::$instance = new Earned_achievements_container();
        }
        self::$earned_achievements[] = $achievement; 
    }
}
?>