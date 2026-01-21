<?php
/**
 * Cron Jobs Handler
 *
 * Handles scheduled tasks.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Cron
 *
 * @since 1.0.0
 */
class SCRM_Cron {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'scrm_daily_tasks', array( $this, 'run_daily_tasks' ) );
		add_action( 'scrm_weekly_tasks', array( $this, 'run_weekly_tasks' ) );
		add_action( 'scrm_check_overdue_invoices', array( $this, 'check_overdue_invoices' ) );
		add_action( 'scrm_cleanup_webhook_logs', array( $this, 'cleanup_webhook_log' ) );

		// Gateway sync cron handlers.
		add_action( 'scrm_paypal_sync', array( $this, 'run_paypal_sync' ) );
		add_action( 'scrm_stripe_sync', array( $this, 'run_stripe_sync' ) );
	}

	/**
	 * Run daily tasks.
	 */
	public function run_daily_tasks() {
		$this->check_overdue_invoices();
		$this->send_invoice_reminders();
		$this->cleanup_temp_files();
	}

	/**
	 * Run weekly tasks.
	 */
	public function run_weekly_tasks() {
		$this->cleanup_webhook_log();
		$this->cleanup_activity_log();
	}

	/**
	 * Check for overdue invoices.
	 */
	private function check_overdue_invoices() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		// Find invoices that just became overdue.
		$overdue = $wpdb->get_results( $wpdb->prepare(
			"SELECT id FROM {$table}
			WHERE status IN ('sent', 'viewed')
			AND due_date < %s
			AND due_date >= %s",
			current_time( 'Y-m-d' ),
			date( 'Y-m-d', strtotime( '-1 day' ) )
		) );

		foreach ( $overdue as $row ) {
			do_action( 'scrm_invoice_overdue', $row->id );

			scrm_log_activity( 'invoice', $row->id, 'became_overdue', __( 'Invoice is now overdue.', 'syncpoint-crm' ) );
		}
	}

	/**
	 * Send invoice reminders.
	 */
	private function send_invoice_reminders() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_invoices';

		// Find invoices due in 3 days that haven't been reminded.
		$upcoming = $wpdb->get_results( $wpdb->prepare(
			"SELECT id FROM {$table}
			WHERE status IN ('sent', 'viewed')
			AND due_date = %s",
			date( 'Y-m-d', strtotime( '+3 days' ) )
		) );

		foreach ( $upcoming as $row ) {
			$invoice = new SCRM\Core\Invoice( $row->id );

			if ( $invoice->exists() ) {
				SCRM\Utils\Emails::send_reminder( $invoice );
			}
		}

		// Send reminders for overdue invoices (every 7 days).
		$overdue = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, due_date FROM {$table}
			WHERE status IN ('sent', 'viewed')
			AND due_date < %s",
			current_time( 'Y-m-d' )
		) );

		foreach ( $overdue as $row ) {
			$days_overdue = floor( ( strtotime( 'today' ) - strtotime( $row->due_date ) ) / DAY_IN_SECONDS );

			// Send reminder every 7 days for first month.
			if ( $days_overdue % 7 === 0 && $days_overdue <= 28 ) {
				$invoice = new SCRM\Core\Invoice( $row->id );

				if ( $invoice->exists() ) {
					SCRM\Utils\Emails::send_reminder( $invoice );
				}
			}
		}
	}

	/**
	 * Cleanup temporary files.
	 */
	private function cleanup_temp_files() {
		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/starter-crm/temp';

		if ( ! is_dir( $temp_dir ) ) {
			return;
		}

		$files = glob( $temp_dir . '/*' );
		$now = time();

		foreach ( $files as $file ) {
			if ( is_file( $file ) && ( $now - filemtime( $file ) ) > DAY_IN_SECONDS ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Cleanup webhook log.
	 */
	private function cleanup_webhook_log() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_webhook_log';

		// Delete logs older than 30 days.
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
		) );
	}

	/**
	 * Cleanup activity log.
	 */
	private function cleanup_activity_log() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_activity_log';

		// Delete logs older than 90 days.
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE created_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-90 days' ) )
		) );
	}

	/**
	 * Run PayPal transaction sync.
	 */
	public function run_paypal_sync() {
		$settings = scrm_get_settings( 'paypal' );

		if ( empty( $settings['enabled'] ) || empty( $settings['auto_sync'] ) ) {
			return;
		}

		if ( scrm_is_sync_running( 'paypal' ) ) {
			return;
		}

		$log_id = scrm_start_sync_log( 'paypal', 'cron' );

		$gateway = new SCRM\Gateways\PayPal();

		if ( ! $gateway->is_available() ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, __( 'PayPal is not available.', 'syncpoint-crm' ) );
			return;
		}

		$results = $gateway->sync_transactions();

		if ( is_wp_error( $results ) ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $results->get_error_message() );
			return;
		}

		scrm_complete_sync_log(
			$log_id,
			'completed',
			$results['synced'] ?? 0,
			$results['skipped'] ?? 0,
			$results['contacts_added'] ?? 0
		);

		do_action( 'scrm_cron_paypal_sync_completed', $results );
	}

	/**
	 * Run Stripe transaction sync.
	 */
	public function run_stripe_sync() {
		$settings = scrm_get_settings( 'stripe' );

		if ( empty( $settings['enabled'] ) || empty( $settings['auto_sync'] ) ) {
			return;
		}

		if ( scrm_is_sync_running( 'stripe' ) ) {
			return;
		}

		$log_id = scrm_start_sync_log( 'stripe', 'cron' );

		$gateway = new SCRM\Gateways\Stripe();

		if ( ! $gateway->is_available() ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, __( 'Stripe is not available.', 'syncpoint-crm' ) );
			return;
		}

		$results = $gateway->sync_transactions();

		if ( is_wp_error( $results ) ) {
			scrm_complete_sync_log( $log_id, 'failed', 0, 0, 0, $results->get_error_message() );
			return;
		}

		scrm_complete_sync_log(
			$log_id,
			'completed',
			$results['synced'] ?? 0,
			$results['skipped'] ?? 0,
			$results['contacts_added'] ?? 0
		);

		do_action( 'scrm_cron_stripe_sync_completed', $results );
	}

	/**
	 * Cleanup sync logs.
	 */
	public function cleanup_sync_log() {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_sync_log';

		// Delete logs older than 30 days.
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE started_at < %s",
			date( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
		) );
	}
}

// Initialize.
new SCRM_Cron();
