<?php
/**
 * Abstract Payment Gateway
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gateway
 *
 * Abstract base class for payment gateways.
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
	 * Whether gateway is enabled.
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Gateway mode (live/test/sandbox).
	 *
	 * @var string
	 */
	public $mode = 'test';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_settings();
	}

	/**
	 * Initialize settings.
	 */
	protected function init_settings() {
		$settings = scrm_get_settings( $this->id );

		$this->enabled = ! empty( $settings['enabled'] );
		$this->mode    = $settings['mode'] ?? 'test';
	}

	/**
	 * Check if gateway is available.
	 *
	 * @return bool True if available.
	 */
	public function is_available() {
		return $this->enabled;
	}

	/**
	 * Check if in test/sandbox mode.
	 *
	 * @return bool True if in test mode.
	 */
	public function is_test_mode() {
		return in_array( $this->mode, array( 'test', 'sandbox' ), true );
	}

	/**
	 * Get gateway settings fields.
	 *
	 * @return array Settings fields.
	 */
	abstract public function get_settings_fields();

	/**
	 * Sync transactions from gateway.
	 *
	 * @param array $args Sync arguments.
	 * @return array|\WP_Error Sync results or error.
	 */
	abstract public function sync_transactions( $args = array() );

	/**
	 * Create payment link for invoice.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @return string|\WP_Error Payment link or error.
	 */
	abstract public function create_payment_link( $invoice );

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error True on success or error.
	 */
	abstract public function process_webhook( $payload );

	/**
	 * Verify webhook signature.
	 *
	 * @param string $payload   Raw payload.
	 * @param string $signature Signature header.
	 * @return bool True if valid.
	 */
	abstract public function verify_webhook_signature( $payload, $signature );

	/**
	 * Get API credentials.
	 *
	 * @return array API credentials.
	 */
	protected function get_credentials() {
		return scrm_get_settings( $this->id );
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $args     Request arguments.
	 * @return array|\WP_Error Response or error.
	 */
	protected function api_request( $endpoint, $args = array() ) {
		// Override in child classes.
		return new \WP_Error( 'not_implemented', __( 'API request not implemented.', 'syncpoint-crm' ) );
	}

	/**
	 * Log gateway event.
	 *
	 * @param string $message Log message.
	 * @param array  $data    Additional data.
	 */
	protected function log( $message, $data = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Starter CRM - %s] %s: %s',
				strtoupper( $this->id ),
				$message,
				wp_json_encode( $data )
			) );
		}
	}
}
