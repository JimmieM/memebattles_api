<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/report_model.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/images/Image_service.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/report_response.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/user/user_service.php');

class Report_service {
    private $db;
    private $user_services; 
    private $requestee_user_id;
    
    function __construct(int $requestee_user_id) {
        $this->db = new DB_service();
        $this->user_service = new User_service();
        $this->requestee_user_id = $requestee_user_id;
    }

    /*
    @param {user_id} - Int
    */
    private function get_reports(int $user_id) {
        $response = new Report_response();
        $report_models = array();
        $query_string = "SELECT report_user_id, report_message, report_level, report_by_user_id FROM reports WHERE report_used_id = $user_id";
        $get_reports = $this->db->query($query_string);
        if($get_reports->success) {
            $reports = $this->db->get_array($get_reports->mysqli_query);
            foreach ($reports as $report) {
                $report_models[] = new Report_model((int)$report['report_id'], (int)$report['report_user_id'], $report['report_message'], (int)$report['report_level'], (int)$report['report_by_user_id']);
            }
            $response->didSucceedWithReports(true, $report_models);
            return $response;
        } 
        $response->didFailWithMessage(false, $get_reports->hasError, $get_reports->message);
        return $response;
    }

    /*
    @param {reports} - Array of Report_models
    */
    // private function process_reports($reports) : Report_response {
    //     $response = new Report_response();
    
    //     foreach ($reports as $report) {
    //     }
    // }

    private function getReportLevelAsMessage(int $level) : ?string {
        $reasons = array(
            1 => "illegal image",
            2 => "illegal name"
        );
        foreach ($reasons as $key => $value) {
            if($key === $level) {
                return $value;
            }
        }
        return null;
    }

    public function report_abuse(string $entity_type, int $dynamic_id, string $reason) : Report_response {
        $response = new Report_response();

        // $report_request = $this->get_reports($user_id);
        // if(!$report_request->success) {
        //     Log_Handler::new(1, "process_reports", "Failed to get reports");
        //     $response->didFailWithMessage(false, true, $report_request->message);
        //     return $response;
        // }
        // $reports = $report_request->reports;

        // $process_reports = $this->process_reports($reports);

        //$message = $this->getReportReasonAsMessage($level);
        
        $query_string = "INSERT INTO `reports`(`report_dynamic_id`, `report_reason`, `report_by_user_id`, `report_entity_type`) VALUES ($dynamic_id, '$reason', $this->requestee_user_id, '$entity_type')";
        $query = $this->db->query($query_string);
        if($query->success) {
            $response->didSucceed(true);
            return $response;
        }
        $response->didFailWithMessage(false, true, $query->mysqli_error);
        return $response;
    }
}
