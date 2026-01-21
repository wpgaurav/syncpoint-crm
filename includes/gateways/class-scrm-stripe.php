<?php
/**
 * Stripe Payment Gateway
 *
 * @package SyncPointCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Class Stripe
 *
 * Stripe API integration.
 *
 * @since 1.0.0
 */
class Stripe extends Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'stripe';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = 'Stripe';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = 'Accept payments and sync transactions from Stripe.';

	/**
	 * Get settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'enabled'          => array(
				'type'    => 'checkbox',
				'label'   => __( 'Enable Stripe', 'syncpoint-crm' ),
				'default' => false,
			),
			'mode'             => array(
				'type'    => 'select',
				'label'   => __( 'Mode', 'syncpoint-crm' ),
				'options' => array(
					'test' => __( 'Test', 'syncpoint-crm' ),
					'live' => __( 'Live', 'syncpoint-crm' ),
				),
				'default' => 'test',
			),
			'test_publishable' => array(
				'type'  => 'text',
				'label' => __( 'Test Publishable Key', 'syncpoint-crm' ),
			),
			'test_secret'      => array(
				'type'  => 'password',
				'label' => __( 'Test Secret Key', 'syncpoint-crm' ),
			),
			'live_publishable' => array(
				'type'  => 'text',
				'label' => __( 'Live Publishable Key', 'syncpoint-crm' ),
			),
			'live_secret'      => array(
				'type'  => 'password',
				'label' => __( 'Live Secret Key', 'syncpoint-crm' ),
			),
			'webhook_secret'   => array(
				'type'  => 'password',
				'label' => __( 'Webhook Signing Secret', 'syncpoint-crm' ),
			),
		);
	}

	/**
	 * Check if gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		$credentials = $this->get_credentials();
		
		if ( empty( $credentials['enabled'] ) ) {
			return false;
		}

		$mode = $credentials['mode'] ?? 'test';

		if ( 'live' === $mode ) {
			return ! empty( $credentials['live_secret'] );
		}

		return ! empty( $credentials['test_secret'] );
	}

	/**
	 * Get the secret key based on mode.
	 *
	 * @return string
	 */
	private function get_secret_key() {
		$credentials = $this->get_credentials();
		$mode        = $credentials['mode'] ?? 'test';

		return 'live' === $mode
			? ( $credentials['live_secret'] ?? '' )
			: ( $credentials['test_secret'] ?? '' );
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $args     Arguments.
	 * @return array|\WP_Error
	 */
	protected function api_request( $endpoint, $args = array() ) {
		$secret_key = $this->get_secret_key();

		if ( empty( $secret_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'Stripe API key is not configured.', 'syncpoint-crm' ) );
		}

		$defaults = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $secret_key,
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'timeout' => 30,
		);

		$args = wp_parse_args( $args, $defaults );
		$url  = 'https://api.stripe.com/v1' . $endpoint;

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log( 'API error', array( 'endpoint' => $endpoint, 'error' => $response->get_error_message() ) );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$message = $body['error']['message'] ?? __( 'Stripe API request failed.', 'syncpoint-crm' );
			return new \WP_Error( 'stripe_api_error', $message );
		}

		return $body;
	}

	/**
	 * Sync transactions.
	 *
	 * @param array $args Arguments.
	 * @return array|\WP_Error
	 */
	public function sync_transactions( $args = array() ) {
		$defaults = array(
			'limit'      => 100,
			'created_gt' => strtotime( '-30 days' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$query = http_build_query( array(
			'limit'       => $args['limit'],
			'created[gt]' => $args['created_gt'],
		) );

		$response = $this->api_request( '/charges?' . $query );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$charges        = $response['data'] ?? array();
		$synced         = 0;
		$skipped        = 0;
		$contacts_added = 0;

		foreach ( $charges as $charge ) {
			$result = $this->process_charge( $charge );

			if ( is_wp_error( $result ) ) {
				$skipped++;
			} elseif ( 'contact_created' === $result ) {
				$synced++;
				$contacts_added++;
			} elseif ( true === $result ) {
				$synced++;
			} else {
				$skipped++;
			}
		}

		return array(
			'synced'         => $synced,
			'skipped'        => $skipped,
			'contacts_added' => $contacts_added,
			'total'          => count( $charges ),
		);
	}

	/**
	 * Process a single charge.
	 *
	 * @param array $charge Stripe charge data.
	 * @return bool|string|\WP_Error
	 */
	private function process_charge( $charge ) {
		$charge_id = $charge['id'] ?? '';

		if ( empty( $charge_id ) ) {
			return new \WP_Error( 'missing_id', 'Missing charge ID.' );
		}

		if ( 'succeeded' !== ( $charge['status'] ?? '' ) ) {
			return 'skipped';
		}

		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'stripe' AND gateway_transaction_id = %s",
			$charge_id
		) );

		if ( $existing ) {
			return 'exists';
		}

		$email = $charge['billing_details']['email'] ?? $charge['receipt_email'] ?? '';

		if ( empty( $email ) ) {
			return new \WP_Error( 'no_email', 'No customer email.' );
		}

		$contact         = scrm_get_contact_by_email( $email );
		$contact_created = false;

		if ( ! $contact ) {
			$name       = $charge['billing_details']['name'] ?? '';
			$name_parts = explode( ' ', $name, 2 );

			$contact_id = scrm_create_contact( array(
				'email'      => $email,
				'first_name' => $name_parts[0] ?? '',
				'last_name'  => $name_parts[1] ?? '',
				'type'       => 'customer',
				'source'     => 'stripe',
			) );

			if ( is_wp_error( $contact_id ) ) {
				return $contact_id;
			}

			$contact         = scrm_get_contact( $contact_id );
			$contact_created = true;
		}

		$amount   = ( $charge['amount'] ?? 0 ) / 100;
		$currency = strtoupper( $charge['currency'] ?? 'usd' );

		scrm_create_transaction( array(
			'contact_id'             => $contact->id,
			'type'                   => 'payment',
			'gateway'                => 'stripe',
			'gateway_transaction_id' => $charge_id,
			'amount'                 => $amount,
			'currency'               => $currency,
			'status'                 => 'completed',
			'description'            => $charge['description'] ?? '',
		) );

		return $contact_created ? 'contact_created' : true;
	}

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error
	 */
	public function process_webhook( $payload ) {
		$event_type = $payload['type'] ?? '';

		$this->log( 'Webhook received', array( 'event_type' => $event_type ) );

		switch ( $event_type ) {
			case 'charge.succeeded':
				$charge = $payload['data']['object'] ?? array();
				return $this->process_charge( $charge );

			case 'charge.refunded':
				// Handle refund.
				break;
		}

		return true;
	}
}
