<div class="wrap">
	<h1><?php _e( 'LendingQ Check In/Out', 'lending1' ); ?></h1>
	<p><?php _e( 'You do not have any Item Types set up.', 'lending1' ); ?></p>	   
	<p><?php _e( 'Please set them up by clicking on <em>Item Types</em> in the <em>LendingQ Stock</em> menu or using the following link:', 'lending1' ); ?></p>
	<br>
	<a href="<?php
	echo add_query_arg( [ 'taxonomy' => 'lendingq_item_type', 'post_type' => 'lendingq_stock' ], admin_url( 'edit-tags.php' ) );
			 ?>"><?php _e( 'Item Type Management', 'lending1' ); ?></a>
</div>