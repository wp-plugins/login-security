<?php

/**
 * Init the AJAX
 */
function ls_ajax_func() {
  
  $handle = 'jquery';     // AJAX Name
  $object_name = 'wls'; // Vars Namespace
  $l10n = array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'nonce'   => wp_create_nonce(LS_NONCE),
  );
  
  wp_localize_script( $handle, $object_name, $l10n );
}
add_action('admin_enqueue_scripts', 'ls_ajax_func');


// Hook the js action with the PHP function to handle the AJAX
add_action('wp_ajax_ls_failed_ip', 'ls_ajax_actions_func');


/**
 * Handle the javascript call
 */
function ls_ajax_actions_func() {
  
  $nonce = (isset($_POST['nonce']) ? $_POST['nonce'] : '');
  
  // Check if the nonce is correct
  if (!wp_verify_nonce($nonce, LS_NONCE)) {
    die('Error!');
  }
  
  global $wpdb;
  
  // get the checked value
  $checked    = (isset($_POST['checked']) ? esc_attr(trim($_POST['checked'])) : '');
  $ip_address = (isset($_POST['ip'])      ? esc_attr(trim($_POST['ip'])) : '');
  
  // Default value for the JSON message
  $result = array(
    'status'  => 1,
    'message' => __('Oups, there is an error to add/remove an IP address from the blacklist.', 'login_security')
  );
  
  // Adapt the QUERY depending on the checkbox status
  if ($checked == '1') {
    // Checkbox is checked : add IP to the blacklist
    
    // insert data in db (or UPDATE if it already exists)
    ls_db_insert_ip_to_blacklist($ip_address);
    
  } elseif ($checked == '0') {
    // Checkbox is checked : remove IP to the blacklist
    
    // Log datetime + IP + user that disabled this IP
    $data = array( 'wlab_is_blocked' => '0' );
    $where = array( 'wlab_blocked_ip' => $ip_address );
    
    // UPDATE in DB
    $wpdb->update( LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST, $data, $where );
    
  }
  
  // Says it works
  $result['status'] = 0;
  
  // Send the results
  ls_ajax_ex($result);
  
  // always use exit
  exit;
}


/**
 * Return an AJAX data with JSON encoded
 * 
 * @param array $return
 */
function ls_ajax_ex( array $return = array() ) {
  header( 'Content-Type: application/json' );
  echo json_encode($return);
  exit;
}

