<?php
/**
 * Uninstall SyncPoint CRM
 *
 * This file is called when the plugin is deleted via the WordPress admin.
 * It removes all plugin data including database tables, options, and capabilities.
 *
 * @package SyncPointCRM
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Always clear pending actions (imports, sync flags, etc.).
 */
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_scrm_%' OR option_name LIKE '_transient_timeout_scrm_%'"
);

/**
 * Remove temp import files.
 */
$upload_dir = wp_upload_dir();
$temp_dir   = trailingslashit( $upload_dir['basedir'] ) . 'starter-crm/temp';

if ( is_dir( $temp_dir ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $temp_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			@rmdir( $file->getRealPath() );
		} else {
			@unlink( $file->getRealPath() );
		}
	}
	@rmdir( $temp_dir );
}

// Check if we should delete data.
$settings    = get_option( 'scrm_settings', array() );
$delete_data = ! empty( $settings['general']['delete_data_on_uninstall'] );
$delete_data = apply_filters( 'scrm_delete_data_on_uninstall', $delete_data, $settings );

if ( ! $delete_data ) {
	return;
}

/**
 * Remove all database tables.
 */
$tables = array(
	'scrm_contacts',
	'scrm_companies',
	'scrm_transactions',
	'scrm_invoices',
	'scrm_invoice_items',
	'scrm_tags',
	'scrm_tag_relationships',
	'scrm_activity_log',
	'scrm_webhook_log',
	'scrm_sync_log',
	'scrm_email_log',
);

foreach ( $tables as $table ) {
	$full_table = $wpdb->prefix . $table;
	$wpdb->query( "DROP TABLE IF EXISTS {$full_table}" );
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
 * Remove uploaded files (invoices PDFs, exports, etc.)
 */
$upload_dir  = wp_upload_dir();
$directories = array(
	$upload_dir['basedir'] . '/syncpoint-crm',
	$upload_dir['basedir'] . '/scrm-exports',
);

foreach ( $directories as $scrm_dir ) {
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
}

// Clear any object cache.
wp_cache_flush();
