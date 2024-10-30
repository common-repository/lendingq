<div class="wrap">
	<h1><?php _e( 'LendingQ Check In/Out', 'lending1' ); ?></h1>
	<p><?php _e( 'You do not have any stock to lend.', 'lending1' ); ?></p>	   
	<p><?php _e( 'Please add items in the <em>LendingQ Stock</em> menu or using the following link:', 'lending1' ); ?></p>
	<br>
	<a href="<?php
	echo add_query_arg( [ 'post_type' => 'lendingq_stock' ], admin_url( 'post-new.php' ) );
			 ?>"><?php _e( 'Add New Item', 'lending1' ); ?></a>
</div>