<script>
	jQuery(document).ready(function() {
		jQuery("#contact_date").datepicker();
	});
</script>
<div class="wrap">
	<h1><?php _e( 'LendingQ Hold Contact', 'lendingq' ); ?></h1>
	<?php _e( 'You are marking this hold as being contacted.', 'lendingq'); ?>
	<form action="<?php echo admin_url( 'admin-post.php' ) ?>">
		<?php
		  if( count( $error_msg ) > 0 ) {
			  foreach( $error_msg as $val ) {
				  echo '		<div class="lendingq_notice">';
				  echo '			<p>'.__( $val, 'lendingq' ).'</p>';
				  echo '		</div>';
			  }
		  }
		?>
		<!-- ----------------------------------- -->
		<table class="form-table" role="presentation">
			<tbody>
				<!-- ----------------------------------- -->
								<tr>
					<th scope="row">
						<?php _e( 'Item Type', 'lendingq' ); ?>
					</th>
					<td>
						<?php
						echo $item_types[$lend['item_type']];
						?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php _e( 'Location', 'lendingq' ); ?>
					</th>
					<td>
						<?php
						echo $locations[$lend['location']];
						?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php _e( 'Hold Date', 'lendingq' ); ?>
					</th>
					<td>
						<?php echo date_i18n( get_option('date_format'), $lend['waiting_date'] ) . ' - ' . date_i18n( get_option('time_format'), $lend['waiting_date'] ); ?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php echo get_option( "lendingq_field_name" ); ?>
					</th>
					<td>
						<?php echo $lend['name']; ?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
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
								die( "Unknown contact in contact hold");
						}
						?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php echo get_option( "lendingq_field_staff" ); ?>
					</th>
					<td>
						<?php echo $lend['staff']; ?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php echo get_option( "lendingq_field_notes" ); ?>
					</th>
					<td>
						<?php echo nl2br( $lend['notes'] ); ?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php echo _e( 'Staff who contacted', 'lendingq' ); ?>
					</th>
					<td>
						<?php
							$contact_staff = ( empty( $_REQUEST['contact_staff'] ) ) ? null : sanitize_text_field( $_REQUEST['contact_staff'] );
						?>
						<input type="text" name="contact_staff" id="contact_staff" value="<?php echo $contact_staff; ?>">
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						<?php echo _e( 'Contact Notes', 'lendingq' ); ?>
					</th>
					<td>
						<?php
						$contact_notes = ( empty( $_REQUEST['contact_notes'] ) ) ? null : sanitize_textarea_field( $_REQUEST['contact_notes'] );
						?>
						<textarea class="regular-text" rows="5" id="contact_notes" name="contact_notes"><?php echo $contact_notes; ?></textarea>
					</td>
				</tr>
				<!-- ----------------------------------- -->
				<tr>
					<th scope="row">
						&nbsp;
					</th>
					<td>
						<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>">
						<input type="hidden" name="action" id="action" value="lendingq_contacted">
						<?php 
						echo wp_nonce_field( 'lendingq_contacted' ); 
						submit_button( __( 'Mark as Contacted', 'lendingq' ), 'primary', 'submit' );
						?>
					</td>
				</tr>
				<!-- ----------------------------------- -->
			</tbody>
		</table>
	</form>
</div>