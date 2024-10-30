<script>
	jQuery( "#lendingq_item_type-adder" ).hide();
	jQuery( "#lendingq_item_type-tabs  li:nth-child(n+1)" ).hide();
	jQuery( "#lendingq_location-adder" ).hide();
	jQuery( "#lendingq_location-tabs  li:nth-child(n+1)" ).hide();
	jQuery(document).ready(function() {
		jQuery("#form_date").datepicker();
		jQuery("input[name=\"tax_input[lendingq_locations][]\"]").click(function () {
			selected = jQuery("input[name=\"tax_input[lendingq_locations][]\"]").filter(":checked").length;
			if (selected > 1){
				jQuery("input[name=\"tax_input[lendingq_locations][]\"]").each(function () {
					jQuery(this).attr("checked", false);
				});
				jQuery(this).attr("checked", true);
			}
		});
		jQuery( "#email" ).change( function() {
			var emailval = jQuery( '#email' ).val().length;
			if( emailval > 1 ) {
				document.getElementById("contact_email").setCustomValidity("");
			}
		});
		jQuery( "#phone" ).change( function() {
			var phoneval = jQuery( '#phone' ).val().length;
			if( phoneval > 1 ) {
				document.getElementById("contact_phone").setCustomValidity("");
			}
		});
		jQuery( "input[type='radio'][name='contact']" ).click( function() {
			var contactval = jQuery('input[name=contact]:checked', '#post').val();
			var emailval = jQuery( '#email' ).val().length;		   
			var phoneval = jQuery( '#phone' ).val().length;
			if( contactval == 'email' ) {
				document.getElementById("contact_phone").setCustomValidity("");
				if( emailval < 1 ) {
					document.getElementById("contact_email").setCustomValidity("<?php _e( "You've chosen Email but haven't entered a valid email into the field.", 'lendingq' ); ?>");
					jQuery('#email').focus();
				}
			}
			if( contactval == 'phone' ) {
				document.getElementById("contact_email").setCustomValidity("");
				if( phoneval < 1 ) {
					document.getElementById("contact_phone").setCustomValidity("<?php _e( "You've chosen Phone but haven't entered a valid phone number into the field.", 'lendingq' ); ?>");
					jQuery('#phone').focus();
				}
			}
		});
	});
</script>
<div id="lendingq_section" class="section section-text">
	<span style="font-size:1.3em"><?php echo nl2br( get_option( 'lendingq_field_header_hold' ) ); ?></span>
	<?php
	if( count( $error_msg ) > 0 ) {
		foreach( $error_msg as $val ) {
			echo '		  <div class="lendingq_notice">';
			echo '			  <p>'.__( $val, 'lendingq' ).'</p>';
			echo '		  </div>';
		}
	}
		?>
	<!-- ----------------------------------- -->
	<table class="form-table" role="presentation">
		<tbody>
			<!-- ----------------------------------- -->
			<?php 
			if( $post->post_status == 'checked_out' ) {
				?>
			<tr>
				<th scope="row">
					<label for="waiting_time"><?php _e( 'Item Type', 'lendingq' ); ?></label>
				</th>
				<td>
					<?php echo $item['item_type']; ?>
				</td>
			</tr> 
			<tr>
				<th scope="row">
					<label for="waiting_time"><?php _e( 'Item Location', 'lendingq' ); ?></label>
				</th>
				<td>
					<?php echo $item['item_location']; ?>
				</td>
			</tr> 
			<tr>
				<th scope="row">
					<label for="form_date"><?php _e( 'Checkout Date', 'lendingq' ); ?></label>
				</th>
				<td>
				   <?php echo date_i18n( get_option('date_format'), $form['checked_out_date'] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waiting_time"><?php _e( 'Due Date', 'lendingq' ); ?></label>
				</th>
				<td>
					<?php echo date_i18n( get_option('date_format'), $form['due_date'] ); ?>
				</td>
			</tr> 
			<?php
			} elseif( $post->post_status == 'waiting_list' or !empty( $form['form_date'] ) or !empty( $form['form_time'] ) ) {
			?>
			<tr>
				<th scope="row">
					<label for="form_date"><?php _e( 'Hold Date', 'lendingq' ); ?>*</label>
				</th>
				<td>
					<input id="form_date" class="regular-text" name="form_date" type="text" required value="<?php echo $form['form_date']; ?>">
					<p class="description" id="form_date-description"><?php _e( 'You can change the date that the item was added to the waiting list here.', 'lendingq' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waiting_time"><?php _e( 'Lending Time', 'lendingq' ); ?>*</label>
				</th>
				<td>
					<input id="form_time" class="regular-text" name="form_time" type="text" required value="<?php echo $form['form_time']; ?>">
					<p class="description" id="waiting_time-description"><?php _e( 'You can change the time of day that the item was added to the waiting list here.', 'lendingq' ); ?></p>
				</td>
			</tr>
			<?php
			}
			?>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="name"><?php echo get_option( "lendingq_field_name" ); ?>*</label>
				</th>
				<td>
					<input id="name" class="regular-text" name="name" type="text" value="<?php echo $form['name']; ?>" required>
					<p class="description" id="name-description"><?php echo get_option( "lendingq_field_name_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="card"><?php echo get_option( "lendingq_field_card" ); ?>*</label>
				</th>
				<td>
					<input id="card" class="regular-text" name="card" type="text" required value="<?php echo $form['card']; ?>">
					<p class="description" id="card-description"><?php echo get_option( "lendingq_field_card_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="verified"><?php echo get_option( "lendingq_field_verified" ); ?>*</label>
				</th>
				<td>
					<?php
					$checked_yes = null;
					if( $form['verified'] == 'true' ) {
						$checked_yes = ' checked="checked"';
					} 
					?>
				<input required type="checkbox" name="verified" value="true"<?php echo $checked_yes; ?>> Yes<br><br>
					<p class="description" id="verified-description"><?php echo get_option( "lendingq_field_verified_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="phone"><?php echo get_option( "lendingq_field_phone" ); ?></label>
				</th>
				<td>
					<input id="phone" class="regular-text" name="phone" type="text" value="<?php echo $form['phone']; ?>">
					<p class="description" id="phone-description"><?php echo get_option( "lendingq_field_phone_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="email"><?php echo get_option( "lendingq_field_email" ); ?></label>
				</th>
				<td>
				<input id="email" class="regular-text" name="email" type="email" value="<?php echo $form['email']; ?>">
					<p class="description" id="email-description"><?php echo get_option( "lendingq_field_email_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="contact"><?php echo get_option( "lendingq_field_contact" ); ?>*</label>
				</th>
				<td>
					<?php
					$checked_email = null;
					$checked_phone = null;
					if( $form['contact'] == 'email' ) {
						$checked_email = ' checked="checked"';
					} # END if( $form['contact'] == 'email' )
					if( $form['contact'] == 'phone' ) {
						$checked_phone = ' checked="checked"';
					} # END if( $form['contact'] == 'phone' )
					?>
				<input required type="radio" name="contact" id="contact_email" value="email"<?php echo $checked_email; ?>> Email&nbsp;&nbsp;&nbsp;&nbsp;<input required type="radio" name="contact" id="contact_phone" value="phone"<?php echo $checked_phone; ?>> Phone<br><br>
					<p class="description" id="contact-description"><?php echo get_option( "lendingq_field_contact_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="staff"><?php echo get_option( "lendingq_field_staff" ); ?>*</label>
				</th>
				<td>
					<input id="staff" class="regular-text" name="staff" type="text" value="<?php echo $form['staff']; ?>" required>
					<p class="description" id="staff-description"><?php echo get_option( "lendingq_field_staff_desc" ); ?></p>
				</td>
			</tr>
			<!-- ----------------------------------- -->
			<tr>
				<th scope="row">
					<label for="notes"><?php echo get_option( "lendingq_field_notes" ); ?></label>
				</th>
				<td>
					<textarea id="notes" class="regular-text" name="notes" rows="5"><?php echo $form['notes']; ?></textarea>
					<p class="description" id="notes-description"><?php echo get_option( "lendingq_field_notes_desc" ); ?></p>
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
						$trash_url = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_cancel_hold', 'post_id' => $post->ID  ], admin_url( 'edit.php' ) );
						echo wp_nonce_url( $trash_url, 'lendingq_cancel_hold' ); ?>"><?php _e( 'Cancel Hold', 'lendingq' ); ?></a>
				</td>
			</tr>
			<!-- ----------------------------------- -->
		</tbody>
	</table>
</div>