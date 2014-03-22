<?php
/**
Plugin Name: Login Security
Plugin URI: http://tonyarchambeau.com/
Description: Improves the security of the login page against brute-force attacks. Records every attempts to login. Easily block an IP address.
Version: 1.0.2
Author: Tony Archambeau
Author URI: http://tonyarchambeau.com/
Text Domain: wp-login-security
Domain Path: /languages

*/


load_plugin_textdomain( 'login_security', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


/***************************************************************
 * Define
 ***************************************************************/


if ( !defined('LS_USER_NAME') )       { define('LS_USER_NAME', basename(dirname(__FILE__)) ); }
if ( !defined('LS_USER_PLUGIN_DIR') ) { define('LS_USER_PLUGIN_DIR', WP_PLUGIN_DIR .'/'. LS_USER_NAME ); }
if ( !defined('LS_USER_PLUGIN_URL') ) { define('LS_USER_PLUGIN_URL', WP_PLUGIN_URL .'/'. LS_USER_NAME ); }

if ( !defined('LS_DB_TABLE_LOGIN_ACCESS') ) {
  global $table_prefix;
  define('LS_DB_TABLE_LOGIN_ACCESS', $table_prefix.'login_access' );
}

if ( !defined('LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST') ) {
  global $table_prefix;
  define('LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST', $table_prefix.'login_access_blacklist' );
}

if ( !defined('LS_DB_VERSION') ) { define('LS_DB_VERSION', '1.0' ); }
if ( !defined('LS_NONCE') ) { define( 'LS_NONCE', 'bVAyLuaOQIiV8' ); }

if ( !defined('LS_DONATE_LINK') ) {
	define('LS_DONATE_LINK', 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=FQKK22PPR3EJE&lc=GB&item_name=Login%20Security&item_number=login%2dsecurity&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted');
}



/***************************************************************
 * INCLUDE
 ***************************************************************/

include_once 'functions-ajax.php';
include_once 'functions-db.php';



/***************************************************************
 * Install and uninstall
 ***************************************************************/


/**
 * Hooks for install
 */
if ( function_exists('register_uninstall_hook') ) {
  register_deactivation_hook(__FILE__, 'ls_uninstall');
}


/**
 * Hooks for uninstall
 */
if ( function_exists('register_activation_hook') ) {
  register_activation_hook(__FILE__, 'ls_install');
}


/**
 * Install this plugin
 */
function ls_install() {
  global $wpdb;
  
  // Define the CHARSET for the table
  $charset_collate = '';
  if ( !empty($wpdb->charset) ) {
    $charset_collate = "DEFAULT CHARACTER SET ".$wpdb->charset;
  }
  if ( !empty($wpdb->collate) ) {
    $charset_collate .= " COLLATE ".$wpdb->collate;
  }
  
  $sql = "CREATE TABLE `".LS_DB_TABLE_LOGIN_ACCESS."` (
 `wla_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `wla_date` datetime DEFAULT NULL,
 `wla_ip` varchar(46) DEFAULT NULL,
 `wla_is_failed` tinyint(4) NOT NULL DEFAULT 0,
 `wla_user_agent` varchar(255) NOT NULL,
 `wla_referer` varchar(255) NOT NULL,
 `wla_logged_wp_user_id` bigint(20) unsigned DEFAULT NULL,
 `wla_failed_login` varchar(60) DEFAULT NULL,
 `wla_failed_pass_md5` varchar(64) DEFAULT NULL,
 PRIMARY KEY (`wla_id`),
 KEY `wla_date` (`wla_date`,`wla_ip`,`wla_is_failed`)
) $charset_collate;
  CREATE TABLE `".LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST."` (
  `wlab_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wlab_blocked_ip` varchar(46) NOT NULL,
  `wlab_added_date` datetime DEFAULT NULL,
  `wlab_added_ip` varchar(46) DEFAULT NULL,
  `wlab_added_by_wp_user_id` bigint(20) unsigned DEFAULT NULL,
  `wlab_blocked_visits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `wlab_is_blocked` tinyint(3) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`wlab_id`),
  UNIQUE KEY `wlab_block_ip` (`wlab_blocked_ip`),
  KEY `wlab_is_blocked` (`wlab_is_blocked`)
) $charset_collate;";
  
  // Install or update
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta($sql);
  
  // Get the current DB version
  $installed_db_version = get_option( 'ls_db_version' );
  
  if ( empty($installed_db_version) ) {
    // Add DB version on DB WordPress
    add_option( 'ls_db_version', LS_DB_VERSION );
  } else {
    // Update DB version on DB WordPress
    update_option( 'ls_db_version', LS_DB_VERSION );
  }
}


/**
 * Uninstall this plugin
 */
function ls_uninstall() {
  
  // @TODO uninstall only if user decided to ! ()
  
}


/**
 * Check if an UPDATE is necessary
 */
function ls_update_check() {
  
  // get the current DB version
  $db_version = get_site_option( 'ls_db_version' );
  
  // Update the DB structure if the DB version changed
  if ( !empty($db_version) && $db_version != LS_DB_VERSION ) {
    ls_install();
  }
}
add_action( 'plugins_loaded', 'ls_update_check' );



/***************************************************************
 * Menu + settings page
 ***************************************************************/


/**
 * Add menu on the Back-Office for the plugin
 */
function ls_add_options_page() {
  
  if ( function_exists('add_options_page') ) {
    $page_title = __('Login Security', 'login_security');
    $menu_title = __('Login Security', 'login_security');
    $capability = 'administrator';
    $menu_slug = plugin_basename(__FILE__);
    $function = 'ls_add_settings_page'; // function that contain the page
    add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function );
  }
  
}
add_action('admin_menu', 'ls_add_options_page');


/**
 * Add the settings page
 * 
 * @return boolean
 */
function ls_add_settings_page() {
  $path = trailingslashit(dirname(__FILE__));
  
  if ( !file_exists( $path . 'settings.php') ) {
    return false;
  }
  require_once($path . 'settings.php');
}


/**
 * Additional links on the plugin page
 *
 * @param array $links
 * @param str $file
 */
function ls_plugin_row_meta( $links, $file ) {
  if ( $file == plugin_basename(__FILE__) ) {
    $settings_page = plugin_basename(__FILE__);
    $links[] = '<a href="options-general.php?page=' . $settings_page .'">' . __('Settings','login_security') . '</a>';
    $links[] = '<a href="' . LS_DONATE_LINK . '">'.__('Donate', 'login_security').'</a>';
  }
  return $links;
}
add_filter('plugin_row_meta', 'ls_plugin_row_meta',10,2);


/**
 * Display pagination links for Back-Office
 * 
 * @param int $current_page
 * @param int $items_per_page
 * @return Ambigous <multitype:, string, void, multitype:string >
 */
function ls_bo_paginate_links( $current_page = 1, $items_per_page = 30 ) {
  global $wpdb;
  
  // Get the total number of rows in a query
  $count_rows = $wpdb->get_row('SELECT FOUND_ROWS() AS count_rows');
  $total_rows = $count_rows->count_rows;
  
  // Define the base URL
  $base_url = '';
  foreach($_GET as $k => $v) {
    if ($k != 'page_number') {
      $base_url .= (empty($base_url) ? '?' : '&amp;');
      $base_url .= $k . '=' . $v;
    }
  }
  
  // Get siteurl
  $siteurl = get_option('siteurl');
  
  // Args to get the pagination links
  $args = array(
      'base'         => $siteurl.'/wp-admin/admin.php' . $base_url . '%_%',
      'format'       => '&page_number=%#%',
      'total'        => ceil($total_rows / $items_per_page),
      'current'      => $current_page,
      'prev_text'    => __('Previous', 'login_security'),
      'next_text'    => __('Next', 'login_security'),
  );
  
  // return pagination links
  return paginate_links( $args ).' '.sprintf(__('(%1$s results)', 'login_security'), $total_rows);
}


/**
 * Display the header of the table
 * 
 * @param array $columns
 */
function ls_bo_table_thead( array $columns = array() ) {
  ?>
  <thead>
    <tr>
      <?php foreach($columns as $column) :?>
        <th><?php echo $column; ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <?php
}


/**
 * Display the table element for the pagination
 * 
 * @param int $columns_count
 * @param str $paginate_links
 */
function ls_bo_pagination_table($columns_count, $paginate_links) {
  ?>
  <tr>
    <td colspan="<?php echo $columns_count; ?>"><div class="pagination pagination-left"><?php echo $paginate_links; ?></div></td>
  </tr>
  <?php
}


/*******************************************************************
 * BLOCK IP ADRESS
 *******************************************************************/

/**
 * Check if a visitor with a blocked IP address tries to access the website
 */
function ls_is_ip_allowed() {
  
  // Get the current IP
  $current_ip = ls_get_ip();
  
  // Query to know if the current IP is on the list of blocked
  $blocked_info = ls_db_get_block_by_ip( $current_ip );
  
  // If the current IP is blocked : EXIT
  if ($blocked_info == null) {
    return '';
  }
  
  global $wpdb;
  
  // increment visits
  $data = array( 'wlab_blocked_visits' => ($blocked_info['wlab_blocked_visits'] + 1) );
  $where = array();
  $where['wlab_blocked_ip'] = $current_ip;
  $where['wlab_is_blocked'] = 1;
  
  // Query to update in DB
  $wpdb->update( LS_DB_TABLE_LOGIN_ACCESS_BLACKLIST, $data, $where );
  
  // Block and display a message
  $message = __('Oups, your IP address was blocked for security reason. Please contact the administrator if you want that your IP to be unlocked.', 'login_security');
  exit( $message );
  return;
}
// very early hook in wordpress, to check if the IP address is allowed
add_action('plugins_loaded', 'ls_is_ip_allowed');



/***************************************************************
 * Tabs
 ***************************************************************/


/**
 * Get the current tab
 * 
 * @return Ambigous <string, mixed>|string
 */
function ls_get_current_tab() {
  if (isset($_GET['tab'])) {
    return esc_html($_GET['tab']);
  } else {
    return 'main';
  }
}


/**
 * Display the tabs
 */
function ls_show_tabs() {
  global $wp_db_version;
  
  // Get the current tab
  $current_tab = ls_get_current_tab();
  
  // All tabs
  $tabs = array();
  $tabs['main']          = __('Main', 'login_security');
  $tabs['login_fail']    = __('Failed login', 'login_security');
  $tabs['login_fail_ip'] = __('Failed login by IP', 'login_security');
  $tabs['login_success'] = __('Successfull login', 'login_security');
  
  // Generate the tab links
  $tab_links = array();
  foreach ($tabs as $tab_k => $tab_name) {
    $tab_curent = ($tab_k === $current_tab ? ' nav-tab-active' : '' );
    $tab_url = '?page=' . plugin_basename(__FILE__) .'&amp;tab='.$tab_k;
    $tab_links[] = '<a class="nav-tab'.$tab_curent.'" href="'.$tab_url.'">'.$tab_name.'</a>';
  }
  
  // Since the 25 oct. 2010 WordPress include the tabs (in CSS)
  // The 25 oct. 2010 = WordPress version was "3.1-alpha"
  if ( $wp_db_version >= 15477 ) {
    // Tabs in CSS
    ?>
    <h2 class="nav-tab-wrapper">
      <?php echo implode("\n", $tab_links); ?>
    </h2>
    <?php
  } else {
    // Tabs without CSS (instead, separate links with "|")
    ?>
    <div>
      <?php echo implode(' | ', $tab_links); ?>
    </div>
    <?php
  }
  
  return;
}



/***************************************************************
 * Main function
 ***************************************************************/


/**
 * Get the real IP address of the user
 */
function ls_get_ip() {
  
  // init
  $ip_address = '';
  
  // IP if internet is shared
  if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
  }
  // IP behind a proxy
  elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  // Normal IP
  elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
  }
  // Otherwise : empty string
  else {
    $ip_address = '';
  }
  
  // Check if there are multiple IP adresse
  if (strpos($ip_address, ',') !== false) {
    $ip_address = explode(',', $ip_address);
    $ip_address = current($ip_address);
  }
  
  // Return the real IP
  return esc_attr($ip_address);
}


/**
 * Get the user agent
 */
function ls_get_user_agent() {
  return (isset($_SERVER['HTTP_USER_AGENT']) ? esc_attr(trim($_SERVER['HTTP_USER_AGENT'])) : '');
}


/**
 * Get the HTTP referer
 */
function ls_get_referer() {
  return (isset($_SERVER['HTTP_REFERER'])    ? esc_attr(trim($_SERVER['HTTP_REFERER'])) : '');
}


/**
 * Return the datetime
 * 
 * @param str $datetime
 */
function ls_format_datetime( $datetime ) {
  
  // Verify if there is an empty string
  if ( empty($datetime) ) {
	return '';
  }
  
  // Get the date_format and time_format
  $date_format = get_option( 'date_format' );
  $time_format = get_option( 'time_format' );
  $date_timestamp = strtotime( $datetime );
  
  // Failed with the strtotime() function
  if ( $date_timestamp==false || $date_timestamp==-1 ) {
    return '';
  }
  
  // Display the date with the date_format and time_format
  return sprintf(
	__('%1$s at %2$s', 'login_security'),
	date( $date_format, $date_timestamp ),
	date( $time_format, $date_timestamp )
	);
}


/**
 * Get an array of the last X days
 * 
 * @param int $days_count
 */
function ls_get_last_days( $days_count = 7 ) {
  
  // init
  $dates_array = array();
  $current_year  = date_i18n('Y');
  $current_month = date_i18n('m');
  $current_day   = date_i18n('d');
  
  // list previous X days
  for ( $i=1 ; $i<=$days_count ; $i++ ) {
    $dates_array[] = date('Y-m-d', mktime(0, 0, 0, $current_month, ($current_day-$i), $current_year));
  }

  // return the array
  return $dates_array;
}


/**
 * Get an array of the last X months
 * 
 * @param int $months_count
 */
function ls_get_last_months( $months_count = 12 ) {
  
  // init
  $dates_array = array();
  
  // list previous X days
  for ($i = 0 ; $i < $months_count ; $i++) {
    $dates_array[] = date('Y-m', strtotime( date_i18n( 'Y-m-01' ).' -'.$i.' months'));
  }

  // return the array
  return $dates_array;
}


/**
 * Get a user through an ID
 * 
 * @param int $wp_user_id
 */
function ls_format_user( $wp_user_id=null ) {
  
  // query to get the user by it's ID
  $user = get_user_by( 'id', $wp_user_id );
  
  if ( $user != false ) {
	// return the user info
	return sprintf(
	  __('%1$s (%2$s)', 'login_security'),
	  $user->display_name,
	  $user->user_email
	  );
  } else {
	// return a message to explain the user is unknow
    return __('Unknown user', 'login_security');
  }
}



/***************************************************************
 * Login success or failed
 ***************************************************************/


/**
 * Save a log each time a user login successfully
 *
 * @param str $user_login
 * @param object $user
 */
function ls_login_succeed( $user_login, $user ) {
  
  global $wpdb;
  
  // Data log
  $dataLog = array();
  $dataLog['wla_date']              = date_i18n('Y-m-d H:i:s');
  $dataLog['wla_ip']                = ls_get_ip();
  $dataLog['wla_is_failed']         = 0;
  $dataLog['wla_user_agent']        = ls_get_user_agent();
  $dataLog['wla_referer']           = ls_get_referer();
  $dataLog['wla_logged_wp_user_id'] = isset($user->ID) ? $user->ID : 0;
  
  // Query to insert in DB
  $wpdb->insert( LS_DB_TABLE_LOGIN_ACCESS, $dataLog );
  
}
add_action( 'wp_login', 'ls_login_succeed', 10, 2 );


/**
 * Save a log each time a user failed to login
 *
 * @param str $username
 */
function ls_login_failed( $username ) {
  
  global $wpdb;
  
  // Data log
  $dataLog = array();
  $dataLog['wla_date']         = date_i18n('Y-m-d H:i:s');
  $dataLog['wla_ip']           = ls_get_ip();
  $dataLog['wla_is_failed']    = 1;
  $dataLog['wla_user_agent']   = ls_get_user_agent();
  $dataLog['wla_referer']      = ls_get_referer();
  $dataLog['wla_failed_login'] = $username;
  
  // try to get the user if it exists
  $user = get_user_by( 'login', $username );
  if ( $user != false ) {
    // if there is auser with this login, save user password (md5)
    // this can help in a futur to know if the user password should be modified
    $dataLog['wla_failed_pass_md5'] = (isset($user->user_pass) ? $user->user_pass : '');
  }
  
  // Query to insert in DB
  $wpdb->insert( LS_DB_TABLE_LOGIN_ACCESS, $dataLog );
  
}
add_action( 'wp_login_failed', 'ls_login_failed' );

