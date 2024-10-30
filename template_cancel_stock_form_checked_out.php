<div class="wrap">
	<h1><?php _e( 'LendingQ Delete Item', 'lendingq' ); ?></h1>
	<h2><?php _e( 'This item is currently checked out and cannot be deleted.', 'lendingq'); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php _e( 'Location', 'lendingq'); ?></th>
				<td><?php echo $locations[$item['location']]; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Notes', 'lendingq'); ?></th>
				<td><?php echo nl2br( $item['item_notes'] ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Name', 'lendingq'); ?></th>
				<td><?php echo $item['item_name']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Model', 'lendingq'); ?></th>
				<td><?php echo $item['item_model']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Manufacturer', 'lendingq'); ?></th>
				<td><?php echo $item['item_manuf']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Serial', 'lendingq'); ?></th>
				<td><?php echo $item['item_serial']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Check Out Length', 'lendingq'); ?></th>
				<td><?php echo $item['item_length']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Notes', 'lendingq'); ?></th>
				<td><?php echo $item['item_notes']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Item Type', 'lendingq'); ?></th>
				<td><?php echo $item_types[$item['item_type']]; ?></td>
			</tr>
		</tbody>
	</table>
	<?php
	$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_in_list' ], admin_url( 'edit.php' ) );
	?>
	<p><a href="<?php echo $url; ?>"><?php _e( 'Go to the Check In list.' ); ?></a></p>
</div>