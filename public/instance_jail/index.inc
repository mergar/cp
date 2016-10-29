<?php
require_once($rp.'/cbsd.inc.php');
require_once($rp.'/left_menu.inc.php');
?>

<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">

	<h1 class="page-header">Helpers</h1>
	<div class="row placeholders">
		<div class="col-xs-6 col-sm-2 placeholder">
		</div>
	</div>

	<h2 class="sub-header">Helpers</h2>
	<div class="table-responsive">
	<table class="table table-striped">
		<thead>
		<tr>
		<th>Name</th>
		<th>Path</th>
		</tr>
		</thead>
		<tbody>
	<?php
		require_once('show_instances.php');
		show_instances();
	?>

</div>
