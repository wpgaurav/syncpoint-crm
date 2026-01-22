<?php
/**
 * REST API Controller
 *
 * Handles REST API route registration and base functionality.
 *
 * @package StarterCRM
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SCRM_REST_API
 *
 * @since 1.0.0
 */
class SCRM_REST_API {

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
		// Nothing to initialize here.
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		// Contacts endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/contacts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_contacts' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_contact' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
					'args'                => $this->get_contact_params(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/contacts/(?P<id>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_contact' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_contact' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
					'args'                => $this->get_contact_params(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_contact' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
				),
			)
		);

		// Companies endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/companies',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_companies' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_company' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
				),
			)
		);

		// Transactions endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/transactions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_transactions' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_transaction' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
				),
			)
		);

		// Dashboard stats endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dashboard_stats' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// Tags endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/tags',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tags' ),
					'permission_callback' => array( $this, 'check_read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_tag' ),
					'permission_callback' => array( $this, 'check_write_permission' ),
				),
			)
		);
	}

	/**
	 * Check read permission.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function check_read_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access this endpoint.', 'syncpoint-crm' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'syncpoint-crm' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check write permission.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if allowed, error otherwise.
	 */
	public function check_write_permission( $request ) {
		return $this->check_read_permission( $request );
	}

	/**
	 * Get collection params.
	 *
	 * @since 1.0.0
	 * @return array Collection parameters.
	 */
	private function get_collection_params() {
		return array(
			'page'     => array(
				'description' => __( 'Page number.', 'syncpoint-crm' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'per_page' => array(
				'description' => __( 'Items per page.', 'syncpoint-crm' ),
				'type'        => 'integer',
				'default'     => 20,
				'minimum'     => 1,
				'maximum'     => 100,
			),
			'search'   => array(
				'description' => __( 'Search term.', 'syncpoint-crm' ),
				'type'        => 'string',
			),
			'orderby'  => array(
				'description' => __( 'Order by field.', 'syncpoint-crm' ),
				'type'        => 'string',
				'default'     => 'created_at',
			),
			'order'    => array(
				'description' => __( 'Order direction.', 'syncpoint-crm' ),
				'type'        => 'string',
				'default'     => 'DESC',
				'enum'        => array( 'ASC', 'DESC' ),
			),
		);
	}

	/**
	 * Get contact params.
	 *
	 * @since 1.0.0
	 * @return array Contact parameters.
	 */
	private function get_contact_params() {
		return array(
			'first_name' => array(
				'description' => __( 'First name.', 'syncpoint-crm' ),
				'type'        => 'string',
			),
			'last_name'  => array(
				'description' => __( 'Last name.', 'syncpoint-crm' ),
				'type'        => 'string',
			),
			'email'      => array(
				'description' => __( 'Email address.', 'syncpoint-crm' ),
				'type'        => 'string',
				'format'      => 'email',
				'required'    => true,
			),
			'phone'      => array(
				'description' => __( 'Phone number.', 'syncpoint-crm' ),
				'type'        => 'string',
			),
			'type'       => array(
				'description' => __( 'Contact type.', 'syncpoint-crm' ),
				'type'        => 'string',
				'enum'        => array( 'customer', 'lead', 'prospect' ),
				'default'     => 'customer',
			),
			'status'     => array(
				'description' => __( 'Contact status.', 'syncpoint-crm' ),
				'type'        => 'string',
				'enum'        => array( 'active', 'inactive', 'archived' ),
				'default'     => 'active',
			),
		);
	}

	/**
	 * Get contacts.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_contacts( $request ) {
		$args = array(
			'limit'   => $request->get_param( 'per_page' ),
			'offset'  => ( $request->get_param( 'page' ) - 1 ) * $request->get_param( 'per_page' ),
			'search'  => $request->get_param( 'search' ),
			'orderby' => $request->get_param( 'orderby' ),
			'order'   => $request->get_param( 'order' ),
			'type'    => $request->get_param( 'type' ),
			'status'  => $request->get_param( 'status' ),
		);

		$contacts = scrm_get_contacts( $args );
		$total    = scrm_count_contacts(
			array(
				'type'   => $request->get_param( 'type' ),
				'status' => $request->get_param( 'status' ),
			)
		);

		$data = array();
		foreach ( $contacts as $contact ) {
			$data[] = $this->prepare_contact_response( $contact );
		}

		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
				'meta'    => array(
					'total'       => $total,
					'page'        => $request->get_param( 'page' ),
					'per_page'    => $request->get_param( 'per_page' ),
					'total_pages' => ceil( $total / $request->get_param( 'per_page' ) ),
				),
			)
		);

		return $response;
	}

	/**
	 * Get single contact.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_contact( $request ) {
		$contact = scrm_get_contact( $request->get_param( 'id' ) );

		if ( ! $contact ) {
			return new WP_Error(
				'not_found',
				__( 'Contact not found.', 'syncpoint-crm' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->prepare_contact_response( $contact ),
			)
		);
	}

	/**
	 * Create contact.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function create_contact( $request ) {
		$data = array(
			'first_name' => $request->get_param( 'first_name' ),
			'last_name'  => $request->get_param( 'last_name' ),
			'email'      => $request->get_param( 'email' ),
			'phone'      => $request->get_param( 'phone' ),
			'type'       => $request->get_param( 'type' ),
			'status'     => $request->get_param( 'status' ),
			'company_id' => $request->get_param( 'company_id' ),
			'currency'   => $request->get_param( 'currency' ),
			'source'     => 'api',
		);

		/**
		 * Fires when a contact is created via REST API.
		 *
		 * @since 1.0.0
		 * @param int             $contact_id Contact ID.
		 * @param WP_REST_Request $request    Request object.
		 */
		$result = scrm_create_contact( array_filter( $data ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$contact = scrm_get_contact( $result );

		do_action( 'scrm_api_contact_created', $result, $request );

		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->prepare_contact_response( $contact ),
			)
		);
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update contact.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function update_contact( $request ) {
		$contact = scrm_get_contact( $request->get_param( 'id' ) );

		if ( ! $contact ) {
			return new WP_Error(
				'not_found',
				__( 'Contact not found.', 'syncpoint-crm' ),
				array( 'status' => 404 )
			);
		}

		$data = array_filter(
			array(
				'first_name' => $request->get_param( 'first_name' ),
				'last_name'  => $request->get_param( 'last_name' ),
				'email'      => $request->get_param( 'email' ),
				'phone'      => $request->get_param( 'phone' ),
				'type'       => $request->get_param( 'type' ),
				'status'     => $request->get_param( 'status' ),
			)
		);

		$result = scrm_update_contact( $contact->id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$contact = scrm_get_contact( $contact->id );

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $this->prepare_contact_response( $contact ),
			)
		);
	}

	/**
	 * Delete contact.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function delete_contact( $request ) {
		$contact = scrm_get_contact( $request->get_param( 'id' ) );

		if ( ! $contact ) {
			return new WP_Error(
				'not_found',
				__( 'Contact not found.', 'syncpoint-crm' ),
				array( 'status' => 404 )
			);
		}

		$force  = $request->get_param( 'force' ) === true;
		$result = scrm_delete_contact( $contact->id, $force );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => $force ? __( 'Contact deleted.', 'syncpoint-crm' ) : __( 'Contact archived.', 'syncpoint-crm' ),
			)
		);
	}

	/**
	 * Prepare contact for response.
	 *
	 * @since 1.0.0
	 * @param object $contact Contact object.
	 * @return array Prepared contact data.
	 */
	private function prepare_contact_response( $contact ) {
		$data = array(
			'id'            => (int) $contact->id,
			'contact_id'    => $contact->contact_id,
			'type'          => $contact->type,
			'status'        => $contact->status,
			'first_name'    => $contact->first_name,
			'last_name'     => $contact->last_name,
			'email'         => $contact->email,
			'phone'         => $contact->phone,
			'company_id'    => $contact->company_id ? (int) $contact->company_id : null,
			'currency'      => $contact->currency,
			'tax_id'        => $contact->tax_id,
			'address'       => array(
				'line_1'      => $contact->address_line_1,
				'line_2'      => $contact->address_line_2,
				'city'        => $contact->city,
				'state'       => $contact->state,
				'postal_code' => $contact->postal_code,
				'country'     => $contact->country,
			),
			'custom_fields' => $contact->custom_fields,
			'source'        => $contact->source,
			'tags'          => scrm_get_object_tags( $contact->id, 'contact' ),
			'created_at'    => $contact->created_at,
			'updated_at'    => $contact->updated_at,
		);

		/**
		 * Filter API contact response.
		 *
		 * @since 1.0.0
		 * @param array  $data    Prepared contact data.
		 * @param object $contact Contact object.
		 */
		return apply_filters( 'scrm_api_contact_response', $data, $contact );
	}

	/**
	 * Get companies.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_companies( $request ) {
		$companies = scrm_get_companies(
			array(
				'limit' => 50,
			)
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $companies,
			)
		);
	}

	/**
	 * Create company.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function create_company( $request ) {
		$result = scrm_create_company(
			array(
				'name'    => $request->get_param( 'name' ),
				'email'   => $request->get_param( 'email' ),
				'website' => $request->get_param( 'website' ),
				'phone'   => $request->get_param( 'phone' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$company = scrm_get_company( $result );

		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $company,
			)
		);
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Get transactions.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_transactions( $request ) {
		// TODO: Implement transactions list.
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(),
				'message' => __( 'Transactions endpoint coming soon.', 'syncpoint-crm' ),
			)
		);
	}

	/**
	 * Create transaction.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function create_transaction( $request ) {
		$result = scrm_create_transaction(
			array(
				'contact_id'  => $request->get_param( 'contact_id' ),
				'type'        => $request->get_param( 'type' ),
				'gateway'     => $request->get_param( 'gateway' ) ?: 'api',
				'amount'      => $request->get_param( 'amount' ),
				'currency'    => $request->get_param( 'currency' ),
				'status'      => $request->get_param( 'status' ),
				'description' => $request->get_param( 'description' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$transaction = scrm_get_transaction( $result );

		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $transaction,
			)
		);
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Get dashboard stats.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_dashboard_stats( $request ) {
		$period = $request->get_param( 'period' ) ?: '30days';

		$stats = array(
			'period'    => $period,
			'contacts'  => array(
				'total'   => scrm_count_contacts( array( 'status' => 'active' ) ),
				'new'     => 0, // TODO: Calculate based on period.
				'by_type' => array(
					'customer' => scrm_count_contacts(
						array(
							'type'   => 'customer',
							'status' => 'active',
						)
					),
					'lead'     => scrm_count_contacts(
						array(
							'type'   => 'lead',
							'status' => 'active',
						)
					),
					'prospect' => scrm_count_contacts(
						array(
							'type'   => 'prospect',
							'status' => 'active',
						)
					),
				),
			),
			'companies' => array(
				'total' => 0, // TODO: Count companies.
			),
			'revenue'   => array(
				'total'    => 0.00,
				'currency' => scrm_get_default_currency(),
			),
			'invoices'  => array(
				'sent'    => 0,
				'paid'    => 0,
				'overdue' => 0,
			),
		);

		/**
		 * Filter dashboard stats.
		 *
		 * @since 1.0.0
		 * @param array  $stats  Dashboard statistics.
		 * @param string $period Time period.
		 */
		$stats = apply_filters( 'scrm_dashboard_stats', $stats, $period );

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $stats,
			)
		);
	}

	/**
	 * Get tags.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_tags( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'scrm_tags';

		$tags = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" );

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $tags,
			)
		);
	}

	/**
	 * Create tag.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function create_tag( $request ) {
		$result = scrm_create_tag(
			array(
				'name'        => $request->get_param( 'name' ),
				'color'       => $request->get_param( 'color' ),
				'description' => $request->get_param( 'description' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$tag = scrm_get_tag( $result );

		$response = rest_ensure_response(
			array(
				'success' => true,
				'data'    => $tag,
			)
		);
		$response->set_status( 201 );

		return $response;
	}
}
