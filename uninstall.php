<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}darkshield_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}darkshield_scan_results" );

delete_option( 'darkshield_mode' );
delete_option( 'darkshield_settings' );
delete_option( 'darkshield_whitelist' );
delete_option( 'darkshield_allowed_services' );
delete_option( 'darkshield_version' );
delete_option( 'darkshield_last_scan' );

delete_transient( 'darkshield_activation_notice' );

wp_clear_scheduled_hook( 'darkshield_daily_cleanup' );
