<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/response.php');

class Image_response extends Response
{
  // @string
  public $base64_image;

  // @string, @string
  public $saved_filepath;

  public function didSucceedWithBase64String($success, $base64_image)
  {
    parent::didSucceed($success);
    $this->base64_image = $base64_image;
  }

  public function didSucceedWithSavingImage($success, $file_path)
  {
    parent::didSucceed($success);
    $this->saved_filepath = $file_path;
  }
}
