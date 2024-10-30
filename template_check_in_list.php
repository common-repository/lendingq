<?php
$loaned_count = 0;
if( !empty( $post_list ) ) {
	foreach( $post_list as $key => $val ) {
		if( $val->post_status == 'checked_out' ) $loaned_count++;
	}
}
?>
<div class="wrap">
	<h1><?php _e( 'LendingQ Check In', 'lendingq' ); ?></h1>
	<?php
	$plural_message = _n_noop( '%s day', '%s days', 'lendingq' );
	# Get an array of all available items, sorted by
	foreach( $item_types as $type_key => $type_name ) {
		?>
	<h2><?php printf( __( '%s Availability', 'lendingq' ), $type_name ); ?></h2>
	<?php
		if( $loaned_count == 0 ) {
			echo '<strong style="font-size:1.2em">'.__( 'There are currently none checked out', 'lendingq' ) . '</strong>';
		} else {
			# go through each location and see if there are any checked out
			$holds =[];
			foreach( $locations as $lkey => $loc ) {
				if( empty( $hold_list['checked_out'][$type_key][$lkey] ) ) continue;
				foreach( $hold_list['checked_out'][$type_key][$lkey] as $vkey => $vval ) {
					$temp = $vval; 
					$temp['item'] = $item[$vval['item_id']];
					$holds[$vkey] = $temp;
				}
			}
			
			uasort($holds, function($a, $b) {
				 if ($a['due_date'] == $b['due_date']) {
					 return 0;
				 }
				return ($a['due_date'] < $b['due_date']) ? -1 : 1;
				#return $a['due_date'] <=> $b['due_date'];
			});
			# if there are some available and people waiting, show
			# those users for the check in list
			if( count( $holds ) == 0 ) {
				echo '<h4>' . __( 'There are currently no holds waiting to be checked in.', 'lendingq' ) . '<h4>';
			} else {
		?>
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr>
					<th id="columnname" class="manage-column column-in-name" scope="col">Name</th>
					<th id="columnname" class="manage-column column-in-location" scope="col">Location</th>
					<th id="columnname" class="manage-column column-in-due_date" scope="col">Due Date</th>
					<th id="columnname" class="manage-column column-in-item" scope="col">Item Info</th>
					<th id="columnname" class="manage-column column-check-in" scope="col">Check In</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<th id="columnname" class="manage-column column-in-name" scope="col">Name</th>
					<th id="columnname" class="manage-column column-in-location" scope="col">Location</th>
					<th id="columnname" class="manage-column column-in-due_date" scope="col">Due Date</th>
					<th id="columnname" class="manage-column column-in-item" scope="col">Item Info</th>
					<th id="columnname" class="manage-column column-check-in" scope="col">Check In</th>
				</tr>
			</tfoot>
			<tbody class="lending_table_col">
				<?php
				foreach( $holds as $hkey => $hval ) {
					$due_date = date_i18n( get_option('date_format'), $hval['due_date'] );
					$now =	date_i18n( get_option('date_format'), time() );
					$days = intval( ( strtotime( $now ) - strtotime( $due_date ) ) / 86400 );
					if( $days < 0 ) {
						$due_message = '<span style="color:green">'.sprintf( __( 'Due in %s', 'lendingq' ), sprintf( translate_nooped_plural( $plural_message, $days, 'lendingq' ), number_format_i18n( abs( $days ) ) ) ).'</span>'; 
					} elseif( $days > 0 ) {
						$due_message = '<span style="color:red">'.sprintf( __( 'Overdue by %s', 'lendingq' ), sprintf( translate_nooped_plural( $plural_message, $days, 'lendingq' ), number_format_i18n( abs( $days ) ) ) ).'</span>'; 
					} else {
						$due_message = __( 'Due today', 'lendingq' );
					}
					$nonce = wp_create_nonce( 'lendingq_checkin' );
					$check_in_link = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_check_in', 'post_id' => $hkey, '_wpnonce' => $nonce ], admin_url( 'edit.php' ) );
					echo "						  <tr>";
					echo "							  <td class=\"column-name\" scope=\"row\">{$hval['name']}</td>";
					echo "							  <td class=\"column-location\">{$locations[$hval['location']]}</td>";
					echo "							  <td class=\"column-location\">{$due_date}<br>{$due_message}</td>";
					echo "							  <td class=\"column-wait_date\">{$hval['item']['item_name']}</td>";
					echo "							  <td class=\"column-check-in\">";
					echo "								<a href=\"{$check_in_link}\">".__( 'Check In', 'lendingq' )."</a>
							</td>";
					echo "						  </tr>";
				}
				?>
			</tbody>
		</table>	
		<?php
			}
		}
		$last_key = key( array_slice( $item_types, -1, 1, true) );
		if( $type_key !== $last_key ) { 
			echo "<hr class=\"lending_hr\">";
		}
	}	 
	?>
</div>