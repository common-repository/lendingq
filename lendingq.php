<?php
/*
Plugin Name: LendingQ
Plugin URI: https://wordpress.org/plugins/lendingq/
Description: A simple system to manage the lending of items (like hotspots) with an integrated waiting list.
Version: 1.0
License: GPLv2 or later
Text Domain: lendingq
*/
define( 'LENDINGQ_PATH', plugin_dir_path( __FILE__ ) );		   
if( !class_exists( "lendingq" ) ) {
	class lendingq {
		var $metabox		= []; 
		var $post_message;
		var $old_date;
		function __construct() {
			$timezone = (get_option( 'timezone_string' ) ) ? get_option( 'timezone_string' ) : date_default_timezone_get();
			date_default_timezone_set( $timezone );
			
			// define error variables for bitwise
			define( "LENDINGQ_CARD_INVALID",			1 );
			define( "LENDINGQ_CARD_NONE",				2 );
			define( "LENDINGQ_CONTACT_NONE",			3 );
			define( "LENDINGQ_DATE_INVALID",			4 );
			define( "LENDINGQ_DATE_NONE",				5 );
			define( "LENDINGQ_EMAIL_INVALID",			6 );
			define( "LENDINGQ_EMAIL_NONE",				7 );
			define( "LENDINGQ_HOLD_STAFF_NONE",			8 );
			define( "LENDINGQ_ITEM_LENGTH_INVALID",		9 );
			define( "LENDINGQ_ITEM_LENGTH_NONE",		10 );
			define( "LENDINGQ_ITEM_MANUF_NONE",			11 );
			define( "LENDINGQ_ITEM_MODEL_NONE",			12 );
			define( "LENDINGQ_ITEM_NAME_DUPE",			13 );
			define( "LENDINGQ_ITEM_NAME_NONE",			14 );
			define( "LENDINGQ_ITEM_SERIAL_NONE",		15 );
			define( "LENDINGQ_ITEM_TYPE_NONE",			16 );
			define( "LENDINGQ_LOCATION_NONE",			17 );
			define( "LENDINGQ_NAME_NONE",				18 );
			define( "LENDINGQ_PHONE_INVALID",			19 );
			define( "LENDINGQ_PHONE_NONE",				20 );
			define( "LENDINGQ_RETURN_STATUS_INVALID",	21 );
			define( "LENDINGQ_RETURN_STATUS_NOTES",		22 );
			define( "LENDINGQ_STAFF_NONE",				23 );
			define( "LENDINGQ_TIME_INVALID",			24 );
			define( "LENDINGQ_TIME_NONE",				25 );
			define( "LENDINGQ_UNAVAIL_STATUS_INVALID",	26 );
			define( "LENDINGQ_VERIFIED_NONE",			27 );
			
			define( "LENDINGQ_CHECKED_OUT_DATE_INVALID",	28 );
			define( "LENDINGQ_CHECKED_OUT_DATE_NONE",		29 );
			
			define( "LENDINGQ_NOTHING_AT_BRANCH",		30 );
			

			
			
			/* ADMIN SETUP */
			/* -------------------------------------------------- */
			// Activation, deactivation and uninstall hooks.
			register_activation_hook(		__FILE__,	'lendingq::lendingq_activate' );
			register_deactivation_hook(		__FILE__,	'lendingq::lendingq_deactivate' );
			register_uninstall_hook(		__FILE__,	'lendingq::lendingq_uninstall' );
			// Fix the columns on the display page
			add_action( "manage_lendingq_hold_posts_columns",			[ $this, 'setup_hold_post_columns' ] );
			add_action( "manage_lendingq_hold_posts_custom_column",		[ $this, 'setup_hold_post_column_values' ], 10, 2 );
			add_filter( 'manage_edit-lendingq_hold_sortable_columns',	[ $this, 'register_hold_sortable_columns' ] );
			
			add_action( "manage_lendingq_stock_posts_columns",			[ $this, 'setup_stock_post_columns' ] );
			add_action( "manage_lendingq_stock_posts_custom_column",	[ $this, 'setup_stock_post_column_values' ], 10, 2 );
			add_filter( 'manage_edit-lendingq_stock_sortable_columns',	[ $this, 'register_hold_sortable_columns_stock' ] );
			// get rid of quick edit
			add_filter( 'post_row_actions', 				[ $this, 'disable_quick_edit' ], 10, 2 );
			// Set up custom settings. 
			add_action( 'admin_init',						[ $this, 'custom_settings' ] );
			// Setup custom post, status and taxonomy types
			add_action( 'init',								[ $this, 'post_setup_custom' ] );
			add_action( 'pre_get_posts',					[ $this, 'waiting_list_orderby' ] );
			
			// If there are any Items that are checked out and missing 
			// Lending Post ID (trashed) then make them available
			add_action( 'init',								[ $this, 'check_item_statuses' ] );
			// Admin Pages and Dashboard Widget setup
			add_action( 'admin_menu',						[ $this, 'admin_menu_add_pages' ] );
			#add_action( 'wp_dashboard_setup',				[ $this, 'widget_dashboard_add' ] );
			// Enqueue CSS and Javascript
			add_action( 'wp_enqueue_scripts',				[ $this, 'lendingq_script_enqueuer' ] );
			add_action( 'admin_enqueue_scripts',			[ $this, 'lendingq_script_enqueuer' ] );
			// Add the Metabox for our custom form
			add_action( 'add_meta_boxes',					[ $this, 'meta_box_add' ] );
			// Add our own messages to the Post Update array
			add_filter( 'post_updated_messages',			[ $this, 'setup_post_updated_messages' ] );
			// Add our filters to the main list so they can filter by type and location
			add_action( 'restrict_manage_posts',			[ $this, 'add_taxonomy_filters' ] );
			// Remove the EDIT option from the bulk action dropdown.
			add_filter( 'bulk_actions-edit-lendingq_stock', [ $this, 'return_bulk_array' ] );
			add_filter( 'bulk_actions-edit-lendingq_hold',	[ $this, 'return_bulk_array' ] );	
			add_action( 'pre_post_update',					[ $this, 'handle_post_date' ], 10, 2 );
			// Check the updated/new post for errors and set status.
			add_filter( 'save_post_lendingq_hold',			[ $this, 'handle_post_check_hold' ], 10, 2 );
			add_filter( 'save_post_lendingq_stock',			[ $this, 'handle_post_check_stock' ], 10, 2 );
			// check out form catch
			add_action( 'admin_post_lendingq_checkout',		[ $this, 'handle_checkout' ] );
			add_action( 'admin_post_lendingq_checkin',		[ $this, 'handle_check_in' ] );
			add_action( 'admin_post_lendingq_contacted',	[ $this, 'handle_contacted' ] );
			add_action( 'admin_post_lendingq_cancel_hold',	[ $this, 'handle_cancel_hold' ] );
			add_action( 'save_post_lendingq_hold',			[ $this, 'check_item_statuses' ] );
			add_filter( 'custom_menu_order',				[ $this, 'change_hold_submenu_order' ] );
			add_action( 'plugins_loaded',					[ $this, 'lendingq_load_plugin_textdomain'] );
		} // END function __construct()
		function return_bulk_array( $bulk_actions ) {
			return [ 'delete' => 'Delete' ];
		}
		function bulk_stock( $redirect_to, $doaction, $post_ids ) {
			if ( $doaction !== 'delete' ) {
				return $redirect_to;
			}
		}
		// 		
		static function lendingq_activate() {
			add_option( "lendingq_field_card",				__( 'Library Card Number', 'lendingq' ) );
			add_option( "lendingq_field_card_desc",			__( 'The card number of the patron making the request.', 'lendingq' ) );
			add_option( "lendingq_field_card_regex",		__( 'A regex to match the card against.', 'lendingq' ) );
			add_option( "lendingq_field_contact",			__( 'Preferred Contact Type', 'lendingq' ) );
			add_option( "lendingq_field_contact_desc",		__( 'Which method would they prefer to be contacted by?', 'lendingq' ) );
			add_option( "lendingq_field_contact_overdue",	__( 3, 'lendingq' ) );
			add_option( "lendingq_field_email",				__( 'Email Address', 'lendingq' ) );
			add_option( "lendingq_field_email_Desc",		__( 'The full email address of the patron making the request.', 'lendingq' ) );
			add_option( "lendingq_field_header_hold",		__( 'Please use this form to place a customer hold for an item.<br>Hold requests are processed in the order received and we cannot guarantee availability dates.', 'lendingq' ) );
			add_option( "lendingq_field_header_item",		__( 'Please use this form to add new items to your stock.', 'lendingq' ) );
			add_option( "lendingq_field_item",				__( 'Item Type', 'lendingq' ) );
			add_option( "lendingq_field_item_desc",			__( 'The type of item requested.', 'lendingq' ) );
			add_option( "lendingq_field_item_manuf",		__( 'Manufacturer', 'lendingq' ) );
			add_option( "lendingq_field_item_manuf_desc",	__( 'Please enter the manufacturer of the item.', 'lendingq' ) );
			add_option( "lendingq_field_item_model",		__( 'Model', 'lendingq' ) );
			add_option( "lendingq_field_item_model_desc",	__( 'Please enter a model number or part description.', 'lendingq' ) );
			add_option( "lendingq_field_item_name",			__( 'Item Name', 'lendingq' ) );
			add_option( "lendingq_field_item_name_desc",	__( 'This name should be unique!', 'lendingq' ) );
			add_option( "lendingq_field_item_notes",		__( 'Notes', 'lendingq' ) );
			add_option( "lendingq_field_item_notes_desc",	__( 'Any information you feel should be added..', 'lendingq' ) );
			add_option( "lendingq_field_item_serial",		__( 'Serial Number', 'lendingq' ) );
			add_option( "lendingq_field_item_serial_desc",	__( 'Enter the serial or an identifying SKU or number.', 'lendingq' ) );
			add_option( "lendingq_field_name",				__( 'Patron Name', 'lendingq' ) );
			add_option( "lendingq_field_name_desc",			__( 'The full name of the patron making the request.', 'lendingq' ) );
			add_option( "lendingq_field_notes",				__( 'Notes', 'lendingq' ) );
			add_option( "lendingq_field_notes_desc",		__( 'Any information you feel should be added.', 'lendingq' ) );
			add_option( "lendingq_field_phone",				__( 'Phone Number', 'lendingq' ) );
			add_option( "lendingq_field_phone_desc",		__( 'The phone number of the patron, including area code. Please use the format 234-555-1234.', 'lendingq' ) );
			add_option( "lendingq_field_phone_regex",		'/^[2-9]\d{2}-\d{3}-\d{4}$/' );
			add_option( "lendingq_field_pickup",			__( 'Preferred Pickup Location', 'lendingq' ) );
			add_option( "lendingq_field_pickup_desc",		__( 'At which branch would you like to place the reservation?', 'lendingq' ) );
			add_option( "lendingq_field_staff",				__( 'Staff who assisted', 'lendingq' ) );
			add_option( "lendingq_field_staff_desc",		__( 'Please enter your name.', 'lendingq' ) );
			add_option( "lendingq_field_verified",			__( 'Have you verified the card and account?', 'lendingq' ) );
			add_option( "lendingq_field_verified_desc", 	__( 'Only check this if the user has been verified and no blocks are on their account.', 'lendingq' ) );
			$role = get_role( 'administrator' );
			$role->add_cap( 'lendingq_view',	true );
			$role->add_cap( 'lendingq_manage',	true );
			$role = get_role( 'editor' );
			$role->add_cap( 'lendingq_view',	true );
			$role = get_role( 'author' );
			$role->add_cap( 'lendingq_view',	true );
			$role = get_role( 'contributor' );
			$role->add_cap( 'lendingq_view',	true );
			$role = get_role( 'subscriber' );
			$role->add_cap( 'lendingq_view',	true );
		} // END function lendingq_activate()
		static function lendingq_deactivate() {
			delete_option( "lendingq_field_card" );
			delete_option( "lendingq_field_card_desc" );
			delete_option( "lendingq_field_card_regex" );
			delete_option( "lendingq_field_contact" );
			delete_option( "lendingq_field_contact_desc" );
			delete_option( "lendingq_field_contact_overdue" );
			delete_option( "lendingq_field_email" );
			delete_option( "lendingq_field_email_desc" );
			delete_option( "lendingq_field_header_hold" );
			delete_option( "lendingq_field_header_item" );				 
			delete_option( "lendingq_field_item" );
			delete_option( "lendingq_field_item_desc" );
			delete_option( "lendingq_field_item_manuf" );
			delete_option( "lendingq_field_item_manuf_desc" );
			delete_option( "lendingq_field_item_model" );
			delete_option( "lendingq_field_item_model_desc" );
			delete_option( "lendingq_field_item_name" );
			delete_option( "lendingq_field_item_name_desc" );
			delete_option( "lendingq_field_item_notes" );
			delete_option( "lendingq_field_item_notes_desc" );
			delete_option( "lendingq_field_item_serial" );
			delete_option( "lendingq_field_item_serial_desc" );
			delete_option( "lendingq_field_name" );
			delete_option( "lendingq_field_name_desc" );
			delete_option( "lendingq_field_notes" );
			delete_option( "lendingq_field_notes_desc" );
			delete_option( "lendingq_field_phone" );
			delete_option( "lendingq_field_phone_desc" );
			delete_option( "lendingq_field_phone_regex" );
			delete_option( "lendingq_field_pickup" );
			delete_option( "lendingq_field_pickup_desc" );
			delete_option( "lendingq_field_staff" );
			delete_option( "lendingq_field_staff_desc" );
			delete_option( "lendingq_field_verified" );
			delete_option( "lendingq_field_verified_desc" );
		} // END function lendingq_deactivate()
		static function lendingq_uninstall() {
			$role = get_role(	'administrator' );
			$role->remove_cap(	'lendingq_view',	true );
			$role->remove_cap(	'lendingq_manage',	true );
			$role = get_role(	'editor' );
			$role->remove_cap(	'lendingq_view',	true );
			$role->remove_cap(	'lendingq_manage',	false );
			$role = get_role(	'author' );
			$role->remove_cap(	'lendingq_view',	true );
			$role->remove_cap(	'lendingq_manage',	false );
			$role = get_role(	'contributor' );
			$role->remove_cap(	'lendingq_view',	true );
			$role->remove_cap(	'lendingq_manage',	false );
			$role = get_role(	'subscriber' );
			$role->remove_cap(	'lendingq_view',	true );
			$role->remove_cap(	'lendingq_manage',	false );
			delete_option( "lendingq_field_card" );
			delete_option( "lendingq_field_card_desc" );
			delete_option( "lendingq_field_card_regex" );
			delete_option( "lendingq_field_contact" );
			delete_option( "lendingq_field_contact_desc" );
			delete_option( "lendingq_field_contact_overdue" );
			delete_option( "lendingq_field_email" );
			delete_option( "lendingq_field_email_desc" );
			delete_option( "lendingq_field_header_hold" );
			delete_option( "lendingq_field_header_item" );
			delete_option( "lendingq_field_item" );
			delete_option( "lendingq_field_item_desc" );
			delete_option( "lendingq_field_item_manuf" );
			delete_option( "lendingq_field_item_manuf_desc" );
			delete_option( "lendingq_field_item_model" );
			delete_option( "lendingq_field_item_model_desc" );
			delete_option( "lendingq_field_item_name" );
			delete_option( "lendingq_field_item_name_desc" );
			delete_option( "lendingq_field_item_notes" );
			delete_option( "lendingq_field_item_notes_desc" );
			delete_option( "lendingq_field_item_serial" );
			delete_option( "lendingq_field_item_serial_desc" );
			delete_option( "lendingq_field_name" );
			delete_option( "lendingq_field_name_desc" );
			delete_option( "lendingq_field_notes" );
			delete_option( "lendingq_field_notes_desc" );
			delete_option( "lendingq_field_phone" );
			delete_option( "lendingq_field_phone_desc" );
			delete_option( "lendingq_field_phone_regex" );
			delete_option( "lendingq_field_pickup" );
			delete_option( "lendingq_field_pickup_desc" );
			delete_option( "lendingq_field_staff" );
			delete_option( "lendingq_field_staff_desc" );
			delete_option( "lendingq_field_verified" );
			delete_option( "lendingq_field_verified_desc" );
		} // END function lendingq_uninstall()
		function lendingq_script_enqueuer() {
			// We need the validate jquery for HTML5 form validation
			// plus out custom JS, CSS and Widget CSS
			wp_enqueue_script( 'lendingq_validate', plugin_dir_url( __FILE__ ) . 'includes/jquery.validate.min.js' );
			wp_enqueue_script( 'lendingq-js', plugin_dir_url( __FILE__ ) . 'lendingq.js' );
			wp_enqueue_script( 'lendingq-js', plugin_dir_url( __FILE__ ) . 'lendingq.js', [ 'jquery' ], NULL, false );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			
			wp_enqueue_style( 'jquery-ui-datepicker-style', plugin_dir_url( __FILE__ ) . 'includes/jquery-ui.css');
			
			wp_enqueue_style( 'lendingq-style', plugin_dir_url( __FILE__ ) . 'lendingq.css' );
			wp_enqueue_style( 'lendingq-style-widget', plugin_dir_url( __FILE__ ) . 'widget.css' );
			// languages
			load_plugin_textdomain( 'lendingq', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
		} // END function lendingq_script_enqueuer()
		// Functions 
		function add_taxonomy_filters() {
			global $typenow;  
			// an array of all the taxonomies you want to display. Use the taxonomy name or slug - each item gets its own select box.  
			$taxonomies = [ 'lendingq_item_type', 'lendingq_location' ];
			// use the custom post type here  
			if( $typenow == 'lendingq_hold' or $typenow == 'lendingq_stock' ) {
				foreach( $taxonomies as $slug) {
					$lendingq_tax	= get_taxonomy( $slug ); 
					$tax_name		= $lendingq_tax->labels->name;	
					$terms			= get_terms( $slug, [ 'hide_empty' => false, 'parent' => 0 ] ); 
					if( count($terms) > 0 ) {
						echo "<select name=\"{$slug}\" id=\"filter-by-{$slug}\" class=\"postform\">";
						echo '<option value="">'. sprintf( __( 'Show All %s', 'lendingq' ), $tax_name ) .'</option>';
						foreach ($terms as $term) { 
							$selected = ( $_GET[$slug] == $term->slug ) ? " selected=\"selected\"" : null;
							#echo '<option value="'. $term->slug .'"'. $selected . '">' . $term->name . '</option>';								
							echo '<option value="'. $term->slug .'"'. $selected . '>' . $term->name . '</option>';
						}  
						echo "</select>";  
					} // END if(count($terms) > 0)
				} // END foreach ($taxonomies as $slug)
			} // END if( $typenow == 'lendingq_hold' )
		} // END function add_taxonomy_filters()
		function admin_menu_add_pages() {
			add_submenu_page(	'edit.php?post_type=lendingq_hold', 
								 __( 'Check In', 'lendingq' ), 
								 __( 'Check In', 'lendingq' ), 
								 'lendingq_view', 
								 'lendingq_check_in_list', 
								 [ $this, 'page_disp_check_in_list' ]
							 );
			add_submenu_page(	'edit.php?post_type=lendingq_hold', 
								 __( 'Check Out', 'lendingq' ), 
								 __( 'Check Out', 'lendingq' ), 
								 'lendingq_view', 
								 'lendingq_check_out_list', 
								 [ $this, 'page_disp_check_out_list' ]
							 );
			add_submenu_page(	'edit.php?post_type=lendingq_stock', 
								 __( 'Settings', 'lendingq' ), 
								 __( 'Settings', 'lendingq' ), 
								 'lendingq_manage', 
								 'lendingq_settings', 
								 [ $this, 'page_disp_settings' ]
							 );
			add_submenu_page(	null, 
								 __( 'Check Out', 'lendingq' ), 
								 __( 'Check Out', 'lendingq' ), 
								 'lendingq_view', 
								 'lendingq_check_out', 
								 [ $this, 'lendingq_check_out' ]
							 );
			add_submenu_page(	null, 
								 __( 'Check In', 'lendingq' ), 
								 __( 'Check In', 'lendingq' ), 
								 'lendingq_view', 
								 'lendingq_check_in', 
								 [ $this, 'lendingq_check_in' ]
							 );
			add_submenu_page(	null, 
								 __( 'Contact', 'lendingq' ), 
								 __( 'Contact', 'lendingq' ), 
								 'lendingq_view', 
								 'lendingq_contact_hold', 
								 [ $this, 'lendingq_contact_hold' ]
							 );
			add_submenu_page(	null, 
								 __( 'Cancel Hold', 'lendingq' ), 
								 __( 'Cancel Hold', 'lendingq' ), 
								 'lendingq_view', 
								 'lendingq_cancel_hold', 
								 [ $this, 'lendingq_cancel_hold' ]
							 );
			add_submenu_page(	null, 
								 __( 'Delete Item', 'lendingq' ), 
								 __( 'Delete Item', 'lendingq' ), 
								 'lendingq_manage', 
								 'lendingq_cancel_stock', 
								 [ $this, 'lendingq_cancel_stock' ]
							 );
			global $submenu;
			unset( $submenu['edit.php?post_type=lendingq_hold'][15] );
			unset( $submenu['edit.php?post_type=lendingq_hold'][16] );
			if( !current_user_can( 'lendingq_manage' ) ) {
				remove_menu_page( 'edit.php?post_type=lendingq_stock' );
			}
		} // END function admin_menu_add_pages()
		function callback_form_card() {
			$val = get_option( 'lendingq_field_card' );
			echo '<input id="lendingq_field_card" class="regular-text" name="lendingq_field_card" type="text" value="'.$val.'">';
		} // END function callback_form_card()
		function callback_form_card_desc() {
			$val = get_option( 'lendingq_field_card_desc' );
			echo '<textarea rows="3" id="lendingq_field_card_desc" class="regular-text" name="lendingq_field_card_desc">'.$val.'</textarea>';
		} // END function callback_form_card_desc()
		function callback_form_card_regex() {
			$val = get_option( 'lendingq_field_card_regex' );
			echo '<input id="lendingq_field_card_regex" class="regular-text" name="lendingq_field_card_regex" type="text" value="'.$val.'">';
		} // END function callback_form_card_desc()
		function callback_form_contact() {
			$val = get_option( 'lendingq_field_contact' );
			echo '<input id="lendingq_field_contact" class="regular-text" name="lendingq_field_contact" type="text" value="'.$val.'">';
		} // END function callback_form_name()
		function callback_form_contact_desc() {
			$val = get_option( 'lendingq_field_contact_desc' );
			echo '<textarea rows="3" id="lendingq_field_contact_desc" class="regular-text" name="lendingq_field_contact_desc">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_contact_overdue() {
			$val = get_option( 'lendingq_field_contact_overdue' );
			echo '<input id="lendingq_field_contact_overdue" class="regular-text" name="lendingq_field_contact_overdue" type="number" value="'.$val.'">';
		} // END function callback_form_contact_overdue()
		function callback_form_email() {
			$val = get_option( 'lendingq_field_email' );
			echo '<input id="lendingq_field_email" class="regular-text" name="lendingq_field_email" type="text" value="'.$val.'">';
		} // END function callback_form_email()
		function callback_form_email_desc() {
			$val = get_option( 'lendingq_field_email_desc' );
			echo '<textarea rows="3" id="lendingq_field_email_desc" class="regular-text" name="lendingq_field_email_desc">'.$val.'</textarea>';
		} // END function callback_form_email_desc()
		function callback_form_header_hold() {
			$val = get_option( 'lendingq_field_header_hold' );
			echo '<div class="input-wrap lending_editor">';
			wp_editor( $val, 'lendingq_field_header_hold', [ 'teeny' => true, 'textarea_rows' => 10, 'media_buttons' => false, 'quicktags' => false ] );
			echo '</div>';
			//echo '<textarea id="field_header" class="regular-text" name="lendingq[field_header]">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_header_item() {
			$val = get_option( 'lendingq_field_header_item' );
			echo '<div class="input-wrap lending_editor">';
			wp_editor( $val, 'lendingq_field_header_item', [ 'teeny' => true, 'textarea_rows' => 10, 'media_buttons' => false, 'quicktags' => false ] );
			echo '</div>';
			//echo '<textarea id="field_header" class="regular-text" name="lendingq[field_header]">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_item() {
			$val = get_option( 'lendingq_field_item' );
			echo '<input id="lendingq_field_item" class="regular-text" name="lendingq_field_item" type="text" value="'.$val.'">';
		} // END function callback_form_item()
		function callback_form_item_desc() {
			$val = get_option( 'lendingq_field_item_desc' );
			echo '<textarea rows="3" id="lendingq_field_item_desc" class="regular-text" name="lendingq_field_item_desc">'.$val.'</textarea>';
		} // END function callback_form_item()
		function callback_form_item_manuf() {
			$val = get_option( 'lendingq_field_item_manuf' );
			echo '<input id="lendingq_field_item_manuf" class="regular-text" name="lendingq_field_item_manuf" type="text" value="'.$val.'">';
		} // END function callback_form_item_manuf()
		function callback_form_item_manuf_desc() {
			$val = get_option( 'lendingq_field_item_manuf_desc' );
			echo '<input id="lendingq_field_item_manuf_desc" class="regular-text" name="lendingq_field_item_manuf_desc" type="text" value="'.$val.'">';
		} // END function callback_form_item_manuf_desc()
		function callback_form_item_model() {
			$val = get_option( 'lendingq_field_item_model' );
			echo '<input id="lendingq_field_item_model" class="regular-text" name="lendingq_field_item_model" type="text" value="'.$val.'">';
		} // END function callback_form_item_model()
		function callback_form_item_model_desc() {
			$val = get_option( 'lendingq_field_item_model_desc' );
			echo '<input id="lendingq_field_item_model_desc" class="regular-text" name="lendingq_field_item_model_desc" type="text" value="'.$val.'">';
		} // END function callback_form_item_model_desc()
		function callback_form_item_name() {
			$val = get_option( 'lendingq_field_item_name' );
			echo '<input id="lendingq_field_item_name" class="regular-text" name="lendingq_field_item_name" type="text" value="'.$val.'">';
		} // END function callback_form_item_name()
		function callback_form_item_name_desc() {
			$val = get_option( 'lendingq_field_item_name_desc' );
			echo '<input id="lendingq_field_item_name_desc" class="regular-text" name="lendingq_field_item_name_desc" type="text" value="'.$val.'">';
		} // END function callback_form_item_name_desc()
		function callback_form_item_notes() {
			$val = get_option( 'lendingq_field_item_notes' );
			echo '<input id="lendingq_field_item_notes" class="regular-text" name="lendingq_field_item_notes" type="text" value="'.$val.'">';
		} // END function callback_form_item_notes()
		function callback_form_item_notes_desc() {
			$val = get_option( 'lendingq_field_item_notes_desc' );
			echo '<input id="lendingq_field_item_notes_desc" class="regular-text" name="lendingq_field_item_notes_desc" type="text" value="'.$val.'">';
		} // END function callback_form_item_notes_desc()
		function callback_form_item_serial() {
			$val = get_option( 'lendingq_field_item_serial' );
			echo '<input id="lendingq_field_item_serial" class="regular-text" name="lendingq_field_item_serial" type="text" value="'.$val.'">';
		} // END function callback_form_item_serial()
		function callback_form_item_serial_desc() {
			$val = get_option( 'lendingq_field_item_serial_desc' );
			echo '<input id="lendingq_field_item_serial_desc" class="regular-text" name="lendingq_field_item_serial_desc" type="text" value="'.$val.'">';
		} // END function callback_form_item_serial_desc()
		function callback_form_name() {
			$val = get_option( 'lendingq_field_name' );
			echo '<input id="lendingq_field_name" class="regular-text" name="lendingq_field_name" type="text" value="'.$val.'">';
		} // END function callback_form_name()
		function callback_form_name_desc() {
			$val = get_option( 'lendingq_field_name_desc' );
			echo '<textarea rows="3" id="lendingq_field_name_desc" class="regular-text" name="lendingq_field_name_desc">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_notes() {
			$val = get_option( 'lendingq_field_notes' );
			echo '<input id="lendingq_field_notes" class="regular-text" name="lendingq_field_notes" type="text" value="'.$val.'">';
		} // END function callback_form_name()
		function callback_form_notes_desc() {
			$val = get_option( 'lendingq_field_notes_desc' );
			echo '<textarea rows="3" id="lendingq_field_notes_desc" class="regular-text" name="lendingq_field_notes_desc">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_phone() {
			$val = get_option( 'lendingq_field_phone' );
			echo '<input id="lendingq_field_phone" class="regular-text" name="lendingq_field_phone" type="text" value="'.$val.'">';
		} // END function callback_form_phone()
		function callback_form_phone_desc() {
			$val = get_option( 'lendingq_field_phone_desc' );
			echo '<textarea rows="3" id="lendingq_field_phone_desc" class="regular-text" name="lendingq_field_phone_desc">'.$val.'</textarea>';
		} // END function callback_form_phone_desc()
		function callback_form_phone_regex() {
			$val = get_option( 'lendingq_field_phone_regex' );
			echo '<input id="lendingq_field_phone_regex" class="regular-text" name="lendingq_field_phone_regex" type="text" value="'.$val.'">';
		} // END function callback_form_phone_regex()
		function callback_form_pickup() {
			$val = get_option( 'lendingq_field_pickup' );
			echo '<input id="lendingq_field_pickup" class="regular-text" name="lendingq_field_pickup" type="text" value="'.$val.'">';
		} // END function callback_form_name()
		function callback_form_pickup_desc() {
			$val = get_option( 'lendingq_field_pickup_desc' );
			echo '<textarea rows="3" id="lendingq_field_pickup_desc" class="regular-text" name="lendingq_field_pickup_desc">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_staff() {
			$val = get_option( 'lendingq_field_staff' );
			echo '<input id="lendingq_field_staff" class="regular-text" name="lendingq_field_staff" type="text" value="'.$val.'">';
		} // END function callback_form_name()
		function callback_form_staff_desc() {
			$val = get_option( 'lendingq_field_staff_desc' );
			echo '<textarea rows="3" id="lendingq_field_staff_desc" class="regular-text" name="lendingq_field_staff_desc">'.$val.'</textarea>';
		} // END function callback_form_name()
		function callback_form_verified() {
			$val = get_option( 'lendingq_field_verified' );
			echo '<input id="lendingq_field_verified" class="regular-text" name="lendingq_field_verified" type="text" value="'.$val.'">';
		} // END function callback_form_name()
		function callback_form_verified_desc() {
			$val = get_option( 'lendingq_field_verified_desc' );
			echo '<textarea rows="3" id="lendingq_field_verified_desc" class="regular-text" name="lendingq_field_verified_desc">'.$val.'</textarea>';
		} // END function callback_form_name()
		function change_hold_submenu_order( $menu_ord ) {
			global $submenu;
			$arr = [];
			$arr[] = $submenu['edit.php?post_type=lendingq_hold'][10];
			$arr[] = $submenu['edit.php?post_type=lendingq_hold'][17];
			$arr[] = $submenu['edit.php?post_type=lendingq_hold'][18];
			$arr[] = $submenu['edit.php?post_type=lendingq_hold'][5];
			$submenu['edit.php?post_type=lendingq_hold'] = $arr;
			return $menu_ord;
		}
		function check_in_out( $item_types, $locations ) {
			// check there are item types available
			if( count( $item_types ) == 0 ) {
				require( LENDINGQ_PATH . '/template_no_item_types.php' );
				return false;
			}
			// check there are locations available
			if( count( $locations ) == 0 ) {
				require( LENDINGQ_PATH . '/template_no_locations.php' );
				return false;
			}
			// check there is stock
			$args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_stock',
						'post_status'	 => 'any' ];
			$post_list = get_posts( $args );
			if( count( $post_list ) == 0 ) {
				require( LENDINGQ_PATH . '/template_no_stock.php' );
				return false;
			}
			return $post_list;
		}
		function check_item_statuses() {
			 $args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_hold',
						'post_status'	 => 'checked_out' ];
			$post_list = get_posts( $args );
			$lending_ids = [];
			foreach( $post_list as $key => $val ) {
				$meta = get_post_meta( $val->ID );
				
				
				if( isset( $meta['item_id'] ) and is_array( $meta['item_id'] ) and !empty( current( array_filter( $meta['item_id'] ) )
					) ) {
					$lending_ids[] = current( array_filter( $meta['item_id'] ) );
				}
			} // END foreach( $post_list as $key => $val )
			$args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_stock',
						'post_status'	 => 'checked_out' ];
			$post_list = get_posts( $args );
			foreach( $post_list as $key => $val ) {
				if( !in_array( $val->ID, $lending_ids ) ) {
					// Bad Item is checked out with no lending
					// Make it available.
					$val->post_status = "item_available";
					wp_update_post( $val );
				} // END if( !in_array( $val->ID, $lending_ids ) )
			}
			return true;
			// Fix Items that have been checked out but have no lending anymore
			// Get all items that are checked ou
			$args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_stock',
						'post_status'	 => 'checked_out' ];
			$stock_list = get_posts( $args );
			$hold_list = get_posts( $args );
			// check meta of each item to see if there is a valid lending
			foreach( $stock_list as $sval ) {
				// check that there is an ID, a checkout and a due date
				if( empty( $item_meta['item_id'] ) or 
						empty( $item_meta['checked_out_date'] ) or 
						empty( $item_meta['due_date'] ) ) {
					}
				$meta = get_post_meta( $sval->ID );
				// Does the hold exist?
				$error = false;
				if( !is_array( $meta['lending_id'] ) or empty( current( array_filter( $meta['lending_id'] ) ) ) ) {
					$error = true;
				} else {
					$item = get_post( current( array_filter( $meta['lending_id'] ) ) );
					if( empty( $item ) ) {
						$error = true;
					}
					// check for lending info
					$item_meta = get_post_meta( $item->ID );
					$hold_id = current( array_filter( $item_meta['item_id'] ) );
					$hold = get_post( $hold_id );
					$hold_meta = get_post_meta( $hold_id );
					if( empty( $hold_meta['item_id'] ) or empty( $hold_meta['checked_out_date'] ) or empty( $hold_meta['due_date'] ) ) {
						die( 'EMPTY' );
					} else {
						if( current( array_filter( $item_meta['item_id'] ) ) !== current( array_filter( $hold_meta['item_id'] ) ) ) {
							update_post_meta( $hold_id, 'item_id', current( array_filter( $item_meta['item_id'] ) ) );
							die('001');
							//$change = true;
						}
						if( $item_meta['checked_out_date'] !== $hold_meta['checked_out_date'] ) {
							update_post_meta( $hold_id, 'item_id', current( array_filter( $item_meta['checked_out_date'] ) ) );
							//$change = true;
							die('002');
						}
						if( $item_meta['due_date'] !== $hold_meta['due_date'] ) {
							update_post_meta( $hold_id, 'item_id', current( array_filter( $item_meta['due_date'] ) ) );
							//$change = true;
							die('003');
						}
						if( $hold->post_status !== 'checked_out' ) {
							$hold->post_status = 'checked_out';
							update_post( $hold );
							die('004');
						}
					}
				}
				if( $error ) {
					// make item available
					$sval->post_status = 'item_available';
					wp_update_post( $sval );		
					// clean the item 
					// ##############################
					update_post_meta( $sval->ID, 'item_id', null );
					update_post_meta( $sval->ID, 'checked_out_date', null );
					update_post_meta( $sval->ID, 'due_date', null );
				}
			}
			// Check lendings that have no items
			// get all checked out lendings
			 $args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_hold',
						'post_status'	 => 'checked_out' ];
			foreach( $hold_list as $hval ) {
				$meta = get_post_meta( $hval->ID );
				// if not, make the item available.
				$error = false;
				if( !isset( $meta['item_id'] ) or empty( current( array_filter( $meta['item_id'] ) ) ) ) {
					$error = true;
				} else {
					$lending = get_post( current( array_filter( $meta['item_id'] ) ) );
					if( empty( $lending ) ) {
						$error = true;
					}
				}
				if( $error ) {
					$hval->post_status = 'waiting_list';
					wp_update_post( $hval );		
					// clean the item 
					// ##############################
					update_post_meta( $hval->ID, 'item_id', null );
				}
			}
		} // END function check_item_statuses()
		function custom_settings() {
			remove_meta_box( 'submitdiv', 'lendingq_hold', 'side' );
			remove_meta_box( 'submitdiv', 'lendingq_stock', 'side' );
			register_setting(	'lendingq_seetings_fields',
								'lendingq', 
								[ $this, 'sanitize' ]
							);
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_card' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_card_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_card_regex' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_contact' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_contact_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_email' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_email_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_header_hold' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_header_item' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_name' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_name_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_notes' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_notes_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_phone' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_phone_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_phone_regex' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_pickup' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_pickup_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_staff' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_staff_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_verified' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_verified_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_name' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_name_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_notes' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_notes_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_serial' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_serial_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_manuf' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_manuf_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_model' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_item_model_desc' );
			register_setting( 'lendingq_seetings_fields', 'lendingq_field_contact_overdue' );
			/* -------------------------------------------------- */
			add_settings_section(	'lendginq_settings_section_optional',
									'Optional Settings', 
									[ $this, 'section_form_fields_optional' ], 
											'lendingq_settings' 
								); 
			add_settings_field(		'lendingq_field_contact_overdue', 
									__( 'Days after contact till overdue', 'lendingq' ), 
									[ $this, 'callback_form_contact_overdue' ], 
									'lendingq_settings', 
									'lendginq_settings_section_optional'
							  );
			/* -------------------------------------------------- */
			add_settings_section(	'lendginq_settings_section_fields',
									'Hold Settings', 
									[ $this, 'section_form_fields_hold' ],   
											'lendingq_settings' 
								); 
			add_settings_field(		'lendingq_field_header_hold', 
									__( 'Header Text', 'lendingq' ), 
									[ $this, 'callback_form_header_hold' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_item', 
									__( 'Item Type', 'lendingq' ), 
									[ $this, 'callback_form_item' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_item_desc', 
									__( 'Item Type Help Text', 'lendingq' ), 
									[ $this, 'callback_form_item_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_name', 
									__( 'Name', 'lendingq' ), 
									[ $this, 'callback_form_name' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_name_desc', 
									__( 'Name Help Text', 'lendingq' ), 
									[ $this, 'callback_form_name_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_card', 
									__( 'Identification Card', 'lendingq' ), 
									[ $this, 'callback_form_card' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_card_desc', 
									__( 'Identification Card Help Text', 'lendingq' ), 
									[ $this, 'callback_form_card_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_card_desc', 
									__( 'Identification Card Regex (Optional)', 'lendingq' ), 
									[ $this, 'callback_form_card_regex' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_verified', 
									__( 'Is Account Verified/Valid', 'lendingq' ), 
									[ $this, 'callback_form_verified' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_verified_desc', 
									__( 'Is Account Verified/Valid Help Text', 'lendingq' ), 
									[ $this, 'callback_form_verified_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_phone', 
									__( 'Phone Number', 'lendingq' ), 
									[ $this, 'callback_form_phone' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_phone_desc', 
									__( 'Phone Number Help Text', 'lendingq' ), 
									[ $this, 'callback_form_phone_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_phone', 
									__( 'Phone Number', 'lendingq' ), 
									[ $this, 'callback_form_phone' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_phone_regex', 
									__( 'Phone Number Regex', 'lendingq' ), 
									[ $this, 'callback_form_phone_regex' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_email', 
									__( 'Email Address', 'lendingq' ), 
									[ $this, 'callback_form_email' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_email_desc', 
									__( 'Email Address Help Text', 'lendingq' ), 
									[ $this, 'callback_form_email_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_contact', 
									__( 'Preferred Contact Method', 'lendingq' ), 
									[ $this, 'callback_form_contact' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_contact_desc', 
									__( 'Preferred Contact Method Help Text', 'lendingq' ), 
									[ $this, 'callback_form_contact_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_pickup', 
									__( 'Preferred Pickup Location', 'lendingq' ), 
									[ $this, 'callback_form_pickup' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_pickup_desc', 
									__( 'Preferred Pickup Location Help Text', 'lendingq' ), 
									[ $this, 'callback_form_pickup_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_staff', 
									__( 'Staff Who Assisted', 'lendingq' ), 
									[ $this, 'callback_form_staff' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_staff_desc', 
									__( 'Staff Who Assisted Help Text', 'lendingq' ), 
									[ $this, 'callback_form_staff_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_notes', 
									__( 'Notes', 'lendingq' ), 
									[ $this, 'callback_form_notes' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			add_settings_field(		'lendingq_field_notes_desc', 
									__( 'Notes Help Text', 'lendingq' ), 
									[ $this, 'callback_form_notes_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_fields'
							  );
			/* -------------------------------------------------- */
			add_settings_section(	'lendginq_settings_section_header_item',
									'Item Settings', 
									[ $this, 'section_form_fields_item' ], 
									'lendingq_settings' 
								); 
			add_settings_field(		'lendingq_field_header_item', 
									__( 'Header Text', 'lendingq' ), 
									[ $this, 'callback_form_header_item' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_name', 
									__( 'Item Name', 'lendingq' ), 
									[ $this, 'callback_form_item_name' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_name_desc', 
									__( 'Item Name Help Text', 'lendingq' ), 
									[ $this, 'callback_form_item_name_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_serial', 
									__( 'Serial Number', 'lendingq' ), 
									[ $this, 'callback_form_item_serial' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_serial_desc', 
									__( 'Serial Number Help Text', 'lendingq' ), 
									[ $this, 'callback_form_item_serial_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_manuf', 
									__( 'Manufacturer', 'lendingq' ), 
									[ $this, 'callback_form_item_manuf' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_manuf_desc', 
									__( 'Manufacturer Help Text', 'lendingq' ), 
									[ $this, 'callback_form_item_manuf_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_model', 
									__( 'Model or Description', 'lendingq' ), 
									[ $this, 'callback_form_item_model' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_model_desc', 
									__( 'Model or Description Help Text', 'lendingq' ), 
									[ $this, 'callback_form_item_model_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_Notes', 
									__( 'Notes', 'lendingq' ), 
									[ $this, 'callback_form_item_notes' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			add_settings_field(		'lendingq_field_item_notes_desc', 
									__( 'Notes Help Text', 'lendingq' ), 
									[ $this, 'callback_form_item_notes_desc' ], 
									'lendingq_settings', 
									'lendginq_settings_section_header_item'
							  );
			/* -------------------------------------------------- */
		} // END function custom_settings()
		function disable_quick_edit( $actions, $post ) {
			global $typenow; 
			if( $typenow == 'lendingq_hold' or $typenow == 'lendingq_stock' ) {
				unset( $actions[ 'inline hide-if-no-js' ] );
				unset( $actions[ 'view' ] );
				unset( $actions[ 'trash' ] );
			} // END if( $typenow !== 'lendingq_hold' )	  
			if( $typenow == 'lendingq_hold' ) {
				$actions['delete'] = '<a href="'. wp_nonce_url( add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_cancel_hold', 'post_id' => $post->ID	 ], admin_url( 'edit.php' ) ), 'lendingq_cancel_hold' ).'">Cancel Hold</a>';
			}
			if( $typenow == 'lendingq_stock' ) {
				$actions['delete'] = '<a href="'. wp_nonce_url( add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_cancel_stock', 'post_id' => $post->ID  ], admin_url( 'edit.php' ) ), 'lendingq_cancel_stock' ).'">Delete Item</a>';
			}
			return $actions;
		} // END function disable_quick_edit( $actions, $post )
		function get_holds() {
			// GET HOLDS
			// --------------------------------------------------
			$args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_hold',
						'post_status'	 => 'any' ];
			$hold_list_raw = get_posts( $args );
			
			$hold_list['dates'] = [];
			if( !empty( $hold_list_raw ) ) {
				foreach( $hold_list_raw as $key => $val ) {
					$temp = [];
					$meta = get_post_meta( $val->ID );

					$temp['post_id']		= $val->ID;
					$temp_it = get_the_terms( $val->ID, 'lendingq_item_type' );
					$temp_loc = get_the_terms( $val->ID, 'lendingq_location' );
					$temp['status']			= $val->post_status;
					$temp['title']			= $val->post_title;
					$temp['item_type']		= ( empty( $temp_it[0]->slug ) ) ? null : $temp_it[0]->slug;
					$temp['location']		= ( empty( $temp_loc[0]->slug ) ) ? null : $temp_loc[0]->slug;
					$good_arr = [ 'card', 'checked_out_date', 'contact', 'contact_date', 'email', 'name', 'notes', 'phone', 'staff', 'due_date', 'item_id' ];
					
					foreach( $good_arr as $key ) {
						$temp[$key]	= ( empty( $meta[$key] ) ) ? null : current( array_filter( $meta[$key] ) );
					}
					if( empty( $meta['waiting_date'] ) ) {
						$temp['w_date_nice'] = $temp['waiting_date'] = null;
					} else {						
						if( empty( current( array_filter( $meta['form_time'] ) ) ) ) {
							$form_time = date_i18n( 'g:i:s a', strtotime( $val->post_date ) );
						} else {
							$form_time = current( array_filter( $meta['form_time'] ) );
						}
						
						$waiting_date = strtotime( date_i18n( 'Y-m-d', current( array_filter( $meta['waiting_date'] ) ) ) . ' ' . $form_time );
						
						$temp['w_date_nice']			= date( get_option('date_format'), $waiting_date ) . '<br>' . date( 'g:i:s a', $waiting_date );
						
						$temp['waiting_date']			= $waiting_date;
						$hold_list['dates_nice'][$val->ID] = $temp['w_date_nice'];
						$hold_list['dates'][$val->ID]	= $waiting_date;
					}
					
					$hold_list['all'][$val->ID]				= $temp;
					$hold_list[$val->post_status][$temp_it[0]->slug][$temp_loc[0]->slug][$val->ID] = $temp;
					 // END if( empty( $meta['waiting_date']) )
				}
			
				arsort( $hold_list['dates'] );
				
				foreach( $hold_list['dates'] as $key => $val ) {
					$waiting_date = $val;
					$hold_list['dates_nice'][$key] = date_i18n( get_option('date_format'), $waiting_date ) . '<br>' . date_i18n( 'g:i:s a', $waiting_date );
				}				
			}
			return $hold_list;
		}
		function get_items() {
			
			$args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_stock',
						'post_status'	 => 'any' ];
			$item_list_raw = get_posts( $args );

			$items = [];
			foreach( $item_list_raw as $key => $val ) {
				$item_meta = get_post_meta( $val->ID );
				
				$items[$val->ID] = [	'post_status'	=> $val->post_status,
										'item_name'		=> current( array_filter( $item_meta['item_name'] ) ), 
										'item_manuf'	=> current( array_filter( $item_meta['item_manuf'] ) ), 
										'item_model'	=> current( array_filter( $item_meta['item_model'] ) ), 
										'item_serial'	=> current( array_filter( $item_meta['item_serial'] ) ), 
										'item_notes'	=> current( array_filter( $item_meta['item_notes'] ) ),
										'item_length'	=> current( array_filter( $item_meta['item_length'] ) ),
										'item_id'		=> $val->ID
									];
			} // END foreach( $item_list_raw as $key => $val ) 
			
			return $items;
		}
		function get_item_types() {
			$item_types		= [];
			$item_types_raw = get_terms( [	'taxonomy' => 'lendingq_item_type', 'hide_empty' => false ] );
			if( count( $item_types_raw ) !== 0 ) {
				foreach( $item_types_raw as $itemt ) {
					$item_types[$itemt->slug] = $itemt->name;
				}
			}
			return $item_types;
		}
		function get_locations() {
			// check there are locations available
			$locations = [];
			$locations_raw = get_terms( [	'taxonomy' => 'lendingq_location', 
											'hide_empty' => false ] 
										);
			if( count( $locations_raw ) !== 0 ) {
				foreach( $locations_raw as $loc ) {
					$locations[$loc->slug] = $loc->name;
				}
			}
			return $locations;
		}
		
		function get_stock_statuses() {
				return [	'item_lost'		=> __( 'Item Lost', 'lendingq' ), 
							'item_stolen'	=> __( 'Item Stolen', 'lendingq' ), 
							'item_broken'	=> __( 'Item Broken', 'lendingq' ), 
							'item_other'	=> __( 'Other', 'lendingq' ) ];
		}
		
		function handle_cancel_hold() {
			if( empty( $_REQUEST['_wpnonce'] ) 
				or false == wp_verify_nonce($_REQUEST['_wpnonce'], 'lendingq_cancel_hold' ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			}
			 // check for id
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
			  $url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error', '_wpnonce' => wp_create_nonce( 'lendingq_cancel_hold' ) ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} elseif( empty( $lending = get_post( $post_id ) ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} // END if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) )
			//if( $lending->post_status !== 'waiting_list' ) {
			//	$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'not_waiting' ], admin_url( 'edit.php' ) );
			//	wp_redirect( $url );
			//	exit;
			//}
			//wp_trash_post( $post_id );
			wp_delete_post( $post_id );
			$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'cancel_success' ], admin_url( 'edit.php' ) );
			wp_redirect( $url );
			exit;
		} // END function handle_cancel_hold()
		function handle_check_in() {
			// check for nonce
			if( empty( $_REQUEST['_wpnonce'] ) 
				or false == wp_verify_nonce($_REQUEST['_wpnonce'], 'lendingq_checkin' ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_in_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			}
			// check for id
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
			  $url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_in_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} elseif( empty( $lending = get_post( $post_id ) ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_in_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} // END if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) )
			$error = 0;
			$check_in_date	= null;
			$return_status	= null;
			$return_notes	= null;
			$unavail_status	 = null;
			
			$stock_statuses = $this->get_stock_statuses();
			
			// check for valid check in date
			if( empty( $_REQUEST['check_in_date'] ) ) {
				$error = $error | 2**LENDINGQ_DATE_NONE;
			} else {
				$d = DateTime::createFromFormat( get_option('date_format'), sanitize_text_field( $_REQUEST['check_in_date'] ) );
				if( false == ( $d && $d->format( get_option('date_format') ) == sanitize_text_field( $_REQUEST['check_in_date'] ) ) ) {
					$error = $error | 2**LENDINGQ_DATE_INVALID;
					$check_in_date = sanitize_text_field( $_REQUEST['check_in_date'] );
				} else {
					$check_in_date = date_i18n( get_option('date_format'), strtotime( sanitize_text_field( $_REQUEST['check_in_date'] ) ) );
				}
			}
			// if unavailable check for status and notes
			if( empty( $_REQUEST['return_status'] ) or
				!in_array( $_REQUEST['return_status'], ['item_available', 'item_unavailable'] ) ) {
				$error = $error | 2**LENDINGQ_RETURN_STATUS_INVALID;
			} elseif( $_REQUEST['return_status'] == 'item_unavailable' ) {
				// bad or empty unavail status
				if( empty( $_REQUEST['unavail_status'] ) or
				!array_key_exists( $_REQUEST['unavail_status'], $stock_statuses ) ) {
					$error = $error | 2**LENDINGQ_UNAVAIL_STATUS_INVALID;
				} else {
					$unavail_status	 = sanitize_text_field( $_REQUEST['unavail_status'] );
				}
				// notes empty
				if( empty( $_REQUEST['return_notes'] ) ) {
					$error = $error | 2**LENDINGQ_RETURN_STATUS_NOTES;
				}
				$return_status	= sanitize_text_field( $_REQUEST['return_status'] );
			} else {
				$return_status = 'item_available';
			}
			if( !empty( $_REQUEST['return_notes'] ) ) {
				$return_notes	= sanitize_textarea_field( $_REQUEST['return_notes'] );
			}
			// handle errors
			if( !empty( $error ) ) {
				set_transient( 'lendingq_checkin_form', ['check_in_date' => $check_in_date, 'return_status' => $return_status, 'return_notes' => $return_notes, 'unavail_status' => $unavail_status, 'unavail_status' => $unavail_status ] );
				$nonce = wp_create_nonce( 'lendingq_checkin' );
				$url = add_query_arg( [ 'post_type' => 'lendingq_stock', 'page' => 'lendingq_check_in', 'post_id' => $post_id, '_wpnonce' => $nonce, 'error' => $error ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} 
			$meta = get_post_meta( $post_id );
			$item_id = current( array_filter( $meta['item_id'] ) );
			$item = get_post( $item_id );
			update_post_meta( $item_id, 'lending_id', null );
			$item->post_status = $return_status;
			wp_update_post( $item );
			// save metadata
			update_post_meta( $item_id, 'check_in_date', $check_in_date );
			update_post_meta( $item_id, 'return_notes', $return_notes );
			update_post_meta( $item_id, 'unavail_status', $unavail_status );
			// change post status
			$lending->post_status = 'returned';
			wp_update_post( $lending );
			update_post_meta( $post_id, 'check_in_date', $check_in_date );
			update_post_meta( $post_id, 'return_notes', $return_notes );
			update_post_meta( $post_id, 'unavail_status', $unavail_status );
			// return to check in
			$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_in_list', 'message' => 'checked_in' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			wp_redirect( $url );
			exit;
		}
		function handle_checkout() {
			// check for nonce
			
			if( empty( $_REQUEST['_wpnonce'] ) 
				or false == wp_verify_nonce($_REQUEST['_wpnonce'], 'lendingq_checkout' ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			}
			
			// check for id
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
			  $url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} elseif( empty( $lending = get_post( $post_id ) ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} // END if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) )

			$hold_meta = get_post_meta( $post_id );
			// check item chosen
			
			
			
			if( empty( $item_id = sanitize_text_field( $_REQUEST['lending_item'] ) ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} 
			$item = get_post( $item_id );
		
			if( empty( $item ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out', 'message' => 'item_available' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} // END if( empty( $item_id = sanitize_text_field( $_REQUEST['lending_item'] ) ) )
		
			// check item is still available
			$args = [	'numberposts' => -1, 
						'post_type'=> 'lendingq_stock',
						'post_status'	 => 'any' ];
			$stock_raw = get_posts( $args );
			$stock = array_map( function( $e ){ return $e->ID; }, $stock_raw);
		
			if( !in_array( $item_id, $stock ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out', 'message' => 'item_available' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
			}
			
			$stock_meta = get_post_meta( $item_id );
			$item_length = current( $stock_meta['item_length'] );
			// --------------------------------------------------
			// Check in out
			// --------------------------------------------------
			$cur_time = current_time( 'timestamp', false );
			$due_date = strtotime( date_i18n('Y-m-d', $cur_time + ( $item_length * 86400 ) ) );
			$checked_out_date = strtotime( date_i18n('Y-m-d', $cur_time ) );
			// change waiting post status
			// ##############################
			$lending->post_status = 'checked_out';
			// attach item post id
			// ##############################
			update_post_meta( $post_id, 'item_id', $item_id );
			// attach checkout and due date
			// ##############################
			update_post_meta( $post_id, 'checked_out_date', $checked_out_date );
			update_post_meta( $post_id, 'due_date', $due_date );
			// change item post status
			// ##############################
			$item->post_status = 'checked_out';
			update_post_meta( $item_id, 'lending_id', $post_id );
			$stock_meta = get_post_meta( $item_id );
			$hold_meta = get_post_meta( $post_id );
			// attach waiting list post id
			// ##############################
			remove_action( 'admin_post_lendingq_checkout', [ $this, 'handle_checkout' ] );
			wp_update_post( $item );
			wp_update_post( $lending );
			add_action( 'admin_post_lendingq_checkout', [ $this, 'handle_checkout' ], 10, 2 );
			foreach ( $hold_meta as $meta_key => $meta_value ) {
				update_post_meta( $post_id, $meta_key, current( $meta_value ) );
			}
			foreach ( $stock_meta as $meta_key => $meta_value ) {
				update_post_meta( $item_id, $meta_key, current( $meta_value ) );
			}
		
			$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'success' ], admin_url( 'edit.php' ) );
			wp_redirect( $url );
			exit;			 
		}
		function handle_contacted() {
			if( empty( $_REQUEST['_wpnonce'] ) 
				or false == wp_verify_nonce($_REQUEST['_wpnonce'], 'lendingq_contacted' ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			}
			 // check for id
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
			  $url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error', '_wpnonce' => wp_create_nonce( 'lendingq_contacted' ) ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} elseif( empty( $lending = get_post( $post_id ) ) ) {
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'error' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} // END if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) )
			$lending_meta = get_post_meta( $post_id );
			$error = 0;
			// check item chosen
			if( empty( $_REQUEST['contact_staff'] ) ) {
				$error = $error | 2**HOLD_LENDINGQ_STAFF_NONE;
			} else {
				$contact_staff = sanitize_text_field( $_REQUEST['contact_staff'] );
			}
			if( $error ) {
				$url = add_query_arg( ['post_type' => 'lendingq_stock', 'page' => 'lendingq_contact_hold', 'error' => $error, 'post_id' => $post_id], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} // END if( empty( $item_id = sanitize_text_field( $_REQUEST['lending_item'] ) ) )
			// current time is contact time
			$contact_notes = ( empty( $_REQUEST['contact_notes'] ) ) ? null : sanitize_textarea_field( $_REQUEST['contact_notes'] ); 
			update_post_meta( $post_id, 'contact_date', time() );
			update_post_meta( $post_id, 'contact_staff', $contact_staff );
			update_post_meta( $post_id, 'contact_notes', $contact_notes );
			$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'contact_success' ], admin_url( 'edit.php' ) );
			wp_redirect( $url );
			exit;
		}
		function handle_post_check_hold( $post_ID, $post ) {
			global $typenow, $old_date; 
			
			$this->update_locationq_meta_hold( $post_ID );
			if( $typenow !== 'lendingq_hold' ) {
				return $post;
			} // END if( $typenow !== 'lendingq_hold' )
				
			if( $post->post_status == 'trash' or $post->post_status == 'auto-draft' ) {
				return $post;
			} // END if( $post->post_status == 'trash' or $post->post_status == 'auto-draft' )
				
			# broken status
			
			$error = 0;
			$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
			/* -------------------------------------------------- */
			/* CHECK FIELDS */
			$item_type = $location = null;
			
			if( empty( $item_type = get_the_terms( $post, 'lendingq_item_type' ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_TYPE_NONE;
			} 
			
			if( empty( $location = get_the_terms( $post, 'lendingq_location' ) ) ) {
				$error = $error | 2**LENDINGQ_LOCATION_NONE;
			}
			// No name
			$field_name = get_option( 'lendingq_field_name' );
			if( !is_array( $post_meta['name'] ) and empty( current( array_filter( $post_meta['name'] ) ) ) ) {
			   $error = $error | 2**LENDINGQ_NAME_NONE;
			   $post_meta['name'] = null;
			}
			$field_name		= get_option( 'lendingq_field_card' );
			$card_regex		= get_option( 'lendingq_field_card_regex' );
			$phone_regex	= get_option( 'lendingq_field_phone_regex' );
			// No card			  
			if( !is_array( $post_meta['card'] ) and empty( current( array_filter( $post_meta['card'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_CARD_NONE;
				$post_meta['card'] = null;
			} elseif( !empty( $card_regex ) ) {
				// Bad regex
				if( !preg_match( $card_regex, current( array_filter( $post_meta['card'] ) ) ) ) {
					$post_meta['card'] = null;
					$error = $error | 2**LENDINGQ_CARD_INVALID;
				} // END if( !preg_match( $card_regex, current( array_filter( $post_meta['card'] ) ) ) )
			}
			// Not verified
			if( !is_array( $post_meta['verified'] ) and empty( current( array_filter( $post_meta['verified'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_VERIFIED_NONE;
				$post_meta['verified'] = null;
			}
			// No contact
			if( !is_array( $post_meta['contact'] ) and empty( current( array_filter( $post_meta['contact'] ) ) ) ) {
				$post_meta['contact'] = null;
			} else {
				$post_meta['contact'] = current( array_filter( $post_meta['contact'] ) );
			}
			// Contact selected, is that field filled out and valid?
			switch( $post_meta['contact'] ) {
				case 'email':
					if( !is_array( $post_meta['email'] ) and empty( current( array_filter( $post_meta['email'] ) ) ) ) {
						$error = $error | 2**LENDINGQ_EMAIL_NONE;
						$post_meta['email'] = null;
					} elseif( !is_email( current( array_filter( $post_meta['email'] ) ) ) ) {
						$error = $error | 2**LENDINGQ_EMAIL_INVALID;
					}
					break;
				case 'phone':
					if( !is_array( $post_meta['phone'] ) and empty( current( array_filter( $post_meta['phone'] ) ) ) ) {
						$error = $error | 2**LENDINGQ_PHONE_NONE;
						$post_meta['phone'] = null;
					} elseif( !preg_match( $phone_regex, current( array_filter( $post_meta['phone'] ) ) ) ) {
						$error = $error | 2**LENDINGQ_PHONE_INVALID;
					}
					break;
				default:
					$error = $error | 2**LENDINGQ_CONTACT_NONE;
					break;				 
			}
			// No staff name
			if( !is_array( $post_meta['staff'] ) and empty( current( array_filter( $post_meta['staff'] ) ) ) ) {
				$post_meta['staff'] = null;
				$error = $error | 2**LENDINGQ_STAFF_NONE;
			}
			// if not approved yet, no date has been set.
			if( !empty( $post_meta['approved'] ) and $post->post_status !== 'checked_out' ) {
				// date on form
				
				if( !is_array( $post_meta['form_date'] ) and empty( current( array_filter( $post_meta['form_date'] ) ) ) ) {
					#$post_meta['form_date'] = null;
					$error = $error | 2**LENDINGQ_DATE_NONE;
				} else {
					$post_meta['form_date'] = current( array_filter( $post_meta['form_date'] ) );
					
					$d = DateTime::createFromFormat( get_option('date_format'), $post_meta['form_date'] );
					if( false == ( $d && $d->format( get_option('date_format') ) == $post_meta['form_date'] ) ) {
						$error = $error | 2**LENDINGQ_DATE_INVALID;
					}
				}
				// time on form
				if( !is_array( $post_meta['form_time'] ) and empty( current( array_filter( $post_meta['form_time'] ) ) ) ) {
					#$post_meta['form_time'] = null;
					$error = $error | 2**LENDINGQ_TIME_NONE;
				} else {
					$post_meta['form_time'] = current( array_filter( $post_meta['form_time'] ) );
					$t = DateTime::createFromFormat( 'g:i:s a', $post_meta['form_time'] );
					if( false == ( $t && $t->format( 'g:i:s a' ) == $post_meta['form_time'] ) ) {
						$error = $error | 2**LENDINGQ_TIME_INVALID;
					}
				}
			}
			
			// Are there any items at this branch?
			
			if( !empty( $item_type ) and !empty( $location ) ) {
				$args = array(
							'post_type' => 'lendingq_stock',
							'tax_query' => array(
							'relation' => 'AND',
								array(
									'taxonomy' => 'lendingq_item_type',
									'terms' => current($item_type)->slug,
									'field' => 'slug',
								),
								array(									
									'taxonomy' => 'lendingq_location',
									'terms' => current($location)->slug,
									'field' => 'slug',
								),

							)
						);

				$wp_query = new WP_Query($args);
				if( $wp_query->found_posts < 1 ) {
					$error = $error | 2**LENDINGQ_NOTHING_AT_BRANCH;
				}
			}
			
			
			// Fix title if name or card are empty
			if( ( !is_array( $post_meta['name'] ) and empty( current( array_filter( $post_meta['name'] ) ) ) ) or ( !is_array( $post_meta['name'] ) and empty( current( array_filter( $post_meta['name'] ) ) ) ) ) {
				$title = __( 'Draft - unfinished', 'lendingq' );
			} else {
				$title = sanitize_text_field( current( array_filter( $post_meta['name'] ) ) ) . ' - ' . sanitize_text_field( current( array_filter( $post_meta['card'] ) ) );				 
			}
			
			// Check that there is a valid checkout date
			if( $post->post_status == 'checked_out' or !empty( current( array_filter( $post_meta['checked_out_date'] ) ) ) ) {
				
				if( !is_array( $post_meta['checked_out_date'] ) and empty( current( array_filter( $post_meta['checked_out_date'] ) ) ) ) {
					#$post_meta['form_date'] = null;
					$error = $error | 2**LENDINGQ_CHECKED_OUT_DATE_NONE;
				} else {
					$post_meta['checked_out_date'] = current( array_filter( $post_meta['checked_out_date'] ) );
					$d = DateTime::createFromFormat( get_option('date_format'), $post_meta['checked_out_date'] );
					if( false == ( $d && $d->format( get_option('date_format') ) == $post_meta['checked_out_date'] ) ) {
						$error = $error | 2**LENDINGQ_CHECKED_OUT_DATE_INVALID;
					} else {
						$post_meta['checked_out_date'] = strtotime( $post_meta['checked_out_date'] );
						update_post_meta( $post_ID, 'checked_out_date', $post_meta['checked_out_date'] );
						
						$item_meta = get_post_meta( current( $post_meta['item_id'] ) );
						
						if( !empty( $item_meta['item_length'] ) ) {
							$due_date = ( 86400 * current( $item_meta['item_length'] ) ) + $post_meta['checked_out_date'];
						}
						update_post_meta( $post_ID, 'due_date', $due_date );
						
						
					}
				}
			}
			
			
			if( !empty( $error ) ) {				
				update_post_meta( $post_ID, 'error', $error );
			} else {
				$final = array();
				foreach( $post_meta as $key => $val ) {
					if( is_array( $post_meta[$key] ) ) {
						$final[$key] = current( array_filter( $val ) );
					} else {
						$final[$key] = $val;
					}
				}
				
				
				if( $post->post_status == 'draft' ) {
					$post->post_status = 'waiting_list';
					wp_update_post( $post );
				}
				
				if( empty( $post_meta['approved'] ) ) {
					$current_time = date_i18n( get_option('date_format'), current_time( 'timestamp', false ) );
					$form_date		= date_i18n( get_option('date_format'), $current_time );
					$form_time		= date_i18n( 'g:i:s a', $current_time );
					update_post_meta( $post_ID, 'form_date', $form_date );
					update_post_meta( $post_ID, 'form_time', $form_time );
					update_post_meta( $post_ID, 'approved', $current_time );
				} else {
					$current_time = date_i18n( get_option('date_format'), strtotime( current( array_filter( $post_meta['form_date'] ) ) . ' ' . current( array_filter( $post_meta['form_time'] ) ) ) );
				}
				
				$current_w_time = date_i18n( get_option('date_format'), strtotime(  $post_meta['form_date']  . ' ' . $post_meta['form_time'] ) );
				update_post_meta( $post_ID, 'w_date_nice', $current_w_time );
				update_post_meta( $post_ID, 'waiting_date', strtotime( $current_w_time ) );
				

				update_post_meta( $post_ID, 'error', null );
				$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
				
			}
			
			if( !empty( $new_item_id = current( $post_meta['new_item_id'] ) ) ) {
				$item_list = self::get_items();
				if( empty( $item_list[$new_item_id] ) ) {
					$error = $error | 2**LENDINGQ_ITEM_CHANGE_UNAVAILABLE;
				} else {
					// ------
					
					$post = get_post( current( $post_meta['item_id'] ) );
					
					wp_update_post( [ 
						'ID'    =>  current( $post_meta['item_id'] ),
						'post_status'   =>  'item_available',
					] );						
					
					wp_update_post( [ 
						'ID'    =>  $item_list[$new_item_id]['item_id'],
						'post_status'   =>  'checked_out',
					] );
					
					update_post_meta( $post_ID, 'item_id', $new_item_id );
				}
			}
			
			
			
			#$post->post_status	  = $status;
			$post->post_title	  = $title;
			$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
			if( empty( $error ) ) {
				add_action( 'redirect_post_location', function( $location ) {
					global $post_ID;
					$location = add_query_arg( 'message', 13, get_edit_post_link( $post_ID, 'url' ) );
					return $location;
				});
				remove_action( 'save_post_lendingq_hold', [ $this, 'handle_post_check_hold' ] );
				wp_update_post( $post );
				add_action( 'save_post_lendingq_hold', [ $this, 'handle_post_check_hold' ], 10, 2 );
				$url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list', 'message' => 'added_hold' ], admin_url( 'edit.php' ) );
				wp_redirect( $url );
				exit;
			} else {
				add_action( 'redirect_post_location', function ( $location ) {
					global $post_ID;
					$location = add_query_arg( 'message', 12, get_edit_post_link( $post_ID, 'url' ) );
					return $location;
				});
			}
			remove_action( 'save_post_lendingq_hold', [ $this, 'handle_post_check_hold' ] );
			wp_update_post( $post );
			add_action( 'save_post_lendingq_hold', [ $this, 'handle_post_check_hold' ], 10, 2 );
			return $post;
		} // END function handle_post_check_hold( $new_status, $old_status, $post ) 
		function handle_post_check_stock( $post_ID, $post ) {
			global $typenow; 
			
			$error = 0;
			if( $typenow !== 'lendingq_stock' ) {
				return $post;
			} // END if( $typenow !== 'lendingq_hold' )
			if( $post->post_status == 'trash' or $post->post_status == 'auto-draft' ) {
				return $post;
			} // END if( $post->post_status == 'trash' or $post->post_status == 'auto-draft' )
			$this->update_locationq_meta_item( $post_ID );
			$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
			/* -------------------------------------------------- */
			/* CHECK FIELDS */
			if( empty( get_the_terms( $post, 'lendingq_item_type' ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_TYPE_NONE;
			}
			if( empty( get_the_terms( $post, 'lendingq_location' ) ) ) {
				$error = $error | 2**LENDINGQ_LOCATION_NONE;
			}
			// No name
			$field_name = get_option( 'lendingq_field_item_name' );
			if( !is_array( $post_meta['item_name'] ) and empty( current( array_filter( $post_meta['item_name'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_NAME_NONE;
				// Fix title if name or card are empty
				$title = __( 'Draft - unfinished', 'lendingq' );
			} else {
				$args = [	'numberposts' => -1, 
							'post_type'=> 'lendingq_stock',
							'post_status'	 => 'any',
							'exclude'	   => [ $post_ID ] ];
				$post_list = array_column( get_posts( $args ), 'post_title' );
				if( $dupe_id = array_search( strtolower( current( array_filter( $post_meta['item_name'] ) ) ), array_map('strtolower', $post_list ) ) ) {
					$title = __( 'Draft - Duplicate Entry', 'lendingq' );
					$error = $error | 2**LENDINGQ_ITEM_NAME_DUPE;
				} else {
					$title = sanitize_text_field( current( array_filter( $post_meta['item_name'] ) ) );					   
				}
			}
			if( !is_array( $post_meta['item_manuf'] ) and empty( current( array_filter( $post_meta['item_manuf'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_MANUF_NONE;
			}
			if( !is_array( $post_meta['item_model'] ) and empty( current( array_filter( $post_meta['item_model'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_MODEL_NONE;
			}
			if( !is_array( $post_meta['item_serial'] ) and empty( current( array_filter( $post_meta['item_serial'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_SERIAL_NONE;
			}
			if( !is_array( $post_meta['item_length'] ) and empty( current( array_filter( $post_meta['item_length'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_LENGTH_NONE;
			} elseif( !is_numeric( current( array_filter( $post_meta['item_length'] ) ) ) ) {
				$error = $error | 2**LENDINGQ_ITEM_LENGTH_INVALID;
			}
			
			// check for marked unavailable
			if( !empty( $post_meta['mark_unavailable'] ) and current( $post_meta['mark_unavailable'] ) == 'on' ) {
				// check if status is picked and real.
				$status = "item_unavailable";
				
				$stock_statuses = $this->get_stock_statuses();
				
				// if unavailable check for status and notes
				// bad or empty unavail status				  
				if( empty( current( $post_meta['unavail_status'] ) ) or 
					!array_key_exists( current( $post_meta['unavail_status'] ), $stock_statuses ) ) {
					$error = $error | 2**LENDINGQ_UNAVAIL_STATUS_INVALID;
					$status = $post->post_status;
				}
				// notes empty
				if( empty( current( $post_meta['return_notes'] ) ) ) {
					$error = $error | 2**LENDINGQ_RETURN_STATUS_NOTES;
					$status = $post->post_status;
				}
			
				if( !empty( current( $post_meta['return_notes'] ) ) ) {
					$post_meta['return_notes'] = [ sanitize_textarea_field( $post_meta['return_notes'] ) ];
				}
			} else {
				$status = $post->post_status;
			}

			if( !empty( $error ) ) {
				update_post_meta( $post_ID, 'error', $error );
			} else {
				if( is_array( $post_meta['reenable'] ) and !empty( current( array_filter( $post_meta['reenable'] ) ) ) and current( array_filter( $post_meta['reenable'] ) ) == 'true' ) {
					$status = 'item_available';
					update_post_meta( $post_ID, 'return_notes', null );
					update_post_meta( $post_ID, 'unavail_status', null );
				}
				update_post_meta( $post_ID, 'error', null );
			}
			$post->post_status	  = $status;
			$post->post_title	  = $title;
			if( empty( $error ) ) {
				if( $status == 'draft' ) $post->post_status = 'item_available';
				add_action( 'redirect_post_location', function( $location ) {
					global $post_ID;
					$location = add_query_arg( [ 'post_type' => 'lendingq_stock', 'message' => '15' ], admin_url( 'post-new.php' ) );
					return $location;
				});
			} else {
				add_action( 'redirect_post_location', function ( $location ) {
					global $post_ID;
					$location = add_query_arg( 'message', 14, get_edit_post_link( $post_ID, 'url' ) );
					return $location;
				});
			}
			remove_action( 'save_post_lendingq_stock', [ $this, 'handle_post_check_stock' ] );
			wp_update_post( $post );
			add_action( 'save_post_lendingq_stock', [ $this, 'handle_post_check_stock' ], 10, 2 );
			return $post;
		} // END handle_post_check_stock
		function handle_post_date( $post_ID, $data ) {			  
		// Before updating, check that you transfer any old waiting date over
			global $typenow;  

			// use the custom post type here  
			if( $typenow !== 'lendingq_hold' ) {
				return;
			}
			
			$old = get_post_meta( $post_ID );
			if( !is_array( $old['waiting_date'] ) or empty( current( array_filter( $old['waiting_date'] ) ) ) ) {
				$old_date = null;
			} else {
				$old_date = current( array_filter( $old['waiting_date'] ) );
			}
			update_post_meta( $post_ID, 'old_date', $old_date );
		} // END function handle_post_date( $post_ID, $data )
		function lendingq_cancel_hold() {
			// check if nonce is okay
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with this post ID. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$post = get_post( $post_id );
			if( empty( $post ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'We couldn\'t find that lending. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$meta = get_post_meta( $post_id );
			$good_arr = [ 'name', 'card', 'phone', 'email', 'contact', 'staff', 'notes', 'form_date', 'form_time', 'due_date', 'return_date', 'checked_out_date', 'waiting_date', 'error' ];
			foreach( $good_arr as $key ) {
				$lend[$key]	= ( empty( $post_meta[$key] ) ) ? null : current( array_filter( $post_meta[$key] ) );
			}
			$item_types		= $this->get_item_types();
			$locations		= $this->get_locations();
			$temp_it = get_the_terms( $post->ID, 'lendingq_item_type' );
			$temp_loc = get_the_terms( $post->ID, 'lendingq_location' );
			$lend['item_type']		= current($temp_it)->slug;
			$lend['location']		= current($temp_loc)->slug;
			require( LENDINGQ_PATH . '/template_cancel_hold_form.php' );
		} // END function handle_cancel_hold {
		function lendingq_cancel_stock() {
			// check if nonce is okay
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with this post ID. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$post = get_post( $post_id );
			if( empty( $post ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'We couldn\'t find that item. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$meta = get_post_meta( $post_id );
			$meta_list = [ 'check_in_date', 'item_length', 'item_manuf', 'item_model', 'item_name', 'item_notes', 'item_serial', 'return_notes', 'unavail_status' ];
			foreach( $meta_list as $val ) {
				$item[$val] = null;
				if( isset( $meta[$val] ) ) {
					$item[$val] = current( array_filter( $meta[$val] ) );
				}
			}
			$item_types		= $this->get_item_types();
			$locations		= $this->get_locations();
			$temp_it = get_the_terms( $post->ID, 'lendingq_item_type' );
			$temp_loc = get_the_terms( $post->ID, 'lendingq_location' );
			$item['item_type']		= current($temp_it)->slug;
			$item['location']		= current($temp_loc)->slug;
			if( $post->post_status == 'checked_out' ) {
				require( LENDINGQ_PATH . '/template_cancel_stock_form_checked_out.php' );
			} else {
				require( LENDINGQ_PATH . '/template_cancel_stock_form.php' );
			}
		} // END function handle_cancel_stock {
		function lendingq_check_in() {
			
			// check if nonce is okay
			if(		empty( $_REQUEST['_wpnonce'] ) 
					or false == wp_verify_nonce($_REQUEST['_wpnonce'], 'lendingq_checkin' ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with the link you used. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			// check that post ID isn't empty
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with this post ID. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$lending_raw = get_post( $post_id );
			// check that post exists
			if( empty( $lending_raw ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'We couldn\'t find that lending. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$lending = get_post_meta( $lending_raw->ID );
			$item_error = false;
			// set up lending array
			$lend		= [
							'ID'				=> $lending_raw->ID, 
							'post_status'		=> $lending_raw->post_status, 
						];
			$good_arr	= [ 'name', 'card', 'contact', 'phone', 'email', 'staff', 'notes', 'due_date', 'checked_out_date', 'approved', 'waiting_date', 'item_id' ];
			
			foreach( $good_arr as $key ) {
				$lend[$key]	= current( array_filter( $lending[$key] ) );
			}
			// IF there is a valid lending, check that the status is checked out
			if( $lend['post_status'] !== 'checked_out' ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'That lending isn\'t currently marked as checked out. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			// check that there is an item associated with this
			$item_raw = get_post( $lend['item_id'] );
			if( empty( $item_raw ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'That lending has no item associated. Changing to a draft. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				die('111111');
				$lending_raw->post_status = 'draft';
				wp_update_post( $lending_raw );
				return false;
			}
			$item_types		= $this->get_item_types();
			$locations		= $this->get_locations();
			$location_raw		= get_the_terms( $post_id, 'lendingq_location' );
			$lend['location']	= $locations[$location_raw[0]->slug];
			$item_type_raw		= get_the_terms( $post_id, 'lendingq_item_type' );
			$lend['item_type']	= $item_types[$item_type_raw[0]->slug];
			$item_meta = get_post_meta( $lend['item_id'] );
			$item = [	
				'type'		=> $item_types[$item_type_raw[0]->slug], 
				'location'	=> $locations[$location_raw[0]->slug], 
			];
			$goo_arr = [ 'name', 'manuf', 'model', 'serial', 'notes' ];
			foreach( $good_arr as $key ) {
				$item[$key] = current( array_filter( $item_meta['item_name'] ) );
			}
			
			require( LENDINGQ_PATH . '/template_check_in_form.php' );
		} // END function lendingq_check_in()
		function lendingq_check_out() {
			// check if nonce is okay
			//if( empty( $_REQUEST['_wpnonce'] ) or ( false == wp_verify_nonce($_REQUEST['_wpnonce'], 'lendingq_checkout' ) ) ) {
				//echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with the link you used. Please go back and try again2.', 'lendingq' ) .'</h3></div>';
				//return false;
			//}
			
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with this post ID. Please go back and try again3.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$post = get_post( $post_id );
			if( empty( $post ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'We couldn\'t find that lending. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			// setup lending ticket for display
			$lend = [];
			$lend['title'] = $post->post_title;
			$lend['status'] = $post->post_status;
			$post_meta = get_post_meta( $post->ID );
			switch( current( array_filter( $post_meta['contact'] ) ) ) {
				case 'email':
					$contact = current( array_filter( $post_meta['email'] ) );
					break;
				case 'phone':
					$contact = current( array_filter( $post_meta['phone'] ) );
					break;
				default:
					$contact = null;
			}
			$item_types = $this->get_item_types();
			$locations = $this->get_locations();
			$temp_it = get_the_terms( $post->ID, 'lendingq_item_type' );
			$temp_loc = get_the_terms( $post->ID, 'lendingq_location' );
			$lend['contact']		= $contact;
			$lend['item_type']		= current($temp_it)->slug;
			$lend['location']		= current($temp_loc)->slug;
			foreach( ['card', 'name', 'notes', 'staff', 'waiting_date', 'contact_date', 'contact_staff', 'contact_notes' ] as $key ) 
			{
				$lend[$key]	= ( !isset( $post_meta[$key] ) ) ? null : current( array_filter( $post_meta[$key] ) );
			}
			require( LENDINGQ_PATH . '/template_check_out_form.php' );
		} // END function lendingq_check_out()
		function lendingq_load_plugin_textdomain() {
			load_plugin_textdomain( 'lendingq', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		} // END function lendingq_load_plugin_textdomain
		function lendingq_contact_hold() {
			 // check if nonce is okay
			if( empty( $post_id = sanitize_text_field( $_REQUEST['post_id'] ) ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'There was a problem with this post ID. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			$post = get_post( $post_id );
			if( empty( $post ) ) {
				echo '<div class="wrap"><h1>'. __( 'LendingQ Error', 'lendingq' ) .'</h1><h3>'. __( 'We couldn\'t find that lending. Please go back and try again.', 'lendingq' ) .'</h3></div>';
				return false;
			}
			// setup lending ticket for display
			$lend = [];
			$lend['title'] = $post->post_title;
			$lend['status'] = $post->post_status;
			$post_meta = get_post_meta( $post->ID );
			$item_types = $this->get_item_types();
			$locations = $this->get_locations();
			$temp_it = get_the_terms( $post->ID, 'lendingq_item_type' );
			$temp_loc = get_the_terms( $post->ID, 'lendingq_location' );
			$lend['hold_date']		= strtotime( $post->post_date );
			$lend['item_type']		= current($temp_it)->slug;
			$lend['location']		= current($temp_loc)->slug;
			$good_arr = ['card', 'contact', 'name', 'email', 'phone', 'notes', 'staff', 'waiting_date' ];
			foreach( $good_arr as $key ) {
				$lend[$key]	= ( empty( $post_meta[$key] ) ) ? null : current( array_filter( $post_meta[$key] ) );
			}
			$error_msg = [];
			if( !empty( $_REQUEST['error'] ) ) {
				$error_msg = $this->list_errors_hold($_REQUEST['error']);
			}
			require( LENDINGQ_PATH . '/template_contact_hold_form.php' );
		} // END function lendingq_contact_hold()
		function list_errors_hold( $error_val ) {
			// Get the custom field names for displaying in the error
			$field_card			= get_option( 'lendingq_field_card' );
			$field_contact		= get_option( 'lendingq_field_contact' );
			$field_email		= get_option( 'lendingq_field_email' );
			$field_name			= get_option( 'lendingq_field_name' );
			$field_phone		= get_option( 'lendingq_field_phone' );
			$field_verified		= get_option( 'lendingq_field_verified' );
			$field_staff		= get_option( 'lendingq_field_staff' );
			$field_item_name	= get_option( 'lendingq_field_item_name' );
			$field_item_manuf	= get_option( 'lendingq_field_item_manuf' );
			$field_item_model	= get_option( 'lendingq_field_item_model' );
			$field_item_serial	= get_option( 'lendingq_field_item_serial' );
			// Create an error message for each defined error type.
			$error_arr[LENDINGQ_CARD_INVALID]	 = __( 'The <em>'.$field_card.'</em> field is invalid.', 'lendingq' );
			$error_arr[LENDINGQ_CARD_NONE]		 = __( 'You must fill in the <em>'.$field_card.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_CONTACT_NONE]	 = __( 'You must choose an option under the <em>'.$field_contact.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_EMAIL_INVALID]	 = __( 'The <em>'.$field_email.'</em> field isn\'t formatted properly.', 'lendingq' );
			$error_arr[LENDINGQ_EMAIL_NONE]		 = __( 'You must fill in the <em>'.$field_email.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_NAME_NONE]		 = __( 'You must fill in the <em>'.$field_name.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_PHONE_INVALID]	 = __( 'The <em>'.$field_phone.'</em> field isn\'t formatted properly.', 'lendingq' );
			$error_arr[LENDINGQ_PHONE_NONE]		 = __( 'You must fill in the <em>'.$field_phone.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_STAFF_NONE]		 = __( 'You must fill in the <em>'.$field_staff.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_VERIFIED_NONE]	 = __( 'You must check the <em>'.$field_verified.'</em> field once you have verified the information.', 'lendingq' );			 
			$error_arr[LENDINGQ_ITEM_TYPE_NONE]	 = __( 'You must choose an <em>Item Type</em>.', 'lendingq' );
			$error_arr[LENDINGQ_LOCATION_NONE]	 = __( 'You must choose a <em>Location</em>.', 'lendingq' );
			
			$error_arr[LENDINGQ_DATE_NONE]		 = __( 'You must enter a <em>Hold Date</em>.', 'lendingq' );
			$error_arr[LENDINGQ_DATE_INVALID]	 = __( 'Your <em>Hold Date</em> is invalid.', 'lendingq' );
			
			$error_arr[LENDINGQ_CHECKED_OUT_DATE_NONE]		 = __( 'You must enter a <em>Checked Out Date</em>.', 'lendingq' );
			$error_arr[LENDINGQ_CHECKED_OUT_DATE_INVALID]	 = __( 'Your <em>Checked Out Date</em> is invalid.', 'lendingq' );
			
			$error_arr[LENDINGQ_TIME_NONE]		 = __( 'You must enter a <em>Lending Time</em>.', 'lendingq' );
			$error_arr[LENDINGQ_TIME_INVALID]	 = __( 'Your <em>Lending Time</em> is invalid.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_NAME_NONE]	 = __( 'You must fill in the <em>'.$field_item_name.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_NAME_DUPE]	 = __( 'That <em>'.$field_item_name.'</em> is already in use.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_MANUF_NONE]  = __( 'You must fill in the <em>'.$field_item_manuf.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_MODEL_NONE]  = __( 'You must fill in the <em>'.$field_item_model.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_SERIAL_NONE] = __( 'You must fill in the <em>'.$field_item_serial.'</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_LENGTH_NONE]	 = __( 'You must fill in the <em>length of lending in days</em>.', 'lendingq' );
			$error_arr[LENDINGQ_ITEM_LENGTH_INVALID] = __( 'You must enter a valid number in the <em>length of lending in days</em>.', 'lendingq' );
			$error_arr[LENDINGQ_HOLD_STAFF_NONE] = __( 'You must enter a name in the <em>Staff who contacted</em> field.', 'lendingq' );
			$error_arr[LENDINGQ_RETURN_STATUS_INVALID] = __( 'You must choose a return status.', 'lendingq' );
			$error_arr[LENDINGQ_UNAVAIL_STATUS_INVALID] = __( 'You must choose a reason why the item is being returned as unavailable.', 'lendingq' );
			$error_arr[LENDINGQ_RETURN_STATUS_NOTES] = __( 'You must explain why the item being returned is unavailable.', 'lendingq' );
			$error_arr[LENDINGQ_NOTHING_AT_BRANCH] = __( 'The location you have chosen does not carry the item type you picked.', 'lendingq' );
			
			$error_final = [];
			foreach( $error_arr as $key => $val ) {
				if( $error_val & 2**$key ) $error_final[] = $error_arr[$key];
			}
			return $error_final;
		}
		function meta_box_add() {
			add_meta_box( 'lendingq_meta', __( 'LendingQ Hold Form', 'lendingq' ), [ $this, 'meta_box_display_hold' ], 'lendingq_hold', 'normal', 'high' );
			add_meta_box( 'lendingq_meta', __( 'LendingQ Item Management', 'lendingq' ), [ $this, 'meta_box_display_item' ], 'lendingq_stock', 'normal', 'high' );
		} // END function meta_box_add() 
		
		function meta_box_display_hold( $post ) {
			$meta = get_post_meta( $post->ID );
			
			foreach( [ 'name', 'card', 'verified', 'phone', 'email', 'contact', 'staff', 'notes', 'waiting_date', 'form_date', 'form_time', 'approved' , 'due_date', 'return_date', 'checked_out_date', 'item_id' ] as $val ) {
				if( !empty( $meta[$val] ) ) {
					$form[$val] = htmlspecialchars( current( array_filter( $meta[$val] ) ) );
				} else {
					$form[$val] = null;
				}
			} // END foreach( [ 'name', 'card' ] as $val )
			$error_msg = [];
			if( isset( $meta['error'] ) and isset( $_REQUEST['message'] ) ) {
				$error_msg = $this->list_errors_hold( current( $meta['error'] ) );
			}
			
			/*
			foreach( [ 'reenable', 'item_name', 'item_notes', 'item_serial', 'item_model', 'item_manuf', 'item_length', 'check_in_date', 'return_notes', 'unavail_status' ] as $val ) {
				$form[$val] = ( !isset( $meta[$val] ) ) ? null : htmlspecialchars( current( array_filter( $meta[$val] ) ) );
			} // END foreach( [ 'name', 'card' ] as $val )
			*/
			
			$item_list = $this::get_items();
			
			if( 
				( 
					isset( $meta['item_id'] ) and 
					!empty( current( array_filter( $meta['item_id'] ) ) ) ) and 
				( 
					isset( $item_list[current($meta['item_id'])] ) and
					!empty( current( array_filter( $item_list[current($meta['item_id'])] ) ) ) 
				) )  {
				$item = array_filter( $item_list[current($meta['item_id'])] );
				
				$item_types		= $this->get_item_types();
				$locations		= $this->get_locations();
				$location_raw		= current( get_the_terms( current( array_filter( $meta['item_id'] ) ), 'lendingq_location' ) );
				$item['item_location']	 = $locations[$location_raw->slug];
				$item_type_raw		= current( get_the_terms( current( array_filter( $meta['item_id'] ) ), 'lendingq_item_type' ) );
				$item['item_type']	= $item_types[$item_type_raw->slug];
			} else {
				$item = [ 'post_status' => null, 'item_name' => null, 'item_manuf' => null, 'item_model' => null, 'item_serial' => null, 'item_notes' => null, 'item_length' => null ];
			}

			$form['item'] = $item;

			require( LENDINGQ_PATH . '/template_post_hold.php' );
		} // END function meta_box_display_hold( $post ) 
		function meta_box_display_item( $post ) {
			$meta = get_post_meta( $post->ID );
			$post_status = $post->post_status;
			foreach( [ 'reenable', 'item_name', 'item_notes', 'item_serial', 'item_model', 'item_manuf', 'item_length', 'check_in_date', 'return_notes', 'unavail_status' ] as $val ) {
				$form[$val] = ( !isset( $meta[$val] ) ) ? null : htmlspecialchars( current( $meta[$val] ) );
			} // END foreach( [ 'name', 'card' ] as $val )
			$error_msg = [];
			if( isset( $meta['error'] ) and isset( $_REQUEST['message'] ) ) {
				$error_msg = $this->list_errors_hold( current( $meta['error'] ) );
			}
			require( LENDINGQ_PATH . '/template_post_item.php' );
		} // END function meta_box_display_item( $post ) 
		function page_disp_check_in_list() {
			$filter = null;
			
			if( !empty( $_REQUEST['clear_filter'] ) ) {
				#set_transient( 'lendingq_check_out_filter', null );
				update_user_meta( get_current_user_id(), 'lendingq_check_in_filter', null );
			}
			if( !empty( $_REQUEST['set_filter'] ) ) {
				$filter = sanitize_text_field( $_REQUEST['set_filter'] );
				#set_transient( 'lendingq_check_out_filter', $filter );
				update_user_meta( get_current_user_id(), 'lendingq_check_in_filter', $filter );
				
			}
			$filter = current( get_user_meta( get_current_user_id(), 'lendingq_check_out_filter' ) );
			
			$item_types		= $this->get_item_types();
			$locations = $this->get_locations();
			
			if( !empty( $filter ) and !array_key_exists( $filter, $locations ) ) {
				$filter = null;
				update_user_meta( get_current_user_id(), 'lendingq_check_in_filter', null );
			}
			
			
			$post_list = $this->check_in_out( $item_types, $locations );
			
			$item = $this->get_items();
			
			$hold_list = $this->get_holds();
			
			
			$this->show_error();
			require( LENDINGQ_PATH . '/template_check_in_list.php' );
		}
		function page_disp_check_out_list() {
			$filter = null;
			
			if( !empty( $_REQUEST['clear_filter'] ) ) {
				#set_transient( 'lendingq_check_out_filter', null );
				update_user_meta( get_current_user_id(), 'lendingq_check_out_filter', null );
			}
			if( !empty( $_REQUEST['set_filter'] ) ) {
				$filter = sanitize_text_field( $_REQUEST['set_filter'] );
				#set_transient( 'lendingq_check_out_filter', $filter );
				update_user_meta( get_current_user_id(), 'lendingq_check_out_filter', $filter );
				
			}
			$filter = current( get_user_meta( get_current_user_id(), 'lendingq_check_out_filter' ) );
			
			$item_types		= $this->get_item_types();
			$locations		= $this->get_locations();
			if( !empty( $filter ) and !array_key_exists( $filter, $locations ) ) {
				$filter = null;
				##set_transient( 'lendingq_check_out_filter', null );
				update_user_meta( get_current_user_id(), 'lendingq_check_out_filter', null );
			}
			$post_list = $this->check_in_out( $item_types, $locations );
			$item = $this->get_items();
			$hold_list = $this->get_holds();
			$this->show_error();
			require( LENDINGQ_PATH . '/template_check_out_list.php' );
		}
		function page_disp_settings() {
			echo '			  <div class="wrap">'."\r\n";
			echo '				  <h1>LendingQ Settings</h1>'."\r\n";
			echo '				  <form method="post" action="options.php">'."\r\n";
			settings_fields( 'lendingq_seetings_fields' );
			do_settings_sections( 'lendingq_settings' );
			submit_button();
			echo '				  </form>';
			echo '			  </div>'."\r\n";
		} // END function page_disp_settings() 
		function post_setup_custom() {
			register_post_type( 'lendingq_hold',
							   [	'labels'			 => [
											'add_new'				=> __( 'New Hold', 'lendingq' ),
											'add_new_item'			=> __( 'Add a new Hold', 'lendingq' ),
											'all_items'				=> __( 'All Holds', 'lendingq' ),
											'edit_item'				=> __( 'Edit Hold', 'lendingq' ),
											'filter_items_list'		=> __( 'Filter Holds list', 'lendingq' ),
											'insert_into_item'		=> __( 'Insert into Hold', 'lendingq' ),
											'items_list'			=> __( 'Holds list', 'lendingq' ),
											'items_list_navigation' => __( 'Holds list navigation', 'lendingq' ),
											'menu_name'				=> __( 'LendingQ', 'lendingq' ),
											'name'					=> __( 'All Holds', 'lendingq' ),
											'name_admin_bar'		=> __( 'LendingQ Hold', 'lendingq' ),
											'new_item'				=> __( 'New Hold', 'lendingq' ),
											'not_found'				=> __( 'No holds found', 'lendingq' ),
											'not_found_in_trash'	=> __( 'No holds found in trash', 'lendingq' ),
											'parent_item_colon'		=> __( 'Parent Hold:', 'lendingq' ),
											'search_items'			=> __( 'Search Holds', 'lendingq' ),
											'singular_name'			=> __( 'Hold List', 'lendingq' ),
											'uploaded_to_this_item' => __( 'Uploaded to this hold', 'lendingq' ),
											'view_item'				=> __( 'View Hold', 'lendingq' ),
										],
										'capabilities'	 => [
											'edit_post'				=> 'lendingq_view',
											'edit_posts'			=> 'lendingq_view',
											'edit_others_posts'		=> 'lendingq_view',
											'publish_posts'			=> 'lendingq_view',
											'read_post'				=> 'lendingq_view',
											'read_private_posts'	=> 'lendingq_view',
											'delete_post'			=> 'lendingq_view',
											'delete_posts'			=> 'lendingq_view', 
										], 
										'has_archive'	 => true,
										'menu_icon'		 => 'dashicons-controls-repeat', 
										'public'		 => true,
										'rewrite'		 => [ 'slug' => 'lendingq-hold' ],
										'supports'		 => [ null ], 
							   ]
							  );
			register_post_type( 'lendingq_stock',
							   [	'labels'			 => [
											'add_new'				=> __( 'New Item', 'lendingq' ),
											'add_new_item'			=> __( 'Add a new Item', 'lendingq' ),
											'all_items'				=> __( 'All Items', 'lendingq' ),
											'edit_item'				=> __( 'Edit Item', 'lendingq' ),
											'filter_items_list'		=> __( 'Filter items list', 'lendingq' ),
											'insert_into_item'		=> __( 'Insert into item', 'lendingq' ),
											'items_list'			=> __( 'Items list', 'lendingq' ),
											'items_list_navigation' => __( 'Items list navigation', 'lendingq' ),
											'menu_name'				=> __( 'LendingQ Stock', 'lendingq' ),
											'name'					=> __( 'All Items', 'lendingq' ),
											'name_admin_bar'		=> __( 'LendingQ Item', 'lendingq' ),
											'new_item'				=> __( 'New Item', 'lendingq' ),
											'not_found'				=> __( 'No items found', 'lendingq' ),
											'not_found_in_trash'	=> __( 'No items found in trash', 'lendingq' ),
											'parent_item_colon'		=> __( 'Parent Item:', 'lendingq' ),
											'search_items'			=> __( 'Search Items', 'lendingq' ),
											'singular_name'			=> __( 'Item List', 'lendingq' ),
											'uploaded_to_this_item' => __( 'Uploaded to this lending', 'lendingq' ),
											'view_item'				=> __( 'View Item', 'lendingq' ),
										],
									  'capabilities'   => [
											'edit_post'				=> 'lendingq_manage',
											'edit_posts'			=> 'lendingq_manage',
											'edit_others_posts'		=> 'lendingq_manage',
											'publish_posts'			=> 'lendingq_manage',
											'read_post'				=> 'lendingq_manage',
											'read_private_posts'	=> 'lendingq_manage',
											'delete_post'			=> 'lendingq_manage',
											'delete_posts'			=> 'lendingq_manage'
									  ], 
										'has_archive'	 => true,
										'menu_icon'		 => 'dashicons-controls-repeat', 
										'public'		 => true,
										'rewrite'		 => [ 'slug' => 'lendingq-stock' ],
										'supports'		 => [ null ], 
							   ]
							  );
			register_post_status(	'waiting_list', [
									'label'						=> _x( 'Waiting List', 'post status label', 'lendingq' ),
									'public'					=> true,
									'label_count'				=> _n_noop( 'Waiting List <span class="count">(%s)</span>', 'Waiting List <span class="count">(%s)</span>', 'lendingq' ),
									'post_type'					=> 'lendingq_hold', 
									'show_in_admin_all_list'	=> true,
									'show_in_admin_status_list' => true,
								] );
			register_post_status(	'checked_out', [
									'label'						=> _x( 'Checked Out', 'post' ),
									'label_count'				=> _n_noop( 'Checked Out (%s)', 'Checked Out (%s)' ),
									'post_type'					=> 'lendingq_hold', 
									'public'					=> true,
									'show_in_admin_all_list'	=> true,
									'show_in_admin_status_list' => true,
								] );
			register_post_status(	'returned', [
									'label'						=> _x( 'Returned', 'post' ),
									'label_count'				=> _n_noop( 'Returned (%s)', 'Returned (%s)' ),
									'post_type'					=> 'lendingq_hold', 
									'public'					=> true,
									'show_in_admin_all_list'	=> true,
									'show_in_admin_status_list' => true,
								] );
			register_taxonomy(	'lendingq_location', 
								[ 'lendingq_hold', 'lendingq_stock' ], 
								[
									'labels'						 => [
										'add_new_item'				 => __( 'Add New Location', 'lendingq' ),
										'add_or_remove_items'		 => __( 'Add or remove items', 'lendingq' ),
										'all_items'					 => __( 'All Locations', 'lendingq' ),
										'choose_from_most_used'		 => __( 'Choose from the most used', 'lendingq' ),
										'edit_item'					 => __( 'Edit Location', 'lendingq' ),
										'edit_item'					 => __( 'Edit Location', 'lendingq' ),
										'items_list'				 => __( 'Locations list', 'lendingq' ),
										'items_list_navigation'		 => __( 'Locations list navigation', 'lendingq' ),
										'menu_name'					 => __( 'Locations', 'lendingq' ),
										'name'						 => _x( 'Locations', 'Taxonomy General Name', 'lendingq' ),
										'new_item_name'				 => __( 'New Location Name', 'lendingq' ),
										'no_terms'					 => __( 'No items', 'lendingq' ),
										'not_found'					 => __( 'Not Found', 'lendingq' ),
										'parent_item'				 => __( 'Parent Location', 'lendingq' ),
										'parent_item_colon'			 => __( 'Parent Location:', 'lendingq' ),
										'popular_items'				 => __( 'Popular Locations', 'lendingq' ),
										'search_items'				 => __( 'Search Locations', 'lendingq' ),
										'separate_items_with_commas' => __( 'Separate items with commas', 'lendingq' ),
										'singular_name'				 => _x( 'LendingQ Locations', 'Taxonomy Singular Name', 'lendingq' ),
										'update_item'				 => __( 'Update Location', 'lendingq' ),
										'view_item'					 => __( 'View Location', 'lendingq' ),
									],
									'capabilities'				 => [ 
										'manage_terms'	=> 'lendingq_manage',
										'edit_terms'	=> 'lendingq_manage',
										'delete_terms'	=> 'lendingq_manage',
										'assign_terms'	=> 'read' 
									], 
									'rewrite'					 => [ 'slug' => 'lendingq_location_slug' ], 
									'hierarchical'				 => false,
									'public'					 => true,
									'show_ui'					 => true,
									'show_in_quick_edit'		 => false,
									'meta_box_cb'				 => 'post_categories_meta_box',
									'show_admin_column'			 => true,
									'show_in_nav_menus'			 => true,
									'show_tagcloud'				 => true,
								]
							 );
			register_taxonomy( 'lendingq_item_type', 
								[ 'lendingq_hold', 'lendingq_stock' ], 
								[
									'labels'					 => [
										'add_new_item'				 => __( 'Add New Item', 'lendingq' ),
										'add_or_remove_items'		 => __( 'Add or remove items', 'lendingq' ),
										'all_items'					 => __( 'All Types', 'lendingq' ),
										'choose_from_most_used'		 => __( 'Choose from the most used', 'lendingq' ),
										'edit_item'					 => __( 'Edit Item', 'lendingq' ),
										'items_list'				 => __( 'Items list', 'lendingq' ),
										'items_list_navigation'		 => __( 'Items list navigation', 'lendingq' ),
										'menu_name'					 => __( 'Item Types', 'lendingq' ),
										'name'						 => _x( 'Item Types', 'Taxonomy General Name', 'lendingq' ),
										'new_item_name'				 => __( 'New Item Name', 'lendingq' ),
										'no_terms'					 => __( 'No items', 'lendingq' ),
										'not_found'					 => __( 'Not Found', 'lendingq' ),
										'parent_item'				 => __( 'Parent Item', 'lendingq' ),
										'parent_item_colon'			 => __( 'Parent Item:', 'lendingq' ),
										'popular_items'				 => __( 'Popular Items', 'lendingq' ),
										'search_items'				 => __( 'Search Items', 'lendingq' ),
										'separate_items_with_commas' => __( 'Separate items with commas', 'lendingq' ),
										'singular_name'				 => _x( 'LendingQ Item Types', 'Taxonomy Singular Name', 'lendingq' ),
										'update_item'				 => __( 'Update Item', 'lendingq' ),
										'view_item'					 => __( 'View Item', 'lendingq' ),
									],
									'capabilities'			   => [ 
										'manage_terms'	=> 'lendingq_manage',
										'edit_terms'	=> 'lendingq_manage',
										'delete_terms'	=> 'lendingq_manage',
										'assign_terms'	=> 'read' 
									], 
									'rewrite'					 => [ 'slug' => 'lendingq_item_type' ],
									'hierarchical'				 => false,
									'public'					 => true,
									'show_ui'					 => true,
									'show_in_quick_edit'		 => false,
									'meta_box_cb'				 => 'post_categories_meta_box',
									'show_admin_column'			 => true,
									'show_in_nav_menus'			 => true,
									'show_tagcloud'				 => true,
									'show_in_rest'				 => true,
								]
							 );
			register_post_status(	'item_available', [
										'label'						=> _x( 'Available', 'post status label', 'lendingq' ),
										'label_count'				=> _n_noop( 'Available <span class="count">(%s)</span>', 'Available <span class="count">(%s)</span>', 'lendingq' ),
										'post_type'					=> [ 'lendingq_stock' ], 
										'public'					=> true,
										'show_in_admin_all_list'	=> true,
										'show_in_admin_status_list' => true,
			] );
			register_post_status(	'checked_out', [
										'label'						=> _x( 'Checked Out', 'post' ),
										'label_count'				=> _n_noop( 'Checked Out (%s)', 'Checked Out (%s)' ),
										'post_type'					=> [ 'lendingq_stock' ], 
										'public'					=> true,
										'show_in_admin_all_list'	=> true,
										'show_in_admin_status_list' => true,
			] );
			register_post_status(	'item_unavailable', [
										'label'						=> _x( 'Unavailable', 'post' ),
										'label_count'				=> _n_noop( 'Unavailable <span class="count">(%s)</span>', 'Unavailable <span class="count">(%s)</span>', 'lendingq' ),
										'post_type'					=> [ 'lendingq_stock' ], 
										'public'					=> true,
										'show_in_admin_all_list'	=> true,
										'show_in_admin_status_list' => true,
			] );
		} // END function post_setup_custom()
		function register_hold_sortable_columns( $columns ) {
			$columns['post_id'] = 'ID';
			$columns['waiting_date'] = 'waiting_date';
			$columns['post_status'] = 'post_status';
			return $columns;
		}
		function register_hold_sortable_columns_stock( $columns ) {
			$columns['post_id'] = 'ID';
			$columns['item_name'] = 'item_name';
			$columns['item_type'] = 'item_type';
			return $columns;
		}
		function remove_bulk_actions( $actions ) {
			global $typenow;  
			if( $typenow == 'lendingq_hold' or $typenow == 'lendingq_stock' ){	
				unset( $actions['edit'] );
			}
			return $actions;
		} // END function remove_bulk_actions()
		function sanitize( $value ) {
			return $value;
		} // END function sanitize( $value ) 
		function section_form_fields_hold() {
			_e( 'Please enter how you would like the form fields to appear if you do not like the defaults.<br />You can also change the help text that appears under each field.', 'lendingq' );
		}
		function section_form_fields_item() {
			_e( 'Please enter how you would like the form fields to appear if you do not like the defaults.<br />You can also change the help text that appears under each field.', 'lendingq' );
		}
		function section_form_fields_optional() {
			_e( 'If you put a value in Contact Overdue, the days since contacted will go red if it goes past this value.', 'lendingq' );
		}
		function section_header_hold() {
			_e( 'This section contains settings for Holds.', 'lendingq' );
		} // END function section_header_hold()
		function section_header_item() {
			_e( 'This section contains settings for Items in your stock.', 'lendingq' );
		} // END function section_header_hold()
		function setup_hold_post_column_values( $column, $post_ID ) {
			switch( $column ) {
				case 'post_id' :
					echo $post_ID;
					break;
				case 'post_status' :
					$post_status = get_post_status_object( get_post_status( $post_ID ) );
					echo $post_status->label;
					break;
				case 'waiting_date' :
					$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );

					if( isset( $post_meta['waiting_date'] ) ) {
					
						if( empty( current( array_filter( $post_meta['form_time'] ) ) ) ) {
							$form_time = get_the_date( 'g:i:s a', $post_ID );
							

						} else {
							$form_time = current( array_filter( $post_meta['form_time'] ) );
						}
												
						$waiting_date = strtotime( date_i18n( 'Y-m-d', current( array_filter( $post_meta['waiting_date'] ) ) ) . ' ' . $form_time );
					
						$date_format = get_option('date_format') .' - '. 'g:i:s a';
						#echo date_i18n( $date_format, $waiting_date );
						echo date( $date_format, $waiting_date );
					}
					
					break;
			}
		}
		
		function setup_stock_post_column_values( $column, $post_ID ) {
			$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
			switch( $column ) {
				case 'post_id' :
					echo $post_ID;
					break;
				case 'post_status' :
					$post_status = get_post_status_object( get_post_status( $post_ID ) );
					echo $post_status->label;
					break;
				case 'waiting_date' :
					if( isset( $post_meta['waiting_date'] ) ) {
						$date_format = get_option('date_format') .' - '.get_option( 'time_format');
						echo 'aaaa'.date_i18n( $date_format, current( array_filter( $post_meta['waiting_date'] ) ) );
					}
					break;
				case 'item_name' :
					$edit_link = get_edit_post_link( $post_ID );
				
					echo '<a href="'.$edit_link.'">'.current( array_filter( $post_meta['item_name'] ) ).'</a>';
					break;
			}
		}
		
		function setup_stock_post_columns( $columns ) {
			$new = [ 'post_id' => 'ID', 'item_name' => __( 'Item Name', 'lendingq' ), 'post_status' => __( 'Status', 'lendingq' ) ];
			unset( $columns['title'] );
			$columns = array_slice( $columns, 0, 1) + $new + array_slice( $columns, 1 );
			
			return $columns;	
		} // END function setup_hold_post_columns( $columns ) {
		
		function setup_hold_post_columns( $columns ) {
			unset( $columns['date'] );
			unset( $columns['expirationdate'] );
			$new = [ 'post_id' => 'ID' ];
			$columns = array_slice( $columns, 0, 1 ) + $new + array_slice( $columns, 1 );
			$columns['waiting_date'] = __( 'Date Added', 'lendingq' );
			$columns['post_status'] = __( 'Status', 'lendingq' );
			return $columns;	
		} // END function setup_hold_post_columns( $columns ) {
		function setup_post_updated_messages( $messages ) {
			// This allows us to add additional messages to the standard wordpress messages
			// so that we can call them to display our custom message when the post is updated.
			$waiting_list_url = add_query_arg( [ 'post_type' => 'lendingq_hold', 'page' => 'lendingq_check_out_list' ], admin_url( 'edit.php' ) );
			$messages['post'][12] = __( 'Your lending was unsuccessful. Please check the errors below.');
			$messages['post'][13] = __( 'Your lending was successful and it now on the waiting list. <a href="'.$waiting_list_url.'">Click here to Check Out.</a>');
			$messages['post'][14] = __( 'Your item was not added. Please check the errors below.');
			$messages['post'][15] = __( 'Your item has been added to the stock.');
			return $messages;
		} // END function setup_post_updated_messages( $messages )
		function show_error() {
			if( !empty( $_REQUEST['message'] ) ) {
				$error = sanitize_text_field( $_REQUEST['message'] );
				switch( $error ) {
					case 'error':
						echo '	<div class="notice notice-error"><p>'.__( 'There was a problem with the link you used. Please try again.', 'lendingq' ).'</p></div>';
						break;
					case 'item_available':
						echo '	<div class="notice notice-error"><p>'.__( 'That item is no longer available. Please try again.', 'lendingq' ).'</p></div>';
						break;
					case 'success':
						echo '	<div class="notice notice-success"><p>'.__( 'You have successfully checked out an item.', 'lendingq' ).'</p></div>';
						break;
					case 'not_waiting':
						echo '	<div class="notice notice-error"><p>'.__( 'That hold is not on the waiting list and cannot be cancelled.', 'lendingq' ).'</p></div>';
						break;
					case 'cancel_success':
						echo '	<div class="notice notice-success"><p>'.__( 'You have successfully cancelled a hold.', 'lendingq' ).'</p></div>';
						break;
					case 'contact_success':
						echo '	<div class="notice notice-success"><p>'.__( 'You have successfully marked a hold as contacted.', 'lendingq' ).'</p></div>';
						break;
					case 'added_hold':
						echo '	<div class="notice notice-success"><p>'.__( 'You have successfully added a new hold.', 'lendingq' ).'</p></div>';
						break;
					case 'checked_in':
						echo '	<div class="notice notice-success"><p>'.__( 'You have successfully checked in an item.', 'lendingq' ).'</p></div>';
						break;
				} // END switch( $error )
			} // END if( !empty( $error = sanitize_text_field( $_REQUEST['message'] ) ) )
		}
		function update_locationq_meta_hold( $post_ID ) {
			$post		= get_post( sanitize_text_field( $post_ID ) );
			$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
			if( $post->post_status == 'auto-draft' ) {
				return false;
			} // END if( $post->post_status == 'auto-draft' )
			$meta_arr = [];
			foreach( [ 'name', 'card', 'verified', 'phone', 'email', 'contact', 'staff', 'notes', 'form_date', 'form_time', 'due_date', 'return_date', 'checked_out_date', 'new_item_id' ] as $key ) {
				if( $key == 'notes' ) {
					$meta_val = ( empty( $_REQUEST[$key] ) ) ? null : sanitize_textarea_field( $_REQUEST[$key] );
				} else {
					$meta_val = ( empty( $_REQUEST[$key] ) ) ? null : sanitize_text_field( $_REQUEST[$key] );
				}
				update_post_meta( $post_ID, $key, $meta_val );
				$meta_arr[$key] = $meta_val;
			} // END foreach( [ 'name', 'card' ] as $key )
			return $meta_val;
		} // END function update_locationq_meta_hold( $post_ID )		 
		function update_locationq_meta_item( $post_ID ) {
			$post		= get_post( sanitize_text_field( $post_ID ) );
			$post_meta	= get_post_meta( sanitize_text_field( $post_ID ) );
			if( $post->post_status == 'auto-draft' ) {
				return false;
			} // END if( $post->post_status == 'auto-draft' )
			$meta_arr = [];
			foreach( [ 'reenable', 'item_name', 'item_manuf', 'item_model', 'item_serial', 'item_notes', 'item_length','mark_unavailable', 'unavail_status', 'return_notes' ] as $key ) {
				$meta_val = ( empty( $_REQUEST[$key] ) ) ? null : sanitize_text_field( $_REQUEST[$key] );
				update_post_meta( $post_ID, $key, $meta_val );
				$meta_arr[$key] = $meta_val;
			} // END foreach( [ 'name', 'card' ] as $key )
			return $meta_val;
		} // END function update_locationq_meta_hold( $post_ID )		 
		function waiting_list_orderby( $query ) {
			if( !is_admin() && !$query->is_main_query() && !in_array( $query->get('post_type'), [ 'lendingq_stock','lendingq_hold' ] ) ) {
				return false;
			}
			$orderby = $query->get( 'orderby');
			
			if( 'waiting_date' == $orderby ) {
				$query->set('meta_key','waiting_date');
				$query->set('orderby','meta_value_num');
			}
			
			if( 'item_name' == $orderby ) {
				$query->set('meta_key','item_name');
				$query->set('orderby','meta_value');
			}
		
			return $query;
		}
		function widget_dashboard_add() {
			global $wp_meta_boxes;
			wp_add_dashboard_widget('lendingq_widget', 'LendingQ Quick View', [ $this, 'widget_display' ] );
		} // END function widget_dashboard_add() 
		function widget_display() {
			require( LENDINGQ_PATH . '/template_quick_widget.php' );
		} // END function widget_display() 
	} // END class lendingq
} // END if( !class_exists( "lendingq" ) )		 
$lendingq = new lendingq;
if( !function_exists( "preme" ) ) {
	function preme( $arr="-----------------+=+-----------------" ) // print_array
	{
		if( $arr === TRUE )	$arr = "**TRUE**";
		if( $arr === FALSE )	$arr = "**FALSE**";
		if( $arr === NULL )	$arr = "**NULL**";
		echo "<pre>";
		print_r( $arr );
		echo "</pre>";
	}
}