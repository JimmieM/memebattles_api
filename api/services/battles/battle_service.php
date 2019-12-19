<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/battle_model.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/contribution_model.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/models/response/battle_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/db/db_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/helpers/helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/images/image_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/user/user_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/handlers/log_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/notifications/notification_service.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/experience/Experience_Achievements_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/handlers/achievements/battle_achievements_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api' . '/services/service.php');

class Battle_service extends Service
{
  private $POSSIBLE_BATTLE_STATUSES = [0 => 'open', 1 => 'closed', 2 => 'finished'];
  private $BATTLE_DURATION_HOURS = 48;
  public $CONTRIBUTIONS_PER_BATTLE = 2;

  function __construct($requestee_user_id)
  {
    parent::__construct($requestee_user_id);
  }

  /**
   * get_all_battles
   *
   * @return Battle_response
   */
  public function get_all_battles(): Battle_response
  {

    $response = new Battle_response();
    //Battle_achievements_handler::battle_wins(1);
    $closed = $this->get_closed_battles();
    $open = $this->get_open_battles();

    $response->didSucceedWithAllBattles(true, $closed->battles, $open->battles);
    return $response;
  }

  /**
   * get_closed_battles_as_finished
   *
   * @return Array
   */
  public function get_closed_battles_as_finished(): ?array
  {
    $query_string = "SELECT battle_id, battle_until_date FROM battles WHERE battle_closed = '1' AND battle_finished = '0' ORDER BY battle_until_date ASC";

    $query = $this->db->query($query_string);
    if ($query->success) {
      return $this->db->get_array($query->mysqli_query);
    }
    return null;
  }

  /**
   * get_closed_battles
   *
   * @return Battle_response
   */
  public function get_closed_battles(): Battle_response
  {
    return $this->get_battles('closed');
  }

  /**
   * Get open battles
   *
   * @return Battle_response
   */
  public function get_open_battles(): Battle_response
  {
    return $this->get_battles('open');
  }

  /**
   * Get finished
   *
   * @return Battle_response
   */
  public function get_finished_battles(): Battle_response
  {
    $response = new Battle_response();
    $query = $this->db->query("SELECT battle_id,battle_type,battle_text,battle_winner_user_id,battle_private,battle_finished,battle_created, battle_closed FROM battles WHERE battle_finished = 1 AND battle_closed = 1");

    if ($query->success) {
      $rows = $this->db->get_array($query->mysqli_query);

      $response->didSucceedWithFinishedBattles(true, $rows);
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
  }

  /**
   * Returns battles that were created by $user_id.
   * Returns opemn battles only if $get_open_only is true
   * @param  mixed $battle_id
   * @param  mixed $get_open_only = false
   *
   * @return Battle_response
   */
  public function get_battle_by_user_id(int $user_id, bool $get_open_only = false)
  {
  }

  /**
   * get_username_by_user_id
   *
   * @param  mixed $user_id
   *
   * @return Response
   */
  private function get_username_by_user_id(int $user_id): Response
  {
    $user_service = new User_service();
    return $user_service->get_username_by_user_id($user_id);
  }

  /**
   * has_exceeded_time_limit
   *
   * @param  mixed $battle_created
   *
   * @return bool
   */
  public function battle_has_exceeded_time_limit(string $battle_until): bool
  {
    $date = new DateTime($battle_until);
    $now = new DateTime();

    if ($date < $now) {
      return true;
    }
    return false;
  }

  /**
   * Remove a battle
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  public function remove_battle(int $battle_id): Battle_response
  {
    $response = new Battle_response();
    if (empty($battle_id) || $battle_id === 0) {
      $response->didFailWithMessage(true, false, "No battle ID provided!");
      return $response;
    }

    $query_string = "DELETE FROM battles WHERE battle_id = $battle_id AND battle_created_by_user_id = $this->requestee_user_id";
    $query = $this->db->query($query_string);
    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(true, false, $query->mysqli_error);
    return $response;
  }

  /**
   * TODO ??
   *
   * get_battle_invitiations
   *
   * @return void
   */
  private function get_battle_invitiations()
  {
    $query_string = "SELECT battle_id,battle_type,battle_text,battle_winner_user_id,battle_private,battle_versus,battle_finished,battle_created,battle_closed,battle_until_date
    FROM battles WHERE battle_invitee_user_id = $this->requestee_user_id AND battle_private = '1' AND battle_finished = '0' AND battle_closed = '0'";
    $query = $this->db->query($query_string);
    if ($query->success) {
    }
  }

  /**
   * split_tags
   *
   * @param  mixed $tags
   *
   * @return Array
   */
  private function split_tags(string $tags): ?array
  {
    return explode(',', $tags);
  }

  /**
   * get_battles
   *
   * @param  mixed $battle_status - 'open', 'closed', 'finished'
   *
   * @return Battle_response
   */
  private function get_battles(string $battle_status, bool $open_only = true): Battle_response
  {
    $response = new Battle_response();

    $battles = [];

    $query_string = "SELECT battle_id,battle_type,battle_text,battle_winner_user_id,battle_private,battle_versus,battle_finished,battle_created,battle_closed,battle_until_date,battle_hashtags FROM battles";
    if ($battle_status === 'open') {
      $query_string .= " WHERE battle_closed = '0' AND battle_finished = '0' AND battle_private = '0' ORDER BY battle_created DESC";
    } else if ($battle_status === 'closed') {
      $query_string .= " WHERE battle_closed = '1' AND battle_finished = '0' AND battle_private = '0' ORDER BY battle_until_date ASC";
    } else {
      $response->didFailWithMessage(false, false, "Query failed because of missing status");
      return $response;
    }

    $query = $this->db->query($query_string);

    if ($query->success) {
      while ($r = $this->db->get_row($query->mysqli_query)) {
        $contributions_req = $this->get_contributions($r['battle_id'], false);

        if ($contributions_req->success) {
          $contributions = $contributions_req->contributions;

          $hashtags = $r['battle_hashtags'];
          if (!empty($hashtags)) {
            $r['battle_hashtags'] = $this->split_tags($hashtags);
          }

          $r['battle_contributions'] = $contributions;
          $if_user_has_voted = $this->has_voted($r['battle_id'], $this->requestee_user_id);
          $r['battle_user_has_voted'] = $if_user_has_voted->has_voted;
          if ($if_user_has_voted->has_voted) {
            $r['battle_user_vote_contribution_id'] = $if_user_has_voted->contribution_id;
          }
          $r['battle_is_battle_creator'] = $this->is_battle_creator($contributions[0]['contribution_user_id']);

          if (($this->count_contributions($r['battle_id'])->success)) {
            if ($battle_status === 'open') {
              $r['battle_created'] = $this->helpers->get_time_between_as_string($r['battle_created'], $this->helpers->now(), "ago");
            } else if ($battle_status === 'closed') {
              if (
                ((int) $this->count_contributions($r['battle_id'])->integer < (int) $this->CONTRIBUTIONS_PER_BATTLE)
                or ($this->battle_has_exceeded_time_limit($r['battle_until_date']))
              ) {
                continue;
              }

              $r['battle_time_left'] = $this->helpers->get_time_between_as_string($this->helpers->now(), $r['battle_until_date'], "left", false);
            }

            // append
            $battles[] = $r;
          }
        }
      }
      $response->didSucceedWithBattles(true, $battles);
      return $response;
    }


    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }


  public function get_battle_status(int $battle_id): Battle_response
  {
    $response = new Battle_response();
    $query = $this->db->query("SELECT battle_finished,battle_created, battle_closed FROM battles WHERE battle_id = $battle_id");

    if ($query->success) {
      $battle = $this->db->get_row($query->mysqli_query);

      $battle_finished = (int) $battle['battle_finished'];
      $battle_created = $battle['battle_created'];
      $battle_closed = (int) $battle['battle_closed'];

      if ($battle_finished === 1) {
        $response->didSucceedWithGettingBattleStatus(true, $this->POSSIBLE_BATTLE_STATUSES[2]);
        return $response;
      }

      $has_exceeded = $this->battle_has_exceeded_time_limit($battle_created);
      if ($has_exceeded) {
        $response->didSucceedWithGettingBattleStatus(true, $this->POSSIBLE_BATTLE_STATUSES[2]);
        return $response;
      }

      if ($battle_closed === 1) {
        $response->didSucceedWithGettingBattleStatus(true, $this->POSSIBLE_BATTLE_STATUSES[1]);
        return $response;
      }

      $response->didSucceedWithGettingBattleStatus(true, $this->POSSIBLE_BATTLE_STATUSES[0]);
      return $response;
    }

    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * get_closed_battles
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  public function get_battle(int $battle_id): Battle_response
  {
    $response = new Battle_response();

    if ($battle_id === null) {
      $response->didFailWithMessage(false, false, "Failed to receive battle ID " . $battle_id);
      return $response;
    }

    $query = $this->db->query("SELECT battle_id,battle_type,battle_text,battle_winner_user_id,battle_private,battle_finished,battle_created, battle_closed FROM battles WHERE battle_id = $battle_id");

    if ($query->success) {

      $r = $this->db->get_row($query->mysqli_query);
      $battle_id = $r['battle_id'];
      $battle_status = $this->get_battle_status($battle_id);

      $contributions_req = $this->get_contributions($r['battle_id'], false);

      if ($contributions_req->success) {
        $contributions = $contributions_req->contributions;
      }


      $r['battle_contributions'] = $contributions;
      $if_user_has_voted = $this->has_voted($battle_id, $this->requestee_user_id); // if you, the requester has voted
      $r['battle_user_has_voted'] = $if_user_has_voted->has_voted;
      if ($if_user_has_voted->has_voted) {
        $r['battle_user_vote_contribution_id'] = $if_user_has_voted->contribution_id;
      }

      $r['battle_is_battle_creator'] = $this->is_battle_creator($contributions[0]['contribution_user_id']);

      if (($this->count_contributions($r['battle_id'])->success)) {
        if ($battle_status === 'open') {
          $r['battle_created'] = $this->helpers->get_time_between_as_string($r['battle_created'], $this->helpers->now(), "ago");
        } else if ($battle_status === 'closed') {
          $r['battle_time_left'] = $this->helpers->get_time_between_as_string($this->helpers->now(), $r['battle_until_date'], "left", false);
        }
      }

      $contributions = $this->get_contributions($battle_id, false);
      if ($contributions->success) {
        $res['battle_contributions'] = $contributions->contributions;

        $response->didSucceedWithABattle(true, $res);
        return $response;
      }

      $response->didFailWithMessage(false, true, "Mysqli Error: " . $contributions->message);
      return $response;
    }
    $response->didFailWithMessage(false, true, "Mysqli Error: " . $query->message);
    return $response;
  }

  /**
   * is_battle_creator
   *
   * Requires the First index of Contributions of a battle. The first is the creator.
   *
   * @param  mixed $battle_contribution_id
   *
   * @return Bool
   */
  private function is_battle_creator(int $battle_contribution_user_id): bool
  {
    return $battle_contribution_user_id === $this->requestee_user_id;
  }

  /**
   * get_contribution
   *
   * @param  mixed $contribution_id
   *
   * @return Battle_response
   */
  private function get_contribution(int $contribution_id): Battle_response
  {
    $response = new Battle_response();
    $query_string = "SELECT contribution_id,contribution_battle_id,contribution_image,contribution_judge_votes,contribution_username,contribution_user_id,contribution_date FROM battle_contributions WHERE contribution_id = $contribution_id LIMIT 1";
    $query = $this->db->query($query_string);
    if ($query->success) {
      return $this->db->get_row($query->mysqli_query);
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * get_contributions_image_sources
   * TODO
   * Returns a slim version of Get_Contributions.
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  private function get_contributions_image_sources(int $battle_id): Battle_response
  {
    $response = new Battle_response();
    $query_string = "SELECT contribution_id,contribution_image,contribution_username,contribution_user_id FROM battle_contributions WHERE contribution_battle_id = $battle_id";
    $query = $this->db->query($query_string);

    if ($query->success) {
      $contributions = $this->db->get_array($query->mysqli_query);
      $response->didSucceedWithContributions(true, $contributions);
      return $response;
    }
    $response->didFailWithMessage(false, true, $query->mysqli_error);
    return $response;
  }

  /**
   * get_contributions
   *
   * @param  mixed $sort_by_top_rated
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  private function get_contributions(int $battle_id, bool $sort_by_top_rated = false): Battle_response
  {
    $response = new Battle_response();

    if (!$sort_by_top_rated && $battle_id === null) {
      $response->didFailWithMessage(true, false, "Missing params");
      return $response;
    }

    $query_string = "SELECT contribution_id,contribution_battle_id,contribution_image,contribution_judge_votes,contribution_username,contribution_user_id,contribution_date FROM battle_contributions WHERE contribution_battle_id = $battle_id";
    if ($sort_by_top_rated) {
      $query_string = "SELECT * FROM battle_contributions LIMIT 40";
    }

    $query = $this->db->query($query_string);

    if ($query->success) {
      $contributions = $this->db->get_array($query->mysqli_query);

      $user_service = new User_service();
      $image_service = new image_service();

      $arr = array();
      $i = 0;
      foreach ($contributions as $contribution) {

        $user_id = (int) $contribution['contribution_user_id'];
        if ($user_id === null || $user_id === 0) {
          continue;
        }

        // convert src to base64
        $get_contribution_image = $image_service->to_base64($contribution['contribution_image']);
        $base64 = $get_contribution_image->base64_image;

        // swap property to base64
        $contribution['contribution_image'] = $base64;

        $profile_picture_req = $user_service->get_profile_picture($user_id);

        if ($profile_picture_req->success) {
          $contribution['contribution_user_profile_picture'] = $profile_picture_req->profile_picture_base64;
        }
        $get_level = $user_service->get_current_level($user_id);
        $contribution['contribution_user_level'] = 1; // Set as level 1(?) TODO:
        if ($get_level->success) {
          $contribution['contribution_user_level'] = $get_level->level;
        }
        $contribution['contribution_date_ago'] = $this->helpers->get_time_between_as_string($contribution['contribution_date'], $this->helpers->now(), "ago");
        $contribution['contribution_middle_text'] = false;
        $contribution['contribution_is_placeholder'] = false;
        // append to {arr}
        $arr[$i++] = $contribution;
      }

      $response->didSucceedWithContributions(true, $arr);
      return $response;
    } else if ($query->hasError) {
      $response->didFailWithMessage(false, $query->hasError, $query->mysqli_error);
    } else {
      $response->didFailWithMessage(false, $query->hasError, $query->message);
    }

    return $response;
  }

  /**
   * create_battle
   *
   * @param  mixed $battle_type
   * @param  mixed $battle_text
   * @param  mixed $battle_private
   * @param  mixed $image_base64
   * @param  mixed $username
   * @param  mixed $user_id
   *
   * @return Battle_response
   */
  public function create_battle($battle_type, string $battle_text, int $battle_private, string $image_base64, string $username, int $user_id, $invitee_user_id): Battle_response
  {
    //$this->helpers->guard_connection(Array($battle_type, $battle_text, $battle_private, $image_base64, $username, $user_id));
    $response = new Battle_response();

    $_battle_type;
    if ($battle_type === "burn") {
      $_battle_type = 0;
    } else if ($battle_type === "describe") {
      $_battle_type = 1;
    } else {
      $response->didFailWithMessage(false, false, "Missing Battle Type! ");
      return $response;
    }

    $battle_created = $this->helpers->now();

    if ($battle_private === 1) {
      $query_string = "INSERT INTO battles (battle_type, battle_text, battle_private, battle_created, battle_created_by_user_id,battle_private_invitee_user_id)
      VALUES
      ($_battle_type, '$battle_text', $battle_private, '$battle_created', $user_id, $invitee_user_id)";
    } else {
      $query_string = "INSERT INTO battles (battle_type, battle_text, battle_private, battle_created, battle_created_by_user_id)
      VALUES
      ($_battle_type, '$battle_text', $battle_private, '$battle_created', $user_id)";
    }

    $create_query = $this->db->query($query_string, true);

    if ($create_query->success) {

      // create a contribution
      $latest_id = $this->db->get_latest_key_id($create_query->connection);
      if ($latest_id === 0) {
        $response->didFailWithMessage(false, true, "Incorrect battle ID");
        return $response;
      }

      $contribute = $this->create_contribution((int) $latest_id, $image_base64, $user_id, $username);

      if ($contribute->success) {
        if ($battle_private === 1) {
          $notification_service = new Notification_service($this->requestee_user_id);
          $username = $this->get_username_by_user_id($invitee_user_id)->username ?? "Someone";
          $notification_model = new Notification_model($invitee_user_id, 1, (int) $latest_id, $username . " invited you to a battle!");
          $notification_service->create_notification($notification_model, true);
        }
        Experience_Achievements_Handler::earn($this->requestee_user_id, 150);
        $response->didSucceedWithMessage(true, "did succeed. With latest id: " . $latest_id);
        return $response;
      }

      $response->didFailWithMessage(false, true, $contribute->message . ':' . $contribute->mysqli_error);
      return $response;
    }
    $response->didFailWithMessage(false, true, '' . $create_query->mysqli_error);
    return $response;
  }

  /**
   * create_contribution
   *
   * @param  mixed $battle_id
   * @param  mixed $image
   * @param  mixed $user_id
   * @param  mixed $username
   *
   * @return Battle_response
   */
  public function create_contribution(int $battle_id, string $image, int $user_id, string $username): Battle_response
  {
    $response = new Battle_response();
    $can_contribute = $this->can_contribute($battle_id, $user_id);

    if (!$can_contribute) {
      $response->didFailWithMessage(false, true, "You've already contributed to this battle.");
      return $response;
    }

    $image_service = new image_service();

    $image_service->__save_for_battle($username, $battle_id);
    $save_image = $image_service->save($image);

    // if not, kill.
    if (!$save_image->success) {
      $response->didFailWithMessage(false, $save_image->hasError, "Save image: " .  $save_image->message);
      return $response;
    }
    $image_src_path = $save_image->saved_filepath;
    $now = $this->helpers->now();

    $query = "INSERT INTO battle_contributions (contribution_battle_id, contribution_image, contribution_username, contribution_user_id, contribution_date) VALUES ($battle_id, '$image_src_path', '$username', $user_id, '$now')";
    $new_contribution = $this->db->query($query);

    if ($new_contribution->success) {
      $this->declare_battle_as_closed($battle_id);

      Battle_Achievements_Handler::contributions($user_id, $response);
      Experience_Achievements_Handler::earn($this->requestee_user_id, 500);
      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(false, true, "Error: " . $new_contribution->mysqli_error . $new_contribution->message);
    return $response;
  }

  /**
   * has conteibured to battle.
   *
   * @param  mixed $battle_id
   * @param  mixed $user_id
   *
   * @return Battle_response
   */
  private function has_contributed_to_battle(int $battle_id, int $user_id): Battle_response
  {

    $response = new Battle_response();

    $has_contributed = $this->db->query("SELECT contribution_id FROM battle_contributions WHERE contribution_battle_id = " . $battle_id . " AND contribution_user_id = " . $user_id);
    if ($has_contributed->success) {
      if ($this->db->count_rows($has_contributed->mysqli_query) >= 1) {
        $row = $this->db->get_row($has_contributed->mysqli_query);
        $response->didReturnIfUserHasContributed(true, true, $row['contribution_id']);
        return $response;
      }
      $response->didReturnIfUserHasContributed(true, false, null, $has_contributed->mysqli_error);
      return $response;
    }
    $response->didFailWithMessage(false, true, $has_contributed->mysqli_error);
    return $response;
  }

  /**
   * can_contribute
   *
   * @param  mixed $battle_id
   * @param  mixed $user_id
   *
   * @return bool
   */
  public function can_contribute(int $battle_id, int $user_id): bool
  {
    $has_exceeded = $this->db->query("SELECT count(*) FROM battle_contributions WHERE contribution_battle_id = " . $battle_id);
    if ($this->db->count_rows($has_exceeded->mysqli_query) >= $this->CONTRIBUTIONS_PER_BATTLE) {
      return false;
    }

    $has_contributed = $this->has_contributed_to_battle($battle_id, $user_id);

    if ($has_contributed->has_contributed) {
      return false;
    }

    return true;
  }

  /**
   * get_contribution_votes
   *
   * @param  mixed $contributions_ids
   *
   * @return array
   */
  public function get_contribution_votes(array ...$contributions_ids): array
  {
    return array();
  }

  /**
   * get_top_rated_contributions
   *
   * @param  mixed $amount
   *
   * @return Battle_response
   */
  public function get_top_rated_contributions(int $amount): Battle_response
  {

    return $this->get_contributions(0, true);
  }

  /**
   * has_voted
   *
   * @param  mixed $battle_id
   * @param  mixed $user_id
   *
   * @return Battle_response
   */
  private function has_voted(int $battle_id, int $user_id): Battle_response
  {
    $response = new Battle_response();
    $has_voted = $this->db->query("SELECT vote_id, vote_contribution_id FROM contribution_votes WHERE vote_battle_id = $battle_id AND vote_user_id = $user_id;");
    if ($has_voted->success) {
      $num_rows = $this->db->count_rows($has_voted->mysqli_query);
      $row = $this->db->get_row($has_voted->mysqli_query);
      if ($num_rows >= 1) {
        $response->didReturnIfUserHasVoted(true, true, $row['vote_contribution_id']);
        return $response;
      }
      $response->didReturnIfUserHasVoted(false, false);
      return $response;
    }
    $response->didReturnIfUserHasVoted(false, true, null, $has_voted->mysqli_error);
    return $response;
  }

  /**
   *   Registers the user as has voted for a contribution.
   *   Can only vote once per unique contribution of a battle.
   *
   * @param  mixed $contribution_id
   * @param  mixed $battle_id
   * @param  mixed $user_id
   *
   * @return Battle_response
   */
  public function vote_contribution(int $contribution_id, int $battle_id, int $user_id): Battle_response
  {
    $response = new Battle_response();

    $has_voted = $this->has_voted;
    if ($has_voted->success) {
      // vote
      $vote_query = "INSERT INTO contribution_votes
        (vote_user_id, vote_battle_id, vote_contribution_id)
        VALUES
        ($user_id, $battle_id, $contribution_id);";
      $update_contribtion = "UPDATE battle_contributions
      SET contribution_judge_votes = contribution_judge_votes + 1
      WHERE contribution_id = $contribution_id
      AND contribution_battle_id = $battle_id;";

      $new_vote = $this->db->query($vote_query);
      $update = $this->db->query($update_contribtion);

      if ($new_vote->success && $update->success) {

        // create notification
        $get_contribution = $this->get_contribution($contribution_id);
        if ($get_contribution->success) {
          $battle_id = $get_contribution->contribution['contribution_battle_id'];
          $contribution_user_id = $get_contribution->contribution['contribution_user_id'];
          $notification_service = new Notification_service($this->requestee_user_id);
          $username = $this->get_username_by_user_id($contribution_user_id)->username ?? "Someone";
          $notification_model = new Notification_model($contribution_user_id, 1, $battle_id, $username . " voted for your meme!");
          $notification_service->create_notification($notification_model, false);
        }

        $response->didSucceed(true);
        return $response;
      }
      $response->didFailWithMessage(false, true, "Query 1: " . $new_vote->mysqli_error . " : Query: " . $vote_query . " \n\n Query 2: " . $update->mysqli_error . " : " . $update_contribtion);
      return $response;
    }

    $response->didFailWithMessage(false, true, $has_voted->mysqli_error);
    return $response;
  }

  /**
   * get_contribution_by_user_id
   *
   * @return void
   */
  public function get_contribution_by_user_id()
  {
  }


  /**
   * count_contributions
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  private function count_contributions($battle_id): Battle_response
  {
    $response = new Battle_response();

    $validate = $this->db->query("SELECT contribution_battle_id FROM battle_contributions WHERE contribution_battle_id = $battle_id");
    if ($validate->success) {
      $num_rows = $this->db->count_rows($validate->mysqli_query);
      $response->didSucceedWithAnInteger(true, $num_rows);
      return $response;
    }
    $response->didFailWithMessage(false, true, $validate->mysqli_error);
    return $response;
  }


  /**
   * is_battle_done
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  public function is_battle_done($battle_id): Battle_response
  {

    $response = new Battle_response();

    $res = $this->get_battle($battle_id);
    if ($res->success) {
      $battle = $res->battle;
      if ($battle['battle_finished'] === '0') {
        $response->didSucceedWithABoolean(true, false);
        return $response;
      }
      $response->didSucceedWithABoolean(true, true);
      return $response;
    }
    $response->didFailWithMessage(false, true, $res->mysqli_error);
  }


  /**
   * declare_battle_as_open
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  public function declare_battle_as_open(int $battle_id): Battle_Response
  {
    $response = new Battle_Response();

    $query = $this->db->query("UPDATE battles SET battle_closed = 0 WHERE battle_id = $battle_id");

    if ($query->success) {
      $response->didSucceed(true);
      return $response;
    }
    $response->didFailWithMessage(false, $query->hasError, $query->mysqli_error);
    return $response;
  }


  /**
   * will declare a battle as finished, only if two contributions has been made for a battle.
   * Also creates a notification for the opponent, that someone has joined.
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  private function declare_battle_as_closed(int $battle_id): Battle_response
  {
    $response = new Battle_response();

    $validate = $this->count_contributions($battle_id);
    if ($validate->success) {
      $num_rows = $validate->integer;

      if ($num_rows >= $this->CONTRIBUTIONS_PER_BATTLE) {

        $until_date = $this->helpers->set_time("+" . $this->BATTLE_DURATION_HOURS . " hours");

        $declaration = $this->db->query("UPDATE battles SET battle_closed = 1, battle_until_date = '$until_date' WHERE battle_id = " . $battle_id);
        if ($declaration->success) {
          $this->notify_battle_creator($battle_id, "Someone has joined your battle!");
          $response->didSucceed(true);
          return $response;
        }
        $response->didFailWithMessage(false, true,  "Declaration: " . $declaration->mysqli_error);
        return $response;
      }
      $response->didFailWithMessage(false, false, "This battle is yet to be finished.");
      return $response;
    }

    $response->didFailWithMessage(false, true, "Validate: " . $validate->message);
    return $response;
  }

  /**
   * notify_battle_creator
   *
   * @param  mixed $battle_id
   * @param  mixed $message
   *
   * @return Battle_response
   */
  private function notify_battle_creator($battle_id, $message): Battle_response
  {

    $response = new Battle_response();

    $get_battle = $this->get_battle($battle_id);
    if ($get_battle->success) {
      $battle = $get_battle->battle;

      $battle_creator = $battle['battle_contributions'][0]['contribution_user_id'];

      if ($battle_creator !== null) {
        $notification_service = new Notification_service($this->requestee_user_id);
        $notification_model = new Notification_model($battle_creator, 1, $battle['battle_id'], $message);
        $create_notification = $notification_service->create_notification($notification_model, true);

        if ($create_notification->success) {
          $response->didSucceed(true);
        } else {
          log_handler::new(1, "Notify_battle_creator", $create_notification->message);
          $response->didFailWithMessage(false, $create_notification->hasError, "Notify: " .  $create_notification->message);
        }
        return $response;
      }
      log_handler::new(1, "Notify_battle_creator", "Battle ID: " . $battle_id . " \nCould not find Battle Creators User ID.");
      $response->didFailWithMessage(false, true, "Could not find Battle Creators User ID.");
      return $response;
    }
    log_handler::new(1, "Notify_battle_creator", "Failed to get battle. " . $get_battle->message);
    $response->didFailWithMessage(false, $get_battle->hasError, "GetBattle: " . $get_battle->message);
    return $response;
  }

  /**
   * restart_battle
   *
   * Restarts a finished battle
   *
   * @param  mixed $battle_id
   *
   * @return void
   */
  public function restart_battle(int $battle_id): void
  {
    if (empty($battle_id)) {
      log_handler::new(1, "restart_battle", "Empty battle_id");
    }
    $until_date = $this->helpers->set_time("+48 hours");
    $now = $this->helpers->now();
    $query_string = "UPDATE
    battles
    SET battle_finished = 0, battle_until_date = '$until_date', battle_created = '$now'
    WHERE battle_id = $battle_id";

    //echo $query_string;

    $query = $this->db->query($query_string);
    //echo json_encode($query);
  }

  /**
   * declare_battle_as_finished
   *
   * @return void
   */
  public function declare_battle_as_finished(int $battle_id, $winner_user_id = null): Query_response
  {

    if ($winner_user_id !== null) {
      $query_string = "UPDATE battles
      SET
      battle_finished = 1,
      battle_winner_user_id = $winner_user_id,
      battle_closed = 1
      WHERE battle_id = $battle_id";
    } else {
      $query_string = "UPDATE battles
      SET
      battle_finished = 1,
      battle_closed = 1
      WHERE battle_id = $battle_id";
    }
    $update_battle = $this->db->query($query_string);

    return $update_battle;
  }


  /**
   * select_winner
   *
   * Used by Battles_finished Cron job.
   *
   * @param  mixed $battle_id
   *
   * @return Battle_response
   */
  public function select_winner(int $battle_id): Battle_response
  {
    // based on two conditions.
    // either two contributions is found, or the battle_finished is set to true/1.

    $get_battle = $this->get_battle($battle_id);

    $response = new Battle_response();

    if ($get_battle->success) {
      $battle = $get_battle->battle;
      if ($battle['battle_finished'] === 1) {
      } else {
        $get_contributions = $this->get_contributions($battle_id);
        if ($get_contributions->success) {
          $contributions = $get_contributions->contributions;
          if (count($contributions) >= 2) {

            $notification_service = new Notification_service($this->requestee_user_id);

            $contribution_1 = $contributions[0];
            $contribution_2 = $contributions[1];
            $winner_userid = 0; // @Int

            // its a tie.
            if ((int) $contribution_1['contribution_judge_votes'] === (int) $contribution_2['contribution_judge_votes']) {
              // Notify both
              $notification_model1 = new Notification_model($contribution_1['contribution_user_id'], 1, $battle['battle_id'], "Your battle ended up in a tie!");
              $notification_model2 = new Notification_model($contribution_2['contribution_user_id'], 1, $battle['battle_id'], "Your battle ended up in a tie!");
              $notification_service->create_notification($notification_model1, true);
              $notification_service->create_notification($notification_model2, true);
              // $this->restart_battle($battle_id);
              // $response->didFailWithSelectingAWinnerWithBattleRestart(true, false, false);
              // return $response;
              $update_battle = $this->declare_battle_as_finished($battle_id);
            } else {
              if ((int) $contribution_1['contribution_judge_votes'] > (int) $contribution_2['contribution_judge_votes']) {
                $winner_userid = $contribution_1['contribution_user_id'];
              } else {
                $winner_userid = $contribution_2['contribution_user_id'];
              }
              $update_battle = $this->declare_battle_as_finished($battle_id, $winner_userid);
            }

            if ($update_battle->success) {
              $response->didSucceedWithSelectingAWinner(true, $winner_userid);
              Battle_Achievements_Handler::battle_wins($winner_userid, $response);
              Experience_Achievements_Handler::earn($winner_userid, 1000);
            } else {
              $response->didFailWithMessage(false, true, "Update battle: " . $update_battle->mysqli_error);
            }
            return $response;
          }
        }
        $response->didFailWithMessage(true, true, "Get contributions;" .  $get_contributions->mysqli_error);
        return $response;
      }
    }
    $response->didFailWithMessage(true, true, "Get battle:" . $get_battle->mysqli_error);
    return $response;
  }
}
