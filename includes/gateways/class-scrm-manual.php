<?php
/**
 * Manual Payment Gateway
 *
 * @package SyncPointCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manual
 *
 * Manual payment gateway for offline transactions.
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
	public $description = 'Manually entered transactions.';

	/**
	 * Get settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return array();
	}

	/**
	 * Check if gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Sync transactions.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public function sync_transactions( $args = array() ) {
		return array(
			'synced'         => 0,
			'skipped'        => 0,
			'contacts_added' => 0,
			'total'          => 0,
		);
	}

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool
	 */
	public function process_webhook( $payload ) {
		return true;
	}
}
