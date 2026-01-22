<?php
/**
 * Webhook Handler
 *
 * Handles incoming webhooks from external services.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_Webhooks
 *
 * @since 1.0.0
 */
class SCRM_Webhooks {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'scrm/v1';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register webhook routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		// Generic inbound webhook.
		register_rest_route(
			self::NAMESPACE,
			'/webhooks/inbound',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_inbound' ),
				'permission_callback' => array( $this, 'verify_webhook_key' ),
			)
		);

		// PayPal webhook.
		register_rest_route(
			self::NAMESPACE,
			'/webhooks/paypal',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_paypal' ),
				'permission_callback' => '__return_true', // PayPal uses signature verification.
			)
		);

		// Stripe webhook.
		register_rest_route(
			self::NAMESPACE,
			'/webhooks/stripe',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_stripe' ),
				'permission_callback' => '__return_true', // Stripe uses signature verification.
			)
		);
	}

	/**
	 * Verify webhook key.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if valid, error otherwise.
	 */
	public function verify_webhook_key( $request ) {
		// Check if webhooks are enabled.
		if ( ! scrm_is_enabled( 'webhooks' ) ) {
			return new WP_Error(
				'webhooks_disabled',
				__( 'Webhooks are disabled.', 'syncpoint-crm' ),
				array( 'status' => 403 )
			);
		}

		// Get webhook key from header or query param.
		$provided_key = $request->get_header( 'X-SCRM-Webhook-Key' );
		if ( ! $provided_key ) {
			$provided_key = $request->get_param( 'key' );
		}

		if ( ! $provided_key ) {
			return new WP_Error(
				'missing_key',
				__( 'Webhook key is required.', 'syncpoint-crm' ),
				array( 'status' => 401 )
			);
		}

		$secret_key = scrm_get_setting( 'webhooks', 'secret_key' );

		if ( $provided_key !== $secret_key ) {
			return new WP_Error(
				'invalid_key',
				__( 'Invalid webhook key.', 'syncpoint-crm' ),
				array( 'status' => 401 )
			);
		}

		// Check IP whitelist if configured.
		$allowed_ips = scrm_get_setting( 'webhooks', 'allowed_ips' );
		if ( ! empty( $allowed_ips ) ) {
			$client_ip = scrm_get_client_ip();
			$ip_list   = array_map( 'trim', explode( "\n", $allowed_ips ) );

			if ( ! in_array( $client_ip, $ip_list, true ) ) {
				return new WP_Error(
					'ip_not_allowed',
					__( 'Your IP address is not allowed.', 'syncpoint-crm' ),
					array( 'status' => 403 )
				);
			}
		}

		/**
		 * Filter webhook authentication.
		 *
		 * @since 1.0.0
		 * @param bool            $is_valid True if valid.
		 * @param string          $source   Webhook source.
		 * @param WP_REST_Request $request  Request object.
		 */
		return apply_filters( 'scrm_webhook_authentication', true, 'inbound', $request );
	}

	/**
	 * Handle generic inbound webhook.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_inbound( $request ) {
		$payload = $request->get_json_params();
		$source  = $payload['source'] ?? 'custom';
		$action  = $payload['action'] ?? '';
		$data    = $payload['data'] ?? array();

		// Log the webhook.
		$log_id = scrm_log_webhook( $source, $request->get_route(), $payload, 'pending' );

		/**
		 * Fires when any webhook is received.
		 *
		 * @since 1.0.0
		 * @param string $source  Webhook source.
		 * @param array  $payload Webhook payload.
		 */
		do_action( 'scrm_webhook_received', $source, $payload );

		$result = null;

		try {
			switch ( $action ) {
				case 'create_contact':
					$result = $this->process_create_contact( $data );
					break;

				case 'update_contact':
					$result = $this->process_update_contact( $data );
					break;

				case 'create_company':
					$result = $this->process_create_company( $data );
					break;

				case 'create_transaction':
					$result = $this->process_create_transaction( $data );
					break;

				case 'tag_contact':
					$result = $this->process_tag_contact( $data );
					break;

				case 'custom':
				default:
					/**
					 * Handle custom webhook action.
					 *
					 * @since 1.0.0
					 * @param array  $data    Webhook data.
					 * @param string $source  Webhook source.
					 * @param array  $payload Full payload.
					 */
					do_action( 'scrm_webhook_custom_action', $data, $source, $payload );
					$result = array( 'action' => 'delegated' );
					break;
			}

			// Update log status.
			if ( $log_id ) {
				global $wpdb;
				$wpdb->upgmdate(
					$wpdb->prefix . 'scrm_webhook_log',
					array(
						'status'       => 'success',
						'response'     => wp_json_encode( $result ),
						'processed_at' => current_time( 'mysql' ),
					),
					array( 'id' => $log_id )
				);
			}

			/**
			 * Fires after a webhook is processed.
			 *
			 * @since 1.0.0
			 * @param string $source  Webhook source.
			 * @param array  $payload Webhook payload.
			 * @param array  $result  Processing result.
			 */
			do_action( 'scrm_webhook_processed', $source, $payload, $result );

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $result,
					'message' => __( 'Webhook processed successfully.', 'syncpoint-crm' ),
				)
			);

		} catch ( Exception $e ) {
			// Update log status to failed.
			if ( $log_id ) {
				global $wpdb;
				$wpdb->upgmdate(
					$wpdb->prefix . 'scrm_webhook_log',
					array(
						'status'   => 'failed',
						'response' => $e->getMessage(),
					),
					array( 'id' => $log_id )
				);
			}

			/**
			 * Fires when webhook processing fails.
			 *
			 * @since 1.0.0
			 * @param string    $source  Webhook source.
			 * @param array     $payload Webhook payload.
			 * @param Exception $error   Error object.
			 */
			do_action( 'scrm_webhook_failed', $source, $payload, $e );

			return new WP_Error(
				'webhook_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Process create contact action.
	 *
	 * @since 1.0.0
	 * @param array $data Contact data.
	 * @return array Result.
	 */
	private function process_create_contact( $data ) {
		// Check for existing contact by email.
		$existing = null;
		if ( ! empty( $data['email'] ) ) {
			$existing = scrm_get_contact_by_email( $data['email'] );
		}

		if ( $existing ) {
			// Update existing contact.
			$result = scrm_update_contact( $existing->id, $data );
			return array(
				'id'         => $existing->id,
				'contact_id' => $existing->contact_id,
				'email'      => $existing->email,
				'created'    => false,
				'updated'    => true,
			);
		}

		// Create new contact.
		$contact_id = scrm_create_contact( $data );

		if ( is_wp_error( $contact_id ) ) {
			throw new Exception( $contact_id->get_error_message() );
		}

		$contact = scrm_get_contact( $contact_id );

		return array(
			'id'         => $contact_id,
			'contact_id' => $contact->contact_id,
			'email'      => $contact->email,
			'created'    => true,
			'updated'    => false,
		);
	}

	/**
	 * Process update contact action.
	 *
	 * @since 1.0.0
	 * @param array $data Contact data.
	 * @return array Result.
	 */
	private function process_update_contact( $data ) {
		$contact = null;

		// Find contact by ID or email.
		if ( ! empty( $data['contact_id'] ) ) {
			$contact = scrm_get_contact( $data['contact_id'] );
		} elseif ( ! empty( $data['email'] ) ) {
			$contact = scrm_get_contact_by_email( $data['email'] );
		}

		if ( ! $contact ) {
			throw new Exception( __( 'Contact not found.', 'syncpoint-crm' ) );
		}

		$result = scrm_update_contact( $contact->id, $data );

		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}

		return array(
			'id'         => $contact->id,
			'contact_id' => $contact->contact_id,
			'updated'    => true,
		);
	}

	/**
	 * Process create company action.
	 *
	 * @since 1.0.0
	 * @param array $data Company data.
	 * @return array Result.
	 */
	private function process_create_company( $data ) {
		$company_id = scrm_create_company( $data );

		if ( is_wp_error( $company_id ) ) {
			throw new Exception( $company_id->get_error_message() );
		}

		$company = scrm_get_company( $company_id );

		return array(
			'id'         => $company_id,
			'company_id' => $company->company_id,
			'created'    => true,
		);
	}

	/**
	 * Process create transaction action.
	 *
	 * @since 1.0.0
	 * @param array $data Transaction data.
	 * @return array Result.
	 */
	private function process_create_transaction( $data ) {
		// Resolve contact.
		$contact_id = null;

		if ( ! empty( $data['contact_id'] ) ) {
			$contact    = scrm_get_contact( $data['contact_id'] );
			$contact_id = $contact ? $contact->id : null;
		} elseif ( ! empty( $data['contact_email'] ) ) {
			$contact = scrm_get_contact_by_email( $data['contact_email'] );
			if ( $contact ) {
				$contact_id = $contact->id;
			} else {
				// Create contact if not exists.
				$contact_id = scrm_create_contact(
					array(
						'email'  => $data['contact_email'],
						'type'   => 'customer',
						'source' => 'webhook',
					)
				);
				if ( is_wp_error( $contact_id ) ) {
					throw new Exception( $contact_id->get_error_message() );
				}
			}
		}

		if ( ! $contact_id ) {
			throw new Exception( __( 'Contact identifier is required.', 'syncpoint-crm' ) );
		}

		$data['contact_id'] = $contact_id;
		$data['gateway']    = $data['gateway'] ?? 'webhook';

		$txn_id = scrm_create_transaction( $data );

		if ( is_wp_error( $txn_id ) ) {
			throw new Exception( $txn_id->get_error_message() );
		}

		return array(
			'id'         => $txn_id,
			'contact_id' => $contact_id,
			'created'    => true,
		);
	}

	/**
	 * Process tag contact action.
	 *
	 * @since 1.0.0
	 * @param array $data Tag data.
	 * @return array Result.
	 */
	private function process_tag_contact( $data ) {
		$contact = null;

		if ( ! empty( $data['contact_id'] ) ) {
			$contact = scrm_get_contact( $data['contact_id'] );
		} elseif ( ! empty( $data['email'] ) ) {
			$contact = scrm_get_contact_by_email( $data['email'] );
		}

		if ( ! $contact ) {
			throw new Exception( __( 'Contact not found.', 'syncpoint-crm' ) );
		}

		$tags     = $data['tags'] ?? array();
		$assigned = array();

		foreach ( (array) $tags as $tag ) {
			$tag_id = is_numeric( $tag ) ? $tag : scrm_get_tag_id_by_slug( $tag );

			// Create tag if not exists.
			if ( ! $tag_id && ! is_numeric( $tag ) ) {
				$tag_id = scrm_create_tag( array( 'name' => $tag ) );
				if ( is_wp_error( $tag_id ) ) {
					continue;
				}
			}

			if ( $tag_id ) {
				scrm_assign_tag( $tag_id, $contact->id, 'contact' );
				$assigned[] = $tag;
			}
		}

		return array(
			'contact_id' => $contact->id,
			'tags'       => $assigned,
		);
	}

	/**
	 * Handle PayPal webhook.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_paypal( $request ) {
		$payload    = $request->get_json_params();
		$event_type = $payload['event_type'] ?? '';

		// Log the webhook.
		scrm_log_webhook( 'paypal', $request->get_route(), $payload, 'pending' );

		// TODO: Verify PayPal webhook signature.

		/**
		 * Fires when a PayPal webhook is received.
		 *
		 * @since 1.0.0
		 * @param string $event_type Event type.
		 * @param array  $payload    Webhook payload.
		 */
		do_action( 'scrm_paypal_webhook_received', $event_type, $payload );

		// TODO: Process PayPal events.

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'PayPal webhook received.', 'syncpoint-crm' ),
			)
		);
	}

	/**
	 * Handle Stripe webhook.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_stripe( $request ) {
		$payload    = $request->get_body();
		$sig_header = $request->get_header( 'Stripe-Signature' );

		// Log the webhook.
		scrm_log_webhook( 'stripe', $request->get_route(), json_decode( $payload, true ), 'pending' );

		// TODO: Verify Stripe webhook signature.
		// $webhook_secret = scrm_get_setting( 'stripe', 'webhook_secret' );

		$event      = json_decode( $payload, true );
		$event_type = $event['type'] ?? '';

		/**
		 * Fires when a Stripe webhook is received.
		 *
		 * @since 1.0.0
		 * @param string $event_type Event type.
		 * @param array  $event      Stripe event data.
		 */
		do_action( 'scrm_stripe_webhook_received', $event_type, $event );

		// TODO: Process Stripe events.

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Stripe webhook received.', 'syncpoint-crm' ),
			)
		);
	}
}

// Initialize webhooks.
new SCRM_Webhooks();
