jQuery(function($) {
	var locations_table;
	var admin_nova_map;
	if(location.searh != '') {
		var search_params = JSON.parse('{"' + decodeURI(location.search.substring(1).replace(/&/g, "\",\"").replace(/=/g,"\":\"")) + '"}'),
			search_param_page_arr = search_params.page ? search_params.page.split('/') : [],
			plugin = search_param_page_arr.length > 0 ? search_param_page_arr[0] : '',
			plugin_page = search_param_page_arr.length > 0 ? search_param_page_arr[1] : '';
	}

	var fn_sort = function() {
		if($(this).val() == '') {
			locations_table.fnFilter('', 1);
			locations_table.fnFilter('');
		} else {
			locations_table.fnFilter('^' + $(this).val(), 1, true);
		}
	};
	var fn_search = function() {
		if($(this).val() == '') {
			locations_table.fnFilter('', 1);
			locations_table.fnFilter('');
		} else {
			locations_table.fnFilter($(this).val(), 1);
		}
	};

	// Show Success Message
	nm_show_success_message();

	if(location.search != '') {	
		// If Plugin is nova-map
		if(plugin == 'nova-map') {
			// Locations
			if(plugin_page == 'page-locations.php') {
				// Load all Locations at first
				load_locations({});
			} else if(plugin_page == 'page-crud-location.php') {
				admin_nova_map = new GMaps({
					div: '#admin_nova_map',
					zoom: 15,
					lat: 49.09933,
					lng: -122.649418,
					mapType: 'hybrid',
					styles: [
						{
							featureType: 'poi',
							stylers: [
								{ visibility: 'off' }
							]
						}
					],
					click: function(event) {
						var plot = $('#nm_btn_plot').data('plot');
						if(plot == 'manual') {
							var coordinates = event.latLng;
							admin_nova_map.removeMarkers();
							admin_nova_map.addMarker({
								lat: coordinates.lat(),
								lng: coordinates.lng()
							});
							admin_nova_map.setCenter(coordinates.lat(), coordinates.lng());
						}
					}
				});
				if(nova_result_lat != '' && nova_result_lng != '') {
					admin_nova_map.addMarker({
						lat: nova_result_lat,
						lng: nova_result_lng
					});
					admin_nova_map.setCenter(nova_result_lat, nova_result_lng);
				}
			} else if(plugin_page == 'page-settings.php') {
				$('.color').colorpicker();
			}
		}
	}	

	// Toggle Check/Uncheck Checkbox
	function nm_location_toggle_check(checkbox) {
		var check = $(checkbox).is(':checked');
		$('.nm_locations tbody tr').each(function() {
			$(this).find('input[type="checkbox"]').prop('checked', check);
		});
		$('.nm_locations thead tr, .nm_locations tfoot tr').find('input[type="checkbox"]').prop('checked', check);
	}

	// Save Location
	function nm_save_location() {
		var errors = [];
		var has_marker = true;

		// Reset Errors
		$('.alert-danger').addClass('hide');
		$('#frm_l_name').closest('.form-group').removeClass('has-error');
		$('#frm_l_address').closest('.form-group').removeClass('has-error');
		$('#admin_nova_map').css('border', 'none');

		// Trim
		$('#frm_l_name').val($('#frm_l_name').val().trim());
		$('#frm_l_address').val($('#frm_l_address').val().trim());
		$('#frm_l_phone_number').val($('#frm_l_phone_number').val().trim());
		$('#frm_l_website').val($('#frm_l_website').val().trim());

		// Check if Location Name is empty
		if($('#frm_l_name').val() == '') {
			$('#frm_l_name').closest('.form-group').addClass('has-error');
			errors.push('Location Name is required');
		}

		// // Check if Service Address is empty
		// if($('#frm_l_address').val() == '') {
		// 	$('#frm_l_address').closest('.form-group').addClass('has-error');
		// 	errors.push('Service Address is required');
		// }

		// Check if Map has a Marker
		if(admin_nova_map.markers.length == 0) {
			// $('#admin_nova_map').css('border', '1px solid #a94442');
			// errors.push('Please check your Service Address, it cannot be found on Google Maps');
			has_marker = false;
		}

		// Check if it has errors
		if(errors.length > 0) {
			var errors_content = '';
			errors_content += '<p><strong>This form has errors.</strong></p>';
			$.each(errors, function(i, v) {
				errors_content += '- ' + v + '<br>';
			});
			$('.alert-danger').html(errors_content);
			$('.alert-danger').removeClass('hide');
		} else {
			var confirmation = true;
			if(has_marker == false) {
				confirmation = confirm('There is no place plotted in the Google Map, are you sure to continue?');
			}
			
			if(confirmation == true) {
				// Initialize Loading Screen
				$('#nm_crud_location_form').css('opacity', '0.5');
				$('.nm_admin_loading').show();
				
				// Initialize Parameters
				var params = {
					action: 'nm_save_location',
					opt: $('#nm_opt').val(),
					l_name: $('#frm_l_name').val(),
					l_address: $('#frm_l_address').val(),
					l_phone_number: $('#frm_l_phone_number').val(),
					l_website: $('#frm_l_website').val(),
					l_details: tinyMCE.get('frm_l_details').getContent(),
				};
				
				if(admin_nova_map.markers.length > 0) {
					params.l_lat = admin_nova_map.markers[0].getPosition().lat();
					params.l_lng = admin_nova_map.markers[0].getPosition().lng();
				}
				if(params.opt == 'edit') {
					params.l_id = $('#nm_l_id').val()
				}

				// Save
				$.post(nova_ajax.ajaxurl, params, function(response) {
					var decode = JSON.parse(response);

					if(decode.success == true) {
						if(params.opt == 'add') {
							window.location.href = window.location.href + '&id=' + decode.l_id + '&success=add';
						} else {
							window.location.href = window.location.href + '&success=edit';
						}
					}
				});
			}
		}
	}

	// Delete Locations
	function nm_delete_locations() {
		var l_id_arr = [];
		$('.nm_locations tbody tr').each(function() {
			if($(this).find('input[type="checkbox"]').is(':checked') == true) {
				l_id_arr.push($(this).find('.l_id').val());
			}
		});
		
		// Check if there Locations checked
		if(l_id_arr.length == 0) {
			alert('Please select location(s) to Delete.');
			return;
		} else {
			var result = confirm('Are you sure to Delete the selected location(s)?');
			if(result == true) {
				// Initialize Loading Screen
				$('.nm_locations').css('opacity', '0.5');
				$('.nm_admin_loading').show();
				
				// Initialize Parameters
				var params = {
					action: 'nm_save_location',
					opt: 'delete',
					l_id_arr: l_id_arr
				};

				// Save
				$.post(nova_ajax.ajaxurl, params, function(response) {
					var decode = JSON.parse(response);

					if(decode.success == true) {
						window.location.href = window.location.href + '&success=true';
					}
				});
			}
		}
	}

	// Save Settings
	function nm_save_settings() {
		var errors = [];

		// Reset Errors
		$('.alert-danger').addClass('hide');
		$('#frm_marker').closest('.form-group').removeClass('has-error');
		$('#frm_search_button').closest('.form-group').removeClass('has-error');

		// Trim
		$('#frm_shortcode').val($('#frm_shortcode').val().trim());
		$('#frm_search_text').val($('#frm_search_text').val().trim());

		// Check if files selected are images
		var valid_extensions = ['jpg', 'jpeg', 'png'];
		if($('#frm_marker').val() != '') {
			var ext = $('#frm_marker').val().split('.').pop();
			if($.inArray(ext, valid_extensions) == -1) {
				$('#frm_marker').closest('.form-group').addClass('has-error');
				errors.push('Marker file selected is not an image. Allowed file types are (jpg, jpeg, png)');
			}
		}
		if($('#frm_search_button').val()) {
			var ext = $('#frm_search_button').val().split('.').pop();
			if($.inArray(ext, valid_extensions) == -1) {
				$('#frm_search_button').closest('.form-group').addClass('has-error');
				errors.push('Search Button file selected is not an image. Allowed file types are (jpg, jpeg, png)');
			}
		}

		// Check if Number of Results is less than Number of Columns Displayed
		if($('#frm_results_number').val() != '') {
			if(parseInt($('#frm_results_number').val()) < parseInt($('#frm_results_columns').val())) {
				$('#frm_results_number').closest('.form-group').addClass('has-error');
				errors.push('Number of Results should be equal or more than the number of Columns');
			}
		}
		
		// Check if it has errors
		if(errors.length > 0) {
			var errors_content = '';
			errors_content += '<p><strong>This form has errors.</strong></p>';
			$.each(errors, function(i, v) {
				errors_content += '- ' + v + '<br>';
			});
			$('.alert-danger').html(errors_content);
			$('.alert-danger').removeClass('hide');
		} else {
			// Initialize Loading Screen
			$('#nm_settings_form').css('opacity', '0.5');
			$('.nm_admin_loading').show();

			if(!$.browser.msie) {
				var form_data = new FormData();
				$('#nm_settings_form input').each(function() {
					if(this.type == 'file' && this.name != '') {
						form_data.append(this.name, this.files[0]);
					} else if(this.type != 'file' && this.name != '') {
						form_data.append(this.name, this.value);
					}
				});
				$.ajax({
					url: nova_ajax.ajaxurl,
					type: 'POST',
					data: form_data,
					cache: false,
					contentType: false,
					processData: false,
					success: function (response) {
						var decode = JSON.parse(response);

						if(decode.success == true) {
							window.location.href = window.location.href + '&success=true';
						}
					}
				});
			} else {
				$('#nm_settings_form').ajaxSubmit({
					success: function (response) {
						var decode = JSON.parse(response);

						if(decode.success == true) {
							window.location.href = window.location.href + '&success=true';
						}
					}
				});
			}
		}
	}

	// Show Success Message
	function nm_show_success_message() {
		if(typeof search_params != 'undefined') {
			if(plugin_page == 'page-locations.php') {
				if(typeof search_params.success != 'undefined') {
					var alert_message = '<strong>Success!</strong> You have deleted Locations.';
					$('.alert-success').html(alert_message);
					$('.alert-success').removeClass('hide');
					
					window.history.pushState('', '', admin_url + '/' + plugin_page);
				}
			} else if(plugin_page == 'page-crud-location.php') {
				if(typeof search_params.success != 'undefined') {
					var alert_message = '<strong>Success!</strong> You have ' + (search_params.success == 'add' ? 'created a new' : 'updated a ') + ' location.';
					$('.alert-success').html(alert_message);
					$('.alert-success').removeClass('hide');

					window.history.pushState('', '', admin_url + '/' + plugin_page + '&id=' + search_params.id);
				}
			} else if(plugin_page == 'page-settings.php') {
				if(typeof search_params.success != 'undefined') {
					var alert_message = '<strong>Success!</strong> You have updated your Settings.';
					$('.alert-success').html(alert_message);
					$('.alert-success').removeClass('hide');

					window.history.pushState('', '', admin_url + '/' + plugin_page);
				}
			}
		}
	}

	// Load Locations
	function load_locations(params) {
		// Initialize Loading Screen
		$('.nm_locations').css('opacity', '0.5');
		$('.nm_admin_loading').show();

		params.action = 'nm_admin_get_locations';
		$.post(ajaxurl, params, function(response){
			var decode = JSON.parse(response); 
			var results = decode.results;

			// Remove all locations first
			$('table.nm_locations tbody').empty();
			
			if(results.length > 0) {
				// Add Rows
				var rows = '';
				for(var i = 0; i < results.length; i++) {
					rows += '\
						<tr>\
							<td><input type="checkbox"><input type="hidden" class="l_id" value="' + results[i].l_id + '"></td>\
							<td title="' + results[i].l_name + '"><a href="' + (admin_url + '/page-crud-location.php' + '&id=' + results[i].l_id) + '"><strong>' + results[i].l_name + '</strong></a></td>\
							<td title="' + results[i].l_address + '"><span>' + results[i].l_address + '</span></td>\
							<td title="' + results[i].l_phone_number + '"><span>' + results[i].l_phone_number + '</span></td>\
							<td title="' + results[i].l_website + '"><a href="' + (results[i].l_website != '' ? results[i].l_website : '#') + '" target="' + (results[i].l_website != '' ? '_blank' : '') + '"><strong>' + results[i].l_website + '</strong></a></td>\
							<td title="' + results[i].l_details + '"><span>' + results[i].l_details + '</span></td>\
							<td title="' + (results[i].l_updated_on != null ? 'Last Modified: ' + results[i].l_updated_on : 'Published: ' + results[i].l_added_on) + '">\
								<span style="border-bottom: 2px dotted #999;">' + (results[i].l_updated_on != null ? results[i].l_updated_on : results[i].l_added_on) + '</span><br>\
								<span>' + (results[i].l_updated_on != null ? 'Last Modified' : 'Published') + '</span>\
							</td>\
						</tr>\
					';
				}
				$('table.nm_locations tbody').append(rows);

				// Initialize Datatable
				locations_table = $('table.nm_locations').dataTable({
					"sDom": "t<'row'<'col-md-8'><'col-md-2'i><'col-md-2'p>>",
					"sPaginationType": "full_numbers",
					"aaSorting": [[1,'asc']],
					"aoColumnDefs": [{
						"bSortable": false,
						"aTargets": [0]
						}
					],
					"oLanguage": {
						"oPaginate": {
							"sFirst": "&lt;&lt;",
							"sPrevious": "&lt;",
							"sNext": "&gt;",
							"sLast": "&gt;&gt;"
						},
						"sInfo": "_TOTAL_ items",
						"sInfoFiltered": "(from _MAX_ items)",
						"sInfoEmpty": "0 items"
					},
					"iDisplayLength": 40
				});

				$('#nm_sort').bind('change', fn_sort);
				$('#nm_search').bind('keyup', fn_search);
			}

			// Hide Loading
			$('.nm_locations').css('opacity', '1');
			$('.nm_admin_loading').hide();
		});
	}

	// Enable/Disable Search
	function toggle_search(type) {
		if(type == 'enable') {
			$('.nova_search_load').show();
		} else {
			$('.nova_search_load').hide();
		}
	}

	// Plot Address
	function plot_address() {
		// Trim
		$('#frm_l_address').val($('#frm_l_address').val().trim());

		// Remove Existing Markers
		admin_nova_map.removeMarkers();

		// If Service Address is not empty
		if($('#frm_l_address').val() != '') {
			// Toggle Search - Enable
			toggle_search('enable');

			// Set Data
			var params = {};
			params.action = 'nm_get_location_coordinates';
			params.l_address = $('#frm_l_address').val();

			// Ajax POST
			$.post(nova_ajax.ajaxurl, params, function(response){
				var decode = JSON.parse(response);

				// If it has Coordinates, put a Marker on the Map
				if(decode.lat != '' && decode.lng != '') {
					admin_nova_map.addMarker({
						lat: decode.lat,
						lng: decode.lng
					});
					admin_nova_map.setCenter(decode.lat, decode.lng);
					admin_nova_map.setZoom(15);
				}

				// Toggle Search - Disable
				toggle_search('disable');
			});
		}
	}

	// Numbers only input
	$('.nm_numbers').keydown(function(event) {	
		// Allow: backspace, delete, tab, escape, enter and .
		if (jQuery.inArray(event.which,[46,8,9,27,13,190]) !== -1 ||
			// Allow: Ctrl+A
			(event.which == 65 && event.ctrlKey === true) || 
			// Allow: home, end, left, right
			(event.which >= 35 && event.which <= 39)) {
				// let it happen, don't do anything
				return;
		}
		else if(event.which == 48 || event.which == 96) {
			if(jQuery(this).val() == '') {
				event.preventDefault();
			}
		}
		else {
			// Ensure that it is a number and stop the keypress
			if (
				event.shiftKey ||
				(event.which < 48 || event.which > 57) &&
				(event.which < 96 || event.which > 105 )
			) {
				event.preventDefault();
			}
		}
	});

	// Delection Location Action
	$('.nm_btn_save.nm_delete_location').click(function(event) {
		nm_delete_locations();
	});

	// Crud Location Action
	$('.nm_crud_location .nm_btn_save').click(function(event) {
		nm_save_location();
	});

	// Settings Action
	$('.nm_settings .nm_btn_save').click(function(event) {
		nm_save_settings();
	});

	// Crud Location Service Address blur
	$('#frm_l_address').blur(function(event) {
		var plot = $('#nm_btn_plot').data('plot');
		if(plot == 'automatic') {
			plot_address();
		}
	});

	// Toggle Check change
	$('.nm_toggle_check').change(function(event) {
		nm_location_toggle_check(this);
	});

	// Toggle Button Plot click
	$('#nm_btn_plot').click(function(event) {
		if($(this).data('plot') == 'automatic') {
			$(this).data('plot', 'manual').html('Plot <strong>AUTOMATICALLY</strong>').removeClass('btn-success').addClass('btn-primary');
			admin_nova_map.removeMarkers();
		} else {
			$(this).data('plot', 'automatic').html('Plot <strong>MANUALLY</strong>').removeClass('btn-primary').addClass('btn-success');
			plot_address();
		}
	});

	// Display Columns blur
	$('#frm_results_columns').blur(function(event) {
		if($(this).val() == '' || parseInt($(this).val()) < 2) {
			$(this).val(2);
		}
	});
});

