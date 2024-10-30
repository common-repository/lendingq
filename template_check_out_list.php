<?php
$stock = [];
$avail_count = 0;
$checked_count = 0;
$overdue = get_option( 'lendingq_field_contact_overdue' );
$temp = [];
$stock = [ 'all' => [], 'available' => [], 'checked_out' => [] ];
if( !empty( $post_list ) ) {
	foreach( $post_list as $key => $post ) {
		$temp['name']		= $post->post_title;
		$temp['status']		= $post->post_status;
		$location_raw		= get_the_terms( $post->ID, 'lendingq_location' );
		$temp['location']	= $location_raw[0]->slug;
		$item_type_raw		= get_the_terms( $post->ID, 'lendingq_item_type' );
		$temp['item_type']	= $item_type_raw[0]->slug;
		$stock['all'][$post->ID] = $temp;
		if ( $post->post_status === 'item_available' ) {
			$avail_count++;
			$stock['available'][$item_type_raw[0]->slug][$location_raw[0]->slug][$post->ID] = $temp;
		}
		if ( $post->post_status === 'checked_out' ) {
			$checked_count++;
			$stock['checked_out'][$item_type_raw[0]->slug][$location_raw[0]->slug][$post->ID] = $temp;
		}
	}
}



?>
<div class="wrap">
	<h1><?php _e( 'LendingQ Check Out', 'lendingq' ); ?></h1>
	<?php
	$contact_message = _n_noop( 'Contacted %s day ago', 'Contacted %s days ago', 'lendingq' );
	# Get an array of all available items, sorted by
	foreach( $item_types as $type_key => $type_name ) {
		?>
	<h2><?php printf( __( '%s Availability', 'lendingq' ), $type_name ); ?></h2>
	<?php
		#if there are none available
		if( $avail_count == 0 ) {
			echo '<strong style="font-size:1.2em">'.__( 'There are currently none available', 'lendingq' ) . '</strong>';
		} else {
			# go through each location and see if there are any free
			$holds = [];
			#echo '<article style="-webkit-column-width: 250px; -moz-column-width: 250px; column-width: 250px;">';
			echo "<div style=\"display:flex; flex-flow: row wrap;\">";
			
			foreach( $locations as $lkey => $loc ) {
				if( !empty( $filter) ) {
					// If first, show All
					if( $lkey == key( array_slice($locations, 0, 1, true ) ) ) {
						$clear_url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'clear_filter' => 'true' ], admin_url( 'edit.php' ) );
						echo '<div style="flex-basis: 250px; padding:10px;"><strong>'.__( 'Showing ', 'lendingq' ) .' ' . $locations[$filter] . '</strong><br><a href="'.$clear_url.'">'.__( 'Show All', 'lendingq' ).'</a></div>';
					}
					if( $lkey !== $filter ) {
						continue;
					}
					
					if( !empty( $hold_list['waiting_list'][$type_key][$lkey] ) ) {
						if( !empty( $stock['available'][$type_key][$lkey] ) ) {
							
							$holds = array_slice( $hold_list['dates'], 0, count( $stock['available'][$type_key][$lkey] ), true );
						}
					}
				} else {
					if( !empty( $hold_list['waiting_list'][$type_key][$lkey] ) ) {
						if( !empty( $stock['available'][$type_key][$lkey] ) ) {
							$holds = array_slice( $hold_list['dates'], 0, count( $stock['available'][$type_key][$lkey] ), true );
						}
					}
				}
				if( empty( $stock['available'][$type_key][$lkey] ) ) {
					printf( __( '<div style="flex-basis: 250px; padding:10px;"><strong>%s</strong>: None Available</div>', 'lendingq' ), $loc ) ;
				} else {
					$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'set_filter' => $lkey ], admin_url( 'edit.php' ) );
					# get available count at this location
					$avail = count( $stock['available'][$type_key][$lkey] );
					echo '<div style="flex-basis: 250px; padding:10px;">';
					printf( __( '<strong>%s</strong>: %s Available', 'lendingq' ), $loc, $avail );
					if( !$filter ) {
						echo '<br><a href="'. $url .'">';
						printf( __( 'Filter by %s.' ), $loc );
						echo '</a>';
					}
					echo '</div>';
				}
			}
			echo "</div>";
			#echo '</article>';
			# if there are some available and people waiting, show
			# those users for the check out list
			if( count( $holds ) == 0 ) {
				echo '<h4>' . __( 'There are currently no holds waiting to be checked out.', 'lendingq' ) . '<h4>';
			} else {
	?>
	<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th id="columnname" class="manage-column column-name" scope="col">Name</th>
				<th id="columnname" class="manage-column column-location" scope="col">Location</th>
				<th id="columnname" class="manage-column column-wait_date" scope="col">Added to Wait</th>
				<th id="columnname" class="manage-column column-check-out" scope="col">Check Out</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th class="manage-column column-name" scope="col">Name</th>
				<th class="manage-column column-location" scope="col">Location</th>					   
				<th class="manage-column column-wait_date" scope="col">Added to Wait</th>
				<th class="manage-column column-check-out" scope="col">Check Out</th>
			</tr>
			</tfoot>
			<tbody class="lending_table_col">
		<?php
				$count = 0;
				foreach( $locations as $lkey => $loc ) {
					if( !empty( $filter) ) {
						if( $lkey !== $filter ) {
							continue;
						}
					}
					$cur_holds		= ( !empty( $hold_list['waiting_list'][$type_key][$lkey] ) ) ? $hold_list['waiting_list'][$type_key][$lkey] : [];
					$cur_stock		= ( !empty( $stock['available'][$type_key][$lkey] ) ) ?$stock['available'][$type_key][$lkey] : [];
					# make a list of hold IDS to sort by waiting date
					$hold_ids		= array_column( $cur_holds, 'waiting_date', 'post_id' );
					
					asort( $hold_ids );
					$on_deck_ids	= array_slice( $hold_ids, count( $cur_stock ), null, TRUE );
					$hold_ids		= array_slice( $hold_ids, 0, count( $cur_stock ), TRUE );
					foreach( $hold_ids as $key => $val ) {
						$cur_hold = $hold_list['all'][$key];
						switch( $cur_hold['contact'] ) {
							case 'phone':
								$contact = $cur_hold['phone'];
								break;
							case 'email':
								$contact = "<a href=\"mailto:{$cur_hold['email']}\">{$cur_hold['email']}</a>";
								break;
							default:
								$contact = __( 'No contact information.', 'lendingq' );
								break;
						}
						$nonce = wp_create_nonce( 'lendingq_checkout' );
						# contacted?
						if( empty( $cur_hold['contact_date'] ) ) {
							$contact_url = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_contact_hold', 'post_id' => $key, '_wpnonce' => $nonce ], admin_url( 'edit.php' ) );
							$contact_link_text = __( 'Contact', 'lendingq' );
							$contact_link = "<a href=\"{$contact_url}\">{$contact_link_text}</a>";
						} else {
							$days = intval( ( time() - $cur_hold['contact_date'] ) / 60 / 60 / 24 );
							if( $days == 0 ) {
								$contact_link = __( 'Contacted Today', 'lendingq' );
							} else {
								$contact_link = sprintf( translate_nooped_plural( $contact_message, $days, 'lendingq' ), number_format_i18n( $days ) );
								if( !empty( $overdue) and $days > $overdue ) {
									$contact_link = "<span style=\"font-weight:bold; color:red\">{$contact_link}</span>";
								}
							}
						}
						$check_out_link = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_check_out', 'post_id' => $key, '_wpnonce' => $nonce ], admin_url( 'edit.php' ) );
						$cancel_link = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_cancel_hold', 'post_id' => $key, '_wpnonce' => $nonce ], admin_url( 'edit.php' ) );
						echo "						  <tr>";
						echo "							  <td class=\"column-name\" scope=\"row\">{$cur_hold['name']}<br>{$contact}</td>";
						echo "							  <td class=\"column-location\">{$locations[$cur_hold['location']]}</td>";
						echo "							  <td class=\"column-wait_date\">{$cur_hold['w_date_nice']}</td>";
						echo "							  <td class=\"column-check-out\">";
						echo "								   {$contact_link}<br>
									<a href=\"{$check_out_link}\">".__( 'Check Out', 'lendingq' )."</a><br>
									<a href=\"{$cancel_link}\">".__( 'Cancel Hold', 'lendingq' )."</a>
								</td>";
						echo "						  </tr>";
					}
					// Check for on deck holds
					if( count( $on_deck_ids ) > 0 ) {
						// use $on_deck_ids to create an on deck list.
					}
				}
		?>
			</tbody>
		</table>
	<?php
			}
			$last_key = key( array_slice( $item_types, -1, 1, true) );
			if( $type_key !== $last_key ) { 
				echo "<hr class=\"lending_hr\">";
			}
		}
	}
	?>