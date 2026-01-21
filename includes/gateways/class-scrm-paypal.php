<?php
/**
 * PayPal Payment Gateway
 *
 * @package SyncPointCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Class PayPal
 *
 * PayPal API integration.
 *
 * @since 1.0.0
 */
class PayPal extends Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'paypal';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = 'PayPal';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = 'Sync transactions from PayPal and accept payments.';

	/**
	 * Get settings fields.
	 *
	 * @return array Settings fields.
	 */
	public function get_settings_fields() {
		return array(
			'enabled'             => array(
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayPal', 'syncpoint-crm' ),
				'default' => false,
			),
			'mode'                => array(
				'type'    => 'select',
				'label'   => __( 'Mode', 'syncpoint-crm' ),
				'options' => array(
					'sandbox' => __( 'Sandbox', 'syncpoint-crm' ),
					'live'    => __( 'Live', 'syncpoint-crm' ),
				),
				'default' => 'sandbox',
			),
			'client_id'           => array(
				'type'  => 'text',
				'label' => __( 'Client ID', 'syncpoint-crm' ),
			),
			'client_secret'       => array(
				'type'  => 'password',
				'label' => __( 'Client Secret', 'syncpoint-crm' ),
			),
			'nvp_username'        => array(
				'type'        => 'text',
				'label'       => __( 'NVP API Username', 'syncpoint-crm' ),
				'description' => __( 'For historical transaction import.', 'syncpoint-crm' ),
			),
			'nvp_password'        => array(
				'type'  => 'password',
				'label' => __( 'NVP API Password', 'syncpoint-crm' ),
			),
			'nvp_signature'       => array(
				'type'  => 'password',
				'label' => __( 'NVP API Signature', 'syncpoint-crm' ),
			),
			'skip_contact_update' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Skip Contact Update', 'syncpoint-crm' ),
				'description' => __( 'If checked, existing contacts will not be updated during sync.', 'syncpoint-crm' ),
				'default'     => false,
			),
			'custom_tags'         => array(
				'type'        => 'text',
				'label'       => __( 'Custom Tags', 'syncpoint-crm' ),
				'description' => __( 'Comma-separated tags to apply to synced contacts.', 'syncpoint-crm' ),
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
		return ! empty( $credentials['enabled'] )
			&& ! empty( $credentials['client_id'] )
			&& ! empty( $credentials['client_secret'] );
	}

	/**
	 * Check if NVP API is available.
	 *
	 * @return bool
	 */
	public function is_nvp_available() {
		$credentials = $this->get_credentials();
		return ! empty( $credentials['enabled'] )
			&& ! empty( $credentials['nvp_username'] )
			&& ! empty( $credentials['nvp_password'] )
			&& ! empty( $credentials['nvp_signature'] );
	}

	/**
	 * Get API base URL.
	 *
	 * @return string
	 */
	private function get_api_url() {
		$credentials = $this->get_credentials();
		$mode        = $credentials['mode'] ?? 'sandbox';

		return 'sandbox' === $mode
			? 'https://api-m.sandbox.paypal.com'
			: 'https://api-m.paypal.com';
	}

	/**
	 * Get NVP API URL.
	 *
	 * @return string
	 */
	private function get_nvp_url() {
		$credentials = $this->get_credentials();
		$mode        = $credentials['mode'] ?? 'sandbox';

		return 'sandbox' === $mode
			? 'https://api-3t.sandbox.paypal.com/nvp'
			: 'https://api-3t.paypal.com/nvp';
	}

	/**
	 * Get access token.
	 *
	 * @return string|\WP_Error
	 */
	private function get_access_token() {
		$cached = get_transient( 'scrm_paypal_access_token' );
		if ( $cached ) {
			return $cached;
		}

		$credentials = $this->get_credentials();
		$client_id   = $credentials['client_id'] ?? '';
		$secret      = $credentials['client_secret'] ?? '';

		$response = wp_remote_post(
			$this->get_api_url() . '/v1/oauth2/token',
			array(
				'headers' => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
				),
				'body'    => array(
					'grant_type' => 'client_credentials',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			return new \WP_Error( 'paypal_auth_failed', __( 'Failed to authenticate with PayPal.', 'syncpoint-crm' ) );
		}

		$expires = ( $body['expires_in'] ?? 3600 ) - 60;
		set_transient( 'scrm_paypal_access_token', $body['access_token'], $expires );

		return $body['access_token'];
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint Endpoint.
	 * @param array  $args     Arguments.
	 * @return array|\WP_Error
	 */
	protected function api_request( $endpoint, $args = array() ) {
		$token = $this->get_access_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$defaults = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		$args = wp_parse_args( $args, $defaults );
		$url  = $this->get_api_url() . $endpoint;

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log( 'API error', array( 'endpoint' => $endpoint, 'error' => $response->get_error_message() ) );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$message = $body['message'] ?? __( 'API request failed.', 'syncpoint-crm' );
			return new \WP_Error( 'paypal_api_error', $message );
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
			'start_date' => gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-30 days' ) ),
			'end_date'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'page_size'  => 100,
		);

		$args     = wp_parse_args( $args, $defaults );
		$endpoint = '/v1/reporting/transactions?' . http_build_query( array(
			'start_date' => $args['start_date'],
			'end_date'   => $args['end_date'],
			'page_size'  => $args['page_size'],
			'fields'     => 'all',
		) );

		$response = $this->api_request( $endpoint );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$transactions   = $response['transaction_details'] ?? array();
		$synced         = 0;
		$skipped        = 0;
		$contacts_added = 0;

		foreach ( $transactions as $txn ) {
			$result = $this->process_transaction( $txn );

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
			'total'          => count( $transactions ),
		);
	}

	/**
	 * Sync transactions using NVP API.
	 *
	 * @param array $args Arguments.
	 * @return array|\WP_Error
	 */
	public function sync_transactions_nvp( $args = array() ) {
		if ( ! $this->is_nvp_available() ) {
			return new \WP_Error( 'nvp_not_configured', __( 'PayPal NVP API credentials are not configured.', 'syncpoint-crm' ) );
		}

		$credentials = $this->get_credentials();

		$defaults = array(
			'start_date' => gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-1 year' ) ),
			'end_date'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$request_data = array(
			'METHOD'    => 'TransactionSearch',
			'VERSION'   => '124.0',
			'USER'      => $credentials['nvp_username'],
			'PWD'       => $credentials['nvp_password'],
			'SIGNATURE' => $credentials['nvp_signature'],
			'STARTDATE' => $args['start_date'],
			'ENDDATE'   => $args['end_date'],
			'STATUS'    => 'Success',
		);

		$response = wp_remote_post(
			$this->get_nvp_url(),
			array(
				'body'    => $request_data,
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		parse_str( wp_remote_retrieve_body( $response ), $result );

		if ( 'Success' !== ( $result['ACK'] ?? '' ) && 'SuccessWithWarning' !== ( $result['ACK'] ?? '' ) ) {
			$error_msg = $result['L_LONGMESSAGE0'] ?? __( 'Unknown PayPal NVP error.', 'syncpoint-crm' );
			return new \WP_Error( 'nvp_error', $error_msg );
		}

		$synced         = 0;
		$skipped        = 0;
		$contacts_added = 0;
		$i              = 0;

		while ( isset( $result[ 'L_TRANSACTIONID' . $i ] ) ) {
			$txn_data = array(
				'transaction_id' => $result[ 'L_TRANSACTIONID' . $i ] ?? '',
				'email'          => $result[ 'L_EMAIL' . $i ] ?? '',
				'name'           => $result[ 'L_NAME' . $i ] ?? '',
				'amount'         => floatval( $result[ 'L_AMT' . $i ] ?? 0 ),
				'currency'       => $result[ 'L_CURRENCYCODE' . $i ] ?? 'USD',
				'status'         => $result[ 'L_STATUS' . $i ] ?? '',
				'type'           => $result[ 'L_TYPE' . $i ] ?? '',
				'timestamp'      => $result[ 'L_TIMESTAMP' . $i ] ?? '',
			);

			$process_result = $this->process_nvp_transaction( $txn_data );

			if ( is_wp_error( $process_result ) ) {
				$skipped++;
			} elseif ( 'contact_created' === $process_result ) {
				$synced++;
				$contacts_added++;
			} elseif ( true === $process_result ) {
				$synced++;
			} else {
				$skipped++;
			}

			$i++;
		}

		return array(
			'synced'         => $synced,
			'skipped'        => $skipped,
			'contacts_added' => $contacts_added,
			'total'          => $i,
		);
	}

	/**
	 * Process a single transaction from REST API.
	 *
	 * @param array $txn Transaction data.
	 * @return bool|string|\WP_Error
	 */
	private function process_transaction( $txn ) {
		$txn_info = $txn['transaction_info'] ?? array();
		$txn_id   = $txn_info['transaction_id'] ?? '';

		if ( empty( $txn_id ) ) {
			return new \WP_Error( 'missing_id', 'Missing transaction ID.' );
		}

		$status = $txn_info['transaction_status'] ?? '';
		if ( 'S' !== $status ) {
			return 'skipped';
		}

		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'paypal' AND gateway_transaction_id = %s",
			$txn_id
		) );

		if ( $existing ) {
			return 'exists';
		}

		$payer_info = $txn['payer_info'] ?? array();
		$email      = $payer_info['email_address'] ?? '';

		if ( empty( $email ) ) {
			return new \WP_Error( 'no_email', 'No payer email.' );
		}

		$contact         = scrm_get_contact_by_email( $email );
		$contact_created = false;

		if ( ! $contact ) {
			$name_parts = explode( ' ', $payer_info['payer_name']['given_name'] ?? '', 2 );

			$contact_id = scrm_create_contact( array(
				'email'      => $email,
				'first_name' => $payer_info['payer_name']['given_name'] ?? '',
				'last_name'  => $payer_info['payer_name']['surname'] ?? '',
				'type'       => 'customer',
				'source'     => 'paypal',
			) );

			if ( is_wp_error( $contact_id ) ) {
				return $contact_id;
			}

			$contact         = scrm_get_contact( $contact_id );
			$contact_created = true;
		}

		$amount   = floatval( $txn_info['transaction_amount']['value'] ?? 0 );
		$currency = strtoupper( $txn_info['transaction_amount']['currency_code'] ?? 'USD' );

		scrm_create_transaction( array(
			'contact_id'             => $contact->id,
			'type'                   => 'payment',
			'gateway'                => 'paypal',
			'gateway_transaction_id' => $txn_id,
			'amount'                 => $amount,
			'currency'               => $currency,
			'status'                 => 'completed',
			'description'            => $txn_info['transaction_subject'] ?? '',
		) );

		return $contact_created ? 'contact_created' : true;
	}

	/**
	 * Process a single NVP transaction.
	 *
	 * @param array $txn Transaction data.
	 * @return bool|string|\WP_Error
	 */
	private function process_nvp_transaction( $txn ) {
		$txn_id = $txn['transaction_id'] ?? '';

		if ( empty( $txn_id ) ) {
			return new \WP_Error( 'missing_id', 'Missing transaction ID.' );
		}

		if ( 'Completed' !== $txn['status'] ) {
			return 'skipped';
		}

		if ( $txn['amount'] < 0 ) {
			return 'skipped';
		}

		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'paypal' AND gateway_transaction_id = %s",
			$txn_id
		) );

		if ( $existing ) {
			return 'exists';
		}

		$email = $txn['email'] ?? '';

		if ( empty( $email ) ) {
			return new \WP_Error( 'no_email', 'No payer email.' );
		}

		$contact         = scrm_get_contact_by_email( $email );
		$contact_created = false;

		if ( ! $contact ) {
			$name_parts = explode( ' ', $txn['name'] ?? '', 2 );

			$contact_id = scrm_create_contact( array(
				'email'      => $email,
				'first_name' => $name_parts[0] ?? '',
				'last_name'  => $name_parts[1] ?? '',
				'type'       => 'customer',
				'source'     => 'paypal',
			) );

			if ( is_wp_error( $contact_id ) ) {
				return $contact_id;
			}

			$contact         = scrm_get_contact( $contact_id );
			$contact_created = true;
		}

		scrm_create_transaction( array(
			'contact_id'             => $contact->id,
			'type'                   => 'payment',
			'gateway'                => 'paypal',
			'gateway_transaction_id' => $txn_id,
			'amount'                 => $txn['amount'],
			'currency'               => $txn['currency'],
			'status'                 => 'completed',
		) );

		return $contact_created ? 'contact_created' : true;
	}

	/**
	 * Create payment link for invoice.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @return string|\WP_Error
	 */
	public function create_payment_link( $invoice ) {
		return new \WP_Error( 'not_implemented', __( 'PayPal payment links are not yet implemented.', 'syncpoint-crm' ) );
	}

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error
	 */
	public function process_webhook( $payload ) {
		$event_type = $payload['event_type'] ?? '';

		$this->log( 'Webhook received', array( 'event_type' => $event_type ) );

		return true;
	}
}
