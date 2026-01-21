<?php
/**
 * Uninstall Starter CRM
 *
 * This file is called when the plugin is deleted via the WordPress admin.
 * It removes all plugin data including database tables, options, and capabilities.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if we should preserve data.
$settings = get_option( 'scrm_settings', array() );
$preserve_data = isset( $settings['general']['preserve_data_on_uninstall'] ) && $settings['general']['preserve_data_on_uninstall'];

if ( $preserve_data ) {
	// User chose to keep data, just exit.
	return;
}

global $wpdb;

/**
 * Remove all database tables.
 */
$tables = array(
	$wpdb->prefix . 'scrm_contacts',
	$wpdb->prefix . 'scrm_companies',
	$wpdb->prefix . 'scrm_transactions',
	$wpdb->prefix . 'scrm_tags',
	$wpdb->prefix . 'scrm_tag_relationships',
	$wpdb->prefix . 'scrm_invoices',
	$wpdb->prefix . 'scrm_invoice_items',
	$wpdb->prefix . 'scrm_activity_log',
	$wpdb->prefix . 'scrm_webhook_log',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Remove all options.
 */
$options = array(
	'scrm_settings',
	'scrm_version',
	'scrm_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

/**
 * Remove all transients.
 */
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	WHERE option_name LIKE '_transient_scrm_%'
	OR option_name LIKE '_transient_timeout_scrm_%'"
);

/**
 * Remove capabilities from all roles.
 */
$capabilities = array(
	'scrm_manage_contacts',
	'scrm_manage_companies',
	'scrm_manage_transactions',
	'scrm_manage_invoices',
	'scrm_manage_settings',
	'scrm_view_dashboard',
	'scrm_import_data',
	'scrm_export_data',
);

global $wp_roles;

if ( isset( $wp_roles ) ) {
	foreach ( $wp_roles->roles as $role_name => $role_info ) {
		$role = get_role( $role_name );
		if ( $role ) {
			foreach ( $capabilities as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}

/**
 * Remove scheduled events.
 */
$events = array(
	'scrm_check_overdue_invoices',
	'scrm_cleanup_webhook_logs',
	'scrm_paypal_sync',
	'scrm_stripe_sync',
);

foreach ( $events as $event ) {
	$timestamp = wp_next_scheduled( $event );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $event );
	}
}

/**
 * Remove uploaded files (invoices PDFs, etc.)
 */
$upload_dir = wp_upload_dir();
$scrm_dir = $upload_dir['basedir'] . '/starter-crm';

if ( is_dir( $scrm_dir ) ) {
	// Recursively delete directory.
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $scrm_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() );
		} else {
			unlink( $file->getRealPath() );
		}
	}

	rmdir( $scrm_dir );
}

// Clear any object cache.
wp_cache_flush();
