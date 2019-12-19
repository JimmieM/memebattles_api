<?php

class Report_model
{
    // @Int
    public $report_id;
    // @int 
    public $report_user_id;
    // @string
    public $report_message;
    // @int
    public $report_level;  

    // @int
    public $report_by_user_id;

    public function __construct(int $report_id, int $report_user_id, string $report_message, int $report_level, int $report_by_user_id)
    {
        $this->report_id = $report_id;
        $this->report_user_id = $report_user_id;
        $this->report_message = $report_message;
        $this->report_level = $report_level;
        $this->report_by_user_id = $report_by_user_id;
    }
    
    public function is_abuse() {
        if($this->report_level === 1) {
            return true;
        }
        return false;
    }
}


?>
