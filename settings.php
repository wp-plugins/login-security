<?php
// Get the page number
$current_page_number = (int) (isset($_GET['page_number']) ? $_GET['page_number'] : 1);
?>
<div class="wrap">
  
  <div id="icon-options-general" class="icon32"></div>
  <h2><?php _e('Login Security', 'login_security'); ?></h2>
  
  <?php ls_show_tabs(); ?>
  
  <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
      <!-- main content -->
      <div id="post-body-content">
        <div class="meta-box-sortables ui-sortable">
          <div class="postbox">

<?php
$current_tab = ls_get_current_tab();
switch ($current_tab) {
  // MAIN
  case 'main':
    ?>
<!--[if lte IE 8]><script type="text/javascript" src="<?php echo LS_USER_PLUGIN_URL; ?>/js/excanvas.min.js"></script><![endif]-->
<script type="text/javascript" src="<?php echo LS_USER_PLUGIN_URL; ?>/js/jquery.flot.min.js"></script>
<script type="text/javascript" src="<?php echo LS_USER_PLUGIN_URL; ?>/js/jquery.flot.categories.min.js"></script>

<script type="text/javascript">
jQuery(function() {
  <?php
  // Get the attacks stats
  $days_stats = ls_db_get_log_stats_days_fail(7);
  $months_stats = ls_db_get_log_stats_months_fail(12);
  
  // get the list of the previous days
  $days = ls_get_last_days(7);
  $months = ls_get_last_months(12);
  
  // sort the days
  krsort($days);
  krsort($months);
  
  // List for each day
  $data_day_js = array();
  foreach ( $days as $day ) {
    $data_day_js[] = '["'.$day.'", '.(isset($days_stats[$day]) ? $days_stats[$day] : '0').']';
  }
  $data_month_js = array();
  foreach ( $months as $month ) {
    $data_month_js[] = '["'.$month.'", '.(isset($months_stats[$month]) ? $months_stats[$month] : '0').']';
  }
  ?>
  var data_days = [ <?php echo implode(',', $data_day_js); ?> ];
  var data_months = [ <?php echo implode(',', $data_month_js); ?> ];
  
  jQuery.plot("#wls-chart-7days", [ data_days ], {
    series: {
      bars: {
        show: true,
        barWidth: 0.6,
        align: "center"
      }
    },
    xaxis: {
      mode: "categories",
      tickLength: 0
    }
  });
  jQuery.plot("#wls-chart-12months", [ data_months ], {
    series: {
      bars: {
        show: true,
        barWidth: 0.6,
        align: "center"
      }
    },
    xaxis: {
      mode: "categories",
      tickLength: 0
    }
  });
});
</script>

  <h3><span><?php _e('Attacks during the last 7 days', 'login_security'); ?></span></h3>
  <div class="inside">

  <div class="wls-chart">
    <div id="wls-chart-7days" style="height:200px;"><!-- --></div>
  </div>
  </div><!-- .inside -->
</div><!-- .postbox -->

<div class="postbox">
  <h3><span><?php _e('Attacks during the last 12 months', 'login_security'); ?></span></h3>
  <div class="inside">

  <div class="wls-chart">
    <div id="wls-chart-12months" style="height:200px;"><!-- --></div>
  </div>

    <?php
    break;
  
  
  // Login failed
  case 'login_fail':
    ?>
  <h3><span><?php _e('Failed login', 'login_security'); ?></span></h3>
  <div class="inside">

<?php
$colunms = array();
$colunms['th-date']       = __('Date', 'login_security');
$colunms['th-ip']         = __('IP', 'login_security');
$colunms['th-login']      = __('Login used', 'login_security');
$colunms['th-user_agent'] = __('User agent', 'login_security');
$colunms['th-referer']    = __('Referer', 'login_security');

// get the logs of all success login
$items = ls_db_get_log_fail( $current_page_number );
?>
<table class="wp-list-table widefat fixed">
  <?php
  // Display the table head
  ls_bo_table_thead($colunms);
  ?>
  <tbody>
  <?php
  if (!empty($items)) :
    // Get the pagination
    $paginate_links = ls_bo_paginate_links( $current_page_number );
    
    // Table pagination
    ls_bo_pagination_table( count($colunms), $paginate_links );
    
    // Display all the items
    foreach ($items as $k => $item) : ?>
      <tr id="item_<?php echo $item['wla_id'];?>"<?php echo ($k%2===0 ? ' class="alternate"' : '');?>>
        <td><?php echo ls_format_datetime( $item['wla_date'] ); ?></td>
        <td><?php echo $item['wla_ip']; ?></td>
        <td><?php echo $item['wla_failed_login']; ?></td>
        <td><?php echo $item['wla_user_agent']; ?></td>
        <td><?php echo $item['wla_referer']; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php
    // Table pagination
    ls_bo_pagination_table( count($colunms), $paginate_links );
    ?>
  <?php else: ?>
    <tr>
      <td colspan="<?php echo count($colunms); ?>"><?php _e('Empty', 'login_security'); ?></td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>

    <?php
    break;
  
  
  // Login failed grouped by IP
  case 'login_fail_ip':
    ?>
  <h3><span><?php _e('Failed login by IP', 'login_security'); ?></span></h3>
  <div class="inside">

<?php
$colunms = array();
$colunms['th-ip']       = __('IP', 'login_security');
$colunms['th-count']    = __('Count', 'login_security');
$colunms['th-date-min'] = __('First date', 'login_security');
$colunms['th-date-max'] = __('Last date', 'login_security');
$colunms['th-action']   = __('Block the IP address', 'login_security');

// get the logs of all success login
$items = ls_db_get_log_fail_by_ip( $current_page_number );
?>
<table class="wp-list-table widefat fixed">
  <?php
  // Display the table head
  ls_bo_table_thead($colunms);
  ?>
  <tbody>
  <?php
  if (!empty($items)) :
    // Get the pagination
    $paginate_links = ls_bo_paginate_links( $current_page_number );
    
    // Get current IP address
    $current_ip = ls_get_ip();
    
    // Table pagination
    ls_bo_pagination_table( count($colunms), $paginate_links );
    
    // Display all the items
    foreach ($items as $k => $item) : /* Display all the items */ ?>
      <tr<?php echo ($k%2===0 ? ' class="alternate"' : '');?>>
        <td><?php echo $item['wla_ip']; ?></td>
        <td><?php echo $item['wla_ip_count']; ?></td>
        <td><?php echo ls_format_datetime( $item['wla_date_min'] ); ?></td>
        <td><?php echo ls_format_datetime( $item['wla_date_max'] ); ?></td>
        <td>
			<?php
			if ( $current_ip != $item['wla_ip'] ) {
				// It's not the current IP address, so display the action button
				$html_checked = (!empty($item['wlab_id']) ? ' checked="checked"': '');
				?>
                <input type="checkbox" class="wls-cb-ip" 
					name="ls_ip[<?php echo $item['wla_ip']; ?>]" 
					value="<?php echo $item['wla_ip']; ?>"<?php echo $html_checked; ?> />
				<?php
			} else {
				// It's the current IP address : unable to block the IP
				_e('Current IP', 'login_security');
			}
			?>
		</td>
      </tr>
    <?php endforeach; ?>
    <?php
    // Table pagination
    ls_bo_pagination_table( count($colunms), $paginate_links );
    ?>
  <?php else: ?>
    <tr>
      <td colspan="<?php echo count($colunms); ?>"><?php _e('Empty', 'login_security'); ?></td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>

<script type="text/javascript">
jQuery(".wls-cb-ip").change(function() {
  var c = this.checked ? '1' : '0';
  // send an ajax request
  jQuery.ajax({
    type: "POST",
    url: wls.ajaxurl,
    data: {
      action : "ls_failed_ip", // action defined with the hook
      nonce : wls.nonce, // send nonce
      checked : c, // is checkbox checked ?
      ip : jQuery(this).val() // IP adress
    },
    success: function( response ) {
      if ( response.status != '0' ) {
        if ( typeof response.message != 'undefined' ) {
          alert(response.message);
        } else {
          alert(response);
        }
      }
    },
    error: function() {
      alert("<?php _e('Oups, there is an error', 'login_security'); ?>");
    }
  });
});
</script>

    <?php
    break;
  
  
  // LOGIN SUCCEED
  case 'login_success':
    ?>
  <h3><span><?php _e('Successfull login', 'login_security'); ?></span></h3>
  <div class="inside">

<?php
$colunms = array();
$colunms['th-date']       = __('Date', 'login_security');
$colunms['th-user']       = __('User', 'login_security');
$colunms['th-ip']         = __('IP', 'login_security');
$colunms['th-user_agent'] = __('User agent', 'login_security');
$colunms['th-referer']    = __('Referer', 'login_security');

// get the logs of all success login
$items = ls_db_get_log_success( $current_page_number );
?>
<table class="wp-list-table widefat fixed">
  <?php
  // Display the table head
  ls_bo_table_thead($colunms);
  ?>
  <tbody>
  <?php
  if (!empty($items)) :
    // Get the pagination
    $paginate_links = ls_bo_paginate_links( $current_page_number );
    
    // Table pagination
    ls_bo_pagination_table( count($colunms), $paginate_links );
    
    // Display all the items
    foreach ($items as $k => $item) : /* Display all the items */ ?>
      <tr id="item_<?php echo $item['wla_id'];?>"<?php echo ($k%2===0 ? ' class="alternate"' : '');?>>
        <td><?php echo ls_format_datetime( $item['wla_date'] ); ?></td>
        <td><?php echo ls_format_user( $item['wla_logged_wp_user_id'] ); ?></td>
        <td><?php echo $item['wla_ip']; ?></td>
        <td><?php echo $item['wla_user_agent']; ?></td>
        <td><?php echo $item['wla_referer']; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php
    // Table pagination
    ls_bo_pagination_table( count($colunms), $paginate_links );
    ?>
  <?php else: ?>
    <tr>
      <td colspan="<?php echo count($colunms); ?>"><?php _e('Empty', 'login_security'); ?></td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>

    <?php
    break;
  
  // DEFAULT
  default:
    break;
}
?>

            </div><!-- .inside -->
          </div><!-- .postbox -->
        </div><!-- .meta-box-sortables .ui-sortable -->
      </div><!-- post-body-content -->
      <!-- sidebar -->
      <div id="postbox-container-1" class="postbox-container">
        <div class="meta-box-sortables">
          <div class="postbox">
          <h3><span><?php _e('About', 'login_security'); ?></span></h3>
          <div style="padding:0 5px;">
            <?php
            // Check language
            $fr_lang = array('fr_FR', 'fr_BE', 'fr_CH', 'fr_LU', 'fr_CA');
            $is_fr = (in_array(WPLANG, $fr_lang) ? true : false);
            
            // Get the URL author depending on the language
            $url_author = ( $is_fr===true ? 'http://tonyarchambeau.com/' : 'http://en.tonyarchambeau.com/' );
            ?>
            <p><?php printf(__('Plugin developed by <a href="%1$s">Tony Archambeau</a>.', 'login_security'), $url_author); ?></p>
            <?php
            $url_paypal = 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=FQKK22PPR3EJE&lc=GB&item_name=Login%20Security&item_number=login%2dsecurity&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted';
            ?>
            <p><a href="<?php echo $url_paypal; ?>"><?php _e('Donate', 'login_security'); ?></a></p>
          </div>
          </div><!-- .postbox -->
        </div><!-- .meta-box-sortables -->
      </div><!-- #postbox-container-1 .postbox-container -->
    </div><!-- #post-body .metabox-holder .columns-2 -->
    <br class="clear" />
  </div><!-- #poststuff -->
</div><!-- .wrap -->
