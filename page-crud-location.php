<?php
// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

define('NOVA_MAP', plugin_basename(dirname(__FILE__)));
define('NOVA_MAP_URLPATH', trailingslashit( plugins_url( '', __FILE__ ) ) );

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
	$l_id = $_GET['id'];
	$sql = "SELECT * FROM nm_locations WHERE l_id = $l_id";
	$result = $wpdb->get_results($sql, ARRAY_A);
	if(count($result) > 0) {
		$result = $result[0];
		$result['l_name'] = stripslashes($result['l_name']);
		$result['l_address'] = stripslashes($result['l_address']);
		$result['l_phone_number'] = stripslashes($result['l_phone_number']);
		$result['l_website'] = stripslashes($result['l_website']);
		$result['l_details'] = stripslashes($result['l_details']);
	} else {
		unset($result);
	}
}
$opt = isset($_GET['id']) && is_numeric($_GET['id']) ? 'edit' : 'add';

echo '
	<script>
		var nova_map_marker = "' . NOVA_MAP_URLPATH . 'images/nova_map_marker.png' . '";
		var nova_ajax = {
			ajaxurl: "' . admin_url('admin-ajax.php') . '"
		};
		var admin_url = "' . admin_url('admin.php?page=' . NOVA_MAP) . '";
		var nova_result_lat = ' . (isset($result) && $result['l_lat'] != '' ? $result['l_lat'] : "''") . ';
		var nova_result_lng = ' . (isset($result) && $result['l_lng'] != '' ? $result['l_lng'] : "''") . ';
	</script>
';
?>
<!-- Title -->
<div class="nova_map_admin_title">
	<div class="image-content">
		<img src="<?php echo NOVA_MAP_URLPATH . 'images/nova_map_marker.png' ?>">
	</div>
	<div class="text-content">
		<h3>Service Map</h3>
		<small><?php echo $opt == 'add' ? '<strong>Add</strong> a new' : '<strong>Edit</strong>' ?> location to the Service Map.</small>
	</div>
</div>

<?php if($opt == 'add' || ($opt == 'edit' && isset($result))): ?>
<div class="row" style="margin: 0;">
	<div class="col-md-7 nm_crud_location">
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

		<form id="nm_crud_location_form" class="form-horizontal" role="form">
			<input type="hidden" id="nm_opt" value="<?php echo $opt ?>">
			<input type="hidden" id="nm_l_id" value="<?php echo $_GET['id'] ?>">

			<div class="form-group">
				<label for="frm_l_name" class="col-sm-3 control-label">Location's Name <span class="text-danger">*</span></label>
				<div class="col-sm-9">
					<input type="text" class="form-control" id="frm_l_name" name="frm_l_name" value="<?php echo isset($result) ? $result['l_name'] : '' ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="frm_l_address" class="col-sm-3 control-label">Service Address</label>
				<div class="col-sm-9">
					<input type="text" class="form-control" id="frm_l_address" name="frm_l_address" value="<?php echo isset($result) ? $result['l_address'] : '' ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="frm_l_phone_number" class="col-sm-3 control-label">Phone Number</label>
				<div class="col-sm-9">
					<input type="text" class="form-control" id="frm_l_phone_number" name="frm_l_phone_number" value="<?php echo isset($result) ? $result['l_phone_number'] : '' ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="frm_l_website" class="col-sm-3 control-label">Website Address</label>
				<div class="col-sm-9">
					<input type="text" class="form-control" id="frm_l_website" name="frm_l_website" value="<?php echo isset($result) ? $result['l_website'] : '' ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="frm_l_details" class="col-sm-3 control-label">Services/Details</label>
				<div class="col-sm-9">
					<?php 
						wp_editor(
							isset($result) ? $result['l_details'] : '',
							'frm_l_details',
							array(
								'media_buttons' => false,
								'quicktags' => false,
								'textarea_rows' => 12,
								'tinymce' => array(
									'content_css' => NOVA_MAP_URLPATH . 'css/nova-map-editor-style.css'
								)
							)
						); ?>
				</div>
			</div>
			<div class="map-container">
				<div class="nova_search_load">
					<i class="fa fa-spinner fa-spin fa-5x"></i>
				</div>
				<p><strong>Please check and confirm that the location of the service is properly mapped below.</strong></p>
				<p>The map relies on data from Google Maps, if you are unable to locate the address it may be because this address does not exist in <a href="https://maps.google.com/" target="_blank">Google Maps</a>.</p>
				<a id="nm_btn_plot" class="btn btn-success btn-sm" data-plot="automatic" style="margin-top: 10px;">Plot <strong>MANUALLY</strong></a>
				<div id="admin_nova_map"></div>
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
<?php else: ?>
<h3>Location not found.</h3>
<?php endif; ?>
</div>