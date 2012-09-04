<?php
/*
 * @author      Bryan Shanaver <bryan[at]fiftyandfifty[dot]org>
 * @version     1.5.0
 */

class TELEPORTD_CACHE {
  
  var $api_options = array(
    'teleportd' => array(
      'api_scheme' => 'http',
      'api_host' => 'v1.api.teleportd.com',
      'api_port' => '8080',
      'api_endpoint' => 'search?string=%string%?',
      'auth_type' => 'apikey'
    ),
    'instagram' => array(
      'api_scheme' => 'https',
      'api_host' => 'api.instagram.com',
      'api_port' => '',
      'api_endpoint' => 'v1/tags/%string%/media/recent?',
      'auth_type' => 'client_id'
    ),
    'twitter' => array(
      'api_scheme' => 'http',
      'api_host' => 'search.twitter.com',
      'api_port' => '',
      'api_endpoint' => 'search.json?q=%string%',
      'auth_type' => ''
    ),
    'youtube' => array(
      'api_scheme' => 'https',
      'api_host' => 'gdata.youtube.com',
      'api_port' => '',
      'api_endpoint' => 'feeds/api/videos?q=%string%',
      'auth_type' => ''
    ),
  );
  
  var $teleportd, $instagram, $twitter, $youtube;
  
  function __construct() {
    $this->teleportd = new PLATFORM_TELEPORTD();
    $this->instagram = new PLATFORM_INSTAGRAM();
    $this->twitter = new PLATFORM_TWITTER();
    $this->youtube = new PLATFORM_YOUTUBE();
    add_action('admin_menu', array(&$this, 'admin_menu'));
    $this->create_posttype_and_taxonomy();
  }
  
  function choose_platform($name){
    switch( $name ) {
      case "instagram":
        $platform = $this->instagram;
        break;
      case "teleportd":
        $platform = $this->teleportd;
        break;
      case "twitter":
        $platform = $this->twitter;
        break;
      case "youtube":
        $platform = $this->youtube;
        break;
      default:
        //wp_die( __('Missing Platform class') );
    }
    return $platform;    
  }
  
  function admin_menu() {
    $page = add_options_page('Teleportd Cache Settings', 'Teleportd Cache', 'manage_options', 'teleportd-cache-api', array(&$this, 'admin_options'));
  }
    
  function admin_options() {
    if (!current_user_can('manage_options'))  {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
       
    if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], "update-options")) {
      $this->save_option('teleportd-cache', $_POST['teleportd_cache'] );
      $this->save_option('teleportd-blacklist', $_POST['teleportd_blacklist'] );
      $this->save_option('teleportd-teleportd-always-private', $_POST['always_private'] );
      $this->save_option('teleportd-teleportd-max-items', $_POST['max_items'] );
      $this->save_option('teleportd-debug-on', $_POST['debug_on'] );
    }
    $all_plugin_options = $this->get_plugin_options();
    
    if( is_numeric($_REQUEST['delete_api']) ){
      foreach( $all_plugin_options as $cache_num => $option_settings ){
        if( $_REQUEST['delete_api'] != $cache_num ){
          $rebuild_api_options[] = $option_settings;
        }
      }
      $this->save_option('teleportd-cache', $rebuild_api_options );
      $all_plugin_options = $rebuild_api_options;
    }
    
    // print "<pre>";
    // print_r($all_plugin_options);
    // print "</pre>";
    
    if( $_REQUEST['add_api'] == 'true' ){
      $all_plugin_options[] = array(
        'api_selected' => $_REQUEST['api_option'],
        'api_authentication' => '',
        'search_name' => $_REQUEST['api_option'],
        'string' => '',
        'period' => '',
        'location' => '',
        'cron_interval' => 'manual'
      );
    }
        
?>


    
<div class="wrap">
  <div id="icon-options-general" class="icon32"><br /></div>
  <h2>Teleportd Picture Cache</h2>
  <form action="options-general.php?page=teleportd-cache-api" method="post" id="teleportd_form">
    <?php wp_nonce_field('update-options'); ?>
    <input type="hidden" name="delete_api" id="delete_api" />
      
<?php if( is_array($all_plugin_options[0] ) ): ?>    
    
    <h3>Global Settings</h3>

	  <table id="all-plugins-table" class="widefat">   
      <thead>
        <tr>
          <th class="manage-column" scope="col">All APIs Inherit These Settings</th>
          <th class="manage-column" scope="col"> </th>
        </tr>
      </thead>
      <tbody class="plugins">
        <tr class="active">
          <td class="desc">
        	  <select name="debug_on" class="disable_onchange" >
        	    <option value="No" <?php selected( $all_plugin_options[debug_on], false ); ?>>No</option>
        	    <option value="Yes" <?php selected( $all_plugin_options[debug_on], true ); ?>>Yes</option>
        		</select> 
          </td>
      	  <th scope="row">
        		<label for="">Turn debug ON</label><br/>
        		<code>Debugging info will be sent to the javascript console when you run manual tests</code>
        	</th>
        </tr>      
        <tr class="active">
          <td class="desc">
        	  <select name="always_private" class="disable_onchange" >
        	    <option value="No" <?php selected( $all_plugin_options[always_private], 'No' ); ?>>No</option>
        	    <option value="Yes" <?php selected( $all_plugin_options[always_private], 'Yes' ); ?>>Yes</option>
        		</select> 
          </td>
      	  <th scope="row">
        		<label for="">Set new items as private by default</label><br/>
        		<code>You will need to review new items and set them to public</code>
        	</th>
        </tr>
        <tr class="active">
          <td class="desc">
            <p><input type="text" name="max_items" value="<?php print ( is_numeric($all_plugin_options[max_items]) ? $all_plugin_options[max_items] : '0') ?>" class="regular-text disable_onchange" /></p>
          </td>
      	  <th scope="row">
        		<label for="">Max number of items to get per API</label><br/>
        		<code>Set to 0 for no max - this may take a long time to run since some services only let you grab 50 at a time.</code>
        	</th>
        </tr>  
        <tr class="active">
          <td class="desc">
            <p><textarea name="teleportd_blacklist" cols="80" rows="4"><?php print $all_plugin_options[blacklisted_users] ?></textarea></p>
          </td>
      	  <th scope="row">
        		<label for="">Blacklisted usernames/handles</label><br/>
        		<code>comma separated</code>
        	</th>
        </tr>
      </tbody>
		</table>   
		
<?php endif; ?>  
		
		<div style="width:100%;height:20px"></div> 
		
    <select name="api_option" style="width:100px">
<?php foreach( $this->api_options as $option => $option_settings ): ?>
      <option value="<?php print $option ?>"><?php print $option ?></option>
<?php endforeach; ?>
    </select> 
    <input type="hidden" name="add_api" id="add_api" />
    <a href="javascript:add_an_api();" class="button-secondary">Add an API</a>
    
    <div style="width:100%;height:20px"></div>
    
    <h3>API Settings</h3>
    
<?php 
 
    foreach( $all_plugin_options as $cache_num => $cache_settings):
      if( is_numeric($cache_num) && $cache_settings['api_selected'] != '' ):
      
      $url = $this->build_api_search_url($cache_num);
      $api_urls .= "api_url[{$cache_num}] = '{$url}';\n\t";
   
      $platform = $this->choose_platform($cache_settings['api_selected']);
      if(is_object($platform)){
        $platform->admin_form($cache_settings, $cache_num, $this->api_options);
      }
        
    endif;
  endforeach;

?>

    <style>
      th{width:600px;}
      .remove-div{text-align:right;margin-right:10px}
    </style>
    <script type="text/javascript">
      jQuery("#teleportd_form").children(".widgets-holder-wrap").children(".sidebar-name").click(function() {
          jQuery(this).parent().toggleClass("closed")
      });    
      function add_an_api(){
        jQuery('#teleportd_form #add_api').val('true');
        jQuery('#teleportd_form').submit()
      }
      function delete_an_api(num){
        jQuery('#teleportd_form #delete_api').val(num);
        jQuery('#teleportd_form').submit()
      }
      function test_api(num){
        var api_url = Array();
        <?php print $api_urls ?>
        jQuery.getJSON(api_url[num] + "&format=json&callback=?", function(data){
          window.console && console.debug(api_url[num]);
          window.console && console.debug(data);
          var success = false;
          try{if(data.meta.code == 200){success = true;}}catch(err){}
          try{if(data.status == 'OK'){success = true;}}catch(err){}
          try{if(data.results){success = true;}}catch(err){}
          if(success){
            alert('Success');
          }else{
            alert('Error');
          }
        });
      }
      function run_manually(num){
        window.console && console.debug('run_manually');
        if(jQuery('#teleportd_form .debug').attr('checked')){debug='true';}else{debug='';}
        jQuery.get(('/wp-admin/?run_teleportd_manually=true&num=' + num + '&debug=' + debug), function(data) {
          window.console && console.debug(data);
          jQuery("#run_manually_response").html('');
        }).complete(function() { alert('Success'); });
      }
      jQuery(function() {
        jQuery('.disable_onchange').change(function() {
          jQuery('#test_api').attr("href", "javascript:alert('save changes first')");
          jQuery('#run_manually').attr("href", "javascript:alert('save changes first')");
        });  
      });
    </script>

    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
  </form>
</div>
<?php

  }
  
  function get_available_postypes(){
    $args=array(
      'public'   => true
    ); 
    $output = 'names'; // names or objects, note names is the default
    $operator = 'or'; // 'and' or 'or'
    $post_types = get_post_types( $args, $output, $operator );
    return $post_types;
  }
 
  function save_option($id, $value) {
    $option_exists = (get_option($id, null) !== null);
    if ($option_exists) {
      update_option($id, $value);
    } else {
      add_option($id, $value);
    }
  }  
  
  function import_item($photos, $platform, $platform_options, $plugin_options){
    global $wpdb;
    
    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    $wordpress_uploads = wp_upload_dir();
    
    $blacklist = explode(',', $plugin_options['blacklisted_users']);
    
    $retrieved = 0;
    $added = 0;
    
    try {
  
        foreach( $photos as $num => $photo ) {
          
          $retrieved++;

          if( !$platform->parse_response($photo, $platform_options) ){ continue; }
          
          if( $plugin_options['max_items'] && $retrieved > $plugin_options['max_items'] ){ break; }
          
          // Check to see if this user is blacklisted, skip to the next on if so   
          if( count($blacklist) ){    
            if(  array_search ( $platform->pic_handle, $blacklist ) ){ if($plugin_options['debug_on']){print $platform->pic_full_title . " [not added] blacklisted user: ".$platform->pic_handle." \n";} continue; }
          }

          // Check to see if this is a retweet, skip to the next on if so  
          if( $platform_options['skip_retweets'] == 'Yes' ){
            if(  strstr ( $platform->pic_full_title , 'RT ') ){ if($plugin_options['debug_on']){print $platform->pic_full_title . " [not added] Retweet suspected : ".$platform->pic_full_title." \n";} continue; }
          }

          // Check to see if we already have this photo, skip to the next one if we do
          $existing_photo = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'teleportd_sha' and meta_value = %s", $platform->pic_sha ) );          
          if( count($existing_photo) >= 1 ){ if($plugin_options['debug_on']){print $platform->pic_full_title . " [not added] sha exists: ".$platform->pic_sha." \n";} continue; }
          else{ if($plugin_options['debug_on']){print $platform->pic_full_title." \n";} }

          $added++;
          
          // if there is a thumnail, process the thumbnail, and attach it
          if( $platform->pic_thumb != '' ) {
            $thumb_imagesize = getimagesize($platform->pic_thumb);
            $img_name = strtolower( preg_replace('/[\s\W]+/','-', $platform->pic_clean_title) );
            $image_file = file_get_contents( $platform->pic_thumb );
            $img_filetype = wp_check_filetype( $platform->pic_thumb, null );
            if( !$img_filetype['ext'] ){ 
              $img_filetype['ext'] = 'jpg';
              $img_filetype['type'] = 'image/jpeg';
            }           
            $img_path = $wordpress_uploads['path'] . "/" . $img_name . "." . $img_filetype['ext'];
            $img_sub_path = $wordpress_uploads['subdir'] . "/" . $img_name . "." . $img_filetype['ext'];
            file_put_contents($img_path , $image_file);
          }else{
            unset($full_imagesize);
          }
          
          // if there is a large image, process it
          if( $platform->pic_full != '' ) {
            $full_imagesize = getimagesize($platform->pic_full);
            if($platform->pic_post_content){
              $post_content = $platform->pic_post_content;
            }
            else{
              $post_content = "<img src='".$platform->pic_full ."' alt='".$platform->pic_clean_title ."' />"; 
            }
          }else{
            unset($thumb_imagesize);
            $post_content = $platform->pic_full_title;
          }
          
          $post = array(
           'post_author' => 1,
           'post_date' => $platform->pic_mysqldate ,
           'post_type' => 'teleportd_images',
           'post_title' => $platform->pic_clean_title,
           'post_content' => $post_content,
           'post_status' => ($plugin_options['always_private'] == 'Yes' ? 'private' : 'publish'),
          );
          $post_id = wp_insert_post( $post, true );
          
          add_post_meta($post_id, 'teleportd_sha', $platform->pic_sha, true);
          add_post_meta($post_id, 'teleportd_platform', $platform->pic_handle_platform, true);
          add_post_meta($post_id, 'teleportd_userhandle', $platform->pic_handle, true);
          add_post_meta($post_id, 'teleportd_location', $platform->pic_loc, true);
          if( $platform->vid_embed ) {
            add_post_meta($post_id, 'teleportd_vid_embed', $platform->vid_embed, true);
          }
          if( $platform->pic_full && $full_imagesize ) {
            add_post_meta($post_id, 'teleportd_full_url', $platform->pic_full, true);
            add_post_meta($post_id, 'teleportd_full_imagesize', ('w'.$full_imagesize[0].'xh'.$full_imagesize[1]), true);
          }
          if( $platform->pic_thumb && $thumb_imagesize ) {
            $attachment = array(
             'post_author' => 1,
             'post_date' => $platform->pic_mysqldate ,
             'post_type' => 'attachment',
             'post_title' => $platform->pic_clean_title,
             'post_parent' => $post_id,
             'post_status' => 'inherit',
             'post_mime_type' => $img_filetype['type'],
            );
            $attachment_id = wp_insert_post( $attachment, true );
            add_post_meta($attachment_id, '_wp_attached_file', $img_sub_path, true );
            add_post_meta($post_id, 'teleportd_thumb_url', $platform->pic_thumb, true);
            add_post_meta($post_id, 'teleportd_thumb_imagesize', ('w'.$thumb_imagesize[0].'xh'.$thumb_imagesize[1]), true);
          }
          
          $category_ids = array();
          $tag_ids = array();
          
          // link post to the platform 'category'
          $new_category = term_exists( $platform->pic_handle_platform, 'teleportd_categories');
          if( $new_category ){
            array_push( $category_ids, $new_category['term_id'] );
          }else{
            $new_term = wp_insert_term( $platform->pic_handle_platform, 'teleportd_categories');
            if(!$new_term['errors']){
              array_push( $category_ids, (int)$new_term['term_id'] );
            }
          }
          
          // link post to the api_search_name category
          if( $this->plugin_options[search_name] ){
            $new_category = term_exists( $this->plugin_options[search_name], 'teleportd_categories');
            if( $new_category ){
              array_push( $category_ids, $new_category['term_id'] );
            }else{
              $new_term = wp_insert_term( $this->plugin_options[search_name], 'teleportd_categories');
              if(!$new_term['errors']){
                array_push( $category_ids, (int)$new_term['term_id'] );
              }
            }
          }
          
          // attach these categories to the new post
          if( count($category_ids) ) {
            wp_set_post_terms( $post_id, $category_ids, 'teleportd_categories' );
          }

          // attach these tags to the new post
          if( count($platform->pic_tags) ) {
            wp_set_object_terms($post_id, $platform->pic_tags, 'teleportd_tags');
          }
          
          // attach these tags to the new post
          if( count($platform->pic_strs) ) {
            wp_set_object_terms($post_id, $platform->pic_strs, 'teleportd_tags');
          }
          
      
        }

        print $platform->pic_handle_platform . " complete! " . $retrieved . " records retrieved, " . $added . " records added\t|\t";
    } catch (Exception $e) {
        print 'Error: ' . $e->getMessage();
    }
    
  }
  
  
  function run_teleportd_query($num=0){
    
    $plugin_options = $this->get_plugin_options();
    $platform_options = $plugin_options[$num];
    $platform = $this->choose_platform($platform_options['api_selected']);
    
    $search_url = $this->build_api_search_url($num);
    
    $count_items = 0;
    
    while( strlen($search_url) > 10 ){
      
      if($plugin_options['debug_on']){print("\n\nurl: " . $search_url . " \n");}
      
      $json_string = $this->remote_get_contents($search_url);
      $response = json_decode($json_string);
      $photos = $platform->clean_response($response);
      
      if( !is_array($photos) ){
        return "No results found";
        break;
      }
      
      if($plugin_options['debug_on']){print("count: " . count($photos) . " \n");}

      $this->import_item($photos, $platform, $platform_options, $plugin_options);
      
      $count_items = count($photos) + $count_items;
      if($count_items > $plugin_options['max_items'] && $plugin_options['max_items']){
        if($plugin_options['debug_on']){print("\n\nMax results settings hit: " . $plugin_options['max_items']);}
        break;
      }
      
      $platform->get_next_page($response, $search_url);
      $search_url = $platform->next_page;
      
    }

  }
  
  function build_api_search_url($option_num=0){
    
    $plugin_options = $this->get_plugin_options();
    $api_settings = $this->get_plugin_options($option_num);
    $api_options = $this->api_options[$api_settings['api_selected']];
  
    $query = $api_options['api_scheme'] . "://" . $api_options['api_host'];                                           //| http://v1.api.teleportd.com
    if( $api_options['api_port'] != '' ){ $query .= ":" . $api_options['api_port']; }                                 //| http://v1.api.teleportd.com:8080
    $query .= "/" . str_replace("%string%", urlencode($api_settings['string']), $api_options['api_endpoint'] );     //| http://v1.api.teleportd.com:8080/search?string=xxxx
    
    if( $api_options['auth_type'] ){
      $query .= "&" . $api_options['auth_type'] . "=" . $api_settings['api_authentication'];                         //| http://v1.api.teleportd.com:8080/search?string=xxxx&apikey=xxxxxxxxxx
    }
  
    if( $api_settings['api_selected'] == 'teleportd' ) {
      $query.= "&window=50";
      $query.= "&period=" . $api_settings['period'];    
      $query.= "&location=" . urlencode($api_settings['location']); 
    }

    if( $api_settings['api_selected'] == 'instagram' ) {
      //$query.= "&max_tag_id=1334773328821"; 
    }
    
    if( $api_settings['api_selected'] == 'twitter' ) {
      $query.= "&rpp=100";
      $query.= "&result_type=mixed&include_entities=true";
      $query.= "&until=" . urlencode($api_settings['period']); 
      $query.= "&geocode=" . urlencode($api_settings['location']); 
    }
    
    if( $api_settings['api_selected'] == 'youtube' ) {
      $query.= "&max-results=50";
      $query.= "&v=2&alt=jsonc";
    }
    
    return $query;
  }
  
  function get_plugin_options($option_num=null){
    $plugin_options = get_option('teleportd-cache');
    // if we want specific options, return them
    if (is_numeric($option_num)) {
      return $plugin_options[$option_num];
    }
    // otherwise, send all the options
    else {
      $plugin_options['blacklisted_users'] = get_option('teleportd-blacklist');
      $plugin_options['always_private'] = get_option('teleportd-teleportd-always-private');
      $plugin_options['debug_on'] = get_option('teleportd-debug-on') == 'Yes' ? true : false;
      $plugin_options['max_items'] = is_numeric(get_option('teleportd-teleportd-max-items')) ? get_option('teleportd-teleportd-max-items') : 0;
      return $plugin_options;
    }
  }
  
  function run_manually_hook() {
    if( $_REQUEST['run_teleportd_manually'] == 'true' ){
      if( $_REQUEST['debug'] ){ $this->debug = true; }
      $this->get_teleportd_pics($_REQUEST['num']);
      die();
    }
  }
  
  function add_cron_intervals( $schedules ) {
  	$schedules['five_minutes'] = array(
  		'interval' => 300,
  		'display' => __('[teleportd-cache] Five Minutes')
  	);
  	$schedules['ten_minutes'] = array(
  		'interval' => 600,
  		'display' => __('[teleportd-cache] Ten Minutes')
  	);
  	$schedules['thirty_minutes'] = array(
  		'interval' => 1800,
  		'display' => __('[teleportd-cache] Thirty Minutes')
  	);
  	return $schedules;
  }
  
  // this function either runs from cron or manually from admin - if it runs without a num, it does all the searches
  function get_teleportd_pics($num=null) {
    if( is_numeric($num) ) {
      $this->run_teleportd_query($num);
    }
    else{
      $all_plugin_options = $this->get_plugin_options();
      foreach( $all_plugin_options as $cache_num => $option_settings ){
        $this->run_teleportd_query($cache_num);
      }
    }
  }
  
  function remote_get_contents($url) {
    if (function_exists('curl_get_contents') AND function_exists('curl_init')){
      if($this->debug){print "\n- USING CURL \n";}
      return $this->curl_get_contents($url);
    }
    else{
      if($this->debug){print "\n- USING file_get_contents \n";}
      return file_get_contents($url);
    }
  }

  function curl_get_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }

  function create_posttype_and_taxonomy() {
    register_post_type( 'teleportd_images',
      array(
        'labels' => array(
        'name' => __( 'Teleportd Pics' ),
        'singular_name' => __( 'Teleportd Pic' ),
        'add_new' => __( 'Add New Teleportd Pic' ),
        'add_new_item' => __( 'Add New Teleportd Pic' ),
        'edit_item' => __( 'Edit Teleportd Pic' ),
        'new_item' => __( 'Add New Teleportd Pic' ),
        'view_item' => __( 'View Teleportd Pic' ),
        'search_items' => __( 'Search Teleportd Pics' ),
        'not_found' => __( 'No teleportd pics found' ),
        'not_found_in_trash' => __( 'No teleportd pics found in trash' )
      ),
      'public' => true,
      'supports' => array( 'title', 'thumbnail', 'editor', 'custom-fields'),
      'capability_type' => 'post',
      'hierarchical' => false,
      'taxonomies' => array('teleportd_categories'),
      'rewrite' => array("slug" => "teleportd_image"), // Permalinks format
      'menu_position' => '5'
      )
    );
    register_taxonomy(
    	'teleportd_categories',
    	'teleportd_images',
    	array(
    	'labels' => array(
    		'name' => 'Teleportd Categories',
    		'singular_name' => 'Teleportd Categories',
    		'search_items' => 'Search Teleportd Categories',
    		'popular_items' => 'Popular Teleportd Categories',
    		'all_items' => 'All Teleportd Categories',
    		'parent_item' => 'Parent Teleportd Categories',
    		'parent_item_colon' => 'Parent Teleportd Categories:',
    		'edit_item' => 'Edit Teleportd Category',
    		'update_item' => 'Update Teleportd Category',
    		'add_new_item' => 'Add New Teleportd Category',
    		'new_item_name' => 'New Teleportd Category Name'
    	),
    		'hierarchical' => true,
    		'label' => 'Teleportd Category',
    		'show_ui' => true,
    		'rewrite' => array( 'slug' => 'teleportd-categories' ),
    	)
    );
    register_taxonomy(
    	'teleportd_tags',
    	'teleportd_images',
    	array(
    	'labels' => array(
    		'name' => 'Teleportd Tags',
    		'singular_name' => 'Teleportd Tags',
    		'search_items' => 'Search Teleportd Tags',
    		'popular_items' => 'Popular Teleportd Tags',
    		'all_items' => 'All Teleportd Tags',
    		'parent_item' => 'Parent Teleportd Tags',
    		'parent_item_colon' => 'Parent Teleportd Tags:',
    		'edit_item' => 'Edit Teleportd Tag',
    		'update_item' => 'Update Teleportd Tag',
    		'add_new_item' => 'Add New Teleportd Tag',
    		'new_item_name' => 'New Teleportd Tag Name'
    	),
    		'hierarchical' => false,
    		'label' => 'Teleportd Tag',
    		'show_ui' => true,
    		'update_count_callback' => '_update_post_term_count',
    		'rewrite' => array( 'slug' => 'teleportd-tags' ),
    	)
    );
  }
  
  function display_teleportd_pics( $defaults ) {
    
    // cool masonry / fancybox display
    //http://www.queness.com/post/8881/create-a-twitter-feed-with-attached-images-from-media-entities
    
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var('paged') : 1;
    $args = array(
      'post_type' => 'teleportd_images',
      //'cat' => $cat,
      //'offset' => $offset,
      'posts_per_page' => ($defaults['rows'] * $defaults['cols']),
      'orderby' => 'date',
      'order' => 'DESC',
      'paged' => $paged
    );

    $get_posts = new WP_Query($args);
    
    if( count($get_posts->posts) ){
    print "<div style='margin:20px;display:block;min-height:20px'>";
?>
    		<div class="next"><?php next_posts_link('Older Entries &raquo;', $get_posts->max_num_pages) ?></div>
    		<div class="prev"><?php previous_posts_link('&laquo; Newer Entries', $get_posts->max_num_pages) ?></div>
<?php 
    print "</div>\n";
    print "\n<div style='width:900px'>";
    print "\n<ul class='teleportd_pics'>\n";
    foreach($get_posts->posts as $num => $post){
      $teleportd_userhandle = get_post_meta($post->ID, 'teleportd_userhandle', true);
      $teleportd_thumb_url = get_post_meta($post->ID, 'teleportd_thumb_url', true);
      $teleportd_full_url = get_post_meta($post->ID, 'teleportd_full_url', true);
      $teleportd_platform = get_post_meta($post->ID, 'teleportd_platform', true);
      $teleportd_thumb_imagesize = get_post_meta($post->ID, 'teleportd_thumb_imagesize', true);
      print "\n\t<li class='{$teleportd_platform} {$teleportd_thumb_imagesize}'><a href='{$teleportd_full_url}' target=_blank><img src='{$teleportd_thumb_url}' title='{$post->post_title}' alt='{$post->post_title}' /></a></li>";
      //if( ($num+1) % $defaults['cols'] == 0 ){ print "<br class='clear'>";}      
    }
    print "</ul></div>\n";
    }
 
  }
  
  
 function display_teleportd_map( $defaults ) {
   
?>   
<script type="text/javascript">
jQuery(document).ready(function() {
  jQuery('#map_canvas').gmap().bind('init', function(evt, map) {
  	jQuery('#map_canvas').gmap('getCurrentPosition', function(position, status) {
  		if ( status === 'OK' ) {
  			var clientPosition = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
  			jQuery('#map_canvas').gmap('addMarker', {'position': clientPosition, 'bounds': true});
  			jQuery('#map_canvas').gmap('addShape', 'Circle', { 
  				'strokeWeight': 0, 
  				'fillColor': "#008595", 
  				'fillOpacity': 0.25, 
  				'center': clientPosition, 
  				'radius': 5, 
  				'clickable': false 
  			});
  		}
  	});   
  });
});
</script>
<div id="map_canvas"></div>

<?php   
   
 }
 
  
    
}