<?php
/**
 * Manual Payment Gateway
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manual
 *
 * Manual payment entry gateway.
 *
 * @since 1.0.0
 */
class Manual extends Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'manual';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = 'Manual';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = 'Manually record payments.';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->enabled = true; // Always enabled.
	}

	/**
	 * Get settings fields.
	 *
	 * @return array Settings fields.
	 */
	public function get_settings_fields() {
		return array(); // No settings needed.
	}

	/**
	 * Sync transactions.
	 *
	 * @param array $args Sync arguments.
	 * @return array Results.
	 */
	public function sync_transactions( $args = array() ) {
		// Nothing to sync for manual gateway.
		return array(
			'synced'  => 0,
			'skipped' => 0,
			'total'   => 0,
		);
	}

	/**
	 * Create payment link.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @return string Payment instructions.
	 */
	public function create_payment_link( $invoice ) {
		// Return invoice public URL.
		return $invoice->get_public_url();
	}

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool True.
	 */
	public function process_webhook( $payload ) {
		return true;
	}

	/**
	 * Verify webhook signature.
	 *
	 * @param string $payload   Payload.
	 * @param string $signature Signature.
	 * @return bool True.
	 */
	public function verify_webhook_signature( $payload, $signature ) {
		return true;
	}
}
