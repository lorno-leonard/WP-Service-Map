<?php
// Nova Map Shortcode
function nova_map_function($atts) {
	wp_enqueue_style('fontawesome_css', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css');
	wp_enqueue_style('nm_css', NOVA_MAP_URLPATH . 'css/nova-map.css');
	wp_enqueue_script('google_maps_api', 'http://maps.google.com/maps/api/js?sensor=true');
	wp_enqueue_script('gmaps_js', NOVA_MAP_URLPATH . 'js/gmaps.0.4.9.js');
	wp_enqueue_script('nm_js', NOVA_MAP_URLPATH . 'js/nova-map.js');
	wp_localize_script('nm_js', 'nova_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));

	// Get Settings
	$settings = file_get_contents(NOVA_MAP_URLPATH . 'settings.json');
	$settings = json_decode($settings, true);

	// Default Values
	$default = array(
		'width' => 'auto',
		'height' => 500,
		'border_color' => 'none',
		'zoom' => 16,
		'address' => '5339 207 Street, Langley, BC, Canada, V3A 2E6',
		'scrollwheel' => true,
		'maptype' => 1, // Hybrid
		'css' => '',
		'search' => 'yes',
		'latitude' => 49.09933,
		'longitude' => -122.649418
	);

	// Extract Shortcode Attributes, overrides from settings if it has a value
	extract(shortcode_atts(
		array(
			'width' => '',
			'height' => '',
			'border_color' => '',
			'zoom' => '',
			'address' => '',
			'scrollwheel' => '',
			'maptype' => '',
			'css' => '',
			'search' => ''
			// 'marker' => NOVA_MAP_URLPATH . 'images/nova_map_marker.png'
		),
		$atts
	));

	// Set Right Values
	$width = $width != '' ? $width : ($settings['shortcode']['width'] != '' ? $settings['shortcode']['width'] : $default['width']);
	$height = $height != '' ? $height : ($settings['shortcode']['height'] != '' ? $settings['shortcode']['height'] : $default['height']);
	$border_color = $border_color != '' ? $border_color : ($settings['shortcode']['border_color'] != '' ? $settings['shortcode']['border_color'] : $default['border_color']);
	$zoom = $zoom != '' ? $zoom : ($settings['shortcode']['zoom'] != '' ? $settings['shortcode']['zoom'] : $default['zoom']);
	$address = $address != '' ? $address : ($settings['shortcode']['address'] != '' ? $settings['shortcode']['address'] : $default['address']);
	$scrollwheel = $scrollwheel != '' ? $scrollwheel : ($settings['shortcode']['scrollwheel'] != '' ? $settings['shortcode']['scrollwheel'] : $default['scrollwheel']);
	$maptype = $maptype != '' ? $maptype : ($settings['shortcode']['maptype'] != '' ? $settings['shortcode']['maptype'] : $default['maptype']);
	$css = $css != '' ? $css : ($settings['shortcode']['css'] != '' ? $settings['shortcode']['css'] : $default['css']);
	$search = $search != '' ? $search : ($settings['shortcode']['search'] != '' ? $settings['shortcode']['search'] : $default['search']);

	// Finalize Values for usage
	$width = is_numeric($width) ? $width . 'px' : $width;
	$height = is_numeric($height) ? $height . 'px' : $height;
	$border_color = $border_color != 'none' && $border_color != ''? '1px solid #' . $border_color : 'none';
        $scrollwheel = $scrollwheel == 'yes' ? true : false;
	if($maptype == 1):
		$maptype = 'hybrid';
	elseif($maptype == 2):
		$maptype = 'roadmap';
	elseif($maptype == 3):
		$maptype = 'terrain';
	elseif($maptype == 4):
		$maptype = 'satellite';
	else:
		$maptype = 'roadmap';
	endif;

	// Get Coordinates of Address as default Coordinates of the map, using CURL
	$ch = curl_init();
	$details_url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&sensor=false';
	curl_setopt($ch, CURLOPT_URL, $details_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = json_decode(curl_exec($ch), true);

	if($response['status'] == 'OK') {
		$latitude = $response['results'][0]['geometry']['location']['lat'];
		$longitude = $response['results'][0]['geometry']['location']['lng'];
	} else {
		$latitude = $default['latitude'];
		$longitude = $default['longitude'];
	}

	$search_box = '';
	$search_results = '';
	if($search == 'yes') {
		$search_box = '
			<div class="nova_search_box">
				<div class="clear_search">
					<div class="img_container"><a><img src="' . NOVA_MAP_URLPATH . 'images/nova_clear_results.png"></a></div>
					<div class="text_container"><span>Clear Results and Show All Locations</span></div>
				</div>
				<input type="text" id="nova_map_location_search" style="width: ' . $settings['search_width'] . 'px; background: ' . $settings['search_color'] . ';" placeholder="' . $settings['search_text'] . '">
				<a><img src="' . NOVA_MAP_URLPATH . $settings['search_button'] . '"></a>
			</div>
			<div class="nova_search_load">
				<i class="fa fa-spinner fa-spin fa-5x"></i>
			</div>
		';
		$search_results = '<div class="nova_search_results"></div>';
	}
	return '
	<div class="nova_map_container">
		<script>
			var nova_map_zoom = ' . $zoom . ';
			var nova_map_address = "' . $address . '";
			var nova_map_scrollwheel = "' . $scrollwheel . '";
			var nova_map_maptype = "' . $maptype . '";

			var nova_map_marker = "' . NOVA_MAP_URLPATH . $settings['marker'] . '";
			var nova_map_results_columns = "' . ($settings['results_columns'] != '' ? $settings['results_columns'] : 2) . '";
			var nova_map_results_padding = "' . ($settings['results_padding'] != '' ? $settings['results_padding'] : 0) . '";
			var nova_map_results_number = "' . $settings['results_number'] . '";

			var nova_map_latitude = ' . $latitude . ';
			var nova_map_longitude = ' . $longitude . ';
			var nova_map_close = "' . NOVA_MAP_URLPATH . 'images/nova_map_close.png";
		</script>
		<div id="nova_map" style="width: ' . $width . '; height: ' . $height . '; border: ' . $border_color . '; ' . $css . '"></div>
		' . $search_box  . '
	</div>
	' . $search_results;
}
add_shortcode('nova_map', 'nova_map_function');