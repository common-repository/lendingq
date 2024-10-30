<?php
if( $transients = get_transient( 'lendingq_checkin_form' ) ) {
	$cur_date		= $transients['check_in_date'];
	$return_status	= $transients['return_status'];
	$return_reason	= $transients['return_status'];
	$return_notes	= $transients['return_notes'];
	$unavail_status = $transients['unavail_status'];
} else {
	$cur_date = ( empty( $_REQUEST['check_in_date'] ) ) ? date_i18n( get_option('date_format'), false ) : $_REQUEST['check_in_date'] ;
	$return_status = $return_reason = $return_notes = null;
}
set_transient( 'lendingq_checkin_form', null );
#$overdue_days = intval( ( $lend['due_date'] - time() ) / 86400 );
$due_date = date_i18n( get_option('date_format'), $lend['due_date'] );
$now =	date_i18n( get_option('date_format'), time() );
$overdue_days = intval( ( strtotime( $now ) - strtotime( $due_date ) ) / 86400 );
$plural_message = _n_noop( '%s day', '%s days', 'lendingq' );
if( $overdue_days > 0 ) {
	$overdue = '<h3 style="color:red; font-weight: bold; ">' . sprintf( __( "Overdue by %s", 'lendingq' ), sprintf( translate_nooped_plural( $plural_message, $overdue_days, 'lendingq' ), number_format_i18n( abs( $overdue_days ) ) ) ) . '</h3>';
} elseif( $overdue_days == 0 ) {
	$overdue = '<h3 style="color:green; font-weight: bold; ">' . __( "Due today", 'lendingq' ) . '</h3>';
} else {
	$overdue = '<h3 style="color:green; font-weight: bold; ">' . sprintf( __( "Due in %s", 'lendingq' ), sprintf( translate_nooped_plural( $plural_message, $overdue_days, 'lendingq' ), number_format_i18n( abs( $overdue_days ) ) ) ) . '</h3>';
}
if( !empty( $_REQUEST['error'] ) ) {
	foreach( $this->list_errors_hold( $_REQUEST['error'] ) as $error_msg ) {
		echo '	<div class="notice notice-error"><p>'.$error_msg.'</p></div>';	 
	}
}
?>
<script>
	function swap_status() {
		if ( jQuery("#return_status").children("option:selected"). val() == 'item_unavailable')
			{
				jQuery("#show_status").show();
			}
			else
			{
				jQuery("#show_status").hide();
			}
	}
	jQuery(document).ready(function() {
		jQuery("#check_in_date").datepicker();
		swap_status();
		jQuery('#return_status').on('change', swap_status );
	});
</script>
<div class="wrap">
	<h1><?php _e( 'LendingQ Check In', 'lendingq' ); ?></h1>
	<?php _e( 'You are accepting the following item as returned on the following date.', 'lendingq'); ?>
	<form action="<?php echo admin_url( 'admin-post.php' ) ?>">
		<h2><?php _e( 'Check In', 'lendingq' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Check In Date', 'lendingq'); ?></th>
					<td><input type="text" name="check_in_date" id="check_in_date" value="<?php echo $cur_date; ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Checked Out Date', 'lendingq'); ?></th>
					<td><?php echo date_i18n( get_option('date_format'), $lend['checked_out_date'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Due Date', 'lendingq'); ?></th>
					<td><?php echo date_i18n( get_option('date_format'), $lend['due_date'] ); 
						echo '<br>'.$overdue; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Return Status', 'lendingq'); ?></th>
					<td>
						<?php
						$return_options = [ 'item_available' => __( 'Item Available', 'lendingq' ), 
											'item_unavailable' => __( 'Item Not Available', 'lendingq' )];
						$selected = ( empty( $return_status ) ) ? null : ' selected="selected"';
						echo '<select name="return_status" id="return_status">';
						echo "<option value=\"\"{$selected}>Choose a status</option>";
						foreach( $return_options as $key => $val ) {
							$selected = ( $key == $return_status ) ? ' selected="selected"' : null;
							echo "<option value=\"{$key}\"{$selected}>{$val}</option>";
						}
						echo '</select>';
						?>
					</td>
				</tr>
				<tr id="show_status">
					<th scope="row"><?php _e( 'Reason Item is Unavailable', 'lendingq'); ?></th>
					<td>
						<?php
						$return_reasons = [ 'item_lost' => __( 'Item Lost', 'lendingq' ), 
											'item_stolen' => __( 'Item Stolen', 'lendingq' ), 
											'item_broken' => __( 'Item Broken', 'lendingq' ), 
										  ];
						echo '<select name="unavail_status" id="unavail_status">';
						$selected = ( empty( $unavail_status ) ) ? null : ' selected="selected"';
						echo "<option value=\"\"{$selected}>Choose a reason</option>";
						foreach( $return_reasons as $key => $val ) {
							$selected = ( $key == $unavail_status ) ? ' selected="selected"' : null;
							echo "<option value=\"{$key}\"{$selected}>{$val}</option>";
						}
						echo '</select>';
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Return Notes', 'lendingq'); ?></th>
					<td>
						<textarea name="return_notes" id="return_notes" rows="3" class="regular-text"><?php echo $return_notes; ?></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>">
		<input type="hidden" name="action" id="action" value="lendingq_checkin">
		<?php 
		echo wp_nonce_field( 'lendingq_checkin' ); 
		submit_button( 'Check In Item', 'primary', 'submit' );
		?>
		<hr style="border: 1px solid grey;">
		<h2><?php _e( 'User Information', 'lendingq' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Loanee Name', 'lendingq'); ?></th>
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
					<td><?php echo $lend['item_type']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Location', 'lendingq'); ?></th>
					<td><?php echo $lend['location']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Notes', 'lendingq'); ?></th>
					<td><?php echo nl2br( $lend['notes'] ); ?></td>
				</tr>
			</tbody>
		</table>
		<hr style="border: 1px solid grey;">
		<h2><?php _e( 'Item Information', 'lendingq' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Item Name', 'lendingq'); ?></th>
					<td><?php echo $item['name']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Type', 'lendingq'); ?></th>
					<td><?php echo $item['type']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Location', 'lendingq'); ?></th>
					<td><?php echo $item['location']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Manufacturer', 'lendingq'); ?></th>
					<td><?php echo $item['manuf']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Model', 'lendingq'); ?></th>
					<td><?php echo $item['model']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Serial', 'lendingq'); ?></th>
					<td><?php echo $item['serial']; ?></td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Notes', 'lendingq'); ?></th>
					<td><?php echo nl2br( $item['notes'] ); ?></td>
				</tr>
			</tbody>
		</table>
	</form>
</div>