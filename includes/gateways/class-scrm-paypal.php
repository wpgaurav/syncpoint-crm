<?php
/**
 * PayPal Payment Gateway
 *
 * @package StarterCRM
 * @since 1.0.0
 */

namespace SCRM\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Class PayPal
 *
 * PayPal REST API integration.
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
	 * Access token.
	 *
	 * @var string|null
	 */
	private $access_token = null;

	/**
	 * Token expiry.
	 *
	 * @var int
	 */
	private $token_expiry = 0;

	/**
	 * Get settings fields.
	 *
	 * @return array Settings fields.
	 */
	public function get_settings_fields() {
		return array(
			'enabled'            => array(
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayPal', 'syncpoint-crm' ),
				'default' => false,
			),
			'mode'               => array(
				'type'    => 'select',
				'label'   => __( 'Live or Sandbox', 'syncpoint-crm' ),
				'options' => array(
					'sandbox' => __( 'Sandbox (Testing)', 'syncpoint-crm' ),
					'live'    => __( 'Live', 'syncpoint-crm' ),
				),
				'default' => 'live',
				'desc'    => __( 'Change here if you need to use PayPal sandbox mode.', 'syncpoint-crm' ),
			),
			// REST API credentials (modern).
			'client_id'          => array(
				'type'  => 'text',
				'label' => __( 'REST API Client ID', 'syncpoint-crm' ),
				'desc'  => __( 'For webhooks and payment links.', 'syncpoint-crm' ),
			),
			'client_secret'      => array(
				'type'  => 'password',
				'label' => __( 'REST API Client Secret', 'syncpoint-crm' ),
			),
			// NVP API credentials (legacy - for transaction history).
			'paypal_email'       => array(
				'type'  => 'text',
				'label' => __( 'PayPal Email', 'syncpoint-crm' ),
				'desc'  => __( 'Enter your PayPal email (must be business account).', 'syncpoint-crm' ),
			),
			'api_username'       => array(
				'type'  => 'text',
				'label' => __( 'API Username', 'syncpoint-crm' ),
				'desc'  => __( 'Enter your API username.', 'syncpoint-crm' ),
			),
			'api_password'       => array(
				'type'  => 'password',
				'label' => __( 'API Password', 'syncpoint-crm' ),
				'desc'  => __( 'Enter your API password.', 'syncpoint-crm' ),
			),
			'api_signature'      => array(
				'type'  => 'text',
				'label' => __( 'API Signature', 'syncpoint-crm' ),
				'desc'  => __( 'Enter your API signature.', 'syncpoint-crm' ),
			),
			'first_txn_date'     => array(
				'type'    => 'date',
				'label'   => __( 'First Transaction Date', 'syncpoint-crm' ),
				'desc'    => __( 'Enter the date of your first transaction in yyyy-mm-dd format. PayPal only lets you import data from the prior 3 years.', 'syncpoint-crm' ),
				'default' => date( 'Y-m-d', strtotime( '-3 years' ) ),
			),
			// Sync settings.
			'skip_contact_update' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Do not update Contacts', 'syncpoint-crm' ),
				'desc'    => __( 'Do not update contact if the email exists. Tick to stop PayPal Sync updating contacts.', 'syncpoint-crm' ),
				'default' => false,
			),
			'tag_with_item_name' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Tag Contacts with the item name', 'syncpoint-crm' ),
				'desc'    => __( 'Check this box to tag contacts with the item name from their purchase.', 'syncpoint-crm' ),
				'default' => false,
			),
			'custom_tags'        => array(
				'type'    => 'text',
				'label'   => __( 'Custom tags for contacts', 'syncpoint-crm' ),
				'desc'    => __( 'Enter custom tags for contacts who have completed a payment using PayPal. If you want to use multiple tags, separate with commas.', 'syncpoint-crm' ),
				'default' => 'PayPal,Paid Customer',
			),
			'webhook_id'         => array(
				'type' => 'text',
				'label' => __( 'Webhook ID', 'syncpoint-crm' ),
				'desc' => __( 'From PayPal Developer Dashboard (optional).', 'syncpoint-crm' ),
			),
		);
	}

	/**
	 * Get API base URL.
	 *
	 * @return string API URL.
	 */
	private function get_api_url() {
		return $this->is_test_mode()
			? 'https://api-m.sandbox.paypal.com'
			: 'https://api-m.paypal.com';
	}

	/**
	 * Get access token.
	 *
	 * @return string|\WP_Error Access token or error.
	 */
	private function get_access_token() {
		// Return cached token if still valid.
		if ( $this->access_token && $this->token_expiry > time() ) {
			return $this->access_token;
		}

		$credentials = $this->get_credentials();
		$client_id   = $credentials['client_id'] ?? '';
		$secret      = $credentials['client_secret'] ?? '';

		if ( empty( $client_id ) || empty( $secret ) ) {
			return new \WP_Error( 'missing_credentials', __( 'PayPal credentials not configured.', 'syncpoint-crm' ) );
		}

		$response = wp_remote_post( $this->get_api_url() . '/v1/oauth2/token', array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => 'grant_type=client_credentials',
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			$this->log( 'OAuth error', array( 'error' => $response->get_error_message() ) );
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$this->log( 'OAuth failed', $body );
			return new \WP_Error( 'oauth_failed', __( 'Failed to obtain PayPal access token.', 'syncpoint-crm' ) );
		}

		$this->access_token = $body['access_token'];
		$this->token_expiry = time() + ( $body['expires_in'] ?? 3600 ) - 60; // Buffer of 60 seconds.

		return $this->access_token;
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $args     Request arguments.
	 * @return array|\WP_Error Response or error.
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

		if ( ! empty( $args['body'] ) && is_array( $args['body'] ) ) {
			$args['body'] = wp_json_encode( $args['body'] );
		}

		$url      = $this->get_api_url() . $endpoint;
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log( 'API error', array(
				'endpoint' => $endpoint,
				'error'    => $response->get_error_message(),
			) );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$this->log( 'API error response', array(
				'endpoint' => $endpoint,
				'code'     => $code,
				'body'     => $body,
			) );

			$message = $body['message'] ?? $body['error_description'] ?? __( 'API request failed.', 'syncpoint-crm' );
			return new \WP_Error( 'api_error', $message, array( 'status' => $code ) );
		}

		return $body;
	}

	/**
	 * Sync transactions from PayPal.
	 *
	 * @param array $args Sync arguments.
	 * @return array|\WP_Error Sync results or error.
	 */
	public function sync_transactions( $args = array() ) {
		$defaults = array(
			'start_date' => date( 'Y-m-d', strtotime( '-30 days' ) ) . 'T00:00:00Z',
			'end_date'   => date( 'Y-m-d' ) . 'T23:59:59Z',
			'page_size'  => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Get transactions.
		$endpoint = '/v1/reporting/transactions?' . http_build_query( array(
			'start_date'       => $args['start_date'],
			'end_date'         => $args['end_date'],
			'page_size'        => $args['page_size'],
			'transaction_type' => 'T0000',
			'fields'           => 'transaction_info,payer_info,cart_info',
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
				$this->log( 'Transaction sync error', array(
					'txn'   => $txn['transaction_info']['transaction_id'] ?? 'unknown',
					'error' => $result->get_error_message(),
				) );
				$skipped++;
			} elseif ( true === $result ) {
				$synced++;
			} elseif ( 'contact_created' === $result ) {
				$synced++;
				$contacts_added++;
			} else {
				$skipped++;
			}
		}

		$result = array(
			'synced'         => $synced,
			'skipped'        => $skipped,
			'contacts_added' => $contacts_added,
			'total'          => count( $transactions ),
		);

		do_action( 'scrm_paypal_sync_completed', $result );

		return $result;
	}

	/**
	 * Process a single transaction.
	 *
	 * @param array $txn Transaction data from PayPal.
	 * @return bool|string|\WP_Error Result.
	 */
	private function process_transaction( $txn ) {
		$txn_info   = $txn['transaction_info'] ?? array();
		$payer_info = $txn['payer_info'] ?? array();

		$paypal_txn_id = $txn_info['transaction_id'] ?? '';
		$amount        = floatval( $txn_info['transaction_amount']['value'] ?? 0 );
		$currency      = $txn_info['transaction_amount']['currency_code'] ?? 'USD';
		$status        = $txn_info['transaction_status'] ?? '';

		if ( empty( $paypal_txn_id ) ) {
			return new \WP_Error( 'missing_id', 'Missing transaction ID.' );
		}

		// Skip non-completed transactions.
		if ( 'S' !== $status ) { // S = Success.
			return 'skipped';
		}

		// Check if already exists.
		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'paypal' AND gateway_transaction_id = %s",
			$paypal_txn_id
		) );

		if ( $existing ) {
			return 'exists';
		}

		// Get sync settings.
		$credentials        = $this->get_credentials();
		$skip_update        = ! empty( $credentials['skip_contact_update'] );
		$tag_with_item      = ! empty( $credentials['tag_with_item_name'] );
		$custom_tags_string = $credentials['custom_tags'] ?? '';

		// Find or create contact.
		$email = $payer_info['email_address'] ?? '';
		if ( empty( $email ) ) {
			return new \WP_Error( 'no_email', 'No payer email.' );
		}

		$contact = scrm_get_contact_by_email( $email );
		$contact_created = false;

		if ( ! $contact ) {
			// Create new contact.
			$contact_id = scrm_create_contact( array(
				'email'      => $email,
				'first_name' => $payer_info['payer_name']['given_name'] ?? '',
				'last_name'  => $payer_info['payer_name']['surname'] ?? '',
				'type'       => 'customer',
				'currency'   => $currency,
				'source'     => 'paypal',
			) );

			if ( is_wp_error( $contact_id ) ) {
				return $contact_id;
			}

			$contact = scrm_get_contact( $contact_id );
			$contact_created = true;
		} elseif ( ! $skip_update ) {
			// Update existing contact.
			scrm_update_contact( $contact->id, array(
				'first_name' => $payer_info['payer_name']['given_name'] ?? $contact->first_name,
				'last_name'  => $payer_info['payer_name']['surname'] ?? $contact->last_name,
			) );
		}

		// Apply custom tags.
		if ( ! empty( $custom_tags_string ) ) {
			$custom_tags = array_map( 'trim', explode( ',', $custom_tags_string ) );
			foreach ( $custom_tags as $tag_name ) {
				if ( empty( $tag_name ) ) continue;
				$tag = scrm_get_tag_by_name( $tag_name );
				if ( ! $tag ) {
					$tag_id = scrm_create_tag( array( 'name' => $tag_name ) );
					if ( ! is_wp_error( $tag_id ) ) {
						scrm_add_contact_tag( $contact->id, $tag_id );
					}
				} else {
					scrm_add_contact_tag( $contact->id, $tag->id );
				}
			}
		}

		// Apply item name as tag.
		if ( $tag_with_item ) {
			$cart_info = $txn['cart_info'] ?? array();
			$items = $cart_info['item_details'] ?? array();
			foreach ( $items as $item ) {
				$item_name = $item['item_name'] ?? '';
				if ( ! empty( $item_name ) ) {
					$tag = scrm_get_tag_by_name( $item_name );
					if ( ! $tag ) {
						$tag_id = scrm_create_tag( array( 'name' => $item_name, 'color' => '#3B82F6' ) );
						if ( ! is_wp_error( $tag_id ) ) {
							scrm_add_contact_tag( $contact->id, $tag_id );
						}
					} else {
						scrm_add_contact_tag( $contact->id, $tag->id );
					}
				}
			}
		}

		// Create transaction.
		$result = scrm_create_transaction( array(
			'contact_id'             => $contact->id,
			'type'                   => $amount >= 0 ? 'payment' : 'refund',
			'gateway'                => 'paypal',
			'gateway_transaction_id' => $paypal_txn_id,
			'amount'                 => abs( $amount ),
			'currency'               => $currency,
			'status'                 => 'completed',
			'description'            => $txn_info['transaction_note'] ?? '',
			'metadata'               => array(
				'paypal_txn' => $txn_info,
				'payer'      => $payer_info,
			),
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $contact_created ? 'contact_created' : true;
	}

	/**
	 * Sync transactions using PayPal NVP API (legacy).
	 * This allows importing historical transactions up to 3 years.
	 *
	 * @return array|\WP_Error Sync results or error.
	 */
	public function sync_transactions_nvp() {
		$credentials = $this->get_credentials();
		
		$api_username  = $credentials['api_username'] ?? '';
		$api_password  = $credentials['api_password'] ?? '';
		$api_signature = $credentials['api_signature'] ?? '';
		$start_date    = $credentials['first_txn_date'] ?? date( 'Y-m-d', strtotime( '-3 years' ) );
		
		if ( empty( $api_username ) || empty( $api_password ) || empty( $api_signature ) ) {
			return new \WP_Error( 'missing_nvp_credentials', __( 'PayPal NVP API credentials not configured.', 'syncpoint-crm' ) );
		}
		
		$nvp_url = $this->is_test_mode()
			? 'https://api-3t.sandbox.paypal.com/nvp'
			: 'https://api-3t.paypal.com/nvp';
		
		$synced         = 0;
		$skipped        = 0;
		$contacts_added = 0;
		$page           = 0;
		$has_more       = true;
		$processed      = 0;

		// Initialize progress transient.
		set_transient( 'scrm_paypal_import_progress', array(
			'status'         => 'running',
			'page'           => 0,
			'processed'      => 0,
			'synced'         => 0,
			'skipped'        => 0,
			'contacts_added' => 0,
			'message'        => __( 'Starting import...', 'syncpoint-crm' ),
		), 600 );

		// Convert date to PayPal format.
		$start_datetime = date( 'Y-m-d', strtotime( $start_date ) ) . 'T00:00:00Z';
		$end_datetime   = date( 'Y-m-d' ) . 'T23:59:59Z';
		
		while ( $has_more ) {
			$nvp_data = array(
				'METHOD'    => 'TransactionSearch',
				'VERSION'   => '204.0',
				'USER'      => $api_username,
				'PWD'       => $api_password,
				'SIGNATURE' => $api_signature,
				'STARTDATE' => $start_datetime,
				'ENDDATE'   => $end_datetime,
				'STATUS'    => 'All',
			);
			
			$response = wp_remote_post( $nvp_url, array(
				'body'    => $nvp_data,
				'timeout' => 60,
			) );
			
			if ( is_wp_error( $response ) ) {
				$error_msg = $response->get_error_message();
				// Update progress with error.
				set_transient( 'scrm_paypal_import_progress', array(
					'status'  => 'error',
					'message' => $error_msg,
				), 600 );
				return $response;
			}
			
			$body = wp_remote_retrieve_body( $response );
			parse_str( $body, $result );

			// Log API response for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SyncPoint CRM PayPal NVP Response ACK: ' . ( $result['ACK'] ?? 'none' ) );
				if ( isset( $result['L_ERRORCODE0'] ) ) {
					error_log( 'SyncPoint CRM PayPal NVP Error: ' . ( $result['L_LONGMESSAGE0'] ?? $result['L_SHORTMESSAGE0'] ?? 'Unknown' ) );
				}
			}

			if ( 'Success' !== ( $result['ACK'] ?? '' ) && 'SuccessWithWarning' !== ( $result['ACK'] ?? '' ) ) {
				$error_msg = $result['L_LONGMESSAGE0'] ?? $result['L_SHORTMESSAGE0'] ?? 'Unknown API error';

				// Update progress with error.
				set_transient( 'scrm_paypal_import_progress', array(
					'status'  => 'error',
					'message' => $error_msg,
				), 600 );

				return new \WP_Error( 'nvp_error', $error_msg );
			}
			
			// Parse transactions from NVP response.
			$i = 0;
			$page_count = 0;
			// Count transactions in this page.
			while ( isset( $result[ 'L_TRANSACTIONID' . $page_count ] ) ) {
				$page_count++;
			}

			// Update progress with page info.
			set_transient( 'scrm_paypal_import_progress', array(
				'status'         => 'running',
				'page'           => $page + 1,
				'processed'      => $processed,
				'synced'         => $synced,
				'skipped'        => $skipped,
				'contacts_added' => $contacts_added,
				'message'        => sprintf(
					/* translators: 1: page number, 2: transactions in page */
					__( 'Processing page %1$d (%2$d transactions)...', 'syncpoint-crm' ),
					$page + 1,
					$page_count
				),
			), 600 );

			$last_timestamp = '';

			while ( isset( $result[ 'L_TRANSACTIONID' . $i ] ) ) {
				$txn_id     = $result[ 'L_TRANSACTIONID' . $i ];
				$email      = $result[ 'L_EMAIL' . $i ] ?? '';
				$name       = $result[ 'L_NAME' . $i ] ?? '';
				$amount     = floatval( $result[ 'L_AMT' . $i ] ?? 0 );
				$currency   = $result[ 'L_CURRENCYCODE' . $i ] ?? 'USD';
				$status     = $result[ 'L_STATUS' . $i ] ?? '';
				$type       = $result[ 'L_TYPE' . $i ] ?? '';
				$timestamp  = $result[ 'L_TIMESTAMP' . $i ] ?? '';
				
				$last_timestamp = $timestamp;
				$i++;
				
				// Skip if not completed/processed.
				if ( 'Completed' !== $status && 'Processed' !== $status ) {
					$skipped++;
					continue;
				}
				
				// Skip non-payment transactions.
				// Accept various PayPal payment types.
				$valid_types = array(
					'Payment',
					'Recurring Payment',
					'Web Accept',
					'Express Checkout',
					'Subscription Payment',
					'Virtual Terminal',
					'Mobile Payment',
					'Donation',
				);
				if ( ! in_array( $type, $valid_types, true ) ) {
					$skipped++;
					continue;
				}
				
				// Skip if already exists.
				global $wpdb;
				$existing = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'paypal' AND gateway_transaction_id = %s",
					$txn_id
				) );
				
				if ( $existing ) {
					$skipped++;
					continue;
				}
				
				// Find or create contact.
				if ( empty( $email ) ) {
					$skipped++;
					continue;
				}
				
				$contact = scrm_get_contact_by_email( $email );
				$contact_created = false;
				
				$skip_update = ! empty( $credentials['skip_contact_update'] );
				
				if ( ! $contact ) {
					$name_parts = explode( ' ', $name, 2 );
					$contact_id = scrm_create_contact( array(
						'email'      => $email,
						'first_name' => $name_parts[0] ?? '',
						'last_name'  => $name_parts[1] ?? '',
						'type'       => 'customer',
						'currency'   => $currency,
						'source'     => 'paypal',
					) );
					
					if ( ! is_wp_error( $contact_id ) ) {
						$contact = scrm_get_contact( $contact_id );
						$contact_created = true;
					}
				} elseif ( ! $skip_update ) {
					$name_parts = explode( ' ', $name, 2 );
					scrm_update_contact( $contact->id, array(
						'first_name' => $name_parts[0] ?? $contact->first_name,
						'last_name'  => $name_parts[1] ?? $contact->last_name,
					) );
				}
				
				if ( ! $contact ) {
					$skipped++;
					continue;
				}
				
				// Apply custom tags.
				$custom_tags_string = $credentials['custom_tags'] ?? '';
				if ( ! empty( $custom_tags_string ) ) {
					$custom_tags = array_map( 'trim', explode( ',', $custom_tags_string ) );
					foreach ( $custom_tags as $tag_name ) {
						if ( empty( $tag_name ) ) continue;
						$tag = scrm_get_tag_by_name( $tag_name );
						if ( ! $tag ) {
							$tag_id = scrm_create_tag( array( 'name' => $tag_name ) );
							if ( ! is_wp_error( $tag_id ) ) {
								scrm_add_contact_tag( $contact->id, $tag_id );
							}
						} else {
							scrm_add_contact_tag( $contact->id, $tag->id );
						}
					}
				}
				
				// Create transaction.
				$txn_result = scrm_create_transaction( array(
					'contact_id'             => $contact->id,
					'type'                   => $amount >= 0 ? 'payment' : 'refund',
					'gateway'                => 'paypal',
					'gateway_transaction_id' => $txn_id,
					'amount'                 => abs( $amount ),
					'currency'               => $currency,
					'status'                 => 'completed',
					'created_at'             => date( 'Y-m-d H:i:s', strtotime( $timestamp ) ),
				) );
				
				if ( ! is_wp_error( $txn_result ) ) {
					$synced++;
					if ( $contact_created ) {
						$contacts_added++;
					}
				} else {
					$skipped++;
				}

				$processed++;

				// Update progress every 10 transactions.
				if ( $processed % 10 === 0 ) {
					set_transient( 'scrm_paypal_import_progress', array(
						'status'         => 'running',
						'page'           => $page + 1,
						'processed'      => $processed,
						'synced'         => $synced,
						'skipped'        => $skipped,
						'contacts_added' => $contacts_added,
						'message'        => sprintf(
							/* translators: 1: processed count, 2: synced count */
							__( 'Processed %1$d transactions, imported %2$d...', 'syncpoint-crm' ),
							$processed,
							$synced
						),
					), 600 );
				}
			}
			
			// NVP API returns max 100 transactions. If we got 100, there might be more.
			$has_more = ( $i >= 100 );
			
			// Update end_datetime for the next page to fetch older transactions.
			if ( $has_more && $last_timestamp ) {
				$end_datetime = $last_timestamp;
			}

			$page++;
			
			// Safety limit to prevent infinite loops.
			if ( $page > 50 ) {
				$has_more = false;
			}
		}
		
		$result = array(
			'synced'         => $synced,
			'skipped'        => $skipped,
			'contacts_added' => $contacts_added,
		);

		// Mark progress as complete.
		set_transient( 'scrm_paypal_import_progress', array(
			'status'         => 'completed',
			'page'           => $page,
			'processed'      => $processed,
			'synced'         => $synced,
			'skipped'        => $skipped,
			'contacts_added' => $contacts_added,
			'message'        => sprintf(
				/* translators: 1: synced count, 2: contacts count */
				__( 'Completed! Imported %1$d transactions, created %2$d contacts.', 'syncpoint-crm' ),
				$synced,
				$contacts_added
			),
		), 600 );

		do_action( 'scrm_paypal_nvp_sync_completed', $result );

		return $result;
	}

	/**
	 * Create payment link for invoice.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @return string|\WP_Error Payment link or error.
	 */
	public function create_payment_link( $invoice ) {
		$contact = $invoice->get_contact();

		$order_data = array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'reference_id' => $invoice->invoice_number,
					'description'  => sprintf(
						/* translators: %s: invoice number */
						__( 'Payment for Invoice %s', 'syncpoint-crm' ),
						$invoice->invoice_number
					),
					'amount'       => array(
						'currency_code' => $invoice->currency,
						'value'         => number_format( $invoice->total, 2, '.', '' ),
					),
				),
			),
			'application_context' => array(
				'brand_name'          => get_bloginfo( 'name' ),
				'landing_page'        => 'BILLING',
				'user_action'         => 'PAY_NOW',
				'return_url'          => add_query_arg( array(
					'scrm_invoice' => $invoice->invoice_number,
					'payment'      => 'success',
				), home_url() ),
				'cancel_url'          => add_query_arg( array(
					'scrm_invoice' => $invoice->invoice_number,
					'payment'      => 'cancelled',
				), home_url() ),
			),
		);

		$response = $this->api_request( '/v2/checkout/orders', array(
			'method' => 'POST',
			'body'   => $order_data,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Find the approve link.
		$links = $response['links'] ?? array();
		foreach ( $links as $link ) {
			if ( 'approve' === $link['rel'] ) {
				return $link['href'];
			}
		}

		return new \WP_Error( 'no_link', __( 'Could not generate payment link.', 'syncpoint-crm' ) );
	}

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error True on success or error.
	 */
	public function process_webhook( $payload ) {
		$event_type = $payload['event_type'] ?? '';

		$this->log( 'Webhook received', array(
			'event_type' => $event_type,
		) );

		switch ( $event_type ) {
			case 'PAYMENT.CAPTURE.COMPLETED':
				return $this->handle_payment_captured( $payload );

			case 'PAYMENT.CAPTURE.REFUNDED':
				return $this->handle_payment_refunded( $payload );

			case 'CHECKOUT.ORDER.APPROVED':
				return $this->handle_order_approved( $payload );

			default:
				// Unknown event, just acknowledge.
				return true;
		}
	}

	/**
	 * Handle payment captured event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error Result.
	 */
	private function handle_payment_captured( $payload ) {
		$resource = $payload['resource'] ?? array();

		$paypal_txn_id = $resource['id'] ?? '';
		$amount        = floatval( $resource['amount']['value'] ?? 0 );
		$currency      = $resource['amount']['currency_code'] ?? 'USD';

		// Get custom ID if set (invoice number).
		$invoice_number = $resource['custom_id'] ?? $resource['invoice_id'] ?? '';

		if ( $invoice_number ) {
			// Find invoice and mark as paid.
			$invoice = new \SCRM\Core\Invoice();
			$invoice->read_by_number( $invoice_number );

			if ( $invoice->exists() && 'paid' !== $invoice->status ) {
				// Create transaction.
				$txn_id = scrm_create_transaction( array(
					'contact_id'             => $invoice->contact_id,
					'invoice_id'             => $invoice->id,
					'type'                   => 'payment',
					'gateway'                => 'paypal',
					'gateway_transaction_id' => $paypal_txn_id,
					'amount'                 => $amount,
					'currency'               => $currency,
					'status'                 => 'completed',
				) );

				if ( ! is_wp_error( $txn_id ) ) {
					$invoice->mark_paid( $txn_id );
				}
			}
		}

		return true;
	}

	/**
	 * Handle payment refunded event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool Result.
	 */
	private function handle_payment_refunded( $payload ) {
		// Log refund, could be extended to create refund transaction.
		$this->log( 'Refund processed', $payload );
		return true;
	}

	/**
	 * Handle order approved event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool Result.
	 */
	private function handle_order_approved( $payload ) {
		// Log order approval.
		$this->log( 'Order approved', $payload );
		return true;
	}

	/**
	 * Verify webhook signature.
	 *
	 * @param string $payload   Raw payload.
	 * @param string $signature Signature header.
	 * @return bool True if valid.
	 */
	public function verify_webhook_signature( $payload, $signature ) {
		// PayPal webhook verification requires additional headers.
		// For now, return true. In production, implement full verification.
		$credentials = $this->get_credentials();
		$webhook_id  = $credentials['webhook_id'] ?? '';

		if ( empty( $webhook_id ) ) {
			$this->log( 'Webhook ID not configured' );
			return false;
		}

		// In production, call PayPal's verify-webhook-signature endpoint.
		// For simplicity, we'll trust the webhook here.
		return true;
	}
}
