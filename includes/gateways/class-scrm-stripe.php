<?php
/**
 * Stripe Payment Gateway
 *
 * @package StarterCRM
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
	public $description = 'Sync transactions from Stripe and accept payments.';

	/**
	 * API version.
	 *
	 * @var string
	 */
	const API_VERSION = '2023-10-16';

	/**
	 * Get settings fields.
	 *
	 * @return array Settings fields.
	 */
	public function get_settings_fields() {
		return array(
			'enabled'             => array(
				'type'    => 'checkbox',
				'label'   => __( 'Enable Stripe', 'syncpoint-crm' ),
				'default' => false,
			),
			'mode'                => array(
				'type'    => 'select',
				'label'   => __( 'Mode', 'syncpoint-crm' ),
				'options' => array(
					'test' => __( 'Test', 'syncpoint-crm' ),
					'live' => __( 'Live', 'syncpoint-crm' ),
				),
				'default' => 'test',
			),
			'test_publishable'    => array(
				'type'  => 'text',
				'label' => __( 'Test Publishable Key', 'syncpoint-crm' ),
			),
			'test_secret'         => array(
				'type'  => 'password',
				'label' => __( 'Test Secret Key', 'syncpoint-crm' ),
			),
			'live_publishable'    => array(
				'type'  => 'text',
				'label' => __( 'Live Publishable Key', 'syncpoint-crm' ),
			),
			'live_secret'         => array(
				'type'  => 'password',
				'label' => __( 'Live Secret Key', 'syncpoint-crm' ),
			),
			'webhook_secret'      => array(
				'type'        => 'password',
				'label'       => __( 'Webhook Signing Secret', 'syncpoint-crm' ),
				'description' => __( 'From Stripe Dashboard > Webhooks.', 'syncpoint-crm' ),
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
			'tag_with_product'    => array(
				'type'        => 'checkbox',
				'label'       => __( 'Tag with Product Name', 'syncpoint-crm' ),
				'description' => __( 'Tag contacts with the product/description from their purchase.', 'syncpoint-crm' ),
				'default'     => false,
			),
		);
	}

	/**
	 * Get secret key.
	 *
	 * @return string Secret key.
	 */
	private function get_secret_key() {
		$credentials = $this->get_credentials();

		if ( $this->is_test_mode() ) {
			return $credentials['test_secret'] ?? '';
		}

		return $credentials['live_secret'] ?? '';
	}

	/**
	 * Get publishable key.
	 *
	 * @return string Publishable key.
	 */
	public function get_publishable_key() {
		$credentials = $this->get_credentials();

		if ( $this->is_test_mode() ) {
			return $credentials['test_publishable'] ?? '';
		}

		return $credentials['live_publishable'] ?? '';
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $args     Request arguments.
	 * @return array|\WP_Error Response or error.
	 */
	protected function api_request( $endpoint, $args = array() ) {
		$secret = $this->get_secret_key();

		if ( empty( $secret ) ) {
			return new \WP_Error( 'missing_credentials', __( 'Stripe credentials not configured.', 'syncpoint-crm' ) );
		}

		$defaults = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization'   => 'Bearer ' . $secret,
				'Stripe-Version'  => self::API_VERSION,
				'Content-Type'    => 'application/x-www-form-urlencoded',
			),
			'timeout' => 30,
		);

		$args = wp_parse_args( $args, $defaults );

		// Stripe uses form-encoded body.
		if ( ! empty( $args['body'] ) && is_array( $args['body'] ) ) {
			$args['body'] = http_build_query( $args['body'] );
		}

		$url      = 'https://api.stripe.com/v1' . $endpoint;
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

			$error = $body['error'] ?? array();
			$message = $error['message'] ?? __( 'API request failed.', 'syncpoint-crm' );
			return new \WP_Error( $error['type'] ?? 'api_error', $message, array( 'status' => $code ) );
		}

		return $body;
	}

	/**
	 * Sync transactions from Stripe.
	 *
	 * @param array $args Sync arguments.
	 * @return array|\WP_Error Sync results or error.
	 */
	public function sync_transactions( $args = array() ) {
		$defaults = array(
			'created_gte' => strtotime( '-30 days' ),
			'limit'       => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Get charges.
		$endpoint = '/charges?' . http_build_query( array(
			'created[gte]' => $args['created_gte'],
			'limit'        => $args['limit'],
		) );

		$response = $this->api_request( $endpoint );

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
				$this->log( 'Charge sync error', array(
					'charge_id' => $charge['id'] ?? 'unknown',
					'error'     => $result->get_error_message(),
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
			'total'          => count( $charges ),
		);

		do_action( 'scrm_stripe_sync_completed', $result );

		return $result;
	}

	/**
	 * Sync customers from Stripe.
	 *
	 * @param array $args Sync arguments.
	 * @return array|\WP_Error Sync results or error.
	 */
	public function sync_customers( $args = array() ) {
		$defaults = array(
			'limit' => 100,
		);

		$args        = wp_parse_args( $args, $defaults );
		$credentials = $this->get_credentials();

		$skip_update     = ! empty( $credentials['skip_contact_update'] );
		$custom_tags     = ! empty( $credentials['custom_tags'] ) ? array_map( 'trim', explode( ',', $credentials['custom_tags'] ) ) : array();

		// Get customers.
		$endpoint = '/customers?' . http_build_query( array(
			'limit' => $args['limit'],
		) );

		$response = $this->api_request( $endpoint );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$customers       = $response['data'] ?? array();
		$imported        = 0;
		$updated         = 0;
		$skipped         = 0;

		foreach ( $customers as $customer ) {
			$email = $customer['email'] ?? '';

			if ( empty( $email ) ) {
				$skipped++;
				continue;
			}

			$existing = scrm_get_contact_by_email( $email );

			if ( $existing ) {
				if ( $skip_update ) {
					$skipped++;
					continue;
				}

				// Update existing contact.
				scrm_update_contact( $existing->id, array(
					'first_name' => $customer['name'] ? explode( ' ', $customer['name'] )[0] : $existing->first_name,
					'last_name'  => $customer['name'] && count( explode( ' ', $customer['name'] ) ) > 1 ? explode( ' ', $customer['name'], 2 )[1] : $existing->last_name,
					'phone'      => $customer['phone'] ?? $existing->phone,
				) );

				// Apply tags.
				$this->apply_customer_tags( $existing->id, $custom_tags );

				$updated++;
			} else {
				// Create new contact.
				$name_parts = explode( ' ', $customer['name'] ?? '', 2 );

				$contact_id = scrm_create_contact( array(
					'email'      => $email,
					'first_name' => $name_parts[0] ?? '',
					'last_name'  => $name_parts[1] ?? '',
					'phone'      => $customer['phone'] ?? '',
					'type'       => 'customer',
					'source'     => 'stripe',
					'currency'   => strtoupper( $customer['currency'] ?? 'USD' ),
				) );

				if ( ! is_wp_error( $contact_id ) ) {
					// Apply tags.
					$this->apply_customer_tags( $contact_id, $custom_tags );
					$imported++;
				} else {
					$skipped++;
				}
			}
		}

		$result = array(
			'imported' => $imported,
			'updated'  => $updated,
			'skipped'  => $skipped,
			'total'    => count( $customers ),
		);

		do_action( 'scrm_stripe_customers_synced', $result );

		return $result;
	}

	/**
	 * Apply tags to a customer.
	 *
	 * @param int   $contact_id  Contact ID.
	 * @param array $custom_tags Custom tags to apply.
	 */
	private function apply_customer_tags( $contact_id, $custom_tags ) {
		foreach ( $custom_tags as $tag_name ) {
			if ( empty( $tag_name ) ) {
				continue;
			}

			$tag = scrm_get_tag_by_name( $tag_name );

			if ( ! $tag ) {
				$tag_id = scrm_create_tag( array( 'name' => $tag_name ) );
			} else {
				$tag_id = $tag->id;
			}

			if ( $tag_id && ! is_wp_error( $tag_id ) ) {
				scrm_assign_tag( $tag_id, $contact_id, 'contact' );
			}
		}
	}

	/**
	 * Process a single charge.
	 *
	 * @param array $charge Charge data from Stripe.
	 * @return bool|string|\WP_Error Result.
	 */
	private function process_charge( $charge ) {
		$charge_id = $charge['id'] ?? '';
		$amount    = floatval( $charge['amount'] ?? 0 ) / 100; // Convert from cents.
		$currency  = strtoupper( $charge['currency'] ?? 'USD' );
		$status    = $charge['status'] ?? '';

		if ( empty( $charge_id ) ) {
			return new \WP_Error( 'missing_id', 'Missing charge ID.' );
		}

		// Skip non-succeeded charges.
		if ( 'succeeded' !== $status ) {
			return 'skipped';
		}

		// Check if already exists.
		global $wpdb;
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'stripe' AND gateway_transaction_id = %s",
			$charge_id
		) );

		if ( $existing ) {
			return 'exists';
		}

		// Find or create contact.
		$email = $charge['billing_details']['email'] ?? $charge['receipt_email'] ?? '';

		if ( empty( $email ) && ! empty( $charge['customer'] ) ) {
			// Try to get email from customer.
			$customer = $this->api_request( '/customers/' . $charge['customer'] );
			if ( ! is_wp_error( $customer ) ) {
				$email = $customer['email'] ?? '';
			}
		}

		if ( empty( $email ) ) {
			return new \WP_Error( 'no_email', 'No customer email.' );
		}

		$contact = scrm_get_contact_by_email( $email );
		$contact_created = false;

		if ( ! $contact ) {
			$name = $charge['billing_details']['name'] ?? '';
			$name_parts = explode( ' ', $name, 2 );

			$contact_id = scrm_create_contact( array(
				'email'      => $email,
				'first_name' => $name_parts[0] ?? '',
				'last_name'  => $name_parts[1] ?? '',
				'type'       => 'customer',
				'currency'   => $currency,
				'source'     => 'stripe',
			) );

			if ( is_wp_error( $contact_id ) ) {
				return $contact_id;
			}

			$contact = scrm_get_contact( $contact_id );
			$contact_created = true;
		}

		// Create transaction.
		$result = scrm_create_transaction( array(
			'contact_id'             => $contact->id,
			'type'                   => 'payment',
			'gateway'                => 'stripe',
			'gateway_transaction_id' => $charge_id,
			'amount'                 => $amount,
			'currency'               => $currency,
			'status'                 => 'completed',
			'description'            => $charge['description'] ?? '',
			'metadata'               => array(
				'stripe_charge'   => $charge_id,
				'stripe_customer' => $charge['customer'] ?? '',
			),
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $contact_created ? 'contact_created' : true;
	}

	/**
	 * Create payment link for invoice.
	 *
	 * @param \SCRM\Core\Invoice $invoice Invoice object.
	 * @return string|\WP_Error Payment link or error.
	 */
	public function create_payment_link( $invoice ) {
		$contact = $invoice->get_contact();

		// Create a payment link.
		$response = $this->api_request( '/payment_links', array(
			'method' => 'POST',
			'body'   => array(
				'line_items[0][price_data][currency]'               => strtolower( $invoice->currency ),
				'line_items[0][price_data][product_data][name]'     => sprintf(
					/* translators: %s: invoice number */
					__( 'Invoice %s', 'syncpoint-crm' ),
					$invoice->invoice_number
				),
				'line_items[0][price_data][unit_amount]'            => round( $invoice->total * 100 ), // Convert to cents.
				'line_items[0][quantity]'                           => 1,
				'metadata[invoice_number]'                          => $invoice->invoice_number,
				'metadata[invoice_id]'                              => $invoice->id,
				'after_completion[type]'                            => 'redirect',
				'after_completion[redirect][url]'                   => add_query_arg( array(
					'scrm_invoice' => $invoice->invoice_number,
					'payment'      => 'success',
				), home_url() ),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response['url'] ?? new \WP_Error( 'no_url', __( 'Could not generate payment link.', 'syncpoint-crm' ) );
	}

	/**
	 * Process webhook.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error True on success or error.
	 */
	public function process_webhook( $payload ) {
		$event_type = $payload['type'] ?? '';

		$this->log( 'Webhook received', array(
			'event_type' => $event_type,
		) );

		switch ( $event_type ) {
			case 'charge.succeeded':
				return $this->handle_charge_succeeded( $payload );

			case 'charge.refunded':
				return $this->handle_charge_refunded( $payload );

			case 'payment_intent.succeeded':
				return $this->handle_payment_intent_succeeded( $payload );

			case 'checkout.session.completed':
				return $this->handle_checkout_completed( $payload );

			default:
				return true;
		}
	}

	/**
	 * Handle charge succeeded event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool|\WP_Error Result.
	 */
	private function handle_charge_succeeded( $payload ) {
		$charge = $payload['data']['object'] ?? array();
		return $this->process_charge( $charge );
	}

	/**
	 * Handle charge refunded event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool Result.
	 */
	private function handle_charge_refunded( $payload ) {
		$charge     = $payload['data']['object'] ?? array();
		$charge_id  = $charge['id'] ?? '';
		$refund_amt = floatval( $charge['amount_refunded'] ?? 0 ) / 100;
		$currency   = strtoupper( $charge['currency'] ?? 'USD' );

		// Find original transaction.
		global $wpdb;
		$txn = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}scrm_transactions WHERE gateway = 'stripe' AND gateway_transaction_id = %s",
			$charge_id
		) );

		if ( $txn ) {
			// Create refund transaction.
			scrm_create_transaction( array(
				'contact_id'             => $txn->contact_id,
				'type'                   => 'refund',
				'gateway'                => 'stripe',
				'gateway_transaction_id' => $charge_id . '_refund',
				'amount'                 => $refund_amt,
				'currency'               => $currency,
				'status'                 => 'completed',
				'description'            => __( 'Refund', 'syncpoint-crm' ),
			) );

			do_action( 'scrm_stripe_refund', $txn, $refund_amt );
		}

		return true;
	}

	/**
	 * Handle payment intent succeeded event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool Result.
	 */
	private function handle_payment_intent_succeeded( $payload ) {
		$intent = $payload['data']['object'] ?? array();
		$metadata = $intent['metadata'] ?? array();

		// Check for invoice.
		$invoice_number = $metadata['invoice_number'] ?? '';

		if ( $invoice_number ) {
			$invoice = new \SCRM\Core\Invoice();
			$invoice->read_by_number( $invoice_number );

			if ( $invoice->exists() && 'paid' !== $invoice->status ) {
				$amount   = floatval( $intent['amount'] ?? 0 ) / 100;
				$currency = strtoupper( $intent['currency'] ?? 'USD' );

				$txn_id = scrm_create_transaction( array(
					'contact_id'             => $invoice->contact_id,
					'invoice_id'             => $invoice->id,
					'type'                   => 'payment',
					'gateway'                => 'stripe',
					'gateway_transaction_id' => $intent['id'] ?? '',
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
	 * Handle checkout session completed event.
	 *
	 * @param array $payload Webhook payload.
	 * @return bool Result.
	 */
	private function handle_checkout_completed( $payload ) {
		$session  = $payload['data']['object'] ?? array();
		$metadata = $session['metadata'] ?? array();

		// Check for invoice.
		$invoice_number = $metadata['invoice_number'] ?? '';

		if ( $invoice_number ) {
			$invoice = new \SCRM\Core\Invoice();
			$invoice->read_by_number( $invoice_number );

			if ( $invoice->exists() && 'paid' !== $invoice->status ) {
				$amount   = floatval( $session['amount_total'] ?? 0 ) / 100;
				$currency = strtoupper( $session['currency'] ?? 'USD' );

				$txn_id = scrm_create_transaction( array(
					'contact_id'             => $invoice->contact_id,
					'invoice_id'             => $invoice->id,
					'type'                   => 'payment',
					'gateway'                => 'stripe',
					'gateway_transaction_id' => $session['payment_intent'] ?? $session['id'],
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
	 * Verify webhook signature.
	 *
	 * @param string $payload   Raw payload.
	 * @param string $signature Signature header.
	 * @return bool True if valid.
	 */
	public function verify_webhook_signature( $payload, $signature ) {
		$credentials    = $this->get_credentials();
		$webhook_secret = $credentials['webhook_secret'] ?? '';

		if ( empty( $webhook_secret ) ) {
			$this->log( 'Webhook secret not configured' );
			return false;
		}

		if ( empty( $signature ) ) {
			return false;
		}

		// Parse signature header.
		$parts = array();
		foreach ( explode( ',', $signature ) as $part ) {
			$item = explode( '=', $part, 2 );
			$parts[ $item[0] ] = $item[1] ?? '';
		}

		$timestamp = $parts['t'] ?? '';
		$sig       = $parts['v1'] ?? '';

		if ( empty( $timestamp ) || empty( $sig ) ) {
			return false;
		}

		// Check timestamp (5 minute tolerance).
		if ( abs( time() - intval( $timestamp ) ) > 300 ) {
			$this->log( 'Webhook timestamp too old' );
			return false;
		}

		// Compute expected signature.
		$signed_payload = $timestamp . '.' . $payload;
		$expected       = hash_hmac( 'sha256', $signed_payload, $webhook_secret );

		return hash_equals( $expected, $sig );
	}
}
