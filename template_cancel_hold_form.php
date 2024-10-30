<div class="wrap">
	<h1><?php _e( 'LendingQ Cancel Hold', 'lendingq' ); ?></h1>
	<?php _e( 'Please double check the information before cancelling this hold.', 'lendingq'); ?>
	<form action="<?php echo admin_url( 'admin-post.php' ) ?>">
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php _e( 'Name', 'lendingq'); ?></th>
				<td><?php echo $lend['name']; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Card', 'lendingq'); ?></th>
				<td><?php echo $lend['card']; ?></td>
			</tr>
			<tr>
					<th scope="row">
						<?php echo get_option( "lendingq_field_contact" ); ?>
					</th>
					<td>
						<?php
						switch( $lend['contact'] ) {
							case 'phone':
								echo "<span style=\"font-size: 1.3em; font-weight: bold;\">Phone: {$lend['phone']}</span>";
								if( !empty( $lend['email'] ) ) {
									echo "<br>Email: <a href=\"mailto:{$lend['email']}\">";			  
								}
								break;
							case 'email':
								echo "<span style=\"font-size: 1.3em; font-weight: bold;\">Email: <a href=\"mailto:{$lend['email']}\">{$lend['email']}</a></span>";
								if( !empty( $lend['phone'] ) ) {
									echo '<br>Phone: '.$lend['phone'];
								}
								break;
							default:
								#die( "Unknown contact in contact hold");
						}
						?>
					</td>
				</tr>
			<tr>
				<th scope="row"><?php _e( 'Helped by', 'lendingq'); ?></th>
				<td><?php echo $lend['staff']; ?></td>
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