<div class="wrap">
	<h1><?php _e( 'LendingQ Check In/Out', 'lending1' ); ?></h1>
	<p><?php _e( 'You do not have any Locations set up.', 'lending1' ); ?></p>	  
	<p><?php _e( 'Please set them up by clicking on <em>Locations</em> in the <em>LendingQ Stock</em> menu or using the following link:', 'lending1' ); ?></p>
	<br>
	<a href="<?php
	echo add_query_arg( [ 'taxonomy' => 'lendingq_location', 'post_type' => 'lendingq_stock' ], admin_url( 'edit-tags.php' ) );
			 ?>"><?php _e( 'Location Management', 'lending1' ); ?></a>
</div>