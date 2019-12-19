<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');
class Report_response extends Response
{
    // @ Array of Report_models
    public $reports;

    /**
     * didSucceedWithReports
     *
     * @param  mixed $success
     * @param  mixed $reports
     *
     * @return void
     */
    public function didSucceedWithReports($success, $reports)
    {
        parent::didSucceed($success);
        $this->reports = $reports;
    }
}
