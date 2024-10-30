<div class="wrap">
	<h1><?php _e( 'LendingQ Check Out', 'lendingq' ); ?></h1>
	<?php _e( 'Please double check this information before selecting an item to checkout from the drop down below.', 'lendingq'); ?>
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
					<th scope="row"><?php _e( 'Contact', 'lendingq'); ?></th>
					<td><?php echo $lend['contact']; ?></td>
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
					<td><?php echo $lend['notes']; ?></td>
				</tr>
			</tbody>
		</table>
		<?php
		if( !empty( $lend['contact_date'] ) ) {
			?>
		<hr style="border: 1px solid grey;">
		<table class="form-table" role="presentation">
			<tbody>		
				<tr>
					<th scope="row"><?php _e( 'Contacted by', 'lendingq'); ?></th>
					<td><?php echo $lend['contact_staff']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Contact date', 'lendingq'); ?></th>
					<td><?php echo date( get_option( 'date_format' ), $lend['contact_date'] ) . ' - ' . date( 'g:i a', $lend['contact_date'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Contact Notes', 'lendingq'); ?></th>
					<td><?php echo nl2br( $lend['contact_notes'] ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
		}
		?>
		<hr style="border: 1px solid grey;">
		<table class="form-table" role="presentation">
			<tbody>		
				<tr>
					<th scope="row"><?php _e( 'Item to Lend', 'lendingq'); ?></th>
					<td><?php
						# check there is stock
						$args = [
										'numberposts' => -1, 
										'post_type'=> 'lendingq_stock',
										'post_status'	 => 'item_available'
						];
						$item_list = get_posts( $args );
						$available = [];
						$item = $this->get_items();

						foreach( $item_list as $key => $post ) {
							$temp = [];
							$location_raw		= get_the_terms( $post->ID, 'lendingq_location' );
							$location			= $location_raw[0]->slug;
							$item_type_raw		= get_the_terms( $post->ID, 'lendingq_item_type' );
							$item_type			= $item_type_raw[0]->slug;
							if( $location == $lend['location'] and $item_type == $lend['item_type'] ) {
								$available[$post->ID] = $item[$post->ID][item_name];
							}
						}
						if( count( $available ) == 0 ) {
							echo '<strong>'. __( 'There is currently no stock available for this lending.', 'lendingq' ).'</strong>';
						} else {
							echo '<select name="lending_item" id="lending_item">';
							foreach( $available as $key => $val ) {
								echo "<option value=\"{$key}\">{$val}</option>";
							}
							echo '</select>';
						}
						?></td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>">
		<input type="hidden" name="action" id="action" value="lendingq_checkout">
		<?php 
		echo wp_nonce_field( 'lendingq_checkout' ); 
		submit_button( 'Lend Item', 'primary', 'submit' );
		?>
	</form>
</div>