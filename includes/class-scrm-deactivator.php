<?php
/**
 * Plugin Deactivator
 *
 * Handles plugin deactivation cleanup.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Deactivator
 *
 * @since 1.0.0
 */
class SCRM_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * This method is called when the plugin is deactivated.
	 * It cleans up scheduled events but does NOT delete data.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		self::unschedule_events();
		self::clear_cache();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Unschedule cron events.
	 *
	 * @since 1.0.0
	 */
	private static function unschedule_events() {
		// Clear scheduled events.
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
	}

	/**
	 * Clear all plugin transients.
	 *
	 * @since 1.0.0
	 */
	private static function clear_cache() {
		global $wpdb;

		// Delete all transients with our prefix.
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_scrm_%'
			OR option_name LIKE '_transient_timeout_scrm_%'"
		);
	}
}
