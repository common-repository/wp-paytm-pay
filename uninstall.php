<?php
/**
 * Wp Paytm Pay Uninstall
 *
 * Uninstalling Wp Paytm Pay tables and options.
 *
 * @author      Alpesh Joshi
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $wp_version;

// Tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paytm_donation" );

// Clear any cached data that has been removed

wp_cache_flush();

