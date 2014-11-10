<?php
/**
 * @package Nova Service Map
 * @version 1.0
 */
/*
Plugin Name: Nova Service Map
Plugin URI: 
Description: Nova Service Map plugin
Author: 
Version: 1.0
Author URI: 
*/

// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

define('NOVA_MAP', plugin_basename(dirname(__FILE__)));
define('NOVA_MAP_URLPATH', trailingslashit( plugins_url( '', __FILE__ ) ) );

include 'shortcodes.php';
include 'functions.php';

if(is_admin()) {
	// Slider - Create Tables
	function nova_map_database() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table_locations = "nm_locations";
		
		$sql = "
		CREATE TABLE IF NOT EXISTS $table_locations (
			l_id int(11) NOT NULL AUTO_INCREMENT,
			l_name varchar(100) NULL,
			l_address text NULL,
			l_phone_number text NULL,
			l_website text NULL,
			l_details text NULL,
			l_lat decimal(15,8)	NULL,
			l_lng decimal(15,8)	NULL,
			l_added_on datetime	NULL,
			l_updated_on datetime NULL,
			UNIQUE KEY l_id (l_id)
		);";	  
		dbDelta( $sql );
	}
	register_activation_hook( __FILE__, 'nova_map_database' );
}

if(!class_exists('Nova_map') && is_admin()) {
	class Nova_map {

		// Constructor
		function Nova_map() {
			// Add Nova Map Admin Menus
			add_action('admin_menu', array(&$this, 'add_nm_admin_menus'));
			
			// Ajax Actions
			add_action('wp_ajax_nm_admin_get_locations', array(&$this, 'ajax_nm_admin_get_locations'));
		}

		// Add Nova Map Admin Menus
		function add_nm_admin_menus() {
			add_menu_page('Service Map',  'Service Map', 'manage_options', NOVA_MAP . '/page-locations.php', '', plugin_dir_url(__FILE__) . 'images/nova_map_logo.png');
			add_submenu_page(NOVA_MAP . '/page-locations.php', 'All Locations', 'All Locations', 'manage_options', NOVA_MAP . '/page-locations.php');
			add_action('admin_head-' . NOVA_MAP . '/page-locations.php', array(&$this, 'nm_register_head'));

			add_submenu_page(NOVA_MAP . '/page-locations.php', 'Add New Location', 'Add New Location', 'manage_options', NOVA_MAP . '/page-crud-location.php');
			add_action('admin_head-' . NOVA_MAP . '/page-crud-location.php', array(&$this, 'nm_register_head'));

			add_submenu_page(NOVA_MAP . '/page-locations.php', 'Settings', 'Settings', 'manage_options', NOVA_MAP . '/page-settings.php');
			add_action('admin_head-' . NOVA_MAP . '/page-settings.php', array(&$this, 'nm_register_head'));
		}

		// Nova Map Register Head
		function nm_register_head() {
			wp_enqueue_style('bootstrap_css', '//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css');
			wp_enqueue_style('fontawesome_css', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css');
			// wp_enqueue_style('jquery_tablesorter_css', NOVA_MAP_URLPATH . 'css/jquery.tablesorter.css');
			wp_enqueue_style('jasny_file_input_css', NOVA_MAP_URLPATH . 'css/jasny.file-input.css');
			wp_enqueue_style('colorpicker_css', NOVA_MAP_URLPATH . 'css/colorpicker.css');
			wp_enqueue_style('nm_admin_css', NOVA_MAP_URLPATH . 'css/nova-map-admin.css');

			wp_enqueue_script('bootstrap_js', '//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js');
			wp_enqueue_script('jquery_tablesorter_js', NOVA_MAP_URLPATH . 'js/jquery.tablesorter.min.js');
			wp_enqueue_script('jquery_tablesorter_pager_js', NOVA_MAP_URLPATH . 'js/jquery.tablesorter.pager.js');
			wp_enqueue_script('jquery_datatable_js', NOVA_MAP_URLPATH . 'js/jquery.dataTables.min.js');
			wp_enqueue_script('jquery_datatable_bootstrap_js', NOVA_MAP_URLPATH . 'js/dataTables-twitterbootstrap.js');
			wp_enqueue_script('google_maps_api', 'http://maps.google.com/maps/api/js?sensor=true');
			wp_enqueue_script('gmaps_js', NOVA_MAP_URLPATH . 'js/gmaps.0.4.9.js');
			wp_enqueue_script('jasny_file_input_js', NOVA_MAP_URLPATH . 'js/jasny.file-input.js');
			wp_enqueue_script('jquery_form_js', NOVA_MAP_URLPATH . 'js/jquery.form.min.js');
			wp_enqueue_script('colorpicker_js', NOVA_MAP_URLPATH . 'js/bootstrap-colorpicker.js');
			wp_enqueue_script('nm_admin_js', NOVA_MAP_URLPATH . 'js/nova-map-admin.js');
		}

		// Ajax Actions Handlers
		function ajax_nm_admin_get_locations() {
			global $wpdb;
			$query = isset($_POST['query']) ? $_POST['query'] : '';
			$page = isset($_POST['page']) ? ($_POST['page'] != '' && is_numeric($_POST['page']) ? intval($_POST['page']) : 1) : 1;
			$num_results = isset($_POST['num_results']) ? ($_POST['num_results'] != '' && is_numeric($_POST['num_results']) ? intval($_POST['num_results']) : 'all') : 'all';

			// Set Base Query
			$sql = 'SELECT * FROM nm_locations';

			// Get Row Count and Number of Pages
			$wpdb->get_results($sql);
			$num_rows = $wpdb->num_rows;
			$num_pages = $num_results != 'all' ? ceil($num_rows / $num_results) : 1;
			
			// Set Limit and Offset
			if($num_results != 'all') {
				$limit = $num_results;
				$offset = ($page - 1) * $num_results;

				$sql .= " LIMIT $offset, $limit";
			}
			
			// Get Results
			$results = $wpdb->get_results($sql, ARRAY_A);
			foreach($results as $k => $v) {				
				$results[$k]['l_name'] = stripslashes($results[$k]['l_name']);
				$results[$k]['l_address'] = stripslashes($results[$k]['l_address']);
				$results[$k]['l_phone_number'] = stripslashes($results[$k]['l_phone_number']);
				$results[$k]['l_website'] = stripslashes($results[$k]['l_website']);
				$results[$k]['l_details'] = stripslashes(strip_tags(implode(' ', explode('</p>', $results[$k]['l_details']))));
				$results[$k]['l_added_on'] = date('Y/m/d', strtotime($results[$k]['l_added_on']));
				$results[$k]['l_updated_on'] = $results[$k]['l_updated_on'] != '' ? date('Y/m/d', strtotime($results[$k]['l_updated_on'])) : $results[$k]['l_updated_on'];
			}

			// Set Data to return
			$data = array(
				'results' => $results,
				'num_rows' => $num_rows,
				'num_pages' => $num_pages,
				'page' => $page
			);

			// Return Data
			echo json_encode($data);
			die();
		}
	}

	// Initialize plugin
	global $Nova_map;
	$Nova_map = new Nova_map();
}