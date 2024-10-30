<script>
	jQuery( "#lendingq_item_type-adder" ).hide();
	jQuery( "#lendingq_item_type-tabs  li:nth-child(n+1)" ).hide();
	jQuery( "#lendingq_location-adder" ).hide();
	jQuery( "#lendingq_location-tabs  li:nth-child(n+1)" ).hide();
	jQuery(document).ready(function() {
		jQuery("input[name=\"tax_input[lendingq_locations][]\"]").click(function () {
			selected = jQuery("input[name=\"tax_input[lendingq_locations][]\"]").filter(":checked").length;
			if (selected > 1){
				jQuery("input[name=\"tax_input[lendingq_locations][]\"]").each(function () {
					jQuery(this).attr("checked", false);
				});
				jQuery(this).attr("checked", true);
			}
		});
	});
</script>
<div id="lendingq_section" class="section section-text">
	<span style="font-size:1.3em"><?php echo nl2br( get_option( 'lendingq_field_header_stock' ) ); ?></span>
	<!-- ----------------------------------- -->
	<?php
	if( $post_status == 'item_unavailable' ) {
		$return_reasons = [ 'item_lost' => __( 'Item Lost', 'lendingq' ), 'item_stolen' => __( 'Item Stolen', 'lendingq' ), 'item_broken' => __( 'Item Broken', 'lendingq' ) ];
	?>
	<div style="border:2px solid red; background: #f99; padding: 12px;">
		<h1><?php _e( "This item is unavailable!", 'lendingq' ); ?></h1>
		<?php _e( "<p>If you would like to clear the return notes and mark it as available again, please check the following box, double check the other settings, and click Submit.</p><p>If you would like to send it to the trash, use the link at the bottom of the form.</p>", 'lendingq' ); ?>
		<table class="form-table" role="presentation" >
			<tbody>
				<tr>
					<th scope="row">
						<label for="name"><?php _e( 'Return Status') ?></label>
					</th>
					<td>
						<?php 
						$reason_temp = current( array_filter( $meta['unavail_status'] ) );
						echo $return_reasons[$reason_temp]; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="name"><?php _e( 'Check in Date') ?></label>
					</th>
					<td>
						<?php echo current( array_filter( $meta['check_in_date'] ) ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="name"><?php _e( 'Return Notes') ?></label>
					</th>
					<td>
						<?php echo nl2br( current( array_filter( $meta['return_notes'] ) ) ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="name"><?php _e( 'Make available') ?></label>
					</th>
					<td>
						<input id="reenable" name="reenable" type="checkbox" value="true">
					</td>
				</tr>
			</tbody>
		</table>
		<p class="description" id="name-description"><?php _e( 'This will clear the return notes and make this item available for check outs.', 'lendingq' ); ?></p>
	</div>
	<?php
	}
	# check for invalid lending
	if( count( $error_msg ) > 0 ) {
		foreach( $error_msg as $val ) {
			echo '		  <div class="lendingq_notice">';
			echo '			  <p>'.__( $val, 'lendingq' ).'</p>';
			echo '		  </div>';
		}
	}
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="name"><?php echo get_option( "lendingq_field_item_name" ); ?>*</label>
				</th>
				<td>
					<input id="item_name" class="regular-text" name="item_name" type="text" value="<?php echo $form['item_name']; ?>" required>
					<p class="description" id="name-description"><?php echo get_option( "lendingq_field_item_name_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="item_length"><?php _e( 'Days to lend' ); ?>*</label>
				</th>
				<td>
					<input id="item_length" class="regular-text" name="item_length" type="text" value="<?php echo $form['item_length']; ?>" required>
					<p class="description" id="item_length-description"><?php _e( 'This must be a number and is how many days after checkout until the item becomes "overdue"', 'lendingq' ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="item_manuf"><?php echo get_option( "lendingq_field_item_manuf" ); ?>*</label>
				</th>
				<td>
					<input id="item_manuf" class="regular-text" name="item_manuf" type="text" required value="<?php echo $form['item_manuf']; ?>">
					<p class="description" id="item_manuf-description"><?php echo get_option( "lendingq_field_item_manuf_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="item_model"><?php echo get_option( "lendingq_field_item_model" ); ?>*</label>
				</th>
				<td>
					<input id="item_model" class="regular-text" name="item_model" type="text" required value="<?php echo $form['item_model']; ?>">
					<p class="description" id="item_model-description"><?php echo get_option( "lendingq_field_item_model_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			 <tr>
				<th scope="row">
					<label for="item_serial"><?php echo get_option( "lendingq_field_item_serial" ); ?>*</label>
				</th>
				<td>
					<input id="item_serial" class="regular-text" name="item_serial" required type="text" value="<?php echo $form['item_serial']; ?>">
					<p class="description" id="item_serial-description"><?php echo get_option( "lendingq_field_item_serial_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="item_notes"><?php echo get_option( "lendingq_field_item_notes" ); ?></label>
				</th>
				<td>
					<textarea id="item_notes" class="regular-text" name="item_notes" rows="5"><?php echo $form['item_notes']; ?></textarea>
					<p class="description" id="item_notes-description"><?php echo get_option( "lendingq_field_item_notes_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					&nbsp;
				</th>
				<td>
					<input type="submit" value="Submit" class="button button-primary button-large" tabindex="-1">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php
						$trash_url = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_cancel_hold', 'post_id' => $post->ID ], admin_url( 'edit.php' ) );
						echo wp_nonce_url( $trash_url, 'lendingq_cancel_hold' ); ?>"><?php _e( 'Delete Item', 'lendingq' ); ?></a>
				</td>
			</tr>
			<!-- ----------------------------------- -->
		</tbody>
	</table>
</div>