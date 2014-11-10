<?php
// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

define('NOVA_MAP', plugin_basename(dirname(__FILE__)));
define('NOVA_MAP_URLPATH', trailingslashit( plugins_url( '', __FILE__ ) ) );

// Set Sort Values
$val_09 = range(0, 9);
$val_az = range('A', 'Z');
$sort_values = array_merge($val_09, $val_az);

echo '
	<script>
		var nova_ajax = {
			ajaxurl: "' . admin_url('admin-ajax.php') . '"
		};
		var admin_url = "' . admin_url('admin.php?page=' . NOVA_MAP) . '";
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
		<small>Edit or delete any entry in the Service Map system from this screen. Use the column titles to reorganize the listings.</small>
	</div>
</div>

<div class="alert alert-success nm_locations_alert hide"></div>
<div class="nm_admin_loading">
	<i class="fa fa-spinner fa-spin fa-5x"></i>
</div>

<!-- Top Button Actions -->
<div class="row" style="margin-right: 0;">
	<div class="col-md-6">
		<div class="btn-group">
			<button type="button" class="btn btn-default">Bulk Actions</button>
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				<span class="caret"></span>
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a class="nm_btn_save nm_delete_location"><i class="fa fa-times"></i> Delete</a></li>
			</ul>
		</div>
	</div>
	<div class="col-md-6">
		<div class="form-inline nm_locations_sort_search">
			<div class="form-group col-sm-4 column">
				<label for="nm_sort">Sort Names</label>
				<select id="nm_sort">
					<option value="" selected>All</option>
					<?php for($i = 0; $i < count($sort_values); $i++): ?>
					<option value="<?php echo $sort_values[$i] ?>"><?php echo $sort_values[$i] ?></option>
					<?php endfor; ?>
				</select>
			</div>
			<div class="form-group col-sm-8 column">
				<div class="input-group">
					<input type="text" class="form-control" id="nm_search" placeholder="Search Names">
					<span class="input-group-addon"><i class="fa fa-search"></i></span>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Results -->
<table class="table table-striped tablesorter nm_locations">
	<thead>
		<tr>
			<th style="width: 5%;"><input type="checkbox" class="nm_toggle_check"></th>
			<th style="width: 15%;"><a>Name</a></th>
			<th style="width: 15%;">Address</th>
			<th style="width: 10%;">Phone Number</th>
			<th style="width: 10%;"><a>Website</a></th>
			<th style="width: 35%;">Details</th>
			<th style="width: 10%;">Added On</th>
		</tr>
	</thead>
	<tbody></tbody>
	<tfoot>
		<tr>
			<th style="width: 5%;"><input type="checkbox" class="nm_toggle_check"></th>
			<th style="width: 20%;"><a>Name</a></th>
			<th style="width: 20%;">Address</th>
			<th style="width: 10%;">Phone Number</th>
			<th style="width: 10%;"><a>Website</a></th>
			<th style="width: 25%;">Details</th>
			<th style="width: 10%;">Added On</th>
		</tr>
	</tfoot>
</table>

<!-- Bottom Button Actions -->
<div class="btn-group">
	<button type="button" class="btn btn-default">Bulk Actions</button>
	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		<span class="sr-only">Toggle Dropdown</span>
	</button>
	<ul class="dropdown-menu" role="menu">
		<li><a class="nm_btn_save nm_delete_location"><i class="fa fa-times"></i> Delete</a></li>
	</ul>
</div>
