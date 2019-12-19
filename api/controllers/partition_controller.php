<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/controllers/controller.php');

class Partition_controller extends Controller
{

  function __construct($bypass_token_requirement = false) {
    parent::__construct($bypass_token_requirement);
  }

  function handle_partition_request() {}
}

?>
