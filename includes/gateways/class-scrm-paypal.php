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
	 * Check if NVP API credentials are configured.
	 *
	 * @return bool
	 */
	public function is_nvp_available() {
		$settings = $this->get_settings();

		// Check for NVP-specific credentials (api_username, api_password, api_signature)
		$username  = $settings['api_username'] ?? '';
		$password  = $settings['api_password'] ?? '';
		$signature = $settings['api_signature'] ?? '';

		return ! empty( $username ) && ! empty( $password ) && ! empty( $signature );
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
	 * Get NVP API URL based on mode.
	 *
	 * @return string
	 */
	protected function get_nvp_url() {
		$settings = $this->get_settings();
		$mode     = $settings['mode'] ?? 'sandbox';

		if ( 'live' === $mode ) {
			return 'https://api-3t.paypal.com/nvp';
		}

		return 'https://api-3t.sandbox.paypal.com/nvp';
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
			$this->log(
				'API error',
				array(
					'endpoint' => $endpoint,
					'error'    => $response->get_error_message(),
				)
			);
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
			'start_date' => gmgmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-30 days' ) ),
			'end_date'   => gmgmdate( 'Y-m-d\TH:i:s\Z' ),
			'page_size'  => 100,
		);

		$args     = wp_parse_args( $args, $defaults );
		$endpoint = '/v1/reporting/transactions?' . http_build_query(
			array(
				'start_date' => $args['start_date'],
				'end_date'   => $args['end_date'],
				'page_size'  => $args['page_size'],
				'fields'     => 'all',
			)
		);

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
				++$skipped;
			} elseif ( 'contact_created' === $result ) {
				++$synced;
				++$contacts_added;
			} elseif ( true === $result ) {
				++$synced;
			} else {
				++$skipped;
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
			return new \WP_Error(
				'nvp_not_configured',
				__( 'PayPal NVP API credentials are not configured. Please enter your API Username, Password, and Signature in the PayPal Import tab.', 'syncpoint-crm' )
			);
		}

		$settings = $this->get_settings();

		// Get NVP credentials
		$nvp_username  = $settings['api_username'] ?? '';
		$nvp_password  = $settings['api_password'] ?? '';
		$nvp_signature = $settings['api_signature'] ?? '';

		// Get date range from settings
		$start_date_setting = $settings['first_txn_date'] ?? gmdate( 'Y-m-d', strtotime( '-1 year' ) );

		// PayPal NVP requires dates in ISO 8601 format
		$original_start = $start_date_setting . 'T00:00:00Z';
		$original_end   = gmdate( 'Y-m-d' ) . 'T23:59:59Z';

		$defaults = array(
			'start_date' => $original_start,
			'end_date'   => $original_end,
		);

		$args = wp_parse_args( $args, $defaults );

		// Update progress
		set_transient(
			'scrm_paypal_import_progress',
			array(
				'status'         => 'running',
				'message'        => __( 'Connecting to PayPal...', 'syncpoint-crm' ),
				'synced'         => 0,
				'skipped'        => 0,
				'contacts_added' => 0,
			),
			600
		);

		$results = array(
			'synced'         => 0,
			'skipped'        => 0,
			'contacts_added' => 0,
			'errors'         => array(),
			'total_fetched'  => 0,
		);

		// PayPal NVP TransactionSearch has a limit of 100 results per call
		// We need to paginate by adjusting the date range based on the oldest transaction returned
		$current_start       = $args['start_date'];
		$current_end         = $args['end_date'];
		$page                = 0;
		$max_pages           = 500; // Safety limit - up to 50,000 transactions
		$all_transaction_ids = array(); // Track to avoid duplicates

		while ( $page < $max_pages ) {
			++$page;

			// Build NVP request
			$request_data = array(
				'METHOD'    => 'TransactionSearch',
				'VERSION'   => '124.0',
				'USER'      => $nvp_username,
				'PWD'       => $nvp_password,
				'SIGNATURE' => $nvp_signature,
				'STARTDATE' => $current_start,
				'ENDDATE'   => $current_end,
			);

			// Update progress
			set_transient(
				'scrm_paypal_import_progress',
				array(
					'status'         => 'running',
					'message'        => sprintf(
						/* translators: 1: page number, 2: total fetched */
						__( 'Fetching transactions (batch %1$d, %2$d total fetched)...', 'syncpoint-crm' ),
						$page,
						$results['total_fetched']
					),
					'synced'         => $results['synced'],
					'skipped'        => $results['skipped'],
					'contacts_added' => $results['contacts_added'],
				),
				600
			);

			$response = wp_remote_post(
				$this->get_nvp_url(),
				array(
					'body'    => $request_data,
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response ) ) {
				$results['errors'][] = $response->get_error_message();
				break;
			}

			$body = wp_remote_retrieve_body( $response );
			parse_str( $body, $result );

			// Check for API errors
			$ack = $result['ACK'] ?? '';
			if ( ! in_array( $ack, array( 'Success', 'SuccessWithWarning' ), true ) ) {
				$error_msg  = $result['L_LONGMESSAGE0'] ?? $result['L_SHORTMESSAGE0'] ?? __( 'Unknown PayPal NVP error.', 'syncpoint-crm' );
				$error_code = $result['L_ERRORCODE0'] ?? '';

				// Error code 11002 = "No transactions found" - this is not an error, just no more results
				if ( '11002' === $error_code ) {
					// No more transactions in this date range
					break;
				}

				return new \WP_Error( 'nvp_error', $error_msg . ' (Code: ' . $error_code . ')' );
			}

			// Parse transactions from response
			$transactions = $this->parse_nvp_transactions( $result );

			if ( empty( $transactions ) ) {
				// No transactions returned
				break;
			}

			$batch_count               = count( $transactions );
			$results['total_fetched'] += $batch_count;

			// Process each transaction
			$oldest_timestamp = null;

			foreach ( $transactions as $txn ) {
				// Skip if we've already processed this transaction (duplicate check)
				if ( in_array( $txn['transaction_id'], $all_transaction_ids, true ) ) {
					continue;
				}
				$all_transaction_ids[] = $txn['transaction_id'];

				// Track the oldest transaction timestamp for pagination
				if ( ! empty( $txn['timestamp'] ) ) {
					$txn_time = strtotime( $txn['timestamp'] );
					if ( null === $oldest_timestamp || $txn_time < $oldest_timestamp ) {
						$oldest_timestamp = $txn_time;
					}
				}

				$import_result = $this->import_nvp_transaction( $txn );

				if ( is_wp_error( $import_result ) ) {
					++$results['skipped'];
				} elseif ( isset( $import_result['created'] ) && $import_result['created'] ) {
					++$results['synced'];
					if ( isset( $import_result['contact_created'] ) && $import_result['contact_created'] ) {
						++$results['contacts_added'];
					}
				} else {
					++$results['skipped'];
				}

				// Update progress periodically
				if ( ( $results['synced'] + $results['skipped'] ) % 25 === 0 ) {
					set_transient(
						'scrm_paypal_import_progress',
						array(
							'status'         => 'running',
							'message'        => sprintf(
								/* translators: %d: transactions processed */
								__( 'Processing transactions (%d imported)...', 'syncpoint-crm' ),
								$results['synced']
							),
							'synced'         => $results['synced'],
							'skipped'        => $results['skipped'],
							'contacts_added' => $results['contacts_added'],
						),
						600
					);
				}
			}

			// If we got fewer than 100 transactions, we've reached the end of this date range
			if ( $batch_count < 100 ) {
				break;
			}

			// If we got exactly 100, there might be more - adjust the end date to fetch older transactions
			if ( null !== $oldest_timestamp ) {
				// Set the new end date to 1 second before the oldest transaction
				// This ensures we don't miss any transactions and don't duplicate
				$new_end = gmgmdate( 'Y-m-d\TH:i:s\Z', $oldest_timestamp - 1 );

				// Make sure we haven't gone past our start date
				if ( strtotime( $new_end ) <= strtotime( $current_start ) ) {
					break;
				}

				$current_end = $new_end;
			} else {
				// No valid timestamp found, can't paginate
				break;
			}

			// Small delay to avoid rate limiting
			usleep( 250000 ); // 0.25 seconds
		}

		// Clear progress
		delete_transient( 'scrm_paypal_import_progress' );

		// Log final results
		$this->log(
			'NVP Import Complete',
			array(
				'total_fetched'  => $results['total_fetched'],
				'synced'         => $results['synced'],
				'skipped'        => $results['skipped'],
				'contacts_added' => $results['contacts_added'],
				'pages'          => $page,
			)
		);

		return $results;
	}

	/**
	 * Parse NVP transaction search results.
	 *
	 * @param array $result NVP response array.
	 * @return array Parsed transactions.
	 */
	protected function parse_nvp_transactions( $result ) {
		$transactions = array();
		$i            = 0;

		// PayPal returns transactions as L_TRANSACTIONID0, L_TRANSACTIONID1, etc.
		while ( isset( $result[ 'L_TRANSACTIONID' . $i ] ) ) {
			$transactions[] = array(
				'transaction_id' => $result[ 'L_TRANSACTIONID' . $i ] ?? '',
				'timestamp'      => $result[ 'L_TIMESTAMP' . $i ] ?? '',
				'type'           => $result[ 'L_TYPE' . $i ] ?? '',
				'email'          => $result[ 'L_EMAIL' . $i ] ?? '',
				'name'           => $result[ 'L_NAME' . $i ] ?? '',
				'status'         => $result[ 'L_STATUS' . $i ] ?? '',
				'amount'         => $result[ 'L_AMT' . $i ] ?? 0,
				'currency'       => $result[ 'L_CURRENCYCODE' . $i ] ?? 'USD',
				'fee'            => $result[ 'L_FEEAMT' . $i ] ?? 0,
				'net'            => $result[ 'L_NETAMT' . $i ] ?? 0,
			);
			++$i;
		}

		return $transactions;
	}

	/**
	 * Import a single NVP transaction.
	 *
	 * @param array $txn Transaction data from NVP.
	 * @return array|\WP_Error Import result or error.
	 */
	protected function import_nvp_transaction( $txn ) {
		global $wpdb;

		// Skip non-payment transactions - be more inclusive
		$payment_types = array(
			'Payment',
			'Recurring Payment',
			'Subscription Payment',
			'Web Accept',
			'Express Checkout Payment',
			'Mass Pay Sent',
			'Donation',
			'eBay Auction Payment',
			'Virtual Terminal Payment',
		);

		if ( ! in_array( $txn['type'], $payment_types, true ) ) {
			return new \WP_Error( 'skipped', 'Not a payment transaction: ' . $txn['type'] );
		}

		// Skip negative amounts (these are usually fees or refunds handled separately)
		if ( floatval( $txn['amount'] ) <= 0 ) {
			return new \WP_Error( 'skipped', 'Non-positive amount' );
		}

		// Check if transaction already exists
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway_transaction_id = %s",
				$txn['transaction_id']
			)
		);

		if ( $exists ) {
			return array(
				'created' => false,
				'reason'  => 'duplicate',
			);
		}

		// Find or create contact
		$contact_id      = null;
		$contact_created = false;

		if ( ! empty( $txn['email'] ) ) {
			$contact = scrm_get_contact_by_email( $txn['email'] );

			if ( ! $contact ) {
				// Parse name
				$name_parts = explode( ' ', trim( $txn['name'] ), 2 );
				$first_name = $name_parts[0] ?? '';
				$last_name  = $name_parts[1] ?? '';

				$new_contact_id = scrm_create_contact(
					array(
						'email'      => $txn['email'],
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'type'       => 'customer',
						'currency'   => $txn['currency'],
						'source'     => 'paypal_import',
					)
				);

				if ( ! is_wp_error( $new_contact_id ) ) {
					$contact_id      = $new_contact_id;
					$contact_created = true;
				}
			} else {
				$contact_id = $contact->id;
			}
		}

		if ( ! $contact_id ) {
			return new \WP_Error( 'no_contact', 'Could not find or create contact' );
		}

		// Map PayPal status to our status
		$status        = 'completed';
		$paypal_status = strtolower( $txn['status'] ?? '' );
		if ( in_array( $paypal_status, array( 'pending', 'in-progress', 'processing' ), true ) ) {
			$status = 'pending';
		} elseif ( in_array( $paypal_status, array( 'refunded', 'reversed', 'canceled-reversal' ), true ) ) {
			$status = 'refunded';
		} elseif ( in_array( $paypal_status, array( 'denied', 'failed', 'voided', 'expired' ), true ) ) {
			$status = 'failed';
		}

		// Create transaction
		$transaction_id = scrm_create_transaction(
			array(
				'contact_id'             => $contact_id,
				'type'                   => 'payment',
				'gateway'                => 'paypal',
				'gateway_transaction_id' => $txn['transaction_id'],
				'amount'                 => abs( floatval( $txn['amount'] ) ),
				'currency'               => $txn['currency'],
				'status'                 => $status,
				'description'            => sprintf(
				/* translators: 1: transaction type, 2: date */
					__( 'PayPal %1$s - %2$s', 'syncpoint-crm' ),
					$txn['type'],
					gmdate( 'M j, Y', strtotime( $txn['timestamp'] ) )
				),
				'metadata'               => array(
					'paypal_type'      => $txn['type'],
					'paypal_status'    => $txn['status'],
					'paypal_timestamp' => $txn['timestamp'],
					'paypal_fee'       => $txn['fee'],
					'paypal_net'       => $txn['net'],
					'payer_name'       => $txn['name'],
					'payer_email'      => $txn['email'],
				),
			)
		);

		if ( is_wp_error( $transaction_id ) ) {
			return $transaction_id;
		}

		return array(
			'created'         => true,
			'transaction_id'  => $transaction_id,
			'contact_id'      => $contact_id,
			'contact_created' => $contact_created,
		);
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
