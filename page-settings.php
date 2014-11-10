<?php
// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

define('NOVA_MAP', plugin_basename(dirname(__FILE__)));
define('NOVA_MAP_URLPATH', trailingslashit( plugins_url( '', __FILE__ ) ) );
define('NOVA_MAP_DIR', trailingslashit(dirname(__FILE__)));

// Retrieve Data from settings.json
$settings = file_get_contents(NOVA_MAP_URLPATH . 'settings.json');
$settings = json_decode($settings, true);
$shortcode = $settings['shortcode'];
$shortcode_value = '[nova_map width="' . $shortcode['width'] . '" height="' . $shortcode['height'] . '" border_color="' . $shortcode['border_color'] . '" zoom="' . $shortcode['zoom'] . '" address="' . $shortcode['address'] . '" scrollwheel="' . $shortcode['scrollwheel'] . '" maptype="' . $shortcode['maptype'] . '" css="' . $shortcode['css'] . '" search="' . $shortcode['search'] . '"]';

echo '
	<script>
		var nova_ajax = {
			ajaxurl: "' . admin_url('admin-ajax.php') . '"
		};
		var admin_url = "' . admin_url('admin.php?page=' . NOVA_MAP) . '";
	</script>
';
?>
<div class="nova_map_admin_title">
	<div class="image-content">
		<img src="<?php echo NOVA_MAP_URLPATH . 'images/nova_map_marker.png' ?>">
	</div>
	<div class="text-content">
		<h3>Service Map</h3>
		<small>You are updating the settings for the Service Map.</small>
	</div>
</div>

<div class="row" style="margin: 0;">
	<div class="col-md-8 nm_settings">
		<div class="alert alert-danger hide"></div>
		<div class="alert alert-success hide"></div>
		<div class="nm_admin_loading">
			<i class="fa fa-spinner fa-spin fa-5x"></i>
		</div>

		<!-- Top Button Actions -->
		<div class="btn-group pull-right">
			<button type="button" class="btn btn-default">Action</button>
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				<span class="caret"></span>
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a class="nm_btn_save"><i class="fa fa-save"></i> Save Changes</a></li>
			</ul>
		</div>

		<form id="nm_settings_form" class="form-horizontal" role="form" enctype="multipart/form-data" action="<?php echo admin_url('admin-ajax.php') ?>" method="POST">
			<input type="hidden" name="action" value="nm_save_settings">

			<div class="form-group">
				<label for="frm_shortcode" class="col-sm-2 control-label" style="padding-top: 6px;">Map Shortcode</label>
				<div class="col-sm-10">
					<input type="text" class="form-control" id="frm_shortcode" name="frm_shortcode" value='<?php echo $shortcode_value ?>'>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" style="padding-right: 0; padding-top: 6px;">Shortcode Values</label>
				<div class="col-sm-10" style="margin-top: 6px;">
					<span>width(px), height(px), border_color(hex), zoom, address, scrollwheel(yes/no), maptype(1-4), css, search(yes/no)</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" style="padding-top: 6px;">Map Marker</label>
				<div class="col-sm-9">
					<div class="fileinput fileinput-new" data-provides="fileinput">
						<div class="input-group">
							<div class="form-control uneditable-input" data-trigger="fileinput" style="font-size: 100%;">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
								<span class="fileinput-filename"><?php echo basename($settings['marker']) ?></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Select file</span>
								<span class="fileinput-exists">Change</span>
								<input type="file" name="frm_marker" id="frm_marker">
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
						</div>
					</div>
				</div>
				<div class="col-sm-1">
					<img src="<?php echo NOVA_MAP_URLPATH . $settings['marker'] ?>" alt="">
				</div>
			</div>
			<hr style="margin: 10px 0; border-top: 1px solid #888;">
			<div class="form-group" style="margin-bottom: 5px;">
				<label class="col-sm-2 control-label" style="padding-top: 0;">Search</label>
				<label class="col-sm-12 control-label" style="padding-top: 0; padding-bottom: 7px;">
					<small>Use these settings to manipulate the search fields and results. To show the search on the map the shortcode must have the search value as 'yes'</small>
				</label>
			</div>
			<div class="form-group" style="margin-bottom: 0;">
				<label class="col-sm-2 control-label" style="padding-top: 6px;">Search Button</label>
				<div class="col-sm-9">
					<div class="fileinput fileinput-new" data-provides="fileinput">
						<div class="input-group">
							<div class="form-control uneditable-input" data-trigger="fileinput" style="font-size: 100%;">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
								<span class="fileinput-filename"><?php echo basename($settings['search_button']) ?></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Select file</span>
								<span class="fileinput-exists">Change</span>
								<input type="file" name="frm_search_button" id="frm_search_button">
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Remove</a>
						</div>
					</div>
				</div>
				<div class="col-sm-1">
					<img src="<?php echo NOVA_MAP_URLPATH . $settings['search_button'] ?>" alt="">
				</div>
			</div>
			<div class="form-group">
				<label for="frm_search_text" class="col-sm-2 control-label" style="padding-top: 6px;">Search Field Text</label>
				<div class="col-sm-10">
					<input type="text" class="form-control" id="frm_search_text" name="frm_search_text" value="<?php echo $settings['search_text'] ?>">
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="frm_search_width" class="col-sm-4 control-label" style="padding-right: 0; padding-top: 6px;">Search Field Width</label>
						<div class="col-sm-6">
							<div class="input-group">
								<input type="text" class="form-control nm_numbers" id="frm_search_width" name="frm_search_width" maxlength="3" value="<?php echo $settings['search_width'] ?>">
								<span class="input-group-addon">Pixels</span>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="frm_search_color" class="col-sm-7 control-label" style="padding-right: 0; text-align: right; padding-top: 6px;">Search Field Color</label>
						<div class="col-sm-5">
							<div class="input-append color input-group" data-color="<?php echo $settings['search_color'] ?>" data-color-format="hex">
								<input type="text" class="form-control" name="frm_search_color" id="frm_search_color" value="<?php echo $settings['search_color'] ?>">
								<span class="input-group-addon add-on"><i style="background-color: <?php echo $settings['search_color'] ?>;"></i></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="frm_results_columns" class="col-sm-4 control-label" style="padding-right: 0; padding-top: 6px;">Display Results in</label>
						<div class="col-sm-6">
							<div class="input-group">
								<input type="text" class="form-control nm_numbers" id="frm_results_columns" name="frm_results_columns"  maxlength="1" value="<?php echo $settings['results_columns'] ?>">
								<span class="input-group-addon">Columns</span>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label for="frm_results_padding" class="col-sm-7 control-label" style="padding-right: 0; text-align: right; padding-top: 6px;">Padding Between Results Columns</label>
						<div class="col-sm-5">
							<div class="input-group">
								<input type="text" class="form-control nm_numbers" id="frm_results_padding" name="frm_results_padding" maxlength="3"value="<?php echo $settings['results_padding'] ?>">
								<span class="input-group-addon">Pixels</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="frm_results_number" class="col-sm-4 control-label" style="padding-right: 0; padding-top: 6px;">Display Number of Results</label>
						<div class="col-sm-6">
							<div class="input-group">
								<input type="text" class="form-control nm_numbers" id="frm_results_number" name="frm_results_number"  maxlength="2" value="<?php echo $settings['results_number'] ?>">
								<span class="input-group-addon">Per Page</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>

		<!-- Bottom Button Actions -->
		<div class="btn-group pull-right">
			<button type="button" class="btn btn-default">Action</button>
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				<span class="caret"></span>
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a class="nm_btn_save"><i class="fa fa-save"></i> Save Changes</a></li>
			</ul>
		</div>
	</div>
</div>