<?php
/*
Plugin Name: Teleportd Cache
Plugin URI: https://github.com/shanaver/Teleportd-Wordpress-Plugin
Description: Grabs image posts from social photo APIs like teleportd, instagram & twitter.  Stores thumbnails & details locally in a custom post_type so you don't have to worry about API issues & constantly pulling the same data remotely.  Also this allows you to remove images if you don't like them and blacklist users if needed.
Version: 1.0.0
Author: Bryan Shanaver
Author URI: http://fiftyandfifty.org
*/
?>
<?php

define('TELEPORTD_CACHE_DIR', dirname(__FILE__));

$dir = teleportd_cache_dir();
include_once "$dir/lib/teleportd_cache.class.php";
include_once "$dir/lib/platforms.class.php";

function teleportd_cache_init() {
  global $teleportd_cache;
  $teleportd_cache = new TELEPORTD_CACHE();
  get_manual_run_request();
}

function get_manual_run_request(){
  global $teleportd_cache;
  if(isset($_POST['run_teleportd_cache'])) {
   $teleportd_cache->run_teleportd_query();
  }
}

function teleportd_cache_dir() {
  if (defined('TELEPORTD_CACHE_DIR') && file_exists(TELEPORTD_CACHE_DIR)) {
    return TELEPORTD_CACHE_DIR;
  } else {
    return dirname(__FILE__);
  }
}

function teleportd_pics( $atts ){
	$defaults = shortcode_atts( 
	  array(
	  'cols' => 8,
	  'rows' => 6), 
	  $atts 
	);
	if( !$teleportd_cache ){
	  $teleportd_cache = new TELEPORTD_CACHE();
	}
	$teleportd_cache->display_teleportd_pics( $defaults );
}


function teleportd_extra_columns($columns) {
  $columns['teleportd_thumbnail'] = 'Thumbnail';
  return $columns;
}


function teleportd_show_extra_columns($column) {
  global $post;
  switch ($column) {
    case 'teleportd_thumbnail':
      $teleportd_thumb_url = get_post_meta($post->ID, 'teleportd_thumb_url', true);
      echo "<img src='{$teleportd_thumb_url}' />";
      break;
  }
}

function get_teleportd_pics_create() {
  wp_schedule_event(time(), 'hourly', 'get_teleportd_pics_cron');
}

function get_teleportd_pics_deactivate() {
  wp_clear_scheduled_hook('get_teleportd_pics_cron');
}

function get_teleportd_pics() {
	if( !$teleportd_cache ){
	  $teleportd_cache = new TELEPORTD_CACHE();
	}
	$teleportd_cache->run_teleportd_query();
}

// Add initialization and activation hooks
add_action('init', 'teleportd_cache_init', 10);
add_action('init', array(&$teleportd_cache, 'run_manually_hook'), 11);
add_action('manage_posts_custom_column',  'teleportd_show_extra_columns');

add_shortcode('teleportd_pics', 'teleportd_pics');

add_filter('manage_edit-teleportd_images_columns', 'teleportd_extra_columns');

/* crons */

register_activation_hook( __FILE__ , 'get_teleportd_pics_create' );
add_action('get_teleportd_pics_cron', 'get_teleportd_pics' );
register_deactivation_hook( __FILE__ , 'get_teleportd_pics_deactivate' );

//add_filter('cron_schedules', array(&$teleportd_cache, 'add_cron_intervals'), 20 );




