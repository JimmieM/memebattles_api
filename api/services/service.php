<?php

class Service
{
    protected $requestee_user_id;
    protected $helpers;
    protected $db;

    function __construct($requestee_user_id)
    {
        $this->$requestee_user_id = $requestee_user_id;
        $this->helpers = new Helpers();
        $this->db = new DB_service();
    }
}
