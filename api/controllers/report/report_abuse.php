<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/services/report/report_service.php');

class Report_abuse extends Controller
{
  private $report_service;

  function __construct()
  {
    parent::__construct();

    $this->report_service = new Report_service($this->user_id);

    $this->return_json(
        $this->report_service->report_abuse(
            $this->post_body_arg("report_entity_type"),
            (int)$this->post_body_arg("report_dynamic_id"),
            $this->post_body_arg("report_reason")
        )
    );
  }
}

new Report_abuse;

?>
