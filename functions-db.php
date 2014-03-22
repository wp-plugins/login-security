<?php

/**
 * Get a list of all succeed log from database
 */
function ls_db_get_log_success( $current_page_number ) {
  
  global $wpdb;
  
  // never trust user input
  $current_page_number = (int) $current_page_number;
  
  // init
  $items_per_page = 30;
  
  // set the query
  $sql = "SELECT SQL_CALC_FOUND_ROWS * 
    FROM `" . LS_DB_TABLE_LOGIN_ACCESS . "`
    WHERE `wla_is_failed` = 0
    ORDER BY `wla_date` DESC
    LIMIT %d, %d";
  
  $sql_prepare = $wpdb->prepare(
    $sql,
    ( ($current_page_number - 1) * $items_per_page),
    $items_per_page
    );
  
  // execute the query + return the results
  return $wpdb->get_results( $sql_prepare, ARRAY_A );
}


/**
 * Get a log of all failed log from database
 */
function ls_db_get_log_fail( $current_page_number ) {
  
  global $wpdb;
  
  // never trust user input
  $current_page_number = (int) $current_page_number;
  
  // init
  $items_per_page = 30;
  
  // set the query
  $sql = "SELECT SQL_CALC_FOUND_ROWS * 
    FROM `" . LS_DB_TABLE_LOGIN_ACCESS . "`
    WHERE `wla_is_failed` = 1
    ORDER BY `wla_date` DESC
    LIMIT %d, %d";
  
  $sql_prepare = $wpdb->prepare(
    $sql,
    ( ($current_page_number - 1) * $items_per_page),
    $items_per_page
    );
  
  // execute the query + return the results
  return $wpdb->get_results( $sql_prepare, ARRAY_A );
}


/**
 * Get a log of all failed log from database
 */
function ls_db_get_log_fail_by_ip( $current_page_number ) {
  
  global $wpdb;
  
  // never trust user input
  $current_page_number = (int) $current_page_number;
  
  // init
  $items_per_page = 30;
  
  // set the query
  $sql = "SELECT SQL_CALC_FOUND_ROWS 
      wla_ip, 
      COUNT(*) AS wla_ip_count, 
      MIN(wla_date) AS wla_date_min, 
      MAX(wla_date) AS wla_date_max, 
      wlab_id
    FROM `" . LS_DB_TABLE_LOGIN_ACCESS . "`
    LEFT JOIN `" . LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST . "` ON  `wlab_blocked_ip` = `wla_ip` 
                                                           AND `wlab_is_blocked` = 1
    WHERE `wla_is_failed` = 1
    GROUP BY `wla_ip`
    ORDER BY `wla_ip_count` DESC
    LIMIT %d, %d";
  
  $sql_prepare = $wpdb->prepare(
    $sql,
    ( ($current_page_number - 1) * $items_per_page),
    $items_per_page
    );
  
  // execute the query + return the results
  return $wpdb->get_results( $sql_prepare, ARRAY_A );
}


/**
 * 
 * 
 * @param int $days_count
 */
function ls_db_get_log_stats_days_fail( $days_count=7 ) {
  
  global $wpdb;
  
  // make sure the variable is an integer
  $days_count = (int) $days_count;
  
  // init
  $count_attack_by_day = array();
  
  // query to list the number of attack for the previous 7 days
  $sql = "SELECT COUNT(*) AS wla_count, DATE(wla_date) AS wla_date_attacks
    FROM `" . LS_DB_TABLE_LOGIN_ACCESS . "` 
    WHERE `wla_is_failed` = 1
    AND DATEDIFF( NOW(), `wla_date` ) < %d
    GROUP BY DATE( `wla_date` ) ";
  
  $sql_prepare = $wpdb->prepare(
    $sql,
    $days_count
    );
  
  // Execute the query
  $items = $wpdb->get_results( $sql_prepare, ARRAY_A );
  
  // List all the results
  if (!empty($items)) {
    foreach ($items as $k => $item) {
      $count_attack_by_day[$item['wla_date_attacks']] = $item['wla_count'];
    }
  }
  
  // Return the attacks by day
  return $count_attack_by_day;
}

/**
 * 
 * 
 * @param int $days_count
 */
function ls_db_get_log_stats_months_fail( $months_count=12 ) {
  
  global $wpdb;
  
  // make sure the variable is an integer
  $months_count = (int) $months_count;
  
  // init
  $count_attack_by_day = array();
  
  // query to list the number of attack for the previous 12 months
  $sql = "SELECT COUNT(*) AS wla_count, DATE_FORMAT( wla_date, '%%Y-%%m' ) AS wla_month_attacks
    FROM `" . LS_DB_TABLE_LOGIN_ACCESS . "` 
    WHERE `wla_is_failed` = 1
    AND PERIOD_DIFF( DATE_FORMAT( NOW(), '%%Y%%m' ), DATE_FORMAT( `wla_date`, '%%Y%%m' ) ) < %d
    GROUP BY MONTH( `wla_date` ) ";
  
  $sql_prepare = $wpdb->prepare(
    $sql,
    $months_count
    );
  
  // Execute the query
  $items = $wpdb->get_results( $sql_prepare, ARRAY_A );
  
  // List all the results
  if (!empty($items)) {
    foreach ($items as $k => $item) {
      $count_attack_by_day[$item['wla_month_attacks']] = $item['wla_count'];
    }
  }
  
  // Return the attacks by day
  return $count_attack_by_day;
}


/**
 * Add the IP address from the blacklist
 *
 * @param str $ip_address
 * @return Ambigous <number, false, boolean, mixed>
 */
function ls_db_insert_ip_to_blacklist( $ip_address='' ) {
  
  // verify it's not empty
  if (empty($ip_address)) {
    return false;
  }
  
  global $wpdb;
  
  // get current user id (if there is no current user, it returns 0)
  $current_user_id = get_current_user_id();
  
  // Define the query
  $sql = " INSERT INTO `" . LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST . "` 
      ( `wlab_blocked_ip`, `wlab_added_date`, `wlab_added_ip`, `wlab_added_by_wp_user_id` ) 
      VALUES (%s, %s, %s, %d)
      ON DUPLICATE KEY UPDATE `wlab_is_blocked` = 1";
  
  $sql_prepare = $wpdb->prepare(
    $sql,
    $ip_address,
    date_i18n('Y-m-d H:i:s'),
    ls_get_ip(),
    $current_user_id
    );
  
  // execute the query
  $result = $wpdb->query( $sql_prepare );
  
  if ($result) {
    return $result;
  }
  
  return false;
}


/**
 * Check if an IP address in on the blacklist
 * 
 * @param str $ip_address
 */
function ls_db_get_block_by_ip( $ip_address='' ) {
  
  global $wpdb;
  
  // query to see if the IP is on the blacklist
  $sql = "SELECT wlab_id, wlab_blocked_visits
    FROM `" . LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST . "` 
    WHERE wlab_blocked_ip = '%s'
    AND   wlab_is_blocked = 1";
  
  $prepared_query = $wpdb->prepare( $sql, $ip_address );
  
  // execute the query
  return $wpdb->get_row( $prepared_query, ARRAY_A );
}

