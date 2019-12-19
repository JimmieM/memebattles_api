<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/models/response/Image_response.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/api'.'/handlers/Log_Handler.php');


/**
 * Image service
 */
class Image_service {
  private $helpers;
  private $output_folder;
  private $output_file;

  private $STATIC_OUTPUT_DIRECTORY = "../img/saved/";

  public $STANDARD_USER_BASE_IMAGE;
  public $STANDARD_BATTLE_IMAGE; // TODO: Implement.
  public $STANDARD_CHAT_IMAGE;
  public $STANDARD_MEME_IMAGE;

  function __construct() {
    $this->set_standard_images();
    $this->helpers = new Helpers();
  }

  /*
  Applies standard images for user and battle.
  */
  private function set_standard_images() {
    // user fallback
    $this->STANDARD_USER_BASE_IMAGE = $this->STATIC_OUTPUT_DIRECTORY.'mockups/standard_user.png';
    // meme fallback
    $this->STANDARD_MEME_IMAGE = $this->STATIC_OUTPUT_DIRECTORY.'mockups/standard_meme.png';
    // chat fallback
    $this->STANDARD_CHAT_IMAGE = $this->STATIC_OUTPUT_DIRECTORY.'mockups/standard_chat.png';
  }

  /*
  Constructor to call when using instance of class to parse for battles.

  @param {username} String
  @param {battle_id} Int
  */
  public function __save_for_battle($username, $battle_id) {
    $this->output_folder = $this->STATIC_OUTPUT_DIRECTORY . 'contributions/' . $username . '/' . $this->helpers->now(true) . '/';
    $this->output_file = $battle_id.'.jpg';
  }

    /*
  Constructor to call when using instance of class to parse for chat message.

  @param {username} String
  */
  public function __save_for_chat($username) {
    $this->output_folder = $this->STATIC_OUTPUT_DIRECTORY . 'chat/' . $username . '/';
    $token = $this->helpers->create_token(12);
    $this->output_file = date("Y-m-d_H.i.s") . '_' . $token . '.jpg';
  }

  /**
   * __save_for_userprofile
   *
   * @param  mixed $username
   *
   * @return void
   */
  public function __save_for_userprofile($username) {
    $this->output_folder = $this->STATIC_OUTPUT_DIRECTORY . 'users/' . $username . '/';
    $this->output_file = $this->helpers->now(true).'.jpg';
  }


  /**
   * get_standard_user_image
   *
   * @return string
   */
  public function get_standard_user_image() : ?string {
    return $this->to_base64($this->STANDARD_USER_BASE_IMAGE)->base64_image ?? null;
  }

  /**
   * get_standard_meme_image
   *
   * @return string
   */
  public function get_standard_meme_image() : ?string {
    return $this->to_base64($this->STANDARD_MEME_IMAGE)->base64_image ?? null;
  }

  private function clean_user_directory() {
    // remove old ones?
  }

  /*
  @param {image_src_path} - String
  */
  function remove_picture($image_src_path) {
    return unlink($image_src_path);
  }

  // @returns String
  function get_saved_path() {
    return $this->output_folder . $this->output_file;
  }


  /*
  Takes a src string of an image.

  @param {path} String

  @Image_service_Response
  */
  function to_base64($path) : Image_service_response {
    $path = __DIR__.$path;
    $error_message = "";
    $response = new Image_service_response();

    $data = file_get_contents($path);

    if(!$data) {
      $error_message = "Could not find image with given path. \n\n Path: ".$path;
      //Log_Handler::new(1,"to_base64",$error_message);
      $response->didFailWithMessage(false, true, $error_message);
      return $response;
    }
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $response->didSucceedWithBase64String(true, 'data:image/' . $type . ';base64,' . base64_encode($data));
    return $response;
  }

  /*
  Saves a image based on a Base64 String.

  @param {base64Image} String - Base64
  @returns String - The Path it was inserted as. (output/file.png)
  */
  function save($base64Image) : Image_service_response {
    $response = new Image_service_response();

    $error_msg = "";

    if($this->output_folder) {
      $path_to_folder = __DIR__. $this->output_folder;

      if (!is_dir($path_to_folder)) {
        $create_dir = mkdir($path_to_folder, 0755, true);
        if(!$create_dir) {
          $error_msg = "Failed to create directory for image. Path: " . $path_to_folder . " : . ";
          Log_Handler::new(1,"Save Image", $error_msg);
          $response->didFailWithMessage(false, true, $error_msg);
          return $response;
        }
      }

      $base64Image = trim($base64Image);
      $base64Image = str_replace('data:image/png;base64,', '', $base64Image);
      $base64Image = str_replace('data:image/jpg;base64,', '', $base64Image);
      $base64Image = str_replace('data:image/jpeg;base64,', '', $base64Image);
      $base64Image = str_replace('data:image/gif;base64,', '', $base64Image);
      $base64Image = str_replace(' ', '+', $base64Image);

      $image_data = base64_decode($base64Image);
      try {
        $image = imagecreatefromstring($image_data);
      } catch (\Throwable $th) {
        $response->didFailWithMessage(false, true, $th);
        return $response;
      }

      if($image) {
        $short_file_path = $this->output_folder . $this->output_file;
        $full_file_path = __DIR__ . $short_file_path;

        $save = imagejpeg($image, $full_file_path, 35);
        if(!$save) {
          Log_Handler::new(1,"Save Image","Failed to save Image. Filepath: " . $short_file_path);
          $response->didFailWithMessage(false, true, "Failed to put file in directory. Filepath: " . $short_file_path);
          return $response;
        }

        $response->didSucceedWithSavingImage(true, $short_file_path);
        return $response;
      } else {
        $response->didFailWithMessage(false, false, "Failed to process image!");
        return $response;
      }
    }
    $response->didFailWithMessage(false, false, "No output folder provided!");
    return $response;
  }
}
