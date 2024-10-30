<div class="wrap">
	<h1><?php _e( 'LendingQ Delete Item', 'lendingq' ); ?></h1>
	<?php _e( 'Please double check the information before removing this item.', 'lendingq'); ?>
	<form action="<?php echo admin_url( 'admin-post.php' ) ?>">
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php _e( 'Name', 'lendingq'); ?></th>
				<td><?php echo $lend['item_name']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Model', 'lendingq'); ?></th>
				<td><?php echo $lend['item_model']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Manufacturer', 'lendingq'); ?></th>
				<td><?php echo $lend['item_manuf']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Serial', 'lendingq'); ?></th>
				<td><?php echo $lend['item_serial']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Check Out Length', 'lendingq'); ?></th>
				<td><?php echo $lend['item_length']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Notes', 'lendingq'); ?></th>
				<td><?php echo $lend['item_notes']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Item Type', 'lendingq'); ?></th>
				<td><?php echo $item_types[$lend['item_type']]; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Location', 'lendingq'); ?></th>
				<td><?php echo $locations[$lend['location']]; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Notes', 'lendingq'); ?></th>
				<td><?php echo nl2br( $lend['notes'] ); ?></td>
			</tr>
		</tbody>
	</table>
		<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>">
		<input type="hidden" name="action" id="action" value="lendingq_cancel_hold">
		<?php 
		echo wp_nonce_field( 'lendingq_cancel_hold' ); 
		submit_button( 'Cancel Lending', 'primary', 'submit' );
		$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list' ], admin_url( 'edit.php' ) );
		?>
		<strong><a href="<?php echo $url; ?>"><?php _e( 'Go back to the Check Out list and do not cancel this hold.', 'lendingq' ); ?></a></strong>
	</form>
</div>