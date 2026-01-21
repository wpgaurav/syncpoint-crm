<?php
/**
 * Abstract Payment Gateway
 *
 * @package SyncPointCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Class Gateway
 *
 * Base class for all payment gateways.
 *
 * @since 1.0.0
 */
abstract class Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Get gateway credentials/settings.
	 *
	 * @return array
	 */
	public function get_credentials() {
		return scrm_get_settings( $this->id );
	}

	/**
	 * Get gateway settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return scrm_get_settings( $this->id );
	}

	/**
	 * Check if gateway is in test mode.
	 *
	 * @return bool
	 */
	public function is_test_mode() {
		$credentials = $this->get_credentials();
		$mode        = $credentials['mode'] ?? 'sandbox';
		return in_array( $mode, array( 'sandbox', 'test' ), true );
	}

	/**
	 * Get settings fields.
	 *
	 * @return array
	 */
	abstract public function get_settings_fields();

	/**
	 * Check if gateway is available.
	 *
	 * @return bool
	 */
	abstract public function is_available();

	/**
	 * Sync transactions.
	 *
	 * @param array $args Arguments.
	 * @return array|\WP_Error
	 */
	abstract public function sync_transactions( $args = array() );

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error
	 */
	abstract public function process_webhook( $payload );

	/**
	 * Log gateway activity.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	protected function log( $message, $context = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[SCRM %s] %s: %s',
				strtoupper( $this->id ),
				$message,
				wp_json_encode( $context )
			) );
		}
	}
}
