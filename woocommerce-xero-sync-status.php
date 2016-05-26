<?php
/*
Plugin Name: WooCommerce Xero Sync Status Column
Plugin URI: http://smithsrus.com/
Description: Add a Xero sync status column to the WooCommerce admin orders list.
Version: 1.0
Author: Doug Smith
Author URI: http://smithsrus.com/
*/

// Only proceed if WooCommerce is running.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WC_Xero_sync_status_column' ) ) {

		class WC_Xero_sync_status_column {
			public function __construct() {
				add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
			}
			
			public function plugins_loaded() {

				// Add a Xero status admin column to the orders page.
				add_filter( 'manage_edit-shop_order_columns', 'xero_admin_column', 20 );
				function xero_admin_column( $columns ) {
					return array_merge( $columns, array( 'xero_status' => 'Xero' ) );
				}
				
				// Add the Xero status admin column contents.
				add_action( 'manage_shop_order_posts_custom_column', 'xero_column_content', 10, 2 );
				function xero_column_content( $column_name, $post_id ) {
					if ( 'xero_status' != $column_name )
						return;

					global $post, $woocommerce, $the_order;

					if ( empty( $the_order ) || $the_order->id != $post->ID ) {
						$the_order = wc_get_order( $post->ID );
					}

					// Mark orders with a zero total (if configured to skip) as skipped.
					if ( ( $the_order->get_total() == 0 ) && ( get_option('wc_xero_export_zero_amount') != 'on' ) ) {
						echo '<mark class="skipped_zero tips" data-tip="$0 order sync skipped">skipped</mark>';
					}

					// Mark orders with non-syncing status as skipped.
					if ( in_array($the_order->get_status(), array( 'failed','cancelled' ) ) ) {
						printf( '<mark class="skipped_status tips" data-tip="%s order status, sync skipped">Skipped</mark>', wc_get_order_status_name( $the_order->get_status() ) );
					}
				
					// Show status for invoice.
					$xero_invoice = get_post_meta($post_id, '_xero_invoice_id', true);
					if( $xero_invoice != '' ) {
						echo '<mark class="synced_invoice tips" data-tip="Invoice synced">invoice</mark>';
					}
				
					// Show status for payment.
					$xero_payment = get_post_meta($post_id, '_xero_payment_id', true);
					if( $xero_payment != '' ) {
						echo '<mark class="synced_payment tips" data-tip="Payment synced">payment</mark>';
					}

					// TODO: Add status for synced_refund.

					// TODO: Add status for sync_error.

					wp_enqueue_style( 'woocommerce-xero-sync-status', plugins_url('assets/css/woocommerce-xero-sync-status.css', __FILE__) );
				}  

			}
			
		}

		// Instantiate our plugin class and add it to the set of globals.
		$GLOBALS['WC_Xero_sync_status_column'] = new WC_Xero_sync_status_column();
	}
}

