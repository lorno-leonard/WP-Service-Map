<?php
define('NOVA_MAP', plugin_basename(dirname(__FILE__)));
define('NOVA_MAP_URLPATH', trailingslashit( plugins_url( '', __FILE__ ) ) );
define('NOVA_MAP_DIR', trailingslashit(dirname(__FILE__)));

// Ajax Get Locations
function ajax_nm_get_locations() {
	global $wpdb;
	$query = trim($_POST['query']);
	$query_arr = explode(' ', $query);
	
	// Options
	$sql_arr = array();
	foreach($query_arr as $k => $v) {
		if(trim($v) != '') {
			$v = mysql_real_escape_string($v);
			$sql_arr[] = "SELECT *, $k AS order_query FROM nm_locations WHERE l_name LIKE '%$v%'";
		}
	}

	// If SQL Array is not empty
	if(count($sql_arr) > 0) {
		$sql_union = implode(' UNION ', $sql_arr);

		// Set Base Query
		$sql = "
			SELECT DISTINCT
				l_id,
				l_name,
				l_address,
				l_phone_number,
				l_website,
				l_details,
				l_lat,
				l_lng,
				l_added_on,
				l_updated_on
			FROM($sql_union)a
			ORDER BY
				order_query,
				l_name
		";
	} else {
		// Set Base Query
		$sql = 'SELECT * FROM nm_locations ORDER BY l_name';
	}
	
	// Get Results
	$results = $wpdb->get_results($sql, ARRAY_A);
	foreach($results as $k => $v) {
		$results[$k]['l_name'] = stripslashes($results[$k]['l_name']);
		$results[$k]['l_address'] = stripslashes($results[$k]['l_address']);
		$results[$k]['l_phone_number'] = stripslashes($results[$k]['l_phone_number']);
		$results[$k]['l_website'] = stripslashes($results[$k]['l_website']);
		$results[$k]['l_details'] = strip_tags(stripslashes($results[$k]['l_details']));
	}

	// Return Data
	echo json_encode($results);
	die();
}
add_action('wp_ajax_nm_get_locations', 'ajax_nm_get_locations');
add_action('wp_ajax_nopriv_nm_get_locations', 'ajax_nm_get_locations');

// Ajax Save Location
function ajax_nm_save_location() {
	global $wpdb;

	if($_POST['opt'] == 'add') {
		$wpdb->insert( 
			'nm_locations', 
			array( 
				'l_name' => $_POST['l_name'],
				'l_address' => $_POST['l_address'],
				'l_phone_number' => $_POST['l_phone_number'],
				'l_website' => filter_var($_POST['l_website'], FILTER_VALIDATE_URL) ? $_POST['l_website'] : ($_POST['l_website'] != '' ? 'http://' . $_POST['l_website'] : ''),
				'l_details' => $_POST['l_details'],
				'l_lat' => isset($_POST['l_lat']) ? $_POST['l_lat'] : '',
				'l_lng' => isset($_POST['l_lng']) ? $_POST['l_lng'] : '',
				'l_added_on' => current_time('mysql', 1)
			)
		);
		$data['l_id'] = $wpdb->insert_id;
	} elseif($_POST['opt'] == 'edit') {
		$wpdb->update( 
			'nm_locations', 
			array( 
				'l_name' => $_POST['l_name'],
				'l_address' => $_POST['l_address'],
				'l_phone_number' => $_POST['l_phone_number'],
				'l_website' => filter_var($_POST['l_website'], FILTER_VALIDATE_URL) ? $_POST['l_website'] : ($_POST['l_website'] != '' ? 'http://' . $_POST['l_website'] : ''),
				'l_details' => $_POST['l_details'],
				'l_lat' => isset($_POST['l_lat']) ? $_POST['l_lat'] : '',
				'l_lng' => isset($_POST['l_lng']) ? $_POST['l_lng'] : '',
				'l_updated_on' => current_time('mysql', 1)
			), 
			array(
				'l_id' => $_POST['l_id']
			)
		);
	} elseif($_POST['opt'] == 'delete') {
		foreach($_POST['l_id_arr'] as $v) {
			$wpdb->delete('nm_locations', array('l_id' => $v));
		}
	}

	// Return Data
	$data['success'] = true;
	echo json_encode($data);
	die();
}
add_action('wp_ajax_nm_save_location', 'ajax_nm_save_location');

// Ajax Save Settings
function ajax_nm_save_settings() {
	// POST Data
	$shortcode = $_POST['frm_shortcode'];
	$search_text = $_POST['frm_search_text'];
	$search_width = $_POST['frm_search_width'];
	$search_color = $_POST['frm_search_color'];
	$results_columns = $_POST['frm_results_columns'];
	$results_padding = $_POST['frm_results_padding'];
	$results_number = $_POST['frm_results_number'];

	// FILES Data
	$file_marker = $_FILES['frm_marker'];
	$file_search_button = $_FILES['frm_search_button'];

	// Get Old Settings
	$old_settings = file_get_contents(NOVA_MAP_URLPATH . 'settings.json');
	$old_settings = json_decode($old_settings, true);
	
	// Get Shortcode attributes
	$shortcode = str_replace(']', '', str_replace('[', '', str_replace('nova_map', '', $shortcode)));
	$shortcode = shortcode_parse_atts(stripslashes($shortcode));

	// Get File Images
	if($file_marker) {
		// $filename = md5(date('YmdHisu') . rand(1000, 9999)) . '.' . pathinfo($file_marker['name'], PATHINFO_EXTENSION);
		$filename = $file_marker['name'];
		move_uploaded_file($file_marker['tmp_name'], NOVA_MAP_DIR . 'images/uploads/' . $filename);
		$marker = 'images/uploads/' . $filename;

		// Delete Old Marker
		if($old_settings['marker'] != 'images/nova_map_marker.png' && file_exists(NOVA_MAP_DIR . $old_settings['marker'])) {
			unlink(NOVA_MAP_DIR . $old_settings['marker']);
		}
	} else {
		if($old_settings['marker'] != 'images/nova_map_marker.png') {
			$marker = $old_settings['marker'];
		} else {
			$marker = 'images/nova_map_marker.png';
		}
	}

	if($file_search_button) {
		// $filename = md5(date('YmdHisu') . rand(1000, 9999)) . '.' . pathinfo($file_search_button['name'], PATHINFO_EXTENSION);
		$filename = $file_marker['name'];
		move_uploaded_file($file_search_button['tmp_name'], NOVA_MAP_DIR . 'images/uploads/' . $filename);
		$search_button = 'images/uploads/' . $filename;

		// Delete Old Search Button
		if($old_settings['search_button'] != 'images/nova_map_search.png' && file_exists(NOVA_MAP_DIR . $old_settings['search_button'])) {
			unlink(NOVA_MAP_DIR . $old_settings['search_button']);
		}
	} else {
		if($old_settings['search_button'] != 'images/nova_map_search.png') {
			$search_button = $old_settings['search_button'];
		} else {
			$search_button = 'images/nova_map_search.png';
		}
	}

	$settings = array(
		'shortcode' => array(
			'width' => $shortcode['width'] ? $shortcode['width'] : 'auto',
			'height' => $shortcode['height'] ? $shortcode['height'] : 300,
			'border_color' => $shortcode['border_color'] ? $shortcode['border_color'] : '',
			'zoom' => $shortcode['zoom'] ? $shortcode['zoom'] : 15,
			'address' => $shortcode['address'] ? $shortcode['address'] : '',
			'scrollwheel' => $shortcode['scrollwheel'] ? $shortcode['scrollwheel'] : 'yes',
			'maptype' => $shortcode['maptype'] ? $shortcode['maptype'] : 1,
			'css' => $shortcode['css'] ? $shortcode['css'] : '',
			'search' => $shortcode['search'] ? $shortcode['search'] : 'yes'
		),
		'marker' => $marker,
		'search_button' => $search_button,
		'search_text' => $search_text,
		'search_width' => $search_width,
		'search_color' => $search_color,
		'results_columns' => $results_columns,
		'results_padding' => $results_padding,
		'results_number' => $results_number
	);
	$fp = fopen(NOVA_MAP_DIR . 'settings.json', 'w');
	fwrite($fp, json_encode($settings));
	fclose($fp);

	// Return Data
	$data['success'] = true;
	echo json_encode($data);
	die();
}
add_action('wp_ajax_nm_save_settings', 'ajax_nm_save_settings');

// Ajax Get Location Coordinates
function ajax_nm_get_location_coordinates() {
	$l_address = $_POST['l_address'];
	$lat = '';
	$lng = '';

	// Initialize CURL
	$ch = curl_init();

	$address = str_replace(' ', '+', urlencode($l_address));
	$details_url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&sensor=false';

	// Get Data By CURL
	curl_setopt($ch, CURLOPT_URL, $details_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = json_decode(curl_exec($ch), true);

	if($response['status'] == 'OK') {
		$lat = $response['results'][0]['geometry']['location']['lat'];
		$lng = $response['results'][0]['geometry']['location']['lng'];
	}

	// Return Data
	$data['lat'] = $lat;
	$data['lng'] = $lng;
	echo json_encode($data);
	die();
}
add_action('wp_ajax_nm_get_location_coordinates', 'ajax_nm_get_location_coordinates');