jQuery(function($) {
	var nova_map;
	nova_map = new GMaps({
		div: '#nova_map',
		zoom: nova_map_zoom,
		lat: nova_map_latitude,
		lng: nova_map_longitude,
		mapType: nova_map_maptype,
		scrollwheel: nova_map_scrollwheel,
		styles: [
			{
				featureType: 'poi',
				stylers: [
					{ visibility: 'off' }
				]
			}
		]
	});
	load_locations();

	var details_min_length = 150;
	var website_min_length = 50;
	var fn_show_more_less = function() {
		var show = $(this).data('show');
		var details = $(this).closest('p').data('details');

		if(show == 'more') {
			$(this).prev('span').text(details);
			$(this).data('show', 'less').text('Show Less');
		} else {
			$(this).prev('span').text(details.substr(0, details_min_length - 3) + '...');
			$(this).data('show', 'more').text('Show More');
			if($(this).closest('.gm-style-iw').length > 0) {
				var map_marker_bubble = $(this).closest('.gm-style-iw');
				$(map_marker_bubble).css('overflow', 'hidden').html($(map_marker_bubble).html()).css('overflow', 'auto');
				$(map_marker_bubble).find('.nm_details_more_less').unbind('click').bind('click', fn_show_more_less);
			}
		}
	};
	var fn_page_number = function() {
		if(!$(this).hasClass('active')) {
			$('.nm_page_number').each(function() {
				$(this).removeClass('active');
			});
			$(this).addClass('active');
			
			$('.nm_page').each(function() {
				$(this).removeClass('active');
			});
			$('#nm_page_' + $(this).data('page')).addClass('active');
		}
	};

	// Load Locations
	function load_locations() {
		// Clear
		nova_map.removeMarkers();
		$('.nova_search_results').empty();
		$('.clear_search').hide();

		// Toggle Search - Enable
		toggle_search('enable');

		// Set Data
		var params = {};
		params.action = 'nm_get_locations';
		params.query = $('#nova_map_location_search').val();
		$.post(nova_ajax.ajaxurl, params, function(response){
			var decode = JSON.parse(response);
			var markers = [];
			var search_results = '';
			var columns = nova_map_results_columns;
			var padding = nova_map_results_padding;
			var number_results = nova_map_results_number;
			var number_pages = isNaN(number_results) == true || number_results == '' ? '' : Math.ceil(decode.length / number_results);
			var width_css = 100 / columns;
			var cur_page = 1;
			var rec_count_page = 0;
			
			for(var i = 0; i < decode.length; i++) {
				var data = decode[i];
				
				// Set Content
				var content = '';

				content += '<p><a href="' + (data.l_website != "" ? data.l_website : '#') + '" target="' + (data.l_website != "" ? '_blank' : '') + '">' + data.l_name + '</a></p>';
				if(data.l_address != '') content += '<p>' + data.l_address + '</p>';
				if(data.l_phone_number != '' || data.l_website != '') {
					content += '<p>';
					if(data.l_phone_number != '' && data.l_website != '') content += data.l_phone_number + '&nbsp;&nbsp;|&nbsp;&nbsp;' + '<a href="' + data.l_website + '" target="_blank">' + (data.l_website.length > website_min_length ? data.l_website.substr(0, website_min_length - 3) + '...' : data.l_website) + '</a>';
					else {
						if(data.l_phone_number != '') content += data.l_phone_number;
						else if(data.l_website != '') content += '<a href="' + data.l_website + '" target="_blank">' + (data.l_website.length > website_min_length ? data.l_website.substr(0, website_min_length - 3) + '...' : data.l_website) + '</a>';
					}
					content += '</p>';
				}
				if(data.l_details != '') {
					content += '<p data-details="' + data.l_details + '"><span>' + (data.l_details.length > details_min_length ? data.l_details.substr(0, details_min_length - 3) + '...</span><a class="nm_details_more_less" data-show="more">Show More</a>' : data.l_details) + '</p>';
				}

				if(parseInt(data.l_lat) != 0 && parseInt(data.l_lng) != 0) {
					// Set Markers
					markers.push({
						lat: data.l_lat,
						lng: data.l_lng,
						infoWindow: {
							maxWidth: 350,
							content: '<div style="height: 130px;">' + content + '</div>',
							domready: function() {
								jQuery('.gm-style-iw').next('div').empty().html('<img src="' + nova_map_close + '" style="width: 20px">').css({
									'width': '20px',
									'height': '20px'
								});
								jQuery('.gm-style-iw').find('.nm_details_more_less').unbind('click').bind('click', fn_show_more_less);
							}
						}
					});
				}

				if(number_pages != '') {
					// Opening Page
					if(i == 0 || i % number_results == 0) {
						search_results += '<div id="nm_page_' + cur_page + '" class="nm_page' + (cur_page == 1 ? ' active' : '') + '">';
						rec_count_page = 0;
					}

					// Opening Row
					if(rec_count_page == 0 || rec_count_page % columns == 0) {
						search_results += '<div class="row">';
					}

					// Set Padding
					var padding_css = '';
					if(rec_count_page == 0 || rec_count_page % columns == 0) {
						padding_css = 'padding-right: ' + (padding / 2) + 'px;';
					} else if((rec_count_page + 1) == decode.length || (rec_count_page + 1) % columns == 0) {
						padding_css = 'padding-left: ' + (padding / 2) + 'px;';
					} else {
						padding_css = 'padding: 0 ' + (padding / 2) + 'px;';
					}

					// Set Content
					search_results += '<div class="col" style="width: ' + width_css + '%; ' + padding_css + '">' + content + '</div>';

					// Closing Row
					if((rec_count_page + 1) == number_results || (rec_count_page + 1) % columns == 0 || (i + 1) == decode.length) {
						search_results += '</div>';
					}

					// Closing Page
					if((i + 1) == decode.length || (i + 1) % number_results == 0) {
						search_results += '</div>';
						cur_page = cur_page + 1
					}

					rec_count_page = rec_count_page + 1;
				} else {
					// Set Search Results
					if(i == 0 || i % columns == 0) {
						search_results += '<div class="row">';
					}

					// Set Padding
					var padding_css = '';
					if(i == 0 || i % columns == 0) {
						padding_css = 'padding-right: ' + (padding / 2) + 'px;';
					} else if((i + 1) == decode.length || (i + 1) % columns == 0) {
						padding_css = 'padding-left: ' + (padding / 2) + 'px;';
					} else {
						padding_css = 'padding: 0 ' + (padding / 2) + 'px;';
					}
					
					// Set Content
					search_results += '<div class="col" style="width: ' + width_css + '%; ' + padding_css + '">' + content + '</div>';

					if((i + 1) == decode.length || (i + 1) % columns == 0) {
						search_results += '</div>';
					}
				}
			}

			// Add Markers
			if(markers.length > 0) {
				nova_map.addMarkers(markers);
				nova_map.fitZoom();

				if(nova_map.getZoom() > 15) {
					nova_map.setZoom(15);
				}
			}

			// Add Search Results
			if(number_pages != '' && number_pages > 1) {
				var page_results = [];
				for(var i = 1; i <= number_pages; i++) {
					page_results.push('<a class="nm_page_number' + (i == 1 ? ' active' : '') + '" data-page="' + i + '">' + i + '</a>');
				}
				
				search_results += '<span>More Results: ' + page_results.join(', ') + '</span>'
				$('.nova_search_results').append(search_results);

				$('.nm_page_number').unbind('click').bind('click', fn_page_number);
			} else {
				$('.nova_search_results').append(search_results);
			}

			// Show Clear Search if Search Key is not empty
			if($('#nova_map_location_search').val().trim() != '' && markers.length > 0) {
				$('.clear_search').show();
			}

			// Bind Show More/Less click function
			$('.nm_details_more_less').unbind('click').bind('click', fn_show_more_less);

			// Toggle Search - Disable
			toggle_search('disable');
		});
	}

	// Enable/Disable Search
	function toggle_search(type) {
		if(type == 'enable') {
			$('.nova_search_load').show();
			$('#nova_map_location_search').prop('disabled', true);
			$('#nova_map_location_search').next('a').hide();
		} else {
			$('.nova_search_load').hide();
			$('#nova_map_location_search').prop('disabled', false);
			$('#nova_map_location_search').next('a').show();
		}
	}

	// Clear Results
	function clear_results() {
		$('#nova_map_location_search').val('');
		load_locations();
	}

	// Load Locations if enter key is pressed
	$('#nova_map_location_search').keydown(function(e) {
		if(e.which == 13) {
			load_locations();
		}
	});

	// Load Locations on click
	$('#nova_map_location_search').next('a').click(function() {
		load_locations();
	});

	// Clear Results on click
	$('.clear_search a').click(function() {
		clear_results();
	});
});